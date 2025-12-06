using System.Net;
using GraficaModerna.Application.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.RateLimiting;
using Stripe;
using Stripe.Checkout;

namespace GraficaModerna.API.Controllers;

[Route("api/webhook")]
[ApiController]
[AllowAnonymous]
[EnableRateLimiting("WebhookPolicy")]
public class StripeWebhookController : ControllerBase
{
    private readonly HashSet<string> _authorizedIps;
    private readonly IConfiguration _configuration;
    private readonly ILogger<StripeWebhookController> _logger;
    private readonly IOrderService _orderService;

    public StripeWebhookController(
        IConfiguration configuration,
        IOrderService orderService,
        ILogger<StripeWebhookController> logger)
    {
        _configuration = configuration;
        _orderService = orderService;
        _logger = logger;

        var ipsString = Environment.GetEnvironmentVariable("STRIPE_WEBHOOK_IPS")
                        ?? _configuration["STRIPE_WEBHOOK_IPS"];

        if (!string.IsNullOrEmpty(ipsString))
        {
            var ips = ipsString.Split(',', StringSplitOptions.RemoveEmptyEntries | StringSplitOptions.TrimEntries);
            _authorizedIps = [.. ips]; 
        }
        else
        {

            _authorizedIps = []; 
            _logger.LogWarning("ALERTA: 'STRIPE_WEBHOOK_IPS' n�o configurado. Webhooks podem ser bloqueados.");
        }
    }

    [HttpPost("stripe")]
    public async Task<IActionResult> HandleStripeEvent()
    {

        if (!IsRequestFromStripe(HttpContext.Connection.RemoteIpAddress))
        {

            _logger.LogWarning("[Webhook] Bloqueado IP n�o autorizado: {RemoteIp}",
                HttpContext.Connection.RemoteIpAddress);
            return StatusCode(403, "Forbidden: IP Source Denied");
        }

        var json = await new StreamReader(HttpContext.Request.Body).ReadToEndAsync();
        var endpointSecret = Environment.GetEnvironmentVariable("STRIPE_WEBHOOK_SECRET")
                             ?? _configuration["Stripe:WebhookSecret"];

        if (string.IsNullOrEmpty(endpointSecret))
        {
            _logger.LogError("CR�TICO: Stripe Webhook Secret n�o configurado.");
            return StatusCode(500);
        }

        try
        {
            var signature = Request.Headers["Stripe-Signature"];
            var stripeEvent = EventUtility.ConstructEvent(json, signature, endpointSecret);

            if (stripeEvent.Data.Object is Session session &&
                session.Metadata != null &&
                session.Metadata.TryGetValue("order_id", out var orderIdString))
            {
                if (Guid.TryParse(orderIdString, out var orderId))
                {
                    var transactionId = session.PaymentIntentId;
                    var amountPaid = session.AmountTotal ?? 0;

                    _logger.LogInformation(
                        "[Webhook] Processando pagamento para Pedido {OrderId}. Transa��o: {TransactionId}. Valor: {AmountPaid}",
                        orderId, transactionId, amountPaid);

                    await _orderService.ConfirmPaymentViaWebhookAsync(orderId, transactionId, amountPaid);
                }
                else
                {

                    _logger.LogWarning("[Webhook] Order ID inv�lido nos metadados: {OrderIdString}", orderIdString);
                }
            }
            else if (stripeEvent.Type == Events.PaymentIntentPaymentFailed)

            {
                _logger.LogWarning("[Webhook] Pagamento falhou: {EventId}", stripeEvent.Id);
            }

            return Ok();
        }
        catch (StripeException e)
        {

            _logger.LogError(e, "Erro valida��o Stripe.");
            return BadRequest();
        }
        catch (Exception e)
        {
            _logger.LogError(e, "Erro interno Webhook.");
            return StatusCode(500);
        }
    }

    private bool IsRequestFromStripe(IPAddress? remoteIp)
    {
        if (remoteIp == null) return false;

        if (IPAddress.IsLoopback(remoteIp)) return true;

        if (_authorizedIps.Count == 0) return false;

        var ip = remoteIp.MapToIPv4().ToString();
        return _authorizedIps.Contains(ip);
    }
}
