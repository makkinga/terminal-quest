<?php

namespace Models;

class Monster
{
    public int $x;
    public int $y;
    public int $hp;
    public int $damage;
    public string $glyph;
    public string $color;

    public function __construct(int $x, int $y, int $hp, int $damage, string $glyph, string $color)
    {
        $this->x = $x;
        $this->y = $y;
        $this->hp = $hp;
        $this->damage = $damage;
        $this->glyph = $glyph;
        $this->color = $color;
    }

    public function isAlive(): bool
    {
        return $this->hp > 0;
    }

    public function takeDamage(int $amount = 1): void
    {
        $this->hp = max(0, $this->hp - $amount);
    }
}
