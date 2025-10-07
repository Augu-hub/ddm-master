<?php

namespace App\Http\Controllers\Param;

use Inertia\Inertia;
use App\Http\Controllers\Controller;
use App\Models\Param\Fonction;
use App\Models\Param\Entite;

class ChartsController extends Controller
{
    /** Page par défaut -> organigramme entités (global) */
    public function index()
    {
        return $this->entity();
    }

    /** Organigramme ENTITÉS (GLOBAL, sans projet) */
    public function entity()
    {
        $raw = Entite::select('id','name','code_base','parent_id','logo')
            ->orderBy('name')
            ->get();

        $entities = $raw->map(function ($e) {
            $logo = $e->logo;
            if ($logo && !preg_match('#^https?://#i', $logo)) {
                $logo = asset('storage/'.$logo);
            }
            return [
                'id'         => $e->id,
                'name'       => $e->name,
                'code'       => $e->code_base, // alias
                'parent_id'  => $e->parent_id,
                'avatar_url' => $logo ?: null,
            ];
        });

        return Inertia::render('dashboards/Param/Charts/EntityChart', [
            'entities' => $entities,
        ]);
    }

    /** Organigramme FONCTIONS (GLOBAL, sans projet) */
    public function function()
    {
        // IMPORTANT : avatar_path pour l'accessor avatar_url
        $functions = Fonction::select('id','name','character','parent_id','avatar_path')
            ->orderBy('name')
            ->get();

        return Inertia::render('dashboards/Param/Charts/FunctionChart', [
            'functions' => $functions,
        ]);
    }

    /** Autre page (ex. distribution) — global également */
    public function distribution()
    {
        return Inertia::render('dashboards/Param/Charts/DistributionChart', [
            'data' => [],
        ]);
    }
}
