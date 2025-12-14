using System.ComponentModel.DataAnnotations;

namespace GraficaModerna.Domain.Entities;

public class EmailTemplate : BaseEntity
{
    [Required]
    public string Key { get; set; } = string.Empty;

    [Required]
    public string Subject { get; set; } = string.Empty;

    [Required]
    public string BodyContent { get; set; } = string.Empty;

    public string Description { get; set; } = string.Empty;
}