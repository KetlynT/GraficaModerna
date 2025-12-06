using GraficaModerna.Application.Interfaces;
using Microsoft.Extensions.Logging;

namespace GraficaModerna.Infrastructure.Services;

// Implementação que apenas loga no console (útil para dev)
public class ConsoleEmailService(ILogger<ConsoleEmailService> logger) : IEmailService
{
    private readonly ILogger<ConsoleEmailService> _logger = logger;

    public Task SendEmailAsync(string to, string subject, string body)
    {
        // Simula o envio
        _logger.LogInformation("--------------------------------------------------");
        _logger.LogInformation($"[EMAIL SIMULADO] Para: {to}");
        _logger.LogInformation($"[EMAIL SIMULADO] Assunto: {subject}");
        _logger.LogInformation($"[EMAIL SIMULADO] Corpo: {body}");
        _logger.LogInformation("--------------------------------------------------");
        return Task.CompletedTask;
    }
}