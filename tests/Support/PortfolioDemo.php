<?php

namespace Tests\Support;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Process\Process;

/** Disposable UI/API demo; credentials, config, sessions and uploads stay in ignored runtime. */
final class PortfolioDemo
{
    public const PASSWORD = 'PortfolioDemo!2026';

    public P7Sandbox $sandbox;

    public P7Runtime $runtime;

    public string $id;

    public string $directory;

    public int $port;

    public ?Process $server = null;

    public ?Process $frontend = null;

    public function __construct()
    {
        $this->id = bin2hex(random_bytes(6));
        $this->directory = base_path('tests/.runtime/portfolio-'.$this->id);
        if (! mkdir($this->directory, 0700)) {
            throw new \RuntimeException('Cannot create a new demo directory.');
        }
        try {
            $this->sandbox = new P7Sandbox;
            $this->sandbox->catalog(6, 3, 2);
            DB::statement('CREATE TABLE portfolio_demo_instance (id CHAR(24) PRIMARY KEY) ENGINE=InnoDB');
            DB::table('portfolio_demo_instance')->insert(['id' => $this->id]);
            DB::table('users')->where('id', 910002)->update(['name' => 'Portfolio Admin', 'email' => 'admin@example.test', 'password' => Hash::make(self::PASSWORD)]);
            foreach ([910001 => 'buyer@example.test', 910003 => 'other@example.test'] as $id => $email) {
                DB::table('users')->insert(['id' => $id, 'name' => 'Synthetic Demo Buyer', 'email' => $email,
                    'password' => Hash::make(self::PASSWORD), 'code' => 'DEMO-'.$id, 'role' => 'user', 'status' => 'active']);
            }
            foreach (['sessions', 'views', 'uploads'] as $name) {
                mkdir($this->directory.'/'.$name, 0700);
            }
            $this->port = self::freePort();
            config(['session.driver' => 'file', 'session.files' => $this->directory.'/sessions',
                'session.cookie' => 'portfolio_'.$this->id, 'session.domain' => null, 'session.secure' => false,
                'view.compiled' => $this->directory.'/views', 'p8.enabled' => false,
                'filesystems.default' => 'public', 'filesystems.disks.public.root' => $this->directory.'/uploads',
                'portfolio_demo' => ['id' => $this->id, 'port' => $this->port]]);
            $this->runtime = new P7Runtime($this->sandbox->connection);
            $caches = array_filter($this->runtime->environment, fn ($key) => str_ends_with($key, '_CACHE'), ARRAY_FILTER_USE_KEY);
            file_put_contents($this->directory.'/manifest.json', json_encode(['id' => $this->id,
                'database' => $this->sandbox->connection['database'], 'source_identity' => $this->sandbox->admin->sourceIdentityHash(),
                'port' => $this->port, 'cache_paths' => array_values($caches)], JSON_PRETTY_PRINT));
        } catch (\Throwable $error) {
            $this->close();
            throw $error;
        }
    }

    public static function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        if (! $socket) {
            throw new \RuntimeException('No loopback port available.');
        }
        $address = stream_socket_get_name($socket, false);
        fclose($socket);

