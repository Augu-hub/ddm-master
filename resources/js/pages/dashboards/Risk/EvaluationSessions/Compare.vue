<template>
  <VerticalLayout>
    <div class="page">

      <!-- HEADER -->
      <div class="page-hdr">
        <div class="page-hdr-left">
          <div class="hdr-icon"><i class="ti ti-git-compare"></i></div>
          <div>
            <h1>Comparaison d'évolution</h1>
            <p>Évolution des risques entre deux sessions (criticité inhérente, résiduelle, cible)</p>
          </div>
        </div>
        <div class="page-hdr-right">
          <Link :href="route('risk.core.eval-sessions.index')" class="btn-ghost"><i class="ti ti-arrow-left"></i> Sessions</Link>
        </div>
      </div>

      <!-- SÉLECTEURS -->
      <div class="sel-bar">
        <div class="sel-grp">
          <span class="sel-lbl">Session de référence (A)</span>
          <select v-model="aId" class="sel">
            <option :value="null">— choisir —</option>
            <option v-for="s in snapSessions" :key="s.id" :value="s.id">{{ s.name }} ({{ s.year }})</option>
          </select>
        </div>
        <div class="sel-arrow"><i class="ti ti-arrow-right"></i></div>
        <div class="sel-grp">
          <span class="sel-lbl">Comparée à (B)</span>
          <select v-model="bId" class="sel">
            <option value="live">État actuel (registre vivant)</option>
            <option v-for="s in snapSessions" :key="s.id" :value="s.id">{{ s.name }} ({{ s.year }})</option>
          </select>
        </div>
        <button class="btn-primary" :disabled="!aId || loading" @click="run">
          <i v-if="loading" class="ti ti-loader-2 ti-spin"></i><i v-else class="ti ti-git-compare"></i> Comparer
        </button>
      </div>

      <div v-if="!aId" class="hint"><i class="ti ti-info-circle"></i> Sélectionnez une session de référence puis lancez la comparaison. Seules les sessions <strong>gelées</strong> sont comparables.</div>

      <div v-else-if="data" class="content">

        <!-- SYNTHÈSE -->
        <div class="tiles">
          <div class="tile tile-worse"><div class="t-num">{{ data.summary.worsened }}</div><div class="t-lbl"><i class="ti ti-trending-up"></i> Aggravés</div></div>
          <div class="tile tile-better"><div class="t-num">{{ data.summary.improved }}</div><div class="t-lbl"><i class="ti ti-trending-down"></i> Améliorés</div></div>
          <div class="tile tile-stable"><div class="t-num">{{ data.summary.stable }}</div><div class="t-lbl"><i class="ti ti-minus"></i> Stables</div></div>
          <div class="tile tile-new"><div class="t-num">{{ data.summary.new }}</div><div class="t-lbl"><i class="ti ti-plus"></i> Nouveaux</div></div>
          <div class="tile tile-removed"><div class="t-num">{{ data.summary.removed }}</div><div class="t-lbl"><i class="ti ti-minus"></i> Disparus</div></div>
        </div>

        <div class="grid">
          <!-- GRAPHE MOYENNES -->
          <div class="card">
            <div class="card-hdr"><strong><i class="ti ti-chart-bar"></i> Criticité moyenne</strong>
              <span class="card-sub">{{ data.a?.name }} → {{ data.b?.name }}</span></div>
            <div class="card-body">
              <apexchart type="bar" height="240" :options="avgOptions" :series="avgSeries" />
            </div>
          </div>
          <!-- LÉGENDE / RÉSUMÉ -->
          <div class="card">
            <div class="card-hdr"><strong><i class="ti ti-info-circle"></i> Lecture</strong></div>
            <div class="card-body summary-read">
              <div class="sr-row"><span class="sr-k">Référence (A)</span><span class="sr-v">{{ data.a?.name }} · {{ data.averages.a.count }} risque(s)</span></div>
              <div class="sr-row"><span class="sr-k">Comparée (B)</span><span class="sr-v">{{ data.b?.name }} · {{ data.averages.b.count }} risque(s)</span></div>
              <hr/>
              <div class="sr-line"><i class="ti ti-shield-bolt" style="color:#ef4444"></i> Inhérent : <b>{{ fmt(data.averages.a.inh) }}</b> → <b>{{ fmt(data.averages.b.inh) }}</b> {{ deltaTxt(data.averages.a.inh, data.averages.b.inh) }}</div>
              <div class="sr-line"><i class="ti ti-shield-half" style="color:#f59e0b"></i> Résiduel : <b>{{ fmt(data.averages.a.res) }}</b> → <b>{{ fmt(data.averages.b.res) }}</b> {{ deltaTxt(data.averages.a.res, data.averages.b.res) }}</div>
              <div class="sr-line"><i class="ti ti-target-arrow" style="color:#10b981"></i> Cible : <b>{{ fmt(data.averages.a.tgt) }}</b> → <b>{{ fmt(data.averages.b.tgt) }}</b> {{ deltaTxt(data.averages.a.tgt, data.averages.b.tgt) }}</div>
              <p class="sr-note">La classification (aggravé / amélioré) se base sur la criticité <strong>résiduelle</strong> (le niveau de risque réel après contrôles).</p>
            </div>
          </div>
        </div>

        <!-- TABLEAU DÉTAILLÉ -->
        <div class="card card--full">
          <div class="card-hdr">
            <strong><i class="ti ti-table"></i> Évolution risque par risque</strong>
            <div class="filters">
              <button v-for="f in statusFilters" :key="f.key" :class="['fbtn', filter===f.key?'fbtn--on':'']" @click="filter=f.key">{{ f.label }}</button>
            </div>
          </div>
          <div class="card-body">
            <div class="tscroll">
              <table class="etable">
                <thead>
                  <tr>
                    <th>Risque</th>
                    <th>Contexte</th>
                    <th>Évolution</th>
                    <th class="c">Inhérent</th>
                    <th class="c">Résiduel A → B</th>
                    <th class="c">Cible</th>
                    <th class="c">Plans (B)</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="r in filteredRows" :key="r.risk_id">
                    <td>
                      <div class="r-name">{{ r.libelle }}</div>
                      <span class="r-code">{{ r.code_risk }}</span>
                    </td>
                    <td class="r-ctx">
                      <div v-if="r.process_name">{{ r.process_name }}</div>
                      <div class="r-act">{{ r.activity_name || r.entity_name || '—' }}</div>
                    </td>
                    <td><span :class="['stat-badge','sb-'+r.status]"><i :class="statusIcon(r.status)"></i> {{ statusLabel(r.status) }}</span></td>
                    <td class="c"><span class="mini">{{ critTxt(r.a,'inh') }} → {{ critTxt(r.b,'inh') }}</span></td>
                    <td class="c">
                      <div class="res-cell">
                        <span class="crit-chip" :style="chipStyle(r.a?.res)">{{ critTxt(r.a,'res') }}</span>
                        <i class="ti ti-arrow-right sep"></i>
                        <span class="crit-chip" :style="chipStyle(r.b?.res)">{{ critTxt(r.b,'res') }}</span>
                        <span v-if="r.delta_res!=null" :class="['dpill', r.delta_res<0?'d-down':r.delta_res>0?'d-up':'d-eq']">
                          {{ r.delta_res>0?'+':'' }}{{ fmt(r.delta_res) }}
                        </span>
                      </div>
                    </td>
                    <td class="c"><span class="mini">{{ critTxt(r.a,'tgt') }} → {{ critTxt(r.b,'tgt') }}</span></td>
                    <td class="c">
                      <div v-if="r.plans_b && r.plans_b.total" class="plan-mini" :title="`${r.plans_b.done}/${r.plans_b.total} terminés`">
                        <div class="pm-bar"><div class="pm-fill" :style="{width:(r.plans_b.progress||0)+'%'}"></div></div>
                        <span>{{ r.plans_b.done }}/{{ r.plans_b.total }}</span>
                      </div>
                      <span v-else class="muted">—</span>
                    </td>
                  </tr>
                  <tr v-if="!filteredRows.length"><td colspan="7" class="empty-row"><i class="ti ti-inbox"></i> Aucun risque</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </VerticalLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps({
  sessions: { type: Array,  default: () => [] },
  preA:     { type: Number, default: null },
  preB:     { type: [Number, String], default: null },
})

