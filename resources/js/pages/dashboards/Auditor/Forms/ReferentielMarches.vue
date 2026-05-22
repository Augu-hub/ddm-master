<template>
  <VerticalLayoutAudit>
    <div class="rcm-root">

      <!-- ── TOPBAR ── -->
      <header class="topbar">
        <div class="topbar-l">
          <a :href="backUrl" class="back-btn"><IconArrow /></a>
          <div class="brand">
            <span class="brand-tag">RCM</span>
            <div>
              <div class="brand-title">Référentiel de Contrôle des Marchés Publics</div>
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
          <div><h2>Référentiels de contrôle des marchés</h2><p>{{ rcmList.length }} référentiel(s)</p></div>
          <button v-if="canManage" class="btn btn-primary" @click="view='create'"><IconPlus /> Nouveau RCM</button>
        </div>
        <div v-if="!rcmList.length" class="empty-state">
          <div class="empty-ico">📋</div>
          <div v-if="canManage"><h3>Aucun référentiel créé</h3><p>Créez le RCM et affectez les auditeurs par phase.</p><button class="btn btn-primary mt" @click="view='create'"><IconPlus /> Créer</button></div>
          <div v-else><h3>En attente de création</h3><p>Le DM ou CM doit créer le référentiel.</p></div>
        </div>
        <div v-else class="rcm-cards">
          <div v-for="r in rcmList" :key="r.id" class="rcm-card" @click="openEdit(r.id)">
            <span class="rcm-code">{{ r.code }}</span>
            <span class="status-chip sm" :class="`s-${r.validation_status}`"><span class="dot"></span>{{ STATUS_LABELS[r.validation_status]??'Brouillon' }}</span>
            <span class="rcm-date">{{ fmt(r.updated_at) }}</span><IconChevron />
          </div>
        </div>
      </div>

      <!-- ════ VUE CRÉATION ════ -->
      <div v-else-if="view === 'create'" class="view-create">
        <div class="create-shell">
          <div class="steps-bar">
            <div class="step" :class="{active:step===1,done:step>1}"><span class="snum">{{step>1?'✓':'1'}}</span> Informations</div>
            <div class="step-line"></div>
            <div class="step" :class="{active:step===2}"><span class="snum">2</span> Affectations par phase</div>
          </div>

          <div v-if="step===1" class="create-card">
            <h3 class="card-title">📋 Identification du RCM</h3>
            <div class="fg2">
              <div class="field"><label>Fait par</label><input v-model="draft.fait_par" class="inp" placeholder="Rédacteur" /></div>
              <div class="field"><label>Revue par</label><input v-model="draft.revue_par" class="inp" placeholder="Relecteur" /></div>
              <div class="field"><label>Autorité contractante</label><input v-model="draft.autorite_contractante" class="inp" placeholder="Ex : Ministère des Finances" /></div>
              <div class="field"><label>Exercice budgétaire</label><input v-model="draft.exercice_budgetaire" class="inp" placeholder="Ex : 2025" /></div>
            </div>
            <div class="step-actions">
              <button class="btn btn-ghost" @click="view='list'">Annuler</button>
              <button class="btn btn-primary" @click="step=2">Suivant <IconChevron /></button>
            </div>
          </div>

          <div v-else-if="step===2" class="create-card">
            <h3 class="card-title">👥 Affecter un auditeur par phase</h3>
            <p class="card-hint">Chaque auditeur affecté créera ses critères dans sa phase.</p>
            <div class="affect-table">
              <div v-for="(ph, code) in phases" :key="code" class="affect-row">
                <div class="phase-info">
                  <span class="ph-icon" :style="`color:${ph.color}`">{{ ph.icon }}</span>
                  <div><div class="ph-code">{{ code }}</div><div class="ph-label">{{ ph.label }}</div></div>
                </div>
                <select v-model="draft.affectations[code]" class="inp sel">
                  <option value="">— Non affecté —</option>
                  <option v-for="a in phaseAuditeurs" :key="a.id" :value="a.id">
                    {{ a.full_name }} · {{ ROLE_LABELS[a.role_code]??a.role_code }}
                  </option>
                </select>
              </div>
            </div>
            <div class="step-actions">
              <button class="btn btn-ghost" @click="step=1"><IconArrow /> Précédent</button>
              <button class="btn btn-primary" :disabled="creating" @click="submitCreate">
                <span v-if="creating" class="spin"></span><IconCheck v-else /> Créer le RCM
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ════ VUE ÉDITION ════ -->
      <template v-else-if="view==='edit' && currentForm">
        <div class="edit-layout">

          <!-- ── SIDEBAR PHASES ── -->
          <aside class="sidebar">
            <div class="sidebar-head">
              <span class="sidebar-title">Phases</span>
              <button v-if="canManage && !isLocked" class="btn-icon-sm" @click="openAffectModal" title="Gérer affectations">👥</button>
            </div>
            <div class="phase-list">
              <div v-for="(ph, code) in phases" :key="code"
                class="phase-item" :class="{'phase-active':activePhase===code,'phase-mine':myPhases.includes(code)}"
                :style="{'--pc':ph.color}" @click="activePhase=code">
                <span class="phase-icon" :style="`color:${ph.color}`">{{ ph.icon }}</span>
                <div class="phase-info-s">
                  <div class="phase-code">{{ code }}</div>
                  <div class="phase-label-s">{{ ph.label }}</div>
                  <div class="phase-aud" v-if="ph.auditeur_id">
                    <span class="role-pill sm" :class="`rp-${getAuditorRole(ph.auditeur_id)}`">{{ getAuditorRole(ph.auditeur_id) }}</span>
                    {{ getAuditorName(ph.auditeur_id) }}
                  </div>
                  <div class="phase-aud muted-sm" v-else>Non affecté</div>
                </div>
                <div class="phase-count" v-if="criteresByPhase[code]?.length">{{ criteresByPhase[code].length }}</div>
              </div>
            </div>
            <div class="sidebar-foot" v-if="currentForm">
              <div class="info-row"><span>Autorité</span><strong>{{ currentForm.autorite_contractante||'—' }}</strong></div>
              <div class="info-row"><span>Exercice</span><strong>{{ currentForm.exercice_budgetaire||'—' }}</strong></div>
              <div class="info-row"><span>Total critères</span><strong>{{ criteres.length }}</strong></div>
            </div>
          </aside>

          <!-- ── CONTENU PHASE ACTIVE ── -->
          <div class="main-content">
            <template v-if="activePhaseData">
              <div class="ph-header" :style="`border-left-color:${activePhaseData.color}`">
                <div class="ph-header-l">
                  <span class="ph-big-icon" :style="`color:${activePhaseData.color}`">{{ activePhaseData.icon }}</span>
                  <div>
                    <div class="ph-big-label">{{ activePhaseData.label }}</div>
                    <div class="ph-big-sub">
                      <template v-if="activePhaseData.auditeur_id">
                        Affecté à : <strong>{{ getAuditorName(activePhaseData.auditeur_id) }}</strong>
                        <span class="role-pill" :class="`rp-${getAuditorRole(activePhaseData.auditeur_id)}`">{{ getAuditorRole(activePhaseData.auditeur_id) }}</span>
                      </template>
                      <span v-else class="muted-sm">Non affecté</span>
                    </div>
                  </div>
                </div>
                <div class="ph-header-r" v-if="canEditPhase(activePhaseData) && !isLocked">
                  <button class="btn btn-sm btn-primary" @click="addCritere(activePhase)">
                    <IconPlus /> Ajouter un critère
                  </button>
                </div>
              </div>

              <!-- Tableau -->
              <div class="tbl-wrap" v-if="criteresByPhase[activePhase]?.length">
                <table class="rcm-tbl table table-td-striped">
                  <thead>
                    <tr class="th-row">
                      <th class="col-ref">Réf.<br>Contrôle</th>
                      <th class="col-art">Référence<br>Réglementaire</th>
                      <th class="col-proc">Intitulé de<br>la Procédure</th>
                      <th class="col-point">Point de Contrôle /<br>Exigence</th>
                      <th class="col-preuves">Preuves du<br>Contrôle</th>
                      <th class="col-resp">Responsable<br>du contrôle</th>
                      <th v-if="!isLocked" class="col-act"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="c in criteresByPhase[activePhase]" :key="c.id" class="tbl-row"
                      :class="{'row-mine':isMine(c)}">

                      <td class="td-ref">
                        <input v-if="canEditCritere(c)" v-model="c.ref_controle" class="cel-inp mono"
                          :placeholder="`${activePhase}-C01`" @change="saveCritere(c)" />
                        <span v-else class="ref-badge" :style="`background:${activePhaseData.color}18;color:${activePhaseData.color}`">{{ c.ref_controle||'—' }}</span>
                      </td>

                      <td class="td-art">
                        <input v-if="canEditCritere(c)" v-model="c.ref_reglementaire" class="cel-inp"
                          placeholder="Art. X – Décret…" @change="saveCritere(c)" />
                        <code v-else class="art-code">{{ c.ref_reglementaire||'—' }}</code>
                      </td>

                      <td class="td-proc">
                        <textarea v-if="canEditCritere(c)" v-model="c.intitule_procedure" class="cel-area" rows="3"
                          placeholder="Intitulé de la procédure…" @change="saveCritere(c)"></textarea>
                        <div v-else class="cell-ro">{{ c.intitule_procedure||'—' }}</div>
                      </td>

                      <td class="td-point">
                        <textarea v-if="canEditCritere(c)" v-model="c.point_controle" class="cel-area" rows="3"
                          placeholder="Exigence réglementaire vérifiée…" @change="saveCritere(c)"></textarea>
                        <div v-else class="cell-ro">{{ c.point_controle||'—' }}</div>
                      </td>

                      <td class="td-preuves">
                        <div class="preuves-cell">
                          <div v-if="c.preuves_fichiers?.length" class="fichiers-list">
                            <div v-for="f in c.preuves_fichiers" :key="f.path" class="fchip" :class="mimeClass(f)">
                              <span class="fchip-icon">{{ mimeIcon(f) }}</span>
                              <a :href="f.url" target="_blank" class="fchip-name" :title="f.name">{{ trunc(f.name,18) }}</a>
                              <span class="fchip-size">{{ fmtSize(f.size) }}</span>
                              <button v-if="canEditCritere(c)&&!isLocked" class="fchip-del" @click="deletePreuve(c,f)">×</button>
                            </div>
                          </div>
                          <div v-if="canEditCritere(c)&&!isLocked" class="upload-zone"
                            :class="{'drag-over':dragOver[c.id]}"
                            @dragover.prevent="dragOver[c.id]=true"
                            @dragleave="dragOver[c.id]=false"
                            @drop.prevent="onDrop($event,c)">
                            <span v-if="uploadingFor===c.id" class="spin-sm"></span>
                            <template v-else>
                              <label :for="`fu-${c.id}`" class="upload-label">
                                <span class="upload-ico">📎</span><span>Joindre un fichier</span>
                                <span class="upload-hint">pdf · xlsx · docx · jpg</span>
                              </label>
                              <input :id="`fu-${c.id}`" type="file" multiple
                                accept=".pdf,.xlsx,.xls,.docx,.doc,.png,.jpg,.jpeg"
                                style="display:none" @change="onFileChange($event,c)" />
                            </template>
                          </div>
                          <span v-else-if="!c.preuves_fichiers?.length" class="muted-sm">Aucune pièce</span>
                          <textarea v-if="canEditCritere(c)" v-model="c.note_preuves" class="cel-area note-area" rows="2"
                            placeholder="Note sur les preuves…" @change="saveCritere(c)"></textarea>
                          <div v-else-if="c.note_preuves" class="cell-ro note-ro">{{ c.note_preuves }}</div>
                        </div>
                      </td>

                      <td class="td-resp">
                        <div class="resp-ro">
                          <span class="resp-name">{{ getAuditorName(c.auditeur_id)||'—' }}</span>
                          <span v-if="c.auditeur_id" class="role-pill sm" :class="`rp-${getAuditorRole(c.auditeur_id)}`">{{ getAuditorRole(c.auditeur_id) }}</span>
                        </div>
                      </td>

                      <td v-if="!isLocked" class="td-act">
                        <button v-if="canEditCritere(c)" class="del-btn" @click="removeCritere(c)" title="Supprimer">×</button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div v-else class="ph-empty">
                <div class="empty-ico sm">📄</div>
                <template v-if="canEditPhase(activePhaseData)">
                  <p>Aucun critère dans cette phase.</p>
                  <button class="btn btn-primary" @click="addCritere(activePhase)"><IconPlus /> Ajouter le premier critère</button>
                </template>
                <p v-else class="muted">L'auditeur affecté n'a pas encore saisi de critères.</p>
              </div>
            </template>
          </div>
        </div>

        <!-- Footer -->
        <footer class="edit-footer">
          <div class="ef-l"><button class="btn btn-ghost" @click="view='list'">← Liste</button></div>
          <div class="ef-c"><span class="totals">{{ Object.keys(phases).length }} phases · {{ criteres.length }} critère(s)</span></div>
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

      <!-- Modal affectations phases -->
      <Teleport to="body">
        <div v-if="affectModal" class="modal-bg" @click.self="affectModal=false">
          <div class="modal">
            <div class="modal-head"><span>👥 Affectations par phase</span><button class="modal-x" @click="affectModal=false">×</button></div>
            <div class="modal-body">
              <div v-for="(ph, code) in phases" :key="code" class="affect-row">
                <div class="phase-info">
                  <span class="ph-icon" :style="`color:${ph.color}`">{{ ph.icon }}</span>
                  <div><div class="ph-code">{{ code }}</div><div class="ph-label">{{ ph.label }}</div></div>
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

