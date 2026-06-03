<template>
  <VerticalLayoutAudit>
    <div class="ua-shell">

      <!-- ══ HEADER ══ -->
      <header class="ua-header">
        <div class="ua-hrow">

          <div class="ua-hinfo">
            <div class="ua-chips">
              <span class="ua-chip chip-type">
                <i class="ti ti-shield-check"></i> Univers d'Audit
              </span>
              <span v-if="selectedYear" class="ua-chip chip-year">
                <i class="ti ti-calendar"></i> {{ selectedYear }}
              </span>
              <span v-if="saveStatus==='saving'" class="ua-chip chip-saving">
                <span class="spin-dot-sm"></span> Sauvegarde…
              </span>
              <span v-else-if="saveStatus==='saved'" class="ua-chip chip-saved">
                <i class="ti ti-check"></i> Sauvegardé
              </span>
              <span v-else-if="saveStatus==='error'" class="ua-chip chip-error">
                <i class="ti ti-alert-circle"></i> Erreur
              </span>
            </div>
            <h1 class="ua-title">Univers d'Audit &amp; Risques</h1>
            <div class="ua-meta">
              <span v-if="selectedEntityObj">
                <i class="ti ti-building"></i>{{ selectedEntityObj.name }}
              </span>
              <span>
                <i class="ti ti-shield-check"></i>
                {{ risks.length }} risque(s)
                <template v-if="evaluatedCount"> · {{ evaluatedCount }} évalué(s)</template>
              </span>
              <span v-if="isDirty" class="ua-dirty">
                <i class="ti ti-circle-dot"></i> Modifications non sauvegardées
              </span>
            </div>
          </div>

          <!-- Actions header -->
          <div class="ua-hbtns">
            <div class="ua-search-wrap">
              <i class="ti ti-search ua-search-ico"></i>
              <input v-model="searchQuery" class="ua-search" placeholder="Rechercher…"/>
              <button v-if="searchQuery" class="ua-search-clr" @click="searchQuery=''">
                <i class="ti ti-x"></i>
              </button>
            </div>
            <button class="btn btn-success btn-sm"
                    @click="openCreateModal"
                    :disabled="!selectedEntity || !selectedYear || loading">
              <i class="ti ti-plus"></i> Créer un risque
            </button>
            <button class="btn btn-save"
                    @click="saveUniverse"
                    :disabled="!selectedEntity || !selectedYear || saving || !risks.length">
              <span v-if="saving" class="spin-dot"></span>
              <i v-else class="ti ti-device-floppy"></i>
              Sauvegarder
            </button>
          </div>
        </div>
      </header>

      <!-- ══ BODY ══ -->
      <div class="ua-body">

        <!-- Filtres -->
        <section class="card">
          <div class="card-label"><i class="ti ti-filter"></i> Sélection</div>
          <div class="card-body">
            <div class="filters-grid">
              <div class="field">
                <label class="lbl">Entité <span class="req">*</span></label>
                <select v-model.number="selectedEntity" class="inp inp-sm" @change="onFilterChange">
                  <option :value="null">— Sélectionner —</option>
                  <option v-for="e in (entities as any[])" :key="(e as any).id" :value="(e as any).id">
                    {{ (e as any).code_base }} – {{ (e as any).name }}
                  </option>
                </select>
              </div>
              <div class="field">
                <label class="lbl">Année <span class="req">*</span></label>
                <select v-model.number="selectedYear" class="inp inp-sm" @change="onFilterChange">
                  <option :value="null">— Année —</option>
                  <option v-for="y in (years as any[])" :key="y" :value="y">{{ y }}</option>
                </select>
              </div>
              <div class="field">
                <label class="lbl">Fait par</label>
                <input v-model="faitPar" class="inp inp-sm" placeholder="Auditeur…" @input="markDirty"/>
              </div>
              <div class="field">
                <label class="lbl">Date analyse</label>
                <input v-model="dateAnalyse" type="date" class="inp inp-sm" @change="markDirty"/>
              </div>
            </div>
          </div>
        </section>

        <!-- Tableau -->
        <section class="card card-tbl">
          <div class="card-label"><i class="ti ti-table"></i> Tableau des risques</div>

          <div v-if="loading" class="ua-loading">
            <div class="ua-spinner"></div>
            <span>Chargement des risques…</span>
          </div>

          <div v-else-if="!risks.length" class="ua-empty">
            <template v-if="!selectedEntity || !selectedYear">
              <i class="ti ti-selector"></i>
              <strong>Sélectionnez une entité et une année</strong>
              <p>Les risques de l'univers d'audit s'afficheront ici.</p>
            </template>
            <template v-else>
              <i class="ti ti-shield-off"></i>
              <strong>Aucun risque pour cette entité / année</strong>
              <p>Créez le premier risque pour commencer l'univers d'audit.</p>
              <button class="btn btn-save btn-sm" @click="openCreateModal">
                <i class="ti ti-plus"></i> Créer le premier risque
              </button>
            </template>
          </div>

          <div v-else class="tbl-outer">
            <div class="tbl-wrap">
              <table class="ua-tbl">
                <thead>
                  <tr>
                    <th class="th-sticky th-entity">ENTITÉ</th>
                    <th class="th-sticky th-process">PROCESSUS</th>
                    <th class="th-sticky th-activity">ACTIVITÉ</th>
                    <th class="th-sticky th-risk">CODE / LIBELLÉ</th>
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
                  <tr v-for="risk in filteredRisks" :key="risk.id"
                      class="risk-row"
                      :class="{'row-evaluated': risk.is_evaluated}">

                    <td class="td-sticky td-entity small">{{ entityCode(risk.entity_id) }}</td>
                    <td class="td-sticky td-process small">{{ risk.process_code || processCode(risk.process_id) }}</td>
                    <td class="td-sticky td-activity small">{{ risk.activity_code || activityCode(risk.activity_id) }}</td>
                    <td class="td-sticky td-risk">
                      <div class="risk-code">{{ risk.code }}</div>
                      <div class="risk-label">{{ truncate(risk.label, 50) }}</div>
                    </td>

                    <td class="td-num">
                      <span v-if="risk.impact_level" class="lv-pill" :style="{background: hexOf(risk.impact_color)}">{{ risk.impact_level }}</span>
                      <span v-else class="dash">—</span>
                    </td>
                    <td class="td-num">
                      <span v-if="risk.frequency_level" class="lv-pill" :style="{background: hexOf(risk.frequency_color)}">{{ risk.frequency_level }}</span>
                      <span v-else class="dash">—</span>
                    </td>
                    <td class="td-num">
                      <span v-if="risk.criticality" class="lv-pill" :style="{background: critColor(risk.criticality)}">{{ risk.criticality }}</span>
                      <span v-else class="dash">—</span>
                    </td>

                    <td class="td-ctrl small">{{ truncate(risk.control_procedure, 35) }}</td>

                    <td class="td-num">
                      <select class="net-sel" v-model.number="risk.impact_net" @change="onNetChange(risk)"
                              :style="risk.impact_net ? {background: critColor(risk.impact_net), color:'#fff', borderColor: critColor(risk.impact_net)} : {}">
                        <option :value="null">—</option>
                        <option v-for="lv in (impacts as any[])" :key="(lv as any).id" :value="(lv as any).level">{{ (lv as any).level }}</option>
                      </select>
                    </td>
                    <td class="td-num">
                      <select class="net-sel" v-model.number="risk.frequency_net" @change="onNetChange(risk)"
                              :style="risk.frequency_net ? {background: critColor(risk.frequency_net), color:'#fff', borderColor: critColor(risk.frequency_net)} : {}">
                        <option :value="null">—</option>
                        <option v-for="lv in (frequencies as any[])" :key="(lv as any).id" :value="(lv as any).level">{{ (lv as any).level }}</option>
                      </select>
                    </td>

                    <td class="td-num">
                      <span v-if="globNet(risk)" class="lv-pill" :style="{background: critColor(globNet(risk)!)}">{{ globNet(risk) }}</span>
                      <span v-else class="dash">—</span>
                    </td>
                    <td class="td-nat small">{{ risk.control_nature_code || '—' }}</td>
                    <td class="td-num">
                      <span v-if="qualifNet(risk)" class="lv-pill" :style="{background: qualifColor(qualifNet(risk)!)}">{{ qualifNet(risk) }}</span>
                      <span v-else class="dash">—</span>
                    </td>
                    <td class="td-eval">
                      <input type="checkbox" class="eval-chk" v-model="risk.is_evaluated" @change="markDirty"/>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- Synthèse + signature -->
        <div v-if="risks.length" class="synth-row">
          <div class="synth-f">
            <label><i class="ti ti-notes"></i> Synthèse</label>
            <textarea class="synth-ta" v-model="synthese" rows="3"
                      placeholder="Synthèse générale de l'univers d'audit…"
                      @input="markDirty"/>
          </div>
          <div class="author-fs">
            <div class="af"><label>Fait par</label><input class="inp" v-model="faitPar" @input="markDirty"/></div>
            <div class="af"><label>Date analyse</label><input class="inp" type="date" v-model="dateAnalyse" @change="markDirty"/></div>
          </div>
        </div>

        <!-- Footer -->
        <footer class="ua-footer">
          <div>
            <button type="button" class="btn btn-ghost" :disabled="!isDirty" @click="annuler">
              <i class="ti ti-x"></i> Annuler
            </button>
            <button type="button" class="btn btn-save"
                    :disabled="!selectedEntity || !selectedYear || saving || !risks.length"
                    @click="saveUniverse">
              <span v-if="saving" class="spin-dot"></span>
              <i v-else class="ti ti-device-floppy"></i>
              Sauvegarder
            </button>
          </div>
          <div class="footer-c">
            <span v-if="universeId" class="saved-code">
              <i class="ti ti-check"></i> Univers #{{ universeId }}
            </span>
          </div>
          <div>
            <small class="text-status">{{ statusMessage }}</small>
          </div>
        </footer>

      </div><!-- /ua-body -->
    </div><!-- /ua-shell -->

    <!-- Modal créer risque -->
    <Teleport to="body">
      <transition name="mfade">
        <div v-if="showModal" class="modal-ov" @click.self="closeModal">
          <div class="modal-box">
            <div class="modal-hd">
              <div class="modal-hd-l"><i class="ti ti-plus"></i> Créer un risque</div>
              <button class="modal-cls" @click="closeModal"><i class="ti ti-x"></i></button>
            </div>
            <div class="modal-body">
              <div class="form-grid2">
                <div class="field">
                  <label class="lbl">Entité</label>
                  <input class="inp" :value="entityName(selectedEntity)" readonly/>
                </div>
                <div class="field">
                  <label class="lbl">Année</label>
                  <input class="inp" :value="selectedYear" readonly/>
                </div>
              </div>
              <div class="field mt9">
                <label class="lbl">Libellé <span class="req">*</span></label>
                <input v-model="form.label" class="inp" placeholder="Libellé du risque…"/>
              </div>
              <div class="field mt9">
                <label class="lbl">Description</label>
                <textarea v-model="form.description" class="ta" rows="2"></textarea>
              </div>
              <div class="form-grid2 mt9">
                <div class="field">
                  <label class="lbl">Type risque</label>
                  <select v-model.number="form.risk_type_id" class="inp inp-sm">
                    <option :value="null">—</option>
                    <option v-for="t in (riskTypes as any[])" :key="(t as any).id" :value="(t as any).id">{{ (t as any).code }} – {{ (t as any).label }}</option>
                  </select>
                </div>
                <div class="field">
                  <label class="lbl">Processus</label>
                  <select v-model.number="form.process_id" class="inp inp-sm">
                    <option :value="null">—</option>
                    <option v-for="p in (processes as any[])" :key="(p as any).id" :value="(p as any).id">{{ (p as any).code }} – {{ (p as any).name }}</option>
                  </select>
                </div>
                <div class="field">
                  <label class="lbl">Activité</label>
                  <select v-model.number="form.activity_id" class="inp inp-sm">
                    <option :value="null">—</option>
                    <option v-for="a in (activities as any[])" :key="(a as any).id" :value="(a as any).id">{{ (a as any).code }} – {{ (a as any).name }}</option>
                  </select>
                </div>
                <div class="field">
                  <label class="lbl">Fréquence brute</label>
                  <select v-model.number="form.frequency_level_id" class="inp inp-sm">
                    <option :value="null">—</option>
                    <option v-for="f in (frequencies as any[])" :key="(f as any).id" :value="(f as any).id">{{ (f as any).level }} – {{ (f as any).label }}</option>
                  </select>
                </div>
                <div class="field">
                  <label class="lbl">Impact brut</label>
                  <select v-model.number="form.impact_level_id" class="inp inp-sm">
                    <option :value="null">—</option>
                    <option v-for="i in (impacts as any[])" :key="(i as any).id" :value="(i as any).id">{{ (i as any).level }} – {{ (i as any).label }}</option>
                  </select>
                </div>
              </div>
              <div class="field mt9">
                <label class="lbl">Procédure de contrôle</label>
                <textarea v-model="form.control_procedure" class="ta" rows="2"></textarea>
              </div>
            </div>
            <div class="modal-ft">
              <button class="btn btn-ghost" @click="closeModal">Annuler</button>
              <button class="btn btn-save" @click="createRisk" :disabled="!form.label || loading">
                <span v-if="loading" class="spin-dot"></span>
                <i v-else class="ti ti-device-floppy"></i> Créer
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- Toast -->
    <Teleport to="body">
      <transition name="toast-up">
        <div v-if="toast.show" class="toast" :class="`toast-${toast.type}`">
          <i :class="toast.type==='success' ? 'ti ti-circle-check' : 'ti ti-alert-circle'"></i>
          {{ toast.msg }}
        </div>
      </transition>
    </Teleport>

  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, computed, onBeforeUnmount } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
  entities:    { type: Array,  default: () => [] },
  processes:   { type: Array,  default: () => [] },
  activities:  { type: Array,  default: () => [] },
  riskTypes:   { type: Array,  default: () => [] },
  frequencies: { type: Array,  default: () => [] },
  impacts:     { type: Array,  default: () => [] },
  matrix:      { type: Array,  default: () => [] },
  years:       { type: Array,  default: () => [] },
  urls:        { type: Object, default: () => ({}) },
})

