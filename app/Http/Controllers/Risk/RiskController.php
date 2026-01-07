<?php

namespace App\Http\Controllers\Risk;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class RiskController extends Controller
{
    public function index()
    {
        return Inertia::render('dashboards/Risk/Functions/index');
    }
}