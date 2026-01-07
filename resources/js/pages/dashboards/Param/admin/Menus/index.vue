<template>
  <VerticalLayout>
    <Head title="Gestion des Menus" />

    <div class="menus-page">
      <!-- Header Pro avec Sidebar Toggle -->
      <div class="header-section">
        <div class="header-content">
          <div class="header-left">
            <button @click="sidebarOpen = !sidebarOpen" class="btn-sidebar-toggle">
              <i class="ti ti-menu-2"></i>
            </button>
            <div class="header-text">
              <h1 class="page-title">📋 Gestion des Menus</h1>
              <p class="page-subtitle">Organisez et personnalisez votre arborescence</p>
            </div>
          </div>
          <div class="header-right">
            <button @click="refreshData" class="btn-refresh" :class="{ loading: isRefreshing }">
              <i class="ti ti-refresh"></i>
              {{ isRefreshing ? 'Actualisation...' : 'Actualiser' }}
            </button>
            <button @click="showCreateModal" class="btn-primary-large">
              <i class="ti ti-plus"></i>
              Ajouter
            </button>
          </div>
        </div>

        <!-- Stats Elegantes -->
        <div class="stats-grid">
          <div class="stat-box stat-blue" v-if="stats">
            <div class="stat-icon"><i class="ti ti-list"></i></div>
            <div class="stat-details">
              <div class="stat-number">{{ stats.total }}</div>
              <div class="stat-label">Menus Total</div>
            </div>
          </div>

          <div class="stat-box stat-green" v-if="stats">
            <div class="stat-icon"><i class="ti ti-hierarchy"></i></div>
            <div class="stat-details">
              <div class="stat-number">{{ stats.withChildren }}</div>
              <div class="stat-label">Parents</div>
            </div>
          </div>

          <div class="stat-box stat-purple" v-if="stats">
            <div class="stat-icon"><i class="ti ti-eye"></i></div>
            <div class="stat-details">
              <div class="stat-number">{{ stats.visible }}</div>
              <div class="stat-label">Visibles</div>
            </div>
          </div>

          <div class="stat-box stat-orange" v-if="stats">
            <div class="stat-icon"><i class="ti ti-database-cog"></i></div>
            <div class="stat-details">
              <div class="stat-number">{{ stats.maxDepth }}</div>
              <div class="stat-label">Profondeur Max</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="main-content">
        <!-- Sidebar Filters -->
        <aside class="sidebar" :class="{ open: sidebarOpen }">
          <div class="sidebar-header">
            <h3>🔍 Filtres</h3>
            <button @click="sidebarOpen = false" class="btn-close-sidebar">
              <i class="ti ti-x"></i>
            </button>
          </div>

          <div class="sidebar-content">
            <!-- Search -->
            <div class="filter-section">
              <label class="filter-title">Rechercher</label>
              <div class="search-wrapper">
                <i class="ti ti-search"></i>
                <input 
                  v-model="searchQuery" 
                  type="text" 
                  placeholder="Nom ou clé..." 
                  class="search-input"
                />
              </div>
            </div>

            <!-- Type Filter -->
            <div class="filter-section">
              <label class="filter-title">Type</label>
              <div class="filter-options">
                <button 
                  v-for="option in typeOptions"
                  :key="option.value"
                  @click="filterType = option.value"
                  :class="{ active: filterType === option.value }"
                  class="filter-option"
                >
                  {{ option.label }}
                </button>
              </div>
            </div>

            <!-- Visibility Filter -->
            <div class="filter-section">
              <label class="filter-title">Visibilité</label>
              <div class="filter-options">
                <button 
                  v-for="option in visibilityOptions"
                  :key="option.value"
                  @click="filterVisibility = option.value"
                  :class="{ active: filterVisibility === option.value }"
                  class="filter-option"
                >
                  {{ option.label }}
                </button>
              </div>
            </div>

            <!-- Results Count -->
            <div class="filter-section">
              <div class="results-info">
                <div class="results-count">{{ filteredMenus.length }}/{{ totalMenus }}</div>
                <button @click="resetFilters" class="btn-reset-filters">
                  🔄 Réinitialiser
                </button>
              </div>
            </div>
          </div>
        </aside>

        <!-- Content Area -->
        <main class="content-area">
          <!-- Toolbar -->
          <div class="toolbar">
            <div class="toolbar-left">
              <span class="toolbar-info">{{ filteredMenus.length }} menu(s) affichés</span>
            </div>
            <div class="toolbar-right">
              <select v-model="sortBy" class="select-sort">
                <option value="sort">📊 Ordre</option>
                <option value="label">📝 Nom (A-Z)</option>
                <option value="created">📅 Récent</option>
              </select>
            </div>
          </div>

          <!-- Menus Tree -->
          <div class="menus-section">
            <div v-if="filteredMenus.length === 0" class="empty-state">
              <i class="ti ti-list-off"></i>
              <h3>Aucun menu trouvé</h3>
              <p>Essayez d'ajuster vos filtres</p>
            </div>

            <div v-else class="tree-container">
              <div class="tree-header">
                <span class="tree-label">Menu</span>
                <span class="tree-module">Module</span>
                <span class="tree-status">Statut</span>
                <span class="tree-actions">Actions</span>
              </div>

              <MenuTreeItem
                v-for="menu in filteredMenus"
                :key="menu.id"
                :menu="menu"
                :modules="modules"
                :all-icons="icons"
                :level="0"
                @edit="editMenu"
                @delete="deleteMenu"
                @add-child="addChildMenu"
                @toggle-visible="toggleVisible"
              />
            </div>
          </div>
        </main>
      </div>
    </div>

    <!-- Modal Create/Edit -->
    <div v-if="modal.show" class="modal-overlay" @click.self="closeModal">
      <div class="modal-box">
        <div class="modal-header">
          <h2>{{ modal.edit ? '✏️ Modifier Menu' : '➕ Ajouter Menu' }}</h2>
          <button @click="closeModal" class="btn-close"><i class="ti ti-x"></i></button>
        </div>

        <form @submit.prevent="saveMenu" class="modal-form">
          <!-- Hiérarchie -->
          <div class="form-section">
            <h3 class="section-title"><i class="ti ti-hierarchy"></i> Hiérarchie</h3>
            <label>Parent (optionnel)</label>
            <select v-model.number="modal.form.parent_id" class="input-modern">
              <option :value="null">📌 Racine (pas de parent)</option>
              <option v-for="p in parentOptions" :key="p.id" :value="p.id">
                {{ p.indent }}{{ p.label }}
              </option>
            </select>
          </div>

          <!-- Module -->
          <div class="form-section">
            <h3 class="section-title"><i class="ti ti-database"></i> Module</h3>
            <select v-model.number="modal.form.module_id" @change="applyModuleTemplate" class="input-modern">
              <option :value="null">— Aucun</option>
              <option v-for="m in modules" :key="m.id" :value="m.id">
                {{ m.name }}
              </option>
            </select>
          </div>

          <!-- Identité -->
          <div class="form-section">
            <h3 class="section-title"><i class="ti ti-tag"></i> Identité</h3>
            <div class="form-row">
              <div class="form-col">
                <label>Libellé *</label>
                <input v-model="modal.form.label" @change="autoKey" required class="input-modern" />
              </div>
              <div class="form-col">
                <label>Clé *</label>
                <div class="input-with-button">
                  <input v-model="modal.form.key" :disabled="modal.edit" required class="input-modern" />
                  <button v-if="!modal.edit" type="button" @click="genKey" class="btn-icon">🔄</button>
                </div>
              </div>
            </div>
          </div>

          <!-- Apparence -->
          <div class="form-section">
            <h3 class="section-title"><i class="ti ti-palette"></i> Apparence</h3>
            <div class="form-row">
              <div class="form-col">
                <label>Type *</label>
                <select v-model="modal.form.type" required class="input-modern">
                  <option value="item">📄 Élément</option>
                  <option value="title">📌 Titre</option>
                  <option value="divider">─ Séparateur</option>
                </select>
              </div>
              <div class="form-col">
                <label>Icône</label>
                <div class="icon-preview">
                  <i v-if="modal.form.icon" :class="modal.form.icon"></i>
                  <span>{{ modal.form.icon || 'Aucune' }}</span>
                </div>
              </div>
            </div>
            <div class="icon-picker">
              <button 
                v-for="icon in icons" 
                :key="icon"
                type="button"
                @click="modal.form.icon = icon"
                :class="{ active: modal.form.icon === icon }"
                class="icon-btn"
              >
                <i :class="icon"></i>
              </button>
            </div>
          </div>

          <!-- Navigation -->
          <div class="form-section">
            <h3 class="section-title"><i class="ti ti-link"></i> Navigation</h3>
            <div class="form-row">
              <div class="form-col">
                <label>URL</label>
                <input v-model="modal.form.url" class="input-modern" />
              </div>
              <div class="form-col">
                <label>Route</label>
                <input v-model="modal.form.route_name" class="input-modern" />
              </div>
            </div>
          </div>

          <!-- Attributs -->
          <div class="form-section">
            <h3 class="section-title"><i class="ti ti-settings"></i> Attributs</h3>
            <div class="form-row">
              <div class="form-col">
                <label>Ordre</label>
                <input v-model.number="modal.form.sort" type="number" min="0" class="input-modern" />
              </div>
              <div class="form-col">
                <label class="checkbox-label">
                  <input v-model="modal.form.visible" type="checkbox" />
                  <span>👁️ Visible</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="modal-actions">
            <button type="button" @click="closeModal" class="btn-secondary">Annuler</button>
            <button type="submit" class="btn-primary" :disabled="modal.loading">
              {{ modal.loading ? '⏳ Enregistrement...' : '✓ Enregistrer' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Delete -->
    <div v-if="delModal.show" class="modal-overlay" @click.self="delModal.show = false">
      <div class="modal-box modal-small">
        <div class="modal-header danger">
          <h2>⚠️ Supprimer Menu</h2>
          <button @click="delModal.show = false" class="btn-close"><i class="ti ti-x"></i></button>
        </div>
        <div class="modal-body">
          <p>Supprimer <strong>{{ delModal.menu?.label }}</strong> ?</p>
          <p v-if="countAllChildren(delModal.menu?.id) > 0" class="danger-text">
            ⚠️ {{ countAllChildren(delModal.menu?.id) }} enfant(s) aussi supprimés
          </p>
        </div>
        <div class="modal-actions">
          <button @click="delModal.show = false" class="btn-secondary">Annuler</button>
          <button @click="confirmDelete" class="btn-danger" :disabled="delModal.loading">
            {{ delModal.loading ? '⏳' : '🗑️ Supprimer' }}
          </button>
        </div>
      </div>
    </div>
  </VerticalLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
import MenuTreeItem from '@/Components/Admin/MenuTreeItem.vue'

const props = defineProps({
  menus: Array,
  modules: Array,
  stats: Object
})

const icons = [
  'ti ti-home', 'ti ti-dashboard', 'ti ti-settings', 'ti ti-users', 'ti ti-lock',
  'ti ti-database', 'ti ti-chart', 'ti ti-report', 'ti ti-check', 'ti ti-adjustments',
  'ti ti-flow-branch', 'ti ti-alert-triangle', 'ti ti-checklist', 'ti ti-apps',
  'ti ti-folder', 'ti ti-folders', 'ti ti-eye', 'ti ti-link', 'ti ti-menu-2',
  'ti ti-building-community', 'ti ti-hierarchy', 'ti ti-topology-star', 'ti ti-bulb'
]

const MODULE_TEMPLATES = {
  'param.projects': { icon: 'ti ti-adjustments', url: '/m/param.projects', route: 'param.projects.home' },
  'process.core': { icon: 'ti ti-flow-branch', url: '/m/process.core', route: 'process.core.home' },
  'risk.core': { icon: 'ti ti-alert-triangle', url: '/m/risk.core', route: 'risk.core.home' },
  'audit.core': { icon: 'ti ti-checklist', url: '/m/audit.core', route: 'audit.core.home' },
  'admin.core': { icon: 'ti ti-lock', url: '/m/admin.core', route: 'admin.core.home' }
}

const typeOptions = [
  { value: '', label: '📝 Tous' },
  { value: 'item', label: '📄 Élément' },
  { value: 'title', label: '📌 Titre' },
  { value: 'divider', label: '─ Séparateur' }
]

const visibilityOptions = [
  { value: '', label: '👁️ Tous' },
  { value: 'true', label: '👁️ Visibles' },
  { value: 'false', label: '🚫 Cachés' }
]

const allMenus = ref(props.menus || [])
const modules = ref(props.modules || [])
const stats = ref(props.stats || null)

const searchQuery = ref('')
const filterType = ref('')
const filterVisibility = ref('')
const sortBy = ref('sort')
const sidebarOpen = ref(window.innerWidth > 768)
const isRefreshing = ref(false)

const modal = ref({
  show: false,
  edit: false,
  loading: false,
  form: {
    id: null,
    label: '',
    key: '',
    type: 'item',
    icon: '',
    url: '',
    route_name: '',
    sort: 10,
    visible: true,
    module_id: null,
    parent_id: null,
  }
})

const delModal = ref({
  show: false,
  loading: false,
  menu: null
})

// Computed
const totalMenus = computed(() => allMenus.value.length)

const filteredMenus = computed(() => {
  return allMenus.value.filter(menu => {
    const matchSearch = !searchQuery.value ||
      menu.label.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      menu.key.toLowerCase().includes(searchQuery.value.toLowerCase())

    const matchType = !filterType.value || menu.type === filterType.value
    const matchVis = !filterVisibility.value || (menu.visible ? 'true' : 'false') === filterVisibility.value

    return matchSearch && matchType && matchVis
  }).sort((a, b) => {
    switch(sortBy.value) {
      case 'label': return a.label.localeCompare(b.label)
      case 'created': return new Date(b.created_at) - new Date(a.created_at)
      default: return a.sort - b.sort
    }
  })
})

const parentOptions = computed(() => {
  return buildParentList(allMenus.value, '', modal.value.form.id)
})

// Methods
function buildParentList(menus, indent = '', excludeId = null) {
  const list = []
  for (const menu of menus) {
    if (menu.id === excludeId) continue
    if (menu.type !== 'divider') {
      list.push({
        id: menu.id,
        label: menu.label,
        indent: indent
      })
      if (menu.children?.length) {
        list.push(...buildParentList(menu.children, indent + '─ ', excludeId))
      }
    }
  }
  return list
}

function countAllChildren(menuId) {
  const menu = findMenuById(allMenus.value, menuId)
  if (!menu || !menu.children) return 0
  
  let count = menu.children.length
  for (const child of menu.children) {
    count += countAllChildren(child.id)
  }
  return count
}

function findMenuById(menus, id) {
  for (const menu of menus) {
    if (menu.id === id) return menu
    if (menu.children?.length) {
      const found = findMenuById(menu.children, id)
      if (found) return found
    }
  }
  return null
}

function refreshData() {
  isRefreshing.value = true
  router.reload({ only: ['menus', 'stats'] })
  setTimeout(() => {
    isRefreshing.value = false
  }, 500)
}

function resetFilters() {
  searchQuery.value = ''
  filterType.value = ''
  filterVisibility.value = ''
}

function showCreateModal() {
  modal.value.edit = false
  resetForm()
  modal.value.show = true
}

function editMenu(menu) {
  modal.value.edit = true
  modal.value.form = { ...menu }
  modal.value.show = true
}

function addChildMenu(parentId) {
  modal.value.edit = false
  resetForm()
  modal.value.form.parent_id = parentId
  modal.value.show = true
}

function resetForm() {
  modal.value.form = {
    id: null,
    label: '',
    key: '',
    type: 'item',
    icon: '',
    url: '',
    route_name: '',
    sort: 10,
    visible: true,
    module_id: null,
    parent_id: null,
  }
}

function autoKey() {
  genKey()
}

function genKey() {
  const label = modal.value.form.label
  if (!label) return
  modal.value.form.key = label
    .toLowerCase()
    .trim()
    .replace(/\s+/g, '-')
    .replace(/[^a-z0-9-]/g, '')
}

function applyModuleTemplate() {
  if (!modal.value.form.module_id) return
  const m = modules.value.find(x => x.id === modal.value.form.module_id)
  if (!m) return
  const t = MODULE_TEMPLATES[m.code]
  if (t) {
    modal.value.form.icon = t.icon
    modal.value.form.url = t.url
    modal.value.form.route_name = t.route
  }
}

function saveMenu() {
  if (!modal.value.form.label || !modal.value.form.key) {
    alert('Libellé et clé obligatoires')
    return
  }

  modal.value.loading = true
  const method = modal.value.edit ? 'put' : 'post'
  const url = modal.value.edit
    ? `/admin/menus/${modal.value.form.id}`
    : '/admin/menus'

  router[method](url, modal.value.form, {
    onSuccess: () => {
      closeModal()
      refreshData()
    },
    onFinish: () => {
      modal.value.loading = false
    }
  })
}

function toggleVisible(menu) {
  menu.visible = !menu.visible
  router.put(`/admin/menus/${menu.id}`, { visible: menu.visible }, {
    onSuccess: () => {
      refreshData()
    }
  })
}

function deleteMenu(menu) {
  delModal.value.menu = menu
  delModal.value.show = true
}

function confirmDelete() {
  delModal.value.loading = true
  router.delete(`/admin/menus/${delModal.value.menu.id}`, {
    onSuccess: () => {
      delModal.value.show = false
      refreshData()
    },
    onFinish: () => {
      delModal.value.loading = false
    }
  })
}

function closeModal() {
  modal.value.show = false
  resetForm()
}
</script>

<style scoped>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.menus-page {
  min-height: 100vh;
  background: #f5f7fa;
}

/* Header */
.header-section {
  background: white;
  padding: 2rem;
  border-bottom: 1px solid #e9ecef;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  gap: 1rem;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex: 1;
}

.btn-sidebar-toggle {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #667eea;
  padding: 0.5rem;
  display: none;
}

.header-text h1 {
  font-size: 1.75rem;
  font-weight: 700;
  color: #212529;
  margin: 0;
}

.header-text p {
  font-size: 0.9rem;
  color: #6c757d;
  margin: 0.25rem 0 0 0;
}

.header-right {
  display: flex;
  gap: 1rem;
}

.btn-refresh {
  background: #f0f0f0;
  border: 2px solid #e0e0e0;
  color: #667eea;
  padding: 0.7rem 1.25rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s;
}

.btn-refresh:hover {
  background: #e9ecef;
  border-color: #667eea;
}

.btn-refresh.loading {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-refresh.loading i {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.btn-primary-large {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.7rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-primary-large:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

/* Stats */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1.25rem;
}

.stat-box {
  background: white;
  border-radius: 10px;
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  border-left: 4px solid;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: all 0.3s;
}

.stat-box:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.stat-icon {
  width: 45px;
  height: 45px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.stat-blue { border-left-color: #667eea; }
.stat-blue .stat-icon { background: rgba(102, 126, 234, 0.1); color: #667eea; }

.stat-green { border-left-color: #56ab2f; }
.stat-green .stat-icon { background: rgba(86, 171, 47, 0.1); color: #56ab2f; }

.stat-purple { border-left-color: #764ba2; }
.stat-purple .stat-icon { background: rgba(118, 75, 162, 0.1); color: #764ba2; }

.stat-orange { border-left-color: #f2994a; }
.stat-orange .stat-icon { background: rgba(242, 153, 74, 0.1); color: #f2994a; }

.stat-number {
  font-size: 1.5rem;
  font-weight: 700;
  color: #212529;
}

.stat-label {
  font-size: 0.8rem;
  color: #6c757d;
  margin-top: 0.2rem;
}

/* Main Content */
.main-content {
  display: flex;
  height: calc(100vh - 320px);
}

/* Sidebar */
.sidebar {
  width: 280px;
  background: white;
  border-right: 1px solid #e9ecef;
  overflow-y: auto;
  transition: all 0.3s;
  position: relative;
}

.sidebar-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e9ecef;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.sidebar-header h3 {
  font-size: 1rem;
  font-weight: 700;
  margin: 0;
}

.btn-close-sidebar {
  background: none;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
  color: #6c757d;
  display: none;
}

.sidebar-content {
  padding: 1.5rem;
}

.filter-section {
  margin-bottom: 1.75rem;
}

.filter-title {
  display: block;
  font-weight: 600;
  color: #212529;
  margin-bottom: 0.75rem;
  font-size: 0.9rem;
}

.search-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.search-wrapper i {
  position: absolute;
  left: 10px;
  color: #6c757d;
}

.search-input {
  width: 100%;
  padding: 0.6rem 1rem 0.6rem 32px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 0.9rem;
  transition: all 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.filter-options {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-option {
  padding: 0.6rem 0.75rem;
  border: 2px solid #e0e0e0;
  background: white;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.85rem;
  transition: all 0.2s;
  text-align: left;
}

.filter-option:hover {
  border-color: #667eea;
  background: #f8f9fa;
}

.filter-option.active {
  background: #667eea;
  color: white;
  border-color: #667eea;
}

.results-info {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.results-count {
  flex: 1;
  text-align: center;
  padding: 0.6rem;
  background: #f0f0f0;
  border-radius: 6px;
  font-weight: 700;
  color: #667eea;
}

.btn-reset-filters {
  padding: 0.6rem 0.75rem;
  background: #f0f0f0;
  border: 2px solid #e0e0e0;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.2s;
}

.btn-reset-filters:hover {
  background: #e0e0e0;
}

/* Content Area */
.content-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* Toolbar */
.toolbar {
  background: white;
  padding: 1rem 2rem;
  border-bottom: 1px solid #e9ecef;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.toolbar-info {
  font-weight: 600;
  color: #667eea;
  font-size: 0.9rem;
}

.select-sort {
  padding: 0.6rem 1rem;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  background: white;
}

.select-sort:focus {
  outline: none;
  border-color: #667eea;
}

/* Menus Section */
.menus-section {
  flex: 1;
  padding: 2rem;
  overflow-y: auto;
  background: #f5f7fa;
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: 12px;
  color: #6c757d;
}

.empty-state i {
  font-size: 3rem;
  color: #e0e0e0;
  display: block;
  margin-bottom: 1rem;
}

.empty-state h3 {
  font-size: 1.25rem;
  color: #212529;
  margin-bottom: 0.5rem;
}

.tree-container {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.tree-header {
  display: grid;
  grid-template-columns: 1fr 200px 120px 150px;
  gap: 1rem;
  padding: 1rem 1.5rem;
  background: #f8f9fa;
  border-bottom: 2px solid #e9ecef;
  font-weight: 600;
  color: #6c757d;
  font-size: 0.85rem;
  position: sticky;
  top: 0;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
  backdrop-filter: blur(2px);
}

.modal-box {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-small {
  max-width: 400px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 2px solid #e9ecef;
  position: sticky;
  top: 0;
  background: white;
}

.modal-header h2 {
  font-size: 1.25rem;
  font-weight: 700;
  color: #212529;
  margin: 0;
}

.modal-header.danger h2 {
  color: #dc3545;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #6c757d;
  transition: color 0.2s;
}

.btn-close:hover {
  color: #212529;
}

.modal-body {
  padding: 1.5rem;
}

.danger-text {
  color: #dc3545;
  font-weight: 600;
  margin-top: 0.75rem;
}

.modal-form {
  padding: 1.5rem;
}

.form-section {
  margin-bottom: 1.75rem;
  padding-bottom: 1.75rem;
  border-bottom: 1px solid #e9ecef;
}

.form-section:last-of-type {
  border-bottom: none;
}

.section-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #495057;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

.form-col {
  display: flex;
  flex-direction: column;
}

.form-col label {
  font-weight: 600;
  color: #212529;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.input-modern {
  padding: 0.7rem;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 0.9rem;
  transition: all 0.2s;
}

.input-modern:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.input-with-button {
  display: flex;
  gap: 0.5rem;
}

.input-with-button .input-modern {
  flex: 1;
}

.btn-icon {
  padding: 0.7rem 0.9rem;
  background: #f0f0f0;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  cursor: pointer;
}

.btn-icon:hover {
  background: #e0e0e0;
}

.icon-preview {
  padding: 0.75rem;
  background: #f8f9fa;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.icon-preview i {
  font-size: 1.5rem;
  color: #667eea;
}

.icon-picker {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(40px, 1fr));
  gap: 0.5rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
  border: 2px solid #e0e0e0;
}

.icon-btn {
  width: 40px;
  height: 40px;
  border: 2px solid #e0e0e0;
  background: white;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: #667eea;
  transition: all 0.2s;
}

.icon-btn:hover {
  border-color: #667eea;
  background: #f0f0f0;
}

.icon-btn.active {
  background: #667eea;
  color: white;
  border-color: #667eea;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  font-weight: 600;
  margin-top: 0.5rem;
}

.checkbox-label input {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: #667eea;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 2px solid #e9ecef;
}

.btn-primary,
.btn-secondary,
.btn-danger {
  padding: 0.7rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.9rem;
}

.btn-primary {
  background: #667eea;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #5568d3;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: #e9ecef;
  color: #495057;
}

.btn-secondary:hover {
  background: #dee2e6;
}

.btn-danger {
  background: #dc3545;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background: #c82333;
}

/* Responsive */
@media (max-width: 1024px) {
  .sidebar {
    width: 240px;
  }

  .tree-header {
    grid-template-columns: 1fr 150px 100px 120px;
    font-size: 0.8rem;
    padding: 0.75rem 1rem;
  }
}

@media (max-width: 768px) {
  .btn-sidebar-toggle {
    display: block;
  }

  .header-content {
    flex-direction: column;
    align-items: flex-start;
  }

  .header-text h1 {
    font-size: 1.5rem;
  }

  .header-right {
    width: 100%;
    flex-direction: column;
  }

  .btn-primary-large {
    width: 100%;
    justify-content: center;
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .main-content {
    height: auto;
    flex-direction: column;
  }

  .sidebar {
    width: 100%;
    position: fixed;
    left: -100%;
    height: 100vh;
    z-index: 999;
    transition: left 0.3s;
  }

  .sidebar.open {
    left: 0;
  }

  .btn-close-sidebar {
    display: block;
  }

  .menus-section {
    padding: 1rem;
  }

  .tree-header {
    grid-template-columns: 1fr 100px 80px;
    gap: 0.5rem;
    padding: 0.75rem;
    font-size: 0.75rem;
  }

  .modal-box {
    max-width: 95vw;
  }

  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>