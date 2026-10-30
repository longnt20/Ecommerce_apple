<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait LoggableTrait
{
    public function logError(\Throwable $e, ?array $data = null)
    {
        Log::error(__CLASS__.'@'.__FUNCTION__, [
            'exception_type' => $e::class,
            'error_code' => $e->getCode(),
            'line' => $e->getLine(),
            'file' => basename($e->getFile()),
            'user_id' => auth()->id() ?? 'N/A',
            'time_stamp' => now()->toDateTimeString(),
        ]);
    }
}
