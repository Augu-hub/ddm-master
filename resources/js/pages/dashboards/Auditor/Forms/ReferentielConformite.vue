<template>
  <VerticalLayoutAudit>
    <div class="rcc-root">

      <!-- ── TOPBAR ── -->
      <header class="topbar">
        <div class="topbar-l">
          <a :href="backUrl" class="back-btn"><IconArrow /></a>
          <div class="brand">
            <span class="brand-tag">RCC</span>
            <div>
              <div class="brand-title">Référentiel de Contrôle de Conformité</div>
              <div class="brand-sub">{{ mission?.code_mission || '—' }} — {{ mission?.libelle || '—' }}</div>
            </div>
          </div>
        </div>
        <div class="topbar-r">
          <template v-if="currentForm">
            <span class="status-chip" :class="`s-${currentForm.validation_status || 'draft'}`">
              <span class="dot"></span>{{ STATUS_LABELS[currentForm.validation_status] || 'Brouillon' }}
            </span>
            <span class="code-chip">{{ currentForm.code || '—' }}</span>
          </template>
          <div class="user-pill" :class="`role-${currentAuditor.role || 'AJ'}`">
            <span class="avatar">{{ initials }}</span>
            <span class="uname">{{ currentAuditor.last_name || '' }} {{ currentAuditor.first_name || '' }}</span>
            <span class="urole">{{ ROLE_LABELS[currentAuditor.role] || 'Auditeur' }}</span>
          </div>
        </div>
      </header>

      <!-- ════ VUE LISTE ════ -->
      <div v-if="view === 'list'" class="view-list">
        <div class="list-hero">
          <div><h2>Référentiels de conformité</h2><p>{{ rccList?.length || 0 }} référentiel(s)</p></div>
          <button v-if="canManage" class="btn btn-primary" @click="view='create'"><IconPlus /> Nouveau RCC</button>
        </div>
        <div v-if="!rccList?.length" class="empty-state">
          <div class="empty-ico">⚖</div>
          <div v-if="canManage">
            <h3>Aucun référentiel créé</h3>
            <p>Créez le premier RCC pour démarrer l'audit.</p>
            <button class="btn btn-primary mt" @click="view='create'"><IconPlus /> Créer</button>
          </div>
          <div v-else><h3>En attente</h3><p>Le DM/CM doit créer le référentiel.</p></div>
        </div>
        <div v-else class="rcc-cards">
          <div v-for="rcc in rccList" :key="rcc.id" class="rcc-card" @click="openEdit(rcc.id)">
            <span class="rcc-code">{{ rcc.code || '—' }}</span>
            <span class="status-chip sm" :class="`s-${rcc.validation_status || 'draft'}`">
              <span class="dot"></span>{{ STATUS_LABELS[rcc.validation_status] || 'Brouillon' }}
            </span>
            <span class="rcc-date">{{ fmt(rcc.updated_at) }}</span>
            <IconChevron />
          </div>
        </div>
      </div>

      <!-- ════ VUE CRÉATION ════ -->
      <div v-else-if="view==='create'" class="view-create">
        <div class="create-shell">
          <div class="create-card">
            <h3 class="card-title">⚖ Nouveau Référentiel de Conformité</h3>
            <div class="fg2">
              <div class="field"><label>Fait par</label><input v-model="draft.fait_par" class="inp" placeholder="Rédacteur" /></div>
              <div class="field"><label>Revue par</label><input v-model="draft.revue_par" class="inp" placeholder="Relecteur" /></div>
              <div class="field"><label>Entité auditée</label><input v-model="draft.entite_auditee" class="inp" placeholder="Direction Générale…" /></div>
              <div class="field"><label>Exercice</label><input v-model="draft.exercice" class="inp" placeholder="2025" /></div>
              <div class="field fg-full"><label>Période</label><input v-model="draft.periode" class="inp" placeholder="1er semestre 2025" /></div>
              <div class="field fg-full"><label>Objectif</label><textarea v-model="draft.objectif" class="inp tarea" rows="2" placeholder="Objectif général…"></textarea></div>
            </div>
            <div class="step-actions">
              <button class="btn btn-ghost" @click="view='list'">Annuler</button>
              <button class="btn btn-primary" :disabled="creating" @click="submitCreate">
                <span v-if="creating" class="spin"></span><IconCheck v-else /> Créer
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ════ VUE ÉDITION ════ -->
      <template v-else-if="view==='edit' && currentForm">
        <div class="edit-layout">

          <!-- ── SIDEBAR ── -->
          <aside class="sidebar">
            <div class="sidebar-head">
              <span class="sidebar-title">Domaines</span>
              <button v-if="canManage && !isLocked" class="btn-icon-sm" @click="openNewDomaine" title="Ajouter">+</button>
            </div>
            <div v-if="!domaines?.length" class="sidebar-empty">
              <div v-if="canManage">
                <p>Aucun domaine.</p>
                <button class="btn btn-sm btn-primary w-full" @click="openNewDomaine"><IconPlus /> Ajouter</button>
              </div>
              <p v-else class="muted-sm">Aucun domaine défini.</p>
            </div>
            <div v-else class="domain-list">
              <div v-for="d in domaines" :key="d.id" class="domain-item"
                :class="{'domain-active':activeDomId===d.id,'domain-mine':isMineDomaine(d)}"
                :style="{'--dc':d.couleur}" @click="activeDomId=d.id">
                <span class="domain-icon">{{ d.icone || '📋' }}</span>
                <div class="domain-info">
                  <div class="domain-code">{{ d.code || '—' }}</div>
                  <div class="domain-label">{{ d.libelle || '—' }}</div>
                  <div class="domain-aud muted-sm" v-if="d.auditeur_id">{{ getAuditorName(d.auditeur_id) }}</div>
                  <div class="domain-aud muted-sm" v-else>Non affecté</div>
                  <div v-if="d.guide_fichier" class="domain-guide-badge">📎 Guide joint</div>
                </div>
                <div class="domain-count" v-if="criteresByDomaine[d.id]?.length">{{ criteresByDomaine[d.id].length }}</div>
                <button v-if="canManage && !isLocked" class="domain-edit-btn" @click.stop="openEditDomaine(d)">✏</button>
              </div>
            </div>
            <div class="sidebar-foot" v-if="currentForm">
              <div class="info-row"><span>Entité</span><strong>{{ currentForm.entite_auditee || '—' }}</strong></div>
              <div class="info-row"><span>Exercice</span><strong>{{ currentForm.exercice || '—' }}</strong></div>
              <div class="info-row"><span>Période</span><strong>{{ currentForm.periode || '—' }}</strong></div>
              <div class="info-row"><span>Fonctions</span><strong>{{ fonctions?.length || 0 }}</strong></div>
              <div class="info-row"><span>Critères</span><strong>{{ criteres?.length || 0 }}</strong></div>
            </div>
          </aside>

          <!-- ── CONTENU ── -->
          <div class="main-content">
            <template v-if="activeDom">

              <!-- Header domaine -->
              <div class="dom-header" :style="{borderLeftColor:activeDom.couleur}">
                <div class="dom-header-l">
                  <span class="dom-icon" :style="{color:activeDom.couleur}">{{ activeDom.icone || '📋' }}</span>
                  <div>
                    <div class="dom-label">{{ activeDom.libelle || '—' }}</div>
                    <div class="dom-sub">
                      <code class="dom-code">{{ activeDom.code || '—' }}</code>
                      <template v-if="activeDom.auditeur_id"> — <strong>{{ getAuditorName(activeDom.auditeur_id) }}</strong></template>
                      <span v-else class="muted-sm">Non affecté</span>
                    </div>
                    <div v-if="activeDom.description" class="dom-desc">{{ activeDom.description }}</div>
                  </div>
                </div>
                <div class="dom-header-r" v-if="!isLocked">
                  <div v-if="canEditDomaine(activeDom)" class="guide-zone">
                    <template v-if="activeDom.guide_fichier">
                      <a :href="activeDom.guide_fichier.url" target="_blank" class="guide-chip">
                        <span>{{ getMimeIcon(activeDom.guide_fichier) }}</span>
                        <span class="guide-name">{{ trunc(activeDom.guide_fichier.name || '', 22) }}</span>
                        <button class="guide-del" @click.prevent="deleteGuideDomaine(activeDom)">×</button>
                      </a>
                    </template>
                    <label v-else :for="`guide-${activeDom.id}`" class="btn btn-sm btn-outline">
                      <span v-if="uploadingGuideFor===activeDom.id" class="spin-sm"></span>
                      <template v-else>📎 Joindre guide</template>
                      <input :id="`guide-${activeDom.id}`" type="file"
                        accept=".pdf,.xlsx,.xls,.docx,.doc,.txt" style="display:none"
                        @change="onGuideChange($event,activeDom)" />
                    </label>
                  </div>
                  <button v-if="canEditDomaine(activeDom)" class="btn btn-sm btn-ai"
                    :disabled="!!aiGenerating[activeDom.id]" @click="openAiModal(activeDom)">
                    <span v-if="aiGenerating[activeDom.id]" class="spin-sm"></span>
                    <template v-else>✨ Générer avec l'IA</template>
                  </button>
                  <button v-if="canEditDomaine(activeDom)" class="btn btn-sm btn-primary" @click="addCritere(activeDom.id)">
                    <IconPlus /> Critère
                  </button>
                </div>
              </div>

              <!-- Tableau critères -->
              <div class="tbl-wrap" v-if="criteresByDomaine[activeDom.id]?.length">
                <table class="rcc-tbl">
                  <thead>
                    <tr class="th-row">
                      <th class="col-ref">Réf. Contrôle</th>
                      <th class="col-art">Réf. Réglementaire</th>
                      <th class="col-proc">Intitulé Procédure</th>
                      <th class="col-point">Point de Contrôle / Exigence</th>
                      <th class="col-preuves">Preuves du Contrôle</th>
                      <th class="col-resp">Responsable du Contrôle</th>
                      <th v-if="!isLocked" class="col-act"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="c in criteresByDomaine[activeDom.id]" :key="c.id"
                      class="tbl-row" :class="{'row-mine':isMine(c)}">

                      <td class="td-ref">
                        <input v-if="canEditCritere(c)" v-model="c.ref_controle" class="cel-inp mono"
                          placeholder="DOM-C01" @change="saveCritere(c)" />
                        <span v-else class="ref-badge" :style="`background:${activeDom.couleur}18;color:${activeDom.couleur}`">
                          {{ c.ref_controle || '—' }}
                        </span>
                      </td>

                      <td class="td-art">
                        <textarea v-if="canEditCritere(c)" v-model="c.ref_reglementaire" class="cel-area" rows="2"
                          placeholder="Art. X — Décret / Guide §…" @change="saveCritere(c)"></textarea>
                        <code v-else class="art-code">{{ c.ref_reglementaire || '—' }}</code>
                      </td>

                      <td class="td-proc">
                        <textarea v-if="canEditCritere(c)" v-model="c.intitule_procedure" class="cel-area" rows="3"
                          placeholder="Procédure auditée…" @change="saveCritere(c)"></textarea>
                        <div v-else class="cell-ro">{{ c.intitule_procedure || '—' }}</div>
                      </td>

                      <td class="td-point">
                        <textarea v-if="canEditCritere(c)" v-model="c.point_controle" class="cel-area" rows="3"
                          placeholder="Ce que l'auditeur vérifie…" @change="saveCritere(c)"></textarea>
                        <div v-else class="cell-ro">{{ c.point_controle || '—' }}</div>
                      </td>

                      <td class="td-preuves">
                        <div class="preuves-cell">
                          <div v-if="c.preuves_fichiers?.length" class="fichiers-list">
                            <div v-for="f in c.preuves_fichiers" :key="f.path" class="fchip" :class="getMimeClass(f)">
                              <span class="fchip-icon">{{ getMimeIcon(f) }}</span>
                              <a :href="f.url" target="_blank" class="fchip-name" :title="f.name">{{ trunc(f.name || '', 18) }}</a>
                              <span class="fchip-size">{{ fmtSize(f.size || 0) }}</span>
                              <button v-if="canEditCritere(c)&&!isLocked" class="fchip-del" @click="deletePreuve(c,f)">×</button>
                            </div>
                          </div>
                          <div v-if="canEditCritere(c)&&!isLocked" class="upload-zone"
                            :class="{'drag-over':dragOver[c.id]}"
                            @dragover.prevent="dragOver[c.id]=true"
                            @dragleave="dragOver[c.id]=false"
                            @drop.prevent="onDrop($event,c)">
                            <span v-if="uploadingFor===c.id" class="spin-sm"></span>
                            <template v-else>
                              <label :for="`fu-${c.id}`" class="upload-label">
                                <span class="upload-ico">📎</span><span>Joindre</span>
                                <span class="upload-hint">pdf · xlsx · docx · jpg</span>
                              </label>
                              <input :id="`fu-${c.id}`" type="file" multiple
                                accept=".pdf,.xlsx,.xls,.docx,.doc,.png,.jpg,.jpeg"
                                style="display:none" @change="onFileChange($event,c)" />
                            </template>
                          </div>
                          <span v-else-if="!c.preuves_fichiers?.length" class="muted-sm">Aucune pièce</span>
                          <textarea v-if="canEditCritere(c)" v-model="c.note_preuves" class="cel-area note-area" rows="2"
                            placeholder="Note sur les preuves…" @change="saveCritere(c)"></textarea>
                          <div v-else-if="c.note_preuves" class="cell-ro note-ro">{{ c.note_preuves }}</div>
                        </div>
                      </td>

                      <td class="td-resp">
                        <template v-if="canEditCritere(c)">
                          <div class="resp-toggle">
                            <button class="resp-mode-btn" :class="{active:getRespMode(c)==='fn'}" @click="setRespMode(c,'fn')">Fonction</button>
                            <button class="resp-mode-btn" :class="{active:getRespMode(c)==='libre'}" @click="setRespMode(c,'libre')">Libre</button>
                          </div>
                          <template v-if="getRespMode(c)==='fn'">
                            <select v-model="c.responsable_fonction_id" class="cel-inp sel-resp"
                              @change="onRespFnChange(c)">
                              <option :value="null">— Sélectionner —</option>
                              <option v-for="f in fonctions" :key="f.id" :value="f.id">
                                {{ f.libelle }}{{ f.code ? ' · '+f.code : '' }}
                              </option>
                            </select>
                            <div v-if="getFonctionLabel(c.responsable_fonction_id)" class="resp-resolved">
                              {{ getFonctionLabel(c.responsable_fonction_id) }}
                            </div>
                          </template>
                          <template v-else>
                            <input v-model="c.responsable_libre" class="cel-inp"
                              placeholder="Saisir le responsable…" @change="saveCritere(c)" />
                          </template>
                        </template>
                        <template v-else>
                          <div class="resp-ro">
                            <span class="resp-name">{{ displayResp(c) }}</span>
                          </div>
                        </template>
                      </td>

                      <td v-if="!isLocked" class="td-act">
                        <button v-if="canEditCritere(c)" class="del-btn" @click="removeCritere(c)" title="Supprimer">×</button>
                      </td>
                    </tr>
                  <div style=""></div>
                  </tbody>
                </table>
              </div>

              <div v-else class="dom-empty">
                <div class="empty-ico sm">📄</div>
                <template v-if="canEditDomaine(activeDom)">
                  <p>Aucun critère dans ce domaine.</p>
                  <div class="dom-empty-actions">
                    <button class="btn btn-primary" @click="addCritere(activeDom.id)"><IconPlus /> Ajouter manuellement</button>
                    <button class="btn btn-ai" @click="openAiModal(activeDom)" :disabled="!!aiGenerating[activeDom.id]">
                      <span v-if="aiGenerating[activeDom.id]" class="spin-sm"></span>
                      <template v-else>✨ Générer avec l'IA</template>
                    </button>
                  </div>
                </template>
                <p v-else class="muted">L'auditeur n'a pas encore saisi de critères.</p>
              </div>
            </template>

            <div v-else class="no-dom">
              <div class="empty-ico">⚖</div>
              <div v-if="canManage">
                <h3>Définissez vos domaines</h3>
                <p>Cliquez sur <strong>+</strong> pour créer le premier domaine.</p>
                <button class="btn btn-primary mt" @click="openNewDomaine"><IconPlus /> Créer un domaine</button>
              </div>
              <div v-else><h3>Aucun domaine</h3><p>Le DM/CM doit créer les domaines.</p></div>
            </div>
          </div>
        </div>

        <footer class="edit-footer">
          <div class="ef-l"><button class="btn btn-ghost" @click="view='list'">← Liste</button></div>
          <div class="ef-c"><span class="totals">{{ domaines?.length || 0 }} domaine(s) · {{ criteres?.length || 0 }} critère(s)</span></div>
          <div class="ef-r">
            <button v-if="currentForm.validation_status==='draft'" class="btn btn-submit" @click="submit"><IconSend /> Soumettre</button>
            <template v-if="canManage && currentForm.validation_status==='in_review'">
              <button class="btn btn-validate" @click="validate"><IconCheck /> Valider</button>
              <button class="btn btn-reject" @click="reject">✕ Rejeter</button>
            </template>
            <div v-if="isLocked" class="locked-pill">🔒 Validé</div>
          </div>
        </footer>
      </template>

      <!-- ════ MODAL DOMAINE ════ -->
      <Teleport to="body">
        <div v-if="domaineModal.open" class="modal-bg" @click.self="domaineModal.open=false">
          <div class="modal">
            <div class="modal-head">
              {{ domaineModal.isEdit ? '✏ Modifier le domaine' : '+ Nouveau domaine' }}
              <button class="modal-x" @click="domaineModal.open=false">×</button>
            </div>
            <div class="modal-body">
              <div class="fg2">
                <div class="field">
                  <label>Code</label>
                  <input v-model="domaineModal.form.code" class="inp" placeholder="DOM-01" />
                </div>
                <div class="field">
                  <label>Auditeur affecté <span class="opt">(optionnel)</span></label>
                  <select v-model="domaineModal.form.auditeur_id" class="inp sel">
                    <option :value="null">— Non affecté —</option>
                    <option v-for="a in phaseAuditeurs" :key="a.id" :value="a.id">
                      {{ a.full_name }} · {{ a.role_code }}
                    </option>
                  </select>
                </div>
                <div class="field fg-full">
                  <label>Libellé *</label>
                  <input v-model="domaineModal.form.libelle" class="inp" placeholder="Passation des marchés…" />
                </div>
                <div class="field fg-full">
                  <label>Description <span class="opt">(optionnel)</span></label>
                  <textarea v-model="domaineModal.form.description" class="inp tarea" rows="2" placeholder="Contexte, périmètre…"></textarea>
                </div>
                <div class="field fg-full">
                  <label>Icône</label>
                  <div class="icon-picker">
                    <button v-for="ico in availableIcons" :key="ico"
                      class="ico-btn" :class="{'ico-sel':domaineModal.form.icone===ico}"
                      @click="domaineModal.form.icone=ico">{{ ico }}</button>
                  </div>
                </div>
                <div class="field fg-full">
                  <label>Couleur</label>
                  <div class="color-picker">
                    <button v-for="col in availableColors" :key="col"
                      class="col-btn" :class="{'col-sel':domaineModal.form.couleur===col}"
                      :style="{background:col}" @click="domaineModal.form.couleur=col"></button>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-foot">
              <button v-if="domaineModal.isEdit" class="btn btn-reject" @click="deleteDomaine" :disabled="domaineModal.saving">Supprimer</button>
              <button class="btn btn-ghost" @click="domaineModal.open=false">Annuler</button>
              <button class="btn btn-primary" @click="saveDomaine" :disabled="domaineModal.saving">
                <span v-if="domaineModal.saving" class="spin"></span>
                <IconCheck v-else /> {{ domaineModal.isEdit ? 'Enregistrer' : 'Créer' }}
              </button>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- ════ MODAL IA ════ -->
      <Teleport to="body">
        <div v-if="aiModal.open" class="modal-bg" @click.self="closeAiModal">
          <div class="modal modal-lg">
            <div class="modal-head ai-head">
              <div class="ai-head-title">
                <span class="ai-spark">✨</span>
                Génération IA — {{ aiModal.domaine?.libelle || '—' }}
              </div>
              <button class="modal-x" @click="closeAiModal">×</button>
            </div>

            <!-- Step 1 : config -->
            <template v-if="aiModal.step===1">
              <div class="modal-body">

                <!-- Contexte -->
                <div class="ai-ctx-box">
                  <div class="ai-ctx-row">
                    <span class="ai-ctx-lbl">Domaine</span>
                    <span><code class="ai-code">{{ aiModal.domaine?.code }}</code> {{ aiModal.domaine?.libelle }}</span>
                  </div>
                  <div class="ai-ctx-row">
                    <span class="ai-ctx-lbl">Entité auditée</span>
                    <span>{{ currentForm?.entite_auditee || '—' }}</span>
                  </div>
                  <div class="ai-ctx-row">
                    <span class="ai-ctx-lbl">Guide joint</span>
                    <span v-if="aiModal.domaine?.guide_fichier" class="ai-guide-ok">✔ {{ aiModal.domaine.guide_fichier.name }}</span>
                    <span v-else class="ai-guide-warn">⚠ Aucun guide — l'IA utilisera son contexte général<span class="ai-hint">(joindre un guide améliore la précision)</span></span>
                  </div>
                  <div class="ai-ctx-row" v-if="fonctions?.length">
                    <span class="ai-ctx-lbl">Fonctions</span>
                    <div class="ai-fns-preview">
                      <span v-for="f in fonctions.slice(0,5)" :key="f.id" class="fn-chip">{{ f.libelle }}</span>
                      <span v-if="fonctions.length>5" class="fn-chip fn-more">+{{ fonctions.length-5 }}</span>
                    </div>
                  </div>
                </div>

                <!-- Note contextuelle -->
                <div class="field mt-12">
                  <label>Note contextuelle <span class="opt">(optionnel)</span></label>
                  <textarea v-model="aiModal.contextNote" class="inp tarea" rows="2"
                    placeholder="Ex : Focus sur les marchés de gré à gré, procédures UEMOA…"></textarea>
                </div>

                <!-- Nombre de critères -->
                <div class="field mt-12">
                  <label>Nombre de critères à générer</label>
                  <div class="nb-row">
                    <button v-for="n in [3,5,8,10]" :key="n" class="nb-btn" :class="{active:aiModal.nbCriteres===n}" @click="aiModal.nbCriteres=n">{{ n }}</button>
                    <input v-model.number="aiModal.nbCriteres" type="number" min="1" max="20" class="inp nb-inp" />
                  </div>
                </div>

                <!-- Mode -->
                <div class="field mt-12">
                  <label>Mode d'insertion</label>
                  <div class="mode-row">
                    <label class="mode-card" :class="{selected:aiModal.mode==='review'}">
                      <input type="radio" v-model="aiModal.mode" value="review" />
                      <div>
                        <div class="mode-title">🔍 Révision</div>
                        <div class="mode-desc">Passer en revue et choisir les critères avant insertion</div>
                      </div>
                    </label>
                    <label class="mode-card" :class="{selected:aiModal.mode==='auto'}">
                      <input type="radio" v-model="aiModal.mode" value="auto" />
                      <div>
                        <div class="mode-title">⚡ Automatique</div>
                        <div class="mode-desc">Insérer directement tous les critères générés</div>
                      </div>
                    </label>
                  </div>
                </div>
              </div>
              <div class="modal-foot">
                <button class="btn btn-ghost" @click="closeAiModal">Annuler</button>
                <button class="btn btn-ai" @click="launchAi">✨ Générer</button>
              </div>
            </template>

            <!-- Step 2 : loading ou review -->
            <template v-else-if="aiModal.step===2">
              <!-- Loading -->
              <div v-if="aiModal.loading" class="modal-body">
                <div class="ai-loading">
                  <div class="ai-spinner-wrap"><span class="ai-spinner">✨</span></div>
                  <div class="ai-loading-title">Génération en cours…</div>
                  <div class="ai-loading-steps">
                    <div class="ai-ls" :class="{active:aiModal.loadStep===0,done:aiModal.loadStep>0}">
                      <span class="ai-ls-dot"></span> Analyse du domaine et du contexte
                    </div>
                    <div class="ai-ls" :class="{active:aiModal.loadStep===1,done:aiModal.loadStep>1}">
                      <span class="ai-ls-dot"></span> Consultation du guide réglementaire
                    </div>
                    <div class="ai-ls" :class="{active:aiModal.loadStep===2,done:aiModal.loadStep>2}">
                      <span class="ai-ls-dot"></span> Rédaction des critères de contrôle
                    </div>
                    <div class="ai-ls" :class="{active:aiModal.loadStep===3,done:aiModal.loadStep>3}">
                      <span class="ai-ls-dot"></span> Structuration et finalisation
                    </div>
                  </div>
                </div>
              </div>

              <!-- Review -->
              <div v-else class="modal-body">
                <div class="ai-review-header">
                  <span class="ai-count-badge">{{ aiModal.generated.filter(c=>c._include).length }} / {{ aiModal.generated.length }} sélectionnés</span>
                  <button class="btn btn-sm btn-ghost" @click="aiModal.generated.forEach(c=>c._include=true)">Tout sélectionner</button>
                  <button class="btn btn-sm btn-ghost" @click="aiModal.generated.forEach(c=>c._include=false)">Tout désélectionner</button>
                </div>
                <div class="ai-list">
                  <div v-for="(c,i) in aiModal.generated" :key="i" class="ai-card" :class="{'ai-excluded':!c._include}">
                    <div class="ai-card-head">
                      <label class="ai-check-label">
                        <input type="checkbox" v-model="c._include" />
                        <span class="ai-ref">{{ c.ref_controle || '—' }}</span>
                      </label>
                      <span class="ai-intitule">{{ c.intitule_procedure }}</span>
                    </div>
                    <div class="ai-card-body">
                      <div class="ai-field ai-full">
                        <label>Réf. réglementaire</label>
                        <input v-model="c.ref_reglementaire" class="cel-inp" placeholder="Art. X…" />
                      </div>
                      <div class="ai-field ai-full">
                        <label>Point de contrôle</label>
                        <textarea v-model="c.point_controle" class="cel-area" rows="2"></textarea>
                      </div>
                      <div class="ai-field">
                        <label>Intitulé procédure</label>
                        <input v-model="c.intitule_procedure" class="cel-inp" />
                      </div>
                      <div class="ai-field">
                        <label>Responsable</label>
                        <input v-model="c.responsable_libre" class="cel-inp" placeholder="Responsable…" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="modal-foot" v-if="!aiModal.loading">
                <button class="btn btn-ghost" @click="closeAiModal">Fermer</button>
                <button class="btn btn-ai" @click="insertCriteres" :disabled="aiModal.inserting || !aiModal.generated.some(c=>c._include)">
                  <span v-if="aiModal.inserting" class="spin"></span>
                  <template v-else>⬇ Insérer ({{ aiModal.generated.filter(c=>c._include).length }})</template>
                </button>
              </div>
            </template>
          </div>
        </div>
      </Teleport>

      <!-- ════ TOAST ════ -->
      <Transition name="t">
        <div v-if="toast.show" class="toast" :class="toast.type==='ok'?'toast-ok':'toast-err'">
          {{ toast.message }}
        </div>
      </Transition>

    </div>
  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'

