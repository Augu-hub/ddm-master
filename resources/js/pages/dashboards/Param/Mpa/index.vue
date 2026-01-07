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
              <div class="stat-icon bg-primary">
                <i class="ti ti-cube"></i>
              </div>
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
              <div class="stat-icon bg-info">
                <i class="ti ti-settings"></i>
              </div>
              <div class="flex-grow-1">
                <small class="text-muted">Processus</small>
                <h5 class="mb-0 fw-bold">{{ processes.length }}</h5>
              </div>
              <div class="stat-badge" :class="processes.length > 0 ? 'badge-info' : 'badge-secondary'">
                📊
              </div>
            </div>
          </b-card-body>
        </b-card>
      </b-col>

      <b-col lg="3">
        <b-card no-body class="shadow-sm stat-card">
          <b-card-body class="p-2">
            <div class="d-flex align-items-center gap-2">
              <div class="stat-icon bg-success">
                <i class="ti ti-list-details"></i>
              </div>
              <div class="flex-grow-1">
                <small class="text-muted">Activités</small>
                <h5 class="mb-0 fw-bold">{{ activities.length }}</h5>
              </div>
              <div class="stat-badge" :class="activities.length > 0 ? 'badge-success' : 'badge-secondary'">
                📋
              </div>
            </div>
          </b-card-body>
        </b-card>
      </b-col>

      <b-col lg="3">
        <b-card no-body class="shadow-sm stat-card">
          <b-card-body class="p-2">
            <div class="d-flex align-items-center gap-2">
              <div class="stat-icon bg-warning">
                <i class="ti ti-database"></i>
              </div>
              <div class="flex-grow-1">
                <small class="text-muted">Données</small>
                <h5 class="mb-0 fw-bold">{{ totalDataCount }}</h5>
              </div>
              <div class="stat-badge" :class="totalDataCount > 0 ? 'badge-warning' : 'badge-secondary'">
                🔧
              </div>
            </div>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>

    <!-- STATISTIQUES MACRO SÉLECTIONNÉ -->
    <b-card v-if="selectedMacro" no-body class="mb-2 shadow-sm border-primary">
      <b-card-header class="py-2 px-3 bg-light-primary">
        <h6 class="mb-0">
          <i class="ti ti-cube text-primary me-2"></i>
          Statistiques: {{ selectedMacro.name }} ({{ selectedMacro.kind }})
        </h6>
      </b-card-header>

      <b-card-body class="p-3">
        <b-row class="g-3">
          <!-- Processus du macro -->
          <b-col lg="3">
            <div class="stat-detail">
              <div class="stat-label">Processus</div>
              <div class="stat-value">{{ processesByMacro.length }}</div>
              <div class="stat-progress">
                <div class="progress-bar" :style="{width: (processesByMacro.length * 25) + '%'}"></div>
              </div>
            </div>
          </b-col>

          <!-- Activités du macro -->
          <b-col lg="3">
            <div class="stat-detail">
              <div class="stat-label">Activités</div>
              <div class="stat-value">{{ activitiesByMacro.length }}</div>
              <div class="stat-progress">
                <div class="progress-bar bg-success" :style="{width: Math.min(activitiesByMacro.length * 10, 100) + '%'}"></div>
              </div>
            </div>
          </b-col>

          <!-- Données d'entrée -->
          <b-col lg="3">
            <div class="stat-detail">
              <div class="stat-label">Données d'entrée</div>
              <div class="stat-value">{{ totalInputsByMacro }}</div>
              <div class="stat-progress">
                <div class="progress-bar bg-info" :style="{width: Math.min(totalInputsByMacro * 5, 100) + '%'}"></div>
              </div>
            </div>
          </b-col>

          <!-- Données de sortie -->
          <b-col lg="3">
            <div class="stat-detail">
              <div class="stat-label">Données de sortie</div>
              <div class="stat-value">{{ totalOutputsByMacro }}</div>
              <div class="stat-progress">
                <div class="progress-bar bg-warning" :style="{width: Math.min(totalOutputsByMacro * 5, 100) + '%'}"></div>
              </div>
            </div>
          </b-col>

          <!-- Ressources -->
          <b-col lg="3">
            <div class="stat-detail">
              <div class="stat-label">Ressources</div>
              <div class="stat-value">{{ totalResourcesByMacro }}</div>
              <div class="stat-progress">
                <div class="progress-bar bg-danger" :style="{width: Math.min(totalResourcesByMacro * 5, 100) + '%'}"></div>
              </div>
            </div>
          </b-col>

          <!-- Complétude -->
          <b-col lg="3">
            <div class="stat-detail">
              <div class="stat-label">Complétude</div>
              <div class="stat-value">{{ macroCompleteness }}%</div>
              <div class="stat-progress">
                <div class="progress-bar" :class="macroCompletenessColor" :style="{width: macroCompleteness + '%'}"></div>
              </div>
            </div>
          </b-col>

          <!-- Statut -->
          <b-col lg="9">
            <div class="stat-status">
              <div class="status-label">Statut</div>
              <div class="status-chips">
                <b-badge v-if="processesByMacro.length > 0" bg="info" class="me-2">
                  <i class="ti ti-check"></i> {{ processesByMacro.length }} processus
                </b-badge>
                <b-badge v-if="totalInputsByMacro > 0" bg="primary" class="me-2">
                  <i class="ti ti-arrow-down"></i> {{ totalInputsByMacro }} entrées
                </b-badge>
                <b-badge v-if="totalOutputsByMacro > 0" bg="warning" class="me-2">
                  <i class="ti ti-arrow-up"></i> {{ totalOutputsByMacro }} sorties
                </b-badge>
                <b-badge v-if="totalResourcesByMacro > 0" bg="success">
                  <i class="ti ti-tools"></i> {{ totalResourcesByMacro }} ressources
                </b-badge>
                <b-badge v-if="processesByMacro.length === 0" bg="secondary">
                  Aucun processus
                </b-badge>
              </div>
            </div>
          </b-col>
        </b-row>
      </b-card-body>
    </b-card>

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
                    <small class="text-muted">
                      {{ getProcessCountByMacro(data.id) }} proc • 
                      {{ getDataCountByMacro(data.id) }} données
                    </small>
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

                  <!-- Macro -->
                  <b-col cols="12">
                    <label class="form-label mb-1">Macro *</label>
                    <b-form-select class="form-select-sm" v-model="processForm.macro_process_id" :options="macroOptions" required @change="onMacroSelected"/>
                  </b-col>

                  <!-- Statistiques du macro sélectionné -->
                  <b-col v-if="processForm.macro_process_id" cols="12" class="border-bottom pb-2 mb-2">
                    <div class="quick-stats">
                      <span class="stat-chip">
                        <i class="ti ti-settings"></i> {{ processesByMacro.length }} processus
                      </span>
                      <span class="stat-chip">
                        <i class="ti ti-arrow-down"></i> {{ totalInputsByMacro }} entrées
                      </span>
                      <span class="stat-chip">
                        <i class="ti ti-arrow-up"></i> {{ totalOutputsByMacro }} sorties
                      </span>
                      <span class="stat-chip">
                        <i class="ti ti-tools"></i> {{ totalResourcesByMacro }} ressources
                      </span>
                    </div>
                  </b-col>

                  <!-- 🤖 SUGGESTIONS PROCESSUS -->
                  <b-col v-if="processusSuggestions.length > 0" cols="12">
                    <div class="ai-suggestions-box">
                      <div class="ai-header">
                        ✨ <strong>Suggestions de processus (IA)</strong>
                        <small v-if="isLoadingProcessus" class="text-info ms-2"><i class="ti ti-loading"></i> Génération...</small>
                      </div>
                      <div class="suggestion-chips">
                        <b-badge 
                          v-for="(sugg, idx) in processusSuggestions" 
                          :key="idx"
                          bg="info"
                          class="suggestion-chip"
                          @click="selectProcessusSuggestion(sugg)"
                          role="button"
                          title="Cliquer pour sélectionner"
                        >
                          <i class="ti ti-click me-1"></i>{{ sugg.name }}
                        </b-badge>
                      </div>
                    </div>
                  </b-col>

                  <!-- Code auto -->
                  <b-col cols="12">
                    <label class="form-label mb-1">Code (auto)</label>
                    <b-form-input class="form-control-sm font-monospace" :value="nextProcessCode" disabled/>
                  </b-col>

                  <!-- Nom -->
                  <b-col cols="12">
                    <label class="form-label mb-1">Nom Processus *</label>
                    <b-form-input class="form-control-sm" v-model.trim="processForm.name" required placeholder="Ex: Gestion des commandes"/>
                  </b-col>

                  <!-- 🤖 SUGGESTIONS DONNÉES -->
                  <b-col v-if="dataProcessusSuggestions.inputs && dataProcessusSuggestions.inputs.length > 0" cols="12" class="pt-2 border-top">
                    <div class="ai-suggestions-data">
                      <div class="ai-header-small">📋 Données suggérées (cliquer pour ajouter)</div>
                      
                      <!-- Inputs -->
                      <div class="mb-2">
                        <small class="text-muted fw-semibold">Entrées:</small>
                        <div class="suggestion-chips-small">
                          <b-badge 
                            v-for="(inp, idx) in dataProcessusSuggestions.inputs"
                            :key="'inp-'+idx"
                            bg="light"
                            text="dark"
                            class="suggestion-chip-sm"
                            @click="processForm.inputs.push(inp)"
                            role="button"
                          >
                            + {{ inp }}
                          </b-badge>
                        </div>
                      </div>

                      <!-- Outputs -->
                      <div class="mb-2">
                        <small class="text-muted fw-semibold">Sorties:</small>
                        <div class="suggestion-chips-small">
                          <b-badge 
                            v-for="(out, idx) in dataProcessusSuggestions.outputs"
                            :key="'out-'+idx"
                            bg="light"
                            text="dark"
                            class="suggestion-chip-sm"
                            @click="processForm.outputs.push(out)"
                            role="button"
                          >
                            + {{ out }}
                          </b-badge>
                        </div>
                      </div>

                      <!-- Resources -->
                      <div>
                        <small class="text-muted fw-semibold">Ressources:</small>
                        <div class="suggestion-chips-small">
                          <b-badge 
                            v-for="(res, idx) in dataProcessusSuggestions.resources"
                            :key="'res-'+idx"
                            bg="light"
                            text="dark"
                            class="suggestion-chip-sm"
                            @click="processForm.resources.push(res)"
                            role="button"
                          >
                            + {{ res }}
                          </b-badge>
                        </div>
                      </div>
                    </div>
                  </b-col>

                  <!-- Entrées avec compteur -->
                  <b-col cols="12" class="mt-2">
                    <label class="form-label mb-1 fw-semibold">
                      Données d'entrée
                      <b-badge bg="info" class="ms-2">{{ processForm.inputs.filter(i => i.trim()).length }}</b-badge>
                    </label>
                    <div v-for="(inp, i) in processForm.inputs" :key="'inp'+i" class="d-flex gap-1 mb-1">
                      <b-form-input class="form-control-sm" v-model="processForm.inputs[i]" placeholder="Ex : Demande initiale"/>
                      <b-button size="sm" variant="danger" @click="removeList(processForm.inputs,i)" title="Supprimer">✕</b-button>
                    </div>
                    <b-button size="sm" variant="outline-primary" @click="processForm.inputs.push('')">+ Ajouter</b-button>
                  </b-col>

                  <!-- Sorties avec compteur -->
                  <b-col cols="12" class="mt-2">
                    <label class="form-label mb-1 fw-semibold">
                      Données de sortie
                      <b-badge bg="success" class="ms-2">{{ processForm.outputs.filter(o => o.trim()).length }}</b-badge>
                    </label>
                    <div v-for="(out, i) in processForm.outputs" :key="'out'+i" class="d-flex gap-1 mb-1">
                      <b-form-input class="form-control-sm" v-model="processForm.outputs[i]" placeholder="Ex : Rapport final"/>
                      <b-button size="sm" variant="danger" @click="removeList(processForm.outputs,i)" title="Supprimer">✕</b-button>
                    </div>
                    <b-button size="sm" variant="outline-primary" @click="processForm.outputs.push('')">+ Ajouter</b-button>
                  </b-col>

                  <!-- Ressources avec compteur -->
                  <b-col cols="12" class="mt-2">
                    <label class="form-label mb-1 fw-semibold">
                      Ressources
                      <b-badge bg="warning" class="ms-2">{{ processForm.resources.filter(r => r.trim()).length }}</b-badge>
                    </label>
                    <div v-for="(res, i) in processForm.resources" :key="'res'+i" class="d-flex gap-1 mb-1">
                      <b-form-input class="form-control-sm" v-model="processForm.resources[i]" placeholder="Ex : Logiciel ERP"/>
                      <b-button size="sm" variant="danger" @click="removeList(processForm.resources,i)" title="Supprimer">✕</b-button>
                    </div>
                    <b-button size="sm" variant="outline-primary" @click="processForm.resources.push('')">+ Ajouter</b-button>
                  </b-col>

                  <!-- Boutons -->
                  <b-col cols="12" class="text-end pt-1">
                    <b-button size="sm" variant="light" class="me-1" @click="resetProcess">Annuler</b-button>
                    <b-button size="sm" variant="primary" type="submit" :disabled="!processForm.macro_process_id">Valider</b-button>
                  </b-col>

                </b-row>
              </b-form>

              <!-- TABLE PROCESSES -->
              <DataTable :value="processesFiltered" size="small" class="pv-table flat">
                <Column header="Code" style="width:120px">
                  <template #body="{data}"><span class="font-monospace text-primary">{{ data.code }}</span></template>
                </Column>

                <Column header="Nom">
                  <template #body="{data}"><span class="fw-semibold">{{ data.name }}</span></template>
                </Column>

                <Column header="Entrées" style="width:80px" bodyClass="text-center">
                  <template #body="{data}">
                    <b-badge :variant="data.inputs?.length > 0 ? 'info' : 'secondary'">
                      <i class="ti ti-arrow-down"></i> {{ data.inputs?.length || 0 }}
                    </b-badge>
                  </template>
                </Column>

                <Column header="Sorties" style="width:80px" bodyClass="text-center">
                  <template #body="{data}">
                    <b-badge :variant="data.outputs?.length > 0 ? 'success' : 'secondary'">
                      <i class="ti ti-arrow-up"></i> {{ data.outputs?.length || 0 }}
                    </b-badge>
                  </template>
                </Column>

                <Column header="Ressources" style="width:100px" bodyClass="text-center">
                  <template #body="{data}">
                    <b-badge :variant="data.resources?.length > 0 ? 'warning' : 'secondary'">
                      <i class="ti ti-tools"></i> {{ data.resources?.length || 0 }}
                    </b-badge>
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

            <!-- ===================== ACTIVITÉ AVEC IA ===================== -->
            <div v-else-if="activeTab==='activity'">
              <b-form @submit.prevent="submitActivity" class="mb-2">
                <b-row class="g-2">

                  <b-col cols="12">
                    <label class="form-label mb-1">Macro *</label>
                    <b-form-select class="form-select-sm" v-model="activityForm.macro_process_id" :options="macroOptionsActivity" required/>
                  </b-col>

                  <b-col cols="12">
                    <label class="form-label mb-1">Processus *</label>
                    <b-form-select class="form-select-sm" v-model="activityForm.process_id" :options="processOptionsActivity" :disabled="!activityForm.macro_process_id" required @change="onProcessActivitySelected"/>
                  </b-col>

                  <!-- Statistiques du processus sélectionné -->
                  <b-col v-if="activityForm.process_id" cols="12" class="border-bottom pb-2 mb-2">
                    <div class="quick-stats">
                      <span class="stat-chip">
                        <i class="ti ti-list-details"></i> {{ activitiesByProcess.length }} activités
                      </span>
                      <span class="stat-chip">
                        <i class="ti ti-arrow-down"></i> {{ getProcessInputCount(activityForm.process_id) }} entrées
                      </span>
                      <span class="stat-chip">
                        <i class="ti ti-arrow-up"></i> {{ getProcessOutputCount(activityForm.process_id) }} sorties
                      </span>
                      <span class="stat-chip">
                        <i class="ti ti-tools"></i> {{ getProcessResourceCount(activityForm.process_id) }} ressources
                      </span>
                    </div>
                  </b-col>

                  <!-- 🤖 SUGGESTIONS ACTIVITÉS -->
                  <b-col v-if="activitesSuggestions.length > 0" cols="12">
                    <div class="ai-suggestions-box">
                      <div class="ai-header">
                        ✨ <strong>Suggestions d'activités (IA)</strong>
                        <small v-if="isLoadingActivites" class="text-info ms-2"><i class="ti ti-loading"></i> Génération...</small>
                      </div>
                      <div class="suggestion-chips">
                        <b-badge 
                          v-for="(sugg, idx) in activitesSuggestions" 
                          :key="idx"
                          bg="success"
                          class="suggestion-chip"
                          @click="selectActivitesSuggestion(sugg)"
                          role="button"
                          title="Cliquer pour sélectionner"
                        >
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
                  <template #body="{data}">
                    <small class="text-muted">{{ data.description || '—' }}</small>
                  </template>
                </Column>

                <Column header="" style="width:50px" bodyClass="text-end">
                  <template #body="{data}">
                    <b-button size="sm" variant="info" @click="editActivite(data)" title="Éditer"><i class="ti ti-pencil"></i></b-button>
                  </template>
                </Column>

                <template #empty><div class="text-muted py-1">Aucune activité</div></template>
              </DataTable>
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

            <Tree :value="treeNodes"
                  v-model:expandedKeys="expandedKeys"
                  selectionMode="single"
                  :filter="true"
                  filterMode="lenient"
                  class="w-100 pv-tree rounded">

              <template #default="{ node }">
                <div class="d-flex align-items-center gap-2">
                  <span class="icon-badge" :class="typeColorClass(node)">
                    <i :class="nodeIcon(node)" class="node-icon"></i>
                    <span class="badge-letter">{{ badgeLetter(node) }}</span>
                  </span>
                  <span class="code-chip font-monospace">{{ node.data.code }}</span>
                  <span class="fw-semibold">{{ node.label }}</span>
                  <span v-if="node.data.dataCount" class="badge bg-light text-dark ms-auto">
                    📊 {{ node.data.dataCount }}
                  </span>
                </div>
              </template>

            </Tree>

          </b-card-body>
        </b-card>
      </b-col>

    </b-row>

    <!-- MODALES (même que avant) -->
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
  activities: Array
})

