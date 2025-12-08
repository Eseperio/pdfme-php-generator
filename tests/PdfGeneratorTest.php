<?php

namespace Pdfme\Generator\Tests;

use Pdfme\Generator\Generator;
use Pdfme\Generator\Renderer\CallbackRenderer;
use Pdfme\Generator\Renderer\RendererRegistry;
use Pdfme\Generator\RenderResult;
use PHPUnit\Framework\TestCase;

class PdfGeneratorTest extends TestCase
{
    public function testGeneratesPdfFromLayout(): void
    {
        $layout = [
            'pages' => [
                [
                    'width' => 210,
                    'height' => 297,
                    'elements' => [
                        ['type' => 'rectangle', 'x' => 10, 'y' => 10, 'width' => 60, 'height' => 20, 'borderColor' => '#000000'],
                        ['type' => 'text', 'x' => 12, 'y' => 12, 'width' => 56, 'height' => 8, 'text' => 'Hello {{user.name}}', 'fontSize' => 12],
                        ['type' => 'line', 'x' => 10, 'y' => 32, 'width' => 60, 'height' => 0],
                    ],
                ],
            ],
        ];

        $generator = new Generator(null, ['compress' => false]);
        $binary = $generator->generate($layout, ['user' => ['name' => 'Pdfme']]);

        $this->assertNotEmpty($binary);
        $this->assertStringStartsWith('%PDF-', $binary);
    }

    public function testCustomRendererReceivesContext(): void
    {
        $registry = RendererRegistry::withDefaultRenderers();
        $captured = [];

        $registry->register('custom-note', new CallbackRenderer(function ($context, $element, $data) use (&$captured) {
            $mpdf = $context->mpdf();
            $last = $context->lastResult();
            $y = $last ? $last->y() + $last->height() + 2 : (float) ($element['y'] ?? 0);

            $captured['resolvedText'] = $context->resolveText($element['text'] ?? '');
            $captured['alignedFrom'] = $last?->y();

            $mpdf->SetFont('dejavusans', '', 10);
            $mpdf->SetXY($element['x'], $y);
            $mpdf->MultiCell($element['width'], 5, $context->resolveText($element['text'] ?? ''));

            $captured['y'] = $y;
            $captured['height'] = 5.0;

            return new RenderResult($element['x'], $y, $element['width'], 5);
        }));

        $layout = [
            'pages' => [
                [
                    'elements' => [
                        ['type' => 'text', 'x' => 10, 'y' => 10, 'width' => 80, 'text' => 'Title'],
                        ['type' => 'custom-note', 'x' => 10, 'y' => 20, 'width' => 80, 'text' => 'Note {{number}}'],
                    ],
                ],
            ],
        ];

        $generator = new Generator($registry, ['compress' => false]);
        $binary = $generator->generate($layout, ['number' => 7]);

        $this->assertNotEmpty($binary);
        $this->assertStringStartsWith('%PDF-', $binary);
        $this->assertSame('Note 7', $captured['resolvedText'] ?? null);
        $this->assertNotNull($captured['alignedFrom'] ?? null);
        $this->assertGreaterThan(($captured['alignedFrom'] ?? 0), $captured['y'] ?? 0);
    }
}
