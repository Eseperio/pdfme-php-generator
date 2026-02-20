<?php

namespace eseperio\PdfmeGenerator\Renderer;

use eseperio\PdfmeGenerator\RenderContext;
use eseperio\PdfmeGenerator\RenderResult;

class RectangleRenderer implements Renderer
{
    public function render(RenderContext $context, array $element): RenderResult
    {
        $mpdf = $context->mpdf();

        $position = $element['position'] ?? [];
        $x = (float) ($position['x'] ?? $element['x'] ?? 0);
        $y = (float) ($position['y'] ?? $element['y'] ?? 0);
        $width = (float) ($element['width'] ?? 0);
        $height = (float) ($element['height'] ?? 0);

        // Border width
        if (isset($element['borderWidth'])) {
            $mpdf->SetLineWidth((float) $element['borderWidth']);
        }

        // pdfme uses `color` for fill, `borderColor` for stroke; also support legacy `fillColor`
        $fillColor = $element['color'] ?? $element['fillColor'] ?? null;
        $strokeColor = $element['borderColor'] ?? null;

        $style = 'D';
        if ($fillColor !== null && $strokeColor !== null) {
            $style = 'DF';
        } elseif ($fillColor !== null) {
            $style = 'F';
        }

        if ($fillColor !== null) {
            $fill = $this->colorToRgb($fillColor);
            $mpdf->SetFillColor($fill[0], $fill[1], $fill[2]);
        }

        if ($strokeColor !== null) {
            $stroke = $this->colorToRgb($strokeColor);
            $mpdf->SetDrawColor($stroke[0], $stroke[1], $stroke[2]);
        }

        // Opacity
        $hasOpacity = isset($element['opacity']);
        if ($hasOpacity) {
            $mpdf->SetAlpha((float) $element['opacity']);
        }

        // Rotation
        $rotate = (float) ($element['rotate'] ?? 0);
        if ($rotate !== 0.0) {
            $mpdf->StartTransform();
            $mpdf->Rotate(-$rotate, $x + $width / 2, $y + $height / 2);
        }

        $mpdf->Rect($x, $y, $width, $height, $style);

        if ($rotate !== 0.0) {
            $mpdf->StopTransform();
        }

        if ($hasOpacity) {
            $mpdf->SetAlpha(1.0);
        }

        // Reset draw/fill colors
        $mpdf->SetDrawColor(0, 0, 0);
        $mpdf->SetFillColor(255, 255, 255);

        if (isset($element['borderWidth'])) {
            $mpdf->SetLineWidth(0.2);
        }

        return new RenderResult($x, $y, $width, $height);
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
