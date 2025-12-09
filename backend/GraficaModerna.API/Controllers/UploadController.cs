using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.RateLimiting;
using SixLabors.ImageSharp;
using SixLabors.ImageSharp.Processing;

namespace GraficaModerna.API.Controllers;

[Route("api/[controller]")]
[ApiController]
[EnableRateLimiting("UploadPolicy")]
public class UploadController : ControllerBase
{
    private const long MaxFileSize = 50 * 1024 * 1024;
    private const int MaxImageDimension = 2048;

    // Assinaturas corrigidas e completas
    private static readonly Dictionary<string, List<byte[]>> _fileSignatures = new()
    {
        { ".jpg", [new byte[] { 0xFF, 0xD8, 0xFF, 0xE0 }, new byte[] { 0xFF, 0xD8, 0xFF, 0xE1 }, new byte[] { 0xFF, 0xD8, 0xFF, 0xE2 }] },
        { ".jpeg", [new byte[] { 0xFF, 0xD8, 0xFF, 0xE0 }, new byte[] { 0xFF, 0xD8, 0xFF, 0xE1 }, new byte[] { 0xFF, 0xD8, 0xFF, 0xE2 }] },
        { ".png", [new byte[] { 0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A }] },
        { ".webp", [new byte[] { 0x52, 0x49, 0x46, 0x46 }] }, // Apenas verifica RIFF, WEBP vem depois
        { ".mp4", [new byte[] { 0x00, 0x00, 0x00, 0x18, 0x66, 0x74, 0x79, 0x70 }, new byte[] { 0x00, 0x00, 0x00, 0x20, 0x66, 0x74, 0x79, 0x70 }] },
        { ".webm", [new byte[] { 0x1A, 0x45, 0xDF, 0xA3 }] },
        { ".mov", [new byte[] { 0x00, 0x00, 0x00, 0x14, 0x66, 0x74, 0x79, 0x70 }] }
    };

    private readonly string[] AllowedExtensions = [".jpg", ".jpeg", ".png", ".webp", ".mp4", ".webm", ".mov"];

    [HttpPost]
    [Authorize(Roles = "Admin")]
    public async Task<IActionResult> Upload(IFormFile file)
    {
        if (file == null || file.Length == 0)
            return BadRequest("Nenhum ficheiro enviado.");

        if (file.Length > MaxFileSize)
            return BadRequest($"O ficheiro excede o tamanho máximo permitido de {MaxFileSize / 1024 / 1024}MB.");

        var ext = Path.GetExtension(file.FileName).ToLowerInvariant();
        if (!AllowedExtensions.Contains(ext))
            return BadRequest("Formato de ficheiro não permitido.");

        if (!_fileSignatures.TryGetValue(ext, out var signatures))
            return BadRequest($"Tipo MIME inválido. Esperado imagem/vídeo, recebido: {file.ContentType}");

        var folderPath = Path.Combine(Directory.GetCurrentDirectory(), "wwwroot", "uploads");
        if (!Directory.Exists(folderPath)) 
            Directory.CreateDirectory(folderPath);

        var fileName = $"{Guid.NewGuid()}{ext}";
        var filePath = Path.Combine(folderPath, fileName);

        try
        {
            using var memoryStream = new MemoryStream();
            await file.CopyToAsync(memoryStream);
            memoryStream.Position = 0;

            // Validação de assinatura melhorada
            var headerBytes = new byte[12];
            var bytesRead = await memoryStream.ReadAsync(headerBytes.AsMemory(0, 12));
            
            bool isValid = false;
            foreach (var signature in signatures)
            {
                if (bytesRead >= signature.Length && 
                    headerBytes.Take(signature.Length).SequenceEqual(signature))
                {
                    isValid = true;
                    break;
                }
            }

            // Validação extra para WEBP (precisa ter WEBP nos bytes 8-11)
            if (ext == ".webp" && isValid)
            {
                if (bytesRead < 12 || 
                    !(headerBytes[8] == 0x57 && headerBytes[9] == 0x45 && 
                      headerBytes[10] == 0x42 && headerBytes[11] == 0x50))
                {
                    isValid = false;
                }
            }

            if (!isValid)
                return BadRequest("O arquivo está corrompido ou a extensão não corresponde ao conteúdo real.");

            memoryStream.Position = 0;

            // Processa imagem ou apenas salva vídeo
            if (ext == ".mp4" || ext == ".webm" || ext == ".mov")
            {
                using var stream = new FileStream(filePath, FileMode.Create);
                await memoryStream.CopyToAsync(stream);
            }
            else
            {
                using var image = await Image.LoadAsync(memoryStream);

                if (image.Width > MaxImageDimension || image.Height > MaxImageDimension)
                {
                    image.Mutate(x => x.Resize(new ResizeOptions
                    {
                        Size = new Size(MaxImageDimension, MaxImageDimension),
                        Mode = ResizeMode.Max
                    }));
                }

                await image.SaveAsync(filePath);
            }
        }
        catch (UnknownImageFormatException)
        {
            return BadRequest("Formato de imagem não suportado ou arquivo corrompido.");
        }
        catch (Exception ex)
        {
            return StatusCode(500, $"Erro ao processar o ficheiro: {ex.Message}");
        }

        var fileUrl = $"{Request.Scheme}://{Request.Host}/uploads/{fileName}";
        return Ok(new { url = fileUrl });
    }
}