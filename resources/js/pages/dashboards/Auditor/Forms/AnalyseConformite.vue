<template>
  <VerticalLayoutAudit>
    <div class="qcc-shell">

      <!-- ══ HEADER ══════════════════════════════════════════════════ -->
      <header class="qci-header">
        <div class="qci-hrow">
          <a :href="props.backUrl ?? '#'" class="qci-back"><i class="ti ti-arrow-left"></i></a>
          <div class="qci-hinfo">
            <div class="qci-chips">
              <code class="qci-code">{{ mission?.code_mission ?? '—' }}</code>
              <span class="qci-chip" :class="`chip-${form.statut || 'brouillon'}`">
                <i :class="statutIcon(form.statut || 'brouillon')"></i>
                {{ statutLbl(form.statut || 'brouillon') }}
              </span>
              <span class="qci-chip chip-type">QCC</span>
              <span v-if="props.auditorRole" class="qci-chip" :class="`chip-role-${props.auditorRole}`">
                {{ props.auditorRole }}
              </span>
            </div>
            <h1 class="qci-title">Questionnaire de Contrôle de Conformité</h1>
            <div class="qci-meta">
              <span v-if="form.date_audit"><i class="ti ti-calendar"></i>{{ form.date_audit }}</span>
              <span v-if="mission?.entity_name"><i class="ti ti-building"></i>{{ mission.entity_name }}</span>
              <span v-if="scoreGlobal !== null">
                <i class="ti ti-chart-bar" :style="{ color: scoreColor(scoreGlobal) }"></i>
                Score : <strong :style="{ color: scoreColor(scoreGlobal) }">{{ scoreGlobal }}%</strong>
              </span>
            </div>
          </div>
          <div class="qci-hactions">
            <button class="qci-btn-ia" :disabled="iaGlobalLoading" @click="genererSyntheseGlobale" title="Synthèse IA globale">
              <span v-if="iaGlobalLoading" class="spin-dot spin-sm" style="border-top-color:#7c3aed"></span>
              <i v-else class="ti ti-robot"></i>
            </button>
          </div>
        </div>

        <!-- Bannières statut -->
        <div v-if="form.statut === 'valide'" class="qci-banner banner-lock">
          <i class="ti ti-lock"></i> QCC <strong>validé</strong> — lecture seule
        </div>
        <div v-else-if="form.statut === 'soumis'" class="qci-banner banner-review">
          <i class="ti ti-clock"></i> Soumis pour validation
          <span v-if="canManage"> · Vous pouvez valider.</span>
        </div>

        <!-- Synthèse IA globale (bandeau) -->
        <div v-if="iaSynthese" class="qcc-synthese-band">
          <div class="qcc-synthese-hd">
            <i class="ti ti-robot"></i>
            <strong>Synthèse IA</strong>
            <span class="qcc-maturite-badge" :class="`mat-${iaSynthese.niveau_maturite}`">
              {{ maturiteLbl(iaSynthese.niveau_maturite) }}
            </span>
            <button class="qcc-synthese-close" @click="iaSynthese = null"><i class="ti ti-x"></i></button>
          </div>
          <p class="qcc-synthese-txt">{{ iaSynthese.synthese_executive }}</p>
          <div class="qcc-synthese-cols">
            <div v-if="iaSynthese.points_forts?.length" class="qcc-syn-col">
              <div class="qcc-syn-col-hd syn-ok"><i class="ti ti-circle-check"></i> Points forts</div>
              <ul><li v-for="p in iaSynthese.points_forts" :key="p">{{ p }}</li></ul>
            </div>
            <div v-if="iaSynthese.axes_amelioration?.length" class="qcc-syn-col">
              <div class="qcc-syn-col-hd syn-warn"><i class="ti ti-alert-triangle"></i> Axes d'amélioration</div>
              <ul><li v-for="a in iaSynthese.axes_amelioration" :key="a">{{ a }}</li></ul>
            </div>
            <div v-if="iaSynthese.risques_critiques?.length" class="qcc-syn-col">
              <div class="qcc-syn-col-hd syn-err"><i class="ti ti-alert-octagon"></i> Risques critiques</div>
              <ul><li v-for="r in iaSynthese.risques_critiques" :key="r">{{ r }}</li></ul>
            </div>
          </div>
        </div>
      </header>

      <!-- ══ BODY ════════════════════════════════════════════════════ -->
      <div class="qci-body">
        <div class="qcc-grid">

          <!-- ── COL GAUCHE ── -->
          <div class="qci-col-left">

            <!-- Info QCC -->
            <section class="card">
              <div class="card-label"><i class="ti ti-clipboard-check"></i> Questionnaire QCC</div>
              <div class="card-body">
                <div class="fg">
                  <label class="flbl">Intitulé QCC <span class="req">*</span></label>
                  <input class="inp" v-model="form.intitule_qcc" :disabled="isLocked" placeholder="Ex: Audit conformité ADC 1…" />
                </div>
                <div class="form-row">
                  <div class="fg">
                    <label class="flbl">Date audit</label>
                    <input type="date" class="inp" v-model="form.date_audit" :disabled="isLocked" />
                  </div>
                  <div class="fg">
                    <label class="flbl">Auditeur</label>
                    <input class="inp inp-ro" :value="props.currentAuditor
                      ? `${props.currentAuditor.last_name ?? ''} ${props.currentAuditor.first_name ?? ''}`.trim()
                      : '—'" readonly />
                  </div>
                </div>
                <div class="fg">
                  <label class="flbl">Description</label>
                  <textarea class="inp inp-ta" v-model="form.description" :disabled="isLocked" rows="2" placeholder="Observations générales…"></textarea>
                </div>
              </div>
            </section>

            <!-- Score global -->
            <section class="card">
              <div class="card-label"><i class="ti ti-chart-pie"></i> Score de conformité</div>
              <div class="card-body" style="padding:14px">
                <div class="qcc-score-ring" v-if="scoreGlobal !== null">
                  <svg viewBox="0 0 80 80" width="80" height="80">
                    <circle cx="40" cy="40" r="32" fill="none" stroke="#e5e7eb" stroke-width="8"/>
                    <circle cx="40" cy="40" r="32" fill="none"
                      :stroke="scoreColor(scoreGlobal)"
                      stroke-width="8"
                      stroke-linecap="round"
                      :stroke-dasharray="`${scoreGlobal * 2.01} 201`"
                      stroke-dashoffset="50"
                      transform="rotate(-90 40 40)"
                    />
                    <text x="40" y="45" text-anchor="middle" font-size="13" font-weight="700" :fill="scoreColor(scoreGlobal)">{{ scoreGlobal }}%</text>
                  </svg>
                  <div class="qcc-score-detail">
                    <div class="qcc-score-line">
                      <span class="dot-ok"></span> Conformes : <strong>{{ stats.conformes }}</strong>
                    </div>
                    <div class="qcc-score-line">
                      <span class="dot-ko"></span> Non conformes : <strong>{{ stats.nonConformes }}</strong>
                    </div>
                    <div class="qcc-score-line">
                      <span class="dot-so"></span> Sans objet : <strong>{{ stats.sansObjet }}</strong>
                    </div>
                    <div class="qcc-score-line">
                      <span class="dot-nd"></span> Non évalués : <strong>{{ stats.nonEvalues }}</strong>
                    </div>
                  </div>
                </div>
                <div v-else class="qcc-score-empty">
                  <i class="ti ti-chart-bar"></i> Aucune réponse enregistrée
                </div>
              </div>
            </section>

            <!-- QCC enregistrés -->
            <section class="card">
              <div class="card-label">
                <i class="ti ti-list"></i> QCC enregistrés
                <span class="card-count">{{ props.accList?.length ?? 0 }}</span>
                <button v-if="!isLocked" class="btn-new-inline" @click="newQcc" title="Nouveau QCC">
                  <i class="ti ti-plus"></i>
                </button>
              </div>
              <div class="card-body" style="padding:0">
                <div v-if="!props.accList?.length" class="td-empty">Aucun questionnaire</div>
                <div v-for="q in props.accList" :key="q.id"
                  class="qci-list-row"
                  :class="{ active: form.id === q.id }"
                  @click="loadQcc(q)">
                  <div class="qlr-l">
                    <code class="qlr-code">{{ q.intitule_qcc || 'QCC-' + q.id }}</code>
                    <span class="qlr-title">{{ q.description || '—' }}</span>
                  </div>
                  <span class="qci-chip" :class="`chip-${q.statut || 'brouillon'}`" style="font-size:.58rem">
                    {{ statutLbl(q.statut || 'brouillon') }}
                  </span>
                </div>
              </div>
            </section>

          </div><!-- /col-left -->

          <!-- ── COL DROITE ── -->
          <div class="qci-col-right">

            <!-- Toolbar -->
            <div class="qci-toolbar">
              <div class="qtb-l">
                <i class="ti ti-table-check" style="color:#1565C0"></i>
                <span class="qtb-title">{{ form.intitule_qcc || 'Questionnaire sans titre' }}</span>
                <span class="qtb-count">{{ itemRows.length }} exigence(s)</span>
              </div>
              <div class="qtb-r">
                <!-- Import Excel -->
                <label v-if="!isLocked" class="btn btn-import" title="Importer depuis Excel">
                  <span v-if="importing" class="spin-dot spin-sm" style="border-top-color:#0369a1"></span>
                  <i v-else class="ti ti-upload"></i>
                  {{ importing ? 'Import…' : 'Importer Excel' }}
                  <input ref="xlsRef" type="file" accept=".xlsx,.xls" class="hidden"
                    @change="importExcel" :disabled="isLocked || importing" />
                </label>
                <!-- Export Excel -->
                <a v-if="form.id" :href="`${props.formUrl}/${form.id}/exporter`"
                  class="btn btn-tpl" title="Exporter en Excel">
                  <i class="ti ti-download"></i> Excel
                </a>
                <!-- Ajouter section -->
                <button v-if="!isLocked" class="btn btn-cat" @click="addSection">
                  <i class="ti ti-folder-plus"></i> Section
                </button>
                <!-- Ajouter ligne -->
                <button v-if="!isLocked" class="btn btn-item" @click="addItem">
                  <i class="ti ti-plus"></i> Ligne
                </button>
              </div>
            </div>

            <!-- Tableau QCC -->
            <div class="qci-table-wrap">
              <table class="qci-table">
                <thead>
                  <tr>
                    <th class="th-num">N°</th>
                    <th class="th-question">Réf. / Exigence Norme</th>
                    <th class="th-rep">O / N / SO</th>
                    <th class="th-forces">Forces</th>
                    <th class="th-faibl">Faiblesses</th>
                    <th class="th-obj">Objectif de contrôle</th>
                    <th class="th-ia">IA</th>
                    <th v-if="!isLocked" class="th-act"></th>
                  </tr>
                </thead>
                <tbody>

                  <!-- Empty state -->
                  <tr v-if="!rows.length">
                    <td :colspan="isLocked ? 7 : 8" class="qci-empty">
                      <i class="ti ti-clipboard-off"></i>
                      <p>Aucune exigence. Importez un fichier Excel ou ajoutez manuellement.</p>
                      <div v-if="!isLocked" class="empty-actions">
                        <label class="btn btn-import">
                          <i class="ti ti-upload"></i> Importer Excel
                          <input type="file" accept=".xlsx,.xls" class="hidden" @change="importExcel" />
                        </label>
                        <button class="btn btn-item" @click="addItem">
                          <i class="ti ti-plus"></i> Ajouter une ligne
                        </button>
                      </div>
                    </td>
                  </tr>

                  <template v-for="(row, idx) in rows" :key="row._id">

                    <!-- SECTION / PHASE -->
                    <tr v-if="row.type === 'section'" class="row-cat" @click="toggleSection(row._id)">
                      <td class="td-num">
                        <i class="ti" :class="collapsed.has(row._id) ? 'ti-chevron-right' : 'ti-chevron-down'"
                          style="font-size:.7rem;color:#6b7280"></i>
                      </td>
                      <td :colspan="isLocked ? 6 : 5" class="td-cat-label">
                        <div style="display:flex;align-items:center;gap:8px">
                          <code class="qcc-ref-badge">{{ row.ref || '—' }}</code>
                          <input v-if="!isLocked" class="inp-cat" v-model="row.label"
                            placeholder="Libellé de la section…" @click.stop />
                          <strong v-else>{{ row.label || '—' }}</strong>
                        </div>
                      </td>
                      <td v-if="!isLocked" class="td-act" @click.stop>
                        <button class="act-btn act-add" @click="addItemAfter(idx)" title="Ajouter une exigence">
                          <i class="ti ti-row-insert-bottom"></i>
                        </button>
                        <button class="act-btn act-del" @click="removeRow(idx)">
                          <i class="ti ti-trash"></i>
                        </button>
                      </td>
                    </tr>

                    <!-- LIGNE EXIGENCE -->
                    <tr v-else
                      class="row-item"
                      :class="{
                        'row-hidden': isHidden(idx),
                        'row-oui': row.reponse === 'c',
                        'row-non': row.reponse === 'Nc',
                        'row-so':  row.reponse === 'SO',
                      }">

                      <!-- N° -->
                      <td class="td-num">
                        <span class="item-num">{{ itemNumber(idx) }}</span>
                      </td>

                      <!-- Exigence -->
                      <td class="td-question">
                        <div class="qcc-exig-wrap">
                          <code v-if="row.ref" class="qcc-ref-sm">{{ row.ref }}</code>
                          <textarea v-if="!isLocked" class="inp-q" v-model="row.label"
                            rows="2" placeholder="Exigence normative…"></textarea>
                          <span v-else class="q-ro">{{ row.label || '—' }}</span>
                        </div>
                      </td>

                      <!-- O / N / SO -->
                     <td class="td-rep">
                   <div class="d-flex" style="gap:6px; background:red;">
                        <select v-if="!isLocked"
                        class="sel-rep"
                        :class="selClass(row.reponse)"
                        v-model="row.reponse"
                        @change="onReponseChange(row)">
                        <option value="">—</option>
                        <option value="O">O</option>
                        <option value="N">N</option>
                        <option value="SO">SO</option>
                        </select>

                        <span v-else class="rep-ro" :class="repRoClass(row.reponse)">
                        {{ row.reponse || '—' }}
                        </span>
                    </div>
                    </td>

                      <!-- Forces -->
                      <td class="td-forces">
                        <textarea v-if="!isLocked" class="inp-obs" v-model="row.forces"
                          rows="2" placeholder="Forces…"></textarea>
                        <span v-else class="obs-ro">{{ row.forces || '—' }}</span>
                      </td>

                      <!-- Faiblesses -->
                      <td class="td-faibl">
                        <textarea v-if="!isLocked" class="inp-obs" v-model="row.faiblesses"
                          rows="2" placeholder="Faiblesses…"></textarea>
                        <span v-else class="obs-ro">{{ row.faiblesses || '—' }}</span>
                      </td>

                      <!-- Objectif de contrôle -->
                      <td class="td-obj">
                        <textarea v-if="!isLocked" class="inp-obs" v-model="row.objectif"
                          rows="2" placeholder="Objectif…"></textarea>
                        <span v-else class="obs-ro">{{ row.objectif || '—' }}</span>
                      </td>

                      <!-- IA -->
                      <td class="td-ia">
                        <button
                          class="act-btn act-ia"
                          :disabled="iaLoading[row._id]"
                          @click="genererPropositionIA(row)"
                          title="Générer une proposition IA">
                          <span v-if="iaLoading[row._id]" class="spin-dot spin-sm" style="border-top-color:#7c3aed;width:10px;height:10px"></span>
                          <i v-else class="ti ti-robot"></i>
                        </button>
                        <!-- Bulle IA -->
                        <div v-if="row._iaProp" class="qcc-ia-bubble" :class="`ia-${row._iaProp.type}`">
                          <div class="qcc-ia-hd">
                            <span class="qcc-ia-type">{{ iaTypeLbl(row._iaProp.type) }}</span>
                            <span class="qcc-ia-prio" :class="`prio-${row._iaProp.priorite}`">
                              {{ row._iaProp.priorite }}
                            </span>
                            <button class="qcc-ia-close" @click="row._iaProp = null"><i class="ti ti-x"></i></button>
                          </div>
                          <p class="qcc-ia-txt">{{ row._iaProp.recommendation }}</p>
                          <ul v-if="row._iaProp.actions?.length" class="qcc-ia-actions">
                            <li v-for="a in row._iaProp.actions" :key="a">{{ a }}</li>
                          </ul>
                        </div>
                      </td>

                      <!-- Actions -->
                      <td v-if="!isLocked" class="td-act">
                        <button class="act-btn act-up" :disabled="idx===0" @click="moveUp(idx)">
                          <i class="ti ti-arrow-up"></i>
                        </button>
                        <button class="act-btn act-down" :disabled="idx>=rows.length-1" @click="moveDown(idx)">
                          <i class="ti ti-arrow-down"></i>
                        </button>
                        <button class="act-btn act-del" @click="removeRow(idx)">
                          <i class="ti ti-trash"></i>
                        </button>
                      </td>
                    </tr>

                  </template>
                </tbody>
              </table>
            </div><!-- /qci-table-wrap -->

          </div><!-- /col-right -->
        </div><!-- /qcc-grid -->

        <!-- ══ FOOTER ══════════════════════════════════════════════ -->
        <footer class="qci-footer">
          <div>
            <button v-if="!isLocked" type="button" class="btn btn-ghost"
              :disabled="processing" @click="annuler">
              <i class="ti ti-x"></i> Annuler
            </button>
            <button v-if="!isLocked" type="button" class="btn btn-save"
              :disabled="processing" @click="submit">
              <span v-if="processing" class="spin-dot"></span>
              <i v-else class="ti ti-device-floppy"></i>
              {{ form.id ? 'Mettre à jour' : 'Enregistrer' }}
            </button>
          </div>
          <div>
            <button v-if="form.id && form.statut === 'brouillon'"
              type="button" class="btn btn-sub" :disabled="processing" @click="soumettre">
              <i class="ti ti-send"></i> Soumettre
            </button>
            <button v-if="canManage && form.statut === 'soumis'"
              type="button" class="btn btn-ok" :disabled="processing" @click="valider">
              <i class="ti ti-circle-check"></i> Valider
            </button>
          </div>
        </footer>

      </div><!-- /qci-body -->
    </div><!-- /qcc-shell -->

    <!-- ══ TOAST ══════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="toast-t">
        <div v-if="toast.show" class="qci-toast" :class="`toast-${toast.type}`">
          <i :class="toast.type === 'success' ? 'ti ti-circle-check' : 'ti ti-circle-x'"></i>
          {{ toast.msg }}
        </div>
      </Transition>
    </Teleport>

  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'

