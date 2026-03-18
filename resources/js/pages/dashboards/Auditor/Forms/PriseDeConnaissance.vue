<template>
  <VerticalLayoutAudit>
    <div class="pdc-shell">

      <!-- ══ HEADER ══ -->
      <header class="pdc-header">
        <div class="pdc-hrow">
          <a :href="props.backUrl" class="pdc-back"><i class="ti ti-arrow-left"></i></a>
          <div class="pdc-hinfo">
            <div class="pdc-chips">
              <code class="pdc-code">{{ mission?.code_mission ?? '—' }}</code>
              <span class="pdc-chip" :class="`chip-${pdc.validation_status || 'draft'}`">
                <i :class="vstIcon(pdc.validation_status || 'draft')"></i>
                {{ vstLbl(pdc.validation_status || 'draft') }}
              </span>
              <span class="pdc-chip chip-type">QPC</span>
              <span v-if="props.auditorRole" class="pdc-chip" :class="`chip-role-${props.auditorRole}`">
                {{ props.auditorRole }}
              </span>
            </div>
            <h1 class="pdc-title">Prise de Connaissance — Questionnaire QPC</h1>
            <div class="pdc-meta">
              <span v-if="assignment?.phase_label"><i class="ti ti-git-branch"></i>{{ assignment.phase_label }}</span>
              <span v-if="mission?.entity_name"><i class="ti ti-building"></i>{{ mission.entity_name }}</span>
              <span v-if="mission?.date_debut_fr"><i class="ti ti-calendar"></i>{{ mission.date_debut_fr }} — {{ mission.date_fin_fr }}</span>
            </div>
          </div>
          <div class="pdc-hactions">
            <button class="btn btn-struct" @click="structPanel.show = true"><i class="ti ti-layout-grid"></i></button>
            <button class="btn btn-ai" @click="aiPanel.show = true"><i class="ti" :class="aiLoading ? 'ti-loader-2 spin' : 'ti-brain'"></i></button>
          </div>
        </div>
        <div v-if="pdc.validation_status === 'validated'" class="pdc-banner banner-lock">
          <i class="ti ti-lock"></i> QPC <strong>validé définitivement</strong> — lecture seule
        </div>
        <div v-else-if="pdc.validation_status === 'in_review'" class="pdc-banner banner-review">
          <i class="ti ti-clock"></i> Soumis pour validation — en attente DM
          <span v-if="canManage"> · Vous pouvez valider ou rejeter.</span>
        </div>
      </header>

      <!-- ══ BODY ══ -->
      <div class="pdc-body">

        <!-- Barre de progression rapide -->
        <div v-if="pdc.id" class="pdc-progress-bar">
          <div class="pb-info">
            <span class="pb-code">{{ pdc.code }}</span>
            <span class="pb-stats">{{ filledLeaves }}/{{ totalLeaves }} items renseignés</span>
          </div>
          <div class="pb-track"><div class="pb-fill" :style="`width:${fillPct}%`"></div></div>
          <span class="pb-pct">{{ fillPct }}%</span>
        </div>

        <!-- ══ GRILLE PRINCIPALE ══ -->
        <div class="pdc-grid">

          <!-- ── COL GAUCHE ── -->
          <div class="pdc-col-left">

            <!-- Info mission -->
            <section class="card">
              <div class="card-label"><i class="ti ti-briefcase"></i> Mission</div>
              <div class="card-body">
                <div class="form-row">
                  <field label="Code mission"><input class="inp inp-ro" :value="mission?.code_mission" readonly /></field>
                  <field label="Phase"><input class="inp inp-ro" :value="assignment?.phase_label || assignment?.phase_code" readonly /></field>
                </div>
                <field label="Intitulé"><input class="inp inp-ro" :value="mission?.libelle" readonly /></field>
                <div class="form-row">
                  <field label="Entité"><input class="inp inp-ro" :value="mission?.entity_name || '—'" readonly /></field>
                  <field label="Lieu"><input class="inp inp-ro" :value="mission?.lieux || '—'" readonly /></field>
                </div>
              </div>
            </section>

            <!-- Info QPC -->
            <section class="card">
              <div class="card-label"><i class="ti ti-clipboard-list"></i> Questionnaire</div>
              <div class="card-body">
                <div class="form-row">
                  <field label="Code QPC"><input class="inp inp-ro" :value="form.code || 'QPC-AUTO'" readonly /></field>
                  <field label="Entité"><input class="inp inp-ro" :value="mission?.entity_name || '—'" readonly /></field>
                </div>
                <field label="Intitulé QPC *">
                  <input class="inp" v-model="form.intitule_qpc" :disabled="isLocked" placeholder="Titre du questionnaire…" />
                </field>
                <div class="form-row">
                  <field label="Fait par"><input class="inp" v-model="form.fait_par" :disabled="isLocked" /></field>
                  <field label="Date"><input type="date" class="inp" v-model="form.date_fait" :disabled="isLocked" /></field>
                </div>
                <div class="form-row">
                  <field label="Revu par"><input class="inp" v-model="form.revue_par" :disabled="isLocked" /></field>
                  <field label="Date revue"><input type="date" class="inp" v-model="form.date_revue" :disabled="isLocked" /></field>
                </div>
              </div>
            </section>

            <!-- Documents joints -->
            <section class="card">
              <div class="card-label"><i class="ti ti-folder-open"></i> Documents joints</div>
              <div class="card-body">
                <div class="dropzone"
                  :class="{ 'dz-over': isDragOver, 'dz-locked': isLocked }"
                  @dragover.prevent="isDragOver = true"
                  @dragleave="isDragOver = false"
                  @drop.prevent="onDrop"
                  @click="!isLocked && ($refs.fileInput as HTMLInputElement).click()">
                  <i class="ti ti-cloud-upload dz-ico"></i>
                  <span v-if="isLocked">Lecture seule</span>
                  <span v-else>Glisser ou <strong>cliquer pour parcourir</strong></span>
                  <small>PDF, Excel, Word, images — max 10 Mo</small>
                  <input ref="fileInput" type="file" multiple class="hidden" @change="onFileSelect" :disabled="isLocked" />
                </div>
                <div v-for="(f, i) in uploadedFiles" :key="i" class="file-item">
                  <i class="ti" :class="fileIcon(f.name)"></i>
                  <span class="file-name">{{ f.name }}</span>
                  <span class="file-badge">Nouveau</span>
                  <span class="file-size">{{ fileSize(f.size) }}</span>
                  <button v-if="!isLocked" type="button" class="file-del" @click="uploadedFiles.splice(i, 1)"><i class="ti ti-trash"></i></button>
                </div>
                <div v-if="savedFiles.length" class="saved-sep">Fichiers enregistrés</div>
                <div v-for="(f, i) in savedFiles" :key="'s' + i" class="file-item file-saved">
                  <i class="ti" :class="fileIcon(f.name)"></i>
                  <a :href="f.url" target="_blank" class="file-name file-link">{{ f.name }}</a>
                  <span class="file-size">{{ f.size_label }}</span>
                  <button v-if="!isLocked" type="button" class="file-del" @click="savedFiles.splice(i, 1)"><i class="ti ti-trash"></i></button>
                </div>
              </div>
            </section>

            <!-- Liste QPC enregistrés -->
            <section class="card">
              <div class="card-label"><i class="ti ti-list"></i> Questionnaires enregistrés</div>
              <div class="card-body" style="padding:0">
                <div style="padding:8px 12px">
                  <input class="inp" v-model="search" placeholder="Rechercher…" />
                </div>
                <table class="tbl">
                  <thead><tr><th>Code</th><th>Intitulé</th><th>Statut</th><th></th></tr></thead>
                  <tbody>
                    <tr v-if="!filteredPdcs.length"><td colspan="4" class="td-empty">Aucun questionnaire</td></tr>
                    <tr v-for="p in filteredPdcs" :key="p.id" class="tbl-row" @click="loadPdc(p)">
                      <td class="td-code">{{ p.code }}</td>
                      <td>{{ p.intitule_qpc || '—' }}</td>
                      <td><span class="pdc-chip" :class="`chip-${p.validation_status || 'draft'}`">{{ vstLbl(p.validation_status || 'draft') }}</span></td>
                      <td class="td-acts" @click.stop>
                        <button class="act-btn act-edit" @click.stop="loadPdc(p)"><i class="ti ti-pencil"></i></button>
                        <button v-if="(p.validation_status || 'draft') !== 'validated'" class="act-btn act-del" @click.stop="deletePdc(p)"><i class="ti ti-trash"></i></button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </section>

          </div><!-- /col-left -->

          <!-- ── COL DROITE — TABLEAU QPC ── -->
          <div class="pdc-col-right">
            <section class="card card-full">
              <div class="card-label"><i class="ti ti-table"></i> Items du questionnaire</div>

              <!-- Toolbar -->
              <div class="qtbar">
                <div class="qtbar-l">
                  <label v-if="!isLocked" class="btn btn-import">
                    <i class="ti ti-upload"></i> Importer Excel
                    <input type="file" accept=".xlsx,.xls" class="hidden" @change="importExcel" ref="xlsRef" />
                  </label>
                  <a href="/templates/QPC_Template.xlsx" download class="btn btn-tpl"><i class="ti ti-download"></i> Template</a>
                  <button v-if="!isLocked" type="button" class="btn btn-ai" @click="aiPanel.show = true" :disabled="aiLoading">
                    <i class="ti" :class="aiLoading ? 'ti-loader-2 spin' : 'ti-brain'"></i> IA
                  </button>
                </div>
                <div class="qtbar-r" v-if="!isLocked">
                  <button type="button" class="btn btn-h1" @click="addNode('h1')"><i class="ti ti-folder-plus"></i> Rubrique</button>
                  <button type="button" class="btn btn-h2" @click="addNode('h2')"><i class="ti ti-folder"></i> Sous-rub.</button>
                  <button type="button" class="btn btn-h3" @click="addNode('h3')"><i class="ti ti-folder-minus"></i> Niv.3</button>
                  <button type="button" class="btn btn-item" @click="addNode('item')"><i class="ti ti-plus"></i> Item</button>
                </div>
              </div>

              <!-- Tableau -->
              <div class="tbl-wrap">
                <table class="qtbl">
                  <thead>
                    <tr>
                      <th class="th-ref">Réf.</th>
                      <th class="th-lib">Libellé</th>
                      <th class="th-val">Valeur / Observation</th>
                      <th v-if="!isLocked" class="th-act"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="!qpcItems.length">
                      <td :colspan="isLocked ? 3 : 4" class="q-empty">
                        <i class="ti ti-table-off"></i>
                        <p>Aucun item. Importez un Excel, utilisez l'IA ou ajoutez manuellement.</p>
                        <button v-if="!isLocked" type="button" class="btn btn-ai" @click="aiPanel.show = true">
                          <i class="ti ti-brain"></i> Générer par IA
                        </button>
                      </td>
                    </tr>

                    <template v-for="(item, i) in qpcItems" :key="item._id">

                      <!-- H1 -->
                      <tr v-if="item.type === 'h1'" class="row-h1" v-show="isVisible(i)">
                        <td class="td-ref">
                          <div class="ref-cell">
                            <button v-if="!isLocked" type="button" class="tog" @click="toggleNode(item._id)">
                              <i class="ti" :class="collapsed.has(item._id) ? 'ti-chevron-right' : 'ti-chevron-down'"></i>
                            </button>
                            <i class="ti ti-folder ico-h1"></i>
                            <span class="ref-num">{{ item.ref }}</span>
                          </div>
                        </td>
                        <td colspan="3">
                          <input v-if="!isLocked" class="h-inp" v-model="item.libelle" placeholder="Titre de la rubrique…" />
                          <strong v-else class="h-ro">{{ item.libelle }}</strong>
                        </td>
                        <td><span class="type-badge type-h1">Rubrique</span></td>
                        <td v-if="!isLocked" class="td-act">
                          <button type="button" class="act-btn act-add" @click="addAfter(i, 'h2')"><i class="ti ti-folder-plus"></i></button>
                          <button type="button" class="act-btn act-del" @click="removeNode(i)"><i class="ti ti-trash"></i></button>
                        </td>
                      </tr>

                      <!-- H2 -->
                      <tr v-else-if="item.type === 'h2'" class="row-h2" v-show="isVisible(i)">
                        <td class="td-ref">
                          <div class="ref-cell ref-ind1">
                            <button v-if="!isLocked" type="button" class="tog" @click="toggleNode(item._id)">
                              <i class="ti" :class="collapsed.has(item._id) ? 'ti-chevron-right' : 'ti-chevron-down'"></i>
                            </button>
                            <i class="ti ti-folder ico-h2"></i>
                            <span class="ref-num">{{ item.ref }}</span>
                          </div>
                        </td>
                        <td colspan="3">
                          <input v-if="!isLocked" class="h-inp" v-model="item.libelle" placeholder="Sous-rubrique…" />
                          <span v-else class="h-ro">{{ item.libelle }}</span>
                        </td>
                        <td><span class="type-badge type-h2">Sous-rub.</span></td>
                        <td v-if="!isLocked" class="td-act">
                          <button type="button" class="act-btn act-add" @click="addAfter(i, 'h3')"><i class="ti ti-folder-plus"></i></button>
                          <button type="button" class="act-btn act-del" @click="removeNode(i)"><i class="ti ti-trash"></i></button>
                        </td>
                      </tr>

                      <!-- H3 -->
                      <tr v-else-if="item.type === 'h3'" class="row-h3" v-show="isVisible(i)">
                        <td class="td-ref">
                          <div class="ref-cell ref-ind2">
                            <button v-if="!isLocked" type="button" class="tog" @click="toggleNode(item._id)">
                              <i class="ti" :class="collapsed.has(item._id) ? 'ti-chevron-right' : 'ti-chevron-down'"></i>
                            </button>
                            <i class="ti ti-folder-minus ico-h3"></i>
                            <span class="ref-num">{{ item.ref }}</span>
                          </div>
                        </td>
                        <td colspan="3">
                          <input v-if="!isLocked" class="h-inp" v-model="item.libelle" placeholder="Niveau 3…" />
                          <span v-else class="h-ro">{{ item.libelle }}</span>
                        </td>
                        <td><span class="type-badge type-h3">Niv.3</span></td>
                        <td v-if="!isLocked" class="td-act">
                          <button type="button" class="act-btn act-add" @click="addAfter(i, 'item')"><i class="ti ti-plus"></i></button>
                          <button type="button" class="act-btn act-del" @click="removeNode(i)"><i class="ti ti-trash"></i></button>
                        </td>
                      </tr>

                      <!-- ITEM -->
                      <tr v-else class="row-item" :class="depthClass(i)" v-show="isVisible(i)">
                        <td class="td-ref">
                          <div class="ref-cell" :class="indentClass(i)">
                            <i class="ti ti-file-text ico-item"></i>
                            <span class="ref-item">{{ item.ref }}</span>
                          </div>
                        </td>
                        <td class="td-lib">
                          <input v-if="!isLocked" class="item-inp" v-model="item.libelle" placeholder="Description…" />
                          <span v-else>{{ item.libelle }}</span>
                        </td>
                        <td class="td-val">
                          <textarea v-if="!isLocked" class="val-ta" v-model="item.value" rows="2" placeholder="Observation…"></textarea>
                          <span v-else class="val-ro">{{ item.value || '—' }}</span>
                        </td>
                        <td v-if="!isLocked" class="td-act">
                          <button type="button" class="act-btn act-del" @click="removeNode(i)"><i class="ti ti-trash"></i></button>
                        </td>
                      </tr>

                    </template>
                  </tbody>
                </table>
              </div>

            </section>
          </div><!-- /col-right -->

        </div><!-- /pdc-grid -->

        <!-- ══ FOOTER ══ -->
        <footer class="pdc-footer">
          <div>
            <button v-if="!isLocked" type="button" class="btn btn-ghost" :disabled="processing" @click="annuler">
              <i class="ti ti-x"></i> Annuler
            </button>
            <button v-if="!isLocked" type="button" class="btn btn-save" :disabled="processing" @click="submit">
              <span v-if="processing" class="spin-dot"></span>
              <i v-else class="ti ti-device-floppy"></i>
              {{ form.id ? 'Mettre à jour' : 'Enregistrer' }}
            </button>
          </div>
          <div>
            <button v-if="pdc.id && pdc.validation_status === 'draft'" type="button" class="btn btn-sub" :disabled="processing" @click="soumettre">
              <i class="ti ti-send"></i> Soumettre
            </button>
            <template v-if="canManage && pdc.validation_status === 'in_review'">
              <button type="button" class="btn btn-ok" :disabled="processing" @click="valider('validated')"><i class="ti ti-circle-check"></i> Valider</button>
              <button type="button" class="btn btn-rej" :disabled="processing" @click="promptReject"><i class="ti ti-circle-x"></i> Rejeter</button>
            </template>
            <a v-if="pdc.id && (pdc.validation_status === 'validated' || canManage)"
               :href="`${props.formUrl}/${pdc.id}/pdf?download=1`" target="_blank" class="btn btn-pdf">
              <i class="ti ti-file-type-pdf"></i> PDF
            </a>
          </div>
        </footer>

      </div><!-- /pdc-body -->
    </div><!-- /pdc-shell -->

    <!-- ══ MODAL STRUCTURE ══ -->
    <Teleport to="body">
      <transition name="mfade">
        <div v-if="structPanel.show" class="modal-overlay" @click.self="structPanel.show = false">
          <div class="modal modal-struct">
            <div class="modal-hd">
              <div class="modal-av" style="background:#f0fdf4;color:#15803d"><i class="ti ti-layout-grid"></i></div>
              <div>
                <div class="modal-title">Structure du formulaire</div>
                <div class="modal-sub">Niveaux, numérotation et colonnes</div>
              </div>
              <button class="modal-close" @click="structPanel.show = false"><i class="ti ti-x"></i></button>
            </div>
            <div class="modal-body">
              <div class="msec">
                <div class="msec-title"><i class="ti ti-layers"></i> Niveaux actifs</div>
                <div class="level-grid">
                  <label v-for="lvl in LEVELS" :key="lvl.key" class="level-chk" :class="{ active: structPanel.levels[lvl.key] }">
                    <input type="checkbox" v-model="structPanel.levels[lvl.key]" />
                    <i class="ti" :class="lvl.icon" :style="`color:${lvl.color}`"></i>
                    <span>{{ lvl.label }}</span>
                  </label>
                </div>
              </div>
              <div class="msec">
                <div class="msec-title"><i class="ti ti-numbers"></i> Numérotation</div>
                <div class="num-grid">
                  <button v-for="ns in NUM_STYLES" :key="ns.key" type="button"
                    class="num-btn" :class="{ active: structPanel.numStyle === ns.key }"
                    @click="structPanel.numStyle = ns.key; recompute()">
                    <code>{{ ns.preview }}</code>
                    <span>{{ ns.label }}</span>
                  </button>
                </div>
              </div>
              <div class="msec">
                <div class="msec-title"><i class="ti ti-forms"></i> Types de champs autorisés</div>
                <div class="ft-grid">
                  <label v-for="ft in FIELD_TYPES" :key="ft.key" class="ft-chk" :class="{ active: structPanel.allowedFieldTypes.includes(ft.key) }">
                    <input type="checkbox" :checked="structPanel.allowedFieldTypes.includes(ft.key)" @change="toggleFieldType(ft.key)" />
                    <i class="ti" :class="ft.icon"></i>
                    <span>{{ ft.label }}</span>
                  </label>
                </div>
              </div>
            </div>
            <div class="modal-ft">
              <button type="button" class="btn btn-ghost" @click="structPanel.show = false">Fermer</button>
              <button type="button" class="btn btn-save" @click="applyStruct"><i class="ti ti-check"></i> Appliquer</button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- ══ MODAL IA ══ -->
    <Teleport to="body">
      <transition name="mfade">
        <div v-if="aiPanel.show" class="modal-overlay" @click.self="aiPanel.show = false">
          <div class="modal modal-ai">
            <div class="modal-hd">
              <div class="modal-av" style="background:linear-gradient(135deg,#6d28d9,#7c3aed);color:#fff"><i class="ti ti-brain"></i></div>
              <div>
                <div class="modal-title">Assistant IA — Suggestions QPC</div>
                <div class="modal-sub">Génération automatique basée sur la mission</div>
              </div>
              <button class="modal-close" @click="aiPanel.show = false"><i class="ti ti-x"></i></button>
            </div>
            <div class="modal-body">
              <div class="ai-ctx">
                <div class="ai-ctx-row"><span class="ai-lbl">Mission</span><span>{{ mission?.code_mission }} — {{ mission?.libelle }}</span></div>
                <div class="ai-ctx-row"><span class="ai-lbl">Entité</span><span>{{ mission?.entity_name || '—' }}</span></div>
                <div class="ai-ctx-row"><span class="ai-lbl">Phase</span><span>{{ assignment?.phase_label || assignment?.phase_code }}</span></div>
              </div>
              <div class="ai-field">
                <label class="ai-lbl">Contexte supplémentaire <span class="opt">(optionnel)</span></label>
                <textarea class="ai-ta" v-model="aiPanel.prompt" rows="3" placeholder="Ex: Focus sur la gestion des marchés publics…"></textarea>
              </div>
              <div class="ai-opts">
                <div class="ai-opt">
                  <label class="ai-lbl">Rubriques (H1)</label>
                  <select class="sel" v-model="aiPanel.nbH1">
                    <option v-for="n in [3,4,5,6,7,8,10]" :key="n" :value="n">{{ n }}</option>
                  </select>
                </div>
                <div class="ai-opt" v-if="structPanel.levels.h2">
                  <label class="ai-lbl">Sous-rub./H1</label>
                  <select class="sel" v-model="aiPanel.nbH2">
                    <option v-for="n in [2,3,4,5]" :key="n" :value="n">{{ n }}</option>
                  </select>
                </div>
                <div class="ai-opt">
                  <label class="ai-lbl">Items/section</label>
                  <select class="sel" v-model="aiPanel.nbItems">
                    <option v-for="n in [3,4,5,6,8,10]" :key="n" :value="n">{{ n }}</option>
                  </select>
                </div>
                <div class="ai-opt">
                  <label class="ai-lbl">Type par défaut</label>
                  <select class="sel" v-model="aiPanel.defaultFieldType">
                    <option v-for="ft in FIELD_TYPES" :key="ft.key" :value="ft.key">{{ ft.label }}</option>
                  </select>
                </div>
                <div class="ai-opt">
                  <label class="ai-lbl">Insertion</label>
                  <select class="sel" v-model="aiPanel.insertMode">
                    <option value="append">Ajouter à la suite</option>
                    <option value="replace">Remplacer tout</option>
                  </select>
                </div>
              </div>
              <div v-if="aiPanel.loading" class="ai-loading">
                <div class="ai-spinner"></div> L'IA génère votre questionnaire…
              </div>
              <div v-if="aiPanel.suggestions.length && !aiPanel.loading" class="ai-results">
                <div class="ai-results-hd">
                  <span><i class="ti ti-sparkles"></i> {{ aiPanel.suggestions.filter(x => x.type === 'item').length }} items dans {{ aiPanel.suggestions.filter(x => x.type === 'h1').length }} rubriques</span>
                  <button type="button" class="btn btn-ghost btn-sm" @click="runAI"><i class="ti ti-refresh"></i> Régénérer</button>
                </div>
                <div class="ai-preview">
                  <template v-for="(item, i) in aiPanel.suggestions" :key="i">
                    <div v-if="item.type === 'h1'" class="aip-h1"><i class="ti ti-folder"></i>{{ item.ref }} — {{ item.libelle }}</div>
                    <div v-else-if="item.type === 'h2'" class="aip-h2"><i class="ti ti-folder"></i>{{ item.ref }} — {{ item.libelle }}</div>
                    <div v-else-if="item.type === 'h3'" class="aip-h3"><i class="ti ti-folder-minus"></i>{{ item.ref }} — {{ item.libelle }}</div>
                    <div v-else class="aip-item" :class="{ selected: item._sel !== false }" @click="item._sel = item._sel === false">
                      <i class="ti" :class="item._sel !== false ? 'ti-checkbox' : 'ti-square'"></i>
                      <code class="aip-ref">{{ item.ref }}</code>
                      <span>{{ item.libelle }}</span>
                      <span class="aip-type">{{ fieldTypeLabel(item.field_type) }}</span>
                    </div>
                  </template>
                </div>
              </div>
            </div>
            <div class="modal-ft">
              <button type="button" class="btn btn-ghost" @click="aiPanel.show = false">Fermer</button>
              <button type="button" class="btn btn-ai" @click="runAI" :disabled="aiPanel.loading">
                <i class="ti" :class="aiPanel.loading ? 'ti-loader-2 spin' : 'ti-brain'"></i>
                {{ aiPanel.loading ? 'Génération…' : (aiPanel.suggestions.length ? 'Régénérer' : 'Générer') }}
              </button>
              <button v-if="aiPanel.suggestions.length && !aiPanel.loading" type="button" class="btn btn-ai-insert" @click="insertSuggestions">
                <i class="ti ti-table-import"></i> Insérer ({{ aiPanel.suggestions.filter(x => x.type !== 'item' || x._sel !== false).length }})
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- Toast -->
    <Teleport to="body">
      <transition name="toast-up">
        <div v-if="toast.show" class="toast" :class="`toast-${toast.type}`">
          <i :class="toast.type === 'success' ? 'ti ti-circle-check' : 'ti ti-alert-circle'"></i>
          {{ toast.msg }}
        </div>
      </transition>
    </Teleport>

  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
