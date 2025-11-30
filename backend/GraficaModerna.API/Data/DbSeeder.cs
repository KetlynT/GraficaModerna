using GraficaModerna.Domain.Entities;
using GraficaModerna.Infrastructure.Context;
using Microsoft.AspNetCore.Identity;

namespace GraficaModerna.API.Data;

public static class DbSeeder
{
    public static async Task SeedAsync(AppDbContext context, UserManager<ApplicationUser> userManager)
    {
        await context.Database.EnsureCreatedAsync();

        // 1. Seed Usuário Admin
        var adminEmail = "admin@graficamoderna.com";
        var adminUser = await userManager.FindByEmailAsync(adminEmail);

        if (adminUser == null)
        {
            var newAdmin = new ApplicationUser
            {
                UserName = adminEmail,
                Email = adminEmail,
                FullName = "Administrador Sistema",
                EmailConfirmed = true
            };

            // Senha padrão (altere em produção)
            await userManager.CreateAsync(newAdmin, "Admin@123");
        }

        // 2. Seed Settings
        if (!context.SiteSettings.Any())
        {
            context.SiteSettings.AddRange(
                // Contato
                new SiteSetting("whatsapp_number", "5511999999999"),
                new SiteSetting("whatsapp_display", "(11) 99999-9999"),
                new SiteSetting("contact_email", "contato@graficamoderna.com.br"),
                new SiteSetting("address", "Av. Paulista, 1000 - São Paulo, SP"),

                // Home - Hero
                new SiteSetting("hero_badge", "🚀 A melhor gráfica da região"),
                new SiteSetting("hero_title", "Imprima suas ideias com perfeição."),
                new SiteSetting("hero_subtitle", "Cartões de visita, banners e materiais promocionais com entrega rápida e qualidade premium."),

                // Home - Produtos
                new SiteSetting("home_products_title", "Nossos Produtos"),
                new SiteSetting("home_products_subtitle", "Explore as opções disponíveis para o seu negócio e solicite um orçamento.")
            );
        }

        // 3. Seed Pages (Páginas de Conteúdo)
        if (!context.ContentPages.Any())
        {
            context.ContentPages.AddRange(
                new ContentPage("sobre-nos", "Sobre a Gráfica A Moderna",
                    "<h2>Nossa História</h2><p>Desde 2024 entregando qualidade e excelência em impressão para empresas e particulares. Nossa missão é transformar suas ideias em realidade tangível.</p><h3>Nossos Valores</h3><ul><li>Qualidade Premium</li><li>Entrega Rápida</li><li>Sustentabilidade</li></ul>"),
                new ContentPage("politica-privacidade", "Política de Privacidade",
                    "<p>Nós valorizamos seus dados. Esta política descreve como coletamos, usamos e protegemos suas informações pessoais ao utilizar nossos serviços.</p>")
            );
        }

        // 4. Seed Products (DADOS FICTÍCIOS PARA TESTE)
        if (!context.Products.Any())
        {
            context.Products.AddRange(
                new Product(
                    "Cartão de Visita Premium",
                    "Papel couchê 300g com acabamento fosco e verniz localizado. Pacote com 1000 unidades.",
                    89.90m,
                    "https://images.unsplash.com/photo-1589829085413-56de8ae18c73?q=80&w=2000&auto=format&fit=crop"
                ),
                new Product(
                    "Panfletos A5 (1000 un)",
                    "Ideal para divulgação em massa. Papel brilho 115g, impressão colorida frente e verso.",
                    149.00m,
                    "https://images.unsplash.com/photo-1586075010923-2dd45eeed8bd?q=80&w=2000&auto=format&fit=crop"
                ),
                new Product(
                    "Banner em Lona 80x120cm",
                    "Alta resistência para uso externo e interno. Acompanha bastão e corda para pendurar.",
                    75.00m,
                    "https://plus.unsplash.com/premium_photo-1664302152996-2297bbdf2df4?q=80&w=2000&auto=format&fit=crop"
                ),
                new Product(
                    "Adesivos Redondos (Cartela)",
                    "Adesivos em vinil com corte eletrônico preciso. Resistentes à água e sol.",
                    35.00m,
                    "https://images.unsplash.com/photo-1616406432452-07bc59365145?q=80&w=2000&auto=format&fit=crop"
                ),
                new Product(
                    "Caderno Personalizado",
                    "Capa dura com laminação fosca, encadernação wire-o e miolo pautado com sua logo.",
                    45.50m,
                    "https://images.unsplash.com/photo-1544816155-12df9643f363?q=80&w=2000&auto=format&fit=crop"
                ),
                new Product(
                    "Flyer para Eventos",
                    "Design moderno e papel de alta gramatura para divulgar suas festas e eventos corporativos.",
                    120.00m,
                    "https://images.unsplash.com/photo-1563986768609-322da13575f3?q=80&w=1470&auto=format&fit=crop"
                )
            );
        }

        await context.SaveChangesAsync();
    }
}