<template>
  <VerticalLayout>
    <Head title="DIADDEM — Répartition" />

    <!-- Header -->
    <b-row class="mb-1">
      <b-col>
        <div class="d-flex align-items-center justify-content-between gap-2">
          <div class="d-flex align-items-center gap-2">
            <i class="ti ti-arrows-shuffle text-primary fs-6"></i>
            <h6 class="m-0 fw-semibold">Répartition</h6>
            <small class="text-muted">Clic=ouvrir • Ctrl/Cmd=multi • Shift=intervalle • Dbl-clic=ajouter/retirer</small>
          </div>

          <b-input-group size="sm" class="w-auto">
            <b-input-group-text class="px-1"><i class="ti ti-briefcase"></i></b-input-group-text>
            <b-form-select v-model="selectedProjectId" :options="projectOptions" class="px-1" />
          </b-input-group>
        </div>
      </b-col>
    </b-row>

    <!-- Tabs -->
    <b-card no-body class="mb-1 shadow-none border-0">
      <b-card-body class="p-1">
        <b-button-group size="sm">
          <b-button @click="activeTab='mpa'"     :variant="activeTab==='mpa'?'primary':'outline-primary'"><i class="ti ti-topology-star-3 me-1"></i>MPA</b-button>
          <b-button @click="activeTab='funcs'"   :variant="activeTab==='funcs'?'primary':'outline-primary'"><i class="ti ti-users-group me-1"></i>Fonctions</b-button>
          <b-button v-if="hasMpsResp" @click="activeTab='mpsresp'" :variant="activeTab==='mpsresp'?'primary':'outline-primary'"><i class="ti ti-user-cog me-1"></i>MPS/Resp</b-button>
        </b-button-group>
      </b-card-body>
    </b-card>

    <!-- ========================================================= -->
    <!-- =============== Onglet 1 : MPA → ENTITÉ ================= -->
    <!-- ========================================================= -->
    <b-row v-if="activeTab==='mpa'" class="g-1">
      <!-- Arbo MPA -->
      <b-col lg="6" xl="6">
        <b-card no-body class="shadow-sm h-100">
          <b-card-header class="py-1 px-2 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
              <i class="ti ti-hierarchy-3"></i>
              <span class="fw-semibold">MPA ({{ selectedProjectName || '—' }})</span>
            </div>
          </b-card-header>

          <b-card-body class="p-1" style="max-height:72vh;overflow:auto;">
            <div v-if="!selectedProjectId" class="text-muted small">Choisir un projet.</div>
            <div v-else>
              <div v-if="mpa.netErr" class="alert alert-danger py-1 px-2 small mb-1">{{ mpa.netErr }}</div>
              <div v-else-if="mpa.loading" class="text-muted small">Chargement…</div>

              <ul v-else class="list-unstyled mb-0">
                <!-- MACRO -->
                <li v-for="m in mpa.tree" :key="'m'+m.id" class="mb-1">
                  <div class="n d-flex align-items-center gap-2"
                       draggable="true"
                       @dragstart="e=>onDragStartMpa(e,{type:'macro',id:m.id})">
                    <input class="form-check-input me-1" type="checkbox"
                           :checked="mpaIsSelected({type:'macro',id:m.id})"
                           @change="ev=>mpaToggleSelected({type:'macro',id:m.id},ev,{macroId:m.id})"
                           @click.stop />
                    <button class="btn btn-xs btn-light" @click.stop="toggleExpand('macro',m.id)">
                      <i :class="isOpen('macro',m.id)?'ti ti-chevron-down':'ti ti-chevron-right'"></i>
                    </button>
                    <span class="ico color-type-macro"><i class="ti ti-folder"></i><span class="L">M</span></span>
                    <span class="code">{{ m.code||'—' }}</span>
                    <span class="lbl" @click="toggleExpand('macro',m.id)">{{ m.name }}</span>
                  </div>

                  <!-- PROCESS -->
                  <ul v-if="isOpen('macro',m.id)" class="mt-1 ms-4">
                    <li v-for="p in m.children" :key="'p'+p.id" class="mb-1">
                      <div class="n d-flex align-items-center gap-2"
                           draggable="true"
                           @dragstart="e=>onDragStartMpa(e,{type:'process',id:p.id})"
                           @dblclick="mpaQuickAdd({type:'process',id:p.id})">
                        <input class="form-check-input me-1" type="checkbox"
                               :checked="mpaIsSelected({type:'process',id:p.id})"
                               @change="ev=>mpaToggleSelected({type:'process',id:p.id},ev,{macroId:m.id})"
                               @click.stop />
                        <button class="btn btn-xs btn-light" @click.stop="toggleExpand('process',p.id)">
                          <i :class="isOpen('process',p.id)?'ti ti-chevron-down':'ti ti-chevron-right'"></i>
                        </button>
                        <span class="ico color-type-process"><i class="ti ti-folder"></i><span class="L">P</span></span>
                        <span class="code">{{ p.code||'—' }}</span>
                        <span class="lbl" @click="toggleExpand('process',p.id)">{{ p.name }}</span>
                      </div>

                      <!-- ACTIVITÉS -->
                      <ul v-if="isOpen('process',p.id)" class="mt-1 ms-4">
                        <li v-for="a in p.children" :key="'a'+a.id">
                          <div class="it d-inline-flex align-items-center gap-2"
                               draggable="true"
                               @dragstart="e=>onDragStartMpa(e,{type:'activity',id:a.id})"
                               @dblclick="mpaQuickAdd({type:'activity',id:a.id})">
                            <input class="form-check-input me-1" type="checkbox"
                                   :checked="mpaIsSelected({type:'activity',id:a.id})"
                                   @change="ev=>mpaToggleSelected({type:'activity',id:a.id},ev,{processId:p.id,macroId:m.id})"
                                   @click.stop />
                            <span class="ico color-type-activity"><i class="ti ti-file-text"></i><span class="L">A</span></span>
                            <span class="code">{{ a.code||'—' }}</span>
                            <span class="s">{{ a.name }}</span>
                          </div>
                        </li>
                      </ul>
                    </li>
                  </ul>
                </li>
              </ul>
            </div>
          </b-card-body>
        </b-card>
      </b-col>

      <!-- Panier MPA (hiérarchique) -->
      <b-col lg="6" xl="6">
        <b-card no-body class="shadow-sm h-100">
          <b-card-header class="py-1 px-2 d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="ti ti-inbox me-1"></i>Panier</span>
            <div class="d-flex align-items-center gap-1">
              <b-form-select size="sm" v-model="mpa.entityId" :options="entityOptions" style="min-width:220px" />
              <div class="form-check form-switch ms-1">
                <input class="form-check-input" type="checkbox" v-model="mpa.replace" id="repMpa">
                <label class="form-check-label xsmall" for="repMpa">Remplacer</label>
              </div>
              <button class="btn btn-xs btn-light ms-1 rotateable" :class="{'rot-90': ui.mpa.open}" @click="toggleBasket('mpa')" title="Plier/Déplier">
                <i class="ti ti-chevron-right"></i>
              </button>
            </div>
          </b-card-header>

          <b-card-body class="p-1" @dragover="onDragOver" @drop="e=>onDrop(e,'mpa')">
            <!-- Zone pliable (drop + actions + preview + debug) -->
            <b-collapse :visible="ui.mpa.open">
              <div class="drop p-2 text-center mb-2">
                <div v-if="!mpa.staged.length" class="muted xsmall">
                  <i class="ti ti-upload me-1"></i>Déposer M/P/A (multi-sélection)
                </div>

                <div v-else class="text-start">
                  <!-- MACRO -->
                  <div v-for="(M,mi) in mpa.staged" :key="'SM'+M.id" class="rowit d-flex flex-column">
                    <div class="d-flex align-items-center justify-content-between" @dblclick="removeMacro(mi)">
                      <div class="d-flex align-items-center gap-2">
                        <span class="ico color-type-macro"><i class="ti ti-folder"></i><span class="L">M</span></span>
                        <span v-if="M.code" class="code">{{ M.code }}</span>
                        <span class="s fw-semibold">{{ M.name }}</span>
                      </div>
                      <button class="btn btn-link text-danger btn-xs p-0" @click="removeMacro(mi)"><i class="ti ti-x"></i></button>
                    </div>

                    <!-- PROCESS -->
                    <div v-for="(P,pi) in M.children" :key="'SP'+M.id+':'+P.id" class="ms-3 mt-1 d-flex flex-column">
                      <div class="d-flex align-items-center justify-content-between" @dblclick="removeProcess(mi,pi)">
                        <div class="d-flex align-items-center gap-2">
                          <span class="ico color-type-process"><i class="ti ti-folder"></i><span class="L">P</span></span>
                          <span v-if="P.code" class="code">{{ P.code }}</span>
                          <span class="s">{{ P.name }}</span>
                        </div>
                        <button class="btn btn-link text-danger btn-xs p-0" @click="removeProcess(mi,pi)"><i class="ti ti-x"></i></button>
                      </div>

                      <!-- ACTIVITIES -->
                      <div v-for="(A,ai) in P.children" :key="'SA'+M.id+':'+P.id+':'+A.id" class="ms-3 mt-1 d-flex align-items-center justify-content-between" @dblclick="removeActivity(mi,pi,ai)">
                        <div class="d-flex align-items-center gap-2">
                          <span class="ico color-type-activity"><i class="ti ti-file-text"></i><span class="L">A</span></span>
                          <span v-if="A.code" class="code">{{ A.code }}</span>
                          <span class="s">{{ A.name }}</span>
                        </div>
                        <button class="btn btn-link text-danger btn-xs p-0" @click="removeActivity(mi,pi,ai)"><i class="ti ti-x"></i></button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="d-flex align-items-center justify-content-between mb-1">
                <div class="d-flex align-items-center gap-1">
                  <button class="btn btn-xs btn-outline-secondary" @click="clearBasket('mpa')">Vider</button>
                  <button class="btn btn-xs btn-outline-primary" :disabled="!mpa.staged.length || !mpa.entityId" @click="doPreview"><i class="ti ti-refresh"></i> Prévisualiser</button>
                </div>
                <button class="btn btn-xs btn-primary" 
                        :disabled="!mpa.entityId || mpa.preview.count===0" 
                        @click="commitMpa"
                        id="btn-validate-mpa">
                  <i class="ti ti-check me-1"></i>Valider ({{ mpa.preview.count }})
                </button>
              </div>

              <div class="box p-2 mb-1">
                <span class="xsmall muted">Activités distinctes : </span><b class="xsmall">{{ mpa.preview.count }}</b>
              </div>

             
            </b-collapse>

            <!-- Partie NON pliable : déjà affectées -->
            <div class="box p-2 mt-2">
              <div class="fw-semibold xsmall mb-1">Déjà affectées</div>
              <div v-if="!mpa.entityId" class="xsmall muted">—</div>
              <div v-else-if="!mpa.current.activities?.length" class="xsmall muted">Aucune</div>
              <ul v-else class="xsmall" style="max-height:140px;overflow:auto;">
                <li v-for="a in mpa.current.activities" :key="'c'+a.id">{{ a.name }}</li>
              </ul>
            </div>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>

    <!-- ========================================================= -->
    <!-- ============= Onglet 2 : FONCTIONS → ENTITÉ ============ -->
    <!-- ========================================================= -->
    <b-row v-else-if="activeTab==='funcs'" class="g-1">
      <!-- Liste/Arbo Fonctions -->
      <b-col lg="6" xl="6">
        <b-card no-body class="shadow-sm h-100">
          <b-card-header class="py-1 px-2 d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="ti ti-users-group me-1"></i>Fonctions ({{ selectedProjectName||'—' }})</span>
          </b-card-header>
          <b-card-body class="p-1" style="max-height:72vh;overflow:auto;">
            <div v-if="!selectedProjectId" class="text-muted xsmall">Choisir un projet.</div>

            <div v-else>
              <div v-if="funcs.netErr" class="alert alert-danger py-1 px-2 xsmall mb-1">{{ funcs.netErr }}</div>
              <div v-else-if="funcs.loading" class="text-muted xsmall">Chargement…</div>

              <!-- Hiérarchie -->
              <template v-else-if="hasFunctionHierarchy">
                <ul class="list-unstyled mb-0">
                  <li v-for="f in funcs.tree" :key="'f'+f.id" class="mb-1">
                    <div class="n d-flex align-items-center gap-2"
                         draggable="true"
                         @dragstart="e=>onDragStartFuncs(e,{id:f.id,type:'function'})"
                         @dblclick="funcsQuickAdd(f.id)">
                      <input class="form-check-input me-1" type="checkbox"
                             :checked="funcsIsSelected(f.id)"
                             @change="ev=>funcsToggleSelected({id:f.id,parentId:null},ev)"
                             @click.stop />
                      <button class="btn btn-xs btn-light" @click.stop="funcsToggle(f)">
                        <i :class="funcsIsOpen(f)?'ti ti-chevron-down':'ti ti-chevron-right'"></i>
                      </button>
                      <span class="ico color-type-function"><i class="ti ti-user"></i><span class="L">F</span></span>
                      <span class="lbl">{{ f.name }}</span>
                    </div>

                    <ul v-if="funcsIsOpen(f) && f.children?.length" class="mt-1 ms-4">
                      <li v-for="c in f.children" :key="'fc'+c.id" class="mb-1">
                        <div class="it d-inline-flex align-items-center gap-2"
                             draggable="true"
                             @dragstart="e=>onDragStartFuncs(e,{id:c.id,type:'function'})"
                             @dblclick="funcsQuickAdd(c.id)">
                          <input class="form-check-input me-1" type="checkbox"
                                 :checked="funcsIsSelected(c.id)"
                                 @change="ev=>funcsToggleSelected({id:c.id,parentId:f.id},ev)"
                                 @click.stop />
                          <i class="ti ti-grip-vertical text-muted"></i>
                          <span class="s">{{ c.name }}</span>
                        </div>
                      </li>
                    </ul>
                  </li>
                </ul>
              </template>

              <!-- Liste plate -->
              <template v-else>
                <div v-for="(f,i) in funcs.list" :key="'fl'+f.id" class="rowit d-flex align-items-center justify-content-between"
                     draggable="true"
                     @dragstart="e=>onDragStartFuncs(e,{id:f.id,type:'function'})"
                     @dblclick="funcsQuickAdd(f.id)">
                  <div class="d-flex align-items-center gap-2">
                    <input class="form-check-input me-1" type="checkbox"
                           :checked="funcsIsSelected(f.id)"
                           @change="ev=>funcsToggleSelected({id:f.id,parentId:'root',index:i},ev)"
                           @click.stop />
                    <span class="ico color-type-function"><i class="ti ti-user"></i><span class="L">F</span></span>
                    <span class="s">{{ f.name }}</span>
                  </div>
                  <button class="btn btn-xs btn-light">Glisser</button>
                </div>
              </template>
            </div>
          </b-card-body>
        </b-card>
      </b-col>

      <!-- Panier Fonctions -->
      <b-col lg="6" xl="6">
        <b-card no-body class="shadow-sm h-100">
          <b-card-header class="py-1 px-2 d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="ti ti-inbox me-1"></i>Panier</span>
            <div class="d-flex align-items-center gap-1">
              <b-form-select size="sm" v-model="funcs.entityId" :options="entityOptions" style="min-width:220px" />
              <div class="form-check form-switch ms-1">
                <input class="form-check-input" type="checkbox" v-model="funcs.replace" id="repFn">
                <label class="form-check-label xsmall" for="repFn">Remplacer</label>
              </div>
              <button class="btn btn-xs btn-light ms-1 rotateable" :class="{'rot-90': ui.funcs.open}" @click="toggleBasket('funcs')" title="Plier/Déplier">
                <i class="ti ti-chevron-right"></i>
              </button>
            </div>
          </b-card-header>

          <b-card-body class="p-1" @dragover="onDragOver" @drop="e=>onDrop(e,'funcs')">
            <!-- Zone pliable (drop + actions) -->
            <b-collapse :visible="ui.funcs.open">
              <div class="drop p-2 text-center mb-2">
                <div v-if="!funcs.staged.length" class="muted xsmall"><i class="ti ti-upload me-1"></i>Déposer des fonctions (multi)</div>

                <div v-else class="text-start">
                  <div v-for="(n,i) in funcs.staged" :key="'fn'+n.id" class="rowit d-flex align-items-center justify-content-between" @dblclick="removeFunc(i)">
                    <div class="d-flex align-items-center gap-2">
                      <input class="form-check-input me-1" type="checkbox"
                             :checked="funcsStagedIsSelected(n.id)"
                             @change="ev=>funcsStagedToggle(n.id,ev)"
                             @click.stop />
                      <span class="ico color-type-function"><i class="ti ti-user"></i><span class="L">F</span></span>
                      <span class="s">{{ n.name }}</span>
                    </div>
                    <button class="btn btn-link text-danger btn-xs p-0" @click="removeFunc(i)"><i class="ti ti-x"></i></button>
                  </div>
                </div>
              </div>

              <div class="d-flex align-items-center justify-content-between mb-1">
                <div class="d-flex align-items-center gap-1">
                  <button class="btn btn-xs btn-outline-secondary" @click="clearBasket('funcs')">Vider</button>
                  <button class="btn btn-xs btn-outline-danger" :disabled="!funcsStagedSel.size" @click="removeSelectedFuncs"><i class="ti ti-trash"></i> Supprimer sélection</button>
                </div>
                <button class="btn btn-xs btn-primary" :disabled="!funcs.entityId || !funcs.staged.length" @click="commitFuncs"><i class="ti ti-check me-1"></i>Valider</button>
              </div>
            </b-collapse>

            <!-- Partie NON pliable : déjà affectées -->
            <div class="box p-2">
              <div class="fw-semibold xsmall mb-1">Déjà affectées</div>
              <div v-if="!funcs.entityId" class="xsmall muted">—</div>
              <div v-else-if="!funcs.current?.length" class="xsmall muted">Aucune</div>
              <ul v-else class="xsmall" style="max-height:140px;overflow:auto;">
                <li v-for="f in funcs.current" :key="'cf'+f.id">{{ f.name }}</li>
              </ul>
            </div>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>

    <!-- ========================================================= -->
    <!-- =========== Onglet 3 : MPS / Responsable (table) ======== -->
    <!-- ========================================================= -->
    <b-row v-else-if="activeTab==='mpsresp'" class="g-1">
      <b-col>
        <b-card no-body class="shadow-sm">
          <b-card-header class="py-1 px-2 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
              <span class="fw-semibold"><i class="ti ti-hierarchy-3 me-1"></i> MPS / Responsable</span>
              <small v-if="mpsresp.netErr" class="text-danger">{{ mpsresp.netErr }}</small>
            </div>
            <div class="d-flex align-items-center gap-2">
              <div class="d-flex align-items-center gap-1">
                <span class="xsmall text-muted">Entité</span>
                <b-form-select size="sm" v-model="mpsresp.entityId" :options="entityOptions" style="min-width:220px" />
              </div>
              <div class="d-flex align-items-center gap-1">
                <span class="xsmall text-muted">Car.</span>
                <b-form-input size="sm" disabled placeholder="—" style="width:90px" />
              </div>
            </div>
          </b-card-header>

          <b-card-body class="p-1">
            <div v-if="!selectedProjectId" class="muted xsmall">Choisir un projet.</div>
            <div v-else-if="!mpsresp.entityId" class="muted xsmall">Choisir une entité.</div>

            <div v-else class="mb-1 d-flex align-items-center justify-content-between">
              <div class="xsmall text-muted">
                MPA liés : <b>{{ mpsresp.rows.length }}</b> • Fonctions de l'entité : <b>{{ mpsresp.functionOptions.length }}</b>
              </div>
              <div class="d-flex align-items-center gap-1">
                <b-button size="sm" variant="outline-secondary" :disabled="mpsresp.loading" @click="reloadMpsResp">
                  <i class="ti ti-refresh"></i>
                </b-button>
                <b-button size="sm" variant="primary" :disabled="mpsresp.loading || !mpsresp.dirtyKeys.size" @click="saveMpsResp">
                  <i class="ti ti-check me-1"></i> Valider
                </b-button>
                <b-button size="sm" variant="outline-danger" :disabled="mpsresp.loading || !mpsresp.dirtyKeys.size" @click="discardChanges">
                  Annuler
                </b-button>
              </div>
            </div>

            <div class="table-responsive" style="max-height:70vh;overflow:auto;">
              <table class="table table-sm align-middle">
                <thead class="table-light sticky-top">
                  <tr>
                    <th style="width:28px"></th>
                    <th style="width:280px">Arbre</th>
                    <th>Désignation</th>
                    <th style="width:280px">Responsable</th>
                  </tr>
                </thead>
                <tbody v-if="mpsresp.loading">
                  <tr><td colspan="4" class="text-muted xsmall">Chargement…</td></tr>
                </tbody>
                <tbody v-else-if="!mpsresp.rows.length">
                  <tr><td colspan="4" class="text-muted xsmall">Aucun MPA affecté à cette entité.</td></tr>
                </tbody>
                <tbody v-else>
                  <tr v-for="r in mpsresp.rows" :key="rowKey(r)">
                    <td class="text-center">
                      <span class="icon-badge" :class="badgeColor(r.type)">
                        <i :class="r.type==='activity'?'ti ti-file-text':'ti ti-folder'" class="node-icon"></i>
                        <span class="badge-letter">{{ letterFor(r.type) }}</span>
                      </span>
                    </td>
                    <td>
                      <div :style="{paddingLeft: indentPx(r)}" class="d-flex align-items-center gap-2">
                        <span class="code" v-if="r.code">{{ r.code }}</span>
                      </div>
                    </td>
                    <td>{{ r.name }}</td>
                    <td>
                      <b-form-select size="sm"
                        :class="isDirty(r) ? 'is-changed' : ''"
                        v-model="r.function_id"
                        :options="mpsresp.functionOptions"
                        @change="markDirty(r)">
                      </b-form-select>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

          </b-card-body>
        </b-card>
      </b-col>
    </b-row>
  </VerticalLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { computed, ref, watch, onMounted } from 'vue'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

