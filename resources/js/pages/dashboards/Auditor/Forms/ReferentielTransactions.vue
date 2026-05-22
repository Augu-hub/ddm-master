<template>
  <VerticalLayoutAudit>
    <div class="rct-root">

      <!-- ── TOPBAR ── -->
      <header class="topbar">
        <div class="topbar-l">
          <a :href="backUrl" class="back-btn"><IconArrow /></a>
          <div class="brand">
            <span class="brand-tag">RCT</span>
            <div>
              <div class="brand-title">Référentiel de Contrôle des Transactions Financières</div>
              <div class="brand-sub">{{ mission?.code_mission }} — {{ mission?.libelle }}</div>
            </div>
          </div>
        </div>
        <div class="topbar-r">
          <template v-if="currentForm">
            <span class="status-chip" :class="`s-${currentForm.validation_status}`">
              <span class="dot"></span>{{ STATUS_LABELS[currentForm.validation_status] ?? 'Brouillon' }}
            </span>
            <span class="code-chip">{{ currentForm.code }}</span>
          </template>
          <div class="user-pill" :class="`role-${currentAuditor.role}`">
            <span class="avatar">{{ initials }}</span>
            <span class="uname">{{ currentAuditor.last_name }} {{ currentAuditor.first_name }}</span>
            <span class="urole">{{ ROLE_LABELS[currentAuditor.role] }}</span>
          </div>
        </div>
      </header>

      <!-- ════ VUE LISTE ════ -->
      <div v-if="view === 'list'" class="view-list">
        <div class="list-hero">
          <div><h2>Référentiels de contrôle des transactions</h2><p>{{ rctList.length }} référentiel(s)</p></div>
          <button v-if="canManage" class="btn btn-primary" @click="view='create'"><IconPlus /> Nouveau RCT</button>
        </div>
        <div v-if="!rctList.length" class="empty-state">
          <div class="empty-ico">💰</div>
          <div v-if="canManage"><h3>Aucun référentiel créé</h3><p>Créez le RCT et affectez les auditeurs par catégorie.</p><button class="btn btn-primary mt" @click="view='create'"><IconPlus /> Créer</button></div>
          <div v-else><h3>En attente de création</h3><p>Le DM ou CM doit créer le référentiel.</p></div>
        </div>
        <div v-else class="rct-cards">
          <div v-for="r in rctList" :key="r.id" class="rct-card" @click="openEdit(r.id)">
            <span class="rct-code">{{ r.code }}</span>
            <span class="status-chip sm" :class="`s-${r.validation_status}`"><span class="dot"></span>{{ STATUS_LABELS[r.validation_status]??'Brouillon' }}</span>
            <span class="rct-date">{{ fmt(r.updated_at) }}</span><IconChevron />
          </div>
        </div>
      </div>

      <!-- ════ VUE CRÉATION ════ -->
      <div v-else-if="view === 'create'" class="view-create">
        <div class="create-shell">
          <div class="steps-bar">
            <div class="step" :class="{active:step===1,done:step>1}"><span class="snum">{{step>1?'✓':'1'}}</span> Informations</div>
            <div class="step-line"></div>
            <div class="step" :class="{active:step===2}"><span class="snum">2</span> Affectations par catégorie</div>
          </div>

          <div v-if="step===1" class="create-card">
            <h3 class="card-title">💰 Identification du RCT</h3>
            <div class="fg2">
              <div class="field"><label>Fait par</label><input v-model="draft.fait_par" class="inp" placeholder="Rédacteur" /></div>
              <div class="field"><label>Revue par</label><input v-model="draft.revue_par" class="inp" placeholder="Relecteur" /></div>
              <div class="field"><label>Entité auditée</label><input v-model="draft.entite_auditee" class="inp" placeholder="Ex : Direction des Finances" /></div>
              <div class="field"><label>Exercice budgétaire</label><input v-model="draft.exercice_budgetaire" class="inp" placeholder="Ex : 2025" /></div>
              <div class="field fg-full"><label>Période de contrôle</label><input v-model="draft.periode_controle" class="inp" placeholder="Ex : Janvier – Juin 2025" /></div>
            </div>
            <div class="step-actions">
              <button class="btn btn-ghost" @click="view='list'">Annuler</button>
              <button class="btn btn-primary" @click="step=2">Suivant <IconChevron /></button>
            </div>
          </div>

          <div v-else-if="step===2" class="create-card">
            <h3 class="card-title">👥 Affecter un auditeur par catégorie</h3>
            <p class="card-hint">Les critères de chaque catégorie seront pré-assignés. L'auditeur remplira les preuves pour ses critères.</p>
            <div class="affect-table">
              <div v-for="(cat, code) in categories" :key="code" class="affect-row">
                <div class="cat-info">
                  <span class="cat-icon" :style="`color:${cat.color}`">{{ cat.icon }}</span>
                  <div><div class="cat-code">{{ code }}</div><div class="cat-label">{{ cat.label }}</div></div>
                </div>
                <select v-model="draft.affectations[code]" class="inp sel">
                  <option value="">— Non affecté —</option>
                  <option v-for="a in phaseAuditeurs" :key="a.id" :value="a.id">{{ a.full_name }} · {{ ROLE_LABELS[a.role_code]??a.role_code }}</option>
                </select>
              </div>
            </div>
            <div class="step-actions">
              <button class="btn btn-ghost" @click="step=1"><IconArrow /> Précédent</button>
              <button class="btn btn-primary" :disabled="creating" @click="submitCreate">
                <span v-if="creating" class="spin"></span><IconCheck v-else /> Créer le RCT ({{ totalCriteres }} critères)
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ════ VUE ÉDITION ════ -->
      <template v-else-if="view==='edit' && currentForm">
        <div class="edit-layout">

          <!-- ── SIDEBAR CATÉGORIES ── -->
          <aside class="sidebar">
            <div class="sidebar-head">
              <span class="sidebar-title">Domaines de Transaction</span>
              <button v-if="canManage && !isLocked" class="btn-icon-sm" @click="openAffectModal" title="Gérer affectations">👥</button>
            </div>
            <div class="cat-list">
              <div v-for="(cat, code) in localCategories" :key="code"
                class="cat-item" :class="{'cat-active':activeCat===code,'cat-mine':myCategories.includes(code)}"
                :style="{'--cc':cat.color}" @click="activeCat=code">
                <span class="cat-icon-s" :style="`color:${cat.color}`">{{ cat.icon }}</span>
                <div class="cat-info-s">
                  <div class="cat-code">{{ code }}</div>
                  <div class="cat-label-s">{{ cat.label }}</div>
                  <div class="cat-aud" v-if="cat.auditeur_id">
                    <span class="role-pill sm" :class="`rp-${getAuditorRole(cat.auditeur_id)}`">{{ getAuditorRole(cat.auditeur_id) }}</span>
                    {{ getAuditorName(cat.auditeur_id) }}
                  </div>
                  <div class="cat-aud muted-sm" v-else>Non affecté</div>
                </div>
                <div class="cat-count" v-if="criteresByCategory[code]?.length">{{ criteresByCategory[code].length }}</div>
              </div>
            </div>
            <div class="sidebar-foot" v-if="currentForm">
              <div class="info-row"><span>Entité</span><strong>{{ currentForm.entite_auditee||'—' }}</strong></div>
              <div class="info-row"><span>Exercice</span><strong>{{ currentForm.exercice_budgetaire||'—' }}</strong></div>
              <div class="info-row"><span>Période</span><strong>{{ currentForm.periode_controle||'—' }}</strong></div>
              <div class="info-row"><span>Total critères</span><strong>{{ criteres.length }}</strong></div>
            </div>
          </aside>

          <!-- ── CONTENU CATÉGORIE ACTIVE ── -->
          <div class="main-content">
            <template v-if="activeCatData">
              <div class="cat-header" :style="`border-left-color:${activeCatData.color}`">
                <div class="cat-header-l">
                  <span class="cat-big-icon" :style="`color:${activeCatData.color}`">{{ activeCatData.icon }}</span>
                  <div>
                    <div class="cat-big-label">{{ activeCatData.label }}</div>
                    <div class="cat-big-sub">
                      <template v-if="activeCatData.auditeur_id">
                        Affecté à : <strong>{{ getAuditorName(activeCatData.auditeur_id) }}</strong>
                        <span class="role-pill" :class="`rp-${getAuditorRole(activeCatData.auditeur_id)}`">{{ getAuditorRole(activeCatData.auditeur_id) }}</span>
                      </template>
                      <span v-else class="muted-sm">Non affecté</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Tableau — critères en lecture seule, saisies éditables -->
              <div class="tbl-wrap" v-if="criteresByCategory[activeCat]?.length">
                <table class="rct-tbl">
                  <thead>
                    <tr class="th-row">
                      <th class="col-ref">Réf.<br>Contrôle</th>
                      <th class="col-art">Référence<br>Réglementaire</th>
                      <th class="col-proc">Intitulé de<br>la Procédure</th>
                      <th class="col-point">Point de Contrôle /<br>Exigence</th>
                      <th class="col-preuves">Preuves du<br>Contrôle</th>
                      <th class="col-resp">Responsable<br>du contrôle</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="c in criteresByCategory[activeCat]" :key="c.ref_controle" class="tbl-row"
                      :class="{'row-mine':isMine(c)}">

                      <!-- Ref — lecture seule depuis référentiel -->
                      <td class="td-ref">
                        <span class="ref-badge" :style="`background:${activeCatData.color}18;color:${activeCatData.color}`">{{ c.ref_controle }}</span>
                      </td>

                      <!-- Art — lecture seule -->
                      <td class="td-art"><code class="art-code">{{ c.ref_reglementaire||'—' }}</code></td>

                      <!-- Intitulé — lecture seule -->
                      <td class="td-proc"><div class="cell-ro">{{ c.intitule_procedure||'—' }}</div></td>

                      <!-- Point — lecture seule -->
                      <td class="td-point"><div class="cell-ro">{{ c.point_controle||'—' }}</div></td>

                      <!-- Preuves — saisie éditable -->
                      <td class="td-preuves">
                        <div class="preuves-cell">
                          <div v-if="c.preuves_fichiers?.length" class="fichiers-list">
                            <div v-for="f in c.preuves_fichiers" :key="f.path" class="fchip" :class="mimeClass(f)">
                              <span class="fchip-icon">{{ mimeIcon(f) }}</span>
                              <a :href="f.url" target="_blank" class="fchip-name" :title="f.name">{{ trunc(f.name,18) }}</a>
                              <span class="fchip-size">{{ fmtSize(f.size) }}</span>
                              <button v-if="canEditSaisie(c)&&!isLocked" class="fchip-del" @click="deletePreuve(c,f)">×</button>
                            </div>
                          </div>
                          <div v-if="canEditSaisie(c)&&!isLocked" class="upload-zone"
                            :class="{'drag-over':dragOver[c.ref_controle]}"
                            @dragover.prevent="dragOver[c.ref_controle]=true"
                            @dragleave="dragOver[c.ref_controle]=false"
                            @drop.prevent="onDrop($event,c)">
                            <span v-if="uploadingFor===c.ref_controle" class="spin-sm"></span>
                            <template v-else>
                              <label :for="`fu-${c.ref_controle}`" class="upload-label">
                                <span class="upload-ico">📎</span><span>Joindre un fichier</span>
                                <span class="upload-hint">pdf · xlsx · docx · jpg</span>
                              </label>
                              <input :id="`fu-${c.ref_controle}`" type="file" multiple
                                accept=".pdf,.xlsx,.xls,.docx,.doc,.png,.jpg,.jpeg"
                                style="display:none" @change="onFileChange($event,c)" />
                            </template>
                          </div>
                          <span v-else-if="!c.preuves_fichiers?.length" class="muted-sm">Aucune pièce</span>
                          <textarea v-if="canEditSaisie(c)" v-model="c.note_preuves" class="cel-area note-area" rows="2"
                            placeholder="Note sur les preuves…" @change="saveSaisie(c)"></textarea>
                          <div v-else-if="c.note_preuves" class="cell-ro note-ro">{{ c.note_preuves }}</div>
                        </div>
                      </td>

                      <!-- Responsable — lecture seule -->
                      <td class="td-resp">
                        <div class="resp-ro">
                          <span class="resp-name"> </span>
                          <span v-if="c.auditeur_id" class="role-pill sm" :class="`rp-${getAuditorRole(c.auditeur_id)}`">{{ getAuditorRole(c.auditeur_id) }}</span>
                          <input type="button" value="— Non affecté —" class="inp sel" disabled v-if="!c.auditeur_id" >
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div v-else class="cat-empty">
                <div class="empty-ico sm">📄</div>
                <p class="muted">Aucun critère dans le référentiel pour cette catégorie.</p>
              </div>
            </template>
          </div>
        </div>

        <!-- Footer -->
        <footer class="edit-footer">
          <div class="ef-l"><button class="btn btn-ghost" @click="view='list'">← Liste</button></div>
          <div class="ef-c"><span class="totals">{{ Object.keys(categories).length }} catégories · {{ criteres.length }} critère(s)</span></div>
          <div class="ef-r">
            <button v-if="currentForm.validation_status==='draft'" class="btn btn-submit" @click="submit"><IconSend /> Soumettre</button>
            <template v-if="canManage && currentForm.validation_status==='in_review'">
              <button class="btn btn-validate" @click="validate"><IconCheck /> Valider</button>
              <button class="btn btn-reject" @click="reject">✕ Rejeter</button>
            </template>
            <div v-if="isLocked" class="locked-pill">🔒 Validé</div>
          </div>
        </footer>
      </template>

      <!-- Modal affectations catégories -->
      <Teleport to="body">
        <div v-if="affectModal" class="modal-bg" @click.self="affectModal=false">
          <div class="modal">
            <div class="modal-head"><span>👥 Affectations par catégorie</span><button class="modal-x" @click="affectModal=false">×</button></div>
            <div class="modal-body">
              <div v-for="(cat, code) in localCategories" :key="code" class="affect-row">
                <div class="cat-info">
                  <span class="cat-icon" :style="`color:${cat.color}`">{{ cat.icon }}</span>
                  <div><div class="cat-code">{{ code }}</div><div class="cat-label">{{ cat.label }}</div></div>
                </div>
                <select v-model="tmpAffect[code]" class="inp sel">
                  <option :value="null">— Non affecté —</option>
                  <option v-for="a in phaseAuditeurs" :key="a.id" :value="a.id">{{ a.full_name }} · {{ ROLE_LABELS[a.role_code]??a.role_code }}</option>
                </select>
              </div>
            </div>
            <div class="modal-foot">
              <button class="btn btn-ghost" @click="affectModal=false">Annuler</button>
              <button class="btn btn-primary" :disabled="affectSaving" @click="saveAffectations">
                <span v-if="affectSaving" class="spin"></span><IconCheck v-else /> Enregistrer
              </button>
            </div>
          </div>
        </div>
      </Teleport>

      <Teleport to="body">
        <Transition name="t">
          <div v-if="toast.show" class="toast" :class="`toast-${toast.type}`">{{ toast.message }}</div>
        </Transition>
      </Teleport>
    </div>
  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'

