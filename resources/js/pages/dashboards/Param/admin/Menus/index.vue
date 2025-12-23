<template>
  <VerticalLayout>
    <Head title="Administration — Gestion des Menus" />

    <!-- Header avec gradient -->
    <div class="page-header mb-4">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="icon-wrapper">
            <i class="ti ti-menu-2 fs-3"></i>
          </div>
          <div>
            <h3 class="mb-1 fw-bold">Gestion des Menus</h3>
            <p class="text-muted mb-0 small">Créez, organisez et personnalisez votre arborescence</p>
          </div>
        </div>
        <div class="header-actions">
          <b-button variant="primary" @click="showCreateModal" class="modern-btn">
            <i class="ti ti-plus me-2"></i>
            Ajouter un Menu
          </b-button>
        </div>
      </div>
    </div>

    <!-- Statistiques -->
    <b-row class="g-3 mb-4">
      <b-col md="3">
        <div class="stat-card stat-primary">
          <div class="stat-icon">
            <i class="ti ti-list"></i>
          </div>
          <div class="stat-content">
            <h3 class="stat-value">{{ totalMenus }}</h3>
            <p class="stat-label">Menus Total</p>
          </div>
        </div>
      </b-col>
      <b-col md="3">
        <div class="stat-card stat-success">
          <div class="stat-icon">
            <i class="ti ti-hierarchy"></i>
          </div>
          <div class="stat-content">
            <h3 class="stat-value">{{ totalWithChildren }}</h3>
            <p class="stat-label">Avec sous-menus</p>
          </div>
        </div>
      </b-col>
      <b-col md="3">
        <div class="stat-card stat-info">
          <div class="stat-icon">
            <i class="ti ti-eye"></i>
          </div>
          <div class="stat-content">
            <h3 class="stat-value">{{ totalVisible }}</h3>
            <p class="stat-label">Visibles</p>
          </div>
        </div>
      </b-col>
      <b-col md="3">
        <div class="stat-card stat-warning">
          <div class="stat-icon">
            <i class="ti ti-database-cog"></i>
          </div>
          <div class="stat-content">
            <h3 class="stat-value">{{ totalWithModule }}</h3>
            <p class="stat-label">Avec modules</p>
          </div>
        </div>
      </b-col>
    </b-row>

    <!-- Filtres et recherche -->
    <div class="filters-bar mb-4">
      <b-row class="g-2">
        <b-col md="4">
          <div class="search-box">
            <i class="ti ti-search"></i>
            <b-form-input
              v-model="searchQuery"
              placeholder="Rechercher un menu..."
              class="search-input"
            />
          </div>
        </b-col>
        <b-col md="4">
          <b-form-select
            v-model="filterType"
            :options="typeOptions"
            class="modern-select"
          />
        </b-col>
        <b-col md="4">
          <b-form-select
            v-model="filterVisibility"
            :options="visibilityOptions"
            class="modern-select"
          />
        </b-col>
      </b-row>
    </div>

    <!-- Vue arborescente des menus -->
    <div class="menus-container">
      <div v-if="filteredMenus.length === 0" class="empty-state">
        <i class="ti ti-list-off fs-1 text-muted mb-3"></i>
        <p class="text-muted mb-0">Aucun menu trouvé</p>
        <small class="text-muted">Créez votre premier menu pour commencer</small>
      </div>

      <div v-else class="menus-tree" ref="menusTreeEl">
        <menu-item
          v-for="menu in filteredMenus"
          :key="menu.id"
          :menu="menu"
          :modules="modules"
          :icons="icons"
          @edit="editMenu"
          @delete="deleteMenu"
          @add-child="addChildMenu"
          @update="updateMenu"
          @toggle-visibility="toggleVisibility"
        />
      </div>
    </div>

    <!-- Modal de création/édition -->
    <b-modal
      v-model="modalState.show"
      :title="modalState.isEdit ? `Modifier — ${modalState.form.label}` : 'Créer un Menu'"
      size="xl"
      @hidden="resetModal"
      centered
    >
      <b-form @submit.prevent="saveMenu">
        <!-- Section Module (PRIORITAIRE) -->
        <div class="form-section-header">
          <i class="ti ti-database me-2"></i>
          <h6>📌 Module (détermine tout)</h6>
        </div>

        <b-row class="g-3 mb-4">
          <b-col md="12">
            <label class="form-label">Sélectionner un Module</label>
            <b-form-select
              v-model="modalState.form.module_id"
              :options="moduleOptions"
              class="modern-select"
              @change="applyModuleTemplate"
            />
            <small class="form-hint">Sélectionnez un module pour auto-remplir les champs</small>
          </b-col>
        </b-row>

        <!-- Section Libellé et Clé -->
        <div class="form-section-header">
          <i class="ti ti-tags me-2"></i>
          <h6>Identité du Menu</h6>
        </div>

        <b-row class="g-3 mb-4">
          <b-col md="6">
            <label class="form-label">Libellé<span class="text-danger">*</span></label>
            <b-form-input
              v-model="modalState.form.label"
              placeholder="ex: Utilisateurs, Processus, Risques..."
              class="modern-input"
              required
              @change="autoGenerateFromLabel"
            />
            <small class="form-hint">Change automatiquement clé et icône</small>
          </b-col>

          <b-col md="6">
            <label class="form-label">Clé unique<span class="text-danger">*</span></label>
            <div class="input-group">
              <b-form-input
                v-model="modalState.form.key"
                placeholder="auto-générée"
                class="modern-input"
                required
                :disabled="modalState.isEdit"
              />
              <b-button 
                variant="outline-secondary"
                @click="regenerateKey"
                v-if="!modalState.isEdit"
                v-b-tooltip.hover="'Régénérer depuis le libellé'"
              >
                <i class="ti ti-sparkles"></i>
              </b-button>
            </div>
            <small class="form-hint">Générée automatiquement depuis le libellé</small>
          </b-col>
        </b-row>

        <!-- Section Apparence -->
        <div class="form-section-header">
          <i class="ti ti-palette me-2"></i>
          <h6>Apparence</h6>
        </div>

        <b-row class="g-3 mb-4">
          <b-col md="6">
            <label class="form-label">Type<span class="text-danger">*</span></label>
            <b-form-select
              v-model="modalState.form.type"
              :options="typeOptions"
              class="modern-select"
              required
            />
          </b-col>

          <b-col md="6">
            <label class="form-label">Icône (auto-générée)</label>
            <div class="icon-display">
              <i v-if="modalState.form.icon" :class="modalState.form.icon" class="preview-icon"></i>
              <span>{{ modalState.form.icon || 'Auto-détectée' }}</span>
            </div>
            <small class="form-hint">Détectée automatiquement. Cliquez pour changer :</small>
            <div class="icon-picker-compact mt-2">
              <div v-for="icon in icons" :key="icon" class="icon-option-small" @click="modalState.form.icon = icon">
                <i :class="icon"></i>
              </div>
            </div>
          </b-col>
        </b-row>

        <!-- Section Navigation -->
        <div class="form-section-header">
          <i class="ti ti-link me-2"></i>
          <h6>Navigation (auto-générée du module)</h6>
        </div>

        <b-row class="g-3 mb-4">
          <b-col md="6">
            <label class="form-label">URL</label>
            <b-form-input
              v-model="modalState.form.url"
              placeholder="Auto du module"
              class="modern-input"
            />
          </b-col>

          <b-col md="6">
            <label class="form-label">Route</label>
            <b-form-input
              v-model="modalState.form.route_name"
              placeholder="Auto du module"
              class="modern-input"
            />
          </b-col>
        </b-row>

        <!-- Section Attributs -->
        <div class="form-section-header">
          <i class="ti ti-settings me-2"></i>
          <h6>Attributs</h6>
        </div>

        <b-row class="g-3 mb-4">
          <b-col md="4">
            <label class="form-label">Ordre d'affichage</label>
            <b-form-input
              v-model.number="modalState.form.sort"
              type="number"
              min="1"
              class="modern-input"
            />
          </b-col>

          <b-col md="4">
            <label class="form-label">Cible du lien</label>
            <b-form-select
              v-model="modalState.form.target"
              :options="[
                { value: null, text: 'Même fenêtre' },
                { value: '_blank', text: 'Nouvelle fenêtre' }
              ]"
              class="modern-select"
            />
          </b-col>

          <b-col md="4">
            <div class="form-check-wrapper">
              <b-form-checkbox
                v-model="modalState.form.visible"
                class="form-check-modern"
              >
                <span class="checkbox-label">
                  <i class="ti ti-eye me-2"></i>
                  Visible
                </span>
              </b-form-checkbox>
            </div>
          </b-col>
        </b-row>

        <!-- Badge et Tooltip (auto du module) -->
        <b-row class="g-3">
          <b-col md="6">
            <label class="form-label">Badge (JSON)</label>
            <b-form-textarea
              v-model="modalState.form.badge_json"
              placeholder='Auto du module'
              rows="2"
              class="modern-input"
            />
            <small class="form-hint">Auto-généré du module</small>
          </b-col>

          <b-col md="6">
            <label class="form-label">Tooltip (JSON)</label>
            <b-form-textarea
              v-model="modalState.form.tooltip_json"
              placeholder='{"text":"Description"}'
              rows="2"
              class="modern-input"
            />
          </b-col>
        </b-row>
      </b-form>

      <template #modal-footer>
        <b-button variant="light" @click="modalState.show = false">
          <i class="ti ti-x me-2"></i>
          Annuler
        </b-button>
        <b-button
          variant="primary"
          @click="saveMenu"
          :disabled="modalState.loading"
        >
          <i class="ti ti-check me-2"></i>
          {{ modalState.loading ? 'Enregistrement...' : 'Enregistrer' }}
        </b-button>
      </template>
    </b-modal>

    <!-- Modal de suppression -->
    <b-modal
      v-model="deleteModal.show"
      title="Confirmer la suppression"
      centered
      @hidden="deleteModal.show = false"
    >
      <div class="alert alert-warning mb-3">
        <i class="ti ti-alert-triangle me-2"></i>
        <strong>Attention !</strong> Cette action est irréversible.
      </div>
      <p class="mb-3">
        Vous vous apprêtez à supprimer le menu <strong>{{ deleteModal.menu?.label }}</strong>
        <span v-if="deleteModal.menu?.children?.length > 0" class="text-danger d-block small mt-2">
          ({{ deleteModal.menu.children.length }} sous-menu(s) seront aussi supprimés)
        </span>
      </p>

      <template #modal-footer>
        <b-button variant="light" @click="deleteModal.show = false">
          Annuler
        </b-button>
        <b-button
          variant="danger"
          @click="confirmDelete"
          :disabled="deleteModal.loading"
        >
          {{ deleteModal.loading ? 'Suppression...' : 'Supprimer' }}
        </b-button>
      </template>
    </b-modal>
  </VerticalLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, watch } from 'vue'
