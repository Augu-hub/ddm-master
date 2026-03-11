<template>
  <div class="mp-wrapper">

    <!-- ═══════════════════════════════════════════════════════
         EN-TÊTE GLOBAL
    ════════════════════════════════════════════════════════ -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
      <div class="d-flex align-items-center gap-3">
        <div class="mp-header-icon">
          <i class="ti ti-sitemap fs-20"></i>
        </div>
        <div>
          <h4 class="mb-0 fw-bold text-dark">Formulaires d'Audit</h4>
          <p class="text-muted mb-0 fs-12">
            {{ totalForms }} formulaire(s) répartis sur
            {{ groupedData.length }} type(s) de mission · {{ totalPhases }} phase(s)
          </p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <b-button variant="soft-primary" size="sm" @click="expandAll">
          <i class="ti ti-arrows-maximize me-1"></i> Tout ouvrir
        </b-button>
        <b-button variant="soft-secondary" size="sm" @click="collapseAll">
          <i class="ti ti-arrows-minimize me-1"></i> Tout fermer
        </b-button>
      </div>
    </div>

    <!-- ── Aucune donnée ──────────────────────────────────── -->
    <b-card v-if="groupedData.length === 0" no-body class="text-center py-5">
      <b-card-body>
        <i class="ti ti-inbox fs-48 text-muted d-block mb-3" style="opacity:.4"></i>
        <h5 class="text-muted fw-semibold">Aucune donnée disponible</h5>
        <p class="text-muted fs-13 mb-0">
          Vérifiez que les menus ont bien été construits lors de la connexion.<br>
          <small class="text-danger" v-if="debugInfo">{{ debugInfo }}</small>
        </p>
      </b-card-body>
    </b-card>

    <!-- ═══════════════════════════════════════════════════════
         GROUPES PAR TYPE DE MISSION
         Structure attendue depuis session (buildUserMenus) :
         [
           {
             mission_type: { id, code, label, audit_type_code, color, icon },
             phases: [
               { phase_num, phase_label, forms: [ { id, code, label, url_path, icon, children: [...] } ] }
             ]
           }
         ]
    ════════════════════════════════════════════════════════ -->
    <div v-else class="d-flex flex-column gap-4">
      <div
        v-for="(group, gi) in groupedData"
        :key="group.missionType?.id ?? gi"
        class="mp-type-block"
      >
        <!-- ── Titre du type de mission ──────────────────── -->
        <div class="mp-type-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
          <div class="d-flex align-items-center gap-3">
            <div class="mp-type-badge" :style="{ background: group.color }">
              <i :class="group.icon || 'ti ti-clipboard-list'" class="fs-14"></i>
            </div>
            <div>
              <div class="d-flex align-items-center gap-2">
                <h5 class="mb-0 fw-bold text-dark">{{ group.label }}</h5>
                <span class="badge fs-10 px-2" :style="{ background: group.color + '22', color: group.color }">
                  {{ group.code }}
                </span>
                <span v-if="group.auditTypeCode" class="badge fs-10 px-2 bg-light text-muted">
                  → {{ group.auditTypeCode }}
                </span>
              </div>
              <p class="text-muted fs-12 mb-0">
                {{ group.phases.length }} phase(s) ·
                {{ countGroupForms(group) }} formulaire(s)
              </p>
            </div>
          </div>
          <!-- Pills navigation rapide vers chaque phase -->
          <div class="d-flex align-items-center gap-1 flex-wrap">
            <b-button
              v-for="ph in group.phases"
              :key="phaseKey(group, ph)"
              variant="null"
              size="sm"
              class="btn-phase-pill"
              :style="{
                background: phaseColor(ph.phase_num, group.color) + '18',
                color:      phaseColor(ph.phase_num, group.color),
                border:     '1px solid ' + phaseColor(ph.phase_num, group.color) + '44'
              }"
              @click="scrollToPhase(phaseKey(group, ph))"
            >
              {{ ph.phase_label }}
            </b-button>
          </div>
        </div>

        <!-- ── Phases du groupe ──────────────────────────── -->
        <div
          class="d-flex flex-column gap-3 ms-2 ps-3 mp-phases-indent"
          :style="{ '--type-color': group.color }"
        >
          <b-card
            v-for="ph in group.phases"
            :key="phaseKey(group, ph)"
            :id="'phase-' + phaseKey(group, ph)"
            no-body
            class="mp-phase-card"
          >
            <!-- En-tête de phase (cliquable) -->
            <div
              class="mp-phase-header d-flex align-items-center justify-content-between flex-wrap gap-2 px-4 py-3"
              :style="{ borderLeftColor: phaseColor(ph.phase_num, group.color) }"
              @click="togglePhase(phaseKey(group, ph))"
              style="cursor:pointer"
            >
              <div class="d-flex align-items-center gap-3">
                <div
                  class="mp-phase-icon-wrap"
                  :style="{
                    background: phaseColor(ph.phase_num, group.color) + '18',
                    color:      phaseColor(ph.phase_num, group.color)
                  }"
                >
                  <i :class="phaseIcon(ph.phase_num)" class="fs-16"></i>
                </div>
                <div>
                  <div class="d-flex align-items-center gap-2">
                    <h6 class="mb-0 fw-bold text-dark">{{ ph.phase_label }}</h6>
                    <span class="text-muted fs-11 font-monospace">Phase {{ ph.phase_num }}</span>
                  </div>
                  <p class="text-muted fs-11 mb-0">{{ (ph.forms || []).length }} formulaire(s)</p>
                </div>
              </div>
              <div class="d-flex align-items-center gap-2">
                <span
                  class="badge rounded-pill px-3 py-1 fs-11"
                  :style="{
                    background: phaseColor(ph.phase_num, group.color) + '18',
                    color:      phaseColor(ph.phase_num, group.color)
                  }"
                >
                  P{{ ph.phase_num }}
                </span>
                <i
                  :class="expandedPhases[phaseKey(group, ph)] !== false
                    ? 'ti ti-chevron-up' : 'ti ti-chevron-down'"
                  class="text-muted fs-14"
                ></i>
              </div>
            </div>

            <!-- Corps : formulaires -->
            <b-collapse :visible="expandedPhases[phaseKey(group, ph)] !== false">

              <div v-if="!ph.forms || ph.forms.length === 0"
                class="px-4 py-3 text-muted text-center fs-12 bg-light">
                <i class="ti ti-alert-circle me-1"></i> Aucun formulaire pour cette phase.
              </div>

              <div v-else class="table-responsive">
                <table class="table table-hover table-nowrap mb-0 align-middle mp-table">
                  <thead class="bg-light bg-opacity-75">
                    <tr class="text-uppercase fs-10 text-muted">
                      <th class="ps-4" style="width:40px">#</th>
                      <th>Formulaire</th>
                      <th>Chemin</th>
                      <th class="text-center" style="width:110px">Sous-menus</th>
                      <th class="text-end pe-4" style="width:110px">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <template v-for="(form, fi) in ph.forms" :key="form.id">

                      <!-- ── Ligne formulaire principal ─── -->
                      <tr class="mp-form-row">
                        <td class="ps-4 text-muted fs-11">{{ fi + 1 }}</td>
                        <td>
                          <div class="d-flex align-items-center gap-2">
                            <div class="mp-form-icon-wrap">
                              <i :class="form.icon || 'ti ti-file'" class="fs-13 text-muted"></i>
                            </div>
                            <div>
                              <div class="fw-semibold text-dark fs-13">{{ form.label }}</div>
                              <div v-if="form.children?.length" class="fs-10 text-muted">
                                {{ form.children.length }} sous-menu(s)
                              </div>
                            </div>
                          </div>
                        </td>
                        <td>
                          <code class="text-muted fs-11">{{ form.url_path || '—' }}</code>
                        </td>
                        <td class="text-center">
                          <b-button
                            v-if="form.children?.length"
                            variant="null"
                            size="sm"
                            class="btn btn-sm btn-soft-info px-2 py-1 fs-11"
                            @click.stop="toggleFormChildren(form.id)"
                          >
                            <i :class="expandedForms[form.id] ? 'ti ti-eye-off' : 'ti ti-eye'" class="me-1"></i>
                            {{ form.children.length }}
                          </b-button>
                          <span v-else class="text-muted fs-11">—</span>
                        </td>
                        <td class="text-end pe-4">
                          <div class="d-flex align-items-center justify-content-end gap-1">
                            <b-button
                              v-if="form.url_path"
                              variant="null"
                              size="sm"
                              class="btn btn-sm btn-soft-primary px-2 py-1"
                              v-b-tooltip.top="'Aperçu'"
                              @click.stop="openPreview({ label: form.label, url: form.url_path, icon: form.icon, type: 'Formulaire' })"
                            >
                              <i class="ti ti-eye fs-13"></i>
                            </b-button>
                            <a
                              v-if="form.url_path"
                              :href="buildUrl(form.url_path)"
                              target="_blank"
                              class="btn btn-sm btn-soft-secondary px-2 py-1"
                              v-b-tooltip.top="'Nouvel onglet'"
                              @click.stop
                            >
                              <i class="ti ti-external-link fs-13"></i>
                            </a>
                            <span v-if="!form.url_path" class="text-muted fs-11">—</span>
                          </div>
                        </td>
                      </tr>

                      <!-- ── Sous-formulaires (children) ─── -->
                      <tr
                        v-for="child in (form.children || [])"
                        v-show="expandedForms[form.id]"
                        :key="child.id"
                        class="mp-child-row"
                      >
                        <td class="ps-4"></td>
                        <td>
                          <div class="d-flex align-items-center gap-2 ms-4">
                            <i class="ti ti-corner-down-right text-muted fs-11"></i>
                            <div class="mp-child-icon-wrap">
                              <i :class="child.icon || 'ti ti-file-text'" class="fs-11 text-muted"></i>
                            </div>
                            <span class="text-dark fs-12">{{ child.label }}</span>
                          </div>
                        </td>
                        <td>
                          <code class="text-muted fs-10">{{ child.url_path || '—' }}</code>
                        </td>
                        <td class="text-center">
                          <span class="badge bg-warning-subtle text-warning fs-10 px-2">Sous-menu</span>
                        </td>
                        <td class="text-end pe-4">
                          <div class="d-flex align-items-center justify-content-end gap-1">
                            <b-button
                              v-if="child.url_path"
                              variant="null"
                              size="sm"
                              class="btn btn-sm btn-soft-primary px-2 py-1"
                              v-b-tooltip.top="'Aperçu'"
                              @click="openPreview({ label: child.label, url: child.url_path, icon: child.icon, type: 'Sous-menu' })"
                            >
                              <i class="ti ti-eye fs-12"></i>
                            </b-button>
                            <a
                              v-if="child.url_path"
                              :href="buildUrl(child.url_path)"
                              target="_blank"
                              class="btn btn-sm btn-soft-secondary px-2 py-1"
                              v-b-tooltip.top="'Nouvel onglet'"
                            >
                              <i class="ti ti-external-link fs-12"></i>
                            </a>
                          </div>
                        </td>
                      </tr>

                    </template>
                  </tbody>
                </table>
              </div>
            </b-collapse>
          </b-card>
        </div>
        <!-- /phases du groupe -->
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         MODAL APERÇU IFRAME
    ════════════════════════════════════════════════════════ -->
    <b-modal
      v-model="previewModal.show"
      size="xl"
      body-class="p-0"
      header-class="mp-modal-header"
      footer-class="mp-modal-footer"
      dialog-class="mp-preview-dialog"
      scrollable
      hide-title
    >
      <template #header>
        <div class="d-flex align-items-center gap-3 w-100">
          <div class="mp-modal-icon">
            <i :class="previewModal.icon || 'ti ti-layout'" class="fs-16"></i>
          </div>
          <div class="flex-grow-1 min-w-0">
            <div class="fw-bold text-dark fs-14 text-truncate">{{ previewModal.label }}</div>
            <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
              <span class="badge bg-primary-subtle text-primary fs-10">{{ previewModal.type }}</span>
              <code class="text-muted fs-10 text-truncate" style="max-width:300px">{{ previewModal.url }}</code>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <a :href="buildUrl(previewModal.url)" target="_blank" class="btn btn-sm btn-soft-primary">
              <i class="ti ti-external-link me-1"></i> Ouvrir
            </a>
            <b-button variant="null" class="btn btn-sm btn-soft-secondary" @click="previewModal.show = false">
              <i class="ti ti-x fs-14"></i>
            </b-button>
          </div>
        </div>
      </template>

      <div class="mp-iframe-wrap">
        <div v-if="previewModal.loading" class="mp-iframe-loader">
          <div class="spinner-border text-primary" style="width:2rem;height:2rem" role="status"></div>
          <p class="text-muted mt-3 fs-13 mb-0">Chargement de l'aperçu…</p>
        </div>
        <iframe
          v-if="previewModal.show"
          :src="buildUrl(previewModal.url)"
          class="mp-iframe"
          :class="{ 'opacity-0': previewModal.loading }"
          @load="previewModal.loading = false"
          @error="previewModal.loading = false"
          frameborder="0"
          sandbox="allow-same-origin allow-scripts allow-forms"
        ></iframe>
      </div>

      <template #footer>
        <div class="d-flex align-items-center justify-content-between w-100">
          <span class="text-muted fs-12">
            <i class="ti ti-info-circle me-1"></i>
            Aperçu en lecture seule.
          </span>
          <b-button size="sm" variant="secondary" @click="previewModal.show = false">Fermer</b-button>
        </div>
      </template>
    </b-modal>

  </div>
