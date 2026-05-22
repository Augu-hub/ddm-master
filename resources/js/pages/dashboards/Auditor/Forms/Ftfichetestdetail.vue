<template>
  <!-- ══ PANNEAU DÉTAIL TEST ══ style fiche IFACI ══════════════════════ -->
  <div class="ftd-overlay" @click.self="$emit('fermer')">
    <div class="ftd-panel">

      <!-- ── TOPBAR PANNEAU ─────────────────────────────────────── -->
      <div class="ftd-topbar">
        <div class="ftd-topbar__left">
          <code class="ftd-ref" :style="`background:${refColor}`">{{ testRef }}</code>
          <span class="ftd-status-dot" :class="`ftd-dot--${resultat}`"></span>
          <span class="ftd-status-lbl" :class="`ftd-lbl--${resultat}`">{{ resLbl(resultat) || 'Non saisi' }}</span>
        </div>
        <div class="ftd-topbar__right">
          <button v-if="!isLocked" class="ftd-btn ftd-btn--edit" @click="$emit('editerConstat')">
            <i class="ti ti-pencil"></i> Saisir résultat
          </button>
          <button class="ftd-close" @click="$emit('fermer')"><i class="ti ti-x"></i></button>
        </div>
      </div>

      <!-- ── CORPS ─────────────────────────────────────────────── -->
      <div class="ftd-body">

        <!-- ┌─ BLOC 1 : OBJECTIF D'AUDIT ─────────────────────────┐ -->
        <div class="ftd-section">
          <div class="ftd-section__hd">
            <i class="ti ti-target"></i>
            Objectif d'audit — {{ objNum }}
          </div>
          <div class="ftd-section__body">
            <p class="ftd-obj-text">{{ obj?.objectif || '—' }}</p>
          </div>
        </div>

        <!-- ┌─ BLOC 2 : AUDITEUR / DATE ───────────────────────────┐ -->
        <div class="ftd-grid2">
          <div class="ftd-section ftd-section--sm">
            <div class="ftd-section__hd"><i class="ti ti-user-check"></i> Auditeur interne</div>
            <div class="ftd-section__body">
              <p class="ftd-val">{{ auditeurNom || '—' }}</p>
            </div>
          </div>
          <div class="ftd-section ftd-section--sm">
            <div class="ftd-section__hd"><i class="ti ti-calendar"></i> Date</div>
            <div class="ftd-section__body">
              <p class="ftd-val">{{ dateAudit || '—' }}</p>
            </div>
          </div>
        </div>

        <!-- ┌─ BLOC 3 : SOURCE D'INFORMATIONS ────────────────────┐ -->
        <div class="ftd-section">
          <div class="ftd-section__hd"><i class="ti ti-database"></i> Sources d'informations</div>
          <div class="ftd-section__body">
            <div v-if="test?.sources?.length" class="ftd-list">
              <div v-for="(src, i) in test.sources" :key="i" class="ftd-list-item">
                <span class="ftd-bullet">—</span>{{ src }}
              </div>
            </div>
            <div v-else-if="test?.lieu || test?.periode_testee || test?.taille_echantillon" class="ftd-list">
              <div v-if="test.lieu" class="ftd-list-item"><span class="ftd-bullet">—</span>Lieu : {{ test.lieu }}</div>
              <div v-if="test.periode_testee" class="ftd-list-item"><span class="ftd-bullet">—</span>Période testée : {{ test.periode_testee }}</div>
              <div v-if="test.taille_echantillon" class="ftd-list-item"><span class="ftd-bullet">—</span>Taille d'échantillon : {{ test.taille_echantillon }}</div>
            </div>
            <p v-else class="ftd-empty-row">—</p>
          </div>
        </div>

        <!-- ┌─ BLOC 4 : TESTS D'AUDIT ────────────────────────────┐ -->
        <div class="ftd-section">
          <div class="ftd-section__hd">
            <i class="ti ti-checklist"></i>
            Tests d'audit — {{ testRef }}
            <span class="ftd-badge">{{ test?.libelle ? '1' : '0' }} test · {{ procedures.length }} procédure(s)</span>
          </div>
          <div class="ftd-section__body">
            <!-- Libellé du test -->
            <div class="ftd-test-libelle">
              <span class="ftd-bullet">—</span>
              <span>{{ test?.libelle || '—' }}</span>
            </div>

            <!-- Procédures avec outils liés -->
            <div v-if="procedures.length" class="ftd-procs-wrap">
              <div
                v-for="(proc, pi) in procedures"
                :key="pi"
                class="ftd-proc-row"
                :class="getOutilsForProc(pi).length ? 'ftd-proc-row--linked' : ''"
              >
                <div class="ftd-proc-left">
                  <span class="ftd-bullet">—</span>
                  <span class="ftd-proc-txt">{{ proc }}</span>
                </div>
                <div class="ftd-proc-outils">
                  <button
                    v-for="code in getOutilsForProc(pi)"
                    :key="code"
                    class="ftd-outil-tag"
                    :style="`--ot:${outilColor(code)}`"
                    :title="`Ouvrir l'outil ${code}`"
                    @click="$emit('ouvrirOutil', obj, test, code, pi)"
                  >
                    <i class="ti ti-tool"></i>
                    <span class="ftd-outil-code">{{ code }}</span>
                    <span class="ftd-outil-lbl">{{ outilLabel(code) }}</span>
                    <i class="ti ti-arrow-right ftd-outil-arrow"></i>
                  </button>
                  <button
                    v-if="!isLocked"
                    class="ftd-proc-link-btn"
                    @click="$emit('choixOutil', obj, test, pi)"
                    :title="getOutilsForProc(pi).length ? 'Ajouter un outil' : 'Lier un outil IFACI'"
                  >
                    <i class="ti ti-link"></i>
                    {{ getOutilsForProc(pi).length ? 'Ajouter' : 'Lier outil' }}
                  </button>
                </div>
              </div>
            </div>
            <p v-else class="ftd-empty-row">Aucune procédure définie pour ce test.</p>
          </div>
        </div>

        <!-- ┌─ BLOC 5 : RÉSULTATS DU TEST D'AUDIT ───────────────┐ -->
        <div class="ftd-section ftd-section--result" :class="`ftd-section--${resultat}`">
          <div class="ftd-section__hd">
            <i class="ti ti-clipboard-check"></i>
            Résultats du test d'audit
          </div>
          <div class="ftd-section__body">
            <!-- Sélecteur résultat -->
            <div class="ftd-result-row">
              <span class="ftd-result-lbl">Résultat :</span>
              <select
                v-if="!isLocked"
                class="ftd-sel-result"
                :class="`ftd-sel--${resultat}`"
                :value="resultat"
                @change="$emit('setResultat', objNum, testRef, ($event.target as HTMLSelectElement).value)"
              >
                <option value="">— Sélectionner —</option>
                <option value="conforme">✅ Conforme</option>
                <option value="ecart">⚠️ Écart</option>
                <option value="nc">❌ Non conforme</option>
                <option value="na">N/A</option>
              </select>
              <span v-else class="ftd-res-pill" :class="`ftd-res--${resultat}`">
                {{ resLbl(resultat) || '—' }}
              </span>
            </div>

            <!-- Constat -->
            <div class="ftd-constat-wrap">
              <div v-if="constat" class="ftd-list">
                <div class="ftd-list-item">
                  <span class="ftd-bullet">—</span>
                  <span class="ftd-constat-text">{{ constat }}</span>
                </div>
              </div>
              <div v-else-if="!isLocked" class="ftd-constat-empty">
                <i class="ti ti-note-off"></i> Aucun constat saisi
                <button class="ftd-btn-inline" @click="$emit('editerConstat')">Saisir</button>
              </div>
              <p v-else class="ftd-empty-row">—</p>
            </div>

            <!-- Preuve -->
            <div v-if="preuve" class="ftd-preuve-row">
              <i class="ti ti-paperclip"></i>
              <span>{{ preuve }}</span>
            </div>
          </div>
        </div>

        <!-- ┌─ BLOC 6 : OUTILS IFACI UTILISÉS ───────────────────┐ -->
        <div v-if="allOutilsCodes.length" class="ftd-section">
          <div class="ftd-section__hd">
            <i class="ti ti-tools"></i>
            Outils IFACI utilisés
            <span class="ftd-badge">{{ allOutilsCodes.length }}</span>
          </div>
          <div class="ftd-section__body ftd-outils-grid">
            <button
              v-for="code in allOutilsCodes"
              :key="code"
              class="ftd-outil-card"
              :style="`--oc:${outilColor(code)}`"
              @click="$emit('ouvrirOutil', obj, test, code, 0)"
            >
              <span class="ftd-outil-card__code">{{ code }}</span>
              <span class="ftd-outil-card__lbl">{{ outilLabel(code) }}</span>
              <span class="ftd-outil-card__sub">{{ outilSubtitle(code) }}</span>
              <i class="ti ti-arrow-right ftd-outil-card__arrow"></i>
            </button>
          </div>
        </div>

        <!-- ┌─ BOUTONS DE PIED ───────────────────────────────────┐ -->
        <div class="ftd-footer">
          <button class="ftd-btn ftd-btn--ghost" @click="$emit('fermer')">
            <i class="ti ti-arrow-left"></i> Retour
          </button>
          <div class="ftd-footer-right">
            <button v-if="!isLocked" class="ftd-btn ftd-btn--constat" @click="$emit('editerConstat')">
              <i class="ti ti-pencil"></i> Saisir constat & preuve
            </button>
            <button v-if="!isLocked && !allOutilsCodes.length" class="ftd-btn ftd-btn--outil" @click="$emit('choixOutil', obj, test, 0)">
              <i class="ti ti-tool"></i> Ajouter un outil
            </button>
          </div>
        </div>

      </div><!-- /ftd-body -->
    </div><!-- /ftd-panel -->
  </div><!-- /ftd-overlay -->
