using AutoMapper;
using GraficaModerna.Application.DTOs;
using GraficaModerna.Domain.Entities;

namespace GraficaModerna.Application.Mappings;

public class DomainMappingProfile : Profile
{
    public DomainMappingProfile()
    {
        CreateMap<Product, ProductResponseDto>();
        CreateMap<CreateProductDto, Product>();
    }
}