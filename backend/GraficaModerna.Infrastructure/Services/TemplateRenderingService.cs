using GraficaModerna.Application.Interfaces; // Crie essa interface se não existir
using GraficaModerna.Domain.Entities;
using GraficaModerna.Infrastructure.Context; // Para acessar o banco e buscar o template
using Microsoft.EntityFrameworkCore;
using Scriban;
using Scriban.Runtime;

namespace GraficaModerna.Infrastructure.Services;

public class TemplateRenderingService(AppDbContext context)
{
    public async Task<string> RenderEmailAsync(string templateKey, object model)
    {
        var templateEntity = await context.Set<EmailTemplate>()
            .AsNoTracking()
            .FirstOrDefaultAsync(x => x.Key == templateKey);

        if (templateEntity == null)
        {
            throw new Exception($"Template de e-mail '{templateKey}' não encontrado.");
        }

        var template = Template.Parse(templateEntity.BodyContent);
        if (template.HasErrors)
        {
            throw new Exception($"Erro de sintaxe no template '{templateKey}': {template.Messages}");
        }

        var scriptObject = new ScriptObject();
        scriptObject.Import(model);

        var contextScriban = new TemplateContext();
        contextScriban.PushGlobal(scriptObject);

        return await template.RenderAsync(contextScriban);
    }
}