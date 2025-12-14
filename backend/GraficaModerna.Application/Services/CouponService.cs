using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using GraficaModerna.Domain.Entities;
using GraficaModerna.Domain.Interfaces;

namespace GraficaModerna.Application.Services;

public class CouponService(IUnitOfWork uow) : ICouponService
{
    private readonly IUnitOfWork _uow = uow;

    public async Task<CouponResponseDto> CreateAsync(CreateCouponDto dto)
    {
        var existing = await _uow.Coupons.GetByCodeAsync(dto.Code);
        if (existing != null)
            throw new Exception("Cupom já existe.");

        var coupon = new Coupon(dto.Code, dto.DiscountPercentage, dto.ValidityDays);

        await _uow.Coupons.AddAsync(coupon);
        await _uow.CommitAsync();

        return new CouponResponseDto(coupon.Id, coupon.Code, coupon.DiscountPercentage, coupon.ExpiryDate,
            coupon.IsActive);
    }

    public async Task<List<CouponResponseDto>> GetAllAsync()
    {
        var coupons = await _uow.Coupons.GetAllAsync();

        return
        [
            ..coupons.Select(c => new CouponResponseDto(
            c.Id, c.Code, c.DiscountPercentage, c.ExpiryDate, c.IsActive))
        ];
    }

    public async Task DeleteAsync(Guid id)
    {
        await _uow.Coupons.DeleteAsync(id);
        await _uow.CommitAsync();
    }

    public async Task<Coupon?> GetValidCouponAsync(string code)
    {
        var coupon = await _uow.Coupons.GetByCodeAsync(code);

        if (coupon == null || !coupon.IsValid()) return null;
        return coupon;
    }
}