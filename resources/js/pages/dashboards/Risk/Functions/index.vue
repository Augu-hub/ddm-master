<template>
  <AuditModuleLayout title="Gestion Risques" :breadcrumbs="['Audit', 'Risques']" moduleCode="audit">
    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <!-- HEADER AVEC BOUTONS -->
    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="fw-bold text-danger mb-0">🔴 Gestion Audits & Risques</h4>
      <div class="d-flex gap-2">
        <b-button size="sm" variant="primary" @click="openCreateRisk" class="px-2">➕ Créer Risque</b-button>
        <b-button size="sm" variant="success" @click="modals.matrixGlobal = true" class="px-2">📊 Matrice</b-button>
        <b-button size="sm" variant="warning" @click="modals.frequency = true" class="px-2">📈 Fréquence</b-button>
        <b-button size="sm" variant="info" @click="modals.impact = true" class="px-2">📉 Impact</b-button>
        <b-button size="sm" variant="secondary" @click="modals.types = true" class="px-2">🏷️ Types</b-button>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <!-- PAGE PRINCIPALE: IDENTIFICATION DES RISQUES -->
    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <div class="card p-2 mb-2" style="border-radius: 0.25rem;">
      <h6 class="fw-bold mb-2" style="font-size: 0.85rem; color: #667eea;">Info mission</h6>
      
      <div class="row g-2">
        <div class="col-md-4">
          <small class="fw-bold" style="font-size: 0.7rem;">Code projet</small>
          <b-form-select v-model="filters.project_code" :options="projectOptions" size="sm"></b-form-select>
        </div>
        <div class="col-md-4">
          <small class="fw-bold" style="font-size: 0.7rem;">Entité</small>
          <b-form-select v-model="filters.entity" :options="entityOptions" size="sm"></b-form-select>
        </div>
        <div class="col-md-4">
          <small class="fw-bold" style="font-size: 0.7rem;">Année</small>
          <b-form-select v-model="filters.year" :options="yearOptions" size="sm"></b-form-select>
        </div>
      </div>
    </div>

    <!-- TABLEAU IDENTIFICATION -->
    <b-table 
      :items="filteredRisks" 
      :fields="identificationFields"
      responsive 
      hover 
      striped
      small 
      class="mb-0"
      style="font-size: 0.7rem;"
    >
      <template #thead-top="scope">
        <tr class="bg-primary text-white">
          <th v-for="field in scope.fields" :key="`th_${field.key}`" class="fw-bold p-1" style="font-size: 0.65rem;">
            {{ field.label }}
          </th>
        </tr>
      </template>

      <template #cell(entity)="row">
        <small><strong>{{ row.item.entity }}</strong></small>
      </template>

      <template #cell(process)="row">
        <small>{{ row.item.process }}</small>
      </template>

      <template #cell(risk)="row">
        <small><strong>{{ row.item.label }}</strong></small>
      </template>

      <template #cell(impact_brut)="row">
        <b-badge :bg="getImpactColor(row.item.impact_code)" class="px-1" style="font-size: 0.6rem;">{{ row.item.impact_code }}</b-badge>
      </template>

      <template #cell(freq_brut)="row">
        <b-badge :bg="getFreqColor(row.item.frequency_code)" class="px-1" style="font-size: 0.6rem;">{{ row.item.frequency_code }}</b-badge>
      </template>

      <template #cell(global_brut)="row">
        <b-badge :bg="getCriticalityColor(row.item.criticality)" style="font-size: 0.6rem;">{{ row.item.criticality }}</b-badge>
      </template>

      <template #cell(procedure)="row">
        <small>{{ row.item.control_procedure || '-' }}</small>
      </template>

      <template #cell(actions)="row">
        <b-button size="sm" variant="info" @click="editRisk(row.item)" class="px-1 py-0" style="font-size: 0.6rem;">✏️</b-button>
        <b-button size="sm" variant="danger" @click="deleteRisk(row.item)" class="px-1 py-0 ms-1" style="font-size: 0.6rem;">🗑️</b-button>
      </template>
    </b-table>

    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL: CRÉER RISQUE -->
    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <b-modal v-model="modals.createRisk" title="➕ Créer Risque" size="lg" centered hide-footer scrollable>
      <div class="row g-2">
        <!-- COLONNE GAUCHE: Filtres -->
        <div class="col-md-5" style="border-right: 1px solid #dee2e6; padding-right: 1rem;">
          <h6 class="fw-bold mb-2" style="font-size: 0.85rem; color: #667eea;">Contexte</h6>
          
          <small class="fw-bold" style="font-size: 0.7rem;">Projet</small>
          <b-form-select v-model="newRiskForm.project_code" :options="projectOptions" size="sm" class="mb-2" @change="updateEntities"></b-form-select>

          <small class="fw-bold" style="font-size: 0.7rem;">Entité</small>
          <b-form-select v-model="newRiskForm.entity" :options="filteredEntities" size="sm" class="mb-2"></b-form-select>

          <small class="fw-bold" style="font-size: 0.7rem;">Univers de Risque</small>
          <b-form-select v-model="newRiskForm.type_code" :options="typeSelectOptions" size="sm" class="mb-2"></b-form-select>

          <small class="fw-bold" style="font-size: 0.7rem;">Processus</small>
          <b-form-select v-model="newRiskForm.process_id" :options="processOptions" size="sm" class="mb-2"></b-form-select>
        </div>

        <!-- COLONNE DROITE: Formulaire Risque -->
        <div class="col-md-7" style="padding-left: 1rem;">
          <h6 class="fw-bold mb-2" style="font-size: 0.85rem; color: #667eea;">Identification Risque</h6>
          
          <small class="fw-bold" style="font-size: 0.7rem;">Code risque</small>
          <b-form-input v-model="newRiskForm.code" size="sm" placeholder="RC-001" class="mb-2"></b-form-input>

          <small class="fw-bold" style="font-size: 0.7rem;">Libellé risque</small>
          <b-form-input v-model="newRiskForm.label" size="sm" placeholder="Description du risque" class="mb-2"></b-form-input>

          <small class="fw-bold" style="font-size: 0.7rem;">Fréquence</small>
          <b-form-select v-model="newRiskForm.frequency_code" :options="freqSelectOptions" size="sm" class="mb-2"></b-form-select>

          <small class="fw-bold" style="font-size: 0.7rem;">Impact</small>
          <b-form-select v-model="newRiskForm.impact_code" :options="impactSelectOptions" size="sm" class="mb-2"></b-form-select>

          <small class="fw-bold" style="font-size: 0.7rem;">Propriétaire</small>
          <b-form-input v-model="newRiskForm.owner" size="sm" placeholder="Nom propriétaire" class="mb-2"></b-form-input>

          <small class="fw-bold" style="font-size: 0.7rem;">Procédure de contrôle</small>
          <b-form-input v-model="newRiskForm.control_procedure" size="sm" placeholder="Procédure" class="mb-2"></b-form-input>

          <div class="text-end"><b-button size="sm" variant="secondary" @click="modals.createRisk = false">Annuler</b-button> <b-button size="sm" variant="success" @click="saveNewRisk" class="ms-1">Créer</b-button></div>
        </div>
      </div>
    </b-modal>

    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL: MATRICE GLOBALE -->
    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <b-modal v-model="modals.matrixGlobal" title="📊 Matrice globale" size="lg" centered hide-footer scrollable>
      <div class="card p-2 mb-2" style="border-radius: 0.25rem;">
        <h6 class="fw-bold mb-2" style="font-size: 0.85rem; color: #667eea;">Saisie cotation nette</h6>
        
        <div class="row g-2">
          <div class="col-md-12">
            <small class="fw-bold" style="font-size: 0.7rem;">Code projet</small>
            <b-form-select v-model="matrixForm.project_code" :options="projectOptions" size="sm"></b-form-select>
          </div>
          <div class="col-md-6">
            <small class="fw-bold" style="font-size: 0.7rem;">Niveau impact</small>
            <b-form-input v-model.number="matrixForm.impact_level" type="number" min="1" max="5" size="sm" placeholder="0"></b-form-input>
          </div>
          <div class="col-md-6">
            <small class="fw-bold" style="font-size: 0.7rem;">Niveau fréquence</small>
            <b-form-input v-model.number="matrixForm.freq_level" type="number" min="1" max="4" size="sm" placeholder="0"></b-form-input>
          </div>
          <div class="col-md-12">
            <small class="fw-bold" style="font-size: 0.7rem;">Appellation</small>
            <b-form-input v-model="matrixForm.label" size="sm" placeholder="Nom"></b-form-input>
          </div>
          <div class="col-md-12">
            <small class="fw-bold" style="font-size: 0.7rem;">Qualification</small>
            <div style="background: linear-gradient(90deg, #FFD700 0%, #FFD700 100%); height: 25px; border-radius: 0.25rem; margin-bottom: 0.5rem;"></div>
            <input v-model="matrixForm.qualification" type="color" class="form-control form-control-sm" style="height: 1.8rem;">
          </div>
          <div class="col-md-12 text-end"><b-button size="sm" variant="secondary" @click="resetMatrix">Annuler</b-button> <b-button size="sm" variant="success" @click="addMatrix" class="ms-1">Valider</b-button></div>
        </div>
      </div>

      <div class="card p-2" style="border-radius: 0.25rem;">
        <h6 class="fw-bold mb-2" style="font-size: 0.85rem; color: #667eea;">Liste cotation nette</h6>
        <b-table :items="matrixLevels" :fields="matrixFields" responsive hover small style="font-size: 0.7rem; margin-bottom: 0;">
          <template #cell(impact)="row"><b-badge :bg="row.item.impact_color" style="font-size: 0.6rem;">{{ row.item.impact_level }}</b-badge></template>
          <template #cell(freq)="row"><b-badge :bg="row.item.freq_color" style="font-size: 0.6rem;">{{ row.item.freq_level }}</b-badge></template>
          <template #cell(qualification)="row"><div :style="{ backgroundColor: row.item.qualification, height: '20px', borderRadius: '0.2rem' }"></div></template>
        </b-table>
      </div>
    </b-modal>

    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL: COTATION FRÉQUENCE -->
    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <b-modal v-model="modals.frequency" title="📈 Cotation fréquence" size="lg" centered hide-footer scrollable>
      <div class="card p-2 mb-2" style="border-radius: 0.25rem;">
        <h6 class="fw-bold mb-2" style="font-size: 0.85rem; color: #667eea;">Saisie cotation nette</h6>
        
        <div class="row g-2">
          <div class="col-md-12">
            <small class="fw-bold" style="font-size: 0.7rem;">Code projet</small>
            <b-form-select v-model="freqForm.project_code" :options="projectOptions" size="sm"></b-form-select>
          </div>
          <div class="col-md-12">
            <small class="fw-bold" style="font-size: 0.7rem;">Niveau fréquence</small>
            <b-form-input v-model.number="freqForm.level" type="range" min="1" max="4" size="sm" @input="updateFreqLabel"></b-form-input>
            <small class="text-muted" style="font-size: 0.65rem;">{{ ['', 'RARE', 'PROBABLE', 'FREQUENT', 'CERTAIN'][freqForm.level] }}</small>
          </div>
          <div class="col-md-12">
            <small class="fw-bold" style="font-size: 0.7rem;">Appellation</small>
            <b-form-input v-model="freqForm.label" size="sm" placeholder="Nom"></b-form-input>
          </div>
          <div class="col-md-12">
            <small class="fw-bold" style="font-size: 0.7rem;">Description</small>
            <b-form-input v-model="freqForm.description" size="sm" placeholder="Description"></b-form-input>
          </div>
          <div class="col-md-12 text-end"><b-button size="sm" variant="secondary" @click="resetFreq">Annuler</b-button> <b-button size="sm" variant="success" @click="addFreq" class="ms-1">Valider</b-button></div>
        </div>
      </div>

      <div class="card p-2" style="border-radius: 0.25rem;">
        <h6 class="fw-bold mb-2" style="font-size: 0.85rem; color: #667eea;">Liste cotation nette</h6>
        <b-table :items="frequencies" :fields="['level','label','description']" responsive hover small style="font-size: 0.7rem; margin-bottom: 0;">
          <template #cell(level)="row"><b-badge :bg="row.item.color" style="font-size: 0.6rem;">{{ row.item.level }}</b-badge></template>
        </b-table>
      </div>
    </b-modal>

    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL: COTATION IMPACT -->
    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <b-modal v-model="modals.impact" title="📉 Cotation impact" size="lg" centered hide-footer scrollable>
      <div class="card p-2 mb-2" style="border-radius: 0.25rem;">
        <h6 class="fw-bold mb-2" style="font-size: 0.85rem; color: #667eea;">Saisie niveaux risque</h6>
        
        <div class="row g-2">
          <div class="col-md-12">
            <small class="fw-bold" style="font-size: 0.7rem;">Code projet</small>
            <b-form-select v-model="impactForm.project_code" :options="projectOptions" size="sm"></b-form-select>
          </div>
          <div class="col-md-12">
            <small class="fw-bold" style="font-size: 0.7rem;">Niveau impact</small>
            <b-form-input v-model.number="impactForm.level" type="range" min="1" max="5" size="sm" @input="updateImpactLabel"></b-form-input>
            <small class="text-muted" style="font-size: 0.65rem;">{{ ['', 'FAIBLE', 'MODERE', 'IMPORTANT', 'ELEVE', 'MOYEN'][impactForm.level] }}</small>
          </div>
          <div class="col-md-12">
            <small class="fw-bold" style="font-size: 0.7rem;">Appellation</small>
            <b-form-input v-model="impactForm.label" size="sm" placeholder="Nom"></b-form-input>
          </div>
          <div class="col-md-12">
            <small class="fw-bold" style="font-size: 0.7rem;">Description</small>
            <b-form-input v-model="impactForm.description" size="sm" placeholder="Description"></b-form-input>
          </div>
          <div class="col-md-12 text-end"><b-button size="sm" variant="secondary" @click="resetImpact">Annuler</b-button> <b-button size="sm" variant="success" @click="addImpact" class="ms-1">Valider</b-button></div>
        </div>
      </div>

      <div class="card p-2" style="border-radius: 0.25rem;">
        <h6 class="fw-bold mb-2" style="font-size: 0.85rem; color: #667eea;">Liste niveau risque</h6>
        <b-table :items="impacts" :fields="['level','label','description']" responsive hover small style="font-size: 0.7rem; margin-bottom: 0;">
          <template #cell(level)="row"><b-badge :bg="row.item.color" style="font-size: 0.6rem;">{{ row.item.level }}</b-badge></template>
        </b-table>
      </div>
    </b-modal>

    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL: GESTION TYPES -->
    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <b-modal v-model="modals.types" title="🏷️ Gestion des types de risques" size="lg" centered hide-footer scrollable>
      <div class="card p-2 mb-2" style="border-radius: 0.25rem;">
        <h6 class="fw-bold mb-2" style="font-size: 0.85rem; color: #667eea;">Saisie des types de risque</h6>
        
        <div class="row g-2">
          <div class="col-md-12">
            <small class="fw-bold" style="font-size: 0.7rem;">Code projet</small>
            <b-form-select v-model="typeForm.project_code" :options="projectOptions" size="sm"></b-form-select>
          </div>
          <div class="col-md-12">
            <small class="fw-bold" style="font-size: 0.7rem;">Code type</small>
            <b-form-input v-model="typeForm.code" size="sm" placeholder="RC, RF, RI..."></b-form-input>
          </div>
          <div class="col-md-12">
            <small class="fw-bold" style="font-size: 0.7rem;">Libellé type</small>
            <b-form-input v-model="typeForm.label" size="sm" placeholder="Description"></b-form-input>
          </div>
          <div class="col-md-12 text-end"><b-button size="sm" variant="secondary" @click="resetType">Annuler</b-button> <b-button size="sm" variant="success" @click="addType" class="ms-1">Valider</b-button></div>
        </div>
      </div>

      <div class="card p-2" style="border-radius: 0.25rem;">
        <h6 class="fw-bold mb-2" style="font-size: 0.85rem; color: #667eea;">Liste des types de risque</h6>
        <b-table :items="riskTypes" :fields="['code','label']" responsive hover small style="font-size: 0.7rem; margin-bottom: 0;">
          <template #cell(code)="row"><b-badge :bg="row.item.color" style="font-size: 0.6rem;">{{ row.item.code }}</b-badge></template>
        </b-table>
      </div>
    </b-modal>

    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL: ÉDITION RISQUE -->
    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <b-modal v-model="modals.editRisk" title="✏️ Edition risque" size="xl" centered hide-footer scrollable>
      <div class="row g-2">
        <div class="col-md-5" style="border-right: 1px solid #dee2e6; padding-right: 1rem;">
          <h6 class="fw-bold mb-2" style="font-size: 0.85rem; color: #667eea;">Edition Risque</h6>
          
          <small class="fw-bold" style="font-size: 0.7rem;">Projet</small>
          <div class="mb-2" style="background: linear-gradient(90deg, #FFD700 0%, #FFD700 100%); height: 25px; border-radius: 0.25rem;"></div>
          <b-form-select v-model="editingRisk.project_id" :options="projectOptions" size="sm" class="mb-2"></b-form-select>

          <small class="fw-bold d-block mb-2" style="font-size: 0.7rem;">Processus</small>
          <b-list-group style="font-size: 0.7rem;">
            <b-list-group-item v-for="p in processes" :key="p.id" button @click="editingRisk.process_id = p.id" :active="editingRisk.process_id === p.id" class="p-1">
              <strong>{{ p.code }}</strong> {{ p.name }}
            </b-list-group-item>
          </b-list-group>
        </div>

        <div class="col-md-7" style="padding-left: 1rem;">
          <h6 class="fw-bold mb-2" style="font-size: 0.85rem; color: #667eea;">Edition Risque</h6>
          
          <small class="fw-bold" style="font-size: 0.7rem;">Code risque</small>
          <b-form-input v-model="editingRisk.code" size="sm" placeholder="RC-001" class="mb-2"></b-form-input>

          <small class="fw-bold" style="font-size: 0.7rem;">Type</small>
          <b-form-select v-model="editingRisk.type_code" :options="typeSelectOptions" size="sm" class="mb-2"></b-form-select>

          <small class="fw-bold" style="font-size: 0.7rem;">Risque</small>
          <b-form-input v-model="editingRisk.label" size="sm" placeholder="Description" class="mb-2"></b-form-input>

          <small class="fw-bold" style="font-size: 0.7rem;">Code activité</small>
          <b-form-input v-model="editingRisk.activity_code" size="sm" placeholder="Code" class="mb-2"></b-form-input>

          <div class="text-end"><b-button size="sm" variant="secondary" @click="modals.editRisk = false">Annuler</b-button> <b-button size="sm" variant="success" @click="saveEditedRisk" class="ms-1">Valider</b-button></div>
        </div>
      </div>

      <div class="card p-2 mt-2" style="border-radius: 0.25rem;">
        <h6 class="fw-bold mb-2" style="font-size: 0.85rem; color: #667eea;">Liste des risques</h6>
        <b-table :items="risks" :fields="['code','label','type_code','activity_code','process']" responsive hover small style="font-size: 0.7rem; margin-bottom: 0;">
          <template #cell(type_code)="row"><b-badge :bg="getTypeColor(row.item.type_code)" style="font-size: 0.6rem;">{{ row.item.type_code }}</b-badge></template>
        </b-table>
      </div>
    </b-modal>

  </AuditModuleLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import AuditModuleLayout from '@/layoutsparam/AuditModuleLayout.vue'

