<template>
  <VerticalLayoutAudit>
    <div class="ap-shell">

      <!-- ══ HEADER ══ -->
      <header class="ap-header">
        <div class="ap-hrow">
          <a :href="props.backUrl" class="ap-back"><i class="ti ti-arrow-left"></i></a>
          <div class="ap-hinfo">
            <div class="ap-chips">
              <code class="ap-code">{{ mission?.code_mission ?? '—' }}</code>
              <span class="ap-chip" :class="`chip-${ap.validation_status || 'draft'}`">
                <i :class="vstIcon(ap.validation_status || 'draft')"></i>
                {{ vstLbl(ap.validation_status || 'draft') }}
              </span>
              <span class="ap-chip chip-type">Analyse Processus</span>
              <span v-if="props.auditorRole" class="ap-chip" :class="`chip-role-${props.auditorRole}`">{{ props.auditorRole }}</span>
            </div>
            <h1 class="ap-title">Analyse des Processus — AC P3.1</h1>
            <div class="ap-meta">
              <span v-if="assignment?.phase_label"><i class="ti ti-git-branch"></i>{{ assignment.phase_label }}</span>
              <span v-if="mission?.entity_name"><i class="ti ti-building"></i>{{ mission.entity_name }}</span>
              <span>
                <i class="ti ti-layers-intersect"></i>
                {{ allTableProcesses.length }} processus · {{ totalActivities }} activités · {{ props.riskCount }} risques
              </span>
            </div>
          </div>
          <div class="ap-view-toggle">
            <button :class="['vt-btn',{active:viewMode==='table'}]" @click="setMode('table')" title="Tableau seul"><i class="ti ti-table"></i></button>
            <button :class="['vt-btn',{active:viewMode==='split'}]" @click="setMode('split')" title="Vue partagée"><i class="ti ti-layout-columns"></i></button>
            <button :class="['vt-btn',{active:viewMode==='bpmn'}]"  @click="setMode('bpmn')"  title="BPMN seul"><i class="ti ti-chart-dots-3"></i></button>
          </div>
        </div>
        <div v-if="ap.validation_status==='validated'" class="ap-banner banner-lock">
          <i class="ti ti-lock"></i> Analyse <strong>validée</strong> — lecture seule
        </div>
        <div v-else-if="ap.validation_status==='in_review'" class="ap-banner banner-review">
          <i class="ti ti-clock"></i> En attente de validation
          <span v-if="canManage"> · Vous pouvez valider ou rejeter.</span>
        </div>
      </header>

      <!-- ══ BODY ══ -->
      <div class="ap-body">

        <div v-if="!processesData.length" class="ap-empty">
          <i class="ti ti-alert-circle"></i>
          <strong>Aucun processus à risque pour cette mission</strong>
          <p>Associez des risques à cette mission pour générer l'analyse des processus.</p>
        </div>

        <template v-else>

          <!-- ── Barre processus hors mission ── -->
          <div v-if="availableUnlinked.length || addedUnlinkedList.length" class="unlinked-bar">
            <span class="unlinked-bar-lbl"><i class="ti ti-plus-circle"></i> Hors mission :</span>
            <div class="unlinked-pills">
              <button v-for="proc in availableUnlinked" :key="proc.id"
                class="btn-unlinked" @click="addUnlinked(proc)" :title="`Ajouter ${proc.code}`">
                <code>{{ proc.code }}</code><span class="ul-name">{{ proc.name }}</span><i class="ti ti-plus add-ico"></i>
              </button>
              <button v-for="proc in addedUnlinkedList" :key="`a-${proc.id}`"
                class="btn-unlinked btn-ul-added" @click="removeUnlinked(proc)" :title="`Retirer ${proc.code}`">
                <code>{{ proc.code }}</code><span class="ul-name">{{ proc.name }}</span><i class="ti ti-x rem-ico"></i>
              </button>
            </div>
          </div>

          <!-- ══ SPLIT LAYOUT ══ -->
          <div class="ap-split" :class="`mode-${viewMode}`" ref="splitContainer">

            <!-- ─── GAUCHE : Tableau ─── -->
            <div class="ap-pane ap-pane-left" v-show="viewMode!=='bpmn'">
              <div class="pane-hd">
                <div class="pane-hd-l"><i class="ti ti-table"></i><span>Cartographie des processus</span></div>
                <div class="pane-hd-r">
                  <span class="risk-badge"><i class="ti ti-alert-triangle"></i> {{ props.riskCount }} risque(s)</span>
                  <div class="ap-list-dd" v-if="(apList as any[]).length">
                    <button class="btn btn-sm btn-ghost" @click="showApList=!showApList">
                      <i class="ti ti-history"></i> {{ (apList as any[]).length }} AP
                    </button>
                    <div v-if="showApList" class="ap-list-menu">
                      <div v-for="item in (apList as any[])" :key="item.id" class="ap-list-item"
                        @click="loadAp(item);showApList=false">
                        <code>{{ item.code }}</code>
                        <span class="ap-chip-sm" :class="`chip-${item.validation_status||'draft'}`">{{ vstLbl(item.validation_status||'draft') }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="cart-scroll">
                <table class="cart-tbl">
                  <thead>
                    <tr>
                      <th class="th-s0">Processus</th>
                      <th class="th-s1">Libellé</th>
                      <th>Obj. Stratégique</th>
                      <th>Obj. Opérationnel</th>
                      <th>Indicateur</th>
                      <th>Propriétaire</th>
                      <th>Autres Intervenants</th>
                      <th>Éléments d'Entrées<br><small>(Intrants)</small></th>
                      <th>Éléments de Sortie<br><small>(Extrants)</small></th>
                      <th class="th-ff">Forces / Faiblesses</th>
                      <th>Observations</th>
                    </tr>
                  </thead>
                  <tbody>
                    <template v-for="proc in allTableProcesses" :key="proc.id">
                      <tr class="row-proc" :class="{'row-proc-ul':isUnlinked(proc.id),'row-proc-active':bpmnProcId===proc.id}"
                          @click="onProcRowClick(proc.id)" style="cursor:pointer">
                        <td class="td-s0 td-proc-code">
                          <i class="ti ti-box ti-xs"></i> {{ proc.code }}
                          <span v-if="localBpmnMap[proc.id]" class="bpmn-local-dot" title="BPMN annoté"><i class="ti ti-pencil-check"></i></span>
                        </td>
                        <td class="td-s1 td-proc-name">{{ proc.name }}</td>
                        <td><textarea v-if="!isLocked" class="c-ta" v-model="procForms[proc.id].objectif_strategique" rows="2" placeholder="Objectif stratégique…" @click.stop/>
                          <div v-else class="ro-txt">{{ procForms[proc.id].objectif_strategique||'—' }}</div></td>
                        <td><textarea v-if="!isLocked" class="c-ta" v-model="procForms[proc.id].objectif_operationnel" rows="2" placeholder="Objectif opérationnel…" @click.stop/>
                          <div v-else class="ro-txt">{{ procForms[proc.id].objectif_operationnel||'—' }}</div></td>
                        <td><textarea v-if="!isLocked" class="c-ta" v-model="procForms[proc.id].indicateur" rows="2" placeholder="KPI…" @click.stop/>
                          <div v-else class="ro-txt">{{ procForms[proc.id].indicateur||'—' }}</div></td>
                        <td class="td-sel">
                          <template v-if="!isLocked">
                            <select class="c-sel" v-model="procForms[proc.id].proprietaire" @click.stop>
                              <option value="">— choisir —</option>
                              <option v-for="fn in (assignmentFunctions as any[])" :key="fn.id" :value="fn.character">{{ fn.character }} – {{ fn.name }}</option>
                            </select>
                          </template>
                          <div v-else class="ro-fn">
                            <span v-if="procForms[proc.id].proprietaire" class="fn-tag fn-own">{{ procForms[proc.id].proprietaire }}</span>
                            <span v-else class="ro-txt">—</span>
                          </div>
                        </td>
                        <td class="td-interv">
                          <template v-if="!isLocked">
                            <div class="interv-wrap">
                              <div class="interv-tags">
                                <span v-for="fn in selectedIntervenants(proc.id)" :key="fn" class="fn-tag fn-int" @click.stop="removeIntervenant(proc.id,fn)">{{ fn }}<i class="ti ti-x fn-x"></i></span>
                                <span v-if="!selectedIntervenants(proc.id).length" class="no-interv">Aucun</span>
                              </div>
                              <select class="c-sel-sm" @change="onAddIntervenant(proc.id,($event.target as HTMLSelectElement))" @click.stop>
                                <option value="">+ Ajouter</option>
                                <option v-for="fn in (assignmentFunctions as any[])" :key="fn.id" :value="fn.character" :disabled="selectedIntervenants(proc.id).includes(fn.character)">{{ fn.character }} – {{ fn.name }}</option>
                              </select>
                            </div>
                          </template>
                          <div v-else class="interv-ro">
                            <span v-for="fn in selectedIntervenants(proc.id)" :key="fn" class="fn-tag fn-int">{{ fn }}</span>
                            <span v-if="!selectedIntervenants(proc.id).length" class="ro-txt">—</span>
                          </div>
                        </td>
                        <td class="td-io">
                          <template v-if="!isLocked">
                            <div v-if="proc.default_entrees && !procForms[proc.id]._entrees_edited" class="io-preview io-in-preview">
                              <div class="io-chips"><span v-for="(l,i) in ioLines(procForms[proc.id].entrees)" :key="i" class="io-chip io-in">{{ l }}</span></div>
                              <button class="io-edit-btn" @click.stop="procForms[proc.id]._entrees_edited=true"><i class="ti ti-edit"></i></button>
                            </div>
                            <textarea v-else class="c-ta" v-model="procForms[proc.id].entrees" rows="3" placeholder="Intrants…" @click.stop/>
                          </template>
                          <div v-else class="io-chips">
                            <span v-for="(l,i) in ioLines(procForms[proc.id].entrees)" :key="i" class="io-chip io-in">{{ l }}</span>
                            <span v-if="!ioLines(procForms[proc.id].entrees).length" class="ro-txt">—</span>
                          </div>
                        </td>
                        <td class="td-io">
                          <template v-if="!isLocked">
                            <div v-if="proc.default_sorties && !procForms[proc.id]._sorties_edited" class="io-preview io-out-preview">
                              <div class="io-chips"><span v-for="(l,i) in ioLines(procForms[proc.id].sorties)" :key="i" class="io-chip io-out">{{ l }}</span></div>
                              <button class="io-edit-btn" @click.stop="procForms[proc.id]._sorties_edited=true"><i class="ti ti-edit"></i></button>
                            </div>
                            <textarea v-else class="c-ta" v-model="procForms[proc.id].sorties" rows="3" placeholder="Extrants…" @click.stop/>
                          </template>
                          <div v-else class="io-chips">
                            <span v-for="(l,i) in ioLines(procForms[proc.id].sorties)" :key="i" class="io-chip io-out">{{ l }}</span>
                            <span v-if="!ioLines(procForms[proc.id].sorties).length" class="ro-txt">—</span>
                          </div>
                        </td>
                        <td class="td-ff">
                          <button class="btn-ff" @click.stop="openFf(proc.id)" :disabled="isLocked">
                            <div class="ff-scores">
                              <span class="ff-f"><i class="ti ti-thumb-up"></i>{{ countLines(procForms[proc.id].forces) }}</span>
                              <span class="ff-w"><i class="ti ti-thumb-down"></i>{{ countLines(procForms[proc.id].faiblesses) }}</span>
                            </div>
                            <span class="ff-cta">{{ isLocked ? 'Voir' : 'Éditer' }}</span>
                          </button>
                        </td>
                        <td>
                          <textarea v-if="!isLocked" class="c-ta" v-model="procForms[proc.id].observations" rows="2" placeholder="Observations…" @click.stop/>
                          <div v-else class="ro-txt">{{ procForms[proc.id].observations||'—' }}</div>
                        </td>
                      </tr>
                      <tr v-for="act in proc.activities" :key="act.id" class="row-act">
                        <td class="td-s0 td-act-code"><i class="ti ti-box ti-xs act-ico"></i> {{ act.code }}</td>
                        <td class="td-s1 td-act-name">{{ act.name }}</td>
                        <td colspan="8" class="td-act-risks">
                          <div class="act-risks-list">
                            <span v-for="r in act.risks" :key="r.id" class="risk-tag" :class="`risk-${r.status}`"><i class="ti ti-alert-triangle"></i> {{ r.code }} — {{ r.label }}</span>
                            <span v-if="!act.risks?.length" class="no-risk">Aucun risque</span>
                          </div>
                        </td>
                        <td class="td-act-obs-pad"></td>
                      </tr>
                    </template>
                  </tbody>
                </table>
              </div>

              <div class="synth-row">
                <div class="synth-f">
                  <label><i class="ti ti-notes"></i> Synthèse</label>
                  <textarea v-if="!isLocked" class="synth-ta" v-model="synthese" rows="3" placeholder="Synthèse générale…"/>
                  <div v-else class="ro-txt synth-ro">{{ synthese||'—' }}</div>
                </div>
                <div class="author-fs">
                  <div class="af"><label>Fait par</label><input class="inp" v-model="form.fait_par" :disabled="isLocked"/></div>
                  <div class="af"><label>Revu par</label><input class="inp" v-model="form.revue_par" :disabled="isLocked"/></div>
                </div>
              </div>
            </div>

            <!-- ─── RESIZE HANDLE ─── -->
            <div v-if="viewMode==='split'" class="resize-handle" ref="resizeHandle" @mousedown="startResize">
              <div class="resize-grip"><i class="ti ti-grip-vertical"></i></div>
            </div>

            <!-- ─── DROITE : BPMN EDITOR ─── -->
            <div class="ap-pane ap-pane-right" v-show="viewMode!=='table'">

              <!-- Pane header BPMN -->
              <div class="pane-hd pane-hd-bpmn">
                <div class="pane-hd-l">
                  <i class="ti ti-chart-dots-3"></i>
                  <select v-model="bpmnProcId" class="bpmn-sel" @change="onBpmnChange">
                    <option v-for="p in allTableProcesses" :key="p.id" :value="p.id">{{ p.code }} — {{ p.name }}</option>
                  </select>
                  <span v-if="localBpmnMap[bpmnProcId!]" class="bpmn-badge-local"><i class="ti ti-pencil-check"></i> Annoté</span>
                  <span v-else-if="currentBpmnProc?.bpmn_xml" class="bpmn-badge-official"><i class="ti ti-copy"></i> Officiel</span>
                </div>
                <div class="pane-hd-r">
                  <!-- Save status pill -->
                  <div class="bpmn-save-pill" :class="bpmnSaveStatus">
                    <i :class="bpmnSaveIcon"></i>
                    <span>{{ bpmnSaveMsg }}</span>
                  </div>
                  <!-- Toolbar toggle -->
                  <button class="bpmn-tb-btn" :class="{active:showBpmnToolbar}" @click="showBpmnToolbar=!showBpmnToolbar" title="Palette">
                    <i class="ti ti-layout-sidebar-left"></i>
                  </button>
                  <!-- Props toggle -->
                  <button class="bpmn-tb-btn" :class="{active:showBpmnProps}" @click="showBpmnProps=!showBpmnProps" title="Propriétés">
                    <i class="ti ti-layout-sidebar-right"></i>
                  </button>
                  <button class="bpmn-tb-btn" @click="fitBpmn" title="Ajuster"><i class="ti ti-maximize"></i></button>
                  <!-- Reset -->
                  <button v-if="!isLocked && localBpmnMap[bpmnProcId!]" class="bpmn-tb-btn bpmn-tb-reset" @click="resetToOfficial" title="Réinitialiser vers officiel">
                    <i class="ti ti-refresh"></i>
                  </button>
                  <!-- Save manuel version -->
                  <button v-if="!isLocked && currentBpmnProc?.bpmn_xml" class="bpmn-tb-save" :disabled="bpmnSaving || !bpmnDirty" @click="saveLocalBpmn">
                    <span v-if="bpmnSaving" class="spin-dot"></span>
                    <i v-else class="ti ti-device-floppy"></i>
                    Sauvegarder
                  </button>
                </div>
              </div>

              <!-- Info bar -->
              <div v-if="!isLocked && currentBpmnProc?.bpmn_xml" class="bpmn-info-bar">
                <i class="ti ti-info-circle"></i>
                <span>Copie d'analyse — le BPMN officiel n'est pas modifié.</span>
                <span v-if="bpmnDirty" class="bpmn-dirty-dot">● Non sauvegardé</span>
              </div>

              <!-- BPMN Editor layout -->
              <div class="bpmn-editor-layout" :class="{'no-bpmn-left':!showBpmnToolbar,'no-bpmn-right':!showBpmnProps}">

                <!-- ── Palette gauche ── -->
                <aside v-if="showBpmnToolbar && !isLocked" class="bpmn-sidebar-left">
                  <div class="bsl-head">
                    <span class="bsl-title"><i class="ti ti-tools"></i> Palette</span>
                    <input v-model="bpmnToolbarSearch" class="bsl-search" placeholder="Rechercher…"/>
                  </div>
                  <div class="bsl-body">
                    <div v-for="grp in bpmnToolbarGroups" :key="grp.id" class="btg">
                      <div class="btg-label"><i :class="grp.icon"></i> {{ grp.label }}</div>
                      <div class="btg-grid">
                        <button v-for="item in filterBpmnGroup(grp.items)" :key="item.type+item.name"
                          class="btg-item"
                          @click="createBpmnElement(item.type, item.name)"
                          @dragstart="dragStart($event, item)" draggable="true"
                          :title="item.description">
                          <i :class="`bpmn-icon ${item.icon}`"></i>
                          <span>{{ item.name }}</span>
                        </button>
                      </div>
                    </div>
                    <div class="btg">
                      <div class="btg-label"><i class="ti ti-users"></i> Structure</div>
                      <div class="btg-grid">
                        <button class="btg-item" @click="addParticipant"><i class="bpmn-icon bpmn-icon-participant"></i><span>Participant</span></button>
                        <button class="btg-item" @click="addLane"><i class="bpmn-icon bpmn-icon-lane"></i><span>Couloir</span></button>
                      </div>
                    </div>
                  </div>
                </aside>

                <!-- ── Canvas BPMN ── -->
                <div class="bpmn-canvas-wrap">
                  <!-- Mini toolbar -->
                  <div class="bpmn-canvas-bar">
                    <div class="bcb-left">
                      <button class="bcb-btn" @click="zoomIn"    title="Zoom +"><i class="ti ti-zoom-in"></i></button>
                      <button class="bcb-btn" @click="zoomOut"   title="Zoom -"><i class="ti ti-zoom-out"></i></button>
                      <button class="bcb-btn" @click="fitBpmn"   title="Ajuster"><i class="ti ti-maximize"></i></button>
                      <div class="bcb-zoom">{{ bpmnZoomLevel }}%</div>
                    </div>
                    <div class="bcb-right">
                      <span class="bcb-stat"><i class="ti ti-shapes"></i> {{ bpmnElementCount }}</span>
                      <span class="bcb-stat"><i class="ti ti-link"></i> {{ bpmnConnectionCount }}</span>
                      <span v-if="Object.keys(bpmnTaskLinks).length" class="bcb-stat bcb-links">
                        <i class="ti ti-activity"></i> {{ Object.keys(bpmnTaskLinks).length }} lien(s)
                      </span>
                    </div>
                  </div>

                  <!-- Canvas container -->
                  <div ref="bpmnCanvas" class="bpmn-canvas"
                       @contextmenu.prevent="handleContextMenu"
                       @dragover.prevent @drop.prevent="handleDrop">
                    <transition name="fade">
                      <div v-if="bpmnLoading" class="bpmn-canvas-loader">
                        <div class="bcl-ring"></div>
                        <p>{{ currentBpmnProc?.bpmn_xml ? 'Chargement du diagramme…' : 'Aucun BPMN officiel pour ce processus' }}</p>
                      </div>
                    </transition>
                    <div v-if="!bpmnLoading && !currentBpmnProc?.bpmn_xml" class="bpmn-no-diagram">
                      <i class="ti ti-chart-dots-3"></i>
                      <p>Aucun diagramme BPMN officiel pour ce processus.</p>
                    </div>
                  </div>

                  <!-- Context menu -->
                  <transition name="scale">
                    <div v-if="showCtxMenu" class="ctx-menu"
                         :style="{left:ctxX+'px',top:ctxY+'px'}">
                      <div v-if="bpmnSelectedEl" class="ctx-head">
                        <strong>{{ bpmnSelName || 'Élément' }}</strong>
                        <code>{{ bpmnSelId }}</code>
                      </div>
                      <div v-if="bpmnSelectedEl && (isBpmnTask(bpmnSelectedEl)||isBpmnParticipant(bpmnSelectedEl))" class="ctx-colors">
                        <button v-for="c in colorPalette" :key="c.hex" class="ctx-color"
                          :style="{background:c.hex,outline:bpmnSelColor===c.hex?'3px solid #1565C0':'none'}"
                          @click="applyBpmnColor(c.hex)" :title="c.name"></button>
                      </div>
                      <div class="ctx-items">
                        <button class="ctx-item" @click="duplicateBpmnElement"><i class="ti ti-copy"></i> Dupliquer</button>
                        <button class="ctx-item" @click="resizeBpmnElement('larger')"><i class="ti ti-zoom-in"></i> Agrandir</button>
                        <button class="ctx-item" @click="resizeBpmnElement('smaller')"><i class="ti ti-zoom-out"></i> Réduire</button>
                        <button class="ctx-item" @click="resetBpmnElement"><i class="ti ti-refresh"></i> Réinitialiser</button>
                        <button class="ctx-item ctx-danger" @click="deleteBpmnElement"><i class="ti ti-trash"></i> Supprimer</button>
                      </div>
                      <button class="ctx-close" @click="closeCtxMenu"><i class="ti ti-x"></i></button>
                    </div>
                  </transition>
                  <div v-if="showCtxMenu" class="ctx-overlay" @click="closeCtxMenu"></div>
                </div>

                <!-- ── Panneau propriétés droite ── -->
                <aside v-if="showBpmnProps" class="bpmn-sidebar-right">
                  <div class="bsr-head">
                    <span class="bsr-title"><i class="ti ti-settings"></i> Propriétés</span>
                    <button class="bsr-close" @click="showBpmnProps=false"><i class="ti ti-x"></i></button>
                  </div>
                  <div class="bsr-body">

                    <!-- Pas de sélection -->
                    <div v-if="!bpmnSelectedEl" class="bsr-nosel">
                      <div class="bsr-nosel-icon"><i class="ti ti-cursor-text"></i></div>
                      <h4>Sélectionnez un élément</h4>
                      <p>Cliquez sur une tâche pour lier une activité</p>
                      <!-- Activités disponibles -->
                      <div v-if="currentProcActivities.length" class="bsr-acts">
                        <div class="bsr-acts-hd">
                          <i class="ti ti-activity"></i> Activités
                          <span class="bsr-acts-badge">{{ currentProcActivities.length }}</span>
                        </div>
                        <div class="bsr-acts-list">
                          <div v-for="act in currentProcActivities" :key="act.id" class="bsr-act-item">
                            <code>{{ act.code }}</code><span>{{ act.name }}</span>
                          </div>
                        </div>
                      </div>
                      <div v-else class="bsr-acts">
                        <div class="bsr-acts-hd"><i class="ti ti-activity"></i> Activités <span class="bsr-acts-badge bsr-acts-badge-0">0</span></div>
                        <div class="bsr-act-empty"><i class="ti ti-mood-empty"></i><span>Aucune activité pour ce processus</span></div>
                      </div>
                    </div>

                    <!-- Sélection -->
                    <div v-else class="bsr-props">
                      <div class="bsr-el-head">
                        <div class="bsr-el-icon"><i :class="bpmnSelIcon"></i></div>
                        <div>
                          <div class="bsr-el-name">{{ bpmnSelName || '—' }}</div>
                          <code class="bsr-el-id">{{ bpmnSelId }}</code>
                          <span class="bsr-el-type">{{ bpmnSelType }}</span>
                        </div>
                      </div>

                      <!-- Tabs -->
                      <div class="bsr-tabs">
                        <button v-for="tab in bpmnVisibleTabs" :key="tab.id"
                          :class="['bsr-tab',{active:bpmnActiveTab===tab.id}]"
                          @click="bpmnActiveTab=tab.id">
                          <i :class="tab.icon"></i> {{ tab.label }}
                        </button>
                      </div>

                      <!-- Tab Général -->
                      <div v-if="bpmnActiveTab==='general'" class="bsr-tab-content">
                        <div class="bpf-group">
                          <label>Nom</label>
                          <input v-model="bpmnSelName" @change="updateBpmnElName" class="bpf-input" placeholder="Nom…"/>
                        </div>
                      </div>

                      <!-- Tab Style -->
                      <div v-if="bpmnActiveTab==='style'" class="bsr-tab-content">
                        <div class="bpf-group">
                          <label>Couleur</label>
                          <div class="bpf-colors">
                            <button v-for="c in colorPalette" :key="c.hex" class="bpf-swatch"
                              :style="{background:c.hex,outline:bpmnSelColor===c.hex?'3px solid #1565C0':'none'}"
                              @click="applyBpmnColor(c.hex)" :title="c.name"></button>
                          </div>
                          <div class="bpf-color-row">
                            <input type="color" v-model="bpmnSelColor" @change="applyBpmnColor(bpmnSelColor)" class="bpf-color-picker"/>
                            <input type="text" v-model="bpmnSelColor" @change="applyBpmnColor(bpmnSelColor)" class="bpf-input bpf-color-hex"/>
                          </div>
                        </div>
                      </div>

                      <!-- Tab Activité -->
                      <div v-if="bpmnActiveTab==='activity'" class="bsr-tab-content">
                        <!-- Lien existant -->
                        <div v-if="bpmnCurrentLink" class="bsr-act-linked">
                          <div class="bsal-head">
                            <span><i class="ti ti-link"></i> Activité liée</span>
                            <button @click="unlinkBpmnActivity" class="bsal-unlink"><i class="ti ti-unlink"></i> Délier</button>
                          </div>
                          <div class="bsal-card">
                            <div class="bsal-card-icon"><i class="ti ti-activity"></i></div>
                            <div>
                              <strong>{{ bpmnCurrentLink.activity_name }}</strong>
                              <code>{{ bpmnCurrentLink.activity_code }}</code>
                            </div>
                          </div>
                        </div>
                        <!-- Recherche + liste -->
                        <div class="bpf-group">
                          <label>
                            <i class="ti ti-activity"></i>
                            {{ bpmnCurrentLink ? 'Changer' : 'Associer une activité' }}
                            <span class="bpf-acts-count">{{ currentProcActivities.length }}</span>
                          </label>
                          <div class="bpf-act-search">
                            <i class="ti ti-search bpf-search-ico"></i>
                            <input v-model="bpmnActSearch" class="bpf-input bpf-act-search-inp" placeholder="Filtrer…"/>
                            <button v-if="bpmnActSearch" @click="bpmnActSearch=''" class="bpf-act-clear"><i class="ti ti-x"></i></button>
                          </div>
                          <div class="bpf-act-list">
                            <div v-if="filteredBpmnActivities.length===0" class="bpf-act-empty">
                              <i class="ti ti-mood-empty"></i>
                              <span>{{ currentProcActivities.length===0 ? 'Aucune activité disponible' : 'Aucun résultat' }}</span>
                            </div>
                            <div v-for="act in filteredBpmnActivities" :key="act.id"
                              class="bpf-act-item"
                              :class="{'bpf-act-item--active':bpmnCurrentLink?.activity_id===act.id}"
                              @click="linkBpmnActivity(act)">
                              <div class="bpf-act-ico"><i class="ti ti-activity"></i></div>
                              <div class="bpf-act-info">
                                <strong>{{ act.name }}</strong>
                                <code>{{ act.code }}</code>
                              </div>
                              <i v-if="bpmnCurrentLink?.activity_id===act.id" class="ti ti-check bpf-act-check"></i>
                              <i v-else class="ti ti-link-plus bpf-act-add"></i>
                            </div>
                          </div>
                        </div>
                      </div>

                      <div class="bsr-footer">
                        <button @click="bpmnSelectedEl=null" class="bsr-desel"><i class="ti ti-x"></i> Désélectionner</button>
                      </div>
                    </div>
                  </div>
                </aside>
              </div><!-- /bpmn-editor-layout -->
            </div><!-- /ap-pane-right -->

          </div><!-- /ap-split -->

          <!-- ══ FOOTER ══ -->
          <footer class="ap-footer">
            <div>
              <button v-if="!isLocked" type="button" class="btn btn-ghost" @click="annuler"><i class="ti ti-x"></i> Annuler</button>
              <button v-if="!isLocked" type="button" class="btn btn-save" :disabled="processing" @click="submit">
                <span v-if="processing" class="spin-dot"></span>
                <i v-else class="ti ti-device-floppy"></i>
                {{ form.id ? 'Mettre à jour' : 'Enregistrer' }}
              </button>
            </div>
            <div class="footer-c">
              <span v-if="form.id" class="saved-code"><i class="ti ti-check"></i> {{ form.code }}</span>
            </div>
            <div>
              <button v-if="ap.id && ap.validation_status==='draft'" type="button" class="btn btn-sub" :disabled="processing" @click="soumettre">
                <i class="ti ti-send"></i> Soumettre
              </button>
              <template v-if="canManage && ap.validation_status==='in_review'">
                <button type="button" class="btn btn-ok"  :disabled="processing" @click="valider('validated')"><i class="ti ti-circle-check"></i> Valider</button>
                <button type="button" class="btn btn-rej" :disabled="processing" @click="promptReject"><i class="ti ti-circle-x"></i> Rejeter</button>
              </template>
            </div>
          </footer>

        </template>
      </div>
    </div>

    <!-- ══ MODAL Forces / Faiblesses ══ -->
    <Teleport to="body">
      <transition name="mfade">
        <div v-if="ffModal.open" class="modal-ov" @click.self="closeFf">
          <div class="modal-box">
            <div class="modal-hd">
              <div class="modal-hd-l">
                <i class="ti ti-balance"></i>
                <span>Forces &amp; Faiblesses</span>
                <code class="modal-code">{{ ffProc?.code }}</code>
                <span class="modal-pname">{{ ffProc?.name }}</span>
              </div>
              <button class="modal-cls" @click="closeFf"><i class="ti ti-x"></i></button>
            </div>
            <div class="modal-body">
              <div class="ff-cols">
                <div class="ff-col ff-forces">
                  <div class="ff-col-hd"><i class="ti ti-thumb-up"></i> Forces <span class="ff-hint">Une force par ligne</span></div>
                  <div class="ff-items">
                    <div v-for="(line,idx) in ffLines('forces')" :key="`f${idx}`" class="ff-item ff-item-f">
                      <i class="ti ti-point-filled ff-dot"></i><span class="ff-text">{{ line }}</span>
                      <button v-if="!isLocked" class="ff-del" @click="deleteLine('forces',idx)"><i class="ti ti-x"></i></button>
                    </div>
                    <div v-if="!ffLines('forces').length" class="ff-empty">Aucune force saisie</div>
                  </div>
                  <div v-if="!isLocked" class="ff-add">
                    <input class="ff-inp" v-model="ffModal.newForce" placeholder="Nouvelle force…" @keydown.enter.prevent="addLine('forces')"/>
                    <button class="ff-add-btn ff-add-f" @click="addLine('forces')" :disabled="!ffModal.newForce.trim()"><i class="ti ti-plus"></i></button>
                  </div>
                  <div v-if="!isLocked" class="ff-bulk-lbl">Ou saisie libre :</div>
                  <textarea v-if="!isLocked" class="ff-ta ff-ta-f" v-model="procForms[ffModal.procId!].forces" rows="4" placeholder="Force 1&#10;Force 2…"/>
                </div>
                <div class="ff-col ff-faib">
                  <div class="ff-col-hd"><i class="ti ti-thumb-down"></i> Faiblesses <span class="ff-hint">Une faiblesse par ligne</span></div>
                  <div class="ff-items">
                    <div v-for="(line,idx) in ffLines('faiblesses')" :key="`w${idx}`" class="ff-item ff-item-w">
                      <i class="ti ti-point-filled ff-dot"></i><span class="ff-text">{{ line }}</span>
                      <button v-if="!isLocked" class="ff-del" @click="deleteLine('faiblesses',idx)"><i class="ti ti-x"></i></button>
                    </div>
                    <div v-if="!ffLines('faiblesses').length" class="ff-empty">Aucune faiblesse saisie</div>
                  </div>
                  <div v-if="!isLocked" class="ff-add">
                    <input class="ff-inp" v-model="ffModal.newFaib" placeholder="Nouvelle faiblesse…" @keydown.enter.prevent="addLine('faiblesses')"/>
                    <button class="ff-add-btn ff-add-w" @click="addLine('faiblesses')" :disabled="!ffModal.newFaib.trim()"><i class="ti ti-x"></i></button>
                  </div>
                  <div v-if="!isLocked" class="ff-bulk-lbl">Ou saisie libre :</div>
                  <textarea v-if="!isLocked" class="ff-ta ff-ta-w" v-model="procForms[ffModal.procId!].faiblesses" rows="4" placeholder="Faiblesse 1&#10;Faiblesse 2…"/>
                </div>
              </div>
            </div>
            <div class="modal-ft">
              <button class="btn btn-save" @click="closeFf"><i class="ti ti-check"></i> Confirmer</button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- Toast -->
    <Teleport to="body">
      <transition name="toast-up">
        <div v-if="toast.show" class="toast" :class="`toast-${toast.type}`">
          <i :class="toast.type==='success'?'ti ti-circle-check':'ti ti-alert-circle'"></i> {{ toast.msg }}
        </div>
      </transition>
    </Teleport>

  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, reactive, computed, nextTick, onMounted, onBeforeUnmount, markRaw } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  mission:             { type: Object, default: null },
  assignment:          { type: Object, default: null },
  form:                { type: Object, default: null },
  apList:              { type: Array,  default: () => [] },
  processesData:       { type: Array,  default: () => [] },
  unlinkedProcesses:   { type: Array,  default: () => [] },
  assignmentFunctions: { type: Array,  default: () => [] },
  riskCount:           { type: Number, default: 0 },
  auditorRole:         { type: String, default: null },
  missionId:           { type: Number, default: null },
  assignmentId:        { type: Number, default: null },
  currentAuditor:      { type: Object, default: null },
  backUrl:             { type: String, default: '' },
  formUrl:             { type: String, default: '' },
})

