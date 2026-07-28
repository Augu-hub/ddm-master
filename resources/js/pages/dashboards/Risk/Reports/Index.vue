<template>
  <VerticalLayout>
    <div class="page">

      <!-- HEADER -->
      <div class="page-hdr">
        <div class="hdr-left">
          <div class="hdr-icon"><i class="ti ti-chart-histogram"></i></div>
          <div><h1>Rapports & Tableau de bord</h1><p>Vue de pilotage en temps réel et génération des rapports de session</p></div>
        </div>
      </div>

      <!-- ══════════ ZONE 1 : TABLEAU DE BORD (LIVE) ══════════ -->
      <div class="zone">
        <div class="zone-hdr">
          <span class="zone-tag zone-tag--live"><i class="ti ti-broadcast"></i> Tableau de bord</span>
          <span class="zone-sub">Indicateurs live du registre — indépendants des sessions</span>
        </div>

        <div class="tiles">
          <div class="tile"><div class="ti-ic" style="background:#0f172a"><i class="ti ti-alert-triangle"></i></div><div><b>{{ dashboard.risks_total }}</b><small>Risques au registre</small></div></div>
          <div class="tile"><div class="ti-ic" style="background:#6366f1"><i class="ti ti-checkbox"></i></div><div><b>{{ dashboard.risks_evaluated }}</b><small>Évalués (inhérent)</small></div></div>
          <div class="tile"><div class="ti-ic" style="background:#f59e0b"><i class="ti ti-shield-half"></i></div><div><b>{{ dashboard.risks_residual }}</b><small>Avec résiduel</small></div></div>
          <div class="tile"><div class="ti-ic" style="background:#10b981"><i class="ti ti-target-arrow"></i></div><div><b>{{ dashboard.risks_target }}</b><small>Avec cible</small></div></div>
          <div class="tile"><div class="ti-ic" :style="{background: dashboard.plans.overdue ? '#ef4444':'#0d9488'}"><i class="ti ti-clock-x"></i></div><div><b>{{ dashboard.plans.overdue }}</b><small>Plans en retard</small></div></div>
          <div class="tile"><div class="ti-ic" style="background:#2563eb"><i class="ti ti-trending-up"></i></div><div><b>{{ dashboard.plans.taux }}%</b><small>Avancement plans</small></div></div>
        </div>

        <div class="db-grid">
          <div class="card">
            <div class="card-hdr"><i class="ti ti-map-2"></i> Répartition résiduelle par zone</div>
            <div class="card-body">
              <div v-for="z in dashboard.zones" :key="z.label" class="zrow">
                <span class="zlbl"><i class="zdot" :style="{background:z.color_code}"></i>{{ z.label }}</span>
                <div class="zbar"><div class="zfill" :style="{width: zpct(z.label)+'%', background:z.color_code}"></div></div>
                <span class="zval">{{ dashboard.zoneDist[z.label]||0 }}</span>
              </div>
              <div v-if="!dashboard.zones.length" class="muted">Aucune configuration de matrice active.</div>
            </div>
          </div>
          <div class="card">
            <div class="card-hdr"><i class="ti ti-list-check"></i> Plans d'action</div>
            <div class="card-body kv">
              <div><span>Total</span><b>{{ dashboard.plans.total }}</b></div>
              <div><span>Terminés</span><b>{{ dashboard.plans.completed }}</b></div>
              <div><span>En retard</span><b :class="dashboard.plans.overdue?'txt-red':''">{{ dashboard.plans.overdue }}</b></div>
              <div><span>Avancement global</span><b>{{ dashboard.plans.taux }}%</b></div>
              <div><span>Incidents recensés</span><b>{{ dashboard.incidents }}</b></div>
              <Link :href="route('risk.core.action-plan.tracking')" class="mini-link"><i class="ti ti-arrow-right"></i> Ouvrir le suivi des plans</Link>
            </div>
          </div>
        </div>
      </div>

      <!-- ══════════ ZONE 2 : RAPPORTS À GÉNÉRER (SESSION) ══════════ -->
      <div class="zone">
        <div class="zone-hdr">
          <span class="zone-tag zone-tag--rep"><i class="ti ti-file-report"></i> Rapports à générer</span>
          <span class="zone-sub">Documents liés à une <b>session d'évaluation</b> — figés ou à l'état vivant</span>
        </div>

        <div class="sess-pick">
          <label>Session d'évaluation</label>
          <select v-model="sessionId" class="sel">
            <option v-if="!sessions.length" :value="null">Aucune session — en créer une d'abord</option>
            <option v-for="s in sessions" :key="s.id" :value="s.id">
              {{ s.name }} <template v-if="s.year">({{ s.year }})</template>{{ s.is_active?' · active':'' }}{{ s.is_frozen?' · gelée':' · vivante' }}
            </option>
          </select>
          <Link :href="route('risk.core.eval-sessions.index')" class="mini-link"><i class="ti ti-versions"></i> Gérer les sessions</Link>
        </div>

        <div class="rep-grid">
          <component :is="sessionId ? Link : 'div'" :href="sessionId ? route('risk.core.eval-sessions.report', sessionId) : undefined" :class="['rep-card', !sessionId?'rep-card--off':'']">
            <div class="rc-ic" style="background:#0d9488"><i class="ti ti-file-report"></i></div>
            <div class="rc-txt"><b>Rapport de gestion & cartographie</b><small>Synthèse exécutive, profil N-1/N, KPI, plan de mitigation — éditable & imprimable</small></div>
            <i class="ti ti-arrow-right rc-arr"></i>
          </component>

          <component :is="sessionId ? Link : 'div'" :href="sessionId ? route('risk.core.evaluation.cartographie', { session_id: sessionId }) : undefined" :class="['rep-card', !sessionId?'rep-card--off':'']">
            <div class="rc-ic" style="background:#4f46e5"><i class="ti ti-map-2"></i></div>
            <div class="rc-txt"><b>Cartographie de synthèse</b><small>Matrices Inhérent / Résiduel / Cible + trajectoire, par activité/processus</small></div>
            <i class="ti ti-arrow-right rc-arr"></i>
          </component>

          <component :is="sessionId ? Link : 'div'" :href="sessionId ? route('risk.core.eval-sessions.compare', { a: sessionId }) : undefined" :class="['rep-card', !sessionId?'rep-card--off':'']">
            <div class="rc-ic" style="background:#0891b2"><i class="ti ti-git-compare"></i></div>
            <div class="rc-txt"><b>Comparaison d'évolution</b><small>Évolution des risques entre deux sessions (aggravés / améliorés)</small></div>
            <i class="ti ti-arrow-right rc-arr"></i>
          </component>

          <Link :href="route('risk.core.reports.plan-synthetique')" class="rep-card">
            <div class="rc-ic" style="background:#d97706"><i class="ti ti-table"></i></div>
            <div class="rc-txt"><b>Plan d'action synthétique</b><small>Tableau des recommandations par processus + taux d'avancement</small></div>
            <i class="ti ti-arrow-right rc-arr"></i>
          </Link>

          <Link :href="route('risk.core.reports.gantt')" class="rep-card">
            <div class="rc-ic" style="background:#0f766e"><i class="ti ti-timeline"></i></div>
            <div class="rc-txt"><b>Diagramme de Gantt</b><small>Planning des recommandations — prévisionnel & réalisé, par processus</small></div>
            <i class="ti ti-arrow-right rc-arr"></i>
          </Link>

          <div class="rep-card rep-card--form">
            <div class="rc-ic" style="background:#7c3aed"><i class="ti ti-file-description"></i></div>
            <div class="rc-txt">
              <b>Fiche risque synthétique</b>
              <small>Une fiche par risque (cotation I/R/Cible, contrôles, traitement, mini-carte)</small>
              <div class="fiche-row">
                <select v-model="ficheRiskId" class="sel sel-sm">
                  <option :value="null">Choisir un risque…</option>
                  <option v-for="r in riskList" :key="r.id" :value="r.id">{{ r.code_risk }} — {{ r.libelle }}</option>
                </select>
                <button class="btn-open" :disabled="!ficheRiskId" @click="openFiche"><i class="ti ti-external-link"></i> Ouvrir</button>
              </div>
            </div>
          </div>

          <div class="rep-card rep-card--form">
            <div class="rc-ic" style="background:#be185d"><i class="ti ti-clipboard-list"></i></div>
            <div class="rc-txt">
              <b>Plan d'action par recommandation</b>
              <small>Détail d'une recommandation : actions, coûts, responsables, dates, état, taux</small>
              <div class="fiche-row">
                <select v-model="recoRiskId" class="sel sel-sm">
                  <option :value="null">Choisir un risque…</option>
                  <option v-for="r in riskList" :key="r.id" :value="r.id">{{ r.code_risk }} — {{ r.libelle }}</option>
                </select>
                <button class="btn-open" style="background:#be185d" :disabled="!recoRiskId" @click="openReco"><i class="ti ti-external-link"></i> Ouvrir</button>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </VerticalLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps({
  dashboard:     { type: Object, default: () => ({ zones: [], zoneDist: {}, plans: {} }) },
  sessions:      { type: Array,  default: () => [] },
  activeSession: { type: Object, default: () => null },
  riskList:      { type: Array,  default: () => [] },
})

