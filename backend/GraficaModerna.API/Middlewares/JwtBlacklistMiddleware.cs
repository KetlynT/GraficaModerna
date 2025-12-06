using System.IdentityModel.Tokens.Jwt;
using GraficaModerna.Application.Interfaces;

namespace GraficaModerna.API.Middlewares;

public class JwtValidationMiddleware(ITokenBlacklistService blacklistService) : IMiddleware
{
    private readonly ITokenBlacklistService _blacklistService = blacklistService;

    public async Task InvokeAsync(HttpContext context, RequestDelegate next)
    {
        var token = ExtractToken(context);

        if (string.IsNullOrEmpty(token))
        {
            await next(context);
            return;
        }

        var jwtHandler = new JwtSecurityTokenHandler();

        if (!jwtHandler.CanReadToken(token))
        {
            context.Response.StatusCode = StatusCodes.Status401Unauthorized;
            await context.Response.WriteAsync("Token inv�lido.");
            return;
        }

        var jwt = jwtHandler.ReadJwtToken(token);

        if (await _blacklistService.IsTokenBlacklistedAsync(token))
        {
            context.Response.StatusCode = StatusCodes.Status401Unauthorized;
            await context.Response.WriteAsync("Token revogado.");
            return;
        }

        var exp = jwt.Payload.Expiration;
        if (exp == null || DateTimeOffset.FromUnixTimeSeconds(exp.Value) < DateTimeOffset.UtcNow)
        {
            context.Response.StatusCode = StatusCodes.Status401Unauthorized;
            await context.Response.WriteAsync("Token expirado.");
            return;
        }



        var hasSubject = jwt.Claims.Any(c => c.Type == "sub" || c.Type == "nameid");
        var hasEmail = jwt.Claims.Any(c => c.Type == "email");
        var hasRole = jwt.Claims.Any(c => c.Type == "role");

        if (!hasSubject || !hasEmail || !hasRole)
        {
            context.Response.StatusCode = StatusCodes.Status401Unauthorized;

            var missing = new List<string>();
            if (!hasSubject) missing.Add("sub/nameid");
            if (!hasEmail) missing.Add("email");
            if (!hasRole) missing.Add("role");

            await context.Response.WriteAsync(
                $"Token incompleto. Faltando claims obrigat�rias: {string.Join(", ", missing)}");
            return;
        }

        await next(context);
    }

    private static string? ExtractToken(HttpContext context)
    {

        if (context.Request.Cookies.TryGetValue("jwt", out var cookieToken))
            return cookieToken;

        var header = context.Request.Headers.Authorization.ToString();
        if (!string.IsNullOrEmpty(header) && header.StartsWith("Bearer "))
            return header["Bearer ".Length..].Trim();

        return null;
    }
}
