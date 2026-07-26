<template>
  <VerticalLayout>
    <Head title="Affectation des phases" />

    <div class="pa-app">

      <!-- ══════════════════════════════════════════════════════════
           EN-TÊTE — étapes + résumé mission + actions
      ══════════════════════════════════════════════════════════ -->
      <header class="pa-header">
        <ol class="pa-steps">
          <li v-for="(lbl, i) in steps" :key="i">
            <button
              type="button"
              class="pa-step"
              :class="{ 'is-done': step > i + 1, 'is-active': step === i + 1 }"
              :disabled="i + 1 > step && !(i + 1 === 2 && selectedMission) "
              @click="tryGoStep(i + 1)"
            >
              <span class="pa-step-mark">
                <i v-if="step > i + 1" class="ti ti-check"></i>
                <span v-else>{{ i + 1 }}</span>
              </span>
              <span class="pa-step-label">{{ lbl }}</span>
            </button>
            <span v-if="i < steps.length - 1" class="pa-step-sep"></span>
          </li>
        </ol>

        <div v-if="step >= 2 && localMission?.id" class="pa-mission-chip">
          <span class="pa-dot" :style="{ background: localMission.audit_color ?? '#64748B' }"></span>
          <code class="pa-mission-code">{{ localMission.code_mission }}</code>
          <span class="pa-mission-title">{{ localMission.libelle }}</span>
          <span v-if="localMission.audit_type_label" class="pa-badge pa-badge--neutral">{{ localMission.audit_type_label }}</span>
          <span v-if="step === 3" class="pa-mission-meta"><i class="ti ti-list-check"></i>{{ checkedPhaseIds.size }} phase(s)</span>
        </div>

        <div class="pa-header-actions">
          <span v-if="loadingData" class="pa-loading-tag"><span class="pa-spinner"></span>Chargement…</span>

          <button v-if="step >= 2" type="button" class="pa-btn pa-btn--icon" title="Étape précédente" @click="step = Math.max(1, step - 1)">
            <i class="ti ti-arrow-left"></i>
          </button>

          <button
            v-if="step === 2 && localMission?.mission_type_id"
            type="button"
            class="pa-btn pa-btn--ddm"
            :disabled="syncing"
            :title="`Resynchroniser les libellés depuis ddmparam [${localMission.audit_type_code ?? '…'}]`"
            @click="syncPhasesFromDdm"
          >
            <span v-if="syncing" class="pa-spinner pa-spinner--light"></span>
            <i v-else class="ti ti-database-cog"></i>
            {{ syncing ? 'Synchronisation…' : 'Resynchroniser' }}
          </button>

          <button v-if="step === 2" type="button" class="pa-btn pa-btn--primary" :disabled="!checkedPhaseIds.size" @click="goStep3">
            Configurer l'affectation <i class="ti ti-arrow-right"></i>
          </button>

          <template v-if="step === 3">
            <span v-if="dirty.size" class="pa-badge pa-badge--warning">
              <span class="pa-badge-dot"></span>{{ dirty.size }} modification(s) non enregistrée(s)
            </span>
            <button type="button" class="pa-btn pa-btn--success" :disabled="saving || !dirty.size" @click="saveAll">
              <span v-if="saving" class="pa-spinner pa-spinner--light"></span>
              <i v-else class="ti ti-device-floppy"></i>
              {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
            </button>
          </template>
        </div>
      </header>

      <!-- ── Résultat de la synchro ddmparam ── -->
      <Transition name="pa-fade">
        <div v-if="showSyncResult && syncResult" class="pa-sync-panel" :class="syncResult.success ? 'is-ok' : 'is-error'">
          <div class="pa-sync-head">
            <i :class="'ti ' + (syncResult.success ? 'ti-circle-check' : 'ti-circle-x')"></i>
            <span class="pa-sync-msg">{{ syncResult.message || syncResult.error }}</span>
            <div v-if="syncResult.success" class="pa-sync-stats">
              <span v-if="syncResult.updated" class="pa-chip pa-chip--info"><i class="ti ti-refresh"></i>{{ syncResult.updated }} mis à jour</span>
              <span v-if="syncResult.created" class="pa-chip pa-chip--success"><i class="ti ti-plus"></i>{{ syncResult.created }} créé(s)</span>
              <span v-if="syncResult.skipped" class="pa-chip pa-chip--neutral"><i class="ti ti-minus"></i>{{ syncResult.skipped }} inchangé(s)</span>
            </div>
            <button type="button" class="pa-icon-btn" @click="showSyncResult = false"><i class="ti ti-x"></i></button>
          </div>
          <ul v-if="syncResult.changes?.length" class="pa-sync-list">
            <li v-for="ch in syncResult.changes" :key="ch.code">
              <code class="pa-sync-code">{{ ch.code }}</code>
              <span class="pa-chip" :class="ch.action === 'created' ? 'pa-chip--success' : 'pa-chip--info'">
                {{ ch.action === 'created' ? 'créé' : 'mis à jour' }}
              </span>
              <span v-if="ch.action === 'created'" class="pa-sync-label">{{ ch.label }}</span>
              <template v-if="ch.fields?.length">
                <span v-for="f in ch.fields" :key="f.field" class="pa-sync-diff">
                  <em>{{ f.field }}</em>
                  <s>{{ f.old || '—' }}</s>
                  <i class="ti ti-arrow-right"></i>
                  <strong>{{ f.new }}</strong>
                </span>
              </template>
            </li>
          </ul>
        </div>
      </Transition>

      <!-- ══════════════════════════════════════════════════════════
           ÉTAPE 1 — Choisir une mission
      ══════════════════════════════════════════════════════════ -->
      <section v-if="step === 1" class="pa-body">
        <div class="pa-toolbar">
          <h2 class="pa-toolbar-title"><i class="ti ti-clipboard-list"></i>Missions <span class="pa-toolbar-count">{{ filteredMissions.length }}</span></h2>
          <div class="pa-search">
            <i class="ti ti-search"></i>
            <input v-model="search" type="text" placeholder="Rechercher par code, libellé, entité…" />
          </div>
          <div class="pa-filter-group">
            <button v-for="s in statusFilters" :key="s.v" type="button" class="pa-filter" :class="{ 'is-active': fStatus === s.v }" @click="fStatus = s.v">{{ s.l }}</button>
          </div>
        </div>

        <div class="pa-table-scroll">
          <table class="pa-table">
            <thead>
              <tr>
                <th>Code</th><th>Libellé</th><th>Type</th><th>Période</th><th>Entités</th><th>Avancement</th><th>Statut</th><th class="pa-th-action"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in filteredMissions" :key="m.id" class="pa-row" :class="{ 'is-selected': selectedMission?.id === m.id }" @click="pickMission(m)">
                <td><code class="pa-code-tag">{{ m.code_mission }}</code></td>
                <td class="pa-ellipsis pa-w-260">{{ m.libelle }}</td>
                <td>
                  <span v-if="m.type_label || m.audit_type_label" class="pa-badge pa-badge--neutral">{{ m.type_label || m.audit_type_label }}</span>
                  <span v-else class="pa-muted">—</span>
                </td>
                <td class="pa-mono pa-muted pa-small">{{ fmt(m.date_debut) }} → {{ fmt(m.date_fin) }}</td>
                <td class="pa-ellipsis pa-w-180 pa-small pa-muted">{{ m.entities_list || '—' }}</td>
                <td>
                  <div v-if="m.total_aff > 0" class="pa-progress" :title="`${m.completed_aff}/${m.total_aff}`">
                    <div class="pa-progress-track"><div class="pa-progress-fill" :style="{ width: m.pct_aff + '%' }"></div></div>
                    <span class="pa-progress-pct">{{ m.pct_aff }}%</span>
                  </div>
                  <span v-else class="pa-muted pa-small">—</span>
                </td>
                <td><span class="pa-badge" :class="stChip(m.status)">{{ stLbl(m.status) }}</span></td>
                <td class="pa-th-action">
                  <a :href="pageUrl(m.id)" class="pa-row-link" title="Ouvrir cette mission" @click.stop><i class="ti ti-arrow-right"></i></a>
                </td>
              </tr>
              <tr v-if="!filteredMissions.length">
                <td colspan="8" class="pa-empty-row"><i class="ti ti-search-off"></i>Aucune mission ne correspond à votre recherche</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="selectedMission" class="pa-confirm-bar">
          <span class="pa-confirm-info"><i class="ti ti-circle-check pa-text-success"></i><strong>{{ selectedMission.code_mission }}</strong>&nbsp;— {{ selectedMission.libelle }}</span>
          <a :href="pageUrl(selectedMission.id)" class="pa-btn pa-btn--primary">Ouvrir cette mission <i class="ti ti-arrow-right"></i></a>
        </div>
      </section>

      <!-- ══════════════════════════════════════════════════════════
           ÉTAPE 2 — Choisir les phases
      ══════════════════════════════════════════════════════════ -->
      <section v-if="step === 2" class="pa-body pa-body--split">
        <aside class="pa-sidebar">
          <div class="pa-side-block">
            <span class="pa-side-label">Mission</span>
            <code class="pa-side-code">{{ localMission.code_mission }}</code>
            <p class="pa-side-title">{{ localMission.libelle }}</p>
            <p class="pa-side-sub">{{ fmt(localMission.date_debut) }} → {{ fmt(localMission.date_fin) }}</p>
          </div>

          <div v-if="localMission.audit_type_label" class="pa-side-block">
            <span class="pa-side-label">Type d'audit</span>
            <div class="pa-audit-badge" :style="{ background: localMission.audit_color ?? '#64748B' }">
              <i :class="localMission.audit_icon ?? 'ti ti-folder'"></i>{{ localMission.audit_type_label }}
            </div>
          </div>

          <div v-if="formsList.length" class="pa-side-block">
            <span class="pa-side-label">Formulaires disponibles <span class="pa-count-pill">{{ formsList.length }}</span></span>
            <ul class="pa-forms-preview">
              <li v-for="f in formsList.slice(0, 8)" :key="f.code" :class="{ 'is-child': f.level > 1 }">
                <i :class="f.icon || 'ti ti-file'"></i>
                <code>{{ f.code }}</code>
                <span>{{ f.label }}</span>
              </li>
            </ul>
            <p v-if="formsList.length > 8" class="pa-side-more">+ {{ formsList.length - 8 }} autre(s)</p>
            <p class="pa-side-source"><i class="ti ti-database"></i>Source : {{ formsSource }}</p>
          </div>
          <div v-else-if="!loadingData" class="pa-side-block">
            <span class="pa-side-label">Formulaires</span>
            <p class="pa-hint pa-hint--warning"><i class="ti ti-alert-triangle"></i>Aucun formulaire trouvé pour ce type de mission.</p>
          </div>

          <div class="pa-side-block">
            <span class="pa-side-label">Phases sélectionnées</span>
            <div class="pa-counter"><span class="pa-counter-n">{{ checkedPhaseIds.size }}</span><span class="pa-counter-t">/ {{ totalPhases }}</span></div>
            <div class="pa-track"><div class="pa-track-fill" :style="{ width: pct + '%' }"></div></div>
          </div>

          <div class="pa-side-actions">
            <button type="button" class="pa-btn pa-btn--ghost pa-btn--sm" @click="checkAllPhases"><i class="ti ti-checks"></i>Tout cocher</button>
            <button type="button" class="pa-btn pa-btn--ghost pa-btn--sm" @click="uncheckAllPhases"><i class="ti ti-x"></i>Réinitialiser</button>
          </div>

          <button type="button" class="pa-btn pa-btn--primary pa-btn--block" :disabled="!checkedPhaseIds.size" @click="goStep3">
            Configurer l'affectation <i class="ti ti-arrow-right"></i>
          </button>
        </aside>

        <div class="pa-phase-panel">
          <div v-if="loadingData" class="pa-panel-state"><span class="pa-spinner"></span>Chargement des phases…</div>

          <div v-else-if="!localPhases.length" class="pa-panel-state pa-panel-state--empty">
            <i class="ti ti-list-search"></i>
            <p>Aucune phase disponible pour ce type de mission.</p>
            <button type="button" class="pa-btn pa-btn--ddm pa-btn--sm" :disabled="syncing" @click="syncPhasesFromDdm">
              <i class="ti ti-database-cog"></i>Importer depuis ddmparam
            </button>
          </div>

          <template v-else>
            <div class="pa-search pa-search--phases">
              <i class="ti ti-search"></i>
              <input v-model="phaseSearch" type="text" placeholder="Filtrer les phases…" />
            </div>

            <div class="pa-phase-groups">
              <!--
                CORRECTION : clé/couleur/icône basées sur phase_num (numérique,
                stable, 1..5, vient de ddmparam). Le libellé affiché
                (group.label) vient directement de
                ddmparam.audit_type_forms.phase_label côté backend — plus de
                ptLabel() figé ici.
              -->
              <div v-for="group in filteredPhaseGroups" :key="group.phase_num" class="pa-phase-group" :style="{ '--pt-color': ptColor(group.phase_num) }">
                <button type="button" class="pa-phase-group-head" @click="toggleGrp(group.phase_num)">
                  <input
                    type="checkbox" class="pa-checkbox"
                    :checked="grpAllChk(group)" :indeterminate.prop="grpPartialChk(group)"
                    @change="toggleGroupCheck(group, $event.target.checked)" @click.stop
                  />
                  <i :class="ptIcon(group.phase_num)" class="pa-phase-group-icon"></i>
                  <span class="pa-phase-group-name">{{ group.label }}</span>
                  <span class="pa-phase-group-count">{{ cntChkInGrp(group) }} / {{ cntGrp(group) }}</span>
                  <i class="ti pa-phase-group-chevron" :class="openGroups.has(group.phase_num) ? 'ti-chevron-up' : 'ti-chevron-down'"></i>
                </button>

                <div v-if="openGroups.has(group.phase_num)" class="pa-phase-group-body">
                  <template v-for="p in group.phases" :key="p.id">
                    <label class="pa-phase-row" :class="{ 'is-mandatory': p.is_mandatory, 'is-unprovisioned': !isProvisioned(p) }">
                      <input
                        type="checkbox" class="pa-checkbox"
                        :checked="checkedPhaseIds.has(p.id)" :disabled="p.is_mandatory || !isProvisioned(p)"
                        @change="togglePhaseCheck(p, $event.target.checked)"
                      />
                      <code class="pa-phase-code">{{ p.code_full || p.code }}</code>
                      <span class="pa-phase-name">{{ p.label }}</span>
                      <span v-if="p.form_code && forms[p.form_code]" class="pa-form-tag" :title="forms[p.form_code].label">
                        <i :class="forms[p.form_code].icon || 'ti ti-file'"></i>{{ p.form_code }}
                      </span>
                      <span v-if="p.children?.length" class="pa-badge pa-badge--violet">{{ p.children.length }} sous-phase(s)</span>
                      <span v-if="!isProvisioned(p)" class="pa-badge pa-badge--warning" title="Pas encore créée côté tenant — lancez une resynchronisation">Non provisionnée</span>
                      <span v-else-if="p.is_mandatory" class="pa-badge pa-badge--neutral">Obligatoire</span>
                    </label>
                    <label
                      v-for="c in (p.children || [])" :key="c.id"
                      class="pa-phase-row pa-phase-row--child"
                      :class="{ 'is-mandatory': c.is_mandatory, 'is-unprovisioned': !isProvisioned(c) }"
                    >
                      <span class="pa-connector">└</span>
                      <input
                        type="checkbox" class="pa-checkbox"
                        :checked="checkedPhaseIds.has(c.id)" :disabled="c.is_mandatory || !isProvisioned(c)"
                        @change="togglePhaseCheck(c, $event.target.checked)"
                      />
                      <code class="pa-phase-code">{{ c.code_full || c.code }}</code>
                      <span class="pa-phase-name">{{ c.label }}</span>
                      <span v-if="c.form_code && forms[c.form_code]" class="pa-form-tag" :title="forms[c.form_code].label">
                        <i :class="forms[c.form_code].icon || 'ti ti-file'"></i>{{ c.form_code }}
                      </span>
                      <span v-if="!isProvisioned(c)" class="pa-badge pa-badge--warning">Non provisionnée</span>
                      <span v-else-if="c.is_mandatory" class="pa-badge pa-badge--neutral">Obligatoire</span>
                    </label>
                  </template>
                </div>
              </div>
            </div>
          </template>
        </div>
      </section>

      <!-- ══════════════════════════════════════════════════════════
           ÉTAPE 3 — Affectation détaillée
      ══════════════════════════════════════════════════════════ -->
      <section v-if="step === 3" class="pa-body pa-body--assign">
        <nav class="pa-entity-tabs">
          <button
            v-for="e in localEntities" :key="e.id" type="button"
            class="pa-entity-tab" :class="{ 'is-active': activeEntityId === e.id, 'is-dirty': entityHasDirty(e.id) }"
            @click="selectEntity(e.id)"
          >
            <span class="pa-entity-dot" :class="entityHasDirty(e.id) ? 'is-amber' : entityProgress(e.id) > 0 ? 'is-green' : 'is-gray'"></span>
            <span class="pa-entity-name">{{ e.name }}</span>
            <span class="pa-entity-range">{{ fmt(e.date_debut) }} – {{ fmt(e.date_fin) }}</span>
            <span v-if="entityProgress(e.id) > 0" class="pa-entity-pct">{{ entityProgress(e.id) }}%</span>
          </button>
        </nav>

        <template v-if="activeEntityId">
          <div class="pa-entity-bar">
            <i class="ti ti-building pa-text-primary"></i>
            <strong class="pa-small">{{ activeEntity?.name }}</strong>
            <span class="pa-muted pa-small">{{ fmt(activeEntity?.date_debut) }} → {{ fmt(activeEntity?.date_fin) }}</span>
            <span v-if="activeEntity?.date_debut && activeEntity?.date_fin" class="pa-badge pa-badge--neutral pa-small">{{ dateDiffDays(activeEntity.date_debut, activeEntity.date_fin) }} j</span>
            <span v-if="entityDateErrors(activeEntityId).length" class="pa-badge pa-badge--danger"><i class="ti ti-alert-triangle"></i>{{ entityDateErrors(activeEntityId).length }} erreur(s)</span>
            <button v-if="warnings.length" type="button" class="pa-badge pa-badge--warning pa-badge--clickable" @click="showWarnings = !showWarnings"><i class="ti ti-info-circle"></i>{{ warnings.length }} avertissement(s)</button>

            <div class="pa-entity-bar-actions">
              <button type="button" class="pa-btn pa-btn--ghost pa-btn--sm" title="Aligner les dates en cascade" @click="cascadeEntity(activeEntityId)"><i class="ti ti-sort-ascending"></i>Cascade</button>
              <button type="button" class="pa-btn pa-btn--ghost pa-btn--sm" title="Assigner tous les auditeurs" @click="selectAllAuds(activeEntityId)"><i class="ti ti-user-check"></i>Auditeurs</button>
              <button type="button" class="pa-btn pa-btn--danger-ghost pa-btn--sm" title="Réinitialiser l'entité" @click="clearEntity(activeEntityId)"><i class="ti ti-eraser"></i></button>
            </div>
          </div>

          <div v-if="showWarnings && warnings.length" class="pa-warnings">
            <div class="pa-warnings-head">
              <i class="ti ti-info-circle"></i>Avertissements
              <button type="button" class="pa-icon-btn" @click="showWarnings = false"><i class="ti ti-x"></i></button>
            </div>
            <ul><li v-for="(w, i) in warnings" :key="i">{{ w }}</li></ul>
          </div>

          <div class="pa-assign-scroll">
            <table class="pa-assign-table">
              <thead>
                <tr>
                  <th class="pa-col-grip"></th>
                  <th class="pa-col-code pa-th-left">Code</th>
                  <th class="pa-col-label pa-th-left">Phase</th>
                  <th class="pa-col-form">Formulaire</th>
                  <th class="pa-col-toggle"><i class="ti ti-toggle-right"></i></th>
                  <th class="pa-col-status">Statut</th>
                  <th class="pa-col-date pa-th-left">Début<small>≥ début précédent</small></th>
                  <th class="pa-col-date pa-th-left">Fin<small>≥ date de début</small></th>
                  <th class="pa-col-days"><i class="ti ti-clock"></i><small>Jours</small></th>
                  <th v-for="aud in entityAuds(activeEntityId)" :key="'th' + aud.auditeur_id" class="pa-col-aud">
                    <div class="pa-aud-head" :title="aud.full_name">
                      <span class="pa-aud-avatar" :class="rCls(aud.role_code || aud.role)">{{ initials(aud.full_name) }}</span>
                      <span class="pa-aud-role" :class="rCls(aud.role_code || aud.role)">{{ aud.role_code || aud.role }}</span>
                    </div>
                  </th>
                  <th class="pa-col-note"><i class="ti ti-notes"></i></th>
                </tr>
              </thead>
              <tbody>
                <template v-for="grp in checkedByGroup" :key="'g' + grp.phase_num">
                  <tr class="pa-row-sep">
                    <td :colspan="10 + entityAuds(activeEntityId).length">
                      <i :class="ptIcon(grp.phase_num)" :style="{ color: ptColor(grp.phase_num) }"></i>
                      <b :style="{ color: ptColor(grp.phase_num) }">{{ grp.label }}</b>
                      <span class="pa-muted pa-small">— {{ grp.phases.filter(p => !p.hasSelectedChildren).length }} phase(s)</span>
                    </td>
                  </tr>
                  <template v-for="ph in grp.phases" :key="'p' + ph.id">
                    <tr v-if="ph.hasSelectedChildren" class="pa-row-locked">
                      <td><i class="ti ti-lock pa-lock-icon"></i></td>
                      <td><code class="pa-mono" :style="{ color: ptColor(grp.phase_num) }">{{ ph.code_full || ph.code }}</code></td>
                      <td :colspan="8 + entityAuds(activeEntityId).length" class="pa-locked-label">{{ ph.label }}<span class="pa-locked-hint">géré via ses sous-phases</span></td>
                    </tr>
                    <tr
                      v-else class="pa-row-phase"
                      :class="{
                        'is-active': isCfgChk(ph.id, activeEntityId),
                        'is-child': ph.level > 1,
                        'is-dragover': dragOverId === ph.id,
                        'is-dragging': draggingId === ph.id,
                        'has-warning': hasStartErr(ph.id, activeEntityId),
                      }"
                      :draggable="ph.level > 1"
                      @dragstart="onDragStart($event, ph, grp)" @dragover.prevent="onDragOver($event, ph)" @dragleave="onDragLeave" @drop="onDrop($event, ph, grp)"
                    >
                      <td class="pa-td-grip"><i v-if="ph.level > 1" class="ti ti-grip-vertical pa-grip-icon"></i></td>
                      <td class="pa-td-code"><span v-if="ph.level > 1" class="pa-connector">└</span><code class="pa-mono" :style="{ color: ptColor(grp.phase_num) }">{{ ph.code_full || ph.code }}</code></td>
                      <td class="pa-td-label">
                        <span class="pa-phase-label-text">{{ ph.label }}</span>
                        <span v-if="prevStart(ph.id, activeEntityId)" class="pa-prev-hint"><i class="ti ti-corner-down-right"></i>≥ {{ fmt(prevStart(ph.id, activeEntityId)) }}</span>
                      </td>
                      <td class="pa-td-form">
                        <template v-if="ph.form_code && forms[ph.form_code]">
                          <a
                            v-if="forms[ph.form_code].url_path && isCfgChk(ph.id, activeEntityId)"
                            :href="buildFormUrl(forms[ph.form_code].url_path, localMission.id)" target="_blank"
                            class="pa-form-link" :title="forms[ph.form_code].label"
                          >
                            <i :class="forms[ph.form_code].icon || 'ti ti-file'"></i><span>{{ ph.form_code }}</span>
                          </a>
                          <span v-else class="pa-form-tag" :title="forms[ph.form_code].label">
                            <i :class="forms[ph.form_code].icon || 'ti ti-file'"></i>{{ ph.form_code }}
                          </span>
                        </template>
                        <span v-else class="pa-muted pa-small">—</span>
                      </td>
                      <td class="pa-td-toggle">
                        <label class="pa-toggle">
                          <input type="checkbox" :checked="isCfgChk(ph.id, activeEntityId)" :disabled="ph.is_mandatory" @change="toggleEntCheck(ph.id, activeEntityId, $event.target.checked)" />
                          <span class="pa-toggle-track"></span>
                        </label>
                      </td>
                      <td class="pa-td-status">
                        <select
                          class="pa-select" :value="getCfg(ph.id, activeEntityId).status || 'pending'"
                          :disabled="!isCfgChk(ph.id, activeEntityId)" @change="setCfg(ph.id, activeEntityId, { status: $event.target.value })"
                        >
                          <option value="pending">En attente</option>
                          <option value="in_progress">En cours</option>
                          <option value="completed">Terminé</option>
                          <option value="skipped">Ignoré</option>
                        </select>
                      </td>
                      <td class="pa-td-date" :class="{ 'has-error': hasStartErr(ph.id, activeEntityId) }">
                        <input
                          type="date" class="pa-date-input" :class="{ 'is-active': isCfgChk(ph.id, activeEntityId), 'has-error': hasStartErr(ph.id, activeEntityId) }"
                          :value="getCfg(ph.id, activeEntityId).planned_start" :min="minStart(ph.id, activeEntityId)" :max="activeEntity?.date_fin"
                          :disabled="!isCfgChk(ph.id, activeEntityId)" @change="onStartChange(ph.id, activeEntityId, $event.target.value)"
                        />
                      </td>
                      <td class="pa-td-date">
                        <input
                          type="date" class="pa-date-input" :class="{ 'is-active': isCfgChk(ph.id, activeEntityId) }"
                          :value="getCfg(ph.id, activeEntityId).planned_end"
                          :min="getCfg(ph.id, activeEntityId).planned_start || minStart(ph.id, activeEntityId)" :max="activeEntity?.date_fin"
                          :disabled="!isCfgChk(ph.id, activeEntityId)" @change="onEndChange(ph.id, activeEntityId, $event.target.value)"
                        />
                      </td>
                      <td class="pa-td-days">
                        <span v-if="getCfg(ph.id, activeEntityId).planned_start && getCfg(ph.id, activeEntityId).planned_end" class="pa-days-badge" :class="phaseDays(ph.id, activeEntityId) > 0 ? 'is-ok' : 'is-error'">{{ phaseDays(ph.id, activeEntityId) }} j</span>
                        <span v-else class="pa-muted">—</span>
                      </td>
                      <td v-for="aud in entityAuds(activeEntityId)" :key="'a' + aud.auditeur_id" class="pa-td-aud" :class="{ 'is-active': isAudChk(ph.id, activeEntityId, aud.auditeur_id) }">
                        <label class="pa-aud-toggle" :title="aud.full_name">
                          <input type="checkbox" :checked="isAudChk(ph.id, activeEntityId, aud.auditeur_id)" :disabled="!isCfgChk(ph.id, activeEntityId)" @change="toggleAud(ph.id, activeEntityId, aud.auditeur_id, $event.target.checked)" />
                          <span class="pa-aud-face" :class="isAudChk(ph.id, activeEntityId, aud.auditeur_id) ? 'is-on ' + rCls(aud.role_code || aud.role) : 'is-off'">
                            <i v-if="isAudChk(ph.id, activeEntityId, aud.auditeur_id)" class="ti ti-check"></i>
                          </span>
                        </label>
                      </td>
                      <td class="pa-td-note">
                        <button type="button" class="pa-note-btn" :class="{ 'has-note': getCfg(ph.id, activeEntityId).notes }" :disabled="!isCfgChk(ph.id, activeEntityId)" @click="openNote(ph.id, activeEntityId)">
                          <i class="ti ti-notes"></i>
                        </button>
                      </td>
                    </tr>
                  </template>
                </template>

                <tr v-if="!checkedByGroup.length">
                  <td :colspan="10 + entityAuds(activeEntityId).length" class="pa-empty-row">Aucune phase sélectionnée — revenez à l'étape « Phases »</td>
                </tr>
                <tr v-if="checkedByGroup.length && activeEntityId" class="pa-row-total">
                  <td colspan="7"><span class="pa-total-label"><i class="ti ti-sum"></i>Total</span></td>
                  <td class="pa-td-date pa-total-dates" colspan="2"><span class="pa-total-range">{{ fmt(totalStartDate(activeEntityId)) }} → {{ fmt(totalEndDate(activeEntityId)) }}</span></td>
                  <td class="pa-td-days pa-total-days"><span class="pa-days-badge is-total">{{ totalJours(activeEntityId) }} j</span></td>
                  <td :colspan="entityAuds(activeEntityId).length + 1" class="pa-total-hint">union des intervalles sélectionnés</td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>
        <div v-else class="pa-panel-state"><i class="ti ti-building"></i>Choisissez une entité ci-dessus pour commencer</div>
      </section>

    </div>

    <!-- ── Notifications ── -->
    <Teleport to="body">
      <Transition name="pa-fade">
        <div v-if="toast.show" class="pa-toast" :class="'is-' + toast.type">
          <i :class="'ti ' + (toast.type === 'success' ? 'ti-circle-check' : toast.type === 'warning' ? 'ti-alert-triangle' : 'ti-circle-x')"></i>
          <span>{{ toast.message }}</span>
          <button type="button" @click="toast.show = false"><i class="ti ti-x"></i></button>
        </div>
      </Transition>
    </Teleport>

    <!-- ── Modale de note ── -->
    <Teleport to="body">
      <div v-if="noteModal.show" class="pa-modal-bg" @click.self="closeNote">
        <div class="pa-modal">
          <div class="pa-modal-head">
            <i class="ti ti-notes"></i>Note de phase
            <button type="button" class="pa-icon-btn" @click="closeNote"><i class="ti ti-x"></i></button>
          </div>
          <textarea v-model="noteModal.draft" class="pa-modal-textarea" rows="5" placeholder="Saisir une note pour cette phase…"></textarea>
          <div class="pa-modal-foot">
            <button type="button" class="pa-btn pa-btn--ghost pa-btn--sm" @click="closeNote">Annuler</button>
            <button type="button" class="pa-btn pa-btn--primary pa-btn--sm" @click="saveNote"><i class="ti ti-check"></i>Valider</button>
          </div>
        </div>
      </div>
    </Teleport>

  </VerticalLayout>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import VerticalLayout from '@/layouts/VerticalLayout.vue'
