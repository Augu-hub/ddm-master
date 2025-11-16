<!-- resources/js/Pages/admin/assign/UsersModules.vue -->
<template>
  <VerticalLayout>
    <Head title="Administration — Affecter Modules → Utilisateur" />

    <!-- Header -->
    <div class="page-header mb-4">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="icon-wrapper">
            <i class="ti ti-user-cog fs-3"></i>
          </div>
          <div>
            <h3 class="mb-1 fw-bold">Affecter des Modules à un Utilisateur</h3>
            <p class="text-white-50 mb-0 small">Administration des habilitations par module</p>
          </div>
        </div>
      </div>
    </div>

    <b-row class="g-3">
      <!-- Panneau d'affectation -->
      <b-col lg="5">
        <div class="panel">
          <div class="panel-header">
            <div class="d-flex align-items-center gap-2">
              <i class="ti ti-user-plus text-primary"></i>
              <h5 class="mb-0 fw-semibold">Sélection & Affectation</h5>
            </div>
          </div>

          <div class="panel-body">
            <!-- Sélection utilisateur -->
            <div class="form-section">
              <label class="form-label">
                <i class="ti ti-user me-1"></i>
                Utilisateur
              </label>
              <b-form-select
                v-model="selectedUserId"
                :options="usersOptions"
                class="modern-select"
              />
              <small class="form-hint">
                Choisissez un utilisateur : ses modules actuels seront chargés automatiquement.
              </small>
            </div>

            <!-- Sélection modules -->
            <div class="form-section" :class="{ 'opacity-50': !selectedUserId }">
              <label class="form-label d-flex align-items-center justify-content-between">
                <span>
                  <i class="ti ti-apps me-1"></i>
                  Modules à affecter
                </span>
                <span class="badge bg-info-subtle text-info">
                  {{ form.module_ids.length }} sélectionné(s)
                </span>
              </label>

              <b-form-select
                v-model="form.module_ids"
                :options="modulesOptions"
                :select-size="10"
                multiple
                class="modern-select"
                :disabled="!selectedUserId"
              />

              <small class="form-hint">
                Astuce : maintenez <kbd>Ctrl/Cmd</kbd> pour multi-sélectionner. Les modules sont groupés par Service.
              </small>

              <!-- Mode de synchronisation -->
              <div class="mt-3">
                <label class="form-label">
                  <i class="ti ti-switch-horizontal me-1"></i>
                  Mode de synchronisation
                </label>
                <div class="d-flex flex-wrap gap-2">
                  <b-form-radio
                    v-model="form.mode"
                    name="mode"
                    value="replace"
                  >
                    Remplacer (sync)
                  </b-form-radio>
                  <b-form-radio
                    v-model="form.mode"
                    name="mode"
                    value="append"
                  >
                    Ajouter (sans retirer)
                  </b-form-radio>
                  <b-form-radio
                    v-model="form.mode"
                    name="mode"
                    value="detach_missing"
                  >
                    Détacher le reste
                  </b-form-radio>
                </div>
                <small class="form-hint">
                  <strong>Remplacer</strong> écrase les affectations existantes par votre sélection.
                </small>
              </div>
            </div>

            <!-- Actions -->
            <div class="d-grid gap-2 mt-3">
              <b-button
                variant="primary"
                class="modern-btn"
                :disabled="!selectedUserId || form.module_ids.length === 0 || submitting"
                @click="submit"
              >
                <i class="ti ti-check me-2"></i>
                {{ submitting ? 'Affectation…' : 'Affecter les modules' }}
              </b-button>

              <b-button
                variant="light"
                class="modern-btn"
                :disabled="!selectedUserId || submitting"
                @click="resetSelection"
              >
                <i class="ti ti-refresh me-2"></i>
                Réinitialiser
              </b-button>
            </div>
          </div>
        </div>
      </b-col>

      <!-- Panneau récap -->
      <b-col lg="7">
        <div class="panel">
          <div class="panel-header">
            <div class="d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2">
                <i class="ti ti-list-check text-primary"></i>
                <h5 class="mb-0 fw-semibold">Récapitulatif</h5>
              </div>
              <div v-if="selectedUser" class="badge bg-primary-subtle text-primary">
                {{ selectedUser.name }} — {{ selectedUser.email }}
              </div>
            </div>
          </div>

          <div class="panel-body">
            <div v-if="!selectedUser" class="empty-state">
              <i class="ti ti-user-search fs-1 text-muted mb-3"></i>
              <p class="text-muted mb-0">Sélectionnez un utilisateur à gauche pour voir ses modules</p>
            </div>

            <template v-else>
              <b-row class="g-3">
                <!-- Modules actuels -->
                <b-col md="6">
                  <div class="subpanel">
                    <div class="subpanel-header">
                      <i class="ti ti-lock-check me-2"></i>
                      Modules actuels
                    </div>
                    <div class="subpanel-body">
                      <div v-if="currentModuleIds.length === 0" class="no-items">
                        <i class="ti ti-info-circle me-2"></i>
                        Aucun module affecté
                      </div>
                      <ul v-else class="pill-list">
                        <li v-for="m in currentModules" :key="m.id" class="pill">
                          <span class="pill-title">{{ m.name }}</span>
                          <small class="pill-sub">{{ m.code }}</small>
                        </li>
                      </ul>
                    </div>
                  </div>
                </b-col>

                <!-- Nouvelle sélection -->
                <b-col md="6">
                  <div class="subpanel">
                    <div class="subpanel-header">
                      <i class="ti ti-edit me-2"></i>
                      Nouvelle sélection
                    </div>
                    <div class="subpanel-body">
                      <div v-if="form.module_ids.length === 0" class="no-items">
                        <i class="ti ti-hand-click me-2"></i>
                        Choisissez des modules dans la liste
                      </div>
                      <ul v-else class="pill-list">
                        <li v-for="id in form.module_ids" :key="`sel-${id}`" class="pill pill-info">
                          <span class="pill-title">{{ moduleById(id)?.name || '—' }}</span>
                          <small class="pill-sub">{{ moduleById(id)?.code }}</small>
                        </li>
                      </ul>
                    </div>
                  </div>
                </b-col>
              </b-row>
            </template>
          </div>
        </div>
      </b-col>
    </b-row>
  </VerticalLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps({
  users: { type: Array, default: () => [] },   // [{id,name,email, modules:[{id,code,name}]}]
  modules: { type: Array, default: () => [] }, // [{id,code,name, service:{id,label}}]
})

