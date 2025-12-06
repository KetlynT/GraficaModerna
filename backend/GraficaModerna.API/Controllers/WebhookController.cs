using GraficaModerna.Application.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace GraficaModerna.API.Controllers;

[Route("api/[controller]")]
[ApiController]

public class WebhookController(
    IOrderService orderService,
    IConfiguration configuration,
    ILogger<WebhookController> logger) : ControllerBase
{
    private readonly IConfiguration _configuration = configuration;
    private readonly ILogger<WebhookController> _logger = logger;
    private readonly IOrderService _orderService = orderService;

    [HttpPost("payment-update")]
    [AllowAnonymous]
    public async Task<IActionResult> ReceiveNotification()
    {
        using var reader = new StreamReader(HttpContext.Request.Body);
        var jsonBody = await reader.ReadToEndAsync();

        var signature = Request.Headers["X-Gateway-Signature"].FirstOrDefault();
        var webhookSecret = _configuration["PaymentSettings:WebhookSecret"];

        var isValidSignature = !string.IsNullOrEmpty(webhookSecret);

        if (!isValidSignature)
        {
            _logger.LogWarning("Tentativa de Webhook com assinatura inv�lida.");
            return Unauthorized();
        }

        try
        {

            var mockOrderId = Guid.Empty;
            var status = "approved";
            var transactionId = "txn_123456";
            long amountPaidInCents = 10000;

            if (status == "approved")
            {
                await _orderService.ConfirmPaymentViaWebhookAsync(mockOrderId, transactionId, amountPaidInCents);

                _logger.LogInformation("Pagamento aprovado via Webhook para o pedido {OrderId}", mockOrderId);
            }

            return Ok();
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Erro ao processar webhook");
            return StatusCode(500);
        }
    }
}
