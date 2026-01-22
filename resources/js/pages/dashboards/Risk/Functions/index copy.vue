<!-- resources/js/pages/dashboards/Audit/Index.vue - VERSION CORRIGÉE -->
<template>
  <AuditModuleLayoutFinal 
    title="Gestion Risques" 
    :breadcrumbs="['Audit', 'Risques']" 
    :moduleCode="'audit'"
  >
    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <!-- HEADER AVEC BOUTONS -->
    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="fw-bold text-danger mb-0">🔴 Gestion Audits & Risques</h4>
      <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-sm btn-primary px-2" @click="openCreateRisk">➕ Créer Risque</button>
        <button class="btn btn-sm btn-success px-2" @click="modals.matrixGlobal = true">📊 Matrice</button>
        <button class="btn btn-sm btn-warning px-2" @click="modals.frequency = true">📈 Fréquence</button>
        <button class="btn btn-sm btn-info px-2" @click="modals.impact = true">📉 Impact</button>
        <button class="btn btn-sm btn-secondary px-2" @click="modals.types = true">🏷️ Types</button>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <!-- FILTRES -->
    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <div class="card p-2 mb-3 bg-light">
      <h6 class="fw-bold mb-2" style="font-size: 0.85rem; color: #667eea;">📋 Filtres mission</h6>
      
      <div class="row g-2">
        <div class="col-md-4">
          <small class="fw-bold d-block mb-1" style="font-size: 0.7rem;">Projet</small>
          <select v-model="filters.project_code" class="form-select form-select-sm">
            <option value="KEKELI">KEKELI</option>
          </select>
        </div>
        <div class="col-md-4">
          <small class="fw-bold d-block mb-1" style="font-size: 0.7rem;">Entité</small>
          <select v-model="filters.entity" class="form-select form-select-sm">
            <option value="">Tous</option>
            <option v-for="ent in entityOptions" :key="ent" :value="ent">{{ ent }}</option>
          </select>
        </div>
        <div class="col-md-4">
          <small class="fw-bold d-block mb-1" style="font-size: 0.7rem;">Année</small>
          <select v-model.number="filters.year" class="form-select form-select-sm">
            <option :value="2026">2026</option>
            <option :value="2025">2025</option>
          </select>
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <!-- TABLEAU DES ENTITÉS ET PROCESSUS -->
    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <div class="card mb-3" style="overflow-x: auto;">
      <div class="card-header bg-primary text-white py-2">
        <h6 class="fw-bold mb-0" style="font-size: 0.8rem;">📊 Tableau de gestion des risques par entité et processus</h6>
      </div>
      
      <!-- Tableau principal -->
      <table class="table table-sm table-hover mb-0" style="font-size: 0.7rem;">
        <thead class="table-primary text-white">
          <tr>
            <th class="p-1" style="font-size: 0.65rem; width: 15%;">PROCESSUS</th>
            <th class="p-1" style="font-size: 0.65rem; width: 20%;">Activiter</th>
            <th class="p-1" style="font-size: 0.65rem; width: 25%;">RISQUES IDENTIFIÉS</th>
            <th class="p-1" style="font-size: 0.65rem; width: 10%;">IMPACT MOYEN</th>
            <th class="p-1" style="font-size: 0.65rem; width: 10%;">FRÉQ. MOYENNE</th>
            <th class="p-1" style="font-size: 0.65rem; width: 10%;">CRITICITÉ</th>
            <th class="p-1" style="font-size: 0.65rem; width: 10%;">ACTIONS</th>
          </tr>
        </thead>
        <tbody>
          <!-- Boucle sur les entités -->
          <template v-for="entity in filteredEntitiesWithProcesses" :key="entity.name">
            <!-- Ligne de l'entité -->
            <tr class="entity-row align-middle">
              <td class="p-1" :rowspan="entity.processes.length + 1">
                <div class="d-flex align-items-center">
                  <div class="me-2">
                    <button 
                      v-if="entity.collapsed" 
                      @click="toggleEntity(entity.name)" 
                      class="btn btn-sm btn-outline-primary p-0"
                      style="width: 18px; height: 18px; font-size: 0.6rem;"
                    >
                      +
                    </button>
                    <button 
                      v-else 
                      @click="toggleEntity(entity.name)" 
                      class="btn btn-sm btn-outline-primary p-0"
                      style="width: 18px; height: 18px; font-size: 0.6rem;"
                    >
                      -
                    </button>
                  </div>
                  <small><strong>{{ entity.name }}</strong></small>
                </div>
              </td>
            </tr>
            
            <!-- Boucle sur les processus de l'entité (uniquement si non collapsed) -->
            <template v-if="!entity.collapsed">
              <tr 
                v-for="process in entity.processes" 
                :key="process.id"
                :class="['process-row align-middle', process.expanded ? 'table-active' : '']"
              >
                <td class="p-1">
                  <div class="d-flex align-items-center">
                    <div class="me-2">
                      <button 
                        @click="toggleProcess(process.id)" 
                        class="btn btn-sm btn-outline-secondary p-0"
                        style="width: 18px; height: 18px; font-size: 0.6rem;"
                      >
                        {{ process.expanded ? '−' : '+' }}
                      </button>
                    </div>
                    <div>
                      <small><strong>{{ process.code }}</strong></small><br>
                      <small class="text-muted">{{ process.name }}</small>
                    </div>
                  </div>
                </td>
                
                <!-- Cellule des risques identifiés -->
                <td class="p-1">
                  <!-- Liste des risques pour ce processus -->
                  <div v-if="process.risks && process.risks.length > 0">
                    <div 
                      v-for="risk in process.risks" 
                      :key="risk.id"
                      class="risk-item mb-1 p-1 rounded"
                      :style="{ borderLeft: `3px solid ${getRiskTypeColor(risk.type_code)}` }"
                    >
                      <div class="d-flex justify-content-between align-items-start">
                        <div>
                          <small class="fw-bold d-block">{{ risk.code }} - {{ risk.label }}</small>
                          <small class="text-muted">{{ risk.type_label }}</small>
                        </div>
                        <button 
                          @click="deleteRisk(risk)" 
                          class="btn btn-sm btn-link text-danger p-0"
                          style="font-size: 0.6rem;"
                        >
                          🗑️
                        </button>
                      </div>
                    </div>
                  </div>
                  <div v-else class="text-muted">
                    <small>Aucun risque identifié</small>
                  </div>
                  
                  <!-- Bouton pour ajouter un risque à ce processus -->
                  <button 
                    v-if="process.expanded"
                    @click="openAddRiskToProcess(process)"
                    class="btn btn-sm btn-outline-success mt-1 w-100"
                    style="font-size: 0.65rem;"
                  >
                    + Ajouter un risque
                  </button>
                </td>
                
                <!-- Cellules des statistiques -->
                <td class="p-1">
                  <span 
                    v-if="process.risks && process.risks.length > 0"
                    class="badge" 
                    :class="'bg-' + getAverageImpactColor(process)"
                    style="font-size: 0.6rem;"
                  >
                    {{ getAverageImpactLabel(process) }}
                  </span>
                  <span v-else class="badge bg-secondary" style="font-size: 0.6rem;">
                    -
                  </span>
                </td>
                
                <td class="p-1">
                  <span 
                    v-if="process.risks && process.risks.length > 0"
                    class="badge" 
                    :class="'bg-' + getAverageFrequencyColor(process)"
                    style="font-size: 0.6rem;"
                  >
                    {{ getAverageFrequencyLabel(process) }}
                  </span>
                  <span v-else class="badge bg-secondary" style="font-size: 0.6rem;">
                    -
                  </span>
                </td>
                
                <td class="p-1">
                  <span 
                    v-if="process.risks && process.risks.length > 0"
                    class="badge" 
                    :class="'bg-' + getCriticalityColor(process.criticality)"
                    style="font-size: 0.6rem;"
                  >
                    {{ process.criticality || 0 }}
                  </span>
                  <span v-else class="badge bg-secondary" style="font-size: 0.6rem;">
                    0
                  </span>
                </td>
                
                <!-- Cellule des actions -->
                <td class="p-1">
                  <button 
                    @click="openProcessDetail(process)"
                    class="btn btn-sm btn-info px-1 py-0 me-1"
                    style="font-size: 0.6rem;"
                    title="Voir détails"
                  >
                    👁️
                  </button>
                  <button 
                    @click="editProcess(process)"
                    class="btn btn-sm btn-warning px-1 py-0"
                    style="font-size: 0.6rem;"
                    title="Modifier"
                  >
                    ✏️
                  </button>
                </td>
              </tr>
            </template>
          </template>
        </tbody>
      </table>
      
      <div v-if="filteredEntitiesWithProcesses.length === 0" class="p-3 text-center text-muted">
        <small>Aucune donnée trouvée</small>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL: AJOUTER UN RISQUE À UN PROCESSUS -->
    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <div v-if="modals.addRiskToProcess" class="modal d-block" style="background: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">➕ Ajouter un risque à {{ selectedProcess?.code }}</h5>
            <button type="button" class="btn-close" @click="closeAddRiskToProcess"></button>
          </div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-6">
                <small class="fw-bold d-block mb-1" style="font-size: 0.7rem;">Type de risque</small>
                <select v-model="newRiskToProcess.type_code" class="form-select form-select-sm mb-2" @change="updateRiskSuggestions">
                  <option value="">Sélectionner un type</option>
                  <option v-for="t in riskTypes" :key="t.code" :value="t.code">{{ t.code }} - {{ t.label }}</option>
                </select>
              </div>
              <div class="col-md-6">
                <small class="fw-bold d-block mb-1" style="font-size: 0.7rem;">Code risque</small>
                <input v-model="newRiskToProcess.code" type="text" class="form-control form-control-sm mb-2" placeholder="Ex: RC-001">
              </div>
              
              <div class="col-md-12">
                <small class="fw-bold d-block mb-1" style="font-size: 0.7rem;">Libellé du risque</small>
                <textarea v-model="newRiskToProcess.label" class="form-control form-control-sm mb-2" rows="2" placeholder="Description du risque"></textarea>
              </div>
              
              <!-- Suggestions d'IA pour les risques -->
              <div v-if="riskSuggestions.length > 0" class="col-md-12">
                <small class="fw-bold d-block mb-1" style="font-size: 0.7rem; color: #667eea;">💡 Suggestions IA pour ce type de risque:</small>
                <div class="card p-2 bg-light">
                  <div class="d-flex flex-wrap gap-2">
                    <button 
                      v-for="suggestion in riskSuggestions" 
                      :key="suggestion.id"
                      @click="selectRiskSuggestion(suggestion)"
                      class="btn btn-sm btn-outline-primary"
                      style="font-size: 0.65rem;"
                    >
                      {{ suggestion.label }}
                    </button>
                  </div>
                </div>
              </div>
              
              <div class="col-md-6">
                <small class="fw-bold d-block mb-1" style="font-size: 0.7rem;">Fréquence</small>
                <select v-model="newRiskToProcess.frequency_code" class="form-select form-select-sm mb-2">
                  <option value="">Sélectionner</option>
                  <option v-for="f in frequencies" :key="f.code" :value="f.code">{{ f.label }} (Niveau {{ f.level }})</option>
                </select>
              </div>
              
              <div class="col-md-6">
                <small class="fw-bold d-block mb-1" style="font-size: 0.7rem;">Impact</small>
                <select v-model="newRiskToProcess.impact_code" class="form-select form-select-sm mb-2">
                  <option value="">Sélectionner</option>
                  <option v-for="i in impacts" :key="i.code" :value="i.code">{{ i.label }} (Niveau {{ i.level }})</option>
                </select>
              </div>
              
              <div class="col-md-12">
                <small class="fw-bold d-block mb-1" style="font-size: 0.7rem;">Procédure de contrôle</small>
                <input v-model="newRiskToProcess.control_procedure" type="text" class="form-control form-control-sm mb-2" placeholder="Procédure de contrôle existante">
              </div>
              
              <div class="col-md-12">
                <small class="fw-bold d-block mb-1" style="font-size: 0.7rem;">Propriétaire du risque</small>
                <input v-model="newRiskToProcess.owner" type="text" class="form-control form-control-sm" placeholder="Responsable du risque">
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" @click="closeAddRiskToProcess">Annuler</button>
            <button type="button" class="btn btn-success btn-sm" @click="addRiskToProcess">Ajouter</button>
          </div>
        </div>
      </div>
    </div>

    <!-- AUTRES MODALES (inchangées) ... -->

  </AuditModuleLayoutFinal>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import AuditModuleLayoutFinal from '@/layoutsparam/AuditModuleLayout-FINAL.vue'

