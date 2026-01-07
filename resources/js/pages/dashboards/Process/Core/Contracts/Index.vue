<template>
  <VerticalLayout>
    <Head title="Contrats d'Interfaces" />

    <!-- HEADER SIMPLE -->
    <div class="p-3 mb-3 rounded bg-primary text-white">
      <h3 class="fw-bold mb-0"><i class="ti ti-file-text me-2"></i>Contrats d'Interfaces</h3>
    </div>

    <!-- SÉLECTION SIMPLE -->
    <b-card class="shadow-sm mb-3">
      <b-row class="g-2">
        <b-col md="4">
          <label class="fw-bold small d-block mb-1">Entité</label>
          <input class="form-control form-control-sm" :value="link?.entity_name" disabled />
        </b-col>
        <b-col md="4">
          <label class="fw-bold small d-block mb-1">Fonction</label>
          <input class="form-control form-control-sm" :value="link?.function_name" disabled />
        </b-col>
        <b-col md="4">
          <label class="fw-bold small d-block mb-1">Processus</label>
          <select v-model="selectedProcess" @change="loadContract" class="form-select form-select-sm">
            <option value="">— Choisir —</option>
            <option v-for="p in processes" :key="p.id" :value="p.id">{{ p.code }} - {{ p.name }}</option>
          </select>
        </b-col>
      </b-row>
    </b-card>

    <!-- CONTRAT EN ÉDITION -->
    <template v-if="currentContract">

      <!-- INFO PROCESSUS -->
      <b-card class="shadow-sm mb-3 border-primary border-2 p-3">
        <h5 class="fw-bold text-primary mb-1">{{ currentContract.process.code }} — {{ currentContract.process.name }}</h5>
        <p class="text-muted small mb-2">{{ currentContract.purpose || 'Pas de finalité' }}</p>
        <small><strong>Propriétaire:</strong> {{ currentContract.owner || '—' }}</small>
      </b-card>

      <!-- ONGLETS -->
      <b-card class="shadow-sm">
        <b-tabs nav-class="bg-light">

          <!-- TAB: DONNÉES & INDICATEURS -->
          <b-tab title="📋 Contrat" active>
            <div class="p-3">

              <!-- Infos Générales -->
              <div class="mb-4 pb-3 border-bottom">
                <h6 class="fw-bold mb-2">Propriétaire & Finalité</h6>
                <b-row class="g-2">
                  <b-col md="6">
                    <input v-model="currentContract.owner" class="form-control form-control-sm" placeholder="Propriétaire" />
                  </b-col>
                  <b-col md="6">
                    <textarea v-model="currentContract.purpose" class="form-control form-control-sm" rows="1" placeholder="Finalité"></textarea>
                  </b-col>
                </b-row>
              </div>

              <!-- TABLEAU SORTIE -->
              <div class="mb-4">
                <h6 class="fw-bold mb-2">Données de Sortie</h6>
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem;">
                  <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light sticky-top">
                      <tr style="font-size: 0.8rem;">
                        <th style="width: 30px;">#</th>
                        <th style="width: 120px;">Sortie</th>
                        <th style="width: 100px;">Fonction</th>
                        <th style="width: 90px;">Utilisateur</th>
                        <th style="width: 110px;">Attentes</th>
                        <th style="width: 180px; background: #fff3cd;">🤖 Suggestions IA</th>
                        <th style="width: 60px;">Doc</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(item, idx) in currentContract.outputs" :key="item.id" :class="idx % 2 === 0 ? 'table-light' : ''">
                        <td class="fw-bold">{{ idx + 1 }}</td>
                        <td><strong>{{ item.label }}</strong></td>
                        
                        <!-- FONCTION (Trigger de la génération) -->
                        <td>
                          <select v-model="item.actor_function_id" @change="updateActorFromFunction(idx)" class="form-select form-select-sm" style="font-size: 0.7rem;">
                            <option value="">—</option>
                            <option v-for="fn in currentContract.functions" :key="fn.id" :value="fn.id">{{ fn.name }}</option>
                          </select>
                        </td>
                        
                        <!-- UTILISATEUR (Auto-rempli SEULEMENT si fonction choisie) -->
                        <td>
                          <span v-if="item.user_name" class="badge bg-primary" style="font-size: 0.65rem;">{{ item.user_name }}</span>
                          <small v-else class="text-muted">—</small>
                        </td>
                        
                        <!-- ATTENTES (Spécifiques à la fonction) -->
                        <td>
                          <textarea v-model="item.expectations" class="form-control form-control-sm" rows="1" style="font-size: 0.7rem;"></textarea>
                        </td>

                        <!-- SUGGESTIONS IA (Basées sur fonction choisie) -->
                        <td style="background: #fff3cd;">
                          <!-- Si suggestions disponibles -->
                          <div v-if="item.aisu?.expectations?.length > 0" style="font-size: 0.65rem; max-height: 200px; overflow-y: auto;">
                            <div class="mb-2">
                              <strong class="d-block">Attentes:</strong>
                              <div v-for="(e, i) in item.aisu.expectations.slice(0, 2)" :key="i" class="small">✓ {{ e }}</div>
                            </div>
                            <div v-if="item.aisu.activity_indicators?.length > 0" class="mb-2 p-1 bg-primary bg-opacity-10 rounded">
                              <strong class="d-block">📊 Activité:</strong>
                              <div v-for="(ind, i) in item.aisu.activity_indicators.slice(0, 2)" :key="'a-'+i" class="small">✓ {{ ind }}</div>
                            </div>
                            <div v-if="item.aisu.performance_indicators?.length > 0" class="mb-2 p-1 bg-success bg-opacity-10 rounded">
                              <strong class="d-block">🎯 Perf:</strong>
                              <div v-for="(ind, i) in item.aisu.performance_indicators.slice(0, 2)" :key="'p-'+i" class="small">✓ {{ ind }}</div>
                            </div>
                            <div class="d-flex gap-1">
                              <button @click="applySuggestions(item)" class="btn btn-sm btn-warning flex-grow-1" style="font-size: 0.6rem;">Appliquer</button>
                              <button @click="clearSuggestions(item)" class="btn btn-sm btn-outline-secondary" style="font-size: 0.6rem;">Reset</button>
                            </div>
                          </div>

                          <!-- En cours de génération -->
                          <div v-else-if="loadingSuggestions[item.id]" class="text-center p-1">
                            <b-spinner small variant="warning"></b-spinner>
                            <small class="d-block">Génération...</small>
                          </div>

                          <!-- Bouton générer (SEULEMENT si fonction choisie) -->
                          <button v-else-if="item.actor_function_id" @click="generateAISuggestions(item, idx)" class="btn btn-sm btn-outline-warning w-100" style="font-size: 0.65rem;">
                            <i class="ti ti-robot"></i> Générer
                          </button>

                          <!-- Message si pas de fonction -->
                          <small v-else class="text-muted d-block text-center">Sélect. fonction</small>
                        </td>

                        <!-- Document -->
                        <td class="text-center">
                          <div class="btn-group btn-group-sm">
                            <label class="btn btn-outline-primary" style="cursor: pointer; font-size: 0.65rem; padding: 0.25rem;">
                              <i class="ti ti-upload"></i>
                              <input type="file" style="display:none" @change="uploadFile($event, idx)" />
                            </label>
                            <button v-if="item.document_path" @click="downloadFile(idx)" class="btn btn-outline-success" style="font-size: 0.65rem; padding: 0.25rem;">
                              <i class="ti ti-download"></i>
                            </button>
                            <button v-if="item.document_path" @click="deleteFile(idx)" class="btn btn-outline-danger" style="font-size: 0.65rem; padding: 0.25rem;">
                              <i class="ti ti-trash"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- INDICATEURS SIMPLES -->
              <div class="mb-4">
                <h6 class="fw-bold mb-3">Indicateurs</h6>
                <b-row class="g-3">
                  <!-- Activité -->
                  <b-col lg="6">
                    <div class="p-3 border rounded" style="background: #f0f7ff;">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0">📊 Activité</h6>
                        <span class="badge bg-primary">{{ activityList.length }}</span>
                      </div>
                      <div class="mb-2">
                        <div class="input-group input-group-sm">
                          <input v-model="newActivity" type="text" class="form-control" placeholder="Ajouter..." @keyup.enter="addActivity" />
                          <button @click="addActivity" class="btn btn-primary"><i class="ti ti-plus"></i></button>
                        </div>
                      </div>
                      <div v-if="activityList.length > 0" class="list-group list-group-sm">
                        <div v-for="(item, idx) in activityList" :key="idx" class="list-group-item d-flex justify-content-between align-items-center">
                          <small><strong>[{{ idx + 1 }}]</strong> {{ item }}</small>
                          <button @click="removeActivity(idx)" class="btn btn-sm btn-outline-danger" style="font-size: 0.65rem;">
                            <i class="ti ti-trash"></i>
                          </button>
                        </div>
                      </div>
                      <div v-else class="alert alert-info mb-0" style="font-size: 0.75rem;">Aucun indicateur</div>
                    </div>
                  </b-col>

                  <!-- Performance -->
                  <b-col lg="6">
                    <div class="p-3 border rounded" style="background: #f0fff4;">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0">🎯 Performance</h6>
                        <span class="badge bg-success">{{ perfList.length }}</span>
                      </div>
                      <div class="mb-2">
                        <div class="input-group input-group-sm">
                          <input v-model="newPerf" type="text" class="form-control" placeholder="Ajouter..." @keyup.enter="addPerf" />
                          <button @click="addPerf" class="btn btn-success"><i class="ti ti-plus"></i></button>
                        </div>
                      </div>
                      <div v-if="perfList.length > 0" class="list-group list-group-sm">
                        <div v-for="(item, idx) in perfList" :key="idx" class="list-group-item d-flex justify-content-between align-items-center">
                          <small><strong>[{{ idx + 1 }}]</strong> {{ item }}</small>
                          <button @click="removePerf(idx)" class="btn btn-sm btn-outline-danger" style="font-size: 0.65rem;">
                            <i class="ti ti-trash"></i>
                          </button>
                        </div>
                      </div>
                      <div v-else class="alert alert-info mb-0" style="font-size: 0.75rem;">Aucun indicateur</div>
                    </div>
                  </b-col>
                </b-row>
              </div>

              <!-- STATS -->
              <div class="mb-4">
                <b-row class="g-2">
                  <b-col sm="6" lg="3">
                    <div class="text-center p-3 bg-light rounded">
                      <div style="font-size: 1.5rem; font-weight: bold; color: #0d6efd;">{{ activityList.length }}</div>
                      <small class="text-muted">Activité</small>
                    </div>
                  </b-col>
                  <b-col sm="6" lg="3">
                    <div class="text-center p-3 bg-light rounded">
                      <div style="font-size: 1.5rem; font-weight: bold; color: #198754;">{{ perfList.length }}</div>
                      <small class="text-muted">Performance</small>
                    </div>
                  </b-col>
                  <b-col sm="6" lg="3">
                    <div class="text-center p-3 bg-light rounded">
                      <div style="font-size: 1.5rem; font-weight: bold; color: #ffc107;">{{ activityList.length + perfList.length }}</div>
                      <small class="text-muted">Total</small>
                    </div>
                  </b-col>
                  <b-col sm="6" lg="3">
                    <div class="text-center p-3 bg-light rounded">
                      <div style="font-size: 1.5rem; font-weight: bold; color: #dc3545;">{{ currentContract.outputs.length }}</div>
                      <small class="text-muted">Sorties</small>
                    </div>
                  </b-col>
                </b-row>
              </div>

              <!-- BOUTONS -->
              <div class="d-flex justify-content-between gap-2 flex-wrap">
                <b-button variant="secondary" @click="closeContract" size="sm">
                  <i class="ti ti-x me-1"></i> Fermer
                </b-button>
                <div class="d-flex gap-2">
                  <b-button variant="outline-info" @click="exportExcel" size="sm">
                    <i class="ti ti-file-spreadsheet me-1"></i> Excel
                  </b-button>
                  <b-button variant="success" @click="printContract" size="sm">
                    <i class="ti ti-printer me-1"></i> Imprimer
                  </b-button>
                  <b-button variant="primary" @click="saveContract" :disabled="saving" size="sm">
                    <i class="ti ti-device-floppy me-1"></i> {{ saving ? 'Enr...' : 'Enregistrer' }}
                  </b-button>
                </div>
              </div>
            </div>
          </b-tab>

          <!-- TAB: HISTORIQUE -->
          <b-tab title="📜 Historique">
            <div class="p-3">
              <div v-if="histories.length > 0">
                <div v-for="h in histories" :key="h.id" class="d-flex gap-2 mb-2 pb-2 border-bottom" style="font-size: 0.8rem;">
                  <div class="p-2 rounded bg-light" style="min-width: 35px; text-align: center;">
                    <i :class="getActionIcon(h.action)"></i>
                  </div>
                  <div class="flex-grow-1">
                    <strong class="d-block">{{ h.action_label }}</strong>
                    <small class="text-muted d-block">{{ h.user_name }} • {{ h.created_at }}</small>
                  </div>
                </div>
              </div>
              <div v-else class="alert alert-info mb-0">Aucun historique</div>
            </div>
          </b-tab>

        </b-tabs>
      </b-card>

    </template>

    <!-- VIDE -->
    <div v-else class="alert alert-info text-center p-5">
      <i class="ti ti-inbox display-4 mb-3 d-block" style="opacity: 0.5;"></i>
      <h5>Aucun contrat</h5>
      <p class="text-muted">Sélectionnez un processus</p>
    </div>

  </VerticalLayout>
