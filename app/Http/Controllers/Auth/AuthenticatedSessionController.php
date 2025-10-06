<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Session;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = auth()->user();

        // Admin global : uniquement par email (aucun rôle/permission)
        if ($this->isGlobalAdmin($user)) {
            session(['tenant_id' => null]);
            session(['is_global_admin' => true]);
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // Récupération des tenants de l'utilisateur
        $tenants = $user->tenants ?? collect();

        if ($tenants->isEmpty()) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['email' => 'Aucun tenant assigné à ce compte.']);
        }

        if ($tenants->count() === 1) {
            session(['tenant_id' => $tenants->first()->id]);
            session(['is_global_admin' => false]);
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return redirect()->route('select.tenant');
    }

    /**
     * Vérifier si l'utilisateur est admin global (sans rôles/permissions)
     */
    private function isGlobalAdmin($user): bool
    {
        if (! $user) {
            return false;
        }

        // Option A : email fixe
        // return $user->email === 'admin@diaddem.local';

        // Option B : liste d’emails autorisés via .env (séparés par virgules)
        // Ex: GLOBAL_ADMINS="admin@diaddem.local,root@example.com"
        try {
            $whitelist = array_filter(array_map('trim', explode(',', (string) env('GLOBAL_ADMINS', 'admin@diaddem.local'))));
            return in_array($user->email, $whitelist, true);
        } catch (\Throwable $e) {
            \Log::warning('isGlobalAdmin: erreur lecture GLOBAL_ADMINS', ['error' => $e->getMessage()]);
            return $user->email === 'admin@diaddem.local';
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Session::forget(['tenant_id', 'is_global_admin']);
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