        return (int) substr($address, strrpos($address, ':') + 1);
    }

    public function start(): void
    {
        $this->server = new Process([PHP_BINARY, '-S', '127.0.0.1:'.$this->port, '-t', public_path(),
            base_path('scripts/support/portfolio-router.php')], base_path(), $this->runtime->environment);
        $this->server->setTimeout(null)->disableOutput()->start();
        $client = $this->client();
        for ($attempt = 0; $attempt < 100; $attempt++) {
            try {
                $response = $client->get('/_demo/health');
                $body = json_decode($response->getBody(), true);
                if ($response->getStatusCode() === 200 && ($body['id'] ?? null) === $this->id) {
                    return;
                }
            } catch (\GuzzleHttp\Exception\ConnectException) {
                // Wait only for the server owned by this fixture.
            }
            if (! $this->server->isRunning()) {
                break;
            }
            usleep(100000);
        }
        throw new \RuntimeException('Isolated demo server did not become ready.');
    }

    public function client(array $options = []): Client
    {
        return new Client(array_replace(['base_uri' => 'http://127.0.0.1:'.$this->port, 'http_errors' => false,
            'allow_redirects' => false, 'connect_timeout' => 1, 'timeout' => 10], $options));
    }

    public function startFrontend(): int
    {
        $port = self::freePort();
        $this->frontend = new Process(['node', base_path('frontend/node_modules/vite/bin/vite.js'), '--host', '127.0.0.1',
            '--port', (string) $port, '--strictPort'], base_path('frontend'), ['VITE_DEV_API_TARGET' => 'http://127.0.0.1:'.$this->port]);
        $this->frontend->setTimeout(null)->disableOutput()->start();
        $client = new Client(['http_errors' => false, 'timeout' => 1]);
        for ($attempt = 0; $attempt < 100; $attempt++) {
            try {
                $response = $client->get('http://127.0.0.1:'.$port.'/api/home');
                if ($response->getStatusCode() === 200 && isset(json_decode($response->getBody(), true)['data'])) {
                    return $port;
                }
            } catch (\GuzzleHttp\Exception\TransferException) {
            }
            if (! $this->frontend->isRunning()) {
                break;
            }
            usleep(100000);
        }
        throw new \RuntimeException('Isolated Vite proxy did not become ready.');
    }

    public function close(): void
    {
        $this->frontend?->stop(1);
        $this->server?->stop(1);
        if (isset($this->runtime)) {
            $this->runtime->close();
        }
        if (isset($this->sandbox)) {
            $this->sandbox->close();
        }
        self::removeDirectory($this->directory);
    }

    public static function cleanup(string $id): void
    {
        if (! preg_match('/^[a-f0-9]{12}$/D', $id)) {
            throw new \RuntimeException('Invalid demo identity.');
        }
        $directory = base_path('tests/.runtime/portfolio-'.$id);
        $manifest = json_decode(file_get_contents($directory.'/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
        $admin = new DatabaseSandbox;
        if (($manifest['id'] ?? '') !== $id || ! hash_equals($admin->sourceIdentityHash(), $manifest['source_identity'] ?? '')
            || ! preg_match('/^apple_project_p1_fresh_[a-f0-9]{12}$/D', $manifest['database'] ?? '')) {
            throw new \RuntimeException('Demo ownership verification failed.');
        }
        $client = new Client(['http_errors' => false, 'timeout' => 1]);
        try {
            $response = $client->get('http://127.0.0.1:'.(int) $manifest['port'].'/_demo/health');
            if ($response->getStatusCode() === 200) {
                throw new \RuntimeException('Demo is still running; allow its normal exit before cleanup.');
            }
        } catch (\GuzzleHttp\Exception\ConnectException) {
            // Stopped instance: the DB marker below must still prove ownership.
        }
        $connection = $admin->connectSandbox($manifest['database']);
        $marker = $connection->query('SELECT id FROM portfolio_demo_instance')->fetchColumn();
        if ($marker !== $id) {
            throw new \RuntimeException('Refusing to remove a database without the exact demo marker.');
        }
        foreach ($manifest['cache_paths'] as $path) {
            if (! preg_match('~^tests/\.runtime/p7-[a-f0-9]{12}-(config|routes|events|services|packages)\.php$~D', $path)) {
                throw new \RuntimeException('Unexpected demo cache path.');
            }
        }
        $connection = null;
        $admin->source()->exec('DROP DATABASE `'.$manifest['database'].'`');
        foreach ($manifest['cache_paths'] as $path) {
            if (is_file(base_path($path))) {
                unlink(base_path($path));
            }
        }
        self::removeDirectory($directory);
    }

    public static function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }
        $path = realpath($directory);
        $root = realpath(base_path('tests/.runtime'));
        if (! $path || dirname($path) !== $root || is_link($directory) || ! preg_match('/^portfolio-[a-f0-9]{12}$/D', basename($path))) {
            throw new \RuntimeException('Refusing cleanup outside an owned demo runtime directory.');
        }
        // Resolve and validate the absolute root before any recursive cleanup on Windows.
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) {
            if ($file->isLink()) {
                throw new \RuntimeException('Unexpected symlink in demo runtime; cleanup stopped.');
            }
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($path);
    }
}