const snapSessions = computed(() => props.sessions.filter(s => s.has_snapshot))
const aId = ref(props.preA ?? snapSessions.value[0]?.id ?? null)
const bId = ref(props.preB ?? 'live')
const loading = ref(false)
const data = ref(null)
const filter = ref('all')

const statusFilters = [
  { key:'all',      label:'Tous' },
  { key:'worsened', label:'Aggravés' },
  { key:'improved', label:'Améliorés' },
  { key:'new',      label:'Nouveaux' },
  { key:'removed',  label:'Disparus' },
]
const statusLabel = (s) => ({worsened:'Aggravé', improved:'Amélioré', stable:'Stable', new:'Nouveau', removed:'Disparu'})[s] || s
const statusIcon  = (s) => ({worsened:'ti ti-trending-up', improved:'ti ti-trending-down', stable:'ti ti-minus', new:'ti ti-plus', removed:'ti ti-x'})[s] || 'ti ti-minus'

const fmt = (v) => v==null ? '—' : (Math.round(v*10)/10).toString().replace('.',',')
const critTxt = (side, stage) => side && side[stage] && side[stage].crit!=null ? fmt(side[stage].crit) : '—'
const chipStyle = (cell) => {
  if (!cell || cell.crit==null) return { background:'#f1f5f9', color:'#94a3b8' }
  const c = cell.color || '#64748b'
  return { background:c+'1a', color:c, border:`1px solid ${c}55` }
}
const deltaTxt = (a,b) => {
  if (a==null || b==null) return ''
  const d = Math.round((b-a)*10)/10
  if (d===0) return '(=)'
  return d<0 ? `(▼ ${fmt(Math.abs(d))})` : `(▲ ${fmt(d)})`
}

