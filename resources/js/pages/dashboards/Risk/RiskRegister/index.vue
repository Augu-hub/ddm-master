<template>
  <VerticalLayout>
    <div class="rr-page">

      <!-- ── HEADER ── -->
      <div class="rr-header">
        <div class="d-flex align-items-center gap-3">
          <div class="rr-header-icon"><i class="ti ti-shield-check"></i></div>
          <div>
            <h4 class="mb-0 fw-bold">Registre des risques</h4>
            <small class="text-muted">Par entité · processus · activité · facteur</small>
          </div>
        </div>
        <div class="d-flex gap-2 align-items-center">
          <span v-if="props.currentUser?.name" class="rr-user-badge">
            <i class="ti ti-user me-1"></i>{{ props.currentUser.name }}
            <span v-if="props.isRiskAdmin" class="rr-admin-tag ms-1">Admin</span>
          </span>
          <Link :href="route('risk.core.risk-library.index')" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-books me-1"></i>Bibliothèque
          </Link>
        </div>
      </div>

      <!-- ── STATS ── -->
      <div class="row g-2 mb-3">
        <div class="col-6 col-md-3" v-for="c in statCards" :key="c.label">
          <div class="rr-stat" :class="'rr-stat--' + c.color">
            <i :class="'ti ' + c.icon"></i>
            <div>
              <div class="rr-stat-val">{{ c.value }}</div>
              <div class="rr-stat-lbl">{{ c.label }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── SÉLECTEUR ENTITÉ ── -->
      <div class="rr-entity-bar mb-3">
        <span class="rr-entity-lbl"><i class="ti ti-building me-1"></i>Entité :</span>
        <div class="d-flex gap-2 flex-wrap">
          <button v-for="e in props.entities" :key="e.id"
                  :class="['btn btn-sm', selectedEntityId === e.id ? 'btn-primary' : 'btn-outline-secondary']"
                  @click="selectEntity(e.id)">
            {{ e.name }}
          </button>
        </div>
      </div>

      <!-- États -->
      <div v-if="!selectedEntityId" class="rr-empty">
        <i class="ti ti-building-skyscraper d-block fs-1 mb-2 opacity-20"></i>
        Sélectionnez une entité
      </div>
      <div v-else-if="treeLoading" class="rr-empty">
        <span class="spinner-border spinner-border-sm me-2"></span>Chargement…
      </div>
      <div v-else-if="!tree.length" class="rr-empty">
        <i class="ti ti-circles-relation d-block fs-1 mb-2 opacity-20"></i>
        Aucune activité assignée à cette entité
      </div>

      <!-- ── TABLEAU ── -->
      <template v-else>
        <div class="mb-2">
          <small class="text-muted">
            <i class="ti ti-lock me-1"></i>
            Activités <strong>hors périmètre</strong> = grisées (consultation uniquement).
            <i class="ti ti-books ms-3 me-1 text-info"></i>
            <span class="rr-lib-hint">+ Biblio</span> = transférer en bibliothèque.
          </small>
        </div>
        <div class="rr-table-wrap">
          <table class="rr-table">
            <thead>
              <tr>
                <th class="rr-th" style="width:44px">Type</th>
                <th class="rr-th" style="width:32px">N°</th>
                <th class="rr-th" style="min-width:140px">Processus</th>
                <th class="rr-th" style="min-width:130px">Activité</th>
                <th class="rr-th" style="min-width:240px">Risque</th>
                <th class="rr-th" style="width:90px">Criticité</th>
                <th class="rr-th" style="width:90px">Responsable</th>
                <th class="rr-th" style="width:95px">Actions</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="(macro, mi) in tree" :key="macro.id">
                <template v-for="(process, pi) in macro.processes" :key="process.id">
                  <template v-for="(activity, ai) in process.activities" :key="activity.id">

                    <!-- ────── CAS 1 : PAS DE FACTEUR ────── -->
                    <template v-if="!factorOf(activity.id)">
                      <tr :class="['rr-row-nofactor', { 'rr-dimmed': !canEdit(activity.id) }]">

                        <!-- Type macro — rowspan sur tout le macro -->
                        <td v-if="pi === 0 && ai === 0"
                            class="rr-td rr-td--macro"
                            :rowspan="macroRows(macro)">
                          <div class="rr-macro-dot"
                               :style="{ background: macroColor(macro.kind) }">
                            {{ macroLabel(macro.kind) }}
                          </div>
                        </td>

                        <!-- N° — rowspan sur tout le processus -->
                        <td v-if="ai === 0"
                            class="rr-td rr-td--n"
                            :rowspan="processRows(process)">
                          {{ pi + 1 }}
                        </td>

                        <!-- Processus — rowspan sur tout le processus -->
                        <td v-if="ai === 0"
                            class="rr-td rr-td--proc"
                            :rowspan="processRows(process)">
                          <div class="fw-semibold small">{{ process.name }}</div>
                          <span class="rr-proc-code">{{ process.code }}</span>
                        </td>

                        <!-- Activité -->
                        <td class="rr-td rr-td--act">
                          <div class="fw-semibold small">{{ activity.name }}</div>
                          <span class="rr-act-code">{{ activity.code }}</span>
                          <div v-if="!canEdit(activity.id)" class="rr-locked mt-1">
                            <i class="ti ti-lock me-1"></i>Hors périmètre
                          </div>
                        </td>

                        <!-- Zone facteur / actions -->
                        <td colspan="4" class="rr-td rr-td--factorzone">
                          <template v-if="canEdit(activity.id)">
                            <!-- Chips IA si disponibles -->
                            <div v-if="iaFactorSugg[activity.id]?.length"
                                 class="d-flex flex-wrap gap-2 mb-2">
                              <span class="rr-ia-label">
                                <i class="ti ti-sparkles me-1"></i>IA :
                              </span>
                              <span v-for="(f, fi) in iaFactorSugg[activity.id]" :key="fi"
                                    class="rr-ia-chip"
                                    @click="useIaFactor(activity, f)">
                                <i class="ti ti-plus me-1"></i>{{ f }}
                              </span>
                            </div>
                            <div v-if="iaFactorDesc[activity.id]" class="rr-factor-desc mb-1">
                              {{ iaFactorDesc[activity.id] }}
                            </div>
                            <div class="d-flex gap-2 flex-wrap align-items-center">
                              <button class="btn btn-xs rr-btn-ia"
                                      :disabled="!!iaFactorLoading[activity.id]"
                                      @click="suggestFactors(activity, process, macro)">
                                <span v-if="iaFactorLoading[activity.id]"
                                      class="spinner-border spinner-border-sm"></span>
                                <i v-else class="ti ti-sparkles"></i>
                                Facteur IA
                              </button>
                              <button class="btn btn-xs rr-btn-add"
                                      @click="openFactorModal(activity, process, macro)">
                                <i class="ti ti-plus me-1"></i>Définir le facteur
                              </button>
                            </div>
                          </template>
                          <span v-else class="text-muted small">—</span>
                        </td>
                      </tr>
                    </template>

                    <!-- ────── CAS 2 : AVEC FACTEUR ────── -->
                    <template v-else>

                      <!-- Ligne facteur -->
                      <tr :class="['rr-row-factor', { 'rr-dimmed': !canEdit(activity.id) }]">

                        <td v-if="pi === 0 && ai === 0"
                            class="rr-td rr-td--macro"
                            :rowspan="macroRows(macro)">
                          <div class="rr-macro-dot"
                               :style="{ background: macroColor(macro.kind) }">
                            {{ macroLabel(macro.kind) }}
                          </div>
                        </td>

                        <td v-if="ai === 0"
                            class="rr-td rr-td--n"
                            :rowspan="processRows(process)">
                          {{ pi + 1 }}
                        </td>

                        <td v-if="ai === 0"
                            class="rr-td rr-td--proc"
                            :rowspan="processRows(process)">
                          <div class="fw-semibold small">{{ process.name }}</div>
                          <span class="rr-proc-code">{{ process.code }}</span>
                        </td>

                        <!-- Activité — couvre facteur + N risques + bouton+ -->
                        <td class="rr-td rr-td--act"
                            :rowspan="activityRows(activity)">
                          <div class="fw-semibold small">{{ activity.name }}</div>
                          <span class="rr-act-code">{{ activity.code }}</span>
                          <div v-if="!canEdit(activity.id)" class="rr-locked mt-1">
                            <i class="ti ti-lock me-1"></i>Hors périmètre
                          </div>
                        </td>

                        <!-- Facteur — s'étend sur colonnes Risque+Criticité+Responsable+Actions -->
                        <td colspan="4" class="rr-td rr-td--factor">
                          <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="rr-factor-badge">
                              <i class="ti ti-alert-triangle me-1"></i>Facteur de risque
                            </span>
                            <span class="rr-factor-name">
                              {{ factorOf(activity.id).label }}
                            </span>
                            <span v-if="factorOf(activity.id).is_ia" class="rr-ia-label ms-1">
                              <i class="ti ti-sparkles me-1"></i>IA
                            </span>
                            <button v-if="canEdit(activity.id)"
                                    class="btn btn-xs rr-btn-edit-factor ms-auto"
                                    @click="openFactorModal(activity, process, macro)">
                              <i class="ti ti-pencil me-1"></i>Modifier
                            </button>
                            <tr :class="['rr-row-add', { 'rr-dimmed': !canEdit(activity.id) }]">
                        <td class="rr-td" colspan="3"></td>
                        <td class="rr-td" colspan="4">
                          <template v-if="canEdit(activity.id)">
                            <div class="d-flex gap-2 flex-wrap align-items-center">
                              <button class="btn btn-xs rr-btn-add"
                                      @click="openCreateModal(activity, process, macro)">
                                <i class="ti ti-plus me-1"></i>Nouveau risque
                              </button>
                              <button class="btn btn-xs rr-btn-incident"
                                      @click="openIncidentModal(activity, process, macro)">
                                <i class="ti ti-alert-triangle me-1"></i>Depuis incident
                              </button>
                              <button class="btn btn-xs rr-btn-ia"
                                      :disabled="!!iaRiskLoading[factorOf(activity.id).id]"
                                      @click="triggerRiskIA(activity, process, macro)">
                                <span v-if="iaRiskLoading[factorOf(activity.id).id]"
                                      class="spinner-border spinner-border-sm"></span>
                                <i v-else class="ti ti-sparkles"></i>
                                Risques IA
                              </button>
                            </div>
                          </template>
                        </td>
                      </tr>
                          </div>
                          <div v-if="factorOf(activity.id).description"
                               class="rr-factor-desc mt-1">
                            {{ factorOf(activity.id).description }}
                          </div>
                        </td>
                      </tr>

                      <!-- Lignes risques -->
                      <tr v-for="(risk, ri) in risksOf(activity.id)"
                          :key="risk.id"
                          :class="['rr-row-risk', { 'rr-dimmed': !canEdit(activity.id) }]"
                          @click="canEdit(activity.id) && openDetail(risk)">

                        <!-- N° risque -->
                        <td class="rr-td rr-td--rseq text-center">
                          <span class="rr-rseq">{{ ri + 1 }}</span>
                        </td>

                        <!-- Code + libellé -->
                        <td colspan="2" class="rr-td">
                          <div class="d-flex align-items-start gap-2">
                            <span class="rr-risk-code flex-shrink-0">{{ risk.code_risk }}</span>
                            <div>
                              <div class="small fw-semibold lh-sm">{{ risk.libelle }}</div>
                              <small v-if="risk.nomenclature_label" class="text-muted">
                                <i class="ti ti-tag me-1"></i>{{ risk.nomenclature_label }}
                              </small>
                              <div v-if="isMissingAnalysis(risk)"
                                   class="rr-missing-hint mt-1">
                                <i class="ti ti-pencil me-1"></i>Analyse à compléter
                              </div>
                            </div>
                          </div>
                        </td>

                        <!-- Criticité -->
                        <td class="rr-td text-center">
                          <span v-if="risk.zone_label" class="rr-zone"
                                :style="{
                                  background:  (risk.zone_color || '#94a3b8') + '22',
                                  color:       risk.zone_color || '#94a3b8',
                                  borderColor: (risk.zone_color || '#94a3b8') + '55',
                                }">
                            {{ risk.zone_label }}
                            <small v-if="risk.criticality_score" class="ms-1 opacity-70">
                              ({{ risk.criticality_score }})
                            </small>
                          </span>
                          <span v-else class="text-muted small">—</span>
                        </td>

                        <!-- Responsable -->
                        <td class="rr-td">
                          <small v-if="risk.owner" class="text-muted">
                            <i class="ti ti-user me-1"></i>{{ risk.owner }}
                          </small>
                          <span v-else class="text-muted small">—</span>
                        </td>

                        <!-- Actions -->
                        <td class="rr-td" @click.stop>
                          <div v-if="canEdit(activity.id)" class="d-flex flex-column gap-1">
                            <div class="d-flex gap-1">
                              <Link :href="route('risk.core.risks.edit', risk.id)"
                                    class="btn btn-xs btn-outline-primary"
                                    title="Modifier">
                                <i class="ti ti-pencil"></i>
                              </Link>
                              <button class="btn btn-xs btn-outline-danger"
                                      title="Supprimer"
                                      @click="confirmDelete(risk)">
                                <i class="ti ti-trash"></i>
                              </button>
                            </div>
                            <!-- Transfert bibliothèque -->
                            <button v-if="!risk.moved_to_library_at"
                                    class="btn btn-xs rr-btn-lib"
                                    title="Transférer en bibliothèque"
                                    @click="confirmLibrary(risk)">
                              <i class="ti ti-books me-1"></i>+ Biblio
                            </button>
                            <span v-else class="rr-lib-badge">
                              <i class="ti ti-books me-1"></i>En biblio
                            </span>
                          </div>
                        </td>
                      </tr>

                      <!-- Ligne bouton + nouveau risque -->
                      

                    </template>
                    <!-- /CAS 2 -->

                  </template><!-- /activity -->
                </template><!-- /process -->
              </template><!-- /macro -->
            </tbody>
          </table>
        </div>
      </template>

    </div><!-- /page -->

    <!-- ══ OFFCANVAS DÉTAIL ══ -->
    <BOffcanvas v-model="showDetail" placement="end" style="width:480px">
      <template #header>
        <div v-if="detailRisk" class="d-flex gap-2 align-items-center flex-wrap">
          <span class="rr-risk-code">{{ detailRisk.code_risk }}</span>
          <span class="badge"
                :class="'bg-' + detailRisk.statut_badge + '-subtle text-' + detailRisk.statut_badge">
            {{ detailRisk.statut_label }}
          </span>
          <span v-if="detailRisk.moved_to_library_at" class="badge bg-info-subtle text-info">
            <i class="ti ti-books me-1"></i>Bibliothèque
          </span>
        </div>
      </template>
      <template v-if="detailRisk">
        <h6 class="fw-bold mb-3">{{ detailRisk.libelle }}</h6>
        <div v-if="detailRisk.zone_label"
             class="rounded-3 mb-3 p-3"
             :style="'background:' + (detailRisk.zone_color || '#94a3b8') + '12;border-left:4px solid ' + (detailRisk.zone_color || '#94a3b8')">
          <strong>{{ detailRisk.zone_label }}</strong>
          <span class="text-muted small ms-2">Score : {{ detailRisk.criticality_score }}</span>
          <div class="small text-muted mt-1">
            Impact : {{ detailRisk.impact_label ?? '—' }} ·
            Fréquence : {{ detailRisk.frequency_label ?? '—' }}
          </div>
        </div>
        <div class="vstack gap-3">
          <template v-for="f in detailFields" :key="f.key">
            <div v-if="detailRisk[f.key]">
              <div class="text-muted fw-semibold text-uppercase mb-1"
                   style="font-size:.63rem;letter-spacing:.04em">
                <i :class="'ti ' + f.icon + ' me-1'"></i>{{ f.label }}
              </div>
              <div class="small" style="white-space:pre-line">{{ detailRisk[f.key] }}</div>
            </div>
          </template>
        </div>
        <div class="mt-3 pt-3 border-top d-flex gap-2">
          <Link :href="route('risk.core.risks.edit', detailRisk.id)"
                class="btn btn-outline-primary btn-sm">
            <i class="ti ti-pencil me-1"></i>Modifier
          </Link>
          <button v-if="!detailRisk.moved_to_library_at"
                  class="btn btn-sm rr-btn-lib"
                  @click="confirmLibrary(detailRisk); showDetail = false">
            <i class="ti ti-books me-1"></i>Transférer en bibliothèque
          </button>
        </div>
      </template>
    </BOffcanvas>

    <!-- ══ MODAL FACTEUR ══ -->
    <BModal v-model="showFactorModal"
            :title="factorCtx ? 'Facteur — ' + factorCtx.activity.name : 'Facteur de risque'"
            size="md" hide-footer @hidden="resetFactor">
      <div v-if="factorCtx" class="row g-3">
        <div class="col-12">
          <div class="rr-ctx-box">
            <span class="rr-proc-code me-1">{{ factorCtx.process.code }}</span>
            {{ factorCtx.process.name }}
            <i class="ti ti-chevron-right mx-1 opacity-40"></i>
            <span class="rr-act-code me-1">{{ factorCtx.activity.code }}</span>
            {{ factorCtx.activity.name }}
          </div>
        </div>
        <!-- Chips IA -->
        <div v-if="iaFactorSugg[factorCtx.activity.id]?.length" class="col-12">
          <div class="rr-ia-box">
            <div class="rr-ia-box-title">
              <i class="ti ti-sparkles me-1"></i>Suggestions IA
            </div>
            <div class="d-flex flex-wrap gap-2 mt-2">
              <div v-for="(s, si) in iaFactorSugg[factorCtx.activity.id]" :key="si"
                   :class="['rr-ia-chip', { 'rr-ia-chip--on': factorForm.label === s }]"
                   @click="factorForm.label = s;
                           factorForm.description = iaFactorDesc[factorCtx.activity.id] || ''">
                {{ s }}
              </div>
            </div>
          </div>
        </div>
        <div v-else-if="iaFactorLoading[factorCtx.activity.id]" class="col-12">
          <div class="text-muted small d-flex gap-2 align-items-center">
            <span class="spinner-border spinner-border-sm"></span>L'IA analyse…
          </div>
        </div>
        <div class="col-12">
          <button class="btn btn-xs rr-btn-ia mb-2"
                  :disabled="!!iaFactorLoading[factorCtx.activity.id]"
                  @click="suggestFactors(factorCtx.activity, factorCtx.process, factorCtx.macro)">
            <i class="ti ti-sparkles me-1"></i>Suggérer avec l'IA
          </button>
          <label class="form-label fw-semibold">
            Libellé du facteur <span class="text-danger">*</span>
          </label>
          <input v-model="factorForm.label" type="text" class="form-control"
                 placeholder="Ex : Défaillance fournisseurs, Risques humains…"/>
          <div class="form-text text-muted">1 seul facteur par activité</div>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Description (optionnel)</label>
          <textarea v-model="factorForm.description"
                    class="form-control form-control-sm" rows="2"
                    placeholder="Contexte du facteur…"></textarea>
        </div>
      </div>
      <div class="d-flex justify-content-end gap-2 mt-3 pt-3 border-top">
        <button class="btn btn-outline-secondary btn-sm"
                @click="showFactorModal = false">Annuler</button>
        <button class="btn btn-primary btn-sm"
                :disabled="!factorForm.label.trim() || factorSaving"
                @click="saveFactor">
          <span v-if="factorSaving" class="spinner-border spinner-border-sm me-1"></span>
          <i v-else class="ti ti-check me-1"></i>
          {{ factorOf(factorCtx?.activity.id) ? 'Mettre à jour' : 'Définir' }}
        </button>
      </div>
    </BModal>

    <!-- ══ MODAL CRÉER RISQUE ══ -->
    <BModal v-model="showCreateModal"
            :title="createCtx ? 'Nouveau risque — ' + createCtx.activity.name : 'Nouveau risque'"
            size="lg" hide-footer @hidden="resetCreate">
      <div v-if="createCtx">

        <!-- Contexte -->
        <div class="rr-ctx-box mb-3">
          <span class="rr-proc-code me-1">{{ createCtx.process.code }}</span>
          {{ createCtx.process.name }}
          <i class="ti ti-chevron-right mx-1 opacity-40"></i>
          <span class="rr-act-code me-1">{{ createCtx.activity.code }}</span>
          {{ createCtx.activity.name }}
          <span v-if="createCtx.factor" class="ms-2 rr-factor-tag-sm">
            {{ createCtx.factor.label }}
          </span>
        </div>

        <!-- Tabs mode saisie -->
        <div class="rr-tabs mb-3">
          <button :class="['rr-tab', { 'rr-tab--on': createMode === 'free' }]"
                  @click="createMode = 'free'">
            <i class="ti ti-pencil me-1"></i>Saisie libre
          </button>
          <button :class="['rr-tab', { 'rr-tab--on': createMode === 'ia' }]"
                  @click="switchToIa">
            <i class="ti ti-sparkles me-1"></i>Suggestions IA
            <span v-if="iaRiskLoading[createCtx.factor?.id]"
                  class="spinner-border spinner-border-sm ms-1"></span>
          </button>
          <button :class="['rr-tab', { 'rr-tab--on': createMode === 'incident' }]"
                  @click="createMode = 'incident'">
            <i class="ti ti-alert-triangle me-1"></i>Depuis incident
          </button>
        </div>

        <!-- Saisie libre -->
        <div v-if="createMode === 'free'" class="row g-3">
          <div class="col-12">
            <label class="form-label fw-semibold">
              Libellé du risque <span class="text-danger">*</span>
            </label>
            <input v-model="createForm.libelle" type="text" class="form-control"
                   :class="{ 'is-invalid': createErrors.libelle }"
                   placeholder="Décrivez le risque…" maxlength="255"/>
            <div v-if="createErrors.libelle" class="invalid-feedback">
              {{ createErrors.libelle }}
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Nomenclature</label>
            <select v-model="createForm.nomenclature_id"
                    class="form-select form-select-sm">
              <option :value="null">— Sélectionner —</option>
              <option v-for="n in props.nomenclatures" :key="n.id" :value="n.id">
                {{ n.label }}
              </option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Responsable</label>
            <input v-model="createForm.owner" type="text"
                   class="form-control form-control-sm"
                   :placeholder="props.currentUser?.name ?? 'Nom…'"/>
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">Causes</label>
            <textarea v-model="createForm.causes"
                      class="form-control form-control-sm" rows="2"
                      placeholder="Causes identifiées…"></textarea>
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">Conséquences</label>
            <textarea v-model="createForm.consequences"
                      class="form-control form-control-sm" rows="2"
                      placeholder="Impacts potentiels…"></textarea>
          </div>
        </div>

        <!-- IA -->
        <div v-else-if="createMode === 'ia'">
          <div v-if="iaRiskLoading[createCtx.factor?.id]"
               class="text-center py-4 text-muted">
            <span class="spinner-border me-2"></span>L'IA génère des suggestions…
          </div>
          <div v-else-if="!iaSuggestions.length" class="text-center py-4 text-muted">
            <i class="ti ti-sparkles d-block fs-2 mb-2 opacity-30"></i>
            <button class="btn btn-sm rr-btn-ia" @click="loadIaRisks">
              <i class="ti ti-sparkles me-1"></i>Générer des risques
            </button>
          </div>
          <div v-else>
            <div class="rr-ia-box mb-3">
              <div class="rr-ia-box-title">
                <i class="ti ti-sparkles me-1"></i>Sélectionnez un risque
                <button class="btn btn-xs rr-btn-ia ms-2" @click="loadIaRisks">
                  <i class="ti ti-refresh me-1"></i>Actualiser
                </button>
              </div>
              <div class="d-flex flex-wrap gap-2 mt-2">
                <div v-for="(s, si) in iaSuggestions" :key="si"
                     :class="['rr-ia-chip', { 'rr-ia-chip--on': createForm.libelle === s }]"
                     @click="createForm.libelle = s">
                  {{ s }}
                </div>
              </div>
            </div>
            <div v-if="createForm.libelle">
              <label class="form-label fw-semibold">Libellé sélectionné (modifiable)</label>
              <input v-model="createForm.libelle" type="text" class="form-control"/>
            </div>
          </div>
        </div>

        <!-- Depuis incident -->
        <div v-else-if="createMode === 'incident'">
          <p class="text-muted small mb-3">
            Sélectionnez un incident en bibliothèque — il sera enregistré comme risque.
          </p>
          <div v-if="!props.libraryIncidents.length"
               class="text-center text-muted py-3">
            <i class="ti ti-books d-block fs-2 opacity-25 mb-1"></i>
            Aucun incident en bibliothèque
          </div>
          <div v-for="inc in props.libraryIncidents" :key="inc.id"
               :class="['rr-inc-opt', { 'rr-inc-opt--on': selectedIncident?.id === inc.id }]"
               @click="pickIncident(inc)">
            <div class="d-flex align-items-center gap-2">
              <span class="rr-inc-code">{{ inc.code_incident }}</span>
              <span class="small fw-semibold">{{ inc.libelle }}</span>
            </div>
            <small v-if="inc.description" class="text-muted d-block mt-1 ms-4">
              {{ inc.description }}
            </small>
          </div>
          <div v-if="selectedIncident" class="mt-3">
            <label class="form-label fw-semibold">Libellé (modifiable)</label>
            <input v-model="createForm.libelle" type="text" class="form-control"/>
          </div>
        </div>

      </div>
      <div class="d-flex justify-content-end gap-2 mt-3 pt-3 border-top">
        <button class="btn btn-outline-secondary btn-sm"
                @click="showCreateModal = false">Annuler</button>
        <button class="btn btn-primary btn-sm"
                :disabled="!createForm.libelle.trim() || createSaving"
                @click="submitCreate">
          <span v-if="createSaving" class="spinner-border spinner-border-sm me-1"></span>
          <i v-else class="ti ti-check me-1"></i>Créer le risque
        </button>
      </div>
    </BModal>

    <!-- ══ MODAL BIBLIO ══ -->
    <BModal v-model="showLibModal" title="Transférer en bibliothèque"
            size="sm" hide-footer>
      <p class="mb-3">
        Transférer <strong>{{ libTarget?.code_risk }}</strong> en bibliothèque ?
        <small class="d-block text-muted mt-1">
          Il restera visible ici. Complétez son analyse dans la bibliothèque.
        </small>
      </p>
      <div class="d-flex justify-content-end gap-2">
        <button class="btn btn-outline-secondary btn-sm"
                @click="showLibModal = false">Annuler</button>
        <button class="btn btn-info btn-sm text-white"
                :disabled="libSaving" @click="doLibrary">
          <span v-if="libSaving" class="spinner-border spinner-border-sm me-1"></span>
          <i v-else class="ti ti-books me-1"></i>Transférer
        </button>
      </div>
    </BModal>

    <!-- ══ MODAL SUPPRESSION ══ -->
    <BModal v-model="showDeleteModal" title="Supprimer le risque"
            size="sm" hide-footer>
      <p class="mb-3">Supprimer <strong>{{ deleteTarget?.code_risk }}</strong> ?</p>
      <div class="d-flex justify-content-end gap-2">
        <button class="btn btn-outline-secondary btn-sm"
                @click="showDeleteModal = false">Annuler</button>
        <button class="btn btn-danger btn-sm"
                :disabled="deleteSaving" @click="doDelete">
          <span v-if="deleteSaving" class="spinner-border spinner-border-sm me-1"></span>
          <i v-else class="ti ti-trash me-1"></i>Supprimer
        </button>
      </div>
    </BModal>

  </VerticalLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { BModal, BOffcanvas } from 'bootstrap-vue-next'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

