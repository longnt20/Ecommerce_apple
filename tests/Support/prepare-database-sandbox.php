<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

if ($argc !== 2 || $argv[1] !== '--provision-from-env') {
    fwrite(STDERR, "Usage: php tests/Support/prepare-database-sandbox.php --provision-from-env\n");
    exit(2);
}

try {
    $sandbox = new Tests\Support\DatabaseSandbox;
    $root = dirname(__DIR__, 2);
    if (! is_dir($root.'/tests/.runtime')) {
        mkdir($root.'/tests/.runtime', 0777, true);
    }
    if (! is_dir($root.'/database/schema')) {
        mkdir($root.'/database/schema', 0777, true);
    }
    $path = $root.'/database/schema/mysql-schema.sql';
    if (is_file($path)) {
        // A reviewed baseline is immutable; an upgraded live schema is not its replacement.
        $snapshot = file_get_contents($path);
    } else {
        $snapshot = $sandbox->schemaSnapshot();
        file_put_contents($path, $snapshot);
    }
    $sandbox->provisionP0($snapshot);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Sandbox preparation failed: '.get_class($exception).' / '.$exception->getCode().". Credentials and SQL are intentionally omitted.\n");
    exit(1);
}