const IconArrow   = { template: `<svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>` }
const IconChevron = { template: `<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>` }
const IconPlus    = { template: `<svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>` }
const IconCheck   = { template: `<svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>` }
const IconSend    = { template: `<svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>` }

interface Fichier {name:string;path:string;url:string;size:number;mime:string;uploaded_at:string;uploaded_by:number}
interface Category {label:string;icon:string;color:string;auditeur_id:number|null}
interface Critere {categorie_code:string;ref_controle:string;ref_reglementaire:string;intitule_procedure:string;point_controle:string;preuves_attendues:string;saisie_id:number|null;note_preuves:string;preuves_fichiers:Fichier[];auditeur_id:number|null}

const props = defineProps<{
  form:any|null; categories:Record<string,Category>; criteres:Critere[]; myCategories:string[]
  phaseAuditeurs:any[]; rctList:any[]; mission:any
  currentAuditor:{id:number;last_name:string;first_name:string;role:string}
  canManage:boolean; backUrl:string; missionId:number; assignmentId:number
  urlStore:string; urlUpdate:string|null; urlSoumettre:string|null; urlValider:string|null
  urlUpdateCats:string|null; urlSaveSaisie:string|null; urlUploadPreuve:string|null; urlDeletePreuve:string|null
}>()