// ════════════════════════════════════════════════════════════════════════════
// DONNÉES
// ════════════════════════════════════════════════════════════════════════════

const riskTypes = ref([
  { code: 'RC', label: 'Risque de conformité', color: 'danger' },
  { code: 'RF', label: 'Risque financier', color: 'info' },
  { code: 'RI', label: 'Risque informatique', color: 'warning' },
  { code: 'RM', label: 'Risque métier', color: 'secondary' },
  { code: 'RO', label: 'Risque opérationnel', color: 'primary' },
  { code: 'RS', label: 'Risque stratégique', color: 'success' },
])

const frequencies = ref([
  { code: 'RARE', label: 'Rare', level: 1, color: 'success', description: 'Une fois tous les 5 ans' },
  { code: 'PROBABLE', label: 'Probable', level: 2, color: 'warning', description: 'Une fois par an' },
  { code: 'FREQUENT', label: 'Fréquent', level: 3, color: 'danger', description: 'Plusieurs fois par an' },
  { code: 'CERTAIN', label: 'Certain', level: 4, color: 'dark', description: 'Mensuel ou plus' },
])

const impacts = ref([
  { code: 'FAIBLE', label: 'Faible', level: 1, color: 'success', description: 'Impact mineur' },
  { code: 'MODERE', label: 'Modéré', level: 2, color: 'info', description: 'Perturbations mineures' },
  { code: 'IMPORTANT', label: 'Important', level: 3, color: 'warning', description: 'Perturbations significatives' },
  { code: 'ELEVE', label: 'Élevé', level: 4, color: 'danger', description: 'Conséquences majeures' },
  { code: 'MOYEN', label: 'Moyen', level: 5, color: 'dark', description: 'Catastrophique' },
])

