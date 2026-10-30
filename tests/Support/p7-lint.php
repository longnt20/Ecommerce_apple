<?php

require __DIR__.'/../../vendor/autoload.php';

// Explicit paths keep legacy formatting work outside this phase.
$paths = ['app/Http/Controllers/Admin/BannerController.php', 'app/Http/Controllers/Admin/InventoryController.php',
    'app/Http/Controllers/Admin/ProductController.php', 'app/Http/Controllers/Api/HomeController.php',
    'app/Jobs/AssignCouponJob.php', 'app/Jobs/NotifyCouponAssignment.php', 'app/Logging/RedactSensitiveData.php',
    'app/Observers/CatalogReadCacheObserver.php', 'app/Observers/ProductAttributeObserver.php',
    'app/Providers/AppServiceProvider.php', 'app/Providers/TelescopeServiceProvider.php',
    'app/Reporting/InventoryReadQuery.php', 'app/Reporting/OperationsHealth.php', 'routes/console.php', 'app/Services/CatalogReadCache.php', 'app/Services/ProductAttributeService.php',
    'app/Traits/LoggableTrait.php', 'bootstrap/app.php', 'config/logging.php', 'config/queue.php', 'config/telescope.php'];
foreach (['tests/Integration/P7/*.php', 'tests/Unit/P7/*.php', 'tests/Feature/P7/*.php', 'tests/Support/P7*.php', 'tests/Support/*p7*.php', 'tests/Support/prepare-ci.php'] as $pattern) {
    $paths = [...$paths, ...glob(dirname(__DIR__, 2).'/'.$pattern)];
}
$process = new Symfony\Component\Process\Process([PHP_BINARY, 'vendor/bin/pint', ...(($argv[1] ?? null) === '--fix' ? [] : ['--test']), ...array_unique($paths)], dirname(__DIR__, 2));
$process->setTimeout(180);
exit($process->run(fn ($type, $output) => print ($output)));
