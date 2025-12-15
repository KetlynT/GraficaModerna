using GraficaModerna.Domain.Interfaces;
using GraficaModerna.Infrastructure.Context;
using Microsoft.EntityFrameworkCore;
using Microsoft.EntityFrameworkCore.Storage;
using Microsoft.Extensions.Logging;
using System.Data;

namespace GraficaModerna.Infrastructure.Repositories;

public class UnitOfWork(AppDbContext context, ILoggerFactory loggerFactory) : IUnitOfWork
{
    private readonly AppDbContext _context = context;
    private readonly ILoggerFactory _loggerFactory = loggerFactory;

    private IProductRepository? _products;
    private ICartRepository? _carts;
    private IOrderRepository? _orders;
    private IAddressRepository? _addresses;
    private ICouponRepository? _coupons;
    private IEmailTemplateRepository? _emailTemplates;

    public IProductRepository Products =>
        _products ??= new ProductRepository(_context, _loggerFactory.CreateLogger<ProductRepository>());

    public ICartRepository Carts =>
        _carts ??= new CartRepository(_context);

    public IOrderRepository Orders =>
        _orders ??= new OrderRepository(_context);

    public IAddressRepository Addresses =>
        _addresses ??= new AddressRepository(_context);

    public ICouponRepository Coupons =>
        _coupons ??= new CouponRepository(_context);

    public IEmailTemplateRepository EmailTemplates =>
        _emailTemplates ??= new EmailTemplateRepository(_context);

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