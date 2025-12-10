using GraficaModerna.Application.DTOs;
using GraficaModerna.Domain.Entities;
using GraficaModerna.Domain.Interfaces;
using GraficaModerna.Infrastructure.Services;
using Moq;
using Xunit;

namespace GraficaModerna.Tests.Services;

public class AddressServiceTests
{
    private readonly Mock<IUnitOfWork> _uowMock;
    private readonly Mock<IAddressRepository> _addressRepoMock;
    private readonly AddressService _service;

    public AddressServiceTests()
    {
        _uowMock = new Mock<IUnitOfWork>();
        _addressRepoMock = new Mock<IAddressRepository>();
        _uowMock.Setup(u => u.Addresses).Returns(_addressRepoMock.Object);
        _service = new AddressService(_uowMock.Object);
    }

    [Fact]
    public async Task GetUserAddressesAsync_ShouldReturnList()
    {
        var userId = "user1";
        var addresses = new List<UserAddress>
        {
            new() { UserId = userId, Name = "Home" },
            new() { UserId = userId, Name = "Work" }
        };

        _addressRepoMock.Setup(r => r.GetByUserIdAsync(userId)).ReturnsAsync(addresses);

        var result = await _service.GetUserAddressesAsync(userId);

        Assert.Equal(2, result.Count);
    }

    [Fact]
    public async Task CreateAsync_ShouldSetDefault_WhenFirstAddress()
    {
        var userId = "user1";
        var dto = new CreateAddressDto("Home", "Receiver", "12345678", "Street", "1", null, "Neighborhood", "City", "ST", null, "11999999999", false);

        _addressRepoMock.Setup(r => r.HasAnyAsync(userId)).ReturnsAsync(false);

        UserAddress? capturedAddress = null;
        _addressRepoMock.Setup(r => r.AddAsync(It.IsAny<UserAddress>()))
            .Callback<UserAddress>(a => capturedAddress = a);

        await _service.CreateAsync(userId, dto);

        Assert.NotNull(capturedAddress);
        Assert.True(capturedAddress!.IsDefault);
        _uowMock.Verify(u => u.CommitAsync(), Times.Once);
    }

    [Fact]
    public async Task UpdateAsync_ShouldUpdateProperties()
    {
        var userId = "user1";
        var addressId = Guid.NewGuid();
        var address = new UserAddress { Id = addressId, UserId = userId, Name = "Old" };

        _addressRepoMock.Setup(r => r.GetByIdAsync(addressId, userId)).ReturnsAsync(address);

        var dto = new CreateAddressDto("New", "Rec", "00000000", "St", "2", null, "Neigh", "City", "UF", null, "00000000000", true);

        await _service.UpdateAsync(addressId, userId, dto);

        Assert.Equal("New", address.Name);
        Assert.True(address.IsDefault);
        _uowMock.Verify(u => u.CommitAsync(), Times.Once);
    }

    [Fact]
    public async Task DeleteAsync_ShouldRemoveAddress_WhenFound()
    {
        var userId = "user1";
        var addressId = Guid.NewGuid();
        var address = new UserAddress { Id = addressId, UserId = userId };

        _addressRepoMock.Setup(r => r.GetByIdAsync(addressId, userId)).ReturnsAsync(address);

        await _service.DeleteAsync(addressId, userId);

        _addressRepoMock.Verify(r => r.DeleteAsync(address), Times.Once);
        _uowMock.Verify(u => u.CommitAsync(), Times.Once);
    }
}