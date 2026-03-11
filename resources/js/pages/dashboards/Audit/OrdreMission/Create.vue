<template>
    <div class="om-create">

        <!-- ═══════════ HERO ═══════════ -->
        <div class="om-hero">
            <div class="om-hero-inner">
                <div>
                    <div class="om-hero-label">Module Audit · Cabinet KEKELI</div>
                    <h1 class="om-hero-title">Nouvel Ordre de Mission</h1>
                    <p class="om-hero-sub">Créez et transmettez l'OM — paramétrage par entité</p>
                </div>
                <div class="om-ref-badge">
                    <span class="om-ref-num">{{ form.reference_om || newRef }}</span>
                    <span class="om-ref-label">REFERENCE OM</span>
                </div>
            </div>
        </div>

        <form @submit.prevent="submitForm" class="om-form">
            <div class="om-layout">

                <!-- ════════════════════════════════
                     PANNEAU GAUCHE — INFOS GENERALES
                ════════════════════════════════ -->
                <div class="om-panel-left">

                    <!-- MISSION SOURCE -->
                    <div class="om-card">
                        <div class="om-card-hd om-hd-blue">Mission Source</div>
                        <div class="om-card-bd">
                            <div class="om-field">
                                <label class="om-lbl">Mission Programmee</label>
                                <select v-model="form.mission_prog_id" class="om-sel" @change="onMissionChange">
                                    <option value="">— Choisir une mission —</option>
                                    <option v-for="m in missions" :key="m.id" :value="m.id">
                                        {{ m.code_mission }} — {{ m.libelle }}
                                    </option>
                                </select>
                                <div v-if="loadingMission" class="om-loading">Chargement...</div>
                            </div>
                            <div class="om-field">
                                <label class="om-lbl">Intitule <span class="req">*</span></label>
                                <input v-model="form.intitule" type="text" class="om-inp" required placeholder="Libelle de l'ordre de mission" />
                            </div>
                            <div class="om-field">
                                <label class="om-lbl">Objectif</label>
                                <textarea v-model="form.objectif" class="om-ta" rows="3" placeholder="Objectif principal..."></textarea>
                            </div>
                            <div class="om-row2">
                                <div class="om-field">
                                    <label class="om-lbl">Lieux</label>
                                    <input v-model="form.lieux" type="text" class="om-inp" placeholder="Ville / Adresse" />
                                </div>
                                <div class="om-field">
                                    <label class="om-lbl">Domaine</label>
                                    <input v-model="form.domaine" type="text" class="om-inp" placeholder="Finance, RH..." />
                                </div>
                            </div>
                            <div class="om-row2">
                                <div class="om-field">
                                    <label class="om-lbl">Perimetre / Limites</label>
                                    <input v-model="form.limite" type="text" class="om-inp" />
                                </div>
                                <div class="om-field">
                                    <label class="om-lbl">Moyens Alloues</label>
                                    <input v-model="form.moyen" type="text" class="om-inp" />
                                </div>
                            </div>
                            <div class="om-row3">
                                <div class="om-field">
                                    <label class="om-lbl">Budget Global (FCFA)</label>
                                    <input v-model="form.budget" type="number" class="om-inp" placeholder="0" min="0" />
                                </div>
                                <div class="om-field">
                                    <label class="om-lbl">Phase</label>
                                    <input v-model="form.phase" type="text" class="om-inp" placeholder="ORMI" />
                                </div>
                                <div class="om-field">
                                    <label class="om-lbl">Reference OM</label>
                                    <input v-model="form.reference_om" type="text" class="om-inp" :placeholder="newRef" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DIFFUSION -->
                    <div class="om-card">
                        <div class="om-card-hd om-hd-violet">Diffusion & Destinataires</div>
                        <div class="om-card-bd">
                            <div class="om-field">
                                <label class="om-lbl">Forme de diffusion <span class="req">*</span></label>
                                <div class="om-diff-row">
                                    <label v-for="opt in diffOpts" :key="opt.v"
                                        class="om-diff-opt" :class="{ active: form.forme_diffusion===opt.v }">
                                        <input type="radio" v-model="form.forme_diffusion" :value="opt.v" class="sr-only" />
                                        <span class="om-diff-lbl">{{ opt.l }}</span>
                                    </label>
                                </div>
                            </div>
                            <div class="om-row2">
                                <div class="om-field">
                                    <label class="om-lbl">Emetteur</label>
                                    <input v-model="form.emetteur" type="text" class="om-inp" placeholder="Directeur Audit Interne" />
                                </div>
                                <div class="om-field">
                                    <label class="om-lbl">Date limite diffusion</label>
                                    <input v-model="form.date_limite_diffusion" type="date" class="om-inp" />
                                </div>
                            </div>
                            <div class="om-field">
                                <label class="om-lbl">Destinataire(s) global</label>
                                <input v-model="form.destinataire" type="text" class="om-inp" placeholder="Responsables principaux" />
                            </div>
                            <div class="om-field">
                                <label class="om-lbl">Copie (CC) global</label>
                                <input v-model="form.copie" type="text" class="om-inp" placeholder="email1@ex.com, email2@ex.com" />
                            </div>
                            <div class="om-field">
                                <label class="om-lbl">Message personnalise global</label>
                                <textarea v-model="form.message_personnalise" class="om-ta" rows="3"
                                    placeholder="Message inclus dans tous les emails...&#10;(peut etre surcharge par entite)"></textarea>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ════════════════════════════════
                     PANNEAU DROIT — ENTITES + AUDITEURS
                ════════════════════════════════ -->
                <div class="om-panel-right">

                    <!-- AJOUT ENTITE -->
                    <div class="om-card">
                        <div class="om-card-hd om-hd-teal">
                            Entites Couvertes
                            <span class="om-cnt">{{ form.entites.length }}</span>
                        </div>
                        <div class="om-card-bd">
                            <div class="om-add-row">
                                <select v-model="newEntityId" class="om-sel om-sel-sm">
                                    <option value="">+ Ajouter une entite...</option>
                                    <option v-for="e in availableEntites" :key="e.id" :value="e.id">{{ e.name }}</option>
                                </select>
                                <button type="button" @click="addEntity" class="om-btn-add" :disabled="!newEntityId">Ajouter</button>
                            </div>
                            <div v-if="form.entites.length===0" class="om-empty">
                                <div class="om-empty-icon">🏢</div>
                                <div>Aucune entite selectionnee</div>
                            </div>
                        </div>
                    </div>

                    <!-- BLOC PAR ENTITE -->
                    <TransitionGroup name="slide">
                    <div v-for="(ent, idx) in form.entites" :key="ent.entity_id" class="om-ent-block">

                        <!-- Titre entite -->
                        <div class="om-ent-header">
                            <div class="om-ent-title">
                                <span class="om-ent-num">{{ idx+1 }}</span>
                                <strong>{{ getEntityName(ent.entity_id) }}</strong>
                            </div>
                            <div class="om-ent-actions">
                                <button type="button" @click="toggleEnt(idx)"
                                    class="om-btn-tog" :class="{ open: ent._open }">
                                    {{ ent._open ? '▲ Reduire' : '▼ Configurer' }}
                                </button>
                                <button type="button" @click="removeEntity(idx)" class="om-btn-del">Retirer</button>
                            </div>
                        </div>

                        <!-- Resume ferme -->
                        <div v-if="!ent._open" class="om-ent-summary">
                            <span class="om-sum-tag" v-if="ent.date_debut">
                                {{ fmtDate(ent.date_debut) }} → {{ fmtDate(ent.date_fin) }}
                                <span v-if="ent.duree" class="om-sum-sub">({{ ent.duree }}j)</span>
                            </span>
                            <span class="om-sum-tag" v-if="ent.auditeurs.length">
                                {{ ent.auditeurs.length }} auditeur(s)
                            </span>
                            <span class="om-sum-tag" v-if="ent.email_contact">
                                {{ ent.email_contact }}
                            </span>
                            <span class="om-sum-tag" v-if="ent.documents.length">
                                {{ ent.documents.length }} doc(s) joint(s)
                            </span>
                            <span v-if="!ent.date_debut && !ent.email_contact" class="om-sum-warn">
                                Non configure — cliquez "Configurer"
                            </span>
                        </div>

                        <!-- Detail ouvert -->
                        <div v-if="ent._open" class="om-ent-detail">

                            <!-- Periode -->
                            <div class="om-ent-section">
                                <div class="om-ent-sec-title">Periode d'intervention</div>
                                <div class="om-row3">
                                    <div class="om-field">
                                        <label class="om-lbl-sm">Date debut</label>
                                        <input v-model="ent.date_debut" type="date" class="om-inp om-inp-sm"
                                            @change="calcDureeEnt(ent)" />
                                    </div>
                                    <div class="om-field">
                                        <label class="om-lbl-sm">Date fin</label>
                                        <input v-model="ent.date_fin" type="date" class="om-inp om-inp-sm"
                                            @change="calcDureeEnt(ent)" />
                                    </div>
                                    <div class="om-field">
                                        <label class="om-lbl-sm">Duree (j)</label>
                                        <input v-model="ent.duree" type="number" class="om-inp om-inp-sm" readonly />
                                    </div>
                                </div>
                                <div class="om-field">
                                    <label class="om-lbl-sm">Lieux specifique (optionnel)</label>
                                    <input v-model="ent.lieux" type="text" class="om-inp om-inp-sm"
                                        :placeholder="form.lieux || 'Adresse entite...'" />
                                </div>
                            </div>

                            <!-- Contact -->
                            <div class="om-ent-section">
                                <div class="om-ent-sec-title">Contact & Envoi</div>
                                <div class="om-row2">
                                    <div class="om-field">
                                        <label class="om-lbl-sm">
                                            Email de contact
                                            <span v-if="form.forme_diffusion!=='papier'" class="req">*</span>
                                        </label>
                                        <input v-model="ent.email_contact" type="email" class="om-inp om-inp-sm"
                                            placeholder="contact@entite.com"
                                            :class="{ 'inp-warn': needsEmail && !ent.email_contact }" />
                                    </div>
                                    <div class="om-field">
                                        <label class="om-lbl-sm">Nom du contact</label>
                                        <input v-model="ent.nom_contact" type="text" class="om-inp om-inp-sm"
                                            placeholder="Responsable / DG" />
                                    </div>
                                </div>
                                <div class="om-row2">
                                    <div class="om-field">
                                        <label class="om-lbl-sm">CC specifique</label>
                                        <input v-model="ent.copie" type="text" class="om-inp om-inp-sm"
                                            placeholder="email@cc.com" />
                                    </div>
                                    <div class="om-field">
                                        <label class="om-lbl-sm">Destinataire specifique</label>
                                        <input v-model="ent.destinataire" type="text" class="om-inp om-inp-sm"
                                            placeholder="Surcharge le global" />
                                    </div>
                                </div>
                                <div class="om-field">
                                    <label class="om-lbl-sm">Message specifique (surcharge le message global)</label>
                                    <textarea v-model="ent.message" class="om-ta om-ta-sm" rows="2"
                                        placeholder="Message propre a cette entite..."></textarea>
                                </div>
                                <div v-if="needsEmail && !ent.email_contact" class="om-warn">
                                    Email requis pour envoi electronique
                                </div>
                            </div>

                            <!-- Auditeurs de l'entite -->
                            <div class="om-ent-section">
                                <div class="om-ent-sec-title">
                                    Auditeurs affectes a cette entite
                                    <span class="om-cnt-sm">{{ ent.auditeurs.length }}</span>
                                </div>
                                <div class="om-add-row">
                                    <select v-model="ent._newAudId" class="om-sel om-sel-sm">
                                        <option value="">+ Ajouter un auditeur...</option>
                                        <option v-for="a in getAvailableAuditeurs(ent)" :key="a.id" :value="a.id">
                                            {{ a.last_name.toUpperCase() }} {{ a.first_name }} ({{ a.audit_code }})
                                        </option>
                                    </select>
                                    <button type="button" @click="addAudToEnt(ent)"
                                        class="om-btn-add om-btn-green" :disabled="!ent._newAudId">
                                        Ajouter
                                    </button>
                                </div>
                                <div class="om-aud-list">
                                    <div v-for="(aud, ai) in ent.auditeurs" :key="aud.auditeur_id" class="om-aud-row">
                                        <div class="om-aud-av">{{ getInitials(aud.auditeur_id) }}</div>
                                        <div class="om-aud-info">
                                            <span class="om-aud-name">{{ getAudName(aud.auditeur_id) }}</span>
                                            <span class="om-aud-code">{{ getAudCode(aud.auditeur_id) }}</span>
                                        </div>
                                        <select v-model="aud.role" class="om-sel om-sel-xs">
                                            <option value="">Role</option>
                                            <option v-for="r in roles" :key="r.code" :value="r.code">
                                                {{ r.code }} – {{ r.libelle }}
                                            </option>
                                        </select>
                                        <button type="button" @click="removeAudFromEnt(ent, ai)" class="om-btn-del-sm">x</button>
                                    </div>
                                    <div v-if="ent.auditeurs.length===0" class="om-empty-sm">
                                        Aucun auditeur affecte a cette entite
                                    </div>
                                </div>
                            </div>

                            <!-- Documents joints -->
                            <div class="om-ent-section">
                                <div class="om-ent-sec-title">
                                    Documents joints a l'email
                                    <span class="om-cnt-sm">{{ ent.documents.length }}</span>
                                </div>
                                <div class="om-doc-upload"
                                    @click="triggerUpload(ent.entity_id)"
                                    @dragover.prevent @drop.prevent="onDrop($event, ent)">
                                    <input
                                        type="file"
                                        :id="`upload_${ent.entity_id}`"
                                        multiple
                                        accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg"
                                        @change="onFileSelect($event, ent)"
                                        style="display:none" />
                                    <div class="om-doc-upload-icon">+</div>
                                    <div class="om-doc-upload-txt">Cliquez ou deposez vos fichiers ici</div>
                                    <div class="om-doc-upload-sub">PDF, Word, Excel, Images — max 20 Mo</div>
                                </div>
                                <div class="om-doc-list">
                                    <div v-for="(doc, di) in ent.documents" :key="di" class="om-doc-row">
                                        <span class="om-doc-icon">{{ getDocIcon(doc.name) }}</span>
                                        <div class="om-doc-info">
                                            <span class="om-doc-name">{{ doc.name }}</span>
                                            <span class="om-doc-size">{{ formatSize(doc.size) }}</span>
                                        </div>
                                        <button type="button" @click="removeDoc(ent, di)" class="om-btn-del-sm">x</button>
                                    </div>
                                </div>
                            </div>

                        </div><!-- /ent._open -->

                    </div>
                    </TransitionGroup>

                    <!-- BLOC AUDITEURS GENERAUX (globaux) -->
                    <div class="om-card">
                        <div class="om-card-hd om-hd-green">
                            Equipe Globale
                            <span class="om-cnt">{{ globalAuditeurs.length }}</span>
                        </div>
                        <div class="om-card-bd">
                            <div class="om-hint-box">
                                Les auditeurs globaux sont visibles dans le PDF de toutes les entites.
                                Pour affecter un auditeur a une entite specifique, configurez-le dans le bloc entite.
                            </div>
                            <div class="om-add-row">
                                <select v-model="newGlobalAudId" class="om-sel om-sel-sm">
                                    <option value="">+ Ajouter auditeur global...</option>
                                    <option v-for="a in availableGlobalAuditeurs" :key="a.id" :value="a.id">
                                        {{ a.last_name.toUpperCase() }} {{ a.first_name }} ({{ a.audit_code }})
                                    </option>
                                </select>
                                <button type="button" @click="addGlobalAud" class="om-btn-add om-btn-green" :disabled="!newGlobalAudId">
                                    Ajouter
                                </button>
                            </div>
                            <div class="om-aud-list">
                                <div v-for="(aud, ai) in globalAuditeurs" :key="aud.auditeur_id" class="om-aud-row">
                                    <div class="om-aud-av">{{ getInitials(aud.auditeur_id) }}</div>
                                    <div class="om-aud-info">
                                        <span class="om-aud-name">{{ getAudName(aud.auditeur_id) }}</span>
                                        <span class="om-aud-code">{{ getAudCode(aud.auditeur_id) }}</span>
                                    </div>
                                    <select v-model="aud.role" class="om-sel om-sel-xs">
                                        <option value="">Role</option>
                                        <option v-for="r in roles" :key="r.code" :value="r.code">
                                            {{ r.code }} – {{ r.libelle }}
                                        </option>
                                    </select>
                                    <button type="button" @click="removeGlobalAud(ai)" class="om-btn-del-sm">x</button>
                                </div>
                                <div v-if="globalAuditeurs.length===0" class="om-empty-sm">Aucun auditeur global</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- BARRE D'ACTIONS -->
            <div class="om-action-bar">
                <a :href="route('audit.core.ordre-missions.index')" class="om-btn om-ghost">Annuler</a>
                <div style="display:flex;gap:12px">
                    <button type="submit" name="action" value="brouillon" class="om-btn om-outline">
                        Enregistrer brouillon
                    </button>
                    <button type="submit" name="action" value="emettre" class="om-btn om-primary">
                        Creer et Emettre l'OM
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const props = defineProps({
    missions:   { type: Array, default: () => [] },
    entites:    { type: Array, default: () => [] },
    auditeurs:  { type: Array, default: () => [] },
    roles:      { type: Array, default: () => [] },
    newRef:     { type: String, default: '' },
})

