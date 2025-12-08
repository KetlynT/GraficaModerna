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
    // Aumentado para 50MB para suportar vídeos curtos
    private const long MaxFileSize = 50 * 1024 * 1024;
    private const int MaxImageDimension = 2048;

    private static readonly Dictionary<string, string> _validMimeTypes = new()
    {
        { ".jpg", "image/jpeg" },
        { ".jpeg", "image/jpeg" },
        { ".png", "image/png" },
        { ".webp", "image/webp" },
        // Novos tipos de vídeo
        { ".mp4", "video/mp4" },
        { ".webm", "video/webm" },
        { ".mov", "video/quicktime" }
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

        if (!_validMimeTypes.TryGetValue(ext, out var expectedMime) ||
            !file.ContentType.Equals(expectedMime, StringComparison.CurrentCultureIgnoreCase))
            // Nota: Browsers às vezes enviam mime types genéricos, cuidado com essa validação estrita em produção
            return BadRequest($"Tipo MIME inválido. Esperado: {expectedMime}, Recebido: {file.ContentType}");

        var folderPath = Path.Combine(Directory.GetCurrentDirectory(), "wwwroot", "uploads"); // Mudado para 'uploads' para ser genérico
        if (!Directory.Exists(folderPath)) Directory.CreateDirectory(folderPath);

        var fileName = $"{Guid.NewGuid()}{ext}";
        var filePath = Path.Combine(folderPath, fileName);

        try
        {
            // Se for imagem, redimensiona. Se for vídeo, apenas salva.
            if (ext == ".mp4" || ext == ".webm" || ext == ".mov")
            {
                using var stream = new FileStream(filePath, FileMode.Create);
                await file.CopyToAsync(stream);
            }
            else
            {
                using var stream = file.OpenReadStream();
                using var image = await Image.LoadAsync(stream);

                var format = image.Metadata.DecodedImageFormat;
                if (format == null || !_validMimeTypes[ext].Contains(format.DefaultMimeType))
                {
                    return BadRequest("O conteúdo da imagem não corresponde à extensão declarada.");
                }

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
        catch (ImageFormatException)
        {
            return BadRequest("O arquivo não é uma imagem válida ou está corrompido.");
        }
        catch (Exception ex)
        {
            return StatusCode(500, $"Erro interno ao processar o ficheiro: {ex.Message}");
        }

        // Ajuste a URL conforme sua pasta estática (images ou uploads)
        var fileUrl = $"{Request.Scheme}://{Request.Host}/uploads/{fileName}";
        return Ok(new { url = fileUrl });
    }
}