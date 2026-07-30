<template>
  <div class="g-wrap">
    <div class="toolbar no-print">
      <Link :href="route('risk.core.reports.index')" class="tb-btn"><i class="ti ti-arrow-left"></i> Rapports</Link>
      <div class="tb-title"><i class="ti ti-timeline"></i> Diagramme de Gantt des recommandations</div>
      <div class="tb-right">
        <select v-model="procSel" class="tb-sel" @change="onProc">
          <option :value="null">Tous les processus</option>
          <option v-for="p in processList" :key="p.id" :value="p.id">{{ p.code }} — {{ p.name }}</option>
        </select>
        <button class="tb-btn tb-print" @click="print"><i class="ti ti-printer"></i> Imprimer / PDF</button>
      </div>
    </div>

    <div class="doc">
      <h1 class="doc-title">Diagramme de Gantt des recommandations</h1>
      <div class="legende no-print">
        <span class="lg"><span class="bar bar-prev"></span> Prévisionnel (échéance planifiée)</span>
        <span class="lg"><span class="bar bar-real"></span> Réalisé (avancement / clôture)</span>
      </div>

      <div v-if="!processes.length || !months.length" class="empty"><i class="ti ti-inbox"></i> Aucune recommandation datée à planifier.</div>

      <div v-else class="scroll">
        <table class="gantt">
          <thead>
            <tr>
              <th class="c-info">Processus / Activité / Recommandation</th>
              <th class="c-resp">Responsable</th>
              <th v-for="m in months" :key="m" class="c-month">{{ fmtMonth(m) }}</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="p in processes" :key="p.code">
              <tr class="r-proc"><td :colspan="2 + months.length"><span class="pc">{{ p.code }}</span> {{ p.name }}</td></tr>
              <template v-for="a in p.activities" :key="a.code">
                <tr class="r-act"><td :colspan="2 + months.length"><span class="ac">{{ a.code }}</span> {{ a.name }}</td></tr>
                <tr v-for="pl in a.plans" :key="pl.id" class="r-plan">
                  <td class="c-info">
                    <div class="pl-title">{{ pl.title }}</div>
                    <div class="pl-sub"><span class="chip-code">{{ pl.code_risk }}</span> {{ truncate(pl.risk_libelle, 40) }}</div>
                  </td>
                  <td class="c-resp">{{ pl.responsable || '—' }}</td>
                  <td v-for="(m, i) in months" :key="m" class="c-cell">
                    <div v-if="inPrev(pl, i)" class="bar bar-prev" :class="segCls(pl, i, 'prev')"></div>
                    <div v-if="inReal(pl, i)" class="bar bar-real" :class="segCls(pl, i, 'real')"></div>
                  </td>
                </tr>
              </template>
            </template>
          </tbody>
        </table>
      </div>

      <footer class="foot">DIADDEM RISK · Gantt généré le {{ today }} · {{ months.length }} mois</footer>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  processes:     { type: Array, default: () => [] },
  months:        { type: Array, default: () => [] },
  processList:   { type: Array, default: () => [] },
  processFilter: { type: Number, default: null },
})

const procSel = ref(props.processFilter ?? null)
const onProc = () => router.get(route('risk.core.reports.gantt'), procSel.value ? { process: procSel.value } : {}, { preserveScroll: true })

const monthIdx = (d) => d ? props.months.indexOf(String(d).slice(0, 7)) : -1
const prevRange = (pl) => {
  const s = monthIdx(pl.start_date), e = monthIdx(pl.target_date)
  const start = s >= 0 ? s : e, end = e >= 0 ? e : s
  return (start < 0) ? null : [Math.min(start, end), Math.max(start, end)]
}
const realRange = (pl) => {
  const pr = prevRange(pl); if (!pr) return null
  const s = pr[0]
  const comp = monthIdx(pl.completion_date)
  if (comp >= 0) return [s, Math.max(s, comp)]
  const prog = pl.progress || 0
  if (prog <= 0) return null
  const end = s + Math.round((pr[1] - s) * prog / 100)
  return [s, Math.max(s, end)]
}
const inPrev = (pl, i) => { const r = prevRange(pl); return r && i >= r[0] && i <= r[1] }
const inReal = (pl, i) => { const r = realRange(pl); return r && i >= r[0] && i <= r[1] }
const segCls = (pl, i, kind) => {
  const r = kind === 'prev' ? prevRange(pl) : realRange(pl); if (!r) return ''
  return [i === r[0] ? 'seg-start' : '', i === r[1] ? 'seg-end' : ''].join(' ')
}

const MONTHS_FR = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.']
const fmtMonth = (m) => { const [y, mo] = m.split('-'); return `${MONTHS_FR[+mo - 1]} ${y.slice(2)}` }
const truncate = (s, n = 40) => s && s.length > n ? s.slice(0, n) + '…' : (s || '')
const today = new Date().toLocaleDateString('fr-FR')
const print = () => window.print()
</script>

