<template>
  <div class="container-fluid py-2">

    <!-- HEADER -->
    <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
      <h5 class="fw-bold mb-0" style="color:#1a1a2e">
        <i class="ti ti-shield-check me-1" style="color:#1565C0"></i>
        Univers d'Audit &amp; Risques
      </h5>
      <div class="d-flex gap-2 align-items-center flex-wrap">
        <span v-if="saveStatus==='saving'" class="badge bg-warning text-dark">
          <span class="spinner-border spinner-border-sm me-1"></span>Sauvegarde…
        </span>
        <span v-else-if="saveStatus==='saved'" class="badge bg-success">
          <i class="ti ti-check me-1"></i>Sauvegardé
        </span>
        <span v-else-if="saveStatus==='error'" class="badge bg-danger">
          <i class="ti ti-alert-circle me-1"></i>Erreur
        </span>
        <button class="btn btn-sm btn-primary"
                @click="saveUniverse"
                :disabled="!selectedEntity || !selectedYear || saving || !risks.length">
          <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
          <i v-else class="ti ti-device-floppy me-1"></i>
          Sauvegarder
        </button>
      </div>
    </div>

    <!-- FILTRES -->
    <div class="card mb-2 border-0 shadow-sm">
      <div class="card-header py-2" style="background:#1565C0">
        <span class="text-white fw-bold small">
          <i class="ti ti-filter me-1"></i>Sélection
        </span>
      </div>
      <div class="card-body py-2">
        <div class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="form-label fw-bold small mb-1">Entité *</label>
            <select v-model.number="selectedEntity" class="form-select form-select-sm" @change="onFilterChange">
              <option :value="null">-- Sélectionner --</option>
              <option v-for="e in entities" :key="e.id" :value="e.id">
                {{ e.code_base }} – {{ e.name }}
              </option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label fw-bold small mb-1">Année *</label>
            <select v-model.number="selectedYear" class="form-select form-select-sm" @change="onFilterChange">
              <option :value="null">-- Année --</option>
              <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-bold small mb-1">Fait par</label>
            <input v-model="faitPar" class="form-control form-control-sm" placeholder="Auditeur…" @input="markDirty"/>
          </div>
          <div class="col-md-2">
            <label class="form-label fw-bold small mb-1">Date analyse</label>
            <input v-model="dateAnalyse" type="date" class="form-control form-control-sm" @change="markDirty"/>
          </div>
          <div class="col-md-2">
            <small class="text-muted">{{ statusMessage }}</small>
          </div>
        </div>
      </div>
    </div>

    <!-- TABLEAU -->
    <div class="card border-0 shadow-sm">
      <div class="card-header py-2 d-flex justify-content-between align-items-center" style="background:#1565C0">
        <h6 class="fw-bold mb-0 text-white small">
          <i class="ti ti-table me-1"></i>
          UNIVERS D'AUDIT — {{ risks.length }} risque(s)
          <span v-if="evaluatedCount" class="badge bg-success ms-2">{{ evaluatedCount }} évalué(s)</span>
        </h6>
        <button class="btn btn-sm btn-success"
                @click="openCreateModal"
                :disabled="!selectedEntity || !selectedYear || loading">
          <i class="ti ti-plus me-1"></i>Créer un risque
        </button>
      </div>

      <div class="table-wrapper">
        <table class="table table-bordered table-sm mb-0 audit-table">
          <thead class="thead-fixed">
            <tr>
              <th class="col-entity">ENTITÉ</th>
              <th class="col-process">PROCESSUS</th>
              <th class="col-activity">ACTIVITÉ</th>
              <th class="col-risk">CODE / LIBELLÉ</th>
              <th class="th-num">IMP<br><small>BRUT</small></th>
              <th class="th-num">FREQ<br><small>BRUT</small></th>
              <th class="th-num">GLOB<br><small>BRUT</small></th>
              <th class="th-ctrl">PROCÉDURE CONTRÔLE</th>
              <th class="th-num">IMP<br><small>NET</small></th>
              <th class="th-num">FREQ<br><small>NET</small></th>
              <th class="th-num">GLOB<br><small>NET</small></th>
              <th class="th-nat">NATURE</th>
              <th class="th-num">QUALIF<br><small>NETTE</small></th>
              <th class="th-eval">ÉVAL</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="risk in risks" :key="risk.id"
                class="risk-row"
                :class="{'row-evaluated': risk.is_evaluated}">

              <!-- Colonnes fixes -->
              <td class="col-entity small fw-bold">{{ entityCode(risk.entity_id) }}</td>
              <td class="col-process small">{{ risk.process_code || processCode(risk.process_id) }}</td>
              <td class="col-activity small">{{ risk.activity_code || activityCode(risk.activity_id) }}</td>
              <td class="col-risk">
                <div class="fw-bold" style="font-size:.69rem;color:#1565C0">{{ risk.code }}</div>
                <div style="font-size:.7rem;color:#1a1a2e;line-height:1.3">{{ truncate(risk.label, 50) }}</div>
              </td>

              <!-- Impact brut -->
              <td class="td-num">
                <span v-if="risk.impact_level" class="lv-pill" :style="{background: hexOf(risk.impact_color)}">
                  {{ risk.impact_level }}
                </span>
                <span v-else class="dash">—</span>
              </td>

              <!-- Fréquence brute -->
              <td class="td-num">
                <span v-if="risk.frequency_level" class="lv-pill" :style="{background: hexOf(risk.frequency_color)}">
                  {{ risk.frequency_level }}
                </span>
                <span v-else class="dash">—</span>
              </td>

              <!-- Global brut -->
              <td class="td-num">
                <span v-if="risk.criticality" class="lv-pill" :style="{background: critColor(risk.criticality)}">
                  {{ risk.criticality }}
                </span>
                <span v-else class="dash">—</span>
              </td>

              <!-- Procédure contrôle -->
              <td class="td-ctrl small text-muted">{{ truncate(risk.control_procedure, 35) }}</td>

              <!-- Impact net (éditable) -->
              <td class="td-num">
                <select class="net-sel"
                        v-model.number="risk.impact_net"
                        @change="onNetChange(risk)"
                        :style="risk.impact_net ? {background: critColor(risk.impact_net), color:'#fff', borderColor: critColor(risk.impact_net)} : {}">
                  <option :value="null">—</option>
                  <option v-for="lv in (impacts as any[])" :key="(lv as any).id" :value="(lv as any).level">
                    {{ (lv as any).level }}
                  </option>
                </select>
              </td>

              <!-- Fréquence nette (éditable) -->
              <td class="td-num">
                <select class="net-sel"
                        v-model.number="risk.frequency_net"
                        @change="onNetChange(risk)"
                        :style="risk.frequency_net ? {background: critColor(risk.frequency_net), color:'#fff', borderColor: critColor(risk.frequency_net)} : {}">
                  <option :value="null">—</option>
                  <option v-for="lv in (frequencies as any[])" :key="(lv as any).id" :value="(lv as any).level">
                    {{ (lv as any).level }}
                  </option>
                </select>
              </td>

              <!-- Global net (calculé) -->
              <td class="td-num">
                <span v-if="globNet(risk)" class="lv-pill" :style="{background: critColor(globNet(risk))}">
                  {{ globNet(risk) }}
                </span>
                <span v-else class="dash">—</span>
              </td>

              <!-- Nature -->
              <td class="td-nat small text-center">{{ risk.control_nature_code || '—' }}</td>

              <!-- Qualification nette -->
              <td class="td-num">
                <span v-if="qualifNet(risk)" class="lv-pill" :style="{background: qualifColor(qualifNet(risk))}">
                  {{ qualifNet(risk) }}
                </span>
                <span v-else class="dash">—</span>
              </td>

              <!-- Évalué -->
              <td class="td-eval">
                <input type="checkbox" class="eval-chk"
                       v-model="risk.is_evaluated"
                       @change="markDirty"/>
              </td>
            </tr>

            <!-- Vide -->
            <tr v-if="!risks.length && !loading">
              <td colspan="14" class="text-center text-muted p-4">
                <template v-if="!selectedEntity || !selectedYear">
                  <i class="ti ti-selector" style="font-size:2rem;opacity:.25;display:block;margin-bottom:.5rem"></i>
                  Sélectionnez une entité et une année
                </template>
                <template v-else>
                  <i class="ti ti-shield-off" style="font-size:2rem;opacity:.25;display:block;margin-bottom:.5rem"></i>
                  Aucun risque pour cette entité / année.
                  <button class="btn btn-sm btn-outline-success ms-2" @click="openCreateModal">
                    <i class="ti ti-plus me-1"></i>Créer le premier
                  </button>
                </template>
              </td>
            </tr>
            <tr v-if="loading">
              <td colspan="14" class="text-center p-4">
                <span class="spinner-border text-primary"></span>
                <div class="mt-2 small text-muted">Chargement des risques…</div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- SYNTHÈSE -->
    <div v-if="risks.length" class="card border-0 shadow-sm mt-2">
      <div class="card-body py-2">
        <label class="fw-bold small mb-1" style="color:#1565C0">
          <i class="ti ti-notes me-1"></i>Synthèse
        </label>
        <textarea v-model="synthese" class="form-control form-control-sm" rows="2"
                  placeholder="Synthèse générale de l'univers d'audit…"
                  @input="markDirty"></textarea>
      </div>
    </div>

    <!-- MODAL CRÉER RISQUE -->
    <Teleport to="body">
      <transition name="mfade">
        <div v-if="showModal" class="modal-ov" @click.self="closeModal">
          <div class="modal-box">
            <div class="modal-hd">
              <span class="fw-bold small">
                <i class="ti ti-plus me-1 text-success"></i>Créer un risque
              </span>
              <button class="modal-cls" @click="closeModal"><i class="ti ti-x"></i></button>
            </div>
            <div class="modal-inner">
              <div class="row g-2">
                <div class="col-6">
                  <label class="form-label small fw-bold mb-1">Entité</label>
                  <input class="form-control form-control-sm" :value="entityName(selectedEntity)" readonly/>
                </div>
                <div class="col-6">
                  <label class="form-label small fw-bold mb-1">Année</label>
                  <input class="form-control form-control-sm" :value="selectedYear" readonly/>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold mb-1">Libellé *</label>
                  <input v-model="form.label" class="form-control form-control-sm" placeholder="Libellé du risque…"/>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold mb-1">Description</label>
                  <textarea v-model="form.description" class="form-control form-control-sm" rows="2"></textarea>
                </div>
                <div class="col-6">
                  <label class="form-label small fw-bold mb-1">Type risque</label>
                  <select v-model.number="form.risk_type_id" class="form-select form-select-sm">
                    <option :value="null">—</option>
                    <option v-for="t in (riskTypes as any[])" :key="(t as any).id" :value="(t as any).id">
                      {{ (t as any).code }} – {{ (t as any).label }}
                    </option>
                  </select>
                </div>
                <div class="col-6">
                  <label class="form-label small fw-bold mb-1">Processus</label>
                  <select v-model.number="form.process_id" class="form-select form-select-sm">
                    <option :value="null">—</option>
                    <option v-for="p in (processes as any[])" :key="(p as any).id" :value="(p as any).id">
                      {{ (p as any).code }} – {{ (p as any).name }}
                    </option>
                  </select>
                </div>
                <div class="col-6">
                  <label class="form-label small fw-bold mb-1">Activité</label>
                  <select v-model.number="form.activity_id" class="form-select form-select-sm">
                    <option :value="null">—</option>
                    <option v-for="a in (activities as any[])" :key="(a as any).id" :value="(a as any).id">
                      {{ (a as any).code }} – {{ (a as any).name }}
                    </option>
                  </select>
                </div>
                <div class="col-6">
                  <label class="form-label small fw-bold mb-1">Fréquence brute</label>
                  <select v-model.number="form.frequency_level_id" class="form-select form-select-sm">
                    <option :value="null">—</option>
                    <option v-for="f in (frequencies as any[])" :key="(f as any).id" :value="(f as any).id">
                      {{ (f as any).level }} – {{ (f as any).label }}
                    </option>
                  </select>
                </div>
                <div class="col-6">
                  <label class="form-label small fw-bold mb-1">Impact brut</label>
                  <select v-model.number="form.impact_level_id" class="form-select form-select-sm">
                    <option :value="null">—</option>
                    <option v-for="i in (impacts as any[])" :key="(i as any).id" :value="(i as any).id">
                      {{ (i as any).level }} – {{ (i as any).label }}
                    </option>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold mb-1">Procédure de contrôle</label>
                  <textarea v-model="form.control_procedure" class="form-control form-control-sm" rows="2"></textarea>
                </div>
              </div>
            </div>
            <div class="modal-ft">
              <button class="btn btn-sm btn-secondary" @click="closeModal">Annuler</button>
              <button class="btn btn-sm btn-success" @click="createRisk"
                      :disabled="!form.label || loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="ti ti-device-floppy me-1"></i>Créer
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- TOAST -->
    <Teleport to="body">
      <transition name="toast-up">
        <div v-if="toast.show" class="toast-notif" :class="`toast-${toast.type}`">
          <i :class="toast.type==='success'?'ti ti-circle-check':'ti ti-alert-circle'"></i>
          {{ toast.msg }}
        </div>
      </transition>
    </Teleport>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onBeforeUnmount } from 'vue'

