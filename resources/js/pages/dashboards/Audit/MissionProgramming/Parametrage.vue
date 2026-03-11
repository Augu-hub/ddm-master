<!-- resources/js/pages/dashboards/Audit/MissionProgramming/Parametrage.vue -->
<template>
  <div class="pm-container">

    <!-- TITRE -->
    <div class="pm-titlebar">
      <span class="pm-icon">⚙️</span>
      <span>Paramétrage — Programmation de missions</span>
    </div>

    <!-- ONGLETS -->
    <div class="pm-tabs">
      <button
        v-for="t in tabs" :key="t.key"
        class="pm-tab"
        :class="{ 'pm-tab-active': currentTab === t.key }"
        @click="currentTab = t.key"
      >
        <span class="pm-tab-icon">{{ t.icon }}</span>
        {{ t.label }}
        <span class="pm-tab-count">{{ getCount(t.key) }}</span>
      </button>
    </div>

    <!-- CONTENU -->
    <div class="pm-body">

      <!-- Flash messages -->
      <div v-if="$page.props.flash && $page.props.flash.success" class="pm-flash pm-flash-success">
        ✓ {{ $page.props.flash.success }}
      </div>
      <div v-if="hasErrors" class="pm-flash pm-flash-error">
        <p v-for="(e, k) in allErrors" :key="k">{{ e }}</p>
      </div>

      <!-- ================================================================
           ONGLET 1 : PHASES DE MISSION
           ================================================================ -->
      <div v-show="currentTab === 'phases'">
        <div class="pm-section-header">
          <h3>Phases de mission</h3>
          <p class="pm-desc">Définissez les phases du processus d'audit (Préparation, Réalisation, Rapport, Clôture...)</p>
          <button class="pm-btn pm-btn-add" @click="openModal('phase')">+ Nouvelle phase</button>
        </div>

        <table class="pm-table">
          <thead>
            <tr>
              <th style="width:40px">#</th>
              <th style="width:80px">Code</th>
              <th>Libellé</th>
              <th>Description</th>
              <th style="width:60px">Ordre</th>
              <th style="width:70px">Statut</th>
              <th style="width:100px">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in phases" :key="p.id" :class="{ 'pm-inactive': !p.is_active }">
              <td class="pm-id">{{ p.id }}</td>
              <td><span class="pm-code">{{ p.code }}</span></td>
              <td class="pm-bold">{{ p.libelle }}</td>
              <td class="pm-desc-cell">{{ p.description }}</td>
              <td class="pm-center">{{ p.ordre }}</td>
              <td class="pm-center">
                <span class="pm-badge" :class="p.is_active ? 'pm-badge-ok' : 'pm-badge-off'">
                  {{ p.is_active ? 'Actif' : 'Inactif' }}
                </span>
              </td>
              <td class="pm-actions-cell">
                <button class="pm-btn-icon pm-btn-edit" @click="editItem('phase', p)" title="Modifier">✏️</button>
                <button class="pm-btn-icon pm-btn-delete" @click="deleteItem('phase', p)" title="Supprimer">🗑️</button>
              </td>
            </tr>
            <tr v-if="phases.length === 0"><td colspan="7" class="pm-empty">Aucune phase configurée</td></tr>
          </tbody>
        </table>
      </div>

      <!-- ================================================================
           ONGLET 2 : RÔLES DE MISSION (avec hiérarchie)
           ================================================================ -->
      <div v-show="currentTab === 'roles'">
        <div class="pm-section-header">
          <h3>Rôles de mission</h3>
          <p class="pm-desc">Hiérarchie : DM → CM → AS → AJ. Un CM peut avoir plusieurs AS, un AS peut avoir plusieurs AJ.</p>
          <button class="pm-btn pm-btn-add" @click="openModal('role')">+ Nouveau rôle</button>
        </div>

        <!-- Arbre hiérarchique -->
        <div class="pm-hierarchy">
          <div v-for="r in rolesTree" :key="r.code" class="pm-h-node pm-h-level0">
            <div class="pm-h-item" :class="{ 'pm-inactive': !r.is_active }">
              <span class="pm-h-code">{{ r.code }}</span>
              <span class="pm-h-libelle">{{ r.libelle }}</span>
              <span class="pm-h-niveau">Niv. {{ r.niveau }}</span>
              <span v-if="r.max_enfants" class="pm-h-max">Max: {{ r.max_enfants }}</span>
              <span class="pm-badge" :class="r.is_active ? 'pm-badge-ok' : 'pm-badge-off'">{{ r.is_active ? 'Actif' : 'Inactif' }}</span>
              <button class="pm-btn-icon pm-btn-edit" @click="editItem('role', r)">✏️</button>
              <button class="pm-btn-icon pm-btn-delete" @click="deleteItem('role', r)">🗑️</button>
            </div>
            <div v-for="c1 in r.children" :key="c1.code" class="pm-h-node pm-h-level1">
              <div class="pm-h-item" :class="{ 'pm-inactive': !c1.is_active }">
                <span class="pm-h-connector">└─</span>
                <span class="pm-h-code">{{ c1.code }}</span>
                <span class="pm-h-libelle">{{ c1.libelle }}</span>
                <span class="pm-h-niveau">Niv. {{ c1.niveau }}</span>
                <span v-if="c1.max_enfants" class="pm-h-max">Max: {{ c1.max_enfants }}</span>
                <span class="pm-badge" :class="c1.is_active ? 'pm-badge-ok' : 'pm-badge-off'">{{ c1.is_active ? 'Actif' : 'Inactif' }}</span>
                <button class="pm-btn-icon pm-btn-edit" @click="editItem('role', c1)">✏️</button>
                <button class="pm-btn-icon pm-btn-delete" @click="deleteItem('role', c1)">🗑️</button>
              </div>
              <div v-for="c2 in c1.children" :key="c2.code" class="pm-h-node pm-h-level2">
                <div class="pm-h-item" :class="{ 'pm-inactive': !c2.is_active }">
                  <span class="pm-h-connector">&nbsp;&nbsp;&nbsp;└─</span>
                  <span class="pm-h-code">{{ c2.code }}</span>
                  <span class="pm-h-libelle">{{ c2.libelle }}</span>
                  <span class="pm-h-niveau">Niv. {{ c2.niveau }}</span>
                  <span v-if="c2.max_enfants" class="pm-h-max">Max: {{ c2.max_enfants }}</span>
                  <span class="pm-badge" :class="c2.is_active ? 'pm-badge-ok' : 'pm-badge-off'">{{ c2.is_active ? 'Actif' : 'Inactif' }}</span>
                  <button class="pm-btn-icon pm-btn-edit" @click="editItem('role', c2)">✏️</button>
                  <button class="pm-btn-icon pm-btn-delete" @click="deleteItem('role', c2)">🗑️</button>
                </div>
                <div v-for="c3 in c2.children" :key="c3.code" class="pm-h-node pm-h-level3">
                  <div class="pm-h-item" :class="{ 'pm-inactive': !c3.is_active }">
                    <span class="pm-h-connector">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;└─</span>
                    <span class="pm-h-code">{{ c3.code }}</span>
                    <span class="pm-h-libelle">{{ c3.libelle }}</span>
                    <span class="pm-h-niveau">Niv. {{ c3.niveau }}</span>
                    <span class="pm-badge" :class="c3.is_active ? 'pm-badge-ok' : 'pm-badge-off'">{{ c3.is_active ? 'Actif' : 'Inactif' }}</span>
                    <button class="pm-btn-icon pm-btn-edit" @click="editItem('role', c3)">✏️</button>
                    <button class="pm-btn-icon pm-btn-delete" @click="deleteItem('role', c3)">🗑️</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-if="roles.length === 0" class="pm-empty">Aucun rôle configuré</div>
        </div>

        <!-- Tableau rôles (vue alternative) -->
        <details class="pm-details">
          <summary>Vue tableau</summary>
          <table class="pm-table pm-mt-10">
            <thead>
              <tr>
                <th style="width:40px">#</th><th style="width:70px">Code</th><th>Libellé</th>
                <th style="width:80px">Parent</th><th style="width:60px">Niveau</th>
                <th style="width:90px">Max enfants</th><th style="width:60px">Ordre</th>
                <th style="width:70px">Statut</th><th style="width:100px">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in roles" :key="r.id" :class="{ 'pm-inactive': !r.is_active }">
                <td class="pm-id">{{ r.id }}</td>
                <td><span class="pm-code">{{ r.code }}</span></td>
                <td class="pm-bold">{{ r.libelle }}</td>
                <td>{{ r.parent_code || '—' }}</td>
                <td class="pm-center">{{ r.niveau }}</td>
                <td class="pm-center">{{ r.max_enfants || '∞' }}</td>
                <td class="pm-center">{{ r.ordre }}</td>
                <td class="pm-center">
                  <span class="pm-badge" :class="r.is_active ? 'pm-badge-ok' : 'pm-badge-off'">{{ r.is_active ? 'Actif' : 'Inactif' }}</span>
                </td>
                <td class="pm-actions-cell">
                  <button class="pm-btn-icon pm-btn-edit" @click="editItem('role', r)">✏️</button>
                  <button class="pm-btn-icon pm-btn-delete" @click="deleteItem('role', r)">🗑️</button>
                </td>
              </tr>
            </tbody>
          </table>
        </details>
      </div>

      <!-- ================================================================
           ONGLET 3 : TYPES DE BUDGET
           ================================================================ -->
      <div v-show="currentTab === 'budget_types'">
        <div class="pm-section-header">
          <h3>Types de budget</h3>
          <p class="pm-desc">Fixe, Variable, Par Phase, Par Auditeur...</p>
          <button class="pm-btn pm-btn-add" @click="openModal('budget_type')">+ Nouveau type</button>
        </div>

        <table class="pm-table">
          <thead>
            <tr>
              <th style="width:40px">#</th><th style="width:80px">Code</th><th>Libellé</th>
              <th>Description</th><th style="width:70px">Statut</th><th style="width:100px">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="b in budgetTypes" :key="b.id" :class="{ 'pm-inactive': !b.is_active }">
              <td class="pm-id">{{ b.id }}</td>
              <td><span class="pm-code">{{ b.code }}</span></td>
              <td class="pm-bold">{{ b.libelle }}</td>
              <td class="pm-desc-cell">{{ b.description }}</td>
              <td class="pm-center">
                <span class="pm-badge" :class="b.is_active ? 'pm-badge-ok' : 'pm-badge-off'">{{ b.is_active ? 'Actif' : 'Inactif' }}</span>
              </td>
              <td class="pm-actions-cell">
                <button class="pm-btn-icon pm-btn-edit" @click="editItem('budget_type', b)">✏️</button>
                <button class="pm-btn-icon pm-btn-delete" @click="deleteItem('budget_type', b)">🗑️</button>
              </td>
            </tr>
            <tr v-if="budgetTypes.length === 0"><td colspan="6" class="pm-empty">Aucun type configuré</td></tr>
          </tbody>
        </table>
      </div>

      <!-- ================================================================
           ONGLET 4 : CATÉGORIES DE BUDGET
           ================================================================ -->
      <div v-show="currentTab === 'budget_categories'">
        <div class="pm-section-header">
          <h3>Catégories de budget auditeur</h3>
          <p class="pm-desc">Logistiques, Communication, Primes, Hébergement, Divers... avec montant par défaut.</p>
          <button class="pm-btn pm-btn-add" @click="openModal('budget_category')">+ Nouvelle catégorie</button>
        </div>

        <table class="pm-table">
          <thead>
            <tr>
              <th style="width:40px">#</th><th style="width:80px">Code</th><th>Libellé</th>
              <th>Description</th><th style="width:120px">Montant défaut</th><th style="width:60px">Ordre</th>
              <th style="width:70px">Statut</th><th style="width:100px">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in budgetCategories" :key="c.id" :class="{ 'pm-inactive': !c.is_active }">
              <td class="pm-id">{{ c.id }}</td>
              <td><span class="pm-code">{{ c.code }}</span></td>
              <td class="pm-bold">{{ c.libelle }}</td>
              <td class="pm-desc-cell">{{ c.description }}</td>
              <td class="pm-amount">{{ fmt(c.montant_defaut) }} FCFA</td>
              <td class="pm-center">{{ c.ordre }}</td>
              <td class="pm-center">
                <span class="pm-badge" :class="c.is_active ? 'pm-badge-ok' : 'pm-badge-off'">{{ c.is_active ? 'Actif' : 'Inactif' }}</span>
              </td>
              <td class="pm-actions-cell">
                <button class="pm-btn-icon pm-btn-edit" @click="editItem('budget_category', c)">✏️</button>
                <button class="pm-btn-icon pm-btn-delete" @click="deleteItem('budget_category', c)">🗑️</button>
              </td>
            </tr>
            <tr v-if="budgetCategories.length === 0"><td colspan="8" class="pm-empty">Aucune catégorie configurée</td></tr>
          </tbody>
        </table>
      </div>

    </div>

    <!-- ================================================================
         MODAL CRUD (création / édition)
         ================================================================ -->
    <div v-if="modal.show" class="pm-modal-overlay" @click.self="closeModal">
      <div class="pm-modal">
        <div class="pm-modal-header">
          <h3>{{ modal.isEdit ? 'Modifier' : 'Créer' }} — {{ modalTitle }}</h3>
          <button class="pm-modal-close" @click="closeModal">✕</button>
        </div>

        <div class="pm-modal-body">

          <!-- === FORMULAIRE PHASE === -->
          <template v-if="modal.type === 'phase'">
            <div class="pm-form-row">
              <label>Code *</label>
              <input type="text" v-model="modal.data.code" class="pm-form-input" placeholder="P1, P2..." maxlength="20" />
            </div>
            <div class="pm-form-row">
              <label>Libellé *</label>
              <input type="text" v-model="modal.data.libelle" class="pm-form-input" placeholder="PHASE PREPARATION" />
            </div>
            <div class="pm-form-row">
              <label>Description</label>
              <textarea v-model="modal.data.description" class="pm-form-textarea" rows="2"></textarea>
            </div>
            <div class="pm-form-row-inline">
              <div>
                <label>Ordre *</label>
                <input type="number" v-model.number="modal.data.ordre" class="pm-form-input pm-w-80" min="0" />
              </div>
              <div>
                <label>Actif</label>
                <label class="pm-switch">
                  <input type="checkbox" v-model="modal.data.is_active" :true-value="1" :false-value="0" />
                  <span class="pm-slider"></span>
                </label>
              </div>
            </div>
          </template>

          <!-- === FORMULAIRE RÔLE === -->
          <template v-if="modal.type === 'role'">
            <div class="pm-form-row">
              <label>Code *</label>
              <input type="text" v-model="modal.data.code" class="pm-form-input" placeholder="DM, CM, AS, AJ" maxlength="10" />
            </div>
            <div class="pm-form-row">
              <label>Libellé *</label>
              <input type="text" v-model="modal.data.libelle" class="pm-form-input" placeholder="Directeur de Mission" />
            </div>
            <div class="pm-form-row">
              <label>Description</label>
              <textarea v-model="modal.data.description" class="pm-form-textarea" rows="2"></textarea>
            </div>
            <div class="pm-form-row">
              <label>Rôle parent</label>
              <select v-model="modal.data.parent_code" class="pm-form-input">
                <option value="">— Aucun (rôle racine) —</option>
                <option v-for="r in roles" :key="r.id" :value="r.code" :disabled="modal.isEdit && r.code === modal.data.code">
                  {{ r.code }} — {{ r.libelle }}
                </option>
              </select>
            </div>
            <div class="pm-form-row-inline">
              <div>
                <label>Niveau hiérarchique *</label>
                <input type="number" v-model.number="modal.data.niveau" class="pm-form-input pm-w-80" min="0" max="10" />
              </div>
              <div>
                <label>Max subordonnés</label>
                <input type="number" v-model.number="modal.data.max_enfants" class="pm-form-input pm-w-80" min="1" placeholder="∞" />
              </div>
              <div>
                <label>Ordre *</label>
                <input type="number" v-model.number="modal.data.ordre" class="pm-form-input pm-w-80" min="0" />
              </div>
              <div>
                <label>Actif</label>
                <label class="pm-switch">
                  <input type="checkbox" v-model="modal.data.is_active" :true-value="1" :false-value="0" />
                  <span class="pm-slider"></span>
                </label>
              </div>
            </div>
            <!-- Aide visuelle hiérarchie -->
            <div class="pm-hint" v-if="modal.data.parent_code">
              <strong>Hiérarchie :</strong> {{ modal.data.parent_code }} → <strong>{{ modal.data.code || '?' }}</strong>
            </div>
          </template>

          <!-- === FORMULAIRE TYPE BUDGET === -->
          <template v-if="modal.type === 'budget_type'">
            <div class="pm-form-row">
              <label>Code *</label>
              <input type="text" v-model="modal.data.code" class="pm-form-input" placeholder="FIXE, VARIABLE..." maxlength="20" />
            </div>
            <div class="pm-form-row">
              <label>Libellé *</label>
              <input type="text" v-model="modal.data.libelle" class="pm-form-input" placeholder="Budget Fixe" />
            </div>
            <div class="pm-form-row">
              <label>Description</label>
              <textarea v-model="modal.data.description" class="pm-form-textarea" rows="2"></textarea>
            </div>
            <div class="pm-form-row-inline">
              <div>
                <label>Actif</label>
                <label class="pm-switch">
                  <input type="checkbox" v-model="modal.data.is_active" :true-value="1" :false-value="0" />
                  <span class="pm-slider"></span>
                </label>
              </div>
            </div>
          </template>

          <!-- === FORMULAIRE CATÉGORIE BUDGET === -->
          <template v-if="modal.type === 'budget_category'">
            <div class="pm-form-row">
              <label>Code *</label>
              <input type="text" v-model="modal.data.code" class="pm-form-input" placeholder="LOG, COM, PRIME..." maxlength="20" />
            </div>
            <div class="pm-form-row">
              <label>Libellé *</label>
              <input type="text" v-model="modal.data.libelle" class="pm-form-input" placeholder="Logistiques" />
            </div>
            <div class="pm-form-row">
              <label>Description</label>
              <textarea v-model="modal.data.description" class="pm-form-textarea" rows="2"></textarea>
            </div>
            <div class="pm-form-row-inline">
              <div>
                <label>Montant par défaut (FCFA)</label>
                <input type="number" v-model.number="modal.data.montant_defaut" class="pm-form-input pm-w-140" min="0" step="500" />
              </div>
              <div>
                <label>Ordre *</label>
                <input type="number" v-model.number="modal.data.ordre" class="pm-form-input pm-w-80" min="0" />
              </div>
              <div>
                <label>Actif</label>
                <label class="pm-switch">
                  <input type="checkbox" v-model="modal.data.is_active" :true-value="1" :false-value="0" />
                  <span class="pm-slider"></span>
                </label>
              </div>
            </div>
          </template>

        </div>

        <div class="pm-modal-footer">
          <button class="pm-btn pm-btn-cancel" @click="closeModal">Annuler</button>
          <button class="pm-btn pm-btn-save" @click="saveItem" :disabled="saving">
            {{ saving ? 'Enregistrement...' : (modal.isEdit ? 'Mettre à jour' : 'Créer') }}
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL CONFIRMATION SUPPRESSION -->
    <div v-if="deleteConfirm.show" class="pm-modal-overlay" @click.self="deleteConfirm.show = false">
      <div class="pm-modal pm-modal-sm">
        <div class="pm-modal-header pm-modal-header-danger">
          <h3>Confirmer la suppression</h3>
          <button class="pm-modal-close" @click="deleteConfirm.show = false">✕</button>
        </div>
        <div class="pm-modal-body">
          <p>Voulez-vous vraiment supprimer <strong>{{ deleteConfirm.label }}</strong> ?</p>
          <p class="pm-warning">Cette action est irréversible. Si l'élément est utilisé dans des missions, désactivez-le plutôt.</p>
        </div>
        <div class="pm-modal-footer">
          <button class="pm-btn pm-btn-cancel" @click="deleteConfirm.show = false">Annuler</button>
          <button class="pm-btn pm-btn-danger" @click="confirmDelete">Supprimer</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import { router } from '@inertiajs/vue3'

