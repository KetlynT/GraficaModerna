using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using GraficaModerna.Application.Services;
using GraficaModerna.Domain.Entities;
using GraficaModerna.Domain.Enums;
using GraficaModerna.Domain.Interfaces;
using Microsoft.AspNetCore.Http;
using Microsoft.AspNetCore.Identity;
using Microsoft.EntityFrameworkCore.Storage;
using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.Logging;
using Moq;
using Xunit;

namespace GraficaModerna.Tests.Services;

public class OrderServiceTests
{
    private readonly Mock<IUnitOfWork> _uowMock;
    private readonly Mock<IOrderRepository> _orderRepoMock;
    private readonly Mock<ICartRepository> _cartRepoMock;
    private readonly Mock<IProductRepository> _productRepoMock;
    private readonly Mock<ICouponRepository> _couponRepoMock;
    private readonly Mock<IEmailService> _emailServiceMock;
    private readonly Mock<UserManager<ApplicationUser>> _userManagerMock;
    private readonly Mock<IHttpContextAccessor> _httpContextAccessorMock;
    private readonly Mock<IShippingService> _shippingServiceMock;
    private readonly Mock<IPaymentService> _paymentServiceMock;
    private readonly Mock<IConfiguration> _configurationMock;
    private readonly Mock<ILogger<OrderService>> _loggerMock;
    private readonly OrderService _service;

    public OrderServiceTests()
    {
        _uowMock = new Mock<IUnitOfWork>();
        _orderRepoMock = new Mock<IOrderRepository>();
        _cartRepoMock = new Mock<ICartRepository>();
        _productRepoMock = new Mock<IProductRepository>();
        _couponRepoMock = new Mock<ICouponRepository>();

        _uowMock.Setup(u => u.Orders).Returns(_orderRepoMock.Object);
        _uowMock.Setup(u => u.Carts).Returns(_cartRepoMock.Object);
        _uowMock.Setup(u => u.Products).Returns(_productRepoMock.Object);
        _uowMock.Setup(u => u.Coupons).Returns(_couponRepoMock.Object);
        _uowMock.Setup(u => u.BeginTransactionAsync())
            .ReturnsAsync(new Mock<IDbContextTransaction>().Object);

        _emailServiceMock = new Mock<IEmailService>();
        _httpContextAccessorMock = new Mock<IHttpContextAccessor>();
        _shippingServiceMock = new Mock<IShippingService>();
        _paymentServiceMock = new Mock<IPaymentService>();
        _loggerMock = new Mock<ILogger<OrderService>>();
        _configurationMock = new Mock<IConfiguration>();

        _configurationMock.Setup(c => c["ADMIN_EMAIL"]).Returns("admin@test.com");

        var userStoreMock = new Mock<IUserStore<ApplicationUser>>();
        _userManagerMock = new Mock<UserManager<ApplicationUser>>(
            userStoreMock.Object, null!, null!, null!, null!, null!, null!, null!, null!);

        _service = new OrderService(
            _uowMock.Object,
            _emailServiceMock.Object,
            _userManagerMock.Object,
            _httpContextAccessorMock.Object,
            [_shippingServiceMock.Object],
            _paymentServiceMock.Object,
            _configurationMock.Object,
            _loggerMock.Object
        );
    }

