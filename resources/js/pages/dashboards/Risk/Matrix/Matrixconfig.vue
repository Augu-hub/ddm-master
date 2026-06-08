<template>
  <VerticalLayout>
    <Head title="Paramétrage — Matrice des risques" />

    <!-- HEADER -->
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
      <div class="d-flex align-items-center gap-3">
        <div class="mc-logo"><i class="ti ti-layout-grid"></i></div>
        <div>
          <h4 class="mb-0 fw-bold">Configuration de la matrice</h4>
          <small class="text-muted">Paramétrez impacts, fréquences et zones — données de la base</small>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <Transition name="flash">
          <span v-if="flash" class="mc-flash" :class="flashOk ? 'mc-flash--ok' : 'mc-flash--err'">
            <i :class="flashOk ? 'ti ti-check' : 'ti ti-alert-circle'"></i> {{ flash }}
          </span>
        </Transition>
        <b-button variant="primary" size="sm" @click="openCreate">
          <i class="ti ti-plus me-1"></i>Nouvelle configuration
        </b-button>
      </div>
    </div>

    <b-row class="g-3">

      <!-- ══ LISTE GAUCHE ══ -->
      <b-col lg="3">
        <div class="mc-panel h-100">
          <div class="mc-panel-hd">
            <i class="ti ti-stack me-2 text-primary"></i>
            <span class="fw-semibold small">Configurations ({{ props.configs.length }})</span>
          </div>
          <div class="mc-list">
            <div v-if="!props.configs.length" class="text-center text-muted py-4 small">
              <i class="ti ti-inbox d-block fs-2 mb-1 opacity-25"></i>Aucune configuration
            </div>
            <div
              v-for="c in props.configs" :key="c.id"
              :class="['mc-list-row', { 'mc-list-row--active': currentId === c.id }]"
              @click="selectConfig(c.id)"
            >
              <div class="flex-grow-1 overflow-hidden">
                <div class="d-flex align-items-center gap-2">
                  <span class="fw-semibold small text-truncate">{{ c.name }}</span>
                  <span v-if="c.is_active" class="mc-badge mc-badge--on">✓ Actif</span>
                </div>
                <div class="mc-list-sub">
                  {{ c.matrix_label }} · max {{ c.matrix_size * c.matrix_size }}
                </div>
              </div>
              <i v-if="currentId === c.id" class="ti ti-chevron-right text-primary" style="font-size:12px"></i>
            </div>
          </div>
        </div>
      </b-col>

      <!-- ══ DÉTAIL DROITE ══ -->
      <b-col lg="9">

        <div v-if="!cfg" class="mc-empty">
          <i class="ti ti-layout-grid" style="font-size:3rem;color:#cbd5e1"></i>
          <p class="text-muted mt-3 mb-0">Sélectionnez ou créez une configuration</p>
        </div>

        <template v-else>

          <!-- En-tête config sélectionnée -->
          <div class="mc-cfg-hd mb-3">
            <div class="mc-cfg-av">{{ cfg.name.charAt(0).toUpperCase() }}</div>
            <div class="flex-grow-1 min-w-0">
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="fw-bold fs-6">{{ cfg.name }}</span>
                <span class="mc-size-tag">{{ cfg.matrix_label }}</span>
                <span v-if="cfg.is_active" class="mc-badge mc-badge--on">Actif</span>
                <span v-else              class="mc-badge mc-badge--off">Inactif</span>
              </div>
              <div v-if="cfg.description" class="text-muted small mt-1">{{ cfg.description }}</div>
            </div>
            <div class="d-flex gap-2 flex-shrink-0">
              <b-button size="sm" variant="outline-primary" @click="openEdit">
                <i class="ti ti-pencil me-1"></i>Renommer
              </b-button>
              <b-button v-if="!cfg.is_active" size="sm" variant="success" @click="doActivate">
                <i class="ti ti-check me-1"></i>Activer
              </b-button>
              <b-button v-if="!cfg.is_active" size="sm" variant="outline-danger" @click="doDelete">
                <i class="ti ti-trash"></i>
              </b-button>
            </div>
          </div>

          <!-- Tabs -->
          <div class="mc-tabs mb-3">
            <button v-for="t in tabs" :key="t.id"
                    :class="['mc-tab', { 'mc-tab--active': tab === t.id }]"
                    @click="tab = t.id">
              <i :class="['ti', t.icon]"></i>
              {{ t.label }}
              <span class="mc-tab-ct">{{ t.count }}</span>
            </button>
          </div>

          <!-- ════ IMPACTS ════ -->
          <div v-if="tab === 'impact'" class="mc-panel">
            <div class="mc-panel-hd">
              <i class="ti ti-arrow-up me-2 text-danger"></i>
              <span class="fw-semibold small">
                Niveaux d'impact — {{ cfg.impacts.length }} niveaux
                · score 1 (faible) → {{ cfg.matrix_size }} (maximal)
              </span>
            </div>

            <div v-for="imp in sortedImpacts" :key="imp.id" class="mc-row">
              <div class="mc-strip" :style="{ background: imp.color_code }"></div>
              <div class="mc-pill" :style="{ background: imp.color_code }">{{ imp.score }}</div>

              <!-- Vue normale -->
              <div v-if="editingImpact !== imp.id" class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <span class="fw-semibold small" :style="{ color: imp.color_code }">{{ imp.label }}</span>
                  <span class="mc-color-dot" :style="{ background: imp.color_code }"></span>
                  <code class="mc-hex">{{ imp.color_code }}</code>
                </div>
                <div v-if="imp.description" class="mc-desc">{{ imp.description }}</div>
              </div>

              <!-- Édition inline -->
              <div v-else class="flex-grow-1">
                <div class="row g-2 mb-2">
                  <div class="col-12 col-md-4">
                    <label class="mc-lbl">Libellé *</label>
                    <input v-model="impForm.label" class="form-control form-control-sm"/>
                  </div>
                  <div class="col-12 col-md-4">
                    <label class="mc-lbl">Description</label>
                    <input v-model="impForm.description" class="form-control form-control-sm"
                           placeholder="Optionnel…"/>
                  </div>
                  <div class="col-12 col-md-3">
                    <label class="mc-lbl">Couleur *</label>
                    <div class="d-flex align-items-center gap-2">
                      <label class="mc-clr-wrap">
                        <input type="color" v-model="impForm.color_code" class="mc-clr-inp"/>
                        <span class="mc-clr-sw" :style="{ background: impForm.color_code }"></span>
                      </label>
                      <code class="mc-hex">{{ impForm.color_code }}</code>
                    </div>
                  </div>
                </div>
                <div class="d-flex gap-2">
                  <b-button size="sm" variant="primary" @click="saveImpact(imp.id)" :disabled="saving">
                    <i class="ti ti-device-floppy me-1"></i>Sauvegarder
                  </b-button>
                  <b-button size="sm" variant="light" @click="editingImpact = null">Annuler</b-button>
                </div>
              </div>

              <button v-if="editingImpact !== imp.id" class="mc-edit-btn"
                      @click="startImpactEdit(imp)">
                <i class="ti ti-pencil"></i>
              </button>
            </div>
          </div>

          <!-- ════ FRÉQUENCES ════ -->
          <div v-if="tab === 'frequency'" class="mc-panel">
            <div class="mc-panel-hd">
              <i class="ti ti-clock me-2" style="color:#3b82f6"></i>
              <span class="fw-semibold small">
                Niveaux de fréquence — {{ cfg.frequencies.length }} niveaux
                · score 1 (rare) → {{ cfg.matrix_size }} (très fréquent)
              </span>
            </div>

            <div v-for="freq in sortedFrequencies" :key="freq.id" class="mc-row">
              <div class="mc-strip" :style="{ background: freq.color_code }"></div>
              <div class="mc-pill" :style="{ background: freq.color_code }">{{ freq.score }}</div>

              <!-- Vue normale -->
              <div v-if="editingFreq !== freq.id" class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <span class="fw-semibold small" :style="{ color: freq.color_code }">{{ freq.label }}</span>
                  <span v-if="freq.recurrence" class="mc-rec">{{ freq.recurrence }}</span>
                  <span class="mc-color-dot" :style="{ background: freq.color_code }"></span>
                  <code class="mc-hex">{{ freq.color_code }}</code>
                </div>
                <div v-if="freq.description" class="mc-desc">{{ freq.description }}</div>
              </div>

              <!-- Édition inline -->
              <div v-else class="flex-grow-1">
                <div class="row g-2 mb-2">
                  <div class="col-12 col-md-3">
                    <label class="mc-lbl">Libellé *</label>
                    <input v-model="freqForm.label" class="form-control form-control-sm"/>
                  </div>
                  <div class="col-12 col-md-3">
                    <label class="mc-lbl">Récurrence</label>
                    <input v-model="freqForm.recurrence" class="form-control form-control-sm"
                           placeholder="Ex : 1 fois / an"/>
                  </div>
                  <div class="col-12 col-md-3">
                    <label class="mc-lbl">Description</label>
                    <input v-model="freqForm.description" class="form-control form-control-sm"
                           placeholder="Optionnel…"/>
                  </div>
                  <div class="col-12 col-md-2">
                    <label class="mc-lbl">Couleur *</label>
                    <div class="d-flex align-items-center gap-2">
                      <label class="mc-clr-wrap">
                        <input type="color" v-model="freqForm.color_code" class="mc-clr-inp"/>
                        <span class="mc-clr-sw" :style="{ background: freqForm.color_code }"></span>
                      </label>
                      <code class="mc-hex">{{ freqForm.color_code }}</code>
                    </div>
                  </div>
                </div>
                <div class="d-flex gap-2">
                  <b-button size="sm" variant="primary" @click="saveFrequency(freq.id)" :disabled="saving">
                    <i class="ti ti-device-floppy me-1"></i>Sauvegarder
                  </b-button>
                  <b-button size="sm" variant="light" @click="editingFreq = null">Annuler</b-button>
                </div>
              </div>

              <button v-if="editingFreq !== freq.id" class="mc-edit-btn"
                      @click="startFreqEdit(freq)">
                <i class="ti ti-pencil"></i>
              </button>
            </div>
          </div>

          <!-- ════ ZONES ════ -->
          <div v-if="tab === 'zones'" class="mc-panel">
            <div class="mc-panel-hd">
              <i class="ti ti-color-swatch me-2 text-warning"></i>
              <span class="fw-semibold small">
                Zones de criticité — {{ cfg.zones.length }} zones
              </span>
              <b-button size="sm" variant="outline-secondary" class="ms-auto"
                        @click="doResetZones" :disabled="saving">
                <i class="ti ti-refresh me-1"></i>Réinitialiser
              </b-button>
            </div>

            <!-- Graduation visuelle calculée depuis la base -->
            <div v-if="cfg.zones.length" class="mc-graduation">
              <div class="mc-graduation-lbl">Graduation des zones (scores de la base) :</div>
              <div class="mc-graduation-bar">
                <div
                  v-for="z in sortedZones" :key="z.id + '_g'"
                  class="mc-grad-seg"
                  :style="{
                    background: z.color_code,
                    flex: Math.max(1, z.max_score - z.min_score + 1)
                  }"
                  :title="`${z.label} — scores ${z.min_score} à ${z.max_score}`"
                >
                  <span class="mc-grad-lbl">{{ z.label }}</span>
                </div>
              </div>
              <div class="mc-graduation-scores">
                <div
                  v-for="z in sortedZones" :key="z.id + '_s'"
                  class="mc-grad-score-seg"
                  :style="{ flex: Math.max(1, z.max_score - z.min_score + 1) }"
                >
                  <small class="text-muted" style="font-size:.6rem">
                    {{ z.min_score }}–{{ z.max_score }}
                  </small>
                </div>
              </div>
            </div>

            <div v-for="z in sortedZones" :key="z.id" class="mc-row">
              <div class="mc-strip" :style="{ background: z.color_code }"></div>
              <div class="mc-zone-dot" :style="{ background: z.color_code }"></div>

              <!-- Vue normale -->
              <div v-if="editingZone !== z.id" class="flex-grow-1">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                  <span class="fw-bold" :style="{ color: z.color_code }">{{ z.label }}</span>
                  <span class="mc-range">[{{ z.min_score }} → {{ z.max_score }}]</span>
                  <code class="mc-hex">{{ z.color_code }}</code>
                  <span v-if="z.description" class="text-muted small">{{ z.description }}</span>
                </div>
              </div>

              <!-- Édition inline -->
              <div v-else class="flex-grow-1">
                <div class="row g-2 mb-2">
                  <div class="col-12 col-md-3">
                    <label class="mc-lbl">Label *</label>
                    <input v-model="zoneForm.label" class="form-control form-control-sm"/>
                  </div>
                  <div class="col-6 col-md-2">
                    <label class="mc-lbl">Score min *</label>
                    <input v-model.number="zoneForm.min_score" type="number" min="1"
                           :max="cfg.matrix_size * cfg.matrix_size"
                           class="form-control form-control-sm"/>
                  </div>
                  <div class="col-6 col-md-2">
                    <label class="mc-lbl">Score max *</label>
                    <input v-model.number="zoneForm.max_score" type="number"
                           :min="zoneForm.min_score"
                           :max="cfg.matrix_size * cfg.matrix_size"
                           class="form-control form-control-sm"/>
                  </div>
                  <div class="col-12 col-md-2">
                    <label class="mc-lbl">Couleur *</label>
                    <div class="d-flex align-items-center gap-2">
                      <label class="mc-clr-wrap">
                        <input type="color" v-model="zoneForm.color_code" class="mc-clr-inp"/>
                        <span class="mc-clr-sw" :style="{ background: zoneForm.color_code }"></span>
                      </label>
                      <code class="mc-hex">{{ zoneForm.color_code }}</code>
                    </div>
                  </div>
                  <div class="col-12 col-md-3">
                    <label class="mc-lbl">Description</label>
                    <input v-model="zoneForm.description" class="form-control form-control-sm"
                           placeholder="Optionnel…"/>
                  </div>
                </div>
                <div class="d-flex gap-2">
                  <b-button size="sm" variant="primary" @click="saveZone(z.id)" :disabled="saving">
                    <i class="ti ti-device-floppy me-1"></i>Sauvegarder
                  </b-button>
                  <b-button size="sm" variant="light" @click="editingZone = null">Annuler</b-button>
                </div>
              </div>

              <button v-if="editingZone !== z.id" class="mc-edit-btn"
                      @click="startZoneEdit(z)">
                <i class="ti ti-pencil"></i>
              </button>
            </div>
          </div>

        </template>
      </b-col>
    </b-row>

    <!-- ══ MODAL CRÉER ══ -->
    <b-modal v-model="createModal" title="Nouvelle configuration" size="md" hide-footer centered>
      <div class="row g-3">
        <div class="col-12">
          <label class="mc-lbl fw-semibold">Nom <span class="text-danger">*</span></label>
          <input v-model.trim="createForm.name" class="form-control form-control-sm"
                 placeholder="Ex : Matrice 5×5 standard"
                 @keydown.enter="doCreate"/>
          <div v-if="createErrors.name" class="text-danger small mt-1">
            {{ createErrors.name }}
          </div>
        </div>
        <div class="col-12 col-md-6">
          <label class="mc-lbl fw-semibold">Taille <span class="text-danger">*</span></label>
          <select v-model.number="createForm.matrix_size" class="form-select form-select-sm">
            <option v-for="s in [3,4,5,6,7,8,9,10]" :key="s" :value="s">
              {{ s }}×{{ s }} — max score {{ s * s }}
            </option>
          </select>
        </div>
        <div class="col-12">
          <label class="mc-lbl fw-semibold">Description</label>
          <textarea v-model.trim="createForm.description" class="form-control form-control-sm"
                    rows="2" placeholder="Contexte d'usage…"></textarea>
        </div>
      </div>
      <div class="mc-tip mt-3">
        <i class="ti ti-info-circle me-1"></i>
        Les impacts, fréquences et zones seront générés automatiquement depuis la base.
      </div>
      <div class="d-flex justify-content-end gap-2 mt-3 pt-3 border-top">
        <b-button variant="light" @click="createModal = false">Annuler</b-button>
        <b-button variant="primary" :disabled="createLoading" @click="doCreate">
          <span v-if="createLoading" class="spinner-border spinner-border-sm me-1"></span>
          <i v-else class="ti ti-sparkles me-1"></i>Créer
        </b-button>
      </div>
    </b-modal>

    <!-- ══ MODAL RENOMMER ══ -->
    <b-modal v-model="editModal" title="Renommer la configuration" size="md" hide-footer centered>
      <div class="row g-3">
        <div class="col-12">
          <label class="mc-lbl fw-semibold">Nom <span class="text-danger">*</span></label>
          <input v-model.trim="editForm.name" class="form-control form-control-sm"/>
          <div v-if="editErrors.name" class="text-danger small mt-1">{{ editErrors.name }}</div>
        </div>
        <div class="col-12">
          <label class="mc-lbl fw-semibold">Description</label>
          <textarea v-model.trim="editForm.description" class="form-control form-control-sm"
                    rows="2"></textarea>
        </div>
      </div>
      <div class="d-flex justify-content-end gap-2 mt-3 pt-3 border-top">
        <b-button variant="light" @click="editModal = false">Annuler</b-button>
        <b-button variant="primary" :disabled="editLoading" @click="doEdit">
          <span v-if="editLoading" class="spinner-border spinner-border-sm me-1"></span>
          <i v-else class="ti ti-device-floppy me-1"></i>Enregistrer
        </b-button>
      </div>
    </b-modal>

  </VerticalLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

