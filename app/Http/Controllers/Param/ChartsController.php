<?php
namespace App\Http\Controllers\Param;


use Inertia\Inertia;
use App\Http\Controllers\Controller;
use App\Models\Param\Projet; // adaptez si vous utilisez Tenant comme "projet"
use App\Models\Param\Fonction; // -> renommez en fonction de votre modèle (ex: Functionn)
use App\Models\Param\Entite;   // ton modèle Entite

class ChartsController extends Controller
{
public function index()
    {
        // Récupérer toutes les entités du tenant avec leurs relations
        $entities = Entite::with([
            'project:id,name',
            'parent:id,name,level,code_base',
            'children' // Pour l'organigramme hiérarchique
        ])
        ->orderBy('name')
        ->get();

        // Récupérer les projets pour les filtres éventuels
        $projects = Projet::orderBy('name')->get(['id', 'name']);

        return Inertia::render('dashboards/Param/Charts/EntityChart', [
            'entities' => $entities,
            'projects' => $projects,
        ]);
    }
public function entity()
    {
        $pid = request('project_id');

        // Projets (ou Tenants si c’est ta nouvelle logique)
        $projects = Projet::select('id','name')
            ->orderBy('name')
            ->get();

        // Entités filtrées par projet (adapter si tu n’utilises plus project_id)
        $raw = Entite::select('id','project_id','name','code_base','parent_id','logo')
            ->when($pid, fn($q)=>$q->where('project_id', $pid))
            ->orderBy('name')
            ->get();

        // Mapper pour coller au format attendu par la vue (code + avatar_url)
        $entities = $raw->map(function($e){
            return [
                'id'         => $e->id,
                'project_id' => $e->project_id,
                'name'       => $e->name,
                'code'       => $e->code_base,               // <- alias
                'parent_id'  => $e->parent_id,
                'avatar_url' => $e->logo
                    ? (str_starts_with($e->logo, ['http://','https://'])
                        ? $e->logo
                        : asset('storage/'.$e->logo))
                    : null,
            ];
        });

        return Inertia::render('dashboards/Param/Charts/EntityChart', [
            'projects' => $projects,
            'entities' => $entities,
            'filters'  => ['project_id' => $pid],
        ]);
    }

public function function()
{
    $pid = request('project_id');

    $projects = Projet::select('id','name')->orderBy('name')->get();

    // IMPORTANT : ajouter avatar_path
    $functions = Fonction::select('id','project_id','name','character','parent_id','avatar_path')
        ->when($pid, fn($q)=>$q->where('project_id', $pid))
        ->orderBy('name')
        ->get();

    return Inertia::render('dashboards/Param/Charts/FunctionChart', [
        'projects'  => $projects,
        'functions' => $functions,   // contiendra avatar_url via $appends dans le modèle
        'filters'   => ['project_id' => $pid],
    ]);
}


public function distribution()
{
return Inertia::render('dashboards/Param/Charts/DistributionChart', [
'projects' => Projet::select('id','name')->orderBy('name')->get(),
'data' => [],
'filters' => ['project_id' => request('project_id')],
]);
}
}