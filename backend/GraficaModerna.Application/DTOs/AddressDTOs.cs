using System.ComponentModel.DataAnnotations;

namespace GraficaModerna.Application.DTOs;

public record AddressDto(
    Guid Id,
    string Name,
    string ReceiverName,
    string ZipCode,
    string Street,
    string Number,
    string Complement,
    string Neighborhood,
    string City,
    string State,
    string Reference,
    string PhoneNumber,
    bool IsDefault
);

public record CreateAddressDto(
    [Required] string Name,
    [Required] string ReceiverName,
    [Required] string ZipCode,
    [Required] string Street,
    [Required] string Number,
    string? Complement,
    [Required] string Neighborhood,
    [Required] string City,
    [Required] string State,
    string? Reference,
    [Required] string PhoneNumber,
    bool IsDefault
);