</template>

<script setup>
import { computed, reactive, ref, nextTick } from 'vue'
import { usePage } from '@inertiajs/vue3'

// ── URL de base ──────────────────────────────────────────────
const page    = usePage()
const baseUrl = computed(() =>
  page.props?.ziggy?.url ?? page.props?.appUrl ?? window.location.origin
)

function buildUrl(urlPath) {
  if (!urlPath) return '#'
  const path = urlPath.startsWith('/') ? urlPath : '/' + urlPath
  return baseUrl.value + path
}

// ── Lecture des menus depuis la session ──────────────────────
// buildUserMenus retourne maintenant :
// [
//   {
//     mission_type: { id, code, label, audit_type_code, audit_type_label, color, icon },
//     phases: [
//       { phase_num: 1, phase_label: 'Préparation', forms: [ { id, code, label, url_path, icon, children:[] } ] },
//       { phase_num: 2, phase_label: 'Réalisation', forms: [...] },
//       ...
//     ]
//   }
// ]
const rawMenus = page.props?.auth?.menus
              ?? page.props?.userMenus
              ?? []

// Couleurs de phase par numéro
const PHASE_COLORS = ['#1D4ED8','#059669','#D97706','#7C3AED','#DC2626']
const TYPE_COLORS  = ['#2E86AB','#1E8449','#D68910','#7D3C98','#B03A2E','#1A5276','#117A65','#784212']

