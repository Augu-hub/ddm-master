<template>
  <VerticalLayout>
    <!-- HEADER -->
    <b-row class="mb-3">
      <b-col>
        <div class="d-flex justify-content-between align-items-center">
          <h3 class="fw-bold text-danger mb-0">
            Gestion des Risques PRO
          </h3>
          <div>
            <b-button size="sm" variant="success" @click="openNewRiskModal" class="me-2">
              + Nouveau Risque
            </b-button>
            <b-button size="sm" variant="outline-primary" @click="exportData">
              Export CSV
            </b-button>
          </div>
        </div>
      </b-col>
    </b-row>

    <!-- STATS -->
    <b-row class="mb-3">
      <b-col lg="3">
        <b-card no-body class="border-0 shadow-sm bg-light">
          <b-card-body class="text-center p-3">
            <div class="h2 text-primary fw-bold">{{ risks.length }}</div>
            <small class="text-muted">Risques Total</small>
          </b-card-body>
        </b-card>
      </b-col>
      <b-col lg="3">
        <b-card no-body class="border-0 shadow-sm bg-light">
          <b-card-body class="text-center p-3">
            <div class="h2 text-danger fw-bold">{{ risksCritiques }}</div>
            <small class="text-muted">Critiques (≥12)</small>
          </b-card-body>
        </b-card>
      </b-col>
      <b-col lg="3">
        <b-card no-body class="border-0 shadow-sm bg-light">
          <b-card-body class="text-center p-3">
            <div class="h2 text-success fw-bold">{{ risksActifs }}</div>
            <small class="text-muted">Actifs</small>
          </b-card-body>
        </b-card>
      </b-col>
      <b-col lg="3">
        <b-card no-body class="border-0 shadow-sm bg-light">
          <b-card-body class="text-center p-3">
            <div class="h2 text-info fw-bold">{{ moyenneCriticite }}</div>
            <small class="text-muted">Criticité Moy.</small>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>

    <!-- FILTRES -->
    <b-row class="mb-3">
      <b-col lg="12">
        <b-card no-body class="border-0 shadow-sm">
          <b-card-body class="p-3">
            <b-row>
              <b-col lg="4">
                <small class="text-muted d-block mb-2 fw-bold">Filtrer par Type</small>
                <b-form-select
                  v-model="filterTypeCode"
                  :options="typeOptions"
                  size="sm"
                />
              </b-col>
              <b-col lg="4">
                <small class="text-muted d-block mb-2 fw-bold">Filtrer par Statut</small>
                <b-form-select
                  v-model="filterStatus"
                  :options="statusOptions"
                  size="sm"
                />
              </b-col>
              <b-col lg="4">
                <small class="text-muted d-block mb-2 fw-bold">Recherche</small>
                <b-input-group size="sm">
                  <b-form-input 
                    v-model="searchText" 
                    placeholder="Code ou libellé..."
                  />
                  <b-input-group-append>
                    <b-button variant="outline-secondary" @click="searchText = ''">
                      ✕
                    </b-button>
                  </b-input-group-append>
                </b-input-group>
              </b-col>
            </b-row>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>

    <!-- TABLEAU RISQUES -->
    <b-row>
      <b-col lg="12">
        <b-card no-body class="border-0 shadow-sm">
          <b-table
            :items="risksFiltrés"
            :fields="tableFields"
            responsive
            hover
            striped
            small
            class="mb-0"
          >
            <!-- CODE -->
            <template #cell(code)="row">
              <b-badge :bg="getTypeColor(row.item.type_code)" class="px-2 py-1">
                <strong>{{ row.item.code }}</strong>
              </b-badge>
            </template>

            <!-- LABEL + DESC -->
            <template #cell(label)="row">
              <div>
                <strong>{{ row.item.label }}</strong>
                <br>
                <small class="text-muted">{{ row.item.description }}</small>
              </div>
            </template>

            <!-- TYPE -->
            <template #cell(type_code)="row">
              <b-badge :bg="getTypeColor(row.item.type_code)">
                {{ row.item.type_code }}
              </b-badge>
            </template>

            <!-- FRÉQUENCE -->
            <template #cell(frequency_code)="row">
              <b-button
                size="sm"
                variant="warning"
                class="px-2 py-1"
                @click="openFrequencyModal(row.item)"
              >
                {{ getFrequencyLabel(row.item.frequency_code) }}
              </b-button>
            </template>

            <!-- IMPACT -->
            <template #cell(impact_code)="row">
              <b-button
                size="sm"
                variant="warning"
                class="px-2 py-1"
                @click="openImpactModal(row.item)"
              >
                {{ getImpactLabel(row.item.impact_code) }}
              </b-button>
            </template>

            <!-- CRITICITÉ -->
            <template #cell(criticality)="row">
              <b-badge :bg="getCriticalityColor(row.item.criticality)" class="px-2 py-1">
                <strong>{{ row.item.criticality }}</strong>
              </b-badge>
            </template>

            <!-- PROPRIÉTAIRE -->
            <template #cell(owner)="row">
              {{ row.item.owner || '-' }}
            </template>

            <!-- PROCESSUS -->
            <template #cell(process_id)="row">
              <small>{{ getProcessLabel(row.item.process_id) }}</small>
            </template>

            <!-- STATUT -->
            <template #cell(status)="row">
              <b-badge :bg="row.item.status === 'active' ? 'success' : 'danger'">
                {{ row.item.status === 'active' ? '✓ Actif' : '✗ Inactif' }}
              </b-badge>
            </template>

            <!-- ACTIONS -->
            <template #cell(actions)="row">
              <b-button-group size="sm">
                <b-button variant="info" @click="editRisk(row.item)" title="Éditer">
                  ✏️
                </b-button>
                <b-button variant="warning" @click="openAIModal(row.item)" title="IA">
                  🤖
                </b-button>
                <b-button variant="danger" @click="deleteRisk(row.item)" title="Supprimer">
                  🗑️
                </b-button>
              </b-button-group>
            </template>
          </b-table>
        </b-card>
      </b-col>
    </b-row>

    <!-- MODAL: COTATION FRÉQUENCE -->
    <b-modal
      id="modal-frequency"
      v-model="modals.frequency"
      title="Cotation Fréquence"
      @ok="saveFrequency"
      centered
    >
      <b-form>
        <b-form-group label="Risque" label-cols-sm="4">
          <b-form-input :value="editingRisk?.label" disabled></b-form-input>
        </b-form-group>

        <b-form-group label="Niveau (1-4)" label-cols-sm="4">
          <b-form-input
            v-model.number="forms.frequency.level"
            type="range"
            min="1"
            max="4"
            @input="updateFrequencyLabel"
          ></b-form-input>
          <small class="text-muted d-block mt-2">
            <strong>{{ forms.frequency.label }}</strong> — {{ forms.frequency.description }}
          </small>
        </b-form-group>

        <b-form-group label="Fréquences" label-cols-sm="4">
          <div class="d-flex gap-2 flex-wrap">
            <b-badge
              v-for="f in frequencies"
              :key="f.code"
              :bg="f.color"
              :class="{ 'border border-2 border-dark': parseInt(forms.frequency.level) === f.level }"
            >
              {{ f.label }}
            </b-badge>
          </div>
        </b-form-group>
      </b-form>
    </b-modal>

    <!-- MODAL: COTATION IMPACT -->
    <b-modal
      id="modal-impact"
      v-model="modals.impact"
      title="Cotation Impact"
      @ok="saveImpact"
      centered
    >
      <b-form>
        <b-form-group label="Risque" label-cols-sm="4">
          <b-form-input :value="editingRisk?.label" disabled></b-form-input>
        </b-form-group>

        <b-form-group label="Niveau (1-5)" label-cols-sm="4">
          <b-form-input
            v-model.number="forms.impact.level"
            type="range"
            min="1"
            max="5"
            @input="updateImpactLabel"
          ></b-form-input>
          <small class="text-muted d-block mt-2">
            <strong>{{ forms.impact.label }}</strong> — {{ forms.impact.description }}
          </small>
        </b-form-group>

        <b-form-group label="Impacts" label-cols-sm="4">
          <div class="d-flex gap-2 flex-wrap">
            <b-badge
              v-for="i in impacts"
              :key="i.code"
              :bg="i.color"
              :class="{ 'border border-2 border-dark': parseInt(forms.impact.level) === i.level }"
            >
              {{ i.label }}
            </b-badge>
          </div>
        </b-form-group>
      </b-form>
    </b-modal>

    <!-- MODAL: ÉDITION RISQUE -->
    <b-modal
      id="modal-risk-editor"
      v-model="modals.riskEditor"
      :title="editingRisk?.id ? 'Édition Risque' : 'Nouveau Risque'"
      size="lg"
      @ok="saveRisk"
      centered
    >
      <b-form>
        <b-form-group label="Code" label-cols-sm="3">
          <b-form-input v-model="forms.risk.code" placeholder="RC-001"></b-form-input>
        </b-form-group>

        <b-form-group label="Type" label-cols-sm="3">
          <b-form-select v-model="forms.risk.type_code" :options="typeSelectOptions"></b-form-select>
        </b-form-group>

        <b-form-group label="Libellé" label-cols-sm="3">
          <b-form-input v-model="forms.risk.label" placeholder="Description du risque"></b-form-input>
        </b-form-group>

        <b-form-group label="Propriétaire" label-cols-sm="3">
          <b-form-input v-model="forms.risk.owner" placeholder="Nom du responsable"></b-form-input>
        </b-form-group>

        <b-form-group label="Processus" label-cols-sm="3">
          <b-form-select v-model.number="forms.risk.process_id" :options="processSelectOptions"></b-form-select>
        </b-form-group>

        <b-form-group label="Date Cible" label-cols-sm="3">
          <b-form-input v-model="forms.risk.target_date" type="date"></b-form-input>
        </b-form-group>

        <b-form-group label="Description" label-cols-sm="3">
          <b-form-textarea v-model="forms.risk.description" rows="4"></b-form-textarea>
        </b-form-group>
      </b-form>
    </b-modal>

    <!-- MODAL: SUGGESTIONS IA -->
    <b-modal
      id="modal-ai"
      v-model="modals.ai"
      title="🤖 Suggestions IA (Grok)"
      size="lg"
      centered
    >
      <div v-if="ai.loading" class="text-center">
        <b-spinner class="mb-3"></b-spinner>
        <p class="text-muted">⏳ Analyse IA en cours...</p>
      </div>

      <div v-else-if="ai.content" class="bg-light p-3 rounded">
        <h6 class="fw-bold mb-3">Risque: {{ editingRisk?.label }}</h6>
        <div class="ai-content" v-html="formatAIResponse(ai.content)"></div>
      </div>

      <div v-else class="text-center text-muted">
        Cliquez sur "Générer Suggestions" pour obtenir des recommandations
      </div>

      <template #modal-footer>
        <b-button variant="primary" @click="generateAISuggestions">
          Générer Suggestions
        </b-button>
        <b-button variant="secondary" @click="modals.ai = false">Fermer</b-button>
      </template>
    </b-modal>

  </VerticalLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