// DONNÉES
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
  { id: 1, code: 'P001', name: 'Processus de vente' },
  { id: 2, code: 'P002', name: 'Processus de production' },
  { id: 3, code: 'P003', name: 'Processus RH' },
  { id: 4, code: 'P004', name: 'Processus financier' },
  { id: 5, code: 'P005', name: 'Processus IT' },
])

const projects = ref([
  { code: 'KEKELI', name: 'KEKELI', entities: ['Finance', 'RH', 'IT', 'Commercial'] },
])

const risks = ref([
  { id: 1, code: 'RC-001', label: 'RGPD', type_code: 'RC', frequency_code: 'PROBABLE', impact_code: 'ELEVE', entity: 'Finance', process: 'Paiement', owner: 'Dupont', control_procedure: 'Audit interne', criticality: 8, project_code: 'KEKELI', year: 2026, activity_code: 'ACT01', process_id: 4 },
  { id: 2, code: 'RC-002', label: 'Contrôle interne', type_code: 'RC', frequency_code: 'FREQUENT', impact_code: 'IMPORTANT', entity: 'RH', process: 'Paie', owner: 'Martin', control_procedure: 'Test', criticality: 9, project_code: 'KEKELI', year: 2026, activity_code: 'ACT02', process_id: 3 },
  { id: 3, code: 'RI-001', label: 'Cyberattaque', type_code: 'RI', frequency_code: 'FREQUENT', impact_code: 'MOYEN', entity: 'IT', process: 'Infrastructure', owner: 'Mercier', control_procedure: 'MFA', criticality: 20, project_code: 'KEKELI', year: 2026, activity_code: 'ACT03', process_id: 5 },
])

