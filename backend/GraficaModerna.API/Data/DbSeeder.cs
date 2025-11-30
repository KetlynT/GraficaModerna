using GraficaModerna.Domain.Entities;
using GraficaModerna.Infrastructure.Context;

namespace GraficaModerna.API.Data;

public static class DbSeeder
{
    public static async Task SeedAsync(AppDbContext context)
    {
        // Garante que o banco existe
        await context.Database.EnsureCreatedAsync();

        // Seed Settings
        if (!context.SiteSettings.Any())
        {
            context.SiteSettings.AddRange(
                new SiteSetting("whatsapp_number", "5511999999999"),
                new SiteSetting("whatsapp_display", "(11) 99999-9999"),
                new SiteSetting("contact_email", "contato@graficamoderna.com.br"),
                new SiteSetting("address", "Av. Paulista, 1000 - São Paulo, SP")
            );
        }

        // Seed Pages
        if (!context.ContentPages.Any())
        {
            context.ContentPages.AddRange(
                new ContentPage("sobre-nos", "Sobre a Gráfica A Moderna",
                    "<p>Fundada em 2024, a <strong>Gráfica A Moderna</strong> nasceu com a missão de revolucionar o mercado de impressos.</p><p>Nossa tecnologia de ponta garante cores vibrantes e acabamento impecável.</p>"),

                new ContentPage("politica-privacidade", "Política de Privacidade",
                    "<p>Nós valorizamos seus dados. Esta política descreve como coletamos e protegemos suas informações...</p>")
            );
        }

        await context.SaveChangesAsync();
    }
}