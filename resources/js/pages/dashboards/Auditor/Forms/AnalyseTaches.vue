<template>
  <VerticalLayoutAudit>
    <div class="at-shell">

      <!-- ══ HEADER ══════════════════════════════════════════════════════ -->
      <header class="at-header">
        <div class="at-hrow">
          <a :href="props.backUrl" class="at-back"><i class="ti ti-arrow-left"></i></a>
          <div class="at-hinfo">
            <div class="at-chips">
              <code class="at-code">{{ mission?.code_mission ?? '—' }}</code>
              <span class="at-chip" :class="`chip-${form.validation_status || 'draft'}`">
                <i :class="vstIcon(form.validation_status || 'draft')"></i>
                {{ vstLbl(form.validation_status || 'draft') }}
              </span>
              <span class="at-chip chip-type">AT</span>
              <span v-if="props.auditorRole" class="at-chip" :class="`chip-role-${props.auditorRole}`">{{ props.auditorRole }}</span>
            </div>
            <h1 class="at-title">Analyse des Tâches — Matrice RACI</h1>
            <div class="at-meta">
              <span v-if="assignment?.phase_label"><i class="ti ti-git-branch"></i>{{ assignment.phase_label }}</span>
              <span v-if="mission?.entity_name"><i class="ti ti-building"></i>{{ mission.entity_name }}</span>
              <span v-if="mission?.date_debut_fr"><i class="ti ti-calendar"></i>{{ mission.date_debut_fr }} — {{ mission.date_fin_fr }}</span>
              <span v-if="props.riskCount"><i class="ti ti-alert-triangle" style="color:#f59e0b"></i>{{ props.riskCount }} risques mission</span>
              <span v-if="props.phaseAuditeurs?.length"><i class="ti ti-users" style="color:#6366f1"></i>{{ props.phaseAuditeurs.length }} auditeurs</span>
            </div>
          </div>
          <div class="at-hactions">
            <button class="btn-chat" :class="{ unread: unreadCount > 0 }" @click="openChat">
              <i class="ti ti-message-circle"></i><span>Chat AT</span>
              <span v-if="unreadCount > 0" class="chat-badge">{{ unreadCount }}</span>
            </button>
          </div>
        </div>
        <div v-if="form.validation_status === 'validated'" class="at-banner banner-lock">
          <i class="ti ti-lock"></i> Analyse des tâches <strong>validée définitivement</strong> — lecture seule
        </div>
        <div v-else-if="form.validation_status === 'in_review'" class="at-banner banner-review">
          <i class="ti ti-clock"></i> Soumise pour validation — en attente DM
          <span v-if="canManage"> · Vous pouvez valider ou rejeter.</span>
        </div>
        <div v-else-if="form.validation_status === 'draft' && form.validation_note" class="at-banner banner-reject">
          <i class="ti ti-circle-x"></i> Rejetée — <em>{{ form.validation_note }}</em>
        </div>
      </header>

      <!-- ══ BODY ════════════════════════════════════════════════════════ -->
      <div class="at-body">
        <div class="at-grid">

          <!-- ── COLONNE GAUCHE ── -->
          <div class="at-col-left">

            <!-- Info mission -->
            <section class="card">
              <div class="card-label"><i class="ti ti-briefcase"></i> Mission</div>
              <div class="card-body">
                <div class="form-row">
                  <field label="Code"><input class="inp inp-ro" :value="mission?.code_mission" readonly /></field>
                  <field label="Phase"><input class="inp inp-ro" :value="assignment?.phase_label || assignment?.phase_code" readonly /></field>
                </div>
                <field label="Intitulé"><input class="inp inp-ro" :value="mission?.libelle" readonly /></field>
                <field label="Entité"><input class="inp inp-ro" :value="mission?.entity_name || '—'" readonly /></field>
              </div>
            </section>

            <!-- Auditeurs de la phase -->
            <section class="card">
              <div class="card-label">
                <i class="ti ti-users"></i> Auditeurs de la phase
                <span class="card-count">{{ props.phaseAuditeurs?.length ?? 0 }}</span>
              </div>
              <div class="card-body" style="padding:8px">
                <div v-if="!props.phaseAuditeurs?.length" class="fn-empty">
                  <i class="ti ti-user-off"></i> Aucun auditeur affecté
                </div>
                <div v-else class="auditeur-list">
                  <div v-for="aud in props.phaseAuditeurs" :key="aud.id" class="auditeur-item">
                    <div class="aud-avatar" :class="`av-${aud.role_code}`">{{ aud.initials }}</div>
                    <div class="aud-info">
                      <span class="aud-name">{{ aud.full_name }}</span>
                      <span class="aud-meta">
                        <span class="aud-code">{{ aud.audit_code }}</span>
                        <span class="aud-role-lbl">{{ aud.role_label }}</span>
                      </span>
                    </div>
                    <span class="aud-role-badge" :class="`chip-role-${aud.role_code}`">{{ aud.role_code }}</span>
                  </div>
                </div>
              </div>
            </section>

            <!-- Info formulaire -->
            <section class="card">
              <div class="card-label"><i class="ti ti-table"></i> Formulaire AT</div>
              <div class="card-body">
                <div class="form-row">
                  <field label="Code AT"><input class="inp inp-ro" :value="form.code || 'AT-AUTO'" readonly /></field>
                </div>
                <div class="form-row">
                  <field label="Fait par"><input class="inp" v-model="form.fait_par" :disabled="isLocked" /></field>
                  <field label="Date"><input type="date" class="inp" v-model="form.date_fait" :disabled="isLocked" /></field>
                </div>
                <div class="form-row">
                  <field label="Revu par"><input class="inp" v-model="form.revue_par" :disabled="isLocked" /></field>
                  <field label="Date revue"><input type="date" class="inp" v-model="form.date_revue" :disabled="isLocked" /></field>
                </div>
                <field label="Synthèse / Observations">
                  <textarea class="inp inp-ta" v-model="form.synthese" :disabled="isLocked" rows="3"
                    placeholder="Observations générales…"></textarea>
                </field>
              </div>
            </section>

            <!-- Légende RACI -->
            <section class="card">
              <div class="card-label"><i class="ti ti-info-circle"></i> Légende RACI</div>
              <div class="card-body">
                <div v-for="r in props.raciRoles" :key="r.id" class="raci-legend-row">
                  <span class="raci-badge" :class="`raci-${r.code}`">{{ r.code }}</span>
                  <div><strong>{{ r.label }}</strong><p>{{ r.description }}</p></div>
                </div>
                <div class="risk-legend">
                  <div class="risk-legend-title"><i class="ti ti-alert-triangle"></i> Risques</div>
                  <div class="risk-legend-row"><span class="risk-pill crit-high risk-mission">R</span><span>Risque lié à la mission</span></div>
                  <div class="risk-legend-row"><span class="risk-pill crit-low risk-native">R</span><span>Risque natif de l'activité</span></div>
                  <div class="risk-legend-row"><span class="legend-dot-yellow"></span><span>Activité liée à un risque mission</span></div>
                </div>
              </div>
            </section>

            <!-- Liste AT -->
            <section class="card">
              <div class="card-label"><i class="ti ti-list"></i> Analyses enregistrées</div>
              <div class="card-body" style="padding:0">
                <table class="tbl">
                  <thead><tr><th>Code</th><th>Fait par</th><th>Statut</th></tr></thead>
                  <tbody>
                    <tr v-if="!props.atList?.length"><td colspan="3" class="td-empty">Aucune analyse</td></tr>
                    <tr v-for="at in props.atList" :key="at.id" class="tbl-row">
                      <td class="td-code">{{ at.code }}</td>
                      <td>{{ at.fait_par || '—' }}</td>
                      <td><span class="at-chip" :class="`chip-${at.validation_status||'draft'}`">{{ vstLbl(at.validation_status||'draft') }}</span></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </section>

          </div><!-- /col-left -->

          <!-- ── COLONNE DROITE ── -->
          <div class="at-col-right">

            <!-- Barre ajout processus — DM/CM uniquement -->
            <div v-if="!isLocked && canManage" class="add-proc-bar">
              <i class="ti ti-git-branch" style="color:#6b7280"></i>
              <span>Ajouter un processus hors mission :</span>
              <select v-model="procToAdd" class="inp proc-sel">
                <option value="">— Sélectionner —</option>
                <option v-for="p in props.unlinkedProcesses" :key="p.id" :value="p.id">{{ p.code }} — {{ p.name }}</option>
              </select>
              <button class="btn btn-sm btn-add-proc" :disabled="!procToAdd" @click="addProcess">
                <i class="ti ti-plus"></i> Ajouter
              </button>
            </div>

            <!-- Bandeau info AS/AJ -->
            <div v-if="!canManage && !isLocked" class="at-info-bar">
              <i class="ti ti-info-circle"></i>
              Vous voyez tous les processus mais ne pouvez éditer que ceux qui vous sont assignés.
            </div>

            <div v-if="!activeProcesses.length" class="at-no-proc">
              <i class="ti ti-alert-circle"></i>
              <p>Aucun processus lié à cette mission.</p>
            </div>

            <!-- ── BLOCS PROCESSUS ──────────────────────────────────────── -->
            <div v-for="proc in activeProcesses" :key="proc.id" class="proc-block">

              <!-- En-tête processus -->
              <div class="proc-header" @click="toggleProc(proc.id)">
                <div class="proc-header-l">
                  <span class="proc-chev" :class="{'proc-chev--open': expandedProcs.has(proc.id)}">
                    <i class="ti ti-chevron-right"></i>
                  </span>
                  <span class="proc-ico"><i class="ti ti-git-branch"></i></span>
                  <div class="proc-info">
                    <span class="proc-code">{{ proc.code }}</span>
                    <span class="proc-name">{{ proc.name }}</span>
                  </div>
                  <div class="proc-badges">
                    <span v-if="proc.risk_count" class="risk-badge">
                      <i class="ti ti-alert-triangle"></i>{{ proc.risk_count }} risques
                    </span>
                    <!-- Fonctions du processus -->
                    <span class="fn-count-badge">
                      <i class="ti ti-layout-columns"></i>{{ (procFunctions[proc.id] ?? []).length }} fonctions
                    </span>
                    <!-- Auditeur assigné -->
                    <span
                      v-if="getAssignedAuditeur(proc.id)"
                      class="assigned-badge"
                      :class="`av-${getAssignedAuditeur(proc.id)?.role_code}`"
                    >
                      <i class="ti ti-user-check"></i>
                      {{ getAssignedAuditeur(proc.id)?.initials }}
                      <span class="assigned-name">{{ getAssignedAuditeur(proc.id)?.full_name }}</span>
                    </span>
                    <span v-else-if="!canManage" class="unassigned-badge">
                      <i class="ti ti-user-off"></i> Non assigné
                    </span>
                    <span v-if="!proc.is_linked" class="added-badge">+ Ajouté</span>
                    <span v-if="!canManage && !canEditProcess(proc.id)" class="readonly-badge">
                      <i class="ti ti-eye"></i> Lecture seule
                    </span>
                  </div>
                </div>
                <div class="proc-header-r" @click.stop>
                  <!-- Sélecteur affectation DM/CM -->
                  <div v-if="canManage && !isLocked" class="proc-assign-wrap">
                    <select
                      class="inp proc-assign-sel"
                      :value="localProcessAssignments[proc.id] ?? ''"
                      @change="assignProcessToAuditeur(proc.id, ($event.target as HTMLSelectElement).value)"
                    >
                      <option value="">— Assigner à —</option>
                      <option v-for="aud in props.phaseAuditeurs" :key="aud.id" :value="aud.id">
                        {{ aud.role_code }} · {{ aud.full_name }}
                      </option>
                    </select>
                  </div>
                  <button
                    v-if="!proc.is_linked && canManage && !isLocked"
                    class="ibtn ibtn--del"
                    @click="removeProcess(proc.id)"
                  ><i class="ti ti-x"></i></button>
                </div>
              </div>

              <!-- Corps du processus (expandé) -->
              <div v-if="expandedProcs.has(proc.id)" class="proc-body">

                <!-- ── Gestion des fonctions PAR PROCESSUS ─────────────── -->
                <div class="proc-fn-bar">
                  <div class="proc-fn-list">
                    <span class="proc-fn-label"><i class="ti ti-layout-columns"></i> Fonctions RACI :</span>
                    <div
                      v-for="fn in (procFunctions[proc.id] ?? [])"
                      :key="fn.id"
                      class="proc-fn-tag"
                    >
                      <span class="proc-fn-char">{{ fn.character || fn.name.charAt(0) }}</span>
                      <span class="proc-fn-name">{{ fn.name }}</span>
                      <button
                        v-if="!isLocked && canEditProcess(proc.id)"
                        class="proc-fn-del"
                        @click="removeProcFunction(proc.id, fn.id)"
                        title="Retirer cette fonction"
                      ><i class="ti ti-x"></i></button>
                    </div>
                    <div v-if="!(procFunctions[proc.id]?.length)" class="proc-fn-empty">
                      Aucune fonction — ajoutez-en ci-dessous
                    </div>
                  </div>
                  <!-- Ajout fonction pour CE processus -->
                  <div v-if="!isLocked && canEditProcess(proc.id)" class="proc-fn-add">
                    <select
                      v-model="fnToAddPerProc[proc.id]"
                      class="inp proc-fn-sel"
                    >
                      <option value="">+ Ajouter une fonction</option>
                      <option
                        v-for="fn in availableFunctionsForProc(proc.id)"
                        :key="fn.id"
                        :value="fn.id"
                      >{{ fn.character ? `[${fn.character}] ` : '' }}{{ fn.name }}</option>
                    </select>
                    <button
                      class="btn btn-sm btn-add-fn"
                      :disabled="!fnToAddPerProc[proc.id]"
                      @click="addProcFunction(proc.id)"
                    ><i class="ti ti-plus"></i></button>
                  </div>
                </div>

                <!-- ── Tableau RACI ─────────────────────────────────────── -->
                <div v-if="!proc.activities.length" class="raci-empty">
                  <i class="ti ti-table-off"></i> Aucune activité dans ce processus
                </div>

                <div v-else class="raci-table-container">
                  <table class="raci-table">
                    <thead>
                      <tr>
                        <th class="th-act">Activité</th>
                        <th class="th-risk">
                          Risques
                          <span class="th-risk-hint">
                            <span class="risk-dot dot-mission" title="Mission"></span>
                            <span class="risk-dot dot-native"  title="Natif"></span>
                          </span>
                        </th>
                        <!-- Colonnes = fonctions DE CE PROCESSUS -->
                        <th
                          v-for="fn in (procFunctions[proc.id] ?? [])"
                          :key="fn.id"
                          class="th-fn"
                          :title="fn.name"
                        >
                          <div class="fn-th-inner">
                            <span class="fn-th-char">{{ fn.character || fn.name.charAt(0) }}</span>
                            <span class="fn-th-name">{{ fn.name }}</span>
                          </div>
                        </th>
                        <th v-if="!(procFunctions[proc.id]?.length)" class="th-fn-empty">
                          ← Ajoutez des fonctions ci-dessus
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <template v-for="act in proc.activities" :key="act.id">
                        <tr
                          class="raci-row"
                          :class="{
                            'raci-row--linked':   act.linked_to_risk,
                            'raci-row--inactive': !act.linked_to_risk && !activatedActs[act.id],
                          }"
                        >
                          <!-- Activité -->
                          <td class="td-act">
                            <div class="act-cell">
                              <button
                                v-if="!act.linked_to_risk && !isLocked && canEditProcess(proc.id)"
                                class="act-toggle"
                                :class="{'act-toggle--on': activatedActs[act.id]}"
                                @click="toggleActivity(act.id)"
                              ><i :class="activatedActs[act.id] ? 'ti ti-toggle-right' : 'ti ti-toggle-left'"></i></button>
                              <i v-if="act.linked_to_risk" class="ti ti-alert-triangle act-risk-ico"></i>
                              <div class="act-info">
                                <span class="act-code">{{ act.code }}</span>
                                <span class="act-name">{{ act.name }}</span>
                              </div>
                            </div>
                          </td>

                          <!-- ══ Risques : code + nom ══ -->
                          <td class="td-risk" @click="toggleRiskDetail(act.id)">
                            <div v-if="act.risks?.length" class="risk-pills-wrap">
                              <div class="risk-pills">
                                <span
                                  v-for="r in act.risks"
                                  :key="r.id"
                                  class="risk-pill"
                                  :class="[critClass(r.crit), r.is_mission_risk ? 'risk-mission' : 'risk-native']"
                                  :title="`${r.code} — ${r.label}`"
                                >{{ r.code }}</span>
                              </div>
                              <!-- Noms des risques toujours visibles sous les pills -->
                              <div class="risk-names">
                                <span
                                  v-for="r in act.risks"
                                  :key="`n-${r.id}`"
                                  class="risk-name-item"
                                  :class="r.is_mission_risk ? 'risk-name-mission' : 'risk-name-native'"
                                >{{ r.label }}</span>
                              </div>
                            </div>
                            <span v-else class="no-risk">—</span>
                          </td>

                          <!-- Cellules RACI par fonction DU PROCESSUS -->
                          <td
                            v-for="fn in (procFunctions[proc.id] ?? [])"
                            :key="fn.id"
                            class="td-raci"
                            :class="{'td-raci--locked': isLocked || (!act.linked_to_risk && !activatedActs[act.id]) || !canEditProcess(proc.id)}"
                          >
                            <div v-if="act.linked_to_risk || activatedActs[act.id]" class="raci-cell">
                              <button
                                v-for="role in raciRoleCodes"
                                :key="role"
                                class="raci-btn"
                                :class="[`raci-btn-${role}`, {'raci-btn--active': getRaci(proc.id, act.id, fn.id) === role}]"
                                :disabled="isLocked || !canEditProcess(proc.id)"
                                @click="setRaci(proc.id, act.id, fn.id, role)"
                                :title="raciLabel(role)"
                              >{{ role }}</button>
                            </div>
                            <div v-else class="raci-cell-disabled">
                              <i class="ti ti-minus" style="color:#e5e7eb"></i>
                            </div>
                          </td>
                        </tr>

                        <!-- Détail risques expandé — avec nom complet -->
                        <tr v-if="expandedActs.has(act.id) && act.risks?.length" class="risk-detail-row">
                          <td :colspan="2 + (procFunctions[proc.id]?.length ?? 0)">
                            <div class="risk-detail-list">
                              <div v-for="r in act.risks" :key="r.id" class="risk-detail-item">
                                <span class="risk-detail-code" :class="[critClass(r.crit), r.is_mission_risk ? 'risk-mission' : 'risk-native']">
                                  {{ r.code }}
                                </span>
                                <span class="risk-detail-label">{{ r.label }}</span>
                                <span class="risk-detail-badge" :class="r.is_mission_risk ? 'badge-mission' : 'badge-native'">
                                  {{ r.is_mission_risk ? 'Mission' : 'Natif' }}
                                </span>
                                <span class="risk-detail-crit" :class="critClass(r.crit)">{{ r.crit || '—' }}</span>
                              </div>
                            </div>
                          </td>
                        </tr>
                      </template>
                    </tbody>
                  </table>
                </div>

                <!-- Actions processus -->
                <div v-if="!isLocked && canEditProcess(proc.id)" class="raci-actions">
                  <button class="btn btn-sm btn-ghost" @click="clearProcRaci(proc.id)">
                    <i class="ti ti-eraser"></i> Effacer RACI
                  </button>
                </div>
              </div><!-- /proc-body -->
            </div><!-- /proc-block -->

          </div><!-- /col-right -->
        </div><!-- /at-grid -->

        <!-- ══ FOOTER ══════════════════════════════════════════════════ -->
        <footer class="at-footer">
          <div>
            <button v-if="!isLocked" type="button" class="btn btn-ghost" :disabled="processing" @click="annuler">
              <i class="ti ti-x"></i> Annuler
            </button>
            <button v-if="!isLocked" type="button" class="btn btn-save" :disabled="processing" @click="submit">
              <span v-if="processing" class="spin-dot"></span>
              <i v-else class="ti ti-device-floppy"></i>
              {{ form.id ? 'Mettre à jour' : 'Enregistrer' }}
            </button>
          </div>
          <div>
            <button v-if="form.id && form.validation_status === 'draft'"
              type="button" class="btn btn-sub" :disabled="processing" @click="soumettre">
              <i class="ti ti-send"></i> Soumettre
            </button>
            <template v-if="canManage && form.validation_status === 'in_review'">
              <button type="button" class="btn btn-ok" :disabled="processing" @click="valider('validate')">
                <i class="ti ti-circle-check"></i> Valider
              </button>
              <button type="button" class="btn btn-rej" :disabled="processing" @click="promptReject">
                <i class="ti ti-circle-x"></i> Rejeter
              </button>
            </template>
          </div>
        </footer>
      </div><!-- /at-body -->
    </div><!-- /at-shell -->

    <!-- ══ CHAT ════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <transition name="slide-right">
        <div v-if="chatPanel.show" class="at-chat-panel">
          <div class="at-chat-hd">
            <div class="at-chat-hdinfo">
              <div class="at-chat-av"><i class="ti ti-message-circle"></i></div>
              <div>
                <span class="at-chat-title">Chat — Analyse des Tâches</span>
                <span class="at-chat-sub">{{ mission?.code_mission }} · {{ assignment?.phase_label || 'AT' }}</span>
              </div>
            </div>
            <button class="at-chat-close" @click="chatPanel.show = false"><i class="ti ti-x"></i></button>
          </div>
          <div class="at-chat-msgs" ref="chatMsgEl">
            <div v-if="!localMsgs.length" class="at-chat-empty">
              <i class="ti ti-messages"></i><p>Aucun message.</p>
            </div>
            <div v-for="msg in localMsgs" :key="msg.id" class="at-cmsg"
              :class="[`ft-${msg.type}`,`fp-${msg.priority}`,{mine:msg.is_mine}]">
              <div class="at-cav" :class="`cav-${msg.author_role}`">{{ msg.author_initials }}</div>
              <div class="at-cbody2">
                <div class="at-cmeta">
                  <span class="at-cwho" :class="`cr-${msg.author_role}`">{{ msg.author_name }}</span>
                  <span class="at-crole">{{ msg.author_role }}</span>
                  <span v-if="msg.type !== 'message'" class="at-ctypetag">{{ chatTypeLbl(msg.type) }}</span>
                  <span v-if="msg.priority !== 'normal'" class="at-cpritag" :class="`pp-${msg.priority}`">{{ msg.priority }}</span>
                  <span class="at-cdate">{{ msg.created_at_fr }}</span>
                </div>
                <p class="at-ctxt">{{ msg.content }}</p>
              </div>
            </div>
          </div>
          <div class="at-chat-compose">
            <div class="at-chat-opts">
              <select v-model="chatPanel.type" class="at-chat-sel">
                <option value="message">💬 Message</option>
                <option v-if="canManage" value="instruction">📋 Instruction</option>
                <option v-if="canManage" value="correction">✏️ Correction</option>
                <option v-if="canManage" value="validation">✅ Validation</option>
                <option v-if="canManage" value="rejet">❌ Rejet</option>
                <option value="info">ℹ️ Info</option>
              </select>
              <div class="at-prios">
                <button v-for="p in PRIOS" :key="p.v" type="button"
                  class="at-priobtn" :class="[{active: chatPanel.priority===p.v},`ppb-${p.v}`]"
                  @click="chatPanel.priority = p.v"><i :class="p.icon"></i> {{ p.l }}</button>
              </div>
            </div>
            <div class="at-chat-row">
              <textarea v-model="chatPanel.draft" class="at-chat-ta" rows="2"
                placeholder="Votre message…" @keydown.ctrl.enter="sendMsg"></textarea>
              <button type="button" class="at-chat-send"
                :disabled="!chatPanel.draft.trim() || chatPanel.sending" @click="sendMsg">
                <span v-if="chatPanel.sending" class="spin-dot spin-dot--sm"></span>
                <i v-else class="ti ti-send"></i>
              </button>
            </div>
            <div class="at-chat-hint">Ctrl+Entrée pour envoyer</div>
          </div>
        </div>
      </transition>
      <div v-if="chatPanel.show" class="at-chat-overlay" @click="chatPanel.show = false"></div>
    </Teleport>

    <!-- ══ TOAST ══════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="toast-t">
        <div v-if="toast.show" class="toast" :class="`toast--${toast.type}`">
          <i :class="toast.type==='success' ? 'ti ti-circle-check' : 'ti ti-circle-x'"></i>
          {{ toast.msg }}
        </div>
      </Transition>
    </Teleport>

  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, computed, reactive, onMounted, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'