//import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'

// ── Composant utilitaire champ ─────────────────────────────────
const Field = {
  props: ['label'],
  template: `<div class="field"><lFabel class="lbl">{{ label }}</label><slot /></div>`,
}

// ── Constantes ─────────────────────────────────────────────────
const LEVELS = [
  { key: 'h1', label: 'Rubrique (niv.1)',        icon: 'ti-folder',       color: '#1565C0' },
  { key: 'h2', label: 'Sous-rubrique (niv.2)',   icon: 'ti-folder',       color: '#0891b2' },
  { key: 'h3', label: 'Sous-sous-rub. (niv.3)',  icon: 'ti-folder-minus', color: '#d97706' },
]
const NUM_STYLES = [
  { key: 'arabic', label: 'Arabe',  preview: '1 / 1.1 / 1.1.1' },
  { key: 'roman',  label: 'Romain', preview: 'I / I.A / I.A.1'  },
  { key: 'alpha',  label: 'Alpha',  preview: 'A / A.1 / A.1.a'  },
]
const FIELD_TYPES = [
  { key: 'text',       label: 'Texte',       icon: 'ti-text-size'      },
  { key: 'textarea',   label: 'Zone texte',  icon: 'ti-align-left'     },
  { key: 'yesno',      label: 'Oui/Non',     icon: 'ti-toggle-right'   },
  { key: 'conformite', label: 'Conformité',  icon: 'ti-circle-check'   },
  { key: 'risque',     label: 'Risque',      icon: 'ti-alert-triangle' },
  { key: 'number',     label: 'Nombre',      icon: 'ti-hash'           },
  { key: 'date',       label: 'Date',        icon: 'ti-calendar'       },
]

