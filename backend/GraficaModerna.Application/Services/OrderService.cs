using System.ComponentModel;
using GraficaModerna.Application.Constants;
using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using GraficaModerna.Domain.Constants;
using GraficaModerna.Domain.Entities;
using GraficaModerna.Domain.Enums;
using GraficaModerna.Domain.Extensions;
using GraficaModerna.Domain.Interfaces;
using GraficaModerna.Domain.Models;
using Microsoft.AspNetCore.Http;
using Microsoft.AspNetCore.Identity;
using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.Logging;

namespace GraficaModerna.Application.Services;

public class OrderService(
    IUnitOfWork unitOfWork,
    IEmailService emailService,
    ITemplateService templateService,
    UserManager<ApplicationUser> userManager,
    IHttpContextAccessor httpContextAccessor,
    IEnumerable<IShippingService> shippingServices,
    IPaymentService paymentService,
    IConfiguration configuration,
    ILogger<OrderService> logger) : IOrderService
{
    private readonly IUnitOfWork _uow = unitOfWork;
    private readonly IEmailService _emailService = emailService;
    private readonly ITemplateService _templateService = templateService;
    private readonly IHttpContextAccessor _httpContextAccessor = httpContextAccessor;
    private readonly IPaymentService _paymentService = paymentService;
    private readonly IEnumerable<IShippingService> _shippingServices = shippingServices;
    private readonly UserManager<ApplicationUser> _userManager = userManager;
    private readonly IConfiguration _configuration = configuration;
    private readonly ILogger<OrderService> _logger = logger;

    public async Task<OrderDto> CreateOrderFromCartAsync(string userId, CreateAddressDto addressDto, string? couponCode,
        string shippingMethod)
    {
        var cart = await _uow.Carts.GetByUserIdAsync(userId);

        if (cart == null || cart.Items.Count == 0)
            throw new InvalidOperationException("Carrinho vazio.");

        if (cart.Items.Any(i => i.Quantity <= 0))
            throw new InvalidOperationException("O carrinho contém itens com quantidades inválidas.");

        var shippingItems = cart.Items.Select(i => new ShippingItemDto
        {
            ProductId = i.ProductId,
            Weight = i.Product!.Weight,
            Width = i.Product.Width,
            Height = i.Product.Height,
            Length = i.Product.Length,
            Quantity = i.Quantity
        }).ToList();

        var shippingTasks = _shippingServices.Select(s => s.CalculateAsync(addressDto.ZipCode, shippingItems));
        var shippingResults = await Task.WhenAll(shippingTasks);
        var allOptions = shippingResults.SelectMany(x => x).ToList();

        var selectedOption = allOptions.FirstOrDefault(o =>
                                 o.Name.Trim().Equals(shippingMethod.Trim(),
                                     StringComparison.InvariantCultureIgnoreCase)) ??
                             throw new ArgumentException("Método de envio inválido ou indisponível.");
        var verifiedShippingCost = selectedOption.Price;

        using var transaction = await _uow.BeginTransactionAsync();
        try
        {
            decimal subTotal = 0;
            var orderItems = new List<OrderItem>();

            foreach (var item in cart.Items)
            {
                if (item.Product == null) continue;

                if (item.Product.StockQuantity < item.Quantity)
                    throw new InvalidOperationException($"Estoque insuficiente para o produto {item.Product.Name}");

                subTotal += item.Quantity * item.Product.Price;

                orderItems.Add(new OrderItem
                {
                    ProductId = item.ProductId,
                    ProductName = item.Product.Name,
                    Quantity = item.Quantity,
                    UnitPrice = item.Product.Price
                });
            }

            decimal discount = 0;
            if (!string.IsNullOrWhiteSpace(couponCode))
            {
                var coupon = await _uow.Coupons.GetByCodeAsync(couponCode);

                if (coupon != null && coupon.IsValid())
                {
                    var alreadyUsed = await _uow.Coupons.IsUsageLimitReachedAsync(userId, coupon.Code);
                    if (alreadyUsed) throw new InvalidOperationException("Cupom já utilizado.");

                    discount = subTotal * (coupon.DiscountPercentage / 100m);
                }
            }

            var totalAmount = subTotal - discount + verifiedShippingCost;

            if (totalAmount < Order.MinOrderAmount)
                throw new InvalidOperationException($"O valor total do pedido deve ser no mínimo {Order.MinOrderAmount:C}.");

            if (totalAmount > Order.MaxOrderAmount)
                throw new InvalidOperationException($"O valor do pedido excede o limite de segurança de {Order.MaxOrderAmount:C}.");

            var formattedAddress =
                $"{addressDto.Street}, {addressDto.Number} - {addressDto.Complement} - {addressDto.Neighborhood}, " +
                $"{addressDto.City}/{addressDto.State} (Ref: {addressDto.Reference}) - A/C: {addressDto.ReceiverName} - Tel: {addressDto.PhoneNumber}";

            var order = new Order
            {
                UserId = userId,
                ShippingAddress = formattedAddress,
                ShippingZipCode = addressDto.ZipCode,
                ShippingCost = verifiedShippingCost,
                ShippingMethod = selectedOption.Name,
                Status = OrderStatus.Pendente,
                OrderDate = DateTime.UtcNow,
                SubTotal = subTotal,
                Discount = discount,
                TotalAmount = totalAmount,
                AppliedCoupon = couponCode?.ToUpper(),
                Items = orderItems,
                CustomerIp = GetIpAddress(),
                UserAgent = GetUserAgent()
            };

            order.History.Add(new OrderHistory
            {
                Status = OrderStatus.Pendente.GetDescription(),
                Message = "Pedido criado via Checkout",
                ChangedBy = userId,
                Timestamp = DateTime.UtcNow,
                IpAddress = GetIpAddress(),
                UserAgent = GetUserAgent()
            });

            await _uow.Orders.AddAsync(order);
            await _uow.CommitAsync();

            if (discount > 0 && couponCode != null)
            {
                await _uow.Coupons.RecordUsageAsync(new CouponUsage
                {
                    UserId = userId,
                    CouponCode = couponCode.ToUpper(),
                    OrderId = order.Id,
                    UsedAt = DateTime.UtcNow
                });
            }

            await _uow.Carts.ClearCartAsync(cart.Id);
            await _uow.CommitAsync();

            await transaction.CommitAsync();

            _ = SendOrderReceivedEmailAsync(userId, order);

            return MapToDto(order, "Atenção: A reserva dos itens e o débito no estoque só ocorrem após a confirmação do pagamento.");
        }
        catch
        {
            await transaction.RollbackAsync();
            throw;
        }
    }

    public async Task<PagedResultDto<OrderDto>> GetUserOrdersAsync(string userId, int page, int pageSize)
    {
        var result = await _uow.Orders.GetByUserIdAsync(userId, page, pageSize);

        var dtos = result.Items.Select(o => MapToDto(o));

        return new PagedResultDto<OrderDto>
        {
            Items = dtos,
            TotalItems = result.TotalItems,
            Page = page,
            PageSize = pageSize
        };
    }

    public async Task<PagedResultDto<AdminOrderDto>> GetAllOrdersAsync(int page, int pageSize)
    {
        var result = await _uow.Orders.GetAllAsync(page, pageSize);

        var dtos = result.Items.Select(MapToAdminDto);

        return new PagedResultDto<AdminOrderDto>
        {
            Items = dtos,
            TotalItems = result.TotalItems,
            Page = page,
            PageSize = pageSize
        };
    }

    public async Task ConfirmPaymentViaWebhookAsync(Guid orderId, string transactionId, long amountPaidInCents)
    {
        var existingOrder = await _uow.Orders.GetByTransactionIdAsync(transactionId);
        if (existingOrder != null && existingOrder.Status == OrderStatus.Pago)
        {
            _logger.LogWarning("[Webhook] Tentativa de reprocessamento detectada. Transaction: {TransactionId}", transactionId);
            return;
        }

        using var transaction = await _uow.BeginTransactionAsync();

        try
        {
            var order = await _uow.Orders.GetByIdAsync(orderId);

            if (order == null)
            {
                _logger.LogError("[Webhook] Pedido não encontrado. OrderId: {OrderId}", orderId);
                return;
            }

            var expectedAmount = (long)(order.TotalAmount * 100);
            if (expectedAmount != amountPaidInCents)
            {
                await NotifySecurityTeamAsync(order, transactionId, expectedAmount, amountPaidInCents);
                throw new Exception($"Divergência de valores de segurança. Esperado: {expectedAmount}, Recebido: {amountPaidInCents}");
            }

            if (order.Status == OrderStatus.Pago)
            {
                await transaction.RollbackAsync();
                return;
            }

            var productIds = order.Items.Select(i => i.ProductId).ToList();
            var products = await _uow.Products.GetByIdsAsync(productIds);

            var outOfStockItems = new List<string>();

            foreach (var item in order.Items)
            {
                var product = products.FirstOrDefault(p => p.Id == item.ProductId);

                if (product == null || product.StockQuantity < item.Quantity)
                {
                    outOfStockItems.Add(item.ProductName);
                }
            }

            if (outOfStockItems.Count > 0)
            {
                _logger.LogWarning("[Webhook] Estoque insuficiente para o pedido {OrderId}. Itens: {Items}. Iniciando estorno.",
                    orderId, string.Join(", ", outOfStockItems));

                try
                {
                    await _paymentService.RefundPaymentAsync(transactionId);

                    order.StripePaymentIntentId = transactionId;
                    order.Status = OrderStatus.Cancelado;

                    AddAuditLog(order, OrderStatus.Cancelado,
                        $"⚠️ Cancelamento Automático: Estoque insuficiente ({string.Join(", ", outOfStockItems)}). Valor estornado.",
                        "SYSTEM-STOCK-CHECK");

                    await _uow.CommitAsync();
                    await transaction.CommitAsync();

                    if (order.User != null && !string.IsNullOrEmpty(order.User.Email))
                    {
                        var emailModel = new
                        {
                            Name = order.User.FullName,
                            OrderId = order.Id,
                            Items = outOfStockItems,
                            Year = DateTime.Now.Year
                        };

                        var (subject, body) = await _templateService.RenderEmailAsync("OrderCancelledOutOfStock", emailModel);
                        await _emailService.SendEmailAsync(order.User.Email, subject, body);
                    }

                    return;
                }
                catch (Exception ex)
                {
                    _logger.LogCritical(ex, "[Webhook] ERRO CRÍTICO no estorno automático. Order {OrderId}", orderId);
                    await transaction.RollbackAsync();
                    throw;
                }
            }

            foreach (var item in order.Items)
            {
                var product = products.FirstOrDefault(p => p.Id == item.ProductId);
                if (product != null)
                {
                    product.DebitStock(item.Quantity);
                    await _uow.Products.UpdateAsync(product);
                }
            }

            order.StripePaymentIntentId = transactionId;
            order.Status = OrderStatus.Pago;

            AddAuditLog(order, OrderStatus.Pago,
                $"✅ Pagamento confirmado e Estoque debitado. Transaction: {transactionId}",
                "STRIPE-WEBHOOK");

            await _uow.CommitAsync();
            await transaction.CommitAsync();

            _logger.LogInformation("[Webhook] Pagamento confirmado. OrderId: {OrderId}", orderId);
            _ = SendOrderUpdateEmailAsync(order.UserId, order, "Pago");
        }
        catch (Exception ex)
        {
            await transaction.RollbackAsync();
            _logger.LogError(ex, "[Webhook] Erro ao processar transação do pedido {OrderId}", orderId);
            throw;
        }
    }

    public async Task UpdateAdminOrderAsync(Guid orderId, UpdateOrderStatusDto dto)
    {
        var user = _httpContextAccessor.HttpContext?.User;
        var adminUserId = _userManager.GetUserId(user!) ?? "AdminUnknown";

        if (user == null || !user.IsInRole(Roles.Admin))
            throw new UnauthorizedAccessException("Apenas administradores podem alterar pedidos.");

        using var transaction = await _uow.BeginTransactionAsync();
        try
        {
            var order = await _uow.Orders.GetByIdAsync(orderId) ?? throw new KeyNotFoundException("Pedido não encontrado");

            var oldStatus = order.Status;
            var newStatusEnum = ParseStatus(dto.Status);
            var auditMessage = $"Status alterado manualmente para {dto.Status}";

            if (newStatusEnum == OrderStatus.AguardandoDevolucao)
            {
                if (!string.IsNullOrEmpty(dto.ReverseLogisticsCode))
                    order.ReverseLogisticsCode = dto.ReverseLogisticsCode;

                order.ReturnInstructions = !string.IsNullOrEmpty(dto.ReturnInstructions)
                    ? dto.ReturnInstructions
                    : "Instruções padrão de devolução...";

                auditMessage += ". Instruções geradas.";
            }

            if (newStatusEnum == OrderStatus.ReembolsoReprovado ||
                newStatusEnum == OrderStatus.Reembolsado ||
                newStatusEnum == OrderStatus.ReembolsadoParcialmente ||
                newStatusEnum == OrderStatus.Cancelado)
            {
                if (!string.IsNullOrEmpty(dto.RefundRejectionReason))
                    order.RefundRejectionReason = dto.RefundRejectionReason;

                if (!string.IsNullOrEmpty(dto.RefundRejectionProof))
                    order.RefundRejectionProof = dto.RefundRejectionProof;

                if (newStatusEnum == OrderStatus.ReembolsoReprovado)
                    auditMessage += ". Justificativa e provas anexadas.";
            }

            if ((newStatusEnum == OrderStatus.Reembolsado ||
                 newStatusEnum == OrderStatus.ReembolsadoParcialmente ||
                 newStatusEnum == OrderStatus.Cancelado)
                && order.Status != OrderStatus.Reembolsado
                && order.Status != OrderStatus.ReembolsadoParcialmente
                && order.Status != OrderStatus.Cancelado
                && !string.IsNullOrEmpty(order.StripePaymentIntentId))
            {
                try
                {
                    decimal amountToRefund = 0;

                    if (dto.RefundAmount.HasValue)
                        amountToRefund = dto.RefundAmount.Value;
                    else
                        amountToRefund = order.RefundRequestedAmount ?? order.TotalAmount;

                    if (amountToRefund > order.TotalAmount)
                        throw new InvalidOperationException($"O valor do reembolso ({amountToRefund:C}) não pode ser maior que o total do pedido.");

                    if (order.RefundType == "Parcial" && order.RefundRequestedAmount.HasValue)
                    {
                        if (amountToRefund > order.RefundRequestedAmount.Value)
                            throw new InvalidOperationException($"O valor do reembolso ({amountToRefund:C}) excede o valor calculado dos itens solicitados ({order.RefundRequestedAmount.Value:C}).");
                    }

                    await _paymentService.RefundPaymentAsync(order.StripePaymentIntentId, amountToRefund);

                    auditMessage += $". Reembolso de R$ {amountToRefund:N2} processado no Stripe.";

                    if (newStatusEnum == OrderStatus.Reembolsado && amountToRefund < order.TotalAmount)
                    {
                        newStatusEnum = OrderStatus.ReembolsadoParcialmente;
                        dto = dto with { Status = newStatusEnum.GetDescription() };
                    }
                }
                catch (Exception ex)
                {
                    throw new Exception($"Erro no reembolso Stripe: {ex.Message}");
                }
            }

            if (newStatusEnum == OrderStatus.Entregue && order.Status != OrderStatus.Entregue)
                order.DeliveryDate = DateTime.UtcNow;

            if (!string.IsNullOrEmpty(dto.TrackingCode))
            {
                order.TrackingCode = dto.TrackingCode;
                auditMessage += $" (Rastreio: {dto.TrackingCode})";
            }

            AddAuditLog(order, newStatusEnum, auditMessage, $"Admin:{adminUserId}");

            await _uow.Orders.UpdateAsync(order);
            await _uow.CommitAsync();
            await transaction.CommitAsync();

            if (oldStatus != newStatusEnum)
            {
                _ = SendOrderUpdateEmailAsync(order.UserId, order, dto.Status);
            }
        }
        catch
        {
            await transaction.RollbackAsync();
            throw;
        }
    }

    public async Task RequestRefundAsync(Guid orderId, string userId, RequestRefundDto dto)
    {
        var order = await GetUserOrderOrFail(orderId, userId);

        if (order.Status != OrderStatus.Entregue && order.Status != OrderStatus.Pago)
            throw new InvalidOperationException("Status do pedido não permite solicitação de reembolso.");

        if (!string.IsNullOrEmpty(order.RefundType))
            throw new InvalidOperationException("Já existe uma solicitação de reembolso para este pedido.");

        decimal calculatedRefundAmount = 0;

        if (dto.RefundType == "Parcial")
        {
            if (dto.Items == null || dto.Items.Count == 0)
                throw new ArgumentException("Nenhum item selecionado para reembolso parcial.");

            decimal discountRatio = order.SubTotal > 0 ? order.Discount / order.SubTotal : 0;

            foreach (var itemRequest in dto.Items)
            {
                var orderItem = order.Items.FirstOrDefault(i => i.ProductId == itemRequest.ProductId)
                    ?? throw new ArgumentException($"Produto {itemRequest.ProductId} não pertence a este pedido.");

                if (itemRequest.Quantity > orderItem.Quantity || itemRequest.Quantity <= 0)
                    throw new ArgumentException($"Quantidade inválida para o produto {orderItem.ProductName}.");

                orderItem.RefundQuantity = itemRequest.Quantity;

                decimal effectiveUnitPrice = orderItem.UnitPrice * (1 - discountRatio);
                calculatedRefundAmount += effectiveUnitPrice * itemRequest.Quantity;
            }

            calculatedRefundAmount = Math.Round(calculatedRefundAmount, 2);

            order.RefundType = "Parcial";
            order.RefundRequestedAmount = calculatedRefundAmount;

            AddAuditLog(order, OrderStatus.ReembolsoSolicitado,
                $"Cliente solicitou reembolso PARCIAL de R$ {calculatedRefundAmount:F2}.", userId);
        }
        else
        {
            order.RefundType = "Total";
            order.RefundRequestedAmount = order.TotalAmount;

            foreach (var item in order.Items) item.RefundQuantity = item.Quantity;

            AddAuditLog(order, OrderStatus.ReembolsoSolicitado,
                "Cliente solicitou reembolso TOTAL.", userId);
        }

        await _uow.Orders.UpdateAsync(order);
        await _uow.CommitAsync();
    }

    public async Task<Order> GetOrderForPaymentAsync(Guid orderId, string userId)
    {
        var order = await GetUserOrderOrFail(orderId, userId);

        if (order.Status == OrderStatus.Pago)
            throw new InvalidOperationException("Este pedido já está pago.");

        if (order.Status == OrderStatus.Cancelado || order.Status == OrderStatus.Reembolsado)
            throw new InvalidOperationException("Este pedido foi cancelado e não pode ser pago.");

        if (order.Items.Count == 0)
            throw new InvalidOperationException("Pedido inválido: sem itens.");

        if (order.TotalAmount <= 0)
            throw new InvalidOperationException("Pedido com valor inválido.");

        return order;
    }

    public async Task<PaymentStatusDto> GetPaymentStatusAsync(Guid orderId, string userId)
    {
        var order = await _uow.Orders.GetByIdAsync(orderId);

        if (order == null || order.UserId != userId)
            throw new KeyNotFoundException("Pedido não encontrado.");

        return new PaymentStatusDto(order.Id, order.Status, order.TotalAmount);
    }

    private string GetIpAddress()
    {
        return _httpContextAccessor.HttpContext?.Connection?.RemoteIpAddress?.ToString() ?? "0.0.0.0";
    }

    private string GetUserAgent()
    {
        return _httpContextAccessor.HttpContext?.Request.Headers.UserAgent.ToString() ?? "Unknown";
    }

    private void AddAuditLog(Order order, OrderStatus newStatus, string message, string changedBy)
    {
        order.Status = newStatus;

        order.History.Add(new OrderHistory
        {
            OrderId = order.Id,
            Status = newStatus.GetDescription(),
            Message = message,
            ChangedBy = changedBy,
            Timestamp = DateTime.UtcNow,
            IpAddress = GetIpAddress(),
            UserAgent = GetUserAgent()
        });
    }

    private async Task<Order> GetUserOrderOrFail(Guid orderId, string userId)
    {
        var order = await _uow.Orders.GetByIdAsync(orderId)
            ?? throw new KeyNotFoundException("Pedido não encontrado.");

        if (order.UserId != userId)
            throw new UnauthorizedAccessException("Você não tem permissão para acessar este pedido.");

        return order;
    }

    private async Task NotifySecurityTeamAsync(
        Order order,
        string transactionId,
        long expectedAmount,
        long receivedAmount)
    {
        try
        {
            var securityEmail = _configuration["ADMIN_EMAIL"] ?? throw new InvalidOperationException("Configuração ADMIN_EMAIL não encontrada.");

            var emailModel = new
            {
                OrderId = order.Id,
                UserEmail = order.User?.Email ?? "N/A",
                UserId = order.UserId,
                TransactionId = transactionId,
                ExpectedAmount = expectedAmount / 100.0,
                ReceivedAmount = receivedAmount / 100.0,
                Divergence = Math.Abs(expectedAmount - receivedAmount) / 100.0,
                CustomerIp = order.CustomerIp ?? "N/A",
                UserAgent = order.UserAgent ?? "N/A",
                Date = DateTime.UtcNow,
                Year = DateTime.Now.Year
            };

            var (subject, body) = await _templateService.RenderEmailAsync("SecurityAlertPaymentMismatch", emailModel);
            await _emailService.SendEmailAsync(securityEmail, subject, body);

            _logger.LogCritical(
                "[SECURITY] Alerta enviado para time de segurança. OrderId: {OrderId}, User: {UserId}",
                order.Id, order.UserId);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex,
                "[SECURITY] Falha ao enviar alerta de segurança. OrderId: {OrderId}", order.Id);
        }
    }

    private async Task SendOrderReceivedEmailAsync(string userId, Order order)
    {
        try
        {
            var user = await _userManager.FindByIdAsync(userId);
            if (user != null && !string.IsNullOrEmpty(user.Email))
            {
                var emailModel = new
                {
                    Name = user.FullName,
                    OrderNumber = order.Id,
                    Total = order.TotalAmount.ToString("C"),
                    Items = order.Items.Select(i => new { i.ProductName, i.Quantity, Price = i.UnitPrice.ToString("C") }),
                    Year = DateTime.Now.Year
                };

                var (subject, body) = await _templateService.RenderEmailAsync("OrderReceived", emailModel);
                await _emailService.SendEmailAsync(user.Email, subject, body);
            }
        }
        catch
        {
        }
    }

    private async Task SendOrderUpdateEmailAsync(string userId, Order order, string newStatus)
    {
        try
        {
            var user = await _userManager.FindByIdAsync(userId);
            if (user == null || string.IsNullOrEmpty(user.Email)) return;

            var templateKey = newStatus switch
            {
                "Pago" => "PaymentConfirmed",
                "Enviado" => "OrderShipped",
                "Entregue" => "OrderDelivered",
                "Cancelado" => "OrderCanceled",
                "Reembolsado" => "OrderRefunded",
                "Reembolsado Parcialmente" => "OrderPartiallyRefunded",
                "Aguardando Devolução" => "OrderReturnInstructions",
                "Reembolso Reprovado" => "OrderRefundRejected",
                _ => null
            };

            if (templateKey == null) return;

            var emailModel = new
            {
                Name = user.FullName,
                OrderNumber = order.Id,
                TrackingCode = order.TrackingCode,
                ReverseLogisticsCode = order.ReverseLogisticsCode,
                ReturnInstructions = order.ReturnInstructions,
                RefundRejectionReason = order.RefundRejectionReason,
                RefundRejectionProof = order.RefundRejectionProof,
                Year = DateTime.Now.Year
            };

            var (subject, body) = await _templateService.RenderEmailAsync(templateKey, emailModel);
            await _emailService.SendEmailAsync(user.Email, subject, body);
        }
        catch
        {
        }
    }

    private static OrderDto MapToDto(Order order, string? paymentWarning = null)
    {
        return new OrderDto(
            order.Id,
            order.OrderDate,
            order.DeliveryDate,
            order.SubTotal,
            order.Discount,
            order.ShippingCost,
            order.TotalAmount,
            order.Status.GetDescription(),
            order.TrackingCode,
            order.ReverseLogisticsCode,
            order.ReturnInstructions,
            order.RefundRejectionReason,
            order.RefundRejectionProof,
            order.ShippingAddress,
            order.User?.FullName ?? "Cliente",
            [.. order.Items.Select(i =>
                new OrderItemDto(i.ProductId, i.ProductName, i.Quantity, i.RefundQuantity, i.UnitPrice, i.Quantity * i.UnitPrice)
            )],
            paymentWarning
        );
    }

    private static AdminOrderDto MapToAdminDto(Order order)
    {
        return new AdminOrderDto(
            order.Id,
            order.OrderDate,
            order.DeliveryDate,
            order.SubTotal,
            order.Discount,
            order.ShippingCost,
            order.TotalAmount,
            order.Status.GetDescription(),
            order.TrackingCode,
            order.ReverseLogisticsCode,
            order.ReturnInstructions,
            order.RefundRejectionReason,
            order.RefundRejectionProof,
            order.ShippingAddress,
            order.User?.FullName ?? "Cliente Desconhecido",
            DataMaskingExtensions.MaskCpfCnpj(order.User?.CpfCnpj ?? ""),
            order.User?.Email ?? "N/A",
            DataMaskingExtensions.MaskIpAddress(order.CustomerIp),
            [.. order.Items.Select(i =>
                new OrderItemDto(i.ProductId, i.ProductName, i.Quantity, i.RefundQuantity, i.UnitPrice, i.Quantity * i.UnitPrice)
            )],
            [.. order.History.Select(h =>
                new OrderHistoryDto(h.Status, h.Message, h.ChangedBy, h.Timestamp)
            ).OrderByDescending(h => h.Timestamp)]
        );
    }

    private static OrderStatus ParseStatus(string status)
    {
        foreach (var field in typeof(OrderStatus).GetFields())
        {
            var attribute = (DescriptionAttribute?)Attribute.GetCustomAttribute(field, typeof(DescriptionAttribute));
            if (attribute != null && attribute.Description.Equals(status, StringComparison.OrdinalIgnoreCase))
                return (OrderStatus)field.GetValue(null)!;

            if (field.Name.Equals(status, StringComparison.OrdinalIgnoreCase))
                return (OrderStatus)field.GetValue(null)!;
        }
        return OrderStatus.Pendente;
    }
}