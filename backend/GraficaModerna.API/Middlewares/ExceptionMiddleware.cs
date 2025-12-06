using System.Net;
using System.Text.Json;

namespace GraficaModerna.API.Middlewares;

public class ExceptionMiddleware(
    RequestDelegate next,
    ILogger<ExceptionMiddleware> logger,
    IHostEnvironment env)
{
    // Instância estática para performance (evita recriar a cada erro)
    private static readonly JsonSerializerOptions _jsonOptions = new()
    {
        PropertyNamingPolicy = JsonNamingPolicy.CamelCase
    };

    public async Task InvokeAsync(HttpContext context)
    {
        try
        {
            await next(context);
        }
        catch (Exception ex)
        {
            var traceId = context.TraceIdentifier;

            logger.LogError(ex, "Um erro não tratado ocorreu. TraceId: {TraceId}", traceId);

            context.Response.ContentType = "application/json";
            context.Response.StatusCode = (int)HttpStatusCode.InternalServerError;

            var response = new
            {
                // CORREÇÃO IDE0037: O nome 'StatusCode' é inferido automaticamente desta propriedade.
                // Não é necessário escrever "StatusCode = ..."
                context.Response.StatusCode,

                Message = env.IsDevelopment() ? ex.Message : "Erro interno no servidor. Contate o suporte informando o código de rastreio.",
                StackTrace = env.IsDevelopment() ? ex.StackTrace : string.Empty,

                // CORREÇÃO IDE0037: O nome 'traceId' é inferido automaticamente da variável local.
                traceId
            };

            var json = JsonSerializer.Serialize(response, _jsonOptions);

            await context.Response.WriteAsync(json);
        }
    }
}