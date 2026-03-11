<template>
  <VerticalLayoutAudit>
  <div class="qpc-wrap">

    <!-- ══ ENTÊTE MISSION ══ -->
    <div class="qpc-section-border">
      <div class="qpc-section-label">Entité mission</div>
      <div class="qpc-entity-row">
        <div class="qpc-ef"><span class="qpc-el">Projet</span><input class="qpc-ei" :value="mission?.code_mission??''" readonly /></div>
        <div class="qpc-ef qpc-ef-wide"><span class="qpc-el">Entité</span><input class="qpc-ei" :value="mission?.entity_name??''" readonly /></div>
        <div class="qpc-ef"><span class="qpc-el">Phase</span><input class="qpc-ei" :value="assignment?.phase_code??''" readonly /></div>
      </div>
    </div>

    <!-- ══ INFO QUESTIONNAIRE ══ -->
    <div class="qpc-section-border qpc-mt">
      <div class="qpc-section-label">Info questionnaire</div>
      <div class="qpc-info-row">
        <span class="qpc-il">Code QPC</span>
        <input class="qpc-ii qpc-ii-code" :value="pdc.code||''" readonly />
        <span class="qpc-il">Mission</span>
        <input class="qpc-ii qpc-ii-mission" :value="mission?.libelle??''" readonly />
      </div>
      <div class="qpc-fp-row">
        <span class="qpc-il">Fait par</span>
        <input class="qpc-ii qpc-ii-fp" v-model="form.fait_par" :disabled="isLocked" />
        <span class="qpc-il qpc-il-date">Date</span>
        <input type="date" class="qpc-ii qpc-ii-date" v-model="form.date_fait" :disabled="isLocked" />
      </div>
      <div class="qpc-fp-row">
        <span class="qpc-il">Revu par</span>
        <input class="qpc-ii qpc-ii-fp" v-model="form.revue_par" :disabled="isLocked" />
        <span class="qpc-il qpc-il-date">Date</span>
        <input type="date" class="qpc-ii qpc-ii-date" v-model="form.date_revue" :disabled="isLocked" />
      </div>
    </div>

    <!-- ══ INTITULÉ + IMPORTER ══ -->
    <div class="qpc-intitule-row qpc-mt">
      <span class="qpc-il">Intitulé QPC</span>
      <input class="qpc-ii qpc-ii-intitule" v-model="form.intitule_qpc" :disabled="isLocked" placeholder="TITRE QPC" />
      <label v-if="!isLocked" class="qpc-btn-import">
        <i class="ti ti-upload"></i> Importer
        <input type="file" accept=".xlsx,.xls" class="qpc-fhid" @change="importExcel" ref="xlsRef" />
      </label>
      <a href="/templates/QPC_Template.xlsx" download class="qpc-btn-tpl" title="Télécharger le template Excel">
        <i class="ti ti-download"></i> Template
      </a>
    </div>

    <!-- ══ TABLEAU QPC ══ -->
    <div class="qpc-table-wrap">
      <!-- En-têtes -->
      <div class="qpc-thead">
        <div class="qpc-th qpc-th-code">Code <i class="ti ti-arrows-sort qpc-sico"></i></div>
        <div class="qpc-th qpc-th-lib">Libellé <i class="ti ti-arrows-sort qpc-sico"></i></div>
        <div class="qpc-th qpc-th-file"><i class="ti ti-filter qpc-sico"></i> FichierAttaché <i class="ti ti-chevron-right qpc-sico"></i></div>
        <div v-if="!isLocked" class="qpc-th qpc-th-act"></div>
      </div>

      <!-- Corps -->
      <div class="qpc-tbody">
        <template v-for="(item, i) in qpcItems" :key="i">

          <!-- CATÉGORIE -->
          <div v-if="item.type==='cat'" class="qpc-row-cat">
            <div class="qpc-td-code">
              <button type="button" class="qpc-cat-toggle" @click="toggleCat(i)" v-if="!isLocked">
                <i class="ti" :class="collapsedCats.has(i)?'ti-chevron-right':'ti-chevron-down'"></i>
              </button>
              <i class="ti ti-folder qpc-cat-folder"></i>
              <span class="qpc-cat-num">{{ item.num }}</span>
            </div>
            <div class="qpc-td-lib">
              <input v-if="!isLocked" class="qpc-cat-lib-inp" v-model="item.libelle" placeholder="Titre de la catégorie…" />
              <strong v-else class="qpc-cat-lib-ro">{{ item.libelle }}</strong>
            </div>
            <div class="qpc-td-file"></div>
            <div v-if="!isLocked" class="qpc-td-act">
              <button type="button" class="qpc-del" @click="removeRow(i)"><i class="ti ti-x"></i></button>
            </div>
          </div>

          <!-- ITEM -->
          <div v-else class="qpc-row-item" :class="{alt:i%2===0}" v-show="!isCatHidden(i)">
            <div class="qpc-td-code">
              <i class="ti ti-file-text qpc-item-ico"></i>
              <input v-if="!isLocked" class="qpc-item-code-inp" v-model="item.code" placeholder="Code" />
              <span v-else class="qpc-item-code-ro">{{ item.code }}</span>
            </div>
            <div class="qpc-td-lib">
              <input v-if="!isLocked" class="qpc-item-lib-inp" v-model="item.libelle" placeholder="Description de l'item…" />
              <span v-else class="qpc-item-lib-ro">{{ item.libelle }}</span>
            </div>
            <div class="qpc-td-file">
              <label v-if="!isLocked" class="qpc-attach-lbl">
                <i class="ti ti-paperclip"></i>
                <input type="file" class="qpc-fhid" @change="e=>onFile(e,item)" />
              </label>
              <span class="qpc-attach-name">{{ item.fichier||'' }}</span>
            </div>
            <div v-if="!isLocked" class="qpc-td-act">
              <button type="button" class="qpc-del" @click="removeRow(i)"><i class="ti ti-x"></i></button>
            </div>
          </div>

        </template>

        <!-- Vide -->
        <div v-if="qpcItems.length===0" class="qpc-empty">
          <i class="ti ti-table-off"></i>
          Aucun item. Importez un fichier Excel ou ajoutez manuellement.
        </div>
      </div>
    </div>

    <!-- ══ BARRE AJOUT MANUEL ══ -->
    <div v-if="!isLocked" class="qpc-add-bar">
      <button type="button" class="qpc-add-cat" @click="addCategory">
        <i class="ti ti-folder-plus"></i> Ajouter catégorie
      </button>
      <button type="button" class="qpc-add-item" @click="addItem">
        <i class="ti ti-plus"></i> Ajouter item
      </button>
      <div class="qpc-add-spacer"></div>
      <span class="qpc-counter">{{ filledItems }}/{{ totalItems }} items renseignés</span>
      <div class="qpc-pbar-wrap">
        <div class="qpc-pbar-fill" :style="`width:${fillPct}%`"></div>
      </div>
      <span class="qpc-pct">{{ fillPct }}%</span>
    </div>

    <!-- ══ ACTIONS WORKFLOW ══ -->
    <div class="qpc-actions-row">
      <!-- Annuler + Valider (comme la capture) -->
      <div class="qpc-actions-left">
        <button v-if="!isLocked" type="button" class="qpc-btn-cancel" @click="annuler" :disabled="processing">
          <i class="ti ti-circle-minus"></i> Annuler
        </button>
        <button v-if="!isLocked" type="button" class="qpc-btn-validate" @click="submit" :disabled="processing">
          <i v-if="!processing" class="ti ti-circle-check"></i>
          <i v-else class="ti ti-loader-2 qpc-spin"></i>
          {{ processing ? 'En cours…' : (form.id ? 'Mettre à jour' : 'Valider') }}
        </button>
      </div>
      <!-- Workflow -->
      <div class="qpc-actions-right">
        <button v-if="pdc.id&&pdc.validation_status==='draft'" type="button"
          class="qpc-btn-sub" @click="soumettre" :disabled="processing">
          <i class="ti ti-send"></i> Soumettre
        </button>
        <template v-if="canManage&&pdc.validation_status==='in_review'">
          <button type="button" class="qpc-btn-ok" @click="valider('validated')" :disabled="processing">
            <i class="ti ti-circle-check"></i> Valider
          </button>
          <button type="button" class="qpc-btn-rej" @click="valider('rejected')" :disabled="processing">
            <i class="ti ti-circle-x"></i> Rejeter
          </button>
        </template>
        <a v-if="pdc.id&&(pdc.validation_status==='validated'||canManage)"
           :href="`${props.formUrl}/${pdc.id}/pdf?download=1`" target="_blank"
           class="qpc-btn-pdf">
          <i class="ti ti-file-type-pdf"></i> PDF
        </a>
      </div>
    </div>

    <!-- ══ LISTE QPC (grille en bas comme la capture) ══ -->
    <div class="qpc-list-wrap">
      <div class="qpc-list-thead">
        <div class="qpc-lth"><i class="ti ti-search qpc-sico2"></i></div>
        <div class="qpc-lth">Intitulé questionnaire <i class="ti ti-search qpc-sico2"></i></div>
        <div class="qpc-lth">Code Mission</div>
        <div class="qpc-lth">Mission <i class="ti ti-search qpc-sico2"></i> <i class="ti ti-chevron-right qpc-sico2"></i></div>
      </div>
      <div class="qpc-list-body">
        <div v-if="pdcList.length===0" class="qpc-list-empty">Aucun questionnaire enregistré</div>
        <div v-for="item in pdcList" :key="item.id"
          class="qpc-list-row" :class="{active:pdc.id===item.id}"
          @click="loadPdc(item)">
          <div class="qpc-ltd">
            <span class="qpc-ltd-code">{{ item.code }}</span>
          </div>
          <div class="qpc-ltd">{{ item.intitule_qpc||'—' }}</div>
          <div class="qpc-ltd">{{ item.code_mission_ref||mission?.code_mission||'—' }}</div>
          <div class="qpc-ltd">{{ mission?.libelle||'—' }}</div>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <Teleport to="body">
      <transition name="fade-up">
        <div v-if="toast.show" class="qpc-toast" :class="`t-${toast.type}`">
          <i :class="toast.type==='ok'?'ti ti-circle-check':'ti ti-alert-circle'"></i>
          {{ toast.msg }}
        </div>
      </transition>
    </Teleport>

  </div>
  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import VerticalLayoutAudit from '@/layouts/VerticalLayout.vue'

