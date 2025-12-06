using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.RateLimiting;
using SixLabors.ImageSharp;


namespace GraficaModerna.API.Controllers;

[Route("api/[controller]")]
[ApiController]
[EnableRateLimiting("UploadPolicy")]
public class UploadController : ControllerBase
{
    private const long MaxFileSize = 5 * 1024 * 1024;

    private static readonly Dictionary<string, string> _validMimeTypes = new()
    {
        { ".jpg", "image/jpeg" },
        { ".jpeg", "image/jpeg" },
        { ".png", "image/png" },
        { ".webp", "image/webp" }
    };

    private readonly string[] AllowedExtensions = [".jpg", ".jpeg", ".png", ".webp"];

    [HttpPost]
    [Authorize(Roles = "Admin")]
    public async Task<IActionResult> Upload(IFormFile file)
    {
        if (file == null || file.Length == 0)
            return BadRequest("Nenhum ficheiro enviado.");

        if (file.Length > MaxFileSize)
            return BadRequest("O ficheiro excede o tamanho m�ximo permitido de 5MB.");

        var ext = Path.GetExtension(file.FileName).ToLowerInvariant();
        if (!AllowedExtensions.Contains(ext))
            return BadRequest("Formato de ficheiro n�o permitido.");


        if (!_validMimeTypes.TryGetValue(ext, out var expectedMime) ||
            !file.ContentType.Equals(expectedMime, StringComparison.CurrentCultureIgnoreCase))
            return BadRequest($"Tipo MIME inv�lido. Esperado: {expectedMime}, Recebido: {file.ContentType}");

        var folderPath = Path.Combine(Directory.GetCurrentDirectory(), "wwwroot", "images");
        if (!Directory.Exists(folderPath)) Directory.CreateDirectory(folderPath);

        var fileName = $"{Guid.NewGuid()}{ext}";
        var filePath = Path.Combine(folderPath, fileName);

        try
        {
            using var stream = file.OpenReadStream();



            try
            {

                var format = await Image.DetectFormatAsync(stream);

                if (format == null)
                    return BadRequest("O arquivo n�o � uma imagem reconhecida.");


                if (!_validMimeTypes[ext].Contains(format.DefaultMimeType))
                    return BadRequest(
                        $"Conte�do do arquivo ({format.DefaultMimeType}) n�o corresponde � extens�o ({ext}).");




            }
            catch (Exception)
            {
                return BadRequest("O arquivo est� corrompido ou n�o � uma imagem v�lida.");
            }

            stream.Position = 0;
            using var fileStream = new FileStream(filePath, FileMode.Create);
            await stream.CopyToAsync(fileStream);
        }
        catch (Exception ex)
        {
            Console.WriteLine($"Erro cr�tico no upload: {ex}");
            return StatusCode(500, "Erro interno ao processar o ficheiro.");
        }

        var imageUrl = $"{Request.Scheme}://{Request.Host}/images/{fileName}";
        return Ok(new { url = imageUrl });
    }
}
