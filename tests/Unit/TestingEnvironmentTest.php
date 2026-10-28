<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\TestingEnvironment;

class TestingEnvironmentTest extends TestCase
{
    #[DataProvider('unsafeSettings')]
    public function test_rejects_unsafe_database_configuration_before_boot(array $override): void
    {
        $this->expectException(RuntimeException::class);
        TestingEnvironment::validate(array_replace($this->validSettings(), $override));
    }

    public static function unsafeSettings(): array
    {
        return [
            'application database' => [['database' => 'laravel']],
            'arbitrary test database' => [['database' => 'production_test']],
            'root account' => [['username' => 'root']],
            'remote host' => [['host' => 'db.example.com']],
            'empty password' => [['password' => '']],
            'invalid port' => [['port' => '3306;dbname=laravel']],
            'unknown mode' => [['mode' => 'sqlite']],
        ];
    }

    public function test_accepts_only_the_dedicated_mysql_configuration(): void
    {
        $settings = $this->validSettings();
        $this->assertSame($settings, TestingEnvironment::validate($settings));
    }

    public function test_install_discards_inherited_application_connection_and_url(): void
    {
        putenv('DB_DATABASE=laravel');
        putenv('DB_USERNAME=root');
        putenv('DB_URL=mysql://application-user@localhost/laravel');
        putenv('APP_ENV=production');

        try {
            TestingEnvironment::install(dirname(__DIR__, 2));
            $this->assertSame('testing', getenv('APP_ENV'));
            $this->assertSame('p0_disabled', getenv('DB_CONNECTION'));
            $this->assertSame('', getenv('DB_DATABASE'));
            $this->assertSame('', getenv('DB_USERNAME'));
            $this->assertSame('', getenv('DB_URL'));
        } finally {
            TestingEnvironment::install(dirname(__DIR__, 2));
        }
    }

    private function validSettings(): array
    {
        return [
            'mode' => 'mysql', 'host' => '127.0.0.1', 'port' => '3306',
            'database' => 'apple_project_p0_tests', 'username' => 'apple_project_p0_test',
            'password' => 'synthetic-test-password',
        ];
    }
}
