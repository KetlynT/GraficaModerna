using GraficaModerna.Application.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using System.Security.Claims; // Necessário para ler o Token

namespace GraficaModerna.API.Controllers;

[Route("api/[controller]")]
[ApiController]
[Authorize(Roles = "User")] // Apenas Clientes podem executar pagamentos
public class PaymentController : ControllerBase
{
    private readonly IOrderService _orderService;

    public PaymentController(IOrderService orderService) { _orderService = orderService; }

    [HttpPost("pay/{orderId}")]
    public async Task<IActionResult> SimulatePayment(Guid orderId)
    {
        await Task.Delay(1000); // Simula processamento
        try
        {
            // CORREÇÃO DE SEGURANÇA (IDOR):
            // Obtém o ID do usuário diretamente do Token de autenticação.
            // Isso impede que um usuário pague o pedido de outro.
            var userId = User.FindFirstValue(ClaimTypes.NameIdentifier);

            if (string.IsNullOrEmpty(userId))
                return Unauthorized("Usuário não identificado.");

            // Chama o novo método seguro no serviço
            await _orderService.PayOrderAsync(orderId, userId);

            return Ok(new { message = "Aprovado!" });
        }
        catch (Exception ex)
        {
            // Retorna 400 Bad Request se a validação de segurança falhar
            return BadRequest(new { message = ex.Message });
        }
    }
}