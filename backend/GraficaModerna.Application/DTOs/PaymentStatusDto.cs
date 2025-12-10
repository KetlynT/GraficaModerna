using GraficaModerna.Domain.Enums;

namespace GraficaModerna.Application.DTOs;

public record PaymentStatusDto(Guid Id, OrderStatus Status, decimal TotalAmount);