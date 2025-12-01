using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using Microsoft.AspNetCore.Mvc;

namespace GraficaModerna.API.Controllers;

[Route("api/[controller]")]
[ApiController]
public class ShippingController : ControllerBase
{
    // AQUI ESTÁ A MÁGICA DA ESCALABILIDADE:
    // Injetamos uma COLEÇÃO de serviços. Hoje só tem Correios, amanhã pode ter 10.
    private readonly IEnumerable<IShippingService> _shippingServices;
    private readonly IProductService _productService;

    public ShippingController(IEnumerable<IShippingService> shippingServices, IProductService productService)
    {
        _shippingServices = shippingServices;
        _productService = productService;
    }

    [HttpPost("calculate")]
    public async Task<ActionResult<List<ShippingOptionDto>>> Calculate([FromBody] CalculateShippingRequest request)
    {
        if (string.IsNullOrEmpty(request.DestinationCep) || request.DestinationCep.Length < 8)
            return BadRequest("CEP de destino inválido.");

        if (!request.Items.Any())
            return BadRequest("Nenhum item informado para cálculo.");

        var allOptions = new List<ShippingOptionDto>();

        // Dispara o cálculo para TODOS os provedores registrados em paralelo
        var tasks = _shippingServices.Select(service => service.CalculateAsync(request.DestinationCep, request.Items));

        try
        {
            var results = await Task.WhenAll(tasks);

            // Junta todas as listas de opções em uma só
            foreach (var result in results)
            {
                allOptions.AddRange(result);
            }

            // Ordena pelo menor preço para o cliente
            return Ok(allOptions.OrderBy(x => x.Price));
        }
        catch (Exception ex)
        {
            return StatusCode(500, $"Erro ao calcular frete: {ex.Message}");
        }
    }

    [HttpGet("product/{productId}/{cep}")]
    public async Task<ActionResult<List<ShippingOptionDto>>> CalculateForProduct(Guid productId, string cep)
    {
        var product = await _productService.GetByIdAsync(productId);
        if (product == null) return NotFound("Produto não encontrado.");

        var item = new ShippingItemDto
        {
            Weight = product.Weight,
            Height = product.Height,
            Width = product.Width,
            Length = product.Length,
            Quantity = 1
        };

        var allOptions = new List<ShippingOptionDto>();
        var tasks = _shippingServices.Select(s => s.CalculateAsync(cep, new List<ShippingItemDto> { item }));

        try
        {
            var results = await Task.WhenAll(tasks);
            foreach (var result in results) allOptions.AddRange(result);

            return Ok(allOptions.OrderBy(x => x.Price));
        }
        catch (Exception ex)
        {
            return StatusCode(500, ex.Message);
        }
    }
}