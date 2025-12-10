using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using GraficaModerna.Domain.Constants;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace GraficaModerna.API.Controllers;

[Route("api/[controller]")]
[ApiController]
public class CouponsController(ICouponService service, IContentService contentService) : ControllerBase
{
    private readonly ICouponService _service = service;
    private readonly IContentService _contentService = contentService;

    [HttpGet("validate/{code}")]
    public async Task<IActionResult> Validate(string code)
    {
        var settings = await _contentService.GetSettingsAsync();
        if (settings.TryGetValue("purchase_enabled", out var enabled) && enabled == "false")
            return BadRequest("Uso de cupons indisponível temporariamente.");

        var coupon = await _service.GetValidCouponAsync(code);
        if (coupon == null) return NotFound("Inválido.");
        return Ok(new { coupon.Code, coupon.DiscountPercentage });
    }

    [HttpGet]
    [Authorize(Roles = Roles.Admin)]
    public async Task<ActionResult<List<CouponResponseDto>>> GetAll()
    {
        return Ok(await _service.GetAllAsync());
    }

    [HttpPost]
    [Authorize(Roles = Roles.Admin)]
    public async Task<ActionResult<CouponResponseDto>> Create(CreateCouponDto dto)
    {
        try
        {
            return Ok(await _service.CreateAsync(dto));
        }
        catch (Exception ex)
        {
            return BadRequest(ex.Message);
        }
    }

    [HttpDelete("{id}")]
    [Authorize(Roles = Roles.Admin)]
    public async Task<IActionResult> Delete(Guid id)
    {
        await _service.DeleteAsync(id);
        return NoContent();
    }
}