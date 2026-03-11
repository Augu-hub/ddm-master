<template>
  <div class="container-fluid py-4">
    <Head title="🏛️ Gestion Audit Factors" />

    <div class="page-header mb-4">
      <div class="row align-items-center">
        <div class="col">
          <h1 class="mb-1 fw-bold">🏛️ Gestion des Facteurs d'Audit</h1>
          <p class="text-muted mb-0">Gérer les facteurs avec poids et couleurs</p>
        </div>
        <div class="col-auto">
          <a :href="route('audit.core.audit.factors.export')" class="btn btn-outline-success btn-sm">
            <i class="ti ti-download"></i> Export CSV
          </a>
        </div>
      </div>
    </div>

    <!-- ONGLETS PRINCIPAUX -->
    <ul class="nav nav-tabs nav-fill mb-3">
      <li class="nav-item">
        <a class="nav-link" :class="{ active: mainTab === 'factors' }" @click.prevent="mainTab = 'factors'" href="#!">
          <i class="ti ti-list-check"></i> Facteurs ({{ allFactors.length }})
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" :class="{ active: mainTab === 'scales' }" @click.prevent="mainTab = 'scales'" href="#!">
          <i class="ti ti-ruler-2"></i> Échelles ({{ allScales.length }})
        </a>
      </li>
    </ul>

    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <!-- FACTEURS -->
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <div v-show="mainTab === 'factors'">
      <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
          <a class="nav-link" :class="{ active: factorView === 'list' }" @click.prevent="factorView = 'list'" href="#!">
            <i class="ti ti-list"></i> Liste
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" :class="{ active: factorView === 'add' }" @click.prevent="factorView = 'add'" href="#!">
            <i class="ti ti-plus"></i> Ajouter
          </a>
        </li>
      </ul>

      <!-- STATS -->
      <div class="row g-2 mb-3">
        <div class="col-md-3">
          <div class="card border-0 shadow-sm">
            <div class="card-body p-3">
              <small class="text-muted d-block">Total</small>
              <h4 class="mb-0 fw-bold">{{ allFactors.length }}</h4>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card border-0 shadow-sm">
            <div class="card-body p-3">
              <small class="text-muted d-block">Actifs</small>
              <h4 class="mb-0 fw-bold">{{ activeCount }}</h4>
            </div>
          </div>
        </div>
      </div>

      <!-- LISTE FACTEURS -->
      <div v-show="factorView === 'list'" class="card border-0 shadow-sm">
        <div class="card-header bg-light">
          <div class="row g-2 align-items-center">
            <div class="col"><h6 class="mb-0"><i class="ti ti-list"></i> Facteurs</h6></div>
            <div class="col-auto">
              <input v-model="searchFactors" type="text" class="form-control form-control-sm" placeholder="🔍 Rechercher..." style="width: 200px;" />
            </div>
          </div>
        </div>
        <div class="card-body p-0">
          <div v-if="sortedFactors.length > 0" class="table-responsive">
            <table class="table table-hover mb-0 table-sm">
              <thead class="table-light">
                <tr>
                  <th style="width: 60px">Pos.</th>
                  <th>Libellé</th>
                  <th style="width: 80px">Poids (%)</th>
                  <th>Description</th>
                  <th style="width: 80px">Statut</th>
                  <th style="width: 280px">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(f, idx) in sortedFactors" :key="f.id">
                  <td class="fw-bold text-center">
                    <span class="badge bg-secondary">{{ f.order_position }}</span>
                  </td>
                  <td class="fw-semibold">{{ f.label }}</td>
                  <td class="text-center">
                    <span class="badge bg-info">{{ f.weight || 25 }}%</span>
                  </td>
                  <td><small class="text-muted">{{ f.description || '—' }}</small></td>
                  <td>
                    <span class="badge" :class="f.is_active ? 'bg-success' : 'bg-danger'">
                      {{ f.is_active ? 'Actif' : 'Inactif' }}
                    </span>
                  </td>
                  <td>
                    <!-- FLÈCHE UP -->
                    <button 
                      v-if="idx > 0"
                      @click="moveUp(idx)" 
                      class="btn btn-outline-primary btn-xs"
                      title="Monter"
                    >
                      <i class="ti ti-arrow-up"></i>
                    </button>
                    <button v-else class="btn btn-outline-secondary btn-xs" disabled>
                      <i class="ti ti-arrow-up"></i>
                    </button>

                    <!-- FLÈCHE DOWN -->
                    <button 
                      v-if="idx < sortedFactors.length - 1"
                      @click="moveDown(idx)" 
                      class="btn btn-outline-primary btn-xs"
                      title="Descendre"
                    >
                      <i class="ti ti-arrow-down"></i>
                    </button>
                    <button v-else class="btn btn-outline-secondary btn-xs" disabled>
                      <i class="ti ti-arrow-down"></i>
                    </button>

                    <!-- ÉDITER -->
                    <button @click="openEditModal(f)" class="btn btn-info btn-xs" title="Éditer">
                      <i class="ti ti-edit"></i>
                    </button>

                    <!-- TOGGLE STATUT -->
                    <button @click="toggleStatus(f.id)" class="btn btn-secondary btn-xs" title="Toggle statut">
                      <i class="ti ti-power"></i>
                    </button>

                    <!-- SUPPRIMER -->
                    <button @click="removeFactor(f.id)" class="btn btn-danger btn-xs" title="Supprimer">
                      <i class="ti ti-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="text-center py-5 text-muted">Aucun facteur</div>
        </div>
      </div>

      <!-- AJOUTER FACTEUR -->
      <div v-show="factorView === 'add'" class="row justify-content-center">
        <div class="col-lg-6">
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-light"><h5 class="mb-0"><i class="ti ti-plus"></i> Ajouter Facteur</h5></div>
            <div class="card-body">
              <form @submit.prevent="createFactor">
                <div class="mb-3">
                  <label class="form-label fw-semibold">Libellé *</label>
                  <input v-model.trim="form.label" type="text" class="form-control form-control-sm" placeholder="Ex: Conformité..." required />
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Description</label>
                  <textarea v-model.trim="form.description" class="form-control form-control-sm" rows="3" placeholder="Détails..."></textarea>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Poids (%) - Par défaut: 25</label>
                  <input v-model.number="form.weight" type="number" class="form-control form-control-sm" min="0" max="100" placeholder="25" />
                </div>
                <div class="form-check form-switch mb-3">
                  <input id="active" v-model="form.is_active" type="checkbox" class="form-check-input" checked />
                  <label for="active" class="form-check-label">Actif</label>
                </div>
                <button type="submit" class="btn btn-primary btn-sm" :disabled="loading">{{ loading ? 'Création...' : 'Créer' }}</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <!-- ÉCHELLES -->
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <div v-show="mainTab === 'scales'">
      <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
          <a class="nav-link" :class="{ active: scaleView === 'list' }" @click.prevent="scaleView = 'list'" href="#!">
            <i class="ti ti-list"></i> Liste
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" :class="{ active: scaleView === 'add' }" @click.prevent="scaleView = 'add'" href="#!">
            <i class="ti ti-plus"></i> Ajouter
          </a>
        </li>
      </ul>

      <!-- LISTE ÉCHELLES -->
      <div v-show="scaleView === 'list'" class="card border-0 shadow-sm">
        <div class="card-header bg-light">
          <h6 class="mb-0"><i class="ti ti-ruler-2"></i> Échelles ({{ allScales.length }})</h6>
        </div>
        <div class="card-body p-0">
          <div v-if="allScales.length > 0" class="table-responsive">
            <table class="table table-hover mb-0 table-sm">
              <thead class="table-light">
                <tr>
                  <th style="width: 60px">Valeur</th>
                  <th>Libellé</th>
                  <th style="width: 120px">Couleur</th>
                  <th>Description</th>
                  <th style="width: 140px">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="scale in sortedScales" :key="scale.id">
                  <td class="fw-bold text-center">
                    <span class="badge" :style="{ backgroundColor: scale.color }">{{ scale.value }}</span>
                  </td>
                  <td class="fw-semibold">{{ scale.label }}</td>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div style="width: 30px; height: 30px; border-radius: 4px; border: 1px solid #ddd;" :style="{ backgroundColor: scale.color }"></div>
                      <code style="font-size: 0.75rem;">{{ scale.color }}</code>
                    </div>
                  </td>
                  <td><small class="text-muted">{{ scale.description || '—' }}</small></td>
                  <td>
                    <button @click="openEditScaleModal(scale)" class="btn btn-info btn-xs" title="Éditer">
                      <i class="ti ti-edit"></i>
                    </button>
                    <button @click="deleteScale(scale.id)" class="btn btn-danger btn-xs" title="Supprimer">
                      <i class="ti ti-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="text-center py-5 text-muted">Aucune échelle</div>
        </div>
      </div>

      <!-- AJOUTER ÉCHELLE -->
      <div v-show="scaleView === 'add'" class="row justify-content-center">
        <div class="col-lg-6">
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-light"><h5 class="mb-0"><i class="ti ti-plus"></i> Créer Échelle</h5></div>
            <div class="card-body">
              <form @submit.prevent="createScale">
                <div class="mb-3">
                  <label class="form-label fw-semibold">Valeur (1-5) *</label>
                  <input v-model.number="scaleForm.value" type="number" class="form-control form-control-sm" min="1" max="5" required />
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Libellé *</label>
                  <input v-model.trim="scaleForm.label" type="text" class="form-control form-control-sm" placeholder="Ex: Faible..." required />
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Couleur *</label>
                  <div class="input-group input-group-sm">
                    <input v-model.trim="scaleForm.color" type="text" class="form-control" placeholder="#D32F2F" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" required />
                    <input v-model="scaleForm.color" type="color" class="form-control form-control-color" style="width: 50px; cursor: pointer;" />
                  </div>
                  <small class="text-muted d-block mt-1">Format HEX (ex: #D32F2F)</small>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Description</label>
                  <textarea v-model.trim="scaleForm.description" class="form-control form-control-sm" rows="2" placeholder="Détails..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-sm" :disabled="loading">{{ loading ? 'Création...' : 'Créer' }}</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL ÉDITION FACTEUR -->
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="editModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Modifier Facteur</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <form @submit.prevent="updateFactor" v-if="editingFactor">
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label fw-semibold">Libellé *</label>
                <input v-model.trim="editingFactor.label" type="text" class="form-control form-control-sm" required />
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <textarea v-model.trim="editingFactor.description" class="form-control form-control-sm" rows="3"></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Poids (%) *</label>
                <input v-model.number="editingFactor.weight" type="number" class="form-control form-control-sm" min="0" max="100" required />
              </div>
              <div class="form-check form-switch">
                <input id="edit_active" v-model="editingFactor.is_active" type="checkbox" class="form-check-input" />
                <label for="edit_active" class="form-check-label">Actif</label>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
              <button type="submit" class="btn btn-primary btn-sm" :disabled="loading">Mettre à jour</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL ÉDITION ÉCHELLE -->
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="editScaleModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Modifier Échelle</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <form @submit.prevent="updateScale" v-if="editingScale">
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label fw-semibold">Valeur (1-5) *</label>
                <input v-model.number="editingScale.value" type="number" class="form-control form-control-sm" min="1" max="5" required />
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Libellé *</label>
                <input v-model.trim="editingScale.label" type="text" class="form-control form-control-sm" required />
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Couleur *</label>
                <div class="input-group input-group-sm">
                  <input v-model.trim="editingScale.color" type="text" class="form-control" placeholder="#D32F2F" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" required />
                  <input v-model="editingScale.color" type="color" class="form-control form-control-color" style="width: 50px; cursor: pointer;" />
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <textarea v-model.trim="editingScale.description" class="form-control form-control-sm" rows="2"></textarea>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
              <button type="submit" class="btn btn-primary btn-sm" :disabled="loading">Mettre à jour</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
  factors: { type: Array, default: () => [] },
  scales: { type: Array, default: () => [] }
})

