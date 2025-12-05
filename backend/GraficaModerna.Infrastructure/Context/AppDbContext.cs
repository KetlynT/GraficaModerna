using GraficaModerna.Domain.Entities;
using Microsoft.AspNetCore.Identity.EntityFrameworkCore;
using Microsoft.EntityFrameworkCore;

namespace GraficaModerna.Infrastructure.Context;

public class AppDbContext : IdentityDbContext<ApplicationUser>
{
    public AppDbContext(DbContextOptions<AppDbContext> options) : base(options) { }

    public DbSet<Product> Products { get; set; }
    public DbSet<Cart> Carts { get; set; }
    public DbSet<CartItem> CartItems { get; set; }
    public DbSet<Order> Orders { get; set; }
    public DbSet<UserAddress> UserAddresses { get; set; }
    public DbSet<Coupon> Coupons { get; set; }
    public DbSet<SiteSetting> SiteSettings { get; set; }
    public DbSet<ContentPage> ContentPages { get; set; }

    // CORREÇÃO: Nova tabela
    public DbSet<CouponUsage> CouponUsages { get; set; }

    protected override void OnModelCreating(ModelBuilder builder)
    {
        base.OnModelCreating(builder);
        builder.Entity<SiteSetting>().HasKey(s => s.Key);
        // CORREÇÃO: Precisão decimal para evitar erros financeiros no Postgres
        builder.Entity<Product>().Property(p => p.Price).HasPrecision(18, 2);
        builder.Entity<Order>().Property(p => p.TotalAmount).HasPrecision(18, 2);
        builder.Entity<Order>().Property(p => p.SubTotal).HasPrecision(18, 2);
        builder.Entity<Order>().Property(p => p.ShippingCost).HasPrecision(18, 2);
        builder.Entity<Order>().Property(p => p.Discount).HasPrecision(18, 2);

        // CORREÇÃO: Configuração do RowVersion para concorrência
        builder.Entity<Product>()
            .Property(p => p.RowVersion)
            .IsRowVersion();
    }
}