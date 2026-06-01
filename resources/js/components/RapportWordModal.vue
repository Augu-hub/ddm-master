<template>
  <Teleport to="body">
    <Transition name="rwm-fade">
      <div v-if="show" class="rwm-backdrop" @click.self="close">
        <div class="rwm-modal">

          <!-- ── HEADER ── -->
          <div class="rwm-header">
            <div class="rwm-header__left">
              <div class="rwm-file-ico"><i class="ti ti-file-word"></i></div>
              <div>
                <div class="rwm-title">Rapport d'Audit Interne</div>
                <div class="rwm-sub">{{ currentStep === 'loading' ? 'Génération en cours…' : 'Édition &amp; export' }}</div>
              </div>
            </div>
            <div class="rwm-header__right">
              <span v-if="dirty" class="rwm-dirty"><i class="ti ti-pencil"></i> Non sauvegardé</span>
              <button class="rwm-ico-btn" @click="close" title="Fermer"><i class="ti ti-x"></i></button>
            </div>
          </div>

          <!-- ── ONGLETS ── -->
          <div class="rwm-tabs">
            <button class="rwm-tab" :class="{on: activeTab==='apercu'}" @click="activeTab='apercu'">
              <i class="ti ti-eye"></i> Aperçu
            </button>
            <button class="rwm-tab" :class="{on: activeTab==='edit'}" @click="activeTab='edit'">
              <i class="ti ti-edit"></i> Zones modifiables
              <span v-if="editableCount" class="rwm-tab-badge">{{ editableCount }}</span>
            </button>
            <button class="rwm-tab" :class="{on: activeTab==='export'}" @click="activeTab='export'">
              <i class="ti ti-download"></i> Exporter
            </button>
          </div>

          <!-- ── BODY ── -->
          <div class="rwm-body">

            <!-- LOADING -->
            <div v-if="currentStep === 'loading'" class="rwm-center">
              <div class="rwm-spinner"></div>
              <p class="rwm-load-title">Génération du rapport…</p>
              <p class="rwm-load-sub">Extraction des données · Mise en forme</p>
            </div>

            <!-- ERREUR -->
            <div v-else-if="currentStep === 'error'" class="rwm-center rwm-center--err">
              <i class="ti ti-alert-circle"></i>
              <p>{{ errorMsg }}</p>
              <button class="rwm-btn rwm-btn--ghost" @click="generate">Réessayer</button>
            </div>

            <!-- APERÇU -->
            <template v-else-if="activeTab === 'apercu'">
              <div class="rwm-preview-wrap">
                <div class="rwm-page">

                  <!-- Couverture (verrouillée) -->
                  <div class="rwm-section-hd rwm-locked">
                    <i class="ti ti-lock" style="font-size:.7rem"></i> Section verrouillée — Données source
                  </div>
                  <div class="rwm-cover">
                    <div class="rwm-cover-title">RAPPORT D'AUDIT INTERNE</div>
                    <div class="rwm-cover-meta">
                      <div v-for="(v, k) in coverMeta" :key="k" class="rwm-cover-row">
                        <span class="rwm-cover-k">{{ k }}</span>
                        <span class="rwm-cover-v">{{ v }}</span>
                      </div>
                    </div>
                    <div class="rwm-mission-box">
                      <span class="rwm-mission-lbl">Intitulé de la mission</span>
                      <div class="rwm-mission-val">{{ reportData?.mission?.libelle }}</div>
                    </div>
                    <div class="rwm-conf">🔒 CONFIDENTIEL — Ce rapport s'adresse uniquement aux personnes désignées.</div>
                  </div>

                  <!-- Opinion (modifiable) -->
                  <div class="rwm-section-hd rwm-editable-hd">
                    <i class="ti ti-pencil" style="font-size:.7rem"></i> Section modifiable — 1.1 Opinion Générale
                  </div>
                  <div class="rwm-editable-zone" @click="goToEdit('opinion')">
                    <div class="rwm-ezone-hint">Cliquer pour modifier ↗</div>
                    <div class="rwm-opinion-badge">{{ reportData?.opinion?.niveau }}</div>
                    <p class="rwm-opinion-txt">{{ editableFields.opinion || reportData?.opinion?.description }}</p>
                  </div>

                  <!-- Stats (verrouillées) -->
                  <div class="rwm-section-hd rwm-locked">
                    <i class="ti ti-lock" style="font-size:.7rem"></i> Statistiques des constats
                  </div>
                  <div class="rwm-stats">
                    <div class="rwm-stat rwm-stat--red">
                      <div class="rwm-stat-n">{{ reportData?.statsConstats?.critique ?? 0 }}</div>
                      <div class="rwm-stat-l">Critique</div>
                    </div>
                    <div class="rwm-stat rwm-stat--orange">
                      <div class="rwm-stat-n">{{ reportData?.statsConstats?.significatif ?? 0 }}</div>
                      <div class="rwm-stat-l">Significatif</div>
                    </div>
                    <div class="rwm-stat rwm-stat--green">
                      <div class="rwm-stat-n">{{ reportData?.statsConstats?.peu_significatif ?? 0 }}</div>
                      <div class="rwm-stat-l">Peu significatif</div>
                    </div>
                    <div class="rwm-stat rwm-stat--blue">
                      <div class="rwm-stat-n">{{ reportData?.statsConstats?.total ?? 0 }}</div>
                      <div class="rwm-stat-l">Total</div>
                    </div>
                  </div>

                  <!-- Résumé constats (verrouillé) -->
                  <div class="rwm-section-hd rwm-locked">
                    <i class="ti ti-lock" style="font-size:.7rem"></i> 1.2 — Résumé des Constats (source BD)
                  </div>
                  <div v-for="(obj, i) in reportData?.tableauObjectifs ?? []" :key="obj.num" class="rwm-obj-preview">
                    <div class="rwm-obj-title">{{ i+1 }}. {{ obj.objectif }}</div>
                    <div v-for="c in getConstatsForObj(obj.num)" :key="c.num_frap" class="rwm-constat-line">
                      <span class="rwm-frap-ref">{{ c.num_frap }}</span>
                      <span class="rwm-frap-txt">{{ c.probleme || c.fait_constats?.substring(0,100) }}</span>
                      <span :class="['rwm-badge','rwm-badge--'+(c.importance??'basse')]">{{ badgeLabel(c.importance) }}</span>
                    </div>
                  </div>

                  <!-- Zones modifiables 1.4 → 1.8 -->
                  <div class="rwm-section-hd rwm-editable-hd">
                    <i class="ti ti-pencil" style="font-size:.7rem"></i> Sections modifiables (1.4 → 1.8)
                  </div>
                  <div v-for="field in freeTextFields" :key="field.key"
                       class="rwm-editable-zone rwm-editable-zone--sm"
                       @click="goToEdit(field.key)">
                    <div class="rwm-ezone-hint">{{ field.label }} — Cliquer pour modifier ↗</div>
                    <p class="rwm-ezone-txt">{{ editableFields[field.key] || field.placeholder }}</p>
                  </div>

                </div>
              </div>
            </template>

            <!-- ÉDITEUR ZONES MODIFIABLES -->
            <template v-else-if="activeTab === 'edit'">
              <div class="rwm-edit-wrap">
                <div class="rwm-edit-legend">
                  <i class="ti ti-info-circle"></i>
                  Seules les zones <strong>surlignées en bleu</strong> sont modifiables.
                  Les données sources sont verrouillées.
                </div>
                <div v-for="field in editableFieldDefs" :key="field.key"
                     class="rwm-field-group" :id="'rwm-field-'+field.key">
                  <div class="rwm-field-header">
                    <i class="ti ti-pencil"></i>
                    <span class="rwm-field-label">{{ field.label }}</span>
                    <span class="rwm-field-section">{{ field.section }}</span>
                  </div>
                  <textarea
                    class="rwm-field-input"
                    :rows="field.rows ?? 3"
                    :placeholder="field.placeholder"
                    v-model="editableFields[field.key]"
                    @input="onFieldInput"
                  ></textarea>
                </div>
              </div>
            </template>

            <!-- EXPORT -->
            <template v-else-if="activeTab === 'export'">
              <div class="rwm-export-wrap">

                <!-- Carte Word -->
                <div class="rwm-export-card">
                  <div class="rwm-export-ico" style="background:#dbeafe;color:#1d4ed8">
                    <i class="ti ti-file-word"></i>
                  </div>
                  <div style="flex:1">
                    <div class="rwm-export-title">Rapport Word (.docx)</div>
                    <div class="rwm-export-sub">Document complet avec toutes vos modifications, prêt pour Word.</div>
                  </div>
                  <button class="rwm-btn rwm-btn--dl" :disabled="generatingWord" @click="downloadWord">
                    <span v-if="generatingWord" class="rwm-spin"></span>
                    <i v-else class="ti ti-file-word"></i>
                    {{ generatingWord ? 'Génération…' : 'Télécharger .docx' }}
                  </button>
                </div>

                <!-- Carte HTML -->
                <div class="rwm-export-card">
                  <div class="rwm-export-ico" style="background:#d1fae5;color:#065f46">
                    <i class="ti ti-file-code"></i>
                  </div>
                  <div style="flex:1">
                    <div class="rwm-export-title">Rapport HTML (.html)</div>
                    <div class="rwm-export-sub">Page web autonome, ouvrable dans tout navigateur, imprimable en PDF via Ctrl+P.</div>
                  </div>
                  <button class="rwm-btn rwm-btn--html" :disabled="generatingHtml" @click="downloadHtml">
                    <span v-if="generatingHtml" class="rwm-spin"></span>
                    <i v-else class="ti ti-file-code"></i>
                    {{ generatingHtml ? 'Génération…' : 'Télécharger .html' }}
                  </button>
                </div>

                <!-- Résumé contenu -->
                <div class="rwm-export-summary">
                  <div class="rwm-export-sum-title">Contenu du rapport</div>
                  <div class="rwm-export-sum-grid">
                    <div class="rwm-sum-item rwm-sum-item--lock">
                      <i class="ti ti-lock"></i>
                      <div>
                        <div class="rwm-sum-k">Données verrouillées (source BD)</div>
                        <ul class="rwm-sum-list">
                          <li>Page de couverture (mission, équipe, dates)</li>
                          <li>Statistiques des constats</li>
                          <li>Résumé des constats par objectif (1.2)</li>
                          <li>Plan d'actions généré automatiquement (1.3)</li>
                          <li>Tableau de maîtrise des risques — Section 2</li>
                          <li>Annexes (objectifs, critères, équipe)</li>
                        </ul>
                      </div>
                    </div>
                    <div class="rwm-sum-item rwm-sum-item--edit">
                      <i class="ti ti-pencil"></i>
                      <div>
                        <div class="rwm-sum-k">Zones modifiables</div>
                        <ul class="rwm-sum-list">
                          <li v-for="f in editableFieldDefs" :key="f.key">{{ f.label }}</li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </template>

          </div>

          <!-- ── FOOTER ── -->
          <div class="rwm-footer">
            <div class="rwm-footer__left">
              <span v-if="lastSaved" class="rwm-saved"><i class="ti ti-check"></i> Sauvegardé {{ lastSaved }}</span>
            </div>
            <div class="rwm-footer__right">
              <button class="rwm-btn rwm-btn--ghost" @click="close">Fermer</button>
              <button class="rwm-btn rwm-btn--save" :disabled="!dirty || saving" @click="saveEdits">
                <span v-if="saving" class="rwm-spin"></span>
                <i v-else class="ti ti-device-floppy"></i>
                {{ saving ? 'Sauvegarde…' : 'Sauvegarder' }}
              </button>
              <button class="rwm-btn rwm-btn--html" :disabled="generatingHtml" @click="downloadHtml">
                <i class="ti ti-file-code"></i> HTML
              </button>
              <button class="rwm-btn rwm-btn--dl" :disabled="generatingWord" @click="downloadWord">
                <i class="ti ti-file-word"></i> Word
              </button>
            </div>
          </div>

        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'

