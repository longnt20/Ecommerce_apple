<?php

require __DIR__.'/../../vendor/autoload.php';

putenv('P0_DB_MODE=disabled');
Tests\Support\TestApplication::create();
$sandbox = new Tests\Support\P7Sandbox;
try {
    $sandbox->catalog();
    $result = Tests\Support\P7LockWait::measure($sandbox->connection);
    $result['recorded_at_utc'] = gmdate(DATE_ATOM);
    file_put_contents(dirname(__DIR__, 2).'/docs/architecture/p7-lock-wait.json', json_encode($result, JSON_PRETTY_PRINT)."\n");
    echo "Synthetic lock wait measured; no business rows changed.\n";
} finally {
    $sandbox->close();
}
