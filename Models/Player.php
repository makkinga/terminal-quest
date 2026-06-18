<?php

namespace Models;

class Player
{
    public int $x;
    public int $y;
    public int $hp;
    public int $coins;
    public bool $wasHit = false;

    public function __construct(int $x = 7, int $y = 7, int $hp = 5, int $coins = 0)
    {
        $this->x = $x;
        $this->y = $y;
        $this->hp = $hp;
        $this->coins = $coins;
    }

    public function isAlive(): bool
    {
        return $this->hp > 0;
    }

    public function takeDamage(int $amount = 1): void
    {
        $this->hp = max(0, $this->hp - $amount);
    }

    public function collectCoin(): void
    {
        $this->coins++;
    }

    public function placeAtEdge(string $edge): void
    {
        [$this->x, $this->y] = match ($edge) {
            'top' => [17, 1],
            'bottom' => [17, 16],
            'left' => [1, 9],
            'right' => [32, 9],
        };
    }
}