/* ===================== PROPS ===================== */
const props = defineProps({
  projects: { type:Array, default:()=>[] },
  entities: { type:Array, default:()=>[] },
  routes:   { type:Object, default:()=>({ mpa:{}, funcs:{}, mpsresp:{} }) },
})

/* ===================== STATE GLOBAL ===================== */
const activeTab = ref('mpa')
const selectedProjectId = ref(props.projects?.[0]?.id ?? null)
const projectOptions = computed(()=>[
  { value:null, text:'— Projet —', disabled:true },
  ...props.projects.map(p=>({ value:p.id, text:p.name }))
])
const selectedProjectName = computed(()=> props.projects.find(p=>String(p.id)===String(selectedProjectId.value))?.name ?? '')

const entityOptions = computed(()=>{
  if (!selectedProjectId.value) return [{ value:null, text:'— Entité —', disabled:true }]
  const list = props.entities.filter(e=>String(e.project_id)===String(selectedProjectId.value))
  return [{ value:null, text:list.length?'— Entité —':'— Aucune —', disabled:true }, ...list.map(e=>({ value:e.id, text:e.name }))]
})

// UI (mémoire de pliage)
const UI_KEYS = { mpa:'ddm.repartition.ui.mpa.open', funcs:'ddm.repartition.ui.funcs.open' }
const ui = ref({
  mpa: { open: localStorage.getItem(UI_KEYS.mpa) !== '0' },
  funcs: { open: localStorage.getItem(UI_KEYS.funcs) !== '0' },
})
function toggleBasket(area){
  if(area==='mpa'){ ui.value.mpa.open = !ui.value.mpa.open; localStorage.setItem(UI_KEYS.mpa, ui.value.mpa.open? '1':'0') }
  if(area==='funcs'){ ui.value.funcs.open = !ui.value.funcs.open; localStorage.setItem(UI_KEYS.funcs, ui.value.funcs.open? '1':'0') }
}

