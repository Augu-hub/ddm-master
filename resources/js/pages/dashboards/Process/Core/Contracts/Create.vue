<template>
  <VerticalLayout>
    <Head title="Gestion des Contrats d'Interfaces" />

    <!-- ============ HEADER ============ -->
    <b-card class="shadow-sm mb-3 p-3 bg-gradient-primary border-0">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h2 class="fw-bold text-white mb-0" style="font-size: 1.3rem;">
            <i class="ti ti-file-text me-2"></i>
            Contrats d'Interfaces
          </h2>
          <p class="text-white-50 small mb-0">Gestion complète des interfaces processus</p>
        </div>

        <div class="d-flex align-items-center gap-3">
          <div class="position-relative">
            <button @click="showNotifs = !showNotifs" class="btn btn-link text-white p-0" style="font-size: 1.8rem;">
              <i class="ti ti-bell-ringing"></i>
              <span v-if="unreadCount > 0" class="badge bg-danger position-absolute top-0 start-100 translate-middle">
                {{ unreadCount }}
              </span>
            </button>

            <div v-if="showNotifs" class="notification-panel">
              <div class="d-flex justify-content-between align-items-center p-3 border-bottom bg-light fw-bold">
                <span><i class="ti ti-bell me-2"></i>Notifications ({{ notifsList.length }})</span>
                <button @click="showNotifs = false" class="btn btn-sm btn-link p-0 text-muted">
                  <i class="ti ti-x"></i>
                </button>
              </div>

              <div v-if="notifsList.length > 0" style="max-height: 500px; overflow-y: auto;">
                <div v-for="n in notifsList" :key="n.id" 
                     @click="markNotifRead(n.id)"
                     :class="['p-3', 'border-bottom', 'cursor-pointer', !n.read_at ? 'bg-info-soft' : 'bg-white']">
                  <span v-if="!n.read_at" class="badge bg-danger">⚠️ Non-lu</span>
                  <span v-else class="badge bg-secondary">✓ Lu</span>
                  <h6 class="mt-2 mb-2 fw-bold">📋 {{ n.process_name }}</h6>
                  <small class="d-block text-muted mb-1"><i class="ti ti-code"></i> {{ n.process_code }}</small>
                  <small class="d-block mb-1"><strong>Sortie:</strong> {{ n.output_label }}</small>
                  <small class="text-muted" style="font-size: 0.75rem;">{{ formatDate(n.created_at) }}</small>
                </div>
                <div class="p-3 bg-light border-top">
                  <button @click="markAllNotifsRead" class="btn btn-sm btn-outline-primary w-100">
                    <i class="ti ti-check-all me-1"></i> Tout marquer lu
                  </button>
                </div>
              </div>
              <div v-else class="p-4 text-center text-muted">
                <i class="ti ti-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                <p class="mt-2">Aucune notification</p>
              </div>
            </div>
            <div v-if="showNotifs" @click="showNotifs = false" class="notification-overlay"></div>
          </div>

          <div class="text-end text-white" style="font-size: 0.85rem;">
            <strong>{{ user?.name }}</strong><br />
            <small>{{ link?.function_name }}</small>
          </div>
        </div>
      </div>
    </b-card>

    <!-- ============ SÉLECTION PROCESSUS ============ -->
    <b-card class="shadow-sm mb-3 p-3 border-0">
      <h6 class="fw-bold mb-3"><i class="ti ti-list-check text-primary me-2"></i> Sélectionner un Processus</h6>
      <b-row class="g-2">
        <b-col lg="4">
          <label class="fw-bold small text-muted text-uppercase" style="font-size: 0.65rem;">Entité</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light"><i class="ti ti-building"></i></span>
            <input class="form-control form-control-sm" :value="link?.entity_name" disabled />
          </div>
        </b-col>
        <b-col lg="4">
          <label class="fw-bold small text-muted text-uppercase" style="font-size: 0.65rem;">Fonction</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light"><i class="ti ti-briefcase"></i></span>
            <input class="form-control form-control-sm" :value="link?.function_name" disabled />
          </div>
        </b-col>
        <b-col lg="4">
          <label class="fw-bold small text-muted text-uppercase" style="font-size: 0.65rem;">Processus</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light"><i class="ti ti-flow-switch"></i></span>
            <select v-model="selectedProcess" @change="loadContract" class="form-select form-select-sm">
              <option value="">📋 Choisir un processus...</option>
              <option v-for="p in processes" :key="p.id" :value="p.id">{{ p.code }} — {{ p.name }}</option>
            </select>
          </div>
        </b-col>
      </b-row>
    </b-card>

    <!-- ============ LISTE CONTRATS ============ -->
    <b-card v-if="contracts.length > 0" class="shadow-sm mb-3 p-3 border-0">
      <h6 class="fw-bold mb-3"><i class="ti ti-inbox text-primary me-2"></i> Contrats Existants ({{ contracts.length }})</h6>
      <div class="table-responsive">
        <table class="table table-hover align-middle table-sm mb-0" style="font-size: 0.85rem;">
          <thead class="table-light">
            <tr>
              <th style="width: 180px;"><i class="ti ti-code me-1"></i> Processus</th>
              <th><i class="ti ti-user me-1"></i> Propriétaire</th>
              <th style="width: 60px;"><i class="ti ti-arrow-up me-1"></i> Sorties</th>
              <th style="width: 70px;">Statut</th>
              <th style="width: 100px;">Modifié</th>
              <th style="width: 50px;">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="contract in contracts" :key="contract.id" class="cursor-pointer" @click="viewContract(contract)">
              <td>
                <strong class="text-primary">{{ contract.process_code }}</strong><br />
                <small class="text-muted">{{ contract.process_name }}</small>
              </td>
              <td>{{ contract.owner || '—' }}</td>
              <td><span class="badge bg-info-soft text-info">{{ contract.outputs_count }}</span></td>
              <td>
                <span v-if="contract.status === 'active'" class="badge bg-success-soft text-success">✓ Actif</span>
                <span v-else class="badge bg-secondary-soft">{{ contract.status }}</span>
              </td>
              <td><small class="text-muted">{{ contract.updated_at }}</small></td>
              <td>
                <b-button size="sm" variant="outline-primary" @click.stop="loadContractById(contract.id)" style="font-size: 0.75rem;">
                  <i class="ti ti-pencil"></i>
                </b-button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </b-card>

    <!-- ============ CONTRAT EN ÉDITION ============ -->
    <template v-if="currentContract">

      <b-card class="shadow-sm mb-3 p-3 border-primary border-2 bg-light-primary border-0">
        <div class="row align-items-center">
          <div class="col">
            <h5 class="fw-bold text-primary mb-1">
              <i class="ti ti-flow-switch me-2"></i>
              {{ currentContract.process.code }} — {{ currentContract.process.name }}
            </h5>
            <p class="text-muted mb-0" style="font-size: 0.75rem;">
              <i class="ti ti-quote me-1"></i>
              {{ currentContract.purpose || 'Aucune finalité définie' }}
            </p>
          </div>
          <div class="col-auto text-end">
            <small class="text-muted d-block">Propriétaire</small>
            <strong class="text-primary">{{ currentContract.owner || '—' }}</strong>
          </div>
        </div>
      </b-card>

      <b-card class="shadow-sm mb-3 border-0">
        <b-tabs>

          <!-- TAB 1: CONTRAT -->
          <b-tab title="📋 Contrat" active>
            <div class="p-3">

              <!-- INFOS GÉNÉRALES -->
              <div class="mb-4">
                <h6 class="fw-bold mb-3"><i class="ti ti-info-circle text-primary me-2"></i> Informations Générales</h6>
                <b-row class="g-2">
                  <b-col md="6">
                    <label class="fw-bold small text-muted text-uppercase" style="font-size: 0.65rem;">Propriétaire</label>
                    <input v-model="currentContract.owner" type="text" class="form-control form-control-sm" placeholder="Nom" />
                  </b-col>
                  <b-col md="6">
                    <label class="fw-bold small text-muted text-uppercase" style="font-size: 0.65rem;">Finalité</label>
                    <textarea v-model="currentContract.purpose" class="form-control form-control-sm" rows="1" placeholder="Objectif"></textarea>
                  </b-col>
                </b-row>
              </div>

              <!-- ========== INDICATEURS ULTRA-COMPLETS ========== -->
              <div class="mb-4">
                <h6 class="fw-bold mb-4">
                  <i class="ti ti-chart-bar text-warning me-2"></i>
                  📊 INDICATEURS COMPLETS D'ACTIVITÉ & PERFORMANCE
                </h6>

                <b-row class="g-3">

                  <!-- ACTIVITÉ (GAUCHE) -->
                  <b-col lg="6">
                    <b-card class="border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #E7F5FF 0%, #F0F9FF 100%);">
                      <template #header>
                        <div class="d-flex justify-content-between align-items-center w-100">
                          <h6 class="fw-bold mb-0" style="font-size: 0.95rem;">
                            <i class="ti ti-activity text-primary me-2"></i>
                            📊 Indicateurs d'Activité
                          </h6>
                          <span class="badge bg-primary-soft text-primary">{{ activityIndicatorsList.length }}</span>
                        </div>
                      </template>

                      <!-- INPUT -->
                      <div class="mb-3">
                        <label class="fw-bold small text-muted text-uppercase" style="font-size: 0.65rem;">Ajouter un indicateur</label>
                        <div class="input-group input-group-sm">
                          <input v-model="newActivityIndicator" type="text" class="form-control form-control-sm" 
                                 placeholder="Ex: Délai moyen 48h" @keyup.enter="addActivityIndicator" />
                          <button @click="addActivityIndicator" class="btn btn-primary" style="font-size: 0.75rem;">
                            <i class="ti ti-plus me-1"></i> Ajouter
                          </button>
                        </div>
                      </div>

                      <!-- LISTE -->
                      <div v-if="activityIndicatorsList.length > 0">
                        <div v-for="(indicator, idx) in activityIndicatorsList" :key="idx" 
                             class="d-flex justify-content-between align-items-center p-2 mb-2 bg-white rounded"
                             style="border-left: 4px solid #0d6efd;">
                          <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2">
                              <span class="badge bg-primary" style="font-size: 0.65rem;">{{ idx + 1 }}</span>
                              <div>
                                <strong class="text-dark d-block" style="font-size: 0.85rem;">{{ indicator }}</strong>
                              </div>
                            </div>
                          </div>
                          <button @click="removeActivityIndicator(idx)" class="btn btn-sm btn-outline-danger" 
                                  style="font-size: 0.7rem; padding: 0.2rem 0.4rem;">
                            <i class="ti ti-trash"></i>
                          </button>
                        </div>
                      </div>

                      <div v-else class="alert alert-info mb-0" style="font-size: 0.8rem;">
                        <i class="ti ti-info-circle me-2"></i> Aucun indicateur d'activité défini
                      </div>

                      <textarea v-model="indicatorsActivity" style="display: none;"></textarea>
                    </b-card>
                  </b-col>

                  <!-- PERFORMANCE (DROITE) -->
                  <b-col lg="6">
                    <b-card class="border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #F0FCF5 0%, #F9FEFB 100%);">
                      <template #header>
                        <div class="d-flex justify-content-between align-items-center w-100">
                          <h6 class="fw-bold mb-0" style="font-size: 0.95rem;">
                            <i class="ti ti-target text-success me-2"></i>
                            🎯 Indicateurs de Performance
                          </h6>
                          <span class="badge bg-success-soft text-success">{{ perfIndicatorsList.length }}</span>
                        </div>
                      </template>

                      <!-- INPUT -->
                      <div class="mb-3">
                        <label class="fw-bold small text-muted text-uppercase" style="font-size: 0.65rem;">Ajouter un indicateur</label>
                        <div class="input-group input-group-sm">
                          <input v-model="newPerfIndicator" type="text" class="form-control form-control-sm" 
                                 placeholder="Ex: Taux satisfaction 95%" @keyup.enter="addPerfIndicator" />
                          <button @click="addPerfIndicator" class="btn btn-success" style="font-size: 0.75rem;">
                            <i class="ti ti-plus me-1"></i> Ajouter
                          </button>
                        </div>
                      </div>

                      <!-- LISTE -->
                      <div v-if="perfIndicatorsList.length > 0">
                        <div v-for="(indicator, idx) in perfIndicatorsList" :key="idx" 
                             class="d-flex justify-content-between align-items-center p-2 mb-2 bg-white rounded"
                             style="border-left: 4px solid #198754;">
                          <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2">
                              <span class="badge bg-success" style="font-size: 0.65rem;">{{ idx + 1 }}</span>
                              <div>
                                <strong class="text-dark d-block" style="font-size: 0.85rem;">{{ indicator }}</strong>
                              </div>
                            </div>
                          </div>
                          <button @click="removePerfIndicator(idx)" class="btn btn-sm btn-outline-danger" 
                                  style="font-size: 0.7rem; padding: 0.2rem 0.4rem;">
                            <i class="ti ti-trash"></i>
                          </button>
                        </div>
                      </div>

                      <div v-else class="alert alert-info mb-0" style="font-size: 0.8rem;">
                        <i class="ti ti-info-circle me-2"></i> Aucun indicateur de performance défini
                      </div>

                      <textarea v-model="indicatorsPerf" style="display: none;"></textarea>
                    </b-card>
                  </b-col>

                </b-row>
              </div>

              <!-- STATISTIQUES INDICATEURS -->
              <div class="mb-4">
                <b-row class="g-2">
                  <b-col sm="6" lg="3">
                    <b-card class="border-0 shadow-sm text-center" style="background: #E7F5FF;">
                      <div class="fw-bold text-primary" style="font-size: 2rem;">{{ activityIndicatorsList.length }}</div>
                      <small class="text-muted d-block">Indicateurs d'Activité</small>
                    </b-card>
                  </b-col>
                  <b-col sm="6" lg="3">
                    <b-card class="border-0 shadow-sm text-center" style="background: #F0FCF5;">
                      <div class="fw-bold text-success" style="font-size: 2rem;">{{ perfIndicatorsList.length }}</div>
                      <small class="text-muted d-block">Indicateurs de Performance</small>
                    </b-card>
                  </b-col>
                  <b-col sm="6" lg="3">
                    <b-card class="border-0 shadow-sm text-center" style="background: #FFF3E0;">
                      <div class="fw-bold text-warning" style="font-size: 2rem;">{{ totalIndicators }}</div>
                      <small class="text-muted d-block">Total Indicateurs</small>
                    </b-card>
                  </b-col>
                  <b-col sm="6" lg="3">
                    <b-card class="border-0 shadow-sm text-center" style="background: #F3E5F5;">
                      <div class="fw-bold text-danger" style="font-size: 2rem;">{{ currentContract.outputs.length }}</div>
                      <small class="text-muted d-block">Données de Sortie</small>
                    </b-card>
                  </b-col>
                </b-row>
              </div>

              <!-- DONNÉES DE SORTIE -->
              <div class="mb-4">
                <h6 class="fw-bold mb-3"><i class="ti ti-arrow-up-circle text-info me-2"></i> Données de Sortie — 🤖 Suggestions IA</h6>

                <div class="table-responsive" style="max-height: 700px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem;">
                  <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 0.75rem;">
                    <thead class="table-light sticky-top">
                      <tr>
                        <th style="width: 35px;" class="text-center">#</th>
                        <th style="width: 140px; background: #e3f2fd;">Sortie</th>
                        <th style="width: 110px;">Fonction</th>
                        <th style="width: 100px;">Utilisateur</th>
                        <th style="width: 120px;">Attentes</th>
                        <th style="width: 250px; background: #fff3cd; border-left: 3px solid #ffc107;">🤖 Suggestions IA</th>
                        <th style="width: 70px;">Document</th>
                        <th style="width: 50px;" class="text-center">Notif</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(item, idx) in currentContract.outputs" :key="item.id" :class="{ 'table-light': idx % 2 === 0 }">
                        <td class="text-center fw-bold text-muted">{{ idx + 1 }}</td>
                        <td style="background: #e3f2fd;"><strong class="text-dark">{{ item.label }}</strong></td>
                        <td>
                          <select v-model="item.actor_function_id" @change="updateActorFromFunction(idx)" class="form-select form-select-sm" style="font-size: 0.75rem;">
                            <option value="">— Aucune</option>
                            <option v-for="fn in currentContract.functions" :key="fn.id" :value="fn.id">{{ fn.name }}</option>
                          </select>
                        </td>
                        <td>
                          <span v-if="item.user_name" class="badge bg-primary-soft text-primary" style="font-size: 0.65rem;">
                            <i class="ti ti-user"></i> {{ item.user_name }}
                          </span>
                          <small v-else class="text-muted">—</small>
                        </td>
                        <td>
                          <textarea v-model="item.expectations" class="form-control form-control-sm" rows="1" placeholder="Détails" style="font-size: 0.75rem;"></textarea>
                        </td>

                        <!-- SUGGESTIONS IA -->
                        <td style="background: #fff3cd; border-left: 3px solid #ffc107;">
                          <div v-if="item.aisu?.expectations?.length > 0" class="bg-warning bg-opacity-15 p-2 rounded" style="font-size: 0.7rem; max-height: 300px; overflow-y: auto;">
                            <div class="mb-2">
                              <small class="fw-bold d-block">📌 Attentes:</small>
                              <div v-for="(exp, i) in item.aisu.expectations" :key="i" class="small mb-1 ps-2" style="border-left: 2px solid #ffc107;">
                                <i class="ti ti-check text-success" style="font-size: 0.6rem;"></i> {{ exp }}
                              </div>
                            </div>
                            <div v-if="item.aisu.quality_criteria?.length > 0" class="mb-2">
                              <small class="fw-bold d-block">✓ Critères:</small>
                              <div v-for="(c, i) in item.aisu.quality_criteria" :key="i" class="small">{{ c }}</div>
                            </div>
                            <div v-if="item.aisu.validation_steps?.length > 0" class="mb-2">
                              <small class="fw-bold d-block">⚙️ Validation:</small>
                              <div v-for="(v, i) in item.aisu.validation_steps" :key="i" class="small">{{ v }}</div>
                            </div>
                            <div v-if="item.aisu.user_recommendations?.[0]" class="mb-2 p-1 bg-info bg-opacity-10 rounded">
                              <small class="fw-bold d-block">👤 Recommandé:</small>
                              <strong class="small text-primary">{{ item.aisu.user_recommendations[0].user_name }}</strong><br>
                              <small class="text-muted">{{ item.aisu.user_recommendations[0].job_title }}</small>
                            </div>
                            <div class="d-flex gap-1">
                              <button @click="applySuggestions(item)" class="btn btn-sm btn-warning flex-grow-1" style="font-size: 0.65rem;">
                                <i class="ti ti-check"></i> Appliquer
                              </button>
                              <button @click="clearSuggestions(item)" class="btn btn-sm btn-outline-secondary flex-grow-1" style="font-size: 0.65rem;">
                                <i class="ti ti-x"></i> Reset
                              </button>
                            </div>
                          </div>

                          <div v-else-if="loadingSuggestions[item.id]" class="text-center py-2">
                            <b-spinner small variant="warning"></b-spinner><br>
                            <small class="text-muted" style="font-size: 0.65rem;">Génération...</small>
                          </div>

                          <button v-else-if="item.actor_function_id" @click="generateAISuggestions(item, idx)" class="btn btn-sm btn-outline-warning w-100" style="font-size: 0.65rem; padding: 0.25rem; white-space: normal;">
                            <i class="ti ti-robot"></i><br><strong>Générer</strong>
                          </button>

                          <span v-else class="text-muted small d-block p-1 text-center" style="font-size: 0.65rem;">
                            <i class="ti ti-alert-circle"></i><br>Sélectionnez fonction
                          </span>
                        </td>

                        <td class="text-center">
                          <div class="btn-group btn-group-sm">
                            <label class="btn btn-outline-primary" style="font-size: 0.7rem; padding: 0.2rem 0.3rem; cursor: pointer;">
                              <i class="ti ti-upload"></i>
                              <input type="file" style="display:none" @change="uploadFile($event, idx)" />
                            </label>
                            <button v-if="item.document_path" @click="downloadFile(idx)" class="btn btn-outline-success" style="font-size: 0.7rem; padding: 0.2rem 0.3rem;">
                              <i class="ti ti-download"></i>
                            </button>
                            <button v-if="item.document_path" @click="deleteFile(idx)" class="btn btn-outline-danger" style="font-size: 0.7rem; padding: 0.2rem 0.3rem;">
                              <i class="ti ti-trash"></i>
                            </button>
                          </div>
                          <small v-if="item.file_name" class="d-block text-muted mt-1" style="font-size: 0.65rem;">{{ item.file_name }}</small>
                        </td>

                        <td class="text-center">
                          <button v-if="item.actor_function_id && item.user_name" @click="sendNotif(idx)" class="btn btn-sm btn-outline-info" style="font-size: 0.7rem; padding: 0.2rem 0.3rem;">
                            <i class="ti ti-send"></i>
                          </button>
                          <span v-else class="text-muted small">—</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- ACTIONS -->
              <div class="d-flex justify-content-between gap-2 flex-wrap">
                <b-button variant="secondary" @click="closeContract" size="sm" style="font-size: 0.8rem;">
                  <i class="ti ti-x me-1"></i> Fermer
                </b-button>

                <div class="d-flex gap-2 flex-wrap">
                  <b-button variant="outline-info" @click="exportExcel" :disabled="saving" size="sm" style="font-size: 0.8rem;">
                    <i class="ti ti-file-spreadsheet me-1"></i> Excel
                  </b-button>
                  <b-button variant="outline-danger" @click="exportPdf" :disabled="saving" size="sm" style="font-size: 0.8rem;">
                    <i class="ti ti-file-pdf me-1"></i> PDF
                  </b-button>
                  <b-button variant="success" @click="printContract" size="sm" style="font-size: 0.8rem;">
                    <i class="ti ti-printer me-1"></i> Imprimer
                  </b-button>
                  <b-button variant="primary" @click="saveContract" :disabled="saving" size="sm" style="font-size: 0.8rem;">
                    <b-spinner small v-if="saving" class="me-1"></b-spinner>
                    <i v-else class="ti ti-device-floppy me-1"></i>
                    {{ saving ? 'Enr...' : 'Enregistrer' }}
                  </b-button>
                </div>
              </div>
            </div>
          </b-tab>

          <!-- TAB 2: HISTORIQUE -->
          <b-tab title="📜 Historique">
            <div class="p-3">
              <div v-if="histories.length > 0">
                <div v-for="(h, idx) in histories" :key="idx" class="d-flex gap-3 mb-3 pb-3 border-bottom">
                  <span :class="['d-flex', 'align-items-center', 'justify-content-center', 'rounded-circle', 'fw-bold', getActionColor(h.action)]" style="width: 40px; height: 40px;">
                    <i :class="getActionIcon(h.action)"></i>
                  </span>
                  <div class="flex-grow-1">
                    <h6 class="fw-bold mb-1">{{ h.action_label }}</h6>
                    <small class="text-muted d-block mb-1">
                      <i class="ti ti-user me-1"></i> {{ h.user_name }} • {{ h.created_at }}
                    </small>
                    <p v-if="h.description" class="mb-0 small">{{ h.description }}</p>
                  </div>
                </div>
              </div>
              <div v-else class="alert alert-info text-center mb-0">
                <i class="ti ti-info-circle me-2"></i> Aucun historique
              </div>
            </div>
          </b-tab>

        </b-tabs>
      </b-card>

    </template>

    <!-- VIDE -->
    <div v-else class="alert alert-info text-center p-4 rounded">
      <i class="ti ti-info-circle display-4 text-info mb-3 d-block"></i>
      <h5 class="fw-bold mb-2">Aucun contrat sélectionné</h5>
      <p class="text-muted mb-0">Sélectionnez un processus pour afficher ou créer son contrat.</p>
    </div>

  </VerticalLayout>
