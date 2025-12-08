<?php

namespace Pdfme\Generator\Renderer;

use Pdfme\Generator\RenderContext;
use Pdfme\Generator\RenderResult;

class RectangleRenderer implements Renderer
{
    public function render(RenderContext $context, array $element, array $data): RenderResult
    {
        $mpdf = $context->mpdf();
        $x = (float) ($element['x'] ?? 0);
        $y = (float) ($element['y'] ?? 0);
        $width = (float) ($element['width'] ?? 0);
        $height = (float) ($element['height'] ?? 0);
        $style = (string) ($element['style'] ?? 'D');

        if (isset($element['fillColor'])) {
            $fill = $this->colorToRgb($element['fillColor']);
            $mpdf->SetFillColor($fill[0], $fill[1], $fill[2]);
            $style = 'F';
        }

        if (isset($element['borderColor'])) {
            $stroke = $this->colorToRgb($element['borderColor']);
            $mpdf->SetDrawColor($stroke[0], $stroke[1], $stroke[2]);
        }

        $mpdf->Rect($x, $y, $width, $height, $style);

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
