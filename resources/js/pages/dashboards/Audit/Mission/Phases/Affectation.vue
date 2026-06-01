<template>
  <VerticalLayout>
    <Head title="Affectation des Phases" />

    <div class="paf-shell">

      <!-- ══════════════════════════════════════════════════════════
           BARRE SUPÉRIEURE — stepper + mission + actions
      ══════════════════════════════════════════════════════════ -->
      <div class="paf-bar">
        <div class="paf-steps">
          <button
            v-for="(lbl, i) in steps" :key="i"
            class="step-pill"
            :class="{ active: step === i+1, done: step > i+1 }"
            @click="tryGoStep(i+1)"
          >
            <span class="sp-num">
              <i v-if="step > i+1" class="ti ti-check"></i>
              <span v-else>{{ i+1 }}</span>
            </span>
            <span class="sp-lbl">{{ lbl }}</span>
          </button>
        </div>

        <div v-if="step >= 2 && localMission?.id" class="paf-mission-info">
          <span class="mi-color-dot" :style="{ background: localMission.audit_color ?? '#64748B' }"></span>
          <code class="mi-code">{{ localMission.code_mission }}</code>
          <span class="mi-lib">{{ localMission.libelle }}</span>
          <span class="mi-badge" v-if="localMission.audit_type_label">{{ localMission.audit_type_label }}</span>
          <span v-if="step === 3" class="mi-count"><i class="ti ti-list-check"></i> {{ checkedPhaseIds.size }} phase(s)</span>
        </div>

        <div class="paf-bar-right">
          <span v-if="loadingData" class="loading-pill"><span class="spin"></span> Chargement…</span>
          <button v-if="step >= 2" class="act act-ghost" @click="step = Math.max(1, step-1)">
            <i class="ti ti-arrow-left"></i>
          </button>

          <!-- ── BOUTON SYNC DDMPARAM — visible étape 2 ── -->
          <template v-if="step === 2 && localMission?.mission_type_id">
            <button
              class="act act-ddm"
              :disabled="syncing"
              :title="`Synchroniser libellés depuis ddmparam [${localMission.audit_type_code ?? '…'}]`"
              @click="syncPhasesFromDdm"
            >
              <span v-if="syncing" class="spin spin-ddm"></span>
              <i v-else class="ti ti-database-import"></i>
              {{ syncing ? 'Sync…' : 'Sync DDMParam' }}
            </button>
          </template>

          <template v-if="step === 2">
            <button class="act act-primary" :disabled="!checkedPhaseIds.size" @click="goStep3">
              Affecter <i class="ti ti-arrow-right"></i>
            </button>
          </template>
          <template v-if="step === 3">
            <span v-if="dirty.size" class="dirty-badge">
              <span class="db-dot"></span>{{ dirty.size }} modif.
            </span>
            <button class="act act-save" :disabled="saving || !dirty.size" @click="saveAll">
              <span v-if="saving" class="spin"></span>
              <i v-else class="ti ti-device-floppy"></i>
              {{ saving ? 'Sauvegarde…' : 'Sauvegarder' }}
            </button>
          </template>
        </div>
      </div>

      <!-- ── PANNEAU RÉSULTAT SYNC (apparaît après sync) ── -->
      <Transition name="tf">
        <div v-if="showSyncResult && syncResult" class="sync-panel" :class="syncResult.success ? 'sp-ok' : 'sp-err'">
          <div class="sp-head">
            <i :class="'ti ' + (syncResult.success ? 'ti-circle-check' : 'ti-circle-x')"></i>
            <span class="sp-msg">{{ syncResult.message || syncResult.error }}</span>
            <div class="sp-stats" v-if="syncResult.success">
              <span v-if="syncResult.updated" class="sp-stat up"><i class="ti ti-refresh"></i> {{ syncResult.updated }} mis à jour</span>
              <span v-if="syncResult.created" class="sp-stat cr"><i class="ti ti-plus"></i> {{ syncResult.created }} créé(s)</span>
              <span v-if="syncResult.skipped" class="sp-stat sk"><i class="ti ti-minus"></i> {{ syncResult.skipped }} inchangé(s)</span>
            </div>
            <button class="sp-close" @click="showSyncResult = false">✕</button>
          </div>
          <div v-if="syncResult.changes?.length" class="sp-body">
            <div v-for="ch in syncResult.changes" :key="ch.code" class="sp-change">
              <code class="sp-code">{{ ch.code }}</code>
              <span class="sp-act" :class="ch.action === 'created' ? 'sa-cr' : 'sa-up'">
                {{ ch.action === 'created' ? '+ créé' : '↻ màj' }}
              </span>
              <span v-if="ch.action === 'created'" class="sp-lbl">{{ ch.label }}</span>
              <template v-if="ch.fields?.length">
                <span v-for="f in ch.fields" :key="f.field" class="sp-field">
                  <span class="sp-fname">{{ f.field }}</span>
                  <s class="sp-old">{{ f.old || '—' }}</s>
                  <span class="sp-arrow">→</span>
                  <strong class="sp-new">{{ f.new }}</strong>
                </span>
              </template>
            </div>
          </div>
        </div>
      </Transition>

      <!-- ════ ÉTAPE 1 — Liste des missions ════ -->
      <div v-if="step === 1" class="paf-content">
        <div class="s1-toolbar">
          <span class="tb-title"><i class="ti ti-clipboard-list"></i> Missions <em>{{ filteredMissions.length }}</em></span>
          <div class="tb-search"><i class="ti ti-search"></i><input v-model="search" placeholder="Code, libellé, entité…" /></div>
          <div class="tb-pills">
            <button v-for="s in statusFilters" :key="s.v" class="pill" :class="{ active: fStatus === s.v }" @click="fStatus = s.v">{{ s.l }}</button>
          </div>
        </div>
        <div class="s1-table-wrap">
          <table class="g-table">
            <thead><tr><th>Code</th><th>Libellé</th><th>Type</th><th>Période</th><th>Entités</th><th>Avancement</th><th>Statut</th><th></th></tr></thead>
            <tbody>
              <tr v-for="m in filteredMissions" :key="m.id" :class="{ sel: selectedMission?.id === m.id }" @click="pickMission(m)">
                <td><code class="mono blue">{{ m.code_mission }}</code></td>
                <td class="ell max240">{{ m.libelle }}</td>
                <td>
                  <span class="chip cb" v-if="m.type_label || m.audit_type_label">{{ m.type_label || m.audit_type_label }}</span>
                  <span v-else class="muted">—</span>
                </td>
                <td class="mono muted small">{{ fmt(m.date_debut) }} → {{ fmt(m.date_fin) }}</td>
                <td class="ell max160 small muted">{{ m.entities_list || '—' }}</td>
                <td>
                  <div class="prog-bar-wrap" v-if="m.total_aff > 0" :title="`${m.completed_aff}/${m.total_aff}`">
                    <div class="prog-bar"><div class="prog-fill" :style="{ width: m.pct_aff + '%' }"></div></div>
                    <span class="prog-pct">{{ m.pct_aff }}%</span>
                  </div>
                  <span v-else class="muted small">—</span>
                </td>
                <td><span class="chip" :class="stChip(m.status)">{{ stLbl(m.status) }}</span></td>
                <td><a :href="pageUrl(m.id)" class="row-btn" title="Charger" @click.stop><i class="ti ti-arrow-right"></i></a></td>
              </tr>
              <tr v-if="!filteredMissions.length"><td colspan="8" class="empty-row"><i class="ti ti-search"></i> Aucune mission</td></tr>
            </tbody>
          </table>
        </div>
        <div v-if="selectedMission" class="s1-footer">
          <span class="sf-info"><i class="ti ti-check-circle green"></i><strong>{{ selectedMission.code_mission }}</strong> — {{ selectedMission.libelle }}</span>
          <a :href="pageUrl(selectedMission.id)" class="act act-primary">Charger cette mission <i class="ti ti-arrow-right"></i></a>
        </div>
      </div>

      <!-- ════ ÉTAPE 2 — Sélection des phases ════ -->
      <div v-if="step === 2" class="paf-content s2-layout">
        <div class="s2-side">
          <div class="side-block">
            <label class="side-label">Mission</label>
            <code class="side-code">{{ localMission.code_mission }}</code>
            <div class="side-lib">{{ localMission.libelle }}</div>
            <div class="side-period muted small">{{ fmt(localMission.date_debut) }} → {{ fmt(localMission.date_fin) }}</div>
          </div>
          <div class="side-block" v-if="localMission.audit_type_label">
            <label class="side-label">Type d'audit</label>
            <div class="side-type-badge" :style="{ background: localMission.audit_color ?? '#64748B' }">
              <i :class="localMission.audit_icon ?? 'ti ti-folder'"></i>
              {{ localMission.audit_type_label }}
            </div>
          </div>
          <div class="side-block" v-if="formsList.length > 0">
            <label class="side-label">Formulaires chargés <span class="badge-count">{{ formsList.length }}</span></label>
            <div class="forms-mini-list">
              <div v-for="f in formsList.slice(0, 8)" :key="f.code" class="form-mini-item" :class="{ 'fmi-child': f.level > 1 }">
                <i :class="f.icon || 'ti ti-file'" class="fmi-icon"></i>
                <span class="fmi-code">{{ f.code }}</span>
                <span class="fmi-label">{{ f.label }}</span>
              </div>
              <div v-if="formsList.length > 8" class="form-mini-more">+{{ formsList.length - 8 }} autres…</div>
            </div>
            <div class="forms-source-badge"><i class="ti ti-database"></i> Source : {{ formsSource }}</div>
          </div>
          <div class="side-block" v-else-if="!loadingData">
            <label class="side-label">Formulaires</label>
            <div class="no-forms-hint"><i class="ti ti-alert-triangle amber"></i> Aucun formulaire trouvé.</div>
          </div>
          <div class="side-block">
            <label class="side-label">Phases sélectionnées</label>
            <div class="side-counter"><span class="sc-n">{{ checkedPhaseIds.size }}</span><span class="sc-t">/ {{ totalPhases }}</span></div>
            <div class="sc-bar"><div class="sc-fill" :style="'width:' + pct + '%'"></div></div>
          </div>
          <div class="side-btns">
            <button class="act act-ghost sm" @click="checkAllPhases"><i class="ti ti-checks"></i> Tout</button>
            <button class="act act-ghost sm" @click="uncheckAllPhases"><i class="ti ti-x"></i> Réinitialiser</button>
          </div>
          <div class="side-submit">
            <button class="act act-primary full" :disabled="!checkedPhaseIds.size" @click="goStep3">
              Configurer l'affectation <i class="ti ti-arrow-right"></i>
            </button>
          </div>
        </div>

        <div class="s2-groups-wrap">
          <div v-if="loadingData" class="loading-phases"><span class="spin"></span> Chargement des phases…</div>
          <div v-else-if="!localPhases.length" class="empty-phases">
            <i class="ti ti-list-off"></i>
            <p>Aucune phase disponible.</p>
            <button class="act act-ddm sm" @click="syncPhasesFromDdm" :disabled="syncing">
              <i class="ti ti-database-import"></i> Importer depuis DDMParam
            </button>
          </div>
          <template v-else>
            <div class="phases-search-bar">
              <i class="ti ti-search"></i>
              <input v-model="phaseSearch" placeholder="Filtrer les phases…" class="phase-search-input" />
            </div>
            <div class="s2-groups">
              <div v-for="group in filteredPhaseGroups" :key="group.phase_type" class="pg-card">
                <div class="pg-head" :style="'border-left: 3px solid ' + ptColor(group.phase_type)" @click="toggleGrp(group.phase_type)">
                  <input type="checkbox" :checked="grpAllChk(group)" :indeterminate.prop="grpPartialChk(group)" @change="toggleGroupCheck(group, $event.target.checked)" @click.stop />
                  <span class="pg-icon">{{ ptIcon(group.phase_type) }}</span>
                  <span class="pg-name" :style="'color:' + ptColor(group.phase_type)">{{ ptLabel(group.phase_type) }}</span>
                  <span class="pg-cnt">{{ cntChkInGrp(group) }}/{{ cntGrp(group) }}</span>
                  <i :class="'ti ti-chevron-' + (openGroups.has(group.phase_type) ? 'up' : 'down') + ' pg-arr'"></i>
                </div>
                <div v-if="openGroups.has(group.phase_type)" class="pg-body">
                  <template v-for="p in group.phases" :key="p.id">
                    <label class="ph-row ph-parent" :class="{ mandatory: p.is_mandatory }">
                      <input type="checkbox" :checked="checkedPhaseIds.has(p.id)" :disabled="p.is_mandatory" @change="togglePhaseCheck(p, $event.target.checked)" />
                      <code class="ph-code">{{ p.code_full || p.code }}</code>
                      <span class="ph-name">{{ p.label }}</span>
                      <span v-if="p.form_code && forms[p.form_code]" class="ph-form-badge" :title="forms[p.form_code].label">
                        <i :class="forms[p.form_code].icon || 'ti ti-file'"></i> {{ p.form_code }}
                      </span>
                      <span v-if="p.children?.length" class="chip cv">{{ p.children.length }}</span>
                      <span v-if="p.is_mandatory" class="chip ca">Oblig.</span>
                    </label>
                    <label v-for="c in (p.children || [])" :key="c.id" class="ph-row ph-child" :class="{ mandatory: c.is_mandatory }">
                      <span class="conn">└</span>
                      <input type="checkbox" :checked="checkedPhaseIds.has(c.id)" :disabled="c.is_mandatory" @change="togglePhaseCheck(c, $event.target.checked)" />
                      <code class="ph-code">{{ c.code_full || c.code }}</code>
                      <span class="ph-name">{{ c.label }}</span>
                      <span v-if="c.form_code && forms[c.form_code]" class="ph-form-badge" :title="forms[c.form_code].label">
                        <i :class="forms[c.form_code].icon || 'ti ti-file'"></i> {{ c.form_code }}
                      </span>
                      <span v-if="c.is_mandatory" class="chip ca">Oblig.</span>
                    </label>
                  </template>
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>

      <!-- ════ ÉTAPE 3 — Tableau d'affectation ════ -->
      <div v-if="step === 3" class="paf-content s3-layout">
        <div class="ent-tabs">
          <button v-for="e in localEntities" :key="e.id" class="et" :class="{ active: activeEntityId === e.id, dirty: entityHasDirty(e.id) }" @click="selectEntity(e.id)">
            <span class="et-dot" :class="entityHasDirty(e.id) ? 'd-amber' : entityProgress(e.id) > 0 ? 'd-green' : 'd-gray'"></span>
            <span class="et-name">{{ e.name }}</span>
            <span class="et-sub">{{ fmt(e.date_debut) }} – {{ fmt(e.date_fin) }}</span>
            <span v-if="entityProgress(e.id) > 0" class="et-pct">{{ entityProgress(e.id) }}%</span>
          </button>
        </div>

        <template v-if="activeEntityId">
          <div class="ent-bar">
            <i class="ti ti-building blue"></i>
            <strong class="small">{{ activeEntity?.name }}</strong>
            <span class="muted small">{{ fmt(activeEntity?.date_debut) }} → {{ fmt(activeEntity?.date_fin) }}</span>
            <span v-if="activeEntity?.date_debut && activeEntity?.date_fin" class="chip cb small">{{ dateDiffDays(activeEntity.date_debut, activeEntity.date_fin) }}j</span>
            <span v-if="entityDateErrors(activeEntityId).length" class="err-pill"><i class="ti ti-alert-triangle"></i> {{ entityDateErrors(activeEntityId).length }} erreur(s)</span>
            <span v-if="warnings.length" class="warn-pill" @click="showWarnings = !showWarnings"><i class="ti ti-info-circle"></i> {{ warnings.length }} avertissement(s)</span>
            <div class="ent-bar-right">
              <button class="act act-ghost sm" @click="cascadeEntity(activeEntityId)" title="Cascade dates"><i class="ti ti-sort-ascending"></i> Cascade</button>
              <button class="act act-ghost sm" @click="selectAllAuds(activeEntityId)" title="Tous auditeurs"><i class="ti ti-user-check"></i> Auditeurs</button>
              <button class="act act-danger-ghost sm" @click="clearEntity(activeEntityId)" title="Réinitialiser"><i class="ti ti-eraser"></i></button>
            </div>
          </div>

          <div v-if="showWarnings && warnings.length" class="warnings-panel">
            <div class="wp-head"><i class="ti ti-info-circle"></i> Avertissements <button @click="showWarnings = false" class="wp-close">✕</button></div>
            <ul class="wp-list"><li v-for="(w, i) in warnings" :key="i">{{ w }}</li></ul>
          </div>

          <div class="aff-wrap">
            <table class="aff-tbl">
              <thead>
                <tr>
                  <th class="col-grip"></th>
                  <th class="col-code">Code</th>
                  <th class="col-lbl">Phase</th>
                  <th class="col-form">Formulaire</th>
                  <th class="col-aff"><i class="ti ti-toggle-right"></i></th>
                  <th class="col-status">Statut</th>
                  <th class="col-date">Début<small>≥ début préc.</small></th>
                  <th class="col-date">Fin<small>≥ début phase</small></th>
                  <th class="col-days"><i class="ti ti-clock"></i><small>J</small></th>
                  <th v-for="aud in entityAuds(activeEntityId)" :key="'th' + aud.auditeur_id" class="col-aud">
                    <div class="aud-hd" :title="aud.full_name">
                      <div class="aud-av" :class="rCls(aud.role_code || aud.role)">{{ initials(aud.full_name) }}</div>
                      <span class="aud-rc" :class="rCls(aud.role_code || aud.role)">{{ aud.role_code || aud.role }}</span>
                    </div>
                  </th>
                  <th class="col-note"><i class="ti ti-notes"></i></th>
                </tr>
              </thead>
              <tbody>
                <template v-for="grp in checkedByGroup" :key="'g' + grp.phase_type">
                  <tr class="tr-sep">
                    <td :colspan="10 + entityAuds(activeEntityId).length">
                      <span class="sep-icon">{{ ptIcon(grp.phase_type) }}</span>
                      <b :style="'color:' + ptColor(grp.phase_type)">{{ ptLabel(grp.phase_type) }}</b>
                      <span class="sep-cnt muted small">— {{ grp.phases.filter(p => !p.hasSelectedChildren).length }} phase(s)</span>
                    </td>
                  </tr>
                  <template v-for="ph in grp.phases" :key="'p' + ph.id">
                    <tr v-if="ph.hasSelectedChildren" class="tr-locked">
                      <td><i class="ti ti-lock lock-ic"></i></td>
                      <td><code class="mono" :style="'color:' + ptColor(grp.phase_type)">{{ ph.code_full || ph.code }}</code></td>
                      <td :colspan="8 + entityAuds(activeEntityId).length" class="locked-lbl">{{ ph.label }}<span class="locked-hint">— via sous-phases</span></td>
                    </tr>
                    <tr v-else class="tr-ph"
                      :class="{ 'tr-on': isCfgChk(ph.id, activeEntityId), 'tr-sub': ph.level > 1, 'tr-dov': dragOverId === ph.id, 'tr-drag': draggingId === ph.id, 'tr-warn': hasStartErr(ph.id, activeEntityId) }"
                      :draggable="ph.level > 1"
                      @dragstart="onDragStart($event, ph, grp)" @dragover.prevent="onDragOver($event, ph)" @dragleave="onDragLeave" @drop="onDrop($event, ph, grp)"
                    >
                      <td class="td-grip"><i v-if="ph.level > 1" class="ti ti-grip-vertical grip-ic"></i></td>
                      <td class="td-code"><span v-if="ph.level > 1" class="sub-conn">└</span><code class="mono" :style="'color:' + ptColor(grp.phase_type)">{{ ph.code_full || ph.code }}</code></td>
                      <td class="td-lbl">
                        <span class="ph-lbl-txt">{{ ph.label }}</span>
                        <span v-if="prevStart(ph.id, activeEntityId)" class="prev-hint"><i class="ti ti-corner-down-right"></i>≥ {{ fmt(prevStart(ph.id, activeEntityId)) }}</span>
                      </td>
                      <td class="td-form">
                        <template v-if="ph.form_code && forms[ph.form_code]">
                          <a v-if="forms[ph.form_code].url_path && isCfgChk(ph.id, activeEntityId)" :href="buildFormUrl(forms[ph.form_code].url_path, localMission.id)" target="_blank" class="form-link" :title="forms[ph.form_code].label">
                            <i :class="forms[ph.form_code].icon || 'ti ti-file'"></i><span>{{ ph.form_code }}</span>
                          </a>
                          <span v-else class="form-tag" :title="forms[ph.form_code].label">
                            <i :class="forms[ph.form_code].icon || 'ti ti-file'"></i> {{ ph.form_code }}
                          </span>
                        </template>
                        <span v-else class="muted small">—</span>
                      </td>
                      <td class="td-aff">
                        <label class="tog">
                          <input type="checkbox" :checked="isCfgChk(ph.id, activeEntityId)" :disabled="ph.is_mandatory" @change="toggleEntCheck(ph.id, activeEntityId, $event.target.checked)" />
                          <span class="tog-track"></span>
                        </label>
                      </td>
                      <td class="td-status">
                        <select class="status-sel" :value="getCfg(ph.id, activeEntityId).status || 'pending'" :disabled="!isCfgChk(ph.id, activeEntityId)" @change="setCfg(ph.id, activeEntityId, { status: $event.target.value })">
                          <option value="pending">En attente</option>
                          <option value="in_progress">En cours</option>
                          <option value="completed">Terminé</option>
                          <option value="skipped">Ignoré</option>
                        </select>
                      </td>
                      <td class="td-date" :class="{ 'td-err': hasStartErr(ph.id, activeEntityId) }">
                        <input type="date" class="di" :class="{ 'di-on': isCfgChk(ph.id, activeEntityId), 'di-err': hasStartErr(ph.id, activeEntityId) }"
                          :value="getCfg(ph.id, activeEntityId).planned_start" :min="minStart(ph.id, activeEntityId)" :max="activeEntity?.date_fin"
                          :disabled="!isCfgChk(ph.id, activeEntityId)" @change="onStartChange(ph.id, activeEntityId, $event.target.value)" />
                      </td>
                      <td class="td-date">
                        <input type="date" class="di" :class="{ 'di-on': isCfgChk(ph.id, activeEntityId) }"
                          :value="getCfg(ph.id, activeEntityId).planned_end"
                          :min="getCfg(ph.id, activeEntityId).planned_start || minStart(ph.id, activeEntityId)" :max="activeEntity?.date_fin"
                          :disabled="!isCfgChk(ph.id, activeEntityId)" @change="onEndChange(ph.id, activeEntityId, $event.target.value)" />
                      </td>
                      <td class="td-days">
                        <span v-if="getCfg(ph.id, activeEntityId).planned_start && getCfg(ph.id, activeEntityId).planned_end" class="days-badge" :class="phaseDays(ph.id, activeEntityId) > 0 ? 'db-ok' : 'db-err'">{{ phaseDays(ph.id, activeEntityId) }}j</span>
                        <span v-else class="days-empty">—</span>
                      </td>
                      <td v-for="aud in entityAuds(activeEntityId)" :key="'a' + aud.auditeur_id" class="td-aud" :class="{ 'td-aud-on': isAudChk(ph.id, activeEntityId, aud.auditeur_id) }">
                        <label class="aud-tog" :title="aud.full_name">
                          <input type="checkbox" :checked="isAudChk(ph.id, activeEntityId, aud.auditeur_id)" :disabled="!isCfgChk(ph.id, activeEntityId)" @change="toggleAud(ph.id, activeEntityId, aud.auditeur_id, $event.target.checked)" />
                          <span class="aud-face" :class="isAudChk(ph.id, activeEntityId, aud.auditeur_id) ? 'af-on ' + rCls(aud.role_code || aud.role) : 'af-off'">
                            <i v-if="isAudChk(ph.id, activeEntityId, aud.auditeur_id)" class="ti ti-check"></i>
                          </span>
                        </label>
                      </td>
                      <td class="td-note">
                        <button class="note-btn" :class="{ 'nb-fill': getCfg(ph.id, activeEntityId).notes }" :disabled="!isCfgChk(ph.id, activeEntityId)" @click="openNote(ph.id, activeEntityId)">
                          <i class="ti ti-notes"></i>
                        </button>
                      </td>
                    </tr>
                  </template>
                </template>
                <tr v-if="!checkedByGroup.length">
                  <td :colspan="10 + entityAuds(activeEntityId).length" class="empty-row">Aucune phase sélectionnée — retournez à l'étape 2</td>
                </tr>
                <tr v-if="checkedByGroup.length && activeEntityId" class="tr-total">
                  <td colspan="7"><span class="total-lbl"><i class="ti ti-sum"></i> TOTAL</span></td>
                  <td class="td-date total-dates" colspan="2"><span class="total-range">{{ fmt(totalStartDate(activeEntityId)) }} → {{ fmt(totalEndDate(activeEntityId)) }}</span></td>
                  <td class="td-days total-days-cell"><span class="days-badge db-total">{{ totalJours(activeEntityId) }}j</span></td>
                  <td :colspan="entityAuds(activeEntityId).length + 1" class="total-hint muted small">union des intervalles</td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>
        <div v-else class="no-ent"><i class="ti ti-building"></i> Choisissez une entité ci-dessus</div>
      </div>

    </div><!-- /paf-shell -->

    <!-- ── TOAST ── -->
    <Teleport to="body">
      <Transition name="tf">
        <div v-if="toast.show" class="paf-toast" :class="'toast-' + toast.type">
          <i :class="'ti ' + (toast.type === 'success' ? 'ti-circle-check' : toast.type === 'warning' ? 'ti-alert-triangle' : 'ti-circle-x')"></i>
          {{ toast.message }}
          <button @click="toast.show = false">✕</button>
        </div>
      </Transition>
    </Teleport>

    <!-- ── MODAL NOTE ── -->
    <Teleport to="body">
      <div v-if="noteModal.show" class="modal-bg" @click.self="closeNote">
        <div class="modal-box">
          <div class="modal-hd"><i class="ti ti-notes"></i> Note de phase<button @click="closeNote" class="modal-close">✕</button></div>
          <textarea v-model="noteModal.draft" class="modal-ta" rows="5" placeholder="Saisir une note…"></textarea>
          <div class="modal-ft">
            <button class="act act-ghost sm" @click="closeNote">Annuler</button>
            <button class="act act-primary sm" @click="saveNote"><i class="ti ti-check"></i> OK</button>
          </div>
        </div>
      </div>
    </Teleport>

  </VerticalLayout>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
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
const _hasAssignments = Object.keys(props.assignments || {}).length > 0
const step = ref(props.mission ? (_hasAssignments ? 3 : 2) : 1)