</template>

<script setup>
import { Head } from "@inertiajs/vue3"
import VerticalLayout from "@/layouts/VerticalLayout.vue"
import { ref, onMounted, computed } from "vue"

const props = defineProps({ user: Object, link: Object, processes: Array, contracts: Array })

// ========== STATE ==========
const selectedProcess = ref("")
const currentContract = ref(null)
const contractId = ref(null)
const saving = ref(false)
const functionUserMap = ref({})
const loadingSuggestions = ref({})
const histories = ref([])
const indicatorsActivity = ref("")
const indicatorsPerf = ref("")
const newActivityIndicator = ref("")
const newPerfIndicator = ref("")
const showNotifs = ref(false)
const notifsList = ref([])
const unreadCount = ref(0)

onMounted(() => {
  loadNotifs()
  setInterval(loadNotifs, 30000)
})

// ========== COMPUTED ==========
const activityIndicatorsList = computed(() => {
  return indicatorsActivity.value.split('\n').map(i => i.trim()).filter(i => i.length > 0)
})

const perfIndicatorsList = computed(() => {
  return indicatorsPerf.value.split('\n').map(i => i.trim()).filter(i => i.length > 0)
})

const totalIndicators = computed(() => {
  return activityIndicatorsList.value.length + perfIndicatorsList.value.length
})

