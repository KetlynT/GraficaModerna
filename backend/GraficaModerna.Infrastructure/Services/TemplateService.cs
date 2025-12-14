using GraficaModerna.Application.Interfaces;
using GraficaModerna.Domain.Entities;
using GraficaModerna.Infrastructure.Context;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Logging;
using Scriban;
using Scriban.Runtime;

namespace GraficaModerna.Infrastructure.Services;

public class TemplateService(AppDbContext context, ILogger<TemplateService> logger) : ITemplateService
{
    private readonly AppDbContext _context = context;
    private readonly ILogger<TemplateService> _logger = logger;

    public async Task<(string Subject, string Body)> RenderEmailAsync<T>(string templateKey, T model)
    {
        try
        {
            var templateEntity = await _context.EmailTemplates
                .AsNoTracking()
                .FirstOrDefaultAsync(x => x.Key == templateKey);

            if (templateEntity == null)
            {
                _logger.LogError("Template de e-mail '{TemplateKey}' não encontrado no banco de dados.", templateKey);
                throw new Exception($"Template de e-mail '{templateKey}' não configurado no sistema.");
            }

            var scriptObject = new ScriptObject();
            scriptObject.Import(model);

            var contextScriban = new TemplateContext();
            contextScriban.PushGlobal(scriptObject);

            var bodyTemplate = Template.Parse(templateEntity.BodyContent);
            if (bodyTemplate.HasErrors)
            {
                var errors = string.Join("; ", bodyTemplate.Messages);
                _logger.LogError("Erro de sintaxe no CORPO do template '{TemplateKey}': {Errors}", templateKey, errors);
                throw new Exception($"Erro de sintaxe no template '{templateKey}': {errors}");
            }
            var renderedBody = await bodyTemplate.RenderAsync(contextScriban);

            var subjectTemplate = Template.Parse(templateEntity.Subject);
            if (subjectTemplate.HasErrors)
            {
                var errors = string.Join("; ", subjectTemplate.Messages);
                _logger.LogError("Erro de sintaxe no ASSUNTO do template '{TemplateKey}': {Errors}", templateKey, errors);
                throw new Exception($"Erro de sintaxe no assunto do template '{templateKey}': {errors}");
            }
            var renderedSubject = await subjectTemplate.RenderAsync(contextScriban);

            return (renderedSubject, renderedBody);
        }
        catch (Exception ex)
        {
            _logger.LogCritical(ex, "Falha crítica ao renderizar template '{TemplateKey}'", templateKey);
            throw;
        }
    }
}