const filteredRows = computed(() => {
  if (!data.value) return []
  return filter.value==='all' ? data.value.rows : data.value.rows.filter(r => r.status===filter.value)
})

const run = async () => {
  if (!aId.value) return
  loading.value = true
  try {
    const url = route('risk.core.eval-sessions.compare.data', { a: aId.value, b: bId.value })
    const r = await fetch(url, { headers:{ 'Accept':'application/json' } })
    data.value = await r.json()
  } catch { data.value = null }
  finally { loading.value = false }
}

// graphe moyennes A vs B (3 stades)
const avgSeries = computed(() => {
  if (!data.value) return []
  const a = data.value.averages.a, b = data.value.averages.b
  return [
    { name: data.value.a?.name || 'A', data: [a.inh ?? 0, a.res ?? 0, a.tgt ?? 0] },
    { name: data.value.b?.name || 'B', data: [b.inh ?? 0, b.res ?? 0, b.tgt ?? 0] },
  ]
})
const avgOptions = computed(() => ({
  chart:{ type:'bar', toolbar:{show:false}, fontFamily:'Inter, system-ui, sans-serif' },
  plotOptions:{ bar:{ columnWidth:'55%', borderRadius:4, borderRadiusApplication:'end' } },
  colors:['#94a3b8', '#4f46e5'],
  xaxis:{ categories:['Inhérent','Résiduel','Cible'], labels:{ style:{ fontSize:'12px', fontWeight:600 } } },
  yaxis:{ labels:{ formatter:(v)=>Math.round(v) } },
  legend:{ position:'top', fontSize:'12px' },
  dataLabels:{ enabled:true, formatter:(v)=>v?fmt(v):'', style:{ fontSize:'10px' } },
  grid:{ borderColor:'#eef2f7', strokeDashArray:4 },
  tooltip:{ y:{ formatter:(v)=>fmt(v) } },
}))

onMounted(() => { if (aId.value) run() })
</script>

