<?php

namespace Pdfme\Generator\Renderer;

use Pdfme\Generator\RenderContext;
use Pdfme\Generator\RenderResult;

interface Renderer
{
    public function render(RenderContext $context, array $element, array $data): RenderResult;
}
