using AutoMapper;
using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using GraficaModerna.Domain.Entities;
using GraficaModerna.Domain.Interfaces;
using Microsoft.Extensions.Caching.Memory;

namespace GraficaModerna.Application.Services;

public class ProductService : IProductService
{
    private readonly IProductRepository _repository;
    private readonly IMapper _mapper;
    private readonly IMemoryCache _cache;

    public ProductService(IProductRepository repository, IMapper mapper, IMemoryCache cache)
    {
        _repository = repository;
        _mapper = mapper;
        _cache = cache;
    }

    public async Task<PagedResultDto<ProductResponseDto>> GetCatalogAsync(string? search, string? sort, string? order, int page, int pageSize)
    {
        // CORREÇÃO: Reduzimos drasticamente o cache ou o removemos para listagens críticas
        // Para um MVP seguro, vamos desabilitar o cache temporariamente ou usar 15 segundos
        // para garantir que o cliente veja o "Sem Estoque" quase imediatamente.

        var cacheKey = $"catalog_{search}_{sort}_{order}_{page}_{pageSize}";

        return await _cache.GetOrCreateAsync(cacheKey, async entry =>
        {
            // CORREÇÃO: Tempo de vida curto (15s) em vez de 2 min
            entry.AbsoluteExpirationRelativeToNow = TimeSpan.FromSeconds(15);

            var (products, totalCount) = await _repository.GetAllAsync(search, sort, order, page, pageSize);

            return new PagedResultDto<ProductResponseDto>
            {
                Items = _mapper.Map<IEnumerable<ProductResponseDto>>(products),
                TotalItems = totalCount,
                Page = page,
                PageSize = pageSize
            };
        }) ?? new PagedResultDto<ProductResponseDto>();
    }

    public async Task<ProductResponseDto> GetByIdAsync(Guid id)
    {
        // CORREÇÃO: Nunca usar cache para "Detalhes do Produto" para garantir estoque real na tela de compra
        var product = await _repository.GetByIdAsync(id);
        return _mapper.Map<ProductResponseDto>(product);
    }

    public async Task<ProductResponseDto> CreateAsync(CreateProductDto dto)
    {
        var product = _mapper.Map<Product>(dto);
        var created = await _repository.CreateAsync(product);
        return _mapper.Map<ProductResponseDto>(created);
    }

    public async Task UpdateAsync(Guid id, CreateProductDto dto)
    {
        var product = await _repository.GetByIdAsync(id);
        if (product == null) throw new KeyNotFoundException("Produto não encontrado.");

        product.Update(
            dto.Name,
            dto.Description,
            dto.Price,
            dto.ImageUrl,
            dto.Weight,
            dto.Width,
            dto.Height,
            dto.Length,
            dto.StockQuantity
        );

        await _repository.UpdateAsync(product);

        // Em um sistema ideal, chamaríamos _cache.Remove("catalog_...") aqui,
        // mas como as chaves são dinâmicas, o tempo curto de expiração (15s) resolve.
    }

    public async Task DeleteAsync(Guid id)
    {
        var product = await _repository.GetByIdAsync(id);
        if (product == null) throw new KeyNotFoundException("Produto não encontrado.");

        product.Deactivate();
        await _repository.UpdateAsync(product);
    }
}