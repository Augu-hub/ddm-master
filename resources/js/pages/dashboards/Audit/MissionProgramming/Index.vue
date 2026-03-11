<template>
    <VerticalLayout>
        <Head title="Programmation des Missions" />

        <div class="pm-wrap">

            <!-- ══ HEADER ══ -->
            <div class="pm-header">
                <div class="pm-header-left">
                    <div class="pm-header-icon"><i class="ti ti-calendar-event"></i></div>
                    <div>
                        <h1 class="pm-title">Programmation des Missions</h1>
                        <p class="pm-sub">Planification stratégique · Suivi opérationnel des missions d'audit</p>
                    </div>
                </div>
                <div class="pm-header-actions">

                    <!-- ══ EXPORT BUTTONS ══ -->
                    <div class="export-group">
                        <span class="export-label"><i class="ti ti-download"></i> Exporter</span>

                        <!-- Sélecteur année -->
                        <select v-model="exportYear" class="year-select" title="Année d'export">
                            <option v-for="y in exportYears" :key="y" :value="y">{{ y }}</option>
                        </select>

                        <!-- Export Excel : toutes entités -->
                        <button class="pm-btn-export pm-btn-excel" @click="exportExcel" :disabled="isExporting">
                            <i class="ti ti-file-spreadsheet"></i>
                            <span>{{ isExporting ? 'Export…' : 'Excel (Toutes entités)' }}</span>
                        </button>

                        <!-- Export PDF -->
                        <button class="pm-btn-export pm-btn-pdf" @click="exportPDF">
                            <i class="ti ti-file-type-pdf"></i><span>PDF</span>
                        </button>
                    </div>

                    <Link
                        :href="route('audit.core.programmation-missions.create', { entity_id: selectedEntityId })"
                        class="pm-btn-new"
                    >
                        <i class="ti ti-plus"></i><span>Nouvelle programmation</span>
                    </Link>
                </div>
            </div>

            <!-- ══ TOOLBAR ══ -->
            <div class="pm-toolbar">
                <div class="entity-selector" v-if="entities && entities.length">
                    <i class="ti ti-building entity-icon"></i>
                    <select v-model.number="selectedEntityId" @change="reloadPage" class="entity-select">
                        <option :value="null">Toutes les entités</option>
                        <option v-for="e in entities" :key="e.id" :value="e.id">{{ e.name }}</option>
                    </select>
                </div>
                <div class="pm-stats-inline">
                    <div class="psi-item"><span class="psi-val psi-total-val">{{ stats?.total || 0 }}</span><span class="psi-lbl">Total</span></div>
                    <div class="psi-sep"></div>
                    <div class="psi-item"><span class="psi-dot dot-plan"></span><span class="psi-val">{{ stats?.planifiees || 0 }}</span><span class="psi-lbl">Planifiées</span></div>
                    <div class="psi-sep"></div>
                    <div class="psi-item"><span class="psi-dot dot-enc"></span><span class="psi-val">{{ stats?.en_cours || 0 }}</span><span class="psi-lbl">En cours</span></div>
                    <div class="psi-sep"></div>
                    <div class="psi-item"><span class="psi-dot dot-term"></span><span class="psi-val">{{ stats?.terminees || 0 }}</span><span class="psi-lbl">Terminées</span></div>
                    <div class="psi-sep"></div>
                    <div class="psi-item"><i class="ti ti-users" style="font-size:.62rem;color:#94a3b8;"></i><span class="psi-val">{{ totalAuditeurs }}</span><span class="psi-lbl">Auditeurs</span></div>
                    <div class="psi-sep"></div>
                    <div class="psi-item"><i class="ti ti-coin" style="font-size:.62rem;color:#94a3b8;"></i><span class="psi-val">{{ fmtShort(totalBudget) }}</span><span class="psi-lbl">Budget</span></div>
                </div>
            </div>

            <!-- ══ FILTRES ══ -->
            <div class="pm-filters">
                <div class="pm-search">
                    <i class="ti ti-search"></i>
                    <input v-model="localSearch" placeholder="Code, libellé, FPM, entité…" />
                    <button v-if="localSearch" @click="localSearch=''" class="clear-btn"><i class="ti ti-x"></i></button>
                </div>
                <select v-model="localStatus" class="pm-select">
                    <option value="">Tous statuts</option>
                    <option value="planifiee">Planifiée</option>
                    <option value="en_cours">En cours</option>
                    <option value="terminee">Terminée</option>
                    <option value="annulee">Annulée</option>
                </select>
                <div class="pm-date-range">
                    <input type="date" v-model="localDateDebut" class="pm-date-input" />
                    <span class="date-arrow"><i class="ti ti-arrow-right"></i></span>
                    <input type="date" v-model="localDateFin" class="pm-date-input" />
                </div>
                <button @click="applyFilters" class="pm-btn-apply"><i class="ti ti-filter"></i> Filtrer</button>
                <button @click="resetFilters" class="pm-btn-reset"><i class="ti ti-refresh"></i> Reset</button>
                <span class="pm-count-result">{{ filteredMissions.length }} résultat(s)</span>
            </div>

            <!-- ══ TABLEAU ══ -->
            <div class="pm-table-wrap" id="pm-print-area">
                <table class="pm-table">
                    <thead>
                        <tr>
                            <th style="width:92px;">Code</th>
                            <th style="width:62px;">FPM</th>
                            <th>Libellé / Objectif</th>
                            <th style="width:90px;">Entité</th>
                            <th style="width:85px;">Début</th>
                            <th style="width:85px;">Fin</th>
                            <th style="width:38px;" class="tc">Jrs</th>
                            <th style="width:70px;">Lieu</th>
                            <th style="width:115px;">Budget</th>
                            <th style="width:210px;">Équipe affectée</th>
                            <th style="width:74px;" class="tc">Statut</th>
                            <th style="width:58px;" class="tc">Avance</th>
                            <th style="width:72px;" class="tc no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-if="filteredMissions.length === 0">
                            <tr><td colspan="13"><div class="pm-empty"><i class="ti ti-folder-off"></i><span>Aucune mission programmée</span></div></td></tr>
                        </template>

                        <template v-else v-for="mission in filteredMissions" :key="mission.id">
                            <tr class="pm-row" :class="{ 'pm-row-expanded': expandedId === mission.id }" @click="toggleExpand(mission.id)">

                                <td><span class="code-chip">{{ mission.code_mission }}</span></td>
                                <td><span v-if="mission.numero_fpm" class="fpm-chip">{{ mission.numero_fpm }}</span><span v-else class="nd">—</span></td>

                                <td>
                                    <div class="mission-name">{{ mission.libelle }}</div>
                                    <div v-if="mission.objectif" class="mission-obj">{{ truncate(mission.objectif, 52) }}</div>
                                </td>

                                <td><span class="entite-txt">{{ mission.entities_list || '—' }}</span></td>
                                <td><span class="date-txt">{{ mission.date_debut_fr }}</span></td>
                                <td><span class="date-txt">{{ mission.date_fin_fr }}</span></td>
                                <td class="tc"><span class="dur-chip">{{ mission.duree || '—' }}</span></td>
                                <td><span class="lieu-txt" :title="mission.lieux">{{ truncate(mission.lieux || '—', 10) }}</span></td>

                                <td>
                                    <div class="budget-cell">
                                        <span class="budget-val">{{ fmtShort(mission.budget_total) }}</span>
                                        <span class="budget-unit">FCFA</span>
                                    </div>
                                    <div v-if="(budgetLignesParMission[mission.id] || []).length" class="budget-hint">
                                        {{ (budgetLignesParMission[mission.id] || []).length }} ligne(s) variable
                                    </div>
                                </td>

                                <!-- ÉQUIPE -->
                                <td class="team-td">
                                    <div class="team-inline">
                                        <template v-if="getEquipe(mission.id).length === 0">
                                            <span class="no-team">Non affectée</span>
                                        </template>
                                        <template v-else>
                                            <div class="team-chips">
                                                <div v-for="(m, i) in getEquipe(mission.id).slice(0, 4)" :key="i"
                                                    class="team-chip-mini" :class="'tc-' + (m.role || 'none')"
                                                    :title="m.last_name + ' ' + m.first_name + (m.role ? ' — ' + m.role : '')">
                                                    <span class="tca">{{ (m.last_name || '?').charAt(0) }}</span>
                                                    <span class="tcn">{{ m.last_name }}</span>
                                                    <span v-if="m.role" class="tcr">{{ m.role }}</span>
                                                </div>
                                                <div v-if="getEquipe(mission.id).length > 4" class="team-chip-more">
                                                    +{{ getEquipe(mission.id).length - 4 }}
                                                </div>
                                            </div>
                                            <div class="team-total"><i class="ti ti-users"></i> {{ getEquipe(mission.id).length }} membre(s)</div>
                                        </template>
                                    </div>
                                </td>

                                <td class="tc">
                                    <span class="status-chip" :class="'s-' + mission.status">{{ getStatusLabel(mission.status) }}</span>
                                </td>

                                <td class="tc">
                                    <div class="prog-cell">
                                        <div class="prog-bar-mini">
                                            <div class="prog-fill" :class="{ 'prog-done': mission.progression >= 100 }"
                                                :style="{ width: (mission.progression || 0) + '%' }"></div>
                                        </div>
                                        <span class="prog-pct">{{ mission.progression || 0 }}%</span>
                                    </div>
                                </td>

                                <td class="tc no-print" @click.stop>
                                    <div class="actions-cell">
                                        <Link :href="route('audit.core.programmation-missions.show', mission.id)" class="act-btn act-view" title="Détails"><i class="ti ti-eye"></i></Link>
                                        <button @click="openEdit(mission)" class="act-btn act-edit" title="Modifier"><i class="ti ti-edit"></i></button>
                                        <button @click="confirmDelete(mission)" class="act-btn act-del" title="Supprimer"><i class="ti ti-trash"></i></button>
                                    </div>
                                </td>
                            </tr>

                            <!-- EXPAND -->
                            <tr v-if="expandedId === mission.id" class="pm-expand-row">
                                <td colspan="13" class="pm-expand-td">
                                    <div class="expand-panel">
                                        <div class="exp-grid">

                                            <!-- ÉQUIPE DÉTAIL -->
                                            <div class="exp-col">
                                                <div class="exp-title">
                                                    <i class="ti ti-users me-1"></i>
                                                    Équipe affectée
                                                    <span class="exp-count">{{ getEquipe(mission.id).length }}</span>
                                                </div>
                                                <div v-if="getEquipe(mission.id).length === 0" class="exp-empty">Aucun auditeur affecté</div>
                                                <div v-else class="team-detail-list">
                                                    <div v-for="(membre, i) in getEquipe(mission.id)" :key="i"
                                                        class="team-detail-row"
                                                        :class="'tdr-' + (membre.role || 'none')">
                                                        <div class="tdr-avatar" :class="'av-' + (membre.role || 'none')">
                                                            {{ (membre.last_name || '?').charAt(0) }}
                                                        </div>
                                                        <div class="tdr-info">
                                                            <div class="tdr-name">{{ membre.last_name }} {{ membre.first_name }}</div>
                                                            <div class="tdr-code">{{ membre.audit_code }}</div>
                                                        </div>
                                                        <span v-if="membre.role" class="tdr-role" :class="'role-' + membre.role">{{ membre.role }}</span>
                                                        <span v-if="membre.role_libelle && membre.role_libelle !== membre.role" class="tdr-role-lib">{{ membre.role_libelle }}</span>
                                                        <span v-if="membre.budget_individuel > 0" class="tdr-budget">
                                                            {{ fmtShort(membre.budget_individuel) }} FCFA
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- BUDGET DETAIL -->
                                            <div class="exp-col">
                                                <div class="exp-title"><i class="ti ti-coin me-1"></i>Budget</div>
                                                <div class="budget-expand-rows">
                                                    <div class="ber-row">
                                                        <span class="ber-lbl">Fixe</span>
                                                        <span class="ber-val">{{ fmtBudget(mission.montant_fixe) }} FCFA</span>
                                                    </div>
                                                    <div v-for="(bl, bi) in (budgetLignesParMission[mission.id] || [])" :key="bi" class="ber-row ber-var">
                                                        <span class="ber-dot"></span>
                                                        <span class="ber-lbl">{{ bl.libelle }}</span>
                                                        <span class="ber-val">{{ fmtBudget(bl.montant) }} FCFA</span>
                                                    </div>
                                                    <div class="ber-row ber-total">
                                                        <span class="ber-lbl">Total</span>
                                                        <span class="ber-val">{{ fmtBudget(mission.budget_total) }} FCFA</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- STATUT + OBJECTIF -->
                                            <div class="exp-col">
                                                <div class="exp-title"><i class="ti ti-settings me-1"></i>Changer le statut</div>
                                                <div class="status-actions">
                                                    <button v-for="s in statusOptions" :key="s.val"
                                                        @click="changeStatus(mission.id, s.val)"
                                                        :disabled="mission.status === s.val"
                                                        class="st-btn"
                                                        :class="['st-' + s.val, { 'st-active': mission.status === s.val }]">
                                                        <i :class="s.icon"></i> {{ s.label }}
                                                    </button>
                                                </div>
                                                <div v-if="mission.objectif" style="margin-top:8px;">
                                                    <div class="exp-title"><i class="ti ti-target me-1"></i>Objectif</div>
                                                    <div class="obj-text">{{ mission.objectif }}</div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <div v-if="pagination && pagination.last_page > 1" class="pm-pagination">
                <button :disabled="pagination.current_page <= 1" @click="goPage(pagination.current_page - 1)" class="pag-btn"><i class="ti ti-chevron-left"></i></button>
                <span class="pag-info">Page {{ pagination.current_page }} / {{ pagination.last_page }}</span>
                <button :disabled="pagination.current_page >= pagination.last_page" @click="goPage(pagination.current_page + 1)" class="pag-btn"><i class="ti ti-chevron-right"></i></button>
            </div>
        </div>
    </VerticalLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import VerticalLayout from '@/layouts/VerticalLayout.vue'