interface Fichier { name:string;path:string;url:string;size:number;mime:string;uploaded_at:string;uploaded_by:number }
interface Phase { label:string;icon:string;color:string;phase_code:string;auditeur_id:number|null }
interface Critere { id:number;rcm_id:number;phase_code:string;ref_controle:string;ref_reglementaire:string;intitule_procedure:string;point_controle:string;note_preuves:string;preuves_fichiers:Fichier[];auditeur_id:number|null;ordre:number }

const props = defineProps<{
  form:any|null; phases:Record<string,Phase>; criteres:Critere[]; myPhases:string[]
  phaseAuditeurs:any[]; rcmList:any[]; mission:any
  currentAuditor:{id:number;last_name:string;first_name:string;role:string}
  canManage:boolean; backUrl:string; missionId:number; assignmentId:number
  urlStore:string; urlUpdate:string|null; urlSoumettre:string|null; urlValider:string|null
  urlUpdatePhases:string|null; urlStoreCritere:string|null; urlUpdateCritere:string|null; urlDeleteCritere:string|null
  urlUploadPreuve:string|null; urlDeletePreuve:string|null
}>()

const STATUS_LABELS:Record<string,string>={draft:'Brouillon',in_review:'En révision',validated:'Validé'}
const ROLE_LABELS:Record<string,string>={DM:'Dir. Mission',CM:'Chef Mission',AS:'Aud. Senior',AJ:'Aud. Junior'}

