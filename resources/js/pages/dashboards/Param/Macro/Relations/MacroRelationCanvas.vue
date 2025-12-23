<template>
  <div class="relative w-full h-[700px] bg-white rounded-lg shadow overflow-hidden">

    <!-- SVG LAYER FOR ARROWS -->
    <svg class="absolute inset-0 w-full h-full pointer-events-none">
      <defs>
        <marker id="arrow" markerWidth="12" markerHeight="12" refX="10" refY="6" orient="auto">
          <path d="M0,0 L12,6 L0,12 z" fill="black" />
        </marker>
      </defs>

      <!-- Draw all links -->
      <line
        v-for="(rel,i) in relationsComputed"
        :key="'l'+i"
        :x1="rel.x1"
        :y1="rel.y1"
        :x2="rel.x2"
        :y2="rel.y2"
        stroke="black"
        stroke-width="2"
        marker-end="url(#arrow)"
      />
    </svg>

    <!-- PROCESSUS BLOCKS -->
    <div
      v-for="(p,i) in nodes"
      :key="p.id"
      class="absolute px-3 py-2 rounded shadow text-white font-semibold cursor-move select-none"
      :style="{
        top: p.y + 'px',
        left: p.x + 'px',
        background: colorByMacro(p.macroProcess?.kind)
      }"
      @mousedown="startDrag($event, p)"
      @mouseup="stopDrag"
      @dblclick="beginLink(p)"
    >
      {{ p.name }}
    </div>

    <!-- SAVE BUTTON -->
    <button
      class="absolute bottom-4 right-4 bg-primary px-5 py-2 rounded-lg text-white shadow"
      @click="save"
    >
      💾 Enregistrer les relations
    </button>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'

/**
 * Props
 */
const props = defineProps({
  entity: Object,
  processes: Array,
  relations: Array,
  routes: Object
})

const emit = defineEmits(['save'])

// ========== INTERNAL STATES ==========
const nodes = ref([])
const dragging = ref(null)
const linkStart = ref(null)
const relationsLocal = ref([])

// Load processes
onMounted(() => {
  // Initialize nodes with default positions depending on macro
  nodes.value = props.processes.map((p, index) => ({
    ...p,
    x: autoX(index),
    y: autoY(p.macroProcess?.kind)
  }))

  // Initialize relations
  relationsLocal.value = props.relations.map(rel => ({
    source_id: rel.source_process_id,
    target_id: rel.target_process_id,
    link_type: rel.link_type,
    metadata: rel.metadata ?? {}
  }))
})

/* -----------------------
   Auto Positioning
------------------------*/
function autoY(kind) {
  if (kind === 'Direction') return 60
  if (kind === 'Réalisation') return 300
  return 520 // Support
}
function autoX(i) {
  return 80 + (i % 8) * 180
}

/* -----------------------
   COLORS BY MACRO
------------------------*/
function colorByMacro(kind) {
  if (kind === 'Direction') return '#d9534f'
  if (kind === 'Réalisation') return '#5bc0de'
  return '#f0ad4e'
}

/* -----------------------
   DRAG LOGIC
------------------------*/
function startDrag(evt, node) {
  dragging.value = {
    node,
    offsetX: evt.clientX - node.x,
    offsetY: evt.clientY - node.y
  }
}

function stopDrag() {
  dragging.value = null
}

window.addEventListener('mousemove', (evt) => {
  if (!dragging.value) return
  dragging.value.node.x = evt.clientX - dragging.value.offsetX
  dragging.value.node.y = evt.clientY - dragging.value.offsetY
})

/* -----------------------
   LINK CREATION (double click)
------------------------*/
function beginLink(node) {
  if (!linkStart.value) {
    linkStart.value = node
  } else {
    if (linkStart.value.id !== node.id) {
      relationsLocal.value.push({
        source_id: linkStart.value.id,
        target_id: node.id,
        link_type: "arrow",
        metadata: {}
      })
    }
    linkStart.value = null
  }
}

/* -----------------------
   COMPUTE ARROWS POSITIONS
------------------------*/
const relationsComputed = ref([])

watch([nodes, relationsLocal], () => {
  relationsComputed.value = relationsLocal.value
    .map(rel => {
      const source = nodes.value.find(n => n.id === rel.source_id)
      const target = nodes.value.find(n => n.id === rel.target_id)

      if (!source || !target) return null

      return {
        x1: source.x + 80,
        y1: source.y + 25,
        x2: target.x + 80,
        y2: target.y + 25
      }
    })
    .filter(Boolean)
}, { deep: true })

/* -----------------------
   SAVE
------------------------*/
function save() {
  const payload = relationsLocal.value.map(rel => ({
    source_id: rel.source_id,
    target_id: rel.target_id,
    link_type: rel.link_type,
    metadata: rel.metadata ?? {}
  }))

  emit('save', payload)
}
</script>

<style scoped>
.graph-container {
  border: 1px solid #ddd;
}
</style>