// Icons
const IconArrow = { template: `<svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>` }
const IconChevron = { template: `<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>` }
const IconPlus = { template: `<svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>` }
const IconCheck = { template: `<svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>` }
const IconSend = { template: `<svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>` }

// Props
const props = defineProps<{
  form: any | null
  domaines: any[]
  criteres: any[]
  fonctions: any[]
  myDomaineIds: number[]
  phaseAuditeurs: any[]
  rccList: any[]
  mission: any
  currentAuditor: { id: number; last_name: string; first_name: string; role: string }
  canManage: boolean
  backUrl: string
  missionId: number
  assignmentId: number
  availableIcons: string[]
  availableColors: string[]
  urlStore: string
  urlUpdate: string | null
  urlSoumettre: string | null
  urlValider: string | null
  urlStoreDomaine: string | null
  urlUpdateDomaine: string | null
  urlDeleteDomaine: string | null
  urlStoreCritere: string | null
  urlUpdateCritere: string | null
  urlDeleteCritere: string | null
  urlUploadPreuve: string | null
  urlDeletePreuve: string | null
  urlUploadGuide: string | null
  urlDeleteGuide: string | null
  urlGenerateAi: string | null
}>()

// Constants
const STATUS_LABELS: Record<string, string> = { draft: 'Brouillon', in_review: 'En révision', validated: 'Validé' }
const ROLE_LABELS: Record<string, string> = { DM: 'Dir. Mission', CM: 'Chef Mission', AS: 'Aud. Senior', AJ: 'Aud. Junior' }