const props = defineProps({
    missions:                { type: Array,  default: () => [] },
    equipesParMission:       { type: Object, default: () => ({}) },
    budgetLignesParMission:  { type: Object, default: () => ({}) },
    stats:                   { type: Object, default: () => ({}) },
    entities:                { type: Array,  default: () => [] },
    filters:                 { type: Object, default: () => ({}) },
    selectedEntityId:        { type: Number, default: null },
    pagination:              { type: Object, default: null },
})

// ── État ──────────────────────────────────────────────────────────────────────
const selectedEntityId       = ref(props.selectedEntityId || null)
const missions               = ref(props.missions || [])
const equipesParMission      = ref(props.equipesParMission || {})
const budgetLignesParMission = ref(props.budgetLignesParMission || {})
const stats                  = ref(props.stats || {})
const expandedId             = ref(null)

// ── Filtres locaux ────────────────────────────────────────────────────────────
const localSearch    = ref(props.filters?.search     || '')
const localStatus    = ref(props.filters?.status     || '')
const localDateDebut = ref(props.filters?.date_debut || '')
const localDateFin   = ref(props.filters?.date_fin   || '')

// ── Export ────────────────────────────────────────────────────────────────────
const currentYear = new Date().getFullYear()
const exportYear  = ref(props.filters?.year ? parseInt(props.filters.year) : currentYear)
const isExporting = ref(false)

