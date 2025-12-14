using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Services;
using GraficaModerna.Domain.Entities;
using GraficaModerna.Domain.Interfaces;
using Microsoft.EntityFrameworkCore.Storage;
using Microsoft.Extensions.Logging;
using Moq;
using System.Data;
using Xunit;

namespace GraficaModerna.Tests.Services;

public class CartServiceTests
{
    private readonly Mock<IUnitOfWork> _uowMock;
    private readonly Mock<ICartRepository> _cartRepoMock;
    private readonly Mock<IProductRepository> _productRepoMock;
    private readonly Mock<ILogger<CartService>> _loggerMock;
    private readonly CartService _service;

    public CartServiceTests()
    {
        _uowMock = new Mock<IUnitOfWork>();
        _cartRepoMock = new Mock<ICartRepository>();
        _productRepoMock = new Mock<IProductRepository>();
        _loggerMock = new Mock<ILogger<CartService>>();

        // Configura o UoW para retornar os mocks dos repositórios
        _uowMock.Setup(u => u.Carts).Returns(_cartRepoMock.Object);
        _uowMock.Setup(u => u.Products).Returns(_productRepoMock.Object);

        // Mock da transação para não falhar no 'using var transaction'
        _uowMock.Setup(u => u.BeginTransactionAsync(It.IsAny<IsolationLevel>()))
            .ReturnsAsync(new Mock<IDbContextTransaction>().Object);

        // Instancia o serviço SEM o AppDbContext
        _service = new CartService(_uowMock.Object, _loggerMock.Object);
    }

    [Fact]
    public async Task AddItemAsync_ShouldAggregateQuantity_WhenItemAlreadyExists()
    {
        // Arrange
        var userId = "user_agg";
        var productId = Guid.NewGuid();
        var product = new Product("P1", "D", 10m, "url", 1, 1, 1, 1, 100) { Id = productId };

        var cart = new Cart { UserId = userId };
        cart.Items.Add(new CartItem { ProductId = productId, Quantity = 2, Product = product });

        // Mock dos métodos WithLock que o serviço usa agora
        _productRepoMock.Setup(r => r.GetByIdWithLockAsync(productId))
            .ReturnsAsync(product);

        _cartRepoMock.Setup(r => r.GetByUserIdWithLockAsync(userId))
            .ReturnsAsync(cart);

        var dto = new AddToCartDto(productId, 3);

        // Act
        await _service.AddItemAsync(userId, dto);

        // Assert
        Assert.Single(cart.Items);
        Assert.Equal(5, cart.Items.First().Quantity); // 2 + 3 = 5

        _uowMock.Verify(u => u.CommitAsync(), Times.Once);
    }

    [Fact]
    public async Task AddItemAsync_ShouldThrowException_WhenProductIsInactive()
    {
        // Arrange
        var userId = "user_inactive";
        var productId = Guid.NewGuid();
        // O repositório geralmente retorna null se o produto não estiver ativo (conforme cláusula WHERE no repository)
        // ou podemos retornar o produto e verificar se o serviço checa o status, 
        // mas o SQL do repositório já filtra IsActive=true.

        _productRepoMock.Setup(r => r.GetByIdWithLockAsync(productId))
            .ReturnsAsync((Product?)null); // Simula produto não encontrado ou inativo

        var dto = new AddToCartDto(productId, 1);

        // Act & Assert
        var ex = await Assert.ThrowsAsync<InvalidOperationException>(() =>
            _service.AddItemAsync(userId, dto));

        Assert.Contains("indisponível", ex.Message);
    }

    [Fact]
    public async Task GetCartAsync_ShouldRemoveOrphanedItems()
    {
        // Arrange
        var userId = "user_orphan";
        var activeProduct = new Product("Active", "D", 10m, "url", 1, 1, 1, 1, 10);
        var inactiveProduct = new Product("Inactive", "D", 10m, "url", 1, 1, 1, 1, 10);
        inactiveProduct.Deactivate();

        var cart = new Cart { UserId = userId };
        var itemActive = new CartItem { Product = activeProduct, ProductId = activeProduct.Id, Quantity = 1 };
        var itemInactive = new CartItem { Product = inactiveProduct, ProductId = inactiveProduct.Id, Quantity = 1 };

        cart.Items.Add(itemActive);
        cart.Items.Add(itemInactive);

        _cartRepoMock.Setup(r => r.GetByUserIdAsync(userId)).ReturnsAsync(cart);

        // Act
        var result = await _service.GetCartAsync(userId);

        // Assert
        // O serviço retorna DTO apenas dos itens ativos
        Assert.Single(result.Items);
        Assert.Equal("Active", result.Items.First().ProductName);

        // Verifica se chamou a remoção do item inativo
        _cartRepoMock.Verify(r => r.RemoveItemAsync(itemInactive), Times.Once);
        _uowMock.Verify(u => u.CommitAsync(), Times.Once);
    }
}