<template>
  <VerticalLayout>
    <Head title="DIADDEM — Fonctions / Utilisateurs" />

    <b-row class="mb-1">
      <b-col>
        <div class="d-flex align-items-center justify-content-between gap-2">
          <div class="d-flex align-items-center gap-2">
            <i class="ti ti-user-check text-primary fs-6"></i>
            <h6 class="m-0 fw-semibold">Affectation Utilisateur ⇄ Fonction</h6>
            <small class="text-muted">par entité</small>
          </div>
        </div>
      </b-col>
    </b-row>

    <b-card no-body class="shadow-sm">
      <b-card-header class="py-1 px-2 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
          <span class="xsmall text-muted">Entité</span>
          <b-form-select size="sm" v-model="entityId" :options="entityOptions" style="min-width:260px" />
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="xsmall text-muted">Recherche user</span>
          <input class="form-control form-control-sm" style="max-width:260px" v-model="q" @input="searchUsers" placeholder="Nom ou email…" />
          <b-form-select size="sm" :options="userOptions" v-model="selUser" style="min-width:280px" />
        </div>
      </b-card-header>

      <b-card-body class="p-1">
        <div v-if="!entityId" class="xsmall muted">Choisissez une entité.</div>

        <div v-else class="table-responsive">
          <table class="table table-sm align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:28px"></th>
                <th>Fonction</th>
                <th style="width:360px">Utilisateur</th>
                <th style="width:140px"></th>
              </tr>
            </thead>
            <tbody v-if="loading"><tr><td colspan="4" class="xsmall text-muted">Chargement…</td></tr></tbody>
            <tbody v-else-if="!rows.length"><tr><td colspan="4" class="xsmall text-muted">Aucune fonction affectée à cette entité.</td></tr></tbody>
            <tbody v-else>
              <tr v-for="r in rows" :key="'f'+r.id">
                <td class="text-center"><span class="ico color-type-function"><i class="ti ti-user"></i><span class="L">F</span></span></td>
                <td>{{ r.name }}</td>
                <td>
                  <div class="xsmall" v-if="map.get(String(r.id))?.user_id">
                    <div class="fw-semibold">{{ map.get(String(r.id))?.name }}</div>
                    <div class="text-muted">{{ map.get(String(r.id))?.email }}</div>
                  </div>
                  <div class="xsmall muted" v-else>— Aucun (NULL)</div>
                </td>
                <td class="text-end">
                  <div class="d-flex gap-1 justify-content-end">
                    <b-button size="sm" variant="primary" :disabled="!selUser" @click="setUser(r.id, Number(selUser))"><i class="ti ti-user-plus"></i></b-button>
                    <b-button size="sm" variant="outline-danger" @click="clearUser(r.id)"><i class="ti ti-user-x"></i></b-button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </b-card-body>
    </b-card>
  </VerticalLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue';

const props = defineProps({
  entities: { type:Array, default:()=>[] },
  routes:   { type:Object, default:()=>({ search:'', map:'', set:'', clear:'', funcs:{ current:'' } }) },
});

const entityId = ref(null);
const entityOptions = computed(()=>[
  { value:null, text: props.entities?.length? '— Entité —':'— Aucune —', disabled:true },
  ...props.entities.map(e=>({ value:e.id, text:e.name }))
]);

const loading = ref(false);
const rows    = ref([]);          // fonctions (id,name) de l'entité
const map     = ref(new Map());   // function_id -> { user_id,name,email }

const q         = ref('');
const userOptions = ref([]);
const selUser     = ref('');

async function loadFunctionsForEntity(){
  if (!entityId.value){ rows.value=[]; return; }
  loading.value = true;
  try{
    const url = new URL(props.routes.funcs.current, window.location.origin);
    url.searchParams.set('entity_id', entityId.value);
    const r = await fetch(url); const j = await r.json();
    rows.value = Array.isArray(j)? j : (j?.functions||[]);
  } finally { loading.value = false; }
}

async function loadMap(){
  if (!entityId.value){ map.value = new Map(); return; }
  const url = new URL(props.routes.map, window.location.origin);
  url.searchParams.set('entity_id', entityId.value);
  const r = await fetch(url); const j = await r.json();
  const M = new Map();
  for (const it of (j.items||[])){
    M.set(String(it.function_id), { user_id: it.user_id, name: it.name, email: it.email });
  }
  map.value = M;
}

async function searchUsers(){
  const url = new URL(props.routes.search, window.location.origin);
  if (q.value) url.searchParams.set('q', q.value);
  const r = await fetch(url); const j = await r.json();
  userOptions.value = j.options || [];
}

async function setUser(functionId, userId){
  if (!entityId.value) return;
  await fetch(props.routes.set, {
    method:'POST',
    headers:{
      'Content-Type':'application/json',
      'Accept':'application/json',
      'X-Requested-With':'XMLHttpRequest',
      'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]')?.content ?? ''
    },
    body: JSON.stringify({ entity_id: entityId.value, function_id: Number(functionId), user_id: userId })
  });
  await loadMap();
}

async function clearUser(functionId){
  if (!entityId.value) return;
  await fetch(props.routes.clear, {
    method:'POST',
    headers:{
      'Content-Type':'application/json',
      'Accept':'application/json',
      'X-Requested-With':'XMLHttpRequest',
      'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]')?.content ?? ''
    },
    body: JSON.stringify({ entity_id: entityId.value, function_id: Number(functionId) })
  });
  await loadMap();
}

watch(entityId, async ()=>{ await loadFunctionsForEntity(); await loadMap(); });
onMounted(async ()=>{ if (props.entities?.length){ entityId.value = props.entities[0].id; } await searchUsers(); });
</script>

<style scoped>
.small, small, .xsmall{ font-size:.72rem }
.muted{ color:#6b7280 }
.btn-xs{ padding:.1rem .35rem; font-size:.68rem; line-height:1.1; border-radius:.28rem }
.card, .b-card{ border-radius:.5rem }
.ico{ position:relative; display:inline-flex;align-items:center;justify-content:center; width:20px;height:16px;border-radius:.35rem; border:1.5px solid #0ea5e9 }
.ico .ti{ color:#0ea5e9 }
.L{ position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:.58rem; font-weight:900; color:#0ea5e9 }
.color-type-function{ --clr-type:#0ea5e9 }
</style>
