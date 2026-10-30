<?php

namespace App\Experiments\P8;

use PDO;
use RuntimeException;

/** Explicit sandbox migrations, deliberately outside Laravel's default migration path. */
final class Schema
{
    public static function apply(PDO $pdo, string $role): void
    {
        SandboxGuard::database($pdo->query('SELECT DATABASE()')->fetchColumn());
        if (! in_array($role, ['producer', 'consumer'], true)) {
            throw new RuntimeException('p8_invalid_role');
        }
        $sql = file_get_contents(dirname(__DIR__, 3).'/experiments/p8/database/001_'.$role.'.sql');
        $exists = $pdo->query("SHOW TABLES LIKE 'p8_schema_migrations'")->fetchColumn();
        if ($exists) {
            SandboxGuard::role($pdo, $role);
            $query = $pdo->prepare('SELECT sha256 FROM p8_schema_migrations WHERE role=? AND version=1');
            $query->execute([$role]);
            if (! hash_equals(hash('sha256', $sql), $query->fetchColumn())) {
                throw new RuntimeException('p8_migration_checksum_changed');
            }

            return;
        }
        foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) as $statement) {
            if (trim($statement) !== '') {
                $pdo->exec($statement);
            }
        }
        $pdo->prepare('INSERT INTO p8_schema_migrations (role,version,sha256) VALUES (?,1,?)')->execute([$role, hash('sha256', $sql)]);
    }
}
