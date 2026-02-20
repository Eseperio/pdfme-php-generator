<?php

namespace eseperio\PdfmeGenerator;

use Mpdf\Mpdf;
use eseperio\PdfmeGenerator\Renderer\RendererRegistry;

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

    /**
     * Resolve element content: if the element has a `name` key that matches a key
     * in the current input data, that value is used. Otherwise falls back to
     * the element's own `content` property.
     */
    public function resolveContent(array $element): string
    {
        $name = $element['name'] ?? null;
        if ($name !== null && array_key_exists($name, $this->data)) {
            $value = $this->data[$name];
            return is_scalar($value) ? (string) $value : '';
        }

        return (string) ($element['content'] ?? '');
    }

    /**
     * Resolve a text string containing `{{ path.to.key }}` placeholders using
     * dot-notation lookup in the current data map.
     */
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
