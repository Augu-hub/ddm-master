<template>
  <VerticalLayoutAudit>
    <div class="gpf-shell">

      <!-- ══ HEADER sticky — thémé par le type d'audit ══ -->
      <header class="gpf-header" :style="`--fc:${mc};--fcl:${mc}18;--fcm:${mc}35`">
        <div class="gpf-hrow">
          <a :href="props.backUrl" class="gpf-back" title="Retour aux phases">
            <i class="ti ti-arrow-left"></i>
          </a>

          <div class="gpf-hinfo">
            <div class="gpf-chips">
              <code class="gpf-code" :style="`color:${mc};background:${mc}15;border-color:${mc}30`">
                {{ mission?.code_mission ?? '—' }}
              </code>
              <span class="gpf-phasechip" :style="`color:${mc};background:${mc}12`">
                <i class="ti ti-git-branch"></i>
                Phase {{ formMeta?.phase_num }} · {{ formMeta?.phase_label }}
              </span>
              <span v-if="record?.status" class="gpf-vstchip" :class="`gvsc-${record.status}`">
                <i :class="stIcon(record.status)"></i> {{ stLbl(record.status) }}
              </span>
              <span v-if="props.auditorRole" class="gpf-rolechip" :class="`rc-${props.auditorRole}`">
                <i class="ti ti-shield-half"></i> {{ props.auditorRole }}
              </span>
            </div>
            <h1 class="gpf-htitle">
              <i :class="formMeta?.icon || 'ti ti-file-description'" :style="`color:${mc}`"></i>
              {{ formMeta?.label ?? 'Formulaire' }}
            </h1>
            <div class="gpf-hmeta">
              <span v-if="mission?.audit_type_label"><i class="ti ti-tag"></i>{{ mission.audit_type_label }}</span>
              <span v-if="mission?.entity_name"><i class="ti ti-building"></i>{{ mission.entity_name }}</span>
              <span v-if="formMeta?.norme"><i class="ti ti-certificate"></i>{{ formMeta.norme }}</span>
            </div>
          </div>
        </div>

        <!-- Bandeaux d'état -->
        <div v-if="record?.status === 'validated'" class="gpf-hbanner gpf-hbanner-lock">
          <i class="ti ti-lock"></i>
          <span>Formulaire <strong>validé définitivement</strong> par le DM — lecture seule</span>
        </div>
        <div v-else-if="record?.status === 'submitted'" class="gpf-hbanner gpf-hbanner-review">
          <i class="ti ti-clock"></i>
          <span>Soumis pour validation — en attente DM. <span v-if="canManage">Vous pouvez valider ou rejeter.</span></span>
        </div>
        <div v-else-if="record?.validation_note" class="gpf-hbanner gpf-hbanner-reject">
          <i class="ti ti-corner-up-left"></i>
          <span>Rejeté précédemment : <strong>{{ record.validation_note }}</strong> — corrigez puis resoumettez.</span>
        </div>
        <div v-if="props.noMission" class="gpf-hbanner gpf-hbanner-warn">
          <i class="ti ti-alert-triangle"></i>
          <span>Ouvrez ce formulaire depuis les phases d'une mission.
            <a :href="props.backUrl" class="gpf-link">Mes missions</a></span>
        </div>
        <div v-if="props.phaseNotStarted" class="gpf-hbanner gpf-hbanner-warn">
          <i class="ti ti-player-pause"></i>
          <span>Cette phase n'est pas encore démarrée. Démarrez-la depuis les phases de mission.</span>
        </div>
      </header>

      <!-- ══ BODY ══ -->
      <div v-if="!props.noMission && !props.phaseNotStarted" class="gpf-body">
        <div class="gpf-main">

          <!-- Description du formulaire (référentiel) -->
          <section v-if="formMeta?.description" class="gpf-card gpf-desc-card" :style="`border-left:3px solid ${mc}`">
            <i class="ti ti-info-circle" :style="`color:${mc}`"></i>
            <p>{{ formMeta.description }}</p>
          </section>

          <!-- Sections de travail -->
          <section class="gpf-card">
            <div class="gpf-clabel" :style="`color:${mc};border-color:${mc}25`">
              <i class="ti ti-layout-list"></i> Sections de travail
              <span class="gpf-cnt">{{ form.sections.length }}</span>
            </div>
            <div class="gpf-cbody">
              <p v-if="!form.sections.length" class="gpf-empty-hint">
                Structurez votre travail en sections (constats, analyses, vérifications…).
              </p>
              <div v-for="(s, i) in form.sections" :key="i" class="gpf-section">
                <div class="gpf-sec-head">
                  <span class="gpf-sec-n" :style="`background:${mc}12;color:${mc}`">{{ i + 1 }}</span>
                  <input v-model="s.titre" type="text" class="gpf-sec-titre"
                    placeholder="Titre de la section…" :disabled="isLocked" />
                  <button v-if="!isLocked" type="button" class="gpf-row-del"
                    @click="form.sections.splice(i, 1)">
                    <i class="ti ti-trash"></i>
                  </button>
                </div>
                <textarea v-model="s.contenu" class="gpf-sec-contenu" rows="4"
                  placeholder="Contenu, constats, analyse…" :disabled="isLocked"></textarea>
              </div>
              <button v-if="!isLocked" type="button" class="gpf-add" :style="`color:${mc};border-color:${mc}35`"
                @click="form.sections.push({ titre: '', contenu: '' })">
                <i class="ti ti-plus"></i> Ajouter une section
              </button>
            </div>
          </section>

          <!-- Notes libres -->
          <section class="gpf-card">
            <div class="gpf-clabel" :style="`color:${mc};border-color:${mc}25`">
              <i class="ti ti-notes"></i> Notes complémentaires
            </div>
            <div class="gpf-cbody">
              <textarea v-model="form.notes" rows="4" class="gpf-notes"
                placeholder="Observations, points d'attention, références…" :disabled="isLocked"></textarea>
            </div>
          </section>

          <!-- Note de bas de page -->
          <p class="gpf-generic-note">
            <i class="ti ti-bolt"></i>
            Écran générique piloté par le référentiel central ({{ formMeta?.audit_type_code }} ·
            {{ formMeta?.code }}) — un écran métier dédié pourra le remplacer sans migration de données.
          </p>
        </div>

        <!-- ══ BARRE D'ACTIONS ══ -->
        <div class="gpf-actions">
          <div class="gpf-actions-left">
            <span v-if="record?.version" class="gpf-version">
              <i class="ti ti-versions"></i> v{{ record.version }}
            </span>
          </div>
          <div class="gpf-actions-right">
            <button v-if="!isLocked" type="button"
              class="gpf-btn gpf-btn-save" :style="`background:${mc}`" :disabled="processing" @click="save">
              <i :class="processing ? 'ti ti-loader-2 gpf-spin' : 'ti ti-device-floppy'"></i>
              Enregistrer
            </button>
            <button v-if="record?.id && record.status === 'draft'"
              type="button" class="gpf-btn gpf-btn-submitrev" :disabled="processing" @click="soumettre">
              <i class="ti ti-send"></i> Soumettre au DM
            </button>
            <template v-if="canManage && record?.status === 'submitted'">
              <button type="button" class="gpf-btn gpf-btn-reject" :disabled="processing" @click="promptReject">
                <i class="ti ti-x"></i> Rejeter
              </button>
              <button type="button" class="gpf-btn gpf-btn-validate" :disabled="processing" @click="valider('validate')">
                <i class="ti ti-shield-check"></i> Valider définitivement
              </button>
            </template>
          </div>
        </div>
      </div>

      <!-- ══ TOAST ══ -->
      <Teleport to="body">
        <transition name="gpf-toast">
          <div v-if="toast.show" class="gpf-toast" :class="`gpf-toast-${toast.type}`">
            <i :class="toast.type === 'success' ? 'ti ti-circle-check' : 'ti ti-circle-x'"></i>
            {{ toast.msg }}
          </div>
        </transition>
      </Teleport>

    </div>
  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