// ── Props ─────────────────────────────────────────────────────────────────────
const props = defineProps({
  risks:            { type: Array,   default: () => [] },
  stats:            { type: Object,  default: () => ({}) },
  entities:         { type: Array,   default: () => [] },
  nomenclatures:    { type: Array,   default: () => [] },
  libraryIncidents: { type: Array,   default: () => [] },
  userActivityIds:  { type: Array,   default: () => [] },
  isRiskAdmin:      { type: Boolean, default: false },
  currentUser:      { type: Object,  default: () => ({}) },
})

// ── Arbre ─────────────────────────────────────────────────────────────────────
const selectedEntityId = ref(null)
const tree             = ref([])
const treeLoading      = ref(false)
// factorsMap : { activityId(number) → factorObj | undefined }
const factorsMap       = ref({})

// ── IA ────────────────────────────────────────────────────────────────────────
const iaFactorLoading = ref({})   // { actId: bool }
const iaFactorSugg    = ref({})   // { actId: string[] }
const iaFactorDesc    = ref({})   // { actId: string }
const iaRiskLoading   = ref({})   // { factorId: bool }
const iaSuggestions   = ref([])

// ── Modals ────────────────────────────────────────────────────────────────────
const showDetail   = ref(false)
const detailRisk   = ref(null)