const processes = ref([
  { id: 1, code: 'P001', name: 'Processus de vente', entity: 'Commercial' },
  { id: 2, code: 'P002', name: 'Processus de production', entity: 'Production' },
  { id: 3, code: 'P003', name: 'Processus RH', entity: 'RH' },
  { id: 4, code: 'P004', name: 'Processus financier', entity: 'Finance' },
  { id: 5, code: 'P005', name: 'Processus IT', entity: 'IT' },
  { id: 6, code: 'P006', name: 'Processus achats', entity: 'Finance' },
  { id: 7, code: 'P007', name: 'Processus recrutement', entity: 'RH' },
  { id: 8, code: 'P008', name: 'Processus maintenance', entity: 'Production' },
])

const entities = ref([
  { name: 'Finance', processes: [4, 6] },
  { name: 'RH', processes: [3, 7] },
  { name: 'IT', processes: [5] },
  { name: 'Commercial', processes: [1] },
  { name: 'Production', processes: [2, 8] },
])

const risks = ref([
  { id: 1, code: 'RC-001', label: 'Non-conformité RGPD', type_code: 'RC', type_label: 'Risque de conformité', frequency_code: 'PROBABLE', impact_code: 'ELEVE', entity: 'Finance', process_id: 4, owner: 'Dupont', control_procedure: 'Audit interne trimestriel', criticality: 8, project_code: 'KEKELI', year: 2026, activity_code: 'ACT01' },
  { id: 2, code: 'RC-002', label: 'Défaillance contrôle interne', type_code: 'RC', type_label: 'Risque de conformité', frequency_code: 'FREQUENT', impact_code: 'IMPORTANT', entity: 'RH', process_id: 3, owner: 'Martin', control_procedure: 'Tests réguliers', criticality: 9, project_code: 'KEKELI', year: 2026, activity_code: 'ACT02' },
  { id: 3, code: 'RI-001', label: 'Cyberattaque phishing', type_code: 'RI', type_label: 'Risque informatique', frequency_code: 'FREQUENT', impact_code: 'MOYEN', entity: 'IT', process_id: 5, owner: 'Mercier', control_procedure: 'MFA et formation', criticality: 20, project_code: 'KEKELI', year: 2026, activity_code: 'ACT03' },
  { id: 4, code: 'RF-001', label: 'Fraude financière', type_code: 'RF', type_label: 'Risque financier', frequency_code: 'RARE', impact_code: 'ELEVE', entity: 'Finance', process_id: 4, owner: 'Lefevre', control_procedure: 'Contrôles à 4 yeux', criticality: 4, project_code: 'KEKELI', year: 2026, activity_code: 'ACT04' },
  { id: 5, code: 'RO-001', label: 'Panne équipement critique', type_code: 'RO', type_label: 'Risque opérationnel', frequency_code: 'PROBABLE', impact_code: 'IMPORTANT', entity: 'Production', process_id: 2, owner: 'Dubois', control_procedure: 'Maintenance préventive', criticality: 6, project_code: 'KEKELI', year: 2026, activity_code: 'ACT05' },
])

