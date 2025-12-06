namespace GraficaModerna.Domain.Entities;

public class ContentPage
{
    public ContentPage()
    {
    } 

    public ContentPage(string slug, string title, string content)
    {
        Id = Guid.NewGuid();
        Slug = slug;
        Title = title;
        Content = content;
        LastUpdated = DateTime.UtcNow;
    }

    public Guid Id { get; set; }
    public string Slug { get; set; } = string.Empty;
    public string Title { get; set; } = string.Empty;
    public string Content { get; set; } = string.Empty;
    public DateTime LastUpdated { get; set; } = DateTime.UtcNow;
}
