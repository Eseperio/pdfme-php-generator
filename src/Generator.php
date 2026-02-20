<?php

namespace eseperio\PdfmeGenerator;

use Mpdf\Mpdf;
use eseperio\PdfmeGenerator\Renderer\RendererRegistry;

class Generator
{
    private RendererRegistry $registry;
    private array $mpdfConfig;

    public function __construct(?RendererRegistry $registry = null, array $mpdfConfig = [])
    {
        $this->registry = $registry ?? RendererRegistry::withDefaultRenderers();
        $this->mpdfConfig = $mpdfConfig;
    }

    /**
     * Generate a PDF from a pdfme template and input data.
     *
     * @param array $template  pdfme template: ['basePdf' => ..., 'schemas' => [...]]
     *                         `basePdf` can be a blank-page spec array
     *                         (['width' => mm, 'height' => mm, 'padding' => [T, R, B, L]])
     *                         or a base64-encoded PDF string.
     *                         `schemas` is an array of pages; each page is an array of
     *                         schema element definitions.
     * @param array  $inputs   Array of input records. Each record is a flat key→value map
     *                         where keys match schema element `name` properties.
     *                         If multiple records are provided each one produces a page set;
     *                         currently only the first record is used for all pages.
     *
     * @return string Raw PDF binary string
     */
    public function generate(array $template, array $inputs = []): string
    {
        $schemas = $template['schemas'] ?? [];
        if ($schemas === []) {
            return '';
        }

        $basePdf = $template['basePdf'] ?? [];

        // Resolve data: first input record wins; fall back to sampledata from template
        $inputData = $inputs[0] ?? $template['sampledata'][0] ?? [];

        $mpdf = new Mpdf($this->buildMpdfConfig($basePdf));
        if (array_key_exists('compress', $this->mpdfConfig) && $this->mpdfConfig['compress'] === false) {
            $mpdf->SetCompression(false);
        }

        $context = new RenderContext($mpdf, $inputData, $this->registry);

        foreach ($schemas as $pageSchemas) {
            $mpdf->AddPageByArray($this->buildPageDefinition($basePdf));
            $this->renderPage($context, (array) $pageSchemas);
        }

        return $mpdf->Output('', 'S');
    }

    private function renderPage(RenderContext $context, array $schemas): void
    {
        foreach ($schemas as $element) {
            $type = (string) ($element['type'] ?? '');
            if (!$context->registry()->has($type)) {
                continue;
            }
            $renderer = $context->registry()->get($type);
            $result = $renderer->render($context, $element);
            $context->setLastResult($result);
        }
    }

    private function buildMpdfConfig(mixed $basePdf): array
    {
        $config = $this->mpdfConfig;

        if (is_array($basePdf)) {
            if (isset($basePdf['width']) && isset($basePdf['height'])) {
                $config['format'] = [(float) $basePdf['width'], (float) $basePdf['height']];
            }

            // padding: [top, right, bottom, left] or a single number
            if (isset($basePdf['padding'])) {
                [$mt, $mr, $mb, $ml] = $this->normalisePadding($basePdf['padding']);
                $config['margin_top']    = $mt;
                $config['margin_right']  = $mr;
                $config['margin_bottom'] = $mb;
                $config['margin_left']   = $ml;
            } else {
                // No margins: elements use absolute page coordinates
                $config['margin_top']    = $config['margin_top']    ?? 0;
                $config['margin_right']  = $config['margin_right']  ?? 0;
                $config['margin_bottom'] = $config['margin_bottom'] ?? 0;
                $config['margin_left']   = $config['margin_left']   ?? 0;
            }
        }

        if (!isset($config['tempDir'])) {
            $config['tempDir'] = sys_get_temp_dir();
        }

        return $config;
    }

    private function buildPageDefinition(mixed $basePdf): array
    {
        $definition = ['orientation' => 'P'];

        if (is_array($basePdf)) {
            if (isset($basePdf['width']) && isset($basePdf['height'])) {
                $definition['sheet-size'] = [(float) $basePdf['width'], (float) $basePdf['height']];
            }

            if (isset($basePdf['padding'])) {
                [$mt, $mr, $mb, $ml] = $this->normalisePadding($basePdf['padding']);
                $definition['margin-top']    = $mt;
                $definition['margin-right']  = $mr;
                $definition['margin-bottom'] = $mb;
                $definition['margin-left']   = $ml;
            } else {
                $definition['margin-top']    = 0;
                $definition['margin-right']  = 0;
                $definition['margin-bottom'] = 0;
                $definition['margin-left']   = 0;
            }
        }

        return $definition;
    }

    /**
     * Normalise a padding value to a [top, right, bottom, left] tuple (in mm).
     *
     * @param  int|float|array $padding
     * @return array{0: float, 1: float, 2: float, 3: float}
     */
    private function normalisePadding(mixed $padding): array
    {
        if (is_array($padding)) {
            return [
                (float) ($padding[0] ?? 0),
                (float) ($padding[1] ?? 0),
                (float) ($padding[2] ?? 0),
                (float) ($padding[3] ?? 0),
            ];
        }

        $p = (float) $padding;
        return [$p, $p, $p, $p];
    }
}
