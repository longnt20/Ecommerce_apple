<?php

namespace Tests\Support;

use Dotenv\Dotenv;
use RuntimeException;

final class TestingEnvironment
{
    private static array $database = [];

    public static function install(string $root): void
    {
        // Parse only the test file; never load the application's .env into a test.
        $path = $root.'/.env.testing';
        $local = is_file($path) ? Dotenv::parse(file_get_contents($path)) : [];
        $read = static function (string $key, string $default) use ($local): string {
            $value = getenv($key);

            return $value !== false ? $value : (string) ($local[$key] ?? $default);
        };

        self::$database = self::validate([
            'mode' => $read('P0_DB_MODE', 'disabled'),
            'host' => $read('P0_TEST_DB_HOST', '127.0.0.1'),
            'port' => $read('P0_TEST_DB_PORT', '3306'),
            'database' => $read('P0_TEST_DB_DATABASE', 'apple_project_p0_tests'),
            'username' => $read('P0_TEST_DB_USERNAME', 'apple_project_p0_test'),
            'password' => $read('P0_TEST_DB_PASSWORD', ''),
        ]);

        $environment = [
            'APP_ENV' => 'testing',
            'APP_KEY' => 'base64:'.base64_encode(str_repeat('p', 32)),
            'APP_DEBUG' => 'false',
            'DB_CONNECTION' => 'p0_disabled',
            'DB_URL' => '',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306',
            'DB_DATABASE' => '',
            'DB_USERNAME' => '',
            'DB_PASSWORD' => '',
            'DB_SOCKET' => '',
            'CACHE_STORE' => 'array',
            'SESSION_DRIVER' => 'array',
            'QUEUE_CONNECTION' => 'sync',
            'MAIL_MAILER' => 'array',
            'LOG_CHANNEL' => 'null',
            'TELESCOPE_ENABLED' => 'false',
            'APP_URL' => 'http://localhost',
        ];

        if (! is_dir($root.'/tests/.runtime') && ! mkdir($root.'/tests/.runtime', 0777, true) && ! is_dir($root.'/tests/.runtime')) {
            throw new RuntimeException('Cannot create the isolated P0 test cache directory.');
        }

        foreach (['CONFIG', 'ROUTES', 'EVENTS', 'SERVICES', 'PACKAGES'] as $cache) {
            $path = $root.'/tests/.runtime/'.strtolower($cache).'.php';
            if (in_array($cache, ['CONFIG', 'ROUTES', 'EVENTS'], true) && is_file($path)) {
                throw new RuntimeException('P0 refuses cached test configuration. Remove only tests/.runtime cache files.');
            }
            $environment['APP_'.$cache.'_CACHE'] = 'tests/.runtime/'.strtolower($cache).'.php';
        }

        foreach ($environment as $key => $value) {
            putenv($key.'='.$value);
            $_ENV[$key] = $_SERVER[$key] = $value;
        }
    }

    public static function validate(array $settings): array
    {
        if (! in_array($settings['mode'] ?? null, ['disabled', 'mysql'], true)) {
            throw new RuntimeException('P0_DB_MODE must be disabled or mysql.');
        }

        if ($settings['mode'] === 'mysql' && (
            ! in_array($settings['host'] ?? '', ['127.0.0.1', 'localhost', '::1'], true)
            || ! ctype_digit((string) ($settings['port'] ?? ''))
            || (int) $settings['port'] < 1 || (int) $settings['port'] > 65535
            || ($settings['database'] ?? '') !== 'apple_project_p0_tests'
            || ($settings['username'] ?? '') !== 'apple_project_p0_test'
            || ($settings['password'] ?? '') === ''
        )) {
            throw new RuntimeException('P0 MySQL requires the allowlisted local test database and a dedicated password-protected test account.');
        }

        return $settings;
    }

    public static function database(): array
    {
        return self::$database;
    }
}