// Debug state
const debug = ref({ show: true, lastAction: '' })

/* Headers fetch */
const baseHeaders = {
  'Accept':'application/json',
  'Content-Type':'application/json',
  'X-Requested-With':'XMLHttpRequest',
  'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]')?.content ?? ''
}

/* Helpers communs */
const badgeColor = t => t==='macro'?'color-type-macro':(t==='process'?'color-type-process':(t==='activity'?'color-type-activity':'color-type-function'))
const letterFor  = t => t==='macro'?'M':(t==='process'?'P':'A')

/* ========================================================= */
/* ====================== ONGLET MPA ======================== */
/* ========================================================= */
const mpa = ref({
  loading:false, netErr:null, tree:[],
  expanded:{},
  entityId:null,
  staged:[],
  preview:{count:0,activity_ids:[]},
  current:{activity_ids:[],activities:[]},
  replace:false
})

/* index+ordre pour insertion */
const idx = ref({ macros:new Map(), processes:new Map(), activities:new Map() })
const order = ref({ processByMacro:{}, activityByProcess:{} })

function rebuildIndex(){
  const macros=new Map(), procs=new Map(), acts=new Map()
  const processByMacro={}, activityByProcess={}
  for (const m of (mpa.value.tree||[])){
    const mid = String(m.id)
    macros.set(mid, { id:mid, name:m.name, code:m.code })
    processByMacro[mid] = []
    ;(m.children||[]).forEach(p=>{
      const pid=String(p.id)
      processByMacro[mid].push(pid)
      procs.set(pid, { id:pid, name:p.name, code:p.code, macroId:mid })
      activityByProcess[pid] = []
      ;(p.children||[]).forEach(a=>{
        const aid=String(a.id)
        activityByProcess[pid].push(aid)
        acts.set(aid, { id:aid, name:a.name, code:a.code, processId:pid, macroId:mid })
      })
    })
  }
  idx.value = { macros, processes:procs, activities:acts }
  order.value = { processByMacro, activityByProcess }
}

