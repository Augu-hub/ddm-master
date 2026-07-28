<template>
  <VerticalLayout>
    <div class="page">

      <!-- HEADER -->
      <div class="page-hdr">
        <div class="page-hdr-left">
          <div class="hdr-icon"><i class="ti ti-map-2"></i></div>
          <div>
            <h1>Cartographie des risques</h1>
            <p>Vue inhérente, résiduelle et cible — par activité, processus, sélection ou entité</p>
          </div>
        </div>
        <div class="page-hdr-right">
          <select v-if="entities.length" v-model="activeEntity" class="cfg-sel">
            <option :value="null">Toutes les entités</option>
            <option v-for="e in entities" :key="e.id" :value="e.id">{{ e.name }} ({{ e.count }})</option>
          </select>
          <span v-if="activeCfg" class="cfg-fixed" title="Matrice active (appliquée partout)"><i class="ti ti-grid-dots"></i> {{ activeCfg.name }} ({{ activeCfg.matrix_label }})</span>
          <Link :href="route('risk.core.eval-sessions.index')" class="btn-ghost"><i class="ti ti-versions"></i> Sessions</Link>
          <Link :href="route('risk.core.evaluation.cible')" class="btn-ghost"><i class="ti ti-arrow-left"></i> Évaluation</Link>
        </div>
      </div>

      <!-- BANDEAU SESSION GELÉE -->
      <div v-if="sessionMeta" class="frozen-banner">
        <i class="ti ti-snowflake"></i>
        Cartographie figée de la session <strong>{{ sessionMeta.name }}</strong>
        <span v-if="sessionMeta.year">· {{ sessionMeta.year }}</span>
        <span v-if="sessionMeta.snapshot_at" class="fb-date">gelée le {{ sessionMeta.snapshot_at }}</span>
        <Link :href="route('risk.core.evaluation.cartographie')" class="fb-live"><i class="ti ti-broadcast"></i> Voir l'état actuel</Link>
      </div>

      <!-- BARRE DE CONTRÔLES -->
      <div class="ctrl-bar">
        <div class="ctrl-grp">
          <span class="ctrl-lbl">Maille</span>
          <div class="seg">
            <button v-for="m in mailles" :key="m.key" :class="['seg-btn', maille===m.key?'seg-btn--on':'']" @click="maille=m.key">
              <i :class="m.icon"></i> {{ m.label }}
            </button>
          </div>
        </div>
        <div class="ctrl-grp">
          <span class="ctrl-lbl">Vue matrice</span>
          <div class="seg">
            <button v-for="l in layers" :key="l.key" :class="['seg-btn', layer===l.key?'seg-btn--on':'']" :style="layer===l.key?{background:l.color,borderColor:l.color,color:'#fff'}:{}" @click="layer=l.key">
              <i :class="l.icon"></i> {{ l.label }}
            </button>
          </div>
        </div>
        <div class="ctrl-grp">
          <span class="ctrl-lbl">Affichage</span>
          <div class="seg">
            <button :class="['seg-btn', viewMode==='simple'?'seg-btn--on':'']" @click="viewMode='simple'"><i class="ti ti-square"></i> Simple</button>
            <button :class="['seg-btn', viewMode==='multiple'?'seg-btn--on':'']" @click="viewMode='multiple'"><i class="ti ti-layout-grid"></i> Multiple I/R/Cible</button>
          </div>
        </div>
        <div class="ctrl-grp">
          <label class="switch">
            <input type="checkbox" v-model="showTrajectory" />
            <span>Trajectoire de synthèse I→R→Cible</span>
          </label>
        </div>
        <div class="ctrl-grp ctrl-grp--right">
          <button v-if="maille==='serie'" class="btn-ghost" @click="serieOpen=!serieOpen">
            <i class="ti ti-list-check"></i> Sélection ({{ selectedActivityIds.length }})
          </button>
        </div>
      </div>

      <!-- PANNEAU SÉLECTION (maille = série) -->
      <Transition name="slide">
        <div v-if="maille==='serie' && serieOpen" class="serie-panel">
          <div class="serie-head">
            <strong><i class="ti ti-list-check"></i> Série d'éléments à cartographier</strong>
            <div class="serie-actions">
              <button class="mini-btn" @click="selectAllActivities">Tout</button>
              <button class="mini-btn" @click="selectedActivityIds=[]">Aucun</button>
            </div>
          </div>
          <div class="serie-tree">
            <div v-for="macro in tree" :key="macro.id" class="serie-macro">
              <div class="serie-macro-name">{{ macro.name }}</div>
              <div v-for="proc in macro.processes" :key="proc.id" class="serie-proc">
                <label class="serie-proc-name">
                  <input type="checkbox" :checked="procFullySelected(proc)" @change="toggleProc(proc, $event.target.checked)" />
                  <span class="pcode">{{ proc.code }}</span> {{ proc.name }}
                </label>
                <div class="serie-acts">
                  <label v-for="act in proc.activities" :key="act.id" class="serie-act">
                    <input type="checkbox" :value="act.id" v-model="selectedActivityIds" />
                    <span class="acode">{{ act.code }}</span> {{ act.name }}
                    <span class="acount">{{ act.risks.length }}</span>
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Transition>

      <!-- STAT TILES -->
      <div class="tiles">
        <div class="tile">
          <div class="tile-ic" style="background:#0f172a"><i class="ti ti-alert-triangle"></i></div>
          <div><div class="tile-num">{{ scopedRisks.length }}</div><div class="tile-lbl">Risques cartographiés</div></div>
        </div>
        <div class="tile">
          <div class="tile-ic" style="background:#ef4444"><i class="ti ti-shield-bolt"></i></div>
          <div><div class="tile-num">{{ fmt(avg('inherent')) }}</div><div class="tile-lbl">Criticité inhérente moy.</div></div>
        </div>
        <div class="tile">
          <div class="tile-ic" style="background:#f59e0b"><i class="ti ti-shield-half"></i></div>
          <div><div class="tile-num">{{ fmt(avg('residual')) }}</div><div class="tile-lbl">Criticité résiduelle moy.</div></div>
        </div>
        <div class="tile">
          <div class="tile-ic" style="background:#10b981"><i class="ti ti-target-arrow"></i></div>
          <div><div class="tile-num">{{ fmt(avg('target')) }}</div><div class="tile-lbl">Criticité cible moy.</div></div>
        </div>
        <div class="tile">
          <div class="tile-ic" :style="{background: reductionPct>=0 ? '#0d9488' : '#ef4444'}"><i class="ti ti-trending-down"></i></div>
          <div><div class="tile-num">{{ reductionPct>=0?'−':'+' }}{{ Math.abs(reductionPct) }}%</div><div class="tile-lbl">Réduction visée (I→Cible)</div></div>
        </div>
      </div>

      <!-- CONTENU PRINCIPAL -->
      <div class="grid">

        <!-- MATRICE -->
        <div class="card card--matrix">
          <div class="card-hdr">
            <strong><i class="ti ti-grid-dots"></i> Matrice de criticité — <span :style="{color:currentLayer.color}">{{ currentLayer.label }}</span></strong>
            <span class="card-sub">{{ scopedRisks.length }} risque(s) · scope : {{ mailleLabel }}</span>
          </div>
          <div class="card-body">
            <div v-if="!matrixData" class="empty"><i class="ti ti-settings-off"></i> Aucune configuration de matrice active.</div>
            <div v-else-if="viewMode==='simple'" class="matrix-wrap">
              <!-- axe X -->
              <div class="axis-x">Fréquence / Vraisemblance →</div>
              <div class="matrix-flex">
                <div class="axis-y"><span>↑ Impact / Gravité</span></div>
                <table class="cmatrix">
                  <thead>
                    <tr>
                      <th class="corner"></th>
                      <th v-for="f in freqCols" :key="f.id">
                        <span class="axbadge" :style="{background:f.color_code||'#64748b'}">{{ f.label }}</span>
                        <span class="axscore">×{{ f.score }}</span>
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="im in impactRows" :key="im.id">
                      <td class="rowhd">
                        <span class="axbadge" :style="{background:im.color_code||'#64748b'}">{{ im.label }}</span>
                        <span class="axscore">×{{ im.score }}</span>
                      </td>
                      <td v-for="f in freqCols" :key="f.id"
                          class="ccell"
                          :style="{ background: cellColor(im,f) }"
                          @mouseenter="hoverCell = cellKey(im,f)" @mouseleave="hoverCell=null">
                        <span class="cell-score">{{ im.score * f.score }}</span>
                        <!-- SYNTHÈSE : 3 pastilles I / R / Cible ensemble -->
                        <div v-if="layer==='synthese'" class="syn-chips">
                          <span v-if="cellRisksStage(im,f,'inherent').length" class="syn-chip" :style="{background:IRC_COLORS.inherent}" :title="`Inhérent : ${cellRisksStage(im,f,'inherent').length}`">{{ cellRisksStage(im,f,'inherent').length }}</span>
                          <span v-if="cellRisksStage(im,f,'residual').length" class="syn-chip" :style="{background:IRC_COLORS.residual}" :title="`Résiduel : ${cellRisksStage(im,f,'residual').length}`">{{ cellRisksStage(im,f,'residual').length }}</span>
                          <span v-if="cellRisksStage(im,f,'target').length" class="syn-chip" :style="{background:IRC_COLORS.target}" :title="`Cible : ${cellRisksStage(im,f,'target').length}`">{{ cellRisksStage(im,f,'target').length }}</span>
                        </div>
                        <!-- comptage risques dans la cellule (stade unique) -->
                        <span v-else-if="cellRisks(im,f).length" class="cell-count">{{ cellRisks(im,f).length }}</span>
                        <!-- tooltip -->
                        <div v-if="layer!=='synthese' && hoverCell===cellKey(im,f) && cellRisks(im,f).length" class="cell-tip">
                          <div class="tip-hd">{{ cellRisks(im,f).length }} risque(s) · {{ im.score*f.score }}</div>
                          <div v-for="r in cellRisks(im,f).slice(0,8)" :key="r.id" class="tip-row">
                            <span class="tip-code">{{ r.code_risk }}</span> {{ truncate(r.libelle,42) }}
                          </div>
                          <div v-if="cellRisks(im,f).length>8" class="tip-more">+{{ cellRisks(im,f).length-8 }} autres…</div>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Trajectoire de synthèse (superposition) -->
              <div v-if="showTrajectory && trajectory.length>1" class="traj">
                <div class="traj-title"><i class="ti ti-route"></i> Trajectoire moyenne du risque</div>
                <div class="traj-line">
                  <template v-for="(p,i) in trajectory" :key="p.key">
                    <div class="traj-node" :style="{borderColor:p.color}">
                      <span class="traj-shape" :style="{background:p.color}"><i :class="p.icon"></i></span>
                      <div class="traj-meta">
                        <div class="traj-name" :style="{color:p.color}">{{ p.label }}</div>
                        <div class="traj-score">I {{ fmt(p.impact) }} × F {{ fmt(p.freq) }} = <b>{{ fmt(p.score) }}</b></div>
                      </div>
                    </div>
                    <div v-if="i<trajectory.length-1" class="traj-arrow">
                      <i class="ti ti-arrow-narrow-right"></i>
                      <span :class="['traj-delta', trajectory[i+1].score<=p.score?'down':'up']">
                        {{ trajectory[i+1].score<=p.score?'−':'+' }}{{ fmt(Math.abs(trajectory[i+1].score-p.score)) }}
                      </span>
                    </div>
                  </template>
                </div>
              </div>

              <!-- Légende I/R/Cible (mode synthèse) -->
              <div v-if="layer==='synthese'" class="irc-legend">
                <span class="irc"><i class="irc-dot" :style="{background:IRC_COLORS.inherent}"></i>Inhérent</span>
                <span class="irc"><i class="irc-dot" :style="{background:IRC_COLORS.residual}"></i>Résiduel</span>
                <span class="irc"><i class="irc-dot" :style="{background:IRC_COLORS.target}"></i>Cible</span>
                <span class="irc-note">Chaque risque est positionné à ses 3 stades sur la même matrice.</span>
              </div>

              <!-- Légende zones -->
              <div class="zlegend">
                <span v-for="z in sortedZones" :key="z.id" class="zchip">
                  <i class="zdot" :style="{background:z.color_code}"></i>{{ z.label }}
                  <span class="zrange">{{ z.min_score }}–{{ z.max_score }}</span>
                </span>
              </div>
            </div>

            <!-- VUE MULTIPLE : Inhérent / Résiduel / Cible côte à côte -->
            <div v-else class="multi-wrap">
              <div v-for="st in stagesMeta" :key="st.key" class="multi-col">
                <div class="multi-title" :style="{color:st.color}"><i :class="st.icon"></i> {{ st.label }}</div>
                <table class="cmatrix cmatrix--mini">
                  <tbody>
                    <tr v-for="im in impactRows" :key="im.id">
                      <td class="rowhd-mini">{{ im.score }}</td>
                      <td v-for="f in freqCols" :key="f.id" class="ccell ccell--mini" :style="{ background: cellColor(im,f) }"
                          :title="`I${im.score}×F${f.score} — ${cellRisksStage(im,f,st.key).length} risque(s)`">
                        <span v-if="cellRisksStage(im,f,st.key).length" class="cell-count cell-count--mini">{{ cellRisksStage(im,f,st.key).length }}</span>
                      </td>
                    </tr>
                    <tr class="mini-foot"><td></td><td v-for="f in freqCols" :key="f.id">{{ f.score }}</td></tr>
                  </tbody>
                </table>
              </div>
              <div class="zlegend multi-legend">
                <span v-for="z in sortedZones" :key="z.id" class="zchip"><i class="zdot" :style="{background:z.color_code}"></i>{{ z.label }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- ÉVOLUTION DU PROFIL (barres empilées par zone) -->
        <div class="card">
          <div class="card-hdr">
            <strong><i class="ti ti-chart-bar"></i> Évolution du profil</strong>
            <span class="card-sub">Répartition par zone à chaque stade</span>
          </div>
          <div class="card-body">
            <apexchart v-if="matrixData" type="bar" height="260" :options="evoOptions" :series="evoSeries" />
            <div class="mini-legend">
              <span v-for="z in sortedZones" :key="z.id" class="ml-item"><i :style="{background:z.color_code}"></i>{{ z.label }}</span>
            </div>
          </div>
        </div>

      </div>

      <!-- SYNTHÈSE PAR GROUPE -->
      <div class="card card--full">
        <div class="card-hdr">
          <strong><i class="ti ti-table"></i> Synthèse par {{ mailleLabel }}</strong>
          <span class="card-sub">Criticité moyenne inhérente → résiduelle → cible et écart</span>
        </div>
        <div class="card-body">
          <table class="synth-table">
            <thead>
              <tr>
                <th class="st-grp">{{ mailleColLabel }}</th>
                <th class="st-c">Risques</th>
                <th class="st-c">Inhérent</th>
                <th class="st-c">Résiduel</th>
                <th class="st-c">Cible</th>
                <th class="st-c">Δ I→Cible</th>
                <th class="st-bar">Profil</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="g in groups" :key="g.key">
                <td class="st-grp">
                  <div class="grp-name">{{ g.label }}</div>
                  <div v-if="g.sublabel" class="grp-sub">{{ g.sublabel }}</div>
                </td>
                <td class="st-c"><span class="pill-n">{{ g.risks.length }}</span></td>
                <td class="st-c"><span class="crit-pill" :style="critStyle(g.avgI)">{{ fmt(g.avgI) }}</span></td>
                <td class="st-c"><span class="crit-pill" :style="critStyle(g.avgR)">{{ fmt(g.avgR) }}</span></td>
                <td class="st-c"><span class="crit-pill" :style="critStyle(g.avgC)">{{ fmt(g.avgC) }}</span></td>
                <td class="st-c">
                  <span v-if="g.avgI!=null && g.avgC!=null" :class="['delta', g.avgC<=g.avgI?'delta-down':'delta-up']">
                    {{ g.avgC<=g.avgI?'▼':'▲' }} {{ fmt(Math.abs(g.avgI-g.avgC)) }}
                  </span>
                  <span v-else class="muted">—</span>
                </td>
                <td class="st-bar">
                  <div class="profile-bar">
                    <div v-for="z in sortedZones" :key="z.id"
                         v-show="g.zoneCounts[z.id]"
                         class="pb-seg"
                         :style="{ background:z.color_code, flexGrow: g.zoneCounts[z.id]||0 }"
                         :title="`${z.label}: ${g.zoneCounts[z.id]||0}`">
                      {{ g.zoneCounts[z.id]>0 ? g.zoneCounts[z.id] : '' }}
                    </div>
                  </div>
                </td>
              </tr>
              <tr v-if="!groups.length">
                <td colspan="7" class="empty-row"><i class="ti ti-inbox"></i> Aucun risque dans ce périmètre</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </VerticalLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps({
  risks:            { type: Array,  default: () => [] },
  tree:             { type: Array,  default: () => [] },
  stats:            { type: Object, default: () => ({}) },
  matrixConfigs:    { type: Array,  default: () => [] },
  matrixData:       { type: Object, default: () => null },
  selectedConfigId: { type: Number, default: null },
  entities:         { type: Array,  default: () => [] },
  sessionMeta:      { type: Object, default: () => null },
})

// ── état ────────────────────────────────────────────────────────────────
const activeConfigId = ref(props.selectedConfigId ?? props.matrixConfigs[0]?.id ?? null)
const activeCfg = computed(() => props.matrixConfigs.find(c => c.is_active) || props.matrixConfigs.find(c => c.id === props.selectedConfigId) || props.matrixConfigs[0] || null)
const activeEntity   = ref(null)
const maille         = ref('activite')
const layer          = ref('inherent')
const showTrajectory = ref(true)
const serieOpen      = ref(true)
const hoverCell      = ref(null)
const selectedActivityIds = ref(allActivityIds())

const mailles = [
  { key:'activite',  label:'Par activité',    icon:'ti ti-list' },
  { key:'processus', label:'Par processus',   icon:'ti ti-sitemap' },
  { key:'serie',     label:'Sélection',       icon:'ti ti-checkbox' },
  { key:'entite',    label:'Synthèse entité', icon:'ti ti-building' },
]
const layers = [
  { key:'inherent', label:'Inhérent',  icon:'ti ti-shield-bolt', color:'#ef4444', shape:'ti ti-circle-filled' },
  { key:'residual', label:'Résiduel',  icon:'ti ti-shield-half', color:'#f59e0b', shape:'ti ti-square-rotated-filled' },
  { key:'target',   label:'Cible',     icon:'ti ti-target-arrow',color:'#10b981', shape:'ti ti-diamonds-filled' },
  { key:'synthese', label:'Synthèse I·R·Cible', icon:'ti ti-layers-intersect', color:'#6366f1', shape:'ti ti-stack-2' },
]
// Couleurs I/R/Cible cohérentes (identiques au rapport) — appliquées partout.
const IRC_COLORS = { inherent:'#ef4444', residual:'#f59e0b', target:'#10b981' }
const currentLayer = computed(() => layers.find(l => l.key===layer.value) ?? layers[0])

// ── helpers matrice ─────────────────────────────────────────────────────
const impactRows = computed(() => [...(props.matrixData?.impacts ?? [])].sort((a,b)=>b.score-a.score))
const freqCols   = computed(() => [...(props.matrixData?.frequencies ?? [])].sort((a,b)=>a.score-b.score))
const sortedZones= computed(() => [...(props.matrixData?.zones ?? [])].sort((a,b)=>(a.sort_order??0)-(b.sort_order??0)))
const zoneForScore = (score) => sortedZones.value.find(z => score>=z.min_score && score<=z.max_score) ?? null
const cellColor = (im,f) => zoneForScore(im.score*f.score)?.color_code ?? '#e2e8f0'
const cellKey   = (im,f) => `${im.id}_${f.id}`

// accès aux scores selon le stade
const impactOf = (r) => layer.value==='inherent' ? r.impact_score : layer.value==='residual' ? r.residual_impact_score : r.target_impact_score
const freqOf   = (r) => layer.value==='inherent' ? r.frequency_score : layer.value==='residual' ? r.residual_frequency_score : r.target_frequency_score
const cellRisks = (im,f) => scopedRisks.value.filter(r => Number(impactOf(r))===im.score && Number(freqOf(r))===f.score)

// ── Vue multiple (I / R / Cible côte à côte) ──────────────────────────────
const viewMode = ref('simple') // 'simple' | 'multiple'
const stagesMeta = [
  { key:'inherent', label:'Inhérent', color:'#ef4444', icon:'ti ti-shield-bolt' },
  { key:'residual', label:'Résiduel', color:'#f59e0b', icon:'ti ti-shield-half' },
  { key:'target',   label:'Cible',    color:'#10b981', icon:'ti ti-target-arrow' },
]
const impactOfStage = (r,s) => s==='inherent' ? r.impact_score : s==='residual' ? r.residual_impact_score : r.target_impact_score
const freqOfStage   = (r,s) => s==='inherent' ? r.frequency_score : s==='residual' ? r.residual_frequency_score : r.target_frequency_score
const cellRisksStage = (im,f,s) => scopedRisks.value.filter(r => Number(impactOfStage(r,s))===im.score && Number(freqOfStage(r,s))===f.score)

// ── périmètre (entité + maille/série) ───────────────────────────────────
const scopedRisks = computed(() => {
  let r = props.risks
  if (activeEntity.value != null) r = r.filter(x => Number(x.entity_id)===Number(activeEntity.value))
  if (maille.value==='serie')     r = r.filter(x => selectedActivityIds.value.includes(Number(x.activity_id)))
  return r
})

// ── criticité par stade ─────────────────────────────────────────────────
const critOf = (r, stade) => {
  const v = stade==='inherent' ? r.criticality_score : stade==='residual' ? r.residual_criticality : r.target_criticality
  return v==null || v==='' ? null : Number(v)
}
const zoneIdOf = (r, stade) => stade==='inherent' ? r.zone_id : stade==='residual' ? r.residual_zone_id : r.target_zone_id
const avgFor = (list, stade) => {
  const vals = list.map(r=>critOf(r,stade)).filter(v=>v!=null)
  return vals.length ? vals.reduce((a,b)=>a+b,0)/vals.length : null
}
const avg = (stade) => avgFor(scopedRisks.value, stade)
const reductionPct = computed(() => {
  const i = avg('inherent'), c = avg('target')
  if (!i || c==null) return 0
  return Math.round((i - c) / i * 100)
})

// ── trajectoire moyenne I→R→Cible ───────────────────────────────────────
const meanScore = (list, accessor) => {
  const vals = list.map(accessor).map(Number).filter(v=>!isNaN(v) && v>0)
  return vals.length ? vals.reduce((a,b)=>a+b,0)/vals.length : null
}
const trajectory = computed(() => {
  const stades = [
    { key:'inherent', label:'Inhérent', color:'#ef4444', icon:'ti ti-circle-filled', imp:r=>r.impact_score,          frq:r=>r.frequency_score },
    { key:'residual', label:'Résiduel', color:'#f59e0b', icon:'ti ti-square-rotated-filled', imp:r=>r.residual_impact_score, frq:r=>r.residual_frequency_score },
    { key:'target',   label:'Cible',    color:'#10b981', icon:'ti ti-diamonds-filled', imp:r=>r.target_impact_score,  frq:r=>r.target_frequency_score },
  ]
  return stades.map(s => {
    const impact = meanScore(scopedRisks.value, s.imp)
    const freq   = meanScore(scopedRisks.value, s.frq)
    return { key:s.key, label:s.label, color:s.color, icon:s.icon, impact, freq, score:(impact&&freq)?impact*freq:null }
  }).filter(p => p.score!=null)
})

// ── groupes (synthèse) ──────────────────────────────────────────────────
const zoneCountsFor = (list) => {
  const counts = {}
  for (const z of sortedZones.value) counts[z.id] = 0
  for (const r of list) {
    const zid = zoneIdOf(r, layer.value)
    if (zid!=null && counts[zid]!=null) counts[zid]++
  }
  return counts
}
const makeGroup = (key, label, sublabel, list) => ({
  key, label, sublabel, risks:list,
  avgI: avgFor(list,'inherent'), avgR: avgFor(list,'residual'), avgC: avgFor(list,'target'),
  zoneCounts: zoneCountsFor(list),
})
const groups = computed(() => {
  const map = new Map()
  const push = (k, label, sub, r) => {
    if (!map.has(k)) map.set(k, { label, sub, list:[] })
    map.get(k).list.push(r)
  }
  for (const r of scopedRisks.value) {
    if (maille.value==='processus') {
      push(r.process_id ?? 0, r.process_name ?? '—', r.process_code ?? '', r)
    } else if (maille.value==='entite') {
      push(r.entity_id ?? 0, r.entity_name ?? 'Sans entité', '', r)
    } else { // activite | serie → maille activité
      push(r.activity_id ?? 0, r.activity_name ?? '—', `${r.process_code ?? ''} · ${r.activity_code ?? ''}`.trim(), r)
    }
  }
  return [...map.entries()]
    .map(([k,v]) => makeGroup(k, v.label, v.sub, v.list))
    .sort((a,b) => (b.avgI ?? 0) - (a.avgI ?? 0))
})

const mailleLabel = computed(() => ({activite:'activité', processus:'processus', serie:'sélection', entite:'entité'})[maille.value])
const mailleColLabel = computed(() => ({activite:'Activité', processus:'Processus', serie:'Activité', entite:'Entité'})[maille.value])

// ── graphe évolution (barres empilées par zone) ─────────────────────────
const stageCounts = (stade) => {
  const c = {}; for (const z of sortedZones.value) c[z.id]=0
  for (const r of scopedRisks.value) { const zid=zoneIdOf(r,stade); if (zid!=null && c[zid]!=null) c[zid]++ }
  return c
}
const evoSeries = computed(() => {
  const stages = ['inherent','residual','target']
  const perStage = stages.map(stageCounts)
  return sortedZones.value.map(z => ({
    name: z.label,
    data: perStage.map(c => c[z.id] || 0),
  }))
})
const evoOptions = computed(() => ({
  chart: { type:'bar', stacked:true, toolbar:{show:false}, fontFamily:'Inter, system-ui, sans-serif' },
  plotOptions: { bar: { horizontal:false, columnWidth:'46%', borderRadius:4, borderRadiusApplication:'end' } },
  colors: sortedZones.value.map(z => z.color_code || '#94a3b8'),
  xaxis: { categories:['Inhérent','Résiduel','Cible'], labels:{ style:{ fontSize:'12px', fontWeight:600 } } },
  yaxis: { labels:{ formatter:(v)=>Math.round(v) } },
  legend: { show:false },
  dataLabels: { enabled:true, formatter:(v)=> v>0?v:'', style:{ fontSize:'10px', colors:['#fff'] } },
  stroke: { width:2, colors:['#fff'] },
  tooltip: { y:{ formatter:(v)=>`${v} risque(s)` } },
  grid: { borderColor:'#eef2f7', strokeDashArray:4 },
}))

// ── util série ──────────────────────────────────────────────────────────
function allActivityIds () {
  const ids = []
  for (const m of (props.tree ?? [])) for (const p of (m.processes ?? [])) for (const a of (p.activities ?? [])) ids.push(Number(a.id))
  return ids
}
function selectAllActivities () { selectedActivityIds.value = allActivityIds() }
const procFullySelected = (proc) => proc.activities.length>0 && proc.activities.every(a => selectedActivityIds.value.includes(Number(a.id)))
const toggleProc = (proc, checked) => {
  const ids = proc.activities.map(a => Number(a.id))
  if (checked) selectedActivityIds.value = [...new Set([...selectedActivityIds.value, ...ids])]
  else selectedActivityIds.value = selectedActivityIds.value.filter(id => !ids.includes(id))
}

// ── divers ──────────────────────────────────────────────────────────────
const fmt = (v) => v==null ? '—' : (Math.round(v*10)/10).toString().replace('.',',')
const truncate = (s,n=40) => s?.length>n ? s.slice(0,n)+'…' : (s||'')
const critStyle = (v) => {
  if (v==null) return { background:'#f1f5f9', color:'#94a3b8' }
  const z = zoneForScore(Math.round(v))
  const c = z?.color_code || '#64748b'
  return { background:c+'1a', color:c, border:`1px solid ${c}55` }
}
const onCfgChange = () => router.get(route('risk.core.evaluation.cartographie'), { config_id:activeConfigId.value }, { preserveState:true, preserveScroll:true })
</script>

<style scoped>
.page{display:flex;flex-direction:column;min-height:calc(100vh - 60px);background:#f0f4f8;font-family:'Inter',system-ui,sans-serif;font-size:13px;}

/* HEADER */
.page-hdr{display:flex;align-items:center;justify-content:space-between;padding:10px 22px;background:#0f172a;flex-wrap:wrap;gap:10px;}
.page-hdr-left{display:flex;align-items:center;gap:12px;}
.hdr-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;color:#fff;background:linear-gradient(135deg,#4f46e5,#7c3aed);}
.page-hdr-left h1{font-size:16px;font-weight:800;color:#f1f5f9;margin:0;}
.page-hdr-left p{font-size:11px;color:#64748b;margin:0;}
.page-hdr-right{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.cfg-sel{font-size:11px;padding:6px 10px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.07);color:#c8d6e5;border-radius:8px;cursor:pointer;}
.cfg-fixed{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:6px 11px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05);color:#c8d6e5;border-radius:8px;}
.btn-ghost{display:flex;align-items:center;gap:5px;padding:6px 12px;background:rgba(255,255,255,.07);color:#c8d6e5;border:1px solid rgba(255,255,255,.12);border-radius:8px;font-size:11px;font-weight:600;cursor:pointer;text-decoration:none;}
.btn-ghost:hover{background:rgba(255,255,255,.14);}

/* BANNER SESSION */
.frozen-banner{display:flex;align-items:center;gap:8px;padding:8px 22px;background:#eff6ff;border-bottom:1px solid #bfdbfe;color:#1e40af;font-size:12px;font-weight:600;}
.frozen-banner i{font-size:15px;}
.fb-date{color:#3b82f6;font-weight:500;font-size:11px;}
.fb-live{margin-left:auto;display:flex;align-items:center;gap:5px;font-size:11px;font-weight:700;color:#1d4ed8;text-decoration:none;padding:4px 10px;border-radius:7px;background:#dbeafe;}
.fb-live:hover{background:#bfdbfe;}

/* CTRL BAR */
.ctrl-bar{display:flex;align-items:center;gap:22px;padding:10px 22px;background:#fff;border-bottom:1px solid #e2e8f0;flex-wrap:wrap;}
.ctrl-grp{display:flex;align-items:center;gap:8px;}
.ctrl-grp--right{margin-left:auto;}
.ctrl-lbl{font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;}
.seg{display:flex;gap:3px;background:#f1f5f9;padding:3px;border-radius:9px;}
.seg-btn{display:flex;align-items:center;gap:5px;padding:6px 12px;border:1.5px solid transparent;border-radius:7px;font-size:11.5px;font-weight:600;cursor:pointer;background:transparent;color:#64748b;transition:all .12s;}
.seg-btn:hover{color:#334155;background:#fff;}
.seg-btn--on{background:#fff;color:#1d4ed8;border-color:#dbeafe;box-shadow:0 1px 3px rgba(0,0,0,.08);}
.switch{display:flex;align-items:center;gap:7px;font-size:11.5px;font-weight:600;color:#475569;cursor:pointer;}
.switch input{width:15px;height:15px;accent-color:#4f46e5;cursor:pointer;}

/* SERIE PANEL */
.serie-panel{background:#fff;border-bottom:1px solid #e2e8f0;padding:12px 22px;max-height:280px;overflow:auto;}
.serie-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;}
.serie-head strong{font-size:12px;color:#1e293b;display:flex;align-items:center;gap:6px;}
.serie-actions{display:flex;gap:6px;}
.mini-btn{font-size:10px;font-weight:700;padding:4px 10px;border:1px solid #e2e8f0;border-radius:7px;background:#f8fafc;color:#475569;cursor:pointer;}
.mini-btn:hover{background:#eef2f7;}
.serie-tree{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;}
.serie-macro-name{font-size:10px;font-weight:800;color:#7c3aed;text-transform:uppercase;letter-spacing:.04em;margin-bottom:5px;}
.serie-proc{margin-bottom:8px;}
.serie-proc-name{display:flex;align-items:center;gap:6px;font-size:11.5px;font-weight:700;color:#1e293b;cursor:pointer;}
.serie-acts{margin:3px 0 0 20px;display:flex;flex-direction:column;gap:2px;}
.serie-act{display:flex;align-items:center;gap:6px;font-size:11px;color:#475569;cursor:pointer;padding:2px 0;}
.serie-act input,.serie-proc-name input{accent-color:#4f46e5;cursor:pointer;}
.pcode{font-family:monospace;font-size:9px;background:#e2e8f0;color:#475569;padding:0 4px;border-radius:3px;}
.acode{font-family:monospace;font-size:8px;background:#dcfce7;color:#15803d;padding:0 4px;border-radius:3px;}
.acount{margin-left:auto;font-size:9px;font-weight:700;color:#94a3b8;background:#f1f5f9;padding:0 6px;border-radius:8px;}

/* TILES */
.tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;padding:16px 22px 4px;}
.tile{display:flex;align-items:center;gap:12px;background:#fff;border:1px solid #e9eef5;border-radius:12px;padding:12px 16px;}
.tile-ic{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff;flex-shrink:0;}
.tile-num{font-size:20px;font-weight:900;color:#0f172a;line-height:1;}
.tile-lbl{font-size:10.5px;color:#64748b;font-weight:600;margin-top:3px;}

/* GRID */
.grid{display:grid;grid-template-columns:minmax(0,1.6fr) minmax(0,1fr);gap:14px;padding:12px 22px;}
@media(max-width:1100px){.grid{grid-template-columns:1fr;}}
.card{background:#fff;border:1px solid #e9eef5;border-radius:14px;display:flex;flex-direction:column;overflow:hidden;}
.card--full{margin:0 22px 22px;}
.card-hdr{display:flex;align-items:baseline;justify-content:space-between;gap:10px;padding:12px 16px;border-bottom:1px solid #eef2f7;flex-wrap:wrap;}
.card-hdr strong{font-size:13px;color:#1e293b;display:flex;align-items:center;gap:7px;}
.card-sub{font-size:10.5px;color:#94a3b8;font-weight:500;}
.card-body{padding:16px;flex:1;}
.empty{display:flex;align-items:center;justify-content:center;gap:8px;padding:40px;color:#94a3b8;font-size:13px;}

/* MATRICE */
.matrix-wrap{display:flex;flex-direction:column;gap:12px;}
.axis-x{text-align:center;font-size:10.5px;color:#94a3b8;font-weight:600;padding-left:110px;}
.matrix-flex{display:flex;align-items:stretch;gap:4px;}
.axis-y{display:flex;align-items:center;justify-content:center;width:20px;flex-shrink:0;}
.axis-y span{writing-mode:vertical-rl;transform:rotate(180deg);font-size:10.5px;color:#94a3b8;font-weight:600;white-space:nowrap;}
.cmatrix{border-collapse:separate;border-spacing:4px;width:100%;table-layout:fixed;}
.cmatrix th,.cmatrix td{text-align:center;}
.corner{width:96px;}
.cmatrix thead th{vertical-align:bottom;padding-bottom:3px;}
.axbadge{display:inline-block;font-size:9.5px;font-weight:700;color:#fff;padding:2px 8px;border-radius:5px;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.axscore{display:block;font-size:8.5px;color:#94a3b8;font-family:monospace;margin-top:1px;}
.rowhd{width:96px;text-align:right;padding-right:6px;}
.rowhd .axbadge{max-width:88px;}
.ccell{position:relative;height:62px;border-radius:7px;cursor:default;transition:filter .12s;vertical-align:middle;}
.ccell:hover{filter:brightness(1.06);}
.cell-score{position:absolute;top:4px;right:6px;font-size:9px;font-weight:700;color:rgba(255,255,255,.7);text-shadow:0 1px 2px rgba(0,0,0,.4);}
.cell-count{display:inline-flex;align-items:center;justify-content:center;min-width:26px;height:26px;padding:0 6px;border-radius:14px;background:rgba(15,23,42,.82);color:#fff;font-size:13px;font-weight:800;box-shadow:0 2px 6px rgba(0,0,0,.25);}
.syn-chips{display:flex;align-items:center;justify-content:center;gap:3px;flex-wrap:wrap;}
.syn-chip{display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 4px;border-radius:9px;color:#fff;font-size:10px;font-weight:800;box-shadow:0 1px 3px rgba(0,0,0,.35);border:1.5px solid rgba(255,255,255,.85);}
.irc-legend{display:flex;align-items:center;gap:14px;flex-wrap:wrap;padding:6px 0;}
.irc{display:flex;align-items:center;gap:5px;font-size:11px;font-weight:700;color:#334155;}
.irc-dot{width:12px;height:12px;border-radius:50%;display:inline-block;border:1.5px solid #fff;box-shadow:0 0 0 1px rgba(0,0,0,.1);}
.irc-note{font-size:10px;color:#94a3b8;font-style:italic;margin-left:auto;}
.cell-tip{position:absolute;bottom:calc(100% + 6px);left:50%;transform:translateX(-50%);z-index:30;background:#0f172a;color:#e2e8f0;border-radius:9px;padding:8px 10px;width:240px;text-align:left;box-shadow:0 10px 30px rgba(0,0,0,.35);}
.tip-hd{font-size:10px;font-weight:800;color:#fbbf24;margin-bottom:5px;text-transform:uppercase;letter-spacing:.03em;}
.tip-row{font-size:10.5px;line-height:1.5;color:#cbd5e1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.tip-code{font-family:monospace;font-size:9px;color:#818cf8;font-weight:700;margin-right:4px;}
.tip-more{font-size:9.5px;color:#64748b;margin-top:3px;font-style:italic;}

/* TRAJECTOIRE */
.traj{border:1px dashed #cbd5e1;border-radius:12px;padding:12px 14px;background:#f8fafc;}
.traj-title{font-size:11px;font-weight:800;color:#475569;display:flex;align-items:center;gap:6px;margin-bottom:10px;text-transform:uppercase;letter-spacing:.03em;}
.traj-line{display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
.traj-node{display:flex;align-items:center;gap:8px;padding:6px 12px;background:#fff;border:2px solid;border-radius:10px;}
.traj-shape{width:26px;height:26px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;flex-shrink:0;}
.traj-name{font-size:11px;font-weight:800;}
.traj-score{font-size:10px;color:#64748b;}
.traj-score b{color:#1e293b;}
.traj-arrow{display:flex;flex-direction:column;align-items:center;color:#94a3b8;font-size:20px;}
.traj-delta{font-size:10px;font-weight:800;padding:1px 7px;border-radius:8px;}
.traj-delta.down{background:#dcfce7;color:#15803d;}
.traj-delta.up{background:#fee2e2;color:#dc2626;}

/* LEGENDE ZONES */
.zlegend{display:flex;gap:12px;flex-wrap:wrap;padding-top:4px;border-top:1px solid #f1f5f9;}
.zchip{display:flex;align-items:center;gap:5px;font-size:10.5px;font-weight:600;color:#475569;}
.zdot{width:12px;height:12px;border-radius:4px;display:inline-block;}
.zrange{font-size:9px;color:#94a3b8;font-family:monospace;}

/* VUE MULTIPLE */
.multi-wrap{display:flex;gap:16px;flex-wrap:wrap;align-items:flex-start;}
.multi-col{flex:1;min-width:180px;}
.multi-title{font-size:12px;font-weight:800;display:flex;align-items:center;gap:6px;margin-bottom:8px;padding-bottom:5px;border-bottom:2px solid currentColor;}
.cmatrix--mini{border-spacing:2px;width:100%;}
.rowhd-mini{width:16px;text-align:center;font-size:9px;font-weight:700;color:#94a3b8;font-family:monospace;}
.ccell--mini{height:34px;border-radius:4px;position:relative;text-align:center;vertical-align:middle;}
.cell-count--mini{min-width:20px;height:20px;font-size:11px;}
.mini-foot td{text-align:center;font-size:9px;font-weight:700;color:#94a3b8;font-family:monospace;height:14px;}
.multi-legend{flex-basis:100%;margin-top:4px;border-top:1px solid #f1f5f9;padding-top:6px;}

/* MINI LEGEND (chart) */
.mini-legend{display:flex;gap:12px;flex-wrap:wrap;justify-content:center;margin-top:8px;}
.ml-item{display:flex;align-items:center;gap:5px;font-size:10.5px;font-weight:600;color:#475569;}
.ml-item i{width:11px;height:11px;border-radius:3px;display:inline-block;}

/* SYNTHESE TABLE */
.synth-table{width:100%;border-collapse:collapse;font-size:12px;}
.synth-table thead th{text-align:left;padding:9px 12px;background:#f8fafc;border-bottom:2px solid #e2e8f0;font-size:10px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.03em;white-space:nowrap;}
.st-c{text-align:center!important;}
.st-bar{width:34%;}
.synth-table td{padding:9px 12px;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
.grp-name{font-size:12px;font-weight:700;color:#0f172a;}
.grp-sub{font-size:9.5px;color:#94a3b8;font-family:monospace;margin-top:1px;}
.pill-n{display:inline-flex;align-items:center;justify-content:center;min-width:24px;height:22px;padding:0 8px;border-radius:11px;background:#eef2f7;color:#475569;font-size:11px;font-weight:800;}
.crit-pill{display:inline-block;min-width:36px;padding:3px 10px;border-radius:8px;font-size:12px;font-weight:800;text-align:center;}
.delta{font-size:11px;font-weight:800;padding:2px 9px;border-radius:8px;}
.delta-down{background:#dcfce7;color:#15803d;}
.delta-up{background:#fee2e2;color:#dc2626;}
.muted{color:#cbd5e1;}
.profile-bar{display:flex;height:24px;border-radius:7px;overflow:hidden;background:#f1f5f9;gap:2px;}
.pb-seg{display:flex;align-items:center;justify-content:center;color:#fff;font-size:10px;font-weight:800;min-width:16px;transition:flex-grow .2s;}
.empty-row{text-align:center;padding:36px!important;color:#94a3b8;}

/* transitions */
.slide-enter-active,.slide-leave-active{transition:all .2s ease;overflow:hidden;}
.slide-enter-from,.slide-leave-to{opacity:0;max-height:0;padding-top:0;padding-bottom:0;}
::-webkit-scrollbar{width:6px;height:6px;}::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:3px;}
</style>
