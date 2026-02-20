<?php

namespace eseperio\PdfmeGenerator\Renderer;

use eseperio\PdfmeGenerator\RenderContext;
use eseperio\PdfmeGenerator\RenderResult;

class ImageRenderer implements Renderer
{
    public function render(RenderContext $context, array $element): RenderResult
    {
        $mpdf = $context->mpdf();

        $position = $element['position'] ?? [];
        $x = (float) ($position['x'] ?? $element['x'] ?? 0);
        $y = (float) ($position['y'] ?? $element['y'] ?? 0);
        $width = (float) ($element['width'] ?? 0);
        $height = (float) ($element['height'] ?? 0);

        // Content: field value from input data (by name) or static content; legacy src fallback
        $source = $context->resolveContent($element);
        if ($source === '') {
            $source = (string) ($element['src'] ?? '');
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

        if ($source !== '') {
            $mpdf->Image($this->resolveSource($source), $x, $y, $width, $height, '', '', true, false);
        }

        if ($rotate !== 0.0) {
            $mpdf->StopTransform();
        }

        if ($hasOpacity) {
            $mpdf->SetAlpha(1.0);
        }

        return new RenderResult($x, $y, $width, $height);
    }

    private function resolveSource(string $source): string
    {
        if (str_starts_with($source, 'data:')) {
            [$meta, $data] = explode(',', $source, 2);
            if (str_contains($meta, ';base64')) {
                $binary = base64_decode($data, true);
                $temp = tempnam(sys_get_temp_dir(), 'pdfme-image');
                file_put_contents($temp, $binary);
                return $temp;
            }
        }

        return $source;
    }
}
