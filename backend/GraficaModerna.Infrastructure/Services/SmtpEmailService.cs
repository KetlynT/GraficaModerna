using GraficaModerna.Application.Interfaces;
using MailKit.Net.Smtp;
using MailKit.Security;
using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.Logging;
using MimeKit;

namespace GraficaModerna.Infrastructure.Services;

public class SmtpEmailService(IConfiguration configuration, ILogger<SmtpEmailService> logger) : IEmailService
{
    private readonly IConfiguration _configuration = configuration;
    private readonly ILogger<SmtpEmailService> _logger = logger;

    public async Task SendEmailAsync(string to, string subject, string body)
    {
        var host = _configuration["SMTP_HOST"];
        var portStr = _configuration["SMTP_PORT"];
        var username = _configuration["SMTP_USERNAME"];
        var password = _configuration["SMTP_PASSWORD"];
        var fromEmail = _configuration["SMTP_FROM_EMAIL"];
        var fromName = _configuration["SMTP_FROM_NAME"];

        if (string.IsNullOrEmpty(host) || string.IsNullOrEmpty(username) || string.IsNullOrEmpty(password))
        {
            _logger.LogWarning("SMTP não configurado no .env. Email para {To} ignorado.", to);
            return;
        }

        try
        {
            var emailMessage = new MimeMessage();
            emailMessage.From.Add(new MailboxAddress(fromName, fromEmail));
            emailMessage.To.Add(MailboxAddress.Parse(to));
            emailMessage.Subject = subject;

            var bodyBuilder = new BodyBuilder { HtmlBody = body };
            emailMessage.Body = bodyBuilder.ToMessageBody();

            using var client = new SmtpClient();

            var port = int.TryParse(portStr, out var p) ? p : 587;

            await client.ConnectAsync(host, port, SecureSocketOptions.StartTls);
            await client.AuthenticateAsync(username, password);

            await client.SendAsync(emailMessage);

            await client.DisconnectAsync(true);

            _logger.LogInformation("Email enviado para: {To}", to);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Erro ao enviar email para {To}", to);
        }
    }
}