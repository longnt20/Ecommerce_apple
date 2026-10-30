<?php

require dirname(__DIR__).'/vendor/autoload.php';

use Tests\Support\PortfolioDemo;
use Tests\Support\PortfolioScenario;
use Tests\Support\TestApplication;

$mode = $argv[1] ?? 'verify';
if (! in_array($mode, ['verify', 'serve', 'cleanup'], true)) {
    fwrite(STDERR, "Usage: php scripts/portfolio-demo.php [verify|serve] [--frontend] [--seconds=900] | cleanup <id>\n");
    exit(2);
}
putenv('P0_DB_MODE=disabled');
TestApplication::create();
$demo = null;
try {
    if ($mode === 'cleanup') {
        PortfolioDemo::cleanup($argv[2] ?? '');
        echo "Owned demo runtime removed.\n";
        exit(0);
    }
    $demo = new PortfolioDemo;
    $demo->start();
    $frontendPort = in_array('--frontend', $argv, true) ? $demo->startFrontend() : null;
    if ($mode === 'verify') {
        $report = PortfolioScenario::run($demo);
        $report['verified_at_utc'] = gmdate(DATE_ATOM);
        $report['vite_proxy_verified'] = $frontendPort !== null;
        $demo->close();
        $report['runtime_cleaned'] = ! is_dir($demo->directory);
        $demo = null;
        if (! is_dir(base_path('docs/portfolio'))) {
            mkdir(base_path('docs/portfolio'), 0755, true);
        }
        file_put_contents(base_path('docs/portfolio/demo-verification.json'), json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n");
        echo json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n";
    } else {
        $seconds = 900;
        foreach ($argv as $argument) {
            if (preg_match('/^--seconds=(\d+)$/D', $argument, $match)) {
                $seconds = max(1, min(3600, (int) $match[1]));
            }
        }
        echo 'Admin: http://127.0.0.1:'.$demo->port."/login\n";
        if ($frontendPort) {
            echo 'Storefront: http://127.0.0.1:'.$frontendPort."\n";
        }
        echo "Synthetic accounts: admin@example.test / buyer@example.test\nPassword: ".PortfolioDemo::PASSWORD."\n";
        echo 'Runtime: '.$demo->directory."\nAuto-cleanup after ".$seconds." seconds; allow normal exit.\n";
        echo 'Recovery after forced termination: php scripts/portfolio-demo.php cleanup '.$demo->id."\n";
        fflush(STDOUT);
        for ($elapsed = 0; $elapsed < $seconds; $elapsed++) {
            sleep(1);
        }
    }
} catch (Throwable $error) {
    // Never dump request headers, cached config or database credentials.
    fwrite(STDERR, 'Portfolio demo failed ('.get_class($error).'): '.($error instanceof RuntimeException ? $error->getMessage() : 'details withheld')."\n");
    $failed = true;
} finally {
    $demo?->close();
}
exit(isset($failed) ? 1 : 0);
