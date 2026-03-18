<template>
    <VerticalLayout>
        <Head title="DDM — Nomenclature des Risques" />

        <!-- HEADER -->
        <b-row class="mb-0">
            <b-col>
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-sitemap text-danger fs-5"></i>
                    <h4 class="m-0 fw-semibold">Nomenclature des Risques</h4>
                    <small class="text-muted ms-2">🤖 Suggestions IA Mistral (ISO 31000 · COSO · Basel II)</small>
                </div>
            </b-col>
        </b-row>

        <!-- STATS -->
        <b-row class="g-2 mb-2">
            <b-col lg="3">
                <b-card no-body class="shadow-sm stat-card">
                    <b-card-body class="p-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="stat-icon bg-danger"><i class="ti ti-mood-nervous"></i></div>
                            <div class="flex-grow-1">
                                <small class="text-muted">Appétances</small>
                                <h5 class="mb-0 fw-bold">{{ appetites.length }}</h5>
                            </div>
                            <div class="stat-badge" :class="appetites.length > 0 ? 'badge-danger' : 'badge-secondary'">🎯</div>
                        </div>
                    </b-card-body>
                </b-card>
            </b-col>
            <b-col lg="3">
                <b-card no-body class="shadow-sm stat-card">
                    <b-card-body class="p-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="stat-icon bg-primary"><i class="ti ti-folder"></i></div>
                            <div class="flex-grow-1">
                                <small class="text-muted">Domaines (Niv. 1)</small>
                                <h5 class="mb-0 fw-bold">{{ level1List.length }}</h5>
                            </div>
                            <div class="stat-badge badge-info">D</div>
                        </div>
                    </b-card-body>
                </b-card>
            </b-col>
            <b-col lg="3">
                <b-card no-body class="shadow-sm stat-card">
                    <b-card-body class="p-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="stat-icon bg-info"><i class="ti ti-folders"></i></div>
                            <div class="flex-grow-1">
                                <small class="text-muted">Familles (Niv. 2)</small>
                                <h5 class="mb-0 fw-bold">{{ level2List.length }}</h5>
                            </div>
                            <div class="stat-badge badge-info">F</div>
                        </div>
                    </b-card-body>
                </b-card>
            </b-col>
            <b-col lg="3">
                <b-card no-body class="shadow-sm stat-card">
                    <b-card-body class="p-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="stat-icon bg-warning"><i class="ti ti-file-text"></i></div>
                            <div class="flex-grow-1">
                                <small class="text-muted">Types (Niv. 3)</small>
                                <h5 class="mb-0 fw-bold">{{ level3List.length }}</h5>
                            </div>
                            <div class="stat-badge badge-warning">T</div>
                        </div>
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>

        <!-- ALERTE FEEDBACK -->
        <b-alert v-if="alert.show" :variant="alert.variant" show dismissible @dismissed="alert.show=false" class="py-2 px-3 mb-2">
            {{ alert.message }}
        </b-alert>

        <!-- TABS -->
        <b-card no-body class="mb-2 shadow-none border-0">
            <b-card-body class="p-1">
                <b-button-group size="sm">
                    <b-button @click="activeTab='appetites'"  :variant="tabVariant('appetites')" ><i class="ti ti-mood-nervous me-1"></i> Appétances</b-button>
                    <b-button @click="activeTab='level1'"     :variant="tabVariant('level1')"    ><i class="ti ti-folder me-1"></i> Domaines</b-button>
                    <b-button @click="activeTab='level2'"     :variant="tabVariant('level2')"    ><i class="ti ti-folders me-1"></i> Familles</b-button>
                    <b-button @click="activeTab='level3'"     :variant="tabVariant('level3')"    ><i class="ti ti-file-text me-1"></i> Types</b-button>
                </b-button-group>
            </b-card-body>
        </b-card>

        <b-row class="g-1">

            <!-- ═══════════════ PANNEAU GAUCHE : FORMULAIRE ═══════════════ -->
            <b-col lg="6">
                <b-card no-body class="shadow-sm">

                    <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
              <span v-if="activeTab==='appetites'">
                <i class="ti ti-mood-nervous me-1"></i>
                {{ editMode ? 'Modifier' : 'Ajouter' }} une appétance
              </span>
                            <span v-else-if="activeTab==='level1'">
                <i class="ti ti-folder me-1"></i>
                {{ editMode ? 'Modifier' : 'Ajouter' }} un domaine (Niv. 1)
              </span>
                            <span v-else-if="activeTab==='level2'">
                <i class="ti ti-folders me-1"></i>
                {{ editMode ? 'Modifier' : 'Ajouter' }} une famille (Niv. 2)
              </span>
                            <span v-else>
                <i class="ti ti-file-text me-1"></i>
                {{ editMode ? 'Modifier' : 'Ajouter' }} un type précis (Niv. 3)
              </span>
                        </h6>
                        <small v-if="editMode" class="badge bg-warning text-dark">Mode édition</small>
                    </b-card-header>

                    <b-card-body class="p-2">

                        <!-- ══════════ APPÉTANCES ══════════ -->
                        <div v-if="activeTab==='appetites'">
                            <b-form @submit.prevent="submitAppetite" class="mb-3">
                                <b-row class="g-2">

                                    <!-- Code -->
                                    <b-col cols="4">
                                        <label class="form-label mb-1">Code * <small class="text-muted">(ex: APT-0)</small></label>
                                        <b-form-input class="form-control-sm font-monospace" v-model.trim="appetiteForm.code"
                                                      placeholder="APT-0" required :disabled="editMode" />
                                    </b-col>

                                    <!-- Label -->
                                    <b-col cols="8">
                                        <label class="form-label mb-1">Libellé *</label>
                                        <b-form-input class="form-control-sm" v-model.trim="appetiteForm.label"
                                                      placeholder="Ex: Modéré" required />
                                    </b-col>

                                    <!-- Scores min/max -->
                                    <b-col cols="3">
                                        <label class="form-label mb-1">Score min *</label>
                                        <b-form-input class="form-control-sm" type="number" min="0" max="25"
                                                      v-model.number="appetiteForm.score_min" required />
                                    </b-col>
                                    <b-col cols="3">
                                        <label class="form-label mb-1">Score max *</label>
                                        <b-form-input class="form-control-sm" type="number" min="0" max="25"
                                                      v-model.number="appetiteForm.score_max" required />
                                    </b-col>

                                    <!-- Couleur -->
                                    <b-col cols="3">
                                        <label class="form-label mb-1">Couleur *</label>
                                        <div class="d-flex gap-1 align-items-center">
                                            <input type="color" class="form-control form-control-sm form-control-color p-0"
                                                   v-model="appetiteForm.color" style="width:36px;height:28px" />
                                            <b-form-input class="form-control-sm font-monospace" v-model="appetiteForm.color"
                                                          placeholder="#ffc107" style="max-width:90px" />
                                        </div>
                                    </b-col>

                                    <!-- Ordre -->
                                    <b-col cols="3">
                                        <label class="form-label mb-1">Ordre</label>
                                        <b-form-input class="form-control-sm" type="number" min="0"
                                                      v-model.number="appetiteForm.sort_order" />
                                    </b-col>

                                    <!-- Description -->
                                    <b-col cols="12">
                                        <label class="form-label mb-1">Description</label>
                                        <b-form-textarea class="form-control-sm" rows="2"
                                                         v-model.trim="appetiteForm.description"
                                                         placeholder="Description de la posture de tolérance..." />
                                    </b-col>

                                    <!-- Prévisualisation -->
                                    <b-col cols="12">
                                        <div class="preview-badge" :style="{ background: appetiteForm.color + '22', borderColor: appetiteForm.color, color: appetiteForm.color }">
                                            <span class="fw-bold">{{ appetiteForm.code || 'APT-?' }}</span>
                                            <span class="ms-2">{{ appetiteForm.label || 'Libellé' }}</span>
                                            <span class="ms-2 text-muted small">{{ appetiteForm.score_min }}–{{ appetiteForm.score_max }}</span>
                                        </div>
                                    </b-col>

                                    <!-- Boutons -->
                                    <b-col cols="12" class="text-end pt-1">
                                        <b-button size="sm" variant="light" class="me-1" @click="resetAppetite">Annuler</b-button>
                                        <b-button size="sm" variant="danger" type="submit" :disabled="saving">
                                            {{ saving ? '⏳' : (editMode ? '💾 Enregistrer' : '➕ Créer') }}
                                        </b-button>
                                    </b-col>
                                </b-row>
                            </b-form>

                            <!-- TABLE APPÉTANCES -->
                            <DataTable :value="appetites" size="small" class="pv-table flat">
                                <Column header="Code" style="width:90px">
                                    <template #body="{data}">
                                        <span class="font-monospace fw-bold" :style="{color: data.color}">{{ data.code }}</span>
                                    </template>
                                </Column>
                                <Column header="Libellé">
                                    <template #body="{data}">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="color-dot" :style="{background: data.color}"></span>
                                            <span class="fw-semibold">{{ data.label }}</span>
                                        </div>
                                    </template>
                                </Column>
                                <Column header="Plage" style="width:90px" bodyClass="text-center">
                                    <template #body="{data}">
                                        <small class="text-muted">{{ data.score_min }}–{{ data.score_max }}</small>
                                    </template>
                                </Column>
                                <Column header="" style="width:60px" bodyClass="text-end">
                                    <template #body="{data}">
                                        <b-button size="sm" variant="light" @click="editAppetite(data)" title="Modifier">
                                            <i class="ti ti-pencil"></i>
                                        </b-button>
                                        <b-button size="sm" variant="light" class="ms-1 text-danger" @click="confirmDestroyAppetite(data)" title="Supprimer">
                                            <i class="ti ti-trash"></i>
                                        </b-button>
                                    </template>
                                </Column>
                                <template #empty><div class="text-muted py-1">Aucune appétance définie</div></template>
                            </DataTable>
                        </div>

                        <!-- ══════════ DOMAINES (NIVEAU 1) ══════════ -->
                        <div v-else-if="activeTab==='level1'">

                            <!-- Secteur pour IA -->
                            <div class="ai-sector-box mb-2">
                                <label class="form-label mb-1 fw-semibold">
                                    <i class="ti ti-robot me-1 text-primary"></i>Secteur d'activité
                                    <small class="text-muted fw-normal ms-1">(pour les suggestions IA)</small>
                                </label>
                                <div class="d-flex gap-2">
                                    <b-form-input class="form-control-sm flex-grow-1" v-model.trim="sector"
                                                  placeholder="Ex: industrie agroalimentaire, banque, assurance..." />
                                    <b-button size="sm" variant="primary" @click="loadDomainSuggestions" :disabled="isLoadingAI || !sector">
                                        <span v-if="isLoadingAI"><i class="ti ti-loader-2 ti-spin"></i></span>
                                        <span v-else><i class="ti ti-sparkles"></i> Suggérer</span>
                                    </b-button>
                                </div>
                            </div>

                            <!-- Suggestions IA domaines -->
                            <div v-if="aiDomainSuggestions.length > 0" class="ai-suggestions-box mb-2">
                                <div class="ai-header">✨ <strong>Domaines suggérés</strong> (cliquer pour pré-remplir)</div>
                                <div class="suggestion-chips">
                  <span v-for="(s, idx) in aiDomainSuggestions" :key="idx"
                        class="suggestion-chip"
                        @click="prefillLevel1(s)"
                        role="button" :title="s.description">
                    <i class="ti ti-click me-1"></i>{{ s.code }} — {{ s.label }}
                  </span>
                                </div>
                            </div>

                            <b-form @submit.prevent="submitNomenclature(1)" class="mb-2">
                                <b-row class="g-2">
                                    <b-col cols="4">
                                        <label class="form-label mb-1">Code * <small class="text-muted">(ex: RO)</small></label>
                                        <b-form-input class="form-control-sm font-monospace" v-model.trim="nomForm.code"
                                                      placeholder="RO" required :disabled="editMode" />
                                    </b-col>
                                    <b-col cols="8">
                                        <label class="form-label mb-1">Libellé *</label>
                                        <b-form-input class="form-control-sm" v-model.trim="nomForm.label"
                                                      placeholder="Risque Opérationnel" required />
                                    </b-col>
                                    <b-col cols="12">
                                        <label class="form-label mb-1">Appétance par défaut
                                            <small class="text-muted">(tous les enfants hériteront si non défini)</small>
                                        </label>
                                        <b-form-select class="form-select-sm" v-model="nomForm.appetite_id" :options="appetiteOptions" />
                                    </b-col>
                                    <b-col cols="12">
                                        <label class="form-label mb-1">Description</label>
                                        <b-form-input class="form-control-sm" v-model.trim="nomForm.description" placeholder="Description courte..." />
                                    </b-col>
                                    <b-col cols="12" class="text-end pt-1">
                                        <b-button size="sm" variant="light" class="me-1" @click="resetNom">Annuler</b-button>
                                        <b-button size="sm" variant="primary" type="submit" :disabled="saving">
                                            {{ saving ? '⏳' : (editMode ? '💾 Enregistrer' : '➕ Créer') }}
                                        </b-button>
                                    </b-col>
                                </b-row>
                            </b-form>

                            <DataTable :value="level1List" size="small" class="pv-table flat">
                                <Column header="Code" style="width:70px">
                                    <template #body="{data}"><span class="font-monospace text-primary fw-bold">{{ data.code }}</span></template>
                                </Column>
                                <Column header="Libellé">
                                    <template #body="{data}"><span class="fw-semibold">{{ data.label }}</span></template>
                                </Column>
                                <Column header="Appétance" style="width:120px">
                                    <template #body="{data}">
                    <span v-if="data.appetite" class="apt-badge" :style="{background: data.appetite.color+'22', color: data.appetite.color, borderColor: data.appetite.color}">
                      {{ data.appetite.code }}
                    </span>
                                        <small v-else class="text-muted">—</small>
                                    </template>
                                </Column>
                                <Column header="Enfants" style="width:70px" bodyClass="text-center">
                                    <template #body="{data}">
                                        <b-badge bg="light" text="dark">{{ childrenCount(data.id) }}</b-badge>
                                    </template>
                                </Column>
                                <Column header="" style="width:60px" bodyClass="text-end">
                                    <template #body="{data}">
                                        <b-button size="sm" variant="light" @click="editNom(data)" title="Modifier"><i class="ti ti-pencil"></i></b-button>
                                        <b-button size="sm" variant="light" class="ms-1 text-danger" @click="confirmDestroyNom(data)" title="Supprimer"><i class="ti ti-trash"></i></b-button>
                                    </template>
                                </Column>
                                <template #empty><div class="text-muted py-1">Aucun domaine défini</div></template>
                            </DataTable>
                        </div>

                        <!-- ══════════ FAMILLES (NIVEAU 2) ══════════ -->
                        <div v-else-if="activeTab==='level2'">

                            <!-- Sélecteur domaine parent + bouton IA -->
                            <b-col cols="12" class="mb-2">
                                <label class="form-label mb-1">Domaine parent *</label>
                                <div class="d-flex gap-2">
                                    <b-form-select class="form-select-sm flex-grow-1" v-model="selectedParentL1"
                                                   :options="level1Options" @change="onLevel1Selected" />
                                    <b-button size="sm" variant="primary" @click="loadFamilySuggestions"
                                              :disabled="isLoadingAI || !selectedParentL1" title="Suggérer des familles avec Mistral">
                                        <span v-if="isLoadingAI"><i class="ti ti-loader-2 ti-spin"></i></span>
                                        <span v-else><i class="ti ti-sparkles"></i></span>
                                    </b-button>
                                </div>
                            </b-col>

                            <!-- Suggestions IA familles -->
                            <div v-if="aiFamilySuggestions.length > 0" class="ai-suggestions-box mb-2">
                                <div class="ai-header">✨ <strong>Familles suggérées pour {{ selectedDomainLabel }}</strong></div>
                                <div class="suggestion-chips">
                  <span v-for="(s, idx) in aiFamilySuggestions" :key="idx"
                        class="suggestion-chip"
                        @click="prefillLevel2(s)"
                        role="button" :title="s.description">
                    <i class="ti ti-click me-1"></i>{{ s.code }} — {{ s.label }}
                  </span>
                                </div>
                            </div>

                            <b-form @submit.prevent="submitNomenclature(2)" class="mb-2">
                                <b-row class="g-2">
                                    <b-col cols="4">
                                        <label class="form-label mb-1">Code * <small class="text-muted">(ex: RO-RH)</small></label>
                                        <b-form-input class="form-control-sm font-monospace" v-model.trim="nomForm.code"
                                                      placeholder="RO-RH" required :disabled="editMode" />
                                    </b-col>
                                    <b-col cols="8">
                                        <label class="form-label mb-1">Libellé *</label>
                                        <b-form-input class="form-control-sm" v-model.trim="nomForm.label"
                                                      placeholder="Ressources humaines" required />
                                    </b-col>
                                    <b-col cols="12">
                                        <label class="form-label mb-1">
                                            Appétance
                                            <small class="text-muted">
                                                (optionnel — hérite de
                                                <span class="text-primary fw-semibold">{{ selectedDomainLabel || 'son parent' }}</span>
                                                si vide)
                                            </small>
                                        </label>
                                        <b-form-select class="form-select-sm" v-model="nomForm.appetite_id" :options="appetiteOptions" />
                                    </b-col>
                                    <b-col cols="12">
                                        <label class="form-label mb-1">Description</label>
                                        <b-form-input class="form-control-sm" v-model.trim="nomForm.description" placeholder="Description courte..." />
                                    </b-col>
                                    <b-col cols="12" class="text-end pt-1">
                                        <b-button size="sm" variant="light" class="me-1" @click="resetNom">Annuler</b-button>
                                        <b-button size="sm" variant="info" type="submit" :disabled="saving || !selectedParentL1">
                                            {{ saving ? '⏳' : (editMode ? '💾 Enregistrer' : '➕ Créer') }}
                                        </b-button>
                                    </b-col>
                                </b-row>
                            </b-form>

                            <DataTable :value="level2Filtered" size="small" class="pv-table flat">
                                <Column header="Code" style="width:100px">
                                    <template #body="{data}"><span class="font-monospace text-info fw-bold">{{ data.code }}</span></template>
                                </Column>
                                <Column header="Libellé">
                                    <template #body="{data}"><span class="fw-semibold">{{ data.label }}</span></template>
                                </Column>
                                <Column header="Appétance" style="width:120px">
                                    <template #body="{data}">
                    <span v-if="data.appetite" class="apt-badge" :style="{background: data.appetite.color+'22', color: data.appetite.color, borderColor: data.appetite.color}">
                      {{ data.appetite.code }}
                    </span>
                                        <small v-else class="text-muted fst-italic">hérite</small>
                                    </template>
                                </Column>
                                <Column header="" style="width:60px" bodyClass="text-end">
                                    <template #body="{data}">
                                        <b-button size="sm" variant="light" @click="editNom(data)" title="Modifier"><i class="ti ti-pencil"></i></b-button>
                                        <b-button size="sm" variant="light" class="ms-1 text-danger" @click="confirmDestroyNom(data)" title="Supprimer"><i class="ti ti-trash"></i></b-button>
                                    </template>
                                </Column>
                                <template #empty><div class="text-muted py-1">{{ selectedParentL1 ? 'Aucune famille pour ce domaine' : 'Sélectionnez un domaine' }}</div></template>
                            </DataTable>
                        </div>

                        <!-- ══════════ TYPES PRÉCIS (NIVEAU 3) ══════════ -->
                        <div v-else>

                            <!-- Sélecteurs domaine + famille + bouton IA -->
                            <b-row class="g-2 mb-2">
                                <b-col cols="5">
                                    <label class="form-label mb-1">Domaine *</label>
                                    <b-form-select class="form-select-sm" v-model="selectedParentL1ForL3"
                                                   :options="level1Options" @change="onLevel1ForL3Selected" />
                                </b-col>
                                <b-col cols="5">
                                    <label class="form-label mb-1">Famille parent *</label>
                                    <b-form-select class="form-select-sm" v-model="selectedParentL2"
                                                   :options="level2OptionsForL3" @change="onLevel2Selected" />
                                </b-col>
                                <b-col cols="2" class="d-flex align-items-end">
                                    <b-button size="sm" variant="primary" class="w-100" @click="loadTypeSuggestions"
                                              :disabled="isLoadingAI || !selectedParentL2" title="Suggérer des types avec Mistral">
                                        <span v-if="isLoadingAI"><i class="ti ti-loader-2 ti-spin"></i></span>
                                        <span v-else><i class="ti ti-sparkles"></i></span>
                                    </b-button>
                                </b-col>
                            </b-row>

                            <!-- Suggestions IA types -->
                            <div v-if="aiTypeSuggestions.length > 0" class="ai-suggestions-box mb-2">
                                <div class="ai-header">✨ <strong>Types suggérés pour {{ selectedFamilyLabel }}</strong></div>
                                <div class="suggestion-chips">
                  <span v-for="(s, idx) in aiTypeSuggestions" :key="idx"
                        class="suggestion-chip"
                        @click="prefillLevel3(s)"
                        role="button" :title="s.description">
                    <i class="ti ti-click me-1"></i>{{ s.code }} — {{ s.label }}
                  </span>
                                </div>
                            </div>

                            <b-form @submit.prevent="submitNomenclature(3)" class="mb-2">
                                <b-row class="g-2">
                                    <b-col cols="5">
                                        <label class="form-label mb-1">Code * <small class="text-muted">(ex: RO-RH-001)</small></label>
                                        <b-form-input class="form-control-sm font-monospace" v-model.trim="nomForm.code"
                                                      placeholder="RO-RH-001" required :disabled="editMode" />
                                    </b-col>
                                    <b-col cols="7">
                                        <label class="form-label mb-1">Libellé *</label>
                                        <b-form-input class="form-control-sm" v-model.trim="nomForm.label"
                                                      placeholder="Perte de compétences clés" required />
                                    </b-col>
                                    <b-col cols="12">
                                        <label class="form-label mb-1">
                                            Appétance
                                            <small class="text-muted">
                                                (optionnel — hérite de
                                                <span class="text-warning fw-semibold">{{ selectedFamilyLabel || 'la famille parente' }}</span>
                                                si vide)
                                            </small>
                                        </label>
                                        <b-form-select class="form-select-sm" v-model="nomForm.appetite_id" :options="appetiteOptions" />
                                    </b-col>
                                    <b-col cols="12">
                                        <label class="form-label mb-1">Description</label>
                                        <b-form-input class="form-control-sm" v-model.trim="nomForm.description" placeholder="Conséquence principale..." />
                                    </b-col>
                                    <b-col cols="12" class="text-end pt-1">
                                        <b-button size="sm" variant="light" class="me-1" @click="resetNom">Annuler</b-button>
                                        <b-button size="sm" variant="warning" type="submit" :disabled="saving || !selectedParentL2">
                                            {{ saving ? '⏳' : (editMode ? '💾 Enregistrer' : '➕ Créer') }}
                                        </b-button>
                                    </b-col>
                                </b-row>
                            </b-form>

                            <DataTable :value="level3Filtered" size="small" class="pv-table flat">
                                <Column header="Code" style="width:120px">
                                    <template #body="{data}"><span class="font-monospace text-warning fw-bold" style="font-size:.7rem">{{ data.code }}</span></template>
                                </Column>
                                <Column header="Libellé">
                                    <template #body="{data}"><span class="fw-semibold" style="font-size:.75rem">{{ data.label }}</span></template>
                                </Column>
                                <Column header="Appétance" style="width:120px">
                                    <template #body="{data}">
                    <span v-if="data.appetite" class="apt-badge" :style="{background: data.appetite.color+'22', color: data.appetite.color, borderColor: data.appetite.color}">
                      {{ data.appetite.code }}
                    </span>
                                        <small v-else class="text-muted fst-italic">hérite</small>
                                    </template>
                                </Column>
                                <Column header="" style="width:60px" bodyClass="text-end">
                                    <template #body="{data}">
                                        <b-button size="sm" variant="light" @click="editNom(data)" title="Modifier"><i class="ti ti-pencil"></i></b-button>
                                        <b-button size="sm" variant="light" class="ms-1 text-danger" @click="confirmDestroyNom(data)" title="Supprimer"><i class="ti ti-trash"></i></b-button>
                                    </template>
                                </Column>
                                <template #empty><div class="text-muted py-1">{{ selectedParentL2 ? 'Aucun type pour cette famille' : 'Sélectionnez une famille' }}</div></template>
                            </DataTable>
                        </div>

                    </b-card-body>
                </b-card>
            </b-col>

            <!-- ═══════════════ PANNEAU DROIT : ARBORESCENCE ═══════════════ -->
            <b-col lg="6">
                <b-card no-body class="shadow-sm h-100">
                    <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="ti ti-sitemap me-1"></i> Arborescence complète</h6>
                        <small class="text-muted">{{ nomenclatures.length }} entrées</small>
                    </b-card-header>
                    <b-card-body class="p-2">
                        <Tree :value="treeNodes"
                              v-model:expandedKeys="expandedKeys"
                              selectionMode="single"
                              :filter="true"
                              filterMode="lenient"
                              class="w-100 pv-tree rounded">
                            <template #default="{ node }">
                                <div class="d-flex align-items-center gap-2 w-100">
                  <span class="level-badge" :class="'level-' + node.data.level">
                    {{ node.data.level === 1 ? 'D' : node.data.level === 2 ? 'F' : 'T' }}
                  </span>
                                    <span class="code-chip font-monospace">{{ node.data.code }}</span>
                                    <span class="fw-semibold flex-grow-1" style="font-size:.8rem">{{ node.label }}</span>
                                    <span v-if="node.data.appetite" class="apt-badge ms-auto"
                                          :style="{background: node.data.appetite.color+'22', color: node.data.appetite.color, borderColor: node.data.appetite.color}">
                    {{ node.data.appetite.code }}
                  </span>
                                    <span v-else-if="node.data.level > 1" class="text-muted" style="font-size:.68rem">hérite</span>
                                </div>
                            </template>
                        </Tree>
                    </b-card-body>
                </b-card>
            </b-col>

        </b-row>

        <!-- MODAL CONFIRMATION SUPPRESSION -->
        <b-modal v-model="deleteModal.show" title="Confirmer la suppression" hide-footer>
            <p>Supprimer <strong>{{ deleteModal.label }}</strong> ?</p>
            <p class="text-muted small">Cette action est irréversible.</p>
            <div class="text-end">
                <b-button variant="light" class="me-2" @click="deleteModal.show=false">Annuler</b-button>
                <b-button variant="danger" @click="confirmDelete" :disabled="saving">
                    {{ saving ? '⏳' : 'Supprimer' }}
                </b-button>
            </div>
        </b-modal>

    </VerticalLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tree from 'primevue/tree'
