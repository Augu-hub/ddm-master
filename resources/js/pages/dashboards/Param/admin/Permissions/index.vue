<template>
  <VerticalLayout>
    <Head title="Administration — Permissions" />

    <!-- Header simple -->
    <b-row class="mb-3">
      <b-col>
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <i class="ti ti-key text-primary fs-5"></i>
            <h4 class="m-0 fw-semibold">Permissions</h4>
            <small class="text-muted ms-2">Multi-tenant</small>
          </div>

          <div class="d-flex align-items-center gap-2">
            <b-input-group size="sm" class="w-auto">
              <b-input-group-text><i class="ti ti-home-2"></i></b-input-group-text>
              <b-form-select v-model="selectedTenantId" :options="tenantOptions" />
            </b-input-group>

            <b-input-group size="sm" class="w-auto">
              <b-input-group-text><i class="ti ti-search"></i></b-input-group-text>
              <b-form-input v-model.trim="search" placeholder="Rechercher…" />
            </b-input-group>
          </div>
        </div>
      </b-col>
    </b-row>

    <b-row class="g-3">
      <!-- Colonne Permissions -->
      <b-col lg="7">
        <b-card no-body class="shadow-sm h-100 position-relative">
          <div v-if="loading" class="overlay">
            <b-spinner small class="me-2"></b-spinner> Chargement…
          </div>

          <b-card-header class="py-2 px-3 d-flex align-items-center justify-content-between">
            <h6 class="mb-0"><i class="ti ti-lock-access me-1"></i> Catalogue</h6>
            <small class="text-muted">Tenant : <strong>{{ currentTenantName || '—' }}</strong></small>
          </b-card-header>

          <b-card-body class="p-3">
            <b-alert v-if="!selectedTenantId" show variant="secondary" class="py-2 px-3 mb-3">
              <i class="ti ti-info-circle me-2"></i>
              Sélectionnez un tenant pour afficher/éditer les permissions.
            </b-alert>

            <!-- Formulaire ajout multiple -->
            <div v-else class="mb-3">
              <div class="creation-zone p-3 mb-3">
                <h6 class="mb-2"><i class="ti ti-plus-circle me-1"></i> Ajouter des permissions</h6>
                
                <div class="permission-rows">
                  <div v-for="(item, idx) in newPermissions" :key="idx" class="perm-row mb-2">
                    <b-row class="g-2 align-items-end">
                      <b-col>
                        <label class="form-label small mb-1">Nom permission</label>
                        <b-form-input
                          v-model.trim="item.name"
                          placeholder="ex: param.users.edit"
                          size="sm"
                        />
                      </b-col>
                      <b-col cols="auto" style="width: 120px">
                        <label class="form-label small mb-1">Guard</label>
                        <b-form-input
                          v-model.trim="item.guard_name"
                          placeholder="web"
                          size="sm"
                        />
                      </b-col>
                      <b-col cols="auto">
                        <b-button
                          v-if="newPermissions.length > 1"
                          size="sm"
                          variant="outline-danger"
                          @click="removePermissionRow(idx)"
                        >
                          <i class="ti ti-trash"></i>
                        </b-button>
                      </b-col>
                    </b-row>
                  </div>
                </div>

                <div class="d-flex gap-2 mt-2">
                  <b-button size="sm" variant="outline-primary" @click="addPermissionRow">
                    <i class="ti ti-plus me-1"></i> Ajouter une ligne
                  </b-button>
                  <b-button 
                    size="sm" 
                    variant="primary"
                    :disabled="!canCreatePermissions || loading"
                    @click="createMultiplePermissions"
                  >
                    <i class="ti ti-device-floppy me-1"></i>
                    Enregistrer {{ validPermissionsCount }} permission(s)
                  </b-button>
                </div>
              </div>

              <!-- Table des permissions -->
              <DataTable
                :value="filteredPermissions"
                size="small"
                class="pv-table"
                :paginator="true"
                :rows="15"
                :rowsPerPageOptions="[15,30,50]"
                dataKey="name"
                stripedRows
                @row-click="onSelectPermission"
              >
                <Column header="#" style="width:50px">
                  <template #body="{ index }">
                    <span class="row-num">{{ index + 1 }}</span>
                  </template>
                </Column>

                <Column header="Permission" field="name" sortable>
                  <template #body="{ data }">
                    <div class="d-flex align-items-center gap-2">
                      <i class="ti ti-key text-primary"></i>
                      <Tag :value="data.name" severity="info" class="font-monospace" />
                    </div>
                  </template>
                </Column>

                <Column header="Guard" field="guard_name" style="width:100px">
                  <template #body="{ data }">
                    <span class="badge bg-secondary-subtle text-secondary">
                      {{ data.guard_name || 'web' }}
                    </span>
                  </template>
                </Column>

                <Column header="Rôles" style="width:80px">
                  <template #body="{ data }">
                    <span class="badge bg-info-subtle text-info">
                      {{ data.role_ids?.length || 0 }}
                    </span>
                  </template>
                </Column>

                <Column style="width:100px">
                  <template #body="{ data }">
                    <b-button
                      size="sm"
                      variant="primary"
                      @click.stop="selectPermission(data)"
                    >
                      <i class="ti ti-settings"></i> Gérer
                    </b-button>
                  </template>
                </Column>

                <template #empty>
                  <div class="text-center py-3 text-muted">
                    <i class="ti ti-search-off fs-4 d-block mb-2"></i>
                    Aucune permission trouvée
                  </div>
                </template>
              </DataTable>
            </div>
          </b-card-body>
        </b-card>
      </b-col>

      <!-- Colonne Affectations -->
      <b-col lg="5">
        <b-card no-body class="shadow-sm h-100">
          <b-card-header class="py-2 px-3">
            <h6 class="mb-0"><i class="ti ti-user-cog me-1"></i> Affectations</h6>
          </b-card-header>
          <b-card-body class="p-3">
            <b-alert v-if="!selectedTenantId" show variant="secondary" class="py-2 px-3 mb-2">
              Sélectionnez un tenant.
            </b-alert>

            <b-alert v-else-if="!selectedPermission" show variant="light" class="py-2 px-3">
              <i class="ti ti-hand-click me-2"></i>
              Cliquez sur une permission pour gérer ses affectations
            </b-alert>

            <div v-else>
              <!-- Permission sélectionnée -->
              <div class="selected-perm mb-3 p-2">
                <small class="text-muted d-block mb-1">Permission sélectionnée</small>
                <div class="d-flex align-items-center gap-2">
                  <Tag :value="selectedPermission.name" severity="info" class="font-monospace" />
                  <span class="badge bg-secondary-subtle text-secondary">
                    {{ selectedPermission.guard_name || 'web' }}
                  </span>
                </div>
              </div>

              <!-- Section Rôles -->
              <div class="section-box mb-3">
                <div class="section-header">
                  <i class="ti ti-user-shield me-1"></i>
                  <strong>Rôles associés</strong>
                  <span class="badge bg-primary-subtle text-primary ms-auto">
                    {{ assignedRolesCount }}
                  </span>
                </div>

                <div class="roles-list">
                  <div
                    v-for="role in rolesInTenant"
                    :key="role.id"
                    class="role-item"
                    :class="{ 'role-active': roleHasPermission(role.id, selectedPermission) }"
                  >
                    <div class="d-flex align-items-center gap-2">
                      <i class="ti ti-badge"></i>
                      <span>{{ role.name }}</span>
                    </div>
                    <b-form-checkbox
                      switch
                      size="sm"
                      :checked="roleHasPermission(role.id, selectedPermission)"
                      @change="toggleRolePermission(role.id, selectedPermission)"
                    />
                  </div>

                  <div v-if="rolesInTenant.length === 0" class="text-center py-3 text-muted">
                    <i class="ti ti-user-off"></i>
                    <p class="small mb-0 mt-1">Aucun rôle disponible</p>
                  </div>
                </div>
              </div>

              <!-- Section Utilisateurs -->
              <div class="section-box">
                <div class="section-header">
                  <i class="ti ti-users me-1"></i>
                  <strong>Utilisateurs directs</strong>
                </div>

                <div class="users-assign">
                  <label class="form-label small mb-1">Utilisateur</label>
                  <b-form-select
                    v-model="selectedUserId"
                    :options="userOptions"
                    size="sm"
                    class="mb-2"
                  />

                  <div class="d-flex gap-2">
                    <b-button
                      size="sm"
                      variant="success"
                      class="flex-grow-1"
                      :disabled="!selectedUserId"
                      @click="attachPermissionToUser"
                    >
                      <i class="ti ti-user-plus me-1"></i> Attribuer
                    </b-button>
                    <b-button
                      size="sm"
                      variant="outline-danger"
                      class="flex-grow-1"
                      :disabled="!selectedUserId"
                      @click="detachPermissionFromUser"
                    >
                      <i class="ti ti-user-minus me-1"></i> Retirer
                    </b-button>
                  </div>
                </div>
              </div>
            </div>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>
  </VerticalLayout>