</template>

<script setup lang="ts">
import { computed } from 'vue'

// ── Props ──────────────────────────────────────────────────────────────────
const props = defineProps<{
  obj: any
  test: any
  testRef: string
  objNum: string
  auditeurNom?: string
  dateAudit?: string
  missionLibelle?: string
  codeMission?: string
  outilsIfaci?: { code: string; label: string; icon: string; color: string }[]
  /** clé: "objNum::testRef::procIdx" → string[] */
  outilsProcsMap: Record<string, string[]>
  resultatsMap: Record<string, { resultat: string; constat: string; preuve: string }>
  isLocked?: boolean
}>()

defineEmits([
  'fermer',
  'editerConstat',
  'setResultat',
  'ouvrirOutil',   // (obj, test, code, procIdx)
  'choixOutil',    // (obj, test, procIdx)
])

// ── Computed data ────────────────────────────────────────────────────────
const procedures = computed<string[]>(() => props.test?.procedures ?? [])

const resultat = computed(() => props.resultatsMap[`${props.objNum}::${props.testRef}`]?.resultat ?? '')
const constat  = computed(() => props.resultatsMap[`${props.objNum}::${props.testRef}`]?.constat  ?? '')
const preuve   = computed(() => props.resultatsMap[`${props.objNum}::${props.testRef}`]?.preuve   ?? '')

