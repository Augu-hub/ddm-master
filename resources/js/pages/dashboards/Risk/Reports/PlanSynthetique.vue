<template>
  <div class="ps-wrap">
    <div class="toolbar no-print">
      <Link :href="route('risk.core.reports.index')" class="tb-btn"><i class="ti ti-arrow-left"></i> Rapports</Link>
      <div class="tb-title"><i class="ti ti-table"></i> Plan d'action synthétique</div>
      <button class="tb-btn tb-print" @click="print"><i class="ti ti-printer"></i> Imprimer / PDF</button>
    </div>

    <div class="doc">
      <h1 class="doc-title">Plan d'action synthétique des recommandations</h1>

      <div v-if="!processes.length" class="empty"><i class="ti ti-inbox"></i> Aucun plan d'action enregistré.</div>

      <div v-for="p in processes" :key="p.code" class="proc-block">
        <div class="proc-hdr"><span class="pc">{{ p.code || '—' }}</span> {{ p.name || 'Processus' }}
          <span class="proc-taux">Avancement processus : <b>{{ p.taux }} %</b></span>
        </div>

        <div v-for="a in p.activities" :key="a.code" class="act-block">
          <div class="act-hdr"><span class="ac">{{ a.code || '—' }}</span> {{ a.name || 'Sous-processus' }}</div>
          <table class="tbl">
            <thead>
              <tr>
                <th class="nrr">NRR</th><th>Code risque</th><th>Recommandation / Action</th><th>Responsable</th>
                <th class="num">Coût risque</th><th class="num">Coût recomm.</th><th>Traitement</th>
                <th>Début</th><th>Échéance</th><th>État</th><th class="num">Taux</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="pl in a.plans" :key="pl.id">
                <td class="nrr"><span class="nrr-chip" :style="{background:pl.res_zone_color||'#94a3b8'}">{{ pl.nrr!=null ? pl.nrr : '—' }}</span></td>
                <td><span class="chip-code">{{ pl.code_risk }}</span></td>
                <td>
                  <Link v-if="pl.risk_id" :href="route('risk.core.reports.plan-recommandation', pl.risk_id)" class="reco-link no-print" title="Voir le détail par recommandation">{{ pl.title }} <i class="ti ti-external-link"></i></Link>
                  <span class="reco-print">{{ pl.title }}</span>
                </td>
                <td>{{ pl.responsable || '—' }}</td>
                <td class="num">{{ money(pl.cout_risque) }}</td>
                <td class="num">{{ money(pl.cost_estimate) }}</td>
                <td>{{ pl.decision || '—' }}</td>
                <td>{{ fmtDate(pl.start_date) }}</td>
                <td>{{ fmtDate(pl.target_date) }}</td>
                <td><span class="etat" :class="etatClass(pl)">{{ etatLabel(pl) }}</span></td>
                <td class="num"><b>{{ pl.progress || 0 }} %</b></td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="tot"><td colspan="10">Total {{ a.name }}</td><td class="num"><b>{{ a.taux }} %</b></td></tr>
            </tfoot>
          </table>
        </div>
      </div>

      <div class="legend no-print">
        <b>Règle d'état :</b> taux = 100 % ⇒ « Mise en œuvre » (vert) ; sinon « En cours » (rose). Le taux provient du plan d'action détaillé (tâches).
      </div>
      <footer class="foot">DIADDEM RISK · généré le {{ today }}</footer>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({ processes: { type: Array, default: () => [] } })

const fmtDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: '2-digit' }) : '—'
const money = (v) => (v == null || v === '' || Number(v) === 0) ? '—' : new Intl.NumberFormat('fr-FR').format(v)
const etatLabel = (pl) => (pl.progress >= 100) ? 'Mise en œuvre' : (pl.status === 'cancelled' ? 'Annulé' : 'En cours')
const etatClass = (pl) => (pl.progress >= 100) ? 'et-ok' : (pl.status === 'cancelled' ? 'et-no' : 'et-run')
const today = new Date().toLocaleDateString('fr-FR')
const print = () => window.print()
</script>

