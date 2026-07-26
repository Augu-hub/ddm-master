<template>
    <ul class="audit-nav">
        <li class="audit-nav-title">ESPACE AUDITEUR</li>

        <li
            v-for="item in menuItems"
            :key="item.label"
            class="audit-nav-item"
            :class="{ active: isActive(item.route) }"
        >
            <component
                :is="item.route ? Link : 'a'"
                :href="item.route ? route(item.route) : '#'"
                class="audit-nav-link"
            >
                <span class="nav-icon">
                    <i :class="item.icon"></i>
                </span>
                <span class="nav-label">{{ item.label }}</span>

                <span v-if="item.badge" class="nav-badge">{{ item.badge }}</span>
            </component>
        </li>

        <!-- ════════════════════════════════════════════════════════════
             MISSION EN COURS — présent dès qu'une page de formulaire est
             ouverte (missionMenu injecté par BasePhaseFormController).
             Chaque formulaire de la mission est navigable directement,
             avec son statut et le bon contexte (?mission_id&assignment_id).
        ════════════════════════════════════════════════════════════ -->
        <template v-if="missionMenu.length">
            <li class="audit-nav-title mission-title">
                <i class="ti ti-target-arrow"></i>
                MISSION EN COURS
            </li>
            <li v-if="missionInfo" class="mission-head">
                <code class="mission-code">{{ missionInfo.code_mission }}</code>
                <span class="mission-libelle">{{ missionInfo.libelle }}</span>
            </li>

            <li v-for="phase in missionMenu" :key="phase.phase_num" class="mission-phase">
                <button class="mission-phase-head" @click="togglePhase(phase.phase_num)">
                    <i :class="openPhases.has(phase.phase_num) ? 'ti ti-chevron-down' : 'ti ti-chevron-right'"></i>
                    <span class="mp-num">{{ phase.phase_num }}</span>
                    <span class="mp-label">{{ phase.phase_label }}</span>
                    <span class="mp-count">{{ phase.forms.length }}</span>
                </button>

                <ul v-show="openPhases.has(phase.phase_num)" class="mission-forms">
                    <li v-for="f in phase.forms" :key="f.assignment_id"
                        class="mission-form"
                        :class="{
                            'mf-child':    !!f.parent_id,
                            'mf-active':   isCurrentForm(f),
                            'mf-disabled': !f.url,
                        }">
                        <component
                            :is="f.url ? 'a' : 'span'"
                            :href="f.url ?? undefined"
                            class="mf-link"
                            :title="f.label"
                        >
                            <span class="mf-status" :class="`mfs-${f.status}`"></span>
                            <i :class="f.icon" class="mf-icon"></i>
                            <span class="mf-label">{{ f.label }}</span>
                            <i v-if="f.validation_status === 'validated'" class="ti ti-lock mf-lock"></i>
                        </component>
                    </li>
                </ul>
            </li>
        </template>
    </ul>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const page = usePage();

const missionsDemarrables = computed(() =>
    (page.props as any).missionsDemarrables ?? []
);

// ── Menu mission (injecté par BasePhaseFormController::buildPayload) ──
const missionMenu = computed<any[]>(() => (page.props as any).missionMenu ?? []);
const missionInfo = computed<any>(() => (page.props as any).mission ?? null);
const currentAssignmentId = computed<number | null>(() => {
    const id = (page.props as any).assignmentId;
    return id ? Number(id) : null;
});

// Phases dépliées — par défaut, celle du formulaire courant (sinon toutes)
const openPhases = reactive<Set<number>>(new Set());
let initialized = false;
function initOpenPhases() {
    if (initialized || !missionMenu.value.length) return;
    initialized = true;
    const current = missionMenu.value.find(p =>
        (p.forms ?? []).some((f: any) => f.assignment_id === currentAssignmentId.value)
    );
    if (current) openPhases.add(current.phase_num);
    else missionMenu.value.forEach(p => openPhases.add(p.phase_num));
}
initOpenPhases();

function togglePhase(num: number) {
    if (openPhases.has(num)) openPhases.delete(num);
    else openPhases.add(num);
}

function isCurrentForm(f: any): boolean {
    return currentAssignmentId.value !== null && f.assignment_id === currentAssignmentId.value;
}

const menuItems = computed(() => [
    {
        label: "Vue d'ensemble",
        route: 'auditor.dashboard',
        icon:  'ti ti-layout-dashboard',
        badge: null,
    },
    {
        label: 'Mes Missions',
        // Route canonique m/audit.core (cohérente avec MesMissions/MissionPhases)
        route: 'audit.core.auditor.missions.index',
        icon:  'ti ti-briefcase',
        badge: missionsDemarrables.value.length || null,
    },
    {
        label: 'Planning',
        route: 'auditor.planning',      // → dashboard, onglet Planning
        icon:  'ti ti-calendar-stats',
        badge: null,
    },
    {
        label: 'Compétences',
        route: 'auditor.competences',   // → page dédiée (référentiel + niveaux)
        icon:  'ti ti-award',
        badge: null,
    },
    {
        label: 'Budget',
        route: 'auditor.budget',        // → dashboard, onglet Budget
        icon:  'ti ti-coin',
        badge: null,
    },
]);