// Génère la liste des années disponibles (5 ans en arrière → année suivante)
const exportYears = computed(() => {
    const years = []
    for (let y = currentYear + 1; y >= currentYear - 4; y--) {
        years.push(y)
    }
    return years
})

/**
 * Export Excel — déclenche le téléchargement côté serveur.
 * Passe TOUS les filtres actifs + l'année sélectionnée.
 * Le contrôleur récupère TOUTES les entités (pas de filtre entity_id).
 */
const exportExcel = () => {
    if (isExporting.value) return
    isExporting.value = true

    const params = new URLSearchParams({
        year:       exportYear.value,
        search:     localSearch.value    || '',
        status:     localStatus.value    || '',
        date_debut: localDateDebut.value || '',
        date_fin:   localDateFin.value   || '',
        // NB : pas de entity_id → le contrôleur exporte TOUTES les entités
    })

    // Crée un lien temporaire pour déclencher le téléchargement
    const url  = route('audit.core.programmation-missions.export-excel') + '?' + params.toString()
    const link = document.createElement('a')
    link.href  = url
    link.setAttribute('download', `Plan_Audit_${exportYear.value}_Complet.xlsx`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)

    // Re-enable le bouton après 3 s (temps du téléchargement)
    setTimeout(() => { isExporting.value = false }, 3000)
}

