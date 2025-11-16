<template>
  <VerticalLayout>
    <Head title="Organigramme — Fonctions par Entité" />

    <b-row class="mb-1">
      <b-col>
        <div class="d-flex align-items-center justify-content-between">
          <!-- Titre -->
          <div class="d-flex align-items-center gap-2">
            <i class="ti ti-hierarchy-3 text-primary fs-5"></i>
            <h4 class="m-0 fw-semibold">Organigramme des fonctions</h4>
            <small class="text-muted ms-2">Par entité — liaison principale & ordre du référentiel</small>
          </div>

          <!-- Outils (select entité + zoom) -->
          <div class="d-flex align-items-center gap-2">
            <b-input-group size="sm" class="w-auto">
              <b-input-group-text><i class="ti ti-building"></i></b-input-group-text>
              <b-form-select
                v-model="selectedEntityId"
                :options="entityOptions"
                @change="onEntityChange"
              />
            </b-input-group>

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
        <b-alert v-if="!selectedEntityId" show variant="secondary" class="py-2 px-3 mb-2">
          Sélectionnez une entité pour afficher l’organigramme.
        </b-alert>

        <div v-else class="canvas-wrap">
          <!-- Chargement -->
          <div v-if="loading" class="text-center py-5">
            <b-spinner variant="primary" />
            <div class="mt-2">Chargement de l’organigramme…</div>
          </div>

          <!-- Vide -->
          <div v-else-if="!positionedNodes.length" class="text-center py-5 text-muted">
            <i class="ti ti-chart-bar-off fs-1"></i>
            <p class="mt-2">Aucune donnée disponible pour l’organigramme</p>
          </div>

          <!-- Graphe SVG -->
          <svg v-else :viewBox="viewBox" preserveAspectRatio="xMinYMin meet" :style="svgStyle">
            <!-- Liaisons orthogonales -->
            <g class="edges">
              <template v-for="e in edges" :key="e.id">
                <polyline :points="e.points" class="edge-ortho" />
                <circle v-for="(j,i) in e.joints" :key="i" :cx="j.x" :cy="j.y" r="2.8" class="joint" />
              </template>
            </g>

            <!-- Nœuds -->
            <g class="nodes">
              <template v-for="n in positionedNodes" :key="n.id">
                <g :transform="`translate(${n.x}, ${n.y})`" :class="['uml', n.kind, { ghost: n.ghost }]">
                  <!-- Boîte -->
                  <rect :width="NODE_W" :height="NODE_H" rx="10" ry="10" class="uml-box" />

                  <!-- Avatar/Initiales -->
                  <defs>
                    <clipPath :id="`clip-node-${n.id}`">
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
                    :clip-path="`url(#clip-node-${n.id})`"
                  />
                  <text v-else :x="CENTER_X" :y="AVATAR_Y + 4" text-anchor="middle" class="avatar-initials">
                    {{ n.initials }}
                  </text>

                  <!-- Titres -->
                  <text :x="CENTER_X" :y="TITLE_Y" text-anchor="middle" class="uml-title">
                    {{ n.title }}
                  </text>
                  <text :x="CENTER_X" :y="SUBTITLE_Y" text-anchor="middle" class="uml-sub">
                    {{ n.subtitle || '—' }}
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
import { computed, onMounted, ref } from 'vue'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

/** ========= Props =========
 * On attend côté Inertia:
 * - entities: [{id,name,code_base,parent_id,...}]
 * - initialEntityId?: number
 */
const props = defineProps({
  entities: { type: Array, default: () => [] },
  initialEntityId: { type: Number, default: null }
})

/** ========= Sélection entité ========= */
const selectedEntityId = ref(props.initialEntityId ?? null)
const entityOptions = computed(() => [
  { value: null, text: '— Sélectionner une entité —', disabled: true },
  ...(props.entities || []).map(e => ({
    value: e.id,
    text: `${e.name} (${e.code_base || 'N/A'})`
  }))
])

/** ========= State & fetch ========= */
const loading = ref(false)
const chartData = ref([]) // tableau de { id,name,type:'entity', children:[functions...], children_entities:[...] }

/** Utilitaires fetch */
const getCsrfToken = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

async function loadChartData(entityId) {
  if (!entityId) { chartData.value = []; return }
  loading.value = true
  chartData.value = []
  try {
    const url = `/param/charts/entity-functions-data/${entityId}`
    const res = await fetch(url, {
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': getCsrfToken()
      }
    })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    const data = await res.json()
    chartData.value = Array.isArray(data) ? data : [data]
  } catch (e) {
    console.error('Erreur chargement organigramme:', e)
    chartData.value = []
  } finally {
    loading.value = false
  }
}