function phaseColor(phaseNum, groupColor) {
  return PHASE_COLORS[(phaseNum - 1) % PHASE_COLORS.length] ?? groupColor ?? '#64748B'
}
function phaseIcon(phaseNum) {
  return [
    'ti ti-search',         // 1 Préparation
    'ti ti-tools',          // 2 Réalisation
    'ti ti-file-report',    // 3 Conclusion
    'ti ti-chart-arrows-vertical', // 4 Suivi
    'ti ti-bulb',           // 5 Recommandations
  ][(phaseNum - 1)] ?? 'ti ti-flag'
}

// ── Clé unique par phase (groupIndex_phaseNum) ───────────────
// Évite les collisions si plusieurs types ont même phase_num
function phaseKey(group, ph) {
  return `${group.missionType?.id ?? group.code}_${ph.phase_num}`
}

// ── Normalisation des menus ──────────────────────────────────
// Supporte DEUX formats possibles pour la rétrocompatibilité :
//
// FORMAT A (nouveau — AuthenticatedSessionController corrigé) :
//   { mission_type: {...}, phases: [ { phase_num, phase_label, forms: [...] } ] }
//
// FORMAT B (ancien — structure phase/forms plate) :
//   { phase: { id, code, label, color, icon, order }, forms: [...] }
//
const groupedData = computed(() => {
  if (!rawMenus || rawMenus.length === 0) return []

  // Détecter le format en regardant le premier élément
  const first = rawMenus[0]

  // ── FORMAT A : nouveau (mission_type + phases[]) ──────────
  if (first?.mission_type !== undefined || first?.phases !== undefined) {
    return rawMenus.map((item, gi) => {
      const mt = item.mission_type ?? {}
      return {
        missionType:   mt,
        code:          mt.code          ?? item.code          ?? `T${gi+1}`,
        label:         mt.label         ?? item.label          ?? `Type ${gi+1}`,
        auditTypeCode: mt.audit_type_code ?? null,
        color:         mt.color         ?? mt.audit_color      ?? TYPE_COLORS[gi % TYPE_COLORS.length],
        icon:          mt.icon          ?? mt.audit_icon        ?? 'ti ti-clipboard-list',
        order:         mt.sort_order    ?? gi,
        phases:        Array.isArray(item.phases) ? item.phases : [],
      }
    }).sort((a, b) => a.order - b.order)
  }

  // ── FORMAT B : ancien (phase + forms plats — groupement manuel) ─
  // On regroupe par mission_type si dispo, sinon tout dans un seul groupe
  const map = new Map()
  rawMenus.forEach((item, idx) => {
    const mt  = item.phase?.mission_type ?? null
    const key = mt?.id ?? '__ungrouped__'

    if (!map.has(key)) {
      map.set(key, {
        missionType: mt,
        code:    mt?.code  ?? '—',
        label:   mt?.label ?? 'Formulaires',
        auditTypeCode: null,
        color:   mt?.color ?? TYPE_COLORS[map.size % TYPE_COLORS.length],
        icon:    mt?.icon  ?? 'ti ti-clipboard-list',
        order:   mt?.sort_order ?? map.size,
        // Convertir au format A pour uniformité
        phases: [],
      })
    }

    const g = map.get(key)
    // Convertir l'item en phase synthétique
    const ph = item.phase ?? {}
    g.phases.push({
      phase_num:   ph.order ?? idx + 1,
      phase_label: ph.label ?? ph.code ?? `Phase ${idx+1}`,
      _phaseId:    ph.id,   // conservé pour compatibilité scroll
      forms:       Array.isArray(item.forms) ? item.forms : [],
    })
  })

  return Array.from(map.values()).sort((a, b) => a.order - b.order)
})

