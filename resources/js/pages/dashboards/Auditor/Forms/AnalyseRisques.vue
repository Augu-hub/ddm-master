<template>
  <VerticalLayoutAudit>
    <div class="ar-shell">

      <!-- ══ HEADER ══ -->
      <header class="ar-header">
        <div class="ar-hrow">
          <a :href="props.backUrl" class="ar-back"><i class="ti ti-arrow-left"></i></a>

          <div class="ar-hinfo">
            <div class="ar-chips">
              <code class="ar-code">{{ mission?.code_mission ?? '—' }}</code>
              <span class="ar-chip" :class="`chip-${ar.validation_status || 'draft'}`">
                <i :class="vstIcon(ar.validation_status || 'draft')"></i>
                {{ vstLbl(ar.validation_status || 'draft') }}
              </span>
              <span class="ar-chip chip-type">Analyse des Risques</span>
              <span class="ar-chip chip-year">
                <i class="ti ti-calendar"></i> Univers {{ props.activeYear }}
              </span>
              <span v-if="props.auditorRole" class="ar-chip" :class="`chip-role-${props.auditorRole}`">
                {{ props.auditorRole }}
              </span>
            </div>
            <h1 class="ar-title">Analyse des Risques — AC P2</h1>
            <div class="ar-meta">
              <span v-if="assignment?.phase_label"><i class="ti ti-git-branch"></i>{{ assignment.phase_label }}</span>
              <span v-if="mission?.entity_name"><i class="ti ti-building"></i>{{ mission.entity_name }}</span>
              <span>
                <i class="ti ti-shield-check"></i>
                {{ filteredRisks.length }} risques · {{ selectedCount }} sélectionnés
              </span>
              <span v-if="!allRisks.length" class="ar-warn-inline">
                <i class="ti ti-alert-triangle"></i>
                Aucun risque dans l'univers {{ props.activeYear }} — créez-en via l'Univers d'Audit
              </span>
            </div>
          </div>

          <!-- Actions header -->
          <div class="ar-view-actions">
            <div class="ar-search-wrap">
              <i class="ti ti-search ar-search-ico"></i>
              <input v-model="searchQuery" class="ar-search" placeholder="Rechercher…"/>
              <button v-if="searchQuery" class="ar-search-clr" @click="searchQuery=''">
                <i class="ti ti-x"></i>
              </button>
            </div>

            <button class="ar-filter-btn" :class="{active: showFilters}" @click="showFilters=!showFilters">
              <i class="ti ti-filter"></i>
              <span v-if="activeFilterCount" class="filter-badge">{{ activeFilterCount }}</span>
            </button>

            <div class="ar-list-dd" v-if="(arList as any[]).length">
              <button class="btn btn-sm btn-ghost" @click="showArList=!showArList">
                <i class="ti ti-history"></i> {{ (arList as any[]).length }} AR
              </button>
              <div v-if="showArList" class="ar-list-menu">
                <div v-for="item in (arList as any[])" :key="item.id" class="ar-list-item"
                     @click="loadAr(item); showArList=false">
                  <code>{{ item.code }}</code>
                  <span class="ar-chip-sm" :class="`chip-${item.validation_status||'draft'}`">
                    {{ vstLbl(item.validation_status||'draft') }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Bannière statut -->
        <div v-if="ar.validation_status==='validated'" class="ar-banner banner-lock">
          <i class="ti ti-lock"></i> Analyse <strong>validée</strong> — lecture seule
        </div>
        <div v-else-if="ar.validation_status==='in_review'" class="ar-banner banner-review">
          <i class="ti ti-clock"></i> En attente de validation
          <span v-if="canManage"> · Vous pouvez valider ou rejeter.</span>
        </div>

        <!-- Filtres -->
        <transition name="slide-down">
          <div v-if="showFilters" class="ar-filters">
            <div class="filter-group">
              <label>Processus</label>
              <select v-model="filterProcess" class="f-sel">
                <option value="">Tous</option>
                <option v-for="p in uniqueProcesses" :key="p.code" :value="p.code">
                  {{ p.code }} — {{ p.name }}
                </option>
              </select>
            </div>
            <div class="filter-group">
              <label>Nature</label>
              <select v-model="filterNature" class="f-sel">
                <option value="">Toutes</option>
                <option v-for="n in natures" :key="n">{{ n }}</option>
              </select>
            </div>
            <div class="filter-group">
              <label>Qualif. contrôle</label>
              <select v-model="filterQualif" class="f-sel">
                <option value="">Toutes</option>
                <option v-for="q in qualifControlesList" :key="q">{{ q }}</option>
              </select>
            </div>
            <div class="filter-group">
              <label>Évalué</label>
              <select v-model="filterEvalue" class="f-sel">
                <option value="">Tous</option>
                <option value="yes">Évalués</option>
                <option value="no">Non évalués</option>
              </select>
            </div>
            <div class="filter-group">
              <label>Choix</label>
              <select v-model="filterChoix" class="f-sel">
                <option value="">Tous</option>
                <option value="selected">Sélectionnés</option>
                <option value="unselected">Non sélectionnés</option>
              </select>
            </div>
            <button class="btn-reset-filters" @click="resetFilters">
              <i class="ti ti-rotate-2"></i> Réinitialiser
            </button>
          </div>
        </transition>
      </header>

      <!-- ══ BODY ══ -->
      <div class="ar-body">

        <!-- Aucun risque -->
        <div v-if="!allRisks.length" class="ar-empty">
          <i class="ti ti-shield-off"></i>
          <strong>Aucun risque pour l'univers {{ props.activeYear }}</strong>
          <p>
            Créez des risques dans l'<strong>Univers d'Audit</strong> pour l'entité
            <strong>{{ mission?.entity_name ?? '—' }}</strong> / année <strong>{{ props.activeYear }}</strong>,
            puis revenez ici.
          </p>
        </div>

        <template v-else>

          <!-- ── Info univers ── -->
          <div class="universe-info-bar">
            <i class="ti ti-universe"></i>
            <span>
              <strong>{{ allRisks.length }}</strong> risque(s) chargés depuis l'univers
              <strong>{{ props.activeYear }}</strong>
              · <strong>{{ evaluatedCount }}</strong> évalués dans l'univers
            </span>
            <span v-if="evaluatedCount > 0" class="univ-ok">
              <i class="ti ti-circle-check"></i> Impact/Fréquence pré-remplis depuis l'univers
            </span>
          </div>

          <!-- ── Tableau ── -->
          <div class="ar-table-wrap">
            <div class="tbl-scroll">
              <table class="ar-tbl">
                <thead>
                  <tr>
                    <th class="th-stick th-proc" @click="sortBy('process_code')">
                      Processus <i :class="sortIcon('process_code')"></i>
                    </th>
                    <th class="th-stick2 th-sproc" @click="sortBy('activity_name')">
                      S.Processus <i :class="sortIcon('activity_name')"></i>
                    </th>
                    <th class="th-code" @click="sortBy('code')">
                      Code risque <i :class="sortIcon('code')"></i>
                    </th>
                    <th class="th-label" @click="sortBy('label')">
                      Libellé du risque <i :class="sortIcon('label')"></i>
                    </th>
                    <th class="th-ctrl">Procédures Contrôle</th>
                    <th class="th-num">
                      N. Imp<br><small>résiduel</small>
                    </th>
                    <th class="th-num">
                      N. Freq<br><small>résiduel</small>
                    </th>
                    <th class="th-num">
                      Glob<br><small>Résid</small>
                    </th>
                    <th class="th-nature">Nature</th>
                    <th class="th-qualif">Qualif_controle</th>
                    <th class="th-choix">Choix</th>
                    <th class="th-assert">Assertions / Objectifs Contrôle</th>
                    <th class="th-forces">Forces</th>
                    <th class="th-filt"><i class="ti ti-filter"></i></th>
                    <th class="th-faib">Faiblesses</th>
                    <th class="th-filt"><i class="ti ti-filter"></i></th>
                    <th class="th-obj">Objectif de contrôle</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-for="group in groupedRisks" :key="group.processCode">

                    <!-- Ligne groupe processus -->
                    <tr class="row-group" @click="toggleGroup(group.processCode)">
                      <td colspan="17" class="td-group">
                        <div class="group-hd">
                          <i :class="expandedGroups.has(group.processCode) ? 'ti ti-chevron-down' : 'ti ti-chevron-right'"></i>
                          <code class="group-code">{{ group.processCode }}</code>
                          <span class="group-name">{{ group.processName }}</span>
                          <span class="group-count">{{ group.risks.length }} risque(s)</span>
                          <button v-if="!isLocked" class="btn-add-act"
                                  @click.stop="openAddActivity(group)"
                                  title="Ajouter une activité">
                            <i class="ti ti-plus"></i> Activité
                          </button>
                        </div>
                      </td>
                    </tr>

                    <!-- Risques du groupe -->
                    <template v-if="expandedGroups.has(group.processCode)">
                      <tr v-for="risk in group.risks" :key="risk.id"
                          class="row-risk"
                          :class="{
                            'row-risk-selected': riskForms[risk.id]?.choix,
                            'row-risk-new':      risk._isNew,
                            'row-risk-evaluated': risk.is_evaluated && !risk._isNew,
                          }">

                        <td class="td-stick td-proc">{{ risk.process_code }}</td>
                        <td class="td-stick2 td-sproc">{{ risk.activity_name || risk.activity_code }}</td>

                        <td class="td-code">
                          <code class="risk-code">{{ risk.code }}</code>
                          <span v-if="risk._isNew" class="badge-new">NOUVEAU</span>
                          <!-- Badge "évalué dans univers" -->
                          <span v-else-if="risk.is_evaluated" class="badge-univ" title="Évalué dans l'univers d'audit">
                            <i class="ti ti-universe"></i>
                          </span>
                        </td>

                        <td class="td-label">
                          <div class="risk-label-wrap">
                            <span class="risk-type-dot"
                                  :style="{background: colorOf(risk.risk_type_color)}"
                                  :title="risk.risk_type_label"></span>
                            <div class="risk-label-col">
                              <span class="risk-label-txt">{{ risk.label }}</span>
                              <!-- Qualif univers si disponible -->
                              <span v-if="risk.qualification_net" class="risk-qualif-univ"
                                    :class="`qualif-${slugQualif(risk.qualification_net)}`">
                                {{ risk.qualification_net }}
                              </span>
                            </div>
                          </div>
                        </td>

                        <!-- Procédure contrôle — pré-remplie depuis univers -->
                        <td class="td-ctrl">
                          <textarea v-if="!isLocked" class="c-ta"
                                    v-model="riskForms[risk.id].control_procedure"
                                    rows="2" placeholder="Procédure…"/>
                          <div v-else class="ro-txt">{{ riskForms[risk.id].control_procedure || '—' }}</div>
                        </td>

                        <!-- Impact résiduel — pré-rempli depuis univers -->
                        <td class="td-num">
                          <div class="level-wrap">
                            <div v-if="!isLocked" class="level-cell"
                                 :style="levelStyle(riskForms[risk.id].impact_net)">
                              <select class="lv-sel" v-model.number="riskForms[risk.id].impact_net"
                                      @change="recomputeGlob(risk.id)">
                                <option :value="null">—</option>
                                <option v-for="lv in (props.impactLevels as any[])"
                                        :key="(lv as any).id" :value="Number((lv as any).level)">
                                  {{ (lv as any).level }}
                                </option>
                              </select>
                            </div>
                            <div v-else class="level-ro" :style="levelStyle(riskForms[risk.id].impact_net)">
                              {{ riskForms[risk.id].impact_net ?? '—' }}
                            </div>
                            <!-- Indicateur source univers -->
                            <span v-if="risk.is_evaluated && riskForms[risk.id].impact_net"
                                  class="src-univ" title="Depuis l'univers">U</span>
                          </div>
                        </td>

                        <!-- Fréquence résiduelle — pré-remplie depuis univers -->
                        <td class="td-num">
                          <div class="level-wrap">
                            <div v-if="!isLocked" class="level-cell"
                                 :style="levelStyle(riskForms[risk.id].frequency_net)">
                              <select class="lv-sel" v-model.number="riskForms[risk.id].frequency_net"
                                      @change="recomputeGlob(risk.id)">
                                <option :value="null">—</option>
                                <option v-for="lv in (props.frequencyLevels as any[])"
                                        :key="(lv as any).id" :value="Number((lv as any).level)">
                                  {{ (lv as any).level }}
                                </option>
                              </select>
                            </div>
                            <div v-else class="level-ro" :style="levelStyle(riskForms[risk.id].frequency_net)">
                              {{ riskForms[risk.id].frequency_net ?? '—' }}
                            </div>
                            <span v-if="risk.is_evaluated && riskForms[risk.id].frequency_net"
                                  class="src-univ" title="Depuis l'univers">U</span>
                          </div>
                        </td>

                        <!-- Global résiduel -->
                        <td class="td-num">
                          <div class="level-ro glob-cell" :style="globStyle(riskForms[risk.id].glob_resid)">
                            {{ riskForms[risk.id].glob_resid ?? '—' }}
                          </div>
                        </td>

                        <!-- Nature -->
                        <td class="td-nature">
                          <select v-if="!isLocked" class="c-sel-sm"
                                  v-model="riskForms[risk.id].nature">
                            <option value="">—</option>
                            <option v-for="n in natures" :key="n" :value="n">{{ n }}</option>
                          </select>
                          <span v-else class="nature-badge" :class="`nature-${riskForms[risk.id].nature}`">
                            {{ riskForms[risk.id].nature || '—' }}
                          </span>
                        </td>

                        <!-- Qualif contrôle -->
                        <td class="td-qualif">
                          <select v-if="!isLocked" class="c-sel-sm"
                                  v-model="riskForms[risk.id].qualif_controle">
                            <option value="">—</option>
                            <option v-for="q in qualifControlesList" :key="q" :value="q">{{ q }}</option>
                          </select>
                          <span v-else class="qc-badge">{{ riskForms[risk.id].qualif_controle || '—' }}</span>
                        </td>

                        <!-- Choix -->
                        <td class="td-choix">
                          <label class="choix-toggle">
                            <input type="checkbox" v-model="riskForms[risk.id].choix" :disabled="isLocked"/>
                            <span class="choix-box" :class="{checked: riskForms[risk.id].choix}">
                              <i v-if="riskForms[risk.id].choix" class="ti ti-check"></i>
                            </span>
                          </label>
                        </td>

                        <!-- Assertions -->
                        <td class="td-assert">
                          <textarea v-if="!isLocked" class="c-ta"
                                    v-model="riskForms[risk.id].assertions"
                                    rows="2" placeholder="Assertions…"/>
                          <div v-else class="ro-txt">{{ riskForms[risk.id].assertions || '—' }}</div>
                        </td>

                        <!-- Forces -->
                        <td class="td-forces">
                          <textarea v-if="!isLocked" class="c-ta c-ta-green"
                                    v-model="riskForms[risk.id].forces"
                                    rows="2" placeholder="Forces…"/>
                          <div v-else class="ro-txt ro-green">{{ riskForms[risk.id].forces || '—' }}</div>
                        </td>
                        <td class="td-filt">
                          <span v-if="riskForms[risk.id].forces" class="filt-dot filt-green"></span>
                        </td>

                        <!-- Faiblesses -->
                        <td class="td-faib">
                          <textarea v-if="!isLocked" class="c-ta c-ta-red"
                                    v-model="riskForms[risk.id].faiblesses"
                                    rows="2" placeholder="Faiblesses…"/>
                          <div v-else class="ro-txt ro-red">{{ riskForms[risk.id].faiblesses || '—' }}</div>
                        </td>
                        <td class="td-filt">
                          <span v-if="riskForms[risk.id].faiblesses" class="filt-dot filt-red"></span>
                        </td>

                        <!-- Objectif de contrôle -->
                        <td class="td-obj">
                          <textarea v-if="!isLocked" class="c-ta"
                                    v-model="riskForms[risk.id].objectif_controle"
                                    rows="2" placeholder="Objectif de contrôle…"/>
                          <div v-else class="ro-txt">{{ riskForms[risk.id].objectif_controle || '—' }}</div>
                        </td>
                      </tr>
                    </template>
                  </template>

                  <tr v-if="!groupedRisks.length && (searchQuery || activeFilterCount)">
                    <td colspan="17" class="td-no-result">
                      <i class="ti ti-search-off"></i> Aucun risque ne correspond aux filtres
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- ── Ajouter processus ── -->
          <div v-if="!isLocked && availableProcesses.length" class="add-proc-bar">
            <span class="add-proc-lbl">
              <i class="ti ti-plus-circle"></i> Ajouter un processus :
            </span>
            <div class="add-proc-pills">
              <button v-for="p in availableProcesses" :key="(p as any).id"
                      class="btn-add-proc" @click="openAddProcess(p)">
                <code>{{ (p as any).code }}</code>
                <span>{{ (p as any).name }}</span>
                <i class="ti ti-plus"></i>
              </button>
            </div>
          </div>

          <!-- ── Synthèse & signature ── -->
          <div class="synth-row">
            <div class="synth-f">
              <label><i class="ti ti-notes"></i> Synthèse</label>
              <textarea v-if="!isLocked" class="synth-ta" v-model="synthese"
                        rows="3" placeholder="Synthèse générale…"/>
              <div v-else class="ro-txt synth-ro">{{ synthese || '—' }}</div>
            </div>
            <div class="author-fs">
              <div class="af">
                <label>Fait par</label>
                <input class="inp" v-model="form.fait_par" :disabled="isLocked"/>
              </div>
              <div class="af">
                <label>Revu par</label>
                <input class="inp" v-model="form.revue_par" :disabled="isLocked"/>
              </div>
            </div>
          </div>
        </template>
      </div>

      <!-- ══ FOOTER ══ -->
      <footer class="ar-footer">
        <div>
          <button v-if="!isLocked" type="button" class="btn btn-ghost" @click="annuler">
            <i class="ti ti-x"></i> Annuler
          </button>
          <button v-if="!isLocked" type="button" class="btn btn-save"
                  :disabled="processing" @click="submit">
            <span v-if="processing" class="spin-dot"></span>
            <i v-else class="ti ti-device-floppy"></i>
            {{ form.id ? 'Mettre à jour' : 'Enregistrer' }}
          </button>
        </div>
        <div class="footer-c">
          <span v-if="form.id" class="saved-code">
            <i class="ti ti-check"></i> {{ form.code }}
          </span>
        </div>
        <div>
          <button v-if="ar.id && ar.validation_status==='draft'" type="button"
                  class="btn btn-sub" :disabled="processing" @click="soumettre">
            <i class="ti ti-send"></i> Soumettre
          </button>
          <template v-if="canManage && ar.validation_status==='in_review'">
            <button type="button" class="btn btn-ok" :disabled="processing"
                    @click="valider('validated')">
              <i class="ti ti-circle-check"></i> Valider
            </button>
            <button type="button" class="btn btn-rej" :disabled="processing"
                    @click="promptReject">
              <i class="ti ti-circle-x"></i> Rejeter
            </button>
          </template>
        </div>
      </footer>

    </div>

    <!-- ══ MODAL Ajouter activité ══ -->
    <Teleport to="body">
      <transition name="mfade">
        <div v-if="actModal.open" class="modal-ov" @click.self="closeActModal">
          <div class="modal-box modal-sm">
            <div class="modal-hd">
              <div class="modal-hd-l">
                <i class="ti ti-activity"></i>
                <span>Ajouter une activité</span>
                <code v-if="actModal.processCode">{{ actModal.processCode }}</code>
              </div>
              <button class="modal-cls" @click="closeActModal"><i class="ti ti-x"></i></button>
            </div>
            <div class="modal-body">
              <p class="modal-hint">
                Activité à ajouter au processus <strong>{{ actModal.processName }}</strong>
              </p>
              <div class="modal-search-wrap">
                <i class="ti ti-search modal-search-ico"></i>
                <input v-model="actModal.search" class="modal-search" placeholder="Rechercher…"/>
              </div>
              <div class="modal-list">
                <div v-if="!filteredModalActivities.length" class="modal-empty">
                  <i class="ti ti-mood-empty"></i><span>Aucune activité disponible</span>
                </div>
                <div v-for="act in filteredModalActivities" :key="act.id"
                     class="modal-item"
                     :class="{selected: actModal.selectedId === act.id}"
                     @click="actModal.selectedId = act.id">
                  <div class="modal-item-ico"><i class="ti ti-activity"></i></div>
                  <div class="modal-item-info">
                    <strong>{{ act.name }}</strong>
                    <code>{{ act.code }}</code>
                  </div>
                  <i v-if="actModal.selectedId === act.id" class="ti ti-check modal-check"></i>
                </div>
              </div>
            </div>
            <div class="modal-ft">
              <button class="btn btn-ghost" @click="closeActModal">Annuler</button>
              <button class="btn btn-save" @click="confirmAddActivity"
                      :disabled="!actModal.selectedId">
                <i class="ti ti-plus"></i> Ajouter
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- ══ MODAL Ajouter processus ══ -->
    <Teleport to="body">
      <transition name="mfade">
        <div v-if="procModal.open" class="modal-ov" @click.self="closeProcModal">
          <div class="modal-box modal-sm">
            <div class="modal-hd">
              <div class="modal-hd-l">
                <i class="ti ti-box"></i>
                <span>Ajouter des risques</span>
                <code>{{ procModal.process?.code }}</code>
              </div>
              <button class="modal-cls" @click="closeProcModal"><i class="ti ti-x"></i></button>
            </div>
            <div class="modal-body">
              <p class="modal-hint">Risques à importer depuis ce processus pour l'analyse.</p>
              <div class="modal-list">
                <div v-if="!procModalRisks.length" class="modal-empty">
                  <i class="ti ti-shield-off"></i><span>Aucun risque dans ce processus</span>
                </div>
                <div v-for="r in procModalRisks" :key="r.id"
                     class="modal-item"
                     :class="{selected: procModal.selectedIds.includes(r.id)}"
                     @click="toggleProcRisk(r.id)">
                  <div class="modal-item-ico"><i class="ti ti-alert-triangle"></i></div>
                  <div class="modal-item-info">
                    <strong>{{ r.label }}</strong>
                    <code>{{ r.code }}</code>
                  </div>
                  <i v-if="procModal.selectedIds.includes(r.id)" class="ti ti-check modal-check"></i>
                </div>
              </div>
            </div>
            <div class="modal-ft">
              <button class="btn btn-ghost" @click="closeProcModal">Annuler</button>
              <button class="btn btn-save" @click="confirmAddProcess"
                      :disabled="!procModal.selectedIds.length">
                <i class="ti ti-plus"></i>
                Ajouter{{ procModal.selectedIds.length ? ` (${procModal.selectedIds.length})` : '' }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- Toast -->
    <Teleport to="body">
      <transition name="toast-up">
        <div v-if="toast.show" class="toast" :class="`toast-${toast.type}`">
          <i :class="toast.type==='success'?'ti ti-circle-check':'ti ti-alert-circle'"></i>
          {{ toast.msg }}
        </div>
      </transition>
    </Teleport>

  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  mission:             { type: Object, default: null },
  assignment:          { type: Object, default: null },
  form:                { type: Object, default: null },
  arList:              { type: Array,  default: () => [] },
  risksData:           { type: Array,  default: () => [] },
  allProcesses:        { type: Array,  default: () => [] },
  allActivities:       { type: Array,  default: () => [] },
  assignmentFunctions: { type: Array,  default: () => [] },
  impactLevels:        { type: Array,  default: () => [] },
  frequencyLevels:     { type: Array,  default: () => [] },
  riskTypes:           { type: Array,  default: () => [] },
  matrix:              { type: Array,  default: () => [] },
  auditorRole:         { type: String, default: null },
  missionId:           { type: Number, default: null },
  assignmentId:        { type: Number, default: null },
  currentAuditor:      { type: Object, default: null },
  riskCount:           { type: Number, default: 0 },
  missionYear:         { type: Number, default: null },
  activeYear:          { type: Number, default: null },
  backUrl:             { type: String, default: '' },
  formUrl:             { type: String, default: '' },
  editUrl:             { type: String, default: '' },
})