// ════════════════════════════════════════════════════════════════════
// FORMULAIRE GÉNÉRIQUE DE PHASE — servi par DynamicPhaseFormController
// pour tout formulaire du référentiel central sans écran dédié.
// Stockage : mission_phase_form_data (JSON sections + notes) avec le
// workflow complet draft → submitted → validated/rejected.
// ════════════════════════════════════════════════════════════════════
import { computed, reactive, ref } from 'vue'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'

const props = defineProps({
  mission:      { type: Object,  default: null },
  assignment:   { type: Object,  default: null },
  auditeurs:    { type: Array,   default: () => [] },
  auditorRole:  { type: String,  default: null },
  record:       { type: Object,  default: null },   // ligne mission_phase_form_data hydratée
  formMeta:     { type: Object,  default: null },   // formulaire ddmparam (label, phase, norme…)
  errors:       { type: Object,  default: () => ({}) },

  noMission:       { type: Boolean, default: false },
  phaseNotStarted: { type: Boolean, default: false },

  missionId:    { type: Number, default: null },
  assignmentId: { type: Number, default: null },
  missionMenu:  { type: Array,  default: () => [] }, // consommé par le sidebar

  backUrl:     { type: String, default: '' },
  formUrl:     { type: String, default: '' },
  chatBaseUrl: { type: String, default: '' },
})

