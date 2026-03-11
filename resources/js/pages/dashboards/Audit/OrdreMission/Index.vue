<template>
    <div class="om-index">

        <!-- ─── EN-TÊTE ─── -->
        <div class="omi-hero">
            <div class="omi-hero-inner">
                <div>
                    <div class="omi-label">Module Audit · Cabinet KEKELI</div>
                    <h1 class="omi-title">📋 Ordres de Mission</h1>
                    <p class="omi-sub">Gestion, émission et envoi des ordres de mission aux entités auditées</p>
                </div>
                <a :href="route('audit.core.ordre-missions.create')" class="omi-btn-new">
                    ＋ Nouvel Ordre de Mission
                </a>
            </div>
        </div>

        <!-- ─── KPIs ─── -->
        <div class="omi-kpis">
            <div class="omi-kpi" v-for="kpi in kpiList" :key="kpi.label" :class="kpi.cls">
                <div class="omi-kpi-num">{{ kpi.value }}</div>
                <div class="omi-kpi-label">{{ kpi.label }}</div>
            </div>
        </div>

        <!-- ─── FILTRES ─── -->
        <div class="omi-filters">
            <div class="omi-search-wrap">
                <span class="omi-search-icon">🔍</span>
                <input v-model="search" type="text" class="omi-search"
                    placeholder="Rechercher par référence, intitulé, destinataire…"
                    @input="applyFilters" />
            </div>
            <select v-model="statusFilter" class="omi-select" @change="applyFilters">
                <option value="">Tous statuts</option>
                <option value="brouillon">Brouillon</option>
                <option value="emis">Émis</option>
                <option value="envoye">Envoyé</option>
                <option value="accuse">Accusé réception</option>
            </select>
        </div>

        <!-- ─── TABLE ─── -->
        <div class="omi-table-wrap">
            <table class="omi-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Référence OM</th>
                        <th>Intitulé Mission</th>
                        <th>Entités</th>
                        <th>Équipe</th>
                        <th>Période</th>
                        <th>Diffusion</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(om, idx) in ordres.data" :key="om.id" class="omi-tr">
                        <td class="omi-td-seq">{{ (ordres.current_page - 1) * ordres.per_page + idx + 1 }}</td>
                        <td>
                            <div class="omi-ref">{{ om.reference_om }}</div>
                            <div class="omi-phase" v-if="om.phase">{{ om.phase }}</div>
                        </td>
                        <td>
                            <div class="omi-intitule">{{ om.intitule }}</div>
                            <div class="omi-lieu" v-if="om.lieux">📍 {{ om.lieux }}</div>
                        </td>
                        <td>
                            <div class="omi-entities" v-if="om.entites_noms">
                                {{ om.entites_noms }}
                            </div>
                            <span v-else class="omi-none">—</span>
                        </td>
                        <td>
                            <div class="omi-team" v-if="om.auditeurs_liste">
                                <div v-for="line in om.auditeurs_liste.split('\n').slice(0,2)" :key="line" class="omi-team-line">
                                    {{ line }}
                                </div>
                                <span v-if="om.auditeurs_liste.split('\n').length > 2" class="omi-more">
                                    +{{ om.auditeurs_liste.split('\n').length - 2 }} autres
                                </span>
                            </div>
                            <span v-else class="omi-none">—</span>
                        </td>
                        <td>
                            <div v-if="om.date_debut_fr" class="omi-dates">
                                {{ om.date_debut_fr }}
                                <span v-if="om.date_fin_fr"> → {{ om.date_fin_fr }}</span>
                                <div class="omi-duree" v-if="om.duree">{{ om.duree }} j</div>
                            </div>
                            <span v-else class="omi-none">—</span>
                        </td>
                        <td>
                            <span class="omi-diffusion-badge" :class="'diff-' + om.forme_diffusion">
                                {{ diffusionLabel(om.forme_diffusion) }}
                            </span>
                        </td>
                        <td>
                            <span class="omi-status" :class="'st-' + om.status">
                                {{ statusLabel(om.status) }}
                            </span>
                        </td>
                        <td>
                            <div class="omi-actions">
                                <!-- Voir -->
                                <a :href="route('audit.core.ordre-missions.show', om.id)"
                                    class="omi-action-btn omi-view" title="Voir">👁</a>
                                <!-- Éditer -->
                                <a :href="route('audit.core.ordre-missions.edit', om.id)"
                                    class="omi-action-btn omi-edit" title="Modifier">✏️</a>
                                <!-- PDF -->
                                <a :href="route('audit.core.ordre-missions.pdf', om.id)"
                                    target="_blank" class="omi-action-btn omi-pdf" title="Télécharger PDF">📄</a>
                                <!-- Envoyer emails -->
                                <button v-if="om.forme_diffusion !== 'papier'"
                                    @click="sendEmails(om.id)"
                                    class="omi-action-btn omi-send"
                                    :class="{ 'omi-sent': om.status === 'envoye' }"
                                    title="Envoyer par email">
                                    {{ om.status === 'envoye' ? '✅' : '📧' }}
                                </button>
                                <!-- Supprimer -->
                                <button @click="confirmDelete(om)" class="omi-action-btn omi-del" title="Supprimer">🗑</button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="ordres.data.length === 0">
                        <td colspan="9" class="omi-empty">
                            <div class="omi-empty-inner">
                                <div style="font-size:40px;margin-bottom:10px">📋</div>
                                <strong>Aucun ordre de mission trouvé</strong>
                                <div class="omi-empty-sub">Créez votre premier OM en cliquant sur « Nouvel Ordre de Mission »</div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ─── PAGINATION ─── -->
        <div class="omi-pagination" v-if="ordres.last_page > 1">
            <button v-for="page in ordres.last_page" :key="page"
                @click="goToPage(page)"
                class="omi-page-btn"
                :class="{ active: page === ordres.current_page }">
                {{ page }}
            </button>
        </div>

        <!-- ─── MODAL CONFIRMATION SUPPRESSION ─── -->
        <Transition name="om-modal">
        <div v-if="deleteTarget" class="omi-modal-overlay" @click.self="deleteTarget = null">
            <div class="omi-modal">
                <div class="omi-modal-icon">⚠️</div>
                <h3>Confirmer la suppression</h3>
                <p>Voulez-vous supprimer l'OM <strong>{{ deleteTarget?.reference_om }}</strong> ?</p>
                <p class="omi-modal-sub">Cette action est irréversible.</p>
                <div class="omi-modal-actions">
                    <button @click="deleteTarget = null" class="omi-btn-cancel">Annuler</button>
                    <button @click="doDelete" class="omi-btn-confirm">Supprimer</button>
                </div>
            </div>
        </div>
        </Transition>

    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    ordres:  Object,
    stats:   Object,
    filters: Object,
})

