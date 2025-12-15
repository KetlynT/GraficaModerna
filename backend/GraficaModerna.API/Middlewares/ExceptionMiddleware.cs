using System.Net;
using System.Text.Json;
using Microsoft.EntityFrameworkCore;

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

            logger.LogError(ex,
                "Um erro não tratado ocorreu. TraceId: {TraceId}",
                traceId);

            context.Response.ContentType = "application/json";

            context.Response.StatusCode = ex switch
            {
                ArgumentException => (int)HttpStatusCode.BadRequest,
                InvalidOperationException => (int)HttpStatusCode.BadRequest,
                UnauthorizedAccessException => (int)HttpStatusCode.Unauthorized,
                KeyNotFoundException => (int)HttpStatusCode.NotFound,
                DbUpdateConcurrencyException => (int)HttpStatusCode.Conflict,
                _ => (int)HttpStatusCode.InternalServerError
            };

            context.Response.Headers["X-Trace-Id"] = traceId;

            var message = ex switch
            {
                DbUpdateConcurrencyException => "O item foi modificado por outro processo. Por favor, tente novamente.",
                _ => env.IsDevelopment() ? ex.Message : "Ocorreu um erro interno no servidor."
            };

            if (context.Response.StatusCode >= 400 && context.Response.StatusCode < 500 && context.Response.StatusCode != 409)
            {
                message = ex.Message;
            }

            var response = new
            {
                statusCode = context.Response.StatusCode,
                message,
                traceId
            };

            var json = JsonSerializer.Serialize(response, _jsonOptions);

            await context.Response.WriteAsync(json);
        }
    }
}