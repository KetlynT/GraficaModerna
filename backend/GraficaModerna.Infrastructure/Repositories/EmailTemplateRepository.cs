using GraficaModerna.Domain.Entities;
using GraficaModerna.Domain.Interfaces;
using GraficaModerna.Infrastructure.Context;
using Microsoft.EntityFrameworkCore;

namespace GraficaModerna.Infrastructure.Repositories;

public class EmailTemplateRepository(AppDbContext context) : IEmailTemplateRepository
{
    private readonly AppDbContext _context = context;

    public async Task<IEnumerable<EmailTemplate>> GetAllAsync()
    {
        return await _context.EmailTemplates
            .AsNoTracking()
            .OrderBy(x => x.Key)
            .ToListAsync();
    }

    public async Task<EmailTemplate?> GetByIdAsync(Guid id)
    {
        return await _context.EmailTemplates.FindAsync(id);
    }

    public void Update(EmailTemplate template)
    {
        _context.EmailTemplates.Update(template);
    }
}