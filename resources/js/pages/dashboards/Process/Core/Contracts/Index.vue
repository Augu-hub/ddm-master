<template>
  <VerticalLayout>
    <Head title="Contrat d'interfaces" />

    <!-- ============ HEADER MODERNE AVEC NOTIFICATIONS ============ -->
    <b-card class="shadow-sm mb-3 p-3 bg-gradient-primary">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h2 class="fw-bold text-white mb-0" style="font-size: 1.3rem;">
            <i class="ti ti-file-text me-2"></i>
            Contrat d'Interfaces
          </h2>
          <p class="text-white-50 small mb-0" style="font-size: 0.75rem;">Gestion complète des interfaces processus</p>
        </div>

        <div class="d-flex align-items-center gap-3">
          <!-- 🔔 CLOCHE NOTIFICATIONS -->
          <div class="position-relative">
            <button @click="showNotifs = !showNotifs" class="btn btn-link text-white p-0" style="font-size: 1.8rem; position: relative;">
              <i class="ti ti-bell-ringing"></i>
              <span v-if="unreadCount > 0" class="badge bg-danger position-absolute top-0 start-100 translate-middle" style="font-size: 0.7rem;">
                {{ unreadCount }}
              </span>
            </button>

            <!-- PANEL NOTIFICATIONS -->
            <div v-if="showNotifs" class="notification-dropdown shadow-lg rounded" style="position: absolute; right: 0; top: calc(100% + 10px); width: 420px; max-height: 600px; background: white; z-index: 1050; overflow-y: auto;">
              
              <!-- Header -->
              <div class="d-flex justify-content-between align-items-center p-3 border-bottom bg-light" style="font-weight: 600;">
                <span><i class="ti ti-bell me-2"></i>Notifications ({{ notifsList.length }})</span>
                <button @click="showNotifs = false" class="btn btn-sm btn-link p-0 text-muted">
                  <i class="ti ti-x"></i>
                </button>
              </div>

              <!-- Liste Notifications -->
              <div v-if="notifsList.length > 0">
                <div v-for="n in notifsList" :key="n.id" 
                     @click="markNotifRead(n.id)"
                     :class="['p-3', 'border-bottom', 'cursor-pointer', !n.read_at ? 'bg-info' : 'bg-white']"
                     style="transition: 0.2s; font-size: 0.9rem;">
                  
                  <!-- Badge statut -->
                  <div class="mb-2">
                    <span v-if="!n.read_at" class="badge bg-danger me-2">⚠️ Non-lu</span>
                    <span v-else class="badge bg-secondary">✓ Lu</span>
                  </div>

                  <!-- Titre -->
                  <h6 class="mb-2 fw-bold">📋 {{ n.process_name }}</h6>

                  <!-- Détails -->
                  <small class="d-block text-muted mb-1">
                    <i class="ti ti-code"></i> {{ n.process_code }}
                  </small>
                  <small class="d-block text-dark mb-2">
                    <strong>Sortie :</strong> {{ n.output_label }}
                  </small>
                  <small class="d-block text-dark mb-2">
                    <strong>Fonction :</strong> {{ n.function_name }}<br>
                    <strong>Utilisateur :</strong> {{ n.user_name }}
                  </small>
                  <small v-if="n.expectations" class="d-block text-muted mb-2">
                    <strong>Attentes :</strong> {{ n.expectations }}
                  </small>
                  <small class="text-muted" style="font-size: 0.8rem;">
                    {{ formatDate(n.created_at) }}
                  </small>
                </div>

                <!-- Action -->
                <div class="p-3 bg-light border-top d-flex gap-2">
                  <button @click="markAllNotifsRead" class="btn btn-sm btn-outline-primary flex-grow-1">
                    <i class="ti ti-check-all me-1"></i> Tout marquer lu
                  </button>
                </div>
              </div>

              <!-- Vide -->
              <div v-else class="p-4 text-center text-muted">
                <i class="ti ti-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                <p class="mt-2">Aucune notification</p>
              </div>
            </div>

            <!-- Overlay fermeture -->
            <div v-if="showNotifs" @click="showNotifs = false" style="position: fixed; inset: 0; z-index: 1049;"></div>
          </div>

          <!-- User Info -->
          <div class="text-end text-white" style="font-size: 0.85rem;">
            <strong>{{ user?.name }}</strong><br />
            <small style="font-size: 0.7rem;">{{ link?.function_name }} — {{ link?.entity_name }}</small>
          </div>
        </div>
      </div>
    </b-card>

    <!-- ============ SÉLECTION PROCESSUS ============ -->
    <b-card class="shadow-sm mb-3 p-2">
      <h6 class="fw-bold mb-2" style="font-size: 0.9rem;">
        <i class="ti ti-list-check text-primary me-2"></i>
        Sélectionner un Processus
      </h6>

      <b-row class="g-2">
        <b-col md="4">
          <label class="fw-bold small text-muted text-uppercase" style="font-size: 0.65rem;">Entité</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light" style="font-size: 0.8rem;">
              <i class="ti ti-building text-muted"></i>
            </span>
            <input class="form-control form-control-sm" :value="link?.entity_name" disabled />
          </div>
        </b-col>

        <b-col md="4">
          <label class="fw-bold small text-muted text-uppercase" style="font-size: 0.65rem;">Fonction</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light" style="font-size: 0.8rem;">
              <i class="ti ti-briefcase text-muted"></i>
            </span>
            <input class="form-control form-control-sm" :value="link?.function_name" disabled />
          </div>
        </b-col>

        <b-col md="4">
          <label class="fw-bold small text-muted text-uppercase" style="font-size: 0.65rem;">Processus</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light" style="font-size: 0.8rem;">
              <i class="ti ti-flow-switch text-muted"></i>
            </span>
            <select v-model="selectedProcess" @change="loadContract" class="form-select form-select-sm">
              <option value="">📋 Choisir un processus...</option>
              <option v-for="p in processes" :key="p.id" :value="p.id">
                {{ p.code }} — {{ p.name }}
              </option>
            </select>
          </div>
        </b-col>
      </b-row>
    </b-card>

    <!-- ============ LISTE CONTRATS EXISTANTS ============ -->
    <template v-if="contracts.length > 0">
      <b-card class="shadow-sm mb-3 p-2">
        <h6 class="fw-bold mb-2" style="font-size: 0.9rem;">
          <i class="ti ti-inbox text-primary me-2"></i>
          Contrats Existants ({{ contracts.length }})
        </h6>

        <div class="table-responsive">
          <table class="table table-hover align-middle table-sm" style="font-size: 0.85rem; margin-bottom: 0;">
            <thead class="table-light">
              <tr style="height: 30px;">
                <th style="width: 180px; padding: 0.4rem 0.5rem;">
                  <i class="ti ti-code me-1"></i> Processus
                </th>
                <th style="padding: 0.4rem 0.5rem;">
                  <i class="ti ti-user me-1"></i> Propriétaire
                </th>
                <th style="width: 60px; padding: 0.4rem 0.5rem;">
                  <i class="ti ti-arrow-up me-1"></i> Sorties
                </th>
                <th style="width: 70px; padding: 0.4rem 0.5rem;">Statut</th>
                <th style="width: 80px; padding: 0.4rem 0.5rem;">Modifié</th>
                <th style="width: 50px; padding: 0.4rem 0.5rem;">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="contract in contracts" :key="contract.id" class="cursor-pointer" 
                  @click="viewContract(contract)" style="height: 32px;">
                <td style="padding: 0.4rem 0.5rem;">
                  <strong class="text-primary" style="font-size: 0.85rem;">{{ contract.process_code }}</strong><br />
                  <small class="text-muted" style="font-size: 0.7rem;">{{ contract.process_name }}</small>
                </td>
                <td style="padding: 0.4rem 0.5rem; font-size: 0.85rem;">{{ contract.owner || '—' }}</td>
                <td style="padding: 0.4rem 0.5rem;">
                  <span class="badge bg-info-soft text-info" style="font-size: 0.7rem; padding: 0.3rem 0.4rem;">
                    {{ contract.outputs_count }}
                  </span>
                </td>
                <td style="padding: 0.4rem 0.5rem;">
                  <span v-if="contract.status === 'active'" class="badge bg-success-soft text-success" style="font-size: 0.7rem; padding: 0.3rem 0.4rem;">
                    ✓ Actif
                  </span>
                  <span v-else class="badge bg-secondary-soft text-secondary" style="font-size: 0.7rem; padding: 0.3rem 0.4rem;">
                    {{ contract.status }}
                  </span>
                </td>
                <td style="padding: 0.4rem 0.5rem;">
                  <small class="text-muted" style="font-size: 0.7rem;">{{ contract.updated_at }}</small>
                </td>
                <td style="padding: 0.4rem 0.5rem;">
                  <b-button size="sm" variant="outline-primary" @click.stop="loadContractById(contract.id)" style="padding: 0.3rem 0.5rem; font-size: 0.75rem;">
                    <i class="ti ti-pencil"></i>
                  </b-button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </b-card>
    </template>

    <!-- ============ CONTRAT EN ÉDITION ============ -->
    <template v-if="currentContract">

      <!-- HEADER PROCESSUS -->
      <b-card class="shadow-sm mb-3 p-2 border-primary border-2 bg-light-primary">
        <div class="row align-items-center">
          <div class="col">
            <h5 class="fw-bold text-primary mb-1" style="font-size: 1rem;">
              <i class="ti ti-flow-switch me-2"></i>
              {{ currentContract.process.code }} — {{ currentContract.process.name }}
            </h5>
            <p class="text-muted mb-0" style="font-size: 0.75rem;">
              <i class="ti ti-quote me-1"></i>
              {{ currentContract.purpose || 'Aucune finalité définie' }}
            </p>
          </div>
          <div class="col-auto text-end" style="font-size: 0.8rem;">
            <div class="small text-muted mb-1">Propriétaire</div>
            <strong>{{ currentContract.owner || '—' }}</strong>
          </div>
        </div>
      </b-card>

      <!-- ONGLETS -->
      <b-card class="shadow-sm mb-3">
        <b-tabs>

          <!-- TAB 1: CONTRAT -->
          <b-tab title="📋 Contrat" active>
            <div class="p-2">

              <!-- ========== INFOS GÉNÉRALES ========== -->
              <div class="mb-3">
                <h6 class="fw-bold mb-2" style="font-size: 0.85rem;">
                  <i class="ti ti-info-circle text-primary me-2"></i>
                  Informations Générales
                </h6>

                <b-row class="g-1">
                  <b-col md="6">
                    <label class="fw-bold small text-muted text-uppercase" style="font-size: 0.65rem;">Propriétaire</label>
                    <input v-model="currentContract.owner" type="text" class="form-control form-control-sm" 
                           placeholder="Nom du propriétaire" style="font-size: 0.8rem;" />
                  </b-col>

                  <b-col md="6">
                    <label class="fw-bold small text-muted text-uppercase" style="font-size: 0.65rem;">Finalité</label>
                    <textarea v-model="currentContract.purpose" class="form-control form-control-sm" 
                              rows="1" placeholder="Objectif du processus" style="font-size: 0.8rem;"></textarea>
                  </b-col>
                </b-row>
              </div>

              <!-- ========== DONNÉES DE SORTIE AVEC NOTIFICATIONS ========== -->
              <div class="mb-3">
                <h6 class="fw-bold mb-2" style="font-size: 0.85rem;">
                  <i class="ti ti-arrow-up-circle text-info me-2"></i>
                  Données de Sortie
                </h6>

                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                  <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 0.78rem;">
                    <thead class="table-light sticky-top">
                      <tr style="height: 30px;">
                        <th style="width: 35px; padding: 0.3rem;" class="text-center">#</th>
                        <th style="width: 140px; padding: 0.3rem;">Sortie</th>
                        <th style="width: 120px; padding: 0.3rem;">
                          <i class="ti ti-briefcase me-1"></i> Fonction
                        </th>
                        <th style="width: 110px; padding: 0.3rem;">
                          <i class="ti ti-user me-1"></i> Utilisateur
                        </th>
                        <th style="padding: 0.3rem;">Attentes</th>
                        <th style="width: 90px; padding: 0.3rem;">Document</th>
                        <th style="width: 70px; padding: 0.3rem;" class="text-center">
                          <i class="ti ti-send me-1"></i> Notifier
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(item, idx) in currentContract.outputs" :key="item.id" 
                          :class="{ 'table-light': idx % 2 === 0 }" style="height: 32px;">
                        <td style="padding: 0.3rem;" class="text-center fw-bold text-muted">{{ idx + 1 }}</td>
                        
                        <td style="padding: 0.3rem;">
                          <span class="fw-bold text-dark" style="font-size: 0.78rem;">{{ item.label }}</span>
                        </td>

                        <!-- FONCTION -->
                        <td style="padding: 0.3rem;">
                          <select v-model="item.actor_function_id"
                                  @change="updateActorFromFunction(idx)"
                                  class="form-select form-select-sm"
                                  style="font-size: 0.75rem; padding: 0.3rem;">
                            <option value="">— Pas de fonction</option>
                            <option v-for="fn in currentContract.functions" :key="fn.id" :value="fn.id">
                              {{ fn.name }}
                            </option>
                          </select>
                        </td>

                        <!-- UTILISATEUR AUTO-REMPLI -->
                        <td style="padding: 0.3rem;">
                          <div v-if="item.user_name" class="d-flex align-items-center gap-1">
                            <span class="badge bg-primary-soft text-primary" style="font-size: 0.65rem; padding: 0.2rem 0.4rem;">
                              <i class="ti ti-user"></i> {{ item.user_name }}
                            </span>
                          </div>
                          <small v-else class="text-muted" style="font-size: 0.7rem;">
                            —
                          </small>
                        </td>

                        <!-- ATTENTES -->
                        <td style="padding: 0.3rem;">
                          <input v-model="item.expectations" type="text" 
                                 class="form-control form-control-sm" 
                                 placeholder="Détail attentes" 
                                 style="font-size: 0.75rem; padding: 0.3rem;" />
                        </td>

                        <!-- DOCUMENT -->
                        <td style="padding: 0.3rem;" class="text-center">
                          <div class="btn-group btn-group-sm" role="group">
                            <label class="btn btn-outline-primary" title="Uploader" style="padding: 0.25rem 0.4rem; font-size: 0.75rem;">
                              <i class="ti ti-upload"></i>
                              <input type="file" style="display:none" 
                                     @change="uploadFile($event, idx)" />
                            </label>

                            <button v-if="item.document_path" 
                                    @click="downloadFile(idx)"
                                    class="btn btn-outline-success" title="Télécharger"
                                    style="padding: 0.25rem 0.4rem; font-size: 0.75rem;">
                              <i class="ti ti-download"></i>
                            </button>

                            <button v-if="item.document_path"
                                    @click="deleteFile(idx)"
                                    class="btn btn-outline-danger" title="Supprimer"
                                    style="padding: 0.25rem 0.4rem; font-size: 0.75rem;">
                              <i class="ti ti-trash"></i>
                            </button>
                          </div>

                          <small v-if="item.file_name" class="d-block text-muted mt-1" style="font-size: 0.65rem;">
                            <i class="ti ti-paperclip me-1"></i> {{ item.file_name }}
                          </small>
                        </td>

                        <!-- NOTIFIER -->
                        <td style="padding: 0.3rem;" class="text-center">
                          <button 
                            v-if="item.actor_function_id && item.user_name"
                            @click="sendNotif(idx)"
                            class="btn btn-sm btn-outline-primary"
                            title="Envoyer notification"
                            style="padding: 0.25rem 0.4rem; font-size: 0.75rem;">
                            <i class="ti ti-send"></i>
                          </button>
                          <span v-else class="text-muted small">—</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- ========== INDICATEURS ========== -->
              <div class="mb-3">
                <h6 class="fw-bold mb-2" style="font-size: 0.85rem;">
                  <i class="ti ti-chart-bar text-info me-2"></i>
                  Indicateurs
                </h6>

                <b-row class="g-1">
                  <b-col md="6">
                    <label class="fw-bold small text-muted text-uppercase" style="font-size: 0.65rem;">Activité</label>
                    <textarea v-model="indicatorsActivity" class="form-control form-control-sm" rows="2"
                              placeholder="1. Délai moyen&#10;2. Volume..." style="font-size: 0.8rem;"></textarea>
                  </b-col>

                  <b-col md="6">
                    <label class="fw-bold small text-muted text-uppercase" style="font-size: 0.65rem;">Performance</label>
                    <textarea v-model="indicatorsPerf" class="form-control form-control-sm" rows="2"
                              placeholder="1. Taux de satisfaction&#10;2. Coûts..." style="font-size: 0.8rem;"></textarea>
                  </b-col>
                </b-row>
              </div>

              <!-- ========== ACTIONS ========== -->
              <div class="d-flex justify-content-between gap-1 flex-wrap">
                <b-button variant="secondary" @click="closeContract" class="px-2" size="sm" style="font-size: 0.8rem;">
                  <i class="ti ti-x me-1"></i> Fermer
                </b-button>

                <div class="d-flex gap-1 flex-wrap">
                  <b-button variant="outline-info" @click="exportExcel" :disabled="saving" size="sm" style="font-size: 0.8rem;">
                    <i class="ti ti-file-spreadsheet me-1"></i> Excel
                  </b-button>

                  <b-button variant="outline-danger" @click="exportPdf" :disabled="saving" size="sm" style="font-size: 0.8rem;">
                    <i class="ti ti-file-pdf me-1"></i> PDF
                  </b-button>

                  <b-button variant="success" @click="printContract" size="sm" style="font-size: 0.8rem;">
                    <i class="ti ti-printer me-1"></i> Imprimer
                  </b-button>

                  <b-button variant="primary" @click="saveContract" :disabled="saving" size="sm" style="font-size: 0.8rem;">
                    <i class="ti ti-device-floppy me-1"></i>
                    {{ saving ? 'Enr...' : 'Enregistrer' }}
                  </b-button>
                </div>
              </div>
            </div>
          </b-tab>

          <!-- TAB 2: HISTORIQUE -->
          <b-tab title="📜 Historique">
            <div class="p-2">
              <div v-if="histories.length > 0">
                <div v-for="(h, idx) in histories" :key="idx" class="d-flex gap-2 mb-2 pb-2 border-bottom" style="font-size: 0.8rem;">
                  <div class="flex-shrink-0">
                    <span :class="['avatar-sm', getActionColor(h.action)]" 
                          style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                      <i :class="getActionIcon(h.action)" style="font-size: 0.9rem;"></i>
                    </span>
                  </div>

                  <div class="flex-grow-1">
                    <h6 class="fw-bold mb-0" style="font-size: 0.8rem;">{{ h.action_label }}</h6>
                    <small class="text-muted d-block mb-1" style="font-size: 0.7rem;">
                      <i class="ti ti-user me-1"></i> {{ h.user_name }} • {{ h.created_at }}
                    </small>
                    <p v-if="h.description" class="mb-0 small" style="font-size: 0.75rem;">{{ h.description }}</p>
                    <p v-if="h.file_name" class="mb-0 small text-info" style="font-size: 0.75rem;">
                      <i class="ti ti-paperclip me-1"></i> {{ h.file_name }}
                    </p>
                  </div>
                </div>
              </div>

              <div v-else class="alert alert-info text-center" style="padding: 1rem 0.5rem; font-size: 0.8rem;">
                <i class="ti ti-info-circle me-2"></i>
                Aucun historique
              </div>
            </div>
          </b-tab>

        </b-tabs>
      </b-card>

    </template>

    <!-- MESSAGE VIDE -->
    <div v-else class="alert alert-info text-center p-3 rounded-lg" style="font-size: 0.85rem;">
      <i class="ti ti-info-circle display-5 text-info mb-2 d-block"></i>
      <h5 class="fw-bold" style="font-size: 1rem;">Aucun contrat sélectionné</h5>
      <p class="text-muted mb-0">Sélectionnez un processus pour afficher ou créer son contrat d'interfaces.</p>
    </div>

  </VerticalLayout>