const props = defineProps({
  entities:    { type: Array, default: () => [] },
  processes:   { type: Array, default: () => [] },
  activities:  { type: Array, default: () => [] },
  riskTypes:   { type: Array, default: () => [] },
  frequencies: { type: Array, default: () => [] },
  impacts:     { type: Array, default: () => [] },
  matrix:      { type: Array, default: () => [] },
  years:       { type: Array, default: () => [] },
  urls:        { type: Object, default: () => ({}) },
})

// ── Palette Bootstrap → hex ───────────────────────────────────────────────
const PALETTE: Record<string,string> = {
  danger:'#dc3545', warning:'#ffc107', info:'#0dcaf0',
  success:'#28a745', secondary:'#6c757d', primary:'#0d6efd',
}
const hexOf = (c: string) => PALETTE[c] ?? c ?? PALETTE.secondary

const critColor = (n: number|null) => {
  if (!n) return PALETTE.secondary
  if (n <= 2)  return '#22c55e'
  if (n <= 4)  return '#a3e635'
  if (n <= 6)  return '#facc15'
  if (n <= 9)  return '#f97316'
  return '#ef4444'
}
const qualifColor = (q: string) => {
  const v = (q || '').toUpperCase()
  if (v === 'ACCEPTABLE')   return PALETTE.success
  if (v === 'EFFICACE')     return PALETTE.info
  if (v === 'A RENFORCER')  return PALETTE.warning
  if (v === 'INACCEPTABLE') return PALETTE.danger
  return PALETTE.secondary
}

