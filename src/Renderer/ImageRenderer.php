<?php

namespace Pdfme\Generator\Renderer;

use Pdfme\Generator\RenderContext;
use Pdfme\Generator\RenderResult;

class ImageRenderer implements Renderer
{
    public function render(RenderContext $context, array $element, array $data): RenderResult
    {
        $mpdf = $context->mpdf();
        $source = (string) ($element['src'] ?? '');
        $x = (float) ($element['x'] ?? 0);
        $y = (float) ($element['y'] ?? 0);
        $width = (float) ($element['width'] ?? 0);
        $height = (float) ($element['height'] ?? 0);

        $mpdf->Image($this->resolveSource($source), $x, $y, $width, $height, '', '', true, false);

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
