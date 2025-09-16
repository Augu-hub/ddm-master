<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ErrorController extends Controller
{
    public function error400()
    {
        return Inertia::render('error/400');
    }

    public function error401()
    {
        return Inertia::render('error/401');
    }

    public function error403()
    {
        return Inertia::render('error/403');
    }

    public function error404()
    {
        return Inertia::render('error/404');
    }

    public function error404Alt()
    {
        return Inertia::render('error/404-alt');
    }

    public function error408()
    {
        return Inertia::render('error/408');
    }

    public function error500()
    {
        return Inertia::render('error/500');
    }

    public function error501()
    {
        return Inertia::render('error/501');
    }

    public function error502()
    {
        return Inertia::render('error/502');
    }

    public function error503()
    {
        return Inertia::render('error/503');
    }
}
