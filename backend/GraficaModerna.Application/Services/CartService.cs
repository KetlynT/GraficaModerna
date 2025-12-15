using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using GraficaModerna.Domain.Entities;
using GraficaModerna.Domain.Interfaces;
using Microsoft.Extensions.Logging;
using System.Data;

namespace GraficaModerna.Application.Services;

public class CartService(IUnitOfWork uow, ILogger<CartService> logger) : ICartService
{
    private const int MaxConcurrencyRetries = 3;
    private const int MaxQuantityPerItem = 5000;
    private readonly IUnitOfWork _uow = uow;
    private readonly ILogger<CartService> _logger = logger;

    public async Task<CartDto> GetCartAsync(string userId)
    {
        var cart = await GetOrCreateCart(userId);

        var itemsDto = cart.Items
            .Where(i => i.Product != null && i.Product.IsActive)
            .Select(i => new CartItemDto(
                i.Id,
                i.ProductId,
                i.Product!.Name,
                (i.Product.ImageUrls != null && i.Product.ImageUrls.Count > 0) ? i.Product.ImageUrls[0] : "",
                i.Product.Price,
                i.Quantity,
                i.Product.Price * i.Quantity,
                i.Product.Weight,
                i.Product.Width,
                i.Product.Height,
                i.Product.Length
            )).ToList();

        var orphanedItems = cart.Items.Where(i => i.Product == null || !i.Product.IsActive).ToList();
        if (orphanedItems.Count != 0)
        {
            foreach (var item in orphanedItems) await _uow.Carts.RemoveItemAsync(item);
            await _uow.CommitAsync();
        }

        return new CartDto(cart.Id, itemsDto, itemsDto.Sum(i => i.TotalPrice));
    }

    public async Task AddItemAsync(string userId, AddToCartDto dto)
    {
        ArgumentNullException.ThrowIfNull(dto);
        if (string.IsNullOrWhiteSpace(userId))
            throw new ArgumentException("userId inválido.", nameof(userId));

        if (dto.Quantity <= 0)
            throw new ArgumentOutOfRangeException(nameof(dto), "Quantidade deve ser maior que zero.");

        if (dto.Quantity > MaxQuantityPerItem)
            throw new ArgumentOutOfRangeException(nameof(dto),
                $"Quantidade excede o limite permitido de {MaxQuantityPerItem}.");

        for (var attempt = 1; attempt <= MaxConcurrencyRetries; attempt++)
        {
            using var transaction = await _uow.BeginTransactionAsync(IsolationLevel.ReadCommitted);
            try
            {
                var product = await _uow.Products.GetByIdWithLockAsync(dto.ProductId)
                    ?? throw new InvalidOperationException("Produto indisponível ou removido.");

                if (product.StockQuantity < dto.Quantity)
                    throw new InvalidOperationException("Estoque insuficiente para a quantidade solicitada.");

                var cart = await _uow.Carts.GetByUserIdWithLockAsync(userId);

                if (cart == null)
                {
                    cart = new Cart
                    {
                        UserId = userId,
                        LastUpdated = DateTime.UtcNow
                    };
                    await _uow.Carts.AddAsync(cart);
                    await _uow.CommitAsync();
                }

                var existing = cart.Items.FirstOrDefault(i => i.ProductId == dto.ProductId);

                if (existing != null)
                {
                    long newTotal = (long)existing.Quantity + dto.Quantity;

                    if (newTotal > MaxQuantityPerItem)
                        throw new InvalidOperationException(
                            $"O total de itens excederia o limite máximo de {MaxQuantityPerItem}.");

                    if (newTotal > int.MaxValue)
                        throw new InvalidOperationException("Quantidade inválida (Excesso de itens).");

                    if (product.StockQuantity < newTotal)
                        throw new InvalidOperationException(
                            "Não é possível adicionar mais itens: estoque insuficiente.");

                    existing.Quantity = (int)newTotal;
                }
                else
                {
                    cart.Items.Add(new CartItem
                    {
                        CartId = cart.Id,
                        ProductId = dto.ProductId,
                        Quantity = dto.Quantity
                    });
                }

                cart.LastUpdated = DateTime.UtcNow;
                await _uow.CommitAsync();
                await transaction.CommitAsync();

                _logger.LogInformation(
                    "Item adicionado ao carrinho. User: {UserId}, Product: {ProductId}, Qty: {Quantity}",
                    userId, dto.ProductId, dto.Quantity);

                return;
            }
            catch (Exception ex)
            {
                await transaction.RollbackAsync();

                var isConcurrencyException = ex.GetType().Name.Contains("Concurrency") || ex.GetType().Name.Contains("DbUpdateConcurrency");

                if (isConcurrencyException)
                {
                    _logger.LogWarning(ex,
                        "Tentativa {Attempt} de {MaxAttempts} falhou (concorrência). User: {UserId}",
                        attempt, MaxConcurrencyRetries, userId);

                    if (attempt == MaxConcurrencyRetries)
                        throw new InvalidOperationException(
                            "Não foi possível processar a operação devido a alta concorrência. Tente novamente.");

                    await Task.Delay(TimeSpan.FromMilliseconds(100 * attempt));
                }
                else
                {
                    throw;
                }
            }
        }
    }

