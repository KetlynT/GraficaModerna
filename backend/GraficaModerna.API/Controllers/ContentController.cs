using GraficaModerna.Domain.Entities;
using GraficaModerna.Infrastructure.Context;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;

namespace GraficaModerna.API.Controllers;

[Route("api/[controller]")]
[ApiController]
public class ContentController : ControllerBase
{
    private readonly AppDbContext _context;

    public ContentController(AppDbContext context)
    {
        _context = context;
    }

    // GET: api/content/pages/sobre-nos
    [HttpGet("pages/{slug}")]
    public async Task<IActionResult> GetPage(string slug)
    {
        var page = await _context.ContentPages.FirstOrDefaultAsync(p => p.Slug == slug);
        if (page == null) return NotFound();
        return Ok(page);
    }

    // GET: api/content/settings
    [HttpGet("settings")]
    public async Task<IActionResult> GetSettings()
    {
        var settings = await _context.SiteSettings.ToListAsync();
        // Transforma lista em dicionário para fácil acesso no front: { "whatsapp": "...", "email": "..." }
        var dictionary = settings.ToDictionary(s => s.Key, s => s.Value);
        return Ok(dictionary);
    }
}