    [Fact]
    public async Task CreateOrderFromCartAsync_ShouldCreateOrder()
    {
        var userId = "user123";
        var product = new Product("P1", "D1", 100m, "img", 1, 10, 10, 10, 50);
        var cart = new Cart { Id = Guid.NewGuid(), UserId = userId };
        cart.Items.Add(new CartItem { Product = product, ProductId = product.Id, Quantity = 2 });

        _cartRepoMock.Setup(c => c.GetByUserIdAsync(userId)).ReturnsAsync(cart);

        var addressDto = new CreateAddressDto("Home", "Me", "12345678", "St", "1", "", "Neigh", "City", "ST", "", "11999999999", false);
        var shippingOption = new ShippingOptionDto { Name = "Sedex", Price = 20m };

        _shippingServiceMock.Setup(s => s.CalculateAsync(It.IsAny<string>(), It.IsAny<List<ShippingItemDto>>()))
            .ReturnsAsync([shippingOption]);

        var result = await _service.CreateOrderFromCartAsync(userId, addressDto, null, "Sedex");

        Assert.NotNull(result);
        Assert.Equal(220m, result.TotalAmount);
        Assert.Equal("Pendente", result.Status);

        _uowMock.Verify(u => u.Orders.AddAsync(It.IsAny<Order>()), Times.Once);
        _uowMock.Verify(u => u.Carts.ClearCartAsync(cart.Id), Times.Once);
        _uowMock.Verify(u => u.CommitAsync(), Times.AtLeastOnce);
    }

    [Fact]
    public async Task ConfirmPaymentViaWebhookAsync_ShouldUpdateStatusAndStock()
    {
        var orderId = Guid.NewGuid();
        var product = new Product("P1", "D1", 100m, "img", 1, 10, 10, 10, 50) { Id = Guid.NewGuid() };

        var order = new Order
        {
            Id = orderId,
            UserId = "u1",
            TotalAmount = 100m,
            Status = OrderStatus.Pendente,
            Items = [new() { ProductId = product.Id, Quantity = 10, UnitPrice = 10m }]
        };

        _orderRepoMock.Setup(r => r.GetByIdAsync(orderId)).ReturnsAsync(order);
        _productRepoMock.Setup(r => r.GetByIdsAsync(It.IsAny<List<Guid>>())).ReturnsAsync([product]);

        await _service.ConfirmPaymentViaWebhookAsync(orderId, "txn_123", 10000);

        Assert.Equal(OrderStatus.Pago, order.Status);
        Assert.Equal("txn_123", order.StripePaymentIntentId);
        Assert.Equal(40, product.StockQuantity);

        _uowMock.Verify(u => u.Products.UpdateAsync(product), Times.Once);
        _uowMock.Verify(u => u.CommitAsync(), Times.AtLeastOnce);
    }

    [Fact]
    public async Task RequestRefundAsync_ShouldSetRefundRequested()
    {
        var userId = "u1";
        var orderId = Guid.NewGuid();
        var order = new Order { Id = orderId, UserId = userId, Status = OrderStatus.Entregue, TotalAmount = 100m };

        _orderRepoMock.Setup(r => r.GetByIdAsync(orderId)).ReturnsAsync(order);

        var dto = new RequestRefundDto("Total", null);

        await _service.RequestRefundAsync(orderId, userId, dto);

        Assert.Equal("Total", order.RefundType);
        Assert.Equal(100m, order.RefundRequestedAmount);

        _uowMock.Verify(u => u.Orders.UpdateAsync(order), Times.Once);
        _uowMock.Verify(u => u.CommitAsync(), Times.Once);
    }

    [Fact]
    public async Task ConfirmPaymentViaWebhookAsync_ShouldDetectFraud_WhenAmountMismatch()
    {
        var order = new Order
        {
            Id = Guid.NewGuid(),
            UserId = "user1",
            TotalAmount = 200.00m,
            Status = OrderStatus.Pendente
        };

        _orderRepoMock.Setup(r => r.GetByIdAsync(order.Id)).ReturnsAsync(order);

        var ex = await Assert.ThrowsAsync<Exception>(() =>
            _service.ConfirmPaymentViaWebhookAsync(order.Id, "txn_fraud", 1));

        Assert.Contains("Divergência de valores", ex.Message);

        _emailServiceMock.Verify(e => e.SendEmailAsync(
            It.IsAny<string>(),
            It.Is<string>(s => s.Contains("ALERTA DE SEGURANÇA")),
            It.IsAny<string>()), Times.Once);
    }

