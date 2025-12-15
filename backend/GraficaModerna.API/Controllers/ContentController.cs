using GraficaModerna.Application.Interfaces;
using Microsoft.AspNetCore.Mvc;

namespace GraficaModerna.API.Controllers;

[Route("api/[controller]")]
[ApiController]
public class ContentController(IContentService service) : ControllerBase
{
    private readonly IContentService _service = service;

    [HttpGet("pages")]
    public async Task<IActionResult> GetAllPages()
    {
        var pages = await _service.GetAllPagesAsync();
        return Ok(pages);
    }

    [HttpGet("pages/{slug}")]
    public async Task<IActionResult> GetPage(string slug)
    {
        var page = await _service.GetBySlugAsync(slug);

        if (page == null)
            return NotFound();

        return Ok(page);
    }

    [HttpGet("settings")]
    public async Task<IActionResult> GetSettings()
    {
        var settingsList = await _service.GetSettingsAsync();
        var settingsDict = settingsList.ToDictionary(s => s.Key, s => s.Value);
        return Ok(settingsDict);
    }
}