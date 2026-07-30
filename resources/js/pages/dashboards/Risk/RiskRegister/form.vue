<template>
  <VerticalLayout>
    <div class="page">

      <!-- HEADER -->
      <div class="page-hdr">
        <div class="hdr-left">
          <Link :href="route('risk.core.risks.index')" class="hdr-back" title="Retour au registre"><i class="ti ti-arrow-left"></i></Link>
          <div class="hdr-icon"><i class="ti ti-shield-plus"></i></div>
          <div>
            <h1>{{ isEdit ? `Modifier — ${risk?.code_risk}` : 'Nouveau risque' }}</h1>
            <p v-if="fromIncident"><i class="ti ti-link"></i> Converti depuis l'incident {{ fromIncident.code_origine }}</p>
            <p v-else>Enregistrer un risque dans le registre</p>
          </div>
        </div>
        <div class="hdr-right">
          <LibraryImportPicker v-if="!isEdit" @apply="applyLibraryImport" />
          <MistralRiskSuggester
            :secteur="secteurEntite"
            :activite-code="actCtx.activity_code"
            :activite-nom="actCtx.activity_name"
            :processus-code="actCtx.process_code"
            :processus-nom="actCtx.process_name"
            :macro-processus="actCtx.macro_name"
            :nomenclature-domaine="nomenCtx.domaine"
            :nomenclature-famille="nomenCtx.famille"
            :nomenclature-type="nomenCtx.type"
            @apply="applyMistral"
          />
        </div>
      </div>

      <form @submit.prevent="submitForm" class="form-wrap">
        <div class="form-grid">

          <!-- ── COLONNE PRINCIPALE ────────────────────────────── -->
          <div class="form-main">

            <!-- 1. Localisation -->
            <div class="card">
              <div class="card-hdr"><i class="ti ti-sitemap"></i> Localisation du risque</div>
              <div class="card-body">
                <ActivityTreePicker
                  :entities="entities"
                  v-model:model-entity-id="form.entity_id"
                  v-model:model-activity-id="form.activity_id"
                  :error="errors.entity_id || errors.activity_id"
                  @selected="onActivitySelected"
                />
                <div class="fld">
                  <label>Type de risque (nomenclature) <span class="req">*</span></label>
                  <select v-model="form.nomenclature_id" :class="['inp', errors.nomenclature_id ? 'inp--err' : '']" @change="onNomenclatureChange">
                    <option value="">— Sélectionner un type —</option>
                    <option v-for="n in nomenclatures" :key="n.id" :value="n.id">{{ n.label }}</option>
                  </select>
                  <div v-if="errors.nomenclature_id" class="err">{{ errors.nomenclature_id }}</div>
                </div>
              </div>
            </div>

            <!-- 2. Identification -->
            <div class="card">
              <div class="card-hdr"><i class="ti ti-id"></i> Identification</div>
              <div class="card-body">
                <div class="fld">
                  <label>Libellé <span class="req">*</span></label>
                  <input id="field-libelle" v-model="form.libelle" type="text" :class="['inp', errors.libelle ? 'inp--err' : '']" placeholder="Intitulé du risque…" maxlength="255" />
                  <div v-if="errors.libelle" class="err">{{ errors.libelle }}</div>
                </div>
                <div class="fld">
                  <label>Description</label>
                  <textarea v-model="form.description" class="inp" rows="2" placeholder="Description générale…"></textarea>
                </div>
              </div>
            </div>

            <!-- 3. Contexte & parties prenantes -->
            <div class="card">
              <div class="card-hdr"><i class="ti ti-users"></i> Contexte &amp; parties prenantes</div>
              <div class="card-body">
                <div class="fld">
                  <label>Entité partenaire impliquée</label>
                  <textarea v-model="form.entite_partenaire_impliquee" class="inp" rows="2" placeholder="Entité(s) partenaire(s) concernée(s)…"></textarea>
                </div>
                <div class="fld">
                  <label>Outils utilisés</label>
                  <textarea v-model="form.outils_utilises" class="inp" rows="2" placeholder="Logiciels, systèmes ou outils impliqués…"></textarea>
                </div>
                <div class="row2">
                  <div class="fld">
                    <label>Vraisemblance d'apparition</label>
                    <input v-model="form.vraisemblance_apparition" type="text" class="inp" placeholder="Ex : Rare, Occasionnel, Fréquent…" maxlength="255" />
                  </div>
                  <div class="fld">
                    <label>Critère du risque</label>
                    <input v-model="form.critere_risque" type="text" class="inp" placeholder="Critère de référence applicable…" maxlength="255" />
                  </div>
                </div>
              </div>
            </div>

            <!-- 4. Analyse du risque -->
            <div class="card">
              <div class="card-hdr"><i class="ti ti-clipboard-text"></i> Analyse du risque</div>
              <div class="card-body">
                <div class="fld">
                  <label>Causes</label>
                  <textarea v-model="form.causes" class="inp" rows="3" placeholder="Quelles sont les causes potentielles de ce risque ?"></textarea>
                </div>
                <div class="fld">
                  <label>Conséquences / impacts</label>
                  <textarea v-model="form.consequences" class="inp" rows="3" placeholder="Quels seraient les impacts si ce risque se matérialisait ?"></textarea>
                </div>
                <div class="fld">
                  <label>Conséquences sur d'autres processus</label>
                  <textarea v-model="form.consequences_autres_processus" class="inp" rows="2" placeholder="Processus impactés en cascade…"></textarea>
                </div>
                <div class="fld">
                  <label>Coût des conséquences</label>
                  <textarea v-model="form.cout_consequences" class="inp" rows="2" placeholder="Estimation qualitative ou quantitative…"></textarea>
                </div>
                <div class="fld">
                  <label>Contrôles existants</label>
                  <textarea v-model="form.controles_existants" class="inp" rows="3" placeholder="Dispositifs de maîtrise déjà en place…"></textarea>
                </div>
                <div class="fld">
                  <label>Plan de traitement</label>
                  <textarea v-model="form.plan_traitement" class="inp" rows="3" placeholder="Actions prévues pour réduire ou traiter ce risque…"></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- ── COLONNE LATÉRALE ──────────────────────────────── -->
          <div class="form-side">
            <div class="card">
              <div class="card-hdr"><i class="ti ti-settings"></i> Gestion</div>
              <div class="card-body">
                <div class="fld">
                  <label>Responsable (owner)</label>
                  <input v-model="form.owner" type="text" class="inp" placeholder="Nom ou fonction…" />
                </div>
                <div class="fld">
                  <label>Statut</label>
                  <select v-model="form.statut" class="inp">
                    <option value="draft">Brouillon</option>
                    <option value="actif">Actif</option>
                  </select>
                </div>
                <div class="fld">
                  <label class="switch">
                    <input id="risque_realise" v-model="form.risque_realise" type="checkbox" />
                    <span>Risque réalisé</span>
                  </label>
                  <span class="hint">Cocher si le risque s'est effectivement matérialisé.</span>
                </div>
                <div class="fld">
                  <label>Coût du risque</label>
                  <div class="cost-grp">
                    <input v-model="form.cout_risque" type="number" step="0.01" min="0" class="inp" placeholder="0.00" />
                    <span class="cost-suffix">€</span>
                  </div>
                </div>
                <div v-if="form.incident_id" class="link-inc">
                  <i class="ti ti-link"></i> Lié à l'incident {{ fromIncident?.code_origine }}
                </div>

                <div class="side-actions">
                  <button type="submit" class="btn-primary" :disabled="submitting">
                    <i :class="submitting ? 'ti ti-loader-2 spin' : 'ti ti-check'"></i>
                    {{ isEdit ? 'Enregistrer les modifications' : 'Créer le risque' }}
                  </button>
                  <Link :href="route('risk.core.risks.index')" class="btn-ghost"><i class="ti ti-x"></i> Annuler</Link>
                </div>
              </div>
            </div>
          </div>

        </div>
      </form>
    </div>
  </VerticalLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