const showFactorModal = ref(false)
const factorCtx       = ref(null)
const factorSaving    = ref(false)
const factorForm      = ref({ label: '', description: '' })

const showCreateModal  = ref(false)
const createCtx        = ref(null)
const createMode       = ref('free')   // 'free' | 'ia' | 'incident'
const createSaving     = ref(false)
const createErrors     = ref({})
const selectedIncident = ref(null)
const createForm       = ref(emptyCreate())

const showLibModal = ref(false)
const libTarget    = ref(null)
const libSaving    = ref(false)

const showDeleteModal = ref(false)
const deleteTarget    = ref(null)
const deleteSaving    = ref(false)

// ── Helpers ───────────────────────────────────────────────────────────────────
function emptyCreate() {
  return {
    libelle: '', nomenclature_id: null, owner: '',
    causes: '', consequences: '', controles_existants: '',
  }
}

// Permission
const canEdit = actId =>
  props.isRiskAdmin || props.userActivityIds.includes(Number(actId))

// Facteur d'une activité (ou null)
const factorOf = actId => factorsMap.value[Number(actId)] ?? null

// Risques d'une activité :
// - ceux qui ont factor_id === facteur de l'activité
// - OU ceux liés à l'activité sans factor_id (créés avant facteur)
function risksOf(actId) {
  const id  = Number(actId)
  const fac = factorOf(id)
  if (!fac) return []
  return props.risks.filter(r =>
    r.factor_id === fac.id ||
    (r.factor_id == null && Number(r.activity_id) === id)
  )
}