// ── Props ─────────────────────────────────────────────────────────
const props = withDefaults(defineProps<{
  mission?:        any
  auditorRole?:    string
  missionId?:      number
  assignmentId?:   number
  form?:           any
  accList?:        any[]
  currentAuditor?: any
  formUrl?:        string
  urlStore?:       string
  urlUpdate?:      string
  urlSoumettre?:   string
  urlValider?:     string
  urlImporter?:    string
  urlExporter?:    string
  urlIaItem?:      string
  urlIaSynthese?:  string
  backUrl?:        string
  canManage?:      boolean
}>(), {
  qccList:    () => [],
  canManage:  false,
})

// ── Form state ─────────────────────────────────────────────────────
const form = reactive<any>({
  id:           null,
  intitule_qcc: '',
  description:  '',
  date_audit:   new Date().toISOString().slice(0, 10),
  statut:       'brouillon',
  score_global: null,
  ...(props.form ?? {}),
})

// ── Rows (sections + items) ───────────────────────────────────────
let _uid = 0
function uid() { return `_${++_uid}` }

const rows = ref<any[]>([])

onMounted(() => {
  if (props.form?.items?.length) {
    rows.value = buildRowsFromItems(props.form.items)
  }
  if (!form.date_audit) {
    form.date_audit = new Date().toISOString().slice(0, 10)
  }
})