const STATUS_LABELS:Record<string,string>={draft:'Brouillon',in_review:'En révision',validated:'Validé'}
const ROLE_LABELS:Record<string,string>={DM:'Dir. Mission',CM:'Chef Mission',AS:'Aud. Senior',AJ:'Aud. Junior'}

const view        = ref<'list'|'create'|'edit'>(props.form?'edit':'list')
const step        = ref(1)
const currentForm = ref<any>(props.form)
const creating    = ref(false)
const saving      = ref(false)
const affectModal = ref(false)
const affectSaving= ref(false)
const uploadingFor= ref<string|null>(null)
const dragOver    = reactive<Record<string,boolean>>({})
const toast       = ref({show:false,type:'ok',message:''})
const activeCat   = ref<string>(props.myCategories[0]??Object.keys(props.categories)[0])
const localCategories = reactive<Record<string,Category>>({...props.categories})
const criteres    = reactive<Critere[]>(props.criteres.map(c=>({...c,preuves_fichiers:[...(c.preuves_fichiers??[])]})))
const tmpAffect   = reactive<Record<string,any>>(Object.fromEntries(Object.keys(props.categories).map(k=>[k,props.categories[k].auditeur_id??null])))
const draft = reactive({fait_par:'',revue_par:'',entite_auditee:'',exercice_budgetaire:'',periode_controle:'',affectations:Object.fromEntries(Object.keys(props.categories).map(k=>[k,''])) as Record<string,any>})

