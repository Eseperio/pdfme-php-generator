<?php

namespace eseperio\PdfmeGenerator\Renderer;

use eseperio\PdfmeGenerator\RenderContext;
use eseperio\PdfmeGenerator\RenderResult;

class LineRenderer implements Renderer
{
    public function render(RenderContext $context, array $element): RenderResult
    {
        $mpdf = $context->mpdf();

        $position = $element['position'] ?? [];
        $x1 = (float) ($position['x'] ?? $element['x'] ?? 0);
        $y1 = (float) ($position['y'] ?? $element['y'] ?? 0);

        if (isset($element['x2']) && isset($element['y2'])) {
            $x2 = (float) $element['x2'];
            $y2 = (float) $element['y2'];
        } else {
            $x2 = $x1 + (float) ($element['width'] ?? 0);
            $y2 = $y1 + (float) ($element['height'] ?? 0);
        }

        // Line color via pdfme `color` field
        if (isset($element['color'])) {
            $rgb = $this->colorToRgb($element['color']);
            $mpdf->SetDrawColor($rgb[0], $rgb[1], $rgb[2]);
        }

        // Opacity
        $hasOpacity = isset($element['opacity']);
        if ($hasOpacity) {
            $mpdf->SetAlpha((float) $element['opacity']);
        }

        $mpdf->Line($x1, $y1, $x2, $y2);

        if ($hasOpacity) {
            $mpdf->SetAlpha(1.0);
        }

        $mpdf->SetDrawColor(0, 0, 0);

        return new RenderResult($x1, $y1, $x2 - $x1, $y2 - $y1);
    }

    private function colorToRgb(string|array $color): array
    {
        if (is_array($color)) {
            return [(int) $color[0], (int) $color[1], (int) $color[2]];
        }

        $color = ltrim((string) $color, '#');
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
