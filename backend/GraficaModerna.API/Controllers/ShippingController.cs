using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.RateLimiting;

namespace GraficaModerna.API.Controllers;

[Route("api/[controller]")]
[ApiController]
[EnableRateLimiting("ShippingPolicy")]

public class ShippingController(
    IEnumerable<IShippingService> shippingServices,
    IProductService productService,
    ILogger<ShippingController> logger) : ControllerBase
{
    private const int MaxItemsPerCalculation = 50;
    private readonly ILogger<ShippingController> _logger = logger;
    private readonly IProductService _productService = productService;
    private readonly IEnumerable<IShippingService> _shippingServices = shippingServices;

    [HttpPost("calculate")]
    public async Task<ActionResult<List<ShippingOptionDto>>> Calculate([FromBody] CalculateShippingRequest request)
    {
        if (request == null || string.IsNullOrWhiteSpace(request.DestinationCep))
            return BadRequest(new { message = "CEP de destino inv�lido." });

        var cleanCep = new string([.. request.DestinationCep.Where(char.IsDigit)]);

        if (cleanCep.Length != 8)
            return BadRequest(new { message = "CEP inv�lido. Certifique-se de informar os 8 d�gitos num�ricos." });

        if (request.Items == null || request.Items.Count == 0)
            return BadRequest(new { message = "Nenhum item informado para c�lculo." });

        if (request.Items.Count > MaxItemsPerCalculation)
            return BadRequest(new
                { message = $"O c�lculo � limitado a {MaxItemsPerCalculation} itens distintos por vez." });

        List<ShippingItemDto> validatedItems = [];

        foreach (var item in request.Items)
        {
            if (item.Quantity <= 0)
                return BadRequest(new
                    { message = $"Item {item.ProductId} possui quantidade inv�lida ({item.Quantity})." });

            if (item.Quantity > 1000)
                return BadRequest(new
                {
                    message =
                        $"Quantidade excessiva para o item {item.ProductId}. Entre em contato para cota��o de atacado."
                });

            if (item.ProductId != Guid.Empty)
            {
                var product = await _productService.GetByIdAsync(item.ProductId);
                if (product != null)
                    validatedItems.Add(new ShippingItemDto
                    {
                        ProductId = product.Id,
                        Weight = product.Weight,
                        Width = product.Width,
                        Height = product.Height,
                        Length = product.Length,
                        Quantity = item.Quantity
                    });
            }
        }

        if (validatedItems.Count == 0)
            return BadRequest(new { message = "Nenhum produto v�lido encontrado para c�lculo." });

        List<ShippingOptionDto> allOptions = [];

        try
        {
            var tasks = _shippingServices.Select(service => service.CalculateAsync(cleanCep, validatedItems));
            var results = await Task.WhenAll(tasks);

            foreach (var result in results) allOptions.AddRange(result);

            return Ok(allOptions.OrderBy(x => x.Price));
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Erro cr�tico ao calcular frete para CEP {Cep}", cleanCep);
            return StatusCode(500,
                new { message = "N�o foi poss�vel calcular o frete no momento. Tente novamente mais tarde." });
        }
    }

    [HttpGet("product/{productId}/{cep}")]
    public async Task<ActionResult<List<ShippingOptionDto>>> CalculateForProduct(Guid productId, string cep)
    {
        if (string.IsNullOrWhiteSpace(cep))
            return BadRequest(new { message = "CEP inv�lido." });

        var cleanCep = new string([.. cep.Where(char.IsDigit)]);

        if (cleanCep.Length != 8)
            return BadRequest(new { message = "CEP inv�lido. Informe apenas os 8 d�gitos." });

        try
        {
            var product = await _productService.GetByIdAsync(productId);
            if (product == null) return NotFound(new { message = "Produto n�o encontrado." });

            var item = new ShippingItemDto
            {
                ProductId = product.Id,
                Weight = product.Weight,
                Height = product.Height,
                Width = product.Width,
                Length = product.Length,
                Quantity = 1
            };

            List<ShippingOptionDto> allOptions = [];

            var tasks = _shippingServices.Select(s => s.CalculateAsync(cleanCep, [item]));
            var results = await Task.WhenAll(tasks);

            foreach (var result in results) allOptions.AddRange(result);

            return Ok(allOptions.OrderBy(x => x.Price));
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Erro ao calcular frete �nico para Produto {ProductId} e CEP {Cep}", productId,
                cleanCep);
            return StatusCode(500, new { message = "Servi�o de c�lculo de frete indispon�vel temporariamente." });
        }
    }
}
