using System.ComponentModel.DataAnnotations;

namespace GraficaModerna.Application.DTOs;

public class EmailTemplateDto
{
    public Guid Id { get; set; }
    public string Key { get; set; } = string.Empty;
    public string Subject { get; set; } = string.Empty;
    public string BodyContent { get; set; } = string.Empty;
    public string Description { get; set; } = string.Empty;
    public DateTime? UpdatedAt { get; set; }
}

public class UpdateEmailTemplateDto
{
    [Required(ErrorMessage = "O assunto é obrigatório")]
    public string Subject { get; set; } = string.Empty;

    [Required(ErrorMessage = "O conteúdo do e-mail é obrigatório")]
    public string BodyContent { get; set; } = string.Empty;
}