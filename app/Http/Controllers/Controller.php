<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\Param\Entite;
use Inertia\Inertia;
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;


    // Partage global avec Inertia
    public function shareEntities()
    {
        if (auth()->check()) {
            $entities = Entite::where('project_id', auth()->user()->current_project_id)
                ->orderBy('level')
                ->orderBy('name')
                ->get(['id', 'name', 'code_base']);
                
            Inertia::share('entities', $entities);
        }
    }
}

