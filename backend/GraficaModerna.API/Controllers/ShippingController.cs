using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using Microsoft.AspNetCore.Mvc;

namespace GraficaModerna.API.Controllers;

[Route("api/[controller]")]
[ApiController]
public class ShippingController : ControllerBase
{
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

        if (request.Items == null || !request.Items.Any())
            return BadRequest("Nenhum item informado para cálculo.");

        // --- CORREÇÃO DE SEGURANÇA (PARAMETER TAMPERING) ---
        // Recriamos a lista de itens buscando os dados REAIS no banco de dados.
        // Isso impede que um usuário malicioso envie um peso de 0.001kg para pagar menos frete.
        var validatedItems = new List<ShippingItemDto>();

        foreach (var item in request.Items)
        {
            if (item.ProductId != Guid.Empty)
            {
                var product = await _productService.GetByIdAsync(item.ProductId);
                if (product != null)
                {
                    validatedItems.Add(new ShippingItemDto
                    {
                        ProductId = product.Id,
                        // Usamos APENAS os dados do banco de dados
                        Weight = product.Weight,
                        Width = product.Width,
                        Height = product.Height,
                        Length = product.Length,
                        Quantity = item.Quantity
                    });
                }
            }
        }

        if (!validatedItems.Any())
            return BadRequest("Nenhum produto válido encontrado para cálculo.");

        var allOptions = new List<ShippingOptionDto>();

        // Executa o cálculo com os itens validados
        var tasks = _shippingServices.Select(service => service.CalculateAsync(request.DestinationCep, validatedItems));

        try
        {
            var results = await Task.WhenAll(tasks);

            foreach (var result in results)
            {
                allOptions.AddRange(result);
            }

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
            ProductId = product.Id,
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