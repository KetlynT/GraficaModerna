using System.Net;
using System.Text.Json;

namespace GraficaModerna.API.Middlewares;

public class ExceptionMiddleware
{
    private readonly RequestDelegate _next;
    private readonly ILogger<ExceptionMiddleware> _logger;
    private readonly IHostEnvironment _env;

    public ExceptionMiddleware(RequestDelegate next, ILogger<ExceptionMiddleware> logger, IHostEnvironment env)
    {
        _next = next;
        _logger = logger;
        _env = env;
    }

    public async Task InvokeAsync(HttpContext context)
    {
        try
        {
            await _next(context);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Um erro não tratado ocorreu.");

            context.Response.ContentType = "application/json";
            context.Response.StatusCode = (int)HttpStatusCode.InternalServerError;

            // CORRIGIDO: Lógica simplificada para evitar erro de tipo anônimo
            var response = new
            {
                StatusCode = context.Response.StatusCode,
                Message = _env.IsDevelopment() ? ex.Message : "Erro interno no servidor. Contate o suporte.",
                StackTrace = _env.IsDevelopment() ? ex.StackTrace : string.Empty
            };

            var options = new JsonSerializerOptions { PropertyNamingPolicy = JsonNamingPolicy.CamelCase };
            var json = JsonSerializer.Serialize(response, options);

            await context.Response.WriteAsync(json);
        }
    }
}