const view        = ref<'list'|'create'|'edit'>(props.form?'edit':'list')
const step        = ref(1)
const currentForm = ref<any>(props.form)
const creating    = ref(false)
const saving      = ref(false)
const affectModal = ref(false)
const affectSaving = ref(false)
const uploadingFor = ref<number|null>(null)
const dragOver     = reactive<Record<number,boolean>>({})
const toast        = ref({show:false,type:'ok',message:''})
const activePhase  = ref<string>(props.myPhases[0]??Object.keys(props.phases)[0])
const phases       = reactive<Record<string,Phase>>({...props.phases})
const criteres     = reactive<Critere[]>([...props.criteres])
const tmpAffect    = reactive<Record<string,any>>(Object.fromEntries(Object.keys(props.phases).map(k=>[k,props.phases[k].auditeur_id??null])))
const draft = reactive({fait_par:'',revue_par:'',autorite_contractante:'',exercice_budgetaire:'',affectations:Object.fromEntries(Object.keys(props.phases).map(k=>[k,''])) as Record<string,any>})

const initials      = computed(()=>((props.currentAuditor.last_name?.[0]??'?')+(props.currentAuditor.first_name?.[0]??'?')).toUpperCase())
const isLocked      = computed(()=>currentForm.value?.validation_status==='validated')
const activePhaseData = computed(()=>phases[activePhase.value]??null)
const criteresByPhase = computed(()=>{ const m:Record<string,Critere[]>={}; for(const c of criteres){if(!m[c.phase_code])m[c.phase_code]=[]; m[c.phase_code].push(c)} return m })

