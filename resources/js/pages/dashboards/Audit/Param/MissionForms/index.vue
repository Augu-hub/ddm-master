<template>
  <VerticalLayout>
    <!-- Page Header -->
    <PageTitle title="E-Wallet" subtitle="Dashboard" /> <!-- À remplacer si nécessaire, mais gardé pour la cohérence du layout -->

    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <div class="text-muted small">
          Audit Core <i class="ti ti-chevron-right"></i> Paramètres <i class="ti ti-chevron-right"></i>
          <span class="text-dark fw-medium">Formulaires par type d'audit</span>
        </div>
        <h2 class="page-title mb-0 d-flex align-items-center gap-2">
          <i class="ti ti-forms text-primary"></i>
          Formulaires de mission
        </h2>
      </div>
      <b-button variant="primary" @click="openAdd()">
        <i class="ti ti-plus me-1"></i> Nouveau formulaire
      </b-button>
    </div>

    <!-- Statistics -->
    <b-row class="mb-4">
      <b-col lg="4">
        <b-card no-body class="border">
          <b-card-body class="d-flex align-items-center gap-3">
            <div class="bg-primary bg-opacity-10 rounded p-2">
              <i class="ti ti-file-description fs-24 text-primary"></i>
            </div>
            <div>
              <h3 class="fw-bold mb-0">{{ stats.total_forms }}</h3>
              <p class="text-muted mb-0 small">Formulaires total</p>
            </div>
          </b-card-body>
        </b-card>
      </b-col>
      <b-col lg="4">
        <b-card no-body class="border">
          <b-card-body class="d-flex align-items-center gap-3">
            <div class="bg-success bg-opacity-10 rounded p-2">
              <i class="ti ti-shield-check fs-24 text-success"></i>
            </div>
            <div>
              <h3 class="fw-bold mb-0">{{ stats.total_audit_types }}</h3>
              <p class="text-muted mb-0 small">Types d'audit</p>
            </div>
          </b-card-body>
        </b-card>
      </b-col>
      <b-col lg="4">
        <b-card no-body class="border">
          <b-card-body class="d-flex align-items-center gap-3">
            <div class="bg-warning bg-opacity-10 rounded p-2">
              <i class="ti ti-clipboard-list fs-24 text-warning"></i>
            </div>
            <div>
              <h3 class="fw-bold mb-0">{{ stats.total_phases }}</h3>
              <p class="text-muted mb-0 small">Phases de mission</p>
            </div>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>

    <!-- Audit type sections -->
    <div v-for="atData in formsData" :key="atData.audit_type.id" class="mb-4">
      <b-card no-body class="border">
        <b-card-header class="d-flex align-items-center justify-content-between bg-light py-3">
          <div class="d-flex align-items-center gap-3">
            <span
              class="badge rounded-pill px-3 py-2"
              :style="{ backgroundColor: atData.audit_type.color + '20', color: atData.audit_type.color, borderColor: atData.audit_type.color }"
            >
              <i :class="atData.audit_type.icon" class="me-1"></i>
              {{ atData.audit_type.code }}
            </span>
            <div>
              <h5 class="mb-0">{{ atData.audit_type.label }}</h5>
              <small class="text-muted">{{ atData.total_forms }} formulaire(s)</small>
            </div>
          </div>
          <b-button size="sm" variant="outline-primary" @click="openAdd(atData.audit_type)">
            <i class="ti ti-plus me-1"></i> Ajouter
          </b-button>
        </b-card-header>

        <!-- Phases list -->
        <b-card-body class="p-0">
          <div v-for="phaseData in atData.by_phase" :key="phaseData.mission_type.id" class="border-bottom">
            <!-- Phase header (click to toggle) -->
            <div
              class="d-flex align-items-center justify-content-between p-3 cursor-pointer"
              :class="{ 'bg-light': openPhases[atData.audit_type.code + '-' + phaseData.mission_type.id] }"
              @click="togglePhase(atData.audit_type.code, phaseData.mission_type.id)"
            >
              <div class="d-flex align-items-center gap-3">
                <i
                  :class="['ti', openPhases[atData.audit_type.code + '-' + phaseData.mission_type.id] ? 'ti-chevron-down' : 'ti-chevron-right']"
                ></i>
                <span class="badge rounded-circle p-2" :style="{ backgroundColor: phaseData.mission_type.color }"></span>
                <i :class="phaseData.mission_type.icon" :style="{ color: phaseData.mission_type.color }"></i>
                <span class="fw-medium">{{ phaseData.mission_type.label }}</span>
                <span class="badge bg-light text-dark">{{ phaseData.forms_count }}</span>
              </div>
              <b-button
                size="sm"
                variant="ghost-secondary"
                class="p-0 border-0"
                @click.stop="openAdd(atData.audit_type, phaseData.mission_type)"
              >
                <i class="ti ti-plus"></i>
              </b-button>
            </div>

            <!-- Forms inside phase -->
            <div v-if="openPhases[atData.audit_type.code + '-' + phaseData.mission_type.id]" class="px-4 pb-3">
              <div v-if="phaseData.forms_count === 0" class="text-muted py-2">
                <i class="ti ti-file-off me-1"></i> Aucun formulaire —
                <a href="#" @click.prevent="openAdd(atData.audit_type, phaseData.mission_type)">Ajouter</a>
              </div>

              <div v-else class="border rounded mt-2">
                <!-- Level 1 forms -->
                <div v-for="form in phaseData.forms" :key="form.id" class="p-2 border-bottom">
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                      <i :class="form.icon" class="text-muted"></i>
                      <div>
                        <div class="fw-medium">{{ form.label }}</div>
                        <small class="text-muted d-flex align-items-center gap-1">
                          <i class="ti ti-link"></i> {{ form.url_path }}
                        </small>
                      </div>
                    </div>
                    <div class="d-flex gap-2">
                      <b-button
                        size="sm"
                        variant="ghost-secondary"
                        class="p-0 border-0"
                        @click="openAdd(atData.audit_type, phaseData.mission_type, form)"
                        title="Ajouter sous-formulaire"
                      >
                        <i class="ti ti-indent-increase"></i>
                      </b-button>
                      <b-button
                        size="sm"
                        variant="ghost-secondary"
                        class="p-0 border-0"
                        @click="openEdit(atData.audit_type, form)"
                      >
                        <i class="ti ti-edit"></i>
                      </b-button>
                      <b-button
                        size="sm"
                        variant="ghost-secondary"
                        class="p-0 border-0 text-danger"
                        @click="confirmDel(atData.audit_type, form)"
                      >
                        <i class="ti ti-trash"></i>
                      </b-button>
                    </div>
                  </div>

                  <!-- Sub-forms -->
                  <div v-if="form.children && form.children.length" class="mt-2 ms-4 ps-3 border-start">
                    <div v-for="sub in form.children" :key="sub.id" class="d-flex align-items-center justify-content-between py-1">
                      <div class="d-flex align-items-center gap-3">
                        <i class="ti ti-corner-down-right text-muted"></i>
                        <i :class="sub.icon" class="text-muted"></i>
                        <div>
                          <div class="fw-medium">{{ sub.label }}</div>
                          <small class="text-muted d-flex align-items-center gap-1">
                            <i class="ti ti-link"></i> {{ sub.url_path }}
                          </small>
                        </div>
                      </div>
                      <div class="d-flex gap-2">
                        <b-button
                          size="sm"
                          variant="ghost-secondary"
                          class="p-0 border-0"
                          @click="openEdit(atData.audit_type, sub)"
                        >
                          <i class="ti ti-edit"></i>
                        </b-button>
                        <b-button
                          size="sm"
                          variant="ghost-secondary"
                          class="p-0 border-0 text-danger"
                          @click="confirmDel(atData.audit_type, sub)"
                        >
                          <i class="ti ti-trash"></i>
                        </b-button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </b-card-body>
      </b-card>
    </div>

    <!-- Add/Edit Modal -->
    <b-modal v-model="modal.open" :title="modal.editId ? 'Modifier le formulaire' : 'Nouveau formulaire'" centered hide-footer>
      <b-form @submit.prevent="saveForm">
        <b-row>
          <b-col cols="12">
            <b-form-group label="Type d'audit *">
              <b-form-select
                v-model="modal.auditTypeCode"
                :options="auditTypeOptions"
                :disabled="!!modal.editId"
                @change="onAuditTypeChange"
              ></b-form-select>
              <div v-if="selectedAuditType" class="mt-2">
                <span
                  class="badge"
                  :style="{ backgroundColor: selectedAuditType.color + '20', color: selectedAuditType.color, borderColor: selectedAuditType.color }"
                >
                  <i :class="selectedAuditType.icon" class="me-1"></i> {{ selectedAuditType.label }}
                </span>
              </div>
            </b-form-group>
          </b-col>

          <b-col cols="12">
            <b-form-group label="Phase de mission *">
              <b-form-select
                v-model="modal.missionTypeId"
                :options="missionTypeOptions"
                :disabled="!!modal.editId"
                @change="onPhaseChange"
              ></b-form-select>
            </b-form-group>
          </b-col>

          <b-col cols="12" v-if="modal.auditTypeCode && modal.missionTypeId && !modal.editId && parentForms.length">
            <b-form-group label="Rattacher à (sous-formulaire optionnel)">
              <b-form-select v-model="modal.parentId" :options="parentFormOptions"></b-form-select>
            </b-form-group>
          </b-col>

          <b-col cols="12">
            <b-form-group label="Nom du formulaire *">
              <b-form-input v-model="modal.label" @input="computePreviewUrl" placeholder="Ex: Analyse du contrôle interne"></b-form-input>
            </b-form-group>
          </b-col>

          <!-- URL Preview -->
          <b-col cols="12" v-if="previewUrl">
            <div class="bg-light p-2 rounded small">
              <i class="ti ti-link text-primary me-1"></i>
              <span class="text-muted">{{ previewRoute }}</span><br />
              <span class="text-primary">{{ previewUrl }}</span>
            </div>
          </b-col>

          <b-col cols="6">
            <b-form-group label="Icône Tabler">
              <div class="d-flex align-items-center gap-2">
                <i :class="modal.icon || 'ti ti-file-description'" class="fs-20"></i>
                <b-form-input v-model="modal.icon" placeholder="ti ti-file-description"></b-form-input>
              </div>
            </b-form-group>
          </b-col>

          <b-col cols="6">
            <b-form-group label="Description">
              <b-form-input v-model="modal.description" placeholder="Optionnel"></b-form-input>
            </b-form-group>
          </b-col>
        </b-row>

        <div class="d-flex justify-content-end gap-2 mt-3">
          <b-button variant="light" @click="closeModal">Annuler</b-button>
          <b-button variant="primary" type="submit" :disabled="saving || !canSave">
            <i v-if="saving" class="ti ti-loader-2 spin me-1"></i>
            {{ modal.editId ? 'Modifier' : 'Créer' }}
          </b-button>
        </div>
      </b-form>
    </b-modal>

    <!-- Delete Modal -->
    <b-modal v-model="delModal.open" title="Supprimer le formulaire" centered hide-footer>
      <div class="alert alert-warning d-flex gap-2 mb-3">
        <i class="ti ti-alert-triangle fs-20"></i>
        <div>
          Supprimer <strong>{{ delModal.form?.label }}</strong> ?
          <template v-if="delModal.form?.children?.length">
            <br /><span class="small">Les {{ delModal.form.children.length }} sous-formulaire(s) seront aussi supprimés.</span>
          </template>
        </div>
      </div>
      <div class="d-flex justify-content-end gap-2">
        <b-button variant="light" @click="delModal.open = false">Annuler</b-button>
        <b-button variant="danger" :disabled="saving" @click="deleteForm">
          <i v-if="saving" class="ti ti-loader-2 spin me-1"></i> Supprimer
        </b-button>
      </div>
    </b-modal>

    <!-- Flash Toast -->
    <b-toast v-model="flash.show" :variant="flash.type === 'ok' ? 'success' : 'danger'" solid toaster="b-toaster-top-right">
      <div class="d-flex gap-2">
        <i :class="flash.type === 'ok' ? 'ti ti-circle-check' : 'ti ti-alert-circle'"></i>
        <span>{{ flash.msg }}</span>
      </div>
    </b-toast>
  </VerticalLayout>