const props = defineProps({
  mission:{type:Object,default:null},
  assignment:{type:Object,default:null},
  form:{type:Object,default:null},
  pdcList:{type:Array,default:()=>[]},
  errors:{type:Object,default:()=>({})},
  auditorRole:{type:String,default:null},
  missionId:{type:Number,default:null},
  assignmentId:{type:Number,default:null},
  currentAuditor:{type:Object,default:null},
  backUrl:{type:String,default:''},
  formUrl:{type:String,default:''},
})

const canManage  = computed(()=>['DM','CM'].includes(props.auditorRole??''))
const pdc        = reactive<any>(props.form?{...props.form}:{})
const isLocked   = computed(()=>pdc.validation_status==='validated'||(pdc.validation_status==='in_review'&&!canManage.value))
const currentName= computed(()=>{const a=props.currentAuditor as any;if(!a)return '';return [a.last_name,a.first_name].filter(Boolean).join(' ').trim()||a.audit_code||''})
const safeJson   = (v:any)=>{try{return JSON.parse(v??'[]')}catch{return[]}}

const form = reactive({
  id:           props.form?.id??null,
  code:         props.form?.code??'',
  entite_auditee:props.form?.entite_auditee??'',
  intitule_qpc: props.form?.intitule_qpc??'',
  fait_par:     props.form?.fait_par||currentName.value,
  revue_par:    props.form?.revue_par??'',
  date_fait:    props.form?.date_fait??'',
  date_revue:   props.form?.date_revue??'',
})

