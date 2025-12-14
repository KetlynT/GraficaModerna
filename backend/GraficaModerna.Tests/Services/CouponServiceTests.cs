using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Services;
using GraficaModerna.Domain.Entities;
using GraficaModerna.Domain.Interfaces;
using Moq;
using Xunit;

namespace GraficaModerna.Tests.Services;

public class CouponServiceTests
{
    private readonly Mock<IUnitOfWork> _uowMock;
    private readonly Mock<ICouponRepository> _couponRepoMock;
    private readonly CouponService _service;

    public CouponServiceTests()
    {
        _uowMock = new Mock<IUnitOfWork>();
        _couponRepoMock = new Mock<ICouponRepository>();

        _uowMock.Setup(u => u.Coupons).Returns(_couponRepoMock.Object);

        _service = new CouponService(_uowMock.Object);
    }

    [Fact]
    public async Task CreateAsync_ShouldCreateCoupon()
    {
        var dto = new CreateCouponDto(
            "TEST10",
            10,
            30
        );

        _couponRepoMock.Setup(r => r.GetByCodeAsync("TEST10")).ReturnsAsync((Coupon?)null);

        Coupon? capturedCoupon = null;
        _couponRepoMock.Setup(r => r.AddAsync(It.IsAny<Coupon>()))
            .Callback<Coupon>(c => capturedCoupon = c);

        var result = await _service.CreateAsync(dto);

        Assert.NotNull(result);
        Assert.Equal("TEST10", result.Code);
        _uowMock.Verify(u => u.CommitAsync(), Times.Once);
    }

    [Fact]
    public async Task CreateAsync_ShouldThrowException_WhenCodeExists()
    {
        var existingCoupon = new Coupon("DUPLICATE", 10, 10);

        _couponRepoMock.Setup(r => r.GetByCodeAsync("DUPLICATE")).ReturnsAsync(existingCoupon);

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

        _couponRepoMock.Setup(r => r.GetByCodeAsync("VALID")).ReturnsAsync(coupon);

        var result = await _service.GetValidCouponAsync("valid"); // O serviço deve tratar uppercase/lowercase

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

        _couponRepoMock.Setup(r => r.GetByCodeAsync("EXPIRED")).ReturnsAsync(coupon);

        var result = await _service.GetValidCouponAsync("EXPIRED");

        Assert.Null(result);
    }
}