// ─── FORM ─────────────────────────────────────────────────────
const form = reactive({
    reference_om:           props.newRef,
    mission_prog_id:        '',
    mission_id:             '',
    intitule:               '',
    objectif:               '',
    lieux:                  '',
    domaine:                '',
    limite:                 '',
    moyen:                  '',
    budget:                 0,
    phase:                  'ORMI',
    forme_diffusion:        'electronique',
    date_limite_diffusion:  '',
    emetteur:               '',
    destinataire:           '',
    copie:                  '',
    message_personnalise:   '',
    entites: [],
    /* entite shape:
    {
        entity_id, _open, _newAudId,
        date_debut, date_fin, duree, lieux,
        email_contact, nom_contact, copie, destinataire, message,
        auditeurs: [{ auditeur_id, role, role_libelle }],
        documents: [File objects]
    }
    */
})

const globalAuditeurs = ref([]) // [{ auditeur_id, role, role_libelle }]
const loadingMission  = ref(false)
const newEntityId     = ref('')
const newGlobalAudId  = ref('')
const fileRefs        = ref({})

const diffOpts = [
    { v: 'electronique', l: 'Electronique' },
    { v: 'papier',       l: 'Papier'       },
    { v: 'les_deux',     l: 'Les deux'     },
]

// ─── COMPUTED ─────────────────────────────────────────────────
const needsEmail = computed(() => form.forme_diffusion !== 'papier')

