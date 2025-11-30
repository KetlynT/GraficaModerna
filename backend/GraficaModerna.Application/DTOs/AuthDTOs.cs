using System.ComponentModel.DataAnnotations;

namespace GraficaModerna.Application.DTOs;

public record LoginDto([Required, EmailAddress] string Email, [Required] string Password);

public record RegisterDto([Required] string FullName, [Required, EmailAddress] string Email, [Required] string Password);

public record AuthResponseDto(string Token, string Email, string Role);