import ActivityTreePicker from './activitytreepicker.vue'
import MistralRiskSuggester from './mistralrisksuggester.vue'
import LibraryImportPicker from './LibraryImportPicker.vue'

// ── Props ──────────────────────────────────────────────────────────────────
const props = defineProps({
    risk:          { type: Object, default: null },
    entities:      { type: Array,  default: () => [] },
    nomenclatures: { type: Array,  default: () => [] },
    fromIncident:  { type: Object, default: null },
})

const isEdit = computed(() => !!props.risk)

// ── Formulaire ─────────────────────────────────────────────────────────────
const form = ref({
    libelle:                       props.risk?.libelle                       ?? props.fromIncident?.libelle      ?? '',
    description:                   props.risk?.description                   ?? props.fromIncident?.description  ?? '',
    entity_id:                     props.risk?.entity_id                     ?? '',
    activity_id:                   props.risk?.activity_id                   ?? '',
    nomenclature_id:               props.risk?.nomenclature_id               ?? '',
    causes:                        props.risk?.causes                        ?? '',
    consequences:                  props.risk?.consequences                  ?? '',
    consequences_autres_processus: props.risk?.consequences_autres_processus ?? '',
    cout_consequences:             props.risk?.cout_consequences             ?? '',
    controles_existants:           props.risk?.controles_existants           ?? '',
    owner:                         props.risk?.owner                         ?? '',
    entite_partenaire_impliquee:   props.risk?.entite_partenaire_impliquee   ?? '',
    outils_utilises:              props.risk?.outils_utilises              ?? '',
    vraisemblance_apparition:      props.risk?.vraisemblance_apparition      ?? '',
    plan_traitement:               props.risk?.plan_traitement               ?? '',
    critere_risque:                props.risk?.critere_risque                ?? '',
    statut:                        props.risk?.statut                        ?? 'draft',
    risque_realise:                props.risk?.risque_realise                ?? false,
    cout_risque:                   props.risk?.cout_risque                   ?? null,
    incident_id:                   props.risk?.incident_id                   ?? props.fromIncident?.incident_id ?? null,
})

