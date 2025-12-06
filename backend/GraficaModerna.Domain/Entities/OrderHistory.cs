namespace GraficaModerna.Domain.Entities;

public class OrderHistory
{
    public Guid Id { get; set; }
    public Guid OrderId { get; set; }
    public string Status { get; set; } = string.Empty; 
    public string? Message { get; set; } 
    public string ChangedBy { get; set; } = string.Empty; 
    public DateTime Timestamp { get; set; } = DateTime.UtcNow;

    public string? IpAddress { get; set; }
    public string? UserAgent { get; set; }


}
