<template>
  <div class="container-fluid py-4" ref="pageTop">
    <!-- 📊 HEADER STICKY -->
    <div class="d-flex justify-content-between align-items-center mb-4 sticky-header">
      <div>
        <h2 class="fw-bold text-warning mb-0">⚙️ Paramètres d'Audit Interne</h2>
        <small class="text-muted">Configuration Audit (audit_frequency_levels, audit_impact_levels, audit_matrix)</small>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-warning btn-sm" @click="scrollToTop">⬆️ Haut</button>
        <button class="btn btn-secondary btn-sm" @click="goBack">← Retour</button>
      </div>
    </div>

    <!-- 📊 NOTIFICATION GLOBALE -->
    <transition name="fade">
      <div v-if="notification.show" :class="`alert alert-${notification.type} alert-dismissible fade show`" role="alert">
        <strong>{{ notification.title }}</strong>
        {{ notification.message }}
        <button type="button" class="btn-close" @click="closeNotification"></button>
      </div>
    </transition>

    <!-- 📈 STATISTIQUES GLOBALES -->
    <div class="row g-2 mb-4">
      <div class="col-md-3 col-sm-6 col-12">
        <div class="card border-0 shadow-sm bg-info text-white">
          <div class="card-body text-center py-2">
            <h6 class="mb-0">{{ (frequencies || []).length }}</h6>
            <small>Fréquences</small>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6 col-12">
        <div class="card border-0 shadow-sm bg-warning text-dark">
          <div class="card-body text-center py-2">
            <h6 class="mb-0">{{ (impacts || []).length }}</h6>
            <small>Impacts</small>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6 col-12">
        <div class="card border-0 shadow-sm bg-success text-white">
          <div class="card-body text-center py-2">
            <h6 class="mb-0">{{ (matrix || []).length }}</h6>
            <small>Matrice</small>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6 col-12">
        <div class="card border-0 shadow-sm bg-secondary text-white">
          <div class="card-body text-center py-2">
            <h6 class="mb-0">{{ ((entities || []).length + (processes || []).length + (activities || []).length) }}</h6>
            <small>Ent+Proc+Act</small>
          </div>
        </div>
      </div>
    </div>

    <!-- 🗂️ ONGLETS -->
    <div class="card border-0 shadow-sm">
      <div class="card-header p-0 bg-dark">
        <ul class="nav nav-tabs mb-0 flex-wrap" role="tablist">
          <li class="nav-item">
            <button class="nav-link fw-bold small" :class="{ active: activeTab === 'frequency' }" 
                    @click="activeTab = 'frequency'" type="button">📊 Fréq</button>
          </li>
          <li class="nav-item">
            <button class="nav-link fw-bold small" :class="{ active: activeTab === 'impact' }" 
                    @click="activeTab = 'impact'" type="button">⚡ Impact</button>
          </li>
          <li class="nav-item">
            <button class="nav-link fw-bold small" :class="{ active: activeTab === 'matrix' }" 
                    @click="activeTab = 'matrix'" type="button">📈 Matrice</button>
          </li>
          <li class="nav-item">
            <button class="nav-link fw-bold small" :class="{ active: activeTab === 'entity' }" 
                    @click="activeTab = 'entity'" type="button">🏛️ Entités</button>
          </li>
          <li class="nav-item">
            <button class="nav-link fw-bold small" :class="{ active: activeTab === 'process' }" 
                    @click="activeTab = 'process'" type="button">⚙️ Proc</button>
          </li>
          <li class="nav-item">
            <button class="nav-link fw-bold small" :class="{ active: activeTab === 'activity' }" 
                    @click="activeTab = 'activity'" type="button">📌 Act</button>
          </li>
          <li class="nav-item">
            <button class="nav-link fw-bold small" :class="{ active: activeTab === 'exercise' }" 
                    @click="activeTab = 'exercise'" type="button">📚 Exercices</button>
          </li>
          <li class="nav-item">
            <button class="nav-link fw-bold small" :class="{ active: activeTab === 'session' }" 
                    @click="activeTab = 'session'" type="button">🎯 Sessions</button>
          </li>
        </ul>
      </div>

      <div class="card-body p-3">
        
        <!-- 📊 FREQUENCY -->
        <div v-show="activeTab === 'frequency'">
          <div class="d-flex justify-content-between mb-2">
            <h6 class="fw-bold mb-0">📊 Niveaux de Fréquence (audit_frequency_levels)</h6>
            <button class="btn btn-info btn-sm" @click="openModal('frequency')">➕ Ajouter</button>
          </div>
          <div v-if="!frequencies || frequencies.length === 0" class="alert alert-info mb-0">Aucune fréquence créée</div>
          <table v-else class="table table-sm table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Code</th>
                <th>Level</th>
                <th>Label</th>
                <th>Couleur HEX</th>
                <th>Aperçu</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="f in frequencies" :key="f.id">
                <td><strong class="text-primary">{{ f.code }}</strong></td>
                <td><span class="badge bg-secondary">{{ f.level }}/5</span></td>
                <td><strong>{{ f.label }}</strong></td>
                <td><code>{{ f.color }}</code></td>
                <td>
                  <span class="badge" :style="{ backgroundColor: f.color, color: getTextColor(f.color) }">
                    {{ f.label }}
                  </span>
                </td>
                <td>
                  <button class="btn btn-sm btn-outline-primary me-1" @click="editFrequency(f)" title="Modifier">✏️</button>
                  <button class="btn btn-sm btn-outline-danger" @click="deleteItem('frequency', f.id)" title="Supprimer">🗑️</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ⚡ IMPACT -->
        <div v-show="activeTab === 'impact'">
          <div class="d-flex justify-content-between mb-2">
            <h6 class="fw-bold mb-0">⚡ Niveaux d'Impact (audit_impact_levels)</h6>
            <button class="btn btn-warning btn-sm" @click="openModal('impact')">➕ Ajouter</button>
          </div>
          <div v-if="!impacts || impacts.length === 0" class="alert alert-info mb-0">Aucun impact créé</div>
          <table v-else class="table table-sm table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Code</th>
                <th>Level</th>
                <th>Label</th>
                <th>Couleur HEX</th>
                <th>Aperçu</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="i in impacts" :key="i.id">
                <td><strong class="text-primary">{{ i.code }}</strong></td>
                <td><span class="badge bg-secondary">{{ i.level }}/5</span></td>
                <td><strong>{{ i.label }}</strong></td>
                <td><code>{{ i.color }}</code></td>
                <td>
                  <span class="badge" :style="{ backgroundColor: i.color, color: getTextColor(i.color) }">
                    {{ i.label }}
                  </span>
                </td>
                <td>
                  <button class="btn btn-sm btn-outline-primary me-1" @click="editImpact(i)" title="Modifier">✏️</button>
                  <button class="btn btn-sm btn-outline-danger" @click="deleteItem('impact', i.id)" title="Supprimer">🗑️</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- 📊 MATRIX -->
        <div v-show="activeTab === 'matrix'">
          <div class="d-flex justify-content-between mb-2">
            <h6 class="fw-bold mb-0">📈 Matrice Risques 5×5 (audit_matrix)</h6>
            <button class="btn btn-success btn-sm" @click="openModal('matrix')">➕ Ajouter</button>
          </div>
          <div v-if="!matrix || matrix.length === 0" class="alert alert-info mb-0">Aucune entrée matrice</div>
          
          <!-- ✅ MATRICE 5×5 VISUELLE -->
          <div v-else class="table-responsive">
            <table class="table table-sm table-bordered text-center mb-3">
              <thead class="table-dark">
                <tr>
                  <th>Impact \ Freq</th>
                  <th v-for="f in [1,2,3,4,5]" :key="`freq-${f}`">{{ f }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="impact in [1,2,3,4,5]" :key="`impact-${impact}`">
                  <th class="table-dark">{{ impact }}</th>
                  <td v-for="freq in [1,2,3,4,5]" :key="`cell-${impact}-${freq}`">
                    <div v-if="getMatrixCell(impact, freq)" 
                         class="p-2 rounded font-weight-bold"
                         :style="{ backgroundColor: getMatrixCell(impact, freq).color, color: getTextColor(getMatrixCell(impact, freq).color) }">
                      <strong>{{ getMatrixCell(impact, freq).criticality_score }}</strong>
                      <small class="d-block">{{ getMatrixCell(impact, freq).label || '-' }}</small>
                    </div>
                    <div v-else class="p-2 text-muted">-</div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- TABLEAU DÉTAILLÉ -->
          <table v-if="matrix && matrix.length > 0" class="table table-sm table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Impact</th>
                <th>Freq</th>
                <th>Score</th>
                <th class="d-none d-md-table-cell">Label</th>
                <th>Couleur</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in matrix" :key="m.id">
                <td><span class="badge bg-danger">{{ m.impact_level }}</span></td>
                <td><span class="badge bg-warning text-dark">{{ m.frequency_level }}</span></td>
                <td><strong>{{ m.criticality_score }}</strong></td>
                <td class="d-none d-md-table-cell">{{ truncate(m.label, 20) }}</td>
                <td><code>{{ m.color }}</code></td>
                <td>
                  <button class="btn btn-sm btn-outline-danger" @click="deleteItem('matrix', m.id)" title="Supprimer">🗑️</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- 🏛️ ENTITIES -->
        <div v-show="activeTab === 'entity'">
          <div class="d-flex justify-content-between mb-2">
            <h6 class="fw-bold mb-0">🏛️ Entités</h6>
            <button class="btn btn-danger btn-sm" @click="openModal('entity')">➕ Ajouter</button>
          </div>
          <div v-if="!entities || entities.length === 0" class="alert alert-info mb-0">Aucune entité créée</div>
          <table v-else class="table table-sm table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Code</th>
                <th>Nom</th>
                <th class="d-none d-md-table-cell">Level</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="e in entities" :key="e.id">
                <td><strong class="text-primary">{{ e.code_base }}</strong></td>
                <td>{{ e.name }}</td>
                <td class="d-none d-md-table-cell"><span class="badge bg-light text-dark">{{ e.level }}</span></td>
                <td>
                  <button class="btn btn-sm btn-outline-primary me-1" @click="editEntity(e)" title="Modifier">✏️</button>
                  <button class="btn btn-sm btn-outline-danger" @click="deleteItem('entity', e.id)" title="Supprimer">🗑️</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ⚙️ PROCESSES -->
        <div v-show="activeTab === 'process'">
          <div class="d-flex justify-content-between mb-2">
            <h6 class="fw-bold mb-0">⚙️ Processus</h6>
            <button class="btn btn-success btn-sm" @click="openModal('process')">➕ Ajouter</button>
          </div>
          <div v-if="!processes || processes.length === 0" class="alert alert-info mb-0">Aucun processus créé</div>
          <table v-else class="table table-sm table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Code</th>
                <th>Nom</th>
                <th class="d-none d-md-table-cell">Entité</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in processes" :key="p.id">
                <td><strong class="text-primary">{{ p.code }}</strong></td>
                <td>{{ p.name }}</td>
                <td class="d-none d-md-table-cell"><span class="badge bg-light text-dark">{{ getEntityName(p.entity_id) }}</span></td>
                <td>
                  <button class="btn btn-sm btn-outline-primary me-1" @click="editProcess(p)" title="Modifier">✏️</button>
                  <button class="btn btn-sm btn-outline-danger" @click="deleteItem('process', p.id)" title="Supprimer">🗑️</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- 📌 ACTIVITIES -->
        <div v-show="activeTab === 'activity'">
          <div class="d-flex justify-content-between mb-2">
            <h6 class="fw-bold mb-0">📌 Activités</h6>
            <button class="btn btn-secondary btn-sm" @click="openModal('activity')">➕ Ajouter</button>
          </div>
          <div v-if="!activities || activities.length === 0" class="alert alert-info mb-0">Aucune activité créée</div>
          <table v-else class="table table-sm table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Code</th>
                <th>Nom</th>
                <th class="d-none d-md-table-cell">Processus</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="a in activities" :key="a.id">
                <td><strong class="text-primary">{{ a.code }}</strong></td>
                <td>{{ a.name }}</td>
                <td class="d-none d-md-table-cell"><span class="badge bg-light text-dark">{{ getProcessName(a.process_id) }}</span></td>
                <td>
                  <button class="btn btn-sm btn-outline-primary me-1" @click="editActivity(a)" title="Modifier">✏️</button>
                  <button class="btn btn-sm btn-outline-danger" @click="deleteItem('activity', a.id)" title="Supprimer">🗑️</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- 📚 EXERCISES -->
        <div v-show="activeTab === 'exercise'">
          <div class="d-flex justify-content-between mb-2">
            <h6 class="fw-bold mb-0">📚 Exercices d'Audit</h6>
            <button class="btn btn-primary btn-sm" @click="openModal('exercise')">➕ Ajouter</button>
          </div>
          <div v-if="!exercises || exercises.length === 0" class="alert alert-info mb-0">Aucun exercice créé</div>
          <table v-else class="table table-sm table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Code</th>
                <th>Nom</th>
                <th>Année</th>
                <th class="d-none d-md-table-cell">Dates</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="ex in exercises" :key="ex.id">
                <td><strong class="text-primary">{{ ex.code }}</strong></td>
                <td>{{ ex.name }}</td>
                <td>{{ ex.year }}</td>
                <td class="d-none d-md-table-cell"><small>{{ formatDate(ex.start_date) }} - {{ formatDate(ex.end_date) }}</small></td>
                <td>
                  <span class="badge" :class="statusBadge(ex.status)">{{ ex.status }}</span>
                </td>
                <td>
                  <button class="btn btn-sm btn-outline-primary me-1" @click="editExercise(ex)" title="Modifier">✏️</button>
                  <button class="btn btn-sm btn-outline-danger" @click="deleteItem('exercise', ex.id)" title="Supprimer">🗑️</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- 🎯 SESSIONS -->
        <div v-show="activeTab === 'session'">
          <div class="d-flex justify-content-between mb-2">
            <h6 class="fw-bold mb-0">🎯 Sessions d'Audit</h6>
            <button class="btn btn-info btn-sm" @click="openModal('session')">➕ Ajouter</button>
          </div>
          <div v-if="!sessions || sessions.length === 0" class="alert alert-info mb-0">Aucune session créée</div>
          <table v-else class="table table-sm table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Code</th>
                <th>Nom</th>
                <th class="d-none d-md-table-cell">Exercice</th>
                <th class="d-none d-md-table-cell">Entité</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="ses in sessions" :key="ses.id">
                <td><strong class="text-primary">{{ ses.code }}</strong></td>
                <td>{{ ses.name }}</td>
                <td class="d-none d-md-table-cell"><span class="badge bg-light text-dark">{{ getExerciseName(ses.exercise_id) }}</span></td>
                <td class="d-none d-md-table-cell"><span class="badge bg-light text-dark">{{ getEntityName(ses.entity_id) }}</span></td>
                <td>
                  <span class="badge" :class="statusBadge(ses.status)">{{ ses.status }}</span>
                </td>
                <td>
                  <button class="btn btn-sm btn-outline-primary me-1" @click="editSession(ses)" title="Modifier">✏️</button>
                  <button class="btn btn-sm btn-outline-danger" @click="deleteItem('session', ses.id)" title="Supprimer">🗑️</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
      </div>
    </div>

    <!-- 🎯 MODAL -->
    <div v-if="showModal" class="modal d-block" style="background: rgba(0,0,0,0.6); z-index: 1050;">
      <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-warning text-dark border-0 py-2">
            <h5 class="modal-title fw-bold small">{{ editingItem ? '✏️ Modifier' : '➕ Ajouter' }}</h5>
            <button type="button" class="btn-close btn-sm" @click="closeModal"></button>
          </div>

          <div class="modal-body small">
            <!-- FORM FREQUENCY/IMPACT -->
            <div v-if="currentForm === 'frequency' || currentForm === 'impact'" class="row g-2">
              <div class="col-6">
                <label class="form-label fw-bold">Code *</label>
                <input v-model="formData.code" type="text" class="form-control form-control-sm">
              </div>
              <div class="col-6">
                <label class="form-label fw-bold">Level (1-5) *</label>
                <input v-model.number="formData.level" type="number" min="1" max="5" class="form-control form-control-sm">
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Label *</label>
                <input v-model="formData.label" type="text" class="form-control form-control-sm">
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Description</label>
                <textarea v-model="formData.description" class="form-control form-control-sm" rows="2"></textarea>
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Couleur HEX *</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text">#</span>
                  <input v-model="formData.color" type="text" class="form-control form-control-sm" placeholder="28a745">
                </div>
                <small class="text-muted d-block mt-1">Format: RRGGBB (ex: 28a745)</small>
                <div v-if="formData.color" class="color-preview mt-2" :style="{ backgroundColor: '#' + formData.color, color: getTextColor('#' + formData.color) }">
                  Aperçu: #{{ formData.color }}
                </div>
              </div>
            </div>

            <!-- FORM MATRIX -->
            <div v-else-if="currentForm === 'matrix'" class="row g-2">
              <div class="col-6">
                <label class="form-label fw-bold">Impact Level (1-5) *</label>
                <input v-model.number="formData.impact_level" type="number" min="1" max="5" class="form-control form-control-sm">
              </div>
              <div class="col-6">
                <label class="form-label fw-bold">Frequency Level (1-5) *</label>
                <input v-model.number="formData.frequency_level" type="number" min="1" max="5" class="form-control form-control-sm">
              </div>
              <div class="col-6">
                <label class="form-label fw-bold">Criticality Score</label>
                <input v-model.number="formData.criticality_score" type="number" class="form-control form-control-sm">
              </div>
              <div class="col-6">
                <label class="form-label fw-bold">Label</label>
                <input v-model="formData.label" type="text" class="form-control form-control-sm">
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Qualification</label>
                <input v-model="formData.qualification" type="text" class="form-control form-control-sm">
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Couleur HEX *</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text">#</span>
                  <input v-model="formData.color" type="text" class="form-control form-control-sm" placeholder="28a745">
                </div>
                <small class="text-muted d-block mt-1">Format: RRGGBB (ex: 28a745)</small>
                <div v-if="formData.color" class="color-preview mt-2" :style="{ backgroundColor: '#' + formData.color, color: getTextColor('#' + formData.color) }">
                  Aperçu: #{{ formData.color }}
                </div>
              </div>
            </div>

            <!-- FORM ENTITY -->
            <div v-else-if="currentForm === 'entity'" class="row g-2">
              <div class="col-6">
                <label class="form-label fw-bold">Code *</label>
                <input v-model="formData.code_base" type="text" class="form-control form-control-sm">
              </div>
              <div class="col-6">
                <label class="form-label fw-bold">Niveau (0-5) *</label>
                <input v-model.number="formData.level" type="number" min="0" max="5" class="form-control form-control-sm">
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Nom *</label>
                <input v-model="formData.name" type="text" class="form-control form-control-sm">
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Description</label>
                <textarea v-model="formData.description" class="form-control form-control-sm" rows="2"></textarea>
              </div>
            </div>

            <!-- FORM PROCESS -->
            <div v-else-if="currentForm === 'process'" class="row g-2">
              <div class="col-6">
                <label class="form-label fw-bold">Code *</label>
                <input v-model="formData.code" type="text" class="form-control form-control-sm">
              </div>
              <div class="col-6">
                <label class="form-label fw-bold">Entité *</label>
                <select v-model.number="formData.entity_id" class="form-select form-select-sm">
                  <option value="">-- Sélectionner --</option>
                  <option v-for="e in (entities || [])" :key="e.id" :value="e.id">{{ e.code_base }} - {{ e.name }}</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Nom *</label>
                <input v-model="formData.name" type="text" class="form-control form-control-sm">
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Description</label>
                <textarea v-model="formData.description" class="form-control form-control-sm" rows="2"></textarea>
              </div>
            </div>

            <!-- FORM ACTIVITY -->
            <div v-else-if="currentForm === 'activity'" class="row g-2">
              <div class="col-6">
                <label class="form-label fw-bold">Code *</label>
                <input v-model="formData.code" type="text" class="form-control form-control-sm">
              </div>
              <div class="col-6">
                <label class="form-label fw-bold">Processus *</label>
                <select v-model.number="formData.process_id" class="form-select form-select-sm">
                  <option value="">-- Sélectionner --</option>
                  <option v-for="p in (processes || [])" :key="p.id" :value="p.id">{{ p.code }} - {{ p.name }}</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Nom *</label>
                <input v-model="formData.name" type="text" class="form-control form-control-sm">
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Description</label>
                <textarea v-model="formData.description" class="form-control form-control-sm" rows="2"></textarea>
              </div>
            </div>

            <!-- FORM EXERCISE -->
            <div v-else-if="currentForm === 'exercise'" class="row g-2">
              <div class="col-6">
                <label class="form-label fw-bold">Code *</label>
                <input v-model="formData.code" type="text" class="form-control form-control-sm">
              </div>
              <div class="col-6">
                <label class="form-label fw-bold">Année *</label>
                <input v-model.number="formData.year" type="number" min="2000" max="2100" class="form-control form-control-sm">
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Nom *</label>
                <input v-model="formData.name" type="text" class="form-control form-control-sm">
              </div>
              <div class="col-6">
                <label class="form-label fw-bold">Date début *</label>
                <input v-model="formData.start_date" type="date" class="form-control form-control-sm">
              </div>
              <div class="col-6">
                <label class="form-label fw-bold">Date fin *</label>
                <input v-model="formData.end_date" type="date" class="form-control form-control-sm">
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Description</label>
                <textarea v-model="formData.description" class="form-control form-control-sm" rows="2"></textarea>
              </div>
            </div>

            <!-- FORM SESSION -->
            <div v-else-if="currentForm === 'session'" class="row g-2">
              <div class="col-6">
                <label class="form-label fw-bold">Code *</label>
                <input v-model="formData.code" type="text" class="form-control form-control-sm">
              </div>
              <div class="col-6">
                <label class="form-label fw-bold">Exercice *</label>
                <select v-model.number="formData.exercise_id" class="form-select form-select-sm">
                  <option value="">-- Sélectionner --</option>
                  <option v-for="ex in (exercises || [])" :key="ex.id" :value="ex.id">{{ ex.code }} - {{ ex.name }}</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label fw-bold">Entité *</label>
                <select v-model.number="formData.entity_id" class="form-select form-select-sm">
                  <option value="">-- Sélectionner --</option>
                  <option v-for="e in (entities || [])" :key="e.id" :value="e.id">{{ e.code_base }} - {{ e.name }}</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Nom *</label>
                <input v-model="formData.name" type="text" class="form-control form-control-sm">
              </div>
              <div class="col-6">
                <label class="form-label fw-bold">Date *</label>
                <input v-model="formData.session_date" type="date" class="form-control form-control-sm">
              </div>
              <div class="col-6">
                <label class="form-label fw-bold">Date début *</label>
                <input v-model="formData.start_date" type="date" class="form-control form-control-sm">
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Date fin *</label>
                <input v-model="formData.end_date" type="date" class="form-control form-control-sm">
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Description</label>
                <textarea v-model="formData.description" class="form-control form-control-sm" rows="2"></textarea>
              </div>
            </div>
          </div>

          <div class="modal-footer bg-light border-top py-2">
            <button @click="closeModal" class="btn btn-secondary btn-sm">❌ Annuler</button>
            <button @click="saveItem" class="btn btn-warning btn-sm" :disabled="loading">
              {{ loading ? '⏳' : '💾' }} {{ editingItem ? 'Modifier' : 'Ajouter' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  frequencies: { type: Array, default: () => [] },
  impacts: { type: Array, default: () => [] },
  matrix: { type: Array, default: () => [] },
  entities: { type: Array, default: () => [] },
  processes: { type: Array, default: () => [] },
  activities: { type: Array, default: () => [] },
  exercises: { type: Array, default: () => [] },
  sessions: { type: Array, default: () => [] },
})

const pageTop = ref(null)
const activeTab = ref('frequency')
const showModal = ref(false)
const currentForm = ref('')
const editingItem = ref(null)
const loading = ref(false)

// ✅ UTILISER LES PROPS DIRECTEMENT - GÉRER LES UNDEFINED
const frequencies = ref(props.frequencies ?? [])
const impacts = ref(props.impacts ?? [])
const matrix = ref(props.matrix ?? [])
const entities = ref(props.entities ?? [])
const processes = ref(props.processes ?? [])
const activities = ref(props.activities ?? [])
const exercises = ref(props.exercises ?? [])
const sessions = ref(props.sessions ?? [])

const formData = ref({})

// 🎨 NOTIFICATION SYSTEM
const notification = ref({
  show: false,
  type: 'success',
  title: '',
  message: '',
  timeout: null
})

// 🎨 Déterminer couleur du texte basée sur luminosité fond
const getTextColor = (bgColor) => {
  if (!bgColor) return '#000'
  
  try {
    let hex = bgColor
    if (!bgColor.startsWith('#')) {
      hex = '#' + bgColor
    }
    
    const rgb = hexToRgb(hex)
    if (!rgb) return '#000'
    
    const brightness = (rgb.r * 299 + rgb.g * 587 + rgb.b * 114) / 1000
    return brightness > 128 ? '#000' : '#fff'
  } catch {
    return '#000'
  }
}

const hexToRgb = (hex) => {
  const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
  return result ? {
    r: parseInt(result[1], 16),
    g: parseInt(result[2], 16),
    b: parseInt(result[3], 16)
  } : null
}

// 🔔 NOTIFICATION METHODS
const showNotification = (type, title, message, duration = 4000) => {
  clearTimeout(notification.value.timeout)
  
  notification.value = {
    show: true,
    type,
    title,
    message
  }
  
  notification.value.timeout = setTimeout(() => {
    closeNotification()
  }, duration)
}

const closeNotification = () => {
  notification.value.show = false
  clearTimeout(notification.value.timeout)
}

// METHODS
const scrollToTop = () => pageTop.value?.scrollIntoView({ behavior: 'smooth' })
const goBack = () => window.history.back()

const openModal = (form) => {
  currentForm.value = form
  editingItem.value = null
  formData.value = { }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  currentForm.value = ''
  editingItem.value = null
  formData.value = {}
}

const editFrequency = (item) => {
  currentForm.value = 'frequency'
  editingItem.value = item
  formData.value = { ...item }
  showModal.value = true
}

const editImpact = (item) => {
  currentForm.value = 'impact'
  editingItem.value = item
  formData.value = { ...item }
  showModal.value = true
}

const editEntity = (item) => {
  currentForm.value = 'entity'
  editingItem.value = item
  formData.value = { ...item }
  showModal.value = true
}

const editProcess = (item) => {
  currentForm.value = 'process'
  editingItem.value = item
  formData.value = { ...item }
  showModal.value = true
}

const editActivity = (item) => {
  currentForm.value = 'activity'
  editingItem.value = item
  formData.value = { ...item }
  showModal.value = true
}

const editExercise = (item) => {
  currentForm.value = 'exercise'
  editingItem.value = item
  formData.value = { ...item }
  showModal.value = true
}

const editSession = (item) => {
  currentForm.value = 'session'
  editingItem.value = item
  formData.value = { ...item }
  showModal.value = true
}

const getMatrixCell = (impact, freq) => {
  return (matrix.value || []).find(m => m.impact_level === impact && m.frequency_level === freq)
}

const saveItem = async () => {
  const data = formData.value
  
  // Validation champs obligatoires
  if (!data.code || (!data.label && !data.name)) {
    showNotification('warning', '⚠️ Champs requis', 'Veuillez remplir les champs obligatoires (*)')
    return
  }

  // Validation couleur HEX si présente
  if (data.color && !isValidHex(data.color)) {
    showNotification('warning', '⚠️ Couleur invalide', 'Veuillez entrer une couleur HEX valide (ex: 28a745)')
    return
  }

  const endpoints = {
    frequency: '/api/settings/audit-frequencies',
    impact: '/api/settings/audit-impacts',
    matrix: '/api/settings/audit-matrix',
    entity: '/api/settings/entities',
    process: '/api/settings/processes',
    activity: '/api/settings/activities',
    exercise: '/api/settings/exercises',
    session: '/api/settings/sessions'
  }

  const endpoint = endpoints[currentForm.value]
  const method = editingItem.value ? 'PUT' : 'POST'
  const url = editingItem.value ? `${endpoint}/${editingItem.value.id}` : endpoint

  // Ajouter # à la couleur HEX si nécessaire
  if (data.color && !data.color.startsWith('#')) {
    data.color = '#' + data.color
  }

  loading.value = true
  try {
    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      },
      body: JSON.stringify(data)
    })

    const result = await response.json()

    if (response.ok) {
      showNotification('success', '✅ Succès', result.message || 'Opération réussie')
      closeModal()
      setTimeout(() => location.reload(), 1500)
    } else {
      showNotification('danger', '❌ Erreur', result.error || result.message || 'Une erreur est survenue')
    }
  } catch (error) {
    console.error('Error:', error)
    showNotification('danger', '❌ Erreur réseau', error.message)
  } finally {
    loading.value = false
  }
}

