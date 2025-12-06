using System.Security.Cryptography;
using System.Text;
using GraficaModerna.Application.Interfaces;
using Microsoft.Extensions.Caching.Distributed;

namespace GraficaModerna.Infrastructure.Services;

public class TokenBlacklistService(IDistributedCache cache) : ITokenBlacklistService
{
    private readonly IDistributedCache _cache = cache;

    public async Task BlacklistTokenAsync(string token, DateTime expiryDate)
    {
        var timeToLive = expiryDate - DateTime.UtcNow;
        if (timeToLive <= TimeSpan.Zero) return;

        var options = new DistributedCacheEntryOptions
        {
            AbsoluteExpirationRelativeToNow = timeToLive
        };

        var key = HashToken(token);
        await _cache.SetStringAsync(key, "revoked", options);
    }

    public async Task<bool> IsTokenBlacklistedAsync(string token)
    {
        var key = HashToken(token);
        var value = await _cache.GetStringAsync(key);
        return value != null;
    }

    private static string HashToken(string token)
    {
        var bytes = Encoding.UTF8.GetBytes(token);
        var hash = SHA256.HashData(bytes);

        var sb = new StringBuilder(hash.Length * 2);
        foreach (var b in hash) sb.Append(b.ToString("x2"));
        return sb.ToString();
    }
}