function onEntityChange(val) {
  // Recharge et met à jour l’URL (sans reload)
  loadChartData(val)
  if (val) window.history.replaceState({}, '', `/param/charts/entity-functions/${val}`)
  else window.history.replaceState({}, '', `/param/charts/entity-functions`)
}

/** ========= Flatten (relations + ordre du référentiel) =========
 * Back renvoie pour chaque entité:
 *   children: [{ id, name, character, parent_id, avatar_url, ghost, order }]
 * - Liaison principale: fonction -> parent fonction si parent présent, sinon -> entité
 * - Ordre: par 'order', sinon par name/id
 */
const EKey = (eid) => `E-${eid}`
const FKey = (fid, eid) => `F-${fid}-E${eid}`

function flatten(data) {
  const out = []
  if (!data || !data.length) return out

  const walkEntity = (entity, parentKey = 'root') => {
    const eid = Number(entity.id)
    const eKey = EKey(eid)

    // Noeud entité
    out.push({
      id: eKey,
      parentKey,
      kind: 'entity',
      title: entity.name,
      subtitle: entity.code_base || entity.code || '',
      avatar: entity.avatar_url || null,
      ghost: false,
      order: 0
    })

    const funcs = (entity.children || []).slice()
    const funcIdSet = new Set(funcs.map(f => Number(f.id)))

    // d'abord les ghosts (parents non affectés), puis assignées — dans l'ordre "order"
    const byOrder = (a, b) => {
      const oa = a.order ?? 0, ob = b.order ?? 0
      if (oa !== ob) return oa - ob
      // fallback: alpha puis id
      const na = (a.name || '').localeCompare(b.name || '')
      return na !== 0 ? na : (a.id || 0) - (b.id || 0)
    }

    funcs.filter(f => f.ghost).sort(byOrder).forEach(f => {
      const parentKey = f.parent_id && funcIdSet.has(Number(f.parent_id))
        ? FKey(f.parent_id, eid) : eKey
      out.push({
        id: FKey(f.id, eid),
        parentKey,
        kind: 'function',
        title: f.name,
        subtitle: f.character || '',
        avatar: f.avatar_url || null,
        ghost: true,
        order: f.order ?? 0
      })
    })

    funcs.filter(f => !f.ghost).sort(byOrder).forEach(f => {
      const parentKey = f.parent_id && funcIdSet.has(Number(f.parent_id))
        ? FKey(f.parent_id, eid) : eKey
      out.push({
        id: FKey(f.id, eid),
        parentKey,
        kind: 'function',
        title: f.name,
        subtitle: f.character || '',
        avatar: f.avatar_url || null,
        ghost: false,
        order: f.order ?? 0
      })
    })

    // Sous-entités éventuelles (si tu en affiches plusieurs niveaux côté data)
    ;(entity.children_entities || []).forEach(child => walkEntity(child, eKey))
  }

  data.forEach(root => { if (root.type === 'entity') walkEntity(root, 'root') })
  return out
}

const flatItems = computed(() => flatten(chartData.value))

/** ========= Layout (top-down, centré, orthogonal) ========= */
const NODE_W = 280, NODE_H = 120
const AVATAR_R = 18
const CENTER_X = NODE_W / 2
const AVATAR_Y = 28
const TITLE_Y = AVATAR_Y + AVATAR_R + 18
const SUBTITLE_Y = TITLE_Y + 16
const H_GAP = 40, V_GAP = 110

const zoom = ref(100)
const svgStyle = computed(() => ({
  width: '100%',
  height: '70vh',
  background: '#fff',
  transform: `scale(${zoom.value/100})`,
  transformOrigin: '0 0'
}))

// groupement parent -> enfants
const childrenByParent = computed(() => {
  const m = new Map()
  flatItems.value.forEach(it => {
    const key = String(it.parentKey ?? 'root')
    if (!m.has(key)) m.set(key, [])
    m.get(key).push(it)
  })

  // Tri: entité en tête; fonctions par 'order'
  for (const [, arr] of m) {
    arr.sort((a, b) => {
      if (a.kind !== b.kind) return a.kind === 'entity' ? -1 : 1
      if (a.kind === 'function') {
        const oa = a.order ?? 0, ob = b.order ?? 0
        if (oa !== ob) return oa - ob
      }
      const na = (a.title || '').localeCompare(b.title || '')
      return na !== 0 ? na : (a.id || '').toString().localeCompare((b.id || '').toString())
    })
  }
  return m
})

