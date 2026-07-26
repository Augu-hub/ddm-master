<template>
    <VerticalLayout>
        <div class="mm-shell">

            <!-- ══ HEADER HERO ══ -->
            <div class="mm-hero">
                <div class="hero-bg-mesh"></div>
                <div class="hero-content">
                    <div class="hero-left">
                        <div class="hero-avatar">
                            <img v-if="auditor.avatar" :src="auditor.avatar" :alt="auditor.nom_complet" />
                            <span v-else class="av-initials">{{ auditor.initiales }}</span>
                            <span class="av-status-dot"></span>
                        </div>
                        <div class="hero-info">
                            <p class="hi-role">Espace Auditeur</p>
                            <h1 class="hi-name">{{ auditor.nom_complet }}</h1>
                            <div class="hi-meta">
                                <span class="hm-pill">
                                    <i class="ti ti-building"></i>
                                    {{ auditor.entity || 'Cabinet KEKELI' }}
                                </span>
                                <span class="hm-pill hm-code">
                                    <i class="ti ti-fingerprint"></i>
                                    {{ auditor.audit_code }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="hero-kpis">
                        <div v-for="kpi in kpiCards" :key="kpi.key" class="kpi-card" :class="'kpi-'+kpi.key">
                            <div class="kpi-icon"><i :class="kpi.icon"></i></div>
                            <div class="kpi-body">
                                <span class="kpi-val">{{ kpi.val }}</span>
                                <span class="kpi-lbl">{{ kpi.lbl }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══ TOOLBAR ══ -->
            <div class="mm-toolbar">
                <div class="tb-left">
                    <span class="tb-count">
                        <i class="ti ti-clipboard-list"></i>
                        <strong>{{ filteredAffectations.length }}</strong> mission{{ filteredAffectations.length > 1 ? 's' : '' }}
                    </span>
                    <div class="tb-search">
                        <i class="ti ti-search"></i>
                        <input v-model="search" placeholder="Rechercher…" />
                        <button v-if="search" @click="search=''" class="sr-clear"><i class="ti ti-x"></i></button>
                    </div>
                </div>
                <div class="tb-right">
                    <div class="tb-filters">
                        <button v-for="f in statusFilters" :key="f.v" class="sf-btn"
                            :class="{active: fStatus===f.v, ['sf-'+f.v]: true}"
                            @click="fStatus=f.v">
                            <span class="sf-dot"></span>
                            {{ f.l }}
                            <span class="sf-cnt">{{ countByStatus(f.v) }}</span>
                        </button>
                    </div>
                    <div class="tb-views">
                        <button :class="{active:viewMode==='cards'}" @click="viewMode='cards'" title="Cartes"><i class="ti ti-layout-grid"></i></button>
                        <button :class="{active:viewMode==='list'}" @click="viewMode='list'" title="Liste"><i class="ti ti-list"></i></button>
                    </div>
                </div>
            </div>

            <!-- ══ CONTENU ══ -->
            <div class="mm-body">

                <!-- Vue Cartes -->
                <div v-if="viewMode==='cards'" class="cards-grid">
                    <div v-for="aff in filteredAffectations" :key="aff.id"
                        class="m-card" :class="'mc-'+aff.status"
                        @click="openGantt(aff)">

                        <!-- Bande latérale statut -->
                        <div class="mc-stripe" :class="'stripe-'+aff.status"></div>

                        <div class="mc-inner">
                            <!-- Header carte -->
                            <div class="mc-head">
                                <div class="mc-head-left">
                                    <code class="mc-code">{{ aff.code_mission }}</code>
                                    <span class="mc-role-badge" :class="'rb-'+aff.mon_role">
                                        <i class="ti ti-user-circle"></i>
                                        {{ aff.mon_role }} — {{ aff.role_libelle }}
                                    </span>
                                </div>
                                <span class="mc-status" :class="'mcs-'+aff.status">
                                    {{ stLbl(aff.status) }}
                                </span>
                            </div>

                            <!-- Libellé -->
                            <h3 class="mc-title">{{ aff.libelle }}</h3>

                            <!-- Entité + dates -->
                            <div class="mc-period">
                                <div v-if="aff.entity_name" class="mcp-entity">
                                    <i class="ti ti-building"></i>
                                    {{ aff.entity_name }}
                                </div>
                                <div class="mcp-dates">
                                    <i class="ti ti-calendar"></i>
                                    {{ aff.date_debut_fr }} → {{ aff.date_fin_fr }}
                                    <span class="mcp-dur">{{ aff.duree }}j</span>
                                </div>
                                <div v-if="aff.lieux" class="mcp-lieu">
                                    <i class="ti ti-map-pin"></i>
                                    {{ aff.lieux }}
                                </div>
                            </div>

                            <!-- Auditeurs affectés -->
                            <div class="mc-team" v-if="getEquipe(aff.mission_id).length">
                                <span class="mct-label">Équipe</span>
                                <div class="mct-avatars">
                                    <div v-for="m in getEquipe(aff.mission_id).slice(0,5)" :key="m.auditeur_id"
                                        class="mct-av" :class="'av-'+m.role"
                                        :title="m.last_name+' '+m.first_name+' ('+m.role+')'">
                                        {{ initials(m.last_name, m.first_name) }}
                                        <span v-if="m.is_me" class="mct-me-dot"></span>
                                    </div>
                                    <div v-if="getEquipe(aff.mission_id).length > 5" class="mct-av mct-more">
                                        +{{ getEquipe(aff.mission_id).length - 5 }}
                                    </div>
                                </div>
                                <div class="mct-names-tooltip">
                                    <div v-for="m in getEquipe(aff.mission_id)" :key="m.auditeur_id"
                                        class="mct-member" :class="'av-'+m.role">
                                        <span class="mct-mn-av">{{ initials(m.last_name, m.first_name) }}</span>
                                        <span class="mct-mn-name">{{ m.last_name }} {{ m.first_name }}</span>
                                        <span class="mct-mn-role" :class="'rb-'+m.role">{{ m.role }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Progression -->
                            <div class="mc-prog">
                                <div class="mcp-bar">
                                    <div class="mcp-fill" :style="'width:'+aff.progression+'%'"
                                        :class="progClass(aff.progression, aff.status)"></div>
                                </div>
                                <span class="mcp-pct">{{ aff.progression }}%</span>
                            </div>

                            <!-- Footer -->
                            <div class="mc-foot">
                                <div v-if="aff.budget_individuel > 0" class="mcf-budget">
                                    <i class="ti ti-coin"></i>
                                    {{ formatBudget(aff.budget_individuel) }} FCFA
                                </div>
                                <div class="mcf-actions">
                                    <button class="mca-btn" @click.stop="openGantt(aff)" title="Voir Gantt">
                                        <i class="ti ti-chart-gantt"></i>
                                    </button>
                                    <a :href="phasesUrl(aff.mission_id)" class="mca-btn mca-primary"
                                        @click.stop title="Voir les phases">
                                        <i class="ti ti-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="!filteredAffectations.length" class="empty-state">
                        <i class="ti ti-clipboard-off"></i>
                        <p>Aucune mission trouvée</p>
                        <span>Modifiez vos filtres pour afficher plus de résultats</span>
                    </div>
                </div>

                <!-- Vue Liste -->
                <div v-if="viewMode==='list'" class="list-wrap">
                    <table class="list-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Mission</th>
                                <th>Mon Rôle</th>
                                <th>Entité</th>
                                <th>Période</th>
                                <th>Équipe</th>
                                <th>Avancement</th>
                                <th>Statut</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="aff in filteredAffectations" :key="aff.id"
                                class="lt-row" @click="openGantt(aff)">
                                <td>
                                    <code class="lt-code">{{ aff.code_mission }}</code>
                                </td>
                                <td>
                                    <div class="lt-lib">{{ aff.libelle }}</div>
                                    <div v-if="aff.objectif" class="lt-obj">{{ aff.objectif }}</div>
                                </td>
                                <td>
                                    <span class="mc-role-badge" :class="'rb-'+aff.mon_role">
                                        {{ aff.mon_role }}
                                    </span>
                                </td>
                                <td>
                                    <span v-if="aff.entity_name" class="lt-ent">
                                        <i class="ti ti-building"></i>
                                        {{ aff.entity_name }}
                                    </span>
                                    <span v-else class="lt-muted">—</span>
                                </td>
                                <td class="lt-dates">
                                    <span>{{ aff.date_debut_fr }}</span>
                                    <i class="ti ti-arrow-right lt-arr"></i>
                                    <span>{{ aff.date_fin_fr }}</span>
                                    <span class="lt-dur">{{ aff.duree }}j</span>
                                </td>
                                <!-- Auditeurs inline -->
                                <td>
                                    <div class="lt-team">
                                        <div v-for="m in getEquipe(aff.mission_id).slice(0,4)" :key="m.auditeur_id"
                                            class="mct-av mct-av-sm" :class="'av-'+m.role"
                                            :title="m.last_name+' '+m.first_name+' ('+m.role+')'">
                                            {{ initials(m.last_name, m.first_name) }}
                                        </div>
                                        <div v-if="getEquipe(aff.mission_id).length > 4" class="mct-av mct-av-sm mct-more">
                                            +{{ getEquipe(aff.mission_id).length - 4 }}
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="lt-prog-wrap">
                                        <div class="lt-prog-bar">
                                            <div class="lt-prog-fill" :style="'width:'+aff.progression+'%'"
                                                :class="progClass(aff.progression, aff.status)"></div>
                                        </div>
                                        <span class="lt-pct">{{ aff.progression }}%</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="mc-status" :class="'mcs-'+aff.status">{{ stLbl(aff.status) }}</span>
                                </td>
                                <td @click.stop>
                                    <div style="display:flex;gap:4px;">
                                        <button class="mca-btn" @click="openGantt(aff)"><i class="ti ti-chart-gantt"></i></button>
                                        <a :href="phasesUrl(aff.mission_id)" class="mca-btn mca-primary"><i class="ti ti-arrow-right"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!filteredAffectations.length">
                                <td colspan="9" class="empty-row">
                                    <i class="ti ti-clipboard-off"></i> Aucune mission
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div><!-- /mm-body -->

        </div><!-- /mm-shell -->

        <!-- ══ GANTT DRAWER ══ -->
        <Teleport to="body">
            <transition name="drawer">
                <div v-if="gantt.open" class="gantt-overlay" @click.self="closeGantt">
                    <div class="gantt-drawer">
                        <!-- Drawer header -->
                        <div class="gd-header">
                            <div class="gd-hd-left">
                                <code class="gd-code">{{ gantt.mission?.code_mission }}</code>
                                <h2 class="gd-title">{{ gantt.mission?.libelle }}</h2>
                                <div class="gd-meta">
                                    <span><i class="ti ti-calendar"></i> {{ gantt.mission?.date_debut_fr }} → {{ gantt.mission?.date_fin_fr }}</span>
                                    <span v-if="gantt.mission?.lieux"><i class="ti ti-map-pin"></i> {{ gantt.mission?.lieux }}</span>
                                    <span v-if="gantt.mission?.duree_totale"><i class="ti ti-clock"></i> {{ gantt.mission?.duree_totale }} jours</span>
                                </div>
                            </div>
                            <div class="gd-hd-right">
                                <a :href="phasesUrl(gantt.missionId)" class="gd-btn-phases">
                                    <i class="ti ti-list-check"></i> Voir les phases
                                </a>
                                <button @click="closeGantt" class="gd-close">
                                    <i class="ti ti-x"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Loading -->
                        <div v-if="gantt.loading" class="gd-loading">
                            <div class="gd-spin"></div>
                            <p>Chargement du Gantt…</p>
                        </div>

                        <template v-else-if="gantt.data">
                            <!-- Équipe -->
                            <div class="gd-equipe">
                                <span class="gde-label"><i class="ti ti-users"></i> Équipe</span>
                                <div class="gde-list">
                                    <div v-for="m in gantt.data.equipe" :key="m.auditeur_id"
                                        class="gde-member" :class="'av-'+m.role">
                                        <div class="gde-av" :class="'av-'+m.role">
                                            {{ initials(m.last_name, m.first_name) }}
                                            <span v-if="m.is_me" class="gde-me">Moi</span>
                                        </div>
                                        <div class="gde-info">
                                            <span class="gde-name">{{ m.last_name }} {{ m.first_name }}</span>
                                            <span class="gde-role" :class="'rb-'+m.role">{{ m.role_libelle }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Stats globales -->
                            <div class="gd-stats">
                                <div v-for="s in ganttStats" :key="s.k" class="gds-item" :class="'gds-'+s.k">
                                    <span class="gds-n">{{ s.v }}</span>
                                    <span class="gds-l">{{ s.l }}</span>
                                </div>
                            </div>

                            <!-- Gantt timeline -->
                            <div class="gd-gantt-wrap">
                                <!--
                                    CORRECTION : clé/couleur/icône basées sur phase_num
                                    (numérique, stable, 1..5) au lieu de phase_type (chaîne
                                    figée côté ancien backend). Le libellé affiché (grp.label)
                                    vient maintenant directement de ddmparam.audit_type_forms
                                    .phase_label — dynamique, exact pour chaque type d'audit.
                                -->
                                <div v-for="grp in gantt.data.phases_by_type" :key="grp.phase_num" class="gg-group">
                                    <div class="gg-group-head" :style="'border-left:3px solid '+ptColor(grp.phase_num)">
                                        <span class="gg-gh-icon">{{ ptIcon(grp.phase_num) }}</span>
                                        <span class="gg-gh-name" :style="'color:'+ptColor(grp.phase_num)">{{ grp.label }}</span>
                                        <div class="gg-gh-stats">
                                            <span class="ggs-done">{{ grp.stats.completed }} <i class="ti ti-check"></i></span>
                                            <span class="ggs-ip">{{ grp.stats.in_progress }} <i class="ti ti-loader-2"></i></span>
                                            <span class="ggs-pend">{{ grp.stats.pending }} <i class="ti ti-clock"></i></span>
                                        </div>
                                    </div>

                                    <div v-for="ph in grp.phases" :key="ph.assignment_id" class="gg-phase">
                                        <div class="gg-ph-meta">
                                            <div class="gg-ph-left">
                                                <code class="gg-code" :style="'color:'+ptColor(grp.phase_num)">{{ ph.code_full||ph.code }}</code>
                                                <span class="gg-label">{{ ph.label }}</span>
                                                <!-- Auditeur propriétaire (owner_name) -->
                                                <span v-if="ph.owner_name" class="gg-owner" :title="'Responsable: '+ph.owner_name">
                                                    <i class="ti ti-user-check"></i>{{ ph.owner_name }}
                                                </span>
                                            </div>
                                            <div class="gg-ph-right">
                                                <span class="gg-ph-dur" v-if="ph.planned_duration">{{ ph.planned_duration }}j</span>
                                                <span class="gg-ph-status" :class="'ps-'+ph.phase_status">
                                                    {{ phStatusLbl(ph.phase_status) }}
                                                </span>
                                                <span class="gg-ph-pct">{{ ph.progression }}%</span>
                                            </div>
                                        </div>

                                        <!-- Barre de progression visuelle -->
                                        <div class="gg-ph-bar-wrap">
                                            <div class="gg-ph-bar">
                                                <div class="gg-ph-fill"
                                                    :style="'width:'+ph.progression+'%;background:'+ptColor(grp.phase_num)"
                                                    :class="'ps-fill-'+ph.phase_status"></div>
                                            </div>
                                            <div class="gg-ph-dates" v-if="ph.planned_start||ph.planned_end">
                                                <span>{{ ph.planned_start_fr||'—' }}</span>
                                                <span>{{ ph.planned_end_fr||'—' }}</span>
                                            </div>
                                        </div>

                                        <!-- Tâches -->
                                        <div v-if="ph.tasks && ph.tasks.length" class="gg-tasks">
                                            <div v-for="t in ph.tasks" :key="t.task_id"
                                                class="gg-task" :class="'gt-'+t.task_status">
                                                <i :class="t.task_status==='done'?'ti ti-check-circle':'ti ti-circle-dashed'"></i>
                                                <span class="gt-lbl">{{ t.libelle }}</span>
                                                <span class="gt-who">{{ t.last_name }} {{ t.first_name }}</span>
                                            </div>
                                        </div>

                                        <!-- Entité -->
                                        <div v-if="ph.entity_name" class="gg-ph-ent">
                                            <i class="ti ti-building"></i> {{ ph.entity_name }}
                                        </div>
                                    </div>
                                </div>

                                <div v-if="!gantt.data.phases_by_type?.length" class="gd-empty">
                                    <i class="ti ti-chart-gantt"></i>
                                    <p>Aucune phase affectée</p>
                                </div>
                            </div>
                        </template>

                        <div v-else-if="gantt.error" class="gd-error">
                            <i class="ti ti-alert-circle"></i>
                            <p>{{ gantt.error }}</p>
                            <button @click="loadGantt(gantt.missionId)">Réessayer</button>
                        </div>
                    </div>
                </div>
            </transition>
        </Teleport>

    </VerticalLayout>
</template>

<script setup lang="ts">
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { computed, ref, reactive } from 'vue';

const props = defineProps({
    auditor:             { type: Object, default: () => ({}) },
    affectations:        { type: Array,  default: () => [] },
    affectationEntities: { type: Array,  default: () => [] },
    equipesParMission:   { type: Object, default: () => ({}) },
    budgetLignes:        { type: Object, default: () => ({}) },
    stats:               { type: Object, default: () => ({}) },
});

// ── STATE ──────────────────────────────────────────────────────────────────
const search   = ref('');
const fStatus  = ref('');
const viewMode = ref<'cards'|'list'>('cards');

const statusFilters = [
    { v: '',          l: 'Toutes'   },
    { v: 'en_cours',  l: 'En cours' },
    { v: 'planifiee', l: 'Planifiée'},
    { v: 'terminee',  l: 'Terminée' },
    { v: 'annulee',   l: 'Annulée'  },
];

const gantt = reactive({
    open:       false,
    loading:    false,
    missionId:  null as number|null,
    mission:    null as any,
    data:       null as any,
    error:      null as string|null,
});

// ── COMPUTED ───────────────────────────────────────────────────────────────
const kpiCards = computed(() => [
    { key: 'total',    icon: 'ti ti-clipboard-list', val: props.stats.mes_missions ?? 0,     lbl: 'Missions'    },
    { key: 'encours',  icon: 'ti ti-loader-2',        val: props.stats.en_cours ?? 0,         lbl: 'En cours'    },
    { key: 'planif',   icon: 'ti ti-calendar-clock',  val: props.stats.planifiees ?? 0,       lbl: 'Planifiées'  },
    { key: 'done',     icon: 'ti ti-circle-check',    val: props.stats.terminees ?? 0,        lbl: 'Terminées'   },
    { key: 'jours',    icon: 'ti ti-clock',           val: props.stats.jours_total ?? 0,      lbl: 'Jours-audit' },
    { key: 'budget',   icon: 'ti ti-coin',            val: formatBudget(props.stats.budget_total ?? 0), lbl: 'Budget FCFA' },
]);

const filteredAffectations = computed(() => {
    const q = search.value.trim().toLowerCase();
    return (props.affectationEntities as any[]).filter(a => {
        const mq = !q || [a.code_mission, a.libelle, a.entity_name].some(s => String(s || '').toLowerCase().includes(q));
        return mq && (!fStatus.value || a.status === fStatus.value);
    });
});

const ganttStats = computed(() => {
    if (!gantt.data) return [];
    const s = gantt.data.stats ?? {};
    return [
        { k:'total', v: s.total_phases ?? 0,    l: 'Phases'    },
        { k:'done',  v: s.completed ?? 0,        l: 'Terminées' },
        { k:'ip',    v: s.in_progress ?? 0,      l: 'En cours'  },
        { k:'pend',  v: s.pending ?? 0,          l: 'En attente'},
        { k:'moy',   v: (s.progression_moy??0)+'%', l: 'Avancement'},
    ];
});

// ── METHODS ────────────────────────────────────────────────────────────────
function getEquipe(missionId: number): any[] {
    return (props.equipesParMission as any)[missionId]?.membres ?? [];
}

function countByStatus(s: string) {
    if (!s) return (props.affectationEntities as any[]).length;
    return (props.affectationEntities as any[]).filter(a => a.status === s).length;
}

function openGantt(aff: any) {
    gantt.open      = true;
    gantt.missionId = aff.mission_id;
    gantt.mission   = aff;
    gantt.data      = null;
    gantt.error     = null;
    loadGantt(aff.mission_id);
}

function closeGantt() { gantt.open = false; }

async function loadGantt(missionId: number | null) {
    if (!missionId) return;
    gantt.loading = true;
    gantt.error   = null;
    try {
        // CORRECTION : /api/auditor/... n'existe pas — la route réelle est
        // GET /m/audit.core/auditor/missions/{id}/gantt (cf. routes/web.php).
        const res  = await fetch(`/m/audit.core/auditor/missions/${missionId}/gantt`, {
            headers: { Accept: 'application/json' }
        });
        const json = await res.json();
        if (!res.ok) throw new Error(json?.error || 'Erreur chargement');
        gantt.data    = json;
        gantt.mission = json.mission;
    } catch (e: any) {
        gantt.error = e.message;
    } finally {
        gantt.loading = false;
    }
}

function phasesUrl(missionId: number | null) {
    return missionId ? `/m/audit.core/auditor/missions/${missionId}/phases` : '#';
}

function initials(last: string, first: string) {
    return ((last?.[0] ?? '') + (first?.[0] ?? '')).toUpperCase() || '?';
}

function stLbl(s: string) {
    return ({ planifiee: 'Planifiée', en_cours: 'En cours', terminee: 'Terminée', annulee: 'Annulée' } as any)[s] || s;
}

function phStatusLbl(s: string) {
    return ({ pending: 'À faire', in_progress: 'En cours', completed: 'Terminé', skipped: 'Ignorée' } as any)[s] || s;
}

function progClass(p: number, status: string) {
    if (status === 'terminee') return 'pf-done';
    if (status === 'annulee')  return 'pf-cancel';
    if (p >= 75) return 'pf-high';
    if (p >= 40) return 'pf-mid';
    return 'pf-low';
}

// ── Style des groupes de phases ──────────────────────────────────────────
// Clé numérique stable (phase_num, 1..5, vient de ddmparam.audit_type_forms).
// C'est un choix de PRÉSENTATION (couleur/icône), volontairement indépendant
// du libellé affiché (grp.label), qui lui vient dynamiquement de la base et
// varie selon le type d'audit.
function ptColor(n: number) {
    return ({ 1: '#7C3AED', 2: '#0369A1', 3: '#059669', 4: '#D97706', 5: '#db2777' } as any)[n] || '#64748B';
}

function ptIcon(n: number) {
    return ({ 1: '⚙', 2: '🔍', 3: '📋', 4: '📊', 5: '💡' } as any)[n] || '•';
}

function formatBudget(v: number) {
    if (!v) return '0';
    return new Intl.NumberFormat('fr-FR').format(Math.round(v));
}
</script>

<style scoped>
/* ══ IMPORTS ══ */
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,600;0,9..40,800;1,9..40,400&family=JetBrains+Mono:wght@400;700&display=swap');

* { box-sizing: border-box; }

.mm-shell {
    font-family: 'DM Sans', sans-serif;
    min-height: calc(100vh - 68px);
    background: #060c1a;
    color: #e2e8f0;
    overflow-x: hidden;
}

/* ══ HERO ══ */
.mm-hero {
    position: relative;
    overflow: hidden;
    padding: 28px 28px 24px;
    background: linear-gradient(135deg, #0a1628 0%, #0f1e3a 40%, #0d1527 100%);
    border-bottom: 1px solid rgba(255,255,255,.06);
}

.hero-bg-mesh {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 60% 80% at 80% 50%, rgba(37,99,235,.12) 0%, transparent 70%),
        radial-gradient(ellipse 40% 60% at 20% 80%, rgba(124,58,237,.08) 0%, transparent 60%);
    pointer-events: none;
}

.hero-content {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    flex-wrap: wrap;
}

.hero-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.hero-avatar {
    position: relative;
    width: 64px;
    height: 64px;
    border-radius: 18px;
    overflow: hidden;
    border: 2px solid rgba(37,99,235,.5);
    box-shadow: 0 0 0 4px rgba(37,99,235,.1), 0 8px 24px rgba(0,0,0,.4);
    flex-shrink: 0;
    background: #1e3a5f;
    display: flex;
    align-items: center;
    justify-content: center;
}

.hero-avatar img { width: 100%; height: 100%; object-fit: cover; }
.av-initials { font-size: 1.4rem; font-weight: 800; color: #60a5fa; }
.av-status-dot {
    position: absolute;
    bottom: 4px;
    right: 4px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #10b981;
    border: 2px solid #0a1628;
}

.hero-info { display: flex; flex-direction: column; gap: 4px; }
.hi-role { font-size: .65rem; text-transform: uppercase; letter-spacing: .12em; color: #60a5fa; font-weight: 700; margin: 0; }
.hi-name { font-size: 1.4rem; font-weight: 800; color: #f1f5f9; margin: 0; letter-spacing: -.02em; }
.hi-meta { display: flex; gap: 6px; flex-wrap: wrap; }
.hm-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: .68rem;
    color: #94a3b8;
    background: rgba(255,255,255,.05);
    border: 1px solid rgba(255,255,255,.08);
    padding: 2px 8px;
    border-radius: 20px;
}
.hm-code { font-family: 'JetBrains Mono', monospace; color: #7dd3fc; }

/* KPIs */
.hero-kpis {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.kpi-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 12px;
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.07);
    backdrop-filter: blur(10px);
    min-width: 90px;
    transition: background .15s;
}
.kpi-card:hover { background: rgba(255,255,255,.07); }

.kpi-icon { font-size: 1.1rem; opacity: .7; }
.kpi-body { display: flex; flex-direction: column; }
.kpi-val { font-size: 1.1rem; font-weight: 800; color: #f1f5f9; line-height: 1.1; }
.kpi-lbl { font-size: .6rem; text-transform: uppercase; letter-spacing: .08em; color: #64748b; }

.kpi-total  .kpi-icon { color: #60a5fa; }
.kpi-encours .kpi-icon { color: #fbbf24; }
.kpi-planif  .kpi-icon { color: #a78bfa; }
.kpi-done   .kpi-icon { color: #34d399; }
.kpi-jours  .kpi-icon { color: #f472b6; }
.kpi-budget .kpi-icon { color: #4ade80; }

/* ══ TOOLBAR ══ */
.mm-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 10px 20px;
    background: #080e1c;
    border-bottom: 1px solid rgba(255,255,255,.05);
    flex-wrap: wrap;
    /* CORRECTION : `sticky: top` n'est pas du CSS valide */
    position: sticky;
    top: 0;
    z-index: 20;
}

.tb-left { display: flex; align-items: center; gap: 10px; }
.tb-count {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: .75rem;
    color: #64748b;
    white-space: nowrap;
}
.tb-count strong { color: #e2e8f0; font-size: .9rem; }
.tb-count i { color: #2563eb; }

.tb-search {
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 8px;
    padding: 0 10px;
    min-width: 200px;
    transition: border-color .15s;
}
.tb-search:focus-within { border-color: rgba(37,99,235,.5); }
.tb-search input {
    background: none;
    border: none;
    color: #e2e8f0;
    font-size: .76rem;
    padding: 6px 0;
    flex: 1;
    outline: none;
}
.tb-search input::placeholder { color: #475569; }
.tb-search i { color: #475569; font-size: .78rem; }
.sr-clear { background: none; border: none; color: #475569; cursor: pointer; padding: 2px; }
.sr-clear:hover { color: #e2e8f0; }

.tb-right { display: flex; align-items: center; gap: 10px; }

.tb-filters { display: flex; gap: 4px; }

.sf-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 11px;
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,.07);
    background: rgba(255,255,255,.03);
    color: #64748b;
    font-size: .68rem;
    font-weight: 700;
    cursor: pointer;
    transition: all .15s;
    white-space: nowrap;
}
.sf-btn:hover { border-color: rgba(37,99,235,.4); color: #93c5fd; }
.sf-btn.active { background: rgba(37,99,235,.15); border-color: rgba(37,99,235,.4); color: #93c5fd; }
.sf-btn.sf-en_cours.active  { background: rgba(251,191,36,.1); border-color: rgba(251,191,36,.3); color: #fbbf24; }
.sf-btn.sf-planifiee.active { background: rgba(167,139,250,.1); border-color: rgba(167,139,250,.3); color: #a78bfa; }
.sf-btn.sf-terminee.active  { background: rgba(52,211,153,.1); border-color: rgba(52,211,153,.3); color: #34d399; }
.sf-btn.sf-annulee.active   { background: rgba(248,113,113,.1); border-color: rgba(248,113,113,.3); color: #f87171; }

.sf-dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
.sf-cnt {
    font-size: .56rem;
    background: rgba(255,255,255,.08);
    padding: 1px 5px;
    border-radius: 10px;
}

.tb-views { display: flex; gap: 2px; }
.tb-views button {
    width: 30px;
    height: 30px;
    border-radius: 6px;
    border: 1px solid rgba(255,255,255,.07);
    background: transparent;
    color: #475569;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all .12s;
}
.tb-views button:hover { color: #e2e8f0; background: rgba(255,255,255,.05); }
.tb-views button.active { background: rgba(37,99,235,.15); color: #60a5fa; border-color: rgba(37,99,235,.3); }

/* ══ BODY ══ */
.mm-body { padding: 16px 20px; overflow-y: auto; }

/* ── Grille cartes ── */
.cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 14px;
}

.m-card {
    position: relative;
    background: #0d1627;
    border: 1px solid rgba(255,255,255,.06);
    border-radius: 14px;
    overflow: hidden;
    cursor: pointer;
    transition: transform .18s, border-color .18s, box-shadow .18s;
    display: flex;
}
.m-card:hover {
    transform: translateY(-3px);
    border-color: rgba(37,99,235,.3);
    box-shadow: 0 12px 40px rgba(0,0,0,.4), 0 0 0 1px rgba(37,99,235,.1);
}

.mc-stripe {
    width: 4px;
    flex-shrink: 0;
}
.stripe-planifiee { background: linear-gradient(180deg, #a78bfa, #7c3aed); }
.stripe-en_cours  { background: linear-gradient(180deg, #fbbf24, #d97706); }
.stripe-terminee  { background: linear-gradient(180deg, #34d399, #059669); }
.stripe-annulee   { background: linear-gradient(180deg, #f87171, #dc2626); }

.mc-inner { flex: 1; padding: 14px 15px; display: flex; flex-direction: column; gap: 10px; }

.mc-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
.mc-head-left { display: flex; flex-direction: column; gap: 5px; }

.mc-code {
    font-family: 'JetBrains Mono', monospace;
    font-size: .67rem;
    font-weight: 700;
    color: #60a5fa;
    background: rgba(37,99,235,.12);
    padding: 2px 7px;
    border-radius: 4px;
    width: fit-content;
}

.mc-role-badge {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: .6rem;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 8px;
    width: fit-content;
}
.rb-DM { background: rgba(251,191,36,.15); color: #fbbf24; border: 1px solid rgba(251,191,36,.2); }
.rb-CM { background: rgba(96,165,250,.15); color: #60a5fa; border: 1px solid rgba(96,165,250,.2); }
.rb-AS { background: rgba(52,211,153,.15); color: #34d399; border: 1px solid rgba(52,211,153,.2); }
.rb-AJ { background: rgba(167,139,250,.15); color: #a78bfa; border: 1px solid rgba(167,139,250,.2); }

.mc-status {
    font-size: .58rem;
    font-weight: 800;
    padding: 2px 8px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: .06em;
    white-space: nowrap;
    flex-shrink: 0;
}
.mcs-planifiee { background: rgba(167,139,250,.15); color: #a78bfa; }
.mcs-en_cours  { background: rgba(251,191,36,.15);  color: #fbbf24; }
.mcs-terminee  { background: rgba(52,211,153,.15);  color: #34d399; }
.mcs-annulee   { background: rgba(248,113,113,.15); color: #f87171; }

.mc-title {
    font-size: .85rem;
    font-weight: 700;
    color: #e2e8f0;
    margin: 0;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.mc-period { display: flex; flex-direction: column; gap: 4px; }
.mcp-entity, .mcp-dates, .mcp-lieu {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: .68rem;
    color: #64748b;
}
.mcp-entity i { color: #475569; }
.mcp-dates i  { color: #475569; }
.mcp-lieu i   { color: #475569; }
.mcp-dur {
    font-size: .6rem;
    font-weight: 700;
    background: rgba(124,58,237,.15);
    color: #a78bfa;
    padding: 1px 6px;
    border-radius: 10px;
    margin-left: 4px;
}

/* Team avatars */
.mc-team { display: flex; align-items: center; gap: 8px; position: relative; }
.mct-label { font-size: .57rem; text-transform: uppercase; letter-spacing: .08em; color: #475569; font-weight: 700; flex-shrink: 0; }
.mct-avatars { display: flex; align-items: center; }

.mct-av {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .52rem;
    font-weight: 800;
    border: 2px solid #060c1a;
    margin-left: -5px;
    cursor: default;
    position: relative;
    flex-shrink: 0;
    transition: transform .12s;
}
.mct-av:first-child { margin-left: 0; }
.mct-av:hover { transform: scale(1.2); z-index: 2; }
.mct-av-sm { width: 20px; height: 20px; font-size: .48rem; }

.av-DM { background: rgba(251,191,36,.2); color: #fbbf24; }
.av-CM { background: rgba(96,165,250,.2); color: #60a5fa; }
.av-AS { background: rgba(52,211,153,.2); color: #34d399; }
.av-AJ { background: rgba(167,139,250,.2); color: #a78bfa; }
.mct-more { background: rgba(255,255,255,.06); color: #64748b; }
.mct-me-dot {
    position: absolute;
    bottom: -1px;
    right: -1px;
    width: 6px;
    height: 6px;
    background: #10b981;
    border-radius: 50%;
    border: 1px solid #060c1a;
}

/* Tooltip noms */
.mct-names-tooltip {
    position: absolute;
    left: 0;
    top: calc(100% + 6px);
    background: #0f1e3a;
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 8px;
    padding: 8px;
    display: none;
    flex-direction: column;
    gap: 5px;
    min-width: 200px;
    z-index: 50;
    box-shadow: 0 8px 30px rgba(0,0,0,.5);
}
.mc-team:hover .mct-names-tooltip { display: flex; }
.mct-member { display: flex; align-items: center; gap: 7px; }
.mct-mn-av { width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .5rem; font-weight: 800; flex-shrink: 0; }
.mct-mn-name { font-size: .7rem; color: #cbd5e1; flex: 1; }
.mct-mn-role { font-size: .55rem; font-weight: 800; padding: 1px 5px; border-radius: 6px; }

/* Progression */
.mc-prog { display: flex; align-items: center; gap: 8px; }
.mcp-bar { flex: 1; height: 4px; background: rgba(255,255,255,.06); border-radius: 3px; overflow: hidden; }
.mcp-fill { height: 100%; border-radius: 3px; transition: width .3s; }
.pf-done   { background: linear-gradient(90deg, #059669, #34d399); }
.pf-high   { background: linear-gradient(90deg, #2563eb, #60a5fa); }
.pf-mid    { background: linear-gradient(90deg, #d97706, #fbbf24); }
.pf-low    { background: linear-gradient(90deg, #475569, #64748b); }
.pf-cancel { background: #dc2626; }
.mcp-pct { font-size: .65rem; font-weight: 800; color: #94a3b8; white-space: nowrap; }

/* Footer carte */
.mc-foot { display: flex; align-items: center; justify-content: space-between; }
.mcf-budget { font-size: .65rem; color: #4ade80; display: flex; align-items: center; gap: 4px; }
.mcf-actions { display: flex; gap: 5px; }
.mca-btn {
    width: 28px;
    height: 28px;
    border-radius: 7px;
    border: 1px solid rgba(255,255,255,.07);
    background: rgba(255,255,255,.04);
    color: #64748b;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    text-decoration: none;
    font-size: .8rem;
    transition: all .12s;
}
.mca-btn:hover { background: rgba(255,255,255,.1); color: #e2e8f0; }
.mca-primary { background: rgba(37,99,235,.15); border-color: rgba(37,99,235,.3); color: #60a5fa; }
.mca-primary:hover { background: #2563eb; color: #fff; }

/* Empty */
.empty-state {
    grid-column: 1/-1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 60px 20px;
    color: #334155;
}
.empty-state i { font-size: 2rem; }
.empty-state p { font-size: .9rem; font-weight: 600; color: #475569; margin: 0; }
.empty-state span { font-size: .76rem; }

/* ── Liste ── */
.list-wrap { overflow-x: auto; }
.list-table { width: 100%; border-collapse: collapse; font-size: .75rem; }
.list-table thead th {
    padding: 8px 10px;
    background: #0a1220;
    font-size: .58rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #475569;
    border-bottom: 1px solid rgba(255,255,255,.05);
    white-space: nowrap;
    text-align: left;
}
.lt-row { border-bottom: 1px solid rgba(255,255,255,.04); cursor: pointer; transition: background .1s; }
.lt-row:hover td { background: rgba(37,99,235,.05); }
.lt-row td { padding: 8px 10px; vertical-align: middle; color: #cbd5e1; }
.lt-code { font-family: 'JetBrains Mono', monospace; font-size: .67rem; color: #60a5fa; }
.lt-lib { font-weight: 600; color: #e2e8f0; }
.lt-obj { font-size: .67rem; color: #475569; margin-top: 2px; }
.lt-ent { display: flex; align-items: center; gap: 4px; font-size: .7rem; }
.lt-ent i { color: #475569; }
.lt-muted { color: #334155; }
.lt-dates { display: flex; align-items: center; gap: 5px; font-size: .68rem; white-space: nowrap; font-family: 'JetBrains Mono', monospace; }
.lt-arr { color: #334155; font-size: .6rem; }
.lt-dur { font-size: .58rem; background: rgba(124,58,237,.15); color: #a78bfa; padding: 1px 5px; border-radius: 8px; }
.lt-team { display: flex; align-items: center; }
.lt-prog-wrap { display: flex; align-items: center; gap: 6px; min-width: 90px; }
.lt-prog-bar { flex: 1; height: 4px; background: rgba(255,255,255,.06); border-radius: 3px; overflow: hidden; }
.lt-prog-fill { height: 100%; border-radius: 3px; }
.lt-pct { font-size: .62rem; color: #64748b; white-space: nowrap; }
.empty-row { text-align: center; padding: 40px; color: #334155; font-size: .8rem; }

/* ══ GANTT DRAWER ══ */
.gantt-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.7);
    backdrop-filter: blur(6px);
    z-index: 1000;
    display: flex;
    align-items: stretch;
    justify-content: flex-end;
}

.gantt-drawer {
    width: min(560px, 96vw);
    background: #080f1f;
    border-left: 1px solid rgba(255,255,255,.07);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.drawer-enter-active, .drawer-leave-active { transition: all .25s cubic-bezier(.4,0,.2,1); }
.drawer-enter-from, .drawer-leave-to { opacity: 0; transform: translateX(40px); }

/* Drawer header */
.gd-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
    padding: 18px 18px 14px;
    background: linear-gradient(135deg, #0a1628, #0d1a30);
    border-bottom: 1px solid rgba(255,255,255,.06);
    flex-shrink: 0;
}
.gd-hd-left { display: flex; flex-direction: column; gap: 5px; flex: 1; min-width: 0; }
.gd-code {
    font-family: 'JetBrains Mono', monospace;
    font-size: .67rem;
    color: #60a5fa;
    background: rgba(37,99,235,.12);
    padding: 2px 7px;
    border-radius: 4px;
    width: fit-content;
}
.gd-title { font-size: 1rem; font-weight: 800; color: #f1f5f9; margin: 0; line-height: 1.3; }
.gd-meta { display: flex; flex-wrap: wrap; gap: 8px; }
.gd-meta span { display: flex; align-items: center; gap: 4px; font-size: .67rem; color: #64748b; }
.gd-meta i { font-size: .7rem; }

.gd-hd-right { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
.gd-btn-phases {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 7px;
    background: rgba(37,99,235,.15);
    border: 1px solid rgba(37,99,235,.3);
    color: #60a5fa;
    font-size: .7rem;
    font-weight: 700;
    text-decoration: none;
    transition: all .12s;
}
.gd-btn-phases:hover { background: #2563eb; color: #fff; }
.gd-close {
    width: 30px;
    height: 30px;
    border-radius: 7px;
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.07);
    color: #475569;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: .8rem;
}
.gd-close:hover { color: #e2e8f0; background: rgba(255,255,255,.08); }

/* Loading / error */
.gd-loading, .gd-error {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    color: #475569;
}
.gd-spin {
    width: 28px;
    height: 28px;
    border: 3px solid rgba(37,99,235,.2);
    border-top-color: #2563eb;
    border-radius: 50%;
    animation: spin .7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.gd-error i { font-size: 1.5rem; color: #dc2626; }
.gd-error button {
    padding: 5px 14px;
    border-radius: 6px;
    background: rgba(37,99,235,.15);
    border: 1px solid rgba(37,99,235,.3);
    color: #60a5fa;
    cursor: pointer;
}

/* Équipe drawer */
.gd-equipe {
    padding: 12px 18px;
    border-bottom: 1px solid rgba(255,255,255,.05);
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    flex-shrink: 0;
}
.gde-label { font-size: .6rem; text-transform: uppercase; letter-spacing: .08em; color: #475569; font-weight: 700; flex-shrink: 0; }
.gde-list { display: flex; flex-wrap: wrap; gap: 6px; }
.gde-member {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 3px 8px 3px 4px;
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,.07);
    background: rgba(255,255,255,.03);
}
.gde-av {
    position: relative;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .5rem;
    font-weight: 800;
}
.gde-me {
    position: absolute;
    top: -4px;
    right: -4px;
    font-size: .42rem;
    background: #10b981;
    color: #fff;
    padding: 0 3px;
    border-radius: 4px;
    font-weight: 800;
    white-space: nowrap;
}
.gde-info { display: flex; flex-direction: column; }
.gde-name { font-size: .65rem; font-weight: 600; color: #cbd5e1; }
.gde-role { font-size: .55rem; font-weight: 700; padding: 0 4px; border-radius: 4px; }

/* Stats drawer */
.gd-stats {
    display: flex;
    gap: 0;
    border-bottom: 1px solid rgba(255,255,255,.05);
    flex-shrink: 0;
}
.gds-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 8px 4px;
    border-right: 1px solid rgba(255,255,255,.04);
}
.gds-item:last-child { border-right: none; }
.gds-n { font-size: .9rem; font-weight: 800; color: #e2e8f0; }
.gds-l { font-size: .55rem; text-transform: uppercase; letter-spacing: .06em; color: #475569; }
.gds-done .gds-n { color: #34d399; }
.gds-ip .gds-n { color: #fbbf24; }
.gds-moy .gds-n { color: #60a5fa; }

/* Gantt timeline */
.gd-gantt-wrap { flex: 1; overflow-y: auto; padding: 10px 0; }
.gd-gantt-wrap::-webkit-scrollbar { width: 3px; }
.gd-gantt-wrap::-webkit-scrollbar-thumb { background: rgba(255,255,255,.08); border-radius: 3px; }

.gg-group { margin-bottom: 4px; }
.gg-group-head {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 7px 18px;
    background: rgba(255,255,255,.02);
    border-bottom: 1px solid rgba(255,255,255,.04);
    position: sticky;
    top: 0;
    z-index: 2;
}
.gg-gh-icon { font-size: .8rem; }
.gg-gh-name { flex: 1; font-size: .72rem; font-weight: 800; letter-spacing: .02em; }
.gg-gh-stats { display: flex; gap: 8px; font-size: .6rem; }
.ggs-done { color: #34d399; }
.ggs-ip { color: #fbbf24; }
.ggs-pend { color: #475569; }

.gg-phase {
    padding: 10px 18px 8px;
    border-bottom: 1px solid rgba(255,255,255,.03);
    transition: background .1s;
}
.gg-phase:hover { background: rgba(255,255,255,.02); }

.gg-ph-meta { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 6px; }
.gg-ph-left { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; min-width: 0; flex: 1; }
.gg-code {
    font-family: 'JetBrains Mono', monospace;
    font-size: .6rem;
    background: rgba(255,255,255,.05);
    padding: 1px 5px;
    border-radius: 3px;
    flex-shrink: 0;
}
.gg-label { font-size: .76rem; font-weight: 600; color: #e2e8f0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.gg-owner { font-size: .6rem; color: #64748b; display: flex; align-items: center; gap: 3px; flex-shrink: 0; }

.gg-ph-right { display: flex; align-items: center; gap: 5px; flex-shrink: 0; }
.gg-ph-dur { font-size: .6rem; color: #a78bfa; background: rgba(124,58,237,.1); padding: 1px 5px; border-radius: 8px; }
.gg-ph-status {
    font-size: .58rem;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 8px;
}
.ps-pending    { background: rgba(71,85,105,.2); color: #64748b; }
.ps-in_progress{ background: rgba(251,191,36,.1); color: #fbbf24; }
.ps-completed  { background: rgba(52,211,153,.1); color: #34d399; }
.ps-skipped    { background: rgba(100,116,139,.1); color: #475569; }
.gg-ph-pct { font-size: .62rem; font-weight: 800; color: #94a3b8; }

.gg-ph-bar-wrap { margin-bottom: 5px; }
.gg-ph-bar { height: 3px; background: rgba(255,255,255,.06); border-radius: 3px; overflow: hidden; margin-bottom: 3px; }
.gg-ph-fill { height: 100%; border-radius: 3px; transition: width .4s; opacity: .85; }
.ps-fill-completed { opacity: 1; filter: brightness(1.2); }
.ps-fill-skipped { opacity: .3; }
.gg-ph-dates { display: flex; justify-content: space-between; font-size: .58rem; font-family: 'JetBrains Mono', monospace; color: #334155; }

/* Tâches */
.gg-tasks { display: flex; flex-direction: column; gap: 3px; margin-top: 5px; }
.gg-task {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 3px 6px;
    background: rgba(255,255,255,.025);
    border-radius: 5px;
    font-size: .65rem;
}
.gg-task i { font-size: .7rem; flex-shrink: 0; }
.gt-done i { color: #34d399; }
.gt-pending i, .gt-todo i { color: #334155; }
.gt-lbl { flex: 1; color: #94a3b8; }
.gt-who { font-size: .58rem; color: #475569; }

.gg-ph-ent { font-size: .6rem; color: #334155; display: flex; align-items: center; gap: 4px; margin-top: 3px; }
.gg-ph-ent i { font-size: .6rem; }

.gd-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 40px 20px;
    color: #334155;
}
.gd-empty i { font-size: 2rem; }
</style>