<template>
  <div class="fr-wrap">
    <div class="toolbar no-print">
      <Link :href="route('risk.core.reports.index')" class="tb-btn"><i class="ti ti-arrow-left"></i> Rapports</Link>
      <div class="tb-title"><i class="ti ti-file-description"></i> Fiche risque — {{ risk.code_risk }}</div>
      <button class="tb-btn tb-print" @click="print"><i class="ti ti-printer"></i> Imprimer / PDF</button>
    </div>

    <div class="doc">
      <h1 class="doc-title">Fiche de risque synthétique</h1>

      <!-- Contexte -->
      <table class="ctx">
        <tr>
          <th>Code processus</th><td>{{ risk.process_code || '—' }}</td>
          <th colspan="2">Libellé processus</th><td colspan="2">{{ risk.process_name || '—' }}</td>
        </tr>
        <tr>
          <th>Code S/processus</th><td>{{ risk.activity_code || '—' }}</td>
          <th colspan="2">Libellé sous-processus (activité)</th><td colspan="2">{{ risk.activity_name || '—' }}</td>
        </tr>
        <tr>
          <th>Entité</th><td>{{ risk.entity_name || '—' }}</td>
          <th>Type (nomenclature)</th><td>{{ risk.nomenclature_code || '—' }}</td>
          <th>Propriétaire</th><td>{{ risk.owner || '—' }}</td>
        </tr>
        <tr>
          <th>Code risque</th><td><span class="chip-code">{{ risk.code_risk }}</span></td>
          <th>Libellé risque</th><td colspan="3" class="rlib">{{ risk.libelle }}</td>
        </tr>
      </table>

      <!-- Évaluation I / R / Cible -->
      <table class="eval">
        <thead>
          <tr><th class="lead">Évaluation</th><th class="c-inh">Inhérent</th><th class="c-res">Résiduel</th><th class="c-tgt">Cible</th></tr>
        </thead>
        <tbody>
          <tr><td>Coût du risque</td><td>{{ money(risk.cout_risque) }}</td><td>{{ money(risk.cout_risque) }}</td><td>{{ money(risk.cout_risque) }}</td></tr>
          <tr><td>Niveau Impact</td><td>{{ nn(risk.inh_impact) }}</td><td>{{ nn(risk.res_impact) }}</td><td>{{ nn(risk.tgt_impact) }}</td></tr>
          <tr><td>Niveau Fréquence</td><td>{{ nn(risk.inh_freq) }}</td><td>{{ nn(risk.res_freq) }}</td><td>{{ nn(risk.tgt_freq) }}</td></tr>
          <tr class="hl">
            <td>Niveau global (criticité)</td>
            <td><span class="crit" :style="critStyle(risk.inh_crit, risk.inh_zone_color)">{{ nn(risk.inh_crit) }}</span></td>
            <td><span class="crit" :style="critStyle(risk.res_crit, risk.res_zone_color)">{{ nn(risk.res_crit) }}</span></td>
            <td><span class="crit" :style="critStyle(risk.tgt_crit, risk.tgt_zone_color)">{{ nn(risk.tgt_crit) }}</span></td>
          </tr>
          <tr><td>Appellation niveau global</td>
            <td>{{ risk.inh_zone || '—' }}</td><td>{{ risk.res_zone || '—' }}</td><td>{{ risk.tgt_zone || '—' }}</td></tr>
        </tbody>
      </table>

      <!-- Détails -->
      <table class="det">
        <tr><th>Cause probable / Source</th><td>{{ risk.causes || '—' }}</td></tr>
        <tr><th>Conséquences</th><td>{{ risk.consequences || '—' }}</td></tr>
        <tr><th>Procédures de mise sous contrôle actuel</th><td>{{ control?.control_procedure || control?.description || risk.controles_existants || '—' }}</td></tr>
        <tr>
          <th>Efficacité / Niveau de maîtrise</th>
          <td>
            <span v-if="control?.efficacite!=null">Efficacité : {{ control.efficacite }}/100</span>
            <span v-if="control?.mastery_label" class="mastery" :style="{background:(control.mastery_color||'#64748b')+'22',color:control.mastery_color||'#64748b'}">{{ control.mastery_label }}</span>
            <span v-if="!control">—</span>
          </td>
        </tr>
        <tr><th>Traitement (décision)</th><td>{{ risk.decision || '—' }}</td></tr>
        <tr><th>Recommandation générale</th><td>{{ recommandation || risk.plan_traitement || '—' }}</td></tr>
        <tr><th>Critère de risque (KRI)</th><td>{{ risk.critere_risque || '—' }}</td></tr>
      </table>

      <!-- Carte du risque -->
      <div class="carte-wrap">
        <div class="carte-title">Carte du risque</div>
        <div class="carte-flex">
          <table class="mini-matrix">
            <thead>
              <tr><th></th><th v-for="f in freqCols" :key="f.score">{{ f.score }}</th></tr>
            </thead>
            <tbody>
              <tr v-for="im in impactRows" :key="im.score">
                <th>{{ im.score }}</th>
                <td v-for="f in freqCols" :key="f.score" :style="{background:cellColor(im.score*f.score)}">
                  <span v-if="isCell('inh',im.score,f.score)" class="mk mk-inh" title="Inhérent"></span>
                  <span v-if="isCell('res',im.score,f.score)" class="mk mk-res" title="Résiduel"></span>
                  <span v-if="isCell('tgt',im.score,f.score)" class="mk mk-tgt" title="Cible"></span>
                </td>
              </tr>
            </tbody>
          </table>
          <div class="carte-side">
            <div class="axis-note"><b>↑ Impact</b> · <b>Fréquence →</b></div>
            <div class="legende">
              <div class="lg"><span class="mk mk-inh"></span> Inhérent</div>
              <div class="lg"><span class="mk mk-res"></span> Résiduel</div>
              <div class="lg"><span class="mk mk-tgt"></span> Objectif (cible)</div>
            </div>
            <div class="zleg">
              <div v-for="z in matrix.zones" :key="z.label" class="zl"><i :style="{background:z.color_code}"></i>{{ z.label }} <span>({{ z.min_score }}–{{ z.max_score }})</span></div>
            </div>
          </div>
        </div>
      </div>

      <footer class="foot">DIADDEM RISK · fiche générée le {{ today }}</footer>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  risk:           { type: Object, default: () => ({}) },
  control:        { type: Object, default: () => null },
  recommandation: { type: String, default: null },
  matrix:         { type: Object, default: () => ({ impacts: [], frequencies: [], zones: [] }) },
})

