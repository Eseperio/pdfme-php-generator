<?php

namespace Pdfme\Generator;

class RenderResult
{
    public function __construct(
        private float $x,
        private float $y,
        private float $width,
        private float $height
    ) {
    }

    public static function fromElement(array $element): self
    {
        $x = isset($element['x']) ? (float) $element['x'] : 0.0;
        $y = isset($element['y']) ? (float) $element['y'] : 0.0;
        $width = isset($element['width']) ? (float) $element['width'] : 0.0;
        $height = isset($element['height']) ? (float) $element['height'] : 0.0;

        return new self($x, $y, $width, $height);
    }

    public function x(): float
    {
        return $this->x;
    }

    public function y(): float
    {
        return $this->y;
    }

    public function width(): float
    {
        return $this->width;
    }

    public function height(): float
    {
        return $this->height;
    }
}
