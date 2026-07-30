<template>
  <div class="stepper">
    <!-- Session active (visible en permanence) -->
    <div class="sess" v-if="session">
      <i class="ti ti-versions"></i>
      <div>
        <div class="sess-name">{{ session.name }}<span v-if="session.year"> · {{ session.year }}</span></div>
        <div class="sess-tag">
          <span v-if="session.is_active" class="t-act">session active</span>
          <span v-else class="t-inact">non active</span>
          <span v-if="session.is_frozen" class="t-frozen">gelée</span>
        </div>
      </div>
    </div>
    <div class="sess sess--none" v-else>
      <i class="ti ti-alert-circle"></i>
      <Link :href="route('risk.core.eval-sessions.index')" class="sess-link">Aucune session active — en créer une</Link>
    </div>

    <!-- Étapes séquentielles -->
    <div class="steps">
      <template v-for="(s, i) in steps" :key="s.key">
        <Link :href="route(s.route)"
              :class="['step', current===s.key?'step--on':'', isDone(s.key)?'step--done':'', reachable(i)?'':'step--lock']">
          <span class="s-num">
            <i v-if="isDone(s.key) && current!==s.key" class="ti ti-check"></i>
            <template v-else>{{ i+1 }}</template>
          </span>
          <span class="s-body">
            <span class="s-lbl">{{ s.label }}</span>
            <span class="s-cnt">{{ prog[s.key] || 0 }}/{{ prog.total || 0 }}</span>
          </span>
        </Link>
        <i v-if="i < steps.length-1" class="ti ti-chevron-right sep"></i>
      </template>

      <i class="ti ti-chevron-right sep sep--carto"></i>
      <Link :href="route('risk.core.evaluation.cartographie')" :class="['step step--carto', current==='cartographie'?'step--on':'']">
        <span class="s-num"><i class="ti ti-map-2"></i></span>
        <span class="s-body"><span class="s-lbl">Cartographie</span><span class="s-cnt">synthèse</span></span>
      </Link>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const props = defineProps({
  current: { type: String, default: 'inherent' }, // inherent|controle|residuel|cible|cartographie
})

const page = usePage()
const session = computed(() => page.props.activeSession || null)
const prog = computed(() => page.props.stepProgress || { total: 0 })

const steps = [
  { key: 'inherent', label: 'Inhérente',       route: 'risk.core.evaluation.inherente' },
  { key: 'controle', label: 'Mise sous contrôle', route: 'risk.core.evaluation.controle' },
  { key: 'residuel', label: 'Résiduelle',      route: 'risk.core.evaluation.residuelle' },
  { key: 'cible',    label: 'Cible',           route: 'risk.core.evaluation.cible' },
]

const order = ['inherent', 'controle', 'residuel', 'cible']
const isDone = (key) => (prog.value[key] || 0) > 0
// Une étape est atteignable si la précédente a au moins un risque traité (séquence).
const reachable = (i) => i === 0 || (prog.value[order[i - 1]] || 0) > 0
</script>

<style scoped>
.stepper{display:flex;align-items:center;gap:16px;padding:9px 22px;background:#111c33;border-bottom:1px solid rgba(255,255,255,.06);flex-wrap:wrap;}
.sess{display:flex;align-items:center;gap:9px;padding:5px 12px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:10px;color:#e2e8f0;}
.sess>i{font-size:18px;color:#818cf8;}
.sess-name{font-size:12px;font-weight:800;color:#f1f5f9;line-height:1.1;}
.sess-tag{display:flex;gap:5px;margin-top:2px;}
.sess-tag span{font-size:8.5px;font-weight:700;padding:1px 6px;border-radius:6px;}
.t-act{background:#065f46;color:#6ee7b7;}
.t-inact{background:#7c2d12;color:#fdba74;}
.t-frozen{background:#1e3a8a;color:#bfdbfe;}
.sess--none{color:#fca5a5;}.sess--none>i{color:#f87171;}
.sess-link{color:#fca5a5;font-size:11px;font-weight:700;text-decoration:underline;}

.steps{display:flex;align-items:center;gap:4px;flex-wrap:wrap;flex:1;}
.step{display:flex;align-items:center;gap:8px;padding:5px 12px;border-radius:9px;text-decoration:none;border:1.5px solid transparent;transition:all .12s;}
.s-num{width:22px;height:22px;border-radius:50%;background:rgba(255,255,255,.12);color:#cbd5e1;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;flex-shrink:0;}
.s-body{display:flex;flex-direction:column;line-height:1.1;}
.s-lbl{font-size:12px;font-weight:700;color:#cbd5e1;}
.s-cnt{font-size:9px;color:#64748b;font-weight:600;margin-top:1px;}
.step:hover{background:rgba(255,255,255,.06);}
.step--on{background:rgba(99,102,241,.18);border-color:#6366f1;}
.step--on .s-lbl{color:#fff;}
.step--on .s-num{background:#6366f1;color:#fff;}
.step--done .s-num{background:#059669;color:#fff;}
.step--done .s-lbl{color:#a7f3d0;}
.step--lock{opacity:.55;}
.step--lock .s-lbl::after{content:' 🔒';font-size:9px;}
.step--carto .s-num{background:rgba(129,140,248,.25);color:#c7d2fe;}
.step--carto .s-lbl{color:#c7d2fe;}
.sep{color:#334155;font-size:14px;}
.sep--carto{color:#475569;}
</style>