import { computed, onMounted, reactive, ref, watch } from 'vue'

const props = defineProps({
  allMissions: { type: Array,  default: () => [] },
  mission:     { type: Object, default: null },
  entities:    { type: Array,  default: () => [] },
  phases:      { type: Array,  default: () => [] },
  assignments: { type: Object, default: () => ({}) },
  auditeurs:   { type: Object, default: () => ({}) },
  forms:       { type: Object, default: () => ({}) },
  currentUser: { type: Object, default: () => ({}) },
})

const saveUrl = (id: number) => `/m/audit.core/api/mission-phases/affectation/${id}`

function normAud(o: any): Record<string, any[]> {
  if (!o || typeof o !== 'object') return {}
  return Object.fromEntries(Object.entries(o).map(([k, v]) => [String(k), v as any[]]))
}

const steps = ['Mission', 'Phases', 'Affectation']
const hasInitialAssignments = Object.keys(props.assignments || {}).length > 0
const step = ref(props.mission ? (hasInitialAssignments ? 3 : 2) : 1)

const search       = ref('')
const fStatus      = ref('')
const phaseSearch  = ref('')
const loadingData  = ref(false)
const showWarnings = ref(false)
const warnings     = ref<string[]>([])

const statusFilters = [
  { v: '', l: 'Toutes' }, { v: 'planifiee', l: 'Planifiée' },
  { v: 'en_cours', l: 'En cours' }, { v: 'terminee', l: 'Terminée' },
]

