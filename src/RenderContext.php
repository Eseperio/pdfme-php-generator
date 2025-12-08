<?php

namespace Pdfme\Generator;

use Mpdf\Mpdf;
use Pdfme\Generator\Renderer\RendererRegistry;

class RenderContext
{
    private ?RenderResult $lastResult = null;

    public function __construct(
        private Mpdf $mpdf,
        private array $data,
        private RendererRegistry $registry
    ) {
    }

    public function mpdf(): Mpdf
    {
        return $this->mpdf;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function registry(): RendererRegistry
    {
        return $this->registry;
    }

    public function resolveText(string $content): string
    {
        return preg_replace_callback('/{{\s*([\w\.]+)\s*}}/', function ($matches) {
            $path = $matches[1];
            $value = $this->valueFromPath($path);
            return is_scalar($value) ? (string) $value : '';
        }, $content) ?? $content;
    }

    public function valueFromPath(string $path, mixed $default = ''): mixed
    {
        $segments = explode('.', $path);
        $value = $this->data;

        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
                continue;
            }

            return $default;
        }

        return $value;
    }

    public function lastResult(): ?RenderResult
    {
        return $this->lastResult;
    }

    public function setLastResult(RenderResult $result): void
    {
        $this->lastResult = $result;
    }
}