const search      = ref('')
const fStatus     = ref('')
const phaseSearch = ref('')
const loadingData = ref(false)
const showWarnings = ref(false)
const warnings    = ref<string[]>([])

const statusFilters = [
  { v: '', l: 'Tous' }, { v: 'planifiee', l: 'Planifiée' },
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
const dirty         = ref(new Set<string>())
const openGroups    = ref(new Set<string>((props.phases as any[]).map((g: any) => g.phase_type)))
const saving        = ref(false)
const toast         = ref({ show: false, type: 'success', message: '' })
const activeEntityId = ref<number | null>((props.entities as any[])[0]?.id ?? null)
const draggingId    = ref<number | null>(null)
const draggingPhase = ref<any>(null)
const dragOverId    = ref<number | null>(null)
const noteModal     = ref({ show: false, phaseId: null as number | null, entityId: null as number | null, draft: '' })

// ── DDMParam Sync state ──────────────────────────────────────────────────────
const syncing        = ref(false)
const syncResult     = ref<null | {
  success: boolean; updated: number; created: number; skipped: number;
  changes: any[]; message: string; error?: string;
}>(null)
const showSyncResult = ref(false)

// ── COMPUTED ────────────────────────────────────────────────────────────────
const filteredMissions = computed(() => {
  const q = search.value.trim().toLowerCase()
  return (props.allMissions as any[]).filter(m => {
    const mq = !q || [m.code_mission, m.libelle, m.entities_list].some((s: any) => String(s || '').toLowerCase().includes(q))
    return mq && (!fStatus.value || m.status === fStatus.value)
  })
})
const totalPhases = computed(() => { let n = 0; for (const g of localPhases.value) for (const p of g.phases) { n++; n += p.children?.length || 0 }; return n })
const pct = computed(() => totalPhases.value ? Math.round(checkedPhaseIds.value.size / totalPhases.value * 100) : 0)
const activeEntity = computed(() => localEntities.value.find(e => e.id === activeEntityId.value) || null)
const formsList = computed(() => Object.values(forms.value).sort((a: any, b: any) => { if (a.phase_num !== b.phase_num) return a.phase_num - b.phase_num; if (a.level !== b.level) return a.level - b.level; return a.sort_order - b.sort_order }))
const filteredPhaseGroups = computed(() => {
  const q = phaseSearch.value.trim().toLowerCase()
  if (!q) return localPhases.value
  return localPhases.value.map(g => ({ ...g, phases: g.phases.map((p: any) => ({ ...p, children: (p.children || []).filter((c: any) => c.label.toLowerCase().includes(q) || (c.code_full || c.code).toLowerCase().includes(q)) })).filter((p: any) => p.label.toLowerCase().includes(q) || (p.code_full || p.code).toLowerCase().includes(q) || p.children.length > 0) })).filter(g => g.phases.length > 0)
})
const checkedByGroup = computed(() => {
  const out: any[] = []
  for (const g of localPhases.value) {
    const rows: any[] = []
    for (const p of g.phases) {
      const kids = (p.children || []).filter((c: any) => checkedPhaseIds.value.has(c.id))
      if (kids.length) { rows.push({ ...p, hasSelectedChildren: true, level: p.level || 1, _parentId: null }); kids.forEach((c: any) => rows.push({ ...c, hasSelectedChildren: false, level: 2, _parentId: p.id })) }
      else if (checkedPhaseIds.value.has(p.id)) rows.push({ ...p, hasSelectedChildren: false, level: p.level || 1, _parentId: null })
    }
    if (rows.length) out.push({ phase_type: g.phase_type, phases: rows })
  }
  return out
})
const allChkPhases = computed(() => checkedByGroup.value.flatMap(g => g.phases.filter((p: any) => !p.hasSelectedChildren)))

// ── INIT assignments ─────────────────────────────────────────────────────────
function initPh(id: number) { if (!entCfg[id]) entCfg[id] = {} }

for (const [k, v] of Object.entries(props.assignments || {})) {
  const [p, e] = (k as string).split('_').map(Number)
  if (!p || !e) continue
  checkedPhaseIds.value.add(p); initPh(p)
  const vv = v as any
  const audIds = Array.isArray(vv.auditeur_ids) ? vv.auditeur_ids.map(Number) : (Array.isArray(vv.auditeurs) ? vv.auditeurs.map((a: any) => Number(a.auditeur_id ?? a)) : [])
  entCfg[p][e] = { checked: true, status: vv.status || 'pending', planned_start: vv.planned_start || null, planned_end: vv.planned_end || null, notes: vv.notes || null, auditeurs: audIds }
}

onMounted(() => {
  forceMandatory()
  if (step.value === 3) {
    for (const id of checkedPhaseIds.value) { initPh(id); for (const e of localEntities.value) { if (!entCfg[id][e.id]) entCfg[id][e.id] = { checked: false, status: 'pending', planned_start: null, planned_end: null, notes: null, auditeurs: [] } } }
    if (!activeEntityId.value && localEntities.value.length) activeEntityId.value = localEntities.value[0].id
  }
  if (Object.keys(forms.value).length > 0) {
    const sample = Object.values(forms.value)[0] as any
    formsSource.value = sample?.route_name ? 'ddmparam (direct)' : 'Session utilisateur'
  }
})

// ── WATCHERS ─────────────────────────────────────────────────────────────────
watch(() => props.entities, v => { localEntities.value = [...(v as any[])]; if (!activeEntityId.value && (v as any[]).length) activeEntityId.value = (v as any[])[0].id }, { deep: true })
watch(() => props.auditeurs, v => { localAuds.value = normAud(v) }, { deep: true })
watch(() => props.phases, v => { localPhases.value = [...(v as any[])]; openGroups.value = new Set((v as any[]).map((g: any) => g.phase_type)) }, { deep: true })
watch(() => props.mission, v => { localMission.value = v ? { ...v } : {}; if (v) step.value = 2 })
watch(() => props.forms, v => { forms.value = (v as any) || {} }, { deep: true })

// ── NAVIGATION ────────────────────────────────────────────────────────────────
function tryGoStep(n: number) { if (n < step.value) { step.value = n; return }; if (n === 2 && !selectedMission.value) return; if (n === 3 && !checkedPhaseIds.value.size) return; if (n === 3) goStep3(); else step.value = n }
function pickMission(m: any) { selectedMission.value = m }
function pageUrl(id: number) { return window.location.pathname.split('?')[0] + '?mission_id=' + id }
function goStep3() {
  for (const id of checkedPhaseIds.value) { initPh(id); for (const e of localEntities.value) { if (!entCfg[id][e.id]) entCfg[id][e.id] = { checked: false, status: 'pending', planned_start: null, planned_end: null, notes: null, auditeurs: [] } } }
  forceMandatory()
  if (!activeEntityId.value && localEntities.value.length) activeEntityId.value = localEntities.value[0].id
  step.value = 3
}
function selectEntity(eid: number) { activeEntityId.value = eid }

// ── PHASES SELECTION ─────────────────────────────────────────────────────────
function togglePhaseCheck(p: any, checked: boolean) { if (p.is_mandatory) return; const s = new Set(checkedPhaseIds.value); if (checked) { s.add(p.id); initPh(p.id) } else s.delete(p.id); checkedPhaseIds.value = s }
function toggleGroupCheck(g: any, checked: boolean) { const s = new Set(checkedPhaseIds.value); for (const p of g.phases) { if (!p.is_mandatory || checked) { if (checked) { s.add(p.id); initPh(p.id) } else s.delete(p.id) }; for (const c of (p.children || [])) { if (!c.is_mandatory || checked) { if (checked) { s.add(c.id); initPh(c.id) } else s.delete(c.id) } } }; checkedPhaseIds.value = s }
function checkAllPhases() { const s = new Set<number>(); for (const g of localPhases.value) for (const p of g.phases) { s.add(p.id); initPh(p.id); for (const c of (p.children || [])) { s.add(c.id); initPh(c.id) } }; checkedPhaseIds.value = s }
function uncheckAllPhases() { const s = new Set<number>(); for (const g of localPhases.value) for (const p of g.phases) { if (p.is_mandatory) s.add(p.id); for (const c of (p.children || [])) if (c.is_mandatory) s.add(c.id) }; checkedPhaseIds.value = s }
function toggleGrp(pt: string) { const s = new Set(openGroups.value); s.has(pt) ? s.delete(pt) : s.add(pt); openGroups.value = s }
function grpAllChk(g: any) { return g.phases.every((p: any) => checkedPhaseIds.value.has(p.id) && (p.children || []).every((c: any) => checkedPhaseIds.value.has(c.id))) }
function grpPartialChk(g: any) { return !grpAllChk(g) && g.phases.some((p: any) => checkedPhaseIds.value.has(p.id) || (p.children || []).some((c: any) => checkedPhaseIds.value.has(c.id))) }
function cntChkInGrp(g: any) { let n = 0; for (const p of g.phases) { if (checkedPhaseIds.value.has(p.id)) n++; for (const c of (p.children || [])) if (checkedPhaseIds.value.has(c.id)) n++ }; return n }
function cntGrp(g: any) { let n = 0; for (const p of g.phases) { n++; n += p.children?.length || 0 }; return n }
function forceMandatory() {
  for (const g of localPhases.value) for (const p of g.phases) {
    const force = (ph: any) => {
      if (!ph.is_mandatory) return
      const s = new Set(checkedPhaseIds.value); s.add(ph.id); checkedPhaseIds.value = s; initPh(ph.id)
      for (const e of localEntities.value) { if (!entCfg[ph.id][e.id]) entCfg[ph.id][e.id] = { checked: true, status: 'pending', planned_start: null, planned_end: null, notes: null, auditeurs: [] }; else entCfg[ph.id][e.id].checked = true }
    }
    force(p); for (const c of (p.children || [])) force(c)
  }
}

// ── CONFIG ENTITÉ ─────────────────────────────────────────────────────────────
function getCfg(pid: number, eid: number) { return entCfg[pid]?.[eid] || { checked: false, status: 'pending', planned_start: null, planned_end: null, notes: null, auditeurs: [] } }
function setCfg(pid: number, eid: number, patch: any) { initPh(pid); if (!entCfg[pid][eid]) entCfg[pid][eid] = { checked: false, status: 'pending', planned_start: null, planned_end: null, notes: null, auditeurs: [] }; Object.assign(entCfg[pid][eid], patch); const d = new Set(dirty.value); d.add(`${pid}_${eid}`); dirty.value = d }
function isCfgChk(pid: number, eid: number) { return !!getCfg(pid, eid).checked }
function toggleEntCheck(pid: number, eid: number, checked: boolean) { if (allChkPhases.value.find((p: any) => p.id === pid)?.is_mandatory) return; setCfg(pid, eid, { checked }) }

// ── AUDITEURS ─────────────────────────────────────────────────────────────────
function entityAuds(eid: number | null) { if (!eid) return []; const list = localAuds.value[String(eid)] || []; const ord: Record<string, number> = { DM: 1, CM: 2, AS: 3, AJ: 4 }; return [...list].sort((a: any, b: any) => (ord[a.role_code ?? a.role] ?? 9) - (ord[b.role_code ?? b.role] ?? 9)) }
function isAudChk(pid: number, eid: number, audId: number) { return (getCfg(pid, eid).auditeurs || []).includes(Number(audId)) }
function toggleAud(pid: number, eid: number, audId: number, checked: boolean) { const list = [...(getCfg(pid, eid).auditeurs || [])].map(Number); const id = Number(audId), idx = list.indexOf(id); if (checked && idx === -1) list.push(id); if (!checked && idx !== -1) list.splice(idx, 1); setCfg(pid, eid, { auditeurs: list }) }
function selectAllAuds(eid: number) { const auds = entityAuds(eid).map((a: any) => Number(a.auditeur_id)); if (!auds.length) return; for (const ph of allChkPhases.value) { if (isCfgChk(ph.id, eid)) setCfg(ph.id, eid, { auditeurs: [...auds] }) }; showToast('Tous les auditeurs assignés.', 'success') }

// ── DATES ─────────────────────────────────────────────────────────────────────
function prevStart(pid: number, eid: number): string | null { const phases = allChkPhases.value; const idx = phases.findIndex((p: any) => p.id === pid); for (let i = idx - 1; i >= 0; i--) { const c = getCfg(phases[i].id, eid); if (c.checked && c.planned_start) return c.planned_start }; return null }
function minStart(pid: number, eid: number): string | null { const entityMin = activeEntity.value?.date_debut || null; const prev = prevStart(pid, eid); if (!entityMin && !prev) return null; if (!entityMin) return prev; if (!prev) return entityMin; return prev >= entityMin ? prev : entityMin }
function onStartChange(pid: number, eid: number, value: string) {
  const floor = minStart(pid, eid); const validated = (floor && value && value < floor) ? floor : (value || null)
  const patch: any = { planned_start: validated }; const cfg = getCfg(pid, eid)
  if (cfg.planned_end && validated && cfg.planned_end < validated) patch.planned_end = null
  setCfg(pid, eid, patch)
  const phases = allChkPhases.value; const idx = phases.findIndex((p: any) => p.id === pid); if (idx === -1 || !validated) return
  let prevS = validated
  for (let i = idx + 1; i < phases.length; i++) { const nc = getCfg(phases[i].id, eid); if (!nc.checked) continue; if (nc.planned_start && nc.planned_start >= prevS) break; if (nc.planned_start) { const np: any = { planned_start: prevS }; if (nc.planned_end && nc.planned_end < prevS) np.planned_end = null; setCfg(phases[i].id, eid, np) }; prevS = getCfg(phases[i].id, eid).planned_start || prevS }
}
function onEndChange(pid: number, eid: number, value: string) { setCfg(pid, eid, { planned_end: value || null }) }
function phaseDays(pid: number, eid: number): number { const c = getCfg(pid, eid); if (!c.planned_start || !c.planned_end) return 0; const diff = Math.round((new Date(c.planned_end).getTime() - new Date(c.planned_start).getTime()) / 86400000) + 1; return diff > 0 ? diff : 0 }
function totalJours(eid: number): number { const intervals: { s: string, e: string }[] = []; for (const ph of allChkPhases.value) { const c = getCfg(ph.id, eid); if (!c.checked || !c.planned_start || !c.planned_end || c.planned_end < c.planned_start) continue; intervals.push({ s: c.planned_start, e: c.planned_end }) }; if (!intervals.length) return 0; intervals.sort((a, b) => a.s < b.s ? -1 : 1); const merged: { s: string, e: string }[] = [{ ...intervals[0] }]; for (let i = 1; i < intervals.length; i++) { const cur = intervals[i], last = merged[merged.length - 1]; if (cur.s <= last.e) { if (cur.e > last.e) last.e = cur.e } else merged.push({ ...cur }) }; let total = 0; for (const seg of merged) total += Math.round((new Date(seg.e).getTime() - new Date(seg.s).getTime()) / 86400000) + 1; return total }
function totalStartDate(eid: number): string | null { let min: string | null = null; for (const ph of allChkPhases.value) { const c = getCfg(ph.id, eid); if (!c.checked || !c.planned_start) continue; if (!min || c.planned_start < min) min = c.planned_start }; return min }
function totalEndDate(eid: number): string | null { let max: string | null = null; for (const ph of allChkPhases.value) { const c = getCfg(ph.id, eid); if (!c.checked || !c.planned_end) continue; if (!max || c.planned_end > max) max = c.planned_end }; return max }
function entityDateErrors(eid: number | null) { if (!eid) return []; const entity = localEntities.value.find(e => e.id === eid); const errs: any[] = []; let prevS: string | null = null; for (const ph of allChkPhases.value) { const c = getCfg(ph.id, eid); if (!c.checked) continue; if (prevS && c.planned_start && c.planned_start < prevS) errs.push({ key: `${ph.id}_${eid}_start`, msg: `"${ph.label}" : début < début précédent` }); if (c.planned_start && c.planned_end && c.planned_end < c.planned_start) errs.push({ key: `${ph.id}_${eid}_end`, msg: `"${ph.label}" : fin < début` }); if (c.planned_end && entity?.date_fin && c.planned_end > entity.date_fin) errs.push({ key: `${ph.id}_${eid}_over`, msg: `"${ph.label}" : dépasse la période` }); if (c.planned_start) prevS = c.planned_start }; return errs }
function hasStartErr(pid: number, eid: number) { return entityDateErrors(eid).some(e => e.key === `${pid}_${eid}_start`) }
function entityProgress(eid: number) { const a = allChkPhases.value; if (!a.length) return 0; return Math.round(a.filter((p: any) => getCfg(p.id, eid).checked).length / a.length * 100) }
function entityHasDirty(eid: number) { return [...dirty.value].some(k => k.endsWith('_' + eid)) }
function cascadeEntity(eid: number) { const entity = localEntities.value.find(e => e.id === eid); let prevS: string | null = entity?.date_debut || null; for (const ph of allChkPhases.value) { const c = getCfg(ph.id, eid); if (!c.checked) continue; if (prevS && (!c.planned_start || c.planned_start < prevS)) { const np: any = { planned_start: prevS }; if (c.planned_end && c.planned_end < prevS) np.planned_end = null; setCfg(ph.id, eid, np) }; prevS = getCfg(ph.id, eid).planned_start || prevS }; showToast('Dates alignées (cascade).', 'success') }
function clearEntity(eid: number) { for (const ph of allChkPhases.value) if (getCfg(ph.id, eid).checked) setCfg(ph.id, eid, { planned_start: null, planned_end: null, auditeurs: [], notes: null, status: 'pending' }); showToast('Entité réinitialisée.', 'warning') }
function buildFormUrl(urlPath: string, missionId: number): string { if (!urlPath) return '#'; const sep = urlPath.includes('?') ? '&' : '?'; return urlPath + sep + 'mission_id=' + missionId }

// ── DRAG & DROP ───────────────────────────────────────────────────────────────
function canDrop(t: any) { if (!draggingPhase.value) return false; return draggingPhase.value.level > 1 && t.level > 1 && draggingPhase.value._parentId === t._parentId }
function onDragStart(e: DragEvent, ph: any, _g: any) { if (ph.level <= 1) { e.preventDefault(); return }; draggingId.value = ph.id; draggingPhase.value = ph; e.dataTransfer!.effectAllowed = 'move'; e.dataTransfer!.setData('text/plain', String(ph.id)) }
function onDragOver(e: DragEvent, t: any) { if (!canDrop(t)) { dragOverId.value = null; return }; e.preventDefault(); dragOverId.value = t.id }
function onDragLeave() { dragOverId.value = null }
function onDrop(e: DragEvent, t: any, g: any) {
  e.preventDefault(); dragOverId.value = null
  if (!canDrop(t)) { draggingId.value = null; draggingPhase.value = null; return }
  const dragId = draggingId.value!, parentId = draggingPhase.value._parentId; draggingId.value = null; draggingPhase.value = null
  const gi = localPhases.value.findIndex((gr: any) => gr.phase_type === g.phase_type); if (gi === -1) return
  const np = JSON.parse(JSON.stringify(localPhases.value)); const parent = np[gi].phases.find((p: any) => p.id === parentId); if (!parent?.children) return
  const fi = parent.children.findIndex((c: any) => c.id === dragId); const ti = parent.children.findIndex((c: any) => c.id === t.id); if (fi === -1 || ti === -1) return
  const [m] = parent.children.splice(fi, 1); parent.children.splice(ti, 0, m); localPhases.value = np; showToast('Ordre mis à jour.', 'success')
}

// ── NOTES ─────────────────────────────────────────────────────────────────────
function openNote(pid: number, eid: number) { noteModal.value = { show: true, phaseId: pid, entityId: eid, draft: getCfg(pid, eid).notes || '' } }
function closeNote() { noteModal.value.show = false }
function saveNote() { const { phaseId, entityId, draft } = noteModal.value; if (phaseId && entityId) setCfg(phaseId, entityId, { notes: draft || null }); closeNote() }

// ── SAUVEGARDE ────────────────────────────────────────────────────────────────
async function saveAll() {
  if (saving.value || !dirty.value.size) return; saving.value = true; warnings.value = []
  const payload = []
  for (const k of dirty.value) {
    const [pid, eid] = k.split('_').map(Number); const c = getCfg(pid, eid)
    const phase = allChkPhases.value.find((p: any) => p.id === pid) as any
    const formCode = phase?.form_code || null; const formUrl = formCode && forms.value[formCode] ? forms.value[formCode].url_path : null
    payload.push({ phase_id: pid, entity_id: eid, checked: c.checked, status: c.status || 'pending', planned_start: c.planned_start || null, planned_end: c.planned_end || null, notes: c.notes || null, auditeur_ids: c.auditeurs || [], form_url: formUrl })
  }
  try {
    const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
    const res = await fetch(saveUrl(localMission.value.id), { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: JSON.stringify({ assignments: payload }) })
    const json = await res.json()
    if (!res.ok) { showToast('Erreur : ' + (json?.error || json?.message || 'Erreur serveur'), 'error'); return }
    dirty.value = new Set()
    if (json.warnings?.length) { warnings.value = json.warnings; showWarnings.value = true; showToast(`${json.upserted} sauvegardé(s) — ${json.warnings.length} avertissement(s).`, 'warning') }
    else showToast(`${json.upserted} affectation(s) sauvegardée(s), ${json.deleted} supprimée(s).`, 'success')
  } catch (e: any) { showToast('Erreur réseau : ' + e.message, 'error') }
  finally { saving.value = false }
}

// ── SYNC DDMPARAM ─────────────────────────────────────────────────────────────
async function syncPhasesFromDdm() {
  if (!localMission.value?.mission_type_id) { showToast('Aucun type de mission associé.', 'warning'); return }
  syncing.value = true; syncResult.value = null
  try {
    const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
    const res  = await fetch(`/m/audit.core/api/mission-phases/sync-labels/${localMission.value.mission_type_id}`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } })
    const json = await res.json()
    syncResult.value   = json; showSyncResult.value = true
    if (json.success) {
      showToast(json.message, (json.updated > 0 || json.created > 0) ? 'success' : 'warning')
      if (json.updated > 0 || json.created > 0) await reloadPhases()
    } else { showToast(json.error || json.message || 'Erreur sync', 'error') }
  } catch (e: any) { showToast('Erreur réseau : ' + e.message, 'error') }
  finally { syncing.value = false }
}

