<?php

namespace App\Services\Audit;

use Illuminate\Support\Facades\{Auth, DB, Log, Session};

/**
 * ══════════════════════════════════════════════════════════════════
 *  UserMenuSessionService — v3
 * ══════════════════════════════════════════════════════════════════
 *
 *  Charge TOUS les formulaires ddmparam pour chaque mission_type
 *  du tenant. Un menu est toujours présent ; il est marqué
 *  'available = false' s'il n'a pas de url_path → le layout
 *  l'affiche barré/grisé.
 *
 *  Logique :
 *   1. Lire mission_types du tenant (fruitiva)
 *   2. Pour chaque mission_type → résoudre audit_type_code
 *   3. Charger TOUS les formulaires ddmparam de ce type (is_active=1)
 *   4. Construire arbre : phase_num → racines → enfants
 *   5. Marquer available = (url_path non vide)
 *   6. Stocker en session sous 'user_menus'
 *
 *  Structure session :
 *  [
 *    {
 *      mission_type: { id, code, label, audit_type_code, audit_type_label, color, icon },
 *      phases: [
 *        {
 *          phase_num: 1,
 *          phase_label: "Préparation",
 *          forms: [
 *            {
 *              id, code, label, description, route_name, url_path,
 *              icon, sort_order, parent_id, available,
 *              children: [ ... ]
 *            }
 *          ]
 *        },
 *        ...  (toutes les phases 1-5 présentes même si vides)
 *      ]
 *    },
 *    ...
 *  ]
 * ══════════════════════════════════════════════════════════════════
 */
class UserMenuSessionService
{
    private const SESSION_KEY  = 'user_menus';
    private const SESSION_META = 'user_menus_meta';
    private const TTL_MINUTES  = 60;

    // Labels canoniques des phases ddmparam
    private const PHASE_LABELS = [
        1 => 'Préparation',
        2 => 'Réalisation',
        3 => 'Conclusion',
        4 => 'Suivi',
        5 => 'Recommandations',
    ];

    // Fallback code mission_type → audit_type_code ddmparam
    private const CODE_MAP = [
        'AC' => 'AC', 'AF' => 'AF', 'AP' => 'AP',
        'AM' => 'AM', 'RP' => 'RP', 'ES' => 'ES',
        'CONFORMITE'  => 'AC', 'FRAUDE'    => 'AF',
        'PERFORMANCE' => 'AP', 'MARCHES'   => 'AM',
        'REVUE'       => 'RP', 'EVALUATION'=> 'ES',
    ];

    // ══════════════════════════════════════════════════════════════
    //  POINTS D'ENTRÉE PUBLICS
    // ══════════════════════════════════════════════════════════════

    public static function getOrLoad(?string $tenantDb = null, bool $force = false): array
    {
        if (!$force && self::isCacheValid()) {
            return Session::get(self::SESSION_KEY, []);
        }

        $db = $tenantDb ?? self::resolveTenantDb();
        if (!$db) {
            Log::warning('[UserMenuSession] DB tenant introuvable.');
            return [];
        }

        $menus = self::build($db);
        self::persist($menus);
        return $menus;
    }

    public static function refresh(?string $tenantDb = null): array
    {
        return self::getOrLoad($tenantDb, true);
    }

    public static function clear(): void
    {
        Session::forget(self::SESSION_KEY);
        Session::forget(self::SESSION_META);
    }

    /** Retourne les forms d'un audit_type_code, indexés par 'code' */
    public static function getFormsByAuditTypeCode(string $code): array
    {
        foreach (Session::get(self::SESSION_KEY, []) as $item) {
            if (strtoupper($item['mission_type']['audit_type_code'] ?? '') === strtoupper($code)) {
                return self::flatten($item['phases'] ?? []);
            }
        }
        return [];
    }

