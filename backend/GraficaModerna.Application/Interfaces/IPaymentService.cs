using GraficaModerna.Domain.Entities;

namespace GraficaModerna.Application.Interfaces;

public interface IPaymentService
{
    Task<string> CreateCheckoutSessionAsync(Order order);

    Task RefundPaymentAsync(string paymentIntentId, decimal? amount = null);
}
