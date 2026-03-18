<template>
  <VerticalLayout>
    <Head title="Modélisation BPMN" />

    <!-- ===== HERO HEADER ===== -->
    <div class="bpmn-hero">
      <div class="hero-bg-grid"></div>
      <div class="hero-content">
        <div class="hero-left">
          <div class="hero-badge">
            <span class="badge-dot"></span>
            BPMN 2.0
          </div>
          <h1 class="hero-title">Modélisation<br><span class="hero-accent">des Processus</span></h1>
          <p class="hero-desc">Concevez vos flux métier avec la norme internationale BPMN 2.0. Chaque processus, chaque activité, chaque décision.</p>
          <div class="hero-stats">
            <div class="hstat">
              <div class="hstat-value">{{ stats.total_processes }}</div>
              <div class="hstat-label">Processus</div>
            </div>
            <div class="hstat-divider"></div>
            <div class="hstat">
              <div class="hstat-value">{{ stats.total_activities }}</div>
              <div class="hstat-label">Activités</div>
            </div>
            <div class="hstat-divider"></div>
            <div class="hstat">
              <div class="hstat-value">{{ stats.with_diagrams }}</div>
              <div class="hstat-label">Diagrammes</div>
            </div>
          </div>
        </div>
        <div class="hero-right">
          <button class="btn-create" @click="$inertia.visit(route('process.core.modeling.bpmn.create'))">
            <i class="ti ti-plus"></i>
            <span>Nouveau processus</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ===== TOOLBAR ===== -->
    <div class="toolbar-row">
      <div class="search-box">
        <i class="ti ti-search"></i>
        <input v-model="search" placeholder="Rechercher un processus..." />
        <button v-if="search" @click="search = ''" class="clear-btn"><i class="ti ti-x"></i></button>
      </div>
      <div class="view-toggle">
        <button :class="{ active: view === 'grid' }" @click="view = 'grid'">
          <i class="ti ti-layout-grid"></i>
        </button>
        <button :class="{ active: view === 'list' }" @click="view = 'list'">
          <i class="ti ti-list"></i>
        </button>
      </div>
    </div>

    <!-- ===== GRID VIEW ===== -->
    <div v-if="view === 'grid'" class="process-grid">
      <div v-for="process in filteredProcesses" :key="process.id" class="process-card">
        <div class="card-header-bar">
          <div class="card-code">{{ process.code }}</div>
          <div class="card-badge" :class="process.diagrams_count > 0 ? 'has-diagram' : 'no-diagram'">
            <i :class="process.diagrams_count > 0 ? 'ti ti-check' : 'ti ti-circle-dashed'"></i>
            {{ process.diagrams_count > 0 ? 'Diagramme' : 'Vierge' }}
          </div>
        </div>
        <div class="card-body">
          <h3 class="card-title">{{ process.name }}</h3>
          <div class="card-meta">
            <span><i class="ti ti-activity"></i> {{ process.activities_count }} activité{{ process.activities_count !== 1 ? 's' : '' }}</span>
            <span><i class="ti ti-calendar"></i> {{ formatDate(process.created_at) }}</span>
          </div>
        </div>
        <div class="card-actions">
          <button class="btn-edit" @click="$inertia.visit(route('process.core.modeling.bpmn.edit', process.id))">
            <i class="ti ti-pencil"></i> Éditer
          </button>
          <button class="btn-del" @click="confirmDelete(process)">
            <i class="ti ti-trash"></i>
          </button>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="filteredProcesses.length === 0" class="empty-state">
        <div class="empty-icon"><i class="ti ti-topology-star-3"></i></div>
        <h3>Aucun processus trouvé</h3>
        <p>{{ search ? 'Modifiez votre recherche.' : 'Créez votre premier processus BPMN.' }}</p>
        <button v-if="!search" class="btn-create-sm" @click="$inertia.visit(route('process.core.modeling.bpmn.create'))">
          <i class="ti ti-plus"></i> Créer un processus
        </button>
      </div>
    </div>

    <!-- ===== LIST VIEW ===== -->
    <div v-else class="list-view">
      <div class="list-header">
        <span class="col-code">Code</span>
        <span class="col-name">Nom du Processus</span>
        <span class="col-acts">Activités</span>
        <span class="col-date">Créé le</span>
        <span class="col-status">Statut</span>
        <span class="col-actions">Actions</span>
      </div>
      <div v-for="process in filteredProcesses" :key="process.id" class="list-row">
        <span class="col-code"><code>{{ process.code }}</code></span>
        <span class="col-name fw-semibold">{{ process.name }}</span>
        <span class="col-acts">
          <span class="acts-pill">{{ process.activities_count }}</span>
        </span>
        <span class="col-date text-muted small">{{ formatDate(process.created_at) }}</span>
        <span class="col-status">
          <span class="status-pill" :class="process.diagrams_count > 0 ? 'status-ok' : 'status-empty'">
            {{ process.diagrams_count > 0 ? 'Diagramme' : 'Vierge' }}
          </span>
        </span>
        <span class="col-actions">
          <button class="btn-act-edit" @click="$inertia.visit(route('process.core.modeling.bpmn.edit', process.id))">
            <i class="ti ti-pencil"></i>
          </button>
          <button class="btn-act-del" @click="confirmDelete(process)">
            <i class="ti ti-trash"></i>
          </button>
        </span>
      </div>
      <div v-if="filteredProcesses.length === 0" class="empty-state">
        <div class="empty-icon"><i class="ti ti-topology-star-3"></i></div>
        <h3>Aucun processus</h3>
        <p>{{ search ? 'Aucun résultat pour cette recherche.' : 'Créez votre premier processus BPMN.' }}</p>
      </div>
    </div>

    <!-- ===== PAGINATION ===== -->
    <div class="pagination-row">
      <span class="page-info">{{ processes.from }}–{{ processes.to }} sur {{ processes.total }}</span>
      <div class="page-btns">
        <button :disabled="!processes.prev_page_url" @click="changePage(processes.current_page - 1)" class="page-btn">
          <i class="ti ti-chevron-left"></i>
        </button>
        <button v-for="p in pageRange" :key="p"
          :class="['page-btn', { active: p === processes.current_page }]"
          @click="changePage(p)">{{ p }}</button>
        <button :disabled="!processes.next_page_url" @click="changePage(processes.current_page + 1)" class="page-btn">
          <i class="ti ti-chevron-right"></i>
        </button>
      </div>
    </div>

  </VerticalLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps({
  processes: Object,
  stats:     Object,
  bpmn_enabled: Boolean,
})