import axios from 'axios'

// ══════════════════════════════════════════
// PROPS
// ══════════════════════════════════════════
const props = defineProps({
    appetites:     Array,
    nomenclatures: Array,
})

// ══════════════════════════════════════════
// UI STATE
// ══════════════════════════════════════════
const activeTab  = ref('appetites')
const editMode   = ref(false)
const saving     = ref(false)
const expandedKeys = ref({})

const alert = ref({ show: false, variant: 'success', message: '' })
const showAlert = (message, variant = 'success') => {
    alert.value = { show: true, variant, message }
    setTimeout(() => { alert.value.show = false }, 3500)
}

const tabVariant = t => activeTab.value === t ? 'danger' : 'outline-danger'

// ══════════════════════════════════════════
// FORMS
// ══════════════════════════════════════════
const defaultAppetiteForm = () => ({ id: null, code: '', label: '', description: '', score_min: 0, score_max: 0, color: '#6c757d', sort_order: 0 })
const defaultNomForm      = () => ({ id: null, code: '', label: '', description: '', appetite_id: null, parent_id: null, level: 1 })

const appetiteForm = ref(defaultAppetiteForm())
const nomForm      = ref(defaultNomForm())

// ══════════════════════════════════════════
// SÉLECTEURS PARENTS (Niv. 2 et 3)
// ══════════════════════════════════════════
const selectedParentL1      = ref(null)
const selectedParentL1ForL3 = ref(null)
const selectedParentL2      = ref(null)
const sector                = ref('')