import Sortable from 'sortablejs'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
import MenuItem from './MenuManagement/MenuItem.vue'

// Templates de modules
const MODULE_TEMPLATES = {
  'param.projects': {
    icon: 'ti ti-adjustments',
    badge_json: JSON.stringify({ text: 'Config', variant: 'info' }),
    url: '/m/param.projects',
    route_name: 'param.projects.home'
  },
  'process.core': {
    icon: 'ti ti-flow-branch',
    badge_json: JSON.stringify({ text: 'Processus', variant: 'primary' }),
    url: '/m/process.core',
    route_name: 'process.core.home'
  },
  'risk.core': {
    icon: 'ti ti-alert-triangle',
    badge_json: JSON.stringify({ text: 'Risques', variant: 'danger' }),
    url: '/m/risk.core',
    route_name: 'risk.core.home'
  },
  'audit.core': {
    icon: 'ti ti-checklist',
    badge_json: JSON.stringify({ text: 'Audit', variant: 'warning' }),
    url: '/m/audit.core',
    route_name: 'audit.core.home'
  },
  'admin.core': {
    icon: 'ti ti-lock',
    badge_json: JSON.stringify({ text: 'Admin', variant: 'secondary' }),
    url: '/m/admin.core',
    route_name: 'admin.core.home'
  }
}

