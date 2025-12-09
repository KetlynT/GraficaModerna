using System.Security.Cryptography;
using System.Text;
using Microsoft.Extensions.Configuration;

namespace GraficaModerna.Infrastructure.Security;

public class MetadataSecurityService
{
    private readonly byte[] _encryptionKey;
    private const int NonceSize = 12;
    private const int TagSize = 16;

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

        if (_encryptionKey.Length != 16 && _encryptionKey.Length != 24 && _encryptionKey.Length != 32)
            throw new Exception("METADATA_ENC_KEY deve ter 128, 192 ou 256 bits (16, 24 ou 32 bytes).");
    }

    public string Protect(string plainText)
    {
        if (string.IsNullOrEmpty(plainText))
            throw new ArgumentException("Texto a criptografar não pode ser vazio.");

        var plainBytes = Encoding.UTF8.GetBytes(plainText);
        var nonce = new byte[NonceSize];
        var ciphertext = new byte[plainBytes.Length];
        var tag = new byte[TagSize];

        using (var rng = RandomNumberGenerator.Create())
        {
            rng.GetBytes(nonce);
        }

        using var aesGcm = new AesGcm(_encryptionKey, TagSize);
        
        try
        {
            aesGcm.Encrypt(nonce, plainBytes, ciphertext, tag);
        }
        catch (CryptographicException ex)
        {
            throw new Exception("Falha na criptografia dos metadados.", ex);
        }

        var result = new byte[NonceSize + ciphertext.Length + TagSize];
        Buffer.BlockCopy(nonce, 0, result, 0, NonceSize);
        Buffer.BlockCopy(ciphertext, 0, result, NonceSize, ciphertext.Length);
        Buffer.BlockCopy(tag, 0, result, NonceSize + ciphertext.Length, TagSize);

        return Convert.ToBase64String(result);
    }

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

        if (fullCipher.Length < NonceSize + TagSize)
            throw new System.Security.SecurityException("Dados corrompidos ou inválidos.");

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
            aesGcm.Decrypt(nonce, ciphertext, tag, plainBytes);
        }
        catch (CryptographicException)
        {
            throw new System.Security.SecurityException(
                "Falha na verificação de integridade: dados foram modificados ou corrompidos.");
        }

        return Encoding.UTF8.GetString(plainBytes);
    }

    public static string GenerateNewKey()
    {
        var key = new byte[32];
        using var rng = RandomNumberGenerator.Create();
        rng.GetBytes(key);
        return Convert.ToBase64String(key);
    }
}