// State
const view = ref<'list' | 'create' | 'edit'>(props.form ? 'edit' : 'list')
const currentForm = ref<any>(props.form)
const creating = ref(false)
const saving = ref(false)
const domaines = reactive<any[]>([...(props.domaines || [])])
const criteres = reactive<any[]>([...(props.criteres || [])])
const fonctions = reactive<any[]>([...(props.fonctions || [])])
const activeDomId = ref<number | null>((props.myDomaineIds?.[0] || props.domaines?.[0]?.id || null))
const uploadingFor = ref<number | null>(null)
const uploadingGuideFor = ref<number | null>(null)
const aiGenerating = reactive<Record<number, boolean>>({})
const dragOver = reactive<Record<number, boolean>>({})
const toast = ref({ show: false, type: 'ok', message: '' })

const domaineModal = reactive({
  open: false, isEdit: false, saving: false, editId: null as number | null,
  form: { code: '', libelle: '', description: '', icone: '📋', couleur: '#1e40af', auditeur_id: null as number | null }
})

const aiModal = reactive({
  open: false, step: 1, loading: false, inserting: false, loadStep: 0,
  domaine: null as any | null,
  contextNote: '', nbCriteres: 5, mode: 'review' as 'auto' | 'review',
  generated: [] as any[],
})

