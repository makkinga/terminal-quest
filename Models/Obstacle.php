<?php

namespace Models;

class Obstacle
{
    public int $x;
    public int $y;
    public int $damage;
    public string $glyph;
    public string $color;

    public function __construct(int $x, int $y, int $damage = 1, string $glyph = '~', string $color = 'red')
    {
        $this->x = $x;
        $this->y = $y;
        $this->damage = $damage;
        $this->glyph = $glyph;
        $this->color = $color;
    }
}