// ══════════════════════════════════════════
// LISTES CALCULÉES
// ══════════════════════════════════════════
const level1List = computed(() => props.nomenclatures.filter(n => n.level === 1))
const level2List = computed(() => props.nomenclatures.filter(n => n.level === 2))
const level3List = computed(() => props.nomenclatures.filter(n => n.level === 3))

const level2Filtered = computed(() => {
    if (!selectedParentL1.value) return []
    return props.nomenclatures.filter(n => n.level === 2 && n.parent_id === Number(selectedParentL1.value))
})

const level3Filtered = computed(() => {
    if (!selectedParentL2.value) return []
    return props.nomenclatures.filter(n => n.level === 3 && n.parent_id === Number(selectedParentL2.value))
})

const level2OptionsForL3 = computed(() => {
    const list = props.nomenclatures.filter(n => n.level === 2 && n.parent_id === Number(selectedParentL1ForL3.value))
    return [
        { value: null, text: '— Sélectionner une famille —', disabled: true },
        ...list.map(n => ({ value: String(n.id), text: `${n.code} — ${n.label}` }))
    ]
})

const selectedDomainLabel = computed(() => {
    const found = props.nomenclatures.find(n => n.id === Number(selectedParentL1.value))
    return found?.label ?? ''
})

const selectedFamilyLabel = computed(() => {
    const found = props.nomenclatures.find(n => n.id === Number(selectedParentL2.value))
    return found?.label ?? ''
})

