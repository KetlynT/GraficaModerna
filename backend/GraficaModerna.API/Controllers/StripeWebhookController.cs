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
    private readonly IConfiguration _configuration;
    private readonly IOrderService _orderService;
    private readonly ILogger<StripeWebhookController> _logger;
    private readonly HashSet<string> _authorizedIps;

    public StripeWebhookController(
        IConfiguration configuration,
        IOrderService orderService,
        ILogger<StripeWebhookController> logger)
    {
        _configuration = configuration;
        _orderService = orderService;
        _logger = logger;

        // LEITURA DO .ENV
        var ipsString = Environment.GetEnvironmentVariable("STRIPE_WEBHOOK_IPS")
                        ?? _configuration["STRIPE_WEBHOOK_IPS"];

        // CORREÇÃO 2: Inicialização de Coleção Simplificada (Sintaxe C# 12)
        if (!string.IsNullOrEmpty(ipsString))
        {
            var ips = ipsString.Split(',', StringSplitOptions.RemoveEmptyEntries | StringSplitOptions.TrimEntries);
            _authorizedIps = [.. ips]; // Antes: new HashSet<string>(ips);
        }
        else
        {
            // Se não houver configuração, inicia vazio para bloquear tudo por segurança
            _authorizedIps = []; // Antes: new HashSet<string>();
            _logger.LogWarning("ALERTA: 'STRIPE_WEBHOOK_IPS' não configurado. Webhooks podem ser bloqueados.");
        }
    }

    [HttpPost("stripe")]
    public async Task<IActionResult> HandleStripeEvent()
    {
        // 1. Validação de IP
        if (!IsRequestFromStripe(HttpContext.Connection.RemoteIpAddress))
        {
            // CORREÇÃO 1: Log Estruturado (Sem cifrão $, usando placeholders {})
            _logger.LogWarning("[Webhook] Bloqueado IP não autorizado: {RemoteIp}", HttpContext.Connection.RemoteIpAddress);
            return StatusCode(403, "Forbidden: IP Source Denied");
        }

        var json = await new StreamReader(HttpContext.Request.Body).ReadToEndAsync();
        var endpointSecret = Environment.GetEnvironmentVariable("STRIPE_WEBHOOK_SECRET")
                             ?? _configuration["Stripe:WebhookSecret"];

        if (string.IsNullOrEmpty(endpointSecret))
        {
            _logger.LogError("CRÍTICO: Stripe Webhook Secret não configurado.");
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
                {
                    if (Guid.TryParse(orderIdString, out Guid orderId))
                    {
                        var transactionId = session.PaymentIntentId;
                        long amountPaid = session.AmountTotal ?? 0;

                        // CORREÇÃO 1: Log Estruturado
                        _logger.LogInformation("[Webhook] Processando pagamento para Pedido {OrderId}. Transação: {TransactionId}. Valor: {AmountPaid}",
                            orderId, transactionId, amountPaid);

                        await _orderService.ConfirmPaymentViaWebhookAsync(orderId, transactionId, amountPaid);
                    }
                    else
                    {
                        // CORREÇÃO 1: Log Estruturado
                        _logger.LogWarning("[Webhook] Order ID inválido nos metadados: {OrderIdString}", orderIdString);
                    }
                }
            }
            else if (stripeEvent.Type == Events.PaymentIntentPaymentFailed)
            {
                // CORREÇÃO 1: Log Estruturado
                _logger.LogWarning("[Webhook] Pagamento falhou: {EventId}", stripeEvent.Id);
            }

            return Ok();
        }
        catch (StripeException e)
        {
            // Logs de erro já costumam aceitar exceção como primeiro argumento, mas a mensagem deve ser template
            _logger.LogError(e, "Erro validação Stripe.");
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

        // Permite testes locais
        if (IPAddress.IsLoopback(remoteIp)) return true;

        if (_authorizedIps.Count == 0) return false;

        var ip = remoteIp.MapToIPv4().ToString();
        return _authorizedIps.Contains(ip);
    }
}