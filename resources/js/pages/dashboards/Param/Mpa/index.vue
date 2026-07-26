<template>
  <VerticalLayout>
    <Head title="DIADDEM — MPA (Global)" />

    <!-- HEADER -->
    <b-row class="mb-0">
      <b-col>
        <div class="d-flex align-items-center gap-2">
          <i class="ti ti-topology-star-3 text-primary fs-5"></i>
          <h4 class="m-0 fw-semibold">Macro-Processus-Activités</h4>
          <small class="text-muted ms-2">🤖 Mode global avec suggestions IA</small>
        </div>
      </b-col>
    </b-row>

    <!-- STATISTIQUES GLOBALES -->
    <b-row class="g-2 mb-2">
      <b-col lg="3">
        <b-card no-body class="shadow-sm stat-card">
          <b-card-body class="p-2">
            <div class="d-flex align-items-center gap-2">
              <div class="stat-icon bg-primary"><i class="ti ti-cube"></i></div>
              <div class="flex-grow-1">
                <small class="text-muted">Macros</small>
                <h5 class="mb-0 fw-bold">{{ macros.length }}/3</h5>
              </div>
              <div class="stat-badge" :class="macrosLocked ? 'badge-success' : 'badge-warning'">
                {{ macrosLocked ? '✅' : '⚠️' }}
              </div>
            </div>
          </b-card-body>
        </b-card>
      </b-col>

      <b-col lg="3">
        <b-card no-body class="shadow-sm stat-card">
          <b-card-body class="p-2">
            <div class="d-flex align-items-center gap-2">
              <div class="stat-icon bg-info"><i class="ti ti-settings"></i></div>
              <div class="flex-grow-1">
                <small class="text-muted">Processus</small>
                <h5 class="mb-0 fw-bold">{{ processes.length }}</h5>
              </div>
              <div class="stat-badge" :class="processes.length > 0 ? 'badge-info' : 'badge-secondary'">📊</div>
            </div>
          </b-card-body>
        </b-card>
      </b-col>

      <b-col lg="3">
        <b-card no-body class="shadow-sm stat-card">
          <b-card-body class="p-2">
            <div class="d-flex align-items-center gap-2">
              <div class="stat-icon bg-success"><i class="ti ti-list-details"></i></div>
              <div class="flex-grow-1">
                <small class="text-muted">Activités</small>
                <h5 class="mb-0 fw-bold">{{ activities.length }}</h5>
              </div>
              <div class="stat-badge" :class="activities.length > 0 ? 'badge-success' : 'badge-secondary'">📋</div>
            </div>
          </b-card-body>
        </b-card>
      </b-col>

      <b-col lg="3">
        <b-card no-body class="shadow-sm stat-card">
          <b-card-body class="p-2">
            <div class="d-flex align-items-center gap-2">
              <div class="stat-icon bg-warning"><i class="ti ti-target-arrow"></i></div>
              <div class="flex-grow-1">
                <small class="text-muted">Objectifs</small>
                <h5 class="mb-0 fw-bold">{{ objectifs.length }}</h5>
              </div>
              <div class="stat-badge" :class="objectifs.length > 0 ? 'badge-warning' : 'badge-secondary'">🎯</div>
            </div>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>

    <!-- TABS -->
    <b-card no-body class="mb-2 shadow-none border-0">
      <b-card-body class="p-1">
        <b-button-group size="sm">
          <b-button @click="activeTab='macro'"    :variant="tabVariant('macro')"   ><i class="ti ti-cube me-1"></i> Macro</b-button>
          <b-button @click="activeTab='process'"  :variant="tabVariant('process')" ><i class="ti ti-settings me-1"></i> Processus</b-button>
          <b-button @click="activeTab='activity'" :variant="tabVariant('activity')"><i class="ti ti-list-details me-1"></i> Activités</b-button>
        </b-button-group>
      </b-card-body>
    </b-card>

    <b-row class="g-1">

      <!-- GAUCHE -->
      <b-col lg="6" xl="6">
        <b-card no-body class="shadow-sm">

          <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
              <span v-if="activeTab==='macro'">Macros</span>
              <span v-else-if="activeTab==='process'">Création Processus</span>
              <span v-else>Création Activité</span>
            </h6>
            <small class="text-muted">Global</small>
          </b-card-header>

          <b-card-body class="p-2">

            <!-- ===================== MACRO ===================== -->
            <div v-if="activeTab==='macro'">
              <b-alert :variant="macrosLocked?'success':'info'" show class="py-2 px-3 mb-2">
                <i class="ti" :class="macrosLocked?'ti-lock':'ti-info-circle'"></i>
                <span v-if="macrosLocked"> ✅ Ensemble complet (3/3). Cliquez pour renommer.</span>
                <span v-else> ⚠️ Cliquez « Valider les 3 par défaut » pour créer D/R/S.</span>
              </b-alert>

              <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
                <span>
                  <template v-if="macrosLocked"><i class="ti ti-checks"></i> Verrouillés</template>
                  <template v-else><i class="ti ti-alert-triangle"></i> Manquants : {{ missingKinds.join(" • ") }}</template>
                </span>
                <b-button size="sm" variant="outline-primary" @click="validateDefaults" :disabled="macrosLocked">
                  <i class="ti ti-checkup-list me-1"></i> Valider par défaut
                </b-button>
              </div>

              <DataTable :value="macrosFilteredSorted" size="small" class="pv-table flat">
                <Column header="Code" style="width:100px">
                  <template #body="{ data }"><Tag :value="data.code" :severity="kindSeverity(data.kind)" /></template>
                </Column>
                <Column header="Type" style="width:120px">
                  <template #body="{ data }"><Tag :value="data.kind" :severity="kindSeverity(data.kind)" /></template>
                </Column>
                <Column header="Nom">
                  <template #body="{ data }"><span class="fw-semibold">{{ data.name }}</span></template>
                </Column>
                <Column header="Stats" style="width:120px" bodyClass="text-center">
                  <template #body="{ data }">
                    <small class="text-muted">{{ getProcessCountByMacro(data.id) }} proc • {{ getDataCountByMacro(data.id) }} données</small>
                  </template>
                </Column>
                <Column header="" style="width:40px" bodyClass="text-end">
                  <template #body="{ data }">
                    <b-button size="sm" variant="light" @click="openEditModal(data)" title="Renommer"><i class="ti ti-pencil"></i></b-button>
                  </template>
                </Column>
                <template #empty><div class="text-muted py-1">Aucun macro</div></template>
              </DataTable>
            </div>

            <!-- ===================== PROCESSUS AVEC IA ===================== -->
            <div v-else-if="activeTab==='process'">

              <b-form @submit.prevent="submitProcess" class="mb-2">
                <b-row class="g-2">

                  <b-col cols="12">
                    <label class="form-label mb-1">Macro *</label>
                    <b-form-select class="form-select-sm" v-model="processForm.macro_process_id" :options="macroOptions" required @change="onMacroSelected"/>
                  </b-col>

                  <!-- ✅ Checkbox "Programme" -->
                  <b-col v-if="selectedMacroIsRealisation" cols="12">
                    <b-form-checkbox v-model="processForm.has_objectives" switch class="objectives-toggle" @change="onHasObjectivesToggle">
                      <span class="fw-semibold">Ce processus est un programme (fonctionne par Objectifs)</span>
                      <br>
                      <small class="text-muted">
                        Si activé : l'IA suggère immédiatement des objectifs. Chaque objectif aura ses propres
                        données d'entrée/sortie/ressources et ses propres activités. Le processus n'a alors plus
                        de données globales.
                      </small>
                    </b-form-checkbox>
                  </b-col>

                  <!-- 🤖 SUGGESTIONS PROCESSUS -->
                  <b-col v-if="processusSuggestions.length > 0" cols="12">
                    <div class="ai-suggestions-box">
                      <div class="ai-header">
                        ✨ <strong>Suggestions de processus (IA)</strong>
                        <small v-if="isLoadingProcessus" class="text-info ms-2"><i class="ti ti-loading"></i> Génération...</small>
                      </div>
                      <div class="suggestion-chips">
                        <b-badge v-for="(sugg, idx) in processusSuggestions" :key="idx" bg="info" class="suggestion-chip"
                          @click="selectProcessusSuggestion(sugg)" role="button" title="Cliquer pour sélectionner">
                          <i class="ti ti-click me-1"></i>{{ sugg.name }}
                        </b-badge>
                      </div>
                    </div>
                  </b-col>

                  <b-col cols="12">
                    <label class="form-label mb-1">Code (auto)</label>
                    <b-form-input class="form-control-sm font-monospace" :value="nextProcessCode" disabled/>
                  </b-col>

                  <b-col cols="12">
                    <label class="form-label mb-1">Nom Processus *</label>
                    <b-form-input class="form-control-sm" v-model.trim="processForm.name" required placeholder="Ex: Gestion des commandes"/>
                  </b-col>

                  <!-- ═══ MODE PROGRAMME : suggestions + liste d'objectifs (à créer après validation du process) ═══ -->
                  <b-col v-if="processForm.has_objectives" cols="12" class="pt-2 border-top">
                    <b-alert variant="warning" show class="py-2 px-3 mb-2">
                      <i class="ti ti-target-arrow"></i>
                      Mode Programme activé. Les objectifs se créeront une fois le processus validé,
                      dans l'onglet <strong>Activités</strong>. Voici déjà des suggestions d'objectifs :
                    </b-alert>

                    <div v-if="objectifsSuggestionsPreview.length > 0" class="ai-suggestions-box">
                      <div class="ai-header">
                        ✨ <strong>Objectifs suggérés (IA)</strong>
                        <small v-if="isLoadingObjectifsPreview" class="text-info ms-2"><i class="ti ti-loading"></i> Génération...</small>
                      </div>
                      <div class="suggestion-chips">
                        <b-badge v-for="(sugg, idx) in objectifsSuggestionsPreview" :key="idx" bg="warning" text="dark" class="suggestion-chip">
                          <i class="ti ti-target-arrow me-1"></i>{{ sugg.name }}
                        </b-badge>
                      </div>
                      <small class="text-muted d-block mt-2">Ces suggestions seront réutilisées automatiquement dans l'onglet Activités.</small>
                    </div>
                    <div v-else-if="isLoadingObjectifsPreview" class="text-muted small">
                      <i class="ti ti-loading"></i> Génération des suggestions d'objectifs...
                    </div>
                  </b-col>

                  <!-- ═══ MODE SIMPLE : données d'entrée/sortie/ressources du processus ═══ -->
                  <template v-else>

                    <b-col v-if="dataProcessusSuggestions.inputs && dataProcessusSuggestions.inputs.length > 0" cols="12" class="pt-2 border-top">
                      <div class="ai-suggestions-data">
                        <div class="ai-header-small">📋 Données suggérées (cliquer pour ajouter)</div>

                        <div class="mb-2">
                          <small class="text-muted fw-semibold">Entrées:</small>
                          <div class="suggestion-chips-small">
                            <b-badge v-for="(inp, idx) in dataProcessusSuggestions.inputs" :key="'inp-'+idx" bg="light" text="dark"
                              class="suggestion-chip-sm" @click="processForm.inputs.push(inp)" role="button">+ {{ inp }}</b-badge>
                          </div>
                        </div>

                        <div class="mb-2">
                          <small class="text-muted fw-semibold">Sorties:</small>
                          <div class="suggestion-chips-small">
                            <b-badge v-for="(out, idx) in dataProcessusSuggestions.outputs" :key="'out-'+idx" bg="light" text="dark"
                              class="suggestion-chip-sm" @click="processForm.outputs.push(out)" role="button">+ {{ out }}</b-badge>
                          </div>
                        </div>

                        <div>
                          <small class="text-muted fw-semibold">Ressources:</small>
                          <div class="suggestion-chips-small">
                            <b-badge v-for="(res, idx) in dataProcessusSuggestions.resources" :key="'res-'+idx" bg="light" text="dark"
                              class="suggestion-chip-sm" @click="processForm.resources.push(res)" role="button">+ {{ res }}</b-badge>
                          </div>
                        </div>
                      </div>
                    </b-col>

                    <b-col cols="12" class="mt-2">
                      <label class="form-label mb-1 fw-semibold">
                        Données d'entrée <b-badge bg="info" class="ms-2">{{ processForm.inputs.filter(i => i.trim()).length }}</b-badge>
                      </label>
                      <div v-for="(inp, i) in processForm.inputs" :key="'inp'+i" class="d-flex gap-1 mb-1">
                        <b-form-input class="form-control-sm" v-model="processForm.inputs[i]" placeholder="Ex : Demande initiale"/>
                        <b-button size="sm" variant="danger" @click="removeList(processForm.inputs,i)" title="Supprimer">✕</b-button>
                      </div>
                      <b-button size="sm" variant="outline-primary" @click="processForm.inputs.push('')">+ Ajouter</b-button>
                    </b-col>

                    <b-col cols="12" class="mt-2">
                      <label class="form-label mb-1 fw-semibold">
                        Données de sortie <b-badge bg="success" class="ms-2">{{ processForm.outputs.filter(o => o.trim()).length }}</b-badge>
                      </label>
                      <div v-for="(out, i) in processForm.outputs" :key="'out'+i" class="d-flex gap-1 mb-1">
                        <b-form-input class="form-control-sm" v-model="processForm.outputs[i]" placeholder="Ex : Rapport final"/>
                        <b-button size="sm" variant="danger" @click="removeList(processForm.outputs,i)" title="Supprimer">✕</b-button>
                      </div>
                      <b-button size="sm" variant="outline-primary" @click="processForm.outputs.push('')">+ Ajouter</b-button>
                    </b-col>

                    <b-col cols="12" class="mt-2">
                      <label class="form-label mb-1 fw-semibold">
                        Ressources <b-badge bg="warning" class="ms-2">{{ processForm.resources.filter(r => r.trim()).length }}</b-badge>
                      </label>
                      <div v-for="(res, i) in processForm.resources" :key="'res'+i" class="d-flex gap-1 mb-1">
                        <b-form-input class="form-control-sm" v-model="processForm.resources[i]" placeholder="Ex : Logiciel ERP"/>
                        <b-button size="sm" variant="danger" @click="removeList(processForm.resources,i)" title="Supprimer">✕</b-button>
                      </div>
                      <b-button size="sm" variant="outline-primary" @click="processForm.resources.push('')">+ Ajouter</b-button>
                    </b-col>

                  </template>

                  <b-col cols="12" class="text-end pt-1">
                    <b-button size="sm" variant="light" class="me-1" @click="resetProcess">Annuler</b-button>
                    <b-button size="sm" variant="primary" type="submit" :disabled="!processForm.macro_process_id">Valider</b-button>
                  </b-col>

                </b-row>
              </b-form>

              <!-- TABLE PROCESSES -->
              <DataTable :value="processesFiltered" size="small" class="pv-table flat">
                <Column header="Code" style="width:110px">
                  <template #body="{data}"><span class="font-monospace text-primary">{{ data.code }}</span></template>
                </Column>
                <Column header="Nom">
                  <template #body="{data}">
                    <span class="fw-semibold">{{ data.name }}</span>
                    <b-badge v-if="data.has_objectives" bg="warning" class="ms-1" title="Programme">
                      <i class="ti ti-target-arrow"></i> Programme
                    </b-badge>
                  </template>
                </Column>
                <Column header="Entrées" style="width:75px" bodyClass="text-center">
                  <template #body="{data}">
                    <b-badge v-if="!data.has_objectives" :variant="data.inputs?.length > 0 ? 'info' : 'secondary'">
                      <i class="ti ti-arrow-down"></i> {{ data.inputs?.length || 0 }}
                    </b-badge>
                    <small v-else class="text-muted">par objectif</small>
                  </template>
                </Column>
                <Column header="Sorties" style="width:75px" bodyClass="text-center">
                  <template #body="{data}">
                    <b-badge v-if="!data.has_objectives" :variant="data.outputs?.length > 0 ? 'success' : 'secondary'">
                      <i class="ti ti-arrow-up"></i> {{ data.outputs?.length || 0 }}
                    </b-badge>
                    <small v-else class="text-muted">—</small>
                  </template>
                </Column>
                <Column header="Ressources" style="width:95px" bodyClass="text-center">
                  <template #body="{data}">
                    <b-badge v-if="!data.has_objectives" :variant="data.resources?.length > 0 ? 'warning' : 'secondary'">
                      <i class="ti ti-tools"></i> {{ data.resources?.length || 0 }}
                    </b-badge>
                    <small v-else class="text-muted">—</small>
                  </template>
                </Column>
                <Column header="Statut" style="width:80px" bodyClass="text-center">
                  <template #body="{data}">
                    <span v-if="isProcessComplete(data)" class="badge bg-success">✅ Complet</span>
                    <span v-else class="badge bg-warning">⚠️ Incomplet</span>
                  </template>
                </Column>
                <Column header="" style="width:50px" bodyClass="text-end">
                  <template #body="{data}">
                    <b-button size="sm" variant="info" @click="editProcessus(data)" title="Éditer"><i class="ti ti-pencil"></i></b-button>
                  </template>
                </Column>
                <template #empty><div class="text-muted py-1">Aucun processus</div></template>
              </DataTable>
            </div>

            <!-- ===================== ACTIVITÉ / OBJECTIFS AVEC IA ===================== -->
            <div v-else-if="activeTab==='activity'">

              <b-row class="g-2 mb-2">
                <b-col cols="12">
                  <label class="form-label mb-1">Macro *</label>
                  <b-form-select class="form-select-sm" v-model="activityForm.macro_process_id" :options="macroOptionsActivity" required @change="onActivityMacroChanged"/>
                </b-col>
                <b-col cols="12">
                  <label class="form-label mb-1">Processus *</label>
                  <b-form-select class="form-select-sm" v-model="activityForm.process_id" :options="processOptionsActivity" :disabled="!activityForm.macro_process_id" required @change="onProcessActivitySelected"/>
                </b-col>
              </b-row>

              <!-- ═══════════ MODE PROGRAMME (has_objectives === true) ═══════════ -->
              <template v-if="selectedProcessForActivity && selectedProcessForActivity.has_objectives">

                <b-alert variant="warning" show class="py-2 px-3 mb-2">
                  <i class="ti ti-target-arrow"></i>
                  Ce processus est un <strong>Programme</strong>. Choisissez ou créez un objectif, puis créez ses activités.
                </b-alert>

                <div class="quick-stats mb-2 pb-2 border-bottom">
                  <span class="stat-chip"><i class="ti ti-target-arrow"></i> {{ objectifsByProcess.length }} objectifs</span>
                  <span class="stat-chip"><i class="ti ti-list-details"></i> {{ activitiesByProcessAllObjectifs.length }} activités</span>
                </div>

                <!-- 🤖 SUGGESTIONS OBJECTIFS (auto-générées ou reprises depuis l'étape processus) -->
                <div v-if="objectifsSuggestions.length > 0" class="ai-suggestions-box">
                  <div class="ai-header">
                    ✨ <strong>Suggestions d'objectifs (IA)</strong>
                    <small v-if="isLoadingObjectifs" class="text-info ms-2"><i class="ti ti-loading"></i> Génération...</small>
                  </div>
                  <div class="suggestion-chips">
                    <b-badge v-for="(sugg, idx) in objectifsSuggestions" :key="idx" bg="warning" text="dark" class="suggestion-chip"
                      @click="selectObjectifSuggestion(sugg)" role="button" title="Cliquer pour sélectionner">
                      <i class="ti ti-click me-1"></i>{{ sugg.name }}
                    </b-badge>
                  </div>
                </div>

                <!-- FORM OBJECTIF (avec ses propres données) -->
                <b-form @submit.prevent="submitObjectif" class="mb-3">
                  <b-row class="g-2">
                    <b-col cols="12">
                      <label class="form-label mb-1">Nom de l'objectif *</label>
                      <b-form-input class="form-control-sm" v-model.trim="objectifForm.name" required placeholder="Ex: Réduire le délai de traitement" @blur="onObjectifNameEntered"/>
                    </b-col>
                    <b-col cols="12">
                      <label class="form-label mb-1">Description</label>
                      <b-form-textarea rows="2" class="form-control-sm" v-model.trim="objectifForm.description" placeholder="Détails de l'objectif..."/>
                    </b-col>

                    <!-- 🤖 SUGGESTIONS DONNÉES DE L'OBJECTIF -->
                    <b-col v-if="dataObjectifSuggestions.inputs && dataObjectifSuggestions.inputs.length > 0" cols="12" class="pt-2 border-top">
                      <div class="ai-suggestions-data">
                        <div class="ai-header-small">
                          📋 Données suggérées pour cet objectif
                          <small v-if="isLoadingObjectifData" class="text-info ms-2"><i class="ti ti-loading"></i> Génération...</small>
                        </div>

                        <div class="mb-2">
                          <small class="text-muted fw-semibold">Entrées:</small>
                          <div class="suggestion-chips-small">
                            <b-badge v-for="(inp, idx) in dataObjectifSuggestions.inputs" :key="'oinp-'+idx" bg="light" text="dark"
                              class="suggestion-chip-sm" @click="objectifForm.inputs.push(inp)" role="button">+ {{ inp }}</b-badge>
                          </div>
                        </div>

                        <div class="mb-2">
                          <small class="text-muted fw-semibold">Sorties:</small>
                          <div class="suggestion-chips-small">
                            <b-badge v-for="(out, idx) in dataObjectifSuggestions.outputs" :key="'oout-'+idx" bg="light" text="dark"
                              class="suggestion-chip-sm" @click="objectifForm.outputs.push(out)" role="button">+ {{ out }}</b-badge>
                          </div>
                        </div>

                        <div>
                          <small class="text-muted fw-semibold">Ressources:</small>
                          <div class="suggestion-chips-small">
                            <b-badge v-for="(res, idx) in dataObjectifSuggestions.resources" :key="'ores-'+idx" bg="light" text="dark"
                              class="suggestion-chip-sm" @click="objectifForm.resources.push(res)" role="button">+ {{ res }}</b-badge>
                          </div>
                        </div>
                      </div>
                    </b-col>

                    <b-col cols="12" class="mt-1">
                      <label class="form-label mb-1 fw-semibold">
                        Données d'entrée <b-badge bg="info" class="ms-2">{{ objectifForm.inputs.filter(i => i.trim()).length }}</b-badge>
                      </label>
                      <div v-for="(inp, i) in objectifForm.inputs" :key="'oi'+i" class="d-flex gap-1 mb-1">
                        <b-form-input class="form-control-sm" v-model="objectifForm.inputs[i]" placeholder="Ex : Cahier des charges"/>
                        <b-button size="sm" variant="danger" @click="removeList(objectifForm.inputs,i)">✕</b-button>
                      </div>
                      <b-button size="sm" variant="outline-primary" @click="objectifForm.inputs.push('')">+ Ajouter</b-button>
                    </b-col>

                    <b-col cols="12" class="mt-2">
                      <label class="form-label mb-1 fw-semibold">
                        Données de sortie <b-badge bg="success" class="ms-2">{{ objectifForm.outputs.filter(o => o.trim()).length }}</b-badge>
                      </label>
                      <div v-for="(out, i) in objectifForm.outputs" :key="'oo'+i" class="d-flex gap-1 mb-1">
                        <b-form-input class="form-control-sm" v-model="objectifForm.outputs[i]" placeholder="Ex : Livrable validé"/>
                        <b-button size="sm" variant="danger" @click="removeList(objectifForm.outputs,i)">✕</b-button>
                      </div>
                      <b-button size="sm" variant="outline-primary" @click="objectifForm.outputs.push('')">+ Ajouter</b-button>
                    </b-col>

                    <b-col cols="12" class="mt-2">
                      <label class="form-label mb-1 fw-semibold">
                        Ressources <b-badge bg="warning" class="ms-2">{{ objectifForm.resources.filter(r => r.trim()).length }}</b-badge>
                      </label>
                      <div v-for="(res, i) in objectifForm.resources" :key="'or'+i" class="d-flex gap-1 mb-1">
                        <b-form-input class="form-control-sm" v-model="objectifForm.resources[i]" placeholder="Ex : Équipe projet"/>
                        <b-button size="sm" variant="danger" @click="removeList(objectifForm.resources,i)">✕</b-button>
                      </div>
                      <b-button size="sm" variant="outline-primary" @click="objectifForm.resources.push('')">+ Ajouter</b-button>
                    </b-col>

                    <b-col cols="12" class="text-end pt-1">
                      <b-button size="sm" variant="light" class="me-1" @click="resetObjectifForm">Annuler</b-button>
                      <b-button size="sm" variant="warning" type="submit">+ Ajouter l'objectif</b-button>
                    </b-col>
                  </b-row>
                </b-form>

                <!-- LISTE DES OBJECTIFS DU PROCESSUS -->
                <div v-for="obj in objectifsByProcess" :key="obj.id" class="objectif-card mb-2">
                  <div class="objectif-card-header" @click="toggleObjectifExpand(obj.id)">
                    <div class="d-flex align-items-center gap-2">
                      <i class="ti" :class="expandedObjectifs[obj.id] ? 'ti-chevron-down' : 'ti-chevron-right'"></i>
                      <i class="ti ti-target-arrow text-warning"></i>
                      <span class="fw-semibold">{{ obj.name }}</span>
                      <b-badge bg="secondary" class="ms-1">{{ getActivitesByObjectif(obj.id).length }} activité(s)</b-badge>
                    </div>
                    <div class="d-flex gap-1">
                      <b-button size="sm" variant="light" @click.stop="editObjectif(obj)" title="Éditer"><i class="ti ti-pencil"></i></b-button>
                      <b-button size="sm" variant="light" @click.stop="selectObjectifForActivity(obj)" title="Créer une activité ici"><i class="ti ti-plus"></i></b-button>
                    </div>
                  </div>

                  <div v-if="expandedObjectifs[obj.id]" class="objectif-card-body">
                    <p v-if="obj.description" class="text-muted small mb-2">{{ obj.description }}</p>

                    <div class="quick-stats mb-2">
                      <span class="stat-chip"><i class="ti ti-arrow-down"></i> {{ obj.inputs?.length || 0 }} entrées</span>
                      <span class="stat-chip"><i class="ti ti-arrow-up"></i> {{ obj.outputs?.length || 0 }} sorties</span>
                      <span class="stat-chip"><i class="ti ti-tools"></i> {{ obj.resources?.length || 0 }} ressources</span>
                    </div>

                    <!-- 🤖 SUGGESTIONS ACTIVITÉS POUR CET OBJECTIF -->
                    <div v-if="activityForm.objectif_id === obj.id && activitesObjectifSuggestions.length > 0" class="ai-suggestions-box mb-2">
                      <div class="ai-header">
                        ✨ <strong>Suggestions d'activités (IA)</strong>
                        <small v-if="isLoadingActivitesObjectif" class="text-info ms-2"><i class="ti ti-loading"></i> Génération...</small>
                      </div>
                      <div class="suggestion-chips">
                        <b-badge v-for="(sugg, idx) in activitesObjectifSuggestions" :key="idx" bg="success" class="suggestion-chip"
                          @click="selectActivitesObjectifSuggestion(sugg)" role="button">
                          <i class="ti ti-click me-1"></i>{{ sugg.name }}
                        </b-badge>
                      </div>
                    </div>

                    <!-- FORM ACTIVITÉ (rattachée à cet objectif) -->
                    <b-form v-if="activityForm.objectif_id === obj.id" @submit.prevent="submitActivity" class="mb-2 ps-2 border-start border-3 border-warning">
                      <b-row class="g-2">
                        <b-col cols="12">
                          <label class="form-label mb-1">Nom de l'activité *</label>
                          <b-form-input class="form-control-sm" v-model.trim="activityForm.name" required placeholder="Ex: Valider la demande"/>
                        </b-col>
                        <b-col cols="12">
                          <label class="form-label mb-1">Description</label>
                          <b-form-textarea rows="2" class="form-control-sm" v-model.trim="activityForm.description" placeholder="Détails de l'activité..."/>
                        </b-col>
                        <b-col cols="12" class="text-end pt-1">
                          <b-button size="sm" variant="light" class="me-1" @click="resetActivity">Annuler</b-button>
                          <b-button size="sm" variant="primary" type="submit">Valider</b-button>
                        </b-col>
                      </b-row>
                    </b-form>

                    <DataTable :value="getActivitesByObjectif(obj.id)" size="small" class="pv-table flat">
                      <Column header="Code" style="width:140px">
                        <template #body="{data}"><span class="font-monospace text-primary">{{ data.code }}</span></template>
                      </Column>
                      <Column header="Activité">
                        <template #body="{data}"><span class="fw-semibold">{{ data.name }}</span></template>
                      </Column>
                      <Column header="Description" style="width:180px">
                        <template #body="{data}"><small class="text-muted">{{ data.description || '—' }}</small></template>
                      </Column>
                      <Column header="" style="width:50px" bodyClass="text-end">
                        <template #body="{data}">
                          <b-button size="sm" variant="info" @click="editActivite(data)" title="Éditer"><i class="ti ti-pencil"></i></b-button>
                        </template>
                      </Column>
                      <template #empty><div class="text-muted py-1 small">Aucune activité pour cet objectif</div></template>
                    </DataTable>
                  </div>
                </div>

                <div v-if="objectifsByProcess.length === 0" class="text-muted text-center py-3">
                  Aucun objectif pour ce processus. Créez-en un ci-dessus.
                </div>

              </template>

              <!-- ═══════════ MODE SIMPLE (has_objectives === false) ═══════════ -->
              <template v-else>

                <b-form @submit.prevent="submitActivity" class="mb-2">
                  <b-row class="g-2">

                    <b-col v-if="activityForm.process_id" cols="12" class="border-bottom pb-2 mb-2">
                      <div class="quick-stats">
                        <span class="stat-chip"><i class="ti ti-list-details"></i> {{ activitiesByProcess.length }} activités</span>
                        <span class="stat-chip"><i class="ti ti-arrow-down"></i> {{ getProcessInputCount(activityForm.process_id) }} entrées</span>
                        <span class="stat-chip"><i class="ti ti-arrow-up"></i> {{ getProcessOutputCount(activityForm.process_id) }} sorties</span>
                        <span class="stat-chip"><i class="ti ti-tools"></i> {{ getProcessResourceCount(activityForm.process_id) }} ressources</span>
                      </div>
                    </b-col>

                    <b-col v-if="activitesSuggestions.length > 0" cols="12">
                      <div class="ai-suggestions-box">
                        <div class="ai-header">
                          ✨ <strong>Suggestions d'activités (IA)</strong>
                          <small v-if="isLoadingActivites" class="text-info ms-2"><i class="ti ti-loading"></i> Génération...</small>
                        </div>
                        <div class="suggestion-chips">
                          <b-badge v-for="(sugg, idx) in activitesSuggestions" :key="idx" bg="success" class="suggestion-chip"
                            @click="selectActivitesSuggestion(sugg)" role="button" title="Cliquer pour sélectionner">
                            <i class="ti ti-click me-1"></i>{{ sugg.name }}
                          </b-badge>
                        </div>
                      </div>
                    </b-col>

                    <b-col cols="12">
                      <label class="form-label mb-1">Code (auto)</label>
                      <b-form-input class="form-control-sm font-monospace" :value="nextActivityCode" disabled/>
                    </b-col>

                    <b-col cols="12">
                      <label class="form-label mb-1">Nom *</label>
                      <b-form-input class="form-control-sm" v-model.trim="activityForm.name" required placeholder="Ex: Valider la demande"/>
                    </b-col>

                    <b-col cols="12">
                      <label class="form-label mb-1">Description</label>
                      <b-form-textarea rows="2" class="form-control-sm" v-model.trim="activityForm.description" placeholder="Détails de l'activité..."/>
                    </b-col>

                    <b-col cols="12" class="text-end pt-1">
                      <b-button size="sm" variant="light" class="me-1" @click="resetActivity">Annuler</b-button>
                      <b-button size="sm" variant="primary" type="submit">Valider</b-button>
                    </b-col>

                  </b-row>
                </b-form>

                <DataTable :value="activitiesFiltered" size="small" class="pv-table flat">
                  <Column header="Code" style="width:140px">
                    <template #body="{data}"><span class="font-monospace text-primary">{{ data.code }}</span></template>
                  </Column>
                  <Column header="Activité">
                    <template #body="{data}"><span class="fw-semibold">{{ data.name }}</span></template>
                  </Column>
                  <Column header="Description" style="width:200px">
                    <template #body="{data}"><small class="text-muted">{{ data.description || '—' }}</small></template>
                  </Column>
                  <Column header="" style="width:50px" bodyClass="text-end">
                    <template #body="{data}">
                      <b-button size="sm" variant="info" @click="editActivite(data)" title="Éditer"><i class="ti ti-pencil"></i></b-button>
                    </template>
                  </Column>
                  <template #empty><div class="text-muted py-1">Aucune activité</div></template>
                </DataTable>

              </template>

            </div>

          </b-card-body>

        </b-card>
      </b-col>

      <!-- DROITE : ARBORESCENCE -->
      <b-col lg="6" xl="6">
        <b-card no-body class="shadow-sm h-100">
          <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="ti ti-folders me-1"></i> Arborescence</h6>
            <small class="text-muted">Global</small>
          </b-card-header>

          <b-card-body class="p-2">
            <Tree :value="treeNodes" v-model:expandedKeys="expandedKeys" selectionMode="single" :filter="true" filterMode="lenient" class="w-100 pv-tree rounded">
              <template #default="{ node }">
                <div class="d-flex align-items-center gap-2">
                  <span class="icon-badge" :class="typeColorClass(node)">
                    <i :class="nodeIcon(node)" class="node-icon"></i>
                    <span class="badge-letter">{{ badgeLetter(node) }}</span>
                  </span>
                  <span class="code-chip font-monospace">{{ node.data.code }}</span>
                  <span class="fw-semibold">{{ node.label }}</span>
                  <span v-if="node.data.dataCount" class="badge bg-light text-dark ms-auto">📊 {{ node.data.dataCount }}</span>
                </div>
              </template>
            </Tree>
          </b-card-body>
        </b-card>
      </b-col>

    </b-row>

    <!-- MODALES -->
    <b-modal v-model="edit.show" title="Renommer le macro" hide-footer>
      <b-form @submit.prevent="submitEdit">
        <b-form-group label="Nom du macro">
          <b-form-input v-model.trim="edit.name" required autofocus/>
        </b-form-group>
        <div class="text-end mt-2">
          <b-button variant="light" class="me-2" @click="edit.show=false">Annuler</b-button>
          <b-button variant="primary" type="submit" :disabled="savingEdit">Enregistrer</b-button>
        </div>
      </b-form>
    </b-modal>

    <b-modal v-model="editProcessusModal.show" title="Éditer le processus" hide-footer size="lg">
      <b-form @submit.prevent="submitEditProcessus">
        <b-row class="g-2">
          <b-col cols="12">
            <label class="form-label mb-1">Nom</label>
            <b-form-input class="form-control-sm" v-model.trim="editProcessusModal.name" required/>
          </b-col>

          <b-col v-if="editProcessusModal.macroIsRealisation" cols="12">
            <b-form-checkbox v-model="editProcessusModal.has_objectives" switch>
              <span class="fw-semibold">Ce processus est un Programme (Objectifs)</span>
            </b-form-checkbox>
            <small class="text-muted d-block mt-1">
              ⚠️ Désactiver ne supprime pas les objectifs existants, mais les données/actitivés
              devront être créées directement sur le processus (mode simple).
            </small>
          </b-col>

          <template v-if="!editProcessusModal.has_objectives">
            <b-col cols="12">
              <label class="form-label mb-1 fw-semibold">Données d'entrée <b-badge bg="info">{{ editProcessusModal.inputs.length }}</b-badge></label>
              <div v-for="(inp, i) in editProcessusModal.inputs" :key="'ein'+i" class="d-flex gap-1 mb-1">
                <b-form-input class="form-control-sm" v-model="editProcessusModal.inputs[i]" placeholder="Donnée"/>
                <b-button size="sm" variant="danger" @click="editProcessusModal.inputs.splice(i,1)">✕</b-button>
              </div>
              <b-button size="sm" variant="outline-primary" @click="editProcessusModal.inputs.push('')">+ Ajouter</b-button>
            </b-col>

            <b-col cols="12">
              <label class="form-label mb-1 fw-semibold">Données de sortie <b-badge bg="success">{{ editProcessusModal.outputs.length }}</b-badge></label>
              <div v-for="(out, i) in editProcessusModal.outputs" :key="'eou'+i" class="d-flex gap-1 mb-1">
                <b-form-input class="form-control-sm" v-model="editProcessusModal.outputs[i]" placeholder="Donnée"/>
                <b-button size="sm" variant="danger" @click="editProcessusModal.outputs.splice(i,1)">✕</b-button>
              </div>
              <b-button size="sm" variant="outline-primary" @click="editProcessusModal.outputs.push('')">+ Ajouter</b-button>
            </b-col>

            <b-col cols="12">
              <label class="form-label mb-1 fw-semibold">Ressources <b-badge bg="warning">{{ editProcessusModal.resources.length }}</b-badge></label>
              <div v-for="(res, i) in editProcessusModal.resources" :key="'eres'+i" class="d-flex gap-1 mb-1">
                <b-form-input class="form-control-sm" v-model="editProcessusModal.resources[i]" placeholder="Ressource"/>
                <b-button size="sm" variant="danger" @click="editProcessusModal.resources.splice(i,1)">✕</b-button>
              </div>
              <b-button size="sm" variant="outline-primary" @click="editProcessusModal.resources.push('')">+ Ajouter</b-button>
            </b-col>
          </template>
          <b-col v-else cols="12">
            <b-alert variant="info" show class="py-2 px-3 mb-0 small">
              Les données d'entrée/sortie/ressources se gèrent désormais par objectif (onglet Activités).
            </b-alert>
          </b-col>
        </b-row>

        <div class="text-end mt-3 border-top pt-2">
          <b-button variant="light" class="me-2" @click="editProcessusModal.show=false">Annuler</b-button>
          <b-button variant="primary" type="submit" :disabled="savingEditProcessus">Enregistrer</b-button>
        </div>
      </b-form>
    </b-modal>

    <b-modal v-model="editActiviteModal.show" title="Éditer l'activité" hide-footer size="md">
      <b-form @submit.prevent="submitEditActivite">
        <b-row class="g-2">
          <b-col cols="12">
            <label class="form-label mb-1">Nom</label>
            <b-form-input class="form-control-sm" v-model.trim="editActiviteModal.name" required/>
          </b-col>
          <b-col cols="12">
            <label class="form-label mb-1">Description</label>
            <b-form-textarea rows="2" class="form-control-sm" v-model.trim="editActiviteModal.description"/>
          </b-col>
        </b-row>
        <div class="text-end mt-3 border-top pt-2">
          <b-button variant="light" class="me-2" @click="editActiviteModal.show=false">Annuler</b-button>
          <b-button variant="primary" type="submit" :disabled="savingEditActivite">Enregistrer</b-button>
        </div>
      </b-form>
    </b-modal>

    <b-modal v-model="editObjectifModal.show" title="Éditer l'objectif" hide-footer size="lg">
      <b-form @submit.prevent="submitEditObjectif">
        <b-row class="g-2">
          <b-col cols="12">
            <label class="form-label mb-1">Nom</label>
            <b-form-input class="form-control-sm" v-model.trim="editObjectifModal.name" required/>
          </b-col>
          <b-col cols="12">
            <label class="form-label mb-1">Description</label>
            <b-form-textarea rows="2" class="form-control-sm" v-model.trim="editObjectifModal.description"/>
          </b-col>

          <b-col cols="12">
            <label class="form-label mb-1 fw-semibold">Données d'entrée <b-badge bg="info">{{ editObjectifModal.inputs.length }}</b-badge></label>
            <div v-for="(inp, i) in editObjectifModal.inputs" :key="'eoin'+i" class="d-flex gap-1 mb-1">
              <b-form-input class="form-control-sm" v-model="editObjectifModal.inputs[i]" placeholder="Donnée"/>
              <b-button size="sm" variant="danger" @click="editObjectifModal.inputs.splice(i,1)">✕</b-button>
            </div>
            <b-button size="sm" variant="outline-primary" @click="editObjectifModal.inputs.push('')">+ Ajouter</b-button>
          </b-col>

          <b-col cols="12">
            <label class="form-label mb-1 fw-semibold">Données de sortie <b-badge bg="success">{{ editObjectifModal.outputs.length }}</b-badge></label>
            <div v-for="(out, i) in editObjectifModal.outputs" :key="'eoout'+i" class="d-flex gap-1 mb-1">
              <b-form-input class="form-control-sm" v-model="editObjectifModal.outputs[i]" placeholder="Donnée"/>
              <b-button size="sm" variant="danger" @click="editObjectifModal.outputs.splice(i,1)">✕</b-button>
            </div>
            <b-button size="sm" variant="outline-primary" @click="editObjectifModal.outputs.push('')">+ Ajouter</b-button>
          </b-col>

          <b-col cols="12">
            <label class="form-label mb-1 fw-semibold">Ressources <b-badge bg="warning">{{ editObjectifModal.resources.length }}</b-badge></label>
            <div v-for="(res, i) in editObjectifModal.resources" :key="'eores'+i" class="d-flex gap-1 mb-1">
              <b-form-input class="form-control-sm" v-model="editObjectifModal.resources[i]" placeholder="Ressource"/>
              <b-button size="sm" variant="danger" @click="editObjectifModal.resources.splice(i,1)">✕</b-button>
            </div>
            <b-button size="sm" variant="outline-primary" @click="editObjectifModal.resources.push('')">+ Ajouter</b-button>
          </b-col>
        </b-row>

        <div class="text-end mt-3 border-top pt-2">
          <b-button variant="light" class="me-2" @click="editObjectifModal.show=false">Annuler</b-button>
          <b-button variant="primary" type="submit" :disabled="savingEditObjectif">Enregistrer</b-button>
        </div>
      </b-form>
    </b-modal>

  </VerticalLayout>
</template>

<script setup>
import { Head, useForm, router } from "@inertiajs/vue3"
import { ref, computed } from "vue"
import VerticalLayout from "@/layoutsparam/VerticalLayout.vue"
import DataTable from "primevue/datatable"
import Column from "primevue/column"
import Tree from "primevue/tree"
import Tag from "primevue/tag"
import axios from "axios"

const props = defineProps({
  macros: Array,
  processes: Array,
  activities: Array,
  objectifs: Array
})

/* ================== TABS ================== */
const activeTab = ref('macro')
const tabVariant = t => activeTab.value===t?'primary':'outline-primary'

/* ================== FORMS ================== */
const processForm = useForm({
  macro_process_id: null,
  name: '',
  has_objectives: false,
  inputs: [''],
  outputs: [''],
  resources: ['']
})

const activityForm = useForm({
  macro_process_id: null,
  process_id: null,
  objectif_id: null,
  name: '',
  description: ''
})

const objectifForm = useForm({
  process_id: null,
  name: '',
  description: '',
  inputs: [''],
  outputs: [''],
  resources: ['']
})

/* ================== 🤖 IA STATE ================== */
const isLoadingProcessus = ref(false)
const isLoadingActivites = ref(false)
const isLoadingObjectifs = ref(false)
const isLoadingObjectifsPreview = ref(false)
const isLoadingObjectifData = ref(false)
const isLoadingActivitesObjectif = ref(false)

const processusSuggestions = ref([])
const activitesSuggestions = ref([])
const objectifsSuggestions = ref([])
const objectifsSuggestionsPreview = ref([])   // aperçu généré dès la checkbox cochée (onglet Processus)
const activitesObjectifSuggestions = ref([])
const dataProcessusSuggestions = ref({ inputs: [], outputs: [], resources: [] })
const dataObjectifSuggestions = ref({ inputs: [], outputs: [], resources: [] })

/* ================== OBJECTIFS UI STATE ================== */
const expandedObjectifs = ref({})
const toggleObjectifExpand = id => { expandedObjectifs.value[id] = !expandedObjectifs.value[id] }

/* ================== EDIT MODALS ================== */
const edit = ref({show:false, id:null, name:''})
const savingEdit = ref(false)

const editProcessusModal = ref({show:false, id:null, name:'', has_objectives:false, macroIsRealisation:false, inputs:[], outputs:[], resources:[]})
const savingEditProcessus = ref(false)

const editActiviteModal = ref({show:false, id:null, name:'', description:''})
const savingEditActivite = ref(false)

const editObjectifModal = ref({show:false, id:null, name:'', description:'', inputs:[], outputs:[], resources:[]})
const savingEditObjectif = ref(false)

/* ================== MACROS VALIDATION ================== */
const requiredKinds = ['Direction','Réalisation','Support']
const macrosFilteredSorted = computed(() => {
  const order = {Direction:1, Réalisation:2, Support:3}
  return [...props.macros].sort((a,b)=> order[a.kind]-order[b.kind])
})
const kindsPresent = computed(() => props.macros.map(m=>m.kind))
const missingKinds = computed(() => requiredKinds.filter(k=>!kindsPresent.value.includes(k)))
const macrosLocked = computed(() => missingKinds.value.length===0)

/* ================== COMPUTED INDEXES ================== */
const macrosById = computed(() => {
  const map = {}
  props.macros.forEach(m => { map[String(m.id)] = m })
  return map
})
const processesById = computed(() => {
  const map = {}
  props.processes.forEach(p => { map[String(p.id)] = p })
  return map
})

/* ================== STATISTIQUES PAR MACRO (form processus) ================== */
const selectedMacro = computed(() => macrosById.value[String(processForm.macro_process_id)])
const selectedMacroIsRealisation = computed(() => selectedMacro.value?.kind === 'Réalisation')

const processesByMacro = computed(() => {
  const mID = processForm.macro_process_id
  if (!mID) return []
  return props.processes.filter(p => String(p.macro_process_id) === String(mID))
})

/* ================== ACTIVITÉ TAB : processus sélectionné + objectifs ================== */
const selectedProcessForActivity = computed(() => {
  const pID = activityForm.process_id
  if (!pID) return null
  return processesById.value[String(pID)] || null
})

const objectifsByProcess = computed(() => {
  const pID = activityForm.process_id
  if (!pID) return []
  return props.objectifs.filter(o => String(o.process_id) === String(pID))
})

const activitiesByProcess = computed(() => {
  const pID = activityForm.process_id
  if (!pID) return []
  return props.activities.filter(a => String(a.process_id) === String(pID) && !a.objectif_id)
})

const activitiesByProcessAllObjectifs = computed(() => {
  const objIds = objectifsByProcess.value.map(o => o.id)
  return props.activities.filter(a => objIds.includes(a.objectif_id))
})

const getActivitesByObjectif = objectifId => props.activities.filter(a => String(a.objectif_id) === String(objectifId))

/* ================== HELPERS ================== */
const getProcessCountByMacro = id => props.processes.filter(p => p.macro_process_id === id).length
const getDataCountByMacro = id => {
  const procs = props.processes.filter(p => p.macro_process_id === id)
  return procs.reduce((sum, p) => sum + (p.inputs?.length || 0) + (p.outputs?.length || 0) + (p.resources?.length || 0), 0)
}
const getProcessInputCount = id => processesById.value[String(id)]?.inputs?.length || 0
const getProcessOutputCount = id => processesById.value[String(id)]?.outputs?.length || 0
const getProcessResourceCount = id => processesById.value[String(id)]?.resources?.length || 0

const isProcessComplete = proc => {
  if (proc.has_objectives) {
    const objs = props.objectifs.filter(o => o.process_id === proc.id)
    return objs.length > 0 && objs.every(o => (o.inputs?.length||0) > 0 && (o.outputs?.length||0) > 0 && (o.resources?.length||0) > 0)
  }
  return (proc.inputs?.length||0) > 0 && (proc.outputs?.length||0) > 0 && (proc.resources?.length||0) > 0
}

const kindSeverity = k => ({ Direction: 'info', Réalisation: 'success', Support: 'warning' }[k] || 'secondary')

/* ================== SELECT OPTIONS ================== */
const macroOptions = computed(() =>
  [{value:null, text:"— Sélectionner —", disabled:true},
   ...props.macros.map(m=>({value:String(m.id), text:`${m.code} — ${m.name}`}))]
)
const macroOptionsActivity = macroOptions

const processOptionsActivity = computed(() => {
  const mID = activityForm.macro_process_id
  if (!mID) return [{value:null, text:"— Choisir un macro —", disabled:true}]
  const list = props.processes.filter(p => String(p.macro_process_id) === String(mID))
  return [
    {value:null, text:list.length?"— Sélectionner —":"— Aucun processus —", disabled:true},
    ...list.map(p=>({value:String(p.id), text:`${p.code} — ${p.name}${p.has_objectives ? ' 🎯' : ''}`}))
  ]
})

/* ================== FILTERED LISTS ================== */
const processesFiltered = computed(() => {
  const mID = processForm.macro_process_id
  if (!mID) return []
  return props.processes.filter(p => String(p.macro_process_id) === String(mID))
})

const activitiesFiltered = computed(() => {
  const pID = activityForm.process_id
  if (!pID) return []
  return props.activities.filter(a => String(a.process_id) === String(pID) && !a.objectif_id)
})

/* ================== AUTO CODES ================== */
const nextProcessCode = computed(() => {
  const mID = processForm.macro_process_id
  const macro = props.macros.find(m=>String(m.id)===String(mID))
  if (!macro) return ''
  const letter = (macro.code || macro.kind[0] || 'X').slice(-1).toUpperCase()
  const count = props.processes.filter(p=>String(p.macro_process_id)===String(mID)).length + 1
  return 'P' + String(count).padStart(2,'0') + letter
})

const nextActivityCode = computed(() => {
  const pid = activityForm.process_id
  const proc = props.processes.find(p=>String(p.id)===String(pid))
  if (!proc) return ''
  const m = proc.code.match(/^P(\d{2})([A-Z])$/)
  const seq = m ? m[1] : '01'
  const letter = m ? m[2] : 'X'
  const count = props.activities.filter(a=>String(a.process_id)===String(pid) && !a.objectif_id).length + 1
  return `A${String(count).padStart(2,'0')}P${seq}${letter}`
})

/* ================== TREE NODES ================== */
const expandedKeys = ref({})

const makeMacro = m => ({ key: `M-${m.id}`, label: m.name, data: {type:'macro', code:m.code}, children: [] })
const makeProc = p => ({
  key: `P-${p.id}`,
  label: p.name + (p.has_objectives ? ' 🎯' : ''),
  data: {type:'process', code:p.code, dataCount: (p.inputs?.length || 0) + (p.outputs?.length || 0) + (p.resources?.length || 0)},
  children: []
})
const makeObjectif = o => ({
  key: `O-${o.id}`,
  label: o.name,
  data: {type:'objectif', code:'OBJ', dataCount: (o.inputs?.length||0)+(o.outputs?.length||0)+(o.resources?.length||0)},
  children: []
})
const makeAct = a => ({ key: `A-${a.id}`, label: a.name, data: {type:'activity', code:a.code} })

const treeNodes = computed(() =>
  props.macros.map(m => {
    const root = makeMacro(m)
    const procs = props.processes.filter(p => p.macro_process_id === m.id)
    root.children = procs.map(p => {
      const node = makeProc(p)
      if (p.has_objectives) {
        const objs = props.objectifs.filter(o => o.process_id === p.id)
        node.children = objs.map(o => {
          const objNode = makeObjectif(o)
          const acts = props.activities.filter(a => a.objectif_id === o.id)
          objNode.children = acts.map(a => makeAct(a))
          return objNode
        })
      } else {
        const acts = props.activities.filter(a => a.process_id === p.id && !a.objectif_id)
        node.children = acts.map(a => makeAct(a))
      }
      return node
    })
    return root
  })
)

const badgeLetter = n => n.data.type==='macro'?'M':n.data.type==='process'?'P':n.data.type==='objectif'?'O':'A'
const nodeIcon = n => n.data.type==='activity'?'pi pi-file':n.data.type==='objectif'?'pi pi-bullseye':'pi pi-folder'
const typeColorClass = n =>
  n.data.type==='macro'?'color-type-macro':
  n.data.type==='process'?'color-type-process':
  n.data.type==='objectif'?'color-type-objectif':'color-type-activity'

/* ================== 🤖 IA ACTIONS ================== */

const onMacroSelected = async () => {
  const macro = macrosById.value[String(processForm.macro_process_id)]
  if (!macro) return

  if (macro.kind !== 'Réalisation') {
    processForm.has_objectives = false
  }

  processusSuggestions.value = []
  dataProcessusSuggestions.value = { inputs: [], outputs: [], resources: [] }
  objectifsSuggestionsPreview.value = []
  isLoadingProcessus.value = true

  try {
    const res = await axios.post(route('param.projects.mpa.ai.suggest-processus'), {
      macro_kind: macro.kind,
      macro_name: macro.name
    })
    if (res.data.success && res.data.processus) {
      processusSuggestions.value = res.data.processus
    }
  } catch (err) {
    console.error('❌ Erreur génération processus:', err)
  } finally {
    isLoadingProcessus.value = false
  }
}

const selectProcessusSuggestion = async (sugg) => {
  processForm.name = sugg.name
  const macro = macrosById.value[String(processForm.macro_process_id)]
  if (!macro) return

  if (processForm.has_objectives) {
    await fetchObjectifsPreview(sugg.name, macro.kind)
  } else {
    dataProcessusSuggestions.value = { inputs: [], outputs: [], resources: [] }
    isLoadingProcessus.value = true
    try {
      const res = await axios.post(route('param.projects.mpa.ai.suggest-data'), {
        processus_name: sugg.name,
        macro_kind: macro.kind
      })
      if (res.data.success) {
        dataProcessusSuggestions.value = {
          inputs: res.data.inputs || [],
          outputs: res.data.outputs || [],
          resources: res.data.resources || []
        }
      }
    } catch (err) {
      console.error('❌ Erreur génération données:', err)
    } finally {
      isLoadingProcessus.value = false
    }
  }
}

/**
 * ✅ Déclenché dès que la checkbox "Programme" est cochée : suggère immédiatement
 * des objectifs (aperçu), en se basant sur le nom déjà saisi si disponible.
 */
const onHasObjectivesToggle = async (checked) => {
  if (!checked) {
    objectifsSuggestionsPreview.value = []
    return
  }
  const macro = macrosById.value[String(processForm.macro_process_id)]
  if (!macro || !processForm.name || processForm.name.trim().length < 3) return
  await fetchObjectifsPreview(processForm.name, macro.kind)
}

const fetchObjectifsPreview = async (processusName, macroKind) => {
  isLoadingObjectifsPreview.value = true
  objectifsSuggestionsPreview.value = []
  try {
    const res = await axios.post(route('param.projects.mpa.ai.suggest-objectifs'), {
      processus_name: processusName,
      macro_kind: macroKind
    })
    if (res.data.success && res.data.objectifs) {
      objectifsSuggestionsPreview.value = res.data.objectifs
    }
  } catch (err) {
    console.error('❌ Erreur génération objectifs (aperçu):', err)
  } finally {
    isLoadingObjectifsPreview.value = false
  }
}

const onActivityMacroChanged = () => {
  activityForm.process_id = null
  activityForm.objectif_id = null
  activitesSuggestions.value = []
  objectifsSuggestions.value = []
  activitesObjectifSuggestions.value = []
}

const onProcessActivitySelected = async () => {
  const proc = processesById.value[String(activityForm.process_id)]
  activityForm.objectif_id = null
  activitesSuggestions.value = []
  objectifsSuggestions.value = []
  activitesObjectifSuggestions.value = []
  resetObjectifForm()

  if (!proc) return

  if (proc.has_objectives) {
    objectifForm.process_id = proc.id

    // Reprend l'aperçu généré à l'étape processus s'il correspond, sinon regénère.
    if (objectifsSuggestionsPreview.value.length > 0) {
      objectifsSuggestions.value = objectifsSuggestionsPreview.value
      return
    }

    isLoadingObjectifs.value = true
    try {
      const macro = macrosById.value[String(proc.macro_process_id)]
      const res = await axios.post(route('param.projects.mpa.ai.suggest-objectifs'), {
        processus_name: proc.name,
        macro_kind: macro?.kind || ''
      })
      if (res.data.success && res.data.objectifs) {
        objectifsSuggestions.value = res.data.objectifs
      }
    } catch (err) {
      console.error('❌ Erreur génération objectifs:', err)
    } finally {
      isLoadingObjectifs.value = false
    }
  } else {
    isLoadingActivites.value = true
    try {
      const macro = macrosById.value[String(proc.macro_process_id)]
      const res = await axios.post(route('param.projects.mpa.ai.suggest-activites'), {
        processus_name: proc.name,
        macro_kind: macro?.kind || ''
      })
      if (res.data.success && res.data.activites) {
        activitesSuggestions.value = res.data.activites
      }
    } catch (err) {
      console.error('❌ Erreur génération activités:', err)
    } finally {
      isLoadingActivites.value = false
    }
  }
}

const selectActivitesSuggestion = (sugg) => {
  activityForm.name = sugg.name
  activityForm.description = sugg.description || ''
}

const selectObjectifSuggestion = (sugg) => {
  objectifForm.name = sugg.name
  objectifForm.description = sugg.description || ''
  onObjectifNameEntered()
}

/**
 * Déclenché quand le nom de l'objectif est saisi/confirmé : suggère les
 * données d'entrée/sortie/ressources SPÉCIFIQUES à cet objectif.
 */
const onObjectifNameEntered = async () => {
  if (!objectifForm.name || objectifForm.name.trim().length < 3) return
  const proc = processesById.value[String(activityForm.process_id)]
  if (!proc) return

  dataObjectifSuggestions.value = { inputs: [], outputs: [], resources: [] }
  isLoadingObjectifData.value = true
  try {
    const macro = macrosById.value[String(proc.macro_process_id)]
    const res = await axios.post(route('param.projects.mpa.ai.suggest-objectif-data'), {
      objectif_name: objectifForm.name,
      processus_name: proc.name,
      macro_kind: macro?.kind || ''
    })
    if (res.data.success) {
      dataObjectifSuggestions.value = {
        inputs: res.data.inputs || [],
        outputs: res.data.outputs || [],
        resources: res.data.resources || []
      }
    }
  } catch (err) {
    console.error('❌ Erreur génération données objectif:', err)
  } finally {
    isLoadingObjectifData.value = false
  }
}

const selectObjectifForActivity = async (obj) => {
  activityForm.objectif_id = obj.id
  activityForm.name = ''
  activityForm.description = ''
  activitesObjectifSuggestions.value = []
  expandedObjectifs.value[obj.id] = true

  const proc = processesById.value[String(obj.process_id)]
  if (!proc) return

  isLoadingActivitesObjectif.value = true
  try {
    const macro = macrosById.value[String(proc.macro_process_id)]
    const res = await axios.post(route('param.projects.mpa.ai.suggest-activites-objectif'), {
      objectif_name: obj.name,
      processus_name: proc.name,
      macro_kind: macro?.kind || ''
    })
    if (res.data.success && res.data.activites) {
      activitesObjectifSuggestions.value = res.data.activites
    }
  } catch (err) {
    console.error('❌ Erreur génération activités (objectif):', err)
  } finally {
    isLoadingActivitesObjectif.value = false
  }
}

const selectActivitesObjectifSuggestion = (sugg) => {
  activityForm.name = sugg.name
  activityForm.description = sugg.description || ''
}

/* ================== FORM ACTIONS ================== */
const removeList = (list, i) => list.splice(i, 1)

const resetProcess = () => {
  processForm.name = ''
  processForm.has_objectives = false
  processForm.inputs = ['']
  processForm.outputs = ['']
  processForm.resources = ['']
  processusSuggestions.value = []
  dataProcessusSuggestions.value = { inputs: [], outputs: [], resources: [] }
  objectifsSuggestionsPreview.value = []
}

const resetActivity = () => {
  activityForm.name = ''
  activityForm.description = ''
  activityForm.objectif_id = null
  activitesSuggestions.value = []
  activitesObjectifSuggestions.value = []
}

const resetObjectifForm = () => {
  objectifForm.name = ''
  objectifForm.description = ''
  objectifForm.inputs = ['']
  objectifForm.outputs = ['']
  objectifForm.resources = ['']
  dataObjectifSuggestions.value = { inputs: [], outputs: [], resources: [] }
}

const validateDefaults = () => {
  router.post(route('param.projects.macro.validate'), {}, {preserveScroll:true})
}

const submitProcess = () => {
  const hasObj = selectedMacroIsRealisation.value ? processForm.has_objectives : false

  router.post(
    route('param.projects.projects.processus.store'),
    {
      macro_process_id: Number(processForm.macro_process_id),
      name: processForm.name,
      has_objectives: hasObj,
      inputs: hasObj ? [] : processForm.inputs.filter(i => i.trim() !== ''),
      outputs: hasObj ? [] : processForm.outputs.filter(o => o.trim() !== ''),
      resources: hasObj ? [] : processForm.resources.filter(r => r.trim() !== '')
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        resetProcess()
        processForm.macro_process_id = null
        router.reload({only:['processes']})
      }
    }
  )
}