<style scoped>
.page{display:flex;flex-direction:column;min-height:calc(100vh - 60px);background:#f0f4f8;font-family:'Inter',system-ui,sans-serif;font-size:13px;}
.page-hdr{display:flex;align-items:center;justify-content:space-between;padding:10px 22px;background:#0f172a;flex-wrap:wrap;gap:10px;}
.page-hdr-left{display:flex;align-items:center;gap:12px;}
.hdr-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;color:#fff;background:linear-gradient(135deg,#0891b2,#0d9488);}
.page-hdr-left h1{font-size:16px;font-weight:800;color:#f1f5f9;margin:0;}
.page-hdr-left p{font-size:11px;color:#64748b;margin:0;}
.btn-ghost{display:flex;align-items:center;gap:5px;padding:7px 14px;background:rgba(255,255,255,.07);color:#c8d6e5;border:1px solid rgba(255,255,255,.12);border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;}

.sel-bar{display:flex;align-items:flex-end;gap:14px;padding:14px 22px;background:#fff;border-bottom:1px solid #e2e8f0;flex-wrap:wrap;}
.sel-grp{display:flex;flex-direction:column;gap:4px;}
.sel-lbl{font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em;}
.sel{padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:12.5px;min-width:230px;background:#f8fafc;color:#0f172a;cursor:pointer;}
.sel-arrow{padding-bottom:8px;color:#94a3b8;font-size:18px;}
.btn-primary{display:flex;align-items:center;gap:6px;padding:9px 18px;background:#0d9488;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;}
.btn-primary:hover:not(:disabled){background:#0f766e;}
.btn-primary:disabled{opacity:.5;cursor:not-allowed;}

.hint{margin:20px 22px;padding:16px;background:#fff;border:1px dashed #cbd5e1;border-radius:12px;color:#64748b;display:flex;align-items:center;gap:8px;font-size:13px;}
.content{padding:16px 22px 24px;}

.tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:14px;}
.tile{background:#fff;border:1px solid #e9eef5;border-radius:12px;padding:14px 16px;border-left:4px solid #cbd5e1;}
.t-num{font-size:24px;font-weight:900;color:#0f172a;line-height:1;}
.t-lbl{font-size:11px;font-weight:700;color:#64748b;margin-top:5px;display:flex;align-items:center;gap:4px;}
.tile-worse{border-left-color:#ef4444;}.tile-worse .t-num{color:#dc2626;}
.tile-better{border-left-color:#22c55e;}.tile-better .t-num{color:#16a34a;}
.tile-stable{border-left-color:#94a3b8;}
.tile-new{border-left-color:#6366f1;}.tile-new .t-num{color:#4f46e5;}
.tile-removed{border-left-color:#f59e0b;}

.grid{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);gap:14px;margin-bottom:14px;}
@media(max-width:1000px){.grid{grid-template-columns:1fr;}}
.card{background:#fff;border:1px solid #e9eef5;border-radius:14px;overflow:hidden;}
.card--full{}
.card-hdr{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 16px;border-bottom:1px solid #eef2f7;flex-wrap:wrap;}
.card-hdr strong{font-size:13px;color:#1e293b;display:flex;align-items:center;gap:7px;}
.card-sub{font-size:10.5px;color:#94a3b8;}
.card-body{padding:16px;}
.summary-read{font-size:12.5px;color:#334155;}
.sr-row{display:flex;justify-content:space-between;gap:10px;padding:3px 0;}
.sr-k{color:#94a3b8;font-weight:600;}
.sr-v{font-weight:700;color:#1e293b;}
.summary-read hr{border:none;border-top:1px solid #eef2f7;margin:8px 0;}
.sr-line{display:flex;align-items:center;gap:6px;padding:4px 0;font-size:12.5px;}
.sr-note{margin-top:10px;font-size:10.5px;color:#94a3b8;font-style:italic;line-height:1.5;}

.filters{display:flex;gap:4px;flex-wrap:wrap;}
.fbtn{font-size:11px;font-weight:600;padding:5px 11px;border:1px solid #e2e8f0;border-radius:20px;background:#fff;color:#64748b;cursor:pointer;}
.fbtn--on{border-color:#0d9488;background:#f0fdfa;color:#0f766e;}

.tscroll{overflow-x:auto;}
.etable{width:100%;border-collapse:collapse;font-size:12px;min-width:820px;}
.etable thead th{text-align:left;padding:9px 12px;background:#f8fafc;border-bottom:2px solid #e2e8f0;font-size:10px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.03em;white-space:nowrap;}
.etable th.c,.etable td.c{text-align:center;}
.etable td{padding:9px 12px;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
.r-name{font-size:12px;font-weight:700;color:#0f172a;max-width:240px;}
.r-code{font-family:monospace;font-size:9px;font-weight:700;color:#4338ca;background:#ede9fe;padding:1px 6px;border-radius:4px;}
.r-ctx{font-size:11px;color:#475569;}
.r-act{font-size:10px;color:#94a3b8;}
.stat-badge{display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:800;padding:3px 9px;border-radius:10px;white-space:nowrap;}
.sb-worsened{background:#fee2e2;color:#dc2626;}.sb-improved{background:#dcfce7;color:#16a34a;}.sb-stable{background:#f1f5f9;color:#64748b;}.sb-new{background:#e0e7ff;color:#4f46e5;}.sb-removed{background:#fef3c7;color:#b45309;}
.mini{font-size:11px;color:#64748b;font-weight:600;white-space:nowrap;}
.res-cell{display:flex;align-items:center;gap:6px;justify-content:center;}
.crit-chip{display:inline-block;min-width:30px;padding:2px 8px;border-radius:7px;font-size:11px;font-weight:800;text-align:center;}
.sep{color:#cbd5e1;}
.dpill{font-size:10px;font-weight:800;padding:1px 7px;border-radius:8px;}
.d-down{background:#dcfce7;color:#15803d;}.d-up{background:#fee2e2;color:#dc2626;}.d-eq{background:#f1f5f9;color:#94a3b8;}
.plan-mini{display:flex;align-items:center;gap:6px;justify-content:center;}
.pm-bar{width:52px;height:6px;background:#e2e8f0;border-radius:3px;overflow:hidden;}
.pm-fill{height:100%;background:#10b981;}
.plan-mini span{font-size:10px;font-weight:700;color:#475569;}
.muted{color:#cbd5e1;}
.empty-row{text-align:center;padding:34px!important;color:#94a3b8;}
@keyframes ti-spin{to{transform:rotate(360deg);}}.ti-spin{animation:ti-spin .7s linear infinite;display:inline-block;}
</style>
