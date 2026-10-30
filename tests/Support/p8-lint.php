<?php

require __DIR__.'/../../vendor/autoload.php';

$paths = ['config/p8.php', 'bootstrap/providers.php', 'app/Modules/ModuleServiceProvider.php',
    'app/Modules/Catalog/Contracts/CatalogProjectionEvents.php', 'app/Modules/Catalog/Infrastructure/NoCatalogProjectionEvents.php',
    'app/Modules/Catalog/Infrastructure/SaveProduct.php', 'tests/Support/TestApplication.php',
    'tests/Feature/P7/OperationsConfigurationTest.php', 'tests/Support/P8Sandbox.php',
    'tests/Support/benchmark-p8.php', 'tests/Support/p8-lint.php', 'tests/Support/p8-producer-worker.php',
    'tests/Support/record-p8-verification.php'];
foreach (['app/Experiments/P8/*.php', 'app/Experiments/P8/Contracts/*.php', 'experiments/p8/*.php',
    'tests/Feature/P8/*.php', 'tests/Integration/P8/*.php', 'tests/Unit/P8/*.php'] as $pattern) {
    $paths = [...$paths, ...glob(dirname(__DIR__, 2).'/'.$pattern)];
}
$process = new Symfony\Component\Process\Process([PHP_BINARY, 'vendor/bin/pint', ...(($argv[1] ?? null) === '--fix' ? [] : ['--test']), ...array_unique($paths)], dirname(__DIR__, 2));
$process->setTimeout(180);
exit($process->run(fn ($type, $output) => print ($output)));