/* ================== TABS ================== */
const activeTab = ref('macro')
const tabVariant = t => activeTab.value===t?'primary':'outline-primary'

/* ================== FORMS ================== */
const processForm = useForm({
  macro_process_id: null,
  name: '',
  inputs: [''],
  outputs: [''],
  resources: ['']
})

const activityForm = useForm({
  macro_process_id: null,
  process_id: null,
  name: '',
  description: ''
})

/* ================== 🤖 IA STATE ================== */
const isLoadingProcessus = ref(false)
const isLoadingActivites = ref(false)
const processusSuggestions = ref([])
const activitesSuggestions = ref([])
const dataProcessusSuggestions = ref({ inputs: [], outputs: [], resources: [] })

/* ================== EDIT MODALS ================== */
const edit = ref({show:false, id:null, name:''})
const savingEdit = ref(false)

const editProcessusModal = ref({show:false, id:null, name:'', inputs:[], outputs:[], resources:[]})
const savingEditProcessus = ref(false)

const editActiviteModal = ref({show:false, id:null, name:'', description:''})
const savingEditActivite = ref(false)

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

/* ================== STATISTIQUES GLOBALES ================== */
const totalDataCount = computed(() => {
  return props.processes.reduce((sum, p) => {
    return sum + (p.inputs?.length || 0) + (p.outputs?.length || 0) + (p.resources?.length || 0)
  }, 0)
})

