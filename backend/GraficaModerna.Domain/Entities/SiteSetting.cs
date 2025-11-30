namespace GraficaModerna.Domain.Entities;

public class SiteSetting
{
    public string Key { get; private set; } = string.Empty; // ex: 'contact_whatsapp', 'contact_email'
    public string Value { get; private set; } = string.Empty;

    protected SiteSetting() { }

    public SiteSetting(string key, stringVm value)
    {
        Key = key;
        Value = value;
    }
}