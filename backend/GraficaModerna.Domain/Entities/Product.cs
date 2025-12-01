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

    // Propriedades para Frete
    public decimal Weight { get; private set; }
    public int Width { get; private set; }
    public int Height { get; private set; }
    public int Length { get; private set; }

    // CORREÇÃO: Inicializando propriedades não nulas para evitar erro CS8618
    protected Product()
    {
        Name = string.Empty;
        Description = string.Empty;
        ImageUrl = string.Empty;
    }

    public Product(string name, string description, decimal price, string imageUrl, decimal weight, int width, int height, int length)
    {
        ValidateDomain(name, price, weight, width, height, length);

        Id = Guid.NewGuid();
        Name = name;
        Description = description;
        Price = price;
        ImageUrl = imageUrl;
        Weight = weight;
        Width = width;
        Height = height;
        Length = length;
        IsActive = true;
        CreatedAt = DateTime.UtcNow;
    }

    public void Update(string name, string description, decimal price, string imageUrl, decimal weight, int width, int height, int length)
    {
        ValidateDomain(name, price, weight, width, height, length);
        Name = name;
        Description = description;
        Price = price;
        ImageUrl = imageUrl;
        Weight = weight;
        Width = width;
        Height = height;
        Length = length;
    }

    public void Deactivate() => IsActive = false;

    private void ValidateDomain(string name, decimal price, decimal weight, int width, int height, int length)
    {
        if (string.IsNullOrWhiteSpace(name))
            throw new ArgumentException("O nome do produto é obrigatório.");

        if (price < 0)
            throw new ArgumentException("O preço não pode ser negativo.");

        if (weight <= 0)
            throw new ArgumentException("O peso deve ser maior que zero.");

        if (width < 0 || height < 0 || length < 0)
            throw new ArgumentException("As dimensões não podem ser negativas.");
    }
}