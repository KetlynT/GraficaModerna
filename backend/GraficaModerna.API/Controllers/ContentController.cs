using Ganss.Xss;
using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.RateLimiting;



namespace GraficaModerna.API.Controllers;

[Route("api/[controller]")]
[ApiController]
public class ContentController(IContentService service, IHtmlSanitizer sanitizer) : ControllerBase
{
    private readonly IHtmlSanitizer _sanitizer = sanitizer;
    private readonly IContentService _service = service; 

    [HttpGet("{slug}")]
    public async Task<IActionResult> GetPage(string slug)
    {
        var page = await _service.GetBySlugAsync(slug);

        if (page == null)
            return NotFound();





        if (!string.IsNullOrEmpty(page.Content)) page.Content = _sanitizer.Sanitize(page.Content);

        return Ok(page);
    }


    [HttpPost]
    [Authorize(Roles = "Admin")]
    [EnableRateLimiting("AdminPolicy")]
    public async Task<IActionResult> Create([FromBody] CreateContentDto dto)
    {

        dto.Content = _sanitizer.Sanitize(dto.Content);

        var result = await _service.CreateAsync(dto);
        return Ok(result);
    }
}
