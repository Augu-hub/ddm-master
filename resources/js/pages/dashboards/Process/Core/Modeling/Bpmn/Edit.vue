<template>
  <div class="bpmn-editor-page">
    <Head :title="diagramTitle" />

    <!-- ==================== HEADER ==================== -->
    <div class="header-bar">
      <div class="header-content">
        <div class="header-info">
          <h1 class="title">{{ process.name || 'Éditeur BPMN' }}</h1>
          <div class="header-subinfo">
            <span class="code">{{ process.code }}</span>
            <span class="version">v{{ diagram.version }}</span>
            <span class="activities-count">
              {{ initial_data?.task_links?.length || 0 }} activités disponibles
            </span>
          </div>
        </div>
        
        <div class="header-actions">
          <!-- Indicateur sauvegarde -->
          <div class="save-indicator" :class="saveStatus">
            <i :class="saveIcon"></i>
            <span>{{ saveMessage }}</span>
            <small v-if="lastSaveTime">{{ lastSaveTime }}</small>
          </div>
          
          <!-- Actions rapides -->
          <div class="action-buttons">
            <button @click="toggleProperties" class="btn-action" :title="showProperties ? 'Masquer propriétés' : 'Afficher propriétés'">
              <i :class="showProperties ? 'ti ti-layout-sidebar-right-expand' : 'ti ti-layout-sidebar-right'"></i>
            </button>
            
            <button @click="toggleToolbar" class="btn-action" :title="showToolbar ? 'Masquer outils' : 'Afficher outils'">
              <i :class="showToolbar ? 'ti ti-layout-sidebar-left-expand' : 'ti ti-layout-sidebar-left'"></i>
            </button>
            
            <button @click="zoomFit" class="btn-action" title="Ajuster la vue">
              <i class="ti ti-arrows-minimize"></i>
            </button>
            
            <button @click="saveManual" :disabled="saving" class="btn-save-version">
              <i v-if="!saving" class="ti ti-versions"></i>
              <b-spinner v-else small></b-spinner>
              <span>Nouvelle version</span>
            </button>
            
            <button @click="goBack" class="btn-outline">
              <i class="ti ti-arrow-left"></i> Retour
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ==================== MAIN LAYOUT ==================== -->
    <div class="main-layout">
      <!-- LEFT: TOOLBAR -->
      <div v-if="showToolbar" class="sidebar-left">
        <div class="toolbar-header">
          <h6><i class="ti ti-tools me-2"></i>Éléments BPMN</h6>
          <small>Glissez ou cliquez</small>
        </div>
        
        <div class="toolbar-search">
          <input v-model="toolbarSearch" placeholder="Rechercher un élément..." class="search-input" />
        </div>
        
        <div class="toolbar-content">
          <!-- Événements de départ -->
          <div class="toolbar-group">
            <h6 class="group-label">
              <i class="ti ti-player-play"></i> Démarrage
            </h6>
            <div class="tool-grid">
              <button v-for="event in filteredStartEvents" :key="event.type"
                class="tool-btn"
                @click="createElementInParticipant(event.type, event.name)"
                @dragstart="dragStart($event, event)"
                draggable="true"
                :title="event.description">
                <i :class="`bpmn-icon ${event.icon}`"></i>
                <span class="tool-label">{{ event.name }}</span>
              </button>
            </div>
          </div>
          
          <!-- Tâches -->
          <div class="toolbar-group">
            <h6 class="group-label">
              <i class="ti ti-checkbox"></i> Tâches
            </h6>
            <div class="tool-grid">
              <button v-for="task in filteredTasks" :key="task.type"
                class="tool-btn"
                @click="createElementInParticipant(task.type, task.name)"
                @dragstart="dragStart($event, task)"
                draggable="true"
                :title="task.description">
                <i :class="`bpmn-icon ${task.icon}`"></i>
                <span class="tool-label">{{ task.name }}</span>
              </button>
            </div>
          </div>
          
          <!-- Gateways -->
          <div class="toolbar-group">
            <h6 class="group-label">
              <i class="ti ti-share"></i> Décisions
            </h6>
            <div class="tool-grid">
              <button v-for="gateway in filteredGateways" :key="gateway.type"
                class="tool-btn"
                @click="createElementInParticipant(gateway.type, gateway.name)"
                @dragstart="dragStart($event, gateway)"
                draggable="true"
                :title="gateway.description">
                <i :class="`bpmn-icon ${gateway.icon}`"></i>
                <span class="tool-label">{{ gateway.name }}</span>
              </button>
            </div>
          </div>
          
          <!-- Événements intermédiaires -->
          <div class="toolbar-group">
            <h6 class="group-label">
              <i class="ti ti-bolt"></i> Intermédiaires
            </h6>
            <div class="tool-grid">
              <button v-for="event in filteredIntermediateEvents" :key="event.type"
                class="tool-btn"
                @click="createElementInParticipant(event.type, event.name)"
                @dragstart="dragStart($event, event)"
                draggable="true"
                :title="event.description">
                <i :class="`bpmn-icon ${event.icon}`"></i>
                <span class="tool-label">{{ event.name }}</span>
              </button>
            </div>
          </div>
          
          <!-- Événements de fin -->
          <div class="toolbar-group">
            <h6 class="group-label">
              <i class="ti ti-player-stop"></i> Fin
            </h6>
            <div class="tool-grid">
              <button v-for="event in filteredEndEvents" :key="event.type"
                class="tool-btn"
                @click="createElementInParticipant(event.type, event.name)"
                @dragstart="dragStart($event, event)"
                draggable="true"
                :title="event.description">
                <i :class="`bpmn-icon ${event.icon}`"></i>
                <span class="tool-label">{{ event.name }}</span>
              </button>
            </div>
          </div>
          
          <!-- Autres -->
          <div class="toolbar-group">
            <h6 class="group-label">
              <i class="ti ti-apps"></i> Autres
            </h6>
            <div class="tool-grid">
              <button v-for="other in filteredOtherElements" :key="other.type"
                class="tool-btn"
                @click="createElementInParticipant(other.type, other.name)"
                @dragstart="dragStart($event, other)"
                draggable="true"
                :title="other.description">
                <i :class="`bpmn-icon ${other.icon}`"></i>
                <span class="tool-label">{{ other.name }}</span>
              </button>
            </div>
          </div>
          
          <!-- Bouton pour ajouter un participant -->
          <div class="toolbar-group">
            <h6 class="group-label">
              <i class="ti ti-users"></i> Structure
            </h6>
            <div class="tool-grid">
              <button class="tool-btn" @click="addParticipant" title="Ajouter un participant">
                <i class="bpmn-icon bpmn-icon-participant"></i>
                <span class="tool-label">Participant</span>
              </button>
              <button class="tool-btn" @click="addLane" title="Ajouter un couloir">
                <i class="bpmn-icon bpmn-icon-lane"></i>
                <span class="tool-label">Couloir</span>
              </button>
            </div>
          </div>
        </div>
        
        <div class="toolbar-footer">
          <div class="diagram-info">
            <small>Diagramme #{{ diagram.id }}</small>
            <small>Créé le {{ formatDate(diagram.created_at) }}</small>
          </div>
        </div>
      </div>

      <!-- CENTER: CANVAS -->
      <div class="main-content" :class="{ 'full-width': !showToolbar }">
        <!-- Barre d'outils canvas -->
        <div class="canvas-toolbar">
          <div class="canvas-actions">
            <button @click="zoomIn" class="btn-canvas" title="Zoom +">
              <i class="ti ti-zoom-in"></i>
            </button>
            <button @click="zoomOut" class="btn-canvas" title="Zoom -">
              <i class="ti ti-zoom-out"></i>
            </button>
            <button @click="zoomFit" class="btn-canvas" title="Ajuster">
              <i class="ti ti-maximize"></i>
            </button>
            <button @click="centerView" class="btn-canvas" title="Centrer">
              <i class="ti ti-current-location"></i>
            </button>
            <div class="zoom-level">{{ zoomLevel }}%</div>
          </div>
          
          <div class="canvas-stats">
            <span class="stat">
              <i class="ti ti-shapes"></i> {{ elementCount }} éléments
            </span>
            <span class="stat">
              <i class="ti ti-link"></i> {{ connectionCount }} connexions
            </span>
            <span class="stat">
              <i class="ti ti-palette"></i> {{ coloredElements }} couleurs
            </span>
          </div>
        </div>
        
        <!-- Canvas principal -->
        <div ref="canvas" 
             class="bpmn-canvas" 
             @contextmenu.prevent="handleContextMenu"
             @dragover.prevent="handleDragOver"
             @drop.prevent="handleDrop">
          
          <!-- Overlay de chargement -->
          <div v-if="loadingEditor" class="canvas-loader">
            <div class="loader-content">
              <b-spinner variant="primary"></b-spinner>
              <h5>Chargement du diagramme...</h5>
              <p v-if="initial_data">
                Restauration des {{ initial_data.task_links?.length || 0 }} liens et 
                {{ initial_data.element_configs ? Object.keys(initial_data.element_configs).length : 0 }} configurations
              </p>
            </div>
          </div>
          
          <!-- Indicateur de drag -->
          <div v-if="draggingElement" class="drag-indicator" :style="dragIndicatorStyle">
            <i :class="`bpmn-icon ${draggingElement.icon}`"></i>
            <span>{{ draggingElement.name }}</span>
          </div>
        </div>
        
        <!-- Menu contextuel (clic droit) -->
        <div v-if="showContextMenu" 
             class="context-menu" 
             :style="{ left: contextMenuX + 'px', top: contextMenuY + 'px' }">
          
          <!-- En-tête -->
          <div class="menu-header" v-if="selectedElement">
            <h6>{{ selectedElementName }}</h6>
            <code>{{ selectedElementId }}</code>
          </div>
          
          <!-- Couleurs -->
          <div class="menu-section" v-if="selectedElement && (isTask(selectedElement) || isParticipant(selectedElement))">
            <div class="menu-label">Couleur de remplissage</div>
            <div class="menu-colors">
              <button v-for="color in colorPalette"
                :key="color.name"
                class="color-option"
                :style="{ backgroundColor: color.hex, border: '2px solid ' + (selectedColor === color.hex ? '#667eea' : '#dee2e6') }"
                :title="color.name"
                @click="applyColor(color.hex)">
                <i v-if="selectedColor === color.hex" class="ti ti-check"></i>
              </button>
            </div>
            <div class="custom-color">
              <input type="color" v-model="customColor" @change="applyColor(customColor)" />
              <span>Personnalisé</span>
            </div>
          </div>
          
          <!-- Taille -->
          <div class="menu-section" v-if="selectedElement">
            <div class="menu-label">Taille</div>
            <div class="size-controls">
              <button @click="resizeElement('smaller')" class="size-btn">
                <i class="ti ti-arrow-big-left"></i>
              </button>
              <button @click="resizeElement('larger')" class="size-btn">
                <i class="ti ti-arrow-big-right"></i>
              </button>
              <button @click="resizeElement('reset')" class="size-btn">
                <i class="ti ti-arrow-back-up"></i>
              </button>
            </div>
          </div>
          
          <!-- Actions -->
          <div class="menu-section">
            <button @click="copyElement" class="menu-item">
              <i class="ti ti-copy me-2"></i>Copier
            </button>
            <button @click="duplicateElement" class="menu-item">
              <i class="ti ti-clone me-2"></i>Dupliquer
            </button>
            <button @click="alignElement('left')" class="menu-item">
              <i class="ti ti-align-left me-2"></i>Aligner à gauche
            </button>
            <button @click="alignElement('center')" class="menu-item">
              <i class="ti ti-align-center me-2"></i>Centrer
            </button>
          </div>
          
          <!-- Actions spéciales pour participants -->
          <div v-if="selectedElement && isParticipant(selectedElement)" class="menu-section">
            <button @click="addLaneToParticipant" class="menu-item">
              <i class="ti ti-columns me-2"></i>Ajouter couloir
            </button>
            <button @click="expandParticipant" class="menu-item">
              <i class="ti ti-arrows-maximize me-2"></i>Agrandir
            </button>
          </div>
          
          <!-- Actions dangereuses -->
          <div class="menu-section danger-section">
            <button @click="deleteElement" class="menu-item danger">
              <i class="ti ti-trash me-2"></i>Supprimer
            </button>
            <button @click="resetElement" class="menu-item warning">
              <i class="ti ti-refresh me-2"></i>Réinitialiser
            </button>
          </div>
          
          <!-- Fermeture -->
          <div class="menu-footer">
            <button @click="closeContextMenu" class="menu-item">
              <i class="ti ti-x me-2"></i>Fermer
            </button>
          </div>
        </div>
        
        <!-- Overlay menu contextuel -->
        <div v-if="showContextMenu" @click="closeContextMenu" class="context-overlay"></div>
      </div>

      <!-- RIGHT: PROPERTIES PANEL -->
      <div v-if="showProperties" class="sidebar-right">
        <div class="properties-header">
          <h6><i class="ti ti-settings"></i> Propriétés</h6>
          <button @click="toggleProperties" class="btn-close-panel">
            <i class="ti ti-x"></i>
          </button>
        </div>
        
        <div class="properties-content">
          <!-- Aucun élément sélectionné -->
          <div v-if="!selectedElement" class="empty-props">
            <div class="empty-icon">
              <i class="ti ti-pointer"></i>
            </div>
            <h5>Sélectionnez un élément</h5>
            <p>Cliquez sur un élément du diagramme pour voir et modifier ses propriétés</p>
            <div class="empty-tips">
              <div class="tip">
                <i class="ti ti-arrow-big-right-lines"></i>
                <span>Glissez depuis la barre d'outils</span>
              </div>
              <div class="tip">
                <i class="ti ti-color-swatch"></i>
                <span>Clic droit pour plus d'options</span>
              </div>
              <div class="tip">
                <i class="ti ti-link"></i>
                <span>Liez des activités aux tâches</span>
              </div>
            </div>
          </div>
          
          <!-- Élément sélectionné -->
          <div v-else>
            <!-- En-tête élément -->
            <div class="element-header">
              <div class="element-icon">
                <i :class="selectedElementIcon"></i>
              </div>
              <div class="element-info">
                <h5>{{ selectedElementName }}</h5>
                <code class="element-id">{{ selectedElementId }}</code>
                <div class="element-type">
                  <span class="badge-type">{{ selectedElementType }}</span>
                </div>
              </div>
            </div>
            
            <!-- Navigation propriétés -->
            <div class="props-tabs">
              <button :class="['tab-btn', activeTab === 'general' ? 'active' : '']" 
                      @click="activeTab = 'general'">
                <i class="ti ti-info-circle"></i> Général
              </button>
              <button :class="['tab-btn', activeTab === 'style' ? 'active' : '']" 
                      @click="activeTab = 'style'"
                      v-if="isTask(selectedElement) || isParticipant(selectedElement) || isLane(selectedElement)">
                <i class="ti ti-palette"></i> Style
              </button>
              <button :class="['tab-btn', activeTab === 'activity' ? 'active' : '']" 
                      @click="activeTab = 'activity'"
                      v-if="isTask(selectedElement)">
                <i class="ti ti-link"></i> Activité
              </button>
              <button :class="['tab-btn', activeTab === 'advanced' ? 'active' : '']" 
                      @click="activeTab = 'advanced'">
                <i class="ti ti-settings"></i> Avancé
              </button>
            </div>
            
            <!-- Contenu des onglets -->
            <div class="props-content">
              <!-- Onglet Général -->
              <div v-if="activeTab === 'general'" class="tab-pane">
                <div class="prop-group">
                  <label class="prop-label">
                    <i class="ti ti-forms"></i> Nom
                  </label>
                  <input type="text" 
                         v-model="selectedElementName" 
                         @change="updateElementName"
                         class="prop-input"
                         placeholder="Nom de l'élément" />
                </div>
                
                <div class="prop-group">
                  <label class="prop-label">
                    <i class="ti ti-info-circle"></i> Description
                  </label>
                  <textarea v-model="selectedElementDescription" 
                            @change="updateElementDescription"
                            class="prop-textarea"
                            placeholder="Description optionnelle..."
                            rows="3"></textarea>
                </div>
                
                <div v-if="isParticipant(selectedElement)" class="prop-group">
                  <label class="prop-label">
                    <i class="ti ti-layout-rows"></i> Lanes
                  </label>
                  <div class="lanes-list">
                    <div v-for="lane in getParticipantLanes()" :key="lane.id" class="lane-item">
                      <i class="ti ti-columns"></i>
                      <span>{{ lane.businessObject?.name || 'Lane' }}</span>
                      <button @click="selectLane(lane)" class="btn-small">
                        <i class="ti ti-eye"></i>
                      </button>
                    </div>
                    <button @click="addLaneToSelectedParticipant" class="btn-add-lane">
                      <i class="ti ti-plus"></i> Ajouter un couloir
                    </button>
                  </div>
                </div>
              </div>
              
              <!-- Onglet Style -->
              <div v-if="activeTab === 'style' && (isTask(selectedElement) || isParticipant(selectedElement) || isLane(selectedElement))" class="tab-pane">
                <div class="prop-group">
                  <label class="prop-label">
                    <i class="ti ti-palette"></i> Couleur principale
                  </label>
                  <div class="color-picker">
                    <div class="color-presets">
                      <button v-for="color in colorPalette"
                        :key="color.name"
                        class="color-preset"
                        :style="{ backgroundColor: color.hex, border: '2px solid ' + (selectedColor === color.hex ? '#667eea' : '#dee2e6') }"
                        @click="applyColor(color.hex)"
                        :title="color.name">
                      </button>
                    </div>
                    <div class="color-custom">
                      <input type="color" v-model="selectedColor" @change="applyColor(selectedColor)" />
                      <div class="color-info">
                        <div class="color-preview" :style="{ backgroundColor: selectedColor }"></div>
                        <input type="text" v-model="selectedColor" @change="applyColor(selectedColor)" class="color-hex" />
                      </div>
                    </div>
                  </div>
                </div>
                
                <div class="prop-group">
                  <label class="prop-label">
                    <i class="ti ti-border-style"></i> Bordure
                  </label>
                  <div class="border-controls">
                    <div class="border-width">
                      <label>Épaisseur</label>
                      <input type="range" min="1" max="5" v-model="borderWidth" @change="updateBorder" />
                      <span>{{ borderWidth }}px</span>
                    </div>
                    <div class="border-style">
                      <label>Style</label>
                      <select v-model="borderStyle" @change="updateBorder" class="form-select">
                        <option value="solid">Pleine</option>
                        <option value="dashed">Tiretée</option>
                        <option value="dotted">Pointillée</option>
                      </select>
                    </div>
                  </div>
                </div>
                
                <div class="prop-group" v-if="isTask(selectedElement)">
                  <label class="prop-label">
                    <i class="ti ti-letter-spacing"></i> Texte
                  </label>
                  <div class="text-controls">
                    <div class="text-size">
                      <label>Taille</label>
                      <select v-model="textSize" @change="updateTextStyle" class="form-select">
                        <option value="small">Petit</option>
                        <option value="medium">Moyen</option>
                        <option value="large">Grand</option>
                      </select>
                    </div>
                    <div class="text-color">
                      <label>Couleur</label>
                      <input type="color" v-model="textColor" @change="updateTextStyle" />
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Onglet Activité -->
              <div v-if="activeTab === 'activity' && isTask(selectedElement)" class="tab-pane">
                <div class="activity-section">
                  <div v-if="currentActivityLink" class="activity-linked">
                    <div class="linked-header">
                      <h6><i class="ti ti-link"></i> Activité liée</h6>
                      <button @click="unlinkActivity" class="btn-unlink">
                        <i class="ti ti-unlink"></i>
                      </button>
                    </div>
                    <div class="linked-info">
                      <div class="activity-card">
                        <div class="activity-icon">
                          <i class="ti ti-activity"></i>
                        </div>
                        <div class="activity-details">
                          <h5>{{ currentActivityLink.activity_name }}</h5>
                          <code>{{ currentActivityLink.activity_code }}</code>
                          <div class="activity-meta">
                            <span class="meta">
                              <i class="ti ti-id"></i> #{{ currentActivityLink.activity_id }}
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <div v-else>
                    <div class="activity-search">
                      <label class="prop-label">
                        <i class="ti ti-search"></i> Lier une activité
                      </label>
                      <div class="search-wrapper">
                        <input type="text" 
                               v-model="activitySearch"
                               @focus="showActivitySuggestions = true"
                               @blur="handleActivityBlur"
                               class="search-input"
                               placeholder="Rechercher une activité..." />
                        <i class="ti ti-search search-icon"></i>
                      </div>
                      
                      <!-- Suggestions -->
                      <div v-if="showActivitySuggestions && filteredActivities.length > 0" 
                           class="suggestions-dropdown">
                        <div class="suggestions-header">
                          <small>{{ filteredActivities.length }} activités disponibles</small>
                        </div>
                        <div class="suggestions-list">
                          <div v-for="activity in filteredActivities" 
                               :key="activity.id"
                               class="suggestion-item"
                               @click="selectActivity(activity)">
                            <div class="suggestion-icon">
                              <i class="ti ti-activity"></i>
                            </div>
                            <div class="suggestion-info">
                              <strong>{{ activity.name }}</strong>
                              <code>{{ activity.code }}</code>
                            </div>
                            <div class="suggestion-action">
                              <i class="ti ti-link"></i>
                            </div>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Aucun résultat -->
                      <div v-if="activitySearch && filteredActivities.length === 0" class="no-results">
                        <i class="ti ti-mood-empty"></i>
                        <p>Aucune activité trouvée</p>
                      </div>
                    </div>
                    
                    <!-- Activité sélectionnée (confirmation) -->
                    <div v-if="selectedActivity" class="activity-confirm">
                      <div class="confirm-header">
                        <h6>Confirmer le lien</h6>
                      </div>
                      <div class="confirm-body">
                        <div class="activity-preview">
                          <i class="ti ti-activity"></i>
                          <div>
                            <strong>{{ selectedActivity.name }}</strong>
                            <code>{{ selectedActivity.code }}</code>
                          </div>
                        </div>
                        <div class="confirm-actions">
                          <button @click="linkActivity" class="btn-confirm">
                            <i class="ti ti-check"></i> Lier
                          </button>
                          <button @click="selectedActivity = null" class="btn-cancel">
                            <i class="ti ti-x"></i> Annuler
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Onglet Avancé -->
              <div v-if="activeTab === 'advanced'" class="tab-pane">
                <div class="prop-group">
                  <label class="prop-label">
                    <i class="ti ti-code"></i> ID BPMN
                  </label>
                  <div class="element-id-display">
                    <code>{{ selectedElementId }}</code>
                    <button @click="copyToClipboard(selectedElementId)" class="btn-copy">
                      <i class="ti ti-copy"></i>
                    </button>
                  </div>
                </div>
                
                <div class="prop-group">
                  <label class="prop-label">
                    <i class="ti ti-category"></i> Type BPMN
                  </label>
                  <div class="element-type-display">
                    <code>{{ selectedElementType }}</code>
                  </div>
                </div>
                
                <div class="prop-group" v-if="selectedElement.businessObject">
                  <label class="prop-label">
                    <i class="ti ti-database"></i> Propriétés business
                  </label>
                  <pre class="business-object">{{ JSON.stringify(selectedElement.businessObject, null, 2) }}</pre>
                </div>
                
                <div class="prop-group">
                  <label class="prop-label">
                    <i class="ti ti-adjustments"></i> Actions
                  </label>
                  <div class="advanced-actions">
                    <button @click="exportElement" class="btn-advanced">
                      <i class="ti ti-download"></i> Exporter
                    </button>
                    <button @click="resetElementStyles" class="btn-advanced">
                      <i class="ti ti-refresh"></i> Réinitialiser styles
                    </button>
                    <button @click="showElementInfo" class="btn-advanced">
                      <i class="ti ti-info-circle"></i> Informations
                    </button>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Footer propriétés -->
            <div class="props-footer">
              <div class="element-stats">
                <small>Modifié {{ elementLastModified }}</small>
              </div>
              <button @click="deselectElement" class="btn-deselect">
                <i class="ti ti-x"></i> Désélectionner
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- ==================== MODALS ==================== -->
    
    <!-- Modal nouvelle version -->
    <b-modal v-model="showSaveModal" title="Créer une nouvelle version" hide-footer>
      <div class="save-modal">
        <p>Créer une version sauvegardée du diagramme avec un commentaire.</p>
        
        <div class="form-group">
          <label>Description des changements</label>
          <textarea v-model="versionDescription" 
                    class="form-control" 
                    rows="3"
                    placeholder="Décrivez les modifications apportées..."></textarea>
        </div>
        
        <div class="version-preview">
          <h6>Résumé des changements</h6>
          <ul>
            <li v-if="pendingChanges.elements > 0">
              <i class="ti ti-shapes"></i> {{ pendingChanges.elements }} éléments modifiés
            </li>
            <li v-if="pendingChanges.links > 0">
              <i class="ti ti-link"></i> {{ pendingChanges.links }} liens d'activité
            </li>
            <li v-if="pendingChanges.styles > 0">
              <i class="ti ti-palette"></i> {{ pendingChanges.styles }} styles appliqués
            </li>
          </ul>
        </div>
        
        <div class="modal-actions">
          <button @click="cancelSave" class="btn btn-secondary">
            Annuler
          </button>
          <button @click="confirmSave" class="btn btn-primary" :disabled="saving">
            <span v-if="!saving">Créer la version</span>
            <span v-else>
              <b-spinner small></b-spinner> Création...
            </span>
          </button>
        </div>
      </div>
    </b-modal>
    
    <!-- Modal informations -->
    <b-modal v-model="showInfoModal" title="Informations du diagramme" size="lg">
      <div class="info-modal">
        <div class="info-grid">
          <div class="info-card">
            <h6><i class="ti ti-database"></i> Stockage</h6>
            <ul>
              <li>Diagramme ID: <code>{{ diagram.id }}</code></li>
              <li>Version: <strong>v{{ diagram.version }}</strong></li>
              <li>Créé le: {{ formatDate(diagram.created_at) }}</li>
              <li>Dernière sauvegarde: {{ lastSaveTime || 'Jamais' }}</li>
            </ul>
          </div>
          
          <div class="info-card">
            <h6><i class="ti ti-chart-bar"></i> Statistiques</h6>
            <ul>
              <li>Éléments totaux: {{ elementCount }}</li>
              <li>Tâches liées: {{ linkedTasksCount }}</li>
              <li>Connexions: {{ connectionCount }}</li>
              <li>Taille XML: {{ xmlSize }} Ko</li>
              <li>Participants: {{ participantsCount }}</li>
              <li>Couloirs: {{ lanesCount }}</li>
            </ul>
          </div>
        </div>
        
        <div class="info-actions">
          <button @click="exportDiagram" class="btn btn-outline-primary">
            <i class="ti ti-download"></i> Exporter BPMN
          </button>
          <button @click="showVersions" class="btn btn-outline-secondary">
            <i class="ti ti-history"></i> Voir l'historique
          </button>
        </div>
      </div>
    </b-modal>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed, markRaw } from 'vue'
