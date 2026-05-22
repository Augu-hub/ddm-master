<template>
  <VerticalLayoutAudit>
    <div class="oz-shell">
      <header class="oz-header">
        <div class="oz-header__inner">
          <a :href="props.backUrl" class="oz-back"><i class="ti ti-arrow-left"></i></a>
          <div class="oz-header__meta">
            <div class="oz-header__badges">
              <span class="oz-code">{{ form.code||'IX-AUTO' }}</span>
              <span class="oz-status" :class="'ozs--'+form.statut"><i :class="vstIcon(form.statut)"></i>{{ vstLbl(form.statut) }}</span>
              <span class="oz-pill"><i class="ti ti-shield-half"></i>{{ props.auditorRole }}</span>
              <span class="oz-pill"><i class="ti ti-user-check"></i>{{ props.auditeurNom }}</span>
            </div>
            <h1 class="oz-header__title" style="--hc:#0f766e"><span class="oz-num">IX</span>Questionnaire de Contrôle Interne (QCI)</h1>
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
          <h3 class="oz-card__title"><i class="ti ti-info-circle"></i> En-tête</h3>
          <div class="oz-g3">
            <div class="oz-f oz-full"><label class="oz-lbl">Intitulé *</label><input type="text" class="oz-inp" v-model="form.intitule" :disabled="isLocked" placeholder="Ex. : QCI Processus Achats"/></div>
            <div class="oz-f oz-full"><label class="oz-lbl">Périmètre</label><input type="text" class="oz-inp" v-model="form.perimetre" :disabled="isLocked" placeholder="Direction des Achats — exercice 2024"/></div>
            <div class="oz-f"><label class="oz-lbl">Processus</label><input type="text" class="oz-inp" v-model="form.processus" :disabled="isLocked" placeholder="Achats / Fournisseurs"/></div>
            <div class="oz-f"><label class="oz-lbl">Cadre de référence</label>
              <select class="oz-sel" v-model="form.cadre_reference" :disabled="isLocked">
                <option value="COSO">COSO</option><option value="AMF">AMF</option><option value="COBIT">COBIT</option><option value="Autre">Autre</option>
              </select>
            </div>
            <div class="oz-f"><label class="oz-lbl">Date</label><input type="date" class="oz-inp" v-model="form.date_qci" :disabled="isLocked"/></div>
          </div>
        </div>
        <div v-for="comp in composantes" :key="comp.code" class="oz-card">
          <div class="oz-card__hd">
            <h3 class="oz-card__title" :style="'color:'+comp.color"><i :class="comp.icon"></i>{{comp.label}}</h3>
            <span class="oz-badge-count">{{ scoreComp(comp.code) }}</span>
          </div>
          <div class="oz-table-wrap">
            <table class="oz-tbl">
              <thead><tr><th style="width:50px">N°</th><th style="min-width:280px">Question</th><th style="width:85px">Réponse</th><th style="min-width:150px">Constat / Preuve</th><th style="min-width:120px">Risque si Non</th><th style="width:80px">Niveau</th></tr></thead>
              <tbody>
                <tr v-for="q in getQuestions(comp.code)" :key="q.num">
                  <td style="font-size:.65rem;font-weight:600;color:#64748b">{{q.num}}</td>
                  <td style="font-size:.7rem;color:#374151;line-height:1.4">{{q.libelle}}</td>
                  <td>
                    <select class="oz-sel-sm" v-model="q.reponse" :disabled="isLocked" :style="q.reponse==='oui'?'color:#15803d;font-weight:700':q.reponse==='non'?'color:#dc2626;font-weight:700':q.reponse==='partiel'?'color:#d97706;font-weight:700':''">
                      <option value="">—</option><option value="oui">✅ Oui</option><option value="non">❌ Non</option><option value="partiel">⚠️ Partiel</option><option value="na">N/A</option>
                    </select>
                  </td>
                  <td><textarea class="oz-ta-sm" v-model="q.commentaire" rows="2" :disabled="isLocked" placeholder="Constat…"></textarea></td>
                  <td><textarea class="oz-ta-sm" v-model="q.risque_si_non" rows="2" :disabled="isLocked"></textarea></td>
                  <td><select class="oz-sel-sm" v-model="q.niveau_risque" :disabled="isLocked"><option value="">—</option><option value="Fort">Fort</option><option value="Moyen">Moyen</option><option value="Faible">Faible</option></select></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="oz-card">
          <h3 class="oz-card__title"><i class="ti ti-file-text"></i> Conclusion</h3>
          <textarea class="oz-ta" v-model="form.conclusion" :disabled="isLocked" rows="3" placeholder="Conclusion générale de l'évaluation…"></textarea>
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