// STATE
const mainTab = ref('factors')
const factorView = ref('list')
const scaleView = ref('list')
const loading = ref(false)
const searchFactors = ref('')

// DATA
const allFactors = ref(props.factors || [])
const allScales = ref(props.scales || [])
const editingFactor = ref(null)
const editingScale = ref(null)

// FORMS
const form = ref({ label: '', description: '', weight: 25, is_active: true })
const scaleForm = ref({ value: 1, label: '', description: '', color: '#D32F2F' })

// COMPUTED
const sortedFactors = computed(() => {
  let result = allFactors.value.filter(f =>
    f.label.toLowerCase().includes(searchFactors.value.toLowerCase()) ||
    (f.description && f.description.toLowerCase().includes(searchFactors.value.toLowerCase()))
  )
  return result.sort((a, b) => a.order_position - b.order_position)
})

const sortedScales = computed(() => {
  return allScales.value.sort((a, b) => a.value - b.value)
})

const activeCount = computed(() => allFactors.value.filter(f => f.is_active).length)

// ═════════════════════════════════════════════════════════════════════════════
// FACTEURS
// ═════════════════════════════════════════════════════════════════════════════
const createFactor = () => {
  if (!form.value.label) return
  loading.value = true
  router.post(route('audit.core.audit.factors.store'), form.value, {
    onSuccess: (page) => {
      allFactors.value = page.props.factors || []
      form.value = { label: '', description: '', weight: 25, is_active: true }
      factorView.value = 'list'
    },
    onFinish: () => { loading.value = false }
  })
}