// ── State ──────────────────────────────────────────────────────────────────
const processing     = ref(false)
const showArList     = ref(false)
const showFilters    = ref(false)
const searchQuery    = ref('')
const filterProcess  = ref('')
const filterNature   = ref('')
const filterQualif   = ref('')
const filterChoix    = ref('')
const filterEvalue   = ref('')
const sortKey        = ref('process_code')
const sortDir        = ref<'asc'|'desc'>('asc')
const synthese       = ref(props.form?.synthese ?? '')
const expandedGroups = ref<Set<string>>(new Set())
const extraRisks     = ref<any[]>([])

const ar   = reactive<Record<string,any>>(props.form ? { ...props.form } : {})
const form = reactive({
  id:        props.form?.id        ?? null,
  code:      props.form?.code      ?? '',
  fait_par:  props.form?.fait_par  ?? auditorFullName(),
  revue_par: props.form?.revue_par ?? '',
})

// ── Constantes ─────────────────────────────────────────────────────────────
const natures             = ['RM', 'RF', 'RO', 'RC', 'RS']
const qualifControlesList = computed(() => {
  const q = new Set<string>(['QC 1','QC 2','QC 3','QC 4','QC 5','QC 6','QC 7','QC 8','QC 9','QC 10','QC 11'])
  allRisks.value.forEach(r => { if (riskForms[r.id]?.qualif_controle) q.add(riskForms[r.id].qualif_controle) })
  return Array.from(q).sort()
})

