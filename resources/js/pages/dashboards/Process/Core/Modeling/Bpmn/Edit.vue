<template>
  <div class="bpmn-editor">
    <Head :title="editorTitle" />

    <!-- ===== TOP BAR ===== -->
    <div class="topbar">
      <div class="topbar-left">
        <button class="tb-back" @click="goBack" title="Retour">
          <i class="ti ti-arrow-left"></i>
        </button>
        <div class="topbar-info">
          <span class="tb-name">{{ process.name }}</span>
          <div class="tb-meta">
            <code class="tb-code">{{ process.code }}</code>
            <span class="tb-version">v{{ diagram.version }}</span>
            <span class="tb-acts">{{ Object.keys(taskLinks).length }} liens</span>
          </div>
        </div>
      </div>

      <div class="topbar-center">
        <div class="save-pill" :class="saveStatus">
          <i :class="saveIcon"></i>
          <span>{{ saveMessage }}</span>
          <small v-if="lastSaveTime">{{ lastSaveTime }}</small>
        </div>
      </div>

      <div class="topbar-right">
        <button @click="toggleToolbar" class="tb-btn" :class="{ active: showToolbar }" title="Outils">
          <i class="ti ti-layout-sidebar-left"></i>
        </button>
        <button @click="toggleProperties" class="tb-btn" :class="{ active: showProperties }" title="Propriétés">
          <i class="ti ti-layout-sidebar-right"></i>
        </button>
        <button @click="zoomFit" class="tb-btn" title="Ajuster la vue">
          <i class="ti ti-maximize"></i>
        </button>
        <div class="tb-divider"></div>
        <button @click="saveManual" :disabled="saving" class="tb-btn-save">
          <i class="ti ti-versions"></i>
          <span>Nouvelle version</span>
        </button>
      </div>
    </div>

    <!-- ===== MAIN LAYOUT ===== -->
    <div class="editor-layout" :class="{ 'no-left': !showToolbar, 'no-right': !showProperties }">

      <!-- ── LEFT TOOLBAR ────────────────────────── -->
      <aside v-if="showToolbar" class="sidebar-left">
        <div class="sl-head">
          <span class="sl-title"><i class="ti ti-tools"></i> Palette</span>
          <input v-model="toolbarSearch" class="sl-search" placeholder="Rechercher..." />
        </div>

        <div class="sl-body">
          <div v-for="group in toolbarGroups" :key="group.id" class="tool-group">
            <div class="tg-label">
              <i :class="group.icon"></i> {{ group.label }}
            </div>
            <div class="tg-grid">
              <button
                v-for="item in filterGroup(group.items)"
                :key="item.type + item.name"
                class="tg-item"
                @click="createElementInParticipant(item.type, item.name)"
                @dragstart="dragStart($event, item)"
                draggable="true"
                :title="item.description"
              >
                <i :class="`bpmn-icon ${item.icon}`"></i>
                <span>{{ item.name }}</span>
              </button>
            </div>
          </div>

          <div class="tool-group">
            <div class="tg-label"><i class="ti ti-users"></i> Structure</div>
            <div class="tg-grid">
              <button class="tg-item" @click="addParticipant" title="Participant">
                <i class="bpmn-icon bpmn-icon-participant"></i>
                <span>Participant</span>
              </button>
              <button class="tg-item" @click="addLane" title="Couloir">
                <i class="bpmn-icon bpmn-icon-lane"></i>
                <span>Couloir</span>
              </button>
            </div>
          </div>
        </div>

        <div class="sl-foot">
          <small>#{{ diagram.id }} · {{ formatDate(diagram.created_at) }}</small>
        </div>
      </aside>

      <!-- ── CANVAS ──────────────────────────────── -->
      <div class="canvas-area">
        <!-- Mini toolbar -->
        <div class="canvas-bar">
          <div class="cb-left">
            <button @click="zoomIn"    class="cb-btn" title="Zoom +"><i class="ti ti-zoom-in"></i></button>
            <button @click="zoomOut"   class="cb-btn" title="Zoom -"><i class="ti ti-zoom-out"></i></button>
            <button @click="zoomFit"   class="cb-btn" title="Ajuster"><i class="ti ti-maximize"></i></button>
            <button @click="centerView" class="cb-btn" title="Centrer"><i class="ti ti-current-location"></i></button>
            <div class="cb-zoom">{{ zoomLevel }}%</div>
          </div>
          <div class="cb-right">
            <span class="cb-stat"><i class="ti ti-shapes"></i> {{ elementCount }}</span>
            <span class="cb-stat"><i class="ti ti-link"></i> {{ connectionCount }}</span>
          </div>
        </div>

        <!-- BPMN container -->
        <div
          ref="canvas"
          class="bpmn-canvas"
          @contextmenu.prevent="handleContextMenu"
          @dragover.prevent="handleDragOver"
          @drop.prevent="handleDrop"
        >
          <!-- Loading overlay -->
          <transition name="fade">
            <div v-if="loadingEditor" class="canvas-loader">
              <div class="loader-ring"></div>
              <p>Chargement du diagramme…</p>
              <small v-if="initial_data">
                {{ initial_data.task_links?.length || 0 }} liens ·
                {{ Object.keys(initial_data.element_configs || {}).length }} configs
              </small>
            </div>
          </transition>
        </div>

        <!-- Context menu -->
        <transition name="scale">
          <div
            v-if="showContextMenu"
            class="ctx-menu"
            :style="{ left: contextMenuX + 'px', top: contextMenuY + 'px' }"
          >
            <div v-if="selectedElement" class="ctx-head">
              <strong>{{ selectedElementName || 'Élément' }}</strong>
              <code>{{ selectedElementId }}</code>
            </div>
            <div v-if="selectedElement && (isTask(selectedElement) || isParticipant(selectedElement))" class="ctx-colors">
              <button
                v-for="c in colorPalette"
                :key="c.hex"
                class="ctx-color"
                :style="{ background: c.hex, outline: selectedColor === c.hex ? '3px solid #6366f1' : 'none' }"
                @click="applyColor(c.hex)"
                :title="c.name"
              ></button>
            </div>
            <div class="ctx-items">
              <button class="ctx-item" @click="duplicateElement"><i class="ti ti-copy"></i> Dupliquer</button>
              <button class="ctx-item" @click="resizeElement('larger')"><i class="ti ti-zoom-in"></i> Agrandir</button>
              <button class="ctx-item" @click="resizeElement('smaller')"><i class="ti ti-zoom-out"></i> Réduire</button>
              <button class="ctx-item" @click="resetElement"><i class="ti ti-refresh"></i> Réinitialiser</button>
              <button class="ctx-item danger" @click="deleteElement"><i class="ti ti-trash"></i> Supprimer</button>
            </div>
            <button class="ctx-close" @click="closeContextMenu"><i class="ti ti-x"></i></button>
          </div>
        </transition>
        <div v-if="showContextMenu" class="ctx-overlay" @click="closeContextMenu"></div>
      </div>

      <!-- ── RIGHT PROPERTIES ────────────────────── -->
      <aside v-if="showProperties" class="sidebar-right">
        <div class="sr-head">
          <span class="sr-title"><i class="ti ti-settings"></i> Propriétés</span>
          <button @click="toggleProperties" class="sr-close"><i class="ti ti-x"></i></button>
        </div>

        <div class="sr-body">

          <!-- ══ PAS DE SÉLECTION : on affiche quand même les activités ══ -->
          <div v-if="!selectedElement" class="no-sel">
            <div class="no-sel-icon"><i class="ti ti-cursor-text"></i></div>
            <h4>Sélectionnez un élément</h4>
            <p>Cliquez sur une tâche du canvas pour lier une activité</p>
            <div class="no-sel-tips">
              <div class="tip"><i class="ti ti-drag-drop"></i><span>Glissez depuis la palette</span></div>
              <div class="tip"><i class="ti ti-mouse-2"></i><span>Clic droit pour plus d'options</span></div>
              <div class="tip"><i class="ti ti-link"></i><span>Liez des activités aux tâches</span></div>
            </div>

            <!-- Liste des activités même sans sélection -->
            <div class="no-sel-activities" v-if="allActivities.length > 0">
              <div class="nsa-header">
                <i class="ti ti-activity"></i>
                <span>Activités disponibles</span>
                <span class="nsa-badge">{{ allActivities.length }}</span>
              </div>
              <div class="nsa-list">
                <div
                  v-for="act in allActivities"
                  :key="act.id"
                  class="nsa-item"
                >
                  <code>{{ act.code }}</code>
                  <span>{{ act.name }}</span>
                </div>
              </div>
            </div>
            <div class="no-sel-activities" v-else>
              <div class="nsa-header">
                <i class="ti ti-activity"></i>
                <span>Activités</span>
                <span class="nsa-badge nsa-badge-empty">0</span>
              </div>
              <div class="nsa-empty">
                <i class="ti ti-mood-empty"></i>
                <span>Aucune activité trouvée pour ce processus</span>
              </div>
            </div>
          </div>

          <!-- ══ SÉLECTION ══ -->
          <div v-else class="prop-panel">
            <!-- Element header -->
            <div class="prop-el-head">
              <div class="prop-el-icon"><i :class="selectedElementIcon"></i></div>
              <div>
                <div class="prop-el-name">{{ selectedElementName || '—' }}</div>
                <code class="prop-el-id">{{ selectedElementId }}</code>
                <span class="prop-el-type">{{ selectedElementType }}</span>
              </div>
            </div>

            <!-- Tabs -->
            <div class="prop-tabs">
              <button
                v-for="tab in visibleTabs"
                :key="tab.id"
                :class="['ptab', { active: activeTab === tab.id }]"
                @click="activeTab = tab.id"
              >
                <i :class="tab.icon"></i> {{ tab.label }}
              </button>
            </div>

            <!-- Tab: Général -->
            <div v-if="activeTab === 'general'" class="prop-content">
              <div class="pf-group">
                <label>Nom</label>
                <input
                  v-model="selectedElementName"
                  @change="updateElementName"
                  class="pf-input"
                  placeholder="Nom de l'élément"
                />
              </div>
              <div class="pf-group">
                <label>Description</label>
                <textarea
                  v-model="selectedElementDescription"
                  @change="updateElementDescription"
                  class="pf-textarea"
                  rows="3"
                  placeholder="Description…"
                ></textarea>
              </div>
            </div>

            <!-- Tab: Style -->
            <div v-if="activeTab === 'style'" class="prop-content">
              <div class="pf-group">
                <label>Couleur</label>
                <div class="color-grid">
                  <button
                    v-for="c in colorPalette"
                    :key="c.hex"
                    class="col-swatch"
                    :style="{ background: c.hex, outline: selectedColor === c.hex ? '3px solid #6366f1' : 'none' }"
                    @click="applyColor(c.hex)"
                    :title="c.name"
                  ></button>
                </div>
                <div class="custom-color-row">
                  <input type="color" v-model="selectedColor" @change="applyColor(selectedColor)" class="color-input" />
                  <input type="text" v-model="selectedColor" @change="applyColor(selectedColor)" class="pf-input color-hex-input" />
                </div>
              </div>
            </div>

            <!-- Tab: Activité -->
            <div v-if="activeTab === 'activity'" class="prop-content">

              <!-- Activité déjà liée -->
              <div v-if="currentActivityLink" class="act-linked">
                <div class="act-linked-head">
                  <span><i class="ti ti-link"></i> Activité liée</span>
                  <button @click="unlinkActivity" class="btn-unlink"><i class="ti ti-unlink"></i> Délier</button>
                </div>
                <div class="act-card">
                  <div class="act-card-icon"><i class="ti ti-activity"></i></div>
                  <div>
                    <strong>{{ currentActivityLink.activity_name }}</strong>
                    <code>{{ currentActivityLink.activity_code }}</code>
                  </div>
                </div>
              </div>

              <!-- Recherche + liste -->
              <div class="act-search-section">
                <div class="pf-group act-group">
                  <label>
                    <i class="ti ti-activity"></i>
                    {{ currentActivityLink ? 'Changer l\'activité' : 'Activités disponibles' }}
                    <span class="acts-count">{{ allActivities.length }}</span>
                  </label>

                  <!-- Debug info visible -->
                  <div v-if="allActivities.length === 0" class="acts-debug">
                    <i class="ti ti-alert-circle"></i>
                    Aucune activité chargée pour ce processus (id={{ process.id }})
                  </div>

                  <div class="act-search-wrap">
                    <i class="ti ti-search act-search-icon"></i>
                    <input
                      v-model="activitySearch"
                      class="pf-input act-search-input"
                      placeholder="Filtrer les activités…"
                    />
                    <button v-if="activitySearch" @click="activitySearch = ''" class="act-clear">
                      <i class="ti ti-x"></i>
                    </button>
                  </div>

                  <!-- Liste scrollable -->
                  <div class="act-list">
                    <div v-if="filteredActivities.length === 0 && allActivities.length === 0" class="act-empty">
                      <i class="ti ti-mood-empty"></i>
                      <span>Aucune activité disponible pour ce processus</span>
                    </div>
                    <div v-else-if="filteredActivities.length === 0" class="act-empty">
                      <i class="ti ti-search"></i>
                      <span>Aucun résultat pour "{{ activitySearch }}"</span>
                    </div>
                    <div
                      v-for="act in filteredActivities"
                      :key="act.id"
                      class="act-list-item"
                      :class="{ 'act-list-item--active': currentActivityLink?.activity_id === act.id }"
                      @click="linkActivityDirect(act)"
                      :title="act.name + ' — ' + act.code"
                    >
                      <div class="act-list-icon">
                        <i class="ti ti-activity"></i>
                      </div>
                      <div class="act-list-info">
                        <strong>{{ act.name }}</strong>
                        <code>{{ act.code }}</code>
                      </div>
                      <div class="act-list-action">
                        <i v-if="currentActivityLink?.activity_id === act.id" class="ti ti-check" style="color:#22c55e"></i>
                        <i v-else class="ti ti-link-plus"></i>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tab: Avancé -->
            <div v-if="activeTab === 'advanced'" class="prop-content">
              <div class="pf-group">
                <label>ID BPMN</label>
                <div class="id-row">
                  <code>{{ selectedElementId }}</code>
                  <button @click="copyToClipboard(selectedElementId)" class="btn-copy"><i class="ti ti-copy"></i></button>
                </div>
              </div>
              <div class="pf-group">
                <label>Type</label>
                <code class="type-badge">{{ selectedElementType }}</code>
              </div>
              <div class="pf-group">
                <label>Actions</label>
                <div class="adv-actions">
                  <button @click="resetElementStyles" class="adv-btn"><i class="ti ti-refresh"></i> Réinitialiser</button>
                  <button @click="exportElement" class="adv-btn"><i class="ti ti-download"></i> Exporter</button>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="prop-footer">
              <button @click="deselectElement" class="btn-desel"><i class="ti ti-x"></i> Désélectionner</button>
            </div>
          </div>
        </div>
      </aside>
    </div>

    <!-- ===== MODAL: Nouvelle version ===== -->
    <transition name="modal">
      <div v-if="showSaveModal" class="modal-overlay" @click.self="cancelSave">
        <div class="modal-box">
          <div class="modal-head">
            <i class="ti ti-versions modal-icon"></i>
            <h3>Nouvelle version</h3>
          </div>
          <div class="modal-body">
            <p>Créez un snapshot versionné du diagramme actuel.</p>
            <div class="pf-group">
              <label>Description des modifications</label>
              <textarea
                v-model="versionDescription"
                class="pf-textarea"
                rows="3"
                placeholder="Décrivez les changements…"
              ></textarea>
            </div>
            <div class="changes-summary">
              <div class="chg" v-if="pendingChanges.elements > 0"><i class="ti ti-shapes"></i> {{ pendingChanges.elements }} éléments</div>
              <div class="chg" v-if="pendingChanges.links > 0"><i class="ti ti-link"></i> {{ pendingChanges.links }} liens</div>
              <div class="chg" v-if="pendingChanges.styles > 0"><i class="ti ti-palette"></i> {{ pendingChanges.styles }} styles</div>
            </div>
          </div>
          <div class="modal-foot">
            <button @click="cancelSave" class="btn-cancel">Annuler</button>
            <button @click="confirmSave" :disabled="saving" class="btn-submit">
              <span v-if="saving" class="loading-dots"><span></span><span></span><span></span></span>
              <template v-else><i class="ti ti-check"></i> Créer la version</template>
            </button>
          </div>
        </div>
      </div>
    </transition>

  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed, markRaw } from 'vue'