// ── Palette ────────────────────────────────────────────────────────────────
const PALETTE: Record<string,string> = {
  danger:'#dc3545', warning:'#ffc107', info:'#0dcaf0',
  success:'#28a745', secondary:'#6c757d', primary:'#0d6efd',
}
const hexOf      = (c: string) => PALETTE[c] ?? c ?? PALETTE.secondary
const critColor  = (n: number|null) => {
  if (!n) return PALETTE.secondary
  if (n <= 2) return '#22c55e'
  if (n <= 4) return '#a3e635'
  if (n <= 6) return '#facc15'
  if (n <= 9) return '#f97316'
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

// ── State ──────────────────────────────────────────────────────────────────
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
const searchQuery    = ref('')

const form = ref({
  label: '', description: '', risk_type_id: null as number|null,
  frequency_level_id: null as number|null, impact_level_id: null as number|null,
  process_id: null as number|null, activity_id: null as number|null,
  control_procedure: '',
})

const toast = ref({ show: false, type: 'success', msg: '' })
let toastTimer:    ReturnType<typeof setTimeout>|null = null
let autoSaveTimer: ReturnType<typeof setTimeout>|null = null

onBeforeUnmount(() => {
  if (autoSaveTimer) clearTimeout(autoSaveTimer)
  if (toastTimer)    clearTimeout(toastTimer)
})

// ── Computed ───────────────────────────────────────────────────────────────
const selectedEntityObj = computed(() =>
  (props.entities as any[]).find(e => e.id === selectedEntity.value) ?? null
)
const statusMessage = computed(() => {
  if (!selectedEntity.value || !selectedYear.value) return '⏳ Sélectionnez une entité et une année'
  if (loading.value) return '⏳ Chargement…'
  if (isDirty.value) return '● Modifications en cours'
  return `✅ ${risks.value.length} risque(s)`
})
const evaluatedCount = computed(() => risks.value.filter(r => r.is_evaluated).length)
const filteredRisks  = computed(() => {
  if (!searchQuery.value) return risks.value
  const q = searchQuery.value.toLowerCase()
  return risks.value.filter(r =>
    r.code?.toLowerCase().includes(q) ||
    r.label?.toLowerCase().includes(q) ||
    r.process_code?.toLowerCase().includes(q) ||
    r.activity_code?.toLowerCase().includes(q)
  )
})

// ── Calculs ────────────────────────────────────────────────────────────────
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

// ── Helpers ────────────────────────────────────────────────────────────────
const entityCode   = (id: number) => (props.entities  as any[]).find(e => e.id === id)?.code_base ?? '—'
const entityName   = (id: number|null) => (props.entities as any[]).find(e => e.id === id)?.name ?? '—'
const processCode  = (id: number) => (props.processes  as any[]).find(p => p.id === id)?.code ?? '—'
const activityCode = (id: number) => (props.activities as any[]).find(a => a.id === id)?.code ?? '—'
const truncate     = (t: string, n: number) => !t ? '—' : t.length > n ? t.slice(0, n) + '…' : t

// ── Chargement ─────────────────────────────────────────────────────────────
const onFilterChange = async () => {
  risks.value      = []
  universeId.value = null
  isDirty.value    = false
  synthese.value   = ''
  if (selectedEntity.value && selectedYear.value) await loadRisks()
}

const loadRisks = async () => {
  try {
    loading.value = true
    const url = (props.urls as any).loadRisks
    if (!url) throw new Error('URL loadRisks non configurée — vérifiez le contrôleur')

    const res  = await apiCall(url, 'POST', { entity_id: selectedEntity.value, year: selectedYear.value })
    const raw  = await res.text()
    const data = JSON.parse(raw)

    if (!res.ok || !data.success) throw new Error(data.error ?? data.message ?? `HTTP ${res.status}`)

    risks.value       = data.risks        ?? []
    universeId.value  = data.universe_id  ?? null
    synthese.value    = data.synthese     ?? ''
    faitPar.value     = data.fait_par     ?? ''
    dateAnalyse.value = data.date_analyse ?? ''
    isDirty.value     = false

  } catch (e: any) {
    console.error('loadRisks error:', e)
    showToast('error', e.message ?? 'Erreur chargement')
  } finally {
    loading.value = false
  }
}

// ── Auto-save ──────────────────────────────────────────────────────────────
const markDirty  = () => {
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

    const res  = await apiCall((props.urls as any).save, 'POST', payload)
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

function annuler() {
  if (!confirm('Annuler les modifications non sauvegardées ?')) return
  onFilterChange()
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
    const res  = await apiCall((props.urls as any).createRisk, 'POST', {
      ...form.value, entity_id: selectedEntity.value, year: selectedYear.value,
    })
    const data = await res.json()
    if (!res.ok || !data.success) throw new Error(data.error ?? 'Erreur création')

    risks.value.push({ ...data.risk, impact_net: null, frequency_net: null, control_nature_code: null, is_evaluated: false })
    closeModal()
    markDirty()
    showToast('success', `Risque ${data.risk?.code ?? ''} créé`)
  } catch (e: any) {
    showToast('error', e.message)
  } finally {
    loading.value = false
  }
}

// ── HTTP ───────────────────────────────────────────────────────────────────

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
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

.ua-shell { display: flex; flex-direction: column; min-height: 100vh; background: #f4f6f8; font-family: 'Segoe UI', system-ui, sans-serif; --mc: #1565C0; --rd: #dc2626; --gr: #15803d; }

/* Header */
.ua-header { position: sticky; top: 0; z-index: 100; background: #fff; border-bottom: 1px solid #e2e8f0; box-shadow: 0 1px 4px rgba(0,0,0,.06); padding: 0 16px; }
.ua-hrow { display: flex; align-items: center; gap: 10px; min-height: 58px; padding: 6px 0; flex-wrap: wrap; }
.ua-hinfo { flex: 1; min-width: 0; }
.ua-chips { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; margin-bottom: 2px; }
.ua-chip { display: inline-flex; align-items: center; gap: 3px; font-size: .6rem; font-weight: 700; padding: 2px 7px; border-radius: 9px; text-transform: uppercase; letter-spacing: .04em; }
.chip-type   { background: rgba(21,101,192,.12); color: #1565C0; border: 1px solid rgba(21,101,192,.25); }
.chip-year   { background: rgba(21,101,192,.08); color: #1565C0; }
.chip-saving { background: #fef3c7; color: #d97706; }
.chip-saved  { background: #d1e7dd; color: #0f5132; }
.chip-error  { background: #f8d7da; color: #842029; }
.ua-title { font-size: .88rem; font-weight: 700; color: #1a1a2e; }
.ua-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 2px; }
.ua-meta span { display: inline-flex; align-items: center; gap: 3px; font-size: .67rem; color: #64748b; }
.ua-dirty { color: #d97706 !important; font-weight: 600; }
.ua-hbtns { display: flex; align-items: center; gap: 7px; flex-shrink: 0; flex-wrap: wrap; }
.ua-search-wrap { position: relative; display: flex; align-items: center; }
.ua-search-ico { position: absolute; left: 8px; color: #94a3b8; font-size: .78rem; pointer-events: none; }
.ua-search { border: 1px solid #e2e8f0; border-radius: 7px; padding: 5px 28px; font-size: .74rem; color: #374151; font-family: inherit; outline: none; width: 180px; background: #f8fafc; transition: all .15s; }
.ua-search:focus { border-color: var(--mc); background: #fff; width: 220px; }
.ua-search-clr { position: absolute; right: 6px; background: none; border: none; color: #94a3b8; cursor: pointer; font-size: .75rem; padding: 2px; }

/* Body */
.ua-body { flex: 1; padding: 12px 16px 24px; display: flex; flex-direction: column; gap: 12px; }

/* Cards */
.card { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; position: relative; }
.card-label { position: absolute; top: -10px; left: 14px; background: #fff; padding: 0 8px; font-size: .63rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--mc); border: 1px solid rgba(21,101,192,.3); border-radius: 4px; display: inline-flex; align-items: center; gap: 5px; z-index: 1; white-space: nowrap; }
.card-body { padding: 18px 14px 14px; display: flex; flex-direction: column; gap: 9px; }
.card-tbl { overflow: hidden; }

/* Filtres */
.filters-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
@media (max-width: 900px) { .filters-grid { grid-template-columns: 1fr 1fr; } }

/* Fields */
.field { display: flex; flex-direction: column; gap: 3px; }
.lbl { font-size: .7rem; font-weight: 600; color: #475569; }
.req { color: #dc2626; }
.inp { width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 6px 10px; font-size: .8rem; color: #1a1a2e; background: #fff; outline: none; font-family: inherit; transition: border-color .12s; }
.inp:focus { border-color: var(--mc); box-shadow: 0 0 0 2px rgba(21,101,192,.1); }
.inp:disabled { background: #f8fafc; color: #94a3b8; }
.inp-sm { padding: 4px 8px; font-size: .76rem; }
.ta { width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 6px 10px; font-size: .8rem; color: #1a1a2e; font-family: inherit; resize: vertical; outline: none; }
.ta:focus { border-color: var(--mc); }
.form-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 9px; }
.mt9 { margin-top: 9px; }

/* États tableau */
.ua-loading { display: flex; align-items: center; gap: 12px; padding: 48px; justify-content: center; color: #64748b; font-size: .82rem; }
.ua-spinner { width: 28px; height: 28px; border: 3px solid #e2e8f0; border-top-color: var(--mc); border-radius: 50%; animation: spin 1s linear infinite; }
.ua-empty { display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 60px 24px; color: #94a3b8; text-align: center; }
.ua-empty i { font-size: 2.5rem; opacity: .25; }
.ua-empty strong { color: #475569; font-size: .88rem; }
.ua-empty p { font-size: .78rem; max-width: 380px; line-height: 1.6; }

/* Tableau */
.tbl-outer { padding: 18px 0 0; }
.tbl-wrap { overflow: auto; max-height: 560px; border-top: 1px solid #e2e8f0; }
.ua-tbl { width: 100%; border-collapse: separate; border-spacing: 0; background: #fff; min-width: 1380px; }

.ua-tbl thead th { position: sticky; top: 0; z-index: 20; padding: .45rem .5rem; font-size: .62rem; font-weight: 700; text-align: center; white-space: nowrap; background: #1e3a5f; color: rgba(255,255,255,.92); border: 1px solid #162f4a; }
.th-sticky { z-index: 21 !important; text-align: left !important; }
.th-entity   { left: 0;     min-width: 72px; }
.th-process  { left: 72px;  min-width: 92px; }
.th-activity { left: 164px; min-width: 88px; }
.th-risk     { left: 252px; min-width: 185px; }
.th-num  { min-width: 52px;  text-align: center; }
.th-ctrl { min-width: 155px; text-align: left; }
.th-nat  { min-width: 62px;  text-align: center; }
.th-eval { min-width: 46px;  text-align: center; }

.ua-tbl tbody td { padding: .3rem .45rem; font-size: .72rem; border: 1px solid #e2e8f0; vertical-align: middle; }
.td-sticky { position: sticky; background: #fff; z-index: 10; }
.td-entity   { left: 0;     min-width: 72px; font-weight: 600; }
.td-process  { left: 72px;  min-width: 92px; }
.td-activity { left: 164px; min-width: 88px; }
.td-risk     { left: 252px; min-width: 185px; }
.td-num  { text-align: center; padding: .2rem !important; }
.td-ctrl { max-width: 155px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #64748b; }
.td-nat  { text-align: center; }
.td-eval { text-align: center; }

.risk-row:hover td { background: #f0f6ff !important; }
.row-evaluated td { background: #f0fdf4; }
.row-evaluated:hover td { background: #dcfce7 !important; }
.risk-code  { font-size: .69rem; font-weight: 700; color: var(--mc); }
.risk-label { font-size: .7rem; color: #1a1a2e; line-height: 1.3; }

/* Badges */
.lv-pill { display: inline-flex; align-items: center; justify-content: center; min-width: 28px; height: 28px; border-radius: 6px; font-size: .78rem; font-weight: 800; color: #fff; padding: 0 5px; }
.dash { color: #94a3b8; font-size: .8rem; }
.net-sel { width: 46px; border: 1.5px solid #e2e8f0; border-radius: 5px; padding: 2px; font-size: .75rem; font-weight: 700; text-align: center; cursor: pointer; outline: none; background: #f8fafc; transition: all .12s; display: block; margin: auto; }
.net-sel:focus { border-color: var(--mc); }
.eval-chk { width: 16px; height: 16px; cursor: pointer; accent-color: var(--mc); display: block; margin: auto; }

/* Synthèse */
.synth-row { display: flex; gap: 10px; background: #fff; border: 1px solid #e2e8f0; border-radius: 9px; padding: 12px; flex-wrap: wrap; }
.synth-f { flex: 1; min-width: 260px; display: flex; flex-direction: column; gap: 5px; }
.synth-f label { font-size: .72rem; font-weight: 600; color: #475569; display: flex; align-items: center; gap: 4px; }
.synth-ta { width: 100%; border: 1px solid #d1d5db; border-radius: 7px; padding: 7px 10px; font-size: .78rem; color: #1a1a2e; font-family: inherit; resize: vertical; outline: none; }
.synth-ta:focus { border-color: var(--mc); }
.author-fs { display: flex; flex-direction: column; gap: 8px; min-width: 180px; }
.af { display: flex; flex-direction: column; gap: 3px; }
.af label { font-size: .68rem; font-weight: 600; color: #475569; }

/* Footer */
.ua-footer { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; padding: 10px 14px; background: #fff; border: 1px solid #e2e8f0; border-radius: 9px; }
.ua-footer > div { display: flex; align-items: center; gap: 7px; }
.footer-c { flex: 1; display: flex; justify-content: center; }
.saved-code { font-size: .72rem; color: var(--gr); display: flex; align-items: center; gap: 4px; font-weight: 600; }
.text-status { font-size: .7rem; color: #64748b; }

/* Boutons */
.btn { display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; border-radius: 6px; font-size: .74rem; font-weight: 600; border: 1px solid transparent; cursor: pointer; font-family: inherit; text-decoration: none; transition: all .12s; }
.btn:disabled { opacity: .5; cursor: not-allowed; }
.btn-sm { padding: 4px 9px; font-size: .7rem; }
.btn-ghost { background: transparent; color: #64748b; border-color: #d1d5db; }
.btn-ghost:hover:not(:disabled) { background: #f1f5f9; }
.btn-save { background: var(--mc); color: #fff; }
.btn-save:hover:not(:disabled) { filter: brightness(1.1); }
.btn-success { background: #15803d; color: #fff; }
.btn-success:hover:not(:disabled) { background: #166534; }
.spin-dot { width: 11px; height: 11px; border: 2px solid rgba(255,255,255,.4); border-top-color: #fff; border-radius: 50%; animation: spin .6s linear infinite; display: inline-block; }
.spin-dot-sm { width: 9px; height: 9px; border: 2px solid rgba(255,255,255,.4); border-top-color: #fff; border-radius: 50%; animation: spin .6s linear infinite; display: inline-block; }

/* Modal */
.modal-ov { position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 400; display: flex; align-items: center; justify-content: center; padding: 20px; }
.modal-box { background: #fff; border-radius: 14px; box-shadow: 0 8px 40px rgba(0,0,0,.22); width: 100%; max-width: 620px; max-height: 88vh; display: flex; flex-direction: column; overflow: hidden; }
.modal-hd { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; border-bottom: 1px solid #e2e8f0; flex-shrink: 0; }
.modal-hd-l { display: flex; align-items: center; gap: 6px; font-size: .82rem; font-weight: 700; color: #1a1a2e; }
.modal-hd-l i { color: var(--mc); }
.modal-cls { width: 28px; height: 28px; border: none; background: #f1f5f9; border-radius: 7px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #64748b; transition: all .13s; }
.modal-cls:hover { background: #fee2e2; color: #dc2626; }
.modal-body { flex: 1; overflow-y: auto; padding: 14px 18px; }
.modal-ft { display: flex; justify-content: flex-end; gap: 8px; padding: 12px 18px; border-top: 1px solid #e2e8f0; flex-shrink: 0; }

/* Toast */
.toast { position: fixed; bottom: 22px; right: 22px; z-index: 600; display: flex; align-items: center; gap: 9px; padding: 10px 16px; border-radius: 9px; font-size: .78rem; font-weight: 600; box-shadow: 0 4px 16px rgba(0,0,0,.18); }
.toast-success { background: var(--gr); color: #fff; }
.toast-error   { background: var(--rd); color: #fff; }

/* Transitions */
.mfade-enter-active, .mfade-leave-active { transition: all .2s ease; }
.mfade-enter-from, .mfade-leave-to { opacity: 0; }
.mfade-enter-from .modal-box, .mfade-leave-to .modal-box { transform: scale(.96) translateY(6px); }
.toast-up-enter-active, .toast-up-leave-active { transition: all .22s ease; }
.toast-up-enter-from, .toast-up-leave-to { opacity: 0; transform: translateY(8px); }

::-webkit-scrollbar { width: 5px; height: 5px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>