const selectedMission = ref<any>(props.mission ? { ...props.mission } : null)
const localMission    = ref<any>(props.mission ? { ...props.mission } : {})
const localEntities   = ref<any[]>([...(props.entities as any[])])
const localPhases     = ref<any[]>([...(props.phases as any[])])
const localAuds       = ref(normAud(props.auditeurs))
const forms           = ref<Record<string, any>>(props.forms as any || {})
const formsSource     = ref<string>('Session / ddmparam')

const checkedPhaseIds = ref(new Set<number>())
const entCfg: Record<number, Record<number, any>> = reactive({})
const dirty          = ref(new Set<string>())
// Clé numérique (phase_num, 1..5) — plus une string figée.
const openGroups     = ref(new Set<number>((props.phases as any[]).map((g: any) => g.phase_num)))
const saving         = ref(false)
const toast          = ref({ show: false, type: 'success', message: '' })
const activeEntityId = ref<number | null>((props.entities as any[])[0]?.id ?? null)
const draggingId     = ref<number | null>(null)
const draggingPhase  = ref<any>(null)
const dragOverId     = ref<number | null>(null)
const noteModal      = ref({ show: false, phaseId: null as number | null, entityId: null as number | null, draft: '' })

// ── Synchronisation ddmparam ─────────────────────────────────────────────
const syncing        = ref(false)
const syncResult     = ref<null | {
  success: boolean; updated: number; created: number; skipped: number;
  changes: any[]; message: string; error?: string;
}>(null)
const showSyncResult = ref(false)

function emptyCfg() {
  return { checked: false, status: 'pending', planned_start: null, planned_end: null, notes: null, auditeurs: [] }
}

// Une phase renvoyée par ddmparam mais pas encore provisionnée côté tenant
// (pas de ligne mission_phases correspondante) porte `provisioned: false`
// (cf. MissionPhaseAffectationController::getPhasesForType). Comme l'id est
// désormais toujours l'id réel de ddmparam.audit_type_forms (jamais un id
// négatif "virtuel"), on se fie explicitement à ce flag plutôt qu'au signe de l'id.
function isProvisioned(p: any) { return p.provisioned !== false }

// ── Données dérivées ──────────────────────────────────────────────────────
const filteredMissions = computed(() => {
  const q = search.value.trim().toLowerCase()
  return (props.allMissions as any[]).filter(m => {
    const matchesSearch = !q || [m.code_mission, m.libelle, m.entities_list].some((s: any) => String(s || '').toLowerCase().includes(q))
    return matchesSearch && (!fStatus.value || m.status === fStatus.value)
  })
})

