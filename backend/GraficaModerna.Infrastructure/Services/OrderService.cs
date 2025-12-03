using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using GraficaModerna.Domain.Entities;
using GraficaModerna.Domain.Interfaces;
using Microsoft.AspNetCore.Http;
using Microsoft.AspNetCore.Identity;
using Microsoft.EntityFrameworkCore;

namespace GraficaModerna.Infrastructure.Services;

public class OrderService : IOrderService
{
    private readonly IUnitOfWork _uow;
    private readonly IEmailService _emailService;
    private readonly UserManager<ApplicationUser> _userManager;
    // Dependências novas para segurança
    private readonly IHttpContextAccessor _httpContextAccessor;
    private readonly IEnumerable<IShippingService> _shippingServices;

    public OrderService(
        IUnitOfWork uow,
        IEmailService emailService,
        UserManager<ApplicationUser> userManager,
        IHttpContextAccessor httpContextAccessor,
        IEnumerable<IShippingService> shippingServices)
    {
        _uow = uow;
        _emailService = emailService;
        _userManager = userManager;
        _httpContextAccessor = httpContextAccessor;
        _shippingServices = shippingServices;
    }

    public async Task<OrderDto> CreateOrderFromCartAsync(string userId, CreateAddressDto addressDto, string? couponCode, decimal frontendShippingCost, string shippingMethod)
    {
        // 1. Recálculo de Frete (SEGURANÇA CRÍTICA: Não confiar no frontendShippingCost)
        var cart = await _uow.Carts.GetByUserIdAsync(userId);
        if (cart == null || !cart.Items.Any()) throw new Exception("Carrinho vazio.");

        // Monta os itens para cálculo de frete usando dados do banco (Product)
        var shippingItems = cart.Items.Select(i => new ShippingItemDto
        {
            ProductId = i.ProductId,
            Weight = i.Product!.Weight,
            Width = i.Product.Width,
            Height = i.Product.Height,
            Length = i.Product.Length,
            Quantity = i.Quantity
        }).ToList();

        decimal verifiedShippingCost = 0;
        bool shippingMethodFound = false;

        // Executa cálculo real nos provedores
        var shippingTasks = _shippingServices.Select(s => s.CalculateAsync(addressDto.ZipCode, shippingItems));
        var shippingResults = await Task.WhenAll(shippingTasks);
        var allOptions = shippingResults.SelectMany(x => x).ToList();

        // Tenta encontrar a opção escolhida pelo usuário
        var selectedOption = allOptions.FirstOrDefault(o => o.Name == shippingMethod);

        if (selectedOption != null)
        {
            verifiedShippingCost = selectedOption.Price;
            shippingMethodFound = true;
        }
        else
        {
            // Fallback: Se o método não existir mais, pega o mais barato ou lança erro
            // Aqui vamos lançar erro para segurança
            throw new Exception("O método de envio selecionado não está mais disponível ou é inválido.");
        }

        // --- Início da Transação ---
        using var transaction = await _uow.BeginTransactionAsync();
        try
        {
            decimal subTotal = 0;
            var orderItems = new List<OrderItem>();

            foreach (var item in cart.Items)
            {
                if (item.Product == null) continue;

                // Controle de Concorrência (DebitStock)
                item.Product.DebitStock(item.Quantity);

                subTotal += item.Quantity * item.Product.Price;

                orderItems.Add(new OrderItem
                {
                    ProductId = item.ProductId,
                    ProductName = item.Product.Name,
                    Quantity = item.Quantity,
                    UnitPrice = item.Product.Price
                });
            }

            decimal discount = 0;
            if (!string.IsNullOrEmpty(couponCode))
            {
                var coupon = await _uow.Coupons.GetByCodeAsync(couponCode);
                if (coupon != null && coupon.IsValid())
                    discount = subTotal * (coupon.DiscountPercentage / 100m);
            }

            // Usa o custo verificado, não o do parâmetro
            decimal totalAmount = (subTotal - discount) + verifiedShippingCost;

            var formattedAddress = $"{addressDto.Street}, {addressDto.Number}";
            if (!string.IsNullOrWhiteSpace(addressDto.Complement)) formattedAddress += $" - {addressDto.Complement}";
            formattedAddress += $" - {addressDto.Neighborhood}, {addressDto.City}/{addressDto.State}";
            if (!string.IsNullOrWhiteSpace(addressDto.Reference)) formattedAddress += $" (Ref: {addressDto.Reference})";
            formattedAddress += $" - A/C: {addressDto.ReceiverName} - Tel: {addressDto.PhoneNumber}";

            // Captura de IP para Auditoria
            var clientIp = _httpContextAccessor.HttpContext?.Connection?.RemoteIpAddress?.ToString();

            var order = new Order
            {
                UserId = userId,
                ShippingAddress = formattedAddress,
                ShippingZipCode = addressDto.ZipCode,
                ShippingCost = verifiedShippingCost, // Valor Seguro
                ShippingMethod = shippingMethod,
                Status = "Pendente",
                OrderDate = DateTime.UtcNow,
                SubTotal = subTotal,
                Discount = discount,
                TotalAmount = totalAmount,
                AppliedCoupon = !string.IsNullOrEmpty(couponCode) ? couponCode.ToUpper() : null,
                Items = orderItems,
                CustomerIp = clientIp // Auditoria
            };

            await _uow.Orders.AddAsync(order);
            await _uow.Carts.ClearCartAsync(cart.Id);

            await _uow.CommitAsync();
            await transaction.CommitAsync();

            // Envio de e-mail (Fire and forget)
            try
            {
                var user = await _userManager.FindByIdAsync(userId);
                if (user != null) _ = _emailService.SendEmailAsync(user.Email!, "Pedido Confirmado", $"Seu pedido #{order.Id} foi recebido. Total: {totalAmount:C}");
            }
            catch { }

            return MapToDto(order);
        }
        catch (DbUpdateConcurrencyException)
        {
            await transaction.RollbackAsync();
            throw new Exception("Alguns itens do seu carrinho acabaram de ficar sem estoque. Por favor, revise seu pedido.");
        }
        catch
        {
            await transaction.RollbackAsync();
            throw;
        }
    }

