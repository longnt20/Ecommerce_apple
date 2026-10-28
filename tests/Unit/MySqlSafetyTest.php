<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\MySqlSafety;

class MySqlSafetyTest extends TestCase
{
    #[DataProvider('grants')]
    public function test_grant_policy_blocks_global_cross_database_and_ddl_privileges(string $grant, bool $allowed): void
    {
        $this->assertSame($allowed, MySqlSafety::isAllowedGrant($grant));
    }

    public static function grants(): array
    {
        return [
            ['GRANT USAGE ON *.* TO `apple_project_p0_test`@`localhost`', true],
            ['GRANT SELECT, INSERT, UPDATE, DELETE ON `apple_project_p0_tests`.* TO `apple_project_p0_test`@`localhost`', true],
            ['GRANT ALL PRIVILEGES ON *.* TO `root`@`localhost`', false],
            ['GRANT SELECT ON `laravel`.* TO `apple_project_p0_test`@`localhost`', false],
            ['GRANT SELECT, CREATE, DROP ON `apple_project_p0_tests`.* TO `apple_project_p0_test`@`localhost`', false],
            ['GRANT SELECT ON `apple_project_p0_tests`.* TO `apple_project_p0_test`@`localhost` WITH GRANT OPTION', false],
            ['GRANT `application_role`@`%` TO `apple_project_p0_test`@`localhost`', false],
        ];
    }
}