const availableEntites = computed(() => {
    const sel = form.entites.map(e => e.entity_id)
    return props.entites.filter(e => !sel.includes(e.id))
})

const availableGlobalAuditeurs = computed(() => {
    const sel = globalAuditeurs.value.map(a => a.auditeur_id)
    return props.auditeurs.filter(a => !sel.includes(a.id))
})

function getAvailableAuditeurs(ent) {
    const sel = ent.auditeurs.map(a => a.auditeur_id)
    return props.auditeurs.filter(a => !sel.includes(a.id))
}

// ─── HANDLERS MISSION ─────────────────────────────────────────
async function onMissionChange() {
    if (!form.mission_prog_id) return
    loadingMission.value = true
    try {
        const res  = await fetch(route('audit.core.ordre-missions.mission-entites', form.mission_prog_id))
        const data = await res.json()

        if (data.mission) {
            form.intitule   = data.mission.libelle   || ''
            form.objectif   = data.mission.objectif  || ''
            form.lieux      = data.mission.lieux     || ''
            form.budget     = data.mission.budget    || 0
            form.mission_id = data.mission.id        || ''
        }

        // Charger entites avec leurs dates
        form.entites = (data.entites || []).map(e => makeEntite(e.entity_id, {
            date_debut: e.date_debut || data.mission?.date_debut || '',
            date_fin:   e.date_fin   || data.mission?.date_fin   || '',
        }))
        form.entites.forEach(e => calcDureeEnt(e))

        // Charger auditeurs globaux
        globalAuditeurs.value = (data.auditeurs || []).map(a => ({
            auditeur_id:  a.auditeur_id,
            role:         a.role         || '',
            role_libelle: a.role_libelle || '',
        }))

    } catch (err) {
        console.error('Erreur chargement mission', err)
    } finally {
        loadingMission.value = false
    }
}

