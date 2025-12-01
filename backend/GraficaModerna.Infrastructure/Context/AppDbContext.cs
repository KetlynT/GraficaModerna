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
    public DbSet<Cart> Carts { get; set; }
    public DbSet<CartItem> CartItems { get; set; }
    public DbSet<Order> Orders { get; set; }
    public DbSet<OrderItem> OrderItems { get; set; }
    public DbSet<Coupon> Coupons { get; set; }

    protected override void OnModelCreating(ModelBuilder builder)
    {
        base.OnModelCreating(builder);

        // Produto
        builder.Entity<Product>(e =>
        {
            e.HasKey(p => p.Id);
            e.Property(p => p.Name).IsRequired().HasMaxLength(100);
            e.Property(p => p.Price).HasPrecision(10, 2);
            e.Property(p => p.IsActive).HasDefaultValue(true);
            e.Property(p => p.Weight).HasPrecision(10, 3);
        });

        // Configurações do Site (CORREÇÃO AQUI)
        builder.Entity<SiteSetting>(e =>
        {
            // Define 'Key' como a chave primária
            e.HasKey(s => s.Key);
            e.Property(s => s.Key).HasMaxLength(100); // Limita o tamanho da chave
            e.Property(s => s.Value).HasMaxLength(500); // Limita o tamanho do valor (opcional)
        });

        // Cupom
        builder.Entity<Coupon>(e => {
            e.HasKey(c => c.Id);
            e.HasIndex(c => c.Code).IsUnique();
            e.Property(c => c.DiscountPercentage).HasPrecision(5, 2);
        });

        // Pedido
        builder.Entity<Order>(e => {
            e.Property(o => o.SubTotal).HasPrecision(10, 2);
            e.Property(o => o.Discount).HasPrecision(10, 2);
            e.Property(o => o.TotalAmount).HasPrecision(10, 2);
        });

        // Itens do Pedido
        builder.Entity<OrderItem>(e => {
            e.Property(oi => oi.UnitPrice).HasPrecision(10, 2);
        });
    }
}