// Détection d'icônes
const ICON_DETECTION = {
  'utilisateurs': 'ti ti-users',
  'users': 'ti ti-users',
  'paramètres': 'ti ti-settings',
  'processus': 'ti ti-flow-branch',
  'risques': 'ti ti-alert-triangle',
  'audit': 'ti ti-checklist',
  'administration': 'ti ti-lock',
  'dashboard': 'ti ti-layout-dashboard',
  'tableau de bord': 'ti ti-layout-dashboard',
  'accueil': 'ti ti-home',
  'projets': 'ti ti-folders',
  'entités': 'ti ti-building-community',
  'modules': 'ti ti-apps',
  'rapports': 'ti ti-report-analytics',
  'raci': 'ti ti-users-group',
  'maturité': 'ti ti-stairs-up',
  'criticité': 'ti ti-flame'
}

const props = defineProps({
  menus: { type: Array, default: () => [] },
  modules: { type: Array, default: () => [] },
})

const menus = ref(props.menus)
const searchQuery = ref('')
const filterType = ref(null)
const filterVisibility = ref(null)

const typeOptions = [
  { value: null, text: 'Tous les types' },
  { value: 'item', text: 'Élément simple' },
  { value: 'title', text: 'Titre/Section' },
  { value: 'divider', text: 'Séparateur' }
]