const draft = reactive({ fait_par: '', revue_par: '', entite_auditee: '', exercice: '', periode: '', objectif: '' })

// Computed
const initials = computed(() => ((props.currentAuditor.last_name?.[0] || '?') + (props.currentAuditor.first_name?.[0] || '?')).toUpperCase())
const isLocked = computed(() => currentForm.value?.validation_status === 'validated')
const activeDom = computed(() => domaines.find(d => d.id === activeDomId.value) || null)
const criteresByDomaine = computed(() => {
  const m: Record<number, any[]> = {}
  for (const c of criteres) {
    if (!m[c.domaine_id]) m[c.domaine_id] = []
    m[c.domaine_id].push(c)
  }
  return m
})

// Helper functions
function trunc(s: string, n: number): string {
  if (!s || typeof s !== 'string') return ''
  return s.length > n ? s.slice(0, n) + '…' : s
}

function fmtSize(b: number): string {
  if (!b || isNaN(b)) return '0 Ko'
  return b > 1048576 ? (b / 1048576).toFixed(1) + ' Mo' : (b / 1024).toFixed(0) + ' Ko'
}

function fmt(d: string): string {
  if (!d) return '—'
  try { return new Date(d).toLocaleDateString('fr-FR') } catch { return d }
}

function getMimeIcon(f: any): string {
  if (!f) return '📎'
  const mime = f.mime || ''
  const name = f.name || ''
  if (/pdf/i.test(mime)) return '📕'
  if (/xlsx?|spreadsheet/i.test(mime) || /\.xlsx?$/i.test(name)) return '📊'
  if (/docx?|word/i.test(mime) || /\.docx?$/i.test(name)) return '📄'
  if (/image/i.test(mime)) return '🖼'
  return '📎'
}

function getMimeClass(f: any): string {
  if (!f) return 'fchip-other'
  const mime = f.mime || ''
  const name = f.name || ''
  if (/pdf/i.test(mime)) return 'fchip-pdf'
  if (/xlsx?|spreadsheet/i.test(mime) || /\.xlsx?$/i.test(name)) return 'fchip-xl'
  if (/docx?|word/i.test(mime) || /\.docx?$/i.test(name)) return 'fchip-doc'
  if (/image/i.test(mime)) return 'fchip-img'
  return 'fchip-other'
}

function getAuditorName(id: any): string {
  if (!id) return '—'
  const auditor = props.phaseAuditeurs?.find(a => a.id === Number(id))
  return auditor?.full_name || '—'
}

function getFonctionLabel(id: number | null): string {
  if (!id) return ''
  const fn = fonctions.find(f => f.id === id)
  return fn?.libelle || ''
}

function getRespMode(c: any): 'fn' | 'libre' {
  if (!c) return 'fn'
  if (c._respMode) return c._respMode
  if (c.responsable_libre && !c.responsable_fonction_id) return 'libre'
  return 'fn'
}

function setRespMode(c: any, mode: 'fn' | 'libre') {
  if (!c) return
  c._respMode = mode
  if (mode === 'fn') c.responsable_libre = null
  else c.responsable_fonction_id = null
}

function onRespFnChange(c: any) {
  if (!c) return
  c.responsable_libre = getFonctionLabel(c.responsable_fonction_id) || null
  saveCritere(c)
}

function displayResp(c: any): string {
  if (!c) return '—'
  if (c.responsable_libre) return c.responsable_libre
  const fn = fonctions.find(f => f.id === c.responsable_fonction_id)
  return fn?.libelle || '—'
}

function isMine(c: any): boolean {
  return c?.auditeur_id === props.currentAuditor.id
}

function isMineDomaine(d: any): boolean {
  return props.myDomaineIds?.includes(d?.id) || false
}

function canEditDomaine(d: any): boolean {
  return !isLocked.value && (props.canManage || props.myDomaineIds?.includes(d?.id))
}

function canEditCritere(c: any): boolean {
  return !isLocked.value && (props.canManage || c?.auditeur_id === props.currentAuditor.id)
}

function showToast(type: 'ok' | 'err', msg: string) {
  toast.value = { show: true, type, message: msg }
  setTimeout(() => { toast.value.show = false }, 3400)
}

function csrf(): string {
  return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content || ''
}

// Navigation
function openEdit(id: number) {
  if (props.form?.id === id) { view.value = 'edit'; return }
  router.visit(window.location.pathname + `?rcc_id=${id}`, { preserveState: false })
}

// Domaines
function openNewDomaine() {
  domaineModal.isEdit = false
  domaineModal.editId = null
  domaineModal.form = {
    code: '',
    libelle: '',
    description: '',
    icone: '📋',
    couleur: props.availableColors?.[domaines.length % (props.availableColors?.length || 10)] || '#374151',
    auditeur_id: null
  }
  domaineModal.open = true
}