const mc = computed<string>(() => {
  const c = (props.mission as any)?.audit_color || (props.formMeta as any)?.audit_color
  if (c && c !== '#000000' && c !== '#000' && c !== 'null') return c
  return '#2563eb'
})

const canManage = computed(() => ['DM', 'CM'].includes(props.auditorRole ?? ''))

const record = reactive<Record<string, any>>(props.record ? { ...props.record } : {})

const form = reactive({
  sections: ((props.record as any)?.sections ?? []) as Array<{ titre: string; contenu: string }>,
  notes:    ((props.record as any)?.notes ?? '') as string,
})

const processing = ref(false)

const isLocked = computed(() =>
  record.status === 'validated' ||
  (record.status === 'submitted' && !canManage.value)
)

// ── Actions ───────────────────────────────────────────────────────────────
async function save() {
  if (isLocked.value) return
  await apiPost(props.formUrl, {
    mission_id:    props.missionId,
    assignment_id: props.assignmentId,
    sections:      form.sections,
    notes:         form.notes,
  }, (json: any) => {
    if (json.record) Object.assign(record, json.record)
    showToast('success', 'Formulaire enregistré')
  })
}

async function soumettre() {
  if (!record.id) { showToast('error', 'Enregistrez d\'abord le formulaire.'); return }
  if (!confirm('Soumettre ce formulaire pour validation par le DM ?')) return
  await apiPost(`${props.formUrl}/soumettre`,
    { assignment_id: props.assignmentId },
    (json: any) => {
      record.status = json.status
      showToast('success', 'Formulaire soumis — en attente validation DM')
    })
}

async function valider(action: 'validate' | 'reject', note?: string) {
  await apiPost(`${props.formUrl}/valider`,
    { assignment_id: props.assignmentId, action, note },
    (json: any) => {
      record.status = json.status
      if (action === 'reject') record.validation_note = note
      showToast('success', action === 'validate' ? 'Formulaire validé ✓' : 'Formulaire rejeté — repassé en brouillon')
    })
}

function promptReject() {
  const note = prompt('Motif du rejet (obligatoire) :')
  if (!note?.trim()) return
  valider('reject', note)
}

