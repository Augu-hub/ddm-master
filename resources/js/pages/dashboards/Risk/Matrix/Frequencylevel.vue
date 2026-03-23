<template>
    <VerticalLayout>
        <Head title="DDM — Niveaux de fréquence" />

        <!-- HEADER -->
        <b-row class="mb-2">
            <b-col class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-repeat text-primary fs-5"></i>
                    <h4 class="m-0 fw-semibold">Niveaux de fréquence</h4>
                    <small class="text-muted ms-1">Matrice des risques</small>
                </div>
                <div class="d-flex align-items-center gap-2" v-if="matrixConfigs.length">
                    <label class="form-label mb-0 text-muted small">Configuration :</label>
                    <select v-model="currentConfigId" @change="onConfigChange"
                            class="form-select form-select-sm" style="width:auto">
                        <option v-for="cfg in matrixConfigs" :key="cfg.id" :value="cfg.id">
                            {{ cfg.name }} ({{ cfg.matrix_label }}){{ cfg.is_active ? ' ✓' : '' }}
                        </option>
                    </select>
                </div>
            </b-col>
        </b-row>

        <!-- ALERTE — pas de config -->
        <b-alert v-if="!matrixConfigs.length" variant="warning" show class="py-2 px-3">
            <i class="ti ti-alert-triangle me-1"></i>
            Aucune configuration disponible.
            <a :href="route('risk.core.matrix-config.index')" class="alert-link ms-1">Créer une configuration →</a>
        </b-alert>

        <template v-else>
            <!-- STATS -->
            <b-row class="g-2 mb-2">
                <b-col md="4">
                    <b-card no-body class="shadow-sm stat-card border-start border-primary border-3">
                        <b-card-body class="p-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="stat-icon bg-primary"><i class="ti ti-repeat"></i></div>
                                <div>
                                    <small class="text-muted d-block">Niveaux définis</small>
                                    <h5 class="mb-0 fw-bold">{{ frequencyLevels.length }} / {{ selectedConfig?.matrix_size ?? '—' }}</h5>
                                </div>
                                <div class="ms-auto">
                                    <span v-if="capacityPercent >= 100" class="badge bg-success">Complet</span>
                                    <span v-else class="badge bg-warning text-dark">En cours</span>
                                </div>
                            </div>
                            <div class="progress mt-2" style="height:4px">
                                <div class="progress-bar" :class="capacityPercent >= 100 ? 'bg-success' : 'bg-primary'"
                                     :style="{ width: capacityPercent + '%' }"></div>
                            </div>
                        </b-card-body>
                    </b-card>
                </b-col>
                <b-col md="4">
                    <b-card no-body class="shadow-sm stat-card border-start border-info border-3">
                        <b-card-body class="p-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="stat-icon bg-info"><i class="ti ti-layout-grid"></i></div>
                                <div>
                                    <small class="text-muted d-block">Taille matrice</small>
                                    <h5 class="mb-0 fw-bold">{{ selectedConfig?.matrix_label ?? '—' }}</h5>
                                </div>
                            </div>
                        </b-card-body>
                    </b-card>
                </b-col>
                <b-col md="4">
                    <b-card no-body class="shadow-sm stat-card border-start border-success border-3">
                        <b-card-body class="p-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="stat-icon bg-success"><i class="ti ti-checklist"></i></div>
                                <div>
                                    <small class="text-muted d-block">Total critères</small>
                                    <h5 class="mb-0 fw-bold">{{ totalCriteria }}</h5>
                                </div>
                            </div>
                        </b-card-body>
                    </b-card>
                </b-col>
            </b-row>

            <b-alert v-if="alert.show" :variant="alert.variant" show dismissible
                     @dismissed="alert.show = false" class="py-2 px-3 mb-2">
                {{ alert.message }}
            </b-alert>

            <b-row class="g-2">
                <!-- FORMULAIRE niveau -->
                <b-col lg="5">
                    <b-card no-body class="shadow-sm">
                        <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="ti ti-repeat me-1 text-primary"></i>
                                {{ editingId ? 'Modifier le niveau' : 'Ajouter un niveau de fréquence' }}
                            </h6>
                            <span v-if="editingId" class="badge bg-warning text-dark">Mode édition</span>
                        </b-card-header>
                        <b-card-body class="p-3">
                            <b-form @submit.prevent="submitForm">
                                <b-row class="g-2">
                                    <b-col cols="8">
                                        <label class="form-label mb-1">Libellé <span class="text-danger">*</span></label>
                                        <b-form-input class="form-control-sm" v-model.trim="form.label"
                                                      placeholder="ex : Probable" required />
                                        <div v-if="errors.label" class="text-danger small mt-1">{{ errors.label }}</div>
                                    </b-col>
                                    <b-col cols="4">
                                        <label class="form-label mb-1">Score <span class="text-danger">*</span></label>
                                        <select v-model.number="form.score" class="form-select form-select-sm">
                                            <option v-for="s in availableScores" :key="s" :value="s">{{ s }}</option>
                                        </select>
                                        <div v-if="errors.score" class="text-danger small mt-1">{{ errors.score }}</div>
                                    </b-col>
                                    <b-col cols="12">
                                        <label class="form-label mb-1">Description qualitative</label>
                                        <b-form-textarea class="form-control-sm" rows="3"
                                                         v-model.trim="form.description"
                                                         placeholder="Décrivez ce niveau de fréquence..." />
                                    </b-col>
                                    <b-col cols="12">
                                        <label class="form-label mb-1">Récurrence</label>
                                        <b-form-input class="form-control-sm" v-model.trim="form.recurrence"
                                                      placeholder="ex : 1 fois / 5 ans" />
                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                            <button v-for="rec in recurrenceSuggestions" :key="rec" type="button"
                                                    class="recurrence-chip"
                                                    @click="form.recurrence = rec">{{ rec }}</button>
                                        </div>
                                    </b-col>
                                    <b-col cols="12">
                                        <label class="form-label mb-1">Couleur</label>
                                        <div class="d-flex gap-2 align-items-center">
                                            <input type="color" v-model="form.color_code"
                                                   class="form-control form-control-sm form-control-color p-0"
                                                   style="width:36px;height:28px" />
                                            <b-form-input class="form-control-sm font-monospace"
                                                          v-model="form.color_code"
                                                          placeholder="#0ea5e9" style="max-width:100px" />
                                            <span class="apt-badge" :style="badgeStyle(form.color_code)">
                                                {{ form.label || 'Aperçu' }}
                                            </span>
                                        </div>
                                        <div v-if="errors.color_code" class="text-danger small mt-1">{{ errors.color_code }}</div>
                                    </b-col>
                                    <b-col cols="6">
                                        <label class="form-label mb-1">Ordre d'affichage</label>
                                        <b-form-input class="form-control-sm" type="number" min="0"
                                                      v-model.number="form.sort_order" />
                                    </b-col>
                                    <b-col cols="12" class="d-flex justify-content-between align-items-center pt-1">
                                        <b-button size="sm" variant="light" @click="resetForm">
                                            <i class="ti ti-x me-1"></i>Annuler
                                        </b-button>
                                        <div class="d-flex gap-1">
                                            <b-button size="sm" variant="outline-primary" @click="openMistralPanel">
                                                <i class="ti ti-sparkles me-1"></i>IA
                                            </b-button>
                                            <b-button size="sm" variant="primary" type="submit"
                                                      :disabled="processing || (!canAddMore && !editingId)">
                                                <i class="ti ti-loader-2 ti-spin me-1" v-if="processing"></i>
                                                <i class="ti ti-device-floppy me-1" v-else></i>
                                                {{ editingId ? 'Enregistrer' : 'Créer' }}
                                            </b-button>
                                        </div>
                                    </b-col>
                                </b-row>
                            </b-form>
                        </b-card-body>
                    </b-card>
                </b-col>

                <!-- TABLE niveaux + critères -->
                <b-col lg="7">
                    <b-card no-body class="shadow-sm">
                        <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="ti ti-list me-1"></i>Niveaux définis</h6>
                            <b-button v-if="frequencyLevels.length" size="sm" variant="outline-primary"
                                      @click="openCriteriaMistralPanel">
                                <i class="ti ti-sparkles me-1"></i>Critères IA
                            </b-button>
                        </b-card-header>
                        <b-card-body class="p-0">
                            <div v-if="!frequencyLevels.length" class="text-center text-muted py-5">
                                <i class="ti ti-repeat fs-1 opacity-25 d-block mb-2"></i>
                                <p class="mb-0">Aucun niveau de fréquence défini.</p>
                            </div>
                            <div v-else>
                                <DataTable :value="sortedLevels" size="small" class="pv-table flat">
                                    <Column header="Score" style="width:55px" bodyClass="text-center">
                                        <template #body="{data}">
                                            <span class="fw-bold font-monospace fs-6" :style="{ color: data.color_code }">{{ data.score }}</span>
                                        </template>
                                    </Column>
                                    <Column header="Libellé">
                                        <template #body="{data}">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="color-dot" :style="{ background: data.color_code }"></span>
                                                <div>
                                                    <span class="fw-semibold">{{ data.label }}</span>
                                                    <small v-if="data.recurrence" class="text-muted d-block" style="font-size:.67rem">
                                                        <i class="ti ti-clock me-1"></i>{{ data.recurrence }}
                                                    </small>
                                                </div>
                                            </div>
                                        </template>
                                    </Column>
                                    <Column header="Description" bodyClass="text-muted small">
                                        <template #body="{data}">
                                            {{ data.description ? data.description.substring(0, 55) + (data.description.length > 55 ? '…' : '') : '—' }}
                                        </template>
                                    </Column>
                                    <Column header="" style="width:95px" bodyClass="text-end pe-2">
                                        <template #body="{data}">
                                            <b-button size="sm" variant="light" class="me-1" @click="openForm(data)" title="Modifier le niveau">
                                                <i class="ti ti-pencil"></i>
                                            </b-button>
                                            <b-button size="sm" variant="light" class="text-danger" @click="confirmDelete(data)" title="Supprimer le niveau">
                                                <i class="ti ti-trash"></i>
                                            </b-button>
                                        </template>
                                    </Column>
                                    <template #empty>
                                        <div class="text-muted py-2 text-center">Aucun niveau</div>
                                    </template>
                                </DataTable>

                                <!-- ═══ PANNEAU CRITÈRES PAR NIVEAU ═══ -->
                                <div class="criteria-section border-top">
                                    <div class="criteria-section-header px-3 py-2">
                                        <small class="text-muted fw-semibold text-uppercase" style="font-size:.68rem">
                                            <i class="ti ti-checklist me-1"></i>Critères d'évaluation par niveau
                                        </small>
                                    </div>

                                    <div v-for="level in sortedLevels" :key="level.id" class="criteria-level-block border-bottom">
                                        <div class="criteria-level-header px-3 py-2 d-flex align-items-center justify-content-between"
                                             role="button" @click="toggleCriteriaPanel(level.id)">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="color-dot" :style="{ background: level.color_code }"></span>
                                                <span class="fw-semibold small">{{ level.label }}</span>
                                                <span v-if="level.recurrence" class="text-muted" style="font-size:.65rem">
                                                    <i class="ti ti-clock me-1"></i>{{ level.recurrence }}
                                                </span>
                                                <span class="badge criteria-count-badge" :style="badgeStyle(level.color_code)">
                                                    {{ level.criteria?.length ?? 0 }} critère{{ (level.criteria?.length ?? 0) !== 1 ? 's' : '' }}
                                                </span>
                                            </div>
                                            <div class="d-flex align-items-center gap-1">
                                                <b-button size="sm" variant="light" class="btn-add-criterion"
                                                          @click.stop="openAddCriterion(level)" title="Ajouter un critère">
                                                    <i class="ti ti-plus"></i>
                                                </b-button>
                                                <i class="ti small text-muted"
                                                   :class="openCriteriaPanels[level.id] ? 'ti-chevron-up' : 'ti-chevron-down'"></i>
                                            </div>
                                        </div>

                                        <div v-show="openCriteriaPanels[level.id]" class="criteria-body px-3 pb-2">
                                            <!-- Formulaire inline -->
                                            <div v-if="criterionForm.levelId === level.id" class="criterion-form-inline mb-2 p-2">
                                                <div class="mb-1">
                                                    <input v-model.trim="criterionForm.designation"
                                                           class="form-control form-control-sm"
                                                           placeholder="Désignation du critère *"
                                                           @keydown.enter.prevent="submitCriterion"
                                                           @keydown.escape="cancelCriterion"
                                                           ref="criterionDesignationInput" />
                                                    <div v-if="criterionErrors.designation" class="text-danger" style="font-size:.7rem">{{ criterionErrors.designation }}</div>
                                                </div>
                                                <div class="mb-2">
                                                    <textarea v-model.trim="criterionForm.description"
                                                              class="form-control form-control-sm" rows="2"
                                                              placeholder="Description détaillée (optionnel)"
                                                              @keydown.escape="cancelCriterion"></textarea>
                                                </div>
                                                <div class="d-flex gap-1 justify-content-end">
                                                    <b-button size="sm" variant="light" @click="cancelCriterion">
                                                        <i class="ti ti-x"></i>
                                                    </b-button>
                                                    <b-button size="sm" variant="primary" @click="submitCriterion"
                                                              :disabled="criterionProcessing || !criterionForm.designation">
                                                        <i class="ti ti-loader-2 ti-spin" v-if="criterionProcessing"></i>
                                                        <i class="ti ti-check" v-else></i>
                                                        {{ criterionForm.criterionId ? 'Modifier' : 'Ajouter' }}
                                                    </b-button>
                                                </div>
                                            </div>

                                            <!-- Liste critères (drag & drop) -->
                                            <div v-if="level.criteria?.length"
                                                 class="criteria-list"
                                                 @dragover.prevent
                                                 @drop.prevent="onCriterionDrop($event, level)">
                                                <div v-for="(criterion, idx) in level.criteria" :key="criterion.id"
                                                     class="criterion-row d-flex align-items-start gap-2"
                                                     draggable="true"
                                                     @dragstart="onCriterionDragStart($event, level.id, idx)"
                                                     @dragenter.prevent>
                                                    <i class="ti ti-grip-vertical drag-handle text-muted mt-1 flex-shrink-0"></i>
                                                    <div class="flex-fill min-w-0">
                                                        <div class="fw-semibold" style="font-size:.78rem">{{ criterion.designation }}</div>
                                                        <div v-if="criterion.description" class="text-muted" style="font-size:.72rem">{{ criterion.description }}</div>
                                                    </div>
                                                    <div class="d-flex gap-1 flex-shrink-0">
                                                        <button type="button" class="btn-icon" @click="openEditCriterion(level, criterion)" title="Modifier">
                                                            <i class="ti ti-pencil"></i>
                                                        </button>
                                                        <button type="button" class="btn-icon text-danger" @click="confirmDeleteCriterion(level, criterion)" title="Supprimer">
                                                            <i class="ti ti-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div v-else-if="criterionForm.levelId !== level.id"
                                                 class="text-muted text-center py-2" style="font-size:.73rem">
                                                <i class="ti ti-playlist-off me-1 opacity-50"></i>
                                                Aucun critère — <button type="button" class="btn btn-link p-0 small" @click="openAddCriterion(level)">ajouter</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Graduation visuelle -->
                                <div class="p-3 border-top">
                                    <small class="text-muted fw-semibold text-uppercase d-block mb-2">Graduation</small>
                                    <div class="d-flex rounded overflow-hidden" style="height:28px">
                                        <div v-for="level in sortedLevels" :key="level.id"
                                             class="flex-fill d-flex align-items-center justify-content-center small fw-semibold text-white"
                                             :style="{ backgroundColor: level.color_code }"
                                             :title="`Score ${level.score} — ${level.label}`">
                                            {{ level.label }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </b-card-body>
                    </b-card>
                </b-col>
            </b-row>
        </template>

        <!-- MODALE suppression niveau -->
        <b-modal v-model="deleteModal.show" title="Supprimer ce niveau ?"
                 ok-title="Supprimer" ok-variant="danger" cancel-title="Annuler"
                 @ok="executeDelete" centered>
            <p>Le niveau <strong>{{ deleteModal.level?.label }}</strong> et tous ses critères seront supprimés.</p>
        </b-modal>

        <!-- MODALE suppression critère -->
        <b-modal v-model="deleteCriterionModal.show" title="Supprimer ce critère ?"
                 ok-title="Supprimer" ok-variant="danger" cancel-title="Annuler"
                 @ok="executeDeleteCriterion" centered>
            <p>Le critère <strong>« {{ deleteCriterionModal.criterion?.designation }} »</strong> sera supprimé.</p>
        </b-modal>

        <!-- OFFCANVAS IA — Niveaux fréquence -->
        <b-offcanvas v-model="mistralPanel.show" placement="end" title="Assistant IA — Fréquences" style="width:360px">
            <div class="p-2">
                <div class="ai-domain-block mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <small class="text-muted fw-semibold text-uppercase" style="font-size:.68rem">
                            <i class="ti ti-category me-1"></i>Domaines suggérés
                        </small>
                        <button type="button" class="btn btn-link p-0 text-muted" style="font-size:.7rem"
                                @click="showDomains = !showDomains">{{ showDomains ? 'Masquer' : 'Afficher' }}</button>
                    </div>
                    <div v-if="showDomains">
                        <div v-for="cat in DOMAIN_SUGGESTIONS" :key="cat.category" class="mb-2">
                            <small class="text-muted d-block mb-1" style="font-size:.67rem">
                                <i :class="'ti ' + cat.icon + ' me-1'"></i>{{ cat.category }}
                            </small>
                            <div class="d-flex flex-wrap gap-1">
                                <button v-for="item in cat.items" :key="item" type="button"
                                        class="domain-chip" :class="{ 'domain-chip--active': mistralPanel.sector === item }"
                                        @click="mistralPanel.sector = item">{{ item }}</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="ai-sector-box mb-2">
                    <label class="form-label mb-1 fw-semibold" style="font-size:.78rem">
                        <i class="ti ti-robot me-1 text-primary"></i>Secteur d'activité <span class="text-danger">*</span>
                    </label>
                    <b-form-input class="form-control-sm" v-model.trim="mistralPanel.sector" placeholder="ex : agroalimentaire…" />
                </div>
                <div class="mb-3">
                    <label class="form-label mb-1" style="font-size:.78rem">Contexte <small class="text-muted">(optionnel)</small></label>
                    <b-form-textarea class="form-control-sm" rows="2" v-model.trim="mistralPanel.context" placeholder="Taille, région, réglementation…" />
                </div>
                <b-button variant="primary" class="w-100" size="sm" @click="fetchMistralSuggestions"
                          :disabled="mistralPanel.loading || mistralPanel.sector.length < 3">
                    <i class="ti ti-loader-2 ti-spin me-1" v-if="mistralPanel.loading"></i>
                    <i class="ti ti-sparkles me-1" v-else></i>
                    {{ mistralPanel.loading ? 'Génération en cours…' : 'Générer les suggestions' }}
                </b-button>
                <div v-if="mistralPanel.error" class="mt-3 ai-error-box">
                    <div class="text-danger fw-semibold" style="font-size:.8rem">{{ mistralPanel.error }}</div>
                    <b-button size="sm" variant="outline-danger" class="w-100 mt-2" @click="mistralPanel.error = null">
                        <i class="ti ti-refresh me-1"></i>Réessayer
                    </b-button>
                </div>
                <div v-else-if="mistralPanel.suggestions.length" class="mt-3 ai-suggestions-box">
                    <div class="ai-header"><i class="ti ti-sparkles me-1"></i>
                        <strong>{{ mistralPanel.suggestions.length }} suggestions</strong>
                        <small class="text-muted ms-1">— cliquer pour utiliser</small>
                    </div>
                    <div v-for="(s, i) in mistralPanel.suggestions" :key="i"
                         class="suggestion-chip d-flex align-items-center gap-2 mb-2 w-100"
                         @click="applySuggestion(s)" role="button">
                        <span class="fw-bold font-monospace" :style="{ color: s.color_code }">{{ s.score }}</span>
                        <span class="color-dot" :style="{ background: s.color_code }"></span>
                        <div class="flex-fill min-w-0">
                            <span class="fw-semibold d-block">{{ s.label }}</span>
                            <small class="text-muted" v-if="s.recurrence"><i class="ti ti-clock me-1"></i>{{ s.recurrence }}</small>
                        </div>
                        <i class="ti ti-arrow-right small flex-shrink-0"></i>
                    </div>
                </div>
            </div>
        </b-offcanvas>

        <!-- OFFCANVAS IA — Critères fréquence (global) -->
        <b-offcanvas v-model="criteriaPanel.show" placement="end" title="Assistant IA — Critères de fréquence" style="width:420px">
            <div class="p-2">
                <div class="alert alert-info py-2 px-3 mb-3" style="font-size:.78rem">
                    <i class="ti ti-info-circle me-1"></i>
                    Mistral va générer des critères pour <strong>tous les {{ frequencyLevels.length }} niveaux</strong>
                    de fréquence en une seule fois.
                </div>
                <div class="ai-domain-block mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <small class="text-muted fw-semibold text-uppercase" style="font-size:.68rem">
                            <i class="ti ti-category me-1"></i>Domaines suggérés
                        </small>
                        <button type="button" class="btn btn-link p-0 text-muted" style="font-size:.7rem"
                                @click="showCriteriaDomains = !showCriteriaDomains">{{ showCriteriaDomains ? 'Masquer' : 'Afficher' }}</button>
                    </div>
                    <div v-if="showCriteriaDomains">
                        <div v-for="cat in DOMAIN_SUGGESTIONS" :key="cat.category" class="mb-2">
                            <small class="text-muted d-block mb-1" style="font-size:.67rem">
                                <i :class="'ti ' + cat.icon + ' me-1'"></i>{{ cat.category }}
                            </small>
                            <div class="d-flex flex-wrap gap-1">
                                <button v-for="item in cat.items" :key="item" type="button"
                                        class="domain-chip" :class="{ 'domain-chip--active': criteriaPanel.sector === item }"
                                        @click="criteriaPanel.sector = item">{{ item }}</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="ai-sector-box mb-2">
                    <label class="form-label mb-1 fw-semibold" style="font-size:.78rem">
                        <i class="ti ti-robot me-1 text-primary"></i>Secteur d'activité <span class="text-danger">*</span>
                    </label>
                    <b-form-input class="form-control-sm" v-model.trim="criteriaPanel.sector" placeholder="ex : agroalimentaire, banque commerciale…" />
                </div>
                <div class="mb-3">
                    <label class="form-label mb-1" style="font-size:.78rem">Contexte <small class="text-muted">(optionnel)</small></label>
                    <b-form-textarea class="form-control-sm" rows="2" v-model.trim="criteriaPanel.context" placeholder="Taille, région, réglementation applicable…" />
                </div>
                <b-button variant="primary" class="w-100 mb-3" size="sm" @click="fetchCriteriaSuggestions"
                          :disabled="criteriaPanel.loading || criteriaPanel.sector.length < 3">
                    <i class="ti ti-loader-2 ti-spin me-1" v-if="criteriaPanel.loading"></i>
                    <i class="ti ti-sparkles me-1" v-else></i>
                    {{ criteriaPanel.loading ? 'Génération en cours…' : 'Générer les critères' }}
                </b-button>
                <div v-if="criteriaPanel.error" class="ai-error-box mb-2">
                    <div class="text-danger fw-semibold" style="font-size:.8rem">{{ criteriaPanel.error }}</div>
                    <b-button size="sm" variant="outline-danger" class="w-100 mt-2" @click="criteriaPanel.error = null">
                        <i class="ti ti-refresh me-1"></i>Réessayer
                    </b-button>
                </div>
                <div v-if="criteriaPanel.suggestions && Object.keys(criteriaPanel.suggestions).length"
                     class="criteria-suggestions-box">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <small class="fw-semibold" style="font-size:.78rem">
                            <i class="ti ti-sparkles me-1 text-warning"></i>Suggestions générées
                        </small>
                        <b-button size="sm" variant="success" @click="applyAllCriteriaSuggestions" :disabled="criteriaPanel.applying">
                            <i class="ti ti-loader-2 ti-spin me-1" v-if="criteriaPanel.applying"></i>
                            <i class="ti ti-checks me-1" v-else></i>
                            Tout appliquer
                        </b-button>
                    </div>
                    <div v-for="level in sortedLevels" :key="level.id"
                         v-show="criteriaPanel.suggestions[level.id]?.length"
                         class="criteria-suggestion-level mb-2">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="color-dot" :style="{ background: level.color_code }"></span>
                            <span class="fw-semibold small">{{ level.label }}</span>
                            <span v-if="level.recurrence" class="text-muted" style="font-size:.65rem">
                                <i class="ti ti-clock me-1"></i>{{ level.recurrence }}
                            </span>
                            <span class="badge" :style="badgeStyle(level.color_code)">{{ criteriaPanel.suggestions[level.id]?.length ?? 0 }}</span>
                            <b-button size="sm" variant="outline-success" class="ms-auto py-0 px-1" style="font-size:.65rem"
                                      @click="applyLevelCriteriaSuggestions(level)" :disabled="criteriaPanel.applying">
                                Appliquer
                            </b-button>
                        </div>
                        <div v-for="(crit, i) in criteriaPanel.suggestions[level.id]" :key="i" class="criteria-suggestion-item">
                            <div class="fw-semibold" style="font-size:.75rem">{{ crit.designation }}</div>
                            <div v-if="crit.description" class="text-muted" style="font-size:.7rem">{{ crit.description }}</div>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-1 text-end" style="font-size:.67rem">Secteur : {{ criteriaPanel.usedSector }}</small>
                </div>
            </div>
        </b-offcanvas>

    </VerticalLayout>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

const props = defineProps({
    matrixConfigs:    { type: Array,  default: () => [] },
    selectedConfigId: { type: Number, default: null },
    frequencyLevels:  { type: Array,  default: () => [] },
})

const DOMAIN_SUGGESTIONS = [
    { category: 'Finance & Banque',  icon: 'ti-coin',               items: ['Banque commerciale', 'Assurance vie', 'Microfinance', 'Fonds d\'investissement'] },
    { category: 'Agroalimentaire',   icon: 'ti-plant',              items: ['Transformation alimentaire', 'Agriculture et élevage', 'Pêche et aquaculture', 'Distribution alimentaire'] },
    { category: 'Santé',             icon: 'ti-heartbeat',          items: ['Hôpital public', 'Clinique privée', 'Pharmacie', 'Laboratoire médical'] },
    { category: 'Industrie',         icon: 'ti-building-factory',   items: ['Industrie manufacturière', 'BTP et construction', 'Mines et extraction', 'Énergie et utilities'] },
    { category: 'Services',          icon: 'ti-briefcase',          items: ['Télécommunications', 'Transport et logistique', 'Commerce de détail', 'Hôtellerie et tourisme'] },
    { category: 'Secteur public',    icon: 'ti-building-community', items: ['Administration publique', 'Collectivité territoriale', 'ONG et association', 'Établissement scolaire'] },
    { category: 'Technologie',       icon: 'ti-device-laptop',      items: ['Éditeur de logiciels', 'E-commerce', 'Fintech', 'Cybersécurité'] },
]

const recurrenceSuggestions = ['1 fois / 10 ans', '1 fois / 5 ans', '1 fois / an', 'Plusieurs fois / an', 'Mensuel ou plus']

// ─── State niveaux ────────────────────────────────────────────────────────────
const currentConfigId = ref(props.selectedConfigId)
const editingId       = ref(null)
const processing      = ref(false)
const errors          = ref({})
const alert           = ref({ show: false, variant: 'success', message: '' })
const deleteModal     = ref({ show: false, level: null })
const showDomains     = ref(true)

// ─── State critères ───────────────────────────────────────────────────────────
const openCriteriaPanels    = ref({})
const criterionForm         = ref({ levelId: null, criterionId: null, designation: '', description: '', sort_order: 0 })
const criterionErrors       = ref({})
const criterionProcessing   = ref(false)
const criterionDesignationInput = ref(null)
const deleteCriterionModal  = ref({ show: false, level: null, criterion: null })
const dragState             = ref({ levelId: null, fromIdx: null })

// ─── State Mistral ────────────────────────────────────────────────────────────
const mistralPanel = ref({ show: false, sector: '', context: '', loading: false, suggestions: [], error: null, hints: [], hasSearched: false, usedSector: '' })
const criteriaPanel = ref({ show: false, sector: '', context: '', loading: false, applying: false, suggestions: {}, error: null, usedSector: '' })
const showCriteriaDomains = ref(true)

const form = ref({ matrix_config_id: props.selectedConfigId, label: '', score: 1, description: '', recurrence: '', color_code: '#0ea5e9', sort_order: 0 })

// ─── Computed ─────────────────────────────────────────────────────────────────
const selectedConfig  = computed(() => props.matrixConfigs.find(c => c.id === currentConfigId.value) ?? null)
const sortedLevels    = computed(() => [...props.frequencyLevels].sort((a, b) => a.sort_order - b.sort_order || a.score - b.score))
const capacityPercent = computed(() => Math.min(100, Math.round((props.frequencyLevels.length / (selectedConfig.value?.matrix_size ?? 1)) * 100)))
const canAddMore      = computed(() => props.frequencyLevels.length < (selectedConfig.value?.matrix_size ?? 0))
const usedScores      = computed(() => props.frequencyLevels.filter(l => l.id !== editingId.value).map(l => l.score))
const availableScores = computed(() => {
    const max = selectedConfig.value?.matrix_size ?? 5
    return Array.from({ length: max }, (_, i) => i + 1).filter(s => !usedScores.value.includes(s) || s === form.value.score)
})
const totalCriteria = computed(() => props.frequencyLevels.reduce((sum, l) => sum + (l.criteria?.length ?? 0), 0))

// ─── Helpers ─────────────────────────────────────────────────────────────────
const showAlert = (message, variant = 'success') => {
    alert.value = { show: true, variant, message }
    setTimeout(() => { alert.value.show = false }, 4000)
}

const onConfigChange = () => router.get(route('risk.core.frequency.index'), { config_id: currentConfigId.value }, { preserveState: true, preserveScroll: true })

const openForm = (level = null) => {
    editingId.value = level?.id ?? null
    errors.value    = {}
    form.value = level
        ? { matrix_config_id: currentConfigId.value, label: level.label, score: level.score, description: level.description ?? '', recurrence: level.recurrence ?? '', color_code: level.color_code, sort_order: level.sort_order }
        : { matrix_config_id: currentConfigId.value, label: '', score: availableScores.value[0] ?? 1, description: '', recurrence: '', color_code: '#0ea5e9', sort_order: props.frequencyLevels.length }
}

const resetForm = () => {
    editingId.value = null; errors.value = {}
    form.value = { matrix_config_id: currentConfigId.value, label: '', score: availableScores.value[0] ?? 1, description: '', recurrence: '', color_code: '#0ea5e9', sort_order: props.frequencyLevels.length }
}

const submitForm = () => {
    processing.value = true
    const url    = editingId.value ? route('risk.core.frequency.update', editingId.value) : route('risk.core.frequency.store')
    const method = editingId.value ? 'put' : 'post'
    router[method](url, form.value, {
        preserveScroll: true,
        onSuccess: () => { resetForm(); processing.value = false; showAlert('Niveau enregistré avec succès.') },
        onError:   (e) => { errors.value = e; processing.value = false },
    })
}

const confirmDelete = (level) => { deleteModal.value = { show: true, level } }
const executeDelete = () => {
    router.delete(route('risk.core.frequency.destroy', deleteModal.value.level.id), {
        preserveScroll: true,
        onSuccess: () => showAlert('Niveau supprimé.'),
    })
}

const badgeStyle = (colorCode) => ({ background: (colorCode ?? '#0ea5e9') + '22', borderColor: colorCode, color: colorCode })

// ─── Gestion panneau critères ─────────────────────────────────────────────────
const toggleCriteriaPanel = (levelId) => {
    openCriteriaPanels.value = { ...openCriteriaPanels.value, [levelId]: !openCriteriaPanels.value[levelId] }
}

const openAddCriterion = (level) => {
    if (!openCriteriaPanels.value[level.id]) {
        openCriteriaPanels.value = { ...openCriteriaPanels.value, [level.id]: true }
    }
    criterionForm.value = { levelId: level.id, criterionId: null, designation: '', description: '', sort_order: level.criteria?.length ?? 0 }
    criterionErrors.value = {}
    nextTick(() => criterionDesignationInput.value?.focus())
}

const openEditCriterion = (level, criterion) => {
    if (!openCriteriaPanels.value[level.id]) {
        openCriteriaPanels.value = { ...openCriteriaPanels.value, [level.id]: true }
    }
    criterionForm.value = { levelId: level.id, criterionId: criterion.id, designation: criterion.designation, description: criterion.description ?? '', sort_order: criterion.sort_order }
    criterionErrors.value = {}
    nextTick(() => criterionDesignationInput.value?.focus())
}

const cancelCriterion = () => {
    criterionForm.value = { levelId: null, criterionId: null, designation: '', description: '', sort_order: 0 }
    criterionErrors.value = {}
}

const submitCriterion = () => {
    if (!criterionForm.value.designation.trim()) { criterionErrors.value = { designation: 'La désignation est obligatoire.' }; return }
    criterionProcessing.value = true; criterionErrors.value = {}
    const levelId = criterionForm.value.levelId
    const isEdit  = !!criterionForm.value.criterionId
    const url    = isEdit
        ? route('risk.core.frequency.criteria.update', { frequency_level: levelId, criterion: criterionForm.value.criterionId })
        : route('risk.core.frequency.criteria.store',  { frequency_level: levelId })
    router[isEdit ? 'put' : 'post'](url, { designation: criterionForm.value.designation, description: criterionForm.value.description, sort_order: criterionForm.value.sort_order }, {
        preserveScroll: true,
        onSuccess: () => { cancelCriterion(); criterionProcessing.value = false; showAlert(isEdit ? 'Critère modifié.' : 'Critère ajouté.') },
        onError:   (e) => { criterionErrors.value = e; criterionProcessing.value = false },
    })
}

const confirmDeleteCriterion = (level, criterion) => { deleteCriterionModal.value = { show: true, level, criterion } }
const executeDeleteCriterion = () => {
    const { level, criterion } = deleteCriterionModal.value
    router.delete(route('risk.core.frequency.criteria.destroy', { frequency_level: level.id, criterion: criterion.id }), {
        preserveScroll: true, onSuccess: () => showAlert('Critère supprimé.'),
    })
}

const onCriterionDragStart = (event, levelId, fromIdx) => {
    dragState.value = { levelId, fromIdx }
    event.dataTransfer.effectAllowed = 'move'
}

const onCriterionDrop = (event, level) => {
    const { levelId, fromIdx } = dragState.value
    if (levelId !== level.id) return
    const items = [...(level.criteria ?? [])]
    const [moved] = items.splice(fromIdx, 1)
    const rows  = [...event.currentTarget.querySelectorAll('.criterion-row')]
    let toIdx   = rows.length
    for (let i = 0; i < rows.length; i++) {
        const rect = rows[i].getBoundingClientRect()
        if (event.clientY < rect.top + rect.height / 2) { toIdx = i; break }
    }
    items.splice(toIdx, 0, moved)
    router.post(route('risk.core.frequency.criteria.reorder', { frequency_level: level.id }), { items: items.map((c, idx) => ({ id: c.id, sort_order: idx })) }, {
        preserveScroll: true, onSuccess: () => { dragState.value = { levelId: null, fromIdx: null } },
    })
}

// ─── Mistral niveaux ──────────────────────────────────────────────────────────
const openMistralPanel = () => { mistralPanel.value.show = true }

const fetchMistralSuggestions = async () => {
    mistralPanel.value.loading = true; mistralPanel.value.error = null; mistralPanel.value.suggestions = []; mistralPanel.value.hasSearched = false
    try {
        const res = await fetch(route('risk.core.frequency.mistral.suggest'), {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
            body: JSON.stringify({ sector: mistralPanel.value.sector, context: mistralPanel.value.context, matrix_size: selectedConfig.value?.matrix_size ?? 5 }),
        })
        const data = await res.json()
        if (!res.ok) { mistralPanel.value.error = data.message ?? 'Erreur IA.' }
        else { mistralPanel.value.suggestions = data.suggestions ?? []; mistralPanel.value.usedSector = data.sector ?? mistralPanel.value.sector }
    } catch { mistralPanel.value.error = 'Impossible de contacter l\'assistant IA.' }
    finally { mistralPanel.value.loading = false; mistralPanel.value.hasSearched = true }
}

const applySuggestion = (s) => {
    form.value = { matrix_config_id: currentConfigId.value, label: s.label, score: s.score, description: s.description, recurrence: s.recurrence ?? '', color_code: s.color_code, sort_order: s.score - 1 }
    mistralPanel.value.show = false
}

// ─── Mistral critères (global) ────────────────────────────────────────────────
const openCriteriaMistralPanel = () => { criteriaPanel.value.show = true }

const fetchCriteriaSuggestions = async () => {
    criteriaPanel.value.loading = true; criteriaPanel.value.error = null; criteriaPanel.value.suggestions = {}
    try {
        const res = await fetch(route('risk.core.frequency.criteria.mistral.suggest'), {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
            body: JSON.stringify({ sector: criteriaPanel.value.sector, context: criteriaPanel.value.context, matrix_config_id: currentConfigId.value }),
        })
        const data = await res.json()
        if (!res.ok) { criteriaPanel.value.error = data.message ?? 'Erreur IA.' }
        else {
            criteriaPanel.value.suggestions = data.suggestions ?? {}
            criteriaPanel.value.usedSector  = data.sector ?? criteriaPanel.value.sector
            const opened = {}
            sortedLevels.value.forEach(l => { opened[l.id] = true })
            openCriteriaPanels.value = opened
        }
    } catch { criteriaPanel.value.error = 'Impossible de contacter l\'assistant IA.' }
    finally { criteriaPanel.value.loading = false }
}

const applyLevelCriteriaSuggestions = async (level) => {
    const criteria = criteriaPanel.value.suggestions[level.id]
    if (!criteria?.length) return

    criteriaPanel.value.applying = true

    try {
        // Inertia router est un singleton : plusieurs appels router.post en forEach
        // s'annulent mutuellement (seul le dernier est exécuté).
        // On utilise fetch natif avec redirect:'manual' pour ne PAS suivre le
        // back() du contrôleur — évite N requêtes GET parasites qui causeraient
        // un 409 en conflit avec le router.reload() final.
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content

        await Promise.all(criteria.map((crit, idx) =>
            fetch(route('risk.core.frequency.criteria.store', { frequency_level: level.id }), {
                method:   'POST',
                redirect: 'manual',
                headers:  { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body:     JSON.stringify({ designation: crit.designation, description: crit.description ?? '', sort_order: idx }),
            }).then(r => { if (r.status >= 400) throw new Error(`HTTP ${r.status}`) })
        ))

        const updated = { ...criteriaPanel.value.suggestions }
        delete updated[level.id]
        criteriaPanel.value.suggestions = updated

        router.reload({ preserveScroll: true })
        showAlert(`Critères de « ${level.label} » appliqués.`)
    } catch {
        showAlert("Une erreur est survenue lors de l'application des critères.", 'danger')
    } finally {
        criteriaPanel.value.applying = false
    }
}

const applyAllCriteriaSuggestions = async () => {
    criteriaPanel.value.applying = true

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content

        // Toutes les insertions en parallèle via fetch natif.
        // redirect:'manual' empêche fetch de suivre le back() du contrôleur,
        // ce qui évite les requêtes GET concurrentes causant le 409 Inertia.
        const allRequests = []
        for (const level of sortedLevels.value) {
            const criteria = criteriaPanel.value.suggestions[level.id] ?? []
            criteria.forEach((crit, idx) => {
                allRequests.push(
                    fetch(route('risk.core.frequency.criteria.store', { frequency_level: level.id }), {
                        method:   'POST',
                        redirect: 'manual',
                        headers:  { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body:     JSON.stringify({ designation: crit.designation, description: crit.description ?? '', sort_order: idx }),
                    }).then(r => { if (r.status >= 400) throw new Error(`HTTP ${r.status}`) })
                )
            })
        }

        await Promise.all(allRequests)

        criteriaPanel.value.suggestions = {}
        criteriaPanel.value.show        = false
        router.reload({ preserveScroll: true })
        showAlert('Tous les critères ont été appliqués avec succès.')
    } catch {
        showAlert("Une erreur est survenue lors de l'application des critères.", 'danger')
    } finally {
        criteriaPanel.value.applying = false
    }
}
</script>

<style scoped>
.form-control-sm, .form-select-sm { font-size:.75rem; height:26px; padding:.15rem .45rem }
.btn-sm { padding:.15rem .45rem; font-size:.72rem }
.stat-icon { width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:white; font-size:18px }
.stat-card { transition:all .2s }
.stat-card:hover { box-shadow:0 4px 12px rgba(0,0,0,.1); transform:translateY(-2px) }
.pv-table :deep(.p-datatable-thead>tr>th) { background:#f8fafc; border:1px solid #e5e7eb; padding:.25rem .35rem; font-size:.73rem }
.pv-table :deep(.p-datatable-tbody>tr>td) { border:1px solid #eef2f7; padding:.25rem .35rem; font-size:.72rem }
.apt-badge { padding:.1rem .5rem; border-radius:12px; font-size:.7rem; font-weight:700; border:1px solid }
.color-dot { width:10px; height:10px; border-radius:50%; display:inline-block; flex-shrink:0 }
.recurrence-chip { padding:.1rem .45rem; cursor:pointer; font-size:.68rem; border-radius:20px; background:#f0f4ff; color:#3730a3; border:1px solid #c7d2fe; transition:all .15s }
.recurrence-chip:hover { background:#e0e7ff }

/* ── Critères ── */
.criteria-section-header { background:#f8f9fa }
.criteria-level-block { transition:background .15s }
.criteria-level-header { cursor:pointer; background:#fdfdfd; transition:background .15s }
.criteria-level-header:hover { background:#f0f4ff }
.criteria-count-badge { font-size:.62rem; padding:.1rem .4rem; border-radius:10px; border-width:1px; border-style:solid }
.btn-add-criterion { padding:.1rem .3rem; font-size:.7rem; line-height:1.2; border-radius:4px }
.criterion-form-inline { background:#f0f4ff; border:1px dashed #3b5bdb; border-radius:.4rem }
.criterion-form-inline textarea { font-size:.73rem; padding:.2rem .4rem; resize:none }
.criteria-list { display:flex; flex-direction:column; gap:.3rem; padding:.25rem 0 }
.criterion-row { padding:.3rem .4rem; border-radius:.3rem; background:#fafafa; border:1px solid #eef2f7; transition:background .15s }
.criterion-row:hover { background:#f0f4ff; border-color:#c7d7fb }
.criterion-row[draggable="true"] { cursor:grab }
.criterion-row[draggable="true"]:active { cursor:grabbing; opacity:.7 }
.drag-handle { font-size:.8rem; cursor:grab }
.btn-icon { background:none; border:none; padding:.1rem .2rem; font-size:.78rem; cursor:pointer; color:#6c757d; border-radius:3px; line-height:1 }
.btn-icon:hover { background:#e9ecef; color:#212529 }
.btn-icon.text-danger:hover { background:#fff5f5; color:#dc3545 }

/* ── Offcanvas IA niveaux ── */
.ai-domain-block { background:#f0f4ff; border:1px solid #c7d7fb; border-radius:.4rem; padding:.5rem .65rem }
.ai-sector-box   { background:#f8f9fa; border:1px dashed #dee2e6; border-radius:.4rem; padding:.5rem .75rem }
.domain-chip { padding:.1rem .5rem; font-size:.7rem; border-radius:12px; border:1px solid #c7d7fb; background:#fff; color:#3b5bdb; cursor:pointer; transition:all .15s; line-height:1.4 }
.domain-chip:hover       { background:#3b5bdb; color:#fff; border-color:#3b5bdb }
.domain-chip--active     { background:#3b5bdb; color:#fff; border-color:#3b5bdb }
.ai-error-box { background:#fff5f5; border:1px solid #fca5a5; border-radius:.4rem; padding:.65rem .75rem }
.ai-empty-box { background:#f8f9fa; border:1px dashed #dee2e6; border-radius:.4rem; padding:.75rem }
.ai-suggestions-box { background:linear-gradient(135deg,#e3f2fd 0%,#bbdefb 100%); border:2px solid #1976d2; border-radius:.4rem; padding:.6rem .75rem }
.ai-header { font-weight:600; color:#0d47a1; margin-bottom:.5rem; font-size:.82rem }
.suggestion-chip { padding:.3rem .7rem; cursor:pointer; user-select:none; transition:all .2s; font-size:.78rem; border-radius:12px; background:#e3f2fd; color:#1565c0; border:1px solid #90caf9 }
.suggestion-chip:hover { background:#bbdefb; transform:translateY(-1px); box-shadow:0 2px 6px rgba(25,118,210,.2) }

/* ── Offcanvas critères IA ── */
.criteria-suggestions-box { background:#fffbf0; border:2px solid #f59e0b; border-radius:.4rem; padding:.6rem .75rem }
.criteria-suggestion-level { background:#fff; border:1px solid #e5e7eb; border-radius:.35rem; padding:.4rem .6rem }
.criteria-suggestion-item { background:#f8f9fa; border-radius:.25rem; padding:.2rem .5rem; margin-bottom:.2rem; font-size:.73rem; border-left:2px solid #f59e0b }
</style>
