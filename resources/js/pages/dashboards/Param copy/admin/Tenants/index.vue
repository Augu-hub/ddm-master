<template>
  <VerticalLayout>
    <Head title="Administration — Clients" />

    <!-- Header avec gradient -->
    <div class="page-header mb-4">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="icon-wrapper">
            <i class="ti ti-building-store fs-3"></i>
          </div>
          <div>
            <h3 class="mb-1 fw-bold">Gestion des Clients</h3>
            <p class="text-muted mb-0 small">Administration multi-tenant</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Statistiques -->
    <b-row class="g-3 mb-4">
      <b-col md="4">
        <div class="stat-card stat-primary">
          <div class="stat-icon">
            <i class="ti ti-building-store"></i>
          </div>
          <div class="stat-content">
            <h3 class="stat-value">{{ tenants.length }}</h3>
            <p class="stat-label">Clients Total</p>
          </div>
        </div>
      </b-col>
      <b-col md="4">
        <div class="stat-card stat-success">
          <div class="stat-icon">
            <i class="ti ti-users"></i>
          </div>
          <div class="stat-content">
            <h3 class="stat-value">{{ usersWithTenantsCount }}</h3>
            <p class="stat-label">Utilisateurs Assignés</p>
          </div>
        </div>
      </b-col>
      <b-col md="4">
        <div class="stat-card stat-warning">
          <div class="stat-icon">
            <i class="ti ti-user-off"></i>
          </div>
          <div class="stat-content">
            <h3 class="stat-value">{{ usersWithoutTenantsCount }}</h3>
            <p class="stat-label">Sans Affectation</p>
          </div>
        </div>
      </b-col>
    </b-row>

    <b-row class="g-3">
      <!-- Formulaire de création -->
      <b-col lg="4">
        <div class="creation-panel">
          <div class="panel-header">
            <div class="d-flex align-items-center gap-2">
              <i class="ti ti-plus-circle text-primary"></i>
              <h5 class="mb-0 fw-semibold">Nouveau Client</h5>
            </div>
          </div>

          <div class="panel-body">
            <b-form @submit.prevent="createTenant">
              <div class="form-section">
                <label class="form-label">
                  <i class="ti ti-building me-1"></i>
                  Nom du client
                </label>
                <b-form-input
                  v-model="tenantForm.name"
                  placeholder="Ex: Entreprise ABC"
                  class="modern-input"
                  required
                  :state="tenantForm.errors.name ? false : null"
                />
                <div v-if="tenantForm.errors.name" class="error-feedback">
                  {{ tenantForm.errors.name }}
                </div>
              </div>

              <div class="form-section">
                <label class="form-label">
                  <i class="ti ti-tag me-1"></i>
                  Code unique
                </label>
                <b-form-input
                  v-model="tenantForm.code"
                  placeholder="Ex: abc_corp"
                  class="modern-input"
                  required
                  :state="tenantForm.errors.code ? false : null"
                />
                <small class="form-hint">Identifiant unique du client</small>
                <div v-if="tenantForm.errors.code" class="error-feedback">
                  {{ tenantForm.errors.code }}
                </div>
              </div>

              <div class="form-section">
                <label class="form-label">
                  <i class="ti ti-database me-1"></i>
                  Base de données
                </label>
                <b-form-input
                  v-model="tenantForm.db_name"
                  placeholder="Ex: abc_database"
                  class="modern-input"
                  required
                  :state="tenantForm.errors.db_name ? false : null"
                />
                <small class="form-hint">Nom de la base de données dédiée</small>
                <div v-if="tenantForm.errors.db_name" class="error-feedback">
                  {{ tenantForm.errors.db_name }}
                </div>
              </div>

              <div class="form-section">
                <label class="form-label">
                  <i class="ti ti-users me-1"></i>
                  Utilisateurs à associer
                  <span class="badge bg-info-subtle text-info ms-2">
                    {{ tenantForm.user_ids.length }}
                  </span>
                </label>
                <b-form-select
                  v-model="tenantForm.user_ids"
                  :options="availableUsersOptions"
                  multiple
                  :select-size="5"
                  class="modern-select"
                />
                <small v-if="availableUsersOptions.length === 0" class="form-hint text-warning">
                  <i class="ti ti-alert-triangle me-1"></i>
                  Tous les utilisateurs sont déjà assignés
                </small>
                <small v-else class="form-hint">
                  Maintenez Ctrl/Cmd pour sélectionner plusieurs utilisateurs
                </small>
              </div>

              <b-button
                type="submit"
                variant="primary"
                class="modern-btn w-100"
                :disabled="tenantForm.processing"
              >
                <i class="ti ti-check me-2"></i>
                {{ tenantForm.processing ? 'Création en cours...' : 'Créer le Client' }}
              </b-button>
            </b-form>
          </div>
        </div>
      </b-col>

      <!-- Liste des clients -->
      <b-col lg="8">
        <div class="tenants-panel">
          <div class="panel-header">
            <div class="d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2">
                <i class="ti ti-list-check text-primary"></i>
                <h5 class="mb-0 fw-semibold">Clients Existants</h5>
              </div>
              <div class="badge bg-primary-subtle text-primary">
                {{ tenants.length }} client(s)
              </div>
            </div>
          </div>

          <div class="panel-body">
            <div v-if="tenants.length === 0" class="empty-state">
              <i class="ti ti-building-store fs-1 text-muted mb-3"></i>
              <p class="text-muted mb-0">Aucun client configuré</p>
              <small class="text-muted">Créez votre premier client pour commencer</small>
            </div>

            <div v-else class="tenants-list">
              <div
                v-for="tenant in tenants"
                :key="tenant.id"
                class="tenant-card"
              >
                <div class="tenant-main">
                  <div class="tenant-header">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                      <div class="tenant-icon">
                        <i class="ti ti-building-store"></i>
                      </div>
                      <div class="tenant-title">
                        <h6 class="mb-0">{{ tenant.name }}</h6>
                        <div class="d-flex align-items-center gap-2 mt-1">
                          <span class="badge bg-primary-subtle text-primary">
                            <i class="ti ti-tag me-1"></i>
                            {{ tenant.code }}
                          </span>
                          <span class="badge bg-secondary-subtle text-secondary">
                            <i class="ti ti-database me-1"></i>
                            {{ tenant.db_name }}
                          </span>
                        </div>
                      </div>
                    </div>

                    <div class="tenant-actions">
                      <b-button
                        size="sm"
                        variant="outline-success"
                        class="action-btn"
                        @click="showAddUsersModal(tenant)"
                        :disabled="availableUsersOptions.length === 0"
                        v-b-tooltip.hover="availableUsersOptions.length === 0 ? 'Aucun utilisateur disponible' : 'Ajouter des utilisateurs'"
                      >
                        <i class="ti ti-user-plus"></i>
                      </b-button>

                      <b-button
                        size="sm"
                        variant="outline-primary"
                        class="action-btn"
                        @click="editTenant(tenant)"
                        v-b-tooltip.hover="'Modifier le client'"
                      >
                        <i class="ti ti-edit"></i>
                      </b-button>

                      <b-button
                        size="sm"
                        variant="outline-danger"
                        class="action-btn"
                        @click="confirmDelete(tenant)"
                        :disabled="tenant.id === 1 || tenant.users_count > 0"
                        v-b-tooltip.hover="getDeleteTooltip(tenant)"
                      >
                        <i class="ti ti-trash"></i>
                      </b-button>
                    </div>
                  </div>

                  <div class="tenant-meta">
                    <small class="text-muted">
                      <i class="ti ti-calendar me-1"></i>
                      Créé le {{ tenant.created_at }}
                    </small>
                    <span class="badge" :class="tenant.users_count > 0 ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'">
                      <i class="ti ti-users me-1"></i>
                      {{ tenant.users_count }} utilisateur(s)
                    </span>
                  </div>

                  <div class="tenant-users">
                    <div v-if="tenant.users && tenant.users.length > 0">
                      <small class="text-muted d-block mb-2">
                        <i class="ti ti-users me-1"></i>
                        Utilisateurs assignés :
                      </small>
                      <div class="users-grid">
                        <div
                          v-for="user in tenant.users"
                          :key="user.id"
                          class="user-badge"
                        >
                          <div class="user-info">
                            <i class="ti ti-user-circle"></i>
                            <span>{{ user.name }}</span>
                          </div>
                          <b-button
                            size="sm"
                            variant="ghost"
                            class="remove-btn"
                            @click="removeUserFromTenant(tenant, user)"
                            v-b-tooltip.hover="'Retirer cet utilisateur'"
                          >
                            <i class="ti ti-x"></i>
                          </b-button>
                        </div>
                      </div>
                    </div>
                    <div v-else class="no-users">
                      <i class="ti ti-user-off me-2"></i>
                      <span>Aucun utilisateur associé à ce client</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </b-col>
    </b-row>

    <!-- Modal d'édition -->
    <b-modal
      v-model="editModal.show"
      :title="`Modifier le client — ${editModal.tenant?.name}`"
      size="lg"
      @hidden="resetEditModal"
      centered
    >
      <b-form v-if="editModal.tenant" @submit.prevent="updateTenant">
        <div class="form-section">
          <label class="form-label">
            <i class="ti ti-building me-1"></i>
            Nom du client
          </label>
          <b-form-input
            v-model="editModal.form.name"
            class="modern-input"
            required
            :state="editModal.errors.name ? false : null"
          />
          <div v-if="editModal.errors.name" class="error-feedback">
            {{ editModal.errors.name }}
          </div>
        </div>

        <div class="form-section">
          <label class="form-label">
            <i class="ti ti-tag me-1"></i>
            Code unique
          </label>
          <b-form-input
            v-model="editModal.form.code"
            class="modern-input"
            required
            :state="editModal.errors.code ? false : null"
          />
          <div v-if="editModal.errors.code" class="error-feedback">
            {{ editModal.errors.code }}
          </div>
        </div>

        <div class="form-section">
          <label class="form-label">
            <i class="ti ti-database me-1"></i>
            Base de données
          </label>
          <b-form-input
            v-model="editModal.form.db_name"
            class="modern-input"
            required
            :state="editModal.errors.db_name ? false : null"
          />
          <div v-if="editModal.errors.db_name" class="error-feedback">
            {{ editModal.errors.db_name }}
          </div>
        </div>
      </b-form>

      <template #modal-footer>
        <b-button variant="light" @click="editModal.show = false">
          <i class="ti ti-x me-2"></i>
          Annuler
        </b-button>
        <b-button
          variant="primary"
          @click="updateTenant"
          :disabled="editModal.loading"
        >
          <i class="ti ti-check me-2"></i>
          {{ editModal.loading ? 'Mise à jour...' : 'Mettre à jour' }}
        </b-button>
      </template>
    </b-modal>

    <!-- Modal pour ajouter des utilisateurs -->
    <b-modal
      v-model="addUsersModal.show"
      :title="`Ajouter des utilisateurs — ${addUsersModal.tenant?.name}`"
      @hidden="resetAddUsersModal"
      centered
    >
      <div class="form-section">
        <label class="form-label">
          <i class="ti ti-users me-1"></i>
          Utilisateurs disponibles
          <span v-if="addUsersModal.selectedUsers.length > 0" class="badge bg-primary-subtle text-primary ms-2">
            {{ addUsersModal.selectedUsers.length }} sélectionné(s)
          </span>
        </label>
        <b-form-select
          v-model="addUsersModal.selectedUsers"
          :options="availableUsersOptions"
          multiple
          :select-size="6"
          class="modern-select"
        />
        <small v-if="availableUsersOptions.length === 0" class="form-hint text-warning">
          <i class="ti ti-alert-triangle me-1"></i>
          Aucun utilisateur disponible
        </small>
        <small v-else class="form-hint">
          Maintenez Ctrl/Cmd pour sélectionner plusieurs utilisateurs
        </small>
      </div>

      <template #modal-footer>
        <b-button variant="light" @click="addUsersModal.show = false">
          <i class="ti ti-x me-2"></i>
          Annuler
        </b-button>
        <b-button
          variant="success"
          @click="addUsersToTenant"
          :disabled="addUsersModal.loading || addUsersModal.selectedUsers.length === 0"
        >
          <i class="ti ti-user-plus me-2"></i>
          {{ addUsersModal.loading ? 'Ajout...' : `Ajouter (${addUsersModal.selectedUsers.length})` }}
        </b-button>
      </template>
    </b-modal>
  </VerticalLayout>
