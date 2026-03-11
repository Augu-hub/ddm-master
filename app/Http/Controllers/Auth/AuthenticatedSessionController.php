<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status'           => $request->session()->get('status'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = Auth::user();

        Log::info('Connexion', [
            'user_id'    => $user->id,
            'email'      => $user->email,
            'role'       => $user->role       ?? 'non défini',
            'is_auditor' => $user->is_auditor  ?? 0,
        ]);

        // 1. ADMIN GLOBAL
        if ($this->isGlobalAdmin($user)) {
            $this->setSession([
                'is_global_admin' => true,
                'is_auditor'      => false,
                'tenant_id'       => null,
            ]);
            Session::forget('user_menus');
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // 2. RÉSOUDRE LE TENANT
        $tenantId = $this->resolveTenantId($user);
        if (!$tenantId) {
            Auth::logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Aucun tenant assigné à ce compte.']);
        }

        // 3. DÉTECTER AUDITEUR
        if ($this->isAuditorFromMaster($user, $tenantId)) {
            $auditCode = $this->resolveAuditorCode($user->email, $tenantId);
            $auditorId = $this->resolveAuditorId($user->email, $tenantId);

            $this->syncAuditorFlagOnMaster($user->id, $auditCode);

            $this->setSession([
                'is_auditor'      => true,
                'auditor_id'      => $auditorId,
                'auditor_code'    => $auditCode,
                'is_global_admin' => false,
                'tenant_id'       => $tenantId,
            ]);

            $menus = $this->buildUserMenus($tenantId, $user);
            Session::put('user_menus', $menus);

            return redirect()->route('auditor.dashboard');
        }

        // 4. UTILISATEUR STANDARD
        $this->setSession([
            'is_auditor'      => false,
            'is_global_admin' => false,
            'tenant_id'       => $tenantId,
        ]);

        $menus = $this->buildUserMenus($tenantId, $user);
        Session::put('user_menus', $menus);

        $tenantCount = DB::connection('mysql')->table('tenant_user')
            ->where('user_id', $user->id)->count();

        if ($tenantCount > 1) {
            return redirect()->route('select.tenant');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Session::forget([
            'tenant_id',
            'is_global_admin',
            'is_auditor',
            'auditor_id',
            'auditor_code',
            'user_menus',
        ]);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    // =========================================================
    // HELPERS — connexion MASTER uniquement
    // =========================================================

    private function isGlobalAdmin($user): bool
    {
        if (!$user) return false;
        if (isset($user->role) && $user->role === 'admin') return true;
        try {
            $whitelist = array_filter(array_map('trim', explode(',', (string) env('GLOBAL_ADMINS', 'admin@diaddem.local'))));
            return in_array($user->email, $whitelist, true);
        } catch (\Throwable $e) {
            return $user->email === 'admin@diaddem.local';
        }
    }

    private function isAuditorFromMaster($user, int $tenantId): bool
    {
        if (isset($user->is_auditor) && (int) $user->is_auditor === 1) return true;
        if (isset($user->role) && $user->role === 'auditor') return true;

        $dbName = $this->getTenantDbName($tenantId);
        if (!$dbName) return false;

        try {
            return DB::connection('mysql')
                ->table(DB::raw("`{$dbName}`.`auditors`"))
                ->where('email', $user->email)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->exists();
        } catch (\Throwable $e) {
            Log::warning('isAuditorFromMaster error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function resolveAuditorCode(string $email, int $tenantId): ?string
    {
        $dbName = $this->getTenantDbName($tenantId);
        if (!$dbName) return null;
        try {
            return DB::connection('mysql')
                ->table(DB::raw("`{$dbName}`.`auditors`"))
                ->where('email', $email)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->value('audit_code');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function resolveAuditorId(string $email, int $tenantId): ?int
    {
        $dbName = $this->getTenantDbName($tenantId);
        if (!$dbName) return null;
        try {
            $id = DB::connection('mysql')
                ->table(DB::raw("`{$dbName}`.`auditors`"))
                ->where('email', $email)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->value('id');
            return $id ? (int) $id : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function resolveTenantId($user): ?int
    {
        if (method_exists($user, 'tenants')) {
            $tenants = $user->tenants ?? collect();
            if ($tenants->isNotEmpty()) return (int) $tenants->first()->id;
        }
        $pivot = DB::connection('mysql')->table('tenant_user')
            ->where('user_id', $user->id)
            ->first();
        return $pivot ? (int) $pivot->tenant_id : null;
    }

    private function getTenantDbName(int $tenantId): ?string
    {
        try {
            $tenant = DB::connection('mysql')
                ->table('tenants')
                ->where('id', $tenantId)
                ->first();
            if (!$tenant) return null;
            return $tenant->db_name ?? $tenant->database ?? $tenant->tenancy_db_name ?? null;
        } catch (\Throwable $e) {
            Log::warning('getTenantDbName error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function syncAuditorFlagOnMaster(int $userId, ?string $auditCode): void
    {
        try {
            DB::connection('mysql')->table('users')
                ->where('id', $userId)
                ->update([
                    'is_auditor'   => 1,
                    'role'         => 'auditor',
                    'auditor_code' => $auditCode,
                    'updated_at'   => now(),
                ]);
        } catch (\Throwable $e) {
            Log::warning('syncAuditorFlagOnMaster error', ['error' => $e->getMessage()]);
        }
    }

    private function setSession(array $data): void
    {
        foreach ($data as $key => $value) {
            Session::put($key, $value);
        }
    }

    // =========================================================
    // CONSTRUCTION DES MENUS
    // Source : ddmparam.audit_types + ddmparam.audit_type_forms
    // Lien   : tenant.mission_types.audit_type_code
    // =========================================================

    /**
     * Construit les menus de navigation pour l'utilisateur connecté.
     *
     * Flux :
     *  1. Charge les mission_types ACTIFS du tenant (avec audit_type_code)
     *  2. Pour chaque mission_type, retrouve l'audit_type correspondant
     *     dans ddmparam.audit_types (via audit_type_code)
     *  3. Charge les formulaires depuis ddmparam.audit_type_forms
     *     groupés par phase_num
     *  4. Construit l'arbre phase → formulaires avec sous-formulaires
     */
    private function buildUserMenus(int $tenantId, $user): array
    {
        $dbName = $this->getTenantDbName($tenantId);
        if (!$dbName) {
            Log::error('buildUserMenus: DB tenant introuvable', ['tenant_id' => $tenantId]);
            return [];
        }

        Log::info('buildUserMenus: démarrage', [
            'db'      => $dbName,
            'user_id' => $user->id,
            'tenant'  => $tenantId,
        ]);

        $conn  = DB::connection('mysql');
        $menus = [];

        try {
            // ── 1. Mission types du tenant avec leur code audit ──────────
            $missionTypes = $conn
                ->table(DB::raw("`{$dbName}`.`mission_types` as mt"))
                ->where('mt.is_active', 1)
                ->orderBy('mt.sort_order')
                ->get([
                    'mt.id',
                    'mt.code',
                    'mt.label',
                    'mt.audit_type_code',
                    'mt.audit_type_label',
                    'mt.audit_color',
                    'mt.audit_icon',
                ]);

            if ($missionTypes->isEmpty()) {
                Log::warning('buildUserMenus: aucun mission_type actif', ['db' => $dbName]);
                return [];
            }

            // ── 2. Tous les audit_types depuis ddmparam (index par code) ─
            $auditTypes = $conn
                ->table('ddmparam.audit_types')
                ->where('is_active', 1)
                ->get()
                ->keyBy('code');      // ['AC' => {...}, 'AF' => {...}, ...]

            // ── 3. Tous les formulaires ddmparam (index par audit_type_id) ─
            $allForms = $conn
                ->table('ddmparam.audit_type_forms as f')
                ->join('ddmparam.audit_types as at', 'at.id', '=', 'f.audit_type_id')
                ->where('f.is_active', 1)
                ->orderBy('f.phase_num')
                ->orderBy('f.sort_order')
                ->get([
                    'f.id',
                    'f.audit_type_id',
                    'at.code as audit_type_code',
                    'f.phase_num',
                    'f.phase_label',
                    'f.parent_id',
                    'f.code',
                    'f.label',
                    'f.url_path',
                    'f.icon',
                    'f.sort_order',
                ]);

            // Grouper les formulaires par code audit_type
            $formsByType = $allForms->groupBy('audit_type_code');

            // ── 4. Construire un menu par mission_type ───────────────────
            foreach ($missionTypes as $mt) {

                // Résoudre l'audit_type : priorité à la colonne tenant,
                // fallback sur le mapping statique si colonne pas encore remplie
                $auditTypeCode = $mt->audit_type_code
                    ?? $this->resolveAuditTypeCodeFallback($mt->code);

                $auditType = $auditTypeCode ? ($auditTypes[$auditTypeCode] ?? null) : null;

                // Couleur et icône : tenant > ddmparam > défaut
                $color = $mt->audit_color
                    ?? ($auditType ? $auditType->color : '#6c757d');
                $icon  = $mt->audit_icon
                    ?? ($auditType ? $auditType->icon  : 'ti ti-folder');
                $label = $mt->audit_type_label
                    ?? ($auditType ? $auditType->label : $mt->label);

                // Formulaires de ce type d'audit depuis ddmparam
                $typeForms = $auditTypeCode
                    ? ($formsByType[$auditTypeCode] ?? collect())
                    : collect();

                // Construire l'arbre par phase
                $phases = $this->buildPhasesTree($typeForms);

                $menus[] = [
                    'mission_type' => [
                        'id'              => $mt->id,
                        'code'            => $mt->code,
                        'label'           => $mt->label,
                        'audit_type_code' => $auditTypeCode,
                        'audit_type_label'=> $label,
                        'color'           => $color,
                        'icon'            => $icon,
                    ],
                    'phases' => $phases,   // arbre phases → formulaires
                ];
            }

        } catch (\Throwable $e) {
            Log::error('buildUserMenus: exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }

        Log::info('buildUserMenus: terminé', ['nb_types' => count($menus)]);
        return $menus;
    }

    /**
     * Construit l'arbre [phase → [formulaires avec enfants]]
     * depuis une collection de formulaires ddmparam.
     */
    private function buildPhasesTree(\Illuminate\Support\Collection $forms): array
    {
        if ($forms->isEmpty()) return [];

        // Grouper par phase
        $byPhase = $forms->groupBy('phase_num');
        $phases  = [];

        foreach ($byPhase as $phaseNum => $phaseForms) {
            $phaseLabel = $phaseForms->first()->phase_label;

            // Racines (pas de parent)
            $roots = $phaseForms->whereNull('parent_id')->values();
            $tree  = [];

            foreach ($roots as $root) {
                $children = $phaseForms->where('parent_id', $root->id)->values();
                $tree[] = [
                    'id'         => $root->id,
                    'code'       => $root->code,
                    'label'      => $root->label,
                    'url_path'   => $root->url_path,
                    'icon'       => $root->icon,
                    'sort_order' => $root->sort_order,
                    'children'   => $children->map(fn($c) => [
                        'id'         => $c->id,
                        'code'       => $c->code,
                        'label'      => $c->label,
                        'url_path'   => $c->url_path,
                        'icon'       => $c->icon,
                        'sort_order' => $c->sort_order,
                    ])->all(),
                ];
            }

            $phases[] = [
                'phase_num'   => (int) $phaseNum,
                'phase_label' => $phaseLabel,
                'forms'       => $tree,
            ];
        }

        // Trier par numéro de phase
        usort($phases, fn($a, $b) => $a['phase_num'] <=> $b['phase_num']);

        return $phases;
    }

    /**
     * Fallback : mapping statique code mission_type tenant → code audit ddmparam
     * Utilisé si la colonne audit_type_code n'est pas encore remplie dans le tenant.
     */
    private function resolveAuditTypeCodeFallback(string $missionTypeCode): ?string
    {
        return [
            'AC' => 'AC',
            'AP' => 'AP',
            'AF' => 'AF',
            'AM' => 'AM',
            'RP' => 'RP',
            'ES' => 'ES',
        ][$missionTypeCode] ?? null;
    }
}