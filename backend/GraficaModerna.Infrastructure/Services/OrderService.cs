using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using GraficaModerna.Domain.Entities;
using GraficaModerna.Infrastructure.Context;
using Microsoft.AspNetCore.Http;
using Microsoft.AspNetCore.Identity;
using Microsoft.EntityFrameworkCore;

namespace GraficaModerna.Infrastructure.Services;

public class OrderService : IOrderService
{
    private readonly AppDbContext _context; // Uso direto do Context para transações
    private readonly IEmailService _emailService;
    private readonly UserManager<ApplicationUser> _userManager;
    private readonly IHttpContextAccessor _httpContextAccessor;
    private readonly IEnumerable<IShippingService> _shippingServices;
    private readonly IPaymentService _paymentService;

    public OrderService(
        AppDbContext context,
        IEmailService emailService,
        UserManager<ApplicationUser> userManager,
        IHttpContextAccessor httpContextAccessor,
        IEnumerable<IShippingService> shippingServices,
        IPaymentService paymentService)
    {
        _context = context;
        _emailService = emailService;
        _userManager = userManager;
        _httpContextAccessor = httpContextAccessor;
        _shippingServices = shippingServices;
        _paymentService = paymentService;
    }

    public async Task<OrderDto> CreateOrderFromCartAsync(string userId, CreateAddressDto addressDto, string? couponCode, decimal frontendShippingCost, string shippingMethod)
    {
        var cart = await _context.Carts
            .Include(c => c.Items)
            .ThenInclude(i => i.Product)
            .FirstOrDefaultAsync(c => c.UserId == userId);

        if (cart == null || !cart.Items.Any()) throw new Exception("Carrinho vazio.");

        // 1. Recálculo e Validação Robusta de Frete
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
        var shippingTasks = _shippingServices.Select(s => s.CalculateAsync(addressDto.ZipCode, shippingItems));
        var shippingResults = await Task.WhenAll(shippingTasks);
        var allOptions = shippingResults.SelectMany(x => x).ToList();

        // CORREÇÃO: Comparação insensível a maiúsculas/espaços
        var selectedOption = allOptions.FirstOrDefault(o =>
            o.Name.Trim().Equals(shippingMethod.Trim(), StringComparison.InvariantCultureIgnoreCase));

        // Fallback: Se o nome mudou na API, tenta achar pelo preço exato
        if (selectedOption == null)
        {
            selectedOption = allOptions.FirstOrDefault(o => o.Price == frontendShippingCost);
        }

        if (selectedOption != null) verifiedShippingCost = selectedOption.Price;
        else throw new Exception("Método de envio inválido ou indisponível para o CEP informado. Tente atualizar a página.");

        // 2. Transação com Controle de Concorrência
        using var transaction = await _context.Database.BeginTransactionAsync();
        try
        {
            decimal subTotal = 0;
            var orderItems = new List<OrderItem>();

            foreach (var item in cart.Items)
            {
                if (item.Product == null) continue;

                // CORREÇÃO: Tenta debitar. Se falhar no SaveChanges por RowVersion, cai no catch.
                item.Product.DebitStock(item.Quantity);
                _context.Entry(item.Product).State = EntityState.Modified;

                subTotal += item.Quantity * item.Product.Price;
                orderItems.Add(new OrderItem
                {
                    ProductId = item.ProductId,
                    ProductName = item.Product.Name,
                    Quantity = item.Quantity,
                    UnitPrice = item.Product.Price
                });
            }

            // 3. Validação Real de Cupom (Único por usuário)
            decimal discount = 0;
            if (!string.IsNullOrEmpty(couponCode))
            {
                var coupon = await _context.Coupons.FirstOrDefaultAsync(c => c.Code == couponCode.ToUpper());

                if (coupon != null && coupon.IsValid())
                {
                    bool alreadyUsed = await _context.CouponUsages.AnyAsync(u => u.UserId == userId && u.CouponCode == coupon.Code);
                    if (alreadyUsed) throw new Exception("Cupom já utilizado por este usuário.");

                    discount = subTotal * (coupon.DiscountPercentage / 100m);
                }
            }

            decimal totalAmount = (subTotal - discount) + verifiedShippingCost;
            var formattedAddress = $"{addressDto.Street}, {addressDto.Number} - {addressDto.Complement} - {addressDto.Neighborhood}, {addressDto.City}/{addressDto.State} (Ref: {addressDto.Reference}) - A/C: {addressDto.ReceiverName} - Tel: {addressDto.PhoneNumber}";

            var order = new Order
            {
                UserId = userId,
                ShippingAddress = formattedAddress,
                ShippingZipCode = addressDto.ZipCode,
                ShippingCost = verifiedShippingCost,
                ShippingMethod = selectedOption?.Name ?? shippingMethod,
                Status = "Pendente",
                OrderDate = DateTime.UtcNow,
                SubTotal = subTotal,
                Discount = discount,
                TotalAmount = totalAmount,
                AppliedCoupon = !string.IsNullOrEmpty(couponCode) ? couponCode.ToUpper() : null,
                Items = orderItems,
                CustomerIp = _httpContextAccessor.HttpContext?.Connection?.RemoteIpAddress?.ToString()
            };

            _context.Orders.Add(order);
            await _context.SaveChangesAsync();

            // Registra uso do cupom
            if (discount > 0 && !string.IsNullOrEmpty(couponCode))
            {
                _context.CouponUsages.Add(new CouponUsage
                {
                    UserId = userId,
                    CouponCode = couponCode.ToUpper(),
                    OrderId = order.Id,
                    UsedAt = DateTime.UtcNow
                });
            }

            // Limpa carrinho
            _context.CartItems.RemoveRange(cart.Items);

            // Commit final (aqui o RowVersion valida o estoque)
            await _context.SaveChangesAsync();
            await transaction.CommitAsync();

            try
            {
                var user = await _userManager.FindByIdAsync(userId);
                if (user != null) _ = _emailService.SendEmailAsync(user.Email!, "Pedido Recebido", $"Seu pedido #{order.Id} foi criado. Aguardando pagamento.");
            }
            catch { }

            return MapToDto(order);
        }
        catch (DbUpdateConcurrencyException)
        {
            await transaction.RollbackAsync();
            throw new Exception("Sinto muito! Um dos itens do seu carrinho esgotou-se no exato momento da compra. Por favor, revise seu carrinho.");
        }
        catch (Exception)
        {
            await transaction.RollbackAsync();
            throw;
        }
    }

