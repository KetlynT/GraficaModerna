using System.Security.Claims;
using Ganss.Xss;
using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using GraficaModerna.Domain.Constants;
using GraficaModerna.Domain.Entities;
using GraficaModerna.Domain.Interfaces;
using GraficaModerna.Domain.Models;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.RateLimiting;

namespace GraficaModerna.API.Controllers;

[Route("api/[controller]")]
[ApiController]
[Authorize(Roles = Roles.Admin)]
[EnableRateLimiting("AdminPolicy")]
public class AdminController(
    IOrderService orderService,
    IProductService productService,
    IUnitOfWork uow,
    IAuthService authService,
    IDashboardService dashboardService,
    ICouponService couponService,
    IContentService contentService,
    IHtmlSanitizer sanitizer) : ControllerBase
{
    private readonly IOrderService _orderService = orderService;
    private readonly IProductService _productService = productService;
    private readonly IUnitOfWork _uow = uow;
    private readonly IAuthService _authService = authService;
    private readonly IDashboardService _dashboardService = dashboardService;
    private readonly ICouponService _couponService = couponService;
    private readonly IContentService _contentService = contentService;
    private readonly IHtmlSanitizer _sanitizer = sanitizer;

    #region Auth & Dashboard

    [EnableRateLimiting("AuthPolicy")]
    [HttpPost("auth/login")]
    [AllowAnonymous]
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

    [HttpGet("dashboard/stats")]
    public async Task<IActionResult> GetDashboardStats()
    {
        var stats = await _dashboardService.GetStatsAsync();
        return Ok(stats);
    }

    #endregion

    #region Orders

    [HttpGet("orders")]
    public async Task<IActionResult> GetOrders([FromQuery] int page = 1, [FromQuery] int pageSize = 10)
    {
        var orders = await _orderService.GetAllOrdersAsync(page, pageSize);
        return Ok(orders);
    }

    [HttpPatch("orders/{id}/status")]
    public async Task<IActionResult> UpdateOrderStatus(Guid id, [FromBody] UpdateOrderStatusDto dto)
    {
        try
        {
            await _orderService.UpdateAdminOrderAsync(id, dto);
            return Ok();
        }
        catch (Exception ex)
        {
            return BadRequest(new { message = ex.Message });
        }
    }

    #endregion

    #region Products

    [HttpPost("products")]
    public async Task<ActionResult<ProductResponseDto>> CreateProduct([FromBody] CreateProductDto dto)
    {
        var result = await _productService.CreateAsync(dto);
        return Created($"/api/products/{result.Id}", result);
    }

    [HttpPut("products/{id}")]
    public async Task<ActionResult> UpdateProduct(Guid id, [FromBody] UpdateProductDto dto)
    {
        try
        {
            await _productService.UpdateAsync(id, dto);
            return NoContent();
        }
        catch (KeyNotFoundException)
        {
            return NotFound(new { message = "Produto não encontrado." });
        }
    }

    [HttpDelete("products/{id}")]
    public async Task<ActionResult> DeleteProduct(Guid id)
    {
        try
        {
            await _productService.DeleteAsync(id);
            return NoContent();
        }
        catch (KeyNotFoundException)
        {
            return NotFound(new { message = "Produto não encontrado." });
        }
    }

    #endregion

    #region Coupons

    [HttpGet("coupons")]
    public async Task<ActionResult<List<CouponResponseDto>>> GetAllCoupons()
    {
        return Ok(await _couponService.GetAllAsync());
    }

    [HttpPost("coupons")]
    public async Task<ActionResult<CouponResponseDto>> CreateCoupon(CreateCouponDto dto)
    {
        try
        {
            return Ok(await _couponService.CreateAsync(dto));
        }
        catch (Exception ex)
        {
            return BadRequest(new { message = ex.Message });
        }
    }

    [HttpDelete("coupons/{id}")]
    public async Task<IActionResult> DeleteCoupon(Guid id)
    {
        await _couponService.DeleteAsync(id);
        return NoContent();
    }

    #endregion

    #region Content & Settings

    [HttpPost("content/pages")]
    public async Task<IActionResult> CreatePage([FromBody] CreateContentDto dto)
    {
        dto.Content = _sanitizer.Sanitize(dto.Content);
        var result = await _contentService.CreateAsync(dto);
        return Ok(result);
    }

    [HttpPut("content/pages/{slug}")]
    public async Task<IActionResult> UpdatePage(string slug, [FromBody] UpdateContentDto dto)
    {
        dto.Content = _sanitizer.Sanitize(dto.Content);
        await _contentService.UpdateAsync(slug, dto);
        return Ok();
    }

    [HttpPost("content/settings")]
    public async Task<IActionResult> UpdateSettings([FromBody] Dictionary<string, string> settings)
    {
        await _contentService.UpdateSettingsAsync(settings);
        return Ok();
    }

    [HttpGet("email-templates")]
    public async Task<ActionResult<IEnumerable<EmailTemplateDto>>> GetEmailTemplates()
    {
        var templates = await _uow.EmailTemplates.GetAllAsync();

        var dtos = templates.Select(t => new EmailTemplateDto
        {
            Id = t.Id,
            Key = t.Key,
            Subject = t.Subject,
            BodyContent = t.BodyContent,
            Description = t.Description,
            UpdatedAt = t.UpdatedAt
        });
        return Ok(dtos);
    }

    [HttpGet("email-templates/{id}")]
    public async Task<ActionResult<EmailTemplateDto>> GetEmailTemplateById(Guid id) // Alterado int para Guid
    {
        var template = await _uow.EmailTemplates.GetByIdAsync(id);

        if (template == null)
            return NotFound(new { message = "Template não encontrado." });

        return Ok(new EmailTemplateDto
        {
            Id = template.Id,
            Key = template.Key,
            Subject = template.Subject,
            BodyContent = template.BodyContent,
            Description = template.Description,
            UpdatedAt = template.UpdatedAt
        });
    }

    [HttpPut("email-templates/{id}")]
    public async Task<IActionResult> UpdateEmailTemplate(Guid id, [FromBody] UpdateEmailTemplateDto dto) // Alterado int para Guid
    {
        if (!ModelState.IsValid) return BadRequest(ModelState);

        var template = await _uow.EmailTemplates.GetByIdAsync(id);

        if (template == null) return NotFound(new { message = "Template não encontrado." });

        template.Subject = dto.Subject;
        template.BodyContent = dto.BodyContent;
        template.UpdatedAt = DateTime.UtcNow;

        try
        {
            _uow.EmailTemplates.Update(template);
            await _uow.CommitAsync();
            return NoContent();
        }
        catch (Exception ex)
        {
            return StatusCode(500, new { message = "Erro ao atualizar template.", details = ex.Message });
        }
    }

    #endregion

    private void SetTokenCookies(string accessToken, string refreshToken)
    {
        var cookieOptions = new CookieOptions
        {
            HttpOnly = true,
            Secure = true,
            SameSite = SameSiteMode.Strict,
            Expires = DateTime.UtcNow.AddMinutes(15)
        };

        var refreshCookieOptions = new CookieOptions
        {
            HttpOnly = true,
            Secure = true,
            SameSite = SameSiteMode.Strict,
            Expires = DateTime.UtcNow.AddDays(7)
        };

        Response.Cookies.Append("accessToken", accessToken, cookieOptions);
        Response.Cookies.Append("refreshToken", refreshToken, refreshCookieOptions);
    }
}