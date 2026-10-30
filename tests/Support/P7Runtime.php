<?php

namespace Tests\Support;

use Illuminate\Bus\Dispatcher;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\Process\Process;

/** Runs real Artisan/queue/scheduler processes against an allowlisted synthetic DB. */
final class P7Runtime
{
    private string $prefix;

    public array $environment;

    public function __construct(array $connection)
    {
        if (! preg_match('/^apple_project_p1_(fresh|restore)_[a-f0-9]{12}$/D', $connection['database'])
            || ! in_array($connection['host'], ['127.0.0.1', 'localhost', '::1'], true)) {
            throw new \RuntimeException('Runtime rehearsal refuses a non-sandbox DB.');
        }
        $this->prefix = base_path('tests/.runtime/p7-'.bin2hex(random_bytes(6)));
        $config = config()->all();
        $config['app']['env'] = 'testing';
        $config['app']['debug'] = false;
        $config['database']['default'] = 'mysql';
        $config['database']['connections'] = ['mysql' => $connection];
        $config['database']['redis'] = [];
        $config['queue']['default'] = 'database';
        $config['queue']['connections'] = ['database' => ['driver' => 'database', 'connection' => 'mysql',
            'table' => 'jobs', 'queue' => 'default', 'retry_after' => 90, 'after_commit' => true]];
        $config['queue']['failed'] = ['driver' => 'database-uuids', 'database' => 'mysql', 'table' => 'failed_jobs'];
        $config['queue']['batching']['database'] = 'mysql';
        $config['cache']['default'] = 'database';
        $config['cache']['stores'] = ['database' => ['driver' => 'database', 'connection' => 'mysql', 'table' => 'cache',
            'lock_connection' => 'mysql', 'lock_table' => 'cache_locks']];
        $config['cache']['prefix'] = 'p7_rehearsal_';
        $config['mail']['default'] = 'array';
        $config['mail']['mailers'] = ['array' => ['transport' => 'array']];
        $config['logging']['default'] = 'null';
        $config['telescope']['enabled'] = false;
        $config['inventory']['enabled'] = true;
        $config['ordering']['transitions_enabled'] = true;
        $config['vnpay']['enabled'] = false;
        $config['services'] = [];
        file_put_contents($this->prefix.'-config.php', '<?php return '.var_export($config, true).';');
        $cachePrefix = 'tests/.runtime/'.basename($this->prefix);
        $this->environment = ['APP_ENV' => 'testing', 'APP_CONFIG_CACHE' => $cachePrefix.'-config.php',
            'APP_ROUTES_CACHE' => $cachePrefix.'-routes.php', 'APP_EVENTS_CACHE' => $cachePrefix.'-events.php',
            'APP_SERVICES_CACHE' => $cachePrefix.'-services.php', 'APP_PACKAGES_CACHE' => $cachePrefix.'-packages.php',
            'TELESCOPE_ENABLED' => 'false', 'MAIL_MAILER' => 'array', 'DB_URL' => '',
            'DB_CONNECTION' => 'mysql', 'DB_HOST' => $connection['host'], 'DB_PORT' => (string) $connection['port'],
            'DB_DATABASE' => $connection['database'], 'DB_USERNAME' => $connection['username'], 'DB_PASSWORD' => $connection['password'],
            'DB_QUEUE_CONNECTION' => 'mysql', 'CACHE_STORE' => 'database', 'DB_CACHE_CONNECTION' => 'mysql',
            'LOG_CHANNEL' => 'null', 'QUEUE_CONNECTION' => 'database', 'INVENTORY_PROTOCOL_ENABLED' => 'true',
            'ORDER_TRANSITIONS_ENABLED' => 'true', 'VNPAY_ENABLED' => 'false',
            'APP_KEY' => 'base64:'.base64_encode(str_repeat('p', 32))];
    }

    public function process(array $arguments): Process
    {
        return new Process([PHP_BINARY, base_path('artisan'), ...$arguments], base_path(), $this->environment, timeout: 90);
    }

    public function run(array $arguments): string
    {
        $process = $this->process($arguments);
        if ($process->run() !== 0) {
            file_put_contents($this->prefix.'-failure.txt', $process->getOutput().$process->getErrorOutput());
            throw new \RuntimeException('Sandbox Artisan command failed: '.$arguments[0].'; output withheld.');
        }

        return $process->getOutput();
    }

    public static function realQueue(): void
    {
        config(['queue.default' => 'database', 'queue.connections.database.connection' => 'p7',
            'queue.failed.database' => 'p7', 'queue.connections.database.after_commit' => true]);
        $manager = new QueueManager(app());
        (new \Illuminate\Queue\QueueServiceProvider(app()))->registerConnectors($manager);
        Queue::swap($manager);
        Bus::swap(new Dispatcher(app(), fn ($connection = null) => Queue::connection($connection)));
    }

    public function close(): void
    {
        // Only files created by this instance; never remove application cache or directories.
        foreach (['config', 'routes', 'events', 'services', 'packages'] as $kind) {
            $file = $this->prefix.'-'.$kind.'.php';
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