// ========== INDICATEURS - GESTION ==========
function addActivityIndicator() {
  if (!newActivityIndicator.value.trim()) {
    alert('❌ Veuillez entrer un indicateur')
    return
  }
  const current = indicatorsActivity.value.trim()
  indicatorsActivity.value = current ? current + '\n' + newActivityIndicator.value.trim() : newActivityIndicator.value.trim()
  newActivityIndicator.value = ""
}

function removeActivityIndicator(idx) {
  const items = activityIndicatorsList.value
  items.splice(idx, 1)
  indicatorsActivity.value = items.join('\n')
}

function addPerfIndicator() {
  if (!newPerfIndicator.value.trim()) {
    alert('❌ Veuillez entrer un indicateur')
    return
  }
  const current = indicatorsPerf.value.trim()
  indicatorsPerf.value = current ? current + '\n' + newPerfIndicator.value.trim() : newPerfIndicator.value.trim()
  newPerfIndicator.value = ""
}

function removePerfIndicator(idx) {
  const items = perfIndicatorsList.value
  items.splice(idx, 1)
  indicatorsPerf.value = items.join('\n')
}

// ========== REQUÊTE SÉCURISÉE ==========
async function secureRequest(url, data = {}, method = 'POST') {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
  if (!csrfToken) throw new Error('Token CSRF manquant')

  const options = {
    method,
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      'Accept': 'application/json'
    }
  }

  if (method !== 'GET') {
    options.body = JSON.stringify(data)
  }

  try {
    const response = await fetch(url, options)
    if (!response.ok) throw new Error(`HTTP ${response.status}`)
    return await response.json()
  } catch (error) {
    console.error('❌ Erreur:', error.message)
    throw error
  }
}

