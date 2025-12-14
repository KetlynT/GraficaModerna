using GraficaModerna.Domain.Entities;

namespace GraficaModerna.Domain.Interfaces;

public interface IAddressRepository
{
    Task<List<UserAddress>> GetByUserIdAsync(string userId);
    Task<UserAddress?> GetByIdAsync(Guid id, string userId);
    Task AddAsync(UserAddress address);
    Task DeleteAsync(UserAddress address);
    Task<bool> HasAnyAsync(string userId);
}