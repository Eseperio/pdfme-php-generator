<?php

namespace Pdfme\Generator\Renderer;

use Pdfme\Generator\RenderContext;
use Pdfme\Generator\RenderResult;

class TextRenderer implements Renderer
{
    public function render(RenderContext $context, array $element, array $data): RenderResult
    {
        $mpdf = $context->mpdf();
        $content = $context->resolveText((string) ($element['text'] ?? ''));
        $x = (float) ($element['x'] ?? 0);
        $y = (float) ($element['y'] ?? 0);
        $width = (float) ($element['width'] ?? 0);
        $lineHeight = (float) ($element['lineHeight'] ?? ($element['height'] ?? 4));
        $alignment = strtoupper((string) ($element['align'] ?? 'L'));

        $font = (string) ($element['fontFamily'] ?? 'dejavusans');
        $style = (string) ($element['fontStyle'] ?? '');
        $fontSize = (float) ($element['fontSize'] ?? 12);

        $mpdf->SetFont($font, $style, $fontSize);

        if (isset($element['color'])) {
            $rgb = $this->colorToRgb($element['color']);
            $mpdf->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
        }

        $mpdf->SetXY($x, $y);
        $mpdf->MultiCell($width, $lineHeight, $content, $element['border'] ?? 0, $alignment);
        $endY = $mpdf->y;
        $height = max((float) ($element['height'] ?? 0), $endY - $y);

        return new RenderResult($x, $y, $width, $height);
    }

    private function colorToRgb(string|array $color): array
    {
        if (is_array($color)) {
            return [(int) $color[0], (int) $color[1], (int) $color[2]];
        }

        $color = ltrim($color, '#');
        if (strlen($color) === 3) {
            $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
        }

        return [
            hexdec(substr($color, 0, 2)),
            hexdec(substr($color, 2, 2)),
            hexdec(substr($color, 4, 2)),
        ];
    }
}