const riskSuggestionsData = ref([
  { id: 1, type_code: 'RC', label: 'Non-respect des délais légaux de conservation des documents' },
  { id: 2, type_code: 'RC', label: 'Manquement aux obligations de reporting réglementaire' },
  { id: 3, type_code: 'RF', label: 'Fluctuation des taux de change impactant la rentabilité' },
  { id: 4, type_code: 'RF', label: 'Défaut de paiement client majeur' },
  { id: 5, type_code: 'RI', label: 'Perte de données sensibles due à une faille de sécurité' },
  { id: 6, type_code: 'RI', label: 'Indisponibilité du système informatique critique' },
  { id: 7, type_code: 'RO', label: 'Rupture de stock de matières premières stratégiques' },
  { id: 8, type_code: 'RO', label: 'Départ simultané de collaborateurs clés' },
  { id: 9, type_code: 'RM', label: 'Évolution défavorable des prix du marché' },
  { id: 10, type_code: 'RM', label: 'Perte de parts de marché au profit d\'un concurrent' },
  { id: 11, type_code: 'RS', label: 'Changement brusque de la réglementation sectorielle' },
  { id: 12, type_code: 'RS', label: 'Échec d\'un projet stratégique d\'investissement' },
])

const matrixLevels = ref([
  { impact_level: 2, freq_level: 1, qualification: '#90EE90', impact_color: 'info', freq_color: 'success' },
  { impact_level: 3, freq_level: 1, qualification: '#FFD700', impact_color: 'warning', freq_color: 'success' },
  { impact_level: 4, freq_level: 3, qualification: '#FF6347', impact_color: 'danger', freq_color: 'danger' },
])

