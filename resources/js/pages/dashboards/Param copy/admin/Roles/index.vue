<template>
  <VerticalLayout>
    <Head title="Administration — Rôles" />

    <b-row class="mb-2">
      <b-col>
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <i class="ti ti-user-shield text-primary fs-5"></i>
            <h4 class="m-0 fw-semibold">Rôles</h4>
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

    <b-row class="g-2">
      <!-- Créer + Liste -->
      <b-col lg="6" xl="6">
        <b-card no-body class="shadow-sm h-100 position-relative">
          <div v-if="loading" class="overlay">
            <b-spinner small class="me-2"></b-spinner> Chargement…
          </div>

          <b-card-header class="py-2 px-3 d-flex align-items-center justify-content-between">
            <h6 class="mb-0"><i class="ti ti-badge me-1"></i> Catalogue</h6>
            <small class="text-muted">Tenant : <strong>{{ currentTenantName || '—' }}</strong></small>
          </b-card-header>

          <b-card-body class="p-2">
            <b-alert v-if="!selectedTenantId" show variant="secondary" class="py-2 px-3 mb-2">
              Sélectionnez un tenant pour afficher/éditer les rôles.
            </b-alert>

            <b-form v-else class="mb-2" @submit.prevent="createRole">
              <div class="d-flex gap-2 align-items-end flex-wrap">
                <div>
                  <label class="form-label mb-1">Nom du rôle</label>
                  <b-form-input class="form-control-sm" v-model.trim="roleForm.name" placeholder="ex: manager" required />
                  <small v-if="roleForm.errors.name" class="text-danger">{{ roleForm.errors.name }}</small>
                </div>
                <div>
                  <label class="form-label mb-1">Guard</label>
                  <b-form-input class="form-control-sm" v-model.trim="roleForm.guard_name" placeholder="web" />
                </div>
                <div class="ms-auto">
                  <b-button size="sm" variant="primary" type="submit" :disabled="loading || roleForm.processing || !roleForm.name">
                    <i class="ti ti-plus"></i> Créer
                  </b-button>
                </div>
              </div>
            </b-form>

            <DataTable
              :value="filteredRoles"
              size="small"
              class="pv-table flat"
              :paginator="true"
              :rows="12"
              :rowsPerPageOptions="[12,25,50]"
              dataKey="id"
              @row-click="onSelectRole"
            >
              <Column header="#" style="width:40px">
                <template #body="{ index }">{{ index + 1 }}</template>
              </Column>
              <Column header="Rôle">
                <template #body="{ data }">
                  <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-badge"></i>
                    <strong>{{ data.name }}</strong>
                  </div>
                </template>
              </Column>
              <Column header="Guard" style="width:90px">
                <template #body="{ data }"><span class="text-muted">{{ data.guard_name || 'web' }}</span></template>
              </Column>
              <Column header="Permissions" style="width:120px">
                <template #body="{ data }">
                  <b-badge variant="info" pill>{{ countPerms(data) }}</b-badge>
                </template>
              </Column>
              <Column header=" " style="width:90px" bodyClass="text-end">
                <template #body="{ data }">
                  <b-button size="sm" variant="light" @click="selectRole(data)">
                    <i class="ti ti-adjustments"></i> Gérer
                  </b-button>
                </template>
              </Column>
              <template #empty><div class="text-muted py-2">Aucun rôle</div></template>
            </DataTable>
          </b-card-body>
        </b-card>
      </b-col>

      <!-- Affectations: permissions du rôle + utilisateurs -->
      <b-col lg="6" xl="6">
        <b-card no-body class="shadow-sm h-100">
          <b-card-header class="py-2 px-3"><h6 class="mb-0">Affectations</h6></b-card-header>
          <b-card-body class="p-2">
            <b-alert v-if="!selectedTenantId" show variant="secondary" class="py-2 px-3 mb-2">
              Sélectionnez un tenant.
            </b-alert>

            <b-alert v-else-if="!selectedRole" show variant="light" class="py-2 px-3">
              Sélectionnez un rôle pour ajuster ses permissions et ses utilisateurs.
            </b-alert>

            <div v-else>
              <div class="d-flex align-items-center gap-2 mb-2">
                <i class="ti ti-badge"></i>
                <strong>{{ selectedRole.name }}</strong>
                <span class="text-muted">/ guard: {{ selectedRole.guard_name || 'web' }}</span>
              </div>

              <b-row class="g-2">
                <!-- Permissions du rôle -->
                <b-col cols="12">
                  <b-card no-body>
                    <b-card-header class="py-2 px-3"><h6 class="mb-0"><i class="ti ti-key me-1"></i> Permissions</h6></b-card-header>
                    <b-card-body class="p-2">
                      <div class="d-flex align-items-center gap-2 mb-1">
                        <b-input-group size="sm" class="w-auto">
                          <b-input-group-text><i class="ti ti-search"></i></b-input-group-text>
                          <b-form-input v-model.trim="permSearch" placeholder="Filtrer permissions…" />
                        </b-input-group>
                      </div>
                      <div class="d-flex flex-wrap gap-2">
                        <div
                          v-for="p in filteredPermissionsForRole"
                          :key="p.name"
                          class="d-flex align-items-center justify-content-between small-tile"
                        >
                          <span class="font-monospace">{{ p.name }}</span>
                          <b-form-checkbox
                            switch size="sm"
                            :checked="roleHasPermission(selectedRole, p)"
                            @change="togglePermissionOnRole(selectedRole, p)"
                          />
                        </div>
                      </div>
                    </b-card-body>
                  </b-card>
                </b-col>

                <!-- Utilisateurs du rôle -->
                <b-col cols="12">
                  <b-card no-body>
                    <b-card-header class="py-2 px-3"><h6 class="mb-0"><i class="ti ti-users me-1"></i> Utilisateurs</h6></b-card-header>
                    <b-card-body class="p-2">
                      <div class="d-flex align-items-end gap-2 flex-wrap">
                        <div class="flex-grow-1">
                          <label class="form-label mb-1">Utilisateur</label>
                          <b-form-select v-model="selectedUserId" :options="userOptions" class="form-select-sm" />
                        </div>
                        <div class="d-flex gap-1">
                          <b-button size="sm" variant="primary" :disabled="!selectedUserId" @click="attachRoleToUser">
                            <i class="ti ti-user-plus"></i> Assigner
                          </b-button>
                          <b-button size="sm" variant="outline-danger" :disabled="!selectedUserId" @click="detachRoleFromUser">
                            <i class="ti ti-user-minus"></i> Retirer
                          </b-button>
                        </div>
                      </div>
                    </b-card-body>
                  </b-card>
                </b-col>
              </b-row>
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