const submitActivity = () => {
  router.post(
    route('param.projects.projects.activites.store'),
    {
      process_id: activityForm.objectif_id ? null : activityForm.process_id,
      objectif_id: activityForm.objectif_id,
      name: activityForm.name,
      description: activityForm.description
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        const wasObjectifMode = !!activityForm.objectif_id
        resetActivity()
        if (!wasObjectifMode) activityForm.process_id = null
        router.reload({only:['activities']})
      }
    }
  )
}

const submitObjectif = () => {
  router.post(
    route('param.projects.mpa.ai.objectifs.store'),
    {
      process_id: activityForm.process_id,
      name: objectifForm.name,
      description: objectifForm.description,
      inputs: objectifForm.inputs.filter(i => i.trim() !== ''),
      outputs: objectifForm.outputs.filter(o => o.trim() !== ''),
      resources: objectifForm.resources.filter(r => r.trim() !== '')
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        resetObjectifForm()
        router.reload({only:['objectifs']})
      }
    }
  )
}

/* ================== EDIT MACRO ================== */
const openEditModal = m => { edit.value = {show:true, id:m.id, name:m.name} }

const submitEdit = () => {
  savingEdit.value = true
  router.put(route('param.projects.mpa.update', edit.value.id), {name: edit.value.name}, {
    preserveScroll: true,
    onFinish: () => {
      savingEdit.value = false
      edit.value.show = false
      router.reload({only:['macros']})
    }
  })
}