    /** Retourne les forms d'un mission_type_id, indexés par 'code' */
    public static function getFormsByMissionTypeId(int $id): array
    {
        foreach (Session::get(self::SESSION_KEY, []) as $item) {
            if ((int) ($item['mission_type']['id'] ?? 0) === $id) {
                return self::flatten($item['phases'] ?? []);
            }
        }
        return [];
    }

    // ══════════════════════════════════════════════════════════════
    //  CONSTRUCTION PRINCIPALE
    // ══════════════════════════════════════════════════════════════

    private static function build(string $dbName): array
    {
        try {
            $conn = DB::connection('mysql');

            // ── 1. Mission types du tenant ────────────────────────────────
            $missionTypes = $conn
                ->table(DB::raw("`{$dbName}`.`mission_types` as mt"))
                ->where('mt.is_active', 1)
                ->orderBy('mt.sort_order')
                ->get([
                    'mt.id', 'mt.code', 'mt.label',
                    'mt.audit_type_code', 'mt.audit_type_label',
                    'mt.audit_color', 'mt.audit_icon',
                ]);

            if ($missionTypes->isEmpty()) {
                Log::warning("[UserMenuSession] Aucun mission_type actif dans [{$dbName}].");
                return self::buildFromDdmparamOnly($conn);
            }

            // ── 2. Audit types ddmparam (index par code) ──────────────────
            $auditTypes = $conn
                ->table('ddmparam.audit_types')
                ->get()              // tous, actifs ou non — on filtre juste les forms
                ->keyBy('code');

            // ── 3. TOUS les formulaires actifs ddmparam ───────────────────
            //    Une seule requête pour toutes les données
            $allForms = $conn
                ->table('ddmparam.audit_type_forms as f')
                ->join('ddmparam.audit_types as at', 'at.id', '=', 'f.audit_type_id')
                ->where('f.is_active', 1)
                ->orderBy('f.phase_num')
                ->orderBy('f.sort_order')
                ->orderBy('f.id')
                ->get([
                    'f.id',
                    'f.audit_type_id',
                    'at.code   as audit_type_code',
                    'f.phase_num',
                    'f.phase_label',
                    'f.parent_id',
                    'f.code',
                    'f.label',
                    'f.description',
                    'f.route_name',
                    'f.url_path',
                    'f.icon',
                    'f.sort_order',
                ]);

            // Grouper par audit_type_code
            $formsByCode = $allForms->groupBy('audit_type_code');

            // ── 4. Construire un menu par mission_type ────────────────────
            $menus = [];
            foreach ($missionTypes as $mt) {

                $auditCode = !empty($mt->audit_type_code)
                    ? strtoupper($mt->audit_type_code)
                    : (self::CODE_MAP[strtoupper($mt->code)] ?? null);

                $auditType = $auditCode ? ($auditTypes[$auditCode] ?? null) : null;

                $color = !empty($mt->audit_color)  ? $mt->audit_color  : ($auditType?->color  ?? '#64748B');
                $icon  = !empty($mt->audit_icon)   ? $mt->audit_icon   : ($auditType?->icon   ?? 'ti ti-clipboard');
                $label = !empty($mt->audit_type_label) ? $mt->audit_type_label : ($auditType?->label ?? $mt->label);

                // Formulaires ddmparam de ce type d'audit
                $typeForms = $auditCode ? ($formsByCode[$auditCode] ?? collect()) : collect();

                // Construire l'arbre complet avec TOUTES les phases (1→5)
                $phases = self::buildPhasesTree($typeForms);

                $menus[] = [
                    'mission_type' => [
                        'id'               => $mt->id,
                        'code'             => $mt->code,
                        'label'            => $mt->label,
                        'audit_type_code'  => $auditCode,
                        'audit_type_label' => $label,
                        'color'            => $color,
                        'icon'             => $icon,
                    ],
                    'phases' => $phases,
                ];
            }

            Log::info("[UserMenuSession] ✅ [{$dbName}] → " . count($menus) . " type(s) chargés"
                . " | " . $allForms->count() . " formulaires ddmparam");

            return $menus;

        } catch (\Throwable $e) {
            Log::error('[UserMenuSession] build() exception: ' . $e->getMessage()
                . ' | ' . $e->getFile() . ':' . $e->getLine());
            return [];
        }
    }