// Indicateur analyse incomplète
const isMissingAnalysis = r =>
  !r.causes || !r.consequences || !r.entite_partenaire_impliquee

// ── Rowspans ──────────────────────────────────────────────────────────────────
// Activité sans facteur = 1 ligne
// Activité avec facteur = 1 (facteur) + N risques + 1 (bouton+)
function activityRows(activity) {
  const fac = factorOf(activity.id)
  if (!fac) return 1
  return 1 + risksOf(activity.id).length + 1
}

function processRows(process) {
  return process.activities.reduce((s, a) => s + activityRows(a), 0)
}

function macroRows(macro) {
  return macro.processes.reduce((s, p) => s + processRows(p), 0)
}

// ── Visuels macro ─────────────────────────────────────────────────────────────
const macroColor = kind => ({
  Direction:   '#9333ea',
  Réalisation: '#16a34a',
  Support:     '#2563eb',
})[kind] ?? '#64748b'

const macroLabel = kind => ({
  Direction:   'DIR',
  Réalisation: 'OP',
  Support:     'SUP',
})[kind] ?? (kind ?? '?')

// ── Stats ─────────────────────────────────────────────────────────────────────
const statCards = computed(() => [
  { label: 'Total',      value: props.stats.total          ?? 0, icon: 'ti-shield',       color: 'primary' },
  { label: 'Actifs',     value: props.stats.total_actif    ?? 0, icon: 'ti-shield-check', color: 'success' },
  { label: 'Brouillons', value: props.stats.total_draft    ?? 0, icon: 'ti-shield-half',  color: 'warning' },
  { label: 'Incidents',  value: props.stats.from_incidents ?? 0, icon: 'ti-link',         color: 'info'    },
])

