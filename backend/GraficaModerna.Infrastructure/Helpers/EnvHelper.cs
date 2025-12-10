namespace GraficaModerna.Infrastructure.Helpers;

public static class EnvHelper
{
    public static string Required(string name, int? minLength = null)
    {
        var value = Environment.GetEnvironmentVariable(name);

        if (string.IsNullOrWhiteSpace(value))
            throw new Exception($"FATAL: {name} não configurada.");

        if (minLength.HasValue && value.Length < minLength.Value)
            throw new Exception($"FATAL: {name} insegura (tamanho mínimo: {minLength.Value}).");

        return value;
    }

    public static int RequiredInt(string name)
    {
        var value = Required(name);
        if (!int.TryParse(value, out var n))
            throw new Exception($"FATAL: {name} inválida (deve ser um número inteiro).");
        return n;
    }
}