// ════════════════════════════════════════════════════════════════════════════
// STATE & FORMS
// ════════════════════════════════════════════════════════════════════════════

const filters = ref({ project_code: 'KEKELI', entity: '', year: 2026 })
const newRiskForm = ref({ project_code: 'KEKELI', entity: '', type_code: '', process_id: null, code: '', label: '', frequency_code: '', impact_code: '', owner: '', control_procedure: '' })
const matrixForm = ref({ project_code: 'KEKELI', impact_level: 0, freq_level: 0, label: '', qualification: '#FFD700' })
const freqForm = ref({ project_code: 'KEKELI', level: 1, label: '', description: '' })
const impactForm = ref({ project_code: 'KEKELI', level: 1, label: '', description: '' })
const typeForm = ref({ project_code: 'KEKELI', code: '', label: '' })
const editingRisk = ref({ id: null, project_id: 'KEKELI', process_id: null, code: '', type_code: '', label: '', activity_code: '' })

const newRiskToProcess = ref({ 
  type_code: '', 
  code: '', 
  label: '', 
  frequency_code: '', 
  impact_code: '', 
  control_procedure: '', 
  owner: '' 
})

const riskSuggestions = ref<any[]>([])
const selectedProcess = ref<any>(null)

const modals = ref({ 
  createRisk: false, 
  matrixGlobal: false, 
  frequency: false, 
  impact: false, 
  types: false, 
  editRisk: false,
  addRiskToProcess: false 
})

const collapsedEntities = ref<string[]>([])
const expandedProcesses = ref<number[]>([])

// ════════════════════════════════════════════════════════════════════════════
// COMPUTED
// ════════════════════════════════════════════════════════════════════════════

const entityOptions = computed(() => {
  return entities.value.map(e => e.name)
})

const filteredEntities = computed(() => {
  return ['Finance', 'RH', 'IT', 'Commercial', 'Production']
})

const filteredEntitiesWithProcesses = computed(() => {
  return entities.value
    .filter(entity => {
      if (filters.value.entity && entity.name !== filters.value.entity) {
        return false
      }
      return true
    })
    .map(entity => {
      const entityProcesses = processes.value
        .filter(process => process.entity === entity.name)
        .map(process => {
          const processRisks = risks.value.filter(risk => risk.process_id === process.id)
          
          const criticality = processRisks.length > 0 
            ? Math.round(processRisks.reduce((sum, risk) => sum + (risk.criticality || 0), 0) / processRisks.length)
            : 0
            
          return {
            ...process,
            risks: processRisks,
            criticality: criticality,
            expanded: expandedProcesses.value.includes(process.id)
          }
        })
      
      return {
        ...entity,
        processes: entityProcesses,
        collapsed: collapsedEntities.value.includes(entity.name)
      }
    })
})