function canEditPhase(ph:Phase){return !isLocked.value&&(props.canManage||props.myPhases.includes(ph.phase_code??activePhase.value))}
function canEditCritere(c:Critere){return !isLocked.value&&(props.canManage||c.auditeur_id===props.currentAuditor.id)}
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
function openEdit(id:number){if(props.form?.id===id){view.value='edit';return}router.visit(window.location.pathname+`?rcm_id=${id}`,{preserveState:false})}
function openAffectModal(){Object.keys(phases).forEach(k=>{tmpAffect[k]=phases[k].auditeur_id??null});affectModal.value=true}

async function addCritere(phaseCode:string){
  if(!props.urlStoreCritere)return
  try{
    const res=await fetch(props.urlStoreCritere,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({phase_code:phaseCode,intitule_procedure:'Nouveau critère'})})
    const data=await res.json()
    if(data.success){criteres.push(data.critere);showToast('ok','Critère ajouté')}
    else showToast('err',data.error??'Erreur')
  }catch{showToast('err','Erreur réseau')}
}

async function saveCritere(c:Critere){
  if(!props.urlUpdateCritere)return
  try{
    const url=props.urlUpdateCritere.replace(':id',String(c.id))
    await fetch(url,{method:'PUT',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({ref_controle:c.ref_controle,ref_reglementaire:c.ref_reglementaire,intitule_procedure:c.intitule_procedure,point_controle:c.point_controle,note_preuves:c.note_preuves})})
  }catch{showToast('err','Erreur sauvegarde')}
}

