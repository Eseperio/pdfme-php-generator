<?php

namespace eseperio\PdfmeGenerator\Renderer;

use eseperio\PdfmeGenerator\RenderContext;
use eseperio\PdfmeGenerator\RenderResult;

interface Renderer
{
    public function render(RenderContext $context, array $element): RenderResult;
}
