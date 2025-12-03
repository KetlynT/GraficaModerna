using System.Text.Json.Serialization;

namespace GraficaModerna.Domain.Entities;

public class UserAddress
{
    public Guid Id { get; set; } = Guid.NewGuid();
    public string UserId { get; set; } = string.Empty;

    // Dados do Endereço
    public string Name { get; set; } = "Casa"; // Ex: Casa, Trabalho
    public string ReceiverName { get; set; } = string.Empty; // Quem recebe
    public string ZipCode { get; set; } = string.Empty;
    public string Street { get; set; } = string.Empty;
    public string Number { get; set; } = string.Empty;
    public string Complement { get; set; } = string.Empty;
    public string Neighborhood { get; set; } = string.Empty;
    public string City { get; set; } = string.Empty;
    public string State { get; set; } = string.Empty;
    public string Reference { get; set; } = string.Empty; // Ponto de referência
    public string PhoneNumber { get; set; } = string.Empty; // Telefone de contato na entrega

    public bool IsDefault { get; set; } = false;

    // Navegação
    [JsonIgnore]
    public ApplicationUser? User { get; set; }

    public override string ToString()
    {
        return $"{Street}, {Number} - {Complement} - {Neighborhood}, {City}/{State} - CEP: {ZipCode} (Ref: {Reference}) - A/C: {ReceiverName} - Tel: {PhoneNumber}";
    }
}