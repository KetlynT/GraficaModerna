using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using System.Security.Claims;

namespace GraficaModerna.API.Controllers;

[Route("api/[controller]")]
[ApiController]
[Authorize]
public class OrdersController : ControllerBase
{
    private readonly IOrderService _orderService;

    public OrdersController(IOrderService orderService)
    {
        _orderService = orderService;
    }

    [HttpPost]
    public async Task<IActionResult> Checkout([FromBody] CheckoutDto dto)
    {
        try
        {
            var userId = User.FindFirstValue(ClaimTypes.NameIdentifier);

            // Chama o serviço corrigido que valida estoque, cupons e frete
            var order = await _orderService.CreateOrderFromCartAsync(
                userId,
                dto.Address,
                dto.CouponCode,
                dto.ShippingCost,
                dto.ShippingMethod
            );

            return Ok(order);
        }
        catch (Exception ex)
        {
            // Retorna 400 Bad Request com a mensagem de erro (ex: "Estoque insuficiente")
            // O frontend vai exibir isso no Toast.
            return BadRequest(new { message = ex.Message });
        }
    }

    [HttpGet]
    public async Task<IActionResult> GetMyOrders()
    {
        var userId = User.FindFirstValue(ClaimTypes.NameIdentifier);
        var orders = await _orderService.GetUserOrdersAsync(userId);
        return Ok(orders);
    }

    // Endpoint para Admin (futuro)
    [HttpGet("all")]
    [Authorize(Roles = "Admin")]
    public async Task<IActionResult> GetAllOrders()
    {
        var orders = await _orderService.GetAllOrdersAsync();
        return Ok(orders);
    }

    [HttpPost("{id}/cancel")]
    public async Task<IActionResult> RequestCancel(Guid id)
    {
        try
        {
            var userId = User.FindFirstValue(ClaimTypes.NameIdentifier);
            await _orderService.RequestRefundAsync(id, userId);
            return Ok();
        }
        catch (Exception ex)
        {
            return BadRequest(new { message = ex.Message });
        }
    }
}

// DTO Auxiliar para receber os dados do Frontend
public class CheckoutDto
{
    public CreateAddressDto Address { get; set; }
    public string? CouponCode { get; set; }
    public decimal ShippingCost { get; set; }
    public string ShippingMethod { get; set; }
}