function pKey(pi: number): string {
  return `${props.objNum}::${props.testRef}::${pi}`
}
function getOutilsForProc(pi: number): string[] {
  return props.outilsProcsMap[pKey(pi)] ?? []
}

/** Tous les codes outils distincts liés à ce test */
const allOutilsCodes = computed<string[]>(() => {
  const prefix = `${props.objNum}::${props.testRef}::`
  const codes = new Set<string>()
  Object.entries(props.outilsProcsMap).forEach(([k, v]) => {
    if (k.startsWith(prefix)) v.forEach(c => codes.add(c))
  })
  return [...codes]
})

// ── UI helpers ────────────────────────────────────────────────────────────
function outilColor(code: string): string {
  return props.outilsIfaci?.find(o => o.code === code)?.color ?? '#374151'
}
function outilLabel(code: string): string {
  return props.outilsIfaci?.find(o => o.code === code)?.label ?? code
}
function outilSubtitle(code: string): string {
  const map: Record<string, string> = {
    I: "Collecte d'informations & preuves",
    II: 'Séparation des fonctions',
    III: 'Représentation graphique · ISO 5807',
    IV: 'Objectifs / Risques / Contrôles',
    V: 'Suivi transaction t-6 → t',
    VI: 'Hiérarchisation P×I',
    VII: 'Matrice COSO',
    VIII: 'Ishikawa 5M+2',
    IX: 'QCI · COSO',
    X: 'Génération d\'idées',
    XI: 'Traçabilité chaînage',
    XII: 'Confirmation tiers',
    XIII: 'Comparaisons / Ratios',
    XIV: 'Observation terrain',
    XV: 'Sondage statistique',
  }
  return map[code] ?? ''
}
function resLbl(r: string): string {
  return ({ conforme: '✅ Conforme', ecart: '⚠️ Écart', nc: '❌ Non conforme', na: 'N/A' } as any)[r] ?? ''
}
const refColor = computed(() => {
  const r = resultat.value
  if (r === 'conforme') return '#a7f3d0'
  if (r === 'ecart')    return '#fde68a'
  if (r === 'nc')       return '#fca5a5'
  return '#ddd6fe'
})
</script>

