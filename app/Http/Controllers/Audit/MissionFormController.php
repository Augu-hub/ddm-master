<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MissionFormController extends Controller
{
    // ── Mapping audit_type_code → table ──────────────────────────
    private const TABLE_MAP = [
        'ac' => 'audit_forms_ac',
        'af' => 'audit_forms_af',
        'ap' => 'audit_forms_ap',
        'am' => 'audit_forms_am',
        'rp' => 'audit_forms_rp',
        'es' => 'audit_forms_es',
    ];

    private function db() { return DB::connection(); }

    private function table(string $typeCode): string
    {
        $code = strtolower($typeCode);
        if (!isset(self::TABLE_MAP[$code])) {
            abort(404, "Type d'audit inconnu : {$typeCode}");
        }
        return self::TABLE_MAP[$code];
    }

    private function toSlug(string $label): string
    {
        $s = mb_strtolower(trim($label));
        $map = ['à'=>'a','â'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','î'=>'i','ï'=>'i',
                'ô'=>'o','ù'=>'u','û'=>'u','ç'=>'c','œ'=>'oe',"'"=>'-',"\u{2019}"=>'-'];
        $s = strtr($s, $map);
        $s = preg_replace('/[^a-z0-9\s-]/', '', $s);
        $s = preg_replace('/[\s-]+/', '-', trim($s));
        return substr($s, 0, 120);
    }

    // ─────────────────────────────────────────────────────────────
    // PAGE PRINCIPALE : vue organisée par audit_type
    // GET /param/mission-forms
    // ─────────────────────────────────────────────────────────────
    public function index(): InertiaResponse
    {
        try {
            Log::info('[MissionForms] index()');

            $missionTypes = $this->db()->table('mission_types')
                ->where('is_active', 1)->orderBy('sort_order')
                ->get(['id','code','label','short_label','color','icon']);

            $auditTypes = $this->db()->table('audit_types')
                ->where('is_active', 1)->orderBy('sort_order')
                ->get(['id','code','label','color','icon']);

            // Pour chaque audit_type, charger ses forms groupés par phase
            $formsData = [];
            foreach ($auditTypes as $at) {
                $table = $this->table($at->code);
                $allForms = $this->db()->table($table . ' as f')
                    ->join('mission_types as mt', 'mt.id', '=', 'f.mission_type_id')
                    ->orderBy('mt.sort_order')
                    ->orderBy('f.sort_order')
                    ->get([
                        'f.id', 'f.mission_type_id', 'f.parent_id', 'f.code',
                        'f.label', 'f.description', 'f.route_name', 'f.url_path',
                        'f.icon', 'f.sort_order', 'f.is_active',
                        'mt.code as phase_code', 'mt.label as phase_label',
                        'mt.color as phase_color', 'mt.icon as phase_icon',
                    ]);

                // Grouper par mission_type (phase)
                $byPhase = [];
                foreach ($missionTypes as $mt) {
                    $forms = $allForms->where('mission_type_id', $mt->id)->values();
                    // Construire arbre N1/N2
                    $roots = $forms->whereNull('parent_id')->values();
                    $roots = $roots->map(function ($f) use ($forms) {
                        $f->children = $forms->where('parent_id', $f->id)->values();
                        return $f;
                    });
                    $byPhase[] = [
                        'mission_type'  => $mt,
                        'forms'         => $roots,
                        'forms_count'   => $forms->count(),
                    ];
                }

                $formsData[] = [
                    'audit_type'  => $at,
                    'by_phase'    => $byPhase,
                    'total_forms' => $allForms->count(),
                ];
            }

            $stats = [
                'total_audit_types' => count($auditTypes),
                'total_phases'      => count($missionTypes),
                'total_forms'       => array_sum(array_column($formsData, 'total_forms')),
            ];

            return Inertia::render('dashboards/Audit/Param/MissionForms/index', [
                'formsData'    => $formsData,
                'missionTypes' => $missionTypes,
                'auditTypes'   => $auditTypes,
                'stats'        => $stats,
            ]);

        } catch (\Throwable $e) {
            Log::error('[MissionForms] CRASH index()', [
                'error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()
            ]);
            throw $e;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // STORE : créer un form dans la table du type d'audit choisi
    // POST /param/mission-forms/{auditTypeCode}
    // Body : mission_type_id, label, description?, icon?, parent_id?
    // ─────────────────────────────────────────────────────────────
    public function store(Request $request, string $auditTypeCode)
    {
        $table = $this->table($auditTypeCode);
        $atCode = strtolower($auditTypeCode);

        $data = $request->validate([
            'mission_type_id' => 'required|integer|exists:mission_types,id',
            'label'           => 'required|string|max:300',
            'description'     => 'nullable|string',
            'icon'            => 'nullable|string|max:100',
            'parent_id'       => 'nullable|integer',
        ]);

        $missionType = $this->db()->table('mission_types')->find($data['mission_type_id']);
        if (!$missionType) {
            return response()->json(['success' => false, 'error' => 'Phase introuvable.'], 404);
        }

        // Générer code/url automatiquement
        $code = $this->toSlug($data['label']);
        $phaseCode = strtolower($missionType->code);

        // Anti-collision sur le code
        $base = $code;
        $i = 1;
        while ($this->db()->table($table)
            ->where('mission_type_id', $data['mission_type_id'])
            ->where('code', $code)->exists()) {
            $code = $base . '-' . $i++;
        }

        $urlPath   = "/m/audit.core/{$atCode}/{$phaseCode}/{$code}";
        $routeName = "audit.{$atCode}.{$phaseCode}.{$code}";

        $maxSort = $this->db()->table($table)
            ->where('mission_type_id', $data['mission_type_id'])
            ->when($data['parent_id'] ?? null, fn($q, $p) => $q->where('parent_id', $p),
                fn($q) => $q->whereNull('parent_id'))
            ->max('sort_order') ?? 0;

        $formId = $this->db()->table($table)->insertGetId([
            'mission_type_id' => $data['mission_type_id'],
            'parent_id'       => $data['parent_id'] ?? null,
            'code'            => $code,
            'label'           => $data['label'],
            'description'     => $data['description'] ?? null,
            'route_name'      => $routeName,
            'url_path'        => $urlPath,
            'icon'            => $data['icon'] ?? 'ti ti-file-description',
            'sort_order'      => $maxSort + 1,
            'is_active'       => 1,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return response()->json([
            'success'    => true,
            'form_id'    => $formId,
            'code'       => $code,
            'url_path'   => $urlPath,
            'route_name' => $routeName,
            'message'    => "Form \"{$data['label']}\" créé dans {$atCode}/{$phaseCode}.",
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // UPDATE
    // PUT /param/mission-forms/{auditTypeCode}/{id}
    // ─────────────────────────────────────────────────────────────
    public function update(Request $request, string $auditTypeCode, int $id)
    {
        $table = $this->table($auditTypeCode);

        $data = $request->validate([
            'label'       => 'required|string|max:300',
            'description' => 'nullable|string',
            'icon'        => 'nullable|string|max:100',
            'is_active'   => 'boolean',
            'sort_order'  => 'nullable|integer',
        ]);

        $form = $this->db()->table($table)->find($id);
        if (!$form) {
            return response()->json(['success' => false, 'error' => 'Form introuvable.'], 404);
        }

        // Si label changé → régénérer url/route
        if ($data['label'] !== $form->label) {
            $missionType = $this->db()->table('mission_types')->find($form->mission_type_id);
            $atCode = strtolower($auditTypeCode);
            $phaseCode = strtolower($missionType->code);
            $newCode = $this->toSlug($data['label']);
            $base = $newCode; $i = 1;
            while ($this->db()->table($table)
                ->where('mission_type_id', $form->mission_type_id)
                ->where('code', $newCode)->where('id', '!=', $id)->exists()) {
                $newCode = $base . '-' . $i++;
            }
            $data['code']       = $newCode;
            $data['url_path']   = "/m/audit.core/{$atCode}/{$phaseCode}/{$newCode}";
            $data['route_name'] = "audit.{$atCode}.{$phaseCode}.{$newCode}";
        }

        $data['updated_at'] = now();
        $this->db()->table($table)->where('id', $id)->update($data);

        return response()->json([
            'success'  => true,
            'url_path' => $data['url_path'] ?? $form->url_path,
            'message'  => 'Form mis à jour.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // DESTROY
    // DELETE /param/mission-forms/{auditTypeCode}/{id}
    // ─────────────────────────────────────────────────────────────
    public function destroy(string $auditTypeCode, int $id)
    {
        $table = $this->table($auditTypeCode);
        $form  = $this->db()->table($table)->find($id);
        if (!$form) {
            return response()->json(['success' => false, 'error' => 'Form introuvable.'], 404);
        }

        // Supprimer les enfants si N1
        $this->db()->table($table)->where('parent_id', $id)->delete();
        $this->db()->table($table)->where('id', $id)->delete();

        return response()->json(['success' => true, 'message' => 'Form supprimé.']);
    }

    // ─────────────────────────────────────────────────────────────
    // API : lister les forms d'un audit_type par phase
    // GET /api/mission-forms/{auditTypeCode}/{missionTypeId}
    // ─────────────────────────────────────────────────────────────
    public function apiForms(string $auditTypeCode, int $missionTypeId)
    {
        $table  = $this->table($auditTypeCode);
        $forms  = $this->db()->table($table)
            ->where('mission_type_id', $missionTypeId)
            ->where('is_active', 1)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($f) use ($table) {
                $f->children = $this->db()->table($table)
                    ->where('parent_id', $f->id)
                    ->where('is_active', 1)
                    ->orderBy('sort_order')->get();
                return $f;
            });

        return response()->json(['success' => true, 'data' => $forms]);
    }

    // ─────────────────────────────────────────────────────────────
    // API : lister tous les forms d'un audit_type (toutes phases)
    // GET /api/mission-forms/{auditTypeCode}
    // ─────────────────────────────────────────────────────────────
    public function apiAllForms(string $auditTypeCode)
    {
        $table = $this->table($auditTypeCode);
        $forms = $this->db()->table($table . ' as f')
            ->join('mission_types as mt', 'mt.id', '=', 'f.mission_type_id')
            ->where('f.is_active', 1)
            ->orderBy('mt.sort_order')->orderBy('f.sort_order')
            ->get([
                'f.*',
                'mt.code as phase_code',
                'mt.label as phase_label',
                'mt.color as phase_color',
            ]);

        return response()->json(['success' => true, 'data' => $forms]);
    }
}