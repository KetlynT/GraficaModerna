namespace GraficaModerna.Domain.Entities;

public class OrderItem
{
    public Guid Id { get; set; }
    public Guid OrderId { get; set; }
    public Guid ProductId { get; set; }

    public string ProductName { get; set; } = string.Empty;
    public decimal UnitPrice { get; set; }
    public int Quantity { get; set; }
    public int RefundQuantity { get; set; }
    public Order? Order { get; set; }
    public Product? Product { get; set; } 
}
