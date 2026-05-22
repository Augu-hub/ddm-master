<template>
  <VerticalLayoutAudit>
    <div class="rk-shell">

      <!-- ══ HEADER ══ -->
      <header class="rk-header">
        <div class="rk-hrow">
          <div class="rk-hinfo">
            <div class="rk-chips">
              <span class="rk-chip chip-type"><i class="ti ti-shield-exclamation"></i> Gestion des Risques</span>
              <span v-if="props.activeSession" class="rk-chip chip-session">
                <i class="ti ti-player-play"></i> {{ props.activeSession.code }}
              </span>
              <span v-else class="rk-chip chip-warn"><i class="ti ti-alert-triangle"></i> Aucune session active</span>
              <span class="rk-chip chip-count">{{ allRisks.length }} risque(s)</span>
            </div>
            <h1 class="rk-title">Gestion des Risques — Analyse &amp; IA</h1>
            <div class="rk-meta">
              <span v-if="props.activeSession?.entity_name"><i class="ti ti-building"></i>{{ props.activeSession.entity_name }}</span>
              <span v-if="props.activeSession?.exercise_name"><i class="ti ti-calendar"></i>{{ props.activeSession.exercise_name }}</span>
              <span v-if="stats.critical > 0" class="rk-meta-crit"><i class="ti ti-alert-octagon"></i>{{ stats.critical }} critique(s)</span>
            </div>
          </div>
          <div class="rk-hbtns">
            <div class="rk-search-wrap">
              <i class="ti ti-search rk-search-ico"></i>
              <input v-model="searchQuery" class="rk-search" placeholder="Rechercher…"/>
              <button v-if="searchQuery" class="rk-search-clr" @click="searchQuery=''"><i class="ti ti-x"></i></button>
            </div>
            <button class="btn btn-ghost btn-sm" @click="showSessionModal=true" :disabled="!(props.allSessions as any[]).length">
              <i class="ti ti-refresh"></i> Session
            </button>
            <button class="btn btn-save" @click="openCreate">
              <i class="ti ti-plus"></i> Créer un risque
            </button>
          </div>
        </div>
        <div v-if="!props.activeSession" class="rk-banner banner-warn">
          <i class="ti ti-alert-triangle"></i>
          Aucune session active — activez une session pour lier les nouveaux risques.
        </div>
      </header>

      <!-- ══ BODY ══ -->
      <div class="rk-body">

        <!-- Stats bar -->
        <div v-if="allRisks.length" class="rk-stats-bar">
          <div class="rk-stat-item" style="border-top-color:#ef4444">
            <span class="rk-stat-n" style="color:#ef4444">{{ stats.critical }}</span>
            <span class="rk-stat-l">Critique</span>
          </div>
          <div class="rk-stat-item" style="border-top-color:#f97316">
            <span class="rk-stat-n" style="color:#f97316">{{ stats.high }}</span>
            <span class="rk-stat-l">Élevé</span>
          </div>
          <div class="rk-stat-item" style="border-top-color:#facc15">
            <span class="rk-stat-n" style="color:#92400e">{{ stats.medium }}</span>
            <span class="rk-stat-l">Moyen</span>
          </div>
          <div class="rk-stat-item" style="border-top-color:#22c55e">
            <span class="rk-stat-n" style="color:#15803d">{{ stats.low }}</span>
            <span class="rk-stat-l">Faible</span>
          </div>
          <div class="rk-stat-item" style="border-top-color:#94a3b8">
            <span class="rk-stat-n">{{ stats.total }}</span>
            <span class="rk-stat-l">Total</span>
          </div>
        </div>

        <!-- Filtres -->
        <section class="card">
          <div class="card-label"><i class="ti ti-filter"></i> Filtres</div>
          <div class="card-body">
            <div class="filters-grid">
              <div class="field">
                <label class="lbl">Entité</label>
                <select v-model.number="filterEntityId" class="inp inp-sm">
                  <option :value="null">— Toutes —</option>
                  <option v-for="e in (props.entities as any[])" :key="e.id" :value="e.id">{{ e.code_base }} – {{ e.name }}</option>
                </select>
              </div>
              <div class="field">
                <label class="lbl">Processus</label>
                <select v-model.number="filterProcessId" class="inp inp-sm" @change="filterActivityId=null">
                  <option :value="null">— Tous —</option>
                  <option v-for="p in (props.processes as any[])" :key="p.id" :value="p.id">{{ p.code }} – {{ p.name }}</option>
                </select>
              </div>
              <div class="field">
                <label class="lbl">Activité</label>
                <select v-model.number="filterActivityId" class="inp inp-sm">
                  <option :value="null">— Toutes —</option>
                  <option v-for="a in filteredActivitiesForFilter" :key="a.id" :value="a.id">{{ a.code }} – {{ a.name }}</option>
                </select>
              </div>
              <div class="field">
                <label class="lbl">Type risque</label>
                <select v-model.number="filterTypeId" class="inp inp-sm">
                  <option :value="null">— Tous —</option>
                  <option v-for="t in (props.riskTypes as any[])" :key="t.id" :value="t.id">{{ t.code }} – {{ t.label }}</option>
                </select>
              </div>
            </div>
          </div>
        </section>

        <!-- Vue hiérarchique -->
        <section class="card card-tbl">
          <div class="card-label"><i class="ti ti-layout-rows"></i> Vue hiérarchique</div>

          <!-- Risques sans processus/activité -->
          <div v-if="orphanRisks.length" class="orphan-block">
            <div class="orphan-hd"><i class="ti ti-alert-circle"></i> Risques sans processus/activité assignés ({{ orphanRisks.length }})</div>
            <div class="risk-tbl-hd risk-tbl-hd-orphan">
              <span class="rc-code">CODE</span>
              <span class="rc-label">LIBELLÉ</span>
              <span class="rc-type">TYPE</span>
              <span class="rc-owner">RESPONSABLE</span>
              <span class="rc-lv">IMP.B</span>
              <span class="rc-lv">FRQ.B</span>
              <span class="rc-crit">CRIT.B</span>
              <span class="rc-stat">STATUT</span>
              <span class="rc-acts"></span>
            </div>
            <div v-for="risk in orphanRisks" :key="risk.id" class="risk-row risk-row-orphan"
                 :style="{'border-left-color': critColor(risk.criticality_gross||0)}">
              <span class="rc-code">
                <span class="hier-code-badge" :style="{background: typeColor(risk.risk_type_id)+'15', borderColor: typeColor(risk.risk_type_id)+'55'}">
                  <span class="hcb-prefix" :style="{color: typeColor(risk.risk_type_id)}">{{ codePrefix(risk.code) }}</span>
                  <span class="hcb-seq">{{ codeSeq(risk.code) }}</span>
                </span>
              </span>
              <span class="rc-label" :title="risk.label">{{ truncate(risk.label, 45) }}</span>
              <span class="rc-type">
                <span class="type-badge" :style="{background: typeColor(risk.risk_type_id)+'22', color: typeColor(risk.risk_type_id)}">{{ typeCode(risk.risk_type_id) }}</span>
              </span>
              <span class="rc-owner small">{{ truncate(risk.owner_function_name || risk.owner, 20) || '—' }}</span>
              <span class="rc-lv">
                <span v-if="risk.impact_level" class="lv-pill" :style="{background: risk.impact_color}">{{ risk.impact_level }}</span>
                <span v-else class="dash">—</span>
              </span>
              <span class="rc-lv">
                <span v-if="risk.frequency_level" class="lv-pill" :style="{background: risk.frequency_color}">{{ risk.frequency_level }}</span>
                <span v-else class="dash">—</span>
              </span>
              <span class="rc-crit">
                <span v-if="risk.criticality_gross" class="crit-pill" :style="{background: critColor(risk.criticality_gross)}">
                  {{ risk.criticality_gross }}<small>{{ critLabel(risk.criticality_gross) }}</small>
                </span>
                <span v-else class="dash">—</span>
              </span>
              <span class="rc-stat">
                <span class="status-badge" :class="'st-'+risk.status">{{ statusLbl(risk.status) }}</span>
              </span>
              <span class="rc-acts">
                <button class="act-btn act-edit" @click="openEdit(risk)"><i class="ti ti-pencil"></i></button>
                <button class="act-btn act-del"  @click="deleteRisk(risk)"><i class="ti ti-trash"></i></button>
              </span>
            </div>
          </div>

          <!-- Vide total -->
          <div v-if="!allRisks.length" class="rk-empty">
            <i class="ti ti-shield-off"></i>
            <strong>Aucun risque</strong>
            <p>Créez votre premier risque avec le bouton "Créer un risque".</p>
          </div>
          <div v-else-if="!visibleProcesses.length && !orphanRisks.length" class="rk-empty">
            <i class="ti ti-filter-off"></i>
            <strong>Aucun résultat</strong>
            <p>Aucun risque ne correspond aux filtres sélectionnés.</p>
          </div>

          <!-- Hiérarchie Processus > Activités > Risques -->
          <div v-if="visibleProcesses.length" class="hier-wrap">
            <div v-for="proc in visibleProcesses" :key="proc.id" class="proc-block">
              <div class="proc-hd" @click="toggleProcess(proc.id)">
                <div class="proc-toggle">
                  <i class="ti" :class="openProcesses.has(proc.id) ? 'ti-chevron-down' : 'ti-chevron-right'"></i>
                </div>
                <div class="proc-info">
                  <!-- Hiérarchie macro-proc.proc -->
                  <div class="proc-hier">
                    <span v-if="getMacroForProcess(proc.id)" class="hier-seg seg-macro">{{ getMacroForProcess(proc.id)?.code }}</span>
                    <span v-if="getMacroForProcess(proc.id)" class="hier-dot">.</span>
                    <span class="hier-seg seg-proc">{{ proc.code }}</span>
                  </div>
                  <span class="proc-name">{{ proc.name }}</span>
                </div>
                <div class="proc-meta">
                  <span class="proc-count">{{ countRisksForProcess(proc.id) }} risque(s)</span>
                </div>
              </div>

              <div v-show="openProcesses.has(proc.id)" class="act-list">
                <div v-if="!getActivitiesForProcess(proc.id).length" class="act-empty">
                  <i class="ti ti-layout-list"></i> Aucune activité
                </div>
                <div v-for="act in getActivitiesForProcess(proc.id)" :key="act.id" class="act-block">
                  <div class="act-hd" @click="toggleActivity(act.id)">
                    <div class="act-toggle">
                      <i class="ti" :class="openActivities.has(act.id) ? 'ti-chevron-down' : 'ti-chevron-right'"></i>
                    </div>
                    <div class="act-info">
                      <!-- Hiérarchie macro.proc.act -->
                      <div class="act-hier">
                        <span v-if="getMacroForProcess(proc.id)" class="hier-seg seg-macro-sm">{{ getMacroForProcess(proc.id)?.code }}</span>
                        <span v-if="getMacroForProcess(proc.id)" class="hier-dot">.</span>
                        <span class="hier-seg seg-proc-sm">{{ proc.code }}</span>
                        <span class="hier-dot">.</span>
                        <span class="hier-seg seg-act">{{ act.code }}</span>
                      </div>
                      <span class="act-name">{{ act.name }}</span>
                    </div>
                    <div class="act-stats" v-if="getRisksForActivity(act.id).length">
                      <span v-for="s in activityStats(act.id)" :key="s.label"
                            class="act-stat-pill" :style="{background: s.color, color: '#fff'}">
                        {{ s.label }}: {{ s.count }}
                      </span>
                    </div>
                    <div class="act-meta">
                      <span class="act-count">{{ getRisksForActivity(act.id).length }} risque(s)</span>
                      <button class="btn btn-save btn-xs" @click.stop="openCreateForActivity(proc, act)">
                        <i class="ti ti-plus"></i> Ajouter
                      </button>
                    </div>
                  </div>

                  <div v-show="openActivities.has(act.id)" class="risks-list">
                    <div v-if="!getRisksForActivity(act.id).length" class="risks-empty">
                      <i class="ti ti-shield"></i> Aucun risque — cliquez "Ajouter"
                    </div>
                    <div v-else>
                      <div class="risk-tbl-hd">
                        <span class="rc-code">CODE HIÉRARCHIQUE</span>
                        <span class="rc-label">LIBELLÉ</span>
                        <span class="rc-type">TYPE</span>
                        <span class="rc-owner">RESPONSABLE</span>
                        <span class="rc-lv">IMP.B</span>
                        <span class="rc-lv">FRQ.B</span>
                        <span class="rc-crit">CRIT.B</span>
                        <span class="rc-lv">IMP.N</span>
                        <span class="rc-lv">FRQ.N</span>
                        <span class="rc-crit">CRIT.N</span>
                        <span class="rc-stat">STATUT</span>
                        <span class="rc-acts"></span>
                      </div>
                      <div v-for="risk in getRisksForActivity(act.id)" :key="risk.id"
                           class="risk-row" :style="{'border-left-color': critColor(risk.criticality_gross||0)}">
                        <!-- Code hiérarchique inline -->
                        <span class="rc-code">
                          <span class="hier-code-badge" :style="{background: typeColor(risk.risk_type_id)+'12', borderColor: typeColor(risk.risk_type_id)+'44'}">
                            <span class="hcb-parts">
                              <template v-for="(part, pi) in (risk.code_hierarchy?.parts ?? [])" :key="pi">
                                <span class="hcb-part" :class="pi===0?'seg-macro':pi===1?'seg-proc':'seg-act'">{{ part }}</span>
                                <span v-if="pi < (risk.code_hierarchy?.parts?.length??0)-1" class="hcb-dot">.</span>
                              </template>
                            </span>
                            <span class="hcb-sep">-</span>
                            <span class="hcb-seq" :style="{color: typeColor(risk.risk_type_id)}">{{ risk.code_hierarchy?.sequence ?? '' }}</span>
                          </span>
                        </span>
                        <span class="rc-label" :title="risk.label">{{ truncate(risk.label, 38) }}</span>
                        <span class="rc-type">
                          <span class="type-badge" :style="{background: typeColor(risk.risk_type_id)+'22', color: typeColor(risk.risk_type_id)}">{{ typeCode(risk.risk_type_id) }}</span>
                        </span>
                        <span class="rc-owner small">{{ truncate(risk.owner_function_name || risk.owner, 16) || '—' }}</span>
                        <span class="rc-lv">
                          <span v-if="risk.impact_level" class="lv-pill" :style="{background: risk.impact_color}">{{ risk.impact_level }}</span>
                          <span v-else class="dash">—</span>
                        </span>
                        <span class="rc-lv">
                          <span v-if="risk.frequency_level" class="lv-pill" :style="{background: risk.frequency_color}">{{ risk.frequency_level }}</span>
                          <span v-else class="dash">—</span>
                        </span>
                        <span class="rc-crit">
                          <span v-if="risk.criticality_gross" class="crit-pill" :style="{background: critColor(risk.criticality_gross)}">
                            {{ risk.criticality_gross }}<small>{{ critLabel(risk.criticality_gross) }}</small>
                          </span>
                          <span v-else class="dash">—</span>
                        </span>
                        <span class="rc-lv">
                          <span v-if="risk.impact_net" class="lv-pill" :style="{background: netLevelColor(risk.impact_net)}">{{ risk.impact_net }}</span>
                          <span v-else class="dash">—</span>
                        </span>
                        <span class="rc-lv">
                          <span v-if="risk.frequency_net" class="lv-pill" :style="{background: netLevelColor(risk.frequency_net)}">{{ risk.frequency_net }}</span>
                          <span v-else class="dash">—</span>
                        </span>
                        <span class="rc-crit">
                          <span v-if="risk.criticality_net" class="crit-pill" :style="{background: critColor(risk.criticality_net)}">
                            {{ risk.criticality_net }}<small>{{ critLabel(risk.criticality_net) }}</small>
                          </span>
                          <span v-else class="dash">—</span>
                        </span>
                        <span class="rc-stat">
                          <span class="status-badge" :class="'st-'+risk.status">{{ statusLbl(risk.status) }}</span>
                        </span>
                        <span class="rc-acts">
                          <button class="act-btn act-edit" @click="openEdit(risk)"><i class="ti ti-pencil"></i></button>
                          <button class="act-btn act-dup"  @click="openDuplicate(risk)"><i class="ti ti-copy"></i></button>
                          <button class="act-btn act-del"  @click="deleteRisk(risk)"><i class="ti ti-trash"></i></button>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </section>

      </div><!-- /rk-body -->
    </div><!-- /rk-shell -->

    <!-- ══ MODAL RISQUE ══ -->
    <Teleport to="body">
      <transition name="mfade">
        <div v-if="showModal" class="modal-ov" @click.self="closeModal">
          <div class="modal-box modal-xl">

            <!-- Header -->
            <div class="modal-hd">
              <div class="modal-hd-l">
                <i :class="editingId && !isDuplicating ? 'ti ti-pencil' : isDuplicating ? 'ti ti-copy' : 'ti ti-plus'"></i>
                {{ editingId && !isDuplicating ? 'Modifier le risque' : isDuplicating ? 'Dupliquer' : 'Créer un risque' }}
                <!-- Aperçu du code hiérarchique calculé -->
                <span v-if="previewCode" class="modal-code-hier">
                  <template v-for="(part, pi) in previewCode.parts" :key="pi">
                    <span class="modal-code-part" :class="pi===0?'seg-macro':pi===1?'seg-proc':'seg-act'">{{ part }}</span>
                    <span v-if="pi < previewCode.parts.length-1" class="modal-code-dot">.</span>
                  </template>
                  <span class="modal-code-dot">-</span>
                  <span class="modal-code-seq">NNN</span>
                </span>
              </div>
              <button class="modal-cls" @click="closeModal"><i class="ti ti-x"></i></button>
            </div>

            <!-- Scroll zone -->
            <div class="modal-scroll">
              <div class="modal-cols">

                <!-- ── Col gauche ── -->
                <div class="modal-col-left">

                  <!-- 1 Contexte -->
                  <div class="modal-section">
                    <div class="modal-section-title"><span class="step-badge">1</span> Contexte &amp; Codification</div>

                    <!-- Aperçu code hiérarchique -->
                    <div v-if="previewCode" class="code-preview-box">
                      <div class="cpb-label"><i class="ti ti-barcode"></i> Code généré automatiquement</div>
                      <div class="cpb-code">
                        <template v-for="(part, pi) in previewCode.parts" :key="pi">
                          <span class="cpb-part" :class="pi===0?'cpb-macro':pi===1?'cpb-proc':'cpb-act'">{{ part }}</span>
                          <span v-if="pi < previewCode.parts.length-1" class="cpb-dot">.</span>
                        </template>
                        <span class="cpb-dot">-</span>
                        <span class="cpb-seq">NNN</span>
                      </div>
                      <div class="cpb-legend">
                        <span v-if="previewCode.parts[0]" class="cpb-leg-item"><span class="cpb-bullet cpb-macro"></span>Macro-processus</span>
                        <span v-if="previewCode.parts[1]" class="cpb-leg-item"><span class="cpb-bullet cpb-proc"></span>Processus</span>
                        <span v-if="previewCode.parts[2]" class="cpb-leg-item"><span class="cpb-bullet cpb-act"></span>Activité</span>
                        <span class="cpb-leg-item"><span class="cpb-bullet cpb-seq"></span>Séquence auto</span>
                      </div>
                    </div>
                    <div v-else class="code-preview-empty">
                      <i class="ti ti-info-circle"></i>
                      Sélectionnez un processus et une activité pour voir le code hiérarchique.
                    </div>

                    <div class="form-grid2" style="margin-top:10px">
                      <div class="field">
                        <label class="lbl">Type de risque <span class="req">*</span></label>
                        <select v-model.number="form.risk_type_id" class="inp inp-sm" @change="onTypeChange">
                          <option :value="null">— Sélectionner —</option>
                          <option v-for="t in (props.riskTypes as any[])" :key="t.id" :value="t.id">{{ t.code }} – {{ t.label }}</option>
                        </select>
                        <div v-if="selectedType" class="type-preview" :style="{background: typeColor(selectedType.id)+'15', borderColor: typeColor(selectedType.id)}">
                          <span class="type-dot" :style="{background: typeColor(selectedType.id)}"></span>
                          {{ selectedType.code }} — {{ selectedType.label }}
                        </div>
                      </div>
                      <div class="field">
                        <label class="lbl">Entité</label>
                        <select v-model.number="form.entity_id" class="inp inp-sm" @change="onEntityChange">
                          <option :value="null">— Sélectionner —</option>
                          <option v-for="e in (props.entities as any[])" :key="e.id" :value="e.id">{{ e.code_base }} – {{ e.name }}</option>
                        </select>
                      </div>
                    </div>
                    <div class="form-grid2" style="margin-top:8px">
                      <div class="field">
                        <label class="lbl">Processus</label>
                        <select v-model.number="form.process_id" class="inp inp-sm" @change="onProcessChange">
                          <option :value="null">— Sélectionner —</option>
                          <option v-for="p in (props.processes as any[])" :key="p.id" :value="p.id">{{ p.code }} – {{ p.name }}</option>
                        </select>
                      </div>
                      <div class="field">
                        <label class="lbl">Activité
                          <span v-if="form.process_id" class="lbl-filtered">({{ activitiesForForm.length }})</span>
                        </label>
                        <select v-model.number="form.activity_id" class="inp inp-sm" @change="onActivityChange">
                          <option :value="null">— Sélectionner —</option>
                          <option v-for="a in activitiesForForm" :key="a.id" :value="a.id">{{ a.code }} – {{ a.name }}</option>
                        </select>
                        <small v-if="form.process_id && !activitiesForForm.length" class="hint-warn">
                          <i class="ti ti-alert-triangle"></i> Aucune activité
                        </small>
                      </div>
                    </div>
                  </div>

                  <!-- 2 Détails -->
                  <div class="modal-section">
                    <div class="modal-section-title"><span class="step-badge">2</span> Détails</div>
                    <div class="field">
                      <label class="lbl">Libellé <span class="req">*</span></label>
                      <input v-model="form.label" class="inp" placeholder="Libellé du risque…"/>
                    </div>
                    <div class="field" style="margin-top:8px">
                      <label class="lbl">Description</label>
                      <textarea v-model="form.description" class="ta" rows="2" placeholder="Description détaillée…"/>
                    </div>
                    <div class="form-grid2" style="margin-top:8px">
                      <div class="field">
                        <label class="lbl">Fonction responsable</label>
                        <select v-model.number="form.owner_function_id" class="inp inp-sm" @change="onFunctionChange">
                          <option :value="null">— Sélectionner —</option>
                          <option v-for="f in functionsForSelectedEntity" :key="f.id" :value="f.id">{{ f.label }}</option>
                        </select>
                        <small v-if="form.entity_id && !functionsForSelectedEntity.length" class="hint-info">
                          <i class="ti ti-info-circle"></i> Aucune fonction pour cette entité
                        </small>
                      </div>
                      <div class="field">
                        <label class="lbl">Propriétaire (libre)</label>
                        <input v-model="form.owner" class="inp" placeholder="Nom ou titre…"/>
                      </div>
                    </div>
                    <div class="field" style="margin-top:8px">
                      <label class="lbl">Statut</label>
                      <select v-model="form.status" class="inp inp-sm">
                        <option value="identified">Identifié</option>
                        <option value="assessed">Évalué</option>
                        <option value="mitigated">Atténué</option>
                        <option value="monitored">Suivi</option>
                        <option value="closed">Fermé</option>
                      </select>
                    </div>
                  </div>

                  <!-- 3 Évaluation brute -->
                  <div class="modal-section">
                    <div class="modal-section-title"><span class="step-badge">3</span> Évaluation brute</div>
                    <div class="form-grid2">
                      <div class="field">
                        <label class="lbl">Impact brut <span class="req">*</span></label>
                        <select v-model.number="form.impact_level_id" class="inp inp-sm" @change="onBrutChange">
                          <option :value="null">— Sélectionner —</option>
                          <option v-for="i in (props.impacts as any[])" :key="i.id" :value="i.id">{{ i.label }} ({{ i.level }}/5)</option>
                        </select>
                        <div v-if="impactBrut" class="lv-preview" :style="{background: impactBrut.color}">
                          <span>{{ impactBrut.label }}</span><strong>{{ impactBrut.level }}/5</strong>
                        </div>
                      </div>
                      <div class="field">
                        <label class="lbl">Fréquence brute <span class="req">*</span></label>
                        <select v-model.number="form.frequency_level_id" class="inp inp-sm" @change="onBrutChange">
                          <option :value="null">— Sélectionner —</option>
                          <option v-for="f in (props.frequencies as any[])" :key="f.id" :value="f.id">{{ f.label }} ({{ f.level }}/5)</option>
                        </select>
                        <div v-if="frequencyBrut" class="lv-preview" :style="{background: frequencyBrut.color}">
                          <span>{{ frequencyBrut.label }}</span><strong>{{ frequencyBrut.level }}/5</strong>
                        </div>
                      </div>
                    </div>
                    <div v-if="criticityBrut" class="crit-display" :style="{background: critColor(criticityBrut.score)+'18', borderColor: critColor(criticityBrut.score)}">
                      <div class="crit-score" :style="{background: critColor(criticityBrut.score)}">{{ criticityBrut.score }}</div>
                      <div class="crit-info">
                        <strong>Criticité brute</strong>
                        <span>{{ criticityBrut.label }} — {{ criticityBrut.qualification }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- 4 Évaluation nette -->
                  <div class="modal-section">
                    <div class="modal-section-title"><span class="step-badge">4</span> Évaluation nette</div>
                    <div class="form-grid2">
                      <div class="field">
                        <label class="lbl">Impact net <span v-if="impactBrut" class="lbl-max">(max {{ impactBrut.level }})</span></label>
                        <select v-model.number="form.impact_net" class="inp inp-sm">
                          <option :value="null">—</option>
                          <option v-for="lv in netImpactOptions" :key="lv.id" :value="lv.level">{{ lv.label }} ({{ lv.level }})</option>
                        </select>
                        <div v-if="form.impact_net" class="lv-preview" :style="{background: netLevelColor(form.impact_net)}">
                          <span>{{ netImpactLabel(form.impact_net) }}</span><strong>{{ form.impact_net }}/5</strong>
                        </div>
                        <small v-if="impactBrut" class="hint-rule"><i class="ti ti-info-circle"></i> ≤ Impact brut ({{ impactBrut.level }})</small>
                      </div>
                      <div class="field">
                        <label class="lbl">Fréquence nette <span v-if="frequencyBrut" class="lbl-max">(max {{ frequencyBrut.level }})</span></label>
                        <select v-model.number="form.frequency_net" class="inp inp-sm">
                          <option :value="null">—</option>
                          <option v-for="lv in netFrequencyOptions" :key="lv.id" :value="lv.level">{{ lv.label }} ({{ lv.level }})</option>
                        </select>
                        <div v-if="form.frequency_net" class="lv-preview" :style="{background: netLevelColor(form.frequency_net)}">
                          <span>{{ netFrequencyLabel(form.frequency_net) }}</span><strong>{{ form.frequency_net }}/5</strong>
                        </div>
                        <small v-if="frequencyBrut" class="hint-rule"><i class="ti ti-info-circle"></i> ≤ Fréquence brute ({{ frequencyBrut.level }})</small>
                      </div>
                    </div>
                    <div v-if="criticityNet" class="crit-display" :style="{background: critColor(criticityNet)+'18', borderColor: critColor(criticityNet)}">
                      <div class="crit-score" :style="{background: critColor(criticityNet)}">{{ criticityNet }}</div>
                      <div class="crit-info"><strong>Criticité nette</strong><span>{{ criticityNetLabel }}</span></div>
                    </div>
                  </div>

                  <!-- 5 Procédure de contrôle -->
                  <div class="modal-section">
                    <div class="modal-section-title"><span class="step-badge">5</span> Procédure de contrôle</div>
                    <div class="ctrl-row">
                      <textarea v-model="form.control_procedure" class="ta" rows="3" placeholder="Décrivez la procédure de contrôle…"/>
                      <button class="btn-ai-ctrl" @click="generateControl" :disabled="!form.label || !form.process_id || aiControlLoading" title="Générer avec Mistral IA">
                        <i class="ti" :class="aiControlLoading ? 'ti-loader-2 spin' : 'ti-brain'"></i>
                      </button>
                    </div>
                  </div>

                </div><!-- /modal-col-left -->

                <!-- ── Col droite : IA Mistral ── -->
                <div class="modal-col-right">
                  <div class="modal-section ia-section">
                    <div class="modal-section-title">
                      <span class="step-badge step-ai"><i class="ti ti-sparkles"></i></span>
                      Suggestions Mistral AI
                    </div>

                    <div v-if="!form.process_id || !form.activity_id || !form.risk_type_id" class="ia-placeholder">
                      <i class="ti ti-brain"></i>
                      <p>Sélectionnez un <strong>processus</strong>, une <strong>activité</strong> et un <strong>type de risque</strong> pour recevoir des suggestions contextualisées.</p>
                    </div>
                    <div v-else-if="aiLoading" class="ia-loading">
                      <div class="ia-spinner"></div>
                      <span>Mistral génère…</span>
                    </div>
                    <div v-else-if="aiSuggestions.length" class="ia-suggestions">
                      <p class="ia-hint">Cliquez sur une suggestion pour l'appliquer</p>
                      <div v-for="(s, i) in aiSuggestions" :key="i"
                           class="ia-item" :class="{active: form.label === s.label}"
                           @click="selectSuggestion(s)">
                        <div class="ia-item-top">
                          <!-- Pas de code ici : le code est calculé selon le contexte -->
                          <span class="ia-idx">{{ i + 1 }}</span>
                          <i class="ti ti-corner-down-left ia-select-ico"></i>
                        </div>
                        <div class="ia-label">{{ s.label }}</div>
                        <div v-if="s.description" class="ia-desc">{{ s.description }}</div>
                        <!-- Aperçu procédure de contrôle -->
                        <div v-if="s.control_procedure" class="ia-ctrl-preview">
                          <i class="ti ti-shield-check"></i>
                          {{ truncate(s.control_procedure, 80) }}
                        </div>
                      </div>
                    </div>
                    <div v-else class="ia-placeholder">
                      <i class="ti ti-alert-circle"></i><p>Aucune suggestion disponible.</p>
                    </div>

                    <!-- Fonctions de l'entité -->
                    <div v-if="functionsForSelectedEntity.length" class="func-list-box">
                      <div class="func-list-title">
                        <i class="ti ti-users"></i> Fonctions — {{ entityNameById(form.entity_id) }}
                      </div>
                      <div v-for="f in functionsForSelectedEntity" :key="f.id"
                           class="func-item"
                           :class="{active: form.owner_function_id === f.id}"
                           @click="form.owner_function_id = f.id; onFunctionChange()">
                        <span class="func-char">{{ f.character || '—' }}</span>
                        <span class="func-name">{{ f.name }}</span>
                        <i v-if="form.owner_function_id === f.id" class="ti ti-check func-check"></i>
                      </div>
                    </div>

                  </div>
                </div><!-- /modal-col-right -->

              </div><!-- /modal-cols -->
            </div><!-- /modal-scroll -->

            <!-- Footer -->
            <div class="modal-ft">
              <button class="btn btn-ghost" @click="closeModal">Annuler</button>
              <button class="btn btn-save" @click="saveRisk"
                      :disabled="!form.label || !form.risk_type_id || !form.impact_level_id || !form.frequency_level_id || saving">
                <span v-if="saving" class="spin-dot"></span>
                <i v-else class="ti ti-device-floppy"></i>
                {{ editingId && !isDuplicating ? 'Mettre à jour' : isDuplicating ? 'Dupliquer' : 'Créer le risque' }}
              </button>
            </div>

          </div><!-- /modal-box -->
        </div>
      </transition>
    </Teleport>

    <!-- ══ MODAL SESSION ══ -->
    <Teleport to="body">
      <transition name="mfade">
        <div v-if="showSessionModal" class="modal-ov" @click.self="showSessionModal=false">
          <div class="modal-box" style="max-width:500px">
            <div class="modal-hd">
              <div class="modal-hd-l"><i class="ti ti-refresh"></i> Changer de session</div>
              <button class="modal-cls" @click="showSessionModal=false"><i class="ti ti-x"></i></button>
            </div>
            <div class="modal-scroll" style="max-height:360px">
              <div style="padding:12px 16px;display:flex;flex-direction:column;gap:8px">
                <div v-for="session in (props.allSessions as any[])" :key="session.id"
                     class="session-item" :class="{active: session.is_active}"
                     @click="switchSession(session.id)">
                  <div class="session-item-info">
                    <div class="session-code">{{ session.code }}</div>
                    <div class="session-name">{{ session.name }}</div>
                    <div class="session-meta">{{ session.entity_name }} · {{ session.exercise_name }}</div>
                  </div>
                  <div class="session-item-right">
                    <span class="session-badge" :class="session.is_active ? 'badge-active' : 'badge-inactive'">
                      {{ session.is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <span class="session-risks">{{ session.risks_count }} risques</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-ft"><button class="btn btn-ghost" @click="showSessionModal=false">Fermer</button></div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- Toast -->
    <Teleport to="body">
      <transition name="toast-up">
        <div v-if="toast.show" class="toast" :class="`toast-${toast.type}`">
          <i :class="toast.type==='success' ? 'ti ti-circle-check' : 'ti ti-alert-circle'"></i>
          {{ toast.msg }}
        </div>
      </transition>
    </Teleport>

  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'

const props = defineProps({
  activeSession:   { type: Object, default: null },
  allSessions:     { type: Array,  default: () => [] },
  entities:        { type: Array,  default: () => [] },
  macroProcesses:  { type: Array,  default: () => [] },  // { id, code, name }
  processes:       { type: Array,  default: () => [] },  // { id, code, name, macro_process_id }
  activities:      { type: Array,  default: () => [] },
  riskTypes:       { type: Array,  default: () => [] },
  frequencies:     { type: Array,  default: () => [] },
  impacts:         { type: Array,  default: () => [] },
  matrix:          { type: Array,  default: () => [] },
  initialRisks:    { type: Array,  default: () => [] },
  statistics:      { type: Object, default: () => ({}) },
  entityFunctions: { type: Object, default: () => ({}) },
})

const csrf = () => (usePage().props as any).csrf ?? ''
const API_BASE = '/api/m/risk.core'

// ── State ─────────────────────────────────────────────────────────────────────
const allRisks         = ref<any[]>([])
const showModal        = ref(false)
const showSessionModal = ref(false)
const editingId        = ref<number|null>(null)
const isDuplicating    = ref(false)
const saving           = ref(false)
const aiLoading        = ref(false)
const aiControlLoading = ref(false)
const aiSuggestions    = ref<any[]>([])
const searchQuery      = ref('')
const filterEntityId   = ref<number|null>(null)
const filterProcessId  = ref<number|null>(null)
const filterActivityId = ref<number|null>(null)
const filterTypeId     = ref<number|null>(null)
const openProcesses    = ref(new Set<number>())
const openActivities   = ref(new Set<number>())

const form = reactive({
  label: '', description: '',
  risk_type_id: null as number|null,
  frequency_level_id: null as number|null, frequency_net: null as number|null,
  impact_level_id: null as number|null,    impact_net: null as number|null,
  entity_id: null as number|null, process_id: null as number|null, activity_id: null as number|null,
  owner_function_id: null as number|null,
  owner: '', control_procedure: '', status: 'identified',
})

const toast = ref({ show: false, type: 'success', msg: '' })
let toastTimer: any = null

onMounted(() => {
  allRisks.value = [...(props.initialRisks as any[])]
  allRisks.value.forEach((r: any) => {
    if (r.process_id)  openProcesses.value.add(r.process_id)
    if (r.activity_id) openActivities.value.add(r.activity_id)
  })
})

// ── Computed ──────────────────────────────────────────────────────────────────
const impactBrut = computed(() =>
  form.impact_level_id ? (props.impacts as any[]).find((i: any) => i.id === form.impact_level_id) : null
)
const frequencyBrut = computed(() =>
  form.frequency_level_id ? (props.frequencies as any[]).find((f: any) => f.id === form.frequency_level_id) : null
)
const selectedType = computed(() =>
  form.risk_type_id ? (props.riskTypes as any[]).find((t: any) => t.id === form.risk_type_id) : null
)
const criticityBrut = computed(() => {
  if (!impactBrut.value || !frequencyBrut.value) return null
  const score = impactBrut.value.level * frequencyBrut.value.level
  const cell  = (props.matrix as any[]).find((m: any) =>
    m.impact_level === impactBrut.value!.level && m.frequency_level === frequencyBrut.value!.level)
  return { score, label: cell?.label ?? '—', qualification: cell?.qualification ?? '—' }
})
const netImpactOptions = computed(() =>
  impactBrut.value ? (props.impacts as any[]).filter((i: any) => i.level <= impactBrut.value!.level) : props.impacts as any[]
)
const netFrequencyOptions = computed(() =>
  frequencyBrut.value ? (props.frequencies as any[]).filter((f: any) => f.level <= frequencyBrut.value!.level) : props.frequencies as any[]
)
const criticityNet = computed(() =>
  form.frequency_net && form.impact_net ? Math.round(form.frequency_net * form.impact_net) : null
)
const criticityNetLabel = computed(() => {
  if (!criticityNet.value) return '—'
  const cell = (props.matrix as any[]).find((m: any) => m.criticality_score === criticityNet.value)
  return cell?.label ?? critLabel(criticityNet.value)
})
const activitiesForForm = computed(() =>
  form.process_id ? (props.activities as any[]).filter((a: any) => a.process_id === form.process_id) : []
)
const filteredActivitiesForFilter = computed(() =>
  filterProcessId.value
    ? (props.activities as any[]).filter((a: any) => a.process_id === filterProcessId.value)
    : props.activities as any[]
)
const functionsForSelectedEntity = computed(() => {
  if (!form.entity_id) return []
  return (props.entityFunctions as any)[form.entity_id] ?? []
})

/**
 * Calcul du code hiérarchique en temps réel pour l'aperçu dans le modal.
 * Parts : [macroCode?, processCode?, activityCode?]
 */
const previewCode = computed(() => {
  const parts: string[] = []
  if (form.process_id) {
    const proc = (props.processes as any[]).find((p: any) => p.id === form.process_id)
    if (proc) {
      if (proc.macro_process_id) {
        const macro = (props.macroProcesses as any[]).find((m: any) => m.id === proc.macro_process_id)
        if (macro?.code) parts.push(macro.code.replace(/[^A-Za-z0-9]/g, '').toUpperCase())
      }
      if (proc.code) parts.push(proc.code.replace(/[^A-Za-z0-9]/g, '').toUpperCase())
    }
  }
  if (form.activity_id) {
    const act = (props.activities as any[]).find((a: any) => a.id === form.activity_id)
    if (act?.code) parts.push(act.code.replace(/[^A-Za-z0-9]/g, '').toUpperCase())
  }
  if (!parts.length && form.risk_type_id && selectedType.value?.code) {
    parts.push(selectedType.value.code.substring(0, 3).toUpperCase())
  }
  if (!parts.length) return null
  return { parts, prefix: parts.join('.') }
})

const filteredRisks = computed(() => {
  const q = searchQuery.value.toLowerCase()
  return allRisks.value.filter((r: any) => {
    if (filterEntityId.value   && r.entity_id   !== filterEntityId.value)   return false
    if (filterProcessId.value  && r.process_id  !== filterProcessId.value)  return false
    if (filterActivityId.value && r.activity_id !== filterActivityId.value) return false
    if (filterTypeId.value     && r.risk_type_id !== filterTypeId.value)    return false
    if (q && !(r.code?.toLowerCase().includes(q) || r.label?.toLowerCase().includes(q))) return false
    return true
  })
})

const orphanRisks = computed(() =>
  filteredRisks.value.filter((r: any) => !r.process_id || !r.activity_id)
)

const visibleProcesses = computed(() => {
  let procs = props.processes as any[]
  if (filterProcessId.value) procs = procs.filter((p: any) => p.id === filterProcessId.value)
  return procs.filter((p: any) => {
    const acts = getActivitiesForProcess(p.id)
    return acts.some((a: any) => getRisksForActivity(a.id).length > 0)
  })
})

const stats = computed(() => {
  const r = filteredRisks.value
  return {
    total:    r.length,
    critical: r.filter((x: any) => (x.criticality_gross||0) >= 15).length,
    high:     r.filter((x: any) => { const c=x.criticality_gross||0; return c>=9&&c<15 }).length,
    medium:   r.filter((x: any) => { const c=x.criticality_gross||0; return c>=4&&c<9 }).length,
    low:      r.filter((x: any) => (x.criticality_gross||0) < 4).length,
  }
})

// ── Hiérarchie helpers ────────────────────────────────────────────────────────
function getActivitiesForProcess(pid: number): any[] {
  return (props.activities as any[]).filter((a: any) => a.process_id === pid)
}
function getRisksForActivity(aid: number): any[] {
  return filteredRisks.value.filter((r: any) => r.activity_id === aid && r.process_id)
}
function countRisksForProcess(pid: number): number {
  return getActivitiesForProcess(pid).reduce((s, a) => s + getRisksForActivity(a.id).length, 0)
}
function getMacroForProcess(pid: number): any|null {
  const proc = (props.processes as any[]).find((p: any) => p.id === pid)
  if (!proc?.macro_process_id) return null
  return (props.macroProcesses as any[]).find((m: any) => m.id === proc.macro_process_id) ?? null
}
function activityStats(aid: number) {
  const risks = getRisksForActivity(aid); const s = []
  const crit = risks.filter((r: any) => (r.criticality_gross||0) >= 15).length
  const high = risks.filter((r: any) => { const c=r.criticality_gross||0; return c>=9&&c<15 }).length
  const med  = risks.filter((r: any) => { const c=r.criticality_gross||0; return c>=4&&c<9 }).length
  if (crit) s.push({ label:'Critique', count:crit, color:'#ef4444' })
  if (high) s.push({ label:'Élevé',    count:high, color:'#f97316' })
  if (med)  s.push({ label:'Moyen',    count:med,  color:'#f59e0b' })
  return s
}
function toggleProcess(id: number) { openProcesses.value.has(id) ? openProcesses.value.delete(id) : openProcesses.value.add(id) }
function toggleActivity(id: number) { openActivities.value.has(id) ? openActivities.value.delete(id) : openActivities.value.add(id) }
function entityNameById(id: number|null): string {
  if (!id) return '—'
  return (props.entities as any[]).find((e: any) => e.id === id)?.name ?? '—'
}

// Décomposer code pour affichage dans les orphans (fallback)
function codePrefix(code: string): string {
  return code?.replace(/-\d+$/, '') ?? ''
}
function codeSeq(code: string): string {
  return code?.match(/-(\d+)$/)?.[1] ?? ''
}

// ── Couleurs ──────────────────────────────────────────────────────────────────
function critColor(score: number): string {
  if (!score) return '#94a3b8'
  if (score >= 15) return '#ef4444'
  if (score >= 9)  return '#f97316'
  if (score >= 4)  return '#facc15'
  return '#22c55e'
}
function critLabel(score: number): string {
  if (!score) return '—'
  if (score >= 15) return 'Critique'
  if (score >= 9)  return 'Élevé'
  if (score >= 4)  return 'Moyen'
  return 'Faible'
}
function netLevelColor(level: number): string {
  if (level <= 1) return '#22c55e'; if (level <= 2) return '#a3e635'
  if (level <= 3) return '#facc15'; if (level <= 4) return '#f97316'
  return '#ef4444'
}
function typeColor(typeId: number|null): string {
  if (!typeId) return '#6c757d'
  return (props.riskTypes as any[]).find((t: any) => t.id === typeId)?.color ?? '#6c757d'
}
function typeCode(typeId: number|null): string {
  if (!typeId) return '—'
  return (props.riskTypes as any[]).find((t: any) => t.id === typeId)?.code ?? '—'
}
function netImpactLabel(level: number): string {
  return (props.impacts as any[]).find((i: any) => i.level === level)?.label ?? `Niveau ${level}`
}
function netFrequencyLabel(level: number): string {
  return (props.frequencies as any[]).find((f: any) => f.level === level)?.label ?? `Niveau ${level}`
}
function statusLbl(s: string): string {
  return ({ identified:'Identifié', assessed:'Évalué', mitigated:'Atténué', monitored:'Suivi', closed:'Fermé' } as any)[s] ?? s
}
function truncate(t: string, n: number): string {
  if (!t) return ''; return t.length > n ? t.slice(0, n) + '…' : t
}

// ── Formulaire ────────────────────────────────────────────────────────────────
function onEntityChange()   { form.owner_function_id = null; form.owner = '' }
function onProcessChange()  { form.activity_id = null; triggerAI() }
function onActivityChange() { triggerAI() }
function onTypeChange()     { triggerAI() }
function onBrutChange() {
  if (form.impact_net    && impactBrut.value    && form.impact_net    > impactBrut.value.level)    form.impact_net    = impactBrut.value.level
  if (form.frequency_net && frequencyBrut.value && form.frequency_net > frequencyBrut.value.level) form.frequency_net = frequencyBrut.value.level
}
function onFunctionChange() {
  if (!form.owner_function_id) return
  const f = functionsForSelectedEntity.value.find((x: any) => x.id === form.owner_function_id)
  if (f && !form.owner) form.owner = f.label
}

// ── IA Mistral ────────────────────────────────────────────────────────────────
async function triggerAI() {
  if (!form.process_id || !form.activity_id || !form.risk_type_id) { aiSuggestions.value = []; return }
  aiLoading.value = true
  try {
    const proc = (props.processes  as any[]).find((p: any) => p.id === form.process_id)
    const act  = (props.activities as any[]).find((a: any) => a.id === form.activity_id)
    const type = (props.riskTypes  as any[]).find((t: any) => t.id === form.risk_type_id)
    const res  = await postJson(`${API_BASE}/suggest-ai`, {
      process_name:   proc?.name  ?? '',
      activity_name:  act?.name   ?? '',
      risk_type_name: type?.label ?? '',
    })
    // Les suggestions n'ont pas de code — juste label, description, control_procedure
    aiSuggestions.value = res.suggestions ?? []
  } catch (e: any) {
    aiSuggestions.value = []
  } finally {
    aiLoading.value = false
  }
}

/**
 * Appliquer une suggestion IA :
 * - label  → form.label
 * - description → form.description (si vide)
 * - control_procedure → form.control_procedure (si vide)
 * Pas de code : il sera généré côté serveur selon la hiérarchie
 */
function selectSuggestion(s: any) {
  form.label = s.label ?? s
  if (s.description        && !form.description)        form.description        = s.description
  if (s.control_procedure  && !form.control_procedure)  form.control_procedure  = s.control_procedure
}

async function generateControl() {
  if (!form.label || !form.process_id) return
  aiControlLoading.value = true
  try {
    const proc = (props.processes  as any[]).find((p: any) => p.id === form.process_id)
    const act  = (props.activities as any[]).find((a: any) => a.id === form.activity_id)
    const res  = await postJson(`${API_BASE}/suggest-control`, {
      risk_label:    form.label,
      activity_name: act?.name  ?? '',
      process_name:  proc?.name ?? '',
    })
    form.control_procedure = res.control_procedure ?? ''
  } catch (e: any) {
    showToast('error', 'Erreur IA')
  } finally {
    aiControlLoading.value = false
  }
}

// ── CRUD ──────────────────────────────────────────────────────────────────────
function openCreate() { resetForm(); editingId.value=null; isDuplicating.value=false; showModal.value=true }
function openCreateForActivity(proc: any, act: any) {
  resetForm(); form.process_id=proc.id; form.activity_id=act.id
  editingId.value=null; isDuplicating.value=false; showModal.value=true; triggerAI()
}
function openEdit(risk: any) {
  resetForm()
  Object.assign(form, {
    label: risk.label, description: risk.description??'',
    risk_type_id: risk.risk_type_id, frequency_level_id: risk.frequency_level_id,
    frequency_net: risk.frequency_net, impact_level_id: risk.impact_level_id,
    impact_net: risk.impact_net, entity_id: risk.entity_id,
    process_id: risk.process_id, activity_id: risk.activity_id,
    owner_function_id: risk.owner_function_id??null,
    owner: risk.owner??'', control_procedure: risk.control_procedure??'',
    status: risk.status??'identified',
  })
  editingId.value=risk.id; isDuplicating.value=false; showModal.value=true; triggerAI()
}
function openDuplicate(risk: any) {
  resetForm()
  const { id, code, code_hierarchy, created_at, updated_at, ...rest } = risk
  Object.assign(form, { ...rest, status:'identified' })
  editingId.value=null; isDuplicating.value=true; showModal.value=true; triggerAI()
}
function closeModal() {
  showModal.value=false; editingId.value=null; isDuplicating.value=false
  aiSuggestions.value=[]; resetForm()
}
function resetForm() {
  Object.assign(form, {
    label:'', description:'', risk_type_id:null,
    frequency_level_id:null, frequency_net:null, impact_level_id:null, impact_net:null,
    entity_id:null, process_id:null, activity_id:null, owner_function_id:null,
    owner:'', control_procedure:'', status:'identified',
  })
  aiSuggestions.value=[]
}
async function saveRisk() {
  if (!form.label || !form.risk_type_id || !form.impact_level_id || !form.frequency_level_id) {
    showToast('error', 'Champs obligatoires : libellé, type, impact, fréquence'); return
  }
  saving.value = true
  try {
    const isUpdate = !!editingId.value && !isDuplicating.value
    const url  = isUpdate ? `${API_BASE}/${editingId.value}` : `${API_BASE}/`
    // Ne pas envoyer de code : le serveur le génère selon la hiérarchie
    const payload = { ...form }
    const data = isUpdate ? await putJson(url, payload) : await postJson(url, payload)
    const risk = data.risk ?? data
    if (isUpdate) {
      const idx = allRisks.value.findIndex((r: any) => r.id === editingId.value)
      if (idx >= 0) allRisks.value[idx] = risk
    } else {
      allRisks.value.push(risk)
      if (risk.process_id)  openProcesses.value.add(risk.process_id)
      if (risk.activity_id) openActivities.value.add(risk.activity_id)
    }
    showToast('success', `Risque '${risk.code}' ${isUpdate ? 'modifié' : isDuplicating.value ? 'dupliqué' : 'créé'}`)
    closeModal()
  } catch (e: any) {
    showToast('error', e.message || 'Erreur sauvegarde')
  } finally {
    saving.value = false
  }
}
async function deleteRisk(risk: any) {
  if (!confirm(`Supprimer le risque ${risk.code} — "${truncate(risk.label, 40)}" ?`)) return
  try {
    await deleteReq(`${API_BASE}/${risk.id}`)
    allRisks.value = allRisks.value.filter((r: any) => r.id !== risk.id)
    showToast('success', `Risque '${risk.code}' supprimé`)
  } catch (e: any) { showToast('error', 'Erreur suppression') }
}
async function switchSession(sessionId: number) {
  try {
    await postJson(`${API_BASE}/switch-session`, { session_id: sessionId })
    showSessionModal.value=false; window.location.reload()
  } catch (e: any) { showToast('error', 'Erreur changement session') }
}

// ── HTTP ──────────────────────────────────────────────────────────────────────
const H = () => ({ 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept':'application/json' })
async function postJson(url: string, body: object): Promise<any> {
  const r = await fetch(url, { method:'POST', headers:H(), body:JSON.stringify(body) })
  const d = await r.json()
  if (!r.ok) throw new Error(d?.error || d?.message || `HTTP ${r.status}`)
  return d
}
async function putJson(url: string, body: object): Promise<any> {
  const r = await fetch(url, { method:'PUT', headers:H(), body:JSON.stringify(body) })
  const d = await r.json()
  if (!r.ok) throw new Error(d?.error || d?.message || `HTTP ${r.status}`)
  return d
}
async function deleteReq(url: string): Promise<any> {
  const r = await fetch(url, { method:'DELETE', headers:H() })
  const d = await r.json()
  if (!r.ok) throw new Error(d?.error || d?.message || `HTTP ${r.status}`)
  return d
}

// ── Toast ─────────────────────────────────────────────────────────────────────
function showToast(type: string, msg: string) {
  if (toastTimer) clearTimeout(toastTimer)
  toast.value = { show:true, type, msg }
  toastTimer = setTimeout(() => { toast.value.show = false }, 3500)
}
</script>

<style scoped>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
.rk-shell{display:flex;flex-direction:column;min-height:100vh;background:#f4f6f8;font-family:'Segoe UI',system-ui,sans-serif;--mc:#1565C0;--rd:#dc2626;--gr:#15803d;--ai:#7c3aed}

/* ── Header ── */
.rk-header{position:sticky;top:0;z-index:100;background:#fff;border-bottom:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.06);padding:0 16px}
.rk-hrow{display:flex;align-items:center;gap:10px;min-height:58px;padding:6px 0;flex-wrap:wrap}
.rk-hinfo{flex:1;min-width:0}
.rk-chips{display:flex;align-items:center;gap:4px;flex-wrap:wrap;margin-bottom:2px}
.rk-chip{display:inline-flex;align-items:center;gap:3px;font-size:.6rem;font-weight:700;padding:2px 7px;border-radius:9px;text-transform:uppercase;letter-spacing:.04em}
.chip-type{background:rgba(220,38,38,.12);color:#b91c1c;border:1px solid rgba(220,38,38,.2)}
.chip-session{background:rgba(21,101,192,.12);color:#1565C0}
.chip-count{background:#f1f5f9;color:#64748b}
.chip-warn{background:#fef3c7;color:#d97706}
.rk-title{font-size:.88rem;font-weight:700;color:#1a1a2e}
.rk-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:2px}
.rk-meta span{display:inline-flex;align-items:center;gap:3px;font-size:.67rem;color:#64748b}
.rk-meta-crit{color:#b91c1c!important;font-weight:600}
.rk-hbtns{display:flex;align-items:center;gap:7px;flex-shrink:0;flex-wrap:wrap}
.rk-search-wrap{position:relative;display:flex;align-items:center}
.rk-search-ico{position:absolute;left:8px;color:#94a3b8;font-size:.78rem;pointer-events:none}
.rk-search{border:1px solid #e2e8f0;border-radius:7px;padding:5px 28px;font-size:.74rem;color:#374151;font-family:inherit;outline:none;width:180px;background:#f8fafc;transition:all .15s}
.rk-search:focus{border-color:var(--mc);background:#fff;width:210px}
.rk-search-clr{position:absolute;right:6px;background:none;border:none;color:#94a3b8;cursor:pointer;font-size:.72rem}
.rk-banner{display:flex;align-items:center;gap:6px;padding:5px 0 8px;font-size:.75rem;border-top:1px solid #f1f5f9;color:#d97706}

/* ── Body ── */
.rk-body{flex:1;padding:12px 16px 24px;display:flex;flex-direction:column;gap:12px}

/* ── Stats ── */
.rk-stats-bar{display:flex;gap:8px;flex-wrap:wrap}
.rk-stat-item{display:flex;flex-direction:column;align-items:center;padding:8px 14px;background:#fff;border:1px solid #e2e8f0;border-top-width:3px;border-radius:8px;min-width:70px;text-align:center}
.rk-stat-n{font-size:1.2rem;font-weight:800;line-height:1;color:#374151}
.rk-stat-l{font-size:.6rem;color:#94a3b8;font-weight:600;text-transform:uppercase;margin-top:2px}

/* ── Cards ── */
.card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;position:relative}
.card-label{position:absolute;top:-10px;left:14px;background:#fff;padding:0 8px;font-size:.63rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--mc);border:1px solid rgba(21,101,192,.3);border-radius:4px;display:inline-flex;align-items:center;gap:5px;z-index:1;white-space:nowrap}
.card-body{padding:18px 14px 14px;display:flex;flex-direction:column;gap:9px}
.card-tbl{overflow:hidden}

/* ── Filtres ── */
.filters-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
@media(max-width:900px){.filters-grid{grid-template-columns:1fr 1fr}}

/* ── Fields ── */
.field{display:flex;flex-direction:column;gap:3px}
.lbl{font-size:.7rem;font-weight:600;color:#475569}
.lbl-max{font-size:.62rem;color:var(--mc);font-weight:700;background:rgba(21,101,192,.1);padding:1px 5px;border-radius:4px;margin-left:4px}
.lbl-filtered{font-size:.62rem;color:#15803d;font-weight:600;margin-left:3px}
.req{color:#dc2626}
.inp{width:100%;border:1px solid #d1d5db;border-radius:6px;padding:6px 10px;font-size:.8rem;color:#1a1a2e;background:#fff;outline:none;font-family:inherit;transition:border-color .12s}
.inp:focus{border-color:var(--mc);box-shadow:0 0 0 2px rgba(21,101,192,.1)}
.inp-sm{padding:4px 8px;font-size:.76rem}
.ta{width:100%;border:1px solid #d1d5db;border-radius:6px;padding:6px 10px;font-size:.8rem;color:#1a1a2e;font-family:inherit;resize:vertical;outline:none;transition:border-color .12s}
.ta:focus{border-color:var(--mc)}
.form-grid2{display:grid;grid-template-columns:1fr 1fr;gap:9px}
.hint-rule{color:#94a3b8;font-size:.64rem;display:flex;align-items:center;gap:3px;margin-top:2px}
.hint-warn{color:#d97706;font-size:.64rem;display:flex;align-items:center;gap:3px;margin-top:2px}
.hint-info{color:#1565C0;font-size:.64rem;display:flex;align-items:center;gap:3px;margin-top:2px}

/* ══ CODE HIÉRARCHIQUE BADGE ══ */
/* Palette de segments */
.seg-macro   {color:#7c3aed;font-weight:800}
.seg-proc    {color:#1565C0;font-weight:700}
.seg-act     {color:#0f766e;font-weight:700}
.seg-macro-sm{color:#7c3aed;font-size:.6rem;font-weight:800}
.seg-proc-sm {color:#1565C0;font-size:.6rem;font-weight:700}

/* Badge inline dans le tableau */
.hier-code-badge{display:inline-flex;align-items:center;gap:0;padding:3px 7px;border-radius:5px;border:1px solid;font-family:monospace;font-size:.62rem;line-height:1;white-space:nowrap;background:#f8fafc}
.hcb-parts{display:inline-flex;align-items:center;gap:0}
.hcb-part{font-weight:800}
.hcb-dot,.hcb-sep{color:#94a3b8;font-weight:400;padding:0 1px}
.hcb-seq{font-weight:800;font-size:.64rem}
/* Fallback pour orphans */
.hcb-prefix{font-weight:800;font-size:.62rem}

/* ══ HIÉRARCHIE DANS LES HEADERS PROCESSUS/ACTIVITÉ ══ */
.proc-hier,.act-hier{display:inline-flex;align-items:center;gap:0;padding:2px 6px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;font-family:monospace;font-size:.65rem;margin-right:6px}
.hier-seg{font-weight:800;font-size:.65rem}
.hier-dot{color:#94a3b8;padding:0 1px;font-size:.62rem}

/* ── Hiérarchie ── */
.rk-empty{display:flex;flex-direction:column;align-items:center;gap:10px;padding:50px 24px;color:#94a3b8;text-align:center}
.rk-empty i{font-size:2.5rem;opacity:.2}
.rk-empty strong{color:#475569;font-size:.88rem}
.rk-empty p{font-size:.78rem;max-width:360px;line-height:1.6}

.orphan-block{border-bottom:2px solid #fef3c7;padding-bottom:4px;margin-bottom:4px}
.orphan-hd{padding:8px 14px;background:#fef3c7;font-size:.72rem;font-weight:700;color:#d97706;display:flex;align-items:center;gap:6px}

.hier-wrap{padding:16px 0 4px}
.proc-block{border-bottom:1px solid #f1f5f9}
.proc-block:last-child{border-bottom:none}
.proc-hd{display:flex;align-items:center;gap:10px;padding:9px 14px;cursor:pointer;background:#f8fafc;transition:background .12s}
.proc-hd:hover{background:#f0f6ff}
.proc-toggle{width:18px;color:#94a3b8;font-size:.8rem;flex-shrink:0}
.proc-info{flex:1;display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.proc-name{font-size:.78rem;font-weight:600;color:#1a1a2e}
.proc-meta{flex-shrink:0}
.proc-count{font-size:.66rem;color:#64748b;background:#f1f5f9;padding:2px 8px;border-radius:10px}
.act-list{padding:0 0 4px 28px;border-left:2px solid #e2e8f0;margin-left:14px}
.act-empty{padding:8px 12px;color:#94a3b8;font-size:.72rem;display:flex;align-items:center;gap:5px}
.act-block{border-bottom:1px solid #f8fafc}
.act-block:last-child{border-bottom:none}
.act-hd{display:flex;align-items:center;gap:8px;padding:7px 12px;cursor:pointer;transition:background .12s}
.act-hd:hover{background:#f8fafc}
.act-toggle{width:16px;color:#94a3b8;font-size:.74rem;flex-shrink:0}
.act-info{flex:1;display:flex;align-items:center;gap:6px;min-width:0;flex-wrap:wrap}
.act-name{font-size:.76rem;color:#374151;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:220px}
.act-stats{display:flex;gap:4px;flex-wrap:wrap}
.act-stat-pill{font-size:.57rem;font-weight:700;padding:1px 6px;border-radius:7px}
.act-meta{display:flex;align-items:center;gap:6px;flex-shrink:0}
.act-count{font-size:.64rem;color:#64748b}
.risks-list{padding:4px 8px 8px 22px}
.risks-empty{padding:8px 10px;color:#94a3b8;font-size:.72rem;display:flex;align-items:center;gap:6px;font-style:italic}

/* ── Tableau risques ── */
.risk-tbl-hd{display:grid;grid-template-columns:120px 1fr 58px 100px 38px 38px 80px 38px 38px 64px 72px 60px;gap:3px;padding:4px 6px;background:#1e3a5f;font-size:.54rem;font-weight:700;text-transform:uppercase;letter-spacing:.03em;color:rgba(255,255,255,.85)}
.risk-tbl-hd-orphan{grid-template-columns:120px 1fr 58px 100px 38px 38px 80px 72px 60px}
.risk-row{display:grid;grid-template-columns:120px 1fr 58px 100px 38px 38px 80px 38px 38px 64px 72px 60px;gap:3px;padding:4px 6px;border-left:3px solid #e2e8f0;border-bottom:1px solid #f1f5f9;background:#fff;align-items:center;transition:background .1s}
.risk-row-orphan{grid-template-columns:120px 1fr 58px 100px 38px 38px 80px 72px 60px}
.risk-row:hover{background:#f8fbff}
.rc-code{display:flex;align-items:center}
.rc-label{font-size:.72rem;color:#1a1a2e;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.rc-type{display:flex;align-items:center}
.type-badge{font-size:.6rem;font-weight:700;padding:2px 5px;border-radius:4px;display:inline-block}
.rc-owner{font-size:.67rem;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.rc-lv,.rc-crit,.rc-stat,.rc-acts{display:flex;align-items:center;justify-content:center}
.lv-pill{display:inline-flex;align-items:center;justify-content:center;min-width:24px;height:20px;border-radius:4px;font-size:.7rem;font-weight:800;color:#fff;padding:0 3px}
.crit-pill{display:inline-flex;flex-direction:column;align-items:center;justify-content:center;min-width:50px;padding:2px 3px;border-radius:4px;font-size:.7rem;font-weight:800;color:#fff;line-height:1.2}
.crit-pill small{font-size:.5rem;font-weight:500;opacity:.85}
.dash{color:#cbd5e1;font-size:.9rem}
.status-badge{font-size:.58rem;font-weight:700;padding:2px 6px;border-radius:6px}
.st-identified{background:#e3f2fd;color:#1565C0}.st-assessed{background:#fef3c7;color:#d97706}
.st-mitigated{background:#d1fae5;color:#15803d}.st-monitored{background:#ede9fe;color:#6d28d9}.st-closed{background:#f1f5f9;color:#64748b}
.rc-acts{gap:3px}
.act-btn{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:4px;border:none;cursor:pointer;font-size:.68rem}
.act-edit{background:#e3f2fd;color:#1565C0}.act-edit:hover{background:#1565C0;color:#fff}
.act-dup{background:#f3f4f6;color:#6b7280}.act-dup:hover{background:#6b7280;color:#fff}
.act-del{background:#fee2e2;color:#ef4444}.act-del:hover{background:#ef4444;color:#fff}

/* ── Boutons ── */
.btn{display:inline-flex;align-items:center;gap:4px;padding:6px 12px;border-radius:6px;font-size:.74rem;font-weight:600;border:1px solid transparent;cursor:pointer;font-family:inherit;transition:all .12s}
.btn:disabled{opacity:.5;cursor:not-allowed}
.btn-sm{padding:4px 9px;font-size:.7rem}.btn-xs{padding:2px 7px;font-size:.63rem}
.btn-ghost{background:transparent;color:#64748b;border-color:#d1d5db}.btn-ghost:hover:not(:disabled){background:#f1f5f9}
.btn-save{background:var(--mc);color:#fff}.btn-save:hover:not(:disabled){filter:brightness(1.1)}
.spin-dot{width:11px;height:11px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite;display:inline-block}

/* ── Modal ── */
.modal-ov{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:400;display:flex;align-items:center;justify-content:center;padding:20px}
.modal-box{background:#fff;border-radius:14px;box-shadow:0 8px 40px rgba(0,0,0,.22);width:100%;max-width:620px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden}
.modal-xl{max-width:1020px}
.modal-hd{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid #e2e8f0;flex-shrink:0}
.modal-hd-l{display:flex;align-items:center;gap:6px;font-size:.82rem;font-weight:700;color:#1a1a2e;flex-wrap:wrap}
.modal-hd-l i{color:var(--mc)}
/* Code hiérarchique dans header modal */
.modal-code-hier{display:inline-flex;align-items:center;gap:0;padding:2px 8px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:5px;font-family:monospace;font-size:.68rem;margin-left:4px}
.modal-code-part{font-weight:800}
.modal-code-dot{color:#94a3b8;padding:0 1px;font-size:.62rem}
.modal-code-seq{color:#94a3b8;font-style:italic;font-weight:600}
.modal-cls{width:28px;height:28px;border:none;background:#f1f5f9;border-radius:7px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#64748b}
.modal-cls:hover{background:#fee2e2;color:#dc2626}
.modal-scroll{flex:1;overflow-y:auto;min-height:0}
.modal-ft{display:flex;justify-content:flex-end;gap:8px;padding:12px 18px;border-top:1px solid #e2e8f0;flex-shrink:0;background:#fff}
.modal-cols{display:grid;grid-template-columns:1fr 310px}
@media(max-width:750px){.modal-cols{grid-template-columns:1fr}}
.modal-col-left{padding:14px 18px;border-right:1px solid #f1f5f9;display:flex;flex-direction:column;gap:0}
.modal-col-right{padding:14px;background:#f8fafc;display:flex;flex-direction:column;gap:0}

/* ── Sections modal ── */
.modal-section{margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid #f1f5f9}
.modal-section:last-child{border-bottom:none;margin-bottom:0}
.modal-section-title{display:flex;align-items:center;gap:7px;font-size:.72rem;font-weight:700;color:#1a1a2e;text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px}
.step-badge{display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%;background:var(--mc);color:#fff;font-size:.62rem;font-weight:800;flex-shrink:0}
.step-ai{background:linear-gradient(135deg,#6d28d9,#7c3aed)}
.ia-section{flex:1}

/* ══ APERÇU CODE HIÉRARCHIQUE dans modal ══ */
.code-preview-box{background:linear-gradient(135deg,#f8f5ff,#eff6ff);border:1.5px solid #c4b5fd;border-radius:10px;padding:10px 14px;margin-bottom:4px}
.cpb-label{font-size:.62rem;font-weight:700;color:#6d28d9;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;display:flex;align-items:center;gap:4px}
.cpb-code{display:inline-flex;align-items:center;gap:0;font-family:monospace;font-size:1rem;font-weight:800;letter-spacing:.02em;margin-bottom:6px}
.cpb-part{font-weight:900}
.cpb-macro{color:#7c3aed}
.cpb-proc{color:#1565C0}
.cpb-act{color:#0f766e}
.cpb-dot{color:#94a3b8;padding:0 2px;font-size:.8rem}
.cpb-seq{color:#94a3b8;font-style:italic;font-size:.85rem}
.cpb-legend{display:flex;flex-wrap:wrap;gap:8px;margin-top:2px}
.cpb-leg-item{display:flex;align-items:center;gap:4px;font-size:.6rem;color:#475569}
.cpb-bullet{width:8px;height:8px;border-radius:50%;display:inline-block;flex-shrink:0}

.code-preview-empty{background:#f8fafc;border:1px dashed #d1d5db;border-radius:8px;padding:9px 12px;font-size:.7rem;color:#94a3b8;display:flex;align-items:center;gap:6px;margin-bottom:4px}

/* Couleurs bullet */
.cpb-bullet.cpb-macro{background:#7c3aed}
.cpb-bullet.cpb-proc{background:#1565C0}
.cpb-bullet.cpb-act{background:#0f766e}
.cpb-bullet.cpb-seq{background:#94a3b8}

/* ── Level preview ── */
.lv-preview{display:flex;align-items:center;justify-content:space-between;padding:5px 10px;border-radius:6px;color:#fff;font-size:.72rem;font-weight:600;margin-top:4px}
.type-preview{display:flex;align-items:center;gap:6px;padding:5px 10px;border-radius:6px;border:1px solid;font-size:.72rem;font-weight:600;margin-top:4px}
.type-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
.crit-display{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;border:1.5px solid;margin-top:10px}
.crit-score{width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:800;color:#fff;flex-shrink:0}
.crit-info{display:flex;flex-direction:column;gap:2px}
.crit-info strong{font-size:.74rem;color:#1a1a2e}
.crit-info span{font-size:.7rem;color:#64748b}
.ctrl-row{display:flex;gap:7px}
.btn-ai-ctrl{width:36px;height:36px;border-radius:7px;border:none;background:linear-gradient(135deg,#6d28d9,#7c3aed);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0;align-self:flex-start;margin-top:1px}
.btn-ai-ctrl:disabled{opacity:.5;cursor:not-allowed}

/* ── IA suggestions ── */
.ia-placeholder{display:flex;flex-direction:column;align-items:center;gap:10px;padding:20px 10px;color:#94a3b8;text-align:center}
.ia-placeholder i{font-size:2rem;opacity:.25}
.ia-placeholder p{font-size:.73rem;line-height:1.6}
.ia-loading{display:flex;flex-direction:column;align-items:center;gap:10px;padding:24px;color:#6d28d9}
.ia-spinner{width:26px;height:26px;border:3px solid rgba(109,40,217,.2);border-top-color:#7c3aed;border-radius:50%;animation:spin .8s linear infinite}
.ia-hint{font-size:.67rem;color:#94a3b8;margin-bottom:6px;font-style:italic}
.ia-suggestions{display:flex;flex-direction:column;gap:7px}
.ia-item{padding:9px 11px;border-radius:8px;border:1.5px solid #e2e8f0;cursor:pointer;background:#fff;transition:all .14s}
.ia-item:hover{border-color:#7c3aed;background:#fdf4ff}
.ia-item.active{border-color:#7c3aed;background:#f5f3ff}
.ia-item-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:4px}
/* Numéro de suggestion (pas de code) */
.ia-idx{width:18px;height:18px;background:linear-gradient(135deg,#6d28d9,#7c3aed);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.58rem;font-weight:800;flex-shrink:0}
.ia-select-ico{color:#cbd5e1;font-size:.72rem}
.ia-item:hover .ia-select-ico,.ia-item.active .ia-select-ico{color:#7c3aed}
.ia-label{font-size:.75rem;color:#1a1a2e;font-weight:600;line-height:1.4;margin-bottom:3px}
.ia-desc{font-size:.68rem;color:#64748b;line-height:1.4;margin-bottom:3px}
.ia-ctrl-preview{display:flex;align-items:flex-start;gap:5px;font-size:.64rem;color:#0f766e;background:#f0fdf4;border-radius:4px;padding:4px 7px;margin-top:2px}
.ia-ctrl-preview i{flex-shrink:0;margin-top:1px}

/* ── Fonctions ── */
.func-list-box{margin-top:14px;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden}
.func-list-title{padding:6px 10px;background:#f8fafc;font-size:.66rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em;display:flex;align-items:center;gap:5px;border-bottom:1px solid #e2e8f0}
.func-item{display:flex;align-items:center;gap:8px;padding:7px 10px;cursor:pointer;border-bottom:1px solid #f8fafc;transition:background .12s}
.func-item:last-child{border-bottom:none}
.func-item:hover{background:#f0f6ff}
.func-item.active{background:#eff6ff}
.func-char{font-family:monospace;font-size:.64rem;font-weight:800;color:var(--mc);background:rgba(21,101,192,.1);padding:1px 5px;border-radius:3px;min-width:30px;text-align:center}
.func-name{flex:1;color:#374151;font-size:.74rem;font-weight:500}
.func-check{color:var(--mc);font-size:.72rem;flex-shrink:0}

/* ── Session modal ── */
.session-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;border:1.5px solid #e2e8f0;cursor:pointer;transition:all .14s}
.session-item:hover{border-color:var(--mc);background:#f0f6ff}
.session-item.active{border-color:var(--mc);background:rgba(21,101,192,.04)}
.session-item-info{flex:1}
.session-code{font-size:.78rem;font-weight:700;color:var(--mc);font-family:monospace}
.session-name{font-size:.75rem;color:#1a1a2e}
.session-meta{font-size:.65rem;color:#94a3b8;margin-top:2px}
.session-item-right{display:flex;flex-direction:column;align-items:flex-end;gap:4px;flex-shrink:0}
.session-badge{font-size:.6rem;font-weight:700;padding:2px 8px;border-radius:8px}
.badge-active{background:#d1fae5;color:#15803d}.badge-inactive{background:#f1f5f9;color:#64748b}
.session-risks{font-size:.63rem;color:#94a3b8}

/* ── Toast ── */
.toast{position:fixed;bottom:22px;right:22px;z-index:600;display:flex;align-items:center;gap:9px;padding:10px 16px;border-radius:9px;font-size:.78rem;font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.18)}
.toast-success{background:#15803d;color:#fff}.toast-error{background:#dc2626;color:#fff}

/* ── Transitions ── */
.mfade-enter-active,.mfade-leave-active{transition:all .2s ease}
.mfade-enter-from,.mfade-leave-to{opacity:0}
.mfade-enter-from .modal-box,.mfade-leave-to .modal-box{transform:scale(.96) translateY(6px)}
.toast-up-enter-active,.toast-up-leave-active{transition:all .22s ease}
.toast-up-enter-from,.toast-up-leave-to{opacity:0;transform:translateY(8px)}

::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:#e2e8f0;border-radius:3px}
@keyframes spin{to{transform:rotate(360deg)}}
.spin{animation:spin .6s linear infinite;display:inline-block}
</style>