<style scoped>
.g-wrap{background:#e2e8f0;min-height:100vh;font-family:'Inter',system-ui,sans-serif;color:#1e293b;padding-bottom:30px;}
.toolbar{position:sticky;top:0;z-index:20;display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 18px;background:#0f172a;flex-wrap:wrap;}
.tb-title{color:#e2e8f0;font-size:13px;font-weight:700;display:flex;align-items:center;gap:6px;}
.tb-right{display:flex;align-items:center;gap:8px;}
.tb-btn{display:flex;align-items:center;gap:5px;padding:7px 13px;background:rgba(255,255,255,.08);color:#e2e8f0;border:1px solid rgba(255,255,255,.14);border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;}
.tb-btn:hover{background:rgba(255,255,255,.16);}
.tb-print{background:#2563eb;border-color:#2563eb;}
.tb-sel{padding:6px 10px;background:rgba(255,255,255,.08);color:#e2e8f0;border:1px solid rgba(255,255,255,.14);border-radius:8px;font-size:12px;max-width:220px;}
.tb-sel option{color:#0f172a;}

.doc{max-width:1300px;margin:20px auto;background:#fff;padding:28px 30px;box-shadow:0 4px 24px rgba(0,0,0,.12);}
.doc-title{font-size:19px;font-weight:800;color:#0f172a;margin:0 0 12px;border-bottom:3px solid #0f172a;padding-bottom:9px;}
.legende{display:flex;gap:20px;margin-bottom:12px;font-size:11.5px;color:#475569;}
.lg{display:flex;align-items:center;gap:7px;font-weight:600;}
.empty{text-align:center;color:#94a3b8;padding:40px;font-size:14px;}
.empty i{font-size:34px;display:block;opacity:.3;margin-bottom:8px;}

.scroll{overflow-x:auto;}
.gantt{border-collapse:collapse;font-size:10.5px;width:100%;}
.gantt th,.gantt td{border:1px solid #e2e8f0;}
.gantt thead th{background:#f1f5f9;padding:5px 6px;font-size:9px;font-weight:700;color:#475569;text-transform:uppercase;white-space:nowrap;position:sticky;top:0;}
.c-info{min-width:230px;max-width:280px;text-align:left;padding:5px 8px;}
.c-resp{min-width:90px;text-align:left;padding:5px 8px;white-space:nowrap;}
.c-month{width:44px;min-width:44px;text-align:center;}
.r-proc td{background:#dbeafe;font-weight:800;color:#1e3a8a;padding:5px 9px;border-color:#bfdbfe;}
.pc{font-family:monospace;background:#1e3a8a;color:#fff;padding:1px 6px;border-radius:4px;font-size:10px;}
.r-act td{background:#ccfbf1;font-weight:700;color:#0f766e;padding:4px 9px;}
.ac{font-family:monospace;background:#0f766e;color:#fff;padding:1px 5px;border-radius:3px;font-size:9px;}
.r-plan:hover{background:#fafbff;}
.pl-title{font-weight:600;color:#0f172a;}
.pl-sub{font-size:9px;color:#64748b;margin-top:1px;}
.chip-code{font-family:monospace;font-weight:700;color:#4338ca;background:#ede9fe;padding:0 4px;border-radius:3px;}
.c-cell{position:relative;height:30px;padding:0;}
.bar{position:absolute;left:-1px;right:-1px;height:9px;}
.bar-prev{top:5px;background:#93c5fd;border-top:1px solid #3b82f6;border-bottom:1px solid #3b82f6;}
.bar-real{top:16px;background:#6ee7b7;border-top:1px solid #059669;border-bottom:1px solid #059669;}
.seg-start{left:4px;border-left:1px solid;border-top-left-radius:5px;border-bottom-left-radius:5px;}
.bar-prev.seg-start{border-left-color:#3b82f6;}
.bar-real.seg-start{border-left-color:#059669;}
.seg-end{right:4px;border-right:1px solid;border-top-right-radius:5px;border-bottom-right-radius:5px;}
.bar-prev.seg-end{border-right-color:#3b82f6;}
.bar-real.seg-end{border-right-color:#059669;}
.foot{margin-top:16px;padding-top:10px;border-top:1px solid #e2e8f0;font-size:10px;color:#94a3b8;text-align:center;}

@media print{
  .no-print{display:none!important;}
  .g-wrap{background:#fff;padding:0;}
  .doc{box-shadow:none;margin:0;max-width:100%;padding:0;}
  .gantt{font-size:8px;}
  .c-month{width:30px;min-width:30px;}
}
</style>
