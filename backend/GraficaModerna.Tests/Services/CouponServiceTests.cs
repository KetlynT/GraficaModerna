using GraficaModerna.Application.DTOs;
using GraficaModerna.Domain.Entities;
using GraficaModerna.Infrastructure.Context;
using GraficaModerna.Infrastructure.Services;
using Microsoft.EntityFrameworkCore;
using Xunit;

namespace GraficaModerna.Tests.Services;

public class CouponServiceTests
{
    private readonly AppDbContext _context;
    private readonly CouponService _service;

    public CouponServiceTests()
    {
        var options = new DbContextOptionsBuilder<AppDbContext>()
            .UseInMemoryDatabase(
                databaseName: Guid.NewGuid().ToString()
            )
            .Options;

        _context = new AppDbContext(options);
        _service = new CouponService(_context);
    }

    [Fact]
    public async Task CreateAsync_ShouldCreateCoupon()
    {
        var dto = new CreateCouponDto(
            "TEST10",
            10,
            30
        );

        var result = await _service.CreateAsync(dto);

        Assert.NotNull(result);
        Assert.Equal("TEST10", result.Code);
        Assert.Single(_context.Coupons);
    }

    [Fact]
    public async Task CreateAsync_ShouldThrowException_WhenCodeExists()
    {
        _context.Coupons.Add(
            new Coupon(
                "DUPLICATE",
                10,
                10
            )
        );

        await _context.SaveChangesAsync();

        var dto = new CreateCouponDto(
            "DUPLICATE",
            20,
            20
        );

        await Assert.ThrowsAsync<Exception>(
            () => _service.CreateAsync(dto)
        );
    }

    [Fact]
    public async Task GetValidCouponAsync_ShouldReturnCoupon_WhenValid()
    {
        var coupon = new Coupon(
            "VALID",
            15,
            5
        );

        _context.Coupons.Add(coupon);
        await _context.SaveChangesAsync();

        var result = await _service.GetValidCouponAsync("valid");

        Assert.NotNull(result);
        Assert.Equal(15, result.DiscountPercentage);
    }

    [Fact]
    public async Task GetValidCouponAsync_ShouldReturnNull_WhenExpired()
    {
        var coupon = new Coupon(
            "EXPIRED",
            10,
            -1
        );

        _context.Coupons.Add(coupon);
        await _context.SaveChangesAsync();

        var result = await _service.GetValidCouponAsync("EXPIRED");

        Assert.Null(result);
    }
}