function buildRowsFromItems(items: any[]): any[] {
  const result: any[] = []
  let lastRef = ''
  for (const it of items) {
    // Nouvelle section si ref_article change
    if (it.ref_article && it.ref_article !== lastRef) {
      lastRef = it.ref_article
      result.push({
        _id:   uid(),
        type:  'section',
        ref:   it.ref_article,
        label: it.libelle_norme ?? '',
      })
    }
    result.push({
      _id:        uid(),
      type:       'item',
      id:         it.id ?? null,
      ref:        it.ref_article ?? '',
      label:      it.exigence_norme ?? '',
      reponse:    it.reponse ?? '',
      forces:     it.forces ?? '',
      faiblesses: it.faiblesses ?? '',
      objectif:   it.objectif ?? '',
      observations: it.observations ?? '',
      _iaProp:    null,
    })
  }
  return result
}

// ── Computed ───────────────────────────────────────────────────────
const itemRows = computed(() => rows.value.filter(r => r.type === 'item'))

const stats = computed(() => {
  const items = itemRows.value
  return {
    conformes:    items.filter(r => r.reponse === 'O').length,
    nonConformes: items.filter(r => r.reponse === 'N').length,
    sansObjet:    items.filter(r => r.reponse === 'SO').length,
    nonEvalues:   items.filter(r => !r.reponse).length,
  }
})