const sessionId = ref(props.activeSession?.id ?? props.sessions[0]?.id ?? null)
const ficheRiskId = ref(null)
const recoRiskId = ref(null)

const zpct = (label) => {
  const max = Math.max(1, ...Object.values(props.dashboard.zoneDist || { x: 1 }))
  return Math.round(((props.dashboard.zoneDist?.[label] || 0) / max) * 100)
}
const openFiche = () => { if (ficheRiskId.value) router.visit(route('risk.core.reports.fiche', ficheRiskId.value)) }
const openReco = () => { if (recoRiskId.value) router.visit(route('risk.core.reports.plan-recommandation', recoRiskId.value)) }
</script>

<style scoped>
.page{background:#f0f4f8;min-height:calc(100vh - 60px);font-family:'Inter',system-ui,sans-serif;font-size:13px;padding-bottom:30px;}
.page-hdr{display:flex;align-items:center;justify-content:space-between;padding:12px 22px;background:#0f172a;}
.hdr-left{display:flex;align-items:center;gap:12px;}
.hdr-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;color:#fff;background:linear-gradient(135deg,#0891b2,#6366f1);}
.page-hdr h1{font-size:16px;font-weight:800;color:#f1f5f9;margin:0;}
.page-hdr p{font-size:11px;color:#64748b;margin:0;}

.zone{margin:16px 22px;background:#fff;border:1px solid #e9eef5;border-radius:16px;padding:16px 18px;}
.zone-hdr{display:flex;align-items:center;gap:12px;margin-bottom:14px;flex-wrap:wrap;}
.zone-tag{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:800;padding:5px 12px;border-radius:9px;}
.zone-tag--live{background:#ecfdf5;color:#047857;}
.zone-tag--rep{background:#eef2ff;color:#4338ca;}
.zone-sub{font-size:11.5px;color:#94a3b8;}

.tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(155px,1fr));gap:10px;margin-bottom:14px;}
.tile{display:flex;align-items:center;gap:10px;background:#f8fafc;border:1px solid #eef2f7;border-radius:11px;padding:10px 12px;}
.ti-ic{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff;flex-shrink:0;}
.tile b{font-size:19px;font-weight:900;color:#0f172a;display:block;line-height:1;}
.tile small{font-size:10px;color:#64748b;font-weight:600;}

.db-grid{display:grid;grid-template-columns:1.4fr 1fr;gap:12px;}
@media(max-width:860px){.db-grid{grid-template-columns:1fr;}}
.card{border:1px solid #eef2f7;border-radius:12px;overflow:hidden;}
.card-hdr{display:flex;align-items:center;gap:7px;padding:10px 13px;background:#f8fafc;border-bottom:1px solid #eef2f7;font-size:12px;font-weight:700;color:#334155;}
.card-body{padding:13px;}
.zrow{display:flex;align-items:center;gap:9px;margin-bottom:7px;font-size:12px;}
.zlbl{width:150px;font-weight:600;color:#334155;display:flex;align-items:center;gap:6px;}
.zdot{width:11px;height:11px;border-radius:3px;display:inline-block;}
.zbar{flex:1;height:14px;background:#f1f5f9;border-radius:7px;overflow:hidden;}
.zfill{height:100%;border-radius:7px;min-width:2px;transition:width .3s;}
.zval{width:30px;text-align:right;font-weight:800;color:#0f172a;}
.kv{display:flex;flex-direction:column;gap:7px;}
.kv>div{display:flex;justify-content:space-between;font-size:12.5px;padding:3px 0;border-bottom:1px dashed #eef2f7;}
.kv b{color:#0f172a;font-weight:800;}
.txt-red{color:#dc2626!important;}
.muted{color:#94a3b8;font-size:12px;}
.mini-link{display:inline-flex;align-items:center;gap:5px;margin-top:8px;font-size:11px;font-weight:700;color:#4f46e5;text-decoration:none;}

.sess-pick{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px;padding:11px 13px;background:#f8fafc;border:1px solid #eef2f7;border-radius:11px;}
.sess-pick label{font-size:11px;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.03em;}
.sel{padding:7px 11px;border:1px solid #e2e8f0;border-radius:8px;font-size:12.5px;background:#fff;min-width:250px;cursor:pointer;}
.sel-sm{min-width:180px;padding:5px 9px;font-size:11.5px;}

.rep-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:12px;}
.rep-card{display:flex;align-items:center;gap:12px;background:#fff;border:1px solid #e9eef5;border-radius:12px;padding:13px 14px;text-decoration:none;color:inherit;transition:all .12s;}
.rep-card:hover{border-color:#c7d2fe;box-shadow:0 3px 12px rgba(79,70,229,.1);transform:translateY(-1px);}
.rep-card--off{opacity:.5;pointer-events:none;}
.rep-card--form{flex-direction:row;align-items:flex-start;}
.rc-ic{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:19px;color:#fff;flex-shrink:0;}
.rc-txt{flex:1;min-width:0;}
.rc-txt b{font-size:13px;font-weight:800;color:#0f172a;display:block;}
.rc-txt small{font-size:10.5px;color:#64748b;line-height:1.4;display:block;margin-top:2px;}
.rc-arr{color:#cbd5e1;font-size:18px;}
.rep-card:hover .rc-arr{color:#4f46e5;}
.fiche-row{display:flex;gap:6px;margin-top:8px;align-items:center;flex-wrap:wrap;}
.btn-open{display:inline-flex;align-items:center;gap:4px;padding:5px 11px;background:#7c3aed;color:#fff;border:none;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;}
.btn-open:disabled{opacity:.5;cursor:not-allowed;}
</style>