const search       = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || '')
const deleteTarget = ref(null)

const kpiList = computed(() => [
    { label: 'Total OM',   value: props.stats.total,     cls: 'kpi-blue'   },
    { label: 'Brouillons', value: props.stats.brouillon, cls: 'kpi-yellow' },
    { label: 'Émis',       value: props.stats.emis,      cls: 'kpi-indigo' },
    { label: 'Envoyés',    value: props.stats.envoye,    cls: 'kpi-green'  },
])

function applyFilters() {
    router.get(route('audit.core.ordre-missions.index'), {
        search: search.value, status: statusFilter.value
    }, { preserveState: true, replace: true })
}

function goToPage(p) {
    router.get(route('audit.core.ordre-missions.index'), {
        search: search.value, status: statusFilter.value, page: p
    })
}

function sendEmails(id) {
    if (!confirm('Envoyer l\'OM par email à toutes les entités renseignées ?')) return
    router.post(route('audit.core.ordre-missions.send-emails', id), {}, {
        onSuccess: () => alert('Emails envoyés avec succès !')
    })
}

function confirmDelete(om) { deleteTarget.value = om }
function doDelete() {
    router.delete(route('audit.core.ordre-missions.destroy', deleteTarget.value.id), {
        onSuccess: () => { deleteTarget.value = null }
    })
}

