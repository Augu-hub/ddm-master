<template>
  <VerticalLayout>
    <Head title="Organigramme — Entités" />

    <b-row class="mb-1">
      <b-col>
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <i class="ti ti-building-skyscraper text-primary fs-5"></i>
            <h4 class="m-0 fw-semibold">Organigramme des entités</h4>
            <small class="text-muted ms-2">Hiérarchie (avatar + liaisons orthogonales)</small>
          </div>

          <!-- Zoom seulement (projet supprimé) -->
          <div class="d-flex align-items-center gap-2">
            <div class="d-flex align-items-center gap-1">
              <small class="text-muted">Zoom</small>
              <input type="range" min="50" max="160" step="10" v-model.number="zoom" />
            </div>
          </div>
        </div>
      </b-col>
    </b-row>

    <b-card no-body class="shadow-sm">
      <b-card-body class="p-2">
        <div class="canvas-wrap">
          <svg :viewBox="viewBox" preserveAspectRatio="xMinYMin meet" :style="svgStyle">
            <!-- Liaisons orthogonales -->
            <g class="edges">
              <template v-for="edge in edges" :key="edge.id">
                <polyline :points="edge.points" class="edge-ortho" />
                <circle v-for="(j,i) in edge.joints" :key="i" :cx="j.x" :cy="j.y" r="2.8" class="joint" />
              </template>
            </g>

            <!-- Boîtes -->
            <g class="nodes">
              <template v-for="n in positionedNodes" :key="n.id">
                <g :transform="`translate(${n.x}, ${n.y})`" class="uml">
                  <rect :width="NODE_W" :height="NODE_H" rx="10" ry="10" class="uml-box" />

                  <!-- Avatar cercle centré -->
                  <defs>
                    <clipPath :id="`clip-entity-${n.id}`">
                      <circle :cx="CENTER_X" :cy="AVATAR_Y" :r="AVATAR_R" />
                    </clipPath>
                  </defs>
                  <circle :cx="CENTER_X" :cy="AVATAR_Y" :r="AVATAR_R" class="avatar-ring" />
                  <image
                    v-if="n.avatar"
                    :href="n.avatar"
                    :x="CENTER_X - AVATAR_R"
                    :y="AVATAR_Y - AVATAR_R"
                    :width="AVATAR_R*2"
                    :height="AVATAR_R*2"
                    :clip-path="`url(#clip-entity-${n.id})`"
                  />
                  <text v-else :x="CENTER_X" :y="AVATAR_Y + 4" text-anchor="middle" class="avatar-initials">
                    {{ n.initials }}
                  </text>

                  <!-- Nom centré -->
                  <text :x="CENTER_X" :y="TITLE_Y" text-anchor="middle" class="uml-title">
                    {{ n.name }}
                  </text>
                  <!-- Code en sous-titre -->
                  <text :x="CENTER_X" :y="SUBTITLE_Y" text-anchor="middle" class="uml-sub">
                    {{ n.code || '—' }}
                  </text>
                </g>
              </template>
            </g>
          </svg>
        </div>
      </b-card-body>
    </b-card>
  </VerticalLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps({
  // GLOBAL: plus de projects/filters
  entities:  { type: Array,  default: () => [] }, // {id, name, code, parent_id, avatar_url?}
})

/* Données (globales) */
const items = computed(() => props.entities)

/* Tailles & positions */
const NODE_W = 280, NODE_H = 120
const AVATAR_R = 18
const CENTER_X = NODE_W/2
const AVATAR_Y = 28
const TITLE_Y = AVATAR_Y + AVATAR_R + 18
const SUBTITLE_Y = TITLE_Y + 16
const H_GAP = 40, V_GAP = 110

/* Zoom */
const zoom = ref(100)
const svgStyle = computed(() => ({
  width: '100%',
  height: '70vh',
  background: '#fff',
  transform: `scale(${zoom.value/100})`,
  transformOrigin: '0 0'
}))