</template>

<script setup>
import { Head } from "@inertiajs/vue3"
import VerticalLayout from "@/layoutsparam/VerticalLayout.vue"
import { ref, onMounted } from "vue"
import axios from "axios"

const props = defineProps({
  user: Object,
  link: Object,
  processes: Array,
  contracts: Array,
})

const API_BASE = (() => {
  const path = window.location.pathname
  if (path.includes('/m/process.core')) {
    return '/m/process.core/process/contracts'
  } else if (path.includes('/admin')) {
    return '/admin/process/contracts'
  } else {
    return '/process/contracts'
  }
})()

// ========== STATE ==========
const selectedProcess = ref("")
const currentContract = ref(null)
const contractId = ref(null)
const saving = ref(false)
const histories = ref([])
const indicatorsActivity = ref("")
const indicatorsPerf = ref("")
const functionUserMap = ref({})

// ========== NOTIFICATIONS ==========
const showNotifs = ref(false)
const notifsList = ref([])
const unreadCount = ref(0)

onMounted(() => {
  loadNotifs()
  setInterval(loadNotifs, 30000)
})

// Charger notifications
async function loadNotifs() {
  try {
    const res = await axios.get(`${API_BASE}/notifications`)
    notifsList.value = res.data.notifications || []
    unreadCount.value = res.data.unread_count || 0
  } catch (e) {
    console.error('Erreur notifs:', e)
  }
}

