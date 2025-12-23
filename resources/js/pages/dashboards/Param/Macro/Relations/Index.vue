<template>
  <VerticalLayout>
    <Head :title="`Relations — ${entity.name}`" />

    <!-- HEADER -->
    <b-card class="mb-3 shadow-sm border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h4 class="fw-bold mb-0">
            <i class="ti ti-git-branch me-2"></i>Diagramme BPMN — {{ entity.name }}
          </h4>
        </div>
        <div class="btn-group" role="group">
          <b-button variant="light" size="sm" @click="saveAll">
            <i class="ti ti-device-floppy me-1"></i> Sauvegarder
          </b-button>
          <b-button variant="light" size="sm" @click="reloadAll">
            <i class="ti ti-refresh me-1"></i> Recharger
          </b-button>
        </div>
      </div>
    </b-card>

    <b-row class="g-2">
      <!-- GAUCHE: PROCESSUS -->
      <b-col lg="2">
        <b-card class="h-100 shadow-sm border-0">
          <h6 class="fw-bold mb-2 text-primary">
            <i class="ti ti-list me-1"></i>Processus
          </h6>
          <b-form-input v-model="searchProc" placeholder="Rechercher..." size="sm" class="mb-2" />
          <div class="scroll-area">
            <div v-for="(group, id) in filteredGroups" :key="id" class="mb-3">
              <div class="group-header">{{ group.macro.name }}</div>
              <div v-for="p in group.items" :key="p.id"
                   class="proc-item"
                   draggable="true"
                   @dragstart="draggedProcess = { ...p, macro_id: group.macro.id, macro_name: group.macro.name }">
                <strong>{{ p.code }}</strong>
                <small>{{ p.name }}</small>
              </div>
            </div>
          </div>
        </b-card>
      </b-col>

      <!-- CENTRE: CANVAS -->
      <b-col lg="7">
        <b-card class="h-100 shadow-sm border-0">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold text-primary mb-0">
              <i class="ti ti-chart-infographic me-1"></i>Canvas
            </h6>
            <div class="btn-group" role="group">
              <b-button size="sm" variant="outline-secondary" @click="zoomOut">
                <i class="ti ti-zoom-out"></i>
              </b-button>
              <b-button size="sm" variant="outline-secondary" @click="resetZoom">
                {{ Math.round(zoom * 100) }}%
              </b-button>
              <b-button size="sm" variant="outline-secondary" @click="zoomIn">
                <i class="ti ti-zoom-in"></i>
              </b-button>
            </div>
          </div>

          <!-- CANVAS WRAPPER -->
          <div class="canvas-wrapper" ref="wrapperRef" @drop="dropProcess" @dragover.prevent @click="deselectAll">
            
            <!-- CONTENU ZOOMÉ -->
            <div class="canvas-content" :style="{ transform: `scale(${zoom})`, transformOrigin: '0 0' }">

              <!-- SVG LIAISONS -->
              <svg class="svg-bg" ref="svgContainer" @click.stop>
                <defs>
                  <marker id="arrowhead" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto">
                    <path d="M0,0 L0,6 L9,3 z" fill="#667eea" />
                  </marker>
                  <marker id="arrowhead-selected" markerWidth="12" markerHeight="12" refX="10" refY="4" orient="auto">
                    <path d="M0,0 L0,8 L10,4 z" fill="#e91e63" />
                  </marker>
                  <filter id="glow">
                    <feGaussianBlur stdDeviation="2" result="coloredBlur"/>
                    <feMerge>
                      <feMergeNode in="coloredBlur"/>
                      <feMergeNode in="SourceGraphic"/>
                    </feMerge>
                  </filter>
                </defs>

                <!-- FLÈCHES INTELLIGENTES -->
                <g v-for="(rel, i) in relations" :key="'rel-' + i">
                  <!-- Zone cliquable -->
                  <path :d="computeIntelligentPath(rel, i)"
                        stroke="transparent"
                        stroke-width="20"
                        fill="none"
                        style="cursor: grab; pointer-events: stroke;"
                        @click.stop="selectRelation(i)"
                        @mousedown.stop="selectedRel === i ? startDragRelation(i, $event) : selectRelation(i)" />

                  <!-- Flèche visible -->
                  <path :d="computeIntelligentPath(rel, i)"
                        :stroke="selectedRel === i ? '#e91e63' : '#667eea'"
                        :stroke-width="selectedRel === i ? 3 : 2"
                        fill="none"
                        :marker-end="selectedRel === i ? 'url(#arrowhead-selected)' : 'url(#arrowhead)'"
                        style="pointer-events: none; transition: stroke 0.1s;"
                        :filter="selectedRel === i ? 'url(#glow)' : 'none'" />

                  <!-- POINTS D'ANCRAGE (sur les bords seulement) -->
                  <template v-if="selectedRel === i">
                    <circle :cx="getRealAnchor(i, 'start')?.x || 0"
                            :cy="getRealAnchor(i, 'start')?.y || 0"
                            r="6"
                            fill="#10b981"
                            stroke="white"
                            stroke-width="2"
                            style="cursor: grab; pointer-events: auto; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));"
                            @mousedown.stop="startDragAnchor(i, 'start', $event)"
                            title="Glissez sur le bord du rectangle" />
                    
                    <circle :cx="getRealAnchor(i, 'end')?.x || 0"
                            :cy="getRealAnchor(i, 'end')?.y || 0"
                            r="6"
                            fill="#f59e0b"
                            stroke="white"
                            stroke-width="2"
                            style="cursor: grab; pointer-events: auto; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));"
                            @mousedown.stop="startDragAnchor(i, 'end', $event)"
                            title="Glissez sur le bord du rectangle" />
                  </template>
                </g>

                <!-- NŒUDS DE LIAISON (petits points roses) -->
                <g v-for="(node, idx) in junctionNodes" :key="'junction-' + idx">
                  <circle :cx="node.x" :cy="node.y" r="4" fill="#e91e63" stroke="white" stroke-width="1" opacity="0.8" 
                          style="filter: drop-shadow(0 2px 4px rgba(233, 30, 99, 0.3));" />
                </g>
              </svg>

              <!-- BOÎTES PROCESSUS -->
              <transition-group name="box" tag="div">
                <div v-for="node in nodes" :key="node.uid"
                     class="box"
                     :style="{ top: node.y + 'px', left: node.x + 'px' }"
                     :class="{ 
                       selected: selectedNode?.uid === node.uid,
                       source: sourceNode?.uid === node.uid,
                       target: targetNode?.uid === node.uid
                     }"
                     @mousedown="startDrag(node, $event)"
                     @click.stop="clickNode(node)">
                  
                  <div class="box-content">
                    <strong class="box-code">{{ node.code }}</strong>
                    <div class="box-name">{{ node.name }}</div>
                  </div>

                  <button class="btn-delete" 
                          @click.stop="removeNode(node)">
                    <i class="ti ti-x"></i>
                  </button>
                </div>
              </transition-group>

            </div>
          </div>

          <small class="text-muted mt-2 d-block">
            💡 Glissez processus | Cliquez flèche | 🟢/🟠 = Ancres (restent sur les bords) | Nœuds de liaison auto anti-croisement
          </small>
        </b-card>
      </b-col>

      <!-- DROITE: CONTRÔLES -->
      <b-col lg="3">
        <b-card class="mb-2 shadow-sm border-0">
          <h6 class="fw-bold text-primary mb-2">
            <i class="ti ti-circuit-2 me-1"></i>Liaison
          </h6>

          <div v-if="!selectedRel">
            <b-button variant="primary" class="w-100" @click="liaisionMode = true">
              <i class="ti ti-plus me-1"></i>Créer liaison
            </b-button>

            <div v-if="liaisionMode" class="mt-2">
              <div v-if="!sourceNode" class="p-2 bg-info bg-opacity-10 rounded border border-info">
                <strong class="text-info d-block mb-2">1️⃣ Cliquez SOURCE</strong>
              </div>

              <div v-if="sourceNode && !targetNode" class="p-2 bg-success bg-opacity-10 rounded border border-success">
                <strong class="text-success d-block mb-2">2️⃣ Cliquez CIBLE</strong>
                <div class="small text-muted">Source: <span class="badge bg-success">{{ sourceNode.code }}</span></div>
              </div>

              <div v-if="sourceNode && targetNode" class="p-2 bg-success bg-opacity-10 rounded border border-success">
                <strong class="text-success d-block mb-2">✅ Créer!</strong>
                <b-button variant="success" size="sm" class="w-100 mb-1" @click="createRelation">Créer liaison</b-button>
              </div>

              <b-button v-if="sourceNode || targetNode" variant="outline-secondary" size="sm" class="w-100 mt-2" @click="resetConnection">
                Annuler
              </b-button>
              <b-button v-if="!sourceNode" variant="outline-danger" size="sm" class="w-100 mt-2" @click="liaisionMode = false">
                Fermer
              </b-button>
            </div>
          </div>

          <div v-if="selectedRel !== null && relations[selectedRel]" class="p-2 bg-danger bg-opacity-10 rounded border border-danger">
            <strong class="text-danger d-block mb-2">⬅️ Liaison active</strong>
            <div class="small mb-2">
              <div>De: <span class="badge bg-success">{{ getNodeLabel(relations[selectedRel].source_id) }}</span></div>
              <div>À: <span class="badge bg-info">{{ getNodeLabel(relations[selectedRel].target_id) }}</span></div>
              <div v-if="relationHasJunctions(selectedRel)" class="mt-2 p-1 bg-warning bg-opacity-10 rounded">
                <small class="text-warning">📍 Nœuds de liaison (anti-croisement)</small>
              </div>
            </div>

            <small class="text-muted d-block mb-2">
              💡 <strong>Glissez flèche</strong> pour déplacer<br>
              🟢 <strong>Point vert</strong> = Départ (sur bord)<br>
              🟠 <strong>Point orange</strong> = Arrivée (sur bord)
            </small>
            <b-button variant="danger" size="sm" class="w-100 mb-1" @click="deleteRelation(selectedRel)">
              <i class="ti ti-trash me-1"></i>Supprimer
            </b-button>
            <b-button variant="outline-secondary" size="sm" class="w-100" @click="selectedRel = null">
              Fermer
            </b-button>
          </div>
        </b-card>

        <b-card class="shadow-sm border-0">
          <h6 class="fw-bold text-primary mb-2">
            <i class="ti ti-info-circle me-1"></i>Infos
          </h6>
          <div class="small">
            <div style="display: flex; justify-content: space-between">
              <strong>Processus:</strong>
              <span class="badge bg-primary">{{ nodes.length }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 6px">
              <strong>Liaisons:</strong>
              <span class="badge bg-primary">{{ relations.length }}</span>
            </div>
            <div v-if="junctionNodes.length > 0" style="display: flex; justify-content: space-between; margin-top: 6px">
              <strong>Nœuds liaison:</strong>
              <span class="badge bg-warning">{{ junctionNodes.length }}</span>
            </div>
          </div>
          <hr class="my-2">
          <div v-if="lastSave" class="p-2 bg-success bg-opacity-10 rounded border border-success text-center">
            <small class="text-success">
              <i class="ti ti-check me-1"></i>Sauvegardé<br>
              <strong>{{ lastSave }}</strong>
            </small>
          </div>
        </b-card>
      </b-col>
    </b-row>
  </VerticalLayout>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import axios from 'axios'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
import { Head } from '@inertiajs/vue3'

const props = defineProps({
  entity: Object,
  routes: Object
})

const grouped = ref({})
const nodes = ref([])
const relations = ref([])
const anchors = ref({}) // Ancres personnalisées par relation
const selectedNode = ref(null)
const selectedRel = ref(null)
const sourceNode = ref(null)
const targetNode = ref(null)
const lastSave = ref("")
const liaisionMode = ref(false)
const searchProc = ref("")
const zoom = ref(1)
const junctionNodes = ref([])

const wrapperRef = ref(null)
const svgContainer = ref(null)

let draggedProcess = null
let dragging = null
let dragOffset = { x: 0, y: 0 }
let draggedRelation = null
let dragRelationOffset = { x: 0, y: 0 }
let dragAnchor = null

// ===== COMPUTED =====
const filteredGroups = computed(() => {
  if (!searchProc.value) return grouped.value
  
  const filtered = {}
  Object.keys(grouped.value).forEach(key => {
    const group = grouped.value[key]
    const filteredItems = group.items.filter(item => 
      item.code.toLowerCase().includes(searchProc.value.toLowerCase()) ||
      item.name.toLowerCase().includes(searchProc.value.toLowerCase())
    )
    
    if (filteredItems.length > 0) {
      filtered[key] = { ...group, items: filteredItems }
    }
  })
  
  return filtered
})

// ===== CHARGEMENT =====
const loadProcesses = async () => {
  try {
    const res = await axios.get(props.routes.processes)
    grouped.value = res.data.grouped
  } catch (e) {
    console.error('Erreur:', e)
  }
}

const loadRelations = async () => {
  try {
    const res = await axios.get(props.routes.load)
    relations.value = res.data.relations.map(r => ({
      source_id: r.source_process_id,
      target_id: r.target_process_id,
      link_type: r.link_type || 'arrow'
    }))
    
    anchors.value = {}
    res.data.relations.forEach((r, idx) => {
      anchors.value[idx] = {
        start: r.start_anchor || null,
        end: r.end_anchor || null
      }
    })

    initializeAnchors()
    detectJunctions()
  } catch (e) {
    console.error('Erreur:', e)
  }
}

// ===== ZOOM =====
const zoomIn = () => { zoom.value = Math.min(zoom.value + 0.1, 2) }
const zoomOut = () => { zoom.value = Math.max(zoom.value - 0.1, 0.5) }
const resetZoom = () => { zoom.value = 1 }

// ===== DROP PROCESSUS =====
const dropProcess = (e) => {
  if (!draggedProcess) return
  const rect = wrapperRef.value.getBoundingClientRect()
  const x = (e.clientX - rect.left) / zoom.value
  const y = (e.clientY - rect.top) / zoom.value

  nodes.value.push({
    uid: Date.now() + Math.random(),
    id: draggedProcess.id,
    code: draggedProcess.code,
    name: draggedProcess.name,
    macro_id: draggedProcess.macro_id,
    macro_name: draggedProcess.macro_name,
    x: Math.max(0, x - 75),
    y: Math.max(0, y - 20)
  })

  initializeAnchors()
  detectJunctions()
}

// ===== DRAG BOÎTE =====
const startDrag = (node, e) => {
  dragging = node
  dragOffset = {
    x: e.clientX / zoom.value - node.x,
    y: e.clientY / zoom.value - node.y
  }
  window.addEventListener('mousemove', onDrag)
  window.addEventListener('mouseup', stopDrag)
}

const onDrag = (e) => {
  if (!dragging) return
  dragging.x = Math.max(0, e.clientX / zoom.value - dragOffset.x)
  dragging.y = Math.max(0, e.clientY / zoom.value - dragOffset.y)
  detectJunctions()
}

const stopDrag = () => {
  dragging = null
  window.removeEventListener('mousemove', onDrag)
  window.removeEventListener('mouseup', stopDrag)
}

// ===== DRAG LIAISON =====
const startDragRelation = (relIndex, e) => {
  e.preventDefault()
  draggedRelation = relIndex
  selectedRel.value = relIndex
  
  dragRelationOffset = {
    x: e.clientX / zoom.value,
    y: e.clientY / zoom.value
  }
  
  window.addEventListener('mousemove', onDragRelation)
  window.addEventListener('mouseup', stopDragRelation)
}

const onDragRelation = (e) => {
  if (draggedRelation === null) return
  // Pour future: déplacer la liaison entière
}

const stopDragRelation = () => {
  draggedRelation = null
  window.removeEventListener('mousemove', onDragRelation)
  window.removeEventListener('mouseup', stopDragRelation)
}

// ===== DRAG ANCRE (TOUJOURS SUR LES BORDS) =====
const startDragAnchor = (relIndex, side, e) => {
  dragAnchor = { relIndex, side, startX: e.clientX, startY: e.clientY }
  window.addEventListener('mousemove', onDragAnchor)
  window.addEventListener('mouseup', stopDragAnchor)
}

const onDragAnchor = (e) => {
  if (!dragAnchor) return

  const rel = relations.value[dragAnchor.relIndex]
  const isStart = dragAnchor.side === 'start'
  const nodeId = isStart ? rel.source_id : rel.target_id
  const bounds = getNodeBounds(nodeId)

  if (!bounds) return

  const x = e.clientX / zoom.value
  const y = e.clientY / zoom.value

  // Déterminer le bord le plus proche
  const distTop = Math.abs(y - bounds.top)
  const distBottom = Math.abs(y - bounds.bottom)
  const distLeft = Math.abs(x - bounds.left)
  const distRight = Math.abs(x - bounds.right)

  const minDist = Math.min(distTop, distBottom, distLeft, distRight)

  let newAnchor = { ...anchors.value[dragAnchor.relIndex][dragAnchor.side] }

  // Placer sur le bord le plus proche
  if (minDist === distTop) {
    newAnchor = { x: Math.max(bounds.left, Math.min(bounds.right, x)), y: bounds.top }
  } else if (minDist === distBottom) {
    newAnchor = { x: Math.max(bounds.left, Math.min(bounds.right, x)), y: bounds.bottom }
  } else if (minDist === distLeft) {
    newAnchor = { x: bounds.left, y: Math.max(bounds.top, Math.min(bounds.bottom, y)) }
  } else if (minDist === distRight) {
    newAnchor = { x: bounds.right, y: Math.max(bounds.top, Math.min(bounds.bottom, y)) }
  }

  anchors.value[dragAnchor.relIndex][dragAnchor.side] = newAnchor
}

const stopDragAnchor = () => {
  dragAnchor = null
  window.removeEventListener('mousemove', onDragAnchor)
  window.removeEventListener('mouseup', stopDragAnchor)
  detectJunctions()
}

// ===== CALCUL ANCRES PAR DÉFAUT (EN LIVE) =====
const getNodeBounds = (id) => {
  const node = nodes.value.find(n => n.id === id)
  if (!node) return null
  return {
    x: node.x,
    y: node.y,
    w: 150,
    h: 40,
    left: node.x,
    right: node.x + 150,
    top: node.y,
    bottom: node.y + 40,
    centerX: node.x + 75,
    centerY: node.y + 20
  }
}

const getSmartDefaultAnchor = (srcBounds, tgtBounds, isStart) => {
  const dx = tgtBounds.centerX - srcBounds.centerX
  const dy = tgtBounds.centerY - srcBounds.centerY
  const absX = Math.abs(dx)
  const absY = Math.abs(dy)

  if (isStart) {
    if (absX > absY) {
      return dx > 0 
        ? { x: srcBounds.right, y: srcBounds.centerY }
        : { x: srcBounds.left, y: srcBounds.centerY }
    } else {
      return dy > 0
        ? { x: srcBounds.centerX, y: srcBounds.bottom }
        : { x: srcBounds.centerX, y: srcBounds.top }
    }
  } else {
    if (absX > absY) {
      return dx > 0
        ? { x: tgtBounds.left, y: tgtBounds.centerY }
        : { x: tgtBounds.right, y: tgtBounds.centerY }
    } else {
      return dy > 0
        ? { x: tgtBounds.centerX, y: tgtBounds.top }
        : { x: tgtBounds.centerX, y: tgtBounds.bottom }
    }
  }
}

const isAnchorOnBorder = (anchor, bounds) => {
  const tolerance = 2
  const onTop = Math.abs(anchor.y - bounds.top) < tolerance
  const onBottom = Math.abs(anchor.y - bounds.bottom) < tolerance
  const onLeft = Math.abs(anchor.x - bounds.left) < tolerance
  const onRight = Math.abs(anchor.x - bounds.right) < tolerance

  return (onTop || onBottom || onLeft || onRight)
}

// Obtenir l'ancre réelle (personnalisée OU par défaut recalculée en live)
const getRealAnchor = (relIdx, side) => {
  const rel = relations.value[relIdx]
  if (!rel) return null

  // Si ancre personnalisée existe ET est encore valide, l'utiliser
  if (anchors.value[relIdx]?.[side]) {
    // Vérifier que l'ancre personnalisée est toujours sur le bon bord
    const nodeId = side === 'start' ? rel.source_id : rel.target_id
    const bounds = getNodeBounds(nodeId)
    const anchor = anchors.value[relIdx][side]

    if (bounds && isAnchorOnBorder(anchor, bounds)) {
      return anchor
    }
  }

  // Sinon recalculer par défaut en live
  const srcBounds = getNodeBounds(rel.source_id)
  const tgtBounds = getNodeBounds(rel.target_id)

  if (!srcBounds || !tgtBounds) return null

  return getSmartDefaultAnchor(srcBounds, tgtBounds, side === 'start')
}

const initializeAnchors = () => {
  relations.value.forEach((rel, idx) => {
    if (!anchors.value[idx] || !anchors.value[idx].start) {
      const srcBounds = getNodeBounds(rel.source_id)
      const tgtBounds = getNodeBounds(rel.target_id)

      if (srcBounds && tgtBounds) {
        anchors.value[idx] = {
          start: getSmartDefaultAnchor(srcBounds, tgtBounds, true),
          end: getSmartDefaultAnchor(srcBounds, tgtBounds, false)
        }
      }
    }
  })
}

// ===== DÉTECTION DE CROISEMENTS & NŒUDS (EN LIVE) =====
const detectJunctions = () => {
  junctionNodes.value = []

  // Grouper liaisons par direction
  const grouped = {}

  relations.value.forEach((rel, idx) => {
    const src = getNodeBounds(rel.source_id)
    const tgt = getNodeBounds(rel.target_id)

    if (!src || !tgt) return

    const startAnchor = getRealAnchor(idx, 'start')
    const endAnchor = getRealAnchor(idx, 'end')

    if (!startAnchor || !endAnchor) return

    const dx = endAnchor.x - startAnchor.x
    const dy = endAnchor.y - startAnchor.y
    const direction = Math.abs(dx) > Math.abs(dy) ? 'horizontal' : 'vertical'

    if (!grouped[direction]) grouped[direction] = []
    grouped[direction].push({ idx, rel, startAnchor, endAnchor })
  })

  // Créer nœuds pour liaisons du même sens
  Object.values(grouped).forEach(group => {
    if (group.length > 1) {
      for (let i = 0; i < group.length - 1; i++) {
        for (let j = i + 1; j < group.length; j++) {
          const item1 = group[i]
          const item2 = group[j]

          // Créer nœud au milieu
          const juncX = (item1.startAnchor.x + item1.endAnchor.x) / 2
          const juncY = (item1.startAnchor.y + item1.endAnchor.y) / 2

          junctionNodes.value.push({
            x: juncX,
            y: juncY,
            relations: [item1.idx, item2.idx]
          })
        }
      }
    }
  })
}

// ===== CALCUL CHEMIN INTELLIGENT (EN LIVE) =====
const computeIntelligentPath = (rel, i) => {
  const startAnchor = getRealAnchor(i, 'start')
  const endAnchor = getRealAnchor(i, 'end')

  if (!startAnchor || !endAnchor) return ""

  let path = `M ${startAnchor.x} ${startAnchor.y}`

  // Utiliser nœuds de liaison si ils existent
  const relatedJunctions = junctionNodes.value.filter(j => j.relations.includes(i))

  if (relatedJunctions.length > 0) {
    relatedJunctions.forEach(junction => {
      path += ` L ${junction.x} ${junction.y}`
    })
  } else {
    // Chemin orthogonal simple
    const midX = (startAnchor.x + endAnchor.x) / 2
    path += ` L ${midX} ${startAnchor.y} L ${midX} ${endAnchor.y}`
  }

  path += ` L ${endAnchor.x} ${endAnchor.y}`

  return path
}

// ===== INTERACTIONS =====
const clickNode = (node) => {
  if (!liaisionMode.value) {
    selectedNode.value = node
    return
  }

  if (!sourceNode.value) {
    sourceNode.value = node
  } else if (sourceNode.value.uid !== node.uid) {
    targetNode.value = node
  }
}

const removeNode = (node) => {
  nodes.value = nodes.value.filter(n => n.uid !== node.uid)
  const idxToRemove = []
  relations.value.forEach((r, idx) => {
    if (r.source_id === node.id || r.target_id === node.id) {
      idxToRemove.push(idx)
    }
  })
  idxToRemove.reverse().forEach(idx => {
    relations.value.splice(idx, 1)
    delete anchors.value[idx]
  })
  detectJunctions()
}

const getNodeLabel = (id) => {
  const node = nodes.value.find(n => n.id === id)
  return node ? node.code : '?'
}

const resetConnection = () => {
  sourceNode.value = null
  targetNode.value = null
}

const createRelation = () => {
  if (!sourceNode.value || !targetNode.value) return
  if (sourceNode.value.id === targetNode.value.id) return

  relations.value.push({
    source_id: sourceNode.value.id,
    target_id: targetNode.value.id,
    link_type: 'arrow'
  })

  initializeAnchors()
  resetConnection()
  liaisionMode.value = false
  detectJunctions()
}

const selectRelation = (i) => {
  selectedRel.value = selectedRel.value === i ? null : i
}

const deleteRelation = (i) => {
  relations.value.splice(i, 1)
  delete anchors.value[i]
  selectedRel.value = null
  detectJunctions()
}

const deselectAll = () => {
  selectedNode.value = null
  selectedRel.value = null
}

const relationHasJunctions = (relIdx) => {
  return junctionNodes.value.some(j => j.relations.includes(relIdx))
}

// ===== SAUVEGARDE =====
const saveAll = async () => {
  try {
    await axios.post(props.routes.save, {
      relations: relations.value.map((r, idx) => ({
        source_id: r.source_id,
        target_id: r.target_id,
        link_type: r.link_type,
        start_anchor: anchors.value[idx]?.start || null,
        end_anchor: anchors.value[idx]?.end || null
      }))
    })
    lastSave.value = new Date().toLocaleTimeString('fr-FR')
  } catch (e) {
    console.error('Erreur:', e)
  }
}

const reloadAll = () => {
  loadProcesses()
  loadRelations()
  selectedNode.value = null
  selectedRel.value = null
  sourceNode.value = null
  targetNode.value = null
  liaisionMode.value = false
  zoom.value = 1
}

let saveTimer = null
watch(() => relations.value, () => {
  clearTimeout(saveTimer)
  saveTimer = setTimeout(saveAll, 1000)
}, { deep: true })

// Recalculer les nœuds quand les nœuds bougent
watch(() => nodes.value.map(n => ({ x: n.x, y: n.y, id: n.id })), () => {
  detectJunctions()
}, { deep: true })

// Recalculer les nœuds quand les ancres changent
watch(() => anchors.value, () => {
  detectJunctions()
}, { deep: true })

onMounted(reloadAll)
</script>

<style scoped>
.box-enter-active, .box-leave-active { transition: all 0.2s ease; }
.box-enter-from, .box-leave-to { opacity: 0; transform: scale(0.9); }

.scroll-area {
  max-height: 600px;
  overflow-y: auto;
  padding-right: 6px;
}

.scroll-area::-webkit-scrollbar { width: 6px; }
.scroll-area::-webkit-scrollbar-thumb { background: #667eea; border-radius: 10px; }

.group-header {
  background: linear-gradient(135deg, #667eea15, #764ba215);
  padding: 6px 8px;
  margin-top: 10px;
  border-radius: 4px;
  font-size: 0.7rem;
  font-weight: bold;
  color: #667eea;
  border-left: 3px solid #667eea;
}

.proc-item {
  padding: 8px;
  margin: 4px 0;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  background: white;
  cursor: grab;
  font-size: 0.75rem;
  transition: all 0.15s;
}

.proc-item:hover {
  background: linear-gradient(135deg, #667eea10, #764ba210);
  border-color: #667eea;
  box-shadow: 0 2px 6px rgba(102, 126, 234, 0.15);
}

.proc-item strong { color: #667eea; display: block; }
.proc-item small { color: #666; margin-top: 2px; }

.canvas-wrapper {
  position: relative;
  height: 600px;
  border: 2px solid #e0e0e0;
  background: linear-gradient(135deg, #fafbff 0%, #f5f7ff 100%);
  border-radius: 8px;
  overflow: auto;
}

.canvas-content {
  position: relative;
  width: 2000px;
  height: 1500px;
  background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"><circle cx="10" cy="10" r="1" fill="%23e5e7eb"/></svg>');
  background-size: 20px 20px;
}

.svg-bg {
  position: absolute;
  width: 100%;
  height: 100%;
  z-index: 1;
}

.box {
  position: absolute;
  width: 150px;
  padding: 10px;
  background: white;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  cursor: move;
  transition: all 0.1s;
  z-index: 10;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  user-select: none;
}

.box:hover { border-color: #667eea; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2); }
.box.selected { border-color: #667eea; background: #667eea05; }
.box.source { border-color: #10b981; background: #10b98105; }
.box.target { border-color: #f59e0b; background: #f59e0b05; }

.box-content { pointer-events: none; font-size: 0.75rem; }
.box-code { color: #667eea; font-weight: bold; }
.box-name { color: #666; margin-top: 4px; }

.btn-delete {
  position: absolute;
  top: -8px;
  right: -8px;
  width: 24px;
  height: 24px;
  border: none;
  background: #ef4444;
  color: white;
  border-radius: 50%;
  cursor: pointer;
  font-size: 0.7rem;
  opacity: 0;
  transition: opacity 0.2s;
  z-index: 20;
}

.box:hover .btn-delete { opacity: 1; }
.btn-delete:hover { background: #dc2626; }
</style>