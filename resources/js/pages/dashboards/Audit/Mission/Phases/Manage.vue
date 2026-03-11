<template>
  <div class="manage-phases-container">

    <!-- ░░ HEADER ░░ -->
    <div class="page-header">
      <div class="header-left">
        <h2>⚙️ Gestion des Phases — Obligatoires / Optionnelles</h2>
        <p class="text-muted mb-0">Définissez quelles phases sont obligatoires pour chaque type de mission</p>
      </div>
      <div class="header-right">
        <a href="/m/audit.core/mission-phases" class="btn btn-outline-secondary btn-sm">
          ← Retour Phases
        </a>
      </div>
    </div>

    <!-- ░░ TYPE SELECTOR ░░ -->
    <div class="type-selector-bar bg-white rounded shadow-sm p-3 mb-4">
      <div class="row align-items-center">
        <div class="col-md-4">
          <label class="form-label fw-semibold mb-1">Type de Mission</label>
          <select v-model="currentTypeId" @change="loadPhases" class="form-select">
            <option v-for="t in missionTypes" :key="t.id" :value="t.id">
              {{ t.code }} — {{ t.label }}
            </option>
          </select>
        </div>

        <div class="col-md-8 d-flex gap-2 justify-content-md-end mt-3 mt-md-0" v-if="phases.length">
          <!-- Stats pills -->
          <span class="stat-pill stat-total">
            📊 {{ stats.total }} phases
          </span>
          <span class="stat-pill stat-mandatory">
            🔒 {{ stats.mandatory }} obligatoires
          </span>
          <span class="stat-pill stat-optional">
            🔓 {{ stats.optional }} optionnelles
          </span>

          <!-- Actions groupées -->
          <button @click="markAll(true)" class="btn btn-sm btn-danger" :disabled="saving">
            🔒 Tout Obligatoire
          </button>
          <button @click="markAll(false)" class="btn btn-sm btn-success" :disabled="saving">
            🔓 Tout Optionnel
          </button>
        </div>
      </div>
    </div>

    <!-- ░░ ALERTS ░░ -->
    <div v-if="error" class="alert alert-danger alert-dismissible fade show">
      ❌ {{ error }} <button @click="error=null" class="btn-close"></button>
    </div>
    <div v-if="successMessage" class="alert alert-success alert-dismissible fade show">
      ✅ {{ successMessage }} <button @click="successMessage=null" class="btn-close"></button>
    </div>

    <!-- ░░ LOADING ░░ -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary"></div>
      <p class="mt-2 text-muted">Chargement des phases...</p>
    </div>

    <!-- ░░ TABLE PHASES ░░ -->
    <div v-else-if="grouped.length" class="phases-table bg-white rounded shadow-sm">

      <!-- Groupe par phase_type -->
      <div v-for="group in grouped" :key="group.type" class="phase-group">

        <!-- En-tête de groupe -->
        <div class="group-header" :style="{ backgroundColor: typeColors[group.type] }">
          <span class="group-icon">{{ typeIcons[group.type] }}</span>
          <span class="group-label">{{ group.type }}</span>
          <span class="group-count ms-2 badge bg-white" :style="{ color: typeColors[group.type] }">
            {{ group.phases.length }} phases
          </span>
          <span class="ms-2 badge bg-white opacity-75" :style="{ color: typeColors[group.type] }">
            {{ group.phases.filter(p=>p.is_mandatory).length }} obligatoires
          </span>
        </div>

        <!-- Lignes -->
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:6%">Niv.</th>
              <th style="width:12%">Code</th>
              <th style="width:42%">Libellé</th>
              <th style="width:25%">Description</th>
              <th style="width:15%" class="text-center">Obligatoire</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="phase in group.phases"
              :key="phase.id"
              :class="['phase-row', { 'row-mandatory': phase.is_mandatory, 'row-optional': !phase.is_mandatory }]"
            >
              <!-- Niveau -->
              <td class="text-center">
                <span class="level-badge" :style="{ marginLeft: (phase.level - 1) * 10 + 'px' }">
                  {{ 'L' + phase.level }}
                </span>
              </td>

              <!-- Code -->
              <td>
                <span class="code-badge" :class="'level-' + phase.level">
                  {{ phase.code_full }}
                </span>
              </td>

              <!-- Libellé -->
              <td>
                <div class="phase-label" :style="{ paddingLeft: (phase.level - 1) * 14 + 'px' }">
                  <span v-if="phase.level > 1" class="tree-connector">└─</span>
                  {{ phase.label }}
                </div>
              </td>

              <!-- Description courte -->
              <td>
                <span class="description-short text-muted small">
                  {{ phase.description ? phase.description.substring(0, 60) + (phase.description.length > 60 ? '…' : '') : '—' }}
                </span>
              </td>

              <!-- Toggle Obligatoire -->
              <td class="text-center">
                <div class="toggle-wrapper">
                  <label class="toggle-switch" :title="phase.is_mandatory ? 'Rendre optionnel' : 'Rendre obligatoire'">
                    <input
                      type="checkbox"
                      :checked="phase.is_mandatory"
                      @change="toggleMandatory(phase)"
                      :disabled="toggling === phase.id"
                    >
                    <span class="toggle-slider"></span>
                  </label>
                  <span class="toggle-label" :class="phase.is_mandatory ? 'text-danger fw-semibold' : 'text-success'">
                    {{ phase.is_mandatory ? '🔒 Obligatoire' : '🔓 Optionnel' }}
                  </span>
                  <span v-if="toggling === phase.id" class="spinner-border spinner-border-sm ms-1 text-primary"></span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Empty -->
    <div v-else-if="!loading" class="text-center py-5 text-muted">
      <p>Sélectionnez un type de mission pour voir ses phases</p>
    </div>

  </div>