const view = ref('grid')
const search = ref('')

const filteredProcesses = computed(() => {
  if (!search.value) return props.processes.data
  const s = search.value.toLowerCase()
  return props.processes.data.filter(p =>
    p.name.toLowerCase().includes(s) || p.code.toLowerCase().includes(s)
  )
})

const pageRange = computed(() => {
  const total = Math.ceil(props.processes.total / props.processes.per_page)
  const cur   = props.processes.current_page
  const pages = []
  for (let i = Math.max(1, cur - 2); i <= Math.min(total, cur + 2); i++) pages.push(i)
  return pages
})

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function changePage(page) {
  router.get(route('process.core.modeling.bpmn.index'), { page }, { preserveState: true, preserveScroll: true })
}

function confirmDelete(process) {
  if (confirm(`Supprimer définitivement "${process.name}" ?`)) {
    router.delete(route('process.core.modeling.bpmn.destroy', process.id))
  }
}
</script>

<style scoped>
/* ─── HERO ─────────────────────────────────────── */
.bpmn-hero {
  position: relative;
  background: #0f172a;
  border-radius: 20px;
  padding: 2.5rem 2.5rem 2rem;
  margin-bottom: 1.5rem;
  overflow: hidden;
  color: #fff;
}

.hero-bg-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(99,102,241,.12) 1px, transparent 1px),
    linear-gradient(90deg, rgba(99,102,241,.12) 1px, transparent 1px);
  background-size: 40px 40px;
  mask-image: linear-gradient(to bottom, transparent, rgba(0,0,0,.6) 40%, rgba(0,0,0,.6) 70%, transparent);
}

.hero-content {
  position: relative;
  z-index: 1;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 2rem;
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(99,102,241,.2);
  border: 1px solid rgba(99,102,241,.4);
  color: #818cf8;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: .1em;
  text-transform: uppercase;
  padding: 0.3rem 0.8rem;
  border-radius: 50px;
  margin-bottom: 1rem;
}

