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

        // PROTEÇÃO ESTRUTURAL 1: Validação de Configuração
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

        // CORREÇÃO DO BUG: Verifica se a versão já começa com 'v' para não duplicar
        // Se activeVersion for "v1", prefixo será "$". Resultado: "$v1$..."
        // Se activeVersion for "1", prefixo será "$v". Resultado: "$v1$..."
        var prefix = activeVersion.StartsWith("v") ? "$" : "$v";
        
        return $"{prefix}{activeVersion}${hash}";
    }

    public override PasswordVerificationResult VerifyHashedPassword(ApplicationUser user, string hashedPassword, string providedPassword)
    {
        string version;
        string actualHash;

        // Lógica de Parsing do Hash ($v1$HASH...)
        if (hashedPassword.StartsWith("$v"))
        {
            var parts = hashedPassword.Split('$', 3, StringSplitOptions.RemoveEmptyEntries);
            if (parts.Length < 2) return PasswordVerificationResult.Failed;
            version = parts[0]; // Extrai "v1"
            actualHash = parts[1];
        }
        else
        {
            // Suporte para versões antigas (Legado)
            version = "v1";
            actualHash = hashedPassword;
        }

        // Tenta buscar a chave (Pepper) histórica
        if (!_settings.Peppers.TryGetValue(version, out var pepper))
        {
            _logger.LogWarning("Falha no Login: Pepper da versão '{Version}' não encontrado na configuração.", version);
            return PasswordVerificationResult.Failed;
        }

        // Verifica a senha
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