<template>
  <VerticalLayout>
    <Head title="Nouveau Processus BPMN" />

    <div class="create-page">

      <!-- ===== BREADCRUMB ===== -->
      <div class="crumb-row">
        <button class="crumb-btn" @click="$inertia.visit(route('process.core.modeling.bpmn.index'))">
          <i class="ti ti-topology-star-3"></i> Modélisation BPMN
        </button>
        <i class="ti ti-chevron-right crumb-sep"></i>
        <span class="crumb-current">Nouveau processus</span>
      </div>

      <!-- ===== SPLIT LAYOUT ===== -->
      <div class="split-layout">

        <!-- LEFT: FORM ─────────────────── -->
        <div class="form-panel">
          <div class="panel-head">
            <div class="panel-icon"><i class="ti ti-plus"></i></div>
            <div>
              <h2 class="panel-title">Créer un processus</h2>
              <p class="panel-subtitle">Informations de base pour votre nouveau processus BPMN</p>
            </div>
          </div>

          <form @submit.prevent="submit" class="form-body">

            <div class="field-group">
              <label class="field-label">
                Code <span class="req">*</span>
                <span class="field-hint">Identifiant unique</span>
              </label>
              <div class="input-wrapper" :class="{ error: errors.code }">
                <span class="input-prefix"><i class="ti ti-hash"></i></span>
                <input
                  v-model="form.code"
                  type="text"
                  placeholder="Ex : PROC-001"
                  class="field-input"
                  autofocus
                  required
                />
              </div>
              <span v-if="errors.code" class="field-error">{{ errors.code[0] }}</span>
            </div>

            <div class="field-group">
              <label class="field-label">
                Nom du processus <span class="req">*</span>
                <span class="field-hint">Nom descriptif</span>
              </label>
              <div class="input-wrapper" :class="{ error: errors.name }">
                <span class="input-prefix"><i class="ti ti-file-text"></i></span>
                <input
                  v-model="form.name"
                  type="text"
                  placeholder="Ex : Traitement des commandes clients"
                  class="field-input"
                  required
                />
              </div>
              <span v-if="errors.name" class="field-error">{{ errors.name[0] }}</span>
            </div>

            <!-- Info box -->
            <div class="info-box">
              <div class="info-box-icon"><i class="ti ti-bulb"></i></div>
              <div class="info-box-text">
                <strong>Après création</strong>, vous serez redirigé vers l'éditeur BPMN complet avec
                palette d'outils, canvas interactif et panneau de propriétés.
              </div>
            </div>

            <div class="form-footer">
              <button type="button" class="btn-cancel"
                @click="$inertia.visit(route('process.core.modeling.bpmn.index'))"
                :disabled="loading">
                <i class="ti ti-arrow-left"></i> Retour
              </button>
              <button type="submit" class="btn-submit" :disabled="loading || !form.code || !form.name">
                <span v-if="loading" class="loading-dots">
                  <span></span><span></span><span></span>
                </span>
                <template v-else>
                  <i class="ti ti-arrow-right"></i>
                  Créer et ouvrir l'éditeur
                </template>
              </button>
            </div>

          </form>
        </div>

        <!-- RIGHT: PREVIEW ─────────────── -->
        <div class="preview-panel">
          <div class="preview-head">
            <span class="preview-label">Aperçu</span>
          </div>
          <div class="preview-card" :class="{ filled: form.name }">
            <div class="preview-card-top">
              <div class="preview-code">{{ form.code || 'PROC-XXX' }}</div>
              <div class="preview-status">Nouveau</div>
            </div>
            <div class="preview-name">{{ form.name || 'Nom du processus' }}</div>
            <div class="preview-meta">
              <span><i class="ti ti-activity"></i> 0 activité</span>
              <span><i class="ti ti-calendar"></i> Aujourd'hui</span>
            </div>
            <div class="preview-diagram-area">
              <!-- mini BPMN preview SVG -->
              <svg viewBox="0 0 300 80" xmlns="http://www.w3.org/2000/svg">
                <!-- start event -->
                <circle cx="30" cy="40" r="14" fill="none" stroke="#6366f1" stroke-width="2"/>
                <!-- arrow 1 -->
                <line x1="44" y1="40" x2="80" y2="40" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr)"/>
                <!-- task 1 -->
                <rect x="80" y="26" width="60" height="28" rx="5" fill="none" stroke="#6366f1" stroke-width="2"/>
                <text x="110" y="44" font-size="9" text-anchor="middle" fill="#1e293b">Tâche A</text>
                <!-- arrow 2 -->
                <line x1="140" y1="40" x2="175" y2="40" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr)"/>
                <!-- gateway -->
                <polygon points="190,28 205,40 190,52 175,40" fill="none" stroke="#f59e0b" stroke-width="2"/>
                <!-- arrow 3 -->
                <line x1="205" y1="40" x2="240" y2="40" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr)"/>
                <!-- end event -->
                <circle cx="256" cy="40" r="14" fill="none" stroke="#ef4444" stroke-width="3"/>
                <defs>
                  <marker id="arr" markerWidth="6" markerHeight="6" refX="5" refY="3" orient="auto">
                    <path d="M0,0 L0,6 L6,3 z" fill="#94a3b8"/>
                  </marker>
                </defs>
              </svg>
            </div>
          </div>

          <!-- Steps info -->
          <div class="steps-list">
            <div class="step" :class="{ active: form.code }">
              <div class="step-num">1</div>
              <div class="step-info">
                <strong>Code unique</strong>
                <span>Identifiant du processus</span>
              </div>
              <i class="ti ti-check step-check" v-if="form.code"></i>
            </div>
            <div class="step" :class="{ active: form.name }">
              <div class="step-num">2</div>
              <div class="step-info">
                <strong>Nom descriptif</strong>
                <span>Libellé du processus</span>
              </div>
              <i class="ti ti-check step-check" v-if="form.name"></i>
            </div>
            <div class="step">
              <div class="step-num">3</div>
              <div class="step-info">
                <strong>Modélisation BPMN</strong>
                <span>Éditeur graphique complet</span>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </VerticalLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const form    = ref({ code: '', name: '' })