let _uid = 0
const uid = () => `n${++_uid}`
const slugify = (s: string) => s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]/g, '')

// ── Props ──────────────────────────────────────────────────────
const props = defineProps({
  mission:        { type: Object,  default: null },
  assignment:     { type: Object,  default: null },
  form:           { type: Object,  default: null },
  pdcList:        { type: Array,   default: () => [] },
  auditorRole:    { type: String,  default: null },
  missionId:      { type: Number,  default: null },
  assignmentId:   { type: Number,  default: null },
  currentAuditor: { type: Object,  default: null },
  backUrl:        { type: String,  default: '' },
  formUrl:        { type: String,  default: '' },
  aiUrl:          { type: String,  default: '' },
})

// ── Helpers ────────────────────────────────────────────────────
const canManage         = computed(() => ['DM', 'CM'].includes(props.auditorRole ?? ''))
const pdc               = reactive<Record<string, any>>(props.form ? { ...props.form } : {})
const isLocked          = computed(() => pdc.validation_status === 'validated' || (pdc.validation_status === 'in_review' && !canManage.value))
const currentAuditorName = computed(() => {
  const a = props.currentAuditor as any
  return a ? [a.last_name, a.first_name].filter(Boolean).join(' ').trim() || a.audit_code || '' : ''
})
const safeJson = (v: any, fb: any = []) => {
  if (Array.isArray(v)) return v
  try { return JSON.parse(v ?? '[]') } catch { return fb }
}

