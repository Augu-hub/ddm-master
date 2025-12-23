<template>
  <VerticalLayout>
    <Head title="Gestion des Utilisateurs" />

    <!-- Header professionnel -->
    <div class="page-header-modern">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="header-icon-wrapper">
            <i class="ti ti-users fs-2"></i>
          </div>
          <div>
            <h2 class="header-title mb-1">Gestion des Utilisateurs</h2>
            <p class="header-subtitle mb-0">
              Gérez les utilisateurs de votre organisation
            </p>
          </div>
        </div>
        <div class="header-actions">
          <b-button 
            variant="outline-secondary" 
            @click="exportUsers"
            class="action-btn"
          >
            <i class="ti ti-download me-2"></i>
            Exporter
          </b-button>
          <b-button 
            variant="primary" 
            @click="createUser"
            class="action-btn"
          >
            <i class="ti ti-plus me-2"></i>
            Ajouter un utilisateur
          </b-button>
        </div>
      </div>
    </div>

    <!-- Statistiques -->
    <b-row class="g-3 mb-4">
      <b-col md="3" v-for="stat in stats" :key="stat.key">
        <div class="stat-card-modern" :class="`stat-${stat.variant}`">
          <div class="stat-icon-container">
            <i :class="stat.icon"></i>
          </div>
          <div class="stat-details">
            <div class="stat-value">{{ stat.value }}</div>
            <div class="stat-label">{{ stat.label }}</div>
          </div>
          <div class="stat-badge" :class="`badge-${stat.variant}`">
            <i :class="stat.trendIcon"></i>
            {{ stat.trend }}
          </div>
        </div>
      </b-col>
    </b-row>

    <!-- Filtres avancés -->
    <div class="filters-container-modern">
      <b-row class="g-3">
        <b-col md="6">
          <div class="search-wrapper-modern">
            <i class="ti ti-search"></i>
            <b-form-input
              v-model="filters.search"
              placeholder="Rechercher par nom, email, matricule..."
              class="search-input-modern"
              @input="debounceSearch"
            />
            <b-button 
              v-if="filters.search" 
              variant="link" 
              size="sm" 
              @click="filters.search = ''; applyFilters()"
              class="clear-search"
            >
              <i class="ti ti-x"></i>
            </b-button>
          </div>
        </b-col>

        <b-col md="3">
          <b-form-select
            v-model="filters.status"
            :options="statusOptions"
            class="filter-select-modern"
            @change="applyFilters"
          />
        </b-col>

        <b-col md="3">
          <b-form-select
            v-model="filters.entity_id"
            :options="entityOptions"
            class="filter-select-modern"
            @change="applyFilters"
          />
        </b-col>
      </b-row>

      <div class="active-filters mt-3" v-if="hasActiveFilters">
        <span class="filter-label">Filtres actifs :</span>
        <b-badge 
          v-if="filters.status" 
          variant="primary" 
          class="filter-badge"
          @click="filters.status = null; applyFilters()"
        >
          Statut: {{ getStatusLabel(filters.status) }}
          <i class="ti ti-x ms-1"></i>
        </b-badge>
        <b-badge 
          v-if="filters.entity_id" 
          variant="info" 
          class="filter-badge"
          @click="filters.entity_id = null; applyFilters()"
        >
          Entité: {{ getEntityLabel(filters.entity_id) }}
          <i class="ti ti-x ms-1"></i>
        </b-badge>
        <b-button 
          variant="link" 
          size="sm" 
          @click="clearAllFilters"
          class="clear-all-btn"
        >
          Tout effacer
        </b-button>
      </div>
    </div>

    <!-- Table des utilisateurs -->
    <div class="table-container-modern">
      <b-table
        :items="users.data"
        :fields="fields"
        responsive
        hover
        class="modern-table"
        :busy="loading"
        show-empty
      >
        <template #table-busy>
          <div class="text-center my-5">
            <b-spinner variant="primary"></b-spinner>
            <p class="mt-2 text-muted">Chargement des utilisateurs...</p>
          </div>
        </template>

        <template #empty>
          <div class="empty-state-modern">
            <i class="ti ti-users-off"></i>
            <h4>Aucun utilisateur trouvé</h4>
            <p>Commencez par ajouter votre premier utilisateur</p>
            <b-button variant="primary" @click="createUser">
              <i class="ti ti-plus me-2"></i>
              Ajouter un utilisateur
            </b-button>
          </div>
        </template>

        <!-- Avatar + Nom -->
        <template #cell(user)="{ item }">
          <div class="user-cell">
            <div class="user-avatar">
              <img :src="item.avatar_url" :alt="item.name" />
            </div>
            <div class="user-info">
              <div class="user-name">{{ item.name }}</div>
              <div class="user-matricule">{{ item.matricule }}</div>
            </div>
          </div>
        </template>

        <!-- Contact -->
        <template #cell(contact)="{ item }">
          <div class="contact-cell">
            <div class="contact-item">
              <i class="ti ti-mail"></i>
              <a :href="`mailto:${item.email}`">{{ item.email }}</a>
            </div>
            <div class="contact-item" v-if="item.phone">
              <i class="ti ti-phone"></i>
              <a :href="`tel:${item.phone}`">{{ item.phone }}</a>
            </div>
          </div>
        </template>

        <!-- Entité -->
        <template #cell(entity)="{ item }">
          <b-badge 
            v-if="item.entity" 
            variant="light" 
            class="entity-badge"
          >
            <i class="ti ti-building me-1"></i>
            {{ item.entity.name }}
          </b-badge>
          <span v-else class="text-muted">—</span>
        </template>

        <!-- Poste -->
        <template #cell(job_title)="{ item }">
          <span v-if="item.job_title" class="job-title-text">
            <i class="ti ti-briefcase me-1 text-muted"></i>
            {{ item.job_title }}
          </span>
          <span v-else class="text-muted">—</span>
        </template>

        <!-- Statut -->
        <template #cell(status)="{ item }">
          <b-badge 
            :variant="item.status_badge.variant"
            class="status-badge"
          >
            <i :class="getStatusIcon(item.status)" class="me-1"></i>
            {{ item.status_badge.text }}
          </b-badge>
        </template>

        <!-- Actions -->
        <template #cell(actions)="{ item }">
          <div class="action-buttons">
            <b-button
              variant="light"
              size="sm"
              @click="viewUser(item)"
              v-b-tooltip.hover="'Voir les détails'"
            >
              <i class="ti ti-eye"></i>
            </b-button>
            <b-button
              variant="light"
              size="sm"
              @click="editUser(item)"
              v-b-tooltip.hover="'Modifier'"
            >
              <i class="ti ti-edit"></i>
            </b-button>
            <b-dropdown 
              variant="light" 
              size="sm" 
              no-caret
              class="action-dropdown"
            >
              <template #button-content>
                <i class="ti ti-dots-vertical"></i>
              </template>
              <b-dropdown-item @click="resendEmail(item)">
                <i class="ti ti-mail text-info"></i>
                Renvoyer l'email
              </b-dropdown-item>
              <b-dropdown-divider></b-dropdown-divider>
              <b-dropdown-item @click="changeStatus(item, 'active')" v-if="item.status !== 'active'">
                <i class="ti ti-check text-success"></i>
                Activer
              </b-dropdown-item>
              <b-dropdown-item @click="changeStatus(item, 'inactive')" v-if="item.status !== 'inactive'">
                <i class="ti ti-ban text-warning"></i>
                Désactiver
              </b-dropdown-item>
              <b-dropdown-item @click="changeStatus(item, 'suspended')" v-if="item.status !== 'suspended'">
                <i class="ti ti-lock text-danger"></i>
                Suspendre
              </b-dropdown-item>
              <b-dropdown-divider></b-dropdown-divider>
              <b-dropdown-item @click="deleteUser(item)" variant="danger">
                <i class="ti ti-trash"></i>
                Supprimer
              </b-dropdown-item>
            </b-dropdown>
          </div>
        </template>
      </b-table>

      <!-- Pagination -->
      <div class="pagination-container-modern" v-if="users.data.length > 0">
        <div class="pagination-info">
          Affichage de {{ users.from }} à {{ users.to }} sur {{ users.total }} utilisateurs
        </div>
        <b-pagination
          v-model="currentPage"
          :total-rows="users.total"
          :per-page="users.per_page"
          @change="changePage"
          class="modern-pagination"
          align="center"
        />
      </div>
    </div>
  </VerticalLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps({
  users: Object,
  entities: Array,
  filters: Object,
  statistics: Object,
})

