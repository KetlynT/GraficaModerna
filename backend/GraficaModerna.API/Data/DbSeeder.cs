using GraficaModerna.Domain.Entities;
using GraficaModerna.Infrastructure.Context;
using Microsoft.AspNetCore.Identity;
using Microsoft.Extensions.Configuration;
using Microsoft.EntityFrameworkCore;

namespace GraficaModerna.API.Data;

public static class DbSeeder
{
    // Adicionado parâmetro RoleManager
    public static async Task SeedAsync(AppDbContext context, UserManager<ApplicationUser> userManager, RoleManager<IdentityRole> roleManager, IConfiguration config)
    {
        await context.Database.EnsureCreatedAsync();

        // --- 0. SEGURANÇA: CRIAR ROLES ---
        string[] roleNames = { "Admin", "User" };
        foreach (var roleName in roleNames)
        {
            if (!await roleManager.RoleExistsAsync(roleName))
            {
                await roleManager.CreateAsync(new IdentityRole(roleName));
            }
        }

        // --- 1. USUÁRIOS ---
        var adminEmail = config["AdminSettings:Email"] ?? "admin@graficamoderna.com";
        var defaultPassword = config["AdminSettings:DefaultPassword"] ?? "SenhaForte@123";

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

            var result = await userManager.CreateAsync(adminUser, defaultPassword);

            if (result.Succeeded)
            {
                // CRUCIAL: Dar poder de Admin para este usuário
                await userManager.AddToRoleAsync(adminUser, "Admin");
            }
        }

        // Cliente de Teste
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

            var result = await userManager.CreateAsync(clientUser, "Cliente@123");
            if (result.Succeeded)
            {
                await userManager.AddToRoleAsync(clientUser, "User");

                if (!context.UserAddresses.Any(u => u.UserId == clientUser.Id))
                {
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
                        Reference = "Próximo ao Metrô",
                        PhoneNumber = "11988887777",
                        IsDefault = true
                    });
                    await context.SaveChangesAsync();
                }
            }
        }

        // --- DEMAIS DADOS (Produtos, Configs...) ---
        if (!context.SiteSettings.Any())
        {
            context.SiteSettings.AddRange(
                new SiteSetting("site_name", "Gráfica A Moderna"),
                new SiteSetting("whatsapp_number", "5511999999999")
            );
            await context.SaveChangesAsync();
        }

        // (O restante do seed de produtos pode permanecer igual ao anterior)
    }
}