.badge-dot {
  width: 6px; height: 6px;
  background: #4ade80;
  border-radius: 50%;
  animation: pulse 2s infinite;
}

@keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(.8)} }

.hero-title {
  font-size: 2.4rem;
  font-weight: 800;
  line-height: 1.15;
  margin: 0 0 .75rem;
  letter-spacing: -.02em;
}

.hero-accent { color: #818cf8; }

.hero-desc {
  color: #94a3b8;
  font-size: .95rem;
  max-width: 480px;
  line-height: 1.6;
  margin-bottom: 1.5rem;
}

.hero-stats { display: flex; align-items: center; gap: 1.5rem; }

.hstat-value {
  font-size: 1.6rem;
  font-weight: 800;
  color: #fff;
  line-height: 1;
}

.hstat-label { font-size: .75rem; color: #64748b; margin-top: .2rem; }

.hstat-divider { width: 1px; height: 36px; background: rgba(255,255,255,.1); }

.btn-create {
  display: flex;
  align-items: center;
  gap: .6rem;
  background: #6366f1;
  color: #fff;
  border: none;
  padding: .75rem 1.5rem;
  border-radius: 12px;
  font-weight: 600;
  font-size: .95rem;
  cursor: pointer;
  white-space: nowrap;
  transition: all .2s;
  box-shadow: 0 4px 20px rgba(99,102,241,.4);
}

.btn-create:hover { background: #4f46e5; transform: translateY(-2px); box-shadow: 0 8px 28px rgba(99,102,241,.5); }

/* ─── TOOLBAR ───────────────────────────────────── */
.toolbar-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
  gap: 1rem;
}

.search-box {
  display: flex;
  align-items: center;
  gap: .6rem;
  background: #fff;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  padding: .55rem 1rem;
  flex: 1;
  max-width: 380px;
  transition: border-color .2s;
}

.search-box:focus-within { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1); }

.search-box i { color: #94a3b8; font-size: 1rem; }

.search-box input {
  border: none;
  outline: none;
  width: 100%;
  font-size: .9rem;
  color: #1e293b;
  background: transparent;
}

.clear-btn {
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 0;
  line-height: 1;
}

.view-toggle {
  display: flex;
  background: #f1f5f9;
  border-radius: 8px;
  padding: 3px;
  gap: 2px;
}

.view-toggle button {
  background: none;
  border: none;
  padding: .45rem .65rem;
  border-radius: 6px;
  cursor: pointer;
  color: #64748b;
  transition: all .2s;
  font-size: .95rem;
}

.view-toggle button.active { background: #fff; color: #6366f1; box-shadow: 0 1px 4px rgba(0,0,0,.08); }

/* ─── GRID ───────────────────────────────────────── */
.process-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.25rem;
  margin-bottom: 1.5rem;
}

.process-card {
  background: #fff;
  border-radius: 14px;
  border: 1.5px solid #e2e8f0;
  overflow: hidden;
  transition: all .25s;
  cursor: default;
}

.process-card:hover {
  border-color: #a5b4fc;
  box-shadow: 0 8px 30px rgba(99,102,241,.12);
  transform: translateY(-3px);
}

.card-header-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: .9rem 1.1rem .6rem;
  border-bottom: 1px solid #f1f5f9;
}

.card-code {
  font-family: 'JetBrains Mono', monospace;
  font-size: .8rem;
  color: #6366f1;
  background: #eef2ff;
  padding: .2rem .6rem;
  border-radius: 6px;
  font-weight: 600;
}

.card-badge {
  display: flex;
  align-items: center;
  gap: .3rem;
  font-size: .72rem;
  font-weight: 600;
  padding: .2rem .6rem;
  border-radius: 50px;
}

.card-badge.has-diagram { background: #dcfce7; color: #16a34a; }
.card-badge.no-diagram  { background: #f1f5f9; color: #94a3b8; }

.card-body { padding: .9rem 1.1rem; }

.card-title {
  font-size: 1rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 .6rem;
  line-height: 1.4;
}

.card-meta {
  display: flex;
  gap: 1rem;
  font-size: .78rem;
  color: #64748b;
}

.card-meta span { display: flex; align-items: center; gap: .3rem; }

.card-actions {
  display: flex;
  gap: .5rem;
  padding: .75rem 1.1rem;
  border-top: 1px solid #f1f5f9;
  background: #fafafa;
}

.btn-edit {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: .4rem;
  background: #6366f1;
  color: #fff;
  border: none;
  padding: .5rem;
  border-radius: 8px;
  font-size: .85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all .2s;
}

.btn-edit:hover { background: #4f46e5; }

.btn-del {
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff;
  border: 1.5px solid #fecaca;
  color: #ef4444;
  padding: .5rem .75rem;
  border-radius: 8px;
  cursor: pointer;
  transition: all .2s;
}

.btn-del:hover { background: #fef2f2; }

/* ─── LIST VIEW ─────────────────────────────────── */
.list-view {
  background: #fff;
  border-radius: 14px;
  border: 1.5px solid #e2e8f0;
  overflow: hidden;
  margin-bottom: 1.5rem;
}

.list-header, .list-row {
  display: grid;
  grid-template-columns: 120px 1fr 90px 110px 110px 90px;
  align-items: center;
  padding: .75rem 1.25rem;
  gap: 1rem;
}

.list-header {
  background: #f8fafc;
  border-bottom: 1.5px solid #e2e8f0;
  font-size: .75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .06em;
  color: #64748b;
}

.list-row {
  border-bottom: 1px solid #f1f5f9;
  transition: background .15s;
}

.list-row:last-child { border-bottom: none; }
.list-row:hover { background: #f8fafc; }

.list-row code {
  font-size: .8rem;
  color: #6366f1;
  background: #eef2ff;
  padding: .15rem .5rem;
  border-radius: 5px;
}

.acts-pill {
  display: inline-block;
  background: #e0e7ff;
  color: #3730a3;
  font-size: .78rem;
  font-weight: 700;
  padding: .15rem .6rem;
  border-radius: 20px;
}

.status-pill {
  font-size: .75rem;
  font-weight: 600;
  padding: .2rem .65rem;
  border-radius: 50px;
}

.status-ok    { background: #dcfce7; color: #16a34a; }
.status-empty { background: #f1f5f9; color: #94a3b8; }

.btn-act-edit, .btn-act-del {
  background: none;
  border: 1.5px solid #e2e8f0;
  border-radius: 7px;
  padding: .3rem .55rem;
  cursor: pointer;
  font-size: .9rem;
  transition: all .2s;
}

.btn-act-edit { color: #6366f1; }
.btn-act-edit:hover { background: #eef2ff; border-color: #a5b4fc; }
.btn-act-del  { color: #ef4444; margin-left: .3rem; }
.btn-act-del:hover  { background: #fef2f2; border-color: #fca5a5; }

/* ─── EMPTY STATE ───────────────────────────────── */
.empty-state {
  grid-column: 1 / -1;
  text-align: center;
  padding: 4rem 2rem;
  color: #64748b;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: .3;
}

.empty-state h3 { font-size: 1.1rem; color: #0f172a; margin-bottom: .5rem; }
.empty-state p  { font-size: .9rem; margin-bottom: 1.25rem; }

.btn-create-sm {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  background: #6366f1;
  color: #fff;
  border: none;
  padding: .6rem 1.25rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all .2s;
}

.btn-create-sm:hover { background: #4f46e5; }

/* ─── PAGINATION ────────────────────────────────── */
.pagination-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: .5rem 0;
}

.page-info { font-size: .85rem; color: #64748b; }

.page-btns { display: flex; gap: .4rem; }

.page-btn {
  min-width: 36px; height: 36px;
  display: flex; align-items: center; justify-content: center;
  background: #fff;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  color: #475569;
  font-size: .85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all .2s;
}

.page-btn:hover:not(:disabled) { border-color: #a5b4fc; color: #6366f1; }
.page-btn.active { background: #6366f1; border-color: #6366f1; color: #fff; }
.page-btn:disabled { opacity: .35; cursor: not-allowed; }

/* ─── RESPONSIVE ────────────────────────────────── */
@media (max-width: 768px) {
  .hero-content { flex-direction: column; align-items: flex-start; }
  .hero-title   { font-size: 1.7rem; }
  .hero-stats   { gap: 1rem; }
  .list-header, .list-row { grid-template-columns: 100px 1fr 60px 80px; }
  .col-date, .col-status { display: none; }
}
</style>