const matrixLevels = ref([
  { impact_level: 2, freq_level: 1, qualification: '#90EE90', impact_color: 'info', freq_color: 'success' },
  { impact_level: 3, freq_level: 1, qualification: '#FFD700', impact_color: 'warning', freq_color: 'success' },
  { impact_level: 4, freq_level: 3, qualification: '#FF6347', impact_color: 'danger', freq_color: 'danger' },
])

// FORMS
const filters = ref({ project_code: 'KEKELI', entity: '', year: 2026 })
const newRiskForm = ref({ project_code: 'KEKELI', entity: '', type_code: '', process_id: null, code: '', label: '', frequency_code: '', impact_code: '', owner: '', control_procedure: '' })
const matrixForm = ref({ project_code: 'KEKELI', impact_level: 0, freq_level: 0, label: '', qualification: '#FFD700' })
const freqForm = ref({ project_code: 'KEKELI', level: 1, label: '', description: '' })
const impactForm = ref({ project_code: 'KEKELI', level: 1, label: '', description: '' })
const typeForm = ref({ project_code: 'KEKELI', code: '', label: '' })
const editingRisk = ref({ project_id: 'KEKELI', process_id: null, code: '', type_code: '', label: '', activity_code: '' })

const modals = ref({ createRisk: false, matrixGlobal: false, frequency: false, impact: false, types: false, editRisk: false })