function openEditDomaine(d: any) {
  domaineModal.isEdit = true
  domaineModal.editId = d.id
  domaineModal.form = {
    code: d.code || '',
    libelle: d.libelle || '',
    description: d.description || '',
    icone: d.icone || '📋',
    couleur: d.couleur || '#374151',
    auditeur_id: d.auditeur_id || null
  }
  domaineModal.open = true
}

async function saveDomaine() {
  if (!domaineModal.form.libelle?.trim()) { showToast('err', 'Libellé obligatoire'); return }
  domaineModal.saving = true
  try {
    if (domaineModal.isEdit && domaineModal.editId) {
      const url = props.urlUpdateDomaine!.replace(':id', String(domaineModal.editId))
      const res = await fetch(url, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify(domaineModal.form)
      })
      const data = await res.json()
      if (data.success) {
        const i = domaines.findIndex(d => d.id === domaineModal.editId)
        if (i !== -1) Object.assign(domaines[i], data.domaine)
        domaineModal.open = false
        showToast('ok', 'Domaine mis à jour')
      } else { showToast('err', data.error || 'Erreur') }
    } else {
      const res = await fetch(props.urlStoreDomaine!, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify(domaineModal.form)
      })
      const data = await res.json()
      if (data.success) {
        domaines.push(data.domaine)
        activeDomId.value = data.domaine.id
        domaineModal.open = false
        showToast('ok', data.message)
      } else { showToast('err', data.error || 'Erreur') }
    }
  } catch (e) { showToast('err', 'Erreur réseau') }
  finally { domaineModal.saving = false }
}

async function deleteDomaine() {
  if (!domaineModal.editId || !confirm('Supprimer ce domaine et tous ses critères ?')) return
  domaineModal.saving = true
  try {
    const url = props.urlDeleteDomaine!.replace(':id', String(domaineModal.editId))
    const res = await fetch(url, {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() }
    })
    const data = await res.json()
    if (data.success) {
      const id = domaineModal.editId!
      const i = domaines.findIndex(d => d.id === id)
      if (i !== -1) domaines.splice(i, 1)
      for (let j = criteres.length - 1; j >= 0; j--) {
        if (criteres[j].domaine_id === id) criteres.splice(j, 1)
      }
      activeDomId.value = domaines[0]?.id || null
      domaineModal.open = false
      showToast('ok', 'Domaine supprimé')
    } else { showToast('err', data.error || 'Erreur') }
  } catch (e) { showToast('err', 'Erreur réseau') }
  finally { domaineModal.saving = false }
}

// Guide
async function onGuideChange(e: Event, d: any) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file || !props.urlUploadGuide) return
  uploadingGuideFor.value = d.id
  try {
    const fd = new FormData()
    fd.append('domaine_id', String(d.id))
    fd.append('file', file)
    const res = await fetch(props.urlUploadGuide, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf() },
      body: fd
    })
    const data = await res.json()
    if (data.success) {
      const i = domaines.findIndex(x => x.id === d.id)
      if (i !== -1) domaines[i].guide_fichier = data.fichier
      showToast('ok', `Guide «${file.name}» joint`)
    } else { showToast('err', data.error || 'Erreur') }
  } catch (e) { showToast('err', 'Erreur réseau') }
  finally {
    uploadingGuideFor.value = null
    ;(e.target as HTMLInputElement).value = ''
  }
}

async function deleteGuideDomaine(d: any) {
  if (!d.guide_fichier || !props.urlDeleteGuide || !confirm(`Supprimer le guide "${d.guide_fichier.name}" ?`)) return
  try {
    const res = await fetch(props.urlDeleteGuide, {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ domaine_id: d.id })
    })
    const data = await res.json()
    if (data.success) {
      const i = domaines.findIndex(x => x.id === d.id)
      if (i !== -1) domaines[i].guide_fichier = null
      showToast('ok', 'Guide supprimé')
    } else { showToast('err', data.error || 'Erreur') }
  } catch (e) { showToast('err', 'Erreur réseau') }
}

// Critères
async function addCritere(domaineId: number) {
  if (!props.urlStoreCritere) return
  try {
    const res = await fetch(props.urlStoreCritere, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ domaine_id: domaineId, intitule_procedure: 'Nouveau critère' })
    })
    const data = await res.json()
    if (data.success) { criteres.push({ ...data.critere, preuves_fichiers: [] }) }
    else { showToast('err', data.error || 'Erreur') }
  } catch (e) { showToast('err', 'Erreur réseau') }
}

async function saveCritere(c: any) {
  if (!props.urlUpdateCritere) return
  try {
    const url = props.urlUpdateCritere.replace(':id', String(c.id))
    await fetch(url, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({
        ref_controle: c.ref_controle,
        ref_reglementaire: c.ref_reglementaire,
        intitule_procedure: c.intitule_procedure,
        point_controle: c.point_controle,
        note_preuves: c.note_preuves,
        responsable_fonction_id: c.responsable_fonction_id,
        responsable_libre: c.responsable_libre,
      })
    })
  } catch (e) { showToast('err', 'Erreur sauvegarde') }
}

async function removeCritere(c: any) {
  if (!props.urlDeleteCritere || !confirm('Supprimer ce critère ?')) return
  try {
    const url = props.urlDeleteCritere.replace(':id', String(c.id))
    const res = await fetch(url, {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() }
    })
    const data = await res.json()
    if (data.success) {
      const i = criteres.findIndex(x => x.id === c.id)
      if (i !== -1) criteres.splice(i, 1)
    } else { showToast('err', data.error || 'Erreur') }
  } catch (e) { showToast('err', 'Erreur réseau') }
}

// Preuves
async function uploadFile(c: any, file: File) {
  if (!props.urlUploadPreuve) return
  uploadingFor.value = c.id
  try {
    const fd = new FormData()
    fd.append('critere_id', String(c.id))
    fd.append('file', file)
    const res = await fetch(props.urlUploadPreuve, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf() },
      body: fd
    })
    const data = await res.json()
    if (data.success) {
      if (!c.preuves_fichiers) c.preuves_fichiers = []
      c.preuves_fichiers.push(data.fichier)
    } else { showToast('err', data.error || 'Erreur') }
  } catch (e) { showToast('err', 'Erreur réseau') }
  finally { uploadingFor.value = null }
}

function onFileChange(e: Event, c: any) {
  const files = Array.from((e.target as HTMLInputElement).files || [])
  files.forEach(f => uploadFile(c, f))
  ;(e.target as HTMLInputElement).value = ''
}

function onDrop(e: DragEvent, c: any) {
  dragOver[c.id] = false
  const files = Array.from(e.dataTransfer?.files || [])
  files.forEach(f => uploadFile(c, f))
}

async function deletePreuve(c: any, f: any) {
  if (!props.urlDeletePreuve || !confirm(`Supprimer "${f.name}" ?`)) return
  try {
    const res = await fetch(props.urlDeletePreuve, {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ critere_id: c.id, path: f.path })
    })
    const data = await res.json()
    if (data.success) {
      const i = c.preuves_fichiers.findIndex((x: any) => x.path === f.path)
      if (i !== -1) c.preuves_fichiers.splice(i, 1)
    } else { showToast('err', data.error || 'Erreur') }
  } catch (e) { showToast('err', 'Erreur réseau') }
}

// IA
function openAiModal(d: any) {
  Object.assign(aiModal, {
    open: true, step: 1, loading: false, inserting: false, loadStep: 0,
    domaine: d, contextNote: '', nbCriteres: 5, mode: 'review', generated: []
  })
}

function closeAiModal() { aiModal.open = false }

async function launchAi() {
  if (!props.urlGenerateAi || !aiModal.domaine) return
  aiModal.loading = true
  aiModal.step = 2
  aiModal.loadStep = 0

  const stepTimer = setInterval(() => {
    if (aiModal.loadStep < 3) aiModal.loadStep++
  }, 1200)

  try {
    const res = await fetch(props.urlGenerateAi, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({
        domaine_id: aiModal.domaine.id,
        context_note: aiModal.contextNote,
        nb_criteres: aiModal.nbCriteres,
        mode: aiModal.mode
      })
    })
    const data = await res.json()
    clearInterval(stepTimer)
    aiModal.loadStep = 4

    if (!data.success) {
      showToast('err', data.error || 'Erreur IA')
      aiModal.loading = false
      return
    }

    if (aiModal.mode === 'auto') {
      for (const c of data.criteres || []) {
        criteres.push({ ...c, preuves_fichiers: [] })
      }
      showToast('ok', `✨ ${data.criteres?.length || 0} critère(s) insérés`)
      aiModal.open = false
    } else {
      aiModal.generated = (data.criteres || []).map((c: any) => ({ ...c, _include: true }))
    }
  } catch (e) {
    clearInterval(stepTimer)
    showToast('err', 'Erreur réseau')
  } finally { aiModal.loading = false }
}

