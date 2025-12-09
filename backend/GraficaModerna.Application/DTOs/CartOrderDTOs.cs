using System.ComponentModel.DataAnnotations;

namespace GraficaModerna.Application.DTOs;

public record AddToCartDto(
    Guid ProductId,
    [Range(1, int.MaxValue, ErrorMessage = "A quantidade deve ser no mínimo 1.")]
    int Quantity);

public record UpdateCartItemDto(
    [Range(1, int.MaxValue, ErrorMessage = "A quantidade deve ser no mínimo 1.")]
    int Quantity);

public record CartItemDto(
    Guid Id,
    Guid ProductId,
    string ProductName,
    string ProductImage,
    decimal UnitPrice,
    int Quantity,
    decimal TotalPrice,
    decimal Weight,
    int Width,
    int Height,
    int Length
);

public record CartDto(Guid Id, List<CartItemDto> Items, decimal GrandTotal);

// **CORREÇÃO**: DTO público sem dados sensíveis
public record OrderDto(
    Guid Id,
    DateTime OrderDate,
    DateTime? DeliveryDate,
    decimal SubTotal,
    decimal Discount,
    decimal ShippingCost,
    decimal TotalAmount,
    string Status,
    string? TrackingCode,
    string? ReverseLogisticsCode,
    string? ReturnInstructions,
    string? RefundRejectionReason,
    string? RefundRejectionProof,
    string ShippingAddress,
    string CustomerName, // Mantém apenas nome
    // REMOVIDO: CustomerCpf - viola LGPD
    // REMOVIDO: CustomerEmail - expõe PII desnecessariamente
    List<OrderItemDto> Items
);

// **NOVO DTO**: Para uso APENAS no painel administrativo
public record AdminOrderDto(
    Guid Id,
    DateTime OrderDate,
    DateTime? DeliveryDate,
    decimal SubTotal,
    decimal Discount,
    decimal ShippingCost,
    decimal TotalAmount,
    string Status,
    string? TrackingCode,
    string? ReverseLogisticsCode,
    string? ReturnInstructions,
    string? RefundRejectionReason,
    string? RefundRejectionProof,
    string ShippingAddress,
    string CustomerName,
    string CustomerCpfMasked, // Exibe apenas XXX.XXX.XXX-12
    string CustomerEmail,
    string? CustomerIpMasked, // Exibe apenas 192.168.1.XXX
    List<OrderItemDto> Items,
    List<OrderHistoryDto> AuditTrail // Histórico completo
);

// **NOVO DTO**: Para histórico de auditoria
public record OrderHistoryDto(
    string Status,
    string? Message,
    string ChangedBy,
    DateTime Timestamp
);

public record OrderItemDto(string ProductName, int Quantity, decimal UnitPrice, decimal Total);

public record UpdateOrderStatusDto(
    string Status,
    string? TrackingCode,
    string? ReverseLogisticsCode,
    string? ReturnInstructions,
    string? RefundRejectionReason,
    string? RefundRejectionProof
);

// **UTILITÁRIO**: Máscaras para dados sensíveis
public static class DataMaskingExtensions
{
    public static string MaskCpfCnpj(string document)
    {
        if (string.IsNullOrWhiteSpace(document))
            return "N/A";

        var clean = new string(document.Where(char.IsDigit).ToArray());

        if (clean.Length == 11) // CPF
            return $"XXX.XXX.XXX-{clean[^2..]}";
        
        if (clean.Length == 14) // CNPJ
            return $"XX.XXX.XXX/XXXX-{clean[^2..]}";

        return "***";
    }

    public static string MaskEmail(string email)
    {
        if (string.IsNullOrWhiteSpace(email) || !email.Contains('@'))
            return "***@***";

        var parts = email.Split('@');
        var localPart = parts[0];
        var domain = parts[1];

        var maskedLocal = localPart.Length > 2 
            ? $"{localPart[0]}***{localPart[^1]}" 
            : "***";

        return $"{maskedLocal}@{domain}";
    }

    public static string MaskIpAddress(string? ip)
    {
        if (string.IsNullOrWhiteSpace(ip))
            return "N/A";

        var parts = ip.Split('.');
        if (parts.Length == 4)
            return $"{parts[0]}.{parts[1]}.{parts[2]}.XXX";

        // IPv6
        var ipv6Parts = ip.Split(':');
        if (ipv6Parts.Length >= 4)
            return $"{string.Join(":", ipv6Parts.Take(3))}:XXXX";

        return "XXX.XXX.XXX.XXX";
    }
}