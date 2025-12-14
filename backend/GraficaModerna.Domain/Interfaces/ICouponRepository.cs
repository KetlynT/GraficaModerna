using GraficaModerna.Domain.Entities;

namespace GraficaModerna.Domain.Interfaces;

public interface ICouponRepository
{
    Task<Coupon?> GetByCodeAsync(string code);
    Task<List<Coupon>> GetAllAsync();
    Task AddAsync(Coupon coupon);
    Task DeleteAsync(Guid id);
    Task RecordUsageAsync(CouponUsage usage);
    Task<bool> IsUsageLimitReachedAsync(string userId, string couponCode);
}