export default {
  props: {
    activeTab:        { type: String, default: 'phases' },
    phases:           { type: Array, default: () => [] },
    roles:            { type: Array, default: () => [] },
    budgetTypes:      { type: Array, default: () => [] },
    budgetCategories: { type: Array, default: () => [] },
  },

  data() {
    return {
      currentTab: this.activeTab || 'phases',
      tabs: [
        { key: 'phases',            label: 'Phases',             icon: '📋' },
        { key: 'roles',             label: 'Rôles',              icon: '👥' },
        { key: 'budget_types',      label: 'Types Budget',       icon: '💰' },
        { key: 'budget_categories', label: 'Catégories Budget',  icon: '📊' },
      ],

      // Modal CRUD
      modal: {
        show: false,
        type: '',     // phase, role, budget_type, budget_category
        isEdit: false,
        data: {},
      },
      saving: false,

      // Confirmation suppression
      deleteConfirm: {
        show: false,
        type: '',
        id: null,
        label: '',
      },
    }
  },

  computed: {
    rolesTree() {
      const all = [...this.roles]
      const roots = all.filter(r => !r.parent_code)
      const buildTree = (parentCode) => {
        return all
          .filter(r => r.parent_code === parentCode)
          .sort((a, b) => a.ordre - b.ordre)
          .map(r => ({ ...r, children: buildTree(r.code) }))
      }
      return roots
        .sort((a, b) => a.ordre - b.ordre)
        .map(r => ({ ...r, children: buildTree(r.code) }))
    },

    hasErrors() {
      const errs = this.$page.props.errors
      return errs && Object.keys(errs).length > 0
    },

    allErrors() {
      return this.$page.props.errors || {}
    },

    modalTitle() {
      const map = { phase: 'Phase', role: 'Rôle', budget_type: 'Type de Budget', budget_category: 'Catégorie Budget' }
      return map[this.modal.type] || ''
    }
  },

  methods: {
    fmt(v) { return v != null ? Number(v).toLocaleString('fr-FR') : '0' },

    getCount(key) {
      const map = { phases: this.phases, roles: this.roles, budget_types: this.budgetTypes, budget_categories: this.budgetCategories }
      return (map[key] || []).length
    },

    // === Modal ouverture ===
    openModal(type) {
      const defaults = {
        phase:           { code:'', libelle:'', description:'', ordre: (this.phases.length + 1), is_active: 1 },
        role:            { code:'', libelle:'', description:'', parent_code:'', niveau:0, max_enfants:null, ordre: (this.roles.length + 1), is_active: 1 },
        budget_type:     { code:'', libelle:'', description:'', is_active: 1 },
        budget_category: { code:'', libelle:'', description:'', montant_defaut:0, ordre: (this.budgetCategories.length + 1), is_active: 1 },
      }
      this.modal = { show: true, type, isEdit: false, data: { ...defaults[type] } }
    },

    editItem(type, item) {
      this.modal = { show: true, type, isEdit: true, data: { ...item } }
    },

    closeModal() {
      this.modal.show = false
      this.saving = false
    },

    // === Sauvegarde ===
    saveItem() {
      this.saving = true
      const { type, isEdit, data } = this.modal

      const routeMap = {
        phase:           { store: 'audit.core.programmation-missions.parametrage.phases.store',           update: 'audit.core.programmation-missions.parametrage.phases.update' },
        role:            { store: 'audit.core.programmation-missions.parametrage.roles.store',             update: 'audit.core.programmation-missions.parametrage.roles.update' },
        budget_type:     { store: 'audit.core.programmation-missions.parametrage.budget-types.store',      update: 'audit.core.programmation-missions.parametrage.budget-types.update' },
        budget_category: { store: 'audit.core.programmation-missions.parametrage.budget-categories.store', update: 'audit.core.programmation-missions.parametrage.budget-categories.update' },
      }

      const routeName = isEdit ? routeMap[type].update : routeMap[type].store
      const method = isEdit ? 'put' : 'post'
      const url = isEdit ? route(routeName, data.id) : route(routeName)

      router[method](url, data, {
        preserveScroll: true,
        onSuccess: () => { this.closeModal() },
        onError: () => { this.saving = false },
      })
    },

    // === Suppression ===
    deleteItem(type, item) {
      this.deleteConfirm = {
        show: true,
        type,
        id: item.id,
        label: `${item.code} — ${item.libelle}`,
      }
    },

    confirmDelete() {
      const { type, id } = this.deleteConfirm
      const routeMap = {
        phase:           'audit.core.programmation-missions.parametrage.phases.destroy',
        role:            'audit.core.programmation-missions.parametrage.roles.destroy',
        budget_type:     'audit.core.programmation-missions.parametrage.budget-types.destroy',
        budget_category: 'audit.core.programmation-missions.parametrage.budget-categories.destroy',
      }
      router.delete(route(routeMap[type], id), {
        preserveScroll: true,
        onSuccess: () => { this.deleteConfirm.show = false },
      })
    }
  }
}
</script>