function auditorFullName() {
  const a = props.currentAuditor as any
  return a ? [a.last_name, a.first_name].filter(Boolean).join(' ') : ''
}

// ── Compteur risques évalués dans l'univers ────────────────────────────────
const evaluatedCount = computed(() =>
  (props.risksData as any[]).filter(r => r.is_evaluated).length
)

// ── Formulaires par risque ─────────────────────────────────────────────────
const riskForms = reactive<Record<number, any>>({})

/**
 * Construit le form d'un risque.
 * IMPORTANT : impact_net et frequency_net sont castés en Number pour que
 * v-model.number et les comparaisons dans computeGlob fonctionnent.
 * glob_resid : utilise d'abord la valeur calculée côté serveur,
 *              puis recalcule en fallback si absente.
 */
function makeRiskForm(r: any) {
  const impNet  = r.impact_net    != null ? Number(r.impact_net)    : null
  const freqNet = r.frequency_net != null ? Number(r.frequency_net) : null
  const glob    = r.glob_resid    != null ? Number(r.glob_resid)    : computeGlob(impNet, freqNet)

  return {
    control_procedure: r.control_procedure ?? '',
    impact_net:        impNet,
    frequency_net:     freqNet,
    glob_resid:        glob,
    nature:            r.nature            ?? '',
    qualif_controle:   r.qualif_controle   ?? '',
    assertions:        r.assertions        ?? '',
    forces:            r.forces            ?? '',
    faiblesses:        r.faiblesses        ?? '',
    objectif_controle: r.objectif_controle ?? '',
    choix:             r.choix             ?? false,
  }
}

