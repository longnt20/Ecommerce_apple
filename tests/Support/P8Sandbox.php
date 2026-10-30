<?php

namespace Tests\Support;

use App\Experiments\P8\Schema;
use Illuminate\Support\Facades\DB;
use PDO;
use Symfony\Component\Process\Process;

final class P8Sandbox
{
    public P7Sandbox $producer;

    public array $consumerConnection;

    public array $relayConnection;

    public ?PDO $consumer = null;

    public string $spool;

    private array $users = [];

    private bool $spoolCreated = false;

    public function __construct(int $products = 1, int $variants = 1)
    {
        $this->producer = new P7Sandbox;
        try {
            $this->producer->catalog($products, $variants, 5);
            Schema::apply(DB::connection()->getPdo(), 'producer');
            $name = 'apple_project_p1_fresh_'.bin2hex(random_bytes(6));
            $pdo = $this->producer->admin->create($name);
            Schema::apply($pdo, 'consumer');
            $this->consumerConnection = $this->account($this->producer->admin->connectionConfig($name), 'consumer');
            $this->relayConnection = $this->account($this->producer->connection, 'relay');
            $this->consumer = \App\Experiments\P8\SandboxGuard::connect($this->consumerConnection);
            $this->spool = base_path('tests/.runtime/p8-spool-'.bin2hex(random_bytes(6)));
            if (! mkdir($this->spool, 0700)) {
                throw new \RuntimeException('Cannot create a new private P8 spool.');
            }
            $this->spoolCreated = true;
            config(['p8.enabled' => true]);
        } catch (\Throwable $error) {
            $this->close();
            throw $error;
        }
    }

    private function account(array $connection, string $role): array
    {
        $username = 'p8_'.$role.'_'.bin2hex(random_bytes(6));
        $host = getenv('GITHUB_ACTIONS') === 'true' ? '%' : 'localhost';
        $password = bin2hex(random_bytes(24));
        $pdo = $this->producer->admin->source();
        $account = $pdo->quote($username).'@'.$pdo->quote($host);
        $pdo->exec('CREATE USER '.$account.' IDENTIFIED BY '.$pdo->quote($password));
        $this->users[] = $account;
        $database = $connection['database'];
        if ($role === 'consumer') {
            $pdo->exec('GRANT SELECT, INSERT, UPDATE, DELETE ON `'.$database.'`.* TO '.$account);
        } else {
            foreach (['p8_schema_migrations' => 'SELECT', 'p8_outbox_messages' => 'SELECT, UPDATE', 'p8_publish_attempts' => 'INSERT'] as $table => $grants) {
                $pdo->exec('GRANT '.$grants.' ON `'.$database.'`.`'.$table.'` TO '.$account);
            }
        }

        return array_replace($connection, ['username' => $username, 'password' => $password]);
    }

    public function worker(string $action, array $extra = []): Process
    {
        $process = new Process([PHP_BINARY, base_path('experiments/p8/worker.php')], base_path());
        $input = array_replace(['action' => $action, 'connection' => $action === 'relay' ? $this->relayConnection : $this->consumerConnection,
            'spool' => $this->spool, 'limit' => 1000], $extra);

        return $process->setInput(json_encode($input, JSON_THROW_ON_ERROR))->setTimeout(60);
    }

    public function run(string $action, array $extra = []): array
    {
        $worker = $this->worker($action, $extra);
        if ($worker->run() !== 0) {
            throw new \RuntimeException('P8 independent worker failed; details withheld.');
        }

        return json_decode($worker->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    }

    public function close(): void
    {
        $this->consumer = null;
        foreach ($this->users as $account) {
            $this->producer->admin->source()->exec('DROP USER '.$account);
        }
        $this->users = [];
        if ($this->spoolCreated && is_dir($this->spool)) {
            $path = \App\Experiments\P8\SandboxGuard::spool($this->spool);
            foreach (glob($path.'/*') as $file) {
                if (is_file($file)) {
                    unlink($file); // Only this instance's synthetic transport files.
                }
            }
            rmdir($path);
            $this->spoolCreated = false;
        }
        $this->producer->close();
        config(['p8.enabled' => false]);
    }
}
