using AutoMapper;
using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using GraficaModerna.Domain.Entities;
using GraficaModerna.Domain.Interfaces;

namespace GraficaModerna.Application.Services;

public class ProductService : IProductService
{
    private readonly IProductRepository _repository;
    private readonly IMapper _mapper;

    public ProductService(IProductRepository repository, IMapper mapper)
    {
        _repository = repository;
        _mapper = mapper;
    }

    public async Task<IEnumerable<ProductResponseDto>> GetCatalogAsync()
    {
        var products = await _repository.GetAllAsync();
        return _mapper.Map<IEnumerable<ProductResponseDto>>(products);
    }

    public async Task<ProductResponseDto> GetByIdAsync(Guid id)
    {
        var product = await _repository.GetByIdAsync(id);
        return _mapper.Map<ProductResponseDto>(product);
    }

    public async Task<ProductResponseDto> CreateAsync(CreateProductDto dto)
    {
        // O AutoMapper aqui converteria DTO -> Entity, mas como temos validação no construtor
        // é mais seguro instanciar explicitamente para garantir o domínio rico.
        var product = new Product(dto.Name, dto.Description, dto.Price, dto.ImageUrl);

        var created = await _repository.CreateAsync(product);

        return _mapper.Map<ProductResponseDto>(created);
    }
}