</template>

<script>
export default {
  name: 'ManagePhases',
  props: {
    missionTypes: { type: Array, default: () => [] },
    selectedTypeId: { type: Number, default: null },
    phases: { type: Array, default: () => [] },
    statistics: { type: Object, default: () => ({ total: 0, mandatory: 0, optional: 0 }) },
  },

  data() {
    return {
      currentTypeId: this.selectedTypeId,
      localPhases:   [...this.phases],
      stats:         { ...this.statistics },
      loading:       false,
      toggling:      null,
      saving:        false,
      error:         null,
      successMessage: null,

      typeColors: {
        PREPARATION:  '#1D4ED8',
        VERIFICATION: '#059669',
        CONCLUSION:   '#D97706',
        SUIVI:        '#7C3AED',
      },
      typeIcons: {
        PREPARATION:  '🟦',
        VERIFICATION: '🟩',
        CONCLUSION:   '🟨',
        SUIVI:        '🟪',
      },
    };
  },

  computed: {
    grouped() {
      const types = ['PREPARATION', 'VERIFICATION', 'CONCLUSION', 'SUIVI'];
      const groups = [];
      for (const type of types) {
        const phases = this.localPhases.filter(p => p.phase_type === type);
        if (phases.length) groups.push({ type, phases });
      }
      // Phases sans type
      const other = this.localPhases.filter(p => !types.includes(p.phase_type));
      if (other.length) groups.push({ type: 'AUTRE', phases: other });
      return groups;
    },
  },

  methods: {
    getCsrf() {
      return document.querySelector('meta[name="csrf-token"]')?.content || '';
    },

    async loadPhases() {
      if (!this.currentTypeId) return;
      this.loading = true;
      this.error   = null;
      try {
        const url = `/m/audit.core/mission-phases/manage?type_id=${this.currentTypeId}`;
        // Rechargement Inertia
        this.$inertia.get(url, {}, { preserveState: false, preserveScroll: false });
      } catch (e) {
        this.error = e.message;
      } finally {
        this.loading = false;
      }
    },

    async toggleMandatory(phase) {
      this.toggling = phase.id;
      this.error    = null;
      try {
        const res = await fetch(
          `/m/audit.core/api/mission-phases/${phase.id}/toggle-mandatory`,
          {
            method: 'PATCH',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': this.getCsrf(),
            },
          }
        );
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Erreur');

        // Mise à jour locale
        const idx = this.localPhases.findIndex(p => p.id === phase.id);
        if (idx !== -1) {
          this.localPhases[idx] = { ...this.localPhases[idx], is_mandatory: data.is_mandatory };
        }
        this.recalcStats();
        this.successMessage = data.message;
        setTimeout(() => { this.successMessage = null; }, 3000);

      } catch (e) {
        this.error = e.message;
      } finally {
        this.toggling = null;
      }
    },

    async markAll(isMandatory) {
      if (!confirm(`Rendre TOUTES les phases ${isMandatory ? 'OBLIGATOIRES' : 'OPTIONNELLES'} ?`)) return;
      this.saving = true;
      this.error  = null;
      try {
        const res = await fetch('/m/audit.core/api/mission-phases/bulk-mandatory', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': this.getCsrf(),
          },
          body: JSON.stringify({
            type_id:      this.currentTypeId,
            is_mandatory: isMandatory,
          }),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error);

        this.localPhases = this.localPhases.map(p => ({ ...p, is_mandatory: isMandatory }));
        this.recalcStats();
        this.successMessage = data.message;
        setTimeout(() => { this.successMessage = null; }, 3000);

      } catch (e) {
        this.error = e.message;
      } finally {
        this.saving = false;
      }
    },

    recalcStats() {
      const mandatory = this.localPhases.filter(p => p.is_mandatory).length;
      this.stats = {
        total:     this.localPhases.length,
        mandatory,
        optional:  this.localPhases.length - mandatory,
      };
    },
  },

  watch: {
    phases(val)     { this.localPhases = [...val]; },
    statistics(val) { this.stats = { ...val }; },
  },
};
</script>