const keyFor = (type,id)=>`${type}:${id}`
const isOpen = (type,id)=> !!mpa.value.expanded[keyFor(type,id)]
const toggleExpand = (type,id)=>{ const k=keyFor(type,id); mpa.value.expanded[k]=!mpa.value.expanded[k] }

async function loadMpaTree(){
  if (!selectedProjectId.value) return
  mpa.value.loading=true; mpa.value.netErr=null
  try{
    const url = new URL(props.routes.mpa.tree, window.location.origin)
    url.searchParams.set('project_id', selectedProjectId.value)
    const res = await fetch(url, { headers: baseHeaders })
    if(!res.ok) throw new Error(`HTTP ${res.status}`)
    const j = await res.json()
    mpa.value.tree = Array.isArray(j)?j:[]
    rebuildIndex()
  }catch(e){ 
    mpa.value.netErr=String(e?.message||e); 
    mpa.value.tree=[]; 
    rebuildIndex() 
  }
  finally{ mpa.value.loading=false }
}

async function loadMpaCurrent(){
  if (!mpa.value.entityId){ 
    mpa.value.current={activity_ids:[],activities:[]}; 
    return 
  }
  try{
    const url = new URL(props.routes.mpa.current, window.location.origin)
    url.searchParams.set('entity_id', mpa.value.entityId)
    url.searchParams.set('project_id', selectedProjectId.value)
    const resp = await fetch(url, { headers: baseHeaders })
    if (!resp.ok) throw new Error(`HTTP ${resp.status}`)
    mpa.value.current = await resp.json()
  }catch(e){
    mpa.value.netErr = String(e?.message || e)
    mpa.value.current = { activity_ids:[], activities:[] }
  }
}

