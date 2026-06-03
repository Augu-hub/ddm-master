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
        $tenantId = (int) (session('tenant_id') ?? 1);

        $matrixConfigs = RiskMatrixConfig::forTenant($tenantId)
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id'           => $c->id,
                'name'         => $c->name,
                'matrix_size'  => $c->matrix_size,
                'matrix_label' => $c->matrix_label,
                'is_active'    => $c->is_active,
            ]);

        $selectedConfigId = $request->integer('config_id')
            ?: optional($matrixConfigs->firstWhere('is_active', true))['id']
                ?: optional($matrixConfigs->first())['id'];

        // Niveaux avec critères instanciés (liés aux templates)
        $frequencyLevels = $selectedConfigId
            ? RiskFrequencyLevel::forTenant($tenantId)
                ->forConfig($selectedConfigId)
                ->with([
                    'criteria' => fn ($q) => $q->orderBy('sort_order'),
                ])
                ->ordered()
                ->get()
                ->map(fn ($l) => [
                    'id'          => $l->id,
                    'label'       => $l->label,
                    'score'       => $l->score,
                    'description' => $l->description,
                    'recurrence'  => $l->recurrence,
                    'full_label'  => $l->full_label,
                    'color_code'  => $l->color_code,
                    'sort_order'  => $l->sort_order,
                    'criteria'    => $l->criteria->map(fn ($c) => [
                        'id'          => $c->id,
                        'template_id' => $c->template_id,   // null pour anciens critères libres
                        'designation' => $c->designation,
                        'description' => $c->description,
                        'sort_order'  => $c->sort_order,
                    ])->values()->all(),
                ])
            : collect();

        // Templates de critères définis pour cette config
        $criteriaTemplates = $selectedConfigId
            ? RiskFrequencyCriteriaTemplate::where('tenant_id', $tenantId)
                ->where('matrix_config_id', $selectedConfigId)
                ->whereNull('deleted_at')
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($t) => [
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

    // ─── Niveaux CRUD (inchangé) ───────────────────────────────────────────────

    public function store(StoreFrequencyLevelRequest $request): RedirectResponse
    {
        $tenantId = (int) (session('tenant_id') ?? 1);

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

        // Instancier automatiquement une ligne vide pour chaque template existant
        $this->syncCriteriaFromTemplates($level, $tenantId);

        return back()->with('success', "Niveau de fréquence « {$request->label} » créé avec succès.");
    }

    public function update(UpdateFrequencyLevelRequest $request, int $frequency_level): RedirectResponse
    {
        $tenantId        = (int) (session('tenant_id') ?? 1);
        $frequency_level = $this->findLevelForTenant($frequency_level, $tenantId);

        $frequency_level->update($request->validated());

        return back()->with('success', "Niveau de fréquence « {$frequency_level->label} » mis à jour.");
    }

    public function destroy(Request $request, int $frequency_level): RedirectResponse
    {
        $tenantId        = (int) (session('tenant_id') ?? 1);
        $frequency_level = $this->findLevelForTenant($frequency_level, $tenantId);

        $label = $frequency_level->label;
        $frequency_level->delete();

        return back()->with('success', "Niveau de fréquence « {$label} » supprimé.");
    }

    public function reorder(Request $request): RedirectResponse
    {
        $tenantId = (int) (session('tenant_id') ?? 1);

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

    /**
     * Crée un template de critère pour une config.
     * Instancie automatiquement une ligne vide (description = null)
     * dans risk_frequency_criteria pour chaque niveau existant.
     *
     * Route : POST /frequency/templates
     */
    public function storeTemplate(Request $request): RedirectResponse
    {
        $tenantId = (int) (session('tenant_id') ?? 1);

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

    /**
     * Met à jour un template et propage la désignation dans toutes ses instances.
     *
     * Route : PUT /frequency/templates/{template}
     */
    public function updateTemplate(Request $request, int $template): RedirectResponse
    {
        $tenantId = (int) (session('tenant_id') ?? 1);
        $template = RiskFrequencyCriteriaTemplate::where('tenant_id', $tenantId)->findOrFail($template);

        $validated = $request->validate([
            'designation' => ['required', 'string', 'max:200'],
            'hint'        => ['nullable', 'string', 'max:500'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ]);

        $template->update($validated);

        // Propager la désignation dans toutes les instances
        RiskFrequencyCriterion::where('template_id', $template->id)
            ->update(['designation' => $validated['designation']]);

        return back()->with('success', "Critère « {$template->designation} » mis à jour sur tous les niveaux.");
    }

    /**
     * Supprime un template et toutes ses instances (soft delete).
     *
     * Route : DELETE /frequency/templates/{template}
     */
    public function destroyTemplate(int $template): RedirectResponse
    {
        $tenantId = (int) (session('tenant_id') ?? 1);
        $template = RiskFrequencyCriteriaTemplate::where('tenant_id', $tenantId)->findOrFail($template);

        $label = $template->designation;

        RiskFrequencyCriterion::where('template_id', $template->id)->delete();
        $template->delete();

        return back()->with('success', "Critère « {$label} » supprimé de tous les niveaux.");
    }

    /**
     * Réordonne les templates et propage l'ordre dans les instances.
     *
     * Route : POST /frequency/templates/reorder
     */
    public function reorderTemplates(Request $request): RedirectResponse
    {
        $tenantId = (int) (session('tenant_id') ?? 1);

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

    // ─── IA — Suggestions niveaux (existant, inchangé) ────────────────────────

    // La route /frequency/mistral/suggest reste dans ce contrôleur
    // telle qu'elle était dans votre code original.

    // ─── IA — Contenu critères (template × niveaux) ───────────────────────────

    /**
     * Génère via Mistral la description de chaque critère pour chaque niveau.
     * S'appuie sur les templates définis + la description qualitative des niveaux.
     *
     * Route : POST /frequency/criteria/suggest-content
     *
     * Body  : { sector, context?, matrix_config_id }
     * Retour: { suggestions: { [level_id]: { [template_id]: "description" } }, sector }
     */
    public function suggestCriteriaContent(Request $request)
    {
        $tenantId = (int) (session('tenant_id') ?? 1);

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

        $levelsDesc = $levels->map(fn ($l) =>
            "- Niveau {$l->score} « {$l->label} »" .
            ($l->recurrence ? " (récurrence : {$l->recurrence})" : '') .
            ($l->description ? " : {$l->description}" : '')
        )->implode("\n");

        $criteriaDesc = $templates->map(fn ($t, $i) =>
            ($i + 1) . ". « {$t->designation} »" .
            ($t->hint ? " — Indice : {$t->hint}" : '')
        )->implode("\n");

        $sector  = $request->input('sector');
        $context = $request->input('context', '');

        $prompt = <<<PROMPT
Tu es un expert en gestion des risques dans le secteur : {$sector}.
{$context}

Voici les niveaux de fréquence de la matrice de risques :
{$levelsDesc}

Voici les critères d'évaluation à renseigner pour chaque niveau :
{$criteriaDesc}

Pour CHAQUE niveau et CHAQUE critère, génère une description précise, observable et mesurable,
adaptée au niveau de fréquence concerné et au secteur {$sector}.

Réponds UNIQUEMENT en JSON valide, sans balises markdown, sans commentaires.
Format attendu :
{
  "suggestions": {
    "[level_id]": {
      "[template_id]": "description du critère pour ce niveau"
    }
  },
  "sector": "secteur utilisé"
}

Les level_id sont : {$levels->pluck('id')->implode(', ')}
Les template_id sont : {$templates->pluck('id')->implode(', ')}
PROMPT;

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.mistral.api_key'),
                'Content-Type'  => 'application/json',
            ])->post('https://api.mistral.ai/v1/chat/completions', [
                'model'       => config('services.mistral.model', 'mistral-small-latest'),
                'temperature' => 0.4,
                'messages'    => [['role' => 'user', 'content' => $prompt]],
            ]);

            if (! $response->successful()) {
                return response()->json(['message' => 'Erreur Mistral : ' . $response->status()], 502);
            }

            $content = preg_replace('/```json\s*|\s*```/', '', trim(
                $response->json('choices.0.message.content', '')
            ));
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE || ! isset($data['suggestions'])) {
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

    /**
     * Applique les suggestions IA en masse dans les instances risk_frequency_criteria.
     * Met à jour uniquement la colonne `description` des lignes déjà existantes
     * (créées lors de storeTemplate ou syncCriteriaFromTemplates).
     *
     * Route : POST /frequency/criteria/apply-content
     *
     * Body  : { matrix_config_id, suggestions: { [level_id]: { [template_id]: "description" } } }
     */
    public function applyCriteriaContent(Request $request): RedirectResponse
    {
        $tenantId = (int) (session('tenant_id') ?? 1);

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
            if (! $levelIds->contains((int) $levelId)) {
                continue; // sécurité tenant
            }
            foreach ($templateMap as $templateId => $description) {
                RiskFrequencyCriterion::where('frequency_level_id', (int) $levelId)
                    ->where('template_id', (int) $templateId)
                    ->whereNull('deleted_at')
                    ->update(['description' => $description]);
            }
        }

        return back()->with('success', 'Contenu des critères appliqué avec succès.');
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Pour un niveau nouvellement créé, instancier une ligne vide
     * dans risk_frequency_criteria pour chaque template déjà défini.
     * Utilise RiskFrequencyCriterion — le modèle existant.
     */
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