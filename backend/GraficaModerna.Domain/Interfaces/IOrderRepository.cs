using GraficaModerna.Domain.Entities;
using GraficaModerna.Domain.Models;

namespace GraficaModerna.Domain.Interfaces;

public interface IOrderRepository
{
    Task AddAsync(Order order);
    Task<Order?> GetByIdAsync(Guid id);
    Task<Order?> GetByTransactionIdAsync(string transactionId);
    Task<PagedResultDto<Order>> GetByUserIdAsync(string userId, int page, int pageSize);
    Task<PagedResultDto<Order>> GetAllAsync(int page, int pageSize);
    Task UpdateAsync(Order order);
}