const childrenCount = (parentId) =>
    props.nomenclatures.filter(n => n.parent_id === parentId).length

// ══════════════════════════════════════════
// SELECT OPTIONS
// ══════════════════════════════════════════
const appetiteOptions = computed(() => [
    { value: null, text: '— Aucune (héritage) —' },
    ...props.appetites.map(a => ({ value: String(a.id), text: `${a.code} — ${a.label} (${a.score_min}–${a.score_max})` }))
])

const level1Options = computed(() => [
    { value: null, text: '— Sélectionner un domaine —', disabled: true },
    ...level1List.value.map(n => ({ value: String(n.id), text: `${n.code} — ${n.label}` }))
])

// ══════════════════════════════════════════
// IA STATE
// ══════════════════════════════════════════
const isLoadingAI           = ref(false)
const aiDomainSuggestions   = ref([])
const aiFamilySuggestions   = ref([])
const aiTypeSuggestions     = ref([])

const loadDomainSuggestions = async () => {
    if (!sector.value) return
    aiDomainSuggestions.value = []
    isLoadingAI.value = true
    try {
        const res = await axios.post(route('risk.core.nomenclature.ai.suggest-domains'), {
            sector:           sector.value,
            existing_domains: level1List.value.map(n => n.code),
        })
        if (res.data.success) aiDomainSuggestions.value = res.data.domains ?? []
    } catch (err) {
        console.error('❌ Suggestions domaines:', err)
        showAlert('Erreur IA : ' + (err.response?.data?.error ?? err.message), 'danger')
    } finally {
        isLoadingAI.value = false
    }
}