async function reloadPhases() {
  if (!localMission.value?.mission_type_id) return; loadingData.value = true
  try {
    const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
    const res  = await fetch(`/m/audit.core/api/mission-phases/by-type/${localMission.value.mission_type_id}`, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf } })
    const json = await res.json()
    if (json.success && json.phases) { localPhases.value = json.phases; openGroups.value = new Set(json.phases.map((g: any) => g.phase_type)); showToast('Phases rechargées.', 'success') }
    else window.location.reload()
  } catch { window.location.reload() }
  finally { loadingData.value = false }
}

// ── HELPERS UI ────────────────────────────────────────────────────────────────
let _tt: ReturnType<typeof setTimeout>
function showToast(msg: string, type = 'success') { toast.value = { show: true, type, message: msg }; clearTimeout(_tt); _tt = setTimeout(() => { toast.value.show = false }, 5000) }
function fmt(d?: string) { if (!d) return '—'; try { const [y, m, dd] = d.split('-'); return `${dd}/${m}/${y}` } catch { return d } }
function dateDiffDays(a: string, b: string) { return Math.round((new Date(b).getTime() - new Date(a).getTime()) / 86400000) + 1 }
function stLbl(s: string) { return ({ planifiee: 'Planifiée', en_cours: 'En cours', terminee: 'Terminée', annulee: 'Annulée' } as any)[s] || s }
function stChip(s: string) { return ({ planifiee: 'ca', en_cours: 'cb', terminee: 'cg', annulee: 'cr' } as any)[s] || 'cm' }
function ptColor(t: string) { return ({ PREPARATION: '#7C3AED', VERIFICATION: '#0369A1', CONCLUSION: '#059669', SUIVI: '#D97706', AUTRE: '#64748B' } as any)[t] || '#94a3b8' }
function ptIcon(t: string) { return ({ PREPARATION: '⚙', VERIFICATION: '🔍', CONCLUSION: '📋', SUIVI: '📊', AUTRE: '•' } as any)[t] || '•' }
function ptLabel(t: string) { return ({ PREPARATION: 'Préparation', VERIFICATION: 'Vérification', CONCLUSION: 'Conclusion', SUIVI: 'Suivi', AUTRE: 'Autre' } as any)[t] || (t || 'Autre') }
function rCls(r: string) { return ({ DM: 'r-dm', CM: 'r-cm', AS: 'r-as', AJ: 'r-aj' } as any)[r] || 'r-oth' }
function initials(full: string) { if (!full) return '?'; const p = full.trim().split(/\s+/); return (p.length === 1 ? (p[0][0] || '?') : (p[0][0] + p[p.length - 1][0])).toUpperCase() }
</script>

