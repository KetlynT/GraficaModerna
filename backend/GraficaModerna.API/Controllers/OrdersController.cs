using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using System.Security.Claims;

namespace GraficaModerna.API.Controllers;

[Route("api/[controller]")]
[ApiController]
[Authorize] // Autenticação básica requerida para todos
public class OrdersController : ControllerBase
{
    private readonly IOrderService _orderService;

    public OrdersController(IOrderService orderService) { _orderService = orderService; }

    [HttpGet]
    [Authorize(Roles = "User")] // MUDANÇA: Apenas Clientes podem ver "seus" pedidos
    public async Task<ActionResult<List<OrderDto>>> GetMyOrders()
    {
        var userId = User.FindFirstValue(ClaimTypes.NameIdentifier);
        return Ok(await _orderService.GetUserOrdersAsync(userId!));
    }

    // Rotas de Admin continuam protegidas para Admin
    [HttpGet("admin/all")]
    [Authorize(Roles = "Admin")]
    public async Task<ActionResult<List<OrderDto>>> GetAllOrders() => Ok(await _orderService.GetAllOrdersAsync());

    [HttpPatch("{id}/status")]
    [Authorize(Roles = "Admin")]
    public async Task<IActionResult> UpdateStatus(Guid id, [FromBody] string newStatus)
    {
        try { await _orderService.UpdateOrderStatusAsync(id, newStatus); return NoContent(); }
        catch (Exception ex) { return BadRequest(ex.Message); }
    }
}