</template>

<script setup>
import { Head } from "@inertiajs/vue3"
import VerticalLayout from "@/layouts/VerticalLayout.vue"
import { ref, onMounted, computed } from "vue"

const props = defineProps({ user: Object, link: Object, processes: Array, contracts: Array })

const selectedProcess = ref("")
const currentContract = ref(null)
const contractId = ref(null)
const saving = ref(false)
const functionUserMap = ref({})
const loadingSuggestions = ref({})
const histories = ref([])
const activityText = ref("")
const perfText = ref("")
const newActivity = ref("")
const newPerf = ref("")

const activityList = computed(() => activityText.value.split('\n').map(i => i.trim()).filter(i => i.length > 0))
const perfList = computed(() => perfText.value.split('\n').map(i => i.trim()).filter(i => i.length > 0))

function addActivity() {
  if (!newActivity.value.trim()) return
  activityText.value = activityText.value.trim() ? activityText.value + '\n' + newActivity.value.trim() : newActivity.value.trim()
  newActivity.value = ""
}

function removeActivity(idx) {
  const items = activityList.value
  items.splice(idx, 1)
  activityText.value = items.join('\n')
}

function addPerf() {
  if (!newPerf.value.trim()) return
  perfText.value = perfText.value.trim() ? perfText.value + '\n' + newPerf.value.trim() : newPerf.value.trim()
  newPerf.value = ""
}

