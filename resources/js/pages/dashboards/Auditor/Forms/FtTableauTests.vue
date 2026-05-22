<template>
  <div class="ftt-wrap">

    <!-- ══ BARRE DE SYNTHÈSE GLOBALE ══════════════════════════════ -->
    <div class="ftt-summary" v-if="programmeData?.found">
      <div class="ftt-sum-item ftt-sum-item--total">
        <span class="ftt-sum-n">{{ totalTests }}</span>
        <span class="ftt-sum-lbl">Tests</span>
      </div>
      <div class="ftt-sum-item ftt-sum-item--done">
        <span class="ftt-sum-n">{{ testsAvecResultat }}</span>
        <span class="ftt-sum-lbl">Saisis</span>
      </div>
      <div class="ftt-sum-item ftt-sum-item--outils">
        <span class="ftt-sum-n">{{ totalOutilsLies }}</span>
        <span class="ftt-sum-lbl">Outils liés</span>
      </div>
      <div class="ftt-sum-item ftt-sum-item--prog">
        <span class="ftt-sum-n">{{ progressPct }}%</span>
        <span class="ftt-sum-lbl">Avancement</span>
      </div>
      <div class="ftt-progress-bar">
        <div class="ftt-progress-fill" :style="`width:${progressPct}%`"></div>
      </div>
    </div>

    <!-- ══ ÉTAT VIDE ════════════════════════════════════════════════ -->
    <div v-if="!programmeData?.found || !programmeData?.objectifs?.length" class="ftt-empty">
      <div class="ftt-empty__ico"><i class="ti ti-clipboard-off"></i></div>
      <p class="ftt-empty__title">Aucun test affecté</p>
      <p class="ftt-empty__sub">Contactez le Chef de Mission pour vous affecter des tests dans le programme de travail.</p>
    </div>

    <!-- ══ BLOCS PAR OBJECTIF ═══════════════════════════════════════ -->
    <div v-for="(obj, oi) in (programmeData?.objectifs ?? [])" :key="oi" class="ftt-obj-block">

      <!-- En-tête objectif -->
      <div class="ftt-obj-hdr">
        <span class="ftt-obj-num">{{ obj.num }}</span>
        <span class="ftt-obj-label">{{ obj.objectif }}</span>
        <span v-if="obj._axe_rado" class="ftt-tag ftt-tag--blue">{{ obj._axe_rado }}</span>
        <span v-if="obj._priorite" class="ftt-tag" :class="'ftt-prio--' + obj._priorite">{{ obj._priorite }}</span>
        <span class="ftt-obj-meta">
          {{ obj.tests?.length ?? 0 }} test(s) ·
          {{ obj.tests?.filter((t: any, ti: number) => getResultat(obj.num, tRef(t, oi, ti))).length ?? 0 }} saisis
        </span>
      </div>

      <!-- ── Ligne par test (cliquable → panneau détail) ── -->
      <div
        v-for="(test, ti) in obj.tests"
        :key="ti"
        class="ftt-test-row"
        :class="[
          getResultat(obj.num, tRef(test, oi, ti)) ? 'ftt-test-row--done' : '',
          resultClass(getResultat(obj.num, tRef(test, oi, ti))),
          detailActif?.testRef === tRef(test, oi, ti) ? 'ftt-test-row--active' : ''
        ]"
        @click="ouvrirDetail(obj, test, oi, ti)"
      >
        <!-- Indicateur résultat (barre gauche) -->
        <span class="ftt-row-bar" :class="`ftt-bar--${getResultat(obj.num, tRef(test, oi, ti))}`"></span>

        <!-- Réf -->
        <code class="ftt-ref" :style="`background:${refColor(obj.num, tRef(test, oi, ti))}`">
          {{ tRef(test, oi, ti) }}
        </code>

        <!-- Info test -->
        <div class="ftt-row-info">
          <p class="ftt-test-lbl">{{ test.libelle || '—' }}</p>
          <div class="ftt-row-chips">
            <span v-if="test.periode_testee" class="ftt-chip ftt-chip--cal">
              <i class="ti ti-calendar"></i>{{ test.periode_testee }}
            </span>
            <span v-if="test.lieu" class="ftt-chip ftt-chip--geo">
              <i class="ti ti-map-pin"></i>{{ test.lieu }}
            </span>
            <span v-if="test.taille_echantillon" class="ftt-chip ftt-chip--n">
              n={{ test.taille_echantillon }}
            </span>
            <!-- Outils liés (chips) -->
            <span
              v-for="code in allOutilsForTest(obj.num, tRef(test, oi, ti))"
              :key="code"
              class="ftt-chip ftt-chip--outil"
              :style="`--oc:${outilColor(code)}`"
            ><i class="ti ti-tool"></i>{{ code }}</span>
            <!-- Constat présent -->
            <span v-if="getConstat(obj.num, tRef(test, oi, ti))" class="ftt-chip ftt-chip--note">
              <i class="ti ti-note"></i> Constat
            </span>
          </div>
        </div>

        <!-- Résultat pill + flèche -->
        <div class="ftt-row-right">
          <span class="ftt-res-pill" :class="`ftt-res--${getResultat(obj.num, tRef(test, oi, ti))}`">
            {{ resLbl(getResultat(obj.num, tRef(test, oi, ti))) || 'À saisir' }}
          </span>
          <!-- Procédures count -->
          <span v-if="test.procedures?.length" class="ftt-procs-count">
            <i class="ti ti-list"></i>{{ test.procedures.length }}
          </span>
          <i class="ti ti-chevron-right ftt-arrow" :class="detailActif?.testRef === tRef(test, oi, ti) ? 'ftt-arrow--active' : ''"></i>
        </div>
      </div>

    </div><!-- /ftt-obj-block -->

    <!-- ══ PANNEAU DÉTAIL TEST ═══════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="ftd-slide">
        <FtFicheTestDetail
          v-if="detailActif"
          :obj="detailActif.obj"
          :test="detailActif.test"
          :test-ref="detailActif.testRef"
          :obj-num="detailActif.objNum"
          :auditeur-nom="auditeurNom"
          :date-audit="dateAudit"
          :mission-libelle="missionLibelle"
          :code-mission="codeMission"
          :outils-ifaci="outilsIfaci"
          :outils-procs-map="outilsProcsMap"
          :resultats-map="resultatsMap"
          :is-locked="isLocked"
          @fermer="detailActif = null"
          @editer-constat="onEditerConstat"
          @set-resultat="(on, tr, v) => $emit('setResultat', on, tr, v)"
          @ouvrir-outil="(obj, test, code, pi) => $emit('ouvrirProc', obj, test, code, pi, detailActif!.oi, detailActif!.ti)"
          @choix-outil="(obj, test, pi) => $emit('choixOutil', obj, test, pi, detailActif!.oi, detailActif!.ti)"
        />
      </Transition>
    </Teleport>

  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import FtFicheTestDetail from './FtFicheTestDetail.vue'

