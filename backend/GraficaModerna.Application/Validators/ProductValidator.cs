using FluentValidation;
using GraficaModerna.Application.DTOs;

namespace GraficaModerna.Application.Validators;

public class ProductValidator : AbstractValidator<CreateProductDto>
{
    public ProductValidator()
    {
        RuleFor(x => x.Name)
            .NotEmpty().WithMessage("O nome é obrigatório.")
            .Length(3, 100).WithMessage("O nome deve ter entre 3 e 100 caracteres.");

        RuleFor(x => x.Price)
            .GreaterThan(0).WithMessage("O preço deve ser maior que zero.");

        RuleFor(x => x.Description)
            .MaximumLength(1000).WithMessage("A descrição é muito longa.");

        RuleForEach(x => x.ImageUrls)
            .Must(uri => Uri.TryCreate(uri, UriKind.Absolute, out _))
            .When(x => x.ImageUrls != null && x.ImageUrls.Count > 0)
            .WithMessage("Uma das URLs da imagem é inválida.");
    }
}

public class UpdateProductValidator : AbstractValidator<UpdateProductDto>
{
    public UpdateProductValidator()
    {
        RuleFor(x => x.Name)
            .NotEmpty().WithMessage("O nome é obrigatório.")
            .Length(3, 100).WithMessage("O nome deve ter entre 3 e 100 caracteres.");

        RuleFor(x => x.Price)
            .GreaterThan(0).WithMessage("O preço deve ser maior que zero.");

        RuleFor(x => x.Description)
            .MaximumLength(1000).WithMessage("A descrição é muito longa.");

        RuleForEach(x => x.ImageUrls)
            .Must(uri => Uri.TryCreate(uri, UriKind.Absolute, out _))
            .When(x => x.ImageUrls != null && x.ImageUrls.Count > 0)
            .WithMessage("Uma das URLs da imagem é inválida.");
    }
}