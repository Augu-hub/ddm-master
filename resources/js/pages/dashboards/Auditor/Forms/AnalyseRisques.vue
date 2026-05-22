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
              <span class="ar-chip" :class="`chip-${ar.validation_status||'draft'}`">
                <i :class="vstIcon(ar.validation_status||'draft')"></i>
                {{ vstLbl(ar.validation_status||'draft') }}
              </span>
              <span class="ar-chip chip-type">AR</span>
              <span class="ar-chip chip-year"><i class="ti ti-calendar"></i> Univers {{ props.activeYear }}</span>
              <span v-if="props.auditorRole" class="ar-chip" :class="`chip-role-${props.auditorRole}`">{{ props.auditorRole }}</span>
            </div>
            <h1 class="ar-title">Analyse des Risques — AC P2</h1>
            <div class="ar-meta">
              <span v-if="assignment?.phase_label"><i class="ti ti-git-branch"></i>{{ assignment.phase_label }}</span>
              <span v-if="mission?.entity_name"><i class="ti ti-building"></i>{{ mission.entity_name }}</span>
              <span><i class="ti ti-users"></i>{{ phaseAuditeurs.length }} auditeurs</span>
              <span><i class="ti ti-shield-check"></i>{{ filteredRisks.length }} risques · {{ selectedCount }} sélectionnés</span>
            </div>
          </div>
          <div class="ar-hactions">
            <div class="ar-search-wrap">
              <i class="ti ti-search ar-sico"></i>
              <input v-model="searchQuery" class="ar-search" placeholder="Rechercher…"/>
              <button v-if="searchQuery" class="ar-sclear" @click="searchQuery=''"><i class="ti ti-x"></i></button>
            </div>
            <button class="ar-filter-btn" :class="{active:showFilters}" @click="showFilters=!showFilters">
              <i class="ti ti-filter"></i>
              <span v-if="activeFilterCount" class="filter-badge">{{ activeFilterCount }}</span>
            </button>
          </div>
        </div>
        <div v-if="ar.validation_status==='validated'" class="ar-banner banner-lock">
          <i class="ti ti-lock"></i> Analyse <strong>validée</strong> — lecture seule
        </div>
        <div v-else-if="ar.validation_status==='in_review'" class="ar-banner banner-review">
          <i class="ti ti-clock"></i> En attente de validation<span v-if="canManage"> · Vous pouvez valider ou rejeter.</span>
        </div>
        <div v-else-if="ar.validation_status==='draft'&&ar.validation_note" class="ar-banner banner-reject">
          <i class="ti ti-circle-x"></i> Rejeté — <em>{{ ar.validation_note }}</em>
        </div>
        <!-- Filtres -->
        <transition name="slide-down">
          <div v-if="showFilters" class="ar-filters">
            <div class="fgrp"><label>Processus</label>
              <select v-model="filterProcess" class="f-sel">
                <option value="">Tous</option>
                <option v-for="p in uniqueProcesses" :key="p.code" :value="p.code">{{ p.code }} — {{ p.name }}</option>
              </select>
            </div>
            <div class="fgrp"><label>Nature</label>
              <select v-model="filterNature" class="f-sel">
                <option value="">Toutes</option>
                <option v-for="n in natures" :key="n">{{ n }}</option>
              </select>
            </div>
            <div class="fgrp"><label>Qualif.</label>
              <select v-model="filterQualif" class="f-sel">
                <option value="">Toutes</option>
                <option v-for="q in qualifControlesList" :key="q">{{ q }}</option>
              </select>
            </div>
            <div class="fgrp"><label>Évalué</label>
              <select v-model="filterEvalue" class="f-sel">
                <option value="">Tous</option><option value="yes">Évalués</option><option value="no">Non évalués</option>
              </select>
            </div>
            <div class="fgrp"><label>Choix</label>
              <select v-model="filterChoix" class="f-sel">
                <option value="">Tous</option><option value="selected">Sélectionnés</option><option value="unselected">Non sélectionnés</option>
              </select>
            </div>
            <div class="fgrp"><label>Affectation</label>
              <select v-model="filterAssign" class="f-sel">
                <option value="">Tous</option><option value="mine">Mes processus</option>
                <option value="assigned">Affectés</option><option value="unassigned">Non affectés</option>
              </select>
            </div>
            <button class="btn-reset-f" @click="resetFilters"><i class="ti ti-rotate-2"></i> Réinitialiser</button>
          </div>
        </transition>
      </header>

      <!-- ══ BODY ══ -->
      <div class="ar-body">
        <div class="ar-layout">

          <!-- ════ SIDEBAR ════ -->
          <aside class="ar-sidebar">

            <!-- Mission -->
            <section class="card">
              <div class="card-lbl"><i class="ti ti-briefcase"></i> Mission</div>
              <div class="card-body">
                <div class="fg"><span class="flbl">Code</span><input class="inp inp-ro" :value="mission?.code_mission" readonly/></div>
                <div class="fg"><span class="flbl">Entité</span><input class="inp inp-ro" :value="mission?.entity_name||'—'" readonly/></div>
                <div class="fg"><span class="flbl">Année</span><input class="inp inp-ro" :value="props.activeYear" readonly/></div>
              </div>
            </section>

            <!-- Auditeurs -->
            <section class="card">
              <div class="card-lbl"><i class="ti ti-users"></i> Auditeurs <span class="card-cnt">{{ phaseAuditeurs.length }}</span></div>
              <div class="card-body p6">
                <div v-if="!phaseAuditeurs.length" class="empty-s"><i class="ti ti-user-off"></i> Aucun</div>
                <div v-for="aud in phaseAuditeurs" :key="aud.id" class="aud-row">
                  <div class="aud-av" :class="`av-${aud.role_code}`">{{ aud.initials }}</div>
                  <div class="aud-inf">
                    <span class="aud-nm">{{ aud.full_name }}</span>
                    <span class="aud-cd">{{ aud.audit_code }}</span>
                  </div>
                  <span class="ar-chip" :class="`chip-role-${aud.role_code}`">{{ aud.role_code }}</span>
                </div>
              </div>
            </section>

            <!-- Formulaire meta (DM/CM) -->
            <section v-if="canManage" class="card">
              <div class="card-lbl"><i class="ti ti-table"></i> Formulaire AR</div>
              <div class="card-body">
                <div class="fg"><span class="flbl">Code</span><input class="inp inp-ro" :value="form.code||'AR-AUTO'" readonly/></div>
                <div class="form-r2">
                  <div class="fg"><span class="flbl">Fait par</span><input class="inp" v-model="form.fait_par" :disabled="isLocked"/></div>
                  <div class="fg"><span class="flbl">Revu par</span><input class="inp" v-model="form.revue_par" :disabled="isLocked"/></div>
                </div>
                <div class="fg"><span class="flbl">Synthèse</span>
                  <textarea class="inp inp-ta" v-model="synthese" :disabled="isLocked" rows="3" placeholder="Synthèse générale…"></textarea>
                </div>
              </div>
            </section>

            <!-- AFFECTATION DES PROCESSUS (DM/CM) -->
            <section v-if="canManage && !isLocked" class="card card-assign">
              <div class="card-lbl"><i class="ti ti-user-plus"></i> Affecter les processus <span class="card-cnt">{{ uniqueProcesses.length }}</span></div>
              <div class="card-body p6">
                <p class="assign-hint">
                  Associez chaque processus à un auditeur.<br>
                  L'auditeur assigné peut ensuite tout faire sur ses processus.
                </p>
                <div v-if="!phaseAuditeurs.length" class="empty-s"><i class="ti ti-user-off"></i> Aucun auditeur dans la phase</div>
                <div v-if="!uniqueProcesses.length" class="empty-s"><i class="ti ti-shield-off"></i> Aucun processus chargé</div>

                <div v-for="proc in uniqueProcesses" :key="proc.code" class="assign-item">
                  <div class="assign-proc-hd">
                    <code class="assign-proc-code">{{ proc.code }}</code>
                    <span class="assign-proc-name">{{ proc.name }}</span>
                  </div>
                  <select class="assign-sel"
                          :value="String(processAssignments[proc.code] ?? '')"
                          @change="assignProcess(proc.code, ($event.target as HTMLSelectElement).value)">
                    <option value="">— Non affecté —</option>
                    <option v-for="aud in phaseAuditeurs" :key="aud.id" :value="String(aud.id)">
                      {{ aud.role_code }} · {{ aud.full_name }}
                    </option>
                  </select>
                  <div v-if="getProcessAssignee(proc.code)" class="assign-badge-row">
                    <div class="aud-av aud-av-sm" :class="`av-${getProcessAssignee(proc.code)?.role_code}`">{{ getProcessAssignee(proc.code)?.initials }}</div>
                    <span class="assign-aud-name">{{ getProcessAssignee(proc.code)?.full_name }}</span>
                    <button class="ibtn ibtn-del" @click="assignProcess(proc.code, '')" title="Désaffecter"><i class="ti ti-x"></i></button>
                  </div>
                  <div v-else class="assign-unset"><i class="ti ti-user-off"></i> Non affecté</div>
                </div>

                <button class="btn btn-save btn-xs btn-full" style="margin-top:10px"
                        @click="saveAssignments" :disabled="saving">
                  <span v-if="saving" class="spin-s"></span>
                  <i v-else class="ti ti-device-floppy"></i>
                  {{ saving ? 'Enregistrement…' : 'Enregistrer les affectations' }}
                </button>

                <transition name="slide-down">
                  <div v-if="showAssignLog && assignLog.length" class="assign-log">
                    <div class="assign-log-hd">
                      <i class="ti ti-list-check"></i> Résultat de l'enregistrement
                      <button class="ibtn" style="margin-left:auto" @click="showAssignLog=false"><i class="ti ti-x"></i></button>
                    </div>
                    <div v-for="(line, li) in assignLog" :key="li"
                         class="assign-log-line"
                         :class="line.startsWith('✓')?'log-ok':line.startsWith('✗')?'log-err':'log-info'">
                      {{ line }}
                    </div>
                  </div>
                </transition>
              </div>
            </section>

            <!-- Info affectation pour AS/AJ -->
            <section v-if="!canManage" class="card">
              <div class="card-lbl"><i class="ti ti-info-circle"></i> Vos affectations</div>
              <div class="card-body p6">
                <div v-if="!myAssignedProcesses.length" class="empty-s">
                  <i class="ti ti-user-off"></i> Aucun processus assigné
                </div>
                <div v-for="proc in myAssignedProcesses" :key="proc.code" class="my-proc-row">
                  <i class="ti ti-check-circle" style="color:#059669"></i>
                  <div>
                    <code class="assign-proc-code">{{ proc.code }}</code>
                    <span class="assign-proc-name">{{ proc.name }}</span>
                  </div>
                </div>
                <div v-if="!myAssignedProcesses.length" class="assign-hint" style="margin-top:4px">
                  Le DM/CM doit vous affecter des processus pour vous permettre de saisir l'analyse.
                </div>
              </div>
            </section>

            <!-- AR enregistrés -->
            <section class="card">
              <div class="card-lbl"><i class="ti ti-list"></i> AR enregistrés</div>
              <div class="card-body p0">
                <table class="stbl">
                  <thead><tr><th>Code</th><th>Statut</th></tr></thead>
                  <tbody>
                    <tr v-if="!(arList as any[]).length"><td colspan="2" class="td-empty">Aucun AR</td></tr>
                    <tr v-for="a in (arList as any[])" :key="a.id" class="stbl-row" @click="loadAr(a)">
                      <td class="td-code">{{ a.code }}</td>
                      <td><span class="ar-chip" :class="`chip-${a.validation_status||'draft'}`">{{ vstLbl(a.validation_status||'draft') }}</span></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </section>

            <!-- Légende -->
            <section class="card">
              <div class="card-lbl"><i class="ti ti-info-circle"></i> Légende</div>
              <div class="card-body">
                <div class="lg-row">
                  <span class="lg-b" style="background:#d1fae5;color:#065f46">U</span>
                  <div><b>Univers</b><p>Valeur pré-remplie depuis l'univers d'audit</p></div>
                </div>
                <div class="lg-row">
                  <span class="lg-b" style="background:#f0fdf4;color:#059669;border:1px solid #a7f3d0">✓</span>
                  <div><b>Mes processus</b><p>Vous pouvez modifier ces lignes</p></div>
                </div>
                <div class="lg-row">
                  <span class="lg-b" style="background:#f3f4f6;color:#9ca3af">🔒</span>
                  <div><b>Non affecté</b><p>Lecture seule pour AS/AJ</p></div>
                </div>
              </div>
            </section>

          </aside>

          <!-- ════ CONTENU PRINCIPAL ════ -->
          <div class="ar-main">

            <div v-if="!allRisks.length" class="ar-empty">
              <i class="ti ti-shield-off"></i>
              <strong>Aucun risque pour l'univers {{ props.activeYear }}</strong>
              <p>Créez des risques dans l'<strong>Univers d'Audit</strong> pour l'entité <strong>{{ mission?.entity_name ?? '—' }}</strong>.</p>
            </div>

            <template v-else>
              <!-- Barre info -->
              <div class="univ-bar">
                <i class="ti ti-universe"></i>
                <span><strong>{{ allRisks.length }}</strong> risque(s) · <strong>{{ evaluatedCount }}</strong> évalués dans l'univers {{ props.activeYear }}</span>
                <span v-if="evaluatedCount>0" class="univ-ok"><i class="ti ti-circle-check"></i> Impact/Fréquence pré-remplis</span>
                <span v-if="!canManage&&myAssignedProcesses.length" class="univ-mine">
                  <i class="ti ti-check-circle"></i> {{ myAssignedProcesses.length }} processus assigné(s)
                </span>
                <span v-if="!canManage&&!myAssignedProcesses.length" class="univ-warn">
                  <i class="ti ti-alert-triangle"></i> Aucun processus affecté — contacter le DM/CM
                </span>
              </div>

              <!-- Tableau -->
              <div class="ar-tbl-wrap">
                <div class="tbl-scroll">
                  <table class="ar-tbl">
                    <thead>
                      <tr>
                        <th class="th-stick th-proc" @click="sortBy('process_code')">Processus <i :class="sortIcon('process_code')"></i></th>
                        <th class="th-stick2 th-sproc">S.Processus</th>
                        <th class="th-code" @click="sortBy('code')">Code <i :class="sortIcon('code')"></i></th>
                        <th class="th-label" @click="sortBy('label')">Libellé du risque <i :class="sortIcon('label')"></i></th>
                        <th class="th-ctrl">Procédure Contrôle</th>
                        <th class="th-num">N.Imp<br><small>résid.</small></th>
                        <th class="th-num">N.Freq<br><small>résid.</small></th>
                        <th class="th-num">Glob<br><small>résid.</small></th>
                        <th class="th-nature">Nature</th>
                        <th class="th-qualif">Qualif.</th>
                        <th class="th-choix">Choix</th>
                       
                        <th class="th-forces">Forces</th>
                        <th class="th-filt"></th>
                        <th class="th-faib">Faiblesses</th>
                        <th class="th-filt"></th>
                        <th class="th-obj">Objectif contrôle</th>
                        <th class="th-asgn"><i class="ti ti-user-check"></i> Affecté à</th>
                      </tr>
                    </thead>
                    <tbody>
                      <template v-for="group in groupedRisks" :key="group.processCode">
                        <!-- Ligne groupe -->
                        <tr class="row-group" @click="toggleGroup(group.processCode)">
                          <td colspan="18" class="td-group">
                            <div class="group-hd">
                              <i :class="expandedGroups.has(group.processCode)?'ti ti-chevron-down':'ti ti-chevron-right'"></i>
                              <code class="group-code">{{ group.processCode }}</code>
                              <span class="group-name">{{ group.processName }}</span>
                              <span class="group-count">{{ group.risks.length }} risque(s)</span>
                              <span v-if="getProcessAssignee(group.processCode)" class="group-assignee" :class="`av-${getProcessAssignee(group.processCode)?.role_code}`">
                                <i class="ti ti-user-check"></i>
                                {{ getProcessAssignee(group.processCode)?.initials }}
                                <span>{{ getProcessAssignee(group.processCode)?.full_name }}</span>
                              </span>
                              <span v-else class="group-unassigned"><i class="ti ti-user-off"></i> Non affecté</span>
                              <button v-if="canManage&&!isLocked" class="btn-grp-assign" @click.stop="quickAssignProcess(group)" title="Affecter ce processus à un auditeur">
                                <i class="ti ti-user-plus"></i>
                              </button>
                              <button v-if="!isLocked&&canEditProcess(group.processCode)" class="btn-add-act" @click.stop="openAddActivity(group)">
                                <i class="ti ti-plus"></i> Activité / Risque
                              </button>
                            </div>
                          </td>
                        </tr>

                        <!-- Risques -->
                        <template v-if="expandedGroups.has(group.processCode)">
                          <tr v-for="risk in group.risks" :key="risk.id"
                              class="row-risk"
                              :class="{
                                'row-selected': riskForms[risk.id]?.choix,
                                'row-new':      risk._isNew,
                                'row-grayed':   !canManage && !canEditRisk(risk),
                              }">
                            <td class="td-stick td-proc">{{ risk.process_code }}</td>
                            <td class="td-stick2 td-sproc">
                              <div class="sproc-cell">
                                <span>{{ risk.activity_name||risk.activity_code }}</span>
                                <button v-if="canManage&&!isLocked" class="btn-add-risk-act"
                                        @click.stop="openAddRiskInActivity(group, risk.activity_id, risk.activity_name||risk.activity_code)"
                                        title="Ajouter un risque dans cette activité">
                                  <i class="ti ti-plus"></i>
                                </button>
                              </div>
                            </td>
                            <td class="td-code-c">
                              <code class="risk-code">{{ risk.code }}</code>
                              <span v-if="risk._isNew" class="badge-new">NEW</span>
                              <span v-else-if="risk.is_evaluated" class="badge-univ" title="Évalué dans l'univers"><i class="ti ti-universe"></i></span>
                              <span v-if="!canManage&&!canEditRisk(risk)" class="badge-lock" title="Non affecté"><i class="ti ti-lock"></i></span>
                            </td>
                            <td class="td-label-c">
                              <div class="risk-lbl-wrap">
                                <span class="rdot" :style="{background:colorOf(risk.risk_type_color)}" :title="risk.risk_type_label"></span>
                                <div class="risk-lbl-col">
                                  <span class="risk-lbl">{{ risk.label }}</span>
                                  <span v-if="risk.qualification_net" class="risk-qualif" :class="`qualif-${slugQualif(risk.qualification_net)}`">{{ risk.qualification_net }}</span>
                                </div>
                              </div>
                            </td>
                            <td class="td-ctrl-c">
                              <textarea v-if="!isLocked&&canEditRisk(risk)" class="c-ta" v-model="riskForms[risk.id].control_procedure" rows="2" placeholder="Procédure…"/>
                              <div v-else class="ro-txt">{{ riskForms[risk.id].control_procedure||'—' }}</div>
                            </td>
                            <td class="td-num-c">
                              <div class="lvl-wrap">
                                <div v-if="!isLocked&&canEditRisk(risk)" class="lvl-cell" :style="levelStyle(riskForms[risk.id].impact_net)">
                                  <select class="lv-sel" v-model.number="riskForms[risk.id].impact_net" @change="recomputeGlob(risk.id)">
                                    <option :value="null">—</option>
                                    <option v-for="lv in (props.impactLevels as any[])" :key="(lv as any).id" :value="Number((lv as any).level)">{{ (lv as any).level }}</option>
                                  </select>
                                </div>
                                <div v-else class="lvl-ro" :style="levelStyle(riskForms[risk.id].impact_net)">{{ riskForms[risk.id].impact_net??'—' }}</div>
                                <span v-if="risk.is_evaluated&&riskForms[risk.id].impact_net" class="src-u">U</span>
                              </div>
                            </td>
                            <td class="td-num-c">
                              <div class="lvl-wrap">
                                <div v-if="!isLocked&&canEditRisk(risk)" class="lvl-cell" :style="levelStyle(riskForms[risk.id].frequency_net)">
                                  <select class="lv-sel" v-model.number="riskForms[risk.id].frequency_net" @change="recomputeGlob(risk.id)">
                                    <option :value="null">—</option>
                                    <option v-for="lv in (props.frequencyLevels as any[])" :key="(lv as any).id" :value="Number((lv as any).level)">{{ (lv as any).level }}</option>
                                  </select>
                                </div>
                                <div v-else class="lvl-ro" :style="levelStyle(riskForms[risk.id].frequency_net)">{{ riskForms[risk.id].frequency_net??'—' }}</div>
                                <span v-if="risk.is_evaluated&&riskForms[risk.id].frequency_net" class="src-u">U</span>
                              </div>
                            </td>
                            <td class="td-num-c">
                              <div class="lvl-ro glob-cell" :style="globStyle(riskForms[risk.id].glob_resid)">{{ riskForms[risk.id].glob_resid??'—' }}</div>
                            </td>
                            <td class="td-nature-c">
                              <select v-if="!isLocked&&canEditRisk(risk)" class="c-sel-sm" v-model="riskForms[risk.id].nature">
                                <option value="">—</option>
                                <option v-for="n in natures" :key="n" :value="n">{{ n }}</option>
                              </select>
                              <span v-else class="nature-b" :class="`nb-${riskForms[risk.id].nature}`">{{ riskForms[risk.id].nature||'—' }}</span>
                            </td>
                            <td class="td-qualif-c">
                              <select v-if="!isLocked&&canEditRisk(risk)" class="c-sel-sm" v-model="riskForms[risk.id].qualif_controle">
                                <option value="">—</option>
                                <option v-for="q in qualifControlesList" :key="q" :value="q">{{ q }}</option>
                              </select>
                              <span v-else class="qc-b">{{ riskForms[risk.id].qualif_controle||'—' }}</span>
                            </td>
                            <td class="td-choix-c">
                              <label class="chk-wrap">
                                <input type="checkbox" v-model="riskForms[risk.id].choix" :disabled="isLocked||!canEditRisk(risk)" class="hidden"/>
                                <span class="chk-box" :class="{checked:riskForms[risk.id].choix}">
                                  <i v-if="riskForms[risk.id].choix" class="ti ti-check"></i>
                                </span>
                              </label>
                            </td>
                          
                            <td class="td-ff-c">
                              <textarea v-if="!isLocked&&canEditRisk(risk)" class="c-ta c-ta-gr" v-model="riskForms[risk.id].forces" rows="2" placeholder="Forces…"/>
                              <div v-else class="ro-txt ro-gr">{{ riskForms[risk.id].forces||'—' }}</div>
                            </td>
                            <td class="td-dot"><span v-if="riskForms[risk.id].forces" class="dot dot-gr"></span></td>
                            <td class="td-ff-c">
                              <textarea v-if="!isLocked&&canEditRisk(risk)" class="c-ta c-ta-rd" v-model="riskForms[risk.id].faiblesses" rows="2" placeholder="Faiblesses…"/>
                              <div v-else class="ro-txt ro-rd">{{ riskForms[risk.id].faiblesses||'—' }}</div>
                            </td>
                            <td class="td-dot"><span v-if="riskForms[risk.id].faiblesses" class="dot dot-rd"></span></td>
                            <td class="td-obj-c">
                              <textarea v-if="!isLocked&&canEditRisk(risk)" class="c-ta" v-model="riskForms[risk.id].objectif_controle" rows="2" placeholder="Objectif…"/>
                              <div v-else class="ro-txt">{{ riskForms[risk.id].objectif_controle||'—' }}</div>
                            </td>
                            <td class="td-asgn-c">
                              <div v-if="getProcessAssignee(risk.process_code)" class="asgn-info">
                                <div class="aud-av aud-av-sm" :class="`av-${getProcessAssignee(risk.process_code)?.role_code}`">
                                  {{ getProcessAssignee(risk.process_code)?.initials }}
                                </div>
                                <span class="asgn-name">{{ getProcessAssignee(risk.process_code)?.full_name }}</span>
                              </div>
                              <span v-else class="asgn-empty">—</span>
                            </td>
                          </tr>
                        </template>
                      </template>
                      <tr v-if="!groupedRisks.length&&(searchQuery||activeFilterCount)">
                        <td colspan="18" class="td-no-res"><i class="ti ti-search-off"></i> Aucun résultat</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Ajouter processus (DM/CM) -->
              <div v-if="!isLocked&&canManage&&availableProcesses.length" class="add-proc-bar">
                <span class="add-proc-lbl"><i class="ti ti-plus-circle"></i> Ajouter un processus :</span>
                <div class="add-proc-pills">
                  <button v-for="p in availableProcesses" :key="(p as any).id" class="btn-add-proc" @click="openAddProcess(p)">
                    <code>{{ (p as any).code }}</code><span>{{ (p as any).name }}</span><i class="ti ti-plus"></i>
                  </button>
                </div>
              </div>

            </template>
          </div><!-- /ar-main -->
        </div><!-- /ar-layout -->

        <!-- Footer -->
        <footer class="ar-footer">
          <div>
            <button v-if="!isLocked" type="button" class="btn btn-ghost btn-sm" @click="annuler"><i class="ti ti-x"></i> Annuler</button>
            <button v-if="!isLocked" type="button" class="btn btn-save btn-sm" :disabled="processing" @click="submit">
              <span v-if="processing" class="spin-s"></span>
              <i v-else class="ti ti-device-floppy"></i>{{ form.id ? 'Mettre à jour' : 'Enregistrer' }}
            </button>
          </div>
          <div class="footer-mid"><span v-if="form.id" class="saved-code"><i class="ti ti-check"></i> {{ form.code }}</span></div>
          <div>
            <button v-if="ar.id&&ar.validation_status==='draft'" type="button" class="btn btn-sub btn-sm" :disabled="processing" @click="soumettre">
              <i class="ti ti-send"></i> Soumettre
            </button>
            <template v-if="canManage&&ar.validation_status==='in_review'">
              <button type="button" class="btn btn-ok btn-sm" :disabled="processing" @click="valider('validated')"><i class="ti ti-circle-check"></i> Valider</button>
              <button type="button" class="btn btn-rej btn-sm" :disabled="processing" @click="promptReject"><i class="ti ti-circle-x"></i> Rejeter</button>
            </template>
          </div>
        </footer>
      </div><!-- /ar-body -->
    </div>

    <!-- Modal Quick Assign -->
    <Teleport to="body">
      <transition name="mfade">
        <div v-if="quickAssign.open" class="modal-ov" @click.self="quickAssign.open=false">
          <div class="modal-box modal-sm">
            <div class="modal-hd">
              <div class="modal-hd-l"><i class="ti ti-user-plus"></i><span>Assigner le processus</span><code>{{ quickAssign.processCode }}</code></div>
              <button class="modal-cls" @click="quickAssign.open=false"><i class="ti ti-x"></i></button>
            </div>
            <div class="modal-body">
              <div class="modal-list">
                <div v-for="aud in phaseAuditeurs" :key="aud.id" class="modal-item"
                     :class="{selected:Number(processAssignments[quickAssign.processCode])===aud.id}"
                     @click="assignProcess(quickAssign.processCode,String(aud.id));quickAssign.open=false;showToast('success',`Processus affecté à ${aud.full_name}`)">
                  <div class="modal-item-ico aud-av" :class="`av-${aud.role_code}`">{{ aud.initials }}</div>
                  <div class="modal-item-info"><strong>{{ aud.full_name }}</strong><code>{{ aud.role_code }} · {{ aud.audit_code }}</code></div>
                  <i v-if="Number(processAssignments[quickAssign.processCode])===aud.id" class="ti ti-check modal-check"></i>
                </div>
                <div class="modal-item" @click="assignProcess(quickAssign.processCode,'');quickAssign.open=false">
                  <div class="modal-item-ico" style="background:#fef2f2;color:#dc2626"><i class="ti ti-user-off"></i></div>
                  <div class="modal-item-info"><strong>Désaffecter</strong><code>Retirer l'affectation</code></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- Modal Activité / Risque -->
    <Teleport to="body">
      <transition name="mfade">
        <div v-if="actModal.open" class="modal-ov" @click.self="closeActModal">
          <div class="modal-box modal-sm">

            <div class="modal-hd">
              <div class="modal-hd-l">
                <i :class="{
                  'ti ti-activity':       ['choose-activity','create-activity'].includes(actModal.mode),
                  'ti ti-alert-triangle': ['choose-risk','create-risk'].includes(actModal.mode),
                }"></i>
                <span>{{
                  actModal.mode === 'choose-activity' ? 'Choisir une activité' :
                  actModal.mode === 'create-activity' ? 'Créer une activité'   :
                  actModal.mode === 'choose-risk'     ? 'Choisir un risque'    :
                                                        'Créer un risque'
                }}</span>
                <code>{{ actModal.processCode }}</code>
                <span v-if="['choose-risk','create-risk'].includes(actModal.mode)" class="modal-sub">
                  {{ actModal.activityCode }} — {{ actModal.activityName }}
                </span>
              </div>
              <button class="modal-cls" @click="closeActModal"><i class="ti ti-x"></i></button>
            </div>

            <!-- Barre retour (modes risque) -->
            <div v-if="['choose-risk','create-risk'].includes(actModal.mode)" class="modal-back-bar">
              <button class="btn-modal-back" @click="actModal.mode='choose-activity';actModal.search='';actModal.selectedId=null">
                <i class="ti ti-arrow-left"></i> Changer d'activité
              </button>
              <span class="modal-act-badge">
                <i class="ti ti-activity"></i> {{ actModal.activityCode }} · {{ actModal.activityName }}
              </span>
            </div>

            <div class="modal-body">

              <!-- MODE : Choisir activité -->
              <template v-if="actModal.mode==='choose-activity'">
                <p class="modal-hint">
                  Choisissez une activité existante du processus <strong>{{ actModal.processName }}</strong>,
                  ou créez-en une nouvelle.
                </p>
                <div class="modal-search-wrap">
                  <i class="ti ti-search modal-sico"></i>
                  <input v-model="actModal.search" class="modal-search" placeholder="Rechercher…"/>
                </div>
                <div class="modal-list">
                  <div v-if="!filteredModalActivities.length && !actModal.search" class="modal-empty">
                    <i class="ti ti-mood-empty"></i><span>Aucune activité — créez-en une ci-dessous</span>
                  </div>
                  <div v-for="act in filteredModalActivities" :key="act.id" class="modal-item"
                       :class="{selected:actModal.selectedId===act.id}" @click="actModal.selectedId=act.id">
                    <div class="modal-item-ico" :class="act._local?'modal-ico-new':''">
                      <i class="ti ti-activity"></i>
                    </div>
                    <div class="modal-item-info">
                      <strong>{{ act.name }}</strong>
                      <code>{{ act.code }}</code>
                    </div>
                    <span v-if="act._local" class="badge-new">LOCAL</span>
                    <i v-if="actModal.selectedId===act.id" class="ti ti-check modal-check"></i>
                  </div>
                </div>
                <button class="btn-create-new" @click="actModal.mode='create-activity';actModal.search=''">
                  <i class="ti ti-plus-circle"></i> Créer une nouvelle activité dans {{ actModal.processCode }}
                </button>
              </template>

              <!-- MODE : Créer activité -->
              <template v-else-if="actModal.mode==='create-activity'">
                <p class="modal-hint">
                  Créez une nouvelle activité dans le processus <strong>{{ actModal.processName }}</strong>.
                  Le code est généré automatiquement mais modifiable.
                </p>
                <div class="create-form">
                  <div class="create-field">
                    <label>Code activité *</label>
                    <div class="create-field-row">
                      <span class="create-prefix">{{ actModal.processCode }}-</span>
                      <input v-model="actModal.newActCode" class="inp create-inp"
                             placeholder="A03" @input="actModal.newActCode=actModal.newActCode.toUpperCase()"/>
                    </div>
                    <span class="create-preview">{{ actModal.newActCode }}</span>
                  </div>
                  <div class="create-field">
                    <label>Nom de l'activité *</label>
                    <input v-model="actModal.newActName" class="inp" placeholder="Ex: Gestion des congés"/>
                  </div>
                </div>
              </template>

              <!-- MODE : Choisir risque -->
              <template v-else-if="actModal.mode==='choose-risk'">
                <p class="modal-hint">
                  Choisissez un risque existant de l'univers d'audit ou créez-en un nouveau.
                </p>
                <div class="modal-search-wrap">
                  <i class="ti ti-search modal-sico"></i>
                  <input v-model="actModal.search" class="modal-search" placeholder="Rechercher un risque…"/>
                </div>
                <div class="modal-list">
                  <div v-if="!filteredModalRisks.length&&!actModal.search" class="modal-empty">
                    <i class="ti ti-shield-off"></i>
                    <span>Aucun risque univers disponible — créez-en un ci-dessous</span>
                  </div>
                  <div v-for="r in filteredModalRisks" :key="r.id" class="modal-item"
                       :class="{selected:actModal.selectedId===r.id}" @click="actModal.selectedId=r.id">
                    <div class="modal-item-ico" style="background:#fef3c7;color:#92400e">
                      <i class="ti ti-alert-triangle"></i>
                    </div>
                    <div class="modal-item-info">
                      <strong>{{ r.label }}</strong>
                      <code>{{ r.code }}</code>
                    </div>
                    <span v-if="r.is_evaluated" class="badge-univ" title="Évalué"><i class="ti ti-universe"></i></span>
                    <i v-if="actModal.selectedId===r.id" class="ti ti-check modal-check"></i>
                  </div>
                </div>
                <button class="btn-create-new" @click="actModal.mode='create-risk';actModal.search=''">
                  <i class="ti ti-plus-circle"></i> Créer un nouveau risque dans {{ actModal.activityCode }}
                </button>
              </template>

              <!-- MODE : Créer risque -->
              <template v-else-if="actModal.mode==='create-risk'">
                <p class="modal-hint">
                  Créez un nouveau risque dans l'activité <strong>{{ actModal.activityCode }}</strong>.
                </p>
                <div class="create-form">
                  <div class="create-field">
                    <label>Code risque *</label>
                    <input v-model="actModal.newRiskCode" class="inp"
                           placeholder="Ex: P02R-A03-R01"
                           @input="actModal.newRiskCode=actModal.newRiskCode.toUpperCase()"/>
                    <span class="create-hint">Format suggéré : {{ actModal.activityCode }}-Rxx</span>
                  </div>
                  <div class="create-field">
                    <label>Libellé du risque *</label>
                    <input v-model="actModal.newRiskLabel" class="inp" placeholder="Ex: Risque de fraude interne"/>
                  </div>
                </div>
              </template>

            </div><!-- /modal-body -->

            <div class="modal-ft">
              <button class="btn btn-ghost btn-sm" @click="closeActModal">Annuler</button>
              <button class="btn btn-save btn-sm" @click="confirmModal"
                      :disabled="
                        (actModal.mode==='choose-activity' && !actModal.selectedId) ||
                        (actModal.mode==='create-activity' && (!actModal.newActCode.trim()||!actModal.newActName.trim())) ||
                        (actModal.mode==='choose-risk'     && !actModal.selectedId) ||
                        (actModal.mode==='create-risk'     && (!actModal.newRiskCode.trim()||!actModal.newRiskLabel.trim()))
                      ">
                <i class="ti ti-plus"></i>
                {{
                  actModal.mode === 'choose-activity' ? 'Sélectionner'          :
                  actModal.mode === 'create-activity' ? "Créer l'activité →"    :
                  actModal.mode === 'choose-risk'     ? 'Ajouter ce risque'     :
                                                        'Créer le risque'
                }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- Modal Ajouter processus -->
    <Teleport to="body">
      <transition name="mfade">
        <div v-if="procModal.open" class="modal-ov" @click.self="closeProcModal">
          <div class="modal-box modal-sm">
            <div class="modal-hd">
              <div class="modal-hd-l"><i class="ti ti-box"></i><span>Ajouter des risques</span><code>{{ procModal.process?.code }}</code></div>
              <button class="modal-cls" @click="closeProcModal"><i class="ti ti-x"></i></button>
            </div>
            <div class="modal-body">
              <div class="modal-list">
                <div v-if="!procModalRisks.length" class="modal-empty"><i class="ti ti-shield-off"></i><span>Aucun risque</span></div>
                <div v-for="r in procModalRisks" :key="r.id" class="modal-item"
                     :class="{selected:procModal.selectedIds.includes(r.id)}" @click="toggleProcRisk(r.id)">
                  <div class="modal-item-ico"><i class="ti ti-alert-triangle"></i></div>
                  <div class="modal-item-info"><strong>{{ r.label }}</strong><code>{{ r.code }}</code></div>
                  <i v-if="procModal.selectedIds.includes(r.id)" class="ti ti-check modal-check"></i>
                </div>
              </div>
            </div>
            <div class="modal-ft">
              <button class="btn btn-ghost btn-sm" @click="closeProcModal">Annuler</button>
              <button class="btn btn-save btn-sm" @click="confirmAddProcess" :disabled="!procModal.selectedIds.length">
                <i class="ti ti-plus"></i> Ajouter{{ procModal.selectedIds.length ? ` (${procModal.selectedIds.length})` : '' }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- Toast -->
    <Teleport to="body">
      <transition name="toast-t">
        <div v-if="toast.show" class="toast" :class="`toast--${toast.type}`">
          <i :class="toast.type==='success'?'ti ti-circle-check':'ti ti-alert-circle'"></i>{{ toast.msg }}
        </div>
      </transition>
    </Teleport>

  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  mission:                { type: Object, default: null },
  assignment:             { type: Object, default: null },
  form:                   { type: Object, default: null },
  arList:                 { type: Array,  default: () => [] },
  risksData:              { type: Array,  default: () => [] },
  allProcesses:           { type: Array,  default: () => [] },
  allActivities:          { type: Array,  default: () => [] },
  assignmentFunctions:    { type: Array,  default: () => [] },
  impactLevels:           { type: Array,  default: () => [] },
  frequencyLevels:        { type: Array,  default: () => [] },
  riskTypes:              { type: Array,  default: () => [] },
  matrix:                 { type: Array,  default: () => [] },
  auditorRole:            { type: String, default: null },
  missionId:              { type: Number, default: null },
  assignmentId:           { type: Number, default: null },
  currentAuditor:         { type: Object, default: null },
  phaseAuditeurs:         { type: Array,  default: () => [] },
  processAssignmentsData: { type: Object, default: () => ({}) },
  riskCount:              { type: Number, default: 0 },
  activeYear:             { type: Number, default: null },
  backUrl:                { type: String, default: '' },
  formUrl:                { type: String, default: '' },
  urlStore:               { type: String, default: '' },
  urlUpdate:              { type: String, default: '' },
  urlSoumettre:           { type: String, default: '' },
  urlValider:             { type: String, default: '' },
  urlAssignProcess:       { type: String, default: '' },
})