// ── State général ──────────────────────────────────────────────────────────
const viewMode       = ref<'table'|'split'|'bpmn'>('split')
const splitContainer = ref<HTMLElement|null>(null)
const resizeHandle   = ref<HTMLElement|null>(null)
const showApList     = ref(false)
const processing     = ref(false)
const synthese       = ref(props.form?.synthese ?? '')
const splitLeft      = ref(55)
const bpmnProcId     = ref<number|null>(null)

const addedUnlinkedIds  = ref<Set<number>>(new Set())
const addedUnlinkedList = ref<any[]>([])

const ap   = reactive<Record<string,any>>(props.form ? { ...props.form } : {})
const form = reactive({
  id:        props.form?.id        ?? null,
  code:      props.form?.code      ?? '',
  fait_par:  props.form?.fait_par  ?? auditorFullName(),
  revue_par: props.form?.revue_par ?? '',
})

function auditorFullName() {
  const a = props.currentAuditor as any
  return a ? [a.last_name, a.first_name].filter(Boolean).join(' ') : ''
}

// ──────────────────────────────────────────────────────────────────────────
// ── BPMN LOCAL (copie annotée auditeur) ──────────────────────────────────
// ──────────────────────────────────────────────────────────────────────────
const localBpmnMap    = reactive<Record<number, string>>({})
const bpmnSaving      = ref(false)
const bpmnSaved       = ref(false)
const bpmnDirty       = ref(false)
const bpmnSaveStatus  = ref<'idle'|'saving'|'saved'|'error'>('idle')

