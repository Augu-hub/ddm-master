<template>
    <VerticalLayout>
        <PageTitle title="Créer une Mission d'Audit" subtitle="Nouvelle mission" />

        <!-- Flash messages -->
        <b-alert v-if="$page.props.flash?.success" variant="success" dismissible show class="mb-3 d-flex align-items-center gap-2">
            <i class="ti ti-circle-check fs-16"></i>{{ $page.props.flash.success }}
        </b-alert>
        <b-alert v-if="$page.props.flash?.error" variant="danger" dismissible show class="mb-3 d-flex align-items-center gap-2">
            <i class="ti ti-alert-circle fs-16"></i>{{ $page.props.flash.error }}
        </b-alert>

        <!-- ═══════════════ BARRE DE COMMANDE ═══════════════ -->
        <div class="command-bar mb-4">
            <div class="command-bar-inner d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="exercise-badge">
                        <span v-if="activeExercise" class="d-flex align-items-center gap-2">
                            <span class="exercise-dot"></span>
                            <span class="text-muted fs-12">Exercice</span>
                            <span class="fw-bold text-dark">{{ activeExercise.code }}</span>
                            <span class="text-muted fs-12">— {{ activeExercise.year }}</span>
                        </span>
                        <span v-else class="text-danger fs-12"><i class="ti ti-alert-triangle me-1"></i>Aucun exercice actif</span>
                    </div>

                    <div class="separator-v"></div>

                    <div class="d-flex align-items-center gap-2" v-if="generatedCode !== '—'">
                        <span class="text-muted fs-11 text-uppercase fw-semibold">Code mission</span>
                        <code class="mission-code-badge">{{ generatedCode }}</code>
                        <span
                            v-if="selectedTypeFull"
                            class="type-chip"
                            :style="{ background: hexToRgba(selectedTypeFull.audit_color, .12), color: selectedTypeFull.audit_color }"
                        >
                            <i :class="selectedTypeFull.audit_icon" class="me-1"></i>{{ selectedTypeFull.code }}
                        </span>
                        <span
                            v-if="selectedTypeFull && !selectedTypeFull.is_synced"
                            class="sync-warning-badge"
                            title="Ce type de mission n'est pas encore resynchronisé avec le référentiel central (ddmparam). Les libellés/couleurs affichés proviennent déjà de ddmparam, mais la base tenant reste à mettre à jour."
                        >
                            <i class="ti ti-refresh-alert me-1"></i>Non synchronisé
                        </span>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <span class="status-pill" :class="formIsValid ? 'status-ready' : 'status-incomplete'">
                        <i :class="formIsValid ? 'ti ti-check' : 'ti ti-dots'" class="me-1"></i>
                        {{ formIsValid ? 'Prêt' : 'Incomplet' }}
                    </span>
                    <b-button variant="light" size="sm" class="btn-icon-text" @click="resetForm">
                        <i class="ti ti-refresh me-1"></i>Réinitialiser
                    </b-button>
                    <b-button variant="primary" size="sm" class="btn-save" :disabled="form.processing || !formIsValid" @click="submit">
                        <b-spinner v-if="form.processing" small class="me-1"></b-spinner>
                        <i v-else class="ti ti-device-floppy me-1"></i>
                        {{ form.processing ? 'Enregistrement...' : 'Enregistrer' }}
                    </b-button>
                </div>
            </div>
        </div>

        <!-- ═══════════════ STEPPER ═══════════════ -->
        <div class="stepper-row mb-4">
            <div
                v-for="(s, i) in steps" :key="i"
                class="stepper-item"
                :class="{
                    'step-done':    completedStep > i,
                    'step-active':  completedStep === i,
                    'step-pending': completedStep < i,
                }"
            >
                <div class="step-indicator">
                    <i v-if="completedStep > i" class="ti ti-check"></i>
                    <span v-else>{{ i + 1 }}</span>
                </div>
                <div class="step-info">
                    <span class="step-label">{{ s.label }}</span>
                    <span class="step-hint">{{ s.hint }}</span>
                </div>
                <div v-if="i < steps.length - 1" class="step-connector"></div>
            </div>
        </div>

        <!-- ═══════════════ CORPS PRINCIPAL ═══════════════ -->
        <b-row class="g-3">

            <!-- ── Colonne gauche ──────────────────────────── -->
            <b-col lg="7">

                <!-- Carte 1 : Référence source -->
                <div class="form-section mb-3">
                    <div class="section-header">
                        <span class="section-number">01</span>
                        <div>
                            <h5 class="section-title">Référence source</h5>
                            <p class="section-subtitle">Mission existante servant de base</p>
                        </div>
                        <b-badge v-if="form.fpm_number" variant="null" class="ms-auto bg-primary-subtle text-primary fs-11 font-monospace">
                            {{ form.fpm_number }}
                        </b-badge>
                    </div>
                    <div class="section-body">
                        <div
                            class="source-picker"
                            :class="form.fpm_number ? 'source-picker--selected' : ''"
                            @click="openModal"
                        >
                            <i class="ti ti-search source-picker-icon"></i>
                            <div class="source-picker-text">
                                <span v-if="!form.fpm_number" class="text-muted fs-13">
                                    Parcourir les missions disponibles…
                                </span>
                                <span v-else class="fs-13">
                                    <strong class="text-primary">{{ form.fpm_number }}</strong>
                                    <span class="text-muted ms-2 fs-12">{{ truncate(loadedMissionTitle, 60) }}</span>
                                </span>
                            </div>
                            <button v-if="form.fpm_number" class="source-clear" @click.stop="clearSource">
                                <i class="ti ti-x"></i>
                            </button>
                            <i v-else class="ti ti-chevron-right text-muted"></i>
                        </div>
                    </div>
                </div>

                <!-- Carte 2 : Objectif -->
                <div class="form-section mb-3" :class="{ 'section-locked': !form.fpm_number }">
                    <div class="section-header">
                        <span class="section-number">02</span>
                        <div>
                            <h5 class="section-title">Objectif de la mission</h5>
                            <p class="section-subtitle">But principal visé par cette mission</p>
                        </div>
                        <span v-if="!form.fpm_number" class="ms-auto lock-badge">
                            <i class="ti ti-lock me-1"></i>Étape 1 requise
                        </span>
                    </div>
                    <div class="section-body">
                        <b-form-textarea
                            v-model.trim="form.objective"
                            rows="3"
                            class="custom-textarea fs-13"
                            placeholder="Décrire l'objectif principal de cette mission d'audit…"
                            :disabled="!form.fpm_number"
                            @input="onObjectiveInput"
                        />
                        <div class="char-count">{{ form.objective?.length || 0 }} caractères</div>
                    </div>
                </div>

                <!-- Carte 3 : Intitulé + IA -->
                <div class="form-section mb-3" :class="{ 'section-locked': !form.objective }">
                    <div class="section-header">
                        <span class="section-number">03</span>
                        <div>
                            <h5 class="section-title"><i class="ti ti-sparkles text-primary me-1"></i>Intitulé de la mission</h5>
                            <p class="section-subtitle">Saisir ou générer via IA</p>
                        </div>
                        <b-button
                            v-if="form.objective"
                            variant="null" size="sm"
                            class="ms-auto ai-btn"
                            :disabled="loadingTitles"
                            @click="generateTitles"
                        >
                            <b-spinner v-if="loadingTitles" small class="me-1"></b-spinner>
                            <i v-else class="ti ti-wand me-1"></i>
                            {{ loadingTitles ? 'Génération…' : 'Générer IA' }}
                        </b-button>
                    </div>
                    <div class="section-body">
                        <!-- Suggestions IA -->
                        <div v-if="aiSugs.length" class="ai-suggestions mb-3">
                            <p class="ai-suggestions-label"><i class="ti ti-robot me-1"></i>Propositions IA — cliquer pour sélectionner</p>
                            <div
                                v-for="(s, i) in aiSugs" :key="i"
                                class="ai-suggestion-item"
                                :class="{ 'ai-suggestion-item--selected': form.title === s }"
                                @click="form.title = s"
                            >
                                <span class="ai-suggestion-num">{{ i + 1 }}</span>
                                <span class="flex-grow-1 fs-13">{{ s }}</span>
                                <i v-if="form.title === s" class="ti ti-check text-primary"></i>
                            </div>
                        </div>
                        <b-form-input
                            v-model.trim="form.title"
                            class="custom-input fs-13"
                            :class="{ 'input-valid': (form.title?.length || 0) > 5, 'is-invalid': form.errors?.title }"
                            placeholder="Saisir ou sélectionner un intitulé…"
                            :disabled="!form.objective"
                        />
                        <div v-if="form.errors?.title" class="invalid-feedback">{{ form.errors.title }}</div>
                    </div>
                </div>

                <!-- Carte 4 : Paramètres -->
                <div class="form-section mb-3" :class="{ 'section-locked': !form.title }">
                    <div class="section-header">
                        <span class="section-number">04</span>
                        <div>
                            <h5 class="section-title">Paramètres de la mission</h5>
                            <p class="section-subtitle">Type, priorité, dates et domaine</p>
                        </div>
                    </div>
                    <div class="section-body">
                        <b-row class="g-2 mb-3">
                            <b-col md="7">
                                <label class="field-label">Type de mission <span class="text-danger">*</span></label>
                                <b-form-select
                                    v-model="form.mission_type_id"
                                    size="sm"
                                    class="custom-select"
                                    :disabled="!form.title"
                                    :class="{ 'is-invalid': form.errors?.mission_type_id }"
                                    @change="onTypeChange"
                                >
                                    <option :value="null">— Sélectionner un type —</option>
                                    <option v-for="t in missionTypes" :key="t.id" :value="t.id">
                                        {{ t.code }} — {{ t.label }}{{ t.audit_type_label ? ` (${t.audit_type_label})` : '' }}
                                    </option>
                                </b-form-select>
                                <div v-if="form.errors?.mission_type_id" class="invalid-feedback">{{ form.errors.mission_type_id }}</div>

                                <!-- Aperçu du type d'audit rattaché (référentiel central ddmparam) -->
                                <div v-if="selectedTypeFull" class="audit-type-preview">
                                    <span
                                        class="audit-type-dot"
                                        :style="{ background: selectedTypeFull.audit_color }"
                                    ></span>
                                    <i :class="selectedTypeFull.audit_icon" class="fs-12"></i>
                                    <span class="fs-12 text-muted">{{ selectedTypeFull.audit_type_label || 'Type d\'audit non rattaché' }}</span>
                                    <span v-if="!selectedTypeFull.audit_type_code" class="fs-11 text-danger ms-1">
                                        <i class="ti ti-alert-triangle"></i> à rattacher côté paramétrage
                                    </span>
                                </div>
                            </b-col>
                            <b-col md="5">
                                <label class="field-label">Priorité</label>
                                <div class="prio-group">
                                    <button
                                        v-for="p in prioOpts" :key="p.v"
                                        type="button"
                                        class="prio-btn"
                                        :class="[form.priority === p.v ? p.active : p.outline]"
                                        @click="form.priority = p.v"
                                        :title="p.l"
                                    >
                                        <i :class="p.i"></i>
                                        <span class="prio-label">{{ p.l }}</span>
                                    </button>
                                </div>
                            </b-col>
                        </b-row>

                        <b-row class="g-2 mb-3">
                            <b-col md="6">
                                <label class="field-label">Domaine</label>
                                <b-form-input v-model.trim="form.domain" size="sm" maxlength="120" placeholder="ex : Achats, RH, Finance…" />
                            </b-col>
                            <b-col md="6">
                                <label class="field-label">Référence document</label>
                                <b-form-input v-model.trim="form.reference_document" size="sm" maxlength="120" placeholder="ex : POL-AUD-2026…" />
                            </b-col>
                        </b-row>

                        <b-row class="g-2 align-items-end">
                            <b-col>
                                <label class="field-label">Date début</label>
                                <b-form-input v-model="form.planned_start_date" type="date" size="sm" @change="calcDur" />
                            </b-col>
                            <b-col>
                                <label class="field-label">Date fin</label>
                                <b-form-input v-model="form.planned_end_date" type="date" size="sm" :min="form.planned_start_date" @change="calcDur" />
                            </b-col>
                            <b-col cols="auto">
                                <label class="field-label">Durée</label>
                                <div class="duration-badge">
                                    <span v-if="form.planned_duration_days">{{ form.planned_duration_days }}<small>j</small></span>
                                    <span v-else class="text-muted">—</span>
                                </div>
                            </b-col>
                        </b-row>
                    </div>
                </div>
            </b-col>

            <!-- ── Colonne droite ──────────────────────────── -->
            <b-col lg="5">

                <!-- Entités : sélection initiale -->
                <div v-if="!validatedEntities.length" class="form-section mb-3">
                    <div class="section-header">
                        <span class="section-number"><i class="ti ti-building"></i></span>
                        <div>
                            <h5 class="section-title">Entités à auditer <span class="text-danger">*</span></h5>
                            <p class="section-subtitle">{{ selectedEntityIds.length }} sélectionnée(s)</p>
                        </div>
                    </div>
                    <div class="section-body">
                        <div class="entity-grid" style="max-height:240px; overflow-y:auto;">
                            <label
                                v-for="e in entities" :key="e.id"
                                class="entity-check-item"
                                :class="{ 'entity-check-item--selected': selectedEntityIds.includes(e.id) }"
                            >
                                <input type="checkbox" :value="e.id" v-model="selectedEntityIds" class="entity-checkbox" />
                                <span class="entity-code">{{ e.code_base }}</span>
                                <span class="entity-name">{{ e.name }}</span>
                            </label>
                            <div v-if="!entities.length" class="empty-state py-3">
                                <i class="ti ti-building-off fs-2 d-block mb-1 opacity-40"></i>
                                <span>Aucune entité disponible</span>
                            </div>
                        </div>
                        <div v-if="selectedEntityIds.length" class="mt-3 d-flex justify-content-end">
                            <b-button variant="success" size="sm" @click="validateEntities">
                                <i class="ti ti-check me-1"></i>Valider {{ selectedEntityIds.length }} entité(s)
                            </b-button>
                        </div>
                    </div>
                </div>

                <!-- Entités validées avec risques -->
                <template v-else>
                    <div v-for="entity in validatedEntities" :key="entity.id" class="form-section mb-3">
                        <div class="entity-validated-header">
                            <label class="d-flex align-items-center gap-2 m-0 flex-grow-1 cursor-pointer">
                                <div class="entity-toggle-wrap">
                                    <input
                                        type="checkbox"
                                        :value="entity.id"
                                        v-model="includedEntityIds"
                                        class="entity-toggle"
                                        @change="toggleEntity(entity.id)"
                                    />
                                    <span class="entity-toggle-track"></span>
                                </div>
                                <span class="fw-bold fs-13">{{ entity.code_base }}</span>
                                <span class="text-muted fs-12">{{ entity.name }}</span>
                            </label>
                            <b-badge variant="null" class="bg-info-subtle text-info fs-11">
                                {{ entityRisksByEntity[entity.id]?.length || 0 }} risque(s)
                            </b-badge>
                        </div>

                        <div v-if="includedEntityIds.includes(entity.id)">
                            <div style="max-height:200px; overflow-y:auto;">
                                <table class="risks-table">
                                    <thead>
                                        <tr>
                                            <th style="width:32px;"></th>
                                            <th>Code</th>
                                            <th>Libellé</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="r in entityRisksByEntity[entity.id]" :key="r.id"
                                            class="risk-row"
                                            :class="{ 'risk-row--selected': form.risk_ids.includes(r.id) }"
                                            @click="toggleRisk(r.id)"
                                        >
                                            <td>
                                                <span class="risk-checkbox" :class="{ 'risk-checkbox--checked': form.risk_ids.includes(r.id) }">
                                                    <i v-if="form.risk_ids.includes(r.id)" class="ti ti-check"></i>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="risk-code-badge me-1">{{ r.code }}</span>
                                                <span class="proc-badge">{{ r.process_code }}</span>
                                            </td>
                                            <td class="fs-12 text-muted">{{ r.label }}</td>
                                        </tr>
                                        <tr v-if="!entityRisksByEntity[entity.id]?.length">
                                            <td colspan="3" class="text-center text-muted py-2 fs-12">Aucun risque</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div v-else class="entity-disabled-msg">
                            <i class="ti ti-eye-off me-1"></i>Entité désactivée
                        </div>
                    </div>

                    <div v-if="form.risk_ids.length" class="risk-summary mb-2">
                        <span><b-badge variant="danger">{{ form.risk_ids.length }}</b-badge> risque(s) sélectionné(s)</span>
                        <a href="#" class="text-danger fs-12" @click.prevent="form.risk_ids = []">
                            <i class="ti ti-trash me-1"></i>Tout effacer
                        </a>
                    </div>

                    <div class="d-flex justify-content-end mb-3">
                        <b-button variant="outline-secondary" size="sm" @click="resetEntities">
                            <i class="ti ti-x me-1"></i>Changer les entités
                        </b-button>
                    </div>
                </template>

                <!-- ═══ Compétences ═══ -->
                <div class="form-section mb-3">
                    <div class="section-header">
                        <span class="section-number"><i class="ti ti-user-star"></i></span>
                        <div>
                            <h5 class="section-title">Compétences nécessaires</h5>
                            <p class="section-subtitle">{{ form.competency_ids.length }} sélectionnée(s)</p>
                        </div>
                        <div class="ms-auto d-flex gap-1 align-items-center">
                            <button v-if="form.competency_ids.length" class="comp-clear-btn" @click="form.competency_ids = []" title="Tout désélectionner">
                                <i class="ti ti-x"></i>
                            </button>
                            <button class="comp-all-btn" @click="selectAllComps" title="Tout sélectionner">
                                <i class="ti ti-list-check"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Recherche rapide compétences -->
                    <div class="px-3 pb-2">
                        <div class="comp-search">
                            <i class="ti ti-search comp-search-icon"></i>
                            <input v-model="compSearch" type="text" placeholder="Filtrer les compétences…" class="comp-search-input" />
                        </div>
                    </div>

                    <div class="comp-list" style="max-height:260px; overflow-y:auto;">
                        <label
                            v-for="c in filteredComps" :key="c.id"
                            class="comp-item"
                            :class="{ 'comp-item--selected': form.competency_ids.includes(c.id) }"
                        >
                            <input
                                type="checkbox"
                                :value="c.id"
                                v-model="form.competency_ids"
                                class="comp-checkbox-native"
                            />
                            <span class="comp-check" :class="{ 'comp-check--on': form.competency_ids.includes(c.id) }">
                                <i v-if="form.competency_ids.includes(c.id)" class="ti ti-check"></i>
                            </span>
                            <span class="comp-code">{{ c.code }}</span>
                            <span class="comp-name">{{ truncate(c.name, 36) }}</span>
                        </label>
                        <div v-if="!filteredComps.length" class="empty-state py-3">
                            <i class="ti ti-certificate fs-2 d-block mb-1 opacity-40"></i>
                            <span>{{ compSearch ? 'Aucune compétence trouvée' : 'Aucune compétence disponible' }}</span>
                        </div>
                    </div>

                    <!-- Récap sélection -->
                    <div v-if="form.competency_ids.length" class="comp-recap">
                        <div class="comp-tags">
                            <span
                                v-for="cid in form.competency_ids" :key="cid"
                                class="comp-tag"
                            >
                                {{ getCompCode(cid) }}
                                <button class="comp-tag-remove" @click.stop="removeComp(cid)"><i class="ti ti-x"></i></button>
                            </span>
                        </div>
                    </div>
                </div>
            </b-col>
        </b-row>

        <!-- ═══════════════ HISTORIQUE ═══════════════ -->
        <div v-if="createdMissions?.length" class="form-section mt-2 mb-4">
            <div class="section-header">
                <span class="section-number"><i class="ti ti-history"></i></span>
                <div>
                    <h5 class="section-title">Missions planifiées <span class="text-muted fw-normal fs-13">({{ createdMissions.length }})</span></h5>
                    <p class="section-subtitle">Missions créées dans cet exercice</p>
                </div>
                <div class="ms-auto" style="min-width:220px;">
                    <div class="comp-search">
                        <i class="ti ti-search comp-search-icon"></i>
                        <input v-model="histSearch" type="text" placeholder="Rechercher…" class="comp-search-input" />
                    </div>
                </div>
            </div>
            <div class="section-body p-0">
                <div style="overflow-x:auto;">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Intitulé</th>
                                <th>N° FPM</th>
                                <th>Domaine</th>
                                <th>Début</th>
                                <th>Fin</th>
                                <th>Dur.</th>
                                <th>Source</th>
                                <th>Priorité</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="m in filteredHistory" :key="m.id">
                                <td><code class="text-primary fs-12">{{ m.code }}</code></td>
                                <td class="fs-12">{{ truncate(m.title, 38) }}</td>
                                <td class="fs-12 text-muted font-monospace">{{ m.fpm_number || '—' }}</td>
                                <td class="fs-12 text-muted">{{ m.domain || '—' }}</td>
                                <td class="fs-12">{{ m.planned_start_date || '—' }}</td>
                                <td class="fs-12">{{ m.planned_end_date || '—' }}</td>
                                <td class="fs-12 text-center">
                                    <span v-if="m.planned_duration_days" class="fw-semibold text-primary">{{ m.planned_duration_days }}j</span>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td>
                                    <span class="hist-badge" :class="m.source === 'SUR DEMANDE' ? 'badge-info' : 'badge-primary'">{{ m.source }}</span>
                                </td>
                                <td>
                                    <span class="hist-badge" :class="prioBadgeCls(m.priority)">{{ m.priority }}</span>
                                </td>
                                <td>
                                    <span class="hist-badge" :class="statusBadgeCls(m.status)">{{ m.status }}</span>
                                </td>
                            </tr>
                            <tr v-if="!filteredHistory.length">
                                <td colspan="10" class="text-center text-muted py-4 fs-13">Aucune mission trouvée</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ═══════════════ MODAL SOURCE ═══════════════ -->
        <teleport to="body">
            <transition name="modal-fade">
                <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
                    <div class="modal-panel">
                        <div class="modal-head">
                            <div class="d-flex align-items-center gap-3">
                                <div class="modal-icon-wrap"><i class="ti ti-search fs-18"></i></div>
                                <div>
                                    <h5 class="mb-0 text-white fs-15">Sélectionner une référence source</h5>
                                    <p class="text-white-50 mb-0 fs-12">Classé par rang de priorité décroissant</p>
                                </div>
                            </div>
                            <button class="modal-close" @click="showModal = false"><i class="ti ti-x"></i></button>
                        </div>

                        <div class="modal-body-area p-3">
                            <b-row class="g-2 mb-3">
                                <b-col cols="8">
                                    <div class="comp-search">
                                        <i class="ti ti-search comp-search-icon"></i>
                                        <input v-model="mSearch" type="text" placeholder="Rechercher par code, titre, objectif…" class="comp-search-input" autofocus />
                                    </div>
                                </b-col>
                                <b-col cols="4">
                                    <b-form-select v-model="mType" size="sm">
                                        <option :value="null">Toutes les sources</option>
                                        <option value="fpm">FPM — Demandes</option>
                                        <option value="Risk">Audit Risk</option>
                                    </b-form-select>
                                </b-col>
                            </b-row>

                            <div class="modal-stats mb-3">
                                <span class="modal-stat"><b-badge variant="info" class="me-1">FPM</b-badge><strong>{{ fpmCount }}</strong> <span class="text-muted">demandes</span></span>
                                <span class="modal-stat"><b-badge variant="success" class="me-1">RISK</b-badge><strong>{{ riskCount }}</strong> <span class="text-muted">missions</span></span>
                                <span class="ms-auto text-muted fs-12">{{ filteredMissions.length }} résultat(s)</span>
                            </div>

                            <div style="max-height:400px; overflow-y:auto; border-radius:8px; border:1px solid #e9ecef;">
                                <table class="modal-table">
                                    <thead>
                                        <tr>
                                            <th style="width:36px;"></th>
                                            <th style="width:52px;">Rang</th>
                                            <th style="width:72px;">Source</th>
                                            <th style="width:90px;">Code</th>
                                            <th>Intitulé / Objectif</th>
                                            <th style="width:60px;">Risques</th>
                                            <th style="width:90px;">Entité(s)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="m in filteredMissions" :key="`${m.source}-${m.id}`"
                                            class="modal-row"
                                            :class="{ 'modal-row--selected': selId === m.id && selSrc === m.source }"
                                            @click="selId = m.id; selSrc = m.source"
                                        >
                                            <td><input type="radio" :checked="selId === m.id && selSrc === m.source" class="form-check-input m-0" /></td>
                                            <td>
                                                <span class="rank-badge" :class="rankBadgeCls(m)">
                                                    {{ m.rank || m.level || m.coefficient || '—' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="src-badge" :class="m.source === 'fpm' ? 'src-fpm' : 'src-risk'">
                                                    {{ m.source?.toUpperCase() }}
                                                </span>
                                            </td>
                                            <td><code class="fs-12">{{ m.code || '—' }}</code></td>
                                            <td>
                                                <p class="mb-0 fs-13">{{ truncate(m.title || m.objective || '', 58) }}</p>
                                                <small v-if="m.title && m.objective" class="text-muted">{{ truncate(m.objective, 52) }}</small>
                                            </td>
                                            <td class="text-center"><b-badge variant="danger" class="fs-11">{{ m.risk_count || 0 }}</b-badge></td>
                                            <td class="fs-12 text-muted">{{ m.entity_ids?.length ? m.entity_ids.join(', ') : (m.entity?.code_base || '—') }}</td>
                                        </tr>
                                        <tr v-if="!filteredMissions.length">
                                            <td colspan="7" class="text-center text-muted py-5 fs-13">
                                                <i class="ti ti-mood-empty fs-1 d-block mb-2 opacity-40"></i>
                                                Aucune mission correspondante
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="modal-foot">
                            <b-button variant="light" size="sm" @click="showModal = false">Annuler</b-button>
                            <b-button variant="primary" size="sm" :disabled="!selId" @click="confirmLoad">
                                <i class="ti ti-check me-1"></i>Charger cette mission
                            </b-button>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>
    </VerticalLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { computed, ref, onMounted } from 'vue'
import axios from 'axios'
import VerticalLayout from '@/layouts/VerticalLayout.vue'
import PageTitle from '@/components/PageTitle.vue'

// ── Props ─────────────────────────────────────────────────────────────
const props = defineProps({
    exercises:       { type: Array,  default: () => [] },
    missionTypes:    { type: Array,  default: () => [] },
    // Référentiel central ddmparam.audit_types (source de vérité pour
    // couleur/icône/libellé — voir MissionController@create)
    auditTypes:      { type: Array,  default: () => [] },
    entities:        { type: Array,  default: () => [] },
    competencies:    { type: Array,  default: () => [] },
    fpmMissions:     { type: Array,  default: () => [] },
    auditMissions:   { type: Array,  default: () => [] },
    risks:           { type: Array,  default: () => [] },
    processes:       { type: Array,  default: () => [] },
    assignments:     { type: Array,  default: () => [] },
    createdMissions: { type: Array,  default: () => [] },
    activeExercise:  { type: Object, default: null },
})

// ── Exercice actif ────────────────────────────────────────────────────
const activeExercise = computed(() =>
    props.activeExercise || props.exercises[0] || null
)

// ── Formulaire Inertia ────────────────────────────────────────────────
const form = useForm({
    audit_exercise_id:     null,
    mission_type_id:       null,
    entity_ids:            [],
    title:                 '',
    objective:             '',
    domain:                '',
    reference_document:    '',
    fpm_number:            '',
    priority:              'moyenne',
    planned_start_date:    '',
    planned_end_date:      '',
    planned_duration_days: 0,
    risk_ids:              [],
    competency_ids:        [],
})

onMounted(() => {
    if (activeExercise.value) form.audit_exercise_id = activeExercise.value.id
})

// ── État local ────────────────────────────────────────────────────────
const showModal          = ref(false)
const mSearch            = ref('')
const mType              = ref(null)
const selId              = ref(null)
const selSrc             = ref(null)
const selectedEntityIds  = ref([])
const validatedEntities  = ref([])
const includedEntityIds  = ref([])
const loadingTitles      = ref(false)
const aiSugs             = ref([])
const loadedMissionTitle = ref('')
const histSearch         = ref('')
const compSearch         = ref('')
const seqCount           = ref(1)
let   titleTimer         = null

// ── Stepper ───────────────────────────────────────────────────────────
const steps = [
    { label: 'Référence',  hint: 'Mission source' },
    { label: 'Objectif',   hint: 'But principal'  },
    { label: 'Intitulé',   hint: 'Généré par IA'  },
    { label: 'Paramètres', hint: 'Type + dates'   },
]

const completedStep = computed(() => {
    if (!form.fpm_number)      return 0
    if (!form.objective)       return 1
    if (!form.title)           return 2
    if (!form.mission_type_id) return 3
    return 4
})

// ── Priorités ─────────────────────────────────────────────────────────
const prioOpts = [
    { v: 'basse',    l: 'Basse',  i: 'ti ti-arrow-narrow-down', active: 'prio-active-success',  outline: 'prio-outline-success'  },
    { v: 'moyenne',  l: 'Moy.',   i: 'ti ti-minus',             active: 'prio-active-warning',  outline: 'prio-outline-warning'  },
    { v: 'haute',    l: 'Haute',  i: 'ti ti-arrow-narrow-up',   active: 'prio-active-orange',   outline: 'prio-outline-orange'   },
    { v: 'critique', l: 'Crit.',  i: 'ti ti-flame',             active: 'prio-active-danger',   outline: 'prio-outline-danger'   },
]

// ── Helpers ───────────────────────────────────────────────────────────
const truncate = (s, n = 30) =>
    s && typeof s === 'string' ? (s.length > n ? s.slice(0, n) + '…' : s) : '—'

const rankBadgeCls = (m) => {
    const v = parseFloat(m.rank || m.coefficient || m.level || 0)
    return v >= 4 ? 'rank-danger' : v >= 2 ? 'rank-warning' : v >= 1 ? 'rank-success' : 'rank-muted'
}

const prioBadgeCls = (p) => ({
    basse:    'badge-success', moyenne: 'badge-warning',
    haute:    'badge-orange',  critique: 'badge-danger',
}[p] || 'badge-secondary')

const statusBadgeCls = (s) => ({
    brouillon: 'badge-warning', planifiée: 'badge-info',
    en_cours:  'badge-primary', terminée:  'badge-success',
    annulée:   'badge-secondary',
}[s] || 'badge-secondary')

const getCompCode = (id) => {
    const c = (props.competencies || []).find(x => x.id === id)
    return c?.code || `#${id}`
}

// Convertit un hex (#RRGGBB) en rgba(...) pour les fonds de badges
const hexToRgba = (hex, alpha = 1) => {
    if (!hex) return `rgba(148,163,184,${alpha})` // gris neutre si pas de couleur
    const h = hex.replace('#', '')
    const bigint = parseInt(h.length === 3 ? h.split('').map(c => c + c).join('') : h, 16)
    const r = (bigint >> 16) & 255, g = (bigint >> 8) & 255, b = bigint & 255
    return `rgba(${r}, ${g}, ${b}, ${alpha})`
}

// ── Computeds ─────────────────────────────────────────────────────────
const competenciesSafe = computed(() => props.competencies || [])

const filteredComps = computed(() => {
    if (!compSearch.value.trim()) return competenciesSafe.value
    const t = compSearch.value.toLowerCase()
    return competenciesSafe.value.filter(c =>
        (c.code || '').toLowerCase().includes(t) ||
        (c.name || '').toLowerCase().includes(t)
    )
})

// Types de mission déjà enrichis côté contrôleur avec le référentiel
// central (audit_type_label/audit_color/audit_icon/is_synced) — voir
// MissionController@create. On garde un fallback local par sécurité si
// jamais le back envoie une version non enrichie.
const auditTypesByCode = computed(() =>
    Object.fromEntries((props.auditTypes || []).map(a => [a.code, a]))
)

const selectedTypeFull = computed(() => {
    if (!form.mission_type_id) return null
    const t = (props.missionTypes || []).find(x => x.id === form.mission_type_id)
    if (!t) return null
    const ref = t.audit_type_code ? auditTypesByCode.value[t.audit_type_code] : null
    return {
        ...t,
        audit_type_label: ref?.label ?? t.audit_type_label,
        audit_color:      ref?.color ?? t.audit_color ?? '#94A3B8',
        audit_icon:       ref?.icon  ?? t.audit_icon  ?? 'ti ti-clipboard-list',
    }
})

const generatedCode = computed(() => {
    if (!form.audit_exercise_id || !form.mission_type_id) return '—'
    const ex = (props.exercises || []).find(e => e.id === form.audit_exercise_id)
    const t  = selectedTypeFull.value
    if (!ex || !t) return '—'
    return `${t.code}-${String(seqCount.value).padStart(3, '0')}-${String(ex.year).slice(-2)}`
})

const allMissions = computed(() => {
    const fpm   = (props.fpmMissions   || []).map(m => ({ ...m, source: 'fpm'  }))
    const audit = (props.auditMissions || []).map(m => ({ ...m, source: 'Risk' }))
    return [...fpm, ...audit].sort((a, b) =>
        parseFloat(b.rank || b.coefficient || b.level || 0) -
        parseFloat(a.rank || a.coefficient || a.level || 0)
    )
})

const fpmCount  = computed(() => (props.fpmMissions   || []).length)
const riskCount = computed(() => (props.auditMissions || []).length)

const filteredMissions = computed(() => {
    let list = allMissions.value
    if (mType.value)          list = list.filter(m => m.source === mType.value)
    if (mSearch.value.trim()) {
        const t = mSearch.value.toLowerCase()
        list = list.filter(m =>
            (m.code      || '').toLowerCase().includes(t) ||
            (m.title     || '').toLowerCase().includes(t) ||
            (m.objective || '').toLowerCase().includes(t)
        )
    }
    return list
})

const entityRisksByEntity = computed(() => {
    if (!validatedEntities.value.length) return {}
    const entityIds = validatedEntities.value.map(e => e.id)
    let allEntityRisks = (props.risks || []).filter(r => entityIds.includes(r.entity_id))

    if (!allEntityRisks.length) {
        const procIds = new Set(
            (props.assignments || [])
                .filter(a => entityIds.includes(a.entity_id) && a.mpa_type === 'process')
                .map(a => a.mpa_id)
        )
        if (procIds.size) {
            allEntityRisks = (props.risks || []).filter(r => procIds.has(r.process_id))
        }
    }

    allEntityRisks = allEntityRisks.map(r => ({
        ...r,
        process_code: (props.processes || []).find(p => p.id === r.process_id)?.code || '—',
    }))

    const grouped = {}
    allEntityRisks.forEach(r => {
        const key = r.entity_id
        if (!grouped[key]) grouped[key] = []
        grouped[key].push(r)
    })
    Object.keys(grouped).forEach(k => {
        grouped[k].sort((a, b) => (a.code || '').localeCompare(b.code || ''))
    })
    return grouped
})

const filteredHistory = computed(() => {
    const list = props.createdMissions || []
    if (!histSearch.value.trim()) return list
    const t = histSearch.value.toLowerCase()
    return list.filter(m =>
        (m.code  || '').toLowerCase().includes(t) ||
        (m.title || '').toLowerCase().includes(t)
    )
})

const formIsValid = computed(() =>
    !!(form.title?.trim()) &&
    !!form.audit_exercise_id &&
    !!form.mission_type_id &&
    validatedEntities.value.length > 0 &&
    includedEntityIds.value.length > 0
)

// ── Méthodes ──────────────────────────────────────────────────────────
const validateEntities = () => {
    const selected = (props.entities || []).filter(e => selectedEntityIds.value.includes(e.id))
    validatedEntities.value = selected
    includedEntityIds.value = selected.map(e => e.id)
    form.entity_ids         = selected.map(e => e.id)
    selectedEntityIds.value = []
}

const toggleEntity = (entityId) => {
    if (!includedEntityIds.value.includes(entityId)) {
        form.entity_ids = form.entity_ids.filter(id => id !== entityId)
        const toRemove  = new Set((entityRisksByEntity.value[entityId] || []).map(r => r.id))
        form.risk_ids   = form.risk_ids.filter(id => !toRemove.has(id))
    } else {
        if (!form.entity_ids.includes(entityId)) form.entity_ids.push(entityId)
    }
}

const resetEntities = () => {
    if (!confirm('Revenir à la sélection des entités ? Les risques sélectionnés seront perdus.')) return
    validatedEntities.value = []
    includedEntityIds.value = []
    form.entity_ids         = []
    form.risk_ids           = []
}

const openModal = () => {
    showModal.value = true
    selId.value     = null
    selSrc.value    = null
    mSearch.value   = ''
    mType.value     = null
}

const clearSource = () => {
    form.fpm_number          = ''
    loadedMissionTitle.value = ''
    form.objective           = ''
    aiSugs.value             = []
}

const confirmLoad = () => {
    const m = allMissions.value.find(x => x.id === selId.value && x.source === selSrc.value)
    if (!m) return

    form.fpm_number          = m.code || ''
    loadedMissionTitle.value = m.title || ''
    form.objective           = m.objective || m.but || m.description || ''
    form.planned_start_date  = m.planned_start_date || m.start_date || ''
    form.planned_end_date    = m.planned_end_date   || m.end_date   || ''
    calcDur()

    if (m.domain)             form.domain             = m.domain
    if (m.reference_document) form.reference_document = m.reference_document
    if (m.mission_type_id)    form.mission_type_id    = m.mission_type_id
    if (m.priority) {
        const map = { critique: 'critique', haute: 'haute', high: 'haute', moyenne: 'moyenne', medium: 'moyenne', basse: 'basse', low: 'basse' }
        form.priority = map[String(m.priority).toLowerCase()] || 'moyenne'
    }

    const entitySrc = m.entity_ids?.length ? m.entity_ids : (m.entity_id ? [m.entity_id] : [])
    if (entitySrc.length) {
        const ents = (props.entities || []).filter(e => entitySrc.includes(e.id))
        validatedEntities.value = ents
        includedEntityIds.value = ents.map(e => e.id)
        form.entity_ids         = ents.map(e => e.id)
    }

    if (Array.isArray(m.risk_ids) && m.risk_ids.length) {
        const validRiskIds = m.risk_ids.filter(rid => {
            const risk = (props.risks || []).find(r => r.id === rid)
            return risk && form.entity_ids.includes(risk.entity_id)
        })
        form.risk_ids = [...new Set([...form.risk_ids, ...validRiskIds])]
    }

    if (Array.isArray(m.competency_ids) && m.competency_ids.length) {
        form.competency_ids = [...new Set([...form.competency_ids, ...m.competency_ids])]
    }

    showModal.value = false
    if (form.objective) setTimeout(generateTitles, 600)
}

const generateTitles = async () => {
    if (!form.objective || loadingTitles.value) return
    loadingTitles.value = true
    aiSugs.value        = []
    try {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || ''
        const resp = await axios.post('/m/audit.core/api/ai/suggest-mission-title', {
            objective: form.objective,
            type:      selectedTypeFull.value?.label || '',
            entity:    validatedEntities.value.map(e => e.name).join(', '),
            domain:    form.domain || '',
            year:      activeExercise.value?.year || new Date().getFullYear(),
        }, { headers: { 'X-CSRF-TOKEN': csrf } })

        if (resp.data.success && Array.isArray(resp.data.suggestions)) {
            aiSugs.value = resp.data.suggestions.slice(0, 4)
            if (!form.title && aiSugs.value.length) form.title = aiSugs.value[0]
        }
    } catch (err) {
        console.error('Erreur IA :', err)
    } finally {
        loadingTitles.value = false
    }
}

const onObjectiveInput = () => {
    aiSugs.value = []
    clearTimeout(titleTimer)
    if ((form.objective?.length || 0) > 12) titleTimer = setTimeout(generateTitles, 1400)
}

const onTypeChange = () => { if (form.objective) generateTitles() }

const calcDur = () => {
    if (!form.planned_start_date || !form.planned_end_date) {
        form.planned_duration_days = 0; return
    }
    form.planned_duration_days = Math.max(0, Math.ceil(
        (new Date(form.planned_end_date) - new Date(form.planned_start_date)) / 86400000
    ))
}

const toggleRisk = (id) => {
    if (form.risk_ids.includes(id)) {
        form.risk_ids = form.risk_ids.filter(r => r !== id)
    } else {
        const risk = (props.risks || []).find(r => r.id === id)
        if (risk && includedEntityIds.value.includes(risk.entity_id)) form.risk_ids.push(id)
    }
}

// Compétences : v-model natif via form.competency_ids (checkbox input)
const removeComp = (id) => {
    form.competency_ids = form.competency_ids.filter(c => c !== id)
}

const selectAllComps = () => {
    const allIds = filteredComps.value.map(c => c.id)
    const merged = [...new Set([...form.competency_ids, ...allIds])]
    form.competency_ids = merged
}

const resetForm = () => {
    form.reset()
    form.priority              = 'moyenne'
    form.planned_duration_days = 0
    form.audit_exercise_id     = activeExercise.value?.id || null
    validatedEntities.value    = []
    includedEntityIds.value    = []
    selectedEntityIds.value    = []
    aiSugs.value               = []
    loadedMissionTitle.value   = ''
    compSearch.value           = ''
}

const submit = () => {
    if (!formIsValid.value) {
        alert('Champs obligatoires manquants : intitulé, type de mission et au moins une entité.')
        return
    }
    form.post(route('audit.core.missions.store'), {
        preserveScroll: true,
        onSuccess: () => { seqCount.value++; resetForm() },
        onError: (errors) => console.error('Erreurs de validation :', errors),
    })
}
</script>

<style scoped>
/* ════════════════════════════════════════════════
   VARIABLES
═══════════════════════════════════════════════ */
:root {
    --border-color: #e9ecef;
    --surface: #ffffff;
    --surface-alt: #f8f9fb;
    --text-muted: #8a94a6;
    --radius-sm: 6px;
    --radius-md: 10px;
    --radius-lg: 14px;
    --shadow-sm: 0 1px 3px rgba(0,0,0,.06);
    --shadow-md: 0 4px 16px rgba(0,0,0,.08);
}

/* ════════════════════════════════════════════════
   COMMAND BAR
═══════════════════════════════════════════════ */
.command-bar {
    background: #fff;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
}
.command-bar-inner { padding: 10px 16px; }
.exercise-dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: #22c55e;
    display: inline-block;
    box-shadow: 0 0 0 3px rgba(34,197,94,.15);
}
.separator-v { width: 1px; height: 20px; background: var(--border-color); }
.mission-code-badge {
    background: #eef2ff; color: #4f46e5;
    border: 1px solid #c7d2fe;
    padding: 2px 8px;
    border-radius: 5px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: .5px;
}
.type-chip {
    padding: 2px 7px; border-radius: 4px;
    font-size: 11px; font-weight: 600;
    display: inline-flex; align-items: center;
}
.sync-warning-badge {
    background: #fef3c7; color: #92400e;
    padding: 2px 7px; border-radius: 4px;
    font-size: 10px; font-weight: 600;
    display: inline-flex; align-items: center;
}
.audit-type-preview {
    display: flex; align-items: center; gap: 6px;
    margin-top: 6px; padding: 4px 2px;
}
.audit-type-dot {
    width: 8px; height: 8px; border-radius: 50%;
    flex-shrink: 0;
}
.status-pill {
    padding: 4px 10px; border-radius: 20px;
    font-size: 12px; font-weight: 600;
    display: inline-flex; align-items: center;
}
.status-ready    { background: #dcfce7; color: #166534; }
.status-incomplete { background: #f3f4f6; color: #6b7280; }
.btn-icon-text { border-radius: var(--radius-sm); font-size: 12px; }
.btn-save {
    border-radius: var(--radius-sm);
    font-size: 12px; font-weight: 600;
    padding: 6px 14px;
}

/* ════════════════════════════════════════════════
   STEPPER
═══════════════════════════════════════════════ */
.stepper-row {
    display: flex; gap: 0;
    background: #fff;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}
.stepper-item {
    flex: 1; display: flex; align-items: center;
    gap: 10px; padding: 12px 16px;
    position: relative;
    transition: background .2s;
}
.stepper-item:not(:last-child)::after {
    content: '';
    position: absolute; right: 0; top: 50%;
    transform: translateY(-50%);
    width: 1px; height: 60%;
    background: var(--border-color);
}
.step-indicator {
    width: 32px; height: 32px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700; flex-shrink: 0;
    transition: all .25s;
}
.step-done .step-indicator    { background: #22c55e; color: #fff; }
.step-active .step-indicator  { background: #4f46e5; color: #fff; box-shadow: 0 0 0 4px rgba(79,70,229,.15); }
.step-pending .step-indicator { background: #f1f5f9; color: #94a3b8; }
.step-info { display: flex; flex-direction: column; overflow: hidden; }
.step-label {
    font-size: 12px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.step-done .step-label   { color: #166534; }
.step-active .step-label { color: #4f46e5; }
.step-pending .step-label { color: #94a3b8; }
.step-hint {
    font-size: 11px; color: #b0bac5;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

/* ════════════════════════════════════════════════
   FORM SECTIONS
═══════════════════════════════════════════════ */
.form-section {
    background: #fff;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    transition: opacity .2s, filter .2s;
    overflow: hidden;
}
.section-locked { opacity: .45; pointer-events: none; filter: grayscale(.4); }

.section-header {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid var(--border-color);
    background: var(--surface-alt);
}
.section-number {
    width: 28px; height: 28px;
    border-radius: 8px;
    background: #eef2ff; color: #4f46e5;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700;
    flex-shrink: 0;
}
.section-title {
    font-size: 13px; font-weight: 700;
    color: #1e293b; margin: 0;
}
.section-subtitle {
    font-size: 11px; color: var(--text-muted); margin: 0;
}
.lock-badge {
    font-size: 11px; color: #94a3b8;
    background: #f8fafc; border: 1px solid #e2e8f0;
    border-radius: 4px; padding: 2px 7px;
}
.section-body { padding: 14px 16px; }

/* ════════════════════════════════════════════════
   SOURCE PICKER
═══════════════════════════════════════════════ */
.source-picker {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px;
    border: 1.5px dashed #d1d5db;
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: all .2s;
    background: #fafbfc;
}
.source-picker:hover { border-color: #4f46e5; background: #f5f3ff; }
.source-picker--selected { border-style: solid; border-color: #4f46e5; background: #eef2ff; }
.source-picker-icon { color: #94a3b8; font-size: 16px; flex-shrink: 0; }
.source-picker-text { flex: 1; overflow: hidden; }
.source-clear {
    background: none; border: none; color: #ef4444;
    padding: 2px 4px; cursor: pointer;
    border-radius: 4px;
    transition: background .15s;
}
.source-clear:hover { background: #fee2e2; }

/* ════════════════════════════════════════════════
   TEXTAREA & INPUT
═══════════════════════════════════════════════ */
.custom-textarea, .custom-input {
    border-radius: var(--radius-sm) !important;
    border-color: #e2e8f0 !important;
    font-size: 13px !important;
}
.custom-textarea:focus, .custom-input:focus {
    border-color: #4f46e5 !important;
    box-shadow: 0 0 0 3px rgba(79,70,229,.08) !important;
}
.input-valid { border-color: #22c55e !important; }
.char-count { text-align: right; font-size: 11px; color: var(--text-muted); margin-top: 4px; }

/* ════════════════════════════════════════════════
   AI SUGGESTIONS
═══════════════════════════════════════════════ */
.ai-btn {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff; border-radius: 6px;
    font-size: 12px; font-weight: 600;
    padding: 5px 12px; border: none;
}
.ai-btn:hover { opacity: .88; }
.ai-suggestions {
    border: 1.5px dashed #c7d2fe;
    border-radius: var(--radius-md);
    padding: 10px 12px;
    background: #f5f3ff;
}
.ai-suggestions-label {
    font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .5px;
    color: #6d28d9; margin-bottom: 8px;
}
.ai-suggestion-item {
    display: flex; align-items: flex-start; gap: 8px;
    padding: 6px 8px; border-radius: 6px;
    cursor: pointer; margin-bottom: 4px;
    transition: background .15s;
    border: 1px solid transparent;
}
.ai-suggestion-item:hover { background: #ede9fe; }
.ai-suggestion-item--selected { background: #ede9fe; border-color: #a78bfa; }
.ai-suggestion-num {
    width: 20px; height: 20px;
    background: #7c3aed; color: #fff;
    border-radius: 50%; font-size: 10px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; margin-top: 1px;
}

/* ════════════════════════════════════════════════
   CHAMP LABEL
═══════════════════════════════════════════════ */
.field-label {
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .4px;
    color: #64748b; margin-bottom: 4px;
    display: block;
}

/* ════════════════════════════════════════════════
   PRIORITÉ
═══════════════════════════════════════════════ */
.prio-group { display: flex; border-radius: var(--radius-sm); overflow: hidden; border: 1px solid #e2e8f0; }
.prio-btn {
    flex: 1; padding: 5px 4px;
    border: none; background: #f8f9fa;
    font-size: 11px; font-weight: 600; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 4px;
    transition: all .15s;
}
.prio-btn:not(:last-child) { border-right: 1px solid #e2e8f0; }
.prio-btn:hover { filter: brightness(.96); }
.prio-label { display: none; }
@media (min-width: 1400px) { .prio-label { display: inline; } }

.prio-active-success  { background: #22c55e; color: #fff; }
.prio-active-warning  { background: #f59e0b; color: #fff; }
.prio-active-orange   { background: #f97316; color: #fff; }
.prio-active-danger   { background: #ef4444; color: #fff; }
.prio-outline-success { color: #22c55e; }
.prio-outline-warning { color: #f59e0b; }
.prio-outline-orange  { color: #f97316; }
.prio-outline-danger  { color: #ef4444; }

/* ════════════════════════════════════════════════
   DURÉE
═══════════════════════════════════════════════ */
.duration-badge {
    height: 31px; min-width: 56px;
    background: #eef2ff; color: #4f46e5;
    border: 1px solid #c7d2fe;
    border-radius: var(--radius-sm);
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 700;
    padding: 0 10px;
}
.duration-badge small { font-size: 10px; margin-left: 1px; }

/* ════════════════════════════════════════════════
   ENTITÉS
═══════════════════════════════════════════════ */
.entity-grid { display: flex; flex-direction: column; gap: 2px; }
.entity-check-item {
    display: flex; align-items: center; gap: 8px;
    padding: 6px 8px; border-radius: 6px;
    cursor: pointer;
    border: 1px solid transparent;
    transition: all .15s;
}
.entity-check-item:hover { background: #f8f9fa; }
.entity-check-item--selected { background: #f0fdf4; border-color: #bbf7d0; }
.entity-checkbox { width: 14px; height: 14px; cursor: pointer; }
.entity-code { font-size: 12px; font-weight: 700; color: #4f46e5; min-width: 50px; }
.entity-name { font-size: 12px; color: #64748b; }

/* entités validées */
.entity-validated-header {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 14px;
    background: var(--surface-alt);
    border-bottom: 1px solid var(--border-color);
}
.entity-toggle-wrap { position: relative; }
.entity-toggle { appearance: none; width: 32px; height: 18px; background: #d1d5db; border-radius: 9px; cursor: pointer; transition: background .2s; position: relative; outline: none; }
.entity-toggle:checked { background: #4f46e5; }
.entity-toggle-track::before {
    content: ''; position: absolute; top: 2px; left: 2px;
    width: 14px; height: 14px; border-radius: 50%; background: #fff;
    transition: transform .2s; pointer-events: none;
}
.entity-toggle:checked ~ .entity-toggle-track::before { transform: translateX(14px); }

/* risques table */
.risks-table {
    width: 100%; border-collapse: collapse; font-size: 12px;
}
.risks-table thead th {
    padding: 6px 10px; font-size: 10px; font-weight: 700;
    text-transform: uppercase; color: #94a3b8; background: #f8f9fb;
    border-bottom: 1px solid var(--border-color);
}
.risk-row { cursor: pointer; transition: background .12s; }
.risk-row td { padding: 5px 10px; border-bottom: 1px solid #f1f5f9; }
.risk-row:hover { background: #f8f9fa; }
.risk-row--selected { background: #fef2f2 !important; }
.risk-checkbox {
    width: 16px; height: 16px; border-radius: 4px;
    border: 1.5px solid #d1d5db; background: #fff;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 10px; transition: all .15s;
}
.risk-checkbox--checked { background: #ef4444; border-color: #ef4444; color: #fff; }
.risk-code-badge {
    display: inline-block;
    background: #fee2e2; color: #991b1b;
    border-radius: 4px; padding: 1px 5px;
    font-size: 10px; font-weight: 700;
}
.proc-badge {
    display: inline-block;
    background: #e0f2fe; color: #0369a1;
    border-radius: 4px; padding: 1px 5px;
    font-size: 10px; font-weight: 600;
}

.entity-disabled-msg {
    padding: 8px 14px; font-size: 12px;
    color: #94a3b8; background: #f8f9fb;
}

.risk-summary {
    display: flex; align-items: center; justify-content: space-between;
    padding: 6px 10px;
    background: #fff5f5; border-radius: 6px; font-size: 12px;
}

/* ════════════════════════════════════════════════
   COMPÉTENCES — LISTE MULTI-SÉLECTION
═══════════════════════════════════════════════ */
.comp-search {
    position: relative;
    display: flex; align-items: center;
}
.comp-search-icon {
    position: absolute; left: 9px;
    color: #94a3b8; font-size: 13px; pointer-events: none;
}
.comp-search-input {
    width: 100%; padding: 6px 10px 6px 30px;
    border: 1.5px solid #e2e8f0; border-radius: var(--radius-sm);
    font-size: 12px; outline: none;
    transition: border-color .15s;
    background: #fafbfc;
}
.comp-search-input:focus { border-color: #4f46e5; background: #fff; }

.comp-list { padding: 6px 10px; }
.comp-item {
    display: flex; align-items: center; gap: 8px;
    padding: 5px 8px; border-radius: 6px;
    cursor: pointer;
    border: 1px solid transparent;
    transition: all .12s;
    margin-bottom: 2px;
    user-select: none;
}
.comp-item:hover { background: #f8f9fa; }
.comp-item--selected { background: #f0fdf4; border-color: #bbf7d0; }

.comp-checkbox-native {
    position: absolute; opacity: 0; pointer-events: none; width: 0; height: 0;
}
.comp-check {
    width: 16px; height: 16px; border-radius: 4px;
    border: 1.5px solid #d1d5db; background: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 10px; flex-shrink: 0;
    transition: all .15s;
}
.comp-check--on { background: #22c55e; border-color: #22c55e; color: #fff; }
.comp-code {
    font-size: 10px; font-weight: 700;
    background: #dcfce7; color: #166534;
    border-radius: 4px; padding: 1px 5px;
    flex-shrink: 0;
}
.comp-name { font-size: 12px; color: #374151; }

/* Boutons sélect all / clear */
.comp-all-btn, .comp-clear-btn {
    width: 26px; height: 26px; border-radius: 6px;
    border: 1px solid #e2e8f0; background: #f8f9fa;
    cursor: pointer; font-size: 13px; color: #64748b;
    display: flex; align-items: center; justify-content: center;
    transition: all .15s;
}
.comp-all-btn:hover  { background: #e0f2fe; color: #0369a1; border-color: #bae6fd; }
.comp-clear-btn:hover { background: #fee2e2; color: #991b1b; border-color: #fecaca; }

/* Tags récapitulatifs */
.comp-recap {
    padding: 8px 12px;
    border-top: 1px solid var(--border-color);
    background: #f8f9fb;
}
.comp-tags { display: flex; flex-wrap: wrap; gap: 5px; }
.comp-tag {
    display: inline-flex; align-items: center; gap: 4px;
    background: #dcfce7; color: #166534;
    border: 1px solid #bbf7d0;
    border-radius: 20px; padding: 2px 8px 2px 8px;
    font-size: 11px; font-weight: 600;
}
.comp-tag-remove {
    background: none; border: none; cursor: pointer;
    color: #6b7280; padding: 0; line-height: 1;
    display: flex; align-items: center; font-size: 10px;
}
.comp-tag-remove:hover { color: #ef4444; }

/* ════════════════════════════════════════════════
   HISTORIQUE TABLE
═══════════════════════════════════════════════ */
.history-table {
    width: 100%; border-collapse: collapse; font-size: 12px;
    white-space: nowrap;
}
.history-table thead th {
    padding: 8px 12px;
    font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .4px;
    color: #94a3b8; background: #f8f9fb;
    border-bottom: 1px solid var(--border-color);
}
.history-table tbody tr:hover { background: #f8f9fa; }
.history-table tbody td { padding: 7px 12px; border-bottom: 1px solid #f1f5f9; }

.hist-badge {
    display: inline-block; padding: 2px 7px;
    border-radius: 4px; font-size: 10px; font-weight: 600;
}
.badge-success  { background: #dcfce7; color: #166534; }
.badge-warning  { background: #fef9c3; color: #854d0e; }
.badge-danger   { background: #fee2e2; color: #991b1b; }
.badge-info     { background: #e0f2fe; color: #0369a1; }
.badge-primary  { background: #eef2ff; color: #3730a3; }
.badge-secondary { background: #f1f5f9; color: #64748b; }
.badge-orange   { background: #fff7ed; color: #c2410c; }

/* ════════════════════════════════════════════════
   MODAL
═══════════════════════════════════════════════ */
.modal-overlay {
    position: fixed; inset: 0; z-index: 1055;
    background: rgba(15,20,40,.55);
    backdrop-filter: blur(3px);
    display: flex; align-items: center; justify-content: center; padding: 16px;
}
.modal-panel {
    background: #fff; border-radius: 16px;
    width: 100%; max-width: 860px;
    box-shadow: 0 24px 60px rgba(0,0,0,.18);
    display: flex; flex-direction: column;
    max-height: 90vh; overflow: hidden;
}
.modal-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 18px;
    background: #0f172a;
    border-radius: 16px 16px 0 0;
}
.modal-icon-wrap {
    width: 36px; height: 36px; border-radius: 8px;
    background: rgba(255,255,255,.1);
    display: flex; align-items: center; justify-content: center; color: #fff;
}
.modal-close {
    width: 28px; height: 28px; border-radius: 6px;
    background: rgba(255,255,255,.12); border: none;
    color: #fff; cursor: pointer; font-size: 14px;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s;
}
.modal-close:hover { background: rgba(255,255,255,.22); }
.modal-body-area { flex: 1; overflow-y: auto; }
.modal-stats {
    display: flex; align-items: center; gap: 16px;
    padding: 8px 12px; border-radius: 8px;
    background: #f8f9fb; font-size: 13px;
}
.modal-stat { display: flex; align-items: center; gap: 4px; }
.modal-foot {
    display: flex; align-items: center; justify-content: flex-end; gap: 8px;
    padding: 10px 16px;
    border-top: 1px solid var(--border-color);
    background: var(--surface-alt);
    border-radius: 0 0 16px 16px;
}

.modal-table {
    width: 100%; border-collapse: collapse; font-size: 12px; white-space: nowrap;
}
.modal-table thead th {
    padding: 8px 10px;
    font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px;
    color: #fff; background: #1e293b;
    position: sticky; top: 0; z-index: 1;
}
.modal-row { cursor: pointer; transition: background .12s; }
.modal-row td { padding: 6px 10px; border-bottom: 1px solid #f1f5f9; }
.modal-row:hover { background: #f8f9fb; }
.modal-row--selected { background: #eef2ff !important; }

.rank-badge {
    display: inline-block; padding: 2px 7px;
    border-radius: 4px; font-size: 11px; font-weight: 700;
}
.rank-danger  { background: #fee2e2; color: #991b1b; }
.rank-warning { background: #fef9c3; color: #854d0e; }
.rank-success { background: #dcfce7; color: #166534; }
.rank-muted   { background: #f1f5f9; color: #64748b; }

.src-badge {
    display: inline-block; padding: 2px 7px;
    border-radius: 4px; font-size: 10px; font-weight: 700;
}
.src-fpm  { background: #e0f2fe; color: #0369a1; }
.src-risk { background: #dcfce7; color: #166534; }

/* ════════════════════════════════════════════════
   EMPTY STATE
═══════════════════════════════════════════════ */
.empty-state {
    display: flex; flex-direction: column; align-items: center;
    color: #94a3b8; font-size: 12px; text-align: center;
}

/* ════════════════════════════════════════════════
   ANIMATIONS
═══════════════════════════════════════════════ */
.modal-fade-enter-active { animation: modalIn .22s cubic-bezier(.175,.885,.32,1.1); }
.modal-fade-leave-active { animation: modalIn .16s ease-in reverse; }
@keyframes modalIn {
    from { opacity: 0; transform: scale(.94) translateY(8px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}

/* ════════════════════════════════════════════════
   UTILITAIRES
═══════════════════════════════════════════════ */
.cursor-pointer { cursor: pointer; }
.fs-11 { font-size: 0.6875rem !important; }
.border-dashed { border-style: dashed !important; }
</style>
