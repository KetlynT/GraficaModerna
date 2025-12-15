using GraficaModerna.Application.Interfaces;
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
        if (coupon == null) return NotFound("Cupom inválido ou expirado.");

        return Ok(new { coupon.Code, coupon.DiscountPercentage });
    }
}