</template>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'

const props = defineProps({
  tenants: { type: Array, default: () => [] },
  roles: { type: Array, default: () => [] },
  permissions: { type: Array, default: () => [] },
  users: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({ team_id: null }) },
})

const loading = ref(false)
const selectedTenantId = ref(props.filters?.team_id ?? null)
const search = ref('')
const selectedPermission = ref(null)
const selectedUserId = ref(null)

// Gestion des permissions multiples
const newPermissions = ref([{ name: '', guard_name: 'web' }])

const addPermissionRow = () => {
  newPermissions.value.push({ name: '', guard_name: 'web' })
}

const removePermissionRow = (idx) => {
  if (newPermissions.value.length > 1) {
    newPermissions.value.splice(idx, 1)
  }
}

const validPermissionsCount = computed(() => {
  return newPermissions.value.filter(p => p.name.trim()).length
})

const canCreatePermissions = computed(() => {
  return validPermissionsCount.value > 0 && selectedTenantId.value
})

const tenantOptions = computed(() => [
  { value: null, text: '— Sélectionner un tenant —', disabled: true },
  ...(props.tenants || []).map(t => ({ value: t.id, text: `${t.code} — ${t.name}` })),
])

const currentTenantName = computed(() =>
  (props.tenants || []).find(t => String(t.id) === String(selectedTenantId.value))?.name ?? ''
)