const errors     = ref({})
const submitting = ref(false)

// ── Contexte activité (alimenté par ActivityTreePicker @selected) ──────────
const actCtx = ref({
    activity_code: props.risk?.activity_code    ?? '',
    activity_name: props.risk?.activity_name    ?? '',
    process_code:  props.risk?.process_code     ?? '',
    process_name:  props.risk?.process_name     ?? '',
    macro_name:    props.risk?.macro_process_name
        ? `${props.risk.macro_process_code} — ${props.risk.macro_process_name}`
        : '',
})

function onActivitySelected(payload) {
    if (!payload) {
        form.value.activity_id = ''
        actCtx.value = { activity_code: '', activity_name: '', process_code: '', process_name: '', macro_name: '' }
        return
    }
    form.value.activity_id = payload.activity.id
    actCtx.value = {
        activity_code: payload.activity.code,
        activity_name: payload.activity.name,
        process_code:  payload.process.code,
        process_name:  payload.process.name,
        macro_name:    `${payload.macro.code} — ${payload.macro.name}`,
    }
}

// ── Secteur de l'entité ────────────────────────────────────────────────────
const secteurEntite = computed(() => {
    if (!form.value.entity_id) return ''
    const e = props.entities.find(e => e.id === form.value.entity_id)
    return e?.secteur ?? e?.description ?? e?.name ?? ''
})

// ── Contexte nomenclature pour Mistral ────────────────────────────────────
const nomenCtx = ref({ domaine: '', famille: '', type: '' })