const openEditModal = (factor) => {
  editingFactor.value = { ...factor }
  new window.bootstrap.Modal(document.getElementById('editModal')).show()
}

const updateFactor = () => {
  if (!editingFactor.value) return
  loading.value = true
  router.put(route('audit.core.audit.factors.update', editingFactor.value.id), editingFactor.value, {
    onSuccess: (page) => {
      allFactors.value = page.props.factors || []
      editingFactor.value = null
      const modal = window.bootstrap.Modal.getInstance(document.getElementById('editModal'))
      modal?.hide()
    },
    onFinish: () => { loading.value = false }
  })
}

const moveUp = (idx) => {
  if (idx <= 0) return
  const current = sortedFactors.value[idx]
  const previous = sortedFactors.value[idx - 1]
  const temp = current.order_position
  current.order_position = previous.order_position
  previous.order_position = temp
  savePositions()
}

const moveDown = (idx) => {
  if (idx >= sortedFactors.value.length - 1) return
  const current = sortedFactors.value[idx]
  const next = sortedFactors.value[idx + 1]
  const temp = current.order_position
  current.order_position = next.order_position
  next.order_position = temp
  savePositions()
}

const savePositions = () => {
  const orders = sortedFactors.value.map((f, idx) => ({
    id: f.id,
    position: idx + 1
  }))
  router.put(route('audit.core.audit.factors.reorder'), { orders }, {
    onSuccess: (page) => {
      allFactors.value = page.props.factors || []
    }
  })
}

