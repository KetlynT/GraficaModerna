using GraficaModerna.Domain.Entities;
using GraficaModerna.Domain.Interfaces;
using GraficaModerna.Infrastructure.Context;
using Microsoft.EntityFrameworkCore;

namespace GraficaModerna.Infrastructure.Repositories;

public class CouponRepository(AppDbContext context) : ICouponRepository
{
    private readonly AppDbContext _context = context;

    public async Task<Coupon?> GetByCodeAsync(string code)
    {
        return await _context.Coupons.FirstOrDefaultAsync(c =>
            c.Code.Equals(code, StringComparison.CurrentCultureIgnoreCase));
    }

    public async Task<List<Coupon>> GetAllAsync()
    {
        return await _context.Coupons.OrderByDescending(c => c.ExpiryDate).ToListAsync();
    }

    public async Task AddAsync(Coupon coupon)
    {
        await _context.Coupons.AddAsync(coupon);
    }

    public async Task DeleteAsync(Guid id)
    {
        var coupon = await _context.Coupons.FindAsync(id);
        if (coupon != null) _context.Coupons.Remove(coupon);
    }

    public async Task RecordUsageAsync(CouponUsage usage)
    {
        await _context.CouponUsages.AddAsync(usage);
    }

    public async Task<bool> IsUsageLimitReachedAsync(string userId, string couponCode)
    {
        return await _context.CouponUsages.AnyAsync(u =>
            u.UserId == userId &&
            u.CouponCode == couponCode);
    }
}