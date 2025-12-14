using GraficaModerna.Domain.Interfaces;
using GraficaModerna.Infrastructure.Context;
using Microsoft.EntityFrameworkCore;
using Microsoft.EntityFrameworkCore.Storage;
using System.Data;

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
    public IEmailTemplateRepository EmailTemplates => _emailTemplates ??= new EmailTemplateRepository(_context);
    private IEmailTemplateRepository? _emailTemplates;
    public async Task CommitAsync()
    {
        await _context.SaveChangesAsync();
    }

    public async Task<IDbContextTransaction> BeginTransactionAsync(IsolationLevel isolationLevel = IsolationLevel.ReadCommitted)
    {
        return await _context.Database.BeginTransactionAsync(isolationLevel);
    }

    public void Dispose()
    {
        _context.Dispose();
        GC.SuppressFinalize(this);
    }
}