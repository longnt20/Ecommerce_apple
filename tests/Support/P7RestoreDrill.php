<?php

namespace Tests\Support;

use PDO;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

final class P7RestoreDrill
{
    public static function run(P7Sandbox $sandbox): array
    {
        $connection = $sandbox->connection;
        $source = $sandbox->admin->connectSandbox($connection['database']);
        $finder = new ExecutableFinder;
        $mysql = glob(dirname(PHP_BINARY, 3).'/mysql/*/bin/mysql.exe')[0] ?? $finder->find('mysql');
        $dump = glob(dirname(PHP_BINARY, 3).'/mysql/*/bin/mysqldump.exe')[0] ?? $finder->find('mysqldump');
        if (! $mysql || ! $dump) {
            throw new \RuntimeException('Installed MySQL clients required for restore rehearsal.');
        }
        $backup = base_path('tests/.runtime/p7-synthetic-backup-'.bin2hex(random_bytes(6)).'.sql');
        $arguments = ['--host='.$connection['host'], '--port='.$connection['port'], '--user='.$connection['username']];
        $environment = ['MYSQL_PWD' => $connection['password']];
        $before = self::fingerprint($source);
        $runtime = null;
        try {
            $process = new Process([$dump, ...$arguments, '--single-transaction', '--skip-lock-tables', '--hex-blob',
                '--set-gtid-purged=OFF', '--column-statistics=0', '--no-tablespaces', '--result-file='.$backup, $connection['database']],
                base_path(), $environment);
            if ($process->run() !== 0 || ! is_file($backup) || filesize($backup) === 0) {
                throw new \RuntimeException('Synthetic full backup failed.');
            }
            $stream = fopen($backup, 'rb');
            while (($line = fgets($stream)) !== false) {
                if (preg_match('/^\s*(USE\s|CREATE\s+DATABASE\b|DROP\s+DATABASE\b)/i', $line)) {
                    throw new \RuntimeException('Database selection in backup rejected.');
                }
            }
            rewind($stream);
            $name = 'apple_project_p1_restore_'.bin2hex(random_bytes(6));
            $target = $sandbox->admin->create($name);
            $restore = new Process([$mysql, ...$arguments, '--default-character-set=utf8mb4', $name], base_path(), $environment);
            try {
                if ($restore->setInput($stream)->run() !== 0) {
                    throw new \RuntimeException('Synthetic restore failed.');
                }
            } finally {
                fclose($stream);
            }
            if ($before !== self::fingerprint($target)) {
                throw new \RuntimeException('Restored table data differs.');
            }
            if (DatabaseSandbox::schemaSnapshotFrom($source) !== DatabaseSandbox::schemaSnapshotFrom($target)) {
                throw new \RuntimeException('Restored schema/FK differs.');
            }
            // Check every FK: re-enabling FOREIGN_KEY_CHECKS alone does not validate old rows.
            $foreignKeys = $target->query('SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($foreignKeys as $fk) {
                foreach ($fk as $identifier) {
                    if (! preg_match('/^[a-z_]+$/D', $identifier)) {
                        throw new \RuntimeException('Unexpected FK identifier.');
                    }
                }
                $orphans = $target->query('SELECT COUNT(*) FROM `'.$fk['TABLE_NAME'].'` c LEFT JOIN `'.$fk['REFERENCED_TABLE_NAME'].'` p ON c.`'.$fk['COLUMN_NAME'].'` = p.`'.$fk['REFERENCED_COLUMN_NAME'].'` WHERE c.`'.$fk['COLUMN_NAME'].'` IS NOT NULL AND p.`'.$fk['REFERENCED_COLUMN_NAME'].'` IS NULL')->fetchColumn();
                if ((int) $orphans !== 0) {
                    throw new \RuntimeException('Orphan detected after restore.');
                }
            }
            $runtime = new P7Runtime($sandbox->admin->connectionConfig($name));
            $runtime->run(['inventory:reconcile']);

            return ['table_count' => count($before), 'fk_count' => count($foreignKeys), 'backup_bytes' => filesize($backup),
                'all_table_data_equal' => true, 'schema_and_fk_equal' => true, 'orphan_count' => 0, 'reconcile_exit' => 0];
        } finally {
            $runtime?->close();
            if (is_file($backup)) {
                unlink($backup); // Synthetic artifact only; never touch real backups.
            }
        }
    }

    private static function fingerprint(PDO $pdo): array
    {
        $result = [];
        foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
            if (! preg_match('/^[a-z_]+$/D', $table)) {
                throw new \RuntimeException('Unexpected table identifier.');
            }
            $rows = $pdo->query('SELECT * FROM `'.$table.'`')->fetchAll(PDO::FETCH_ASSOC);
            $rows = array_map(fn ($row) => json_encode($row, JSON_THROW_ON_ERROR), $rows);
            sort($rows);
            $result[$table] = hash('sha256', json_encode($rows));
        }
        ksort($result);

        return $result;
    }
}
