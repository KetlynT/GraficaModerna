using System.IdentityModel.Tokens.Jwt;
using System.Security.Claims;
using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using GraficaModerna.Domain.Constants;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.RateLimiting;

namespace GraficaModerna.API.Controllers;

[Route("api/[controller]")]
[ApiController]
public class AuthController(
    IAuthService authService,
    ITokenBlacklistService blacklistService,
    ILogger<AuthController> logger,
    IContentService contentService) : ControllerBase
{
    private readonly IAuthService _authService = authService;
    private readonly ITokenBlacklistService _blacklistService = blacklistService;
    private readonly ILogger<AuthController> _logger = logger;
    private readonly IContentService _contentService = contentService;

    [EnableRateLimiting("AuthPolicy")]
    [HttpPost("register")]
    public async Task<ActionResult> Register(RegisterDto dto)
    {
        var settings = await _contentService.GetSettingsAsync();
        if (settings.TryGetValue("purchase_enabled", out var enabled) && enabled == "false")
        {
            return StatusCode(403, new { message = "O cadastro de novos usuários está temporariamente desativado." });
        }

        var result = await _authService.RegisterAsync(dto);
        SetTokenCookies(result.AccessToken, result.RefreshToken);

        return Ok(new
        {
            result.Email,
            result.Role,
            message = "Cadastro realizado com sucesso."
        });
    }

    [EnableRateLimiting("AuthPolicy")]
    [HttpPost("login")]
    public async Task<ActionResult> Login(LoginDto dto)
    {
        var userLoginDto = dto with { IsAdminLogin = false };
        var result = await _authService.LoginAsync(userLoginDto);

        var settings = await _contentService.GetSettingsAsync();
        if (settings.TryGetValue("purchase_enabled", out var enabled) && enabled == "false")
        {
            if (result.Role != Roles.Admin)
            {
                await _blacklistService.BlacklistTokenAsync(result.AccessToken, DateTime.UtcNow.AddDays(1));
                return StatusCode(403, new { message = "Loja em modo orçamento. Login restrito a administradores." });
            }
        }

        SetTokenCookies(result.AccessToken, result.RefreshToken);

        return Ok(new
        {
            result.Email,
            result.Role,
            message = "Login realizado com sucesso."
        });
    }

    [EnableRateLimiting("AuthPolicy")]
    [HttpPost("admin/login")]
    public async Task<ActionResult> AdminLogin(LoginDto dto)
    {
        var adminLoginDto = dto with { IsAdminLogin = true };
        var result = await _authService.LoginAsync(adminLoginDto);

        SetTokenCookies(result.AccessToken, result.RefreshToken);

        return Ok(new
        {
            result.Email,
            result.Role,
            message = "Login administrativo realizado com sucesso."
        });
    }

    [HttpPost("logout")]
    [Authorize]
    public async Task<IActionResult> Logout()
    {
        // Tenta obter token do cookie primeiro, depois do header
        string? token = null;
        if (Request.Cookies.TryGetValue("jwt", out var cookieToken))
            token = cookieToken;
        else if (Request.Headers.TryGetValue("Authorization", out var authHeader))
        {
            var header = authHeader.ToString();
            if (header.StartsWith("Bearer ", StringComparison.OrdinalIgnoreCase))
                token = header["Bearer ".Length..].Trim();
        }

        if (!string.IsNullOrEmpty(token))
            try
            {
                var handler = new JwtSecurityTokenHandler();
                if (handler.CanReadToken(token))
                {
                    var jwtToken = handler.ReadJwtToken(token);
                    await _blacklistService.BlacklistTokenAsync(token, jwtToken.ValidTo);
                }
            }
            catch (Exception ex)
            {
                _logger.LogWarning("Erro ao processar blacklist no logout: {Message}", ex.Message);
            }

        // Remove cookies
        Response.Cookies.Delete("jwt");
        Response.Cookies.Delete("refreshToken");

        return Ok(new { message = "Deslogado com sucesso" });
    }

    [HttpGet("check-auth")]
    [Authorize]
    public IActionResult CheckAuth()
    {
        var role = User.FindFirstValue(ClaimTypes.Role);
        return Ok(new { isAuthenticated = true, role });
    }

    [HttpGet("profile")]
    [Authorize(Roles = Roles.User + "," + Roles.Admin)]
    public async Task<ActionResult<UserProfileDto>> GetProfile()
    {
        var userId = User.FindFirstValue(ClaimTypes.NameIdentifier);
        if (string.IsNullOrEmpty(userId)) return Unauthorized();

        var profile = await _authService.GetProfileAsync(userId);
        return Ok(profile);
    }

    [HttpPut("profile")]
    [Authorize(Roles = Roles.User + "," + Roles.Admin)]
    public async Task<IActionResult> UpdateProfile(UpdateProfileDto dto)
    {
        var settings = await _contentService.GetSettingsAsync();
        if (settings.TryGetValue("purchase_enabled", out var enabled) && enabled == "false")
        {
            var role = User.FindFirstValue(ClaimTypes.Role);
            if (role != Roles.Admin)
            {
                return StatusCode(403, new { message = "Edição de perfil desativada temporariamente." });
            }
        }

        var userId = User.FindFirstValue(ClaimTypes.NameIdentifier);
        if (string.IsNullOrEmpty(userId)) return Unauthorized();

        await _authService.UpdateProfileAsync(userId, dto);
        return NoContent();
    }

    [HttpPost("refresh-token")]
    [AllowAnonymous]
    public async Task<IActionResult> RefreshToken()
    {
        // Ler tokens dos cookies
        var accessToken = Request.Cookies["jwt"];
        var refreshToken = Request.Cookies["refreshToken"];

        if (string.IsNullOrEmpty(accessToken) || string.IsNullOrEmpty(refreshToken))
            return BadRequest("Tokens não encontrados nos cookies.");

        var tokenModel = new TokenModel(accessToken, refreshToken);

        try
        {
            var result = await _authService.RefreshTokenAsync(tokenModel);
            SetTokenCookies(result.AccessToken, result.RefreshToken);

            return Ok(new { result.Email, result.Role });
        }
        catch (Exception ex)
        {
            return BadRequest(ex.Message);
        }
    }

    [HttpPost("confirm-email")]
    [AllowAnonymous]
    public async Task<IActionResult> ConfirmEmail([FromBody] ConfirmEmailDto dto)
    {
        await _authService.ConfirmEmailAsync(dto);
        return Ok(new { Message = "Email confirmado com sucesso!" });
    }

    [HttpPost("forgot-password")]
    [AllowAnonymous]
    public async Task<IActionResult> ForgotPassword([FromBody] ForgotPasswordDto dto)
    {
        await _authService.ForgotPasswordAsync(dto);
        return Ok(new { Message = "Se o e-mail estiver cadastrado, você receberá um link de recuperação." });
    }

    [HttpPost("reset-password")]
    [AllowAnonymous]
    public async Task<IActionResult> ResetPassword([FromBody] ResetPasswordDto dto)
    {
        await _authService.ResetPasswordAsync(dto);
        return Ok(new { Message = "Senha redefinida com sucesso. Faça login com a nova senha." });
    }

    private void SetTokenCookies(string accessToken, string refreshToken)
    {
        var cookieOptions = new CookieOptions
        {
            HttpOnly = true,
            Secure = true, // Requer HTTPS
            SameSite = SameSiteMode.Strict,
            Expires = DateTime.UtcNow.AddMinutes(15) // Tempo do Access Token
        };

        var refreshCookieOptions = new CookieOptions
        {
            HttpOnly = true,
            Secure = true,
            SameSite = SameSiteMode.Strict,
            Expires = DateTime.UtcNow.AddDays(7) // Tempo do Refresh Token
        };

        Response.Cookies.Append("jwt", accessToken, cookieOptions);
        Response.Cookies.Append("refreshToken", refreshToken, refreshCookieOptions);
    }
}