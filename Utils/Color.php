<?php

namespace Utils;

class Color
{
    private static array $map = [
        'black' => "\033[30m",
        'red' => "\033[31m",
        'green' => "\033[32m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'magenta' => "\033[35m",
        'cyan' => "\033[36m",
        'white' => "\033[37m",
        'gray' => "\033[30m",
        'bright_red' => "\033[31m",
        'bright_green' => "\033[32m",
        'bright_yellow' => "\033[33m",
        'bright_blue' => "\033[34m",
        'bright_magenta' => "\033[35m",
        'bright_cyan' => "\033[36m",
        'bright_white' => "\033[37m",
        'reset' => "\033[0m",
    ];

    public static function get(string $name): string
    {
        return self::$map[$name] ?? self::$map['reset'];
    }

    public static function wrap(string $text, string $name): string
    {
        if ($name === '') return $text;

        return self::get($name) . $text . self::$map['reset'];
    }
}
