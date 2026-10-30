<?php

// Intentionally no Laravel bootstrap, .env loading, source model or mail/payment client.
require __DIR__.'/../../vendor/autoload.php';

use App\Experiments\P8\FileSpool;
use App\Experiments\P8\ProjectionConsumer;
use App\Experiments\P8\Relay;
use App\Experiments\P8\SandboxGuard;

try {
    $input = json_decode(stream_get_contents(STDIN), true, 32, JSON_THROW_ON_ERROR);
    $pdo = SandboxGuard::connect($input['connection']);
    $spool = new FileSpool($input['spool']);
    $barrier = function (string $stage) use ($input) {
        if (($input['fault'] ?? null) === $stage) {
            fwrite(STDOUT, "P8_FAULT_READY\n");
            fflush(STDOUT);
            while (true) {
                usleep(100000);
            }
        }
    };
    if ($input['action'] === 'relay') {
        $relay = new Relay($pdo, $spool);
        if (isset($input['replay_id'])) {
            $relay->replay($input['replay_id']);
        }
        $result = $relay->publish($input['limit'] ?? 100, fn () => $barrier('after-publish'));
    } elseif ($input['action'] === 'consume') {
        $consumer = new ProjectionConsumer($pdo);
        $result = ['applied' => 0, 'duplicate' => 0, 'stale' => 0, 'quarantined' => 0];
        foreach ($spool->files($input['limit'] ?? 100, $input['offset'] ?? 0) as $file) {
            $bytes = $spool->bytes($file);
            try {
                $event = json_decode($bytes, true, 32, JSON_THROW_ON_ERROR);
                $outcome = is_array($event) ? $consumer->consume($event, $barrier) : $consumer->malformed($bytes);
            } catch (JsonException) {
                $outcome = $consumer->malformed($bytes);
            }
            $result[$outcome]++;
        }
    } else {
        throw new RuntimeException('invalid_action');
    }
    echo json_encode($result, JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    fwrite(STDERR, 'P8 worker stopped: '.$error::class.'; details withheld.'."\n");
    exit(1);
}
