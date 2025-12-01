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

    public async Task<PagedResultDto<ProductResponseDto>> GetCatalogAsync(string? search, string? sort, string? order, int page, int pageSize)
    {
        var (products, totalCount) = await _repository.GetAllAsync(search, sort, order, page, pageSize);

        return new PagedResultDto<ProductResponseDto>
        {
            Items = _mapper.Map<IEnumerable<ProductResponseDto>>(products),
            TotalItems = totalCount,
            Page = page,
            PageSize = pageSize
        };
    }

    public async Task<ProductResponseDto> GetByIdAsync(Guid id)
    {
        var product = await _repository.GetByIdAsync(id);
        return _mapper.Map<ProductResponseDto>(product);
    }

    public async Task<ProductResponseDto> CreateAsync(CreateProductDto dto)
    {
        // O AutoMapper vai usar o construtor do Product automaticamente
        var product = _mapper.Map<Product>(dto);
        var created = await _repository.CreateAsync(product);
        return _mapper.Map<ProductResponseDto>(created);
    }

    public async Task UpdateAsync(Guid id, CreateProductDto dto)
    {
        var product = await _repository.GetByIdAsync(id);
        if (product == null) throw new KeyNotFoundException("Produto não encontrado.");

        // CORREÇÃO: Passando os novos parâmetros de frete para o método Update
        product.Update(
            dto.Name, 
            dto.Description, 
            dto.Price, 
            dto.ImageUrl,
            dto.Weight,
            dto.Width,
            dto.Height,
            dto.Length
        );

        await _repository.UpdateAsync(product);
    }

    public async Task DeleteAsync(Guid id)
    {
        var product = await _repository.GetByIdAsync(id);
        if (product == null) throw new KeyNotFoundException("Produto não encontrado.");

        product.Deactivate();
        await _repository.UpdateAsync(product);
    }
}