// ─── HELPERS ENTITE ───────────────────────────────────────────
function makeEntite(entity_id, extra = {}) {
    return {
        entity_id,
        _open:        true,
        _newAudId:    '',
        date_debut:   extra.date_debut  || '',
        date_fin:     extra.date_fin    || '',
        duree:        extra.duree       || '',
        lieux:        extra.lieux       || '',
        email_contact: extra.email_contact || '',
        nom_contact:  extra.nom_contact || '',
        copie:        extra.copie       || '',
        destinataire: extra.destinataire|| '',
        message:      extra.message     || '',
        auditeurs:    extra.auditeurs   || [],
        documents:    [],
    }
}

function addEntity() {
    if (!newEntityId.value) return
    form.entites.push(makeEntite(parseInt(newEntityId.value)))
    newEntityId.value = ''
}
function removeEntity(idx) { form.entites.splice(idx, 1) }
function toggleEnt(idx) { form.entites[idx]._open = !form.entites[idx]._open }

function calcDureeEnt(ent) {
    if (!ent.date_debut || !ent.date_fin) { ent.duree = ''; return }
    const d = Math.ceil(Math.abs(new Date(ent.date_fin) - new Date(ent.date_debut)) / 86400000) + 1
    ent.duree = d > 0 ? d : ''
}

