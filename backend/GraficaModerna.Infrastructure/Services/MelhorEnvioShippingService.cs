using System.Globalization;
using System.Net;
using System.Net.Http.Headers;
using System.Text;
using System.Text.Json;
using System.Text.Json.Serialization;
using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using GraficaModerna.Domain.Entities;
using GraficaModerna.Infrastructure.Context;
using GraficaModerna.Infrastructure.Helpers;
using Microsoft.AspNetCore.Hosting;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.Hosting;
using Microsoft.Extensions.Logging;

namespace GraficaModerna.Infrastructure.Services;

public class MelhorEnvioShippingService(
    AppDbContext context,
    IHttpClientFactory httpClientFactory,
    IConfiguration configuration,
    IWebHostEnvironment env,
    ILogger<MelhorEnvioShippingService> logger) : IShippingService
{
    private readonly IConfiguration _configuration = configuration;
    private readonly AppDbContext _context = context;
    private readonly IWebHostEnvironment _env = env;
    private readonly IHttpClientFactory _httpClientFactory = httpClientFactory;
    private readonly ILogger<MelhorEnvioShippingService> _logger = logger;

    private static readonly JsonSerializerOptions _jsonOptions = new()
    {
        PropertyNamingPolicy = JsonNamingPolicy.SnakeCaseLower
    };

    public async Task<List<ShippingOptionDto>> CalculateAsync(string destinationCep, List<ShippingItemDto> items)
    {
        if (items == null || items.Count == 0) return [];

        var originCepSetting = await _context.SiteSettings
            .AsNoTracking()
            .FirstOrDefaultAsync(s => s.Key == "sender_cep");

        var originCep = originCepSetting?.Value?.Replace("-", "").Trim();

        if (string.IsNullOrEmpty(originCep))
        {
            throw new Exception("CEP de origem não configurado. Entre em contato com a administração.");
        }

        var requestPayload = new
        {
            from = new { postal_code = originCep },
            to = new { postal_code = destinationCep.Replace("-", "").Trim() },
            products = items.Select(i => new
            {
                width = i.Width,
                height = i.Height,
                length = i.Length,
                weight = i.Weight,
                insurance_value = 0,
                quantity = i.Quantity
            }).ToList()
        };

        try
        {
            var client = _httpClientFactory.CreateClient("MelhorEnvio");

            const int maxRetries = 3;
            int currentRetry = 0;
            HttpResponseMessage? response = null;

            while (currentRetry < maxRetries)
            {
                try
                {
                    var token = await GetAccessTokenAsync();
                    response = await SendCalculateRequestAsync(client, token, requestPayload);

                    if (response.StatusCode == HttpStatusCode.Unauthorized)
                    {
                        _logger.LogWarning("Token Melhor Envio expirado (401). Tentando renovação...");
                        var newToken = await RefreshAccessTokenAsync();
                        if (!string.IsNullOrEmpty(newToken))
                        {
                            response = await SendCalculateRequestAsync(client, newToken, requestPayload);
                        }
                    }

                    if (response.IsSuccessStatusCode || (int)response.StatusCode < 500)
                    {
                        break;
                    }

                    throw new HttpRequestException($"Erro de servidor: {response.StatusCode}");
                }
                catch (HttpRequestException ex)
                {
                    currentRetry++;
                    _logger.LogWarning(ex, "Falha na conexão com Melhor Envio. Tentativa {Retry}/{Max}", currentRetry, maxRetries);

                    if (currentRetry >= maxRetries) throw;

                    await Task.Delay(1000 * currentRetry);
                }
            }

            if (response == null || !response.IsSuccessStatusCode)
            {
                var errorBody = response != null ? await response.Content.ReadAsStringAsync() : "Sem resposta";
                _logger.LogError("Erro API Melhor Envio ({StatusCode}): {Body}", response?.StatusCode, errorBody);
                return [];
            }

            var responseBody = await response.Content.ReadAsStringAsync();
            var meOptions = JsonSerializer.Deserialize<List<MelhorEnvioResponse>>(responseBody, _jsonOptions);

            if (meOptions == null) return [];

            var validOptions = meOptions
                .Where(x => string.IsNullOrEmpty(x.Error))
                .Select(x =>
                {
                    decimal price = 0;
                    if (decimal.TryParse(x.Price, NumberStyles.Any, CultureInfo.InvariantCulture, out var parsedPrice))
                        price = parsedPrice;
                    else if (decimal.TryParse(x.CustomPrice, NumberStyles.Any, CultureInfo.InvariantCulture,
                                 out var parsedCustomPrice)) price = parsedCustomPrice;

                    return new ShippingOptionDto
                    {
                        Name = $"{x.Company?.Name} - {x.Name}",
                        Price = price,
                        DeliveryDays = x.DeliveryRange?.Max ?? x.DeliveryTime,
                        Provider = "Melhor Envio"
                    };
                })
                .Where(x => x.Price > 0)
                .OrderBy(x => x.Price)
                .ToList();

            return validOptions;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Erro crítico ou esgotamento de tentativas ao calcular frete Melhor Envio.");
            return [];
        }
    }

    private async Task<HttpResponseMessage> SendCalculateRequestAsync(HttpClient client, string token, object payload)
    {
        var requestMessage = new HttpRequestMessage(HttpMethod.Post, "me/shipment/calculate");
        requestMessage.Headers.Authorization = new AuthenticationHeaderValue("Bearer", token);
        requestMessage.Headers.Accept.Add(new MediaTypeWithQualityHeaderValue("application/json"));

        var userAgent = _configuration["MELHOR_ENVIO_USER_AGENT"] ?? "GraficaModernaAPI/1.0 (suporte@graficamoderna.com.br)";
        requestMessage.Headers.TryAddWithoutValidation("User-Agent", userAgent);

        var jsonContent = JsonSerializer.Serialize(payload, _jsonOptions);
        requestMessage.Content = new StringContent(jsonContent, Encoding.UTF8, "application/json");

        return await client.SendAsync(requestMessage);
    }

    private async Task<string> GetAccessTokenAsync()
    {
        var dbToken = await _context.SiteSettings.FindAsync("melhor_envio_access_token");
        if (dbToken != null && !string.IsNullOrWhiteSpace(dbToken.Value))
        {
            return dbToken.Value;
        }
        return _configuration["MELHOR_ENVIO_TOKEN"] ?? "";
    }

    private async Task<string?> RefreshAccessTokenAsync()
    {
        try
        {
            var clientId = _configuration["MELHOR_ENVIO_CLIENT_ID"];
            var clientSecret = _configuration["MELHOR_ENVIO_CLIENT_SECRET"];

            var dbRefreshToken = await _context.SiteSettings.FindAsync("melhor_envio_refresh_token");
            var refreshToken = dbRefreshToken?.Value ?? _configuration["MELHOR_ENVIO_REFRESH_TOKEN"];

            if (string.IsNullOrEmpty(clientId) || string.IsNullOrEmpty(clientSecret) || string.IsNullOrEmpty(refreshToken))
            {
                _logger.LogError("Credenciais para refresh do Melhor Envio incompletas.");
                return null;
            }

            var client = _httpClientFactory.CreateClient("MelhorEnvio");

            var requestContent = new FormUrlEncodedContent(
            [
                new KeyValuePair<string, string>("grant_type", "refresh_token"),
                new KeyValuePair<string, string>("client_id", clientId),
                new KeyValuePair<string, string>("client_secret", clientSecret),
                new KeyValuePair<string, string>("refresh_token", refreshToken)
            ]);

            var response = await client.PostAsync("/oauth/token", requestContent);

            if (!response.IsSuccessStatusCode)
            {
                var error = await response.Content.ReadAsStringAsync();
                _logger.LogCritical("Falha ao renovar token Melhor Envio: {Error}", error);
                return null;
            }

            var responseJson = await response.Content.ReadAsStringAsync();
            using var doc = JsonDocument.Parse(responseJson);
            var root = doc.RootElement;

            if (root.TryGetProperty("access_token", out var accessTokenElem) &&
                root.TryGetProperty("refresh_token", out var refreshTokenElem))
            {
                var newAccessToken = accessTokenElem.GetString();
                var newRefreshToken = refreshTokenElem.GetString();

                await UpdateTokenInDbAsync("melhor_envio_access_token", newAccessToken);
                await UpdateTokenInDbAsync("melhor_envio_refresh_token", newRefreshToken);

                _logger.LogInformation("Token Melhor Envio renovado com sucesso.");
                return newAccessToken;
            }

            return null;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Erro ao executar refresh token do Melhor Envio.");
            return null;
        }
    }

    private async Task UpdateTokenInDbAsync(string key, string? value)
    {
        if (string.IsNullOrEmpty(value)) return;

        var setting = await _context.SiteSettings.FindAsync(key);
        if (setting == null)
        {
            setting = new SiteSetting(key, value);
            _context.SiteSettings.Add(setting);
        }
        else
        {
            setting.UpdateValue(value);
            _context.Entry(setting).State = EntityState.Modified;
        }

        await _context.SaveChangesAsync();
    }

    private class MelhorEnvioResponse
    {
        [JsonPropertyName("name")] public string? Name { get; set; }
        [JsonPropertyName("price")] public string? Price { get; set; }
        [JsonPropertyName("custom_price")] public string? CustomPrice { get; set; }
        [JsonPropertyName("delivery_time")] public int DeliveryTime { get; set; }
        [JsonPropertyName("delivery_range")] public DeliveryRange? DeliveryRange { get; set; }
        [JsonPropertyName("company")] public CompanyObj? Company { get; set; }
        [JsonPropertyName("error")] public string? Error { get; set; }
    }

    private class DeliveryRange
    {
        [JsonPropertyName("max")] public int Max { get; set; }
        [JsonPropertyName("min")] public int Min { get; set; }
    }

    private class CompanyObj
    {
        [JsonPropertyName("name")] public string? Name { get; set; }
        [JsonPropertyName("picture")] public string? Picture { get; set; }
    }
}