// BPMN Editor refs
const bpmnCanvas      = ref<HTMLElement|null>(null)
const bpmnLoading     = ref(false)
const showBpmnToolbar = ref(true)
const showBpmnProps   = ref(true)
const bpmnToolbarSearch = ref('')
const bpmnZoomLevel   = ref(100)
const bpmnActSearch   = ref('')
const bpmnActiveTab   = ref('general')

// Selected element
const bpmnSelectedEl  = ref<any>(null)
const bpmnSelId       = ref('')
const bpmnSelName     = ref('')
const bpmnSelType     = ref('')
const bpmnSelIcon     = ref('')
const bpmnSelColor    = ref('#3b82f6')
const bpmnCurrentLink = ref<any>(null)

// Context menu
const showCtxMenu = ref(false)
const ctxX        = ref(0)
const ctxY        = ref(0)

// Task links & modeler
const bpmnTaskLinks   = ref<Record<string,any>>({})
let   bpmnModeler: any = null
let   bpmnMainParticipant: any = null
let   bpmnAutoSaveTimer: ReturnType<typeof setTimeout> | null = null
const draggingBpmnEl  = ref<any>(null)

const bpmnSaveMsg = computed(() => ({
  idle: 'Auto-save', saving: 'Sauvegarde…', saved: 'Sauvegardé', error: 'Erreur'
}[bpmnSaveStatus.value]))

const bpmnSaveIcon = computed(() => ({
  idle: 'ti ti-circle-dotted', saving: 'ti ti-loader animate-spin',
  saved: 'ti ti-check', error: 'ti ti-alert-circle'
}[bpmnSaveStatus.value]))

const bpmnElementCount = computed(() => {
  if (!bpmnModeler) return 0
  try { return bpmnModeler.get('elementRegistry').getAll().filter((e: any) => !e.type?.includes('di:')).length } catch { return 0 }
})
const bpmnConnectionCount = computed(() => {
  if (!bpmnModeler) return 0
  try { return bpmnModeler.get('elementRegistry').getAll().filter((e: any) => e.type === 'bpmn:SequenceFlow').length } catch { return 0 }
})

// Activités du processus courant (depuis processesData)
const currentProcActivities = computed(() => {
  if (!bpmnProcId.value) return []
  const proc = allTableProcesses.value.find(p => p.id === bpmnProcId.value)
  if (!proc) return []
  // Activités dans le processus
  const acts: any[] = proc.activities ?? []
  return acts.map((a: any) => ({ id: a.id, code: a.code, name: a.name, description: a.description ?? '' }))
})

const filteredBpmnActivities = computed(() => {
  const all = currentProcActivities.value
  if (!bpmnActSearch.value) return all
  const s = bpmnActSearch.value.toLowerCase()
  return all.filter((a: any) => a.name?.toLowerCase().includes(s) || a.code?.toLowerCase().includes(s))
})

const bpmnVisibleTabs = computed(() => {
  const tabs: any[] = [{ id: 'general', icon: 'ti ti-info-circle', label: 'Général' }]
  if (bpmnSelectedEl.value && (isBpmnTask(bpmnSelectedEl.value) || isBpmnParticipant(bpmnSelectedEl.value))) {
    tabs.push({ id: 'style', icon: 'ti ti-palette', label: 'Style' })
  }
  if (bpmnSelectedEl.value && isBpmnTask(bpmnSelectedEl.value)) {
    tabs.push({ id: 'activity', icon: 'ti ti-link', label: `Activité${bpmnCurrentLink.value ? ' ✓' : ''}` })
  }
  return tabs
})

// Initialiser localBpmnMap depuis props.form.bpmn_annotations
function initLocalBpmn() {
  try {
    const raw = props.form?.bpmn_annotations
    if (raw) {
      const parsed = JSON.parse(raw)
      for (const [key, xml] of Object.entries(parsed)) {
        localBpmnMap[Number(key)] = xml as string
      }
    }
  } catch {}
}
initLocalBpmn()

function getDisplayXml(procId: number): string | null {
  return localBpmnMap[procId] ?? (allTableProcesses.value.find(p => p.id === procId)?.bpmn_xml ?? null)
}

// ── Toolbar groups ──────────────────────────────────────────────────────
const bpmnToolbarGroups = [
  { id:'start', icon:'ti ti-player-play', label:'Démarrage', items:[
    { type:'bpmn:StartEvent', name:'Début',    icon:'bpmn-icon-start-event-none',    description:'Début' },
    { type:'bpmn:StartEvent', name:'Message',  icon:'bpmn-icon-start-event-message', description:'Départ message' },
    { type:'bpmn:StartEvent', name:'Minuteur', icon:'bpmn-icon-start-event-timer',   description:'Minuteur' },
  ]},
  { id:'tasks', icon:'ti ti-checkbox', label:'Tâches', items:[
    { type:'bpmn:Task',             name:'Tâche',        icon:'bpmn-icon-task',               description:'Tâche générique' },
    { type:'bpmn:UserTask',         name:'Utilisateur',  icon:'bpmn-icon-user-task',          description:'Tâche utilisateur' },
    { type:'bpmn:ServiceTask',      name:'Service',      icon:'bpmn-icon-service-task',       description:'Service' },
    { type:'bpmn:ScriptTask',       name:'Script',       icon:'bpmn-icon-script-task',        description:'Script' },
    { type:'bpmn:BusinessRuleTask', name:'Règle métier', icon:'bpmn-icon-business-rule-task', description:'Règle métier' },
  ]},
  { id:'gateways', icon:'ti ti-share', label:'Décisions', items:[
    { type:'bpmn:ExclusiveGateway', name:'Exclusif',  icon:'bpmn-icon-gateway-xor',      description:'XOR' },
    { type:'bpmn:ParallelGateway',  name:'Parallèle', icon:'bpmn-icon-gateway-parallel', description:'AND' },
    { type:'bpmn:InclusiveGateway', name:'Inclusif',  icon:'bpmn-icon-gateway-or',       description:'OR' },
  ]},
  { id:'end', icon:'ti ti-player-stop', label:'Fin', items:[
    { type:'bpmn:EndEvent', name:'Fin',     icon:'bpmn-icon-end-event-none',    description:'Fin simple' },
    { type:'bpmn:EndEvent', name:'Message', icon:'bpmn-icon-end-event-message', description:'Fin message' },
    { type:'bpmn:EndEvent', name:'Erreur',  icon:'bpmn-icon-end-event-error',   description:'Fin erreur' },
  ]},
  { id:'other', icon:'ti ti-apps', label:'Autres', items:[
    { type:'bpmn:TextAnnotation', name:'Note',   icon:'bpmn-icon-text-annotation', description:'Annotation' },
    { type:'bpmn:Group',          name:'Groupe', icon:'bpmn-icon-group',            description:'Groupe' },
  ]},
]