const initials    = computed(()=>((props.currentAuditor.last_name?.[0]??'?')+(props.currentAuditor.first_name?.[0]??'?')).toUpperCase())
const isLocked    = computed(()=>currentForm.value?.validation_status==='validated')
const totalCriteres = computed(()=>props.criteres.length)
const activeCatData = computed(()=>localCategories[activeCat.value]??null)
const criteresByCategory = computed(()=>{const m:Record<string,Critere[]>={};for(const c of criteres){if(!m[c.categorie_code])m[c.categorie_code]=[];m[c.categorie_code].push(c)}return m})

function canEditSaisie(c:Critere){return !isLocked.value&&(props.canManage||c.auditeur_id===props.currentAuditor.id)}
function isMine(c:Critere){return c.auditeur_id===props.currentAuditor.id}
function getAuditorName(id:any){return id?props.phaseAuditeurs.find(a=>a.id===Number(id))?.full_name??'—':''}
function getAuditorRole(id:any){return id?props.phaseAuditeurs.find(a=>a.id===Number(id))?.role_code??''  :''}
function fmt(d:string){try{return new Date(d).toLocaleDateString('fr-FR')}catch{return d}}
function trunc(s:string,n:number){return s.length>n?s.slice(0,n)+'…':s}
function fmtSize(b:number){return b>1048576?(b/1048576).toFixed(1)+'Mo':(b/1024).toFixed(0)+'Ko'}
function mimeIcon(f:Fichier){if(/pdf/i.test(f.mime))return'📕';if(/xlsx?|spreadsheet/i.test(f.mime)||/\.xlsx?$/.test(f.name))return'📊';if(/docx?|word/i.test(f.mime)||/\.docx?$/.test(f.name))return'📄';if(/image/i.test(f.mime))return'🖼';return'📎'}
function mimeClass(f:Fichier){if(/pdf/i.test(f.mime))return'fchip-pdf';if(/xlsx?|spreadsheet/i.test(f.mime)||/\.xlsx?$/.test(f.name))return'fchip-xl';if(/docx?|word/i.test(f.mime)||/\.docx?$/.test(f.name))return'fchip-doc';if(/image/i.test(f.mime))return'fchip-img';return'fchip-other'}
function showToast(type:'ok'|'err',msg:string){toast.value={show:true,type,message:msg};setTimeout(()=>{toast.value.show=false},3200)}
function csrf(){return(document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content??''}
function openEdit(id:number){if(props.form?.id===id){view.value='edit';return}router.visit(window.location.pathname+`?rct_id=${id}`,{preserveState:false})}
function openAffectModal(){Object.keys(localCategories).forEach(k=>{tmpAffect[k]=localCategories[k].auditeur_id??null});affectModal.value=true}

async function saveSaisie(c:Critere){
  if(!props.urlSaveSaisie)return
  try{await fetch(props.urlSaveSaisie,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({ref_controle:c.ref_controle,note_preuves:c.note_preuves})})}
  catch{showToast('err','Erreur sauvegarde')}
}