import { router } from '@inertiajs/vue3'
import { Head } from '@inertiajs/vue3'
import axios from 'axios'

import BpmnModeler from 'bpmn-js/lib/Modeler'
import 'bpmn-js/dist/assets/diagram-js.css'
import 'bpmn-js/dist/assets/bpmn-font/css/bpmn.css'
import 'bpmn-js/dist/assets/bpmn-font/css/bpmn-codes.css'
import 'bpmn-js/dist/assets/bpmn-font/css/bpmn-embedded.css'

// ─── PROPS ──────────────────────────────────────────────
const props = defineProps({
  process:              { type: Object,  required: true },
  diagram:              { type: Object,  required: true },
  initial_data:         { type: Object,  required: true },
  // Inertia passe la clé telle quelle depuis PHP (snake_case)
  // On accepte les deux formes pour être robuste
  available_activities: { type: Array,   default: () => [] },
  availableActivities:  { type: Array,   default: () => [] },
  bpmn_enabled:         { type: Boolean, default: false },
})

// ─── COMPUTED : source unique de vérité pour les activités ──
// Inertia peut passer la clé en snake_case OU camelCase selon la version
const allActivities = computed(() => {
  // Priorité : snake_case (tel qu'envoyé par PHP/Inertia)
  const raw = props.available_activities?.length
    ? props.available_activities
    : props.availableActivities

  if (!raw || !Array.isArray(raw)) return []
  return raw.map(a => ({
    id:          a.id,
    code:        a.code        || '',
    name:        a.name        || '',
    description: a.description || '',
  }))
})

