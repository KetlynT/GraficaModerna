using FluentValidation;
using GraficaModerna.Domain.Entities;

namespace GraficaModerna.Application.Validators;

public class ContentPageValidator : AbstractValidator<ContentPage>
{
    public ContentPageValidator()
    {
        RuleFor(x => x.Title)
            .NotEmpty().WithMessage("O t�tulo � obrigat�rio.")
            .MaximumLength(200).WithMessage("O t�tulo deve ter no m�ximo 200 caracteres.");

        RuleFor(x => x.Content)
            .NotEmpty().WithMessage("O conte�do da p�gina n�o pode ser vazio.");

        RuleFor(x => x.Slug)
            .NotEmpty().WithMessage("O slug � obrigat�rio.")
            .Matches("^[a-z0-9-]+$")
            .WithMessage("O slug deve conter apenas letras min�sculas, n�meros e h�fens (ex: minha-pagina).");
    }
}