const loadFamilySuggestions = async () => {
    if (!selectedParentL1.value) return
    const domain = props.nomenclatures.find(n => n.id === Number(selectedParentL1.value))
    if (!domain) return
    aiFamilySuggestions.value = []
    isLoadingAI.value = true
    try {
        const res = await axios.post(route('risk.core.nomenclature.ai.suggest-families'), {
            domain_code:  domain.code,
            domain_label: domain.label,
            sector:       sector.value || 'entreprise',
        })
        if (res.data.success) aiFamilySuggestions.value = res.data.families ?? []
    } catch (err) {
        console.error('❌ Suggestions familles:', err)
        showAlert('Erreur IA : ' + (err.response?.data?.error ?? err.message), 'danger')
    } finally {
        isLoadingAI.value = false
    }
}

const loadTypeSuggestions = async () => {
    if (!selectedParentL2.value) return
    const family = props.nomenclatures.find(n => n.id === Number(selectedParentL2.value))
    const domain = props.nomenclatures.find(n => n.id === family?.parent_id)
    if (!family) return
    aiTypeSuggestions.value = []
    isLoadingAI.value = true
    try {
        const res = await axios.post(route('risk.core.nomenclature.ai.suggest-types'), {
            family_code:  family.code,
            family_label: family.label,
            domain_label: domain?.label ?? '',
            sector:       sector.value || 'entreprise',
        })
        if (res.data.success) aiTypeSuggestions.value = res.data.types ?? []
    } catch (err) {
        console.error('❌ Suggestions types:', err)
        showAlert('Erreur IA : ' + (err.response?.data?.error ?? err.message), 'danger')
    } finally {
        isLoadingAI.value = false
    }
}

