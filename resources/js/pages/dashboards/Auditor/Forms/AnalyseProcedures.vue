<template>
  <VerticalLayoutAudit>
    <div class="apt-shell">

      <!-- ══ HEADER ══ -->
      <header class="apt-header">
        <div class="apt-hrow">
          <a :href="props.backUrl" class="apt-back"><i class="ti ti-arrow-left"></i></a>
          <div class="apt-hinfo">
            <div class="apt-chips">
              <code class="apt-code">{{ mission?.code ?? '—' }}</code>
              <span class="apt-chip" :class="`chip-${apt.validation_status||'draft'}`">
                <i :class="vstIcon(apt.validation_status||'draft')"></i>
                {{ vstLbl(apt.validation_status||'draft') }}
              </span>
              <span class="apt-chip chip-type">APT — Test des Procédures</span>
              <span v-if="props.auditorRole" class="apt-chip" :class="`chip-role-${props.auditorRole}`">
                {{ props.auditorRole }}
              </span>
            </div>
            <h1 class="apt-title">Analyse des Procédures de Test — Audit Interne</h1>
            <div class="apt-meta">
              <span v-if="mission?.title"><i class="ti ti-clipboard"></i>{{ mission.title }}</span>
              <span v-if="mission?.entity_name"><i class="ti ti-building"></i>{{ mission.entity_name }}</span>
              <span v-for="aud in (props.auditeurs as any[])" :key="aud.auditeur_id"
                    :class="['aud-chip','aud-'+(aud.role||'X').toLowerCase()]">
                <i class="ti ti-user"></i>{{ aud.full_name }}<code>{{ aud.role||'?' }}</code>
              </span>
            </div>
          </div>
          <div class="apt-hactions">
            <div class="apt-prog" v-if="apt.id">
              <div class="prog-track"><div class="prog-fill" :style="{width:globalFillPct+'%'}"></div></div>
              <span class="prog-pct">{{ globalFillPct }}%</span>
            </div>
            <button class="btn btn-legend" @click="showLegend=true"><i class="ti ti-info-circle"></i></button>
          </div>
        </div>
        <div v-if="apt.validation_status==='validated'" class="apt-banner banner-lock">
          <i class="ti ti-lock"></i> Formulaire <strong>validé</strong> — lecture seule
        </div>
        <div v-else-if="apt.validation_status==='in_review'" class="apt-banner banner-review">
          <i class="ti ti-clock"></i> En attente de validation
          <span v-if="canManage"> · Vous pouvez valider ou rejeter.</span>
        </div>
        <!-- Tabs principaux -->
        <div class="apt-tabs">
          <button v-for="tab in MAIN_TABS" :key="tab.key"
                  :class="['apt-tab',{active:activeTab===tab.key}]"
                  @click="activeTab=tab.key">
            <i class="ti" :class="tab.icon"></i>
            <span>{{ tab.label }}</span>
            <span v-if="tab.key==='PROC' && procedures.length" class="tab-ct">{{ procedures.length }}</span>
          </button>
        </div>
      </header>

      <!-- ══ BODY ══ -->
      <div class="apt-body">

        <!-- ─── TAB: Identification mission ─── -->
        <div v-show="activeTab==='INFO'" class="tab-pane">
          <div class="apt-grid2">
            <div class="col-forms">
              <!-- Info mission -->
             

              <!-- Upload document IA -->
              <section class="card card-ai-upload">
                <div class="card-label" style="background:linear-gradient(135deg,#6d28d9,#7c3aed);color:#fff;border-color:#6d28d9">
                  <i class="ti ti-brain"></i> Analyse IA de document procédure
                </div>
                <div class="card-body">
                  <p class="ai-upload-hint">
                    Uploadez un document de procédure (PDF, Word, Image). L'IA génère automatiquement la matrice de test, le plan de collecte, la grille d'entretien et le diagramme BPMN — injectés dans la procédure active.
                  </p>
                  <div :class="['dropzone-ai',isDragOverAi?'dz-active':'',docAnalyzing?'dz-loading':'']"
                       @dragover.prevent="isDragOverAi=true"
                       @dragleave="isDragOverAi=false"
                       @drop.prevent="onDropDoc"
                       @click="!docAnalyzing&&triggerDocFile()">
                    <div v-if="docAnalyzing" class="dz-analyzing">
                      <div class="ai-spinner-lg"></div>
                      <span>Analyse en cours…</span>
                      <small>30 à 60 secondes selon la complexité</small>
                    </div>
                    <template v-else>
                      <i class="ti ti-brain dz-ico-ai"></i>
                      <span>Glisser ou <strong>cliquer pour parcourir</strong></span>
                      <small>PDF, Word (.docx), Image, TXT — max 20 Mo</small>
                    </template>
                    <input ref="docFileRef" type="file" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.txt" class="hidden" @change="onDocFileSelect" />
                  </div>
                  <div v-if="lastAnalyzedDoc" class="analyzed-doc-info">
                    <i class="ti ti-circle-check" style="color:#15803d"></i>
                    <span>Analysé : <strong>{{ lastAnalyzedDoc }}</strong></span>
                    <button class="btn-clear-analysis" @click="lastAnalyzedDoc=''" title="Effacer"><i class="ti ti-x"></i></button>
                  </div>
                  <!-- Sélecteur procédure cible -->
                  <div v-if="procedures.length" class="field" style="margin-top:8px">
                    <label class="lbl">Injecter les données dans la procédure</label>
                    <select class="inp inp-sm" v-model="docTargetProcIdx">
                      <option v-for="(p,pi) in procedures" :key="p._k" :value="pi">
                        {{ pi+1 }}. {{ p.intitule||'Procédure '+(pi+1) }}
                      </option>
                    </select>
                  </div>
                </div>
              </section>
               <!-- Pièces jointes globales -->
              <section class="card">
                <div class="card-label"><i class="ti ti-folder-open"></i> Pièces jointes globales</div>
                <div class="card-body">
                  <div :class="['dropzone',isDragOver?'dz-active':'',isLocked?'dz-locked':'']"
                       @dragover.prevent="isDragOver=true" @dragleave="isDragOver=false"
                       @drop.prevent="onDrop" @click="!isLocked&&triggerFile()">
                    <i class="ti ti-cloud-upload dz-ico"></i>
                    <span v-if="isLocked">Lecture seule</span>
                    <span v-else>Glisser ou <strong>cliquer</strong></span>
                    <small>PDF, Excel, Word — max 10 Mo</small>
                    <input ref="fileInputRef" type="file" multiple class="hidden" @change="onFileSelect" :disabled="isLocked" />
                  </div>
                  <div v-for="(f,i) in newFiles" :key="'n'+i" class="file-item">
                    <i class="ti" :class="fileIcon(f.name)"></i>
                    <span class="file-name">{{ f.name }}</span><span class="file-badge">Nouveau</span>
                    <button v-if="!isLocked" class="file-del" @click="newFiles.splice(i,1)"><i class="ti ti-trash"></i></button>
                  </div>
                  <div v-for="(f,i) in savedFiles" :key="'s'+i" class="file-item file-saved">
                    <i class="ti" :class="fileIcon(f.name)"></i>
                    <a :href="f.url" target="_blank" class="file-name file-link">{{ f.name }}</a>
                    <span class="file-size">{{ f.size_label }}</span>
                    <button v-if="!isLocked" class="file-del" @click="savedFiles.splice(i,1)"><i class="ti ti-trash"></i></button>
                  </div>
                </div>
              </section>
            </div>
            <div class="col-forms">
              <!-- Liste APT -->
              <section class="card">
                <div class="card-label"><i class="ti ti-list"></i> APT de cette mission</div>
                <div class="card-body p0">
                  <div class="p-s"><input class="inp" v-model="search" placeholder="Rechercher…" /></div>
                  <table class="tbl">
                    <thead><tr><th>Code</th><th>Procédure</th><th>Statut</th><th></th></tr></thead>
                    <tbody>
                      <tr v-if="!filteredApts.length"><td colspan="4" class="td-empty">Aucun formulaire</td></tr>
                      <tr v-for="a in filteredApts" :key="a.id" class="tbl-row" @click="loadApt(a)">
                        <td class="td-code">{{ a.code }}</td>
                        <td>{{ a.intitule_proc||'—' }}</td>
                        <td><span class="apt-chip" :class="`chip-${a.validation_status||'draft'}`">{{ vstLbl(a.validation_status||'draft') }}</span></td>
                        <td class="td-acts" @click.stop>
                          <button class="act-btn act-edit" @click.stop="loadApt(a)"><i class="ti ti-pencil"></i></button>
                          <button v-if="(a.validation_status||'draft')!=='validated'" class="act-btn act-del" @click.stop="deleteApt(a)"><i class="ti ti-trash"></i></button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </section>
             
             
            </div>
          </div>
        </div>

        <!-- ─── TAB: PROCÉDURES ─── -->
        <div v-show="activeTab==='PROC'" class="tab-pane">
          <div class="proc-layout">

            <!-- Panneau gauche: liste des procédures -->
            <div class="proc-left">
              <div class="proc-left-head">
                <span class="plh-title"><i class="ti ti-list-check"></i> Procédures testées</span>
                <button v-if="!isLocked" class="btn btn-save btn-sm" @click="addProcedure">
                  <i class="ti ti-plus"></i> Ajouter
                </button>
              </div>

              <!-- IA : suggestion de procédures -->
              <div v-if="!isLocked" class="ia-proc-box">
                <div class="ia-proc-label"><i class="ti ti-brain"></i> Suggestion IA</div>
                <div class="ia-proc-desc">Décrivez la procédure à tester et l'IA génère automatiquement sa structure complète (niveaux, matrice, BPMN).</div>
                <textarea class="ia-proc-ta" v-model="iaProcPrompt" rows="3"
                          placeholder="Ex: Procédure de passation des marchés publics pour la direction des achats de FRUITIVA…" />
                <div class="ia-proc-actions">
                  <select class="inp inp-sm" v-model="iaProcTemplate">
                    <option value="">Aucun template</option>
                    <option v-for="t in props.templates" :key="t.id" :value="t.code">{{ t.titre }}</option>
                  </select>
                  <button class="btn btn-ai btn-sm" @click="suggestProcedureIA" :disabled="iaLoading||!iaProcPrompt.trim()">
                    <i class="ti" :class="iaLoading?'ti-loader-2 spin':'ti-brain'"></i>
                    {{ iaLoading?'Analyse…':'Générer' }}
                  </button>
                </div>
              </div>

              <!-- Liste des procédures -->
              <div class="proc-list">
                <div v-if="!procedures.length" class="proc-empty">
                  <i class="ti ti-clipboard-list"></i>
                  <p>Aucune procédure — cliquez "Ajouter" ou utilisez l'IA</p>
                </div>
                <div v-for="(proc,pi) in procedures" :key="proc._k"
                     :class="['proc-card',{active:activeProcIdx===pi}]"
                     @click="selectProc(pi)">
                  <div class="proc-card-header">
                    <div class="proc-card-num">{{ pi+1 }}</div>
                    <div class="proc-card-info">
                      <div class="proc-card-title">{{ proc.intitule||'Procédure sans titre' }}</div>
                      <div class="proc-card-meta">
                        <code v-if="proc.ref_procedure" class="proc-ref">{{ proc.ref_procedure }}</code>
                        <span v-if="proc.service_dept" class="proc-svc"><i class="ti ti-building"></i>{{ proc.service_dept }}</span>
                      </div>
                    </div>
                    <div class="proc-card-status" :class="'pcs-'+proc.statut">
                      {{ {en_cours:'En cours',termine:'Terminé',suspendu:'Suspendu'}[proc.statut]||'—' }}
                    </div>
                  </div>
                  <!-- Niveaux de la procédure -->
                  <div class="proc-levels-mini" v-if="proc.levels?.length">
                    <div v-for="(lv,li) in proc.levels" :key="lv._k"
                         :class="['plm-item','plm-'+lv.statut_niveau]"
                         @click.stop="selectLevel(pi,li)">
                      <span class="plm-code">{{ lv.code_niveau }}</span>
                      <span class="plm-label">{{ lv.libelle_niveau }}</span>
                      <div class="plm-stats">
                        <span class="plm-c">{{ countLevelR(lv,'c') }}</span>
                        <span class="plm-nc">{{ countLevelR(lv,'nc') }}</span>
                        <span class="plm-pp">{{ countLevelR(lv,'pp') }}</span>
                      </div>
                      <div class="plm-docs" v-if="lv.documents?.length">
                        <i class="ti ti-files"></i>{{ lv.documents.length }}
                      </div>
                    </div>
                    <button v-if="!isLocked" class="btn-add-level" @click.stop="addLevel(pi)">
                      <i class="ti ti-plus"></i> Niveau
                    </button>
                  </div>
                  <div class="proc-card-actions" @click.stop>
                    <button v-if="!isLocked && !proc.levels?.length" class="btn btn-ghost btn-xs" @click.stop="addLevel(pi)">
                      <i class="ti ti-plus"></i> Ajouter niveaux
                    </button>
                    <button v-if="!isLocked" class="btn-icon-del" @click.stop="removeProc(pi)">
                      <i class="ti ti-trash"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Panneau droit: édition procédure active -->
            <div class="proc-right" v-if="activeProcIdx >= 0 && procedures[activeProcIdx]">
              <div class="proc-right-head">
                <div class="prh-title">
                  <i class="ti ti-clipboard-list"></i>
                  Procédure {{ activeProcIdx+1 }}
                  <code class="prh-code">{{ procedures[activeProcIdx].ref_procedure||'—' }}</code>
                </div>
                <!-- Sous-tabs de la procédure -->
                <div class="proc-subtabs">
                  <button v-for="st in PROC_SUBTABS" :key="st.key"
                          :class="['pstab',{active:activeProcSubTab===st.key}]"
                          @click="activeProcSubTab=st.key">
                    <i class="ti" :class="st.icon"></i>{{ st.label }}
                  </button>
                </div>
              </div>

              <!-- Sous-tab: Identification procédure -->
              <div v-show="activeProcSubTab==='ID'" class="proc-sub-content">
                <div class="proc-id-grid">
                  <div class="form-row2">
                    <div class="field"><label class="lbl">Référence procédure</label>
                      <input class="inp" v-model="procedures[activeProcIdx].ref_procedure" :disabled="isLocked" placeholder="PROC-001" /></div>
                    <div class="field"><label class="lbl">Version en vigueur</label>
                      <input class="inp" v-model="procedures[activeProcIdx].version_vigueur" :disabled="isLocked" placeholder="v1.0" /></div>
                  </div>
                  <div class="field"><label class="lbl">Intitulé de la procédure *</label>
                    <input class="inp" v-model="procedures[activeProcIdx].intitule" :disabled="isLocked" placeholder="Titre…" /></div>
                  <div class="form-row2">
                    <div class="field"><label class="lbl">Service / Département</label>
                      <input class="inp" v-model="procedures[activeProcIdx].service_dept" :disabled="isLocked" /></div>
                    <div class="field"><label class="lbl">Responsable processus</label>
                      <input class="inp" v-model="procedures[activeProcIdx].responsable_proc" :disabled="isLocked" /></div>
                  </div>
                  <div class="form-row2">
                    <div class="field"><label class="lbl">Date entrée en vigueur</label>
                      <input type="date" class="inp" v-model="procedures[activeProcIdx].date_entree_vigueur" :disabled="isLocked" /></div>
                    <div class="field"><label class="lbl">Dernière révision</label>
                      <input type="date" class="inp" v-model="procedures[activeProcIdx].date_derniere_revision" :disabled="isLocked" /></div>
                  </div>
                  <div class="field"><label class="lbl">Description</label>
                    <textarea class="ta" v-model="procedures[activeProcIdx].description" :disabled="isLocked" rows="3" /></div>
                  <div class="form-row2">
                    <div class="field"><label class="lbl">Population totale</label>
                      <input type="number" class="inp" v-model.number="procedures[activeProcIdx].population_totale" :disabled="isLocked" /></div>
                    <div class="field"><label class="lbl">Taille échantillon</label>
                      <input type="number" class="inp" v-model.number="procedures[activeProcIdx].taille_echantillon" :disabled="isLocked" /></div>
                  </div>
                  <div class="field">
                    <label class="lbl">Méthode d'échantillonnage</label>
                    <div v-if="!isLocked" class="radio-grp">
                      <label v-for="m in METHODES" :key="m.key" class="radio-lbl">
                        <input type="radio" :value="m.key" v-model="procedures[activeProcIdx].methode_echantillonnage" />
                        {{ m.label }}
                      </label>
                    </div>
                  </div>
                  <div class="field"><label class="lbl">Statut</label>
                    <select class="inp" v-model="procedures[activeProcIdx].statut" :disabled="isLocked">
                      <option value="en_cours">En cours</option>
                      <option value="termine">Terminé</option>
                      <option value="suspendu">Suspendu</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Sous-tab: Niveaux de test -->
              <div v-show="activeProcSubTab==='LEVELS'" class="proc-sub-content">
                <div class="level-toolbar">
                  <button v-if="!isLocked" class="btn btn-save btn-sm" @click="addLevel(activeProcIdx)">
                    <i class="ti ti-plus"></i> Ajouter un niveau
                  </button>
                  <button v-if="!isLocked && procedures[activeProcIdx].levels?.length" class="btn btn-ai btn-sm"
                          @click="suggestLevelsIA(activeProcIdx)" :disabled="iaLoading">
                    <i class="ti" :class="iaLoading?'ti-loader-2 spin':'ti-brain'"></i> Suggérer niveaux IA
                  </button>
                </div>

                <div class="levels-list">
                  <div v-if="!procedures[activeProcIdx].levels?.length" class="upload-invite upload-invite-sm">
                    <div class="ui-icon"><i class="ti ti-layers-subtract"></i></div>
                    <div class="ui-title">Aucun niveau défini</div>
                    <p class="ui-hint">Ajoutez des niveaux de test (ex: N1-Préparation, N2-Exécution, N3-Vérification).</p>
                  </div>

                  <div v-for="(lv,li) in (procedures[activeProcIdx].levels||[])" :key="lv._k"
                       :class="['level-block',{active:activeLevelIdx===li}]"
                       @click="activeLevelIdx=li">
                    <div class="level-block-header">
                      <div class="lbh-left">
                        <span class="lbh-badge" :class="'lbh-'+lv.statut_niveau">{{ lv.code_niveau }}</span>
                        <span class="lbh-title">{{ lv.libelle_niveau }}</span>
                      </div>
                      <div class="lbh-stats" v-if="(lv.items_matrice_parsed||[]).filter((r:any)=>!r.is_section).length">
                        <span class="lbs-c">{{ countLevelR(lv,'c') }} C</span>
                        <span class="lbs-nc">{{ countLevelR(lv,'nc') }} NC</span>
                        <span class="lbs-pp">{{ countLevelR(lv,'pp') }} PP</span>
                      </div>
                      <div class="lbh-docs-ct" v-if="(lv.documents||[]).length">
                        <i class="ti ti-files"></i>{{ lv.documents.length }} doc{{ lv.documents.length>1?'s':'' }}
                      </div>
                      <button v-if="!isLocked" class="btn-icon-del" @click.stop="removeLevel(activeProcIdx,li)">
                        <i class="ti ti-trash"></i>
                      </button>
                    </div>

                    <!-- Détails niveau si actif -->
                    <div v-if="activeLevelIdx===li" class="level-block-detail" @click.stop>
                      <div class="lbd-subtabs">
                        <button v-for="lst in LEVEL_SUBTABS" :key="lst.key"
                                :class="['lstab',{active:activeLevelSubTab===lst.key}]"
                                @click="activeLevelSubTab=lst.key">
                          <i class="ti" :class="lst.icon"></i>{{ lst.label }}
                          <span v-if="lst.key==='DOCS' && (lv.documents||[]).length" class="lstab-ct">{{ lv.documents.length }}</span>
                        </button>
                      </div>

                      <!-- ID du niveau -->
                      <div v-if="activeLevelSubTab==='ID'" class="lbd-form">
                        <div class="form-row2">
                          <div class="field"><label class="lbl">Code niveau</label>
                            <input class="inp" v-model="lv.code_niveau" :disabled="isLocked" placeholder="N1" /></div>
                          <div class="field"><label class="lbl">Libellé</label>
                            <input class="inp" v-model="lv.libelle_niveau" :disabled="isLocked" placeholder="Préparation…" /></div>
                        </div>
                        <div class="field"><label class="lbl">Description</label>
                          <textarea class="ta" v-model="lv.description_niveau" :disabled="isLocked" rows="2" /></div>
                        <div class="field"><label class="lbl">Objectif</label>
                          <textarea class="ta" v-model="lv.objectif_niveau" :disabled="isLocked" rows="2" /></div>
                        <div class="form-row2">
                          <div class="field"><label class="lbl">Date début</label>
                            <input type="date" class="inp" v-model="lv.date_debut" :disabled="isLocked" /></div>
                          <div class="field"><label class="lbl">Date fin</label>
                            <input type="date" class="inp" v-model="lv.date_fin" :disabled="isLocked" /></div>
                        </div>
                        <div class="field"><label class="lbl">Statut niveau</label>
                          <select class="inp" v-model="lv.statut_niveau" :disabled="isLocked">
                            <option value="non_commence">Non commencé</option>
                            <option value="en_cours">En cours</option>
                            <option value="termine">Terminé</option>
                            <option value="suspendu">Suspendu</option>
                          </select>
                        </div>
                        <div class="form-row2">
                          <div class="field"><label class="lbl">Fait par</label>
                            <input class="inp" v-model="lv.fait_par" :disabled="isLocked" /></div>
                          <div class="field"><label class="lbl">Revu par</label>
                            <input class="inp" v-model="lv.revue_par" :disabled="isLocked" /></div>
                        </div>
                        <div class="field"><label class="lbl">Observations</label>
                          <textarea class="ta" v-model="lv.observations" :disabled="isLocked" rows="2" /></div>
                        <div class="field"><label class="lbl">Recommandations</label>
                          <textarea class="ta" v-model="lv.recommandations" :disabled="isLocked" rows="2" /></div>
                      </div>

                      <!-- Matrice du niveau -->
                      <div v-if="activeLevelSubTab==='MAT'" class="lbd-matrice">
                        <div class="lbd-toolbar">
                          <label v-if="!isLocked" class="btn btn-import btn-sm">
                            <i class="ti ti-upload"></i> Excel
                            <input type="file" accept=".xlsx,.xls" class="hidden" @change="e=>importLevelExcel(e as Event,activeProcIdx,li,'B')" />
                          </label>
                          <button v-if="!isLocked" class="btn btn-ai btn-sm" @click="suggestMatriceIA(activeProcIdx,li)" :disabled="iaLoading">
                            <i class="ti" :class="iaLoading?'ti-loader-2 spin':'ti-brain'"></i> IA
                          </button>
                          <div class="lbd-stats" v-if="(lv.items_matrice_parsed||[]).filter((r:any)=>!r.is_section).length">
                            <span class="stat-c">{{ countLevelR(lv,'c') }} C</span>·
                            <span class="stat-nc">{{ countLevelR(lv,'nc') }} NC</span>·
                            <span class="stat-pp">{{ countLevelR(lv,'pp') }} PP</span>
                          </div>
                        </div>
                        <div v-if="!(lv.items_matrice_parsed||[]).length" class="upload-invite upload-invite-sm">
                          <div class="ui-icon"><i class="ti ti-layout-rows"></i></div>
                          <div class="ui-title">Matrice vide</div>
                          <p class="ui-hint">Importez un Excel ou utilisez l'IA pour générer la matrice de ce niveau.</p>
                        </div>
                        <div v-else class="tbl-wrap-b">
                          <table class="btbl">
                            <thead><tr>
                              <th class="th-num">N°</th>
                              <th class="th-point">Point de contrôle</th>
                              <th class="th-oc">OC</th><th class="th-oa">OA</th>
                              <th class="th-nat">Nature</th><th class="th-ctrlb">Ctrl?</th>
                              <th class="th-prev">Preuve / Doc</th>
                              <th class="th-obs">Observation</th>
                              <th class="th-res">Résultat</th>
                            </tr></thead>
                            <tbody>
                              <template v-for="(row,ri) in (lv.items_matrice_parsed||[])" :key="ri">
                                <tr v-if="row.is_section" class="row-section">
                                  <td colspan="9"><div class="section-hd"><i class="ti ti-layout-rows"></i>{{ row.section }}</div></td>
                                </tr>
                                <tr v-else class="row-b" :class="rowClass(row)">
                                  <td class="td-num-b">{{ row.num }}</td>
                                  <td class="td-point">{{ row.point_controle }}</td>
                                  <td><code class="badge-oc">{{ row.obj_controle }}</code></td>
                                  <td><code class="badge-oa">{{ row.obj_audit }}</code></td>
                                  <td class="td-nat">
                                    <div v-if="!isLocked" class="btn-grp">
                                      <button :class="['ynb','ynb-fort',{active:row.nature==='fort'}]" @click="row.nature=row.nature==='fort'?null:'fort'">F</button>
                                      <button :class="['ynb','ynb-faib',{active:row.nature==='faible'}]" @click="row.nature=row.nature==='faible'?null:'faible'">f</button>
                                    </div>
                                    <span v-else :class="['badge-nat',row.nature==='fort'?'nat-fort':'nat-faib']">{{ row.nature?(row.nature==='fort'?'Fort':'Faible'):'—' }}</span>
                                  </td>
                                  <td class="td-ctrlb">
                                    <div v-if="!isLocked" class="btn-grp">
                                      <button :class="['ynb','ynb-oui',{active:row.controle_present==='oui'}]" @click="row.controle_present=row.controle_present==='oui'?null:'oui'">O</button>
                                      <button :class="['ynb','ynb-non',{active:row.controle_present==='non'}]" @click="row.controle_present=row.controle_present==='non'?null:'non'">N</button>
                                    </div>
                                    <span v-else :class="['badge-ctrl',row.controle_present==='oui'?'ctrl-oui':'ctrl-non']">{{ row.controle_present?(row.controle_present==='oui'?'O':'N'):'—' }}</span>
                                  </td>
                                  <td>
                                    <!-- Document lié à ce point de contrôle -->
                                    <div class="preuve-cell">
                                      <input v-if="!isLocked" class="c-inp" v-model="row.preuve" placeholder="Réf…" />
                                      <span v-else class="ro-sm">{{ row.preuve||'—' }}</span>
                                      <!-- Badge doc si document attaché -->
                                      <button v-if="!isLocked" class="btn-attach-doc"
                                              :class="{active: row.document_id}"
                                              @click="openDocPicker(activeProcIdx,li,ri)"
                                              title="Attacher un document">
                                        <i class="ti ti-paperclip"></i>
                                        <span v-if="row.document_id" class="attached-badge">1</span>
                                      </button>
                                    </div>
                                  </td>
                                  <td><textarea v-if="!isLocked" class="c-ta" v-model="row.observation" rows="2" placeholder="Constat…" /><span v-else class="ro-sm">{{ row.observation||'—' }}</span></td>
                                  <td class="td-res">
                                    <div v-if="!isLocked" class="btn-grp-v">
                                      <button :class="['ynb','ynb-c',{active:row.resultat==='c'}]"  @click="row.resultat=row.resultat==='c'?null:'c'">C</button>
                                      <button :class="['ynb','ynb-nc',{active:row.resultat==='nc'}]" @click="row.resultat=row.resultat==='nc'?null:'nc'">NC</button>
                                      <button :class="['ynb','ynb-pp',{active:row.resultat==='pp'}]" @click="row.resultat=row.resultat==='pp'?null:'pp'">PP</button>
                                    </div>
                                    <span v-else :class="['badge-res','res-'+row.resultat]">{{ row.resultat?.toUpperCase()||'—' }}</span>
                                  </td>
                                </tr>
                              </template>
                            </tbody>
                          </table>
                        </div>
                      </div>

                      <!-- Documents du niveau -->
                      <div v-if="activeLevelSubTab==='DOCS'" class="lbd-docs">
                        <div class="lbd-toolbar">
                          <label v-if="!isLocked" class="btn btn-import btn-sm">
                            <i class="ti ti-upload"></i> Ajouter document
                            <input type="file" multiple class="hidden" @change="e=>addLevelDoc(e as Event,activeProcIdx,li)" :disabled="isLocked" />
                          </label>
                          <span class="lbd-doc-hint">Documents spécifiques au niveau <strong>{{ lv.code_niveau }}</strong></span>
                        </div>
                        <div v-if="!(lv.documents||[]).length" class="upload-invite upload-invite-sm">
                          <div class="ui-icon"><i class="ti ti-file-upload"></i></div>
                          <div class="ui-title">Aucun document pour ce niveau</div>
                          <p class="ui-hint">Chaque niveau peut avoir ses propres documents de référence, preuves et annexes.</p>
                        </div>
                        <div v-else class="docs-grid">
                          <div v-for="(doc,di) in (lv.documents||[])" :key="di" class="doc-card">
                            <div class="doc-card-icon"><i class="ti" :class="fileIcon(doc.name)"></i></div>
                            <div class="doc-card-info">
                              <div class="doc-name">{{ doc.original_name||doc.name }}</div>
                              <div class="doc-meta">
                                <select v-if="!isLocked && !doc.id" class="inp inp-xs" v-model="doc.type_document">
                                  <option value="">Type…</option>
                                  <option value="procedure">Procédure</option>
                                  <option value="preuve">Preuve</option>
                                  <option value="rapport">Rapport</option>
                                  <option value="annexe">Annexe</option>
                                  <option value="formulaire">Formulaire</option>
                                  <option value="autre">Autre</option>
                                </select>
                                <span v-else class="doc-type-badge">{{ doc.type_document||'doc' }}</span>
                                <span class="doc-size">{{ doc.size_label }}</span>
                              </div>
                              <input v-if="!isLocked && !doc.id" class="c-inp" v-model="doc.ref_interne" placeholder="Référence interne…" />
                            </div>
                            <div class="doc-card-actions">
                              <a v-if="doc.url" :href="doc.url" target="_blank" class="btn-icon-view"><i class="ti ti-eye"></i></a>
                              <button v-if="!isLocked" class="btn-icon-del" @click="removeLevelDoc(activeProcIdx,li,di)"><i class="ti ti-trash"></i></button>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Collecte du niveau -->
                      <div v-if="activeLevelSubTab==='COLL'" class="lbd-collecte">
                        <div class="lbd-toolbar">
                          <button v-if="!isLocked" class="btn btn-ai btn-sm" @click="suggestCollecteIA(activeProcIdx,li)" :disabled="iaLoading">
                            <i class="ti" :class="iaLoading?'ti-loader-2 spin':'ti-brain'"></i> Suggérer collecte IA
                          </button>
                        </div>
                        <div v-if="!(lv.plan_collecte_parsed||[]).length" class="upload-invite upload-invite-sm">
                          <div class="ui-icon"><i class="ti ti-files"></i></div>
                          <div class="ui-title">Plan de collecte vide</div>
                          <p class="ui-hint">Utilisez l'IA ou remplissez manuellement le plan de collecte de ce niveau.</p>
                        </div>
                        <div v-else class="tbl-wrap-b">
                          <table class="btbl">
                            <thead><tr>
                              <th class="th-num">N°</th>
                              <th class="th-info">Information à collecter</th>
                              <th class="th-src">Source</th>
                              <th class="th-meth">Méthode</th>
                              <th class="th-stat-c">Statut</th>
                            </tr></thead>
                            <tbody>
                              <tr v-for="(row,ri) in (lv.plan_collecte_parsed||[])" :key="ri" class="row-b">
                                <td class="td-num-b">{{ row.num }}</td>
                                <td><input v-if="!isLocked" class="c-inp" v-model="row.information" /><span v-else class="ro-sm">{{ row.information }}</span></td>
                                <td><input v-if="!isLocked" class="c-inp" v-model="row.source" /><span v-else class="ro-sm">{{ row.source }}</span></td>
                                <td><input v-if="!isLocked" class="c-inp" v-model="row.methode_collecte" /><span v-else class="ro-sm">{{ row.methode_collecte }}</span></td>
                                <td class="td-stat-c">
                                  <div v-if="!isLocked" class="btn-grp-v">
                                    <button v-for="s in STATUTS_C" :key="s.key" :class="['ynb',{active:row.statut===s.key}]"
                                            :style="row.statut===s.key?s.activeStyle:''"
                                            @click="row.statut=row.statut===s.key?null:s.key">{{ s.label }}</button>
                                  </div>
                                  <span v-else :class="['badge-sc','sc-'+row.statut]">{{ STATUTS_C.find(s=>s.key===row.statut)?.label||'—' }}</span>
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>

                    </div><!-- /level-block-detail -->
                  </div><!-- /level-block -->
                </div>
              </div>

              <!-- Sous-tab: Appréciation procédure -->
              <div v-show="activeProcSubTab==='APPRE'" class="proc-sub-content">
                <div class="appre-grid">
                  <div v-for="grp in APPRE_GROUPS" :key="grp.key" class="appre-col">
                    <div class="appre-lbl">{{ grp.label }}</div>
                    <div v-if="!isLocked" class="radio-grp-v">
                      <label v-for="o in grp.options" :key="o.key" class="radio-lbl">
                        <input type="radio" :value="o.key" v-model="(procedures[activeProcIdx] as any)[grp.field]" />
                        <span :class="['badge-appre',o.cls]">{{ o.label }}</span>
                      </label>
                    </div>
                    <span v-else :class="['badge-appre',grp.options.find(o=>o.key===(procedures[activeProcIdx] as any)[grp.field])?.cls||'']">
                      {{ grp.options.find(o=>o.key===(procedures[activeProcIdx] as any)[grp.field])?.label||'—' }}
                    </span>
                  </div>
                </div>
                <div class="field" style="margin-top:12px">
                  <label class="lbl">Commentaire global sur la procédure</label>
                  <textarea class="ta" v-model="procedures[activeProcIdx].commentaire" :disabled="isLocked" rows="4" />
                </div>
              </div>

            </div><!-- /proc-right -->
            <div v-else class="proc-right proc-right-empty">
              <div class="pre-hint">
                <i class="ti ti-cursor-text"></i>
                <p>Sélectionnez une procédure dans la liste</p>
              </div>
            </div>
          </div>
        </div>

        <!-- ─── TAB: BPMN Diagramme ─── -->
        <div v-show="activeTab==='FC'" class="tab-pane fc-tab-pane">
          <div v-if="!bpmnHasContent && !bpmnLoading" class="upload-invite">
            <div class="ui-icon"><i class="ti ti-topology-bus"></i></div>
            <div class="ui-title">Aucun diagramme BPMN</div>
            <p class="ui-hint">L'IA génère le diagramme lors de la création d'une procédure. Vous pouvez aussi créer un diagramme vide.</p>
            <div class="ui-actions">
              <button class="btn btn-save" @click="activeTab='PROC'"><i class="ti ti-clipboard-list"></i> Gérer les procédures</button>
              <button class="btn btn-ghost" @click="initBpmnEmpty"><i class="ti ti-plus"></i> Diagramme vide</button>
            </div>
          </div>
          <div v-else class="bpmn-workspace" :class="{'bpmn-show-props':bpmnShowProps}">
            <!-- Sidebar gauche -->
            <aside class="bpmn-sidebar-left">
              <div class="bsl-section">
                <div class="bsl-title"><i class="ti ti-file-description"></i> Procédure</div>
                <div class="bsl-body">
                  <div class="bsl-proc-title">{{ activeBpmnProcTitle }}</div>
                  <div class="bsl-row"><span class="bsl-lbl">APT</span><code class="bsl-code">{{ apt.code||'—' }}</code></div>
                  <div class="bsl-row"><span class="bsl-lbl">Mission</span><span>{{ mission?.code }}</span></div>
                </div>
              </div>
              <!-- Sélecteur de procédure pour le BPMN -->
              <div class="bsl-section">
                <div class="bsl-title"><i class="ti ti-layers-subtract"></i> Procédure active</div>
                <div class="bsl-body">
                  <select class="inp inp-sm" v-model="bpmnProcIdx" @change="switchBpmnProc">
                    <option v-for="(p,pi) in procedures" :key="p._k" :value="pi">
                      {{ pi+1 }}. {{ p.intitule||'Procédure '+(pi+1) }}
                    </option>
                  </select>
                </div>
              </div>
            </aside>

            <!-- Canvas BPMN -->
            <div class="bpmn-main">
              <div class="bpmn-toolbar">
                <div class="bpmn-tb-left">
                  <span class="bpmn-tb-label"><i class="ti ti-topology-bus"></i> Éditeur BPMN</span>
                  <div class="bpmn-tb-sep"></div>
                  <button class="btn btn-ghost btn-sm" @click="bpmnZoomIn"><i class="ti ti-zoom-in"></i></button>
                  <button class="btn btn-ghost btn-sm" @click="bpmnZoomOut"><i class="ti ti-zoom-out"></i></button>
                  <button class="btn btn-ghost btn-sm" @click="bpmnFit"><i class="ti ti-maximize"></i></button>
                  <div class="bpmn-tb-sep"></div>
                  <button class="btn btn-ghost btn-sm" @click="bpmnUndo"><i class="ti ti-arrow-back-up"></i></button>
                  <button class="btn btn-ghost btn-sm" @click="bpmnRedo"><i class="ti ti-arrow-forward-up"></i></button>
                </div>
                <div class="bpmn-tb-right">
                  <div class="bpmn-save-pill" :class="bpmnSaveStatus">
                    <i :class="bpmnSaveStatus==='saving'?'ti ti-loader-2 spin':bpmnSaveStatus==='saved'?'ti ti-check':'ti ti-circle-dotted'"></i>
                    <span>{{ bpmnSaveStatus==='saving'?'Sauvegarde…':bpmnSaveStatus==='saved'?'Sauvegardé':'Auto-save' }}</span>
                  </div>
                  <button class="btn btn-ghost btn-sm" @click="bpmnShowProps=!bpmnShowProps" :class="{active:bpmnShowProps}">
                    <i class="ti ti-layout-sidebar-right"></i>
                  </button>
                  <button v-if="!isLocked" class="btn btn-ai btn-sm" @click="exportBpmnXml">
                    <i class="ti ti-download"></i> XML
                  </button>
                  <button v-if="!isLocked" class="btn btn-ghost btn-sm btn-danger-sm" @click="clearBpmn">
                    <i class="ti ti-trash"></i>
                  </button>
                </div>
              </div>
              <div class="bpmn-canvas-wrap">
                <div v-if="bpmnLoading" class="bpmn-loader">
                  <div class="bpmn-loader-ring"></div>
                  <p>Chargement du diagramme BPMN…</p>
                </div>
                <div ref="bpmnContainer" class="bpmn-container" :style="isLocked?'pointer-events:none;opacity:.85':''"></div>
              </div>
            </div>

            <!-- Sidebar droite propriétés -->
            <transition name="slide-props">
              <aside v-if="bpmnShowProps" class="bpmn-sidebar-right">
                <div class="bsr-head">
                  <span class="bsr-title"><i class="ti ti-settings"></i> Propriétés</span>
                  <button @click="bpmnShowProps=false" class="bsr-close"><i class="ti ti-x"></i></button>
                </div>
                <div class="bsr-body">
                  <div v-if="!bpmnSelectedEl" class="bsr-no-sel">
                    <i class="ti ti-cursor-text bsr-no-icon"></i>
                    <p>Cliquez sur un élément</p>
                    <div class="bsr-tips">
                      <div class="bsr-tip"><i class="ti ti-drag-drop"></i> Glissez depuis la palette</div>
                      <div class="bsr-tip"><i class="ti ti-link"></i> Connectez les éléments</div>
                    </div>
                  </div>
                  <div v-else class="bsr-props">
                    <div class="bsr-el-head">
                      <div class="bsr-el-icon"><i :class="getBpmnIcon(bpmnSelectedEl.type)"></i></div>
                      <div>
                        <div class="bsr-el-name">{{ bpmnSelectedEl.businessObject?.name||'—' }}</div>
                        <code class="bsr-el-id">{{ bpmnSelectedEl.id }}</code>
                        <span class="bsr-el-type">{{ bpmnSelectedEl.type?.replace('bpmn:','') }}</span>
                      </div>
                    </div>
                    <div class="bsr-field">
                      <label class="bsr-lbl">Nom</label>
                      <input class="bsr-inp" v-model="bpmnElName" @change="updateBpmnElName" :disabled="isLocked" />
                    </div>
                    <div class="bsr-field">
                      <label class="bsr-lbl">Couleur</label>
                      <div class="bsr-color-grid">
                        <button v-for="c in BPMN_COLORS" :key="c.hex" class="bsr-color-dot"
                                :style="{background:c.hex,outline:bpmnElColor===c.hex?'3px solid #1565C0':'none'}"
                                @click="applyBpmnColor(c.hex)" :disabled="isLocked"></button>
                      </div>
                    </div>
                    <div class="bsr-field" v-if="!isLocked">
                      <label class="bsr-lbl">Actions</label>
                      <div class="bsr-actions">
                        <button class="bsr-act-btn" @click="bpmnDeleteSelected"><i class="ti ti-trash"></i> Supprimer</button>
                        <button class="bsr-act-btn" @click="bpmnDuplicate"><i class="ti ti-copy"></i> Dupliquer</button>
                      </div>
                    </div>
                  </div>
                </div>
              </aside>
            </transition>
          </div>
        </div>

        <!-- ─── TAB: Synthèse globale ─── -->
        <div v-show="activeTab==='SYNTH'" class="tab-pane">
          <!-- Synthèse F/F globale -->
          <div class="tab-toolbar">
            <div class="tbar-l">
              <button v-if="!isLocked" class="btn btn-add-f"  @click="addSyntheseRow('force')"><i class="ti ti-plus"></i> Force</button>
              <button v-if="!isLocked" class="btn btn-add-fw" @click="addSyntheseRow('faiblesse')"><i class="ti ti-plus"></i> Faiblesse</button>
              <button v-if="!isLocked" class="btn btn-ai btn-sm" @click="autoSyntheseFromLevels" :disabled="iaLoading">
                <i class="ti" :class="iaLoading?'ti-loader-2 spin':'ti-brain'"></i> Auto depuis niveaux
              </button>
            </div>
          </div>
          <div v-if="!synthese.length" class="upload-invite upload-invite-sm">
            <div class="ui-icon"><i class="ti ti-report-analytics"></i></div>
            <div class="ui-title">Aucune synthèse globale</div>
            <p class="ui-hint">Ajoutez les forces et faiblesses consolidées ou générez-les automatiquement depuis les niveaux.</p>
          </div>
          <div v-else class="synth-section">
            <div class="synth-group">
              <div class="synth-group-hd synth-force"><i class="ti ti-shield-check"></i> Forces ({{ synthese.filter(r=>r.type==='force').length }})</div>
              <table class="btbl"><thead><tr><th>Constat</th><th>Qualification</th><th>Risque/Impact</th><th>Obj. audit</th><th v-if="!isLocked" style="width:36px"></th></tr></thead>
                <tbody>
                  <tr v-if="!synthese.filter(r=>r.type==='force').length"><td colspan="5" class="td-empty">Aucune force</td></tr>
                  <tr v-for="row in synthese.filter(r=>r.type==='force')" :key="row._k" class="row-force">
                    <td><textarea v-if="!isLocked" class="c-ta" v-model="row.constat" rows="2" /><span v-else class="ro-sm">{{row.constat}}</span></td>
                    <td class="td-qualif-s"><span class="badge-force">FORCE</span></td>
                    <td><textarea v-if="!isLocked" class="c-ta" v-model="row.risque" rows="2" /><span v-else class="ro-sm">{{row.risque}}</span></td>
                    <td><input v-if="!isLocked" class="c-inp" v-model="row.obj_audit" /><span v-else class="ro-sm">{{row.obj_audit}}</span></td>
                    <td v-if="!isLocked"><button class="act-btn act-del" @click="removeSynthese(row)"><i class="ti ti-trash"></i></button></td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="synth-group">
              <div class="synth-group-hd synth-faib"><i class="ti ti-alert-triangle"></i> Faiblesses ({{ synthese.filter(r=>r.type==='faiblesse').length }})</div>
              <table class="btbl"><thead><tr><th>Constat</th><th>Qualification</th><th>Risque/Impact</th><th>Obj. audit</th><th v-if="!isLocked" style="width:36px"></th></tr></thead>
                <tbody>
                  <tr v-if="!synthese.filter(r=>r.type==='faiblesse').length"><td colspan="5" class="td-empty">Aucune faiblesse</td></tr>
                  <tr v-for="row in synthese.filter(r=>r.type==='faiblesse')" :key="row._k" class="row-faib">
                    <td><textarea v-if="!isLocked" class="c-ta" v-model="row.constat" rows="2" /><span v-else class="ro-sm">{{row.constat}}</span></td>
                    <td class="td-qualif-s"><span class="badge-faib">FAIBLESSE</span></td>
                    <td><textarea v-if="!isLocked" class="c-ta" v-model="row.risque" rows="2" /><span v-else class="ro-sm">{{row.risque}}</span></td>
                    <td><input v-if="!isLocked" class="c-inp" v-model="row.obj_audit" /><span v-else class="ro-sm">{{row.obj_audit}}</span></td>
                    <td v-if="!isLocked"><button class="act-btn act-del" @click="removeSynthese(row)"><i class="ti ti-trash"></i></button></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <!-- Appréciation globale APT -->
          <section class="card" style="margin-top:12px">
            <div class="card-label"><i class="ti ti-chart-bar"></i> Appréciation globale APT</div>
            <div class="card-body">
              <div class="appre-grid">
                <div v-for="grp in APPRE_GROUPS" :key="grp.key" class="appre-col">
                  <div class="appre-lbl">{{ grp.label }}</div>
                  <div v-if="!isLocked" class="radio-grp-v">
                    <label v-for="o in grp.options" :key="o.key" class="radio-lbl">
                      <input type="radio" :value="o.key" v-model="(formF as any)[grp.field]" />
                      <span :class="['badge-appre',o.cls]">{{ o.label }}</span>
                    </label>
                  </div>
                  <span v-else :class="['badge-appre',grp.options.find(o=>o.key===(formF as any)[grp.field])?.cls||'']">
                    {{ grp.options.find(o=>o.key===(formF as any)[grp.field])?.label||'—' }}
                  </span>
                </div>
              </div>
              <div class="field" style="margin-top:12px">
                <label class="lbl">Conclusion et recommandations globales</label>
                <textarea class="ta" v-model="formF.commentaire_global" :disabled="isLocked" rows="5" placeholder="Conclusion…" />
              </div>
            </div>
          </section>
        </div>

        <!-- Footer -->
        <footer class="apt-footer">
          <div>
            <button v-if="!isLocked" class="btn btn-ghost" :disabled="processing" @click="annuler"><i class="ti ti-x"></i> Annuler</button>
            <button v-if="!isLocked" class="btn btn-save" :disabled="processing" @click="submit">
              <span v-if="processing" class="spin-dot"></span>
              <i v-else class="ti ti-device-floppy"></i>
              {{ apt.id?'Mettre à jour':'Enregistrer' }}
            </button>
          </div>
          <div class="footer-c">
            <span v-if="apt.id" class="saved-code"><i class="ti ti-check"></i> {{ apt.code }}</span>
            <span v-if="autoSaving" class="saved-code" style="color:#6d28d9"><i class="ti ti-loader-2 spin"></i> Sauvegarde…</span>
          </div>
          <div>
            <button v-if="apt.id && apt.validation_status==='draft'" class="btn btn-sub" :disabled="processing" @click="soumettre"><i class="ti ti-send"></i> Soumettre</button>
            <template v-if="canManage && apt.validation_status==='in_review'">
              <button class="btn btn-ok" :disabled="processing" @click="valider('validated')"><i class="ti ti-circle-check"></i> Valider</button>
              <button class="btn btn-rej" :disabled="processing" @click="promptReject"><i class="ti ti-circle-x"></i> Rejeter</button>
            </template>
          </div>
        </footer>
      </div><!-- /apt-body -->
    </div><!-- /apt-shell -->

    <!-- MODAL Légende -->
    <Teleport to="body">
      <transition name="mfade">
        <div v-if="showLegend" class="modal-ov" @click.self="showLegend=false">
          <div class="modal modal-legend">
            <div class="modal-hd">
              <div class="modal-av" style="background:#1e3a5f;color:#fff"><i class="ti ti-info-circle"></i></div>
              <div><div class="modal-title">Légende</div><div class="modal-sub">IIA 2024 · COSO 2013 · ISO 31000</div></div>
              <button class="modal-close" @click="showLegend=false"><i class="ti ti-x"></i></button>
            </div>
            <div class="modal-body">
              <div class="leg-section">Résultats Matrice</div>
              <div class="leg-row"><span class="leg-badge leg-c">C</span><span>Conforme</span></div>
              <div class="leg-row"><span class="leg-badge leg-nc">NC</span><span>Non Conforme</span></div>
              <div class="leg-row"><span class="leg-badge leg-pp">PP</span><span>Partiel</span></div>
              <div class="leg-sep"></div>
              <div class="leg-section">Statuts Niveaux</div>
              <div class="leg-row"><span class="lbh-badge lbh-non_commence" style="font-size:.7rem;padding:2px 7px">NC</span><span>Non commencé</span></div>
              <div class="leg-row"><span class="lbh-badge lbh-en_cours" style="font-size:.7rem;padding:2px 7px">EC</span><span>En cours</span></div>
              <div class="leg-row"><span class="lbh-badge lbh-termine" style="font-size:.7rem;padding:2px 7px">OK</span><span>Terminé</span></div>
              <div class="leg-sep"></div>
              <div class="leg-section">OC — Objectifs de Contrôle</div>
              <div v-for="oc in OC_LIST" :key="oc.code" class="leg-ref"><code>{{ oc.code }}</code><span>{{ oc.label }}</span></div>
            </div>
            <div class="modal-ft"><button class="btn btn-ghost" @click="showLegend=false">Fermer</button></div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- Toast -->
    <Teleport to="body">
      <transition name="toast-up">
        <div v-if="toast.show" class="toast" :class="`toast-${toast.type}`">
          <i :class="toast.type==='success'?'ti ti-circle-check':'ti ti-alert-circle'"></i>
          {{ toast.msg }}
        </div>
      </transition>
    </Teleport>

  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, reactive, computed, nextTick, watch, onMounted, onBeforeUnmount, markRaw } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

