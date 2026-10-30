<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

final class P7LockWait
{
    public static function measure(array $connection, int $samples = 5): array
    {
        $times = [];
        for ($i = 0; $i < $samples; $i++) {
            DB::beginTransaction();
            DB::table('inventory')->where('id', 3000001)->lockForUpdate()->firstOrFail();
            $worker = new Process([PHP_BINARY, __DIR__.'/p7-lock-worker.php'], base_path());
            $worker->setInput(json_encode($connection)."\n")->setTimeout(15);
            try {
                $worker->start();
                if (! $worker->waitUntil(fn ($type, $output) => str_contains($output, 'READY'))) {
                    throw new \RuntimeException('Lock probe did not reach barrier.');
                }
                usleep(500000);
                DB::rollBack();
                if ($worker->wait() !== 0) {
                    throw new \RuntimeException('Sandbox lock probe failed.');
                }
                $result = json_decode(substr($worker->getOutput(), strlen("READY\n")), true, flags: JSON_THROW_ON_ERROR);
                $times[] = $result['lock_query_ms'];
            } finally {
                if (DB::transactionLevel() > 0) {
                    DB::rollBack();
                }
                $worker->stop(0);
            }
        }
        sort($times);

        return ['samples' => $samples, 'method' => 'Two native MySQL connections; parent holds one synthetic stock row until 500ms after child READY; child SELECT FOR UPDATE then rollback; includes select/network overhead. Diagnostic only, no business mutation or concurrency SLO.',
            'min_ms' => min($times), 'p95_ms' => $times[(int) ceil($samples * .95) - 1], 'max_ms' => max($times)];
    }
}