// ── State ─────────────────────────────────────────────────────────────────
const selectedEntity = ref<number|null>(null)
const selectedYear   = ref<number|null>(null)
const risks          = ref<any[]>([])
const loading        = ref(false)
const saving         = ref(false)
const saveStatus     = ref<'idle'|'saving'|'saved'|'error'>('idle')
const isDirty        = ref(false)
const synthese       = ref('')
const faitPar        = ref('')
const dateAnalyse    = ref('')
const showModal      = ref(false)
const universeId     = ref<number|null>(null)

const form = ref({
  label:'', description:'', risk_type_id: null as number|null,
  frequency_level_id: null as number|null, impact_level_id: null as number|null,
  process_id: null as number|null, activity_id: null as number|null,
  control_procedure:'',
})

const toast = ref({ show:false, type:'success', msg:'' })
let toastTimer: ReturnType<typeof setTimeout>|null = null
let autoSaveTimer: ReturnType<typeof setTimeout>|null = null

onBeforeUnmount(() => {
  if (autoSaveTimer) clearTimeout(autoSaveTimer)
  if (toastTimer)    clearTimeout(toastTimer)
})

// ── Computed ──────────────────────────────────────────────────────────────
const statusMessage  = computed(() => {
  if (!selectedEntity.value || !selectedYear.value) return '⏳ Sélectionnez une entité et une année'
  if (loading.value) return '⏳ Chargement…'
  return `✅ ${risks.value.length} risque(s)`
})
const evaluatedCount = computed(() => risks.value.filter(r => r.is_evaluated).length)

