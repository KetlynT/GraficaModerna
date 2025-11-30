using GraficaModerna.Domain.Entities;

namespace GraficaModerna.Domain.Interfaces;

public interface IProductRepository
{
    Task<IEnumerable<Product>> GetAllAsync();
    Task<Product?> GetByIdAsync(Guid id);
    Task<Product> CreateAsync(Product product);
    Task UpdateAsync(Product product);
    // Delete lógico via Update (IsActive = false)
}