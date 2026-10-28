<?php

namespace Tests\Support;

use PDO;
use RuntimeException;

final class MySqlSafety
{
    public static function verify(array $settings): void
    {
        TestingEnvironment::validate($settings);
        if ($settings['mode'] !== 'mysql') {
            throw new RuntimeException('P0 MySQL preflight requires explicit mysql mode.');
        }
        $pdo = new PDO(
            'mysql:host='.$settings['host'].';port='.$settings['port'].';dbname='.$settings['database'].';charset=utf8mb4',
            $settings['username'], $settings['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3],
        );

        $identity = $pdo->query('SELECT DATABASE() AS db, CURRENT_USER() AS account')->fetch(PDO::FETCH_ASSOC);
        if ($identity['db'] !== 'apple_project_p0_tests' || ! str_starts_with($identity['account'], 'apple_project_p0_test@')) {
            throw new RuntimeException('P0 refuses the resolved MySQL database/account.');
        }

        $hasSchemaGrant = false;
        foreach ($pdo->query('SHOW GRANTS FOR CURRENT_USER')->fetchAll(PDO::FETCH_COLUMN) as $grant) {
            if (! self::isAllowedGrant($grant)) {
                throw new RuntimeException('P0 requires a test account with only SELECT/INSERT/UPDATE/DELETE on its dedicated schema, without roles or global privileges.');
            }
            if (! str_starts_with($grant, 'GRANT USAGE ON *.* TO ')) {
                $hasSchemaGrant = true;
            }
        }

        if (! $hasSchemaGrant) {
            throw new RuntimeException('P0 test schema privileges are missing.');
        }

        $requiredTables = ['users', 'carts', 'cart_items', 'categories', 'products', 'product_variants', 'product_attributes', 'warehouses', 'inventory', 'inventory_transactions', 'orders', 'order_items'];
        $engines = $pdo->query('SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()')->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ($requiredTables as $table) {
            if (($engines[$table] ?? null) !== 'InnoDB') {
                throw new RuntimeException('P0 requires the existing fixture tables to use InnoDB so test transactions can roll back. No schema is created automatically.');
            }
        }
        $externalReferences = $pdo->query('SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_SCHEMA IS NOT NULL AND REFERENCED_TABLE_SCHEMA <> DATABASE()')->fetchColumn();
        if ((int) $externalReferences !== 0) {
            throw new RuntimeException('P0 refuses foreign keys referencing a different schema.');
        }
    }

    public static function isAllowedGrant(string $grant): bool
    {
        if (str_contains($grant, 'WITH GRANT OPTION')) {
            return false;
        }
        if (str_starts_with($grant, 'GRANT USAGE ON *.* TO ')) {
            return true;
        }
        $grant = str_replace('\\_', '_', $grant);
        if (! preg_match('/^GRANT ([A-Z, ]+) ON `apple_project_p0_tests`\.\* TO /', $grant, $match)) {
            return false;
        }

        return array_diff(explode(', ', $match[1]), ['SELECT', 'INSERT', 'UPDATE', 'DELETE']) === [];
    }
}
