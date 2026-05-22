<template>
  <VerticalLayoutAudit>
    <div class="oe-root">

      <!-- ══ TOPBAR ══════════════════════════════════════════════ -->
      <div class="oe-bar">
        <a :href="backUrl" class="oe-back"><i class="ti ti-arrow-left"></i></a>
        <div class="oe-bar__id">
          <span class="oe-code">{{ form.code || 'ENT-AUTO' }}</span>
          <span class="oe-num">I</span>
          <span class="oe-name">Grille d'Entretien</span>
        </div>
        <div v-if="hasCtx" class="oe-bar__ctx">
          <span v-if="mc.test_ref" class="oe-ctag oe-ctag--v">{{ mc.test_ref }}</span>
          <span v-if="mc.procedure_code" class="oe-ctag oe-ctag--b">{{ mc.procedure_code }}</span>
          <span v-if="mc.mission_libelle" class="oe-ctag oe-ctag--g">{{ mc.mission_libelle }}</span>
        </div>
        <div class="oe-spacer"></div>
        <span class="oe-st" :class="'oe-st--' + form.statut">
          <i :class="statusIcon(form.statut)"></i>{{ statusLabel(form.statut) }}
        </span>
        <span class="oe-role"><i class="ti ti-shield-half"></i>{{ auditorRole }}</span>
        <template v-if="!isLocked">
          <button class="oe-btn oe-btn--ghost" :disabled="saving" @click="annuler"><i class="ti ti-x"></i></button>
          <button class="oe-btn oe-btn--save" :disabled="saving" @click="submit">
            <span v-if="saving" class="oe-spin"></span>
            <i v-else class="ti ti-device-floppy"></i>{{ form.id ? 'Sauver' : 'Créer' }}
          </button>
          <button v-if="form.id && form.statut === 'draft'" class="oe-btn oe-btn--submit" @click="soumettre">
            <i class="ti ti-send"></i>
          </button>
        </template>
        <template v-if="canManage && form.statut === 'in_review'">
          <button class="oe-btn oe-btn--ok" @click="valider('validated')"><i class="ti ti-check"></i></button>
          <button class="oe-btn oe-btn--ko" @click="promptReject"><i class="ti ti-x"></i></button>
        </template>

        <!-- ⭐ BOUTON EMAIL (toujours visible si fiche sauvegardée et email détecté) -->
        <button
          v-if="form.id && emailFromInterlocuteur"
          class="oe-btn oe-btn--mail"
          @click="sendValidationEmail"
          title="Envoyer la validation à l'interlocuteur">
          <i class="ti ti-mail"></i> Envoyer email
        </button>
        <button
          v-else-if="form.id && !emailFromInterlocuteur"
          class="oe-btn oe-btn--mail-disabled"
          @click="demanderEmailInterlocuteur"
          title="Renseignez l'interlocuteur avec son email (ex: Jean Dupont <jean@exemple.com>)">
          <i class="ti ti-mail"></i> Email manquant
        </button>

        <!-- BOUTON TEST EMAIL -->
        <button
          v-if="form.id"
          class="oe-btn oe-btn--test"
          @click="testEmail"
          title="Tester l’envoi d’email (SMTP)">
          <i class="ti ti-mail-search"></i> Test
        </button>
      </div>

      <!-- Banner -->
      <div v-if="banner.show" class="oe-banner" :class="'oe-banner--'+banner.type">
        <i :class="banner.icon"></i> {{ banner.msg }}
      </div>

      <!-- Onglets -->
      <div class="oe-tabs">
        <button class="oe-tab" :class="{'oe-tab--on':tab==='form'}" @click="tab='form'">
          <i class="ti ti-forms"></i> Formulaire
        </button>
        <button class="oe-tab" :class="{'oe-tab--on':tab==='docs'}" @click="tab='docs'">
          <i class="ti ti-files"></i> Documents &amp; IA
          <span v-if="documents.length" class="oe-tab-n">{{ documents.length }}</span>
        </button>
      </div>

      <!-- Corps -->
      <div class="oe-body">

        <!-- ── FORMULAIRE ────────────────────────────────────── -->
        <template v-if="tab==='form'">

          <!-- Contexte procédure -->
          <div v-if="hasCtx && (mc.libelle_proc||mc.libelle_test||mc.objectif_audit)" class="oe-ctx-strip">
            <div v-if="mc.libelle_test" class="oe-ctx-item">
              <span class="oe-ctx-k">Test</span>
              <span class="oe-ctx-v">{{ mc.libelle_test }}</span>
            </div>
            <div v-if="mc.libelle_proc" class="oe-ctx-item">
              <span class="oe-ctx-k">Procédure</span>
              <span class="oe-ctx-v">{{ mc.libelle_proc }}</span>
            </div>
            <div v-if="mc.objectif_audit" class="oe-ctx-item oe-ctx-item--full">
              <span class="oe-ctx-k">Objectif d'audit</span>
              <span class="oe-ctx-v">{{ mc.objectif_audit }}</span>
            </div>
          </div>

          <!-- Infos générales -->
          <div class="oe-section">
            <div class="oe-hd"><i class="ti ti-info-circle"></i> Informations générales</div>
            <div class="oe-grid">
              <div class="oe-f oe-s2">
                <label>Intitulé <span class="oe-req">*</span></label>
                <input class="oe-inp" v-model="form.intitule" :disabled="isLocked" placeholder="Objet de l'entretien" />
              </div>
              <div class="oe-f oe-s2">
                <label>Interlocuteur(s) <span class="oe-hint">(inclure l'email pour la validation)</span></label>
                <input class="oe-inp" v-model="form.interlocuteur" :disabled="isLocked" placeholder="Jean Dupont <jean@exemple.com>" />
              </div>
              <div class="oe-f"><label>Fonction</label><input class="oe-inp" v-model="form.fonction" :disabled="isLocked" /></div>
              <div class="oe-f"><label>Date</label><input type="date" class="oe-inp" v-model="form.date_entretien" :disabled="isLocked" /></div>
              <div class="oe-f oe-s2"><label>Lieu</label><input class="oe-inp" v-model="form.lieu" :disabled="isLocked" /></div>
              <div class="oe-f oe-s2">
                <label>Objectif de l'entretien</label>
                <textarea class="oe-ta" rows="2" v-model="form.objectif" :disabled="isLocked" placeholder="Ce que l'entretien vise à clarifier…"></textarea>
              </div>
            </div>
          </div>

          <!-- Questions -->
          <div class="oe-section">
            <div class="oe-hd">
              <i class="ti ti-help-circle"></i> Questions
              <span class="oe-badge">{{ questions.length }}</span>
              <button v-if="!isLocked" class="oe-add" @click="addQ"><i class="ti ti-plus"></i> Ajouter</button>
            </div>
            <div v-if="!questions.length" class="oe-empty"><i class="ti ti-help"></i> Aucune question</div>
            <div v-else class="oe-twrap">
              <table class="oe-tbl">
                <thead><tr>
                  <th style="width:28px">#</th>
                  <th style="width:90px">Type</th>
                  <th>Question</th><th>Réponse</th>
                  <th style="width:110px">Note</th>
                  <th v-if="!isLocked" style="width:26px"></th>
                </tr></thead>
                <tbody>
                  <tr v-for="(q,i) in questions" :key="i" :class="i%2?'oe-alt':''">
                    <td class="tc oe-n">{{ i+1 }}</td>
                    <td><select class="oe-sel" v-model="q.type" :disabled="isLocked">
                      <option>Ouverte</option><option>Fermée</option><option>Factuelle</option><option>Rebond</option>
                    </select></td>
                    <td><textarea class="oe-ta-sm" v-model="q.libelle" rows="2" :disabled="isLocked"></textarea></td>
                    <td><textarea class="oe-ta-sm" v-model="q.reponse" rows="2" :disabled="isLocked"></textarea></td>
                    <td><textarea class="oe-ta-sm" v-model="q.note" rows="2" :disabled="isLocked"></textarea></td>
                    <td v-if="!isLocked" class="tc"><button class="oe-del" @click="questions.splice(i,1)"><i class="ti ti-trash"></i></button></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Synthèse + Signatures côte à côte -->
          <div class="oe-section">
            <div class="oe-hd"><i class="ti ti-file-check"></i> Synthèse &amp; Signatures</div>
            <div class="oe-synth-row">
              <div class="oe-f" style="flex:2">
                <label>Synthèse</label>
                <textarea class="oe-ta" rows="4" v-model="form.synthese" :disabled="isLocked" placeholder="Points essentiels retenus…"></textarea>
              </div>
              <div style="flex:1;display:flex;flex-direction:column;gap:.4rem">
                <div class="oe-f">
                  <label>Sig. Auditeur</label>
                  <input class="oe-inp" v-model="form.sig_auditeur" :disabled="isLocked" />
                  <div class="oe-sig-line"></div>
                </div>
                <div class="oe-f">
                  <label>Sig. Interlocuteur</label>
                  <input class="oe-inp" v-model="form.sig_interlocuteur" :disabled="isLocked" />
                  <div class="oe-sig-line"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Résumé IA (si dispo) -->
          <div v-if="iaResult" class="oe-section oe-section--ia">
            <div class="oe-hd">
              <i class="ti ti-sparkles"></i> Analyse IA
              <span class="oe-ia-score">{{ iaResult.score }}/10</span>
              <button class="oe-add oe-add--ghost" @click="tab='docs'"><i class="ti ti-files"></i> Détail</button>
            </div>
            <p class="oe-ia-synth">{{ iaResult.synthese }}</p>
          </div>

        </template>

        <!-- ── DOCUMENTS & IA ─────────────────────────────────── -->
        <template v-if="tab==='docs'">

          <!-- Documents -->
          <div class="oe-section">
            <div class="oe-hd">
              <i class="ti ti-files"></i> Documents
              <span class="oe-badge">{{ documents.length }}</span>
              <template v-if="!isLocked">
                <input type="file" ref="fileInput" class="oe-fhidden"
                  accept=".doc,.docx,.odt,.rtf,.pdf,.xls,.xlsx,.csv" @change="handleUpload" />
                <button class="oe-add oe-add--upload" @click="fileInput?.click()" :disabled="uploading">
                  <span v-if="uploading" class="oe-spin oe-spin--d"></span>
                  <i v-else class="ti ti-upload"></i>
                  {{ uploading ? 'Upload…' : 'Importer' }}
                </button>
              </template>
            </div>
            <div v-if="!documents.length" class="oe-empty">
              <i class="ti ti-file-off"></i><span>Aucun document · Word, Excel, PDF acceptés (max 10 Mo)</span>
            </div>
            <div v-else class="oe-doclist">
              <div v-for="doc in documents" :key="doc.id" class="oe-doc" :class="'oe-doc--'+doc.status">
                <div class="oe-doc-ico" :class="icoClass(doc.file_extension)">
                  <i :class="fIcon(doc.file_extension)"></i>
                </div>
                <div class="oe-doc-info">
                  <span class="oe-doc-name">{{ doc.original_name }}</span>
                  <span class="oe-doc-meta">{{ fSize(doc.file_size) }} · {{ fDate(doc.created_at) }}</span>
                </div>
                <span class="oe-dst" :class="'oe-dst--'+doc.status">{{ dstLbl(doc.status) }}</span>
                <div class="oe-doc-acts">
                  <button v-if="isWord(doc.file_extension)" class="oe-da oe-da--w" @click="openWord(doc)" title="Éditer Word"><i class="ti ti-edit"></i></button>
                  <button v-else-if="isExcel(doc.file_extension)" class="oe-da oe-da--e" @click="openExcel(doc)" title="Éditer Excel"><i class="ti ti-edit"></i></button>
                  <button class="oe-da" @click="dlDoc(doc)" title="Télécharger"><i class="ti ti-download"></i></button>
                  <template v-if="canManage">
                    <button class="oe-da oe-da--ok" @click="valDoc(doc,'validated')"><i class="ti ti-check"></i></button>
                    <button class="oe-da oe-da--ko" @click="valDoc(doc,'rejected')"><i class="ti ti-x"></i></button>
                  </template>
                  <button v-if="!isLocked" class="oe-da oe-da--del" @click="delDoc(doc)"><i class="ti ti-trash"></i></button>
                </div>
              </div>
            </div>
          </div>

          <!-- Analyse IA -->
          <div class="oe-section oe-section--ia-panel">
            <div class="oe-hd">
              <div class="oe-ia-ico"><i class="ti ti-sparkles"></i></div>
              <div>
                <span class="oe-ia-title">Analyse IA · Mistral</span>
                <span class="oe-ia-sub">Synthèse automatique de l'entretien &amp; documents</span>
              </div>
              <div class="oe-spacer"></div>
              <button v-if="form.id" class="oe-btn-ia" :disabled="iaLoading" @click="genIa">
                <span v-if="iaLoading" class="oe-spin"></span>
                <i v-else class="ti ti-sparkles"></i>
                {{ iaLoading ? 'Analyse…' : 'Lancer' }}
              </button>
              <span v-else class="oe-ia-hint">Enregistrez d'abord</span>
            </div>

            <template v-if="iaResult">
              <div class="oe-ia-scorebar">
                <span class="oe-ia-scorenum">{{ iaResult.score }}<small>/10</small></span>
                <div class="oe-ia-bar-bg">
                  <div class="oe-ia-bar-fill" :style="{width:iaResult.score*10+'%',
                    background:iaResult.score>=7?'#16a34a':iaResult.score>=5?'#d97706':'#dc2626'}"></div>
                </div>
              </div>
              <div class="oe-ia-box">
                <span class="oe-ia-boxlbl">Synthèse</span>
                <p>{{ iaResult.synthese }}</p>
              </div>
              <div class="oe-ia-cols">
                <div v-if="iaResult.points_forts?.length" class="oe-ia-col oe-ia-col--ok">
                  <div class="oe-ia-col-hd"><i class="ti ti-thumb-up"></i> Points forts</div>
                  <ul><li v-for="(x,i) in iaResult.points_forts" :key="i">{{ x }}</li></ul>
                </div>
                <div v-if="iaResult.points_faibles?.length" class="oe-ia-col oe-ia-col--warn">
                  <div class="oe-ia-col-hd"><i class="ti ti-alert-triangle"></i> Faiblesses</div>
                  <ul><li v-for="(x,i) in iaResult.points_faibles" :key="i">{{ x }}</li></ul>
                </div>
                <div v-if="iaResult.risques?.length" class="oe-ia-col oe-ia-col--ko">
                  <div class="oe-ia-col-hd"><i class="ti ti-shield-exclamation"></i> Risques</div>
                  <ul><li v-for="(x,i) in iaResult.risques" :key="i">{{ x }}</li></ul>
                </div>
                <div v-if="iaResult.recommandations?.length" class="oe-ia-col oe-ia-col--blue">
                  <div class="oe-ia-col-hd"><i class="ti ti-bulb"></i> Recommandations</div>
                  <ul><li v-for="(x,i) in iaResult.recommandations" :key="i">{{ x }}</li></ul>
                </div>
              </div>
              <div class="oe-ia-foot">
                <button class="oe-add oe-add--ghost" @click="reportSynth">
                  <i class="ti ti-file-import"></i> Reporter la synthèse dans le formulaire
                </button>
              </div>
            </template>
            <div v-else class="oe-empty">
              <i class="ti ti-sparkles"></i>
              <span>{{ form.id ? "Cliquez sur Lancer pour analyser" : "Enregistrez d'abord la fiche" }}</span>
            </div>
          </div>

        </template>
      </div>

      <!-- Toast -->
      <Transition name="t">
        <div v-if="toast.show" class="oe-toast" :class="'oe-toast--'+toast.type">
          <i :class="toast.type==='success'?'ti ti-circle-check':'ti ti-alert-circle'"></i>{{ toast.msg }}
        </div>
      </Transition>
    </div>

    <WordEditorModal v-if="wordEd.doc" v-model:show="wordEd.show" :doc="wordEd.doc"
      :url-edit-base="urlEditDocBase" :url-save-base="urlSaveDocBase" @saved="onWordSaved" />
    <ExcelEditorModal v-if="excelEd.doc && urlLoadExcelBase && urlSaveExcelBase"
      v-model:show="excelEd.show" :doc="excelEd.doc"
      :url-load-base="urlLoadExcelBase" :url-save-base="urlSaveExcelBase" @saved="onExcelSaved" />
  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { reactive, ref, computed, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayoutAudit from '@/Layouts/VerticalLayoutAudit.vue'
import WordEditorModal  from '@/Components/WordEditorModal.vue'
import ExcelEditorModal from '@/Components/ExcelEditorModal.vue'

const props = defineProps<{
  entretien?: any; documents?: any[]; questions?: any[]
  auditorRole?: string; auditeurNom?: string; backUrl?: string
  urlStore?: string; urlUpdate?: string|null; urlSoumettre?: string|null
  urlValider?: string|null; urlIa?: string|null; urlUploadDoc?: string
  urlDownloadDocBase?: string; urlEditDocBase?: string; urlSaveDocBase?: string
  urlLoadExcelBase?: string; urlSaveExcelBase?: string
  urlValidateDocBase?: string; urlDeleteDocBase?: string
  urlSendValidationEmail?: string
  missionContext?: {
    mission_id?: number|string; assignment_id?: number|string
    mission_libelle?: string; procedure_code?: string; test_ref?: string
    libelle_test?: string; libelle_proc?: string; objectif_audit?: string
  }
}>()

const auditorRole    = props.auditorRole ?? 'AJ'
const backUrl        = props.backUrl ?? '/'
const urlEditDocBase = props.urlEditDocBase ?? ''
const urlSaveDocBase = props.urlSaveDocBase ?? ''
const urlLoadExcelBase = props.urlLoadExcelBase ?? ''
const urlSaveExcelBase = props.urlSaveExcelBase ?? ''
const mc = computed(() => props.missionContext ?? {})

const tab  = ref<'form'|'docs'>('form')
const form = reactive<any>({
  id:null,code:'',statut:'draft',validation_note:'',validation_status:'pending',
  intitule:'',objectif:'',interlocuteur:'',fonction:'',date_entretien:'',lieu:'',
  synthese:'',sig_auditeur:props.auditeurNom??'',sig_interlocuteur:'',confirmed_at:null,
  ...(props.entretien??{}),
})
const questions = reactive<any[]>([...(props.questions??[])])
const documents = reactive<any[]>([...(props.documents??[])])
const saving    = ref(false)
const uploading = ref(false)
const fileInput = ref<HTMLInputElement|null>(null)
const iaLoading = ref(false)
const iaResult  = ref<any>(null)
const toast     = reactive({show:false,type:'success',msg:''})
let _tt: ReturnType<typeof setTimeout>|null = null
const wordEd  = reactive<{show:boolean;doc:any}>({show:false,doc:null})
const excelEd = reactive<{show:boolean;doc:any}>({show:false,doc:null})

const canManage = computed(()=>['DM','CM'].includes(auditorRole))
const isLocked  = computed(()=>form.statut==='validated'||(form.statut==='in_review'&&!canManage.value)||form.validation_status==='email_sent')
const hasCtx    = computed(()=>!!(mc.value.test_ref||mc.value.procedure_code))
const banner    = computed(()=>{
  if(form.validation_status==='confirmed') return {show:true,type:'ok',icon:'ti ti-check',msg:`Confirmée par l'audité le ${fDate(form.confirmed_at)}`}
  if(form.validation_status==='email_sent') return {show:true,type:'mail',icon:'ti ti-mail',msg:'Email envoyé — en attente de confirmation'}
  if(form.statut==='validated') return {show:true,type:'ok',icon:'ti ti-lock',msg:'Fiche validée — lecture seule'}
  if(form.statut==='in_review') return {show:true,type:'rev',icon:'ti ti-clock',msg:'Soumise pour validation'}
  if(form.statut==='draft'&&form.validation_note) return {show:true,type:'ko',icon:'ti ti-circle-x',msg:`Rejetée — ${form.validation_note}`}
  return {show:false,type:'',icon:'',msg:''}
})

// Extractions email
function extractEmailFromString(str: string|null|undefined): string|null {
  if (!str) return null
  const match = str.match(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/)
  return match ? match[0] : null
}
const emailFromInterlocuteur = computed(() => extractEmailFromString(form.interlocuteur))

// Helpers divers
function csrf(){return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content??''}
function statusLabel(s:string){return{draft:'Brouillon',in_review:'En révision',validated:'Validé',rejected:'Rejeté'}[s]??s}
function statusIcon(s:string){return{draft:'ti ti-edit',in_review:'ti ti-clock',validated:'ti ti-lock',rejected:'ti ti-circle-x'}[s]??'ti ti-file'}
function fDate(d:string|null){return d?new Date(d).toLocaleDateString('fr-FR'):''}
function fSize(b:number){if(!b)return'—';const k=1024,i=Math.floor(Math.log(b)/Math.log(k));return parseFloat((b/Math.pow(k,i)).toFixed(1))+' '+['B','KB','MB','GB'][i]}
function showToast(type:string,msg:string,dur=3500){if(_tt)clearTimeout(_tt);toast.show=true;toast.type=type;toast.msg=msg;_tt=setTimeout(()=>{toast.show=false},dur)}
function dUrl(base:string,id:number){return base?.replace('__DOC__',String(id))||''}
function isWord(ext:string){return['doc','docx','odt','rtf'].includes(ext?.toLowerCase()??'')}
function isExcel(ext:string){return['xls','xlsx','csv'].includes(ext?.toLowerCase()??'')}
function isPdf(ext:string){return ext?.toLowerCase()==='pdf'}
function fIcon(ext:string){if(isWord(ext))return'ti ti-file-word';if(isExcel(ext))return'ti ti-file-spreadsheet';if(isPdf(ext))return'ti ti-file-type-pdf';return'ti ti-file'}
function icoClass(ext:string){if(isWord(ext))return'oe-dic--w';if(isExcel(ext))return'oe-dic--e';if(isPdf(ext))return'oe-dic--p';return''}
function dstLbl(s:string){return{draft:'Brouillon',validated:'Validé',rejected:'Rejeté'}[s]??s}
function annuler(){if(backUrl)router.visit(backUrl)}
function addQ(){questions.push({type:'Ouverte',libelle:'',reponse:'',note:''})}
function reportSynth(){if(iaResult.value?.synthese){form.synthese=iaResult.value.synthese;tab.value='form';showToast('success','Synthèse reportée')}}

// CRUD
async function submit(){
  saving.value=true
  try{
    const url=form.id?props.urlUpdate:props.urlStore
    if(!url)throw new Error('URL manquante')
    const questionsValides = questions.filter((q:any) => q.libelle?.trim())
    const payload:any={
      mission_id:mc.value.mission_id,assignment_id:mc.value.assignment_id,
      procedure_code:mc.value.procedure_code,test_ref:mc.value.test_ref,
      intitule:form.intitule,objectif:form.objectif,interlocuteur:form.interlocuteur,
      fonction:form.fonction,date_entretien:form.date_entretien,lieu:form.lieu,
      synthese:form.synthese,sig_auditeur:form.sig_auditeur,
      sig_interlocuteur:form.sig_interlocuteur,questions:questionsValides,
    }
    if(form.id) payload._method='PUT'
    const res=await fetch(url,{
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf(),'Accept':'application/json'},
      body:JSON.stringify(payload),
    })
    if(!res.ok){
      const txt=await res.text()
      throw new Error(`HTTP ${res.status} — ${txt.substring(0,200)}`)
    }
    const d=await res.json()
    if(d.success){showToast('success',form.id?'Mis à jour':'Créé');if(d.record){form.id=d.record.id;form.code=d.record.code;form.statut=d.record.statut}}
    else throw new Error(d.error||'Erreur')
  }catch(e:any){showToast('error',e.message)}finally{saving.value=false}
}

