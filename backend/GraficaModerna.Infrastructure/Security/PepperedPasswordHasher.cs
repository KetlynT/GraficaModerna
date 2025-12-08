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
        // Se o .env não carregar, não deixa o servidor explodir. Lança erro claro.
        if (string.IsNullOrEmpty(activeVersion))
        {
            _logger.LogError("CRÍTICO: A configuração 'PepperSettings.ActiveVersion' está vazia. Verifique se o arquivo .env está na raiz e se as chaves usam '__' (duplo underscore).");
            throw new InvalidOperationException("Erro de configuração de segurança: ActiveVersion não definida.");
        }

        if (!_settings.Peppers.TryGetValue(activeVersion, out var pepper))
        {
            _logger.LogError("CRÍTICO: Pepper para a versão '{Version}' não encontrado no .env ou appsettings.", activeVersion);
            throw new InvalidOperationException($"Pepper não encontrado para a versão {activeVersion}.");
        }

        var hash = base.HashPassword(user, password + pepper);
        return $"$v{activeVersion}${hash}";
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
            version = parts[0];
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

        // PROTEÇÃO ESTRUTURAL 2: Loop de Rehash
        // O erro 500 acontecia aqui. Se a senha está certa, mas a config falhou, 
        // o sistema tentava atualizar a senha e quebrava. Agora protegemos isso.
        if (result == PasswordVerificationResult.Success)
        {
            // Se a configuração ativa sumiu, NÃO tente atualizar a senha. Apenas logue e deixe entrar.
            if (string.IsNullOrEmpty(_settings.ActiveVersion))
            {
                _logger.LogWarning("ALERTA DE SEGURANÇA: Login bem-sucedido, mas 'ActiveVersion' não está configurada. O sistema não pode rotacionar a chave.");
                return PasswordVerificationResult.Success; 
            }

            // Apenas se tudo estiver saudável, solicitamos a atualização do hash
            if (version != _settings.ActiveVersion)
            {
                return PasswordVerificationResult.SuccessRehashNeeded;
            }
        }

        return result;
    }
}