// ── Calculs côté client ────────────────────────────────────────────────────
const globNet = (risk: any): number|null => {
  if (!risk.frequency_net || !risk.impact_net) return null
  const n = Math.round(risk.frequency_net * risk.impact_net * 10) / 10
  return n > 0 ? n : null
}
const qualifNet = (risk: any): string|null => {
  const gn = globNet(risk)
  if (!gn) return null
  const entry = (props.matrix as any[]).find(m => Math.abs(m.frequency_level * m.impact_level - gn) < 0.1)
  return entry?.qualification ?? null
}

// ── Helpers affichage ─────────────────────────────────────────────────────
const entityCode   = (id: number) => (props.entities  as any[]).find(e => e.id === id)?.code_base ?? '—'
const entityName   = (id: number|null) => (props.entities as any[]).find(e => e.id === id)?.name ?? '—'
const processCode  = (id: number) => (props.processes  as any[]).find(p => p.id === id)?.code ?? '—'
const activityCode = (id: number) => (props.activities as any[]).find(a => a.id === id)?.code ?? '—'
const truncate     = (t: string, n: number) => !t ? '—' : t.length > n ? t.slice(0, n) + '…' : t

// ── Chargement ─────────────────────────────────────────────────────────────
const onFilterChange = async () => {
  risks.value   = []
  universeId.value = null
  if (selectedEntity.value && selectedYear.value) await loadRisks()
}