async function insertCriteres() {
  if (!props.urlStoreCritere || !aiModal.domaine) return
  const toInsert = aiModal.generated.filter(c => c._include)
  if (!toInsert.length) return
  aiModal.inserting = true
  let inserted = 0
  try {
    for (const crit of toInsert) {
      const res = await fetch(props.urlStoreCritere, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify({
          domaine_id: aiModal.domaine.id,
          ref_controle: crit.ref_controle,
          ref_reglementaire: crit.ref_reglementaire,
          intitule_procedure: crit.intitule_procedure,
          point_controle: crit.point_controle,
          responsable_fonction_id: crit.responsable_fonction_id,
          responsable_libre: crit.responsable_libre,
        })
      })
      const data = await res.json()
      if (data.success) {
        criteres.push({ ...data.critere, preuves_fichiers: [] })
        inserted++
      }
    }
    showToast('ok', `✨ ${inserted} critère(s) insérés`)
    aiModal.open = false
  } catch (e) { showToast('err', 'Erreur réseau') }
  finally { aiModal.inserting = false }
}

// Création RCC
async function submitCreate() {
  creating.value = true
  try {
    const res = await fetch(props.urlStore, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ mission_id: props.missionId, assignment_id: props.assignmentId, ...draft })
    })
    const data = await res.json()
    if (data.success) {
      showToast('ok', 'RCC créé !')
      setTimeout(() => {
        if (data.redirect) window.location.href = data.redirect
        else router.reload()
      }, 500)
    } else { showToast('err', data.message || 'Erreur') }
  } catch (e) { showToast('err', 'Erreur réseau') }
  finally { creating.value = false }
}

// Workflow
async function submit() {
  if (!props.urlSoumettre) return
  saving.value = true
  try {
    const res = await fetch(props.urlSoumettre, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ mission_id: props.missionId, assignment_id: props.assignmentId })
    })
    const data = await res.json()
    if (data.success && currentForm.value) {
      currentForm.value.validation_status = 'in_review'
      showToast('ok', 'Soumis pour validation')
    } else { showToast('err', data.error || 'Erreur') }
  } catch (e) { showToast('err', 'Erreur réseau') }
  finally { saving.value = false }
}

async function validate() {
  if (!props.urlValider) return
  saving.value = true
  try {
    const res = await fetch(props.urlValider, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ mission_id: props.missionId, assignment_id: props.assignmentId, action: 'validate' })
    })
    const data = await res.json()
    if (data.success && currentForm.value) {
      currentForm.value.validation_status = 'validated'
      showToast('ok', 'RCC validé')
    } else { showToast('err', data.error || 'Erreur') }
  } catch (e) { showToast('err', 'Erreur réseau') }
  finally { saving.value = false }
}

async function reject() {
  const note = prompt('Motif du rejet :')
  if (!note?.trim()) return
  saving.value = true
  try {
    const res = await fetch(props.urlValider!, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ mission_id: props.missionId, assignment_id: props.assignmentId, action: 'reject', note })
    })
    const data = await res.json()
    if (data.success && currentForm.value) {
      currentForm.value.validation_status = 'draft'
      showToast('ok', 'RCC rejeté')
    } else { showToast('err', data.error || 'Erreur') }
  } catch (e) { showToast('err', 'Erreur réseau') }
  finally { saving.value = false }
}
</script>

