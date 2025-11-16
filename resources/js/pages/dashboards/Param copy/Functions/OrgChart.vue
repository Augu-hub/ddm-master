<template>
  <VerticalLayout>
    <Head title="Organigramme — Fonctions" />

    <b-row class="mb-1">
      <b-col>
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <i class="ti ti-chart-org text-primary fs-5"></i>
            <h4 class="m-0 fw-semibold">Organigramme des fonctions</h4>
            <small class="text-muted ms-2">Vue diagramme hiérarchique</small>
          </div>
          <b-input-group size="sm" class="w-auto">
            <b-input-group-text><i class="ti ti-briefcase"></i></b-input-group-text>
            <b-form-select v-model="selectedProjectId" :options="projectOptions" />
          </b-input-group>
        </div>
      </b-col>
    </b-row>

    <b-card no-body class="shadow-sm">
      <b-card-body class="p-2">
        <b-alert v-if="!selectedProjectId" show variant="secondary" class="py-2 px-3 mb-2">
          Sélectionnez un projet pour afficher l’organigramme.
        </b-alert>

        <div v-else class="org-wrapper">
          <ul class="org-tree">
            <TreeNode
              v-for="root in roots"
              :key="root.id"
              :node="root"
              :children="childrenByParent.get(String(root.id)) || []"
              :map="childrenByParent"
            />
          </ul>
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
  projects:  { type: Array, default: () => [] }, // [{id,name}]
  functions: { type: Array, default: () => [] }, // [{id,project_id,name,character,parent_id}]
  filters:   { type: Object, default: () => ({ project_id: null }) },
})

const selectedProjectId = ref(props.filters?.project_id ?? (props.projects?.[0]?.id ?? null))
const projectOptions = computed(() => [
  { value: null, text: '— Sélectionner un projet —', disabled: true },
  ...(props.projects||[]).map(p => ({ value: p.id, text: p.name }))
])

const funcs = computed(() => (props.functions||[]).filter(f => String(f.project_id) === String(selectedProjectId.value)))
const childrenByParent = computed(() => {
  const m = new Map()
  funcs.value.forEach(f => {
    const k = String(f.parent_id ?? 'root')
    if (!m.has(k)) m.set(k, [])
    m.get(k).push(f)
  })
  // tri par nom lisible
  for (const [k, arr] of m) arr.sort((a,b) => (a.name||'').localeCompare(b.name||''))
  return m
})

const roots = computed(() => childrenByParent.value.get('root') || [])

/* Composant récursif local */
import { defineComponent } from 'vue'
const TreeNode = defineComponent({
  name: 'TreeNode',
  props: {
    node: { type: Object, required: true },
    children: { type: Array, default: () => [] },
    map: { type: Object, required: true }, // Map parent->children
  },
  setup(props) {
    const code = computed(() => (props.node.character || '').toString())
    const initials = computed(() => {
      const raw = code.value.trim()
      if (raw) return raw.slice(0,3).toUpperCase()
      const nm = (props.node.name || '').trim()
      if (!nm) return 'F'
      return nm.split(/\s+/).map(w=>w[0]||'').join('').slice(0,3).toUpperCase()
    })
    const children = computed(() => props.map.get(String(props.node.id)) || [])
    return { code, initials, children }
  },
  template: `
    <li>
      <div class="node-card">
        <span class="fx-badge fx-org" role="img" aria-label="fonction">{{ initials }}</span>
        <span class="code-chip font-mono">{{ code || '—' }}</span>
        <span class="title">{{ node.name }}</span>
      </div>
      <ul v-if="children.length">
        <TreeNode
          v-for="c in children"
          :key="c.id"
          :node="c"
          :children="[]"
          :map="map"
        />
      </ul>
    </li>
  `
})
</script>

<style scoped>
/* wrapper */
.org-wrapper{ overflow:auto; padding:.25rem }

/* UL/LI Tree avec connecteurs */
.org-tree,
.org-tree ul{ position:relative; padding-top:1rem; padding-left:0; }
.org-tree li{
  list-style:none; text-align:center; padding:.75rem .5rem 0 .5rem; position:relative; display:inline-block;
}

/* lignes verticales/horizontales */
.org-tree li::before, .org-tree li::after{
  content:''; position:absolute; top:0; right:50%; border-top:1px solid #d4d4d8; width:50%; height:1rem;
}
.org-tree li::after{ right:auto; left:50%; border-left:0; border-right:0 }
.org-tree li:only-child::after, .org-tree li:only-child::before{ display:none }
.org-tree li:only-child{ padding-top:0 }
.org-tree li:first-child::before{ border:none }
.org-tree li:last-child::after{ border:none }
.org-tree ul::before{
  content:''; position:absolute; top:0; left:50%; border-left:1px solid #d4d4d8; width:0; height:1rem;
}

/* Carte de nœud */
.node-card{
  display:inline-flex; align-items:center; gap:.35rem;
  background:#ffffff; border:1px solid #e5e7eb; border-radius:.5rem; padding:.25rem .5rem;
  box-shadow:0 1px 0 rgba(0,0,0,.02);
  white-space:nowrap;
}

/* pictogramme */
.fx-badge{
  min-width:26px; height:20px; border-radius:.35rem;
  display:inline-flex; align-items:center; justify-content:center;
  padding:0 .4rem; font-weight:900; font-size:.78rem; line-height:1;
  color:#111; background:#fef08a; border:1px solid #eab308;
}
.fx-org{ background:#c7e3ff; border-color:#93c5fd } /* org spécifique */

/* code avant nom */
.code-chip{
  background:#eef2f7; border:1px solid #e2e8f0; border-radius:.35rem;
  padding:.05rem .35rem; font-size:.72rem; color:#0f172a;
}
.font-mono{ font-family: ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace }

/* titre */
.title{ font-weight:600; font-size:.82rem; color:#0f172a }
</style>
