namespace GraficaModerna.Domain.Entities;

public class Coupon
{

    public Coupon()
    {
    }

    public Coupon(string code, decimal percentage, int daysValid)
    {
        Id = Guid.NewGuid();
        Code = code.ToUpper().Trim();
        DiscountPercentage = percentage;
        ExpiryDate = DateTime.UtcNow.AddDays(daysValid);
        IsActive = true;
    }

    public Guid Id { get; set; }
    public string Code { get; set; } = string.Empty;
    public decimal DiscountPercentage { get; set; } 
    public DateTime ExpiryDate { get; set; }
    public bool IsActive { get; set; } = true;

    public bool IsValid()
    {
        return IsActive && DateTime.UtcNow <= ExpiryDate;
    }
}
