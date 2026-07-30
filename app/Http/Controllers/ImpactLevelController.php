<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImpactLevelRequest;
use App\Http\Requests\UpdateImpactLevelRequest;
use App\Models\RiskAppetiteLevel;
use App\Models\RiskImpactLevel;
use App\Models\RiskMatrixConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ImpactLevelController extends Controller
{
    // ─── Index ─────────────────────────────────────────────────────────────────

    public function index(Request $request): Response
    {
        $tenantId = (int)(session('tenant_id') ?? 1);

        $matrixConfigs = RiskMatrixConfig::forTenant($tenantId)
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get()
            ->map(fn($c) => [
                'id'           => $c->id,
                'name'         => $c->name,
                'matrix_size'  => $c->matrix_size,
                'matrix_label' => $c->matrix_label,
                'is_active'    => $c->is_active,
            ]);

        $selectedConfigId = $request->integer('config_id')
            ?: optional($matrixConfigs->firstWhere('is_active', true))['id']
                ?: optional($matrixConfigs->first())['id'];

        // ── Niveaux d'impact ──────────────────────────────────────────────────
        if ($selectedConfigId) {

            $levels = RiskImpactLevel::forTenant($tenantId)
                ->forConfig($selectedConfigId)
                ->ordered()
                ->get();

            // Critères structurés via DB::table (avec template_id)
            $criteriaRows = DB::table('risk_impact_criteria')
                ->where('tenant_id', $tenantId)
                ->whereIn('impact_level_id', $levels->pluck('id'))
                ->whereNull('deleted_at')
                ->orderBy('sort_order')
                ->get();

            $criteriaByLevel = $criteriaRows->groupBy('impact_level_id');

            $impactLevels = $levels->map(function ($l) use ($criteriaByLevel) {
                $structuredCriteria = ($criteriaByLevel->get($l->id) ?? collect())
                    ->map(fn($c) => [
                        'id'                   => $c->id,
                        'template_id'          => $c->template_id          ?? null,
                        'designation'          => $c->designation,
                        'description'          => $c->description,
                        'sort_order'           => $c->sort_order,
                        'appetite_id'          => $c->appetite_id          ?? null,
                        'appetite_description' => $c->appetite_description ?? null,
                    ])->values()->all();

                return [
                    'id'          => $l->id,
                    'label'       => $l->label,
                    'score'       => $l->score,
                    'description' => $l->description,
                    'color_code'  => $l->color_code,
                    'sort_order'  => $l->sort_order,
                    'criteria'    => $structuredCriteria,
                ];
            });

        } else {
            $impactLevels = collect();
        }

        // ── Templates de critères ─────────────────────────────────────────────
        $criteriaTemplates = $selectedConfigId
            ? DB::table('risk_impact_criteria_templates')
                ->where('tenant_id', $tenantId)
                ->where('matrix_config_id', $selectedConfigId)
                ->whereNull('deleted_at')
                ->orderBy('sort_order')
                ->get()
                ->map(fn($t) => [
                    'id'                   => $t->id,
                    'designation'          => $t->designation,
                    'hint'                 => $t->hint,
                    'sort_order'           => $t->sort_order,
                    'appetite_id'          => $t->appetite_id          ?? null,
                    'appetite_description' => $t->appetite_description ?? null,
                ])
            : collect();

        // ── Appétences actives ────────────────────────────────────────────────
        $appetites = RiskAppetiteLevel::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'label', 'color', 'description', 'score_min', 'score_max'])
            ->toArray();

        return Inertia::render('dashboards/Risk/Matrix/Impactlevel', [
            'matrixConfigs'     => $matrixConfigs,
            'selectedConfigId'  => $selectedConfigId,
            'impactLevels'      => $impactLevels,
            'criteriaTemplates' => $criteriaTemplates,
            'appetites'         => $appetites,
        ]);
    }

    // ─── Niveaux CRUD ──────────────────────────────────────────────────────────

    public function store(StoreImpactLevelRequest $request): RedirectResponse
    {
        $tenantId = (int)(session('tenant_id') ?? 1);
        $config   = RiskMatrixConfig::forTenant($tenantId)->findOrFail($request->integer('matrix_config_id'));

        $existingCount = RiskImpactLevel::forConfig($config->id)->count();
        if ($existingCount >= $config->matrix_size) {
            return back()->withErrors([
                'score' => "Cette matrice {$config->matrix_label} ne peut contenir que {$config->matrix_size} niveaux d'impact.",
            ]);
        }

        $level = RiskImpactLevel::create([...$request->validated(), 'tenant_id' => $tenantId]);

        // Créer une instance vide pour chaque template existant
        $this->syncCriteriaFromTemplates($level, $tenantId);

        return back()->with('success', "Niveau d'impact « {$request->label} » créé avec succès.");
    }

    public function update(UpdateImpactLevelRequest $request, int $impact_level): RedirectResponse
    {
        $tenantId     = (int)(session('tenant_id') ?? 1);
        $impact_level = $this->findLevelForTenant($impact_level, $tenantId);
        $impact_level->update($request->validated());
        return back()->with('success', "Niveau d'impact « {$impact_level->label} » mis à jour.");
    }

    public function destroy(Request $request, int $impact_level): RedirectResponse
    {
        $tenantId     = (int)(session('tenant_id') ?? 1);
        $impact_level = $this->findLevelForTenant($impact_level, $tenantId);
        $label        = $impact_level->label;
        $impact_level->delete();
        return back()->with('success', "Niveau d'impact « {$label} » supprimé.");
    }

    public function reorder(Request $request): RedirectResponse
    {
        $tenantId = (int)(session('tenant_id') ?? 1);
        $request->validate([
            'items'              => ['required', 'array'],
            'items.*.id'         => ['required', 'integer'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);
        foreach ($request->input('items') as $item) {
            RiskImpactLevel::forTenant($tenantId)
                ->where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }
        return back()->with('success', 'Ordre mis à jour.');
    }

    // ─── Templates de critères ─────────────────────────────────────────────────

    /**
     * Créer un template de critère — sans appétence (assignée après).
     */
    public function storeTemplate(Request $request): RedirectResponse
    {
        $tenantId  = (int)(session('tenant_id') ?? 1);
        $validated = $request->validate([
            'matrix_config_id' => ['required', 'integer'],
            'designation'      => ['required', 'string', 'max:200'],
            'hint'             => ['nullable', 'string', 'max:500'],
            'sort_order'       => ['nullable', 'integer', 'min:0'],
        ]);

        RiskMatrixConfig::forTenant($tenantId)->findOrFail($validated['matrix_config_id']);

        $sortOrder = $validated['sort_order']
            ?? DB::table('risk_impact_criteria_templates')
                ->where('matrix_config_id', $validated['matrix_config_id'])
                ->whereNull('deleted_at')
                ->count();

        $templateId = DB::table('risk_impact_criteria_templates')->insertGetId([
            'tenant_id'        => $tenantId,
            'matrix_config_id' => $validated['matrix_config_id'],
            'designation'      => $validated['designation'],
            'hint'             => $validated['hint'] ?? null,
            'appetite_id'      => null,
            'appetite_description' => null,
            'sort_order'       => $sortOrder,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // Créer une instance vide dans chaque niveau existant
        $levels = RiskImpactLevel::forTenant($tenantId)
            ->forConfig($validated['matrix_config_id'])
            ->get();

        foreach ($levels as $level) {
            DB::table('risk_impact_criteria')->insert([
                'tenant_id'       => $tenantId,
                'impact_level_id' => $level->id,
                'template_id'     => $templateId,
                'designation'     => $validated['designation'],
                'description'     => null,
                'appetite_id'     => null,
                'appetite_description' => null,
                'sort_order'      => $sortOrder,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        return back()->with('success', "Critère « {$validated['designation']} » ajouté à tous les niveaux.");
    }

    /**
     * Modifier désignation + hint d'un template — ne touche pas à l'appétence.
     */
    public function updateTemplate(Request $request, int $template): RedirectResponse
    {
        $tenantId  = (int)(session('tenant_id') ?? 1);
        $tpl       = $this->findTemplateForTenant($template, $tenantId);

        $validated = $request->validate([
            'designation' => ['required', 'string', 'max:200'],
            'hint'        => ['nullable', 'string', 'max:500'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ]);

        DB::table('risk_impact_criteria_templates')
            ->where('id', $template)
            ->where('tenant_id', $tenantId)
            ->update([
                'designation' => $validated['designation'],
                'hint'        => $validated['hint'] ?? null,
                'sort_order'  => $validated['sort_order'] ?? $tpl->sort_order,
                'updated_at'  => now(),
            ]);

        // Propager la nouvelle désignation sur toutes les instances
        DB::table('risk_impact_criteria')
            ->where('template_id', $template)
            ->update(['designation' => $validated['designation'], 'updated_at' => now()]);

        return back()->with('success', "Critère « {$validated['designation']} » mis à jour.");
    }

    public function destroyTemplate(int $template): RedirectResponse
    {
        $tenantId = (int)(session('tenant_id') ?? 1);
        $tpl      = $this->findTemplateForTenant($template, $tenantId);

        // Soft-delete des instances
        DB::table('risk_impact_criteria')
            ->where('template_id', $template)
            ->update(['deleted_at' => now(), 'updated_at' => now()]);

        // Soft-delete du template
        DB::table('risk_impact_criteria_templates')
            ->where('id', $template)
            ->where('tenant_id', $tenantId)
            ->update(['deleted_at' => now(), 'updated_at' => now()]);

        return back()->with('success', "Critère « {$tpl->designation} » supprimé de tous les niveaux.");
    }

    public function reorderTemplates(Request $request): RedirectResponse
    {
        $tenantId = (int)(session('tenant_id') ?? 1);
        $request->validate([
            'items'              => ['required', 'array'],
            'items.*.id'         => ['required', 'integer'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);
        foreach ($request->input('items') as $item) {
            DB::table('risk_impact_criteria_templates')
                ->where('id', $item['id'])->where('tenant_id', $tenantId)
                ->update(['sort_order' => $item['sort_order'], 'updated_at' => now()]);
            DB::table('risk_impact_criteria')
                ->where('template_id', $item['id'])
                ->update(['sort_order' => $item['sort_order'], 'updated_at' => now()]);
        }
        return back()->with('success', 'Ordre des critères mis à jour.');
    }

    /**
     * Assigner (ou retirer) une appétence à un template existant.
     * Route  : POST /impact/templates/{template}/appetite
     * Body   : { appetite_id: int|null }
     * Retour : JSON — appelé en fetch depuis la Vue
     */
    public function assignTemplateAppetite(Request $request, int $template): JsonResponse
    {
        $tenantId = (int)(session('tenant_id') ?? 1);
        $this->findTemplateForTenant($template, $tenantId);

        $validated = $request->validate([
            'appetite_id' => ['nullable', 'integer', 'exists:risk_appetite_levels,id'],
        ]);

        $appetiteId          = $validated['appetite_id'] ?? null;
        $appetiteDescription = null;
        $appetite            = null;

        if ($appetiteId !== null) {
            $appetite = RiskAppetiteLevel::where('id', $appetiteId)
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->first();

            if (!$appetite) {
                return response()->json(['message' => 'Appétence invalide ou inactive.'], 422);
            }
            $appetiteDescription = $appetite->description;
        }

        DB::table('risk_impact_criteria_templates')
            ->where('id', $template)->where('tenant_id', $tenantId)
            ->update([
                'appetite_id'          => $appetiteId,
                'appetite_description' => $appetiteDescription,
                'updated_at'           => now(),
            ]);

        return response()->json([
            'template_id'          => $template,
            'appetite_id'          => $appetiteId,
            'appetite_description' => $appetiteDescription,
            'appetite'             => $appetite ? [
                'id'          => $appetite->id,
                'label'       => $appetite->label,
                'color'       => $appetite->color,
                'description' => $appetite->description,
                'score_min'   => $appetite->score_min,
                'score_max'   => $appetite->score_max,
            ] : null,
            'message' => $appetiteId
                ? "Appétence « {$appetite->label} » assignée au critère."
                : "Appétence retirée.",
        ]);
    }

    // ─── Critère instance — update description (cellule matrice) ───────────────

    public function updateCriterion(Request $request, int $impact_level, int $criterion): RedirectResponse
    {
        $tenantId = (int)(session('tenant_id') ?? 1);
        $this->findLevelForTenant($impact_level, $tenantId);

        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::table('risk_impact_criteria')
            ->where('id', $criterion)
            ->where('impact_level_id', $impact_level)
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->update(['description' => $validated['description'], 'updated_at' => now()]);

        return back()->with('success', 'Contenu mis à jour.');
    }

    // ─── IA — Contenu critères (matrice template × niveaux) ────────────────────

    public function suggestCriteriaContent(Request $request)
    {
        $tenantId = (int)(session('tenant_id') ?? 1);

        $request->validate([
            'sector'           => ['required', 'string', 'min:3'],
            'context'          => ['nullable', 'string'],
            'matrix_config_id' => ['required', 'integer'],
        ]);

        $configId = $request->integer('matrix_config_id');
        RiskMatrixConfig::forTenant($tenantId)->findOrFail($configId);

        $levels = RiskImpactLevel::forTenant($tenantId)
            ->forConfig($configId)
            ->ordered()
            ->get(['id', 'label', 'score', 'description']);

        $templates = DB::table('risk_impact_criteria_templates')
            ->where('tenant_id', $tenantId)
            ->where('matrix_config_id', $configId)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->get();

        if ($levels->isEmpty() || $templates->isEmpty()) {
            return response()->json([
                'message' => 'Veuillez d\'abord définir les niveaux d\'impact et les critères templates.',
            ], 422);
        }

        // Appétences pour enrichir le prompt
        $appetiteIds = $templates->pluck('appetite_id')->filter()->unique()->values();
        $appetiteMap = [];
        if ($appetiteIds->isNotEmpty()) {
            $appetiteMap = RiskAppetiteLevel::whereIn('id', $appetiteIds)
                ->get(['id', 'label', 'score_min', 'score_max', 'description'])
                ->keyBy('id')->toArray();
        }

        $sector  = $request->input('sector');
        $context = $request->input('context', '');

        $levelsDesc = $levels->map(fn($l) =>
            "- Niveau {$l->score} « {$l->label} »"
            . ($l->description ? " : {$l->description}" : '')
        )->implode("\n");

        $criteriaDesc = $templates->map(function ($t, $i) use ($appetiteMap) {
            $line = ($i + 1) . ". « {$t->designation} »";
            if ($t->appetite_id && isset($appetiteMap[$t->appetite_id])) {
                $apt  = $appetiteMap[$t->appetite_id];
                $line .= " — Appétence : {$apt['label']} (score {$apt['score_min']}–{$apt['score_max']})";
                if (!empty($apt['description'])) $line .= " : {$apt['description']}";
            }
            if ($t->hint) $line .= " — Indice : {$t->hint}";
            return $line;
        })->implode("\n");

        $levelIds    = $levels->pluck('id')->implode(', ');
        $templateIds = $templates->pluck('id')->implode(', ');

        $prompt = "Tu es un expert en gestion des risques dans le secteur : {$sector}.\n"
            . ($context ? "{$context}\n\n" : "\n")
            . "Voici les niveaux d'impact de la matrice de risques :\n{$levelsDesc}\n\n"
            . "Voici les critères d'évaluation à renseigner pour chaque niveau :\n{$criteriaDesc}\n\n"
            . "Pour CHAQUE niveau et CHAQUE critère, génère une description précise, observable et mesurable.\n"
            . "IMPORTANT : pour chaque critère qui possède une appétence définie, la description doit\n"
            . "être cohérente avec cette tolérance au risque.\n\n"
            . "Réponds UNIQUEMENT en JSON valide, sans balises markdown, sans commentaires.\n"
            . "Format attendu :\n"
            . "{ \"suggestions\": { \"[level_id]\": { \"[template_id]\": \"description\" } }, \"sector\": \"secteur utilisé\" }\n\n"
            . "Les level_id sont : {$levelIds}\n"
            . "Les template_id sont : {$templateIds}";

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.mistral.api_key'),
                'Content-Type'  => 'application/json',
            ])->post('https://api.mistral.ai/v1/chat/completions', [
                'model'       => config('services.mistral.model', 'mistral-small-latest'),
                'temperature' => 0.4,
                'messages'    => [['role' => 'user', 'content' => $prompt]],
            ]);

            if (!$response->successful()) {
                return response()->json(['message' => 'Erreur Mistral : ' . $response->status()], 502);
            }

            $content = preg_replace('/```json\s*|\s*```/', '', trim(
                $response->json('choices.0.message.content', '')
            ));
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE || !isset($data['suggestions'])) {
                return response()->json(['message' => 'Réponse IA invalide, veuillez réessayer.'], 502);
            }

            return response()->json([
                'suggestions' => $data['suggestions'],
                'sector'      => $data['sector'] ?? $sector,
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la communication avec l\'IA.'], 502);
        }
    }

    public function applyCriteriaContent(Request $request): RedirectResponse
    {
        $tenantId = (int)(session('tenant_id') ?? 1);

        $request->validate([
            'matrix_config_id' => ['required', 'integer'],
            'suggestions'      => ['required', 'array'],
        ]);

        $configId = $request->integer('matrix_config_id');
        RiskMatrixConfig::forTenant($tenantId)->findOrFail($configId);

        $levelIds = RiskImpactLevel::forTenant($tenantId)
            ->forConfig($configId)
            ->pluck('id');

        foreach ($request->input('suggestions') as $levelId => $templateMap) {
            if (!$levelIds->contains((int)$levelId)) continue;
            foreach ($templateMap as $templateId => $description) {
                DB::table('risk_impact_criteria')
                    ->where('impact_level_id', (int)$levelId)
                    ->where('template_id', (int)$templateId)
                    ->whereNull('deleted_at')
                    ->update(['description' => $description, 'updated_at' => now()]);
            }
        }

        return back()->with('success', 'Contenu des critères appliqué avec succès.');
    }

    // ─── IA — Suggestions niveaux ──────────────────────────────────────────────

    public function suggestLevels(Request $request)
    {
        $request->validate([
            'sector'      => ['required', 'string', 'min:3'],
            'context'     => ['nullable', 'string'],
            'matrix_size' => ['required', 'integer', 'min:2', 'max:10'],
        ]);

        $sector     = $request->input('sector');
        $context    = $request->input('context', '');
        $matrixSize = $request->integer('matrix_size');

        $prompt = "Tu es un expert en gestion des risques dans le secteur : {$sector}.\n"
            . ($context ? "{$context}\n\n" : "\n")
            . "Génère exactement {$matrixSize} niveaux d'impact pour une matrice {$matrixSize}×{$matrixSize}, "
            . "adaptés au secteur {$sector}. Scores de 1 (le moins grave) à {$matrixSize} (le plus grave).\n\n"
            . "Pour chaque niveau : \"label\" (max 30 car.), \"score\" (1-{$matrixSize}), \"description\" (1-2 phrases), \"color_code\" (HEX vert→rouge).\n\n"
            . "Réponds UNIQUEMENT en JSON valide, sans balises markdown.\n"
            . "Format : { \"suggestions\": [{\"label\":\"...\",\"score\":1,\"description\":\"...\",\"color_code\":\"#22c55e\"}], \"sector\": \"secteur utilisé\" }";

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.mistral.api_key'),
                'Content-Type'  => 'application/json',
            ])->post('https://api.mistral.ai/v1/chat/completions', [
                'model'       => config('services.mistral.model', 'mistral-small-latest'),
                'temperature' => 0.4,
                'messages'    => [['role' => 'user', 'content' => $prompt]],
            ]);

            if (!$response->successful()) {
                return response()->json(['message' => 'Erreur Mistral : ' . $response->status()], 502);
            }

            $content = preg_replace('/```json\s*|\s*```/', '', trim(
                $response->json('choices.0.message.content', '')
            ));
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE || !isset($data['suggestions'])) {
                return response()->json(['message' => 'Réponse IA invalide, veuillez réessayer.'], 502);
            }

            return response()->json([
                'suggestions' => $data['suggestions'],
                'sector'      => $data['sector'] ?? $sector,
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la communication avec l\'IA.'], 502);
        }
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    private function syncCriteriaFromTemplates(RiskImpactLevel $level, int $tenantId): void
    {
        $templates = DB::table('risk_impact_criteria_templates')
            ->where('tenant_id', $tenantId)
            ->where('matrix_config_id', $level->matrix_config_id)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->get();

        foreach ($templates as $template) {
            DB::table('risk_impact_criteria')->insert([
                'tenant_id'            => $tenantId,
                'impact_level_id'      => $level->id,
                'template_id'          => $template->id,
                'designation'          => $template->designation,
                'description'          => null,
                'appetite_id'          => null,
                'appetite_description' => null,
                'sort_order'           => $template->sort_order,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        }
    }

    private function findLevelForTenant(int $id, int $tenantId): RiskImpactLevel
    {
        $level = RiskImpactLevel::forTenant($tenantId)->findOrFail($id);
        abort_if($level->tenant_id !== $tenantId, 403, 'Accès non autorisé.');
        return $level;
    }

    private function findTemplateForTenant(int $id, int $tenantId): object
    {
        $tpl = DB::table('risk_impact_criteria_templates')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->first();
        abort_if(!$tpl, 404, 'Template introuvable.');
        return $tpl;
    }
}