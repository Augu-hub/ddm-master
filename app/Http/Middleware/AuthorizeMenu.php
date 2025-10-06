<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthorizeMenu
{
    public function handle(Request $request, Closure $next, string $menuKey = null)
    {
        // Désactivé : on laisse tout passer, aucun appel Spatie/DB
        return $next($request);
    }
}