// ── Form meta ──────────────────────────────────────────────────
const form = reactive({
  id:             props.form?.id            ?? null,
  code:           props.form?.code          ?? '',
  intitule_qpc:   props.form?.intitule_qpc  ?? '',
  fait_par:       props.form?.fait_par      || currentAuditorName.value,
  revue_par:      props.form?.revue_par     ?? '',
  date_fait:      props.form?.date_fait     ?? '',
  date_revue:     props.form?.date_revue    ?? '',
})

// ── Items QPC ──────────────────────────────────────────────────
const qpcItems  = reactive<any[]>(safeJson(props.form?.qpc_items).map((x: any) => ({ ...x, _id: uid() })))
const collapsed = reactive<Set<string>>(new Set())

// ── Structure ──────────────────────────────────────────────────
const structPanel = reactive({
  show: false,
  levels: { h1: true, h2: true, h3: false },
  numStyle: 'arabic' as 'arabic' | 'roman' | 'alpha',
  allowedFieldTypes: ['text', 'yesno', 'conformite', 'risque', 'number', 'date'],
})

function toggleFieldType(k: string) {
  const i = structPanel.allowedFieldTypes.indexOf(k)
  i === -1 ? structPanel.allowedFieldTypes.push(k) : structPanel.allowedFieldTypes.splice(i, 1)
}
function applyStruct() { recompute(); structPanel.show = false; showToast('success', 'Structure appliquée') }

// ── Numérotation ───────────────────────────────────────────────
function buildRef(counters: number[]) {
  const toRoman = (n: number) => {
    const v = [1000,900,500,400,100,90,50,40,10,9,5,4,1]
    const s = ['M','CM','D','CD','C','XC','L','XL','X','IX','V','IV','I']
    let r = ''; v.forEach((val, i) => { while (n >= val) { r += s[i]; n -= val } }); return r
  }
  const A = (n: number) => String.fromCharCode(64 + n)
  const a = (n: number) => String.fromCharCode(96 + n)
  const ns = structPanel.numStyle

  if (ns === 'roman') {
    if (counters.length === 1) return toRoman(counters[0])
    if (counters.length === 2) return `${toRoman(counters[0])}.${A(counters[1])}`
    if (counters.length === 3) return `${toRoman(counters[0])}.${A(counters[1])}.${counters[2]}`
    return `${toRoman(counters[0])}.${A(counters[1])}.${counters[2]}.${a(counters[3])}`
  }
  if (ns === 'alpha') {
    if (counters.length === 1) return A(counters[0])
    if (counters.length === 2) return `${A(counters[0])}.${counters[1]}`
    if (counters.length === 3) return `${A(counters[0])}.${counters[1]}.${a(counters[2])}`
    return `${A(counters[0])}.${counters[1]}.${a(counters[2])}.${counters[3]}`
  }
  // arabic
  return counters.join('.')
}

function recompute() {
  // Compteurs par niveau — ci se remet à 0 à chaque nouveau parent direct
  let c1 = 0, c2 = 0, c3 = 0, ci = 0
  // Dernier ancêtre connu à chaque niveau
  let last1 = 0, last2 = 0, last3 = 0

  qpcItems.forEach(item => {
    if (item.type === 'h1') {
      c1++; c2 = 0; c3 = 0; ci = 0
      last1 = c1; last2 = 0; last3 = 0
      item.ref = buildRef([c1])

    } else if (item.type === 'h2') {
      c2++; c3 = 0; ci = 0          // reset h3 et items à chaque nouvelle h2
      last2 = c2; last3 = 0
      item.ref = buildRef([last1, c2])

    } else if (item.type === 'h3') {
      c3++; ci = 0                   // reset items à chaque nouvelle h3
      last3 = c3
      item.ref = buildRef([last1, last2, c3])

    } else {
      // Item feuille : ci repart de 1 sous chaque parent direct
      ci++
      const parts: number[] = []
      if (last1) parts.push(last1)
      if (last2) parts.push(last2)
      if (last3) parts.push(last3)
      parts.push(ci)
      item.ref = buildRef(parts)
    }
  })
}

function addNode(type: 'h1' | 'h2' | 'h3' | 'item') {
  qpcItems.push({ _id: uid(), type, ref: '', libelle: '', value: '' })
  recompute()
}
function addAfter(idx: number, type: 'h1' | 'h2' | 'h3' | 'item') {
  qpcItems.splice(idx + 1, 0, { _id: uid(), type, ref: '', libelle: '', value: '' })
  recompute()
}
function removeNode(i: number) { qpcItems.splice(i, 1); recompute() }
function toggleNode(id: string) { collapsed.has(id) ? collapsed.delete(id) : collapsed.add(id) }

function nodeLevel(t: string) { return t === 'h1' ? 1 : t === 'h2' ? 2 : t === 'h3' ? 3 : 4 }

function isVisible(idx: number): boolean {
  const item = qpcItems[idx]
  for (let i = idx - 1; i >= 0; i--) {
    const anc = qpcItems[i]
    if (nodeLevel(anc.type) < nodeLevel(item.type) && collapsed.has(anc._id)) return false
    if (nodeLevel(anc.type) < nodeLevel(item.type)) break
  }
  return true
}

function indentClass(idx: number) {
  // Chercher le parent direct (premier élément de niveau inférieur en remontant)
  for (let i = idx - 1; i >= 0; i--) {
    const t = qpcItems[i].type
    if (t === 'h3') return 'ref-ind3'
    if (t === 'h2') return 'ref-ind2'
    if (t === 'h1') return 'ref-ind1'
    // Si on rencontre un autre item on continue à remonter
  }
  return ''
}
function depthClass(idx: number) {
  // Même logique : trouver le parent direct
  for (let i = idx - 1; i >= 0; i--) {
    const t = qpcItems[i].type
    if (t === 'h3') return 'depth-3'
    if (t === 'h2') return 'depth-2'
    if (t === 'h1') return 'depth-1'
  }
  return ''
}

// ── Compteurs ──────────────────────────────────────────────────
const totalLeaves  = computed(() => qpcItems.filter(x => x.type === 'item').length)
const filledLeaves = computed(() => qpcItems.filter(x => x.type === 'item' && x.value?.toString().trim()).length)
const fillPct      = computed(() => totalLeaves.value === 0 ? 0 : Math.round(filledLeaves.value / totalLeaves.value * 100))

