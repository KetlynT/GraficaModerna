using GraficaModerna.Application.Interfaces;
using GraficaModerna.Infrastructure.Context; // Acesso para buscar o pedido
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using System.Security.Claims;

namespace GraficaModerna.API.Controllers;

[Route("api/[controller]")]
[ApiController]
[Authorize]
public class PaymentsController : ControllerBase
{
    private readonly IPaymentService _paymentService;
    private readonly AppDbContext _context;

    public PaymentsController(IPaymentService paymentService, AppDbContext context)
    {
        _paymentService = paymentService;
        _context = context;
    }

    [HttpPost("checkout-session/{orderId}")]
    public async Task<IActionResult> CreateSession(Guid orderId)
    {
        var userId = User.FindFirstValue(ClaimTypes.NameIdentifier);

        // Busca o pedido garantindo que pertence ao usuário logado
        var order = await _context.Orders
            .Include(o => o.Items)
            .FirstOrDefaultAsync(o => o.Id == orderId && o.UserId == userId);

        if (order == null) return NotFound("Pedido não encontrado.");
        if (order.Status == "Pago") return BadRequest("Este pedido já está pago.");

        try
        {
            // Gera a URL do Stripe (com arredondamento corrigido no Serviço)
            var url = await _paymentService.CreateCheckoutSessionAsync(order);
            return Ok(new { url });
        }
        catch (Exception ex)
        {
            return BadRequest(new { message = $"Erro ao iniciar pagamento: {ex.Message}" });
        }
    }
}