// ── Props ─────────────────────────────────────────────────────────────────────
const props = defineProps({
  configs:        { type: Array,  default: () => [] },
  selectedId:     { type: Number, default: null },
  selectedConfig: { type: Object, default: null },
})

// ── État ──────────────────────────────────────────────────────────────────────
const currentId = ref(props.selectedId)
const cfg       = ref(props.selectedConfig)
const tab       = ref('impact')
const saving    = ref(false)

// Flash
const flash   = ref('')
const flashOk = ref(true)
let flashT    = null

// Modales
const createModal   = ref(false)
const createLoading = ref(false)
const createForm    = ref({ name: '', matrix_size: 5, description: '' })
const createErrors  = ref({})

const editModal   = ref(false)
const editLoading = ref(false)
const editForm    = ref({ name: '', description: '' })
const editErrors  = ref({})

// Éditions inline
const editingImpact = ref(null)
const editingFreq   = ref(null)
const editingZone   = ref(null)

const impForm  = ref({ label: '', description: '', color_code: '#6b7280' })
const freqForm = ref({ label: '', description: '', recurrence: '', color_code: '#6b7280' })
const zoneForm = ref({ label: '', min_score: 1, max_score: 1, color_code: '#6b7280', description: '' })

// ── Sync props Inertia après redirect ────────────────────────────────────────
watch(() => props.selectedConfig, v => { cfg.value = v })
watch(() => props.selectedId,     v => { currentId.value = v })

