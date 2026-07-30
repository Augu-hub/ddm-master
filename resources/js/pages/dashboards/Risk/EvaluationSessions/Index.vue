<template>
  <VerticalLayout>
    <div class="page">

      <!-- HEADER -->
      <div class="page-hdr">
        <div class="page-hdr-left">
          <div class="hdr-icon"><i class="ti ti-versions"></i></div>
          <div>
            <h1>Sessions d'évaluation</h1>
            <p>Campagnes d'évaluation, actualisation d'une session et comparaison d'évolution</p>
          </div>
        </div>
        <div class="page-hdr-right">
          <Link :href="route('risk.core.eval-sessions.compare')" class="btn-ghost"><i class="ti ti-git-compare"></i> Comparer</Link>
          <button class="btn-primary" @click="openCreate"><i class="ti ti-plus"></i> Nouvelle session</button>
        </div>
      </div>

      <!-- BANDEAU REGISTRE VIVANT -->
      <div class="live-bar">
        <span class="live-dot"></span>
        <strong>Registre vivant</strong>
        <span class="live-stat">{{ liveStats.total }} risque(s)</span>
        <span class="live-stat live-stat--i">{{ liveStats.with_inherent }} inhérent</span>
        <span class="live-stat live-stat--r">{{ liveStats.with_residual }} résiduel</span>
        <span class="live-stat live-stat--c">{{ liveStats.with_target }} cible</span>
        <Link :href="route('risk.core.evaluation.cartographie')" class="live-link"><i class="ti ti-map-2"></i> Cartographie actuelle</Link>
      </div>

      <!-- LISTE SESSIONS -->
      <div class="wrap">
        <div v-if="!sessions.length" class="empty">
          <i class="ti ti-versions-off"></i>
          <p>Aucune session d'évaluation. Créez-en une pour figer et comparer vos cartographies dans le temps.</p>
          <button class="btn-primary" @click="openCreate"><i class="ti ti-plus"></i> Créer la première session</button>
        </div>

        <div v-else class="cards">
          <div v-for="s in sessions" :key="s.id" :class="['scard', s.is_active?'scard--active':'']">
            <div class="scard-top">
              <div>
                <span class="scode">{{ s.code || '—' }}</span>
                <span v-if="s.is_active" class="badge-active"><i class="ti ti-broadcast"></i> Active</span>
                <span :class="['badge-status','bs-'+s.status]">{{ statusLabel(s.status) }}</span>
              </div>
              <div class="scard-menu">
                <button class="ic-btn ic-danger" title="Supprimer" @click="destroy(s)"><i class="ti ti-trash"></i></button>
              </div>
            </div>

            <div class="sname">{{ s.name }}</div>
            <div class="smeta">
              <span v-if="s.year"><i class="ti ti-calendar"></i> {{ s.year }}</span>
              <span><i class="ti ti-alert-triangle"></i> {{ s.risks_count }} risque(s) gelé(s)</span>
              <span v-if="s.parent_session_id" class="s-parent"><i class="ti ti-git-branch"></i> actualisée</span>
            </div>
            <div class="ssnap">
              <template v-if="s.snapshot_at"><i class="ti ti-snowflake"></i> Gelée le {{ s.snapshot_at }}</template>
              <template v-else class="ssnap--none"><i class="ti ti-alert-circle"></i> Pas encore gelée</template>
            </div>

            <div class="scard-actions">
              <button v-if="!s.is_active && s.status!=='archived'" class="act act-blue" @click="activate(s)"><i class="ti ti-broadcast"></i> Activer</button>
              <button class="act" :disabled="busy===s.id" @click="snapshot(s)"><i class="ti ti-snowflake"></i> {{ s.snapshot_at?'Regeler':'Geler' }}</button>
              <button class="act act-indigo" @click="openActualiser(s)"><i class="ti ti-refresh"></i> Actualiser</button>
              <Link class="act" :href="route('risk.core.evaluation.cartographie', { session_id: s.id })"><i class="ti ti-map-2"></i> Carto</Link>
              <Link class="act act-teal" :href="route('risk.core.eval-sessions.report', s.id)"><i class="ti ti-file-report"></i> Rapport</Link>
              <Link class="act" :href="route('risk.core.eval-sessions.compare', { a: s.id })"><i class="ti ti-git-compare"></i> Comparer</Link>
              <button v-if="s.status!=='closed' && s.status!=='archived'" class="act act-amber" @click="close(s)"><i class="ti ti-lock"></i> Clôturer</button>
            </div>
          </div>
        </div>
      </div>

      <!-- MODAL CRÉATION / ACTUALISATION -->
      <Teleport to="body">
        <Transition name="mfade">
          <div v-if="modal" class="modal-ov" @click.self="modal=null">
            <div class="modal-box">
              <div class="modal-hdr">
                <i :class="modal==='create'?'ti ti-plus':'ti ti-refresh'"></i>
                <span>{{ modal==='create' ? 'Nouvelle session' : 'Actualiser la session' }}</span>
                <button class="modal-x" @click="modal=null"><i class="ti ti-x"></i></button>
              </div>
              <div class="modal-body">
                <p v-if="modal==='actualiser'" class="modal-note">
                  <i class="ti ti-info-circle"></i>
                  La session <strong>{{ actualiserFrom?.name }}</strong> sera d'abord gelée comme référence, puis une nouvelle session active est créée pour le nouveau cycle.
                </p>
                <label class="fld">
                  <span>Nom de la session</span>
                  <input v-model="form.name" type="text" :placeholder="modal==='actualiser' ? ('Actualisation '+ (form.year||'')) : 'Évaluation annuelle…'" />
                </label>
                <label class="fld">
                  <span>Année</span>
                  <input v-model.number="form.year" type="number" min="2000" max="2100" />
                </label>
                <label class="fld">
                  <span>Notes (optionnel)</span>
                  <textarea v-model="form.notes" rows="2"></textarea>
                </label>
              </div>
              <div class="modal-foot">
                <button class="btn-cancel" @click="modal=null">Annuler</button>
                <button class="btn-primary" :disabled="submitting || (modal==='create' && !form.name)" @click="submit">
                  <i v-if="submitting" class="ti ti-loader-2 ti-spin"></i><i v-else class="ti ti-check"></i>
                  {{ modal==='create' ? 'Créer' : 'Actualiser' }}
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>

      <Transition name="fl">
        <div v-if="flash" class="flash"><i class="ti ti-check-circle"></i> {{ flash }}</div>
      </Transition>
    </div>
  </VerticalLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps({
  sessions:  { type: Array,  default: () => [] },
  liveStats: { type: Object, default: () => ({}) },
})

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || ''
const modal = ref(null)              // 'create' | 'actualiser'
const actualiserFrom = ref(null)
const submitting = ref(false)
const busy = ref(null)
const flash = ref('')
const form = ref({ name:'', year:new Date().getFullYear(), notes:'' })