// ─── AUDITEURS PAR ENTITE ─────────────────────────────────────
function addAudToEnt(ent) {
    if (!ent._newAudId) return
    ent.auditeurs.push({ auditeur_id: parseInt(ent._newAudId), role: '', role_libelle: '' })
    ent._newAudId = ''
}
function removeAudFromEnt(ent, ai) { ent.auditeurs.splice(ai, 1) }

// ─── AUDITEURS GLOBAUX ────────────────────────────────────────
function addGlobalAud() {
    if (!newGlobalAudId.value) return
    globalAuditeurs.value.push({ auditeur_id: parseInt(newGlobalAudId.value), role: '', role_libelle: '' })
    newGlobalAudId.value = ''
}
function removeGlobalAud(ai) { globalAuditeurs.value.splice(ai, 1) }

// ─── DOCUMENTS ────────────────────────────────────────────────
function triggerUpload(entityId) {
    const input = document.getElementById(`upload_${entityId}`)
    if (input) input.click()
}
function onFileSelect(event, ent) {
    Array.from(event.target.files).forEach(f => {
        if (f.size > 20 * 1024 * 1024) { alert(`Fichier trop lourd : ${f.name} (max 20 Mo)`); return }
        ent.documents.push(f)
    })
    event.target.value = ''
}
function onDrop(event, ent) {
    Array.from(event.dataTransfer.files).forEach(f => {
        if (f.size > 20 * 1024 * 1024) { alert(`Fichier trop lourd : ${f.name} (max 20 Mo)`); return }
        ent.documents.push(f)
    })
}
function removeDoc(ent, di) { ent.documents.splice(di, 1) }

function getDocIcon(name) {
    const ext = (name || '').split('.').pop().toLowerCase()
    const map = { pdf: 'PDF', doc: 'DOC', docx: 'DOC', xls: 'XLS', xlsx: 'XLS', png: 'IMG', jpg: 'IMG', jpeg: 'IMG' }
    return map[ext] || 'FIC'
}
function formatSize(bytes) {
    if (!bytes) return ''
    if (bytes < 1024) return bytes + ' B'
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' Ko'
    return (bytes / 1048576).toFixed(1) + ' Mo'
}

// ─── HELPERS NOMS ─────────────────────────────────────────────
function getEntityName(id)   { return props.entites.find(e => e.id === id)?.name || '—' }
function getAudName(id) {
    const a = props.auditeurs.find(a => a.id === id)
    return a ? `${a.last_name.toUpperCase()} ${a.first_name}` : '—'
}
function getAudCode(id)      { return props.auditeurs.find(a => a.id === id)?.audit_code || '' }
function getInitials(id) {
    const a = props.auditeurs.find(a => a.id === id)
    if (!a) return '?'
    return (a.last_name[0] || '').toUpperCase() + (a.first_name[0] || '').toUpperCase()
}
function fmtDate(d) {
    if (!d) return ''
    const [y, m, j] = d.split('-')
    return `${j}/${m}/${y}`
}