// ── Computed ──────────────────────────────────────────────────────────────────
const tabs = computed(() => [
  { id: 'impact',    label: 'Impacts',    icon: 'ti-arrow-up',     count: cfg.value?.impacts?.length     ?? 0 },
  { id: 'frequency', label: 'Fréquences', icon: 'ti-clock',        count: cfg.value?.frequencies?.length ?? 0 },
  { id: 'zones',     label: 'Zones',      icon: 'ti-color-swatch', count: cfg.value?.zones?.length        ?? 0 },
])

// Toutes les données triées viennent exclusivement de props (base de données)
const sortedImpacts     = computed(() => [...(cfg.value?.impacts     ?? [])].sort((a, b) => a.score      - b.score))
const sortedFrequencies = computed(() => [...(cfg.value?.frequencies ?? [])].sort((a, b) => a.score      - b.score))
const sortedZones       = computed(() => [...(cfg.value?.zones       ?? [])].sort((a, b) => a.sort_order - b.sort_order))

// ── Flash ─────────────────────────────────────────────────────────────────────
const showFlash = (msg, ok = true) => {
  if (flashT) clearTimeout(flashT)
  flash.value = msg; flashOk.value = ok
  flashT = setTimeout(() => { flash.value = '' }, 3200)
}

// ── Routage Inertia ───────────────────────────────────────────────────────────
const iput = (url, data, onDone) => {
  saving.value = true
  router.put(url, data, {
    preserveScroll: true,
    onSuccess: p => {
      saving.value = false
      onDone?.()
      if (p.props?.flash?.success) showFlash(p.props.flash.success)
    },
    onError: e => {
      saving.value = false
      showFlash(Object.values(e)[0] ?? 'Erreur', false)
    },
  })
}

