<template>
  <div class="mission-phases-container">
    <!-- Header -->
    <div class="phases-header bg-white shadow-sm p-4 rounded mb-4">
      <div class="row align-items-center">
        <div class="col-md-6">
          <h2 class="mb-0">📋 Phases de Mission</h2>
        </div>
        <div class="col-md-6 text-end">
          <!-- Action Buttons -->
          <button 
            @click="expandAll" 
            class="btn btn-sm btn-success ms-2"
            title="Déplier tous"
          >
            ⬇️ Tout Déplier
          </button>
          <button 
            @click="collapseAll" 
            class="btn btn-sm btn-warning ms-2"
            title="Replier tous"
          >
            ⬆️ Tout Replier
          </button>
          <button 
            @click="openCreateModal(null)" 
            class="btn btn-sm btn-primary ms-2"
          >
            ➕ Nouvelle Phase
          </button>
        </div>
      </div>

      <!-- Statistics -->
      <div v-if="selectedAudit" class="row mt-3 text-muted small">
        <div class="col-4">📊 Total: {{ statistics.totalPhases }} phases</div>
        <div class="col-4">🔝 Racines: {{ statistics.mainPhases }}</div>
        <div class="col-4">⚖️ Poids: {{ statistics.totalWeight }}</div>
      </div>
    </div>

    <!-- Audit Type Selector - Cards -->
    <div v-if="!selectedAudit" class="audit-selector-section">
      <h3 class="mb-4 text-center">🎯 Sélectionnez un Type d'Audit</h3>
      
      <div class="audit-cards-grid">
        <div
          v-for="audit in missionTypes"
          :key="audit.id"
          @click="selectAudit(audit)"
          class="audit-card"
          :style="{ borderColor: getAuditColor(audit.id) }"
        >
          <div class="audit-icon" :style="{ backgroundColor: getAuditColor(audit.id) }">
            {{ getAuditIcon(audit.code) }}
          </div>
          <div class="audit-content">
            <h5 class="audit-code">{{ audit.code }}</h5>
            <p class="audit-label">{{ audit.label }}</p>
            <small class="audit-description">{{ getAuditDescription(audit.code) }}</small>
          </div>
          <div class="audit-arrow">→</div>
        </div>
      </div>
    </div>

    <!-- Phases Section -->
    <div v-if="selectedAudit" class="phases-section">
      <!-- Selected Audit Info Bar -->
      <div class="selected-audit-bar bg-light border-bottom p-3 mb-4 rounded">
        <div class="row align-items-center">
          <div class="col-md-6">
            <div class="selected-audit-info">
              <span class="audit-icon-small" :style="{ backgroundColor: getAuditColor(selectedAudit.id) }">
                {{ getAuditIcon(selectedAudit.code) }}
              </span>
              <div class="ms-3">
                <h6 class="mb-0">{{ selectedAudit.code }}</h6>
                <small class="text-muted">{{ selectedAudit.label }}</small>
              </div>
            </div>
          </div>
          <div class="col-md-6 text-end">
            <button 
              @click="deselectAudit"
              class="btn btn-sm btn-outline-secondary"
            >
              ← Changer d'Audit
            </button>
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="alert alert-info">
        ⏳ Chargement hiérarchie complète...
      </div>

      <!-- Error -->
      <div v-if="error" class="alert alert-danger alert-dismissible fade show">
        ❌ {{ error }}
        <button @click="error = null" class="btn-close"></button>
      </div>

      <!-- Success -->
      <div v-if="successMessage" class="alert alert-success alert-dismissible fade show">
        ✅ {{ successMessage }}
        <button @click="successMessage = null" class="btn-close"></button>
      </div>

      <!-- Phases Table -->
      <div class="phases-table-wrapper bg-white rounded shadow-sm p-3">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th style="width: 5%"></th>
              <th style="width: 12%">Code</th>
              <th style="width: 25%">Label</th>
              <th style="width: 35%">Description</th>
              <th style="width: 8%">Type</th>
              <th style="width: 15%">Actions</th>
            </tr>
          </thead>
          <tbody>
            <!-- Phases Racines -->
            <PhaseRow
              v-for="phase in hierarchy"
              :key="`phase-${phase.id}`"
              :phase="phase"
              :expanded-ids="expandedIds"
              :mission-type-id="selectedAudit.id"
              @toggle-expand="toggleExpand"
              @edit="editPhase"
              @delete="deletePhase"
              @create-child="openCreateModal"
            />
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-if="hierarchy.length === 0" class="text-center py-5 text-muted">
          <p>📭 Aucune phase trouvée pour ce type d'audit</p>
        </div>
      </div>
    </div>

    <!-- Modal Création/Edit Phase -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content">
        <div class="modal-header">
          <h5>{{ form.id ? '✏️ Modifier Phase' : '➕ Créer Phase' }}</h5>
          <button @click="closeModal" class="btn-close"></button>
        </div>

        <div class="modal-body">
          <!-- Step Indicators -->
          <div class="step-indicators mb-4">
            <div 
              v-for="(step, idx) in steps" 
              :key="idx"
              class="step"
              :class="{ active: currentStep === idx + 1, completed: currentStep > idx + 1 }"
            >
              <span class="step-number">{{ idx + 1 }}</span>
              <span class="step-label">{{ step }}</span>
            </div>
          </div>

          <!-- Step 1: Parent -->
          <div v-if="currentStep === 1">
            <h6>1️⃣ Parent de la phase?</h6>
            <select v-model="form.parent_id" class="form-select">
              <option :value="null">📍 Racine (Pas de parent)</option>
              <option v-for="phase in availableParents" :key="phase.id" :value="phase.id">
                {{ phase.code_full }} - {{ phase.label }}
              </option>
            </select>
          </div>

          <!-- Step 2: Code -->
          <div v-if="currentStep === 2">
            <h6>2️⃣ Code phase</h6>
            <input 
              v-model="form.code" 
              type="text" 
              class="form-control"
              placeholder="Ex: P1, E1, PREP"
              @keyup="generateCodeFull"
            >
            <small class="text-muted d-block mt-2">Code généré: <strong>{{ generatedCodeFull }}</strong></small>
          </div>

          <!-- Step 3: Label -->
          <div v-if="currentStep === 3">
            <h6>3️⃣ Libellé phase</h6>
            <input 
              v-model="form.label" 
              type="text" 
              class="form-control"
              placeholder="Ex: Fiche 1 : Déclenchement de la mission"
            >
          </div>

          <!-- Step 4: Description -->
          <div v-if="currentStep === 4">
            <h6>4️⃣ Description (Texte Fiche)</h6>
            <textarea 
              v-model="form.description" 
              class="form-control" 
              rows="4"
              placeholder="Description détaillée de la fiche ou phase..."
            ></textarea>
            <small class="text-muted">{{ form.description?.length || 0 }}/5000 caractères</small>
          </div>

          <!-- Step 5: Type & Logos -->
          <div v-if="currentStep === 5">
            <h6>5️⃣ Type Phase & Logos</h6>
            
            <div class="mb-3">
              <label class="form-label">Type Phase</label>
              <select v-model="form.phase_type" class="form-select">
                <option :value="null">-- Sélectionner un type --</option>
                <option value="PREPARATION">🟦 PREPARATION</option>
                <option value="VERIFICATION">🟩 VERIFICATION</option>
                <option value="CONCLUSION">🟨 CONCLUSION</option>
                <option value="SUIVI">🟪 SUIVI</option>
              </select>
            </div>

            <div class="row">
              <div class="col-6 mb-3">
                <label class="form-label">Logo Préparation</label>
                <input v-model="form.logo_preparation" type="text" class="form-control form-control-sm" placeholder="/chemin/logo">
              </div>
              <div class="col-6 mb-3">
                <label class="form-label">Logo Vérification</label>
                <input v-model="form.logo_verification" type="text" class="form-control form-control-sm" placeholder="/chemin/logo">
              </div>
              <div class="col-6 mb-3">
                <label class="form-label">Logo Conclusion</label>
                <input v-model="form.logo_conclusion" type="text" class="form-control form-control-sm" placeholder="/chemin/logo">
              </div>
              <div class="col-6 mb-3">
                <label class="form-label">Logo Suivi</label>
                <input v-model="form.logo_suivi" type="text" class="form-control form-control-sm" placeholder="/chemin/logo">
              </div>
            </div>
          </div>

          <!-- Step 6: Poids & Options -->
          <div v-if="currentStep === 6">
            <h6>6️⃣ Poids & Options</h6>
            <div class="mb-3">
              <label class="form-label">Poids (0-5)</label>
              <input 
                v-model.number="form.weight" 
                type="number" 
                min="0" 
                max="5" 
                class="form-control"
              >
            </div>
            <div class="form-check">
              <input v-model="form.is_decomposable" type="checkbox" class="form-check-input" id="decomposable">
              <label class="form-check-label" for="decomposable">
                Décomposable en sous-phases
              </label>
            </div>
          </div>

          <!-- Step 7: Confirmation -->
          <div v-if="currentStep === 7" class="alert alert-light">
            <h6>7️⃣ Confirmation</h6>
            <div class="row">
              <div class="col-6">
                <p><strong>Code:</strong> {{ generatedCodeFull }}</p>
                <p><strong>Type:</strong> {{ form.phase_type || '—' }}</p>
                <p><strong>Poids:</strong> {{ form.weight }}/5</p>
              </div>
              <div class="col-6">
                <p><strong>Label:</strong> {{ form.label }}</p>
                <p><strong>Description:</strong> {{ (form.description || '').substring(0, 50) }}...</p>
                <p><strong>Decomposable:</strong> {{ form.is_decomposable ? 'Oui' : 'Non' }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button 
            @click="previousStep" 
            class="btn btn-secondary"
            :disabled="currentStep === 1"
          >
            ← Précédent
          </button>
          <button 
            @click="nextStep" 
            class="btn btn-primary"
            v-if="currentStep < 7"
          >
            Suivant →
          </button>
          <button 
            @click="savePhase" 
            class="btn btn-success"
            v-if="currentStep === 7"
            :disabled="saving"
          >
            {{ saving ? '⏳ Sauvegarde...' : '💾 Enregistrer' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import PhaseRow from './PhaseRow.vue';

export default {
  name: 'MissionPhases',
  components: { PhaseRow },
  props: {
    missionTypes: Array,
    selectedTypeId: Number,
    hierarchyProp: Array,
    statistics: Object,
  },
  data() {
    return {
      selectedAudit: null,
      hierarchy: [],
      expandedIds: new Set(),
      loading: false,
      error: null,
      successMessage: null,
      showModal: false,
      saving: false,
      currentStep: 1,
      steps: ['Parent', 'Code', 'Label', 'Description', 'Type & Logos', 'Poids', 'Confirmation'],
      form: {
        id: null,
        code: '',
        label: '',
        description: '', // ✅ V4: Description fiche
        phase_type: null, // ✅ V4: Type phase
        logo_preparation: null, // ✅ V4: Logo prep
        logo_verification: null, // ✅ V4: Logo verif
        logo_conclusion: null, // ✅ V4: Logo conc
        logo_suivi: null, // ✅ V4: Logo suivi
        parent_id: null,
        parentLevel: 1,
        weight: 0,
        is_decomposable: false,
        mission_type_id: null,
      },
      availableParents: [],
      generatedCodeFull: '',
    };
  },
  mounted() {
    if (this.selectedTypeId && this.missionTypes) {
      const audit = this.missionTypes.find(a => a.id === this.selectedTypeId);
      if (audit) this.selectAudit(audit);
    }
  },
  methods: {
    getCsrfToken() {
      return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
        || document.querySelector('input[name="_token"]')?.value 
        || '';
    },

    selectAudit(audit) {
      this.selectedAudit = audit;
      this.loadPhases();
    },

    deselectAudit() {
      this.selectedAudit = null;
      this.hierarchy = [];
      this.expandedIds.clear();
      this.error = null;
      this.successMessage = null;
    },

    getAuditColor(auditId) {
      const colors = {
        1: '#0d6efd', 2: '#28a745', 3: '#fd7e14',
        4: '#dc3545', 5: '#6f42c1', 6: '#20c997',
      };
      return colors[auditId] || '#6c757d';
    },

    getAuditIcon(code) {
      const icons = {
        'ASS-AAP': '📊', 'ASS-ADC': '✅', 'ASS-ADI': '🔍',
        'ASS-ADM': '📋', 'ASS-ADP': '⚡', 'ASS-ERC': '⚠️',
      };
      return icons[code] || '🎯';
    },

    getAuditDescription(code) {
      const descriptions = {
        'ASS-AAP': 'Audit des processus analytiques et gestion paie',
        'ASS-ADC': 'Audit de conformité réglementaire et légale',
        'ASS-ADI': 'Audit interne général',
        'ASS-ADM': 'Audit administratif',
        'ASS-ADP': 'Audit de performance opérationnelle',
        'ASS-ERC': 'Audit des risques et contrôles internes',
      };
      return descriptions[code] || '';
    },

    async loadPhases() {
      this.loading = true;
      this.error = null;

      try {
        const response = await fetch(
          `/m/audit.core/api/mission-phases/hierarchy/${this.selectedAudit.id}`
        );
        if (!response.ok) throw new Error('Erreur chargement');

        const data = await response.json();
        this.hierarchy = this.buildCompleteHierarchy(data.data || []);
        await this.loadAllPhases();
        this.expandAll();

      } catch (err) {
        this.error = `Erreur: ${err.message}`;
        console.error('loadPhases error:', err);
      } finally {
        this.loading = false;
      }
    },

    buildCompleteHierarchy(phases, level = 1) {
      return phases.map(phase => ({
        ...phase,
        level: level,
        _level: level,
        children: phase.children && phase.children.length > 0 
          ? this.buildCompleteHierarchy(phase.children, level + 1)
          : [],
      }));
    },

    async loadAllPhases() {
      try {
        const response = await fetch(
          `/m/audit.core/api/mission-phases/hierarchy/${this.selectedAudit.id}`
        );
        const data = await response.json();
        this.availableParents = this.flattenHierarchy(data.data || []);
      } catch (err) {
        console.error('loadAllPhases error:', err);
      }
    },

    flattenHierarchy(phases, list = []) {
      phases.forEach(phase => {
        list.push(phase);
        if (phase.children && phase.children.length > 0) {
          this.flattenHierarchy(phase.children, list);
        }
      });
      return list;
    },

    toggleExpand(phaseId) {
      if (this.expandedIds.has(phaseId)) {
        this.expandedIds.delete(phaseId);
      } else {
        this.expandedIds.add(phaseId);
      }
    },

    expandAll() {
      const collectIds = (phases) => {
        phases.forEach(phase => {
          this.expandedIds.add(phase.id);
          if (phase.children && phase.children.length > 0) {
            collectIds(phase.children);
          }
        });
      };
      collectIds(this.hierarchy);
    },

    collapseAll() {
      this.expandedIds.clear();
    },

    openCreateModal(parentPhase) {
      this.currentStep = 1;
      this.form = {
        id: null,
        code: '',
        label: '',
        description: '',
        phase_type: null,
        logo_preparation: null,
        logo_verification: null,
        logo_conclusion: null,
        logo_suivi: null,
        parent_id: parentPhase ? parentPhase.id : null,
        parentLevel: parentPhase ? (parentPhase._level || parentPhase.level || 1) : 1,
        weight: 0,
        is_decomposable: false,
        mission_type_id: this.selectedAudit.id,
      };
      this.generatedCodeFull = '';
      this.showModal = true;
    },

    editPhase(phase) {
      this.currentStep = 1;
      this.form = {
        id: phase.id,
        code: phase.code,
        label: phase.label,
        description: phase.description || '',
        phase_type: phase.phase_type || null,
        logo_preparation: phase.logo_preparation || null,
        logo_verification: phase.logo_verification || null,
        logo_conclusion: phase.logo_conclusion || null,
        logo_suivi: phase.logo_suivi || null,
        parent_id: phase.parent_id,
        parentLevel: phase._level || phase.level || 1,
        weight: phase.weight || 0,
        is_decomposable: phase.is_decomposable || false,
        mission_type_id: this.selectedAudit.id,
      };
      this.generatedCodeFull = phase.code_full;
      this.showModal = true;
    },

    generateCodeFull() {
      if (!this.form.code) {
        this.generatedCodeFull = '';
        return;
      }

      const parentLevel = this.form.parentLevel;
      
      if (parentLevel === 1) {
        const parentNum = this.form.parent_id ? this.findPhaseCodeNum(this.form.parent_id) : 1;
        const childNum = this.countSiblingsAtLevel(2, this.form.parent_id) + 1;
        this.generatedCodeFull = `P${parentNum}N${childNum}`;
      } else if (parentLevel === 2) {
        const parent = this.findPhaseById(this.form.parent_id);
        const childNum = this.countSiblingsAtLevel(3, this.form.parent_id) + 1;
        this.generatedCodeFull = `${parent.code_full}.1N3.${childNum}`;
      } else {
        const parent = this.findPhaseById(this.form.parent_id);
        const childNum = this.countSiblingsAtLevel(parentLevel + 1, this.form.parent_id) + 1;
        this.generatedCodeFull = `${parent.code_full}.1N${parentLevel + 1}.${childNum}`;
      }
    },

    findPhaseById(phaseId) {
      const search = (phases) => {
        for (const p of phases) {
          if (p.id === phaseId) return p;
          if (p.children && p.children.length > 0) {
            const found = search(p.children);
            if (found) return found;
          }
        }
        return null;
      };
      return search(this.availableParents) || {};
    },

    findPhaseCodeNum(phaseId) {
      const phase = this.findPhaseById(phaseId);
      return phase.code_full ? phase.code_full.match(/^P(\d+)/)?.[1] || 1 : 1;
    },

    countSiblingsAtLevel(level, parentId) {
      const parent = this.findPhaseById(parentId);
      return parent && parent.children ? parent.children.length : 0;
    },

    nextStep() {
      if (this.currentStep < this.steps.length) this.currentStep++;
    },

    previousStep() {
      if (this.currentStep > 1) this.currentStep--;
    },

    async savePhase() {
      this.saving = true;
      this.error = null;

      try {
        const payload = {
          code: this.form.code,
          code_full: this.generatedCodeFull,
          label: this.form.label,
          description: this.form.description,
          phase_type: this.form.phase_type,
          logo_preparation: this.form.logo_preparation,
          logo_verification: this.form.logo_verification,
          logo_conclusion: this.form.logo_conclusion,
          logo_suivi: this.form.logo_suivi,
          parent_id: this.form.parent_id,
          weight: this.form.weight,
          is_decomposable: this.form.is_decomposable,
          mission_type_id: this.form.mission_type_id,
        };

        const method = this.form.id ? 'PUT' : 'POST';
        const url = this.form.id
          ? `/m/audit.core/api/mission-phases/${this.form.id}`
          : '/m/audit.core/api/mission-phases';

        const response = await fetch(url, {
          method,
          headers: { 
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': this.getCsrfToken(),
          },
          body: JSON.stringify(payload),
        });

        if (!response.ok) {
          const err = await response.json();
          throw new Error(err.error || 'Erreur sauvegarde');
        }

        this.successMessage = `Phase ${this.form.code} ${this.form.id ? 'modifiée' : 'créée'} ✅`;
        this.closeModal();
        await this.loadPhases();

      } catch (err) {
        this.error = `Erreur: ${err.message}`;
      } finally {
        this.saving = false;
      }
    },

    async deletePhase(phaseId) {
      if (!confirm('Supprimer cette phase et TOUS ses enfants?')) return;

      try {
        const response = await fetch(`/m/audit.core/api/mission-phases/${phaseId}`, {
          method: 'DELETE',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': this.getCsrfToken(),
          },
        });

        if (!response.ok) {
          const err = await response.json();
          throw new Error(err.error || 'Erreur suppression');
        }

        this.successMessage = 'Phase supprimée en cascade ✅';
        await this.loadPhases();

      } catch (err) {
        this.error = `Erreur: ${err.message}`;
      }
    },

    closeModal() {
      this.showModal = false;
      this.currentStep = 1;
    },
  },
};
</script>

<style scoped>
.mission-phases-container {
  padding: 20px;
  background: #f8f9fa;
  min-height: 100vh;
}

.phases-header {
  border-left: 4px solid #0d6efd;
}

/* AUDIT SELECTOR */
.audit-selector-section {
  margin-top: 30px;
  margin-bottom: 50px;
}

.audit-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
  padding: 20px 0;
}

.audit-card {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 25px;
  background: white;
  border: 3px solid;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.audit-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.audit-icon {
  width: 80px;
  height: 80px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 40px;
  flex-shrink: 0;
}

.audit-content {
  flex: 1;
  min-width: 0;
}

.audit-code {
  margin: 0 0 8px 0;
  font-weight: 700;
  font-size: 18px;
  color: #212529;
}

.audit-label {
  margin: 0 0 6px 0;
  font-size: 16px;
  font-weight: 500;
  color: #495057;
}

.audit-description {
  color: #6c757d;
  display: block;
  line-height: 1.4;
}

.audit-arrow {
  font-size: 28px;
  color: #ccc;
  flex-shrink: 0;
  transition: all 0.3s ease;
}

.audit-card:hover .audit-arrow {
  color: inherit;
  transform: translateX(4px);
}

/* SELECTED AUDIT BAR */
.selected-audit-bar {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 8px !important;
}

.selected-audit-info {
  display: flex;
  align-items: center;
}

.audit-icon-small {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: white;
  font-weight: bold;
}

/* PHASES TABLE */
.phases-table-wrapper {
  overflow-x: auto;
}

.table {
  font-size: 0.95rem;
}

.table thead {
  background: #f0f1f3;
}

/* MODAL */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
}

.modal-content {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 700px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.modal-header {
  border-bottom: 1px solid #dee2e6;
  padding: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h5 {
  margin: 0;
  font-weight: 600;
}

.modal-body {
  padding: 2rem 1.5rem;
}

.modal-footer {
  border-top: 1px solid #dee2e6;
  padding: 1.5rem;
  display: flex;
  gap: 10px;
  justify-content: flex-end;
}

.step-indicators {
  display: flex;
  justify-content: space-between;
  margin-bottom: 2rem;
  flex-wrap: wrap;
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  opacity: 0.5;
  transition: opacity 0.3s;
  flex: 1;
  min-width: 60px;
}

.step.active {
  opacity: 1;
}

.step.completed {
  opacity: 1;
}

.step-number {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #dee2e6;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  margin-bottom: 8px;
  color: #666;
}

.step.active .step-number {
  background: #0d6efd;
  color: white;
}

.step.completed .step-number {
  background: #28a745;
  color: white;
}

.step-label {
  font-size: 0.75rem;
  text-align: center;
  max-width: 60px;
}

.form-control, .form-select {
  border: 1px solid #dee2e6;
  border-radius: 6px;
  padding: 0.6rem 0.75rem;
}

.form-control:focus, .form-select:focus {
  border-color: #0d6efd;
  box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

.btn {
  border-radius: 6px;
  padding: 0.6rem 1rem;
  font-weight: 500;
  transition: all 0.3s;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.alert {
  border-radius: 6px;
  border: none;
  padding: 1rem;
  margin-bottom: 1rem;
}

@media (max-width: 768px) {
  .audit-cards-grid {
    grid-template-columns: 1fr;
  }

  .audit-card {
    padding: 20px;
    gap: 15px;
  }

  .audit-icon {
    width: 60px;
    height: 60px;
    font-size: 32px;
  }

  .step-indicators {
    justify-content: space-around;
  }

  .step {
    min-width: 50px;
  }
}
</style>