const loadRisks = async () => {
  try {
    loading.value = true

    // URL depuis les props Inertia (générée par route() Laravel)
    const url = (props.urls as any).loadRisks
    if (!url) {
      console.error('[universe] URL loadRisks manquante dans props.urls:', props.urls)
      throw new Error('URL loadRisks non configurée — vérifiez le contrôleur')
    }

    console.log('[universe] loadRisks →', url, { entity_id: selectedEntity.value, year: selectedYear.value })

    const res = await apiCall(url, 'POST', {
      entity_id: selectedEntity.value,
      year:      selectedYear.value,
    })

    console.log('[universe] HTTP status:', res.status, res.ok)

    // Lire le corps même en cas d'erreur pour logger le message Laravel
    const raw = await res.text()
    console.log('[universe] raw response:', raw.slice(0, 500))

    const data = JSON.parse(raw)
    if (!res.ok || !data.success) {
      throw new Error(data.error ?? data.message ?? `HTTP ${res.status}`)
    }

    risks.value       = data.risks ?? []
    universeId.value  = data.universe_id ?? null
    synthese.value    = data.synthese    ?? ''
    faitPar.value     = data.fait_par    ?? ''
    dateAnalyse.value = data.date_analyse ?? ''
    isDirty.value     = false

    console.log('[universe] Loaded', risks.value.length, 'risks')

  } catch (e: any) {
    console.error('loadRisks error:', e)
    showToast('error', e.message ?? 'Erreur chargement')
  } finally {
    loading.value = false
  }
}

// ── Auto-save ──────────────────────────────────────────────────────────────
const markDirty = () => {
  isDirty.value = true
  if (autoSaveTimer) clearTimeout(autoSaveTimer)
  autoSaveTimer = setTimeout(saveUniverse, 5000)
}
const onNetChange = (_risk: any) => markDirty()