const loading = ref(false)
const currentPage = ref(props.users.current_page)
const filters = ref({
  search: props.filters.search || '',
  status: props.filters.status || null,
  entity_id: props.filters.entity_id || null,
})

const fields = [
  { key: 'user', label: 'Utilisateur', sortable: true },
  { key: 'contact', label: 'Contact', sortable: false },
  { key: 'entity', label: 'Entité', sortable: true },
  { key: 'job_title', label: 'Poste', sortable: true },
  { key: 'status', label: 'Statut', sortable: true },
  { key: 'actions', label: 'Actions', class: 'text-end' },
]

const stats = computed(() => [
  {
    key: 'total',
    label: 'Total utilisateurs',
    value: props.statistics.total,
    icon: 'ti ti-users',
    variant: 'primary',
    trend: '+' + props.statistics.recent,
    trendIcon: 'ti ti-trending-up',
  },
  {
    key: 'active',
    label: 'Actifs',
    value: props.statistics.active,
    icon: 'ti ti-user-check',
    variant: 'success',
    trend: Math.round((props.statistics.active / props.statistics.total) * 100) + '%',
    trendIcon: 'ti ti-check',
  },
  {
    key: 'with_entity',
    label: 'Avec entité',
    value: props.statistics.with_entity,
    icon: 'ti ti-building',
    variant: 'info',
    trend: Math.round((props.statistics.with_entity / props.statistics.total) * 100) + '%',
    trendIcon: 'ti ti-building-community',
  },
  {
    key: 'recent',
    label: 'Récents (30j)',
    value: props.statistics.recent,
    icon: 'ti ti-clock',
    variant: 'warning',
    trend: 'Ce mois',
    trendIcon: 'ti ti-calendar',
  },
])