const scoreGlobal = computed<number | null>(() => {
  const items = itemRows.value.filter(r => r.reponse)
  if (!items.length) return null
  const total = items.reduce((sum, r) => {
    if (r.reponse === 'O')  return sum + 100
    if (r.reponse === 'N')  return sum + 0
    if (r.reponse === 'SO') return sum + 50
    return sum
  }, 0)
  return Math.round(total / items.length)
})

const isLocked = computed(() =>
  form.statut === 'valide' ||
  (form.statut === 'soumis' && !props.canManage)
)

// ── Sections repliées ──────────────────────────────────────────────
const collapsed = ref<Set<string>>(new Set())
function toggleSection(id: string) {
  collapsed.value.has(id) ? collapsed.value.delete(id) : collapsed.value.add(id)
}
function getSectionOf(idx: number): string | null {
  for (let i = idx - 1; i >= 0; i--) {
    if (rows.value[i].type === 'section') return rows.value[i]._id
  }
  return null
}
function isHidden(idx: number): boolean {
  const sec = getSectionOf(idx)
  return sec ? collapsed.value.has(sec) : false
}

// ── Numérotation ──────────────────────────────────────────────────
function itemNumber(idx: number): string {
  let n = 0
  for (let i = 0; i <= idx; i++) {
    if (rows.value[i].type === 'item') n++
  }
  return String(n)
}

// ── CRUD lignes ───────────────────────────────────────────────────
function addSection() {
  rows.value.push({ _id: uid(), type: 'section', ref: '', label: '', _iaProp: null })
}
function addItem() {
  rows.value.push({ _id: uid(), type: 'item', id: null, ref: '', label: '', reponse: '', forces: '', faiblesses: '', objectif: '', observations: '', _iaProp: null })
}
function addItemAfter(idx: number) {
  rows.value.splice(idx + 1, 0, { _id: uid(), type: 'item', id: null, ref: '', label: '', reponse: '', forces: '', faiblesses: '', objectif: '', observations: '', _iaProp: null })
}
function removeRow(idx: number) { rows.value.splice(idx, 1) }
function moveUp(idx: number) {
  if (idx === 0) return
  ;[rows.value[idx - 1], rows.value[idx]] = [rows.value[idx], rows.value[idx - 1]]
}
function moveDown(idx: number) {
  if (idx >= rows.value.length - 1) return
  ;[rows.value[idx + 1], rows.value[idx]] = [rows.value[idx], rows.value[idx + 1]]
}

// ── Helpers CSS ───────────────────────────────────────────────────
function selClass(r: string) {
  return { 'sel-O': r === 'O', 'sel-N': r === 'N', 'sel-SO': r === 'SO', 'sel-empty': !r }
}
function repRoClass(r: string) {
  return { 'rep-ro-O': r === 'O', 'rep-ro-N': r === 'N', 'rep-ro-SO': r === 'SO', 'rep-ro-empty': !r }
}
function scoreColor(s: number): string {
  if (s >= 75) return '#059669'
  if (s >= 50) return '#d97706'
  return '#dc2626'
}

// ── Statuts ────────────────────────────────────────────────────────
function statutLbl(s: string) {
  return ({ brouillon:'Brouillon', soumis:'Soumis', valide:'Validé ✓', archive:'Archivé' } as any)[s] ?? s
}
function statutIcon(s: string) {
  return ({ brouillon:'ti ti-pencil', soumis:'ti ti-clock', valide:'ti ti-circle-check', archive:'ti ti-archive' } as any)[s] ?? 'ti ti-circle'
}
function maturiteLbl(m: string) {
  return ({ initial:'Initial', en_developpement:'En développement', defini:'Défini', gere:'Géré', optimise:'Optimisé' } as any)[m] ?? m
}
function iaTypeLbl(t: string) {
  return ({ amelioration:'Amélioration', validation:'Validation', alerte:'⚠ Alerte' } as any)[t] ?? t
}

// ── CSRF ──────────────────────────────────────────────────────────
function csrf() {
  return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? ''
}

// ── Toast ─────────────────────────────────────────────────────────
const toast = ref({ show: false, type: 'success', msg: '' })
function showToast(type: string, msg: string) {
  toast.value = { show: true, type, msg }
  setTimeout(() => (toast.value.show = false), 4000)
}