// ── Props ──────────────────────────────────────────────────────────────────
const props = defineProps({
  mission:         { type: Object, default: null },
  assignment:      { type: Object, default: null },
  form:            { type: Object, default: null },
  aptList:         { type: Array,  default: () => [] },
  proceduresData:  { type: Array,  default: () => [] },  // apt_procedures avec leurs niveaux
  syntheseFF:      { type: Array,  default: () => [] },
  flowchartData:   { type: Object, default: () => ({}) },
  savedFiles:      { type: Array,  default: () => [] },
  auditeurs:       { type: Array,  default: () => [] },
  auditorRole:     { type: String, default: null },
  missionId:       { type: Number, default: null },
  assignmentId:    { type: Number, default: null },
  currentAuditor:  { type: Object, default: null },
  templates:       { type: Array,  default: () => [] },  // apt_procedure_templates
  backUrl:         { type: String, default: '' },
  urlStore:           { type: String, default: '' },
  urlUpdate:          { type: String, default: null },
  urlDestroy:         { type: String, default: null },
  urlEdit:            { type: String, default: null },
  urlSoumettre:       { type: String, default: null },
  urlValider:         { type: String, default: null },
  urlImportExcel:     { type: String, default: '' },
  urlAiReformat:      { type: String, default: '' },
  urlAnalyzeDocument: { type: String, default: '' },
  urlAiSuggest:       { type: String, default: '' },
  urlLevelDocUpload:  { type: String, default: '' },
  urlIndex:           { type: String, default: '' },
})

