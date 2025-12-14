using GraficaModerna.Application.Interfaces;
using GraficaModerna.Domain.Entities;
using GraficaModerna.Infrastructure.Context;
using Microsoft.EntityFrameworkCore;
using Scriban;
using Scriban.Runtime;

namespace GraficaModerna.Infrastructure.Services;

public class TemplateService(AppDbContext context) : ITemplateService
{
    public async Task<(string Subject, string Body)> RenderEmailAsync<T>(string templateKey, T model)
    {
        var templateEntity = await context.EmailTemplates
            .AsNoTracking()
            .FirstOrDefaultAsync(x => x.Key == templateKey);

        if (templateEntity == null)
            throw new Exception($"Template de e-mail '{templateKey}' não configurado no sistema.");

        var scriptObject = new ScriptObject();
        scriptObject.Import(model);

        var contextScriban = new TemplateContext();
        contextScriban.PushGlobal(scriptObject);

        var bodyTemplate = Template.Parse(templateEntity.BodyContent);
        if (bodyTemplate.HasErrors)
            throw new Exception($"Erro de sintaxe no template '{templateKey}': {bodyTemplate.Messages}");

        var renderedBody = await bodyTemplate.RenderAsync(contextScriban);

        var subjectTemplate = Template.Parse(templateEntity.Subject);
        var renderedSubject = await subjectTemplate.RenderAsync(contextScriban);

        return (renderedSubject, renderedBody);
    }
}