// ── State ──────────────────────────────────────────────────────────────────
const processing     = ref(false)
const saving         = ref(false)
const showFilters    = ref(false)
const assignLog      = ref<string[]>([])
const showAssignLog  = ref(false)
const searchQuery    = ref('')
const filterProcess  = ref('')
const filterNature   = ref('')
const filterQualif   = ref('')
const filterChoix    = ref('')
const filterEvalue   = ref('')
const filterAssign   = ref('')
const sortKey        = ref('process_code')
const sortDir        = ref<'asc'|'desc'>('asc')
const synthese       = ref(props.form?.synthese ?? '')
const expandedGroups = ref<Set<string>>(new Set())
const extraRisks     = ref<any[]>([])

const processAssignments = reactive<Record<string, number|null>>(
  Object.fromEntries(
    Object.entries(props.processAssignmentsData as Record<string,number>)
      .map(([k,v]) => [k, v ? Number(v) : null])
  )
)

const ar   = reactive<Record<string,any>>(props.form ? {...props.form} : {})
const form = reactive({
  id:        props.form?.id        ?? null,
  code:      props.form?.code      ?? '',
  fait_par:  props.form?.fait_par  ?? auditorFullName(),
  revue_par: props.form?.revue_par ?? '',
})

// ── Constantes ─────────────────────────────────────────────────────────────
const natures = ['RM','RF','RO','RC','RS']
const qualifControlesList = computed(() => {
  const q = new Set<string>(['QC 1','QC 2','QC 3','QC 4','QC 5','QC 6','QC 7','QC 8','QC 9','QC 10','QC 11'])
  allRisks.value.forEach(r => { if (riskForms[r.id]?.qualif_controle) q.add(riskForms[r.id].qualif_controle) })
  return Array.from(q).sort()
})
function auditorFullName() {
  const a = props.currentAuditor as any
  return a ? [a.last_name,a.first_name].filter(Boolean).join(' ') : ''
}