/* ================== EDIT PROCESSUS ================== */
const editProcessus = (proc) => {
  const macro = macrosById.value[String(proc.macro_process_id)]
  editProcessusModal.value = {
    show: true,
    id: proc.id,
    name: proc.name,
    has_objectives: !!proc.has_objectives,
    macroIsRealisation: macro?.kind === 'Réalisation',
    inputs: proc.inputs?.map(i => i.label || i) || [],
    outputs: proc.outputs?.map(o => o.label || o) || [],
    resources: proc.resources?.map(r => r.label || r) || []
  }
}

const submitEditProcessus = () => {
  savingEditProcessus.value = true
  router.put(
    route('param.projects.projects.processus.update', editProcessusModal.value.id),
    {
      name: editProcessusModal.value.name,
      has_objectives: editProcessusModal.value.macroIsRealisation ? editProcessusModal.value.has_objectives : false,
      inputs: editProcessusModal.value.has_objectives ? [] : editProcessusModal.value.inputs.filter(i => i.trim()),
      outputs: editProcessusModal.value.has_objectives ? [] : editProcessusModal.value.outputs.filter(o => o.trim()),
      resources: editProcessusModal.value.has_objectives ? [] : editProcessusModal.value.resources.filter(r => r.trim())
    },
    {
      preserveScroll: true,
      onFinish: () => {
        savingEditProcessus.value = false
        editProcessusModal.value.show = false
        router.reload({only:['processes']})
      }
    }
  )
}