<style scoped>
/* ── Overlay & panneau ───────────────────────────────────────────── */
.ftd-overlay {
  position: fixed; inset: 0; z-index: 1100;
  background: rgba(10, 15, 30, .45); backdrop-filter: blur(3px);
  display: flex; align-items: flex-start; justify-content: flex-end;
}
.ftd-panel {
  width: min(640px, 96vw); height: 100vh; background: #fff;
  display: flex; flex-direction: column; overflow: hidden;
  box-shadow: -8px 0 40px rgba(10, 15, 30, .18);
  animation: ftd-slide-in .22s ease;
}
@keyframes ftd-slide-in {
  from { transform: translateX(30px); opacity: 0; }
  to   { transform: translateX(0);    opacity: 1; }
}

/* ── Topbar ──────────────────────────────────────────────────────── */
.ftd-topbar {
  display: flex; align-items: center; justify-content: space-between;
  gap: .5rem; padding: .6rem .9rem;
  background: #0f172a; flex-shrink: 0;
}
.ftd-topbar__left { display: flex; align-items: center; gap: .5rem; }
.ftd-topbar__right { display: flex; align-items: center; gap: .35rem; }
.ftd-ref {
  padding: 3px 10px; border-radius: 5px; font-size: .72rem;
  font-weight: 800; font-family: monospace; color: #0f172a;
}
.ftd-status-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.ftd-dot--conforme { background: #4ade80; }
.ftd-dot--ecart    { background: #fbbf24; }
.ftd-dot--nc       { background: #f87171; }
.ftd-dot--na, .ftd-dot-- { background: #94a3b8; }
.ftd-status-lbl { font-size: .68rem; font-weight: 600; }
.ftd-lbl--conforme { color: #4ade80; }
.ftd-lbl--ecart    { color: #fbbf24; }
.ftd-lbl--nc       { color: #f87171; }
.ftd-lbl-- { color: #94a3b8; }
.ftd-close {
  display: inline-flex; align-items: center; justify-content: center;
  width: 28px; height: 28px; background: rgba(255,255,255,.1);
  border: 1px solid rgba(255,255,255,.2); border-radius: 6px;
  color: #fff; cursor: pointer; font-size: .8rem; transition: background .12s;
}
.ftd-close:hover { background: rgba(255,255,255,.22); }

/* ── Corps scrollable ────────────────────────────────────────────── */
.ftd-body {
  flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 0;
  background: #f8fafc;
}
.ftd-body::-webkit-scrollbar { width: 4px; }
.ftd-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 2px; }

/* ── Sections style tableau IFACI ────────────────────────────────── */
.ftd-section {
  border-bottom: 1px solid #e2e8f0;
}
.ftd-section--sm { flex: 1; }
.ftd-grid2 { display: flex; border-bottom: 1px solid #e2e8f0; }
.ftd-grid2 .ftd-section { border-bottom: none; border-right: 1px solid #e2e8f0; }
.ftd-grid2 .ftd-section:last-child { border-right: none; }

.ftd-section__hd {
  display: flex; align-items: center; gap: .4rem; flex-wrap: wrap;
  padding: .45rem .9rem;
  background: #1e3a5f; color: #fff;
  font-size: .72rem; font-weight: 700; letter-spacing: .01em;
}
/* Alterner couleurs des en-têtes */
.ftd-section:nth-child(odd) .ftd-section__hd  { background: #1e3a5f; }
.ftd-section:nth-child(even) .ftd-section__hd { background: #1e3a5f; }
.ftd-grid2 .ftd-section .ftd-section__hd { background: #1e3a5f; }

.ftd-section__body {
  padding: .6rem .9rem; background: #fff; min-height: 36px;
}

/* ── Contenu ─────────────────────────────────────────────────────── */
.ftd-obj-text { font-size: .75rem; color: #0f172a; line-height: 1.55; margin: 0; }
.ftd-val { font-size: .78rem; color: #0f172a; font-weight: 600; margin: 0; }
.ftd-empty-row { font-size: .7rem; color: #94a3b8; font-style: italic; margin: 0; }

.ftd-list { display: flex; flex-direction: column; gap: .25rem; }
.ftd-list-item {
  display: flex; align-items: flex-start; gap: .4rem;
  font-size: .73rem; color: #0f172a; line-height: 1.45;
}
.ftd-bullet { flex-shrink: 0; color: #64748b; font-weight: 600; margin-top: 1px; }

/* Test libellé */
.ftd-test-libelle {
  display: flex; align-items: flex-start; gap: .4rem;
  font-size: .73rem; font-weight: 600; color: #0f172a;
  margin-bottom: .5rem; padding-bottom: .5rem;
  border-bottom: 1px dashed #e2e8f0;
}

/* ── Procédures ──────────────────────────────────────────────────── */
.ftd-procs-wrap { display: flex; flex-direction: column; gap: .3rem; margin-top: .2rem; }
.ftd-proc-row {
  display: flex; align-items: flex-start; gap: .4rem;
  padding: .3rem .4rem; border-radius: 6px; border: 1px solid transparent;
  transition: all .12s;
}
.ftd-proc-row:hover { background: #f8fafc; border-color: #e2e8f0; }
.ftd-proc-row--linked { background: #f0fdf4; border-color: #bbf7d0 !important; }
.ftd-proc-left {
  display: flex; align-items: flex-start; gap: .35rem; flex: 1; min-width: 0;
}
.ftd-proc-txt { font-size: .71rem; color: #334155; line-height: 1.4; }
.ftd-proc-outils {
  display: flex; align-items: center; gap: .25rem; flex-wrap: wrap; flex-shrink: 0;
}

/* Tag outil dans procédure */
.ftd-outil-tag {
  display: inline-flex; align-items: center; gap: .2rem;
  padding: 3px 8px 3px 6px;
  background: color-mix(in srgb, var(--ot) 10%, #fff);
  border: 1.5px solid color-mix(in srgb, var(--ot) 35%, #e2e8f0);
  color: var(--ot); border-radius: 6px;
  font-size: .62rem; font-weight: 700; cursor: pointer;
  transition: all .12s; white-space: nowrap;
}
.ftd-outil-tag:hover {
  background: color-mix(in srgb, var(--ot) 18%, #fff);
  transform: translateY(-1px); box-shadow: 0 2px 8px rgba(0,0,0,.1);
}
.ftd-outil-code { font-family: monospace; }
.ftd-outil-lbl { font-weight: 400; font-size: .58rem; max-width: 80px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ftd-outil-arrow { font-size: .52rem; opacity: .6; }

/* Bouton lier outil procédure */
.ftd-proc-link-btn {
  display: inline-flex; align-items: center; gap: .2rem; padding: 3px 7px;
  background: #eff6ff; border: 1.5px dashed #93c5fd; color: #1d4ed8;
  border-radius: 5px; font-size: .6rem; font-weight: 600; cursor: pointer;
  transition: all .12s; white-space: nowrap;
}
.ftd-proc-link-btn:hover { background: #dbeafe; border-style: solid; }

/* ── Résultats ───────────────────────────────────────────────────── */
.ftd-section--result.ftd-section--conforme .ftd-section__body { background: #f0fdf4; }
.ftd-section--result.ftd-section--ecart    .ftd-section__body { background: #fffbeb; }
.ftd-section--result.ftd-section--nc       .ftd-section__body { background: #fff5f5; }

.ftd-result-row {
  display: flex; align-items: center; gap: .6rem;
  padding-bottom: .5rem; margin-bottom: .5rem;
  border-bottom: 1px dashed #e2e8f0;
}
.ftd-result-lbl { font-size: .65rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .04em; flex-shrink: 0; }
.ftd-sel-result {
  border: 1.5px solid #e2e8f0; border-radius: 6px; padding: 4px 8px;
  font-size: .7rem; color: #0f172a; background: #fff; outline: none; cursor: pointer;
}
.ftd-sel--conforme { background: #f0fdf4; border-color: #a7f3d0; color: #065f46; font-weight: 700; }
.ftd-sel--ecart    { background: #fffbeb; border-color: #fde68a; color: #92400e; font-weight: 700; }
.ftd-sel--nc       { background: #fff5f5; border-color: #fecaca; color: #dc2626; font-weight: 700; }
.ftd-res-pill {
  display: inline-block; padding: 3px 10px; border-radius: 20px;
  font-size: .66rem; font-weight: 700;
}
.ftd-res--conforme { background: #d1fae5; color: #065f46; }
.ftd-res--ecart    { background: #fef3c7; color: #92400e; }
.ftd-res--nc       { background: #fee2e2; color: #dc2626; }
.ftd-res--na       { background: #f1f5f9; color: #475569; }

.ftd-constat-wrap { min-height: 32px; }
.ftd-constat-text { font-size: .72rem; color: #0f172a; line-height: 1.5; white-space: pre-wrap; }
.ftd-constat-empty {
  display: flex; align-items: center; gap: .4rem;
  font-size: .68rem; color: #94a3b8; font-style: italic; padding: .3rem 0;
}
.ftd-btn-inline {
  padding: 2px 8px; background: #eff6ff; border: 1px solid #bfdbfe;
  color: #1d4ed8; border-radius: 5px; font-size: .62rem; font-weight: 600; cursor: pointer;
}
.ftd-preuve-row {
  display: flex; align-items: center; gap: .3rem; margin-top: .5rem;
  padding-top: .45rem; border-top: 1px dashed #e2e8f0;
  font-size: .67rem; color: #0369a1;
}

/* ── Grid outils ──────────────────────────────────────────────────── */
.ftd-outils-grid {
  display: flex; flex-direction: column; gap: .3rem; padding: .6rem .9rem !important;
}
.ftd-outil-card {
  display: flex; align-items: center; gap: .55rem;
  padding: .45rem .65rem;
  background: #fff;
  border: 1.5px solid color-mix(in srgb, var(--oc) 25%, #e2e8f0);
  border-radius: 8px; cursor: pointer; text-align: left;
  transition: all .12s;
}
.ftd-outil-card:hover {
  background: color-mix(in srgb, var(--oc) 6%, #fff);
  border-color: var(--oc);
  transform: translateX(2px);
}
.ftd-outil-card__code {
  min-width: 28px; height: 24px; display: flex; align-items: center; justify-content: center;
  background: var(--oc); color: #fff; border-radius: 5px;
  font-size: .65rem; font-weight: 800; font-family: monospace; flex-shrink: 0;
}
.ftd-outil-card__lbl {
  font-size: .72rem; font-weight: 700; color: #0f172a; flex: 1;
}
.ftd-outil-card__sub {
  font-size: .6rem; color: #64748b; white-space: nowrap;
}
.ftd-outil-card__arrow { font-size: .65rem; color: var(--oc); margin-left: .2rem; }

/* ── Badge ───────────────────────────────────────────────────────── */
.ftd-badge {
  display: inline-block; padding: 1px 8px; border-radius: 20px;
  background: rgba(255,255,255,.2); color: #fff;
  font-size: .58rem; font-weight: 600;
}

/* ── Footer ──────────────────────────────────────────────────────── */
.ftd-footer {
  display: flex; align-items: center; justify-content: space-between;
  gap: .5rem; padding: .7rem .9rem;
  background: #fff; border-top: 1px solid #e2e8f0; flex-shrink: 0;
}
.ftd-footer-right { display: flex; gap: .4rem; }
.ftd-btn {
  display: inline-flex; align-items: center; gap: .3rem; padding: 5px 12px;
  border-radius: 7px; font-size: .7rem; font-weight: 600; border: 1px solid transparent;
  cursor: pointer; transition: all .12s;
}
.ftd-btn--ghost { background: transparent; border-color: #e2e8f0; color: #475569; }
.ftd-btn--ghost:hover { background: #f1f5f9; }
.ftd-btn--edit { background: rgba(255,255,255,.15); border-color: rgba(255,255,255,.25); color: #fff; font-size: .66rem; }
.ftd-btn--constat { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
.ftd-btn--constat:hover { background: #dbeafe; }
.ftd-btn--outil { background: #ede9fe; border-color: #ddd6fe; color: #6d28d9; }
.ftd-btn--outil:hover { background: #ddd6fe; }
</style>