function initRiskForms() {
  for (const r of (props.risksData as any[])) {
    riskForms[r.id] = makeRiskForm(r)
  }
}
initRiskForms()

// ── Tous les risques (univers + extras ajoutés manuellement) ───────────────
const allRisks = computed<any[]>(() => [
  ...(props.risksData as any[]),
  ...extraRisks.value,
])

// ── Calcul glob résiduel depuis la matrice ─────────────────────────────────
// On compare en Number() pour éviter le mismatch string/int
function computeGlob(imp: any, freq: any): number | null {
  if (imp == null || freq == null) return null
  const i = Number(imp)
  const f = Number(freq)
  if (!i || !f) return null
  const entry = (props.matrix as any[]).find(
    m => Number(m.frequency_level) === f && Number(m.impact_level) === i
  )
  return entry ? Number(entry.qualification) : i * f
}

function recomputeGlob(riskId: number) {
  const f = riskForms[riskId]
  f.glob_resid = computeGlob(f.impact_net, f.frequency_net)
}

// ── Couleurs niveaux ───────────────────────────────────────────────────────
const LEVEL_COLORS: Record<number, string> = {
  1: '#22c55e', 2: '#a3e635', 3: '#facc15', 4: '#f97316', 5: '#ef4444',
}
function levelStyle(v: any) {
  if (v == null) return {}
  const n = Number(v)
  if (!n) return {}
  return { background: LEVEL_COLORS[n] ?? '#94a3b8', color: '#fff' }
}
function globStyle(g: any) {
  if (g == null) return {}
  const n = Number(g)
  if (!n) return {}
  if (n >= 16) return { background: '#991b1b', color: '#fff' }
  if (n >= 9)  return { background: '#ef4444', color: '#fff' }
  if (n >= 4)  return { background: '#f97316', color: '#fff' }
  if (n >= 2)  return { background: '#facc15', color: '#1a1a2e' }
  return { background: '#22c55e', color: '#fff' }
}
function colorOf(c: string) {
  const map: Record<string, string> = {
    danger: '#dc3545', warning: '#ffc107', info: '#0dcaf0',
    success: '#28a745', secondary: '#6c757d', primary: '#0d6efd',
  }
  return map[c] ?? c ?? '#94a3b8'
}
function slugQualif(q: string): string {
  return (q ?? '').toLowerCase().replace(/[^a-z]/g, '')
}

// ── Filtres ────────────────────────────────────────────────────────────────
const activeFilterCount = computed(() =>
  [filterProcess.value, filterNature.value, filterQualif.value, filterChoix.value, filterEvalue.value].filter(Boolean).length
)
function resetFilters() {
  filterProcess.value = ''
  filterNature.value  = ''
  filterQualif.value  = ''
  filterChoix.value   = ''
  filterEvalue.value  = ''
}

const uniqueProcesses = computed(() => {
  const map = new Map()
  for (const r of allRisks.value) {
    if (!map.has(r.process_code))
      map.set(r.process_code, { code: r.process_code, name: r.process_name })
  }
  return Array.from(map.values())
})

const filteredRisks = computed(() => {
  let risks = allRisks.value
  const q = searchQuery.value.toLowerCase()
  if (q) risks = risks.filter(r =>
    r.label?.toLowerCase().includes(q) ||
    r.code?.toLowerCase().includes(q)  ||
    r.process_code?.toLowerCase().includes(q) ||
    r.activity_name?.toLowerCase().includes(q)
  )
  if (filterProcess.value) risks = risks.filter(r => r.process_code === filterProcess.value)
  if (filterNature.value)  risks = risks.filter(r => riskForms[r.id]?.nature === filterNature.value)
  if (filterQualif.value)  risks = risks.filter(r => riskForms[r.id]?.qualif_controle === filterQualif.value)
  if (filterChoix.value === 'selected')   risks = risks.filter(r => riskForms[r.id]?.choix)
  if (filterChoix.value === 'unselected') risks = risks.filter(r => !riskForms[r.id]?.choix)
  if (filterEvalue.value === 'yes') risks = risks.filter(r => r.is_evaluated)
  if (filterEvalue.value === 'no')  risks = risks.filter(r => !r.is_evaluated)
  return risks
})

const selectedCount = computed(() =>
  allRisks.value.filter(r => riskForms[r.id]?.choix).length
)

function sortBy(key: string) {
  if (sortKey.value === key) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  else { sortKey.value = key; sortDir.value = 'asc' }
}
function sortIcon(key: string) {
  if (sortKey.value !== key) return 'ti ti-arrows-sort th-sort-ico'
  return sortDir.value === 'asc'
    ? 'ti ti-sort-ascending th-sort-ico active'
    : 'ti ti-sort-descending th-sort-ico active'
}

// ── Groupement par processus ───────────────────────────────────────────────
const groupedRisks = computed(() => {
  const sorted = [...filteredRisks.value].sort((a, b) => {
    let av: any = (a as any)[sortKey.value] ?? ''
    let bv: any = (b as any)[sortKey.value] ?? ''
    if (typeof av === 'string') av = av.toLowerCase()
    if (typeof bv === 'string') bv = bv.toLowerCase()
    return av < bv
      ? (sortDir.value === 'asc' ? -1 : 1)
      : av > bv ? (sortDir.value === 'asc' ? 1 : -1) : 0
  })
  const groups = new Map<string, any>()
  for (const r of sorted) {
    const k = r.process_code ?? '—'
    if (!groups.has(k))
      groups.set(k, { processCode: k, processName: r.process_name ?? k, risks: [] })
    groups.get(k)!.risks.push(r)
  }
  return Array.from(groups.values())
})