/** Sélections **/
const selectedUserId = ref(null)
const submitting = ref(false)

const form = ref({
  module_ids: [],
  mode: 'replace', // 'replace' | 'append' | 'detach_missing'
})

/** Helpers de données **/
const usersOptions = computed(() =>
  props.users.map(u => ({
    value: u.id,
    text: `${u.name} — ${u.email}`,
  }))
)

// Groupement des modules par service (optgroup pour b-form-select)
const modulesOptions = computed(() => {
  const groups = {}
  props.modules.forEach(m => {
    const label = m?.service?.label || 'Sans service'
    if (!groups[label]) groups[label] = []
    groups[label].push({ value: m.id, text: `${m.name} (${m.code})` })
  })
  return Object.entries(groups).map(([label, opts]) => ({ label, options: opts }))
})

const selectedUser = computed(() => props.users.find(u => u.id === selectedUserId.value) || null)
const currentModuleIds = computed(() => (selectedUser.value?.modules || []).map(m => m.id))
const currentModules = computed(() => selectedUser.value?.modules || [])

const moduleById = (id) => props.modules.find(m => m.id === id)

/** Effet : quand on change d’utilisateur, pré-remplir avec ses modules actuels */
watch(selectedUserId, (val) => {
  if (!val) {
    form.value.module_ids = []
    return
  }
  // Précharger = on édite l’existant
  form.value.module_ids = [...currentModuleIds.value]
})

/** Actions **/
const submit = () => {
  if (!selectedUserId.value || form.value.module_ids.length === 0) return
  submitting.value = true
  router.post(`/admin/users/${selectedUserId.value}/modules/sync`, {
    module_ids: form.value.module_ids,
    mode: form.value.mode,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      // Recharge pour rafraîchir les modules actuels de l'utilisateur
      router.reload({ only: ['users'] })
    },
    onFinish: () => { submitting.value = false }
  })
}

const resetSelection = () => {
  form.value.module_ids = [...currentModuleIds.value]
  form.value.mode = 'replace'
}
</script>

<style scoped>
.page-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 1.25rem;
  border-radius: 12px;
  color: white;
}
.icon-wrapper {
  width: 48px; height: 48px;
  background: rgba(255,255,255,.18);
  border-radius: 12px;
  display: grid; place-items: center;
  backdrop-filter: blur(8px);
}

.panel {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0,0,0,.08);
  overflow: hidden;
}
.panel-header {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  padding: 1rem 1.25rem;
  border-bottom: 2px solid #dee2e6;
}
.panel-body { padding: 1.25rem; }

.form-section { margin-bottom: 1rem; }
.form-label { font-weight: 600; font-size: .9rem; color: #495057; }

.modern-select {
  border: 2px solid #e0e0e0; border-radius: 8px;
  padding: .5rem .75rem; transition: all .2s;
  font-size: .9rem;
}
.modern-select:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102,126,234,.12);
  outline: none;
}
.form-hint { display:block; margin-top:.375rem; color:#6c757d; font-size:.8rem; }

.d-grid { display: grid; }
.gap-2 { gap: .5rem; }

.subpanel {
  background: #fff;
  border: 2px solid #e9ecef;
  border-radius: 12px;
  overflow: hidden;
}
.subpanel-header {
  background: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
  padding: .75rem 1rem;
  font-weight: 600;
}
.subpanel-body { padding: 1rem; }

.empty-state { text-align:center; padding: 2rem 1rem; }
.no-items { color:#6c757d; font-size:.9rem; }

.pill-list { list-style:none; padding:0; margin:0; display:flex; flex-wrap:wrap; gap:.5rem; }
.pill {
  border: 1px solid #dee2e6;
  border-radius: 999px;
  padding: .35rem .75rem;
  background: #fff;
}
.pill-info { background: #e7f1ff; border-color: #cfe2ff; }
.pill-title { font-weight: 600; margin-right:.5rem; }
.pill-sub { color:#6c757d; }

.opacity-50 { opacity: .5; pointer-events: none; }
</style>
