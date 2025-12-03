using GraficaModerna.Domain.Entities;
using GraficaModerna.Infrastructure.Context;
using Microsoft.AspNetCore.Identity;
using Microsoft.Extensions.Configuration;

namespace GraficaModerna.API.Data;

public static class DbSeeder
{
    public static async Task SeedAsync(AppDbContext context, UserManager<ApplicationUser> userManager, IConfiguration config)
    {
        // Limpa e recria o banco (Apenas dev!)
        await context.Database.EnsureDeletedAsync();
        await context.Database.EnsureCreatedAsync();

        // --- 1. USUÁRIOS ---
        var adminEmail = config["AdminSettings:Email"] ?? "admin@graficamoderna.com";
        var adminPassword = config["AdminSettings:Password"] ?? "SenhaSegura!123";

        if (await userManager.FindByEmailAsync(adminEmail) == null)
        {
            var adminUser = new ApplicationUser
            {
                UserName = adminEmail,
                Email = adminEmail,
                FullName = "Administrador Sistema",
                EmailConfirmed = true,
                PhoneNumber = "11999999999"
            };
            await userManager.CreateAsync(adminUser, adminPassword);
        }

        var clientEmail = "cliente@teste.com";
        if (await userManager.FindByEmailAsync(clientEmail) == null)
        {
            var clientUser = new ApplicationUser
            {
                UserName = clientEmail,
                Email = clientEmail,
                FullName = "João da Silva",
                EmailConfirmed = true,
                PhoneNumber = "11988887777"
            };

            var result = await userManager.CreateAsync(clientUser, "Cliente!123");

            if (result.Succeeded)
            {
                // Adiciona endereço de exemplo
                context.UserAddresses.Add(new UserAddress
                {
                    UserId = clientUser.Id,
                    Name = "Casa",
                    ReceiverName = "João da Silva",
                    ZipCode = "01001-000",
                    Street = "Praça da Sé",
                    Number = "100",
                    Complement = "Apto 10",
                    Neighborhood = "Sé",
                    City = "São Paulo",
                    State = "SP",
                    PhoneNumber = "11988887777",
                    IsDefault = true
                });
                await context.SaveChangesAsync();
            }
        }

        // --- 2. CONFIGURAÇÕES DO SITE ---
        if (!context.SiteSettings.Any())
        {
            context.SiteSettings.AddRange(
                new SiteSetting("site_name", "Gráfica A Moderna"),
                new SiteSetting("site_logo", "https://cdn-icons-png.flaticon.com/512/2972/2972461.png"),
                new SiteSetting("hero_bg_url", "https://images.unsplash.com/photo-1562564055-71e051d33c19?q=80&w=2070&auto=format&fit=crop"),
                new SiteSetting("whatsapp_number", "5511999999999"),
                new SiteSetting("whatsapp_display", "(11) 99999-9999"),
                new SiteSetting("contact_email", "contato@graficamoderna.com.br"),
                new SiteSetting("address", "Av. Paulista, 1000 - São Paulo, SP"),
                new SiteSetting("sender_cep", "01310-100"),
                new SiteSetting("hero_badge", "🚀 Qualidade Premium"),
                new SiteSetting("hero_title", "Sua marca impressa com excelência."),
                new SiteSetting("hero_subtitle", "Soluções gráficas completas para empresas e profissionais."),
                new SiteSetting("home_products_title", "Nossos Produtos"),
                new SiteSetting("home_products_subtitle", "Confira nossos produtos mais vendidos.")
            );
            await context.SaveChangesAsync();
        }

        // --- 3. CUPONS ---
        if (!context.Coupons.Any())
        {
            context.Coupons.AddRange(
                new Coupon("BEMVINDO", 10, 365),
                new Coupon("PROMO20", 20, 30),
                new Coupon("FRETEGRATIS", 100, 7)
            );
            await context.SaveChangesAsync();
        }

        // --- 4. PÁGINAS ---
        if (!context.ContentPages.Any())
        {
            context.ContentPages.AddRange(
                new ContentPage("sobre-nos", "Sobre Nós", "<p>Somos líderes de mercado...</p>"),
                new ContentPage("politica", "Política de Privacidade", "<p>Seus dados estão protegidos...</p>")
            );
            await context.SaveChangesAsync();
        }

        // --- 5. PRODUTOS ---
        if (!context.Products.Any())
        {
            context.Products.AddRange(
                new Product("Cartão de Visita Premium", "Couchê 300g, laminação fosca.", 89.90m, "https://images.unsplash.com/photo-1589829085413-56de8ae18c73?q=80&w=800", 1.2m, 9, 5, 5, 500),
                new Product("Panfletos A5 (2500 un)", "Couchê Brilho 115g.", 149.90m, "https://images.unsplash.com/photo-1586075010923-2dd45eeed8bd?q=80&w=800", 4.0m, 21, 15, 15, 200),
                new Product("Adesivos em Vinil (m²)", "Corte especial, à prova d'água.", 65.00m, "https://images.unsplash.com/photo-1529338296731-c4280a44fc4e?q=80&w=800", 0.5m, 30, 30, 5, 100),
                new Product("Banner em Lona", "Acabamento bastão e corda. 80x120cm.", 75.00m, "https://images.unsplash.com/photo-1512314889357-e157c22f938d?q=80&w=800", 1.0m, 120, 10, 10, 50)
            );
            await context.SaveChangesAsync();
        }
    }
}