</template>

<script setup lang="ts">
import { ref, computed, reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayout from '@/layouts/VerticalLayout.vue'
import PageTitle from '@/components/PageTitle.vue' // Si nécessaire, sinon à retirer

// Types
interface AuditType  { id:number; code:string; label:string; color:string; icon:string }
interface MissionType { id:number; code:string; label:string; color:string; icon:string; short_label:string }
interface Form {
  id:number; mission_type_id:number; parent_id:number|null;
  code:string; label:string; description:string|null;
  route_name:string; url_path:string; icon:string; sort_order:number; is_active:number;
  children?: Form[];
}
interface PhaseData { mission_type:MissionType; forms:Form[]; forms_count:number }
interface AtData    { audit_type:AuditType; by_phase:PhaseData[]; total_forms:number }
interface Stats     { total_forms:number; total_audit_types:number; total_phases:number }

const props = withDefaults(defineProps<{
  formsData?:    AtData[];
  missionTypes?: MissionType[];
  auditTypes?:   AuditType[];
  stats?:        Stats;
}>(), {
  formsData:    () => [],
  missionTypes: () => [],
  auditTypes:   () => [],
  stats:        () => ({ total_forms:0, total_audit_types:0, total_phases:0 }),
})

// Open phases state
const openPhases = reactive<Record<string,boolean>>({})
watch(() => props.formsData, (data) => {
  data.forEach(atd => {
    atd.by_phase.forEach(pd => {
      if (pd.forms_count > 0) {
        openPhases[atd.audit_type.code + '-' + pd.mission_type.id] = true
      }
    })
  })
}, { immediate: true })

const saving = ref(false)
const flash  = reactive({ show:false, type:'ok' as 'ok'|'err', msg:'' })
let flashTimer: ReturnType<typeof setTimeout>

function showFlash(msg:string, type:'ok'|'err'='ok') {
  clearTimeout(flashTimer)
  flash.msg = msg; flash.type = type; flash.show = true
  flashTimer = setTimeout(() => flash.show = false, 3500)
}

function togglePhase(atCode:string, mtId:number) {
  const key = atCode + '-' + mtId
  openPhases[key] = !openPhases[key]
}

// Computed options
const auditTypeOptions = computed(() => [
  { value: '', text: '— Choisir —' },
  ...props.auditTypes.map(at => ({ value: at.code.toLowerCase(), text: `${at.code} — ${at.label}` }))
])

const missionTypeOptions = computed(() => [
  { value: 0, text: '— Choisir une phase —' },
  ...props.missionTypes.map(mt => ({ value: mt.id, text: mt.label }))
])

const selectedAuditType = computed(() =>
  props.auditTypes.find(at => at.code.toLowerCase() === modal.auditTypeCode) ?? null
)

const parentForms = computed<Form[]>(() => {
  if (!modal.auditTypeCode || !modal.missionTypeId) return []
  const atData = props.formsData.find(d => d.audit_type.code.toLowerCase() === modal.auditTypeCode)
  if (!atData) return []
  const pd = atData.by_phase.find(p => p.mission_type.id === modal.missionTypeId)
  return pd?.forms ?? []
})

const parentFormOptions = computed(() => [
  { value: null, text: '— Formulaire principal —' },
  ...parentForms.value.map(pf => ({ value: pf.id, text: pf.label }))
])

// URL preview
const previewUrl   = ref('')
const previewRoute = ref('')

function toSlug(label:string): string {
  const map: Record<string,string> = {
    'à':'a','â':'a','é':'e','è':'e','ê':'e','ë':'e','î':'i','ï':'i',
    'ô':'o','ù':'u','û':'u','ç':'c','œ':'oe',"'":'', '\u2019':''
  }
  let s = label.toLowerCase().trim()
  s = s.replace(/[àâéèêëîïôùûçœ']/g, c => map[c] ?? '')
  s = s.replace(/[^a-z0-9\s-]/g, '')
  s = s.replace(/[\s-]+/g, '-').trim()
  return s.substring(0, 80)
}

function computePreviewUrl() {
  if (!modal.auditTypeCode || !modal.missionTypeId || !modal.label.trim()) {
    previewUrl.value = ''; previewRoute.value = ''; return
  }
  const mt = props.missionTypes.find(m => m.id === modal.missionTypeId)
  if (!mt) return
  const slug = toSlug(modal.label)
  const atCode    = modal.auditTypeCode
  const phaseCode = mt.code.toLowerCase()
  previewUrl.value   = `/m/audit.core/${atCode}/${phaseCode}/${slug}`
  previewRoute.value = `audit.${atCode}.${phaseCode}.${slug}`
}

// Modal state
const modal = reactive({
  open: false, editId: null as number|null,
  auditTypeCode: '', missionTypeId: 0 as number,
  parentId: null as number|null,
  label: '', description: '', icon: 'ti ti-file-description',
  _editAtCode: '', // original code for edit
})

function openAdd(at?: AuditType, mt?: MissionType, parent?: Form) {
  Object.assign(modal, {
    open: true, editId: null,
    auditTypeCode: at ? at.code.toLowerCase() : '',
    missionTypeId: mt ? mt.id : 0,
    parentId: parent ? parent.id : null,
    label: '', description: '', icon: 'ti ti-file-description',
    _editAtCode: '',
  })
  previewUrl.value = ''; previewRoute.value = ''
}

function openEdit(at: AuditType, form: Form) {
  Object.assign(modal, {
    open: true, editId: form.id,
    auditTypeCode: at.code.toLowerCase(),
    missionTypeId: form.mission_type_id,
    parentId: form.parent_id,
    label: form.label,
    description: form.description ?? '',
    icon: form.icon,
    _editAtCode: at.code.toLowerCase(),
  })
  computePreviewUrl()
}

function onAuditTypeChange() { modal.missionTypeId = 0; modal.parentId = null; computePreviewUrl() }
function onPhaseChange()     { modal.parentId = null; computePreviewUrl() }
function closeModal() { modal.open = false }

const canSave = computed(() =>
  modal.auditTypeCode && modal.missionTypeId && modal.label.trim().length >= 2
)

// Save form (create or update)
async function saveForm() {
  if (!canSave.value) return
  saving.value = true
  try {
    const body: Record<string,unknown> = {
      mission_type_id: modal.missionTypeId,
      label:           modal.label.trim(),
      description:     modal.description || null,
      icon:            modal.icon || 'ti ti-file-description',
    }

    let url: string
    let method: 'post' | 'put'

    if (modal.editId) {
      // Correction: retrait du préfixe /m/audit.core
      url    = `/param/mission-forms/${modal._editAtCode}/${modal.editId}`
      method = 'put'
    } else {
      body.parent_id = modal.parentId
      // Correction: retrait du préfixe /m/audit.core
      url    = `/param/mission-forms/${modal.auditTypeCode}`
      method = 'post'
    }

    const res = await fetch(url, {
      method: method.toUpperCase(),
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '',
        'Accept': 'application/json',
      },
      body: JSON.stringify(body),
    })
    const json = await res.json()
    if (json.success) {
      showFlash(json.message ?? 'Enregistré.')
      closeModal()
      router.reload({ only: ['formsData','stats'] })
    } else {
      showFlash(json.error ?? 'Erreur.', 'err')
    }
  } catch (e) {
    showFlash('Erreur réseau.', 'err')
  } finally {
    saving.value = false
  }
}

// Delete modal state
const delModal = reactive<{
  open: boolean; atCode: string; form: Form|null
}>({ open: false, atCode: '', form: null })

function confirmDel(at: AuditType, form: Form) {
  delModal.atCode = at.code.toLowerCase()
  delModal.form   = form
  delModal.open   = true
}

async function deleteForm() {
  if (!delModal.form) return
  saving.value = true
  try {
    // Correction: retrait du préfixe /m/audit.core
    const res = await fetch(
      `/param/mission-forms/${delModal.atCode}/${delModal.form.id}`,
      {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '',
          'Accept': 'application/json',
        },
      }
    )
    const json = await res.json()
    if (json.success) {
      showFlash(json.message ?? 'Supprimé.')
      delModal.open = false
      router.reload({ only: ['formsData','stats'] })
    } else {
      showFlash(json.error ?? 'Erreur.', 'err')
    }
  } catch {
    showFlash('Erreur réseau.', 'err')
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.cursor-pointer { cursor: pointer; }
.spin { animation: spin 0.7s linear infinite; display: inline-block; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>