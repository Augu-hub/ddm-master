<template>
  <VerticalLayoutAudit>
    <div class="oz-shell">
      <header class="oz-header">
        <div class="oz-header__inner">
          <a :href="props.backUrl" class="oz-back"><i class="ti ti-arrow-left"></i></a>
          <div class="oz-header__meta">
            <div class="oz-header__badges">
              <span class="oz-code">{{ form.code||'XV-AUTO' }}</span>
              <span class="oz-status" :class="'ozs--'+form.statut"><i :class="vstIcon(form.statut)"></i>{{ vstLbl(form.statut) }}</span>
              <span class="oz-pill"><i class="ti ti-shield-half"></i>{{ props.auditorRole }}</span>
              <span class="oz-pill"><i class="ti ti-user-check"></i>{{ props.auditeurNom }}</span>
            </div>
            <h1 class="oz-header__title" style="--hc:#0c4a6e"><span class="oz-num">XV</span>Échantillonnage Statistique</h1>
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
          <h3 class="oz-card__title"><i class="ti ti-database"></i> Étape 1 — Définition de la Population et du Test</h3>
          <div class="oz-g2">
            <div class="oz-f oz-full"><label class="oz-lbl">Population de référence <span class="oz-req">*</span></label><input type="text" class="oz-inp" v-model="form.population_reference" :disabled="isLocked" placeholder="Ex. : Toutes les factures fournisseurs émises entre le 01/01/N et 31/12/N…"/></div>
            <div class="oz-f oz-full"><label class="oz-lbl">Objet du test / Objectif d'audit</label><input type="text" class="oz-inp" v-model="form.objet_test" :disabled="isLocked" placeholder="Ex. : Vérifier l'existence des bons de commande associés…"/></div>
            <div class="oz-f"><label class="oz-lbl">Type de sondage</label>
              <select class="oz-sel" v-model="form.type_sondage" :disabled="isLocked">
                <option value="">— Choisir —</option><option value="attribut">Attribut (présence/absence d'une caractéristique)</option><option value="valeur">Valeur (estimation d'un montant ou d'une quantité)</option>
              </select>
            </div>
            <div class="oz-f"><label class="oz-lbl">Taille de la population (N)</label><input type="number" class="oz-inp" v-model.number="form.taille_population" :disabled="isLocked" placeholder="Ex. : 1 250" min="1"/></div>
            <div class="oz-f"><label class="oz-lbl">Auditeur</label><input type="text" class="oz-inp" v-model="form.auditeur" :disabled="isLocked" :placeholder="props.auditeurNom"/></div>
            <div class="oz-f"><label class="oz-lbl">Date</label><input type="date" class="oz-inp" v-model="form.date" :disabled="isLocked"/></div>
          </div>
        </div>
        <div class="oz-card" style="background:#f0f9ff;border-color:#bae6fd">
          <h3 class="oz-card__title"><i class="ti ti-calculator"></i> Étape 2 — Paramètres Statistiques</h3>
          <div class="oz-g3">
            <div class="oz-f">
              <label class="oz-lbl">Niveau de confiance (%)</label>
              <div style="display:flex;gap:.3rem;margin-top:.2rem">
                <button v-for="nc in [90,95,99]" :key="nc" @click="!isLocked&&setConf(nc)" :disabled="isLocked"
                  :style="form.niveau_confiance===nc?'background:#0c4a6e;color:#fff;border-color:#0c4a6e':'background:#fff;color:#475569'"
                  style="flex:1;padding:5px 8px;border:1px solid #e2e8f0;border-radius:5px;font-size:.72rem;font-weight:600;cursor:pointer;font-family:inherit">{{nc}}%</button>
              </div>
              <p style="font-size:.62rem;color:#64748b;margin:.2rem 0 0">Coefficient t : <strong>{{coeffT}}</strong></p>
            </div>
            <div class="oz-f"><label class="oz-lbl">Erreur maximale acceptable (ε)</label>
              <input type="number" class="oz-inp" v-model.number="form.erreur_max" :disabled="isLocked" placeholder="Ex. : 0.05 (5%) ou 10 000" step="any" @change="recalc"/>
              <p style="font-size:.62rem;color:#64748b;margin:.2rem 0 0">En décimal pour Attribut · En valeur absolue pour Valeur</p>
            </div>
            <div class="oz-f"><label class="oz-lbl">{{ form.type_sondage==='valeur'?'Écart-type exploratoire (σ)':'Taux de présence exploratoire (p)' }}</label>
              <input type="number" class="oz-inp" v-model.number="form.parametre" :disabled="isLocked" :placeholder="form.type_sondage==='valeur'?'σ calculé sur pré-sondage':'p entre 0 et 1 (ex. : 0.5)'" step="any" @change="recalc"/>
            </div>
          </div>
        </div>
        <div class="oz-card" style="background:#ecfdf5;border-color:#a7f3d0">
          <h3 class="oz-card__title"><i class="ti ti-math-function"></i> Étape 3 — Taille d'Échantillon</h3>
          <div class="oz-g4">
            <div style="text-align:center;padding:.75rem;background:#fff;border-radius:8px;border:1px solid #e2e8f0">
              <div style="font-size:1.5rem;font-weight:800;color:#0c4a6e;line-height:1">{{form.taille_calculee??'—'}}</div>
              <small style="font-size:.62rem;color:#64748b">Taille calculée (n)</small>
            </div>
            <div style="text-align:center;padding:.75rem;background:#fff;border-radius:8px;border:2px solid #0c4a6e">
              <div style="font-size:1.5rem;font-weight:800;color:#0c4a6e;line-height:1">{{form.taille_retenue??'—'}}</div>
              <small style="font-size:.62rem;color:#64748b">Taille retenue (+5% marge)</small>
            </div>
            <div style="text-align:center;padding:.75rem;background:#fff;border-radius:8px;border:1px solid #e2e8f0">
              <div style="font-size:.9rem;font-weight:700;color:#0c4a6e;line-height:1">{{coeffT}}</div>
              <small style="font-size:.62rem;color:#64748b">Coefficient t</small>
            </div>
            <div class="oz-f"><label class="oz-lbl">Taille retenue (modifier si besoin)</label><input type="number" class="oz-inp" v-model.number="form.taille_retenue" :disabled="isLocked" min="1"/></div>
          </div>
        </div>
        <div class="oz-card">
          <div class="oz-card__hd">
            <h3 class="oz-card__title"><i class="ti ti-list-numbers"></i> Étape 4 — Suivi de l'Échantillon</h3>
            <span class="oz-badge-count">{{ elements.length }}</span>
            <button v-if="!isLocked" class="oz-add" @click="elements.push({reference:'',valeur:null,attribut_present:'',anomalie_detectee:'non',nature_anomalie:''})"><i class="ti ti-plus"></i> Élément</button>
          </div>
          <div class="oz-table-wrap">
            <table class="oz-tbl">
              <thead><tr><th class="tc" style="width:28px">N°</th><th style="min-width:130px">Référence / Identifiant</th><th v-if="form.type_sondage==='valeur'" class="tc" style="width:110px">Valeur</th><th v-if="form.type_sondage!=='valeur'" class="tc" style="width:100px">Attribut présent</th><th class="tc" style="width:100px">Anomalie détectée</th><th style="min-width:150px">Nature de l'anomalie</th><th v-if="!isLocked" style="width:28px"></th></tr></thead>
              <tbody>
                <tr v-if="!elements.length"><td :colspan="isLocked?6:7" class="oz-ec">Aucun élément</td></tr>
                <tr v-for="(e,ei) in elements" :key="ei" :style="e.anomalie_detectee==='oui'?'background:#fef2f2':''">
                  <td class="tc oz-n">{{ei+1}}</td>
                  <td><input class="oz-inp-sm" type="text" v-model="e.reference" :disabled="isLocked" placeholder="Réf…"/></td>
                  <td v-if="form.type_sondage==='valeur'" class="tc"><input class="oz-inp-sm" style="text-align:right" type="number" v-model.number="e.valeur" :disabled="isLocked" placeholder="0"/></td>
                  <td v-if="form.type_sondage!=='valeur'" class="tc"><select class="oz-sel-sm" v-model="e.attribut_present" :disabled="isLocked" :style="e.attribut_present==='oui'?'color:#15803d;font-weight:700':e.attribut_present==='non'?'color:#dc2626;font-weight:700':''"><option value="">—</option><option value="oui">✅ Oui</option><option value="non">❌ Non</option></select></td>
                  <td class="tc"><select class="oz-sel-sm" v-model="e.anomalie_detectee" :disabled="isLocked" :style="e.anomalie_detectee==='oui'?'color:#dc2626;font-weight:700':e.anomalie_detectee==='non'?'color:#15803d':''"><option value="">—</option><option value="oui">❌ Oui</option><option value="non">✅ Non</option></select></td>
                  <td><textarea class="oz-ta-sm" v-model="e.nature_anomalie" rows="2" :disabled="isLocked" placeholder="Description de l'anomalie…"></textarea></td>
                  <td v-if="!isLocked" class="tc"><button class="oz-del" @click="elements.splice(ei,1)"><i class="ti ti-trash"></i></button></td>
                </tr>
              </tbody>
            </table>
            <div v-if="elements.length" style="padding:.4rem .6rem;background:#f8fafc;border-top:1px solid #e2e8f0;display:flex;gap:1.5rem;font-size:.68rem;color:#475569">
              <span><strong>{{elements.length}}</strong> éléments</span>
              <span v-if="form.type_sondage==='valeur'">Total : <strong>{{fmtN(elements.reduce((s:number,e:any)=>s+(Number(e.valeur)||0),0))}}</strong> · Moyenne : <strong>{{fmtN(elements.reduce((s:number,e:any)=>s+(Number(e.valeur)||0),0)/elements.length)}}</strong></span>
              <span v-else>Attributs présents : <strong>{{elements.filter((e:any)=>e.attribut_present==='oui').length}}</strong> ({{Math.round(elements.filter((e:any)=>e.attribut_present==='oui').length/elements.length*100)}}%)</span>
              <span>Anomalies : <strong style="color:#dc2626">{{elements.filter((e:any)=>e.anomalie_detectee==='oui').length}}</strong> ({{Math.round(elements.filter((e:any)=>e.anomalie_detectee==='oui').length/elements.length*100)}}%)</span>
            </div>
          </div>
        </div>
        <div class="oz-card">
          <h3 class="oz-card__title"><i class="ti ti-trending-up"></i> Étape 5 — Extrapolation et Conclusion</h3>
          <div class="oz-g2">
            <div class="oz-f oz-full"><label class="oz-lbl">Intervalle de confiance</label><input type="text" class="oz-inp" v-model="form.intervalle_confiance" :disabled="isLocked" placeholder="Ex. : Il y a 95% de chances que le taux réel soit entre X% et Y%"/></div>
            <div class="oz-f oz-full"><label class="oz-lbl">Conclusion</label><textarea class="oz-ta" v-model="form.conclusion" :disabled="isLocked" rows="3" placeholder="Il y a P% de chances que…"></textarea></div>
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
const props=withDefaults(defineProps<{record?:any;elements?:any[];missions?:any[];auditorRole?:string;auditeurNom?:string;backUrl?:string;urlStore?:string;urlUpdate?:string|null;urlSoumettre?:string|null;urlValider?:string|null;urlIa?:string|null;missionContext?:any;}>(),{record:null,elements:()=>[],missions:()=>[],auditorRole:'AJ',auditeurNom:'',backUrl:'/',urlStore:'',urlUpdate:null,urlSoumettre:null,urlValider:null,urlIa:null,missionContext:()=>({})})
const form=reactive<any>({id:null,code:'',statut:'draft',validation_note:'',auditeur:props.auditeurNom,objectif_audit:'',population_reference:'',objet_test:'',type_sondage:'',taille_population:null,niveau_confiance:95,erreur_max:0.05,parametre:null,coefficient_t:1.96,taille_calculee:null,taille_retenue:null,intervalle_confiance:'',conclusion:'',date:'',...(props.record??{})})
const elements=reactive<any[]>([...(props.elements??[])])
const processing=ref(false)
const coeffsT:Record<number,number>={90:1.645,95:1.96,99:2.576}
const coeffT=_c(()=>coeffsT[form.niveau_confiance]??1.96)
function setConf(nc:number){form.niveau_confiance=nc;recalc()}
function recalc(){
  const t=coeffT.value;const eps=Number(form.erreur_max)||0;let n:number|null=null
  if(form.type_sondage==='valeur'){const s=Number(form.parametre)||0;if(s>0&&eps>0)n=Math.ceil((t*t*s*s)/(eps*eps))}
  else{const p=Math.max(0.001,Math.min(0.999,Number(form.parametre)||0.5));if(eps>0)n=Math.ceil((t*t*p*(1-p))/(eps*eps))}
  form.taille_calculee=n;form.taille_retenue=n?Math.ceil(n*1.05):null;form.coefficient_t=t
}
function fmtN(n:any):string{if(n===null||n===undefined)return'—';return Number(n).toLocaleString('fr-FR',{minimumFractionDigits:0,maximumFractionDigits:2})}
function buildPayload(){return{mission_id:props.missionContext?.mission_id,assignment_id:props.missionContext?.assignment_id,procedure_code:props.missionContext?.procedure_code,test_ref:props.missionContext?.test_ref,auditeur:form.auditeur,objectif_audit:form.objectif_audit,population_reference:form.population_reference,objet_test:form.objet_test,type_sondage:form.type_sondage,taille_population:form.taille_population,niveau_confiance:form.niveau_confiance,erreur_max:form.erreur_max,ecart_type_exploratoire:form.type_sondage==='valeur'?form.parametre:null,taux_presence_exploratoire:form.type_sondage!=='valeur'?form.parametre:null,coefficient_t:form.coefficient_t,taille_calculee:form.taille_calculee,taille_retenue:form.taille_retenue,intervalle_confiance:form.intervalle_confiance,conclusion:form.conclusion,elements}}

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
