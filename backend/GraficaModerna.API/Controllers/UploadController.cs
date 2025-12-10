using GraficaModerna.Domain.Constants;
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

    [HttpPost]
    [Authorize(Roles = Roles.Admin)]
    public async Task<IActionResult> Upload(IFormFile file)
    {
        if (file == null || file.Length == 0)
            return BadRequest("Nenhum ficheiro enviado.");

        if (file.Length > MaxFileSize)
            return BadRequest($"O ficheiro excede o tamanho máximo permitido de {MaxFileSize / 1024 / 1024}MB.");

        var folderPath = Path.Combine(Directory.GetCurrentDirectory(), "wwwroot", "uploads");
        if (!Directory.Exists(folderPath))
            Directory.CreateDirectory(folderPath);

        try
        {
            using var memoryStream = new MemoryStream();
            await file.CopyToAsync(memoryStream);
            memoryStream.Position = 0;

            var detectedExtension = await DetectExtensionFromSignatureAsync(memoryStream);
            if (string.IsNullOrEmpty(detectedExtension))
            {
                return BadRequest("O arquivo parece estar corrompido, falsificado ou tem um formato não permitido.");
            }

            var fileName = $"{Guid.NewGuid()}{detectedExtension}";
            var filePath = Path.Combine(folderPath, fileName);

            memoryStream.Position = 0;

            if (IsVideo(detectedExtension))
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

            var fileUrl = $"{Request.Scheme}://{Request.Host}/uploads/{fileName}";
            return Ok(new { url = fileUrl });
        }
        catch (UnknownImageFormatException)
        {
            return BadRequest("O arquivo não é uma imagem válida ou está corrompido.");
        }
        catch (ImageFormatException)
        {
            return BadRequest("Erro ao decodificar a imagem.");
        }
        catch (Exception)
        {
            return StatusCode(500, "Erro interno ao processar o ficheiro.");
        }
    }

    private static bool IsVideo(string ext) => ext is ".mp4" or ".webm" or ".mov";

    private static async Task<string?> DetectExtensionFromSignatureAsync(MemoryStream stream)
    {
        stream.Position = 0;
        var header = new byte[16];
        var bytesRead = await stream.ReadAsync(header);

        if (bytesRead < 4) return null;

        if (header[0] == 0xFF && header[1] == 0xD8 && header[2] == 0xFF) return ".jpg";

        if (header[0] == 0x89 && header[1] == 0x50 && header[2] == 0x4E && header[3] == 0x47) return ".png";

        if (bytesRead >= 12 &&
            header[0] == 0x52 && header[1] == 0x49 && header[2] == 0x46 && header[3] == 0x46 &&
            header[8] == 0x57 && header[9] == 0x45 && header[10] == 0x42 && header[11] == 0x50) return ".webp";

        if (bytesRead >= 8 &&
            header[4] == 0x66 && header[5] == 0x74 && header[6] == 0x79 && header[7] == 0x70) return ".mp4";

        if (header[0] == 0x1A && header[1] == 0x45 && header[2] == 0xDF && header[3] == 0xA3) return ".webm";

        return null;
    }
}