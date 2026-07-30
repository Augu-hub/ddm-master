<template>
  <VerticalLayout>
    <div class="ap-page">

      <!-- ═══ TOPBAR ═══ -->
      <div class="ap-topbar">
        <div class="ap-topbar-brand">
          <i class="ti ti-list-check"></i>
          <div>
            <h1>Plans d'action</h1>
            <p>Suivi des actions de traitement des risques — FRUCTIVIA AGRO</p>
          </div>
        </div>

        <div class="ap-kpis">
          <div class="kpi" :class="stats.overdue>0?'kpi--alert':''">
            <span>{{ stats.total }}</span><small>Total</small>
          </div>
          <div class="kpi kpi--pending">
            <span>{{ stats.pending }}</span><small>À faire</small>
          </div>
          <div class="kpi kpi--prog">
            <span>{{ stats.in_progress }}</span><small>En cours</small>
          </div>
          <div class="kpi kpi--done">
            <span>{{ stats.completed }}</span><small>Terminés</small>
          </div>
          <div class="kpi" :class="stats.overdue>0?'kpi--danger':''">
            <span>{{ stats.overdue }}</span><small>En retard</small>
          </div>
          <div class="kpi kpi--crit">
            <span>{{ stats.critical }}</span><small>Critiques</small>
          </div>
        </div>

        <div class="ap-topbar-actions">
          <button class="btn-primary btn-icon-only" @click="openCreate()" title="Nouvelle action">
            <i class="ti ti-plus"></i>
          </button>
          <button class="btn-secondary btn-icon-only" @click="openCreateMultiple()" title="Actions multiples">
            <i class="ti ti-layers"></i>
          </button>
          <button class="btn-icon" @click="reload()" title="Actualiser">
            <i class="ti ti-refresh"></i>
          </button>
        </div>
      </div>

      <!-- ═══ BARRE ÉVALUATION RISQUES ═══ -->
      <div class="eval-progress-bar">
        <span class="evp-label">Évaluation risques</span>
        <div v-for="step in evalSteps" :key="step.label" class="evp-step">
          <span class="evp-step-label">{{ step.label }}</span>
          <div class="evp-track">
            <div class="evp-fill" :style="{ width: stepPercent(step.val)+'%', background: step.color }"></div>
          </div>
          <span class="evp-count" :style="{ color: step.color }">
            {{ step.val }}/{{ stats.risks_total }}
          </span>
        </div>
      </div>

      <!-- ═══ FILTRES ═══ -->
      <div class="ap-filters">
        <div class="filter-chips">
          <span :class="['fchip', !activeFilter?'fchip--on':'']" @click="activeFilter=null">
            Tous <span class="fnum">{{ actionPlans.length }}</span>
          </span>
          <span
            v-for="s in statuses" :key="s.value"
            :class="['fchip','fchip-status', activeFilter===s.value?'fchip--on':'']"
            :style="activeFilter===s.value?{borderColor:s.color,color:s.color,background:s.color+'18'}:{}"
            @click="activeFilter = activeFilter===s.value ? null : s.value"
          >
            {{ s.label }}
            <span class="fnum">{{ countByStatus(s.value) }}</span>
          </span>
        </div>
        <div class="filter-right">
          <select v-model="fPriority" class="fsel">
            <option value="">Toutes priorités</option>
            <option v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</option>
          </select>
          <select v-model="fRisk" class="fsel">
            <option value="">Tous les risques</option>
            <option v-for="r in allRisks" :key="r.id" :value="r.id">
              {{ r.code_risk }} — {{ truncate(r.libelle, 35) }}
            </option>
          </select>
          <select v-model="fEntity" class="fsel">
            <option value="">Toutes entités</option>
            <option v-for="e in entities" :key="e.id" :value="e.id">{{ e.name }}</option>
          </select>
          <div class="search-box">
            <i class="ti ti-search"></i>
            <input v-model="searchQ" placeholder="Rechercher…" class="fsearch" />
          </div>
          <button v-if="hasFilters" class="btn-clear" @click="clearFilters">
            <i class="ti ti-x"></i> Effacer
          </button>
          <button class="btn-toggle-all" @click="toggleAllRisks">
            <i :class="['ti', allExpanded?'ti-fold-up':'ti-fold-down']"></i>
            {{ allExpanded ? 'Tout replier' : 'Tout déplier' }}
          </button>
        </div>
      </div>

      <!-- ═══ CORPS ═══ -->
      <div class="ap-body">

        <!-- Panneau global de création (sans risque présélectionné) -->
        <div v-if="formPanel.open && !formPanel.risk_id" class="inline-panel inline-panel--floating">
          <div class="ipf-hdr">
            <span><i class="ti ti-list-check"></i> {{ formPanel.id ? "Modifier l'action" : 'Nouvelle action' }}</span>
            <button class="ip-close" @click="closeForm"><i class="ti ti-x"></i></button>
          </div>
          <div class="fgrid">
            <div class="fg fg-full">
              <label class="flbl">Titre de l'action *</label>
              <input v-model="form.title" class="finp" placeholder="Ex : Mettre en place une procédure de validation…" />
            </div>
            <div class="fg fg-full">
              <label class="flbl">Description courte</label>
              <textarea v-model="form.description" rows="2" class="finp"></textarea>
            </div>
            <div class="fg fg-full">
              <label class="flbl">Plan d'action détaillé</label>
              <textarea v-model="form.action_plan" rows="4" class="finp" placeholder="Décrivez les étapes…"></textarea>
            </div>
            <div class="fg">
              <label class="flbl">Risque associé *</label>
              <select v-model="form.risk_id" class="finp">
                <option value="">— Sélectionner —</option>
                <optgroup v-for="grp in risksByProcess" :key="grp.process" :label="grp.process">
                  <option v-for="r in grp.risks" :key="r.id" :value="r.id">
                    {{ r.code_risk }} — {{ truncate(r.libelle, 45) }}
                  </option>
                </optgroup>
              </select>
            </div>
            <div class="fg">
              <label class="flbl">Entité</label>
              <select v-model="form.entity_id" class="finp">
                <option value="">— Aucune —</option>
                <option v-for="e in entities" :key="e.id" :value="e.id">{{ e.name }}</option>
              </select>
            </div>
            <div class="fg">
              <label class="flbl">Priorité *</label>
              <select v-model="form.priority" class="finp">
                <option v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</option>
              </select>
            </div>
            <div class="fg">
              <label class="flbl">Statut *</label>
              <select v-model="form.status" class="finp">
                <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
              </select>
            </div>
            <div class="fg">
              <label class="flbl">Responsable</label>
              <select v-model="form.assigned_to" class="finp">
                <option value="">— Non assigné —</option>
                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
              </select>
            </div>
            <div class="fg">
              <label class="flbl">Date de début</label>
              <input v-model="form.start_date" type="date" class="finp" />
            </div>
            <div class="fg">
              <label class="flbl">Date cible *</label>
              <input v-model="form.target_date" type="date" class="finp" />
            </div>
            <div class="fg">
              <label class="flbl">Coût estimé (XOF)</label>
              <input v-model.number="form.cost_estimate" type="number" step="1000" class="finp" placeholder="0" />
            </div>
            <div class="fg fg-full">
              <label class="flbl">Notes</label>
              <textarea v-model="form.notes" rows="2" class="finp"></textarea>
            </div>
          </div>
          <div class="ip-footer">
            <button class="btn-cancel" @click="closeForm"><i class="ti ti-x"></i> Annuler</button>
            <button class="btn-save" :disabled="!form.title||!form.risk_id||!form.target_date||saving" @click="saveForm">
              <i :class="saving?'ti ti-loader-2 spin':'ti ti-check'"></i>
              {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
            </button>
          </div>
        </div>

        <div v-if="multiPanel.open && !multiPanel.risk_id" class="inline-panel inline-panel--floating">
          <div class="ipf-hdr">
            <span><i class="ti ti-layers"></i> Actions multiples</span>
            <button class="ip-close" @click="closeMulti"><i class="ti ti-x"></i></button>
          </div>
          <div class="multi-hint">
            <i class="ti ti-info-circle"></i>
            Chaque ligne = une action distincte. Seules les lignes avec un titre seront créées.
          </div>
          <div class="fg" style="margin-bottom:12px">
            <label class="flbl">Risque associé *</label>
            <select v-model="multiPanel.risk_id" class="finp">
              <option value="">— Sélectionner un risque —</option>
              <optgroup v-for="grp in risksByProcess" :key="grp.process" :label="grp.process">
                <option v-for="r in grp.risks" :key="r.id" :value="r.id">
                  {{ r.code_risk }} — {{ truncate(r.libelle, 45) }}
                </option>
              </optgroup>
            </select>
          </div>
          <div class="multi-opts">
            <label class="multi-opt-chk">
              <input type="checkbox" v-model="multiSameDates" />
              Même date cible pour toutes les actions
            </label>
            <input v-if="multiSameDates" v-model="multiCommonDate" type="date" class="finp finp-inline" />
          </div>
          <div class="multi-list">
            <div v-for="(item, i) in multiItems" :key="i" class="multi-item">
              <span class="multi-idx">{{ i + 1 }}</span>
              <div class="multi-fields">
                <input v-model="item.title" class="finp" placeholder="Titre de l'action *" />
                <input v-model="item.description" class="finp" placeholder="Description" />
                <select v-model="item.priority" class="finp">
                  <option v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</option>
                </select>
                <select v-model="item.status" class="finp">
                  <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
                <select v-model="item.assigned_to" class="finp">
                  <option value="">— Responsable —</option>
                  <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                </select>
                <input v-if="!multiSameDates" v-model="item.target_date" type="date" class="finp" />
              </div>
              <button class="multi-del" @click="removeMultiItem(i)" title="Supprimer">
                <i class="ti ti-x"></i>
              </button>
            </div>
          </div>
          <div class="multi-footer-actions">
            <button class="btn-add-sm" @click="addMultiItem">
              <i class="ti ti-plus"></i> Ajouter une ligne
            </button>
            <span class="multi-count">{{ validMultiItems }} action(s) valide(s)</span>
          </div>
          <div class="ip-footer">
            <button class="btn-cancel" @click="closeMulti"><i class="ti ti-x"></i> Annuler</button>
            <button class="btn-save" :disabled="!validMultiItems||!multiPanel.risk_id||multiSaving" @click="saveMultiple">
              <i :class="multiSaving?'ti ti-loader-2 spin':'ti ti-check'"></i>
              {{ multiSaving ? 'Envoi…' : 'Créer ' + validMultiItems + ' actions' }}
            </button>
          </div>
        </div>

        <!-- Groupes par processus, chacun dépliable indépendamment -->
        <div v-for="proc in groupedByProcess" :key="proc.key" class="proc-group">
          <div class="proc-hdr" @click="toggleProcess(proc.key)">
            <i :class="['ti', expandedProcesses.has(proc.key)?'ti-chevron-down':'ti-chevron-right','proc-toggle']"></i>
            <span class="proc-macro" v-if="proc.macro_name">{{ proc.macro_name }} ›</span>
            <span class="proc-name">{{ proc.process_name || 'Processus non défini' }}</span>
            <span v-if="proc.process_code" class="proc-code">{{ proc.process_code }}</span>
            <span class="proc-count">{{ proc.risks.length }} risque{{ proc.risks.length>1?'s':'' }}</span>
          </div>

          <div v-if="expandedProcesses.has(proc.key)" class="proc-body">
        <!-- Groupes par risque (avec actions) -->
        <div
          v-for="group in proc.risks"
          :key="group.risk_id"
          class="risk-group"
        >
          <!-- En-tête risque -->
          <div class="rg-hdr" @click="toggleRisk(group.risk_id)">
            <div class="rg-hdr-left">
              <i :class="['ti', expandedRisks.has(group.risk_id)?'ti-chevron-down':'ti-chevron-right', 'rg-toggle']"></i>
              <span class="rg-code">{{ group.code_risk }}</span>
              <span class="rg-lib">{{ group.risk_libelle }}</span>
              <span class="rg-ctx">
                {{ group.macro_name }} › {{ group.process_name }} › {{ group.activity_name }}
              </span>
            </div>
            <div class="rg-hdr-right">
              <div class="rg-scores">
                <div v-if="group.criticality_score" class="rscore rscore--i" :style="{ background: group.zone_color||'#6b7280' }">
                  <i class="ti ti-shield-bolt"></i>{{ group.criticality_score }}
                  <span>{{ group.zone_label }}</span>
                </div>
                <div v-if="group.residual_criticality_score" class="rscore rscore--r" :style="{ background: group.residual_zone_color||'#8b5cf6' }">
                  <i class="ti ti-shield-half"></i>{{ group.residual_criticality_score }}
                  <span>{{ group.residual_zone_label }}</span>
                </div>
                <div v-if="group.target_criticality_score" class="rscore rscore--t" :style="{ background: group.target_zone_color||'#0d9488' }">
                  <i class="ti ti-target"></i>{{ group.target_criticality_score }}
                  <span>{{ group.target_zone_label }}</span>
                </div>
              </div>
              <span v-if="group.decision" :class="['dec-badge','dec-'+group.decision.toLowerCase()]">
                {{ DECISION_LABELS[group.decision] || group.decision }}
              </span>
              <span class="rg-count">{{ group.actions.length }} action{{ group.actions.length>1?'s':'' }}</span>
              <button class="btn-add-action" @click.stop="openCreate(group.risk_id)" title="Nouvelle action">
                <i class="ti ti-plus"></i>
              </button>
              <button class="btn-multi-sm" @click.stop="openCreateMultiple(group.risk_id)" title="Actions multiples">
                <i class="ti ti-layers"></i>
              </button>
            </div>
          </div>

          <!-- Corps du risque -->
          <template v-if="expandedRisks.has(group.risk_id)">

            <!-- Timeline d'évaluation -->
            <div class="eval-timeline">
              <div class="et-row">
                <!-- Inhérent -->
                <div :class="['ets', group.has_inherent?'ets--done':'ets--todo']">
                  <div class="ets-dot" :style="group.has_inherent?{background:group.zone_color||'#6b7280'}:{}">
                    <i :class="['ti', group.has_inherent?'ti-check':'ti-shield-bolt']"></i>
                  </div>
                  <div class="ets-content">
                    <div class="ets-lbl">Inhérent</div>
                    <template v-if="group.has_inherent">
                      <span class="ets-score" :style="{ background: group.zone_color||'#6b7280' }">
                        {{ group.criticality_score }}
                      </span>
                      <span class="ets-zone" :style="{ color: group.zone_color||'#6b7280' }">{{ group.zone_label }}</span>
                      <span class="ets-sub">
                        Impact {{ group.impact_label }} ({{ group.impact_score }}) ×
                        Fréq. {{ group.frequency_label }} ({{ group.frequency_score }})
                      </span>
                      <span v-if="group.frequency_recurrence" class="ets-sub ets-rec">
                        <i class="ti ti-clock"></i>{{ group.frequency_recurrence }}
                      </span>
                    </template>
                    <span v-else class="ets-nd">Non évalué</span>
                  </div>
                </div>

                <div class="ets-line" :class="group.has_control?'ets-line--on':''"></div>

                <!-- Contrôle -->
                <div :class="['ets', group.has_control?'ets--done':'ets--todo']">
                  <div class="ets-dot" :style="group.has_control?{background:'#0ea5e9'}:{}">
                    <i :class="['ti', group.has_control?'ti-check':'ti-shield-lock']"></i>
                  </div>
                  <div class="ets-content">
                    <div class="ets-lbl">Contrôle</div>
                    <template v-if="group.has_control">
                      <span class="ets-ctrl-ok"><i class="ti ti-shield-lock"></i> Sous contrôle</span>
                      <span v-if="group.control_code" class="ets-ctrl-code">{{ group.control_code }}</span>
                      <span v-if="group.control_type" class="ets-ctrl-type">{{ group.control_type }}</span>
                      <div v-if="group.control_efficacite!=null" class="ets-efficacite">
                        <div class="eff-track"><div class="eff-fill" :style="{ width: group.control_efficacite+'%', background:'#0ea5e9' }"></div></div>
                        <span>{{ group.control_efficacite }}%</span>
                      </div>
                      <span v-if="group.control_status" class="ets-ctrl-status">{{ group.control_status }}</span>
                      <span v-if="group.control_owner" class="ets-sub"><i class="ti ti-user"></i>{{ group.control_owner }}</span>
                      <span v-if="group.referential_type" class="ets-ref">{{ group.referential_type }}</span>
                    </template>
                    <span v-else class="ets-nd">Non défini</span>
                  </div>
                </div>

                <div class="ets-line" :class="group.has_residual?'ets-line--on':''"></div>

                <!-- Résiduel -->
                <div :class="['ets', group.has_residual?'ets--done':'ets--todo']">
                  <div class="ets-dot" :style="group.has_residual?{background:group.residual_zone_color||'#8b5cf6'}:{}">
                    <i :class="['ti', group.has_residual?'ti-check':'ti-shield-half']"></i>
                  </div>
                  <div class="ets-content">
                    <div class="ets-lbl">Résiduel</div>
                    <template v-if="group.has_residual">
                      <span class="ets-score" :style="{ background: group.residual_zone_color||'#8b5cf6' }">
                        {{ group.residual_criticality_score }}
                      </span>
                      <span class="ets-zone" :style="{ color: group.residual_zone_color||'#8b5cf6' }">{{ group.residual_zone_label }}</span>
                      <span class="ets-sub">
                        Impact {{ group.residual_impact_label }} ({{ group.residual_impact_score }}) ×
                        Fréq. {{ group.residual_frequency_label }} ({{ group.residual_frequency_score }})
                      </span>
                    </template>
                    <span v-else class="ets-nd">Non évalué</span>
                  </div>
                </div>

                <div class="ets-line" :class="group.has_target?'ets-line--on':''"></div>

                <!-- Cible -->
                <div :class="['ets', group.has_target?'ets--done':'ets--todo']">
                  <div class="ets-dot" :style="group.has_target?{background:group.target_zone_color||'#0d9488'}:{}">
                    <i :class="['ti', group.has_target?'ti-check':'ti-target']"></i>
                  </div>
                  <div class="ets-content">
                    <div class="ets-lbl">Cible</div>
                    <template v-if="group.has_target">
                      <span class="ets-score" :style="{ background: group.target_zone_color||'#0d9488' }">
                        {{ group.target_criticality_score }}
                      </span>
                      <span class="ets-zone" :style="{ color: group.target_zone_color||'#0d9488' }">{{ group.target_zone_label }}</span>
                      <span class="ets-sub">
                        Impact {{ group.target_impact_label }} ({{ group.target_impact_score }}) ×
                        Fréq. {{ group.target_frequency_label }} ({{ group.target_frequency_score }})
                      </span>
                      <span v-if="group.risk_target_date" class="ets-sub">
                        <i class="ti ti-calendar"></i>{{ fmtDate(group.risk_target_date) }}
                      </span>
                    </template>
                    <span v-else class="ets-nd">Non défini</span>
                  </div>
                </div>
              </div>

              <!-- Infos complémentaires risque -->
              <div v-if="group.causes||group.consequences||group.nomenclature_label" class="risk-meta">
                <div v-if="group.nomenclature_label" class="rmeta-item">
                  <i class="ti ti-tag"></i>
                  <span>{{ group.nomenclature_code }} · {{ group.nomenclature_label }}</span>
                </div>
                <div v-if="group.risk_owner" class="rmeta-item">
                  <i class="ti ti-user-circle"></i>
                  <span>Propriétaire : {{ group.risk_owner }}</span>
                </div>
                <details v-if="group.causes" class="rmeta-detail">
                  <summary><i class="ti ti-alert-triangle"></i> Causes</summary>
                  <p>{{ group.causes }}</p>
                </details>
                <details v-if="group.consequences" class="rmeta-detail">
                  <summary><i class="ti ti-alert-circle"></i> Conséquences</summary>
                  <p>{{ group.consequences }}</p>
                </details>
              </div>
            </div>

            <!-- ═══ RECOMMANDATION (contient les plans d'action de ce risque) ═══ -->
            <div class="reco-block">
              <div class="reco-hdr">
                <i class="ti ti-bulb"></i>
                <span>Recommandation</span>
                <button
                  class="reco-edit-btn"
                  @click.stop="openRecoEdit(group.risk_id, group.recommendation_content)"
                  :title="group.recommendation_content ? 'Modifier la recommandation' : 'Ajouter une recommandation'"
                >
                  <i :class="group.recommendation_content ? 'ti ti-pencil' : 'ti ti-plus'"></i>
                </button>
              </div>

              <!-- Édition inline de la recommandation -->
              <div v-if="recoEdit.open && recoEdit.risk_id===group.risk_id" class="reco-edit">
                <textarea
                  v-model="recoEdit.content" rows="4" class="finp"
                  placeholder="Décrivez la recommandation / le plan de traitement pour ce risque…"
                ></textarea>
                <div class="ip-footer">
                  <button class="btn-cancel" @click="closeRecoEdit"><i class="ti ti-x"></i> Annuler</button>
                  <button class="btn-save" :disabled="recoSaving" @click="saveReco">
                    <i :class="recoSaving?'ti ti-loader-2 spin':'ti ti-check'"></i>
                    {{ recoSaving ? 'Enregistrement…' : 'Enregistrer' }}
                  </button>
                </div>
              </div>

              <div v-else class="reco-body">
                <p v-if="group.recommendation_content" class="reco-text">{{ group.recommendation_content }}</p>
                <p v-else class="reco-empty">Aucune recommandation formalisée — cliquez sur « + » pour en ajouter une.</p>
                <div v-if="group.controles_existants" class="reco-sub">
                  <span class="reco-sub-lbl"><i class="ti ti-shield-check"></i> Contrôles existants</span>
                  <p>{{ group.controles_existants }}</p>
                </div>

                <!-- Plans d'action de cette recommandation -->
                <div class="actions-section">
                  <div class="as-hdr">
                    <span class="as-title">
                      <i class="ti ti-list-details"></i>
                      Plans d'action ({{ group.actions.length }})
                    </span>
                    <div class="as-btns">
                      <button class="btn-add-sm btn-icon-only" @click="openCreate(group.risk_id)" title="Ajouter un plan d'action">
                        <i class="ti ti-plus"></i>
                      </button>
                      <button class="btn-multi-sm2" @click="openCreateMultiple(group.risk_id)" title="Actions multiples">
                        <i class="ti ti-layers"></i>
                      </button>
                    </div>
                  </div>

                  <!-- Panneau inline : actions multiples scoping ce risque -->
                  <div v-if="multiPanel.open && multiPanel.risk_id===group.risk_id" class="inline-panel">
                    <div class="ipf-hdr">
                      <span><i class="ti ti-layers"></i> Actions multiples</span>
                      <span class="m-risk-code">{{ group.code_risk }}</span>
                      <button class="ip-close" @click="closeMulti"><i class="ti ti-x"></i></button>
                    </div>
                    <div class="multi-hint">
                      <i class="ti ti-info-circle"></i>
                      Chaque ligne = une action distincte. Seules les lignes avec un titre seront créées.
                    </div>
                    <div class="multi-opts">
                      <label class="multi-opt-chk">
                        <input type="checkbox" v-model="multiSameDates" />
                        Même date cible pour toutes les actions
                      </label>
                      <input v-if="multiSameDates" v-model="multiCommonDate" type="date" class="finp finp-inline" />
                    </div>
                    <div class="multi-list">
                      <div v-for="(item, i) in multiItems" :key="i" class="multi-item">
                        <span class="multi-idx">{{ i + 1 }}</span>
                        <div class="multi-fields">
                          <input v-model="item.title" class="finp" placeholder="Titre de l'action *" />
                          <input v-model="item.description" class="finp" placeholder="Description" />
                          <select v-model="item.priority" class="finp">
                            <option v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</option>
                          </select>
                          <select v-model="item.status" class="finp">
                            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                          </select>
                          <select v-model="item.assigned_to" class="finp">
                            <option value="">— Responsable —</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                          </select>
                          <input v-if="!multiSameDates" v-model="item.target_date" type="date" class="finp" />
                        </div>
                        <button class="multi-del" @click="removeMultiItem(i)" title="Supprimer">
                          <i class="ti ti-x"></i>
                        </button>
                      </div>
                    </div>
                    <div class="multi-footer-actions">
                      <button class="btn-add-sm" @click="addMultiItem">
                        <i class="ti ti-plus"></i> Ajouter une ligne
                      </button>
                      <span class="multi-count">{{ validMultiItems }} action(s) valide(s)</span>
                    </div>
                    <div class="ip-footer">
                      <button class="btn-cancel" @click="closeMulti"><i class="ti ti-x"></i> Annuler</button>
                      <button class="btn-save" :disabled="!validMultiItems||!multiPanel.risk_id||multiSaving" @click="saveMultiple">
                        <i :class="multiSaving?'ti ti-loader-2 spin':'ti ti-check'"></i>
                        {{ multiSaving ? 'Envoi…' : 'Créer ' + validMultiItems + ' actions' }}
                      </button>
                    </div>
                  </div>

                  <div
                    v-if="!group.actions.length && !(formPanel.open && formPanel.risk_id===group.risk_id && !formPanel.id)"
                    class="as-empty"
                  >
                    <i class="ti ti-inbox"></i>
                    <p>Aucun plan d'action pour cette recommandation</p>
                    <button class="btn-add" @click="openCreate(group.risk_id)">
                      <i class="ti ti-plus"></i> Créer un plan d'action
                    </button>
                  </div>

                  <div
                    v-if="group.actions.length || (formPanel.open && formPanel.risk_id===group.risk_id && !formPanel.id)"
                    class="actions-table-wrap"
                  >
                  <table class="actions-table">
                    <thead>
                      <tr>
                        <th>Code</th>
                        <th>Titre / Description</th>
                        <th>Priorité</th>
                        <th>Statut</th>
                        <th>Responsable</th>
                        <th>Début</th>
                        <th>Échéance</th>
                        <th>Avancement</th>
                        <th>Coût estimé</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <!-- Ligne de création : horizontale, alignée sur les colonnes du tableau -->
                      <tr v-if="formPanel.open && formPanel.risk_id===group.risk_id && !formPanel.id" class="arow-form">
                        <td><span class="a-code a-code--new">Nouveau</span></td>
                        <td class="rf-td-title">
                          <input v-model="form.title" class="finp finp-cell" placeholder="Titre du plan d'action *" />
                          <input v-model="form.description" class="finp finp-cell finp-cell-sub" placeholder="Description courte (optionnel)" />
                        </td>
                        <td>
                          <select v-model="form.priority" class="finp finp-cell">
                            <option v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</option>
                          </select>
                        </td>
                        <td>
                          <select v-model="form.status" class="finp finp-cell">
                            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                          </select>
                        </td>
                        <td>
                          <select v-model="form.assigned_to" class="finp finp-cell">
                            <option value="">— Non assigné —</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                          </select>
                        </td>
                        <td><input v-model="form.start_date" type="date" class="finp finp-cell" /></td>
                        <td><input v-model="form.target_date" type="date" class="finp finp-cell" placeholder="Échéance *" /></td>
                        <td class="rf-td-muted">— <span class="rf-hint">(suivi)</span></td>
                        <td><input v-model.number="form.cost_estimate" type="number" step="1000" class="finp finp-cell" placeholder="Coût XOF" /></td>
                        <td>
                          <div class="a-btns">
                            <button class="aib aib-save" :disabled="!form.title||!form.risk_id||!form.target_date||saving" @click="saveForm" title="Enregistrer">
                              <i :class="saving?'ti ti-loader-2 spin':'ti ti-check'"></i>
                            </button>
                            <button class="aib aib-del" @click="closeForm" title="Annuler">
                              <i class="ti ti-x"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                      <!-- Champs complémentaires (facultatifs), toujours visibles sur la même ligne logique -->
                      <tr v-if="formPanel.open && formPanel.risk_id===group.risk_id && !formPanel.id" class="arow-form arow-form-extra">
                        <td colspan="10">
                          <div class="rf-extra-row">
                            <div class="rf-extra-fld">
                              <label class="flbl">Entité</label>
                              <select v-model="form.entity_id" class="finp">
                                <option value="">— Aucune —</option>
                                <option v-for="e in entities" :key="e.id" :value="e.id">{{ e.name }}</option>
                              </select>
                            </div>
                            <div class="rf-extra-fld rf-extra-fld--wide">
                              <label class="flbl">Plan d'action détaillé</label>
                              <input v-model="form.action_plan" class="finp" placeholder="Décrivez les étapes…" />
                            </div>
                            <div class="rf-extra-fld rf-extra-fld--wide">
                              <label class="flbl">Notes</label>
                              <input v-model="form.notes" class="finp" placeholder="Notes complémentaires" />
                            </div>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                    <tbody>
                  <template v-for="action in group.actions" :key="action.id">
                    <tr
                      :class="[
                        'arow',
                        isOverdue(action)?'arow--late':'',
                        action.status==='completed'?'arow--done':'',
                        expandedActions.has(action.id)?'arow--open':''
                      ]"
                      @click="toggleActionDetail(action.id)"
                    >
                      <td>
                        <i :class="['ti', expandedActions.has(action.id)?'ti-chevron-down':'ti-chevron-right', 'a-toggle']"></i>
                        <span class="a-code">{{ action.code }}</span>
                        <span v-if="action.is_auto_generated" class="a-auto" title="Généré automatiquement">
                          <i class="ti ti-robot"></i>
                        </span>
                      </td>
                      <td>
                        <div class="a-title">{{ action.title }}</div>
                        <div v-if="action.description" class="a-desc">{{ truncate(action.description, 65) }}</div>
                      </td>
                      <td><span :class="['prio','prio-'+action.priority]">{{ prioLabel(action.priority) }}</span></td>
                      <td>
                        <span :class="['stat','stat-'+action.status]">{{ statLabel(action.status) }}</span>
                        <div v-if="isOverdue(action)" class="a-late">
                          <i class="ti ti-clock-x"></i> En retard
                        </div>
                      </td>
                      <td class="a-user">
                        <span v-if="action.assigned_to_name">{{ action.assigned_to_name }}</span>
                        <span v-else class="a-none">—</span>
                      </td>
                      <td class="a-date">{{ fmtDate(action.start_date) }}</td>
                      <td :class="['a-date', isOverdue(action)?'a-date--late':'']">
                        {{ fmtDate(action.target_date) }}
                      </td>
                      <td>
                        <div class="prog-wrap">
                          <div class="prog-track">
                            <div class="prog-fill" :style="{ width: (action.progress||0)+'%' }"></div>
                          </div>
                          <span class="prog-pct">{{ action.progress||0 }}%</span>
                        </div>
                      </td>
                      <td class="a-cost">{{ fmtCur(action.cost_estimate) }}</td>
                      <td>
                        <div class="a-btns">
                          <button class="aib" @click.stop="toggleActionDetail(action.id)" title="Détail">
                            <i class="ti ti-eye"></i>
                          </button>
                          <button class="aib" @click.stop="openEdit(action)" title="Modifier">
                            <i class="ti ti-pencil"></i>
                          </button>
                          <button class="aib aib-del" @click.stop="deleteAction(action)" title="Supprimer">
                            <i class="ti ti-trash"></i>
                          </button>
                        </div>
                      </td>
                    </tr>

                    <!-- Panneau d'édition inline pour cette action précise -->
                    <tr v-if="formPanel.open && formPanel.id===action.id" class="arow-detail">
                      <td colspan="10">
                        <div class="action-detail-inline">
                          <div class="ipf-hdr ipf-hdr--edit">
                            <span><i class="ti ti-pencil"></i> Modifier l'action</span>
                            <span class="a-code">{{ action.code }}</span>
                            <button class="ip-close" @click="closeForm"><i class="ti ti-x"></i></button>
                          </div>
                          <div class="fgrid">
                            <div class="fg fg-full">
                              <label class="flbl">Titre de l'action *</label>
                              <input v-model="form.title" class="finp" />
                            </div>
                            <div class="fg fg-full">
                              <label class="flbl">Description courte</label>
                              <textarea v-model="form.description" rows="2" class="finp"></textarea>
                            </div>
                            <div class="fg fg-full">
                              <label class="flbl">Plan d'action détaillé</label>
                              <textarea v-model="form.action_plan" rows="4" class="finp"></textarea>
                            </div>
                            <div class="fg">
                              <label class="flbl">Entité</label>
                              <select v-model="form.entity_id" class="finp">
                                <option value="">— Aucune —</option>
                                <option v-for="e in entities" :key="e.id" :value="e.id">{{ e.name }}</option>
                              </select>
                            </div>
                            <div class="fg">
                              <label class="flbl">Priorité *</label>
                              <select v-model="form.priority" class="finp">
                                <option v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</option>
                              </select>
                            </div>
                            <div class="fg">
                              <label class="flbl">Statut *</label>
                              <select v-model="form.status" class="finp">
                                <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                              </select>
                            </div>
                            <div class="fg">
                              <label class="flbl">Responsable</label>
                              <select v-model="form.assigned_to" class="finp">
                                <option value="">— Non assigné —</option>
                                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                              </select>
                            </div>
                            <div class="fg">
                              <label class="flbl">Date de début</label>
                              <input v-model="form.start_date" type="date" class="finp" />
                            </div>
                            <div class="fg">
                              <label class="flbl">Date cible *</label>
                              <input v-model="form.target_date" type="date" class="finp" />
                            </div>
                            <div class="fg">
                              <label class="flbl">Coût estimé (XOF)</label>
                              <input v-model.number="form.cost_estimate" type="number" step="1000" class="finp" />
                            </div>
                            <div class="fg fg-full">
                              <label class="flbl">Notes</label>
                              <textarea v-model="form.notes" rows="2" class="finp"></textarea>
                            </div>
                          </div>
                          <div class="ip-footer">
                            <button class="btn-cancel" @click="closeForm"><i class="ti ti-x"></i> Annuler</button>
                            <button class="btn-save" :disabled="!form.title||!form.risk_id||!form.target_date||saving" @click="saveForm">
                              <i :class="saving?'ti ti-loader-2 spin':'ti ti-check'"></i>
                              {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
                            </button>
                          </div>
                        </div>
                      </td>
                    </tr>

                    <!-- Ligne de détail inline (vue) -->
                    <tr v-if="expandedActions.has(action.id) && !(formPanel.open && formPanel.id===action.id)" class="arow-detail">
                      <td colspan="10">
                        <div class="action-detail-inline">
                          <div class="adi-grid">
                            <div v-if="action.action_plan" class="adi-block adi-block--full">
                              <div class="adi-label">Plan d'action détaillé</div>
                              <p class="adi-text">{{ action.action_plan }}</p>
                            </div>
                            <div v-if="action.notes" class="adi-block adi-block--full">
                              <div class="adi-label">Notes</div>
                              <p class="adi-text">{{ action.notes }}</p>
                            </div>
                            <div class="adi-block">
                              <div class="adi-label">Informations</div>
                              <div class="adi-rows">
                                <div class="adi-row"><span>Entité</span><span>{{ action.entity_name||'—' }}</span></div>
                                <div class="adi-row"><span>Source</span><span>{{ action.source_status||'Manuel' }}</span></div>
                                <div class="adi-row"><span>Créé par</span><span>{{ action.created_by_name||'—' }}</span></div>
                                <div class="adi-row"><span>Créé le</span><span>{{ fmtDate(action.created_at) }}</span></div>
                              </div>
                            </div>
                            <div class="adi-block">
                              <div class="adi-label">Coûts</div>
                              <div class="adi-rows">
                                <div class="adi-row"><span>Estimé</span><span>{{ fmtCur(action.cost_estimate) }}</span></div>
                                <div class="adi-row"><span>Réel</span><span>{{ fmtCur(action.actual_cost) }}</span></div>
                              </div>
                            </div>
                            <div class="adi-block">
                              <div class="adi-label">Dates</div>
                              <div class="adi-rows">
                                <div class="adi-row"><span>Début</span><span>{{ fmtDate(action.start_date)||'—' }}</span></div>
                                <div class="adi-row"><span>Cible</span><span :class="isOverdue(action)?'text-red':''">{{ fmtDate(action.target_date)||'—' }}</span></div>
                                <div class="adi-row"><span>Terminé</span><span>{{ fmtDate(action.completion_date)||'—' }}</span></div>
                              </div>
                            </div>
                          </div>

                          <!-- Suivi (c'est ici, et uniquement ici, que se calcule la progression) -->
                          <div class="d-section">
                            <div class="d-sec-hdr">
                              <span><i class="ti ti-checkbox"></i> Suivi ({{ (detailCache[action.id]?.tasks||[]).length }}) <em class="d-sec-hint">— calcule l'avancement</em></span>
                              <button class="btn-add-sm btn-icon-only" @click="openTaskCreate(action.id)" title="Ajouter un suivi">
                                <i class="ti ti-plus"></i>
                              </button>
                            </div>

                            <div v-if="taskPanel.open && taskPanel.plan_id===action.id" class="task-inline-form">
                              <div class="fgrid">
                                <div class="fg fg-full">
                                  <label class="flbl">Titre *</label>
                                  <input v-model="taskForm.title" class="finp" />
                                </div>
                                <div class="fg fg-full">
                                  <label class="flbl">Description</label>
                                  <textarea v-model="taskForm.description" rows="2" class="finp"></textarea>
                                </div>
                                <div class="fg">
                                  <label class="flbl">Responsable</label>
                                  <select v-model="taskForm.assigned_to" class="finp">
                                    <option value="">—</option>
                                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                                  </select>
                                </div>
                                <div class="fg">
                                  <label class="flbl">Date cible</label>
                                  <input v-model="taskForm.target_date" type="date" class="finp" />
                                </div>
                                <div class="fg">
                                  <label class="flbl">Statut</label>
                                  <select v-model="taskForm.status" class="finp">
                                    <option value="pending">À faire</option>
                                    <option value="in_progress">En cours</option>
                                    <option value="completed">Terminé</option>
                                    <option value="cancelled">Annulé</option>
                                  </select>
                                </div>
                              </div>
                              <div class="task-inline-footer">
                                <button class="btn-cancel" @click="closeTaskPanel"><i class="ti ti-x"></i> Annuler</button>
                                <button class="btn-save" :disabled="!taskForm.title||taskSaving" @click="saveTask">
                                  <i :class="taskSaving?'ti ti-loader-2 spin':'ti ti-check'"></i> Enregistrer
                                </button>
                              </div>
                            </div>

                            <div v-if="detailCache[action.id]?.loading" class="d-empty">Chargement…</div>
                            <div v-else-if="(detailCache[action.id]?.tasks||[]).length" class="tasks-list">
                              <div v-for="t in detailCache[action.id].tasks" :key="t.id" :class="['task-item','task-'+t.status]">
                                <i :class="['ti', t.status==='completed'?'ti-check-circle':'ti-circle', 'task-ic']"></i>
                                <div class="task-info">
                                  <span class="task-title">{{ t.title }}</span>
                                  <span class="task-meta">{{ t.assigned_to_name||'—' }} · {{ fmtDate(t.target_date)||'—' }}</span>
                                </div>
                                <span :class="['stat','stat-'+t.status]" style="font-size:9px">{{ statLabel(t.status) }}</span>
                                <div class="task-btns">
                                  <button class="aib" @click="editTask(t)"><i class="ti ti-pencil"></i></button>
                                  <button class="aib aib-del" @click="deleteTask(t)"><i class="ti ti-trash"></i></button>
                                </div>
                              </div>
                            </div>
                            <div v-else class="d-empty">Aucun suivi défini pour l'instant</div>
                          </div>

                          <!-- Commentaires -->
                          <div class="d-section">
                            <div class="d-sec-hdr"><span><i class="ti ti-message"></i> Commentaires ({{ (detailCache[action.id]?.comments||[]).length }})</span></div>
                            <div class="cmt-input-row">
                              <textarea v-model="newComments[action.id]" rows="2" class="finp" placeholder="Ajouter un commentaire…"></textarea>
                              <button class="btn-send" @click="addComment(action.id)" :disabled="!newComments[action.id]?.trim()">
                                <i class="ti ti-send"></i>
                              </button>
                            </div>
                            <div v-for="c in (detailCache[action.id]?.comments||[])" :key="c.id" class="cmt-item">
                              <div class="cmt-meta">
                                <strong>{{ c.user_name||'Anonyme' }}</strong>
                                <span class="cmt-date">{{ fmtDateTime(c.created_at) }}</span>
                                <span v-if="c.is_internal" class="cmt-internal">Interne</span>
                              </div>
                              <p>{{ c.comment }}</p>
                            </div>
                            <div v-if="!(detailCache[action.id]?.comments||[]).length" class="d-empty">Aucun commentaire</div>
                          </div>

                          <!-- Historique -->
                          <div class="d-section">
                            <div class="d-sec-hdr"><span><i class="ti ti-clock-history"></i> Historique</span></div>
                            <div v-for="h in (detailCache[action.id]?.history||[])" :key="h.id" class="hist-item">
                              <span class="hist-action">{{ h.action }}</span>
                              <span class="hist-desc">{{ h.description }}</span>
                              <span class="hist-user">{{ h.user_name||'Système' }}</span>
                              <span class="hist-date">{{ fmtDateTime(h.created_at) }}</span>
                            </div>
                            <div v-if="!(detailCache[action.id]?.history||[]).length" class="d-empty">Aucun historique</div>
                          </div>
                        </div>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
              </div>
            </div>
              </div>
            </div>
          </template>
        </div>
          </div>
        </div>

        <!-- Risques sans plan d'action -->
        <div v-if="risksWithoutActions.length" class="no-action-risks">
          <div class="nar-hdr" @click="showNoAction = !showNoAction">
            <i :class="['ti', showNoAction?'ti-chevron-down':'ti-chevron-right']"></i>
            <i class="ti ti-shield-off nar-icon"></i>
            <span>Risques sans plan d'action ({{ risksWithoutActions.length }})</span>
          </div>
          <div v-if="showNoAction" class="nar-list">
            <template v-for="r in risksWithoutActions" :key="r.id">
              <div class="nar-item">
                <span class="nar-code">{{ r.code_risk }}</span>
                <span class="nar-lib">{{ truncate(r.libelle, 75) }}</span>
                <span class="nar-ctx">{{ r.process_code }} › {{ r.activity_code }}</span>
                <div v-if="r.criticality_score" class="rscore rscore--i" :style="{ background: r.zone_color||'#6b7280' }">
                  <i class="ti ti-shield-bolt"></i>{{ r.criticality_score }}
                </div>
                <span v-if="r.decision" :class="['dec-badge','dec-'+r.decision.toLowerCase()]">
                  {{ DECISION_LABELS[r.decision]||r.decision }}
                </span>
                <button class="btn-add-sm btn-icon-only" @click="openCreate(r.id)" title="Ajouter une action">
                  <i class="ti ti-plus"></i>
                </button>
              </div>
              <div v-if="formPanel.open && formPanel.risk_id===r.id" class="inline-panel inline-panel--nar">
                <div class="ipf-hdr">
                  <span><i class="ti ti-list-check"></i> Nouvelle action</span>
                  <span class="m-risk-code">{{ r.code_risk }}</span>
                  <button class="ip-close" @click="closeForm"><i class="ti ti-x"></i></button>
                </div>
                <div class="fgrid">
                  <div class="fg fg-full">
                    <label class="flbl">Titre de l'action *</label>
                    <input v-model="form.title" class="finp" placeholder="Ex : Mettre en place une procédure de validation…" />
                  </div>
                  <div class="fg fg-full">
                    <label class="flbl">Description courte</label>
                    <textarea v-model="form.description" rows="2" class="finp"></textarea>
                  </div>
                  <div class="fg fg-full">
                    <label class="flbl">Plan d'action détaillé</label>
                    <textarea v-model="form.action_plan" rows="4" class="finp" placeholder="Décrivez les étapes…"></textarea>
                  </div>
                  <div class="fg">
                    <label class="flbl">Entité</label>
                    <select v-model="form.entity_id" class="finp">
                      <option value="">— Aucune —</option>
                      <option v-for="e in entities" :key="e.id" :value="e.id">{{ e.name }}</option>
                    </select>
                  </div>
                  <div class="fg">
                    <label class="flbl">Priorité *</label>
                    <select v-model="form.priority" class="finp">
                      <option v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</option>
                    </select>
                  </div>
                  <div class="fg">
                    <label class="flbl">Statut *</label>
                    <select v-model="form.status" class="finp">
                      <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                  </div>
                  <div class="fg">
                    <label class="flbl">Responsable</label>
                    <select v-model="form.assigned_to" class="finp">
                      <option value="">— Non assigné —</option>
                      <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                  </div>
                  <div class="fg">
                    <label class="flbl">Date de début</label>
                    <input v-model="form.start_date" type="date" class="finp" />
                  </div>
                  <div class="fg">
                    <label class="flbl">Date cible *</label>
                    <input v-model="form.target_date" type="date" class="finp" />
                  </div>
                  <div class="fg">
                    <label class="flbl">Coût estimé (XOF)</label>
                    <input v-model.number="form.cost_estimate" type="number" step="1000" class="finp" placeholder="0" />
                  </div>
                  <div class="fg fg-full">
                    <label class="flbl">Notes</label>
                    <textarea v-model="form.notes" rows="2" class="finp"></textarea>
                  </div>
                </div>
                <div class="ip-footer">
                  <button class="btn-cancel" @click="closeForm"><i class="ti ti-x"></i> Annuler</button>
                  <button class="btn-save" :disabled="!form.title||!form.risk_id||!form.target_date||saving" @click="saveForm">
                    <i :class="saving?'ti ti-loader-2 spin':'ti ti-check'"></i>
                    {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
                  </button>
                </div>
              </div>
            </template>
          </div>
        </div>

        <!-- État vide global -->
        <div v-if="!groupedByRisk.length && !risksWithoutActions.length" class="empty-state">
          <i class="ti ti-clipboard-off"></i>
          <p>Aucun résultat pour ces filtres</p>
          <button class="btn-add" @click="openCreate()">
            <i class="ti ti-plus"></i> Créer une action
          </button>
        </div>
      </div>

      <!-- ═══ FLASH ═══ -->
      <Transition name="fl">
        <div v-if="flashMsg" :class="['flash', flashOk?'flash-ok':'flash-err']">
          <i :class="flashOk?'ti ti-check-circle':'ti ti-alert-circle'"></i>
          {{ flashMsg }}
        </div>
      </Transition>

    </div>
  </VerticalLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

// ── PROPS — exactement ce que le contrôleur envoie via Inertia::render() ─────
const props = defineProps({
  actionPlans:      { type: Array,  default: () => [] },
  allRisks:         { type: Array,  default: () => [] },
  stats:            { type: Object, default: () => ({}) },
  entities:         { type: Array,  default: () => [] },
  users:            { type: Array,  default: () => [] },
  priorities:       { type: Array,  default: () => [] },
  statuses:         { type: Array,  default: () => [] },
  decisionStatuses: { type: Array,  default: () => [] },
  recommendations:  { type: Array,  default: () => [] },
  filters:          { type: Object, default: () => ({}) },
})

// ── CONSTANTES ───────────────────────────────────────────────────────────────
const DECISION_LABELS = {
  ACCEPTE: 'Accepté', REDUIT: 'Réduire', TRANSFERE: 'Transférer',
  REFUSE: 'Refuser',  MITIGE: 'Atténué', CONTROLE: 'Sous contrôle',
}

// ── STATE GÉNÉRAL ─────────────────────────────────────────────────────────────
const saving      = ref(false)
const taskSaving  = ref(false)
const multiSaving = ref(false)
const searchQ     = ref('')
const activeFilter = ref(null)
const fPriority   = ref(props.filters.priority || '')
const fRisk       = ref(props.filters.risk_id  ? parseInt(props.filters.risk_id) : '')
const fEntity     = ref(props.filters.entity_id ? parseInt(props.filters.entity_id) : '')
const expandedRisks   = ref(new Set())
const expandedProcesses = ref(new Set())
const expandedActions = ref(new Set())
const showNoAction    = ref(false)
const flashMsg = ref(''); const flashOk = ref(true); let flashTimer = null

// ── PANNEAUX INLINE (remplacent les anciennes modales) ───────────────────────
// Création / édition d'une action — un seul panneau actif à la fois,
// positionné dans le template selon formPanel.risk_id / formPanel.id
const formPanel = ref({ open: false, id: null, risk_id: null })

// Édition inline de la recommandation d'un risque (1 par risque)
const recoEdit = ref({ open: false, risk_id: null, content: '' })
const recoSaving = ref(false)

// Création multiple — idem
const multiPanel = ref({ open: false, risk_id: null })

// Tâche (création / édition) — idem, rattachée à une action précise
const taskPanel = ref({ open: false, id: null, plan_id: null })

// Cache du détail (tâches / commentaires / historique) par action, chargé à la demande
// afin de pouvoir garder plusieurs actions ouvertes simultanément sans tout recharger.
const detailCache = reactive({})
const newComments = reactive({})

// Multi
const multiItems      = ref([])
const multiSameDates  = ref(false)
const multiCommonDate = ref('')

// Formulaires
const defaultForm = () => ({
  id: null, risk_id: '', entity_id: '', title: '', description: '', action_plan: '',
  priority: 'medium', status: 'pending', assigned_to: '', target_date: '',
  start_date: '', cost_estimate: null, actual_cost: null, notes: '',
  source_status: '',
})
const form     = ref(defaultForm())
const taskForm = ref({ id: null, plan_id: null, title: '', description: '', assigned_to: '', target_date: '', status: 'pending' })

// ── HELPERS ──────────────────────────────────────────────────────────────────
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || ''

const apiFetch = async (url, body = {}, method = 'POST') => {
  const r = await fetch(url, {
    method,
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
    body: JSON.stringify(body),
  })
  return [r, await r.json()]
}

const truncate = (s, n = 50) => s && s.length > n ? s.slice(0, n) + '…' : s || ''

const fmtDate = (d) =>
  d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) : null

const fmtDateTime = (d) =>
  d ? new Date(d).toLocaleString('fr-FR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : null

const fmtCur = (v) =>
  v != null
    ? new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(v)
    : '—'

const isOverdue = (p) =>
  p && p.status !== 'completed' && p.status !== 'cancelled' && p.target_date &&
  new Date(p.target_date) < new Date()

const prioLabel = (v) => props.priorities.find(p => p.value === v)?.label || v || '—'
const statLabel = (v) => props.statuses.find(s => s.value === v)?.label || v || '—'
const countByStatus = (v) => props.actionPlans.filter(p => p.status === v).length

const getRiskCode = (id) => props.allRisks.find(r => r.id === id)?.code_risk || id

const stepPercent = (val) =>
  stats.value.risks_total > 0 ? Math.round((val / stats.value.risks_total) * 100) : 0

const showFlash = (msg, ok = true) => {
  if (flashTimer) clearTimeout(flashTimer)
  flashMsg.value = msg
  flashOk.value = ok
  flashTimer = setTimeout(() => { flashMsg.value = '' }, 4000)
}

// ── COMPUTED ─────────────────────────────────────────────────────────────────
const stats = computed(() => props.stats || {})

const evalSteps = computed(() => [
  { label: 'Inhérent',  val: stats.value.risks_evaluated    || 0, color: '#f97316' },
  { label: 'Contrôle',  val: stats.value.risks_controlled   || 0, color: '#0ea5e9' },
  { label: 'Résiduel',  val: stats.value.risks_with_residual || 0, color: '#8b5cf6' },
  { label: 'Cible',     val: stats.value.risks_with_target  || 0, color: '#0d9488' },
])

const hasFilters = computed(() => !!(activeFilter.value || fPriority.value || fRisk.value || fEntity.value || searchQ.value))

// Risques groupés par processus pour les selects
const risksByProcess = computed(() => {
  const map = {}
  props.allRisks.forEach(r => {
    const key = r.process_name || 'Processus inconnu'
    if (!map[key]) map[key] = []
    map[key].push(r)
  })
  return Object.entries(map).map(([process, risks]) => ({ process, risks }))
})

// Plans filtrés
const filteredPlans = computed(() => {
  let list = props.actionPlans || []
  if (activeFilter.value) list = list.filter(p => p.status === activeFilter.value)
  if (fPriority.value)    list = list.filter(p => p.priority === fPriority.value)
  if (fRisk.value)        list = list.filter(p => p.risk_id === parseInt(fRisk.value))
  if (fEntity.value)      list = list.filter(p => p.entity_id === parseInt(fEntity.value))
  if (searchQ.value) {
    const q = searchQ.value.toLowerCase()
    list = list.filter(p =>
      p.title?.toLowerCase().includes(q) ||
      p.code?.toLowerCase().includes(q) ||
      p.risk_libelle?.toLowerCase().includes(q) ||
      p.code_risk?.toLowerCase().includes(q) ||
      p.assigned_to_name?.toLowerCase().includes(q) ||
      p.nomenclature_label?.toLowerCase().includes(q)
    )
  }
  return list
})

// Groupés par risque
const groupedByRisk = computed(() => {
  const riskMap = {}

  filteredPlans.value.forEach(p => {
    if (!riskMap[p.risk_id]) {
      riskMap[p.risk_id] = {
        risk_id:                 p.risk_id,
        code_risk:               p.code_risk,
        risk_libelle:            p.risk_libelle,
        activity_code:           p.activity_code,
        activity_name:           p.activity_name,
        process_code:            p.process_code,
        process_name:            p.process_name,
        macro_code:              p.macro_code,
        macro_name:              p.macro_name,
        causes:                  p.causes,
        consequences:            p.consequences,
        nomenclature_code:       p.nomenclature_code,
        nomenclature_label:      p.nomenclature_label,
        risk_owner:              p.risk_owner,
        decision:                p.decision,
        // Recommandation (1 par risque, contient les plans d'action) / héritage plan_traitement
        recommendation_content:  p.recommendation_content ?? p.plan_traitement ?? null,
        controles_existants:     p.controles_existants,
        // Inhérent
        criticality_score:       p.criticality_score,
        zone_label:              p.zone_label,
        zone_color:              p.zone_color,
        impact_label:            p.impact_label,
        impact_score:            p.impact_score,
        frequency_label:         p.frequency_label,
        frequency_score:         p.frequency_score,
        frequency_recurrence:    p.frequency_recurrence,
        has_inherent:            !!p.has_inherent,
        // Contrôle
        control_id:              p.control_id,
        control_code:            p.control_code,
        control_type:            p.control_type,
        control_status:          p.control_status,
        control_owner:           p.control_owner,
        control_efficacite:      p.control_efficacite,
        control_periodicite:     p.control_periodicite,
        referential_type:        p.referential_type,
        referential_ref:         p.referential_ref,
        has_control:             !!p.has_control,
        // Résiduel
        residual_criticality_score: p.residual_criticality_score,
        residual_zone_label:        p.residual_zone_label,
        residual_zone_color:        p.residual_zone_color,
        residual_impact_label:      p.residual_impact_label,
        residual_impact_score:      p.residual_impact_score,
        residual_frequency_label:   p.residual_frequency_label,
        residual_frequency_score:   p.residual_frequency_score,
        has_residual:               !!p.has_residual,
        // Cible
        target_criticality_score: p.target_criticality_score,
        target_zone_label:        p.target_zone_label,
        target_zone_color:        p.target_zone_color,
        target_impact_label:      p.target_impact_label,
        target_impact_score:      p.target_impact_score,
        target_frequency_label:   p.target_frequency_label,
        target_frequency_score:   p.target_frequency_score,
        risk_target_date:         p.risk_target_date,
        risk_action_plan:         p.risk_action_plan,
        has_target:               !!p.has_target,
        actions: [],
      }
    }
    riskMap[p.risk_id].actions.push(p)
  })

  return Object.values(riskMap)
    .sort((a, b) => (b.criticality_score || 0) - (a.criticality_score || 0))
})

// Recommandations indexées par risk_id (utile pour les risques sans plan d'action)
const recommendationMap = computed(() => {
  const map = {}
  props.recommendations.forEach(r => { map[r.risk_id] = r })
  return map
})

// Regroupés par processus (contient les groupes par risque)
const groupedByProcess = computed(() => {
  const map = {}
  groupedByRisk.value.forEach(g => {
    const key = g.process_code || g.process_name || 'sans-processus'
    if (!map[key]) {
      map[key] = {
        key, process_code: g.process_code, process_name: g.process_name,
        macro_code: g.macro_code, macro_name: g.macro_name,
        risks: [],
      }
    }
    map[key].risks.push(g)
  })
  return Object.values(map).sort((a, b) => (a.process_name || '').localeCompare(b.process_name || ''))
})

// Risques sans aucun plan d'action (sur l'ensemble, pas juste les filtrés)
const risksWithoutActions = computed(() => {
  const withAP = new Set(props.actionPlans.map(p => p.risk_id))
  return props.allRisks.filter(r => !withAP.has(r.id))
})

const validMultiItems = computed(() => multiItems.value.filter(i => i.title?.trim()).length)

const allExpanded = computed(() =>
  groupedByRisk.value.length > 0
  && groupedByRisk.value.every(g => expandedRisks.value.has(g.risk_id))
  && groupedByProcess.value.every(p => expandedProcesses.value.has(p.key))
)

// ── INTERACTIONS GÉNÉRALES ────────────────────────────────────────────────────

const toggleRisk = (id) => {
  const s = new Set(expandedRisks.value)
  s.has(id) ? s.delete(id) : s.add(id)
  expandedRisks.value = s
}

const toggleProcess = (key) => {
  const s = new Set(expandedProcesses.value)
  s.has(key) ? s.delete(key) : s.add(key)
  expandedProcesses.value = s
}

const toggleAllRisks = () => {
  if (allExpanded.value) {
    expandedRisks.value = new Set()
    expandedProcesses.value = new Set()
  } else {
    expandedRisks.value = new Set(groupedByRisk.value.map(g => g.risk_id))
    expandedProcesses.value = new Set(groupedByProcess.value.map(p => p.key))
  }
}

const toggleActionDetail = (id) => {
  const s = new Set(expandedActions.value)
  if (s.has(id)) {
    s.delete(id)
  } else {
    s.add(id)
    loadDetail(id)
  }
  expandedActions.value = s
}

const clearFilters = () => {
  activeFilter.value = null
  fPriority.value = ''
  fRisk.value = ''
  fEntity.value = ''
  searchQ.value = ''
}

// ── CRUD PLANS (inline, sans modale) ─────────────────────────────────────────

const findProcessKeyForRisk = (riskId) => {
  const g = groupedByRisk.value.find(g => g.risk_id === riskId)
  if (!g) return null
  return g.process_code || g.process_name || 'sans-processus'
}

const ensureRiskVisible = (riskId) => {
  const pk = findProcessKeyForRisk(riskId)
  if (pk && !expandedProcesses.value.has(pk)) toggleProcess(pk)
  if (!expandedRisks.value.has(riskId)) toggleRisk(riskId)
}

const openCreate = (riskId = null) => {
  form.value = defaultForm()
  if (riskId) {
    form.value.risk_id = riskId
    ensureRiskVisible(riskId)
  }
  formPanel.value = { open: true, id: null, risk_id: riskId || null }
}

const openEdit = (plan) => {
  form.value = {
    id:             plan.id,
    risk_id:        plan.risk_id,
    entity_id:      plan.entity_id || '',
    title:          plan.title,
    description:    plan.description || '',
    action_plan:    plan.action_plan || '',
    priority:       plan.priority,
    status:         plan.status,
    assigned_to:    plan.assigned_to || '',
    target_date:    plan.target_date || '',
    start_date:     plan.start_date || '',
    cost_estimate:  plan.cost_estimate,
    actual_cost:    plan.actual_cost,
    notes:          plan.notes || '',
    source_status:  plan.source_status || '',
  }
  ensureRiskVisible(plan.risk_id)
  formPanel.value = { open: true, id: plan.id, risk_id: plan.risk_id }
}

const closeForm = () => { formPanel.value.open = false }

const saveForm = async () => {
  if (!form.value.title || !form.value.risk_id || !form.value.target_date) return
  saving.value = true
  try {
    const url    = form.value.id ? `/m/risk.core/action-plan/${form.value.id}` : '/m/risk.core/action-plan'
    const method = form.value.id ? 'PUT' : 'POST'
    const [r, d] = await apiFetch(url, form.value, method)
    if (r.ok && d.success) {
      showFlash(d.message || 'Enregistré ✓')
      formPanel.value.open = false
      reload()
    } else {
      showFlash(d.message || 'Erreur lors de l\'enregistrement', false)
    }
  } catch {
    showFlash('Erreur réseau', false)
  } finally {
    saving.value = false
  }
}

// ── RECOMMANDATION (1 par risque, contient les plans d'action) ───────────────

const openRecoEdit = (riskId, currentContent) => {
  recoEdit.value = { open: true, risk_id: riskId, content: currentContent || '' }
}

const closeRecoEdit = () => { recoEdit.value.open = false }

const saveReco = async () => {
  if (!recoEdit.value.risk_id) return
  recoSaving.value = true
  try {
    const [r, d] = await apiFetch('/m/risk.core/recommendation', {
      risk_id: recoEdit.value.risk_id,
      content: recoEdit.value.content,
    })
    if (r.ok && d.success) {
      showFlash('Recommandation enregistrée ✓')
      recoEdit.value.open = false
      reload()
    } else {
      showFlash(d.message || "Erreur lors de l'enregistrement de la recommandation", false)
    }
  } catch {
    showFlash('Erreur réseau', false)
  } finally {
    recoSaving.value = false
  }
}

const deleteAction = async (plan) => {
  if (!confirm(`Supprimer l'action "${plan.title}" ?`)) return
  try {
    const [r, d] = await apiFetch(`/m/risk.core/action-plan/${plan.id}`, {}, 'DELETE')
    if (r.ok && d.success) {
      showFlash('Action supprimée')
      reload()
    } else {
      showFlash(d.message || 'Erreur', false)
    }
  } catch {
    showFlash('Erreur réseau', false)
  }
}

// ── CRUD MULTIPLES (inline) ───────────────────────────────────────────────────

const openCreateMultiple = (riskId = null) => {
  multiPanel.value  = { open: true, risk_id: riskId || null }
  multiItems.value  = [{ title: '', description: '', priority: 'medium', status: 'pending', assigned_to: '', target_date: '' }]
  multiSameDates.value  = false
  multiCommonDate.value = ''
  if (riskId) ensureRiskVisible(riskId)
}

const closeMulti = () => { multiPanel.value.open = false }

const addMultiItem = () => {
  multiItems.value.push({ title: '', description: '', priority: 'medium', status: 'pending', assigned_to: '', target_date: '' })
}

const removeMultiItem = (i) => {
  if (multiItems.value.length > 1) multiItems.value.splice(i, 1)
}

const saveMultiple = async () => {
  const valid = multiItems.value.filter(i => i.title?.trim())
  if (!valid.length || !multiPanel.value.risk_id) return
  multiSaving.value = true
  let ok = 0, err = 0

  for (const item of valid) {
    const payload = {
      risk_id:     multiPanel.value.risk_id,
      title:       item.title.trim(),
      description: item.description || '',
      action_plan: '',
      priority:    item.priority || 'medium',
      status:      item.status   || 'pending',
      assigned_to: item.assigned_to || '',
      target_date: multiSameDates.value ? multiCommonDate.value : item.target_date,
      start_date:  '', cost_estimate: null, actual_cost: null, notes: '',
    }
    if (!payload.target_date) { err++; continue }

    try {
      const [r, d] = await apiFetch('/m/risk.core/action-plan', payload)
      r.ok && d.success ? ok++ : err++
    } catch { err++ }
  }

  multiSaving.value = false
  if (ok > 0) {
    showFlash(`${ok} action(s) créée(s)${err ? ', ' + err + ' erreur(s)' : ''}`)
    multiPanel.value.open = false
    reload()
  } else {
    showFlash('Aucune action créée. Vérifiez les données.', false)
  }
}

// ── DÉTAIL (chargement à la demande, mise en cache par action) ───────────────

const loadDetail = async (actionId) => {
  if (detailCache[actionId]?.loaded || detailCache[actionId]?.loading) return
  detailCache[actionId] = { tasks: [], comments: [], history: [], loaded: false, loading: true }
  try {
    const [tasks, comments, history] = await Promise.all([
      fetch(`/m/risk.core/action-plan/${actionId}/tasks`).then(r => r.json()),
      fetch(`/m/risk.core/action-plan/${actionId}/comments`).then(r => r.json()),
      fetch(`/m/risk.core/action-plan/${actionId}/history`).then(r => r.json()),
    ])
    detailCache[actionId] = {
      tasks: tasks.tasks || [], comments: comments.comments || [], history: history.history || [],
      loaded: true, loading: false,
    }
  } catch {
    detailCache[actionId] = { tasks: [], comments: [], history: [], loaded: false, loading: false }
  }
}

const refreshTasks = async (actionId) => {
  const t = await fetch(`/m/risk.core/action-plan/${actionId}/tasks`).then(r => r.json())
  if (!detailCache[actionId]) detailCache[actionId] = { tasks: [], comments: [], history: [], loaded: true, loading: false }
  detailCache[actionId].tasks = t.tasks || []
}

const refreshComments = async (actionId) => {
  const c = await fetch(`/m/risk.core/action-plan/${actionId}/comments`).then(r => r.json())
  if (!detailCache[actionId]) detailCache[actionId] = { tasks: [], comments: [], history: [], loaded: true, loading: false }
  detailCache[actionId].comments = c.comments || []
}

// ── COMMENTAIRES ──────────────────────────────────────────────────────────────

const addComment = async (actionId) => {
  const text = (newComments[actionId] || '').trim()
  if (!text) return
  try {
    const [r, d] = await apiFetch('/m/risk.core/action-plan/comment', {
      plan_id: actionId, comment: text, is_internal: false,
    })
    if (r.ok && d.success) {
      newComments[actionId] = ''
      showFlash('Commentaire ajouté')
      await refreshComments(actionId)
    }
  } catch { showFlash('Erreur', false) }
}

// ── TÂCHES (inline) ───────────────────────────────────────────────────────────

const openTaskCreate = (actionId) => {
  taskForm.value = { id: null, plan_id: actionId, title: '', description: '', assigned_to: '', target_date: '', status: 'pending' }
  taskPanel.value = { open: true, id: null, plan_id: actionId }
}

const editTask = (t) => {
  taskForm.value = { ...t }
  taskPanel.value = { open: true, id: t.id, plan_id: t.plan_id }
}

const closeTaskPanel = () => { taskPanel.value.open = false }

const saveTask = async () => {
  if (!taskForm.value.title) return
  taskSaving.value = true
  try {
    const url    = taskForm.value.id ? `/m/risk.core/action-plan/task/${taskForm.value.id}` : '/m/risk.core/action-plan/task'
    const method = taskForm.value.id ? 'PUT' : 'POST'
    const [r, d] = await apiFetch(url, taskForm.value, method)
    if (r.ok && d.success) {
      showFlash('Tâche enregistrée')
      const planId = taskPanel.value.plan_id
      taskPanel.value.open = false
      await refreshTasks(planId)
    } else { showFlash(d.message || 'Erreur', false) }
  } catch { showFlash('Erreur réseau', false) }
  finally { taskSaving.value = false }
}

const deleteTask = async (t) => {
  if (!confirm('Supprimer cette tâche ?')) return
  try {
    const [r, d] = await apiFetch(`/m/risk.core/action-plan/task/${t.id}`, {}, 'DELETE')
    if (r.ok && d.success) {
      showFlash('Tâche supprimée')
      await refreshTasks(t.plan_id)
    }
  } catch { showFlash('Erreur', false) }
}

const reload = () => router.reload({ preserveState: true })
</script>

<style scoped>
/* ═══ PAGE ═══ */
.ap-page { display:flex; flex-direction:column; height:calc(100vh - 60px); background:#f0f4f8; overflow:hidden; font-size:12px; }

/* ═══ TOPBAR ═══ */
.ap-topbar { display:flex; align-items:center; gap:14px; padding:8px 20px; background:#0f172a; color:#e2e8f0; flex-shrink:0; flex-wrap:wrap; }
.ap-topbar-brand { display:flex; align-items:center; gap:10px; }
.ap-topbar-brand i { font-size:22px; color:#60a5fa; }
.ap-topbar-brand h1 { font-size:14px; font-weight:700; color:#f1f5f9; margin:0; }
.ap-topbar-brand p  { font-size:9px; color:#64748b; margin:0; }
.ap-kpis { display:flex; gap:8px; margin-left:auto; }
.kpi { display:flex; flex-direction:column; align-items:center; padding:4px 10px; background:rgba(255,255,255,.07); border-radius:8px; cursor:default; }
.kpi span { font-size:16px; font-weight:800; line-height:1; color:#e2e8f0; }
.kpi small { font-size:8px; font-weight:600; color:#64748b; margin-top:1px; }
.kpi--alert span,.kpi--danger span { color:#f87171; }
.kpi--pending span { color:#94a3b8; }
.kpi--prog span { color:#60a5fa; }
.kpi--done span { color:#4ade80; }
.kpi--crit span { color:#fb923c; }
.ap-topbar-actions { display:flex; gap:7px; }
.btn-primary { display:flex; align-items:center; gap:5px; padding:6px 13px; background:#2563eb; color:#fff; border:none; border-radius:7px; font-size:11px; font-weight:700; cursor:pointer; }
.btn-primary:hover { background:#1d4ed8; }
.btn-secondary { display:flex; align-items:center; gap:5px; padding:6px 13px; background:#7c3aed; color:#fff; border:none; border-radius:7px; font-size:11px; font-weight:700; cursor:pointer; }
.btn-secondary:hover { background:#6d28d9; }
.btn-icon { width:32px; height:32px; border:1px solid rgba(255,255,255,.15); background:rgba(255,255,255,.07); color:#94a3b8; border-radius:7px; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:15px; }
.btn-icon:hover { background:rgba(255,255,255,.14); color:#e2e8f0; }

/* ═══ BARRE ÉVALUATION ═══ */
.eval-progress-bar { display:flex; align-items:center; gap:14px; padding:7px 20px; background:#fff; border-bottom:1px solid #e2e8f0; flex-shrink:0; flex-wrap:wrap; }
.evp-label { font-size:9px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.04em; }
.evp-step { display:flex; align-items:center; gap:5px; }
.evp-step-label { font-size:9px; color:#64748b; min-width:52px; }
.evp-track { width:70px; height:5px; background:#e2e8f0; border-radius:3px; }
.evp-fill { height:100%; border-radius:3px; transition:width .3s; }
.evp-count { font-size:9px; font-weight:600; min-width:28px; }

/* ═══ FILTRES ═══ */
.ap-filters { display:flex; align-items:center; justify-content:space-between; padding:7px 18px; background:#fff; border-bottom:1px solid #e2e8f0; flex-shrink:0; flex-wrap:wrap; gap:7px; }
.filter-chips { display:flex; gap:4px; flex-wrap:wrap; }
.fchip { padding:3px 11px; border-radius:20px; border:1.5px solid #e2e8f0; cursor:pointer; font-size:10px; font-weight:600; color:#64748b; }
.fchip:hover { border-color:#93c5fd; color:#2563eb; }
.fchip--on { border-color:#2563eb !important; background:#eff6ff !important; color:#1d4ed8 !important; }
.fnum { background:rgba(0,0,0,.08); border-radius:8px; padding:0 4px; margin-left:3px; font-size:9px; }
.filter-right { display:flex; align-items:center; gap:7px; flex-wrap:wrap; }
.fsel { font-size:11px; padding:5px 8px; border:1px solid #e2e8f0; border-radius:6px; background:#fff; color:#334155; }
.search-box { position:relative; }
.search-box i { position:absolute; left:8px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px; }
.fsearch { padding:5px 8px 5px 26px; border:1px solid #e2e8f0; border-radius:6px; font-size:11px; background:#f8fafc; width:170px; }
.btn-clear { display:flex; align-items:center; gap:4px; padding:4px 9px; border:1px solid #e2e8f0; border-radius:6px; background:#fff; color:#64748b; font-size:10px; cursor:pointer; }
.btn-toggle-all { display:flex; align-items:center; gap:4px; padding:4px 9px; border:1px solid #dbeafe; border-radius:6px; background:#eff6ff; color:#2563eb; font-size:10px; font-weight:700; cursor:pointer; }
.btn-toggle-all:hover { background:#dbeafe; }

/* ═══ BODY ═══ */
.ap-body { flex:1; overflow-y:auto; padding:12px 18px; display:flex; flex-direction:column; gap:10px; }

/* ═══ GROUPE RISQUE ═══ */
.risk-group { background:#fff; border-radius:12px; overflow:hidden; border:1px solid #e2e8f0; box-shadow:0 1px 3px rgba(0,0,0,.05); }
.rg-hdr { display:flex; align-items:center; gap:8px; padding:9px 14px; background:#f8fafc; border-bottom:1px solid #eef2f7; cursor:pointer; flex-wrap:wrap; }
.rg-hdr:hover { background:#f1f5f9; }
.rg-hdr-left { display:flex; align-items:center; gap:7px; flex:1; min-width:0; }
.rg-toggle { font-size:13px; color:#94a3b8; flex-shrink:0; }
.rg-code { font-family:monospace; font-size:9px; font-weight:800; color:#4338ca; background:#ede9fe; padding:1px 6px; border-radius:3px; flex-shrink:0; }
.rg-lib { font-size:12px; font-weight:700; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.rg-ctx { font-size:9px; color:#94a3b8; white-space:nowrap; }
.rg-hdr-right { display:flex; align-items:center; gap:7px; flex-shrink:0; flex-wrap:wrap; }
.rg-scores { display:flex; gap:4px; }
.rscore { display:inline-flex; align-items:center; gap:3px; font-size:10px; font-weight:800; padding:2px 8px; border-radius:7px; color:#fff; }
.rscore span { font-size:8px; opacity:.85; }
.dec-badge { font-size:9px; font-weight:700; padding:2px 7px; border-radius:5px; }
.dec-accepte,.dec-accepter { background:#dcfce7; color:#15803d; }
.dec-reduit,.dec-reduire  { background:#fef3c7; color:#d97706; }
.dec-transfere { background:#dbeafe; color:#1d4ed8; }
.dec-refuse   { background:#fee2e2; color:#dc2626; }
.dec-mitige   { background:#ede9fe; color:#7c3aed; }
.dec-controle { background:#e0f2fe; color:#0369a1; }
.rg-count { font-size:9px; color:#94a3b8; background:#f1f5f9; padding:2px 7px; border-radius:7px; border:1px solid #e2e8f0; }
.btn-add-action { display:flex; align-items:center; gap:3px; padding:3px 9px; background:#eff6ff; color:#2563eb; border:1.5px solid #dbeafe; border-radius:6px; font-size:10px; font-weight:700; cursor:pointer; }
.btn-add-action:hover { background:#dbeafe; }
.btn-multi-sm { display:flex; align-items:center; gap:3px; padding:3px 7px; background:#ede9fe; color:#7c3aed; border:1.5px solid #c4b5fd; border-radius:6px; font-size:10px; cursor:pointer; }
.btn-multi-sm:hover { background:#c4b5fd; }

/* ═══ TIMELINE ÉVALUATION ═══ */
.eval-timeline { padding:14px 18px 10px; background:#fafbff; border-bottom:1px solid #eef2f7; }
.et-row { display:flex; align-items:flex-start; gap:0; }
.ets { display:flex; align-items:flex-start; gap:8px; flex:1; min-width:0; }
.ets-dot { width:26px; height:26px; border-radius:50%; background:#e2e8f0; display:flex; align-items:center; justify-content:center; font-size:12px; color:#94a3b8; flex-shrink:0; transition:all .2s; }
.ets--done .ets-dot { color:#fff; }
.ets-content { flex:1; min-width:0; }
.ets-lbl { font-size:9px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.04em; margin-bottom:3px; }
.ets-score { display:inline-flex; align-items:center; font-size:13px; font-weight:900; padding:1px 7px; border-radius:6px; color:#fff; margin-right:4px; }
.ets-zone { font-size:10px; font-weight:600; }
.ets-sub { font-size:9px; color:#94a3b8; display:flex; align-items:center; gap:3px; margin-top:2px; }
.ets-rec { color:#8b5cf6 !important; }
.ets-nd { font-size:9px; color:#94a3b8; font-style:italic; }
.ets-ctrl-ok { font-size:10px; font-weight:700; color:#0ea5e9; display:flex; align-items:center; gap:3px; }
.ets-ctrl-code { font-size:9px; font-weight:700; background:#e0e7ff; color:#4338ca; padding:1px 6px; border-radius:4px; }
.ets-ctrl-type { font-size:9px; background:#e0f2fe; color:#0369a1; padding:1px 6px; border-radius:4px; }
.ets-ctrl-status { font-size:9px; background:#dcfce7; color:#15803d; padding:1px 6px; border-radius:4px; }
.ets-ref { font-size:9px; background:#fef3c7; color:#92400e; padding:1px 6px; border-radius:4px; }
.ets-efficacite { display:flex; align-items:center; gap:5px; margin-top:3px; }
.eff-track { width:60px; height:4px; background:#e2e8f0; border-radius:2px; }
.eff-fill { height:100%; border-radius:2px; }
.ets-efficacite span { font-size:9px; color:#0ea5e9; }
.ets-line { flex-shrink:0; width:20px; height:2px; background:#e2e8f0; margin-top:13px; }
.ets-line--on { background:#22c55e; }
/* Meta risque */
.risk-meta { display:flex; flex-wrap:wrap; gap:6px; margin-top:10px; padding-top:8px; border-top:1px solid #eef2f7; }
.rmeta-item { display:flex; align-items:center; gap:4px; font-size:10px; color:#64748b; }
.rmeta-item i { font-size:11px; color:#94a3b8; }
.rmeta-detail { font-size:10px; color:#64748b; }
.rmeta-detail summary { cursor:pointer; list-style:none; display:flex; align-items:center; gap:4px; }
.rmeta-detail p { margin:4px 0 0 16px; line-height:1.6; white-space:pre-line; background:#f8fafc; padding:6px 8px; border-radius:5px; border:1px solid #e2e8f0; }

/* ═══ RECOMMANDATION ═══ */
.reco-block { margin:0 18px 12px; background:#fffbea; border:1px solid #fde68a; border-radius:10px; overflow:hidden; }
.reco-hdr { display:flex; align-items:center; gap:6px; padding:8px 14px; background:#fef3c7; font-size:11px; font-weight:800; color:#92400e; text-transform:uppercase; letter-spacing:.03em; }
.reco-hdr i { font-size:14px; }
.reco-edit-btn { margin-left:auto; width:22px; height:22px; border:1px solid #fbbf24; background:#fff; color:#92400e; border-radius:6px; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:12px; }
.reco-edit-btn:hover { background:#fde68a; }
.reco-edit { padding:12px 14px; }
.reco-edit .finp { margin-bottom:8px; }
.reco-body { padding:10px 14px 12px; }
.reco-text { font-size:12px; color:#1e293b; line-height:1.7; margin:0; white-space:pre-line; }
.reco-empty { font-size:11px; color:#a16207; font-style:italic; margin:0; }
.reco-sub { margin-top:9px; padding-top:8px; border-top:1px dashed #fde68a; }
.reco-sub-lbl { display:flex; align-items:center; gap:4px; font-size:9px; font-weight:700; color:#166534; text-transform:uppercase; letter-spacing:.03em; margin-bottom:3px; }
.reco-sub p { font-size:11px; color:#334155; line-height:1.6; margin:0; white-space:pre-line; }
.reco-body .actions-section { margin-top:12px; padding-top:12px; border-top:1px dashed #fde68a; }

/* ═══ LIGNE DE CRÉATION DANS LE TABLEAU (remplace la carte séparée) ═══ */
.arow-form td { background:#eff6ff; padding:6px 9px; border-bottom:1px solid #bfdbfe; vertical-align:top; }
.arow-form:first-of-type td { border-top:2px solid #93c5fd; }
.finp-cell { padding:5px 7px; font-size:10.5px; }
.rf-td-title { min-width:200px; }
.finp-cell-sub { margin-top:4px; font-size:9.5px; color:#64748b; }
.rf-td-muted { color:#94a3b8; font-size:10px; text-align:center; white-space:nowrap; }
.rf-hint { font-size:8px; font-style:italic; }
.a-code--new { background:#dbeafe; color:#1d4ed8; }
.aib-save { background:#dcfce7; border-color:#86efac; color:#16a34a; }
.aib-save:hover:not(:disabled) { background:#bbf7d0; }
.aib-save:disabled { opacity:.4; cursor:not-allowed; }
.arow-form-extra td { background:#f5f8ff; padding:8px 14px 10px; border-bottom:2px solid #93c5fd; }
.rf-extra-row { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; }
.rf-extra-fld { display:flex; flex-direction:column; gap:3px; min-width:160px; flex:1; }
.rf-extra-fld--wide { flex:2; min-width:220px; }
.rf-extra-fld .finp { font-size:10.5px; padding:5px 8px; }

/* Indice sur l'origine de la progression (suivi, pas le plan d'action) */
.d-sec-hint { font-style:italic; font-weight:500; color:#94a3b8; text-transform:none; letter-spacing:0; font-size:9px; margin-left:4px; }



/* ═══ SECTION ACTIONS ═══ */
.actions-section { padding:10px 14px; }
.as-hdr { display:flex; align-items:center; justify-content:space-between; margin-bottom:7px; }
.as-title { font-size:11px; font-weight:700; color:#334155; display:flex; align-items:center; gap:4px; }
.as-btns { display:flex; gap:5px; }
.as-empty { text-align:center; padding:18px; color:#94a3b8; }
.as-empty i { font-size:26px; display:block; margin-bottom:5px; opacity:.3; }
.as-empty p { margin-bottom:7px; font-size:11px; }

/* ═══ PANNEAUX INLINE (remplacent les modales) ═══ */
.inline-panel { background:#f8fafc; border:1.5px solid #bfdbfe; border-radius:10px; margin-bottom:12px; box-shadow:0 1px 4px rgba(37,99,235,.08); animation:panelIn .15s ease; }
.inline-panel--floating { margin:0 0 12px 0; }
.inline-panel--nar { margin:6px 0 0; }
@keyframes panelIn { from{ opacity:0; transform:translateY(-4px);} to{ opacity:1; transform:translateY(0);} }
.ipf-hdr { display:flex; align-items:center; gap:8px; padding:9px 14px; background:#0f172a; color:#e2e8f0; font-size:11px; font-weight:700; border-radius:9px 9px 0 0; }
.ipf-hdr--edit { background:#312e81; }
.ip-close { width:24px; height:24px; border:1px solid rgba(255,255,255,.15); background:rgba(255,255,255,.08); color:#94a3b8; border-radius:6px; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:13px; margin-left:auto; }
.ip-close:hover { background:rgba(255,0,0,.2); color:#f87171; }
.ip-footer { display:flex; justify-content:flex-end; gap:8px; padding:10px 14px; border-top:1px solid #e2e8f0; background:#fff; border-radius:0 0 9px 9px; }
.m-risk-code { font-family:monospace; font-size:10px; font-weight:700; background:#7c3aed; color:#fff; padding:2px 7px; border-radius:4px; }

/* ═══ TABLE ACTIONS ═══ */
.actions-table { width:100%; border-collapse:collapse; font-size:11px; }
.actions-table th { text-align:left; padding:6px 9px; background:#f1f5f9; border-bottom:1.5px solid #e2e8f0; font-size:9px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
.actions-table td { padding:7px 9px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.arow { cursor:pointer; }
.arow:hover { background:#fafbff; }
.arow--late { background:#fef2f2 !important; }
.arow--done { opacity:.7; }
.arow--done .a-title { text-decoration:line-through; color:#94a3b8; }
.arow--open { background:#eff6ff !important; }
.a-toggle { font-size:11px; color:#94a3b8; margin-right:4px; }
.a-code { font-family:monospace; font-size:9px; font-weight:700; color:#4338ca; background:#ede9fe; padding:1px 5px; border-radius:3px; }
.a-auto { margin-left:4px; font-size:10px; color:#8b5cf6; }
.a-title { font-weight:600; color:#0f172a; }
.a-desc { font-size:9px; color:#64748b; margin-top:1px; }
.prio { font-size:9px; font-weight:700; padding:2px 7px; border-radius:7px; display:inline-block; }
.prio-critical { background:#fee2e2; color:#991b1b; }
.prio-high     { background:#fef3c7; color:#92400e; }
.prio-medium   { background:#dbeafe; color:#1e40af; }
.prio-low      { background:#dcfce7; color:#166534; }
.stat { font-size:9px; font-weight:700; padding:2px 7px; border-radius:7px; display:inline-block; }
.stat-pending     { background:#f1f5f9; color:#475569; }
.stat-in_progress { background:#dbeafe; color:#1d4ed8; }
.stat-review      { background:#ede9fe; color:#7c3aed; }
.stat-completed   { background:#dcfce7; color:#16a34a; }
.stat-cancelled   { background:#fee2e2; color:#dc2626; }
.stat-blocked     { background:#fef3c7; color:#d97706; }
.a-late { font-size:8px; color:#ef4444; font-weight:600; display:flex; align-items:center; gap:2px; margin-top:2px; }
.a-user,.a-date,.a-cost { font-size:10px; color:#334155; white-space:nowrap; }
.a-date--late { color:#dc2626 !important; font-weight:700; }
.a-none { color:#94a3b8; }
.text-red { color:#dc2626; font-weight:700; }
.prog-wrap { display:flex; align-items:center; gap:4px; }
.prog-track { width:44px; height:4px; background:#e2e8f0; border-radius:2px; }
.prog-fill { height:100%; background:#22c55e; border-radius:2px; }
.prog-pct { font-size:9px; color:#64748b; }
.a-btns { display:flex; gap:3px; }
.aib { width:24px; height:24px; border:1px solid #e2e8f0; border-radius:4px; background:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:11px; color:#64748b; }
.aib:hover { background:#f1f5f9; border-color:#94a3b8; }
.aib-del:hover { background:#fee2e2; border-color:#fca5a5; color:#dc2626; }

/* Détail inline */
.arow-detail td { background:#fafbff; padding:0; }
.action-detail-inline { padding:12px 14px; border-bottom:1px solid #e2e8f0; }
.adi-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px,1fr)); gap:10px; margin-bottom:12px; }
.adi-block--full { grid-column:1/-1; }
.adi-label { font-size:9px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin-bottom:5px; }
.adi-text { font-size:10px; color:#1e293b; line-height:1.6; margin:0; white-space:pre-line; background:#fff; padding:7px 9px; border-radius:5px; border:1px solid #e2e8f0; }
.adi-rows { display:flex; flex-direction:column; gap:2px; }
.adi-row { display:flex; justify-content:space-between; font-size:10px; padding:2px 0; border-bottom:1px solid #f1f5f9; }
.adi-row span:first-child { color:#64748b; }
.adi-row span:last-child { font-weight:500; color:#0f172a; }

/* ═══ RISQUES SANS ACTION ═══ */
.no-action-risks { background:#fff; border-radius:12px; border:1px solid #e2e8f0; overflow:hidden; }
.nar-hdr { display:flex; align-items:center; gap:7px; padding:9px 14px; background:#f8fafc; cursor:pointer; font-size:11px; font-weight:500; color:#64748b; }
.nar-icon { color:#94a3b8; }
.nar-list { padding:10px 12px; display:flex; flex-direction:column; gap:5px; }
.nar-item { display:flex; align-items:center; gap:7px; padding:6px 9px; background:#f8fafc; border-radius:6px; border:1px solid #e2e8f0; flex-wrap:wrap; }
.nar-code { font-family:monospace; font-size:9px; font-weight:700; color:#4338ca; background:#ede9fe; padding:1px 5px; border-radius:3px; flex-shrink:0; }
.nar-lib { font-size:10px; color:#0f172a; flex:1; }
.nar-ctx { font-size:9px; color:#94a3b8; flex-shrink:0; }

/* ═══ EMPTY STATE ═══ */
.empty-state { text-align:center; padding:50px; color:#94a3b8; background:#fff; border-radius:12px; }
.empty-state i { font-size:42px; display:block; margin-bottom:10px; opacity:.2; }
.empty-state p { margin-bottom:12px; font-size:13px; }
.btn-add { display:flex; align-items:center; gap:5px; padding:7px 14px; background:#2563eb; color:#fff; border:none; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer; margin:0 auto; }
.btn-add:hover { background:#1d4ed8; }
.btn-add-sm { display:flex; align-items:center; gap:3px; padding:4px 9px; background:#2563eb; color:#fff; border:none; border-radius:6px; font-size:10px; font-weight:700; cursor:pointer; }
.btn-add-sm:hover { background:#1d4ed8; }
.btn-multi-sm2 { display:flex; align-items:center; gap:3px; padding:4px 9px; background:#ede9fe; color:#7c3aed; border:1.5px solid #c4b5fd; border-radius:6px; font-size:10px; cursor:pointer; }

/* Formulaire */
.fgrid { display:grid; grid-template-columns:1fr 1fr; gap:10px; padding:14px; }
.fg { display:flex; flex-direction:column; gap:3px; }
.fg-full { grid-column:1/-1; }
.flbl { font-size:10px; font-weight:700; color:#475569; }
.finp { padding:6px 9px; border:1px solid #e2e8f0; border-radius:6px; font-size:11px; color:#0f172a; background:#fff; font-family:inherit; width:100%; box-sizing:border-box; }
.finp:focus { outline:none; border-color:#93c5fd; box-shadow:0 0 0 2px rgba(147,197,253,.25); }
textarea.finp { resize:vertical; min-height:48px; }
.finp-readonly { display:flex; align-items:center; background:#f1f5f9; color:#475569; font-weight:600; }
/* Multi */
.multi-hint { display:flex; align-items:center; gap:7px; padding:9px 14px; margin:0 14px 12px; background:#fffbeb; border:1px solid #fde68a; border-radius:7px; font-size:10px; color:#92400e; }
.multi-opts { display:flex; align-items:center; gap:10px; margin:0 14px 10px; font-size:11px; color:#475569; }
.finp-inline { width:auto !important; display:inline-block; }
.multi-list { display:flex; flex-direction:column; gap:6px; max-height:380px; overflow-y:auto; margin:0 14px 10px; }
.multi-item { display:flex; gap:7px; align-items:flex-start; padding:7px; background:#fff; border-radius:7px; border:1px solid #e2e8f0; }
.multi-idx { min-width:24px; height:24px; background:#e2e8f0; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; color:#475569; flex-shrink:0; margin-top:3px; }
.multi-fields { flex:1; display:grid; grid-template-columns:1.2fr 1fr .6fr .6fr .7fr .6fr; gap:5px; }
.multi-del { width:24px; height:24px; border:1px solid #e2e8f0; border-radius:4px; background:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; color:#94a3b8; flex-shrink:0; margin-top:3px; }
.multi-del:hover { background:#fee2e2; border-color:#fca5a5; color:#dc2626; }
.multi-footer-actions { display:flex; align-items:center; gap:10px; margin:0 14px 12px; }
.multi-count { font-size:10px; color:#64748b; font-weight:600; }

/* Détail : tâches / commentaires / historique inline */
.d-section { border:1px solid #e2e8f0; border-radius:9px; overflow:hidden; margin-top:10px; background:#fff; }
.d-sec-hdr { display:flex; align-items:center; justify-content:space-between; padding:7px 12px; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-size:10px; font-weight:700; color:#334155; }
.d-empty { padding:10px 12px; font-size:10px; color:#94a3b8; text-align:center; }
.tasks-list { display:flex; flex-direction:column; }
.task-item { display:flex; align-items:center; gap:8px; padding:7px 12px; border-bottom:1px solid #f1f5f9; }
.task-item:hover { background:#fafbff; }
.task-completed .task-title { text-decoration:line-through; color:#94a3b8; }
.task-ic { font-size:14px; color:#94a3b8; flex-shrink:0; }
.task-completed .task-ic { color:#22c55e; }
.task-info { flex:1; min-width:0; }
.task-title { font-size:11px; font-weight:600; color:#0f172a; }
.task-meta { font-size:9px; color:#94a3b8; display:block; margin-top:1px; }
.task-btns { display:flex; gap:3px; }
.task-inline-form { background:#f8fafc; border-bottom:1px solid #e2e8f0; }
.task-inline-form .fgrid { padding:10px 12px; grid-template-columns:1fr 1fr; }
.task-inline-footer { display:flex; justify-content:flex-end; gap:8px; padding:8px 12px; border-top:1px solid #e2e8f0; }
/* Commentaires */
.cmt-input-row { display:flex; gap:7px; padding:9px 12px; border-bottom:1px solid #f1f5f9; }
.cmt-input-row .finp { flex:1; }
.btn-send { width:34px; height:34px; background:#2563eb; color:#fff; border:none; border-radius:6px; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:13px; flex-shrink:0; }
.btn-send:disabled { opacity:.4; cursor:not-allowed; }
.cmt-item { padding:7px 12px; border-bottom:1px solid #f1f5f9; font-size:10px; }
.cmt-meta { display:flex; align-items:center; gap:6px; margin-bottom:3px; }
.cmt-meta strong { color:#0f172a; }
.cmt-date { font-size:9px; color:#94a3b8; }
.cmt-internal { font-size:8px; background:#fef3c7; color:#d97706; padding:1px 5px; border-radius:4px; }
.cmt-item p { margin:0; color:#334155; line-height:1.6; }
/* Historique */
.hist-item { display:flex; align-items:center; gap:10px; padding:5px 12px; border-bottom:1px solid #f1f5f9; font-size:10px; color:#64748b; }
.hist-action { font-size:8px; font-weight:700; background:#e0e7ff; color:#4338ca; padding:1px 6px; border-radius:5px; text-transform:uppercase; flex-shrink:0; }
.hist-desc { flex:1; }
.hist-user { font-weight:600; color:#0f172a; flex-shrink:0; }
.hist-date { color:#94a3b8; flex-shrink:0; font-size:9px; }

/* ═══ BOUTONS COMMUNS ═══ */
.btn-cancel { display:flex; align-items:center; gap:4px; padding:7px 14px; border:1.5px solid #e2e8f0; border-radius:7px; background:#fff; color:#475569; font-size:11px; font-weight:600; cursor:pointer; }
.btn-save { display:flex; align-items:center; gap:5px; padding:7px 16px; background:#2563eb; color:#fff; border:none; border-radius:7px; font-size:11px; font-weight:700; cursor:pointer; }
.btn-save:hover:not(:disabled) { background:#1d4ed8; }
.btn-save:disabled { opacity:.45; cursor:not-allowed; }
.dec-hint { display:block; font-size:9px; margin-top:3px; }
.dec-hint i { font-size:10px; margin-right:3px; }

/* ═══ FLASH ═══ */
.flash { position:fixed; bottom:18px; right:18px; z-index:9999; display:flex; align-items:center; gap:7px; padding:10px 16px; border-radius:10px; font-size:11px; font-weight:700; box-shadow:0 4px 16px rgba(0,0,0,.12); }
.flash-ok  { background:#f0fdf4; border:1px solid #86efac; color:#15803d; }
.flash-err { background:#fef2f2; border:1px solid #fca5a5; color:#dc2626; }
.fl-enter-active,.fl-leave-active { transition:opacity .2s,transform .2s; }
.fl-enter-from,.fl-leave-to { opacity:0; transform:translateX(20px); }
/* Spin */
@keyframes spin { to { transform:rotate(360deg); } }
.spin { animation:spin .7s linear infinite; display:inline-block; }
/* Scrollbars */
::-webkit-scrollbar { width:4px; height:4px; }
::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:2px; }

/* Responsive */
@media(max-width:1024px) {
  .multi-fields { grid-template-columns:1fr 1fr; }
}
@media(max-width:768px) {
  .rg-hdr { flex-direction:column; align-items:flex-start; }
  .rg-hdr-right { margin-left:0; }
  .et-row { flex-direction:column; gap:10px; }
  .ets-line { display:none; }
  .rf-extra-row { flex-direction:column; }
  .rf-extra-fld, .rf-extra-fld--wide { min-width:0; width:100%; }
}
@media(max-width:640px) {
  .multi-fields { grid-template-columns:1fr; }
  .fgrid { grid-template-columns:1fr; }
}
/* ═══ BOUTONS ICÔNE SEULE (remplacent les anciens boutons texte "Ajouter"/"Multiples") ═══ */
.btn-icon-only { padding:6px 9px !important; gap:0 !important; }
.btn-add-action.btn-icon-only,
.btn-add-sm.btn-icon-only { width:26px; height:26px; padding:0 !important; justify-content:center; }
.btn-primary.btn-icon-only,
.btn-secondary.btn-icon-only { width:32px; height:32px; padding:0 !important; justify-content:center; }

/* ═══ GROUPES PAR PROCESSUS ═══ */
.proc-group { display:flex; flex-direction:column; gap:10px; margin-bottom:4px; }
.proc-hdr {
  display:flex; align-items:center; gap:8px; padding:9px 16px; cursor:pointer;
  background:linear-gradient(90deg,#eef2ff,#f8fafc); border:1px solid #c7d2fe; border-radius:10px;
  position:relative; z-index:1;
}
.proc-hdr:hover { background:linear-gradient(90deg,#e0e7ff,#eef2ff); }
.proc-toggle { font-size:13px; color:#6366f1; flex-shrink:0; }
.proc-macro { font-size:10px; color:#818cf8; font-weight:600; white-space:nowrap; }
.proc-name { font-size:12px; font-weight:800; color:#312e81; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.proc-code { font-family:monospace; font-size:9px; font-weight:700; color:#4338ca; background:#e0e7ff; padding:1px 6px; border-radius:4px; flex-shrink:0; }
.proc-count { margin-left:auto; font-size:9px; font-weight:700; color:#6366f1; background:#eef2ff; border:1px solid #c7d2fe; padding:2px 9px; border-radius:8px; flex-shrink:0; }
.proc-body { display:flex; flex-direction:column; gap:10px; padding-left:14px; border-left:2px solid #e0e7ff; margin-left:8px; }

/* ═══ CORRECTIFS ANTI-CHEVAUCHEMENT ═══ */
/* Le tableau défile horizontalement plutôt que de faire chevaucher les colonnes */
.actions-table-wrap { width:100%; overflow-x:auto; }
.actions-table { min-width:920px; }
/* Les panneaux inline et les cartes ne se superposent jamais : flux normal, jamais d'absolute/fixed sauf le flash */
.risk-group, .reco-block, .inline-panel, .actions-section, .eval-timeline { position:relative; z-index:0; }
.rg-hdr, .as-hdr { flex-wrap:wrap; row-gap:6px; }
.rg-hdr-right { row-gap:6px; }
.multi-fields { min-width:0; }
.multi-item { flex-wrap:wrap; }
</style>