// OPTIONS
const projectOptions = computed(() => [{ value: 'KEKELI', text: 'KEKELI' }])
const entityOptions = computed(() => {
  const proj = projects.value.find(p => p.code === filters.value.project_code)
  return [{ value: '', text: 'Tous' }, ...(proj?.entities || []).map(e => ({ value: e, text: e }))]
})
const filteredEntities = computed(() => {
  const proj = projects.value.find(p => p.code === newRiskForm.value.project_code)
  return (proj?.entities || []).map(e => ({ value: e, text: e }))
})
const yearOptions = computed(() => [{ value: 2026, text: '2026' }, { value: 2025, text: '2025' }])
const processOptions = computed(() => processes.value.map(p => ({ value: p.id, text: `${p.code} - ${p.name}` })))
const typeSelectOptions = computed(() => [{ value: '', text: 'Type' }, ...riskTypes.value.map(t => ({ value: t.code, text: `${t.code} - ${t.label}` }))])
const freqSelectOptions = computed(() => [{ value: '', text: 'Fréquence' }, ...frequencies.value.map(f => ({ value: f.code, text: f.label }))])
const impactSelectOptions = computed(() => [{ value: '', text: 'Impact' }, ...impacts.value.map(i => ({ value: i.code, text: i.label }))])

// FIELDS
const identificationFields = computed(() => [
  { key: 'entity', label: 'ENTITE' },
  { key: 'process', label: 'PROCESSUS' },
  { key: 'risk', label: 'RISQUE' },
  { key: 'impact_brut', label: 'IMPACT_BRUT' },
  { key: 'freq_brut', label: 'FREQ_BRUT' },
  { key: 'global_brut', label: 'GLOBAL_BRUT' },
  { key: 'procedure', label: 'PROCEDURE DE CONTROLE' },
  { key: 'actions', label: '' },
])