async function removeCritere(c:Critere){
  if(!props.urlDeleteCritere||!confirm('Supprimer ce critère ?'))return
  try{
    const url=props.urlDeleteCritere.replace(':id',String(c.id))
    const res=await fetch(url,{method:'DELETE',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()}})
    const data=await res.json()
    if(data.success){const i=criteres.findIndex(x=>x.id===c.id);if(i!==-1)criteres.splice(i,1);showToast('ok','Critère supprimé')}
    else showToast('err',data.error??'Erreur')
  }catch{showToast('err','Erreur réseau')}
}

async function uploadFile(c:Critere,file:File){
  if(!props.urlUploadPreuve)return; uploadingFor.value=c.id
  try{
    const fd=new FormData();fd.append('critere_id',String(c.id));fd.append('file',file)
    const res=await fetch(props.urlUploadPreuve,{method:'POST',headers:{'X-CSRF-TOKEN':csrf()},body:fd})
    const data=await res.json()
    if(data.success){c.preuves_fichiers.push(data.fichier);showToast('ok',data.message)}
    else showToast('err',data.error??'Erreur upload')
  }catch{showToast('err','Erreur réseau')}finally{uploadingFor.value=null}
}
function onFileChange(e:Event,c:Critere){Array.from((e.target as HTMLInputElement).files??[]).forEach(f=>uploadFile(c,f));(e.target as HTMLInputElement).value=''}
function onDrop(e:DragEvent,c:Critere){dragOver[c.id]=false;Array.from(e.dataTransfer?.files??[]).forEach(f=>uploadFile(c,f))}

async function deletePreuve(c:Critere,f:Fichier){
  if(!props.urlDeletePreuve||!confirm(`Supprimer "${f.name}" ?`))return
  try{
    const res=await fetch(props.urlDeletePreuve,{method:'DELETE',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({critere_id:c.id,path:f.path})})
    const data=await res.json()
    if(data.success){const i=c.preuves_fichiers.findIndex(x=>x.path===f.path);if(i!==-1)c.preuves_fichiers.splice(i,1);showToast('ok','Fichier supprimé')}
    else showToast('err',data.error??'Erreur')
  }catch{showToast('err','Erreur réseau')}
}

async function submitCreate(){
  creating.value=true
  try{
    const res=await fetch(props.urlStore,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({mission_id:props.missionId,assignment_id:props.assignmentId,fait_par:draft.fait_par,revue_par:draft.revue_par,autorite_contractante:draft.autorite_contractante,exercice_budgetaire:draft.exercice_budgetaire,phase_affectations:draft.affectations})})
    const data=await res.json()
    if(data.success){showToast('ok','RCM créé !');setTimeout(()=>{if(data.redirect)window.location.href=data.redirect;else router.reload()},500)}
    else showToast('err',data.message??'Erreur')
  }catch{showToast('err','Erreur réseau')}finally{creating.value=false}
}

async function saveAffectations(){
  if(!props.urlUpdatePhases)return; affectSaving.value=true
  try{
    const res=await fetch(props.urlUpdatePhases,{method:'PUT',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({phase_affectations:tmpAffect})})
    const data=await res.json()
    if(data.success){Object.assign(phases,data.phases);affectModal.value=false;showToast('ok','Affectations enregistrées')}
    else showToast('err',data.error??'Erreur')
  }catch{showToast('err','Erreur réseau')}finally{affectSaving.value=false}
}

async function submit(){
  if(!props.urlSoumettre)return;saving.value=true
  try{const res=await fetch(props.urlSoumettre,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({mission_id:props.missionId,assignment_id:props.assignmentId})});const data=await res.json();if(data.success&&currentForm.value){currentForm.value.validation_status='in_review';showToast('ok','Soumis pour validation')}else showToast('err',data.error??'Erreur')}catch{showToast('err','Erreur réseau')}finally{saving.value=false}
}
async function validate(){
  if(!props.urlValider)return;saving.value=true
  try{const res=await fetch(props.urlValider,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({mission_id:props.missionId,assignment_id:props.assignmentId,action:'validate'})});const data=await res.json();if(data.success&&currentForm.value){currentForm.value.validation_status='validated';showToast('ok','RCM validé')}else showToast('err',data.error??'Erreur')}catch{showToast('err','Erreur réseau')}finally{saving.value=false}
}
async function reject(){
  const note=prompt('Motif du rejet :');if(!note?.trim())return;saving.value=true
  try{const res=await fetch(props.urlValider!,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({mission_id:props.missionId,assignment_id:props.assignmentId,action:'reject',note})});const data=await res.json();if(data.success&&currentForm.value){currentForm.value.validation_status='draft';showToast('ok','RCM rejeté')}else showToast('err',data.error??'Erreur')}catch{showToast('err','Erreur réseau')}finally{saving.value=false}
}
</script>