import { router } from '@inertiajs/vue3'
import { Head } from '@inertiajs/vue3'
import axios from 'axios'

// 🔥 IMPORTS BPMN-JS
import BpmnModeler from 'bpmn-js/lib/Modeler'
import 'bpmn-js/dist/assets/diagram-js.css'
import 'bpmn-js/dist/assets/bpmn-font/css/bpmn.css'
import 'bpmn-js/dist/assets/bpmn-font/css/bpmn-codes.css'
import 'bpmn-js/dist/assets/bpmn-font/css/bpmn-embedded.css'

const props = defineProps({
  process: {
    type: Object,
    required: true,
    default: () => ({ id: null, name: '', code: '' })
  },
  diagram: {
    type: Object,
    required: true,
    default: () => ({ id: null, version: 1, created_at: null })
  },
  initial_data: {
    type: Object,
    required: true,
    default: () => ({
      bpmn_xml: '',
      task_links: [],
      sequence_flows: [],
      element_configs: {}
    })
  },
  availableActivities: {
    type: Array,
    default: () => []
  }
})

// 🔥 RÉFÉRENCES
const canvas = ref(null)
let modeler = null
const loadingEditor = ref(true)
const saving = ref(false)
const saveStatus = ref('idle')
const lastSaveTime = ref(null)