const routeTo = (name, fallback) =>
  (typeof window.route === 'function' ? window.route(name) : fallback)

const fetchTenantData = () => {
  if (!selectedTenantId.value) return
  loading.value = true
  router.get(
    routeTo('admin.permissions.index', '/admin/permissions'),
    { team_id: selectedTenantId.value },
    {
      preserveState: true,
      replace: true,
      onFinish: () => { loading.value = false }
    }
  )
}

watch(selectedTenantId, () => {
  fetchTenantData()
  selectedPermission.value = null
  newPermissions.value = [{ name: '', guard_name: 'web' }]
}, { immediate: true })

const permissionsInTenant = computed(() =>
  (props.permissions || []).filter(p =>
    String(p.team_id) === String(selectedTenantId.value)
  )
)

const rolesInTenant = computed(() =>
  (props.roles || []).filter(r =>
    String(r.team_id) === String(selectedTenantId.value)
  )
)

const filteredPermissions = computed(() => {
  const q = (search.value || '').toLowerCase()
  return permissionsInTenant.value
    .filter(p => !q || String(p.name).toLowerCase().includes(q))
    .sort((a, b) => a.name.localeCompare(b.name))
})

const assignedRolesCount = computed(() => {
  if (!selectedPermission.value) return 0
  return rolesInTenant.value.filter(r =>
    roleHasPermission(r.id, selectedPermission.value)
  ).length
})

const userOptions = computed(() => [
  { value: null, text: '— Sélectionner un utilisateur —', disabled: true },
  ...(props.users || []).map(u => ({ value: u.id, text: `${u.name} — ${u.email}` })),
])

const byRoleId = computed(() =>
  Object.fromEntries((props.roles || []).map(r => [String(r.id), r]))
)

function selectPermission(p) {
  selectedPermission.value = p
}

function onSelectPermission(evt) {
  selectPermission(evt.data)
}

function roleHasPermission(roleId, perm) {
  if (!perm) return false
  if (Array.isArray(perm.role_ids)) {
    return perm.role_ids.includes(roleId)
  }
  const r = byRoleId.value[String(roleId)]
  const list = r?.permission_names || r?.permissions?.map(p => p.name) || []
  return list.includes(perm.name)
}

