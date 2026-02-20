<?php

namespace eseperio\PdfmeGenerator\Renderer;

use eseperio\PdfmeGenerator\RenderContext;
use eseperio\PdfmeGenerator\RenderResult;

class CallbackRenderer implements Renderer
{
    /** @var callable */
    private $callback;

    /**
     * @param callable(RenderContext, array): RenderResult $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function render(RenderContext $context, array $element): RenderResult
    {
        return ($this->callback)($context, $element);
    }
}