// 🔥 ÉLÉMENTS ET SÉLECTION
const selectedElement = ref(null)
const selectedElementId = ref('')
const selectedElementName = ref('')
const selectedElementType = ref('')
const selectedElementDescription = ref('')
const selectedElementIcon = ref('')
const selectedColor = ref('#3498DB')
const customColor = ref('#3498DB')
const borderWidth = ref(2)
const borderStyle = ref('solid')
const textSize = ref('medium')
const textColor = ref('#000000')
const elementLastModified = ref('à l\'instant')

// 🔥 PARTICIPANTS ET COULOIRS
const mainParticipant = ref(null)
const participants = ref([])

// 🔥 ACTIVITÉS
const currentActivityLink = ref(null)
const selectedActivity = ref(null)
const activitySearch = ref('')
const showActivitySuggestions = ref(false)

// 🔥 UI ET ÉTATS
const showToolbar = ref(true)
const showProperties = ref(true)
const activeTab = ref('general')
const showContextMenu = ref(false)
const contextMenuX = ref(0)
const contextMenuY = ref(0)
const showSaveModal = ref(false)
const showInfoModal = ref(false)

// 🔥 DRAG AND DROP
const draggingElement = ref(null)
const dragIndicatorStyle = ref({ left: '0px', top: '0px' })