const visibilityOptions = [
  { value: null, text: 'Tous' },
  { value: true, text: 'Visibles uniquement' },
  { value: false, text: 'Cachés uniquement' }
]

const icons = [
  'ti ti-dashboard', 'ti ti-home', 'ti ti-settings', 'ti ti-folder', 'ti ti-folders',
  'ti ti-building', 'ti ti-users', 'ti ti-user', 'ti ti-lock', 'ti ti-database',
  'ti ti-chart', 'ti ti-report', 'ti ti-bell', 'ti ti-check', 'ti ti-menu',
  'ti ti-eye', 'ti ti-link', 'ti ti-grid', 'ti ti-adjustments', 'ti ti-flow-branch',
  'ti ti-alert-triangle', 'ti ti-checklist', 'ti ti-building-community', 'ti ti-apps',
  'ti ti-packages', 'ti ti-report-analytics', 'ti ti-users-group', 'ti ti-bulb',
  'ti ti-stairs-up', 'ti ti-flame', 'ti ti-arrows-exchange-2', 'ti ti-topology-star'
]

const modalState = ref({
  show: false,
  isEdit: false,
  loading: false,
  errors: {},
  form: {
    key: '',
    label: '',
    type: 'item',
    icon: '',
    url: '',
    route_name: '',
    sort: 10,
    module_id: null,
    visible: true,
    badge_json: '',
    tooltip_json: '',
    target: null,
    parent_id: null
  }
})

const deleteModal = ref({
  show: false,
  loading: false,
  menu: null
})

const moduleOptions = computed(() => [
  { value: null, text: '❌ Aucun module' },
  ...props.modules.map(m => ({ value: m.id, text: `📦 ${m.name}` }))
])

const totalMenus = computed(() => menus.value.length)
const totalVisible = computed(() => menus.value.filter(m => m.visible).length)
const totalWithModule = computed(() => menus.value.filter(m => m.module_id).length)
const totalWithChildren = computed(() => menus.value.filter(m => m.children?.length > 0).length)