// ========== NOTIFICATIONS ==========
async function loadNotifs() {
  try {
    const result = await secureRequest(route('process.core.process.contracts.notifications'), {}, 'POST')
    notifsList.value = result.notifications || []
    unreadCount.value = result.unread_count || 0
  } catch (e) {
    console.error('❌ loadNotifs:', e.message)
  }
}

async function markNotifRead(id) {
  try {
    await secureRequest(route('process.core.process.contracts.notifications'), { id }, 'POST')
    loadNotifs()
  } catch (e) {
    console.error('❌ Error:', e)
  }
}

async function markAllNotifsRead() {
  try {
    await secureRequest(route('process.core.process.contracts.notifications'), { mark_all: true }, 'POST')
    loadNotifs()
  } catch (e) {
    console.error('❌ Error:', e)
  }
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('fr-FR', {
    year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit'
  })
}

// ========== CONTRATS ==========
async function loadContract() {
  if (!selectedProcess.value) {
    currentContract.value = null
    return
  }

  try {
    saving.value = true
    const response = await fetch(
      route('process.core.process.contracts.load') + '?process_id=' + selectedProcess.value,
      {
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
          'Accept': 'application/json'
        }
      }
    )

    if (!response.ok) throw new Error(`Erreur HTTP ${response.status}`)

    const result = await response.json()
    
    if (result.success) {
      contractId.value = result.contract_id
      currentContract.value = result.data

      // ✅ CHARGER LES INDICATEURS DEPUIS DB
      indicatorsActivity.value = (result.data.activity_indicators || []).join("\n")
      indicatorsPerf.value = (result.data.performance_indicators || []).join("\n")

      loadHistory()

      if (currentContract.value.functions?.length > 0) {
        await loadFunctionUsers()
      }
    }
  } catch (error) {
    console.error('❌ loadContract:', error.message)
    alert(`❌ Erreur: ${error.message}`)
  } finally {
    saving.value = false
  }
}

