<?php

namespace Models;

use Utils\Color;
use Utils\Config;
use Utils\Terminal;
use Models\Monster;
use Models\Obstacle;

class Room
{
    private const GRID_WIDTH = 34;
    private const GRID_HEIGHT = 18;

    public int $id;
    public array $blueprint;
    public array $grid;
    public array $coins = [];
    public array $obstacles = [];
    public array $monsters = [];
    public ?array $enterPos;
    public ?array $exitPos;
    public ?array $startPos;

    public function __construct(int $id)
    {
        $this->id = $id;
        $this->load();
    }

    public function placePlayer(Player $player): void
    {
        if ($this->startPos !== null) {
            $player->x = $this->startPos['x'];
            $player->y = $this->startPos['y'];

            return;
        }

        if ($this->enterPos !== null) {
            $player->x = $this->enterPos['x'];
            $player->y = $this->enterPos['y'];
        }
    }

    public function render(Player $player): void
    {
        Terminal::checkDimensions();
        Terminal::clear();
        Terminal::home();
        Terminal::setTitle($player->isAlive() ? 'main' : 'dead');
        Terminal::emptyLine();

        if ($player->isAlive()) {
            $this->renderFrame($player);
        } else {
            Terminal::line('Do you want to start over? [Y]es or [N]o?', center: true);
        }
    }

    public function isFloor(int $x, int $y): bool
    {
        return ($this->grid[$y][$x] ?? 1) === 0;
    }

    public function isExit(int $x, int $y): bool
    {
        return $this->exitPos !== null
            && $this->exitPos['x'] === $x
            && $this->exitPos['y'] === $y;
    }

    public function coinAt(int $x, int $y): bool
    {
        $key = "$y,$x";
        if (isset($this->coins[$key])) {
            unset($this->coins[$key]);

            return true;
        }

        return false;
    }

    public function obstacleAt(int $x, int $y): ?Obstacle
    {
        foreach ($this->obstacles as $obstacle) {
            if ($obstacle->x === $x && $obstacle->y === $y) return $obstacle;
        }

        return null;
    }

    public function monsterAt(int $x, int $y): ?Monster
    {
        foreach ($this->monsters as $monster) {
            if ($monster->x === $x && $monster->y === $y) return $monster;
        }

        return null;
    }

    public function pruneMonsters(): void
    {
        $this->monsters = array_values(array_filter(
            $this->monsters,
            fn(Monster $monster) => $monster->isAlive()
        ));
    }

    private function load(): void
    {
        $this->loadBlueprint();

        $this->makeBaseGrid();

        $this->placeInternalWalls();
        $this->placeEntrance();
        $this->placeExit();
        $this->placeCoins();
        $this->placeObstacles();
        $this->placeMonsters();
        $this->placeStart();
    }

    private function loadBlueprint(): void
    {
        if (!$rooms = Config::get('rooms')) {
            Terminal::error('Rooms config not found');
        }

        $this->blueprint = $rooms[$this->id] ?? null;

        if ($this->blueprint === null) {
            die("Unknown room: {$this->id}\n");
        }
    }

    private function renderFrame(Player $player): void
    {
        $mapWidth = self::GRID_WIDTH * 2;
        $paddingLength = max(0, (int) ((Terminal::getWidth() - $mapWidth) / 2));
        $padding = str_repeat(' ', $paddingLength);

        echo $this->getCreateModeColumnCoordinates($padding);

        $roomWidth = self::GRID_WIDTH;
        $roomHeight = self::GRID_HEIGHT;
        $statusLines = $this->getStatusLines($player);
        $obstaclesByPosition = $this->getIndexedByPosition($this->obstacles);
        $monstersByPosition = $this->getIndexedByPosition($this->monsters);

        foreach ($this->grid as $y => $row) {
            $line = $padding;

            $line .= $this->getCreateModeRowCoordinates($y);

            foreach ($row as $x => $cell) {
                $line .= $this->renderCell(
                    x: $x,
                    y: $y,
                    cell: $cell,
                    player: $player,
                    obstaclesByPosition: $obstaclesByPosition,
                    monstersByPosition: $monstersByPosition,
                    roomWidth: $roomWidth,
                    roomHeight: $roomHeight
                );
            }

            echo $line . '   ' . ($statusLines[$y] ?? '') . "\n";
        }
    }

    private function renderCell(int $x, int $y, int $cell, Player $player, array $obstaclesByPosition, array $monstersByPosition, int $roomWidth, int $roomHeight): string
    {
        $key = "$y,$x";

        if ($x === $player->x && $y === $player->y) {
            return Color::wrap('b', $player->wasHit ? 'red' : 'blue') . ' ';
        }

        if (isset($obstaclesByPosition[$key])) {
            $obstacle = $obstaclesByPosition[$key];

            return Color::wrap($obstacle->glyph, $obstacle->color) . ' ';
        }

        if (isset($monstersByPosition[$key])) {
            $monster = $monstersByPosition[$key];

            return Color::wrap($monster->glyph, $monster->color) . ' ';
        }

        if ($this->exitPos !== null && $x === $this->exitPos['x'] && $y === $this->exitPos['y']) {
            return Color::wrap('>', 'green') . ' ';
        }

        if ($this->enterPos !== null && $x === $this->enterPos['x'] && $y === $this->enterPos['y']) {
            return Color::wrap('<', 'gray') . ' ';
        }

        if (isset($this->coins[$key])) {
            return Color::wrap('●', 'yellow') . ' ';
        }

        if ($cell === 1) {
            $glyph = $this->wallGlyphAt($x, $y, $roomWidth, $roomHeight);
            $connector = (($y === 0 || $y === $roomHeight - 1) && $x !== $roomWidth - 1) ? '═' : ' ';

            return $glyph . $connector;
        }

        return '  ';
    }

