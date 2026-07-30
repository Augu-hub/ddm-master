<template>
    <VerticalLayout>
        <Head title="DDM — Niveaux d'impact" />

        <!-- HEADER -->
        <b-row class="mb-2">
            <b-col class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-flame text-danger fs-5"></i>
                    <h4 class="m-0 fw-semibold">Niveaux d'impact</h4>
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
            <a href="/m/risk.core/matrix-config" class="alert-link ms-1">Créer une configuration →</a>
        </b-alert>

        <template v-else>
            <!-- STATS -->
            <b-row class="g-2 mb-2">
                <b-col md="3">
                    <b-card no-body class="shadow-sm stat-card border-start border-danger border-3">
                        <b-card-body class="p-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="stat-icon bg-danger"><i class="ti ti-flame"></i></div>
                                <div>
                                    <small class="text-muted d-block">Niveaux définis</small>
                                    <h5 class="mb-0 fw-bold">{{ impactLevels.length }} / {{ selectedConfig?.matrix_size ?? '—' }}</h5>
                                </div>
                                <div class="ms-auto">
                                    <span v-if="capacityPercent >= 100" class="badge bg-success">Complet</span>
                                    <span v-else class="badge bg-warning text-dark">En cours</span>
                                </div>
                            </div>
                            <div class="progress mt-2" style="height:4px">
                                <div class="progress-bar" :class="capacityPercent >= 100 ? 'bg-success' : 'bg-danger'"
                                     :style="{ width: capacityPercent + '%' }"></div>
                            </div>
                        </b-card-body>
                    </b-card>
                </b-col>
                <b-col md="3">
                    <b-card no-body class="shadow-sm stat-card border-start border-warning border-3">
                        <b-card-body class="p-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="stat-icon bg-warning"><i class="ti ti-template"></i></div>
                                <div>
                                    <small class="text-muted d-block">Critères définis</small>
                                    <h5 class="mb-0 fw-bold">{{ criteriaTemplates.length }}</h5>
                                </div>
                            </div>
                        </b-card-body>
                    </b-card>
                </b-col>
                <b-col md="3">
                    <b-card no-body class="shadow-sm stat-card border-start border-info border-3">
                        <b-card-body class="p-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="stat-icon bg-info"><i class="ti ti-shield-check"></i></div>
                                <div>
                                    <small class="text-muted d-block">Avec appétence</small>
                                    <h5 class="mb-0 fw-bold">{{ criteriaWithAppetite }} / {{ criteriaTemplates.length }}</h5>
                                </div>
                            </div>
                        </b-card-body>
                    </b-card>
                </b-col>
                <b-col md="3">
                    <b-card no-body class="shadow-sm stat-card border-start border-success border-3">
                        <b-card-body class="p-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="stat-icon bg-success"><i class="ti ti-checklist"></i></div>
                                <div>
                                    <small class="text-muted d-block">Cellules remplies</small>
                                    <h5 class="mb-0 fw-bold">{{ filledCount }} / {{ totalCriteriaInstances }}</h5>
                                </div>
                            </div>
                        </b-card-body>
                    </b-card>
                </b-col>
            </b-row>

            <!-- WORKFLOW -->
            <div class="workflow-steps mb-3">
                <div class="workflow-step"
                     :class="{ 'step-done': criteriaTemplates.length > 0, 'step-active': criteriaTemplates.length === 0 }">
                    <span class="step-num">1</span>
                    <div>
                        <strong>Définir les critères</strong>
                        <small class="d-block text-muted">Désignation + indice + appétence</small>
                    </div>
                </div>
                <div class="workflow-arrow"><i class="ti ti-chevron-right"></i></div>
                <div class="workflow-step"
                     :class="{ 'step-done': impactLevels.length >= (selectedConfig?.matrix_size ?? 0), 'step-active': criteriaTemplates.length > 0 && impactLevels.length < (selectedConfig?.matrix_size ?? 0) }">
                    <span class="step-num">2</span>
                    <div>
                        <strong>Créer les niveaux</strong>
                        <small class="d-block text-muted">Critères associés automatiquement</small>
                    </div>
                </div>
                <div class="workflow-arrow"><i class="ti ti-chevron-right"></i></div>
                <div class="workflow-step"
                     :class="{ 'step-done': filledCount === totalCriteriaInstances && totalCriteriaInstances > 0, 'step-active': impactLevels.length >= (selectedConfig?.matrix_size ?? 0) && filledCount < totalCriteriaInstances }">
                    <span class="step-num">3</span>
                    <div>
                        <strong>Remplir avec l'IA</strong>
                        <small class="d-block text-muted">Tolérance calibrée par appétence</small>
                    </div>
                </div>
            </div>

            <b-alert v-if="alert.show" :variant="alert.variant" show dismissible
                     @dismissed="alert.show = false" class="py-2 px-3 mb-2">
                {{ alert.message }}
            </b-alert>

            <b-row class="g-2">

                <!-- ═══ COLONNE GAUCHE ═══ -->
                <b-col lg="5">

                    <!-- BLOC 1 : Templates de critères -->
                    <b-card no-body class="shadow-sm mb-2">
                        <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="ti ti-template me-1 text-warning"></i>
                                Étape 1 — Critères communs
                                <b-badge v-if="criteriaTemplates.length" pill class="bg-warning text-dark ms-1">
                                    {{ criteriaTemplates.length }}
                                </b-badge>
                            </h6>
                            <small class="text-muted">Partagés par tous les niveaux</small>
                        </b-card-header>
                        <b-card-body class="p-3">

                            <!-- Liste des critères existants -->
                            <div v-if="criteriaTemplates.length" class="mb-3">
                                <div v-for="(tpl, idx) in criteriaTemplates" :key="tpl.id"
                                     class="template-row mb-2"
                                     draggable="true"
                                     @dragstart="onTemplateDragStart($event, idx)"
                                     @dragover.prevent
                                     @drop.prevent="onTemplateDrop($event, idx)">

                                    <!-- Mode affichage -->
                                    <div v-if="editingTemplateId !== tpl.id" class="d-flex align-items-start gap-2">
                                        <i class="ti ti-grip-vertical drag-handle text-muted mt-1 flex-shrink-0"></i>
                                        <div class="flex-fill min-w-0">
                                            <div class="fw-semibold small">{{ tpl.designation }}</div>
                                            <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                                                <span v-if="tpl.appetite_id"
                                                      class="apt-badge"
                                                      :style="appetiteBadgeStyle(tpl.appetite_id)">
                                                    <i class="ti ti-shield-check me-1"></i>
                                                    {{ getAppetiteLabel(tpl.appetite_id) }}
                                                </span>
                                                <span v-else class="badge bg-light text-muted border"
                                                      style="font-size:.65rem; cursor:pointer"
                                                      @click="startEditTemplate(tpl)"
                                                      title="Cliquer pour assigner une appétence">
                                                    <i class="ti ti-shield me-1"></i>Sans appétence
                                                </span>
                                                <small v-if="tpl.hint" class="text-muted" style="font-size:.65rem">
                                                    <i class="ti ti-bulb me-1 text-warning"></i>{{ tpl.hint }}
                                                </small>
                                            </div>
                                            <div v-if="tpl.appetite_description" class="appetite-desc-box mt-1">
                                                <small style="font-size:.67rem; color:#374151">
                                                    {{ tpl.appetite_description }}
                                                </small>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-1 flex-shrink-0">
                                            <button class="btn-icon" @click="startEditTemplate(tpl)" title="Modifier">
                                                <i class="ti ti-pencil"></i>
                                            </button>
                                            <button class="btn-icon text-danger" @click="confirmDeleteTemplate(tpl)" title="Supprimer">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Mode édition inline -->
                                    <div v-else class="d-flex flex-column gap-2">
                                        <div>
                                            <label class="form-label mb-1" style="font-size:.72rem">
                                                Désignation <span class="text-danger">*</span>
                                            </label>
                                            <input v-model.trim="templateEditForm.designation"
                                                   class="form-control form-control-sm"
                                                   placeholder="Nom du critère" />
                                        </div>
                                        <div>
                                            <label class="form-label mb-1" style="font-size:.72rem">
                                                Indice pour l'IA <small class="text-muted">(optionnel)</small>
                                            </label>
                                            <input v-model.trim="templateEditForm.hint"
                                                   class="form-control form-control-sm"
                                                   placeholder="ex : impact financier en %, nb personnes affectées…" />
                                        </div>

                                        <div class="border-top pt-2">
                                            <label class="form-label mb-1 fw-semibold" style="font-size:.72rem">
                                                <i class="ti ti-shield-check me-1 text-info"></i>
                                                Appétence au risque
                                                <small class="text-muted fw-normal">(1 critère = 1 appétence)</small>
                                            </label>
                                            <select v-model="templateEditForm.appetite_id"
                                                    class="form-select form-select-sm mb-2"
                                                    @change="onAppetiteSelectChange(tpl)">
                                                <option :value="null">— Aucune appétence —</option>
                                                <option v-for="apt in appetites" :key="apt.id" :value="apt.id">
                                                    {{ apt.label }} ({{ apt.score_min }}–{{ apt.score_max }})
                                                </option>
                                            </select>

                                            <div v-if="templateEditForm.appetite_description" class="appetite-desc-box">
                                                <small class="text-muted fw-semibold d-block mb-1" style="font-size:.68rem">
                                                    <i class="ti ti-info-circle me-1"></i>Tolérance définie
                                                </small>
                                                <p class="mb-0" style="font-size:.72rem; color:#374151">
                                                    {{ templateEditForm.appetite_description }}
                                                </p>
                                            </div>
                                            <div v-else-if="templateEditForm.appetite_id && appetiteSaving"
                                                 class="text-muted" style="font-size:.72rem">
                                                <i class="ti ti-loader-2 ti-spin me-1"></i>Chargement…
                                            </div>
                                            <div v-else-if="!templateEditForm.appetite_id"
                                                 class="text-muted" style="font-size:.68rem">
                                                <i class="ti ti-info-circle me-1"></i>
                                                Sélectionnez une appétence pour voir sa description.
                                            </div>
                                        </div>

                                        <div class="d-flex gap-1 justify-content-end border-top pt-2">
                                            <b-button size="sm" variant="light" @click="cancelEditTemplate">
                                                <i class="ti ti-x me-1"></i>Annuler
                                            </b-button>
                                            <b-button size="sm" variant="primary"
                                                      @click="submitEditTemplate(tpl)"
                                                      :disabled="!templateEditForm.designation.trim()">
                                                <i class="ti ti-device-floppy me-1"></i>Enregistrer
                                            </b-button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="text-muted text-center py-2 mb-3" style="font-size:.78rem">
                                <i class="ti ti-template opacity-25 d-block fs-3 mb-1"></i>
                                Ajoutez les critères qui s'appliqueront à <strong>tous</strong> les niveaux.
                            </div>

                            <!-- Formulaire ajout critère -->
                            <div class="template-add-form p-2 rounded border">
                                <div class="mb-1">
                                    <input v-model.trim="templateForm.designation"
                                           class="form-control form-control-sm"
                                           placeholder="Nom du critère * (ex : Impact financier)"
                                           @keydown.enter.prevent="submitTemplate" />
                                    <div v-if="templateErrors.designation" class="text-danger" style="font-size:.7rem">
                                        {{ templateErrors.designation }}
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <input v-model.trim="templateForm.hint"
                                           class="form-control form-control-sm"
                                           placeholder="Indice pour l'IA (optionnel)" />
                                </div>
                                <b-button size="sm" variant="warning" class="w-100" @click="submitTemplate"
                                          :disabled="templateProcessing || !templateForm.designation">
                                    <i class="ti ti-loader-2 ti-spin me-1" v-if="templateProcessing"></i>
                                    <i class="ti ti-plus me-1" v-else></i>
                                    Ajouter ce critère
                                </b-button>
                            </div>
                        </b-card-body>
                    </b-card>

                    <!-- BLOC 2 : Formulaire niveau -->
                    <b-card no-body class="shadow-sm">
                        <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="ti ti-flame me-1 text-danger"></i>
                                Étape 2 — {{ editingId ? 'Modifier le niveau' : "Ajouter un niveau d'impact" }}
                            </h6>
                            <span v-if="editingId" class="badge bg-warning text-dark">Mode édition</span>
                        </b-card-header>
                        <b-card-body class="p-3">
                            <b-alert v-if="!criteriaTemplates.length" variant="info" show
                                     class="py-2 px-3 mb-3" style="font-size:.78rem">
                                <i class="ti ti-info-circle me-1"></i>
                                Conseil : définissez d'abord les critères (étape 1) avant de créer les niveaux.
                            </b-alert>
                            <b-form @submit.prevent="submitForm">
                                <b-row class="g-2">
                                    <b-col cols="8">
                                        <label class="form-label mb-1">Libellé <span class="text-danger">*</span></label>
                                        <b-form-input class="form-control-sm" v-model.trim="form.label"
                                                      placeholder="ex : Catastrophique" required />
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
                                        <b-form-textarea class="form-control-sm" rows="2"
                                                         v-model.trim="form.description"
                                                         placeholder="Décrivez ce niveau d'impact…" />
                                    </b-col>
                                    <b-col cols="12">
                                        <label class="form-label mb-1">Couleur</label>
                                        <div class="d-flex gap-2 align-items-center">
                                            <input type="color" v-model="form.color_code"
                                                   class="form-control form-control-sm form-control-color p-0"
                                                   style="width:36px;height:28px" />
                                            <b-form-input class="form-control-sm font-monospace"
                                                          v-model="form.color_code"
                                                          placeholder="#ef4444" style="max-width:100px" />
                                            <span class="apt-badge" :style="badgeStyle(form.color_code)">
                                                {{ form.label || 'Aperçu' }}
                                            </span>
                                        </div>
                                    </b-col>
                                    <b-col cols="12" class="d-flex justify-content-between align-items-center pt-1">
                                        <b-button size="sm" variant="light" @click="resetForm">
                                            <i class="ti ti-x me-1"></i>Annuler
                                        </b-button>
                                        <div class="d-flex gap-1">
                                            <b-button size="sm" variant="outline-primary" @click="openMistralPanel">
                                                <i class="ti ti-sparkles me-1"></i>IA niveaux
                                            </b-button>
                                            <b-button size="sm" variant="danger" type="submit"
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

                <!-- ═══ COLONNE DROITE : matrice critères × niveaux ═══ -->
                <b-col lg="7">
                    <b-card no-body class="shadow-sm">
                        <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="ti ti-table me-1"></i>
                                Étape 3 — Matrice critères × niveaux
                            </h6>
                            <b-button v-if="impactLevels.length && criteriaTemplates.length"
                                      size="sm" variant="outline-primary"
                                      @click="openContentPanel">
                                <i class="ti ti-sparkles me-1"></i>Remplir avec l'IA
                            </b-button>
                        </b-card-header>
                        <b-card-body class="p-0">

                            <div v-if="!criteriaTemplates.length" class="text-center text-muted py-5">
                                <i class="ti ti-template fs-1 opacity-25 d-block mb-2"></i>
                                <p class="mb-0">Définissez d'abord les critères <strong>(étape 1)</strong>.</p>
                            </div>
                            <div v-else-if="!impactLevels.length" class="text-center text-muted py-5">
                                <i class="ti ti-flame fs-1 opacity-25 d-block mb-2"></i>
                                <p class="mb-0">Créez les niveaux d'impact <strong>(étape 2)</strong>.</p>
                            </div>

                            <div v-else>
                                <!-- Matrice critères × niveaux -->
                                <div class="criteria-matrix">
                                    <table class="table table-bordered table-sm mb-0 criteria-table">
                                        <thead>
                                            <tr>
                                                <th class="criteria-name-col">
                                                    <div style="font-size:.68rem; color:#6b7280">
                                                        Critère / Appétence
                                                    </div>
                                                </th>
                                                <th v-for="level in sortedLevels" :key="level.id"
                                                    class="level-col text-center"
                                                    :style="{ borderTop: '3px solid ' + level.color_code }">
                                                    <div class="d-flex flex-column align-items-center gap-1">
                                                        <span class="apt-badge" :style="badgeStyle(level.color_code)">
                                                            {{ level.label }}
                                                        </span>
                                                        <small class="text-muted font-monospace" style="font-size:.6rem">
                                                            S{{ level.score }}
                                                        </small>
                                                    </div>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="tpl in criteriaTemplates" :key="tpl.id">
                                                <td class="criteria-name-col">
                                                    <div class="fw-semibold" style="font-size:.72rem">
                                                        {{ tpl.designation }}
                                                    </div>
                                                    <div class="mt-1">
                                                        <span v-if="tpl.appetite_id"
                                                              class="apt-badge"
                                                              :style="appetiteBadgeStyle(tpl.appetite_id)">
                                                            <i class="ti ti-shield-check me-1"></i>
                                                            {{ getAppetiteLabel(tpl.appetite_id) }}
                                                        </span>
                                                        <span v-else class="text-muted" style="font-size:.62rem">
                                                            <i class="ti ti-shield me-1"></i>—
                                                        </span>
                                                    </div>
                                                    <small v-if="tpl.hint" class="text-muted d-block mt-1"
                                                           style="font-size:.62rem">
                                                        <i class="ti ti-bulb me-1 text-warning"></i>{{ tpl.hint }}
                                                    </small>
                                                </td>
                                                <td v-for="level in sortedLevels" :key="level.id"
                                                    class="level-col criterion-cell"
                                                    :class="{
                                                        'cell-filled': !!getCriterionContent(level, tpl),
                                                        'cell-empty':  !getCriterionContent(level, tpl)
                                                    }"
                                                    @click="openCriterionEdit(level, tpl)">
                                                    <div v-if="getCriterionContent(level, tpl)"
                                                         class="criterion-content">
                                                        {{ getCriterionContent(level, tpl) }}
                                                    </div>
                                                    <div v-else class="criterion-empty">
                                                        <i class="ti ti-plus opacity-25"></i>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Graduation -->
                                <div class="p-3 border-top">
                                    <small class="text-muted fw-semibold text-uppercase d-block mb-2">Graduation</small>
                                    <div class="d-flex rounded overflow-hidden" style="height:28px">
                                        <div v-for="level in sortedLevels" :key="level.id"
                                             class="flex-fill d-flex align-items-center justify-content-center small fw-semibold text-white"
                                             :style="{ backgroundColor: level.color_code }"
                                             :title="'Score ' + level.score + ' — ' + level.label">
                                            {{ level.label }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions niveaux -->
                                <div class="p-3 border-top">
                                    <small class="text-muted fw-semibold text-uppercase d-block mb-2">
                                        Actions par niveau
                                    </small>
                                    <DataTable :value="sortedLevels" size="small" class="pv-table flat">
                                        <Column header="Score" style="width:55px" bodyClass="text-center">
                                            <template #body="{data}">
                                                <span class="fw-bold font-monospace fs-6"
                                                      :style="{ color: data.color_code }">
                                                    {{ data.score }}
                                                </span>
                                            </template>
                                        </Column>
                                        <Column header="Libellé">
                                            <template #body="{data}">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="color-dot" :style="{ background: data.color_code }"></span>
                                                    <span class="fw-semibold">{{ data.label }}</span>
                                                </div>
                                            </template>
                                        </Column>
                                        <Column header="Critères" bodyClass="text-center">
                                            <template #body="{data}">
                                                <span :class="filledForLevel(data) === criteriaTemplates.length
                                                              ? 'text-success fw-bold' : 'text-warning'">
                                                    {{ filledForLevel(data) }} / {{ criteriaTemplates.length }}
                                                </span>
                                            </template>
                                        </Column>
                                        <Column header="" style="width:80px" bodyClass="text-end pe-2">
                                            <template #body="{data}">
                                                <b-button size="sm" variant="light" class="me-1" @click="openForm(data)">
                                                    <i class="ti ti-pencil"></i>
                                                </b-button>
                                                <b-button size="sm" variant="light" class="text-danger" @click="confirmDelete(data)">
                                                    <i class="ti ti-trash"></i>
                                                </b-button>
                                            </template>
                                        </Column>
                                    </DataTable>
                                </div>
                            </div>
                        </b-card-body>
                    </b-card>
                </b-col>
            </b-row>
        </template>

        <!-- ════ MODALES ════ -->

        <!-- Édition cellule -->
        <b-modal v-model="criterionEditModal.show"
                 :title="'« ' + (criterionEditModal.template?.designation ?? '') + ' » — ' + (criterionEditModal.level?.label ?? '')"
                 ok-title="Enregistrer" ok-variant="primary" cancel-title="Annuler"
                 @ok="saveCriterionContent" centered size="lg">
            <div v-if="criterionEditModal.level && criterionEditModal.template">
                <div class="d-flex gap-2 align-items-center mb-3 flex-wrap">
                    <span class="color-dot" :style="{ background: criterionEditModal.level.color_code }"></span>
                    <strong>{{ criterionEditModal.level.label }}</strong>
                    <small class="text-muted font-monospace">Score {{ criterionEditModal.level.score }}</small>
                    <span v-if="criterionEditModal.template.appetite_id"
                          class="ms-auto apt-badge"
                          :style="appetiteBadgeStyle(criterionEditModal.template.appetite_id)">
                        <i class="ti ti-shield-check me-1"></i>
                        {{ getAppetiteLabel(criterionEditModal.template.appetite_id) }}
                    </span>
                    <span v-if="criterionEditModal.template.hint"
                          class="badge bg-warning text-dark"
                          :class="criterionEditModal.template.appetite_id ? '' : 'ms-auto'">
                        <i class="ti ti-bulb me-1"></i>{{ criterionEditModal.template.hint }}
                    </span>
                </div>
                <div v-if="criterionEditModal.template.appetite_description" class="appetite-desc-box mb-3">
                    <small class="text-muted fw-semibold d-block mb-1" style="font-size:.68rem">
                        <i class="ti ti-info-circle me-1"></i>Tolérance définie pour ce critère
                    </small>
                    <p class="mb-0" style="font-size:.72rem; color:#374151">
                        {{ criterionEditModal.template.appetite_description }}
                    </p>
                </div>
                <b-form-textarea v-model="criterionEditModal.description" rows="6"
                                 placeholder="Décrivez ce critère pour ce niveau d'impact…" />
            </div>
        </b-modal>

        <!-- Suppression niveau -->
        <b-modal v-model="deleteModal.show" title="Supprimer ce niveau ?"
                 ok-title="Supprimer" ok-variant="danger" cancel-title="Annuler"
                 @ok="executeDelete" centered>
            <p>Le niveau <strong>{{ deleteModal.level?.label }}</strong> et tous ses critères seront supprimés.</p>
        </b-modal>

        <!-- Suppression template -->
        <b-modal v-model="deleteTemplateModal.show" title="Supprimer ce critère ?"
                 ok-title="Supprimer" ok-variant="danger" cancel-title="Annuler"
                 @ok="executeDeleteTemplate" centered>
            <p>Le critère <strong>« {{ deleteTemplateModal.template?.designation }} »</strong>
               sera supprimé de <strong>tous les niveaux</strong>.</p>
        </b-modal>

        <!-- ════ OFFCANVAS IA — suggestions niveaux ════ -->
        <b-offcanvas v-model="mistralPanel.show" placement="end"
                     title="Assistant IA — Niveaux d'impact" style="width:360px">
            <div class="p-2">
                <div class="ai-domain-block mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <small class="text-muted fw-semibold text-uppercase" style="font-size:.68rem">
                            <i class="ti ti-category me-1"></i>Domaines suggérés
                        </small>
                        <button type="button" class="btn btn-link p-0 text-muted" style="font-size:.7rem"
                                @click="showDomains = !showDomains">
                            {{ showDomains ? 'Masquer' : 'Afficher' }}
                        </button>
                    </div>
                    <div v-if="showDomains">
                        <div v-for="cat in DOMAIN_SUGGESTIONS" :key="cat.category" class="mb-2">
                            <small class="text-muted d-block mb-1" style="font-size:.67rem">
                                <i :class="'ti ' + cat.icon + ' me-1'"></i>{{ cat.category }}
                            </small>
                            <div class="d-flex flex-wrap gap-1">
                                <button v-for="item in cat.items" :key="item" type="button"
                                        class="domain-chip"
                                        :class="{ 'domain-chip--active': mistralPanel.sector === item }"
                                        @click="mistralPanel.sector = item">{{ item }}</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="ai-sector-box mb-2">
                    <label class="form-label mb-1 fw-semibold" style="font-size:.78rem">
                        <i class="ti ti-robot me-1 text-primary"></i>Secteur <span class="text-danger">*</span>
                    </label>
                    <b-form-input class="form-control-sm" v-model.trim="mistralPanel.sector"
                                  placeholder="ex : agroalimentaire…" />
                </div>
                <div class="mb-3">
                    <label class="form-label mb-1" style="font-size:.78rem">
                        Contexte <small class="text-muted">(optionnel)</small>
                    </label>
                    <b-form-textarea class="form-control-sm" rows="2" v-model.trim="mistralPanel.context"
                                     placeholder="Taille, région, réglementation…" />
                </div>
                <b-button variant="primary" class="w-100" size="sm" @click="fetchMistralSuggestions"
                          :disabled="mistralPanel.loading || mistralPanel.sector.length < 3">
                    <i class="ti ti-loader-2 ti-spin me-1" v-if="mistralPanel.loading"></i>
                    <i class="ti ti-sparkles me-1" v-else></i>
                    {{ mistralPanel.loading ? 'Génération…' : 'Générer les suggestions' }}
                </b-button>
                <div v-if="mistralPanel.error" class="mt-3 ai-error-box">
                    <div class="text-danger fw-semibold" style="font-size:.8rem">{{ mistralPanel.error }}</div>
                </div>
                <div v-else-if="mistralPanel.suggestions.length" class="mt-3 ai-suggestions-box">
                    <div class="ai-header">
                        <i class="ti ti-sparkles me-1"></i>
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
                            <small class="text-muted text-truncate d-block" v-if="s.description">
                                {{ s.description.substring(0,60) }}{{ s.description.length > 60 ? '…' : '' }}
                            </small>
                        </div>
                        <i class="ti ti-arrow-right small flex-shrink-0"></i>
                    </div>
                </div>
            </div>
        </b-offcanvas>

        <!-- ════ OFFCANVAS IA — contenu critères ════ -->
        <b-offcanvas v-model="contentPanel.show" placement="end"
                     title="Assistant IA — Contenu des critères" style="width:460px">
            <div class="p-2">
                <div class="alert alert-info py-2 px-3 mb-3" style="font-size:.78rem">
                    <i class="ti ti-info-circle me-1"></i>
                    L'IA va générer pour chacun des <strong>{{ impactLevels.length }} niveaux</strong>
                    une description pour les <strong>{{ criteriaTemplates.length }} critères</strong>,
                    en tenant compte des appétences définies.
                </div>

                <div class="matrix-preview mb-3">
                    <small class="text-muted fw-semibold text-uppercase d-block mb-2" style="font-size:.67rem">
                        Critères et appétences
                    </small>
                    <div class="d-flex flex-column gap-1">
                        <div v-for="tpl in criteriaTemplates" :key="tpl.id"
                             class="d-flex align-items-center gap-2">
                            <span class="badge bg-light border text-dark" style="font-size:.67rem">
                                {{ tpl.designation }}
                            </span>
                            <span v-if="tpl.appetite_id"
                                  class="apt-badge" :style="appetiteBadgeStyle(tpl.appetite_id)"
                                  style="font-size:.63rem">
                                <i class="ti ti-shield-check me-1"></i>
                                {{ getAppetiteLabel(tpl.appetite_id) }}
                            </span>
                            <span v-else class="text-muted" style="font-size:.63rem">
                                <i class="ti ti-shield me-1"></i>sans appétence
                            </span>
                        </div>
                    </div>
                </div>

                <div class="ai-domain-block mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <small class="text-muted fw-semibold text-uppercase" style="font-size:.68rem">
                            <i class="ti ti-category me-1"></i>Domaines suggérés
                        </small>
                        <button type="button" class="btn btn-link p-0 text-muted" style="font-size:.7rem"
                                @click="showContentDomains = !showContentDomains">
                            {{ showContentDomains ? 'Masquer' : 'Afficher' }}
                        </button>
                    </div>
                    <div v-if="showContentDomains">
                        <div v-for="cat in DOMAIN_SUGGESTIONS" :key="cat.category" class="mb-2">
                            <small class="text-muted d-block mb-1" style="font-size:.67rem">
                                <i :class="'ti ' + cat.icon + ' me-1'"></i>{{ cat.category }}
                            </small>
                            <div class="d-flex flex-wrap gap-1">
                                <button v-for="item in cat.items" :key="item" type="button"
                                        class="domain-chip"
                                        :class="{ 'domain-chip--active': contentPanel.sector === item }"
                                        @click="contentPanel.sector = item">{{ item }}</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ai-sector-box mb-2">
                    <label class="form-label mb-1 fw-semibold" style="font-size:.78rem">
                        <i class="ti ti-robot me-1 text-primary"></i>Secteur <span class="text-danger">*</span>
                    </label>
                    <b-form-input class="form-control-sm" v-model.trim="contentPanel.sector"
                                  placeholder="ex : agroalimentaire, banque commerciale…" />
                </div>
                <div class="mb-3">
                    <label class="form-label mb-1" style="font-size:.78rem">
                        Contexte <small class="text-muted">(optionnel)</small>
                    </label>
                    <b-form-textarea class="form-control-sm" rows="2" v-model.trim="contentPanel.context"
                                     placeholder="Taille, région, réglementation…" />
                </div>

                <b-button variant="primary" class="w-100 mb-3" size="sm" @click="fetchCriteriaContent"
                          :disabled="contentPanel.loading || contentPanel.sector.length < 3">
                    <i class="ti ti-loader-2 ti-spin me-1" v-if="contentPanel.loading"></i>
                    <i class="ti ti-sparkles me-1" v-else></i>
                    {{ contentPanel.loading ? 'Génération en cours…' : 'Générer le contenu' }}
                </b-button>

                <div v-if="contentPanel.error" class="ai-error-box mb-2">
                    <div class="text-danger fw-semibold" style="font-size:.8rem">{{ contentPanel.error }}</div>
                </div>

                <div v-if="contentPanel.hasSuggestions" class="criteria-suggestions-box">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <small class="fw-semibold" style="font-size:.78rem">
                            <i class="ti ti-sparkles me-1 text-warning"></i>Contenu généré
                            <small class="text-muted ms-1">— {{ contentPanel.usedSector }}</small>
                        </small>
                        <b-button size="sm" variant="success" @click="applyAllContent"
                                  :disabled="contentPanel.applying">
                            <i class="ti ti-loader-2 ti-spin me-1" v-if="contentPanel.applying"></i>
                            <i class="ti ti-checks me-1" v-else></i>
                            Tout appliquer
                        </b-button>
                    </div>
                    <div v-for="level in sortedLevels" :key="level.id" class="content-preview-level mb-2">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="color-dot" :style="{ background: level.color_code }"></span>
                            <span class="fw-semibold small">{{ level.label }}</span>
                            <span class="badge ms-auto bg-light border text-dark" style="font-size:.65rem">
                                {{ Object.keys(contentPanel.suggestions[level.id] ?? {}).length }}
                                / {{ criteriaTemplates.length }}
                            </span>
                        </div>
                        <div v-for="tpl in criteriaTemplates" :key="tpl.id" class="content-preview-item">
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <span class="text-muted fw-semibold" style="font-size:.68rem">{{ tpl.designation }}</span>
                                <span v-if="tpl.appetite_id"
                                      class="ms-auto apt-badge" :style="appetiteBadgeStyle(tpl.appetite_id)"
                                      style="font-size:.6rem">
                                    {{ getAppetiteLabel(tpl.appetite_id) }}
                                </span>
                            </div>
                            <div style="font-size:.72rem">
                                {{ contentPanel.suggestions[level.id]?.[tpl.id] ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </b-offcanvas>

    </VerticalLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

const props = defineProps({
    matrixConfigs:     { type: Array,  default: () => [] },
    selectedConfigId:  { type: Number, default: null },
    impactLevels:      { type: Array,  default: () => [] },
    criteriaTemplates: { type: Array,  default: () => [] },
    appetites:         { type: Array,  default: () => [] },
})

const DOMAIN_SUGGESTIONS = [
    { category: 'Finance & Banque',  icon: 'ti-coin',               items: ['Banque commerciale', 'Assurance vie', 'Microfinance', "Fonds d'investissement"] },
    { category: 'Agroalimentaire',   icon: 'ti-plant',              items: ['Transformation alimentaire', 'Agriculture et élevage', 'Pêche et aquaculture', 'Distribution alimentaire'] },
    { category: 'Santé',             icon: 'ti-heartbeat',          items: ['Hôpital public', 'Clinique privée', 'Pharmacie', 'Laboratoire médical'] },
    { category: 'Industrie',         icon: 'ti-building-factory',   items: ['Industrie manufacturière', 'BTP et construction', 'Mines et extraction', 'Énergie et utilities'] },
    { category: 'Services',          icon: 'ti-briefcase',          items: ['Télécommunications', 'Transport et logistique', 'Commerce de détail', 'Hôtellerie et tourisme'] },
    { category: 'Secteur public',    icon: 'ti-building-community', items: ['Administration publique', 'Collectivité territoriale', 'ONG et association', 'Établissement scolaire'] },
    { category: 'Technologie',       icon: 'ti-device-laptop',      items: ['Éditeur de logiciels', 'E-commerce', 'Fintech', 'Cybersécurité'] },
]

// ─── State niveaux ────────────────────────────────────────────────────────────
const currentConfigId = ref(props.selectedConfigId)
const editingId       = ref(null)
const processing      = ref(false)
const errors          = ref({})
const alert           = ref({ show: false, variant: 'success', message: '' })
const deleteModal     = ref({ show: false, level: null })
const showDomains     = ref(true)
const form            = ref({
    matrix_config_id: props.selectedConfigId,
    label: '', score: 1, description: '', color_code: '#6b7280', sort_order: 0,
})

// ─── State templates ──────────────────────────────────────────────────────────
const templateForm        = ref({ designation: '', hint: '' })
const templateErrors      = ref({})
const templateProcessing  = ref(false)
const editingTemplateId   = ref(null)
const templateEditForm    = ref({ designation: '', hint: '', appetite_id: null, appetite_description: null })
const appetiteSaving      = ref(false)
const deleteTemplateModal = ref({ show: false, template: null })
const templateDragIdx     = ref(null)

// ─── State édition cellule ────────────────────────────────────────────────────
const criterionEditModal = ref({
    show: false, level: null, template: null, criterionId: null, description: '',
})

// ─── State IA contenu ─────────────────────────────────────────────────────────
const contentPanel = ref({
    show: false, sector: '', context: '',
    loading: false, applying: false,
    suggestions: {}, error: null, usedSector: '', hasSuggestions: false,
})
const showContentDomains = ref(true)

// ─── State IA niveaux ─────────────────────────────────────────────────────────
const mistralPanel = ref({
    show: false, sector: '', context: '', loading: false, suggestions: [], error: null,
})

// ─── Computed ─────────────────────────────────────────────────────────────────
const selectedConfig  = computed(() => props.matrixConfigs.find(c => c.id === currentConfigId.value) ?? null)
const sortedLevels    = computed(() => [...props.impactLevels].sort((a, b) => a.sort_order - b.sort_order || a.score - b.score))
const capacityPercent = computed(() => Math.min(100, Math.round((props.impactLevels.length / (selectedConfig.value?.matrix_size ?? 1)) * 100)))
const canAddMore      = computed(() => props.impactLevels.length < (selectedConfig.value?.matrix_size ?? 0))
const usedScores      = computed(() => props.impactLevels.filter(l => l.id !== editingId.value).map(l => l.score))
const availableScores = computed(() => {
    const max = selectedConfig.value?.matrix_size ?? 5
    return Array.from({ length: max }, (_, i) => i + 1).filter(s => !usedScores.value.includes(s) || s === form.value.score)
})
const totalCriteriaInstances = computed(() => props.impactLevels.length * props.criteriaTemplates.length)
const filledCount = computed(() =>
    props.impactLevels.reduce((sum, l) =>
        sum + (l.criteria ?? []).filter(c => c.description?.trim()).length, 0)
)
const criteriaWithAppetite = computed(() => props.criteriaTemplates.filter(t => t.appetite_id).length)

const filledForLevel = (level) => (level.criteria ?? []).filter(c => c.description?.trim()).length
const getCriterionContent = (level, tpl) =>
    (level.criteria ?? []).find(c => c.template_id === tpl.id)?.description ?? null
const getCriterionId = (level, tpl) =>
    (level.criteria ?? []).find(c => c.template_id === tpl.id)?.id ?? null

// ─── Appétences ───────────────────────────────────────────────────────────────
const appetiteMap = computed(() => {
    const m = {}; props.appetites.forEach(a => { m[a.id] = a }); return m
})
const getAppetiteLabel = (id) => appetiteMap.value[id]?.label ?? '—'
const getAppetiteColor = (id) => appetiteMap.value[id]?.color ?? '#6c757d'
const appetiteBadgeStyle = (id) => {
    const color = getAppetiteColor(id)
    return { background: color + '22', borderColor: color, color, border: '1px solid', borderRadius: '12px', padding: '.1rem .5rem', fontSize: '.68rem', fontWeight: 700, display: 'inline-flex', alignItems: 'center' }
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content
const showAlert = (message, variant = 'success') => {
    alert.value = { show: true, variant, message }
    setTimeout(() => { alert.value.show = false }, 4000)
}
const badgeStyle = (colorCode) => ({
    background: (colorCode ?? '#6b7280') + '22', borderColor: colorCode, color: colorCode,
    border: '1px solid', borderRadius: '12px', padding: '.1rem .5rem', fontSize: '.7rem', fontWeight: 700,
})
const onConfigChange = () =>
    router.get('/m/risk.core/impact', { config_id: currentConfigId.value }, { preserveState: true, preserveScroll: true })

// ─── Niveaux ──────────────────────────────────────────────────────────────────
const openForm = (level = null) => {
    editingId.value = level?.id ?? null; errors.value = {}
    form.value = level
        ? { matrix_config_id: currentConfigId.value, label: level.label, score: level.score, description: level.description ?? '', color_code: level.color_code, sort_order: level.sort_order }
        : { matrix_config_id: currentConfigId.value, label: '', score: availableScores.value[0] ?? 1, description: '', color_code: '#6b7280', sort_order: props.impactLevels.length }
}
const resetForm = () => {
    editingId.value = null; errors.value = {}
    form.value = { matrix_config_id: currentConfigId.value, label: '', score: availableScores.value[0] ?? 1, description: '', color_code: '#6b7280', sort_order: props.impactLevels.length }
}
const submitForm = () => {
    processing.value = true
    const url    = editingId.value ? `/m/risk.core/impact/${editingId.value}` : '/m/risk.core/impact'
    const method = editingId.value ? 'put' : 'post'
    router[method](url, form.value, {
        preserveScroll: true,
        onSuccess: () => { resetForm(); processing.value = false; showAlert('Niveau enregistré.') },
        onError:   (e) => { errors.value = e; processing.value = false },
    })
}
const confirmDelete = (level) => { deleteModal.value = { show: true, level } }
const executeDelete = () => {
    router.delete(`/m/risk.core/impact/${deleteModal.value.level.id}`,
                  { preserveScroll: true, onSuccess: () => showAlert('Niveau supprimé.') })
}

// ─── Templates — CRUD ─────────────────────────────────────────────────────────
const submitTemplate = () => {
    if (!templateForm.value.designation.trim()) {
        templateErrors.value = { designation: 'La désignation est obligatoire.' }; return
    }
    templateProcessing.value = true; templateErrors.value = {}
    router.post('/m/risk.core/impact/templates', {
        matrix_config_id: currentConfigId.value,
        designation:      templateForm.value.designation,
        hint:             templateForm.value.hint,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            templateForm.value = { designation: '', hint: '' }
            templateProcessing.value = false
            showAlert('Critère ajouté à tous les niveaux. Assignez-lui une appétence si besoin.')
        },
        onError: (e) => { templateErrors.value = e; templateProcessing.value = false },
    })
}
const startEditTemplate = (tpl) => {
    editingTemplateId.value = tpl.id
    templateEditForm.value  = { designation: tpl.designation, hint: tpl.hint ?? '', appetite_id: tpl.appetite_id ?? null, appetite_description: tpl.appetite_description ?? null }
}
const cancelEditTemplate = () => { editingTemplateId.value = null }
const submitEditTemplate = (tpl) => {
    router.put(`/m/risk.core/impact/templates/${tpl.id}`, {
        designation: templateEditForm.value.designation,
        hint:        templateEditForm.value.hint,
    }, {
        preserveScroll: true,
        onSuccess: () => { editingTemplateId.value = null; showAlert('Critère mis à jour.') },
    })
}

// ─── Templates — appétence temps réel ────────────────────────────────────────
const onAppetiteSelectChange = async (tpl) => {
    const appetiteId = templateEditForm.value.appetite_id
    templateEditForm.value.appetite_description = null
    if (appetiteId === null) { await saveAppetiteToServer(tpl.id, null); return }
    const localApt = appetiteMap.value[appetiteId]
    if (localApt?.description) templateEditForm.value.appetite_description = localApt.description
    appetiteSaving.value = true
    await saveAppetiteToServer(tpl.id, appetiteId)
    appetiteSaving.value = false
}
const saveAppetiteToServer = async (templateId, appetiteId) => {
    try {
        const res = await fetch(`/m/risk.core/impact/templates/${templateId}/appetite`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
            body: JSON.stringify({ appetite_id: appetiteId }),
        })
        const data = await res.json()
        if (res.ok) {
            templateEditForm.value.appetite_description = data.appetite_description ?? null
            showAlert(data.message, 'success')
        } else {
            showAlert(data.message ?? "Erreur lors de la sauvegarde de l'appétence.", 'danger')
            templateEditForm.value.appetite_id = null; templateEditForm.value.appetite_description = null
        }
    } catch {
        showAlert('Impossible de contacter le serveur.', 'danger')
        templateEditForm.value.appetite_id = null; templateEditForm.value.appetite_description = null
    }
}

// ─── Templates — suppression & drag ──────────────────────────────────────────
const confirmDeleteTemplate = (tpl) => { deleteTemplateModal.value = { show: true, template: tpl } }
const executeDeleteTemplate = () => {
    router.delete(`/m/risk.core/impact/templates/${deleteTemplateModal.value.template.id}`,
                  { preserveScroll: true, onSuccess: () => showAlert('Critère supprimé de tous les niveaux.') })
}
const onTemplateDragStart = (event, idx) => { templateDragIdx.value = idx; event.dataTransfer.effectAllowed = 'move' }
const onTemplateDrop = (event, toIdx) => {
    const fromIdx = templateDragIdx.value
    if (fromIdx === null || fromIdx === toIdx) return
    const items = [...props.criteriaTemplates]
    const [moved] = items.splice(fromIdx, 1); items.splice(toIdx, 0, moved)
    router.post('/m/risk.core/impact/templates/reorder',
                { items: items.map((t, i) => ({ id: t.id, sort_order: i })) }, { preserveScroll: true })
    templateDragIdx.value = null
}

// ─── Édition cellule ──────────────────────────────────────────────────────────
const openCriterionEdit = (level, tpl) => {
    criterionEditModal.value = {
        show: true, level, template: tpl,
        criterionId: getCriterionId(level, tpl),
        description: getCriterionContent(level, tpl) ?? '',
    }
}
const saveCriterionContent = () => {
    const { level, criterionId, description } = criterionEditModal.value
    if (!criterionId) return
    router.put(`/m/risk.core/impact/${level.id}/criteria/${criterionId}`,
               { description }, { preserveScroll: true, onSuccess: () => showAlert('Contenu mis à jour.') })
}

// ─── IA niveaux ───────────────────────────────────────────────────────────────
const openMistralPanel = () => { mistralPanel.value.show = true }
const fetchMistralSuggestions = async () => {
    mistralPanel.value.loading = true; mistralPanel.value.error = null; mistralPanel.value.suggestions = []
    try {
        const res = await fetch('/m/risk.core/impact/mistral/suggest', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify({ sector: mistralPanel.value.sector, context: mistralPanel.value.context, matrix_size: selectedConfig.value?.matrix_size ?? 5 }),
        })
        const data = await res.json()
        if (!res.ok) mistralPanel.value.error = data.message ?? 'Erreur IA.'
        else { mistralPanel.value.suggestions = data.suggestions ?? []; mistralPanel.value.usedSector = data.sector ?? mistralPanel.value.sector }
    } catch { mistralPanel.value.error = "Impossible de contacter l'assistant IA." }
    finally { mistralPanel.value.loading = false }
}
const applySuggestion = (s) => {
    form.value = { matrix_config_id: currentConfigId.value, label: s.label, score: s.score, description: s.description, color_code: s.color_code, sort_order: s.score - 1 }
    mistralPanel.value.show = false
}

// ─── IA contenu critères ──────────────────────────────────────────────────────
const openContentPanel = () => { contentPanel.value.show = true }
const fetchCriteriaContent = async () => {
    contentPanel.value.loading = true; contentPanel.value.error = null
    contentPanel.value.suggestions = {}; contentPanel.value.hasSuggestions = false
    try {
        const res = await fetch('/m/risk.core/impact/criteria/suggest-content', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify({ sector: contentPanel.value.sector, context: contentPanel.value.context, matrix_config_id: currentConfigId.value }),
        })
        const data = await res.json()
        if (!res.ok) { contentPanel.value.error = data.message ?? 'Erreur IA.' }
        else { contentPanel.value.suggestions = data.suggestions ?? {}; contentPanel.value.usedSector = data.sector ?? contentPanel.value.sector; contentPanel.value.hasSuggestions = true }
    } catch { contentPanel.value.error = "Impossible de contacter l'assistant IA." }
    finally { contentPanel.value.loading = false }
}
const applyAllContent = () => {
    contentPanel.value.applying = true
    router.post('/m/risk.core/impact/criteria/apply-content', {
        matrix_config_id: currentConfigId.value,
        suggestions:      contentPanel.value.suggestions,
    }, {
        preserveScroll: true,
        onSuccess: () => { contentPanel.value = { ...contentPanel.value, suggestions: {}, hasSuggestions: false, show: false, applying: false }; showAlert('Contenu de tous les critères appliqué avec succès.') },
        onError:   () => { contentPanel.value.applying = false; showAlert("Erreur lors de l'application.", 'danger') },
    })
}
</script>

