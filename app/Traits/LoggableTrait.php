<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait LoggableTrait
{
    public function logError(\Exception $e, array $data = null)
    {
        Log::error(__CLASS__ . '@' . __FUNCTION__, [
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'data' => $data ?? 'Not data request',
            'user_id' => auth()->id() ?? 'N/A',
            'time_stamp' => now()->toDateTimeString(),
        ]);
    }
}