const props = defineProps<{
  show:        boolean
  missionId:   number | string
  urlData:     string      // POST → charge les données JSON
  urlDownload: string      // POST → retourne le .docx binaire
  urlHtml:     string      // POST → retourne le .html
  urlSave?:    string      // PUT  → sauvegarde les champs en base
}>()

const emit = defineEmits<{
  (e: 'update:show', val: boolean): void
}>()

type Step = 'loading' | 'ready' | 'error'
const currentStep    = ref<Step>('loading')
const activeTab      = ref<'apercu' | 'edit' | 'export'>('apercu')
const errorMsg       = ref('')
const reportData     = ref<any>(null)
const dirty          = ref(false)
const saving         = ref(false)
const generatingWord = ref(false)
const generatingHtml = ref(false)
const lastSaved      = ref<string | null>(null)

const editableFields = reactive<Record<string, string>>({
  opinion:       '',
  points_forts:  '',
  normes:        "L'audit a été conduit conformément aux Normes Internationales pour la Pratique Professionnelle de l'Audit Interne (IIA).",
  limites:       '',
  observations:  '',
  difficultes:   '',
})

const editableFieldDefs = [
  { key: 'opinion',      label: '1.1 — Opinion Générale',                     section: 'Section 1', rows: 4, placeholder: "Synthèse de l'opinion globale sur le dispositif de contrôle interne audité…" },
  { key: 'points_forts', label: '1.4 — Résumé des Points Forts',              section: 'Section 1', rows: 4, placeholder: "Existence d'une charte éthique formalisée… Architecture redondante…" },
  { key: 'normes',       label: "1.5 — Énoncé des Normes d'Audit",            section: 'Section 1', rows: 3, placeholder: "L'audit a été conduit conformément aux Normes IIA…" },
  { key: 'limites',      label: "1.6 — Limites de l'Audit",                   section: 'Section 1', rows: 3, placeholder: "Impossibilité de s'assurer que toutes les interactions…" },
  { key: 'observations', label: '1.7 — Observations de la Structure Auditée', section: 'Section 1', rows: 3, placeholder: "L'encadrement est globalement en accord avec les livrables…" },
  { key: 'difficultes',  label: '1.8 — Difficultés Rencontrées',              section: 'Section 1', rows: 3, placeholder: "Indisponibilité de certains responsables…" },
]

