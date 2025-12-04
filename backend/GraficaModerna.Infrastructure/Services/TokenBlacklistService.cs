using GraficaModerna.Application.Interfaces;
using Microsoft.Extensions.Caching.Distributed;

namespace GraficaModerna.Infrastructure.Services;

public class TokenBlacklistService : ITokenBlacklistService
{
    private readonly IDistributedCache _cache;

    public TokenBlacklistService(IDistributedCache cache)
    {
        _cache = cache;
    }

    public async Task BlacklistTokenAsync(string token, DateTime expiryDate)
    {
        var timeToLive = expiryDate - DateTime.UtcNow;
        if (timeToLive <= TimeSpan.Zero) return;

        var options = new DistributedCacheEntryOptions
        {
            AbsoluteExpirationRelativeToNow = timeToLive
        };
        await _cache.SetStringAsync(token, "revoked", options);
    }

    public async Task<bool> IsTokenBlacklistedAsync(string token)
    {
        // Se retornar valor, significa que a chave existe e o token está revogado
        var value = await _cache.GetStringAsync(token);
        return value != null;
    }
}