    public async Task UpdateAdminOrderAsync(Guid orderId, UpdateOrderStatusDto dto)
    {
        var order = await _context.Orders.FirstOrDefaultAsync(o => o.Id == orderId);
        if (order == null) throw new Exception("Pedido não encontrado");

        // Lógica de Devolução
        if (dto.Status == "Aguardando Devolução")
        {
            if (!string.IsNullOrEmpty(dto.ReverseLogisticsCode)) order.ReverseLogisticsCode = dto.ReverseLogisticsCode;
            order.ReturnInstructions = !string.IsNullOrEmpty(dto.ReturnInstructions) ? dto.ReturnInstructions : "Instruções padrão de devolução...";
        }

        // CORREÇÃO: Reembolso Seguro
        if (dto.Status == "Reembolsado" || dto.Status == "Cancelado")
        {
            if (!string.IsNullOrEmpty(order.StripePaymentIntentId))
            {
                try
                {
                    await _paymentService.RefundPaymentAsync(order.StripePaymentIntentId);
                    order.ReturnInstructions += " [Sistema: Reembolso processado no Stripe]";
                }
                catch (Exception ex)
                {
                    // IMPEDE A MUDANÇA DE STATUS SE O REEMBOLSO FALHAR
                    throw new Exception($"Erro no Stripe: {ex.Message}. O status do pedido NÃO foi alterado.");
                }
            }
        }

        if (dto.Status == "Entregue" && order.Status != "Entregue") order.DeliveryDate = DateTime.UtcNow;
        order.Status = dto.Status;
        if (!string.IsNullOrEmpty(dto.TrackingCode)) order.TrackingCode = dto.TrackingCode;

        await _context.SaveChangesAsync();
    }

    public async Task ConfirmPaymentViaWebhookAsync(Guid orderId, string transactionId)
    {
        var order = await _context.Orders.FindAsync(orderId);
        if (order != null && order.Status != "Pago")
        {
            order.Status = "Pago";
            order.StripePaymentIntentId = transactionId;
            await _context.SaveChangesAsync();
        }
    }

    public async Task PayOrderAsync(Guid orderId, string userId)
    {
        var order = await _context.Orders.FirstOrDefaultAsync(o => o.Id == orderId && o.UserId == userId);
        if (order == null) throw new Exception("Pedido inválido.");
        order.Status = "Pago";
        await _context.SaveChangesAsync();
    }

    public async Task UpdateOrderStatusAsync(Guid orderId, string status, string? trackingCode)
    {
        var dto = new UpdateOrderStatusDto(status, trackingCode, null, null);
        await UpdateAdminOrderAsync(orderId, dto);
    }

    public async Task<List<OrderDto>> GetUserOrdersAsync(string userId)
    {
        var orders = await _context.Orders.Where(o => o.UserId == userId).Include(o => o.Items).OrderByDescending(o => o.OrderDate).ToListAsync();
        return orders.Select(MapToDto).ToList();
    }

    public async Task<List<OrderDto>> GetAllOrdersAsync()
    {
        var orders = await _context.Orders.Include(o => o.Items).OrderByDescending(o => o.OrderDate).ToListAsync();
        return orders.Select(MapToDto).ToList();
    }

    public async Task RequestRefundAsync(Guid orderId, string userId)
    {
        var order = await _context.Orders.FirstOrDefaultAsync(o => o.Id == orderId && o.UserId == userId);
        if (order == null) throw new Exception("Pedido não encontrado.");
        order.Status = "Reembolso Solicitado";
        await _context.SaveChangesAsync();
    }

    private static OrderDto MapToDto(Order order)
    {
        return new OrderDto(
            order.Id,
            order.OrderDate,
            order.DeliveryDate,
            order.SubTotal,
            order.Discount,
            order.ShippingCost,
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