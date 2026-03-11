<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MissionTypeController extends Controller
{
    private function db() { return DB::connection(); }

    private function moduleId(): ?int
    {
        return $this->db()->table('modules')->where('code', 'audit.core')->value('id');
    }

    private function toSlug(string $label): string
    {
        $s = mb_strtolower(trim($label));
        $map = [
            'à'=>'a','â'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
            'î'=>'i','ï'=>'i','ô'=>'o','ù'=>'u','û'=>'u','ç'=>'c',
            'œ'=>'oe',"'"=>'-',"\u{2019}"=>'-',
        ];
        $s = strtr($s, $map);
        $s = preg_replace('/[^a-z0-9\s-]/', '', $s);
        $s = preg_replace('/[\s-]+/', '-', trim($s));
        return substr($s, 0, 80);
    }

    private function loadMTAuditTypes(int $missionTypeId): \Illuminate\Support\Collection
    {
        return $this->db()
            ->table('mission_type_audit_types as mtat')
            ->join('audit_types as at', 'at.id', '=', 'mtat.audit_type_id')
            ->where('mtat.mission_type_id', $missionTypeId)
            ->where('at.is_active', 1)
            ->orderBy('at.sort_order')
            ->get(['at.id','at.code','at.label','at.color','at.icon']);
    }

    private function loadFormAuditTypes(int $formId): \Illuminate\Support\Collection
    {
        return $this->db()
            ->table('mission_type_form_audit_types as mtfat')
            ->join('audit_types as at', 'at.id', '=', 'mtfat.audit_type_id')
            ->where('mtfat.form_id', $formId)
            ->where('at.is_active', 1)
            ->orderBy('at.sort_order')
            ->get(['at.id','at.code','at.label','at.color','at.icon']);
    }

    private function syncMTAuditTypes(int $missionTypeId, array $ids): void
    {
        $this->db()->table('mission_type_audit_types')
            ->where('mission_type_id', $missionTypeId)->delete();
        foreach (array_unique(array_filter($ids)) as $atId) {
            $this->db()->table('mission_type_audit_types')->insertOrIgnore([
                'mission_type_id' => $missionTypeId,
                'audit_type_id'   => (int) $atId,
                'created_at'      => now(),
            ]);
        }
    }

    private function syncFormAuditTypesArr(int $formId, array $ids): void
    {
        $this->db()->table('mission_type_form_audit_types')
            ->where('form_id', $formId)->delete();
        foreach (array_unique(array_filter($ids)) as $atId) {
            $this->db()->table('mission_type_form_audit_types')->insertOrIgnore([
                'form_id'       => $formId,
                'audit_type_id' => (int) $atId,
                'created_at'    => now(),
            ]);
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // PAGE PRINCIPALE
    // ────────────────────────────────────────────────────────────────────────
    public function index(): InertiaResponse
    {
        try {
            Log::info('[MissionTypes] index()');

            $allAuditTypes = $this->db()->table('audit_types')
                ->where('is_active', 1)->orderBy('sort_order')
                ->get(['id','code','label','short_label','color','icon']);

            $types = $this->db()->table('mission_types')
                ->orderBy('sort_order')->get()
                ->map(function ($t) {
                    $phases = $this->db()->table('mission_type_forms')
                        ->where('mission_type_id', $t->id)->where('level', 1)
                        ->orderBy('sort_order')->get()
                        ->map(function ($p) use ($t) {
                            $p->sub_forms = $this->db()->table('mission_type_forms')
                                ->where('mission_type_id', $t->id)->where('parent_id', $p->id)
                                ->orderBy('sort_order')->get()
                                ->map(function ($sub) {
                                    $sub->audit_types = $this->loadFormAuditTypes($sub->id);
                                    return $sub;
                                });
                            $p->audit_types = $this->loadFormAuditTypes($p->id);
                            return $p;
                        });
                    $t->phases      = $phases;
                    $t->forms_count = $this->db()->table('mission_type_forms')
                        ->where('mission_type_id', $t->id)->count();
                    $t->menus_count = $this->db()->table('mission_type_menus')
                        ->where('mission_type_id', $t->id)->count();
                    $t->audit_types = $this->loadMTAuditTypes($t->id);
                    return $t;
                });

            $stats = [
                'total_types'  => $types->count(),
                'active_types' => $types->where('is_active', 1)->count(),
                'total_phases' => $types->sum(fn($t) => $t->phases->count()),
                'total_forms'  => $types->sum(fn($t) => $t->forms_count),
            ];

            return Inertia::render('dashboards/Audit/Param/MissionTypes/index', [
                'missionTypes'  => $types,
                'allAuditTypes' => $allAuditTypes,
                'stats'         => $stats,
            ]);

        } catch (\Throwable $e) {
            Log::error('[MissionTypes] CRASH', ['error' => $e->getMessage(), 'line' => $e->getLine()]);
            throw $e;
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // CRUD TYPES
    // ────────────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'code'             => 'required|string|max:50',
            'label'            => 'required|string|max:200',
            'short_label'      => 'nullable|string|max:100',
            'description'      => 'nullable|string',
            'color'            => 'nullable|string|max:20',
            'icon'             => 'nullable|string|max:100',
            'sort_order'       => 'nullable|integer',
            'audit_type_ids'   => 'nullable|array',
            'audit_type_ids.*' => 'integer',
        ]);
        $data['code'] = strtoupper(trim($data['code']));
        if ($this->db()->table('mission_types')->where('code', $data['code'])->exists()) {
            return response()->json(['success' => false, 'error' => 'Ce code existe déjà.'], 422);
        }
        $auditTypeIds = $data['audit_type_ids'] ?? [];
        unset($data['audit_type_ids']);
        $data += ['is_active' => 1, 'sort_order' => $data['sort_order'] ?? 0,
                  'created_at' => now(), 'updated_at' => now()];

        $typeId = $this->db()->table('mission_types')->insertGetId($data);
        $this->createTypeMenu($typeId, $data['code'], $data['label'], $data['icon'] ?? 'ti ti-clipboard-list');
        if ($auditTypeIds) $this->syncMTAuditTypes($typeId, $auditTypeIds);

        return response()->json(['success' => true, 'type_id' => $typeId,
            'message' => "Type {$data['code']} créé."]);
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'label'            => 'required|string|max:200',
            'short_label'      => 'nullable|string|max:100',
            'description'      => 'nullable|string',
            'color'            => 'nullable|string|max:20',
            'icon'             => 'nullable|string|max:100',
            'sort_order'       => 'nullable|integer',
            'is_active'        => 'boolean',
            'audit_type_ids'   => 'nullable|array',
            'audit_type_ids.*' => 'integer',
        ]);
        $auditTypeIds = array_key_exists('audit_type_ids', $data) ? $data['audit_type_ids'] : null;
        unset($data['audit_type_ids']);
        $data['updated_at'] = now();
        $this->db()->table('mission_types')->where('id', $id)->update($data);
        $type = $this->db()->table('mission_types')->find($id);
        if ($type) {
            $this->db()->table('menus')
                ->where('key', 'audit.core.missions.' . strtolower($type->code))
                ->update(['label' => $data['label'], 'icon' => $data['icon'] ?? $type->icon]);
        }
        if ($auditTypeIds !== null) $this->syncMTAuditTypes($id, $auditTypeIds);
        return response()->json(['success' => true, 'message' => 'Type mis à jour.']);
    }

    public function destroy(int $id)
    {
        $type = $this->db()->table('mission_types')->find($id);
        if (!$type) return response()->json(['success' => false, 'error' => 'Introuvable.'], 404);
        $menuIds = $this->db()->table('mission_type_menus')
            ->where('mission_type_id', $id)->pluck('menu_id');
        if ($menuIds->isNotEmpty()) $this->db()->table('menus')->whereIn('id', $menuIds)->delete();
        $this->db()->table('mission_types')->where('id', $id)->delete();
        return response()->json(['success' => true, 'message' => "Type {$type->code} supprimé."]);
    }

    public function uploadLogo(Request $request, int $id)
    {
        $request->validate(['logo' => 'required|image|max:2048']);
        $type = $this->db()->table('mission_types')->find($id);
        if (!$type) return response()->json(['success' => false, 'error' => 'Introuvable.'], 404);
        if ($type->logo_path) Storage::disk('public')->delete($type->logo_path);
        $path = $request->file('logo')->store("mission-types/{$type->code}", 'public');
        $this->db()->table('mission_types')->where('id', $id)
            ->update(['logo_path' => $path, 'updated_at' => now()]);
        return response()->json(['success' => true, 'logo_url' => asset("storage/{$path}")]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // SYNC AUDIT TYPES (endpoints dédiés)
    // ────────────────────────────────────────────────────────────────────────
    public function syncAuditTypes(Request $request, int $id)
    {
        $request->validate(['audit_type_ids' => 'present|array', 'audit_type_ids.*' => 'integer']);
        $this->syncMTAuditTypes($id, $request->audit_type_ids ?? []);
        $c = count($request->audit_type_ids ?? []);
        return response()->json(['success' => true,
            'message' => $c ? "{$c} type(s) affectés." : 'Affectation effacée (tous les types).',
        ]);
    }

    public function syncFormAuditTypes(Request $request, int $typeId, int $formId)
    {
        $request->validate(['audit_type_ids' => 'present|array', 'audit_type_ids.*' => 'integer']);
        $this->syncFormAuditTypesArr($formId, $request->audit_type_ids ?? []);
        $c = count($request->audit_type_ids ?? []);
        return response()->json(['success' => true,
            'message' => $c ? "{$c} type(s) affectés à la phase." : 'Affectation effacée.',
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // PHASES N1
    // ────────────────────────────────────────────────────────────────────────
    public function storePhase(Request $request, int $typeId)
    {
        $data = $request->validate([
            'label'            => 'required|string|max:300',
            'description'      => 'nullable|string',
            'icon'             => 'nullable|string|max:100',
            'audit_type_ids'   => 'nullable|array',
            'audit_type_ids.*' => 'integer',
        ]);
        $type = $this->db()->table('mission_types')->find($typeId);
        if (!$type) return response()->json(['success' => false, 'error' => 'Type introuvable.'], 404);

        $auditTypeIds = $data['audit_type_ids'] ?? [];
        unset($data['audit_type_ids']);
        $code = $this->toSlug($data['label']);
        $url  = "/m/audit.core/missions/" . strtolower($type->code) . "/{$code}";
        $rn   = "audit." . strtolower($type->code) . ".{$code}";
        if ($this->db()->table('mission_type_forms')
            ->where('mission_type_id', $typeId)->where('code', $code)->exists()) {
            $code .= '-' . time(); $url = "/m/audit.core/missions/" . strtolower($type->code) . "/{$code}";
            $rn = "audit." . strtolower($type->code) . ".{$code}";
        }
        $maxSort = $this->db()->table('mission_type_forms')
            ->where('mission_type_id', $typeId)->where('level', 1)->max('sort_order') ?? 0;
        $formId = $this->db()->table('mission_type_forms')->insertGetId([
            'mission_type_id' => $typeId, 'parent_id' => null, 'code' => $code,
            'label' => $data['label'], 'description' => $data['description'] ?? null,
            'route_name' => $rn, 'url_path' => $url,
            'icon' => $data['icon'] ?? 'ti ti-file-description',
            'level' => 1, 'sort_order' => $maxSort + 1, 'is_active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        if ($auditTypeIds) $this->syncFormAuditTypesArr($formId, $auditTypeIds);
        $this->createPhaseMenu($typeId, $type, $formId, $code,
            $data['label'], $data['icon'] ?? 'ti ti-file-description', $url, $rn);
        return response()->json(['success' => true, 'form_id' => $formId, 'url' => $url,
            'message' => "Phase \"{$data['label']}\" créée."]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // SOUS-PHASES N2
    // ────────────────────────────────────────────────────────────────────────
    public function storeSubPhase(Request $request, int $typeId, int $parentId)
    {
        $data = $request->validate([
            'label'            => 'required|string|max:300',
            'description'      => 'nullable|string',
            'icon'             => 'nullable|string|max:100',
            'audit_type_ids'   => 'nullable|array',
            'audit_type_ids.*' => 'integer',
        ]);
        $type   = $this->db()->table('mission_types')->find($typeId);
        $parent = $this->db()->table('mission_type_forms')
            ->where('id', $parentId)->where('mission_type_id', $typeId)->first();
        if (!$type || !$parent)
            return response()->json(['success' => false, 'error' => 'Type ou phase introuvable.'], 404);

        $auditTypeIds = $data['audit_type_ids'] ?? [];
        unset($data['audit_type_ids']);
        $code = $this->toSlug($data['label']);
        $url  = "/m/audit.core/missions/" . strtolower($type->code) . "/{$code}";
        $rn   = "audit." . strtolower($type->code) . ".{$code}";
        if ($this->db()->table('mission_type_forms')
            ->where('mission_type_id', $typeId)->where('code', $code)->exists()) {
            $code .= '-' . time(); $url = "/m/audit.core/missions/" . strtolower($type->code) . "/{$code}";
            $rn = "audit." . strtolower($type->code) . ".{$code}";
        }
        $maxSort = $this->db()->table('mission_type_forms')
            ->where('parent_id', $parentId)->max('sort_order') ?? 0;
        $formId = $this->db()->table('mission_type_forms')->insertGetId([
            'mission_type_id' => $typeId, 'parent_id' => $parentId, 'code' => $code,
            'label' => $data['label'], 'description' => $data['description'] ?? null,
            'route_name' => $rn, 'url_path' => $url,
            'icon' => $data['icon'] ?? 'ti ti-file-description',
            'level' => 2, 'sort_order' => $maxSort + 1, 'is_active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        if ($auditTypeIds) $this->syncFormAuditTypesArr($formId, $auditTypeIds);
        $parentMenuId = $this->db()->table('mission_type_menus')
            ->where('mission_type_id', $typeId)->where('form_id', $parentId)->value('menu_id');
        $this->createSubPhaseMenu($typeId, $formId, $code,
            $data['label'], 'ti ti-file-description', $url, $rn, $parentMenuId);
        return response()->json(['success' => true, 'form_id' => $formId, 'url' => $url,
            'message' => "Sous-phase \"{$data['label']}\" créée."]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // UPDATE / DELETE PHASE
    // ────────────────────────────────────────────────────────────────────────
    public function updateForm(Request $request, int $typeId, int $formId)
    {
        $data = $request->validate([
            'label'            => 'required|string|max:300',
            'description'      => 'nullable|string',
            'icon'             => 'nullable|string|max:100',
            'is_active'        => 'boolean',
            'audit_type_ids'   => 'nullable|array',
            'audit_type_ids.*' => 'integer',
        ]);
        $auditTypeIds = array_key_exists('audit_type_ids', $data) ? $data['audit_type_ids'] : null;
        unset($data['audit_type_ids']);
        $data['updated_at'] = now();
        $this->db()->table('mission_type_forms')->where('id', $formId)->update($data);
        $mtm = $this->db()->table('mission_type_menus')
            ->where('mission_type_id', $typeId)->where('form_id', $formId)->first();
        if ($mtm) {
            $this->db()->table('menus')->where('id', $mtm->menu_id)
                ->update(['label' => $data['label'], 'icon' => $data['icon'] ?? 'ti ti-file-description']);
        }
        if ($auditTypeIds !== null) $this->syncFormAuditTypesArr($formId, $auditTypeIds);
        return response()->json(['success' => true, 'message' => 'Phase mise à jour.']);
    }

    public function destroyForm(int $typeId, int $formId)
    {
        $form = $this->db()->table('mission_type_forms')
            ->where('id', $formId)->where('mission_type_id', $typeId)->first();
        if (!$form) return response()->json(['success' => false, 'error' => 'Phase introuvable.'], 404);
        $menuIds = $this->db()->table('mission_type_menus')
            ->where('mission_type_id', $typeId)->where('form_id', $formId)->pluck('menu_id');
        if ($menuIds->isNotEmpty()) $this->db()->table('menus')->whereIn('id', $menuIds)->delete();
        $this->db()->table('mission_type_forms')->where('id', $formId)->delete();
        return response()->json(['success' => true, 'message' => 'Phase supprimée.']);
    }

    // ────────────────────────────────────────────────────────────────────────
    // API JSON
    // ────────────────────────────────────────────────────────────────────────
    public function apiList()
    {
        return response()->json(['success' => true, 'data' =>
            $this->db()->table('mission_types')->where('is_active', 1)->orderBy('sort_order')
                ->get(['id','code','label','short_label','color','icon','logo_path'])
        ]);
    }

    public function apiPhasesByType(int $typeId)
    {
        $phases = $this->db()->table('mission_type_forms')
            ->where('mission_type_id', $typeId)->where('level', 1)->where('is_active', 1)
            ->orderBy('sort_order')->get()
            ->map(function ($p) use ($typeId) {
                $p->sub_forms   = $this->db()->table('mission_type_forms')
                    ->where('mission_type_id', $typeId)->where('parent_id', $p->id)
                    ->where('is_active', 1)->orderBy('sort_order')->get();
                $p->audit_types = $this->loadFormAuditTypes($p->id);
                return $p;
            });
        return response()->json(['success' => true, 'data' => $phases]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // MENUS
    // ────────────────────────────────────────────────────────────────────────
    private function createTypeMenu(int $typeId, string $code, string $label, string $icon): void
    {
        $mid = $this->moduleId(); if (!$mid) return;
        $mkey = 'audit.core.missions.' . strtolower($code);
        if ($this->db()->table('menus')->where('key', $mkey)->exists()) return;
        $maxSort = $this->db()->table('menus')->where('module_id', $mid)->max('sort') ?? 0;
        $menuId  = $this->db()->table('menus')->insertGetId([
            'key' => $mkey, 'label' => $label, 'type' => 'title', 'icon' => $icon,
            'url' => null, 'route_name' => null, 'parent_id' => null,
            'module_id' => $mid, 'sort' => $maxSort + 10,
        ]);
        $this->db()->table('mission_type_menus')->insertOrIgnore([
            'mission_type_id' => $typeId, 'form_id' => null,
            'menu_id' => $menuId, 'menu_key' => $mkey,
        ]);
    }

    private function createPhaseMenu(int $typeId, $type, int $formId, string $code,
        string $label, string $icon, string $url, string $rn): void
    {
        $mid = $this->moduleId(); if (!$mid) return;
        $parentMenuId = $this->db()->table('mission_type_menus')
            ->where('mission_type_id', $typeId)->whereNull('form_id')->value('menu_id');
        if (!$parentMenuId) return;
        $maxSort = $this->db()->table('menus')->where('parent_id', $parentMenuId)->max('sort') ?? 0;
        $mkey    = 'audit.core.missions.' . strtolower($type->code) . '.' . $code;
        $menuId  = $this->db()->table('menus')->insertGetId([
            'key' => $mkey, 'label' => $label, 'type' => 'item', 'icon' => $icon,
            'url' => $url, 'route_name' => $rn,
            'parent_id' => $parentMenuId, 'module_id' => $mid, 'sort' => $maxSort + 1,
        ]);
        $this->db()->table('mission_type_menus')->insertOrIgnore([
            'mission_type_id' => $typeId, 'form_id' => $formId,
            'menu_id' => $menuId, 'menu_key' => $mkey,
        ]);
    }

    private function createSubPhaseMenu(int $typeId, int $formId, string $code,
        string $label, string $icon, string $url, string $rn, ?int $parentMenuId): void
    {
        $mid = $this->moduleId(); if (!$mid || !$parentMenuId) return;
        $maxSort = $this->db()->table('menus')->where('parent_id', $parentMenuId)->max('sort') ?? 0;
        $mkey    = 'audit.core.missions.sub.' . $code . '.' . $formId;
        $menuId  = $this->db()->table('menus')->insertGetId([
            'key' => $mkey, 'label' => $label, 'type' => 'item', 'icon' => $icon,
            'url' => $url, 'route_name' => $rn,
            'parent_id' => $parentMenuId, 'module_id' => $mid, 'sort' => $maxSort + 1,
        ]);
        $this->db()->table('mission_type_menus')->insertOrIgnore([
            'mission_type_id' => $typeId, 'form_id' => $formId,
            'menu_id' => $menuId, 'menu_key' => $mkey,
        ]);
    }
}