async function loadFunctionUsers() {
  try {
    const functionIds = currentContract.value.functions.map(f => f.id)
    if (functionIds.length === 0) return

    const result = await secureRequest(
      route('process.core.process.contracts.function-users'),
      { function_ids: functionIds }
    )

    if (result.success && result.users_map) {
      functionUserMap.value = result.users_map
    }
  } catch (error) {
    console.error('❌ loadFunctionUsers:', error.message)
  }
}

function updateActorFromFunction(outputIdx) {
  const output = currentContract.value.outputs[outputIdx]
  
  if (!output.actor_function_id) {
    output.user_name = ''
    output.actor = ''
    output.user_id = null
    output.aisu = null
    return
  }

  const userInfo = functionUserMap.value[output.actor_function_id]
  if (userInfo) {
    output.user_name = userInfo.name
    output.user_id = userInfo.id
    output.actor = userInfo.name
  }

  if (!output.aisu && output.actor_function_id) {
    generateAISuggestions(output, outputIdx)
  }
}

async function generateAISuggestions(row, outputIdx) {
  if (!row.actor_function_id) return

  loadingSuggestions.value[row.id] = true

  try {
    const functionName = currentContract.value.functions.find(f => f.id === row.actor_function_id)?.name

    const result = await secureRequest(
      route('process.core.process.contracts.ai-suggestions'),
      {
        function_id: row.actor_function_id,
        function_name: functionName,
        process_id: currentContract.value.process.id,
        process_name: currentContract.value.process.name,
        output_id: row.id,
        output_label: row.label
      }
    )

    if (result.success) {
      row.aisu = result.suggestions
      if (result.suggestions?.expectations) {
        row.expectations = result.suggestions.expectations.slice(0, 3).join('\n')
      }
    } else {
      throw new Error(result.error || 'Erreur IA')
    }
  } catch (error) {
    console.error('❌ generateAISuggestions:', error.message)
    alert(`❌ Erreur IA: ${error.message}`)
  } finally {
    loadingSuggestions.value[row.id] = false
  }
}

