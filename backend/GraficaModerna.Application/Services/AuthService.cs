using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using GraficaModerna.Domain.Constants;
using GraficaModerna.Domain.Entities;
using Microsoft.AspNetCore.Identity;
using Microsoft.Extensions.Configuration;
using Microsoft.IdentityModel.Tokens;
using System.IdentityModel.Tokens.Jwt;
using System.Security.Claims;
using System.Text;

namespace GraficaModerna.Application.Services;

public class AuthService : IAuthService
{
    private readonly UserManager<ApplicationUser> _userManager;
    private readonly IConfiguration _configuration;

    public AuthService(UserManager<ApplicationUser> userManager, IConfiguration configuration)
    {
        _userManager = userManager;
        _configuration = configuration;
    }

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
            // SEGURANÇA: Prevenção de User Enumeration (CWE-204)
            // Não retornamos "Email já existe" diretamente para evitar varredura de usuários.

            // Filtramos apenas erros de validação de senha (ex: "Senha precisa de um dígito"), 
            // que são úteis para o usuário legítimo corrigir seu input.
            var safeErrors = result.Errors
                .Where(e => e.Code.StartsWith("Password"))
                .Select(e => e.Description);

            if (safeErrors.Any())
            {
                throw new Exception($"A senha não atende aos requisitos: {string.Join("; ", safeErrors)}");
            }

            // Para outros erros (como duplicidade de email ou erro de banco),
            // retornamos uma mensagem genérica segura.
            // Idealmente, o erro original 'result.Errors' deve ser logado (ILogger) aqui.
            throw new Exception("Não foi possível concluir o cadastro. Verifique os dados informados e tente novamente.");
        }

        await _userManager.AddToRoleAsync(user, Roles.User);

        return await GenerateToken(user);
    }

    public async Task<AuthResponseDto> LoginAsync(LoginDto dto)
    {
        var user = await _userManager.FindByEmailAsync(dto.Email);

        // SEGURANÇA: Mensagem genérica para Login inválido
        if (user == null || !await _userManager.CheckPasswordAsync(user, dto.Password))
            throw new Exception("Credenciais inválidas.");

        return await GenerateToken(user);
    }

    public async Task<UserProfileDto> GetProfileAsync(string userId)
    {
        var user = await _userManager.FindByIdAsync(userId);
        if (user == null) throw new Exception("Usuário não encontrado.");
        return new UserProfileDto(user.FullName, user.Email!, user.PhoneNumber);
    }

    public async Task UpdateProfileAsync(string userId, UpdateProfileDto dto)
    {
        var user = await _userManager.FindByIdAsync(userId);
        if (user == null) throw new Exception("Usuário não encontrado.");

        user.FullName = dto.FullName;
        user.PhoneNumber = dto.PhoneNumber;

        var result = await _userManager.UpdateAsync(user);
        if (!result.Succeeded) throw new Exception("Erro ao atualizar perfil.");
    }

    private async Task<AuthResponseDto> GenerateToken(ApplicationUser user)
    {
        var roles = await _userManager.GetRolesAsync(user);
        var primaryRole = roles.Contains(Roles.Admin) ? Roles.Admin : (roles.FirstOrDefault() ?? Roles.User);

        var claims = new List<Claim>
        {
            new Claim(ClaimTypes.NameIdentifier, user.Id),
            new Claim(ClaimTypes.Email, user.Email!),
            new Claim(ClaimTypes.Name, user.FullName),
            new Claim(ClaimTypes.Role, primaryRole)
        };

        // CORREÇÃO: Removemos a chave hardcoded de fallback.
        // A consistência da chave deve ser garantida na inicialização (Program.cs).
        var keyString = Environment.GetEnvironmentVariable("JWT_SECRET_KEY") ?? _configuration["Jwt:Key"];

        if (string.IsNullOrEmpty(keyString) || keyString.Length < 32)
            throw new Exception("Erro interno de configuração (Chave JWT ausente).");

        var key = new SymmetricSecurityKey(Encoding.ASCII.GetBytes(keyString));
        var creds = new SigningCredentials(key, SecurityAlgorithms.HmacSha256);

        var tokenDescriptor = new SecurityTokenDescriptor
        {
            Subject = new ClaimsIdentity(claims),
            Expires = DateTime.UtcNow.AddMinutes(30),
            SigningCredentials = creds,
            Issuer = _configuration["Jwt:Issuer"],
            Audience = _configuration["Jwt:Audience"]
        };

        var tokenHandler = new JwtSecurityTokenHandler();
        var token = tokenHandler.CreateToken(tokenDescriptor);

        return new AuthResponseDto(tokenHandler.WriteToken(token), user.Email!, primaryRole);
    }
}