using System.Security.Cryptography;
using System.Text;
using Microsoft.Extensions.Configuration;

namespace GraficaModerna.Infrastructure.Security;

public class MetadataSecurityService
{
    private readonly byte[] _encryptionKey;
    private readonly byte[] _hmacKey;

    public MetadataSecurityService(IConfiguration configuration)
    {
        var encKeyString = Environment.GetEnvironmentVariable("METADATA_ENC_KEY")
                           ?? configuration["Security:MetadataEncryptionKey"];
        var hmacKeyString = Environment.GetEnvironmentVariable("METADATA_HMAC_KEY")
                            ?? configuration["Security:MetadataHmacKey"];

        if (string.IsNullOrEmpty(encKeyString) || string.IsNullOrEmpty(hmacKeyString))
            throw new Exception("Chaves de segurança de metadados não configuradas.");

        _encryptionKey = Convert.FromBase64String(encKeyString);
        _hmacKey = Convert.FromBase64String(hmacKeyString);
    }

    public (string encryptedData, string signature) Protect(string plainText)
    {
        byte[] encryptedBytes;
        byte[] iv;

        using (var aes = Aes.Create())
        {
            aes.Key = _encryptionKey;
            aes.GenerateIV();
            iv = aes.IV;

            using var encryptor = aes.CreateEncryptor(aes.Key, iv);
            using var ms = new MemoryStream();
            using (var cs = new CryptoStream(ms, encryptor, CryptoStreamMode.Write))
            using (var sw = new StreamWriter(cs))
            {
                sw.Write(plainText);
            }
            encryptedBytes = ms.ToArray();
        }

        // Combina IV + CipherText para garantir que o IV faça parte da assinatura
        var combinedData = new byte[iv.Length + encryptedBytes.Length];
        Array.Copy(iv, 0, combinedData, 0, iv.Length);
        Array.Copy(encryptedBytes, 0, combinedData, iv.Length, encryptedBytes.Length);

        string encryptedData = Convert.ToBase64String(combinedData);
        string signature;

        // Calcula o HMAC sobre os bytes combinados (IV + CipherText)
        using (var hmac = new HMACSHA256(_hmacKey))
        {
            var hashBytes = hmac.ComputeHash(combinedData);
            signature = Convert.ToBase64String(hashBytes);
        }

        return (encryptedData, signature);
    }

    public string Unprotect(string encryptedData, string signature)
    {
        var fullCipher = Convert.FromBase64String(encryptedData);

        // Verifica a assinatura antes de qualquer operação de decifragem
        using (var hmac = new HMACSHA256(_hmacKey))
        {
            var computedHash = hmac.ComputeHash(fullCipher);
            var computedSignature = Convert.ToBase64String(computedHash);

            if (computedSignature != signature)
                throw new System.Security.SecurityException("Assinatura dos metadados inválida ou IV modificado.");
        }

        using var aes = Aes.Create();
        aes.Key = _encryptionKey;

        // Extrai o IV (primeiros 16 bytes)
        var iv = new byte[16];
        if (fullCipher.Length < 16)
            throw new System.Security.SecurityException("Dados corrompidos ou inválidos.");

        Array.Copy(fullCipher, 0, iv, 0, iv.Length);
        aes.IV = iv;

        // Decifra o restante (CipherText)
        using var ms = new MemoryStream(fullCipher, 16, fullCipher.Length - 16);
        using var decryptor = aes.CreateDecryptor(aes.Key, aes.IV);
        using var cs = new CryptoStream(ms, decryptor, CryptoStreamMode.Read);
        using var sr = new StreamReader(cs);

        return sr.ReadToEnd();
    }
}