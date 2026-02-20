<?php

namespace eseperio\PdfmeGenerator\Tests;

use eseperio\PdfmeGenerator\Generator;
use eseperio\PdfmeGenerator\Renderer\CallbackRenderer;
use eseperio\PdfmeGenerator\Renderer\RendererRegistry;
use eseperio\PdfmeGenerator\RenderResult;
use PHPUnit\Framework\TestCase;

class PdfGeneratorTest extends TestCase
{
    public function testGeneratesPdfFromTemplate(): void
    {
        $template = [
            'basePdf' => ['width' => 210, 'height' => 297, 'padding' => [10, 10, 10, 10]],
            'schemas' => [
                [
                    [
                        'name' => 'greeting',
                        'type' => 'text',
                        'position' => ['x' => 12, 'y' => 12],
                        'width' => 56,
                        'height' => 8,
                        'fontSize' => 12,
                        'fontColor' => '#000000',
                        'alignment' => 'left',
                    ],
                    [
                        'name' => 'border',
                        'type' => 'rectangle',
                        'position' => ['x' => 10, 'y' => 10],
                        'width' => 60,
                        'height' => 20,
                        'borderColor' => '#000000',
                    ],
                    [
                        'name' => 'divider',
                        'type' => 'line',
                        'position' => ['x' => 10, 'y' => 32],
                        'width' => 60,
                        'height' => 0,
                    ],
                ],
            ],
        ];

        $inputs = [['greeting' => 'Hello pdfme']];

        $generator = new Generator(null, ['compress' => false]);
        $binary = $generator->generate($template, $inputs);

        $this->assertNotEmpty($binary);
        $this->assertStringStartsWith('%PDF-', $binary);
    }

    public function testStaticContentFallback(): void
    {
        $template = [
            'basePdf' => ['width' => 210, 'height' => 297],
            'schemas' => [
                [
                    [
                        'name' => 'title',
                        'type' => 'text',
                        'content' => 'Static Title',
                        'position' => ['x' => 20, 'y' => 20],
                        'width' => 80,
                        'height' => 10,
                        'fontSize' => 14,
                    ],
                ],
            ],
        ];

        // No input: should fall back to element's static `content`
        $generator = new Generator(null, ['compress' => false]);
        $binary = $generator->generate($template);

        $this->assertNotEmpty($binary);
        $this->assertStringStartsWith('%PDF-', $binary);
    }

    public function testSampledataFallback(): void
    {
        $template = [
            'basePdf' => ['width' => 210, 'height' => 297],
            'schemas' => [
                [
                    [
                        'name' => 'note',
                        'type' => 'text',
                        'position' => ['x' => 10, 'y' => 10],
                        'width' => 80,
                        'height' => 10,
                    ],
                ],
            ],
            'sampledata' => [['note' => 'Sample note']],
        ];

        $generator = new Generator(null, ['compress' => false]);
        $binary = $generator->generate($template);

        $this->assertNotEmpty($binary);
        $this->assertStringStartsWith('%PDF-', $binary);
    }

    public function testCustomRendererReceivesContext(): void
    {
        $registry = RendererRegistry::withDefaultRenderers();
        $captured = [];

        $registry->register('custom-note', new CallbackRenderer(function ($context, $element) use (&$captured) {
            $mpdf = $context->mpdf();
            $last = $context->lastResult();
            $y = $last ? $last->y() + $last->height() + 2 : (float) ($element['position']['y'] ?? 0);

            $content = $context->resolveContent($element);
            $captured['resolvedContent'] = $content;
            $captured['alignedFrom'] = $last?->y();

            $mpdf->SetFont('dejavusans', '', 10);
            $mpdf->SetXY($element['position']['x'] ?? 0, $y);
            $mpdf->MultiCell($element['width'], 5, $content);

            $captured['y'] = $y;
            $captured['height'] = 5.0;

            return new RenderResult($element['position']['x'] ?? 0, $y, $element['width'], 5);
        }));

        $template = [
            'basePdf' => ['width' => 210, 'height' => 297],
            'schemas' => [
                [
                    [
                        'name' => 'title',
                        'type' => 'text',
                        'position' => ['x' => 10, 'y' => 10],
                        'width' => 80,
                        'height' => 8,
                        'content' => 'Title',
                    ],
                    [
                        'name' => 'number',
                        'type' => 'custom-note',
                        'position' => ['x' => 10, 'y' => 20],
                        'width' => 80,
                        'height' => 5,
                    ],
                ],
            ],
        ];

        $generator = new Generator($registry, ['compress' => false]);
        $binary = $generator->generate($template, [['number' => '7']]);

        $this->assertNotEmpty($binary);
        $this->assertStringStartsWith('%PDF-', $binary);
        $this->assertSame('7', $captured['resolvedContent'] ?? null);
        $this->assertNotNull($captured['alignedFrom'] ?? null);
        $this->assertGreaterThan(($captured['alignedFrom'] ?? 0), $captured['y'] ?? 0);
    }