const statusOptions = [
  { value: null, text: 'Tous les statuts' },
  { value: 'active', text: 'Actifs' },
  { value: 'inactive', text: 'Inactifs' },
  { value: 'suspended', text: 'Suspendus' },
]

const entityOptions = computed(() => [
  { value: null, text: 'Toutes les entités' },
  ...props.entities.map(e => ({ value: e.id, text: e.name })),
])

const hasActiveFilters = computed(() => {
  return filters.value.status || filters.value.entity_id
})

let searchTimeout = null
function debounceSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 500)
}

function applyFilters() {
  loading.value = true
  router.get(route('param.projects.users.index'), filters.value, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false
    },
  })
}

function clearAllFilters() {
  filters.value = {
    search: '',
    status: null,
    entity_id: null,
  }
  applyFilters()
}

function changePage(page) {
  currentPage.value = page
  router.get(route('param.projects.users.index'), {
    ...filters.value,
    page,
  })
}

function createUser() {
  router.visit(route('param.projects.users.create'))
}

function viewUser(user) {
  router.visit(route('param.projects.users.show', user.id))
}

function editUser(user) {
  router.visit(route('param.projects.users.edit', user.id))
}

function deleteUser(user) {
  if (confirm(`Êtes-vous sûr de vouloir supprimer ${user.name} ?`)) {
    router.delete(route('param.projects.users.destroy', user.id))
  }
}