function toggleGroup(code: string) {
  if (expandedGroups.value.has(code)) expandedGroups.value.delete(code)
  else expandedGroups.value.add(code)
}

// ── Processus disponibles pour ajout ──────────────────────────────────────
const usedProcessCodes   = computed(() => new Set(allRisks.value.map(r => r.process_code)))
const availableProcesses = computed(() =>
  (props.allProcesses as any[]).filter(p => !usedProcessCodes.value.has(p.code))
)

// ── Modal activité ─────────────────────────────────────────────────────────
const actModal = reactive({
  open: false, processId: null as number | null,
  processCode: '', processName: '', search: '', selectedId: null as number | null,
})

function openAddActivity(group: any) {
  const proc = (props.allProcesses as any[]).find(p => p.code === group.processCode)
  Object.assign(actModal, {
    open: true, processId: proc?.id ?? null,
    processCode: group.processCode, processName: group.processName,
    search: '', selectedId: null,
  })
}
function closeActModal() { actModal.open = false }

const filteredModalActivities = computed(() => {
  let acts = (props.allActivities as any[]).filter(a => a.process_id === actModal.processId)
  const usedActIds = new Set(allRisks.value
    .filter(r => r.process_id === actModal.processId)
    .map(r => r.activity_id))
  acts = acts.filter(a => !usedActIds.has(a.id))
  const s = actModal.search.toLowerCase()
  if (s) acts = acts.filter(a =>
    a.name?.toLowerCase().includes(s) || a.code?.toLowerCase().includes(s)
  )
  return acts
})

function confirmAddActivity() {
  if (!actModal.selectedId) return
  const act  = (props.allActivities as any[]).find(a => a.id === actModal.selectedId)
  const proc = (props.allProcesses  as any[]).find(p => p.id === actModal.processId)
  if (!act) return
  const newRisk = {
    id: -(Date.now()), code: genTempCode(proc?.code ?? 'XX'),
    label: `Nouveau risque — ${act.name}`, description: '',
    process_id: actModal.processId, process_code: actModal.processCode,
    process_name: actModal.processName,
    activity_id: act.id, activity_code: act.code, activity_name: act.name,
    risk_type_id: null, risk_type_label: '-', risk_type_color: 'secondary',
    impact_level: null, impact_label: '-', impact_color: 'secondary',
    frequency_level: null, frequency_label: '-', frequency_color: 'secondary',
    criticality: null, control_procedure: '', status: 'identified',
    is_evaluated: false, criticality_net: null, qualification_net: null,
    _isNew: true,
  }
  extraRisks.value.push(newRisk)
  riskForms[newRisk.id] = makeRiskForm(newRisk)
  expandedGroups.value.add(actModal.processCode)
  closeActModal()
  showToast('success', `Activité "${act.name}" ajoutée`)
}

function genTempCode(processCode: string): string {
  const base = processCode.replace(/[^A-Z0-9]/gi, '').toUpperCase().slice(0, 4)
  return `${base}A${String(allRisks.value.filter(r => r.code?.startsWith(base)).length + 1).padStart(2, '0')}P`
}

// ── Modal processus ────────────────────────────────────────────────────────
const procModal = reactive({ open: false, process: null as any, selectedIds: [] as number[] })

const procModalRisks = computed(() => {
  if (!procModal.process) return []
  const usedIds = new Set(allRisks.value.map(r => r.id))
  return (props.risksData as any[]).filter(
    r => r.process_id === procModal.process?.id && !usedIds.has(r.id)
  )
})

function openAddProcess(proc: any)  { procModal.process = proc; procModal.selectedIds = []; procModal.open = true }
function closeProcModal()           { procModal.open = false }
function toggleProcRisk(id: number) {
  const idx = procModal.selectedIds.indexOf(id)
  if (idx >= 0) procModal.selectedIds.splice(idx, 1)
  else procModal.selectedIds.push(id)
}

function confirmAddProcess() {
  const toAdd = (props.risksData as any[]).filter(r => procModal.selectedIds.includes(r.id))
  for (const r of toAdd) {
    if (!allRisks.value.find(x => x.id === r.id)) {
      extraRisks.value.push({ ...r, _isNew: true })
      riskForms[r.id] = makeRiskForm(r)
    }
  }
  expandedGroups.value.add(procModal.process?.code ?? '')
  closeProcModal()
  showToast('success', `${toAdd.length} risque(s) ajouté(s)`)
}

// ── Statut / accès ─────────────────────────────────────────────────────────
const canManage = computed(() => ['DM', 'CM'].includes(props.auditorRole ?? ''))
const isLocked  = computed(() =>
  ar.validation_status === 'validated' ||
  (ar.validation_status === 'in_review' && !canManage.value)
)

// ── Build payload pour sauvegarde ──────────────────────────────────────────
function buildPayload() {
  return allRisks.value.map(r => ({
    risk_id:      r.id,
    risk_code:    r.code,
    process_code: r.process_code,
    activity_id:  r.activity_id,
    ...(riskForms[r.id] ?? {}),
    _isNew: undefined,  // ne pas sauvegarder ce flag
  }))
}

// ── Submit ─────────────────────────────────────────────────────────────────
async function submit() {
  if (isLocked.value) return
  processing.value = true
  const url    = form.id ? (props.editUrl || `${props.formUrl}/${form.id}`) : props.formUrl
  const method: 'post' | 'put' = form.id ? 'put' : 'post'

  router[method](url, {
    mission_id:    props.missionId,
    assignment_id: props.assignmentId,
    fait_par:      form.fait_par,
    revue_par:     form.revue_par,
    synthese:      synthese.value,
    risques:       JSON.stringify(buildPayload()),
  }, {
    preserveScroll: true,
    onSuccess: (page: any) => {
      const n = page.props?.form
      if (n) {
        if (!form.id) form.id = n.id
        if (n.code)   form.code = n.code
        Object.assign(ar, n)
      }
      showToast('success', 'Analyse enregistrée')
    },
    onError:  () => showToast('error', 'Erreur — vérifiez les champs'),
    onFinish: () => { processing.value = false },
  })
}

function annuler() {
  initRiskForms()
  extraRisks.value = []
  synthese.value   = ''
  Object.assign(form, { id: null, code: '', fait_par: auditorFullName(), revue_par: '' })
  Object.assign(ar, {})
}

function loadAr(item: any) {
  router.visit(
    `${props.formUrl}/${item.id}/edit?mission_id=${props.missionId}&assignment_id=${props.assignmentId}`
  )
}

// ── Workflow validation ────────────────────────────────────────────────────
async function soumettre() {
  if (!form.id) { showToast('error', "Enregistrez d'abord."); return }
  if (!confirm("Soumettre l'analyse ?")) return
  await apiPost(
    `${props.formUrl}/${form.id}/soumettre`,
    { mission_id: props.missionId, assignment_id: props.assignmentId },
    (j: any) => { ar.validation_status = j.status; showToast('success', 'Soumis') }
  )
}
async function valider(action: string, note?: string) {
  await apiPost(
    `${props.formUrl}/${form.id}/valider`,
    { mission_id: props.missionId, assignment_id: props.assignmentId, action, note },
    (j: any) => {
      ar.validation_status = j.status
      showToast('success', action === 'validated' ? 'Validé ✓' : 'Rejeté')
    }
  )
}
function promptReject() {
  const n = prompt('Motif du rejet :')
  if (!n?.trim()) return
  valider('rejected', n)
}

async function apiPost(url: string, body: object, onOk: (j: any) => void) {
  processing.value = true
  try {
    const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
    const r = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
      body: JSON.stringify(body),
    })
    const j = await r.json()
    if (!r.ok) throw new Error(j?.message ?? 'Erreur')
    onOk(j)
  } catch (e: any) { showToast('error', e.message) }
  finally { processing.value = false }
}