    /** Fallback : charger directement tous les types ddmparam sans tenant */
    private static function buildFromDdmparamOnly($conn): array
    {
        $menus = [];

        $auditTypes = $conn->table('ddmparam.audit_types')
            ->where('is_active', 1)
            ->orderBy('label')
            ->get();

        $allForms = $conn
            ->table('ddmparam.audit_type_forms as f')
            ->join('ddmparam.audit_types as at', 'at.id', '=', 'f.audit_type_id')
            ->where('f.is_active', 1)
            ->orderBy('f.phase_num')
            ->orderBy('f.sort_order')
            ->orderBy('f.id')
            ->get([
                'f.id', 'f.audit_type_id', 'at.code as audit_type_code',
                'f.phase_num', 'f.phase_label', 'f.parent_id',
                'f.code', 'f.label', 'f.description',
                'f.route_name', 'f.url_path', 'f.icon', 'f.sort_order',
            ]);

        $formsByCode = $allForms->groupBy('audit_type_code');

        foreach ($auditTypes as $at) {
            $menus[] = [
                'mission_type' => [
                    'id'               => null,
                    'code'             => $at->code,
                    'label'            => $at->label,
                    'audit_type_code'  => $at->code,
                    'audit_type_label' => $at->label,
                    'color'            => $at->color ?? '#64748B',
                    'icon'             => $at->icon  ?? 'ti ti-clipboard',
                ],
                'phases' => self::buildPhasesTree($formsByCode[$at->code] ?? collect()),
            ];
        }

        return $menus;
    }

    // ══════════════════════════════════════════════════════════════
    //  CONSTRUCTION DE L'ARBRE PHASES
    // ══════════════════════════════════════════════════════════════

    /**
     * Construit l'arbre complet des phases depuis une collection plate.
     * TOUTES les phases (1→5) sont présentes même si vides.
     * Chaque formulaire a 'available' = true si url_path non vide.
     */
    private static function buildPhasesTree(\Illuminate\Support\Collection $forms): array
    {
        // Construire récursivement les enfants d'un nœud
        $buildChildren = function (int $parentId) use ($forms, &$buildChildren): array {
            return $forms
                ->where('parent_id', $parentId)
                ->sortBy(['sort_order', 'id'])
                ->values()
                ->map(fn($f) => [
                    'id'          => $f->id,
                    'code'        => $f->code,
                    'label'       => $f->label,
                    'description' => $f->description ?? null,
                    'route_name'  => $f->route_name  ?? null,
                    'url_path'    => $f->url_path    ?? null,
                    'icon'        => $f->icon        ?? 'ti ti-file-description',
                    'sort_order'  => (int) $f->sort_order,
                    'parent_id'   => (int) $f->parent_id,
                    // ← marqué indisponible si pas d'URL
                    'available'   => !empty($f->url_path),
                    'children'    => $buildChildren($f->id),
                ])
                ->toArray();
        };

        // Grouper les racines par phase_num
        $rootsByPhase = $forms
            ->whereNull('parent_id')
            ->groupBy('phase_num');

        $phases = [];

        // Toujours produire les 5 phases dans l'ordre, même si vides
        foreach (self::PHASE_LABELS as $phaseNum => $defaultLabel) {
            $roots = $rootsByPhase->get($phaseNum, collect());

            // Label depuis les données ddmparam ou défaut canonique
            $phaseLabel = $roots->isNotEmpty()
                ? ($roots->first()->phase_label ?? $defaultLabel)
                : $defaultLabel;

            $phases[] = [
                'phase_num'   => $phaseNum,
                'phase_label' => $phaseLabel,
                'forms'       => $roots
                    ->sortBy(['sort_order', 'id'])
                    ->values()
                    ->map(fn($f) => [
                        'id'          => $f->id,
                        'code'        => $f->code,
                        'label'       => $f->label,
                        'description' => $f->description ?? null,
                        'route_name'  => $f->route_name  ?? null,
                        'url_path'    => $f->url_path    ?? null,
                        'icon'        => $f->icon        ?? 'ti ti-file-description',
                        'sort_order'  => (int) $f->sort_order,
                        'parent_id'   => null,
                        // ← marqué indisponible si pas d'URL
                        'available'   => !empty($f->url_path),
                        'children'    => $buildChildren($f->id),
                    ])
                    ->toArray(),
            ];
        }

        return $phases;
    }