const qpcItems = reactive<any[]>(
  Array.isArray(props.form?.qpc_items)?props.form.qpc_items:safeJson(props.form?.qpc_items)
)
const collapsedCats = reactive<Set<number>>(new Set())

function toggleCat(i:number){collapsedCats.has(i)?collapsedCats.delete(i):collapsedCats.add(i)}
function isCatHidden(idx:number):boolean{
  for(let i=idx-1;i>=0;i--){if(qpcItems[i].type==='cat')return collapsedCats.has(i)}
  return false
}

const totalItems  = computed(()=>qpcItems.filter(x=>x.type==='item').length)
const filledItems = computed(()=>qpcItems.filter(x=>x.type==='item'&&x.libelle?.trim()).length)
const fillPct     = computed(()=>totalItems.value===0?0:Math.round(filledItems.value/totalItems.value*100))

function addCategory(){
  const num=String(qpcItems.filter(x=>x.type==='cat').length+1)
  qpcItems.push({type:'cat',num,libelle:'',code:`CAT-${num.padStart(2,'0')}`})
}
function addItem(){
  let cn=0
  for(let i=qpcItems.length-1;i>=0;i--){if(qpcItems[i].type==='cat'){cn=parseInt(qpcItems[i].num||'1');break}}
  const ic=qpcItems.filter(x=>x.type==='item').length+1
  qpcItems.push({type:'item',code:`I-${String(cn).padStart(2,'0')}-${String(ic).padStart(2,'0')}`,libelle:'',fichier:''})
}
function removeRow(i:number){qpcItems.splice(i,1)}
function onFile(e:Event,item:any){const f=(e.target as HTMLInputElement).files?.[0];if(f)item.fichier=f.name}