async function soumettre(){
  if(!props.urlSoumettre)return;saving.value=true
  try{const d=await(await fetch(props.urlSoumettre,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:'{}'})).json();if(d.success){form.statut='in_review';showToast('success','Soumise')}else throw new Error(d.error)}
  catch(e:any){showToast('error',e.message)}finally{saving.value=false}
}

async function valider(action:string,note?:string){
  if(!props.urlValider)return;saving.value=true
  try{const d=await(await fetch(props.urlValider,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({decision:action,commentaire:note})})).json();if(d.success){form.statut=action;showToast('success',action==='validated'?'Validée ✓':'Rejetée')}else throw new Error(d.error)}
  catch(e:any){showToast('error',e.message)}finally{saving.value=false}
}
function promptReject(){const n=prompt('Motif du rejet :');if(n?.trim())valider('rejected',n.trim())}

// Documents
async function handleUpload(e:Event){
  const inp=e.target as HTMLInputElement;if(!inp.files?.length)return
  const fd=new FormData();fd.append('document',inp.files[0])
  if(mc.value.assignment_id)fd.append('assignment_id',String(mc.value.assignment_id))
  uploading.value=true
  try{const d=await(await fetch(props.urlUploadDoc!,{method:'POST',headers:{'X-CSRF-TOKEN':csrf()},body:fd})).json()
    if(d.success&&d.document){documents.unshift(d.document);showToast('success','Importé');if(isWord(d.document.file_extension))setTimeout(()=>openWord(d.document),300);else if(isExcel(d.document.file_extension))setTimeout(()=>openExcel(d.document),300)}
    else throw new Error(d.error||'Erreur')}
  catch(e:any){showToast('error',e.message)}finally{uploading.value=false;inp.value=''}
}

