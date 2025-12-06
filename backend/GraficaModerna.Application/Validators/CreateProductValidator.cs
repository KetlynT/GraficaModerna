using FluentValidation;
using GraficaModerna.Application.DTOs;

namespace GraficaModerna.Application.Validators;

public class CreateProductValidator : AbstractValidator<CreateProductDto>
{
    public CreateProductValidator()
    {
        RuleFor(x => x.Name)
            .NotEmpty().WithMessage("O nome � obrigat�rio.")
            .Length(3, 100).WithMessage("O nome deve ter entre 3 e 100 caracteres.");

        RuleFor(x => x.Price)
            .GreaterThan(0).WithMessage("O pre�o deve ser maior que zero.");

        RuleFor(x => x.Description)
            .MaximumLength(1000).WithMessage("A descri��o � muito longa.");

        RuleFor(x => x.ImageUrl)
            .Must(uri => Uri.TryCreate(uri, UriKind.Absolute, out _))
            .When(x => !string.IsNullOrEmpty(x.ImageUrl))
            .WithMessage("A URL da imagem � inv�lida.");
    }
}