/* ================== EDIT ACTIVITE ================== */
const editActivite = (act) => {
  editActiviteModal.value = { show: true, id: act.id, name: act.name, description: act.description || '' }
}

const submitEditActivite = () => {
  savingEditActivite.value = true
  router.put(route('param.projects.projects.activites.update', editActiviteModal.value.id), {
    name: editActiviteModal.value.name,
    description: editActiviteModal.value.description
  }, {
    preserveScroll: true,
    onFinish: () => {
      savingEditActivite.value = false
      editActiviteModal.value.show = false
      router.reload({only:['activities']})
    }
  })
}

/* ================== EDIT OBJECTIF ================== */
const editObjectif = (obj) => {
  editObjectifModal.value = {
    show: true,
    id: obj.id,
    name: obj.name,
    description: obj.description || '',
    inputs: obj.inputs?.map(i => i.label || i) || [],
    outputs: obj.outputs?.map(o => o.label || o) || [],
    resources: obj.resources?.map(r => r.label || r) || []
  }
}

const submitEditObjectif = () => {
  savingEditObjectif.value = true
  router.put(
    route('param.projects.mpa.ai.objectifs.update', editObjectifModal.value.id),
    {
      name: editObjectifModal.value.name,
      description: editObjectifModal.value.description,
      inputs: editObjectifModal.value.inputs.filter(i => i.trim()),
      outputs: editObjectifModal.value.outputs.filter(o => o.trim()),
      resources: editObjectifModal.value.resources.filter(r => r.trim())
    },
    {
      preserveScroll: true,
      onFinish: () => {
        savingEditObjectif.value = false
        editObjectifModal.value.show = false
        router.reload({only:['objectifs']})
      }
    }
  )
}
</script>

