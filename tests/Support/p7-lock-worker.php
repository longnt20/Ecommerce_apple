<?php

require __DIR__.'/../../vendor/autoload.php';

try {
    $connection = json_decode(fgets(STDIN), true, flags: JSON_THROW_ON_ERROR);
    if (! preg_match('/^apple_project_p1_fresh_[a-f0-9]{12}$/D', $connection['database'])
        || ! in_array($connection['host'], ['127.0.0.1', 'localhost', '::1'], true)) {
        throw new RuntimeException('Non-sandbox lock probe rejected.');
    }
    $pdo = new PDO('mysql:host='.$connection['host'].';port='.$connection['port'].';dbname='.$connection['database'],
        $connection['username'], $connection['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec('SET SESSION innodb_lock_wait_timeout=5');
    $pdo->beginTransaction();
    echo "READY\n";
    flush();
    $start = hrtime(true);
    $pdo->query('SELECT id FROM inventory WHERE id=3000001 FOR UPDATE')->fetchColumn();
    $elapsed = (hrtime(true) - $start) / 1e6;
    $pdo->rollBack();
    echo json_encode(['lock_query_ms' => round($elapsed, 3)]);
} catch (Throwable $error) {
    fwrite(STDERR, $error::class.'; details withheld.');
    exit(1);
}
