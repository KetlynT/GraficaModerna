using GraficaModerna.Domain.Entities;
using Microsoft.AspNetCore.Identity.EntityFrameworkCore;
using Microsoft.EntityFrameworkCore;

namespace GraficaModerna.Infrastructure.Context;

public class AppDbContext : IdentityDbContext<ApplicationUser>
{
    public AppDbContext(DbContextOptions<AppDbContext> options) : base(options) { }

    public DbSet<Product> Products { get; set; }
    public DbSet<ContentPage> ContentPages { get; set; }
    public DbSet<SiteSetting> SiteSettings { get; set; }

    protected override void OnModelCreating(ModelBuilder builder)
    {
        base.OnModelCreating(builder);

        builder.Entity<Product>(e =>
        {
            e.HasKey(p => p.Id);
            e.Property(p => p.Name).IsRequired().HasMaxLength(100);
            e.Property(p => p.Price).HasPrecision(10, 2);
            e.Property(p => p.IsActive).HasDefaultValue(true);

            // Novas propriedades de Frete
            e.Property(p => p.Weight).HasPrecision(10, 3); // 3 casas decimais para Kg
            e.Property(p => p.Width).IsRequired();
            e.Property(p => p.Height).IsRequired();
            e.Property(p => p.Length).IsRequired();
        });

        builder.Entity<ContentPage>(e => {
            e.HasKey(p => p.Id);
            e.HasIndex(p => p.Slug).IsUnique();
        });

        builder.Entity<SiteSetting>(e => {
            e.HasKey(s => s.Key);
        });
    }
}