    public function testTextRendererPdfmeFields(): void
    {
        $template = [
            'basePdf' => ['width' => 210, 'height' => 297],
            'schemas' => [
                [
                    [
                        'name' => 'styled',
                        'type' => 'text',
                        'position' => ['x' => 10, 'y' => 10],
                        'width' => 100,
                        'height' => 20,
                        'fontSize' => 14,
                        'fontColor' => '#FF0000',
                        'alignment' => 'center',
                        'lineHeight' => 1.5,
                        'bold' => true,
                    ],
                ],
            ],
        ];

        $generator = new Generator(null, ['compress' => false]);
        $binary = $generator->generate($template, [['styled' => 'Styled Text']]);

        $this->assertNotEmpty($binary);
        $this->assertStringStartsWith('%PDF-', $binary);
    }

    public function testRectangleRendererPdfmeFields(): void
    {
        $template = [
            'basePdf' => ['width' => 210, 'height' => 297],
            'schemas' => [
                [
                    [
                        'name' => 'box',
                        'type' => 'rectangle',
                        'position' => ['x' => 20, 'y' => 20],
                        'width' => 80,
                        'height' => 40,
                        'color' => '#CCCCCC',
                        'borderColor' => '#000000',
                        'borderWidth' => 1,
                    ],
                ],
            ],
        ];

        $generator = new Generator(null, ['compress' => false]);
        $binary = $generator->generate($template);

        $this->assertNotEmpty($binary);
        $this->assertStringStartsWith('%PDF-', $binary);
    }

    public function testLineRendererPdfmeFields(): void
    {
        $template = [
            'basePdf' => ['width' => 210, 'height' => 297],
            'schemas' => [
                [
                    [
                        'name' => 'separator',
                        'type' => 'line',
                        'position' => ['x' => 10, 'y' => 50],
                        'width' => 190,
                        'height' => 0,
                        'color' => '#999999',
                    ],
                ],
            ],
        ];

        $generator = new Generator(null, ['compress' => false]);
        $binary = $generator->generate($template);

        $this->assertNotEmpty($binary);
        $this->assertStringStartsWith('%PDF-', $binary);
    }

    public function testEmptySchemasReturnsEmptyString(): void
    {
        $generator = new Generator(null, ['compress' => false]);
        $binary = $generator->generate(['schemas' => []]);

        $this->assertSame('', $binary);
    }

    public function testMultiPageTemplate(): void
    {
        $template = [
            'basePdf' => ['width' => 210, 'height' => 297],
            'schemas' => [
                [
                    [
                        'name' => 'page1text',
                        'type' => 'text',
                        'position' => ['x' => 10, 'y' => 10],
                        'width' => 80,
                        'height' => 10,
                        'content' => 'Page 1',
                    ],
                ],
                [
                    [
                        'name' => 'page2text',
                        'type' => 'text',
                        'position' => ['x' => 10, 'y' => 10],
                        'width' => 80,
                        'height' => 10,
                        'content' => 'Page 2',
                    ],
                ],
            ],
        ];

        $generator = new Generator(null, ['compress' => false]);
        $binary = $generator->generate($template);

        $this->assertNotEmpty($binary);
        $this->assertStringStartsWith('%PDF-', $binary);
    }
}