// ── Import Excel ──────────────────────────────────────────────────
const xlsRef    = ref<HTMLInputElement | null>(null)
const importing = ref(false)

async function importExcel(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  importing.value = true
  const fd = new FormData()
  fd.append('file', file)
  fd.append('form_id', form.id ?? '')
  fd.append('_token', csrf())
  try {
    const res  = await fetch(props.urlImporter!, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf() },
      body: fd,
    })
    const data = await res.json()
    if (!res.ok || !data.success) throw new Error(data.message ?? 'Erreur import')
    rows.value = buildRowsFromItems(data.items ?? [])
    showToast('success', `Fichier importé — ${itemRows.value.length} exigence(s) chargée(s).`)
  } catch (err: any) {
    showToast('error', err.message ?? 'Erreur lors de l\'import Excel.')
  } finally {
    importing.value = false
    if (xlsRef.value) xlsRef.value.value = ''
  }
}

// ── IA — Proposition par ligne ────────────────────────────────────
const iaLoading = reactive<Record<string, boolean>>({})

function onReponseChange(row: any) {
  // Auto-proposition IA si réponse = N
  if (row.reponse === 'N' && !row._iaProp) {
    genererPropositionIA(row)
  }
}

async function genererPropositionIA(row: any) {
  if (!form.id) {
    showToast('error', 'Sauvegardez d\'abord le QCC avant de générer une proposition IA.')
    return
  }
  iaLoading[row._id] = true
  try {
    const res  = await fetch(props.urlIaItem!, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({
        item_id:    row.id,
        exigence:   row.label,
        libelle:    row.ref,
        reponse:    row.reponse,
        forces:     row.forces,
        faiblesses: row.faiblesses,
        objectif:   row.objectif,
      }),
    })
    const data = await res.json()
    if (!res.ok || !data.success) throw new Error(data.message ?? 'Erreur IA')
    row._iaProp = data.proposition
  } catch (err: any) {
    showToast('error', 'Erreur IA : ' + err.message)
  } finally {
    iaLoading[row._id] = false
  }
}

// ── IA — Synthèse globale ─────────────────────────────────────────
const iaGlobalLoading = ref(false)
const iaSynthese      = ref<any>(null)

async function genererSyntheseGlobale() {
  if (!form.id) {
    showToast('error', 'Sauvegardez d\'abord le QCC.')
    return
  }
  iaGlobalLoading.value = true
  try {
    const res  = await fetch(props.urlIaSynthese!, {
      headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
    })
    const data = await res.json()
    if (!res.ok || !data.success) throw new Error(data.message ?? 'Erreur IA')
    iaSynthese.value = data.synthese
  } catch (err: any) {
    showToast('error', 'Erreur IA : ' + err.message)
  } finally {
    iaGlobalLoading.value = false
  }
}

// ── Nouveau QCC / Charger QCC ─────────────────────────────────────
function newQcc() {
  form.id = null; form.intitule_qcc = ''; form.description = ''
  form.statut = 'brouillon'; form.score_global = null
  form.date_audit = new Date().toISOString().slice(0, 10)
  rows.value = []; iaSynthese.value = null
}
function loadQcc(q: any) {
  Object.assign(form, {
    id:           q.id,
    intitule_qcc: q.intitule_qcc ?? '',
    description:  q.description  ?? '',
    date_audit:   q.date_audit   ?? '',
    statut:       q.statut       ?? 'brouillon',
    score_global: q.score_global ?? null,
  })
  rows.value = q.items?.length ? buildRowsFromItems(q.items) : []
  iaSynthese.value = null
}

// ── Sérialisation des items ────────────────────────────────────────
function buildItemsPayload() {
  return itemRows.value.map((r, i) => ({
    id:             r.id ?? null,
    ref_article:    r.ref ?? '',
    libelle_norme:  r.ref ?? '',
    exigence_norme: r.label ?? '',
    reponse:        r.reponse  || null,
    forces:         r.forces   || null,
    faiblesses:     r.faiblesses || null,
    objectif:       r.objectif || null,
    observations:   r.observations || null,
    ordre:          i,
  }))
}

// ── Submit ────────────────────────────────────────────────────────
const processing = ref(false)

async function submit() {
  if (!form.intitule_qcc?.trim()) {
    showToast('error', 'L\'intitulé QCC est obligatoire.')
    return
  }
  processing.value = true
  try {
    const payload = {
      intitule_qcc: form.intitule_qcc,
      description:  form.description,
      date_audit:   form.date_audit,
      mission_id:   props.missionId,
      items:        buildItemsPayload(),
    }
    const isNew  = !form.id
    const method = isNew ? 'POST' : 'PUT'
    const url    = isNew ? props.formUrl! : `${props.formUrl}/${form.id}`
    const res    = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify(payload),
    })
    const data = await res.json()
    if (res.ok && (data.success !== false)) {
      if (isNew && data.form?.id) { form.id = data.form.id }
      showToast('success', isNew ? 'QCC créé avec succès.' : 'QCC mis à jour.')
    } else {
      showToast('error', data.message ?? 'Erreur lors de la sauvegarde.')
    }
  } catch { showToast('error', 'Erreur réseau.') }
  finally { processing.value = false }
}

function annuler() { if (props.backUrl) router.visit(props.backUrl) }

async function soumettre() {
  processing.value = true
  try {
    const res  = await fetch(props.urlSoumettre!, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
    })
    const data = await res.json()
    if (res.ok) { form.statut = 'soumis'; showToast('success', 'QCC soumis pour validation.') }
    else showToast('error', data.message ?? 'Erreur')
  } catch { showToast('error', 'Erreur réseau') }
  finally { processing.value = false }
}

async function valider() {
  processing.value = true
  try {
    const res  = await fetch(props.urlValider!, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
    })
    const data = await res.json()
    if (res.ok) { form.statut = 'valide'; showToast('success', 'QCC validé ✓') }
    else showToast('error', data.message ?? 'Erreur')
  } catch { showToast('error', 'Erreur réseau') }
  finally { processing.value = false }
}
</script>