async function uploadFile(c:Critere,file:File){
  if(!props.urlUploadPreuve)return; uploadingFor.value=c.ref_controle
  try{
    const fd=new FormData();fd.append('ref_controle',c.ref_controle);fd.append('file',file)
    const res=await fetch(props.urlUploadPreuve,{method:'POST',headers:{'X-CSRF-TOKEN':csrf()},body:fd})
    const data=await res.json()
    if(data.success){c.preuves_fichiers.push(data.fichier);showToast('ok',data.message)}
    else showToast('err',data.error??'Erreur upload')
  }catch{showToast('err','Erreur réseau')}finally{uploadingFor.value=null}
}
function onFileChange(e:Event,c:Critere){Array.from((e.target as HTMLInputElement).files??[]).forEach(f=>uploadFile(c,f));(e.target as HTMLInputElement).value=''}
function onDrop(e:DragEvent,c:Critere){dragOver[c.ref_controle]=false;Array.from(e.dataTransfer?.files??[]).forEach(f=>uploadFile(c,f))}

async function deletePreuve(c:Critere,f:Fichier){
  if(!props.urlDeletePreuve||!confirm(`Supprimer "${f.name}" ?`))return
  try{
    const res=await fetch(props.urlDeletePreuve,{method:'DELETE',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({ref_controle:c.ref_controle,path:f.path})})
    const data=await res.json()
    if(data.success){const i=c.preuves_fichiers.findIndex(x=>x.path===f.path);if(i!==-1)c.preuves_fichiers.splice(i,1);showToast('ok','Fichier supprimé')}
    else showToast('err',data.error??'Erreur')
  }catch{showToast('err','Erreur réseau')}
}

async function submitCreate(){
  creating.value=true
  try{
    const res=await fetch(props.urlStore,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({mission_id:props.missionId,assignment_id:props.assignmentId,fait_par:draft.fait_par,revue_par:draft.revue_par,entite_auditee:draft.entite_auditee,exercice_budgetaire:draft.exercice_budgetaire,periode_controle:draft.periode_controle,cat_affectations:draft.affectations})})
    const data=await res.json()
    if(data.success){showToast('ok','RCT créé !');setTimeout(()=>{if(data.redirect)window.location.href=data.redirect;else router.reload()},500)}
    else showToast('err',data.message??'Erreur')
  }catch{showToast('err','Erreur réseau')}finally{creating.value=false}
}

async function saveAffectations(){
  if(!props.urlUpdateCats)return; affectSaving.value=true
  try{
    const res=await fetch(props.urlUpdateCats,{method:'PUT',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({cat_affectations:tmpAffect})})
    const data=await res.json()
    if(data.success){Object.assign(localCategories,data.categories);affectModal.value=false;showToast('ok','Affectations enregistrées')}
    else showToast('err',data.error??'Erreur')
  }catch{showToast('err','Erreur réseau')}finally{affectSaving.value=false}
}

async function submit(){if(!props.urlSoumettre)return;saving.value=true;try{const res=await fetch(props.urlSoumettre,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({mission_id:props.missionId,assignment_id:props.assignmentId})});const data=await res.json();if(data.success&&currentForm.value){currentForm.value.validation_status='in_review';showToast('ok','Soumis pour validation')}else showToast('err',data.error??'Erreur')}catch{showToast('err','Erreur réseau')}finally{saving.value=false}}
async function validate(){if(!props.urlValider)return;saving.value=true;try{const res=await fetch(props.urlValider,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({mission_id:props.missionId,assignment_id:props.assignmentId,action:'validate'})});const data=await res.json();if(data.success&&currentForm.value){currentForm.value.validation_status='validated';showToast('ok','RCT validé')}else showToast('err',data.error??'Erreur')}catch{showToast('err','Erreur réseau')}finally{saving.value=false}}
async function reject(){const note=prompt('Motif du rejet :');if(!note?.trim())return;saving.value=true;try{const res=await fetch(props.urlValider!,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({mission_id:props.missionId,assignment_id:props.assignmentId,action:'reject',note})});const data=await res.json();if(data.success&&currentForm.value){currentForm.value.validation_status='draft';showToast('ok','RCT rejeté')}else showToast('err',data.error??'Erreur')}catch{showToast('err','Erreur réseau')}finally{saving.value=false}}
</script>

