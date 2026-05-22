<template>
  <VerticalLayoutAudit>
    <div class="oz-shell">
      <header class="oz-header">
        <div class="oz-header__inner">
          <a :href="props.backUrl" class="oz-back"><i class="ti ti-arrow-left"></i></a>
          <div class="oz-header__meta">
            <div class="oz-header__badges">
              <span class="oz-code">{{ form.code||'XII-AUTO' }}</span>
              <span class="oz-status" :class="'ozs--'+form.statut"><i :class="vstIcon(form.statut)"></i>{{ vstLbl(form.statut) }}</span>
              <span class="oz-pill"><i class="ti ti-shield-half"></i>{{ props.auditorRole }}</span>
              <span class="oz-pill"><i class="ti ti-user-check"></i>{{ props.auditeurNom }}</span>
            </div>
            <h1 class="oz-header__title" style="--hc:#0369a1"><span class="oz-num">XII</span>Circularisation (Confirmation Externe)</h1>
            <div class="oz-header__info">
              <span v-if="missionLibelle"><i class="ti ti-building"></i>{{ missionLibelle }}</span>
              <span v-if="form.procedure_code" class="oz-proc-badge"><i class="ti ti-list-check"></i>{{ form.procedure_code }}</span>
            </div>
          </div>
          <div class="oz-header__actions">
            <template v-if="!isLocked">
              <button class="oz-btn oz-btn--ghost" :disabled="processing" @click="annuler"><i class="ti ti-x"></i></button>
              <button class="oz-btn oz-btn--save" :disabled="processing" @click="submit()">
                <span v-if="processing" class="oz-spin"></span><i v-else class="ti ti-device-floppy"></i>{{ form.id?'Mettre à jour':'Enregistrer' }}
              </button>
              <button v-if="form.id&&form.statut==='draft'" class="oz-btn oz-btn--submit" @click="soumettre"><i class="ti ti-send"></i> Soumettre</button>
            </template>
            <template v-if="canManage&&form.statut==='in_review'">
              <button class="oz-btn oz-btn--ok" @click="valider('validated')"><i class="ti ti-circle-check"></i> Valider</button>
              <button class="oz-btn oz-btn--ko" @click="promptReject"><i class="ti ti-circle-x"></i> Rejeter</button>
            </template>
          </div>
        </div>
        <div v-if="form.statut==='validated'" class="oz-banner oz-banner--ok"><i class="ti ti-lock"></i> Fiche <strong>validée</strong></div>
        <div v-else-if="form.statut==='in_review'" class="oz-banner oz-banner--review"><i class="ti ti-clock"></i> Soumise pour validation</div>
        <div v-else-if="form.statut==='draft'&&form.validation_note" class="oz-banner oz-banner--ko"><i class="ti ti-circle-x"></i> Rejetée — <em>{{ form.validation_note }}</em></div>
      </header>
      <div class="oz-body">

        <div class="oz-card">
          <h3 class="oz-card__title"><i class="ti ti-info-circle"></i> Informations générales</h3>
          <div class="oz-g4">
            <div class="oz-f"><label class="oz-lbl">Date d'envoi</label><input type="date" class="oz-inp" v-model="form.date_envoi" :disabled="isLocked"/></div>
            <div class="oz-f"><label class="oz-lbl">Date limite réponse</label><input type="date" class="oz-inp" v-model="form.date_limite" :disabled="isLocked"/></div>
            <div class="oz-f oz-full"><label class="oz-lbl">Adresse de réception des réponses</label><input type="text" class="oz-inp" v-model="form.adresse_reception" :disabled="isLocked" placeholder="Service Audit, BP 123 ou email@org.fr"/></div>
          </div>
        </div>
        <div class="oz-card" style="background:#f0f9ff;border-color:#bae6fd">
          <h3 class="oz-card__title"><i class="ti ti-certificate"></i> Fiabilité des Sources</h3>
          <div class="oz-g4" style="margin-top:.4rem">
            <div v-for="f in fiabilites" :key="f.niv" style="display:flex;align-items:flex-start;gap:.4rem;padding:.4rem .55rem;background:#fff;border-radius:6px;border:1px solid #e2e8f0">
              <span style="font-family:monospace;font-weight:700;font-size:.8rem;flex-shrink:0" :style="'color:'+f.c">{{f.niv}}</span>
              <div><div style="font-size:.62rem;font-weight:600;color:#374151">{{f.lp}} → {{f.lc}}</div><div style="font-size:.6rem;color:#64748b;margin-top:.1rem">{{f.desc}}</div></div>
            </div>
          </div>
        </div>
        <div class="oz-card">
          <div class="oz-card__hd">
            <h3 class="oz-card__title"><i class="ti ti-list-check"></i> Suivi des Demandes de Confirmation</h3>
            <span class="oz-badge-count">{{ demandes.length }}</span>
            <button v-if="!isLocked" class="oz-add" @click="demandes.push({nom_tiers:'',element_confirmer:'',montant_periode:'',date_envoi_demande:'',date_reponse:'',montant_envoye:null,montant_confirme:null,element_confirme:'',statut_reponse:'en_attente',niveau_fiabilite:3})"><i class="ti ti-plus"></i> Demande</button>
          </div>
          <div class="oz-table-wrap">
            <table class="oz-tbl">
              <thead><tr><th class="tc" style="width:28px">N°</th><th style="min-width:150px">Tiers sollicité</th><th style="min-width:140px">Élément à confirmer</th><th style="width:110px">Montant / Période</th><th style="width:90px">Date envoi</th><th style="width:90px">Date réponse</th><th style="width:110px">Montant envoyé</th><th style="width:110px">Montant confirmé</th><th class="tc" style="width:90px">Écart</th><th style="width:110px">Statut</th><th v-if="!isLocked" style="width:28px"></th></tr></thead>
              <tbody>
                <tr v-if="!demandes.length"><td colspan="11" class="oz-ec">Aucune demande</td></tr>
                <tr v-for="(d,di) in demandes" :key="di" :style="d.statut_reponse==='ecart'?'background:#fffbeb':d.statut_reponse==='sans_reponse'?'background:#fef2f2':d.statut_reponse==='ok'?'background:#f0fdf4':''">
                  <td class="tc oz-n">{{di+1}}</td>
                  <td><textarea class="oz-ta-sm" v-model="d.nom_tiers" rows="2" :disabled="isLocked" placeholder="Nom / Raison sociale…"></textarea></td>
                  <td><textarea class="oz-ta-sm" v-model="d.element_confirmer" rows="2" :disabled="isLocked" placeholder="Solde, stock, contrat…"></textarea></td>
                  <td><input class="oz-inp-sm" type="text" v-model="d.montant_periode" :disabled="isLocked" placeholder="Ex. : 1 500 000 FCFA"/></td>
                  <td><input class="oz-inp-sm" type="date" v-model="d.date_envoi_demande" :disabled="isLocked"/></td>
                  <td><input class="oz-inp-sm" type="date" v-model="d.date_reponse" :disabled="isLocked"/></td>
                  <td><input class="oz-inp-sm" type="number" v-model.number="d.montant_envoye" :disabled="isLocked" placeholder="0" step="any"/></td>
                  <td><input class="oz-inp-sm" type="number" v-model.number="d.montant_confirme" :disabled="isLocked" placeholder="0" step="any"/></td>
                  <td class="tc" :style="calcEcart(d)!==null&&calcEcart(d)!==0?'color:#dc2626;font-weight:700':calcEcart(d)===0?'color:#15803d;font-weight:700':''">
                    {{ calcEcart(d)!==null ? fmtN(calcEcart(d)) : '—' }}
                  </td>
                  <td><select class="oz-sel-sm" v-model="d.statut_reponse" :disabled="isLocked" :style="d.statut_reponse==='ok'?'color:#15803d;font-weight:700':d.statut_reponse==='ecart'?'color:#d97706;font-weight:700':d.statut_reponse==='sans_reponse'?'color:#dc2626;font-weight:700':''"><option value="en_attente">⏳ En attente</option><option value="ok">✅ OK</option><option value="ecart">⚠️ Écart</option><option value="sans_reponse">❌ Sans réponse</option></select></td>
                  <td v-if="!isLocked" class="tc"><button class="oz-del" @click="demandes.splice(di,1)"><i class="ti ti-trash"></i></button></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="oz-card" style="background:linear-gradient(135deg,#f0f9ff,#fff)">
          <h3 class="oz-card__title"><i class="ti ti-chart-bar"></i> Synthèse</h3>
          <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:.6rem;margin-top:.4rem">
            <div style="display:flex;flex-direction:column;align-items:center;padding:.6rem;background:#fff;border-radius:8px;border:1px solid #e2e8f0"><span style="font-size:1.5rem;font-weight:800;color:#0f172a;line-height:1">{{synthese.total}}</span><small style="font-size:.62rem;color:#64748b;margin-top:.2rem;text-align:center">Demandes envoyées</small></div>
            <div style="display:flex;flex-direction:column;align-items:center;padding:.6rem;background:#fff;border-radius:8px;border:1px solid #e2e8f0"><span style="font-size:1.5rem;font-weight:800;color:#15803d;line-height:1">{{synthese.recues}}</span><small style="font-size:.62rem;color:#64748b;margin-top:.2rem;text-align:center">Réponses reçues</small></div>
            <div style="display:flex;flex-direction:column;align-items:center;padding:.6rem;background:#fff;border-radius:8px;border:1px solid #e2e8f0"><span style="font-size:1.5rem;font-weight:800;color:#d97706;line-height:1">{{synthese.sans}}</span><small style="font-size:.62rem;color:#64748b;margin-top:.2rem;text-align:center">Sans réponse</small></div>
            <div style="display:flex;flex-direction:column;align-items:center;padding:.6rem;background:#fff;border-radius:8px;border:1px solid #e2e8f0"><span style="font-size:1.5rem;font-weight:800;color:#dc2626;line-height:1">{{synthese.ecarts}}</span><small style="font-size:.62rem;color:#64748b;margin-top:.2rem;text-align:center">Écarts identifiés</small></div>
            <div style="display:flex;flex-direction:column;align-items:center;padding:.6rem;background:#fff;border-radius:8px;border:1px solid #e2e8f0"><span style="font-size:1.5rem;font-weight:800;color:#0369a1;line-height:1">{{synthese.taux}}%</span><small style="font-size:.62rem;color:#64748b;margin-top:.2rem;text-align:center">Taux de réponse</small></div>
          </div>
        </div>

        <div v-if="iaResult" class="oz-card oz-card--ia">
          <h3 class="oz-card__title"><i class="ti ti-sparkles"></i> Résultat IA — Score : <strong>{{ iaResult.score }}/10</strong></h3>
          <p class="oz-ia-synth">{{ iaResult.synthese }}</p>
          <div v-if="iaResult.recommandations?.length" class="oz-ia-recs">
            <div v-for="r in iaResult.recommandations" :key="r" class="oz-ia-rec"><i class="ti ti-arrow-right"></i>{{ r }}</div>
          </div>
        </div>
        <button v-if="form.id&&!isLocked" class="oz-btn-ia" :disabled="iaLoading" @click="genererIa">
          <span v-if="iaLoading" class="oz-spin"></span><i v-else class="ti ti-sparkles"></i>{{ iaLoading?'Analyse…':'✨ Générer analyse IA' }}
        </button>
        <Transition name="toast"><div v-if="toast.show" class="oz-toast" :class="'ozt--'+toast.type">{{ toast.msg }}</div></Transition>
      </div>
    </div>
  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import {reactive,ref,computed,onBeforeUnmount} from 'vue'
