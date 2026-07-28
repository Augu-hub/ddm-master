<template>
  <div class="pr-wrap">
    <div class="toolbar no-print">
      <Link :href="route('risk.core.reports.index')" class="tb-btn"><i class="ti ti-arrow-left"></i> Rapports</Link>
      <div class="tb-title"><i class="ti ti-clipboard-list"></i> Plan d'action par recommandation — {{ risk.code_risk }}</div>
      <button class="tb-btn tb-print" @click="print"><i class="ti ti-printer"></i> Imprimer / PDF</button>
    </div>

    <div class="doc">
      <h1 class="doc-title">Plan d'action par recommandation</h1>

      <!-- En-tête recommandation -->
      <table class="hdr">
        <tr>
          <th>Code propriétaire</th><td>{{ risk.owner || '—' }}</td>
          <td class="irc irc-i">IR : {{ nn(risk.ir) }}</td>
          <td class="irc irc-f">FR : {{ nn(risk.fr) }}</td>
          <td class="irc irc-n" :style="{background:risk.zone_color||'#334155'}">NGR : {{ nn(risk.ngr) }}</td>
          <th>Date début</th><td>{{ fmtDate(totals.date_debut) }}</td>
          <th>Date fin</th><td>{{ fmtDate(totals.date_fin) }}</td>
        </tr>
        <tr>
          <th>Code risque</th>
          <td><span class="chip-code">{{ risk.code_risk }}</span></td>
          <th>Recommandation générale</th>
          <td colspan="6" class="reco">{{ recommandation || risk.libelle }}</td>
        </tr>
        <tr>
          <th>Processus / Activité</th>
          <td colspan="8">
            <span class="pc">{{ risk.process_code }}</span> {{ risk.process_name }}
            <span v-if="risk.activity_code" class="sep">›</span>
            <span v-if="risk.activity_code" class="ac">{{ risk.activity_code }}</span> {{ risk.activity_name }}
          </td>
        </tr>
      </table>

      <!-- Actions -->
      <table class="acts">
        <thead>
          <tr>
            <th class="c-n">N°</th><th>Libellé action</th><th class="c-cost">Coût action</th>
            <th>Responsable</th><th class="c-date">Date début</th><th class="c-date">Date fin</th>
            <th class="c-etat">État</th><th class="c-taux">Taux</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(a, i) in actions" :key="a.id">
            <td class="c-n">{{ i + 1 }}</td>
            <td>
              <div class="a-title">{{ a.title }}</div>
              <div v-if="a.action_plan" class="a-desc">{{ a.action_plan }}</div>
            </td>
            <td class="c-cost">{{ money(a.cost_estimate) }}</td>
            <td>{{ a.responsable || '—' }}</td>
            <td class="c-date">{{ fmtDate(a.start_date) }}</td>
            <td class="c-date">{{ fmtDate(a.target_date) }}</td>
            <td class="c-etat">
              <span class="chk" :class="(a.progress>=100)?'chk-on':''"><i v-if="a.progress>=100" class="ti ti-check"></i></span>
            </td>
            <td class="c-taux"><b>{{ a.progress || 0 }} %</b></td>
          </tr>
          <tr v-if="!actions.length"><td colspan="8" class="empty">Aucune action rattachée à cette recommandation.</td></tr>
        </tbody>
        <tfoot>
          <tr class="tot">
            <td>Coût risque</td>
            <td class="c-cost"><b>{{ money(totals.cout_risque) }}</b></td>
            <td>Total coûts recomm.</td>
            <td class="c-cost"><b>{{ money(totals.cout_recomm) }}</b></td>
            <td colspan="2" class="r">Taux d'avancement ({{ totals.nb_done }}/{{ totals.nb_actions }} actions)</td>
            <td colspan="2" class="c-taux"><b>{{ totals.taux }} %</b></td>
          </tr>
          <tr class="kri">
            <td>KRI</td><td colspan="7">{{ risk.critere_risque || '—' }}</td>
          </tr>
        </tfoot>
      </table>

      <div class="links no-print">
        <Link :href="route('risk.core.reports.fiche', risk.id)" class="lnk"><i class="ti ti-file-description"></i> Fiche risque</Link>
        <Link :href="route('risk.core.reports.gantt')" class="lnk"><i class="ti ti-timeline"></i> Gantt</Link>
      </div>
      <footer class="foot">DIADDEM RISK · généré le {{ today }}</footer>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  risk:           { type: Object, default: () => ({}) },
  recommandation: { type: String, default: null },
  actions:        { type: Array,  default: () => [] },
  totals:         { type: Object, default: () => ({}) },
})