function ensureMacro(macroId, macroName, macroCode){
  const M = mpa.value.staged.find(x=>x.type==='macro' && String(x.id)===String(macroId))
  if (M) return M
  const found = idx.value.macros.get(String(macroId))
  const node = { type:'macro', id:String(macroId), name:macroName||found?.name||'', code:macroCode||found?.code, children:[] }
  mpa.value.staged.push(node)
  return node
}

function ensureProcess(macroNode, processId, processName, processCode){
  const P = macroNode.children.find(x=>x.type==='process' && String(x.id)===String(processId))
  if (P) return P
  const found = idx.value.processes.get(String(processId))
  const node = { type:'process', id:String(processId), name:processName||found?.name||'', code:processCode||found?.code, children:[] }
  const list = macroNode.children
  const ord = order.value.processByMacro[String(macroNode.id)] || []
  const target = ord.indexOf(String(processId))
  let at = list.length
  if (target !== -1){
    for (let i=0;i<list.length;i++){
      const pos = ord.indexOf(String(list[i].id))
      if (pos !== -1 && pos > target){ at=i; break }
    }
  }
  list.splice(at,0,node)
  return node
}

function ensureActivity(processNode, activityId, activityName, activityCode){
  const exists = processNode.children.find(x=>String(x.id)===String(activityId))
  if (exists) return exists
  const found = idx.value.activities.get(String(activityId))
  const node = { type:'activity', id:String(activityId), name:activityName||found?.name||'', code:activityCode||found?.code }
  const list = processNode.children
  const ord = order.value.activityByProcess[String(processNode.id)] || []
  const target = ord.indexOf(String(activityId))
  let at = list.length
  if (target !== -1){
    for (let i=0;i<list.length;i++){
      const pos = ord.indexOf(String(list[i].id))
      if (pos !== -1 && pos > target){ at=i; break }
    }
  }
  list.splice(at,0,node)
  return node
}

function flattenNodes(){
  const nodes=[]
  for (const M of mpa.value.staged){
    if (!M.children.length) nodes.push({ id:M.id, type:'macro' })
    for (const P of M.children){
      if (!P.children.length) nodes.push({ id:P.id, type:'process' })
      for (const A of P.children) nodes.push({ id:A.id, type:'activity' })
    }
  }
  return nodes
}

