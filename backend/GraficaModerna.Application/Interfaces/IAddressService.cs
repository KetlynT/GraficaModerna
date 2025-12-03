using GraficaModerna.Application.DTOs;

namespace GraficaModerna.Application.Interfaces;

public interface IAddressService
{
    Task<List<AddressDto>> GetUserAddressesAsync(string userId);
    Task<AddressDto> GetByIdAsync(Guid id, string userId);
    Task<AddressDto> CreateAsync(string userId, CreateAddressDto dto);
    Task UpdateAsync(Guid id, string userId, CreateAddressDto dto);
    Task DeleteAsync(Guid id, string userId);
}