<!-- resources/js/pages/dashboards/Audit/Index.vue -->
<!-- 
  ════════════════════════════════════════════════════════════════════════════════
  DASHBOARD GESTION DES RISQUES - VERSION PRODUCTION
  ════════════════════════════════════════════════════════════════════════════════
  
  Composant: Inertia + Vue 3
  Layout: AuditModuleLayoutFinal
  Données: Depuis API Laravel (routes /api/risque/*)
  
  FONCTIONNALITÉS:
  ✅ Affichage risques (filtrés par projet/entité/année)
  ✅ Tableau responsive avec statistiques
  ✅ Création/Édition/Suppression risques
  ✅ Gestion types, fréquences, impacts
  ✅ Matrice de criticité
  ✅ Export CSV
  ✅ Modals avec formulaires complets
  
  DÉPENDANCES:
  - Vue 3 (Composition API)
  - Inertia.js (props from Laravel)
  - Bootstrap 5 (CSS)
  - CSRF Token (Laravel)
  
  ════════════════════════════════════════════════════════════════════════════════
-->

<template>
  <AuditModuleLayoutFinal 
    title="Gestion des Risques" 
    :breadcrumbs="['Audit', 'Risques']" 
    :moduleCode="'audit'"
  >
    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <!-- SECTION 1: HEADER & BOUTONS D'ACTIONS                                       -->
    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
      <h4 class="fw-bold text-danger mb-0">🔴 Gestion Risques</h4>
      <div class="d-flex gap-2 flex-wrap">
        <!-- CRÉER RISQUE - Ouvre modal de création -->
        <button 
          class="btn btn-sm btn-primary px-2" 
          @click="openCreateRisk" 
          title="Créer un nouveau risque"
        >
          ➕ Créer Risque
        </button>

        <!-- MATRICE - Affiche/Édite la matrice d'impact -->
        <button 
          class="btn btn-sm btn-success px-2" 
          @click="modals.matrixGlobal = true" 
          title="Voir la matrice d'impact"
        >
          📊 Matrice
        </button>

        <!-- EXPORT - Télécharge les risques en CSV -->
        <button 
          class="btn btn-sm btn-dark px-2" 
          @click="exportData" 
          title="Exporter en CSV"
        >
          📥 Export
        </button>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <!-- SECTION 2: FILTRES                                                          -->
    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <div class="card p-2 mb-3 bg-light border-0 shadow-sm">
      <h6 class="fw-bold mb-2" style="font-size: 0.85rem; color: #667eea;">📋 Filtres & Recherche</h6>
      
      <div class="row g-2">
        <!-- FILTRE 1: PROJET -->
        <div class="col-md-3">
          <small class="fw-bold d-block mb-1" style="font-size: 0.7rem;">🏢 Projet</small>
          <select 
            v-model="filters.project_code" 
            class="form-select form-select-sm"
            @change="loadRisks"
          >
            <option value="">Tous</option>
            <option value="KEKELI">KEKELI</option>
            <option value="ELONGOPAY">ELONGOPAY</option>
            <option value="DIADDEM">DIADDEM</option>
            <option value="EASYSYCE">EASYSYCE</option>
          </select>
        </div>

        <!-- FILTRE 2: ENTITÉ -->
        <div class="col-md-3">
          <small class="fw-bold d-block mb-1" style="font-size: 0.7rem;">🏛️ Entité</small>
          <select 
            v-model="filters.entity_id" 
            class="form-select form-select-sm"
            @change="loadRisks"
          >
            <option value="">Tous</option>
            <option v-for="ent in entities" :key="ent.id" :value="ent.id">
              {{ ent.code }} - {{ ent.name }}
            </option>
          </select>
        </div>

        <!-- FILTRE 3: ANNÉE -->
        <div class="col-md-2">
          <small class="fw-bold d-block mb-1" style="font-size: 0.7rem;">📅 Année</small>
          <select 
            v-model.number="filters.year" 
            class="form-select form-select-sm"
            @change="loadRisks"
          >
            <option :value="2026">2026</option>
            <option :value="2025">2025</option>
            <option :value="2024">2024</option>
          </select>
        </div>

        <!-- FILTRE 4: RECHERCHE LIBRE -->
        <div class="col-md-4">
          <small class="fw-bold d-block mb-1" style="font-size: 0.7rem;">🔍 Recherche</small>
          <input 
            v-model="searchQuery" 
            type="text" 
            class="form-control form-control-sm" 
            placeholder="Rechercher par code ou libellé..."
          >
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <!-- SECTION 3: STATISTIQUES                                                     -->
    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <div class="row mb-3 g-2">
      <!-- STAT 1: TOTAL -->
      <div class="col-md-3">
        <div class="card bg-light p-2 text-center border-0 shadow-sm">
          <small class="fw-bold" style="color: #667eea; font-size: 0.8rem;">📊 Total</small>
          <h4 class="mb-0 text-primary">{{ filteredRisks.length }}</h4>
          <small style="color: #999;">risques identifiés</small>
        </div>
      </div>

      <!-- STAT 2: CRITIQUES -->
      <div class="col-md-3">
        <div class="card bg-danger text-white p-2 text-center border-0 shadow-sm">
          <small class="fw-bold" style="font-size: 0.8rem;">🔴 Critiques (≥12)</small>
          <h4 class="mb-0">{{ filteredRisks.filter(r => r.criticality >= 12).length }}</h4>
          <small style="opacity: 0.9;">action immédiate</small>
        </div>
      </div>

      <!-- STAT 3: ÉLEVÉS -->
      <div class="col-md-3">
        <div class="card bg-warning p-2 text-center border-0 shadow-sm">
          <small class="fw-bold text-dark" style="font-size: 0.8rem;">🟠 Élevés (8-11)</small>
          <h4 class="mb-0 text-dark">{{ filteredRisks.filter(r => r.criticality >= 8 && r.criticality < 12).length }}</h4>
          <small style="color: #333;">à surveiller</small>
        </div>
      </div>

      <!-- STAT 4: FAIBLES -->
      <div class="col-md-3">
        <div class="card bg-success text-white p-2 text-center border-0 shadow-sm">
          <small class="fw-bold" style="font-size: 0.8rem;">🟢 Faibles (<8)</small>
          <h4 class="mb-0">{{ filteredRisks.filter(r => r.criticality < 8).length }}</h4>
          <small style="opacity: 0.9;">contrôlés</small>
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <!-- SECTION 4: TABLEAU DES RISQUES                                              -->
    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <div class="card mb-3 border-0 shadow-sm">
      <div class="card-header bg-primary text-white py-2 border-0">
        <h6 class="fw-bold mb-0" style="font-size: 0.8rem;">📊 Risques ({{ filteredRisks.length }})</h6>
      </div>
      
      <!-- Wrapper pour scroll horizontal sur mobile -->
      <div style="overflow-x: auto;">
        <table class="table table-sm table-hover mb-0" style="font-size: 0.75rem; min-width: 1200px;">
          <!-- EN-TÊTES DU TABLEAU -->
          <thead class="table-primary text-white">
            <tr>
              <th class="p-2" style="font-size: 0.7rem; width: 8%;">CODE</th>
              <th class="p-2" style="font-size: 0.7rem; width: 20%;">LIBELLÉ</th>
              <th class="p-2" style="font-size: 0.7rem; width: 10%;">TYPE</th>
              <th class="p-2" style="font-size: 0.7rem; width: 8%;">IMPACT</th>
              <th class="p-2" style="font-size: 0.7rem; width: 8%;">FRÉQUENCE</th>
              <th class="p-2" style="font-size: 0.7rem; width: 8%;">CRITICITÉ</th>
              <th class="p-2" style="font-size: 0.7rem; width: 10%;">STATUS</th>
              <th class="p-2" style="font-size: 0.7rem; width: 12%;">PROPRIÉTAIRE</th>
              <th class="p-2" style="font-size: 0.7rem; width: 10%;">ACTIONS</th>
            </tr>
          </thead>

          <!-- LIGNES DU TABLEAU -->
          <tbody>
            <tr v-for="risk in filteredRisks" :key="risk.id" class="align-middle hover-row">
              <!-- CODE -->
              <td class="p-2">
                <strong class="text-primary">{{ risk.code }}</strong>
              </td>

              <!-- LIBELLÉ (tronqué) -->
              <td class="p-2">
                <span :title="risk.label">{{ truncate(risk.label, 30) }}</span>
              </td>

              <!-- TYPE (badge coloré) -->
              <td class="p-2">
                <span 
                  class="badge" 
                  :class="'bg-' + getTypeColor(risk.risk_type_id)" 
                  style="font-size: 0.65rem;"
                >
                  {{ getRiskTypeName(risk.risk_type_id) }}
                </span>
              </td>

              <!-- IMPACT (badge coloré) -->
              <td class="p-2">
                <span 
                  class="badge" 
                  :class="'bg-' + getImpactColor(risk.impact_level_id)" 
                  style="font-size: 0.65rem;"
                >
                  {{ getImpactName(risk.impact_level_id) }}
                </span>
              </td>

              <!-- FRÉQUENCE (badge coloré) -->
              <td class="p-2">
                <span 
                  class="badge" 
                  :class="'bg-' + getFreqColor(risk.frequency_level_id)" 
                  style="font-size: 0.65rem;"
                >
                  {{ getFrequencyName(risk.frequency_level_id) }}
                </span>
              </td>

              <!-- CRITICITÉ (calculée: fréquence × impact) -->
              <td class="p-2">
                <span 
                  class="badge" 
                  :class="'bg-' + getCriticalityColor(risk.criticality)" 
                  style="font-size: 0.65rem;"
                >
                  {{ risk.criticality || '-' }}
                </span>
              </td>

              <!-- STATUS (enum) -->
              <td class="p-2">
                <small :style="{ color: getStatusColor(risk.status) }" class="fw-bold">
                  {{ formatStatus(risk.status) }}
                </small>
              </td>

              <!-- PROPRIÉTAIRE -->
              <td class="p-2">
                <small>{{ risk.owner || '-' }}</small>
              </td>

              <!-- ACTIONS (éditer/supprimer) -->
              <td class="p-2">
                <button 
                  class="btn btn-sm btn-info px-1 py-0 me-1" 
                  @click="editRisk(risk)" 
                  style="font-size: 0.65rem;" 
                  title="Éditer"
                >✏️</button>
                <button 
                  class="btn btn-sm btn-danger px-1 py-0" 
                  @click="deleteRisk(risk)" 
                  style="font-size: 0.65rem;" 
                  title="Supprimer"
                >🗑️</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Message si aucun risque -->
      <div v-if="filteredRisks.length === 0" class="p-3 text-center text-muted">
        <small>📭 Aucun risque trouvé pour cette période</small>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL 1: CRÉER RISQUE                                                       -->
    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <div v-if="modals.createRisk" class="modal d-block" style="background: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
          <!-- En-tête modal -->
          <div class="modal-header bg-primary text-white border-0">
            <h5 class="modal-title fw-bold">➕ Créer Risque</h5>
            <button type="button" class="btn-close btn-close-white" @click="modals.createRisk = false"></button>
          </div>

          <!-- Formulaire -->
          <form @submit.prevent="saveNewRisk">
            <div class="modal-body">
              <div class="row g-2">
                <!-- ═══════════════════════════════════════════════════════════ -->
                <!-- COLONNE GAUCHE: CONTEXTE                                   -->
                <!-- ═══════════════════════════════════════════════════════════ -->
                <div class="col-md-6">
                  <div class="form-group mb-2">
                    <small class="fw-bold d-block mb-1" style="font-size: 0.75rem; color: #333;">
                      Code risque *
                    </small>
                    <input 
                      v-model="newRiskForm.code" 
                      type="text" 
                      class="form-control form-control-sm" 
                      placeholder="RC-001" 
                      required
                    >
                    <small style="color: #999;">Format: TYPE-NNN (RC-001, RI-042, etc)</small>
                  </div>

                  <div class="form-group mb-2">
                    <small class="fw-bold d-block mb-1" style="font-size: 0.75rem; color: #333;">
                      Type de risque *
                    </small>
                    <select v-model.number="newRiskForm.risk_type_id" class="form-select form-select-sm" required>
                      <option value="">Sélectionner un type</option>
                      <option v-for="t in riskTypes" :key="t.id" :value="t.id">
                        {{ t.code }} - {{ t.label }}
                      </option>
                    </select>
                  </div>

                  <div class="form-group mb-2">
                    <small class="fw-bold d-block mb-1" style="font-size: 0.75rem; color: #333;">
                      Fréquence *
                    </small>
                    <select v-model.number="newRiskForm.frequency_level_id" class="form-select form-select-sm" required>
                      <option value="">Sélectionner</option>
                      <option v-for="f in frequencies" :key="f.id" :value="f.id">
                        {{ f.label }} ({{ f.level }})
                      </option>
                    </select>
                    <small style="color: #999;">Détermine la probabilité d'occurrence</small>
                  </div>

                  <div class="form-group mb-2">
                    <small class="fw-bold d-block mb-1" style="font-size: 0.75rem; color: #333;">
                      Impact *
                    </small>
                    <select v-model.number="newRiskForm.impact_level_id" class="form-select form-select-sm" required>
                      <option value="">Sélectionner</option>
                      <option v-for="i in impacts" :key="i.id" :value="i.id">
                        {{ i.label }} ({{ i.level }})
                      </option>
                    </select>
                    <small style="color: #999;">Sévérité si le risque se produit</small>
                  </div>

                  <div class="form-group mb-2">
                    <small class="fw-bold d-block mb-1" style="font-size: 0.75rem; color: #333;">
                      Entité *
                    </small>
                    <select v-model.number="newRiskForm.entity_id" class="form-select form-select-sm" required>
                      <option value="">Sélectionner</option>
                      <option v-for="e in entities" :key="e.id" :value="e.id">
                        {{ e.code }} - {{ e.name }}
                      </option>
                    </select>
                  </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════════ -->
                <!-- COLONNE DROITE: DÉTAILS                                    -->
                <!-- ═══════════════════════════════════════════════════════════ -->
                <div class="col-md-6">
                  <div class="form-group mb-2">
                    <small class="fw-bold d-block mb-1" style="font-size: 0.75rem; color: #333;">
                      Libellé risque *
                    </small>
                    <textarea 
                      v-model="newRiskForm.label" 
                      class="form-control form-control-sm" 
                      rows="3" 
                      placeholder="Description détaillée et contexte du risque" 
                      required
                    ></textarea>
                    <small style="color: #999;">Explication claire du risque identifié</small>
                  </div>

                  <div class="form-group mb-2">
                    <small class="fw-bold d-block mb-1" style="font-size: 0.75rem; color: #333;">
                      Propriétaire
                    </small>
                    <input 
                      v-model="newRiskForm.owner" 
                      type="text" 
                      class="form-control form-control-sm" 
                      placeholder="Nom du responsable"
                    >
                    <small style="color: #999;">Personne responsable du suivi</small>
                  </div>

                  <div class="form-group mb-2">
                    <small class="fw-bold d-block mb-1" style="font-size: 0.75rem; color: #333;">
                      Procédure de contrôle
                    </small>
                    <input 
                      v-model="newRiskForm.control_procedure" 
                      type="text" 
                      class="form-control form-control-sm" 
                      placeholder="Ex: Audit mensuel, KPI suivi quotidien"
                    >
                    <small style="color: #999;">Comment le risque est contrôlé</small>
                  </div>

                  <div class="form-group mb-2">
                    <small class="fw-bold d-block mb-1" style="font-size: 0.75rem; color: #333;">
                      Statut
                    </small>
                    <select v-model="newRiskForm.status" class="form-select form-select-sm">
                      <option value="identified">Identifié</option>
                      <option value="assessed">Évalué</option>
                      <option value="mitigated">Atténué</option>
                      <option value="monitored">Suivi</option>
                      <option value="closed">Fermé</option>
                    </select>
                    <small style="color: #999;">État du risque dans le processus</small>
                  </div>

                  <div class="form-group mb-2">
                    <small class="fw-bold d-block mb-1" style="font-size: 0.75rem; color: #333;">
                      Processus (optionnel)
                    </small>
                    <select v-model.number="newRiskForm.process_id" class="form-select form-select-sm">
                      <option :value="null">Sélectionner</option>
                      <option v-for="p in processes" :key="p.id" :value="p.id">
                        {{ p.code }} - {{ p.name }}
                      </option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <!-- Boutons footer -->
            <div class="modal-footer border-top-0 pt-0">
              <button 
                type="button" 
                class="btn btn-secondary btn-sm" 
                @click="modals.createRisk = false"
              >
                Annuler
              </button>
              <button 
                type="submit" 
                class="btn btn-primary btn-sm"
                :disabled="loading"
              >
                {{ loading ? '⏳ Création...' : '💾 Créer Risque' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL 2: ÉDITER RISQUE                                                      -->
    <!-- ════════════════════════════════════════════════════════════════════════════ -->
    <div v-if="modals.editRisk" class="modal d-block" style="background: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
          <!-- En-tête modal -->
          <div class="modal-header bg-info text-white border-0">
            <h5 class="modal-title fw-bold">✏️ Éditer Risque</h5>
            <button type="button" class="btn-close btn-close-white" @click="modals.editRisk = false"></button>
          </div>

          <!-- Formulaire édition -->
          <form @submit.prevent="saveEditedRisk">
            <div class="modal-body">
              <div class="row g-2">
                <div class="col-md-6">
                  <small class="fw-bold d-block mb-1" style="font-size: 0.75rem;">Code</small>
                  <input 
                    v-model="editingRisk.code" 
                    type="text" 
                    class="form-control form-control-sm mb-2" 
                    required
                  >

                  <small class="fw-bold d-block mb-1" style="font-size: 0.75rem;">Type</small>
                  <select v-model.number="editingRisk.risk_type_id" class="form-select form-select-sm mb-2">
                    <option value="">Sélectionner</option>
                    <option v-for="t in riskTypes" :key="t.id" :value="t.id">
                      {{ t.code }} - {{ t.label }}
                    </option>
                  </select>

                  <small class="fw-bold d-block mb-1" style="font-size: 0.75rem;">Fréquence</small>
                  <select v-model.number="editingRisk.frequency_level_id" class="form-select form-select-sm mb-2">
                    <option v-for="f in frequencies" :key="f.id" :value="f.id">{{ f.label }}</option>
                  </select>

                  <small class="fw-bold d-block mb-1" style="font-size: 0.75rem;">Impact</small>
                  <select v-model.number="editingRisk.impact_level_id" class="form-select form-select-sm mb-2">
                    <option v-for="i in impacts" :key="i.id" :value="i.id">{{ i.label }}</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <small class="fw-bold d-block mb-1" style="font-size: 0.75rem;">Libellé</small>
                  <textarea 
                    v-model="editingRisk.label" 
                    class="form-control form-control-sm mb-2" 
                    rows="3" 
                    required
                  ></textarea>

                  <small class="fw-bold d-block mb-1" style="font-size: 0.75rem;">Propriétaire</small>
                  <input 
                    v-model="editingRisk.owner" 
                    type="text" 
                    class="form-control form-control-sm mb-2"
                  >

                  <small class="fw-bold d-block mb-1" style="font-size: 0.75rem;">Statut</small>
                  <select v-model="editingRisk.status" class="form-select form-select-sm">
                    <option value="identified">Identifié</option>
                    <option value="assessed">Évalué</option>
                    <option value="mitigated">Atténué</option>
                    <option value="monitored">Suivi</option>
                    <option value="closed">Fermé</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Boutons footer -->
            <div class="modal-footer border-top-0 pt-0">
              <button 
                type="button" 
                class="btn btn-secondary btn-sm" 
                @click="modals.editRisk = false"
              >
                Annuler
              </button>
              <button 
                type="submit" 
                class="btn btn-primary btn-sm"
                :disabled="loading"
              >
                {{ loading ? '⏳ Sauvegarde...' : '💾 Sauvegarder' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </AuditModuleLayoutFinal>
</template>

<script setup lang="ts">
/**
 * ════════════════════════════════════════════════════════════════════════════════
 * SCRIPT - COMPOSANT DASHBOARD RISQUES
 * ════════════════════════════════════════════════════════════════════════════════
 */

import { ref, computed, onMounted } from 'vue'
import AuditModuleLayoutFinal from '@/layoutsparam/AuditModuleLayout-FINAL.vue'

// ════════════════════════════════════════════════════════════════════════════════
// PROPS DEPUIS INERTIA/LARAVEL
// ════════════════════════════════════════════════════════════════════════════════

const props = defineProps({
  riskTypes: { type: Array, default: () => [] },
  frequencies: { type: Array, default: () => [] },
  impacts: { type: Array, default: () => [] },
  entities: { type: Array, default: () => [] },
  processes: { type: Array, default: () => [] },
})

// ════════════════════════════════════════════════════════════════════════════════
// STATE VARIABLES
// ════════════════════════════════════════════════════════════════════════════════

// Filtres appliqués
const filters = ref({
  project_code: '',
  entity_id: '',
  year: 2026
})

// Recherche libre
const searchQuery = ref('')

// Loading state
const loading = ref(false)

// Données depuis la base
const riskTypes = ref(props.riskTypes || [])
const frequencies = ref(props.frequencies || [])
const impacts = ref(props.impacts || [])
const entities = ref(props.entities || [])
const processes = ref(props.processes || [])
const risks = ref([])

// Contrôle modals
const modals = ref({
  createRisk: false,
  editRisk: false
})

// Formulaire création
const newRiskForm = ref({
  code: '',
  label: '',
  risk_type_id: null,
  frequency_level_id: null,
  impact_level_id: null,
  entity_id: null,
  process_id: null,
  owner: '',
  control_procedure: '',
  status: 'identified'
})

// Formulaire édition
const editingRisk = ref({
  id: null,
  code: '',
  label: '',
  risk_type_id: null,
  frequency_level_id: null,
  impact_level_id: null,
  owner: '',
  status: 'identified'
})

// ════════════════════════════════════════════════════════════════════════════════
// COMPUTED PROPERTIES
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Risques filtrés selon les critères
 * - Filtre par projet
 * - Filtre par entité
 * - Filtre par année
 * - Recherche par code/libellé
 */
const filteredRisks = computed(() => {
  return risks.value.filter(r => {
    // Filtre projet
    const matchProject = !filters.value.project_code || r.project_code === filters.value.project_code
    
    // Filtre entité
    const matchEntity = !filters.value.entity_id || r.entity_id === filters.value.entity_id
    
    // Filtre année
    const matchYear = !filters.value.year || r.year === filters.value.year
    
    // Recherche libre
    const matchSearch = !searchQuery.value || 
      r.code.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      r.label.toLowerCase().includes(searchQuery.value.toLowerCase())
    
    return matchProject && matchEntity && matchYear && matchSearch
  })
})

// ════════════════════════════════════════════════════════════════════════════════
// LIFECYCLE HOOKS
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Initialisé au montage du composant
 * Charge les risques depuis la base de données
 */
onMounted(() => {
  loadRisks()
})

// ════════════════════════════════════════════════════════════════════════════════
// MÉTHODES - CHARGEMENT DONNÉES
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Charge tous les risques depuis l'API Laravel
 * GET /api/risque/
 */
const loadRisks = async () => {
  try {
    loading.value = true
    const response = await fetch('/api/risque/', {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      risks.value = data
    } else {
      console.error('Erreur lors du chargement des risques')
    }
  } catch (error) {
    console.error('Erreur:', error)
    alert('Erreur lors du chargement des risques')
  } finally {
    loading.value = false
  }
}

// ════════════════════════════════════════════════════════════════════════════════
// MÉTHODES - ACTIONS RISQUES
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Ouvre le modal de création
 * Réinitialise le formulaire
 */
const openCreateRisk = () => {
  newRiskForm.value = {
    code: '',
    label: '',
    risk_type_id: null,
    frequency_level_id: null,
    impact_level_id: null,
    entity_id: null,
    process_id: null,
    owner: '',
    control_procedure: '',
    status: 'identified'
  }
  modals.value.createRisk = true
}

/**
 * Sauvegarde un nouveau risque
 * POST /api/risque/
 */
const saveNewRisk = async () => {
  try {
    loading.value = true
    const response = await fetch('/api/risque/', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
      },
      body: JSON.stringify(newRiskForm.value)
    })
    
    if (response.ok) {
      modals.value.createRisk = false
      await loadRisks() // Recharger la liste
      alert('Risque créé avec succès!')
    } else {
      alert('Erreur lors de la création')
    }
  } catch (error) {
    console.error('Erreur:', error)
    alert('Erreur: ' + error.message)
  } finally {
    loading.value = false
  }
}