// ── Constantes ─────────────────────────────────────────────────────────────
const MAIN_TABS = [
  { key: 'INFO',  label: 'Procédures',      icon: 'ti-briefcase' },
  { key: 'PROC',  label: 'Test Procédures',   icon: 'ti-clipboard-list' },
  { key: 'FC',    label: 'diagramme',         icon: 'ti-topology-bus' },
  { key: 'SYNTH', label: 'Synthèse',     icon: 'ti-report-analytics' },
]
const PROC_SUBTABS = [

  { key: 'ID',   label: 'Identification', icon: 'ti-id' },
  { key: 'DOCS', label: 'Quest d\'identification',      icon: 'ti-files' },
  { key: 'COLL', label: 'Plan Collecte',       icon: 'ti-clipboard' },
  { key: 'MAT',  label: 'Matrice',        icon: 'ti-layout-rows' },
  { key: 'APPRE',  label: 'Appréciation/Synthèse',   icon: 'ti-chart-bar' },
]

const METHODES = [
  { key: 'aleatoire',    label: 'Aléatoire simple' },
  { key: 'systematique', label: 'Systématique' },
  { key: 'jugement',     label: 'Jugement professionnel' },
  { key: 'exhaustif',    label: 'Exhaustif' },
  { key: 'autre',        label: 'Autre' },
]
const STATUTS_C = [
  { key: 'a_collecter', label: 'À col.',  activeStyle: 'background:#1565C0;color:#fff;border-color:#1565C0' },
  { key: 'obtenu',      label: 'Obtenu',  activeStyle: 'background:#15803d;color:#fff;border-color:#15803d' },
  { key: 'na',          label: 'N/A',     activeStyle: 'background:#64748b;color:#fff;border-color:#64748b' },
]
const APPRE_GROUPS = [
  { key: 'conf', field: 'niveau_conformite', label: 'Niveau de conformité', options: [
    { key: 'conforme', label: 'Conforme', cls: 'appre-conf' },
    { key: 'partiellement', label: 'Partiellement', cls: 'appre-part' },
    { key: 'non_conforme', label: 'Non conforme', cls: 'appre-nc' },
  ]},
  { key: 'rr', field: 'niveau_risque', label: 'Risque résiduel', options: [
    { key: 'faible', label: 'Faible', cls: 'appre-conf' },
    { key: 'modere', label: 'Modéré', cls: 'appre-part' },
    { key: 'eleve',  label: 'Élevé',  cls: 'appre-nc' },
    { key: 'critique', label: 'Critique', cls: 'appre-crit' },
  ]},
  { key: 'fi', field: 'fiabilite_controle', label: 'Fiabilité contrôle', options: [
    { key: 'bon',         label: 'Bon',         cls: 'appre-conf' },
    { key: 'acceptable',  label: 'Acceptable',  cls: 'appre-blue' },
    { key: 'insuffisant', label: 'Insuffisant', cls: 'appre-part' },
    { key: 'defaillant',  label: 'Défaillant',  cls: 'appre-nc' },
  ]},
  { key: 'su', field: 'suites', label: 'Suites à donner', options: [
    { key: 'aucune',          label: 'Aucune',          cls: '' },
    { key: 'recommandation',  label: 'Recommandation',  cls: '' },
    { key: 'plan_action',     label: "Plan d'action",   cls: '' },
    { key: 'escalade',        label: 'Escalade',        cls: '' },
  ]},
]
const OC_LIST = [
  { code: 'OC1', label: 'Réalité : opérations = transactions réelles' },
  { code: 'OC2', label: 'Exhaustivité : toutes opérations enregistrées' },
  { code: 'OC3', label: 'Exactitude : montants et données exacts' },
  { code: 'OC4', label: 'Autorisation : opérations approuvées' },
  { code: 'OC5', label: 'Séparation des tâches' },
  { code: 'OC6', label: 'Conservation : biens et informations protégés' },
  { code: 'OC7', label: 'Conformité réglementaire' },
]
const BPMN_COLORS = [
  { name: 'Bleu clair', hex: '#dbeafe' }, { name: 'Bleu',  hex: '#3b82f6' },
  { name: 'Vert clair', hex: '#d1fae5' }, { name: 'Vert',  hex: '#22c55e' },
  { name: 'Ambre',      hex: '#fef3c7' }, { name: 'Rouge', hex: '#ef4444' },
  { name: 'Violet',     hex: '#ede9fe' }, { name: 'Blanc', hex: '#ffffff' },
]

