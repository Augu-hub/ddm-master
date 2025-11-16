<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\DTO\MenuDTO;
use App\Models\Master\Menu;
use App\Models\User;
use Illuminate\Support\Collection;

class MenuService
{
    public function getTreeForUser(User $user): array
    {
        $isGlobal = (bool) session('is_global_admin', false) || $user->hasRole('super-admin');

        $roots = Menu::with([
                'children.children.children',
                'module', 'service', 'permissions', 'users',
            ])
            ->whereNull('parent_id')
            ->orderBy('sort')
            ->get();

        $filtered = $this->filterVisible($roots, $user, $isGlobal);

        // map -> DTO -> array
        return $filtered
            ->map(fn($m) => $this->toDTORecursive($m)->toArray())
            ->values()
            ->all();
    }

    public function getVisibilityForUser(User $user): array
    {
        $isGlobal = (bool) session('is_global_admin', false) || $user->hasRole('super-admin');

        $all = Menu::with(['module','permissions','users'])->orderBy('sort')->get();

        $vis = [];
        foreach ($all as $m) {
            $vis[$m->key] = $this->userCanSee($m, $user, $isGlobal) && (bool) $m->visible;
        }

        return $vis;
    }

    /** ---------- internes ---------- */

    private function filterVisible(Collection $menus, User $user, bool $isGlobal): Collection
    {
        return $menus->filter(fn($m) => $this->userCanSee($m, $user, $isGlobal) && (bool)$m->visible)
            ->map(function ($m) use ($user, $isGlobal) {
                $m->setRelation('children', $this->filterVisible($m->children, $user, $isGlobal));
                return $m;
            })
            ->values();
    }

    private function userCanSee($m, User $user, bool $isGlobal): bool
    {
        if ($isGlobal) {
            // super admin → voit tout, et pas d’authorization requise pour l’admin Param
            return true;
        }

        // Règle spéciale : section Administration dans le module PARAM
        // (si tu veux forcer l’accès admin Param à certains profils)
        // if (str_starts_with($m->key, 'param-admin-')) return $user->hasPermissionTo('param.admin.access');

        // module-link: affectation ou permission *.view
        if ($m->module) {
            $ok = $m->module->users()->where('user_id', $user->id)->exists()
               || $user->hasPermissionTo($m->module->code . '.view');
            if (!$ok) return false;
        }

        // permissions explicites
        if ($m->permissions()->exists()) {
            foreach ($m->permissions as $p) {
                if ($user->can($p->name)) return true;
            }
            return false;
        }

        // liste d’utilisateurs
        if ($m->users()->exists()) {
            return $m->users->contains('id', $user->id);
        }

        return true;
    }

    private function toDTORecursive(Menu $m): MenuDTO
    {
        $dto = MenuDTO::fromModel($m);
        $dto->children = $m->children
            ? $m->children->map(fn($c) => $this->toDTORecursive($c))->values()->all()
            : [];
        return $dto;
    }
}
