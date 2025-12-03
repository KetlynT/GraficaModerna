using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using GraficaModerna.Domain.Entities;
using GraficaModerna.Infrastructure.Context;
using Microsoft.EntityFrameworkCore;

namespace GraficaModerna.Infrastructure.Services;

public class AddressService : IAddressService
{
    private readonly AppDbContext _context;

    public AddressService(AppDbContext context)
    {
        _context = context;
    }

    public async Task<List<AddressDto>> GetUserAddressesAsync(string userId)
    {
        var addresses = await _context.UserAddresses
            .Where(a => a.UserId == userId)
            .OrderByDescending(a => a.IsDefault) // Padrão primeiro
            .ToListAsync();

        return addresses.Select(MapToDto).ToList();
    }

    public async Task<AddressDto> GetByIdAsync(Guid id, string userId)
    {
        var address = await _context.UserAddresses
            .FirstOrDefaultAsync(a => a.Id == id && a.UserId == userId);

        if (address == null) throw new KeyNotFoundException("Endereço não encontrado.");

        return MapToDto(address);
    }

    public async Task<AddressDto> CreateAsync(string userId, CreateAddressDto dto)
    {
        // Se for o primeiro endereço ou marcado como padrão, remove o padrão dos outros
        if (dto.IsDefault || !await _context.UserAddresses.AnyAsync(a => a.UserId == userId))
        {
            await UnsetDefaultAddress(userId);
        }

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
            IsDefault = dto.IsDefault
        };

        _context.UserAddresses.Add(address);
        await _context.SaveChangesAsync();

        return MapToDto(address);
    }

    public async Task UpdateAsync(Guid id, string userId, CreateAddressDto dto)
    {
        var address = await _context.UserAddresses.FirstOrDefaultAsync(a => a.Id == id && a.UserId == userId);
        if (address == null) throw new KeyNotFoundException("Endereço não encontrado.");

        if (dto.IsDefault)
        {
            await UnsetDefaultAddress(userId);
        }

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

        await _context.SaveChangesAsync();
    }

    public async Task DeleteAsync(Guid id, string userId)
    {
        var address = await _context.UserAddresses.FirstOrDefaultAsync(a => a.Id == id && a.UserId == userId);
        if (address != null)
        {
            _context.UserAddresses.Remove(address);
            await _context.SaveChangesAsync();
        }
    }

    private async Task UnsetDefaultAddress(string userId)
    {
        var defaults = await _context.UserAddresses.Where(a => a.UserId == userId && a.IsDefault).ToListAsync();
        foreach (var d in defaults) d.IsDefault = false;
    }

    private static AddressDto MapToDto(UserAddress a) => new(
        a.Id, a.Name, a.ReceiverName, a.ZipCode, a.Street, a.Number, a.Complement,
        a.Neighborhood, a.City, a.State, a.Reference, a.PhoneNumber, a.IsDefault
    );
}