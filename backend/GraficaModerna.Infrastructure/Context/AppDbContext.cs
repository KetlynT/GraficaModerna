using GraficaModerna.Domain.Entities;
using Microsoft.EntityFrameworkCore;

namespace GraficaModerna.Infrastructure.Context;

public class AppDbContext : DbContext
{
    public AppDbContext(DbContextOptions<AppDbContext> options) : base(options) { }

    public DbSet<Product> Products { get; set; }

    protected override void OnModelCreating(ModelBuilder builder)
    {
        base.OnModelCreating(builder);

        builder.Entity<Product>(e =>
        {
            e.HasKey(p => p.Id);
            e.Property(p => p.Name).IsRequired().HasMaxLength(100);
            e.Property(p => p.Price).HasPrecision(10, 2); // Decimal(10,2) para moeda
            e.Property(p => p.IsActive).HasDefaultValue(true);
        });
    }
}