const field = {
  props: { label: String },
  template: `<div class="fg"><label class="flbl">{{ label }}</label><slot /></div>`
}

// ── Props ─────────────────────────────────────────────────────────
const props = withDefaults(defineProps<{
  mission?:            any
  assignment?:         any
  auditorRole?:        string
  missionId?:          number
  assignmentId?:       number
  form?:               any
  // processesData : chaque processus a son propre tableau `functions`
  processesData?:      any[]
  unlinkedProcesses?:  any[]
  raciRoles?:          any[]
  allFunctions?:       any[]
  riskCount?:          number
  atList?:             any[]
  currentAuditor?:     any
  phaseAuditeurs?:     any[]
  processAssignments?: Record<string, number>
  formUrl?:            string
  backUrl?:            string
  chatBaseUrl?:        string
  chatMessages?:       any[]
}>(), {
  processesData:       () => [],
  unlinkedProcesses:   () => [],
  raciRoles:           () => [],
  allFunctions:        () => [],
  atList:              () => [],
  phaseAuditeurs:      () => [],
  processAssignments:  () => ({}),
  riskCount:           0,
  chatMessages:        () => [],
})

// ── Form state ────────────────────────────────────────────────────
const form = reactive<any>({
  id: null, code: '', validation_status: 'draft', validation_note: '',
  fait_par: '', revue_par: '', date_fait: '', date_revue: '', synthese: '',
  ...(props.form ?? {}),
})

