using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.RateLimiting;

namespace GraficaModerna.API.Controllers;

[Route("api/[controller]")]
[ApiController]
[Authorize(Roles = "Admin")]
[EnableRateLimiting("AdminPolicy")]
public class AdminController(IOrderService orderService, IProductService productService) : ControllerBase
{
    private readonly IOrderService _orderService = orderService;
    private readonly IProductService _productService = productService;

    [HttpGet("orders")]
    public async Task<ActionResult<List<AdminOrderDto>>> GetAllOrders()
    {
        var orders = await _orderService.GetAllOrdersAsync();
        return Ok(orders);
    }

    [HttpPut("orders/{id}/status")]
    public async Task<IActionResult> UpdateStatus(Guid id, [FromBody] UpdateOrderStatusDto dto)
    {
        try
        {
            await _orderService.UpdateAdminOrderAsync(id, dto);
            return Ok(new { message = "Status atualizado com sucesso." });
        }
        catch (Exception ex)
        {
            return BadRequest(new { message = ex.Message });
        }
    }
}