async function apiPost(url: string, body: object, onOk: (json: any) => void) {
  processing.value = true
  try {
    const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
      body: JSON.stringify(body),
    })
    const json = await res.json()
    if (!res.ok) throw new Error(json?.message ?? json?.error ?? 'Erreur')
    onOk(json)
  } catch (e: any) {
    showToast('error', e.message)
  } finally {
    processing.value = false
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────
function stLbl(s: string) {
  return ({ draft: 'Brouillon', submitted: 'Soumis — en revue', validated: 'Validé', rejected: 'Rejeté' } as any)[s] ?? s
}
function stIcon(s: string) {
  return ({ draft: 'ti ti-pencil', submitted: 'ti ti-clock', validated: 'ti ti-lock', rejected: 'ti ti-x' } as any)[s] ?? 'ti ti-pencil'
}

const toast = ref({ show: false, type: 'success', msg: '' })
let toastTimer: ReturnType<typeof setTimeout>
function showToast(type: string, msg: string) {
  toast.value = { show: true, type, msg }
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { toast.value.show = false }, 4000)
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap');

* { box-sizing: border-box; }

.gpf-shell {
  font-family: 'Plus Jakarta Sans', sans-serif;
  min-height: calc(100vh - 68px);
  background: #f4f6f8;
  color: #1e293b;
}

/* ══ HEADER ══ */
.gpf-header {
  position: sticky; top: 0; z-index: 30;
  background: #fff; border-bottom: 1px solid #e2e8f0;
  box-shadow: 0 1px 8px rgba(15,23,42,.05);
}
.gpf-hrow { display: flex; align-items: center; gap: 14px; padding: 14px 22px 10px; }
.gpf-back {
  width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: var(--fcl); color: var(--fc); border: 1px solid var(--fcm);
  text-decoration: none; transition: all .15s;
}
.gpf-back:hover { background: var(--fc); color: #fff; }

.gpf-hinfo { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 4px; }
.gpf-chips { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.gpf-code {
  font-family: 'JetBrains Mono', monospace; font-size: .68rem; font-weight: 700;
  padding: 2px 8px; border-radius: 5px; border: 1px solid;
}
.gpf-phasechip {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: .62rem; font-weight: 800; padding: 2px 8px; border-radius: 12px;
}
.gpf-rolechip {
  display: inline-flex; align-items: center; gap: 3px;
  font-size: .6rem; font-weight: 800; padding: 2px 7px; border-radius: 10px;
}
.rc-DM { background: #fef3c7; color: #b45309; }
.rc-CM { background: #dbeafe; color: #1d4ed8; }
.rc-AS { background: #d1fae5; color: #047857; }
.rc-AJ { background: #ede9fe; color: #6d28d9; }

.gpf-vstchip {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: .6rem; font-weight: 800; padding: 2px 8px; border-radius: 12px;
  text-transform: uppercase; letter-spacing: .04em;
}
.gvsc-draft     { background: #f1f5f9; color: #64748b; }
.gvsc-submitted { background: #fef3c7; color: #b45309; }
.gvsc-validated { background: #d1fae5; color: #047857; }
.gvsc-rejected  { background: #fee2e2; color: #b91c1c; }

.gpf-htitle {
  margin: 0; font-size: 1.2rem; font-weight: 800;
  letter-spacing: -.02em; color: #0f172a;
  display: flex; align-items: center; gap: 8px;
}
.gpf-hmeta { display: flex; flex-wrap: wrap; gap: 12px; }
.gpf-hmeta span { display: inline-flex; align-items: center; gap: 4px; font-size: .7rem; color: #64748b; }

.gpf-hbanner {
  display: flex; align-items: center; gap: 8px;
  padding: 8px 22px; font-size: .74rem; font-weight: 600; border-top: 1px solid;
}
.gpf-hbanner-lock   { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
.gpf-hbanner-review { background: #fffbeb; color: #b45309; border-color: #fde68a; }
.gpf-hbanner-reject { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
.gpf-hbanner-warn   { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }
.gpf-link { color: inherit; text-decoration: underline; }

/* ══ BODY ══ */
.gpf-body { padding: 18px 22px 90px; }
.gpf-main { max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: 14px; }

.gpf-card {
  background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
  box-shadow: 0 1px 6px rgba(15,23,42,.04); overflow: hidden;
}
.gpf-desc-card {
  display: flex; gap: 10px; align-items: flex-start; padding: 12px 16px;
}
.gpf-desc-card i { font-size: 1rem; margin-top: 1px; }
.gpf-desc-card p { margin: 0; font-size: .76rem; color: #475569; line-height: 1.5; }

.gpf-clabel {
  display: flex; align-items: center; gap: 7px;
  padding: 11px 16px; font-size: .74rem; font-weight: 800;
  text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid;
}
.gpf-cnt {
  margin-left: auto; font-size: .62rem; font-weight: 800;
  background: #f1f5f9; color: #64748b; padding: 1px 8px; border-radius: 10px;
}
.gpf-cbody { padding: 14px 16px; display: flex; flex-direction: column; gap: 12px; }
.gpf-empty-hint { margin: 0; font-size: .72rem; color: #94a3b8; }

/* Sections */
.gpf-section {
  border: 1px solid #eef2f5; border-radius: 11px; padding: 10px 12px;
  display: flex; flex-direction: column; gap: 8px; background: #fbfcfd;
}
.gpf-sec-head { display: flex; align-items: center; gap: 8px; }
.gpf-sec-n {
  width: 26px; height: 26px; border-radius: 8px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: .66rem; font-weight: 800;
}
.gpf-sec-titre {
  flex: 1; padding: 7px 10px; border-radius: 9px;
  border: 1px solid #e2e8f0; font-size: .78rem; font-weight: 600;
  color: #0f172a; outline: none; font-family: inherit; min-width: 0;
}
.gpf-sec-titre:focus { border-color: var(--fc, #2563eb); }
.gpf-sec-contenu {
  width: 100%; resize: vertical; padding: 8px 10px; border-radius: 9px;
  border: 1px solid #e2e8f0; font-size: .76rem; color: #334155;
  outline: none; font-family: inherit; line-height: 1.5;
}
.gpf-sec-contenu:focus { border-color: var(--fc, #2563eb); }
.gpf-sec-titre:disabled, .gpf-sec-contenu:disabled, .gpf-notes:disabled {
  background: #f8fafc; color: #94a3b8; cursor: not-allowed;
}

.gpf-row-del {
  width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0;
  border: 1px solid #fee2e2; background: #fff; color: #dc2626;
  display: inline-flex; align-items: center; justify-content: center;
  cursor: pointer; font-size: .78rem; transition: all .12s;
}
.gpf-row-del:hover { background: #dc2626; color: #fff; }

.gpf-add {
  align-self: flex-start;
  display: inline-flex; align-items: center; gap: 5px;
  font-size: .7rem; font-weight: 700; padding: 6px 12px;
  border: 1px dashed; border-radius: 9px; background: transparent;
  cursor: pointer; transition: all .12s; font-family: inherit;
}
.gpf-add:hover { background: var(--fcl, #2563eb18); }

.gpf-notes {
  width: 100%; resize: vertical; padding: 9px 11px; border-radius: 10px;
  border: 1px solid #e2e8f0; font-size: .76rem; color: #334155;
  outline: none; font-family: inherit; line-height: 1.5;
}
.gpf-notes:focus { border-color: var(--fc, #2563eb); }

.gpf-generic-note {
  display: flex; align-items: center; gap: 6px;
  margin: 0; font-size: .66rem; color: #94a3b8;
}

/* Actions */
.gpf-actions {
  position: fixed; bottom: 0; left: 0; right: 0; z-index: 25;
  display: flex; align-items: center; justify-content: space-between; gap: 10px;
  padding: 10px 22px;
  background: rgba(255,255,255,.94); backdrop-filter: blur(8px);
  border-top: 1px solid #e2e8f0;
}
.gpf-version {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: .68rem; font-weight: 700; color: #94a3b8;
}
.gpf-actions-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.gpf-btn {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: .74rem; font-weight: 700; padding: 8px 16px;
  border-radius: 9px; border: 1px solid transparent;
  cursor: pointer; transition: all .13s; font-family: inherit;
}
.gpf-btn:disabled { opacity: .55; cursor: not-allowed; }
.gpf-btn-save { color: #fff; }
.gpf-btn-save:hover:not(:disabled) { filter: brightness(1.08); }
.gpf-btn-submitrev { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
.gpf-btn-submitrev:hover:not(:disabled) { background: #1d4ed8; color: #fff; }
.gpf-btn-validate { background: #ecfdf5; border-color: #a7f3d0; color: #047857; }
.gpf-btn-validate:hover:not(:disabled) { background: #047857; color: #fff; }
.gpf-btn-reject { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
.gpf-btn-reject:hover:not(:disabled) { background: #b91c1c; color: #fff; }

/* Toast */
.gpf-toast {
  position: fixed; bottom: 76px; right: 22px; z-index: 200;
  display: flex; align-items: center; gap: 8px;
  padding: 10px 16px; border-radius: 11px;
  font-size: .76rem; font-weight: 700;
  box-shadow: 0 8px 30px rgba(15,23,42,.18);
  font-family: 'Plus Jakarta Sans', sans-serif;
}
.gpf-toast-success { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
.gpf-toast-error   { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
.gpf-toast-enter-active, .gpf-toast-leave-active { transition: all .2s ease; }
.gpf-toast-enter-from, .gpf-toast-leave-to { opacity: 0; transform: translateY(8px); }

.gpf-spin { animation: gpf-rot .7s linear infinite; }
@keyframes gpf-rot { to { transform: rotate(360deg); } }
</style>