function removePerf(idx) {
  const items = perfList.value
  items.splice(idx, 1)
  perfText.value = items.join('\n')
}

async function secureRequest(url, data = {}, method = 'POST') {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
  if (!csrfToken) throw new Error('CSRF token manquant')
  const options = {
    method,
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
  }
  if (method !== 'GET') options.body = JSON.stringify(data)
  const response = await fetch(url, options)
  if (!response.ok) throw new Error(`HTTP ${response.status}`)
  return await response.json()
}

async function loadContract() {
  if (!selectedProcess.value) { currentContract.value = null; return }
  try {
    saving.value = true
    const response = await fetch(route('process.core.process.contracts.load') + '?process_id=' + selectedProcess.value, {
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'Accept': 'application/json' }
    })
    if (!response.ok) throw new Error(`HTTP ${response.status}`)
    const result = await response.json()
    if (result.success) {
      contractId.value = result.contract_id
      currentContract.value = result.data
      activityText.value = (result.data.activity_indicators || []).join("\n")
      perfText.value = (result.data.performance_indicators || []).join("\n")
      loadHistory()
      if (currentContract.value.functions?.length > 0) await loadFunctionUsers()
    }
  } catch (error) {
    alert(`Erreur: ${error.message}`)
  } finally {
    saving.value = false
  }
}