const detailFields = [
  { key: 'causes',              label: 'Causes',            icon: 'ti-bulb'          },
  { key: 'consequences',        label: 'Conséquences',       icon: 'ti-alert-triangle' },
  { key: 'controles_existants', label: 'Contrôles',         icon: 'ti-shield'        },
  { key: 'plan_traitement',     label: 'Plan traitement',    icon: 'ti-list-check'    },
]

// ── Chargement arbre + facteurs ───────────────────────────────────────────────
async function selectEntity(entityId) {
  selectedEntityId.value = entityId
  treeLoading.value      = true
  tree.value             = []
  factorsMap.value       = {}
  try {
    const r    = await fetch(route('risk.core.risks.entity-tree', entityId))
    tree.value = await r.json()
    await loadFactors()
  } catch (e) {
    console.error('entityTree', e)
  } finally {
    treeLoading.value = false
  }
}

async function loadFactors() {
  const actIds = []
  for (const m of tree.value)
    for (const p of m.processes)
      for (const a of p.activities)
        actIds.push(a.id)

  if (!actIds.length) return

  const csrf = csrf_token()
  const res  = await fetch(route('risk.core.risks.factors-for-activities'), {
    method:  'POST',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
    body:    JSON.stringify({ activity_ids: actIds }),
  })
  // Réponse : { "7": { id, activity_id, label, … }, "8": { … }, … }
  // Clé = activity_id (string) — on convertit en number
  const raw     = await res.json()
  const numeric = {}
  for (const [k, v] of Object.entries(raw)) numeric[Number(k)] = v
  factorsMap.value = numeric
}