import {router} from '@inertiajs/vue3'
import VerticalLayoutAudit from '@/Layouts/VerticalLayoutAudit.vue'

import {computed as _c} from 'vue'
const props=withDefaults(defineProps<{record?:any;demandes?:any[];missions?:any[];auditorRole?:string;auditeurNom?:string;backUrl?:string;urlStore?:string;urlUpdate?:string|null;urlSoumettre?:string|null;urlValider?:string|null;urlIa?:string|null;missionContext?:any;}>(),{record:null,demandes:()=>[],missions:()=>[],auditorRole:'AJ',auditeurNom:'',backUrl:'/',urlStore:'',urlUpdate:null,urlSoumettre:null,urlValider:null,urlIa:null,missionContext:()=>({})})
const form=reactive<any>({id:null,code:'',statut:'draft',validation_note:'',date_envoi:'',date_limite:'',adresse_reception:'',auditeur_responsable:props.auditeurNom,...(props.record??{})})
const demandes=reactive<any[]>([...(props.demandes??[])])
const processing=ref(false)
const fiabilites=[{niv:'++++',c:'#15803d',lp:'Externe',lc:'Externe',desc:"Confirmation directe d'un fournisseur à l'auditeur"},{niv:'+++',c:'#0369a1',lp:'Interne',lc:'Externe',desc:"Document interne envoyé directement à l'auditeur"},{niv:'++',c:'#d97706',lp:'Externe',lc:'Interne',desc:"Document externe obtenu par l'audité"},{niv:'+',c:'#dc2626',lp:'Interne',lc:'Interne',desc:"Document produit et conservé par l'organisation"}]
function calcEcart(d:any):number|null{if(d.montant_envoye==null||d.montant_confirme==null)return null;return Number(d.montant_envoye)-Number(d.montant_confirme)}
function fmtN(n:any):string{if(n===null||n===undefined)return'—';return Number(n).toLocaleString('fr-FR',{minimumFractionDigits:0,maximumFractionDigits:2})}
const synthese=_c(()=>{const t=demandes.length;const r=demandes.filter(d=>['ok','ecart'].includes(d.statut_reponse)).length;const s=demandes.filter(d=>d.statut_reponse==='sans_reponse').length;const e=demandes.filter(d=>d.statut_reponse==='ecart').length;return{total:t,recues:r,sans:s,ecarts:e,taux:t>0?Math.round(r/t*100):0}})
function buildPayload(){return{mission_id:props.missionContext?.mission_id,assignment_id:props.missionContext?.assignment_id,procedure_code:props.missionContext?.procedure_code,test_ref:props.missionContext?.test_ref,date_envoi:form.date_envoi,date_limite:form.date_limite,adresse_reception:form.adresse_reception,auditeur_responsable:form.auditeur_responsable,demandes}}

