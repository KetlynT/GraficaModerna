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
using SixLabors.ImageSharp;
using SixLabors.ImageSharp.Processing;

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

    private const long MaxFileSize = 50 * 1024 * 1024;
    private const int MaxImageDimension = 2048;

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

    #region Upload

    [HttpPost("upload")]
    public async Task<IActionResult> Upload(IFormFile file)
    {
        if (file == null || file.Length == 0)
            return BadRequest("Nenhum ficheiro enviado.");

        if (file.Length > MaxFileSize)
            return BadRequest($"O ficheiro excede o tamanho máximo permitido de {MaxFileSize / 1024 / 1024}MB.");

        var folderPath = Path.Combine(Directory.GetCurrentDirectory(), "wwwroot", "uploads");
        if (!Directory.Exists(folderPath))
            Directory.CreateDirectory(folderPath);

        try
        {
            using var memoryStream = new MemoryStream();
            await file.CopyToAsync(memoryStream);
            memoryStream.Position = 0;

            var detectedExtension = await DetectExtensionFromSignatureAsync(memoryStream);
            if (string.IsNullOrEmpty(detectedExtension))
            {
                return BadRequest("O arquivo parece estar corrompido, falsificado ou tem um formato não permitido.");
            }

            var fileName = $"{Guid.NewGuid()}{detectedExtension}";
            var filePath = Path.Combine(folderPath, fileName);

            memoryStream.Position = 0;

            if (IsVideo(detectedExtension))
            {
                using var stream = new FileStream(filePath, FileMode.Create);
                await memoryStream.CopyToAsync(stream);
            }
            else
            {
                using var image = await Image.LoadAsync(memoryStream);

                if (image.Width > MaxImageDimension || image.Height > MaxImageDimension)
                {
                    image.Mutate(x => x.Resize(new ResizeOptions
                    {
                        Size = new Size(MaxImageDimension, MaxImageDimension),
                        Mode = ResizeMode.Max
                    }));
                }

                await image.SaveAsync(filePath);
            }

            var fileUrl = $"{Request.Scheme}://{Request.Host}/uploads/{fileName}";
            return Ok(new { url = fileUrl });
        }
        catch (UnknownImageFormatException)
        {
            return BadRequest("O arquivo não é uma imagem válida ou está corrompido.");
        }
        catch (ImageFormatException)
        {
            return BadRequest("Erro ao decodificar a imagem.");
        }
        catch (Exception)
        {
            return StatusCode(500, "Erro interno ao processar o ficheiro.");
        }
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

    [HttpGet("products")]
    public async Task<ActionResult<PagedResultDto<ProductResponseDto>>> GetProducts(
        [FromQuery] string? search,
        [FromQuery] string? sort,
        [FromQuery] string? order,
        [FromQuery] int page = 1,
        [FromQuery] int pageSize = 8)
    {
        if (page < 1) page = 1;
        if (pageSize > 50) pageSize = 50;

        var result = await _productService.GetCatalogAsync(search, sort, order, page, pageSize);
        return Ok(result);
    }

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
    public async Task<ActionResult<EmailTemplateDto>> GetEmailTemplateById(Guid id)
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
    public async Task<IActionResult> UpdateEmailTemplate(Guid id, [FromBody] UpdateEmailTemplateDto dto)
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

    private static bool IsVideo(string ext) => ext is ".mp4" or ".webm" or ".mov";

    private static async Task<string?> DetectExtensionFromSignatureAsync(MemoryStream stream)
    {
        stream.Position = 0;
        var header = new byte[16];
        var bytesRead = await stream.ReadAsync(header);

        if (bytesRead < 4) return null;

        if (header[0] == 0xFF && header[1] == 0xD8 && header[2] == 0xFF) return ".jpg";
        if (header[0] == 0x89 && header[1] == 0x50 && header[2] == 0x4E && header[3] == 0x47) return ".png";
        if (bytesRead >= 12 &&
            header[0] == 0x52 && header[1] == 0x49 && header[2] == 0x46 && header[3] == 0x46 &&
            header[8] == 0x57 && header[9] == 0x45 && header[10] == 0x42 && header[11] == 0x50) return ".webp";
        if (bytesRead >= 8 &&
            header[4] == 0x66 && header[5] == 0x74 && header[6] == 0x79 && header[7] == 0x70) return ".mp4";
        if (header[0] == 0x1A && header[1] == 0x45 && header[2] == 0xDF && header[3] == 0xA3) return ".webm";

        return null;
    }
}