</template>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps({
  tenants: { type: Array, default: () => [] },
  users: { type: Array, default: () => [] },
})

const tenantForm = useForm({
  name: '',
  code: '',
  db_name: '',
  user_ids: [],
})

const editModal = ref({
  show: false,
  loading: false,
  tenant: null,
  form: {
    name: '',
    code: '',
    db_name: '',
  },
  errors: {}
})

const addUsersModal = ref({
  show: false,
  loading: false,
  tenant: null,
  selectedUsers: []
})

const allAssignedUserIds = computed(() => {
  const assignedIds = new Set()
  props.tenants.forEach(tenant => {
    if (tenant.users) {
      tenant.users.forEach(user => assignedIds.add(user.id))
    }
  })
  return Array.from(assignedIds)
})

const availableUsers = computed(() =>
  props.users.filter(user => !allAssignedUserIds.value.includes(user.id))
)

const availableUsersOptions = computed(() =>
  availableUsers.value.map(user => ({
    value: user.id,
    text: `${user.name} — ${user.email}`
  }))
)

const usersWithTenantsCount = computed(() => allAssignedUserIds.value.length)
const usersWithoutTenantsCount = computed(() => props.users.length - usersWithTenantsCount.value)

function createTenant() {
  tenantForm.post('/admin/tenants', {
    preserveScroll: true,
    onSuccess: () => {
      tenantForm.reset()
      router.reload({ only: ['tenants', 'users'] })
    },
    onError: (errors) => {
      console.log('Erreurs création:', errors)
    }
  })
}