const props = defineProps({
  tenants:     { type: Array,  default: () => [] }, // [{id,code,name}]
  roles:       { type: Array,  default: () => [] }, // [{id,name,guard_name,team_id,permission_names?:[]}]
  permissions: { type: Array,  default: () => [] }, // [{name,guard_name,team_id}]
  users:       { type: Array,  default: () => [] }, // [{id,name,email}]
  filters:     { type: Object, default: () => ({ team_id: null }) },
})

const loading = ref(false)
const selectedTenantId = ref(props.filters?.team_id ?? null)
const tenantOptions = computed(() => [
  { value: null, text: '— Sélectionner un tenant —', disabled: true },
  ...(props.tenants||[]).map(t => ({ value: t.id, text: `${t.code} — ${t.name}` })),
])
const currentTenantName = computed(() =>
  (props.tenants||[]).find(t => String(t.id) === String(selectedTenantId.value))?.name ?? '')

const search = ref('')
const routeTo = (name, fallback) => (typeof window.route === 'function' ? window.route(name) : fallback)

const fetchTenantData = () => {
  if (!selectedTenantId.value) return
  loading.value = true
  router.get(routeTo('admin.roles.index','/admin/roles'), { team_id: selectedTenantId.value }, {
    preserveState: true, replace: true, onFinish: () => { loading.value = false }
  })
}
watch(selectedTenantId, fetchTenantData, { immediate: true })