function openWord(doc:any){wordEd.doc=doc;wordEd.show=true}
function openExcel(doc:any){excelEd.doc=doc;excelEd.show=true}
function onWordSaved(){showToast('success','Word sauvegardé')}
function onExcelSaved(){showToast('success','Excel sauvegardé')}
function dlDoc(doc:any){window.open(dUrl(props.urlDownloadDocBase!,doc.id),'_blank')}

async function valDoc(doc:any,status:string){
  let comment=''
  if(status==='rejected'){comment=prompt('Motif :')||'';if(!comment)return}
  try{const d=await(await fetch(dUrl(props.urlValidateDocBase!,doc.id),{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({status,comment})})).json()
    if(d.success){const i=documents.findIndex(x=>x.id===doc.id);if(i!==-1)documents[i].status=status;showToast('success',status==='validated'?'Validé':'Rejeté')}}
  catch(e:any){showToast('error',e.message)}
}

async function delDoc(doc:any){
  if(!confirm('Supprimer ce document ?'))return
  try{const d=await(await fetch(dUrl(props.urlDeleteDocBase!,doc.id),{method:'DELETE',headers:{'X-CSRF-TOKEN':csrf()}})).json()
    if(d.success){documents.splice(documents.findIndex(x=>x.id===doc.id),1);showToast('success','Supprimé')}}
  catch(e:any){showToast('error',e.message)}
}