// ══════════════════════════════════════════
// PRÉ-REMPLISSAGE DEPUIS SUGGESTION IA
// ══════════════════════════════════════════
const prefillLevel1 = (s) => {
    nomForm.value.code  = s.code
    nomForm.value.label = s.label
    nomForm.value.description = s.description ?? ''
}

const prefillLevel2 = (s) => {
    nomForm.value.code  = s.code
    nomForm.value.label = s.label
    nomForm.value.description = s.description ?? ''
}

const prefillLevel3 = (s) => {
    nomForm.value.code  = s.code
    nomForm.value.label = s.label
    nomForm.value.description = s.description ?? ''
}

// ══════════════════════════════════════════
// HANDLERS SÉLECTEURS
// ══════════════════════════════════════════
const onLevel1Selected = () => {
    aiFamilySuggestions.value = []
    nomForm.value.code = ''
    nomForm.value.label = ''
}

const onLevel1ForL3Selected = () => {
    selectedParentL2.value = null
    aiTypeSuggestions.value = []
}

const onLevel2Selected = () => {
    aiTypeSuggestions.value = []
    nomForm.value.code = ''
    nomForm.value.label = ''
}

// ══════════════════════════════════════════
// CRUD APPÉTANCES
// ══════════════════════════════════════════
const resetAppetite = () => {
    appetiteForm.value = defaultAppetiteForm()
    editMode.value = false
}

