using System.Collections.Generic;
using System.Reflection.Emit;
using GraficaModerna.Domain.Entities;
using Microsoft.EntityFrameworkCore;

namespace GraficaModerna.Infrastructure.Context;

public class AppDbContext : DbContext
{
    public AppDbContext(DbContextOptions<AppDbContext> options) : base(options) { }

    public DbSet<Product> Products { get; set; }
    public DbSet<ContentPage> ContentPages { get; set; } // Nova tabela
    public DbSet<SiteSetting> SiteSettings { get; set; } // Nova tabela

    protected override void OnModelCreating(ModelBuilder builder)
    {
        base.OnModelCreating(builder);

        builder.Entity<Product>(e =>
        {
            e.HasKey(p => p.Id);
            e.Property(p => p.Name).IsRequired().HasMaxLength(100);
            e.Property(p => p.Price).HasPrecision(10, 2);
            e.Property(p => p.IsActive).HasDefaultValue(true);
        });

        // Configuração das novas tabelas
        builder.Entity<ContentPage>(e => {
            e.HasKey(p => p.Id);
            e.HasIndex(p => p.Slug).IsUnique(); // Slug deve ser único
        });

        builder.Entity<SiteSetting>(e => {
            e.HasKey(s => s.Key); // A chave é o ID (ex: 'whatsapp')
        });
    }
}