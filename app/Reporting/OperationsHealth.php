<?php

namespace App\Reporting;

use Illuminate\Support\Facades\DB;

/** Numeric diagnostics only; no serialized job payloads, customers or SQL bindings. */
final class OperationsHealth
{
    public function snapshot(): array
    {
        $oldest = DB::table('jobs')->min('created_at');

        return [
            'recorded_at_utc' => now()->utc()->toIso8601String(),
            'queued_jobs' => DB::table('jobs')->count(),
            'reserved_jobs' => DB::table('jobs')->whereNotNull('reserved_at')->count(),
            'oldest_job_age_seconds' => $oldest === null ? null : max(0, time() - (int) $oldest),
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'pending_notifications' => DB::table('coupon_assignment_deliveries')->where('status', 'pending')->count(),
            'overdue_active_reservations' => DB::table('inventory_reservations')->where('status', 'active')
                ->whereNotNull('expires_at')->where('expires_at', '<=', now())->count(),
            'open_inventory_discrepancies' => DB::table('inventory_discrepancies')->where('status', 'open')->count(),
        ];
    }
}