// ── Filtrage client ───────────────────────────────────────────────────────────
const filteredMissions = computed(() => {
    let list = missions.value
    const t  = localSearch.value.trim().toLowerCase()
    if (t) {
        list = list.filter(m =>
            (m.code_mission  || '').toLowerCase().includes(t) ||
            (m.libelle       || '').toLowerCase().includes(t) ||
            (m.numero_fpm    || '').toLowerCase().includes(t) ||
            (m.entities_list || '').toLowerCase().includes(t) ||
            (m.lieux         || '').toLowerCase().includes(t)
        )
    }
    if (localStatus.value)    list = list.filter(m => m.status === localStatus.value)
    if (localDateDebut.value) list = list.filter(m => (m.date_debut || '') >= localDateDebut.value)
    if (localDateFin.value)   list = list.filter(m => (m.date_fin   || '') <= localDateFin.value)
    return list
})

// ── Stats ─────────────────────────────────────────────────────────────────────
const totalAuditeurs = computed(() => {
    let total = 0
    Object.values(equipesParMission.value || {}).forEach(eq => {
        if (eq?.membres) total += eq.membres.length
    })
    return total
})
const totalBudget = computed(() =>
    missions.value.reduce((s, m) => s + (parseFloat(m.budget_total) || 0), 0)
)

// ── Helpers équipe ────────────────────────────────────────────────────────────
const getEquipe = (missionId) => {
    const eq = equipesParMission.value[missionId]
    if (!eq) return []
    if (selectedEntityId.value) {
        return eq.membres.filter(m => {
            let entites = m.entites
            if (typeof entites === 'string') {
                try { entites = JSON.parse(entites) } catch { entites = [] }
            }
            if (!Array.isArray(entites)) entites = entites ? [entites] : []
            return entites.includes(selectedEntityId.value)
        })
    }
    return eq.membres
}

const getEntityName = (entityId) => {
    const ent = props.entities.find(e => e.id === entityId)
    return ent ? ent.name : '—'
}

// ── Actions serveur ───────────────────────────────────────────────────────────
const applyFilters = () => {
    router.get(route('audit.core.programmation-missions.index'), {
        entity_id:  selectedEntityId.value,
        search:     localSearch.value,
        status:     localStatus.value,
        date_debut: localDateDebut.value,
        date_fin:   localDateFin.value,
    }, { preserveState: true, preserveScroll: true })
}

const resetFilters = () => {
    localSearch.value = ''; localStatus.value = ''
    localDateDebut.value = ''; localDateFin.value = ''
    applyFilters()
}

const reloadPage = () => {
    router.get(route('audit.core.programmation-missions.index'), { entity_id: selectedEntityId.value })
}

const toggleExpand = (id)    => { expandedId.value = expandedId.value === id ? null : id }

const changeStatus = (id, status) => {
    router.patch(route('audit.core.programmation-missions.updateStatus', id), { status }, {
        preserveScroll: true,
        onSuccess: () => { const m = missions.value.find(x => x.id === id); if (m) m.status = status }
    })
}
const openEdit      = (mission) => router.get(route('audit.core.programmation-missions.edit', mission.id))
const confirmDelete = (mission) => {
    if (confirm(`Supprimer la mission "${mission.code_mission}" ?`))
        router.delete(route('audit.core.programmation-missions.destroy', mission.id))
}
const goPage = (page) => router.get(route('audit.core.programmation-missions.index'), {
    entity_id: selectedEntityId.value, page,
    search: localSearch.value, status: localStatus.value,
    date_debut: localDateDebut.value, date_fin: localDateFin.value,
})