const ipost = (url, data, onDone) => {
  router.post(url, data, {
    preserveScroll: true,
    onSuccess: p => {
      onDone?.()
      if (p.props?.flash?.success) showFlash(p.props.flash.success)
    },
    onError: e => { showFlash(Object.values(e)[0] ?? 'Erreur', false) },
  })
}

// ── Navigation ────────────────────────────────────────────────────────────────
const selectConfig = id => {
  router.get(route('risk.core.matrix-config.index'), { config_id: id }, {
    preserveState: false, preserveScroll: true,
  })
}

// ── Créer config ──────────────────────────────────────────────────────────────
const openCreate = () => {
  createForm.value   = { name: '', matrix_size: 5, description: '' }
  createErrors.value = {}
  createModal.value  = true
}

const doCreate = () => {
  createErrors.value = {}
  if (!createForm.value.name.trim()) {
    createErrors.value = { name: 'Le nom est obligatoire.' }; return
  }
  createLoading.value = true
  router.post(route('risk.core.matrix-config.store'), createForm.value, {
    onSuccess: () => { createModal.value = false; createLoading.value = false },
    onError: e  => { createErrors.value = e; createLoading.value = false },
  })
}

// ── Renommer config ───────────────────────────────────────────────────────────
const openEdit = () => {
  editForm.value   = { name: cfg.value?.name ?? '', description: cfg.value?.description ?? '' }
  editErrors.value = {}
  editModal.value  = true
}

