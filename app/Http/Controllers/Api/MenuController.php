<?php
// app/Http/Controllers/Api/MenuController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MenuController extends Controller
{
    /**
     * Retourne la structure complète des menus depuis la base
     */
    public function getMenuStructure(Request $request)
    {
        try {
            \Log::info('🔄 Chargement de la structure des menus pour user: ' . Auth::id());

            $menus = DB::table('menus')
                ->orderBy('parent_id')
                ->orderBy('sort')
                ->get([
                    'id',
                    'key',
                    'label', 
                    'icon',
                    'url',
                    'parent_id',
                    'sort',
                    'is_title',
                    'is_divider', 
                    'visible',
                    'badge_json',
                    'tooltip_json',
                    'meta_json',
                    'created_at',
                    'updated_at'
                ]);

            \Log::info('✅ Menus chargés: ' . $menus->count());

            return response()->json([
                'success' => true,
                'menus' => $menus,
                'count' => $menus->count()
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Erreur récupération menus: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du chargement des menus',
                'menus' => []
            ], 500);
        }
    }

    /**
     * Pour le super admin, retourne toujours true pour tous les menus
     */
    public function getMenuVisibility(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                \Log::warning('❌ Aucun utilisateur connecté');
                return response()->json([
                    'success' => true,
                    'visibility' => [],
                    'is_super_admin' => false
                ]);
            }

            \Log::info('🔍 Calcul visibilité menus pour: ' . $user->email);

            // Récupérer tous les menus
            $menus = DB::table('menus')->get(['id', 'key']);
            $visibility = [];

            // Super admin voit tout
            if ($user->email === 'admin@diaddem.local') {
                \Log::info('🎯 Super admin détecté, accès total');
                
                foreach ($menus as $menu) {
                    $visibility[$menu->key] = true;
                }
                
                return response()->json([
                    'success' => true,
                    'visibility' => $visibility,
                    'is_super_admin' => true,
                    'message' => 'Super admin - accès total'
                ]);
            }

            \Log::info('👤 Utilisateur normal, vérification permissions');

            // Pour les autres utilisateurs, vérifier les permissions
            foreach ($menus as $menu) {
                $hasAccess = $this->userHasMenuAccess($user, $menu->key);
                $visibility[$menu->key] = $hasAccess;
            }

            return response()->json([
                'success' => true,
                'visibility' => $visibility,
                'is_super_admin' => false,
                'message' => 'Permissions normales'
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Erreur visibilité menus: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du calcul de la visibilité',
                'visibility' => []
            ], 500);
        }
    }

    /**
     * Vérifie si l'utilisateur a accès à un menu spécifique
     */
    private function userHasMenuAccess($user, string $menuKey): bool
    {
        // Récupérer les permissions associées à ce menu
        $permissions = DB::table('menu_permission')
            ->join('permissions', 'menu_permission.permission_id', '=', 'permissions.id')
            ->join('menus', 'menu_permission.menu_id', '=', 'menus.id')
            ->where('menus.key', $menuKey)
            ->pluck('permissions.name')
            ->toArray();

        \Log::debug("Menu {$menuKey} - Permissions requises: " . implode(', ', $permissions));

        // Si le menu n'a pas de permissions associées, il est public
        if (empty($permissions)) {
            \Log::debug("Menu {$menuKey} - Public (pas de permissions)");
            return true;
        }

        // Vérifier si l'utilisateur a au moins une des permissions
        $hasAccess = $user->hasAnyPermission($permissions);
        
        \Log::debug("Menu {$menuKey} - Accès: " . ($hasAccess ? 'OUI' : 'NON'));
        
        return $hasAccess;
    }
}