    [Fact]
    public async Task CreateOrderFromCartAsync_ShouldThrow_WhenCouponAlreadyUsed()
    {
        var userId = "user_coupon_abuser";
        var product = new Product("P1", "D", 100m, "img", 1, 1, 1, 1, 10);
        var cart = new Cart { UserId = userId, Id = Guid.NewGuid() };
        cart.Items.Add(new CartItem { Product = product, ProductId = product.Id, Quantity = 1 });

        _cartRepoMock.Setup(c => c.GetByUserIdAsync(userId)).ReturnsAsync(cart);

        var coupon = new Coupon("UNIQUETIME", 50, 10);
        _couponRepoMock.Setup(c => c.GetByCodeAsync("UNIQUETIME")).ReturnsAsync(coupon);
        _couponRepoMock.Setup(c => c.IsUsageLimitReachedAsync(userId, "UNIQUETIME")).ReturnsAsync(true);

        var address = new CreateAddressDto("Casa", "Eu", "12345678", "Rua", "1", "", "Bairro", "Cidade", "UF", "", "1199999999", false);
        _shippingServiceMock.Setup(s => s.CalculateAsync(It.IsAny<string>(), It.IsAny<List<ShippingItemDto>>()))
            .ReturnsAsync([new() { Name = "Correios", Price = 10 }]);

        var ex = await Assert.ThrowsAsync<Exception>(() =>
            _service.CreateOrderFromCartAsync(userId, address, "UNIQUETIME", "Correios"));

        Assert.Equal("Cupom já utilizado.", ex.Message);
    }

    [Fact]
    public async Task ConfirmPaymentViaWebhookAsync_DeveDetectarFraude_QuandoValorPagoForMenorQuePedido()
    {
        var order = new Order
        {
            Id = Guid.NewGuid(),
            UserId = "user_fraude",
            TotalAmount = 200.00m,
            Status = OrderStatus.Pendente
        };

        _orderRepoMock.Setup(r => r.GetByIdAsync(order.Id)).ReturnsAsync(order);

        var ex = await Assert.ThrowsAsync<Exception>(() =>
            _service.ConfirmPaymentViaWebhookAsync(order.Id, "txn_fraude_123", 1));

        Assert.Contains("Divergência de valores", ex.Message);

        _emailServiceMock.Verify(e => e.SendEmailAsync(
            It.IsAny<string>(),
            It.Is<string>(s => s.Contains("ALERTA DE SEGURANÇA")),
            It.IsAny<string>()), Times.Once);
    }

    [Fact]
    public async Task CreateOrderFromCartAsync_DeveImpedirUso_DeCupomJaUtilizadoPeloUsuario()
    {
        var userId = "user_cupom_duplicado";
        var product = new Product("P1", "D", 100m, "img", 1, 1, 1, 1, 10);
        var cart = new Cart { UserId = userId, Id = Guid.NewGuid() };
        cart.Items.Add(new CartItem { Product = product, ProductId = product.Id, Quantity = 1 });

        _cartRepoMock.Setup(c => c.GetByUserIdAsync(userId)).ReturnsAsync(cart);

        var coupon = new Coupon("PROMOUNIC", 50, 10);
        _couponRepoMock.Setup(c => c.GetByCodeAsync("PROMOUNIC")).ReturnsAsync(coupon);
        _couponRepoMock.Setup(c => c.IsUsageLimitReachedAsync(userId, "PROMOUNIC")).ReturnsAsync(true);

        var address = new CreateAddressDto("Casa", "Eu", "12345678", "Rua", "1", "", "Bairro", "Cidade", "UF", "", "1199999999", false);
        _shippingServiceMock.Setup(s => s.CalculateAsync(It.IsAny<string>(), It.IsAny<List<ShippingItemDto>>()))
            .ReturnsAsync([new() { Name = "Sedex", Price = 10 }]);

        var ex = await Assert.ThrowsAsync<Exception>(() =>
            _service.CreateOrderFromCartAsync(userId, address, "PROMOUNIC", "Sedex"));

        Assert.Equal("Cupom já utilizado.", ex.Message);
    }
}