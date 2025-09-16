<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class MapsController extends Controller
{
    public function google()
    {
        return Inertia::render('maps/google');
    }

    public function vector()
    {
        return Inertia::render('maps/vector');
    }

    public function leaflet()
    {
        return Inertia::render('maps/leaflet');
    }
}
