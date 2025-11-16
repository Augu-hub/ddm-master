<template>
  <VerticalLayout>
    <Head title="Organigramme — Fonctions (UML)" />

    <!-- Zoom + export -->
    <div class="flex items-center gap-2 mb-2">
      <b-button size="sm" variant="outline-secondary" @click="zoomOut">-</b-button>
      <input type="range" v-model="zoom" min="20" max="200" step="10" class="form-range w-40" />
      <b-button size="sm" variant="outline-secondary" @click="zoomIn">+</b-button>
      <span class="text-sm font-medium">{{ zoom }}%</span>
      <b-button size="sm" variant="outline-primary" class="ml-auto" @click="download">
        <i class="ti ti-download"></i> Télécharger
      </b-button>
    </div>

    <b-card no-body class="shadow-sm">
      <b-card-body class="p-2">
        <div class="canvas-wrap">
          <svg
            id="orgchart"
            :viewBox="viewBox"
            preserveAspectRatio="xMinYMin meet"
            :style="svgStyle"
          >
            <defs>
              <linearGradient id="grad-box" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#f8fafc" />
                <stop offset="100%" stop-color="#e2e8f0" />
              </linearGradient>
            </defs>

            <!-- Arêtes -->
            <g class="edges">
              <template v-for="edge in edges" :key="edge.id">
                <polyline :points="edge.points" class="edge-ortho" />
                <circle
                  v-for="(j,i) in edge.joints"
                  :key="i"
                  :cx="j.x"
                  :cy="j.y"
                  r="2.8"
                  class="joint"
                />
              </template>
            </g>

            <!-- Noeuds -->
            <g class="nodes">
              <template v-for="n in positionedNodes" :key="n.id">
                <g :transform="`translate(${n.x}, ${n.y})`" class="uml">
                  <!-- Boîte -->
                  <rect
                    :width="NODE_W"
                    :height="NODE_H"
                    rx="14"
                    ry="14"
                    class="uml-box"
                  />

                  <!-- Avatar -->
                  <defs>
                    <clipPath :id="`clip-${n.id}`">
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
                    :clip-path="`url(#clip-${n.id})`"
                    preserveAspectRatio="xMidYMid slice"
                  />
                  <text
                    v-else
                    :x="CENTER_X"
                    :y="AVATAR_Y + 5"
                    text-anchor="middle"
                    class="avatar-initials"
                  >
                    {{ n.initials }}
                  </text>

                  <!-- Nom -->
                  <text :x="CENTER_X" :y="TITLE_Y" text-anchor="middle" class="uml-title">
                    {{ n.name }}
                  </text>
                  <!-- Sous-titre (caractère) -->
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
  // Global : plus de projects / filters
  // Chaque item doit ressembler à :
  // { id, name, character, parent_id, avatar_url? }
  functions: { type: Array, default: () => [] },
})

/* Données (globales) */
const items = computed(() => props.functions)

/* Constantes dessin */
const NODE_W = 280, NODE_H = 140
const AVATAR_R = 30
const CENTER_X = NODE_W/2
const AVATAR_Y = 45
const TITLE_Y = NODE_H - 35
const SUBTITLE_Y = NODE_H - 15
const H_GAP = 50, V_GAP = 140

/* Zoom */
const zoom = ref(100)
const svgStyle = computed(() => ({
  width: '100%',
  height: '70vh',
  background: '#fff',
  transform: `scale(${zoom.value/100})`,
  transformOrigin: '0 0'
}))
function zoomIn() { if (zoom.value < 200) zoom.value += 10 }
function zoomOut() { if (zoom.value > 20) zoom.value -= 10 }

/* Arbre parent → enfants */
const childrenByParent = computed(() => {
  const m = new Map()
  items.value.forEach(it => {
    const key = String(it.parent_id ?? 'root')
    if (!m.has(key)) m.set(key, [])
    m.get(key).push(it)
  })
  for (const [,arr] of m) arr.sort((a,b)=> (a.name||'').localeCompare(b.name||'')) 
  return m
})
const roots = computed(() => childrenByParent.value.get('root') || [])

/* Placement */
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

const positionedNodes = computed(() => {
  return assignPositions().map(({node,x,y})=>{
    const name = (node.name || '').toString().trim()
    const initials = (name ? name.split(/\s+/).map(w => w[0] || '').join('') : 'F').slice(0,3).toUpperCase()
    return {
      id: node.id,
      name: node.name,
      code: (node.character || '').toString(),
      initials,
      avatar: node.avatar_url || null,
      x, y
    }
  })
})

/* Index */
const nodeById = computed(() =>
  Object.fromEntries(positionedNodes.value.map(n => [String(n.id), n]))
)

/* Arêtes */
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

/* viewBox */
const viewBox = computed(() => {
  const nodes = positionedNodes.value
  if (!nodes.length) return `0 0 800 400`
  const minX = Math.min(...nodes.map(n=>n.x)) - 60
  const minY = Math.min(...nodes.map(n=>n.y)) - 60
  const maxX = Math.max(...nodes.map(n=>n.x + NODE_W)) + 60
  const maxY = Math.max(...nodes.map(n=>n.y + NODE_H)) + 60
  return `${minX} ${minY} ${maxX-minX} ${maxY-minY}`
})

/* Export PNG */
function downloadSVGAsPNG(svgEl, name = 'organigramme-fonctions.png') {
  const serializer = new XMLSerializer()
  const svgStr = serializer.serializeToString(svgEl)

  const canvas = document.createElement('canvas')
  const bbox = svgEl.getBBox()
  canvas.width = bbox.width + 100
  canvas.height = bbox.height + 100
  const ctx = canvas.getContext('2d')

  const img = new Image()
  img.onload = function() {
    ctx.fillStyle = '#fff'
    ctx.fillRect(0, 0, canvas.width, canvas.height)
    ctx.drawImage(img, 50, 50)
    const a = document.createElement('a')
    a.download = name
    a.href = canvas.toDataURL('image/png')
    a.click()
  }
  img.src = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svgStr)))
}
function download() {
  const svgEl = document.getElementById('orgchart')
  if (svgEl) downloadSVGAsPNG(svgEl)
}
</script>

<style scoped>
.canvas-wrap {
  width: 100%;
  height: 70vh;
  overflow: auto;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: .5rem;
}

/* Arêtes */
.edge-ortho{ stroke:#64748b; stroke-width:1.5; fill:none }
.joint{ fill:#fff; stroke:#64748b; stroke-width:1.2 }

/* Boîte */
.uml-box{
  fill:url(#grad-box);
  stroke:#94a3b8;
  stroke-width:1.2;
  filter:drop-shadow(2px 2px 4px rgba(0,0,0,0.1))
}
.uml:hover .uml-box{ stroke:#2563eb; stroke-width:1.8; }

/* Avatar */
.avatar-ring{ fill:#e2e8f0; stroke:#94a3b8; stroke-width:1; }
.avatar-initials{
  font: 800 15px/1 ui-monospace, SFMono-Regular, Menlo, Consolas, "Courier New", monospace;
  fill:#0f172a;
}

/* Textes */
.uml-title{
  font: 700 15px/1.2 ui-sans-serif,system-ui,Segoe UI,Roboto,Helvetica,Arial,sans-serif;
  fill:#1e293b;
}
.uml-sub{
  font: 600 13px/1 ui-monospace, SFMono-Regular, Menlo, Consolas, "Courier New", monospace;
  fill:#475569;
}
</style>
