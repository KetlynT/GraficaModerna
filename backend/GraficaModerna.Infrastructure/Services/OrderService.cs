using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using GraficaModerna.Domain.Entities;
using GraficaModerna.Infrastructure.Context;
using Microsoft.EntityFrameworkCore;

namespace GraficaModerna.Infrastructure.Services;

public class OrderService : IOrderService
{
    private readonly AppDbContext _context;
    private readonly IEmailService _emailService;
    private readonly ICouponService _couponService;

    public OrderService(AppDbContext context, IEmailService emailService, ICouponService couponService)
    {
        _context = context;
        _emailService = emailService;
        _couponService = couponService;
    }

    public async Task<OrderDto> CreateOrderFromCartAsync(string userId, CreateAddressDto addressDto, string? couponCode)
    {
        // SEGURANÇA: Execução dentro de transação para garantir integridade
        using var transaction = await _context.Database.BeginTransactionAsync();

        try
        {
            var cart = await _context.Carts
                .Include(c => c.Items).ThenInclude(i => i.Product)
                .FirstOrDefaultAsync(c => c.UserId == userId);

            if (cart == null || !cart.Items.Any()) throw new Exception("Carrinho vazio.");

            decimal subTotal = 0;
            var orderItems = new List<OrderItem>();

            foreach (var item in cart.Items)
            {
                if (item.Product == null) continue;

                // SEGURANÇA: Preços devem vir do banco, nunca do cliente/cache antigo
                if (item.Product.StockQuantity < item.Quantity)
                    throw new Exception($"Estoque insuficiente para {item.Product.Name}.");

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
                var coupon = await _couponService.GetValidCouponAsync(couponCode);
                if (coupon != null) discount = subTotal * (coupon.DiscountPercentage / 100m);
            }

            // FORMATAÇÃO DO ENDEREÇO PARA O SNAPSHOT DO PEDIDO
            // Convertemos os campos separados em uma string única para registro histórico
            var formattedAddress = $"{addressDto.Street}, {addressDto.Number}";
            if (!string.IsNullOrWhiteSpace(addressDto.Complement)) formattedAddress += $" - {addressDto.Complement}";
            formattedAddress += $" - {addressDto.Neighborhood}, {addressDto.City}/{addressDto.State}";
            if (!string.IsNullOrWhiteSpace(addressDto.Reference)) formattedAddress += $" (Ref: {addressDto.Reference})";
            formattedAddress += $" - A/C: {addressDto.ReceiverName} - Tel: {addressDto.PhoneNumber}";

            var order = new Order
            {
                UserId = userId,
                ShippingAddress = formattedAddress, // Snapshot formatado
                ShippingZipCode = addressDto.ZipCode,
                Status = "Pendente",
                OrderDate = DateTime.UtcNow,
                SubTotal = subTotal,
                Discount = discount,
                TotalAmount = subTotal - discount,
                AppliedCoupon = !string.IsNullOrEmpty(couponCode) ? couponCode.ToUpper() : null,
                Items = orderItems
            };

            _context.Orders.Add(order);
            _context.CartItems.RemoveRange(cart.Items);

            await _context.SaveChangesAsync();
            await transaction.CommitAsync();

            try
            {
                var user = await _context.Users.FindAsync(userId);
                if (user != null) _ = _emailService.SendEmailAsync(user.Email!, "Pedido Confirmado", $"Seu pedido #{order.Id} foi recebido com sucesso.");
            }
            catch { }

            return MapToDto(order);
        }
        catch (DbUpdateConcurrencyException)
        {
            await transaction.RollbackAsync();
            throw new Exception("Ops! Um dos itens do seu carrinho acabou de esgotar enquanto você finalizava. Por favor, revise o carrinho.");
        }
        catch
        {
            await transaction.RollbackAsync();
            throw;
        }
    }

    public async Task PayOrderAsync(Guid orderId, string userId)
    {
        using var transaction = await _context.Database.BeginTransactionAsync();
        try
        {
            var order = await _context.Orders.FindAsync(orderId);

            if (order == null) throw new Exception("Pedido não encontrado.");
            if (order.UserId != userId) throw new Exception("Acesso negado: Você não pode pagar um pedido que não é seu.");
            if (order.Status != "Pendente") throw new Exception($"Pedido não pode ser pago pois está: {order.Status}");

            order.Status = "Pago";
            await _context.SaveChangesAsync();
            await transaction.CommitAsync();
        }
        catch
        {
            await transaction.RollbackAsync();
            throw;
        }
    }

    public async Task ConfirmPaymentViaWebhookAsync(Guid orderId, string transactionId)
    {
        using var transaction = await _context.Database.BeginTransactionAsync();
        try
        {
            var order = await _context.Orders.FindAsync(orderId);
            if (order == null) throw new Exception("Pedido não encontrado.");
            if (order.Status == "Pago") return;
            if (order.Status != "Pendente") throw new Exception($"Pedido não pode ser pago pois está: {order.Status}");

            order.Status = "Pago";
            await _context.SaveChangesAsync();
            await transaction.CommitAsync();
        }
        catch
        {
            await transaction.RollbackAsync();
            throw;
        }
    }

    public async Task<List<OrderDto>> GetUserOrdersAsync(string userId)
    {
        var orders = await _context.Orders
            .Include(o => o.Items)
            .Where(o => o.UserId == userId)
            .OrderByDescending(o => o.OrderDate)
            .ToListAsync();

        return orders.Select(MapToDto).ToList();
    }

    public async Task<List<OrderDto>> GetAllOrdersAsync()
    {
        var orders = await _context.Orders
            .Include(o => o.Items)
            .OrderByDescending(o => o.OrderDate)
            .ToListAsync();

        return orders.Select(MapToDto).ToList();
    }

    public async Task UpdateOrderStatusAsync(Guid orderId, string status, string? trackingCode)
    {
        var order = await _context.Orders.FindAsync(orderId);
        if (order != null)
        {
            order.Status = status;
            if (!string.IsNullOrEmpty(trackingCode)) order.TrackingCode = trackingCode;
            await _context.SaveChangesAsync();
        }
    }

    public async Task RequestRefundAsync(Guid orderId, string userId)
    {
        var order = await _context.Orders.FirstOrDefaultAsync(o => o.Id == orderId && o.UserId == userId);
        if (order == null) throw new Exception("Pedido não encontrado.");

        var allowedStatuses = new[] { "Pago", "Enviado" };
        if (!allowedStatuses.Contains(order.Status))
        {
            throw new Exception($"Não é possível solicitar reembolso para pedidos com status: {order.Status}");
        }

        order.Status = "Reembolso Solicitado";
        await _context.SaveChangesAsync();
    }

    private static OrderDto MapToDto(Order order)
    {
        return new OrderDto(
            order.Id,
            order.OrderDate,
            order.TotalAmount,
            order.Status,
            order.TrackingCode,
            order.ShippingAddress,
            order.Items.Select(i => new OrderItemDto(i.ProductName, i.Quantity, i.UnitPrice, i.Quantity * i.UnitPrice)).ToList()
        );
    }
}