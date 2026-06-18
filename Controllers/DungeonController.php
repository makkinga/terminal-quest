<?php

namespace Controllers;

use Models\Room;
use Models\Player;
use Utils\Terminal;

class DungeonController
{
    private static Room $room;
    private static Player $player;
    private static string $input;

    public static function start(Room $room, Player $player): void
    {
        self::$room = $room;
        self::$player = $player;

        while (true) {
            self::$room->render(self::$player);
            self::$player->wasHit = false;

            self::$input = Terminal::readKey();

            if (self::$player->isAlive()) {
                self::handleAliveInput();
            } else {
                self::handleDeadInput();
            }

            if (self::$room->coinAt(self::$player->x, self::$player->y)) {
                self::$player->collectCoin();
            }
        }
    }

    private static function handleAliveInput(): void
    {
        $newX = self::$player->x;
        $newY = self::$player->y;

        switch (self::$input) {
            case 'h':
                $newX--;
                break;
            case 'j':
                $newY++;
                break;
            case 'k':
                $newY--;
                break;
            case 'l':
                $newX++;
                break;
            case 'q':
                Terminal::quit();
                break;
            default:
                return;
        }

        if (self::$room->isExit($newX, $newY)) {
            $nextId = self::$room->exitPos['to'];
            self::$room = new Room($nextId);
            self::$room->placePlayer(self::$player);

            Terminal::clear();

            return;
        }

        self::handleInteraction($newX, $newY);
    }

    private static function handleDeadInput(): void
    {
        switch (self::$input) {
            case 'y':
                self::$room = new Room(1);
                self::$player = new Player();

                self::$room->placePlayer(self::$player);
                break;
            case 'n':
                Terminal::quit();
                break;
        }
    }

    private static function handleInteraction(int $x, int $y): void
    {
        $obstacle = self::$room->obstacleAt($x, $y);
        if ($obstacle !== null) {
            self::$player->takeDamage($obstacle->damage);
            self::$player->wasHit = true;
            return;
        }

        $monster = self::$room->monsterAt($x, $y);
        if ($monster !== null) {
            self::$player->takeDamage($monster->damage);
            self::$player->wasHit = true;
            $monster->takeDamage(1);
            self::$room->pruneMonsters();
            return;
        }

        if (self::$room->isFloor($x, $y)) {
            self::$player->x = $x;
            self::$player->y = $y;
        }
    }
}
