# Pdfme php generator

Library to render pdfme JSON layouts from PHP using [mPDF](https://mpdf.github.io/). It avoids HTML rendering and uses the low-level drawing APIs in mPDF so the output matches pdfme as closely as possible while staying ready for custom elements and plugins.

## Installation

```
composer require pdfme/php-generator
```

## Usage

```php
use Pdfme\Generator\Generator;

$layout = [
    'pages' => [
        [
            'width' => 210,
            'height' => 297,
            'elements' => [
                ['type' => 'text', 'x' => 20, 'y' => 30, 'width' => 120, 'text' => 'Hello {{name}}', 'fontSize' => 14],
                ['type' => 'rectangle', 'x' => 18, 'y' => 28, 'width' => 126, 'height' => 18, 'borderColor' => '#999999'],
            ],
        ],
    ],
];

$generator = new Generator();
$pdfBinary = $generator->generate($layout, ['name' => 'pdfme']);
file_put_contents('example.pdf', $pdfBinary);
```

## Custom renderers and plugins

Register additional renderers to cover custom elements produced by pdfme plugins or client-specific schemas. The callback receives the `RenderContext` and must return a `RenderResult` so the engine can keep tracking coordinates.

See [docs/plugin-guide.md](docs/plugin-guide.md) for a detailed walkthrough and simulated plugin examples.

```php
use Pdfme\Generator\Renderer\CallbackRenderer;
use Pdfme\Generator\Renderer\RendererRegistry;
use Pdfme\Generator\RenderResult;

$registry = RendererRegistry::withDefaultRenderers();
$registry->register('custom-banner', new CallbackRenderer(function ($context, $element, $data) {
    $mpdf = $context->mpdf();
    $mpdf->SetFont('dejavusans', 'B', 18);
    $mpdf->SetXY($element['x'], $element['y']);
    $mpdf->MultiCell($element['width'], 10, $context->resolveText($element['text'] ?? ''));

    return new RenderResult($element['x'], $element['y'], $element['width'], 10);
}));

$generator = new Generator($registry);
```

## Tests

Run the test suite with:

```
composer test
```
