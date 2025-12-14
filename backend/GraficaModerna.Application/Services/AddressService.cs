using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using GraficaModerna.Domain.Entities;
using GraficaModerna.Domain.Interfaces;

namespace GraficaModerna.Application.Services;

public class AddressService(IUnitOfWork uow) : IAddressService
{
    private readonly IUnitOfWork _uow = uow;

    public async Task<List<AddressDto>> GetUserAddressesAsync(string userId)
    {
        var addresses = await _uow.Addresses.GetByUserIdAsync(userId);
        return [.. addresses.Select(MapToDto)];
    }

    public async Task<AddressDto> GetByIdAsync(Guid id, string userId)
    {
        var address = await _uow.Addresses.GetByIdAsync(id, userId) ??
                      throw new KeyNotFoundException("Endereço não encontrado.");
        return MapToDto(address);
    }

    public async Task<AddressDto> CreateAsync(string userId, CreateAddressDto dto)
    {
        var isFirst = !await _uow.Addresses.HasAnyAsync(userId);
        if (dto.IsDefault || isFirst) await UnsetDefaultAddress(userId);

        var address = new UserAddress
        {
            UserId = userId,
            Name = dto.Name,
            ReceiverName = dto.ReceiverName,
            ZipCode = dto.ZipCode,
            Street = dto.Street,
            Number = dto.Number,
            Complement = dto.Complement ?? "",
            Neighborhood = dto.Neighborhood,
            City = dto.City,
            State = dto.State,
            Reference = dto.Reference ?? "",
            PhoneNumber = dto.PhoneNumber,
            IsDefault = dto.IsDefault || isFirst
        };

        await _uow.Addresses.AddAsync(address);
        await _uow.CommitAsync();

        return MapToDto(address);
    }

    public async Task UpdateAsync(Guid id, string userId, CreateAddressDto dto)
    {
        var address = await _uow.Addresses.GetByIdAsync(id, userId) ??
                      throw new KeyNotFoundException("Endereço não encontrado.");
        if (dto.IsDefault) await UnsetDefaultAddress(userId);

        address.Name = dto.Name;
        address.ReceiverName = dto.ReceiverName;
        address.ZipCode = dto.ZipCode;
        address.Street = dto.Street;
        address.Number = dto.Number;
        address.Complement = dto.Complement ?? "";
        address.Neighborhood = dto.Neighborhood;
        address.City = dto.City;
        address.State = dto.State;
        address.Reference = dto.Reference ?? "";
        address.PhoneNumber = dto.PhoneNumber;
        address.IsDefault = dto.IsDefault;

        await _uow.CommitAsync();
    }

    public async Task DeleteAsync(Guid id, string userId)
    {
        var address = await _uow.Addresses.GetByIdAsync(id, userId);
        if (address != null)
        {
            await _uow.Addresses.DeleteAsync(address);
            await _uow.CommitAsync();
        }
    }

    private async Task UnsetDefaultAddress(string userId)
    {
        var addresses = await _uow.Addresses.GetByUserIdAsync(userId);
        foreach (var a in addresses) a.IsDefault = false;
    }

    private static AddressDto MapToDto(UserAddress a)
    {
        return new AddressDto(
            a.Id, a.Name, a.ReceiverName, a.ZipCode, a.Street, a.Number, a.Complement,
            a.Neighborhood, a.City, a.State, a.Reference, a.PhoneNumber, a.IsDefault
        );
    }
}