// Import Excel
const xlsRef = ref<HTMLInputElement|null>(null)
async function importExcel(e:Event){
  const file=(e.target as HTMLInputElement).files?.[0];if(!file)return
  const fd=new FormData();fd.append('file',file)
  try{
    const res=await axios.post(`${props.formUrl}/import-excel`,fd,{headers:{'Content-Type':'multipart/form-data','X-XSRF-TOKEN':decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1]??'')}})
    const items:any[]=res.data.items??[]
    qpcItems.splice(0,qpcItems.length,...items)
    showToast('ok',`${items.length} lignes importées avec succès`)
  }catch(err:any){showToast('err',err.response?.data?.error??'Erreur lors de l\'import')}
  if(xlsRef.value)xlsRef.value.value=''
}

const processing=ref(false)
const toast=reactive({show:false,type:'ok',msg:''});let tt:any
function showToast(type:'ok'|'err',msg:string){if(tt)clearTimeout(tt);Object.assign(toast,{show:true,type,msg});tt=setTimeout(()=>toast.show=false,3500)}

function annuler(){Object.assign(form,{id:null,code:'',entite_auditee:'',intitule_qpc:'',fait_par:currentName.value,revue_par:'',date_fait:'',date_revue:''});qpcItems.splice(0,qpcItems.length);Object.assign(pdc,{})}

function loadPdc(item:any){router.visit(`${props.formUrl}/${item.id}/edit?mission_id=${props.missionId}&assignment_id=${props.assignmentId}`)}

function submit(){
  if(isLocked.value)return;processing.value=true
  const payload:any={...form,mission_id:props.missionId,assignment_id:props.assignmentId,qpc_items:JSON.stringify(qpcItems)}
  const url=form.id?`${props.formUrl}/${form.id}`:props.formUrl
  const method=form.id?'put':'post'
  router[method](url,payload,{preserveScroll:true,
    onSuccess:(p:any)=>{processing.value=false;const u=p.props?.form;if(u){Object.assign(pdc,u);form.id=u.id;form.code=u.code}showToast('ok',form.id?'QPC mis à jour':'QPC enregistré')},
    onError:()=>{processing.value=false;showToast('err','Erreur — vérifiez les champs')}
  })
}
function soumettre(){processing.value=true;router.post(`${props.formUrl}/${pdc.id}/soumettre`,{mission_id:props.missionId,assignment_id:props.assignmentId},{preserveScroll:true,onSuccess:(p:any)=>{processing.value=false;Object.assign(pdc,p.props?.form??{});showToast('ok','Soumis pour validation')},onError:()=>{processing.value=false;showToast('err','Erreur soumission')}})}
function valider(action:string){const note=action==='rejected'?prompt('Motif du rejet :'):null;if(action==='rejected'&&!note)return;processing.value=true;router.post(`${props.formUrl}/${pdc.id}/valider`,{mission_id:props.missionId,assignment_id:props.assignmentId,action,note},{preserveScroll:true,onSuccess:(p:any)=>{processing.value=false;Object.assign(pdc,p.props?.form??{});showToast('ok',action==='validated'?'QPC validé':'QPC rejeté')},onError:()=>{processing.value=false;showToast('err','Erreur validation')}})}
</script>