const colorPalette = [
  { name:'Bleu',    hex:'#1565C0' }, { name:'Indigo',   hex:'#3730a3' },
  { name:'Vert',    hex:'#15803d' }, { name:'Emeraude', hex:'#10b981' },
  { name:'Ambre',   hex:'#f59e0b' }, { name:'Orange',   hex:'#f97316' },
  { name:'Rouge',   hex:'#ef4444' }, { name:'Rose',     hex:'#ec4899' },
  { name:'Violet',  hex:'#7c3aed' }, { name:'Slate',    hex:'#64748b' },
]

function filterBpmnGroup(items: any[]) {
  if (!bpmnToolbarSearch.value) return items
  const s = bpmnToolbarSearch.value.toLowerCase()
  return items.filter(i => i.name.toLowerCase().includes(s) || i.description?.toLowerCase().includes(s))
}

// ── Helpers type ──
function isBpmnTask(el: any) {
  return ['bpmn:Task','bpmn:UserTask','bpmn:ServiceTask','bpmn:ScriptTask',
          'bpmn:ManualTask','bpmn:BusinessRuleTask','bpmn:SendTask','bpmn:ReceiveTask'].includes(el?.type)
}
function isBpmnParticipant(el: any) { return el?.type === 'bpmn:Participant' }
function isBpmnLane(el: any)        { return el?.type === 'bpmn:Lane' }

function getBpmnElIcon(el: any) {
  if (!el) return 'ti ti-question-mark'
  const t = el.type
  if (t?.includes('StartEvent'))  return 'bpmn-icon bpmn-icon-start-event-none'
  if (t?.includes('EndEvent'))    return 'bpmn-icon bpmn-icon-end-event-none'
  if (t?.includes('UserTask'))    return 'bpmn-icon bpmn-icon-user-task'
  if (t?.includes('ServiceTask')) return 'bpmn-icon bpmn-icon-service-task'
  if (t?.includes('Gateway'))     return 'bpmn-icon bpmn-icon-gateway-xor'
  if (t === 'bpmn:Participant')   return 'bpmn-icon bpmn-icon-participant'
  if (t === 'bpmn:Lane')          return 'bpmn-icon bpmn-icon-lane'
  return 'bpmn-icon bpmn-icon-task'
}

function updateBpmnSelInfo(el: any) {
  if (!el) return
  bpmnSelId.value    = el.id
  bpmnSelName.value  = el.businessObject?.name || ''
  bpmnSelType.value  = el.type
  bpmnSelIcon.value  = getBpmnElIcon(el)
  bpmnCurrentLink.value = bpmnTaskLinks.value[el.id] || null
  if (el.di?.fill) bpmnSelColor.value = el.di.fill
  bpmnActiveTab.value = isBpmnTask(el) ? 'activity' : 'general'
}

// ── Init modeler ──────────────────────────────────────────────────────────
async function initBpmnModeler(xml: string) {
  if (!bpmnCanvas.value) return
  await destroyBpmnModeler()

  bpmnLoading.value = true
  bpmnDirty.value   = false
  bpmnTaskLinks.value = {}
  bpmnSelectedEl.value = null

  try {
    if (isLocked.value) {
      const mod = await import('bpmn-js/lib/NavigatedViewer')
      const NV  = (mod.default ?? mod) as any
      bpmnModeler = new NV({ container: bpmnCanvas.value })
    } else {
      const mod     = await import('bpmn-js/lib/Modeler')
      const Modeler = (mod.default ?? mod) as any
      bpmnModeler   = new Modeler({ container: bpmnCanvas.value, keyboard: { bindTo: bpmnCanvas.value } })
    }

    await bpmnModeler.importXML(xml)
    bpmnModeler.get('canvas').zoom('fit-viewport')

    if (!isLocked.value) {
      // Détecter participant principal
      const reg   = bpmnModeler.get('elementRegistry')
      const parts = reg.getAll().filter((e: any) => e.type === 'bpmn:Participant')
      bpmnMainParticipant = parts.length > 0 ? parts[0] : null
      applyDefaultStyles()
      setupBpmnEvents()
      startBpmnAutoSave()
    }
  } catch (e: any) {
    console.error('BPMN init error:', e)
  } finally {
    bpmnLoading.value = false
  }
}

function applyDefaultStyles() {
  if (!bpmnModeler) return
  const modeling = bpmnModeler.get('modeling')
  const reg      = bpmnModeler.get('elementRegistry')
  for (const el of reg.getAll()) {
    if (isBpmnTask(el))              modeling.setColor(el, { fill:'#fff', stroke:'#1565C0', strokeWidth:2 })
    else if (el.type?.includes('Event'))   modeling.setColor(el, { fill:'#fff', stroke:'#15803d', strokeWidth:2 })
    else if (el.type?.includes('Gateway')) modeling.setColor(el, { fill:'#fff', stroke:'#f59e0b', strokeWidth:2 })
    else if (el.type === 'bpmn:Participant') modeling.setColor(el, { fill:'#fff', stroke:'#1e293b', strokeWidth:2 })
    else if (el.type === 'bpmn:Lane')       modeling.setColor(el, { fill:'#f8fafc', stroke:'#1e293b', strokeWidth:2 })
  }
}

function setupBpmnEvents() {
  if (!bpmnModeler) return
  const bus = bpmnModeler.get('eventBus')

  bus.on('element.click', (ev: any) => {
    bpmnSelectedEl.value = markRaw(ev.element)
    updateBpmnSelInfo(ev.element)
    closeCtxMenu()
  })

  bus.on('commandStack.changed', () => {
    bpmnDirty.value     = true
    bpmnSaveStatus.value = 'idle'
    scheduleBpmnAutoSave()
  })

  bus.on('canvas.viewbox.changed', (ev: any) => {
    bpmnZoomLevel.value = Math.round((ev.viewbox?.scale ?? 1) * 100)
  })
}

async function destroyBpmnModeler() {
  stopBpmnAutoSave()
  if (bpmnModeler) {
    try { bpmnModeler.destroy() } catch {}
    bpmnModeler = null
    bpmnMainParticipant = null
  }
}

async function renderBpmn() {
  const procId = bpmnProcId.value
  if (!procId) return
  const xml = getDisplayXml(procId)
  if (!xml) {
    await destroyBpmnModeler()
    bpmnLoading.value = false
    return
  }
  await initBpmnModeler(xml)
}

// ── Auto-save ─────────────────────────────────────────────────────────────
function scheduleBpmnAutoSave() {
  if (bpmnAutoSaveTimer) clearTimeout(bpmnAutoSaveTimer)
  bpmnAutoSaveTimer = setTimeout(performBpmnAutoSave, 3000)
}
function startBpmnAutoSave() { /* déclenché par commandStack.changed */ }
function stopBpmnAutoSave() {
  if (bpmnAutoSaveTimer) { clearTimeout(bpmnAutoSaveTimer); bpmnAutoSaveTimer = null }
}

async function performBpmnAutoSave() {
  if (!bpmnModeler || !bpmnDirty.value || isLocked.value) return
  const procId = bpmnProcId.value
  if (!procId) return
  try {
    bpmnSaveStatus.value = 'saving'
    const { xml } = await bpmnModeler.saveXML({ format: true })
    localBpmnMap[procId] = xml
    await persistBpmnAnnotations()
    bpmnDirty.value       = false
    bpmnSaveStatus.value  = 'saved'
    setTimeout(() => { if (bpmnSaveStatus.value === 'saved') bpmnSaveStatus.value = 'idle' }, 3000)
  } catch {
    bpmnSaveStatus.value = 'error'
  }
}

async function saveLocalBpmn() {
  if (!bpmnModeler || isLocked.value) return
  const procId = bpmnProcId.value
  if (!procId) return
  bpmnSaving.value = true
  try {
    const { xml } = await bpmnModeler.saveXML({ format: true })
    localBpmnMap[procId] = xml
    await persistBpmnAnnotations()
    bpmnDirty.value  = false
    bpmnSaved.value  = true
    bpmnSaveStatus.value = 'saved'
    showToast('success', 'BPMN annoté sauvegardé')
    setTimeout(() => { bpmnSaved.value = false; bpmnSaveStatus.value = 'idle' }, 3000)
  } catch (e: any) {
    showToast('error', 'Erreur : ' + e.message)
    bpmnSaveStatus.value = 'error'
  } finally {
    bpmnSaving.value = false
  }
}

async function persistBpmnAnnotations() {
  if (!props.missionId) return
  const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
  const annotations: Record<string,string> = {}
  for (const [k, v] of Object.entries(localBpmnMap)) annotations[k] = v

  const url    = form.id ? `${props.formUrl}/${form.id}` : props.formUrl
  const method = form.id ? 'PUT' : 'POST'

  await fetch(url, {
    method,
    headers: { 'Content-Type':'application/json','X-CSRF-TOKEN':csrf,Accept:'application/json' },
    body: JSON.stringify({
      mission_id:       props.missionId,
      assignment_id:    props.assignmentId,
      bpmn_annotations: JSON.stringify(annotations),
      processus:        JSON.stringify(buildPayload()),
      synthese:         synthese.value,
      fait_par:         form.fait_par,
      revue_par:        form.revue_par,
      acteurs:          '[]', flux:'[]', observations:'[]',
    }),
  })
}

async function resetToOfficial() {
  if (!bpmnProcId.value) return
  if (!confirm('Réinitialiser vers le BPMN officiel ? Vos annotations seront perdues.')) return
  const procId = bpmnProcId.value
  delete localBpmnMap[procId]
  bpmnDirty.value = false
  await destroyBpmnModeler()
  nextTick(() => renderBpmn())
  showToast('success', 'Réinitialisé vers la version officielle')
}

// ── Element creation ──────────────────────────────────────────────────────
function createBpmnElement(type: string, name: string) {
  if (!bpmnModeler || !bpmnMainParticipant) return
  const modeling = bpmnModeler.get('modeling')
  const cv       = bpmnModeler.get('canvas')
  const g        = cv.getGraphics(bpmnMainParticipant)
  if (!g) return
  const x = g.x + 50 + Math.random() * (g.width  - 150)
  const y = g.y + 50 + Math.random() * (g.height - 150)
  const shape = modeling.createShape({ type }, { x, y }, bpmnMainParticipant)
  if (name) modeling.updateProperties(shape, { name })
  applyBpmnDefaultColor(shape)
  bpmnSelectedEl.value = markRaw(shape)
  updateBpmnSelInfo(shape)
}

function applyBpmnDefaultColor(shape: any) {
  if (!bpmnModeler) return
  const modeling = bpmnModeler.get('modeling')
  if (isBpmnTask(shape))                  modeling.setColor(shape, { fill:'#fff', stroke:'#1565C0', strokeWidth:2 })
  else if (shape.type?.includes('Event'))  modeling.setColor(shape, { fill:'#fff', stroke:'#15803d', strokeWidth:2 })
  else if (shape.type?.includes('Gateway'))modeling.setColor(shape, { fill:'#fff', stroke:'#f59e0b', strokeWidth:2 })
  else if (shape.type==='bpmn:Participant')modeling.setColor(shape, { fill:'#fff', stroke:'#1e293b', strokeWidth:2 })
  else if (shape.type==='bpmn:Lane')       modeling.setColor(shape, { fill:'#f8fafc', stroke:'#1e293b', strokeWidth:2 })
}

function addParticipant() {
  if (!bpmnModeler) return
  const modeling = bpmnModeler.get('modeling')
  const cv       = bpmnModeler.get('canvas')
  const ef       = bpmnModeler.get('elementFactory')
  const part = ef.createShape({ type:'bpmn:Participant' })
  const b    = bpmnMainParticipant ? cv.getGraphics(bpmnMainParticipant) : null
  const s = modeling.createShape(part, { x:(b?b.x+b.width+50:100), y:(b?b.y:100), width:400, height:300 }, cv.getRootElement())
  modeling.updateProperties(s, { name:'Nouveau Participant' })
  modeling.setColor(s, { fill:'#fff', stroke:'#1e293b', strokeWidth:2 })
  bpmnSelectedEl.value = markRaw(s); updateBpmnSelInfo(s)
}

function addLane() {
  if (!bpmnModeler || !bpmnMainParticipant) return
  const modeling = bpmnModeler.get('modeling')
  const ef       = bpmnModeler.get('elementFactory')
  const lane = ef.createShape({ type:'bpmn:Lane' })
  const s = modeling.createShape(lane, { x:0,y:0,width:400,height:100 }, bpmnMainParticipant)
  modeling.updateProperties(s, { name:'Nouveau Couloir' })
  modeling.setColor(s, { fill:'#f8fafc', stroke:'#1e293b', strokeWidth:2 })
  bpmnSelectedEl.value = markRaw(s); updateBpmnSelInfo(s)
}

// ── Color & style ──────────────────────────────────────────────────────────
function applyBpmnColor(color: string) {
  if (!bpmnSelectedEl.value || !bpmnModeler) return
  bpmnSelColor.value = color
  const modeling = bpmnModeler.get('modeling')
  if (isBpmnTask(bpmnSelectedEl.value) || isBpmnParticipant(bpmnSelectedEl.value) || isBpmnLane(bpmnSelectedEl.value)) {
    modeling.setColor(bpmnSelectedEl.value, { fill:color, stroke:color, strokeWidth:2 })
  }
  if (bpmnTaskLinks.value[bpmnSelectedEl.value.id]) bpmnTaskLinks.value[bpmnSelectedEl.value.id].color_hex = color
}

