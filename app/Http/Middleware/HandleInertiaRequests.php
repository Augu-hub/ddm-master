<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $raw   = Inspiring::quote();
        $msg   = Str::of($raw)->beforeLast(' - ')->trim();
        $authr = Str::of($raw)->afterLast(' - ')->trim();

        return array_merge(parent::share($request), [
            'name'  => config('app.name'),

            'quote' => [
                'message' => (string) $msg,
                'author'  => (string) $authr,
            ],

            'auth' => [
                'user' => $request->user() ? [
                    'id'    => $request->user()->id,
                    'name'  => $request->user()->name,
                    'email' => $request->user()->email,
                ] : null,
                'menus' => $request->session()->get('user_menus', []),
            ],

            'ziggy' => array_merge((new Ziggy)->toArray(), [
                'location' => $request->url(),
            ]),

            'csrf' => csrf_token(),
        ]);
    }
}