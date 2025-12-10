namespace GraficaModerna.Application.Constants;

public static class SanitizerRules
{
    public static readonly string[] AllowedTags =
    [
        "p", "h1", "h2", "h3", "h4", "h5", "h6", "br", "hr",
        "b", "strong", "i", "em", "u", "s", "strike", "sub", "sup",
        "div", "span", "blockquote", "pre", "code",
        "ul", "ol", "li", "dl", "dt", "dd",
        "table", "thead", "tbody", "tfoot", "tr", "th", "td",
        "a", "img"
    ];

    public static readonly string[] AllowedAttributes =
    [
        "class", "id", "style", "title", "alt",
        "href", "target", "rel",
        "src", "width", "height",
        "colspan", "rowspan", "align", "valign"
    ];

    public static readonly string[] AllowedCssProperties =
    [
        "text-align", "padding", "margin", "color", "background-color",
        "font-size", "font-weight", "text-decoration", "width", "height"
    ];
}