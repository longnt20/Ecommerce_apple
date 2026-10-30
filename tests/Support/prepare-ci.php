<?php

require __DIR__.'/../../vendor/autoload.php';

$root = dirname(__DIR__, 2);
if (getenv('GITHUB_ACTIONS') !== 'true' || getenv('CI') !== 'true' || PHP_OS_FAMILY !== 'Linux'
    || is_file($root.'/.env') || is_file($root.'/.env.testing')) {
    fwrite(STDERR, "CI bootstrap requires a clean Linux GitHub Actions workspace; existing env files are never overwritten.\n");
    exit(2);
}
try {
    $port = getenv('CI_MYSQL_PORT');
    if (! ctype_digit((string) $port) || (int) $port < 1 || (int) $port > 65535) {
        throw new RuntimeException('Invalid isolated CI service port.');
    }
    // Public disposable CI service credential, never an application secret.
    file_put_contents($root.'/.env', "APP_ENV=testing\nAPP_KEY=base64:".base64_encode(str_repeat('p', 32))."\nDB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_PORT=$port\nDB_DATABASE=apple_project_ci_source\nDB_USERNAME=root\nDB_PASSWORD=p7-ci-disposable\nTELESCOPE_ENABLED=false\nMAIL_MAILER=array\nLOG_CHANNEL=null\n");
    putenv('P0_DB_MODE=disabled');
    Tests\Support\TestApplication::create();
    $sandbox = new Tests\Support\DatabaseSandbox;
    if ((int) $sandbox->source()->query('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()')->fetchColumn() !== 0) {
        throw new RuntimeException('CI source marker must be an empty disposable schema.');
    }
    $sandbox->provisionP0(file_get_contents($root.'/database/schema/mysql-schema.sql'));
    // Docker port mapping reaches MySQL from the runner bridge, not MySQL localhost.
    $test = Dotenv\Dotenv::parse(file_get_contents($root.'/.env.testing'));
    $pdo = $sandbox->source();
    $pdo->exec("CREATE USER 'apple_project_p0_test'@'%' IDENTIFIED BY ".$pdo->quote($test['P0_TEST_DB_PASSWORD']));
    $pdo->exec("GRANT SELECT, INSERT, UPDATE, DELETE ON `apple_project_p0_tests`.* TO 'apple_project_p0_test'@'%'");
    echo "Isolated CI source marker and restricted test account prepared; no business data imported.\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'CI bootstrap failed: '.$error::class.'; details withheld.'."\n");
    exit(1);
}