function onNomenclatureChange() {
    const n = props.nomenclatures.find(n => n.id === form.value.nomenclature_id)
    if (!n) { nomenCtx.value = { domaine: '', famille: '', type: '' }; return }
    nomenCtx.value = {
        domaine: n.level === 1 ? n.label : '',
        famille: n.level === 2 ? n.label : '',
        type:    n.level === 3 ? n.label : n.label,
    }
}

// ── Application suggestion Mistral ────────────────────────────────────────
function applyMistral(suggestion) {
    form.value.libelle             = suggestion.libelle
    form.value.causes              = suggestion.causes
    form.value.consequences        = suggestion.consequences
    form.value.controles_existants = suggestion.controles_existants
    form.value.plan_traitement     = suggestion.plan_traitement
    nextTick(() => {
        document.getElementById('field-libelle')?.scrollIntoView({ behavior: 'smooth', block: 'center' })
    })
}

// ── Application import depuis bibliothèque ────────────────────────────────
function applyLibraryImport(item) {
    form.value.libelle                       = item.libelle                       ?? form.value.libelle
    form.value.description                   = item.description                   ?? form.value.description
    form.value.causes                        = item.causes                        ?? form.value.causes
    form.value.consequences                  = item.consequences                  ?? form.value.consequences
    form.value.consequences_autres_processus = item.consequences_autres_processus ?? form.value.consequences_autres_processus
    form.value.cout_consequences             = item.cout_consequences             ?? form.value.cout_consequences
    form.value.controles_existants           = item.controles_existants           ?? form.value.controles_existants
    form.value.plan_traitement               = item.plan_traitement               ?? form.value.plan_traitement
    form.value.critere_risque                = item.critere_risque                ?? form.value.critere_risque
    form.value.entite_partenaire_impliquee   = item.entite_partenaire_impliquee   ?? form.value.entite_partenaire_impliquee
    form.value.outils_utilises              = item.outils_utilises              ?? form.value.outils_utilises
    form.value.vraisemblance_apparition      = item.vraisemblance_apparition      ?? form.value.vraisemblance_apparition
    form.value.owner                         = item.owner                         ?? form.value.owner

    // Nomenclature — uniquement si non déjà sélectionnée
    if (!form.value.nomenclature_id && item.nomenclature_id) {
        form.value.nomenclature_id = item.nomenclature_id
        nextTick(() => onNomenclatureChange())
    }

    // Coût — uniquement pour les incidents (montant évalué)
    if (item._type === 'incident' && item.cout_risque) {
        form.value.cout_risque = item.cout_risque
    }

    nextTick(() => {
        document.getElementById('field-libelle')?.scrollIntoView({ behavior: 'smooth', block: 'center' })
    })
}

// ── Soumission ─────────────────────────────────────────────────────────────
function submitForm() {
    submitting.value = true
    errors.value     = {}

    const url    = isEdit.value ? route('risk.core.risks.update', props.risk.id) : route('risk.core.risks.store')
    const method = isEdit.value ? 'put' : 'post'

    router[method](url, form.value, {
        onError:  (e) => { errors.value = e },
        onFinish: () => { submitting.value = false },
    })
}

// ── Init ───────────────────────────────────────────────────────────────────
onMounted(() => {
    if (form.value.nomenclature_id) {
        onNomenclatureChange()
    }
})
</script>

