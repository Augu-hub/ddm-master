<?php

namespace App\Support;

use Illuminate\Support\Arr;

class IconResolver
{
    public static function forService(?string $serviceCode): ?string
    {
        if (!$serviceCode) return null;
        return config("diaddem.icons.services.$serviceCode");
    }

    public static function forModule(?string $moduleCode): ?string
    {
        if (!$moduleCode) return null;
        return config("diaddem.icons.modules.$moduleCode");
    }

    public static function forMenu(?string $menuKey, ?string $moduleCode = null, ?string $serviceCode = null): string
    {
        $byKey = $menuKey ? config("diaddem.icons.menus.$menuKey") : null;
        if ($byKey) return $byKey;

        // Essai par module/service si tu veux des règles plus fines
        $byModule = self::forModule($moduleCode);
        if ($byModule) return $byModule;

        $byService = self::forService($serviceCode);
        if ($byService) return $byService;

        return config('diaddem.icons.fallback', 'ti ti-apps');
    }

    /** Utilitaire : normalise une valeur d’icône (string|array|null → string|null) */
    public static function normalize(?string $icon): ?string
    {
        $icon = is_string($icon) ? trim($icon) : null;
        return $icon !== '' ? $icon : null;
    }
}