// ════════════════════════════════════════════════════════════════════════════
// 📊 DONNÉES MANUELLES
// ════════════════════════════════════════════════════════════════════════════

const riskTypes = ref([
  { code: 'RC', label: 'Risque de conformité', bgColor: 'danger' },
  { code: 'RF', label: 'Risque financier', bgColor: 'info' },
  { code: 'RI', label: 'Risque informatique', bgColor: 'warning' },
  { code: 'RM', label: 'Risque métier', bgColor: 'secondary' },
  { code: 'RO', label: 'Risque opérationnel', bgColor: 'primary' },
  { code: 'RS', label: 'Risque stratégique', bgColor: 'success' },
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
  { id: 1, code: 'P001', label: 'Processus de vente' },
  { id: 2, code: 'P002', label: 'Processus de production' },
  { id: 3, code: 'P003', label: 'Processus RH' },
  { id: 4, code: 'P004', label: 'Processus financier' },
  { id: 5, code: 'P005', label: 'Processus IT' },
])

const risks = ref([
  { id: 1, code: 'RC-001', label: 'Non-respect de la RGPD', type_code: 'RC', frequency_code: 'PROBABLE', impact_code: 'ELEVE', process_id: 4, owner: 'Jean Dupont', target_date: '2026-03-31', description: 'Risque lié au non-respect RGPD', status: 'active', criticality: 8 },
  { id: 2, code: 'RC-002', label: 'Contrôle interne inefficace', type_code: 'RC', frequency_code: 'FREQUENT', impact_code: 'IMPORTANT', process_id: 4, owner: 'Marie Martin', target_date: '2026-02-28', description: 'Défaillances processus', status: 'active', criticality: 9 },
  { id: 3, code: 'RC-003', label: 'Audit externe défaillant', type_code: 'RC', frequency_code: 'RARE', impact_code: 'IMPORTANT', process_id: 4, owner: 'Pierre Bernard', target_date: '2026-06-30', description: 'Risque audit externe', status: 'inactive', criticality: 3 },
  { id: 4, code: 'RF-001', label: 'Fraude financière', type_code: 'RF', frequency_code: 'RARE', impact_code: 'MOYEN', process_id: 4, owner: 'Sophie Lefevre', target_date: '2026-12-31', description: 'Risque fraude paiement', status: 'active', criticality: 5 },
  { id: 5, code: 'RF-002', label: 'Fluctuation taux change', type_code: 'RF', frequency_code: 'FREQUENT', impact_code: 'MODERE', process_id: 4, owner: 'Luc Renard', target_date: '2026-04-15', description: 'Exposition change', status: 'active', criticality: 6 },
  { id: 6, code: 'RI-001', label: 'Cyberattaque / Ransomware', type_code: 'RI', frequency_code: 'FREQUENT', impact_code: 'MOYEN', process_id: 5, owner: 'Thomas Mercier', target_date: '2026-01-31', description: 'Risque ransomware', status: 'active', criticality: 20 },
  { id: 7, code: 'RI-002', label: 'Perte de données critiques', type_code: 'RI', frequency_code: 'PROBABLE', impact_code: 'MOYEN', process_id: 5, owner: 'Isabelle Goncalves', target_date: '2026-03-15', description: 'Perte données irréversible', status: 'active', criticality: 10 },
  { id: 8, code: 'RM-001', label: 'Perte de clients clés', type_code: 'RM', frequency_code: 'RARE', impact_code: 'MOYEN', process_id: 1, owner: 'Cathérine Blanc', target_date: '2026-05-31', description: 'Perte contrats', status: 'inactive', criticality: 5 },
  { id: 9, code: 'RM-002', label: 'Perturbation supply chain', type_code: 'RM', frequency_code: 'PROBABLE', impact_code: 'IMPORTANT', process_id: 2, owner: 'Olivier Rousseau', target_date: '2026-04-30', description: 'Rupture appro', status: 'active', criticality: 9 },
  { id: 10, code: 'RO-001', label: 'Absence clé / Turnover', type_code: 'RO', frequency_code: 'PROBABLE', impact_code: 'IMPORTANT', process_id: 3, owner: 'Nathalie Aubert', target_date: '2026-02-15', description: 'Départ éléments clés', status: 'active', criticality: 9 },
  { id: 11, code: 'RS-001', label: 'Évolution marché défavorable', type_code: 'RS', frequency_code: 'PROBABLE', impact_code: 'ELEVE', process_id: 1, owner: 'David Fontaine', target_date: '2026-09-30', description: 'Marché défavorable', status: 'active', criticality: 8 },
])