    // ══════════════════════════════════════════════════════════════
    //  FLATTEN — tableau plat indexé par code (pour les controllers)
    // ══════════════════════════════════════════════════════════════

    public static function flatten(array $phases): array
    {
        $result = [];

        usort($phases, fn($a, $b) => ($a['phase_num'] ?? 0) <=> ($b['phase_num'] ?? 0));

        foreach ($phases as $phase) {
            $phaseNum   = (int)    ($phase['phase_num']   ?? 0);
            $phaseLabel = (string) ($phase['phase_label'] ?? '');

            $walk = function (array $forms, ?string $parentCode = null)
                use (&$walk, &$result, $phaseNum, $phaseLabel)
            {
                usort($forms, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

                foreach ($forms as $form) {
                    $code = $form['code'] ?? null;
                    if (!$code) continue;

                    $result[$code] = [
                        'code'        => $code,
                        'label'       => $form['label']       ?? $code,
                        'description' => $form['description'] ?? null,
                        'route_name'  => $form['route_name']  ?? null,
                        'url_path'    => $form['url_path']    ?? null,
                        'icon'        => $form['icon']        ?? 'ti ti-file-description',
                        'phase_num'   => $phaseNum,
                        'phase_label' => $phaseLabel,
                        'parent_code' => $parentCode,
                        'sort_order'  => (int) ($form['sort_order'] ?? 0),
                        'level'       => $parentCode ? 2 : 1,
                        'available'   => $form['available']   ?? !empty($form['url_path']),
                    ];

                    if (!empty($form['children'])) {
                        $walk($form['children'], $code);
                    }
                }
            };

            $walk($phase['forms'] ?? []);
        }

        return $result;
    }

    // alias pour compatibilité
    public static function flattenForms(array $phases): array
    {
        return self::flatten($phases);
    }

    // ══════════════════════════════════════════════════════════════
    //  SESSION
    // ══════════════════════════════════════════════════════════════

    private static function persist(array $menus): void
    {
        Session::put(self::SESSION_KEY, $menus);
        Session::put(self::SESSION_META, [
            'loaded_at' => now()->timestamp,
            'count'     => count($menus),
            'user_id'   => Auth::id(),
        ]);
    }

    private static function isCacheValid(): bool
    {
        if (!Session::has(self::SESSION_KEY)) return false;

        $meta = Session::get(self::SESSION_META, []);
        if (empty($meta['loaded_at'])) return false;

        if (Auth::id() && ($meta['user_id'] ?? null) !== Auth::id()) return false;

        return (now()->timestamp - (int) $meta['loaded_at']) < (self::TTL_MINUTES * 60);
    }

    private static function resolveTenantDb(): ?string
    {
        // 1. Session middleware tenant
        $s = Session::get('tenant_db') ?? Session::get('current_tenant_db');
        if ($s) return $s;

        // 2. Config
        $c = config('tenant.database') ?? config('app.tenant_db');
        if ($c) return $c;

        // 3. Connexion tenant active
        try {
            $db = DB::connection('tenant')->getDatabaseName();
            if ($db) return $db;
        } catch (\Throwable $e) {}

        return null;
    }
}