<style scoped>
:root { --b:#2563eb;--g:#059669;--a:#d97706;--r:#dc2626;--bd:#e2e8f0;--sf:#f8fafc;--ink:#0f172a;--m:#64748b; }
.paf-shell{display:flex;flex-direction:column;height:calc(100vh - 68px);overflow:hidden;border-radius:8px;border:1px solid #e2e8f0;background:#f8fafc;}
.paf-bar{display:flex;align-items:center;gap:10px;padding:6px 12px;background:#fff;border-bottom:1px solid #e2e8f0;flex-shrink:0;min-height:46px;}
.paf-steps{display:flex;gap:3px;flex-shrink:0;}
.step-pill{display:flex;align-items:center;gap:5px;padding:3px 10px 3px 3px;border-radius:20px;border:1px solid #e2e8f0;background:#f8fafc;font-size:.7rem;font-weight:700;color:#64748b;cursor:pointer;transition:all .15s;}
.step-pill:hover{border-color:#bfdbfe;color:#2563eb;}.step-pill.active{background:#2563eb;color:#fff;border-color:#2563eb;}.step-pill.done{background:#ecfdf5;color:#059669;border-color:#6ee7b7;}
.sp-num{width:18px;height:18px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:800;}
.step-pill.done .sp-num{background:#059669;color:#fff;}
.paf-mission-info{display:flex;align-items:center;gap:6px;padding-left:10px;border-left:1px solid #e2e8f0;flex:1;min-width:0;overflow:hidden;}
.mi-color-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}.mi-code{font-family:monospace;font-size:.68rem;font-weight:800;background:#dbeafe;color:#1d4ed8;padding:2px 7px;border-radius:4px;flex-shrink:0;}
.mi-lib{font-size:.76rem;font-weight:600;color:#334155;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.mi-badge{font-size:.62rem;font-weight:700;background:#f1f5f9;color:#64748b;padding:1px 6px;border-radius:10px;white-space:nowrap;flex-shrink:0;}
.mi-count{font-size:.68rem;color:#64748b;display:flex;align-items:center;gap:3px;flex-shrink:0;}
.paf-bar-right{display:flex;align-items:center;gap:6px;flex-shrink:0;}
.loading-pill{display:flex;align-items:center;gap:5px;font-size:.72rem;color:#64748b;background:#f1f5f9;padding:3px 8px;border-radius:10px;}
.act{display:inline-flex;align-items:center;gap:4px;padding:4px 11px;border-radius:6px;font-size:.73rem;font-weight:700;border:1px solid transparent;cursor:pointer;transition:all .12s;white-space:nowrap;}
.act.sm{padding:3px 9px;font-size:.68rem;}.act.full{width:100%;justify-content:center;}
.act-ghost{background:#f8fafc;border-color:#e2e8f0;color:#334155;}.act-ghost:hover{background:#eff6ff;border-color:#bfdbfe;color:#2563eb;}
.act-primary{background:#2563eb;color:#fff;}.act-primary:hover:not(:disabled){background:#1d4ed8;}.act-primary:disabled{opacity:.35;cursor:not-allowed;}
.act-save{background:#059669;color:#fff;}.act-save:hover:not(:disabled){background:#047857;}.act-save:disabled{opacity:.35;cursor:not-allowed;}
.act-danger-ghost{background:#fef2f2;border-color:#fecaca;color:#dc2626;}.act-danger-ghost:hover{background:#dc2626;color:#fff;}
/* ── DDMParam Sync ── */
.act-ddm{background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;border:none;}
.act-ddm:hover:not(:disabled){opacity:.88;}.act-ddm:disabled{opacity:.4;cursor:not-allowed;}
.spin-ddm{width:10px;height:10px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:sp .6s linear infinite;display:inline-block;}
.sync-panel{flex-shrink:0;border-bottom:1px solid transparent;font-size:.72rem;animation:slideDown .2s ease;}
@keyframes slideDown{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}
.sp-ok{background:#f0fdf4;border-color:#a7f3d0;}.sp-err{background:#fef2f2;border-color:#fecaca;}
.sp-head{display:flex;align-items:center;gap:8px;padding:6px 12px;flex-wrap:wrap;}
.sp-ok .sp-head i{color:#059669;}.sp-err .sp-head i{color:#dc2626;}
.sp-msg{font-weight:600;color:#1f2d3d;flex-shrink:0;}.sp-stats{display:flex;gap:6px;flex-wrap:wrap;}
.sp-stat{display:inline-flex;align-items:center;gap:3px;padding:1px 7px;border-radius:999px;font-weight:700;font-size:.62rem;}
.sp-stat.up{background:#eff6ff;color:#1d4ed8;}.sp-stat.cr{background:#f0fdf4;color:#059669;}.sp-stat.sk{background:#f8fafc;color:#64748b;}
.sp-close{margin-left:auto;background:none;border:none;color:#94a3b8;cursor:pointer;font-size:.8rem;flex-shrink:0;}
.sp-body{padding:4px 12px 8px;display:flex;flex-direction:column;gap:3px;max-height:160px;overflow-y:auto;border-top:1px dashed #e2e8f0;}
.sp-change{display:flex;align-items:center;gap:6px;flex-wrap:wrap;font-size:.67rem;padding:2px 0;}
.sp-code{font-family:monospace;font-size:.6rem;font-weight:800;background:#ede9fe;color:#7c3aed;padding:1px 5px;border-radius:4px;flex-shrink:0;}
.sp-act{font-size:.58rem;font-weight:800;padding:1px 5px;border-radius:4px;flex-shrink:0;}.sa-cr{background:#d1fae5;color:#065f46;}.sa-up{background:#dbeafe;color:#1d4ed8;}
.sp-lbl{color:#334155;font-weight:600;}.sp-field{display:inline-flex;align-items:center;gap:4px;}.sp-fname{color:#64748b;font-style:italic;}.sp-old{color:#dc2626;font-size:.6rem;}.sp-arrow{color:#94a3b8;}.sp-new{color:#059669;}
/* ── dirty badge ── */
.dirty-badge{display:inline-flex;align-items:center;gap:3px;font-size:.68rem;font-weight:700;background:#fffbeb;color:#d97706;border:1px solid #fde68a;padding:2px 8px;border-radius:20px;}
.db-dot{width:6px;height:6px;border-radius:50%;background:#d97706;animation:blink 1.4s infinite;}
.spin{width:10px;height:10px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:sp .6s linear infinite;display:inline-block;}
@keyframes sp{to{transform:rotate(360deg)}}@keyframes blink{0%,100%{opacity:1}50%{opacity:.2}}
.paf-content{flex:1;overflow:hidden;display:flex;flex-direction:column;}
/* ── Étape 1 ── */
.s1-toolbar{display:flex;align-items:center;gap:8px;padding:7px 12px;background:#fff;border-bottom:1px solid #e2e8f0;flex-shrink:0;flex-wrap:wrap;}
.tb-title{font-size:.78rem;font-weight:700;color:#334155;display:flex;align-items:center;gap:5px;flex-shrink:0;}.tb-title em{font-style:normal;color:#2563eb;}
.tb-search{display:flex;align-items:center;gap:5px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:0 8px;flex:1;min-width:160px;max-width:300px;}
.tb-search input{border:none;background:none;font-size:.76rem;padding:4px 0;flex:1;outline:none;color:#0f172a;}
.tb-pills{display:flex;gap:3px;}.pill{padding:3px 9px;border-radius:12px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-size:.68rem;font-weight:700;cursor:pointer;transition:all .12s;}
.pill:hover{border-color:#bfdbfe;color:#2563eb;}.pill.active{background:#2563eb;color:#fff;border-color:#2563eb;}
.s1-table-wrap{flex:1;overflow-y:auto;}
.s1-footer{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:7px 12px;background:#f0f9ff;border-top:1px solid #bae6fd;flex-shrink:0;}
.sf-info{font-size:.76rem;color:#334155;display:flex;align-items:center;gap:4px;}
.prog-bar-wrap{display:flex;align-items:center;gap:5px;min-width:80px;}.prog-bar{flex:1;height:5px;background:#e2e8f0;border-radius:3px;overflow:hidden;}
.prog-fill{height:100%;background:#059669;border-radius:3px;transition:width .3s;}.prog-pct{font-size:.65rem;font-weight:700;color:#059669;white-space:nowrap;}
/* ── Étape 2 ── */
.s2-layout{flex-direction:row;padding:10px;gap:10px;overflow:hidden;min-height:0;}
.s2-side{width:220px;flex-shrink:0;background:#fff;border-radius:8px;border:1px solid #e2e8f0;display:flex;flex-direction:column;padding:10px;gap:10px;overflow-y:auto;min-height:0;max-height:100%;}
.side-block{display:flex;flex-direction:column;gap:3px;}.side-label{font-size:.57rem;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;font-weight:700;}
.side-code{font-family:monospace;font-size:.78rem;color:#2563eb;font-weight:800;}.side-lib{font-size:.75rem;font-weight:600;color:#334155;}
.side-type-badge{display:inline-flex;align-items:center;gap:5px;color:#fff;padding:.3rem .7rem;border-radius:8px;font-size:.75rem;font-weight:700;}
.badge-count{display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;background:#2563eb;color:#fff;font-size:.58rem;font-weight:800;margin-left:4px;}
.forms-mini-list{display:flex;flex-direction:column;gap:2px;}.form-mini-item{display:flex;align-items:center;gap:4px;font-size:.68rem;padding:2px 4px;border-radius:4px;background:#f8fafc;}
.fmi-child{padding-left:12px;color:#94a3b8;}.fmi-icon{font-size:.72rem;color:#64748b;flex-shrink:0;}.fmi-code{font-family:monospace;font-size:.6rem;font-weight:800;color:#2563eb;flex-shrink:0;}
.fmi-label{font-size:.65rem;color:#334155;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}.form-mini-more{font-size:.65rem;color:#94a3b8;font-style:italic;padding:2px 4px;}
.forms-source-badge{display:flex;align-items:center;gap:3px;font-size:.6rem;color:#94a3b8;margin-top:2px;}
.no-forms-hint{display:flex;align-items:center;gap:5px;font-size:.72rem;color:#d97706;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;padding:6px 8px;}
.side-counter{display:flex;align-items:baseline;gap:3px;}.sc-n{font-size:1.4rem;font-weight:800;color:#2563eb;line-height:1;}.sc-t{font-size:.8rem;color:#94a3b8;}
.sc-bar{height:4px;background:#e2e8f0;border-radius:3px;overflow:hidden;}.sc-fill{height:100%;background:#2563eb;border-radius:3px;transition:width .3s;}
.side-btns{display:flex;gap:4px;}.side-submit{margin-top:auto;}
.s2-groups-wrap{flex:1;display:flex;flex-direction:column;min-height:0;overflow:hidden;}
.s2-groups{flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:5px;min-height:0;padding-bottom:8px;}
.loading-phases{flex:1;display:flex;align-items:center;justify-content:center;gap:8px;color:#64748b;font-size:.82rem;}
.empty-phases{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;color:#94a3b8;}
.phases-search-bar{display:flex;align-items:center;gap:6px;background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:4px 10px;flex-shrink:0;}
.phase-search-input{border:none;background:none;font-size:.78rem;flex:1;outline:none;}
.pg-card{background:#fff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;}
.pg-head{display:flex;align-items:center;gap:6px;padding:6px 10px;background:#f8fafc;cursor:pointer;user-select:none;}
.pg-head input[type=checkbox]{width:13px;height:13px;accent-color:#2563eb;cursor:pointer;flex-shrink:0;}.pg-icon{font-size:.8rem;}
.pg-name{flex:1;font-size:.76rem;font-weight:700;}.pg-cnt{font-size:.65rem;color:#94a3b8;}.pg-arr{color:#94a3b8;font-size:.78rem;}
.pg-body{border-top:1px solid #f1f5f9;}
.ph-row{display:flex;align-items:center;gap:6px;padding:4px 10px;border-bottom:1px solid #f8fafc;cursor:pointer;transition:background .1s;min-height:30px;}
.ph-row:last-child{border-bottom:none;}.ph-row:hover{background:#f8fbff;}.ph-parent{background:#fafafa;}.ph-child{padding-left:26px;background:#fdfdff;}.ph-row.mandatory{background:#fffbeb;}
.ph-row input[type=checkbox]{width:13px;height:13px;accent-color:#2563eb;cursor:pointer;flex-shrink:0;}
.conn{color:#cbd5e1;font-family:monospace;font-size:.72rem;flex-shrink:0;}.ph-code{font-family:monospace;font-size:.6rem;color:#94a3b8;background:#f1f5f9;padding:1px 4px;border-radius:3px;flex-shrink:0;}
.ph-name{flex:1;font-size:.76rem;font-weight:500;color:#334155;}.ph-form-badge{display:inline-flex;align-items:center;gap:3px;font-size:.6rem;font-weight:700;color:#2563eb;background:#dbeafe;padding:1px 5px;border-radius:4px;flex-shrink:0;}
/* ── Étape 3 ── */
.s3-layout{background:#fff;}
.ent-tabs{display:flex;overflow-x:auto;gap:2px;padding:5px 10px 0;background:#f1f5f9;border-bottom:2px solid #e2e8f0;flex-shrink:0;scrollbar-width:none;}
.ent-tabs::-webkit-scrollbar{display:none;}
.et{display:flex;align-items:center;gap:4px;padding:5px 11px;border:1px solid transparent;border-bottom:none;border-radius:6px 6px 0 0;background:rgba(255,255,255,.5);font-size:.7rem;font-weight:600;color:#64748b;cursor:pointer;white-space:nowrap;flex-shrink:0;transition:all .12s;}
.et:hover{background:rgba(255,255,255,.9);color:#334155;}.et.active{background:#fff;color:#2563eb;border-color:#e2e8f0;border-bottom-color:#fff;margin-bottom:-2px;}
.et-dot{width:6px;height:6px;border-radius:50%;flex-shrink:0;}.d-gray{background:#e2e8f0;}.d-green{background:#059669;}.d-amber{background:#d97706;animation:blink 1.4s infinite;}
.et-name{font-weight:700;}.et-sub{font-size:.6rem;color:#94a3b8;font-weight:400;}.et-pct{font-size:.6rem;font-weight:800;color:#059669;}
.ent-bar{display:flex;align-items:center;gap:7px;flex-wrap:wrap;padding:5px 12px;background:#eff6ff;border-bottom:1px solid #bfdbfe;flex-shrink:0;}
.ent-bar-right{margin-left:auto;display:flex;gap:5px;}
.err-pill{display:inline-flex;align-items:center;gap:4px;font-size:.68rem;font-weight:700;color:#dc2626;background:#fef2f2;border:1px solid #fecaca;padding:2px 7px;border-radius:20px;}
.warn-pill{display:inline-flex;align-items:center;gap:4px;font-size:.68rem;font-weight:700;color:#d97706;background:#fffbeb;border:1px solid #fde68a;padding:2px 7px;border-radius:20px;cursor:pointer;}
.warnings-panel{background:#fffbeb;border-bottom:1px solid #fde68a;flex-shrink:0;max-height:120px;overflow-y:auto;}
.wp-head{display:flex;align-items:center;gap:6px;padding:5px 12px;font-size:.75rem;font-weight:700;color:#d97706;}.wp-close{margin-left:auto;background:none;border:none;cursor:pointer;color:#d97706;}
.wp-list{padding:0 12px 8px 30px;font-size:.72rem;color:#92400e;}.wp-list li{margin-bottom:2px;}
.aff-wrap{flex:1;overflow:auto;}
.aff-tbl{width:100%;border-collapse:collapse;font-size:.73rem;}
.aff-tbl thead th{position:sticky;top:0;z-index:5;background:#0f172a;padding:6px 5px;font-size:.58rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#475569;border-bottom:1px solid #1e293b;white-space:nowrap;text-align:center;}
.col-grip{width:20px;}.col-code{width:82px;text-align:left !important;padding-left:8px !important;}.col-lbl{min-width:150px;text-align:left !important;}
.col-form{width:90px;text-align:center !important;}.col-aff{width:40px;}.col-status{width:90px;}.col-date{width:110px;text-align:left !important;}
.col-date small{display:block;font-size:.5rem;color:#d97706;font-weight:400;text-transform:none;letter-spacing:0;}
.col-days{width:38px;text-align:center !important;color:#7c3aed !important;}.col-aud{width:44px;min-width:40px;padding:3px 2px !important;}.col-note{width:30px;}
.aud-hd{display:flex;flex-direction:column;align-items:center;gap:2px;}
.aud-av{width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.5rem;font-weight:800;}
.aud-rc{font-size:.48rem;font-weight:700;padding:0 3px;border-radius:3px;text-transform:uppercase;}
.tr-sep td{padding:4px 8px;background:#f1f5f9;border-bottom:1px solid #e2e8f0;font-size:.7rem;}.sep-icon{margin-right:4px;}.sep-cnt{margin-left:6px;}
.td-status{padding:2px 3px !important;}.status-sel{width:100%;border:1px solid #e2e8f0;border-radius:4px;padding:2px 4px;font-size:.65rem;background:#fafafa;color:#334155;outline:none;}
.status-sel:disabled{opacity:.3;cursor:not-allowed;}
.td-form{text-align:center;padding:3px 4px !important;}
.form-link{display:inline-flex;align-items:center;gap:3px;font-size:.65rem;font-weight:700;color:#2563eb;background:#dbeafe;padding:1px 5px;border-radius:4px;text-decoration:none;transition:all .15s;}
.form-link:hover{background:#2563eb;color:#fff;}.form-tag{display:inline-flex;align-items:center;gap:3px;font-size:.65rem;color:#64748b;background:#f1f5f9;padding:1px 5px;border-radius:4px;}
.td-days{text-align:center;padding:3px 2px !important;white-space:nowrap;}
.days-badge{display:inline-flex;align-items:center;padding:1px 5px;border-radius:8px;font-size:.62rem;font-weight:800;font-family:monospace;}
.db-ok{background:#ede9fe;color:#6d28d9;border:1px solid #ddd6fe;}.db-err{background:#fee2e2;color:#dc2626;border:1px solid #fecaca;}
.db-total{background:#7c3aed;color:#fff;border:none;font-size:.7rem;padding:2px 7px;}.days-empty{font-size:.62rem;color:#cbd5e1;}
.tr-total td{padding:6px 5px;background:#0f172a;border-top:2px solid #334155;vertical-align:middle;}
.total-lbl{display:inline-flex;align-items:center;gap:5px;font-size:.68rem;font-weight:800;color:#94a3b8;letter-spacing:.06em;text-transform:uppercase;}
.total-dates{text-align:left;}.total-range{font-family:monospace;font-size:.68rem;color:#7dd3fc;font-weight:700;}.total-days-cell{text-align:center;}.total-hint{color:#475569;font-size:.6rem;font-style:italic;}
.tr-locked td{padding:4px 5px;background:#f8fafc;border-bottom:1px solid #f1f5f9;vertical-align:middle;}.lock-ic{font-size:.76rem;color:#cbd5e1;}
.locked-lbl{font-size:.73rem;font-weight:600;color:#94a3b8;}.locked-hint{font-size:.63rem;color:#94a3b8;font-style:italic;margin-left:5px;}
.tr-ph td{padding:4px 5px;border-bottom:1px solid #f1f5f9;background:#fff;vertical-align:middle;transition:background .1s;}
.tr-ph:hover td{background:#fafbfe;}.tr-on td{background:#f0fdf4 !important;}.tr-sub td{background:#fcfcff;}.tr-sub:hover td{background:#f2f6ff;}.tr-warn td{background:#fef2f2 !important;}
.tr-dov{outline:2px dashed #2563eb;outline-offset:-1px;}.tr-drag{opacity:.25;}
.td-grip{width:20px;text-align:center;}.grip-ic{color:#cbd5e1;cursor:grab;font-size:.82rem;}.grip-ic:hover{color:#2563eb;}
.td-code{padding-left:6px !important;white-space:nowrap;}.sub-conn{color:#cbd5e1;font-family:monospace;margin-right:2px;font-size:.72rem;}.td-lbl{max-width:180px;}
.ph-lbl-txt{font-size:.75rem;font-weight:500;color:#334155;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.prev-hint{display:flex;align-items:center;gap:2px;font-size:.58rem;color:#d97706;margin-top:1px;}
.td-aff{text-align:center;}.tog{display:inline-flex;cursor:pointer;}.tog input{display:none;}
.tog-track{width:26px;height:14px;background:#e2e8f0;border-radius:7px;position:relative;transition:background .18s;}
.tog-track::after{content:'';position:absolute;top:2px;left:2px;width:10px;height:10px;background:#fff;border-radius:50%;box-shadow:0 1px 2px rgba(0,0,0,.2);transition:transform .18s;}
.tog input:checked + .tog-track{background:#059669;}.tog input:checked + .tog-track::after{transform:translateX(12px);}.tog input:disabled + .tog-track{opacity:.3;cursor:not-allowed;}
.td-date{padding:3px 4px !important;}
.di{width:100%;border:1px solid #e2e8f0;border-radius:4px;padding:2px 4px;font-family:monospace;font-size:.67rem;color:#334155;background:#fafafa;outline:none;transition:border-color .12s,box-shadow .12s;}
.di:focus{border-color:#2563eb;box-shadow:0 0 0 2px rgba(37,99,235,.1);background:#fff;}.di.di-on{border-color:#bfdbfe;color:#1d4ed8;font-weight:700;}.di.di-err{border-color:#fecaca !important;background:#fef2f2 !important;}.di:disabled{opacity:.25;cursor:not-allowed;}
.td-aud{text-align:center;padding:3px 2px !important;}.td-aud-on{background:rgba(5,150,105,.04) !important;}
.aud-tog{display:inline-flex;cursor:pointer;}.aud-tog input{display:none;}
.aud-face{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.56rem;font-weight:700;transition:all .12s;}
.af-off{border:2px dashed #d1d5db;background:#fff;}.af-off:hover{border-color:#9ca3af;}.af-on{border:2px solid;}
.aud-tog input:disabled ~ .aud-face{opacity:.25;cursor:not-allowed;}
.td-note{text-align:center;padding:3px 2px !important;}
.note-btn{width:22px;height:22px;border-radius:4px;border:1px solid #e2e8f0;background:#f8fafc;color:#94a3b8;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:.75rem;transition:all .12s;}
.note-btn:hover:not(:disabled){background:#eff6ff;color:#2563eb;border-color:#bfdbfe;}.nb-fill{background:#fffbeb;color:#d97706;border-color:#fde68a;}.note-btn:disabled{opacity:.2;cursor:not-allowed;}
.no-ent{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#94a3b8;font-size:.82rem;gap:6px;background:#fff;}
.r-dm{background:#fef3c7;color:#b45309;border-color:#fde68a !important;}.r-cm{background:#dbeafe;color:#1d4ed8;border-color:#bfdbfe !important;}
.r-as{background:#d1fae5;color:#065f46;border-color:#6ee7b7 !important;}.r-aj{background:#ede9fe;color:#6d28d9;border-color:#ddd6fe !important;}.r-oth{background:#f1f5f9;color:#64748b;border-color:#e2e8f0 !important;}
.g-table{width:100%;border-collapse:collapse;font-size:.76rem;}
.g-table thead th{position:sticky;top:0;z-index:2;background:#f1f5f9;padding:6px 8px;font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#64748b;border-bottom:1px solid #e2e8f0;white-space:nowrap;}
.g-table tbody tr{border-bottom:1px solid #f8fafc;cursor:pointer;transition:background .1s;}.g-table tbody tr:hover td{background:#f0f7ff;}.g-table tbody tr.sel td{background:#eff6ff;}
.g-table td{padding:5px 8px;vertical-align:middle;color:#334155;}
.ell{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}.max240{max-width:240px;}.max160{max-width:160px;}.mono{font-family:monospace;}.small{font-size:.7rem;}.muted{color:#64748b;}.blue{color:#2563eb;}.green{color:#059669;}.amber{color:#d97706;}
.row-btn{display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:5px;background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;text-decoration:none;transition:all .12s;}
.row-btn:hover{background:#2563eb;color:#fff;}.empty-row{text-align:center;padding:28px !important;color:#94a3b8;font-size:.78rem;}
.chip{display:inline-flex;align-items:center;padding:1px 6px;border-radius:10px;font-size:.6rem;font-weight:700;white-space:nowrap;}
.cb{background:#dbeafe;color:#1d4ed8;}.cg{background:#d1fae5;color:#065f46;}.ca{background:#fef3c7;color:#b45309;}.cr{background:#fee2e2;color:#991b1b;}.cm{background:#f1f5f9;color:#64748b;}.cv{background:#ede9fe;color:#6d28d9;}
.paf-toast{position:fixed;bottom:18px;right:18px;z-index:9999;display:flex;align-items:center;gap:7px;padding:9px 14px;border-radius:8px;font-size:.78rem;font-weight:500;box-shadow:0 8px 30px rgba(0,0,0,.18);max-width:420px;}
.paf-toast button{background:none;border:none;color:inherit;cursor:pointer;margin-left:4px;opacity:.7;}
.toast-success{background:#064e3b;color:#6ee7b7;}.toast-warning{background:#78350f;color:#fcd34d;}.toast-error{background:#7f1d1d;color:#fca5a5;}
.tf-enter-active,.tf-leave-active{transition:all .22s;}.tf-enter-from,.tf-leave-to{opacity:0;transform:translateY(8px);}
.modal-bg{position:fixed;inset:0;background:rgba(10,15,30,.5);backdrop-filter:blur(3px);z-index:1000;display:flex;align-items:center;justify-content:center;}
.modal-box{background:#fff;border-radius:10px;width:420px;max-width:94vw;box-shadow:0 20px 60px rgba(0,0,0,.2);border:1px solid #e2e8f0;overflow:hidden;}
.modal-hd{display:flex;align-items:center;gap:6px;padding:11px 14px;font-size:.84rem;font-weight:700;color:#0f172a;border-bottom:1px solid #f8fafc;}
.modal-close{margin-left:auto;background:none;border:none;color:#64748b;cursor:pointer;}
.modal-ta{width:100%;border:none;padding:12px 14px;font-family:inherit;font-size:.8rem;color:#334155;resize:vertical;outline:none;min-height:100px;}
.modal-ft{display:flex;justify-content:flex-end;gap:7px;padding:9px 14px;border-top:1px solid #f8fafc;}
.aff-wrap::-webkit-scrollbar,.s1-table-wrap::-webkit-scrollbar,.s2-groups::-webkit-scrollbar,.s2-side::-webkit-scrollbar{width:4px;height:4px;}
.aff-wrap::-webkit-scrollbar-track,.s1-table-wrap::-webkit-scrollbar-track,.s2-groups::-webkit-scrollbar-track,.s2-side::-webkit-scrollbar-track{background:transparent;}
.aff-wrap::-webkit-scrollbar-thumb,.s1-table-wrap::-webkit-scrollbar-thumb,.s2-groups::-webkit-scrollbar-thumb,.s2-side::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:2px;}
@media(max-width:768px){.paf-shell{height:100vh;border-radius:0;}.s2-layout{flex-direction:column;overflow-y:auto;}.s2-side{width:100%;flex-direction:row;flex-wrap:wrap;max-height:200px;}.s2-groups-wrap{min-height:300px;}}
</style>