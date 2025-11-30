using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using Microsoft.AspNetCore.Mvc;

namespace GraficaModerna.API.Controllers;

[Route("api/[controller]")]
[ApiController]
public class AuthController : ControllerBase
{
    private readonly IAuthService _authService;

    public AuthController(IAuthService authService)
    {
        _authService = authService;
    }

    [HttpPost("register")]
    public async Task<ActionResult<AuthResponseDto>> Register(RegisterDto dto)
    {
        return Ok(await _authService.RegisterAsync(dto));
    }

    [HttpPost("login")]
    public async Task<ActionResult<AuthResponseDto>> Login(LoginDto dto)
    {
        return Ok(await _authService.LoginAsync(dto));
    }
}