const matrixFields = computed(() => [
  { key: 'impact', label: 'Niveau impact' },
  { key: 'freq', label: 'Niveau Fréquence' },
  { key: 'qualification', label: 'Appellation & qualification' },
])

// COMPUTED
const filteredRisks = computed(() => {
  return risks.value.filter(r => {
    const matchProject = !filters.value.project_code || r.project_code === filters.value.project_code
    const matchEntity = !filters.value.entity || r.entity === filters.value.entity
    const matchYear = !filters.value.year || r.year === filters.value.year
    return matchProject && matchEntity && matchYear
  })
})

// METHODS
const openCreateRisk = () => {
  newRiskForm.value = { project_code: 'KEKELI', entity: '', type_code: '', process_id: null, code: '', label: '', frequency_code: '', impact_code: '', owner: '', control_procedure: '' }
  modals.value.createRisk = true
}

const saveNewRisk = () => {
  if (newRiskForm.value.code && newRiskForm.value.label) {
    const criticality = (parseInt(frequencies.value.find(f => f.code === newRiskForm.value.frequency_code)?.level || 0) || 1) * (parseInt(impacts.value.find(i => i.code === newRiskForm.value.impact_code)?.level || 0) || 1)
    risks.value.push({
      id: Math.max(...risks.value.map(r => r.id), 0) + 1,
      ...newRiskForm.value,
      criticality: criticality,
      process: processes.value.find(p => p.id === newRiskForm.value.process_id)?.name || '-',
      project_code: newRiskForm.value.project_code,
      year: 2026,
      activity_code: '',
      status: 'active',
    })
    modals.value.createRisk = false
  }
}