    public async Task UpdateItemQuantityAsync(string userId, Guid cartItemId, int quantity)
    {
        if (string.IsNullOrWhiteSpace(userId))
            throw new ArgumentException("userId inválido.", nameof(userId));

        if (quantity < 0)
            throw new ArgumentException("A quantidade não pode ser negativa.");

        if (quantity > MaxQuantityPerItem)
            throw new ArgumentException($"Quantidade excede o limite permitido de {MaxQuantityPerItem}.");

        if (quantity == 0)
        {
            await RemoveItemAsync(userId, cartItemId);
            return;
        }

        for (var attempt = 1; attempt <= MaxConcurrencyRetries; attempt++)
        {
            using var transaction = await _uow.BeginTransactionAsync(IsolationLevel.ReadCommitted);
            try
            {
                var cart = await _uow.Carts.GetByUserIdWithLockAsync(userId)
                    ?? throw new InvalidOperationException("Carrinho não encontrado.");

                var item = cart.Items.FirstOrDefault(i => i.Id == cartItemId)
                    ?? throw new InvalidOperationException("Item não encontrado no carrinho.");

                var product = await _uow.Products.GetByIdWithLockAsync(item.ProductId)
                    ?? throw new InvalidOperationException("Produto indisponível.");

                if (product.StockQuantity < quantity)
                    throw new InvalidOperationException("Estoque insuficiente.");

                item.Quantity = quantity;
                cart.LastUpdated = DateTime.UtcNow;

                await _uow.CommitAsync();
                await transaction.CommitAsync();
                return;
            }
            catch (Exception ex)
            {
                await transaction.RollbackAsync();

                var isConcurrencyException = ex.GetType().Name.Contains("Concurrency") || ex.GetType().Name.Contains("DbUpdateConcurrency");

                if (isConcurrencyException)
                {
                    if (attempt == MaxConcurrencyRetries)
                        throw new InvalidOperationException(
                            "Não foi possível atualizar o item devido a alta concorrência.");

                    await Task.Delay(TimeSpan.FromMilliseconds(100 * attempt));
                }
                else
                {
                    throw;
                }
            }
        }
    }

    public async Task RemoveItemAsync(string userId, Guid itemId)
    {
        if (string.IsNullOrWhiteSpace(userId))
            throw new ArgumentException("userId inválido.", nameof(userId));

        var cart = await GetOrCreateCart(userId);
        var item = cart.Items.FirstOrDefault(i => i.Id == itemId);

        if (item != null)
        {
            await _uow.Carts.RemoveItemAsync(item);
            await _uow.CommitAsync();
        }
    }

    public async Task ClearCartAsync(string userId)
    {
        if (string.IsNullOrWhiteSpace(userId))
            throw new ArgumentException("userId inválido.", nameof(userId));

        var cart = await _uow.Carts.GetByUserIdAsync(userId);
        if (cart != null)
        {
            await _uow.Carts.ClearCartAsync(cart.Id);
            await _uow.CommitAsync();
        }
    }

    private async Task<Cart> GetOrCreateCart(string userId)
    {
        if (string.IsNullOrWhiteSpace(userId))
            throw new ArgumentException("userId inválido.", nameof(userId));

        var cart = await _uow.Carts.GetByUserIdAsync(userId);
        if (cart == null)
        {
            cart = new Cart
            {
                UserId = userId,
                LastUpdated = DateTime.UtcNow
            };
            await _uow.Carts.AddAsync(cart);
            await _uow.CommitAsync();
        }

        return cart;
    }
}