function applySuggestions(row) {
  if (!row.aisu) return
  if (row.aisu.expectations?.length > 0) {
    row.expectations = row.aisu.expectations.join('\n')
  }
  if (row.aisu.user_recommendations?.[0]) {
    row.user_id = row.aisu.user_recommendations[0].user_id
    row.user_name = row.aisu.user_recommendations[0].user_name
  }
  alert('✅ Suggestions appliquées!')
}

function clearSuggestions(row) {
  if (!confirm('Réinitialiser les suggestions ?')) return
  row.aisu = null
  row.expectations = ''
}

async function sendNotif(idx) {
  const o = currentContract.value.outputs[idx]
  
  if (!o.actor_function_id || !o.user_name) {
    alert('❌ Utilisateur manquant')
    return
  }

  if (!confirm(`Notifier ${o.user_name} ?`)) return

  try {
    const u = functionUserMap.value[o.actor_function_id]
    const fn = currentContract.value.functions.find(f => f.id === o.actor_function_id)?.name

    const result = await secureRequest(
      route('process.core.process.contracts.send-notification'),
      {
        contract_id: contractId.value,
        output_id: o.id,
        output_label: o.label,
        function_id: o.actor_function_id,
        function_name: fn,
        user_id: u.id,
        user_name: u.name,
        user_email: u.email,
        expectations: o.expectations,
        process_code: currentContract.value.process.code,
        process_name: currentContract.value.process.name
      }
    )

    if (result.success) {
      alert(`✅ Notifié ${o.user_name}`)
      loadNotifs()
    }
  } catch (error) {
    alert(`❌ Erreur: ${error.message}`)
  }
}