function resetBpmnElement() {
  if (!bpmnSelectedEl.value || !bpmnModeler) return
  applyBpmnDefaultColor(bpmnSelectedEl.value)
  bpmnSelColor.value = '#1565C0'
  delete bpmnTaskLinks.value[bpmnSelectedEl.value.id]
  bpmnCurrentLink.value = null
  closeCtxMenu()
}

// ── Element actions ────────────────────────────────────────────────────────
function deleteBpmnElement() {
  if (!bpmnSelectedEl.value) return
  bpmnModeler.get('modeling').removeShape(bpmnSelectedEl.value)
  delete bpmnTaskLinks.value[bpmnSelectedEl.value.id]
  bpmnSelectedEl.value = null
  closeCtxMenu()
}

function duplicateBpmnElement() {
  if (!bpmnSelectedEl.value || !bpmnModeler) return
  const cv = bpmnModeler.get('canvas')
  const g  = cv.getGraphics(bpmnSelectedEl.value)
  bpmnModeler.get('modeling').copyShape(bpmnSelectedEl.value, { x:g.x+50,y:g.y+50 }, bpmnSelectedEl.value.parent || cv.getRootElement())
  closeCtxMenu()
}

function resizeBpmnElement(action: 'larger'|'smaller') {
  if (!bpmnSelectedEl.value || !bpmnModeler) return
  const g = bpmnModeler.get('canvas').getGraphics(bpmnSelectedEl.value)
  if (!g) return
  const f = action==='larger' ? 1.2 : 0.8
  bpmnModeler.get('modeling').resizeShape(bpmnSelectedEl.value, { x:g.x,y:g.y,width:g.width*f,height:g.height*f })
}

function updateBpmnElName() {
  if (!bpmnSelectedEl.value || !bpmnModeler) return
  bpmnModeler.get('modeling').updateProperties(bpmnSelectedEl.value, { name:bpmnSelName.value })
}

// ── Activity linking ───────────────────────────────────────────────────────
function linkBpmnActivity(act: any) {
  if (!bpmnSelectedEl.value || !bpmnModeler) return
  const modeling = bpmnModeler.get('modeling')
  const label    = act.code + '\n' + act.name
  modeling.updateProperties(bpmnSelectedEl.value, { name:label })
  bpmnSelName.value = label
  modeling.setColor(bpmnSelectedEl.value, { fill:'#dbeafe', stroke:'#1565C0', strokeWidth:2 })
  bpmnSelColor.value = '#1565C0'
  const link = {
    element_id:   bpmnSelectedEl.value.id,
    element_name: label,
    element_type: bpmnSelectedEl.value.type,
    color_hex:    '#1565C0',
    activity_id:   act.id,
    activity_name: act.name,
    activity_code: act.code,
  }
  bpmnTaskLinks.value[bpmnSelectedEl.value.id] = link
  bpmnCurrentLink.value = link
  bpmnActSearch.value   = ''
}

function unlinkBpmnActivity() {
  if (!bpmnSelectedEl.value) return
  delete bpmnTaskLinks.value[bpmnSelectedEl.value.id]
  bpmnCurrentLink.value = null
  bpmnModeler.get('modeling').updateProperties(bpmnSelectedEl.value, { name:'' })
  bpmnSelName.value = ''
}

// ── Drag & drop ────────────────────────────────────────────────────────────
function dragStart(ev: DragEvent, item: any) {
  draggingBpmnEl.value = item
  ev.dataTransfer?.setData('text/plain', JSON.stringify(item))
}

function handleDrop(ev: DragEvent) {
  if (!draggingBpmnEl.value || !bpmnModeler || !bpmnMainParticipant) return
  const rect = bpmnCanvas.value?.getBoundingClientRect()
  if (!rect) return
  const x = ev.clientX - rect.left
  const y = ev.clientY - rect.top
  const bounds = bpmnModeler.get('canvas').getGraphics(bpmnMainParticipant)
  if (!bounds) return
  const ax = Math.max(bounds.x+20, Math.min(x, bounds.x+bounds.width-100))
  const ay = Math.max(bounds.y+20, Math.min(y, bounds.y+bounds.height-100))
  const shape = bpmnModeler.get('modeling').createShape({ type:draggingBpmnEl.value.type }, { x:ax,y:ay }, bpmnMainParticipant)
  if (draggingBpmnEl.value.name) bpmnModeler.get('modeling').updateProperties(shape, { name:draggingBpmnEl.value.name })
  applyBpmnDefaultColor(shape)
  bpmnSelectedEl.value = markRaw(shape); updateBpmnSelInfo(shape)
  draggingBpmnEl.value = null
}

// ── Context menu ───────────────────────────────────────────────────────────
function handleContextMenu(ev: MouseEvent) {
  if (!bpmnCanvas.value) return
  const rect = bpmnCanvas.value.getBoundingClientRect()
  ctxX.value = ev.clientX - rect.left
  ctxY.value = ev.clientY - rect.top
  showCtxMenu.value = true
}
function closeCtxMenu() { showCtxMenu.value = false }

// ── Zoom ────────────────────────────────────────────────────────────────────
function zoomIn()  { bpmnModeler?.get('canvas').zoom(bpmnModeler.get('canvas').zoom() * 1.2) }
function zoomOut() { bpmnModeler?.get('canvas').zoom(bpmnModeler.get('canvas').zoom() * 0.8) }
function fitBpmn() { try { bpmnModeler?.get('canvas').zoom('fit-viewport') } catch {} }

// ── Modal Forces/Faiblesses ────────────────────────────────────────────────
const ffModal = reactive({ open:false, procId:null as number|null, newForce:'', newFaib:'' })
const ffProc  = computed(() => ffModal.procId !== null ? allTableProcesses.value.find(p => p.id === ffModal.procId) ?? null : null)

function openFf(procId: number) { ffModal.procId=procId; ffModal.newForce=''; ffModal.newFaib=''; ffModal.open=true }
function closeFf() { ffModal.open=false }

function ffLines(field: 'forces'|'faiblesses'): string[] {
  if (ffModal.procId===null) return []
  return (procForms[ffModal.procId]?.[field]??'').split('\n').map((l:string)=>l.trim()).filter(Boolean)
}
function addLine(field: 'forces'|'faiblesses') {
  if (ffModal.procId===null) return
  const val = field==='forces' ? ffModal.newForce.trim() : ffModal.newFaib.trim()
  if (!val) return
  const existing = procForms[ffModal.procId][field]??''
  procForms[ffModal.procId][field] = existing ? existing+'\n'+val : val
  if (field==='forces') ffModal.newForce=''; else ffModal.newFaib=''
}
function deleteLine(field: 'forces'|'faiblesses', idx: number) {
  if (ffModal.procId===null) return
  const lines=ffLines(field); lines.splice(idx,1)
  procForms[ffModal.procId][field]=lines.join('\n')
}
function countLines(val?: string): number { return (val??'').split('\n').map((l:string)=>l.trim()).filter(Boolean).length }
function ioLines(val?: string): string[]  { return (val??'').split('\n').map((l:string)=>l.trim()).filter(Boolean) }

// ── Formulaires par processus ──────────────────────────────────────────────
const procForms = reactive<Record<number,any>>({})

function makeForm(proc: any, saved: any) {
  return {
    objectif_strategique:  saved.objectif_strategique  ?? '',
    objectif_operationnel: saved.objectif_operationnel ?? '',
    indicateur:            saved.indicateur            ?? '',
    proprietaire:          saved.proprietaire          ?? '',
    autres_intervenants:   saved.autres_intervenants   ?? '',
    entrees:               saved.entrees               ?? proc.default_entrees ?? proc.entrees ?? '',
    sorties:               saved.sorties               ?? proc.default_sorties ?? proc.sorties ?? '',
    forces:                saved.forces                ?? '',
    faiblesses:            saved.faiblesses            ?? '',
    observations:          saved.observations          ?? '',
    _entrees_edited:       !!(saved.entrees),
    _sorties_edited:       !!(saved.sorties),
  }
}

function initProcForms() {
  const saved = JSON.parse(props.form?.processus ?? '[]') as any[]
  for (const proc of (props.processesData as any[])) {
    const s = saved.find((x:any) => x.process_id === proc.id) ?? {}
    procForms[proc.id] = makeForm(proc, s)
  }
}
initProcForms()

// ── Intervenants ────────────────────────────────────────────────────────────
function selectedIntervenants(procId: number): string[] {
  return (procForms[procId]?.autres_intervenants??'').split(',').map((s:string)=>s.trim()).filter(Boolean)
}
function onAddIntervenant(procId: number, sel: HTMLSelectElement) {
  const val=sel.value; sel.value=''
  if (!val) return
  const cur=selectedIntervenants(procId)
  if (!cur.includes(val)) procForms[procId].autres_intervenants=[...cur,val].join(', ')
}
function removeIntervenant(procId: number, val: string) {
  procForms[procId].autres_intervenants=selectedIntervenants(procId).filter(v=>v!==val).join(', ')
}

// ── Computed ─────────────────────────────────────────────────────────────────
const allTableProcesses = computed<any[]>(() => [
  ...(props.processesData as any[]),
  ...addedUnlinkedList.value,
])
const availableUnlinked = computed<any[]>(() =>
  (props.unlinkedProcesses as any[]).filter(p => !addedUnlinkedIds.value.has(p.id))
)
const currentBpmnProc = computed(() =>
  bpmnProcId.value ? allTableProcesses.value.find(p => p.id === bpmnProcId.value) ?? null : allTableProcesses.value[0] ?? null
)
const canManage = computed(() => ['DM','CM'].includes(props.auditorRole??''))
const isLocked  = computed(() =>
  ap.validation_status==='validated' || (ap.validation_status==='in_review' && !canManage.value)
)
const totalActivities = computed(() => {
  const ids = new Set<number>()
  for (const p of allTableProcesses.value) for (const a of (p.activities??[])) ids.add(a.id)
  return ids.size
})
function isUnlinked(id: number) { return addedUnlinkedIds.value.has(id) }

// ── Hors mission ──────────────────────────────────────────────────────────────
function addUnlinked(proc: any) {
  addedUnlinkedIds.value.add(proc.id)
  addedUnlinkedList.value=[...addedUnlinkedList.value, proc]
  if (!procForms[proc.id]) {
    const saved=JSON.parse(props.form?.processus??'[]') as any[]
    const s=saved.find((x:any)=>x.process_id===proc.id)??{}
    procForms[proc.id]=makeForm(proc,s)
  }
  bpmnProcId.value=proc.id
  if (viewMode.value==='table') viewMode.value='split'
  nextTick(()=>renderBpmn())
}
function removeUnlinked(proc: any) {
  addedUnlinkedIds.value.delete(proc.id)
  addedUnlinkedList.value=addedUnlinkedList.value.filter(p=>p.id!==proc.id)
  if (bpmnProcId.value===proc.id && allTableProcesses.value.length>0) {
    bpmnProcId.value=allTableProcesses.value[0].id
    nextTick(()=>renderBpmn())
  }
}

// ── Mode ───────────────────────────────────────────────────────────────────────
function setMode(m: 'table'|'split'|'bpmn') {
  viewMode.value=m
  if (m!=='table') nextTick(()=>renderBpmn())
}

function onBpmnChange() {
  bpmnDirty.value=false
  bpmnSelectedEl.value=null
  nextTick(()=>renderBpmn())
}

function onProcRowClick(procId: number) {
  if (bpmnProcId.value===procId) return
  bpmnProcId.value=procId
  if (viewMode.value==='table') viewMode.value='split'
  nextTick(()=>renderBpmn())
}

// ── Resize ────────────────────────────────────────────────────────────────────
let isRes=false, startX=0, startL=55
function startResize(e: MouseEvent) {
  isRes=true; startX=e.clientX; startL=splitLeft.value
  document.addEventListener('mousemove', onResize)
  document.addEventListener('mouseup', stopResize)
  document.body.style.cursor='col-resize'
  document.body.style.userSelect='none'
}
function onResize(e: MouseEvent) {
  if (!isRes||!splitContainer.value) return
  const pct=startL+((e.clientX-startX)/splitContainer.value.getBoundingClientRect().width)*100
  splitLeft.value=Math.min(80,Math.max(20,pct))
  const L=splitContainer.value.querySelector('.ap-pane-left') as HTMLElement|null
  const R=splitContainer.value.querySelector('.ap-pane-right') as HTMLElement|null
  if (L) L.style.width=splitLeft.value+'%'
  if (R) R.style.width=(100-splitLeft.value-0.4)+'%'
}
function stopResize() {
  isRes=false
  document.removeEventListener('mousemove',onResize)
  document.removeEventListener('mouseup',stopResize)
  document.body.style.cursor=''
  document.body.style.userSelect=''
  setTimeout(()=>fitBpmn(),50)
}

// ── Lifecycle ──────────────────────────────────────────────────────────────────
onMounted(() => {
  if (allTableProcesses.value.length) bpmnProcId.value=allTableProcesses.value[0].id
  if (viewMode.value!=='table') nextTick(()=>renderBpmn())
})
onBeforeUnmount(async () => {
  stopBpmnAutoSave()
  document.removeEventListener('mousemove',onResize)
  document.removeEventListener('mouseup',stopResize)
  await destroyBpmnModeler()
})

// ── Build payload ──────────────────────────────────────────────────────────────
function buildPayload() {
  return allTableProcesses.value.map(proc => ({
    process_id:proc.id, process_code:proc.code, process_name:proc.name,
    is_linked:!isUnlinked(proc.id),
    ...Object.fromEntries(Object.entries(procForms[proc.id]??{}).filter(([k])=>!k.startsWith('_'))),
  }))
}

