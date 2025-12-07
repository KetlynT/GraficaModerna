using System.IdentityModel.Tokens.Jwt;
using System.Security.Claims;
using System.Security.Cryptography;
using System.Text;
using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using GraficaModerna.Domain.Constants;
using GraficaModerna.Domain.Entities;
using Microsoft.AspNetCore.Identity;
using Microsoft.Extensions.Configuration;
using Microsoft.IdentityModel.Tokens;


namespace GraficaModerna.Application.Services;

public class AuthService(
    UserManager<ApplicationUser> userManager,
    IConfiguration configuration,
    IPasswordHasher<ApplicationUser> passwordHasher) : IAuthService
{
    private readonly IConfiguration _configuration = configuration;
    private readonly IPasswordHasher<ApplicationUser> _passwordHasher = passwordHasher; 
    private readonly UserManager<ApplicationUser> _userManager = userManager;

    public async Task<AuthResponseDto> RegisterAsync(RegisterDto dto)
    {
        var user = new ApplicationUser
        {
            UserName = dto.Email,
            Email = dto.Email,
            FullName = dto.FullName,
            PhoneNumber = dto.PhoneNumber
        };

        var result = await _userManager.CreateAsync(user, dto.Password);

        if (!result.Succeeded)
        {
            var safeErrors = result.Errors
                .Where(e => e.Code.StartsWith("Password"))
                .Select(e => e.Description);

            if (safeErrors.Any())
                throw new Exception($"Senha fraca: {string.Join("; ", safeErrors)}");

            throw new Exception("Erro ao criar usu�rio.");
        }

        await _userManager.AddToRoleAsync(user, Roles.User);

        return await CreateTokenPairAsync(user);
    }

    public async Task<AuthResponseDto> LoginAsync(LoginDto dto)
    {
        var user = await _userManager.FindByEmailAsync(dto.Email);

        if (user == null || !await _userManager.CheckPasswordAsync(user, dto.Password))
            throw new Exception("Credenciais inv�lidas.");

        return await CreateTokenPairAsync(user);
    }



    public async Task<AuthResponseDto> RefreshTokenAsync(TokenModel tokenModel)
    {
        if (tokenModel is null) throw new Exception("Requisi��o inv�lida");

        var accessToken = tokenModel.AccessToken;
        var refreshToken = tokenModel.RefreshToken;


        if (string.IsNullOrEmpty(refreshToken)) throw new Exception("Refresh token inv�lido");

        var principal = GetPrincipalFromExpiredToken(accessToken) ??
                        throw new Exception("Token de acesso ou refresh token inv�lido");
        var username = principal.Identity!.Name!;

        var user = await _userManager.FindByNameAsync(username);

        if (user == null || user.RefreshToken == null || user.RefreshTokenExpiryTime <= DateTime.UtcNow)
            throw new Exception("Refresh token inv�lido ou expirado.");

        var verificationResult = _passwordHasher.VerifyHashedPassword(user, user.RefreshToken, refreshToken);

        if (verificationResult != PasswordVerificationResult.Success)
            throw new Exception("Refresh token inv�lido ou expirado.");

        return await CreateTokenPairAsync(user);
    }


    public async Task<UserProfileDto> GetProfileAsync(string userId)
    {
        var user = await _userManager.FindByIdAsync(userId) ?? throw new Exception("Usu�rio n�o encontrado.");
        return new UserProfileDto(user.FullName, user.Email!, user.PhoneNumber ?? "");
    }

    public async Task UpdateProfileAsync(string userId, UpdateProfileDto dto)
    {
        var user = await _userManager.FindByIdAsync(userId) ?? throw new Exception("Usu�rio n�o encontrado.");
        user.FullName = dto.FullName;
        user.PhoneNumber = dto.PhoneNumber;

        var result = await _userManager.UpdateAsync(user);
        if (!result.Succeeded) throw new Exception("Erro ao atualizar perfil.");
    }


    private async Task<AuthResponseDto> CreateTokenPairAsync(ApplicationUser user)
    {

        var accessToken = GenerateAccessToken(user);

        var refreshToken = GenerateRefreshToken();

        user.RefreshToken = _passwordHasher.HashPassword(user, refreshToken); 
        user.RefreshTokenExpiryTime = DateTime.UtcNow.AddDays(7);
        await _userManager.UpdateAsync(user);

        var roles = await _userManager.GetRolesAsync(user);
        var primaryRole = roles.Contains(Roles.Admin) ? Roles.Admin : roles.FirstOrDefault() ?? Roles.User;

        return new AuthResponseDto(new JwtSecurityTokenHandler().WriteToken(accessToken), refreshToken, user.Email!,
            primaryRole);
    }

    private JwtSecurityToken GenerateAccessToken(ApplicationUser user)
{
    var userRoles = _userManager.GetRolesAsync(user).Result;
    var primaryRole = userRoles.Contains(Roles.Admin) ? Roles.Admin : userRoles.FirstOrDefault() ?? Roles.User;

    var authClaims = new List<Claim>
    {
        new(JwtRegisteredClaimNames.Sub, user.Id), 
        new(JwtRegisteredClaimNames.Email, user.Email!),
        new(JwtRegisteredClaimNames.Jti, Guid.NewGuid().ToString()),
        new("role", primaryRole),                 
        
        new(JwtRegisteredClaimNames.UniqueName, user.UserName!) 
    };

    var keyString = Environment.GetEnvironmentVariable("JWT_SECRET_KEY") ?? _configuration["Jwt:Key"];
    if (string.IsNullOrEmpty(keyString) || keyString.Length < 32) throw new Exception("Erro config JWT");
    var authSigningKey = new SymmetricSecurityKey(Encoding.UTF8.GetBytes(keyString));

    return new JwtSecurityToken(
        _configuration["Jwt:Issuer"],
        _configuration["Jwt:Audience"],
        expires: DateTime.UtcNow.AddMinutes(15), 
        claims: authClaims,
        signingCredentials: new SigningCredentials(authSigningKey, SecurityAlgorithms.HmacSha256)
    );
}
    private static string GenerateRefreshToken()
    {
        var randomNumber = new byte[64];
        using var rng = RandomNumberGenerator.Create();
        rng.GetBytes(randomNumber);
        return Convert.ToBase64String(randomNumber);
    }

    private ClaimsPrincipal? GetPrincipalFromExpiredToken(string? token)
    {
        var keyString = Environment.GetEnvironmentVariable("JWT_SECRET_KEY") ?? _configuration["Jwt:Key"];
        var tokenValidationParameters = new TokenValidationParameters
        {
            ValidateAudience = false, 
            ValidateIssuer = false,
            ValidateIssuerSigningKey = true,
            IssuerSigningKey = new SymmetricSecurityKey(Encoding.UTF8.GetBytes(keyString!)),
            ValidateLifetime = false 
        };

        var tokenHandler = new JwtSecurityTokenHandler();
        try
        {
            var principal = tokenHandler.ValidateToken(token, tokenValidationParameters, out var securityToken);

            if (securityToken is not JwtSecurityToken jwtSecurityToken ||
                !jwtSecurityToken.Header.Alg.Equals(SecurityAlgorithms.HmacSha256,
                    StringComparison.InvariantCultureIgnoreCase))
                throw new SecurityTokenException("Token inv�lido");

            return principal;
        }
        catch
        {
            return null;
        }
    }
}