// ── Props ──────────────────────────────────────────────────────────────────
const props = defineProps<{
  programmeData?: any
  outilsIfaci?: { code: string; label: string; icon: string; color: string }[]
  /** clé: "objNum::testRef::procIdx" → tableau des codes outils */
  outilsProcsMap: Record<string, string[]>
  resultatsMap: Record<string, { resultat: string; constat: string; preuve: string }>
  isLocked?: boolean
  auditeurNom?: string
  dateAudit?: string
  missionLibelle?: string
  codeMission?: string
}>()

const emit = defineEmits([
  'choixOutil',       // (obj, test, procIdx, oi, ti)
  'ouvrirProc',       // (obj, test, code, procIdx, oi, ti)
  'ouvrirTest',       // (obj, test, code, oi, ti)
  'ouvrirConstat',    // (obj, test, oi, ti)
  'setResultat',      // (objNum, testRef, valeur)
  'setConstat',       // (objNum, testRef, valeur)
  'setPreuve',        // (objNum, testRef, valeur)
])

// ── État panneau détail ───────────────────────────────────────────────────
const detailActif = ref<null | {
  obj: any; test: any; testRef: string; objNum: string; oi: number; ti: number
}>(null)

function ouvrirDetail(obj: any, test: any, oi: number, ti: number) {
  const tr = tRef(test, oi, ti)
  // Si déjà ouvert sur ce test, fermer
  if (detailActif.value?.testRef === tr) {
    detailActif.value = null
    return
  }
  detailActif.value = { obj, test, testRef: tr, objNum: obj.num, oi, ti }
}

function onEditerConstat() {
  if (!detailActif.value) return
  const { obj, test, oi, ti } = detailActif.value
  detailActif.value = null
  emit('ouvrirConstat', obj, test, oi, ti)
}

// ── Helpers clés ──────────────────────────────────────────────────────────
function tRef(test: any, oi: number, ti: number): string {
  return test.ref || `T${oi + 1}.${ti + 1}`
}
function pKey(on: string, tr: string, pi: number): string {
  return `${on}::${tr}::${pi}`
}

// ── Outils ────────────────────────────────────────────────────────────────
function getOutilsForProc(on: string, tr: string, pi: number): string[] {
  return props.outilsProcsMap[pKey(on, tr, pi)] ?? []
}
function allOutilsForTest(on: string, tr: string): string[] {
  const prefix = `${on}::${tr}::`
  const codes = new Set<string>()
  Object.entries(props.outilsProcsMap).forEach(([k, v]) => {
    if (k.startsWith(prefix)) v.forEach(c => codes.add(c))
  })
  return [...codes]
}