// ── Submit ────────────────────────────────────────────────────────────────────
async function submit() {
  if (isLocked.value) return
  processing.value=true

  // Capturer XML avant soumission
  if (bpmnModeler && bpmnProcId.value) {
    try { const { xml } = await bpmnModeler.saveXML({ format:true }); localBpmnMap[bpmnProcId.value]=xml } catch {}
  }

  const annotations: Record<string,string>={}
  for (const [k,v] of Object.entries(localBpmnMap)) annotations[k]=v

  const payload = {
    mission_id:props.missionId, assignment_id:props.assignmentId,
    fait_par:form.fait_par, revue_par:form.revue_par, synthese:synthese.value,
    processus:JSON.stringify(buildPayload()),
    bpmn_annotations:JSON.stringify(annotations),
    acteurs:'[]', flux:'[]', observations:'[]',
  }

  const url    = form.id ? `${props.formUrl}/${form.id}` : props.formUrl
  const method: 'post'|'put' = form.id ? 'put' : 'post'

  router[method](url, payload, {
    preserveScroll:true,
    onSuccess:(page:any) => {
      const n=page.props?.form
      if (n) { if (!form.id) form.id=n.id; if (n.code) form.code=n.code; Object.assign(ap,n) }
      bpmnDirty.value=false
      showToast('success','Analyse enregistrée')
    },
    onError: ()=>showToast('error','Erreur — vérifiez les champs'),
    onFinish:()=>{ processing.value=false },
  })
}

function annuler() {
  initProcForms(); synthese.value=''
  addedUnlinkedIds.value=new Set(); addedUnlinkedList.value=[]
  Object.assign(form,{id:null,code:'',fait_par:auditorFullName(),revue_par:''})
  Object.assign(ap,{})
  for (const k in localBpmnMap) delete localBpmnMap[k as any]
  initLocalBpmn()
}

function loadAp(item: any) {
  router.visit(`${props.formUrl}/${item.id}/edit?mission_id=${props.missionId}&assignment_id=${props.assignmentId}`)
}

// ── Workflow ───────────────────────────────────────────────────────────────────
async function soumettre() {
  if (!form.id) { showToast('error',"Enregistrez d'abord."); return }
  if (!confirm("Soumettre l'analyse ?")) return
  await apiPost(`${props.formUrl}/${form.id}/soumettre`,
    {mission_id:props.missionId,assignment_id:props.assignmentId},
    (j:any)=>{ ap.validation_status=j.status; showToast('success','Soumis') })
}
async function valider(action: string, note?: string) {
  await apiPost(`${props.formUrl}/${form.id}/valider`,
    {mission_id:props.missionId,assignment_id:props.assignmentId,action,note},
    (j:any)=>{ ap.validation_status=j.status; showToast('success',action==='validated'?'Validé ✓':'Rejeté') })
}
function promptReject() { const n=prompt('Motif du rejet :'); if (!n?.trim()) return; valider('rejected',n) }

async function apiPost(url: string, body: object, onOk: (j:any)=>void) {
  processing.value=true
  try {
    const csrf=(document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content??''
    const r=await fetch(url,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,Accept:'application/json'},body:JSON.stringify(body)})
    const j=await r.json(); if (!r.ok) throw new Error(j?.message??'Erreur'); onOk(j)
  } catch(e:any){ showToast('error',e.message) } finally { processing.value=false }
}

const toast=ref({show:false,type:'success',msg:''})
let tt: ReturnType<typeof setTimeout>
function showToast(type: string, msg: string) {
  if (tt) clearTimeout(tt)
  toast.value={show:true,type,msg}
  tt=setTimeout(()=>{ toast.value.show=false },3200)
}
function vstLbl(s: string) { return ({draft:'Brouillon',in_review:'En attente',validated:'Validé ✓',rejected:'Rejeté'} as any)[s]??s }
function vstIcon(s: string) { return ({draft:'ti ti-pencil',in_review:'ti ti-clock',validated:'ti ti-circle-check',rejected:'ti ti-circle-x'} as any)[s]??'ti ti-circle' }
</script>

<style scoped>
/* ── imports bpmn-js ── */
@import 'bpmn-js/dist/assets/diagram-js.css';
@import 'bpmn-js/dist/assets/bpmn-font/css/bpmn.css';
@import 'bpmn-js/dist/assets/bpmn-font/css/bpmn-codes.css';
@import 'bpmn-js/dist/assets/bpmn-font/css/bpmn-embedded.css';

*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