// ── Compteurs ────────────────────────────────────────────────
const totalPhases = computed(() =>
  groupedData.value.reduce((s, g) => s + g.phases.length, 0)
)
const totalForms = computed(() =>
  groupedData.value.reduce((s, g) => s + countGroupForms(g), 0)
)
function countGroupForms(group) {
  return group.phases.reduce((s, ph) => s + (ph.forms?.length ?? 0), 0)
}

// ── Debug info (affiché si vide) ─────────────────────────────
const debugInfo = computed(() => {
  if (!rawMenus) return 'rawMenus est null/undefined'
  if (!Array.isArray(rawMenus)) return `rawMenus n'est pas un tableau (type: ${typeof rawMenus})`
  if (rawMenus.length === 0) return 'rawMenus est un tableau vide'
  return null
})

// ── Accordéons phases ─────────────────────────────────────────
// Initialiser après que groupedData soit calculé
const expandedPhases = reactive({})
const expandedForms  = reactive({})

// Ouvrir toutes les phases au montage
function initExpanded() {
  groupedData.value.forEach(g => {
    g.phases.forEach(ph => {
      const key = phaseKey(g, ph)
      if (expandedPhases[key] === undefined) {
        expandedPhases[key] = true
      }
    })
  })
}
// Appel immédiat (setup synchrone)
initExpanded()

