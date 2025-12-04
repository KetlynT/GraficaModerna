using GraficaModerna.Application.Interfaces;
using Microsoft.AspNetCore.Mvc;
using Stripe;
using Stripe.Checkout;

namespace GraficaModerna.API.Controllers;

[Route("api/webhook")]
[ApiController]
public class StripeWebhookController : ControllerBase
{
    private readonly IConfiguration _configuration;
    private readonly IOrderService _orderService;
    private readonly ILogger<StripeWebhookController> _logger;

    public StripeWebhookController(
        IConfiguration configuration,
        IOrderService orderService,
        ILogger<StripeWebhookController> logger)
    {
        _configuration = configuration;
        _orderService = orderService;
        _logger = logger;
    }

    [HttpPost("stripe")]
    public async Task<IActionResult> HandleStripeEvent()
    {
        var json = await new StreamReader(HttpContext.Request.Body).ReadToEndAsync();
        var endpointSecret = _configuration["Stripe:WebhookSecret"];

        if (string.IsNullOrEmpty(endpointSecret))
        {
            _logger.LogError("Webhook Secret não configurado.");
            return StatusCode(500);
        }

        try
        {
            var signature = Request.Headers["Stripe-Signature"];

            // CORREÇÃO: throwOnApiVersionMismatch = true (Segurança)
            // Se o Stripe mudar o formato do JSON, queremos que falhe explicitamente
            // em vez de processar dados errados silenciosamente.
            var stripeEvent = EventUtility.ConstructEvent(
                json,
                signature,
                endpointSecret,
                throwOnApiVersionMismatch: true
            );

            if (stripeEvent.Type == Events.CheckoutSessionCompleted)
            {
                var session = stripeEvent.Data.Object as Session;

                if (session != null && session.Metadata != null && session.Metadata.TryGetValue("order_id", out var orderIdString))
                {
                    if (Guid.TryParse(orderIdString, out Guid orderId))
                    {
                        var transactionId = session.PaymentIntentId;
                        _logger.LogInformation($"Pagamento confirmado: Pedido {orderId}");

                        await _orderService.ConfirmPaymentViaWebhookAsync(orderId, transactionId);
                    }
                }
            }

            return Ok();
        }
        catch (StripeException e)
        {
            _logger.LogError(e, "Erro no Webhook Stripe.");
            return BadRequest();
        }
        catch (Exception e)
        {
            _logger.LogError(e, "Erro interno Webhook.");
            return StatusCode(500);
        }
    }
}