const props=withDefaults(defineProps<{record?:any;sections?:any[];questions?:any[];missions?:any[];auditorRole?:string;auditeurNom?:string;backUrl?:string;urlStore?:string;urlUpdate?:string|null;urlSoumettre?:string|null;urlValider?:string|null;urlIa?:string|null;missionContext?:any;}>(),{record:null,sections:()=>[],questions:()=>[],missions:()=>[],auditorRole:'AJ',auditeurNom:'',backUrl:'/',urlStore:'',urlUpdate:null,urlSoumettre:null,urlValider:null,urlIa:null,missionContext:()=>({})})
const form=reactive<any>({id:null,code:'',statut:'draft',validation_note:'',intitule:'',perimetre:'',processus:'',cadre_reference:'COSO',date_qci:'',conclusion:'',...(props.record??{})})
const composantes=[{code:'env_ctrl',label:'1. Environnement de contrôle',icon:'ti ti-building',color:'#1e40af'},{code:'eval_risq',label:'2. Évaluation des risques',icon:'ti ti-alert-triangle',color:'#dc2626'},{code:'act_ctrl',label:'3. Activités de contrôle',icon:'ti ti-checklist',color:'#065f46'},{code:'info_com',label:'4. Information & Communication',icon:'ti ti-message-circle',color:'#6d28d9'},{code:'pilotage',label:'5. Pilotage (Monitoring)',icon:'ti ti-chart-line',color:'#0891b2'}]
const defaultQs=[{num:'1.1',composante:'env_ctrl',libelle:"La direction a-t-elle exprimé clairement ses exigences en matière d'intégrité et d'éthique ?"},{num:'1.2',composante:'env_ctrl',libelle:"Le code de conduite a-t-il été formalisé et diffusé ?"},{num:'1.3',composante:'env_ctrl',libelle:"Les compétences du personnel sont-elles à la mesure de leurs responsabilités ?"},{num:'1.4',composante:'env_ctrl',libelle:"Le style de management est-il adapté (planification, organisation, supervision) ?"},{num:'1.5',composante:'env_ctrl',libelle:"La structure organisationnelle est-elle clairement définie (organigramme, fiches de poste) ?"},{num:'2.1',composante:'eval_risq',libelle:"Les objectifs du processus sont-ils définis et formalisés ?"},{num:'2.2',composante:'eval_risq',libelle:"Les risques internes et externes ont-ils été identifiés et évalués ?"},{num:'2.3',composante:'eval_risq',libelle:"Un mécanisme de mise à jour de la cartographie des risques existe-t-il ?"},{num:'2.4',composante:'eval_risq',libelle:"Les politiques et procédures sont-elles mises à jour suite à l'évolution des risques ?"},{num:'3.1',composante:'act_ctrl',libelle:"Existe-t-il des procédures écrites qui définissent les modalités opératoires ?"},{num:'3.2',composante:'act_ctrl',libelle:"Les procédures sont-elles connues, à jour et appliquées ?"},{num:'3.3',composante:'act_ctrl',libelle:"L'attribution des autorisations est-elle satisfaisante ?"},{num:'3.4',composante:'act_ctrl',libelle:"Les tâches incompatibles sont-elles séparées (séparation des fonctions) ?"},{num:'3.5',composante:'act_ctrl',libelle:"Les opérations font-elles l'objet d'une supervision adéquate (coût/qualité/délai) ?"},{num:'3.6',composante:'act_ctrl',libelle:"Est-il impossible de réaliser des opérations non autorisées ?"},{num:'3.7',composante:'act_ctrl',libelle:"Les vérifications d'existence, d'exactitude et de délai sont-elles effectuées ?"},{num:'3.8',composante:'act_ctrl',libelle:"Les opérations réalisées sont-elles formalisées (traçabilité) ?"},{num:'3.9',composante:'act_ctrl',libelle:"Les pièces justificatives sont-elles conservées et protégées ?"},{num:'4.1',composante:'info_com',libelle:"Les circuits de circulation des informations sont-ils clairement identifiés ?"},{num:'4.2',composante:'info_com',libelle:"Le personnel dispose-t-il des informations nécessaires à son activité ?"},{num:'4.3',composante:'info_com',libelle:"Les informations produites sont-elles transmises aux parties prenantes concernées ?"},{num:'4.4',composante:'info_com',libelle:"Les systèmes d'information produisent-ils des données fiables et pertinentes ?"},{num:'5.1',composante:'pilotage',libelle:"Existe-t-il des procédures de suivi et d'évaluation du contrôle interne ?"},{num:'5.2',composante:'pilotage',libelle:"Les faiblesses identifiées font-elles l'objet d'une formalisation et d'un suivi ?"},{num:'5.3',composante:'pilotage',libelle:"Les corrections apportées au système de contrôle interne sont-elles suivies ?"},{num:'5.4',composante:'pilotage',libelle:"Des indicateurs de performance sont-ils définis et suivis régulièrement ?"}]
const savedQs=props.questions??[]
const questionsState=reactive<any[]>(defaultQs.map(dq=>{const found=savedQs.find((sq:any)=>sq.num===dq.num||sq.libelle===dq.libelle);return{...dq,reponse:found?.reponse??'',commentaire:found?.commentaire??'',risque_si_non:found?.risque_si_non??'',niveau_risque:found?.niveau_risque??''}}))
const processing=ref(false)
function getQuestions(comp:string){return questionsState.filter(q=>q.composante===comp)}
function scoreComp(comp:string){const qs=getQuestions(comp);return qs.filter(q=>q.reponse==='oui').length+'/'+qs.length}
function buildPayload(){const sections=composantes.map((c,i)=>({libelle:c.label,code:c.code,ordre:i+1,questions:getQuestions(c.code).map((q,qi)=>({num:q.num,libelle:q.libelle,reponse:q.reponse,commentaire:q.commentaire,risque_si_non:q.risque_si_non,niveau_risque:q.niveau_risque,ordre:qi+1}))}));return{mission_id:props.missionContext?.mission_id,assignment_id:props.missionContext?.assignment_id,procedure_code:props.missionContext?.procedure_code,test_ref:props.missionContext?.test_ref,intitule:form.intitule,perimetre:form.perimetre,processus:form.processus,cadre_reference:form.cadre_reference,date_qci:form.date_qci,conclusion:form.conclusion,sections}}

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
