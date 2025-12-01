using GraficaModerna.Application.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace GraficaModerna.API.Controllers;

[Route("api/[controller]")]
[ApiController]
[Authorize(Roles = "User")] // MUDANÇA: Apenas Clientes podem executar pagamentos
public class PaymentController : ControllerBase
{
    private readonly IOrderService _orderService;

    public PaymentController(IOrderService orderService) { _orderService = orderService; }

    [HttpPost("pay/{orderId}")]
    public async Task<IActionResult> SimulatePayment(Guid orderId)
    {
        await Task.Delay(1000);
        try { await _orderService.UpdateOrderStatusAsync(orderId, "Pago"); return Ok(new { message = "Aprovado!" }); }
        catch (Exception ex) { return BadRequest(ex.Message); }
    }
}