// ─── SUBMIT ───────────────────────────────────────────────────
function submitForm(e) {
    const action = e.submitter?.value || 'brouillon'
    const fd = new FormData()

    // Champs scalaires
    const scalars = [
        'reference_om','mission_prog_id','mission_id','intitule','objectif',
        'lieux','domaine','limite','moyen','budget','phase','forme_diffusion',
        'date_limite_diffusion','emetteur','destinataire','copie','message_personnalise'
    ]
    scalars.forEach(k => fd.append(k, form[k] ?? ''))
    fd.append('_action', action)

    // Entites — donnees texte + auditeurs par entite
    form.entites.forEach((ent, i) => {
        fd.append(`entites[${i}][entity_id]`,      ent.entity_id)
        fd.append(`entites[${i}][date_debut]`,     ent.date_debut    || '')
        fd.append(`entites[${i}][date_fin]`,       ent.date_fin      || '')
        fd.append(`entites[${i}][duree]`,          ent.duree         || '')
        fd.append(`entites[${i}][lieux]`,          ent.lieux         || '')
        fd.append(`entites[${i}][email_contact]`,  ent.email_contact || '')
        fd.append(`entites[${i}][nom_contact]`,    ent.nom_contact   || '')
        fd.append(`entites[${i}][copie]`,          ent.copie         || '')
        fd.append(`entites[${i}][destinataire]`,   ent.destinataire  || '')
        fd.append(`entites[${i}][message]`,        ent.message       || '')

        ent.auditeurs.forEach((a, ai) => {
            fd.append(`entites[${i}][auditeurs][${ai}][auditeur_id]`,  a.auditeur_id)
            fd.append(`entites[${i}][auditeurs][${ai}][role]`,         a.role || '')
            fd.append(`entites[${i}][auditeurs][${ai}][role_libelle]`, a.role_libelle || '')
        })

        // Fichiers sous cle PLATE : docs_{entityId}[]
        // Laravel recoit $_FILES['docs_123'] => tableau de fichiers
        ent.documents.forEach(file => {
            fd.append(`docs_${ent.entity_id}[]`, file, file.name)
        })
    })

    // Auditeurs globaux
    globalAuditeurs.value.forEach((a, ai) => {
        fd.append(`auditeurs[${ai}][auditeur_id]`,  a.auditeur_id)
        fd.append(`auditeurs[${ai}][role]`,         a.role || '')
        fd.append(`auditeurs[${ai}][role_libelle]`, a.role_libelle || '')
    })

    router.post(route('audit.core.ordre-missions.store'), fd, {
        forceFormData: true,
        onError: (errors) => console.error('Erreurs OM:', errors)
    })
}
</script>

