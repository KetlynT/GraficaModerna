using System.ComponentModel.DataAnnotations;

namespace GraficaModerna.Application.DTOs;

public record UserProfileDto(
    string FullName,
    string Email,
    string? PhoneNumber
);

public record UpdateProfileDto(
    [Required] string FullName,
    string? PhoneNumber
);