// ── Affectations processus → auditeur ────────────────────────────
const localProcessAssignments = reactive<Record<number, number | null>>(
  Object.fromEntries(
    Object.entries(props.processAssignments ?? {})
      .map(([k, v]) => [Number(k), v ? Number(v) : null])
  )
)

// ── Processus actifs ──────────────────────────────────────────────
// addedProcIds : processus ajoutés manuellement en session (pas encore sauvés)
const addedProcIds  = ref<number[]>([])
const expandedProcs = ref<Set<number>>(new Set(
  (props.processesData ?? []).map((p: any) => p.id)
))
const expandedActs  = ref<Set<number>>(new Set())
const activatedActs = reactive<Record<number, boolean>>({})

const activeProcesses = computed(() => {
  // processesData = liés mission + manuels sauvegardés (chargés depuis DB)
  const saved  = (props.processesData ?? []).map((p: any) => ({ ...p }))
  // addedProcIds = manuels ajoutés en session (pas encore sauvegardés)
  const newIds = addedProcIds.value.filter(
    id => !(props.processesData ?? []).some((p: any) => p.id === id)
  )
  const newProcs = (props.unlinkedProcesses ?? [])
    .filter((p: any) => newIds.includes(p.id))
    .map((p: any) => ({ ...p, is_linked: false, functions: [], activities: p.activities ?? [] }))
  return [...saved, ...newProcs]
})