const doEdit = () => {
  editErrors.value = {}
  if (!editForm.value.name.trim()) {
    editErrors.value = { name: 'Le nom est obligatoire.' }; return
  }
  editLoading.value = true
  router.put(route('risk.core.matrix-config.update', cfg.value.id), editForm.value, {
    onSuccess: () => { editModal.value = false; editLoading.value = false },
    onError: e  => { editErrors.value = e; editLoading.value = false },
  })
}

// ── Activer / Supprimer ───────────────────────────────────────────────────────
const doActivate = () => {
  if (!confirm('Activer cette configuration ?')) return
  ipost(route('risk.core.matrix-config.activate', cfg.value.id), {})
}

const doDelete = () => {
  if (!confirm(`Supprimer « ${cfg.value.name} » ? Action irréversible.`)) return
  router.delete(route('risk.core.matrix-config.destroy', cfg.value.id), {
    onSuccess: p => showFlash(p.props?.flash?.success ?? 'Supprimée.'),
    onError:   e => showFlash(Object.values(e)[0] ?? 'Erreur', false),
  })
}

// ── Édition impacts ───────────────────────────────────────────────────────────
const startImpactEdit = imp => {
  editingImpact.value = imp.id
  editingFreq.value   = null
  editingZone.value   = null
  impForm.value = { label: imp.label, description: imp.description ?? '', color_code: imp.color_code }
}
const saveImpact = id =>
  iput(route('risk.core.matrix-config.impact.update', id), impForm.value,
       () => { editingImpact.value = null })

