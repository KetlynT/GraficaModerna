using GraficaModerna.Domain.Entities;

namespace GraficaModerna.Domain.Interfaces;

public interface ICartRepository
{
    Task<Cart?> GetByUserIdAsync(string userId);
    Task<Cart?> GetByUserIdWithLockAsync(string userId);
    Task AddAsync(Cart cart);
    Task RemoveItemAsync(CartItem item);
    Task ClearCartAsync(Guid cartId);
}