    private function wallGlyphAt(int $x, int $y, int $roomWidth, int $roomHeight): string
    {
        if ($y === 0) {
            return $x === 0 ? '╔' : ($x === $roomWidth - 1 ? '╗' : '═');
        }

        if ($y === $roomHeight - 1) {
            return $x === 0 ? '╚' : ($x === $roomWidth - 1 ? '╝' : '═');
        }

        return $x === 0 || $x === $roomWidth - 1 ? '║' : '#';
    }

    private function getIndexedByPosition(array $objects): array
    {
        $indexed = [];

        foreach ($objects as $object) {
            $indexed["{$object->y},{$object->x}"] = $object;
        }

        return $indexed;
    }

    private function makeBaseGrid(): void
    {
        for ($y = 0; $y < self::GRID_HEIGHT; $y++) {
            for ($x = 0; $x < self::GRID_WIDTH; $x++) {
                $this->grid[$y][$x] = ($y === 0 || $y === self::GRID_HEIGHT - 1 || $x === 0 || $x === self::GRID_WIDTH - 1) ? 1 : 0;
            }
        }
    }

    private function placeInternalWalls(): void
    {
        foreach ($this->expandPositions($this->blueprint['walls'] ?? []) as [$x, $y]) {
            $this->grid[$y][$x] = 1;
        }
    }

    private function placeEntrance(): void
    {
        $this->enterPos = null;

        if (!empty($this->blueprint['enter'])) {
            $this->enterPos = [
                'x' => $this->blueprint['enter']['x'],
                'y' => $this->blueprint['enter']['y'],
            ];

            $this->grid[$this->enterPos['y']][$this->enterPos['x']] = 0;
        }
    }

    private function placeExit(): void
    {
        $this->exitPos = null;

        if (!empty($this->blueprint['exit'])) {
            $this->exitPos = [
                'x' => $this->blueprint['exit']['x'],
                'y' => $this->blueprint['exit']['y'],
                'to' => $this->blueprint['exit']['to'],
            ];

            $this->grid[$this->exitPos['y']][$this->exitPos['x']] = 0;
        }
    }

    private function placeCoins(): void
    {
        foreach ($this->expandPositions($this->blueprint['coins'] ?? []) as [$x, $y]) {
            $this->coins["$y,$x"] = true;
        }
    }

    private function placeObstacles(): void
    {
        foreach ($this->expandPositions($this->blueprint['obstacles'] ?? []) as $obstacle) {
            $this->obstacles[] = new Obstacle(
                x: $obstacle['x'],
                y: $obstacle['y'],
                damage: $obstacle['damage'],
                glyph: $obstacle['glyph'],
                color: $obstacle['color']
            );
        }
    }

    private function placeMonsters(): void
    {
        foreach ($this->blueprint['monsters'] ?? [] as $monster) {
            $this->monsters[] = new Monster(
                x: $monster['x'],
                y: $monster['y'],
                hp: $monster['hp'],
                damage: $monster['damage'],
                glyph: $monster['glyph'],
                color: $monster['color']
            );
        }
    }

    private function expandPositions(array $items): array
    {
        $expanded = [];

        foreach ($items as $item) {
            if (isset($item['from'])) {
                [$fromX, $fromY] = $item['from'];
                [$toX, $toY] = $item['to'];

                for ($x = min($fromX, $toX); $x <= max($fromX, $toX); $x++) {
                    for ($y = min($fromY, $toY); $y <= max($fromY, $toY); $y++) {
                        $expanded[] = $this->withPosition($item, $x, $y);
                    }
                }
            } else {
                $expanded[] = $item;
            }
        }

        return $expanded;
    }

    private function withPosition(array $item, int $x, int $y): array
    {
        unset($item['from'], $item['to']);

        if (array_is_list($item)) {
            return [$x, $y];
        }

        return [...$item, 'x' => $x, 'y' => $y];
    }

    private function placeStart(): void
    {
        $start = $this->blueprint['start'] ?? null;

        $this->startPos = $start === null
            ? null
            : ['x' => $start[0], 'y' => $start[1]];
    }

    private function getCreateModeColumnCoordinates(string $padding): ?string
    {
        if (!CREATING) return null;

        $columnHeader = $padding . '   ';
        for ($x = 0; $x < self::GRID_WIDTH; $x++) {
            $columnHeader .= $x % 2 == 0 ? Color::get('gray') : Color::get('reset');
            $columnHeader .= str_pad($x, 2, ' ', STR_PAD_RIGHT);
        }

        return $columnHeader . Color::get('reset') . "\n";
    }

    private function getCreateModeRowCoordinates(int $y): ?string
    {
        if (!CREATING) return null;

        return str_pad($y, 2, ' ', STR_PAD_LEFT) . ' ';
    }

    private function getStatusLines(Player $player): array
    {
        return [
            '',
            sprintf('%s: %s', str_pad('HP', 6, ' ', STR_PAD_RIGHT), Color::wrap(str_pad(str_repeat('♥ ', $player->hp), 5, ' '), 'red')),
            sprintf('%s: %s', str_pad('Coins', 6, ' ', STR_PAD_RIGHT), Color::wrap($player->coins, 'yellow')),
            sprintf('%s: %s', str_pad('Room', 6, ' ', STR_PAD_RIGHT), $this->id),
            '',
            Color::wrap('[hjkl] move', 'gray'),
            Color::wrap('[q] quit', 'gray'),
            '',
            $this->exitPos !== null ? sprintf('%s %s', Color::wrap('>', 'green'), 'next room →') : Color::wrap('final room', 'gray'),
            $this->enterPos !== null ? Color::wrap('< entrance', 'gray') : Color::wrap('* start', 'gray'),
        ];
    }
}