// ── State ──────────────────────────────────────────────────────────────────
const activeTab          = ref('INFO')
const activeProcSubTab   = ref('ID')
const activeLevelSubTab  = ref('ID')
const activeProcIdx      = ref(-1)
const activeLevelIdx     = ref(-1)
const processing         = ref(false)
const autoSaving         = ref(false)
const search             = ref('')
const isDragOver         = ref(false)
const isDragOverAi       = ref(false)
const iaLoading          = ref(false)
const docAnalyzing       = ref(false)
const lastAnalyzedDoc    = ref('')
const docFileRef         = ref<HTMLInputElement|null>(null)
const docTargetProcIdx   = ref(0)  // index procédure cible pour l'injection IA
const showLegend         = ref(false)
const iaProcPrompt       = ref('')
const iaProcTemplate     = ref('')
const fileInputRef       = ref<HTMLInputElement|null>(null)

// BPMN
const bpmnContainer  = ref<HTMLElement|null>(null)
const bpmnLoading    = ref(false)
const bpmnHasContent = ref(false)
const bpmnShowProps  = ref(true)
const bpmnSaveStatus = ref<'idle'|'saving'|'saved'|'error'>('idle')
const bpmnSelectedEl = ref<any>(null)
const bpmnElName     = ref('')
const bpmnElColor    = ref('#dbeafe')
const bpmnProcIdx    = ref(0)
let   bpmnModeler: any = null
let   bpmnAutoSaveTimer: any = null

let _sk = 0
const genKey = () => ++_sk

// ── Data réactive ──────────────────────────────────────────────────────────
const apt       = reactive<Record<string,any>>(props.form ? { ...props.form } : {})
const synthese  = reactive<any[]>((props.syntheseFF as any[]).map(r => ({ ...r, _k: genKey() })))
const newFiles  = reactive<any[]>([])
const savedFiles = reactive<any[]>(JSON.parse(JSON.stringify(props.savedFiles)))

// Procédures avec leurs niveaux — structure centrale
const procedures = reactive<any[]>(
  (props.proceduresData as any[]).map(p => ({
    ...p,
    _k: genKey(),
    levels: (p.levels || []).map((l: any) => ({
      ...l,
      _k: genKey(),
      items_matrice_parsed:  safeJson(l.items_matrice),
      plan_collecte_parsed:  safeJson(l.plan_collecte),
      grille_entretien_parsed: safeJson(l.grille_entretien),
      documents: l.documents || [],
    })),
  }))
)

// BPMN XML courant (procédure active)
const currentBpmnXml = ref<string>('')

const formF = reactive({
  niveau_conformite:  props.form?.niveau_conformite  ?? null,
  niveau_risque:      props.form?.niveau_risque      ?? null,
  fiabilite_controle: props.form?.fiabilite_controle ?? null,
  suites:             props.form?.suites             ?? null,
  commentaire_global: props.form?.commentaire_global ?? '',
})

// ── Helpers ────────────────────────────────────────────────────────────────
function safeJson(v: any): any[] {
  if (Array.isArray(v)) return v
  if (!v) return []
  try { const d = JSON.parse(v); return Array.isArray(d) ? d : [] } catch { return [] }
}

// ── Computed ───────────────────────────────────────────────────────────────
const canManage    = computed(() => ['DM','CM'].includes(props.auditorRole ?? ''))
const isLocked     = computed(() => apt.validation_status === 'validated' || (apt.validation_status === 'in_review' && !canManage.value))
const filteredApts = computed(() => {
  const q = search.value.toLowerCase()
  return (props.aptList as any[]).filter((a:any) => !q || a.code?.toLowerCase().includes(q) || a.intitule_proc?.toLowerCase().includes(q))
})
const activeBpmnProcTitle = computed(() => procedures[bpmnProcIdx.value]?.intitule || 'Procédure')
const globalFillPct = computed(() => {
  let total = 0, done = 0
  procedures.forEach(p => {
    (p.levels || []).forEach((l: any) => {
      const items = (l.items_matrice_parsed || []).filter((r: any) => !r.is_section)
      total += items.length
      done  += items.filter((r: any) => r.resultat || r.observation).length
    })
  })
  return total ? Math.round(done / total * 100) : 0
})

function countLevelR(lv: any, v: string) {
  return (lv.items_matrice_parsed || []).filter((r: any) => !r.is_section && r.resultat === v).length
}
function rowClass(row: any) {
  return row.resultat === 'c' ? 'row-c' : row.resultat === 'nc' ? 'row-nc' : row.resultat === 'pp' ? 'row-pp' : ''
}

// ── CRUD Procédures ────────────────────────────────────────────────────────
function addProcedure() {
  procedures.push({
    _k: genKey(), intitule: '', ref_procedure: '', service_dept: '',
    responsable_proc: '', version_vigueur: '', description: '',
    population_totale: null, taille_echantillon: null, methode_echantillonnage: null,
    statut: 'en_cours', bpmn_xml: '', bpmn_synthese: null,
    niveau_conformite: null, niveau_risque: null, fiabilite_controle: null, suites: null, commentaire: '',
    levels: [],
  })
  activeProcIdx.value = procedures.length - 1
  activeProcSubTab.value = 'ID'
}

function selectProc(pi: number) {
  activeProcIdx.value = pi
  activeLevelIdx.value = -1
  activeProcSubTab.value = 'ID'
}

function removeProc(pi: number) {
  if (!confirm('Supprimer cette procédure et tous ses niveaux ?')) return
  procedures.splice(pi, 1)
  if (activeProcIdx.value >= procedures.length) activeProcIdx.value = procedures.length - 1
}

// ── CRUD Niveaux ───────────────────────────────────────────────────────────
function addLevel(pi: number) {
  if (!procedures[pi].levels) procedures[pi].levels = []
  const n = procedures[pi].levels.length + 1
  procedures[pi].levels.push({
    _k: genKey(),
    code_niveau:     `N${n}`,
    libelle_niveau:  `Niveau ${n}`,
    description_niveau: '',
    objectif_niveau: '',
    statut_niveau:   'non_commence',
    resultat_global: null,
    observations: '', recommandations: '',
    items_matrice_parsed: [],
    plan_collecte_parsed: [],
    grille_entretien_parsed: [],
    documents: [],
    fait_par: '', revue_par: '',
  })
  activeProcSubTab.value = 'LEVELS'
  activeLevelIdx.value = procedures[pi].levels.length - 1
  activeLevelSubTab.value = 'ID'
}

function selectLevel(pi: number, li: number) {
  activeProcIdx.value = pi
  activeLevelIdx.value = li
  activeProcSubTab.value = 'LEVELS'
  activeLevelSubTab.value = 'MAT'
}

function removeLevel(pi: number, li: number) {
  if (!confirm('Supprimer ce niveau ?')) return
  procedures[pi].levels.splice(li, 1)
  if (activeLevelIdx.value >= procedures[pi].levels.length) activeLevelIdx.value = -1
}

// ── Documents par niveau ───────────────────────────────────────────────────
async function addLevelDoc(e: Event, pi: number, li: number) {
  const files = Array.from((e.target as HTMLInputElement).files || [])
  ;(e.target as HTMLInputElement).value = ''
  if (!files.length) return

  for (const file of files) {
    if (file.size > 20 * 1024 * 1024) { showToast('error', `${file.name} > 20 Mo`); continue }
    // Upload immédiat si APT sauvegardé, sinon attendre le submit
    if (apt.id && props.urlLevelDocUpload) {
      try {
        const fd = new FormData()
        fd.append('file', file)
        fd.append('apt_id', String(apt.id))
        fd.append('proc_idx', String(pi))
        fd.append('level_idx', String(li))
        const csrf = getCsrf()
        const res = await axios.post(props.urlLevelDocUpload, fd, {
          headers: { 'Content-Type': 'multipart/form-data', 'X-CSRF-TOKEN': csrf }
        })
        if (res.data.success) {
          procedures[pi].levels[li].documents.push(res.data.document)
          showToast('success', 'Document ajouté')
          continue
        }
      } catch (err: any) {
        showToast('error', 'Erreur upload: ' + err.message)
        continue
      }
    }
    // Ajout local (sera uploadé au submit)
    procedures[pi].levels[li].documents.push({
      _pending: true,
      _file: file,
      name: file.name,
      original_name: file.name,
      size: file.size,
      size_label: formatSize(file.size),
      type_document: '',
      ref_interne: '',
    })
  }
}

function removeLevelDoc(pi: number, li: number, di: number) {
  if (!confirm('Supprimer ce document ?')) return
  procedures[pi].levels[li].documents.splice(di, 1)
}

function openDocPicker(pi: number, li: number, ri: number) {
  // Ouvre une sélection parmi les docs du niveau pour lier au point de contrôle
  const docs = procedures[pi].levels[li].documents
  if (!docs.length) { showToast('error', 'Ajoutez d\'abord des documents à ce niveau'); return }
  // Simple prompt de sélection - en production, utiliser un modal
  const docNames = docs.map((d: any, i: number) => `${i+1}. ${d.original_name||d.name}`).join('\n')
  const choice = prompt(`Choisir un document (numéro):\n${docNames}`)
  if (!choice) return
  const idx = parseInt(choice) - 1
  if (idx >= 0 && idx < docs.length) {
    const items = procedures[pi].levels[li].items_matrice_parsed
    items[ri].document_id = docs[idx].id || null
    items[ri].preuve = docs[idx].ref_interne || docs[idx].original_name || docs[idx].name
    showToast('success', 'Document lié')
  }
}

// ── IA Suggestions ─────────────────────────────────────────────────────────
async function suggestProcedureIA() {
  if (!iaProcPrompt.value.trim()) return
  iaLoading.value = true
  try {
    const csrf = getCsrf()
    const res = await axios.post(props.urlAiSuggest, {
      type: 'procedure_complete',
      prompt: iaProcPrompt.value,
      template_code: iaProcTemplate.value || null,
      mission_id: props.missionId,
      mission_title: props.mission?.title,
      entity_name: props.mission?.entity_name,
    }, { headers: { 'X-CSRF-TOKEN': csrf } })

    if (!res.data.success) throw new Error(res.data.error || 'Erreur IA')
    const data = res.data

    // Créer la procédure avec les niveaux générés
    const newProc: any = {
      _k: genKey(),
      intitule:             data.intitule || iaProcPrompt.value,
      ref_procedure:        data.ref_procedure || '',
      service_dept:         data.service_dept || '',
      responsable_proc:     data.responsable_proc || '',
      description:          data.description || '',
      methode_echantillonnage: data.methode || 'jugement',
      population_totale:    data.population_totale || null,
      taille_echantillon:   data.taille_echantillon || null,
      statut:               'en_cours',
      bpmn_xml:             data.bpmn_xml || '',
      bpmn_synthese:        data.bpmn_synthese || null,
      niveau_conformite:    null, niveau_risque: null, fiabilite_controle: null, suites: null, commentaire: '',
      levels: (data.levels || []).map((l: any) => ({
        _k: genKey(),
        code_niveau:     l.code_niveau || 'N1',
        libelle_niveau:  l.libelle || l.libelle_niveau || '',
        description_niveau: l.description || '',
        objectif_niveau: l.objectif || '',
        statut_niveau:   'non_commence',
        resultat_global: null,
        observations: '', recommandations: '',
        items_matrice_parsed:    l.items_matrice  || [],
        plan_collecte_parsed:    l.plan_collecte  || [],
        grille_entretien_parsed: l.grille_entretien || [],
        documents: [],
        fait_par: '', revue_par: '',
      })),
    }

    procedures.push(newProc)
    activeProcIdx.value = procedures.length - 1
    activeProcSubTab.value = 'LEVELS'
    iaProcPrompt.value = ''

    // Si BPMN généré → charger dans l'éditeur
    if (newProc.bpmn_xml) {
      currentBpmnXml.value = newProc.bpmn_xml
      bpmnHasContent.value = true
      bpmnProcIdx.value    = procedures.length - 1
    }

    showToast('success', `Procédure "${newProc.intitule}" créée avec ${newProc.levels.length} niveau(x)`)
  } catch (err: any) {
    showToast('error', 'Erreur IA : ' + (err.response?.data?.error || err.message))
  } finally {
    iaLoading.value = false
  }
}

