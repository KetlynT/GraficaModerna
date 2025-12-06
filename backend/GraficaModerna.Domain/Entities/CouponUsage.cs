namespace GraficaModerna.Domain.Entities;

public class CouponUsage
{
    public Guid Id { get; set; } = Guid.NewGuid();
    public string UserId { get; set; } = string.Empty;
    public string CouponCode { get; set; } = string.Empty;
    public Guid OrderId { get; set; }
    public DateTime UsedAt { get; set; } = DateTime.UtcNow;
}