function togglePhase(key) { expandedPhases[key] = !expandedPhases[key] }
function toggleFormChildren(id) { expandedForms[id] = !expandedForms[id] }

function expandAll() {
  groupedData.value.forEach(g =>
    g.phases.forEach(ph => { expandedPhases[phaseKey(g, ph)] = true })
  )
}
function collapseAll() {
  groupedData.value.forEach(g =>
    g.phases.forEach(ph => { expandedPhases[phaseKey(g, ph)] = false })
  )
}

// ── Navigation scroll ─────────────────────────────────────────
function scrollToPhase(key) {
  expandedPhases[key] = true
  nextTick(() => {
    document.getElementById('phase-' + key)
      ?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  })
}

// ── Modal aperçu ─────────────────────────────────────────────
const previewModal = reactive({
  show: false, label: '', url: '', icon: '', type: '', loading: true,
})
function openPreview({ label, url, icon, type }) {
  Object.assign(previewModal, { label, url, icon: icon || '', type, loading: true, show: true })
}
</script>

<style scoped>
.mp-wrapper {
  padding: 24px;
  font-family: 'Segoe UI', system-ui, sans-serif;
}

/* Header icon */
.mp-header-icon {
  width: 44px; height: 44px; border-radius: 10px;
  background: linear-gradient(135deg, #2E86AB1A, #2E86AB33);
  color: #2E86AB;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}

/* Type block */
.mp-type-badge {
  width: 36px; height: 36px; border-radius: 8px;
  color: #fff; font-weight: 700;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(0,0,0,.18);
}

/* Ligne verticale colorée du groupe */
.mp-phases-indent {
  border-left: 3px solid var(--type-color, #dee2e6);
}

/* Pills navigation rapide */
.btn-phase-pill {
  font-size: 0.68rem; padding: 3px 11px;
  border-radius: 20px; font-weight: 600;
  transition: transform .15s, box-shadow .15s;
}
.btn-phase-pill:hover { transform: translateY(-1px); box-shadow: 0 3px 8px rgba(0,0,0,.12); }

/* Phase card */
.mp-phase-card {
  border: 1px solid #E9ECEF; border-radius: 10px;
  overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,.04);
  transition: box-shadow .2s;
}
.mp-phase-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.08); }