const filteredMenus = computed(() => {
  return menus.value.filter(menu => {
    const matchesSearch = !searchQuery.value || 
      menu.label.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      menu.key.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchesType = !filterType.value || menu.type === filterType.value
    const matchesVisibility = filterVisibility.value === null || menu.visible === filterVisibility.value
    return matchesSearch && matchesType && matchesVisibility
  })
})

// Auto-générer la clé
function regenerateKey() {
  if (modalState.value.form.label) {
    modalState.value.form.key = modalState.value.form.label
      .toLowerCase()
      .trim()
      .replace(/\s+/g, '-')
      .replace(/[^a-z0-9-]/g, '')
  }
}

// Auto-générer depuis le libellé
function autoGenerateFromLabel() {
  if (!modalState.value.form.label) return
  
  // Générer la clé
  regenerateKey()
  
  // Détecter l'icône
  const labelLower = modalState.value.form.label.toLowerCase()
  for (const [key, icon] of Object.entries(ICON_DETECTION)) {
    if (labelLower.includes(key)) {
      modalState.value.form.icon = icon
      break
    }
  }
}

// Appliquer le template du module
function applyModuleTemplate() {
  if (!modalState.value.form.module_id) return
  
  const module = props.modules.find(m => m.id === modalState.value.form.module_id)
  if (!module) return
  
  const template = MODULE_TEMPLATES[module.code]
  if (template) {
    modalState.value.form.icon = template.icon
    modalState.value.form.badge_json = template.badge_json
    modalState.value.form.url = template.url
    modalState.value.form.route_name = template.route_name
  }
}

function showCreateModal() {
  modalState.value.isEdit = false
  resetModalForm()
  modalState.value.show = true
}

function editMenu(menu) {
  modalState.value.isEdit = true
  modalState.value.form = { ...menu }
  modalState.value.show = true
}

function deleteMenu(menu) {
  deleteModal.value.menu = menu
  deleteModal.value.show = true
}

function confirmDelete() {
  if (!deleteModal.value.menu) return
  deleteModal.value.loading = true
  router.delete(`/admin/menus/${deleteModal.value.menu.id}`, {
    onSuccess: () => {
      deleteModal.value.show = false
      router.reload({ only: ['menus'] })
    },
    onFinish: () => {
      deleteModal.value.loading = false
    }
  })
}

function addChildMenu(parentMenu) {
  modalState.value.isEdit = false
  resetModalForm()
  modalState.value.form.parent_id = parentMenu.id
  modalState.value.show = true
}

function updateMenu(updatedMenu) {
  const index = menus.value.findIndex(m => m.id === updatedMenu.id)
  if (index !== -1) {
    menus.value[index] = { ...updatedMenu }
  }
}

function toggleVisibility(menu) {
  menu.visible = !menu.visible
}

function saveMenu() {
  if (!modalState.value.form.label || !modalState.value.form.key) {
    alert('Veuillez remplir les champs obligatoires')
    return
  }

  modalState.value.loading = true
  const method = modalState.value.isEdit ? 'put' : 'post'
  const url = modalState.value.isEdit ? `/admin/menus/${modalState.value.form.id}` : '/admin/menus'

  router[method](url, modalState.value.form, {
    onSuccess: () => {
      modalState.value.show = false
      router.reload({ only: ['menus'] })
    },
    onError: (errors) => {
      modalState.value.errors = errors
    },
    onFinish: () => {
      modalState.value.loading = false
    }
  })
}

function resetModal() {
  resetModalForm()
  modalState.value.isEdit = false
  modalState.value.errors = {}
}

function resetModalForm() {
  modalState.value.form = {
    key: '',
    label: '',
    type: 'item',
    icon: '',
    url: '',
    route_name: '',
    sort: 10,
    module_id: null,
    visible: true,
    badge_json: '',
    tooltip_json: '',
    target: null,
    parent_id: null
  }
}
</script>

<style scoped>
.page-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 1.5rem;
  border-radius: 12px;
  color: white;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.icon-wrapper {
  width: 50px;
  height: 50px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(10px);
}