// ⭐ Envoi d'email (sans condition de statut)
async function sendValidationEmail(){
  if(!props.urlSendValidationEmail){
    showToast('error','URL d’envoi d’email manquante. Re-sauvegardez la fiche.')
    return
  }
  let email = emailFromInterlocuteur.value
  if(!email){
    email = prompt("Email de l'interlocuteur :")
    if(!email?.trim()){showToast('error','Email requis.');return}
    // Optionnel : on pourrait mettre à jour form.interlocuteur pour conserver l'email
    // mais on ne modifie pas automatiquement pour éviter de perdre le format.
  }
  try{
    const res = await fetch(props.urlSendValidationEmail,{
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf(),'Accept':'application/json'},
      body:JSON.stringify({email: email!.trim()})
    })
    const data = await res.json()
    if(!res.ok) throw new Error(data.error || `HTTP ${res.status}`)
    if(data.success){
      form.validation_status = 'email_sent'
      showToast('success',`Email envoyé à ${email} — en attente de confirmation.`,6000)
    } else {
      throw new Error(data.error || 'Erreur inconnue')
    }
  } catch(e:any){
    showToast('error',e.message)
  }
}

function demanderEmailInterlocuteur(){
  const email = prompt("Veuillez renseigner l'email de l'interlocuteur :")
  if(email?.trim()){
    const current = form.interlocuteur || ''
    // Si le champ ne contient pas déjà un email, on l'ajoute entre < >
    if(!extractEmailFromString(current)){
      form.interlocuteur = current + (current ? ' ' : '') + '<' + email.trim() + '>'
    } else {
      // sinon on remplace l'email existant (simple)
      form.interlocuteur = current.replace(/<[^>]*>/, `<${email.trim()}>`)
    }
    showToast('info','Email ajouté. Sauvegardez la fiche pour envoyer l’email.')
  }
}

