using System.Net;
using System.Security;
using GraficaModerna.Application.Interfaces;
using GraficaModerna.Domain.Entities;
using GraficaModerna.Infrastructure.Context;
using GraficaModerna.Infrastructure.Security;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.RateLimiting;
using Microsoft.EntityFrameworkCore;
using Stripe;
using Stripe.Checkout;

namespace GraficaModerna.API.Controllers;

[Route("api/webhook")]
[ApiController]
[AllowAnonymous]
[EnableRateLimiting("WebhookPolicy")]
public class StripeWebhookController(
    IConfiguration configuration,
    IOrderService orderService,
    ILogger<StripeWebhookController> logger,
    MetadataSecurityService securityService,
    AppDbContext context) : ControllerBase
{
    private readonly IConfiguration _configuration = configuration;
    private readonly ILogger<StripeWebhookController> _logger = logger;
    private readonly IOrderService _orderService = orderService;
    private readonly MetadataSecurityService _securityService = securityService;
    private readonly AppDbContext _context = context;

    [HttpPost("stripe")]
    public async Task<IActionResult> HandleStripeEvent()
    {
        var json = await new StreamReader(HttpContext.Request.Body).ReadToEndAsync();
        var endpointSecret = _configuration["STRIPE_WEBHOOK_SECRET"];

        if (string.IsNullOrEmpty(endpointSecret))
        {
            _logger.LogCritical("STRIPE_WEBHOOK_SECRET não configurado.");
            return StatusCode(500);
        }

        try
        {
            var signature = Request.Headers["Stripe-Signature"];
            var stripeEvent = EventUtility.ConstructEvent(json, signature, endpointSecret);

            var eventExists = await _context.ProcessedWebhookEvents
                .AsNoTracking()
                .AnyAsync(e => e.EventId == stripeEvent.Id);

            if (eventExists)
            {
                _logger.LogInformation("Evento {EventId} já processado anteriormente. Ignorando.", stripeEvent.Id);
                return Ok();
            }

            if (stripeEvent.Type == Events.CheckoutSessionCompleted)
            {
                await ProcessCheckoutSessionAsync(stripeEvent);
            }
            else if (stripeEvent.Type == Events.CheckoutSessionAsyncPaymentSucceeded)
            {
                await ProcessCheckoutSessionAsync(stripeEvent);
            }
            else if (stripeEvent.Type == Events.CheckoutSessionAsyncPaymentFailed ||
                     stripeEvent.Type == Events.PaymentIntentPaymentFailed)
            {
                _logger.LogWarning("[Webhook] Pagamento falhou: {EventId}", stripeEvent.Id);
            }
            else
            {
                _logger.LogInformation("[Webhook] Evento não tratado recebido: {EventType}. ID: {EventId}", stripeEvent.Type, stripeEvent.Id);
            }

            _context.ProcessedWebhookEvents.Add(new ProcessedWebhookEvent { EventId = stripeEvent.Id });
            await _context.SaveChangesAsync();

            return Ok();
        }
        catch (StripeException e)
        {
            _logger.LogError(e, "[Webhook] Erro de validação Stripe. Possível assinatura inválida.");
            return BadRequest("Invalid signature");
        }
        catch (Exception e)
        {
            _logger.LogError(e, "[Webhook] Erro interno ao processar webhook {EventId}.", Request.Headers["Stripe-Event-Id"]);
            return StatusCode(500, "Internal server error");
        }
    }

    private async Task ProcessCheckoutSessionAsync(Event stripeEvent)
    {
        if (stripeEvent.Data.Object is not Session session || session.Metadata == null)
            return;

        if (session.Metadata.TryGetValue("order_data", out var encryptedOrder))
        {
            try
            {
                var plainOrderId = _securityService.Unprotect(encryptedOrder);

                if (!Guid.TryParse(plainOrderId, out var orderId))
                {
                    _logger.LogError("ID descriptografado inválido no evento {EventId}.", stripeEvent.Id);
                    throw new Exception("Invalid Order ID format");
                }

                var transactionId = session.PaymentIntentId;
                var amountPaid = session.AmountTotal ?? 0;

                if (string.IsNullOrEmpty(transactionId))
                {
                    _logger.LogError("[Webhook] PaymentIntentId ausente para Order {OrderId}. Event: {EventId}", orderId, stripeEvent.Id);
                    return;
                }

                _logger.LogInformation(
                    "[Webhook] Processando pagamento para Order {OrderId}. Transaction: {TransactionId}",
                    orderId, transactionId);

                try
                {
                    await _orderService.ConfirmPaymentViaWebhookAsync(orderId, transactionId, amountPaid);
                    _logger.LogInformation("[Webhook] Pagamento confirmado com sucesso. Order {OrderId}", orderId);
                }
                catch (Exception ex) when (ex.Message.Contains("FATAL") || ex.Message.Contains("SECURITY"))
                {
                    _logger.LogCritical(ex, "[SECURITY ALERT] Tentativa de fraude detectada. Order: {OrderId}", orderId);
                    throw;
                }
            }
            catch (SecurityException)
            {
                _logger.LogCritical("FALHA DE INTEGRIDADE: Assinatura dos metadados inválida no Webhook {EventId}.", stripeEvent.Id);
                throw;
            }
        }
        else
        {
            _logger.LogWarning("[Webhook] Metadados de segurança ausentes ou inválidos no evento {EventId}", stripeEvent.Id);
        }
    }
}