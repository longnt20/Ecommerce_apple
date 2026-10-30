<?php

namespace Tests\Support;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

/** Synthetic side effect with a durable unique identity, never used by application code. */
final class P7RecoveryProbe implements ShouldQueue
{
    public function __construct(public string $mode) {}

    public function handle(): void
    {
        if (! preg_match('/^apple_project_p1_fresh_[a-f0-9]{12}$/D', DB::connection()->getDatabaseName())) {
            throw new \RuntimeException('Probe refuses application DB.');
        }
        DB::table('cache')->insertOrIgnore(['key' => 'p7_effect_'.$this->mode, 'value' => 'synthetic', 'expiration' => 2147483647]);
        if (! DB::table('cache')->where('key', 'p7_resume_'.$this->mode)->exists()) {
            if ($this->mode === 'failure') {
                throw new \RuntimeException('Synthetic recoverable failure');
            }
            echo "P7_CRASH_READY\n";
            flush();
            // Parent kills this worker after the durable effect, before acknowledgement.
            while (true) {
                usleep(100000);
            }
        }
        DB::table('cache')->insertOrIgnore(['key' => 'p7_done_'.$this->mode, 'value' => 'synthetic', 'expiration' => 2147483647]);
    }
}