<style scoped>
.rcm-root{font-family:'Geist','Inter',system-ui,sans-serif;min-height:100vh;background:#f0f4f8;display:flex;flex-direction:column;--navy:#0f172a;--slate:#475569;--border:#e2e8f0;--green:#15803d;--red:#dc2626}
.topbar{position:sticky;top:0;z-index:100;height:52px;background:#0f172a;display:flex;align-items:center;justify-content:space-between;padding:0 16px;box-shadow:0 2px 10px rgba(0,0,0,.3)}
.topbar-l{display:flex;align-items:center;gap:10px;min-width:0}.topbar-r{display:flex;align-items:center;gap:8px;flex-shrink:0}
.back-btn{width:28px;height:28px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.12);border-radius:7px;color:#94a3b8;text-decoration:none;flex-shrink:0;transition:all .15s}.back-btn:hover{background:rgba(255,255,255,.08);color:#fff}
.brand{display:flex;align-items:center;gap:8px;min-width:0}
.brand-tag{font-size:10px;font-weight:700;letter-spacing:.08em;background:#1e40af;color:#fff;padding:2px 7px;border-radius:5px;flex-shrink:0}
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
.rcm-cards{display:flex;flex-direction:column;gap:7px}
.rcm-card{background:#fff;border:1px solid var(--border);border-radius:10px;padding:12px 14px;display:flex;align-items:center;gap:10px;cursor:pointer;transition:all .15s}.rcm-card:hover{border-color:#93c5fd;box-shadow:0 2px 8px rgba(29,78,216,.08)}
.rcm-code{font-family:monospace;font-size:12px;font-weight:700;color:var(--navy)}.rcm-date{font-size:11px;color:var(--slate);margin-left:auto}
.view-create{flex:1;padding:24px 20px}.create-shell{max-width:700px;margin:0 auto}
.steps-bar{display:flex;align-items:center;margin-bottom:18px;background:#fff;border:1px solid var(--border);border-radius:10px;padding:12px 16px}
.step{display:flex;align-items:center;gap:7px;flex:1;font-size:12px;font-weight:500;color:var(--slate)}.step.active{color:var(--navy);font-weight:600}.step.done{color:var(--green)}
.snum{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;background:#f1f5f9;color:var(--slate);border:2px solid var(--border);flex-shrink:0;transition:all .2s}
.step.active .snum{background:#1e40af;color:#fff;border-color:#1e40af}.step.done .snum{background:var(--green);color:#fff;border-color:var(--green)}
.step-line{width:40px;height:2px;background:var(--border);margin:0 8px;flex-shrink:0}
.create-card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:20px}
.card-title{margin:0 0 14px;font-size:14px;font-weight:700;color:var(--navy)}.card-hint{font-size:12px;color:var(--slate);line-height:1.5;margin:10px 0 0;padding:10px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:7px}
.fg2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.field{display:flex;flex-direction:column;gap:4px}.field label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--slate)}
.step-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:18px}
.affect-table{display:flex;flex-direction:column;gap:6px}
.affect-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;align-items:center;padding:8px 10px;background:#f8fafc;border:1px solid var(--border);border-radius:8px}
.phase-info{display:flex;align-items:center;gap:8px}.ph-icon{font-size:18px;flex-shrink:0}.ph-code{font-size:11px;font-weight:700;font-family:monospace;color:var(--navy)}.ph-label{font-size:11px;color:var(--slate)}
.inp{padding:7px 10px;border:1px solid var(--border);border-radius:7px;font-size:12px;color:var(--navy);font-family:inherit;background:#fff;transition:border-color .15s;width:100%;box-sizing:border-box}
.inp:focus{outline:none;border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1)}.sel{appearance:none;cursor:pointer}
.edit-layout{display:flex;flex:1;overflow:hidden}
.sidebar{width:250px;flex-shrink:0;background:#fff;border-right:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden}
.sidebar-head{display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border-bottom:1px solid var(--border)}
.sidebar-title{font-size:12px;font-weight:700;color:var(--navy)}
.btn-icon-sm{width:26px;height:26px;border-radius:6px;border:1px solid var(--border);background:#f8fafc;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;transition:all .12s}.btn-icon-sm:hover{background:#eff6ff;border-color:#bfdbfe}
.phase-list{flex:1;overflow-y:auto;padding:6px}
.phase-item{display:flex;align-items:flex-start;gap:8px;padding:9px 10px;border-radius:9px;cursor:pointer;border:1px solid transparent;margin-bottom:4px;transition:all .15s;position:relative}
.phase-item:hover{background:#f8fafc;border-color:#e2e8f0}
.phase-active{background:#f0f4ff!important;border-color:var(--pc,#1e40af)!important;border-left-width:3px}
.phase-mine{border-left:2px solid #1e40af}
.phase-icon{font-size:18px;flex-shrink:0;margin-top:1px}
.phase-info-s{flex:1;min-width:0}
.phase-code{font-size:10px;font-family:monospace;font-weight:700;color:var(--navy)}.phase-label-s{font-size:11px;font-weight:600;color:var(--navy);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.phase-aud{font-size:10px;color:var(--slate);margin-top:2px;display:flex;align-items:center;gap:4px}
.phase-count{background:#1e40af;color:#fff;font-size:9px;font-weight:700;padding:1px 5px;border-radius:10px;min-width:16px;text-align:center;flex-shrink:0;align-self:flex-start;margin-top:2px}
.sidebar-foot{padding:10px 14px;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:5px}
.info-row{display:flex;justify-content:space-between;font-size:10px;color:var(--slate)}.info-row strong{color:var(--navy);font-size:11px;text-align:right;max-width:60%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.main-content{flex:1;overflow:auto;display:flex;flex-direction:column}
.ph-header{display:flex;align-items:flex-start;justify-content:space-between;padding:12px 16px;background:#fff;border-bottom:1px solid var(--border);border-left:4px solid #1e40af;gap:12px}
.ph-header-l{display:flex;align-items:flex-start;gap:10px}.ph-header-r{flex-shrink:0}
.ph-big-icon{font-size:26px;flex-shrink:0;margin-top:2px}.ph-big-label{font-size:13px;font-weight:700;color:var(--navy);margin-bottom:3px}
.ph-big-sub{font-size:11px;color:var(--slate);display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.role-pill{display:inline-block;padding:1px 6px;border-radius:4px;font-size:9px;font-weight:700;font-family:monospace}.role-pill.sm{font-size:8px;padding:1px 5px}
.rp-DM{background:#fdf4ff;color:#7e22ce}.rp-CM{background:#eff6ff;color:#0369a1}.rp-AS{background:#fffbeb;color:#d97706}.rp-AJ{background:#f1f5f9;color:#64748b}
.ph-empty{text-align:center;padding:40px;background:#fff;border:1px dashed var(--border);margin:14px}.ph-empty p{font-size:12px;color:var(--slate);margin:0 0 14px}
.tbl-wrap{overflow-x:auto}
.rcm-tbl{width:100%;border-collapse:collapse;font-size:11px;background:#fff}
.th-row th{padding:8px 10px;background:#eff6ff;color:#1e3a8a;font-size:10px;font-weight:700;text-align:left;text-transform:uppercase;letter-spacing:.04em;border-bottom:2px solid #bfdbfe;white-space:nowrap;vertical-align:bottom;border-right:1px solid var(--border)}
.th-row th:last-child{border-right:none}
.rcm-tbl td{border-bottom:1px solid var(--border);border-right:1px solid #f0f4ff;vertical-align:top}.rcm-tbl td:last-child{border-right:none}.rcm-tbl tbody tr:last-child td{border-bottom:none}
.tbl-row:hover{background:#f0f4ff}.row-mine{border-left:3px solid #1e40af}
.col-ref{width:72px}.col-art{width:130px}.col-proc{width:170px}.col-point{width:200px}.col-preuves{width:210px}.col-resp{width:150px}.col-act{width:30px}
.td-ref,.td-art,.td-proc,.td-point,.td-preuves,.td-resp,.td-act{padding:6px 8px}.td-ref,.td-act{vertical-align:middle;text-align:center}
.ref-badge{display:inline-block;padding:2px 7px;border-radius:4px;font-family:monospace;font-size:10px;font-weight:700;white-space:nowrap}
.art-code{font-family:monospace;font-size:10px;color:#0369a1;line-height:1.4;display:block}.cell-ro{font-size:11px;color:#334155;line-height:1.5;padding:2px 0}
.note-ro{font-size:10px;color:var(--slate);font-style:italic;margin-top:4px;padding-top:4px;border-top:1px dashed var(--border)}
.resp-ro{display:flex;flex-direction:column;gap:3px}.resp-name{font-size:11px;color:var(--navy);font-weight:500}
.cel-inp{width:100%;padding:4px 6px;border:1px solid var(--border);border-radius:5px;font-size:10px;font-family:inherit;color:var(--navy);background:#fff;transition:border-color .15s;box-sizing:border-box}.cel-inp:focus{outline:none;border-color:#3b82f6}.mono{font-family:monospace;font-weight:700}
.cel-area{width:100%;padding:4px 6px;border:1px solid var(--border);border-radius:5px;font-size:10px;font-family:inherit;color:var(--navy);background:#fff;resize:vertical;min-height:44px;transition:border-color .15s;box-sizing:border-box}.cel-area:focus{outline:none;border-color:#3b82f6}
.note-area{min-height:34px;border-style:dashed;background:#fafafa;margin-top:6px}
.del-btn{width:20px;height:20px;border:none;background:none;color:#94a3b8;cursor:pointer;font-size:16px;border-radius:4px;display:flex;align-items:center;justify-content:center;transition:all .12s;margin:auto}.del-btn:hover{background:#fef2f2;color:var(--red)}
.preuves-cell{display:flex;flex-direction:column;gap:5px}
.fichiers-list{display:flex;flex-direction:column;gap:3px}
.fchip{display:flex;align-items:center;gap:4px;padding:3px 7px;border-radius:5px;font-size:10px;border:1px solid var(--border);background:#f8fafc;max-width:100%}
.fchip-pdf{background:#fff1f2;border-color:#fecdd3;color:#9f1239}.fchip-xl{background:#f0fdf4;border-color:#bbf7d0;color:#15803d}.fchip-doc{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}.fchip-img{background:#fffbeb;border-color:#fde68a;color:#92400e}.fchip-other{background:#f1f5f9;border-color:#e2e8f0;color:#475569}
.fchip-icon{font-size:12px;flex-shrink:0}.fchip-name{font-size:10px;font-weight:500;text-decoration:none;color:inherit;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.fchip-name:hover{text-decoration:underline}.fchip-size{font-size:9px;color:#94a3b8;white-space:nowrap;flex-shrink:0}
.fchip-del{background:none;border:none;color:#94a3b8;cursor:pointer;font-size:14px;padding:0 2px;border-radius:3px;transition:color .1s}.fchip-del:hover{color:var(--red)}
.upload-zone{border:1.5px dashed #cbd5e1;border-radius:6px;padding:6px 8px;text-align:center;transition:all .15s;background:#fafafa;cursor:pointer}.upload-zone:hover,.drag-over{border-color:#3b82f6;background:#eff6ff}
.upload-label{display:flex;flex-direction:column;align-items:center;gap:2px;cursor:pointer}.upload-ico{font-size:16px}.upload-label span{font-size:10px;color:var(--slate)}.upload-hint{font-size:9px;color:#94a3b8}
.edit-footer{position:sticky;bottom:0;z-index:50;display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#fff;border-top:1px solid var(--border);gap:12px;box-shadow:0 -4px 12px rgba(0,0,0,.07)}
.ef-l,.ef-r{display:flex;gap:7px;align-items:center}.ef-c{flex:1;text-align:center}.totals{font-size:11px;color:var(--slate);font-family:monospace}
.locked-pill{display:flex;align-items:center;gap:5px;padding:6px 12px;background:#f0fdf4;color:var(--green);border:1px solid #bbf7d0;border-radius:7px;font-size:12px;font-weight:600}
.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9000;display:flex;align-items:center;justify-content:center;padding:16px}
.modal{background:#fff;border-radius:12px;width:100%;max-width:600px;max-height:80vh;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.3)}
.modal-head{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid var(--border);font-size:13px;font-weight:700;color:var(--navy)}
.modal-x{background:none;border:none;font-size:22px;cursor:pointer;color:var(--slate)}.modal-body{overflow-y:auto;padding:14px 18px;display:flex;flex-direction:column;gap:7px}.modal-foot{display:flex;justify-content:flex-end;gap:8px;padding:12px 18px;border-top:1px solid var(--border)}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 13px;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .15s;text-decoration:none;white-space:nowrap}
.btn:disabled{opacity:.45;cursor:not-allowed}
.btn-ghost{background:#fff;color:var(--slate);border:1px solid var(--border)}.btn-ghost:hover:not(:disabled){background:#f8fafc}
.btn-primary{background:#1e3a8a;color:#fff}.btn-primary:hover:not(:disabled){background:#1e40af}
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