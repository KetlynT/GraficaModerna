using GraficaModerna.Domain.Interfaces;
using GraficaModerna.Infrastructure.Context;
using Microsoft.EntityFrameworkCore.Storage;

namespace GraficaModerna.Infrastructure.Repositories;

public class UnitOfWork(
    AppDbContext context,
    IProductRepository products,
    ICartRepository carts,
    IOrderRepository orders,
    IAddressRepository addresses,
    ICouponRepository coupons) : IUnitOfWork
{
    private readonly AppDbContext _context = context;

    public IProductRepository Products { get; } = products;
    public ICartRepository Carts { get; } = carts;
    public IOrderRepository Orders { get; } = orders;
    public IAddressRepository Addresses { get; } = addresses;
    public ICouponRepository Coupons { get; } = coupons;

    public async Task CommitAsync()
    {
        await _context.SaveChangesAsync();
    }

    public async Task<IDbContextTransaction> BeginTransactionAsync()
    {
        return await _context.Database.BeginTransactionAsync();
    }

    public void Dispose()
    {
        _context.Dispose();
        GC.SuppressFinalize(this);
    }
}