const procToAdd = ref('')

// ── FONCTIONS PAR PROCESSUS ───────────────────────────────────────
// procFunctions : { process_id → [{ id, name, character, sort_order }] }
// Initialisé depuis processesData.functions (chargé depuis at_process_functions)
const procFunctions = reactive<Record<number, any[]>>(
  Object.fromEntries(
    (props.processesData ?? []).map((p: any) => [p.id, [...(p.functions ?? [])]])
  )
)

// Select "ajouter une fonction" par processus
const fnToAddPerProc = reactive<Record<number, string>>({})

// Fonctions disponibles pour un processus (= allFunctions moins celles déjà ajoutées)
function availableFunctionsForProc(procId: number): any[] {
  const usedIds = (procFunctions[procId] ?? []).map((f: any) => f.id)
  return (props.allFunctions ?? []).filter((f: any) => !usedIds.includes(f.id))
}

function addProcFunction(procId: number) {
  const id = Number(fnToAddPerProc[procId])
  if (!id) return
  const fn = (props.allFunctions ?? []).find((f: any) => f.id === id)
  if (!fn) return
  if (!procFunctions[procId]) procFunctions[procId] = []
  procFunctions[procId].push({ ...fn, sort_order: procFunctions[procId].length })
  fnToAddPerProc[procId] = ''
}

function removeProcFunction(procId: number, fnId: number) {
  if (!procFunctions[procId]) return
  procFunctions[procId] = procFunctions[procId].filter((f: any) => f.id !== fnId)
  // Supprimer les entrées RACI liées à cette fonction pour ce processus
  for (const key of Object.keys(raciMap)) {
    if (key.startsWith(`${procId}_`) && key.endsWith(`_${fnId}`)) delete raciMap[key]
  }
}

// ── RACI local ─────────────────────────────────────────────────────
// Clé : "procId_actId_fnId" → role
// Initialisé depuis processesData.activities[].raci
const raciMap = reactive<Record<string, string>>({})

onMounted(() => {
  for (const proc of props.processesData ?? []) {
    // Initialiser procFunctions si pas encore fait
    if (!procFunctions[proc.id]) procFunctions[proc.id] = [...(proc.functions ?? [])]

    for (const act of proc.activities ?? []) {
      // act.raci = { "function_id" → role } chargé depuis at_raci_entries
      for (const [fnId, role] of Object.entries(act.raci ?? {})) {
        raciMap[`${proc.id}_${act.id}_${fnId}`] = role as string
      }
    }
  }
})

const raciRoleCodes = computed(() => (props.raciRoles ?? []).map((r: any) => r.code))

function getRaci(procId: number, actId: number, fnId: number): string {
  return raciMap[`${procId}_${actId}_${fnId}`] ?? ''
}
function setRaci(procId: number, actId: number, fnId: number, role: string) {
  if (isLocked.value || !canEditProcess(procId)) return
  const key = `${procId}_${actId}_${fnId}`
  raciMap[key] === role ? delete raciMap[key] : (raciMap[key] = role)
}
function clearProcRaci(procId: number) {
  for (const key of Object.keys(raciMap)) {
    if (key.startsWith(`${procId}_`)) delete raciMap[key]
  }
}
function raciLabel(code: string): string {
  return (props.raciRoles ?? []).find((r: any) => r.code === code)?.label ?? code
}

// ── Toggles ───────────────────────────────────────────────────────
function toggleProc(id: number) {
  expandedProcs.value.has(id) ? expandedProcs.value.delete(id) : expandedProcs.value.add(id)
}
function toggleActivity(actId: number) {
  activatedActs[actId] = !activatedActs[actId]
}
function toggleRiskDetail(actId: number) {
  expandedActs.value.has(actId) ? expandedActs.value.delete(actId) : expandedActs.value.add(actId)
}

// ── Processus ─────────────────────────────────────────────────────
function addProcess() {
  if (!procToAdd.value) return
  const id = Number(procToAdd.value)
  if (!addedProcIds.value.includes(id)) {
    addedProcIds.value.push(id)
    expandedProcs.value.add(id)
    procFunctions[id] = []
  }
  procToAdd.value = ''
}
function removeProcess(id: number) {
  addedProcIds.value = addedProcIds.value.filter(i => i !== id)
  expandedProcs.value.delete(id)
  delete procFunctions[id]
  delete localProcessAssignments[id]
  clearProcRaci(id)
}

// ── Droits ────────────────────────────────────────────────────────
const canManage = computed(() => ['DM', 'CM'].includes(props.auditorRole ?? ''))
const isLocked  = computed(() =>
  form.validation_status === 'validated' ||
  (form.validation_status === 'in_review' && !canManage.value)
)
function canEditProcess(procId: number): boolean {
  if (canManage.value) return true
  return localProcessAssignments[procId] === props.currentAuditor?.id
}

// ── Auditeur assigné ──────────────────────────────────────────────
function getAssignedAuditeur(procId: number): any | null {
  const id = localProcessAssignments[procId]
  if (!id) return null
  return (props.phaseAuditeurs ?? []).find((a: any) => a.id === id) ?? null
}

async function assignProcessToAuditeur(procId: number, auditeurIdStr: string) {
  const auditeurId = auditeurIdStr ? Number(auditeurIdStr) : null
  localProcessAssignments[procId] = auditeurId
  try {
    const res  = await fetch(`${props.formUrl}/assign-process`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ assignment_id: props.assignmentId, process_id: procId, auditeur_id: auditeurId }),
    })
    const data = await res.json()
    if (!data.success) showToast('error', data.error ?? 'Erreur affectation')
    else showToast('success', auditeurId ? 'Processus affecté.' : 'Affectation retirée.')
  } catch { showToast('error', 'Erreur réseau') }
}

// ── Helpers ───────────────────────────────────────────────────────
function critClass(crit: any): string {
  if (!crit) return 'crit-unknown'
  const c = String(crit).toLowerCase()
  if (c.includes('élevé') || c.includes('eleve') || c.includes('high') || c.includes('critique')) return 'crit-high'
  if (c.includes('moyen') || c.includes('medium') || c.includes('mod')) return 'crit-med'
  return 'crit-low'
}

function csrf() {
  return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? ''
}