// Test email via route dédiée
async function testEmail(){
  const email = emailFromInterlocuteur.value || prompt("Email de test :")
  if(!email?.trim()){showToast('error','Email requis.');return}
  try{
    const res = await fetch('/test-email',{
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf(),'Accept':'application/json'},
      body:JSON.stringify({email: email.trim()})
    })
    const data = await res.json()
    if(data.success) showToast('success',`Email test envoyé à ${email}.`)
    else throw new Error(data.error)
  } catch(e:any){
    showToast('error',`Échec envoi : ${e.message}`)
  }
}

// IA
async function genIa(){
  if(!props.urlIa)return;iaLoading.value=true
  try{const d=await(await fetch(props.urlIa,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:'{}'})).json()
    if(d.success){iaResult.value=d.ia_result;showToast('success','Analyse générée')}else throw new Error(d.error)}
  catch(e:any){showToast('error',e.message)}finally{iaLoading.value=false}
}

onBeforeUnmount(()=>{if(_tt)clearTimeout(_tt)})
</script>

<style scoped>
*{box-sizing:border-box}
.oe-root{display:flex;flex-direction:column;height:100vh;overflow:hidden;background:#f0f4f8;font-family:'Segoe UI',system-ui,sans-serif;font-size:.78rem}

/* TOPBAR */
.oe-bar{display:flex;align-items:center;gap:.35rem;padding:.28rem .7rem;background:#fff;border-bottom:1px solid #e2e8f0;flex-shrink:0;min-height:38px;overflow:hidden}
.oe-back{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border:1px solid #e2e8f0;border-radius:5px;color:#64748b;text-decoration:none;flex-shrink:0}
.oe-back:hover{background:#f1f5f9}
.oe-bar__id{display:flex;align-items:center;gap:.25rem;flex-shrink:0}
.oe-code{background:#0f172a;color:#e2e8f0;padding:1px 6px;border-radius:4px;font-size:.6rem;font-family:monospace;font-weight:600}
.oe-num{background:#1e40af;color:#fff;padding:1px 6px;border-radius:4px;font-size:.6rem;font-weight:700}
.oe-name{font-size:.75rem;font-weight:700;color:#0f172a;white-space:nowrap}
.oe-bar__ctx{display:flex;gap:.2rem;overflow:hidden;flex-wrap:nowrap}
.oe-ctag{padding:1px 6px;border-radius:20px;font-size:.58rem;font-weight:600;white-space:nowrap}
.oe-ctag--v{background:#ede9fe;color:#6d28d9}
.oe-ctag--b{background:#dbeafe;color:#1d4ed8}
.oe-ctag--g{background:#f0fdf4;color:#15803d;max-width:160px;overflow:hidden;text-overflow:ellipsis}
.oe-spacer{flex:1}
.oe-st{display:inline-flex;align-items:center;gap:.15rem;padding:1px 6px;border-radius:20px;font-size:.6rem;font-weight:600;white-space:nowrap;flex-shrink:0}
.oe-st--draft{background:#f1f5f9;color:#64748b}
.oe-st--in_review{background:#dbeafe;color:#1d4ed8}
.oe-st--validated{background:#dcfce7;color:#15803d}
.oe-st--rejected{background:#fee2e2;color:#dc2626}
.oe-role{font-size:.6rem;color:#64748b;padding:1px 6px;border:1px solid #e2e8f0;border-radius:20px;white-space:nowrap;flex-shrink:0}
.oe-btn{display:inline-flex;align-items:center;gap:.18rem;padding:3px 8px;border:none;border-radius:5px;font-size:.68rem;font-weight:600;cursor:pointer;flex-shrink:0}
.oe-btn:disabled{opacity:.45;cursor:not-allowed}
.oe-btn--ghost{background:#f1f5f9;color:#475569;border:1px solid #e2e8f0}
.oe-btn--save{background:#1e40af;color:#fff}
.oe-btn--submit{background:#7c3aed;color:#fff;padding:3px 6px}
.oe-btn--ok{background:#15803d;color:#fff;padding:3px 6px}
.oe-btn--ko{background:#dc2626;color:#fff;padding:3px 6px}
.oe-btn--mail{background:#d97706;color:#fff;padding:3px 6px}
.oe-btn--mail-disabled{background:#e2e8f0;color:#94a3b8;padding:3px 6px;cursor:not-allowed}
.oe-btn--test{background:#0f172a;color:#fff;padding:3px 6px}
.oe-hint{font-weight:400;text-transform:none;font-size:.56rem;color:#94a3b8}

/* BANNER */
.oe-banner{display:flex;align-items:center;gap:.3rem;padding:.2rem .7rem;font-size:.68rem;flex-shrink:0}
.oe-banner--ok{background:#d1fae5;color:#065f46}
.oe-banner--rev{background:#dbeafe;color:#1d4ed8}
.oe-banner--ko{background:#fee2e2;color:#dc2626}
.oe-banner--mail{background:#fef3c7;color:#92400e}

/* ONGLETS */
.oe-tabs{display:flex;background:#f8fafc;border-bottom:1px solid #e2e8f0;flex-shrink:0}
.oe-tab{display:inline-flex;align-items:center;gap:.2rem;padding:.3rem .8rem;font-size:.7rem;font-weight:600;color:#64748b;border:none;background:none;border-bottom:2px solid transparent;cursor:pointer}
.oe-tab:hover{color:#1e40af;background:#eff6ff}
.oe-tab--on{color:#1e40af;border-bottom-color:#1e40af;background:#fff}
.oe-tab-n{background:#1e40af;color:#fff;padding:0 5px;border-radius:10px;font-size:.56rem;line-height:1.4}

/* BODY */
.oe-body{flex:1;overflow-y:auto;padding:.5rem .65rem;display:flex;flex-direction:column;gap:.45rem}

/* SECTIONS */
.oe-section{background:#fff;border:1px solid #e2e8f0;border-radius:7px;padding:.5rem .6rem}
.oe-section--ia{border-left:3px solid #7c3aed;background:#faf5ff}
.oe-section--ia-panel{background:#fff}
.oe-hd{display:flex;align-items:center;gap:.3rem;font-size:.72rem;font-weight:700;color:#0f172a;margin-bottom:.4rem;flex-wrap:wrap}
.oe-hd i{color:#64748b;font-size:.85rem}
.oe-badge{background:#f1f5f9;border:1px solid #e2e8f0;padding:0 5px;border-radius:10px;font-size:.58rem;font-weight:600}

/* CONTEXTE STRIP */
.oe-ctx-strip{background:#faf5ff;border:1px solid #ddd6fe;border-radius:6px;padding:.35rem .55rem;display:flex;gap:.4rem .8rem;flex-wrap:wrap}
.oe-ctx-item{display:flex;flex-direction:column;gap:.03rem;min-width:100px}
.oe-ctx-item--full{flex:1 1 100%}
.oe-ctx-k{font-size:.56rem;font-weight:700;text-transform:uppercase;color:#7c3aed;letter-spacing:.04em}
.oe-ctx-v{font-size:.68rem;color:#1e293b;line-height:1.35}

/* GRILLE */
.oe-grid{display:grid;grid-template-columns:1fr 1fr;gap:.35rem}
.oe-f{display:flex;flex-direction:column;gap:.12rem}
.oe-f label{font-size:.58rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.03em}
.oe-req{color:#dc2626}
.oe-s2{grid-column:1/-1}
.oe-inp,.oe-ta{border:1px solid #e2e8f0;border-radius:5px;padding:4px 6px;font-size:.73rem;font-family:inherit;width:100%;outline:none}
.oe-inp:focus,.oe-ta:focus{border-color:#93c5fd}
.oe-inp:disabled,.oe-ta:disabled{background:#f8fafc;color:#64748b}
.oe-ta{resize:vertical}

/* SYNTHESE */
.oe-synth-row{display:flex;gap:.55rem;align-items:flex-start}
.oe-sig-line{height:1px;background:repeating-linear-gradient(90deg,#cbd5e1 0,#cbd5e1 5px,transparent 5px,transparent 10px);margin-top:.2rem}

/* QUESTIONS */
.oe-add{display:inline-flex;align-items:center;gap:.18rem;padding:2px 7px;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;border-radius:5px;font-size:.65rem;cursor:pointer;font-weight:600;margin-left:auto}
.oe-add:hover{background:#dbeafe}
.oe-add--upload{background:#f0fdf4;border-color:#bbf7d0;color:#15803d}
.oe-add--ghost{background:none;border:1px solid #e2e8f0;color:#475569}
.oe-add--ghost:hover{background:#f1f5f9}
.oe-add:disabled{opacity:.45;cursor:not-allowed}
.oe-empty{display:flex;align-items:center;gap:.35rem;justify-content:center;padding:.6rem;color:#94a3b8;font-size:.7rem;font-style:italic}
.oe-twrap{overflow-x:auto;border:1px solid #e2e8f0;border-radius:5px}
.oe-tbl{width:100%;border-collapse:collapse;font-size:.68rem}
.oe-tbl th{padding:.28rem .4rem;background:#0f172a;color:#fff;font-size:.6rem;font-weight:600;text-align:left}
.oe-tbl td{padding:.22rem .32rem;border-bottom:1px solid #f3f4f6;vertical-align:top}
.oe-alt{background:#fafbfc}
.oe-n{color:#94a3b8;font-size:.6rem;font-weight:600;text-align:center}
.oe-ta-sm{width:100%;border:1px solid #e5e7eb;border-radius:4px;padding:3px 5px;font-size:.68rem;resize:vertical;font-family:inherit}
.oe-sel{width:100%;border:1px solid #e2e8f0;border-radius:4px;padding:3px 5px;font-size:.66rem}
.oe-del{background:#fee2e2;border:1px solid #fecaca;color:#dc2626;border-radius:4px;cursor:pointer;padding:2px 4px;font-size:.62rem}
.tc{text-align:center}

/* DOCUMENTS */
.oe-fhidden{display:none}
.oe-doclist{display:flex;flex-direction:column;gap:.3rem}
.oe-doc{display:flex;align-items:center;gap:.4rem;padding:.35rem .5rem;background:#f8fafc;border-radius:6px;border:1px solid #e2e8f0}
.oe-doc--validated{border-left:3px solid #15803d;background:#f0fdf4}
.oe-doc--rejected{border-left:3px solid #dc2626;background:#fef2f2}
.oe-doc-ico{width:28px;height:28px;border-radius:5px;display:flex;align-items:center;justify-content:center;font-size:.9rem;background:#dbeafe;color:#1d4ed8;flex-shrink:0}
.oe-dic--w{background:#dbeafe;color:#1d4ed8}
.oe-dic--e{background:#e8f5e9;color:#2e7d32}
.oe-dic--p{background:#fef2f2;color:#dc2626}
.oe-doc-info{flex:1;min-width:0;display:flex;flex-direction:column;gap:.02rem}
.oe-doc-name{font-size:.7rem;font-weight:600;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.oe-doc-meta{font-size:.58rem;color:#64748b}
.oe-dst{font-size:.56rem;font-weight:600;padding:1px 5px;border-radius:10px;white-space:nowrap;flex-shrink:0}
.oe-dst--draft{background:#f1f5f9;color:#64748b}
.oe-dst--validated{background:#dcfce7;color:#15803d}
.oe-dst--rejected{background:#fee2e2;color:#dc2626}
.oe-doc-acts{display:flex;gap:.18rem;flex-shrink:0}
.oe-da{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border:1px solid #e2e8f0;border-radius:4px;background:none;cursor:pointer;font-size:.65rem}
.oe-da:hover{background:#f1f5f9}
.oe-da--w{background:#1e40af;color:#fff;border-color:#1e40af}
.oe-da--e{background:#2e7d32;color:#fff;border-color:#2e7d32}
.oe-da--ok:hover{color:#15803d;border-color:#15803d;background:#f0fdf4}
.oe-da--ko:hover{color:#dc2626;border-color:#dc2626;background:#fef2f2}
.oe-da--del:hover{color:#dc2626;border-color:#fecaca;background:#fef2f2}

/* IA */
.oe-ia-ico{width:30px;height:30px;background:linear-gradient(135deg,#7c3aed,#6d28d9);border-radius:7px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.9rem;flex-shrink:0}
.oe-ia-title{font-size:.72rem;font-weight:700;color:#0f172a;display:block}
.oe-ia-sub{font-size:.6rem;color:#64748b;display:block}
.oe-ia-hint{font-size:.62rem;color:#94a3b8;font-style:italic}
.oe-btn-ia{display:inline-flex;align-items:center;gap:.2rem;padding:4px 10px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:5px;font-size:.68rem;font-weight:600;cursor:pointer;flex-shrink:0}
.oe-btn-ia:disabled{opacity:.45;cursor:not-allowed}
.oe-ia-scorebar{display:flex;align-items:center;gap:.5rem;padding:.3rem .45rem;background:#f8fafc;border-radius:5px;border:1px solid #e2e8f0;margin-bottom:.4rem}
.oe-ia-scorenum{font-size:1.3rem;font-weight:900;color:#0f172a;line-height:1;flex-shrink:0}
.oe-ia-scorenum small{font-size:.58rem;color:#64748b;font-weight:400}
.oe-ia-bar-bg{flex:1;height:5px;background:#e2e8f0;border-radius:3px;overflow:hidden}
.oe-ia-bar-fill{height:100%;border-radius:3px;transition:width .5s}
.oe-ia-box{padding:.35rem .5rem;border-radius:5px;background:#f8fafc;border-left:3px solid #7c3aed;margin-bottom:.4rem}
.oe-ia-boxlbl{font-size:.58rem;font-weight:700;color:#7c3aed;text-transform:uppercase;display:block;margin-bottom:.15rem}
.oe-ia-box p{font-size:.7rem;color:#1e293b;margin:0;line-height:1.45}
.oe-ia-cols{display:grid;grid-template-columns:1fr 1fr;gap:.35rem;margin-bottom:.4rem}
.oe-ia-col{border-radius:5px;padding:.35rem .5rem}
.oe-ia-col--ok{background:#f0fdf4;border:1px solid #bbf7d0}
.oe-ia-col--warn{background:#fffbeb;border:1px solid #fde68a}
.oe-ia-col--ko{background:#fef2f2;border:1px solid #fecaca}
.oe-ia-col--blue{background:#eff6ff;border:1px solid #bfdbfe}
.oe-ia-col-hd{display:flex;align-items:center;gap:.2rem;font-size:.62rem;font-weight:700;margin-bottom:.2rem}
.oe-ia-col--ok .oe-ia-col-hd{color:#15803d}
.oe-ia-col--warn .oe-ia-col-hd{color:#92400e}
.oe-ia-col--ko .oe-ia-col-hd{color:#dc2626}
.oe-ia-col--blue .oe-ia-col-hd{color:#1d4ed8}
.oe-ia-col ul{margin:0;padding-left:.8rem}
.oe-ia-col li{font-size:.65rem;color:#1e293b;margin-bottom:.12rem;line-height:1.3}
.oe-ia-foot{display:flex;padding-top:.3rem;border-top:1px solid #e2e8f0}
.oe-ia-score{margin-left:.2rem;font-size:.95rem;font-weight:800;color:#7c3aed}
.oe-ia-synth{font-size:.7rem;color:#1e293b;margin:0;line-height:1.45}

/* SPINNER */
.oe-spin{display:inline-block;width:10px;height:10px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:sp .7s linear infinite}
.oe-spin--d{border:2px solid rgba(0,0,0,.1);border-top-color:#15803d}
@keyframes sp{to{transform:rotate(360deg)}}

/* TOAST */
.oe-toast{position:fixed;bottom:.75rem;right:.75rem;display:flex;align-items:center;gap:.3rem;padding:.4rem .8rem;border-radius:7px;font-size:.7rem;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,.15)}
.oe-toast--success{background:#dcfce7;color:#15803d}
.oe-toast--error{background:#fee2e2;color:#dc2626}
.t-enter-active,.t-leave-active{transition:all .2s}
.t-enter-from,.t-leave-to{opacity:0;transform:translateY(6px)}

@media(max-width:600px){
  .oe-grid{grid-template-columns:1fr}
  .oe-s2{grid-column:1}
  .oe-synth-row{flex-direction:column}
  .oe-ia-cols{grid-template-columns:1fr}
  .oe-bar__ctx{display:none}
}
</style>