// Marquer comme lu
async function markNotifRead(id) {
  try {
    await axios.post(`${API_BASE}/notifications/${id}/read`)
    loadNotifs()
  } catch (e) {
    console.error('Erreur:', e)
  }
}

// Marquer tout comme lu
async function markAllNotifsRead() {
  try {
    await axios.post(`${API_BASE}/notifications/read-all`)
    loadNotifs()
  } catch (e) {
    console.error('Erreur:', e)
  }
}

// Formater date
function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('fr-FR', {
    year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit'
  })
}

// ========== CONTRATS ==========

async function loadContract() {
  if (!selectedProcess.value) {
    currentContract.value = null
    return
  }

  try {
    saving.value = true
    const res = await axios.get(`${API_BASE}/load`, {
      params: { process_id: selectedProcess.value }
    })

    contractId.value = res.data.contract_id
    currentContract.value = res.data.data

    indicatorsActivity.value = (res.data.data.activity_indicators || []).join("\n")
    indicatorsPerf.value = (res.data.data.performance_indicators || []).join("\n")

    loadHistory()

    if (currentContract.value.functions && currentContract.value.functions.length > 0) {
      loadFunctionUsers()
    }
  } catch (e) {
    console.error('Erreur loadContract:', e.message)
    alert("Erreur: " + e.message)
  } finally {
    saving.value = false
  }
}