<style scoped>
.form-control-sm, .form-select-sm { font-size:.75rem; height:26px; padding:.15rem .45rem }
.btn-sm { padding:.15rem .45rem; font-size:.72rem }

.stat-card { border-left: 4px solid transparent; transition: all 0.2s }
.stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); transform: translateY(-2px) }
.stat-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px }
.stat-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600 }
.badge-success { background: #d4edda; color: #155724 }
.badge-warning { background: #fff3cd; color: #856404 }
.badge-info { background: #d1ecf1; color: #0c5460 }
.badge-secondary { background: #e2e3e5; color: #383d41 }

.quick-stats { display: flex; flex-wrap: wrap; gap: 8px }
.stat-chip { display: inline-flex; align-items: center; gap: 4px; background: #e9ecef; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600 }

.pv-table :deep(.p-datatable-thead>tr>th) { background: #f8fafc; border: 1px solid #e5e7eb; padding: .25rem .35rem; font-size: .74rem }
.pv-table :deep(.p-datatable-tbody>tr>td) { border: 1px solid #eef2f7; padding: .25rem .35rem; font-size: .72rem }

.ai-suggestions-box { background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border: 2px solid #1976d2; border-radius: 0.4rem; padding: 0.75rem; margin-bottom: 0.75rem }
.ai-suggestions-data { background: #f5f5f5; border: 1px solid #ddd; border-radius: 0.3rem; padding: 0.75rem; margin-bottom: 0.75rem }
.ai-header { font-weight: 600; color: #0d47a1; margin-bottom: 0.75rem; font-size: 0.85rem }
.ai-header-small { font-weight: 600; color: #333; font-size: 0.8rem; margin-bottom: 0.5rem }

.suggestion-chips { display: flex; flex-wrap: wrap; gap: 0.5rem }
.suggestion-chip { padding: 0.4rem 0.8rem !important; cursor: pointer; user-select: none; transition: all 0.2s; font-size: 0.85rem; border-radius: 20px; display: inline-flex; align-items: center }
.suggestion-chip:hover { opacity: 0.8; transform: scale(1.05); box-shadow: 0 2px 8px rgba(0,0,0,0.15) }

.suggestion-chips-small { display: flex; flex-wrap: wrap; gap: 0.3rem; margin-bottom: 0.5rem }
.suggestion-chip-sm { padding: 0.25rem 0.5rem !important; cursor: pointer; font-size: 0.75rem; border-radius: 12px }
.suggestion-chip-sm:hover { background-color: #ccc !important; color: #000 !important }

.icon-badge { position: relative; display: flex; width: 22px; height: 18px; border-radius: .4rem; align-items: center; justify-content: center }
.badge-letter { position: absolute; font-size: .65rem; font-weight: 800; color: #000 }
.color-type-macro { --clr-type: #0f766e }
.color-type-process { --clr-type: #7c3aed }
.color-type-objectif { --clr-type: #d97706 }
.color-type-activity { --clr-type: #ef4444 }
.node-icon { color: var(--clr-type) }

.code-chip { background: #eef2f7; border: 1px solid #e2e8f0; padding: .05rem .35rem; border-radius: .35rem; font-size: .72rem }

.objectives-toggle { background: #fff8e1; border: 1px solid #ffe082; border-radius: .4rem; padding: .5rem .75rem }

.objectif-card { border: 1px solid #ffe082; border-radius: .4rem; overflow: hidden }
.objectif-card-header { display: flex; justify-content: space-between; align-items: center; padding: .5rem .75rem; background: #fff8e1; cursor: pointer }
.objectif-card-header:hover { background: #fff3cd }
.objectif-card-body { padding: .5rem .75rem; background: #fffdf7 }
</style>