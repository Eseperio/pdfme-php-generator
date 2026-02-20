<?php

namespace eseperio\PdfmeGenerator\Renderer;

use InvalidArgumentException;

class RendererRegistry
{
    /** @var array<string, Renderer> */
    private array $renderers = [];

    public function register(string $type, Renderer $renderer): self
    {
        $this->renderers[$type] = $renderer;
        return $this;
    }

    public function has(string $type): bool
    {
        return array_key_exists($type, $this->renderers);
    }

    public function get(string $type): Renderer
    {
        if (!$this->has($type)) {
            throw new InvalidArgumentException("Renderer for type {$type} not found.");
        }

        return $this->renderers[$type];
    }

    public static function withDefaultRenderers(): self
    {
        $registry = new self();
        $registry->register('text', new TextRenderer());
        $registry->register('rectangle', new RectangleRenderer());
        $registry->register('line', new LineRenderer());
        $registry->register('image', new ImageRenderer());

        return $registry;
    }
}
