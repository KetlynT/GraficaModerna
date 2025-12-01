using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using GraficaModerna.Infrastructure.Context;
using Microsoft.EntityFrameworkCore;
using System.Globalization;
using System.Xml.Linq;

namespace GraficaModerna.Infrastructure.Services;

public class CorreiosShippingService : IShippingService
{
    private readonly AppDbContext _context;
    private readonly IHttpClientFactory _httpClientFactory;

    // Códigos de Serviço Varejo (Sem Contrato)
    private const string COD_PAC = "04510";
    private const string COD_SEDEX = "04014";

    public CorreiosShippingService(AppDbContext context, IHttpClientFactory httpClientFactory)
    {
        _context = context;
        _httpClientFactory = httpClientFactory;
    }

    public async Task<List<ShippingOptionDto>> CalculateAsync(string destinationCep, List<ShippingItemDto> items)
    {
        // 1. Configuração de Origem e Destino
        var originCepSetting = await _context.SiteSettings.FirstOrDefaultAsync(s => s.Key == "sender_cep");
        string originCep = originCepSetting?.Value?.Replace("-", "") ?? "01001000";
        string destCep = destinationCep.Replace("-", "");

        // 2. Cálculo do Pacote Virtual
        decimal totalWeight = Math.Max(items.Sum(i => i.Weight * i.Quantity), 0.3m);
        int totalHeight = Math.Max(items.Sum(i => i.Height * i.Quantity), 2);
        int maxWidth = Math.Max(items.Max(i => i.Width), 11);
        int maxLength = Math.Max(items.Max(i => i.Length), 16);

        // Ajustes de limites mínimos/máximos aceitos pela API
        if (totalWeight > 30m) totalWeight = 30m; // Limite padrão correios
        if (maxLength > 100) maxLength = 100;

        // 3. Chamadas em Paralelo (Uma para PAC, outra para SEDEX)
        // Isso evita que a API trave ao tentar calcular os dois juntos
        var taskPac = CallCorreiosApiAsync(COD_PAC, originCep, destCep, totalWeight, maxLength, totalHeight, maxWidth);
        var taskSedex = CallCorreiosApiAsync(COD_SEDEX, originCep, destCep, totalWeight, maxLength, totalHeight, maxWidth);

        await Task.WhenAll(taskPac, taskSedex);

        var options = new List<ShippingOptionDto>();
        if (taskPac.Result != null) options.Add(taskPac.Result);
        if (taskSedex.Result != null) options.Add(taskSedex.Result);

        return options.OrderBy(x => x.Price).ToList();
    }

    private async Task<ShippingOptionDto?> CallCorreiosApiAsync(string serviceCode, string origin, string dest, decimal weight, int length, int height, int width)
    {
        // URL Oficial (HTTPS)
        var baseUrl = "https://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx";

        var query = $"?nCdEmpresa=&sDsSenha=" +
                    $"&sCepOrigem={origin}" +
                    $"&sCepDestino={dest}" +
                    $"&nVlPeso={weight.ToString(CultureInfo.InvariantCulture)}" +
                    $"&nCdFormato=1" +
                    $"&nVlComprimento={length}" +
                    $"&nVlAltura={height}" +
                    $"&nVlLargura={width}" +
                    $"&sCdMaoPropria=n" +
                    $"&nVlValorDeclarado=0" +
                    $"&sCdAvisoRecebimento=n" +
                    $"&nCdServico={serviceCode}" +
                    $"&nVlDiametro=0" +
                    $"&StrRetorno=xml";

        try
        {
            var client = _httpClientFactory.CreateClient();
            client.Timeout = TimeSpan.FromSeconds(20); // 20s é suficiente para uma requisição única

            var response = await client.GetAsync(baseUrl + query);

            if (!response.IsSuccessStatusCode) return null;

            using var stream = await response.Content.ReadAsStreamAsync();
            var doc = XDocument.Load(stream);
            var servico = doc.Descendants("cServico").FirstOrDefault();

            if (servico == null) return null;

            var erro = servico.Element("Erro")?.Value;
            var msgErro = servico.Element("MsgErro")?.Value;

            // Filtra erros reais (0 = Sucesso, 010 = Aviso não impeditivo)
            if (erro != null && erro != "0" && erro != "00" && erro != "010")
            {
                // Se quiser debugar erros específicos:
                // Console.WriteLine($"Erro Correios [{serviceCode}]: {msgErro}");
                return null;
            }

            var valorStr = servico.Element("Valor")?.Value;
            var prazoStr = servico.Element("PrazoEntrega")?.Value;

            if (decimal.TryParse(valorStr, NumberStyles.Number, new CultureInfo("pt-BR"), out decimal price))
            {
                // API retorna preço zero em caso de erro não explícito
                if (price == 0) return null;

                return new ShippingOptionDto
                {
                    Name = serviceCode == COD_SEDEX ? "SEDEX" : "PAC",
                    Provider = "Correios",
                    Price = price,
                    DeliveryDays = int.Parse(prazoStr ?? "0")
                };
            }
        }
        catch
        {
            // Ignora falhas de conexão para não travar o fluxo inteiro
            return null;
        }

        return null;
    }
}