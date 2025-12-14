namespace GraficaModerna.Application.Interfaces;

public interface ITemplateService
{
    Task<(string Subject, string Body)> RenderEmailAsync<T>(string templateKey, T model);
}