function createMultiplePermissions() {
  if (!canCreatePermissions.value) return

  const valid = newPermissions.value.filter(p => p.name.trim())
  if (valid.length === 0) return

  loading.value = true

  const promises = valid.map(perm => {
    return new Promise((resolve, reject) => {
      router.post(
        routeTo('admin.permissions.store', '/admin/permissions'),
        {
          name: perm.name.trim(),
          guard_name: perm.guard_name || 'web',
          team_id: selectedTenantId.value
        },
        {
          preserveScroll: true,
          onSuccess: () => resolve(),
          onError: () => reject()
        }
      )
    })
  })

  Promise.allSettled(promises).finally(() => {
    loading.value = false
    newPermissions.value = [{ name: '', guard_name: 'web' }]
    router.reload({ only: ['permissions'] })
  })
}

function toggleRolePermission(roleId, perm) {
  if (!perm) return
  const attach = !roleHasPermission(roleId, perm)
  loading.value = true
  router.post(
    routeTo('admin.permissions.syncRole', '/admin/permissions/sync-role'),
    {
      team_id: selectedTenantId.value,
      role_id: roleId,
      permission_name: perm.name,
      attach
    },
    {
      preserveScroll: true,
      onFinish: () => {
        loading.value = false
        router.reload({ only: ['roles', 'permissions'] })
      }
    }
  )
}

function attachPermissionToUser() {
  if (!selectedUserId.value || !selectedPermission.value) return
  loading.value = true
  router.post(
    routeTo('admin.permissions.syncUser', '/admin/permissions/sync-user'),
    {
      team_id: selectedTenantId.value,
      user_id: selectedUserId.value,
      permission_name: selectedPermission.value.name,
      attach: true
    },
    {
      preserveScroll: true,
      onFinish: () => {
        loading.value = false
        selectedUserId.value = null
      }
    }
  )
}

function detachPermissionFromUser() {
  if (!selectedUserId.value || !selectedPermission.value) return
  loading.value = true
  router.post(
    routeTo('admin.permissions.syncUser', '/admin/permissions/sync-user'),
    {
      team_id: selectedTenantId.value,
      user_id: selectedUserId.value,
      permission_name: selectedPermission.value.name,
      attach: false
    },
    {
      preserveScroll: true,
      onFinish: () => {
        loading.value = false
        selectedUserId.value = null
      }
    }
  )
}
</script>

<style scoped>
.overlay {
  position: absolute;
  inset: 0;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 5;
}

.creation-zone {
  background: #f8f9fa;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
}

.perm-row {
  background: white;
  padding: 0.75rem;
  border-radius: 0.35rem;
  border: 1px solid #e5e7eb;
}

.row-num {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  background: #f1f5f9;
  border-radius: 50%;
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
}

.pv-table :deep(.p-datatable-thead > tr > th) {
  background: #f8fafc;
  border: 1px solid #e5e7eb;
  font-weight: 600;
  font-size: 0.8rem;
  color: #475569;
  padding: 0.5rem 0.75rem;
}

.pv-table :deep(.p-datatable-tbody > tr) {
  cursor: pointer;
  transition: background 0.2s;
}

.pv-table :deep(.p-datatable-tbody > tr:hover) {
  background: #f8f9fa;
}

.pv-table :deep(.p-datatable-tbody > tr > td) {
  border: 1px solid #eef2f7;
  vertical-align: middle;
  padding: 0.5rem 0.75rem;
  font-size: 0.8rem;
}

.selected-perm {
  background: #f8f9fa;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
}

.section-box {
  background: #f8f9fa;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  padding: 1rem;
}

.section-header {
  display: flex;
  align-items: center;
  font-size: 0.875rem;
  margin-bottom: 0.75rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #dee2e6;
}

.roles-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.role-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: white;
  padding: 0.5rem 0.75rem;
  border-radius: 0.35rem;
  border: 1px solid #e5e7eb;
  transition: all 0.2s;
}

.role-item:hover {
  border-color: #3b82f6;
}

.role-item.role-active {
  background: #eff6ff;
  border-color: #3b82f6;
}

.users-assign {
  background: white;
  padding: 0.75rem;
  border-radius: 0.35rem;
}
</style>