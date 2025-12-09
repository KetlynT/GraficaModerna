using System.Security.Cryptography;
using System.Text;
using Microsoft.Extensions.Configuration;

namespace GraficaModerna.Infrastructure.Security;

/// <summary>
/// Serviço de criptografia autenticada usando AES-GCM para metadados do Stripe.
/// AES-GCM fornece confidencialidade E integridade em uma única operação.
/// </summary>
public class MetadataSecurityService
{
    private readonly byte[] _encryptionKey;
    private const int NonceSize = 12; // 96 bits recomendado para AES-GCM
    private const int TagSize = 16; // 128 bits para autenticação

    public MetadataSecurityService(IConfiguration configuration)
    {
        var encKeyString = Environment.GetEnvironmentVariable("METADATA_ENC_KEY")
                           ?? configuration["Security:MetadataEncryptionKey"];

        if (string.IsNullOrEmpty(encKeyString))
            throw new Exception("METADATA_ENC_KEY não configurada.");

        try
        {
            _encryptionKey = Convert.FromBase64String(encKeyString);
        }
        catch (FormatException)
        {
            throw new Exception("METADATA_ENC_KEY deve estar em formato Base64 válido.");
        }

        // AES-GCM requer chave de 128, 192 ou 256 bits
        if (_encryptionKey.Length != 16 && _encryptionKey.Length != 24 && _encryptionKey.Length != 32)
            throw new Exception("METADATA_ENC_KEY deve ter 128, 192 ou 256 bits (16, 24 ou 32 bytes).");
    }

    /// <summary>
    /// Criptografa dados com AES-GCM (Autenticação Embutida).
    /// Retorna: Base64(Nonce || Ciphertext || Tag)
    /// </summary>
    public string Protect(string plainText)
    {
        if (string.IsNullOrEmpty(plainText))
            throw new ArgumentException("Texto a criptografar não pode ser vazio.");

        var plainBytes = Encoding.UTF8.GetBytes(plainText);
        var nonce = new byte[NonceSize];
        var ciphertext = new byte[plainBytes.Length];
        var tag = new byte[TagSize];

        // Gera nonce aleatório (crucial: nunca reusar nonce com mesma chave!)
        using (var rng = RandomNumberGenerator.Create())
        {
            rng.GetBytes(nonce);
        }

        using var aesGcm = new AesGcm(_encryptionKey, TagSize);
        
        try
        {
            // Encrypt-and-Authenticate em uma única operação
            aesGcm.Encrypt(nonce, plainBytes, ciphertext, tag);
        }
        catch (CryptographicException ex)
        {
            throw new Exception("Falha na criptografia dos metadados.", ex);
        }

        // Combina: Nonce + Ciphertext + Tag
        var result = new byte[NonceSize + ciphertext.Length + TagSize];
        Buffer.BlockCopy(nonce, 0, result, 0, NonceSize);
        Buffer.BlockCopy(ciphertext, 0, result, NonceSize, ciphertext.Length);
        Buffer.BlockCopy(tag, 0, result, NonceSize + ciphertext.Length, TagSize);

        return Convert.ToBase64String(result);
    }

    /// <summary>
    /// Descriptografa e valida integridade em uma única operação.
    /// Lança SecurityException se a autenticação falhar.
    /// </summary>
    public string Unprotect(string encryptedData)
    {
        if (string.IsNullOrEmpty(encryptedData))
            throw new ArgumentException("Dados criptografados não podem ser vazios.");

        byte[] fullCipher;
        try
        {
            fullCipher = Convert.FromBase64String(encryptedData);
        }
        catch (FormatException)
        {
            throw new System.Security.SecurityException("Formato de dados inválido.");
        }

        // Valida tamanho mínimo
        if (fullCipher.Length < NonceSize + TagSize)
            throw new System.Security.SecurityException("Dados corrompidos ou inválidos.");

        // Extrai componentes
        var nonce = new byte[NonceSize];
        var tag = new byte[TagSize];
        var ciphertext = new byte[fullCipher.Length - NonceSize - TagSize];

        Buffer.BlockCopy(fullCipher, 0, nonce, 0, NonceSize);
        Buffer.BlockCopy(fullCipher, NonceSize, ciphertext, 0, ciphertext.Length);
        Buffer.BlockCopy(fullCipher, NonceSize + ciphertext.Length, tag, 0, TagSize);

        var plainBytes = new byte[ciphertext.Length];

        using var aesGcm = new AesGcm(_encryptionKey, TagSize);

        try
        {
            // Decrypt-and-Verify em uma única operação atômica
            aesGcm.Decrypt(nonce, ciphertext, tag, plainBytes);
        }
        catch (CryptographicException)
        {
            // Falha na autenticação = dados foram modificados
            throw new System.Security.SecurityException(
                "Falha na verificação de integridade: dados foram modificados ou corrompidos.");
        }

        return Encoding.UTF8.GetString(plainBytes);
    }

    /// <summary>
    /// Gera uma chave segura de 256 bits para usar no .env
    /// Executar uma vez e salvar no METADATA_ENC_KEY
    /// </summary>
    public static string GenerateNewKey()
    {
        var key = new byte[32]; // 256 bits
        using var rng = RandomNumberGenerator.Create();
        rng.GetBytes(key);
        return Convert.ToBase64String(key);
    }
}