const totalPhases = computed(() => {
  let n = 0
  for (const g of localPhases.value) for (const p of g.phases) { n++; n += p.children?.length || 0 }
  return n
})
const pct = computed(() => totalPhases.value ? Math.round(checkedPhaseIds.value.size / totalPhases.value * 100) : 0)
const activeEntity = computed(() => localEntities.value.find(e => e.id === activeEntityId.value) || null)

const formsList = computed(() => Object.values(forms.value).sort((a: any, b: any) => {
  if (a.phase_num !== b.phase_num) return a.phase_num - b.phase_num
  if (a.level !== b.level) return a.level - b.level
  return (a.sort_order || 0) - (b.sort_order || 0)
}))

const filteredPhaseGroups = computed(() => {
  const q = phaseSearch.value.trim().toLowerCase()
  if (!q) return localPhases.value
  return localPhases.value
    .map(g => ({
      ...g,
      phases: g.phases
        .map((p: any) => ({ ...p, children: (p.children || []).filter((c: any) => c.label.toLowerCase().includes(q) || (c.code_full || c.code).toLowerCase().includes(q)) }))
        .filter((p: any) => p.label.toLowerCase().includes(q) || (p.code_full || p.code).toLowerCase().includes(q) || p.children.length > 0),
    }))
    .filter(g => g.phases.length > 0)
})

const checkedByGroup = computed(() => {
  const out: any[] = []
  for (const g of localPhases.value) {
    const rows: any[] = []
    for (const p of g.phases) {
      const kids = (p.children || []).filter((c: any) => checkedPhaseIds.value.has(c.id))
      if (kids.length) {
        rows.push({ ...p, hasSelectedChildren: true, level: p.level || 1, _parentId: null })
        kids.forEach((c: any) => rows.push({ ...c, hasSelectedChildren: false, level: 2, _parentId: p.id }))
      } else if (checkedPhaseIds.value.has(p.id)) {
        rows.push({ ...p, hasSelectedChildren: false, level: p.level || 1, _parentId: null })
      }
    }
    // phase_num (clé stable) + label (libellé dynamique venant de ddmparam,
    // déjà résolu côté backend) — plus de phase_type string ici.
    if (rows.length) out.push({ phase_num: g.phase_num, label: g.label, phases: rows })
  }
  return out
})
const allChkPhases = computed(() => checkedByGroup.value.flatMap(g => g.phases.filter((p: any) => !p.hasSelectedChildren)))

// ── Initialisation des affectations existantes ───────────────────────────
function initPh(id: number) { if (!entCfg[id]) entCfg[id] = {} }

for (const [k, v] of Object.entries(props.assignments || {})) {
  const [p, e] = (k as string).split('_').map(Number)
  if (!p || !e) continue
  checkedPhaseIds.value.add(p)
  initPh(p)
  const vv = v as any
  const audIds = Array.isArray(vv.auditeur_ids)
    ? vv.auditeur_ids.map(Number)
    : (Array.isArray(vv.auditeurs) ? vv.auditeurs.map((a: any) => Number(a.auditeur_id ?? a)) : [])
  entCfg[p][e] = { checked: true, status: vv.status || 'pending', planned_start: vv.planned_start || null, planned_end: vv.planned_end || null, notes: vv.notes || null, auditeurs: audIds }
}

/** Force le rattachement (et la config par entité) de toutes les phases obligatoires. */
function applyMandatoryPhases() {
  for (const g of localPhases.value) {
    for (const p of g.phases) {
      forceOne(p)
      for (const c of (p.children || [])) forceOne(c)
    }
  }
  function forceOne(ph: any) {
    if (!ph.is_mandatory || !isProvisioned(ph)) return
    const s = new Set(checkedPhaseIds.value); s.add(ph.id); checkedPhaseIds.value = s
    initPh(ph.id)
    for (const e of localEntities.value) {
      if (!entCfg[ph.id][e.id]) entCfg[ph.id][e.id] = { ...emptyCfg(), checked: true }
      else entCfg[ph.id][e.id].checked = true
    }
  }
}

onMounted(() => {
  applyMandatoryPhases()
  if (step.value === 3) {
    for (const id of checkedPhaseIds.value) {
      initPh(id)
      for (const e of localEntities.value) if (!entCfg[id][e.id]) entCfg[id][e.id] = emptyCfg()
    }
    if (!activeEntityId.value && localEntities.value.length) activeEntityId.value = localEntities.value[0].id
  }
  if (Object.keys(forms.value).length > 0) {
    const sample = Object.values(forms.value)[0] as any
    formsSource.value = sample?.route_name ? 'ddmparam (direct)' : 'Session utilisateur'
  }
})

// ── Observateurs ──────────────────────────────────────────────────────────
watch(() => props.entities, v => {
  localEntities.value = [...(v as any[])]
  if (!activeEntityId.value && (v as any[]).length) activeEntityId.value = (v as any[])[0].id
}, { deep: true })
watch(() => props.auditeurs, v => { localAuds.value = normAud(v) }, { deep: true })
watch(() => props.phases, v => {
  localPhases.value = [...(v as any[])]
  openGroups.value = new Set((v as any[]).map((g: any) => g.phase_num))
}, { deep: true })
watch(() => props.mission, v => { localMission.value = v ? { ...v } : {}; if (v) step.value = 2 })
watch(() => props.forms, v => { forms.value = (v as any) || {} }, { deep: true })

// ── Navigation ────────────────────────────────────────────────────────────
function tryGoStep(n: number) {
  if (n < step.value) { step.value = n; return }
  if (n === 2 && !selectedMission.value) return
  if (n === 3 && !checkedPhaseIds.value.size) return
  if (n === 3) goStep3(); else step.value = n
}
function pickMission(m: any) { selectedMission.value = m }
function pageUrl(id: number) { return window.location.pathname.split('?')[0] + '?mission_id=' + id }
function goStep3() {
  for (const id of checkedPhaseIds.value) {
    initPh(id)
    for (const e of localEntities.value) if (!entCfg[id][e.id]) entCfg[id][e.id] = emptyCfg()
  }
  applyMandatoryPhases()
  if (!activeEntityId.value && localEntities.value.length) activeEntityId.value = localEntities.value[0].id
  step.value = 3
}
function selectEntity(eid: number) { activeEntityId.value = eid }

// ── Sélection des phases (étape 2) ───────────────────────────────────────
function togglePhaseCheck(p: any, checked: boolean) {
  if (p.is_mandatory || !isProvisioned(p)) return
  const s = new Set(checkedPhaseIds.value)
  if (checked) { s.add(p.id); initPh(p.id) } else s.delete(p.id)
  checkedPhaseIds.value = s
}
function toggleGroupCheck(g: any, checked: boolean) {
  const s = new Set(checkedPhaseIds.value)
  const apply = (ph: any) => {
    if (!isProvisioned(ph)) return
    if (!ph.is_mandatory || checked) { if (checked) { s.add(ph.id); initPh(ph.id) } else s.delete(ph.id) }
  }
  for (const p of g.phases) { apply(p); for (const c of (p.children || [])) apply(c) }
  checkedPhaseIds.value = s
}
function checkAllPhases() {
  const s = new Set<number>()
  for (const g of localPhases.value) for (const p of g.phases) {
    if (isProvisioned(p)) { s.add(p.id); initPh(p.id) }
    for (const c of (p.children || [])) if (isProvisioned(c)) { s.add(c.id); initPh(c.id) }
  }
  checkedPhaseIds.value = s
}
function uncheckAllPhases() {
  const s = new Set<number>()
  for (const g of localPhases.value) for (const p of g.phases) {
    if (p.is_mandatory && isProvisioned(p)) s.add(p.id)
    for (const c of (p.children || [])) if (c.is_mandatory && isProvisioned(c)) s.add(c.id)
  }
  checkedPhaseIds.value = s
}
function toggleGrp(pnum: number) {
  const s = new Set(openGroups.value)
  s.has(pnum) ? s.delete(pnum) : s.add(pnum)
  openGroups.value = s
}
function grpAllChk(g: any) { return g.phases.every((p: any) => checkedPhaseIds.value.has(p.id) && (p.children || []).every((c: any) => checkedPhaseIds.value.has(c.id))) }
function grpPartialChk(g: any) { return !grpAllChk(g) && g.phases.some((p: any) => checkedPhaseIds.value.has(p.id) || (p.children || []).some((c: any) => checkedPhaseIds.value.has(c.id))) }
function cntChkInGrp(g: any) {
  let n = 0
  for (const p of g.phases) { if (checkedPhaseIds.value.has(p.id)) n++; for (const c of (p.children || [])) if (checkedPhaseIds.value.has(c.id)) n++ }
  return n
}
function cntGrp(g: any) { let n = 0; for (const p of g.phases) { n++; n += p.children?.length || 0 }; return n }

// ── Configuration par entité (étape 3) ───────────────────────────────────
function getCfg(pid: number, eid: number) { return entCfg[pid]?.[eid] || emptyCfg() }
function setCfg(pid: number, eid: number, patch: any) {
  initPh(pid)
  if (!entCfg[pid][eid]) entCfg[pid][eid] = emptyCfg()
  Object.assign(entCfg[pid][eid], patch)
  const d = new Set(dirty.value); d.add(`${pid}_${eid}`); dirty.value = d
}
function isCfgChk(pid: number, eid: number) { return !!getCfg(pid, eid).checked }
function toggleEntCheck(pid: number, eid: number, checked: boolean) {
  if (allChkPhases.value.find((p: any) => p.id === pid)?.is_mandatory) return
  setCfg(pid, eid, { checked })
}

// ── Auditeurs ─────────────────────────────────────────────────────────────
const ROLE_ORDER: Record<string, number> = { DM: 1, CM: 2, AS: 3, AJ: 4 }
function entityAuds(eid: number | null) {
  if (!eid) return []
  const list = localAuds.value[String(eid)] || []
  return [...list].sort((a: any, b: any) => (ROLE_ORDER[a.role_code ?? a.role] ?? 9) - (ROLE_ORDER[b.role_code ?? b.role] ?? 9))
}
function isAudChk(pid: number, eid: number, audId: number) { return (getCfg(pid, eid).auditeurs || []).includes(Number(audId)) }
function toggleAud(pid: number, eid: number, audId: number, checked: boolean) {
  const list = [...(getCfg(pid, eid).auditeurs || [])].map(Number)
  const id = Number(audId), idx = list.indexOf(id)
  if (checked && idx === -1) list.push(id)
  if (!checked && idx !== -1) list.splice(idx, 1)
  setCfg(pid, eid, { auditeurs: list })
}
function selectAllAuds(eid: number) {
  const auds = entityAuds(eid).map((a: any) => Number(a.auditeur_id))
  if (!auds.length) return
  for (const ph of allChkPhases.value) if (isCfgChk(ph.id, eid)) setCfg(ph.id, eid, { auditeurs: [...auds] })
  showToast('Tous les auditeurs ont été assignés.', 'success')
}

