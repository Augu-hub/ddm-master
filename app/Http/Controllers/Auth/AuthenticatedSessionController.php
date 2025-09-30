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
use App\Models\Tenant;
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
        $tenants = $user->tenants;

        if ($tenants->isEmpty()) {
            // User has no tenants - this shouldn't happen in a proper setup
            Auth::logout();
            return redirect()->route('login')->withErrors(['email' => 'No tenants assigned to this account.']);
        }

        if ($tenants->count() === 1) {
            // User has only one tenant, auto-select it
            session(['tenant_id' => $tenants->first()->id]);
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // User has multiple tenants, let them choose
        return redirect()->route('select.tenant');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Clean up tenant session
        Session::forget('tenant_id');
        
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}