async function uploadFile(event, outputIdx) {
  const file = event.target.files?.[0]
  if (!file) return

  const formData = new FormData()
  formData.append('contract_id', contractId.value)
  formData.append('output_id', currentContract.value.outputs[outputIdx].id)
  formData.append('file', file)

  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
    const response = await fetch(route('process.core.process.contracts.upload'), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
      },
      body: formData
    })

    if (!response.ok) throw new Error(`HTTP ${response.status}`)

    const result = await response.json()
    
    if (result.success) {
      currentContract.value.outputs[outputIdx].document_path = result.path
      currentContract.value.outputs[outputIdx].file_name = result.file_name || file.name
      alert("✅ Fichier uploadé")
    }
  } catch (error) {
    alert(`❌ Erreur: ${error.message}`)
  }

  event.target.value = ''
}

async function downloadFile(outputIdx) {
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
    const response = await fetch(route('process.core.process.contracts.download'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        contract_id: contractId.value,
        output_id: currentContract.value.outputs[outputIdx].id
      })
    })

    if (!response.ok) throw new Error(`HTTP ${response.status}`)

    const blob = await response.blob()
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = currentContract.value.outputs[outputIdx].file_name || 'download'
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
  } catch (error) {
    alert(`❌ Erreur: ${error.message}`)
  }
}