// ── Résultats ─────────────────────────────────────────────────────────────
function getResultat(on: string, tr: string): string { return props.resultatsMap[`${on}::${tr}`]?.resultat ?? '' }
function getConstat(on: string, tr: string): string  { return props.resultatsMap[`${on}::${tr}`]?.constat  ?? '' }

// ── UI ────────────────────────────────────────────────────────────────────
function outilColor(code: string): string {
  return props.outilsIfaci?.find(o => o.code === code)?.color ?? '#374151'
}
function resLbl(r: string): string {
  return ({ conforme: '✅ Conforme', ecart: '⚠️ Écart', nc: '❌ Non conforme', na: 'N/A' } as any)[r] ?? ''
}
function resultClass(r: string): string {
  return ({ conforme: 'ftt-test-row--conforme', ecart: 'ftt-test-row--ecart', nc: 'ftt-test-row--nc' } as any)[r] ?? ''
}
function refColor(on: string, tr: string): string {
  const r = getResultat(on, tr)
  if (r === 'conforme') return '#a7f3d0'
  if (r === 'ecart')    return '#fde68a'
  if (r === 'nc')       return '#fca5a5'
  return '#ddd6fe'
}

// ── Stats ────────────────────────────────────────────────────────────────
const totalTests = computed(() =>
  (props.programmeData?.objectifs ?? []).reduce((s: number, o: any) => s + (o.tests?.length ?? 0), 0)
)
const testsAvecResultat = computed(() => {
  let n = 0
  ;(props.programmeData?.objectifs ?? []).forEach((obj: any, oi: number) => {
    ;(obj.tests ?? []).forEach((test: any, ti: number) => {
      if (getResultat(obj.num, tRef(test, oi, ti))) n++
    })
  })
  return n
})
const totalOutilsLies = computed(() =>
  Object.values(props.outilsProcsMap).reduce((s, arr) => s + arr.length, 0)
)
const progressPct = computed(() =>
  totalTests.value ? Math.round(testsAvecResultat.value / totalTests.value * 100) : 0
)
</script>

<style scoped>
/* ── Base ────────────────────────────────────────────────────────── */
.ftt-wrap { padding: .6rem; display: flex; flex-direction: column; gap: .6rem; }

