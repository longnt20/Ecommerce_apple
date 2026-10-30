<?php

namespace Tests\Support;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use RuntimeException;

final class TestApplication
{
    public static function create()
    {
        $root = dirname(__DIR__, 2);
        TestingEnvironment::install($root);
        $settings = TestingEnvironment::database();
        if ($settings['mode'] === 'mysql') {
            // Native PDO preflight happens before Laravel providers may run.
            MySqlSafety::verify($settings);
        }
        $app = require $root.'/bootstrap/app.php';
        $app->loadEnvironmentFrom('.env.testing');

        // Runs before service providers can resolve any connection.
        $app->afterBootstrapping(LoadConfiguration::class, function ($app) use ($settings) {
            if (! $app->environment('testing') || $app->configurationIsCached()) {
                throw new RuntimeException('P0 refuses a non-testing or cached application.');
            }

            $connections = [];
            if ($settings['mode'] === 'mysql') {
                $connections['mysql'] = [
                    'driver' => 'mysql',
                    'host' => $settings['host'],
                    'port' => $settings['port'],
                    'database' => $settings['database'],
                    'username' => $settings['username'],
                    'password' => $settings['password'],
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_0900_ai_ci',
                    'prefix' => '',
                    'strict' => true,
                ];
            }

            $app['config']->set([
                'database.default' => $settings['mode'] === 'mysql' ? 'mysql' : 'p0_disabled',
                'database.connections' => $connections,
                'cache.default' => 'array',
                'session.driver' => 'array',
                'mail.default' => 'array',
                'queue.default' => 'sync',
                'logging.default' => 'null',
                'telescope.enabled' => false,
                'inventory.enabled' => true,
                'p8.enabled' => false,
            ]);
        });

        $app->beforeBootstrapping(BootProviders::class, static function () {
            Bus::fake();
            Queue::fake();
            Mail::fake();
            Notification::fake();
            Http::preventStrayRequests();
        });

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