<style scoped>
.manage-phases-container {
  padding: 24px;
  background: #f8fafc;
  min-height: 100vh;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
}
.page-header h2 { font-size: 1.4rem; font-weight: 700; margin: 0; }

/* Stats pills */
.stat-pill {
  display: inline-flex; align-items: center;
  padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;
}
.stat-total     { background: #EFF6FF; color: #1E40AF; }
.stat-mandatory { background: #FEF2F2; color: #DC2626; }
.stat-optional  { background: #F0FDF4; color: #16A34A; }

/* Groupe */
.phase-group { border-bottom: 2px solid #E2E8F0; }
.phase-group:last-child { border-bottom: none; }

.group-header {
  display: flex; align-items: center; gap: 8px;
  padding: 10px 20px; color: white; font-weight: 700; font-size: 0.9rem;
}
.group-icon   { font-size: 1.1rem; }
.group-count  { font-size: 0.75rem; }

/* Table */
.table { font-size: 0.875rem; }
.table thead th { background: #F8FAFC; font-weight: 600; color: #475569; font-size: 0.8rem; }

.row-mandatory { background: #FFF8F8; }
.row-mandatory:hover { background: #FEF2F2 !important; }
.row-optional:hover  { background: #F0FDF4 !important; }

/* Level badge */
.level-badge {
  display: inline-block; font-size: 0.7rem; font-weight: 700;
  background: #E2E8F0; color: #64748B; padding: 2px 6px; border-radius: 4px;
}

/* Code badge */
.code-badge {
  font-family: monospace; font-size: 0.8rem; font-weight: 600;
  padding: 2px 6px; border-radius: 4px;
}
.level-1 { background: #DBEAFE; color: #1D4ED8; }
.level-2 { background: #D1FAE5; color: #065F46; }
.level-3 { background: #FEF3C7; color: #92400E; }

.phase-label { font-weight: 500; }
.tree-connector { color: #94A3B8; margin-right: 4px; font-family: monospace; }
.description-short { line-height: 1.3; }

/* Toggle Switch */
.toggle-wrapper {
  display: flex; align-items: center; gap: 8px; justify-content: center;
}
.toggle-switch { position: relative; display: inline-block; width: 44px; height: 24px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
  position: absolute; cursor: pointer;
  top: 0; left: 0; right: 0; bottom: 0;
  background: #CBD5E1; border-radius: 24px; transition: .3s;
}
.toggle-slider::before {
  content: ""; position: absolute;
  width: 18px; height: 18px; left: 3px; bottom: 3px;
  background: white; border-radius: 50%; transition: .3s;
  box-shadow: 0 1px 3px rgba(0,0,0,.2);
}
.toggle-switch input:checked + .toggle-slider { background: #DC2626; }
.toggle-switch input:checked + .toggle-slider::before { transform: translateX(20px); }
.toggle-switch input:disabled + .toggle-slider { opacity: 0.5; cursor: not-allowed; }

.toggle-label { font-size: 0.78rem; min-width: 90px; }
</style>