<style scoped>
.rcc-root{font-family:'Geist','Inter',system-ui,sans-serif;min-height:100vh;background:#f0f4f8;display:flex;flex-direction:column;--navy:#0f172a;--slate:#475569;--border:#e2e8f0;--green:#15803d;--red:#dc2626;--acc:#7e22ce}
.topbar{position:sticky;top:0;z-index:100;height:52px;background:#0f172a;display:flex;align-items:center;justify-content:space-between;padding:0 16px;box-shadow:0 2px 10px rgba(0,0,0,.3)}
.topbar-l{display:flex;align-items:center;gap:10px;min-width:0}.topbar-r{display:flex;align-items:center;gap:8px;flex-shrink:0}
.back-btn{width:28px;height:28px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.12);border-radius:7px;color:#94a3b8;text-decoration:none;transition:all .15s}.back-btn:hover{background:rgba(255,255,255,.08);color:#fff}
.brand{display:flex;align-items:center;gap:8px;min-width:0}
.brand-tag{font-size:10px;font-weight:700;letter-spacing:.08em;background:#7e22ce;color:#fff;padding:2px 7px;border-radius:5px;flex-shrink:0}
.brand-title{font-size:12px;font-weight:600;color:#f1f5f9;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.brand-sub{font-size:10px;color:#64748b}
.status-chip{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:20px;font-size:10px;font-weight:600}.status-chip.sm{padding:2px 7px;font-size:9px}
.s-draft{background:rgba(100,116,139,.2);color:#94a3b8}.s-in_review{background:rgba(29,78,216,.2);color:#93c5fd;border:1px solid rgba(29,78,216,.3)}.s-validated{background:rgba(21,128,61,.2);color:#86efac;border:1px solid rgba(21,128,61,.3)}
.dot{width:5px;height:5px;border-radius:50%;background:currentColor}
.code-chip{font-size:10px;font-family:monospace;background:rgba(255,255,255,.08);color:#94a3b8;padding:3px 8px;border-radius:5px}
.user-pill{display:flex;align-items:center;gap:6px}.avatar{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;flex-shrink:0}
.role-DM .avatar{background:#7e22ce}.role-CM .avatar{background:#0369a1}.role-AS .avatar,.role-AJ .avatar{background:#374151}
.uname{font-size:11px;font-weight:600;color:#e2e8f0}.urole{font-size:10px;color:#64748b}
.view-list{flex:1;padding:24px 20px;max-width:860px;margin:0 auto;width:100%}
.list-hero{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}
.list-hero h2{margin:0 0 3px;font-size:18px;font-weight:700;color:var(--navy)}.list-hero p{margin:0;font-size:12px;color:var(--slate)}
.empty-state{text-align:center;padding:60px 20px;background:#fff;border-radius:14px;border:1px solid var(--border)}
.empty-ico{font-size:42px;margin-bottom:14px}.empty-ico.sm{font-size:28px}
.empty-state h3{margin:0 0 6px;font-size:15px;color:var(--navy)}.empty-state p,.muted{margin:0;font-size:12px;color:var(--slate)}
.mt{margin-top:14px}.mt-12{margin-top:12px}.muted-sm{font-size:10px;color:#94a3b8;font-style:italic}
.rcc-cards{display:flex;flex-direction:column;gap:7px}
.rcc-card{background:#fff;border:1px solid var(--border);border-radius:10px;padding:12px 14px;display:flex;align-items:center;gap:10px;cursor:pointer;transition:all .15s}.rcc-card:hover{border-color:#c4b5fd;box-shadow:0 2px 8px rgba(126,34,206,.08)}
.rcc-code{font-family:monospace;font-size:12px;font-weight:700;color:var(--navy)}.rcc-date{font-size:11px;color:var(--slate);margin-left:auto}
.view-create{flex:1;padding:24px 20px}.create-shell{max-width:700px;margin:0 auto}
.create-card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:20px}
.card-title{margin:0 0 14px;font-size:14px;font-weight:700;color:var(--navy)}
.fg2{display:grid;grid-template-columns:1fr 1fr;gap:12px}.fg-full{grid-column:1/-1}
.field{display:flex;flex-direction:column;gap:4px}.field label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--slate)}.opt{font-weight:400;text-transform:none;letter-spacing:0}
.step-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:18px}
.inp{padding:7px 10px;border:1px solid var(--border);border-radius:7px;font-size:12px;color:var(--navy);font-family:inherit;background:#fff;transition:border-color .15s;width:100%;box-sizing:border-box}
.inp:focus{outline:none;border-color:#a855f7;box-shadow:0 0 0 3px rgba(168,85,247,.1)}.tarea{resize:vertical;min-height:52px}.sel{appearance:none;cursor:pointer}
.edit-layout{display:flex;flex:1;overflow:hidden}
.sidebar{width:260px;flex-shrink:0;background:#fff;border-right:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden}
.sidebar-head{display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border-bottom:1px solid var(--border)}
.sidebar-title{font-size:12px;font-weight:700;color:var(--navy)}
.btn-icon-sm{width:22px;height:22px;border-radius:6px;border:1px solid var(--border);background:#f8fafc;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;color:var(--slate);transition:all .12s}.btn-icon-sm:hover{background:#fdf4ff;border-color:#c4b5fd;color:#7e22ce}
.sidebar-empty{padding:14px;text-align:center;font-size:11px;color:var(--slate)}
.domain-list{flex:1;overflow-y:auto;padding:6px}
.domain-item{display:flex;align-items:flex-start;gap:8px;padding:9px 10px;border-radius:9px;cursor:pointer;border:1px solid transparent;margin-bottom:4px;transition:all .15s;position:relative}.domain-item:hover{background:#fdf4ff;border-color:#e9d5ff}
.domain-active{background:#fdf4ff!important;border-color:var(--dc,#7e22ce)!important;border-left-width:3px}
.domain-mine{border-left:2px solid #a855f7}
.domain-icon{font-size:18px;flex-shrink:0;margin-top:1px}
.domain-info{flex:1;min-width:0}.domain-code{font-size:10px;font-family:monospace;font-weight:700;color:var(--navy)}.domain-label{font-size:11px;font-weight:600;color:var(--navy);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.domain-aud{font-size:10px;color:var(--slate);margin-top:2px}
.domain-guide-badge{font-size:9px;color:#7e22ce;background:#fdf4ff;border:1px solid #e9d5ff;border-radius:4px;padding:1px 5px;margin-top:3px;display:inline-block}
.domain-count{background:#7e22ce;color:#fff;font-size:9px;font-weight:700;padding:1px 5px;border-radius:10px;min-width:16px;text-align:center;flex-shrink:0;align-self:flex-start;margin-top:2px}
.domain-edit-btn{position:absolute;top:4px;right:4px;background:none;border:none;font-size:11px;cursor:pointer;color:#94a3b8;opacity:0;transition:opacity .15s}.domain-item:hover .domain-edit-btn{opacity:1}
.sidebar-foot{padding:10px 14px;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:5px}
.info-row{display:flex;justify-content:space-between;font-size:10px;color:var(--slate)}.info-row strong{color:var(--navy);font-size:11px}
.main-content{flex:1;overflow:auto;display:flex;flex-direction:column}
.dom-header{display:flex;align-items:flex-start;justify-content:space-between;padding:12px 16px;background:#fff;border-bottom:1px solid var(--border);border-left:4px solid #7e22ce;gap:12px;flex-wrap:wrap}
.dom-header-l{display:flex;align-items:flex-start;gap:10px;flex:1}.dom-header-r{display:flex;align-items:center;gap:7px;flex-wrap:wrap;flex-shrink:0}
.dom-icon{font-size:24px;flex-shrink:0;margin-top:2px}
.dom-label{font-size:13px;font-weight:700;color:var(--navy);margin-bottom:3px}.dom-sub{font-size:11px;color:var(--slate);display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.dom-code{font-size:10px;font-family:monospace;font-weight:700;background:#f1f5f9;color:var(--navy);padding:1px 6px;border-radius:4px}
.dom-desc{font-size:11px;color:var(--slate);font-style:italic;margin-top:4px}
.dom-empty{text-align:center;padding:40px;background:#fff;border:1px dashed var(--border);margin:14px}.dom-empty p{font-size:12px;color:var(--slate);margin:0 0 14px}
.dom-empty-actions{display:flex;gap:8px;justify-content:center;flex-wrap:wrap}
.no-dom{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:60px 20px;text-align:center;background:#fff}
.no-dom h3{margin:0 0 8px;font-size:16px;font-weight:700;color:var(--navy)}.no-dom p{margin:0;font-size:13px;color:var(--slate)}
.guide-zone{display:flex;align-items:center}
.guide-chip{display:inline-flex;align-items:center;gap:5px;padding:4px 9px;border-radius:6px;background:#fdf4ff;border:1px solid #e9d5ff;font-size:10px;color:#581c87;text-decoration:none;font-weight:500;max-width:180px}.guide-chip:hover{background:#f3e8ff}
.guide-name{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:110px}.guide-del{background:none;border:none;color:#94a3b8;cursor:pointer;font-size:14px;border-radius:3px;transition:color .1s;flex-shrink:0}.guide-del:hover{color:var(--red)}
.tbl-wrap{overflow-x:auto}
.rcc-tbl{width:100%;border-collapse:collapse;font-size:11px;background:#fff}
.th-row th{padding:8px 10px;background:#fdf4ff;color:#581c87;font-size:10px;font-weight:700;text-align:left;text-transform:uppercase;letter-spacing:.04em;border-bottom:2px solid #e9d5ff;white-space:nowrap;vertical-align:bottom;border-right:1px solid var(--border)}.th-row th:last-child{border-right:none}
.rcc-tbl td{border-bottom:1px solid var(--border);border-right:1px solid #f5f3ff;vertical-align:top}.rcc-tbl td:last-child{border-right:none}
.rcc-tbl tbody tr:last-child td{border-bottom:none}
.tbl-row:hover{background:#fdf4ff}.row-mine{border-left:3px solid #7e22ce}
.col-ref{width:72px}.col-art{width:130px}.col-proc{width:155px}.col-point{width:200px}.col-preuves{width:200px}.col-resp{width:185px}.col-act{width:30px}
.td-ref,.td-art,.td-proc,.td-point,.td-preuves,.td-resp,.td-act{padding:6px 8px}.td-ref,.td-act{vertical-align:middle;text-align:center}
.ref-badge{display:inline-block;padding:2px 7px;border-radius:4px;font-family:monospace;font-size:10px;font-weight:700;white-space:nowrap}
.art-code{font-family:monospace;font-size:10px;color:#0369a1;line-height:1.4;display:block}
.cell-ro{font-size:11px;color:#334155;line-height:1.5;padding:2px 0}
.note-ro{font-size:10px;color:var(--slate);font-style:italic;margin-top:4px;padding-top:4px;border-top:1px dashed var(--border)}
.resp-ro{display:flex;flex-direction:column;gap:3px}.resp-name{font-size:11px;color:var(--navy);font-weight:500}
.cel-inp{width:100%;padding:4px 6px;border:1px solid var(--border);border-radius:5px;font-size:10px;font-family:inherit;color:var(--navy);background:#fff;transition:border-color .15s;box-sizing:border-box}.cel-inp:focus{outline:none;border-color:#a855f7}.mono{font-family:monospace;font-weight:700}
.cel-area{width:100%;padding:4px 6px;border:1px solid var(--border);border-radius:5px;font-size:10px;font-family:inherit;color:var(--navy);background:#fff;resize:vertical;min-height:44px;transition:border-color .15s;box-sizing:border-box}.cel-area:focus{outline:none;border-color:#a855f7}
.note-area{min-height:34px;border-style:dashed;background:#fafafa;margin-top:6px}
.resp-toggle{display:flex;border:1px solid var(--border);border-radius:5px;overflow:hidden;margin-bottom:5px;width:fit-content}
.resp-mode-btn{padding:2px 8px;font-size:9px;font-weight:600;border:none;background:#f8fafc;color:var(--slate);cursor:pointer;transition:all .12s}.resp-mode-btn.active{background:#581c87;color:#fff}
.sel-resp{margin-top:2px;appearance:none;cursor:pointer}
.resp-resolved{display:flex;align-items:center;gap:4px;margin-top:4px;padding:3px 6px;background:#fdf4ff;border-radius:4px;font-size:10px;font-weight:500;color:#581c87}
.del-btn{width:20px;height:20px;border:none;background:none;color:#94a3b8;cursor:pointer;font-size:16px;border-radius:4px;display:flex;align-items:center;justify-content:center;transition:all .12s;margin:auto}.del-btn:hover{background:#fef2f2;color:var(--red)}
.preuves-cell{display:flex;flex-direction:column;gap:5px}.fichiers-list{display:flex;flex-direction:column;gap:3px}
.fchip{display:flex;align-items:center;gap:4px;padding:3px 7px;border-radius:5px;font-size:10px;border:1px solid var(--border);background:#f8fafc;max-width:100%}
.fchip-pdf{background:#fff1f2;border-color:#fecdd3;color:#9f1239}.fchip-xl{background:#f0fdf4;border-color:#bbf7d0;color:#15803d}.fchip-doc{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}.fchip-img{background:#fffbeb;border-color:#fde68a;color:#92400e}.fchip-other{background:#f5f3ff;border-color:#e9d5ff;color:#7e22ce}
.fchip-icon{font-size:12px;flex-shrink:0}.fchip-name{font-size:10px;font-weight:500;text-decoration:none;color:inherit;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.fchip-name:hover{text-decoration:underline}.fchip-size{font-size:9px;color:#94a3b8;white-space:nowrap;flex-shrink:0}
.fchip-del{background:none;border:none;color:#94a3b8;cursor:pointer;font-size:14px;padding:0 2px;border-radius:3px;transition:color .1s}.fchip-del:hover{color:var(--red)}
.upload-zone{border:1.5px dashed #cbd5e1;border-radius:6px;padding:6px 8px;text-align:center;transition:all .15s;background:#fafafa;cursor:pointer}.upload-zone:hover,.drag-over{border-color:#a855f7;background:#fdf4ff}
.upload-label{display:flex;flex-direction:column;align-items:center;gap:2px;cursor:pointer}.upload-ico{font-size:16px}.upload-label span{font-size:10px;color:var(--slate)}.upload-hint{font-size:9px;color:#94a3b8}
.edit-footer{position:sticky;bottom:0;z-index:50;display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#fff;border-top:1px solid var(--border);gap:12px;box-shadow:0 -4px 12px rgba(0,0,0,.07)}
.ef-l,.ef-r{display:flex;gap:7px;align-items:center}.ef-c{flex:1;text-align:center}
.totals{font-size:11px;color:var(--slate);font-family:monospace}
.locked-pill{display:flex;align-items:center;gap:5px;padding:6px 12px;background:#f0fdf4;color:var(--green);border:1px solid #bbf7d0;border-radius:7px;font-size:12px;font-weight:600}
.w-full{width:100%}
.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9000;display:flex;align-items:center;justify-content:center;padding:16px}
.modal{background:#fff;border-radius:12px;width:100%;max-width:540px;max-height:88vh;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.3)}
.modal-lg{max-width:820px}
.modal-head{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid var(--border);font-size:13px;font-weight:700;color:var(--navy)}
.ai-head{background:linear-gradient(135deg,#1e1b4b 0%,#312e81 50%,#1e1b4b 100%);border-bottom:none}
.ai-head-title{display:flex;align-items:center;gap:8px;color:#e9d5ff;font-size:13px;font-weight:700}
.ai-spark{font-size:18px}
.modal-x{background:none;border:none;font-size:22px;cursor:pointer;color:var(--slate)}.ai-head .modal-x{color:#a5b4fc}
.modal-body{overflow-y:auto;padding:16px 18px}.modal-foot{display:flex;justify-content:flex-end;gap:8px;padding:12px 18px;border-top:1px solid var(--border)}
.ai-ctx-box{background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:10px 12px;margin-bottom:4px}
.ai-ctx-row{display:flex;gap:10px;padding:5px 0;font-size:11px;border-bottom:1px solid #f1f5f9}.ai-ctx-row:last-child{border-bottom:none}
.ai-ctx-lbl{font-weight:600;color:var(--slate);min-width:90px;flex-shrink:0}
.ai-guide-ok{color:var(--green)}.ai-guide-warn{color:#b45309}.ai-hint{color:#94a3b8;font-style:italic;margin-left:4px}
.ai-fns-preview{display:flex;flex-wrap:wrap;gap:4px}
.fn-chip{background:#ede9fe;color:#4c1d95;font-size:9px;font-weight:600;padding:2px 6px;border-radius:4px}
.fn-more{background:#f1f5f9;color:#64748b}
.ai-code{background:#ede9fe;color:#4c1d95;font-size:9px;padding:1px 5px;border-radius:3px;font-family:monospace}
.nb-row{display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-top:4px}
.nb-btn{padding:4px 12px;border:1px solid var(--border);border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;background:#f8fafc;color:var(--slate);transition:all .12s}.nb-btn.active{background:#4c1d95;color:#e9d5ff;border-color:#4c1d95}
.nb-inp{width:60px;flex-shrink:0}
.mode-row{display:flex;gap:10px;margin-top:6px}
.mode-card{flex:1;border:2px solid var(--border);border-radius:8px;padding:10px 12px;cursor:pointer;transition:all .15s;display:flex;align-items:flex-start;gap:8px}.mode-card input{display:none}.mode-card.selected{border-color:#4c1d95;background:#fdf4ff}
.mode-title{font-size:12px;font-weight:700;color:var(--navy)}.mode-desc{font-size:10px;color:var(--slate);margin-top:2px}
.ai-loading{display:flex;flex-direction:column;align-items:center;padding:30px 20px;gap:16px}
.ai-spinner-wrap{width:64px;height:64px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#4c1d95,#1e1b4b);border-radius:16px;box-shadow:0 8px 24px rgba(76,29,149,.4)}
.ai-spinner{font-size:32px;animation:spin-ai 2s linear infinite}
@keyframes spin-ai{to{transform:rotate(360deg)}}
.ai-loading-title{font-size:14px;font-weight:700;color:var(--navy)}
.ai-loading-steps{display:flex;flex-direction:column;gap:8px;width:100%;max-width:320px}
.ai-ls{display:flex;align-items:center;gap:8px;font-size:11px;color:#94a3b8;padding:6px 10px;border-radius:6px;transition:all .3s}
.ai-ls.active{color:#4c1d95;background:#fdf4ff;font-weight:600}
.ai-ls.done{color:var(--green)}.ai-ls.done .ai-ls-dot{background:var(--green)}
.ai-ls-dot{width:6px;height:6px;border-radius:50%;background:#cbd5e1;flex-shrink:0;transition:background .3s}
.ai-ls.active .ai-ls-dot{background:#4c1d95;animation:pulse-dot .8s ease-in-out infinite}
@keyframes pulse-dot{0%,100%{opacity:1}50%{opacity:.3}}
.ai-review-header{display:flex;align-items:center;gap:8px;margin-bottom:10px;flex-wrap:wrap}
.ai-count-badge{font-size:11px;font-weight:600;color:#4c1d95;background:#ede9fe;padding:3px 8px;border-radius:10px;margin-right:auto}
.ai-list{display:flex;flex-direction:column;gap:8px;max-height:420px;overflow-y:auto}
.ai-card{border:1px solid var(--border);border-radius:8px;overflow:hidden;transition:opacity .15s}.ai-excluded{opacity:.38}
.ai-card-head{display:flex;align-items:center;gap:8px;padding:8px 10px;background:#fdf4ff;border-bottom:1px solid #e9d5ff}
.ai-check-label{display:flex;align-items:center;gap:6px;cursor:pointer}
.ai-ref{font-family:monospace;font-size:10px;font-weight:700;color:#4c1d95;background:#ede9fe;padding:1px 6px;border-radius:4px}
.ai-intitule{font-size:11px;color:var(--navy);flex:1;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ai-card-body{display:grid;grid-template-columns:1fr 1fr;gap:6px;padding:8px 10px}
.ai-field{display:flex;flex-direction:column;gap:3px}.ai-field label{font-size:9px;font-weight:700;text-transform:uppercase;color:var(--slate)}.ai-full{grid-column:1/-1}
.icon-picker{display:flex;flex-wrap:wrap;gap:4px;margin-top:4px}
.ico-btn{width:28px;height:28px;border:1px solid var(--border);border-radius:6px;font-size:14px;cursor:pointer;background:#f8fafc;transition:all .12s;display:flex;align-items:center;justify-content:center}.ico-btn:hover,.ico-sel{background:#fdf4ff;border-color:#a855f7}
.color-picker{display:flex;flex-wrap:wrap;gap:5px;margin-top:4px}
.col-btn{width:22px;height:22px;border-radius:50%;border:2px solid transparent;cursor:pointer;transition:all .12s}.col-btn:hover,.col-sel{border-color:#0f172a;transform:scale(1.15)}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 13px;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .15s;text-decoration:none;white-space:nowrap}.btn:disabled{opacity:.45;cursor:not-allowed}
.btn-ghost{background:#fff;color:var(--slate);border:1px solid var(--border)}.btn-ghost:hover:not(:disabled){background:#f8fafc}
.btn-primary{background:#581c87;color:#fff}.btn-primary:hover:not(:disabled){background:#6b21a8}
.btn-outline{background:#fff;color:var(--navy);border:1px solid var(--border)}.btn-outline:hover:not(:disabled){background:#f8fafc}
.btn-submit{background:#1d4ed8;color:#fff}.btn-submit:hover:not(:disabled){background:#1e40af}
.btn-validate{background:var(--green);color:#fff}.btn-validate:hover:not(:disabled){background:#166534}
.btn-reject{background:#fff;color:var(--red);border:1px solid #fecaca}.btn-reject:hover:not(:disabled){background:#fef2f2}
.btn-ai{background:linear-gradient(135deg,#4c1d95,#1e40af);color:#e9d5ff;border:none}.btn-ai:hover:not(:disabled){opacity:.88}
.btn-sm{padding:5px 10px;font-size:11px}
.toast{position:fixed;bottom:70px;right:14px;z-index:9999;padding:9px 14px;border-radius:8px;font-size:12px;font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.15)}
.toast-ok{background:#f0fdf4;color:var(--green);border:1px solid #bbf7d0}.toast-err{background:#fef2f2;color:var(--red);border:1px solid #fecaca}
.t-enter-active,.t-leave-active{transition:all .25s}.t-enter-from,.t-leave-to{transform:translateX(20px);opacity:0}
.spin,.spin-sm{border-radius:50%;animation:sp .5s linear infinite;display:inline-block;flex-shrink:0;border:2px solid currentColor;border-top-color:transparent}
.spin{width:12px;height:12px}.spin-sm{width:10px;height:10px}
@keyframes sp{to{transform:rotate(360deg)}}
</style>