// ─── REFS ───────────────────────────────────────────────
const canvas           = ref(null)
let   modeler          = null
const loadingEditor    = ref(true)
const saving           = ref(false)
const saveStatus       = ref('idle')
const lastSaveTime     = ref(null)

const selectedElement            = ref(null)
const selectedElementId          = ref('')
const selectedElementName        = ref('')
const selectedElementType        = ref('')
const selectedElementDescription = ref('')
const selectedElementIcon        = ref('')
const selectedColor              = ref('#3498DB')

const mainParticipant     = ref(null)
const currentActivityLink = ref(null)
const activitySearch      = ref('')

const showToolbar    = ref(true)
const showProperties = ref(true)
const activeTab      = ref('general')

const showContextMenu = ref(false)
const contextMenuX    = ref(0)
const contextMenuY    = ref(0)

const showSaveModal      = ref(false)
const versionDescription = ref('')
const pendingChanges     = ref({ elements: 0, links: 0, styles: 0 })

const toolbarSearch = ref('')
const zoomLevel     = ref(100)

// taskLinks : map element_id → link object
const taskLinks        = ref({})
const elementConfigs   = ref({})
const sequenceFlows    = ref([])
const currentDiagramId = ref(
  props.diagram?.id && props.diagram.id !== 0 ? props.diagram.id : null
)

// ─── PALETTE ────────────────────────────────────────────
const colorPalette = [
  { name: 'Indigo',   hex: '#6366f1' },
  { name: 'Bleu',     hex: '#3b82f6' },
  { name: 'Vert',     hex: '#22c55e' },
  { name: 'Emeraude', hex: '#10b981' },
  { name: 'Ambre',    hex: '#f59e0b' },
  { name: 'Orange',   hex: '#f97316' },
  { name: 'Rouge',    hex: '#ef4444' },
  { name: 'Rose',     hex: '#ec4899' },
  { name: 'Violet',   hex: '#8b5cf6' },
  { name: 'Slate',    hex: '#64748b' },
]

// ─── TOOLBAR GROUPS ─────────────────────────────────────
const toolbarGroups = [
  {
    id: 'start', icon: 'ti ti-player-play', label: 'Démarrage',
    items: [
      { type: 'bpmn:StartEvent', name: 'Début',    icon: 'bpmn-icon-start-event-none',    description: 'Événement de départ' },
      { type: 'bpmn:StartEvent', name: 'Message',  icon: 'bpmn-icon-start-event-message', description: 'Départ par message' },
      { type: 'bpmn:StartEvent', name: 'Minuteur', icon: 'bpmn-icon-start-event-timer',   description: 'Départ minuteur' },
    ]
  },
  {
    id: 'tasks', icon: 'ti ti-checkbox', label: 'Tâches',
    items: [
      { type: 'bpmn:Task',             name: 'Tâche',        icon: 'bpmn-icon-task',               description: 'Tâche générique' },
      { type: 'bpmn:UserTask',         name: 'Utilisateur',  icon: 'bpmn-icon-user-task',          description: 'Tâche utilisateur' },
      { type: 'bpmn:ServiceTask',      name: 'Service',      icon: 'bpmn-icon-service-task',       description: 'Tâche service' },
      { type: 'bpmn:ScriptTask',       name: 'Script',       icon: 'bpmn-icon-script-task',        description: 'Tâche script' },
      { type: 'bpmn:BusinessRuleTask', name: 'Règle métier', icon: 'bpmn-icon-business-rule-task', description: 'Règle métier' },
    ]
  },
  {
    id: 'gateways', icon: 'ti ti-share', label: 'Décisions',
    items: [
      { type: 'bpmn:ExclusiveGateway', name: 'Exclusif',  icon: 'bpmn-icon-gateway-xor',      description: 'XOR Gateway' },
      { type: 'bpmn:ParallelGateway',  name: 'Parallèle', icon: 'bpmn-icon-gateway-parallel',  description: 'AND Gateway' },
      { type: 'bpmn:InclusiveGateway', name: 'Inclusif',  icon: 'bpmn-icon-gateway-or',        description: 'OR Gateway' },
    ]
  },
  {
    id: 'events', icon: 'ti ti-bolt', label: 'Intermédiaires',
    items: [
      { type: 'bpmn:IntermediateThrowEvent', name: 'Lancer msg', icon: 'bpmn-icon-intermediate-event-throw-message', description: 'Envoyer message' },
      { type: 'bpmn:IntermediateCatchEvent', name: 'Recevoir',   icon: 'bpmn-icon-intermediate-event-catch-message', description: 'Recevoir message' },
    ]
  },
  {
    id: 'end', icon: 'ti ti-player-stop', label: 'Fin',
    items: [
      { type: 'bpmn:EndEvent', name: 'Fin',     icon: 'bpmn-icon-end-event-none',    description: 'Fin simple' },
      { type: 'bpmn:EndEvent', name: 'Message', icon: 'bpmn-icon-end-event-message', description: 'Fin message' },
      { type: 'bpmn:EndEvent', name: 'Erreur',  icon: 'bpmn-icon-end-event-error',   description: 'Fin erreur' },
    ]
  },
  {
    id: 'other', icon: 'ti ti-apps', label: 'Autres',
    items: [
      { type: 'bpmn:TextAnnotation', name: 'Annotation', icon: 'bpmn-icon-text-annotation', description: 'Note texte' },
      { type: 'bpmn:Group',          name: 'Groupe',      icon: 'bpmn-icon-group',            description: 'Groupe' },
    ]
  },
]

// ─── COMPUTED ────────────────────────────────────────────
const editorTitle = computed(() =>
  props.process.name
    ? `BPMN · ${props.process.name} (v${props.diagram.version})`
    : 'Éditeur BPMN'
)

const saveIcon = computed(() => ({
  saving: 'ti ti-loader animate-spin',
  saved:  'ti ti-check',
  error:  'ti ti-alert-circle',
  idle:   'ti ti-circle-dotted',
}[saveStatus.value] || 'ti ti-circle-dotted'))

const saveMessage = computed(() => ({
  saving: 'Sauvegarde…',
  saved:  'Sauvegardé',
  error:  'Erreur',
  idle:   'Auto-save',
}[saveStatus.value] || 'Auto-save'))

const elementCount = computed(() => {
  if (!modeler) return 0
  const r = modeler.get('elementRegistry')
  return r ? r.getAll().filter(e => !e.type?.includes('di:')).length : 0
})

const connectionCount = computed(() => {
  if (!modeler) return 0
  const r = modeler.get('elementRegistry')
  return r ? r.getAll().filter(e => e.type === 'bpmn:SequenceFlow').length : 0
})

// ── filteredActivities : filtre sur allActivities (computed stable) ──
const filteredActivities = computed(() => {
  const all = allActivities.value
  if (!activitySearch.value) return all
  const s = activitySearch.value.toLowerCase()
  return all.filter(a =>
    a.name?.toLowerCase().includes(s) ||
    a.code?.toLowerCase().includes(s)
  )
})

// ── Onglets visibles selon le type d'élément sélectionné ──
const visibleTabs = computed(() => {
  const tabs = [{ id: 'general', icon: 'ti ti-info-circle', label: 'Général' }]
  if (
    selectedElement.value &&
    (isTask(selectedElement.value) || isParticipant(selectedElement.value) || isLane(selectedElement.value))
  ) {
    tabs.push({ id: 'style', icon: 'ti ti-palette', label: 'Style' })
  }
  // Onglet Activité disponible pour TOUTE tâche
  if (selectedElement.value && isTask(selectedElement.value)) {
    tabs.push({ id: 'activity', icon: 'ti ti-link', label: `Activité${currentActivityLink.value ? ' ✓' : ''}` })
  }
  tabs.push({ id: 'advanced', icon: 'ti ti-settings', label: 'Avancé' })
  return tabs
})

// ─── HELPERS ────────────────────────────────────────────
function filterGroup(items) {
  if (!toolbarSearch.value) return items
  const s = toolbarSearch.value.toLowerCase()
  return items.filter(i =>
    i.name.toLowerCase().includes(s) ||
    i.description?.toLowerCase().includes(s)
  )
}

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function isTask(el) {
  if (!el) return false
  return [
    'bpmn:Task', 'bpmn:UserTask', 'bpmn:ServiceTask', 'bpmn:ScriptTask',
    'bpmn:ManualTask', 'bpmn:BusinessRuleTask', 'bpmn:SendTask',
    'bpmn:ReceiveTask', 'bpmn:CallActivity',
  ].includes(el.type)
}
function isParticipant(el) { return el?.type === 'bpmn:Participant' }
function isLane(el)        { return el?.type === 'bpmn:Lane' }

