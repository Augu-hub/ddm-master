<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Param\Auditor;

class EnsureIsAuditor
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (!$user) return redirect()->route('login');

        // Vérifier si auditeur actif
        $isAuditor = session('is_auditor', false)
            || Auditor::where('email', $user->email)->where('status', 'active')->exists();

        if (!$isAuditor) {
            return redirect()->route('dashboard')
                ->withErrors(['error' => 'Accès réservé aux auditeurs.']);
        }

        return $next($request);
    }
}