// ── Sauvegarde ─────────────────────────────────────────────────────────────
const saveUniverse = async () => {
  if (!selectedEntity.value || !selectedYear.value || !risks.value.length) return
  try {
    saving.value     = true
    saveStatus.value = 'saving'

    const payload = {
      entity_id:    selectedEntity.value,
      year:         selectedYear.value,
      synthese:     synthese.value,
      fait_par:     faitPar.value,
      date_analyse: dateAnalyse.value,
      risques: JSON.stringify(risks.value.map(r => ({
        risk_id:             r.id,
        risk_code:           r.code,
        impact_net:          r.impact_net,
        frequency_net:       r.frequency_net,
        criticality_net:     globNet(r),
        qualification_net:   qualifNet(r),
        control_procedure:   r.control_procedure,
        control_nature_code: r.control_nature_code,
        is_evaluated:        r.is_evaluated,
      }))),
    }

    const saveUrl = (props.urls as any).save
    const res  = await apiCall(saveUrl, 'POST', payload)
    const data = await res.json()
    if (!res.ok || !data.success) throw new Error(data.error ?? 'Erreur sauvegarde')

    universeId.value = data.record?.id ?? universeId.value
    isDirty.value    = false
    saveStatus.value = 'saved'
    showToast('success', data.message ?? 'Univers sauvegardé')
    setTimeout(() => { if (saveStatus.value === 'saved') saveStatus.value = 'idle' }, 3000)

  } catch (e: any) {
    saveStatus.value = 'error'
    showToast('error', e.message ?? 'Erreur sauvegarde')
  } finally {
    saving.value = false
  }
}

// ── Créer risque ───────────────────────────────────────────────────────────
const openCreateModal = () => {
  form.value = { label:'', description:'', risk_type_id:null,
    frequency_level_id:null, impact_level_id:null,
    process_id:null, activity_id:null, control_procedure:'' }
  showModal.value = true
}
const closeModal = () => { showModal.value = false }

const createRisk = async () => {
  if (!form.value.label) return
  try {
    loading.value = true
    const crUrl = (props.urls as any).createRisk
    const res  = await apiCall(crUrl, 'POST', {
      ...form.value,
      entity_id: selectedEntity.value,
      year:      selectedYear.value,
    })
    const data = await res.json()
    if (!res.ok || !data.success) throw new Error(data.error ?? 'Erreur création')

    risks.value.push({
      ...data.risk,
      impact_net: null, frequency_net: null,
      control_nature_code: null, is_evaluated: false,
    })
    closeModal()
    markDirty()
    showToast('success', `Risque ${data.risk?.code ?? ''} créé`)
  } catch (e: any) {
    showToast('error', e.message)
  } finally {
    loading.value = false
  }
}

// ── HTTP helper ────────────────────────────────────────────────────────────
const csrf = () => (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
const apiCall = (url: string, method = 'GET', body: object|null = null) =>
  fetch(url, {
    method,
    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrf(), Accept:'application/json' },
    ...(body && method !== 'GET' ? { body: JSON.stringify(body) } : {}),
  })

// ── Toast ──────────────────────────────────────────────────────────────────
const showToast = (type: string, msg: string) => {
  if (toastTimer) clearTimeout(toastTimer)
  toast.value = { show:true, type, msg }
  toastTimer = setTimeout(() => { toast.value.show = false }, 3200)
}
</script>

