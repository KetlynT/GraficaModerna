using System.ComponentModel.DataAnnotations;

namespace GraficaModerna.Application.DTOs;

public record RegisterDto(
    [Required] string FullName,
    [Required][EmailAddress] string Email,
    [Required] string Password,
    [Required] string CpfCnpj,
    string? PhoneNumber
);

public record LoginDto(
    [Required][EmailAddress] string Email,
    [Required] string Password,
    bool IsAdminLogin = false
);
public record ForgotPasswordDto([Required][EmailAddress] string Email);

public record ResetPasswordDto(
    [Required][EmailAddress] string Email,
    [Required] string Token,
    [Required][MinLength(6)] string NewPassword
);

public record ConfirmEmailDto(
    [Required] string UserId,
    [Required] string Token
);

public record AuthResponseDto(
    string AccessToken,
    string RefreshToken,
    string Email,
    string Role,
    string CpfCnpj
);

public record TokenModel(string? AccessToken, string? RefreshToken);