// ════════════════════════════════════════════════════════════════════════════
// 🎯 STATE
// ════════════════════════════════════════════════════════════════════════════

const filterTypeCode = ref(null)
const filterStatus = ref(null)
const searchText = ref('')
const editingRisk = ref(null)

const modals = ref({
  frequency: false,
  impact: false,
  riskEditor: false,
  ai: false,
})

const forms = ref({
  frequency: { level: 1, label: '', description: '' },
  impact: { level: 1, label: '', description: '' },
  risk: { code: '', label: '', type_code: '', process_id: null, owner: '', target_date: '', description: '' },
})

const ai = ref({
  loading: false,
  content: '',
})

// ════════════════════════════════════════════════════════════════════════════
// 📈 COMPUTED
// ════════════════════════════════════════════════════════════════════════════

const tableFields = computed(() => [
  { key: 'code', label: 'Code', sortable: true },
  { key: 'label', label: 'Libellé' },
  { key: 'type_code', label: 'Type' },
  { key: 'frequency_code', label: 'Fréquence' },
  { key: 'impact_code', label: 'Impact' },
  { key: 'criticality', label: 'Criticité' },
  { key: 'owner', label: 'Propriétaire' },
  { key: 'process_id', label: 'Processus' },
  { key: 'status', label: 'Statut' },
  { key: 'actions', label: 'Actions' },
])