// ── Export PDF (côté client, fenêtre d'impression) ───────────────────────────
const exportPDF = () => {
    const entityLabel = props.entities?.find(e => e.id === selectedEntityId.value)?.name || 'Toutes entités'
    const date        = new Date().toLocaleDateString('fr-FR')
    const style = `<style>
        *{font-family:Arial,sans-serif;font-size:9px;}
        body{margin:14px;color:#1e293b;}
        h2{font-size:12px;font-weight:bold;margin-bottom:3px;}
        p{font-size:8px;color:#64748b;margin:0 0 8px;}
        table{width:100%;border-collapse:collapse;}
        th{background:#0f172a;color:rgba(255,255,255,.8);padding:4px 6px;font-size:7.5px;text-align:left;text-transform:uppercase;}
        td{padding:4px 6px;border-bottom:1px solid #f1f5f9;vertical-align:top;}
        tr:nth-child(even) td{background:#f8fafc;}
        .badge{display:inline-block;padding:1px 5px;border-radius:8px;font-size:7px;font-weight:bold;}
        .s-planifiee{background:#fef3c7;color:#d97706;}
        .s-en_cours{background:#dbeafe;color:#1e40af;}
        .s-terminee{background:#d1fae5;color:#059669;}
        .s-annulee{background:#fee2e2;color:#dc2626;}
    </style>`
    let html = `<html><head>${style}</head><body>`
    html += `<h2>Programmation des Missions — ${entityLabel}</h2>`
    html += `<p>Édité le ${date} · ${filteredMissions.value.length} mission(s)</p>`
    html += `<table><thead><tr>
        <th>Code</th><th>FPM</th><th>Libellé</th><th>Entité</th>
        <th>Période</th><th>Jrs</th><th>Budget</th><th>Statut</th><th>%</th>
    </tr></thead><tbody>`
    filteredMissions.value.forEach(m => {
        html += `<tr>
            <td><strong>${m.code_mission}</strong></td>
            <td>${m.numero_fpm || '—'}</td>
            <td><strong>${m.libelle}</strong>${m.objectif ? `<br><small style="color:#94a3b8;">${truncate(m.objectif, 60)}</small>` : ''}</td>
            <td>${m.entities_list || '—'}</td>
            <td style="white-space:nowrap;">${m.date_debut_fr} → ${m.date_fin_fr}</td>
            <td style="text-align:center;">${m.duree || '—'}</td>
            <td><strong>${fmtBudget(m.budget_total)}</strong> FCFA</td>
            <td><span class="badge s-${m.status}">${getStatusLabel(m.status)}</span></td>
            <td style="text-align:center;">${m.progression || 0}%</td>
        </tr>`
    })
    html += `</tbody></table></body></html>`
    const win = window.open('', '_blank')
    win.document.write(html)
    win.document.close()
    win.focus()
    setTimeout(() => win.print(), 400)
}

// ── Utilitaires ───────────────────────────────────────────────────────────────
const getStatusLabel = (s) => ({ planifiee:'Planifiée', en_cours:'En cours', terminee:'Terminée', annulee:'Annulée' }[s] || s)
const fmtBudget = (v) => (!v ? '0' : Number(v).toLocaleString('fr-FR'))
const fmtShort  = (v) => {
    if (!v) return '0'
    const n = Number(v)
    if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M'
    if (n >= 1000)    return (n / 1000).toFixed(0)    + 'k'
    return n.toLocaleString('fr-FR')
}
const truncate = (s, l) => (!s ? '—' : s.length > l ? s.slice(0, l) + '…' : s)

const statusOptions = [
    { val:'planifiee', label:'Planifiée', icon:'ti ti-clock'  },
    { val:'en_cours',  label:'En cours',  icon:'ti ti-loader' },
    { val:'terminee',  label:'Terminée',  icon:'ti ti-check'  },
    { val:'annulee',   label:'Annulée',   icon:'ti ti-ban'    },
]
</script>


