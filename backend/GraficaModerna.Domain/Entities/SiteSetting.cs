namespace GraficaModerna.Domain.Entities;

public class SiteSetting
{
    protected SiteSetting()
    {
    }

    public SiteSetting(string key, string value)
    {
        Key = key;
        Value = value;
    }

    public string Key { get; private set; } = string.Empty;
    public string Value { get; private set; } = string.Empty;

    public void UpdateValue(string value)
    {
        Value = value;
    }
}