const toast = ref({ show: false, type: 'success', msg: '' })
function showToast(type: string, msg: string) {
  toast.value = { show: true, type, msg }
  setTimeout(() => (toast.value.show = false), 4000)
}

// ── Sérialisation pour envoi ──────────────────────────────────────
// raci_data : [{ process_id, activity_id, function_id, role }]
function buildRaciPayload(): any[] {
  return Object.entries(raciMap)
    .filter(([, role]) => role)
    .map(([key, role]) => {
      const [procId, actId, fnId] = key.split('_').map(Number)
      return { process_id: procId, activity_id: actId, function_id: fnId, role }
    })
}

// proc_functions : { "process_id" → [function_id, ...] }
function buildProcFunctionsPayload(): Record<string, number[]> {
  const result: Record<string, number[]> = {}
  for (const [procId, fns] of Object.entries(procFunctions)) {
    if (fns && fns.length > 0) {
      result[procId] = fns.map((f: any) => f.id)
    }
  }
  return result
}

// manual_processes : [process_id, ...] (manuels en session + manuels déjà sauvés)
function buildManualProcessesPayload(): number[] {
  const fromSession = addedProcIds.value
  const fromSaved   = (props.processesData ?? []).filter((p: any) => !p.is_linked).map((p: any) => p.id)
  return [...new Set([...fromSession, ...fromSaved])]
}

// ── Submit ────────────────────────────────────────────────────────
const processing = ref(false)

async function submit() {
  processing.value = true
  try {
    const payload = {
      mission_id:        props.missionId,
      assignment_id:     props.assignmentId,
      fait_par:          form.fait_par,
      revue_par:         form.revue_par,
      date_fait:         form.date_fait,
      date_revue:        form.date_revue,
      synthese:          form.synthese,
      // Nouvelle modélisation : 3 payloads distincts
      raci_data:         JSON.stringify(buildRaciPayload()),
      proc_functions:    JSON.stringify(buildProcFunctionsPayload()),
      manual_processes:  JSON.stringify(buildManualProcessesPayload()),
    }
    const method = form.id ? 'PUT' : 'POST'
    const url    = form.id ? `${props.formUrl}/${form.id}` : props.formUrl
    const res    = await fetch(url!, {
      method,
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify(payload),
    })
    const data = await res.json()
    if (data.success || res.ok) {
      showToast('success', form.id ? 'Analyse mise à jour.' : 'Analyse créée.')
      if (!form.id && data.form?.id) { form.id = data.form.id; form.code = data.form.code }
    } else {
      showToast('error', data.message ?? 'Erreur lors de la sauvegarde.')
    }
  } catch { showToast('error', 'Erreur réseau.') }
  finally  { processing.value = false }
}

function annuler() { if (props.backUrl) router.visit(props.backUrl) }

async function soumettre() {
  processing.value = true
  try {
    const res  = await fetch(`${props.formUrl}/${form.id}/soumettre`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ mission_id: props.missionId, assignment_id: props.assignmentId }),
    })
    const data = await res.json()
    if (data.success) { form.validation_status = 'in_review'; showToast('success', 'Analyse soumise.') }
    else showToast('error', data.error ?? 'Erreur')
  } catch { showToast('error', 'Erreur réseau') }
  processing.value = false
}

async function valider(action: string, note?: string) {
  processing.value = true
  try {
    const res  = await fetch(`${props.formUrl}/${form.id}/valider`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ mission_id: props.missionId, assignment_id: props.assignmentId, action, note }),
    })
    const data = await res.json()
    if (data.success) { form.validation_status = data.status; showToast('success', action === 'validate' ? 'Validée ✓' : 'Rejetée.') }
    else showToast('error', data.error ?? 'Erreur')
  } catch { showToast('error', 'Erreur réseau') }
  processing.value = false
}

function promptReject() {
  const n = prompt('Motif du rejet (obligatoire) :')
  if (!n?.trim()) return
  valider('reject', n.trim())
}

// ── Chat ──────────────────────────────────────────────────────────
const chatMsgEl   = ref<HTMLElement | null>(null)
const localMsgs   = ref<any[]>([...(props.chatMessages ?? [])])
const chatPanel   = ref({ show: false, draft: '', type: 'message', priority: 'normal', sending: false })
const unreadCount = computed(() => localMsgs.value.filter((m: any) => !m.is_read && !m.is_mine).length)
const PRIOS = [
  { v: 'normal', l: 'Normal', icon: 'ti ti-info-circle' },
  { v: 'urgent', l: 'Urgent', icon: 'ti ti-alert-triangle' },
  { v: 'bloquant', l: 'Bloquant', icon: 'ti ti-alert-octagon' },
]
function openChat() {
  chatPanel.value.show = true
  nextTick(() => { if (chatMsgEl.value) chatMsgEl.value.scrollTop = chatMsgEl.value.scrollHeight })
}
async function sendMsg() {
  const { draft, type, priority } = chatPanel.value
  if (!draft.trim()) return
  chatPanel.value.sending = true
  try {
    const res = await fetch(props.chatBaseUrl || '/api/mission-phase-chat', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
      body: JSON.stringify({ assignment_id: props.assignmentId, mission_id: props.missionId,
        form_code: 'analyse-taches', content: draft, type, priority }),
    })
    const json = await res.json()
    if (!res.ok) throw new Error(json?.message ?? 'Erreur')
    localMsgs.value.push({ ...json.message, is_mine: true })
    chatPanel.value.draft = ''
    nextTick(() => { if (chatMsgEl.value) chatMsgEl.value.scrollTop = chatMsgEl.value.scrollHeight })
  } catch (e: any) { showToast('error', 'Chat : ' + e.message) }
  finally { chatPanel.value.sending = false }
}
function chatTypeLbl(t: string) {
  return ({instruction:'Instruction',correction:'Correction',validation:'Validation',rejet:'Rejet',info:'Info'} as any)[t] ?? t
}
function vstLbl(s: string) {
  return ({draft:'Brouillon',in_review:'En attente',validated:'Validé ✓',rejected:'Rejeté'} as any)[s] ?? s
}
function vstIcon(s: string) {
  return ({draft:'ti ti-pencil',in_review:'ti ti-clock',validated:'ti ti-circle-check',rejected:'ti ti-circle-x'} as any)[s] ?? 'ti ti-circle'
}
</script>