const deleteItem = async (type, id) => {
  if (!confirm('⚠️ Êtes-vous certain de vouloir supprimer cet élément?')) return

  const endpoints = {
    frequency: '/api/settings/audit-frequencies',
    impact: '/api/settings/audit-impacts',
    matrix: '/api/settings/audit-matrix',
    entity: '/api/settings/entities',
    process: '/api/settings/processes',
    activity: '/api/settings/activities',
    exercise: '/api/settings/exercises',
    session: '/api/settings/sessions'
  }

  try {
    const response = await fetch(`${endpoints[type]}/${id}`, {
      method: 'DELETE',
      headers: { 
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        'Content-Type': 'application/json'
      }
    })
    
    const data = await response.json()
    
    if (response.ok) {
      showNotification('success', '✅ Suppression', data.message || 'Élément supprimé avec succès')
      setTimeout(() => location.reload(), 1500)
    } else {
      showNotification('danger', '❌ Erreur', data.error || 'Erreur lors de la suppression')
    }
  } catch (error) {
    console.error('Error:', error)
    showNotification('danger', '❌ Erreur réseau', error.message)
  }
}

const isValidHex = (hex) => {
  const cleanHex = hex.replace(/^#/, '')
  return /^[a-fA-F0-9]{6}$|^[a-fA-F0-9]{3}$/.test(cleanHex)
}

const getEntityName = (id) => (entities.value || []).find(e => e.id === id)?.code_base || '-'
const getProcessName = (id) => (processes.value || []).find(p => p.id === id)?.code || '-'
const getExerciseName = (id) => (exercises.value || []).find(e => e.id === id)?.code || '-'
const truncate = (text, len) => !text ? '-' : text.length > len ? text.substring(0, len) + '...' : text
const formatDate = (date) => date ? new Date(date).toLocaleDateString('fr-FR') : '-'
const statusBadge = (status) => {
  const badges = {
    draft: 'bg-secondary',
    in_progress: 'bg-primary',
    active: 'bg-info',
    closed: 'bg-success',
    paused: 'bg-warning text-dark',
    planning: 'bg-primary',
    closing: 'bg-warning text-dark',
    completed: 'bg-success',
    cancelled: 'bg-danger',
    pending: 'bg-warning text-dark',
    approved: 'bg-success',
    rejected: 'bg-danger'
  }
  return badges[status] || 'bg-light text-dark'
}
</script>

<style scoped>
.modal {
  position: fixed;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.sticky-header {
  position: sticky;
  top: 0;
  background: white;
  z-index: 100;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  padding: 0.5rem 0;
}

.nav-link {
  cursor: pointer;
  color: #fff;
  border-bottom: 3px solid transparent;
  transition: all 0.2s;
}

.nav-link:hover {
  border-bottom-color: #ffc107;
}

.nav-link.active {
  color: #fff;
  border-bottom-color: #ffc107;
  background: rgba(255,255,255,0.1);
}

.color-preview {
  padding: 0.75rem;
  border-radius: 4px;
  text-align: center;
  font-weight: bold;
  border: 2px solid #dee2e6;
  transition: all 0.2s;
}

/* Animations de notification */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s, transform 0.3s;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

@media (max-width: 576px) {
  .nav-link {
    font-size: 0.75rem;
    padding: 0.4rem 0.5rem !important;
  }
}
</style>