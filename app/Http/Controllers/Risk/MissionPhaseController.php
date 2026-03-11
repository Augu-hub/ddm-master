<?php

namespace App\Http\Controllers\Risk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MissionPhaseController extends Controller
{
    public function index(Request $request)
    {
        // La vue utilise les menus partagés via HandleInertiaRequests
        return Inertia::render('dashboards/Audit/Mission/Phases/index');
    }
}