/* ================== STATISTIQUES PAR MACRO SÉLECTIONNÉ ================== */
const selectedMacro = computed(() => macrosById.value[String(processForm.macro_process_id)])

const processesByMacro = computed(() => {
  const mID = processForm.macro_process_id
  if (!mID) return []
  return props.processes.filter(p => String(p.macro_process_id) === String(mID))
})

const totalInputsByMacro = computed(() => 
  processesByMacro.value.reduce((sum, p) => sum + (p.inputs?.length || 0), 0)
)

const totalOutputsByMacro = computed(() => 
  processesByMacro.value.reduce((sum, p) => sum + (p.outputs?.length || 0), 0)
)

const totalResourcesByMacro = computed(() => 
  processesByMacro.value.reduce((sum, p) => sum + (p.resources?.length || 0), 0)
)

const activitiesByMacro = computed(() => {
  const mID = processForm.macro_process_id
  if (!mID) return []
  const procIds = props.processes.filter(p => String(p.macro_process_id) === String(mID)).map(p => p.id)
  return props.activities.filter(a => procIds.includes(a.process_id))
})

const macroCompleteness = computed(() => {
  if (!selectedMacro.value) return 0
  const inputs = totalInputsByMacro.value > 0
  const outputs = totalOutputsByMacro.value > 0
  const resources = totalResourcesByMacro.value > 0
  const processes = processesByMacro.value.length > 0
  const count = [inputs, outputs, resources, processes].filter(x => x).length
  return Math.round((count / 4) * 100)
})