const updateEntities = () => {
  newRiskForm.value.entity = ''
}

const updateFreqLabel = () => {
  const f = frequencies.value.find(f => f.level === parseInt(freqForm.value.level))
  if (f) { freqForm.value.label = f.label; freqForm.value.description = f.description }
}

const updateImpactLabel = () => {
  const i = impacts.value.find(i => i.level === parseInt(impactForm.value.level))
  if (i) { impactForm.value.label = i.label; impactForm.value.description = i.description }
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

const editRisk = (risk) => {
  editingRisk.value = { ...risk }
  modals.value.editRisk = true
}

const saveEditedRisk = () => {
  const idx = risks.value.findIndex(r => r.id === editingRisk.value.id)
  if (idx >= 0) { risks.value[idx] = { ...editingRisk.value } }
  modals.value.editRisk = false
}

const deleteRisk = (risk) => {
  if (confirm('Supprimer?')) { risks.value = risks.value.filter(r => r.id !== risk.id) }
}

const resetMatrix = () => { matrixForm.value = { project_code: 'KEKELI', impact_level: 0, freq_level: 0, label: '', qualification: '#FFD700' } }
const resetFreq = () => { freqForm.value = { project_code: 'KEKELI', level: 1, label: '', description: '' } }
const resetImpact = () => { impactForm.value = { project_code: 'KEKELI', level: 1, label: '', description: '' } }
const resetType = () => { typeForm.value = { project_code: 'KEKELI', code: '', label: '' } }

const getTypeColor = (code) => riskTypes.value.find(t => t.code === code)?.color || 'secondary'
const getFreqColor = (code) => frequencies.value.find(f => f.code === code)?.color || 'secondary'
const getImpactColor = (code) => impacts.value.find(i => i.code === code)?.color || 'secondary'
const getCriticalityColor = (criticality) => (criticality >= 12 ? 'danger' : criticality >= 8 ? 'warning' : 'success')
</script>

<style scoped>
:deep(.table) { font-size: 0.7rem; margin-bottom: 0; }
:deep(.table th) { background-color: #0066cc; color: white; padding: 0.3rem 0.4rem; border: 1px solid #0066cc; }
:deep(.table td) { padding: 0.3rem 0.4rem; border: 1px solid #dee2e6; }
:deep(.badge) { padding: 0.15rem 0.3rem; font-size: 0.6rem; }
:deep(.btn-sm) { padding: 0.15rem 0.3rem; font-size: 0.65rem; }
:deep(.form-control-sm, .form-select-sm) { height: 1.8rem; font-size: 0.7rem; }
:deep(.card) { border: 1px solid #dee2e6; }
:deep(.list-group-item) { padding: 0.4rem 0.5rem; font-size: 0.7rem; }
:deep(.list-group-item.active) { background-color: #0d6efd; border-color: #0d6efd; }
</style>