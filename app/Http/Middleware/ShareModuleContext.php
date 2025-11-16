<?php
// app/Http/Middleware/ShareModuleContext.php
namespace App\Http\Middleware;

use App\Models\Master\Menu;
use App\Models\Master\Module;
use App\Models\Param\Entite;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ShareModuleContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $moduleId   = session('current_module_id');
        $moduleCode = session('current_module_code');
        $moduleName = session('current_module_name');

        // Rien à partager si aucun module sélectionné (ex: page dashboard)
        if (!$moduleId) {
            Inertia::share([
                'ctx' => [
                    'module'  => null,
                    'layout'  => null,
                    'entities'=> [],
                ],
            ]);
            return $next($request);
        }

        // 1) Déterminer le layout en fonction du code
        $layout = match ($moduleCode) {
            'param.projects' => 'layouts/ParamLayout',
            'process.core'   => 'layouts/ProcessLayout',
            'risk.core'      => 'layouts/RiskLayout',
            'audit.core'     => 'layouts/AuditLayout',
            default          => 'layouts/DefaultModuleLayout',
        };

        // 2) Charger les entités UNIQUEMENT pour le module concerné
        $entities = [];
        if ($moduleCode === 'param.projects') {
            try {
                // Entités = base tenant
                $entities = Entite::query()
                    ->orderBy('level')
                    ->orderBy('name')
                    ->get(['id','name','code_base','parent_id'])
                    ->map(fn($e) => ['id'=>$e->id,'name'=>$e->name,'slug'=>$e->code_base])
                    ->values()
                    ->all();
            } catch (\Throwable $e) {
                Log::warning('ShareModuleContext: chargement entités KO: '.$e->getMessage());
                $entities = [];
            }
        }

        // 3) Partager via Inertia pour que ton Shell.vue puisse poser le bon layout
        Inertia::share([
            'ctx' => [
                'module' => [
                    'id'   => $moduleId,
                    'code' => $moduleCode,
                    'name' => $moduleName,
                ],
                'layout'   => $layout,
                'entities' => $entities,
            ],
        ]);

        return $next($request);
    }
}