<style scoped>
.page{background:#f0f4f8;min-height:calc(100vh - 60px);font-family:'Inter',system-ui,sans-serif;font-size:13px;color:#1e293b;padding-bottom:36px;}

/* HEADER */
.page-hdr{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 22px;background:#0f172a;flex-wrap:wrap;position:sticky;top:0;z-index:20;}
.hdr-left{display:flex;align-items:center;gap:12px;}
.hdr-back{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.07);color:#c8d6e5;border:1px solid rgba(255,255,255,.12);text-decoration:none;font-size:16px;flex-shrink:0;}
.hdr-back:hover{background:rgba(255,255,255,.14);}
.hdr-icon{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:21px;color:#fff;background:linear-gradient(135deg,#4f46e5,#7c3aed);flex-shrink:0;}
.page-hdr h1{font-size:16px;font-weight:800;color:#f1f5f9;margin:0;}
.page-hdr p{font-size:11px;color:#64748b;margin:0;display:flex;align-items:center;gap:4px;}
.hdr-right{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}

/* GRID */
.form-wrap{padding:18px 22px;}
.form-grid{display:grid;grid-template-columns:minmax(0,1fr) 330px;gap:16px;align-items:start;}
@media(max-width:900px){.form-grid{grid-template-columns:1fr;}}
.form-main{display:flex;flex-direction:column;gap:16px;min-width:0;}
.form-side{position:sticky;top:78px;}

/* CARD */
.card{background:#fff;border:1px solid #e9eef5;border-radius:14px;overflow:hidden;}
.card-hdr{display:flex;align-items:center;gap:7px;padding:11px 16px;background:#f8fafc;border-bottom:1px solid #eef2f7;font-size:11px;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em;}
.card-hdr i{color:#4f46e5;font-size:14px;}
.card-body{padding:16px;display:flex;flex-direction:column;gap:14px;}

/* FIELDS */
.row2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
@media(max-width:560px){.row2{grid-template-columns:1fr;}}
.fld{display:flex;flex-direction:column;gap:5px;min-width:0;}
.fld label{font-size:11.5px;font-weight:700;color:#475569;}
.req{color:#dc2626;}
.inp{width:100%;padding:8px 11px;border:1px solid #e2e8f0;border-radius:9px;font-size:13px;font-family:inherit;color:#0f172a;background:#fff;transition:border-color .12s,box-shadow .12s;}
textarea.inp{resize:vertical;line-height:1.5;}
.inp:focus{outline:none;border-color:#a5b4fc;box-shadow:0 0 0 3px #c7d2fe55;}
.inp--err{border-color:#fca5a5;background:#fef2f2;}
.err{font-size:11px;color:#dc2626;font-weight:600;}
.hint{font-size:10.5px;color:#94a3b8;}

/* SIDEBAR SPECIFICS */
.switch{display:flex;align-items:center;gap:9px;cursor:pointer;}
.switch input{width:16px;height:16px;accent-color:#4f46e5;cursor:pointer;}
.switch span{font-size:12.5px;font-weight:700;color:#334155;}
.cost-grp{display:flex;align-items:stretch;}
.cost-grp .inp{border-radius:9px 0 0 9px;border-right:none;}
.cost-suffix{display:flex;align-items:center;padding:0 13px;background:#f1f5f9;border:1px solid #e2e8f0;border-left:none;border-radius:0 9px 9px 0;color:#64748b;font-weight:800;}
.link-inc{display:flex;align-items:center;gap:6px;font-size:11px;font-weight:600;color:#1e40af;background:#eff6ff;border:1px solid #bfdbfe;border-radius:9px;padding:8px 11px;}

.side-actions{display:flex;flex-direction:column;gap:8px;margin-top:6px;padding-top:12px;border-top:1px solid #eef2f7;}
.btn-primary{display:flex;align-items:center;justify-content:center;gap:6px;padding:10px 16px;background:#4f46e5;color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;}
.btn-primary:hover:not(:disabled){background:#4338ca;}
.btn-primary:disabled{opacity:.55;cursor:not-allowed;}
.btn-ghost{display:flex;align-items:center;justify-content:center;gap:6px;padding:9px 16px;background:#fff;color:#475569;border:1px solid #e2e8f0;border-radius:9px;font-size:12.5px;font-weight:600;cursor:pointer;text-decoration:none;}
.btn-ghost:hover{background:#f8fafc;}
.spin{display:inline-block;animation:spin .7s linear infinite;}@keyframes spin{to{transform:rotate(360deg);}}
</style>
