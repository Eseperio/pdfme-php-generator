<?php

namespace eseperio\PdfmeGenerator\Renderer;

use eseperio\PdfmeGenerator\RenderContext;
use eseperio\PdfmeGenerator\RenderResult;

class TextRenderer implements Renderer
{
    public function render(RenderContext $context, array $element): RenderResult
    {
        $mpdf = $context->mpdf();

        $position = $element['position'] ?? [];
        $x = (float) ($position['x'] ?? $element['x'] ?? 0);
        $y = (float) ($position['y'] ?? $element['y'] ?? 0);
        $width = (float) ($element['width'] ?? 0);
        $height = (float) ($element['height'] ?? 0);

        // Content: field value from input data (by name) or static content
        $content = $context->resolveContent($element);

        // Font
        $fontName = (string) ($element['fontName'] ?? $element['fontFamily'] ?? 'dejavusans');
        $fontSize = (float) ($element['fontSize'] ?? 12);

        $fontStyle = '';
        if (!empty($element['bold'])) {
            $fontStyle .= 'B';
        }
        if (!empty($element['italic'])) {
            $fontStyle .= 'I';
        }
        if (!empty($element['underline'])) {
            $fontStyle .= 'U';
        }
        if (!empty($element['strikethrough'])) {
            $fontStyle .= 'S';
        }
        // Legacy fontStyle field support
        if ($fontStyle === '' && isset($element['fontStyle'])) {
            $fontStyle = (string) $element['fontStyle'];
        }

        $mpdf->SetFont($fontName, $fontStyle, $fontSize);

        // Text color (fontColor takes precedence over legacy color)
        $colorField = $element['fontColor'] ?? $element['color'] ?? null;
        if ($colorField !== null) {
            $rgb = $this->colorToRgb($colorField);
            $mpdf->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
        }

        // Background color
        $hasBg = false;
        if (isset($element['backgroundColor'])) {
            $bgRgb = $this->colorToRgb($element['backgroundColor']);
            $mpdf->SetFillColor($bgRgb[0], $bgRgb[1], $bgRgb[2]);
            $hasBg = true;
        }

        // Character spacing (in pt)
        if (isset($element['characterSpacing'])) {
            $mpdf->SetSpacing((float) $element['characterSpacing']);
        }

        // Alignment: pdfme uses 'left'|'center'|'right'|'justify'
        $alignMap = ['left' => 'L', 'center' => 'C', 'right' => 'R', 'justify' => 'J'];
        $alignment = strtolower((string) ($element['alignment'] ?? $element['align'] ?? 'left'));
        $mpdfAlign = $alignMap[$alignment] ?? 'L';

        // Line height: pdfme uses a multiplier of fontSize; convert to mm
        // 1 pt = 0.3528 mm; multiply by lineHeight ratio
        $lineHeightRatio = (float) ($element['lineHeight'] ?? 1.0);
        $lineHeightMm = max(0.1, $lineHeightRatio * $fontSize * 0.3528);

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

        // Vertical alignment: calculate y offset
        $renderY = $y;
        $verticalAlignment = strtolower((string) ($element['verticalAlignment'] ?? 'top'));
        if ($verticalAlignment !== 'top' && $height > 0 && $width > 0) {
            $charsPerLine = max(1, (int) ($width / ($fontSize * 0.3528 * 0.6)));
            $lineCount = max(1, (int) ceil(mb_strlen($content) / $charsPerLine));
            $textHeight = $lineCount * $lineHeightMm;
            if ($verticalAlignment === 'middle') {
                $renderY = $y + max(0.0, ($height - $textHeight) / 2);
            } elseif ($verticalAlignment === 'bottom') {
                $renderY = $y + max(0.0, $height - $textHeight);
            }
        }

        $mpdf->SetXY($x, $renderY);
        $mpdf->MultiCell($width, $lineHeightMm, $content, $element['border'] ?? 0, $mpdfAlign, $hasBg);

        if ($rotate !== 0.0) {
            $mpdf->StopTransform();
        }

        // Reset state
        $mpdf->SetTextColor(0, 0, 0);
        $mpdf->SetFillColor(255, 255, 255);
        if (isset($element['characterSpacing'])) {
            $mpdf->SetSpacing(0);
        }
        if ($hasOpacity) {
            $mpdf->SetAlpha(1.0);
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