// 🔥 RECHERCHE ET FILTRES
const toolbarSearch = ref('')
const zoomLevel = ref(100)

// 🔥 PENDING CHANGES
const versionDescription = ref('')
const pendingChanges = ref({
  elements: 0,
  links: 0,
  styles: 0
})

// 🔥 DONNÉES
const taskLinks = ref({})
const elementConfigs = ref({})
const sequenceFlows = ref([])
const colorPalette = [
  { name: 'Bleu principal', hex: '#3498DB' },
  { name: 'Vert succès', hex: '#27AE60' },
  { name: 'Rouge erreur', hex: '#E74C3C' },
  { name: 'Orange attention', hex: '#F39C12' },
  { name: 'Violet secondaire', hex: '#9B59B6' },
  { name: 'Turquoise info', hex: '#1ABC9C' },
  { name: 'Bleu nuit', hex: '#2C3E50' },
  { name: 'Gris neutre', hex: '#95A5A6' },
  { name: 'Rose', hex: '#E91E63' },
  { name: 'Jaune', hex: '#F1C40F' }
]

// 🔥 ÉLÉMENTS BPMN
const allBpmnElements = {
  startEvents: [
    { type: 'bpmn:StartEvent', name: 'Début', icon: 'bpmn-icon-start-event-none', description: 'Événement de démarrage simple' },
    { type: 'bpmn:StartEvent', name: 'Message', icon: 'bpmn-icon-start-event-message', description: 'Démarrage par message' },
    { type: 'bpmn:StartEvent', name: 'Minuteur', icon: 'bpmn-icon-start-event-timer', description: 'Démarrage par minuteur' },
    { type: 'bpmn:StartEvent', name: 'Condition', icon: 'bpmn-icon-start-event-conditional', description: 'Démarrage conditionnel' },
  ],
  
  tasks: [
    { type: 'bpmn:Task', name: 'Tâche', icon: 'bpmn-icon-task', description: 'Tâche générique' },
    { type: 'bpmn:UserTask', name: 'Utilisateur', icon: 'bpmn-icon-user-task', description: 'Tâche utilisateur' },
    { type: 'bpmn:ServiceTask', name: 'Service', icon: 'bpmn-icon-service-task', description: 'Tâche de service' },
    { type: 'bpmn:ScriptTask', name: 'Script', icon: 'bpmn-icon-script-task', description: 'Tâche de script' },
    { type: 'bpmn:BusinessRuleTask', name: 'Règle métier', icon: 'bpmn-icon-business-rule-task', description: 'Tâche de règle métier' },
  ],
  
  gateways: [
    { type: 'bpmn:ExclusiveGateway', name: 'Exclusif', icon: 'bpmn-icon-gateway-xor', description: 'Gateway exclusif (XOR)' },
    { type: 'bpmn:ParallelGateway', name: 'Parallèle', icon: 'bpmn-icon-gateway-parallel', description: 'Gateway parallèle (AND)' },
    { type: 'bpmn:InclusiveGateway', name: 'Inclusif', icon: 'bpmn-icon-gateway-or', description: 'Gateway inclusif (OR)' },
  ],
  
  intermediateEvents: [
    { type: 'bpmn:IntermediateThrowEvent', name: 'Lancer message', icon: 'bpmn-icon-intermediate-event-throw-message', description: 'Envoyer un message' },
    { type: 'bpmn:IntermediateCatchEvent', name: 'Recevoir message', icon: 'bpmn-icon-intermediate-event-catch-message', description: 'Recevoir un message' },
    { type: 'bpmn:IntermediateThrowEvent', name: 'Lancer signal', icon: 'bpmn-icon-intermediate-event-throw-signal', description: 'Envoyer un signal' },
    { type: 'bpmn:IntermediateCatchEvent', name: 'Recevoir signal', icon: 'bpmn-icon-intermediate-event-catch-signal', description: 'Recevoir un signal' },
  ],
  
  endEvents: [
    { type: 'bpmn:EndEvent', name: 'Fin', icon: 'bpmn-icon-end-event-none', description: 'Événement de fin simple' },
    { type: 'bpmn:EndEvent', name: 'Message', icon: 'bpmn-icon-end-event-message', description: 'Fin avec message' },
    { type: 'bpmn:EndEvent', name: 'Signal', icon: 'bpmn-icon-end-event-signal', description: 'Fin avec signal' },
    { type: 'bpmn:EndEvent', name: 'Erreur', icon: 'bpmn-icon-end-event-error', description: 'Fin avec erreur' },
  ],
  
  otherElements: [
    { type: 'bpmn:DataObjectReference', name: 'Objet données', icon: 'bpmn-icon-data-object', description: 'Référence d\'objet de données' },
    { type: 'bpmn:TextAnnotation', name: 'Annotation', icon: 'bpmn-icon-text-annotation', description: 'Annotation texte' },
    { type: 'bpmn:Group', name: 'Groupe', icon: 'bpmn-icon-group', description: 'Groupe d\'éléments' },
  ]
}

// 🔥 COMPUTED PROPERTIES
const diagramTitle = computed(() => {
  return props.process.name 
    ? `BPMN - ${props.process.name} (v${props.diagram.version})`
    : 'Éditeur BPMN'
})

const saveMessage = computed(() => {
  switch (saveStatus.value) {
    case 'saving': return 'Sauvegarde...'
    case 'saved': return 'Sauvegardé'
    case 'error': return 'Erreur'
    default: return '●'
  }
})

const saveIcon = computed(() => {
  switch (saveStatus.value) {
    case 'saving': return 'ti ti-loader animate-spin'
    case 'saved': return 'ti ti-check'
    case 'error': return 'ti ti-alert-circle'
    default: return 'ti ti-circle-dotted'
  }
})

const filteredStartEvents = computed(() => 
  filterElements(allBpmnElements.startEvents)
)

const filteredTasks = computed(() => 
  filterElements(allBpmnElements.tasks)
)

const filteredGateways = computed(() => 
  filterElements(allBpmnElements.gateways)
)

const filteredIntermediateEvents = computed(() => 
  filterElements(allBpmnElements.intermediateEvents)
)

const filteredEndEvents = computed(() => 
  filterElements(allBpmnElements.endEvents)
)

const filteredOtherElements = computed(() => 
  filterElements(allBpmnElements.otherElements)
)

const filteredActivities = computed(() => {
  if (!props.availableActivities || !Array.isArray(props.availableActivities)) {
    return []
  }
  
  if (!activitySearch.value) return props.availableActivities.slice(0, 10)
  
  const searchTerm = activitySearch.value.toLowerCase()
  return props.availableActivities.filter(activity => 
    (activity.name && activity.name.toLowerCase().includes(searchTerm)) ||
    (activity.code && activity.code.toLowerCase().includes(searchTerm))
  ).slice(0, 10)
})

const elementCount = computed(() => {
  if (!modeler) return 0
  const registry = modeler.get('elementRegistry')
  return registry ? registry.getAll().filter(el => !el.type.includes('di:')).length : 0
})

const connectionCount = computed(() => {
  if (!modeler) return 0
  const registry = modeler.get('elementRegistry')
  return registry ? registry.getAll().filter(el => el.type === 'bpmn:SequenceFlow').length : 0
})

const coloredElements = computed(() => {
  return Object.keys(taskLinks.value).filter(id => taskLinks.value[id]?.color_hex).length
})

const linkedTasksCount = computed(() => {
  return Object.keys(taskLinks.value).length
})

const xmlSize = computed(() => {
  if (!props.initial_data?.bpmn_xml) return '0'
  return (props.initial_data.bpmn_xml.length / 1024).toFixed(2)
})

const participantsCount = computed(() => {
  if (!modeler) return 0
  const registry = modeler.get('elementRegistry')
  return registry ? registry.getAll().filter(el => el.type === 'bpmn:Participant').length : 0
})

const lanesCount = computed(() => {
  if (!modeler) return 0
  const registry = modeler.get('elementRegistry')
  return registry ? registry.getAll().filter(el => el.type === 'bpmn:Lane').length : 0
})

// 🔥 MÉTHODES UTILITAIRES
function filterElements(elements) {
  if (!toolbarSearch.value) return elements
  const term = toolbarSearch.value.toLowerCase()
  return elements.filter(el => 
    (el.name && el.name.toLowerCase().includes(term)) ||
    (el.description && el.description.toLowerCase().includes(term)) ||
    (el.type && el.type.toLowerCase().includes(term))
  )
}

function formatDate(dateString) {
  if (!dateString) return ''
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    })
  } catch {
    return dateString
  }
}

function isTask(element) {
  if (!element) return false
  const taskTypes = [
    'bpmn:Task', 'bpmn:UserTask', 'bpmn:ServiceTask',
    'bpmn:ScriptTask', 'bpmn:ManualTask', 'bpmn:BusinessRuleTask',
    'bpmn:SendTask', 'bpmn:ReceiveTask', 'bpmn:CallActivity'
  ]
  return taskTypes.includes(element.type)
}