function changeStatus(user, status) {
  router.post(route('param.projects.users.change-status', user.id), {
    status,
  })
}

function resendEmail(user) {
  if (confirm(`Renvoyer l'email de bienvenue à ${user.name} ?\n\nUn nouveau mot de passe sera généré.`)) {
    router.post(route('param.projects.users.resend-email', user.id), {}, {
      preserveScroll: true,
    })
  }
}

function exportUsers() {
  window.location.href = route('param.projects.users.export', filters.value)
}

function getStatusIcon(status) {
  const icons = {
    active: 'ti ti-check',
    inactive: 'ti ti-ban',
    suspended: 'ti ti-lock',
  }
  return icons[status] || 'ti ti-help'
}

function getStatusLabel(status) {
  const option = statusOptions.find(o => o.value === status)
  return option ? option.text : status
}

function getEntityLabel(entityId) {
  const entity = props.entities.find(e => e.id === entityId)
  return entity ? entity.name : entityId
}
</script>

<style scoped>
/* Header moderne */
.page-header-modern {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2rem;
  border-radius: 16px;
  color: white;
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
  margin-bottom: 2rem;
}

.header-icon-wrapper {
  width: 64px;
  height: 64px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(10px);
}

.header-title {
  font-size: 1.75rem;
  font-weight: 700;
  margin: 0;
}

.header-subtitle {
  font-size: 0.95rem;
  opacity: 0.9;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
}

.action-btn {
  border-radius: 10px;
  padding: 0.625rem 1.25rem;
  font-weight: 600;
  transition: all 0.3s;
}

.action-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

/* Statistiques modernes */
.stat-card-modern {
  background: white;
  border-radius: 14px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
  transition: all 0.3s;
  position: relative;
  overflow: hidden;
}

.stat-card-modern::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 4px;
  height: 100%;
  background: var(--stat-color);
}

.stat-card-modern:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
}