const macroCompletenessColor = computed(() => {
  const c = macroCompleteness.value
  if (c === 100) return 'bg-success'
  if (c >= 75) return 'bg-info'
  if (c >= 50) return 'bg-warning'
  return 'bg-danger'
})

/* ================== STATISTIQUES PAR PROCESSUS ================== */
const activitiesByProcess = computed(() => {
  const pID = activityForm.process_id
  if (!pID) return []
  return props.activities.filter(a => String(a.process_id) === String(pID))
})

/* ================== HELPERS ================== */
const getProcessCountByMacro = id => props.processes.filter(p => p.macro_process_id === id).length
const getDataCountByMacro = id => {
  const procs = props.processes.filter(p => p.macro_process_id === id)
  return procs.reduce((sum, p) => sum + (p.inputs?.length || 0) + (p.outputs?.length || 0) + (p.resources?.length || 0), 0)
}

const getProcessInputCount = id => {
  const proc = props.processes.find(p => p.id === Number(id))
  return proc?.inputs?.length || 0
}

const getProcessOutputCount = id => {
  const proc = props.processes.find(p => p.id === Number(id))
  return proc?.outputs?.length || 0
}

const getProcessResourceCount = id => {
  const proc = props.processes.find(p => p.id === Number(id))
  return proc?.resources?.length || 0
}

