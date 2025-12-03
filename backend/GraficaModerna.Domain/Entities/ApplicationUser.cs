using Microsoft.AspNetCore.Identity;

namespace GraficaModerna.Domain.Entities;

public class ApplicationUser : IdentityUser
{
    public string FullName { get; set; } = string.Empty;

    // Relação 1:N com endereços
    public List<UserAddress> Addresses { get; set; } = new();
}