using System.ComponentModel.DataAnnotations;

namespace GraficaModerna.Application.DTOs;

public class CreateContentDto
{
    [Required(ErrorMessage = "O t�tulo � obrigat�rio.")]
    public string Title { get; set; } = string.Empty;

    [Required(ErrorMessage = "O slug � obrigat�rio.")]
    public string Slug { get; set; } = string.Empty;

    [Required(ErrorMessage = "O conte�do � obrigat�rio.")]
    public string Content { get; set; } = string.Empty;
}

public class UpdateContentDto : CreateContentDto
{

}