function isParticipant(element) {
  return element && element.type === 'bpmn:Participant'
}

function isLane(element) {
  return element && element.type === 'bpmn:Lane'
}

function getParticipantForElement(element) {
  if (!modeler || !element) return null
  
  const elementRegistry = modeler.get('elementRegistry')
  const participants = elementRegistry.getAll().filter(el => el.type === 'bpmn:Participant')
  
  // Trouver le participant qui contient cet élément
  for (const participant of participants) {
    if (participant.id === element.parent?.id || 
        (participant.businessObject?.processRef && 
         element.businessObject?.$parent === participant.businessObject.processRef)) {
      return participant
    }
  }
  
  return mainParticipant.value
}

// 🔥 MÉTHODES BPMN
async function initializeModeler() {
  if (!canvas.value) return
  
  try {
    loadingEditor.value = true
    
    const initialData = props.initial_data || {
      bpmn_xml: '',
      task_links: [],
      sequence_flows: [],
      element_configs: {}
    }
    
    modeler = new BpmnModeler({
      container: canvas.value,
      keyboard: { bindTo: window }
    })
    
    const xml = initialData.bpmn_xml || getDefaultXmlWithParticipant()
    await modeler.importXML(xml)
    
    // Trouver le participant principal
    const elementRegistry = modeler.get('elementRegistry')
    const participants = elementRegistry.getAll().filter(el => el.type === 'bpmn:Participant')
    
    if (participants.length > 0) {
      mainParticipant.value = participants[0]
    } else {
      // Créer un participant par défaut
      await createDefaultParticipant()
    }
    
    modeler.get('canvas').zoom('fit-viewport')
    await restoreDiagramState()
    setupModelerEvents()
    
    loadingEditor.value = false
    
  } catch (error) {
    console.error('Erreur initialisation BPMN:', error)
    loadingEditor.value = false
  }
}

async function createDefaultParticipant() {
  if (!modeler) return
  
  const modeling = modeler.get('modeling')
  const canvas = modeler.get('canvas')
  const elementFactory = modeler.get('elementFactory')
  
  // Créer un participant
  const participant = elementFactory.createShape({ type: 'bpmn:Participant' })
  const participantShape = modeling.createShape(
    participant,
    { x: 100, y: 100, width: 600, height: 400 },
    canvas.getRootElement()
  )
  
  modeling.updateProperties(participantShape, { 
    name: 'Participant Principal' 
  })
  
  // Appliquer une bordure visible
  modeling.setColor(participantShape, {
    fill: '#ffffff',
    stroke: '#2C3E50',
    strokeWidth: 2
  })
  
  mainParticipant.value = participantShape
}

async function restoreDiagramState() {
  if (!modeler) return
  
  try {
    const modeling = modeler.get('modeling')
    const elementRegistry = modeler.get('elementRegistry')
    
    const taskLinksArray = props.initial_data?.task_links || []
    
    for (const link of taskLinksArray) {
      const element = elementRegistry.get(link.element_id)
      if (element && isTask(element)) {
        modeling.setColor(element, { 
          fill: link.color_hex || '#3498DB',
          stroke: link.color_hex || '#3498DB',
          strokeWidth: 2
        })
        
        taskLinks.value[link.element_id] = {
          ...link,
          element_type: element.type
        }
      }
    }
    
    // Appliquer des bordures visibles à tous les éléments
    const allElements = elementRegistry.getAll()
    for (const element of allElements) {
      if (element.type === 'bpmn:Participant' || element.type === 'bpmn:Lane') {
        modeling.setColor(element, {
          fill: element.type === 'bpmn:Participant' ? '#ffffff' : '#f8f9fa',
          stroke: '#2C3E50',
          strokeWidth: 2
        })
      } else if (isTask(element)) {
        modeling.setColor(element, {
          fill: '#ffffff',
          stroke: '#3498DB',
          strokeWidth: 2
        })
      } else if (element.type.includes('Event')) {
        modeling.setColor(element, {
          fill: '#ffffff',
          stroke: '#27AE60',
          strokeWidth: 2
        })
      } else if (element.type.includes('Gateway')) {
        modeling.setColor(element, {
          fill: '#ffffff',
          stroke: '#F39C12',
          strokeWidth: 2
        })
      }
    }
    
    if (props.initial_data?.element_configs) {
      elementConfigs.value = { ...props.initial_data.element_configs }
    }
    
    if (props.initial_data?.sequence_flows) {
      sequenceFlows.value = [...props.initial_data.sequence_flows]
    }
    
  } catch (error) {
    console.error('Erreur restauration état:', error)
  }
}

function setupModelerEvents() {
  if (!modeler) return
  
  const eventBus = modeler.get('eventBus')
  
  eventBus.on('element.click', (event) => {
    selectedElement.value = markRaw(event.element)
    updateSelectedElementInfo(event.element)
    closeContextMenu()
  })
  
  eventBus.on('shape.added', handleDiagramChange)
  eventBus.on('shape.removed', handleDiagramChange)
  eventBus.on('connection.added', handleDiagramChange)
  eventBus.on('connection.removed', handleDiagramChange)
  eventBus.on('element.changed', handleDiagramChange)
  
  eventBus.on('canvas.viewbox.changed', (event) => {
    zoomLevel.value = Math.round(event.scale * 100)
  })
}

function updateSelectedElementInfo(element) {
  if (!element) return
  
  selectedElementId.value = element.id
  selectedElementName.value = element.businessObject?.name || ''
  selectedElementType.value = element.type
  selectedElementIcon.value = getElementIcon(element)
  
  currentActivityLink.value = taskLinks.value[element.id] || null
  
  if (element.di && element.di.fill) {
    selectedColor.value = element.di.fill
  }
}

function getElementIcon(element) {
  if (!element) return 'ti ti-question-mark'
  
  const type = element.type
  if (type.includes('StartEvent')) return 'bpmn-icon-start-event-none'
  if (type.includes('EndEvent')) return 'bpmn-icon-end-event-none'
  if (type.includes('UserTask')) return 'bpmn-icon-user-task'
  if (type.includes('ServiceTask')) return 'bpmn-icon-service-task'
  if (type.includes('ScriptTask')) return 'bpmn-icon-script-task'
  if (type.includes('Gateway')) return 'bpmn-icon-gateway-xor'
  if (type.includes('Intermediate')) return 'bpmn-icon-intermediate-event-none'
  if (type === 'bpmn:Participant') return 'bpmn-icon-participant'
  if (type === 'bpmn:Lane') return 'bpmn-icon-lane'
  
  return 'ti ti-shape'
}

function createElementInParticipant(type, name) {
  if (!modeler || !mainParticipant.value) return
  
  const modeling = modeler.get('modeling')
  const canvas = modeler.get('canvas')
  const participantBounds = canvas.getGraphics(mainParticipant.value)
  
  if (!participantBounds) return
  
  // Calculer une position à l'intérieur du participant
  const padding = 50
  const x = participantBounds.x + padding + (Math.random() * (participantBounds.width - 100))
  const y = participantBounds.y + padding + (Math.random() * (participantBounds.height - 100))
  
  // Créer l'élément à l'intérieur du participant
  const shape = modeling.createShape({ type }, { x, y }, mainParticipant.value)
  
  if (name) {
    modeling.updateProperties(shape, { name })
  }
  
  // Appliquer une bordure visible
  if (isTask(shape)) {
    modeling.setColor(shape, {
      fill: '#ffffff',
      stroke: '#3498DB',
      strokeWidth: 2
    })
  } else if (type.includes('Event')) {
    modeling.setColor(shape, {
      fill: '#ffffff',
      stroke: '#27AE60',
      strokeWidth: 2
    })
  } else if (type.includes('Gateway')) {
    modeling.setColor(shape, {
      fill: '#ffffff',
      stroke: '#F39C12',
      strokeWidth: 2
    })
  }
  
  selectedElement.value = markRaw(shape)
  updateSelectedElementInfo(shape)
  handleDiagramChange()
}

function addParticipant() {
  if (!modeler) return
  
  const modeling = modeler.get('modeling')
  const canvas = modeler.get('canvas')
  const elementFactory = modeler.get('elementFactory')
  
  // Créer un nouveau participant
  const participant = elementFactory.createShape({ type: 'bpmn:Participant' })
  
  // Positionner à droite du participant principal
  const participantBounds = canvas.getGraphics(mainParticipant.value)
  const x = participantBounds ? participantBounds.x + participantBounds.width + 50 : 100
  const y = participantBounds ? participantBounds.y : 100
  
  const participantShape = modeling.createShape(
    participant,
    { x, y, width: 400, height: 300 },
    canvas.getRootElement()
  )
  
  modeling.updateProperties(participantShape, { 
    name: 'Nouveau Participant' 
  })
  
  // Appliquer une bordure visible
  modeling.setColor(participantShape, {
    fill: '#ffffff',
    stroke: '#2C3E50',
    strokeWidth: 2
  })
  
  selectedElement.value = participantShape
  updateSelectedElementInfo(participantShape)
  handleDiagramChange()
}

function addLane() {
  if (!modeler || !mainParticipant.value) return
  
  const modeling = modeler.get('modeling')
  const elementFactory = modeler.get('elementFactory')
  
  // Créer un couloir dans le participant principal
  const lane = elementFactory.createShape({ type: 'bpmn:Lane' })
  const laneShape = modeling.createShape(
    lane,
    { x: 0, y: 0, width: 400, height: 100 },
    mainParticipant.value
  )
  
  modeling.updateProperties(laneShape, { 
    name: 'Nouveau Couloir' 
  })
  
  // Appliquer une bordure visible
  modeling.setColor(laneShape, {
    fill: '#f8f9fa',
    stroke: '#2C3E50',
    strokeWidth: 2
  })
  
  selectedElement.value = laneShape
  updateSelectedElementInfo(laneShape)
  handleDiagramChange()
}