const editAppetite = (item) => {
    appetiteForm.value = { ...item }
    editMode.value = true
    activeTab.value = 'appetites'
}

const submitAppetite = async () => {
    saving.value = true
    try {
        const isEdit  = editMode.value && appetiteForm.value.id
        const url     = isEdit
            ? route('risk.core.nomenclature.appetites.update', appetiteForm.value.id)
            : route('risk.core.nomenclature.appetites.store')
        const method  = isEdit ? 'put' : 'post'

        const res = await axios[method](url, appetiteForm.value)

        if (res.data.success) {
            showAlert(res.data.message)
            resetAppetite()
            router.reload({ only: ['appetites'] })
        } else {
            showAlert(res.data.error ?? 'Erreur inconnue', 'danger')
        }
    } catch (err) {
        showAlert(err.response?.data?.error ?? err.message, 'danger')
    } finally {
        saving.value = false
    }
}

// ══════════════════════════════════════════
// CRUD NOMENCLATURES
// ══════════════════════════════════════════
const resetNom = () => {
    nomForm.value = defaultNomForm()
    editMode.value = false
}

const editNom = (item) => {
    nomForm.value = {
        id:          item.id,
        code:        item.code,
        label:       item.label,
        description: item.description ?? '',
        appetite_id: item.appetite_id ? String(item.appetite_id) : null,
        parent_id:   item.parent_id,
        level:       item.level,
    }
    editMode.value = true
    activeTab.value = item.level === 1 ? 'level1' : item.level === 2 ? 'level2' : 'level3'
    if (item.level === 2) selectedParentL1.value = String(item.parent_id)
    if (item.level === 3) {
        const parent = props.nomenclatures.find(n => n.id === item.parent_id)
        if (parent) {
            selectedParentL1ForL3.value = String(parent.parent_id)
            selectedParentL2.value      = String(item.parent_id)
        }
    }
}

const submitNomenclature = async (level) => {
    if (level === 2 && !selectedParentL1.value) return
    if (level === 3 && !selectedParentL2.value) return

    saving.value = true
    try {
        const parentId = level === 2
            ? Number(selectedParentL1.value)
            : level === 3
                ? Number(selectedParentL2.value)
                : null

        const payload = {
            code:        nomForm.value.code,
            label:       nomForm.value.label,
            description: nomForm.value.description || null,
            level,
            parent_id:   editMode.value ? nomForm.value.parent_id : parentId,
            appetite_id: nomForm.value.appetite_id ? Number(nomForm.value.appetite_id) : null,
        }

        const isEdit = editMode.value && nomForm.value.id
        const url    = isEdit
            ? route('risk.core.nomenclature.nomenclatures.update', nomForm.value.id)
            : route('risk.core.nomenclature.nomenclatures.store')
        const method = isEdit ? 'put' : 'post'

        const res = await axios[method](url, payload)

        if (res.data.success) {
            showAlert(res.data.message)
            resetNom()
            router.reload({ only: ['nomenclatures'] })
        } else {
            showAlert(res.data.error ?? 'Erreur', 'danger')
        }
    } catch (err) {
        showAlert(err.response?.data?.error ?? err.message, 'danger')
    } finally {
        saving.value = false
    }
}

