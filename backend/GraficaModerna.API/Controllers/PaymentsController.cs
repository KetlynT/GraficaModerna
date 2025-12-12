using System.Security.Claims;
using GraficaModerna.Application.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.RateLimiting;

namespace GraficaModerna.API.Controllers;

[Route("api/[controller]")]
[ApiController]
[Authorize]
[EnableRateLimiting("StrictPaymentPolicy")]
public class PaymentsController(
    IPaymentService paymentService,
    IOrderService orderService,
    IContentService contentService,
    ILogger<PaymentsController> logger) : ControllerBase
{
    private readonly IContentService _contentService = contentService;
    private readonly IOrderService _orderService = orderService;
    private readonly IPaymentService _paymentService = paymentService;
    private readonly ILogger<PaymentsController> _logger = logger;

    private async Task CheckPurchaseEnabled()
    {
        var settings = await _contentService.GetSettingsAsync();
        if (settings.TryGetValue("purchase_enabled", out var enabled) && enabled == "false")
            throw new Exception("Pagamentos estão desativados temporariamente.");
    }

    [HttpPost("checkout-session/{orderId}")]
    public async Task<IActionResult> CreateSession(Guid orderId)
    {
        try
        {
            await CheckPurchaseEnabled();
        }
        catch (Exception ex)
        {
            return BadRequest(new { message = ex.Message });
        }

        var userId = User.FindFirstValue(ClaimTypes.NameIdentifier);

        if (string.IsNullOrEmpty(userId))
        {
            _logger.LogWarning("Tentativa de criar sessão sem userId válido");
            return Unauthorized("Usuário não identificado.");
        }

        try
        {
            var order = await _orderService.GetOrderForPaymentAsync(orderId, userId);
            var url = await _paymentService.CreateCheckoutSessionAsync(order);

            _logger.LogInformation(
                "Sessão de pagamento criada com sucesso. OrderId: {OrderId}",
                orderId);

            return Ok(new { url });
        }
        catch (KeyNotFoundException ex)
        {
            _logger.LogWarning("Erro de busca de pedido: {Message}", ex.Message);
            return NotFound(ex.Message);
        }
        catch (InvalidOperationException ex)
        {
            _logger.LogWarning("Erro de validação de pedido: {Message}", ex.Message);
            return BadRequest(new { message = ex.Message });
        }
        catch (Exception ex)
        {
            _logger.LogError(
                ex,
                "Erro ao processar pagamento. OrderId: {OrderId}",
                orderId);

            return StatusCode(500, new
            {
                message = "Erro ao processar pagamento. Tente novamente em alguns instantes."
            });
        }
    }

    [HttpGet("status/{orderId}")]
    public async Task<IActionResult> GetPaymentStatus(Guid orderId)
    {
        var userId = User.FindFirstValue(ClaimTypes.NameIdentifier);

        try
        {
            var status = await _orderService.GetPaymentStatusAsync(orderId, userId!);
            return Ok(status);
        }
        catch (KeyNotFoundException)
        {
            return NotFound("Pedido não encontrado.");
        }
    }
}