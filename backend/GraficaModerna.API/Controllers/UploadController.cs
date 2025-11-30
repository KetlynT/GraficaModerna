using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace GraficaModerna.API.Controllers;

[Route("api/[controller]")]
[ApiController]
public class UploadController : ControllerBase
{
    // Limite de segurança: 5MB
    private const long MaxFileSize = 5 * 1024 * 1024;
    private readonly string[] AllowedExtensions = { ".jpg", ".jpeg", ".png", ".webp" };

    [HttpPost]
    [Authorize(Roles = "Admin")] // SEGURANÇA: Apenas Admin pode fazer upload
    public async Task<IActionResult> Upload(IFormFile file)
    {
        if (file == null || file.Length == 0)
            return BadRequest("Nenhum arquivo enviado.");

        // Validação de Tamanho
        if (file.Length > MaxFileSize)
            return BadRequest("O arquivo excede o tamanho máximo permitido de 5MB.");

        // Validação de Extensão
        var ext = Path.GetExtension(file.FileName).ToLower();
        if (!AllowedExtensions.Contains(ext))
            return BadRequest("Formato de arquivo não permitido. Apenas imagens (.jpg, .png, .webp) são aceitas.");

        var folderPath = Path.Combine(Directory.GetCurrentDirectory(), "wwwroot", "images");
        if (!Directory.Exists(folderPath))
            Directory.CreateDirectory(folderPath);

        // Gera nome aleatório para evitar sobrescrita e nomes maliciosos
        var fileName = $"{Guid.NewGuid()}{ext}";
        var filePath = Path.Combine(folderPath, fileName);

        using (var stream = new FileStream(filePath, FileMode.Create))
        {
            await file.CopyToAsync(stream);
        }

        var imageUrl = $"{Request.Scheme}://{Request.Host}/images/{fileName}";
        return Ok(new { url = imageUrl });
    }
}