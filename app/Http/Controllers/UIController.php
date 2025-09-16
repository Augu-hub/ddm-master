<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class UIController extends Controller
{
    public function accordions()
    {
        return Inertia::render('ui/accordions');
    }

    public function alerts()
    {
        return Inertia::render('ui/alerts');
    }

    public function avatars()
    {
        return Inertia::render('ui/avatars');
    }

    public function badges()
    {
        return Inertia::render('ui/badges');
    }

    public function breadcrumb()
    {
        return Inertia::render('ui/breadcrumb');
    }

    public function buttons()
    {
        return Inertia::render('ui/buttons');
    }

    public function cards()
    {
        return Inertia::render('ui/cards');
    }

    public function carousel()
    {
        return Inertia::render('ui/carousel');
    }

    public function collapse()
    {
        return Inertia::render('ui/collapse');
    }

    public function dropdowns()
    {
        return Inertia::render('ui/dropdowns');
    }

    public function ratios()
    {
        return Inertia::render('ui/ratios');
    }

    public function grid()
    {
        return Inertia::render('ui/grid');
    }

    public function links()
    {
        return Inertia::render('ui/links');
    }

    public function listGroup()
    {
        return Inertia::render('ui/list-group');
    }

    public function modals()
    {
        return Inertia::render('ui/modals');
    }

    public function notifications()
    {
        return Inertia::render('ui/notifications');
    }

    public function offcanvas()
    {
        return Inertia::render('ui/offcanvas');
    }

    public function placeholders()
    {
        return Inertia::render('ui/placeholders');
    }

    public function pagination()
    {
        return Inertia::render('ui/pagination');
    }

    public function popovers()
    {
        return Inertia::render('ui/popovers');
    }

    public function progress()
    {
        return Inertia::render('ui/progress');
    }

    public function scrollspy()
    {
        return Inertia::render('ui/scrollspy');
    }

    public function spinners()
    {
        return Inertia::render('ui/spinners');
    }

    public function tabs()
    {
        return Inertia::render('ui/tabs');
    }

    public function tooltips()
    {
        return Inertia::render('ui/tooltips');
    }

    public function typography()
    {
        return Inertia::render('ui/typography');
    }

    public function utilities()
    {
        return Inertia::render('ui/utilities');
    }

}
