<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function clinic()
    {
        return Inertia::render('dashboards/clinic/index');
    }

    public function wallet()
    {
        return Inertia::render('dashboards/e-wallet/index');
    }

    public function sales()
    {
        return Inertia::render('dashboards/Param/index');
    }
  public function salesparam()
    {
        return Inertia::render('dashboards/Param/index');
    }

    
    public function salesprocessus()
    {
        return Inertia::render('dashboards/sales/index');
    }
     public function salesrisques()
    {
        return Inertia::render('dashboards/sales/index');
    }
      public function salesaudi()
    {
        return Inertia::render('dashboards/sales/index');
    }
}