const nn = (v) => v == null || v === '' ? '—' : v
const fmtDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }) : '—'
const money = (v) => (v == null || v === '' || Number(v) === 0) ? '—' : new Intl.NumberFormat('fr-FR').format(v)
const today = new Date().toLocaleDateString('fr-FR')
const print = () => window.print()
</script>

<style scoped>
.pr-wrap{background:#e2e8f0;min-height:100vh;font-family:'Inter',system-ui,sans-serif;color:#1e293b;padding-bottom:30px;}
.toolbar{position:sticky;top:0;z-index:20;display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 18px;background:#0f172a;flex-wrap:wrap;}
.tb-title{color:#e2e8f0;font-size:13px;font-weight:700;display:flex;align-items:center;gap:6px;}
.tb-btn{display:flex;align-items:center;gap:5px;padding:7px 13px;background:rgba(255,255,255,.08);color:#e2e8f0;border:1px solid rgba(255,255,255,.14);border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;}
.tb-btn:hover{background:rgba(255,255,255,.16);}
.tb-print{background:#2563eb;border-color:#2563eb;}

.doc{max-width:1050px;margin:20px auto;background:#fff;padding:30px 34px;box-shadow:0 4px 24px rgba(0,0,0,.12);}
.doc-title{font-size:19px;font-weight:800;color:#0f172a;margin:0 0 16px;border-bottom:3px solid #0f172a;padding-bottom:9px;}

.hdr{width:100%;border-collapse:collapse;font-size:11.5px;margin-bottom:14px;}
.hdr th,.hdr td{border:1px solid #cbd5e1;padding:6px 9px;text-align:left;vertical-align:middle;}
.hdr th{background:#f1f5f9;font-weight:700;color:#334155;font-size:10.5px;white-space:nowrap;}
.irc{font-weight:800;color:#fff;text-align:center;white-space:nowrap;}
.irc-i{background:#ef4444;}.irc-f{background:#f59e0b;}.irc-n{color:#fff;}
.reco{font-weight:600;color:#0f172a;}
.chip-code{font-family:monospace;font-weight:700;color:#4338ca;background:#ede9fe;padding:2px 7px;border-radius:4px;}
.pc{font-family:monospace;background:#1e3a8a;color:#fff;padding:1px 6px;border-radius:4px;font-size:10px;}
.ac{font-family:monospace;background:#0f766e;color:#fff;padding:1px 5px;border-radius:3px;font-size:9px;}
.sep{color:#cbd5e1;margin:0 4px;}

.acts{width:100%;border-collapse:collapse;font-size:11px;}
.acts th{background:#0f172a;color:#e2e8f0;padding:6px 9px;text-align:left;font-size:9.5px;font-weight:700;text-transform:uppercase;white-space:nowrap;}
.acts td{border:1px solid #e2e8f0;padding:6px 9px;vertical-align:top;}
.acts .c-n{text-align:center;width:34px;}
.acts .c-cost{text-align:right;white-space:nowrap;}
.acts .c-date{white-space:nowrap;}
.acts .c-etat{text-align:center;width:50px;}
.acts .c-taux{text-align:right;white-space:nowrap;width:70px;}
.a-title{font-weight:600;color:#0f172a;}
.a-desc{font-size:9.5px;color:#64748b;margin-top:2px;white-space:pre-wrap;}
.chk{display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border:2px solid #cbd5e1;border-radius:5px;color:#fff;}
.chk-on{background:#16a34a;border-color:#16a34a;}
.empty{text-align:center;color:#94a3b8;padding:16px!important;}
.tot td{background:#f8fafc;font-weight:700;border:1px solid #e2e8f0;}
.tot .r{text-align:right;}
.kri td{background:#fffbeb;border:1px solid #fde68a;font-size:10.5px;}
.kri td:first-child{font-weight:800;color:#92400e;}

.links{display:flex;gap:8px;margin-top:14px;}
.lnk{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;color:#4f46e5;text-decoration:none;padding:5px 11px;border:1px solid #c7d2fe;border-radius:7px;background:#eef2ff;}
.foot{margin-top:16px;padding-top:10px;border-top:1px solid #e2e8f0;font-size:10px;color:#94a3b8;text-align:center;}

@media print{
  .no-print{display:none!important;}
  .pr-wrap{background:#fff;padding:0;}
  .doc{box-shadow:none;margin:0;max-width:100%;padding:0 5mm;}
}
</style>
