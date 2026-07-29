<?php

namespace App\Http\Controllers\Concerns;

use App\Services\Audit\PhaseSyncService;
use Illuminate\Support\Facades\DB;

/**
 * Menu latéral "Mission en cours" : toutes les phases/formulaires d'une
 * mission, DIRECTEMENT depuis le référentiel central (ddmparam) — consommé
 * par VerticalMenuAudit (sidebar) via la prop Inertia `missionMenu`.
 *
 * Utilisé par BasePhaseFormController (toutes les pages de formulaire) ET
 * AuditorMissionsController (page MissionPhases) : l'auditeur garde la
 * navigation de sa mission partout.
 *
 * ⚠️ NOUVEAU SCHÉMA : mission_phases.id = ddmparam.audit_type_forms.id —
 * le contenu (label, phase, url_path, hiérarchie) est lu dans ddmparam.
 */
trait BuildsMissionMenu
{
    protected function buildMissionMenu(int $missionId): array
    {
        try {
            // ★ PROVISION AUTO : garantit que la mission possède un assignment
            // par formulaire actif ddmparam de son type d'audit (obligatoires
            // → pending, optionnels → skipped). Idempotent, cache 5 min —
            // les phases apparaissent d'elles-mêmes à l'ouverture de la
            // mission, même créée avant la génération auto ou après un ajout
            // de formulaire côté admin central.
            PhaseSyncService::ensureMissionAssignments($missionId);

            $rows = DB::table('mission_phase_assignments as mpa')
                ->join('ddmparam.audit_type_forms as atf', 'mpa.mission_phase_id', '=', 'atf.id')
                ->where('mpa.mission_programmation_id', $missionId)
                // Ne montrer que les formulaires ACTIFS de ddmparam : une phase
                // désactivée côté central (is_active=0) disparaît du menu même
                // si un assignment existe déjà (complément de centralFormIds()).
                ->where('atf.is_active', 1)
                ->orderBy('atf.phase_num')
                ->orderBy('atf.sort_order')
                ->orderBy('atf.id')
                ->get([
                    'mpa.id as assignment_id',
                    'mpa.status as phase_status',
                    'mpa.validation_status',
                    'atf.id as form_id',
                    'atf.phase_num',
                    'atf.phase_label',
                    'atf.parent_id',
                    'atf.code',
                    'atf.label',
                    'atf.icon',
                    'atf.url_path',
                ]);

            $menu = [];
            foreach ($rows->groupBy('phase_num') as $phaseNum => $group) {
                $menu[] = [
                    'phase_num'   => (int) $phaseNum,
                    'phase_label' => $group->first()->phase_label,
                    'forms'       => $group->map(fn ($r) => [
                        'assignment_id'     => (int) $r->assignment_id,
                        'form_id'           => (int) $r->form_id,
                        'code'              => $r->code,
                        'label'             => $r->label,
                        'icon'              => $r->icon ?: 'ti ti-file-description',
                        'parent_id'         => $r->parent_id ? (int) $r->parent_id : null,
                        'status'            => $r->phase_status,
                        'validation_status' => $r->validation_status ?? 'draft',
                        'url'               => $r->url_path
                            ? url('/' . ltrim($r->url_path, '/'))
                                . "?mission_id={$missionId}&assignment_id={$r->assignment_id}"
                            : null,
                    ])->values()->toArray(),
                ];
            }

            return $menu;
        } catch (\Throwable $e) {
            // Un menu absent ne doit jamais casser une page
            return [];
        }
    }
}
