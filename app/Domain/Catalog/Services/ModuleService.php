<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\DTO\ModuleDTO;
use App\Models\Master\Module;
use App\Models\User;

class ModuleService
{
    public function getCardsForUser(User $user): array
    {
        $isGlobal = (bool) session('is_global_admin', false) || $user->hasRole('super-admin');

        $q = Module::with('service')->where('is_active', true)->orderBy('sort');

        if (!$isGlobal) {
            $q->whereHas('users', fn($x) => $x->where('user_id', $user->id));
        }

        return $q->get()->map(fn($m) => ModuleDTO::fromModel($m)->toArray())->all();
    }
}
