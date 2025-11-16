<?php

namespace App\Http\Controllers;

use App\Models\Master\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Accueil : Grille des modules accessibles par l'utilisateur
     */
    public function __invoke(Request $request)
    {
        $user = $request->user();

        Log::info('═════════════════════════════════════════════');
        Log::info('🏠 DashboardController START', [
            'user_id' => $user->id,
            'user_email' => $user->email,
        ]);

        // Vérifier si admin global
        $isGlobal = (bool) session('is_global_admin', false);

        Log::info('🔐 Type utilisateur', [
            'is_global_admin' => $isGlobal,
        ]);

        // Charger les modules
        $query = Module::on('mysql')
            ->with('service:id,name')
            ->where('is_active', true)
            ->orderBy('sort');

        // Si pas admin global, filtrer par modules assignés
        if (!$isGlobal) {
            $query->whereHas('users', fn($q) => $q->where('user_id', $user->id));
        }

        $modules = $query->get([
            'id',
            'code',
            'name',
            'icon',
            'entry_route_name',
            'service_id'
        ]);

        Log::info('📦 Modules chargés pour dashboard', [
            'count' => $modules->count(),
            'codes' => $modules->pluck('code')->toArray(),
        ]);

        // Mapper pour le frontend
        $modulesList = $modules->map(function($m) {
            return [
                'code' => $m->code,
                'name' => $m->name,
                'icon' => $m->icon,
                'entry_route' => $m->entry_route_name, // Optionnel, le frontend utilise /m/{code}
                'service' => $m->service ? [
                    'name' => $m->service->name
                ] : null,
            ];
        });

        Log::info('✅ Rendu dashboard avec modules');
        Log::info('🏠 DashboardController END');
        Log::info('═════════════════════════════════════════════');

        return Inertia::render('Modules/Access', [
            'modules' => $modulesList,
        ]);
    }

    /**
     * Dashboards « fallback » - points d'entrée spécifiques par module
     * Ces routes sont utilisées si entry_route_name est défini
     */
    public function dashboardParam()
    {
        Log::info('📄 Dashboard Param chargé');
        return Inertia::render('dashboards/Param/Index');
    }

    public function dashboardProcessus()
    {
        Log::info('📄 Dashboard Processus chargé');
        return Inertia::render('dashboards/Processus/Index');
    }

    public function dashboardRisque()
    {
        Log::info('📄 Dashboard Risque chargé');
        return Inertia::render('dashboards/Risque/Index');
    }

    public function dashboardAudit()
    {
        Log::info('📄 Dashboard Audit chargé');
        return Inertia::render('dashboards/Audit/Index');
    }
}