function statusLabel(s) {
    return { brouillon:'Brouillon', emis:'Émis', envoye:'Envoyé', accuse:'Accusé' }[s] || s
}
function diffusionLabel(d) {
    return { electronique:'📧 Email', papier:'🖨️ Papier', les_deux:'📬 Les deux' }[d] || d
}
</script>

<style scoped>
.om-index { background: #F1F5F9; min-height: 100vh; padding-bottom: 40px; }

/* HERO */
.omi-hero { background: linear-gradient(135deg,#0F172A,#1E3A5F,#1E40AF); padding: 26px 32px 22px; }
.omi-hero-inner { display: flex; justify-content: space-between; align-items: center; max-width: 1400px; margin: 0 auto; }
.omi-label { font-size: 10px; color: #93C5FD; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 5px; }
.omi-title { font-size: 24px; font-weight: 800; color: #fff; margin: 0 0 4px; }
.omi-sub   { font-size: 12px; color: #93C5FD; margin: 0; }
.omi-btn-new {
    background: linear-gradient(135deg,#FFFFFF,#EFF6FF);
    color: #1E40AF; padding: 11px 22px; border-radius: 8px;
    font-size: 13px; font-weight: 700; text-decoration: none;
    box-shadow: 0 4px 16px rgba(0,0,0,.2); transition: all .2s; white-space: nowrap;
}
.omi-btn-new:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,0,0,.3); }

/* KPIs */
.omi-kpis { display: flex; gap: 0; max-width: 1400px; margin: 0 auto; }
.omi-kpi {
    flex: 1; padding: 18px 20px; text-align: center;
    border-right: 1px solid rgba(255,255,255,.1);
}
.omi-kpi:last-child { border-right: none; }
.kpi-blue   { background: #1E40AF; }
.kpi-yellow { background: #D97706; }
.kpi-indigo { background: #4338CA; }
.kpi-green  { background: #059669; }
.omi-kpi-num   { font-size: 32px; font-weight: 800; color: #fff; }
.omi-kpi-label { font-size: 11px; color: rgba(255,255,255,.75); text-transform: uppercase; letter-spacing: 1px; margin-top: 3px; }

/* FILTRES */
.omi-filters {
    display: flex; gap: 12px; padding: 16px 32px;
    max-width: 1400px; margin: 0 auto;
}
.omi-search-wrap { position: relative; flex: 1; }
.omi-search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 14px; }
.omi-search {
    width: 100%; padding: 10px 12px 10px 36px;
    border: 1.5px solid #E2E8F0; border-radius: 8px;
    font-size: 13px; color: #0F172A; background: #fff;
}
.omi-search:focus { outline: none; border-color: #1E40AF; }
.omi-select {
    padding: 10px 14px; border: 1.5px solid #E2E8F0; border-radius: 8px;
    font-size: 13px; color: #0F172A; background: #fff; min-width: 160px;
}
.omi-select:focus { outline: none; border-color: #1E40AF; }

/* TABLE */
.omi-table-wrap { max-width: 1400px; margin: 0 auto 20px; padding: 0 32px; overflow-x: auto; }
.omi-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.07); }
.omi-table thead tr { background: #0F172A; }
.omi-table th { color: #fff; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; padding: 13px 12px; text-align: left; white-space: nowrap; }
.omi-tr { border-bottom: 1px solid #F1F5F9; transition: background .15s; }
.omi-tr:last-child { border-bottom: none; }
.omi-tr:hover { background: #F8FAFC; }
.omi-table td { padding: 11px 12px; vertical-align: middle; font-size: 12.5px; color: #334155; }
.omi-td-seq { color: #94A3B8; font-weight: 700; width: 40px; }
.omi-ref { font-weight: 700; color: #1E40AF; font-size: 13px; font-family: monospace; }
.omi-phase { font-size: 10px; color: #94A3B8; }
.omi-intitule { font-weight: 600; color: #0F172A; font-size: 12.5px; max-width: 220px; }
.omi-lieu { font-size: 10.5px; color: #64748B; margin-top: 2px; }
.omi-entities { font-size: 11.5px; color: #475569; max-width: 150px; }
.omi-team-line { font-size: 11px; color: #334155; }
.omi-more { font-size: 10px; color: #94A3B8; }
.omi-dates { font-size: 11.5px; color: #475569; white-space: nowrap; }
.omi-duree { font-size: 10px; color: #94A3B8; margin-top: 1px; }
.omi-none { color: #CBD5E1; }

/* BADGES DIFFUSION */
.omi-diffusion-badge { font-size: 10.5px; padding: 3px 9px; border-radius: 20px; font-weight: 600; white-space: nowrap; }
.diff-electronique { background: #DBEAFE; color: #1E40AF; }
.diff-papier       { background: #F1F5F9; color: #475569; }
.diff-les_deux     { background: #EDE9FE; color: #6D28D9; }

/* BADGES STATUT */
.omi-status { font-size: 10.5px; padding: 3px 10px; border-radius: 20px; font-weight: 700; white-space: nowrap; }
.st-brouillon { background: #F1F5F9; color: #475569; }
.st-emis      { background: #FEF3C7; color: #D97706; }
.st-envoye    { background: #D1FAE5; color: #059669; }
.st-accuse    { background: #DBEAFE; color: #1E40AF; }

/* ACTIONS */
.omi-actions { display: flex; gap: 4px; align-items: center; }
.omi-action-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 30px; height: 30px; border-radius: 6px; border: none;
    cursor: pointer; font-size: 14px; text-decoration: none;
    background: #F8FAFC; transition: all .15s;
}
.omi-action-btn:hover { transform: scale(1.15); }
.omi-view:hover  { background: #DBEAFE; }
.omi-edit:hover  { background: #FEF3C7; }
.omi-pdf:hover   { background: #EDE9FE; }
.omi-send:hover  { background: #D1FAE5; }
.omi-sent        { background: #D1FAE5 !important; }
.omi-del:hover   { background: #FEE2E2; }

/* EMPTY */
.omi-empty { padding: 48px; text-align: center; }
.omi-empty-inner { color: #94A3B8; font-size: 13px; }
.omi-empty-sub { font-size: 11px; margin-top: 6px; }

/* PAGINATION */
.omi-pagination { display: flex; gap: 6px; justify-content: center; padding: 16px; }
.omi-page-btn {
    width: 36px; height: 36px; border-radius: 8px; border: 1.5px solid #E2E8F0;
    background: #fff; font-size: 13px; cursor: pointer; transition: all .15s;
}
.omi-page-btn:hover, .omi-page-btn.active { background: #1E40AF; color: #fff; border-color: #1E40AF; }

/* MODAL */
.omi-modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1000;
    display: flex; align-items: center; justify-content: center;
}
.omi-modal {
    background: #fff; border-radius: 14px; padding: 36px; max-width: 380px;
    text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,.25);
}
.omi-modal-icon { font-size: 44px; margin-bottom: 12px; }
.omi-modal h3   { font-size: 17px; color: #0F172A; margin: 0 0 10px; }
.omi-modal p    { font-size: 13px; color: #475569; margin: 0 0 4px; }
.omi-modal-sub  { font-size: 11.5px; color: #EF4444 !important; }
.omi-modal-actions { display: flex; gap: 12px; justify-content: center; margin-top: 20px; }
.omi-btn-cancel { padding: 9px 22px; border: 1.5px solid #E2E8F0; background: #fff; border-radius: 7px; font-size: 13px; cursor: pointer; }
.omi-btn-confirm { padding: 9px 22px; background: #EF4444; color: #fff; border: none; border-radius: 7px; font-size: 13px; font-weight: 700; cursor: pointer; }

/* TRANSITIONS */
.om-modal-enter-active, .om-modal-leave-active { transition: opacity .25s; }
.om-modal-enter-from, .om-modal-leave-to { opacity: 0; }
</style>