<style scoped>
.ps-wrap{background:#e2e8f0;min-height:100vh;font-family:'Inter',system-ui,sans-serif;color:#1e293b;padding-bottom:30px;}
.toolbar{position:sticky;top:0;z-index:20;display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 18px;background:#0f172a;}
.tb-title{color:#e2e8f0;font-size:13px;font-weight:700;display:flex;align-items:center;gap:6px;}
.tb-btn{display:flex;align-items:center;gap:5px;padding:7px 13px;background:rgba(255,255,255,.08);color:#e2e8f0;border:1px solid rgba(255,255,255,.14);border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;}
.tb-btn:hover{background:rgba(255,255,255,.16);}
.tb-print{background:#2563eb;border-color:#2563eb;}

.doc{max-width:1100px;margin:20px auto;background:#fff;padding:30px 34px;box-shadow:0 4px 24px rgba(0,0,0,.12);}
.doc-title{font-size:19px;font-weight:800;color:#0f172a;margin:0 0 16px;border-bottom:3px solid #0f172a;padding-bottom:9px;}
.empty{text-align:center;color:#94a3b8;padding:40px;font-size:14px;}
.empty i{font-size:34px;display:block;opacity:.3;margin-bottom:8px;}

.proc-block{margin-bottom:20px;}
.proc-hdr{background:#dbeafe;border:1px solid #bfdbfe;border-radius:7px;padding:8px 12px;font-size:13px;font-weight:800;color:#1e3a8a;display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.proc-taux{margin-left:auto;font-size:11px;font-weight:600;color:#1e40af;}
.pc{font-family:monospace;background:#1e3a8a;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;}
.act-block{margin:8px 0 0;}
.act-hdr{background:#ccfbf1;border:1px solid #99f6e4;border-left:none;border-right:none;padding:5px 12px;font-size:12px;font-weight:700;color:#0f766e;}
.ac{font-family:monospace;background:#0f766e;color:#fff;padding:1px 6px;border-radius:3px;font-size:10px;}

.tbl{width:100%;border-collapse:collapse;font-size:10.5px;}
.tbl th{background:#f1f5f9;border:1px solid #e2e8f0;padding:5px 7px;text-align:left;font-size:9px;font-weight:700;color:#475569;text-transform:uppercase;white-space:nowrap;}
.tbl td{border:1px solid #e2e8f0;padding:5px 7px;vertical-align:top;}
.tbl .num{text-align:right;white-space:nowrap;}
.tbl .nrr{text-align:center;width:44px;}
.nrr-chip{display:inline-block;min-width:22px;padding:1px 7px;border-radius:6px;color:#fff;font-weight:800;font-size:10px;}
.chip-code{font-family:monospace;font-weight:700;color:#4338ca;background:#ede9fe;padding:1px 5px;border-radius:3px;font-size:9.5px;}
.reco-link{color:#0f172a;text-decoration:none;font-weight:600;}
.reco-link:hover{color:#4f46e5;text-decoration:underline;}
.reco-link i{font-size:10px;color:#94a3b8;}
.reco-print{display:none;}
.etat{padding:2px 8px;border-radius:8px;font-weight:700;font-size:9px;white-space:nowrap;}
.et-ok{background:#dcfce7;color:#15803d;}.et-run{background:#fce7f3;color:#be185d;}.et-no{background:#fee2e2;color:#dc2626;}
.tot td{background:#f8fafc;font-weight:800;color:#334155;border:1px solid #e2e8f0;}

.legend{margin-top:14px;font-size:10.5px;color:#64748b;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:8px;padding:8px 11px;}
.foot{margin-top:16px;padding-top:10px;border-top:1px solid #e2e8f0;font-size:10px;color:#94a3b8;text-align:center;}

@media print{
  .no-print{display:none!important;}
  .reco-print{display:inline!important;}
  .ps-wrap{background:#fff;padding:0;}
  .doc{box-shadow:none;margin:0;max-width:100%;padding:0 5mm;}
  .proc-block{page-break-inside:avoid;}
}
</style>
