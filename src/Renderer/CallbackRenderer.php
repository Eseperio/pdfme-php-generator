<?php

namespace Pdfme\Generator\Renderer;

use Pdfme\Generator\RenderContext;
use Pdfme\Generator\RenderResult;

class CallbackRenderer implements Renderer
{
    /** @var callable */
    private $callback;

    /**
     * @param callable(RenderContext, array, array): RenderResult $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function render(RenderContext $context, array $element, array $data): RenderResult
    {
        return ($this->callback)($context, $element, $data);
    }
}