async function doPreview(){
  if (!mpa.value.entityId || !mpa.value.staged.length){ 
    mpa.value.preview={count:0,activity_ids:[]}; 
    return 
  }
  const nodes = flattenNodes();
  try {
    const res = await fetch(props.routes.mpa.preview, { 
      method:'POST', 
      headers: baseHeaders, 
      body: JSON.stringify({ project_id: selectedProjectId.value, entity_id: mpa.value.entityId, nodes })
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const result = await res.json();
    mpa.value.preview = result;
  } catch(e) {
    mpa.value.preview = {count:0, activity_ids:[]};
  }
}

async function commitMpa(){
  const ids = Array.isArray(mpa.value.preview?.activity_ids) ? mpa.value.preview.activity_ids : []
  if (!mpa.value.entityId || ids.length === 0) return
  try{
    const payload = { project_id: selectedProjectId.value, entity_id: mpa.value.entityId, activity_ids: ids, replace: !!mpa.value.replace }
    const res = await fetch(props.routes.mpa.commit, { method:'POST', headers: baseHeaders, body: JSON.stringify(payload) })
    if (!res.ok){
      let msg = `HTTP ${res.status}`
      try { const j = await res.json(); if (j?.message) msg = j.message } catch {}
      throw new Error(msg)
    }
    await res.json()
    mpa.value.staged = []
    mpa.value.preview = { count: 0, activity_ids: [] }
    await loadMpaCurrent()
  }catch(e){
    mpa.value.netErr = `Échec de l'enregistrement: ${e?.message || e}`
  }
}

/* Sélection multi MPA */
const mpaSel = ref(new Set())
const mpaLast = ref(null)
const mpaLastCtx = ref(null)
const mpaKey = (n)=>`${n.type}:${n.id}`
const mpaIsSelected = (n)=> mpaSel.value.has(mpaKey(n))

function mpaSetSel(up){ const s=new Set(mpaSel.value); up(s); mpaSel.value=s }

function mpaSameParent(curr, ctx, prevCtx){
  if (!ctx || !prevCtx) return false
  if (curr.type==='process')  return String(ctx.macroId)  === String(prevCtx.macroId)
  if (curr.type==='activity') return String(ctx.processId)=== String(prevCtx.processId)
  return false
}

function mpaSiblingIds(curr, ctx){
  if (curr.type==='process')  return order.value.processByMacro[String(ctx.macroId)] || []
  if (curr.type==='activity') return order.value.activityByProcess[String(ctx.processId)] || []
  return []
}

function mpaToggleSelected(node, ev, ctx){
  const additive = ev?.metaKey || ev?.ctrlKey
  const ranged  = ev?.shiftKey
  const key = mpaKey(node)
  
  if (ranged && mpaLast.value && mpaSameParent(node, ctx, mpaLastCtx.value)){
    const ids = mpaSiblingIds(node, ctx)
    const i1  = ids.indexOf(String(mpaLast.value.id))
    const i2  = ids.indexOf(String(node.id))
    if (i1>-1 && i2>-1){
      const [a,b] = i1<i2 ? [i1,i2] : [i2,i1]
      mpaSetSel(s=>{ if(!additive) s.clear(); for(let i=a;i<=b;i++) s.add(`${node.type}:${ids[i]}`) })
    }else{
      mpaSetSel(s=>{ if(!additive) s.clear(); s.add(key) })
    }
  }else{
    mpaSetSel(s=>{ if(!additive) s.clear(); if (s.has(key)) s.delete(key); else s.add(key) })
  }
  mpaLast.value = { type: node.type, id: String(node.id) }
  mpaLastCtx.value = ctx || null
}

/* Dbl-clic ajout rapide */
function mpaQuickAdd(payload){
  if (!mpa.value.entityId) return
  if (payload.type==='macro'){
    const m = idx.value.macros.get(String(payload.id)); if (!m) return
    ensureMacro(m.id, m.name, m.code)
  } else if (payload.type==='process'){
    const p = idx.value.processes.get(String(payload.id)); if (!p) return
    const M = ensureMacro(p.macroId)
    ensureProcess(M, p.id)
  } else if (payload.type==='activity'){
    const a = idx.value.activities.get(String(payload.id)); if (!a) return
    const M = ensureMacro(a.macroId)
    const P = ensureProcess(M, a.processId)
    ensureActivity(P, a.id)
  }
  doPreview()
}

/* DnD MPA */
function makeDragItemMpa(type, id){
  id = String(id)
  if (type==='macro'){ const m=idx.value.macros.get(id); if(!m) return null; return { type, id, macroId:id, name:m.name, code:m.code } }
  if (type==='process'){ const p=idx.value.processes.get(id); if(!p) return null; return { type, id, macroId:p.macroId, name:p.name, code:p.code } }
  if (type==='activity'){ const a=idx.value.activities.get(id); if(!a) return null; return { type, id, processId:a.processId, macroId:a.macroId, name:a.name, code:a.code } }
  return null
}

function onDragStartMpa(ev, node){
  let items=[]
  if (mpaIsSelected(node)){
    mpaSel.value.forEach(k=>{
      const [t,i]=k.split(':'); if(!['macro','process','activity'].includes(t)) return
      const item = makeDragItemMpa(t,i); if(item) items.push(item)
    })
  }else{
    const item = makeDragItemMpa(node.type, node.id); if (item) items=[item]
  }
  ev.dataTransfer.setData('application/json', JSON.stringify({ items, area:'mpa' }))
  ev.dataTransfer.effectAllowed='copy'
}

/* Suppressions panier MPA */
function removeMacro(mi){ mpa.value.staged.splice(mi,1); doPreview() }
function removeProcess(mi,pi){ const M = mpa.value.staged[mi]; if(!M) return; M.children.splice(pi,1); if(!M.children.length) mpa.value.staged.splice(mi,1); doPreview() }
function removeActivity(mi,pi,ai){ const P = mpa.value.staged[mi]?.children?.[pi]; if(!P) return; P.children.splice(ai,1); if(!P.children.length){ mpa.value.staged[mi].children.splice(pi,1); if(!mpa.value.staged[mi].children.length) mpa.value.staged.splice(mi,1) } doPreview() }

/* ========================================================= */
/* ==================== ONGLET FONCTIONS =================== */
/* ========================================================= */
const funcs = ref({ loading:false, netErr:null, list:[], tree:[], expanded:{}, entityId:null, staged:[], current:[], replace:false })
const hasFunctionHierarchy = computed(()=> funcs.value.tree?.length>0 )
const funcsIsOpen = n => !!funcs.value.expanded[`function:${n.id}`]
const funcsToggle = n => { const k=`function:${n.id}`; funcs.value.expanded[k]=!funcs.value.expanded[k] }

const funcsIdx = ref(new Map())
const funcOrderByParent = ref({})
const funcOrderFlat = ref([])

function rebuildFuncIndex(){
  funcsIdx.value = new Map()
  funcOrderByParent.value = {}
  funcOrderFlat.value = []

  if (hasFunctionHierarchy.value){
    const walk=(arr,parentId=null)=>{
      if(!arr) return
      funcOrderByParent.value[String(parentId??'root')] = funcOrderByParent.value[String(parentId??'root')] || []
      for(const it of arr){
        funcsIdx.value.set(String(it.id), { id:String(it.id), name:it.name, parentId: parentId?String(parentId):'root' })
        funcOrderByParent.value[String(parentId??'root')].push(String(it.id))
        if(Array.isArray(it.children)&&it.children.length){ walk(it.children, it.id) }
      }
    }
    walk(funcs.value.tree, null)
  }else{
    funcOrderByParent.value['root'] = []
    for(const [i,f] of (funcs.value.list||[]).entries()){
      funcsIdx.value.set(String(f.id), { id:String(f.id), name:f.name, parentId:'root', index:i })
      funcOrderByParent.value['root'].push(String(f.id))
      funcOrderFlat.value.push(String(f.id))
    }
  }
}

async function loadFunctions(){
  if (!selectedProjectId.value) return
  funcs.value.loading=true; funcs.value.netErr=null
  try{
    const url = new URL(props.routes.funcs.list, window.location.origin)
    url.searchParams.set('project_id', selectedProjectId.value)
    const res = await fetch(url, { headers: baseHeaders })
    if(!res.ok) throw new Error(`HTTP ${res.status}`)
    const j = await res.json()
    funcs.value.list = Array.isArray(j?.list) ? j.list : []
    funcs.value.tree = Array.isArray(j?.tree) ? j.tree : []
    rebuildFuncIndex()
  }catch(e){ funcs.value.netErr=String(e?.message||e); funcs.value.list=[]; funcs.value.tree=[]; rebuildFuncIndex() }
  finally{ funcs.value.loading=false }
}

async function loadFuncsCurrent(){
  if (!funcs.value.entityId){ funcs.value.current=[]; return }
  const url = new URL(props.routes.funcs.current, window.location.origin)
  url.searchParams.set('entity_id', funcs.value.entityId)
  url.searchParams.set('project_id', selectedProjectId.value)
  const res = await fetch(url, { headers: baseHeaders })
  const j = await res.json()
  funcs.value.current = Array.isArray(j) ? j : (j?.functions||[])
}

const funcsSel = ref(new Set())
const funcsLast = ref(null)
function funcsIsSelected(id){ return funcsSel.value.has(String(id)) }
function funcsSetSel(up){ const s=new Set(funcsSel.value); up(s); funcsSel.value=s }
function funcsSiblings(parentId){ return funcOrderByParent.value[String(parentId??'root')] || [] }

function funcsToggleSelected(node, ev){
  const id = String(node.id)
  const parentId = String(node.parentId ?? 'root')
  const additive = ev?.metaKey || ev?.ctrlKey
  const ranged  = ev?.shiftKey

  if (ranged && funcsLast.value && String(funcsLast.value.parentId)===parentId){
    const ids = funcsSiblings(parentId)
    const i1 = ids.indexOf(String(funcsLast.value.id))
    const i2 = ids.indexOf(id)
    if (i1>-1 && i2>-1){
      const [a,b] = i1<i2 ? [i1,i2] : [i2,i1]
      funcsSetSel(s=>{ if(!additive) s.clear(); for(let i=a;i<=b;i++) s.add(ids[i]) })
    } else {
      funcsSetSel(s=>{ if(!additive) s.clear(); s.add(id) })
    }
  } else {
    funcsSetSel(s=>{ if(!additive) s.clear(); if (s.has(id)) s.delete(id); else s.add(id) })
  }
  funcsLast.value = { id, parentId }
}

function funcsQuickAdd(id){ if (!funcs.value.entityId) return; const f = funcsIdx.value.get(String(id)); if(!f) return; addFunctionToStaged(f.id) }

function onDragStartFuncs(ev, node){
  let ids=[]
  if (funcsSel.value.has(String(node.id))) ids=[...funcsSel.value]
  else ids=[String(node.id)]
  const items = ids.map(id=>{ const f=funcsIdx.value.get(String(id)); if(!f) return null; return { type:'function', id:f.id, name:f.name } }).filter(Boolean)
  ev.dataTransfer.setData('application/json', JSON.stringify({ items, area:'funcs' }))
  ev.dataTransfer.effectAllowed='copy'
}

function addFunctionToStaged(id){
  id=String(id)
  if (funcs.value.staged.find(x=>String(x.id)===id)) return
  const f = funcsIdx.value.get(id); if(!f) return
  const list = funcs.value.staged
  const ord = funcOrderByParent.value['root'] || funcOrderFlat.value || []
  const target = ord.indexOf(id)
  let at = list.length
  if (target !== -1){
    for (let i=0;i<list.length;i++){
      const pos = ord.indexOf(String(list[i].id))
      if (pos !== -1 && pos > target){ at=i; break }
    }
  }
  list.splice(at,0,{ id, name:f.name })
}

function removeFunc(i){ funcs.value.staged.splice(i,1) }

const funcsStagedSel = ref(new Set())
function funcsStagedIsSelected(id){ return funcsStagedSel.value.has(String(id)) }
function funcsStagedToggle(id, ev){ const additive = ev?.metaKey || ev?.ctrlKey; id=String(id); const s=new Set(funcsStagedSel.value); if(!additive) s.clear(); if (s.has(id)) s.delete(id); else s.add(id); funcsStagedSel.value=s }
function removeSelectedFuncs(){ const ids = new Set(funcsStagedSel.value); funcs.value.staged = funcs.value.staged.filter(x=>!ids.has(String(x.id))); funcsStagedSel.value = new Set() }

async function commitFuncs(){
  if (!funcs.value.entityId || !funcs.value.staged.length) return
  const function_ids = funcs.value.staged.map(x=>x.id)
  await fetch(props.routes.funcs.commit, { method:'POST', headers: baseHeaders, body: JSON.stringify({
    project_id: selectedProjectId.value, entity_id: funcs.value.entityId, function_ids, replace: funcs.value.replace
  })})
  funcs.value.staged=[]; funcsStagedSel.value=new Set(); await loadFuncsCurrent()
}

/* ========================================================= */
/* =================== ONGLET MPS / RESPONSABLE ============ */
/* ========================================================= */
const hasMpsResp = computed(()=> !!(props.routes?.mpsresp && props.routes.mpsresp.assign) )
const rowKey     = (r)=> `${r.type}:${r.id}`
const indentPx   = (r)=> (r.type==='macro'?6:(r.type==='process'?22:40)) + 'px'

const mpsresp = ref({
  loading:false, netErr:null,
  entityId:null,
  rows:[],
  functionOptions:[],
  dirtyKeys:new Set(),
})

async function reloadMpsResp(){
  if (!selectedProjectId.value || !mpsresp.value.entityId){ mpsresp.value.rows=[]; mpsresp.value.functionOptions=[]; mpsresp.value.dirtyKeys=new Set(); return }
  mpsresp.value.loading=true; mpsresp.value.netErr=null
  try{
    // Fonctions de l'entité
    const urlF = new URL(props.routes.funcs.current, window.location.origin)
    urlF.searchParams.set('entity_id', mpsresp.value.entityId)
    urlF.searchParams.set('project_id', selectedProjectId.value)
    const rf = await fetch(urlF, { headers: baseHeaders })
    const jf = await rf.json()
    const funs = (Array.isArray(jf) ? jf : (jf?.functions||[])) || []
    mpsresp.value.functionOptions = funs.map(x=>({ value:String(x.id), text:x.name }))

    // Activités déjà affectées à l'entité
    const urlCur = new URL(props.routes.mpa.current, window.location.origin)
    urlCur.searchParams.set('entity_id', mpsresp.value.entityId)
    urlCur.searchParams.set('project_id', selectedProjectId.value)
    const rc = await fetch(urlCur, { headers: baseHeaders })
    const jc = await rc.json()
    const activityIds = new Set((jc?.activity_ids||[]).map(String))

    // Arbo projet
    const urlTree = new URL(props.routes.mpa.tree, window.location.origin)
    urlTree.searchParams.set('project_id', selectedProjectId.value)
    const rt = await fetch(urlTree, { headers: baseHeaders })
    const tree = await rt.json()

    // Filtrer arbo (garder uniquement M/P parents des activités affectées)
    const rows = []
    for (const M of (tree||[])){
      let macroTouched = false
      const procsOut = []
      for (const P of (M.children||[])){
        const actsOut = []
        for (const A of (P.children||[])){
          if (activityIds.has(String(A.id))){ actsOut.push({ type:'activity', id:String(A.id), code:A.code||'', name:A.name }) }
        }
        if (actsOut.length){ macroTouched = true; procsOut.push({ type:'process', id:String(P.id), code:P.code||'', name:P.name, children:actsOut }) }
      }
      if (macroTouched){ rows.push({ type:'macro', id:String(M.id), code:M.code||'', name:M.name, children:procsOut }) }
    }
    const flat=[]
    for (const M of rows){
      flat.push({ type:'macro', id:M.id, code:M.code, name:M.name })
      for (const P of M.children){
        flat.push({ type:'process', id:P.id, code:P.code, name:P.name })
        for (const A of P.children){ flat.push(A) }
      }
    }

    // Pré-remplir responsables actuels via mpsresp.current par fonction
    const mapKeyToFunc = new Map()
    for (const opt of mpsresp.value.functionOptions){
      const url = new URL(props.routes.mpsresp.current, window.location.origin)
      url.searchParams.set('entity_id', mpsresp.value.entityId)
      url.searchParams.set('function_id', opt.value)
      const r = await fetch(url, { headers: baseHeaders })
      const j = await r.json()
      const items = Array.isArray(j)?j:(j?.items||[])
      for (const it of items){
        const k = `${it.type}:${it.id}`
        if (!mapKeyToFunc.has(k)) mapKeyToFunc.set(k, String(opt.value))
      }
    }

    mpsresp.value.rows = flat.map(r=>{ const k = rowKey(r); const fid = mapKeyToFunc.get(k) || null; return { ...r, function_id: fid, _orig_function_id: fid } })
    mpsresp.value.dirtyKeys = new Set()
  }catch(e){
    mpsresp.value.netErr = String(e?.message||e)
    mpsresp.value.rows=[]; mpsresp.value.functionOptions=[]; mpsresp.value.dirtyKeys=new Set()
  }finally{ mpsresp.value.loading=false }
}

function markDirty(r){ const dirty = String(r.function_id||'') !== String(r._orig_function_id||''); const k = rowKey(r); const S = new Set(mpsresp.value.dirtyKeys); if (dirty) S.add(k); else S.delete(k); mpsresp.value.dirtyKeys = S }
function isDirty(r){ return mpsresp.value.dirtyKeys.has(rowKey(r)) }
function discardChanges(){ reloadMpsResp() }

async function saveMpsResp(){
  if (!mpsresp.value.entityId || !mpsresp.value.dirtyKeys.size) return
  mpsresp.value.loading=true
  try{
    const toAssignByFunc = new Map()
    const toUnlinkList   = []

    for (const r of mpsresp.value.rows){
      const k = rowKey(r)
      if (!mpsresp.value.dirtyKeys.has(k)) continue
      const newF = r.function_id ? String(r.function_id) : null
      const oldF = r._orig_function_id ? String(r._orig_function_id) : null

      if (oldF && oldF !== newF){ toUnlinkList.push({ id: r.id, type: r.type, function_id: oldF }) }
      if (newF){ if (!toAssignByFunc.has(newF)) toAssignByFunc.set(newF, []); toAssignByFunc.get(newF).push({ id: r.id, type: r.type }) }
    }

    // Unassign d'abord
    for (const item of toUnlinkList){
      await fetch(props.routes.mpsresp.unassign, { method:'POST', headers: baseHeaders, body: JSON.stringify({ entity_id: mpsresp.value.entityId, function_id: item.function_id, nodes: [{ id: Number(item.id), type: item.type }] }) })
    }

    // Assign groupé par fonction
    for (const [funcId, nodes] of toAssignByFunc.entries()){
      await fetch(props.routes.mpsresp.assign, { method:'POST', headers: baseHeaders, body: JSON.stringify({ entity_id: mpsresp.value.entityId, function_id: funcId, role: null, nodes: nodes.map(n=>({ id:Number(n.id), type:n.type })) }) })
    }

    // Succès local
    for (const r of mpsresp.value.rows){ const k=rowKey(r); if (!mpsresp.value.dirtyKeys.has(k)) continue; r._orig_function_id = r.function_id ? String(r.function_id) : null }
    mpsresp.value.dirtyKeys = new Set()
  } finally {
    mpsresp.value.loading=false
  }
}

/* ========================================================= */
/* ================== DnD commun & resets ================== */
/* ========================================================= */
function onDragOver(ev){ ev.preventDefault(); ev.dataTransfer.dropEffect='copy' }

function onDrop(ev, area){
  ev.preventDefault()
  const data = ev.dataTransfer.getData('application/json'); if(!data) return
  const payload = JSON.parse(data)
  if (payload.area!==area) return

  if (area==='mpa'){
    if (!mpa.value.entityId) return
    const items = Array.isArray(payload.items) ? payload.items : [payload]
    for (const node of items){
      if (node.type==='macro'){
        ensureMacro(node.macroId||node.id, node.name, node.code)
      } else if (node.type==='process'){
        const M = ensureMacro(node.macroId)
        ensureProcess(M, node.id, node.name, node.code)
      } else if (node.type==='activity'){
        const M = ensureMacro(node.macroId)
        const P = ensureProcess(M, node.processId)
        ensureActivity(P, node.id, node.name, node.code)
      }
    }
    doPreview()
  }
  else if (area==='funcs'){
    if (!funcs.value.entityId) return
    const items = Array.isArray(payload.items) ? payload.items : [payload]
    for (const it of items){ if (it.type==='function') addFunctionToStaged(it.id) }
  }
}

function clearBasket(area){
  if (area==='mpa'){ mpa.value.staged=[]; doPreview() }
  else if (area==='funcs'){ funcs.value.staged=[]; funcsStagedSel.value=new Set() }
}

/* ================== Watchers & Mount ================== */
watch(()=>mpa.value.entityId, loadMpaCurrent)
watch(()=>funcs.value.entityId, loadFuncsCurrent)
watch([selectedProjectId, ()=>mpsresp.value.entityId], reloadMpsResp)

watch(selectedProjectId, ()=>{
  resetMpa(); 
  resetFuncs(); 
  mpsresp.value.rows=[]; 
  mpsresp.value.functionOptions=[]; 
  mpsresp.value.dirtyKeys=new Set()
  if (activeTab.value!=='funcs') loadMpaTree()
  loadFunctions()
})

function resetMpa(){ 
  mpa.value={
    loading:false, netErr:null, tree:[],
    expanded:{}, entityId:null, staged:[],
    preview:{count:0,activity_ids:[]},
    current:{activity_ids:[],activities:[]},
    replace:false
  }; 
  rebuildIndex() 
}

function resetFuncs(){ 
  funcs.value={
    loading:false, netErr:null, list:[], tree:[], expanded:{},
    entityId:null, staged:[], current:[], replace:false
  }; 
  rebuildFuncIndex(); 
  funcsSel.value=new Set(); 
  funcsStagedSel.value=new Set() 
}

onMounted(()=>{ 
  if (selectedProjectId.value){ loadMpaTree(); loadFunctions(); } 
})
</script>

<style scoped>
:where(h6){ font-size:.82rem; margin:0 }
.small, small, .xsmall{ font-size:.72rem }
.xsmall{ font-size:.68rem }
.muted{ color:#6b7280 }

.btn-xs{ padding:.1rem .35rem; font-size:.68rem; line-height:1.1; border-radius:.28rem }
.btn, .form-control, .form-select{ padding:.2rem .45rem; font-size:.74rem; line-height:1.1; border-radius:.35rem }

.card, .b-card{ border-radius:.5rem }
.card-header, .b-card-header{ padding:.25rem .4rem!important }
.card-body, .b-card-body{ padding:.4rem!important }

.list-unstyled{ margin:0; padding-left:0 }
ul > li{ list-style:none }

.n{ padding:.15rem .2rem; border-radius:.35rem }
.n:hover{ background:#f8fafc }
.it{ padding:.1rem .25rem; border:1px solid #e5e7eb; border-radius:.35rem }
.rowit{ padding:.2rem .35rem; border:1px solid #eef2f7; border-radius:.35rem; margin-bottom:.25rem }
.rowit:hover{ background:#f0f9ff; border-color:#bfdbfe }

.drop{ background:#fafafa; border:1px dashed #d1d5db; border-radius:.4rem; min-height:80px }
.box{ background:#f8fafc; border:1px solid #e5e7eb; border-radius:.35rem }

.ico,
.icon-badge{
  position:relative;
  display:inline-flex;align-items:center;justify-content:center;
  width:20px;height:16px;border-radius:.35rem;
  background:transparent!important;
  border:1.5px solid var(--clr-type,#94a3b8);
}
.ico .ti,
.icon-badge .node-icon{ color:var(--clr-type,#64748b) }
.L,
.badge-letter{
  position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
  font-size:.58rem;font-weight:900;color:var(--clr-type,#64748b);text-shadow:none;
}

.color-type-macro{ --clr-type:#0f766e }
.color-type-process{ --clr-type:#7c3aed }
.color-type-activity{ --clr-type:#ef4444 }
.color-type-function{ --clr-type:#0ea5e9 }

.code{ background:#eef2f7; border:1px solid #e2e8f0; border-radius:.3rem; padding:.02rem .3rem; font-size:.7rem; font-family:ui-monospace,Menlo,Consolas,monospace; color:#0f172a }

.table-sm>:not(caption)>*>*{ padding:.2rem .4rem }
.table thead th{ position:sticky; top:0; z-index:1 }

.is-changed{ border-color:#60a5fa; box-shadow:0 0 0 .1rem rgba(59,130,246,.2) }

/* Toggle rotation */
.rotateable{ transition: transform .18s ease }
.rot-90{ transform: rotate(90deg) }
</style>