<style scoped>
/* ════════════════════════════════════════════
   ORDRE DE MISSION — CREATE
════════════════════════════════════════════ */
.om-create { background:#F0F4F8; min-height:100vh; padding-bottom:100px; }

/* HERO */
.om-hero { background:linear-gradient(135deg,#0F172A 0%,#1E3A5F 55%,#1E40AF 100%); padding:26px 32px; }
.om-hero-inner { display:flex; justify-content:space-between; align-items:center; gap:20px; max-width:1500px; margin:0 auto; }
.om-hero-label { font-size:10px; color:#93C5FD; letter-spacing:2px; text-transform:uppercase; margin-bottom:5px; }
.om-hero-title { font-size:24px; font-weight:800; color:#fff; margin:0 0 4px; }
.om-hero-sub   { font-size:12px; color:#93C5FD; margin:0; }
.om-ref-badge  { background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.2); border-radius:10px; padding:12px 20px; text-align:center; }
.om-ref-num    { display:block; font-size:16px; font-weight:800; color:#fff; font-family:monospace; }
.om-ref-label  { font-size:9px; color:#93C5FD; letter-spacing:2px; text-transform:uppercase; }

/* FORM */
.om-form { max-width:1500px; margin:0 auto; padding:24px 32px; }

/* LAYOUT 2 COL */
.om-layout { display:grid; grid-template-columns:1fr 480px; gap:24px; align-items:start; }
.om-panel-left, .om-panel-right { display:flex; flex-direction:column; gap:18px; }

/* CARD */
.om-card { background:#fff; border-radius:10px; box-shadow:0 1px 3px rgba(0,0,0,.08),0 4px 14px rgba(0,0,0,.04); overflow:hidden; }
.om-card-hd { padding:11px 18px; font-size:12px; font-weight:700; color:#fff; display:flex; align-items:center; gap:10px; }
.om-card-bd { padding:18px; }
.om-hd-blue   { background:#1E40AF; }
.om-hd-violet { background:#5B21B6; }
.om-hd-teal   { background:#0F766E; }
.om-hd-green  { background:#059669; }

/* CHAMPS */
.om-field { margin-bottom:12px; }
.om-field:last-child { margin-bottom:0; }
.om-lbl    { display:block; font-size:11.5px; font-weight:600; color:#475569; margin-bottom:4px; }
.om-lbl-sm { display:block; font-size:11px;   font-weight:600; color:#64748B; margin-bottom:3px; }
.om-inp, .om-sel, .om-ta {
    width:100%; padding:8px 11px; border:1.5px solid #E2E8F0; border-radius:7px;
    font-size:12.5px; color:#0F172A; background:#FAFBFC; font-family:inherit;
    transition:border-color .2s, box-shadow .2s;
}
.om-inp:focus, .om-sel:focus, .om-ta:focus {
    outline:none; border-color:#1E40AF; box-shadow:0 0 0 3px rgba(30,64,175,.1); background:#fff;
}
.om-inp-sm { padding:6px 9px; font-size:12px; }
.om-ta     { resize:vertical; }
.om-ta-sm  { padding:6px 9px; font-size:11.5px; }
.om-sel-sm { padding:6px 9px; font-size:12px; }
.om-sel-xs { padding:4px 7px; font-size:11px; flex:1; }
.inp-warn  { border-color:#F59E0B!important; background:#FFFBEB!important; }
.req { color:#EF4444; }
.om-row2 { display:grid; grid-template-columns:1fr 1fr; gap:11px; }
.om-row3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:11px; }

/* DIFFUSION */
.om-diff-row { display:flex; gap:8px; }
.om-diff-opt {
    flex:1; display:flex; align-items:center; justify-content:center;
    padding:9px 6px; border:2px solid #E2E8F0; border-radius:8px;
    cursor:pointer; transition:all .2s;
}
.om-diff-opt:hover { border-color:#1E40AF; background:#EFF6FF; }
.om-diff-opt.active { border-color:#1E40AF; background:#DBEAFE; }
.om-diff-lbl { font-size:12px; font-weight:600; color:#475569; }
.om-diff-opt.active .om-diff-lbl { color:#1E40AF; }

/* COUNT BADGE */
.om-cnt    { background:rgba(255,255,255,.25); border-radius:20px; padding:1px 8px; font-size:11px; font-weight:700; }
.om-cnt-sm { background:#EFF6FF; color:#1E40AF; border-radius:10px; padding:1px 7px; font-size:10px; font-weight:700; margin-left:6px; }

/* HINT */
.om-hint-box { background:#EFF6FF; border:1px solid #BFDBFE; border-radius:6px; padding:9px 12px; font-size:11px; color:#1E40AF; margin-bottom:12px; line-height:1.5; }

/* ADD ROW */
.om-add-row { display:flex; gap:7px; margin-bottom:10px; }
.om-btn-add {
    padding:6px 13px; background:#1E40AF; color:#fff; border:none; border-radius:7px;
    font-size:11.5px; font-weight:600; cursor:pointer; white-space:nowrap; transition:background .2s;
}
.om-btn-add:hover:not(:disabled) { background:#1D4ED8; }
.om-btn-add:disabled { background:#CBD5E1; cursor:not-allowed; }
.om-btn-green { background:#059669; }
.om-btn-green:hover:not(:disabled) { background:#047857; }

/* ENTITE BLOCK */
.om-ent-block { background:#fff; border-radius:10px; box-shadow:0 1px 3px rgba(0,0,0,.08),0 4px 14px rgba(0,0,0,.04); overflow:hidden; }
.om-ent-header { display:flex; justify-content:space-between; align-items:center; padding:10px 14px; background:#F8FAFC; border-bottom:1px solid #E2E8F0; }
.om-ent-title  { display:flex; align-items:center; gap:9px; font-size:13px; color:#0F172A; }
.om-ent-num    { background:#1E40AF; color:#fff; width:22px; height:22px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; flex-shrink:0; }
.om-ent-actions { display:flex; gap:7px; }
.om-btn-tog {
    padding:4px 10px; font-size:11px; font-weight:600; border-radius:6px; cursor:pointer;
    background:#EFF6FF; color:#1E40AF; border:1px solid #BFDBFE; transition:all .2s;
}
.om-btn-tog:hover { background:#DBEAFE; }
.om-btn-tog.open  { background:#DBEAFE; }
.om-btn-del {
    padding:4px 10px; font-size:11px; font-weight:600; border-radius:6px; cursor:pointer;
    background:#FEE2E2; color:#DC2626; border:1px solid #FECACA; transition:all .2s;
}
.om-btn-del:hover { background:#FECACA; }
.om-btn-del-sm {
    background:none; border:none; color:#94A3B8; cursor:pointer; font-size:12px;
    padding:2px 6px; border-radius:4px; transition:all .15s; flex-shrink:0;
}
.om-btn-del-sm:hover { background:#FEE2E2; color:#DC2626; }

/* RESUME FERME */
.om-ent-summary { display:flex; gap:7px; flex-wrap:wrap; padding:8px 14px; border-bottom:1px solid #F1F5F9; }
.om-sum-tag { background:#EFF6FF; color:#1E40AF; border-radius:5px; padding:3px 8px; font-size:11px; font-weight:500; }
.om-sum-sub { color:#94A3B8; }
.om-sum-warn { background:#FEF9C3; color:#92400E; border-radius:5px; padding:3px 8px; font-size:11px; }

/* DETAIL ENTITE */
.om-ent-detail { padding:14px; display:flex; flex-direction:column; gap:14px; }
.om-ent-section { background:#F8FAFC; border-radius:8px; padding:12px 14px; }
.om-ent-sec-title { font-size:11px; font-weight:700; color:#1E40AF; text-transform:uppercase; letter-spacing:1px; margin-bottom:10px; display:flex; align-items:center; }

/* WARN */
.om-warn { font-size:11px; color:#D97706; background:#FFFBEB; padding:5px 9px; border-radius:5px; margin-top:6px; }

/* AUDITEURS */
.om-aud-list { display:flex; flex-direction:column; gap:6px; }
.om-aud-row  { display:flex; align-items:center; gap:8px; background:#fff; border:1px solid #E2E8F0; border-radius:7px; padding:7px 10px; }
.om-aud-av   { width:28px; height:28px; border-radius:50%; background:#059669; color:#fff; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:800; flex-shrink:0; }
.om-aud-info { display:flex; flex-direction:column; flex:1; min-width:0; }
.om-aud-name { font-size:11.5px; font-weight:600; color:#0F172A; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.om-aud-code { font-size:10px; color:#94A3B8; font-family:monospace; }

/* EMPTY */
.om-empty    { text-align:center; padding:24px 16px; color:#94A3B8; font-size:13px; }
.om-empty-icon { font-size:30px; margin-bottom:7px; }
.om-empty-sm { text-align:center; padding:10px; color:#CBD5E1; font-size:11px; }

/* DOCUMENTS */
.om-doc-upload {
    border:2px dashed #CBD5E1; border-radius:8px; padding:18px; text-align:center;
    cursor:pointer; transition:all .2s; margin-bottom:10px;
}
.om-doc-upload:hover { border-color:#1E40AF; background:#EFF6FF; }
.om-doc-upload-icon { font-size:28px; color:#CBD5E1; margin-bottom:5px; font-weight:300; }
.om-doc-upload-txt  { font-size:12px; font-weight:600; color:#475569; }
.om-doc-upload-sub  { font-size:10.5px; color:#94A3B8; margin-top:2px; }

.om-doc-list { display:flex; flex-direction:column; gap:5px; }
.om-doc-row  { display:flex; align-items:center; gap:9px; background:#fff; border:1px solid #E2E8F0; border-radius:6px; padding:7px 10px; }
.om-doc-icon { background:#1E40AF; color:#fff; font-size:8px; font-weight:700; padding:2px 5px; border-radius:3px; font-family:monospace; flex-shrink:0; }
.om-doc-info { display:flex; flex-direction:column; flex:1; min-width:0; }
.om-doc-name { font-size:11.5px; color:#0F172A; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.om-doc-size { font-size:10px; color:#94A3B8; }

/* LOADING */
.om-loading { font-size:11px; color:#1E40AF; padding:5px; }

/* ACTION BAR */
.om-action-bar { display:flex; justify-content:space-between; align-items:center; padding:14px 22px; background:#fff; border-radius:10px; box-shadow:0 -2px 14px rgba(0,0,0,.05); margin-top:20px; }
.om-btn { display:inline-flex; align-items:center; gap:7px; padding:9px 20px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; border:none; font-family:inherit; text-decoration:none; transition:all .2s; }
.om-ghost   { background:transparent; color:#64748B; border:1.5px solid #E2E8F0; }
.om-ghost:hover { background:#F1F5F9; }
.om-outline { background:#fff; color:#1E40AF; border:2px solid #1E40AF; }
.om-outline:hover { background:#EFF6FF; }
.om-primary { background:#1E40AF; color:#fff; }
.om-primary:hover { background:#1D4ED8; box-shadow:0 4px 14px rgba(30,64,175,.3); }

/* TRANSITIONS */
.slide-enter-active, .slide-leave-active { transition:all .25s ease; }
.slide-enter-from { opacity:0; transform:translateY(-10px); }
.slide-leave-to   { opacity:0; transform:translateX(20px); }

/* SCROLLBAR */
.om-aud-list::-webkit-scrollbar { width:4px; }
.om-aud-list::-webkit-scrollbar-track { background:#F1F5F9; }
.om-aud-list::-webkit-scrollbar-thumb { background:#CBD5E1; border-radius:10px; }

.sr-only { position:absolute; width:1px; height:1px; clip:rect(0,0,0,0); }

@media (max-width:1100px) { .om-layout { grid-template-columns:1fr; } }
@media (max-width:640px)  { .om-form { padding:14px; } .om-row2,.om-row3 { grid-template-columns:1fr; } }
</style>