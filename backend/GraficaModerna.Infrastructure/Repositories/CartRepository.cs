using GraficaModerna.Domain.Entities;
using GraficaModerna.Domain.Interfaces;
using GraficaModerna.Infrastructure.Context;
using Microsoft.EntityFrameworkCore;

namespace GraficaModerna.Infrastructure.Repositories;

public class CartRepository(AppDbContext context) : ICartRepository
{
    private readonly AppDbContext _context = context;

    public async Task<Cart?> GetByUserIdAsync(string userId)
    {
        return await _context.Carts
            .Include(c => c.Items).ThenInclude(i => i.Product)
            .FirstOrDefaultAsync(c => c.UserId == userId);
    }

    public async Task AddAsync(Cart cart)
    {
        await _context.Carts.AddAsync(cart);
    }

    public Task RemoveItemAsync(CartItem item)
    {
        _context.CartItems.Remove(item);
        return Task.CompletedTask;
    }

    public async Task ClearCartAsync(Guid cartId)
    {
        var items = await _context.CartItems.Where(c => c.CartId == cartId).ToListAsync();
        _context.CartItems.RemoveRange(items);
    }
}