function addLaneToParticipant() {
  if (!selectedElement.value || !isParticipant(selectedElement.value)) return
  addLane()
}

function addLaneToSelectedParticipant() {
  addLaneToParticipant()
}

function getParticipantLanes() {
  if (!modeler || !selectedElement.value || !isParticipant(selectedElement.value)) return []
  
  const elementRegistry = modeler.get('elementRegistry')
  const allElements = elementRegistry.getAll()
  
  return allElements.filter(element => 
    element.type === 'bpmn:Lane' && 
    element.parent?.id === selectedElement.value.id
  )
}

function selectLane(lane) {
  selectedElement.value = markRaw(lane)
  updateSelectedElementInfo(lane)
}

function expandParticipant() {
  if (!selectedElement.value || !isParticipant(selectedElement.value) || !modeler) return
  
  const modeling = modeler.get('modeling')
  const graphics = modeler.get('canvas').getGraphics(selectedElement.value)
  
  if (!graphics) return
  
  modeling.resizeShape(selectedElement.value, {
    x: graphics.x,
    y: graphics.y,
    width: graphics.width * 1.2,
    height: graphics.height * 1.2
  })
  
  pendingChanges.value.styles++
  handleDiagramChange()
}

function applyColor(color) {
  if (!selectedElement.value || !modeler) return
  
  selectedColor.value = color
  const modeling = modeler.get('modeling')
  
  if (isTask(selectedElement.value) || isParticipant(selectedElement.value) || isLane(selectedElement.value)) {
    modeling.setColor(selectedElement.value, { 
      fill: color,
      stroke: color,
      strokeWidth: borderWidth.value
    })
  }
  
  if (taskLinks.value[selectedElement.value.id]) {
    taskLinks.value[selectedElement.value.id].color_hex = color
  }
  
  pendingChanges.value.styles++
  handleDiagramChange()
}

function updateBorder() {
  if (!selectedElement.value || !modeler) return
  
  const modeling = modeler.get('modeling')
  modeling.setColor(selectedElement.value, {
    strokeWidth: borderWidth.value
  })
  
  pendingChanges.value.styles++
  handleDiagramChange()
}

function updateTextStyle() {
  if (!selectedElement.value || !modeler || !isTask(selectedElement.value)) return
  
  const modeling = modeler.get('modeling')
  modeling.updateProperties(selectedElement.value, { 
    di: {
      ...selectedElement.value.di,
      fontSize: textSize.value === 'small' ? '10' : textSize.value === 'medium' ? '12' : '14',
      fill: textColor.value
    }
  })
  
  pendingChanges.value.styles++
  handleDiagramChange()
}

function selectActivity(activity) {
  selectedActivity.value = activity
  activitySearch.value = activity.name
  showActivitySuggestions.value = false
}

function linkActivity() {
  if (!selectedElement.value || !selectedActivity.value || !modeler) return
  
  const modeling = modeler.get('modeling')
  
  modeling.updateProperties(selectedElement.value, { 
    name: selectedActivity.value.name 
  })
  
  taskLinks.value[selectedElement.value.id] = {
    element_id: selectedElement.value.id,
    element_name: selectedActivity.value.name,
    element_type: selectedElement.value.type,
    color_hex: selectedColor.value,
    activity_id: selectedActivity.value.id,
    activity_name: selectedActivity.value.name,
    activity_code: selectedActivity.value.code
  }
  
  currentActivityLink.value = taskLinks.value[selectedElement.value.id]
  selectedActivity.value = null
  activitySearch.value = ''
  
  pendingChanges.value.links++
  handleDiagramChange()
}

function unlinkActivity() {
  if (!selectedElement.value) return
  
  delete taskLinks.value[selectedElement.value.id]
  currentActivityLink.value = null
  
  const modeling = modeler.get('modeling')
  modeling.updateProperties(selectedElement.value, { name: '' })
  
  pendingChanges.value.links++
  handleDiagramChange()
}

function handleActivityBlur() {
  setTimeout(() => {
    showActivitySuggestions.value = false
  }, 200)
}

function dragStart(event, element) {
  draggingElement.value = element
  event.dataTransfer.setData('text/plain', JSON.stringify(element))
}

function handleDragOver(event) {
  if (!draggingElement.value) return
  
  const rect = canvas.value.getBoundingClientRect()
  dragIndicatorStyle.value = {
    left: (event.clientX - rect.left - 40) + 'px',
    top: (event.clientY - rect.top - 40) + 'px',
    display: 'block'
  }
}

function handleDrop(event) {
  if (!draggingElement.value) return
  
  const rect = canvas.value.getBoundingClientRect()
  const x = event.clientX - rect.left
  const y = event.clientY - rect.top
  
  createElementAtPositionInParticipant(draggingElement.value.type, draggingElement.value.name, x, y)
  draggingElement.value = null
  dragIndicatorStyle.value = { display: 'none' }
}

function createElementAtPositionInParticipant(type, name, x, y) {
  if (!modeler || !mainParticipant.value) return
  
  const modeling = modeler.get('modeling')
  const canvas = modeler.get('canvas')
  const participantBounds = canvas.getGraphics(mainParticipant.value)
  
  if (!participantBounds) return
  
  // Vérifier que la position est dans le participant
  if (x < participantBounds.x || x > participantBounds.x + participantBounds.width ||
      y < participantBounds.y || y > participantBounds.y + participantBounds.height) {
    // Ajuster à l'intérieur du participant
    x = Math.max(participantBounds.x + 20, Math.min(x, participantBounds.x + participantBounds.width - 100))
    y = Math.max(participantBounds.y + 20, Math.min(y, participantBounds.y + participantBounds.height - 100))
  }
  
  const shape = modeling.createShape({ type }, { x, y }, mainParticipant.value)
  
  if (name) {
    modeling.updateProperties(shape, { name })
  }
  
  // Appliquer une bordure visible
  if (isTask({ type })) {
    modeling.setColor(shape, {
      fill: '#ffffff',
      stroke: '#3498DB',
      strokeWidth: 2
    })
  } else if (type.includes('Event')) {
    modeling.setColor(shape, {
      fill: '#ffffff',
      stroke: '#27AE60',
      strokeWidth: 2
    })
  } else if (type.includes('Gateway')) {
    modeling.setColor(shape, {
      fill: '#ffffff',
      stroke: '#F39C12',
      strokeWidth: 2
    })
  }
  
  selectedElement.value = markRaw(shape)
  updateSelectedElementInfo(shape)
  handleDiagramChange()
}

function handleContextMenu(event) {
  if (!modeler) return
  
  const rect = canvas.value.getBoundingClientRect()
  contextMenuX.value = event.clientX - rect.left
  contextMenuY.value = event.clientY - rect.top
  
  const elementRegistry = modeler.get('elementRegistry')
  const elements = elementRegistry.getAll()
  
  const clickedElement = elements.find(el => {
    const elBounds = modeler.get('canvas').getGraphics(el)
    if (!elBounds) return false
    
    return event.clientX >= elBounds.x + rect.left &&
           event.clientX <= elBounds.x + rect.left + elBounds.width &&
           event.clientY >= elBounds.y + rect.top &&
           event.clientY <= elBounds.y + rect.top + elBounds.height
  })
  
  if (clickedElement) {
    selectedElement.value = markRaw(clickedElement)
    updateSelectedElementInfo(clickedElement)
  }
  
  showContextMenu.value = true
}

function closeContextMenu() {
  showContextMenu.value = false
}

function deleteElement() {
  if (!selectedElement.value) return
  
  const modeling = modeler.get('modeling')
  modeling.removeShape(selectedElement.value)
  
  if (taskLinks.value[selectedElement.value.id]) {
    delete taskLinks.value[selectedElement.value.id]
  }
  
  selectedElement.value = null
  closeContextMenu()
  
  pendingChanges.value.elements++
  handleDiagramChange()
}

function resizeElement(action) {
  if (!selectedElement.value || !modeler) return
  
  const modeling = modeler.get('modeling')
  const graphics = modeler.get('canvas').getGraphics(selectedElement.value)
  
  if (!graphics) return
  
  const newWidth = action === 'larger' ? graphics.width * 1.2 : 
                   action === 'smaller' ? graphics.width * 0.8 : 
                   selectedElement.value.type === 'bpmn:Task' ? 100 : 
                   selectedElement.value.type.includes('Event') ? 36 : 50
  const newHeight = action === 'larger' ? graphics.height * 1.2 : 
                    action === 'smaller' ? graphics.height * 0.8 : 
                    selectedElement.value.type === 'bpmn:Task' ? 80 : 
                    selectedElement.value.type.includes('Event') ? 36 : 50
  
  modeling.resizeShape(selectedElement.value, {
    x: graphics.x,
    y: graphics.y,
    width: newWidth,
    height: newHeight
  })
  
  pendingChanges.value.styles++
  handleDiagramChange()
}

function duplicateElement() {
  if (!selectedElement.value || !modeler) return
  
  const modeling = modeler.get('modeling')
  const canvas = modeler.get('canvas')
  const graphics = canvas.getGraphics(selectedElement.value)
  
  const newElement = modeling.copyShape(
    selectedElement.value,
    { x: graphics.x + 50, y: graphics.y + 50 },
    selectedElement.value.parent || canvas.getRootElement()
  )
  
  selectedElement.value = newElement
  updateSelectedElementInfo(newElement)
  closeContextMenu()
  
  pendingChanges.value.elements++
  handleDiagramChange()
}

