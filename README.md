# eseperio/pdfme-php-generator

Library to render [pdfme](https://pdfme.com/) JSON templates from PHP using [mPDF](https://mpdf.github.io/). It avoids HTML rendering and uses the low-level drawing APIs in mPDF so the output matches pdfme as closely as possible while staying ready for custom elements and plugins.

## Installation

```
composer require eseperio/pdfme-php-generator
```

## Usage

The library accepts the same template format as pdfme: a `basePdf` spec and a `schemas` array (one sub-array per page). Field values are supplied as `inputs`, which is an array of flat key→value maps – exactly like `generate({ template, inputs })` in the JavaScript pdfme library.

```php
use eseperio\PdfmeGenerator\Generator;

$template = [
    'basePdf' => ['width' => 210, 'height' => 297, 'padding' => [10, 10, 10, 10]],
    'schemas' => [
        // Page 1 – array of schema elements
        [
            [
                'name'      => 'fullName',
                'type'      => 'text',
                'position'  => ['x' => 20, 'y' => 30],
                'width'     => 120,
                'height'    => 10,
                'fontSize'  => 14,
                'fontColor' => '#333333',
                'alignment' => 'left',
            ],
            [
                'name'        => 'border',
                'type'        => 'rectangle',
                'position'    => ['x' => 18, 'y' => 28],
                'width'       => 126,
                'height'      => 18,
                'borderColor' => '#999999',
            ],
        ],
    ],
];

// inputs[0] maps field names to their values
$inputs = [['fullName' => 'John Doe']];

$generator = new Generator();
$pdfBinary = $generator->generate($template, $inputs);
file_put_contents('output.pdf', $pdfBinary);
```

### Static content

If a schema element has a `content` property and no matching key is found in `inputs`, the static `content` value is used:

```php
[
    'name'    => 'footer',
    'type'    => 'text',
    'content' => 'Confidential',
    'position' => ['x' => 10, 'y' => 280],
    'width'   => 190,
    'height'  => 8,
]
```

### Supported element fields

| Field               | Type    | Description |
|---------------------|---------|-------------|
| `name`              | string  | Field name; used to look up values in `inputs` |
| `type`              | string  | `text`, `rectangle`, `line`, `image` |
| `position`          | array   | `{ x: mm, y: mm }` from top-left of page |
| `width`             | float   | Width in mm |
| `height`            | float   | Height in mm |
| `rotate`            | float   | Clockwise rotation in degrees |
| `opacity`           | float   | 0–1 transparency |
| `content`           | string  | Static fallback content |
| **Text**            | | |
| `fontName`          | string  | mPDF font family (default `dejavusans`) |
| `fontSize`          | float   | Font size in pt (default 12) |
| `fontColor`         | string  | Hex color e.g. `#333333` |
| `backgroundColor`   | string  | Hex background fill for text cell |
| `alignment`         | string  | `left` \| `center` \| `right` \| `justify` |
| `verticalAlignment` | string  | `top` \| `middle` \| `bottom` |
| `lineHeight`        | float   | Line-height multiplier (default 1.0) |
| `characterSpacing`  | float   | Extra character spacing in pt |
| `bold`              | bool    | Bold font style |
| `italic`            | bool    | Italic font style |
| `underline`         | bool    | Underline font style |
| `strikethrough`     | bool    | Strikethrough font style |
| **Rectangle**       | | |
| `color`             | string  | Hex fill color |
| `borderColor`       | string  | Hex border color |
| `borderWidth`       | float   | Border width in mm |
| **Line**            | | |
| `color`             | string  | Hex line color |
| **Image**           | | |
| `content`           | string  | URL or base64 data URI |

## Custom renderers and plugins

Register additional renderers to cover custom elements produced by pdfme plugins or client-specific schemas. The callback receives the `RenderContext` and must return a `RenderResult` so the engine can keep tracking coordinates.

See [docs/plugin-guide.md](docs/plugin-guide.md) for a detailed walkthrough and simulated plugin examples.

```php
use eseperio\PdfmeGenerator\Renderer\CallbackRenderer;
use eseperio\PdfmeGenerator\Renderer\RendererRegistry;
use eseperio\PdfmeGenerator\RenderResult;

$registry = RendererRegistry::withDefaultRenderers();
$registry->register('custom-banner', new CallbackRenderer(function ($context, $element) {
    $mpdf = $context->mpdf();
    $x = $element['position']['x'] ?? 0;
    $y = $element['position']['y'] ?? 0;
    $mpdf->SetFont('dejavusans', 'B', 18);
    $mpdf->SetXY($x, $y);
    $mpdf->MultiCell($element['width'], 10, $context->resolveContent($element));

    return new RenderResult($x, $y, $element['width'], 10);
}));

$generator = new Generator($registry);
```

## Tests

Run the test suite with:

```
composer test
```
