<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Param\Entite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NavMenuController extends Controller
{
    /** GET /api/nav/entities-menu */
    public function entities()
    {
        if (!$this->tenantReady()) {
            Log::warning('⛔ Tenant non prêt (entities menu) — on renvoie une liste vide');
            return response()->json([
                'ok'    => true,
                'items' => [],            // menu vide = pas de crash côté front
                'meta'  => ['reason' => 'tenant-not-ready'],
            ]);
        }

        $rows = Entite::query()
            ->select('id','name')
            ->orderBy('name')
            ->get();

        $items = $rows->map(fn($e) => [
            'key'   => 'entity_'.$e->id,
            'label' => $e->name,
            'icon'  => 'ti ti-building-skyscraper',
            // ajuste si tu préfères la route param/charts/entity-functions/{id}
            'url'   => '/param/charts/entity?entity_id='.$e->id,
        ])->values();

        return response()->json(['ok' => true, 'items' => $items]);
    }

    /** GET /api/nav/entities */
    public function getEntities(Request $request)
    {
        Log::info('🔄 CHARGEMENT DES ENTITÉS POUR LE MENU');

        if (!$this->tenantReady()) {
            Log::warning('⛔ Tenant non prêt (getEntities) — retour []');
            return response()->json([], 200);
        }

        try {
            $entities = Entite::query()
                ->orderBy('level')
                ->orderBy('name')
                ->get(['id','name','code_base','parent_id']);

            Log::info("✅ {$entities->count()} entités chargées");
            return response()->json($entities);

        } catch (\Throwable $e) {
            Log::error('❌ ERREUR CHARGEMENT ENTITÉS: '.$e->getMessage());
            // on renvoie un 200 avec tableau vide pour ne pas casser le front
            return response()->json([], 200);
        }
    }

    /**
     * Vérifie si la connexion tenant est bien configurée et exploitable.
     * - database non vide
     * - PDO accessible (et donc DB sélectionnée)
     */
    private function tenantReady(): bool
    {
        try {
            $cfg = config('database.connections.tenant');
            if (empty($cfg) || empty($cfg['database'])) {
                return false;
            }
            DB::connection('tenant')->getPdo(); // force la connexion
            return true;
        } catch (\Throwable $e) {
            Log::warning('Tenant KO: '.$e->getMessage());
            return false;
        }
    }
}