const roots = computed(() => childrenByParent.value.get('root') || [])

function assignPositions() {
  const nodes = []
  let xCursor = 0

  function place(node, depth) {
    const kids = childrenByParent.value.get(String(node.id)) || []
    if (!kids.length) {
      const x = xCursor
      const y = depth * (NODE_H + V_GAP)
      xCursor += NODE_W + H_GAP
      nodes.push({ node, x, y })
      return { xCenter: x + NODE_W / 2 }
    }
    const centers = kids.map(k => place(k, depth + 1))
    const xCenter = (centers[0].xCenter + centers[centers.length - 1].xCenter) / 2
    const x = xCenter - NODE_W / 2
    const y = depth * (NODE_H + V_GAP)
    nodes.push({ node, x, y })
    return { xCenter }
  }

  roots.value.forEach(r => { place(r, 0); xCursor += H_GAP })
  return nodes
}

const positionedNodes = computed(() =>
  assignPositions().map(({ node, x, y }) => {
    const initials = (node.kind === 'entity'
      ? (node.title || '').split(/\s+/).map(w => w[0] || '').join('')
      : (node.subtitle || node.title || '').split(/\s+/).map(w => w[0] || '').join('')
    ).slice(0, 3).toUpperCase()

    return {
      id: node.id,
      kind: node.kind,
      title: node.title,
      subtitle: node.subtitle,
      avatar: node.avatar || null,
      ghost: !!node.ghost,
      x, y,
      initials
    }
  })
)

// index pour edges
const nodeIndex = computed(() =>
  Object.fromEntries(positionedNodes.value.map(n => [String(n.id), { x: n.x, y: n.y }]))
)

const edges = computed(() => {
  const res = []
  flatItems.value.forEach(it => {
    if (!it.parentKey || it.parentKey === 'root') return
    const child = nodeIndex.value[String(it.id)]
    const par = nodeIndex.value[String(it.parentKey)]
    if (!child || !par) return
    const x1 = par.x + NODE_W / 2, y1 = par.y + NODE_H
    const x2 = child.x + NODE_W / 2, y2 = child.y
    const yMid = (y1 + y2) / 2
    res.push({
      id: `${it.parentKey}->${it.id}`,
      points: `${x1},${y1} ${x1},${yMid} ${x2},${yMid} ${x2},${y2}`,
      joints: [{ x: x1, y: yMid }, { x: x2, y: yMid }]
    })
  })
  return res
})

/** ViewBox auto */
const viewBox = computed(() => {
  const nodes = positionedNodes.value
  if (!nodes.length) return `0 0 800 400`
  const minX = Math.min(...nodes.map(n => n.x)) - 60
  const minY = Math.min(...nodes.map(n => n.y)) - 60
  const maxX = Math.max(...nodes.map(n => n.x + NODE_W)) + 60
  const maxY = Math.max(...nodes.map(n => n.y + NODE_H)) + 60
  return `${minX} ${minY} ${maxX - minX} ${maxY - minY}`
})

/** Cycle */
onMounted(() => {
  // Si une entité initiale est fournie (via /{id})
  if (selectedEntityId.value) loadChartData(selectedEntityId.value)
})
</script>

<style scoped>
.canvas-wrap{ width:100%; height:70vh; overflow:auto; background:#fafafa; border:1px solid #e5e7eb; border-radius:.5rem }

/* Liaisons */
.edge-ortho{ stroke:#64748b; stroke-width:1.2; fill:none }
.joint{ fill:#ffffff; stroke:#64748b; stroke-width:1.2 }

/* Boîte */
.uml-box{ fill:#ffffff; stroke:#cbd5e1; stroke-width:1.2 }
.uml.function.ghost .uml-box{ stroke-dasharray:6 4; opacity:.9 }

/* Avatar */
.avatar-ring{ fill:#e2e8f0; stroke:#cbd5e1; stroke-width:1 }
.avatar-initials{
  font: 800 12px/1 ui-monospace, SFMono-Regular, Menlo, Consolas, "Courier New", monospace;
  fill:#0f172a;
}

/* Textes */
.uml-title{
  font: 700 13px/1.2 ui-sans-serif,system-ui,Segoe UI,Roboto,Helvetica,Arial,sans-serif;
  fill:#0f172a;
}
.uml-sub{
  font: 600 11px/1 ui-monospace, SFMono-Regular, Menlo, Consolas, "Courier New", monospace;
  fill:#475569;
}
</style>