async function suggestLevelsIA(pi: number) {
  const proc = procedures[pi]
  if (!proc) return
  iaLoading.value = true
  try {
    const csrf = getCsrf()
    const res = await axios.post(props.urlAiSuggest, {
      type: 'levels_only',
      procedure_title: proc.intitule,
      procedure_description: proc.description,
      mission_id: props.missionId,
    }, { headers: { 'X-CSRF-TOKEN': csrf } })
    if (!res.data.success) throw new Error(res.data.error || 'Erreur IA')
    const levels = res.data.levels || []
    proc.levels.push(...levels.map((l: any) => ({
      _k: genKey(),
      code_niveau: l.code_niveau || 'Nx',
      libelle_niveau: l.libelle || '',
      description_niveau: l.description || '',
      objectif_niveau: l.objectif || '',
      statut_niveau: 'non_commence',
      items_matrice_parsed: l.items_matrice || [],
      plan_collecte_parsed: [],
      grille_entretien_parsed: [],
      documents: [],
    })))
    showToast('success', `${levels.length} niveaux ajoutés`)
  } catch (err: any) {
    showToast('error', 'Erreur IA : ' + err.message)
  } finally {
    iaLoading.value = false
  }
}

async function suggestMatriceIA(pi: number, li: number) {
  const proc = procedures[pi]
  const lv   = proc?.levels?.[li]
  if (!lv) return
  iaLoading.value = true
  try {
    const csrf = getCsrf()
    const res = await axios.post(props.urlAiSuggest, {
      type:              'matrice_niveau',
      procedure_title:   proc.intitule,
      niveau_code:       lv.code_niveau,
      niveau_libelle:    lv.libelle_niveau,
      niveau_description: lv.description_niveau,
      mission_id:        props.missionId,
    }, { headers: { 'X-CSRF-TOKEN': csrf } })
    if (!res.data.success) throw new Error(res.data.error || 'Erreur IA')
    lv.items_matrice_parsed = res.data.items || []
    showToast('success', `${lv.items_matrice_parsed.length} points de contrôle générés`)
  } catch (err: any) {
    showToast('error', 'Erreur IA : ' + err.message)
  } finally {
    iaLoading.value = false
  }
}

async function suggestCollecteIA(pi: number, li: number) {
  const proc = procedures[pi]
  const lv   = proc?.levels?.[li]
  if (!lv) return
  iaLoading.value = true
  try {
    const csrf = getCsrf()
    const res = await axios.post(props.urlAiSuggest, {
      type:            'collecte_niveau',
      procedure_title: proc.intitule,
      niveau_code:     lv.code_niveau,
      niveau_libelle:  lv.libelle_niveau,
      items_matrice:   lv.items_matrice_parsed,
      mission_id:      props.missionId,
    }, { headers: { 'X-CSRF-TOKEN': csrf } })
    if (!res.data.success) throw new Error(res.data.error || 'Erreur IA')
    lv.plan_collecte_parsed = res.data.items || []
    showToast('success', `Plan de collecte généré`)
  } catch (err: any) {
    showToast('error', 'Erreur IA : ' + err.message)
  } finally {
    iaLoading.value = false
  }
}

async function autoSyntheseFromLevels() {
  // Consolider les observations des niveaux en synthèse F/F via IA
  const allItems: any[] = []
  procedures.forEach(p => {
    (p.levels || []).forEach((l: any) => {
      (l.items_matrice_parsed || []).forEach((item: any) => {
        if (!item.is_section && item.observation) allItems.push(item)
      })
    })
  })
  if (!allItems.length) { showToast('error', 'Aucune observation dans les niveaux'); return }
  iaLoading.value = true
  try {
    const csrf = getCsrf()
    const res = await axios.post(props.urlAiReformat, {
      section: 'E',
      items: allItems.slice(0, 50),
      context: `Mission: ${props.mission?.title}. Consolidation des observations en synthèse F/F.`,
      mission_id: props.missionId,
    }, { headers: { 'X-CSRF-TOKEN': csrf } })
    if (!res.data.success) throw new Error(res.data.error || 'Erreur IA')
    const newSynth = (res.data.items || []).map((r: any) => ({ ...r, _k: genKey() }))
    synthese.splice(0, synthese.length, ...newSynth)
    showToast('success', `Synthèse générée : ${synthese.length} éléments`)
  } catch (err: any) {
    showToast('error', 'Erreur IA : ' + err.message)
  } finally {
    iaLoading.value = false
  }
}