// ── Fichiers ───────────────────────────────────────────────────
const isDragOver    = ref(false)
const uploadedFiles = reactive<any[]>([])
const savedFiles    = reactive<any[]>(safeJson(props.form?.attached_files))

function onDrop(e: DragEvent) { isDragOver.value = false; if (isLocked.value) return; Array.from(e.dataTransfer?.files ?? []).forEach(addFile) }
function onFileSelect(e: Event) { Array.from((e.target as HTMLInputElement).files ?? []).forEach(addFile); (e.target as HTMLInputElement).value = '' }
function addFile(f: File) { if (f.size > 10 * 1024 * 1024) { showToast('error', `${f.name} dépasse 10 Mo`); return }; uploadedFiles.push({ name: f.name, size: f.size, file: f }) }
function fileIcon(n: string) {
  const e = n.split('.').pop()?.toLowerCase()
  return e === 'pdf' ? 'ti-file-type-pdf' : ['xlsx','xls'].includes(e ?? '') ? 'ti-file-type-xls' : ['docx','doc'].includes(e ?? '') ? 'ti-file-type-doc' : ['png','jpg','jpeg'].includes(e ?? '') ? 'ti-photo' : 'ti-file'
}
function fileSize(b: number) { return b < 1024 ? b + ' o' : b < 1048576 ? (b / 1024).toFixed(1) + ' Ko' : (b / 1048576).toFixed(1) + ' Mo' }

// ── Import Excel ───────────────────────────────────────────────
// Détection type depuis col A : entier → h1 | x.y → h2 | vide/↳ → item
function detectType(colA: any): 'h1' | 'h2' | 'item' {
  const v = String(colA ?? '').trim()
  if (!v || v === '\u21b3') return 'item'
  if (/^\d+\.\d/.test(v)) return 'h2'
  if (/^\d+$/.test(v))    return 'h1'
  return 'item'
}

const xlsRef = ref<HTMLInputElement | null>(null)
async function importExcel(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]; if (!file) return
  const fd = new FormData(); fd.append('file', file)
  try {
    const res = await axios.post(`${props.formUrl}/import-excel`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    const raw: any[] = res.data.items ?? []
    const mapped = raw.map(x => ({
      _id:     uid(),
      type:    detectType(x.num ?? x.type),
      libelle: x.libelle ?? '',
      value:   '',
    }))
    qpcItems.splice(0, qpcItems.length, ...mapped)
    recompute()
    showToast('success', `${mapped.length} lignes importées`)
  } catch (err: any) { showToast('error', err.response?.data?.error ?? 'Erreur import') }
  if (xlsRef.value) xlsRef.value.value = ''
}

// ── Assistant IA ───────────────────────────────────────────────
const aiLoading = ref(false)
const aiPanel = reactive({
  show: false, loading: false,
  prompt: '', nbH1: 5, nbH2: 3, nbItems: 5,
  defaultFieldType: 'text',
  insertMode: 'append' as 'append' | 'replace',
  suggestions: [] as any[],
})

async function runAI() {
  aiPanel.loading = true; aiLoading.value = true
  try {
    const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
    const url = props.aiUrl || `${props.formUrl}/ai-suggest`
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
      body: JSON.stringify({
        mission_id: props.missionId, assignment_id: props.assignmentId,
        mission_name: props.mission?.libelle, entity_name: props.mission?.entity_name,
        objectif: (props.mission as any)?.objectif, phase_code: props.assignment?.phase_code,
        custom_prompt: aiPanel.prompt, nb_h1: aiPanel.nbH1,
        nb_h2: structPanel.levels.h2 ? aiPanel.nbH2 : 0,
        nb_h3: structPanel.levels.h3 ? 2 : 0,
        nb_items: aiPanel.nbItems, use_h2: structPanel.levels.h2, use_h3: structPanel.levels.h3,
        num_style: structPanel.numStyle, default_field_type: aiPanel.defaultFieldType,
      }),
    })
    const data = await res.json()
    if (!res.ok) throw new Error(data?.message ?? data?.error ?? 'Erreur IA')
    const raw: any[] = data.items ?? []
    raw.forEach(it => { it._id = uid(); if (it.type === 'item') it._sel = true })
    aiPanel.suggestions = raw
    if (!raw.length) showToast('error', "L'IA n'a pas pu générer de suggestions")
  } catch (err: any) {
    showToast('error', 'Erreur IA : ' + (err.message ?? 'Service indisponible'))
  } finally { aiPanel.loading = false; aiLoading.value = false }
}

function insertSuggestions() {
  const toInsert = aiPanel.suggestions
    .filter(it => it.type !== 'item' || it._sel !== false)
    .map(({ _sel, ...rest }) => ({ ...rest, _id: uid(), value: rest.value ?? '', field_type: rest.field_type || aiPanel.defaultFieldType }))
  if (aiPanel.insertMode === 'replace') qpcItems.splice(0, qpcItems.length, ...toInsert)
  else qpcItems.push(...toInsert)
  recompute()
  aiPanel.show = false
  showToast('success', `${toInsert.length} éléments insérés`)
}

// ── Liste / Navigation ─────────────────────────────────────────
const search = ref('')
const filteredPdcs = computed(() => {
  const q = search.value.toLowerCase()
  return (props.pdcList as any[]).filter(p => !q || p.code?.toLowerCase().includes(q) || p.intitule_qpc?.toLowerCase().includes(q))
})
function loadPdc(item: any) { router.visit(`${props.formUrl}/${item.id}/edit?mission_id=${props.missionId}&assignment_id=${props.assignmentId}`) }
function deletePdc(item: any) {
  if (!confirm(`Supprimer ${item.code} ?`)) return
  router.delete(`${props.formUrl}/${item.id}`, {
    preserveScroll: true, data: { mission_id: props.missionId, assignment_id: props.assignmentId },
    onSuccess: () => showToast('success', 'Supprimé'), onError: () => showToast('error', 'Erreur'),
  })
}

// ── CRUD ───────────────────────────────────────────────────────
const processing = ref(false)

function annuler() {
  Object.assign(form, { id: null, code: '', intitule_qpc: '', fait_par: currentAuditorName.value, revue_par: '', date_fait: '', date_revue: '' })
  qpcItems.splice(0, qpcItems.length)
  uploadedFiles.splice(0, uploadedFiles.length)
  Object.assign(pdc, {})
}

async function submit() {
  if (isLocked.value) return
  processing.value = true
  const fd = new FormData()
  ;['id', 'intitule_qpc', 'fait_par', 'revue_par', 'date_fait', 'date_revue'].forEach(k => {
    const v = (form as any)[k]; if (v != null) fd.append(k, String(v))
  })
  fd.append('mission_id', String(props.missionId))
  fd.append('assignment_id', String(props.assignmentId))
  fd.append('qpc_items', JSON.stringify(qpcItems.map(({ _id, ...r }) => r)))
  fd.append('deleted_files', JSON.stringify(savedFiles.map((f: any) => f.path).filter(Boolean)))
  fd.append('struct_config', JSON.stringify({ levels: structPanel.levels, numStyle: structPanel.numStyle, allowedFieldTypes: structPanel.allowedFieldTypes }))
  uploadedFiles.forEach(f => fd.append('attachments[]', f.file, f.name))
  if (form.id) fd.append('_method', 'PUT')
  const url = form.id ? `${props.formUrl}/${form.id}` : props.formUrl
  router.post(url, fd, {
    forceFormData: true, preserveScroll: true,
    onSuccess: (page: any) => {
      processing.value = false
      const n = page.props?.form
      if (n) {
        if (!form.id) form.id = n.id
        if (n.code) form.code = n.code
        Object.assign(pdc, n)
        const sf = safeJson(n.attached_files)
        savedFiles.splice(0, savedFiles.length, ...sf)
        uploadedFiles.splice(0, uploadedFiles.length)
      }
      showToast('success', 'Questionnaire enregistré')
    },
    onError: () => { processing.value = false; showToast('error', 'Erreur — vérifiez les champs') },
    onFinish: () => { processing.value = false },
  })
}