// ── Toast ──────────────────────────────────────────────────────────────────
const toast = ref({ show: false, type: 'success', msg: '' })
let tt: ReturnType<typeof setTimeout>
function showToast(type: string, msg: string) {
  if (tt) clearTimeout(tt)
  toast.value = { show: true, type, msg }
  tt = setTimeout(() => { toast.value.show = false }, 3200)
}
function vstLbl(s: string) {
  return ({ draft: 'Brouillon', in_review: 'En attente', validated: 'Validé ✓', rejected: 'Rejeté' } as any)[s] ?? s
}
function vstIcon(s: string) {
  return ({ draft: 'ti ti-pencil', in_review: 'ti ti-clock', validated: 'ti ti-circle-check', rejected: 'ti ti-circle-x' } as any)[s] ?? 'ti ti-circle'
}

// ── Lifecycle ──────────────────────────────────────────────────────────────
onMounted(() => {
  for (const g of groupedRisks.value) expandedGroups.value.add(g.processCode)
})
</script>

<style scoped>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0 }
.ar-shell {
  display: flex; flex-direction: column; min-height: 100vh;
  background: #f0f4f8; font-family: 'Segoe UI', system-ui, sans-serif;
  --mc: #1565C0; --or: #f59e0b; --gr: #15803d; --rd: #dc2626;
}

