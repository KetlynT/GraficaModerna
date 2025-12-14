using GraficaModerna.Domain.Entities;
using GraficaModerna.Domain.Interfaces;
using GraficaModerna.Infrastructure.Context;
using Microsoft.EntityFrameworkCore;

namespace GraficaModerna.Infrastructure.Repositories;

public class EmailTemplateRepository(AppDbContext context) : IEmailTemplateRepository
{
    public async Task<IEnumerable<EmailTemplate>> GetAllAsync()
    {
        return await context.EmailTemplates.AsNoTracking().OrderBy(x => x.Key).ToListAsync();
    }

    public async Task<EmailTemplate?> GetByIdAsync(int id)
    {
        return await context.EmailTemplates.FindAsync(id);
    }

    public void Update(EmailTemplate template)
    {
        context.EmailTemplates.Update(template);
    }
}