/* ── Synthèse ────────────────────────────────────────────────────── */
.ftt-summary {
  display: flex; align-items: center; gap: .65rem; flex-wrap: wrap;
  background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
  padding: .55rem .9rem; box-shadow: 0 1px 4px rgba(15,23,42,.07);
}
.ftt-sum-item { display: flex; flex-direction: column; align-items: center; min-width: 52px; }
.ftt-sum-n { font-size: 1.15rem; font-weight: 800; line-height: 1; color: #0f172a; }
.ftt-sum-lbl { font-size: .56rem; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; margin-top: 1px; }
.ftt-sum-item--total .ftt-sum-n { color: #1e40af; }
.ftt-sum-item--done .ftt-sum-n  { color: #065f46; }
.ftt-sum-item--outils .ftt-sum-n{ color: #6d28d9; }
.ftt-sum-item--prog .ftt-sum-n  { color: #b45309; }
.ftt-progress-bar {
  flex: 1; min-width: 80px; height: 5px; background: #e2e8f0; border-radius: 10px; overflow: hidden; align-self: center;
}
.ftt-progress-fill { height: 100%; background: linear-gradient(90deg, #1e40af, #6d28d9); border-radius: 10px; transition: width .4s; }

/* ── Vide ────────────────────────────────────────────────────────── */
.ftt-empty { text-align: center; padding: 3.5rem 2rem; background: #fff; border-radius: 12px; border: 2px dashed #e2e8f0; }
.ftt-empty__ico { font-size: 2rem; color: #f59e0b; margin-bottom: .65rem; }
.ftt-empty__title { font-size: .88rem; font-weight: 700; color: #0f172a; margin: 0 0 .3rem; }
.ftt-empty__sub { font-size: .73rem; color: #475569; max-width: 340px; margin: 0 auto; line-height: 1.6; }

/* ── Bloc objectif ───────────────────────────────────────────────── */
.ftt-obj-block {
  background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
  overflow: hidden; box-shadow: 0 1px 4px rgba(15,23,42,.06);
}
.ftt-obj-hdr {
  display: flex; align-items: center; gap: .4rem; padding: .5rem .8rem;
  background: linear-gradient(135deg, #eff6ff, #f0fdf4); border-bottom: 1px solid #e2e8f0;
  flex-wrap: wrap;
}
.ftt-obj-num {
  background: #1e40af; color: #fff; padding: 2px 8px; border-radius: 4px;
  font-size: .62rem; font-family: monospace; font-weight: 700; flex-shrink: 0;
}
.ftt-obj-label { font-size: .75rem; font-weight: 700; color: #1e40af; flex: 1; min-width: 0; }
.ftt-tag { display: inline-block; padding: 1px 7px; border-radius: 20px; font-size: .58rem; font-weight: 600; }
.ftt-tag--blue { background: #dbeafe; color: #1d4ed8; }
.ftt-prio--haute { background: #fee2e2; color: #dc2626; }
.ftt-prio--moyenne { background: #fef3c7; color: #d97706; }
.ftt-prio--faible { background: #d1fae5; color: #065f46; }
.ftt-obj-meta { margin-left: auto; font-size: .6rem; color: #94a3b8; white-space: nowrap; }

/* ── Ligne test ──────────────────────────────────────────────────── */
.ftt-test-row {
  position: relative;
  display: flex; align-items: center; gap: .55rem;
  padding: .55rem .75rem .55rem .6rem;
  border-bottom: 1px solid #f1f5f9;
  cursor: pointer; transition: background .1s;
  user-select: none;
}
.ftt-test-row:last-child { border-bottom: none; }
.ftt-test-row:hover { background: #f8fafc; }
.ftt-test-row--active { background: #eff6ff !important; border-left: 2px solid #2563eb; }
.ftt-test-row--conforme:hover { background: #f0fdf4 !important; }
.ftt-test-row--ecart:hover    { background: #fffbeb !important; }
.ftt-test-row--nc:hover       { background: #fff5f5 !important; }

/* Barre gauche couleur résultat */
.ftt-row-bar {
  position: absolute; left: 0; top: 0; bottom: 0; width: 3px;
  border-radius: 0 2px 2px 0; transition: background .15s;
}
.ftt-bar--conforme { background: #4ade80; }
.ftt-bar--ecart    { background: #fbbf24; }
.ftt-bar--nc       { background: #f87171; }
.ftt-bar--         { background: #e2e8f0; }

.ftt-ref {
  padding: 2px 7px; border-radius: 4px; font-size: .6rem; font-weight: 800;
  font-family: monospace; white-space: nowrap; flex-shrink: 0; color: #0f172a;
  transition: background .15s;
}
.ftt-row-info { flex: 1; min-width: 0; }
.ftt-test-lbl { font-size: .73rem; font-weight: 600; color: #0f172a; margin: 0 0 .18rem; line-height: 1.3; }
.ftt-row-chips { display: flex; gap: .2rem; flex-wrap: wrap; }
.ftt-chip {
  display: inline-flex; align-items: center; gap: 2px; padding: 1px 5px;
  border-radius: 20px; font-size: .55rem; font-weight: 500;
  background: #f1f5f9; border: 1px solid #e2e8f0; color: #475569;
}
.ftt-chip--cal  { background: #dbeafe; border-color: #bfdbfe; color: #1d4ed8; }
.ftt-chip--geo  { background: #d1fae5; border-color: #a7f3d0; color: #065f46; }
.ftt-chip--n    { background: #ede9fe; border-color: #ddd6fe; color: #6d28d9; }
.ftt-chip--outil {
  background: color-mix(in srgb, var(--oc) 12%, #fff);
  border-color: color-mix(in srgb, var(--oc) 35%, #e2e8f0);
  color: var(--oc); font-weight: 700;
}
.ftt-chip--note { background: #fef3c7; border-color: #fde68a; color: #92400e; }

.ftt-row-right {
  display: flex; align-items: center; gap: .35rem; flex-shrink: 0;
}
.ftt-res-pill {
  display: inline-block; padding: 2px 8px; border-radius: 20px;
  font-size: .62rem; font-weight: 700; white-space: nowrap;
  background: #f1f5f9; color: #64748b;
}
.ftt-res--conforme { background: #d1fae5; color: #065f46; }
.ftt-res--ecart    { background: #fef3c7; color: #92400e; }
.ftt-res--nc       { background: #fee2e2; color: #dc2626; }
.ftt-res--na       { background: #f1f5f9; color: #475569; }
.ftt-procs-count {
  display: inline-flex; align-items: center; gap: 2px;
  font-size: .58rem; color: #94a3b8; padding: 1px 5px;
  background: #f1f5f9; border-radius: 20px;
}
.ftt-arrow { font-size: .75rem; color: #94a3b8; transition: all .15s; flex-shrink: 0; }
.ftt-arrow--active { color: #2563eb; transform: rotate(90deg); }

/* ── Transition panneau ──────────────────────────────────────────── */
.ftd-slide-enter-active, .ftd-slide-leave-active { transition: all .22s ease; }
.ftd-slide-enter-from, .ftd-slide-leave-to { opacity: 0; }
</style>