/* ── Header ── */
.ar-header { position: sticky; top: 0; z-index: 100; background: #fff; border-bottom: 1px solid #e2e8f0; box-shadow: 0 1px 4px rgba(0,0,0,.06); padding: 0 16px }
.ar-hrow { display: flex; align-items: center; gap: 10px; min-height: 58px; padding: 6px 0; flex-wrap: wrap }
.ar-back { display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 7px; background: #f1f5f9; border: 1px solid #e2e8f0; color: #64748b; text-decoration: none; flex-shrink: 0; transition: all .15s }
.ar-back:hover { background: var(--mc); color: #fff; border-color: var(--mc) }
.ar-hinfo { flex: 1; min-width: 0 }
.ar-chips { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; margin-bottom: 2px }
.ar-chip { display: inline-flex; align-items: center; gap: 3px; font-size: .6rem; font-weight: 700; padding: 2px 7px; border-radius: 9px; text-transform: uppercase; letter-spacing: .04em }
.ar-chip-sm { font-size: .58rem; font-weight: 700; padding: 1px 5px; border-radius: 7px; text-transform: uppercase }
.chip-draft { background: #f1f5f9; color: #64748b }
.chip-in_review { background: #e3f2fd; color: #1565C0; border: 1px solid rgba(21,101,192,.2) }
.chip-validated { background: #d1e7dd; color: #0f5132 }
.chip-rejected { background: #f8d7da; color: #842029 }
.chip-type { background: rgba(239,68,68,.12); color: #b91c1c }
.chip-year { background: rgba(21,101,192,.12); color: #1565C0; border: 1px solid rgba(21,101,192,.25) }
.chip-role-DM { background: rgba(251,191,36,.2); color: #d97706 }
.chip-role-CM { background: rgba(21,101,192,.12); color: #1565C0 }
.chip-role-AS { background: rgba(22,163,74,.12); color: #15803d }
.chip-role-AJ { background: rgba(124,58,237,.12); color: #6d28d9 }
.ar-warn-inline { color: #b45309 !important; background: #fef3c7; padding: 2px 8px; border-radius: 6px; border: 1px solid #fde68a; font-size: .67rem !important }
.ar-code { font-family: monospace; font-size: .66rem; font-weight: 700; padding: 2px 7px; border-radius: 5px; background: color-mix(in srgb, var(--mc) 8%, white); border: 1px solid color-mix(in srgb, var(--mc) 25%, transparent); color: var(--mc) }
.ar-title { font-size: .88rem; font-weight: 700; color: #1a1a2e }
.ar-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 2px }
.ar-meta span { display: inline-flex; align-items: center; gap: 3px; font-size: .67rem; color: #64748b }
.ar-banner { display: flex; align-items: center; gap: 6px; padding: 5px 0 8px; font-size: .76rem; border-top: 1px solid #f1f5f9 }
.banner-lock { color: #0f5132 }
.banner-review { color: #1565C0 }

/* ── View actions ── */
.ar-view-actions { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; flex-shrink: 0 }
.ar-search-wrap { position: relative; display: flex; align-items: center }
.ar-search-ico { position: absolute; left: 8px; color: #94a3b8; font-size: .78rem; pointer-events: none }
.ar-search { border: 1px solid #e2e8f0; border-radius: 7px; padding: 5px 28px; font-size: .74rem; color: #374151; font-family: inherit; outline: none; width: 180px; background: #f8fafc; transition: all .15s }
.ar-search:focus { border-color: var(--mc); background: #fff; width: 220px }
.ar-search-clr { position: absolute; right: 6px; background: none; border: none; color: #94a3b8; cursor: pointer; font-size: .75rem; padding: 2px }
.ar-filter-btn { display: flex; align-items: center; gap: 4px; padding: 5px 9px; border: 1px solid #e2e8f0; border-radius: 7px; background: #f8fafc; color: #64748b; cursor: pointer; font-size: .78rem; font-family: inherit; transition: all .15s; position: relative }
.ar-filter-btn:hover, .ar-filter-btn.active { border-color: var(--mc); color: var(--mc); background: #eff6ff }
.filter-badge { position: absolute; top: -5px; right: -5px; background: var(--rd); color: #fff; font-size: .58rem; font-weight: 700; width: 16px; height: 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center }
.ar-filters { display: flex; align-items: flex-end; gap: 10px; padding: 8px 0 10px; border-top: 1px solid #f1f5f9; flex-wrap: wrap }
.filter-group { display: flex; flex-direction: column; gap: 3px }
.filter-group label { font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #64748b }
.f-sel { border: 1px solid #e2e8f0; border-radius: 5px; padding: 4px 8px; font-size: .72rem; color: #374151; font-family: inherit; background: #fff; outline: none; cursor: pointer }
.f-sel:focus { border-color: var(--mc) }
.btn-reset-filters { display: flex; align-items: center; gap: 4px; padding: 5px 10px; border: 1px solid #e2e8f0; border-radius: 6px; background: #fff; color: #64748b; font-size: .72rem; cursor: pointer; font-family: inherit; transition: all .13s }
.btn-reset-filters:hover { background: #fef2f2; border-color: #fca5a5; color: var(--rd) }
.ar-list-dd { position: relative }
.ar-list-menu { position: absolute; top: calc(100% + 4px); right: 0; background: #fff; border: 1px solid #e2e8f0; border-radius: 9px; box-shadow: 0 4px 16px rgba(0,0,0,.1); z-index: 200; min-width: 200px; overflow: hidden }
.ar-list-item { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 7px 12px; cursor: pointer; font-size: .74rem; border-bottom: 1px solid #f8fafc; transition: background .12s }
.ar-list-item:hover { background: #f0f6ff }
.ar-list-item code { color: var(--mc); font-weight: 600 }

/* ── Info univers bar ── */
.universe-info-bar {
  display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
  background: rgba(21,101,192,.06); border: 1px solid rgba(21,101,192,.18);
  border-radius: 8px; padding: 7px 14px; font-size: .74rem; color: #1565C0;
}
.universe-info-bar i { font-size: .9rem; flex-shrink: 0 }
.universe-info-bar span { display: flex; align-items: center; gap: 5px }
.univ-ok { color: #15803d; display: flex; align-items: center; gap: 4px; font-size: .7rem; font-weight: 600 }

/* ── Body ── */
.ar-body { flex: 1; padding: 12px 16px 24px; display: flex; flex-direction: column; gap: 10px }
.ar-empty { text-align: center; padding: 60px; color: #94a3b8; display: flex; flex-direction: column; align-items: center; gap: 10px }
.ar-empty i { font-size: 2.5rem; color: var(--or) }
.ar-empty strong { color: #475569 }
.ar-empty p { font-size: .79rem; max-width: 420px; text-align: center; line-height: 1.6 }

/* ── Table ── */
.ar-table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 9px; overflow: hidden }
.tbl-scroll { overflow: auto; max-height: 600px }
.ar-tbl { width: 100%; border-collapse: collapse; font-size: .71rem; min-width: 1600px }
.ar-tbl thead th { background: #1565C0; color: rgba(255,255,255,.92); font-size: .57rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; padding: 7px 8px; border: none; white-space: nowrap; position: sticky; top: 0; z-index: 10; cursor: pointer; user-select: none; vertical-align: bottom }
.ar-tbl thead th:hover { background: #1e40af }
.th-stick  { position: sticky !important; left: 0;   z-index: 11 !important; background: #1e3a5f !important; min-width: 80px !important; cursor: default !important }
.th-stick2 { position: sticky !important; left: 80px; z-index: 11 !important; background: #1e3a5f !important; min-width: 110px !important; cursor: default !important }
.th-sort-ico { font-size: .7rem; margin-left: 2px; opacity: .5 }
.th-sort-ico.active { opacity: 1; color: #fbbf24 }
.th-code { min-width: 110px } .th-label { min-width: 220px } .th-ctrl { min-width: 180px }
.th-num { min-width: 68px; text-align: center } .th-nature { min-width: 55px } .th-qualif { min-width: 85px }
.th-choix { min-width: 50px; text-align: center } .th-assert { min-width: 160px } .th-forces { min-width: 140px }
.th-faib { min-width: 140px } .th-obj { min-width: 160px } .th-filt { min-width: 28px; padding: 4px !important }
.ar-tbl tbody td { padding: 4px 7px; border: 1px solid #e9ecef; vertical-align: top }

/* Lignes groupe */
.row-group { cursor: pointer; background: #1565C0 }
.row-group:hover td { background: #1e3a5f !important }
.td-group { background: #1565C0 !important; padding: 5px 10px !important }
.group-hd { display: flex; align-items: center; gap: 8px; color: #fff; font-size: .74rem; font-weight: 700 }
.group-hd i { font-size: .82rem; color: rgba(255,255,255,.75); flex-shrink: 0 }
.group-code { background: rgba(255,255,255,.15); padding: 2px 7px; border-radius: 5px; font-size: .68rem; color: #fff; font-family: monospace }
.group-name { flex: 1; font-weight: 600 }
.group-count { font-size: .64rem; font-weight: 400; color: rgba(255,255,255,.65); background: rgba(255,255,255,.12); padding: 1px 7px; border-radius: 8px }
.btn-add-act { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 6px; border: 1.5px solid rgba(255,255,255,.4); background: rgba(255,255,255,.1); color: #fff; font-size: .65rem; font-weight: 700; cursor: pointer; font-family: inherit; transition: all .14s; margin-left: auto }
.btn-add-act:hover { background: rgba(255,255,255,.2); border-color: #fff }

/* Lignes risques */
.row-risk { background: #fff; transition: background .1s }
.row-risk:hover td { background: #f8fbff }
.row-risk-selected td { background: #f0fdf4 }
.row-risk-selected:hover td { background: #dcfce7 }
.row-risk-new td { background: #fffbeb }
.row-risk-new:hover td { background: #fef3c7 }
.row-risk-evaluated td { border-left: 2px solid rgba(21,101,192,.2) }

/* Colonnes sticky */
.td-stick  { position: sticky; left: 0;   z-index: 2; background: inherit }
.td-stick2 { position: sticky; left: 80px; z-index: 2; background: inherit }
.td-proc   { font-size: .68rem; font-weight: 700; color: var(--mc); white-space: nowrap; min-width: 80px }
.td-sproc  { font-size: .68rem; color: #374151; min-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap }

/* Code risque + badges */
.td-code { white-space: nowrap }
.risk-code { font-family: monospace; font-size: .68rem; font-weight: 700; color: var(--mc); background: color-mix(in srgb, var(--mc) 8%, white); padding: 2px 6px; border-radius: 4px; border: 1px solid color-mix(in srgb, var(--mc) 20%, transparent) }
.badge-new { display: inline-flex; margin-left: 4px; font-size: .55rem; font-weight: 800; padding: 1px 5px; border-radius: 4px; background: #fef3c7; color: #92400e; border: 1px solid #fde68a; vertical-align: middle }
.badge-univ { display: inline-flex; align-items: center; justify-content: center; margin-left: 4px; font-size: .62rem; width: 16px; height: 16px; border-radius: 50%; background: rgba(21,101,192,.12); color: var(--mc); vertical-align: middle }

/* Label + qualif univers */
.risk-label-wrap { display: flex; align-items: flex-start; gap: 5px }
.risk-type-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; margin-top: 3px }
.risk-label-col { display: flex; flex-direction: column; gap: 2px; min-width: 0 }
.risk-label-txt { font-size: .71rem; color: #1a1a2e; line-height: 1.45 }
.risk-qualif-univ { display: inline-flex; align-items: center; font-size: .6rem; font-weight: 700; padding: 1px 6px; border-radius: 6px; width: fit-content }
.qualif-acceptable   { background: #d1fae5; color: #065f46 }
.qualif-surveiller   { background: #fef3c7; color: #92400e }
.qualif-actionrequise { background: #fee2e2; color: #991b1b }
.qualif-critique     { background: #991b1b; color: #fff }

/* Niveaux */
.td-num { text-align: center; padding: 3px !important }
.level-wrap { display: flex; flex-direction: column; align-items: center; gap: 2px }
.level-cell { border-radius: 5px; display: flex; align-items: center; justify-content: center; min-height: 30px; overflow: hidden; width: 100% }
.lv-sel { background: transparent; border: none; color: inherit; font-size: .82rem; font-weight: 700; cursor: pointer; text-align: center; width: 100%; padding: 3px; outline: none }
.lv-sel option { color: #1a1a2e; background: #fff }
.level-ro { border-radius: 5px; display: flex; align-items: center; justify-content: center; min-height: 26px; font-size: .82rem; font-weight: 700; padding: 3px 8px; width: 100% }
.glob-cell { font-weight: 800; font-size: .85rem; min-width: 36px }
/* Indicateur source univers */
.src-univ { font-size: .55rem; font-weight: 800; color: rgba(21,101,192,.7); letter-spacing: .02em; line-height: 1 }

/* Nature / qualif */
.td-nature { text-align: center }
.c-sel-sm { width: 100%; border: 1px solid #e2e8f0; border-radius: 4px; padding: 3px 5px; font-size: .7rem; color: #374151; font-family: inherit; background: rgba(255,255,255,.85); outline: none; cursor: pointer }
.c-sel-sm:focus { border-color: var(--mc) }
.nature-badge { display: inline-flex; padding: 2px 7px; border-radius: 7px; font-size: .66rem; font-weight: 700 }
.nature-RM { background: #dbeafe; color: #1e40af } .nature-RF { background: #fce7f3; color: #9d174d }
.nature-RO { background: #d1fae5; color: #065f46 } .nature-RC { background: #fef3c7; color: #92400e }
.nature-RS { background: #ede9fe; color: #5b21b6 }
.qc-badge { font-size: .7rem; font-weight: 600; color: #374151 }

/* Choix */
.td-choix { text-align: center; padding: 3px !important }
.choix-toggle { display: flex; align-items: center; justify-content: center; cursor: pointer }
.choix-toggle input { display: none }
.choix-box { width: 20px; height: 20px; border: 2px solid #d1d5db; border-radius: 5px; display: flex; align-items: center; justify-content: center; font-size: .75rem; transition: all .14s; background: #fff }
.choix-box.checked { background: #1565C0; border-color: #1565C0; color: #fff }

/* Textareas */
.c-ta { width: 100%; border: 1px solid #e2e8f0; border-radius: 4px; padding: 3px 6px; font-size: .7rem; color: #1a1a2e; font-family: inherit; resize: vertical; outline: none; min-height: 34px; background: rgba(255,255,255,.7) }
.c-ta:focus { border-color: var(--mc); background: #fff }
.c-ta-green { border-color: #bbf7d0 } .c-ta-green:focus { border-color: var(--gr) }
.c-ta-red   { border-color: #fecdd3 } .c-ta-red:focus   { border-color: var(--rd) }
.ro-txt { font-size: .7rem; color: #374151; white-space: pre-wrap; line-height: 1.4 }
.ro-green { color: #15803d } .ro-red { color: #dc2626 }
.td-filt { padding: 2px !important; text-align: center; vertical-align: middle }
.filt-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50% }
.filt-green { background: var(--gr) } .filt-red { background: var(--rd) }
.td-no-result { text-align: center; padding: 30px; color: #94a3b8; font-size: .78rem }

/* ── Ajouter processus ── */
.add-proc-bar { display: flex; align-items: flex-start; gap: 10px; background: #fff; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 8px 12px; flex-wrap: wrap }
.add-proc-lbl { display: inline-flex; align-items: center; gap: 5px; font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; white-space: nowrap; flex-shrink: 0; margin-top: 4px }
.add-proc-pills { display: flex; flex-wrap: wrap; gap: 5px; flex: 1 }
.btn-add-proc { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px 3px 8px; border-radius: 14px; cursor: pointer; font-family: inherit; font-size: .7rem; transition: all .14s; background: #f8fafc; border: 1.5px dashed #cbd5e1; color: #64748b }
.btn-add-proc:hover { background: #f0f6ff; border-color: var(--mc); color: var(--mc); border-style: solid }
.btn-add-proc code { font-size: .66rem; font-weight: 700; color: inherit }
.btn-add-proc span { max-width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap }
.btn-add-proc i { font-size: .72rem; flex-shrink: 0; color: #94a3b8 }

/* ── Synthèse ── */
.synth-row { display: flex; gap: 10px; background: #fff; border: 1px solid #e2e8f0; border-radius: 9px; padding: 12px; flex-wrap: wrap }
.synth-f { flex: 1; min-width: 260px; display: flex; flex-direction: column; gap: 5px }
.synth-f label { font-size: .63rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--mc); display: flex; align-items: center; gap: 5px }
.synth-ta { width: 100%; border: 1px solid #e2e8f0; border-radius: 6px; padding: 7px 10px; font-size: .76rem; color: #1a1a2e; font-family: inherit; resize: vertical; outline: none }
.synth-ta:focus { border-color: var(--mc) }
.synth-ro { font-size: .76rem; color: #374151; white-space: pre-wrap; min-height: 40px }
.author-fs { display: flex; flex-direction: column; gap: 8px; min-width: 180px }
.af label { font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #64748b; display: block; margin-bottom: 3px }
.inp { width: 100%; border: 1px solid #d1d5db; border-radius: 5px; padding: 5px 8px; font-size: .76rem; color: #1a1a2e; font-family: inherit; outline: none }
.inp:focus { border-color: var(--mc) }
.inp:disabled { background: #f8fafc; color: #94a3b8 }

/* ── Buttons ── */
.btn { display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; border-radius: 6px; font-size: .74rem; font-weight: 600; border: 1px solid transparent; cursor: pointer; font-family: inherit; text-decoration: none; transition: all .12s }
.btn:disabled { opacity: .5; cursor: not-allowed }
.btn-sm { padding: 4px 9px; font-size: .7rem }
.btn-ghost { background: transparent; color: #64748b; border-color: #d1d5db }
.btn-ghost:hover { background: #f1f5f9 }
.btn-save { background: var(--mc); color: #fff }
.btn-save:hover:not(:disabled) { filter: brightness(1.1) }
.btn-sub { background: #0f766e; color: #fff } .btn-sub:hover { background: #0d6460 }
.btn-ok  { background: #15803d; color: #fff } .btn-ok:hover  { background: #166534 }
.btn-rej { background: #dc2626; color: #fff } .btn-rej:hover { background: #b91c1c }

/* ── Footer ── */
.ar-footer { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; padding: 10px 14px; background: #fff; border: 1px solid #e2e8f0; border-radius: 9px }
.ar-footer > div { display: flex; align-items: center; gap: 7px }
.footer-c { flex: 1; display: flex; justify-content: center }
.saved-code { font-size: .72rem; color: #15803d; display: flex; align-items: center; gap: 4px; font-weight: 600 }
.spin-dot { width: 11px; height: 11px; border: 2px solid rgba(255,255,255,.4); border-top-color: #fff; border-radius: 50%; animation: spin .6s linear infinite }

/* ── Modals ── */
.modal-ov { position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 400; display: flex; align-items: center; justify-content: center; padding: 20px }
.modal-box { background: #fff; border-radius: 14px; box-shadow: 0 8px 40px rgba(0,0,0,.22); width: 100%; max-width: 700px; max-height: 88vh; display: flex; flex-direction: column; overflow: hidden }
.modal-sm { max-width: 480px }
.modal-hd { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; border-bottom: 1px solid #e2e8f0; flex-shrink: 0; gap: 8px; flex-wrap: wrap }
.modal-hd-l { display: flex; align-items: center; gap: 8px; font-size: .82rem; font-weight: 700; color: #1a1a2e; flex-wrap: wrap }
.modal-hd-l i { color: var(--mc); font-size: 1.05rem }
.modal-hint { font-size: .76rem; color: #64748b; margin-bottom: 10px; line-height: 1.5 }
.modal-cls { width: 28px; height: 28px; border: none; background: #f1f5f9; border-radius: 7px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #64748b; transition: all .13s; flex-shrink: 0 }
.modal-cls:hover { background: #fee2e2; color: #dc2626 }
.modal-body { flex: 1; overflow-y: auto; padding: 14px 18px }
.modal-ft { display: flex; justify-content: flex-end; gap: 8px; padding: 12px 18px; border-top: 1px solid #e2e8f0; flex-shrink: 0 }
.modal-search-wrap { position: relative; display: flex; align-items: center; margin-bottom: 8px }
.modal-search-ico { position: absolute; left: 9px; color: #94a3b8; font-size: .8rem; pointer-events: none }
.modal-search { width: 100%; border: 1px solid #e2e8f0; border-radius: 7px; padding: 6px 10px 6px 30px; font-size: .76rem; color: #374151; font-family: inherit; outline: none; background: #f8fafc }
.modal-search:focus { border-color: var(--mc); background: #fff }
.modal-list { border: 1.5px solid #e2e8f0; border-radius: 9px; overflow-y: auto; max-height: 300px }
.modal-empty { display: flex; flex-direction: column; align-items: center; gap: 7px; padding: 24px; color: #94a3b8; font-size: .78rem; text-align: center }
.modal-empty i { font-size: 1.4rem; opacity: .3 }
.modal-item { display: flex; align-items: center; gap: 10px; padding: 9px 12px; cursor: pointer; border-bottom: 1px solid #f8fafc; transition: background .12s }
.modal-item:last-child { border-bottom: none }
.modal-item:hover { background: #f5f3ff }
.modal-item.selected { background: #eff6ff }
.modal-item-ico { width: 28px; height: 28px; background: #eff6ff; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: var(--mc); flex-shrink: 0; font-size: .85rem }
.modal-item-info { flex: 1; min-width: 0 }
.modal-item-info strong { display: block; font-size: .78rem; color: #0f172a }
.modal-item-info code { font-size: .68rem; color: #94a3b8 }
.modal-check { color: #22c55e; font-size: .9rem; flex-shrink: 0 }

/* ── Toast ── */
.toast { position: fixed; bottom: 22px; right: 22px; z-index: 600; display: flex; align-items: center; gap: 9px; padding: 10px 16px; border-radius: 9px; font-size: .78rem; font-weight: 600; box-shadow: 0 4px 16px rgba(0,0,0,.18) }
.toast-success { background: #15803d; color: #fff }
.toast-error   { background: #dc2626; color: #fff }

/* ── Transitions ── */
.mfade-enter-active, .mfade-leave-active { transition: all .2s ease }
.mfade-enter-from, .mfade-leave-to { opacity: 0 }
.mfade-enter-from .modal-box, .mfade-leave-to .modal-box { transform: scale(.96) translateY(8px) }
.toast-up-enter-active, .toast-up-leave-active { transition: all .22s ease }
.toast-up-enter-from, .toast-up-leave-to { opacity: 0; transform: translateY(8px) }
.slide-down-enter-active, .slide-down-leave-active { transition: all .2s ease }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; max-height: 0; overflow: hidden }

::-webkit-scrollbar { width: 5px; height: 5px }
::-webkit-scrollbar-track { background: transparent }
::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 3px }
::-webkit-scrollbar-thumb:hover { background: #cbd5e1 }
@keyframes spin { to { transform: rotate(360deg) } }
</style>