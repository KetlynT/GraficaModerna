using GraficaModerna.Application.DTOs;
using GraficaModerna.Domain.Entities;
using GraficaModerna.Domain.Interfaces;
using GraficaModerna.Infrastructure.Context;
using GraficaModerna.Infrastructure.Services;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Logging;
using Moq;
using Xunit;

namespace GraficaModerna.Tests.Services;

public class CartServiceTests
{
    private readonly AppDbContext _context;
    private readonly Mock<IUnitOfWork> _uowMock;
    private readonly Mock<ICartRepository> _cartRepoMock;
    private readonly Mock<ILogger<CartService>> _loggerMock;
    private readonly CartService _service;

    public CartServiceTests()
    {
        var options = new DbContextOptionsBuilder<AppDbContext>()
            .UseInMemoryDatabase(
                databaseName: Guid.NewGuid().ToString()
            )
            .Options;

        _context = new AppDbContext(options);

        _uowMock = new Mock<IUnitOfWork>();
        _cartRepoMock = new Mock<ICartRepository>();
        _loggerMock = new Mock<ILogger<CartService>>();

        _uowMock
            .Setup(u => u.Carts)
            .Returns(_cartRepoMock.Object);

        _service = new CartService(
            _uowMock.Object,
            _context,
            _loggerMock.Object
        );
    }

    [Fact]
    public async Task GetCartAsync_ShouldReturnCartWithTotal()
    {
        var userId = "user1";

        var product = new Product(
            "P1",
            "D1",
            50m,
            "url",
            1,
            1,
            1,
            1,
            100
        );

        var cart = new Cart
        {
            Id = Guid.NewGuid(),
            UserId = userId
        };

        cart.Items.Add(
            new CartItem
            {
                Id = Guid.NewGuid(),
                ProductId = product.Id,
                Product = product,
                Quantity = 2
            }
        );

        _cartRepoMock
            .Setup(r => r.GetByUserIdAsync(userId))
            .ReturnsAsync(cart);

        var result = await _service.GetCartAsync(userId);

        Assert.NotNull(result);
        Assert.Single(result.Items);
        Assert.Equal(100m, result.GrandTotal);
    }

    [Fact]
    public async Task RemoveItemAsync_ShouldRemoveItem()
    {
        var userId = "user1";
        var itemId = Guid.NewGuid();

        var cart = new Cart
        {
            UserId = userId
        };

        var item = new CartItem
        {
            Id = itemId
        };

        cart.Items.Add(item);

        _cartRepoMock
            .Setup(r => r.GetByUserIdAsync(userId))
            .ReturnsAsync(cart);

        await _service.RemoveItemAsync(userId, itemId);

        _cartRepoMock.Verify(
            r => r.RemoveItemAsync(item),
            Times.Once
        );

        _uowMock.Verify(
            u => u.CommitAsync(),
            Times.Once
        );
    }

    [Fact]
    public async Task ClearCartAsync_ShouldClearAllItems()
    {
        var userId = "user1";
        var cartId = Guid.NewGuid();

        var cart = new Cart
        {
            Id = cartId,
            UserId = userId
        };

        _cartRepoMock
            .Setup(r => r.GetByUserIdAsync(userId))
            .ReturnsAsync(cart);

        await _service.ClearCartAsync(userId);

        _cartRepoMock.Verify(
            r => r.ClearCartAsync(cartId),
            Times.Once
        );

        _uowMock.Verify(
            u => u.CommitAsync(),
            Times.Once
        );
    }
}