async function loadFunctionUsers() {
  try {
    const functionIds = currentContract.value.functions.map(f => f.id)
    if (functionIds.length === 0) return
    const result = await secureRequest(route('process.core.process.contracts.function-users'), { function_ids: functionIds })
    if (result.success && result.users_map) functionUserMap.value = result.users_map
  } catch (e) {}
}

// 🔑 CLÉMENT: updateActorFromFunction - Déclenche la génération IA SEULEMENT si fonction choisie
function updateActorFromFunction(outputIdx) {
  const output = currentContract.value.outputs[outputIdx]
  
  // Si pas de fonction: réinitialiser tout
  if (!output.actor_function_id) {
    output.user_name = ''
    output.user_id = null
    output.aisu = null
    output.expectations = ''
    return
  }

  // Si fonction choisie: remplir l'utilisateur ET générer les suggestions
  const userInfo = functionUserMap.value[output.actor_function_id]
  if (userInfo) {
    output.user_name = userInfo.name
    output.user_id = userInfo.id
  }

  // Générer les suggestions IA basées sur la fonction
  if (!output.aisu) {
    generateAISuggestions(output, outputIdx)
  }
}

// 🤖 Générer suggestions IA (BASÉES sur fonction choisie)
async function generateAISuggestions(row, outputIdx) {
  if (!row.actor_function_id) return
  
  loadingSuggestions.value[row.id] = true
  try {
    const functionName = currentContract.value.functions.find(f => f.id === row.actor_function_id)?.name

    const result = await secureRequest(route('process.core.process.contracts.ai-suggestions'), {
      function_id: row.actor_function_id,
      function_name: functionName,
      process_id: currentContract.value.process.id,
      process_name: currentContract.value.process.name,
      output_id: row.id,
      output_label: row.label
    })

    if (result.success) {
      // Stocker les suggestions
      row.aisu = result.suggestions
      
      // Auto-remplir les ATTENTES (Expectations) spécifiques à la fonction
      if (result.suggestions?.expectations?.length > 0) {
        row.expectations = result.suggestions.expectations.join('\n')
      }

      // Auto-remplir les INDICATEURS en bas
      if (result.suggestions?.activity_indicators?.length > 0) {
        const newActivityIndicators = result.suggestions.activity_indicators
        activityText.value = activityText.value ? activityText.value + '\n' + newActivityIndicators.join('\n') : newActivityIndicators.join('\n')
      }
      if (result.suggestions?.performance_indicators?.length > 0) {
        const newPerfIndicators = result.suggestions.performance_indicators
        perfText.value = perfText.value ? perfText.value + '\n' + newPerfIndicators.join('\n') : newPerfIndicators.join('\n')
      }
    } else {
      throw new Error(result.error || 'Erreur IA')
    }
  } catch (error) {
    alert(`Erreur IA: ${error.message}`)
  } finally {
    loadingSuggestions.value[row.id] = false
  }
}