async function loadFunctionUsers() {
  try {
    const functionIds = currentContract.value.functions.map(f => f.id)
    if (functionIds.length === 0) return

    const res = await axios.post(`${API_BASE}/function-users`, {
      function_ids: functionIds
    })

    if (res.data.users_map) {
      functionUserMap.value = res.data.users_map
    }
  } catch (e) {
    console.error('Erreur loadFunctionUsers:', e.message)
  }
}

function updateActorFromFunction(outputIdx) {
  const output = currentContract.value.outputs[outputIdx]
  
  if (!output.actor_function_id) {
    output.user_name = ''
    output.actor = ''
    return
  }

  const userInfo = functionUserMap.value[output.actor_function_id]
  if (userInfo) {
    output.user_name = userInfo.name
    output.actor = userInfo.name
  }
}

async function sendNotif(idx) {
  const o = currentContract.value.outputs[idx]
  
  if (!o.actor_function_id || !o.user_name) {
    alert('❌ Aucun utilisateur')
    return
  }

  if (!confirm(`Notifier ${o.user_name} ?`)) return

  try {
    const u = functionUserMap.value[o.actor_function_id]
    const fn = currentContract.value.functions.find(f => f.id === o.actor_function_id)?.name

    const res = await axios.post(`${API_BASE}/notify`, {
      contract_id: contractId.value,
      output_id: o.id,
      output_label: o.label,
      function_id: o.actor_function_id,
      function_name: fn,
      user_id: u.id,
      user_name: u.name,
      user_email: u.email,
      expectations: o.expectations,
      process_code: currentContract.value.process.code,
      process_name: currentContract.value.process.name
    })

    if (res.data.success) {
      alert(`✅ Notification envoyée à ${o.user_name}`)
      loadNotifs()
    }
  } catch (e) {
    alert('❌ Erreur: ' + e.message)
  }
}

