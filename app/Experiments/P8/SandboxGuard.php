<?php

namespace App\Experiments\P8;

use PDO;
use RuntimeException;

final class SandboxGuard
{
    public static function database(string $name): void
    {
        if (! preg_match('/^apple_project_p1_fresh_[a-f0-9]{12}$/D', $name)) {
            throw new RuntimeException('p8_sandbox_required');
        }
    }

    public static function connect(array $settings): PDO
    {
        self::database($settings['database'] ?? '');
        if (! in_array($settings['host'] ?? '', ['127.0.0.1', 'localhost', '::1'], true)
            || ! ctype_digit((string) ($settings['port'] ?? '')) || (int) $settings['port'] < 1 || (int) $settings['port'] > 65535) {
            throw new RuntimeException('p8_local_mysql_required');
        }

        return new PDO('mysql:host='.$settings['host'].';port='.$settings['port'].';dbname='.$settings['database'].';charset=utf8mb4',
            $settings['username'], $settings['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
    }

    public static function role(PDO $pdo, string $role): void
    {
        self::database($pdo->query('SELECT DATABASE()')->fetchColumn());
        $query = $pdo->prepare('SELECT COUNT(*) FROM p8_schema_migrations WHERE role=? AND version=1');
        $query->execute([$role]);
        if ((int) $query->fetchColumn() !== 1) {
            throw new RuntimeException('p8_wrong_database_role');
        }
    }

    public static function spool(string $directory): string
    {
        $root = realpath(dirname(__DIR__, 3).'/tests/.runtime');
        $path = realpath($directory);
        if (! $root || ! $path || dirname($path) !== $root || is_link($directory)
            || ! preg_match('/^p8-spool-[a-f0-9]{12}$/D', basename($path))) {
            throw new RuntimeException('p8_private_spool_required');
        }

        return $path;
    }
}