// ── Import Excel niveau ────────────────────────────────────────────────────
async function importLevelExcel(e: Event, pi: number, li: number, section: string) {
  const file = (e.target as HTMLInputElement).files?.[0]
  ;(e.target as HTMLInputElement).value = ''
  if (!file) return
  const fd = new FormData()
  fd.append('file', file)
  fd.append('section', section)
  try {
    const res = await axios.post(props.urlImportExcel, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    if (res.data.success) {
      procedures[pi].levels[li].items_matrice_parsed = res.data.items || []
      showToast('success', `${res.data.items.length} lignes importées`)
    }
  } catch (err: any) {
    showToast('error', err.response?.data?.error || 'Erreur import')
  }
}

// ── Synthèse globale ───────────────────────────────────────────────────────
function addSyntheseRow(type: string) { synthese.push({ _k: genKey(), type, constat: '', risque: '', obj_audit: '' }) }
function removeSynthese(row: any) { const i = synthese.indexOf(row); if (i >= 0) synthese.splice(i, 1) }

// ── BPMN ──────────────────────────────────────────────────────────────────
async function initBpmn(xml?: string) {
  if (!bpmnContainer.value) return
  bpmnLoading.value = true
  try {
    const { default: BpmnModeler } = await import('bpmn-js/lib/Modeler')
    if (bpmnModeler) { bpmnModeler.destroy(); bpmnModeler = null }
    bpmnModeler = new BpmnModeler({ container: bpmnContainer.value })
    const xmlToLoad = xml || currentBpmnXml.value || getDefaultBpmnXml()
    await bpmnModeler.importXML(xmlToLoad)
    bpmnModeler.get('canvas').zoom('fit-viewport')
    bpmnHasContent.value = true
    currentBpmnXml.value = xmlToLoad
    applyDefaultStyles()
    setupBpmnEvents()
  } catch (err) {
    console.error('[BPMN]', err)
    showToast('error', 'Erreur BPMN')
  } finally {
    bpmnLoading.value = false
  }
}

function applyDefaultStyles() {
  if (!bpmnModeler) return
  try {
    const m = bpmnModeler.get('modeling')
    const r = bpmnModeler.get('elementRegistry')
    r.getAll().forEach((el: any) => {
      if (['bpmn:Task','bpmn:UserTask','bpmn:ServiceTask'].includes(el.type))
        m.setColor(el, { fill: '#dbeafe', stroke: '#3b82f6', strokeWidth: 2 })
      else if (el.type?.includes('StartEvent')) m.setColor(el, { fill: '#d1fae5', stroke: '#22c55e', strokeWidth: 2 })
      else if (el.type?.includes('EndEvent'))   m.setColor(el, { fill: '#fee2e2', stroke: '#ef4444', strokeWidth: 2 })
      else if (el.type?.includes('Gateway'))    m.setColor(el, { fill: '#fef3c7', stroke: '#f59e0b', strokeWidth: 2 })
    })
  } catch {}
}

function setupBpmnEvents() {
  if (!bpmnModeler) return
  const bus = bpmnModeler.get('eventBus')
  bus.on('element.click', (ev: any) => {
    const el = ev.element
    if (el.type === 'bpmn:Process') return
    bpmnSelectedEl.value = markRaw(el)
    bpmnElName.value     = el.businessObject?.name || ''
    bpmnElColor.value    = el.di?.fill || '#dbeafe'
    if (!bpmnShowProps.value) bpmnShowProps.value = true
  })
  bus.on('commandStack.changed', () => {
    if (bpmnAutoSaveTimer) clearTimeout(bpmnAutoSaveTimer)
    bpmnAutoSaveTimer = setTimeout(saveBpmnToProc, 2500)
  })
}

async function saveBpmnToProc() {
  if (!bpmnModeler) return
  try {
    bpmnSaveStatus.value = 'saving'
    const { xml } = await bpmnModeler.saveXML({ format: true })
    currentBpmnXml.value = xml
    if (procedures[bpmnProcIdx.value]) procedures[bpmnProcIdx.value].bpmn_xml = xml
    bpmnSaveStatus.value = 'saved'
    setTimeout(() => { bpmnSaveStatus.value = 'idle' }, 2500)
  } catch { bpmnSaveStatus.value = 'error' }
}

function switchBpmnProc() {
  const xml = procedures[bpmnProcIdx.value]?.bpmn_xml || ''
  if (xml) {
    currentBpmnXml.value = xml
    if (bpmnModeler) initBpmn(xml)
  }
}

async function initBpmnEmpty() {
  bpmnHasContent.value = true
  activeTab.value = 'FC'
  await nextTick()
  if (bpmnContainer.value) await initBpmn(getDefaultBpmnXml())
}

watch(activeTab, async (tab) => {
  if (tab === 'FC') {
    await nextTick()
    if (bpmnHasContent.value && bpmnModeler) return
    const xml = procedures[bpmnProcIdx.value]?.bpmn_xml || currentBpmnXml.value
    if (xml && bpmnContainer.value) await initBpmn(xml)
  }
})

function bpmnZoomIn()  { bpmnModeler?.get('canvas').zoom(bpmnModeler.get('canvas').zoom() * 1.2) }
function bpmnZoomOut() { bpmnModeler?.get('canvas').zoom(bpmnModeler.get('canvas').zoom() * 0.8) }
function bpmnFit()     { bpmnModeler?.get('canvas').zoom('fit-viewport') }
function bpmnUndo()    { bpmnModeler?.get('commandStack').undo() }
function bpmnRedo()    { bpmnModeler?.get('commandStack').redo() }
function updateBpmnElName() {
  if (!bpmnSelectedEl.value || !bpmnModeler) return
  bpmnModeler.get('modeling').updateProperties(bpmnSelectedEl.value, { name: bpmnElName.value })
}
function applyBpmnColor(color: string) {
  if (!bpmnSelectedEl.value || !bpmnModeler || isLocked.value) return
  bpmnElColor.value = color
  try {
    const n = parseInt(color.slice(1), 16)
    const dr = Math.max(0, (n >> 16) - 40)
    const dg = Math.max(0, ((n >> 8) & 0xff) - 40)
    const db = Math.max(0, (n & 0xff) - 40)
    const stroke = '#' + [dr, dg, db].map(v => v.toString(16).padStart(2, '0')).join('')
    bpmnModeler.get('modeling').setColor(bpmnSelectedEl.value, { fill: color, stroke, strokeWidth: 2 })
  } catch {}
}
function bpmnDeleteSelected() {
  if (!bpmnSelectedEl.value || !bpmnModeler) return
  bpmnModeler.get('modeling').removeShape(bpmnSelectedEl.value)
  bpmnSelectedEl.value = null
}
function bpmnDuplicate() {
  if (!bpmnSelectedEl.value || !bpmnModeler) return
  try {
    const cv = bpmnModeler.get('canvas')
    const g  = cv.getGraphics(bpmnSelectedEl.value)
    bpmnModeler.get('modeling').copyShape(bpmnSelectedEl.value, { x: (g?.x||0)+50, y: (g?.y||0)+50 }, bpmnSelectedEl.value.parent || cv.getRootElement())
  } catch {}
}
async function exportBpmnXml() {
  if (!bpmnModeler) return
  const { xml } = await bpmnModeler.saveXML({ format: true })
  const a = document.createElement('a')
  a.href = 'data:application/xml;charset=utf-8,' + encodeURIComponent(xml)
  a.download = `APT-BPMN-${apt.code||Date.now()}.bpmn`
  a.click()
}
async function clearBpmn() {
  if (!confirm('Effacer le diagramme BPMN de cette procédure ?')) return
  currentBpmnXml.value = ''
  if (procedures[bpmnProcIdx.value]) procedures[bpmnProcIdx.value].bpmn_xml = ''
  bpmnHasContent.value = false
  bpmnSelectedEl.value = null
  if (bpmnModeler) { bpmnModeler.destroy(); bpmnModeler = null }
}
function getBpmnIcon(type: string) {
  if (!type) return 'ti ti-shapes'
  if (type.includes('StartEvent'))  return 'ti ti-player-play'
  if (type.includes('EndEvent'))    return 'ti ti-player-stop'
  if (type.includes('Gateway'))     return 'ti ti-share'
  if (type.includes('Task'))        return 'ti ti-checkbox'
  return 'ti ti-shapes'
}
function getDefaultBpmnXml() {
  const name = (procedures[bpmnProcIdx.value]?.intitule || 'Procédure').replace(/[<>&'"]/g, '')
  return `<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL" xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI" xmlns:dc="http://www.omg.org/spec/DD/20100524/DC" xmlns:di="http://www.omg.org/spec/DD/20100524/DI" id="Definitions_1" targetNamespace="http://bpmn.io/schema/bpmn">
  <bpmn:process id="Process_1" name="${name}" isExecutable="false">
    <bpmn:startEvent id="Start_1" name="Début"><bpmn:outgoing>Flow_1</bpmn:outgoing></bpmn:startEvent>
    <bpmn:task id="Task_1" name="Étape 1"><bpmn:incoming>Flow_1</bpmn:incoming><bpmn:outgoing>Flow_2</bpmn:outgoing></bpmn:task>
    <bpmn:endEvent id="End_1" name="Fin"><bpmn:incoming>Flow_2</bpmn:incoming></bpmn:endEvent>
    <bpmn:sequenceFlow id="Flow_1" sourceRef="Start_1" targetRef="Task_1"/>
    <bpmn:sequenceFlow id="Flow_2" sourceRef="Task_1" targetRef="End_1"/>
  </bpmn:process>
  <bpmndi:BPMNDiagram id="Diagram_1"><bpmndi:BPMNPlane id="Plane_1" bpmnElement="Process_1">
    <bpmndi:BPMNShape id="S_start" bpmnElement="Start_1"><dc:Bounds x="152" y="82" width="36" height="36"/></bpmndi:BPMNShape>
    <bpmndi:BPMNShape id="S_task" bpmnElement="Task_1"><dc:Bounds x="250" y="60" width="120" height="80"/></bpmndi:BPMNShape>
    <bpmndi:BPMNShape id="S_end" bpmnElement="End_1"><dc:Bounds x="432" y="82" width="36" height="36"/></bpmndi:BPMNShape>
    <bpmndi:BPMNEdge id="E_1" bpmnElement="Flow_1"><di:waypoint x="188" y="100"/><di:waypoint x="250" y="100"/></bpmndi:BPMNEdge>
    <bpmndi:BPMNEdge id="E_2" bpmnElement="Flow_2"><di:waypoint x="370" y="100"/><di:waypoint x="432" y="100"/></bpmndi:BPMNEdge>
  </bpmndi:BPMNPlane></bpmndi:BPMNDiagram>
</bpmn:definitions>`
}

// ── Lifecycle ──────────────────────────────────────────────────────────────
onMounted(() => {
  // Vérifier si des procédures ont des BPMN
  const firstBpmn = procedures.find(p => p.bpmn_xml)
  if (firstBpmn) {
    bpmnHasContent.value = true
    currentBpmnXml.value = firstBpmn.bpmn_xml
    bpmnProcIdx.value    = procedures.indexOf(firstBpmn)
  }
})
onBeforeUnmount(() => {
  if (bpmnAutoSaveTimer) clearTimeout(bpmnAutoSaveTimer)
  if (bpmnModeler) { bpmnModeler.destroy(); bpmnModeler = null }
})

// ── Fichiers globaux ───────────────────────────────────────────────────────
function triggerFile() { fileInputRef.value?.click() }
function onDrop(e: DragEvent) { isDragOver.value=false; if(isLocked.value) return; Array.from(e.dataTransfer?.files||[]).forEach(addFile) }
function onFileSelect(e: Event) { Array.from((e.target as HTMLInputElement).files||[]).forEach(addFile); (e.target as HTMLInputElement).value='' }
function addFile(f: File) { if(f.size>10485760){showToast('error',`${f.name} > 10 Mo`);return}; newFiles.push({name:f.name,size:f.size,file:f}) }
function fileIcon(n: string) {
  const e=n.split('.').pop()?.toLowerCase()
  return e==='pdf'?'ti-file-type-pdf':['xlsx','xls'].includes(e||'')?'ti-file-type-xls':['docx','doc'].includes(e||'')?'ti-file-type-doc':'ti-file'
}

// ── Analyse IA document procédure ─────────────────────────────────────────
function triggerDocFile() { docFileRef.value?.click() }
async function onDropDoc(e: DragEvent) {
  isDragOverAi.value = false
  const f = e.dataTransfer?.files?.[0]
  if (f) await analyzeDoc(f)
}
async function onDocFileSelect(e: Event) {
  const f = (e.target as HTMLInputElement).files?.[0]
  if (f) await analyzeDoc(f)
  ;(e.target as HTMLInputElement).value = ''
}

async function analyzeDoc(file: File) {
  if (file.size > 20 * 1024 * 1024) { showToast('error', 'Fichier > 20 Mo'); return }
  docAnalyzing.value = true
  try {
    const fd = new FormData()
    fd.append('document', file)
    if (props.missionId) fd.append('mission_id', String(props.missionId))
    // Titre de la procédure cible si elle existe
    const targetProc = procedures[docTargetProcIdx.value]
    if (targetProc?.intitule) fd.append('procedure_title', targetProc.intitule)

    const res = await axios.post(props.urlAnalyzeDocument, fd, {
      headers: { 'Content-Type': 'multipart/form-data', 'X-CSRF-TOKEN': getCsrf() },
      timeout: 120000, // 2 minutes
    })
    if (!res.data.success) throw new Error(res.data.error || 'Erreur analyse')
    const r = res.data
    lastAnalyzedDoc.value = file.name

    // ── Injection dans la procédure cible ──────────────────────────────
    // Si aucune procédure n'existe, en créer une nouvelle
    if (!procedures.length) addProcedure()
    const pi = Math.min(docTargetProcIdx.value, procedures.length - 1)
    const proc = procedures[pi]

    // Métadonnées synthèse → identification procédure
    if (r.synthese) {
      const s = r.synthese
      if (s.titre         && !proc.intitule)      proc.intitule      = s.titre
      if (s.ref_procedure && !proc.ref_procedure) proc.ref_procedure = s.ref_procedure
      if (s.version       && !proc.version_vigueur) proc.version_vigueur = s.version
      if (s.domaine       && !proc.service_dept)  proc.service_dept  = s.domaine
      if (s.description   && !proc.description)   proc.description   = s.description
    }

    // BPMN → sur la procédure
    if (r.bpmn_xml) {
      proc.bpmn_xml = r.bpmn_xml
      currentBpmnXml.value = r.bpmn_xml
      bpmnHasContent.value = true
      bpmnProcIdx.value    = pi
    }

    // Matrice, collecte, grille → injectés dans le premier niveau
    // Si aucun niveau, en créer un par défaut
    if (!proc.levels?.length) addLevel(pi)
    const lvl = proc.levels[0]

    if ((r.matrice_b || r.items_matrice || []).length) {
      lvl.items_matrice_parsed = r.matrice_b || r.items_matrice || []
    }
    if ((r.collecte_c || r.plan_collecte || []).length) {
      lvl.plan_collecte_parsed = r.collecte_c || r.plan_collecte || []
    }
    if ((r.grille_d || r.grille_entretien || []).length) {
      lvl.grille_entretien_parsed = r.grille_d || r.grille_entretien || []
    }

    // Naviguer vers la procédure
    activeProcIdx.value = pi
    activeProcSubTab.value = 'LEVELS'
    activeLevelIdx.value = 0
    activeLevelSubTab.value = 'MAT'

    showToast('success', `Analyse terminée — données injectées dans la procédure ${pi+1}`)
    submitSilent()

  } catch (err: any) {
    showToast('error', 'Erreur analyse : ' + (err.response?.data?.error || err.message))
  } finally {
    docAnalyzing.value = false
  }
}

async function submitSilent() {
  if (isLocked.value || !apt.id) return
  autoSaving.value = true
  try {
    if (bpmnModeler) {
      try { const { xml } = await bpmnModeler.saveXML({ format: true }); if (procedures[bpmnProcIdx.value]) procedures[bpmnProcIdx.value].bpmn_xml = xml } catch {}
    }
    const fd = new FormData()
    fd.append('mission_id',    String(props.missionId))
    fd.append('assignment_id', String(props.assignmentId))
    fd.append('procedures',    JSON.stringify(serializeProcs()))
    fd.append('synthese_ff',   JSON.stringify(synthese.map(({_k,...r})=>r)))
    Object.entries(formF).forEach(([k,v])=>{ if(v!=null) fd.append(k, String(v)) })
    fd.append('deleted_files', '[]')
    fd.append('_method', 'PUT')
    const res = await axios.post(props.urlUpdate ?? props.urlStore, fd, {
      headers: { 'Content-Type': 'multipart/form-data', 'X-CSRF-TOKEN': getCsrf() }
    })
    if (res.data?.form) Object.assign(apt, res.data.form)
  } catch (err: any) {
    console.warn('[APT] auto-save:', err.message)
  } finally {
    autoSaving.value = false
  }
}
function formatSize(b: number) {
  if (b < 1024) return b + ' o'
  if (b < 1048576) return Math.round(b/1024*10)/10 + ' Ko'
  return Math.round(b/1048576*10)/10 + ' Mo'
}

// ── APT liste ──────────────────────────────────────────────────────────────
function loadApt(a:any) {
  const base = props.urlIndex.replace(/\/[^/]*$/,'')
  router.visit(`${base}/${a.id}/edit?mission_id=${props.missionId}&assignment_id=${props.assignmentId}`)
}
function deleteApt(a:any) {
  if(!confirm(`Supprimer ${a.code}?`)) return
  const url = props.urlIndex.replace(/\/[^/]*$/,'') + '/' + a.id
  router.delete(url, { preserveScroll:true, data:{mission_id:props.missionId,assignment_id:props.assignmentId},
    onSuccess:()=>showToast('success','Supprimé'), onError:()=>showToast('error','Erreur') })
}

// ── Submit ─────────────────────────────────────────────────────────────────
function serializeProcs() {
  return procedures.map(p => ({
    ...p,
    _k: undefined,
    levels: (p.levels || []).map((l: any) => ({
      ...l,
      _k: undefined,
      items_matrice:    JSON.stringify(l.items_matrice_parsed  || []),
      plan_collecte:    JSON.stringify(l.plan_collecte_parsed  || []),
      grille_entretien: JSON.stringify(l.grille_entretien_parsed || []),
      // Documents pending seront gérés séparément
      documents: (l.documents || []).filter((d: any) => !d._pending),
    })),
  }))
}

async function submit() {
  if (isLocked.value) return
  processing.value = true
  // Sauvegarder BPMN courant dans la procédure active
  if (bpmnModeler) {
    try {
      const { xml } = await bpmnModeler.saveXML({ format: true })
      if (procedures[bpmnProcIdx.value]) procedures[bpmnProcIdx.value].bpmn_xml = xml
    } catch {}
  }

  const fd = new FormData()
  fd.append('mission_id',    String(props.missionId))
  fd.append('assignment_id', String(props.assignmentId))
  fd.append('procedures',    JSON.stringify(serializeProcs()))
  fd.append('synthese_ff',   JSON.stringify(synthese.map(({_k,...r})=>r)))
  Object.entries(formF).forEach(([k,v])=>{ if(v!=null) fd.append(k, String(v)) })
  fd.append('deleted_files', JSON.stringify(savedFiles.map((f:any)=>f.path).filter(Boolean)))
  newFiles.forEach(f=>fd.append('attachments[]', f.file, f.name))

  // Documents pending des niveaux
  procedures.forEach((p, pi) => {
    (p.levels || []).forEach((l: any, li: number) => {
      (l.documents || []).filter((d: any) => d._pending && d._file).forEach((d: any, di: number) => {
        fd.append(`level_docs[${pi}][${li}][${di}]`, d._file, d.name)
        fd.append(`level_doc_meta[${pi}][${li}][${di}]`, JSON.stringify({ type_document: d.type_document, ref_interne: d.ref_interne }))
      })
    })
  })

  if (apt.id) fd.append('_method', 'PUT')
  const url = apt.id ? props.urlUpdate ?? props.urlStore : props.urlStore

  router.post(url, fd, {
    forceFormData: true, preserveScroll: true,
    onSuccess: (page: any) => {
      processing.value = false
      const n = page.props?.form
      if (n) Object.assign(apt, n)
      showToast('success', 'Formulaire enregistré')
    },
    onError: () => { processing.value = false; showToast('error', 'Erreur sauvegarde') },
    onFinish: () => { processing.value = false },
  })
}

function annuler() { Object.assign(apt, {}); newFiles.splice(0, newFiles.length) }

// ── Workflow ───────────────────────────────────────────────────────────────
async function soumettre() {
  if (!apt.id) { showToast('error', "Enregistrez d'abord."); return }
  if (!confirm("Soumettre l'APT ?")) return
  await apiPost(props.urlSoumettre||'', {mission_id:props.missionId,assignment_id:props.assignmentId},
    (j:any)=>{ apt.validation_status=j.status; showToast('success','Soumis') })
}
async function valider(action:string, note?:string) {
  await apiPost(props.urlValider||'', {mission_id:props.missionId,assignment_id:props.assignmentId,action,note},
    (j:any)=>{ apt.validation_status=j.status; showToast('success',action==='validated'?'Validé ✓':'Rejeté') })
}
function promptReject() { const n=prompt('Motif :'); if(!n?.trim()) return; valider('rejected',n) }
async function apiPost(url:string, body:object, onOk:(j:any)=>void) {
  processing.value=true
  try {
    const r=await fetch(url,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':getCsrf(),Accept:'application/json'},body:JSON.stringify(body)})
    const j=await r.json(); if(!r.ok) throw new Error(j?.message||'Erreur'); onOk(j)
  } catch(e:any){showToast('error',e.message)} finally{processing.value=false}
}

// ── Toast + helpers ────────────────────────────────────────────────────────
const toast = ref({show:false, type:'success', msg:''})
let tt: any
function showToast(type:string, msg:string) { if(tt) clearTimeout(tt); toast.value={show:true,type,msg}; tt=setTimeout(()=>{toast.value.show=false},3200) }
function vstLbl(s:string) { return({draft:'Brouillon',in_review:'En attente',validated:'Validé ✓',rejected:'Rejeté'}as any)[s]||s }
function vstIcon(s:string) { return({draft:'ti ti-pencil',in_review:'ti ti-clock',validated:'ti ti-circle-check',rejected:'ti ti-circle-x'}as any)[s]||'ti ti-circle' }
function getCsrf() { return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content||'' }
</script>

<style scoped>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
.apt-shell{display:flex;flex-direction:column;min-height:100vh;background:#f0f4f8;font-family:'Segoe UI',system-ui,sans-serif;--mc:#1565C0;--gr:#15803d;--rd:#dc2626;--am:#d97706}

/* Header */
.apt-header{position:sticky;top:0;z-index:100;background:#fff;border-bottom:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.06);padding:0 16px}
.apt-hrow{display:flex;align-items:center;gap:10px;min-height:58px;padding:6px 0;flex-wrap:wrap}
.apt-back{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:7px;background:#f1f5f9;border:1px solid #e2e8f0;color:#64748b;text-decoration:none;flex-shrink:0;transition:all .15s}
.apt-back:hover{background:var(--mc);color:#fff}
.apt-hinfo{flex:1;min-width:0}
.apt-chips{display:flex;align-items:center;gap:4px;flex-wrap:wrap;margin-bottom:2px}
.apt-chip{display:inline-flex;align-items:center;gap:3px;font-size:.6rem;font-weight:700;padding:2px 7px;border-radius:9px;text-transform:uppercase;letter-spacing:.04em}
.chip-draft{background:#f1f5f9;color:#64748b}.chip-in_review{background:#e3f2fd;color:#1565C0}.chip-validated{background:#d1e7dd;color:#0f5132}.chip-rejected{background:#f8d7da;color:#842029}
.chip-type{background:rgba(230,81,0,.1);color:#e65100}
.chip-role-DM{background:rgba(251,191,36,.2);color:#d97706}.chip-role-CM{background:rgba(21,101,192,.12);color:#1565C0}.chip-role-AS{background:rgba(22,163,74,.12);color:#15803d}.chip-role-AJ{background:rgba(124,58,237,.12);color:#6d28d9}
.apt-code{font-family:monospace;font-size:.66rem;font-weight:700;padding:2px 7px;border-radius:5px;background:color-mix(in srgb,var(--mc) 8%,white);border:1px solid color-mix(in srgb,var(--mc) 25%,transparent);color:var(--mc)}
.apt-title{font-size:.88rem;font-weight:700;color:#1a1a2e}
.apt-meta{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-top:2px}
.apt-meta span{display:inline-flex;align-items:center;gap:3px;font-size:.67rem;color:#64748b}
.aud-chip{display:inline-flex;align-items:center;gap:3px;font-size:.62rem;font-weight:600;padding:2px 7px;border-radius:8px;background:#f1f5f9;border:1px solid #e2e8f0;color:#374151}
.aud-chip code{font-size:.58rem;font-weight:800;padding:0 3px;border-radius:3px;background:rgba(0,0,0,.08)}
.aud-dm code{background:#d97706;color:#fff}.aud-cm code{background:#1565C0;color:#fff}.aud-as code{background:#15803d;color:#fff}.aud-aj code{background:#6d28d9;color:#fff}
.apt-hactions{display:flex;align-items:center;gap:8px;flex-shrink:0}
.apt-prog{display:flex;align-items:center;gap:7px}
.prog-track{width:100px;height:6px;background:#e2e8f0;border-radius:3px;overflow:hidden}
.prog-fill{height:100%;background:var(--mc);border-radius:3px;transition:width .4s}
.prog-pct{font-size:.7rem;font-weight:700;color:var(--mc);min-width:30px}
.apt-banner{display:flex;align-items:center;gap:6px;padding:5px 0 8px;font-size:.76rem;border-top:1px solid #f1f5f9}
.banner-lock{color:#0f5132}.banner-review{color:#1565C0}
.apt-tabs{display:flex;gap:3px;flex-wrap:wrap;padding:6px 0 2px}
.apt-tab{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:7px;border:1.5px solid #e2e8f0;background:#f8fafc;color:#64748b;cursor:pointer;font-size:.71rem;font-weight:600;font-family:inherit;transition:all .13s}
.apt-tab:hover{border-color:var(--mc);color:var(--mc);background:#eff6ff}
.apt-tab.active{background:var(--mc);color:#fff;border-color:var(--mc)}
.tab-ct{background:rgba(255,255,255,.25);border-radius:7px;font-size:.6rem;padding:0 5px;font-weight:700;min-width:18px;text-align:center}
.apt-tab:not(.active) .tab-ct{background:rgba(21,101,192,.12);color:var(--mc)}

/* Body */
.apt-body{flex:1;padding:12px 16px 24px;display:flex;flex-direction:column;gap:10px}
.tab-pane{display:flex;flex-direction:column;gap:10px}
.apt-grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media(max-width:1100px){.apt-grid2{grid-template-columns:1fr}}
.col-forms{display:flex;flex-direction:column;gap:12px}

/* Cards */
.card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;position:relative}
.card-label{position:absolute;top:-10px;left:14px;background:#fff;padding:0 8px;font-size:.63rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--mc);border:1px solid color-mix(in srgb,var(--mc) 30%,transparent);border-radius:4px;display:inline-flex;align-items:center;gap:5px;z-index:1;white-space:nowrap}
.card-body{padding:18px 14px 14px;display:flex;flex-direction:column;gap:9px}
.p0{padding:0}.p-s{padding:8px 12px}

/* Fields */
.field{display:flex;flex-direction:column;gap:3px}
.lbl{font-size:.7rem;font-weight:600;color:#475569;display:block}
.inp{width:100%;border:1px solid #d1d5db;border-radius:6px;padding:6px 10px;font-size:.8rem;color:#1a1a2e;background:#fff;outline:none;font-family:inherit;transition:border-color .12s}
.inp:focus{border-color:var(--mc);box-shadow:0 0 0 2px color-mix(in srgb,var(--mc) 12%,transparent)}
.inp-ro{background:#f8fafc;color:#64748b;cursor:default}
.inp:disabled{background:#f8fafc;color:#94a3b8;cursor:not-allowed}
.inp-sm{padding:4px 8px;font-size:.76rem}
.inp-xs{padding:2px 6px;font-size:.68rem;width:auto}
.ta{width:100%;border:1px solid #d1d5db;border-radius:6px;padding:6px 10px;font-size:.8rem;color:#1a1a2e;font-family:inherit;resize:vertical;outline:none}
.ta:focus{border-color:var(--mc)}.ta:disabled{background:#f8fafc;color:#94a3b8}
.form-row2{display:grid;grid-template-columns:1fr 1fr;gap:9px}
.hidden{display:none}
.ro-sm{font-size:.72rem;color:#374151;white-space:pre-wrap}
.radio-grp{display:flex;flex-wrap:wrap;gap:8px}.radio-grp-v{display:flex;flex-direction:column;gap:6px}
.radio-lbl{display:inline-flex;align-items:center;gap:5px;font-size:.76rem;color:#374151;cursor:pointer}

/* Auditeurs */
.aud-row td{padding:5px 10px;font-size:.74rem}
.td-aud-name{font-weight:600;color:#1a1a2e}
.role-badge{display:inline-flex;padding:2px 8px;border-radius:6px;font-size:.65rem;font-weight:800;text-transform:uppercase}
.role-dm{background:#fef3c7;color:#d97706}.role-cm{background:#e3f2fd;color:#1565C0}.role-as{background:#d1fae5;color:#15803d}.role-aj{background:#ede9fe;color:#6d28d9}
.td-date{font-size:.68rem;color:#64748b;white-space:nowrap}

/* Tbl */
.tbl{width:100%;border-collapse:collapse;font-size:.78rem}
.tbl thead th{background:#1e3a5f;color:rgba(255,255,255,.88);font-size:.62rem;font-weight:700;text-transform:uppercase;padding:7px 10px;border:none;white-space:nowrap}
.tbl tbody td{padding:5px 10px;border:1px solid #e9ecef;vertical-align:middle}
.tbl-row{cursor:pointer}.tbl-row:hover td{background:#f8fafc}
.td-empty{text-align:center;color:#94a3b8;font-size:.74rem;padding:14px!important}
.td-code{font-weight:700;color:var(--mc);font-family:monospace;font-size:.74rem}
.td-acts{text-align:right;white-space:nowrap}

/* Act btns */
.act-btn{display:inline-flex;align-items:center;justify-content:center;width:25px;height:25px;border-radius:5px;border:none;cursor:pointer;font-size:.7rem;margin-left:3px}
.act-edit{background:#e3f2fd;color:#1565C0}.act-edit:hover{background:#1565C0;color:#fff}
.act-del{background:#fee2e2;color:#ef4444}.act-del:hover{background:#ef4444;color:#fff}
.btn-icon-del{background:#fee2e2;border:none;color:#ef4444;cursor:pointer;padding:4px 7px;border-radius:5px;font-size:.7rem;display:inline-flex;align-items:center;transition:all .15s}
.btn-icon-del:hover{background:#ef4444;color:#fff}
.btn-icon-view{background:#e3f2fd;border:none;color:#1565C0;cursor:pointer;padding:4px 7px;border-radius:5px;font-size:.7rem;display:inline-flex;align-items:center;text-decoration:none;transition:all .15s}
.btn-icon-view:hover{background:#1565C0;color:#fff}

/* Dropzone */
.dropzone{border:2px dashed #d1d5db;border-radius:8px;padding:14px;text-align:center;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:4px;font-size:.76rem;color:#475569;background:#f8fafc;transition:all .15s}
.dropzone:hover:not(.dz-locked),.dz-active{border-color:var(--mc);background:color-mix(in srgb,var(--mc) 5%,white)}
.dz-locked{cursor:default;opacity:.6}.dz-ico{font-size:1.4rem;color:#94a3b8}

/* Dropzone IA */
.dropzone-ai{border:2px dashed #7c3aed;border-radius:10px;padding:18px;text-align:center;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:5px;font-size:.78rem;color:#475569;background:#fdf4ff;transition:all .15s;min-height:80px;justify-content:center}
.dropzone-ai:hover,.dropzone-ai.dz-active{border-color:#6d28d9;background:color-mix(in srgb,#7c3aed 8%,white)}
.dropzone-ai.dz-loading{cursor:wait;opacity:.8}
.dz-analyzing{display:flex;flex-direction:column;align-items:center;gap:7px;color:#6d28d9}
.dz-ico-ai{font-size:1.8rem;color:#7c3aed}
.ai-spinner-lg{width:30px;height:30px;border:3px solid rgba(124,58,237,.2);border-top-color:#7c3aed;border-radius:50%;animation:spin .8s linear infinite}
.ai-upload-hint{font-size:.73rem;color:#64748b;line-height:1.55}
.analyzed-doc-info{display:flex;align-items:center;gap:7px;padding:6px 10px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:7px;font-size:.74rem;color:#15803d;margin-top:6px}
.analyzed-doc-info span{flex:1}
.btn-clear-analysis{background:none;border:none;color:#94a3b8;cursor:pointer;padding:2px 4px;border-radius:4px;display:flex;align-items:center}
.btn-clear-analysis:hover{background:#fee2e2;color:#dc2626}
.file-item{display:flex;align-items:center;gap:7px;padding:5px 8px;border-radius:6px;background:#f8fafc;border:1px solid #e9ecef;font-size:.74rem;margin-top:4px}
.file-saved{background:#f0fdf4;border-color:#bbf7d0}
.file-name{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:500;color:#1a1a2e}
.file-link{color:var(--mc);text-decoration:none}.file-link:hover{text-decoration:underline}
.file-size{font-size:.62rem;color:#94a3b8}
.file-badge{font-size:.58rem;font-weight:700;padding:1px 5px;border-radius:4px;background:#fef9c3;color:#854d0e}
.file-del{background:none;border:none;cursor:pointer;color:#94a3b8;width:20px;height:20px;display:flex;align-items:center;justify-content:center;border-radius:4px}
.file-del:hover{background:#fee2e2;color:#ef4444}

/* Upload invite */
.upload-invite{display:flex;flex-direction:column;align-items:center;gap:12px;background:#fff;border:2px dashed #d1d5db;border-radius:12px;padding:48px 24px;text-align:center}
.upload-invite-sm{padding:20px 16px}
.ui-icon{width:48px;height:48px;border-radius:12px;background:color-mix(in srgb,var(--mc) 10%,white);display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:var(--mc)}
.ui-title{font-size:.88rem;font-weight:700;color:#1a1a2e}
.ui-hint{font-size:.76rem;color:#64748b;max-width:380px;line-height:1.6}
.ui-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:center;margin-top:4px}

/* ════ PROCÉDURES LAYOUT ════ */
.proc-layout{display:grid;grid-template-columns:300px 1fr;gap:12px;min-height:600px}
@media(max-width:1000px){.proc-layout{grid-template-columns:1fr}}

/* Panneau gauche */
.proc-left{background:#fff;border:1px solid #e2e8f0;border-radius:10px;display:flex;flex-direction:column;overflow:hidden}
.proc-left-head{display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border-bottom:1px solid #f1f5f9;background:#f8fafc;flex-shrink:0}
.plh-title{font-size:.78rem;font-weight:700;color:#374151;display:flex;align-items:center;gap:5px}

/* Box IA procédure */
.ia-proc-box{padding:12px;border-bottom:1px solid #f1f5f9;background:#fdf4ff}
.ia-proc-label{font-size:.68rem;font-weight:700;color:#6d28d9;text-transform:uppercase;letter-spacing:.05em;display:flex;align-items:center;gap:4px;margin-bottom:5px}
.ia-proc-desc{font-size:.7rem;color:#64748b;line-height:1.5;margin-bottom:8px}
.ia-proc-ta{width:100%;border:1.5px solid #ddd6fe;border-radius:7px;padding:7px 10px;font-size:.76rem;color:#1a1a2e;font-family:inherit;resize:vertical;outline:none;background:#fff}
.ia-proc-ta:focus{border-color:#7c3aed}
.ia-proc-actions{display:flex;align-items:center;gap:6px;margin-top:7px}

/* Liste procédures */
.proc-list{flex:1;overflow-y:auto;padding:8px}
.proc-empty{display:flex;flex-direction:column;align-items:center;gap:8px;padding:32px 16px;color:#94a3b8;text-align:center}
.proc-empty i{font-size:2rem;opacity:.3}
.proc-empty p{font-size:.76rem}
.proc-card{background:#fff;border:1.5px solid #e2e8f0;border-radius:9px;cursor:pointer;transition:all .15s;margin-bottom:8px}
.proc-card:hover{border-color:var(--mc);box-shadow:0 2px 8px rgba(21,101,192,.1)}
.proc-card.active{border-color:var(--mc);background:color-mix(in srgb,var(--mc) 3%,white)}
.proc-card-header{display:flex;align-items:flex-start;gap:8px;padding:10px 12px 6px}
.proc-card-num{width:22px;height:22px;border-radius:5px;background:var(--mc);color:#fff;font-size:.68rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.proc-card-info{flex:1;min-width:0}
.proc-card-title{font-size:.8rem;font-weight:600;color:#1a1a2e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.proc-card-meta{display:flex;align-items:center;gap:6px;margin-top:2px;flex-wrap:wrap}
.proc-ref{font-size:.65rem;background:rgba(21,101,192,.1);color:var(--mc);padding:1px 5px;border-radius:3px}
.proc-svc{font-size:.65rem;color:#64748b;display:flex;align-items:center;gap:3px}
.proc-card-status{font-size:.6rem;font-weight:700;padding:2px 6px;border-radius:6px;white-space:nowrap;flex-shrink:0}
.pcs-en_cours{background:#e3f2fd;color:#1565C0}.pcs-termine{background:#d1fae5;color:#15803d}.pcs-suspendu{background:#fef3c7;color:#d97706}

/* Mini niveaux */
.proc-levels-mini{padding:0 12px 6px;display:flex;flex-direction:column;gap:3px}
.plm-item{display:flex;align-items:center;gap:6px;padding:4px 8px;border-radius:6px;background:#f8fafc;border:1px solid #e9ecef;cursor:pointer;transition:all .12s;font-size:.68rem}
.plm-item:hover{border-color:var(--mc);background:#eff6ff}
.plm-code{font-family:monospace;font-weight:700;color:var(--mc);min-width:24px}
.plm-label{flex:1;color:#374151;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.plm-stats{display:flex;gap:4px}
.plm-c{color:#15803d;font-weight:700}.plm-nc{color:#dc2626;font-weight:700}.plm-pp{color:#d97706;font-weight:700}
.plm-docs{display:flex;align-items:center;gap:2px;color:#64748b;font-size:.62rem}
.plm-non_commence{opacity:.6}.plm-en_cours{border-left:3px solid #1565C0}.plm-termine{border-left:3px solid #15803d}.plm-suspendu{border-left:3px solid #d97706}
.btn-add-level{background:none;border:1.5px dashed #d1d5db;color:#94a3b8;padding:3px 8px;border-radius:5px;font-size:.64rem;cursor:pointer;width:100%;text-align:center;margin-top:4px;transition:all .15s;display:flex;align-items:center;justify-content:center;gap:4px}
.btn-add-level:hover{border-color:var(--mc);color:var(--mc)}

.proc-card-actions{padding:4px 12px 8px;display:flex;align-items:center;justify-content:flex-end;gap:6px}
.btn-xs{padding:2px 6px;font-size:.64rem}

/* Panneau droit */
.proc-right{background:#fff;border:1px solid #e2e8f0;border-radius:10px;display:flex;flex-direction:column;overflow:hidden}
.proc-right-empty{align-items:center;justify-content:center}
.pre-hint{display:flex;flex-direction:column;align-items:center;gap:10px;color:#94a3b8}
.pre-hint i{font-size:2rem;opacity:.2}
.pre-hint p{font-size:.8rem}
.proc-right-head{border-bottom:1px solid #f1f5f9;background:#f8fafc;flex-shrink:0;padding:10px 14px 0}
.prh-title{display:flex;align-items:center;gap:7px;font-size:.82rem;font-weight:700;color:#1a1a2e;margin-bottom:8px}
.prh-code{font-size:.65rem;font-weight:700;color:var(--mc);background:rgba(21,101,192,.1);padding:1px 6px;border-radius:4px}
.proc-subtabs{display:flex;gap:2px}
.pstab{display:inline-flex;align-items:center;gap:4px;padding:5px 10px;border-radius:6px 6px 0 0;border:1.5px solid transparent;background:none;color:#64748b;cursor:pointer;font-size:.7rem;font-weight:600;font-family:inherit;transition:all .13s;border-bottom:none}
.pstab:hover{color:var(--mc);background:#eff6ff}
.pstab.active{background:#fff;border-color:#e2e8f0;border-bottom-color:#fff;color:var(--mc)}
.proc-sub-content{flex:1;overflow-y:auto;padding:14px}
.proc-id-grid{display:flex;flex-direction:column;gap:9px}

/* Niveaux */
.level-toolbar{display:flex;align-items:center;gap:7px;margin-bottom:10px;flex-wrap:wrap}
.levels-list{display:flex;flex-direction:column;gap:8px}
.level-block{background:#fff;border:1.5px solid #e2e8f0;border-radius:9px;cursor:pointer;transition:border-color .15s}
.level-block:hover{border-color:var(--mc)}
.level-block.active{border-color:var(--mc);box-shadow:0 0 0 3px color-mix(in srgb,var(--mc) 10%,transparent)}
.level-block-header{display:flex;align-items:center;gap:8px;padding:10px 12px}
.lbh-left{display:flex;align-items:center;gap:8px;flex:1}
.lbh-badge{font-size:.64rem;font-weight:700;padding:2px 8px;border-radius:5px}
.lbh-non_commence{background:#f1f5f9;color:#64748b}
.lbh-en_cours{background:#e3f2fd;color:#1565C0}
.lbh-termine{background:#d1fae5;color:#15803d}
.lbh-suspendu{background:#fef3c7;color:#d97706}
.lbh-title{font-size:.8rem;font-weight:600;color:#1a1a2e}
.lbh-stats{display:flex;gap:5px;font-size:.68rem;font-weight:600}
.lbs-c{color:#15803d}.lbs-nc{color:#dc2626}.lbs-pp{color:#d97706}
.lbh-docs-ct{display:flex;align-items:center;gap:3px;font-size:.68rem;color:#64748b;padding:2px 7px;background:#f8fafc;border-radius:5px}
.level-block-detail{border-top:1px solid #f1f5f9;padding:0}
.lbd-subtabs{display:flex;gap:2px;padding:8px 12px 0;background:#fafafa;flex-wrap:wrap}
.lstab{display:inline-flex;align-items:center;gap:4px;padding:4px 9px;border-radius:5px 5px 0 0;border:1.5px solid transparent;background:none;color:#64748b;cursor:pointer;font-size:.68rem;font-weight:600;font-family:inherit;transition:all .13s;border-bottom:none;position:relative}
.lstab:hover{color:var(--mc)}
.lstab.active{background:#fff;border-color:#e2e8f0;border-bottom-color:#fff;color:var(--mc)}
.lstab-ct{font-size:.58rem;font-weight:700;padding:0 4px;border-radius:4px;background:rgba(21,101,192,.12);color:var(--mc);margin-left:2px}
.lbd-form{padding:12px;display:flex;flex-direction:column;gap:9px}
.lbd-toolbar{display:flex;align-items:center;gap:6px;padding:8px 10px;border-bottom:1px solid #f1f5f9;flex-wrap:wrap;background:#fafafa}
.lbd-stats{font-size:.72rem;font-weight:600;display:flex;gap:5px;margin-left:auto}
.lbd-doc-hint{font-size:.7rem;color:#64748b}
.lbd-matrice,.lbd-docs,.lbd-collecte{padding:10px}

/* Docs grid */
.docs-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px}
.doc-card{background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:8px;display:flex;align-items:flex-start;gap:9px;padding:10px 12px;transition:border-color .15s}
.doc-card:hover{border-color:var(--mc)}
.doc-card-icon{width:34px;height:34px;border-radius:7px;background:#e3f2fd;display:flex;align-items:center;justify-content:center;font-size:1rem;color:var(--mc);flex-shrink:0}
.doc-card-info{flex:1;min-width:0;display:flex;flex-direction:column;gap:4px}
.doc-name{font-size:.76rem;font-weight:600;color:#1a1a2e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.doc-meta{display:flex;align-items:center;gap:5px;flex-wrap:wrap}
.doc-type-badge{font-size:.62rem;font-weight:700;padding:1px 5px;border-radius:4px;background:rgba(21,101,192,.1);color:var(--mc)}
.doc-size{font-size:.62rem;color:#94a3b8}
.doc-card-actions{display:flex;flex-direction:column;gap:3px;flex-shrink:0}

/* Preuve cell */
.preuve-cell{display:flex;align-items:center;gap:4px}
.btn-attach-doc{background:#f1f5f9;border:1.5px solid #e2e8f0;color:#64748b;width:24px;height:24px;border-radius:5px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.72rem;position:relative;transition:all .15s;flex-shrink:0}
.btn-attach-doc:hover,.btn-attach-doc.active{background:var(--mc);border-color:var(--mc);color:#fff}
.attached-badge{position:absolute;top:-4px;right:-4px;width:12px;height:12px;background:#15803d;color:#fff;border-radius:50%;font-size:.5rem;display:flex;align-items:center;justify-content:center;font-weight:700}

/* Tableaux B/C */
.tbl-wrap-b{overflow:auto;border:1px solid #e2e8f0;border-radius:8px;background:#fff}
.btbl{width:100%;border-collapse:collapse;font-size:.72rem;min-width:800px}
.btbl thead th{background:#1e3a5f;color:rgba(255,255,255,.9);font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;padding:6px 7px;border:none;white-space:nowrap;position:sticky;top:0;z-index:3}
.btbl tbody td{padding:4px 6px;border:1px solid #e9ecef;vertical-align:top}
.th-num{width:36px;text-align:center}.th-point{min-width:180px}.th-oc,.th-oa{width:66px;text-align:center}
.th-nat{width:72px}.th-ctrlb{width:56px}.th-prev{min-width:100px}.th-obs{min-width:140px}.th-res{width:72px}
.th-info{min-width:160px}.th-src{min-width:120px}.th-meth{min-width:120px}.th-stat-c{width:70px}
.row-section td{padding:0!important}
.section-hd{display:flex;align-items:center;gap:6px;padding:5px 8px;background:#1e3a5f;color:#fff;font-size:.68rem;font-weight:700}
.row-b{background:#fff;transition:background .1s}.row-b:hover td{background:#f8fbff}
.row-c td{border-left:3px solid #15803d!important;background:#f0fdf4}.row-nc td{border-left:3px solid #dc2626!important;background:#fef2f2}.row-pp td{border-left:3px solid #d97706!important;background:#fffbeb}
.td-num-b{text-align:center;font-weight:700;color:#64748b;font-size:.68rem}.td-point{font-size:.7rem;color:#1a1a2e;line-height:1.4}.td-nat,.td-ctrlb,.td-stat-c{text-align:center}
.badge-oc{font-size:.6rem;font-weight:700;padding:2px 4px;border-radius:3px;background:rgba(21,101,192,.1);color:#1565C0;font-family:monospace}
.badge-oa{font-size:.6rem;font-weight:700;padding:2px 4px;border-radius:3px;background:rgba(15,118,110,.1);color:#0f766e;font-family:monospace}
.btn-grp{display:flex;gap:2px;flex-wrap:wrap}.btn-grp-v{display:flex;flex-direction:column;gap:2px}
.ynb{padding:2px 5px;border-radius:3px;border:1px solid #d1d5db;background:#f8fafc;font-size:.62rem;font-weight:700;cursor:pointer;font-family:inherit;transition:all .1s;white-space:nowrap}
.ynb:hover{border-color:#94a3b8}
.ynb-fort.active{background:#15803d;color:#fff;border-color:#15803d}.ynb-faib.active{background:#dc2626;color:#fff;border-color:#dc2626}
.ynb-oui.active{background:#15803d;color:#fff;border-color:#15803d}.ynb-non.active{background:#dc2626;color:#fff;border-color:#dc2626}
.ynb-c.active{background:#15803d;color:#fff;border-color:#15803d}.ynb-nc.active{background:#dc2626;color:#fff;border-color:#dc2626}.ynb-pp.active{background:#d97706;color:#fff;border-color:#d97706}
.badge-nat{font-size:.64rem;font-weight:700;padding:2px 6px;border-radius:5px}.nat-fort{background:#d1fae5;color:#065f46}.nat-faib{background:#fee2e2;color:#991b1b}
.badge-ctrl{font-size:.68rem;font-weight:700;padding:2px 6px;border-radius:4px}.ctrl-oui{background:#d1fae5;color:#065f46}.ctrl-non{background:#fee2e2;color:#991b1b}
.badge-res{font-size:.66rem;font-weight:700;padding:2px 7px;border-radius:5px;display:inline-block}
.res-c{background:#d1fae5;color:#065f46}.res-nc{background:#fee2e2;color:#991b1b}.res-pp{background:#fef3c7;color:#92400e}
.badge-sc{font-size:.64rem;font-weight:700;padding:2px 6px;border-radius:5px}
.sc-a_collecter{background:#e3f2fd;color:#1565C0}.sc-obtenu{background:#d1fae5;color:#065f46}.sc-na{background:#f1f5f9;color:#64748b}
.c-inp{width:100%;border:1px solid #e2e8f0;border-radius:4px;padding:3px 4px;font-size:.68rem;color:#1a1a2e;font-family:inherit;outline:none;background:rgba(255,255,255,.8)}
.c-inp:focus{border-color:var(--mc);background:#fff}
.c-ta{width:100%;border:1px solid #e2e8f0;border-radius:4px;padding:3px 5px;font-size:.68rem;color:#1a1a2e;font-family:inherit;resize:vertical;outline:none;min-height:32px;background:rgba(255,255,255,.7)}
.c-ta:focus{border-color:var(--mc);background:#fff}

/* Synthèse */
.synth-section{display:flex;flex-direction:column;gap:12px}
.synth-group{background:#fff;border:1px solid #e2e8f0;border-radius:9px;overflow:hidden}
.synth-group-hd{display:flex;align-items:center;gap:7px;padding:8px 14px;font-size:.74rem;font-weight:700}
.synth-force{background:#d1fae5;color:#065f46}.synth-faib{background:#fee2e2;color:#991b1b}
.badge-force{font-size:.62rem;font-weight:700;padding:2px 7px;border-radius:6px;background:#15803d;color:#fff}
.badge-faib{font-size:.62rem;font-weight:700;padding:2px 7px;border-radius:6px;background:#dc2626;color:#fff}
.td-qualif-s{text-align:center;width:80px}
.row-force td{background:#f0fdf4}.row-force:hover td{background:#dcfce7}.row-faib td{background:#fef2f2}.row-faib:hover td{background:#fee2e2}

/* Appréciation */
.appre-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
@media(max-width:900px){.appre-grid{grid-template-columns:repeat(2,1fr)}}
.appre-col{display:flex;flex-direction:column;gap:7px;padding:10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px}
.appre-lbl{font-size:.64rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--mc);padding-bottom:4px;border-bottom:1px solid #e2e8f0}
.badge-appre{font-size:.68rem;font-weight:700;padding:3px 8px;border-radius:6px;display:inline-block}
.appre-conf{background:#d1fae5;color:#065f46}.appre-part{background:#fef3c7;color:#92400e}.appre-nc{background:#fee2e2;color:#991b1b}.appre-crit{background:#991b1b;color:#fff}.appre-blue{background:#e3f2fd;color:#1565C0}

/* BPMN */
.fc-tab-pane{min-height:0;flex:1}
.bpmn-workspace{display:grid;grid-template-columns:200px 1fr;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;background:#fff;height:calc(100vh - 220px);min-height:580px}
.bpmn-workspace.bpmn-show-props{grid-template-columns:200px 1fr 260px}
@media(max-width:900px){.bpmn-workspace,.bpmn-workspace.bpmn-show-props{grid-template-columns:1fr}.bpmn-sidebar-left{display:none}}
.bpmn-sidebar-left{background:#f8fafc;border-right:1px solid #e2e8f0;overflow-y:auto;display:flex;flex-direction:column}
.bsl-section{border-bottom:1px solid #e9ecef}
.bsl-title{display:flex;align-items:center;gap:5px;padding:8px 12px 4px;font-size:.6rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#64748b}
.bsl-body{padding:3px 12px 10px}
.bsl-proc-title{font-size:.74rem;font-weight:700;color:#1a1a2e;margin-bottom:5px}
.bsl-row{display:flex;align-items:center;gap:6px;font-size:.67rem;color:#374151;margin-bottom:3px}
.bsl-lbl{font-size:.58rem;font-weight:700;color:#94a3b8;min-width:44px;text-transform:uppercase}
.bsl-code{font-family:monospace;font-size:.62rem;font-weight:700;padding:1px 4px;border-radius:3px;background:rgba(21,101,192,.1);color:#1565C0}
.bpmn-main{display:flex;flex-direction:column;min-height:0}
.bpmn-toolbar{display:flex;align-items:center;justify-content:space-between;gap:7px;padding:5px 10px;border-bottom:1px solid #e9ecef;background:#fff;flex-wrap:wrap;flex-shrink:0}
.bpmn-tb-left,.bpmn-tb-right{display:flex;align-items:center;gap:4px;flex-wrap:wrap}
.bpmn-tb-label{font-size:.7rem;color:#64748b;display:flex;align-items:center;gap:4px;font-weight:600}
.bpmn-tb-sep{width:1px;height:18px;background:#e2e8f0;margin:0 2px}
.bpmn-save-pill{display:flex;align-items:center;gap:4px;font-size:.68rem;padding:2px 7px;border-radius:20px;border:1px solid #e2e8f0;color:#64748b;background:#f8fafc}
.bpmn-save-pill.saved{background:#f0fdf4;border-color:#bbf7d0;color:#15803d}
.bpmn-save-pill.saving{background:#ede9fe;border-color:#c4b5fd;color:#6d28d9}
.bpmn-save-pill.error{background:#fee2e2;border-color:#fca5a5;color:#dc2626}
.bpmn-canvas-wrap{flex:1;position:relative;min-height:0;background:repeating-linear-gradient(0deg,transparent,transparent 24px,rgba(99,102,241,.04) 24px,rgba(99,102,241,.04) 25px),repeating-linear-gradient(90deg,transparent,transparent 24px,rgba(99,102,241,.04) 24px,rgba(99,102,241,.04) 25px)}
.bpmn-container{position:absolute;inset:0}
.bpmn-loader{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;background:rgba(255,255,255,.95);z-index:10;gap:10px}
.bpmn-loader-ring{width:36px;height:36px;border:3px solid #e2e8f0;border-top-color:#1565C0;border-radius:50%;animation:spin 1s linear infinite}
.bpmn-loader p{font-size:.84rem;font-weight:600;color:#475569;margin:0}
.bpmn-sidebar-right{background:#fff;border-left:1px solid #e2e8f0;display:flex;flex-direction:column;overflow:hidden}
.bsr-head{display:flex;align-items:center;justify-content:space-between;padding:.6rem .9rem;border-bottom:1px solid #f1f5f9;background:#f8fafc;flex-shrink:0}
.bsr-title{font-size:.7rem;font-weight:700;color:#374151;display:flex;align-items:center;gap:4px;text-transform:uppercase;letter-spacing:.05em}
.bsr-close{background:none;border:none;color:#94a3b8;cursor:pointer;font-size:.85rem;padding:.15rem;border-radius:4px}
.bsr-close:hover{color:#ef4444}
.bsr-body{flex:1;overflow-y:auto}
.bsr-no-sel{display:flex;flex-direction:column;align-items:center;text-align:center;padding:1.5rem 1rem;color:#64748b}
.bsr-no-icon{font-size:1.6rem;opacity:.2;margin-bottom:.5rem}
.bsr-no-sel p{font-size:.76rem;line-height:1.5;margin-bottom:.75rem}
.bsr-tips{width:100%;display:flex;flex-direction:column;gap:.3rem}
.bsr-tip{display:flex;align-items:center;gap:.5rem;background:#f8fafc;border:1px solid #f1f5f9;border-radius:6px;padding:.35rem .6rem;font-size:.7rem;color:#64748b}
.bsr-tip i{color:#1565C0;flex-shrink:0}
.bsr-props{padding:.9rem;display:flex;flex-direction:column;gap:9px}
.bsr-el-head{display:flex;align-items:flex-start;gap:.6rem;padding-bottom:.65rem;border-bottom:1px solid #f1f5f9}
.bsr-el-icon{width:32px;height:32px;background:#e3f2fd;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:.9rem;color:#1565C0;flex-shrink:0}
.bsr-el-name{font-size:.82rem;font-weight:700;color:#0f172a;margin-bottom:.12rem;word-break:break-word}
.bsr-el-id{font-size:.63rem;color:#94a3b8;display:block;margin-bottom:.2rem}
.bsr-el-type{font-size:.63rem;background:#e3f2fd;color:#1565C0;padding:.1rem .35rem;border-radius:4px;font-weight:600}
.bsr-field{display:flex;flex-direction:column;gap:3px}
.bsr-lbl{font-size:.65rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em}
.bsr-inp{width:100%;padding:.4rem .6rem;border:1.5px solid #e2e8f0;border-radius:7px;font-size:.8rem;color:#0f172a;font-family:inherit;background:#fff;transition:border-color .2s;box-sizing:border-box}
.bsr-inp:focus{outline:none;border-color:#1565C0}
.bsr-color-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:5px;margin-bottom:.5rem}
.bsr-color-dot{width:100%;padding-bottom:100%;border-radius:5px;border:1.5px solid rgba(0,0,0,.08);cursor:pointer;outline-offset:2px;transition:transform .15s;position:relative}
.bsr-color-dot:hover{transform:scale(1.1)}
.bsr-color-dot:disabled{cursor:not-allowed;opacity:.5}
.bsr-actions{display:flex;gap:.4rem;flex-wrap:wrap}
.bsr-act-btn{display:flex;align-items:center;gap:.35rem;background:#f8fafc;border:1.5px solid #e2e8f0;color:#475569;padding:.35rem .65rem;border-radius:6px;font-size:.74rem;cursor:pointer;transition:all .2s}
.bsr-act-btn:hover{background:#fee2e2;border-color:#fca5a5;color:#ef4444}
.slide-props-enter-active,.slide-props-leave-active{transition:all .2s ease}
.slide-props-enter-from,.slide-props-leave-to{opacity:0;transform:translateX(20px)}

/* Boutons */
.btn{display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:6px;font-size:.74rem;font-weight:600;border:1px solid transparent;cursor:pointer;font-family:inherit;text-decoration:none;transition:all .12s}
.btn:disabled{opacity:.5;cursor:not-allowed}
.btn-sm{padding:3px 8px;font-size:.69rem}
.btn-import{background:#f0f9ff;color:#0369a1;border-color:#bae6fd}.btn-import:hover{background:#e0f2fe}
.btn-ai{background:linear-gradient(135deg,#6d28d9,#7c3aed);color:#fff}.btn-ai:hover{filter:brightness(1.1)}.btn-ai:disabled{opacity:.5}
.btn-add-f{background:#f0fdf4;color:#15803d;border-color:#bbf7d0}.btn-add-f:hover{background:#dcfce7}
.btn-add-fw{background:#fef2f2;color:#dc2626;border-color:#fecaca}.btn-add-fw:hover{background:#fee2e2}
.btn-legend{width:34px;height:34px;padding:0;justify-content:center;background:#f8fafc;border:1px solid #e2e8f0;color:#64748b;border-radius:8px}.btn-legend:hover{background:#1e3a5f;color:#fff;border-color:#1e3a5f}
.btn-ghost{background:transparent;color:#64748b;border-color:#d1d5db}.btn-ghost:hover{background:#f1f5f9}
.btn-ghost.active{background:#e3f2fd;border-color:#90caf9;color:#1565C0}
.btn-save{background:var(--mc);color:#fff}.btn-save:hover:not(:disabled){filter:brightness(1.1)}
.btn-sub{background:#0f766e;color:#fff}.btn-sub:hover{background:#0d6460}
.btn-ok{background:#15803d;color:#fff}.btn-ok:hover{background:#166534}
.btn-rej{background:#dc2626;color:#fff}.btn-rej:hover{background:#b91c1c}
.btn-danger-sm{color:#ef4444;border-color:#fca5a5}.btn-danger-sm:hover{background:#fee2e2}

/* Tab toolbar */
.tab-toolbar{display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;background:#fff;border:1px solid #e2e8f0;border-radius:9px;padding:7px 11px}
.tbar-l{display:flex;align-items:center;gap:6px;flex-wrap:wrap}

/* Footer */
.apt-footer{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;padding:9px 13px;background:#fff;border:1px solid #e2e8f0;border-radius:9px}
.apt-footer>div{display:flex;align-items:center;gap:7px}
.footer-c{flex:1;display:flex;justify-content:center}
.saved-code{font-size:.72rem;color:#15803d;display:flex;align-items:center;gap:4px;font-weight:600}
.spin-dot{width:11px;height:11px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite}

/* Modals */
.modal-ov{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:500;display:flex;align-items:center;justify-content:center;padding:20px}
.modal{background:#fff;border-radius:14px;box-shadow:0 16px 50px rgba(0,0,0,.22);display:flex;flex-direction:column;max-height:88vh;overflow:hidden;width:100%}
.modal-legend{max-width:460px}
.modal-hd{display:flex;align-items:center;gap:10px;padding:13px 16px;border-bottom:1px solid #e2e8f0;flex-shrink:0}
.modal-av{width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0}
.modal-title{font-size:.83rem;font-weight:700;color:#1a1a2e}.modal-sub{font-size:.63rem;color:#94a3b8}
.modal-close{width:26px;height:26px;border-radius:6px;background:#f1f5f9;border:1px solid #e2e8f0;color:#64748b;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.72rem;margin-left:auto}
.modal-close:hover{background:#fee2e2;color:#dc2626}
.modal-body{padding:14px 16px;overflow-y:auto;flex:1;display:flex;flex-direction:column;gap:9px}
.modal-ft{display:flex;align-items:center;justify-content:flex-end;gap:7px;padding:11px 16px;border-top:1px solid #e2e8f0;flex-shrink:0}
.mfade-enter-active,.mfade-leave-active{transition:all .2s ease}
.mfade-enter-from,.mfade-leave-to{opacity:0}
.mfade-enter-from .modal,.mfade-leave-to .modal{transform:scale(.96)}
.leg-section{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--mc);margin:4px 0 2px}
.leg-row{display:flex;align-items:center;gap:9px;padding:3px 0;font-size:.74rem;color:#374151}
.leg-badge{display:inline-flex;align-items:center;justify-content:center;width:30px;height:20px;border-radius:5px;font-size:.66rem;font-weight:800;flex-shrink:0}
.leg-c{background:#d1fae5;color:#065f46}.leg-nc{background:#fee2e2;color:#991b1b}.leg-pp{background:#fef3c7;color:#92400e}
.leg-ref{display:flex;align-items:flex-start;gap:7px;font-size:.71rem;color:#374151;padding:2px 0}
.leg-ref code{font-size:.62rem;font-weight:700;color:var(--mc);min-width:32px;background:color-mix(in srgb,var(--mc) 8%,white);padding:1px 3px;border-radius:3px;flex-shrink:0}
.leg-sep{height:1px;background:#e2e8f0;margin:5px 0}

/* Toast */
.toast{position:fixed;bottom:22px;right:22px;z-index:600;display:flex;align-items:center;gap:9px;padding:10px 16px;border-radius:9px;font-size:.78rem;font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.18)}
.toast-success{background:#15803d;color:#fff}.toast-error{background:#dc2626;color:#fff}
.toast-up-enter-active,.toast-up-leave-active{transition:all .22s ease}
.toast-up-enter-from,.toast-up-leave-to{opacity:0;transform:translateY(8px)}
::-webkit-scrollbar{width:5px;height:5px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:#e2e8f0;border-radius:3px}::-webkit-scrollbar-thumb:hover{background:#cbd5e1}
@keyframes spin{to{transform:rotate(360deg)}}
.spin{animation:spin .6s linear infinite;display:inline-block}
</style>