<style scoped>
/* ─── WRAP ────────────────────────────────────────────────────────────────── */
.pm-wrap { padding:12px 14px; background:#f1f5f9; min-height:100vh; }

/* ─── HEADER ─────────────────────────────────────────────────────────────── */
.pm-header { display:flex;align-items:center;justify-content:space-between;background:#fff;border-radius:8px;padding:9px 14px;margin-bottom:7px;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,.05); }
.pm-header-left { display:flex;align-items:center;gap:10px; }
.pm-header-icon { width:34px;height:34px;background:linear-gradient(135deg,#1e40af,#3b82f6);border-radius:7px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.95rem;flex-shrink:0; }
.pm-title { font-size:.85rem;font-weight:700;color:#0f172a;margin:0;line-height:1.2; }
.pm-sub   { font-size:.62rem;color:#94a3b8;margin:0; }
.pm-header-actions { display:flex;align-items:center;gap:6px;flex-wrap:wrap; }

/* ─── EXPORT GROUP ──────────────────────────────────────────────────────── */
.export-group {
    display:flex;align-items:center;gap:4px;
    background:#f8fafc;border:1px solid #e2e8f0;border-radius:7px;
    padding:3px 6px;
}
.export-label {
    font-size:.62rem;font-weight:600;color:#94a3b8;
    display:flex;align-items:center;gap:3px;padding-right:4px;
    border-right:1px solid #e2e8f0;margin-right:2px;
}
.year-select {
    height:22px;font-size:.65rem;border:1px solid #e2e8f0;
    border-radius:4px;padding:0 4px;background:#fff;color:#1e293b;
    outline:none;cursor:pointer;
}
.pm-btn-export {
    display:inline-flex;align-items:center;gap:4px;height:24px;padding:0 9px;
    border:1px solid #e2e8f0;border-radius:5px;background:#fff;
    font-size:.65rem;font-weight:600;color:#475569;cursor:pointer;transition:all .15s;
}
.pm-btn-export:hover:not(:disabled) { background:#f1f5f9;border-color:#cbd5e1; }
.pm-btn-export:disabled { opacity:.5;cursor:not-allowed; }

/* Excel : vert discret */
.pm-btn-excel { border-color:#bbf7d0;color:#059669; }
.pm-btn-excel:hover:not(:disabled) { background:#d1fae5;border-color:#6ee7b7; }
.pm-btn-excel:disabled { opacity:.5;cursor:not-allowed; }

/* PDF : rouge discret */
.pm-btn-pdf { border-color:#fecaca;color:#dc2626; }
.pm-btn-pdf:hover { background:#fee2e2;border-color:#fca5a5; }

/* Nouvelle programmation */
.pm-btn-new { display:inline-flex;align-items:center;gap:4px;background:#1e40af;color:#fff;border:none;border-radius:6px;padding:0 12px;height:27px;font-size:.7rem;font-weight:600;text-decoration:none;cursor:pointer;transition:background .15s; }
.pm-btn-new:hover { background:#1e3a8a;color:#fff; }

/* ─── TOOLBAR ─────────────────────────────────────────────────────────────── */
.pm-toolbar { display:flex;align-items:center;gap:10px;background:#fff;border-radius:8px;padding:5px 12px;margin-bottom:7px;border:1px solid #e2e8f0;flex-wrap:wrap; }
.entity-selector { display:flex;align-items:center;gap:5px; }
.entity-icon { font-size:.72rem;color:#1e40af; }
.entity-select { height:23px;font-size:.68rem;border:1px solid #e2e8f0;border-radius:5px;padding:0 6px;background:#f8fafc;color:#1e293b;outline:none;min-width:140px; }
.pm-stats-inline { display:flex;align-items:center;gap:7px; }
.psi-sep  { width:1px;height:13px;background:#e2e8f0; }
.psi-item { display:flex;align-items:center;gap:3px; }
.psi-dot  { width:5px;height:5px;border-radius:50%;flex-shrink:0; }
.dot-plan { background:#d97706; } .dot-enc { background:#1e40af; } .dot-term { background:#059669; }
.psi-val       { font-size:.7rem;font-weight:700;color:#0f172a; }
.psi-total-val { font-size:.76rem;color:#1e40af; }
.psi-lbl       { font-size:.58rem;color:#94a3b8; }

/* ─── FILTRES ─────────────────────────────────────────────────────────────── */
.pm-filters { display:flex;align-items:center;gap:5px;background:#fff;border-radius:8px;padding:5px 12px;margin-bottom:7px;border:1px solid #e2e8f0;flex-wrap:wrap; }
.pm-search { display:flex;align-items:center;gap:4px;height:23px;padding:0 7px;border:1px solid #e2e8f0;border-radius:5px;background:#f8fafc;flex:1;min-width:150px; }
.pm-search i { font-size:.7rem;color:#94a3b8;flex-shrink:0; }
.pm-search input { border:none;outline:none;background:transparent;font-size:.68rem;flex:1;color:#1e293b; }
.clear-btn { background:transparent;border:none;cursor:pointer;color:#94a3b8;display:flex;align-items:center;font-size:.6rem; }
.clear-btn:hover { color:#dc2626; }
.pm-select { height:23px;font-size:.68rem;border:1px solid #e2e8f0;border-radius:5px;padding:0 5px;background:#f8fafc;color:#1e293b;outline:none; }
.pm-date-range { display:flex;align-items:center;gap:3px; }
.pm-date-input { height:23px;font-size:.63rem;border:1px solid #e2e8f0;border-radius:5px;padding:0 5px;background:#f8fafc;color:#1e293b;outline:none;width:106px; }
.date-arrow { font-size:.58rem;color:#94a3b8; }
.pm-btn-apply { display:inline-flex;align-items:center;gap:3px;height:23px;padding:0 9px;background:#1e40af;color:#fff;border:none;border-radius:5px;font-size:.66rem;font-weight:600;cursor:pointer; }
.pm-btn-apply:hover { background:#1e3a8a; }
.pm-btn-reset { display:inline-flex;align-items:center;gap:3px;height:23px;padding:0 9px;border:1px solid #e2e8f0;border-radius:5px;background:transparent;font-size:.66rem;color:#64748b;cursor:pointer; }
.pm-btn-reset:hover { background:#f1f5f9; }
.pm-count-result { font-size:.6rem;color:#94a3b8;margin-left:auto;white-space:nowrap; }

/* ─── TABLEAU ─────────────────────────────────────────────────────────────── */
.pm-table-wrap { background:#fff;border-radius:8px;border:1px solid #e2e8f0;overflow-x:auto;overflow-y:auto;max-height:calc(100vh - 255px);scrollbar-width:thin;scrollbar-color:#cbd5e1 transparent; }
.pm-table-wrap::-webkit-scrollbar { width:4px;height:4px; }
.pm-table-wrap::-webkit-scrollbar-thumb { background:#cbd5e1;border-radius:10px; }
.pm-table-wrap::-webkit-scrollbar-thumb:hover { background:#94a3b8; }

.pm-table { width:100%;border-collapse:collapse;min-width:940px; }
.pm-table thead tr { background:#0f172a;position:sticky;top:0;z-index:10; }
.pm-table th { padding:6px 8px;font-size:.57rem;font-weight:700;color:rgba(255,255,255,.65);text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;border-right:1px solid rgba(255,255,255,.06); }
.pm-table th:last-child { border-right:none; }
.pm-row { cursor:pointer;border-bottom:1px solid #f1f5f9;transition:background .1s; }
.pm-row:hover { background:#f8fafc; }
.pm-row.pm-row-expanded { background:#eff6ff;border-bottom:none; }
.pm-table td { padding:5px 8px;vertical-align:middle;font-size:.68rem;color:#334155; }

/* Chips */
.code-chip  { background:#dbeafe;color:#1e40af;font-size:.62rem;font-weight:700;padding:2px 6px;border-radius:4px;font-family:monospace;white-space:nowrap; }
.fpm-chip   { background:#f1f5f9;color:#475569;font-size:.58rem;font-weight:600;padding:2px 5px;border-radius:4px;font-family:monospace; }
.nd         { color:#cbd5e1;font-size:.6rem; }
.mission-name { font-size:.7rem;font-weight:600;color:#0f172a;line-height:1.3; }
.mission-obj  { font-size:.59rem;color:#94a3b8;line-height:1.3;margin-top:1px; }
.entite-txt   { font-size:.63rem;color:#475569;font-weight:500; }
.date-txt     { font-size:.63rem;color:#475569;white-space:nowrap;font-family:monospace; }
.lieu-txt     { font-size:.62rem;color:#64748b; }
.dur-chip     { display:inline-flex;align-items:center;justify-content:center;width:24px;height:15px;background:#f1f5f9;color:#475569;font-size:.6rem;font-weight:700;border-radius:3px; }
.budget-cell  { display:flex;align-items:baseline;gap:2px;white-space:nowrap; }
.budget-val   { font-size:.68rem;font-weight:700;color:#1e293b;font-family:monospace; }
.budget-unit  { font-size:.5rem;color:#94a3b8; }
.budget-hint  { font-size:.56rem;color:#94a3b8;margin-top:1px; }

/* Équipe colonne */
.team-td { padding:4px 8px !important; }
.team-inline { display:flex;flex-direction:column;gap:2px; }
.no-team { font-size:.6rem;color:#cbd5e1;font-style:italic; }
.team-chips { display:flex;flex-wrap:wrap;gap:2px; }
.team-chip-mini { display:inline-flex;align-items:center;gap:2px;height:15px;padding:0 4px;border-radius:3px;font-size:.56rem;border:1px solid transparent;background:#f1f5f9; }
.tca { width:11px;height:11px;border-radius:50%;background:#dbeafe;color:#1e40af;font-size:.48rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.tcn { font-weight:600;color:#334155;max-width:46px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.tcr { font-size:.5rem;font-weight:700;padding:1px 3px;border-radius:2px;background:#1e40af;color:#fff; }
.tc-DM { background:#fff0f0;border-color:#fecaca; } .tc-DM .tca{background:#fee2e2;color:#dc2626;} .tc-DM .tcr{background:#dc2626;}
.tc-CM { background:#fffbeb;border-color:#fde68a; } .tc-CM .tca{background:#fef3c7;color:#d97706;} .tc-CM .tcr{background:#d97706;}
.tc-AS { background:#f0fdf4;border-color:#6ee7b7; } .tc-AS .tca{background:#d1fae5;color:#059669;} .tc-AS .tcr{background:#059669;}
.tc-AJ { background:#eff6ff;border-color:#93c5fd; } .tc-AJ .tca{background:#dbeafe;color:#1d4ed8;} .tc-AJ .tcr{background:#1d4ed8;}
.team-chip-more { height:15px;padding:0 4px;border-radius:3px;background:#f1f5f9;color:#94a3b8;font-size:.56rem;font-weight:700;display:flex;align-items:center; }
.team-total { font-size:.56rem;color:#94a3b8;display:flex;align-items:center;gap:2px; }

/* Statut & progression */
.status-chip { display:inline-flex;align-items:center;justify-content:center;font-size:.55rem;font-weight:700;padding:2px 6px;border-radius:8px;text-transform:uppercase;letter-spacing:.03em;white-space:nowrap; }
.s-planifiee{background:#fef3c7;color:#d97706;} .s-en_cours{background:#dbeafe;color:#1e40af;} .s-terminee{background:#d1fae5;color:#059669;} .s-annulee{background:#fee2e2;color:#dc2626;}
.prog-cell { display:flex;flex-direction:column;align-items:center;gap:1px; }
.prog-bar-mini { width:40px;height:3px;background:#f1f5f9;border-radius:2px;overflow:hidden; }
.prog-fill { height:100%;background:#3b82f6;border-radius:2px;transition:width .3s; }
.prog-fill.prog-done { background:#059669; }
.prog-pct { font-size:.54rem;color:#94a3b8; }

/* Actions */
.actions-cell { display:flex;align-items:center;justify-content:center;gap:2px; }
.act-btn { width:19px;height:19px;border-radius:4px;border:none;background:transparent;color:#94a3b8;display:flex;align-items:center;justify-content:center;font-size:.7rem;cursor:pointer;text-decoration:none;transition:background .1s,color .1s; }
.act-view:hover{background:#dbeafe;color:#1e40af;} .act-edit:hover{background:#fef3c7;color:#d97706;} .act-del:hover{background:#fee2e2;color:#dc2626;}

.pm-empty { display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;padding:34px 20px;color:#94a3b8; }
.pm-empty i { font-size:2rem;opacity:.3; }
.pm-empty span { font-size:.76rem;font-weight:500; }

/* EXPAND */
.pm-expand-row { background:#f8fafc; }
.pm-expand-td  { padding:0 !important;border-bottom:2px solid #dbeafe !important; }
.expand-panel  { padding:9px 14px;border-top:1px solid #dbeafe;background:linear-gradient(135deg,#f0f7ff,#f8fafc); }
.exp-grid { display:grid;grid-template-columns:1fr 185px 185px;gap:11px;align-items:start; }
.exp-title { font-size:.58rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px;display:flex;align-items:center; }
.exp-count { background:#dbeafe;color:#1e40af;font-size:.56rem;font-weight:700;padding:1px 5px;border-radius:8px;margin-left:4px; }
.exp-empty { font-size:.63rem;color:#94a3b8;padding:4px 0;font-style:italic; }

/* Équipe détail */
.team-detail-list { display:flex;flex-direction:column;gap:2px;max-height:180px;overflow-y:auto;scrollbar-width:thin;scrollbar-color:#cbd5e1 transparent; }
.team-detail-list::-webkit-scrollbar { width:3px; }
.team-detail-list::-webkit-scrollbar-thumb { background:#cbd5e1;border-radius:10px; }
.team-detail-row { display:flex;align-items:center;gap:5px;padding:3px 6px;border-radius:4px;background:#fff;border:1px solid #f1f5f9; }
.tdr-DM{border-left:3px solid #dc2626;} .tdr-CM{border-left:3px solid #d97706;} .tdr-AS{border-left:3px solid #059669;} .tdr-AJ{border-left:3px solid #1d4ed8;} .tdr-none{border-left:3px solid #e2e8f0;}
.tdr-avatar { width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:700;flex-shrink:0; }
.av-DM{background:#fee2e2;color:#dc2626;} .av-CM{background:#fef3c7;color:#d97706;} .av-AS{background:#d1fae5;color:#059669;} .av-AJ{background:#dbeafe;color:#1d4ed8;} .av-none{background:#f1f5f9;color:#94a3b8;}
.tdr-info { flex:1;min-width:0; }
.tdr-name { font-size:.68rem;font-weight:600;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.tdr-code { font-size:.56rem;color:#94a3b8;font-family:monospace; }
.tdr-role { font-size:.56rem;font-weight:700;padding:1px 4px;border-radius:3px; }
.role-DM{background:#fee2e2;color:#dc2626;} .role-CM{background:#fef3c7;color:#d97706;} .role-AS{background:#d1fae5;color:#059669;} .role-AJ{background:#dbeafe;color:#1d4ed8;}
.tdr-role-lib { font-size:.56rem;color:#94a3b8; }
.tdr-budget   { font-size:.58rem;font-weight:600;color:#059669;white-space:nowrap;margin-left:auto; }

/* Budget expand */
.budget-expand-rows { display:flex;flex-direction:column;gap:3px; }
.ber-row { display:flex;align-items:center;gap:5px; }
.ber-lbl { font-size:.63rem;color:#94a3b8;flex:1; }
.ber-val { font-size:.63rem;font-weight:600;color:#1e293b;font-family:monospace;white-space:nowrap; }
.ber-var { padding-left:8px; }
.ber-dot { width:5px;height:5px;border-radius:50%;background:#8b5cf6;flex-shrink:0; }
.ber-total { border-top:1px solid #e2e8f0;padding-top:3px;margin-top:2px; }
.ber-total .ber-lbl{font-weight:700;color:#475569;} .ber-total .ber-val{color:#1e40af;font-weight:700;}

/* Statut changement */
.status-actions { display:flex;flex-direction:column;gap:3px; }
.st-btn { display:inline-flex;align-items:center;gap:3px;height:21px;padding:0 8px;border-radius:4px;border:1px solid #e2e8f0;background:#fff;font-size:.6rem;font-weight:500;color:#475569;cursor:pointer;transition:all .1s; }
.st-btn:hover:not(:disabled){background:#f1f5f9;} .st-btn:disabled{opacity:.4;cursor:not-allowed;}
.st-planifiee.st-active{background:#fef3c7;border-color:#fde68a;color:#d97706;font-weight:700;}
.st-en_cours.st-active{background:#dbeafe;border-color:#93c5fd;color:#1e40af;font-weight:700;}
.st-terminee.st-active{background:#d1fae5;border-color:#6ee7b7;color:#059669;font-weight:700;}
.st-annulee.st-active{background:#fee2e2;border-color:#fca5a5;color:#dc2626;font-weight:700;}

.obj-text { font-size:.63rem;color:#475569;line-height:1.5;background:#fff;border:1px solid #e2e8f0;border-radius:4px;padding:5px 7px;max-height:65px;overflow-y:auto;scrollbar-width:thin;scrollbar-color:#cbd5e1 transparent; }

/* Pagination */
.pm-pagination { display:flex;align-items:center;justify-content:center;gap:6px;padding:6px 0; }
.pag-btn { width:24px;height:24px;border-radius:5px;border:1px solid #e2e8f0;background:#fff;display:flex;align-items:center;justify-content:center;font-size:.7rem;cursor:pointer;color:#475569;transition:background .1s; }
.pag-btn:hover:not(:disabled){background:#dbeafe;color:#1e40af;} .pag-btn:disabled{opacity:.4;cursor:not-allowed;}
.pag-info { font-size:.68rem;color:#94a3b8; }

/* Utilitaires */
.tc { text-align:center !important; }
.me-1 { margin-right:3px; }

/* Print */
@media print {
    .no-print,.export-group{ display:none !important; }
    .pm-wrap{padding:0;background:#fff;}
    .pm-table-wrap{max-height:none;overflow:visible;border:none;}
}
</style>