const isProcessComplete = proc => {
  const hasInputs = proc.inputs?.length > 0
  const hasOutputs = proc.outputs?.length > 0
  const hasResources = proc.resources?.length > 0
  return hasInputs && hasOutputs && hasResources
}

const macroNameById = id => macrosById.value[String(id)]?.name || "—"
const processNameById = id => processesById.value[String(id)]?.name || "—"

const getMacroKindBySeverity = mID => {
  const macro = macrosById.value[String(mID)]
  return kindSeverity(macro?.kind || '')
}

const getProcessKindBySeverity = pID => {
  const proc = processesById.value[String(pID)]
  const macro = macrosById.value[String(proc?.macro_process_id)]
  return kindSeverity(macro?.kind || '')
}

const kindSeverity = k => ({
  Direction: 'info',
  Réalisation: 'success',
  Support: 'warning'
}[k] || 'secondary')

/* ================== SELECT OPTIONS ================== */
const macroOptions = computed(() =>
  [{value:null, text:"— Sélectionner —", disabled:true},
   ...props.macros.map(m=>({value:String(m.id), text:`${m.code} — ${m.name}`}))]
)

const macroOptionsActivity = macroOptions

const processOptionsActivity = computed(() => {
  const mID = activityForm.macro_process_id
  if (!mID)
    return [{value:null, text:"— Choisir un macro —", disabled:true}]

  const list = props.processes.filter(p => String(p.macro_process_id) === String(mID))
  return [
    {value:null, text:list.length?"— Sélectionner —":"— Aucun processus —", disabled:true},
    ...list.map(p=>({value:String(p.id), text:`${p.code} — ${p.name}`}))
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
  return props.activities.filter(a => String(a.process_id) === String(pID))
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
  const count = props.activities.filter(a=>String(a.process_id)===String(pid)).length + 1
  
  return `A${String(count).padStart(2,'0')}P${seq}${letter}`
})

/* ================== TREE NODES ================== */
const expandedKeys = ref({})

const makeMacro = m => ({
  key: `M-${m.id}`,
  label: m.name,
  data: {type:'macro', code:m.code},
  children: []
})

const makeProc = p => ({
  key: `P-${p.id}`,
  label: p.name,
  data: {type:'process', code:p.code, dataCount: (p.inputs?.length || 0) + (p.outputs?.length || 0) + (p.resources?.length || 0)},
  children: []
})

const makeAct = a => ({
  key: `A-${a.id}`,
  label: a.name,
  data: {type:'activity', code:a.code}
})

const treeNodes = computed(() =>
  props.macros.map(m => {
    const root = makeMacro(m)
    const procs = props.processes.filter(p => p.macro_process_id === m.id)
    
    root.children = procs.map(p => {
      const node = makeProc(p)
      const acts = props.activities.filter(a => a.process_id === p.id)
      node.children = acts.map(a => makeAct(a))
      return node
    })
    
    return root
  })
)

const badgeLetter = n =>
  n.data.type==='macro'?'M':n.data.type==='process'?'P':'A'

const nodeIcon = n =>
  n.data.type==='activity'?'pi pi-file':'pi pi-folder'

const typeColorClass = n =>
  n.data.type==='macro'?'color-type-macro':
  n.data.type==='process'?'color-type-process':'color-type-activity'

/* ================== 🤖 IA ACTIONS ================== */

const onMacroSelected = async () => {
  const macro = macrosById.value[String(processForm.macro_process_id)]
  if (!macro) return
  
  processusSuggestions.value = []
  dataProcessusSuggestions.value = { inputs: [], outputs: [], resources: [] }
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

const onProcessActivitySelected = async () => {
  const proc = processesById.value[String(activityForm.process_id)]
  if (!proc) return

  activitesSuggestions.value = []
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

const selectActivitesSuggestion = (sugg) => {
  activityForm.name = sugg.name
  activityForm.description = sugg.description || ''
}

/* ================== FORM ACTIONS ================== */
const removeList = (list, i) => list.splice(i, 1)

const resetProcess = () => {
  processForm.name = ''
  processForm.inputs = ['']
  processForm.outputs = ['']
  processForm.resources = ['']
  processusSuggestions.value = []
  dataProcessusSuggestions.value = { inputs: [], outputs: [], resources: [] }
}

const resetActivity = () => {
  activityForm.name = ''
  activityForm.description = ''
  activitesSuggestions.value = []
}

const validateDefaults = () => {
  router.post(route('param.projects.macro.validate'), {}, {preserveScroll:true})
}

const submitProcess = () => {
  const cleanInputs = processForm.inputs.filter(i => i.trim() !== '')
  const cleanOutputs = processForm.outputs.filter(o => o.trim() !== '')
  const cleanResources = processForm.resources.filter(r => r.trim() !== '')

  router.post(
    route('param.projects.processus.store'),
    {
      macro_process_id: Number(processForm.macro_process_id),
      name: processForm.name,
      inputs: cleanInputs,
      outputs: cleanOutputs,
      resources: cleanResources
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
    route('param.projects.activites.store'),
    {
      process_id: activityForm.process_id,
      name: activityForm.name,
      description: activityForm.description
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        resetActivity()
        activityForm.process_id = null
        router.reload({only:['activities']})
      }
    }
  )
}

/* ================== EDIT MACRO ================== */
const openEditModal = m => {
  edit.value = {show:true, id:m.id, name:m.name}
}

const submitEdit = () => {
  savingEdit.value = true
  router.put(
    route('param.projects.mpa.update', edit.value.id),
    {name: edit.value.name},
    {
      preserveScroll: true,
      onFinish: () => {
        savingEdit.value = false
        edit.value.show = false
        router.reload({only:['macros']})
      }
    }
  )
}

/* ================== EDIT PROCESSUS ================== */
const editProcessus = (proc) => {
  editProcessusModal.value = {
    show: true,
    id: proc.id,
    name: proc.name,
    inputs: proc.inputs?.map(i => i.label || i) || [],
    outputs: proc.outputs?.map(o => o.label || o) || [],
    resources: proc.resources?.map(r => r.label || r) || []
  }
}

const submitEditProcessus = () => {
  savingEditProcessus.value = true
  router.put(
    route('param.projects.processus.update', editProcessusModal.value.id),
    {
      name: editProcessusModal.value.name,
      inputs: editProcessusModal.value.inputs.filter(i => i.trim()),
      outputs: editProcessusModal.value.outputs.filter(o => o.trim()),
      resources: editProcessusModal.value.resources.filter(r => r.trim())
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
  editActiviteModal.value = {
    show: true,
    id: act.id,
    name: act.name,
    description: act.description || ''
  }
}

const submitEditActivite = () => {
  savingEditActivite.value = true
  router.put(
    route('param.projects.activites.update', editActiviteModal.value.id),
    {
      name: editActiviteModal.value.name,
      description: editActiviteModal.value.description
    },
    {
      preserveScroll: true,
      onFinish: () => {
        savingEditActivite.value = false
        editActiviteModal.value.show = false
        router.reload({only:['activities']})
      }
    }
  )
}
</script>

<style scoped>
.form-control-sm, .form-select-sm { font-size:.75rem; height:26px; padding:.15rem .45rem }
.btn-sm { padding:.15rem .45rem; font-size:.72rem }

/* STATISTIQUES */
.stat-card { border-left: 4px solid transparent; transition: all 0.2s }
.stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); transform: translateY(-2px) }
.stat-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px }
.stat-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600 }
.badge-success { background: #d4edda; color: #155724 }
.badge-warning { background: #fff3cd; color: #856404 }
.badge-info { background: #d1ecf1; color: #0c5460 }
.badge-secondary { background: #e2e3e5; color: #383d41 }

.stat-detail { background: #f8f9fa; padding: 12px; border-radius: 6px }
.stat-label { font-size: 11px; text-transform: uppercase; color: #6c757d; font-weight: 600; margin-bottom: 4px }
.stat-value { font-size: 24px; font-weight: bold; color: #333; margin-bottom: 6px }
.stat-progress { height: 8px; background: #e9ecef; border-radius: 4px; overflow: hidden }
.progress-bar { height: 100%; background: #007bff; transition: width 0.3s }

.stat-status { background: #f8f9fa; padding: 12px; border-radius: 6px }
.status-label { font-size: 11px; text-transform: uppercase; color: #6c757d; font-weight: 600; margin-bottom: 8px }
.status-chips { display: flex; flex-wrap: wrap; gap: 6px }

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
.color-type-activity { --clr-type: #ef4444 }
.node-icon { color: var(--clr-type) }

.code-chip { background: #eef2f7; border: 1px solid #e2e8f0; padding: .05rem .35rem; border-radius: .35rem; font-size: .72rem }
</style>