async function uploadFile(event, outputIdx) {
  const file = event.target.files?.[0]
  if (!file) return

  const form = new FormData()
  form.append('contract_id', contractId.value)
  form.append('output_id', currentContract.value.outputs[outputIdx].id)
  form.append('file', file)

  try {
    const res = await axios.post(`${API_BASE}/upload`, form, {
      headers: {'Content-Type': 'multipart/form-data'}
    })
    currentContract.value.outputs[outputIdx].document_path = res.data.path
    currentContract.value.outputs[outputIdx].file_name = file.name
    alert("✅ Fichier uploadé")
  } catch (e) {
    alert("Erreur: " + e.message)
  }
}

async function downloadFile(outputIdx) {
  try {
    const res = await axios.get(`${API_BASE}/download`, {
      params: {
        contract_id: contractId.value,
        output_id: currentContract.value.outputs[outputIdx].id
      },
      responseType: 'blob'
    })
    const url = URL.createObjectURL(res.data)
    const a = document.createElement('a')
    a.href = url
    a.download = currentContract.value.outputs[outputIdx].file_name
    a.click()
  } catch (e) {
    alert("Erreur: " + e.message)
  }
}

async function deleteFile(outputIdx) {
  if (!confirm("Supprimer ?")) return
  
  try {
    await axios.post(`${API_BASE}/file/delete`, {
      contract_id: contractId.value,
      output_id: currentContract.value.outputs[outputIdx].id
    })
    currentContract.value.outputs[outputIdx].document_path = null
    currentContract.value.outputs[outputIdx].file_name = null
    alert("✅ Supprimé")
  } catch (e) {
    alert("Erreur: " + e.message)
  }
}

