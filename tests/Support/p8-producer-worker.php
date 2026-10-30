<?php

require __DIR__.'/../../vendor/autoload.php';

try {
    $input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
    App\Experiments\P8\SandboxGuard::database($input['connection']['database']);
    App\Experiments\P8\SandboxGuard::connect($input['connection']);
    putenv('P0_DB_MODE=disabled');
    Tests\Support\TestApplication::create();
    config(['database.connections.p8_producer' => $input['connection'], 'p8.enabled' => true]);
    Illuminate\Support\Facades\DB::setDefaultConnection('p8_producer');
    if (($input['mode'] ?? null) === 'once') {
        fwrite(STDOUT, "P8_PRODUCER_READY\n");
        fflush(STDOUT);
    }
    app(App\Modules\Catalog\Infrastructure\SaveProduct::class)->save(['name' => $input['name'] ?? 'Committed before producer died',
        'category_id' => 910001, 'status' => 'published', 'visibility' => 'public'], 1000001);
    if (($input['mode'] ?? null) === 'once') {
        exit(0);
    }
    fwrite(STDOUT, "P8_FAULT_READY\n");
    fflush(STDOUT);
    while (true) {
        usleep(100000);
    }
} catch (Throwable $error) {
    fwrite(STDERR, 'P8 producer stopped: '.$error::class.'; details withheld.');
    exit(1);
}