const loading = ref(false)
const errors  = ref({})

function submit() {
  loading.value = true
  errors.value  = {}
  router.post(route('process.core.modeling.bpmn.store'), form.value, {
    onSuccess: () => { /* redirect handled by controller */ },
    onError:   (err) => { errors.value = err; loading.value = false },
    onFinish:  () => { loading.value = false },
  })
}
</script>

<style scoped>
.create-page { padding-bottom: 2rem; }

/* ─── BREADCRUMB ─────────────────────────────────── */
.crumb-row {
  display: flex;
  align-items: center;
  gap: .5rem;
  margin-bottom: 1.5rem;
  font-size: .85rem;
}

.crumb-btn {
  background: none;
  border: none;
  color: #6366f1;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: .4rem;
  font-size: .85rem;
  font-weight: 500;
  padding: 0;
  transition: color .2s;
}

.crumb-btn:hover { color: #4f46e5; text-decoration: underline; }

.crumb-sep    { color: #cbd5e1; font-size: .75rem; }
.crumb-current { color: #64748b; }

/* ─── SPLIT LAYOUT ───────────────────────────────── */
.split-layout {
  display: grid;
  grid-template-columns: 1fr 420px;
  gap: 1.5rem;
  align-items: start;
}

/* ─── FORM PANEL ─────────────────────────────────── */
.form-panel {
  background: #fff;
  border-radius: 18px;
  border: 1.5px solid #e2e8f0;
  overflow: hidden;
  box-shadow: 0 4px 24px rgba(0,0,0,.05);
}

.panel-head {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.75rem 2rem;
  background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
  color: #fff;
}

.panel-icon {
  width: 46px; height: 46px;
  background: rgba(99,102,241,.3);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  color: #a5b4fc;
  flex-shrink: 0;
}

.panel-title    { font-size: 1.2rem; font-weight: 700; margin: 0 0 .2rem; }
.panel-subtitle { font-size: .85rem; color: #94a3b8; margin: 0; }

.form-body { padding: 2rem; }

/* ─── FIELDS ─────────────────────────────────────── */
.field-group  { margin-bottom: 1.5rem; }

.field-label {
  display: flex;
  align-items: baseline;
  gap: .4rem;
  font-size: .875rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: .5rem;
}

.req        { color: #ef4444; }
.field-hint { font-size: .75rem; color: #9ca3af; font-weight: 400; margin-left: auto; }

.input-wrapper {
  display: flex;
  align-items: center;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  overflow: hidden;
  transition: all .2s;
  background: #fff;
}

.input-wrapper:focus-within { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1); }
.input-wrapper.error { border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,.1); }

.input-prefix {
  padding: 0 .85rem;
  color: #94a3b8;
  font-size: 1rem;
  background: #f8fafc;
  border-right: 1.5px solid #e2e8f0;
  height: 46px;
  display: flex;
  align-items: center;
}

.field-input {
  flex: 1;
  border: none;
  outline: none;
  padding: .75rem 1rem;
  font-size: .95rem;
  color: #0f172a;
  background: transparent;
  height: 46px;
}

.field-input::placeholder { color: #cbd5e1; }

.field-error { display: block; font-size: .8rem; color: #ef4444; margin-top: .4rem; }

/* ─── INFO BOX ───────────────────────────────────── */
.info-box {
  display: flex;
  gap: .9rem;
  background: #f0f9ff;
  border: 1.5px solid #bae6fd;
  border-radius: 12px;
  padding: 1rem 1.25rem;
  margin-bottom: 1.75rem;
}

.info-box-icon { font-size: 1.25rem; color: #0ea5e9; flex-shrink: 0; }
.info-box-text { font-size: .875rem; color: #0c4a6e; line-height: 1.6; }
.info-box-text strong { display: block; margin-bottom: .1rem; }

/* ─── FORM FOOTER ────────────────────────────────── */
.form-footer {
  display: flex;
  justify-content: flex-end;
  gap: .75rem;
  padding-top: 1.5rem;
  border-top: 1.5px solid #f1f5f9;
}

.btn-cancel {
  display: flex;
  align-items: center;
  gap: .5rem;
  background: none;
  border: 1.5px solid #e2e8f0;
  color: #475569;
  padding: .65rem 1.25rem;
  border-radius: 10px;
  font-size: .9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all .2s;
}

.btn-cancel:hover { background: #f1f5f9; border-color: #cbd5e1; }

.btn-submit {
  display: flex;
  align-items: center;
  gap: .5rem;
  background: #6366f1;
  border: none;
  color: #fff;
  padding: .65rem 1.5rem;
  border-radius: 10px;
  font-size: .9rem;
  font-weight: 700;
  cursor: pointer;
  transition: all .2s;
  min-width: 200px;
  justify-content: center;
}

.btn-submit:hover:not(:disabled) { background: #4f46e5; transform: translateY(-1px); box-shadow: 0 4px 16px rgba(99,102,241,.35); }
.btn-submit:disabled { opacity: .5; cursor: not-allowed; transform: none; }

/* Loading dots */
.loading-dots { display: flex; gap: 4px; align-items: center; }
.loading-dots span {
  width: 6px; height: 6px;
  background: rgba(255,255,255,.8);
  border-radius: 50%;
  animation: ld .8s infinite;
}
.loading-dots span:nth-child(2) { animation-delay: .15s; }
.loading-dots span:nth-child(3) { animation-delay: .3s; }
@keyframes ld { 0%,80%,100%{transform:scale(.6);opacity:.4} 40%{transform:scale(1);opacity:1} }

/* ─── PREVIEW PANEL ──────────────────────────────── */
.preview-panel {
  position: sticky;
  top: 1rem;
}

.preview-head { margin-bottom: .75rem; }
.preview-label {
  font-size: .72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .1em;
  color: #64748b;
}

.preview-card {
  background: #fff;
  border: 1.5px solid #e2e8f0;
  border-radius: 16px;
  padding: 1.25rem;
  transition: all .3s;
  margin-bottom: 1.25rem;
}

.preview-card.filled { border-color: #a5b4fc; box-shadow: 0 4px 20px rgba(99,102,241,.1); }

.preview-card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: .75rem;
}

.preview-code {
  font-family: 'JetBrains Mono', monospace;
  font-size: .8rem;
  color: #6366f1;
  background: #eef2ff;
  padding: .2rem .6rem;
  border-radius: 6px;
  font-weight: 600;
}

.preview-status {
  font-size: .72rem;
  font-weight: 600;
  background: #f0fdf4;
  color: #16a34a;
  padding: .2rem .6rem;
  border-radius: 50px;
}

.preview-name {
  font-size: 1rem;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: .5rem;
  min-height: 1.5rem;
  transition: all .2s;
}

.preview-meta {
  display: flex;
  gap: 1rem;
  font-size: .78rem;
  color: #64748b;
  margin-bottom: 1rem;
}

.preview-meta span { display: flex; align-items: center; gap: .3rem; }

.preview-diagram-area {
  background: #f8fafc;
  border-radius: 10px;
  padding: .75rem;
  border: 1px dashed #e2e8f0;
}

.preview-diagram-area svg { width: 100%; height: auto; }

/* ─── STEPS ──────────────────────────────────────── */
.steps-list { display: flex; flex-direction: column; gap: .6rem; }

.step {
  display: flex;
  align-items: center;
  gap: .9rem;
  background: #fff;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  padding: .75rem 1rem;
  transition: all .25s;
}

.step.active { border-color: #a5b4fc; background: #f5f3ff; }

.step-num {
  width: 28px; height: 28px;
  background: #f1f5f9;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .8rem;
  font-weight: 700;
  color: #64748b;
  flex-shrink: 0;
  transition: all .25s;
}

.step.active .step-num { background: #6366f1; color: #fff; }

.step-info strong { display: block; font-size: .85rem; color: #0f172a; }
.step-info span   { font-size: .75rem; color: #94a3b8; }

.step-check {
  margin-left: auto;
  color: #16a34a;
  font-size: 1rem;
  flex-shrink: 0;
}

/* ─── RESPONSIVE ─────────────────────────────────── */
@media (max-width: 900px) {
  .split-layout { grid-template-columns: 1fr; }
  .preview-panel { position: static; }
}
</style>