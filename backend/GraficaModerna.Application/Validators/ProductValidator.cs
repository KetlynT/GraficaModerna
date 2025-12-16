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

        RuleForEach(x => x.ImageUrls).Must(url =>
        {
            if (string.IsNullOrEmpty(url)) return true;
            if (!Uri.TryCreate(url, UriKind.Absolute, out _)) return false;

            var extension = Path.GetExtension(url).ToLower();
            return extension is ".jpg" or ".jpeg" or ".png" or ".webp" or ".mp4" or ".webm" or ".mov";
        }).WithMessage("Uma ou mais URLs são inválidas. Formatos permitidos: Imagens (jpg, png, webp) e Vídeos (mp4, webm, mov).");
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

        RuleForEach(x => x.ImageUrls).Must(url =>
        {
            if (string.IsNullOrEmpty(url)) return true;
            if (!Uri.TryCreate(url, UriKind.Absolute, out _)) return false;

            var extension = Path.GetExtension(url).ToLower();
            return extension is ".jpg" or ".jpeg" or ".png" or ".webp" or ".mp4" or ".webm" or ".mov";
        }).WithMessage("Uma ou mais URLs são inválidas. Formatos permitidos: Imagens (jpg, png, webp) e Vídeos (mp4, webm, mov).");
    }
}