// Appliquer les suggestions
function applySuggestions(row) {
  if (!row.aisu) return
  if (row.aisu.expectations?.length > 0) row.expectations = row.aisu.expectations.join('\n')
}

// Réinitialiser les suggestions
function clearSuggestions(row) {
  if (!confirm('Réinitialiser?')) return
  row.aisu = null
}

// Upload fichier
async function uploadFile(event, outputIdx) {
  const file = event.target.files?.[0]
  if (!file) return
  const formData = new FormData()
  formData.append('contract_id', contractId.value)
  formData.append('output_id', currentContract.value.outputs[outputIdx].id)
  formData.append('file', file)
  try {
    const response = await fetch(route('process.core.process.contracts.upload'), {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'Accept': 'application/json' },
      body: formData
    })
    if (!response.ok) throw new Error(`HTTP ${response.status}`)
    const result = await response.json()
    if (result.success) {
      currentContract.value.outputs[outputIdx].document_path = result.path
      currentContract.value.outputs[outputIdx].file_name = result.file_name || file.name
    }
  } catch (e) { alert(`Erreur: ${e.message}`) }
  event.target.value = ''
}

// Download fichier
async function downloadFile(outputIdx) {
  try {
    const response = await fetch(route('process.core.process.contracts.download'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'Accept': 'application/json' },
      body: JSON.stringify({ contract_id: contractId.value, output_id: currentContract.value.outputs[outputIdx].id })
    })
    if (!response.ok) throw new Error(`HTTP ${response.status}`)
    const blob = await response.blob()
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = currentContract.value.outputs[outputIdx].file_name || 'download'
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
  } catch (e) { alert(`Erreur: ${e.message}`) }
}