/* créer rôle */
const roleForm = useForm({ name: '', guard_name: 'web', team_id: selectedTenantId.value })
function createRole() {
  roleForm.team_id = selectedTenantId.value
  const opts = { preserveScroll: true, onSuccess: () => { roleForm.reset('name'); router.reload({ only: ['roles'] }) } }
  roleForm.post(routeTo('admin.roles.store','/admin/roles'), opts)
}

/* listes filtrées */
const rolesInTenant = computed(() =>
  (props.roles||[]).filter(r => String(r.team_id) === String(selectedTenantId.value))
)
const filteredRoles = computed(() => {
  const q = (search.value||'').toLowerCase()
  return rolesInTenant.value
    .filter(r => !q || String(r.name).toLowerCase().includes(q))
    .sort((a,b)=> a.name.localeCompare(b.name))
})

/* sélection */
const selectedRole = ref(null)
function selectRole(r){ selectedRole.value = r }
function onSelectRole(evt){ selectRole(evt.data) }

/* permissions côté rôle */
const permissionsInTenant = computed(() =>
  (props.permissions||[]).filter(p => String(p.team_id) === String(selectedTenantId.value))
)
const permSearch = ref('')
const filteredPermissionsForRole = computed(() => {
  const q = (permSearch.value||'').toLowerCase()
  return permissionsInTenant.value
    .filter(p => !q || String(p.name).toLowerCase().includes(q))
    .sort((a,b)=> a.name.localeCompare(b.name))
})

const byRoleId = computed(() =>
  Object.fromEntries((props.roles||[]).map(r => [String(r.id), r]))
)
function roleHasPermission(role, perm) {
  const r = byRoleId.value[String(role?.id)]
  const names = r?.permission_names || r?.permissions?.map(p=>p.name) || []
  return names.includes(perm.name)
}
function togglePermissionOnRole(role, perm) {
  const attach = !roleHasPermission(role, perm)
  loading.value = true
  router.post(routeTo('admin.permissions.syncRole','/admin/permissions/sync-role'), {
    team_id: selectedTenantId.value,
    role_id: role.id,
    permission_name: perm.name,
    attach,
  }, { preserveScroll:true, onFinish: () => { loading.value=false; router.reload({ only:['roles'] }) } })
}

/* utilisateurs */
const usersAll = computed(() => props.users || [])
const userOptions = computed(()=>[
  { value:null, text:'— Sélectionner —', disabled:true },
  ...usersAll.value.map(u => ({ value: u.id, text: `${u.name} <${u.email}>` })),
])
const selectedUserId = ref(null)

function attachRoleToUser(){
  if (!selectedRole.value || !selectedUserId.value) return
  loading.value = true
  router.post(routeTo('admin.roles.syncUserRoles','/admin/roles/sync-user'), {
    team_id: selectedTenantId.value,
    user_id: selectedUserId.value,
    role_name: selectedRole.value.name,
    attach: true,
  }, { preserveScroll:true, onFinish:()=>{ loading.value=false } })
}
function detachRoleFromUser(){
  if (!selectedRole.value || !selectedUserId.value) return
  loading.value = true
  router.post(routeTo('admin.roles.syncUserRoles','/admin/roles/sync-user'), {
    team_id: selectedTenantId.value,
    user_id: selectedUserId.value,
    role_name: selectedRole.value.name,
    attach: false,
  }, { preserveScroll:true, onFinish:()=>{ loading.value=false } })
}

/* util */
function countPerms(r){
  const names = r?.permission_names || r?.permissions?.map(p=>p.name) || []
  return names.length
}
</script>

<style scoped>
.overlay{ position:absolute; inset:0; background:rgba(255,255,255,.6); display:flex; align-items:center; justify-content:center; z-index:5 }
.pv-table :deep(.p-datatable-thead > tr > th){ background:#f8fafc;border:1px solid #e5e7eb;font-weight:600;font-size:.74rem;color:#475569;padding:.25rem .35rem }
.pv-table :deep(.p-datatable-tbody > tr > td){ border:1px solid #eef2f7;vertical-align:middle;padding:.25rem .35rem;font-size:.72rem }
.small-tile{ min-width:260px; padding:.25rem .5rem; border:1px solid #e5e7eb; border-radius:.4rem; background:#fff }
</style>
