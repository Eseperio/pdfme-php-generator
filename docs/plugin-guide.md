# Custom renderer plugins

This guide explains how to extend the library with custom renderers that mirror pdfme plugins. A renderer maps a `type` string fro
m the pdfme JSON to drawing instructions in mPDF. By registering a renderer you can support new element types while keeping coord
inate tracking consistent with the core engine.

## Renderer contract

Every renderer implements `Pdfme\Generator\Renderer\Renderer` and receives three arguments:

- `RenderContext $context`: provides access to the shared `Mpdf` instance, the registry, placeholder resolution, and the last `Re

nderResult` produced on the current page.
- `array $element`: the element definition from the pdfme JSON.
- `array $data`: the data payload passed to `Generator::generate()` for placeholder substitution.

The renderer must return a `RenderResult` describing the x/y position, width, and height consumed so the next element can be posi
tioned correctly.

## Registering a renderer

Renderers live in a `RendererRegistry`. Start with the defaults and register your own type string:

```php
use Pdfme\Generator\Renderer\CallbackRenderer;
use Pdfme\Generator\Renderer\RendererRegistry;
use Pdfme\Generator\RenderResult;

$registry = RendererRegistry::withDefaultRenderers();

$registry->register('custom-banner', new CallbackRenderer(function ($context, $element, $data) {
    $mpdf = $context->mpdf();

    $x = (float) ($element['x'] ?? 10);
    $y = (float) ($element['y'] ?? 10);
    $width = (float) ($element['width'] ?? 120);
    $height = 12.0;

    $mpdf->SetFont('dejavusans', 'B', 16);
    $mpdf->SetFillColor(30, 144, 255);
    $mpdf->Rect($x, $y, $width, $height, 'F');

    $mpdf->SetTextColor(255, 255, 255);
    $mpdf->SetXY($x + 2, $y + 2);
    $mpdf->Write(8, $context->resolveText($element['text'] ?? ''));

    return new RenderResult($x, $y, $width, $height);
}));
```

Pass the registry to `Generator` so your renderer is used:

```php
use Pdfme\Generator\Generator;

$generator = new Generator($registry);
$pdfBinary = $generator->generate($layout, $data);
```

## Simulated plugin example

Imagine a pdfme plugin that emits a `progress-bar` element with a value between 0 and 100. The renderer should draw the backgroun
d and the filled portion while keeping layout coordinates consistent for following elements.

```php
$registry->register('progress-bar', new CallbackRenderer(function ($context, $element, $data) {
    $mpdf = $context->mpdf();

    $x = (float) ($element['x'] ?? 10);
    $y = (float) ($element['y'] ?? ($context->lastResult()?->y() ?? 10));
    $width = (float) ($element['width'] ?? 80);
    $height = (float) ($element['height'] ?? 8);
    $percent = max(0.0, min(100.0, (float) ($element['value'] ?? 0)));

    // Track from the previous element when y is omitted, similar to pdfme dragging behavior
    if (!isset($element['y']) && $context->lastResult()) {
        $y = $context->lastResult()->y() + $context->lastResult()->height() + 2;
    }

    $mpdf->SetFillColor(230, 230, 230);
    $mpdf->Rect($x, $y, $width, $height, 'F');

    $mpdf->SetFillColor(60, 179, 113);
    $mpdf->Rect($x, $y, $width * ($percent / 100), $height, 'F');

    $mpdf->SetTextColor(0, 0, 0);
    $mpdf->SetXY($x + 2, $y + 1);
    $mpdf->SetFont('dejavusans', '', 8);
    $mpdf->Write($height - 2, sprintf('%s%%', $percent));

    return new RenderResult($x, $y, $width, $height);
}));
```

The renderer takes advantage of `RenderContext::lastResult()` to align below the previous element when the `y` coordinate is omitt
ed, mirroring how pdfme arranges elements when dragging. It also returns an accurate `RenderResult` so subsequent elements are pla
ced correctly.

## Testing your plugin

- Use the same `composer test` command to add PHPUnit coverage around your custom renderer.
- Assert that the resulting PDF binary contains the expected text or that the mocked renderer receives the right coordinates.

With this structure, new renderers can be added safely without modifying the core library, keeping parity with pdfme plugins whil
e rendering through mPDF.
