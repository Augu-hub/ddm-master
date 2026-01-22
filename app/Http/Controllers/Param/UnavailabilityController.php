<?php

namespace App\Http\Controllers\Param;

use App\Models\Param\GlobalUnavailability;
use App\Models\Param\AuditorUnavailability;
use App\Models\Param\Auditor;
use App\Models\Param\UnavailabilityType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;

class UnavailabilityController extends Controller
{
    /**
     * Afficher le calendrier des indisponibilités
     */
    public function index()
    {
        $globalTypes = UnavailabilityType::active()
            ->byCategory('global')
            ->orderBy('is_custom', 'asc')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'icon', 'color', 'is_custom']);

        $auditorTypes = UnavailabilityType::active()
            ->byCategory('auditor')
            ->orderBy('is_custom', 'asc')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'icon', 'color', 'is_custom']);

        $globalUnavailabilities = GlobalUnavailability::where('is_active', true)
            ->orderBy('date_start')
            ->get();

        $auditorUnavailabilities = AuditorUnavailability::with('auditor:id,first_name,last_name,code')
            ->orderBy('date_start')
            ->get();

        $auditors = Auditor::active()
            ->select('id', 'first_name', 'last_name', 'code')
            ->orderBy('first_name')
            ->get();

        return Inertia::render('dashboards/Param/Unavailabilities/Index', [
            'globalUnavailabilities' => $globalUnavailabilities,
            'auditorUnavailabilities' => $auditorUnavailabilities,
            'auditors' => $auditors,
            'globalTypes' => $globalTypes,
            'auditorTypes' => $auditorTypes,
        ]);
    }

    /**
     * Créer une indisponibilité globale
     */
    public function storeGlobal(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        GlobalUnavailability::create($validated);

        return redirect()->route('param.projects.unavailabilities.index')
            ->with('success', "Indisponibilité '{$validated['name']}' créée");
    }

    /**
     * Créer une indisponibilité auditeur
     */
    public function storeAuditor(Request $request)
    {
        $validated = $request->validate([
            'auditor_id' => 'required|exists:auditors,id',
            'type' => 'required|string|max:100',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
        ]);

        $validated['is_approved'] = false;

        AuditorUnavailability::create($validated);

        return redirect()->route('param.projects.unavailabilities.index')
            ->with('success', 'Indisponibilité auditeur ajoutée');
    }

    /**
     * Mettre à jour une indisponibilité
     */
    public function update(Request $request, $id, $type)
    {
        $validated = $type === 'global'
            ? $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|string',
                'date_start' => 'required|date',
                'date_end' => 'required|date|after_or_equal:date_start',
                'is_active' => 'nullable|boolean',
            ])
            : $request->validate([
                'auditor_id' => 'required|exists:auditors,id',
                'type' => 'required|string',
                'date_start' => 'required|date',
                'date_end' => 'required|date|after_or_equal:date_start',
            ]);

        $model = $type === 'global' 
            ? GlobalUnavailability::findOrFail($id)
            : AuditorUnavailability::findOrFail($id);

        $model->update($validated);

        return redirect()->route('param.projects.unavailabilities.index')
            ->with('success', 'Indisponibilité mise à jour');
    }

    /**
     * Approuver une indisponibilité auditeur
     */
    public function approveAuditor(AuditorUnavailability $unavailability)
    {
        $unavailability->update([
            'is_approved' => true,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('param.projects.unavailabilities.index')
            ->with('success', 'Indisponibilité approuvée');
    }

    /**
     * Rejeter une indisponibilité auditeur
     */
    public function rejectAuditor(Request $request, AuditorUnavailability $unavailability)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $unavailability->update([
            'is_approved' => false,
            'rejection_reason' => $validated['reason'],
        ]);

        return redirect()->route('param.projects.unavailabilities.index')
            ->with('success', 'Indisponibilité rejetée');
    }

    /**
     * Supprimer une indisponibilité
     */
    public function destroy(Request $request, $id, $type)
    {
        $model = $type === 'global'
            ? GlobalUnavailability::findOrFail($id)
            : AuditorUnavailability::findOrFail($id);

        $name = $model->name ?? ($model->auditor->first_name ?? '');
        $model->delete();

        return redirect()->route('param.projects.unavailabilities.index')
            ->with('success', 'Supprimée');
    }

    /**
     * Vérifier disponibilité auditeur
     */
    public function checkAvailability(Request $request)
    {
        $validated = $request->validate([
            'auditor_id' => 'required|integer',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
        ]);

        $unavailabilities = AuditorUnavailability::where('auditor_id', $validated['auditor_id'])
            ->where('date_start', '<=', $validated['date_end'])
            ->where('date_end', '>=', $validated['date_start'])
            ->where('is_approved', true)
            ->get();

        $isAvailable = $unavailabilities->isEmpty();

        return response()->json([
            'available' => $isAvailable,
            'conflicts' => $unavailabilities,
            'message' => $isAvailable ? 'Auditeur disponible' : 'Conflits détectés'
        ]);
    }

    /**
     * Récupérer indisponibilités par période
     */
    public function getByPeriod(Request $request)
    {
        $validated = $request->validate([
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
            'auditor_id' => 'nullable|integer',
            'type' => 'nullable|string',
        ]);

        $query = AuditorUnavailability::with('auditor')
            ->where('date_start', '<=', $validated['date_end'])
            ->where('date_end', '>=', $validated['date_start'])
            ->where('is_approved', true);

        if ($validated['auditor_id'] ?? null) {
            $query->where('auditor_id', $validated['auditor_id']);
        }

        if ($validated['type'] ?? null) {
            $query->where('type', $validated['type']);
        }

        $unavailabilities = $query->orderBy('date_start')->get();

        return response()->json([
            'unavailabilities' => $unavailabilities,
            'count' => $unavailabilities->count(),
        ]);
    }

    /**
     * Récupérer statistiques
     */
    public function getStats()
    {
        $stats = [
            'global_count' => GlobalUnavailability::where('is_active', true)->count(),
            'auditor_count' => AuditorUnavailability::count(),
            'approved_count' => AuditorUnavailability::where('is_approved', true)->count(),
            'pending_count' => AuditorUnavailability::where('is_approved', false)->count(),
            'affected_auditors' => AuditorUnavailability::distinct('auditor_id')->count(),
            'days_unavailable' => $this->calculateTotalUnavailableDays(),
        ];

        return response()->json($stats);
    }

    /**
     * Créer type personnalisé
     */
    public function createType(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|in:global,auditor',
            'name' => 'required|string|max:100',
            'icon' => 'nullable|string|max:10',
            'color' => 'nullable|regex:/^#[A-Fa-f0-9]{6}$/',
        ]);

        $code = 'custom_' . Str::slug($validated['name']);

        $type = UnavailabilityType::firstOrCreate(
            ['code' => $code],
            [
                'category' => $validated['category'],
                'name' => $validated['name'],
                'icon' => $validated['icon'] ?? '📌',
                'color' => $validated['color'] ?? '#667eea',
                'is_active' => true,
                'is_custom' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'type' => $type,
        ]);
    }

    /**
     * Récupérer types par catégorie
     */
    public function getTypesByCategory($category)
    {
        $types = UnavailabilityType::active()
            ->byCategory($category)
            ->orderBy('is_custom', 'asc')
            ->orderBy('name')
            ->get();

        return response()->json($types);
    }

    /**
     * Générer rapport auditeur
     */
    public function generateAuditorReport(Request $request)
    {
        $validated = $request->validate([
            'auditor_id' => 'required|integer',
            'year' => 'nullable|integer',
        ]);

        $year = $validated['year'] ?? now()->year;
        $auditor = Auditor::findOrFail($validated['auditor_id']);

        $unavailabilities = AuditorUnavailability::where('auditor_id', $validated['auditor_id'])
            ->where('is_approved', true)
            ->whereYear('date_start', $year)
            ->get();

        $totalDays = $unavailabilities->sum(function($u) {
            return ceil((strtotime($u->date_end) - strtotime($u->date_start)) / (60*60*24)) + 1;
        });

        $byType = $unavailabilities->groupBy('type')->map(function($items) {
            return [
                'count' => $items->count(),
                'days' => $items->sum(function($u) {
                    return ceil((strtotime($u->date_end) - strtotime($u->date_start)) / (60*60*24)) + 1;
                })
            ];
        });

        return response()->json([
            'success' => true,
            'auditor' => $auditor,
            'year' => $year,
            'total_days' => $totalDays,
            'total_entries' => $unavailabilities->count(),
            'by_type' => $byType,
            'details' => $unavailabilities,
        ]);
    }

    /**
     * Exporter en CSV
     */
    public function exportCsv()
    {
        $filename = 'indisponibilites_' . now()->format('Y-m-d_His') . '.csv';

        $callback = function() {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Type', 'Nom/Auditeur', 'Type', 'Début', 'Fin', 'Jours', 'Statut']);

            // Indisponibilités globales
            GlobalUnavailability::where('is_active', true)
                ->orderBy('date_start')
                ->each(function($u) use ($file) {
                    $days = ceil((strtotime($u->date_end) - strtotime($u->date_start)) / (60*60*24)) + 1;
                    fputcsv($file, [
                        'GLOBAL',
                        $u->name,
                        $u->type,
                        $u->date_start,
                        $u->date_end,
                        $days,
                        'Actif'
                    ]);
                });

            fputcsv($file, []);

            // Indisponibilités auditeurs
            AuditorUnavailability::with('auditor')
                ->orderBy('date_start')
                ->each(function($u) use ($file) {
                    $days = ceil((strtotime($u->date_end) - strtotime($u->date_start)) / (60*60*24)) + 1;
                    fputcsv($file, [
                        'AUDITEUR',
                        $u->auditor->first_name . ' ' . $u->auditor->last_name,
                        $u->type,
                        $u->date_start,
                        $u->date_end,
                        $days,
                        $u->is_approved ? 'Approuvé' : 'En attente'
                    ]);
                });

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    /**
     * Exporter en JSON
     */
    public function exportJson()
    {
        $global = GlobalUnavailability::where('is_active', true)->get();
        $auditor = AuditorUnavailability::with('auditor')->get();

        return response()->json([
            'exported_at' => now(),
            'global_unavailabilities' => $global,
            'auditor_unavailabilities' => $auditor,
        ])->header('Content-Disposition', 'attachment; filename="indisponibilites.json"');
    }

    /**
     * Importer depuis JSON
     */
    public function importJson(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:json'
        ]);

        try {
            $content = json_decode(file_get_contents($request->file('file')), true);

            $imported = ['global' => 0, 'auditor' => 0];

            if (isset($content['global_unavailabilities'])) {
                foreach ($content['global_unavailabilities'] as $item) {
                    GlobalUnavailability::create([
                        'name' => $item['name'],
                        'type' => $item['type'],
                        'date_start' => $item['date_start'],
                        'date_end' => $item['date_end'],
                        'is_active' => $item['is_active'] ?? true,
                    ]);
                    $imported['global']++;
                }
            }

            if (isset($content['auditor_unavailabilities'])) {
                foreach ($content['auditor_unavailabilities'] as $item) {
                    AuditorUnavailability::create([
                        'auditor_id' => $item['auditor_id'],
                        'type' => $item['type'],
                        'date_start' => $item['date_start'],
                        'date_end' => $item['date_end'],
                    ]);
                    $imported['auditor']++;
                }
            }

            return redirect()->route('param.projects.unavailabilities.index')
                ->with('success', "Importé: {$imported['global']} globales, {$imported['auditor']} auditeurs");
        } catch (\Exception $e) {
            return back()->withError('Erreur import: ' . $e->getMessage());
        }
    }

    /**
     * Bloquer une période pour tous les auditeurs
     */
    public function blockPeriodForAll(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
            'type' => 'required|string',
        ]);

        $global = GlobalUnavailability::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'date_start' => $validated['date_start'],
            'date_end' => $validated['date_end'],
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Période bloquée pour tous les auditeurs",
            'unavailability' => $global,
        ]);
    }

    /**
     * Bulk approve
     */
    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $updated = AuditorUnavailability::whereIn('id', $validated['ids'])
            ->update([
                'is_approved' => true,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => "$updated indisponibilités approuvées",
            'count' => $updated,
        ]);
    }

    /**
     * Récupérer les auditeurs avec calendrier personnalisé
     */
    public function getAuditorCalendar(Request $request)
    {
        $validated = $request->validate([
            'auditor_id' => 'required|integer',
            'year' => 'required|integer',
        ]);

        $auditor = Auditor::findOrFail($validated['auditor_id']);
        $unavailabilities = AuditorUnavailability::where('auditor_id', $validated['auditor_id'])
            ->whereYear('date_start', $validated['year'])
            ->get()
            ->map(function($u) {
                return [
                    'id' => $u->id,
                    'type' => $u->type,
                    'date_start' => $u->date_start,
                    'date_end' => $u->date_end,
                    'is_approved' => $u->is_approved,
                ];
            });

        return response()->json([
            'success' => true,
            'auditor' => $auditor,
            'year' => $validated['year'],
            'unavailabilities' => $unavailabilities,
        ]);
    }

    /**
     * Calculer jours indisponibles
     */
    private function calculateTotalUnavailableDays()
    {
        $auditor = AuditorUnavailability::where('is_approved', true)
            ->get()
            ->sum(function($u) {
                return ceil((strtotime($u->date_end) - strtotime($u->date_start)) / (60*60*24)) + 1;
            });

        $global = GlobalUnavailability::where('is_active', true)
            ->get()
            ->sum(function($u) {
                return ceil((strtotime($u->date_end) - strtotime($u->date_start)) / (60*60*24)) + 1;
            });

        return ['auditor' => $auditor, 'global' => $global];
    }
}