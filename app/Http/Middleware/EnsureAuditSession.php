<?php
// app/Http/Middleware/EnsureAuditSession.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAuditSession
{
    public function handle(Request $request, Closure $next)
    {
        // Si tu avais une logique de session audit ici, remets-la
        // Sinon ce middleware est un pass-through
        return $next($request);
    }
}