<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        // Read again: an issued token is not proof the account remains active.
        abort_unless($request->user()?->fresh()?->status === 'active', 403, 'Tài khoản không hoạt động.');

        return $next($request);
    }
}