// ══════════════════════════════════════════
// SUPPRESSION
// ══════════════════════════════════════════
const deleteModal = ref({ show: false, type: null, id: null, label: '' })

const confirmDestroyAppetite = (item) => {
    deleteModal.value = { show: true, type: 'appetite', id: item.id, label: `${item.code} — ${item.label}` }
}

const confirmDestroyNom = (item) => {
    deleteModal.value = { show: true, type: 'nomenclature', id: item.id, label: `${item.code} — ${item.label}` }
}

const confirmDelete = async () => {
    saving.value = true
    try {
        const url = deleteModal.value.type === 'appetite'
            ? route('risk.core.nomenclature.appetites.destroy', deleteModal.value.id)
            : route('risk.core.nomenclature.nomenclatures.destroy', deleteModal.value.id)

        const res = await axios.delete(url)
        if (res.data.success) {
            showAlert(res.data.message)
            deleteModal.value.show = false
            router.reload({ only: deleteModal.value.type === 'appetite' ? ['appetites'] : ['nomenclatures'] })
        } else {
            showAlert(res.data.error ?? 'Erreur', 'danger')
        }
    } catch (err) {
        showAlert(err.response?.data?.error ?? err.message, 'danger')
    } finally {
        saving.value = false
    }
}

// ══════════════════════════════════════════
// ARBORESCENCE PRIMEVUE TREE
// ══════════════════════════════════════════
const treeNodes = computed(() => {
    const makeNode = (n) => ({
        key:      `N-${n.id}`,
        label:    n.label,
        data:     { code: n.code, level: n.level, appetite: n.appetite ?? null },
        children: [],
    })

    const roots = props.nomenclatures.filter(n => n.level === 1)

    return roots.map(l1 => {
        const node1    = makeNode(l1)
        const children = props.nomenclatures.filter(n => n.level === 2 && n.parent_id === l1.id)

        node1.children = children.map(l2 => {
            const node2    = makeNode(l2)
            const leaves   = props.nomenclatures.filter(n => n.level === 3 && n.parent_id === l2.id)
            node2.children = leaves.map(l3 => makeNode(l3))
            return node2
        })

        return node1
    })
})
</script>

<style scoped>
.form-control-sm, .form-select-sm { font-size:.75rem; height:26px; padding:.15rem .45rem }
.btn-sm { padding:.15rem .45rem; font-size:.72rem }

/* Stats */
.stat-card { border-left: 4px solid transparent; transition: all 0.2s }
.stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); transform: translateY(-2px) }
.stat-icon { width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:white; font-size:18px }
.stat-badge { display:inline-block; padding:2px 8px; border-radius:4px; font-size:12px; font-weight:600 }
.badge-danger    { background:#f8d7da; color:#721c24 }
.badge-info      { background:#d1ecf1; color:#0c5460 }
.badge-warning   { background:#fff3cd; color:#856404 }
.badge-secondary { background:#e2e3e5; color:#383d41 }

/* Tables PrimeVue */
.pv-table :deep(.p-datatable-thead>tr>th) { background:#f8fafc; border:1px solid #e5e7eb; padding:.25rem .35rem; font-size:.73rem }
.pv-table :deep(.p-datatable-tbody>tr>td) { border:1px solid #eef2f7; padding:.25rem .35rem; font-size:.72rem }

/* IA */
.ai-sector-box { background:#f8f9fa; border:1px dashed #dee2e6; border-radius:.4rem; padding:.5rem .75rem }
.ai-suggestions-box { background: linear-gradient(135deg,#e3f2fd 0%,#bbdefb 100%); border:2px solid #1976d2; border-radius:.4rem; padding:.6rem .75rem }
.ai-header { font-weight:600; color:#0d47a1; margin-bottom:.5rem; font-size:.82rem }

.suggestion-chips { display:flex; flex-wrap:wrap; gap:.4rem }
.suggestion-chip  { padding:.3rem .7rem; cursor:pointer; user-select:none; transition:all .2s; font-size:.78rem; border-radius:20px; display:inline-flex; align-items:center; background:#e3f2fd; color:#1565c0; border:1px solid #90caf9 }
.suggestion-chip:hover { background:#bbdefb; transform:scale(1.04); box-shadow:0 2px 6px rgba(0,0,0,.15) }

/* Appétance badges */
.apt-badge { padding:.1rem .5rem; border-radius:12px; font-size:.7rem; font-weight:700; border:1px solid }
.color-dot { width:10px; height:10px; border-radius:50%; display:inline-block; flex-shrink:0 }

/* Preview */
.preview-badge { display:inline-flex; align-items:center; gap:.4rem; padding:.3rem .8rem; border-radius:20px; border:2px solid; font-size:.82rem; font-weight:500 }

/* Tree niveaux */
.level-badge { width:20px; height:20px; border-radius:4px; display:inline-flex; align-items:center; justify-content:center; font-size:.65rem; font-weight:800; color:white; flex-shrink:0 }
.level-1 { background:#0d6efd }
.level-2 { background:#0dcaf0 }
.level-3 { background:#ffc107; color:#000 }

.code-chip { background:#eef2f7; border:1px solid #e2e8f0; padding:.03rem .3rem; border-radius:.3rem; font-size:.68rem }
</style>