const impactRows = computed(() => [...(props.matrix.impacts || [])].sort((a, b) => b.score - a.score))
const freqCols   = computed(() => [...(props.matrix.frequencies || [])].sort((a, b) => a.score - b.score))
const zoneFor = (s) => (props.matrix.zones || []).find(z => s >= z.min_score && s <= z.max_score) || null
const cellColor = (s) => zoneFor(s)?.color_code || '#e2e8f0'
const isCell = (kind, imp, freq) => {
  const i = kind === 'inh' ? props.risk.inh_impact : kind === 'res' ? props.risk.res_impact : props.risk.tgt_impact
  const f = kind === 'inh' ? props.risk.inh_freq : kind === 'res' ? props.risk.res_freq : props.risk.tgt_freq
  return Number(i) === imp && Number(f) === freq
}
const nn = (v) => v == null || v === '' ? '—' : v
const money = (v) => (v == null || v === '' || Number(v) === 0) ? '—' : new Intl.NumberFormat('fr-FR').format(v)
const critStyle = (v, color) => v == null ? {} : { background: (color || '#64748b'), color: '#fff' }
const today = new Date().toLocaleDateString('fr-FR')
const print = () => window.print()
</script>

<style scoped>
.fr-wrap{background:#e2e8f0;min-height:100vh;font-family:'Inter',system-ui,sans-serif;color:#1e293b;padding-bottom:30px;}
.toolbar{position:sticky;top:0;z-index:20;display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 18px;background:#0f172a;}
.tb-title{color:#e2e8f0;font-size:13px;font-weight:700;display:flex;align-items:center;gap:6px;}
.tb-btn{display:flex;align-items:center;gap:5px;padding:7px 13px;background:rgba(255,255,255,.08);color:#e2e8f0;border:1px solid rgba(255,255,255,.14);border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;}
.tb-btn:hover{background:rgba(255,255,255,.16);}
.tb-print{background:#2563eb;border-color:#2563eb;}

.doc{max-width:900px;margin:20px auto;background:#fff;padding:34px 40px;box-shadow:0 4px 24px rgba(0,0,0,.12);}
.doc-title{font-size:20px;font-weight:800;color:#0f172a;margin:0 0 16px;border-bottom:3px solid #0f172a;padding-bottom:10px;}

table{width:100%;border-collapse:collapse;font-size:11.5px;margin-bottom:14px;}
.ctx th,.ctx td,.det th,.det td{border:1px solid #cbd5e1;padding:6px 9px;text-align:left;vertical-align:top;}
.ctx th,.det th{background:#f1f5f9;font-weight:700;color:#334155;font-size:10.5px;width:150px;}
.rlib{font-weight:700;color:#0f172a;}
.chip-code{font-family:monospace;font-weight:700;color:#4338ca;background:#ede9fe;padding:2px 7px;border-radius:4px;}

.eval th,.eval td{border:1px solid #cbd5e1;padding:6px 10px;text-align:center;}
.eval .lead{text-align:left;background:#0f172a;color:#fff;}
.eval thead th{font-weight:800;font-size:12px;}
.c-inh{background:#fee2e2;color:#991b1b;}.c-res{background:#fef3c7;color:#92400e;}.c-tgt{background:#dcfce7;color:#166534;}
.eval td:first-child{text-align:left;font-weight:600;color:#475569;background:#f8fafc;}
.eval .hl td{font-weight:800;}
.crit{display:inline-block;min-width:28px;padding:2px 8px;border-radius:6px;font-weight:800;}
.mastery{margin-left:6px;padding:1px 8px;border-radius:7px;font-weight:700;font-size:10px;}

.carte-wrap{border:1px solid #cbd5e1;border-radius:8px;padding:12px;margin-top:6px;}
.carte-title{font-size:11px;font-weight:800;color:#334155;text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px;}
.carte-flex{display:flex;gap:20px;flex-wrap:wrap;align-items:flex-start;}
.mini-matrix{width:auto;border-collapse:separate;border-spacing:3px;}
.mini-matrix th{background:transparent;border:none;font-size:10px;color:#94a3b8;font-weight:700;width:20px;height:20px;text-align:center;padding:0;}
.mini-matrix td{width:44px;height:38px;border-radius:5px;position:relative;border:none;}
.mk{display:inline-block;width:14px;height:14px;position:absolute;box-shadow:0 1px 3px rgba(0,0,0,.4);}
.mk-inh{background:#fca5a5;border:2px solid #ef4444;border-radius:50%;top:4px;left:4px;}
.mk-res{background:#fcd34d;border:2px solid #f59e0b;border-radius:2px;top:4px;right:4px;}
.mk-tgt{background:#6ee7b7;border:2px solid #10b981;border-radius:2px;transform:rotate(45deg);bottom:4px;left:50%;margin-left:-7px;}
.carte-side{font-size:11px;}
.axis-note{color:#64748b;margin-bottom:8px;}
.legende{display:flex;flex-direction:column;gap:6px;margin-bottom:10px;}
.lg{display:flex;align-items:center;gap:8px;font-weight:600;color:#334155;}
.lg .mk{position:static;box-shadow:none;}
.zleg{display:flex;flex-direction:column;gap:3px;}
.zl{display:flex;align-items:center;gap:6px;font-size:10px;color:#475569;}
.zl i{width:11px;height:11px;border-radius:3px;display:inline-block;}
.zl span{color:#94a3b8;}
.foot{margin-top:18px;padding-top:10px;border-top:1px solid #e2e8f0;font-size:10px;color:#94a3b8;text-align:center;}

@media print{
  .no-print{display:none!important;}
  .fr-wrap{background:#fff;padding:0;}
  .doc{box-shadow:none;margin:0;max-width:100%;padding:0 6mm;}
}
</style>
