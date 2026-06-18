<?php

namespace Utils;

class Terminal
{
    private static int $minimumWidth = 158;
    private static int $minimumHeight = 28;
    private static int $width;
    private static int $heigth;

    public static function enableRawMode(): void
    {
        system('stty -icanon -echo');

        register_shutdown_function([self::class, 'disableRawMode']);
    }

    public static function disableRawMode(): void
    {
        system('stty icanon echo');
    }

    public static function readKey(): string
    {
        return fread(STDIN, 1);
    }

    public static function getWidth(): int
    {
        if (!isset(self::$width)) self::setDimensions();

        return self::$width;
    }

    public static function getHeight(): int
    {
        if (!isset(self::$heigth)) self::setDimensions();

        return self::$heigth;
    }

    public static function checkDimensions(): void
    {
        if (self::getWidth() < self::$minimumWidth) {
            self::line('Terminal too small, make it at least 158 columns wide.');

            exit(1);
        }

        if (self::getHeight() < self::$minimumHeight) {
            self::line('Terminal too small, make it at least 28 lines high.');

            exit(1);
        }
    }

    public static function clear(): void
    {
        echo "\033[2J";
    }

    public static function home(): void
    {
        echo "\033[H";
    }

    public static function centerOutput(string $output): string
    {
        $lines  = explode("\n", $output);
        $maxLength = max(array_map('mb_strlen', $lines));
        $paddingLength = max(0, (int) ((self::$width - $maxLength) / 2));
        $padding = str_repeat(' ', $paddingLength);

        return implode("\n", array_map(fn($l) => $padding . $l, $lines));
    }

    public static function setTitle(string $type = 'main'): void
    {
        echo self::centerOutput(Color::wrap(Config::get("titles.$type"), $type === 'dead' ? 'red' : ''));
    }

    public static function line(string $value, bool $center = false): void
    {
        echo $center ? self::centerOutput($value) : $value;
        echo "\n";
    }

    public static function emptyLine(): void
    {
        self::line("\n");
    }

    public static function error(?string $message = null): void
    {
        if ($message) {
            self::line($message);
        }

        exit(1);
    }

    public static function quit(): void
    {
        self::clear();
        self::home();
        self::line('Bye!');

        exit(0);
    }

    private static function setDimensions(): void
    {
        self::$width = (int) exec('tput cols');
        self::$heigth = (int) exec('tput lines');
    }
}