// Delete fichier
async function deleteFile(outputIdx) {
  if (!confirm("Supprimer?")) return
  try {
    const result = await secureRequest(route('process.core.process.contracts.file.delete'), {
      contract_id: contractId.value, output_id: currentContract.value.outputs[outputIdx].id
    })
    if (result.success) {
      currentContract.value.outputs[outputIdx].document_path = null
      currentContract.value.outputs[outputIdx].file_name = null
    }
  } catch (e) { alert(`Erreur: ${e.message}`) }
}

// Sauvegarder le contrat
async function saveContract() {
  if (!contractId.value) return
  saving.value = true
  try {
    const result = await secureRequest(route('process.core.process.contracts.save'), {
      contract_id: contractId.value,
      owner: currentContract.value.owner,
      purpose: currentContract.value.purpose,
      outputs: currentContract.value.outputs,
      activity_indicators: activityList.value,
      performance_indicators: perfList.value
    })
    if (result.success) { alert("Enregistré!"); loadHistory() }
  } catch (e) { alert(`Erreur: ${e.message}`) } finally { saving.value = false }
}

// Export Excel
function exportExcel() {
  if (!contractId.value) return alert('Aucun contrat')
  window.location.href = route('process.core.process.contracts.export.excel', { contract_id: contractId.value })
}

// Historique
async function loadHistory() {
  try {
    const result = await secureRequest(route('process.core.process.contracts.history'), { contract_id: contractId.value })
    histories.value = result.histories || []
  } catch (e) {}
}

// Print
function printContract() { window.print() }
function closeContract() { currentContract.value = null; contractId.value = null; selectedProcess.value = "" }
function viewContract(c) { selectedProcess.value = c.process_id; loadContractById(c.id) }

async function loadContractById(id) {
  contractId.value = id
  const c = props.contracts.find(x => x.id === id)
  if (c) { selectedProcess.value = c.process_id; await loadContract() }
}

function getActionIcon(action) {
  const icons = { created: 'ti ti-plus', updated_outputs: 'ti ti-pencil', updated_indicators: 'ti ti-chart-bar', file_uploaded: 'ti ti-upload', file_deleted: 'ti ti-trash', notification_sent: 'ti ti-send', archived: 'ti ti-box', restored: 'ti ti-arrow-back' }
  return icons[action] || 'ti ti-info-circle'
}
</script>

<style scoped>
.list-group-item { padding: 0.5rem 0.75rem; }
@media print { .btn { display: none; } }
</style>