<style scoped>
/* ===== CONTAINER ===== */
.pm-container { font-family:'Segoe UI',Tahoma,Verdana,sans-serif; font-size:13px; max-width:1200px; margin:10px auto; background:#fff; border:1px solid #ccc; border-radius:4px; box-shadow:0 2px 12px rgba(0,0,0,.08); }

/* ===== TITLEBAR ===== */
.pm-titlebar { display:flex; align-items:center; gap:8px; padding:10px 16px; background:linear-gradient(135deg,#1a5276,#2980b9); color:#fff; font-size:15px; font-weight:600; border-radius:4px 4px 0 0; }
.pm-icon { font-size:18px; }

/* ===== TABS ===== */
.pm-tabs { display:flex; background:#f0f0f0; border-bottom:2px solid #2980b9; }
.pm-tab { flex:1; padding:10px 12px; border:none; background:transparent; font-size:13px; font-weight:600; color:#555; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px; transition:all .2s; border-bottom:3px solid transparent; margin-bottom:-2px; }
.pm-tab:hover { background:#e8f0fe; color:#1a5276; }
.pm-tab-active { color:#1a5276; background:#fff; border-bottom-color:#2980b9; }
.pm-tab-icon { font-size:16px; }
.pm-tab-count { background:#2980b9; color:#fff; font-size:10px; padding:1px 6px; border-radius:10px; min-width:18px; text-align:center; }
.pm-tab-active .pm-tab-count { background:#1a5276; }

/* ===== BODY ===== */
.pm-body { padding:16px; }

/* ===== SECTION HEADER ===== */
.pm-section-header { display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin-bottom:14px; }
.pm-section-header h3 { margin:0; font-size:16px; color:#1a5276; }
.pm-desc { color:#777; font-size:12px; flex:1; margin:0; }

/* ===== TABLES ===== */
.pm-table { width:100%; border-collapse:collapse; font-size:12px; }
.pm-table thead tr { background:#1a5276; color:#fff; }
.pm-table thead th { padding:8px 10px; text-align:left; font-weight:600; font-size:11px; text-transform:uppercase; letter-spacing:.3px; }
.pm-table tbody tr { border-bottom:1px solid #eee; transition:background .12s; }
.pm-table tbody tr:hover { background:#f0f7ff; }
.pm-table tbody td { padding:7px 10px; }
.pm-inactive { opacity:.55; }
.pm-id { color:#999; font-size:11px; }
.pm-bold { font-weight:600; }
.pm-center { text-align:center; }
.pm-amount { text-align:right; font-weight:600; color:#1a5276; }
.pm-desc-cell { color:#666; font-size:11px; max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.pm-actions-cell { display:flex; gap:4px; }
.pm-code { display:inline-block; background:#e8f0fe; color:#1a5276; padding:2px 8px; border-radius:3px; font-weight:700; font-size:11px; letter-spacing:.5px; }
.pm-empty { text-align:center; color:#aaa; font-style:italic; padding:20px !important; }

/* ===== BADGES ===== */
.pm-badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:10px; font-weight:600; }
.pm-badge-ok { background:#d4edda; color:#155724; }
.pm-badge-off { background:#f8d7da; color:#721c24; }

/* ===== HIERARCHY (Roles) ===== */
.pm-hierarchy { margin-bottom:12px; }
.pm-h-node { margin-bottom:2px; }
.pm-h-item { display:flex; align-items:center; gap:8px; padding:6px 10px; border:1px solid #e8e8e8; border-radius:3px; background:#fafafa; transition:background .12s; }
.pm-h-item:hover { background:#e8f0fe; }
.pm-h-level1 { margin-left:30px; }
.pm-h-level2 { margin-left:60px; }
.pm-h-level3 { margin-left:90px; }
.pm-h-connector { color:#bbb; font-family:monospace; font-size:14px; white-space:pre; }
.pm-h-code { background:#2980b9; color:#fff; padding:2px 8px; border-radius:3px; font-weight:700; font-size:11px; min-width:30px; text-align:center; }
.pm-h-libelle { font-weight:600; flex:1; }
.pm-h-niveau { color:#888; font-size:10px; }
.pm-h-max { color:#e67e22; font-size:10px; }

/* ===== DETAILS (vue alternative) ===== */
.pm-details { margin-top:12px; }
.pm-details summary { cursor:pointer; color:#2980b9; font-weight:600; font-size:12px; padding:4px 0; }
.pm-mt-10 { margin-top:10px; }

/* ===== BUTTONS ===== */
.pm-btn { border:none; border-radius:4px; padding:7px 16px; font-size:12px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:5px; transition:all .15s; }
.pm-btn-add { background:#2980b9; color:#fff; margin-left:auto; }
.pm-btn-add:hover { background:#1a5276; }
.pm-btn-save { background:#27ae60; color:#fff; }
.pm-btn-save:hover { background:#1e8449; }
.pm-btn-save:disabled { background:#aaa; cursor:not-allowed; }
.pm-btn-cancel { background:#95a5a6; color:#fff; }
.pm-btn-cancel:hover { background:#7f8c8d; }
.pm-btn-danger { background:#e74c3c; color:#fff; }
.pm-btn-danger:hover { background:#c0392b; }
.pm-btn-icon { background:none; border:none; cursor:pointer; font-size:14px; padding:2px 4px; border-radius:3px; transition:background .12s; }
.pm-btn-edit:hover { background:#e8f0fe; }
.pm-btn-delete:hover { background:#fde8ea; }
.pm-btn-sm { padding:3px 8px; font-size:11px; border-radius:3px; border:none; cursor:pointer; }
.pm-btn-primary { background:#2980b9; color:#fff; }

/* ===== FLASH ===== */
.pm-flash { padding:8px 14px; border-radius:4px; margin-bottom:12px; font-size:12px; }
.pm-flash-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
.pm-flash-error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
.pm-flash-error p { margin:2px 0; }

/* ===== MODAL ===== */
.pm-modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:9999; display:flex; align-items:center; justify-content:center; }
.pm-modal { background:#fff; border-radius:6px; width:95%; max-width:560px; box-shadow:0 4px 24px rgba(0,0,0,.25); display:flex; flex-direction:column; }
.pm-modal-sm { max-width:420px; }
.pm-modal-header { display:flex; align-items:center; justify-content:space-between; padding:12px 18px; background:#1a5276; color:#fff; border-radius:6px 6px 0 0; }
.pm-modal-header h3 { margin:0; font-size:14px; }
.pm-modal-header-danger { background:#e74c3c; }
.pm-modal-close { background:none; border:none; color:#fff; font-size:18px; cursor:pointer; }
.pm-modal-close:hover { color:#fcc; }
.pm-modal-body { padding:18px; }
.pm-modal-footer { display:flex; justify-content:flex-end; gap:8px; padding:12px 18px; border-top:1px solid #eee; background:#f8f8f8; border-radius:0 0 6px 6px; }

/* ===== FORM ===== */
.pm-form-row { margin-bottom:12px; }
.pm-form-row label { display:block; font-weight:600; font-size:11px; color:#444; margin-bottom:4px; }
.pm-form-input { width:100%; border:1px solid #ccc; border-radius:3px; padding:6px 10px; font-size:12px; font-family:inherit; outline:none; transition:border-color .15s; }
.pm-form-input:focus { border-color:#2980b9; box-shadow:0 0 0 2px rgba(41,128,185,.15); }
.pm-form-textarea { width:100%; border:1px solid #ccc; border-radius:3px; padding:6px 10px; font-size:12px; font-family:inherit; outline:none; resize:vertical; }
.pm-form-textarea:focus { border-color:#2980b9; }
.pm-form-row-inline { display:flex; gap:14px; flex-wrap:wrap; margin-bottom:12px; }
.pm-form-row-inline > div { flex:1; min-width:100px; }
.pm-form-row-inline label { display:block; font-weight:600; font-size:11px; color:#444; margin-bottom:4px; }
.pm-w-80 { width:80px !important; }
.pm-w-140 { width:140px !important; }
.pm-hint { margin-top:8px; padding:6px 10px; background:#fff8e1; border:1px solid #ffe082; border-radius:3px; font-size:11px; color:#795548; }
.pm-warning { color:#e74c3c; font-size:12px; margin-top:6px; }

/* ===== SWITCH TOGGLE ===== */
.pm-switch { position:relative; display:inline-block; width:40px; height:22px; margin-top:4px; }
.pm-switch input { opacity:0; width:0; height:0; }
.pm-slider { position:absolute; cursor:pointer; inset:0; background:#ccc; border-radius:22px; transition:.3s; }
.pm-slider::before { content:""; position:absolute; height:16px; width:16px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:.3s; }
.pm-switch input:checked + .pm-slider { background:#27ae60; }
.pm-switch input:checked + .pm-slider::before { transform:translateX(18px); }

/* ===== RESPONSIVE ===== */
@media (max-width:768px) {
  .pm-tabs { flex-wrap:wrap; }
  .pm-tab { font-size:11px; padding:8px 6px; }
  .pm-form-row-inline { flex-direction:column; }
}
</style>