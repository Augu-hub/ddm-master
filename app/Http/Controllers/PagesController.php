<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class PagesController extends Controller
{
    public function emailActivation()
    {
        return Inertia::render('pages/email-template/activation');
    }

    public function emailBasic()
    {
        return Inertia::render('pages/email-template/basic');
    }

    public function emailInvoice()
    {
        return Inertia::render('pages/email-template/invoice');
    }

    public function faqs()
    {
        return Inertia::render('pages/faqs/index');
    }

    public function pricing1()
    {
        return Inertia::render('pages/pricing/pricing-1');
    }

    public function pricing2()
    {
        return Inertia::render('pages/pricing/pricing-2');
    }

    public function comingSoon()
    {
        return Inertia::render('pages/coming-soon');
    }

    public function maintenance()
    {
        return Inertia::render('pages/maintenance');
    }

    public function starter()
    {
        return Inertia::render('pages/starter');
    }

    public function termsConditions()
    {
        return Inertia::render('pages/terms-conditions');
    }

    public function timeline()
    {
        return Inertia::render('pages/timeline');
    }
}