function csrf_token() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? ''
}

// ── IA facteurs ───────────────────────────────────────────────────────────────
async function suggestFactors(activity, process, macro) {
  iaFactorLoading.value = { ...iaFactorLoading.value, [activity.id]: true }
  try {
    const res  = await fetch(route('risk.core.risks.mistral.suggest'), {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                 'X-CSRF-TOKEN': csrf_token() },
      body: JSON.stringify({
        mode: 'factors',
        activity_id:   activity.id,
        activity_name: activity.name,
        process_name:  process.name,
        macro_name:    macro.name,
        macro_kind:    macro.kind,
      }),
    })
    const d = await res.json()
    iaFactorSugg.value = { ...iaFactorSugg.value, [activity.id]: d.factors  ?? [] }
    iaFactorDesc.value = { ...iaFactorDesc.value, [activity.id]: d.description ?? '' }
  } catch (e) { console.error(e) }
  finally { iaFactorLoading.value = { ...iaFactorLoading.value, [activity.id]: false } }
}

async function useIaFactor(activity, label) {
  await doSaveFactor(activity.id, label, iaFactorDesc.value[activity.id] ?? '', true)
}

// ── Modal facteur ─────────────────────────────────────────────────────────────
function openFactorModal(activity, process, macro) {
  factorCtx.value  = { activity, process, macro }
  const ex         = factorOf(activity.id)
  factorForm.value = { label: ex?.label ?? '', description: ex?.description ?? '' }
  showFactorModal.value = true
}
function resetFactor() {
  factorCtx.value  = null
  factorForm.value = { label: '', description: '' }
}
async function saveFactor() {
  if (!factorForm.value.label.trim()) return
  factorSaving.value = true
  await doSaveFactor(
    factorCtx.value.activity.id,
    factorForm.value.label,
    factorForm.value.description,
    false,
  )
  factorSaving.value    = false
  showFactorModal.value = false
}

async function doSaveFactor(activityId, label, description, isIa) {
  const res  = await fetch(route('risk.core.risks.factors.store'), {
    method:  'POST',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
               'X-CSRF-TOKEN': csrf_token() },
    body: JSON.stringify({ activity_id: activityId, label, description, is_ia: isIa ? 1 : 0 }),
  })
  const d = await res.json()
  if (d.factor) {
    // Stocker sous clé number
    factorsMap.value = { ...factorsMap.value, [Number(activityId)]: d.factor }
    if (isIa) {
      iaFactorSugg.value = {
        ...iaFactorSugg.value,
        [activityId]: (iaFactorSugg.value[activityId] ?? []).filter(f => f !== label),
      }
    }
  }
}

// ── IA risques ────────────────────────────────────────────────────────────────
async function triggerRiskIA(activity, process, macro) {
  openCreateModal(activity, process, macro)
  createMode.value = 'ia'
  await loadIaRisks()
}

async function switchToIa() {
  createMode.value = 'ia'
  if (!iaSuggestions.value.length) await loadIaRisks()
}

