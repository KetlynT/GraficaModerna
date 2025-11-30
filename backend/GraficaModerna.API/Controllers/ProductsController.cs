using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using Microsoft.AspNetCore.Mvc;

namespace GraficaModerna.API.Controllers;

[Route("api/[controller]")]
[ApiController]
public class ProductsController : ControllerBase
{
    private readonly IProductService _service;

    public ProductsController(IProductService service)
    {
        _service = service;
    }

    [HttpGet]
    public async Task<ActionResult<IEnumerable<ProductResponseDto>>> GetAll()
    {
        var products = await _service.GetCatalogAsync();
        return Ok(products);
    }

    [HttpPost]
    // [Authorize(Roles = "Admin")] // Descomentar quando implementar Auth
    public async Task<ActionResult<ProductResponseDto>> Create([FromBody] CreateProductDto dto)
    {
        if (!ModelState.IsValid) return BadRequest(ModelState);

        var result = await _service.CreateAsync(dto);
        return CreatedAtAction(nameof(GetAll), new { id = result.Id }, result);
    }
}