// ════════════════════════════════════════════════════════════════════════════
// METHODS - ✅ CORRIGÉES
// ════════════════════════════════════════════════════════════════════════════

// ✅ CORRECTION: Renommer les variables locales pour éviter le shadowing
const getAverageImpactColor = (process: any) => {
  if (!process.risks || process.risks.length === 0) return 'secondary'
  
  const impactLevels = process.risks.map((r: any) => {
    const impactObj = impacts.value.find(i => i.code === r.impact_code)
    return impactObj ? impactObj.level : 1
  })
  
  const avg = impactLevels.reduce((a: number, b: number) => a + b, 0) / impactLevels.length
  
  if (avg >= 4) return 'danger'
  if (avg >= 3) return 'warning'
  if (avg >= 2) return 'info'
  return 'success'
}

const getAverageImpactLabel = (process: any) => {
  if (!process.risks || process.risks.length === 0) return '-'
  
  const impactLevels = process.risks.map((r: any) => {
    const impactObj = impacts.value.find(i => i.code === r.impact_code)
    return impactObj ? impactObj.level : 1
  })
  
  const avg = impactLevels.reduce((a: number, b: number) => a + b, 0) / impactLevels.length
  
  if (avg >= 4.5) return 'Très Élevé'
  if (avg >= 3.5) return 'Élevé'
  if (avg >= 2.5) return 'Important'
  if (avg >= 1.5) return 'Modéré'
  return 'Faible'
}

const getAverageFrequencyColor = (process: any) => {
  if (!process.risks || process.risks.length === 0) return 'secondary'
  
  const frequencyLevels = process.risks.map((r: any) => {
    const freqObj = frequencies.value.find(f => f.code === r.frequency_code)
    return freqObj ? freqObj.level : 1
  })
  
  const avg = frequencyLevels.reduce((a: number, b: number) => a + b, 0) / frequencyLevels.length
  
  if (avg >= 3.5) return 'dark'
  if (avg >= 2.5) return 'danger'
  if (avg >= 1.5) return 'warning'
  return 'success'
}

const getAverageFrequencyLabel = (process: any) => {
  if (!process.risks || process.risks.length === 0) return '-'
  
  const frequencyLevels = process.risks.map((r: any) => {
    const freqObj = frequencies.value.find(f => f.code === r.frequency_code)
    return freqObj ? freqObj.level : 1
  })
  
  const avg = frequencyLevels.reduce((a: number, b: number) => a + b, 0) / frequencyLevels.length
  
  if (avg >= 3.5) return 'Certain'
  if (avg >= 2.5) return 'Fréquent'
  if (avg >= 1.5) return 'Probable'
  return 'Rare'
}

// ✅ CORRECTION: Déclarer typeCode AVANT de l'utiliser
const selectRiskSuggestion = (suggestion: any) => {
  newRiskToProcess.value.label = suggestion.label
  const typeCode = newRiskToProcess.value.type_code
  
  if (!newRiskToProcess.value.code) {
    const existingRisks = risks.value.filter(r => r.type_code === typeCode)
    const nextNumber = existingRisks.length + 1
    newRiskToProcess.value.code = `${typeCode}-${nextNumber.toString().padStart(3, '0')}`
  }
  
  if (!newRiskToProcess.value.frequency_code) {
    if (typeCode === 'RI') {
      newRiskToProcess.value.frequency_code = 'PROBABLE'
    } else if (typeCode === 'RC') {
      newRiskToProcess.value.frequency_code = 'RARE'
    } else {
      newRiskToProcess.value.frequency_code = 'PROBABLE'
    }
  }
  
  if (!newRiskToProcess.value.impact_code) {
    if (typeCode === 'RF') {
      newRiskToProcess.value.impact_code = 'IMPORTANT'
    } else if (typeCode === 'RS') {
      newRiskToProcess.value.impact_code = 'ELEVE'
    } else {
      newRiskToProcess.value.impact_code = 'MODERE'
    }
  }
}

const toggleEntity = (entityName: string) => {
  const index = collapsedEntities.value.indexOf(entityName)
  if (index > -1) {
    collapsedEntities.value.splice(index, 1)
  } else {
    collapsedEntities.value.push(entityName)
  }
}

const toggleProcess = (processId: number) => {
  const index = expandedProcesses.value.indexOf(processId)
  if (index > -1) {
    expandedProcesses.value.splice(index, 1)
  } else {
    expandedProcesses.value.push(processId)
  }
}