async function loadIaRisks() {
  if (!createCtx.value?.factor) return
  const { factor, activity, process, macro } = createCtx.value
  iaRiskLoading.value = { ...iaRiskLoading.value, [factor.id]: true }
  try {
    const res  = await fetch(route('risk.core.risks.mistral.suggest'), {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                 'X-CSRF-TOKEN': csrf_token() },
      body: JSON.stringify({
        mode:               'risks',
        activity_id:        activity.id,
        activity_name:      activity.name,
        process_name:       process.name,
        macro_name:         macro.name,
        macro_kind:         macro.kind,
        factor_id:          factor.id,
        factor_label:       factor.label,
        factor_description: factor.description ?? '',
      }),
    })
    const d = await res.json()
    iaSuggestions.value = d.risks ?? []
  } catch (e) { console.error(e) }
  finally { iaRiskLoading.value = { ...iaRiskLoading.value, [factor.id]: false } }
}

// ── Modal créer risque ────────────────────────────────────────────────────────
function openCreateModal(activity, process, macro) {
  createCtx.value       = { activity, process, macro, factor: factorOf(activity.id) }
  createForm.value      = emptyCreate()
  createErrors.value    = {}
  createMode.value      = 'free'
  iaSuggestions.value   = []
  selectedIncident.value = null
  showCreateModal.value = true
}

function openIncidentModal(activity, process, macro) {
  openCreateModal(activity, process, macro)
  createMode.value = 'incident'
}

function resetCreate() {
  createCtx.value        = null
  createForm.value       = emptyCreate()
  iaSuggestions.value    = []
  selectedIncident.value = null
  createMode.value       = 'free'
}

function pickIncident(inc) {
  selectedIncident.value   = inc
  createForm.value.libelle = inc.libelle
}

function submitCreate() {
  if (!createForm.value.libelle.trim()) {
    createErrors.value = { libelle: 'Le libellé est obligatoire.' }
    return
  }
  createSaving.value = true
  const ctx = createCtx.value
  router.post(route('risk.core.risks.store'), {
    ...createForm.value,
    entity_id:   selectedEntityId.value,
    activity_id: ctx.activity.id,
    factor_id:   ctx.factor?.id ?? null,
    incident_id: createMode.value === 'incident' ? (selectedIncident.value?.id ?? null) : null,
    statut:      'draft',
  }, {
    preserveScroll: true,
    onSuccess: () => { showCreateModal.value = false },
    onError:   e  => { createErrors.value = e },
    onFinish:  () => { createSaving.value = false },
  })
}

// ── Bibliothèque ──────────────────────────────────────────────────────────────
function confirmLibrary(risk) {
  libTarget.value   = risk
  showLibModal.value = true
}
function doLibrary() {
  libSaving.value = true
  router.post(route('risk.core.risks.move-to-library', libTarget.value.id), {}, {
    preserveScroll: true,
    onSuccess: () => { showLibModal.value = false },
    onFinish:  () => { libSaving.value = false },
  })
}

// ── Détail ────────────────────────────────────────────────────────────────────
function openDetail(risk) {
  detailRisk.value = risk
  showDetail.value = true
}

// ── Suppression ───────────────────────────────────────────────────────────────
function confirmDelete(risk) {
  deleteTarget.value   = risk
  showDeleteModal.value = true
}
function doDelete() {
  deleteSaving.value = true
  router.delete(route('risk.core.risks.destroy', deleteTarget.value.id), {
    preserveScroll: true,
    onSuccess: () => { showDeleteModal.value = false },
    onFinish:  () => { deleteSaving.value = false },
  })
}
</script>

<style scoped>
.rr-page { padding:18px; }