const canManage=computed(()=>['DM','CM'].includes(props.auditorRole??''))
const isLocked=computed(()=>form.statut==='validated'||(form.statut==='in_review'&&!canManage.value))
const missionLibelle=computed(()=>props.missionContext?.mission_libelle??'')
const iaResult=ref<any>(props.record?.ia_result?(typeof props.record.ia_result==='string'?JSON.parse(props.record.ia_result):props.record.ia_result):null)
const iaLoading=ref(false)
const toast=reactive({show:false,type:'success',msg:''})
let _tt:any
function vstLbl(s:string):string{return({draft:'Brouillon',in_review:'En attente',validated:'Validé ✓',rejected:'Rejeté'} as any)[s]??s}
function vstIcon(s:string):string{return({draft:'ti ti-edit',in_review:'ti ti-clock',validated:'ti ti-lock',rejected:'ti ti-circle-x'} as any)[s]??'ti ti-file'}
function csrf():string{return(document.querySelector('meta[name=csrf-token]') as any)?.content??''}
function showToast(t:string,m:string,d=4000){if(_tt)clearTimeout(_tt);toast.show=true;toast.type=t;toast.msg=m;_tt=setTimeout(()=>toast.show=false,d)}
function annuler(){if(props.backUrl)router.visit(props.backUrl)}
async function submit(silent=false){
  processing.value=!silent
  try{
    const url=form.id?(props.urlUpdate??''):(props.urlStore??'')
    const method=form.id?'PUT':'POST'
    if(!url){if(!silent)showToast('error','URL manquante');return}
    const res=await fetch(url,{method,headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify(buildPayload())})
    const d=await res.json()
    if(d.success||res.ok){if(!silent)showToast('success',form.id?'Mis à jour.':'Créé.');if(d.record?.id){form.id=d.record.id;if(d.record.code)form.code=d.record.code;if(d.record.statut)form.statut=d.record.statut}}
    else{if(!silent)showToast('error',d.message??d.error??'Erreur')}
  }catch{if(!silent)showToast('error','Erreur réseau')}
  finally{processing.value=false}
}
async function soumettre(){
  processing.value=true
  try{const res=await fetch(props.urlSoumettre??'',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:'{}'});const d=await res.json();if(d.success){form.statut='in_review';showToast('success','Soumise pour validation.')}else showToast('error',d.error??'Erreur')}
  catch{showToast('error','Erreur réseau')}
  processing.value=false
}
async function valider(action:string,note?:string){
  processing.value=true
  try{const res=await fetch(props.urlValider??'',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({decision:action,commentaire:note})});const d=await res.json();if(d.success){form.statut=action;showToast('success',action==='validated'?'Validée ✓':'Rejetée.')}else showToast('error',d.error??'Erreur')}
  catch{showToast('error','Erreur réseau')}
  processing.value=false
}
function promptReject(){const n=prompt('Motif du rejet :');if(n?.trim())valider('rejected',n.trim())}
async function genererIa(){
  if(!props.urlIa||!form.id)return
  iaLoading.value=true
  try{const res=await fetch(props.urlIa,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:'{}'});const d=await res.json();if(d.success){iaResult.value=d.ia_result;showToast('success','✨ IA générée.')}else showToast('error',d.error??'Erreur IA')}
  catch{showToast('error','Erreur réseau')}
  iaLoading.value=false
}
onBeforeUnmount(()=>{if(_tt)clearTimeout(_tt)})
</script>