function editTenant(tenant) {
  editModal.value.tenant = tenant
  editModal.value.form = {
    name: tenant.name,
    code: tenant.code,
    db_name: tenant.db_name
  }
  editModal.value.show = true
}

function updateTenant() {
  if (!editModal.value.tenant) return

  editModal.value.loading = true
  router.put(`/admin/tenants/${editModal.value.tenant.id}`, editModal.value.form, {
    preserveScroll: true,
    onSuccess: () => {
      editModal.value.show = false
      resetEditModal()
      router.reload({ only: ['tenants'] })
    },
    onError: (errors) => {
      editModal.value.errors = errors
    },
    onFinish: () => {
      editModal.value.loading = false
    }
  })
}

function showAddUsersModal(tenant) {
  addUsersModal.value.tenant = tenant
  addUsersModal.value.selectedUsers = []
  addUsersModal.value.show = true
}

function addUsersToTenant() {
  if (!addUsersModal.value.tenant || addUsersModal.value.selectedUsers.length === 0) return

  addUsersModal.value.loading = true
  router.post(`/admin/tenants/${addUsersModal.value.tenant.id}/sync-users`, {
    user_ids: addUsersModal.value.selectedUsers
  }, {
    preserveScroll: true,
    onSuccess: () => {
      addUsersModal.value.show = false
      resetAddUsersModal()
      router.reload({ only: ['tenants', 'users'] })
    },
    onFinish: () => {
      addUsersModal.value.loading = false
    }
  })
}

