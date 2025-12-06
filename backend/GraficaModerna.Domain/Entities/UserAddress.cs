using System.Text.Json.Serialization;

namespace GraficaModerna.Domain.Entities;

public class UserAddress
{
    public Guid Id { get; set; } = Guid.NewGuid();
    public string UserId { get; set; } = string.Empty;

    public string Name { get; set; } = "Casa"; 
    public string ReceiverName { get; set; } = string.Empty; 
    public string ZipCode { get; set; } = string.Empty;
    public string Street { get; set; } = string.Empty;
    public string Number { get; set; } = string.Empty;
    public string Complement { get; set; } = string.Empty;
    public string Neighborhood { get; set; } = string.Empty;
    public string City { get; set; } = string.Empty;
    public string State { get; set; } = string.Empty;
    public string Reference { get; set; } = string.Empty; 
    public string PhoneNumber { get; set; } = string.Empty; 

    public bool IsDefault { get; set; } = false;

    [JsonIgnore] public ApplicationUser? User { get; set; }

    public override string ToString()
    {
        return
            $"{Street}, {Number} - {Complement} - {Neighborhood}, {City}/{State} - CEP: {ZipCode} (Ref: {Reference}) - A/C: {ReceiverName} - Tel: {PhoneNumber}";
    }
}