<style scoped>
.oz-shell{display:flex;flex-direction:column;min-height:100vh;background:#f1f5f9;font-family:'DM Sans',system-ui,sans-serif;}.oz-header{background:#fff;border-bottom:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(15,23,42,.06);flex-shrink:0;}.oz-header__inner{display:flex;align-items:center;gap:.6rem;padding:.5rem .9rem;}.oz-back{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:6px;border:1px solid #e2e8f0;color:#475569;text-decoration:none;flex-shrink:0;}.oz-back:hover{background:#f8fafc;}.oz-header__meta{flex:1;min-width:0;}.oz-header__badges{display:flex;align-items:center;gap:.3rem;flex-wrap:wrap;margin-bottom:.2rem;}.oz-code{background:#0f172a;color:#e2e8f0;padding:1px 7px;border-radius:4px;font-size:.63rem;font-family:monospace;font-weight:700;}.oz-status{display:inline-flex;align-items:center;gap:.2rem;padding:1px 7px;border-radius:12px;font-size:.63rem;font-weight:600;}.ozs--draft{background:#f1f5f9;color:#64748b;}.ozs--in_review{background:#dbeafe;color:#1d4ed8;}.ozs--validated{background:#dcfce7;color:#15803d;}.ozs--rejected{background:#fee2e2;color:#dc2626;}.oz-pill{display:inline-flex;align-items:center;gap:.2rem;background:#f8fafc;color:#64748b;padding:2px 7px;border-radius:20px;font-size:.63rem;}.oz-proc-badge{background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:10px;padding:1px 7px;font-size:.62rem;display:inline-flex;align-items:center;gap:.2rem;}.oz-header__title{font-size:.82rem;font-weight:700;color:#0f172a;margin:0;display:flex;align-items:center;gap:.4rem;}.oz-num{display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;background:var(--hc,#1e40af);color:#fff;border-radius:5px;font-size:.65rem;font-weight:700;flex-shrink:0;padding:0 3px;}.oz-header__info{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.15rem;}.oz-header__info span{display:inline-flex;align-items:center;gap:.2rem;font-size:.65rem;color:#64748b;}.oz-header__actions{display:flex;align-items:center;gap:.25rem;flex-shrink:0;}.oz-btn{display:inline-flex;align-items:center;gap:.25rem;padding:5px 11px;border:none;border-radius:6px;font-size:.72rem;font-weight:600;cursor:pointer;font-family:inherit;transition:all .12s;}.oz-btn--ghost{background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;}.oz-btn--ghost:hover{background:#e2e8f0;}.oz-btn--save{background:#1e40af;color:#fff;}.oz-btn--save:hover{background:#1d4ed8;}.oz-btn--submit{background:#7c3aed;color:#fff;}.oz-btn--submit:hover{background:#6d28d9;}.oz-btn--ok{background:#15803d;color:#fff;}.oz-btn--ok:hover{background:#166534;}.oz-btn--ko{background:#dc2626;color:#fff;}.oz-btn--ko:hover{background:#b91c1c;}.oz-btn:disabled{opacity:.55;cursor:default;}.oz-banner{display:flex;align-items:center;gap:.4rem;padding:.28rem .9rem;font-size:.7rem;}.oz-banner--ok{background:#d1fae5;color:#065f46;border-top:1px solid #a7f3d0;}.oz-banner--review{background:#dbeafe;color:#1d4ed8;border-top:1px solid #bfdbfe;}.oz-banner--ko{background:#fee2e2;color:#dc2626;border-top:1px solid #fecaca;}.oz-body{flex:1;padding:.75rem;display:flex;flex-direction:column;gap:.75rem;overflow-y:auto;}.oz-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:1rem;}.oz-card__hd{display:flex;align-items:center;gap:.5rem;margin-bottom:.7rem;}.oz-card__title{font-size:.77rem;font-weight:700;color:#0f172a;margin:0 0 .6rem;display:flex;align-items:center;gap:.3rem;}.oz-g2{display:grid;grid-template-columns:1fr 1fr;gap:.6rem;}.oz-g3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:.6rem;}.oz-g4{display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:.6rem;}.oz-f{display:flex;flex-direction:column;gap:.2rem;}.oz-f--full,.oz-full{grid-column:1/-1;}.oz-lbl{font-size:.64rem;font-weight:600;color:#64748b;}.oz-req{color:#dc2626;}.oz-inp{width:100%;border:1px solid #e2e8f0;border-radius:5px;padding:5px 8px;font-size:.75rem;outline:none;font-family:inherit;color:#111827;background:#fff;box-sizing:border-box;}.oz-inp:focus{border-color:#93c5fd;}.oz-inp:disabled{background:#f8fafc;color:#64748b;}.oz-ta{width:100%;border:1px solid #e2e8f0;border-radius:5px;padding:5px 8px;font-size:.73rem;resize:vertical;font-family:inherit;color:#111827;box-sizing:border-box;}.oz-ta:disabled{background:#f8fafc;}.oz-sel{width:100%;border:1px solid #e2e8f0;border-radius:5px;padding:5px 8px;font-size:.73rem;font-family:inherit;background:#fff;cursor:pointer;}.oz-sel:disabled{background:#f8fafc;cursor:default;}.oz-inp-sm{width:100%;border:1px solid #e5e7eb;border-radius:4px;padding:3px 5px;font-size:.67rem;font-family:inherit;box-sizing:border-box;}.oz-ta-sm{width:100%;border:1px solid #e5e7eb;border-radius:4px;padding:3px 5px;font-size:.67rem;resize:vertical;font-family:inherit;}.oz-sel-sm{width:100%;border:1px solid #e5e7eb;border-radius:4px;padding:3px 5px;font-size:.67rem;font-family:inherit;background:#fff;cursor:pointer;}.oz-fv{font-size:.75rem;color:#111827;}.oz-ro-sm{font-size:.67rem;color:#374151;margin:0;}.oz-muted{color:#9ca3af;font-size:.65rem;}.oz-n{color:#94a3b8;font-size:.62rem;}.tc{text-align:center;}.oz-badge-count{background:#f1f5f9;color:#475569;border-radius:10px;padding:1px 7px;font-size:.63rem;font-weight:600;}.oz-add{display:inline-flex;align-items:center;gap:.2rem;padding:3px 9px;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;border-radius:5px;font-size:.68rem;font-weight:600;cursor:pointer;margin-left:auto;}.oz-add:hover{background:#dbeafe;}.oz-del{background:#fee2e2;border:1px solid #fecaca;color:#dc2626;border-radius:4px;cursor:pointer;padding:2px 4px;font-size:.62rem;line-height:1;display:flex;align-items:center;}.oz-del:hover{background:#fecaca;}.oz-table-wrap{overflow-x:auto;border:1px solid #e2e8f0;border-radius:6px;}.oz-tbl{width:100%;border-collapse:collapse;font-size:.68rem;}.oz-tbl thead th{padding:.3rem .4rem;background:#0f172a;color:rgba(255,255,255,.85);font-weight:700;border-bottom:1px solid #1e293b;border-right:1px solid #334155;white-space:nowrap;font-size:.6rem;text-transform:uppercase;}.oz-tbl tbody td{padding:.25rem .35rem;border-bottom:1px solid #f3f4f6;border-right:1px solid #f3f4f6;vertical-align:middle;}.oz-tbl tbody tr:last-child td{border-bottom:none;}.oz-ec{text-align:center;color:#94a3b8;padding:.8rem;font-style:italic;font-size:.68rem;}.oz-card--ia{background:linear-gradient(135deg,#faf5ff,#fff);border-color:#ddd6fe;}.oz-ia-synth{font-size:.75rem;color:#374151;line-height:1.5;margin:0 0 .5rem;}.oz-ia-recs{display:flex;flex-direction:column;gap:.2rem;}.oz-ia-rec{display:flex;align-items:flex-start;gap:.3rem;font-size:.7rem;color:#475569;}.oz-btn-ia{display:inline-flex;align-items:center;gap:.3rem;padding:6px 14px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:6px;font-size:.72rem;font-weight:600;cursor:pointer;font-family:inherit;align-self:flex-start;}.oz-btn-ia:disabled{opacity:.55;cursor:default;}.oz-toast{position:fixed;bottom:1.2rem;right:1.2rem;padding:.6rem 1rem;border-radius:8px;font-size:.75rem;font-weight:600;z-index:9999;box-shadow:0 4px 16px rgba(0,0,0,.12);}.ozt--success{background:#dcfce7;color:#15803d;border:1px solid #a7f3d0;}.ozt--error{background:#fee2e2;color:#dc2626;border:1px solid #fecaca;}.ozt--info{background:#dbeafe;color:#1d4ed8;border:1px solid #bfdbfe;}.oz-spin{width:12px;height:12px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:_sp .7s linear infinite;display:inline-block;}@keyframes _sp{to{transform:rotate(360deg)}}.toast-enter-active,.toast-leave-active{transition:all .3s;}.toast-enter-from,.toast-leave-to{opacity:0;transform:translateY(8px);}

</style>