const openAddRiskToProcess = (process: any) => {
  selectedProcess.value = process
  newRiskToProcess.value = { type_code: '', code: '', label: '', frequency_code: '', impact_code: '', control_procedure: '', owner: '' }
  riskSuggestions.value = []
  modals.value.addRiskToProcess = true
}

const closeAddRiskToProcess = () => {
  modals.value.addRiskToProcess = false
  selectedProcess.value = null
}

const updateRiskSuggestions = () => {
  if (newRiskToProcess.value.type_code) {
    riskSuggestions.value = riskSuggestionsData.value
      .filter(s => s.type_code === newRiskToProcess.value.type_code)
      .slice(0, 4)
  } else {
    riskSuggestions.value = []
  }
}

const addRiskToProcess = () => {
  if (!newRiskToProcess.value.code || !newRiskToProcess.value.label) {
    alert('Veuillez remplir le code et le libellé du risque')
    return
  }
  
  const freqLevel = frequencies.value.find(f => f.code === newRiskToProcess.value.frequency_code)?.level || 1
  const impactLevel = impacts.value.find(i => i.code === newRiskToProcess.value.impact_code)?.level || 1
  const criticality = freqLevel * impactLevel
  const typeLabel = riskTypes.value.find(t => t.code === newRiskToProcess.value.type_code)?.label || ''
  
  const newRisk = {
    id: Math.max(...risks.value.map(r => r.id), 0) + 1,
    code: newRiskToProcess.value.code,
    label: newRiskToProcess.value.label,
    type_code: newRiskToProcess.value.type_code,
    type_label: typeLabel,
    frequency_code: newRiskToProcess.value.frequency_code,
    impact_code: newRiskToProcess.value.impact_code,
    entity: selectedProcess.value.entity,
    process_id: selectedProcess.value.id,
    owner: newRiskToProcess.value.owner || 'Non défini',
    control_procedure: newRiskToProcess.value.control_procedure || 'À définir',
    criticality: criticality,
    project_code: 'KEKELI',
    year: filters.value.year,
    activity_code: `ACT${(risks.value.length + 1).toString().padStart(2, '0')}`,
  }
  
  risks.value.push(newRisk)
  closeAddRiskToProcess()
}

const openCreateRisk = () => {
  newRiskForm.value = { project_code: 'KEKELI', entity: '', type_code: '', process_id: null, code: '', label: '', frequency_code: '', impact_code: '', owner: '', control_procedure: '' }
  modals.value.createRisk = true
}

const saveNewRisk = () => {
  if (newRiskForm.value.code && newRiskForm.value.label) {
    const freqLevel = frequencies.value.find(f => f.code === newRiskForm.value.frequency_code)?.level || 1
    const impactLevel = impacts.value.find(i => i.code === newRiskForm.value.impact_code)?.level || 1
    const criticality = freqLevel * impactLevel
    const typeLabel = riskTypes.value.find(t => t.code === newRiskForm.value.type_code)?.label || ''
    const processName = processes.value.find(p => p.id === newRiskForm.value.process_id)?.name || '-'
    
    risks.value.push({
      id: Math.max(...risks.value.map(r => r.id), 0) + 1,
      code: newRiskForm.value.code,
      label: newRiskForm.value.label,
      type_code: newRiskForm.value.type_code,
      type_label: typeLabel,
      frequency_code: newRiskForm.value.frequency_code,
      impact_code: newRiskForm.value.impact_code,
      entity: newRiskForm.value.entity,
      process: processName,
      process_id: newRiskForm.value.process_id,
      owner: newRiskForm.value.owner,
      control_procedure: newRiskForm.value.control_procedure,
      criticality: criticality,
      project_code: newRiskForm.value.project_code,
      year: 2026,
      activity_code: '',
    })
    modals.value.createRisk = false
  }
}

const updateEntities = () => {
  newRiskForm.value.entity = ''
}

const updateFreqLabel = () => {
  const f = frequencies.value.find(f => f.level === parseInt(freqForm.value.level.toString()))
  if (f) { 
    freqForm.value.label = f.label
    freqForm.value.description = f.description 
  }
}

const updateImpactLabel = () => {
  const i = impacts.value.find(i => i.level === parseInt(impactForm.value.level.toString()))
  if (i) { 
    impactForm.value.label = i.label
    impactForm.value.description = i.description 
  }
}

