<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class LayoutsController extends Controller
{
    public function vertical()
    {
        return Inertia::render('layouts/vertical');
    }

    public function horizontal()
    {
        return Inertia::render('layouts/horizontal');
    }

    public function compact()
    {
        return Inertia::render('layouts/compact');
    }

    public function detached()
    {
        return Inertia::render('layouts/detached');
    }

    public function full()
    {
        return Inertia::render('layouts/full');
    }

    public function fullscreen()
    {
        return Inertia::render('layouts/fullscreen');
    }

    public function hoverMenu()
    {
        return Inertia::render('layouts/hover-menu');
    }

    public function iconView()
    {
        return Inertia::render('layouts/icon-view');
    }

}