// ── Workflow validation ────────────────────────────────────────
async function soumettre() {
  if (!form.id) { showToast('error', 'Enregistrez d\'abord.'); return }
  if (!confirm('Soumettre ?')) return
  await apiPost(`${props.formUrl}/${form.id}/soumettre`, { mission_id: props.missionId, assignment_id: props.assignmentId },
    (j: any) => { pdc.validation_status = j.status; showToast('success', 'QPC soumis') })
}
async function valider(action: string, note?: string) {
  await apiPost(`${props.formUrl}/${form.id}/valider`, { mission_id: props.missionId, assignment_id: props.assignmentId, action, note },
    (j: any) => { pdc.validation_status = j.status; showToast('success', action === 'validated' ? 'QPC validé ✓' : 'QPC rejeté') })
}
function promptReject() { const n = prompt('Motif du rejet :'); if (!n?.trim()) return; valider('rejected', n) }
async function apiPost(url: string, body: object, onOk: (j: any) => void) {
  processing.value = true
  try {
    const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
    const r = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, body: JSON.stringify(body) })
    const j = await r.json()
    if (!r.ok) throw new Error(j?.message ?? 'Erreur')
    onOk(j)
  } catch (e: any) { showToast('error', e.message) } finally { processing.value = false }
}

// ── Toast ──────────────────────────────────────────────────────
const toast = ref({ show: false, type: 'success', msg: '' })
let tt: any
function showToast(type: string, msg: string) { if (tt) clearTimeout(tt); toast.value = { show: true, type, msg }; tt = setTimeout(() => toast.value.show = false, 3200) }

// ── Labels ─────────────────────────────────────────────────────
function vstLbl(s: string) { return ({ draft: 'Brouillon', in_review: 'En attente', validated: 'Validé ✓', rejected: 'Rejeté' } as any)[s] ?? s }
function vstIcon(s: string) { return ({ draft: 'ti ti-pencil', in_review: 'ti ti-clock', validated: 'ti ti-circle-check', rejected: 'ti ti-circle-x' } as any)[s] ?? 'ti ti-circle' }
function fieldTypeLabel(k: string) { return FIELD_TYPES.find(f => f.key === k)?.label ?? k }
</script>

<style scoped>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0 }

