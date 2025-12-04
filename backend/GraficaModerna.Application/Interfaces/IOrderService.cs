using GraficaModerna.Application.DTOs;

namespace GraficaModerna.Application.Interfaces;

public interface IOrderService
{
    Task<OrderDto> CreateOrderFromCartAsync(string userId, CreateAddressDto addressDto, string? couponCode, decimal frontendShippingCost, string shippingMethod);
    Task<List<OrderDto>> GetUserOrdersAsync(string userId);
    Task<List<OrderDto>> GetAllOrdersAsync(); // Apenas Admin

    // Método administrativo blindado
    Task UpdateAdminOrderAsync(Guid orderId, UpdateOrderStatusDto dto);

    Task PayOrderAsync(Guid orderId, string userId);
    Task RequestRefundAsync(Guid orderId, string userId);
    Task ConfirmPaymentViaWebhookAsync(Guid orderId, string transactionId);
    Task UpdateOrderStatusAsync(Guid orderId, string status, string? trackingCode);
}