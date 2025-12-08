<?php

namespace Pdfme\Generator;

use Mpdf\Mpdf;
use Pdfme\Generator\Renderer\RendererRegistry;

class Generator
{
    private RendererRegistry $registry;
    private array $mpdfConfig;

    public function __construct(?RendererRegistry $registry = null, array $mpdfConfig = [])
    {
        $this->registry = $registry ?? RendererRegistry::withDefaultRenderers();
        $this->mpdfConfig = $mpdfConfig;
    }

    public function generate(array $layout, array $data = []): string
    {
        $pages = $layout['pages'] ?? $layout;
        if (!is_array($pages) || $pages === []) {
            return '';
        }

        $firstPage = $pages[0];
        $mpdf = new Mpdf($this->buildMpdfConfig($firstPage));
        if (array_key_exists('compress', $this->mpdfConfig) && $this->mpdfConfig['compress'] === false) {
            $mpdf->SetCompression(false);
        }

        $context = new RenderContext($mpdf, $data, $this->registry);

        foreach ($pages as $index => $page) {
            if ($index === 0) {
                $this->applyPageSetup($mpdf, $page);
            }

            $mpdf->AddPageByArray($this->buildPageDefinition($page));
            $this->renderPage($context, $page, $data);
        }

        return $mpdf->Output('', 'S');
    }

    private function renderPage(RenderContext $context, array $page, array $data): void
    {
        $elements = $page['elements'] ?? [];
        foreach ($elements as $element) {
            $type = (string) ($element['type'] ?? '');
            $renderer = $context->registry()->get($type);
            $result = $renderer->render($context, $element, $data);
            $context->setLastResult($result);
        }
    }

    private function buildMpdfConfig(array $page): array
    {
        $config = $this->mpdfConfig;

        if (isset($page['orientation'])) {
            $config['orientation'] = strtoupper((string) $page['orientation']);
        }

        if (isset($page['width']) && isset($page['height'])) {
            $config['format'] = [(float) $page['width'], (float) $page['height']];
        }

        if (!isset($config['tempDir'])) {
            $config['tempDir'] = sys_get_temp_dir();
        }

        return $config;
    }

    private function buildPageDefinition(array $page): array
    {
        $definition = [];

        $definition['orientation'] = strtoupper((string) ($page['orientation'] ?? $this->mpdfConfig['orientation'] ?? 'P'));

        if (isset($page['width']) && isset($page['height'])) {
            $definition['sheet-size'] = [(float) $page['width'], (float) $page['height']];
        }

        if (isset($page['margin'])) {
            $definition['margin-left'] = (float) ($page['margin']['left'] ?? 15);
            $definition['margin-right'] = (float) ($page['margin']['right'] ?? 15);
            $definition['margin-top'] = (float) ($page['margin']['top'] ?? 16);
            $definition['margin-bottom'] = (float) ($page['margin']['bottom'] ?? 16);
        }

        return $definition;
    }

    private function applyPageSetup(Mpdf $mpdf, array $page): void
    {
        if (!isset($page['margin'])) {
            return;
        }

        $mpdf->SetMargins(
            (float) ($page['margin']['left'] ?? 15),
            (float) ($page['margin']['right'] ?? 15),
            (float) ($page['margin']['top'] ?? 16)
        );
        $mpdf->SetAutoPageBreak(true, (float) ($page['margin']['bottom'] ?? 16));
    }
}