function getElementIcon(el) {
  if (!el) return 'ti ti-question-mark'
  const t = el.type
  if (t.includes('StartEvent'))  return 'bpmn-icon bpmn-icon-start-event-none'
  if (t.includes('EndEvent'))    return 'bpmn-icon bpmn-icon-end-event-none'
  if (t.includes('UserTask'))    return 'bpmn-icon bpmn-icon-user-task'
  if (t.includes('ServiceTask')) return 'bpmn-icon bpmn-icon-service-task'
  if (t.includes('Gateway'))     return 'bpmn-icon bpmn-icon-gateway-xor'
  if (t === 'bpmn:Participant')  return 'bpmn-icon bpmn-icon-participant'
  if (t === 'bpmn:Lane')         return 'bpmn-icon bpmn-icon-lane'
  return 'bpmn-icon bpmn-icon-task'
}

function updateSelectedElementInfo(el) {
  if (!el) return
  selectedElementId.value   = el.id
  selectedElementName.value = el.businessObject?.name || ''
  selectedElementType.value = el.type
  selectedElementIcon.value = getElementIcon(el)
  currentActivityLink.value = taskLinks.value[el.id] || null
  if (el.di?.fill) selectedColor.value = el.di.fill

  // Auto-switch vers l'onglet activité si c'est une tâche
  if (isTask(el)) {
    activeTab.value = 'activity'
  } else {
    activeTab.value = 'general'
  }
}

// ─── MODELER INIT ────────────────────────────────────────
async function initializeModeler() {
  if (!canvas.value) return
  try {
    loadingEditor.value = true
    modeler = new BpmnModeler({ container: canvas.value })
    const xml = props.initial_data?.bpmn_xml || getDefaultXml()
    await modeler.importXML(xml)

    const reg   = modeler.get('elementRegistry')
    const parts = reg.getAll().filter(e => e.type === 'bpmn:Participant')
    mainParticipant.value = parts.length > 0 ? parts[0] : null

    modeler.get('canvas').zoom('fit-viewport')
    await restoreDiagramState()
    setupModelerEvents()
    loadingEditor.value = false
  } catch (err) {
    console.error('Erreur init BPMN:', err)
    loadingEditor.value = false
  }
}

async function restoreDiagramState() {
  if (!modeler) return
  try {
    const modeling = modeler.get('modeling')
    const reg      = modeler.get('elementRegistry')
    const links    = props.initial_data?.task_links || []

    for (const link of links) {
      const el = reg.get(link.element_id)
      if (el && isTask(el)) {
        modeling.setColor(el, {
          fill:        link.color_hex || '#eef2ff',
          stroke:      link.color_hex || '#6366f1',
          strokeWidth: 2,
        })
        taskLinks.value[link.element_id] = { ...link }
      }
    }

    for (const el of reg.getAll()) {
      if (el.type === 'bpmn:Participant' || el.type === 'bpmn:Lane') {
        modeling.setColor(el, {
          fill:        el.type === 'bpmn:Participant' ? '#ffffff' : '#f8fafc',
          stroke:      '#1e293b',
          strokeWidth: 2,
        })
      } else if (isTask(el)) {
        if (!taskLinks.value[el.id]) {
          modeling.setColor(el, { fill: '#ffffff', stroke: '#6366f1', strokeWidth: 2 })
        }
      } else if (el.type?.includes('Event')) {
        modeling.setColor(el, { fill: '#ffffff', stroke: '#22c55e', strokeWidth: 2 })
      } else if (el.type?.includes('Gateway')) {
        modeling.setColor(el, { fill: '#ffffff', stroke: '#f59e0b', strokeWidth: 2 })
      }
    }
  } catch (err) {
    console.error('Erreur restauration:', err)
  }
}

function setupModelerEvents() {
  if (!modeler) return
  const bus = modeler.get('eventBus')

  bus.on('element.click', ev => {
    selectedElement.value = markRaw(ev.element)
    updateSelectedElementInfo(ev.element)
    closeContextMenu()
  })

  bus.on('shape.added',      handleDiagramChange)
  bus.on('shape.removed',    handleDiagramChange)
  bus.on('connection.added', handleDiagramChange)
  bus.on('element.changed',  handleDiagramChange)

  bus.on('canvas.viewbox.changed', ev => {
    zoomLevel.value = Math.round((ev.viewbox?.scale ?? 1) * 100)
  })
}

// ─── ELEMENT CREATION ────────────────────────────────────
function createElementInParticipant(type, name) {
  if (!modeler || !mainParticipant.value) return
  const modeling = modeler.get('modeling')
  const cv       = modeler.get('canvas')
  const g        = cv.getGraphics(mainParticipant.value)
  if (!g) return
  const x = g.x + 50 + Math.random() * (g.width  - 150)
  const y = g.y + 50 + Math.random() * (g.height - 150)
  const shape = modeling.createShape({ type }, { x, y }, mainParticipant.value)
  if (name) modeling.updateProperties(shape, { name })
  applyDefaultColor(shape)
  selectedElement.value = markRaw(shape)
  updateSelectedElementInfo(shape)
  handleDiagramChange()
}

function applyDefaultColor(shape) {
  if (!modeler) return
  const modeling = modeler.get('modeling')
  if (isTask(shape))
    modeling.setColor(shape, { fill: '#fff', stroke: '#6366f1', strokeWidth: 2 })
  else if (shape.type?.includes('Event'))
    modeling.setColor(shape, { fill: '#fff', stroke: '#22c55e', strokeWidth: 2 })
  else if (shape.type?.includes('Gateway'))
    modeling.setColor(shape, { fill: '#fff', stroke: '#f59e0b', strokeWidth: 2 })
  else if (shape.type === 'bpmn:Participant')
    modeling.setColor(shape, { fill: '#fff', stroke: '#1e293b', strokeWidth: 2 })
  else if (shape.type === 'bpmn:Lane')
    modeling.setColor(shape, { fill: '#f8fafc', stroke: '#1e293b', strokeWidth: 2 })
}

function addParticipant() {
  if (!modeler) return
  const modeling = modeler.get('modeling')
  const cv       = modeler.get('canvas')
  const ef       = modeler.get('elementFactory')
  const part     = ef.createShape({ type: 'bpmn:Participant' })
  const b        = mainParticipant.value ? cv.getGraphics(mainParticipant.value) : null
  const x        = b ? b.x + b.width + 50 : 100
  const y        = b ? b.y : 100
  const s = modeling.createShape(part, { x, y, width: 400, height: 300 }, cv.getRootElement())
  modeling.updateProperties(s, { name: 'Nouveau Participant' })
  modeling.setColor(s, { fill: '#fff', stroke: '#1e293b', strokeWidth: 2 })
  selectedElement.value = markRaw(s)
  updateSelectedElementInfo(s)
  handleDiagramChange()
}

function addLane() {
  if (!modeler || !mainParticipant.value) return
  const modeling = modeler.get('modeling')
  const ef       = modeler.get('elementFactory')
  const lane     = ef.createShape({ type: 'bpmn:Lane' })
  const s        = modeling.createShape(lane, { x: 0, y: 0, width: 400, height: 100 }, mainParticipant.value)
  modeling.updateProperties(s, { name: 'Nouveau Couloir' })
  modeling.setColor(s, { fill: '#f8fafc', stroke: '#1e293b', strokeWidth: 2 })
  selectedElement.value = markRaw(s)
  updateSelectedElementInfo(s)
  handleDiagramChange()
}

// ─── COLOR & STYLE ────────────────────────────────────────
function applyColor(color) {
  if (!selectedElement.value || !modeler) return
  selectedColor.value = color
  const modeling = modeler.get('modeling')
  if (
    isTask(selectedElement.value) ||
    isParticipant(selectedElement.value) ||
    isLane(selectedElement.value)
  ) {
    modeling.setColor(selectedElement.value, { fill: color, stroke: color, strokeWidth: 2 })
  }
  if (taskLinks.value[selectedElement.value.id]) {
    taskLinks.value[selectedElement.value.id].color_hex = color
  }
  pendingChanges.value.styles++
  handleDiagramChange()
}

function resetElement() {
  if (!selectedElement.value || !modeler) return
  applyDefaultColor(selectedElement.value)
  selectedColor.value = '#6366f1'
  delete taskLinks.value[selectedElement.value.id]
  currentActivityLink.value = null
  pendingChanges.value.styles++
  handleDiagramChange()
  closeContextMenu()
}

function resetElementStyles() { resetElement() }

