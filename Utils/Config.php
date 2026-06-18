<?php

namespace Utils;

class Config
{
    private static array $config;

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!isset(self::$config)) self::load();

        $segments = explode('.', $key);
        $config = self::$config;

        foreach ($segments as $segment) {
            if (!array_key_exists($segment, $config)) {
                return $default;
            }

            $config = $config[$segment];
        }

        return $config;
    }

    private static function load(): void
    {
        foreach (glob(__DIR__ . '/../config/*.php') as $file) {
            $key = basename($file, '.php');
            self::$config[$key] = require $file;
        }
    }
}
