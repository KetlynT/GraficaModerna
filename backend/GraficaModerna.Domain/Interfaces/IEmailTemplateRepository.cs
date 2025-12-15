using GraficaModerna.Domain.Entities;

namespace GraficaModerna.Domain.Interfaces;

public interface IEmailTemplateRepository
{
    Task<IEnumerable<EmailTemplate>> GetAllAsync();
    Task<EmailTemplate?> GetByIdAsync(Guid id);
    void Update(EmailTemplate template);
}