// ─── ELEMENT ACTIONS ─────────────────────────────────────
function deleteElement() {
  if (!selectedElement.value) return
  modeler.get('modeling').removeShape(selectedElement.value)
  delete taskLinks.value[selectedElement.value.id]
  selectedElement.value = null
  closeContextMenu()
  pendingChanges.value.elements++
  handleDiagramChange()
}

function duplicateElement() {
  if (!selectedElement.value || !modeler) return
  const cv = modeler.get('canvas')
  const g  = cv.getGraphics(selectedElement.value)
  modeler.get('modeling').copyShape(
    selectedElement.value,
    { x: g.x + 50, y: g.y + 50 },
    selectedElement.value.parent || cv.getRootElement()
  )
  closeContextMenu()
  pendingChanges.value.elements++
  handleDiagramChange()
}

function resizeElement(action) {
  if (!selectedElement.value || !modeler) return
  const modeling = modeler.get('modeling')
  const g        = modeler.get('canvas').getGraphics(selectedElement.value)
  if (!g) return
  const factor = action === 'larger' ? 1.2 : 0.8
  modeling.resizeShape(
    selectedElement.value,
    { x: g.x, y: g.y, width: g.width * factor, height: g.height * factor }
  )
  pendingChanges.value.styles++
  handleDiagramChange()
}

function updateElementName() {
  if (!selectedElement.value || !modeler) return
  modeler.get('modeling').updateProperties(selectedElement.value, { name: selectedElementName.value })
  pendingChanges.value.elements++
  handleDiagramChange()
}

function updateElementDescription() {
  pendingChanges.value.elements++
  handleDiagramChange()
}

// ─── ACTIVITY LINKING ────────────────────────────────────
function linkActivityDirect(act) {
  if (!selectedElement.value || !modeler) return

  const modeling = modeler.get('modeling')
  const label    = act.code + '\n' + act.name

  modeling.updateProperties(selectedElement.value, { name: label })
  selectedElementName.value = label

  modeling.setColor(selectedElement.value, {
    fill:        '#eef2ff',
    stroke:      '#6366f1',
    strokeWidth: 2,
  })
  selectedColor.value = '#6366f1'

  const link = {
    element_id:    selectedElement.value.id,
    element_name:  label,
    element_type:  selectedElement.value.type,
    color_hex:     '#6366f1',
    activity_id:   act.id,
    activity_name: act.name,
    activity_code: act.code,
  }

  taskLinks.value[selectedElement.value.id] = link
  currentActivityLink.value                 = link
  activitySearch.value                      = ''
  pendingChanges.value.links++
  handleDiagramChange()
}

function unlinkActivity() {
  if (!selectedElement.value) return
  delete taskLinks.value[selectedElement.value.id]
  currentActivityLink.value = null
  modeler.get('modeling').updateProperties(selectedElement.value, { name: '' })
  selectedElementName.value = ''
  pendingChanges.value.links++
  handleDiagramChange()
}

// ─── DRAG & DROP ─────────────────────────────────────────
const draggingElement = ref(null)

function dragStart(ev, item) {
  draggingElement.value = item
  ev.dataTransfer.setData('text/plain', JSON.stringify(item))
}

function handleDragOver(/* ev */) { /* allows drop */ }

function handleDrop(ev) {
  if (!draggingElement.value) return
  const rect = canvas.value.getBoundingClientRect()
  const x    = ev.clientX - rect.left
  const y    = ev.clientY - rect.top
  createAtPosition(draggingElement.value.type, draggingElement.value.name, x, y)
  draggingElement.value = null
}

function createAtPosition(type, name, x, y) {
  if (!modeler || !mainParticipant.value) return
  const modeling = modeler.get('modeling')
  const cv       = modeler.get('canvas')
  const bounds   = cv.getGraphics(mainParticipant.value)
  if (!bounds) return
  const ax = Math.max(bounds.x + 20, Math.min(x, bounds.x + bounds.width  - 100))
  const ay = Math.max(bounds.y + 20, Math.min(y, bounds.y + bounds.height - 100))
  const shape = modeling.createShape({ type }, { x: ax, y: ay }, mainParticipant.value)
  if (name) modeling.updateProperties(shape, { name })
  applyDefaultColor(shape)
  selectedElement.value = markRaw(shape)
  updateSelectedElementInfo(shape)
  handleDiagramChange()
}

// ─── CONTEXT MENU ────────────────────────────────────────
function handleContextMenu(ev) {
  if (!modeler) return
  const rect         = canvas.value.getBoundingClientRect()
  contextMenuX.value = ev.clientX - rect.left
  contextMenuY.value = ev.clientY - rect.top
  showContextMenu.value = true
}

function closeContextMenu() { showContextMenu.value = false }

// ─── ZOOM ────────────────────────────────────────────────
function zoomIn()    { modeler?.get('canvas').zoom(modeler.get('canvas').zoom() * 1.2) }
function zoomOut()   { modeler?.get('canvas').zoom(modeler.get('canvas').zoom() * 0.8) }
function zoomFit()   { modeler?.get('canvas').zoom('fit-viewport') }
function centerView() {
  if (!modeler) return
  const vb = modeler.get('canvas').viewbox()
  modeler.get('canvas').scroll({ dx: -vb.x + 100, dy: -vb.y + 100 })
}

// ─── SAVE ────────────────────────────────────────────────
let autoSaveTimer    = null
let autoSaveInterval = null

function handleDiagramChange() {
  pendingChanges.value.elements++
  scheduleAutoSave()
}

function scheduleAutoSave() {
  if (autoSaveTimer) clearTimeout(autoSaveTimer)
  autoSaveTimer = setTimeout(performAutoSave, 2000)
}

async function performAutoSave() {
  if (!modeler || saving.value) return
  try {
    saving.value     = true
    saveStatus.value = 'saving'

    const { xml } = await modeler.saveXML({ format: true })
    const reg      = modeler.get('elementRegistry')

    const seqFlows = reg.getAll()
      .filter(e => e.type === 'bpmn:SequenceFlow')
      .map(f => ({
        sequence_id:         f.id,
        sequence_name:       f.businessObject?.name || '',
        source_element_id:   f.source?.id || '',
        source_element_name: f.source?.businessObject?.name || '',
        target_element_id:   f.target?.id || '',
        target_element_name: f.target?.businessObject?.name || '',
      }))

    const payload = {
      bpmn_xml:        xml,
      task_links:      Object.values(taskLinks.value),
      sequence_flows:  seqFlows,
      element_configs: { ...elementConfigs.value },
    }

    // Premier save (pas encore d'ID de diagramme)
    if (!currentDiagramId.value) {
      const res = await axios.put(
        route('process.core.modeling.bpmn.update', props.process.id),
        { bpmn_xml: xml, task_links: Object.values(taskLinks.value), sequence_flows: seqFlows }
      )
      if (res.data?.diagram_id) currentDiagramId.value = res.data.diagram_id
      saveStatus.value   = res.data?.success ? 'saved' : 'error'
      lastSaveTime.value = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
      setTimeout(() => {
        saveStatus.value = 'idle'
        pendingChanges.value = { elements: 0, links: 0, styles: 0 }
      }, 3000)
      return
    }

    const res = await axios.post(
      route('process.core.bpmn.auto-save', currentDiagramId.value),
      payload
    )

    if (res.data.success) {
      saveStatus.value   = 'saved'
      lastSaveTime.value = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
      setTimeout(() => {
        saveStatus.value = 'idle'
        pendingChanges.value = { elements: 0, links: 0, styles: 0 }
      }, 3000)
    }
  } catch (err) {
    console.error('Erreur auto-save:', err)
    saveStatus.value = 'error'
  } finally {
    saving.value = false
  }
}

function saveManual()  { showSaveModal.value = true }
function cancelSave()  { showSaveModal.value = false; versionDescription.value = '' }

async function confirmSave() {
  if (!modeler || saving.value) return
  try {
    saving.value      = true
    const { xml }     = await modeler.saveXML({ format: true })
    const res = await axios.post(
      route('process.core.bpmn.manual-save', currentDiagramId.value || props.diagram.id),
      {
        bpmn_xml:            xml,
        task_links:          Object.values(taskLinks.value),
        sequence_flows:      sequenceFlows.value,
        element_configs:     elementConfigs.value,
        version_description: versionDescription.value || 'Sauvegarde manuelle',
      }
    )
    if (res.data.success) {
      router.reload()
    }
  } catch (err) {
    console.error('Erreur sauvegarde manuelle:', err)
  } finally {
    saving.value        = false
    showSaveModal.value = false
  }
}

// ─── NAVIGATION ──────────────────────────────────────────
function goBack()          { router.visit(route('process.core.modeling.bpmn.index')) }
function toggleProperties(){ showProperties.value = !showProperties.value }
function toggleToolbar()   { showToolbar.value    = !showToolbar.value }
function deselectElement() { selectedElement.value = null }