    // ... (Mantenha os outros métodos existentes abaixo sem alterações: PayOrderAsync, GetUserOrdersAsync, etc.)

    public async Task PayOrderAsync(Guid orderId, string userId)
    {
        var order = await _uow.Orders.GetByIdAsync(orderId);
        if (order == null) throw new Exception("Pedido não encontrado.");
        if (order.UserId != userId) throw new Exception("Acesso negado.");
        if (order.Status != "Pendente") throw new Exception($"Status inválido: {order.Status}");

        order.Status = "Pago";
        await _uow.Orders.UpdateAsync(order);
        await _uow.CommitAsync();
    }

    public async Task ConfirmPaymentViaWebhookAsync(Guid orderId, string transactionId)
    {
        var order = await _uow.Orders.GetByIdAsync(orderId);
        if (order == null || order.Status == "Pago") return;

        order.Status = "Pago";
        await _uow.Orders.UpdateAsync(order);
        await _uow.CommitAsync();
    }

    public async Task<List<OrderDto>> GetUserOrdersAsync(string userId)
    {
        var orders = await _uow.Orders.GetByUserIdAsync(userId);
        return orders.Select(MapToDto).ToList();
    }

    public async Task<List<OrderDto>> GetAllOrdersAsync()
    {
        var orders = await _uow.Orders.GetAllAsync();
        return orders.Select(MapToDto).ToList();
    }

    public async Task UpdateOrderStatusAsync(Guid orderId, string status, string? trackingCode)
    {
        var order = await _uow.Orders.GetByIdAsync(orderId);
        if (order != null)
        {
            if (status == "Entregue" && order.Status != "Entregue")
                order.DeliveryDate = DateTime.UtcNow;

            order.Status = status;
            if (!string.IsNullOrEmpty(trackingCode)) order.TrackingCode = trackingCode;

            await _uow.Orders.UpdateAsync(order);
            await _uow.CommitAsync();
        }
    }

    // Método auxiliar para Admin
    public async Task UpdateAdminOrderAsync(Guid orderId, UpdateOrderStatusDto dto)
    {
        var order = await _uow.Orders.GetByIdAsync(orderId);
        if (order == null) throw new Exception("Pedido não encontrado");

        if (dto.Status == "Entregue" && order.Status != "Entregue")
            order.DeliveryDate = DateTime.UtcNow;

        order.Status = dto.Status;

        if (!string.IsNullOrEmpty(dto.TrackingCode))
            order.TrackingCode = dto.TrackingCode;

        if (!string.IsNullOrEmpty(dto.ReverseLogisticsCode))
            order.ReverseLogisticsCode = dto.ReverseLogisticsCode;

        if (!string.IsNullOrEmpty(dto.ReturnInstructions))
            order.ReturnInstructions = dto.ReturnInstructions;

        await _uow.Orders.UpdateAsync(order);
        await _uow.CommitAsync();
    }

    public async Task RequestRefundAsync(Guid orderId, string userId)
    {
        var order = await _uow.Orders.GetByIdAsync(orderId);
        if (order == null || order.UserId != userId) throw new Exception("Pedido não encontrado.");

        var allowedStatuses = new[] { "Pago", "Enviado", "Entregue" };
        if (!allowedStatuses.Contains(order.Status))
            throw new Exception($"Status ({order.Status}) não permite solicitação.");

        if (order.Status == "Entregue")
        {
            if (!order.DeliveryDate.HasValue)
                throw new Exception("Data de entrega não registrada.");

            var deadline = order.DeliveryDate.Value.AddDays(7);
            if (DateTime.UtcNow > deadline)
                throw new Exception("Prazo de 7 dias expirado.");
        }

        order.Status = "Reembolso Solicitado";
        await _uow.Orders.UpdateAsync(order);
        await _uow.CommitAsync();
    }

    private static OrderDto MapToDto(Order order)
    {
        return new OrderDto(
            order.Id,
            order.OrderDate,
            order.DeliveryDate,
            order.TotalAmount,
            order.Status,
            order.TrackingCode,
            order.ReverseLogisticsCode,
            order.ReturnInstructions,
            order.ShippingAddress,
            order.Items.Select(i => new OrderItemDto(i.ProductName, i.Quantity, i.UnitPrice, i.Quantity * i.UnitPrice)).ToList()
        );
    }
}