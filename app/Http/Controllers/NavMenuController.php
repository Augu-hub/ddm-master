<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Param\Entite;

use Illuminate\Http\Request;
class NavMenuController extends Controller
{
    public function entities()
    {
        $rows = Entite::select('id','name')
            ->orderBy('name')
            ->get();

        // items pour le menu
        $items = $rows->map(fn($e) => [
            'key'   => 'entity_'.$e->id,
            'label' => $e->name,
            'icon'  => 'ti ti-building-skyscraper',
            // lien vers ton organigramme d'entité (PAS par projet)
            'url'   => '/param/charts/entity?entity_id='.$e->id,
        ])->values();

        return response()->json(['ok' => true, 'items' => $items]);
    }
    


    public function getEntities(Request $request)
    {
        \Log::info('🔄 CHARGEMENT DES ENTITÉS POUR LE MENU');

        try {
            // Maintenant la connexion tenant est établie
            $entities = Entite::orderBy('level')
                ->orderBy('name')
                ->get(['id', 'name', 'code_base']);

            \Log::info("✅ {$entities->count()} ENTITÉS CHARGÉES AVEC SUCCÈS");

            return response()->json($entities);

        } catch (\Exception $e) {
            \Log::error('❌ ERREUR CHARGEMENT ENTITÉS: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }
}