// ─── MISC ─────────────────────────────────────────────────
function copyToClipboard(text) { navigator.clipboard?.writeText(text) }

function exportElement() {
  if (!selectedElement.value) return
  const data = JSON.stringify({
    id:   selectedElementId.value,
    name: selectedElementName.value,
    type: selectedElementType.value,
  }, null, 2)
  const a = document.createElement('a')
  a.href     = 'data:application/json;charset=utf-8,' + encodeURIComponent(data)
  a.download = `bpmn-element-${selectedElementId.value}.json`
  a.click()
}

function getDefaultXml() {
  const pid  = `Process_${props.process.id}`
  const name = props.process.name?.replace(/[<>&'"]/g, '') || 'Processus'
  return `<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"
  xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI"
  xmlns:dc="http://www.omg.org/spec/DD/20100524/DC"
  id="Definitions_1" targetNamespace="http://bpmn.io/schema/bpmn">
  <bpmn:process id="${pid}" name="${name}" isExecutable="false">
    <bpmn:startEvent id="StartEvent_1" name="Début" />
  </bpmn:process>
  <bpmndi:BPMNDiagram id="BPMNDiagram_1">
    <bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="${pid}">
      <bpmndi:BPMNShape id="BPMNShape_StartEvent_1" bpmnElement="StartEvent_1">
        <dc:Bounds x="150" y="100" width="36" height="36" />
      </bpmndi:BPMNShape>
    </bpmndi:BPMNPlane>
  </bpmndi:BPMNDiagram>
</bpmn:definitions>`
}

// ─── LIFECYCLE ───────────────────────────────────────────
onMounted(async () => {
  // DEBUG — affiche TOUTES les props reçues pour identifier le nom exact
  console.log('[BPMN] props keys:', Object.keys(props))
  console.log('[BPMN] available_activities:', props.available_activities)
  console.log('[BPMN] availableActivities:', props.availableActivities)
  console.log('[BPMN] allActivities computed:', allActivities.value)

  await initializeModeler()

  autoSaveInterval = setInterval(() => {
    if (pendingChanges.value.elements > 0) performAutoSave()
  }, 30000)
})

onBeforeUnmount(() => {
  modeler?.destroy()
  if (autoSaveTimer)    clearTimeout(autoSaveTimer)
  if (autoSaveInterval) clearInterval(autoSaveInterval)
})
</script>

<style scoped>
/* ─── ROOT ──────────────────────────────────── */
.bpmn-editor {
  height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f1f5f9;
  overflow: hidden;
}

/* ─── TOP BAR ───────────────────────────────── */
.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  background: #0f172a;
  color: #fff;
  padding: 0 1.25rem;
  height: 56px;
  flex-shrink: 0;
  border-bottom: 1px solid #1e293b;
}
.topbar-left   { display: flex; align-items: center; gap: .75rem; min-width: 0; }
.topbar-center { display: flex; align-items: center; }
.topbar-right  { display: flex; align-items: center; gap: .4rem; }

.tb-back {
  background: rgba(255,255,255,.07);
  border: 1px solid rgba(255,255,255,.12);
  color: #94a3b8;
  width: 34px; height: 34px;
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; flex-shrink: 0; transition: all .2s;
}
.tb-back:hover { background: rgba(255,255,255,.15); color: #fff; }

.tb-name {
  font-weight: 700; font-size: .95rem;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px;
}
.tb-meta { display: flex; gap: .5rem; align-items: center; }

.tb-code, .tb-version, .tb-acts {
  font-size: .72rem; padding: .15rem .5rem; border-radius: 4px;
}
.tb-code    { background: rgba(99,102,241,.25); color: #a5b4fc; font-family: monospace; }
.tb-version { background: rgba(255,255,255,.08); color: #64748b; }
.tb-acts    { background: rgba(255,255,255,.08); color: #64748b; }

.save-pill {
  display: flex; align-items: center; gap: .4rem;
  background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1);
  padding: .3rem .85rem; border-radius: 50px; font-size: .78rem; transition: all .3s;
}
.save-pill.saved  { background: rgba(34,197,94,.15);  border-color: rgba(34,197,94,.3);  color: #4ade80; }
.save-pill.saving { background: rgba(99,102,241,.15); border-color: rgba(99,102,241,.3); color: #a5b4fc; }
.save-pill.error  { background: rgba(239,68,68,.15);  border-color: rgba(239,68,68,.3);  color: #f87171; }
.save-pill.idle   { color: #475569; }
.save-pill small  { color: #475569; font-size: .7rem; }

.tb-btn {
  background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1);
  color: #64748b; width: 34px; height: 34px; border-radius: 7px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; font-size: .95rem; transition: all .2s;
}
.tb-btn:hover  { background: rgba(255,255,255,.12); color: #cbd5e1; }
.tb-btn.active { background: rgba(99,102,241,.25); border-color: rgba(99,102,241,.5); color: #a5b4fc; }

.tb-divider { width: 1px; height: 24px; background: rgba(255,255,255,.1); margin: 0 .25rem; }

.tb-btn-save {
  display: flex; align-items: center; gap: .5rem;
  background: #6366f1; border: none; color: #fff;
  padding: 0 1rem; height: 34px; border-radius: 8px;
  font-size: .85rem; font-weight: 600; cursor: pointer;
  transition: all .2s; white-space: nowrap;
}
.tb-btn-save:hover     { background: #4f46e5; }
.tb-btn-save:disabled  { opacity: .5; cursor: not-allowed; }

/* ─── MAIN LAYOUT ───────────────────────────── */
.editor-layout {
  display: grid;
  grid-template-columns: 250px 1fr 300px;
  gap: 6px; padding: 6px;
  flex: 1; min-height: 0;
  height: calc(100vh - 56px);
}
.editor-layout.no-left              { grid-template-columns: 1fr 300px; }
.editor-layout.no-right             { grid-template-columns: 250px 1fr; }
.editor-layout.no-left.no-right     { grid-template-columns: 1fr; }

/* ─── SIDEBAR LEFT ──────────────────────────── */
.sidebar-left {
  background: #fff; border-radius: 12px;
  border: 1.5px solid #e2e8f0;
  display: flex; flex-direction: column;
  overflow: hidden; height: 100%;
}
.sl-head { padding: .75rem; border-bottom: 1px solid #f1f5f9; background: #f8fafc; flex-shrink: 0; }
.sl-title {
  font-size: .8rem; font-weight: 700; color: #374151;
  display: flex; align-items: center; gap: .4rem;
  margin-bottom: .5rem; text-transform: uppercase; letter-spacing: .05em;
}
.sl-search {
  width: 100%; padding: .4rem .65rem;
  border: 1.5px solid #e2e8f0; border-radius: 7px;
  font-size: .82rem; color: #374151; background: #fff;
  transition: all .2s; box-sizing: border-box;
}
.sl-search:focus { outline: none; border-color: #6366f1; }
.sl-body { flex: 1; overflow-y: auto; padding: .6rem; }
.tool-group { margin-bottom: .75rem; }
.tg-label {
  font-size: .7rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .07em; color: #94a3b8; padding: .3rem .4rem;
  display: flex; align-items: center; gap: .4rem; margin-bottom: .3rem;
}
.tg-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px; }
.tg-item {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  padding: .6rem .3rem; border: 1.5px solid #e2e8f0; background: #fff;
  border-radius: 8px; cursor: grab; font-size: 1.4rem; color: #475569;
  min-height: 66px; transition: all .2s;
}
.tg-item:hover {
  border-color: #6366f1; color: #6366f1; background: #f5f3ff;
  transform: translateY(-1px); box-shadow: 0 2px 8px rgba(99,102,241,.15);
}
.tg-item span { font-size: .66rem; margin-top: .35rem; color: #64748b; font-weight: 500; text-align: center; line-height: 1.2; }
.sl-foot { padding: .5rem .75rem; border-top: 1px solid #f1f5f9; background: #f8fafc; flex-shrink: 0; color: #94a3b8; font-size: .72rem; }

/* ─── CANVAS AREA ───────────────────────────── */
.canvas-area {
  background: #fff; border-radius: 12px;
  border: 1.5px solid #e2e8f0;
  display: flex; flex-direction: column;
  overflow: hidden; position: relative; height: 100%;
}
.canvas-bar {
  display: flex; justify-content: space-between; align-items: center;
  padding: .45rem .75rem; border-bottom: 1px solid #f1f5f9;
  background: #f8fafc; flex-shrink: 0;
}
.cb-left, .cb-right { display: flex; align-items: center; gap: .35rem; }
.cb-btn {
  background: #fff; border: 1.5px solid #e2e8f0; color: #475569;
  width: 30px; height: 30px; border-radius: 6px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; font-size: .85rem; transition: all .2s;
}
.cb-btn:hover { background: #6366f1; border-color: #6366f1; color: #fff; }
.cb-zoom {
  font-size: .78rem; font-weight: 700; color: #475569;
  background: #fff; padding: .15rem .55rem; border-radius: 5px; border: 1.5px solid #e2e8f0;
}
.cb-stat {
  font-size: .75rem; color: #64748b; display: flex; align-items: center; gap: .3rem;
  background: #fff; padding: .15rem .55rem; border-radius: 5px; border: 1.5px solid #e2e8f0;
}
.bpmn-canvas {
  flex: 1; position: relative;
  background:
    repeating-linear-gradient(0deg,   transparent, transparent 24px, rgba(99,102,241,.04) 24px, rgba(99,102,241,.04) 25px),
    repeating-linear-gradient(90deg,  transparent, transparent 24px, rgba(99,102,241,.04) 24px, rgba(99,102,241,.04) 25px);
  min-height: 0;
}
.canvas-loader {
  position: absolute; inset: 0;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  background: rgba(255,255,255,.95); z-index: 100; gap: .75rem;
}
.loader-ring {
  width: 44px; height: 44px; border: 3px solid #e2e8f0;
  border-top-color: #6366f1; border-radius: 50%;
  animation: spin 1s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.canvas-loader p     { color: #475569; font-size: .95rem; font-weight: 600; margin: 0; }
.canvas-loader small { color: #94a3b8; font-size: .8rem; }

/* ─── CONTEXT MENU ──────────────────────────── */
.ctx-menu {
  position: absolute; background: #fff; border: 1.5px solid #e2e8f0;
  border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,.15);
  min-width: 200px; z-index: 1000; padding: .5rem; overflow: hidden;
}
.ctx-head { padding: .6rem .75rem; border-bottom: 1px solid #f1f5f9; margin-bottom: .4rem; }
.ctx-head strong { display: block; font-size: .85rem; color: #0f172a; }
.ctx-head code   { font-size: .72rem; color: #94a3b8; }
.ctx-colors { display: flex; flex-wrap: wrap; gap: 5px; padding: .4rem .4rem .6rem; border-bottom: 1px solid #f1f5f9; margin-bottom: .4rem; }
.ctx-color { width: 22px; height: 22px; border-radius: 50%; border: none; cursor: pointer; outline-offset: 2px; transition: transform .15s; }
.ctx-color:hover { transform: scale(1.2); }
.ctx-items { display: flex; flex-direction: column; gap: 2px; }
.ctx-item {
  display: flex; align-items: center; gap: .6rem;
  background: none; border: none; padding: .5rem .75rem; border-radius: 7px;
  font-size: .85rem; color: #374151; cursor: pointer; text-align: left; width: 100%; transition: background .15s;
}
.ctx-item:hover        { background: #f8fafc; }
.ctx-item.danger       { color: #ef4444; }
.ctx-item.danger:hover { background: #fef2f2; }
.ctx-close {
  position: absolute; top: .4rem; right: .4rem;
  background: none; border: none; color: #94a3b8; cursor: pointer; font-size: .85rem; padding: .2rem; border-radius: 4px;
}
.ctx-overlay { position: fixed; inset: 0; z-index: 999; }

/* ─── SIDEBAR RIGHT ─────────────────────────── */
.sidebar-right {
  background: #fff; border-radius: 12px; border: 1.5px solid #e2e8f0;
  display: flex; flex-direction: column; overflow: hidden; height: 100%;
}
.sr-head {
  display: flex; align-items: center; justify-content: space-between;
  padding: .75rem 1rem; border-bottom: 1px solid #f1f5f9; background: #f8fafc; flex-shrink: 0;
}
.sr-title {
  font-size: .8rem; font-weight: 700; color: #374151;
  display: flex; align-items: center; gap: .4rem;
  text-transform: uppercase; letter-spacing: .05em;
}
.sr-close { background: none; border: none; color: #94a3b8; cursor: pointer; font-size: .95rem; padding: .15rem; border-radius: 4px; transition: color .2s; }
.sr-close:hover { color: #ef4444; }
.sr-body { flex: 1; overflow-y: auto; }

/* ── No selection ── */
.no-sel { display: flex; flex-direction: column; align-items: center; text-align: center; padding: 1.5rem 1rem 1rem; color: #64748b; }
.no-sel-icon { font-size: 2rem; margin-bottom: .75rem; opacity: .25; }
.no-sel h4 { font-size: .9rem; color: #0f172a; margin-bottom: .3rem; }
.no-sel p  { font-size: .8rem; line-height: 1.5; margin-bottom: 1rem; }
.no-sel-tips { width: 100%; display: flex; flex-direction: column; gap: .4rem; margin-bottom: 1.25rem; }
.tip {
  display: flex; align-items: center; gap: .6rem;
  background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 8px;
  padding: .45rem .7rem; font-size: .78rem; color: #64748b; text-align: left;
}
.tip i { color: #6366f1; flex-shrink: 0; }

/* ── Activities in no-selection state ── */
.no-sel-activities {
  width: 100%;
  background: #f8fafc;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  overflow: hidden;
}
.nsa-header {
  display: flex; align-items: center; gap: .5rem;
  padding: .6rem .8rem;
  background: #f1f5f9;
  border-bottom: 1px solid #e2e8f0;
  font-size: .78rem; font-weight: 700; color: #374151;
}
.nsa-badge {
  margin-left: auto;
  background: #6366f1; color: #fff;
  font-size: .68rem; font-weight: 700;
  padding: .1rem .45rem; border-radius: 50px; line-height: 1.4;
}
.nsa-badge-empty { background: #94a3b8; }
.nsa-list { max-height: 200px; overflow-y: auto; }
.nsa-item {
  display: flex; align-items: center; gap: .6rem;
  padding: .5rem .8rem; border-bottom: 1px solid #f1f5f9;
  font-size: .8rem;
}
.nsa-item:last-child { border-bottom: none; }
.nsa-item code { color: #6366f1; font-size: .72rem; font-family: monospace; flex-shrink: 0; }
.nsa-item span { color: #374151; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.nsa-empty {
  display: flex; flex-direction: column; align-items: center; gap: .4rem;
  padding: 1.25rem; font-size: .8rem; color: #94a3b8; text-align: center;
}
.nsa-empty i { font-size: 1.3rem; opacity: .4; }

/* ── Properties panel ── */
.prop-panel { display: flex; flex-direction: column; height: 100%; }
.prop-el-head {
  display: flex; align-items: flex-start; gap: .75rem;
  padding: 1rem; border-bottom: 1px solid #f1f5f9; background: #f8fafc;
}
.prop-el-icon {
  width: 40px; height: 40px; background: #eef2ff; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; color: #6366f1; flex-shrink: 0;
}
.prop-el-name { font-size: .9rem; font-weight: 700; color: #0f172a; margin-bottom: .15rem; word-break: break-word; }
.prop-el-id   { font-size: .7rem; color: #94a3b8; display: block; margin-bottom: .3rem; }
.prop-el-type { font-size: .7rem; background: #f0f9ff; color: #0369a1; padding: .1rem .45rem; border-radius: 4px; font-weight: 600; }

.prop-tabs {
  display: flex; border-bottom: 1px solid #f1f5f9;
  background: #fafafa; padding: 0 .5rem; flex-shrink: 0;
}
.ptab {
  display: flex; align-items: center; gap: .3rem;
  background: none; border: none; border-bottom: 2px solid transparent;
  padding: .6rem .65rem; font-size: .78rem; font-weight: 600;
  color: #94a3b8; cursor: pointer; transition: all .2s; white-space: nowrap;
}
.ptab:hover  { color: #374151; }
.ptab.active { color: #6366f1; border-bottom-color: #6366f1; }

.prop-content { flex: 1; overflow-y: auto; padding: 1rem; }
.pf-group { margin-bottom: 1rem; }
.pf-group label {
  display: block; font-size: .78rem; font-weight: 600; color: #374151;
  margin-bottom: .35rem; text-transform: uppercase; letter-spacing: .04em;
}
.pf-input {
  width: 100%; padding: .55rem .75rem; border: 1.5px solid #e2e8f0;
  border-radius: 8px; font-size: .88rem; color: #0f172a; background: #fff;
  transition: all .2s; box-sizing: border-box;
}
.pf-input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1); }
.pf-textarea {
  width: 100%; padding: .55rem .75rem; border: 1.5px solid #e2e8f0;
  border-radius: 8px; font-size: .88rem; color: #0f172a;
  background: #fff; resize: vertical; transition: all .2s; box-sizing: border-box;
}
.pf-textarea:focus { outline: none; border-color: #6366f1; }

/* Color */
.color-grid { display: grid; grid-template-columns: repeat(5,1fr); gap: 6px; margin-bottom: .6rem; }
.col-swatch { width: 100%; padding-bottom: 100%; border-radius: 7px; border: none; cursor: pointer; outline-offset: 2px; transition: transform .15s; position: relative; }
.col-swatch:hover { transform: scale(1.1); }
.custom-color-row { display: flex; align-items: center; gap: .6rem; }
.color-input { width: 38px; height: 34px; border: 1.5px solid #e2e8f0; border-radius: 7px; cursor: pointer; padding: 2px; }
.color-hex-input { flex: 1; font-family: monospace; font-size: .8rem; }

/* ─── ACTIVITY TAB ──────────────────────────── */
.act-linked { background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 10px; padding: .75rem; margin-bottom: .75rem; }
.act-linked-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: .6rem; font-size: .78rem; font-weight: 600; color: #15803d; }
.btn-unlink {
  background: none; border: 1px solid #86efac; color: #16a34a;
  padding: .2rem .5rem; border-radius: 5px; cursor: pointer; font-size: .78rem;
  display: flex; align-items: center; gap: .3rem; transition: all .2s;
}
.btn-unlink:hover { background: #dcfce7; }
.act-card { display: flex; align-items: center; gap: .65rem; }
.act-card-icon { width: 32px; height: 32px; background: #dcfce7; border-radius: 7px; display: flex; align-items: center; justify-content: center; color: #16a34a; flex-shrink: 0; }
.act-card strong { display: block; font-size: .85rem; color: #0f172a; }
.act-card code   { font-size: .72rem; color: #64748b; }

.act-search-section { display: flex; flex-direction: column; gap: .75rem; }
.act-group { position: relative; }
.act-group label { display: flex !important; align-items: center; gap: .4rem; }
.acts-count {
  margin-left: auto; background: #6366f1; color: #fff;
  font-size: .68rem; font-weight: 700; padding: .1rem .45rem; border-radius: 50px; line-height: 1.4;
}

/* Debug info */
.acts-debug {
  display: flex; align-items: center; gap: .5rem;
  background: #fef3c7; border: 1px solid #fcd34d; border-radius: 7px;
  padding: .5rem .75rem; font-size: .78rem; color: #92400e; margin-bottom: .5rem;
}

.act-search-wrap { position: relative; display: flex; align-items: center; margin-bottom: .5rem; }
.act-search-icon { position: absolute; left: .65rem; color: #94a3b8; font-size: .9rem; pointer-events: none; z-index: 1; }
.act-search-input { padding-left: 2.1rem !important; padding-right: 2rem !important; }
.act-clear { position: absolute; right: .5rem; background: none; border: none; color: #94a3b8; cursor: pointer; font-size: .8rem; padding: .1rem; line-height: 1; transition: color .2s; }
.act-clear:hover { color: #ef4444; }

/* Scrollable list */
.act-list { border: 1.5px solid #e2e8f0; border-radius: 10px; overflow-y: auto; max-height: 280px; background: #fff; }
.act-list-item {
  display: flex; align-items: center; gap: .65rem;
  padding: .6rem .75rem; cursor: pointer;
  border-bottom: 1px solid #f8fafc; transition: all .15s;
}
.act-list-item:last-child { border-bottom: none; }
.act-list-item:hover { background: #f5f3ff; }
.act-list-item--active { background: #eef2ff; }
.act-list-item:hover .act-list-action { color: #6366f1; opacity: 1; }
.act-list-icon {
  width: 28px; height: 28px; background: #eef2ff; border-radius: 6px;
  display: flex; align-items: center; justify-content: center; color: #6366f1; flex-shrink: 0; font-size: .9rem;
}
.act-list-info { flex: 1; min-width: 0; }
.act-list-info strong { display: block; font-size: .82rem; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.act-list-info code   { font-size: .7rem; color: #94a3b8; font-family: monospace; }
.act-list-action { color: #cbd5e1; font-size: .9rem; flex-shrink: 0; opacity: 0; transition: all .15s; }
.act-empty {
  display: flex; flex-direction: column; align-items: center; gap: .4rem;
  padding: 1.5rem; font-size: .82rem; color: #94a3b8; text-align: center;
}
.act-empty i { font-size: 1.5rem; opacity: .4; }

/* Advanced */
.id-row { display: flex; align-items: center; gap: .6rem; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: .5rem .75rem; }
.id-row code { font-size: .78rem; color: #475569; flex: 1; word-break: break-all; }
.btn-copy { background: none; border: none; color: #6366f1; cursor: pointer; font-size: .85rem; padding: 0; }
.type-badge { font-size: .78rem; color: #0369a1; background: #f0f9ff; padding: .2rem .6rem; border-radius: 5px; }
.adv-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
.adv-btn {
  display: flex; align-items: center; gap: .4rem;
  background: #f8fafc; border: 1.5px solid #e2e8f0; color: #475569;
  padding: .45rem .75rem; border-radius: 7px; font-size: .8rem; font-weight: 500; cursor: pointer; transition: all .2s;
}
.adv-btn:hover { background: #eef2ff; border-color: #a5b4fc; color: #6366f1; }

.prop-footer { padding: .75rem 1rem; border-top: 1px solid #f1f5f9; background: #fafafa; flex-shrink: 0; }
.btn-desel {
  display: flex; align-items: center; gap: .4rem;
  background: none; border: 1.5px solid #e2e8f0; color: #64748b;
  padding: .4rem .75rem; border-radius: 7px; font-size: .8rem;
  cursor: pointer; transition: all .2s; width: 100%; justify-content: center;
}
.btn-desel:hover { background: #fef2f2; border-color: #fca5a5; color: #ef4444; }

/* ─── MODAL ─────────────────────────────────── */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(15,23,42,.6);
  backdrop-filter: blur(4px); z-index: 2000;
  display: flex; align-items: center; justify-content: center; padding: 1rem;
}
.modal-box { background: #fff; border-radius: 18px; width: 100%; max-width: 460px; box-shadow: 0 25px 60px rgba(0,0,0,.3); overflow: hidden; }
.modal-head { display: flex; align-items: center; gap: .85rem; padding: 1.5rem 1.75rem 1rem; border-bottom: 1px solid #f1f5f9; }
.modal-icon { font-size: 1.5rem; color: #6366f1; }
.modal-head h3 { font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0; }
.modal-body { padding: 1.25rem 1.75rem; }
.modal-body p { font-size: .88rem; color: #64748b; margin-bottom: 1rem; }
.changes-summary { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: 1rem; }
.chg { display: flex; align-items: center; gap: .35rem; background: #f0f9ff; border: 1px solid #bae6fd; color: #0369a1; font-size: .75rem; font-weight: 600; padding: .2rem .65rem; border-radius: 50px; }
.modal-foot { display: flex; justify-content: flex-end; gap: .75rem; padding: 1rem 1.75rem; border-top: 1px solid #f1f5f9; background: #fafafa; }
.btn-cancel {
  display: flex; align-items: center; gap: .4rem;
  background: none; border: 1.5px solid #e2e8f0; color: #475569;
  padding: .55rem 1.1rem; border-radius: 9px; font-size: .88rem; font-weight: 600; cursor: pointer; transition: all .2s;
}
.btn-cancel:hover { background: #f1f5f9; }
.btn-submit {
  display: flex; align-items: center; gap: .4rem;
  background: #6366f1; border: none; color: #fff;
  padding: .55rem 1.25rem; border-radius: 9px; font-size: .88rem; font-weight: 700;
  cursor: pointer; transition: all .2s; min-width: 140px; justify-content: center;
}
.btn-submit:hover:not(:disabled) { background: #4f46e5; }
.btn-submit:disabled { opacity: .5; cursor: not-allowed; }

.loading-dots { display: flex; gap: 4px; align-items: center; }
.loading-dots span { width: 6px; height: 6px; background: rgba(255,255,255,.8); border-radius: 50%; animation: ld .8s infinite; }
.loading-dots span:nth-child(2) { animation-delay: .15s; }
.loading-dots span:nth-child(3) { animation-delay: .3s; }
@keyframes ld { 0%,80%,100%{transform:scale(.6);opacity:.4} 40%{transform:scale(1);opacity:1} }

/* ─── TRANSITIONS ───────────────────────────── */
.fade-enter-active, .fade-leave-active { transition: opacity .3s; }
.fade-enter-from,   .fade-leave-to     { opacity: 0; }

.scale-enter-active { transition: all .15s ease-out; }
.scale-leave-active { transition: all .1s  ease-in; }
.scale-enter-from,  .scale-leave-to { opacity: 0; transform: scale(.95); }

.modal-enter-active { transition: all .25s ease-out; }
.modal-leave-active { transition: all .2s  ease-in; }
.modal-enter-from,  .modal-leave-to     { opacity: 0; }
.modal-enter-from .modal-box { transform: translateY(20px) scale(.97); }

.animate-spin { animation: spin 1s linear infinite; }

/* ─── SCROLLBAR ─────────────────────────────── */
::-webkit-scrollbar       { width: 5px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
</style>