// ── Dates ─────────────────────────────────────────────────────────────────
function prevStart(pid: number, eid: number): string | null {
  const phases = allChkPhases.value
  const idx = phases.findIndex((p: any) => p.id === pid)
  for (let i = idx - 1; i >= 0; i--) { const c = getCfg(phases[i].id, eid); if (c.checked && c.planned_start) return c.planned_start }
  return null
}
function minStart(pid: number, eid: number): string | null {
  const entityMin = activeEntity.value?.date_debut || null
  const prev = prevStart(pid, eid)
  if (!entityMin && !prev) return null
  if (!entityMin) return prev
  if (!prev) return entityMin
  return prev >= entityMin ? prev : entityMin
}
function onStartChange(pid: number, eid: number, value: string) {
  const floor = minStart(pid, eid)
  const validated = (floor && value && value < floor) ? floor : (value || null)
  const patch: any = { planned_start: validated }
  const cfg = getCfg(pid, eid)
  if (cfg.planned_end && validated && cfg.planned_end < validated) patch.planned_end = null
  setCfg(pid, eid, patch)

  const phases = allChkPhases.value
  const idx = phases.findIndex((p: any) => p.id === pid)
  if (idx === -1 || !validated) return
  let prevS = validated
  for (let i = idx + 1; i < phases.length; i++) {
    const nc = getCfg(phases[i].id, eid)
    if (!nc.checked) continue
    if (nc.planned_start && nc.planned_start >= prevS) break
    if (nc.planned_start) {
      const np: any = { planned_start: prevS }
      if (nc.planned_end && nc.planned_end < prevS) np.planned_end = null
      setCfg(phases[i].id, eid, np)
    }
    prevS = getCfg(phases[i].id, eid).planned_start || prevS
  }
}
function onEndChange(pid: number, eid: number, value: string) { setCfg(pid, eid, { planned_end: value || null }) }
function phaseDays(pid: number, eid: number): number {
  const c = getCfg(pid, eid)
  if (!c.planned_start || !c.planned_end) return 0
  const diff = Math.round((new Date(c.planned_end).getTime() - new Date(c.planned_start).getTime()) / 86400000) + 1
  return diff > 0 ? diff : 0
}
function totalJours(eid: number): number {
  const intervals: { s: string, e: string }[] = []
  for (const ph of allChkPhases.value) {
    const c = getCfg(ph.id, eid)
    if (!c.checked || !c.planned_start || !c.planned_end || c.planned_end < c.planned_start) continue
    intervals.push({ s: c.planned_start, e: c.planned_end })
  }
  if (!intervals.length) return 0
  intervals.sort((a, b) => a.s < b.s ? -1 : 1)
  const merged: { s: string, e: string }[] = [{ ...intervals[0] }]
  for (let i = 1; i < intervals.length; i++) {
    const cur = intervals[i], last = merged[merged.length - 1]
    if (cur.s <= last.e) { if (cur.e > last.e) last.e = cur.e } else merged.push({ ...cur })
  }
  let total = 0
  for (const seg of merged) total += Math.round((new Date(seg.e).getTime() - new Date(seg.s).getTime()) / 86400000) + 1
  return total
}
function totalStartDate(eid: number): string | null {
  let min: string | null = null
  for (const ph of allChkPhases.value) { const c = getCfg(ph.id, eid); if (c.checked && c.planned_start && (!min || c.planned_start < min)) min = c.planned_start }
  return min
}
function totalEndDate(eid: number): string | null {
  let max: string | null = null
  for (const ph of allChkPhases.value) { const c = getCfg(ph.id, eid); if (c.checked && c.planned_end && (!max || c.planned_end > max)) max = c.planned_end }
  return max
}
function entityDateErrors(eid: number | null) {
  if (!eid) return []
  const entity = localEntities.value.find(e => e.id === eid)
  const errs: any[] = []
  let prevS: string | null = null
  for (const ph of allChkPhases.value) {
    const c = getCfg(ph.id, eid)
    if (!c.checked) continue
    if (prevS && c.planned_start && c.planned_start < prevS) errs.push({ key: `${ph.id}_${eid}_start`, msg: `« ${ph.label} » : début antérieur à la phase précédente` })
    if (c.planned_start && c.planned_end && c.planned_end < c.planned_start) errs.push({ key: `${ph.id}_${eid}_end`, msg: `« ${ph.label} » : fin antérieure au début` })
    if (c.planned_end && entity?.date_fin && c.planned_end > entity.date_fin) errs.push({ key: `${ph.id}_${eid}_over`, msg: `« ${ph.label} » : dépasse la période de la mission` })
    if (c.planned_start) prevS = c.planned_start
  }
  return errs
}
function hasStartErr(pid: number, eid: number) { return entityDateErrors(eid).some(e => e.key === `${pid}_${eid}_start`) }
function entityProgress(eid: number) {
  const a = allChkPhases.value
  if (!a.length) return 0
  return Math.round(a.filter((p: any) => getCfg(p.id, eid).checked).length / a.length * 100)
}
function entityHasDirty(eid: number) { return [...dirty.value].some(k => k.endsWith('_' + eid)) }
function cascadeEntity(eid: number) {
  const entity = localEntities.value.find(e => e.id === eid)
  let prevS: string | null = entity?.date_debut || null
  for (const ph of allChkPhases.value) {
    const c = getCfg(ph.id, eid)
    if (!c.checked) continue
    if (prevS && (!c.planned_start || c.planned_start < prevS)) {
      const np: any = { planned_start: prevS }
      if (c.planned_end && c.planned_end < prevS) np.planned_end = null
      setCfg(ph.id, eid, np)
    }
    prevS = getCfg(ph.id, eid).planned_start || prevS
  }
  showToast('Dates alignées en cascade.', 'success')
}
function clearEntity(eid: number) {
  for (const ph of allChkPhases.value) if (getCfg(ph.id, eid).checked) setCfg(ph.id, eid, { planned_start: null, planned_end: null, auditeurs: [], notes: null, status: 'pending' })
  showToast('Entité réinitialisée.', 'warning')
}
function buildFormUrl(urlPath: string, missionId: number): string {
  if (!urlPath) return '#'
  const sep = urlPath.includes('?') ? '&' : '?'
  return urlPath + sep + 'mission_id=' + missionId
}

// ── Réordonnancement des sous-phases (glisser-déposer) ───────────────────
function canDrop(t: any) { return !!draggingPhase.value && draggingPhase.value.level > 1 && t.level > 1 && draggingPhase.value._parentId === t._parentId }
function onDragStart(e: DragEvent, ph: any, _g: any) {
  if (ph.level <= 1) { e.preventDefault(); return }
  draggingId.value = ph.id; draggingPhase.value = ph
  e.dataTransfer!.effectAllowed = 'move'
  e.dataTransfer!.setData('text/plain', String(ph.id))
}
function onDragOver(e: DragEvent, t: any) {
  if (!canDrop(t)) { dragOverId.value = null; return }
  e.preventDefault(); dragOverId.value = t.id
}
function onDragLeave() { dragOverId.value = null }
function onDrop(e: DragEvent, t: any, g: any) {
  e.preventDefault(); dragOverId.value = null
  if (!canDrop(t)) { draggingId.value = null; draggingPhase.value = null; return }
  const dragId = draggingId.value!, parentId = draggingPhase.value._parentId
  draggingId.value = null; draggingPhase.value = null

  const gi = localPhases.value.findIndex((gr: any) => gr.phase_num === g.phase_num)
  if (gi === -1) return
  const np = JSON.parse(JSON.stringify(localPhases.value))
  const parent = np[gi].phases.find((p: any) => p.id === parentId)
  if (!parent?.children) return
  const fi = parent.children.findIndex((c: any) => c.id === dragId)
  const ti = parent.children.findIndex((c: any) => c.id === t.id)
  if (fi === -1 || ti === -1) return
  const [moved] = parent.children.splice(fi, 1)
  parent.children.splice(ti, 0, moved)
  localPhases.value = np
  showToast('Ordre mis à jour pour cette session.', 'success')
}

// ── Notes ─────────────────────────────────────────────────────────────────
function openNote(pid: number, eid: number) { noteModal.value = { show: true, phaseId: pid, entityId: eid, draft: getCfg(pid, eid).notes || '' } }
function closeNote() { noteModal.value.show = false }
function saveNote() {
  const { phaseId, entityId, draft } = noteModal.value
  if (phaseId && entityId) setCfg(phaseId, entityId, { notes: draft || null })
  closeNote()
}

// ── Sauvegarde ────────────────────────────────────────────────────────────
async function saveAll() {
  if (saving.value || !dirty.value.size) return
  saving.value = true
  warnings.value = []

  const payload = []
  for (const k of dirty.value) {
    const [pid, eid] = k.split('_').map(Number)
    const c = getCfg(pid, eid)
    const phase = allChkPhases.value.find((p: any) => p.id === pid) as any
    const formCode = phase?.form_code || null
    const formUrl = formCode && forms.value[formCode] ? forms.value[formCode].url_path : null
    payload.push({
      phase_id: pid, entity_id: eid, checked: c.checked, status: c.status || 'pending',
      planned_start: c.planned_start || null, planned_end: c.planned_end || null,
      notes: c.notes || null, auditeur_ids: c.auditeurs || [], form_url: formUrl,
    })
  }

  try {
    const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
    const res = await fetch(saveUrl(localMission.value.id), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
      body: JSON.stringify({ assignments: payload }),
    })
    const json = await res.json()
    if (!res.ok) { showToast('Erreur : ' + (json?.error || json?.message || 'Erreur serveur'), 'error'); return }

    dirty.value = new Set()
    if (json.warnings?.length) {
      warnings.value = json.warnings; showWarnings.value = true
      showToast(`${json.upserted} sauvegardé(s) — ${json.warnings.length} avertissement(s).`, 'warning')
    } else {
      showToast(`${json.upserted} affectation(s) sauvegardée(s), ${json.deleted} supprimée(s).`, 'success')
    }
  } catch (e: any) {
    showToast('Erreur réseau : ' + e.message, 'error')
  } finally {
    saving.value = false
  }
}

// ── Synchronisation ddmparam ─────────────────────────────────────────────
async function syncPhasesFromDdm() {
  if (!localMission.value?.mission_type_id) { showToast('Aucun type de mission associé.', 'warning'); return }
  syncing.value = true; syncResult.value = null
  try {
    const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
    const res  = await fetch(`/m/audit.core/api/mission-phases/sync-labels/${localMission.value.mission_type_id}`, {
      method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
    })
    const json = await res.json()
    syncResult.value = json; showSyncResult.value = true
    if (json.success) {
      showToast(json.message, (json.updated > 0 || json.created > 0) ? 'success' : 'warning')
      if (json.updated > 0 || json.created > 0) await reloadPhases()
    } else {
      showToast(json.error || json.message || 'Erreur lors de la synchronisation', 'error')
    }
  } catch (e: any) {
    showToast('Erreur réseau : ' + e.message, 'error')
  } finally {
    syncing.value = false
  }
}

async function reloadPhases() {
  if (!localMission.value?.mission_type_id) return
  loadingData.value = true
  try {
    const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
    const res  = await fetch(`/m/audit.core/api/mission-phases/by-type/${localMission.value.mission_type_id}`, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf } })
    const json = await res.json()
    if (json.success && json.phases) {
      localPhases.value = json.phases
      openGroups.value = new Set(json.phases.map((g: any) => g.phase_num))
      showToast('Phases rechargées.', 'success')
    } else {
      window.location.reload()
    }
  } catch {
    window.location.reload()
  } finally {
    loadingData.value = false
  }
}