function isActive(routeName: string | null | undefined): boolean {
    if (!routeName) return false;
    try {
        const routeUrl = route(routeName).replace(window.location.origin, '');
        return page.url.startsWith(routeUrl);
    } catch {
        return false;
    }
}
</script>

<style scoped>
/* ════════════════════════════════════════
   NAV LIST
   ════════════════════════════════════════ */
.audit-nav {
    list-style: none;
    padding: 8px 0 24px;
    margin: 0;
}

/* Titre section */
.audit-nav-title {
    color: rgba(148, 163, 184, 0.5);
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    padding: 20px 20px 8px;
}

/* Item */
.audit-nav-item {
    margin: 1px 8px;
    border-radius: 8px;
    overflow: hidden;
}

/* Lien */
.audit-nav-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 12px;
    color: rgba(203, 213, 225, 0.75);
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.18s ease;
    position: relative;
}

/* Hover */
.audit-nav-link:hover {
    color: #e2e8f0;
    background: rgba(255, 255, 255, 0.06);
}

.audit-nav-link:hover .nav-icon i {
    color: #60a5fa;
}

/* Actif */
.audit-nav-item.active .audit-nav-link {
    color: #ffffff;
    background: linear-gradient(90deg, rgba(59, 130, 246, 0.2) 0%, rgba(59, 130, 246, 0.08) 100%);
    box-shadow: inset 3px 0 0 #3b82f6;
}

.audit-nav-item.active .nav-icon i {
    color: #60a5fa;
}

/* Icône */
.nav-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 7px;
    flex-shrink: 0;
    background: rgba(255, 255, 255, 0.04);
    transition: background 0.18s;
}

.nav-icon i {
    font-size: 1.1rem;
    color: rgba(148, 163, 184, 0.7);
    transition: color 0.18s;
}

.audit-nav-item.active .nav-icon {
    background: rgba(59, 130, 246, 0.15);
}

/* Label */
.nav-label {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Badge */
.nav-badge {
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 10px;
    background: #ef4444;
    color: #fff;
    font-size: 0.7rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* ════════════════════════════════════════
   MISSION EN COURS
   ════════════════════════════════════════ */
.mission-title {
    display: flex;
    align-items: center;
    gap: 6px;
    color: rgba(96, 165, 250, 0.85);
    border-top: 1px solid rgba(255, 255, 255, 0.06);
    margin-top: 12px;
}

.mission-head {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 2px 20px 8px;
}
.mission-code {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.66rem;
    font-weight: 700;
    color: #60a5fa;
}
.mission-libelle {
    font-size: 0.7rem;
    color: rgba(203, 213, 225, 0.6);
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.mission-phase { margin: 0 8px; }

.mission-phase-head {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 7px 10px;
    background: transparent;
    border: none;
    border-radius: 8px;
    color: rgba(203, 213, 225, 0.8);
    font-size: 0.74rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.15s;
    text-align: left;
    font-family: inherit;
}
.mission-phase-head:hover { background: rgba(255, 255, 255, 0.05); }
.mission-phase-head > i { font-size: 0.7rem; color: rgba(148, 163, 184, 0.6); }

.mp-num {
    width: 17px;
    height: 17px;
    border-radius: 5px;
    background: rgba(59, 130, 246, 0.15);
    color: #60a5fa;
    font-size: 0.6rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.mp-label {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.mp-count {
    font-size: 0.58rem;
    font-weight: 700;
    color: rgba(148, 163, 184, 0.6);
    background: rgba(255, 255, 255, 0.06);
    padding: 1px 6px;
    border-radius: 8px;
}

.mission-forms {
    list-style: none;
    margin: 0 0 4px;
    padding: 0 0 0 14px;
}

.mission-form { border-radius: 7px; overflow: hidden; }
.mission-form.mf-child { margin-left: 12px; }

.mf-link {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 5px 9px;
    color: rgba(203, 213, 225, 0.65);
    font-size: 0.72rem;
    text-decoration: none;
    border-radius: 7px;
    transition: all 0.14s;
}
a.mf-link:hover { color: #e2e8f0; background: rgba(255, 255, 255, 0.06); }

.mission-form.mf-active .mf-link {
    color: #fff;
    background: rgba(59, 130, 246, 0.18);
    box-shadow: inset 2px 0 0 #3b82f6;
}
.mission-form.mf-disabled .mf-link {
    opacity: 0.4;
    cursor: default;
    text-decoration: line-through;
}

.mf-status {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    flex-shrink: 0;
    background: rgba(148, 163, 184, 0.35); /* pending */
}
.mfs-in_progress { background: #fbbf24; }
.mfs-completed   { background: #34d399; }
.mfs-skipped     { background: rgba(148, 163, 184, 0.2); }

.mf-icon {
    font-size: 0.78rem;
    color: rgba(148, 163, 184, 0.55);
    flex-shrink: 0;
}
.mf-label {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.mf-lock {
    font-size: 0.66rem;
    color: #34d399;
    flex-shrink: 0;
}
</style>