// ── Droits ─────────────────────────────────────────────────────────────────
const canManage = computed(() => ['DM','CM'].includes(props.auditorRole ?? ''))
const isLocked  = computed(() =>
  ar.validation_status === 'validated' ||
  (ar.validation_status === 'in_review' && !canManage.value)
)

function canEditRisk(risk: any): boolean {
  if (isLocked.value) return false
  if (canManage.value) return false
  const assignedId = processAssignments[risk.process_code]
  if (!assignedId) return false
  return Number(assignedId) === Number((props.currentAuditor as any)?.id ?? 0)
}
function canEditProcess(processCode: string): boolean {
  if (isLocked.value) return false
  if (canManage.value) return false
  const assignedId = processAssignments[processCode]
  if (!assignedId) return false
  return Number(assignedId) === Number((props.currentAuditor as any)?.id ?? 0)
}

function getProcessAssignee(processCode: string): any|null {
  const id = processAssignments[processCode]
  if (!id) return null
  return phaseAuditeurs.value.find(a => a.id === Number(id)) ?? null
}

const phaseAuditeurs = computed(() =>
  (props.phaseAuditeurs as any[]).map(a => ({
    id:         Number(a.id),
    audit_code: a.audit_code,
    full_name:  a.full_name ?? [a.last_name,a.first_name].filter(Boolean).join(' '),
    initials:   a.initials  ?? ((a.last_name?.[0]??'')+(a.first_name?.[0]??'')).toUpperCase(),
    role_code:  a.role_code ?? 'AS',
  }))
)