// ── Utilitaires d'affichage ───────────────────────────────────────────────
let toastTimer: ReturnType<typeof setTimeout>
function showToast(message: string, type = 'success') {
  toast.value = { show: true, type, message }
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { toast.value.show = false }, 5000)
}
function fmt(d?: string) { if (!d) return '—'; try { const [y, m, dd] = d.split('-'); return `${dd}/${m}/${y}` } catch { return d } }
function dateDiffDays(a: string, b: string) { return Math.round((new Date(b).getTime() - new Date(a).getTime()) / 86400000) + 1 }
function stLbl(s: string) { return ({ planifiee: 'Planifiée', en_cours: 'En cours', terminee: 'Terminée', annulee: 'Annulée' } as any)[s] || s }
function stChip(s: string) { return ({ planifiee: 'pa-badge--info', en_cours: 'pa-badge--primary', terminee: 'pa-badge--success', annulee: 'pa-badge--danger' } as any)[s] || 'pa-badge--neutral' }

// ── Style des groupes de phases ──────────────────────────────────────────
// Clé numérique stable (phase_num, 1..5, vient de ddmparam). Choix de
// PRÉSENTATION uniquement (couleur/icône) — le libellé affiché (group.label
// / grp.label) vient dynamiquement de ddmparam.audit_type_forms.phase_label
// côté backend et varie légitimement selon le type d'audit.
function ptColor(n: number) { return ({ 1: '#7C3AED', 2: '#2563EB', 3: '#059669', 4: '#D97706', 5: '#0D9488' } as any)[n] || '#94a3b8' }
function ptIcon(n: number) { return ({ 1: 'ti ti-tool', 2: 'ti ti-search', 3: 'ti ti-report', 4: 'ti ti-chart-line', 5: 'ti ti-bulb' } as any)[n] || 'ti ti-dots' }

function rCls(r: string) { return ({ DM: 'is-dm', CM: 'is-cm', AS: 'is-as', AJ: 'is-aj' } as any)[r] || 'is-other' }
function initials(full: string) {
  if (!full) return '?'
  const parts = full.trim().split(/\s+/)
  return (parts.length === 1 ? (parts[0][0] || '?') : (parts[0][0] + parts[parts.length - 1][0])).toUpperCase()
}
</script>

<style scoped>
/* ══════════════════════════════════════════════════════════
   DESIGN TOKENS
══════════════════════════════════════════════════════════ */
.pa-app {
  --ink: #0f172a;
  --body: #334155;
  --muted: #64748b;
  --faint: #94a3b8;
  --border: #e2e8f0;
  --surface: #ffffff;
  --surface-alt: #f8fafc;
  --surface-sunken: #f1f5f9;
  --primary: #2563eb;
  --primary-dark: #1d4ed8;
  --primary-soft: #eff6ff;
  --success: #059669;
  --success-soft: #ecfdf5;
  --warning: #d97706;
  --warning-soft: #fffbeb;
  --danger: #dc2626;
  --danger-soft: #fef2f2;
  --violet: #7c3aed;
  --violet-soft: #f5f3ff;
  --radius-sm: 6px;
  --radius-md: 10px;
  --radius-lg: 14px;
  --shadow-sm: 0 1px 2px rgba(15, 23, 42, .06);
  --shadow-md: 0 6px 20px rgba(15, 23, 42, .10);

  display: flex; flex-direction: column;
  height: calc(100vh - 68px);
  overflow: hidden;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border);
  background: var(--surface-alt);
  font-size: 13px;
  color: var(--body);
}