.ap-shell{display:flex;flex-direction:column;min-height:100vh;background:#f0f4f8;font-family:'Segoe UI',system-ui,sans-serif;
  --mc:#1565C0;--or:#f59e0b;--gr:#15803d;--rd:#dc2626;--vi:#7c3aed}
.ap-body{flex:1;padding:12px 16px 24px;display:flex;flex-direction:column;gap:10px}

/* ═══ Header ═══ */
.ap-header{position:sticky;top:0;z-index:100;background:#fff;border-bottom:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.06);padding:0 16px}
.ap-hrow{display:flex;align-items:center;gap:10px;min-height:58px;padding:6px 0;flex-wrap:wrap}
.ap-back{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:7px;background:#f1f5f9;border:1px solid #e2e8f0;color:#64748b;text-decoration:none;flex-shrink:0;transition:all .15s}
.ap-back:hover{background:var(--mc);color:#fff;border-color:var(--mc)}
.ap-hinfo{flex:1;min-width:0}
.ap-chips{display:flex;align-items:center;gap:4px;flex-wrap:wrap;margin-bottom:2px}
.ap-chip{display:inline-flex;align-items:center;gap:3px;font-size:.6rem;font-weight:700;padding:2px 7px;border-radius:9px;text-transform:uppercase;letter-spacing:.04em}
.ap-chip-sm{font-size:.58rem;font-weight:700;padding:1px 5px;border-radius:7px;text-transform:uppercase}
.chip-draft{background:#f1f5f9;color:#64748b}
.chip-in_review{background:#e3f2fd;color:#1565C0;border:1px solid rgba(21,101,192,.2)}
.chip-validated{background:#d1e7dd;color:#0f5132}
.chip-rejected{background:#f8d7da;color:#842029}
.chip-type{background:rgba(245,158,11,.15);color:#b45309}
.chip-role-DM{background:rgba(251,191,36,.2);color:#d97706}
.chip-role-CM{background:rgba(21,101,192,.12);color:#1565C0}
.chip-role-AS{background:rgba(22,163,74,.12);color:#15803d}
.chip-role-AJ{background:rgba(124,58,237,.12);color:#6d28d9}
.ap-code{font-family:monospace;font-size:.66rem;font-weight:700;padding:2px 7px;border-radius:5px;background:color-mix(in srgb,var(--mc) 8%,white);border:1px solid color-mix(in srgb,var(--mc) 25%,transparent);color:var(--mc)}
.ap-title{font-size:.88rem;font-weight:700;color:#1a1a2e}
.ap-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:2px}
.ap-meta span{display:inline-flex;align-items:center;gap:3px;font-size:.67rem;color:#64748b}
.ap-banner{display:flex;align-items:center;gap:6px;padding:5px 0 8px;font-size:.76rem;border-top:1px solid #f1f5f9}
.banner-lock{color:#0f5132}.banner-review{color:#1565C0}
.ap-view-toggle{display:flex;gap:2px;background:#f1f5f9;border-radius:8px;padding:3px;flex-shrink:0}
.vt-btn{width:32px;height:28px;border:none;border-radius:6px;background:transparent;color:#64748b;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.85rem;transition:all .15s}
.vt-btn.active{background:var(--mc);color:#fff;box-shadow:0 1px 4px rgba(21,101,192,.3)}
.vt-btn:hover:not(.active){background:#e2e8f0}

/* Empty */
.ap-empty{text-align:center;padding:60px;color:#94a3b8;display:flex;flex-direction:column;align-items:center;gap:10px}
.ap-empty i{font-size:2.5rem;color:var(--or)}
.ap-empty strong{color:#475569}.ap-empty p{font-size:.79rem}

/* Unlinked bar */
.unlinked-bar{display:flex;align-items:flex-start;gap:10px;background:#fff;border:1px dashed #cbd5e1;border-radius:10px;padding:8px 12px;flex-wrap:wrap}
.unlinked-bar-lbl{display:inline-flex;align-items:center;gap:5px;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;white-space:nowrap;flex-shrink:0;margin-top:4px}
.unlinked-pills{display:flex;flex-wrap:wrap;gap:5px;flex:1}
.btn-unlinked{display:inline-flex;align-items:center;gap:5px;padding:3px 10px 3px 8px;border-radius:14px;cursor:pointer;font-family:inherit;font-size:.7rem;transition:all .14s;background:#f8fafc;border:1.5px dashed #cbd5e1;color:#64748b}
.btn-unlinked:hover{background:#f0f6ff;border-color:var(--mc);color:var(--mc);border-style:solid}
.btn-unlinked code{font-size:.66rem;font-weight:700;color:inherit}
.ul-name{max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.add-ico,.rem-ico{font-size:.72rem;flex-shrink:0}
.add-ico{color:#94a3b8}
.btn-ul-added{background:color-mix(in srgb,var(--vi) 8%,white);border:1.5px solid color-mix(in srgb,var(--vi) 35%,transparent);border-style:solid;color:#6d28d9}
.btn-ul-added:hover{background:#ede9fe;border-color:var(--vi);color:#5b21b6}
.rem-ico{color:var(--vi)}

/* Split */
.ap-split{display:flex;gap:0;flex:1;background:transparent;min-height:500px;position:relative}
.mode-table .ap-pane-left{width:100%}
.mode-bpmn  .ap-pane-right{width:100%}
.mode-split .ap-pane-left{width:55%;flex-shrink:0}
.mode-split .ap-pane-right{flex:1;min-width:0}
.ap-pane{display:flex;flex-direction:column;gap:8px;min-width:0}

/* Pane header */
.pane-hd{display:flex;align-items:center;justify-content:space-between;background:#fff;border:1px solid #e2e8f0;border-radius:9px;padding:9px 12px;flex-wrap:wrap;gap:6px}
.pane-hd-bpmn{border-radius:9px 9px 0 0;border-bottom:none}
.pane-hd-l{display:flex;align-items:center;gap:7px;font-size:.78rem;font-weight:600;color:#1a1a2e;min-width:0;flex-wrap:wrap}
.pane-hd-l>i{color:var(--mc);font-size:.9rem;flex-shrink:0}
.pane-hd-r{display:flex;align-items:center;gap:5px;flex-shrink:0;flex-wrap:wrap}
.risk-badge{display:inline-flex;align-items:center;gap:4px;font-size:.68rem;font-weight:700;padding:3px 9px;border-radius:7px;background:#fff3cd;color:#92400e;border:1px solid #fde68a}
.bpmn-sel{font-size:.72rem;padding:3px 8px;border:1px solid #e2e8f0;border-radius:5px;background:#f8fafc;color:#374151;font-family:inherit;outline:none;cursor:pointer;max-width:200px}
.bpmn-sel:focus{border-color:var(--mc)}
.bpmn-badge-local{display:inline-flex;align-items:center;gap:3px;font-size:.62rem;font-weight:700;padding:2px 7px;border-radius:7px;background:#ede9fe;color:#6d28d9;border:1px solid #ddd6fe}
.bpmn-badge-official{display:inline-flex;align-items:center;gap:3px;font-size:.62rem;padding:2px 7px;border-radius:7px;background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0}
.bpmn-local-dot{display:inline-flex;align-items:center;margin-left:3px;color:#6d28d9;font-size:.68rem}

/* BPMN save pill */
.bpmn-save-pill{display:flex;align-items:center;gap:4px;padding:3px 9px;border-radius:50px;font-size:.7rem;border:1px solid;transition:all .3s}
.bpmn-save-pill.idle{background:rgba(100,116,139,.08);border-color:rgba(100,116,139,.2);color:#64748b}
.bpmn-save-pill.saving{background:rgba(21,101,192,.1);border-color:rgba(21,101,192,.3);color:var(--mc)}
.bpmn-save-pill.saved{background:rgba(21,128,61,.1);border-color:rgba(21,128,61,.3);color:#15803d}
.bpmn-save-pill.error{background:rgba(220,38,38,.1);border-color:rgba(220,38,38,.3);color:#dc2626}

/* BPMN toolbar buttons */
.bpmn-tb-btn{width:28px;height:28px;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc;color:#64748b;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.82rem;transition:all .15s}
.bpmn-tb-btn:hover{background:var(--mc);color:#fff;border-color:var(--mc)}
.bpmn-tb-btn.active{background:var(--mc);color:#fff;border-color:var(--mc)}
.bpmn-tb-reset{color:var(--rd);border-color:#fca5a5}.bpmn-tb-reset:hover{background:var(--rd)}
.bpmn-tb-save{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:6px;border:none;background:#6d28d9;color:#fff;font-size:.72rem;font-weight:600;cursor:pointer;transition:all .14s;font-family:inherit}
.bpmn-tb-save:hover:not(:disabled){background:#5b21b6}
.bpmn-tb-save:disabled{opacity:.45;cursor:not-allowed}

/* Info bar */
.bpmn-info-bar{display:flex;align-items:center;gap:7px;padding:5px 12px;background:#eff6ff;border:1px solid #bfdbfe;border-top:none;font-size:.69rem;color:#1e40af;flex-wrap:wrap}
.bpmn-info-bar i{flex-shrink:0}
.bpmn-dirty-dot{display:inline-flex;align-items:center;gap:4px;font-weight:700;color:var(--or);margin-left:auto}

/* BPMN Editor layout */
.bpmn-editor-layout{display:grid;grid-template-columns:200px 1fr 240px;gap:0;flex:1;min-height:0;height:calc(100% - 88px);background:#fff;border:1px solid #e2e8f0;border-radius:0 0 9px 9px;overflow:hidden}
.bpmn-editor-layout.no-bpmn-left{grid-template-columns:1fr 240px}
.bpmn-editor-layout.no-bpmn-right{grid-template-columns:200px 1fr}
.bpmn-editor-layout.no-bpmn-left.no-bpmn-right{grid-template-columns:1fr}

/* Palette gauche */
.bpmn-sidebar-left{display:flex;flex-direction:column;background:#f8fafc;border-right:1px solid #e2e8f0;overflow:hidden;height:100%}
.bsl-head{padding:.5rem .6rem;border-bottom:1px solid #e2e8f0;flex-shrink:0}
.bsl-title{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#374151;display:flex;align-items:center;gap:.35rem;margin-bottom:.4rem}
.bsl-search{width:100%;padding:3px 7px;border:1px solid #e2e8f0;border-radius:5px;font-size:.72rem;color:#374151;background:#fff;box-sizing:border-box;outline:none}
.bsl-search:focus{border-color:var(--mc)}
.bsl-body{flex:1;overflow-y:auto;padding:.4rem}
.btg{margin-bottom:.6rem}
.btg-label{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#94a3b8;padding:.2rem .3rem;display:flex;align-items:center;gap:.35rem;margin-bottom:.25rem}
.btg-grid{display:grid;grid-template-columns:1fr 1fr;gap:3px}
.btg-item{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:.45rem .25rem;border:1.5px solid #e2e8f0;background:#fff;border-radius:7px;cursor:grab;font-size:1.25rem;color:#475569;min-height:52px;transition:all .15s}
.btg-item:hover{border-color:var(--mc);color:var(--mc);background:#eff6ff;transform:translateY(-1px)}
.btg-item span{font-size:.6rem;margin-top:.25rem;color:#64748b;font-weight:500;text-align:center;line-height:1.2}

/* Canvas */
.bpmn-canvas-wrap{display:flex;flex-direction:column;position:relative;overflow:hidden;height:100%}
.bpmn-canvas-bar{display:flex;justify-content:space-between;align-items:center;padding:4px 8px;border-bottom:1px solid #f1f5f9;background:#fafafa;flex-shrink:0}
.bcb-left,.bcb-right{display:flex;align-items:center;gap:.3rem}
.bcb-btn{width:26px;height:26px;background:#fff;border:1px solid #e2e8f0;border-radius:5px;color:#475569;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.78rem;transition:all .15s}
.bcb-btn:hover{background:var(--mc);border-color:var(--mc);color:#fff}
.bcb-zoom{font-size:.72rem;font-weight:700;color:#475569;background:#fff;padding:.1rem .45rem;border-radius:4px;border:1px solid #e2e8f0}
.bcb-stat{font-size:.7rem;color:#64748b;display:flex;align-items:center;gap:.25rem;background:#fff;padding:.1rem .45rem;border-radius:4px;border:1px solid #e2e8f0}
.bcb-links{color:#6d28d9;border-color:#ddd6fe;background:#faf5ff}
.bpmn-canvas{flex:1;position:relative;background:
  repeating-linear-gradient(0deg,transparent,transparent 23px,rgba(21,101,192,.04) 23px,rgba(21,101,192,.04) 24px),
  repeating-linear-gradient(90deg,transparent,transparent 23px,rgba(21,101,192,.04) 23px,rgba(21,101,192,.04) 24px)}
.bpmn-canvas-loader{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;background:rgba(255,255,255,.96);z-index:50;gap:.6rem}
.bcl-ring{width:36px;height:36px;border:3px solid #e2e8f0;border-top-color:var(--mc);border-radius:50%;animation:spin .7s linear infinite}
.bpmn-canvas-loader p{color:#475569;font-size:.85rem;font-weight:600;margin:0}
.bpmn-no-diagram{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;color:#94a3b8;text-align:center;padding:24px}
.bpmn-no-diagram i{font-size:2.5rem;opacity:.3}
.bpmn-no-diagram p{font-size:.78rem}

/* Panneau props droite */
.bpmn-sidebar-right{display:flex;flex-direction:column;background:#fff;border-left:1px solid #e2e8f0;overflow:hidden;height:100%}
.bsr-head{display:flex;align-items:center;justify-content:space-between;padding:.5rem .75rem;border-bottom:1px solid #f1f5f9;background:#f8fafc;flex-shrink:0}
.bsr-title{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#374151;display:flex;align-items:center;gap:.35rem}
.bsr-close{background:none;border:none;color:#94a3b8;cursor:pointer;font-size:.9rem;padding:.1rem;border-radius:4px;transition:color .15s}
.bsr-close:hover{color:var(--rd)}
.bsr-body{flex:1;overflow-y:auto}

.bsr-nosel{display:flex;flex-direction:column;align-items:center;text-align:center;padding:1rem .75rem;color:#64748b}
.bsr-nosel-icon{font-size:1.8rem;opacity:.2;margin-bottom:.5rem}
.bsr-nosel h4{font-size:.82rem;color:#0f172a;margin-bottom:.25rem}
.bsr-nosel p{font-size:.75rem;line-height:1.5;margin-bottom:.75rem}
.bsr-acts{width:100%;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:8px;overflow:hidden}
.bsr-acts-hd{display:flex;align-items:center;gap:.4rem;padding:.45rem .6rem;background:#f1f5f9;border-bottom:1px solid #e2e8f0;font-size:.72rem;font-weight:700;color:#374151}
.bsr-acts-badge{margin-left:auto;background:var(--mc);color:#fff;font-size:.65rem;font-weight:700;padding:.1rem .4rem;border-radius:50px}
.bsr-acts-badge-0{background:#94a3b8}
.bsr-acts-list{max-height:180px;overflow-y:auto}
.bsr-act-item{display:flex;align-items:center;gap:.5rem;padding:.4rem .6rem;border-bottom:1px solid #f1f5f9;font-size:.75rem}
.bsr-act-item:last-child{border-bottom:none}
.bsr-act-item code{color:var(--mc);font-size:.68rem;flex-shrink:0}
.bsr-act-item span{color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.bsr-act-empty{display:flex;flex-direction:column;align-items:center;gap:.35rem;padding:1rem;font-size:.75rem;color:#94a3b8}
.bsr-act-empty i{font-size:1.2rem;opacity:.35}

.bsr-props{display:flex;flex-direction:column;height:100%}
.bsr-el-head{display:flex;align-items:flex-start;gap:.6rem;padding:.75rem;border-bottom:1px solid #f1f5f9;background:#f8fafc}
.bsr-el-icon{width:34px;height:34px;background:#eff6ff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:var(--mc);flex-shrink:0}
.bsr-el-name{font-size:.82rem;font-weight:700;color:#0f172a;margin-bottom:.1rem;word-break:break-word}
.bsr-el-id{font-size:.65rem;color:#94a3b8;display:block;margin-bottom:.2rem}
.bsr-el-type{font-size:.65rem;background:#f0f9ff;color:#0369a1;padding:.1rem .4rem;border-radius:4px;font-weight:600}
.bsr-tabs{display:flex;border-bottom:1px solid #f1f5f9;background:#fafafa;flex-shrink:0;overflow-x:auto}
.bsr-tab{display:flex;align-items:center;gap:.25rem;background:none;border:none;border-bottom:2px solid transparent;padding:.45rem .55rem;font-size:.72rem;font-weight:600;color:#94a3b8;cursor:pointer;transition:all .15s;white-space:nowrap}
.bsr-tab:hover{color:#374151}
.bsr-tab.active{color:var(--mc);border-bottom-color:var(--mc)}
.bsr-tab-content{flex:1;overflow-y:auto;padding:.75rem}
.bpf-group{margin-bottom:.75rem}
.bpf-group label{display:flex;align-items:center;gap:.35rem;font-size:.7rem;font-weight:700;color:#374151;margin-bottom:.3rem;text-transform:uppercase;letter-spacing:.04em}
.bpf-input{width:100%;padding:.45rem .6rem;border:1.5px solid #e2e8f0;border-radius:7px;font-size:.82rem;color:#0f172a;background:#fff;transition:all .2s;box-sizing:border-box;font-family:inherit;outline:none}
.bpf-input:focus{border-color:var(--mc);box-shadow:0 0 0 2px rgba(21,101,192,.08)}
.bpf-colors{display:grid;grid-template-columns:repeat(5,1fr);gap:5px;margin-bottom:.5rem}
.bpf-swatch{width:100%;padding-bottom:100%;border-radius:6px;border:none;cursor:pointer;outline-offset:2px;transition:transform .12s;position:relative}
.bpf-swatch:hover{transform:scale(1.1)}
.bpf-color-row{display:flex;align-items:center;gap:.5rem}
.bpf-color-picker{width:34px;height:30px;border:1.5px solid #e2e8f0;border-radius:6px;cursor:pointer;padding:2px}
.bpf-color-hex{flex:1;font-family:monospace;font-size:.78rem}
.bpf-acts-count{margin-left:auto;background:var(--mc);color:#fff;font-size:.65rem;font-weight:700;padding:.1rem .4rem;border-radius:50px}
.bpf-act-search{position:relative;display:flex;align-items:center;margin-bottom:.5rem}
.bpf-search-ico{position:absolute;left:.55rem;color:#94a3b8;font-size:.82rem;pointer-events:none;z-index:1}
.bpf-act-search-inp{padding-left:1.9rem!important;padding-right:1.8rem!important}
.bpf-act-clear{position:absolute;right:.4rem;background:none;border:none;color:#94a3b8;cursor:pointer;font-size:.76rem;padding:.1rem}
.bpf-act-clear:hover{color:var(--rd)}
.bpf-act-list{border:1.5px solid #e2e8f0;border-radius:8px;overflow-y:auto;max-height:220px;background:#fff}
.bpf-act-item{display:flex;align-items:center;gap:.5rem;padding:.5rem .6rem;cursor:pointer;border-bottom:1px solid #f8fafc;transition:all .12s}
.bpf-act-item:last-child{border-bottom:none}
.bpf-act-item:hover{background:#f5f3ff}
.bpf-act-item--active{background:#eff6ff}
.bpf-act-ico{width:24px;height:24px;background:#eff6ff;border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--mc);flex-shrink:0;font-size:.82rem}
.bpf-act-info{flex:1;min-width:0}
.bpf-act-info strong{display:block;font-size:.76rem;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bpf-act-info code{font-size:.66rem;color:#94a3b8}
.bpf-act-check{color:#22c55e;flex-shrink:0;font-size:.85rem}
.bpf-act-add{color:#cbd5e1;flex-shrink:0;font-size:.85rem;opacity:0;transition:opacity .12s}
.bpf-act-item:hover .bpf-act-add{opacity:1;color:var(--mc)}
.bpf-act-empty{display:flex;flex-direction:column;align-items:center;gap:.35rem;padding:1.25rem;font-size:.78rem;color:#94a3b8;text-align:center}
.bpf-act-empty i{font-size:1.4rem;opacity:.3}
.bsr-act-linked{background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:8px;padding:.6rem;margin-bottom:.6rem}
.bsal-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;font-size:.72rem;font-weight:600;color:#15803d}
.bsal-unlink{background:none;border:1px solid #86efac;color:#16a34a;padding:.15rem .45rem;border-radius:5px;cursor:pointer;font-size:.72rem;display:flex;align-items:center;gap:.25rem;transition:all .15s}
.bsal-unlink:hover{background:#dcfce7}
.bsal-card{display:flex;align-items:center;gap:.5rem}
.bsal-card-icon{width:28px;height:28px;background:#dcfce7;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#16a34a;flex-shrink:0}
.bsal-card strong{display:block;font-size:.78rem;color:#0f172a}
.bsal-card code{font-size:.68rem;color:#64748b}
.bsr-footer{padding:.6rem .75rem;border-top:1px solid #f1f5f9;flex-shrink:0}
.bsr-desel{display:flex;align-items:center;justify-content:center;gap:.35rem;width:100%;background:none;border:1.5px solid #e2e8f0;color:#64748b;padding:.35rem;border-radius:7px;font-size:.76rem;cursor:pointer;transition:all .15s}
.bsr-desel:hover{background:#fef2f2;border-color:#fca5a5;color:var(--rd)}

/* Context menu */
.ctx-menu{position:absolute;background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 30px rgba(0,0,0,.14);min-width:180px;z-index:1000;padding:.4rem;overflow:hidden}
.ctx-head{padding:.5rem .65rem;border-bottom:1px solid #f1f5f9;margin-bottom:.35rem}
.ctx-head strong{display:block;font-size:.8rem;color:#0f172a}
.ctx-head code{font-size:.68rem;color:#94a3b8}
.ctx-colors{display:flex;flex-wrap:wrap;gap:4px;padding:.35rem .4rem .5rem;border-bottom:1px solid #f1f5f9;margin-bottom:.35rem}
.ctx-color{width:20px;height:20px;border-radius:50%;border:none;cursor:pointer;outline-offset:2px;transition:transform .12s}
.ctx-color:hover{transform:scale(1.2)}
.ctx-items{display:flex;flex-direction:column;gap:2px}
.ctx-item{display:flex;align-items:center;gap:.5rem;background:none;border:none;padding:.4rem .65rem;border-radius:6px;font-size:.8rem;color:#374151;cursor:pointer;text-align:left;width:100%;transition:background .12s}
.ctx-item:hover{background:#f8fafc}
.ctx-danger{color:var(--rd)}
.ctx-danger:hover{background:#fef2f2}
.ctx-close{position:absolute;top:.35rem;right:.35rem;background:none;border:none;color:#94a3b8;cursor:pointer;font-size:.8rem;padding:.15rem;border-radius:4px}
.ctx-overlay{position:fixed;inset:0;z-index:999}

/* AP list dd */
.ap-list-dd{position:relative}
.ap-list-menu{position:absolute;top:calc(100% + 4px);right:0;background:#fff;border:1px solid #e2e8f0;border-radius:9px;box-shadow:0 4px 16px rgba(0,0,0,.1);z-index:50;min-width:200px;overflow:hidden}
.ap-list-item{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:7px 12px;cursor:pointer;font-size:.74rem;border-bottom:1px solid #f8fafc;transition:background .12s}
.ap-list-item:hover{background:#f0f6ff}
.ap-list-item code{color:var(--mc);font-weight:600}

/* Tableau */
.cart-scroll{background:#fff;border:1px solid #e2e8f0;border-radius:9px;overflow:auto;max-height:560px}
.cart-tbl{width:100%;border-collapse:collapse;font-size:.72rem;min-width:1100px}
.cart-tbl thead th{background:#1565C0;color:rgba(255,255,255,.9);font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:8px 9px;border:none;white-space:nowrap;position:sticky;top:0;z-index:4;min-width:100px}
.th-s0{position:sticky!important;left:0;z-index:5!important;background:#1e3a5f!important;min-width:100px!important}
.th-s1{position:sticky!important;left:100px;z-index:5!important;background:#1e3a5f!important;min-width:160px!important}
.th-ff{min-width:90px!important}
.cart-tbl tbody td{padding:5px 8px;border:1px solid #e9ecef;vertical-align:top}
.row-proc{background:#fff8e1;transition:background .12s}
.row-proc:hover{background:#fff3cd}
.row-proc-active td{box-shadow:inset 0 0 0 2px var(--mc) !important}
.row-proc-ul{background:#ede9fe!important}.row-proc-ul:hover{background:#ddd6fe!important}
.td-s0{position:sticky;left:0;z-index:2}.td-s1{position:sticky;left:100px;z-index:2}
.td-proc-code{background:inherit;font-weight:700;font-size:.7rem;color:#1a1a2e;white-space:nowrap;display:flex;align-items:center;gap:4px;min-width:100px}
.td-proc-name{background:inherit;font-weight:600;font-size:.7rem;color:#1a1a2e;min-width:160px}
.ti-xs{font-size:.72rem}
.row-act{background:#fff}.row-act:hover td{background:#f0f6ff}
.td-act-code{background:inherit;font-family:monospace;font-size:.66rem;font-weight:700;color:var(--mc);white-space:nowrap;display:flex;align-items:center;gap:4px;min-width:100px}
.td-act-name{background:inherit;font-size:.7rem;color:#374151;padding-left:10px;min-width:160px}
.act-ico{color:#94a3b8}
.td-act-risks{padding:5px 10px}
.act-risks-list{display:flex;flex-wrap:wrap;gap:4px}
.no-risk{font-size:.66rem;color:#94a3b8;font-style:italic}
.td-act-obs-pad{min-width:90px}
.risk-tag{display:inline-flex;align-items:center;gap:3px;font-size:.62rem;font-weight:600;padding:2px 7px;border-radius:8px;border:1px solid}
.risk-identified{background:#fff3cd;color:#92400e;border-color:#fde68a}
.risk-assessed{background:#dbeafe;color:#1e40af;border-color:#bfdbfe}
.risk-mitigated{background:#d1fae5;color:#065f46;border-color:#6ee7b7}
.risk-monitored{background:#e0e7ff;color:#3730a3;border-color:#c7d2fe}
.risk-closed{background:#f1f5f9;color:#64748b;border-color:#e2e8f0}

/* Inputs */
.c-ta{width:100%;border:1px solid #e2e8f0;border-radius:4px;padding:3px 6px;font-size:.7rem;color:#1a1a2e;font-family:inherit;resize:vertical;outline:none;min-height:36px;background:rgba(255,255,255,.65)}
.c-ta:focus{border-color:var(--mc);background:#fff}
.ro-txt{font-size:.7rem;color:#374151;white-space:pre-wrap}
.td-sel{min-width:110px}
.c-sel{width:100%;border:1px solid #e2e8f0;border-radius:4px;padding:4px 6px;font-size:.7rem;color:#1a1a2e;font-family:inherit;background:rgba(255,255,255,.65);outline:none;cursor:pointer}
.c-sel:focus{border-color:var(--mc)}
.c-sel-sm{width:100%;border:1px solid #e2e8f0;border-radius:4px;padding:3px 5px;font-size:.66rem;color:#374151;font-family:inherit;background:rgba(255,255,255,.65);outline:none;cursor:pointer;margin-top:4px}
.ro-fn{display:flex;flex-wrap:wrap;gap:3px}
.fn-tag{display:inline-flex;align-items:center;gap:3px;font-size:.62rem;font-weight:700;padding:2px 7px;border-radius:7px}
.fn-own{background:#dbeafe;color:#1e40af;border:1px solid #bfdbfe}
.fn-int{background:#e0e7ff;color:#3730a3;border:1px solid #c7d2fe;cursor:pointer;transition:all .12s}
.fn-int:hover{background:#fecdd3;color:var(--rd);border-color:#fca5a5}
.fn-x{font-size:.56rem}
.td-interv{min-width:130px}
.interv-wrap{display:flex;flex-direction:column;gap:3px}
.interv-tags{display:flex;flex-wrap:wrap;gap:3px;min-height:18px}
.no-interv{font-size:.65rem;color:#94a3b8;font-style:italic}
.interv-ro{display:flex;flex-wrap:wrap;gap:3px}
.td-io{min-width:130px}
.io-preview{display:flex;align-items:flex-start;gap:4px}
.io-chips{display:flex;flex-wrap:wrap;gap:3px;flex:1}
.io-chip{display:inline-flex;align-items:center;font-size:.62rem;padding:2px 6px;border-radius:5px;white-space:nowrap}
.io-in{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0}
.io-out{background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe}
.io-edit-btn{flex-shrink:0;border:none;background:transparent;color:#94a3b8;cursor:pointer;font-size:.76rem;padding:1px 3px;border-radius:4px;transition:color .12s}
.io-edit-btn:hover{color:var(--mc)}
.io-in-preview{border:1px solid #bbf7d0;border-radius:5px;padding:3px 5px;background:#f0fdf4}
.io-out-preview{border:1px solid #bfdbfe;border-radius:5px;padding:3px 5px;background:#eff6ff}
.td-ff{min-width:90px;text-align:center}
.btn-ff{display:flex;flex-direction:column;align-items:center;gap:3px;padding:5px 7px;border-radius:7px;border:1px solid #e2e8f0;background:rgba(255,255,255,.7);cursor:pointer;font-family:inherit;transition:all .14s;width:100%}
.btn-ff:hover:not(:disabled){background:#fff;border-color:var(--mc);box-shadow:0 1px 4px rgba(21,101,192,.12)}
.btn-ff:disabled{opacity:.5;cursor:not-allowed}
.ff-scores{display:flex;gap:8px}
.ff-f{display:inline-flex;align-items:center;gap:3px;font-size:.65rem;font-weight:700;color:var(--gr)}
.ff-w{display:inline-flex;align-items:center;gap:3px;font-size:.65rem;font-weight:700;color:var(--rd)}
.ff-cta{font-size:.59rem;color:#94a3b8}

/* Synthèse */
.synth-row{display:flex;gap:10px;background:#fff;border:1px solid #e2e8f0;border-radius:9px;padding:12px;flex-wrap:wrap}
.synth-f{flex:1;min-width:260px;display:flex;flex-direction:column;gap:5px}
.synth-f label{font-size:.63rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--mc);display:flex;align-items:center;gap:5px}
.synth-ta{width:100%;border:1px solid #e2e8f0;border-radius:6px;padding:7px 10px;font-size:.76rem;color:#1a1a2e;font-family:inherit;resize:vertical;outline:none}
.synth-ta:focus{border-color:var(--mc)}
.synth-ro{font-size:.76rem;color:#374151;white-space:pre-wrap;min-height:40px}
.author-fs{display:flex;flex-direction:column;gap:8px;min-width:180px}
.af label{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#64748b;display:block;margin-bottom:3px}
.inp{width:100%;border:1px solid #d1d5db;border-radius:5px;padding:5px 8px;font-size:.76rem;color:#1a1a2e;font-family:inherit;outline:none}
.inp:focus{border-color:var(--mc)}.inp:disabled{background:#f8fafc;color:#94a3b8}

/* Resize */
.resize-handle{width:8px;cursor:col-resize;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background .15s;position:relative;z-index:10;margin:0 2px}
.resize-handle:hover{background:color-mix(in srgb,var(--mc) 12%,transparent)}
.resize-grip{color:#cbd5e1;font-size:1rem}

/* Buttons */
.btn{display:inline-flex;align-items:center;gap:4px;padding:6px 12px;border-radius:6px;font-size:.74rem;font-weight:600;border:1px solid transparent;cursor:pointer;font-family:inherit;text-decoration:none;transition:all .12s}
.btn:disabled{opacity:.5;cursor:not-allowed}
.btn-sm{padding:4px 9px;font-size:.7rem}
.btn-ghost{background:transparent;color:#64748b;border-color:#d1d5db}.btn-ghost:hover{background:#f1f5f9}
.btn-save{background:var(--mc);color:#fff}.btn-save:hover:not(:disabled){filter:brightness(1.1)}
.btn-sub{background:#0f766e;color:#fff}.btn-sub:hover{background:#0d6460}
.btn-ok{background:#15803d;color:#fff}.btn-ok:hover{background:#166534}
.btn-rej{background:#dc2626;color:#fff}.btn-rej:hover{background:#b91c1c}

/* Footer */
.ap-footer{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;padding:10px 14px;background:#fff;border:1px solid #e2e8f0;border-radius:9px}
.ap-footer>div{display:flex;align-items:center;gap:7px}
.footer-c{flex:1;display:flex;justify-content:center}
.saved-code{font-size:.72rem;color:#15803d;display:flex;align-items:center;gap:4px;font-weight:600}
.spin-dot{width:11px;height:11px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite}

/* Modal FF */
.modal-ov{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:400;display:flex;align-items:center;justify-content:center;padding:20px}
.modal-box{background:#fff;border-radius:14px;box-shadow:0 8px 40px rgba(0,0,0,.22);width:100%;max-width:800px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden}
.modal-hd{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid #e2e8f0;flex-shrink:0;gap:8px;flex-wrap:wrap}
.modal-hd-l{display:flex;align-items:center;gap:8px;font-size:.82rem;font-weight:700;color:#1a1a2e;flex-wrap:wrap}
.modal-hd-l i{color:var(--mc);font-size:1.05rem}
.modal-code{font-family:monospace;font-size:.68rem;font-weight:700;padding:2px 7px;border-radius:5px;background:color-mix(in srgb,var(--mc) 8%,white);border:1px solid color-mix(in srgb,var(--mc) 25%,transparent);color:var(--mc)}
.modal-pname{font-size:.76rem;color:#64748b;font-weight:400}
.modal-cls{width:28px;height:28px;border:none;background:#f1f5f9;border-radius:7px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#64748b;transition:all .13s;flex-shrink:0}
.modal-cls:hover{background:#fee2e2;color:#dc2626}
.modal-body{flex:1;overflow-y:auto;padding:18px}
.modal-ft{display:flex;justify-content:flex-end;padding:12px 18px;border-top:1px solid #e2e8f0;flex-shrink:0}
.ff-cols{display:flex;gap:16px}
.ff-col{flex:1;min-width:0;display:flex;flex-direction:column;gap:8px}
.ff-col-hd{display:flex;align-items:center;gap:6px;font-size:.71rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:6px 10px;border-radius:7px}
.ff-forces .ff-col-hd{background:#f0fdf4;color:var(--gr)}.ff-forces .ff-col-hd i{color:var(--gr)}
.ff-faib   .ff-col-hd{background:#fff1f2;color:var(--rd)}.ff-faib   .ff-col-hd i{color:var(--rd)}
.ff-hint{font-size:.6rem;font-weight:400;text-transform:none;color:#94a3b8;margin-left:auto}
.ff-items{display:flex;flex-direction:column;gap:4px;min-height:60px}
.ff-item{display:flex;align-items:flex-start;gap:6px;padding:5px 8px;border-radius:6px;font-size:.72rem;color:#374151;border:1px solid transparent}
.ff-item-f{background:#f0fdf4;border-color:#bbf7d0}
.ff-item-w{background:#fff1f2;border-color:#fecdd3}
.ff-dot{font-size:.55rem;margin-top:3px;flex-shrink:0}
.ff-item-f .ff-dot{color:var(--gr)}.ff-item-w .ff-dot{color:var(--rd)}
.ff-text{flex:1}
.ff-del{border:none;background:transparent;cursor:pointer;color:#94a3b8;padding:0;font-size:.72rem;flex-shrink:0;line-height:1}
.ff-del:hover{color:var(--rd)}
.ff-empty{font-size:.7rem;color:#94a3b8;font-style:italic;padding:8px}
.ff-add{display:flex;gap:6px;align-items:center}
.ff-inp{flex:1;border:1px solid #e2e8f0;border-radius:5px;padding:5px 9px;font-size:.72rem;color:#1a1a2e;font-family:inherit;outline:none}
.ff-inp:focus{border-color:var(--mc)}
.ff-add-btn{width:30px;height:30px;padding:0;border-radius:6px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.85rem;transition:all .12s;flex-shrink:0}
.ff-add-f{background:var(--gr);color:#fff}.ff-add-f:hover:not(:disabled){background:#166534}
.ff-add-w{background:var(--rd);color:#fff}.ff-add-w:hover:not(:disabled){background:#b91c1c}
.ff-add-btn:disabled{opacity:.4}
.ff-bulk-lbl{font-size:.62rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em;margin-top:2px}
.ff-ta{width:100%;border:1px solid #e2e8f0;border-radius:6px;padding:7px 10px;font-size:.72rem;color:#1a1a2e;font-family:inherit;resize:vertical;outline:none}
.ff-ta-f{border-color:#bbf7d0}.ff-ta-f:focus{border-color:var(--gr)}
.ff-ta-w{border-color:#fecdd3}.ff-ta-w:focus{border-color:var(--rd)}
.mfade-enter-active,.mfade-leave-active{transition:all .2s ease}
.mfade-enter-from,.mfade-leave-to{opacity:0}
.mfade-enter-from .modal-box,.mfade-leave-to .modal-box{transform:scale(.96) translateY(8px)}

/* Toast */
.toast{position:fixed;bottom:22px;right:22px;z-index:600;display:flex;align-items:center;gap:9px;padding:10px 16px;border-radius:9px;font-size:.78rem;font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.18)}
.toast-success{background:#15803d;color:#fff}
.toast-error{background:#dc2626;color:#fff}
.toast-up-enter-active,.toast-up-leave-active{transition:all .22s ease}
.toast-up-enter-from,.toast-up-leave-to{opacity:0;transform:translateY(8px)}

/* Transitions */
.fade-enter-active,.fade-leave-active{transition:opacity .25s}
.fade-enter-from,.fade-leave-to{opacity:0}
.scale-enter-active{transition:all .15s ease-out}
.scale-leave-active{transition:all .1s ease-in}
.scale-enter-from,.scale-leave-to{opacity:0;transform:scale(.95)}

.animate-spin{animation:spin 1s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

/* Scrollbar */
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:#e2e8f0;border-radius:3px}
::-webkit-scrollbar-thumb:hover{background:#cbd5e1}
</style>