async function saveContract() {
  if (!contractId.value) return

  saving.value = true

  try {
    await axios.post(`${API_BASE}/save`, {
      contract_id: contractId.value,
      owner: currentContract.value.owner,
      purpose: currentContract.value.purpose,
      outputs: currentContract.value.outputs,
      activity_indicators: indicatorsActivity.value.split('\n').filter(i => i.trim()),
      performance_indicators: indicatorsPerf.value.split('\n').filter(i => i.trim())
    })

    alert("✅ Enregistré")
    loadHistory()
  } catch (e) {
    alert("Erreur: " + e.message)
  } finally {
    saving.value = false
  }
}

async function exportExcel() {
  try {
    saving.value = true
    const response = await axios.get(`${API_BASE}/export-excel`, {
      params: { contract_id: contractId.value },
      responseType: 'blob'
    })

    const url = URL.createObjectURL(response.data)
    const a = document.createElement('a')
    a.href = url
    a.download = `contrat_${contractId.value}.xlsx`
    a.click()

    alert("✅ Excel généré")
  } catch (e) {
    alert("Erreur: " + e.message)
  } finally {
    saving.value = false
  }
}

async function exportPdf() {
  try {
    saving.value = true
    const response = await axios.get(`${API_BASE}/export-pdf`, {
      params: { contract_id: contractId.value },
      responseType: 'blob'
    })

    const url = URL.createObjectURL(response.data)
    const a = document.createElement('a')
    a.href = url
    a.download = `contrat_${contractId.value}.pdf`
    a.click()

    alert("✅ PDF généré")
  } catch (e) {
    alert("Erreur: " + e.message)
  } finally {
    saving.value = false
  }
}