const freeTextFields = editableFieldDefs.filter(f => f.key !== 'opinion')
const editableCount  = computed(() => editableFieldDefs.filter(f => editableFields[f.key]?.trim()).length)

const coverMeta = computed(() => {
  const m = reportData.value?.mission
  if (!m) return {}
  const fmt = (d: string) => d ? new Date(d).toLocaleDateString('fr-FR') : '—'
  return {
    'N° Rapport':     `RAP-${m.id}-${new Date().getFullYear()}`,
    'N° FPM':         m.numero_fpm ?? '—',
    "Dates d'audit":  `${fmt(m.date_debut)} → ${fmt(m.date_fin)}`,
    'Lieu(x)':        m.lieux ?? '—',
    'Version':        'Définitive',
  }
})

function csrf(): string {
  return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? ''
}
function timeNow(): string {
  return new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}
function badgeLabel(imp?: string): string {
  return { critique: 'Critique', haute: 'Significatif', moyenne: 'Peu sig.', basse: 'Maintenance' }[imp ?? 'basse'] ?? 'Maintenance'
}
function getConstatsForObj(objNum: string): any[] {
  return (reportData.value?.constats ?? []).filter((c: any) => c.obj_num === objNum)
}
function goToEdit(key: string): void {
  activeTab.value = 'edit'
  setTimeout(() => {
    document.getElementById(`rwm-field-${key}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' })
  }, 80)
}
function onFieldInput(): void { dirty.value = true }

async function apiFetch(url: string, options: RequestInit = {}): Promise<any> {
  const res = await fetch(url, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrf(),
      ...(options.headers ?? {}),
    },
  })
  const json = await res.json()
  if (!res.ok) throw new Error(json?.error ?? json?.message ?? `HTTP ${res.status}`)
  return json
}

async function generate(): Promise<void> {
  currentStep.value = 'loading'
  errorMsg.value = ''
  try {
    const data = await apiFetch(props.urlData, { method: 'POST', body: '{}' })
    reportData.value = data.data ?? data
    if (reportData.value?.opinion?.description && !editableFields.opinion)
      editableFields.opinion = reportData.value.opinion.description
    if (reportData.value?.pointsForts?.length && !editableFields.points_forts)
      editableFields.points_forts = reportData.value.pointsForts.map((f: string) => `• ${f}`).join('\n')
    currentStep.value = 'ready'
  } catch (err: any) {
    errorMsg.value = err.message ?? 'Erreur inconnue'
    currentStep.value = 'error'
  }
}

async function saveEdits(): Promise<void> {
  if (!props.urlSave) return
  saving.value = true
  try {
    await apiFetch(props.urlSave, { method: 'PUT', body: JSON.stringify({ editable_fields: editableFields }) })
    dirty.value = false
    lastSaved.value = timeNow()
  } catch (err: any) {
    alert('Erreur sauvegarde : ' + err.message)
  } finally {
    saving.value = false
  }
}

// ── Télécharger Word ──────────────────────────────────────────────
async function downloadWord(): Promise<void> {
  generatingWord.value = true
  try {
    const res = await fetch(props.urlDownload, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ editable_fields: editableFields }),
    })
    if (!res.ok) {
      const json = await res.json().catch(() => ({}))
      throw new Error(json?.error ?? `HTTP ${res.status}`)
    }
    const blob = await res.blob()
    triggerDownload(blob, `rapport_audit_mission_${props.missionId}.docx`)
  } catch (err: any) {
    alert('Erreur téléchargement Word : ' + err.message)
  } finally {
    generatingWord.value = false
  }
}

// ── Télécharger HTML ──────────────────────────────────────────────
async function downloadHtml(): Promise<void> {
  generatingHtml.value = true
  try {
    const res = await fetch(props.urlHtml, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ editable_fields: editableFields }),
    })
    if (!res.ok) {
      const json = await res.json().catch(() => ({}))
      throw new Error(json?.error ?? `HTTP ${res.status}`)
    }
    const blob = await res.blob()
    triggerDownload(blob, `rapport_audit_mission_${props.missionId}.html`)
  } catch (err: any) {
    alert('Erreur téléchargement HTML : ' + err.message)
  } finally {
    generatingHtml.value = false
  }
}

function triggerDownload(blob: Blob, filename: string): void {
  const url = URL.createObjectURL(blob)
  const a   = document.createElement('a')
  a.href = url; a.download = filename; a.click()
  URL.revokeObjectURL(url)
}

function close(): void {
  if (dirty.value && !confirm('Des modifications non sauvegardées seront perdues. Fermer quand même ?')) return
  emit('update:show', false)
}

watch(() => props.show, (val) => {
  if (val) {
    activeTab.value   = 'apercu'
    currentStep.value = 'loading'
    dirty.value       = false
    lastSaved.value   = null
    generate()
  }
})
</script>

<style scoped>
.rwm-backdrop {
  position:fixed;inset:0;background:rgba(15,23,42,.6);backdrop-filter:blur(4px);
  display:flex;align-items:center;justify-content:center;z-index:1060;padding:1rem;
}
.rwm-modal {
  background:#fff;border-radius:14px;width:100%;max-width:1000px;height:92vh;
  display:flex;flex-direction:column;box-shadow:0 25px 60px rgba(15,23,42,.25);overflow:hidden;
}
.rwm-header{display:flex;align-items:center;justify-content:space-between;padding:.7rem 1rem;border-bottom:1px solid #e2e8f0;background:#f8fafc;flex-shrink:0}
.rwm-header__left{display:flex;align-items:center;gap:.6rem}
.rwm-header__right{display:flex;align-items:center;gap:.5rem}
.rwm-file-ico{width:36px;height:36px;background:#dbeafe;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#1d4ed8}
.rwm-title{font-size:.78rem;font-weight:700;color:#0f172a}
.rwm-sub{font-size:.65rem;color:#64748b}
.rwm-dirty{display:inline-flex;align-items:center;gap:.2rem;background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:20px;padding:2px 8px;font-size:.62rem;font-weight:600}
.rwm-ico-btn{background:none;border:1px solid #e2e8f0;border-radius:6px;width:28px;height:28px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#64748b;font-size:.85rem}
.rwm-ico-btn:hover{background:#f1f5f9}

.rwm-tabs{display:flex;border-bottom:1px solid #e2e8f0;background:#f8fafc;flex-shrink:0}
.rwm-tab{display:inline-flex;align-items:center;gap:.2rem;padding:.35rem .85rem;font-size:.7rem;font-weight:600;color:#64748b;border:none;background:none;border-bottom:2px solid transparent;cursor:pointer}
.rwm-tab:hover{color:#1e40af;background:#eff6ff}
.rwm-tab.on{color:#1e40af;border-bottom-color:#1e40af;background:#fff}
.rwm-tab-badge{background:#1e40af;color:#fff;padding:0 5px;border-radius:10px;font-size:.56rem;line-height:1.4}

.rwm-body{flex:1;overflow:auto;background:#f1f5f9;display:flex;flex-direction:column}

.rwm-center{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.6rem;color:#64748b}
.rwm-center--err{color:#dc2626}
.rwm-center--err i{font-size:2rem}
.rwm-spinner{width:38px;height:38px;border:3px solid #e2e8f0;border-top-color:#1e40af;border-radius:50%;animation:rwm-spin .7s linear infinite}
@keyframes rwm-spin{to{transform:rotate(360deg)}}
.rwm-load-title{font-size:.8rem;font-weight:600;color:#0f172a}
.rwm-load-sub{font-size:.65rem;color:#94a3b8}

/* Aperçu */
.rwm-preview-wrap{flex:1;display:flex;justify-content:center;padding:1.2rem .8rem;overflow-y:auto}
.rwm-page{background:#fff;width:100%;max-width:720px;min-height:100%;border-radius:6px;box-shadow:0 1px 8px rgba(15,23,42,.08);padding:1.8rem 2rem;border:1px solid #e2e8f0;display:flex;flex-direction:column;gap:.8rem}
.rwm-section-hd{display:inline-flex;align-items:center;gap:.3rem;padding:2px 8px;border-radius:4px;font-size:.6rem;font-weight:700;letter-spacing:.04em}
.rwm-locked{background:#f1f5f9;color:#64748b}
.rwm-editable-hd{background:#dbeafe;color:#1d4ed8;cursor:pointer}
.rwm-cover{background:#f0f4f8;border-radius:6px;padding:1rem 1.2rem}
.rwm-cover-title{font-size:1.2rem;font-weight:800;color:#1a3a5c;text-align:center;margin-bottom:.8rem}
.rwm-cover-meta{display:grid;grid-template-columns:1fr 1fr;gap:.3rem;margin-bottom:.6rem}
.rwm-cover-row{display:flex;flex-direction:column}
.rwm-cover-k{font-size:.56rem;font-weight:700;color:#888;text-transform:uppercase}
.rwm-cover-v{font-size:.72rem;color:#0f172a}
.rwm-mission-box{background:#fff;border-radius:4px;padding:.4rem .6rem;margin-bottom:.5rem}
.rwm-mission-lbl{font-size:.56rem;color:#888;text-transform:uppercase;display:block;margin-bottom:.15rem}
.rwm-mission-val{font-size:.82rem;font-weight:700;color:#1a3a5c}
.rwm-conf{background:#fff3ee;border-radius:4px;padding:.3rem .5rem;font-size:.6rem;color:#7a2800;font-style:italic}
.rwm-editable-zone{border:1.5px dashed #93c5fd;border-radius:6px;padding:.6rem .8rem;background:#f0f7ff;cursor:pointer;transition:background .15s}
.rwm-editable-zone:hover{background:#e0f2fe}
.rwm-editable-zone--sm{padding:.4rem .6rem}
.rwm-ezone-hint{font-size:.58rem;color:#1d4ed8;font-weight:600;margin-bottom:.2rem}
.rwm-ezone-txt{font-size:.7rem;color:#475569;line-height:1.5;white-space:pre-line}
.rwm-opinion-badge{display:inline-block;background:#dbeafe;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:4px;padding:1px 8px;font-size:.65rem;font-weight:700;margin-bottom:.3rem}
.rwm-opinion-txt{font-size:.7rem;color:#374151;line-height:1.5}
.rwm-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:.4rem}
.rwm-stat{border:1px solid #e2e8f0;border-radius:5px;padding:.4rem;text-align:center}
.rwm-stat-n{font-size:1.4rem;font-weight:800;line-height:1}
.rwm-stat-l{font-size:.58rem;color:#777}
.rwm-stat--red .rwm-stat-n{color:#8b1a1a}
.rwm-stat--orange .rwm-stat-n{color:#7a4a0a}
.rwm-stat--green .rwm-stat-n{color:#0f5a3a}
.rwm-stat--blue .rwm-stat-n{color:#1a4a8a}
.rwm-obj-preview{background:#fafafa;border-radius:5px;padding:.4rem .6rem}
.rwm-obj-title{font-size:.68rem;font-weight:700;color:#1a3a5c;margin-bottom:.25rem}
.rwm-constat-line{display:flex;align-items:flex-start;gap:.4rem;padding:.2rem 0;border-bottom:1px solid #f0f0f0}
.rwm-frap-ref{font-family:monospace;font-size:.6rem;color:#888;white-space:nowrap;flex-shrink:0}
.rwm-frap-txt{flex:1;font-size:.67rem;color:#333}
.rwm-badge{font-size:.58rem;font-weight:600;padding:1px 5px;border-radius:3px;white-space:nowrap;flex-shrink:0}
.rwm-badge--critique{background:#fce8e8;color:#8b1a1a}
.rwm-badge--haute{background:#fef3e2;color:#7a4a0a}
.rwm-badge--moyenne{background:#e6f4ee;color:#0f5a3a}
.rwm-badge--basse{background:#e8f0fb;color:#1a4a8a}

/* Éditeur */
.rwm-edit-wrap{flex:1;padding:1rem;display:flex;flex-direction:column;gap:.6rem;overflow-y:auto}
.rwm-edit-legend{display:flex;align-items:center;gap:.4rem;background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:.4rem .7rem;font-size:.67rem;color:#1d4ed8}
.rwm-field-group{background:#fff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden}
.rwm-field-header{display:flex;align-items:center;gap:.3rem;padding:.4rem .7rem;background:#eff6ff;border-bottom:1px solid #bfdbfe}
.rwm-field-header i{color:#1d4ed8;font-size:.8rem}
.rwm-field-label{font-size:.7rem;font-weight:700;color:#0f172a;flex:1}
.rwm-field-section{font-size:.58rem;color:#64748b;background:#f1f5f9;padding:1px 6px;border-radius:10px}
.rwm-field-input{width:100%;border:none;outline:none;resize:vertical;padding:.5rem .7rem;font-size:.73rem;font-family:inherit;color:#1e293b;line-height:1.55;background:#fff}
.rwm-field-input:focus{background:#f0f7ff}

/* Export */
.rwm-export-wrap{flex:1;padding:1.2rem;display:flex;flex-direction:column;gap:.8rem;overflow-y:auto}
.rwm-export-card{display:flex;align-items:center;gap:.8rem;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:.8rem 1rem}
.rwm-export-ico{width:42px;height:42px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0}
.rwm-export-title{font-size:.75rem;font-weight:700;color:#0f172a}
.rwm-export-sub{font-size:.62rem;color:#64748b}
.rwm-export-summary{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:.8rem 1rem}
.rwm-export-sum-title{font-size:.68rem;font-weight:700;color:#0f172a;margin-bottom:.5rem}
.rwm-export-sum-grid{display:grid;grid-template-columns:1fr 1fr;gap:.6rem}
.rwm-sum-item{display:flex;gap:.4rem;font-size:.65rem}
.rwm-sum-item--lock i{color:#64748b}
.rwm-sum-item--edit i{color:#1d4ed8}
.rwm-sum-k{font-weight:700;color:#374151;margin-bottom:.2rem}
.rwm-sum-list{margin:0;padding-left:1rem;color:#64748b}
.rwm-sum-list li{margin-bottom:.1rem}

/* Footer */
.rwm-footer{display:flex;align-items:center;justify-content:space-between;padding:.7rem 1rem;border-top:1px solid #e2e8f0;background:#f8fafc;flex-shrink:0}
.rwm-footer__right{display:flex;gap:.4rem}
.rwm-saved{font-size:.65rem;color:#15803d;display:inline-flex;align-items:center;gap:.2rem}
.rwm-btn{display:inline-flex;align-items:center;gap:.25rem;padding:6px 14px;border:none;border-radius:6px;font-size:.73rem;font-weight:600;cursor:pointer}
.rwm-btn:disabled{opacity:.5;cursor:default}
.rwm-btn--ghost{background:#f1f5f9;color:#475569;border:1px solid #e2e8f0}
.rwm-btn--ghost:hover{background:#e2e8f0}
.rwm-btn--save{background:#1e40af;color:#fff}
.rwm-btn--save:hover:not(:disabled){background:#1d3a8a}
.rwm-btn--dl{background:#1d4ed8;color:#fff}
.rwm-btn--dl:hover:not(:disabled){background:#1e3a8a}
.rwm-btn--html{background:#065f46;color:#fff}
.rwm-btn--html:hover:not(:disabled){background:#064e3b}
.rwm-spin{width:12px;height:12px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:rwm-spin .7s linear infinite;display:inline-block}

.rwm-fade-enter-active,.rwm-fade-leave-active{transition:all .2s ease}
.rwm-fade-enter-from,.rwm-fade-leave-to{opacity:0;transform:scale(.97)}

@media(max-width:640px){
  .rwm-modal{height:95vh;border-radius:10px 10px 0 0;margin-top:auto}
  .rwm-export-sum-grid{grid-template-columns:1fr}
  .rwm-stats{grid-template-columns:1fr 1fr}
}
</style>