<style scoped>
/* ══ BASE ══ */
.qpc-wrap{display:flex;flex-direction:column;gap:0;background:#f4f6fb;min-height:100vh;padding:12px 16px 24px;font-family:'Segoe UI',Arial,sans-serif;font-size:13px;color:#1a1a2e}

/* ══ SECTION BORDER (comme l'app Windows) ══ */
.qpc-section-border{position:relative;border:1.5px solid #1E5799;border-radius:4px;padding:10px 14px 10px;margin-bottom:0}
.qpc-mt{margin-top:10px}
.qpc-section-label{position:absolute;top:-9px;left:10px;background:#f4f6fb;padding:0 6px;font-size:11px;font-weight:700;color:#1E5799;letter-spacing:.03em}

/* ── Entité ── */
.qpc-entity-row{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.qpc-ef{display:flex;align-items:center;gap:6px}
.qpc-ef-wide{flex:1}
.qpc-el{font-size:11px;color:#555;white-space:nowrap;min-width:38px}
.qpc-ei{height:24px;border:1px solid #d1d5db;border-radius:3px;padding:0 8px;font-size:12px;color:#1a1a2e;background:#f8f9fb;outline:none;flex:1;min-width:80px}
.qpc-ei[readonly]{background:#f0f3f8;color:#666}

/* ── Info questionnaire ── */
.qpc-info-row,.qpc-fp-row{display:flex;align-items:center;gap:6px;margin-bottom:5px;flex-wrap:wrap}
.qpc-il{font-size:12px;color:#555;white-space:nowrap;min-width:54px}
.qpc-il-date{min-width:30px}
.qpc-ii{height:24px;border:1px solid #d1d5db;border-radius:3px;padding:0 8px;font-size:12px;color:#1a1a2e;background:#f8f9fb;outline:none}
.qpc-ii:disabled{background:#f0f3f8;color:#888;cursor:not-allowed}
.qpc-ii-code{width:160px;font-family:monospace;font-weight:700;color:#1E5799;background:#eff6ff}
.qpc-ii-mission{flex:1;min-width:120px}
.qpc-ii-fp{width:200px}
.qpc-ii-date{width:130px}

/* ── Intitulé ── */
.qpc-intitule-row{display:flex;align-items:center;gap:8px;margin-top:10px;flex-wrap:wrap}
.qpc-ii-intitule{flex:1;height:26px;font-size:13px;font-weight:600;color:#1a1a2e;background:#fff;border:1px solid #d1d5db;border-radius:3px;padding:0 10px;outline:none;min-width:200px}
.qpc-btn-import{display:inline-flex;align-items:center;gap:5px;height:28px;padding:0 14px;background:#1E5799;color:#fff;border-radius:4px;font-size:12px;font-weight:700;cursor:pointer;border:none;white-space:nowrap;transition:background .15s}
.qpc-btn-import:hover{background:#174a87}
.qpc-fhid{display:none}
.qpc-btn-tpl{display:inline-flex;align-items:center;gap:5px;height:28px;padding:0 12px;background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;white-space:nowrap;transition:background .15s}
.qpc-btn-tpl:hover{background:#dcfce7}

/* ══ TABLEAU ══ */
.qpc-table-wrap{margin-top:8px;border:1px solid #c8d6e8;border-radius:4px;overflow:hidden}
.qpc-thead{display:flex;background:#1E5799}
.qpc-th{padding:7px 10px;font-size:11px;font-weight:700;color:#fff;display:flex;align-items:center;gap:4px;text-transform:none;letter-spacing:.01em}
.qpc-th-code{width:180px;flex-shrink:0}
.qpc-th-lib{flex:1}
.qpc-th-file{width:200px;flex-shrink:0}
.qpc-th-act{width:30px;flex-shrink:0}
.qpc-sico{font-size:10px;opacity:.8}

.qpc-tbody{display:flex;flex-direction:column}

/* Ligne catégorie */
.qpc-row-cat{display:flex;align-items:center;min-height:32px;background:#2B7FD4;border-bottom:1px solid #1E5799}
.qpc-row-cat:hover{background:#2471C2}
.qpc-td-code{width:180px;flex-shrink:0;padding:4px 8px;display:flex;align-items:center;gap:4px}
.qpc-td-lib{flex:1;padding:4px 8px;display:flex;align-items:center}
.qpc-td-file{width:200px;flex-shrink:0;padding:4px 8px;display:flex;align-items:center;gap:4px}
.qpc-td-act{width:30px;flex-shrink:0;display:flex;align-items:center;justify-content:center}

.qpc-cat-toggle{background:none;border:none;color:#fff;cursor:pointer;padding:0;width:18px;height:18px;display:flex;align-items:center;justify-content:center;font-size:11px;opacity:.85;flex-shrink:0}
.qpc-cat-folder{color:#FFD700;font-size:14px;flex-shrink:0}
.qpc-cat-num{color:#fff;font-weight:800;font-size:13px}
.qpc-cat-lib-inp{flex:1;background:transparent;border:none;border-bottom:1px solid rgba(255,255,255,.4);color:#fff;font-size:13px;font-weight:700;outline:none;padding:2px 0}
.qpc-cat-lib-inp::placeholder{color:rgba(255,255,255,.55)}
.qpc-cat-lib-ro{color:#fff;font-size:13px;font-weight:700}

/* Ligne item */
.qpc-row-item{display:flex;align-items:center;min-height:28px;background:#fff;border-bottom:.5px solid #e8edf5}
.qpc-row-item.alt{background:#f5f8fc}
.qpc-row-item:hover{background:#edf3fc}
.qpc-item-ico{color:#94a3b8;font-size:12px;margin-right:5px;flex-shrink:0}
.qpc-item-code-inp{width:130px;border:none;border-bottom:1px dashed #d1d5db;font-size:11px;color:#1E5799;font-weight:600;background:transparent;outline:none;padding:1px 2px}
.qpc-item-code-inp:focus{border-bottom-color:#1E5799}
.qpc-item-code-ro{font-size:11px;color:#1E5799;font-weight:600}
.qpc-item-lib-inp{flex:1;border:none;font-size:12px;color:#1a1a2e;background:transparent;outline:none;padding:2px 4px}
.qpc-item-lib-inp:focus{background:#f0f6ff;border-radius:2px}
.qpc-item-lib-ro{font-size:12px;color:#1a1a2e}
.qpc-attach-lbl{cursor:pointer;font-size:13px;color:#1E5799;flex-shrink:0;padding:2px 4px}
.qpc-attach-name{font-size:11px;color:#555;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:155px}
.qpc-del{background:none;border:none;color:#94a3b8;cursor:pointer;width:22px;height:22px;display:flex;align-items:center;justify-content:center;font-size:11px;border-radius:3px}
.qpc-del:hover{background:#fef2f2;color:#ef4444}
.qpc-empty{padding:28px;text-align:center;color:#94a3b8;font-size:12px;display:flex;flex-direction:column;align-items:center;gap:10px;background:#fff}
.qpc-empty i{font-size:28px}

/* ══ BARRE AJOUT ══ */
.qpc-add-bar{display:flex;align-items:center;gap:8px;padding:8px 0;margin-top:6px;flex-wrap:wrap}
.qpc-add-cat,.qpc-add-item{display:inline-flex;align-items:center;gap:4px;height:26px;padding:0 12px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid;transition:all .15s}
.qpc-add-cat{background:#eff6ff;color:#1E5799;border-color:#bfdbfe}
.qpc-add-cat:hover{background:#dbeafe}
.qpc-add-item{background:#1e293b;color:#fff;border-color:#1e293b}
.qpc-add-item:hover{background:#0f172a}
.qpc-add-spacer{flex:1}
.qpc-counter{font-size:11px;color:#64748b}
.qpc-pbar-wrap{width:100px;height:6px;background:#e2e8f0;border-radius:3px;overflow:hidden}
.qpc-pbar-fill{height:100%;background:#1E5799;transition:width .3s ease;border-radius:3px}
.qpc-pct{font-size:11px;font-weight:700;color:#1E5799}

/* ══ ACTIONS ══ */
.qpc-actions-row{display:flex;align-items:center;justify-content:space-between;margin-top:10px;gap:8px;flex-wrap:wrap}
.qpc-actions-left,.qpc-actions-right{display:flex;align-items:center;gap:8px}
.qpc-btn-cancel,.qpc-btn-validate,.qpc-btn-sub,.qpc-btn-ok,.qpc-btn-rej,.qpc-btn-pdf{display:inline-flex;align-items:center;gap:5px;height:30px;padding:0 16px;border-radius:4px;font-size:12px;font-weight:700;cursor:pointer;border:none;transition:all .15s}
.qpc-btn-cancel{background:#e2e8f0;color:#1a1a2e}
.qpc-btn-cancel:hover{background:#cbd5e1}
.qpc-btn-validate{background:#1E5799;color:#fff}
.qpc-btn-validate:hover{background:#174a87}
.qpc-btn-sub{background:#2563eb;color:#fff}
.qpc-btn-sub:hover{background:#1d4ed8}
.qpc-btn-ok{background:#16a34a;color:#fff}
.qpc-btn-ok:hover{background:#15803d}
.qpc-btn-rej{background:#dc2626;color:#fff}
.qpc-btn-rej:hover{background:#b91c1c}
.qpc-btn-pdf{background:#7c3aed;color:#fff;text-decoration:none}
.qpc-btn-pdf:hover{background:#6d28d9}
.qpc-btn-cancel:disabled,.qpc-btn-validate:disabled,.qpc-btn-sub:disabled{opacity:.55;cursor:not-allowed}

/* ══ LISTE QPC ══ */
.qpc-list-wrap{margin-top:12px;border:1px solid #c8d6e8;border-radius:4px;overflow:hidden}
.qpc-list-thead{display:flex;background:#1E5799}
.qpc-lth{flex:1;padding:6px 10px;font-size:11px;font-weight:700;color:#fff;display:flex;align-items:center;gap:4px}
.qpc-lth:first-child{flex:0 0 200px}
.qpc-sico2{font-size:10px;opacity:.8}
.qpc-list-body{background:#fff}
.qpc-list-empty{padding:16px;text-align:center;color:#94a3b8;font-size:12px}
.qpc-list-row{display:flex;border-bottom:.5px solid #e8edf5;cursor:pointer;transition:background .12s}
.qpc-list-row:hover{background:#edf3fc}
.qpc-list-row.active{background:#eff6ff;border-left:3px solid #1E5799}
.qpc-ltd{flex:1;padding:7px 10px;font-size:12px;color:#1a1a2e;display:flex;align-items:center}
.qpc-ltd:first-child{flex:0 0 200px}
.qpc-ltd-code{font-family:monospace;font-size:11px;font-weight:700;color:#1E5799;background:#eff6ff;padding:1px 6px;border-radius:3px}

/* ══ TOAST ══ */
.qpc-toast{position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;align-items:center;gap:8px;padding:10px 16px;border-radius:6px;font-size:12px;font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.12)}
.t-ok{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0}
.t-err{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
.fade-up-enter-active,.fade-up-leave-active{transition:all .25s ease}
.fade-up-enter-from,.fade-up-leave-to{opacity:0;transform:translateY(10px)}
.qpc-spin{animation:spin .7s linear infinite;display:inline-block}
@keyframes spin{to{transform:rotate(360deg)}}
</style>