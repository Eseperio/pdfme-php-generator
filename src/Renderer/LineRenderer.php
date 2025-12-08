<?php

namespace Pdfme\Generator\Renderer;

use Pdfme\Generator\RenderContext;
use Pdfme\Generator\RenderResult;

class LineRenderer implements Renderer
{
    public function render(RenderContext $context, array $element, array $data): RenderResult
    {
        $mpdf = $context->mpdf();
        $x1 = (float) ($element['x'] ?? 0);
        $y1 = (float) ($element['y'] ?? 0);

        if (isset($element['x2']) && isset($element['y2'])) {
            $x2 = (float) $element['x2'];
            $y2 = (float) $element['y2'];
        } else {
            $x2 = $x1 + (float) ($element['width'] ?? 0);
            $y2 = $y1 + (float) ($element['height'] ?? 0);
        }

        $mpdf->Line($x1, $y1, $x2, $y2);

        return new RenderResult($x1, $y1, $x2 - $x1, $y2 - $y1);
    }
}