<style scoped>
.at-shell{display:flex;flex-direction:column;min-height:100vh}
.at-header{background:#fff;border-bottom:1px solid #e5e7eb;padding:16px 20px 0;position:sticky;top:0;z-index:50}
.at-hrow{display:flex;align-items:flex-start;gap:12px;padding-bottom:12px}
.at-back{display:flex;align-items:center;justify-content:center;width:34px;height:34px;border:1px solid #e5e7eb;border-radius:8px;color:#6b7280;text-decoration:none;flex-shrink:0;transition:all .15s}
.at-back:hover{background:#f3f4f6;color:#111827}
.at-hinfo{flex:1;min-width:0}
.at-chips{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:5px}
.at-code{font-size:.7rem;font-weight:700;background:#1e293b;color:#fff;padding:2px 8px;border-radius:4px;font-family:ui-monospace,monospace}
.at-chip{display:inline-flex;align-items:center;gap:4px;font-size:.68rem;font-weight:600;padding:2px 8px;border-radius:10px;border:1px solid transparent}
.chip-draft{background:#f3f4f6;color:#6b7280;border-color:#e5e7eb}
.chip-in_review{background:#e3f2fd;color:#1565C0;border-color:rgba(21,101,192,.2)}
.chip-validated{background:#ecfdf5;color:#059669;border-color:#a7f3d0}
.chip-rejected{background:#fef2f2;color:#dc2626;border-color:#fecaca}
.chip-type{background:#ede9fe;color:#7c3aed;border-color:#c4b5fd}
.chip-role-DM{background:#f5f3ff;color:#7c3aed;border-color:#ddd6fe}
.chip-role-CM{background:#f0f9ff;color:#0284c7;border-color:#bae6fd}
.chip-role-AS{background:#f0fdf4;color:#059669;border-color:#a7f3d0}
.chip-role-AJ{background:#fffbeb;color:#d97706;border-color:#fde68a}
.at-title{font-size:1.1rem;font-weight:800;color:#111827;margin:0 0 4px}
.at-meta{display:flex;align-items:center;gap:14px;flex-wrap:wrap;font-size:.74rem;color:#6b7280}
.at-meta span{display:flex;align-items:center;gap:4px}
.at-hactions{display:flex;align-items:center;gap:8px;flex-shrink:0}
.at-banner{display:flex;align-items:center;gap:8px;padding:8px 16px;font-size:.78rem;font-weight:500}
.banner-lock{background:#ecfdf5;color:#059669;border-top:1px solid #a7f3d0}
.banner-review{background:#e3f2fd;color:#1565C0;border-top:1px solid rgba(21,101,192,.2)}
.banner-reject{background:#fef2f2;color:#dc2626;border-top:1px solid #fecaca}
.at-body{padding:20px;flex:1}
.at-grid{display:grid;grid-template-columns:300px 1fr;gap:18px}
@media(max-width:900px){.at-grid{grid-template-columns:1fr}}
.card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;margin-bottom:14px;overflow:hidden}
.card-label{display:flex;align-items:center;gap:6px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;padding:10px 14px;background:#f9fafb;border-bottom:1px solid #e5e7eb}
.card-count{margin-left:auto;font-size:.64rem;font-weight:800;background:#e5e7eb;color:#6b7280;padding:1px 6px;border-radius:8px}
.card-body{padding:12px 14px}
/* Auditeurs */
.auditeur-list{display:flex;flex-direction:column;gap:5px}
.auditeur-item{display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:8px;border:1px solid #e5e7eb;background:#fafafa;transition:background .12s}
.auditeur-item:hover{background:#f3f4f6}
.aud-avatar{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.62rem;font-weight:800;flex-shrink:0;border:2px solid transparent}
.av-DM{background:#f5f3ff;color:#7c3aed;border-color:#ddd6fe}
.av-CM{background:#f0f9ff;color:#0284c7;border-color:#bae6fd}
.av-AS{background:#f0fdf4;color:#059669;border-color:#a7f3d0}
.av-AJ{background:#fffbeb;color:#d97706;border-color:#fde68a}
.aud-info{flex:1;min-width:0}
.aud-name{display:block;font-size:.76rem;font-weight:600;color:#111827;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.aud-meta{display:flex;align-items:center;gap:6px}
.aud-code{font-size:.6rem;font-family:ui-monospace,monospace;color:#9ca3af}
.aud-role-lbl{font-size:.62rem;color:#9ca3af}
.aud-role-badge{display:inline-flex;align-items:center;font-size:.62rem;font-weight:700;padding:2px 7px;border-radius:8px;border:1px solid transparent;flex-shrink:0}
/* Form inputs */
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.fg{display:flex;flex-direction:column;gap:3px;margin-bottom:10px}
.flbl{font-size:.66rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em}
.inp{background:#fff;border:1px solid #e5e7eb;color:#111827;padding:6px 9px;border-radius:6px;font-size:.8rem;outline:none;transition:border-color .15s;font-family:inherit;width:100%;box-sizing:border-box}
.inp:focus{border-color:#2563EB;box-shadow:0 0 0 3px rgba(37,99,235,.1)}
.inp:disabled,.inp-ro{background:#f9fafb;color:#9ca3af;cursor:default}
.inp-ta{resize:vertical;min-height:70px}
/* Légende */
.raci-legend-row{display:flex;align-items:flex-start;gap:10px;padding:6px 0;border-bottom:1px solid #f3f4f6}
.raci-legend-row:last-child{border-bottom:none}
.raci-legend-row strong{font-size:.78rem;color:#374151}
.raci-legend-row p{font-size:.7rem;color:#9ca3af;margin:0}
.raci-badge{width:26px;height:26px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:900;flex-shrink:0}
.raci-R{background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe}
.raci-A{background:#fef3c7;color:#d97706;border:1px solid #fde68a}
.raci-C{background:#f0fdf4;color:#059669;border:1px solid #a7f3d0}
.raci-I{background:#f5f3ff;color:#7c3aed;border:1px solid #ddd6fe}
.risk-legend{margin-top:10px;padding-top:10px;border-top:1px solid #f3f4f6}
.risk-legend-title{font-size:.68rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;display:flex;align-items:center;gap:4px}
.risk-legend-row{display:flex;align-items:center;gap:8px;padding:3px 0;font-size:.7rem;color:#6b7280}
.legend-dot-yellow{width:10px;height:10px;background:#fef3c7;border:1px solid #fde68a;border-radius:2px;display:inline-block;flex-shrink:0}
/* Table sidebar */
.tbl{width:100%;border-collapse:collapse;font-size:.76rem}
.tbl thead tr{background:#f9fafb}
.tbl th,.tbl td{padding:7px 10px;border-bottom:1px solid #f3f4f6;text-align:left}
.tbl th{font-size:.66rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.04em}
.tbl-row{cursor:pointer;transition:background .1s}
.tbl-row:hover{background:#f9fafb}
.td-empty{text-align:center;color:#d1d5db;padding:16px}
.td-code{font-family:ui-monospace,monospace;font-size:.7rem;color:#6b7280}
/* Add proc bar */
.add-proc-bar{display:flex;align-items:center;gap:8px;background:#fff;border:1px solid #e5e7eb;border-radius:9px;padding:10px 14px;margin-bottom:10px;flex-wrap:wrap}
.proc-sel{flex:1;min-width:160px;font-size:.78rem}
.at-info-bar{display:flex;align-items:center;gap:8px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:8px 14px;font-size:.76rem;color:#1d4ed8;margin-bottom:10px}
.at-no-proc{background:#fff;border:1px dashed #e5e7eb;border-radius:10px;padding:32px;text-align:center;color:#9ca3af;font-size:.82rem}
.at-no-proc i{font-size:1.5rem;display:block;margin-bottom:8px}
/* Bloc processus */
.proc-block{background:#fff;border:1px solid #e5e7eb;border-radius:10px;margin-bottom:14px;overflow:hidden}
.proc-header{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;cursor:pointer;transition:background .12s;border-bottom:1px solid transparent}
.proc-header:hover{background:#f9fafb}
.proc-block:has(.proc-body) .proc-header{border-bottom-color:#e5e7eb}
.proc-header-l{display:flex;align-items:center;gap:9px;flex:1;min-width:0;overflow:hidden}
.proc-header-r{display:flex;align-items:center;gap:6px;flex-shrink:0}
.proc-chev{width:18px;height:18px;display:flex;align-items:center;justify-content:center;color:#9ca3af;flex-shrink:0}
.proc-chev i{transition:transform .2s}
.proc-chev--open i{transform:rotate(90deg)}
.proc-ico{font-size:.95rem;color:#6b7280;flex-shrink:0}
.proc-info{min-width:0;overflow:hidden;flex:1}
.proc-code{font-size:.68rem;font-weight:800;font-family:ui-monospace,monospace;color:#6b7280;display:block}
.proc-name{font-size:.82rem;font-weight:600;color:#111827;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.proc-badges{display:flex;align-items:center;gap:5px;flex-shrink:0;flex-wrap:wrap}
.risk-badge{display:flex;align-items:center;gap:4px;font-size:.66rem;font-weight:700;background:#fef3c7;color:#d97706;padding:2px 7px;border-radius:8px;border:1px solid #fde68a}
.fn-count-badge{font-size:.64rem;font-weight:600;background:#ede9fe;color:#7c3aed;padding:2px 7px;border-radius:8px;border:1px solid #ddd6fe;display:flex;align-items:center;gap:3px}
.added-badge{font-size:.64rem;font-weight:700;background:#f0fdf4;color:#059669;padding:2px 6px;border-radius:8px;border:1px solid #a7f3d0}
.readonly-badge{font-size:.64rem;font-weight:600;background:#f3f4f6;color:#9ca3af;padding:2px 6px;border-radius:6px;border:1px solid #e5e7eb}
.unassigned-badge{font-size:.64rem;font-weight:600;background:#fef2f2;color:#dc2626;padding:2px 6px;border-radius:6px;border:1px solid #fecaca;display:flex;align-items:center;gap:3px}
.assigned-badge{display:inline-flex;align-items:center;gap:4px;font-size:.64rem;font-weight:700;padding:2px 8px;border-radius:8px;border:1.5px solid transparent;cursor:default}
.assigned-name{font-weight:500;max-width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.proc-assign-wrap{display:flex;align-items:center}
.proc-assign-sel{font-size:.7rem;padding:4px 8px;border:1px solid #d1d5db;border-radius:6px;background:#f9fafb;color:#374151;cursor:pointer;min-width:150px;max-width:200px}
.proc-assign-sel:focus{border-color:#6366f1;outline:none}
/* Corps processus */
.proc-body{}
/* Barre fonctions PAR PROCESSUS */
.proc-fn-bar{display:flex;align-items:flex-start;gap:10px;padding:10px 14px;background:#f8fafc;border-bottom:1px solid #e5e7eb;flex-wrap:wrap}
.proc-fn-list{display:flex;align-items:center;flex-wrap:wrap;gap:6px;flex:1;min-width:0}
.proc-fn-label{font-size:.7rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;display:flex;align-items:center;gap:4px}
.proc-fn-tag{display:inline-flex;align-items:center;gap:5px;padding:3px 8px;background:#ede9fe;border:1px solid #c4b5fd;border-radius:20px}
.proc-fn-char{width:20px;height:20px;border-radius:50%;background:#7c3aed;color:#fff;font-size:.58rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.proc-fn-name{font-size:.72rem;font-weight:600;color:#4c1d95;max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.proc-fn-del{width:14px;height:14px;background:none;border:none;cursor:pointer;color:#a78bfa;display:flex;align-items:center;justify-content:center;font-size:.55rem;flex-shrink:0;border-radius:3px;transition:all .1s}
.proc-fn-del:hover{color:#7c3aed;background:#ddd6fe}
.proc-fn-empty{font-size:.72rem;color:#9ca3af;font-style:italic}
.proc-fn-add{display:flex;align-items:center;gap:6px;flex-shrink:0}
.proc-fn-sel{font-size:.74rem;padding:5px 8px;border:1px solid #d1d5db;border-radius:6px;background:#fff;color:#374151;cursor:pointer;min-width:160px}
.proc-fn-sel:focus{border-color:#7c3aed;outline:none}
/* RACI table */
.raci-table-container{overflow-x:auto}
.raci-table{width:100%;border-collapse:collapse;font-size:.76rem;min-width:500px}
.raci-table thead tr{background:#f9fafb}
.raci-table th,.raci-table td{border:1px solid #f3f4f6;text-align:center;vertical-align:middle}
.th-act{text-align:left;padding:7px 12px;font-size:.66rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.04em;min-width:180px}
.th-risk{padding:7px 10px;font-size:.66rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.04em;min-width:140px}
.th-risk-hint{display:inline-flex;align-items:center;gap:3px;margin-left:4px;vertical-align:middle}
.risk-dot{width:7px;height:7px;border-radius:50%;display:inline-block}
.dot-mission{background:#ef4444}
.dot-native{background:#9ca3af}
.th-fn{padding:6px 4px;min-width:70px;max-width:90px}
.th-fn-empty{padding:7px;color:#d1d5db;font-size:.7rem;font-style:italic}
.fn-th-inner{display:flex;flex-direction:column;align-items:center;gap:2px;padding:2px}
.fn-th-char{width:24px;height:24px;border-radius:50%;background:#e0e7ff;color:#4f46e5;font-size:.6rem;font-weight:800;display:flex;align-items:center;justify-content:center}
.fn-th-name{font-size:.6rem;font-weight:600;color:#6b7280;max-width:80px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
/* Lignes */
.raci-row{transition:background .1s}
.raci-row:hover{background:#fafafa}
.raci-row--inactive{opacity:.55}
.raci-row--linked{background:#fffbeb}
.raci-row--linked:hover{background:#fef3c7}
.td-act{text-align:left;padding:6px 10px}
.act-cell{display:flex;align-items:center;gap:6px}
.act-toggle{width:20px;height:20px;background:none;border:none;cursor:pointer;color:#9ca3af;flex-shrink:0;font-size:.85rem;display:flex;align-items:center;justify-content:center;border-radius:4px;transition:all .12s}
.act-toggle--on{color:#059669}
.act-toggle:hover{background:#f3f4f6}
.act-risk-ico{font-size:.7rem;color:#f59e0b;flex-shrink:0}
.act-info{min-width:0}
.act-code{font-size:.64rem;font-family:ui-monospace,monospace;color:#9ca3af;display:block}
.act-name{font-size:.76rem;font-weight:500;color:#374151;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:200px}
/* ══ Risques — NOUVEAU : pills + noms ══ */
.td-risk{padding:5px 8px;min-width:140px;max-width:220px;cursor:pointer}
.risk-pills-wrap{display:flex;flex-direction:column;gap:3px}
.risk-pills{display:flex;flex-wrap:wrap;gap:3px;justify-content:flex-start}
/* Noms des risques sous les pills */
.risk-names{display:flex;flex-direction:column;gap:2px;margin-top:2px}
.risk-name-item{font-size:.62rem;line-height:1.3;color:#6b7280;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:200px}
.risk-name-mission{color:#b45309;font-weight:500}
.risk-name-native{color:#6b7280}
/* Pills */
.risk-pill{font-size:.58rem;font-weight:700;padding:1px 5px;border-radius:6px;cursor:pointer;transition:transform .1s;display:inline-block}
.risk-pill:hover{transform:scale(1.1)}
.risk-mission{outline:2px solid currentColor;outline-offset:1px}
.risk-native{opacity:.72}
.crit-high{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
.crit-med{background:#fef3c7;color:#d97706;border:1px solid #fde68a}
.crit-low{background:#ecfdf5;color:#059669;border:1px solid #a7f3d0}
.crit-unknown{background:#f3f4f6;color:#9ca3af;border:1px solid #e5e7eb}
.no-risk{color:#e5e7eb;font-size:.75rem}
.td-raci{padding:4px 6px}
.td-raci--locked{cursor:not-allowed}
.raci-cell{display:flex;gap:3px;justify-content:center;flex-wrap:wrap}
.raci-cell-disabled{display:flex;justify-content:center}
.raci-empty{display:flex;align-items:center;gap:8px;padding:18px 16px;color:#9ca3af;font-size:.78rem}
.raci-btn{width:24px;height:24px;border:1px solid #e5e7eb;border-radius:5px;background:transparent;font-size:.64rem;font-weight:800;cursor:pointer;transition:all .12s;color:#9ca3af;display:flex;align-items:center;justify-content:center}
.raci-btn:hover:not(:disabled){transform:scale(1.1)}
.raci-btn:disabled{cursor:not-allowed;opacity:.35}
.raci-btn-R.raci-btn--active{background:#eff6ff;color:#2563eb;border-color:#2563eb}
.raci-btn-A.raci-btn--active{background:#fef3c7;color:#d97706;border-color:#f59e0b}
.raci-btn-C.raci-btn--active{background:#f0fdf4;color:#059669;border-color:#059669}
.raci-btn-I.raci-btn--active{background:#f5f3ff;color:#7c3aed;border-color:#7c3aed}
.raci-btn-R:hover:not(:disabled):not(.raci-btn--active){background:#dbeafe;color:#2563eb;border-color:#bfdbfe}
.raci-btn-A:hover:not(:disabled):not(.raci-btn--active){background:#fef9c3;color:#d97706;border-color:#fde68a}
.raci-btn-C:hover:not(:disabled):not(.raci-btn--active){background:#dcfce7;color:#059669;border-color:#bbf7d0}
.raci-btn-I:hover:not(:disabled):not(.raci-btn--active){background:#ede9fe;color:#7c3aed;border-color:#ddd6fe}
/* Détail risques */
.risk-detail-row td{padding:0;background:#fffbeb}
.risk-detail-list{padding:6px 10px;display:flex;flex-direction:column;gap:4px}
.risk-detail-item{display:flex;align-items:center;gap:8px;font-size:.72rem}
.risk-detail-code{font-weight:700;padding:1px 6px;border-radius:5px}
.risk-detail-label{flex:1;color:#374151}
.risk-detail-badge{font-size:.6rem;font-weight:600;padding:1px 6px;border-radius:4px;flex-shrink:0}
.badge-mission{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
.badge-native{background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb}
.risk-detail-crit{font-size:.66rem;font-weight:600;padding:1px 6px;border-radius:5px}
.raci-actions{display:flex;gap:6px;padding:8px 12px;background:#f9fafb;border-top:1px solid #f3f4f6}
/* Buttons */
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:7px;font-size:.8rem;font-weight:600;border:none;cursor:pointer;font-family:inherit;transition:all .15s;white-space:nowrap}
.btn-save{background:#1e293b;color:#fff}
.btn-save:hover:not(:disabled){background:#0f172a}
.btn-ghost{background:#fff;color:#374151;border:1px solid #e5e7eb}
.btn-ghost:hover:not(:disabled){background:#f9fafb}
.btn-sub{background:#eff6ff;color:#2563EB;border:1px solid #bfdbfe}
.btn-sub:hover:not(:disabled){background:#dbeafe}
.btn-ok{background:#ecfdf5;color:#059669;border:1px solid #a7f3d0}
.btn-ok:hover:not(:disabled){background:#d1fae5}
.btn-rej{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
.btn-rej:hover:not(:disabled){background:#fee2e2}
.btn-sm{padding:5px 10px;font-size:.76rem}
.btn-add-fn,.btn-add-proc{background:#f9fafb;color:#374151;border:1px solid #e5e7eb}
.btn-add-fn:hover:not(:disabled),.btn-add-proc:hover:not(:disabled){background:#eff6ff;color:#2563EB;border-color:#bfdbfe}
.btn:disabled{opacity:.45;cursor:not-allowed}
.ibtn{width:25px;height:25px;display:flex;align-items:center;justify-content:center;background:transparent;border:1px solid transparent;border-radius:5px;cursor:pointer;font-size:.78rem;color:#d1d5db;transition:all .12s;padding:0}
.ibtn--del:hover{color:#dc2626;border-color:#fecaca;background:#fef2f2}
/* Footer */
.at-footer{display:flex;align-items:center;justify-content:space-between;padding:12px 20px;background:#fff;border-top:1px solid #e5e7eb;position:sticky;bottom:0;z-index:40;flex-wrap:wrap;gap:8px}
.at-footer>div{display:flex;gap:8px;flex-wrap:wrap}
/* Toast */
.toast{position:fixed;top:18px;right:18px;z-index:9999;display:flex;align-items:center;gap:8px;padding:10px 16px;border-radius:8px;font-size:.8rem;font-weight:600;box-shadow:0 4px 18px rgba(0,0,0,.12);border:1px solid transparent}
.toast--success{background:#ecfdf5;color:#059669;border-color:#a7f3d0}
.toast--error{background:#fef2f2;color:#dc2626;border-color:#fecaca}
.toast-t-enter-active,.toast-t-leave-active{transition:all .25s}
.toast-t-enter-from,.toast-t-leave-to{opacity:0;transform:translateX(12px)}
/* Chat */
.btn-chat{display:inline-flex;align-items:center;gap:6px;padding:6px 12px 6px 9px;background:#fff;border:1px solid #e5e7eb;border-radius:8px;color:#6b7280;font-size:.78rem;font-weight:500;cursor:pointer;transition:all .15s;position:relative}
.btn-chat:hover{border-color:#2563EB;color:#2563EB;background:#eff6ff}
.btn-chat.unread{border-color:#bfdbfe;background:#eff6ff;color:#2563EB}
.chat-badge{position:absolute;top:-6px;right:-6px;background:#ef4444;color:#fff;font-size:.58rem;font-weight:700;min-width:16px;height:16px;border-radius:8px;display:flex;align-items:center;justify-content:center;padding:0 3px;border:2px solid #fff}
.at-chat-overlay{position:fixed;inset:0;background:rgba(0,0,0,.28);z-index:400}
.at-chat-panel{position:fixed;top:0;right:0;bottom:0;width:360px;max-width:95vw;background:#fff;border-left:1px solid #e2e8f0;box-shadow:-4px 0 24px rgba(0,0,0,.12);display:flex;flex-direction:column;z-index:500}
.slide-right-enter-active,.slide-right-leave-active{transition:transform .22s ease}
.slide-right-enter-from,.slide-right-leave-to{transform:translateX(100%)}
.at-chat-hd{display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border-bottom:1px solid #e2e8f0;background:#f8fafc;gap:10px}
.at-chat-hdinfo{display:flex;align-items:center;gap:10px;min-width:0}
.at-chat-av{width:34px;height:34px;border-radius:9px;background:#eff6ff;color:#2563EB;display:flex;align-items:center;justify-content:center;font-size:.95rem;flex-shrink:0}
.at-chat-title{display:block;font-size:.8rem;font-weight:700;color:#1a1a2e}
.at-chat-sub{display:block;font-size:.6rem;color:#94a3b8;font-family:monospace}
.at-chat-close{width:26px;height:26px;border-radius:6px;background:#f1f5f9;border:1px solid #e2e8f0;color:#64748b;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.72rem}
.at-chat-msgs{flex:1;overflow-y:auto;padding:12px;display:flex;flex-direction:column;gap:8px}
.at-chat-msgs::-webkit-scrollbar{width:3px}
.at-chat-empty{display:flex;flex-direction:column;align-items:center;gap:8px;padding:40px 20px;color:#cbd5e1}
.at-chat-empty i{font-size:1.8rem}
.at-chat-empty p{font-size:.76rem}
.at-cmsg{display:flex;gap:8px}
.at-cmsg.mine{flex-direction:row-reverse}
.at-cav{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.5rem;font-weight:700;flex-shrink:0;background:#e5e7eb;color:#6b7280}
.cav-DM{background:#f5f3ff;color:#7c3aed}.cav-CM{background:#f0f9ff;color:#0284c7}
.cav-AS{background:#f0fdf4;color:#059669}.cav-AJ{background:#fffbeb;color:#d97706}
.at-cbody2{flex:1;min-width:0}
.at-cmsg.mine .at-cbody2{align-items:flex-end;display:flex;flex-direction:column}
.at-cmeta{display:flex;align-items:center;gap:5px;flex-wrap:wrap;margin-bottom:3px}
.at-cmsg.mine .at-cmeta{flex-direction:row-reverse}
.at-cwho{font-size:.65rem;font-weight:700}
.cr-DM{color:#7c3aed}.cr-CM{color:#0284c7}.cr-AS{color:#059669}.cr-AJ{color:#d97706}
.at-crole{font-size:.58rem;font-weight:600;background:#f3f4f6;color:#6b7280;padding:1px 5px;border-radius:4px}
.at-ctypetag{font-size:.6rem;font-weight:600;padding:1px 6px;border-radius:4px;background:#eff6ff;color:#2563EB}
.at-cpritag{font-size:.58rem;font-weight:700;padding:1px 5px;border-radius:4px}
.pp-urgent{background:#fef3c7;color:#d97706}
.pp-bloquant{background:#fef2f2;color:#dc2626}
.at-cdate{font-size:.58rem;color:#94a3b8;margin-left:auto}
.at-ctxt{font-size:.75rem;color:#1e293b;line-height:1.5;background:#f8fafc;padding:7px 10px;border-radius:8px;border:1px solid #e9ecef}
.at-cmsg.mine .at-ctxt{background:#e3f2fd;border-color:#bbdefb}
.fp-bloquant .at-cbody2{padding-left:6px;border-left:2px solid #dc2626}
.fp-urgent .at-cbody2{padding-left:6px;border-left:2px solid #f59e0b}
.at-chat-compose{border-top:1px solid #e2e8f0;padding:10px 12px 12px;background:#f8fafc;flex-shrink:0;display:flex;flex-direction:column;gap:7px}
.at-chat-opts{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.at-chat-sel{border:1px solid #d1d5db;border-radius:6px;padding:4px 9px;font-size:.69rem;color:#1a1a2e;background:#fff;cursor:pointer;font-family:inherit}
.at-prios{display:flex;gap:4px}
.at-priobtn{display:inline-flex;align-items:center;gap:3px;padding:3px 8px;border-radius:5px;border:1px solid #e5e7eb;background:#fff;font-size:.62rem;font-weight:600;cursor:pointer;color:#6b7280;transition:all .12s;font-family:inherit}
.at-priobtn.active,.at-priobtn:hover{background:#f3f4f6}
.ppb-urgent.active{background:#fef3c7;color:#d97706;border-color:#fde68a}
.ppb-bloquant.active{background:#fef2f2;color:#dc2626;border-color:#fecaca}
.at-chat-row{display:flex;gap:8px;align-items:flex-end}
.at-chat-ta{flex:1;border:1px solid #d1d5db;border-radius:8px;padding:8px 10px;font-size:.77rem;color:#1a1a2e;font-family:inherit;resize:none;outline:none;background:#fff;transition:border-color .12s}
.at-chat-ta:focus{border-color:#2563EB}
.at-chat-send{width:34px;height:34px;border-radius:8px;border:none;background:#2563EB;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.82rem;transition:filter .12s}
.at-chat-send:disabled{opacity:.35;cursor:not-allowed}
.at-chat-send:not(:disabled):hover{filter:brightness(1.12)}
.at-chat-hint{font-size:.57rem;color:#94a3b8}
.spin-dot{width:14px;height:14px;border-radius:50%;border:2px solid currentColor;border-top-color:transparent;animation:spin .6s linear infinite;display:inline-block}
.spin-dot--sm{width:12px;height:12px}
@keyframes spin{to{transform:rotate(360deg)}}
</style>