// ── Édition fréquences ────────────────────────────────────────────────────────
const startFreqEdit = freq => {
  editingFreq.value   = freq.id
  editingImpact.value = null
  editingZone.value   = null
  freqForm.value = {
    label:       freq.label,
    description: freq.description ?? '',
    recurrence:  freq.recurrence  ?? '',
    color_code:  freq.color_code,
  }
}
const saveFrequency = id =>
  iput(route('risk.core.matrix-config.frequency.update', id), freqForm.value,
       () => { editingFreq.value = null })

// ── Édition zones ─────────────────────────────────────────────────────────────
const startZoneEdit = z => {
  editingZone.value   = z.id
  editingImpact.value = null
  editingFreq.value   = null
  zoneForm.value = {
    label:       z.label,
    min_score:   z.min_score,
    max_score:   z.max_score,
    color_code:  z.color_code,
    description: z.description ?? '',
  }
}
const saveZone = id =>
  iput(route('risk.core.matrix-config.zones.update', id), zoneForm.value,
       () => { editingZone.value = null })

const doResetZones = () => {
  if (!confirm('Réinitialiser les zones aux valeurs par défaut de la base ?')) return
  ipost(route('risk.core.matrix-config.reset-zones', cfg.value.id), {})
}
</script>

<style scoped>
/* ── LOGO ──────────────────────────────────────── */
.mc-logo {
  width:44px; height:44px; border-radius:10px; flex-shrink:0;
  background:linear-gradient(135deg,#1e293b,#1e3a5f);
  display:flex; align-items:center; justify-content:center;
  color:#93c5fd; font-size:22px;
}

/* ── FLASH ─────────────────────────────────────── */
.mc-flash {
  display:inline-flex; align-items:center; gap:6px;
  padding:5px 14px; border-radius:20px;
  font-size:.75rem; font-weight:600;
}
.mc-flash--ok  { background:#dcfce7; color:#15803d; border:1px solid #86efac; }
.mc-flash--err { background:#fee2e2; color:#dc2626; border:1px solid #fca5a5; }
.flash-enter-active, .flash-leave-active { transition:all .25s; }
.flash-enter-from, .flash-leave-to       { opacity:0; transform:translateY(-4px); }

/* ── PANEL ─────────────────────────────────────── */
.mc-panel { background:#fff; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; }
.mc-panel-hd {
  display:flex; align-items:center; gap:6px;
  padding:10px 14px; background:#f8fafc;
  border-bottom:1px solid #e2e8f0; font-size:.82rem;
}

/* ── LISTE ─────────────────────────────────────── */
.mc-list     { max-height:calc(100vh - 280px); overflow-y:auto; }
.mc-list-row {
  display:flex; align-items:center; gap:8px;
  padding:10px 14px; border-bottom:1px solid #f1f5f9;
  cursor:pointer; transition:background .1s;
}
.mc-list-row:hover       { background:#f8fafc; }
.mc-list-row--active     { background:#eff6ff; border-left:3px solid #3b82f6; }
.mc-list-sub             { font-size:.65rem; color:#94a3b8; margin-top:2px; }

/* ── BADGES ────────────────────────────────────── */
.mc-badge {
  display:inline-flex; align-items:center;
  font-size:.62rem; font-weight:600;
  padding:1px 7px; border-radius:20px;
}
.mc-badge--on  { background:#dcfce7; color:#15803d; }
.mc-badge--off { background:#f1f5f9; color:#64748b; }
.mc-size-tag {
  font-family:monospace; font-size:.68rem; font-weight:700;
  background:#e0e7ff; color:#4338ca;
  border-radius:4px; padding:1px 6px;
}

/* ── EN-TÊTE CONFIG ────────────────────────────── */
.mc-cfg-hd {
  display:flex; align-items:flex-start; gap:12px;
  background:linear-gradient(135deg,#f8fafc,#f1f5f9);
  border:1px solid #e2e8f0; border-radius:10px; padding:12px 16px;
}
.mc-cfg-av {
  width:42px; height:42px; border-radius:10px; flex-shrink:0;
  background:linear-gradient(135deg,#6366f1,#818cf8);
  color:#fff; font-weight:800; font-size:1rem;
  display:flex; align-items:center; justify-content:center;
}

/* ── TABS ──────────────────────────────────────── */
.mc-tabs { display:flex; gap:4px; background:#f1f5f9; border-radius:8px; padding:4px; }
.mc-tab {
  padding:6px 14px; border-radius:6px; border:none;
  background:transparent; cursor:pointer; font-size:.75rem;
  color:#64748b; font-weight:500; transition:all .12s;
  display:flex; align-items:center; gap:5px;
}
.mc-tab:hover    { background:#e2e8f0; color:#334155; }
.mc-tab--active  { background:#fff; color:#1e293b; font-weight:600; box-shadow:0 1px 4px rgba(0,0,0,.08); }
.mc-tab-ct {
  background:#6366f1; color:#fff;
  border-radius:10px; padding:0 5px; font-size:.6rem; font-weight:700;
}

/* ── LIGNES ────────────────────────────────────── */
.mc-row {
  display:flex; align-items:flex-start; gap:12px;
  padding:12px 14px 12px 0;
  border-bottom:1px solid #f1f5f9; transition:background .1s;
}
.mc-row:hover      { background:#fafafa; }
.mc-row:last-child { border-bottom:none; }
.mc-strip   { width:4px; align-self:stretch; flex-shrink:0; }
.mc-pill {
  width:28px; height:28px; border-radius:50%; flex-shrink:0;
  display:flex; align-items:center; justify-content:center;
  color:#fff; font-size:.75rem; font-weight:800;
}
.mc-zone-dot {
  width:14px; height:14px; border-radius:50%; flex-shrink:0; margin-top:4px;
}

/* ── COULEUR ───────────────────────────────────── */
.mc-clr-wrap { position:relative; display:inline-flex; align-items:center; cursor:pointer; }
.mc-clr-inp  { position:absolute; width:1px; height:1px; opacity:0; pointer-events:none; }
.mc-clr-sw   {
  width:28px; height:28px; border-radius:6px;
  border:2px solid rgba(0,0,0,.12); display:inline-block;
  cursor:pointer; transition:transform .1s;
}
.mc-clr-sw:hover { transform:scale(1.1); }
.mc-color-dot {
  width:12px; height:12px; border-radius:50%;
  display:inline-block; border:1px solid rgba(0,0,0,.1); flex-shrink:0;
}

/* ── TEXTES ────────────────────────────────────── */
.mc-hex  { font-family:monospace; font-size:.68rem; color:#475569; }
.mc-desc { font-size:.68rem; color:#64748b; margin-top:3px; }
.mc-rec  { font-size:.63rem; background:#f1f5f9; color:#475569; padding:1px 6px; border-radius:4px; font-family:monospace; }
.mc-range{ font-family:monospace; font-size:.65rem; background:#f1f5f9; color:#475569; padding:1px 6px; border-radius:4px; }
.mc-lbl  { font-size:.68rem; font-weight:600; color:#475569; display:block; margin-bottom:2px; }

/* ── BOUTON ÉDITER ─────────────────────────────── */
.mc-edit-btn {
  background:none; border:none; cursor:pointer;
  color:#cbd5e1; padding:4px 8px; border-radius:6px;
  font-size:14px; flex-shrink:0; opacity:0; transition:all .15s;
}
.mc-row:hover .mc-edit-btn { opacity:1; color:#6366f1; }

/* ── GRADUATION ZONES ──────────────────────────── */
.mc-graduation {
  padding:10px 14px; background:#f8fafc;
  border-bottom:1px solid #e2e8f0;
}
.mc-graduation-lbl {
  font-size:.65rem; text-transform:uppercase; color:#94a3b8;
  font-weight:700; margin-bottom:6px;
}
.mc-graduation-bar {
  display:flex; border-radius:6px; overflow:hidden; height:26px;
}
.mc-grad-seg {
  display:flex; align-items:center; justify-content:center; min-width:0;
}
.mc-grad-lbl {
  font-size:.6rem; font-weight:700; color:#fff;
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
  padding:0 4px; text-shadow:0 1px 2px rgba(0,0,0,.3);
}
.mc-graduation-scores { display:flex; margin-top:3px; }
.mc-grad-score-seg    { display:flex; justify-content:center; min-width:0; }

/* ── EMPTY ─────────────────────────────────────── */
.mc-empty {
  display:flex; flex-direction:column; align-items:center; justify-content:center;
  min-height:300px; background:#f8fafc;
  border-radius:10px; border:2px dashed #e2e8f0;
}

/* ── TIP ───────────────────────────────────────── */
.mc-tip {
  font-size:.72rem; color:#64748b;
  background:#f0f9ff; border:1px solid #bae6fd;
  border-radius:6px; padding:8px 12px;
  display:flex; align-items:flex-start; gap:6px;
}

/* ── FORM ──────────────────────────────────────── */
.form-control-sm, .form-select-sm { font-size:.75rem; height:28px; padding:.18rem .45rem; }
textarea.form-control-sm          { height:auto; }
.btn-sm { font-size:.72rem; padding:.15rem .5rem; }
</style>