const typeOptions = computed(() => [
  { value: null, text: '-- Tous les types --' },
  ...riskTypes.value.map(t => ({ value: t.code, text: t.label }))
])

const typeSelectOptions = computed(() => [
  { value: '', text: '-- Sélectionner --' },
  ...riskTypes.value.map(t => ({ value: t.code, text: `${t.code} - ${t.label}` }))
])

const processSelectOptions = computed(() => [
  { value: null, text: '-- Sélectionner --' },
  ...processes.value.map(p => ({ value: p.id, text: `${p.code} - ${p.label}` }))
])

const statusOptions = computed(() => [
  { value: null, text: '-- Tous les statuts --' },
  { value: 'active', text: '✓ Actifs' },
  { value: 'inactive', text: '✗ Inactifs' },
])

const risksFiltrés = computed(() => {
  return risks.value.filter(r => {
    const typeMatch = !filterTypeCode.value || r.type_code === filterTypeCode.value
    const statusMatch = !filterStatus.value || r.status === filterStatus.value
    const searchMatch = !searchText.value || 
      r.code.toLowerCase().includes(searchText.value.toLowerCase()) ||
      r.label.toLowerCase().includes(searchText.value.toLowerCase())
    return typeMatch && statusMatch && searchMatch
  })
})

const risksCritiques = computed(() => risks.value.filter(r => r.criticality >= 12).length)
const risksActifs = computed(() => risks.value.filter(r => r.status === 'active').length)
const moyenneCriticite = computed(() => {
  if (risks.value.length === 0) return 0
  const sum = risks.value.reduce((acc, r) => acc + r.criticality, 0)
  return (sum / risks.value.length).toFixed(1)
})