const addMatrix = () => {
  if (matrixForm.value.label) {
    matrixLevels.value.push({
      impact_level: matrixForm.value.impact_level,
      freq_level: matrixForm.value.freq_level,
      qualification: matrixForm.value.qualification,
      impact_color: 'secondary',
      freq_color: 'secondary',
    })
    resetMatrix()
  }
}

const addFreq = () => {
  if (freqForm.value.label) {
    frequencies.value.push({
      code: `FREQ_${freqForm.value.level}`,
      label: freqForm.value.label,
      level: freqForm.value.level,
      color: 'secondary',
      description: freqForm.value.description,
    })
    resetFreq()
  }
}

const addImpact = () => {
  if (impactForm.value.label) {
    impacts.value.push({
      code: `IMP_${impactForm.value.level}`,
      label: impactForm.value.label,
      level: impactForm.value.level,
      color: 'secondary',
      description: impactForm.value.description,
    })
    resetImpact()
  }
}

const addType = () => {
  if (typeForm.value.code && typeForm.value.label) {
    riskTypes.value.push({ code: typeForm.value.code, label: typeForm.value.label, color: 'secondary' })
    resetType()
  }
}

const editProcess = (process: any) => {
  console.log('Éditer le processus:', process)
}

const openProcessDetail = (process: any) => {
  console.log('Voir les détails du processus:', process)
}

const editRisk = (risk: any) => {
  editingRisk.value = { ...risk }
  modals.value.editRisk = true
}

const saveEditedRisk = () => {
  const idx = risks.value.findIndex(r => r.id === editingRisk.value.id)
  if (idx >= 0) { 
    risks.value[idx] = { ...risks.value[idx], ...editingRisk.value }
  }
  modals.value.editRisk = false
}

const deleteRisk = (risk: any) => {
  if (confirm('Confirmer la suppression de ce risque?')) { 
    risks.value = risks.value.filter(r => r.id !== risk.id) 
  }
}

const resetMatrix = () => { matrixForm.value = { project_code: 'KEKELI', impact_level: 0, freq_level: 0, label: '', qualification: '#FFD700' } }
const resetFreq = () => { freqForm.value = { project_code: 'KEKELI', level: 1, label: '', description: '' } }
const resetImpact = () => { impactForm.value = { project_code: 'KEKELI', level: 1, label: '', description: '' } }
const resetType = () => { typeForm.value = { project_code: 'KEKELI', code: '', label: '' } }

const getRiskTypeColor = (code: string) => {
  const type = riskTypes.value.find(t => t.code === code)
  if (!type) return '#6c757d'
  
  switch(type.color) {
    case 'danger': return '#dc3545'
    case 'info': return '#0dcaf0'
    case 'warning': return '#ffc107'
    case 'secondary': return '#6c757d'
    case 'primary': return '#0d6efd'
    case 'success': return '#198754'
    default: return '#6c757d'
  }
}

const getTypeColor = (code: string) => riskTypes.value.find(t => t.code === code)?.color || 'secondary'
const getFreqColor = (code: string) => frequencies.value.find(f => f.code === code)?.color || 'secondary'
const getImpactColor = (code: string) => impacts.value.find(i => i.code === code)?.color || 'secondary'
const getCriticalityColor = (criticality: number) => (criticality >= 12 ? 'danger' : criticality >= 8 ? 'warning' : 'success')
</script>

<style scoped>
.modal {
  position: fixed;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  z-index: 1050;
}

.modal-dialog {
  margin: 2rem auto;
}

table {
  font-size: 0.7rem;
  margin-bottom: 0;
}

table th {
  background-color: #0066cc;
  color: white;
  padding: 0.3rem 0.4rem;
  border: 1px solid #0066cc;
}

table td {
  padding: 0.3rem 0.4rem;
  border: 1px solid #dee2e6;
}

.entity-row {
  background-color: #f8f9fa;
  font-weight: bold;
}

.process-row:hover {
  background-color: #f8f9fa;
}

.risk-item {
  background-color: #f8f9fa;
  border: 1px solid #dee2e6;
  transition: all 0.2s;
}

.risk-item:hover {
  background-color: #e9ecef;
}

.badge {
  padding: 0.15rem 0.3rem;
  font-size: 0.6rem;
}

.btn-sm {
  padding: 0.15rem 0.3rem;
  font-size: 0.65rem;
}

.form-control-sm,
.form-select-sm {
  height: 1.8rem;
  font-size: 0.7rem;
}

.card {
  border: 1px solid #dee2e6;
  border-radius: 0.25rem;
}

small {
  display: block;
}
</style>