/* Phase header */
.mp-phase-header {
  border-left: 4px solid transparent;
  background: #F8F9FA; border-bottom: 1px solid #EAECF0;
  transition: background .15s;
}
.mp-phase-header:hover { background: #EFF2F7; }

.mp-phase-icon-wrap {
  width: 36px; height: 36px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}

/* Table */
.mp-table th { letter-spacing: .04em; padding-top: 9px; padding-bottom: 9px; }
.mp-form-row td { padding-top: 11px; padding-bottom: 11px; }
.mp-form-row:hover td { background: #F5F7FF; }

.mp-form-icon-wrap {
  width: 28px; height: 28px; border-radius: 6px;
  background: #F1F3F4;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}

/* Child rows */
.mp-child-row td {
  padding-top: 7px; padding-bottom: 7px;
  background: #FAFBFC; border-bottom: 1px dashed #EEF0F2;
}
.mp-child-icon-wrap {
  width: 20px; height: 20px; border-radius: 4px; background: #EAECEF;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}

/* Modal */
:deep(.mp-preview-dialog)  { max-width: 92vw !important; }
:deep(.mp-modal-header)    { padding: 14px 20px; border-bottom: 1px solid #EAECF0; background: #F8F9FA; }
:deep(.mp-modal-footer)    { padding: 10px 20px; border-top: 1px solid #EAECF0; background: #F8F9FA; }

.mp-modal-icon {
  width: 36px; height: 36px; border-radius: 8px;
  background: #2E86AB1A; color: #2E86AB;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}

/* Iframe */
.mp-iframe-wrap { position: relative; height: 78vh; background: #F8F9FA; }
.mp-iframe-loader {
  position: absolute; inset: 0; z-index: 2;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  background: #F8F9FA;
}
.mp-iframe {
  width: 100%; height: 100%; border: none; transition: opacity .3s;
}

/* Utilitaires */
.fs-10 { font-size: .625rem  !important; }
.fs-11 { font-size: .688rem  !important; }
.fs-12 { font-size: .75rem   !important; }
.fs-13 { font-size: .8125rem !important; }
.fs-14 { font-size: .875rem  !important; }
.fs-16 { font-size: 1rem     !important; }
.fs-20 { font-size: 1.25rem  !important; }
.fs-48 { font-size: 3rem     !important; }
.min-w-0 { min-width: 0; }
</style>