/* HEADER */
.rr-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:16px; }
.rr-header-icon { width:40px; height:40px; border-radius:9px; flex-shrink:0; background:linear-gradient(135deg,#1e293b,#1e3a5f); display:flex; align-items:center; justify-content:center; color:#93c5fd; font-size:18px; }
.rr-user-badge { font-size:.73rem; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:20px; padding:3px 10px; color:#475569; }
.rr-admin-tag  { font-size:.6rem; font-weight:700; background:#fef3c7; color:#92400e; padding:1px 6px; border-radius:10px; }

/* STATS */
.rr-stat { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:8px; border:1px solid transparent; }
.rr-stat i { font-size:1.2rem; }
.rr-stat-val { font-size:1.15rem; font-weight:800; line-height:1; }
.rr-stat-lbl { font-size:.63rem; color:#64748b; }
.rr-stat--primary { background:#eff6ff; border-color:#bfdbfe; } .rr-stat--primary i { color:#2563eb; }
.rr-stat--success { background:#f0fdf4; border-color:#bbf7d0; } .rr-stat--success i { color:#16a34a; }
.rr-stat--warning { background:#fffbeb; border-color:#fde68a; } .rr-stat--warning i { color:#d97706; }
.rr-stat--info    { background:#f0f9ff; border-color:#bae6fd; } .rr-stat--info i  { color:#0284c7; }

/* ENTITÉ */
.rr-entity-bar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.rr-entity-lbl { font-size:.76rem; font-weight:600; color:#475569; white-space:nowrap; }
.rr-empty      { text-align:center; padding:48px; color:#94a3b8; }

/* TABLEAU */
.rr-table-wrap { overflow-x:auto; border-radius:10px; border:2px solid #e2e8f0; }
.rr-table      { border-collapse:collapse; width:100%; font-size:.77rem; }
.rr-th {
  background:#1e293b; color:#f1f5f9;
  padding:7px 9px; text-align:left; font-size:.67rem; font-weight:700;
  text-transform:uppercase; letter-spacing:.04em;
  border-right:1px solid #334155; white-space:nowrap;
}
.rr-td { padding:6px 9px; border-bottom:1px solid #f1f5f9; border-right:1px solid #f1f5f9; vertical-align:middle; }

/* Cellules structurelles */
.rr-td--macro      { background:#1e293b; color:#fff; text-align:center; border-right:3px solid #0f172a; }
.rr-td--n          { background:#f8fafc; text-align:center; font-weight:700; color:#475569; }
.rr-td--proc       { background:#fef9c3; vertical-align:top; }
.rr-td--act        { background:#f0fdf4; vertical-align:top; border-right:2px solid #bbf7d0; }
.rr-td--factor     { background:#ecfdf5; }
.rr-td--factorzone { background:#fdf4ff; }
.rr-td--rseq       { background:#f8fafc; width:26px; padding:3px; }

/* Lignes */
.rr-row-risk   { background:#fff; cursor:pointer; transition:background .1s; }
.rr-row-risk:hover { background:#f0f9ff; }
.rr-row-add    { background:#fafafa; }

/* GRISAGE : seulement les colonnes "contenu" des lignes hors périmètre */
.rr-dimmed .rr-td--act,
.rr-dimmed .rr-td--factor,
.rr-dimmed .rr-td--factorzone,
.rr-dimmed .rr-td--rseq,
.rr-dimmed td:not(.rr-td--macro):not(.rr-td--n):not(.rr-td--proc) {
  opacity:.42;
}
.rr-dimmed { pointer-events:none; }

/* Badges inline */
.rr-macro-dot   { display:inline-flex; align-items:center; justify-content:center; color:#fff; font-size:.58rem; font-weight:800; padding:3px 6px; border-radius:5px; text-transform:uppercase; letter-spacing:.05em; min-width:32px; }
.rr-proc-code   { font-family:monospace; font-size:.6rem; background:#e2e8f0; color:#475569; padding:0 4px; border-radius:3px; display:inline-block; }
.rr-act-code    { font-family:monospace; font-size:.6rem; background:#dcfce7; color:#166534; padding:0 4px; border-radius:3px; display:inline-block; }
.rr-risk-code   { font-family:monospace; font-size:.65rem; font-weight:700; background:#e0e7ff; color:#4338ca; padding:1px 5px; border-radius:4px; white-space:nowrap; }
.rr-inc-code    { font-family:monospace; font-size:.62rem; font-weight:700; background:#fef3c7; color:#92400e; padding:1px 5px; border-radius:4px; white-space:nowrap; }
.rr-rseq        { display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; border-radius:50%; background:#e0e7ff; color:#4338ca; font-size:.63rem; font-weight:700; }
.rr-factor-badge { font-size:.6rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; background:#bbf7d0; color:#15803d; padding:2px 8px; border-radius:4px; white-space:nowrap; }
.rr-factor-name  { font-size:.74rem; font-weight:600; background:#dcfce7; color:#15803d; border:1px solid #86efac; padding:2px 10px; border-radius:12px; }
.rr-factor-tag-sm { font-size:.67rem; font-weight:600; background:#dcfce7; color:#15803d; border:1px solid #86efac; padding:1px 7px; border-radius:10px; }
.rr-factor-desc   { font-size:.63rem; color:#64748b; line-height:1.4; }
.rr-zone          { font-size:.64rem; font-weight:600; padding:2px 7px; border-radius:12px; border:1px solid; white-space:nowrap; display:inline-block; }
.rr-locked        { font-size:.6rem; color:#94a3b8; }
.rr-missing-hint  { font-size:.6rem; color:#d97706; }
.rr-ia-label      { font-size:.6rem; font-weight:700; background:#ede9fe; color:#7c3aed; padding:1px 6px; border-radius:10px; white-space:nowrap; }
.rr-lib-hint      { font-family:monospace; font-size:.64rem; background:#e0f2fe; color:#0284c7; padding:0 5px; border-radius:3px; }
.rr-lib-badge     { font-size:.6rem; background:#e0f2fe; color:#0284c7; border:1px solid #bae6fd; padding:1px 6px; border-radius:10px; white-space:nowrap; display:inline-block; }

/* Boutons */
.btn-xs { padding:2px 8px; font-size:.67rem; border-radius:5px; border:1px solid transparent; cursor:pointer; }
.rr-btn-add          { background:#e0e7ff; color:#4338ca; border-color:#c7d2fe; font-weight:600; }
.rr-btn-add:hover    { background:#c7d2fe; }
.rr-btn-incident     { background:#fef3c7; color:#92400e; border-color:#fde68a; }
.rr-btn-incident:hover { background:#fde68a; }
.rr-btn-ia           { background:#f3e8ff; color:#7c3aed; border-color:#ddd6fe; }
.rr-btn-ia:hover     { background:#ddd6fe; }
.rr-btn-ia:disabled  { opacity:.5; cursor:not-allowed; }
.rr-btn-edit-factor  { background:#fff; color:#475569; border-color:#e2e8f0; font-size:.63rem; }
.rr-btn-edit-factor:hover { background:#f1f5f9; }
.rr-btn-lib          { background:#e0f2fe; color:#0284c7; border:1px solid #bae6fd; font-size:.62rem; padding:2px 6px; border-radius:5px; cursor:pointer; white-space:nowrap; }
.rr-btn-lib:hover    { background:#bae6fd; }

/* Mode tabs modal */
.rr-tabs { display:flex; gap:4px; background:#f1f5f9; border-radius:8px; padding:3px; }
.rr-tab  { flex:1; padding:5px; border-radius:6px; border:none; background:transparent; cursor:pointer; font-size:.7rem; color:#64748b; font-weight:500; transition:all .12s; }
.rr-tab:hover { background:#e2e8f0; }
.rr-tab--on   { background:#fff; color:#1e293b; font-weight:700; box-shadow:0 1px 4px rgba(0,0,0,.08); }

/* IA */
.rr-ia-box       { background:#f3e8ff; border:1px solid #ddd6fe; border-radius:8px; padding:10px 14px; }
.rr-ia-box-title { font-size:.72rem; font-weight:700; color:#7c3aed; }
.rr-ia-chip      { font-size:.72rem; padding:4px 10px; border-radius:20px; background:#ede9fe; color:#5b21b6; border:1px solid #c4b5fd; cursor:pointer; transition:all .1s; }
.rr-ia-chip:hover { background:#ddd6fe; }
.rr-ia-chip--on  { background:#7c3aed; color:#fff; border-color:#7c3aed; }

/* Contexte box */
.rr-ctx-box { font-size:.75rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:6px 10px; color:#475569; }

/* Incidents */
.rr-inc-opt      { padding:8px 12px; border-radius:6px; border:1px solid #e2e8f0; margin-bottom:6px; cursor:pointer; background:#fff; transition:all .1s; }
.rr-inc-opt:hover { background:#f0f9ff; border-color:#bae6fd; }
.rr-inc-opt--on  { background:#eff6ff; border-color:#3b82f6; }

/* Form */
.form-control-sm, .form-select-sm { font-size:.74rem; height:28px; padding:.18rem .45rem; }
textarea.form-control-sm { height:auto; }
.btn-sm { font-size:.72rem; padding:.15rem .5rem; }
</style>