.header-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  transition: all 0.3s;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
}

.stat-primary .stat-icon {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.stat-success .stat-icon {
  background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
  color: white;
}

.stat-info .stat-icon {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
  color: white;
}

.stat-warning .stat-icon {
  background: linear-gradient(135deg, #f2994a 0%, #f2c94c 100%);
  color: white;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  margin: 0;
  line-height: 1;
}

.stat-label {
  font-size: 0.875rem;
  color: #6c757d;
  margin: 0;
  margin-top: 0.25rem;
}

.filters-bar {
  background: white;
  padding: 1rem 1.25rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
}

.search-box i {
  position: absolute;
  left: 12px;
  color: #6c757d;
}

.search-input {
  padding-left: 36px !important;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
}

.search-input:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.modern-select {
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  padding: 0.625rem 0.875rem;
  transition: all 0.2s;
}

.modern-select:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.menus-container {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  min-height: 400px;
}

.menus-tree {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.empty-state {
  text-align: center;
  padding: 3rem 1.5rem;
}

.form-section-header {
  display: flex;
  align-items: center;
  padding-bottom: 0.75rem;
  margin-bottom: 1rem;
  border-bottom: 2px solid #e9ecef;
  color: #495057;
  font-weight: 600;
  font-size: 0.95rem;
}

.form-label {
  font-weight: 600;
  font-size: 0.875rem;
  color: #495057;
  margin-bottom: 0.5rem;
  display: block;
}

.text-danger {
  color: #dc3545;
}

.modern-input {
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  padding: 0.625rem 0.875rem;
  transition: all 0.2s;
  font-size: 0.875rem;
}

.modern-input:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  outline: none;
}

.form-hint {
  display: block;
  margin-top: 0.375rem;
  color: #6c757d;
  font-size: 0.8rem;
}

.icon-display {
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  padding: 0.875rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: #f8f9fa;
  font-weight: 500;
  color: #495057;
}

.preview-icon {
  font-size: 1.5rem;
  color: #667eea;
}

.icon-picker-compact {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(40px, 1fr));
  gap: 0.5rem;
  padding: 0.75rem;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
}

.icon-option-small {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  border: 2px solid transparent;
  font-size: 1.25rem;
}

.icon-option-small:hover {
  background: white;
  border-color: #667eea;
  color: #667eea;
}

.input-group {
  display: flex;
  gap: 0.5rem;
}

.input-group button {
  border-radius: 8px;
  padding: 0.625rem 1rem;
}

.form-check-wrapper {
  margin-top: 1.5rem;
}

.form-check-modern {
  display: flex;
  align-items: center;
}

.checkbox-label {
  display: flex;
  align-items: center;
  margin: 0;
  font-weight: 500;
  color: #495057;
  cursor: pointer;
}

.modern-btn {
  border-radius: 8px;
  padding: 0.625rem 1.25rem;
  font-weight: 600;
  transition: all 0.2s;
}

.modern-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

:deep(.modal-content) {
  border-radius: 12px;
  border: none;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

:deep(.modal-header) {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-bottom: 2px solid #dee2e6;
  border-radius: 12px 12px 0 0;
  padding: 1.25rem 1.5rem;
}

:deep(.modal-title) {
  font-weight: 700;
  font-size: 1.125rem;
  color: #212529;
}

:deep(.modal-body) {
  padding: 1.5rem;
  max-height: 70vh;
  overflow-y: auto;
}

:deep(.modal-footer) {
  border-top: 1px solid #dee2e6;
  padding: 1rem 1.5rem;
  background: #f8f9fa;
  border-radius: 0 0 12px 12px;
}

@media (max-width: 767px) {
  .page-header {
    padding: 1rem;
  }

  .icon-picker-compact {
    grid-template-columns: repeat(auto-fill, minmax(35px, 1fr));
  }

  :deep(.modal-body) {
    max-height: 60vh;
  }
}
</style>