function removeUserFromTenant(tenant, user) {
  if (confirm(`Retirer l'utilisateur "${user.name}" du client "${tenant.name}" ?`)) {
    router.post(`/admin/tenants/${tenant.id}/remove-user`, {
      user_id: user.id
    }, {
      preserveScroll: true,
      onSuccess: () => {
        router.reload({ only: ['tenants', 'users'] })
      }
    })
  }
}

function confirmDelete(tenant) {
  if (tenant.id === 1) {
    alert('Impossible de supprimer le client principal')
    return
  }

  if (tenant.users_count > 0) {
    alert('Veuillez retirer tous les utilisateurs avant de supprimer le client')
    return
  }

  if (confirm(`Êtes-vous sûr de vouloir supprimer le client "${tenant.name}" ?\n\nCette action est irréversible.`)) {
    router.delete(`/admin/tenants/${tenant.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        router.reload({ only: ['tenants'] })
      }
    })
  }
}

function getDeleteTooltip(tenant) {
  if (tenant.id === 1) return 'Impossible de supprimer le client principal'
  if (tenant.users_count > 0) return 'Retirez d\'abord tous les utilisateurs'
  return 'Supprimer ce client'
}

function resetEditModal() {
  editModal.value = {
    show: false,
    loading: false,
    tenant: null,
    form: {
      name: '',
      code: '',
      db_name: '',
    },
    errors: {}
  }
}

function resetAddUsersModal() {
  addUsersModal.value = {
    show: false,
    loading: false,
    tenant: null,
    selectedUsers: []
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

.creation-panel,
.tenants-panel {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.panel-header {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  padding: 1rem 1.25rem;
  border-bottom: 2px solid #dee2e6;
}

.panel-body {
  padding: 1.25rem;
}

.form-section {
  margin-bottom: 1.25rem;
}

.form-label {
  font-weight: 600;
  font-size: 0.875rem;
  color: #495057;
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
}

.modern-input,
.modern-select {
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  padding: 0.625rem 0.875rem;
  transition: all 0.2s;
  font-size: 0.875rem;
}

.modern-input:focus,
.modern-select:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  outline: none;
}

.modern-select {
  padding: 0.5rem;
}

.modern-select option {
  padding: 0.5rem;
  margin: 2px 0;
  border-radius: 4px;
}

.form-hint {
  display: block;
  margin-top: 0.375rem;
  color: #6c757d;
  font-size: 0.8rem;
}

.error-feedback {
  color: #dc3545;
  font-size: 0.8rem;
  margin-top: 0.375rem;
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

.tenants-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.tenant-card {
  background: white;
  border: 2px solid #e9ecef;
  border-radius: 12px;
  transition: all 0.2s;
}

.tenant-card:hover {
  border-color: #667eea;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
}

.tenant-main {
  padding: 1.25rem;
}

.tenant-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
}

.tenant-icon {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.25rem;
}

.tenant-title h6 {
  font-weight: 700;
  color: #212529;
}

.tenant-actions {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  width: 36px;
  height: 36px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  transition: all 0.2s;
}

.action-btn:hover:not(:disabled) {
  transform: translateY(-2px);
}

.tenant-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.5rem;
  padding: 0.75rem;
  background: #f8f9fa;
  border-radius: 8px;
  margin-bottom: 1rem;
}

.tenant-users {
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.users-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.user-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: white;
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  border: 1px solid #dee2e6;
  transition: all 0.2s;
}

.user-badge:hover {
  border-color: #667eea;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.15);
}

.user-info {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
}

.remove-btn {
  width: 24px;
  height: 24px;
  padding: 0;
  border: none;
  background: transparent;
  color: #dc3545;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.remove-btn:hover {
  background: #dc3545;
  color: white;
}

.no-users {
  text-align: center;
  padding: 1rem;
  color: #6c757d;
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.empty-state {
  text-align: center;
  padding: 3rem 1.5rem;
}

.empty-state i {
  display: block;
  margin: 0 auto 1rem;
}

/* Modals */
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
}

:deep(.modal-footer) {
  border-top: 1px solid #dee2e6;
  padding: 1rem 1.5rem;
  background: #f8f9fa;
  border-radius: 0 0 12px 12px;
}

/* Responsive */
@media (max-width: 991px) {
  .tenant-header {
    flex-direction: column;
    align-items: stretch;
  }

  .tenant-actions {
    justify-content: flex-end;
  }

  .stat-card {
    margin-bottom: 0;
  }
}

@media (max-width: 767px) {
  .page-header {
    padding: 1rem;
  }

  .icon-wrapper {
    width: 40px;
    height: 40px;
  }

  .stat-value {
    font-size: 1.5rem;
  }

  .panel-body {
    padding: 1rem;
  }
}
</style>