.stat-primary { --stat-color: #667eea; }
.stat-success { --stat-color: #56ab2f; }
.stat-info { --stat-color: #4facfe; }
.stat-warning { --stat-color: #f2994a; }

.stat-icon-container {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.stat-primary .stat-icon-container {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.stat-success .stat-icon-container {
  background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
  color: white;
}

.stat-info .stat-icon-container {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
  color: white;
}

.stat-warning .stat-icon-container {
  background: linear-gradient(135deg, #f2994a 0%, #f2c94c 100%);
  color: white;
}

.stat-details {
  flex: 1;
}

.stat-value {
  font-size: 1.75rem;
  font-weight: 700;
  line-height: 1;
  margin-bottom: 0.25rem;
}

.stat-label {
  font-size: 0.875rem;
  color: #6c757d;
}

.stat-badge {
  padding: 0.375rem 0.75rem;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 600;
}

.badge-primary { background: rgba(102, 126, 234, 0.1); color: #667eea; }
.badge-success { background: rgba(86, 171, 47, 0.1); color: #56ab2f; }
.badge-info { background: rgba(79, 172, 254, 0.1); color: #4facfe; }
.badge-warning { background: rgba(242, 153, 74, 0.1); color: #f2994a; }

/* Filtres modernes */
.filters-container-modern {
  background: white;
  padding: 1.5rem;
  border-radius: 14px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
  margin-bottom: 2rem;
}

.search-wrapper-modern {
  position: relative;
  display: flex;
  align-items: center;
}

.search-wrapper-modern > i {
  position: absolute;
  left: 14px;
  color: #6c757d;
  font-size: 1.125rem;
  pointer-events: none;
}

.search-input-modern {
  padding-left: 42px !important;
  padding-right: 42px !important;
  border: 2px solid #e9ecef;
  border-radius: 10px;
  transition: all 0.2s;
}

.search-input-modern:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.clear-search {
  position: absolute;
  right: 8px;
  padding: 0.25rem;
  color: #6c757d;
}

.filter-select-modern {
  border: 2px solid #e9ecef;
  border-radius: 10px;
  padding: 0.625rem 0.875rem;
  transition: all 0.2s;
}

.filter-select-modern:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.active-filters {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
  padding-top: 1rem;
  border-top: 1px solid #e9ecef;
}

.filter-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #6c757d;
}

.filter-badge {
  font-size: 0.875rem;
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.filter-badge:hover {
  opacity: 0.8;
  transform: scale(0.95);
}

.clear-all-btn {
  font-size: 0.875rem;
  color: #dc3545;
  padding: 0.25rem 0.5rem;
}

/* Table moderne */
.table-container-modern {
  background: white;
  border-radius: 14px;
  padding: 1.5rem;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.modern-table {
  margin-bottom: 0;
}

.modern-table :deep(thead) {
  background: #f8f9fa;
  border-radius: 10px;
}

.modern-table :deep(thead th) {
  border: none;
  padding: 1rem;
  font-weight: 600;
  color: #495057;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.modern-table :deep(tbody td) {
  padding: 1rem;
  vertical-align: middle;
  border-color: #f8f9fa;
}

.modern-table :deep(tbody tr) {
  transition: all 0.2s;
}

.modern-table :deep(tbody tr:hover) {
  background: #f8f9fa;
}

/* Cellule utilisateur */
.user-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  overflow: hidden;
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.user-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.user-name {
  font-weight: 600;
  color: #212529;
  font-size: 0.9375rem;
}

.user-matricule {
  font-size: 0.8125rem;
  color: #6c757d;
}

/* Cellule contact */
.contact-cell {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.contact-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.contact-item i {
  color: #6c757d;
  font-size: 1rem;
}

.contact-item a {
  color: #495057;
  text-decoration: none;
  transition: color 0.2s;
}

.contact-item a:hover {
  color: #667eea;
}

/* Badges */
.entity-badge {
  font-size: 0.8125rem;
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  font-weight: 500;
  background: #f8f9fa;
  color: #495057;
  border: 1px solid #e9ecef;
}

.job-title-text {
  font-size: 0.875rem;
  color: #495057;
}

.status-badge {
  font-size: 0.8125rem;
  padding: 0.5rem 0.875rem;
  border-radius: 8px;
  font-weight: 600;
}

/* Actions */
.action-buttons {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}

.action-buttons button {
  border-radius: 8px;
  padding: 0.5rem;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.action-buttons button:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Empty state */
.empty-state-modern {
  text-align: center;
  padding: 4rem 2rem;
}

.empty-state-modern i {
  font-size: 4rem;
  color: #dee2e6;
  margin-bottom: 1rem;
}

.empty-state-modern h4 {
  color: #495057;
  margin-bottom: 0.5rem;
}

.empty-state-modern p {
  color: #6c757d;
  margin-bottom: 1.5rem;
}

/* Pagination */
.pagination-container-modern {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e9ecef;
}

.pagination-info {
  font-size: 0.875rem;
  color: #6c757d;
}

.modern-pagination :deep(.page-link) {
  border-radius: 8px;
  border: 1px solid #e9ecef;
  color: #495057;
  padding: 0.5rem 0.75rem;
  margin: 0 0.25rem;
}

.modern-pagination :deep(.page-link:hover) {
  background: #f8f9fa;
  border-color: #667eea;
  color: #667eea;
}

.modern-pagination :deep(.page-item.active .page-link) {
  background: #667eea;
  border-color: #667eea;
}

@media (max-width: 767px) {
  .page-header-modern {
    padding: 1.5rem;
  }

  .header-title {
    font-size: 1.5rem;
  }

  .header-actions {
    width: 100%;
  }

  .action-btn {
    flex: 1;
  }

  .pagination-container-modern {
    flex-direction: column;
    gap: 1rem;
  }
}
</style>