<style scoped>
/* Table */
.table-wrapper{max-height:580px;overflow:auto;border:1px solid #dee2e6}
.audit-table{width:100%;border-collapse:separate;border-spacing:0;background:#fff;min-width:1380px}

/* Header fixe */
.thead-fixed{position:sticky;top:0;z-index:20}
.thead-fixed th{
  padding:.4rem .5rem;font-size:.67rem;font-weight:700;
  text-align:center;white-space:nowrap;
  background:#1565C0;color:#fff;border:1px solid #1e40af
}

/* Colonnes sticky gauche — header */
.col-entity,.col-process,.col-activity,.col-risk{
  position:sticky;z-index:21;background:#1565C0;color:#fff;text-align:left
}
.col-entity{left:0;min-width:72px}
.col-process{left:72px;min-width:92px}
.col-activity{left:164px;min-width:88px}
.col-risk{left:252px;min-width:185px}

/* Tailles colonnes scrollables */
.th-num{min-width:52px;text-align:center}
.th-ctrl{min-width:155px;text-align:left}
.th-nat{min-width:62px;text-align:center}
.th-eval{min-width:46px;text-align:center}

/* Body */
.audit-table tbody td{
  padding:.3rem .45rem;font-size:.72rem;
  border:1px solid #e2e8f0;vertical-align:middle
}
.audit-table tbody td.col-entity,
.audit-table tbody td.col-process,
.audit-table tbody td.col-activity,
.audit-table tbody td.col-risk{
  position:sticky;background:#fff;z-index:10
}
.audit-table tbody td.col-entity {left:0;min-width:72px;font-weight:600}
.audit-table tbody td.col-process{left:72px;min-width:92px}
.audit-table tbody td.col-activity{left:164px;min-width:88px}
.audit-table tbody td.col-risk{left:252px;min-width:185px}

.td-num{text-align:center;padding:.2rem!important}
.td-ctrl{max-width:155px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.td-nat{text-align:center}
.td-eval{text-align:center}

.risk-row:hover td{background:#f0f6ff!important}
.row-evaluated td{background:#f0fdf4}
.row-evaluated:hover td{background:#dcfce7!important}

/* Badges niveaux */
.lv-pill{
  display:inline-flex;align-items:center;justify-content:center;
  min-width:28px;height:28px;border-radius:6px;
  font-size:.78rem;font-weight:800;color:#fff;padding:0 5px
}
.dash{color:#94a3b8;font-size:.8rem}

/* Select net */
.net-sel{
  width:46px;border:1.5px solid #e2e8f0;border-radius:5px;
  padding:2px 2px;font-size:.75rem;font-weight:700;
  text-align:center;cursor:pointer;outline:none;
  background:#f8fafc;transition:all .12s;display:block;margin:auto
}
.net-sel:focus{border-color:#1565C0}

/* Checkbox */
.eval-chk{width:16px;height:16px;cursor:pointer;accent-color:#1565C0;display:block;margin:auto}

/* Modal */
.modal-ov{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:500;display:flex;align-items:center;justify-content:center;padding:20px}
.modal-box{background:#fff;border-radius:12px;box-shadow:0 8px 40px rgba(0,0,0,.22);width:100%;max-width:620px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden}
.modal-hd{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-bottom:1px solid #e2e8f0}
.modal-cls{background:none;border:none;color:#94a3b8;cursor:pointer;font-size:.85rem;padding:2px;border-radius:4px}
.modal-cls:hover{color:#dc2626;background:#fee2e2}
.modal-inner{flex:1;overflow-y:auto;padding:14px}
.modal-ft{display:flex;justify-content:flex-end;gap:8px;padding:10px 14px;border-top:1px solid #e2e8f0;background:#f8fafc}

/* Toast */
.toast-notif{position:fixed;bottom:20px;right:20px;z-index:600;display:flex;align-items:center;gap:8px;padding:10px 16px;border-radius:9px;font-size:.78rem;font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.18)}
.toast-success{background:#15803d;color:#fff}
.toast-error{background:#dc2626;color:#fff}

/* Transitions */
.mfade-enter-active,.mfade-leave-active{transition:all .2s}
.mfade-enter-from,.mfade-leave-to{opacity:0}
.mfade-enter-from .modal-box,.mfade-leave-to .modal-box{transform:scale(.96) translateY(6px)}
.toast-up-enter-active,.toast-up-leave-active{transition:all .22s}
.toast-up-enter-from,.toast-up-leave-to{opacity:0;transform:translateY(8px)}

::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-thumb{background:#e2e8f0;border-radius:3px}
</style>