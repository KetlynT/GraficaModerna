using Microsoft.EntityFrameworkCore.Storage;
using System.Data;

namespace GraficaModerna.Domain.Interfaces;

public interface IUnitOfWork : IDisposable
{
    IProductRepository Products { get; }
    ICartRepository Carts { get; }
    IOrderRepository Orders { get; }
    IAddressRepository Addresses { get; }
    ICouponRepository Coupons { get; }

    Task CommitAsync();
    Task<IDbContextTransaction> BeginTransactionAsync(IsolationLevel isolationLevel = IsolationLevel.ReadCommitted);
}
