<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFrequencyLevelRequest;
use App\Http\Requests\UpdateFrequencyLevelRequest;
use App\Models\RiskFrequencyCriterion;
use App\Models\RiskFrequencyCriteriaTemplate;
use App\Models\RiskFrequencyLevel;
use App\Models\RiskMatrixConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FrequencyLevelController extends Controller
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

        // ── Niveaux de fréquence ───────────────────────────────────────────────
        // La colonne `criteria` de risk_frequency_levels est un champ JSON natif
        // (pas une relation Eloquent vers risk_frequency_criteria).
        // Les critères structurés (par template) sont dans risk_frequency_criteria.
        // On charge les deux séparément et on les fusionne.

        if ($selectedConfigId) {

            // 1. Niveaux de base (sans eager-load de relation criteria)
            $levels = RiskFrequencyLevel::forTenant($tenantId)
                ->forConfig($selectedConfigId)
                ->ordered()
                ->get();

            // 2. Critères structurés (table risk_frequency_criteria) groupés par level_id
            $criteriaRows = RiskFrequencyCriterion::where('tenant_id', $tenantId)
                ->whereIn('frequency_level_id', $levels->pluck('id'))
                ->whereNull('deleted_at')
                ->orderBy('sort_order')
                ->get();

            $criteriaByLevel = $criteriaRows->groupBy('frequency_level_id');

            $frequencyLevels = $levels->map(function ($l) use ($criteriaByLevel) {
                // Critères structurés pour ce niveau
                $structuredCriteria = ($criteriaByLevel->get($l->id) ?? collect())
                    ->map(fn($c) => [
                        'id'          => $c->id,
                        'template_id' => $c->template_id,
                        'designation' => $c->designation,
                        'description' => $c->description,
                        'sort_order'  => $c->sort_order,
                    ])->values()->all();

                // Critères JSON natifs de la colonne `criteria` (legacy)
                $jsonCriteria = [];
                if (!empty($l->criteria)) {
                    $decoded = is_array($l->criteria)
                        ? $l->criteria
                        : json_decode($l->criteria, true);
                    if (is_array($decoded)) {
                        $jsonCriteria = $decoded;
                    }
                }

                return [
                    'id'              => $l->id,
                    'label'           => $l->label,
                    'score'           => $l->score,
                    'description'     => $l->description,
                    'recurrence'      => $l->recurrence,
                    'full_label'      => $l->full_label   ?? $l->label,
                    'color_code'      => $l->color_code,
                    'sort_order'      => $l->sort_order,
                    'criteria'        => $structuredCriteria,   // critères structurés (templates)
                    'criteria_json'   => $jsonCriteria,         // critères JSON natifs (legacy)
                ];
            });

        } else {
            $frequencyLevels = collect();
        }

        // ── Templates de critères pour cette config ────────────────────────────
        $criteriaTemplates = $selectedConfigId
            ? RiskFrequencyCriteriaTemplate::where('tenant_id', $tenantId)
                ->where('matrix_config_id', $selectedConfigId)
                ->whereNull('deleted_at')
                ->orderBy('sort_order')
                ->get()
                ->map(fn($t) => [
                    'id'          => $t->id,
                    'designation' => $t->designation,
                    'hint'        => $t->hint,
                    'sort_order'  => $t->sort_order,
                ])
            : collect();

        return Inertia::render('dashboards/Risk/Matrix/Frequencylevel', [
            'matrixConfigs'     => $matrixConfigs,
            'selectedConfigId'  => $selectedConfigId,
            'frequencyLevels'   => $frequencyLevels,
            'criteriaTemplates' => $criteriaTemplates,
        ]);
    }

    // ─── Niveaux CRUD ──────────────────────────────────────────────────────────

    public function store(StoreFrequencyLevelRequest $request): RedirectResponse
    {
        $tenantId = (int)(session('tenant_id') ?? 1);

        $config = RiskMatrixConfig::forTenant($tenantId)
            ->findOrFail($request->integer('matrix_config_id'));

        $existingCount = RiskFrequencyLevel::forConfig($config->id)->count();
        if ($existingCount >= $config->matrix_size) {
            return back()->withErrors([
                'score' => "Cette matrice {$config->matrix_label} ne peut contenir que {$config->matrix_size} niveaux de fréquence.",
            ]);
        }

        $level = RiskFrequencyLevel::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        $this->syncCriteriaFromTemplates($level, $tenantId);

        return back()->with('success', "Niveau de fréquence « {$request->label} » créé avec succès.");
    }

    public function update(UpdateFrequencyLevelRequest $request, int $frequency_level): RedirectResponse
    {
        $tenantId        = (int)(session('tenant_id') ?? 1);
        $frequency_level = $this->findLevelForTenant($frequency_level, $tenantId);
        $frequency_level->update($request->validated());
        return back()->with('success', "Niveau de fréquence « {$frequency_level->label} » mis à jour.");
    }

    public function destroy(Request $request, int $frequency_level): RedirectResponse
    {
        $tenantId        = (int)(session('tenant_id') ?? 1);
        $frequency_level = $this->findLevelForTenant($frequency_level, $tenantId);
        $label           = $frequency_level->label;
        $frequency_level->delete();
        return back()->with('success', "Niveau de fréquence « {$label} » supprimé.");
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
            RiskFrequencyLevel::forTenant($tenantId)
                ->where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }
        return back()->with('success', 'Ordre mis à jour.');
    }

    // ─── Templates de critères ─────────────────────────────────────────────────

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

        $template = RiskFrequencyCriteriaTemplate::create([
            'tenant_id'        => $tenantId,
            'matrix_config_id' => $validated['matrix_config_id'],
            'designation'      => $validated['designation'],
            'hint'             => $validated['hint'] ?? null,
            'sort_order'       => $validated['sort_order']
                ?? RiskFrequencyCriteriaTemplate::where('matrix_config_id', $validated['matrix_config_id'])->count(),
        ]);

        // Créer une instance vide dans chaque niveau existant
        $levels = RiskFrequencyLevel::forTenant($tenantId)
            ->forConfig($validated['matrix_config_id'])
            ->get();

        foreach ($levels as $level) {
            RiskFrequencyCriterion::create([
                'tenant_id'          => $tenantId,
                'frequency_level_id' => $level->id,
                'template_id'        => $template->id,
                'designation'        => $template->designation,
                'description'        => null,
                'sort_order'         => $template->sort_order,
            ]);
        }

        return back()->with('success', "Critère « {$template->designation} » ajouté à tous les niveaux.");
    }

    public function updateTemplate(Request $request, int $template): RedirectResponse
    {
        $tenantId = (int)(session('tenant_id') ?? 1);
        $template = RiskFrequencyCriteriaTemplate::where('tenant_id', $tenantId)->findOrFail($template);

        $validated = $request->validate([
            'designation' => ['required', 'string', 'max:200'],
            'hint'        => ['nullable', 'string', 'max:500'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ]);

        $template->update($validated);

        RiskFrequencyCriterion::where('template_id', $template->id)
            ->update(['designation' => $validated['designation']]);

        return back()->with('success', "Critère « {$template->designation} » mis à jour sur tous les niveaux.");
    }

    public function destroyTemplate(int $template): RedirectResponse
    {
        $tenantId = (int)(session('tenant_id') ?? 1);
        $template = RiskFrequencyCriteriaTemplate::where('tenant_id', $tenantId)->findOrFail($template);
        $label    = $template->designation;
        RiskFrequencyCriterion::where('template_id', $template->id)->delete();
        $template->delete();
        return back()->with('success', "Critère « {$label} » supprimé de tous les niveaux.");
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
            RiskFrequencyCriteriaTemplate::where('tenant_id', $tenantId)
                ->where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
            RiskFrequencyCriterion::where('template_id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }
        return back()->with('success', 'Ordre des critères mis à jour.');
    }

    // ─── IA — Contenu critères ─────────────────────────────────────────────────

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

        $levels = RiskFrequencyLevel::forTenant($tenantId)
            ->forConfig($configId)
            ->ordered()
            ->get(['id', 'label', 'score', 'recurrence', 'description']);

        $templates = RiskFrequencyCriteriaTemplate::where('tenant_id', $tenantId)
            ->where('matrix_config_id', $configId)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->get(['id', 'designation', 'hint']);

        if ($levels->isEmpty() || $templates->isEmpty()) {
            return response()->json([
                'message' => 'Veuillez d\'abord définir les niveaux de fréquence et les critères templates.',
            ], 422);
        }

        $levelsDesc = $levels->map(fn($l) =>
            "- Niveau {$l->score} « {$l->label} »" .
            ($l->recurrence ? " (récurrence : {$l->recurrence})" : '') .
            ($l->description ? " : {$l->description}" : '')
        )->implode("\n");

        $criteriaDesc = $templates->map(fn($t, $i) =>
            ($i + 1) . ". « {$t->designation} »" .
            ($t->hint ? " — Indice : {$t->hint}" : '')
        )->implode("\n");

        $sector  = $request->input('sector');
        $context = $request->input('context', '');

        $levelIds   = $levels->pluck('id')->implode(', ');
        $templateIds = $templates->pluck('id')->implode(', ');

        $prompt = "Tu es un expert en gestion des risques dans le secteur : {$sector}.\n"
            . ($context ? "{$context}\n\n" : "\n")
            . "Voici les niveaux de fréquence de la matrice de risques :\n{$levelsDesc}\n\n"
            . "Voici les critères d'évaluation à renseigner pour chaque niveau :\n{$criteriaDesc}\n\n"
            . "Pour CHAQUE niveau et CHAQUE critère, génère une description précise, observable et mesurable,\n"
            . "adaptée au niveau de fréquence concerné et au secteur {$sector}.\n\n"
            . "Réponds UNIQUEMENT en JSON valide, sans balises markdown, sans commentaires.\n"
            . "Format attendu :\n"
            . "{\n"
            . '  "suggestions": { "[level_id]": { "[template_id]": "description" } },' . "\n"
            . '  "sector": "secteur utilisé"' . "\n"
            . "}\n\n"
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

        $levelIds = RiskFrequencyLevel::forTenant($tenantId)
            ->forConfig($configId)
            ->pluck('id');

        foreach ($request->input('suggestions') as $levelId => $templateMap) {
            if (!$levelIds->contains((int)$levelId)) continue;
            foreach ($templateMap as $templateId => $description) {
                RiskFrequencyCriterion::where('frequency_level_id', (int)$levelId)
                    ->where('template_id', (int)$templateId)
                    ->whereNull('deleted_at')
                    ->update(['description' => $description]);
            }
        }

        return back()->with('success', 'Contenu des critères appliqué avec succès.');
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    private function syncCriteriaFromTemplates(RiskFrequencyLevel $level, int $tenantId): void
    {
        $templates = RiskFrequencyCriteriaTemplate::where('tenant_id', $tenantId)
            ->where('matrix_config_id', $level->matrix_config_id)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->get();

        foreach ($templates as $template) {
            RiskFrequencyCriterion::create([
                'tenant_id'          => $tenantId,
                'frequency_level_id' => $level->id,
                'template_id'        => $template->id,
                'designation'        => $template->designation,
                'description'        => null,
                'sort_order'         => $template->sort_order,
            ]);
        }
    }

    private function findLevelForTenant(int $id, int $tenantId): RiskFrequencyLevel
    {
        $level = RiskFrequencyLevel::forTenant($tenantId)->findOrFail($id);
        abort_if($level->tenant_id !== $tenantId, 403, 'Accès non autorisé.');
        return $level;
    }
}