/* Parent → enfants */
const childrenByParent = computed(() => {
  const m = new Map()
  items.value.forEach(it => {
    const key = String(it.parent_id ?? 'root')
    if (!m.has(key)) m.set(key, [])
    m.get(key).push(it)
  })
  for (const [,arr] of m) arr.sort((a,b)=> (a.name||'').localeCompare(b.name||'')) // tri alpha
  return m
})
const roots = computed(() => childrenByParent.value.get('root') || [])

/* Placement simple (top-down centré sur enfants) */
function assignPositions(){
  const nodes = []
  let xCursor = 0
  function place(node, depth){
    const kids = childrenByParent.value.get(String(node.id)) || []
    if (!kids.length){
      const x = xCursor
      const y = depth * (NODE_H + V_GAP)
      xCursor += NODE_W + H_GAP
      nodes.push({ node, x, y })
      return { xCenter: x + NODE_W/2 }
    }
    const centers = kids.map(k => place(k, depth+1))
    const xCenter = (centers[0].xCenter + centers[centers.length-1].xCenter)/2
    const x = xCenter - NODE_W/2
    const y = depth * (NODE_H + V_GAP)
    nodes.push({ node, x, y })
    return { xCenter }
  }
  roots.value.forEach(r => { place(r, 0); xCursor += H_GAP })
  return nodes
}

const positionedNodes = computed(() =>
  assignPositions().map(({node,x,y}) => ({
    id: node.id,
    name: node.name,
    code: (node.code||'').toString(),
    initials: ((node.code||'').trim() || (node.name||'').split(/\s+/).map(w=>w[0]||'').join('')).slice(0,3).toUpperCase(),
    avatar: node.avatar_url || null,
    x, y
  }))
)

/* Index par id */
const nodeById = computed(() =>
  Object.fromEntries(positionedNodes.value.map(n => [String(n.id), n]))
)

/* Arêtes orthogonales */
const edges = computed(() => {
  const res = []
  items.value.forEach(it => {
    if (!it.parent_id) return
    const child = nodeById.value[String(it.id)]
    const par   = nodeById.value[String(it.parent_id)]
    if (!child || !par) return
    const x1 = par.x + NODE_W/2, y1 = par.y + NODE_H
    const x2 = child.x + NODE_W/2, y2 = child.y
    const yMid = (y1 + y2) / 2
    const points = `${x1},${y1} ${x1},${yMid} ${x2},${yMid} ${x2},${y2}`
    const joints = [ {x:x1, y:yMid}, {x:x2, y:yMid} ]
    res.push({ id: `${it.parent_id}-${it.id}`, points, joints })
  })
  return res
})

/* viewBox auto */
const viewBox = computed(() => {
  const nodes = positionedNodes.value
  if (!nodes.length) return `0 0 800 400`
  const minX = Math.min(...nodes.map(n=>n.x)) - 60
  const minY = Math.min(...nodes.map(n=>n.y)) - 60
  const maxX = Math.max(...nodes.map(n=>n.x + NODE_W)) + 60
  const maxY = Math.max(...nodes.map(n=>n.y + NODE_H)) + 60
  return `${minX} ${minY} ${maxX-minX} ${maxY-minY}`
})
</script>

<style scoped>
.canvas-wrap{ width:100%; height:70vh; overflow:auto; background:#fafafa; border:1px solid #e5e7eb; border-radius:.5rem }
.edge-ortho{ stroke:#64748b; stroke-width:1.2; fill:none }
.joint{ fill:#ffffff; stroke:#64748b; stroke-width:1.2 }
.uml-box{ fill:#ffffff; stroke:#cbd5e1; stroke-width:1.2 }
.avatar-ring{ fill:#e2e8f0; stroke:#cbd5e1; stroke-width:1 }
.avatar-initials{ font: 800 12px/1 ui-monospace, SFMono-Regular, Menlo, Consolas, "Courier New", monospace; fill:#0f172a; }
.uml-title{ font: 700 13px/1.2 ui-sans-serif,system-ui,Segoe UI,Roboto,Helvetica,Arial,sans-serif; fill:#0f172a; }
.uml-sub{ font: 600 11px/1 ui-monospace, SFMono-Regular, Menlo, Consolas, "Courier New", monospace; fill:#475569; }
</style>