function alignElement(direction) {
  if (!selectedElement.value || !modeler) return
  
  const modeling = modeler.get('modeling')
  const canvas = modeler.get('canvas')
  const participantBounds = canvas.getGraphics(mainParticipant.value)
  
  if (!participantBounds) return
  
  let newX = selectedElement.value.x
  if (direction === 'left') {
    newX = participantBounds.x + 20
  } else if (direction === 'center') {
    newX = participantBounds.x + (participantBounds.width / 2) - (selectedElement.value.width / 2)
  }
  
  modeling.moveShape(selectedElement.value, { x: newX, y: selectedElement.value.y })
  
  pendingChanges.value.elements++
  handleDiagramChange()
}

function copyElement() {
  if (!selectedElement.value) return
  
  navigator.clipboard.writeText(JSON.stringify({
    type: selectedElement.value.type,
    name: selectedElementName.value,
    id: selectedElementId.value
  }))
  
  closeContextMenu()
}

function resetElement() {
  if (!selectedElement.value || !modeler) return
  
  const modeling = modeler.get('modeling')
  
  if (isTask(selectedElement.value)) {
    modeling.setColor(selectedElement.value, { 
      fill: '#ffffff',
      stroke: '#3498DB',
      strokeWidth: 2
    })
  } else if (isParticipant(selectedElement.value)) {
    modeling.setColor(selectedElement.value, {
      fill: '#ffffff',
      stroke: '#2C3E50',
      strokeWidth: 2
    })
  } else if (isLane(selectedElement.value)) {
    modeling.setColor(selectedElement.value, {
      fill: '#f8f9fa',
      stroke: '#2C3E50',
      strokeWidth: 2
    })
  } else if (selectedElement.value.type.includes('Event')) {
    modeling.setColor(selectedElement.value, {
      fill: '#ffffff',
      stroke: '#27AE60',
      strokeWidth: 2
    })
  } else if (selectedElement.value.type.includes('Gateway')) {
    modeling.setColor(selectedElement.value, {
      fill: '#ffffff',
      stroke: '#F39C12',
      strokeWidth: 2
    })
  }
  
  selectedColor.value = '#3498DB'
  borderWidth.value = 2
  borderStyle.value = 'solid'
  textSize.value = 'medium'
  textColor.value = '#000000'
  
  if (taskLinks.value[selectedElement.value.id]) {
    delete taskLinks.value[selectedElement.value.id]
  }
  
  currentActivityLink.value = null
  
  pendingChanges.value.styles++
  handleDiagramChange()
  closeContextMenu()
}

function resetElementStyles() {
  resetElement()
}

function updateElementName() {
  if (!selectedElement.value || !modeler) return
  
  const modeling = modeler.get('modeling')
  modeling.updateProperties(selectedElement.value, { 
    name: selectedElementName.value 
  })
  
  pendingChanges.value.elements++
  handleDiagramChange()
}

function updateElementDescription() {
  pendingChanges.value.elements++
  handleDiagramChange()
}

function zoomIn() {
  if (!modeler) return
  const canvas = modeler.get('canvas')
  canvas.zoom(canvas.zoom() * 1.2)
}

function zoomOut() {
  if (!modeler) return
  const canvas = modeler.get('canvas')
  canvas.zoom(canvas.zoom() * 0.8)
}

function zoomFit() {
  if (!modeler) return
  modeler.get('canvas').zoom('fit-viewport')
}

function centerView() {
  if (!modeler) return
  const canvas = modeler.get('canvas')
  const viewbox = canvas.viewbox()
  canvas.scroll({ dx: -viewbox.x + 100, dy: -viewbox.y + 100 })
}

let autoSaveTimer = null
let autoSaveInterval = null

function handleDiagramChange() {
  pendingChanges.value.elements++
  scheduleAutoSave()
}

function scheduleAutoSave() {
  if (autoSaveTimer) clearTimeout(autoSaveTimer)
  autoSaveTimer = setTimeout(performAutoSave, 2000)
}

async function performAutoSave() {
  if (!modeler || saving.value) return
  
  try {
    saving.value = true
    saveStatus.value = 'saving'
    
    const { xml } = await modeler.saveXML({ format: true })
    const taskLinksArray = Object.values(taskLinks.value)
    const elementConfigsObject = { ...elementConfigs.value }
    
    const elementRegistry = modeler.get('elementRegistry')
    const sequenceFlowsArray = elementRegistry.getAll()
      .filter(el => el.type === 'bpmn:SequenceFlow')
      .map(flow => ({
        sequence_id: flow.id,
        sequence_name: flow.businessObject?.name || '',
        source_element_id: flow.source?.id || '',
        source_element_name: flow.source?.businessObject?.name || '',
        target_element_id: flow.target?.id || '',
        target_element_name: flow.target?.businessObject?.name || '',
      }))
    
    const response = await axios.post(route('process.core.bpmn.auto-save', props.diagram.id), {
      bpmn_xml: xml,
      task_links: taskLinksArray,
      sequence_flows: sequenceFlowsArray,
      element_configs: elementConfigsObject
    })
    
    if (response.data.success) {
      saveStatus.value = 'saved'
      lastSaveTime.value = new Date().toLocaleTimeString('fr-FR', { 
        hour: '2-digit', 
        minute: '2-digit' 
      })
      
      setTimeout(() => {
        saveStatus.value = 'idle'
        pendingChanges.value = { elements: 0, links: 0, styles: 0 }
      }, 3000)
    }
    
  } catch (error) {
    console.error('Erreur auto-save:', error)
    saveStatus.value = 'error'
  } finally {
    saving.value = false
  }
}

function saveManual() {
  showSaveModal.value = true
}

function cancelSave() {
  showSaveModal.value = false
  versionDescription.value = ''
}

async function confirmSave() {
  if (!modeler || saving.value) return
  
  try {
    saving.value = true
    
    const { xml } = await modeler.saveXML({ format: true })
    const taskLinksArray = Object.values(taskLinks.value)
    
    const response = await axios.post(route('bpmn.manual-save', props.diagram.id), {
      bpmn_xml: xml,
      task_links: taskLinksArray,
      sequence_flows: sequenceFlows.value,
      element_configs: elementConfigs.value,
      version_description: versionDescription.value || 'Sauvegarde manuelle'
    })
    
    if (response.data.success) {
      router.reload()
    }
    
  } catch (error) {
    console.error('Erreur sauvegarde manuelle:', error)
  } finally {
    saving.value = false
    showSaveModal.value = false
  }
}

function toggleProperties() {
  showProperties.value = !showProperties.value
}

function toggleToolbar() {
  showToolbar.value = !showToolbar.value
}

function deselectElement() {
  selectedElement.value = null
}

function goBack() {
  router.visit(route('bpmn.index'))
}

function showElementInfo() {
  showInfoModal.value = true
}

function exportDiagram() {
  window.open(route('bpmn.export', props.diagram.id), '_blank')
}

function showVersions() {
  router.visit(route('bpmn.versions', props.diagram.id))
}

function copyToClipboard(text) {
  navigator.clipboard.writeText(text)
}

function exportElement() {
  if (!selectedElement.value) return
  
  const elementData = {
    id: selectedElementId.value,
    name: selectedElementName.value,
    type: selectedElementType.value,
    color: selectedColor.value,
    activityLink: currentActivityLink.value,
    businessObject: selectedElement.value.businessObject
  }
  
  const dataStr = JSON.stringify(elementData, null, 2)
  const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr)
  
  const exportFileDefaultName = `bpmn-element-${selectedElementId.value}.json`
  
  const linkElement = document.createElement('a')
  linkElement.setAttribute('href', dataUri)
  linkElement.setAttribute('download', exportFileDefaultName)
  linkElement.click()
}

function getDefaultXmlWithParticipant() {
  return `<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL" xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI" xmlns:dc="http://www.omg.org/spec/DD/20100524/DC" xmlns:di="http://www.omg.org/spec/DD/20100524/DI" id="Definitions_1" targetNamespace="http://bpmn.io/schema/bpmn">
  <bpmn:collaboration id="Collaboration_1">
    <bpmn:participant id="Participant_1" name="${props.process.name}" processRef="Process_${props.process.id}" />
  </bpmn:collaboration>
  <bpmn:process id="Process_${props.process.id}" name="${props.process.name}" isExecutable="false">
    <bpmn:startEvent id="StartEvent_1" name="Début" />
  </bpmn:process>
  <bpmndi:BPMNDiagram id="BPMNDiagram_1">
    <bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="Collaboration_1">
      <bpmndi:BPMNShape id="BPMNShape_Participant_1" bpmnElement="Participant_1">
        <dc:Bounds x="100" y="100" width="600" height="400" />
      </bpmndi:BPMNShape>
      <bpmndi:BPMNShape id="BPMNShape_StartEvent_1" bpmnElement="StartEvent_1">
        <dc:Bounds x="200" y="200" width="36" height="36" />
      </bpmndi:BPMNShape>
    </bpmndi:BPMNPlane>
  </bpmndi:BPMNDiagram>
</bpmn:definitions>`
}

// 🔥 LIFECYCLE
onMounted(async () => {
  await initializeModeler()
  
  autoSaveInterval = setInterval(() => {
    if (pendingChanges.value.elements > 0) {
      performAutoSave()
    }
  }, 30000)
})

onBeforeUnmount(() => {
  if (modeler) modeler.destroy()
  if (autoSaveTimer) clearTimeout(autoSaveTimer)
  if (autoSaveInterval) clearInterval(autoSaveInterval)
})
</script>

<style scoped>
/* STYLES OPTIMISÉS POUR PLUS D'ESPACE */
.bpmn-editor-page {
  height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f8f9fa;
  padding: 0;
  margin: 0;
  overflow: hidden;
}

/* Header optimisé */
.header-bar {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1rem 1.5rem;
  margin: 0;
  border-radius: 0;
  box-shadow: 0 2px 10px rgba(102, 126, 234, 0.2);
  position: relative;
  overflow: hidden;
  border: none;
  flex-shrink: 0;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1.5rem;
  position: relative;
  z-index: 1;
}

