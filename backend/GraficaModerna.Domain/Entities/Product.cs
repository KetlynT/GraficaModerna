using System.ComponentModel.DataAnnotations;

namespace GraficaModerna.Domain.Entities;

public class Product(string name, string description, decimal price, string imageUrl, decimal weight, int width, int height, int length, int stockQuantity) : BaseEntity
{
    public string Name { get; private set; } = name;
    public string Description { get; private set; } = description;
    public decimal Price { get; private set; } = price;
    public string ImageUrl { get; private set; } = imageUrl;
    public decimal Weight { get; private set; } = weight;
    public int Width { get; private set; } = width;
    public int Height { get; private set; } = height;
    public int Length { get; private set; } = length;
    public int StockQuantity { get; private set; } = stockQuantity;
    public bool IsActive { get; private set; } = true;

    // CORREÇÃO: Campo para controle de concorrência (Optimistic Concurrency)
    [Timestamp]
    public byte[]? RowVersion { get; set; }

    public void Update(string name, string description, decimal price, string imageUrl, decimal weight, int width, int height, int length, int stockQuantity)
    {
        Name = name;
        Description = description;
        Price = price;
        ImageUrl = imageUrl;
        Weight = weight;
        Width = width;
        Height = height;
        Length = length;
        StockQuantity = stockQuantity;
    }

    public void DebitStock(int quantity)
    {
        if (quantity < 0) throw new ArgumentException("Quantidade inválida.");

        // A validação final ocorre no banco via RowVersion, mas essa checagem previne erros óbvios
        if (StockQuantity < quantity)
            throw new InvalidOperationException($"Estoque insuficiente para o produto '{Name}'.");

        StockQuantity -= quantity;
    }

    public void ReplenishStock(int quantity)
    {
        StockQuantity += quantity;
    }

    public void Deactivate()
    {
        IsActive = false;
    }
}