using System.Net;
using System.Text.Json;

namespace GraficaModerna.API.Middlewares;

public class ExceptionMiddleware(
    RequestDelegate next,
    ILogger<ExceptionMiddleware> logger,
    IHostEnvironment env)
{

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

            logger.LogError(ex, "Um erro n�o tratado ocorreu. TraceId: {TraceId}", traceId);

            context.Response.ContentType = "application/json";
            context.Response.StatusCode = (int)HttpStatusCode.InternalServerError;

            var response = new
            {


                context.Response.StatusCode,

                Message = env.IsDevelopment()
                    ? ex.Message
                    : "Erro interno no servidor. Contate o suporte informando o c�digo de rastreio.",
                StackTrace = env.IsDevelopment() ? ex.StackTrace : string.Empty,

                traceId
            };

            var json = JsonSerializer.Serialize(response, _jsonOptions);

            await context.Response.WriteAsync(json);
        }
    }
}