const removeFactor = (id) => {
  if (!confirm('Êtes-vous sûr de vouloir supprimer?')) return
  router.delete(route('audit.core.audit.factors.destroy', id), {
    onSuccess: (page) => {
      allFactors.value = page.props.factors || []
    }
  })
}

const toggleStatus = (id) => {
  router.put(route('audit.core.audit.factors.toggle', id), {}, {
    onSuccess: (page) => {
      allFactors.value = page.props.factors || []
    }
  })
}

// ═════════════════════════════════════════════════════════════════════════════
// ÉCHELLES
// ═════════════════════════════════════════════════════════════════════════════
const createScale = () => {
  if (!scaleForm.value.value || !scaleForm.value.label || !scaleForm.value.color) return
  loading.value = true
  router.post(route('audit.core.audit.factor-scales.store'), scaleForm.value, {
    onSuccess: (page) => {
      allScales.value = page.props.scales || []
      scaleForm.value = { value: 1, label: '', description: '', color: '#D32F2F' }
      scaleView.value = 'list'
    },
    onFinish: () => { loading.value = false }
  })
}

const openEditScaleModal = (scale) => {
  editingScale.value = { ...scale }
  new window.bootstrap.Modal(document.getElementById('editScaleModal')).show()
}

const updateScale = () => {
  if (!editingScale.value) return
  loading.value = true
  router.put(route('audit.core.audit.factor-scales.update', editingScale.value.id), editingScale.value, {
    onSuccess: (page) => {
      allScales.value = page.props.scales || []
      editingScale.value = null
      const modal = window.bootstrap.Modal.getInstance(document.getElementById('editScaleModal'))
      modal?.hide()
    },
    onFinish: () => { loading.value = false }
  })
}

const deleteScale = (id) => {
  if (!confirm('Êtes-vous sûr?')) return
  router.delete(route('audit.core.audit.factor-scales.destroy', id), {
    onSuccess: (page) => {
      allScales.value = page.props.scales || []
    }
  })
}
</script>

<style scoped>
.page-header { border-bottom: 2px solid #f0f0f0; padding-bottom: 1rem; }
.nav-link { cursor: pointer; color: #666; transition: all 0.3s; }
.nav-link.active { background-color: #0d6efd; color: white; }
.btn-xs { padding: 0.25rem 0.5rem; font-size: 0.75rem; margin: 0 2px; }
table td { vertical-align: middle; }
</style>