<style scoped>
*, *::before, *::after { box-sizing: border-box; }
.qcc-shell { display:flex; flex-direction:column; min-height:100vh; background:#f4f6f8; font-family:'Segoe UI',system-ui,sans-serif; }

/* ══ HEADER (réutilise les classes QCI) ══════════════════════════ */
.qci-header { position:sticky; top:0; z-index:100; background:#fff; border-bottom:1px solid #e2e8f0; box-shadow:0 1px 4px rgba(0,0,0,.06); padding:0 20px; }
.qci-hrow   { display:flex; align-items:center; gap:12px; min-height:60px; padding:8px 0; flex-wrap:wrap; }
.qci-back   { display:flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:8px; background:#f1f5f9; border:1px solid #e2e8f0; color:#64748b; text-decoration:none; flex-shrink:0; font-size:.9rem; transition:all .15s; }
.qci-back:hover { background:#1565C0; color:#fff; border-color:#1565C0; }
.qci-hinfo  { flex:1; min-width:0; }
.qci-chips  { display:flex; align-items:center; gap:5px; flex-wrap:wrap; margin-bottom:3px; }
.qci-code   { font-family:ui-monospace,monospace; font-size:.68rem; font-weight:700; background:#1e293b; color:#fff; padding:2px 8px; border-radius:5px; }
.qci-chip   { display:inline-flex; align-items:center; gap:3px; font-size:.62rem; font-weight:700; padding:2px 8px; border-radius:10px; text-transform:uppercase; letter-spacing:.04em; }
.chip-brouillon { background:rgba(100,116,139,.1); color:#64748b; }
.chip-soumis    { background:#e3f2fd; color:#1565C0; border:1px solid rgba(21,101,192,.2); }
.chip-valide    { background:#d1e7dd; color:#0f5132; border:1px solid rgba(15,81,50,.2); }
.chip-archive   { background:#f1f5f9; color:#475569; }
.chip-type      { background:#ede9fe; color:#7c3aed; border:1px solid #ddd6fe; }
.chip-role-DM   { background:rgba(251,191,36,.18); color:#d97706; }
.chip-role-CM   { background:rgba(21,101,192,.12); color:#1565C0; }
.chip-role-AS   { background:rgba(22,163,74,.12); color:#15803d; }
.qci-title  { font-size:.92rem; font-weight:700; color:#1a1a2e; }
.qci-meta   { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-top:2px; }
.qci-meta span { display:inline-flex; align-items:center; gap:4px; font-size:.69rem; color:#64748b; }
.qci-hactions { margin-left:auto; }
.qci-btn-ia { width:34px; height:34px; border-radius:8px; border:1px solid #ddd6fe; background:#f5f3ff; color:#7c3aed; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:.85rem; transition:all .15s; }
.qci-btn-ia:hover:not(:disabled) { background:#7c3aed; color:#fff; border-color:#7c3aed; }
.qci-btn-ia:disabled { opacity:.45; cursor:not-allowed; }
.qci-banner { display:flex; align-items:center; gap:8px; padding:7px 0 10px; font-size:.77rem; border-top:1px solid #f1f5f9; }
.banner-lock   { color:#0f5132; }
.banner-review { color:#1565C0; }

/* ══ SYNTHÈSE IA GLOBALE ═════════════════════════════════════════ */
.qcc-synthese-band { background:#faf5ff; border:1px solid #ddd6fe; border-radius:10px; margin:10px 0; padding:13px 16px; }
.qcc-synthese-hd   { display:flex; align-items:center; gap:8px; margin-bottom:8px; font-size:.77rem; }
.qcc-synthese-hd strong { color:#7c3aed; }
.qcc-synthese-close { margin-left:auto; width:20px; height:20px; border:none; background:transparent; color:#94a3b8; cursor:pointer; border-radius:4px; display:flex; align-items:center; justify-content:center; font-size:.65rem; }
.qcc-synthese-close:hover { background:#f3f4f6; color:#374151; }
.qcc-maturite-badge { font-size:.6rem; font-weight:700; padding:2px 8px; border-radius:8px; background:#ede9fe; color:#7c3aed; }
.mat-initial          { background:#fee2e2; color:#dc2626; }
.mat-en_developpement { background:#fef3c7; color:#d97706; }
.mat-defini           { background:#dbeafe; color:#1565C0; }
.mat-gere             { background:#d1fae5; color:#059669; }
.mat-optimise         { background:#d1e7dd; color:#0f5132; }
.qcc-synthese-txt { font-size:.76rem; color:#374151; line-height:1.6; margin-bottom:10px; }
.qcc-synthese-cols { display:grid; grid-template-columns:repeat(auto-fit, minmax(160px,1fr)); gap:10px; }
.qcc-syn-col ul  { margin:6px 0 0; padding-left:14px; font-size:.7rem; color:#374151; line-height:1.7; }
.qcc-syn-col-hd  { display:flex; align-items:center; gap:5px; font-size:.67rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; padding:4px 0; }
.syn-ok   { color:#059669; }
.syn-warn { color:#d97706; }
.syn-err  { color:#dc2626; }

/* ══ BODY ════════════════════════════════════════════════════════ */
.qci-body  { padding:20px; flex:1; }
.qcc-grid  { display:grid; grid-template-columns:270px 1fr; gap:18px; }
@media(max-width:900px){ .qcc-grid{ grid-template-columns:1fr; } }

/* ══ CARDS ═══════════════════════════════════════════════════════ */
.card        { background:#fff; border:1px solid #e2e8f0; border-radius:10px; margin-bottom:14px; overflow:hidden; }
.card-label  { display:flex; align-items:center; gap:6px; font-size:.71rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#64748b; padding:9px 13px; background:#f8fafc; border-bottom:1px solid #e2e8f0; }
.card-count  { margin-left:auto; font-size:.64rem; font-weight:800; background:#e5e7eb; color:#6b7280; padding:1px 6px; border-radius:8px; }
.btn-new-inline { width:20px; height:20px; border:1px solid #e5e7eb; border-radius:4px; background:transparent; color:#6b7280; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:.65rem; transition:all .12s; }
.btn-new-inline:hover { background:#eff6ff; color:#1565C0; border-color:#bbdefb; }
.card-body   { padding:12px 13px; }

/* ══ SCORE ═══════════════════════════════════════════════════════ */
.qcc-score-ring  { display:flex; align-items:center; gap:16px; }
.qcc-score-detail { display:flex; flex-direction:column; gap:4px; }
.qcc-score-line  { display:flex; align-items:center; gap:6px; font-size:.71rem; color:#374151; }
.dot-ok  { width:8px; height:8px; border-radius:50%; background:#059669; flex-shrink:0; }
.dot-ko  { width:8px; height:8px; border-radius:50%; background:#dc2626; flex-shrink:0; }
.dot-so  { width:8px; height:8px; border-radius:50%; background:#94a3b8; flex-shrink:0; }
.dot-nd  { width:8px; height:8px; border-radius:50%; background:#e5e7eb; border:1px solid #d1d5db; flex-shrink:0; }
.qcc-score-empty { display:flex; align-items:center; gap:6px; font-size:.74rem; color:#d1d5db; }

/* ══ QCC LIST ════════════════════════════════════════════════════ */
.qci-list-row { display:flex; align-items:center; justify-content:space-between; padding:8px 12px; border-bottom:1px solid #f3f4f6; cursor:pointer; transition:background .1s; gap:8px; }
.qci-list-row:last-child { border-bottom:none; }
.qci-list-row:hover  { background:#f8fafc; }
.qci-list-row.active { background:#e3f2fd; border-left:3px solid #1565C0; }
.qlr-l { flex:1; min-width:0; }
.qlr-code  { font-size:.6rem; font-family:ui-monospace,monospace; color:#94a3b8; display:block; }
.qlr-title { font-size:.74rem; font-weight:500; color:#1e293b; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; display:block; }
.td-empty  { text-align:center; color:#d1d5db; padding:14px; font-size:.76rem; }

/* ══ FORM ════════════════════════════════════════════════════════ */
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.fg   { display:flex; flex-direction:column; gap:3px; margin-bottom:10px; }
.fg:last-child { margin-bottom:0; }
.flbl { font-size:.64rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em; }
.req  { color:#dc2626; }
.inp  { background:#fff; border:1px solid #e2e8f0; color:#1e293b; padding:6px 9px; border-radius:6px; font-size:.78rem; outline:none; transition:border-color .15s; font-family:inherit; width:100%; }
.inp:focus { border-color:#1565C0; box-shadow:0 0 0 3px rgba(21,101,192,.1); }
.inp:disabled, .inp-ro { background:#f8fafc; color:#94a3b8; cursor:default; }
.inp-ta { resize:vertical; min-height:60px; }

/* ══ TOOLBAR ═════════════════════════════════════════════════════ */
.qci-toolbar { display:flex; align-items:center; justify-content:space-between; background:#fff; border:1px solid #e2e8f0; border-radius:10px 10px 0 0; padding:10px 14px; gap:12px; flex-wrap:wrap; }
.qtb-l { display:flex; align-items:center; gap:8px; }
.qtb-title { font-size:.8rem; font-weight:700; color:#1e293b; }
.qtb-count { font-size:.67rem; color:#94a3b8; background:#f3f4f6; padding:2px 7px; border-radius:6px; }
.qtb-r { display:flex; gap:6px; flex-wrap:wrap; }

/* ══ BOUTONS ═════════════════════════════════════════════════════ */
.btn { display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:7px; font-size:.76rem; font-weight:600; border:none; cursor:pointer; font-family:inherit; transition:all .15s; white-space:nowrap; }
.btn-save   { background:#1e293b; color:#fff; }
.btn-save:hover:not(:disabled)   { background:#0f172a; }
.btn-ghost  { background:#fff; color:#374151; border:1px solid #e5e7eb; }
.btn-ghost:hover:not(:disabled)  { background:#f9fafb; }
.btn-sub    { background:#eff6ff; color:#1565C0; border:1px solid #bbdefb; }
.btn-ok     { background:#d1e7dd; color:#0f5132; border:1px solid rgba(15,81,50,.2); }
.btn-cat    { background:#f5f3ff; color:#7c3aed; border:1px solid #ddd6fe; font-size:.74rem; padding:5px 10px; }
.btn-item   { background:#eff6ff; color:#1565C0; border:1px solid #bbdefb; font-size:.74rem; padding:5px 10px; }
.btn-import { background:#f0f9ff; color:#0369a1; border:1px solid #bae6fd; font-size:.74rem; padding:5px 10px; cursor:pointer; }
.btn-import:hover { background:#e0f2fe; }
.btn-tpl    { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; font-size:.74rem; padding:5px 10px; text-decoration:none; }
.btn-tpl:hover { background:#dcfce7; }
.btn:disabled { opacity:.45; cursor:not-allowed; }

/* ══ TABLEAU ═════════════════════════════════════════════════════ */
.qci-table-wrap { overflow-x:auto; background:#fff; border:1px solid #e2e8f0; border-top:none; border-radius:0 0 10px 10px; }
.qci-table { width:100%; border-collapse:collapse; font-size:.76rem; min-width:900px; }
.qci-table thead tr { background:#1565C0; }
.qci-table th { padding:8px 10px; text-align:left; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#fff; white-space:nowrap; }
.th-num      { width:36px; text-align:center; }
.th-question { min-width:220px; }
.th-rep      { width:80px; text-align:center; }
.th-forces   { min-width:130px; }
.th-faibl    { min-width:130px; }
.th-obj      { min-width:130px; }
.th-ia       { width:44px; text-align:center; }
.th-act      { width:80px; }

/* Sections */
.row-cat { background:#e8f0fe; cursor:pointer; border-bottom:1px solid #bfdbfe; }
.row-cat:hover { background:#dbeafe; }
.td-cat-label { padding:7px 10px; }
.inp-cat { width:100%; background:transparent; border:none; font-size:.8rem; font-weight:700; color:#1e3a5f; outline:none; font-family:inherit; }
.inp-cat:focus { border-bottom:1px solid #1565C0; }

/* Lignes */
.row-item   { border-bottom:1px solid #f1f5f9; transition:background .1s; }
.row-item:hover { background:#fafafa; }
.row-hidden { display:none; }
.row-oui { background:#f0fdf4; }
.row-non { background:#fef2f2; }
.row-so  { background:#f8fafc; opacity:.75; }

/* Ref badges */
.qcc-ref-badge { font-size:.6rem; font-family:ui-monospace,monospace; font-weight:700; background:#1e293b; color:#fff; padding:2px 7px; border-radius:4px; flex-shrink:0; }
.qcc-ref-sm    { font-size:.58rem; font-family:ui-monospace,monospace; color:#94a3b8; display:block; margin-bottom:2px; }
.qcc-exig-wrap { display:flex; flex-direction:column; }

.td-num      { text-align:center; padding:4px; }
.item-num    { font-size:.65rem; font-weight:700; color:#94a3b8; font-family:ui-monospace,monospace; }
.td-question { padding:6px 8px; vertical-align:top; }
.inp-q  { width:100%; border:none; background:transparent; resize:none; font-size:.76rem; color:#1e293b; font-family:inherit; outline:none; min-height:38px; padding:2px 0; line-height:1.5; }
.inp-q:focus { border-bottom:1px dashed #1565C0; }
.q-ro  { font-size:.76rem; color:#374151; line-height:1.5; display:block; }

/* Sel O/N/SO */
.td-rep { text-align:center; padding:4px 6px; }
.sel-rep { width:100%; border:1px solid #d1d5db; border-radius:6px; padding:5px 6px; font-size:.78rem; font-weight:700; cursor:pointer; outline:none; font-family:inherit; transition:all .15s; text-align:center; }
.sel-rep:focus { border-color:#1565C0; }
.sel-O     { background:#ecfdf5; color:#059669; border-color:#a7f3d0; }
.sel-N     { background:#fef2f2; color:#dc2626; border-color:#fecaca; }
.sel-SO    { background:#f3f4f6; color:#6b7280; border-color:#d1d5db; }
.sel-empty { background:#fffbeb; color:#92400e; border-color:#fde68a; }
.rep-ro    { display:inline-block; font-size:.76rem; font-weight:700; padding:2px 8px; border-radius:5px; }
.rep-ro-O  { background:#ecfdf5; color:#059669; }
.rep-ro-N  { background:#fef2f2; color:#dc2626; }
.rep-ro-SO { background:#f3f4f6; color:#6b7280; }
.rep-ro-empty { color:#d1d5db; }

/* Forces / Faiblesses / Objectif */
.td-forces, .td-faibl, .td-obj { padding:5px 7px; vertical-align:top; }
.inp-obs { width:100%; border:none; background:transparent; font-size:.72rem; color:#374151; font-family:inherit; outline:none; resize:none; min-height:34px; line-height:1.5; padding:2px 0; }
.inp-obs:focus { border-bottom:1px dashed #1565C0; }
.obs-ro { font-size:.72rem; color:#64748b; line-height:1.5; display:block; }

/* IA */
.td-ia { text-align:center; padding:4px; vertical-align:top; position:relative; }
.act-ia { background:#f5f3ff; color:#7c3aed; border-color:#ddd6fe !important; }
.act-ia:hover:not(:disabled) { background:#7c3aed; color:#fff; border-color:#7c3aed !important; }

.qcc-ia-bubble { position:absolute; right:50px; top:0; width:280px; background:#fff; border:1px solid #ddd6fe; border-radius:10px; box-shadow:0 8px 24px rgba(124,58,237,.12); z-index:50; padding:10px 12px; text-align:left; }
.qcc-ia-hd  { display:flex; align-items:center; gap:6px; margin-bottom:6px; }
.qcc-ia-type { font-size:.66rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; padding:2px 7px; border-radius:6px; }
.ia-alerte .qcc-ia-type       { background:#fee2e2; color:#dc2626; }
.ia-amelioration .qcc-ia-type { background:#fef3c7; color:#d97706; }
.ia-validation .qcc-ia-type   { background:#d1fae5; color:#059669; }
.qcc-ia-prio { font-size:.6rem; font-weight:700; padding:1px 6px; border-radius:4px; }
.prio-haute   { background:#fee2e2; color:#dc2626; }
.prio-moyenne { background:#fef3c7; color:#d97706; }
.prio-faible  { background:#d1fae5; color:#059669; }
.qcc-ia-close { margin-left:auto; width:18px; height:18px; border:none; background:transparent; color:#94a3b8; cursor:pointer; border-radius:4px; display:flex; align-items:center; justify-content:center; font-size:.6rem; }
.qcc-ia-close:hover { background:#f3f4f6; }
.qcc-ia-txt   { font-size:.73rem; color:#374151; line-height:1.6; margin-bottom:6px; }
.qcc-ia-actions { margin:0; padding-left:14px; font-size:.68rem; color:#374151; line-height:1.8; }

/* Actions */
.td-act { padding:4px 6px; white-space:nowrap; text-align:right; vertical-align:middle; }
.act-btn { width:22px; height:22px; border:1px solid transparent; border-radius:4px; background:transparent; cursor:pointer; color:#d1d5db; font-size:.68rem; display:inline-flex; align-items:center; justify-content:center; transition:all .12s; margin-left:2px; }
.act-btn:disabled { opacity:.3; cursor:not-allowed; }
.act-add:hover  { color:#059669; border-color:#a7f3d0; background:#ecfdf5; }
.act-del:hover  { color:#dc2626; border-color:#fecaca; background:#fef2f2; }
.act-up:hover, .act-down:hover { color:#1565C0; border-color:#bbdefb; background:#eff6ff; }

/* Empty */
.qci-empty { text-align:center; padding:40px 20px; color:#94a3b8; }
.qci-empty i { font-size:1.8rem; display:block; margin-bottom:10px; }
.qci-empty p { font-size:.78rem; margin-bottom:14px; }
.empty-actions { display:flex; gap:8px; justify-content:center; flex-wrap:wrap; }
.d-flex {
  display: flex;
}
/* ══ FOOTER ══════════════════════════════════════════════════════ */
.qci-footer { display:flex; align-items:center; justify-content:space-between; padding:12px 20px; background:#fff; border-top:1px solid #e2e8f0; position:sticky; bottom:0; z-index:50; flex-wrap:wrap; gap:8px; }
.qci-footer > div { display:flex; gap:8px; flex-wrap:wrap; }

/* ══ TOAST ═══════════════════════════════════════════════════════ */
.qci-toast { position:fixed; top:18px; right:18px; z-index:9999; display:flex; align-items:center; gap:8px; padding:10px 16px; border-radius:8px; font-size:.8rem; font-weight:600; box-shadow:0 4px 18px rgba(0,0,0,.12); }
.toast-success { background:#d1e7dd; color:#0f5132; border:1px solid rgba(15,81,50,.2); }
.toast-error   { background:#f8d7da; color:#842029; border:1px solid rgba(132,32,41,.2); }
.toast-t-enter-active, .toast-t-leave-active { transition:all .25s; }
.toast-t-enter-from, .toast-t-leave-to { opacity:0; transform:translateX(12px); }

/* ══ SPINNER ═════════════════════════════════════════════════════ */
.spin-dot { width:10px; height:10px; border:2px solid rgba(255,255,255,.3); border-top-color:#fff; border-radius:50%; animation:spin .6s linear infinite; display:inline-block; flex-shrink:0; }
.spin-sm  { width:12px; height:12px; }
@keyframes spin { to { transform:rotate(360deg); } }
.hidden { display:none; }
</style>