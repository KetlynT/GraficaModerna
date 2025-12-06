namespace GraficaModerna.Domain.Entities;

public class Cart
{
    public Guid Id { get; set; }
    public string UserId { get; set; } = string.Empty; 

    public List<CartItem> Items { get; set; } = [];
    public DateTime LastUpdated { get; set; } = DateTime.UtcNow;
}