// ════════════════════════════════════════════════════════════════════════════
// 🔧 METHODS
// ════════════════════════════════════════════════════════════════════════════

const openNewRiskModal = () => {
  editingRisk.value = null
  forms.value.risk = { code: '', label: '', type_code: '', process_id: null, owner: '', target_date: '', description: '' }
  modals.value.riskEditor = true
}

const openFrequencyModal = (risk) => {
  editingRisk.value = risk
  const freq = frequencies.value.find(f => f.code === risk.frequency_code)
  forms.value.frequency = {
    level: freq?.level || 1,
    label: freq?.label || '',
    description: freq?.description || '',
  }
  modals.value.frequency = true
}

const openImpactModal = (risk) => {
  editingRisk.value = risk
  const imp = impacts.value.find(i => i.code === risk.impact_code)
  forms.value.impact = {
    level: imp?.level || 1,
    label: imp?.label || '',
    description: imp?.description || '',
  }
  modals.value.impact = true
}

const editRisk = (risk) => {
  editingRisk.value = { ...risk }
  forms.value.risk = { ...risk }
  modals.value.riskEditor = true
}

const openAIModal = (risk) => {
  editingRisk.value = risk
  ai.value.content = ''
  modals.value.ai = true
}

const generateAISuggestions = () => {
  ai.value.loading = true
  setTimeout(() => {
    ai.value.content = `
<strong>1. Action immédiate</strong><br>
Évaluation rapide de la situation | Identification des points critiques | Mise en place de contrôles temporaires<br><br>
<strong>2. Plan court terme (30j)</strong><br>
Mesures de sécurité essentielles | Formation des collaborateurs | Mise à jour documentation<br><br>
<strong>3. Plan pérennisation (3-6 mois)</strong><br>
Intégration dans processus standard | Audit et vérification | Amélioration continue
    `
    ai.value.loading = false
  }, 1500)
}