const statusLabel = (s) => ({draft:'Brouillon', active:'Active', closed:'Clôturée', archived:'Archivée'})[s] || s
const showFlash = (m) => { flash.value=m; setTimeout(()=>flash.value='',3200) }

const openCreate = () => { form.value={ name:'', year:new Date().getFullYear(), notes:'' }; actualiserFrom.value=null; modal.value='create' }
const openActualiser = (s) => { actualiserFrom.value=s; form.value={ name:'', year:(s.year? s.year+1 : new Date().getFullYear()), notes:'' }; modal.value='actualiser' }

const submit = () => {
  submitting.value = true
  const done = { preserveScroll:true, onFinish:()=>{ submitting.value=false; modal.value=null } }
  if (modal.value==='create') {
    router.post(route('risk.core.eval-sessions.store'), form.value, { ...done, onSuccess:()=>showFlash('Session créée ✓') })
  } else {
    router.post(route('risk.core.eval-sessions.actualiser', actualiserFrom.value.id), form.value, { ...done, onSuccess:()=>showFlash('Session actualisée ✓') })
  }
}

const activate = (s) => router.post(route('risk.core.eval-sessions.activate', s.id), {}, { preserveScroll:true, onSuccess:()=>showFlash('Session activée ✓') })
const close    = (s) => { if (confirm(`Clôturer la session « ${s.name} » ? Le registre sera gelé.`)) router.post(route('risk.core.eval-sessions.close', s.id), {}, { preserveScroll:true, onSuccess:()=>showFlash('Session clôturée ✓') }) }
const destroy  = (s) => { if (confirm(`Supprimer la session « ${s.name} » et ses données gelées ?`)) router.delete(route('risk.core.eval-sessions.destroy', s.id), { preserveScroll:true, onSuccess:()=>showFlash('Session supprimée ✓') }) }

