using System.Security.Claims;
using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.RateLimiting;

namespace GraficaModerna.API.Controllers;

[Route("api/[controller]")]
[ApiController]
[Authorize]
[EnableRateLimiting("UserActionPolicy")]
public class AddressesController(IAddressService service, IContentService contentService) : ControllerBase
{
    private readonly IAddressService _service = service;
    private readonly IContentService _contentService = contentService;

    private string GetUserId()
    {
        return User.FindFirstValue(ClaimTypes.NameIdentifier)!;
    }

    private async Task CheckPurchaseEnabled()
    {
        var settings = await _contentService.GetSettingsAsync();
        if (settings.TryGetValue("purchase_enabled", out var enabled) && enabled == "false")
            throw new Exception("Gerenciamento de endereços indisponível no modo orçamento.");
    }

    [HttpGet]
    public async Task<ActionResult<List<AddressDto>>> GetAll()
    {
        // Leitura permitida (ou poderia ser bloqueada também se desejar esconder tudo)
        return Ok(await _service.GetUserAddressesAsync(GetUserId()));
    }

    [HttpGet("{id}")]
    public async Task<ActionResult<AddressDto>> GetById(Guid id)
    {
        try
        {
            return Ok(await _service.GetByIdAsync(id, GetUserId()));
        }
        catch (KeyNotFoundException)
        {
            return NotFound();
        }
    }

    [HttpPost]
    public async Task<ActionResult<AddressDto>> Create(CreateAddressDto dto)
    {
        try
        {
            await CheckPurchaseEnabled();
            if (!ModelState.IsValid) return BadRequest(ModelState);
            var created = await _service.CreateAsync(GetUserId(), dto);
            return CreatedAtAction(nameof(GetById), new { id = created.Id }, created);
        }
        catch (Exception ex)
        {
            return BadRequest(new { message = ex.Message });
        }
    }

    [HttpPut("{id}")]
    public async Task<IActionResult> Update(Guid id, CreateAddressDto dto)
    {
        try
        {
            await CheckPurchaseEnabled();
            if (!ModelState.IsValid) return BadRequest(ModelState);
            await _service.UpdateAsync(id, GetUserId(), dto);
            return NoContent();
        }
        catch (KeyNotFoundException)
        {
            return NotFound();
        }
        catch (Exception ex)
        {
            return BadRequest(new { message = ex.Message });
        }
    }

    [HttpDelete("{id}")]
    public async Task<IActionResult> Delete(Guid id)
    {
        try
        {
            await CheckPurchaseEnabled();
            await _service.DeleteAsync(id, GetUserId());
            return NoContent();
        }
        catch (Exception ex)
        {
            return BadRequest(new { message = ex.Message });
        }
    }
}