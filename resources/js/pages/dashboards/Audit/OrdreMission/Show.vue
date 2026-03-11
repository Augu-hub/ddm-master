<template>
    <div class="om-show">

        <!-- ─── HERO ─── -->
        <div class="oms-hero">
            <div class="oms-hero-inner">
                <div class="oms-hero-left">
                    <div class="oms-label">Ordre de Mission · Cabinet KEKELI</div>
                    <h1 class="oms-title">{{ om.intitule }}</h1>
                    <div class="oms-ref">
                        <span class="oms-ref-badge">{{ om.reference_om }}</span>
                        <span v-if="om.phase" class="oms-phase">{{ om.phase }}</span>
                        <span class="oms-status" :class="'st-' + om.status">{{ statusLabel(om.status) }}</span>
                    </div>
                </div>
                <div class="oms-hero-actions">
                    <a :href="route('audit.core.ordre-missions.pdf', om.id)"
                        target="_blank" class="oms-btn oms-btn-pdf">
                        📄 Télécharger PDF
                    </a>
                    <button v-if="om.forme_diffusion !== 'papier'"
                        @click="sendEmails()" :disabled="sending"
                        class="oms-btn oms-btn-send">
                        {{ sending ? '⟳ Envoi…' : '📧 Envoyer par email' }}
                    </button>
                    <a :href="route('audit.core.ordre-missions.edit', om.id)"
                        class="oms-btn oms-btn-edit">
                        ✏️ Modifier
                    </a>
                    <a :href="route('audit.core.ordre-missions.index')"
                        class="oms-btn oms-btn-back">
                        ← Retour
                    </a>
                </div>
            </div>
        </div>

        <div class="oms-body">
            <div class="oms-grid">

                <!-- ════ COLONNE PRINCIPALE ════ -->
                <div class="oms-main">

                    <!-- ÉLÉMENTS CARACTÉRISTIQUES -->
                    <div class="oms-card">
                        <div class="oms-card-header oms-blue">
                            <span>📋 Éléments Caractéristiques</span>
                        </div>
                        <div class="oms-card-body">
                            <table class="oms-info-table">
                                <tr>
                                    <td class="oms-info-label">Intitulé</td>
                                    <td class="oms-info-value"><strong>{{ om.intitule }}</strong></td>
                                </tr>
                                <tr v-if="om.objectif">
                                    <td class="oms-info-label">Objectif</td>
                                    <td class="oms-info-value">{{ om.objectif }}</td>
                                </tr>
                                <tr v-if="om.date_debut_fr">
                                    <td class="oms-info-label">Période</td>
                                    <td class="oms-info-value">
                                        <strong>{{ om.date_debut_fr }}</strong>
                                        <span v-if="om.date_fin_fr"> → <strong>{{ om.date_fin_fr }}</strong></span>
                                        <span v-if="om.duree" class="oms-duree"> ({{ om.duree }} jours)</span>
                                    </td>
                                </tr>
                                <tr v-if="om.lieux">
                                    <td class="oms-info-label">Lieu(x)</td>
                                    <td class="oms-info-value">📍 {{ om.lieux }}</td>
                                </tr>
                                <tr v-if="om.domaine">
                                    <td class="oms-info-label">Domaine</td>
                                    <td class="oms-info-value">{{ om.domaine }}</td>
                                </tr>
                                <tr v-if="om.moyen">
                                    <td class="oms-info-label">Moyens</td>
                                    <td class="oms-info-value">{{ om.moyen }}</td>
                                </tr>
                                <tr v-if="om.limite">
                                    <td class="oms-info-label">Périmètre / Limites</td>
                                    <td class="oms-info-value">{{ om.limite }}</td>
                                </tr>
                                <tr v-if="om.budget > 0">
                                    <td class="oms-info-label">Budget</td>
                                    <td class="oms-info-value oms-budget">
                                        {{ formatMontant(om.budget) }} FCFA
                                    </td>
                                </tr>
                                <tr v-if="om.emetteur">
                                    <td class="oms-info-label">Émetteur</td>
                                    <td class="oms-info-value">{{ om.emetteur }}</td>
                                </tr>
                                <tr v-if="om.destinataire">
                                    <td class="oms-info-label">Destinataire(s)</td>
                                    <td class="oms-info-value">{{ om.destinataire }}</td>
                                </tr>
                                <tr v-if="om.copie">
                                    <td class="oms-info-label">Copie (CC)</td>
                                    <td class="oms-info-value">{{ om.copie }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- MESSAGE PERSONNALISÉ -->
                    <div v-if="om.message_personnalise" class="oms-card">
                        <div class="oms-card-header oms-amber">
                            <span>💬 Message Personnalisé</span>
                        </div>
                        <div class="oms-card-body">
                            <div class="oms-message">{{ om.message_personnalise }}</div>
                        </div>
                    </div>

                    <!-- JOURNAL ENVOIS -->
                    <div class="oms-card">
                        <div class="oms-card-header oms-slate">
                            <span>📨 Journal des Envois Email</span>
                            <span class="oms-count">{{ envois.length }}</span>
                        </div>
                        <div class="oms-card-body oms-card-body-p0">
                            <table v-if="envois.length" class="oms-table">
                                <thead>
                                    <tr>
                                        <th>Destinataire</th>
                                        <th>Entité</th>
                                        <th>Statut</th>
                                        <th>Date envoi</th>
                                        <th>Erreur</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="e in envois" :key="e.id">
                                        <td>{{ e.destinataire }}</td>
                                        <td>{{ getEntiteName(e.entity_id) }}</td>
                                        <td>
                                            <span class="oms-envoi-badge" :class="'env-' + e.statut">
                                                {{ e.statut }}
                                            </span>
                                        </td>
                                        <td>{{ formatDate(e.envoye_le) }}</td>
                                        <td class="oms-erreur">{{ e.erreur || '—' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div v-else class="oms-empty">
                                <div>📭</div>
                                <div>Aucun envoi effectué</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ════ COLONNE LATÉRALE ════ -->
                <div class="oms-side">

                    <!-- ENTITÉS -->
                    <div class="oms-card">
                        <div class="oms-card-header oms-teal">
                            <span>🏢 Entités Couvertes</span>
                            <span class="oms-count">{{ entites.length }}</span>
                        </div>
                        <div class="oms-card-body oms-card-body-p0">
                            <div v-for="ent in entites" :key="ent.entity_id" class="oms-entity-item">
                                <div class="oms-entity-top">
                                    <span class="oms-entity-dot"></span>
                                    <strong class="oms-entity-name">{{ ent.entity_name }}</strong>
                                    <span v-if="ent.email_envoye" class="oms-sent-tick" title="Email envoyé">✅</span>
                                </div>
                                <div v-if="ent.nom_contact" class="oms-entity-sub">👤 {{ ent.nom_contact }}</div>
                                <div v-if="ent.email_contact" class="oms-entity-email">
                                    <a :href="'mailto:' + ent.email_contact">{{ ent.email_contact }}</a>
                                    <button @click="sendToEntity(ent.entity_id)"
                                        class="oms-btn-send-one" title="Envoyer à cette entité uniquement">
                                        📧
                                    </button>
                                </div>
                                <div v-else class="oms-no-email">⚠ Pas d'email renseigné</div>
                            </div>
                            <div v-if="!entites.length" class="oms-empty">Aucune entité</div>
                        </div>
                    </div>

                    <!-- ÉQUIPE D'AUDIT -->
                    <div class="oms-card">
                        <div class="oms-card-header oms-green">
                            <span>👥 Équipe d'Audit</span>
                            <span class="oms-count">{{ auditeurs.length }}</span>
                        </div>
                        <div class="oms-card-body oms-card-body-p0">
                            <div v-for="aud in auditeurs" :key="aud.auditeur_id" class="oms-aud-item">
                                <div class="oms-aud-avatar">
                                    {{ (aud.last_name[0] || '') + (aud.first_name[0] || '') }}
                                </div>
                                <div class="oms-aud-info">
                                    <div class="oms-aud-name">
                                        <strong>{{ aud.last_name.toUpperCase() }}</strong>
                                        {{ capitalize(aud.first_name) }}
                                    </div>
                                    <div class="oms-aud-meta">
                                        <span class="oms-aud-code">{{ aud.audit_code }}</span>
                                        <span v-if="aud.role" class="oms-role-badge">{{ aud.role }}</span>
                                    </div>
                                    <div v-if="aud.email" class="oms-aud-email">{{ aud.email }}</div>
                                </div>
                            </div>
                            <div v-if="!auditeurs.length" class="oms-empty">Aucun auditeur affecté</div>
                        </div>
                    </div>

                    <!-- DIFFUSION -->
                    <div class="oms-card">
                        <div class="oms-card-header oms-violet">
                            <span>📤 Diffusion</span>
                        </div>
                        <div class="oms-card-body">
                            <div class="oms-diff-row">
                                <span class="oms-diff-icon">
                                    {{ om.forme_diffusion === 'electronique' ? '📧' : om.forme_diffusion === 'papier' ? '🖨️' : '📬' }}
                                </span>
                                <span class="oms-diff-label">{{ diffusionLabel(om.forme_diffusion) }}</span>
                            </div>
                            <div v-if="om.date_limite_fr" class="oms-diff-detail">
                                📅 Date limite : <strong>{{ om.date_limite_fr }}</strong>
                            </div>
                            <div v-if="om.envoye_le" class="oms-diff-detail oms-sent-date">
                                ✅ Envoyé le {{ formatDate(om.envoye_le) }}
                            </div>
                        </div>
                    </div>

                    <!-- MISSION SOURCE -->
                    <div v-if="om.mission_prog_code" class="oms-card">
                        <div class="oms-card-header oms-indigo">
                            <span>🎯 Mission Source</span>
                        </div>
                        <div class="oms-card-body">
                            <div class="oms-mission-ref">
                                <span class="oms-mission-code">{{ om.mission_prog_code }}</span>
                                <span class="oms-mission-libelle">{{ om.mission_prog_libelle }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FLASH SUCCESS -->
        <Transition name="flash">
        <div v-if="flashMsg" class="oms-flash" :class="flashType">
            {{ flashMsg }}
        </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const props = defineProps({
    om:        Object,
    entites:   Array,
    auditeurs: Array,
    envois:    Array,
})

const sending  = ref(false)
const flashMsg = ref('')
const flashType = ref('flash-success')

function statusLabel(s) {
    return { brouillon:'Brouillon', emis:'Émis', envoye:'Envoyé', accuse:'Accusé réception' }[s] || s
}
function diffusionLabel(d) {
    return { electronique:'Électronique', papier:'Papier', les_deux:'Électronique & Papier' }[d] || d
}
function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1).toLowerCase() : '' }
function formatMontant(n) { return Number(n).toLocaleString('fr-FR') }
function formatDate(d) {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('fr-FR', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' })
}
function getEntiteName(id) {
    return props.entites.find(e => e.entity_id === id)?.entity_name || '—'
}

function sendEmails() {
    if (!confirm('Envoyer l\'OM par email à toutes les entités renseignées ?')) return
    sending.value = true
    router.post(route('audit.core.ordre-missions.send-emails', props.om.id), {}, {
        onFinish: () => { sending.value = false },
        onSuccess: () => { showFlash('Emails envoyés avec succès !', 'flash-success') },
        onError:   () => { showFlash('Erreur lors de l\'envoi.', 'flash-error') },
    })
}

function sendToEntity(entityId) {
    if (!confirm('Envoyer l\'OM à cette entité uniquement ?')) return
    router.post(route('audit.core.ordre-missions.send-emails', props.om.id), {
        entity_ids: [entityId]
    }, {
        onSuccess: () => showFlash('Email envoyé !', 'flash-success'),
        onError:   () => showFlash('Erreur lors de l\'envoi.', 'flash-error'),
    })
}

function showFlash(msg, type) {
    flashMsg.value = msg
    flashType.value = type
    setTimeout(() => { flashMsg.value = '' }, 4000)
}
</script>

<style scoped>
.om-show { background: #F1F5F9; min-height: 100vh; }

/* HERO */
.oms-hero { background: linear-gradient(135deg,#0F172A,#1E3A5F,#1E40AF); padding: 24px 32px 20px; }
.oms-hero-inner { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; max-width: 1400px; margin: 0 auto; flex-wrap: wrap; }
.oms-label { font-size: 10px; color: #93C5FD; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 5px; }
.oms-title { font-size: 22px; font-weight: 800; color: #fff; margin: 0 0 10px; }
.oms-ref   { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.oms-ref-badge { background: rgba(255,255,255,.15); color: #fff; padding: 3px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; font-family: monospace; }
.oms-phase { background: rgba(255,255,255,.1); color: #93C5FD; padding: 3px 10px; border-radius: 20px; font-size: 11px; }
.oms-status { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.st-brouillon { background: #475569; color: #fff; }
.st-emis      { background: #D97706; color: #fff; }
.st-envoye    { background: #059669; color: #fff; }
.st-accuse    { background: #1E40AF; color: #fff; }

/* ACTIONS HERO */
.oms-hero-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.oms-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 7px; font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none; border: none; font-family: inherit; transition: all .2s; white-space: nowrap; }
.oms-btn-pdf  { background: #7C3AED; color: #fff; }
.oms-btn-pdf:hover { background: #6D28D9; }
.oms-btn-send { background: #059669; color: #fff; }
.oms-btn-send:hover:not(:disabled) { background: #047857; }
.oms-btn-send:disabled { opacity: .6; cursor: not-allowed; }
.oms-btn-edit { background: #D97706; color: #fff; }
.oms-btn-edit:hover { background: #B45309; }
.oms-btn-back { background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.2); }
.oms-btn-back:hover { background: rgba(255,255,255,.2); }

/* BODY */
.oms-body { max-width: 1400px; margin: 0 auto; padding: 24px 32px; }
.oms-grid { display: grid; grid-template-columns: 1fr 360px; gap: 20px; }
.oms-main, .oms-side { display: flex; flex-direction: column; gap: 18px; }

/* CARTES */
.oms-card { background: #fff; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,.07); overflow: hidden; }
.oms-card-header { display: flex; align-items: center; justify-content: space-between; padding: 11px 18px; font-size: 12px; font-weight: 700; color: #fff; }
.oms-card-body { padding: 18px; }
.oms-card-body-p0 { padding: 0; }
.oms-count { background: rgba(255,255,255,.25); border-radius: 20px; padding: 1px 9px; font-size: 11px; }

/* COULEURS HEADERS */
.oms-blue   { background: linear-gradient(90deg,#1E40AF,#3B82F6); }
.oms-teal   { background: linear-gradient(90deg,#0F766E,#14B8A6); }
.oms-green  { background: linear-gradient(90deg,#059669,#34D399); }
.oms-amber  { background: linear-gradient(90deg,#D97706,#F59E0B); }
.oms-violet { background: linear-gradient(90deg,#6D28D9,#8B5CF6); }
.oms-indigo { background: linear-gradient(90deg,#4338CA,#6366F1); }
.oms-slate  { background: linear-gradient(90deg,#334155,#64748B); }

/* TABLE INFO */
.oms-info-table { width: 100%; border-collapse: collapse; }
.oms-info-table tr { border-bottom: 1px solid #F1F5F9; }
.oms-info-table tr:last-child { border-bottom: none; }
.oms-info-label { color: #64748B; font-size: 11.5px; font-weight: 600; padding: 8px 10px 8px 0; width: 30%; vertical-align: top; }
.oms-info-value { color: #0F172A; font-size: 12.5px; padding: 8px 0; }
.oms-duree  { color: #94A3B8; font-size: 11px; }
.oms-budget { color: #059669; font-weight: 700; font-size: 13px; }

/* MESSAGE */
.oms-message { font-size: 13px; color: #334155; line-height: 1.7; white-space: pre-wrap; background: #FFFBEB; padding: 14px; border-radius: 6px; border-left: 4px solid #F59E0B; }

/* TABLE ENVOIS */
.oms-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.oms-table th { background: #F8FAFC; color: #64748B; font-size: 10.5px; text-transform: uppercase; letter-spacing: .5px; padding: 9px 12px; text-align: left; border-bottom: 1px solid #E2E8F0; }
.oms-table td { padding: 9px 12px; border-bottom: 1px solid #F1F5F9; color: #334155; }
.oms-table tr:last-child td { border-bottom: none; }
.oms-envoi-badge { padding: 2px 9px; border-radius: 20px; font-size: 10.5px; font-weight: 700; }
.env-envoye    { background: #D1FAE5; color: #059669; }
.env-echec     { background: #FEE2E2; color: #DC2626; }
.env-en_attente{ background: #FEF3C7; color: #D97706; }
.oms-erreur { font-size: 10.5px; color: #EF4444; max-width: 150px; word-break: break-word; }

/* ENTITÉS SIDE */
.oms-entity-item { padding: 12px 16px; border-bottom: 1px solid #F1F5F9; }
.oms-entity-item:last-child { border-bottom: none; }
.oms-entity-top  { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
.oms-entity-dot  { width: 8px; height: 8px; border-radius: 50%; background: #0F766E; flex-shrink: 0; }
.oms-entity-name { font-size: 13px; color: #0F172A; flex: 1; }
.oms-sent-tick   { font-size: 13px; }
.oms-entity-sub  { font-size: 11px; color: #64748B; margin-bottom: 3px; padding-left: 16px; }
.oms-entity-email { display: flex; align-items: center; gap: 8px; padding-left: 16px; }
.oms-entity-email a { font-size: 11.5px; color: #1E40AF; text-decoration: none; }
.oms-entity-email a:hover { text-decoration: underline; }
.oms-no-email { font-size: 11px; color: #F59E0B; padding-left: 16px; }
.oms-btn-send-one { background: none; border: 1px solid #D1FAE5; border-radius: 5px; padding: 2px 6px; cursor: pointer; font-size: 12px; transition: all .15s; }
.oms-btn-send-one:hover { background: #D1FAE5; }

/* AUDITEURS SIDE */
.oms-aud-item { display: flex; gap: 12px; padding: 11px 16px; border-bottom: 1px solid #F1F5F9; align-items: flex-start; }
.oms-aud-item:last-child { border-bottom: none; }
.oms-aud-avatar { width: 34px; height: 34px; border-radius: 50%; background: #059669; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800; flex-shrink: 0; }
.oms-aud-name  { font-size: 12.5px; color: #0F172A; }
.oms-aud-meta  { display: flex; gap: 6px; align-items: center; margin-top: 3px; }
.oms-aud-code  { font-size: 10.5px; color: #94A3B8; font-family: monospace; }
.oms-role-badge { background: #1E40AF; color: #fff; padding: 1px 7px; border-radius: 10px; font-size: 10px; font-weight: 700; }
.oms-aud-email { font-size: 10.5px; color: #64748B; margin-top: 2px; }

/* DIFFUSION */
.oms-diff-row   { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
.oms-diff-icon  { font-size: 22px; }
.oms-diff-label { font-size: 13px; font-weight: 600; color: #334155; }
.oms-diff-detail { font-size: 12px; color: #64748B; margin-top: 4px; }
.oms-sent-date  { color: #059669; }

/* MISSION SOURCE */
.oms-mission-ref { display: flex; flex-direction: column; gap: 4px; }
.oms-mission-code { font-family: monospace; font-size: 12px; font-weight: 700; color: #1E40AF; }
.oms-mission-libelle { font-size: 12.5px; color: #334155; }

/* EMPTY */
.oms-empty { text-align: center; padding: 24px; color: #94A3B8; font-size: 13px; }
.oms-empty > div:first-child { font-size: 28px; margin-bottom: 6px; }

/* FLASH */
.oms-flash {
    position: fixed; bottom: 24px; right: 24px;
    padding: 14px 22px; border-radius: 10px;
    font-size: 13px; font-weight: 600; z-index: 9999;
    box-shadow: 0 8px 24px rgba(0,0,0,.2);
}
.flash-success { background: #D1FAE5; color: #059669; border: 1px solid #A7F3D0; }
.flash-error   { background: #FEE2E2; color: #DC2626; border: 1px solid #FECACA; }
.flash-enter-active, .flash-leave-active { transition: all .3s; }
.flash-enter-from, .flash-leave-to { opacity: 0; transform: translateY(16px); }

/* RESPONSIVE */
@media (max-width: 1024px) {
    .oms-grid { grid-template-columns: 1fr; }
    .oms-body { padding: 16px; }
    .oms-hero { padding: 18px 16px; }
}
</style>