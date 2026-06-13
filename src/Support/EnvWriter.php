<?php

namespace Vendor\LaravelWhatsAppCloud\Support;

class EnvWriter
{
    public static function set(string $key, string $value, ?string $path = null): bool
    {
        $path ??= base_path('.env');

        if (! is_file($path)) {
            return false;
        }

        $content = (string) file_get_contents($path);
        $line = $key.'='.$value;
        $pattern = '/^'.preg_quote($key, '/').'=.*/m';

        if (preg_match($pattern, $content)) {
            $content = (string) preg_replace($pattern, $line, $content);
        } else {
            $content = rtrim($content).PHP_EOL.$line.PHP_EOL;
        }

        return file_put_contents($path, $content) !== false;
    }
}
