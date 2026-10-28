<?php

namespace Tests\Support;

use Dotenv\Dotenv;
use PDO;
use RuntimeException;

final class DatabaseSandbox
{
    private array $settings;

    private PDO $source;

    /** @var list<string> */
    private array $created = [];

    public function __construct()
    {
        $this->settings = Dotenv::parse(file_get_contents(dirname(__DIR__, 2).'/.env'));
        if (($this->settings['DB_CONNECTION'] ?? null) !== 'mysql' || ! empty($this->settings['DB_URL'])
            || ! in_array($this->settings['DB_HOST'] ?? '', ['127.0.0.1', 'localhost', '::1'], true)) {
            throw new RuntimeException('Sandbox provisioning supports only the explicit local MySQL connection in .env.');
        }
        $this->source = $this->connect($this->settings['DB_DATABASE']);
    }

    public function source(): PDO
    {
        return $this->source;
    }

    public function sourceIdentityHash(): string
    {
        return hash('sha256', json_encode([
            $this->settings['DB_HOST'], $this->settings['DB_PORT'], $this->settings['DB_DATABASE'],
            $this->source->query('SELECT @@server_uuid')->fetchColumn(),
        ]));
    }

    public function connectionConfig(string $database): array
    {
        $this->assertSandbox($database);

        return [
            'driver' => 'mysql', 'host' => $this->settings['DB_HOST'], 'port' => $this->settings['DB_PORT'],
            'database' => $database, 'username' => $this->settings['DB_USERNAME'], 'password' => $this->settings['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4', 'collation' => 'utf8mb4_0900_ai_ci', 'prefix' => '', 'strict' => true,
        ];
    }

    public function connectSandbox(string $database): PDO
    {
        $this->assertSandbox($database);

        return $this->connect($database);
    }

    public function create(string $database): PDO
    {
        $this->assertSandbox($database);
        $query = $this->source->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
        $query->execute([$database]);
        if ((int) $query->fetchColumn() !== 0) {
            throw new RuntimeException('Refusing to overwrite an existing sandbox database.');
        }
        $this->source->exec('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
        $this->created[] = $database;

        return $this->connectSandbox($database);
    }

    public function schemaSnapshot(): string
    {
        return self::schemaSnapshotFrom($this->source);
    }

    public static function schemaSnapshotFrom(PDO $connection): string
    {
        $crossSchema = $connection->query('SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_SCHEMA IS NOT NULL AND REFERENCED_TABLE_SCHEMA <> DATABASE()')->fetchColumn();
        if ((int) $crossSchema !== 0) {
            throw new RuntimeException('Cross-schema references require a reviewed cloning plan.');
        }
        $tables = $connection->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = \'BASE TABLE\' ORDER BY TABLE_NAME')->fetchAll(PDO::FETCH_COLUMN);
        $sql = "-- Laravel MySQL baseline, reviewed from the 2026-10-28 schema.\n-- Schema and migration metadata only; no business data or database selection.\nSET FOREIGN_KEY_CHECKS=0;\n";
        foreach ($tables as $table) {
            if (! preg_match('/^[a-z_]+$/', $table)) {
                throw new RuntimeException('Unexpected table identifier.');
            }
            $ddl = $connection->query('SHOW CREATE TABLE `'.$table.'`')->fetch(PDO::FETCH_NUM)[1];
            $ddl = preg_replace('/\sAUTO_INCREMENT=\d+/', '', $ddl);
            $sql .= $ddl.";\n\n";
        }
        foreach ($connection->query('SELECT id, migration, batch FROM migrations ORDER BY id')->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $sql .= 'INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('.(int) $row['id'].', '.$connection->quote($row['migration']).', '.(int) $row['batch'].");\n";
        }

        return $sql."SET FOREIGN_KEY_CHECKS=1;\n";
    }

    public function dropCreated(): void
    {
        foreach (array_reverse($this->created) as $database) {
            $this->assertSandbox($database);
            if ($database === 'apple_project_p0_tests') {
                continue; // Persistent P0 fixture schema/account is retained.
            }
            $this->source->exec('DROP DATABASE `'.$database.'`');
        }
        $this->created = [];
    }

    public static function loadSnapshot(PDO $target, string $sql): void
    {
        $database = $target->query('SELECT DATABASE()')->fetchColumn();
        if (! preg_match('/^apple_project_p(?:0_tests|1_(?:fresh|upgrade|restore)_[a-f0-9]{12})$/', $database)) {
            throw new RuntimeException('Schema loading is restricted to sandbox databases.');
        }
        if (preg_match('/\b(?:USE|DROP\s+DATABASE|CREATE\s+DATABASE)\b/i', $sql)) {
            throw new RuntimeException('Snapshot must not select, create or delete databases.');
        }
        // Generated SHOW CREATE statements do not contain semicolons inside values.
        foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) as $statement) {
            if (trim($statement) !== '') {
                $target->exec($statement);
            }
        }
    }

    public function provisionP0(string $snapshot): void
    {
        $root = dirname(__DIR__, 2);
        $receipt = $root.'/tests/.runtime/p0-sandbox.json';
        if (is_file($receipt) && is_file($root.'/.env.testing')) {
            echo "P0 sandbox already provisioned; existing configuration retained.\n";

            return;
        }
        if (is_file($root.'/.env.testing')) {
            throw new RuntimeException('Existing .env.testing requires review; not overwriting local credentials.');
        }
        $account = $this->source->query("SELECT COUNT(*) FROM mysql.user WHERE User='apple_project_p0_test'")->fetchColumn();
        if ((int) $account !== 0) {
            throw new RuntimeException('Dedicated account already exists without a local receipt; review required.');
        }
        $target = $this->create('apple_project_p0_tests');
        self::loadSnapshot($target, $snapshot);
        $password = bin2hex(random_bytes(24));
        $this->source->exec("CREATE USER 'apple_project_p0_test'@'localhost' IDENTIFIED BY ".$this->source->quote($password));
        $this->source->exec("GRANT SELECT, INSERT, UPDATE, DELETE ON `apple_project_p0_tests`.* TO 'apple_project_p0_test'@'localhost'");
        $environment = "P0_DB_MODE=mysql\nP0_TEST_DB_HOST=".$this->settings['DB_HOST']."\nP0_TEST_DB_PORT=".$this->settings['DB_PORT']."\nP0_TEST_DB_DATABASE=apple_project_p0_tests\nP0_TEST_DB_USERNAME=apple_project_p0_test\nP0_TEST_DB_PASSWORD=".$password."\n";
        file_put_contents($root.'/.env.testing', $environment, LOCK_EX);
        file_put_contents($receipt, json_encode(['database' => 'apple_project_p0_tests', 'schema_sha256' => hash('sha256', $snapshot)], JSON_PRETTY_PRINT));
        echo "P0 sandbox created from schema only; restricted account configured in ignored .env.testing.\n";
    }

    private function assertSandbox(string $database): void
    {
        if ($database === $this->settings['DB_DATABASE']
            || ! preg_match('/^apple_project_p(?:0_tests|1_(?:fresh|upgrade|restore)_[a-f0-9]{12})$/', $database)) {
            throw new RuntimeException('Target is not an allowlisted sandbox distinct from the application database.');
        }
    }

    private function connect(string $database): PDO
    {
        foreach ([$database, $this->settings['DB_HOST'], $this->settings['DB_PORT']] as $part) {
            if (! preg_match('/^[a-zA-Z0-9_.:-]+$/', (string) $part)) {
                throw new RuntimeException('Unsupported MySQL DSN component.');
            }
        }

        return new PDO(
            'mysql:host='.$this->settings['DB_HOST'].';port='.$this->settings['DB_PORT'].';dbname='.$database.';charset=utf8mb4',
            $this->settings['DB_USERNAME'], $this->settings['DB_PASSWORD'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5],
        );
    }
}