<style scoped>
.form-control-sm, .form-select-sm { font-size:.75rem; height:26px; padding:.15rem .45rem }
textarea.form-control-sm { height:auto }
.btn-sm { padding:.15rem .45rem; font-size:.72rem }
.stat-icon { width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:white; font-size:18px }
.stat-card { transition:all .2s }
.stat-card:hover { box-shadow:0 4px 12px rgba(0,0,0,.1); transform:translateY(-2px) }
.color-dot { width:10px; height:10px; border-radius:50%; display:inline-block; flex-shrink:0 }
.apt-badge { padding:.1rem .5rem; border-radius:12px; font-size:.7rem; font-weight:700; border:1px solid; display:inline-flex; align-items:center }
.appetite-desc-box { background:#f0fdf4; border:1px solid #86efac; border-radius:.35rem; padding:.4rem .6rem }
.btn-icon { background:none; border:none; padding:.1rem .2rem; font-size:.78rem; cursor:pointer; color:#6c757d; border-radius:3px }
.btn-icon:hover { background:#e9ecef }
.btn-icon.text-danger:hover { background:#fff5f5; color:#dc3545 }
.workflow-steps { display:flex; align-items:center; gap:.5rem; background:#f8f9fa; border:1px solid #e5e7eb; border-radius:.5rem; padding:.6rem 1rem }
.workflow-step { display:flex; align-items:center; gap:.5rem; padding:.3rem .6rem; border-radius:.35rem; background:#fff; border:1px solid #e5e7eb; flex:1; transition:all .2s }
.workflow-step.step-active { border-color:#3b5bdb; background:#eef2ff }
.workflow-step.step-done   { border-color:#22c55e; background:#f0fdf4 }
.step-num { width:22px; height:22px; border-radius:50%; background:#e5e7eb; display:flex; align-items:center; justify-content:center; font-size:.72rem; font-weight:700; flex-shrink:0 }
.step-active .step-num { background:#3b5bdb; color:#fff }
.step-done   .step-num { background:#22c55e; color:#fff }
.workflow-arrow { color:#9ca3af }
.template-add-form { background:#fffbf0; border-color:#fde68a !important }
.template-row { padding:.4rem .5rem; border-radius:.35rem; background:#fafafa; border:1px solid #eef2f7 }
.template-row:hover { background:#fffbf0; border-color:#fde68a }
.template-row[draggable="true"] { cursor:grab }
.drag-handle { font-size:.8rem; cursor:grab }
.criteria-matrix { overflow-x:auto }
.criteria-table { font-size:.72rem; min-width:500px }
.criteria-name-col { width:190px; min-width:160px; background:#f8fafc; vertical-align:top; padding:.4rem .5rem }
.level-col { min-width:120px; padding:.3rem .4rem }
.criterion-cell { cursor:pointer; transition:background .15s; vertical-align:top }
.criterion-cell:hover { background:#f0f4ff !important }
.cell-empty  { background:#fafafa }
.cell-filled { background:#f0fdf4 }
.criterion-content { font-size:.67rem; line-height:1.3; color:#374151; max-height:60px; overflow:hidden }
.criterion-empty { display:flex; align-items:center; justify-content:center; height:40px; color:#9ca3af }
.pv-table :deep(.p-datatable-thead>tr>th) { background:#f8fafc; border:1px solid #e5e7eb; padding:.25rem .35rem; font-size:.73rem }
.pv-table :deep(.p-datatable-tbody>tr>td) { border:1px solid #eef2f7; padding:.25rem .35rem; font-size:.72rem }
.ai-domain-block { background:#f0f4ff; border:1px solid #c7d7fb; border-radius:.4rem; padding:.5rem .65rem }
.ai-sector-box   { background:#f8f9fa; border:1px dashed #dee2e6; border-radius:.4rem; padding:.5rem .75rem }
.domain-chip { padding:.1rem .5rem; font-size:.7rem; border-radius:12px; border:1px solid #c7d7fb; background:#fff; color:#3b5bdb; cursor:pointer; transition:all .15s; line-height:1.4 }
.domain-chip:hover   { background:#3b5bdb; color:#fff; border-color:#3b5bdb }
.domain-chip--active { background:#3b5bdb; color:#fff; border-color:#3b5bdb }
.ai-error-box { background:#fff5f5; border:1px solid #fca5a5; border-radius:.4rem; padding:.65rem .75rem }
.ai-suggestions-box { background:linear-gradient(135deg,#fce4e4 0%,#ffd5d5 100%); border:2px solid #dc3545; border-radius:.4rem; padding:.6rem .75rem }
.ai-header { font-weight:600; color:#842029; margin-bottom:.5rem; font-size:.82rem }
.suggestion-chip { padding:.3rem .7rem; cursor:pointer; font-size:.78rem; border-radius:12px; background:#fce4e4; color:#842029; border:1px solid #f5c2c7; transition:all .2s }
.suggestion-chip:hover { background:#f5c2c7; transform:translateY(-1px) }
.criteria-suggestions-box { background:#fffbf0; border:2px solid #f59e0b; border-radius:.4rem; padding:.6rem .75rem }
.content-preview-level { background:#fff; border:1px solid #e5e7eb; border-radius:.35rem; padding:.4rem .6rem }
.content-preview-item { background:#f8f9fa; border-radius:.25rem; padding:.2rem .5rem; margin-bottom:.25rem; font-size:.73rem; border-left:2px solid #f59e0b }
.matrix-preview { background:#f8fafc; border:1px dashed #dee2e6; border-radius:.35rem; padding:.5rem .75rem }
</style>