const updateFrequencyLabel = () => {
  const freq = frequencies.value.find(f => f.level === parseInt(forms.value.frequency.level))
  if (freq) {
    forms.value.frequency.label = freq.label
    forms.value.frequency.description = freq.description
  }
}

const updateImpactLabel = () => {
  const imp = impacts.value.find(i => i.level === parseInt(forms.value.impact.level))
  if (imp) {
    forms.value.impact.label = imp.label
    forms.value.impact.description = imp.description
  }
}

const saveFrequency = () => {
  if (editingRisk.value) {
    const freq = frequencies.value.find(f => f.level === parseInt(forms.value.frequency.level))
    editingRisk.value.frequency_code = freq?.code || ''
    recalculateCriticality(editingRisk.value)
  }
  alert('✓ Fréquence enregistrée')
}

const saveImpact = () => {
  if (editingRisk.value) {
    const imp = impacts.value.find(i => i.level === parseInt(forms.value.impact.level))
    editingRisk.value.impact_code = imp?.code || ''
    recalculateCriticality(editingRisk.value)
  }
  alert('✓ Impact enregistré')
}

const saveRisk = () => {
  if (editingRisk.value?.id) {
    Object.assign(editingRisk.value, forms.value.risk)
    alert('✓ Risque enregistré')
  } else {
    const newRisk = {
      id: Math.max(...risks.value.map(r => r.id), 0) + 1,
      ...forms.value.risk,
      status: 'active',
      criticality: 0,
    }
    risks.value.push(newRisk)
    alert('✓ Risque créé')
  }
}

const deleteRisk = (risk) => {
  if (confirm(`Supprimer "${risk.label}"?`)) {
    risks.value = risks.value.filter(r => r.id !== risk.id)
    alert('✓ Risque supprimé')
  }
}

const recalculateCriticality = (risk) => {
  const freq = frequencies.value.find(f => f.code === risk.frequency_code)
  const imp = impacts.value.find(i => i.code === risk.impact_code)
  if (freq && imp) {
    risk.criticality = freq.level * imp.level
  }
}

const getTypeColor = (code) => {
  return riskTypes.value.find(t => t.code === code)?.bgColor || 'secondary'
}

const getFrequencyLabel = (code) => {
  return frequencies.value.find(f => f.code === code)?.label || code || '?'
}

const getImpactLabel = (code) => {
  return impacts.value.find(i => i.code === code)?.label || code || '?'
}

const getProcessLabel = (id) => {
  return processes.value.find(p => p.id === id)?.label || '-'
}

const getCriticalityColor = (criticality) => {
  if (criticality >= 12) return 'danger'
  if (criticality >= 8) return 'warning'
  return 'success'
}

const formatAIResponse = (text) => {
  return text.replace(/\n/g, '')
}

const exportData = () => {
  const csv = 'Code,Libellé,Type,Fréquence,Impact,Criticité,Propriétaire,Processus,Statut\n' +
    risksFiltrés.value.map(r => `${r.code},${r.label},${r.type_code},${getFrequencyLabel(r.frequency_code)},${getImpactLabel(r.impact_code)},${r.criticality},${r.owner},${getProcessLabel(r.process_id)},${r.status}`).join('\n')
  
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'risques_export.csv'
  a.click()
}
</script>

<style scoped>
h3 {
  font-size: 1.25rem;
}

:deep(.gap-2) {
  gap: 0.5rem;
}

:deep(.flex-wrap) {
  flex-wrap: wrap;
}

:deep(.ai-content) {
  font-size: 0.9rem;
  line-height: 1.6;
}

:deep(.table-hover tbody tr:hover) {
  background-color: rgba(0, 0, 0, 0.05);
}
</style>