const myAssignedProcesses = computed(() => {
  const myId = Number((props.currentAuditor as any)?.id ?? 0)
  if (!myId || canManage.value) return []
  return uniqueProcesses.value.filter(p => Number(processAssignments[p.code]) === myId)
})

const evaluatedCount = computed(() => (props.risksData as any[]).filter(r => r.is_evaluated).length)

// ── Formulaires risque ─────────────────────────────────────────────────────
const riskForms = reactive<Record<number,any>>({})
function makeRiskForm(r: any) {
  const imp  = r.impact_net    != null ? Number(r.impact_net)    : null
  const freq = r.frequency_net != null ? Number(r.frequency_net) : null
  return {
    control_procedure: r.control_procedure ?? '',
    impact_net:        imp,
    frequency_net:     freq,
    glob_resid:        r.glob_resid != null ? Number(r.glob_resid) : computeGlob(imp,freq),
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
  for (const r of (props.risksData as any[])) riskForms[r.id] = makeRiskForm(r)
}
initRiskForms()

const allRisks = computed<any[]>(() => [...(props.risksData as any[]),...extraRisks.value])

function computeGlob(imp:any,freq:any):number|null {
  if(imp==null||freq==null) return null
  const i=Number(imp),f=Number(freq); if(!i||!f) return null
  const e=(props.matrix as any[]).find(m=>Number(m.frequency_level)===f&&Number(m.impact_level)===i)
  return e?Number(e.qualification):i*f
}
function recomputeGlob(riskId:number){ const f=riskForms[riskId]; f.glob_resid=computeGlob(f.impact_net,f.frequency_net) }

// ── Couleurs ───────────────────────────────────────────────────────────────
const LEVEL_COLORS:Record<number,string>={1:'#22c55e',2:'#a3e635',3:'#facc15',4:'#f97316',5:'#ef4444'}
function levelStyle(v:any){if(v==null)return{};const n=Number(v);if(!n)return{};return{background:LEVEL_COLORS[n]??'#94a3b8',color:'#fff'}}
function globStyle(g:any){if(g==null)return{};const n=Number(g);if(!n)return{};if(n>=16)return{background:'#991b1b',color:'#fff'};if(n>=9)return{background:'#ef4444',color:'#fff'};if(n>=4)return{background:'#f97316',color:'#fff'};if(n>=2)return{background:'#facc15',color:'#1a1a2e'};return{background:'#22c55e',color:'#fff'}}
function colorOf(c:string){const m:any={danger:'#dc3545',warning:'#ffc107',info:'#0dcaf0',success:'#28a745',secondary:'#6c757d',primary:'#0d6efd'};return m[c]??c??'#94a3b8'}
function slugQualif(q:string){return(q??'').toLowerCase().replace(/[^a-z]/g,'')}

// ── Filtres ────────────────────────────────────────────────────────────────
const activeFilterCount = computed(()=>
  [filterProcess.value,filterNature.value,filterQualif.value,filterChoix.value,filterEvalue.value,filterAssign.value].filter(Boolean).length
)
function resetFilters(){filterProcess.value=filterNature.value=filterQualif.value=filterChoix.value=filterEvalue.value=filterAssign.value=''}

const uniqueProcesses = computed(()=>{
  const map=new Map()
  for(const r of allRisks.value){if(!map.has(r.process_code))map.set(r.process_code,{code:r.process_code,name:r.process_name??r.process_code})}
  return Array.from(map.values())
})

const filteredRisks = computed(()=>{
  let risks=allRisks.value
  const q=searchQuery.value.toLowerCase()
  if(q) risks=risks.filter(r=>r.label?.toLowerCase().includes(q)||r.code?.toLowerCase().includes(q)||r.process_code?.toLowerCase().includes(q)||r.activity_name?.toLowerCase().includes(q))
  if(filterProcess.value) risks=risks.filter(r=>r.process_code===filterProcess.value)
  if(filterNature.value)  risks=risks.filter(r=>riskForms[r.id]?.nature===filterNature.value)
  if(filterQualif.value)  risks=risks.filter(r=>riskForms[r.id]?.qualif_controle===filterQualif.value)
  if(filterChoix.value==='selected')   risks=risks.filter(r=>riskForms[r.id]?.choix)
  if(filterChoix.value==='unselected') risks=risks.filter(r=>!riskForms[r.id]?.choix)
  if(filterEvalue.value==='yes') risks=risks.filter(r=>r.is_evaluated)
  if(filterEvalue.value==='no')  risks=risks.filter(r=>!r.is_evaluated)
  if(filterAssign.value==='mine')       risks=risks.filter(r=>canEditRisk(r))
  if(filterAssign.value==='assigned')   risks=risks.filter(r=>processAssignments[r.process_code])
  if(filterAssign.value==='unassigned') risks=risks.filter(r=>!processAssignments[r.process_code])
  return risks
})

const selectedCount = computed(()=>allRisks.value.filter(r=>riskForms[r.id]?.choix).length)
function sortBy(key:string){if(sortKey.value===key)sortDir.value=sortDir.value==='asc'?'desc':'asc';else{sortKey.value=key;sortDir.value='asc'}}
function sortIcon(key:string){if(sortKey.value!==key)return 'ti ti-arrows-sort th-sort-ico';return sortDir.value==='asc'?'ti ti-sort-ascending th-sort-ico active':'ti ti-sort-descending th-sort-ico active'}

const groupedRisks = computed(()=>{
  const sorted=[...filteredRisks.value].sort((a,b)=>{
    let av:any=(a as any)[sortKey.value]??(''as any),bv:any=(b as any)[sortKey.value]??(''as any)
    if(typeof av==='string')av=av.toLowerCase();if(typeof bv==='string')bv=bv.toLowerCase()
    return av<bv?(sortDir.value==='asc'?-1:1):av>bv?(sortDir.value==='asc'?1:-1):0
  })
  const groups=new Map<string,any>()
  for(const r of sorted){const k=r.process_code??'—';if(!groups.has(k))groups.set(k,{processCode:k,processName:r.process_name??k,risks:[]});groups.get(k)!.risks.push(r)}
  return Array.from(groups.values())
})

function toggleGroup(code:string){if(expandedGroups.value.has(code))expandedGroups.value.delete(code);else expandedGroups.value.add(code)}

const usedProcessCodes = computed(()=>new Set(allRisks.value.map(r=>r.process_code)))
const availableProcesses = computed(()=>(props.allProcesses as any[]).filter(p=>!usedProcessCodes.value.has(p.code)))

// ── Affectation DM/CM ──────────────────────────────────────────────────────
function assignProcess(code:string, audStr:string){
  processAssignments[code] = audStr && audStr!=='' ? Number(audStr) : null
}
const quickAssign=reactive({open:false,processCode:''})
function quickAssignProcess(group:any){quickAssign.processCode=group.processCode;quickAssign.open=true}

async function saveAssignments() {
  saving.value=true; assignLog.value=[]; showAssignLog.value=false
  try {
    const res = await apiFetch(props.urlAssignProcess,'POST',{
      ar_id:         form.id??null,
      mission_id:    props.missionId,
      assignment_id: props.assignmentId,
      assignments:   processAssignments,
    })
    if(!res.success) throw new Error(res.message??res.error??'Erreur')
    if(!form.id&&res.ar_id){form.id=res.ar_id;form.code=res.ar_code??'';if(res.form)Object.assign(ar,res.form)}
    const lines:string[]=[`✓ ${res.message}`]
    for(const [code,audId] of Object.entries(res.assignments??processAssignments)){
      const found=phaseAuditeurs.value.find(a=>a.id===Number(audId))
      lines.push(`  • ${code} → ${found?found.full_name+' ('+found.role_code+')':'Non affecté'}`)
    }
    assignLog.value=lines; showAssignLog.value=true
    setTimeout(()=>reloadEdit(),1500)
  } catch(e:any){
    assignLog.value=['✗ '+e.message]; showAssignLog.value=true
    showToast('error',e.message)
  } finally{saving.value=false}
}

// ── Modal Activité / Risque ────────────────────────────────────────────────
const actModal = reactive({
  open:        false,
  mode:        'choose-activity' as 'choose-activity'|'create-activity'|'choose-risk'|'create-risk',
  processId:   null as number|null,
  processCode: '',
  processName: '',
  activityId:   null as number|null,
  activityCode: '',
  activityName: '',
  search:      '',
  selectedId:  null as number|null,
  newActCode:  '',
  newActName:  '',
  newRiskCode: '',
  newRiskLabel:'',
})

function openAddActivity(group:any){
  const proc=(props.allProcesses as any[]).find(p=>p.code===group.processCode)
  Object.assign(actModal,{
    open:true, mode:'choose-activity',
    processId:proc?.id??null, processCode:group.processCode, processName:group.processName,
    activityId:null, activityCode:'', activityName:'',
    search:'', selectedId:null, newActCode:'', newActName:'', newRiskCode:'', newRiskLabel:'',
  })
  actModal.newActCode = genNextActivityCode(group.processCode)
}
function openAddRiskInActivity(group:any, actId:number, actName:string){
  const proc=(props.allProcesses as any[]).find(p=>p.code===group.processCode)
  Object.assign(actModal,{
    open:true, mode:'choose-risk',
    processId:proc?.id??null, processCode:group.processCode, processName:group.processName,
    activityId:actId, activityCode:actName, activityName:actName,
    search:'', selectedId:null, newRiskCode:'', newRiskLabel:'',
  })
  actModal.newRiskCode = genNextRiskCode(group.processCode, actName)
}
function closeActModal(){actModal.open=false}

function genNextActivityCode(processCode:string):string {
  const base = processCode.replace(/[^A-Z0-9]/gi,'').toUpperCase()
  const existing = [
    ...(props.allActivities as any[]).filter(a=>{const p=(props.allProcesses as any[]).find(x=>x.id===a.process_id);return p?.code===processCode}),
    ...extraRisks.value.filter(r=>r.process_code===processCode).map(r=>({code:r.activity_code})),
  ]
  const nums = existing.map(a=>{const m=a.code?.match(/A(\d+)$/i);return m?parseInt(m[1]):0}).filter(Boolean)
  const next = nums.length ? Math.max(...nums)+1 : 1
  return `${base}-A${String(next).padStart(2,'0')}`
}
function genNextRiskCode(processCode:string, activityCode:string):string {
  const base = activityCode.replace(/[^A-Z0-9\-]/gi,'').toUpperCase()
  const existing = [
    ...(props.risksData as any[]).filter(r=>r.activity_code===activityCode||r.activity_id===actModal.activityId),
    ...extraRisks.value.filter(r=>r.activity_code===activityCode||r.activity_id===actModal.activityId),
  ]
  const nums = existing.map(r=>{const m=r.code?.match(/R(\d+)$/i);return m?parseInt(m[1]):0}).filter(Boolean)
  const next = nums.length ? Math.max(...nums)+1 : 1
  return `${base}-R${String(next).padStart(2,'0')}`
}

const filteredModalActivities = computed(()=>{
  if(actModal.mode!=='choose-activity') return []
  let acts=(props.allActivities as any[]).filter(a=>a.process_id===actModal.processId)
  const localActs=extraRisks.value.filter(r=>r.process_code===actModal.processCode&&r._isNewActivity)
    .map(r=>({id:r.activity_id,code:r.activity_code,name:r.activity_name,_local:true}))
  const all=[...acts,...localActs]
  const s=actModal.search.toLowerCase()
  return s ? all.filter(a=>a.name?.toLowerCase().includes(s)||a.code?.toLowerCase().includes(s)) : all
})

const filteredModalRisks = computed(()=>{
  if(actModal.mode!=='choose-risk') return []
  const loadedIds=new Set(allRisks.value.map(r=>r.id))
  const risks=(props.risksData as any[]).filter(r=>r.activity_id===actModal.activityId&&!loadedIds.has(r.id))
  const s=actModal.search.toLowerCase()
  return s ? risks.filter(r=>r.label?.toLowerCase().includes(s)||r.code?.toLowerCase().includes(s)) : risks
})

function selectActivity(act:any){
  actModal.activityId=act.id; actModal.activityCode=act.code; actModal.activityName=act.name
  actModal.newRiskCode=genNextRiskCode(actModal.processCode, act.code)
  actModal.mode='choose-risk'; actModal.search=''; actModal.selectedId=null
}

function confirmModal(){
  if(actModal.mode==='choose-activity'){
    if(!actModal.selectedId) return
    const act=(props.allActivities as any[]).find(a=>a.id===actModal.selectedId)
    if(!act) return
    selectActivity(act)
  } else if(actModal.mode==='create-activity'){
    if(!actModal.newActCode.trim()||!actModal.newActName.trim()){showToast('error','Code et nom requis');return}
    const fakeId = -(Date.now())
    actModal.activityId=fakeId
    actModal.activityCode=actModal.newActCode.trim().toUpperCase()
    actModal.activityName=actModal.newActName.trim()
    actModal.newRiskCode=genNextRiskCode(actModal.processCode, actModal.activityCode)
    actModal.mode='choose-risk'; actModal.search=''; actModal.selectedId=null
  } else if(actModal.mode==='choose-risk'){
    if(!actModal.selectedId) return
    const risk=(props.risksData as any[]).find(r=>r.id===actModal.selectedId)
    if(!risk) return
    addRiskToAnalysis(risk,false)
    closeActModal()
  } else if(actModal.mode==='create-risk'){
    if(!actModal.newRiskCode.trim()||!actModal.newRiskLabel.trim()){showToast('error','Code et libellé requis');return}
    createNewRisk()
    closeActModal()
  }
}

function addRiskToAnalysis(risk:any, isNew:boolean){
  if(allRisks.value.find(x=>x.id===risk.id)) return
  const r={...risk,_isNew:isNew}
  extraRisks.value.push(r)
  riskForms[r.id]=makeRiskForm(r)
  expandedGroups.value.add(actModal.processCode)
  showToast('success',`"${risk.label}" ajouté`)
}

function createNewRisk(){
  const id=-(Date.now())
  const newRisk={
    id, code:actModal.newRiskCode.trim().toUpperCase(),
    label:actModal.newRiskLabel.trim(), description:'',
    process_id:actModal.processId, process_code:actModal.processCode, process_name:actModal.processName,
    activity_id:actModal.activityId, activity_code:actModal.activityCode, activity_name:actModal.activityName,
    risk_type_id:null,risk_type_label:'-',risk_type_color:'secondary',
    impact_level:null,impact_label:'-',impact_color:'secondary',
    frequency_level:null,frequency_label:'-',frequency_color:'secondary',
    criticality:null,control_procedure:'',status:'identified',
    is_evaluated:false,criticality_net:null,qualification_net:null,
    _isNew:true, _isNewActivity: actModal.activityId!=null && actModal.activityId<0,
  }
  extraRisks.value.push(newRisk)
  riskForms[id]=makeRiskForm(newRisk)
  expandedGroups.value.add(actModal.processCode)
  showToast('success',`Risque "${newRisk.code}" créé`)
}

// ── Modal processus ────────────────────────────────────────────────────────
const procModal=reactive({open:false,process:null as any,selectedIds:[] as number[]})
const procModalRisks=computed(()=>{
  if(!procModal.process) return []
  const usedIds=new Set(allRisks.value.map(r=>r.id))
  return (props.risksData as any[]).filter(r=>r.process_id===procModal.process?.id&&!usedIds.has(r.id))
})
function openAddProcess(proc:any){procModal.process=proc;procModal.selectedIds=[];procModal.open=true}
function closeProcModal(){procModal.open=false}
function toggleProcRisk(id:number){const i=procModal.selectedIds.indexOf(id);if(i>=0)procModal.selectedIds.splice(i,1);else procModal.selectedIds.push(id)}
function confirmAddProcess(){
  const toAdd=(props.risksData as any[]).filter(r=>procModal.selectedIds.includes(r.id))
  for(const r of toAdd){if(!allRisks.value.find(x=>x.id===r.id)){extraRisks.value.push({...r,_isNew:false});riskForms[r.id]=makeRiskForm(r)}}
  expandedGroups.value.add(procModal.process?.code??'')
  closeProcModal(); showToast('success',`${toAdd.length} risque(s) ajouté(s)`)
}

// ── Submit ─────────────────────────────────────────────────────────────────
function buildPayload(){
  return allRisks.value.map(r=>({
    risk_id:r.id,risk_code:r.code,process_code:r.process_code,activity_id:r.activity_id,
    activity_code:r.activity_code,activity_name:r.activity_name,
    ...(riskForms[r.id]??{}),_isNew:undefined,_isNewActivity:undefined,
  }))
}

async function submit(){
  if(isLocked.value) return
  processing.value=true
  const isNew=!form.id
  const url=isNew?props.urlStore:props.urlUpdate
  if(!url){showToast('error','URL manquante');processing.value=false;return}
  try{
    const d=await apiFetch(url,isNew?'POST':'PUT',{
      mission_id:props.missionId, assignment_id:props.assignmentId,
      fait_par:form.fait_par, revue_par:form.revue_par,
      synthese:synthese.value,
      risques:JSON.stringify(buildPayload()),
      process_assignments:JSON.stringify(processAssignments),
    })
    if(!d.success) throw new Error(d.message??d.error??'Erreur')
    if(d.form){Object.assign(ar,d.form);form.code=d.form.code??form.code}
    showToast('success',isNew?'Analyse créée.':'Analyse mise à jour.')
    if(isNew&&d.form?.id){form.id=d.form.id;reloadEdit(d.form.id)}
  }catch(e:any){showToast('error',e.message??'Erreur réseau')}
  finally{processing.value=false}
}

function reloadEdit(id?:number){
  const fid=id??form.id; if(!fid) return
  const base=props.urlUpdate?.replace(/\/[^/]+$/,'')?? props.formUrl
  router.visit(`${base}/${fid}/edit?mission_id=${props.missionId}&assignment_id=${props.assignmentId}`,{preserveScroll:true})
}
function annuler(){router.visit(props.backUrl)}
function loadAr(item:any){reloadEdit(item.id)}

async function soumettre(){
  if(!form.id){showToast('error',"Enregistrez d'abord.");return}
  if(!confirm("Soumettre l'analyse ?")) return
  const d=await apiFetch(props.urlSoumettre,'POST',{mission_id:props.missionId,assignment_id:props.assignmentId})
  if(d.success){ar.validation_status=d.status;showToast('success','Soumis')}
  else showToast('error',d.error??'Erreur')
}
async function valider(action:string,note?:string){
  const d=await apiFetch(props.urlValider,'POST',{mission_id:props.missionId,assignment_id:props.assignmentId,action,note})
  if(d.success){ar.validation_status=d.status;showToast('success',action==='validated'?'Validé ✓':'Rejeté')}
  else showToast('error',d.error??'Erreur')
}
function promptReject(){const n=prompt('Motif du rejet :');if(!n?.trim())return;valider('rejected',n)}

async function apiFetch(url:string,method:string,body:object={}):Promise<any>{
  const csrf=(document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content??''
  const res=await fetch(url,{method,headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},body:JSON.stringify(body)})
  return res.json()
}
const toast=ref({show:false,type:'success',msg:''})
let _tt:any
function showToast(t:string,m:string){if(_tt)clearTimeout(_tt);toast.value={show:true,type:t,msg:m};_tt=setTimeout(()=>{toast.value.show=false},3500)}
function vstLbl(s:string){return({draft:'Brouillon',in_review:'En attente',validated:'Validé ✓',rejected:'Rejeté'} as any)[s]??s}
function vstIcon(s:string){return({draft:'ti ti-pencil',in_review:'ti ti-clock',validated:'ti ti-circle-check',rejected:'ti ti-circle-x'} as any)[s]??'ti ti-circle'}

onMounted(()=>{for(const g of groupedRisks.value)expandedGroups.value.add(g.processCode)})
</script>

<style scoped>
*,*::before,*::after{box-sizing:border-box}
.ar-shell{display:flex;flex-direction:column;min-height:100vh;font-family:'Segoe UI',system-ui,sans-serif;background:#f0f4f8}
.ar-header{background:#fff;border-bottom:1px solid #e5e7eb;padding:12px 20px 0;position:sticky;top:0;z-index:50;box-shadow:0 1px 4px rgba(0,0,0,.05)}
.ar-hrow{display:flex;align-items:flex-start;gap:10px;padding-bottom:10px;flex-wrap:wrap}
.ar-back{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border:1px solid #e5e7eb;border-radius:7px;color:#6b7280;text-decoration:none;flex-shrink:0;transition:all .15s}
.ar-back:hover{background:#f3f4f6;color:#111827}
.ar-hinfo{flex:1;min-width:0}
.ar-chips{display:flex;align-items:center;gap:5px;flex-wrap:wrap;margin-bottom:3px}
.ar-code{font-size:.68rem;font-weight:700;background:#1e293b;color:#fff;padding:2px 7px;border-radius:4px;font-family:ui-monospace,monospace}
.ar-chip{display:inline-flex;align-items:center;gap:3px;font-size:.66rem;font-weight:600;padding:2px 7px;border-radius:9px;border:1px solid transparent}
.chip-draft{background:#f3f4f6;color:#6b7280;border-color:#e5e7eb}
.chip-in_review{background:#e3f2fd;color:#1565C0;border-color:rgba(21,101,192,.2)}
.chip-validated{background:#ecfdf5;color:#059669;border-color:#a7f3d0}
.chip-rejected{background:#fef2f2;color:#dc2626;border-color:#fecaca}
.chip-type{background:#ede9fe;color:#7c3aed;border-color:#c4b5fd}
.chip-year{background:#f0f9ff;color:#0284c7;border-color:#bae6fd}
.chip-role-DM{background:#f5f3ff;color:#7c3aed;border-color:#ddd6fe}
.chip-role-CM{background:#f0f9ff;color:#0284c7;border-color:#bae6fd}
.chip-role-AS{background:#f0fdf4;color:#059669;border-color:#a7f3d0}
.chip-role-AJ{background:#fffbeb;color:#d97706;border-color:#fde68a}
.ar-title{font-size:1rem;font-weight:800;color:#111827;margin:0 0 3px}
.ar-meta{display:flex;align-items:center;gap:12px;flex-wrap:wrap;font-size:.72rem;color:#6b7280}
.ar-meta span{display:flex;align-items:center;gap:3px}
.ar-hactions{display:flex;align-items:center;gap:7px;flex-shrink:0}
.ar-search-wrap{position:relative;display:flex;align-items:center}
.ar-sico{position:absolute;left:8px;color:#9ca3af;font-size:.78rem;pointer-events:none}
.ar-search{border:1px solid #e5e7eb;border-radius:7px;padding:5px 28px;font-size:.74rem;color:#374151;font-family:inherit;outline:none;width:180px;background:#f9fafb;transition:all .15s}
.ar-search:focus{border-color:#2563EB;background:#fff;width:220px}
.ar-sclear{position:absolute;right:6px;background:none;border:none;color:#9ca3af;cursor:pointer;font-size:.75rem;padding:2px}
.ar-filter-btn{display:flex;align-items:center;gap:4px;padding:5px 9px;border:1px solid #e5e7eb;border-radius:7px;background:#f9fafb;color:#6b7280;cursor:pointer;font-size:.78rem;font-family:inherit;transition:all .15s;position:relative}
.ar-filter-btn:hover,.ar-filter-btn.active{border-color:#2563EB;color:#2563EB;background:#eff6ff}
.filter-badge{position:absolute;top:-5px;right:-5px;background:#dc2626;color:#fff;font-size:.58rem;font-weight:700;width:16px;height:16px;border-radius:50%;display:flex;align-items:center;justify-content:center}
.ar-banner{display:flex;align-items:center;gap:7px;padding:6px 0;font-size:.76rem;font-weight:500;border-top:1px solid #f3f4f6}
.banner-lock{color:#059669}.banner-review{color:#1565C0}.banner-reject{color:#dc2626}
.ar-filters{display:flex;align-items:flex-end;gap:10px;padding:8px 0 10px;flex-wrap:wrap;border-top:1px solid #f3f4f6}
.fgrp{display:flex;flex-direction:column;gap:3px}
.fgrp label{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280}
.f-sel{border:1px solid #e5e7eb;border-radius:5px;padding:4px 8px;font-size:.72rem;color:#374151;font-family:inherit;background:#fff;outline:none;cursor:pointer}
.f-sel:focus{border-color:#2563EB}
.btn-reset-f{display:flex;align-items:center;gap:4px;padding:5px 10px;border:1px solid #e5e7eb;border-radius:6px;background:#fff;color:#6b7280;font-size:.72rem;cursor:pointer;font-family:inherit;transition:all .13s}
.btn-reset-f:hover{background:#fef2f2;border-color:#fecaca;color:#dc2626}
.ar-body{flex:1;display:flex;flex-direction:column;overflow:hidden}
.ar-layout{display:grid;grid-template-columns:260px 1fr;flex:1;overflow:hidden;height:calc(100vh - 120px)}
.ar-sidebar{overflow-y:auto;border-right:1px solid #e5e7eb;background:#f9fafb;padding:10px;display:flex;flex-direction:column;gap:8px}
.ar-sidebar::-webkit-scrollbar{width:3px}
.ar-main{display:flex;flex-direction:column;overflow:hidden;background:#f9fafb;padding:10px;gap:8px;overflow-y:auto}
.ar-main::-webkit-scrollbar{width:3px}
.card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;flex-shrink:0}
.card-lbl{display:flex;align-items:center;gap:5px;font-size:.64rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;padding:7px 10px;background:#f9fafb;border-bottom:1px solid #e5e7eb}
.card-cnt{margin-left:auto;font-size:.6rem;font-weight:800;background:#e2e8f0;color:#64748b;padding:1px 5px;border-radius:6px}
.card-body{padding:8px 10px}
.card-assign{border-color:#a7f3d0}
.card-assign .card-lbl{background:#f0fdf4;color:#059669;border-bottom-color:#a7f3d0}
.p0{padding:0!important}.p6{padding:6px!important}
.fg{display:flex;flex-direction:column;gap:2px;margin-bottom:7px}
.fg:last-child{margin-bottom:0}
.flbl{font-size:.6rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.04em;display:block}
.form-r2{display:grid;grid-template-columns:1fr 1fr;gap:6px}
.inp{background:#fff;border:1px solid #e5e7eb;color:#111827;padding:5px 8px;border-radius:5px;font-size:.76rem;outline:none;transition:border-color .15s;font-family:inherit;width:100%}
.inp:focus{border-color:#2563EB;box-shadow:0 0 0 2px rgba(37,99,235,.1)}
.inp:disabled,.inp-ro{background:#f9fafb;color:#9ca3af;cursor:default}
.inp-ta{resize:vertical;min-height:52px}
.empty-s{display:flex;align-items:center;gap:5px;font-size:.7rem;color:#9ca3af;padding:4px}
.aud-row{display:flex;align-items:center;gap:6px;padding:5px 7px;border-radius:6px;border:1px solid #e9ecef;background:#fafafa;margin-bottom:3px}
.aud-av{width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.52rem;font-weight:800;flex-shrink:0;border:2px solid transparent}
.aud-av-sm{width:20px!important;height:20px!important;font-size:.48rem!important}
.av-DM{background:#f5f3ff;color:#7c3aed;border-color:#ddd6fe}.av-CM{background:#f0f9ff;color:#0284c7;border-color:#bae6fd}
.av-AS{background:#f0fdf4;color:#059669;border-color:#a7f3d0}.av-AJ{background:#fffbeb;color:#d97706;border-color:#fde68a}
.aud-inf{flex:1;min-width:0}
.aud-nm{font-size:.7rem;font-weight:600;color:#111827;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.aud-cd{font-size:.58rem;font-family:monospace;color:#9ca3af;display:block}
.stbl{width:100%;border-collapse:collapse;font-size:.72rem}
.stbl thead tr{background:#f9fafb}
.stbl th,.stbl td{padding:5px 9px;border-bottom:1px solid #f3f4f6;text-align:left}
.stbl th{font-size:.62rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.04em}
.stbl-row{cursor:pointer;transition:background .1s}.stbl-row:hover{background:#f9fafb}
.td-empty{text-align:center;color:#d1d5db;padding:10px;font-size:.7rem}
.td-code{font-family:ui-monospace,monospace;font-size:.68rem;color:#6b7280}
.lg-row{display:flex;align-items:flex-start;gap:8px;padding:5px 0;border-bottom:1px solid #f3f4f6}
.lg-row:last-child{border-bottom:none}
.lg-b{width:26px;height:18px;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:800;flex-shrink:0}
.lg-row b{font-size:.72rem;color:#374151;display:block}.lg-row p{font-size:.64rem;color:#9ca3af;margin:0}
.assign-hint{font-size:.68rem;color:#64748b;line-height:1.5;margin-bottom:8px;background:#f0fdf4;border:1px solid #d1fae5;border-radius:5px;padding:6px 8px}
.assign-item{border:1px solid #e5e7eb;border-radius:6px;padding:7px 8px;margin-bottom:6px;background:#fafafa}
.assign-proc-hd{display:flex;align-items:center;gap:5px;margin-bottom:5px}
.assign-proc-code{font-size:.62rem;font-weight:800;color:#1565C0;background:rgba(21,101,192,.08);padding:1px 5px;border-radius:3px;font-family:ui-monospace,monospace}
.assign-proc-name{font-size:.68rem;color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1}
.assign-sel{width:100%;font-size:.7rem;border:1px solid #d1d5db;border-radius:5px;padding:4px 6px;font-family:inherit;background:#fff;color:#374151;cursor:pointer;outline:none;margin-bottom:5px}
.assign-sel:focus{border-color:#2563EB}
.assign-badge-row{display:flex;align-items:center;gap:5px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:5px;padding:3px 7px}
.assign-aud-name{flex:1;font-size:.7rem;font-weight:600;color:#059669;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.assign-unset{font-size:.66rem;color:#9ca3af;display:flex;align-items:center;gap:4px;font-style:italic;padding:2px 0}
.assign-log{margin-top:8px;border:1px solid #e5e7eb;border-radius:6px;overflow:hidden;font-size:.68rem}
.assign-log-hd{display:flex;align-items:center;gap:5px;padding:5px 8px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-weight:600;color:#374151;font-size:.66rem}
.assign-log-line{padding:3px 10px;border-bottom:1px solid #f3f4f6;line-height:1.5}
.assign-log-line:last-child{border-bottom:none}
.log-ok{background:#f0fdf4;color:#059669;font-weight:600}
.log-err{background:#fef2f2;color:#dc2626;font-weight:600}
.log-info{background:#fff;color:#374151}
.my-proc-row{display:flex;align-items:center;gap:7px;padding:5px 7px;border-radius:6px;background:#f0fdf4;border:1px solid #a7f3d0;margin-bottom:4px}
.my-proc-row i{color:#059669;font-size:.8rem;flex-shrink:0}
.univ-bar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;background:#fff;border:1px solid rgba(21,101,192,.18);border-radius:8px;padding:7px 14px;font-size:.74rem;color:#1565C0;flex-shrink:0}
.univ-bar i{font-size:.9rem;flex-shrink:0}
.univ-ok{color:#059669;display:flex;align-items:center;gap:4px;font-size:.7rem;font-weight:600}
.univ-mine{color:#059669;display:flex;align-items:center;gap:4px;font-size:.7rem;font-weight:600;background:#f0fdf4;padding:2px 8px;border-radius:6px;border:1px solid #a7f3d0}
.univ-warn{color:#d97706;display:flex;align-items:center;gap:4px;font-size:.7rem;font-weight:600;background:#fffbeb;padding:2px 8px;border-radius:6px;border:1px solid #fde68a}
.ar-tbl-wrap{background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;flex:1}
.tbl-scroll{overflow:auto;max-height:calc(100vh - 280px);width:100%}
.ar-tbl{width:100%;border-collapse:collapse;font-size:.68rem;min-width:2100px}
.ar-tbl thead th{background:#1e293b;color:rgba(255,255,255,.92);font-size:.57rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:6px 7px;border:none;white-space:nowrap;position:sticky;top:0;z-index:10;cursor:pointer;user-select:none;vertical-align:bottom}
.ar-tbl thead th:hover{background:#111827}
.th-stick{position:sticky!important;left:0;z-index:11!important;background:#111827!important;min-width:80px!important;cursor:default!important}
.th-stick2{position:sticky!important;left:80px;z-index:11!important;background:#111827!important;min-width:110px!important;cursor:default!important}
.th-code{min-width:110px}.th-label{min-width:200px}.th-ctrl{min-width:160px}
.th-num{min-width:66px;text-align:center}.th-nature{min-width:58px}.th-qualif{min-width:85px}
.th-choix{min-width:48px;text-align:center}.th-assert{min-width:150px}
.th-forces,.th-faib{min-width:140px}.th-obj{min-width:150px}
.th-filt{min-width:26px;padding:3px!important}.th-asgn{min-width:130px;cursor:default!important}
.ar-tbl tbody td{padding:3px 6px;border:1px solid #e9ecef;vertical-align:top}
.row-group{cursor:pointer;background:#1e293b}
.row-group:hover td{background:#111827!important}
.td-group{background:#1e293b!important;padding:5px 10px!important}
.group-hd{display:flex;align-items:center;gap:7px;color:#fff;font-size:.72rem;font-weight:700;flex-wrap:wrap}
.group-hd i{font-size:.8rem;color:rgba(255,255,255,.7);flex-shrink:0}
.group-code{background:rgba(255,255,255,.12);padding:2px 7px;border-radius:4px;font-size:.66rem;color:#fff;font-family:monospace}
.group-name{flex:1;font-weight:600}
.group-count{font-size:.62rem;color:rgba(255,255,255,.6);background:rgba(255,255,255,.1);padding:1px 7px;border-radius:7px}
.group-assignee{display:inline-flex;align-items:center;gap:4px;font-size:.64rem;font-weight:600;padding:2px 8px;border-radius:7px;border:1.5px solid;white-space:nowrap}
.group-unassigned{display:inline-flex;align-items:center;gap:3px;font-size:.62rem;color:rgba(255,255,255,.45);font-style:italic}
.btn-grp-assign{width:22px;height:22px;border-radius:5px;border:1.5px solid rgba(255,255,255,.35);background:rgba(255,255,255,.08);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.76rem;padding:0;transition:all .12s;flex-shrink:0}
.btn-grp-assign:hover{background:rgba(52,211,153,.3);border-color:#34d399}
.btn-add-act{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:5px;border:1.5px solid rgba(255,255,255,.35);background:rgba(255,255,255,.08);color:#fff;font-size:.63rem;font-weight:700;cursor:pointer;font-family:inherit;transition:all .13s}
.btn-add-act:hover{background:rgba(255,255,255,.18)}
.row-risk{background:#fff;transition:background .1s}
.row-risk:hover td{background:#f8fbff}
.row-selected td{background:#f0fdf4}.row-selected:hover td{background:#dcfce7}
.row-new td{background:#fffbeb}.row-new:hover td{background:#fef3c7}
.row-grayed td{background:#f8fafc!important;opacity:.6}
.row-grayed .td-stick,.row-grayed .td-stick2{background:#f1f5f9!important}
.td-stick{position:sticky;left:0;z-index:2;background:inherit}
.td-stick2{position:sticky;left:80px;z-index:2;background:inherit}
.td-proc{font-size:.66rem;font-weight:700;color:#1565C0;white-space:nowrap;min-width:80px}
.td-sproc{font-size:.66rem;color:#374151;min-width:110px;padding:2px 4px!important}
.sproc-cell{display:flex;align-items:center;gap:3px;min-width:0}
.sproc-cell span{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.66rem}
.btn-add-risk-act{width:16px;height:16px;border-radius:3px;border:1px solid #bfdbfe;background:#eff6ff;color:#2563EB;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.6rem;padding:0;transition:all .12s;flex-shrink:0;opacity:.6}
.btn-add-risk-act:hover{opacity:1;background:#dbeafe;border-color:#2563EB}
.td-code-c{white-space:nowrap}
.risk-code{font-family:monospace;font-size:.66rem;font-weight:700;color:#1565C0;background:rgba(21,101,192,.08);padding:2px 5px;border-radius:3px;border:1px solid rgba(21,101,192,.18)}
.badge-new{display:inline-flex;margin-left:3px;font-size:.54rem;font-weight:800;padding:1px 4px;border-radius:3px;background:#fef3c7;color:#92400e;border:1px solid #fde68a;vertical-align:middle}
.badge-univ{display:inline-flex;align-items:center;justify-content:center;margin-left:3px;font-size:.58rem;width:14px;height:14px;border-radius:50%;background:rgba(21,101,192,.1);color:#1565C0;vertical-align:middle}
.badge-lock{display:inline-flex;align-items:center;justify-content:center;margin-left:3px;font-size:.58rem;width:14px;height:14px;border-radius:50%;background:#f3f4f6;color:#9ca3af;vertical-align:middle}
.risk-lbl-wrap{display:flex;align-items:flex-start;gap:4px}
.rdot{width:6px;height:6px;border-radius:50%;flex-shrink:0;margin-top:4px}
.risk-lbl-col{display:flex;flex-direction:column;gap:2px;min-width:0}
.risk-lbl{font-size:.68rem;color:#1a1a2e;line-height:1.4}
.risk-qualif{display:inline-flex;font-size:.58rem;font-weight:700;padding:1px 5px;border-radius:5px;width:fit-content}
.qualif-acceptable{background:#d1fae5;color:#065f46}.qualif-surveiller{background:#fef3c7;color:#92400e}.qualif-actionrequise{background:#fee2e2;color:#991b1b}.qualif-critique{background:#991b1b;color:#fff}
.td-num-c{text-align:center;padding:2px!important}
.lvl-wrap{display:flex;flex-direction:column;align-items:center;gap:1px}
.lvl-cell{border-radius:4px;display:flex;align-items:center;justify-content:center;min-height:28px;overflow:hidden;width:100%}
.lv-sel{background:transparent;border:none;color:inherit;font-size:.8rem;font-weight:700;cursor:pointer;text-align:center;width:100%;padding:2px;outline:none}
.lv-sel option{color:#1a1a2e;background:#fff}
.lvl-ro{border-radius:4px;display:flex;align-items:center;justify-content:center;min-height:24px;font-size:.8rem;font-weight:700;padding:2px 6px;width:100%}
.glob-cell{font-weight:800;font-size:.84rem;min-width:34px}
.src-u{font-size:.52rem;font-weight:800;color:rgba(21,101,192,.65);letter-spacing:.02em}
.td-nature-c{text-align:center}
.c-sel-sm{width:100%;border:1px solid #e5e7eb;border-radius:4px;padding:2px 4px;font-size:.68rem;color:#374151;font-family:inherit;background:rgba(255,255,255,.9);outline:none;cursor:pointer}
.c-sel-sm:focus{border-color:#2563EB}
.nature-b{display:inline-flex;padding:2px 6px;border-radius:6px;font-size:.64rem;font-weight:700}
.nb-RM{background:#dbeafe;color:#1e40af}.nb-RF{background:#fce7f3;color:#9d174d}
.nb-RO{background:#d1fae5;color:#065f46}.nb-RC{background:#fef3c7;color:#92400e}.nb-RS{background:#ede9fe;color:#5b21b6}
.qc-b{font-size:.68rem;font-weight:600;color:#374151}
.td-choix-c{text-align:center;padding:2px!important}
.chk-wrap{display:flex;align-items:center;justify-content:center;cursor:pointer}
.hidden{display:none}
.chk-box{width:18px;height:18px;border:2px solid #d1d5db;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:.72rem;transition:all .13s;background:#fff}
.chk-box.checked{background:#1565C0;border-color:#1565C0;color:#fff}
.c-ta{width:100%;border:1px solid #e5e7eb;border-radius:4px;padding:2px 5px;font-size:.68rem;color:#1a1a2e;font-family:inherit;resize:vertical;outline:none;min-height:32px;background:rgba(255,255,255,.8)}
.c-ta:focus{border-color:#2563EB;background:#fff}
.c-ta-gr{border-color:#bbf7d0}.c-ta-gr:focus{border-color:#059669}
.c-ta-rd{border-color:#fecdd3}.c-ta-rd:focus{border-color:#dc2626}
.ro-txt{font-size:.68rem;color:#374151;white-space:pre-wrap;line-height:1.4}
.ro-gr{color:#15803d}.ro-rd{color:#dc2626}
.td-dot{padding:2px!important;text-align:center;vertical-align:middle}
.dot{display:inline-block;width:6px;height:6px;border-radius:50%}
.dot-gr{background:#15803d}.dot-rd{background:#dc2626}
.td-no-res{text-align:center;padding:28px;color:#9ca3af;font-size:.76rem}
.td-asgn-c{text-align:center;padding:3px!important}
.asgn-info{display:flex;align-items:center;gap:4px;justify-content:center}
.asgn-name{font-size:.62rem;font-weight:600;color:#374151;max-width:80px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.asgn-empty{font-size:.66rem;color:#d1d5db}
.add-proc-bar{display:flex;align-items:flex-start;gap:10px;background:#fff;border:1.5px dashed #e5e7eb;border-radius:8px;padding:8px 12px;flex-wrap:wrap;flex-shrink:0}
.add-proc-lbl{display:inline-flex;align-items:center;gap:5px;font-size:.63rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#9ca3af;white-space:nowrap;flex-shrink:0;margin-top:3px}
.add-proc-pills{display:flex;flex-wrap:wrap;gap:5px;flex:1}
.btn-add-proc{display:inline-flex;align-items:center;gap:5px;padding:3px 10px 3px 8px;border-radius:12px;cursor:pointer;font-family:inherit;font-size:.68rem;transition:all .13s;background:#f9fafb;border:1.5px dashed #d1d5db;color:#6b7280}
.btn-add-proc:hover{background:#eff6ff;border-color:#2563EB;color:#2563EB;border-style:solid}
.btn-add-proc code{font-size:.64rem;font-weight:700;color:inherit}
.btn-add-proc span{max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ar-empty{display:flex;flex-direction:column;align-items:center;gap:10px;padding:60px;color:#9ca3af;text-align:center;background:#fff;border:1.5px dashed #e5e7eb;border-radius:8px}
.ar-empty i{font-size:2rem;opacity:.2}.ar-empty strong{color:#475569}.ar-empty p{font-size:.76rem;max-width:360px;line-height:1.6}
.btn{display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:6px;font-size:.78rem;font-weight:600;border:none;cursor:pointer;font-family:inherit;transition:all .15s;white-space:nowrap}
.btn-save{background:#1e293b;color:#fff}.btn-save:hover:not(:disabled){background:#0f172a}
.btn-ghost{background:#fff;color:#374151;border:1px solid #e5e7eb}.btn-ghost:hover:not(:disabled){background:#f9fafb}
.btn-sub{background:#eff6ff;color:#2563EB;border:1px solid #bfdbfe}.btn-sub:hover:not(:disabled){background:#dbeafe}
.btn-ok{background:#ecfdf5;color:#059669;border:1px solid #a7f3d0}.btn-ok:hover:not(:disabled){background:#d1fae5}
.btn-rej{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}.btn-rej:hover:not(:disabled){background:#fee2e2}
.btn-sm{padding:4px 9px;font-size:.74rem}.btn-xs{padding:2px 7px;font-size:.68rem}
.btn-full{width:100%;justify-content:center}
.btn:disabled{opacity:.45;cursor:not-allowed}
.ibtn{width:22px;height:22px;display:flex;align-items:center;justify-content:center;background:transparent;border:1px solid transparent;border-radius:4px;cursor:pointer;font-size:.74rem;color:#d1d5db;transition:all .12s;padding:0}
.ibtn-del:hover{color:#dc2626;border-color:#fecaca;background:#fef2f2}
.ar-footer{display:flex;align-items:center;justify-content:space-between;padding:9px 18px;background:#fff;border-top:1px solid #e5e7eb;flex-wrap:wrap;gap:6px;flex-shrink:0}
.ar-footer>div{display:flex;gap:6px;flex-wrap:wrap}
.footer-mid{flex:1;display:flex;justify-content:center}
.saved-code{font-size:.7rem;color:#059669;display:flex;align-items:center;gap:3px;font-weight:600}
.modal-ov{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:400;display:flex;align-items:center;justify-content:center;padding:20px}
.modal-box{background:#fff;border-radius:12px;box-shadow:0 8px 40px rgba(0,0,0,.22);width:100%;max-width:680px;max-height:88vh;display:flex;flex-direction:column;overflow:hidden}
.modal-sm{max-width:460px}
.modal-hd{display:flex;align-items:center;justify-content:space-between;padding:13px 16px;border-bottom:1px solid #e5e7eb;flex-shrink:0;gap:7px;flex-wrap:wrap}
.modal-hd-l{display:flex;align-items:center;gap:7px;font-size:.8rem;font-weight:700;color:#111827}
.modal-hd-l i{color:#2563EB;font-size:1rem}
.modal-cls{width:26px;height:26px;border:none;background:#f3f4f6;border-radius:6px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6b7280;transition:all .12s}
.modal-cls:hover{background:#fee2e2;color:#dc2626}
.modal-body{flex:1;overflow-y:auto;padding:12px 16px}
.modal-ft{display:flex;justify-content:flex-end;gap:7px;padding:10px 16px;border-top:1px solid #e5e7eb;flex-shrink:0}
.modal-search-wrap{position:relative;display:flex;align-items:center;margin-bottom:7px}
.modal-sico{position:absolute;left:9px;color:#9ca3af;font-size:.78rem;pointer-events:none}
.modal-search{width:100%;border:1px solid #e5e7eb;border-radius:7px;padding:6px 10px 6px 28px;font-size:.74rem;color:#374151;font-family:inherit;outline:none;background:#f9fafb}
.modal-search:focus{border-color:#2563EB;background:#fff}
.modal-list{border:1.5px solid #e5e7eb;border-radius:8px;overflow-y:auto;max-height:300px}
.modal-empty{display:flex;flex-direction:column;align-items:center;gap:7px;padding:22px;color:#9ca3af;font-size:.76rem;text-align:center}
.modal-empty i{font-size:1.4rem;opacity:.3}
.modal-item{display:flex;align-items:center;gap:9px;padding:8px 11px;cursor:pointer;border-bottom:1px solid #f8fafc;transition:background .1s}
.modal-item:last-child{border-bottom:none}
.modal-item:hover{background:#f5f3ff}
.modal-item.selected{background:#eff6ff}
.modal-item-ico{width:26px;height:26px;background:#eff6ff;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#2563EB;flex-shrink:0;font-size:.82rem}
.modal-item-info{flex:1;min-width:0}
.modal-item-info strong{display:block;font-size:.76rem;color:#111827}
.modal-item-info code{font-size:.66rem;color:#9ca3af}
.modal-check{color:#22c55e;font-size:.88rem;flex-shrink:0}
.modal-hint{font-size:.72rem;color:#6b7280;line-height:1.5;margin-bottom:10px;padding:6px 8px;background:#f9fafb;border-radius:5px;border:1px solid #e5e7eb}
.modal-back-bar{display:flex;align-items:center;gap:8px;margin-bottom:8px;padding-bottom:8px;border-bottom:1px solid #f3f4f6}
.btn-modal-back{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border:1px solid #e5e7eb;border-radius:5px;background:#f9fafb;color:#6b7280;font-size:.7rem;cursor:pointer;font-family:inherit;transition:all .12s}
.btn-modal-back:hover{background:#eff6ff;color:#2563EB;border-color:#bfdbfe}
.modal-act-badge{display:inline-flex;align-items:center;gap:4px;font-size:.7rem;font-weight:600;color:#2563EB;background:#eff6ff;padding:2px 8px;border-radius:6px;border:1px solid #bfdbfe}
.modal-sub{font-size:.7rem;color:#6b7280;font-weight:400}
.btn-create-new{width:100%;margin-top:8px;padding:8px;border:1.5px dashed #2563EB;border-radius:7px;background:#f8fbff;color:#2563EB;font-size:.74rem;font-weight:600;cursor:pointer;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:6px;transition:all .13s}
.btn-create-new:hover{background:#eff6ff;border-style:solid}
.create-form{display:flex;flex-direction:column;gap:10px;padding:4px 0}
.create-field{display:flex;flex-direction:column;gap:4px}
.create-field label{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280}
.create-field-row{display:flex;align-items:center;gap:0}
.create-prefix{font-size:.74rem;font-weight:700;color:#1565C0;background:#eff6ff;border:1px solid #bfdbfe;border-right:none;border-radius:5px 0 0 5px;padding:5px 8px;white-space:nowrap}
.create-inp{border-radius:0 5px 5px 0!important;flex:1}
.create-preview{font-size:.64rem;color:#6b7280;font-family:monospace;margin-top:2px}
.create-hint{font-size:.63rem;color:#9ca3af;font-style:italic}
.modal-ico-new{background:#ecfdf5!important;color:#059669!important}
.toast{position:fixed;top:16px;right:16px;z-index:9999;display:flex;align-items:center;gap:7px;padding:9px 14px;border-radius:8px;font-size:.76rem;font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.12);border:1px solid transparent}
.toast--success{background:#ecfdf5;color:#059669;border-color:#a7f3d0}
.toast--error{background:#fef2f2;color:#dc2626;border-color:#fecaca}
.toast-t-enter-active,.toast-t-leave-active{transition:all .22s}
.toast-t-enter-from,.toast-t-leave-to{opacity:0;transform:translateX(10px)}
.mfade-enter-active,.mfade-leave-active{transition:all .2s}
.mfade-enter-from,.mfade-leave-to{opacity:0}
.mfade-enter-from .modal-box,.mfade-leave-to .modal-box{transform:scale(.96) translateY(8px)}
.slide-down-enter-active,.slide-down-leave-active{transition:all .2s}
.slide-down-enter-from,.slide-down-leave-to{opacity:0;max-height:0;overflow:hidden}
.spin-s{width:10px;height:10px;border-radius:50%;border:2px solid currentColor;border-top-color:transparent;animation:spin .6s linear infinite;display:inline-block}
@keyframes spin{to{transform:rotate(360deg)}}
::-webkit-scrollbar{width:4px;height:4px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:#e2e8f0;border-radius:2px}
</style>