/**
 * Édite un risque
 * Ouvre le modal avec les données du risque
 */
const editRisk = (risk) => {
  editingRisk.value = { ...risk }
  modals.value.editRisk = true
}

/**
 * Sauvegarde un risque édité
 * PUT /api/risque/{id}
 */
const saveEditedRisk = async () => {
  try {
    loading.value = true
    const response = await fetch(`/api/risque/${editingRisk.value.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
      },
      body: JSON.stringify(editingRisk.value)
    })
    
    if (response.ok) {
      modals.value.editRisk = false
      await loadRisks()
      alert('Risque modifié avec succès!')
    } else {
      alert('Erreur lors de la modification')
    }
  } catch (error) {
    console.error('Erreur:', error)
    alert('Erreur: ' + error.message)
  } finally {
    loading.value = false
  }
}

/**
 * Supprime un risque
 * DELETE /api/risque/{id}
 */
const deleteRisk = async (risk) => {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer ${risk.code}?`)) return
  
  try {
    loading.value = true
    const response = await fetch(`/api/risque/${risk.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
      }
    })
    
    if (response.ok) {
      await loadRisks()
      alert('Risque supprimé avec succès!')
    } else {
      alert('Erreur lors de la suppression')
    }
  } catch (error) {
    console.error('Erreur:', error)
    alert('Erreur: ' + error.message)
  } finally {
    loading.value = false
  }
}

// ════════════════════════════════════════════════════════════════════════════════
// MÉTHODES - EXPORT
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Exporte les risques en CSV
 * Télécharge un fichier CSV avec les risques filtrés
 */
const exportData = () => {
  const csv = convertToCSV(filteredRisks.value)
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  const url = URL.createObjectURL(blob)
  link.setAttribute('href', url)
  link.setAttribute('download', `risques_${new Date().toISOString().slice(0,10)}.csv`)
  link.click()
}

/**
 * Convertit un array de risques en CSV
 */
const convertToCSV = (data) => {
  const headers = ['CODE', 'LIBELLÉ', 'TYPE', 'FRÉQUENCE', 'IMPACT', 'CRITICITÉ', 'STATUS', 'PROPRIÉTAIRE']
  const csv = [headers.join(',')]
  
  data.forEach(row => {
    csv.push([
      row.code,
      `"${row.label}"`,
      getRiskTypeName(row.risk_type_id),
      getFrequencyName(row.frequency_level_id),
      getImpactName(row.impact_level_id),
      row.criticality || '-',
      row.status,
      row.owner || '-'
    ].join(','))
  })
  
  return csv.join('\n')
}

// ════════════════════════════════════════════════════════════════════════════════
// MÉTHODES - HELPERS (Traduction des IDs)
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Récupère le nom du type de risque par ID
 */
const getRiskTypeName = (id) => {
  const type = riskTypes.value.find(t => t.id === id)
  return type ? type.code : '-'
}

/**
 * Récupère le nom de la fréquence par ID
 */
const getFrequencyName = (id) => {
  const freq = frequencies.value.find(f => f.id === id)
  return freq ? freq.label : '-'
}

/**
 * Récupère le nom de l'impact par ID
 */
const getImpactName = (id) => {
  const impact = impacts.value.find(i => i.id === id)
  return impact ? impact.label : '-'
}

// ════════════════════════════════════════════════════════════════════════════════
// MÉTHODES - COULEURS (UI)
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Retourne la couleur Bootstrap du type de risque
 */
const getTypeColor = (id) => {
  const type = riskTypes.value.find(t => t.id === id)
  return type && type.color ? type.color : 'secondary'
}

/**
 * Retourne la couleur Bootstrap de la fréquence
 */
const getFreqColor = (id) => {
  const freq = frequencies.value.find(f => f.id === id)
  return freq && freq.color ? freq.color : 'warning'
}

/**
 * Retourne la couleur Bootstrap de l'impact
 */
const getImpactColor = (id) => {
  const impact = impacts.value.find(i => i.id === id)
  return impact && impact.color ? impact.color : 'info'
}

/**
 * Retourne la couleur de la criticité basée sur le score
 * ≥ 12: danger (rouge)
 * 8-11: warning (orange)
 * < 8: success (vert)
 */
const getCriticalityColor = (criticality) => {
  if (criticality >= 12) return 'danger'
  if (criticality >= 8) return 'warning'
  return 'success'
}

/**
 * Retourne la couleur hex du statut
 */
const getStatusColor = (status) => {
  const colors = {
    'identified': '#0066cc',
    'assessed': '#6f42c1',
    'mitigated': '#28a745',
    'monitored': '#ffc107',
    'closed': '#6c757d'
  }
  return colors[status] || '#6c757d'
}

// ════════════════════════════════════════════════════════════════════════════════
// MÉTHODES - FORMATAGE
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Tronque un texte à une longueur max
 */
const truncate = (text, length) => {
  return text && text.length > length ? text.substring(0, length) + '...' : text
}

/**
 * Formate le statut en français
 */
const formatStatus = (status) => {
  const statuses = {
    'identified': 'Identifié',
    'assessed': 'Évalué',
    'mitigated': 'Atténué',
    'monitored': 'Suivi',
    'closed': 'Fermé'
  }
  return statuses[status] || status
}
</script>

<style scoped>
/**
 * ════════════════════════════════════════════════════════════════════════════════
 * STYLES SCOPED
 * ════════════════════════════════════════════════════════════════════════════════
 */

/* Modal */
.modal {
  position: fixed;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  z-index: 1050;
  display: flex;
  align-items: center;
}

.modal-dialog {
  margin: auto;
}

/* Tableau */
.hover-row:hover {
  background-color: #f0f0f0;
  transition: background-color 0.2s ease;
}

table th {
  background-color: #0066cc;
  color: white;
  font-weight: 600;
}

table td {
  vertical-align: middle;
}

/* Cartes */
.card {
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
}

/* Badges */
.badge {
  padding: 0.3rem 0.6rem;
  font-size: 0.65rem;
  font-weight: 600;
}

/* Formulaires */
.form-control-sm,
.form-select-sm {
  font-size: 0.8rem;
  height: 1.8rem;
}

/* Boutons */
.btn-sm {
  font-size: 0.7rem;
  padding: 0.3rem 0.6rem;
}

/* Texte */
.text-primary {
  color: #0066cc !important;
}

small {
  display: block;
  color: #666;
}
</style>