using GraficaModerna.Application.Interfaces;
using Microsoft.Extensions.Logging;

namespace GraficaModerna.Infrastructure.Services;

public class ConsoleEmailService(ILogger<ConsoleEmailService> logger) : IEmailService
{
    private readonly ILogger<ConsoleEmailService> _logger = logger;

    public Task SendEmailAsync(string to, string subject, string body)
    {

        _logger.LogInformation("--------------------------------------------------");
        _logger.LogInformation("[EMAIL SIMULADO] Para: {To}", to);
        _logger.LogInformation("[EMAIL SIMULADO] Assunto: {Subject}", subject);
        _logger.LogInformation("[EMAIL SIMULADO] Corpo: {Body}", body);
        _logger.LogInformation("--------------------------------------------------");
        return Task.CompletedTask;
    }
}
