namespace GraficaModerna.Domain.Entities;

public class Product
{
    public Guid Id { get; private set; }
    public string Name { get; private set; }
    public string Description { get; private set; }
    public decimal Price { get; private set; }
    public string ImageUrl { get; private set; }
    public bool IsActive { get; private set; }
    public DateTime CreatedAt { get; private set; }

    // Construtor privado para EF Core
    protected Product() { }

    public Product(string name, string description, decimal price, string imageUrl)
    {
        ValidateDomain(name, price);

        Id = Guid.NewGuid();
        Name = name;
        Description = description;
        Price = price;
        ImageUrl = imageUrl;
        IsActive = true;
        CreatedAt = DateTime.UtcNow;
    }

    public void Update(string name, string description, decimal price, string imageUrl)
    {
        ValidateDomain(name, price);
        Name = name;
        Description = description;
        Price = price;
        ImageUrl = imageUrl;
    }

    public void Deactivate() => IsActive = false;

    private void ValidateDomain(string name, decimal price)
    {
        if (string.IsNullOrWhiteSpace(name))
            throw new ArgumentException("O nome do produto é obrigatório.");

        if (price < 0)
            throw new ArgumentException("O preço não pode ser negativo.");
    }
}