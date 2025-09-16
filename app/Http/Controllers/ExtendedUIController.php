<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ExtendedUIController extends Controller
{
    public function dragula()
    {
        return Inertia::render('extended-ui/dragula');
    }

    public function sweetalerts()
    {
        return Inertia::render('extended-ui/sweetalerts');
    }

    public function ratings()
    {
        return Inertia::render('extended-ui/ratings');
    }

    public function scrollbar()
    {
        return Inertia::render('extended-ui/scrollbar');
    }

}