/* ── Layout ─────────────────────────────────────────────────── */
.pdc-shell  { display: flex; flex-direction: column; min-height: 100vh; background: #f0f4f8; font-family: 'Segoe UI', system-ui, sans-serif; --mc: #1565C0 }
.pdc-body   { flex: 1; padding: 16px 20px 28px; display: flex; flex-direction: column; gap: 14px }
.pdc-grid   { display: grid; grid-template-columns: 360px 1fr; gap: 14px }
@media (max-width: 1100px) { .pdc-grid { grid-template-columns: 1fr } }
.pdc-col-left, .pdc-col-right { display: flex; flex-direction: column; gap: 14px }

/* ── Header ─────────────────────────────────────────────────── */
.pdc-header { position: sticky; top: 0; z-index: 100; background: #fff; border-bottom: 1px solid #e2e8f0; box-shadow: 0 1px 4px rgba(0,0,0,.06); padding: 0 20px }
.pdc-hrow   { display: flex; align-items: center; gap: 12px; min-height: 60px; padding: 8px 0; flex-wrap: wrap }
.pdc-back   { display: flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 8px; background: #f1f5f9; border: 1px solid #e2e8f0; color: #64748b; text-decoration: none; font-size: .9rem; transition: all .15s; flex-shrink: 0 }
.pdc-back:hover { background: var(--mc); color: #fff; border-color: var(--mc) }
.pdc-hinfo  { flex: 1; min-width: 0 }
.pdc-chips  { display: flex; align-items: center; gap: 5px; flex-wrap: wrap; margin-bottom: 3px }
.pdc-chip   { display: inline-flex; align-items: center; gap: 3px; font-size: .62rem; font-weight: 700; padding: 2px 8px; border-radius: 10px; text-transform: uppercase; letter-spacing: .04em }
.chip-draft     { background: #f1f5f9; color: #64748b }
.chip-in_review { background: #e3f2fd; color: #1565C0; border: 1px solid rgba(21,101,192,.2) }
.chip-validated { background: #d1e7dd; color: #0f5132; border: 1px solid rgba(15,81,50,.2) }
.chip-rejected  { background: #f8d7da; color: #842029; border: 1px solid rgba(132,32,41,.2) }
.chip-type      { background: rgba(21,101,192,.1); color: var(--mc) }
.chip-role-DM   { background: rgba(251,191,36,.2); color: #d97706 }
.chip-role-CM   { background: rgba(21,101,192,.12); color: #1565C0 }
.chip-role-AS   { background: rgba(22,163,74,.12); color: #15803d }
.chip-role-AJ   { background: rgba(124,58,237,.12); color: #6d28d9 }
.pdc-code   { font-family: monospace; font-size: .68rem; font-weight: 700; padding: 2px 8px; border-radius: 5px; border: 1px solid color-mix(in srgb, var(--mc) 30%, transparent); background: color-mix(in srgb, var(--mc) 8%, transparent); color: var(--mc) }
.pdc-title  { font-size: .92rem; font-weight: 700; color: #1a1a2e }
.pdc-meta   { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 2px }
.pdc-meta span { display: inline-flex; align-items: center; gap: 4px; font-size: .69rem; color: #64748b }
.pdc-hactions { display: flex; gap: 6px; margin-left: auto }

/* Banners */
.pdc-banner      { display: flex; align-items: center; gap: 8px; padding: 7px 0 10px; font-size: .77rem; border-top: 1px solid #f1f5f9 }
.banner-lock     { color: #0f5132 }
.banner-review   { color: #1565C0 }

/* Progress bar */
.pdc-progress-bar { display: flex; align-items: center; gap: 10px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px }
.pb-info  { display: flex; align-items: center; gap: 8px; flex: 1 }
.pb-code  { font-family: monospace; font-size: .72rem; font-weight: 700; color: var(--mc) }
.pb-stats { font-size: .72rem; color: #64748b }
.pb-track { flex: 1; height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden; min-width: 60px }
.pb-fill  { height: 100%; background: var(--mc); border-radius: 3px; transition: width .4s }
.pb-pct   { font-size: .72rem; font-weight: 700; color: var(--mc); min-width: 34px; text-align: right }

/* ── Cards ──────────────────────────────────────────────────── */
.card      { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; position: relative }
.card-full { flex: 1; display: flex; flex-direction: column }
.card-label { position: absolute; top: -10px; left: 14px; background: #fff; padding: 0 8px; font-size: .64rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--mc); border: 1px solid color-mix(in srgb, var(--mc) 30%, transparent); border-radius: 4px; display: inline-flex; align-items: center; gap: 5px; z-index: 1; white-space: nowrap }
.card-body { padding: 18px 14px 14px; display: flex; flex-direction: column; gap: 9px }

/* Inputs */
.lbl  { font-size: .7rem; font-weight: 600; color: #475569; display: block; margin-bottom: 3px }
.inp  { width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 6px 10px; font-size: .81rem; color: #1a1a2e; background: #fff; outline: none; transition: border-color .12s; font-family: inherit }
.inp:focus { border-color: var(--mc); box-shadow: 0 0 0 3px color-mix(in srgb, var(--mc) 12%, transparent) }
.inp-ro { background: #f8fafc; cursor: default; color: #64748b }
.inp:disabled { background: #f8fafc; color: #94a3b8; cursor: not-allowed }
.field { display: flex; flex-direction: column }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 9px }
.hidden { display: none }

/* ── Dropzone ───────────────────────────────────────────────── */
.dropzone { border: 2px dashed #d1d5db; border-radius: 8px; padding: 18px 14px; text-align: center; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 4px; font-size: .78rem; color: #475569; background: #f8fafc; transition: all .15s }
.dropzone:hover:not(.dz-locked), .dz-over { border-color: var(--mc); background: color-mix(in srgb, var(--mc) 5%, white) }
.dz-locked { cursor: default; opacity: .6 }
.dz-ico { font-size: 1.4rem; color: #94a3b8 }
.file-item { display: flex; align-items: center; gap: 8px; padding: 5px 8px; border-radius: 6px; background: #f8fafc; border: 1px solid #e9ecef; font-size: .76rem; margin-top: 4px }
.file-saved { background: #f0fdf4; border-color: #bbf7d0 }
.file-name  { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500; color: #1a1a2e }
.file-link  { color: var(--mc); text-decoration: none }
.file-link:hover { text-decoration: underline }
.file-size  { font-size: .64rem; color: #94a3b8; flex-shrink: 0 }
.file-badge { font-size: .58rem; font-weight: 700; padding: 1px 5px; border-radius: 4px; background: #fef9c3; color: #854d0e; flex-shrink: 0 }
.file-del   { background: none; border: none; cursor: pointer; color: #94a3b8; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; border-radius: 4px; font-size: .7rem; flex-shrink: 0 }
.file-del:hover { background: #fee2e2; color: #ef4444 }
.saved-sep  { font-size: .64rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; padding: 8px 0 2px; border-top: 1px dashed #e2e8f0; margin-top: 4px }

/* ── Tableau liste ──────────────────────────────────────────── */
.tbl { width: 100%; border-collapse: collapse; font-size: .79rem }
.tbl thead th { background: #1e3a5f; color: rgba(255,255,255,.85); font-size: .64rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; padding: 7px 10px; border: none; white-space: nowrap }
.tbl tbody td { padding: 5px 10px; border: 1px solid #e9ecef; vertical-align: middle }
.tbl-row { cursor: pointer }
.tbl-row:hover td { background: #f8fafc }
.td-empty { text-align: center; color: #adb5bd; font-size: .74rem; padding: 12px !important }
.td-code  { font-weight: 700; color: var(--mc); font-size: .75rem; font-family: monospace }
.td-acts  { text-align: right; white-space: nowrap }
.act-btn  { display: inline-flex; align-items: center; justify-content: center; width: 25px; height: 25px; border-radius: 5px; border: none; cursor: pointer; font-size: .7rem; margin-left: 3px }
.act-edit { background: #e3f2fd; color: #1565C0 } .act-edit:hover { background: #1565C0; color: #fff }
.act-del  { background: #fee2e2; color: #ef4444 } .act-del:hover  { background: #ef4444; color: #fff }
.act-add  { background: #e3f2fd; color: #1565C0 } .act-add:hover  { background: #1565C0; color: #fff }

/* ── Tableau QPC ────────────────────────────────────────────── */
.qtbar   { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; padding: 10px 14px }
.qtbar-l, .qtbar-r { display: flex; align-items: center; gap: 6px; flex-wrap: wrap }
.tbl-wrap { overflow-x: auto; flex: 1 }
.qtbl { width: 100%; border-collapse: collapse; font-size: .79rem }
.qtbl thead th { background: #1e3a5f; color: rgba(255,255,255,.85); font-size: .63rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; padding: 8px 10px; border: none; white-space: nowrap; position: sticky; top: 0; z-index: 2 }
.th-ref  { width: 100px }
.th-lib  { min-width: 180px }
.th-val  { min-width: 200px }
.th-act  { width: 52px }
.qtbl tbody td { padding: 4px 8px; border: 1px solid #e9ecef; vertical-align: middle }

/* Lignes hiérarchiques */
.row-h1   { background: #1e3a5f }
.row-h1 td { padding: 6px 8px; border-color: #2d4a6f }
.row-h1:hover { background: #243f6e }
.row-h2   { background: #2B7FD4 }
.row-h2 td { padding: 5px 8px; border-color: #3a8add }
.row-h2:hover { background: #2471c2 }
.row-h3   { background: #e3f2fd }
.row-h3 td { padding: 4px 8px; border-color: #bbdefb }
.row-h3:hover { background: #d0e8fb }
.row-item { background: #fff }
.row-item:nth-child(even) { background: #fafbfc }
.row-item:hover td { background: #f0f6ff }
.depth-1 { border-left: 3px solid #1565C0 }
.depth-2 { border-left: 3px solid #2B7FD4 }
.depth-3 { border-left: 3px solid #d97706 }

/* Cellule ref */
.td-ref   { white-space: nowrap }
.ref-cell { display: flex; align-items: center; gap: 5px }
.ref-ind1 { padding-left: 10px }
.ref-ind2 { padding-left: 20px }
.ref-ind3 { padding-left: 30px }
.tog { background: none; border: none; cursor: pointer; color: rgba(255,255,255,.7); width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; font-size: .7rem; flex-shrink: 0; padding: 0; border-radius: 3px }
.tog:hover { background: rgba(255,255,255,.15); color: #fff }
.row-h3 .tog { color: #1565C0 }
.row-h3 .tog:hover { background: rgba(21,101,192,.1) }
.ico-h1 { color: #60a5fa; font-size: .8rem; flex-shrink: 0 }
.ico-h2 { color: #FFD700; font-size: .8rem; flex-shrink: 0 }
.ico-h3 { color: #d97706; font-size: .78rem; flex-shrink: 0 }
.ico-item { color: #94a3b8; font-size: .72rem; flex-shrink: 0 }
.ref-num  { color: #fff; font-weight: 800; font-size: .8rem }
.row-h3 .ref-num { color: #1e3a5f }
.ref-item { font-family: monospace; font-size: .7rem; color: var(--mc); font-weight: 600 }

/* Inputs dans tableau */
.h-inp  { background: transparent; border: none; border-bottom: 1px solid rgba(255,255,255,.3); color: #fff; font-weight: 700; font-size: .8rem; outline: none; padding: 2px 4px; width: 100%; font-family: inherit }
.h-inp::placeholder { color: rgba(255,255,255,.4) }
.h-ro   { color: #fff; font-weight: 700; font-size: .8rem }
.row-h3 .h-inp { color: #1e3a5f; border-bottom-color: #93c5fd }
.row-h3 .h-ro  { color: #1e3a5f }
.item-inp { width: 100%; border: none; font-size: .79rem; color: #1a1a2e; background: transparent; outline: none; padding: 2px 4px; font-family: inherit }
.item-inp:focus { background: #f0f6ff; border-radius: 3px }
.val-ta  { width: 100%; border: 1px solid #e2e8f0; border-radius: 4px; font-size: .74rem; color: #1a1a2e; background: #fff; outline: none; padding: 3px 6px; resize: vertical; font-family: inherit; min-height: 40px }
.val-ro  { font-size: .78rem; color: #374151; white-space: pre-wrap }

/* Oui/Non/Conformité/Risque */
.yn-group { display: flex; gap: 3px; flex-wrap: wrap }
.yn { height: 24px; padding: 0 7px; border-radius: 4px; border: 1px solid #d1d5db; background: #f8fafc; font-size: .68rem; font-weight: 600; cursor: pointer; font-family: inherit; transition: all .12s }
.yn.active     { background: #15803d; color: #fff; border-color: #15803d }
.yn-no.active  { background: #dc2626; color: #fff; border-color: #dc2626 }
.yn-na.active  { background: #64748b; color: #fff; border-color: #64748b }
.yn-c.active   { background: #15803d; color: #fff; border-color: #15803d }
.yn-nc.active  { background: #dc2626; color: #fff; border-color: #dc2626 }
.yn-pp.active  { background: #d97706; color: #fff; border-color: #d97706 }
.yn-r-faible.active  { background: #15803d; color: #fff; border-color: #15803d }
.yn-r-moyen.active   { background: #d97706; color: #fff; border-color: #d97706 }
.yn-r-eleve.active   { background: #dc2626; color: #fff; border-color: #dc2626 }
.yn-r-critique.active { background: #7c3aed; color: #fff; border-color: #7c3aed }

/* Badges valeur (lecture seule) */
.val-badge       { font-size: .67rem; font-weight: 700; padding: 2px 8px; border-radius: 10px; text-transform: uppercase }
.vb-oui          { background: #d1fae5; color: #065f46 }
.vb-non          { background: #fee2e2; color: #991b1b }
.vb-na           { background: #f1f5f9; color: #64748b }
.vb-conforme     { background: #d1fae5; color: #065f46 }
.vb-nonconforme  { background: #fee2e2; color: #991b1b }
.vb-partiellement { background: #fef3c7; color: #92400e }
.vb-r-faible     { background: #d1fae5; color: #065f46 }
.vb-r-moyen      { background: #fef3c7; color: #92400e }
.vb-r-eleve      { background: #fee2e2; color: #991b1b }
.vb-r-critique   { background: #ede9fe; color: #4c1d95 }


/* Type select */
.type-badge   { font-size: .58rem; font-weight: 700; padding: 2px 6px; border-radius: 3px; text-transform: uppercase; letter-spacing: .04em }
.type-h1 { background: rgba(96,165,250,.15); color: #1565C0 }
.type-h2 { background: rgba(255,215,0,.2);   color: #1e3a5f }
.type-h3 { background: rgba(217,119,6,.15);  color: #d97706 }

/* Vide */
.q-empty { text-align: center; padding: 48px 20px !important; color: #94a3b8 }
.q-empty i { font-size: 2rem; display: block; margin-bottom: 8px }
.q-empty p { font-size: .79rem; margin-bottom: 10px }

/* ── Boutons ────────────────────────────────────────────────── */
.btn         { display: inline-flex; align-items: center; gap: 5px; padding: 6px 13px; border-radius: 6px; font-size: .74rem; font-weight: 600; border: 1px solid transparent; cursor: pointer; font-family: inherit; text-decoration: none; transition: all .12s }
.btn:disabled { opacity: .5; cursor: not-allowed }
.btn-import  { background: #f0f9ff; color: #0369a1; border-color: #bae6fd } .btn-import:hover { background: #e0f2fe }
.btn-tpl     { background: #f0fdf4; color: #15803d; border-color: #bbf7d0 } .btn-tpl:hover { background: #dcfce7 }
.btn-ai      { background: linear-gradient(135deg,#6d28d9,#7c3aed); color: #fff; border-color: #6d28d9; box-shadow: 0 1px 4px rgba(109,40,217,.25) } .btn-ai:hover { filter: brightness(1.1) } .btn-ai:disabled { opacity: .5; cursor: not-allowed }
.btn-ai-insert { background: #7c3aed; color: #fff; border-color: #7c3aed } .btn-ai-insert:hover { background: #6d28d9 }
.btn-struct  { width: 36px; height: 36px; padding: 0; justify-content: center; background: #f0fdf4; color: #15803d; border-color: #bbf7d0 } .btn-struct:hover { background: #15803d; color: #fff }
.btn-h1      { background: #1e3a5f; color: #fff; border-color: #1e3a5f } .btn-h1:hover { background: #152d4a }
.btn-h2      { background: #2B7FD4; color: #fff; border-color: #2B7FD4 } .btn-h2:hover { background: #2471c2 }
.btn-h3      { background: #e3f2fd; color: #1565C0; border-color: #bbdefb } .btn-h3:hover { background: #dbeafe }
.btn-item    { background: #1e293b; color: #fff; border-color: #1e293b } .btn-item:hover { background: #0f172a }
.btn-ghost   { background: transparent; color: #64748b; border-color: #d1d5db } .btn-ghost:hover { background: #f1f5f9; color: #1a1a2e }
.btn-save    { background: var(--mc); color: #fff; border-color: var(--mc) } .btn-save:hover { filter: brightness(1.1) }
.btn-sub     { background: #0f766e; color: #fff; border-color: #0f766e } .btn-sub:hover { background: #0d6460 }
.btn-ok      { background: #15803d; color: #fff; border-color: #15803d } .btn-ok:hover { background: #166534 }
.btn-rej     { background: #dc2626; color: #fff; border-color: #dc2626 } .btn-rej:hover { background: #b91c1c }
.btn-pdf     { background: #7c3aed; color: #fff; border-color: #7c3aed } .btn-pdf:hover { background: #6d28d9 }
.btn-sm      { padding: 2px 8px; font-size: .69rem }

/* Footer */
.pdc-footer { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding: 12px 16px; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,.05) }
.pdc-footer > div { display: flex; align-items: center; gap: 8px; flex-wrap: wrap }
.spin-dot { width: 12px; height: 12px; border: 2px solid rgba(255,255,255,.4); border-top-color: #fff; border-radius: 50%; animation: spin .6s linear infinite }

/* ── Modals ─────────────────────────────────────────────────── */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 500; display: flex; align-items: center; justify-content: center; padding: 20px }
.modal         { background: #fff; border-radius: 14px; box-shadow: 0 20px 60px rgba(0,0,0,.25); display: flex; flex-direction: column; max-height: 90vh; overflow: hidden; width: 100% }
.modal-struct  { max-width: 580px }
.modal-ai      { max-width: 680px }
.modal-hd      { display: flex; align-items: center; gap: 12px; padding: 16px 20px; border-bottom: 1px solid #e2e8f0; flex-shrink: 0 }
.modal-av      { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0 }
.modal-title   { font-size: .88rem; font-weight: 700; color: #1a1a2e }
.modal-sub     { font-size: .67rem; color: #94a3b8 }
.modal-close   { width: 28px; height: 28px; border-radius: 7px; background: #f1f5f9; border: 1px solid #e2e8f0; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: .75rem; margin-left: auto; flex-shrink: 0 }
.modal-body    { padding: 20px; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 16px }
.modal-ft      { display: flex; align-items: center; justify-content: flex-end; gap: 8px; padding: 14px 20px; border-top: 1px solid #e2e8f0; flex-shrink: 0; flex-wrap: wrap }
.mfade-enter-active, .mfade-leave-active { transition: all .2s ease }
.mfade-enter-from, .mfade-leave-to { opacity: 0; transform: scale(.96) }

/* Structure modal */
.msec       { display: flex; flex-direction: column; gap: 8px }
.msec-title { font-size: .7rem; font-weight: 700; color: #475569; display: flex; align-items: center; gap: 6px; text-transform: uppercase; letter-spacing: .06em }
.level-grid { display: flex; flex-direction: column; gap: 6px }
.level-chk  { display: flex; align-items: center; gap: 8px; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 7px; cursor: pointer; font-size: .78rem; transition: all .12s }
.level-chk.active { border-color: var(--mc); background: color-mix(in srgb, var(--mc) 4%, white) }
.level-chk input { width: 14px; height: 14px; cursor: pointer; flex-shrink: 0 }
.num-grid   { display: flex; gap: 8px; flex-wrap: wrap }
.num-btn    { display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 8px 14px; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; cursor: pointer; font-family: inherit; transition: all .12s }
.num-btn.active { border-color: var(--mc); background: color-mix(in srgb, var(--mc) 6%, white) }
.num-btn code { font-size: .72rem; font-weight: 600 }
.num-btn span { font-size: .62rem; color: #64748b }
.ft-grid    { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px }
.ft-chk     { display: flex; align-items: center; gap: 6px; padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer; font-size: .74rem; transition: all .12s }
.ft-chk.active { border-color: var(--mc); background: color-mix(in srgb, var(--mc) 5%, white) }
.ft-chk input { width: 14px; height: 14px; cursor: pointer; flex-shrink: 0 }
.ft-chk i { font-size: .8rem; color: #64748b }

/* IA modal */
.ai-ctx      { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; display: flex; flex-direction: column; gap: 5px }
.ai-ctx-row  { display: flex; align-items: center; gap: 8px; font-size: .76rem }
.ai-lbl      { font-size: .64rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .04em; min-width: 60px }
.ai-field    { display: flex; flex-direction: column; gap: 4px }
.ai-ta       { width: 100%; border: 1px solid #d1d5db; border-radius: 7px; padding: 9px 11px; font-size: .79rem; color: #1a1a2e; font-family: inherit; resize: vertical; outline: none }
.ai-ta:focus { border-color: var(--mc) }
.ai-opts     { display: flex; gap: 10px; flex-wrap: wrap }
.ai-opt      { display: flex; flex-direction: column; gap: 3px; min-width: 130px }
.sel         { border: 1px solid #d1d5db; border-radius: 5px; padding: 5px 8px; font-size: .74rem; color: #1a1a2e; background: #fff; cursor: pointer; font-family: inherit; outline: none }
.opt         { color: #94a3b8; font-size: .68rem }
.ai-loading  { display: flex; align-items: center; gap: 12px; padding: 20px; justify-content: center; color: #64748b; font-size: .79rem }
.ai-spinner  { width: 22px; height: 22px; border: 2.5px solid #e2e8f0; border-top-color: #7c3aed; border-radius: 50%; animation: spin .7s linear infinite; flex-shrink: 0 }
.ai-results  { border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden }
.ai-results-hd { display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: .75rem; font-weight: 600; color: #1a1a2e }
.ai-preview  { max-height: 260px; overflow-y: auto }
.aip-h1  { display: flex; align-items: center; gap: 7px; padding: 7px 14px; background: #1e3a5f; color: #fff; font-size: .76rem; font-weight: 700 }
.aip-h1 i { color: #60a5fa }
.aip-h2  { display: flex; align-items: center; gap: 7px; padding: 6px 20px; background: #2B7FD4; color: #fff; font-size: .74rem; font-weight: 600 }
.aip-h2 i { color: #FFD700 }
.aip-h3  { display: flex; align-items: center; gap: 7px; padding: 5px 28px; background: #e3f2fd; color: #1e3a5f; font-size: .72rem; font-weight: 600 }
.aip-item  { display: flex; align-items: center; gap: 8px; padding: 5px 14px; border-bottom: .5px solid #f1f5f9; cursor: pointer; font-size: .76rem; color: #475569; transition: background .1s }
.aip-item.selected { background: #f0f6ff; color: #1a1a2e }
.aip-item:hover    { background: #f0f6ff }
.aip-item i { font-size: .78rem; color: #7c3aed; flex-shrink: 0 }
.aip-ref   { font-family: monospace; font-size: .68rem; font-weight: 600; color: #1565C0; flex-shrink: 0; min-width: 56px }
.aip-type  { margin-left: auto; font-size: .64rem; color: #94a3b8; background: #f1f5f9; padding: 1px 5px; border-radius: 3px }

/* Toast */
.toast      { position: fixed; bottom: 24px; right: 24px; z-index: 600; display: flex; align-items: center; gap: 10px; padding: 11px 18px; border-radius: 10px; font-size: .8rem; font-weight: 600; box-shadow: 0 4px 16px rgba(0,0,0,.18) }
.toast-success { background: #15803d; color: #fff }
.toast-error   { background: #dc2626; color: #fff }
.toast-up-enter-active, .toast-up-leave-active { transition: all .24s ease }
.toast-up-enter-from, .toast-up-leave-to { opacity: 0; transform: translateY(10px) }

@keyframes spin { to { transform: rotate(360deg) } }
.spin { animation: spin .6s linear infinite; display: inline-block }
</style>