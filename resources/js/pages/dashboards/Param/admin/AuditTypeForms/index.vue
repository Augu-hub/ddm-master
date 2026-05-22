<template>
  <VerticalLayout>
    <Head title="Types d'Audit & Formulaires" />

    <div class="atf-page">

      <!-- ══ HEADER ═══════════════════════════════════════════════ -->
      <div class="atf-header">
        <div class="atf-header-top">
          <div>
            <h1 class="atf-title">⚙️ Types d'Audit & Formulaires</h1>
            <p class="atf-subtitle">ddmparam · audit_types / audit_type_forms</p>
          </div>
          <div class="atf-header-actions">
            <button @click="refreshData" class="btn-refresh" :class="{ 'is-spinning': refreshing }">
              <i class="ti ti-refresh"></i> Actualiser
            </button>
            <button @click="openTypeModal()" class="btn-add-type">
              <i class="ti ti-plus"></i> Nouveau type d'audit
            </button>
          </div>
        </div>

        <div class="stats-row" v-if="stats">
          <div class="stat-card sc-blue">
            <i class="ti ti-folder-filled"></i>
            <div><div class="sv">{{ stats.types_total }}</div><div class="sl">Types total</div></div>
          </div>
          <div class="stat-card sc-green">
            <i class="ti ti-circle-check-filled"></i>
            <div><div class="sv">{{ stats.types_actifs }}</div><div class="sl">Types actifs</div></div>
          </div>
          <div class="stat-card sc-purple">
            <i class="ti ti-forms"></i>
            <div><div class="sv">{{ stats.forms_total }}</div><div class="sl">Formulaires</div></div>
          </div>
          <div class="stat-card sc-orange">
            <i class="ti ti-layers-subtract"></i>
            <div><div class="sv">{{ stats.max_phase }}</div><div class="sl">Phases max</div></div>
          </div>
        </div>
      </div>

      <!-- ══ BODY ══════════════════════════════════════════════════ -->
      <div class="atf-body">

        <!-- ── Colonne types ──────────────────────────────────────── -->
        <div class="types-panel">
          <div class="panel-head">
            <span>Types d'Audit</span>
            <input v-model="typeSearch" class="mini-search" placeholder="Rechercher..." />
          </div>
          <div class="types-list">
            <div
              v-for="type in filteredTypes" :key="type.id"
              class="type-card"
              :class="{ active: selectedTypeId === type.id, inactive: !type.is_active }"
              @click="selectType(type)"
            >
              <div class="type-color-bar" :style="{ background: type.color }"></div>
              <div class="type-icon">
                <i :class="type.icon" :style="{ color: type.color }"></i>
              </div>
              <div class="type-info">
                <div class="type-code" :style="{ color: type.color }">{{ type.code }}</div>
                <div class="type-label">{{ type.label }}</div>
                <div class="type-counts">
                  <span class="badge-forms">{{ type.forms_total }} form(s)</span>
                  <span class="badge-active" v-if="type.is_active">Actif</span>
                  <span class="badge-inactive" v-else>Inactif</span>
                </div>
              </div>
              <div class="type-actions" @click.stop>
                <button @click="openTypeModal(type)" class="btn-icon-sm" title="Modifier">
                  <i class="ti ti-edit"></i>
                </button>
                <button @click="toggleTypeActive(type)" class="btn-icon-sm" title="Activer/Désactiver">
                  <i :class="type.is_active ? 'ti ti-eye-off' : 'ti ti-eye'"></i>
                </button>
                <button @click="askDelete('audit_type', type)" class="btn-icon-sm danger" title="Supprimer">
                  <i class="ti ti-trash"></i>
                </button>
              </div>
            </div>
            <div v-if="filteredTypes.length === 0" class="empty-types">Aucun type d'audit</div>
          </div>
        </div>

        <!-- ── Panel formulaires ──────────────────────────────────── -->
        <div class="forms-panel">

          <div v-if="!selectedType" class="forms-empty-state">
            <i class="ti ti-folder-open"></i>
            <p>Sélectionnez un type d'audit pour voir ses formulaires</p>
          </div>

          <template v-else>
            <!-- En-tête panel -->
            <div class="forms-header">
              <div class="forms-title-row">
                <div class="forms-type-badge" :style="{ background: selectedType.color }">
                  <i :class="selectedType.icon"></i>
                  {{ selectedType.code }} — {{ selectedType.label }}
                </div>
                <div class="forms-header-btns">
                  <button @click="openPhaseModal()" class="btn-add-phase">
                    <i class="ti ti-layout-rows"></i> Nouvelle phase
                  </button>
                  <button @click="openFormModal()" class="btn-add-form">
                    <i class="ti ti-plus"></i> Ajouter formulaire
                  </button>
                </div>
              </div>
              <div class="forms-filter-row">
                <input v-model="formSearch" class="form-search" placeholder="Rechercher un formulaire..." />
                <label class="toggle-inactive">
                  <input type="checkbox" v-model="showInactiveForms" />
                  Afficher inactifs
                </label>
              </div>
            </div>

            <!-- Chargement -->
            <div v-if="loadingForms" class="forms-loading">
              <i class="ti ti-loader-2 anim-spin"></i> Chargement...
            </div>

            <!-- Phases -->
            <div v-else class="phases-container">
              <div v-if="phases.length === 0" class="empty-forms">
                <i class="ti ti-list-off"></i>
                <p>Aucun formulaire pour ce type d'audit</p>
                <button @click="openPhaseModal()" class="btn-add-phase">
                  <i class="ti ti-plus"></i> Créer la première phase
                </button>
              </div>

              <div v-for="phase in phases" :key="phase.phase_num" class="phase-block">
                <!-- En-tête phase -->
                <div class="phase-header">
                  <div class="phase-badge" :style="{ background: selectedType.color }">
                    Phase {{ phase.phase_num }}
                  </div>
                  <span class="phase-label-text">{{ phase.phase_label }}</span>
                  <span class="phase-count">{{ countPhaseForms(phase) }} formulaire(s)</span>
                  <div class="phase-actions">
                    <button @click="openPhaseModal(phase)" class="btn-icon-sm" title="Modifier la phase">
                      <i class="ti ti-edit"></i>
                    </button>
                    <button @click="openFormModal(null, phase.phase_num, phase.phase_label)" class="btn-icon-sm" title="Ajouter un formulaire ici">
                      <i class="ti ti-plus"></i>
                    </button>
                    <button @click="askDelete('phase', phase)" class="btn-icon-sm danger" title="Supprimer la phase">
                      <i class="ti ti-trash"></i>
                    </button>
                  </div>
                </div>

                <!-- Formulaires de la phase -->
                <div class="phase-forms">
                  <div v-if="!hasVisibleForms(phase.forms)" class="phase-empty">
                    Aucun formulaire visible —
                    <button @click="openFormModal(null, phase.phase_num, phase.phase_label)" class="link-btn">Ajouter</button>
                  </div>
                  <FormTreeItem
                    v-for="form in phase.forms"
                    :key="form.id"
                    :form="form"
                    :level="0"
                    :type-color="selectedType.color"
                    :show-inactive="showInactiveForms"
                    :search="formSearch"
                    @edit="openFormModal"
                    @delete="(f) => askDelete('form', f)"
                    @add-child="(f) => openFormModal(null, f.phase_num, f.phase_label, f.id)"
                    @toggle="toggleFormActive"
                  />
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- ══ MODAL TYPE D'AUDIT ════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="typeModal.show" class="modal-overlay" @click.self="typeModal.show = false">
        <div class="modal-box">
          <div class="modal-head">
            <h3>{{ typeModal.edit ? '✏️ Modifier' : '➕ Nouveau' }} type d'audit</h3>
            <button @click="typeModal.show = false" class="btn-close"><i class="ti ti-x"></i></button>
          </div>
          <form @submit.prevent="saveType" class="modal-form">
            <div class="fg">
              <label>Code *</label>
              <input v-model="typeModal.form.code" :disabled="typeModal.edit" required class="finput" placeholder="ex: AC, AF, CI..." />
            </div>
            <div class="fg">
              <label>Libellé *</label>
              <input v-model="typeModal.form.label" required class="finput" />
            </div>
            <div class="fg-row">
              <div class="fg">
                <label>Couleur</label>
                <div class="color-row">
                  <input v-model="typeModal.form.color" type="color" class="color-picker" />
                  <input v-model="typeModal.form.color" class="finput" placeholder="#667eea" />
                </div>
              </div>
              <div class="fg">
                <label>Icône (Tabler Icons)</label>
                <input v-model="typeModal.form.icon" class="finput" placeholder="ti ti-folder" />
                <div v-if="typeModal.form.icon" class="icon-prev">
                  <i :class="typeModal.form.icon" :style="{ color: typeModal.form.color }"></i>
                  <span>{{ typeModal.form.icon }}</span>
                </div>
              </div>
            </div>
            <div class="fg-check">
              <label><input type="checkbox" v-model="typeModal.form.is_active" /> Actif</label>
            </div>
            <div class="modal-footer">
              <button type="button" @click="typeModal.show = false" class="btn-cancel">Annuler</button>
              <button type="submit" class="btn-save" :disabled="typeModal.loading">
                {{ typeModal.loading ? '⏳...' : '✓ Enregistrer' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ══ MODAL PHASE ════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="phaseModal.show" class="modal-overlay" @click.self="phaseModal.show = false">
        <div class="modal-box">
          <div class="modal-head">
            <h3>{{ phaseModal.edit ? '✏️ Modifier la phase' : '➕ Nouvelle phase' }}</h3>
            <button @click="phaseModal.show = false" class="btn-close"><i class="ti ti-x"></i></button>
          </div>
          <form @submit.prevent="savePhase" class="modal-form">
            <div v-if="phaseModal.edit" class="info-box">
              <i class="ti ti-info-circle"></i>
              Modifier le N° ou le libellé mettra à jour <strong>tous les formulaires</strong> de cette phase.
            </div>
            <div class="fg-row">
              <div class="fg">
                <label>N° de phase *</label>
                <input v-model.number="phaseModal.form.phase_num" type="number" min="1" required class="finput" />
              </div>
              <div class="fg" style="flex:2">
                <label>Libellé de phase *</label>
                <input v-model="phaseModal.form.phase_label" required class="finput" placeholder="ex: Préparation, Réalisation..." />
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" @click="phaseModal.show = false" class="btn-cancel">Annuler</button>
              <button type="submit" class="btn-save" :disabled="phaseModal.loading">
                {{ phaseModal.loading ? '⏳...' : phaseModal.edit ? '✓ Mettre à jour' : '✓ Créer & ajouter formulaire' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ══ MODAL FORMULAIRE ═══════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="formModal.show" class="modal-overlay" @click.self="formModal.show = false">
        <div class="modal-box modal-lg">
          <div class="modal-head">
            <h3>{{ formModal.edit ? '✏️ Modifier' : '➕ Nouveau' }} formulaire</h3>
            <button @click="formModal.show = false" class="btn-close"><i class="ti ti-x"></i></button>
          </div>
          <form @submit.prevent="saveForm" class="modal-form">
            <!-- Phase -->
            <div class="fg-row">
              <div class="fg">
                <label>Phase N° *</label>
                <input v-model.number="formModal.form.phase_num" type="number" min="1" required class="finput" @change="autoFillPhaseLabel" />
              </div>
              <div class="fg" style="flex:2">
                <label>Libellé de phase *</label>
                <input v-model="formModal.form.phase_label" required class="finput" placeholder="ex: Préparation..." />
              </div>
            </div>
            <!-- Parent -->
            <div class="fg">
              <label>Parent (optionnel)</label>
              <select v-model.number="formModal.form.parent_id" class="finput">
                <option :value="null">— Racine (pas de parent)</option>
                <optgroup
                  v-for="ph in flatFormsByPhase" :key="ph.phase_num"
                  :label="`Phase ${ph.phase_num} — ${ph.phase_label}`"
                >
                  <option v-for="f in ph.forms" :key="f.id" :value="f.id" :disabled="f.id === formModal.form.id">
                    {{ f.label }}
                  </option>
                </optgroup>
              </select>
            </div>
            <!-- Code + Label -->
            <div class="fg-row">
              <div class="fg">
                <label>Code *</label>
                <input v-model="formModal.form.code" required class="finput" placeholder="ex: RADO, PTCI..." />
              </div>
              <div class="fg" style="flex:2">
                <label>Libellé *</label>
                <input v-model="formModal.form.label" required class="finput" />
              </div>
            </div>
            <!-- URL + Icône + Ordre -->
            <div class="fg-row">
              <div class="fg" style="flex:2">
                <label>URL Path</label>
                <input v-model="formModal.form.url_path" class="finput" placeholder="/m/audit.core/rado" />
              </div>
              <div class="fg">
                <label>Icône</label>
                <input v-model="formModal.form.icon" class="finput" placeholder="ti ti-file" />
              </div>
              <div class="fg">
                <label>Ordre</label>
                <input v-model.number="formModal.form.sort_order" type="number" min="0" class="finput" />
              </div>
            </div>
            <div class="fg-check">
              <label><input type="checkbox" v-model="formModal.form.is_active" /> Actif</label>
            </div>
            <div class="modal-footer">
              <button type="button" @click="formModal.show = false" class="btn-cancel">Annuler</button>
              <button type="submit" class="btn-save" :disabled="formModal.loading">
                {{ formModal.loading ? '⏳...' : '✓ Enregistrer' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ══ MODAL SUPPRESSION ══════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="delModal.show" class="modal-overlay" @click.self="delModal.show = false">
        <div class="modal-box modal-sm">
          <div class="modal-head danger-head">
            <h3>⚠️ Confirmer la suppression</h3>
            <button @click="delModal.show = false" class="btn-close"><i class="ti ti-x"></i></button>
          </div>
          <div class="modal-body">
            <p>Supprimer <strong>« {{ delModal.label }} »</strong> ?</p>
            <p v-if="delModal.type === 'phase'" class="warn-text">
              ⚠️ Tous les formulaires de cette phase seront définitivement supprimés.
            </p>
            <p v-if="delModal.type === 'audit_type' && delModal.formsCount > 0" class="warn-text">
              ⚠️ {{ delModal.formsCount }} formulaire(s) associés seront aussi supprimés.
            </p>
            <p v-if="delModal.childCount > 0" class="warn-text">
              ⚠️ {{ delModal.childCount }} sous-formulaire(s) seront aussi supprimés.
            </p>
          </div>
          <div class="modal-footer" style="padding:0 1.5rem 1.5rem">
            <button @click="delModal.show = false" class="btn-cancel">Annuler</button>
            <button @click="confirmDelete" class="btn-delete" :disabled="delModal.loading">
              {{ delModal.loading ? '⏳' : '🗑️ Supprimer définitivement' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

  </VerticalLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, defineComponent, h } from 'vue'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

// ══════════════════════════════════════════════════════════════════
//  Composant récursif — render function (Vue 3, pas de template string)
// ══════════════════════════════════════════════════════════════════
const FormTreeItem = defineComponent({
  name: 'FormTreeItem',
  props: {
    form:         { type: Object,  required: true },
    level:        { type: Number,  default: 0 },
    typeColor:    { type: String,  default: '#667eea' },
    showInactive: { type: Boolean, default: false },
    search:       { type: String,  default: '' },
  },
  emits: ['edit', 'delete', 'add-child', 'toggle'],
  setup(props, { emit }) {
    const visible = computed(() => {
      if (!props.showInactive && !props.form.is_active) return false
      if (props.search) {
        const q = props.search.toLowerCase()
        return props.form.label.toLowerCase().includes(q)
            || props.form.code.toLowerCase().includes(q)
            || hasMatchInChildren(props.form, q)
      }
      return true
    })

    function hasMatchInChildren(node, q) {
      return (node.children || []).some(c =>
        c.label.toLowerCase().includes(q) ||
        c.code.toLowerCase().includes(q) ||
        hasMatchInChildren(c, q)
      )
    }

    return () => {
      if (!visible.value) return null

      // Lignes d'indentation
      const lines = Array.from({ length: props.level }, (_, i) =>
        h('span', { key: i, class: 'ftree-line', style: { borderColor: props.typeColor } })
      )

      // Enfants récursifs
      const childNodes = (props.form.children || []).map(child =>
        h(FormTreeItem, {
          key:          child.id,
          form:         child,
          level:        props.level + 1,
          typeColor:    props.typeColor,
          showInactive: props.showInactive,
          search:       props.search,
          onEdit:       (f) => emit('edit', f),
          onDelete:     (f) => emit('delete', f),
          onAddChild:   (f) => emit('add-child', f),
          onToggle:     (f) => emit('toggle', f),
        })
      )

      return h('div', { class: 'ftree-item' }, [
        h('div', { class: ['ftree-row', { 'ftree-inactive': !props.form.is_active }] }, [
          h('div', { class: 'ftree-indent' }, lines),
          h('i', { class: props.form.icon || 'ti ti-file', style: { color: props.typeColor } }),
          h('div', { class: 'ftree-info' }, [
            h('div', { class: 'ftree-code' }, props.form.code),
            h('div', { class: 'ftree-label' }, props.form.label),
            props.form.url_path ? h('div', { class: 'ftree-url' }, props.form.url_path) : null,
          ]),
          h('div', { class: 'ftree-actions' }, [
            h('span', { class: props.form.is_active ? 'badge-on' : 'badge-off' },
              props.form.is_active ? 'Actif' : 'Inactif'),
            h('button', { class: 'btn-icon-xs', title: 'Ajouter sous-formulaire', onClick: () => emit('add-child', props.form) },
              [h('i', { class: 'ti ti-plus' })]),
            h('button', { class: 'btn-icon-xs', title: 'Modifier', onClick: () => emit('edit', props.form) },
              [h('i', { class: 'ti ti-edit' })]),
            h('button', { class: 'btn-icon-xs', title: props.form.is_active ? 'Désactiver' : 'Activer', onClick: () => emit('toggle', props.form) },
              [h('i', { class: props.form.is_active ? 'ti ti-eye-off' : 'ti ti-eye' })]),
            h('button', { class: 'btn-icon-xs danger', title: 'Supprimer', onClick: () => emit('delete', props.form) },
              [h('i', { class: 'ti ti-trash' })]),
          ]),
        ]),
        ...childNodes,
      ])
    }
  },
})

// ══════════════════════════════════════════════════════════════════
//  Props Inertia
// ══════════════════════════════════════════════════════════════════
const props = defineProps({
  auditTypes: { type: Array,  default: () => [] },
  stats:      { type: Object, default: null },
})

// ══════════════════════════════════════════════════════════════════
//  État réactif
// ══════════════════════════════════════════════════════════════════
const auditTypes        = ref(props.auditTypes)
const stats             = ref(props.stats)
const typeSearch        = ref('')
const formSearch        = ref('')
const showInactiveForms = ref(false)
const refreshing        = ref(false)
const loadingForms      = ref(false)
const selectedTypeId    = ref(null)
const phases            = ref([])    // arbre par phase
const flatForms         = ref([])    // liste plate pour select parent

// ══════════════════════════════════════════════════════════════════
//  Computed
// ══════════════════════════════════════════════════════════════════
const selectedType = computed(() =>
  auditTypes.value.find(t => t.id === selectedTypeId.value) ?? null
)

const filteredTypes = computed(() => {
  const q = typeSearch.value.toLowerCase()
  if (!q) return auditTypes.value
  return auditTypes.value.filter(t =>
    t.label.toLowerCase().includes(q) || t.code.toLowerCase().includes(q)
  )
})

// Liste plate groupée par phase pour le <select> parent du modal formulaire
const flatFormsByPhase = computed(() => {
  const map = {}
  flatForms.value.forEach(f => {
    if (!map[f.phase_num]) {
      map[f.phase_num] = { phase_num: f.phase_num, phase_label: f.phase_label, forms: [] }
    }
    map[f.phase_num].forms.push(f)
  })
  return Object.values(map).sort((a, b) => a.phase_num - b.phase_num)
})

// ══════════════════════════════════════════════════════════════════
//  Helpers
// ══════════════════════════════════════════════════════════════════
function countPhaseForms(phase) {
  let n = 0
  const walk = (arr) => arr.forEach(f => { n++; walk(f.children || []) })
  walk(phase.forms)
  return n
}

function countFormChildren(form) {
  if (!form.children?.length) return 0
  return form.children.reduce((acc, c) => acc + 1 + countFormChildren(c), 0)
}

function hasVisibleForms(forms) {
  return forms.some(f => showInactiveForms.value || f.is_active)
}

function autoFillPhaseLabel() {
  const ph = phases.value.find(p => p.phase_num === formModal.value.form.phase_num)
  if (ph && !formModal.value.form.phase_label) {
    formModal.value.form.phase_label = ph.phase_label
  }
}

function nextPhaseNum() {
  if (!phases.value.length) return 1
  return Math.max(...phases.value.map(p => p.phase_num)) + 1
}

// ══════════════════════════════════════════════════════════════════
//  Chargement formulaires (API JSON)
// ══════════════════════════════════════════════════════════════════
async function selectType(type) {
  selectedTypeId.value = type.id
  await loadForms(type.id)
}

async function loadForms(typeId) {
  loadingForms.value = true
  phases.value       = []
  flatForms.value    = []
  try {
    const res  = await fetch(`/admin/audit-type-forms/api/forms/${typeId}`)
    const json = await res.json()
    if (json.success) {
      phases.value    = json.data
      flatForms.value = json.flat
    }
  } catch (e) {
    console.error('loadForms:', e)
  } finally {
    loadingForms.value = false
  }
}

async function refreshData() {
  refreshing.value = true
  try {
    const res  = await fetch('/admin/audit-type-forms/api/audit-types')
    const json = await res.json()
    if (json.success) {
      auditTypes.value = json.data
      stats.value      = json.stats
    }
  } finally {
    refreshing.value = false
  }
  if (selectedTypeId.value) await loadForms(selectedTypeId.value)
}

// ══════════════════════════════════════════════════════════════════
//  MODAL — Type d'audit
// ══════════════════════════════════════════════════════════════════
const typeModal = ref({ show: false, edit: false, loading: false, form: {} })

function openTypeModal(type = null) {
  typeModal.value = {
    show: true,
    edit: !!type,
    loading: false,
    form: type
      ? { id: type.id, code: type.code, label: type.label, color: type.color, icon: type.icon, is_active: type.is_active }
      : { code: '', label: '', color: '#667eea', icon: 'ti ti-folder', is_active: true },
  }
}

function saveType() {
  typeModal.value.loading = true
  const { edit, form } = typeModal.value
  const url    = edit ? `/admin/audit-type-forms/audit-types/${form.id}` : '/admin/audit-type-forms/audit-types'
  const method = edit ? 'put' : 'post'

  router[method](url, form, {
    onSuccess: async () => { typeModal.value.show = false; await refreshData() },
    onError:   (e)  => alert(Object.values(e).join('\n')),
    onFinish:  ()   => { typeModal.value.loading = false },
  })
}

function toggleTypeActive(type) {
  router.patch(`/admin/audit-type-forms/audit-types/${type.id}/toggle-active`, {}, {
    onSuccess: () => refreshData(),
  })
}

// ══════════════════════════════════════════════════════════════════
//  MODAL — Phase
// ══════════════════════════════════════════════════════════════════
const phaseModal = ref({ show: false, edit: false, loading: false, form: {}, originalNum: null })

function openPhaseModal(phase = null) {
  phaseModal.value = {
    show:        true,
    edit:        !!phase,
    loading:     false,
    originalNum: phase ? phase.phase_num : null,
    form:        phase
      ? { phase_num: phase.phase_num, phase_label: phase.phase_label }
      : { phase_num: nextPhaseNum(), phase_label: '' },
  }
}

function savePhase() {
  phaseModal.value.loading = true
  const { edit, form, originalNum } = phaseModal.value

  if (edit) {
    // Renommer → PATCH phase-rename → met à jour tous les formulaires de la phase
    router.patch('/admin/audit-type-forms/phase-rename', {
      audit_type_id:   selectedTypeId.value,
      old_phase_num:   originalNum,
      new_phase_num:   form.phase_num,
      new_phase_label: form.phase_label,
    }, {
      onSuccess: async () => { phaseModal.value.show = false; await loadForms(selectedTypeId.value) },
      onError:   (e)  => alert(Object.values(e).join('\n')),
      onFinish:  ()   => { phaseModal.value.loading = false },
    })
  } else {
    // Créer : pas d'entrée spécifique en DB pour la phase, on ouvre le modal formulaire pré-rempli
    phaseModal.value.show    = false
    phaseModal.value.loading = false
    openFormModal(null, form.phase_num, form.phase_label)
  }
}

// ══════════════════════════════════════════════════════════════════
//  MODAL — Formulaire
// ══════════════════════════════════════════════════════════════════
const formModal = ref({ show: false, edit: false, loading: false, form: {} })

function openFormModal(form = null, defaultPhase = null, defaultPhaseLabel = '', defaultParentId = null) {
  formModal.value = {
    show:    true,
    edit:    !!form,
    loading: false,
    form:    form
      ? { ...form, children: undefined }
      : {
          audit_type_id: selectedTypeId.value,
          phase_num:     defaultPhase      ?? (phases.value[0]?.phase_num   ?? 1),
          phase_label:   defaultPhaseLabel || (phases.value[0]?.phase_label ?? ''),
          parent_id:     defaultParentId,
          code:          '',
          label:         '',
          url_path:      '',
          icon:          'ti ti-file',
          sort_order:    10,
          is_active:     true,
        },
  }
}

function saveForm() {
  formModal.value.loading = true
  const { edit, form } = formModal.value
  const url    = edit ? `/admin/audit-type-forms/forms/${form.id}` : '/admin/audit-type-forms/forms'
  const method = edit ? 'put' : 'post'

  router[method](url, form, {
    onSuccess: async () => { formModal.value.show = false; await loadForms(selectedTypeId.value) },
    onError:   (e)  => alert(Object.values(e).join('\n')),
    onFinish:  ()   => { formModal.value.loading = false },
  })
}

function toggleFormActive(form) {
  router.patch(`/admin/audit-type-forms/forms/${form.id}/toggle-active`, {}, {
    onSuccess: () => loadForms(selectedTypeId.value),
  })
}

// ══════════════════════════════════════════════════════════════════
//  MODAL — Suppression (type / phase / formulaire)
// ══════════════════════════════════════════════════════════════════
const delModal = ref({
  show:       false,
  loading:    false,
  type:       null,   // 'audit_type' | 'phase' | 'form'
  target:     null,
  label:      '',
  formsCount: 0,
  childCount: 0,
})

function askDelete(type, target) {
  delModal.value = {
    show:       true,
    loading:    false,
    type,
    target,
    label:      type === 'phase' ? target.phase_label : target.label,
    formsCount: type === 'audit_type' ? (target.forms_total ?? 0) : 0,
    childCount: type === 'form' ? countFormChildren(target) : 0,
  }
}

function confirmDelete() {
  delModal.value.loading = true
  const { type, target } = delModal.value

  let url
  if (type === 'audit_type') {
    url = `/admin/audit-type-forms/audit-types/${target.id}`
  } else if (type === 'form') {
    url = `/admin/audit-type-forms/forms/${target.id}`
  } else if (type === 'phase') {
    url = `/admin/audit-type-forms/phase/${selectedTypeId.value}/${target.phase_num}`
  }

  router.delete(url, {
    onSuccess: async () => {
      delModal.value.show = false
      if (type === 'audit_type') {
        selectedTypeId.value = null
        phases.value         = []
        await refreshData()
      } else {
        await loadForms(selectedTypeId.value)
        if (type !== 'form') await refreshData() // màj compteurs sidebar
      }
    },
    onFinish: () => { delModal.value.loading = false },
  })
}
</script>

<style scoped>
* { box-sizing: border-box; margin: 0; padding: 0; }

.atf-page {
  min-height: 100vh; background: #f0f2f5;
  display: flex; flex-direction: column;
}

/* ── Header ─────────────────────────────────────────────────────── */
.atf-header {
  background: #fff; padding: 1.5rem 2rem;
  border-bottom: 1px solid #e2e8f0; box-shadow: 0 1px 4px rgba(0,0,0,.04);
}
.atf-header-top {
  display: flex; justify-content: space-between;
  align-items: flex-start; margin-bottom: 1.25rem; gap: 1rem;
}
.atf-title   { font-size: 1.5rem; font-weight: 800; color: #1a202c; }
.atf-subtitle { font-size: .8rem; color: #a0aec0; margin-top: .2rem; font-family: monospace; }
.atf-header-actions { display: flex; gap: .75rem; flex-shrink: 0; }

.btn-refresh {
  background: #f7fafc; border: 2px solid #e2e8f0; color: #4a5568;
  padding: .55rem 1rem; border-radius: 8px; font-weight: 600; font-size: .85rem;
  cursor: pointer; display: flex; align-items: center; gap: .4rem; transition: all .2s;
}
.btn-refresh:hover { border-color: #667eea; color: #667eea; }
.btn-refresh.is-spinning i { animation: spin 1s linear infinite; }

.btn-add-type {
  background: linear-gradient(135deg, #667eea, #764ba2); color: #fff;
  border: none; padding: .55rem 1.25rem; border-radius: 8px;
  font-weight: 700; font-size: .85rem; cursor: pointer;
  display: flex; align-items: center; gap: .4rem;
  box-shadow: 0 4px 12px rgba(102,126,234,.3); transition: all .2s;
}
.btn-add-type:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(102,126,234,.4); }

/* Stats */
.stats-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 1rem; }
.stat-card {
  display: flex; align-items: center; gap: .85rem;
  padding: 1rem 1.25rem; border-radius: 10px; border-left: 4px solid;
  background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.05);
}
.stat-card i { font-size: 1.75rem; opacity: .85; }
.sv { font-size: 1.5rem; font-weight: 800; color: #1a202c; }
.sl { font-size: .75rem; color: #718096; margin-top: .1rem; }
.sc-blue   { border-left-color: #667eea; } .sc-blue   i { color: #667eea; }
.sc-green  { border-left-color: #48bb78; } .sc-green  i { color: #48bb78; }
.sc-purple { border-left-color: #9f7aea; } .sc-purple i { color: #9f7aea; }
.sc-orange { border-left-color: #ed8936; } .sc-orange i { color: #ed8936; }

/* ── Body ───────────────────────────────────────────────────────── */
.atf-body {
  display: flex; flex: 1; overflow: hidden;
  height: calc(100vh - 215px);
}

/* ── Panel types ────────────────────────────────────────────────── */
.types-panel {
  width: 300px; background: #fff;
  border-right: 1px solid #e2e8f0;
  display: flex; flex-direction: column; flex-shrink: 0;
}
.panel-head {
  padding: 1rem; border-bottom: 1px solid #e2e8f0;
  display: flex; flex-direction: column; gap: .6rem;
  font-weight: 700; font-size: .9rem; color: #2d3748; background: #f7fafc;
}
.mini-search {
  padding: .45rem .75rem; border: 2px solid #e2e8f0;
  border-radius: 6px; font-size: .85rem; width: 100%;
}
.mini-search:focus { outline: none; border-color: #667eea; }
.types-list { flex: 1; overflow-y: auto; padding: .5rem; }

.type-card {
  display: flex; align-items: center; gap: .75rem;
  padding: .75rem; border-radius: 8px; cursor: pointer;
  margin-bottom: .4rem; border: 2px solid transparent;
  position: relative; transition: all .2s; overflow: hidden;
}
.type-card:hover { background: #f7fafc; border-color: #e2e8f0; }
.type-card.active { border-color: #667eea; background: #ebf4ff; }
.type-card.inactive { opacity: .6; }
.type-color-bar { position: absolute; left: 0; top: 0; bottom: 0; width: 3px; }
.type-icon {
  width: 36px; height: 36px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  background: #f7fafc; font-size: 1.25rem; flex-shrink: 0;
}
.type-info { flex: 1; min-width: 0; }
.type-code  { font-size: .7rem; font-weight: 800; letter-spacing: .05em; }
.type-label { font-size: .85rem; font-weight: 600; color: #2d3748; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.type-counts { display: flex; gap: .4rem; margin-top: .25rem; align-items: center; }
.badge-forms   { font-size: .7rem; background: #edf2f7; padding: .1rem .4rem; border-radius: 4px; color: #4a5568; }
.badge-active  { font-size: .65rem; background: #c6f6d5; color: #276749; padding: .1rem .4rem; border-radius: 4px; font-weight: 700; }
.badge-inactive{ font-size: .65rem; background: #fed7d7; color: #9b2c2c; padding: .1rem .4rem; border-radius: 4px; font-weight: 700; }
.type-actions  { display: flex; gap: .25rem; flex-shrink: 0; }
.empty-types   { text-align: center; padding: 2rem; color: #a0aec0; font-size: .9rem; }

/* ── Panel formulaires ──────────────────────────────────────────── */
.forms-panel { flex: 1; display: flex; flex-direction: column; overflow: hidden; background: #f0f2f5; }

.forms-empty-state {
  flex: 1; display: flex; flex-direction: column;
  align-items: center; justify-content: center; color: #a0aec0; gap: 1rem;
}
.forms-empty-state i { font-size: 3rem; opacity: .4; }

.forms-header {
  background: #fff; padding: 1rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex; flex-direction: column; gap: .75rem;
}
.forms-title-row { display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
.forms-header-btns { display: flex; gap: .6rem; flex-shrink: 0; }
.forms-type-badge {
  display: inline-flex; align-items: center; gap: .5rem;
  color: #fff; padding: .4rem 1rem; border-radius: 20px;
  font-weight: 700; font-size: .85rem;
}
.btn-add-phase {
  background: #f7fafc; border: 2px solid #e2e8f0; color: #4a5568;
  padding: .4rem .9rem; border-radius: 7px; font-weight: 700; font-size: .8rem;
  cursor: pointer; display: flex; align-items: center; gap: .3rem; transition: all .2s;
}
.btn-add-phase:hover { border-color: #667eea; color: #667eea; }
.btn-add-form {
  background: #667eea; color: #fff; border: none;
  padding: .4rem .9rem; border-radius: 7px; font-weight: 700; font-size: .8rem;
  cursor: pointer; display: flex; align-items: center; gap: .3rem; transition: all .2s;
}
.btn-add-form:hover { background: #5568d3; }

.forms-filter-row { display: flex; gap: 1rem; align-items: center; }
.form-search {
  flex: 1; padding: .45rem .85rem;
  border: 2px solid #e2e8f0; border-radius: 7px; font-size: .85rem;
}
.form-search:focus { outline: none; border-color: #667eea; }
.toggle-inactive {
  display: flex; align-items: center; gap: .4rem;
  font-size: .82rem; color: #4a5568; font-weight: 600; cursor: pointer; white-space: nowrap;
}
.toggle-inactive input { accent-color: #667eea; width: 15px; height: 15px; }

.forms-loading {
  flex: 1; display: flex; align-items: center; justify-content: center;
  color: #667eea; font-weight: 600; gap: .5rem;
}

.phases-container {
  flex: 1; overflow-y: auto; padding: 1rem 1.5rem;
  display: flex; flex-direction: column; gap: 1.25rem;
}

.empty-forms {
  flex: 1; display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 1rem; color: #a0aec0; padding: 3rem;
}
.empty-forms i { font-size: 2.5rem; }

/* Phase block */
.phase-block {
  background: #fff; border-radius: 10px;
  box-shadow: 0 1px 4px rgba(0,0,0,.05); overflow: hidden;
}
.phase-header {
  display: flex; align-items: center; gap: .75rem;
  padding: .75rem 1rem; background: #f7fafc; border-bottom: 1px solid #e2e8f0;
}
.phase-badge {
  color: #fff; padding: .2rem .65rem; border-radius: 12px;
  font-size: .75rem; font-weight: 800; white-space: nowrap;
}
.phase-label-text { flex: 1; font-weight: 700; color: #2d3748; font-size: .9rem; }
.phase-count  { font-size: .78rem; color: #a0aec0; }
.phase-actions{ display: flex; gap: .25rem; }
.phase-forms  { padding: .5rem 0; }
.phase-empty  { padding: .75rem 1.5rem; font-size: .85rem; color: #a0aec0; }
.link-btn {
  background: none; border: none; color: #667eea;
  cursor: pointer; font-weight: 600; text-decoration: underline; font-size: inherit;
}

/* FormTreeItem — styles via :deep() car render function */
:deep(.ftree-item)        { border-bottom: 1px solid #f0f2f5; }
:deep(.ftree-item:last-child) { border-bottom: none; }
:deep(.ftree-row)         { display: flex; align-items: center; gap: .75rem; padding: .65rem 1rem; transition: background .15s; }
:deep(.ftree-row:hover)   { background: #f7fafc; }
:deep(.ftree-row.ftree-inactive) { opacity: .5; }
:deep(.ftree-indent)      { display: flex; }
:deep(.ftree-line)        { width: 20px; height: 18px; display: inline-block; border-left: 2px dashed; opacity: .3; margin-right: 4px; }
:deep(.ftree-info)        { flex: 1; min-width: 0; }
:deep(.ftree-code)        { font-size: .7rem; font-weight: 800; color: #667eea; letter-spacing: .04em; }
:deep(.ftree-label)       { font-size: .88rem; font-weight: 600; color: #2d3748; }
:deep(.ftree-url)         { font-size: .72rem; color: #a0aec0; font-family: monospace; }
:deep(.ftree-actions)     { display: flex; align-items: center; gap: .25rem; flex-shrink: 0; }
:deep(.badge-on)          { font-size: .65rem; background: #c6f6d5; color: #276749; padding: .1rem .4rem; border-radius: 4px; font-weight: 700; }
:deep(.badge-off)         { font-size: .65rem; background: #fed7d7; color: #9b2c2c; padding: .1rem .4rem; border-radius: 4px; font-weight: 700; }

/* Boutons icône */
.btn-icon-sm,
:deep(.btn-icon-xs) {
  width: 28px; height: 28px; border: none; background: transparent;
  cursor: pointer; border-radius: 5px; font-size: .9rem; color: #718096;
  display: flex; align-items: center; justify-content: center; transition: all .15s;
}
.btn-icon-sm:hover,
:deep(.btn-icon-xs:hover)          { background: #e2e8f0; color: #2d3748; }
.btn-icon-sm.danger:hover,
:deep(.btn-icon-xs.danger:hover)   { background: #fed7d7; color: #c53030; }

/* ── Modals ─────────────────────────────────────────────────────── */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,.5);
  display: flex; align-items: center; justify-content: center;
  z-index: 9999; padding: 1rem; backdrop-filter: blur(2px);
}
.modal-box {
  background: #fff; border-radius: 12px;
  width: 100%; max-width: 480px; max-height: 92vh; overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0,0,0,.3);
}
.modal-lg { max-width: 640px; }
.modal-sm { max-width: 380px; }

.modal-head {
  display: flex; justify-content: space-between; align-items: center;
  padding: 1.25rem 1.5rem; border-bottom: 2px solid #e2e8f0;
  position: sticky; top: 0; background: #fff; z-index: 1;
}
.modal-head h3    { font-size: 1.1rem; font-weight: 700; color: #1a202c; margin: 0; }
.danger-head h3   { color: #e53e3e; }
.btn-close        { background: none; border: none; font-size: 1.25rem; cursor: pointer; color: #718096; }
.btn-close:hover  { color: #2d3748; }

.modal-form { padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
.modal-body { padding: 1.25rem 1.5rem; }

.modal-footer {
  display: flex; gap: .75rem; justify-content: flex-end;
  padding-top: 1rem; border-top: 2px solid #e2e8f0; margin-top: .5rem;
}

.info-box {
  background: #ebf8ff; border: 1px solid #90cdf4; border-radius: 8px;
  padding: .75rem 1rem; font-size: .85rem; color: #2b6cb0;
  display: flex; gap: .5rem; align-items: flex-start;
}
.info-box i { margin-top: .1rem; flex-shrink: 0; }

.fg           { display: flex; flex-direction: column; gap: .35rem; }
.fg label     { font-weight: 600; font-size: .85rem; color: #2d3748; }
.fg-row       { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.fg-check label { display: flex; align-items: center; gap: .4rem; font-weight: 600; font-size: .85rem; color: #2d3748; cursor: pointer; }
.fg-check input { accent-color: #667eea; width: 16px; height: 16px; }

.finput {
  padding: .65rem .85rem; border: 2px solid #e2e8f0;
  border-radius: 8px; font-size: .9rem; transition: all .2s; width: 100%;
}
.finput:focus    { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,.1); }
.finput:disabled { background: #f7fafc; color: #a0aec0; }

.color-row      { display: flex; gap: .5rem; align-items: center; }
.color-picker   { width: 44px; height: 42px; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; padding: 2px; }
.icon-prev      { display: flex; align-items: center; gap: .5rem; padding: .4rem .75rem; background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 6px; font-size: .85rem; color: #4a5568; }
.icon-prev i    { font-size: 1.1rem; }

.btn-cancel  { padding: .6rem 1.25rem; background: #edf2f7; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
.btn-cancel:hover { background: #e2e8f0; }
.btn-save    { padding: .6rem 1.5rem; background: #667eea; color: #fff; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; }
.btn-save:hover:not(:disabled) { background: #5568d3; }
.btn-save:disabled { opacity: .6; cursor: not-allowed; }
.btn-delete  { padding: .6rem 1.25rem; background: #e53e3e; color: #fff; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; }
.btn-delete:hover:not(:disabled) { background: #c53030; }
.btn-delete:disabled { opacity: .6; cursor: not-allowed; }

.warn-text { color: #e53e3e; font-weight: 600; margin-top: .5rem; font-size: .9rem; }

/* Animations */
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.anim-spin { animation: spin 1s linear infinite; }

/* Responsive */
@media (max-width: 768px) {
  .atf-body         { flex-direction: column; height: auto; }
  .types-panel      { width: 100%; border-right: none; border-bottom: 1px solid #e2e8f0; max-height: 280px; }
  .stats-row        { grid-template-columns: repeat(2,1fr); }
  .fg-row           { grid-template-columns: 1fr; }
  .forms-header-btns{ flex-direction: column; }
}
</style>