.title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
  text-shadow: 0 2px 4px rgba(0,0,0,0.1);
  color: white;
}

.header-subinfo {
  display: flex;
  gap: 0.75rem;
  align-items: center;
  margin-top: 0.25rem;
}

.code, .version, .activities-count {
  font-size: 0.8rem;
  opacity: 0.9;
  background: rgba(255,255,255,0.15);
  padding: 0.2rem 0.6rem;
  border-radius: 20px;
  font-family: monospace;
  border: 1px solid rgba(255,255,255,0.3);
  color: white;
}

.save-indicator {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.2rem;
  padding: 0.6rem 1rem;
  background: rgba(255,255,255,0.15);
  border-radius: 8px;
  font-size: 0.85rem;
  min-width: 90px;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,0.2);
  color: white;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.btn-action, .btn-outline, .btn-save-version {
  background: rgba(255,255,255,0.15);
  border: 1px solid rgba(255,255,255,0.3);
  color: white;
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  height: 36px;
}

.btn-action:hover, .btn-outline:hover, .btn-save-version:hover {
  background: rgba(255,255,255,0.25);
  border-color: white;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.btn-save-version {
  background: rgba(52, 152, 219, 0.8);
  border-color: #3498DB;
}

.btn-save-version:hover {
  background: #3498DB;
}

/* Main layout optimisé */
.main-layout {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 0.5rem;
  height: calc(100vh - 70px);
  margin: 0;
  padding: 0.5rem;
  flex: 1;
  min-height: 0;
}

.main-content.full-width {
  grid-column: 1 / -1;
}

/* Sidebar optimisé */
.sidebar-left {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  display: flex;
  flex-direction: column;
  width: 260px;
  min-width: 260px;
  overflow: hidden;
  border: 1px solid #e9ecef;
  height: 100%;
}

.toolbar-header {
  padding: 1rem;
  border-bottom: 2px solid #f0f0f0;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  flex-shrink: 0;
}

.toolbar-header h6 {
  margin: 0;
  font-size: 0.95rem;
  color: #2c3e50;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.toolbar-header small {
  font-size: 0.75rem;
  color: #6c757d;
  margin-top: 0.2rem;
  display: block;
}

.toolbar-search {
  padding: 0.75rem;
  border-bottom: 1px solid #f0f0f0;
  flex-shrink: 0;
}

.search-input {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  font-size: 0.85rem;
  transition: all 0.2s;
  background: white;
  color: #495057;
}

.toolbar-content {
  flex: 1;
  overflow-y: auto;
  padding: 0.75rem;
  min-height: 0;
}

.toolbar-group {
  margin-bottom: 1rem;
  background: white;
  padding: 0.75rem;
  border-radius: 6px;
  border: 1px solid #e9ecef;
}

.tool-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.4rem;
}

.tool-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 0.6rem 0.4rem;
  border: 1px solid #dee2e6;
  background: white;
  border-radius: 6px;
  cursor: move;
  transition: all 0.2s;
  color: #495057;
  font-size: 1.4rem;
  min-height: 70px;
}

.tool-btn:hover {
  border-color: #667eea;
  background: #f8f9fa;
  color: #667eea;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.15);
  transform: translateY(-1px);
}

.tool-label {
  font-size: 0.7rem;
  margin-top: 0.4rem;
  text-align: center;
  font-weight: 500;
  color: #495057;
  line-height: 1.2;
}

.toolbar-footer {
  padding: 0.75rem;
  border-top: 1px solid #f0f0f0;
  background: #f8f9fa;
  flex-shrink: 0;
}

/* Main content optimisé */
.main-content {
  position: relative;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  border: 1px solid #e9ecef;
  height: 100%;
}

.canvas-toolbar {
  padding: 0.5rem 1rem;
  background: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-shrink: 0;
}

.canvas-actions {
  display: flex;
  gap: 0.4rem;
  align-items: center;
}

.btn-canvas {
  background: white;
  border: 1px solid #dee2e6;
  color: #495057;
  padding: 0.4rem;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
}

.btn-canvas:hover {
  background: #667eea;
  border-color: #667eea;
  color: white;
  transform: translateY(-1px);
}

.zoom-level {
  font-size: 0.85rem;
  color: #495057;
  margin-left: 0.5rem;
  font-weight: 500;
  background: white;
  padding: 0.2rem 0.6rem;
  border-radius: 20px;
  border: 1px solid #dee2e6;
}

.canvas-stats {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.stat {
  font-size: 0.8rem;
  color: #6c757d;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: white;
  padding: 0.2rem 0.6rem;
  border-radius: 20px;
  border: 1px solid #dee2e6;
}

.bpmn-canvas {
  flex: 1;
  position: relative;
  overflow: hidden;
  background: #f8f9fa;
  min-height: 0;
}

/* Properties panel optimisé */
.sidebar-right {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  display: flex;
  flex-direction: column;
  width: 320px;
  min-width: 320px;
  overflow: hidden;
  border: 1px solid #e9ecef;
  height: 100%;
}

.properties-header {
  padding: 1rem;
  border-bottom: 2px solid #f0f0f0;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  flex-shrink: 0;
}

.btn-close-panel {
  background: none;
  border: none;
  color: #6c757d;
  cursor: pointer;
  padding: 0.2rem;
  border-radius: 4px;
  transition: all 0.2s;
  font-size: 1rem;
}

.properties-content {
  flex: 1;
  overflow-y: auto;
  padding: 0;
  min-height: 0;
}

/* Responsive */
@media (max-width: 1400px) {
  .main-layout {
    grid-template-columns: 240px 1fr 300px;
  }
  
  .sidebar-left {
    width: 240px;
    min-width: 240px;
  }
  
  .sidebar-right {
    width: 300px;
    min-width: 300px;
  }
  
  .tool-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 1200px) {
  .main-layout {
    grid-template-columns: 220px 1fr 280px;
  }
  
  .sidebar-left {
    width: 220px;
    min-width: 220px;
  }
  
  .sidebar-right {
    width: 280px;
    min-width: 280px;
  }
}

@media (max-width: 992px) {
  .main-layout {
    grid-template-columns: 1fr;
  }
  
  .sidebar-left, .sidebar-right {
    position: fixed;
    top: 70px;
    bottom: 0;
    z-index: 1000;
    transform: translateX(-100%);
    transition: transform 0.3s ease;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
  }
  
  .sidebar-left {
    left: 0;
  }
  
  .sidebar-right {
    right: 0;
    transform: translateX(100%);
  }
  
  .main-content {
    grid-column: 1 / -1;
  }
}

@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .header-actions {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .action-buttons {
    flex-wrap: wrap;
    justify-content: center;
  }
  
  .header-subinfo {
    flex-wrap: wrap;
    gap: 0.5rem;
  }
  
  .code, .version, .activities-count {
    font-size: 0.7rem;
    padding: 0.15rem 0.4rem;
  }
  
  .title {
    font-size: 1.3rem;
  }
  
  .canvas-toolbar {
    flex-direction: column;
    gap: 0.5rem;
    align-items: stretch;
  }
  
  .canvas-actions, .canvas-stats {
    justify-content: center;
  }
}

/* Animation pour le spinner */
.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Scrollbar styling */
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}

/* Lanes list */
.lanes-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.lane-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem;
  background: #f8f9fa;
  border-radius: 4px;
  border: 1px solid #dee2e6;
}

.lane-item i {
  color: #667eea;
}

.lane-item span {
  flex: 1;
  font-size: 0.85rem;
  color: #495057;
}

.btn-small {
  background: none;
  border: 1px solid #6c757d;
  color: #6c757d;
  padding: 0.2rem 0.4rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.75rem;
  transition: all 0.2s;
}

.btn-small:hover {
  background: #6c757d;
  color: white;
}

.btn-add-lane {
  width: 100%;
  padding: 0.5rem;
  background: #f8f9fa;
  border: 1px dashed #dee2e6;
  border-radius: 4px;
  color: #6c757d;
  cursor: pointer;
  font-size: 0.85rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: all 0.2s;
}

.btn-add-lane:hover {
  background: #e9ecef;
  border-color: #667eea;
  color: #667eea;
}

/* Form controls */
.form-select {
  padding: 0.4rem 0.75rem;
  border: 1px solid #dee2e6;
  border-radius: 4px;
  font-size: 0.85rem;
  background: white;
  color: #495057;
  width: 100%;
}

.form-select:focus {
  border-color: #667eea;
  outline: none;
  box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
}

.form-control {
  padding: 0.4rem 0.75rem;
  border: 1px solid #dee2e6;
  border-radius: 4px;
  font-size: 0.85rem;
  background: white;
  color: #495057;
  width: 100%;
}

.form-control:focus {
  border-color: #667eea;
  outline: none;
  box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
}

/* Button styles */
.btn {
  padding: 0.4rem 0.75rem;
  border-radius: 4px;
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid transparent;
}

.btn-primary {
  background: #667eea;
  color: white;
  border-color: #667eea;
}

.btn-primary:hover {
  background: #5a67d8;
  border-color: #5a67d8;
}

.btn-secondary {
  background: #6c757d;
  color: white;
  border-color: #6c757d;
}

.btn-secondary:hover {
  background: #5a6268;
  border-color: #5a6268;
}

.btn-outline-primary {
  background: transparent;
  color: #667eea;
  border-color: #667eea;
}

.btn-outline-primary:hover {
  background: #667eea;
  color: white;
}

.btn-outline-secondary {
  background: transparent;
  color: #6c757d;
  border-color: #6c757d;
}

.btn-outline-secondary:hover {
  background: #6c757d;
  color: white;
}
</style>