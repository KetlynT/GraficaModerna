using GraficaModerna.Domain.Entities;
using Microsoft.AspNetCore.Identity;
using Microsoft.Extensions.Logging;
using Microsoft.Extensions.Options;

namespace GraficaModerna.Infrastructure.Security;

public class PepperSettings
{
    public string ActiveVersion { get; set; } = string.Empty;
    public Dictionary<string, string> Peppers { get; set; } = [];
}

public class PepperedPasswordHasher(IOptions<PepperSettings> options, ILogger<PepperedPasswordHasher> logger) : PasswordHasher<ApplicationUser>
{
    private readonly PepperSettings _settings = options.Value;
    private readonly ILogger<PepperedPasswordHasher> _logger = logger;

    public override string HashPassword(ApplicationUser user, string password)
    {
        var activeVersion = _settings.ActiveVersion;

        if (string.IsNullOrEmpty(activeVersion))
        {
            _logger.LogError("CRÍTICO: A configuração 'PepperSettings.ActiveVersion' está vazia.");
            throw new InvalidOperationException("Erro de configuração de segurança: ActiveVersion não definida.");
        }

        if (!_settings.Peppers.TryGetValue(activeVersion, out var pepper))
        {
            _logger.LogError("CRÍTICO: Pepper para a versão '{Version}' não encontrado no .env.", activeVersion);
            throw new InvalidOperationException($"Pepper não encontrado para a versão {activeVersion}.");
        }

        var hash = base.HashPassword(user, password + pepper);

        var prefix = activeVersion.StartsWith('v') ? "$" : "$v";

        return $"{prefix}{activeVersion}${hash}";
    }

    public override PasswordVerificationResult VerifyHashedPassword(ApplicationUser user, string hashedPassword, string providedPassword)
    {
        string version;
        string actualHash;

        if (hashedPassword.StartsWith("$v"))
        {
            var parts = hashedPassword.Split('$', 3, StringSplitOptions.RemoveEmptyEntries);
            if (parts.Length < 2) return PasswordVerificationResult.Failed;
            version = parts[0];
            actualHash = parts[1];
        }
        else
        {
            version = "v1";
            actualHash = hashedPassword;
        }

        if (!_settings.Peppers.TryGetValue(version, out var pepper))
        {
            _logger.LogWarning("Falha no Login: Pepper da versão '{Version}' não encontrado na configuração.", version);
            return PasswordVerificationResult.Failed;
        }

        var result = base.VerifyHashedPassword(user, actualHash, providedPassword + pepper);

        if (result == PasswordVerificationResult.Success)
        {
            if (string.IsNullOrEmpty(_settings.ActiveVersion))
            {
                return PasswordVerificationResult.Success; 
            }

            if (version != _settings.ActiveVersion)
            {
                return PasswordVerificationResult.SuccessRehashNeeded;
            }
        }

        return result;
    }
}