async function loadHistory() {
  try {
    const res = await axios.get(`${API_BASE}/history`, {
      params: { contract_id: contractId.value }
    })
    histories.value = res.data.histories
  } catch (e) {
    console.error('Erreur:', e)
  }
}

function printContract() {
  window.print()
}

function closeContract() {
  currentContract.value = null
  contractId.value = null
  selectedProcess.value = ""
}

function viewContract(contract) {
  selectedProcess.value = contract.process_id
  loadContractById(contract.id)
}

async function loadContractById(id) {
  contractId.value = id
  const contract = props.contracts.find(c => c.id === id)
  if (contract) {
    selectedProcess.value = contract.process_id
    await loadContract()
  }
}

function getActionColor(action) {
  const colors = {
    created: 'bg-success-soft text-success',
    updated_outputs: 'bg-primary-soft text-primary',
    updated_indicators: 'bg-info-soft text-info',
    file_uploaded: 'bg-warning-soft text-warning',
    file_deleted: 'bg-danger-soft text-danger',
    archived: 'bg-secondary-soft text-secondary',
    restored: 'bg-success-soft text-success'
  }
  return colors[action] || 'bg-secondary-soft text-secondary'
}

function getActionIcon(action) {
  const icons = {
    created: 'ti ti-plus',
    updated_outputs: 'ti ti-pencil',
    updated_indicators: 'ti ti-chart-bar',
    file_uploaded: 'ti ti-upload',
    file_deleted: 'ti ti-trash',
    archived: 'ti ti-box',
    restored: 'ti ti-arrow-back'
  }
  return icons[action] || 'ti ti-info-circle'
}
</script>

<style scoped>
.bg-gradient-primary {
  background: linear-gradient(135deg, #2E75B6 0%, #1F4E78 100%);
}

.bg-light-primary {
  background-color: #E7F1F8;
}

.bg-primary-soft {
  background-color: #D6E7F5 !important;
}

.bg-success-soft {
  background-color: #D4EDDA !important;
}

.bg-danger-soft {
  background-color: #F8D7DA !important;
}

.bg-info-soft {
  background-color: #D1ECF1 !important;
}

.bg-info {
  background-color: #e7f3ff !important;
}

.bg-warning-soft {
  background-color: #FFF3CD !important;
}

.bg-secondary-soft {
  background-color: #E2E3E5 !important;
}

.cursor-pointer {
  cursor: pointer;
}

.notification-dropdown {
  border: 1px solid #dee2e6;
}

@media print {
  .btn, .input-group-text, select, input, textarea {
    border: none !important;
    background: transparent !important;
    color: #000 !important;
  }

  .btn { display: none; }
  input, textarea { border-bottom: 1px solid #000 !important; }
}
</style>