<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class FormsController extends Controller
{
    public function basic()
    {
        return Inertia::render('forms/basic');
    }

    public function inputMask()
    {
        return Inertia::render('forms/input-mask');
    }

    public function picker()
    {
        return Inertia::render('forms/picker');
    }

    public function select()
    {
        return Inertia::render('forms/select');
    }

    public function slider()
    {
        return Inertia::render('forms/slider');
    }

    public function validation()
    {
        return Inertia::render('forms/validation');
    }

    public function wizard()
    {
        return Inertia::render('forms/wizard');
    }

    public function fileUploads()
    {
        return Inertia::render('forms/file-uploads');
    }

    public function editors()
    {
        return Inertia::render('forms/editors');
    }

    public function layouts()
    {
        return Inertia::render('forms/layouts');
    }

}