<style scoped>
.rct-root{font-family:'Geist','Inter',system-ui,sans-serif;min-height:100vh;background:#f0f4f8;display:flex;flex-direction:column;--navy:#0f172a;--slate:#475569;--border:#e2e8f0;--green:#15803d;--red:#dc2626}
.topbar{position:sticky;top:0;z-index:100;height:52px;background:#0f172a;display:flex;align-items:center;justify-content:space-between;padding:0 16px;box-shadow:0 2px 10px rgba(0,0,0,.3)}
.topbar-l{display:flex;align-items:center;gap:10px;min-width:0}.topbar-r{display:flex;align-items:center;gap:8px;flex-shrink:0}
.back-btn{width:28px;height:28px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.12);border-radius:7px;color:#94a3b8;text-decoration:none;flex-shrink:0;transition:all .15s}.back-btn:hover{background:rgba(255,255,255,.08);color:#fff}
.brand{display:flex;align-items:center;gap:8px;min-width:0}
.brand-tag{font-size:10px;font-weight:700;letter-spacing:.08em;background:#059669;color:#fff;padding:2px 7px;border-radius:5px;flex-shrink:0}
.brand-title{font-size:12px;font-weight:600;color:#f1f5f9;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.brand-sub{font-size:10px;color:#64748b}
.status-chip{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:20px;font-size:10px;font-weight:600}.status-chip.sm{padding:2px 7px;font-size:9px}
.s-draft{background:rgba(100,116,139,.2);color:#94a3b8}.s-in_review{background:rgba(29,78,216,.2);color:#93c5fd;border:1px solid rgba(29,78,216,.3)}.s-validated{background:rgba(21,128,61,.2);color:#86efac;border:1px solid rgba(21,128,61,.3)}
.dot{width:5px;height:5px;border-radius:50%;background:currentColor}
.code-chip{font-size:10px;font-family:monospace;background:rgba(255,255,255,.08);color:#94a3b8;padding:3px 8px;border-radius:5px}
.user-pill{display:flex;align-items:center;gap:6px}.avatar{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;flex-shrink:0}
.role-DM .avatar{background:#7e22ce}.role-CM .avatar{background:#0369a1}.role-AS .avatar,.role-AJ .avatar{background:#374151}
.uname{font-size:11px;font-weight:600;color:#e2e8f0}.urole{font-size:10px;color:#64748b}
.view-list{flex:1;padding:24px 20px;max-width:860px;margin:0 auto;width:100%}
.list-hero{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}
.list-hero h2{margin:0 0 3px;font-size:18px;font-weight:700;color:var(--navy)}.list-hero p{margin:0;font-size:12px;color:var(--slate)}
.empty-state{text-align:center;padding:60px 20px;background:#fff;border-radius:14px;border:1px solid var(--border)}
.empty-ico{font-size:42px;margin-bottom:14px}.empty-ico.sm{font-size:28px}
.empty-state h3{margin:0 0 6px;font-size:15px;color:var(--navy)}.empty-state p,.muted{margin:0;font-size:12px;color:var(--slate)}.muted-sm{font-size:10px;color:#94a3b8;font-style:italic}.mt{margin-top:14px}
.rct-cards{display:flex;flex-direction:column;gap:7px}
.rct-card{background:#fff;border:1px solid var(--border);border-radius:10px;padding:12px 14px;display:flex;align-items:center;gap:10px;cursor:pointer;transition:all .15s}.rct-card:hover{border-color:#6ee7b7;box-shadow:0 2px 8px rgba(5,150,105,.08)}
.rct-code{font-family:monospace;font-size:12px;font-weight:700;color:var(--navy)}.rct-date{font-size:11px;color:var(--slate);margin-left:auto}
.view-create{flex:1;padding:24px 20px}.create-shell{max-width:720px;margin:0 auto}
.steps-bar{display:flex;align-items:center;margin-bottom:18px;background:#fff;border:1px solid var(--border);border-radius:10px;padding:12px 16px}
.step{display:flex;align-items:center;gap:7px;flex:1;font-size:12px;font-weight:500;color:var(--slate)}.step.active{color:var(--navy);font-weight:600}.step.done{color:var(--green)}
.snum{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;background:#f1f5f9;color:var(--slate);border:2px solid var(--border);flex-shrink:0;transition:all .2s}
.step.active .snum{background:#059669;color:#fff;border-color:#059669}.step.done .snum{background:var(--green);color:#fff;border-color:var(--green)}
.step-line{width:40px;height:2px;background:var(--border);margin:0 8px;flex-shrink:0}
.create-card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:20px}
.card-title{margin:0 0 14px;font-size:14px;font-weight:700;color:var(--navy)}.card-hint{font-size:12px;color:var(--slate);line-height:1.5;margin:10px 0 0;padding:10px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:7px}
.fg2{display:grid;grid-template-columns:1fr 1fr;gap:12px}.fg-full{grid-column:1/-1}
.field{display:flex;flex-direction:column;gap:4px}.field label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--slate)}
.step-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:18px}
.affect-table{display:flex;flex-direction:column;gap:6px}
.affect-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;align-items:center;padding:8px 10px;background:#f8fafc;border:1px solid var(--border);border-radius:8px}
.cat-info{display:flex;align-items:center;gap:8px}.cat-icon{font-size:18px;flex-shrink:0}.cat-code{font-size:11px;font-weight:700;font-family:monospace;color:var(--navy)}.cat-label{font-size:11px;color:var(--slate)}
.inp{padding:7px 10px;border:1px solid var(--border);border-radius:7px;font-size:12px;color:var(--navy);font-family:inherit;background:#fff;transition:border-color .15s;width:100%;box-sizing:border-box}
.inp:focus{outline:none;border-color:#059669;box-shadow:0 0 0 3px rgba(5,150,105,.1)}.sel{appearance:none;cursor:pointer}
.edit-layout{display:flex;flex:1;overflow:hidden}
.sidebar{width:250px;flex-shrink:0;background:#fff;border-right:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden}
.sidebar-head{display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border-bottom:1px solid var(--border)}
.sidebar-title{font-size:12px;font-weight:700;color:var(--navy)}
.btn-icon-sm{width:26px;height:26px;border-radius:6px;border:1px solid var(--border);background:#f8fafc;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;transition:all .12s}.btn-icon-sm:hover{background:#f0fdf4;border-color:#bbf7d0}
.cat-list{flex:1;overflow-y:auto;padding:6px}
.cat-item{display:flex;align-items:flex-start;gap:8px;padding:9px 10px;border-radius:9px;cursor:pointer;border:1px solid transparent;margin-bottom:4px;transition:all .15s}
.cat-item:hover{background:#f0fdf4;border-color:#bbf7d0}
.cat-active{background:#f0fdf4!important;border-color:var(--cc,#059669)!important;border-left-width:3px}
.cat-mine{border-left:2px solid #059669}
.cat-icon-s{font-size:18px;flex-shrink:0;margin-top:1px}
.cat-info-s{flex:1;min-width:0}.cat-code{font-size:10px;font-family:monospace;font-weight:700;color:var(--navy)}.cat-label-s{font-size:11px;font-weight:600;color:var(--navy);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cat-aud{font-size:10px;color:var(--slate);margin-top:2px;display:flex;align-items:center;gap:4px}
.cat-count{background:#059669;color:#fff;font-size:9px;font-weight:700;padding:1px 5px;border-radius:10px;min-width:16px;text-align:center;flex-shrink:0;align-self:flex-start;margin-top:2px}
.sidebar-foot{padding:10px 14px;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:5px}
.info-row{display:flex;justify-content:space-between;font-size:10px;color:var(--slate)}.info-row strong{color:var(--navy);font-size:11px;text-align:right;max-width:60%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.main-content{flex:1;overflow:auto;display:flex;flex-direction:column}
.cat-header{display:flex;align-items:flex-start;justify-content:space-between;padding:12px 16px;background:#fff;border-bottom:1px solid var(--border);border-left:4px solid #059669;gap:12px}
.cat-header-l{display:flex;align-items:center;gap:10px}
.cat-big-icon{font-size:26px;flex-shrink:0}.cat-big-label{font-size:13px;font-weight:700;color:var(--navy);margin-bottom:3px}
.cat-big-sub{font-size:11px;color:var(--slate);display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.role-pill{display:inline-block;padding:1px 6px;border-radius:4px;font-size:9px;font-weight:700;font-family:monospace}.role-pill.sm{font-size:8px;padding:1px 5px}
.rp-DM{background:#fdf4ff;color:#7e22ce}.rp-CM{background:#eff6ff;color:#0369a1}.rp-AS{background:#fffbeb;color:#d97706}.rp-AJ{background:#f1f5f9;color:#64748b}
.cat-empty{text-align:center;padding:40px;background:#fff;border:1px dashed var(--border);margin:14px}.cat-empty p{font-size:12px;color:var(--slate);margin:0}
.tbl-wrap{overflow-x:auto}
.rct-tbl{width:100%;border-collapse:collapse;font-size:11px;background:#fff}
.th-row th{padding:8px 10px;background:#f0fdf4;color:#064e3b;font-size:10px;font-weight:700;text-align:left;text-transform:uppercase;letter-spacing:.04em;border-bottom:2px solid #bbf7d0;white-space:nowrap;vertical-align:bottom;border-right:1px solid var(--border)}
.th-row th:last-child{border-right:none}
.rct-tbl td{border-bottom:1px solid var(--border);border-right:1px solid #f0fdf4;vertical-align:top}.rct-tbl td:last-child{border-right:none}.rct-tbl tbody tr:last-child td{border-bottom:none}
.tbl-row:hover{background:#f0fdf4}.row-mine{border-left:3px solid #059669}
.col-ref{width:72px}.col-art{width:130px}.col-proc{width:170px}.col-point{width:200px}.col-preuves{width:210px}.col-resp{width:150px}
.td-ref,.td-art,.td-proc,.td-point,.td-preuves,.td-resp{padding:6px 8px}.td-ref{vertical-align:middle;text-align:center}
.ref-badge{display:inline-block;padding:2px 7px;border-radius:4px;font-family:monospace;font-size:10px;font-weight:700;white-space:nowrap}
.art-code{font-family:monospace;font-size:10px;color:#0369a1;line-height:1.4;display:block}.cell-ro{font-size:11px;color:#334155;line-height:1.5;padding:2px 0}
.note-ro{font-size:10px;color:var(--slate);font-style:italic;margin-top:4px;padding-top:4px;border-top:1px dashed var(--border)}
.resp-ro{display:flex;flex-direction:column;gap:3px}.resp-name{font-size:11px;color:var(--navy);font-weight:500}
.cel-area{width:100%;padding:4px 6px;border:1px solid var(--border);border-radius:5px;font-size:10px;font-family:inherit;color:var(--navy);background:#fff;resize:vertical;min-height:44px;transition:border-color .15s;box-sizing:border-box}.cel-area:focus{outline:none;border-color:#059669}
.note-area{min-height:34px;border-style:dashed;background:#fafafa;margin-top:6px}
.preuves-cell{display:flex;flex-direction:column;gap:5px}
.fichiers-list{display:flex;flex-direction:column;gap:3px}
.fchip{display:flex;align-items:center;gap:4px;padding:3px 7px;border-radius:5px;font-size:10px;border:1px solid var(--border);background:#f8fafc;max-width:100%}
.fchip-pdf{background:#fff1f2;border-color:#fecdd3;color:#9f1239}.fchip-xl{background:#f0fdf4;border-color:#bbf7d0;color:#15803d}.fchip-doc{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}.fchip-img{background:#fffbeb;border-color:#fde68a;color:#92400e}.fchip-other{background:#f1f5f9;border-color:#e2e8f0;color:#475569}
.fchip-icon{font-size:12px;flex-shrink:0}.fchip-name{font-size:10px;font-weight:500;text-decoration:none;color:inherit;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.fchip-name:hover{text-decoration:underline}.fchip-size{font-size:9px;color:#94a3b8;white-space:nowrap;flex-shrink:0}
.fchip-del{background:none;border:none;color:#94a3b8;cursor:pointer;font-size:14px;padding:0 2px;border-radius:3px;transition:color .1s}.fchip-del:hover{color:var(--red)}
.upload-zone{border:1.5px dashed #cbd5e1;border-radius:6px;padding:6px 8px;text-align:center;transition:all .15s;background:#fafafa;cursor:pointer}.upload-zone:hover,.drag-over{border-color:#059669;background:#f0fdf4}
.upload-label{display:flex;flex-direction:column;align-items:center;gap:2px;cursor:pointer}.upload-ico{font-size:16px}.upload-label span{font-size:10px;color:var(--slate)}.upload-hint{font-size:9px;color:#94a3b8}
.edit-footer{position:sticky;bottom:0;z-index:50;display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#fff;border-top:1px solid var(--border);gap:12px;box-shadow:0 -4px 12px rgba(0,0,0,.07)}
.ef-l,.ef-r{display:flex;gap:7px;align-items:center}.ef-c{flex:1;text-align:center}.totals{font-size:11px;color:var(--slate);font-family:monospace}
.locked-pill{display:flex;align-items:center;gap:5px;padding:6px 12px;background:#f0fdf4;color:var(--green);border:1px solid #bbf7d0;border-radius:7px;font-size:12px;font-weight:600}
.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9000;display:flex;align-items:center;justify-content:center;padding:16px}
.modal{background:#fff;border-radius:12px;width:100%;max-width:620px;max-height:80vh;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.3)}
.modal-head{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid var(--border);font-size:13px;font-weight:700;color:var(--navy)}
.modal-x{background:none;border:none;font-size:22px;cursor:pointer;color:var(--slate)}.modal-body{overflow-y:auto;padding:14px 18px;display:flex;flex-direction:column;gap:7px}.modal-foot{display:flex;justify-content:flex-end;gap:8px;padding:12px 18px;border-top:1px solid var(--border)}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 13px;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .15s;text-decoration:none;white-space:nowrap}
.btn:disabled{opacity:.45;cursor:not-allowed}
.btn-ghost{background:#fff;color:var(--slate);border:1px solid var(--border)}.btn-ghost:hover:not(:disabled){background:#f8fafc}
.btn-primary{background:#064e3b;color:#fff}.btn-primary:hover:not(:disabled){background:#065f46}
.btn-sm{padding:5px 10px;font-size:11px}
.btn-submit{background:#1d4ed8;color:#fff}.btn-submit:hover:not(:disabled){background:#1e40af}
.btn-validate{background:var(--green);color:#fff}.btn-validate:hover:not(:disabled){background:#166534}
.btn-reject{background:#fff;color:var(--red);border:1px solid #fecaca}.btn-reject:hover:not(:disabled){background:#fef2f2}
.toast{position:fixed;bottom:70px;right:14px;z-index:9999;padding:9px 14px;border-radius:8px;font-size:12px;font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.15)}
.toast-ok{background:#f0fdf4;color:var(--green);border:1px solid #bbf7d0}.toast-err{background:#fef2f2;color:var(--red);border:1px solid #fecaca}
.t-enter-active,.t-leave-active{transition:all .25s}.t-enter-from,.t-leave-to{transform:translateX(20px);opacity:0}
.spin,.spin-sm{border-radius:50%;animation:sp .5s linear infinite;display:inline-block;flex-shrink:0;border:2px solid currentColor;border-top-color:transparent}
.spin{width:12px;height:12px}.spin-sm{width:10px;height:10px}
@keyframes sp{to{transform:rotate(360deg)}}
</style>