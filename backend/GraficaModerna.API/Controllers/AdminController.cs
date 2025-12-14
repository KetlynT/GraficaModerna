using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using GraficaModerna.Application.Services;
using GraficaModerna.Domain.Constants;
using GraficaModerna.Domain.Entities;
using GraficaModerna.Domain.Interfaces;
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
    IUnitOfWork uow) : ControllerBase
{
    private readonly IOrderService _orderService = orderService;
    private readonly IProductService _productService = productService;
    private readonly IUnitOfWork _uow = uow;

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

    [HttpGet("orders")]
    public async Task<IActionResult> GetOrders([FromQuery] int page = 1, [FromQuery] int pageSize = 10)
    {
        var orders = await _orderService.GetAllOrdersAsync(page, pageSize);
        return Ok(orders);
    }

    [HttpPatch("orders/{id}/status")]
    public async Task<IActionResult> UpdateStatus(Guid id, [FromBody] UpdateOrderStatusDto dto)
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
    public async Task<ActionResult<EmailTemplateDto>> GetEmailTemplateById(int id)
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
    public async Task<IActionResult> UpdateEmailTemplate(int id, [FromBody] UpdateEmailTemplateDto dto)
    {
        if (!ModelState.IsValid)
            return BadRequest(ModelState);

        var template = await _uow.EmailTemplates.GetByIdAsync(id);

        if (template == null)
            return NotFound(new { message = "Template não encontrado." });

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
}