const snapshot = async (s) => {
  busy.value = s.id
  try {
    const r = await fetch(route('risk.core.eval-sessions.snapshot', s.id), { method:'POST', headers:{ 'X-CSRF-TOKEN':csrf(), 'Accept':'application/json' } })
    const d = await r.json()
    if (r.ok && d.success) { showFlash(d.message); router.reload({ only:['sessions'] }) }
    else showFlash(d.message || 'Erreur')
  } catch { showFlash('Erreur réseau') }
  finally { busy.value=null }
}
</script>

<style scoped>
.page{display:flex;flex-direction:column;min-height:calc(100vh - 60px);background:#f0f4f8;font-family:'Inter',system-ui,sans-serif;font-size:13px;}
.page-hdr{display:flex;align-items:center;justify-content:space-between;padding:10px 22px;background:#0f172a;flex-wrap:wrap;gap:10px;}
.page-hdr-left{display:flex;align-items:center;gap:12px;}
.hdr-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;color:#fff;background:linear-gradient(135deg,#4f46e5,#7c3aed);}
.page-hdr-left h1{font-size:16px;font-weight:800;color:#f1f5f9;margin:0;}
.page-hdr-left p{font-size:11px;color:#64748b;margin:0;}
.page-hdr-right{display:flex;align-items:center;gap:8px;}
.btn-primary{display:flex;align-items:center;gap:6px;padding:8px 16px;background:#4f46e5;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;}
.btn-primary:hover:not(:disabled){background:#4338ca;}
.btn-primary:disabled{opacity:.5;cursor:not-allowed;}
.btn-ghost{display:flex;align-items:center;gap:5px;padding:7px 14px;background:rgba(255,255,255,.07);color:#c8d6e5;border:1px solid rgba(255,255,255,.12);border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;}
.btn-ghost:hover{background:rgba(255,255,255,.14);}

.live-bar{display:flex;align-items:center;gap:12px;padding:9px 22px;background:#fff;border-bottom:1px solid #e2e8f0;font-size:12px;color:#475569;flex-wrap:wrap;}
.live-dot{width:9px;height:9px;border-radius:50%;background:#22c55e;box-shadow:0 0 0 3px #22c55e33;}
.live-stat{font-weight:600;color:#64748b;}
.live-stat--i{color:#ef4444;}.live-stat--r{color:#f59e0b;}.live-stat--c{color:#10b981;}
.live-link{margin-left:auto;display:flex;align-items:center;gap:5px;font-size:11px;font-weight:700;color:#4f46e5;text-decoration:none;}

.wrap{padding:18px 22px;}
.empty{background:#fff;border:1px dashed #cbd5e1;border-radius:14px;padding:48px;text-align:center;color:#94a3b8;display:flex;flex-direction:column;align-items:center;gap:12px;}
.empty i{font-size:40px;}
.empty p{max-width:420px;font-size:13px;}

.cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:14px;}
.scard{background:#fff;border:1px solid #e9eef5;border-radius:14px;padding:16px;display:flex;flex-direction:column;gap:8px;}
.scard--active{border-color:#a5b4fc;box-shadow:0 0 0 2px #c7d2fe66;}
.scard-top{display:flex;align-items:flex-start;justify-content:space-between;gap:6px;}
.scode{font-family:monospace;font-size:10px;font-weight:700;background:#eef2f7;color:#475569;padding:2px 7px;border-radius:5px;}
.badge-active{margin-left:6px;font-size:9px;font-weight:800;color:#15803d;background:#dcfce7;padding:2px 8px;border-radius:8px;}
.badge-status{margin-left:6px;font-size:9px;font-weight:700;padding:2px 8px;border-radius:8px;}
.bs-draft{background:#f1f5f9;color:#64748b;}.bs-active{background:#e0e7ff;color:#4338ca;}.bs-closed{background:#fef3c7;color:#b45309;}.bs-archived{background:#f1f5f9;color:#94a3b8;}
.ic-btn{width:28px;height:28px;border:1px solid #e2e8f0;background:#fff;border-radius:7px;cursor:pointer;color:#94a3b8;}
.ic-danger:hover{background:#fee2e2;color:#dc2626;border-color:#fca5a5;}
.sname{font-size:15px;font-weight:800;color:#0f172a;}
.smeta{display:flex;gap:12px;flex-wrap:wrap;font-size:11px;color:#64748b;}
.smeta span{display:flex;align-items:center;gap:4px;}
.s-parent{color:#7c3aed;font-weight:600;}
.ssnap{font-size:11px;color:#3b82f6;display:flex;align-items:center;gap:5px;}
.scard-actions{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;padding-top:10px;border-top:1px solid #f1f5f9;}
.act{display:flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:5px 10px;border:1px solid #e2e8f0;border-radius:7px;background:#fff;color:#475569;cursor:pointer;text-decoration:none;}
.act:hover{background:#f8fafc;border-color:#cbd5e1;}
.act:disabled{opacity:.5;cursor:not-allowed;}
.act-blue{color:#1d4ed8;border-color:#bfdbfe;background:#eff6ff;}
.act-indigo{color:#4338ca;border-color:#c7d2fe;background:#eef2ff;}
.act-amber{color:#b45309;border-color:#fde68a;background:#fffbeb;}
.act-teal{color:#0f766e;border-color:#99f6e4;background:#f0fdfa;}

.modal-ov{position:fixed;inset:0;background:rgba(2,6,23,.7);backdrop-filter:blur(5px);display:flex;align-items:center;justify-content:center;z-index:2000;padding:20px;}
.modal-box{background:#fff;border-radius:16px;width:min(480px,100%);overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,.3);}
.modal-hdr{display:flex;align-items:center;gap:10px;padding:14px 18px;background:#0f172a;color:#f1f5f9;font-size:14px;font-weight:700;}
.modal-x{margin-left:auto;width:28px;height:28px;border:none;background:rgba(255,255,255,.08);color:#94a3b8;border-radius:7px;cursor:pointer;}
.modal-body{padding:18px;display:flex;flex-direction:column;gap:12px;}
.modal-note{font-size:11.5px;color:#475569;background:#eef2ff;border:1px solid #c7d2fe;border-radius:9px;padding:9px 12px;display:flex;gap:7px;}
.fld{display:flex;flex-direction:column;gap:4px;font-size:11px;font-weight:700;color:#475569;}
.fld input,.fld textarea{padding:8px 11px;border:1px solid #e2e8f0;border-radius:8px;font-size:12.5px;font-weight:400;color:#0f172a;}
.fld input:focus,.fld textarea:focus{outline:none;border-color:#a5b4fc;box-shadow:0 0 0 3px #c7d2fe55;}
.modal-foot{display:flex;justify-content:flex-end;gap:8px;padding:12px 18px;border-top:1px solid #eef2f7;background:#f8fafc;}
.btn-cancel{padding:8px 16px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;color:#475569;font-size:12px;font-weight:600;cursor:pointer;}

.flash{position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;align-items:center;gap:8px;padding:11px 18px;border-radius:12px;background:#dcfce7;color:#15803d;border:1px solid #86efac;font-size:12px;font-weight:700;box-shadow:0 4px 16px rgba(0,0,0,.12);}
.fl-enter-active,.fl-leave-active{transition:all .2s;}.fl-enter-from,.fl-leave-to{opacity:0;transform:translateX(20px);}
.mfade-enter-active{transition:all .18s;}.mfade-leave-active{transition:all .14s;}.mfade-enter-from,.mfade-leave-to{opacity:0;transform:scale(.97);}
@keyframes ti-spin{to{transform:rotate(360deg);}}.ti-spin{animation:ti-spin .7s linear infinite;display:inline-block;}
</style>