/* ══════════════════════════════════════════════════════════
   EN-TÊTE
══════════════════════════════════════════════════════════ */
.pa-header {
  display: flex; align-items: center; gap: 14px;
  padding: 8px 14px; min-height: 52px; flex-shrink: 0;
  background: var(--surface); border-bottom: 1px solid var(--border);
}
.pa-steps { display: flex; align-items: center; gap: 0; list-style: none; margin: 0; padding: 0; flex-shrink: 0; }
.pa-steps li { display: flex; align-items: center; }
.pa-step {
  display: flex; align-items: center; gap: 7px;
  padding: 5px 12px 5px 5px; border-radius: 20px;
  border: 1px solid var(--border); background: var(--surface-alt);
  font-size: .74rem; font-weight: 700; color: var(--muted);
  cursor: pointer; transition: all .15s;
}
.pa-step:hover:not(:disabled) { border-color: #bfdbfe; color: var(--primary); }
.pa-step:disabled { cursor: not-allowed; opacity: .55; }
.pa-step.is-active { background: var(--primary); color: #fff; border-color: var(--primary); }
.pa-step.is-done { background: var(--success-soft); color: var(--success); border-color: #a7f3d0; }
.pa-step-mark {
  width: 20px; height: 20px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: .65rem; font-weight: 800; background: rgba(255, 255, 255, .25);
  flex-shrink: 0;
}
.pa-step.is-done .pa-step-mark { background: var(--success); color: #fff; }
.pa-step-sep { width: 18px; height: 1px; background: var(--border); margin: 0 4px; flex-shrink: 0; }

.pa-mission-chip {
  display: flex; align-items: center; gap: 8px;
  padding-left: 14px; border-left: 1px solid var(--border);
  flex: 1; min-width: 0; overflow: hidden;
}
.pa-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.pa-mission-code {
  font-family: ui-monospace, monospace; font-size: .72rem; font-weight: 800;
  background: var(--primary-soft); color: var(--primary-dark);
  padding: 2px 8px; border-radius: 5px; flex-shrink: 0;
}
.pa-mission-title { font-size: .8rem; font-weight: 600; color: var(--body); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pa-mission-meta { font-size: .72rem; color: var(--muted); display: flex; align-items: center; gap: 4px; flex-shrink: 0; margin-left: auto; }
.pa-mission-meta i { margin-right: 2px; }

.pa-header-actions { display: flex; align-items: center; gap: 7px; flex-shrink: 0; }
.pa-loading-tag { display: flex; align-items: center; gap: 6px; font-size: .74rem; color: var(--muted); background: var(--surface-sunken); padding: 4px 10px; border-radius: 20px; }

/* ── Boutons ── */
.pa-btn {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 6px 13px; border-radius: var(--radius-sm);
  font-size: .76rem; font-weight: 700; border: 1px solid transparent;
  cursor: pointer; transition: all .12s; white-space: nowrap; line-height: 1.1;
}
.pa-btn--sm { padding: 4px 10px; font-size: .71rem; }
.pa-btn--block { width: 100%; justify-content: center; }
.pa-btn--icon { padding: 6px 9px; }
.pa-btn:disabled { opacity: .4; cursor: not-allowed; }
.pa-btn--ghost { background: var(--surface-alt); border-color: var(--border); color: var(--body); }
.pa-btn--ghost:hover:not(:disabled) { background: var(--primary-soft); border-color: #bfdbfe; color: var(--primary-dark); }
.pa-btn--primary { background: var(--primary); color: #fff; }
.pa-btn--primary:hover:not(:disabled) { background: var(--primary-dark); }
.pa-btn--success { background: var(--success); color: #fff; }
.pa-btn--success:hover:not(:disabled) { background: #047857; }
.pa-btn--danger-ghost { background: var(--danger-soft); border-color: #fecaca; color: var(--danger); }
.pa-btn--danger-ghost:hover:not(:disabled) { background: var(--danger); color: #fff; }
.pa-btn--ddm { background: linear-gradient(135deg, var(--violet), var(--primary-dark)); color: #fff; border: none; }
.pa-btn--ddm:hover:not(:disabled) { filter: brightness(1.08); }

.pa-icon-btn { background: none; border: none; color: var(--faint); cursor: pointer; padding: 2px; display: inline-flex; }
.pa-icon-btn:hover { color: var(--body); }

.pa-spinner { width: 11px; height: 11px; border: 2px solid rgba(37, 99, 235, .25); border-top-color: var(--primary); border-radius: 50%; animation: pa-spin .6s linear infinite; display: inline-block; }
.pa-spinner--light { border-color: rgba(255, 255, 255, .35); border-top-color: #fff; }
@keyframes pa-spin { to { transform: rotate(360deg); } }
@keyframes pa-blink { 0%, 100% { opacity: 1; } 50% { opacity: .25; } }

/* ── Badges / chips ── */
.pa-badge { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 20px; font-size: .68rem; font-weight: 700; white-space: nowrap; border: none; }
.pa-badge--clickable { cursor: pointer; }
.pa-badge--neutral { background: var(--surface-sunken); color: var(--muted); }
.pa-badge--primary { background: var(--primary-soft); color: var(--primary-dark); }
.pa-badge--info { background: #e0f2fe; color: #0369a1; }
.pa-badge--success { background: var(--success-soft); color: var(--success); }
.pa-badge--warning { background: var(--warning-soft); color: var(--warning); border: 1px solid #fde68a; }
.pa-badge--danger { background: var(--danger-soft); color: var(--danger); border: 1px solid #fecaca; }
.pa-badge--violet { background: var(--violet-soft); color: var(--violet); }
.pa-badge-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--warning); animation: pa-blink 1.4s infinite; }
.pa-chip { display: inline-flex; align-items: center; gap: 3px; padding: 1px 8px; border-radius: 20px; font-size: .64rem; font-weight: 700; }
.pa-chip--info { background: var(--primary-soft); color: var(--primary-dark); }
.pa-chip--success { background: var(--success-soft); color: var(--success); }
.pa-chip--neutral { background: var(--surface-sunken); color: var(--muted); }

/* ══════════════════════════════════════════════════════════
   PANNEAU DE SYNCHRO
══════════════════════════════════════════════════════════ */
.pa-sync-panel { flex-shrink: 0; font-size: .74rem; animation: pa-slide-down .18s ease; border-bottom: 1px solid transparent; }
@keyframes pa-slide-down { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }
.pa-sync-panel.is-ok { background: var(--success-soft); border-color: #a7f3d0; }
.pa-sync-panel.is-error { background: var(--danger-soft); border-color: #fecaca; }
.pa-sync-head { display: flex; align-items: center; gap: 9px; padding: 7px 14px; flex-wrap: wrap; }
.pa-sync-panel.is-ok .pa-sync-head i:first-child { color: var(--success); }
.pa-sync-panel.is-error .pa-sync-head i:first-child { color: var(--danger); }
.pa-sync-msg { font-weight: 700; color: var(--ink); }
.pa-sync-stats { display: flex; gap: 6px; flex-wrap: wrap; }
.pa-sync-panel .pa-icon-btn { margin-left: auto; }
.pa-sync-list { list-style: none; margin: 0; padding: 4px 14px 9px; display: flex; flex-direction: column; gap: 3px; max-height: 150px; overflow-y: auto; border-top: 1px dashed var(--border); }
.pa-sync-list li { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; font-size: .68rem; padding: 2px 0; }
.pa-sync-code { font-family: ui-monospace, monospace; font-size: .62rem; font-weight: 800; background: var(--violet-soft); color: var(--violet); padding: 1px 6px; border-radius: 4px; }
.pa-sync-label { color: var(--body); font-weight: 600; }
.pa-sync-diff { display: inline-flex; align-items: center; gap: 4px; }
.pa-sync-diff em { color: var(--muted); font-style: italic; }
.pa-sync-diff s { color: var(--danger); font-size: .62rem; }
.pa-sync-diff strong { color: var(--success); }

/* ══════════════════════════════════════════════════════════
   CORPS
══════════════════════════════════════════════════════════ */
.pa-body { flex: 1; overflow: hidden; display: flex; flex-direction: column; }
.pa-panel-state { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 9px; color: var(--faint); font-size: .84rem; background: var(--surface); text-align: center; padding: 24px; }
.pa-panel-state i { font-size: 1.7rem; opacity: .5; }
.pa-panel-state--empty { background: transparent; }

/* ── Étape 1 : liste des missions ── */
.pa-toolbar { display: flex; align-items: center; gap: 10px; padding: 9px 14px; background: var(--surface); border-bottom: 1px solid var(--border); flex-shrink: 0; flex-wrap: wrap; }
.pa-toolbar-title { font-size: .84rem; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: 6px; margin: 0; flex-shrink: 0; }
.pa-toolbar-count { color: var(--primary); font-weight: 800; }
.pa-search { display: flex; align-items: center; gap: 6px; background: var(--surface-alt); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 0 10px; flex: 1; min-width: 180px; max-width: 340px; transition: border-color .12s; }
.pa-search:focus-within { border-color: var(--primary); background: var(--surface); }
.pa-search i { color: var(--faint); font-size: .8rem; }
.pa-search input { border: none; background: none; font-size: .78rem; padding: 6px 0; flex: 1; outline: none; color: var(--ink); }
.pa-search--phases { max-width: none; margin-bottom: 8px; flex-shrink: 0; }
.pa-filter-group { display: flex; gap: 4px; }
.pa-filter { padding: 4px 11px; border-radius: 20px; border: 1px solid var(--border); background: var(--surface); color: var(--muted); font-size: .71rem; font-weight: 700; cursor: pointer; transition: all .12s; }
.pa-filter:hover { border-color: #bfdbfe; color: var(--primary); }
.pa-filter.is-active { background: var(--primary); color: #fff; border-color: var(--primary); }

.pa-table-scroll { flex: 1; overflow-y: auto; }
.pa-table { width: 100%; border-collapse: collapse; font-size: .78rem; }
.pa-table thead th { position: sticky; top: 0; z-index: 2; background: var(--surface-sunken); padding: 8px 10px; font-size: .64rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); border-bottom: 1px solid var(--border); white-space: nowrap; text-align: left; }
.pa-th-action { width: 42px; }
.pa-row { border-bottom: 1px solid var(--surface-alt); cursor: pointer; transition: background .1s; }
.pa-row:hover td { background: var(--primary-soft); }
.pa-row.is-selected td { background: #dbeafe; }
.pa-row td { padding: 7px 10px; vertical-align: middle; color: var(--body); }
.pa-code-tag { font-family: ui-monospace, monospace; font-size: .74rem; font-weight: 800; color: var(--primary-dark); background: var(--primary-soft); padding: 1px 6px; border-radius: 4px; }
.pa-row-link { display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: var(--radius-sm); background: var(--primary-soft); color: var(--primary); border: 1px solid #bfdbfe; text-decoration: none; transition: all .12s; }
.pa-row-link:hover { background: var(--primary); color: #fff; }
.pa-empty-row { text-align: center; padding: 34px !important; color: var(--faint); font-size: .82rem; }
.pa-empty-row i { display: block; font-size: 1.6rem; margin-bottom: 6px; opacity: .5; }

.pa-progress { display: flex; align-items: center; gap: 6px; min-width: 90px; }
.pa-progress-track { flex: 1; height: 5px; background: var(--surface-sunken); border-radius: 3px; overflow: hidden; }
.pa-progress-fill { height: 100%; background: var(--success); border-radius: 3px; transition: width .3s; }
.pa-progress-pct { font-size: .68rem; font-weight: 700; color: var(--success); white-space: nowrap; }

.pa-confirm-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 9px 14px; background: var(--primary-soft); border-top: 1px solid #bfdbfe; flex-shrink: 0; }
.pa-confirm-info { font-size: .78rem; color: var(--body); display: flex; align-items: center; gap: 5px; }

/* ── Étape 2 : sélection des phases ── */
.pa-body--split { flex-direction: row; padding: 12px; gap: 12px; overflow: hidden; min-height: 0; }
.pa-sidebar { width: 240px; flex-shrink: 0; background: var(--surface); border-radius: var(--radius-lg); border: 1px solid var(--border); display: flex; flex-direction: column; padding: 14px; gap: 14px; overflow-y: auto; min-height: 0; }
.pa-side-block { display: flex; flex-direction: column; gap: 4px; }
.pa-side-label { font-size: .62rem; text-transform: uppercase; letter-spacing: .06em; color: var(--faint); font-weight: 800; }
.pa-side-code { font-family: ui-monospace, monospace; font-size: .82rem; color: var(--primary); font-weight: 800; }
.pa-side-title { font-size: .78rem; font-weight: 600; color: var(--body); margin: 0; }
.pa-side-sub { font-size: .7rem; color: var(--muted); margin: 0; }
.pa-audit-badge { display: inline-flex; align-items: center; gap: 6px; color: #fff; padding: 6px 11px; border-radius: var(--radius-sm); font-size: .76rem; font-weight: 700; }
.pa-count-pill { display: inline-flex; align-items: center; justify-content: center; width: 17px; height: 17px; border-radius: 50%; background: var(--primary); color: #fff; font-size: .58rem; font-weight: 800; margin-left: 4px; }
.pa-forms-preview { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 2px; }
.pa-forms-preview li { display: flex; align-items: center; gap: 5px; font-size: .7rem; padding: 3px 5px; border-radius: 5px; background: var(--surface-alt); }
.pa-forms-preview li.is-child { padding-left: 14px; color: var(--faint); }
.pa-forms-preview li i { color: var(--muted); flex-shrink: 0; font-size: .74rem; }
.pa-forms-preview li code { font-family: ui-monospace, monospace; font-size: .62rem; font-weight: 800; color: var(--primary-dark); flex-shrink: 0; }
.pa-forms-preview li span { font-size: .68rem; color: var(--body); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pa-side-more { font-size: .68rem; color: var(--faint); font-style: italic; margin: 0; padding: 2px 5px; }
.pa-side-source { display: flex; align-items: center; gap: 4px; font-size: .62rem; color: var(--faint); margin: 4px 0 0; }
.pa-hint { display: flex; align-items: center; gap: 6px; font-size: .74rem; border-radius: var(--radius-sm); padding: 7px 9px; margin: 0; }
.pa-hint--warning { color: var(--warning); background: var(--warning-soft); border: 1px solid #fde68a; }
.pa-counter { display: flex; align-items: baseline; gap: 3px; }
.pa-counter-n { font-size: 1.5rem; font-weight: 800; color: var(--primary); line-height: 1; }
.pa-counter-t { font-size: .82rem; color: var(--faint); }
.pa-track { height: 5px; background: var(--surface-sunken); border-radius: 3px; overflow: hidden; }
.pa-track-fill { height: 100%; background: var(--primary); border-radius: 3px; transition: width .3s; }
.pa-side-actions { display: flex; gap: 5px; }

.pa-phase-panel { flex: 1; display: flex; flex-direction: column; min-height: 0; overflow: hidden; }
.pa-phase-groups { flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; min-height: 0; padding-bottom: 10px; }
.pa-phase-group { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-md); overflow: hidden; box-shadow: var(--shadow-sm); }
.pa-phase-group-head { display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: var(--surface-alt); cursor: pointer; user-select: none; border: none; width: 100%; text-align: left; border-left: 3px solid var(--pt-color); }
.pa-phase-group-icon { font-size: .84rem; color: var(--pt-color); }
.pa-phase-group-name { flex: 1; font-size: .8rem; font-weight: 700; color: var(--pt-color); }
.pa-phase-group-count { font-size: .68rem; color: var(--faint); font-weight: 600; }
.pa-phase-group-chevron { color: var(--faint); font-size: .8rem; }
.pa-phase-group-body { border-top: 1px solid var(--surface-alt); }
.pa-phase-row { display: flex; align-items: center; gap: 8px; padding: 6px 12px; border-bottom: 1px solid var(--surface-alt); cursor: pointer; transition: background .1s; min-height: 34px; }
.pa-phase-row:last-child { border-bottom: none; }
.pa-phase-row:hover { background: var(--surface-alt); }
.pa-phase-row.is-mandatory { background: var(--warning-soft); }
.pa-phase-row.is-unprovisioned { opacity: .62; }
.pa-phase-row--child { padding-left: 30px; }
.pa-connector { color: var(--border); font-family: ui-monospace, monospace; font-size: .78rem; flex-shrink: 0; }
.pa-phase-code { font-family: ui-monospace, monospace; font-size: .64rem; color: var(--muted); background: var(--surface-sunken); padding: 1px 5px; border-radius: 4px; flex-shrink: 0; }
.pa-phase-name { flex: 1; font-size: .78rem; font-weight: 500; color: var(--body); }
.pa-form-tag { display: inline-flex; align-items: center; gap: 3px; font-size: .64rem; font-weight: 700; color: var(--primary-dark); background: var(--primary-soft); padding: 2px 6px; border-radius: 4px; flex-shrink: 0; }
.pa-checkbox { width: 14px; height: 14px; accent-color: var(--primary); cursor: pointer; flex-shrink: 0; }
.pa-checkbox:disabled { cursor: not-allowed; }

/* ── Étape 3 : tableau d'affectation ── */
.pa-body--assign { background: var(--surface); }
.pa-entity-tabs { display: flex; overflow-x: auto; gap: 3px; padding: 7px 14px 0; background: var(--surface-sunken); border-bottom: 2px solid var(--border); flex-shrink: 0; scrollbar-width: none; }
.pa-entity-tabs::-webkit-scrollbar { display: none; }
.pa-entity-tab { display: flex; align-items: center; gap: 5px; padding: 6px 13px; border: 1px solid transparent; border-bottom: none; border-radius: 8px 8px 0 0; background: rgba(255, 255, 255, .55); font-size: .72rem; font-weight: 600; color: var(--muted); cursor: pointer; white-space: nowrap; flex-shrink: 0; transition: all .12s; }
.pa-entity-tab:hover { background: rgba(255, 255, 255, .95); color: var(--body); }
.pa-entity-tab.is-active { background: var(--surface); color: var(--primary); border-color: var(--border); border-bottom-color: var(--surface); margin-bottom: -2px; }
.pa-entity-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.pa-entity-dot.is-gray { background: var(--border); }
.pa-entity-dot.is-green { background: var(--success); }
.pa-entity-dot.is-amber { background: var(--warning); animation: pa-blink 1.4s infinite; }
.pa-entity-name { font-weight: 700; }
.pa-entity-range { font-size: .62rem; color: var(--faint); font-weight: 400; }
.pa-entity-pct { font-size: .62rem; font-weight: 800; color: var(--success); }

.pa-entity-bar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding: 7px 14px; background: var(--primary-soft); border-bottom: 1px solid #bfdbfe; flex-shrink: 0; }
.pa-entity-bar-actions { margin-left: auto; display: flex; gap: 5px; }
.pa-warnings { background: var(--warning-soft); border-bottom: 1px solid #fde68a; flex-shrink: 0; max-height: 130px; overflow-y: auto; }
.pa-warnings-head { display: flex; align-items: center; gap: 7px; padding: 6px 14px; font-size: .78rem; font-weight: 700; color: var(--warning); }
.pa-warnings-head .pa-icon-btn { margin-left: auto; color: var(--warning); }
.pa-warnings ul { list-style: none; margin: 0; padding: 0 14px 9px 32px; font-size: .74rem; color: #92400e; }
.pa-warnings li { margin-bottom: 3px; list-style: disc; }

.pa-assign-scroll { flex: 1; overflow: auto; }
.pa-assign-table { width: 100%; border-collapse: collapse; font-size: .74rem; }
.pa-assign-table thead th { position: sticky; top: 0; z-index: 5; background: var(--ink); padding: 7px 6px; font-size: .6rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; color: #94a3b8; border-bottom: 1px solid #1e293b; white-space: nowrap; text-align: center; }
.pa-th-left { text-align: left !important; }
.pa-col-grip { width: 20px; }
.pa-col-code { width: 84px; padding-left: 10px !important; }
.pa-col-label { min-width: 160px; }
.pa-col-form { width: 92px; }
.pa-col-toggle { width: 42px; }
.pa-col-status { width: 96px; }
.pa-col-date { width: 112px; }
.pa-col-date small { display: block; font-size: .52rem; color: #fbbf24; font-weight: 400; text-transform: none; letter-spacing: 0; }
.pa-col-days { width: 42px; color: #c4b5fd !important; }
.pa-col-aud { width: 46px; min-width: 42px; padding: 4px 2px !important; }
.pa-col-note { width: 32px; }

.pa-aud-head { display: flex; flex-direction: column; align-items: center; gap: 2px; }
.pa-aud-avatar { width: 21px; height: 21px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .52rem; font-weight: 800; }
.pa-aud-role { font-size: .5rem; font-weight: 700; padding: 0 3px; border-radius: 3px; text-transform: uppercase; }

.pa-row-sep td { padding: 5px 10px; background: var(--surface-sunken); border-bottom: 1px solid var(--border); font-size: .72rem; }
.pa-row-sep i { margin-right: 5px; }
.pa-row-sep span { margin-left: 6px; }
.pa-td-status { padding: 3px !important; }
.pa-select { width: 100%; border: 1px solid var(--border); border-radius: 4px; padding: 3px 4px; font-size: .66rem; background: var(--surface-alt); color: var(--body); outline: none; }
.pa-select:disabled { opacity: .35; cursor: not-allowed; }
.pa-td-form { text-align: center; padding: 3px 4px !important; }
.pa-form-link { display: inline-flex; align-items: center; gap: 3px; font-size: .66rem; font-weight: 700; color: var(--primary-dark); background: var(--primary-soft); padding: 2px 6px; border-radius: 4px; text-decoration: none; transition: all .15s; }
.pa-form-link:hover { background: var(--primary); color: #fff; }
.pa-td-days { text-align: center; padding: 3px 2px !important; white-space: nowrap; }
.pa-days-badge { display: inline-flex; align-items: center; padding: 1px 6px; border-radius: 8px; font-size: .64rem; font-weight: 800; font-family: ui-monospace, monospace; }
.pa-days-badge.is-ok { background: var(--violet-soft); color: #6d28d9; border: 1px solid #ddd6fe; }
.pa-days-badge.is-error { background: var(--danger-soft); color: var(--danger); border: 1px solid #fecaca; }
.pa-days-badge.is-total { background: var(--violet); color: #fff; border: none; font-size: .72rem; padding: 3px 8px; }

.pa-row-total td { padding: 7px 6px; background: var(--ink); border-top: 2px solid #334155; vertical-align: middle; }
.pa-total-label { display: inline-flex; align-items: center; gap: 5px; font-size: .7rem; font-weight: 800; color: #94a3b8; letter-spacing: .06em; text-transform: uppercase; }
.pa-total-dates { text-align: left; }
.pa-total-range { font-family: ui-monospace, monospace; font-size: .7rem; color: #7dd3fc; font-weight: 700; }
.pa-total-hint { color: #64748b; font-size: .62rem; font-style: italic; }

.pa-row-locked td { padding: 5px 6px; background: var(--surface-alt); border-bottom: 1px solid var(--surface-alt); vertical-align: middle; }
.pa-lock-icon { font-size: .78rem; color: var(--border); }
.pa-locked-label { font-size: .74rem; font-weight: 600; color: var(--faint); }
.pa-locked-hint { font-size: .64rem; color: var(--faint); font-style: italic; margin-left: 6px; }

.pa-row-phase td { padding: 5px 6px; border-bottom: 1px solid var(--surface-alt); background: var(--surface); vertical-align: middle; transition: background .1s; }
.pa-row-phase:hover td { background: #fafbfe; }
.pa-row-phase.is-active td { background: var(--success-soft) !important; }
.pa-row-phase.is-child td { background: #fcfcff; }
.pa-row-phase.is-child:hover td { background: #f2f6ff; }
.pa-row-phase.has-warning td { background: var(--danger-soft) !important; }
.pa-row-phase.is-dragover { outline: 2px dashed var(--primary); outline-offset: -1px; }
.pa-row-phase.is-dragging { opacity: .3; }
.pa-td-grip { width: 20px; text-align: center; }
.pa-grip-icon { color: var(--border); cursor: grab; font-size: .84rem; }
.pa-grip-icon:hover { color: var(--primary); }
.pa-td-code { padding-left: 8px !important; white-space: nowrap; }
.pa-td-label { max-width: 190px; }
.pa-phase-label-text { font-size: .76rem; font-weight: 500; color: var(--body); display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pa-prev-hint { display: flex; align-items: center; gap: 2px; font-size: .6rem; color: var(--warning); margin-top: 1px; }
.pa-td-toggle { text-align: center; }
.pa-toggle { display: inline-flex; cursor: pointer; }
.pa-toggle input { display: none; }
.pa-toggle-track { width: 27px; height: 15px; background: var(--border); border-radius: 8px; position: relative; transition: background .18s; display: block; }
.pa-toggle-track::after { content: ''; position: absolute; top: 2px; left: 2px; width: 11px; height: 11px; background: #fff; border-radius: 50%; box-shadow: 0 1px 2px rgba(0, 0, 0, .2); transition: transform .18s; }
.pa-toggle input:checked + .pa-toggle-track { background: var(--success); }
.pa-toggle input:checked + .pa-toggle-track::after { transform: translateX(12px); }
.pa-toggle input:disabled + .pa-toggle-track { opacity: .3; cursor: not-allowed; }
.pa-td-date { padding: 3px 4px !important; }
.pa-date-input { width: 100%; border: 1px solid var(--border); border-radius: 4px; padding: 2px 4px; font-family: ui-monospace, monospace; font-size: .68rem; color: var(--body); background: var(--surface-alt); outline: none; transition: border-color .12s, box-shadow .12s; }
.pa-date-input:focus { border-color: var(--primary); box-shadow: 0 0 0 2px rgba(37, 99, 235, .12); background: var(--surface); }
.pa-date-input.is-active { border-color: #bfdbfe; color: var(--primary-dark); font-weight: 700; }
.pa-date-input.has-error { border-color: #fecaca !important; background: var(--danger-soft) !important; }
.pa-date-input:disabled { opacity: .25; cursor: not-allowed; }
.pa-td-aud { text-align: center; padding: 3px 2px !important; }
.pa-td-aud.is-active { background: rgba(5, 150, 105, .05) !important; }
.pa-aud-toggle { display: inline-flex; cursor: pointer; }
.pa-aud-toggle input { display: none; }
.pa-aud-face { width: 23px; height: 23px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .55rem; font-weight: 700; transition: all .12s; }
.pa-aud-face.is-off { border: 2px dashed #d1d5db; background: var(--surface); }
.pa-aud-face.is-off:hover { border-color: #9ca3af; }
.pa-aud-face.is-on { border: 2px solid; }
.pa-aud-toggle input:disabled ~ .pa-aud-face { opacity: .25; cursor: not-allowed; }
.pa-td-note { text-align: center; padding: 3px 2px !important; }
.pa-note-btn { width: 23px; height: 23px; border-radius: 5px; border: 1px solid var(--border); background: var(--surface-alt); color: var(--faint); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; font-size: .76rem; transition: all .12s; }
.pa-note-btn:hover:not(:disabled) { background: var(--primary-soft); color: var(--primary); border-color: #bfdbfe; }
.pa-note-btn.has-note { background: var(--warning-soft); color: var(--warning); border-color: #fde68a; }
.pa-note-btn:disabled { opacity: .2; cursor: not-allowed; }

/* ── Rôles auditeurs ── */
.is-dm { background: #fef3c7; color: #b45309; border-color: #fde68a !important; }
.is-cm { background: #dbeafe; color: #1d4ed8; border-color: #bfdbfe !important; }
.is-as { background: #d1fae5; color: #065f46; border-color: #6ee7b7 !important; }
.is-aj { background: #ede9fe; color: #6d28d9; border-color: #ddd6fe !important; }
.is-other { background: var(--surface-sunken); color: var(--muted); border-color: var(--border) !important; }

/* ── Utilitaires ── */
.pa-mono { font-family: ui-monospace, monospace; }
.pa-small { font-size: .72rem; }
.pa-muted { color: var(--muted); }
.pa-text-primary { color: var(--primary); }
.pa-text-success { color: var(--success); }
.pa-ellipsis { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pa-w-260 { max-width: 260px; }
.pa-w-180 { max-width: 180px; }

/* ── Notifications ── */
.pa-toast { position: fixed; bottom: 20px; right: 20px; z-index: 9999; display: flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: var(--radius-md); font-size: .8rem; font-weight: 500; box-shadow: var(--shadow-md); max-width: 420px; }
.pa-toast button { background: none; border: none; color: inherit; cursor: pointer; margin-left: 4px; opacity: .75; display: inline-flex; }
.pa-toast.is-success { background: #064e3b; color: #6ee7b7; }
.pa-toast.is-warning { background: #78350f; color: #fcd34d; }
.pa-toast.is-error { background: #7f1d1d; color: #fca5a5; }
.pa-fade-enter-active, .pa-fade-leave-active { transition: all .2s; }
.pa-fade-enter-from, .pa-fade-leave-to { opacity: 0; transform: translateY(8px); }

/* ── Modale ── */
.pa-modal-bg { position: fixed; inset: 0; background: rgba(10, 15, 30, .5); backdrop-filter: blur(3px); z-index: 1000; display: flex; align-items: center; justify-content: center; }
.pa-modal { background: var(--surface); border-radius: var(--radius-lg); width: 440px; max-width: 94vw; box-shadow: var(--shadow-md); border: 1px solid var(--border); overflow: hidden; }
.pa-modal-head { display: flex; align-items: center; gap: 7px; padding: 12px 16px; font-size: .86rem; font-weight: 700; color: var(--ink); border-bottom: 1px solid var(--surface-alt); }
.pa-modal-head .pa-icon-btn { margin-left: auto; }
.pa-modal-textarea { width: 100%; border: none; padding: 13px 16px; font-family: inherit; font-size: .82rem; color: var(--body); resize: vertical; outline: none; min-height: 110px; }
.pa-modal-foot { display: flex; justify-content: flex-end; gap: 8px; padding: 10px 16px; border-top: 1px solid var(--surface-alt); }

/* ── Scrollbars ── */
.pa-assign-scroll::-webkit-scrollbar, .pa-table-scroll::-webkit-scrollbar, .pa-phase-groups::-webkit-scrollbar, .pa-sidebar::-webkit-scrollbar { width: 5px; height: 5px; }
.pa-assign-scroll::-webkit-scrollbar-track, .pa-table-scroll::-webkit-scrollbar-track, .pa-phase-groups::-webkit-scrollbar-track, .pa-sidebar::-webkit-scrollbar-track { background: transparent; }
.pa-assign-scroll::-webkit-scrollbar-thumb, .pa-table-scroll::-webkit-scrollbar-thumb, .pa-phase-groups::-webkit-scrollbar-thumb, .pa-sidebar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

/* ── Responsive ── */
@media (max-width: 768px) {
  .pa-app { height: 100vh; border-radius: 0; }
  .pa-body--split { flex-direction: column; overflow-y: auto; }
  .pa-sidebar { width: 100%; flex-direction: row; flex-wrap: wrap; max-height: 220px; }
  .pa-phase-panel { min-height: 320px; }
}
</style>