async function deleteFile(outputIdx) {
  if (!confirm("Supprimer ?")) return
  
  try {
    const result = await secureRequest(
      route('process.core.process.contracts.file.delete'),
      {
        contract_id: contractId.value,
        output_id: currentContract.value.outputs[outputIdx].id
      }
    )

    if (result.success) {
      currentContract.value.outputs[outputIdx].document_path = null
      currentContract.value.outputs[outputIdx].file_name = null
      alert("✅ Supprimé")
    }
  } catch (error) {
    alert(`❌ Erreur: ${error.message}`)
  }
}

async function saveContract() {
  if (!contractId.value) return

  saving.value = true

  try {
    // ✅ SAUVEGARDER LES INDICATEURS COMPLETS
    const result = await secureRequest(
      route('process.core.process.contracts.save'),
      {
        contract_id: contractId.value,
        owner: currentContract.value.owner,
        purpose: currentContract.value.purpose,
        outputs: currentContract.value.outputs,
        activity_indicators: activityIndicatorsList.value,
        performance_indicators: perfIndicatorsList.value
      }
    )

    if (result.success) {
      alert("✅ Enregistré")
      loadHistory()
    }
  } catch (error) {
    alert(`❌ Erreur: ${error.message}`)
  } finally {
    saving.value = false
  }
}

function exportExcel() {
  if (!contractId.value) return alert('❌ Aucun contrat')
  window.location.href = route('process.core.process.contracts.export.excel', { contract_id: contractId.value })
}

function exportPdf() {
  if (!contractId.value) return alert('❌ Aucun contrat')
  window.location.href = route('process.core.process.contracts.export.pdf', { contract_id: contractId.value })
}

async function loadHistory() {
  try {
    const result = await secureRequest(
      route('process.core.process.contracts.history'),
      { contract_id: contractId.value }
    )
    histories.value = result.histories || []
  } catch (error) {
    console.error('❌ loadHistory:', error.message)
  }
}

function printContract() {
  window.print()
}

function closeContract() {
  currentContract.value = null
  contractId.value = null
  selectedProcess.value = ""
}

function viewContract(contract) {
  selectedProcess.value = contract.process_id
  loadContractById(contract.id)
}

async function loadContractById(id) {
  contractId.value = id
  const contract = props.contracts.find(c => c.id === id)
  if (contract) {
    selectedProcess.value = contract.process_id
    await loadContract()
  }
}

function getActionColor(action) {
  const colors = {
    created: 'bg-success-soft text-success',
    updated_outputs: 'bg-primary-soft text-primary',
    updated_indicators: 'bg-info-soft text-info',
    file_uploaded: 'bg-warning-soft text-warning',
    file_deleted: 'bg-danger-soft text-danger',
    notification_sent: 'bg-info-soft text-info',
    archived: 'bg-secondary-soft text-secondary',
    restored: 'bg-success-soft text-success'
  }
  return colors[action] || 'bg-secondary-soft text-secondary'
}

function getActionIcon(action) {
  const icons = {
    created: 'ti ti-plus',
    updated_outputs: 'ti ti-pencil',
    updated_indicators: 'ti ti-chart-bar',
    file_uploaded: 'ti ti-upload',
    file_deleted: 'ti ti-trash',
    notification_sent: 'ti ti-send',
    archived: 'ti ti-box',
    restored: 'ti ti-arrow-back'
  }
  return icons[action] || 'ti ti-info-circle'
}
</script>

<style scoped>
.bg-gradient-primary { background: linear-gradient(135deg, #2E75B6 0%, #1F4E78 100%); }
.bg-light-primary { background-color: #E7F1F8; }
.bg-primary-soft { background-color: #D6E7F5 !important; }
.bg-success-soft { background-color: #D4EDDA !important; }
.bg-danger-soft { background-color: #F8D7DA !important; }
.bg-info-soft { background-color: #D1ECF1 !important; }
.bg-warning-soft { background-color: #FFF3CD !important; }
.bg-secondary-soft { background-color: #E2E3E5 !important; }
.cursor-pointer { cursor: pointer; }
.notification-panel {
  position: absolute;
  right: 0;
  top: calc(100% + 10px);
  width: 420px;
  max-height: 600px;
  background: white;
  z-index: 1050;
  overflow-y: auto;
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}
.notification-overlay { position: fixed; inset: 0; z-index: 1049; }

@media print {
  .btn, .input-group-text, select, input, textarea {
    border: none !important;
    background: transparent !important;
    color: #000 !important;
  }
  .btn { display: none; }
  input, textarea { border-bottom: 1px solid #000 !important; }
}
</style>