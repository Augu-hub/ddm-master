<template>
  <VerticalLayoutAudit>
    <div class="rado-shell">

      <!-- ══ HEADER ══ -->
      <header class="rado-header">
        <div class="rado-hrow">
          <a :href="props.backUrl" class="rado-back"><i class="ti ti-arrow-left"></i></a>
          <div class="rado-hinfo">
            <div class="rado-chips">
              <code class="rado-code">{{ form.code || 'RADO-AUTO' }}</code>
              <span class="rado-chip" :class="`chip-${form.validation_status||'draft'}`">
                <i :class="vstIcon(form.validation_status||'draft')"></i>{{ vstLbl(form.validation_status||'draft') }}
              </span>
              <span class="rado-chip chip-type">RADO</span>
              <span v-if="props.auditorRole" class="rado-chip" :class="`chip-role-${props.auditorRole}`">{{ props.auditorRole }}</span>
              <!-- Indicateur sauvegarde auto -->
              <span v-if="autoSaveState==='saving'" class="rado-chip chip-autosave chip-saving">
                <span class="spin-s"></span> {{ form.id ? 'Sync serveur…' : 'Sauvegarde…' }}
              </span>
              <span v-else-if="autoSaveState==='saved'" class="rado-chip chip-autosave chip-saved">
                <i class="ti ti-check"></i> {{ form.id ? 'Synchronisé' : 'Brouillon local' }}
              </span>
              <span v-else-if="autoSaveState==='error'" class="rado-chip chip-autosave chip-err">
                <i class="ti ti-alert-triangle"></i> Erreur auto-save
              </span>
              <span v-else-if="hasDraft" class="rado-chip chip-autosave chip-draft-ind">
                <i class="ti ti-device-floppy"></i> Brouillon local
              </span>
            </div>
            <h1 class="rado-title">Rapport d'Orientation de Mission d'Audit</h1>
            <div class="rado-meta">
              <span v-if="db.mission?.code_mission"><i class="ti ti-clipboard"></i>{{ db.mission.code_mission }}</span>
              <span v-if="db.mission?.libelle"><i class="ti ti-file-description"></i>{{ db.mission.libelle }}</span>
              <span v-if="db.entites?.length"><i class="ti ti-building"></i>{{ db.entites.map((e:any)=>e.name).join(', ') }}</span>
              <span v-if="lastDraftSavedAt" class="autosave-hint"><i class="ti ti-clock"></i>Brouillon : {{ lastDraftSavedAt }}</span>
            </div>
          </div>
          <button class="btn btn-print btn-sm" @click="showPrintModal=true">
            <i class="ti ti-printer"></i> Imprimer
          </button>
        </div>

        <!-- Banners -->
        <div v-if="form.validation_status==='validated'" class="rado-banner banner-lock">
          <i class="ti ti-lock"></i> Rapport <strong>validé définitivement</strong> — lecture seule
        </div>
        <div v-else-if="form.validation_status==='in_review'" class="rado-banner banner-review">
          <i class="ti ti-clock"></i> Soumis pour validation<span v-if="canManage"> · DM/CM peut valider ou rejeter.</span>
        </div>
        <div v-else-if="form.validation_status==='draft'&&form.validation_note" class="rado-banner banner-reject">
          <i class="ti ti-circle-x"></i> Rejeté — <em>{{ form.validation_note }}</em>
        </div>

        <!-- Restauration brouillon -->
        <div v-if="hasDraft && !form.id && !isLocked" class="rado-banner banner-draft-restore">
          <i class="ti ti-refresh"></i>
          Un brouillon local est disponible ({{ lastDraftSavedAt }}).
          <button class="btn btn-xs btn-ghost ml-auto" @click="loadDraft"><i class="ti ti-download"></i> Restaurer</button>
          <button class="btn btn-xs btn-ghost" @click="clearDraft"><i class="ti ti-trash"></i> Ignorer</button>
        </div>
      </header>

      <!-- ══ ONGLETS ══ -->
      <div class="rado-tabs">
        <button :class="['rtab',{active:activeTab==='A'}]" @click="activeTab='A'">
          <i class="ti ti-briefcase"></i>Mission &amp; Risques
          <span v-if="(db.risques_retenus?.length??0)>0" class="tab-badge">{{ db.risques_retenus?.length }}</span>
        </button>
        <button :class="['rtab',{active:activeTab==='B'}]" @click="activeTab='B'">
          <i class="ti ti-target"></i>Objectifs, Champ &amp; Suivi
          <span v-if="axes_audit.length>0" class="tab-badge">{{ axes_audit.length }}</span>
        </button>
        <!-- Bouton modale équipe & docs -->
        <button class="rtab rtab-modal" @click="showEquipeModal=true">
          <i class="ti ti-users"></i>Équipe &amp; Documents
          <span v-if="equipe_audit.length+documents_requis.length>0" class="tab-badge">
            {{ equipe_audit.length + documents_requis.length }}
          </span>
        </button>
      </div>

      <!-- ══ BODY ══ -->
      <div class="rado-body">

        <!-- ─── ONGLET A : MISSION & RISQUES ─── -->
        <div v-show="activeTab==='A'" class="tab-content">
          <!-- En-tête RADO exact (calqué Excel) -->
          <div class="ro-entete">
            <div class="ro-top-bar">STRUCTURE / ORGANISATION</div>
            <div class="ro-logo-row">[Insérer le logo et nom de l'organisation]</div>
            <div class="ro-ident-bar">▌ IDENTIFICATION DE LA MISSION</div>
            <div class="ro-ident-fields">
              <div class="ro-ifield rf-sm">
                <span class="ro-flbl">RADO</span>
                <input class="ro-finp" :value="form.code||'RADO-AUTO'" readonly/>
              </div>
              <div class="ro-ifield rf-md">
                <span class="ro-flbl">Code Mission</span>
                <input class="ro-finp ro-finp-ro" :value="db.mission?.code_mission||'—'" readonly/>
              </div>
              <div class="ro-ifield rf-xl">
                <span class="ro-flbl">Intitulé de la Mission</span>
                <input class="ro-finp" v-model="form.titre" :disabled="isLocked" placeholder="Intitulé…" @input="scheduleDraft"/>
              </div>
              <div class="ro-ifield rf-md">
                <span class="ro-flbl">Exercice / Période</span>
                <input class="ro-finp" v-model="form.periode_auditee" :disabled="isLocked" placeholder="Ex : 2024" @input="scheduleDraft"/>
              </div>
            </div>
            <div class="ro-meta-row">
              <div class="ro-mfield">
                <span class="ro-mlbl">Fait par :</span>
                <input class="ro-minp" v-model="form.fait_par" :disabled="isLocked" @input="scheduleDraft"/>
              </div>
              <div class="ro-mfield ro-mfield-sm">
                <span class="ro-mlbl">Date :</span>
                <input type="date" class="ro-minp" v-model="form.date_rapport" :disabled="isLocked" @input="scheduleDraft"/>
              </div>
              <div class="ro-mfield">
                <span class="ro-mlbl">Revu par :</span>
                <input class="ro-minp" v-model="form.revue_par" :disabled="isLocked" @input="scheduleDraft"/>
              </div>
              <div class="ro-mfield">
                <span class="ro-mlbl">Approuvé par :</span>
                <input class="ro-minp" v-model="form.approuve_par" :disabled="isLocked" @input="scheduleDraft"/>
              </div>
              <div class="ro-mfield">
                <span class="ro-mlbl">Date :</span>
                <input type="date" class="ro-minp" v-model="form.date_approbation" :disabled="isLocked" @input="scheduleDraft"/>
              </div>
              <div class="ro-mfield ro-mfield-xl">
                <span class="ro-mlbl">Destinataires :</span>
                <input class="ro-minp" v-model="form.destinataires" :disabled="isLocked"
                       :placeholder="db.ordre_mission?.destinataire||'Destinataires…'" @input="scheduleDraft"/>
              </div>
            </div>
            <div class="ro-main-title">RAPPORT D'ORIENTATION DE MISSION D'AUDIT INTERNE</div>
          </div>

          <!-- Section 1 -->
          <div class="xls-section">
            <div class="xls-hdr">1. &nbsp;CONTEXTE ET JUSTIFICATION DE LA MISSION</div>
            <div class="xls-body">
              <div class="xls-row">
                <div class="xls-lbl">Contexte général de la mission</div>
                <div class="xls-val">
                  <div v-if="db.pdc?.contexte&&!isLocked" class="db-hint">
                    <i class="ti ti-database"></i><em>{{ db.pdc.contexte }}</em>
                    <button class="btn btn-xs btn-db ml-auto" @click="form.contexte=db.pdc.contexte;scheduleDraft()"><i class="ti ti-download"></i></button>
                  </div>
                  <textarea class="rinp rinp-ta" v-model="form.contexte" :disabled="isLocked" rows="2"
                            placeholder="Contexte général…" @input="scheduleDraft"></textarea>
                </div>
              </div>
              <div class="xls-row">
                <div class="xls-lbl">Référence au Plan d'Audit Annuel (PAA)</div>
                <div class="xls-val">
                  <input class="rinp" v-model="form.reference_paa" :disabled="isLocked" placeholder="Réf. PAA…" @input="scheduleDraft"/>
                </div>
              </div>
              <div class="xls-row">
                <div class="xls-lbl">Origine de la mission (routinier / demande spéciale / alerte)</div>
                <div class="xls-val">
                  <select class="rinp" v-model="form.origine_mission" :disabled="isLocked" @change="scheduleDraft">
                    <option value="">Sélectionner…</option>
                    <option value="routinier">Routinier</option>
                    <option value="demande_speciale">Demande spéciale</option>
                    <option value="alerte">Alerte / Signal</option>
                    <option value="autre">Autre</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Section 2 -->
          <div class="xls-section">
            <div class="xls-hdr">2. &nbsp;OBJECTIF GÉNÉRAL DE LA MISSION</div>
            <div class="xls-body xls-body-pad">
              <div v-if="db.mission?.objectif&&!isLocked" class="db-hint mb6">
                <i class="ti ti-database"></i><em>{{ db.mission.objectif }}</em>
                <button class="btn btn-xs btn-db ml-auto" @click="form.objectif_general=db.mission.objectif;scheduleDraft()">
                  <i class="ti ti-download"></i> Utiliser
                </button>
              </div>
              <textarea class="rinp rinp-ta" v-model="form.objectif_general" :disabled="isLocked" rows="3"
                        placeholder="Décrire l'objectif général de la mission…" @input="scheduleDraft"></textarea>
            </div>
          </div>

          <!-- Infos mission -->
          <div class="info-grid">
            <div class="info-card">
              <div class="info-card-hdr"><i class="ti ti-briefcase"></i> Mission</div>
              <div class="ir"><span class="ilbl">Code</span><span class="ival mono">{{ db.mission?.code_mission||'—' }}</span></div>
              <div class="ir"><span class="ilbl">Libellé</span><span class="ival fw">{{ db.mission?.libelle||'—' }}</span></div>
              <div class="ir"><span class="ilbl">N° FPM</span><span class="ival mono">{{ db.mission?.numero_fpm||'—' }}</span></div>
              <div class="ir"><span class="ilbl">Début</span><span class="ival">{{ db.mission?.date_debut||'—' }}</span></div>
              <div class="ir"><span class="ilbl">Fin</span><span class="ival">{{ db.mission?.date_fin||'—' }}</span></div>
              <div class="ir"><span class="ilbl">Lieux</span><span class="ival">{{ db.mission?.lieux||'—' }}</span></div>
            </div>
            <div class="info-card">
              <div class="info-card-hdr"><i class="ti ti-building"></i> Entités auditées</div>
              <div v-if="!db.entites?.length" class="empty-sm">Aucune entité</div>
              <div v-for="e in db.entites" :key="e.id" class="entite-row">
                <span class="entite-name">{{ e.name }}</span>
                <span class="entite-code">{{ e.code_base }}</span>
                <span v-if="e.date_debut" class="entite-dates">{{ e.date_debut }} → {{ e.date_fin }}</span>
              </div>
            </div>
            <div class="info-card">
              <div class="info-card-hdr"><i class="ti ti-file-certificate"></i> Ordre de Mission</div>
              <div v-if="!db.ordre_mission" class="empty-sm">Aucun OM lié</div>
              <template v-else>
                <div class="ir"><span class="ilbl">Réf.</span><span class="ival mono fw">{{ db.ordre_mission.reference_om }}</span></div>
                <div class="ir"><span class="ilbl">Intitulé</span><span class="ival">{{ db.ordre_mission.intitule }}</span></div>
                <div class="ir"><span class="ilbl">Budget</span><span class="ival">{{ formatBudget(db.ordre_mission.budget) }}</span></div>
              </template>
            </div>
          </div>

          <!-- Section 3 : Risques retenus -->
          <div class="xls-section">
            <div class="xls-hdr">3. &nbsp;RISQUES RETENUS (issus du Tableau d'Analyse des Risques – TAR)</div>
            <div v-if="!db.risques_retenus?.length" class="empty-block">
              <i class="ti ti-alert-triangle"></i>
              <p>Aucun risque retenu. Complétez l'AR et cochez les risques à retenir.</p>
            </div>
            <table v-else class="xls-tbl">
              <thead>
                <tr>
                  <th style="width:32px">N°</th>
                  <th style="width:160px">Faiblesses</th>
                  <th>Libellé du Risque</th>
                  <th style="width:150px">Processus / Sous-processus</th>
                  <th style="width:70px;text-align:center">Criticité</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="r in db.risques_retenus" :key="r.num"
                    :class="Number(r.score)>=16?'tr-crit':Number(r.score)>=9?'tr-elev':Number(r.score)>=4?'tr-mod':''">
                  <td class="tc fw">{{ r.num }}</td>
                  <td class="sm">{{ r.faiblesses }}</td>
                  <td>{{ r.libelle }}</td>
                  <td class="sm">{{ r.processus }}</td>
                  <td class="tc">
                    <span :class="['sc',Number(r.score)>=16?'sc-crit':Number(r.score)>=9?'sc-elev':Number(r.score)>=4?'sc-mod':'sc-faib']">{{ r.score }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Section 4 : Faiblesses processus -->
          <div class="xls-section">
            <div class="xls-hdr">4. &nbsp;FAIBLESSES CONSTATÉES SUR LES PROCESSUS</div>
            <table class="xls-tbl">
              <thead><tr><th style="width:32px">N°</th><th>Libellé de la Faiblesse</th><th style="width:150px">Fonction</th><th style="width:170px">Service / Entité concerné(e)</th></tr></thead>
              <tbody>
                <template v-for="dom in ['analyse_risques','analyse_processus']" :key="dom">
                  <tr v-for="f in (faiblessesByDomaine[dom]||[])" :key="`${dom}-${f.num}`">
                    <td class="tc fw">{{ f.num }}</td><td>{{ f.libelle }}</td><td class="sm">{{ f.fonctions }}</td><td class="sm">{{ f.processus_concerne }}</td>
                  </tr>
                </template>
                <tr v-if="!faibProcessusCount"><td colspan="4" class="empty-sm tc">Aucune faiblesse sur les processus.</td></tr>
              </tbody>
            </table>
          </div>

          <!-- Section 5 : Faiblesses procédures -->
          <div class="xls-section">
            <div class="xls-hdr">5. &nbsp;FAIBLESSES CONSTATÉES SUR LES PROCÉDURES</div>
            <table class="xls-tbl">
              <thead><tr><th style="width:32px">N°</th><th>Libellé de la Faiblesse</th><th style="width:150px">Fonction</th><th style="width:170px">Service / Entité concerné(e)</th></tr></thead>
              <tbody>
                <template v-for="dom in ['analyse_procedures','controle_conformite']" :key="dom">
                  <tr v-for="f in (faiblessesByDomaine[dom]||[])" :key="`${dom}-${f.num}`">
                    <td class="tc fw">{{ f.num }}</td><td>{{ f.libelle }}</td><td class="sm">{{ f.fonctions }}</td><td class="sm">{{ f.processus_concerne }}</td>
                  </tr>
                </template>
                <tr v-if="!faibProceduresCount"><td colspan="4" class="empty-sm tc">Aucune faiblesse sur les procédures.</td></tr>
              </tbody>
            </table>
          </div>

        </div><!-- /onglet A -->

        <!-- ─── ONGLET B : OBJECTIFS, CHAMP & SUIVI ─── -->
        <div v-show="activeTab==='B'" class="tab-content">

          <div class="xls-section">
            <div class="xls-hdr">6. &nbsp;OBJECTIFS D'AUDIT</div>
            <!-- ── Sélection compacte ── -->
            <div v-if="!isLocked && (db.objectifs_controle?.length??0)>0" class="obj-picker">
              <div class="obj-picker-top">
                <span class="obj-picker-lbl"><i class="ti ti-list-check"></i> Sélectionner des objectifs à regrouper</span>
                <div class="obj-picker-acts">
                  <button class="btn btn-xs btn-ghost" @click="selectAllObj">Tout</button>
                  <button class="btn btn-xs btn-ghost" @click="clearSelObj">Aucun</button>
                </div>
              </div>
              <div class="obj-chips-list">
                <label v-for="(o,i) in db.objectifs_controle" :key="i"
                       class="obj-chip"
                       :class="{active:objSelected.has(i), used:isObjUsed(i)}"
                       :title="isObjUsed(i)?'Déjà dans un axe':o.libelle">
                  <input type="checkbox" :value="i" v-model="objSelArr"
                         :disabled="isObjUsed(i)" class="sr-only"/>
                  <span class="obj-chip-src">{{ o.source }}</span>
                  <span class="obj-chip-lib">{{ o.libelle }}</span>
                  <i v-if="isObjUsed(i)" class="ti ti-check obj-chip-used-icon"></i>
                </label>
              </div>

              <!-- Barre d'action contextuelle quand sélection active -->
              <Transition name="action-bar-t">
                <div v-if="objSelArr.length>0" class="obj-action-bar">
                  <span class="obj-action-count">
                    <strong>{{ objSelArr.length }}</strong> objectif{{ objSelArr.length>1?'s':'' }} sélectionné{{ objSelArr.length>1?'s':'' }}
                  </span>

                  <!-- Preview suggestion IA inline -->
                  <div v-if="iaSuggestion" class="ia-preview">
                    <div class="ia-preview-hdr">
                      <i class="ti ti-sparkles"></i>
                      <span class="ia-preview-axe">{{ iaSuggestion.axe }}</span>
                      <span class="ia-preview-count">{{ iaSuggestion.objectifs?.length }} obj.</span>
                    </div>
                    <div class="ia-preview-actions">
                      <button class="btn btn-xs btn-ok" @click="confirmerIaAxe">
                        <i class="ti ti-check"></i> Ajouter cet axe
                      </button>
                      <button class="btn btn-xs btn-ghost" @click="iaSuggestion=null;lancerIA_Objectifs()">
                        <i class="ti ti-refresh"></i>
                      </button>
                      <button class="btn btn-xs btn-ghost" @click="iaSuggestion=null;clearSelObj()">
                        <i class="ti ti-x"></i>
                      </button>
                    </div>
                  </div>

                  <button v-else class="btn btn-xs btn-ai" :disabled="aiLoading" @click="lancerIA_Objectifs">
                    <span v-if="aiLoading" class="spin-s"></span>
                    <i v-else class="ti ti-sparkles"></i>
                    {{ aiLoading ? 'Analyse…' : 'Grouper avec l\'IA' }}
                  </button>
                </div>
              </Transition>

              <div v-if="aiError" class="ai-err ai-err-sm"><i class="ti ti-circle-x"></i>{{ aiError }}</div>
            </div>

            <table class="xls-tbl xls-obj-tbl">
              <thead>
                <tr>
                  <th style="width:180px">Rubrique / Regroupement</th>
                  <th>Objectif de Contrôle</th>
                  <th style="width:160px">Risque(s) / Faiblesse(s) liés</th>
                  <th style="width:120px">Critère(s) CRIPP / IIA</th>
                  <th v-if="!isLocked" style="width:30px"></th>
                </tr>
              </thead>
              <tbody>
                <template v-for="(axe, ai) in axes_audit" :key="axe._uid">
                  <tr class="axe-row">
                    <td class="axe-cell" :rowspan="(axe.objectifs||[]).length+1">
                      <div class="axe-cell-inner">
                        <input v-if="!isLocked" class="axe-inp" v-model="axe.axe" placeholder="Rubrique…" @input="scheduleDraft"/>
                        <span v-else class="axe-lbl">{{ axe.axe||'—' }}</span>
                        <div v-if="!isLocked" class="axe-acts">
                          <button class="ibtn ibtn-ok" @click="addObj(axe)"><i class="ti ti-plus"></i></button>
                          <button class="ibtn ibtn-del" @click="axes_audit.splice(ai,1);scheduleDraft()"><i class="ti ti-x"></i></button>
                        </div>
                      </div>
                    </td>
                    <td colspan="3" class="axe-criteres-cell">
                      <textarea v-if="!isLocked" class="rinp rinp-ta-sm" v-model="axe.criteres_evaluation"
                                rows="1" placeholder="Critères CRIPP / IIA pour cet axe…" @input="scheduleDraft"></textarea>
                      <span v-else class="crit-ro">{{ axe.criteres_evaluation||'' }}</span>
                    </td>
                    <td v-if="!isLocked"></td>
                  </tr>
                  <tr v-for="(obj, oi) in (axe.objectifs||[])" :key="oi" class="obj-row">
                    <td>
                      <textarea v-if="!isLocked" class="rinp rinp-ta-sm" v-model="obj.objectif"
                                rows="2" placeholder="Objectif de contrôle…" @input="scheduleDraft"></textarea>
                      <span v-else class="obj-ro">{{ obj.objectif||'—' }}</span>
                    </td>
                    <td>
                      <input v-if="!isLocked" class="rinp" v-model="obj.indicateurs"
                             placeholder="Risque(s) lié(s)…" @input="scheduleDraft"/>
                      <span v-else class="obj-ro sm">{{ obj.indicateurs }}</span>
                    </td>
                    <td class="sm">{{ axe.criteres_evaluation }}</td>
                    <td v-if="!isLocked">
                      <button class="ibtn ibtn-del" @click="axe.objectifs.splice(oi,1);scheduleDraft()"><i class="ti ti-x"></i></button>
                    </td>
                  </tr>
                </template>
                <tr v-if="!axes_audit.length">
                  <td :colspan="isLocked?4:5" class="empty-sm tc">Aucun objectif défini. Utilisez l'IA ou ajoutez manuellement.</td>
                </tr>
              </tbody>
            </table>
            <button v-if="!isLocked" class="btn btn-ghost btn-xs mt6" @click="addAxe"><i class="ti ti-plus"></i> Ajouter un axe</button>
          </div>

          <div class="xls-section">
            <div class="xls-hdr">7. &nbsp;CHAMP D'ACTION (ÉTENDUE DE L'AUDIT)</div>
            <div v-if="!isLocked" class="ai-bar">
              <span><i class="ti ti-sparkles"></i> L'IA peut suggérer un champ d'action basé sur la mission.</span>
              <button class="btn btn-ai btn-xs" :disabled="aiLoading" @click="lancerIA_Champ">
                <span v-if="aiLoading" class="spin-s"></span><i v-else class="ti ti-sparkles"></i> Suggérer
              </button>
            </div>
            <table class="xls-tbl xls-champ-tbl">
              <thead>
                <tr>
                  <th style="width:130px">Rubrique</th>
                  <th>Zone de saisie</th>
                  <th style="width:260px">Exemple / Guide</th>
                  <th v-if="!isLocked" style="width:30px"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item,i) in perimetre" :key="i">
                  <td class="xls-champ-lbl">
                    <input v-if="!isLocked&&i>=5" class="rinp" v-model="item.titre" placeholder="Rubrique…" @input="scheduleDraft"/>
                    <span v-else class="fw">{{ item.titre }}</span>
                  </td>
                  <td>
                    <textarea v-if="!isLocked" class="rinp rinp-ta-sm" v-model="item.contenu"
                              rows="2" :placeholder="item.guide||item.titre+'…'" @input="scheduleDraft"></textarea>
                    <p v-else class="ps-ro">{{ item.contenu||'—' }}</p>
                  </td>
                  <td class="xls-guide sm">
                    <div v-if="item.titre==='Géographique'&&db.mission?.lieux" class="db-hint-inline">
                      <i class="ti ti-database"></i>{{ db.mission.lieux }}
                      <button v-if="!isLocked" class="btn btn-xs btn-db" @click="item.contenu=db.mission.lieux;scheduleDraft()">Utiliser</button>
                    </div>
                    <span v-else class="guide-txt">{{ item.guide }}</span>
                  </td>
                  <td v-if="!isLocked">
                    <button v-if="i>=5" class="ibtn ibtn-del" @click="perimetre.splice(i,1);scheduleDraft()"><i class="ti ti-x"></i></button>
                  </td>
                </tr>
              </tbody>
            </table>
            <button v-if="!isLocked" class="btn btn-ghost btn-xs mt6" @click="addPerimetre"><i class="ti ti-plus"></i> Ajouter une rubrique</button>
          </div>

          <!-- Méthodologie -->
          <div class="xls-section">
            <div class="xls-hdr">8. &nbsp;MÉTHODOLOGIE ET APPROCHE D'AUDIT</div>
            <div class="xls-body xls-body-pad">
              <textarea class="rinp rinp-ta" v-model="form.methodologie" :disabled="isLocked" rows="4"
                        placeholder="Décrire la méthodologie retenue (entretiens, tests, revue documentaire, etc.)…" @input="scheduleDraft"></textarea>
            </div>
          </div>

          <!-- Limites -->
          <div class="xls-section">
            <div class="xls-hdr">12. &nbsp;LIMITES ET RÉSERVES DE L'AUDIT</div>
            <div class="xls-body xls-body-pad">
              <div v-if="db.ordre_mission?.limite" class="db-hint mb6">
                <i class="ti ti-file-certificate"></i>OM : <em>{{ db.ordre_mission.limite }}</em>
                <button v-if="!isLocked" class="btn btn-xs btn-db ml-auto"
                        @click="form.limites=db.ordre_mission.limite;scheduleDraft()"><i class="ti ti-download"></i></button>
              </div>
              <p class="xls-guide-txt">Toutes restrictions, contraintes ou limites connues (ex. : périmètre exclu, contraintes d'accès…)</p>
              <textarea class="rinp rinp-ta" v-model="form.limites" :disabled="isLocked" rows="4"
                        placeholder="Limites et réserves…" @input="scheduleDraft"></textarea>
            </div>
          </div>

          <div class="note-bas">
            <i class="ti ti-info-circle"></i>
            N.B. : Un rapport d'orientation doit être établi par intitulé de mission. Ce document est CONFIDENTIEL.
          </div>
        </div>

      </div><!-- /rado-body -->

      <!-- ══ FOOTER ══ -->
      <footer class="rado-footer">
        <div class="footer-left">
          <button v-if="!isLocked" type="button" class="btn btn-ghost btn-sm" :disabled="processing" @click="annuler">
            <i class="ti ti-x"></i> Annuler
          </button>
          <!-- Niveau 1 : Brouillon local -->
          <button v-if="!isLocked" type="button" class="btn btn-draft btn-sm" :disabled="processing" @click="saveDraft">
            <i class="ti ti-device-floppy"></i> Brouillon
          </button>
          <!-- Niveau 2 : Enregistrement serveur -->
          <button v-if="!isLocked" type="button" class="btn btn-save btn-sm" :disabled="processing" @click="submit">
            <span v-if="processing" class="spin-s"></span>
            <i v-else class="ti ti-circle-check"></i>
            {{ form.id?'Mettre à jour':'Enregistrer' }}
          </button>
        </div>
        <div class="footer-mid">
          <div class="save-levels" v-if="!isLocked">
            <div class="save-level" :class="{active:hasDraft}">
              <span class="level-dot"></span>
              <span class="level-lbl">Brouillon local</span>
            </div>
            <div class="save-level-sep">→</div>
            <div class="save-level" :class="{active:!!form.id}">
              <span class="level-dot"></span>
              <span class="level-lbl">Enregistré serveur</span>
            </div>
            <div class="save-level-sep">→</div>
            <div class="save-level" :class="{active:form.validation_status==='in_review'||form.validation_status==='validated'}">
              <span class="level-dot"></span>
              <span class="level-lbl">Soumis</span>
            </div>
            <div class="save-level-sep">→</div>
            <div class="save-level" :class="{active:form.validation_status==='validated'}">
              <span class="level-dot"></span>
              <span class="level-lbl">Validé</span>
            </div>
          </div>
          <span v-else-if="form.id" class="saved-code"><i class="ti ti-check"></i>{{ form.code }}</span>
        </div>
        <div class="footer-right">
          <button class="btn btn-print btn-sm" @click="showPrintModal=true"><i class="ti ti-printer"></i> Imprimer</button>
          <button v-if="form.id&&form.validation_status==='draft'" type="button" class="btn btn-sub btn-sm"
                  :disabled="processing" @click="soumettre"><i class="ti ti-send"></i> Soumettre</button>
          <template v-if="canManage&&form.validation_status==='in_review'">
            <button type="button" class="btn btn-ok btn-sm" :disabled="processing" @click="valider('validate')"><i class="ti ti-circle-check"></i> Valider</button>
            <button type="button" class="btn btn-rej btn-sm" :disabled="processing" @click="promptReject"><i class="ti ti-circle-x"></i> Rejeter</button>
          </template>
        </div>
      </footer>
    </div><!-- /rado-shell -->

    <!-- ════════════════════════
         MODALE ÉQUIPE & DOCUMENTS
         ════════════════════════ -->
    <Teleport to="body">
      <Transition name="modal-t">
        <div v-if="showEquipeModal" class="modal-overlay" @click.self="showEquipeModal=false">
          <div class="modal-box modal-box-lg">
            <div class="modal-hdr">
              <i class="ti ti-users"></i> Équipe d'audit &amp; Documents requis
              <button class="modal-close" @click="showEquipeModal=false"><i class="ti ti-x"></i></button>
            </div>
            <div class="modal-body modal-body-scroll">

              <!-- Équipe -->
              <div class="modal-section-hdr">
                <i class="ti ti-users"></i> Équipe d'audit et planning
                <div class="modal-section-acts">
                  <span class="pool-info">{{ equipe_audit.length }} membre(s)</span>
                  <button v-if="!isLocked && db.equipe?.length && !equipe_audit.length"
                          class="btn btn-xs btn-db" @click="importEquipe">
                    <i class="ti ti-download"></i> Importer BD
                  </button>
                </div>
              </div>
              <table class="xls-tbl">
                <thead>
                  <tr>
                    <th>Nom &amp; Prénom</th>
                    <th style="width:170px">Rôle / Fonction</th>
                    <th style="width:60px">J/H</th>
                    <th style="width:180px">Observations</th>
                    <th v-if="!isLocked" style="width:30px"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(eq,i) in equipe_audit" :key="i">
                    <td>
                      <input v-if="!isLocked" class="c-inp" v-model="eq.nom" @input="scheduleDraft"/>
                      <span v-else class="c-ro">{{ eq.nom }}</span>
                    </td>
                    <td>
                      <select v-if="!isLocked" class="c-inp" v-model="eq.role" @change="scheduleDraft">
                        <option>Directeur de Mission</option>
                        <option>Chef de mission</option>
                        <option>Auditeur senior</option>
                        <option>Auditeur junior</option>
                        <option>Expert technique</option>
                      </select>
                      <span v-else class="c-ro">{{ eq.role }}</span>
                    </td>
                    <td class="tc">
                      <input v-if="!isLocked" type="number" class="c-inp" v-model="eq.jours_homme" min="0" @input="scheduleDraft"/>
                      <span v-else class="c-ro">{{ eq.jours_homme }}</span>
                    </td>
                    <td>
                      <input v-if="!isLocked" class="c-inp" v-model="eq.observations" @input="scheduleDraft"/>
                      <span v-else class="c-ro">{{ eq.observations }}</span>
                    </td>
                    <td v-if="!isLocked">
                      <button class="ibtn ibtn-del" @click="equipe_audit.splice(i,1);scheduleDraft()"><i class="ti ti-x"></i></button>
                    </td>
                  </tr>
                  <tr v-if="!equipe_audit.length">
                    <td :colspan="isLocked?4:5" class="empty-sm tc">Aucun membre.</td>
                  </tr>
                </tbody>
              </table>
              <button v-if="!isLocked" class="btn btn-ghost btn-xs mt6"
                      @click="equipe_audit.push({nom:'',role:'Auditeur senior',jours_homme:'',observations:''});scheduleDraft()">
                <i class="ti ti-plus"></i> Ajouter un membre
              </button>

              <!-- Documents -->
              <div class="modal-section-hdr mt12">
                <i class="ti ti-files"></i> Documents et informations requis
                <div class="modal-section-acts">
                  <span class="pool-info">{{ documents_requis.length }} document(s)</span>
                  <button v-if="!isLocked && missingDocsDefaults.length"
                          class="btn btn-xs btn-db" @click="addDocsStandards">
                    <i class="ti ti-download"></i> Standards
                  </button>
                </div>
              </div>
              <table class="xls-tbl">
                <thead>
                  <tr>
                    <th>Document / Information demandé(e)</th>
                    <th style="width:140px">Source / Responsable</th>
                    <th style="width:110px">Délai de transmission</th>
                    <th style="width:90px">Statut</th>
                    <th v-if="!isLocked" style="width:30px"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(d,i) in documents_requis" :key="i">
                    <td>
                      <input v-if="!isLocked" class="c-inp" v-model="d.document" placeholder="Nom du document…" @input="scheduleDraft"/>
                      <span v-else class="c-ro">{{ d.document }}</span>
                    </td>
                    <td>
                      <input v-if="!isLocked" class="c-inp" v-model="d.source" @input="scheduleDraft"/>
                      <span v-else class="c-ro">{{ d.source }}</span>
                    </td>
                    <td>
                      <input v-if="!isLocked" class="c-inp" v-model="d.delai" @input="scheduleDraft"/>
                      <span v-else class="c-ro">{{ d.delai }}</span>
                    </td>
                    <td>
                      <select v-if="!isLocked" class="c-inp" v-model="d.statut" @change="scheduleDraft">
                        <option value="">—</option>
                        <option value="reçu">Reçu</option>
                        <option value="en_attente">En attente</option>
                        <option value="partiel">Partiel</option>
                      </select>
                      <span v-else class="c-ro">{{ d.statut }}</span>
                    </td>
                    <td v-if="!isLocked">
                      <button class="ibtn ibtn-del" @click="documents_requis.splice(i,1);scheduleDraft()"><i class="ti ti-x"></i></button>
                    </td>
                  </tr>
                  <tr v-if="!documents_requis.length">
                    <td :colspan="isLocked?4:5" class="empty-sm tc">Aucun document.</td>
                  </tr>
                </tbody>
              </table>
              <button v-if="!isLocked" class="btn btn-ghost btn-xs mt6"
                      @click="documents_requis.push({document:'',source:'',delai:'',statut:''});scheduleDraft()">
                <i class="ti ti-plus"></i> Ajouter un document
              </button>

            </div>
            <div class="modal-footer">
              <span class="modal-footer-info" v-if="hasDraft"><i class="ti ti-device-floppy"></i> Modifications sauvegardées en brouillon local</span>
              <button class="btn btn-ghost btn-sm" @click="showEquipeModal=false">Fermer</button>
              <button v-if="!isLocked" class="btn btn-save btn-sm" :disabled="processing" @click="showEquipeModal=false;submit()">
                <i class="ti ti-circle-check"></i> Enregistrer
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ════════════════════════
         MODALE IMPRESSION
         ════════════════════════ -->
    <Teleport to="body">
      <Transition name="modal-t">
        <div v-if="showPrintModal" class="modal-overlay" @click.self="showPrintModal=false">
          <div class="modal-box">
            <div class="modal-hdr">
              <i class="ti ti-printer"></i> Imprimer RADO — par entité
              <button class="modal-close" @click="showPrintModal=false"><i class="ti ti-x"></i></button>
            </div>
            <div class="modal-body">
              <p class="modal-note">Le rapport complet (toutes les sections) est généré pour chaque entité sélectionnée.</p>
              <div class="rfg mb10">
                <span class="rlbl">Entité à imprimer</span>
                <select class="rinp" v-model="printEntiteId">
                  <option value="all">Toutes les entités (1 document par entité)</option>
                  <option v-for="e in (db.entites||[])" :key="e.id" :value="e.id">{{ e.name }} — {{ e.code_base }}</option>
                  <option v-if="!db.entites?.length" value="generic">Document générique (sans entité)</option>
                </select>
              </div>
              <div class="rfg mb10">
                <span class="rlbl">Organisation (en-tête)</span>
                <input class="rinp" v-model="printOrg" placeholder="Nom de l'organisation…"/>
              </div>
              <div class="rfg mb4">
                <span class="rlbl">Sections à inclure</span>
                <label class="chk-row"><input type="checkbox" v-model="printOpts.equipe"/> Section 9 — Équipe d'audit</label>
                <label class="chk-row"><input type="checkbox" v-model="printOpts.documents"/> Section 11 — Liste des documents</label>
                <label class="chk-row"><input type="checkbox" v-model="printOpts.signatures"/> Section 13 — Approbation et signatures</label>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-ghost btn-sm" @click="showPrintModal=false">Annuler</button>
              <button class="btn btn-print btn-sm" @click="lancerImpression"><i class="ti ti-printer"></i> Lancer l'impression</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ════════════════════════
         ZONE IMPRESSION
         ════════════════════════ -->
    <Teleport to="body">
      <div v-if="printContent.length" id="rado-print-zone">
        <div v-for="(doc,di) in printContent" :key="di" class="rp-doc">
          <div class="rp-org-hdr">{{ printOrg || 'STRUCTURE / ORGANISATION' }}</div>
          <div class="rp-org-sub">[Insérer le logo et nom de l'organisation]</div>
          <div class="rp-ident-bar">▌ IDENTIFICATION DE LA MISSION</div>
          <table class="rp-ident-tbl">
            <tr>
              <td class="rp-id-cell rp-id-rado">RADO</td>
              <td class="rp-id-cell">
                <span class="rp-id-lbl">Code Mission</span>
                <span class="rp-id-val">{{ db.mission?.code_mission||'—' }}</span>
              </td>
              <td class="rp-id-cell rp-id-xl">
                <span class="rp-id-lbl">Intitulé de la Mission</span>
                <span class="rp-id-val fw">{{ form.titre||db.mission?.libelle||'—' }}</span>
              </td>
              <td class="rp-id-cell">
                <span class="rp-id-lbl">Exercice / Période</span>
                <span class="rp-id-val">{{ form.periode_auditee||'—' }}</span>
              </td>
            </tr>
          </table>
          <table class="rp-meta-tbl">
            <tr>
              <td><b>Fait par :</b> {{ form.fait_par||'……………………' }}</td>
              <td><b>Date :</b> {{ form.date_rapport||'…………………' }}</td>
              <td><b>Revu par :</b> {{ form.revue_par||'……………………' }}</td>
            </tr>
            <tr>
              <td><b>Approuvé par :</b> {{ form.approuve_par||'……………………' }}</td>
              <td><b>Date :</b> {{ form.date_approbation||'…………………' }}</td>
              <td><b>Destinataires :</b> {{ form.destinataires||db.ordre_mission?.destinataire||'……………………' }}</td>
            </tr>
          </table>
          <div class="rp-main-title">RAPPORT D'ORIENTATION DE MISSION D'AUDIT INTERNE</div>
          <div class="rp-entite-banner">
            Entité auditée : <strong>{{ doc.entite.name }}</strong>
            <span v-if="doc.entite.code_base"> ({{ doc.entite.code_base }})</span>
            <span v-if="doc.entite.date_debut"> &nbsp;·&nbsp; {{ doc.entite.date_debut }} → {{ doc.entite.date_fin }}</span>
          </div>
          <div class="rp-sec-hdr">1. &nbsp;CONTEXTE ET JUSTIFICATION DE LA MISSION</div>
          <table class="rp-s1-tbl">
            <tr><td class="rp-s1-lbl">Contexte général de la mission</td><td>{{ form.contexte||'—' }}</td></tr>
            <tr><td class="rp-s1-lbl">Référence au Plan d'Audit Annuel (PAA)</td><td>{{ form.reference_paa||'—' }}</td></tr>
            <tr><td class="rp-s1-lbl">Origine de la mission</td><td>{{ origineLabel(form.origine_mission) }}</td></tr>
          </table>
          <div class="rp-sec-hdr">2. &nbsp;OBJECTIF GÉNÉRAL DE LA MISSION</div>
          <div class="rp-s2-body">{{ form.objectif_general||'—' }}</div>
          <div class="rp-sec-hdr">3. &nbsp;RISQUES RETENUS</div>
          <table class="rp-tbl">
            <thead><tr class="rp-th-orange"><th style="width:22pt">N°</th><th style="width:110pt">Faiblesses</th><th>Libellé du Risque</th><th style="width:110pt">Processus</th><th style="width:48pt">Criticité</th></tr></thead>
            <tbody>
              <tr v-for="r in (doc.risques||[])" :key="r.num" class="rp-tr-data">
                <td class="tc">{{ r.num }}</td><td>{{ r.faiblesses }}</td><td>{{ r.libelle }}</td><td>{{ r.processus }}</td>
                <td class="tc"><span :class="['rp-sc',Number(r.score)>=16?'rp-sc-crit':Number(r.score)>=9?'rp-sc-elev':'rp-sc-mod']">{{ r.score }}</span></td>
              </tr>
              <tr v-for="i in Math.max(0,5-(doc.risques||[]).length)" :key="`e-r-${i}`" class="rp-tr-empty rp-tr-data">
                <td>{{ (doc.risques||[]).length+i }}</td><td></td><td></td><td></td><td></td>
              </tr>
            </tbody>
          </table>
          <div class="rp-sec-hdr">4. &nbsp;FAIBLESSES CONSTATÉES SUR LES PROCESSUS</div>
          <table class="rp-tbl">
            <thead><tr class="rp-th-orange"><th style="width:22pt">N°</th><th>Libellé de la Faiblesse</th><th style="width:110pt">Fonction</th><th style="width:130pt">Service / Entité concerné(e)</th></tr></thead>
            <tbody>
              <tr v-for="f in (doc.faibProcessus||[])" :key="f.num" class="rp-tr-data">
                <td class="tc">{{ f.num }}</td><td>{{ f.libelle }}</td><td>{{ f.fonctions }}</td><td>{{ f.processus_concerne }}</td>
              </tr>
              <tr v-for="i in Math.max(0,4-(doc.faibProcessus||[]).length)" :key="`e-fp-${i}`" class="rp-tr-empty rp-tr-data">
                <td>{{ (doc.faibProcessus||[]).length+i }}</td><td></td><td></td><td></td>
              </tr>
            </tbody>
          </table>
          <div class="rp-sec-hdr">5. &nbsp;FAIBLESSES CONSTATÉES SUR LES PROCÉDURES</div>
          <table class="rp-tbl">
            <thead><tr class="rp-th-orange"><th style="width:22pt">N°</th><th>Libellé de la Faiblesse</th><th style="width:110pt">Fonction</th><th style="width:130pt">Service / Entité concerné(e)</th></tr></thead>
            <tbody>
              <tr v-for="f in (doc.faibProcedures||[])" :key="f.num" class="rp-tr-data">
                <td class="tc">{{ f.num }}</td><td>{{ f.libelle }}</td><td>{{ f.fonctions }}</td><td>{{ f.processus_concerne }}</td>
              </tr>
              <tr v-for="i in Math.max(0,4-(doc.faibProcedures||[]).length)" :key="`e-fpr-${i}`" class="rp-tr-empty rp-tr-data">
                <td>{{ (doc.faibProcedures||[]).length+i }}</td><td></td><td></td><td></td>
              </tr>
            </tbody>
          </table>
          <div class="rp-sec-hdr">6. &nbsp;OBJECTIFS D'AUDIT</div>
          <table class="rp-tbl">
            <thead><tr class="rp-th-orange"><th style="width:140pt">Rubrique / Regroupement</th><th>Objectif de Contrôle</th><th style="width:130pt">Risque(s) / Faiblesse(s) liés</th><th style="width:100pt">Critère(s) CRIPP / IIA</th></tr></thead>
            <tbody>
              <template v-for="axe in axes_audit" :key="axe._uid">
                <tr v-if="!(axe.objectifs||[]).length" class="rp-tr-data">
                  <td class="rp-axe-td">{{ axe.axe }}</td><td>—</td><td>—</td><td>{{ axe.criteres_evaluation }}</td>
                </tr>
                <template v-else>
                  <tr v-for="(obj,oi) in axe.objectifs" :key="oi" class="rp-tr-data">
                    <td v-if="oi===0" :rowspan="axe.objectifs.length" class="rp-axe-td">{{ axe.axe }}</td>
                    <td>{{ obj.objectif }}</td><td>{{ obj.indicateurs }}</td><td>{{ axe.criteres_evaluation }}</td>
                  </tr>
                </template>
              </template>
              <tr v-if="!axes_audit.length" class="rp-tr-empty rp-tr-data"><td colspan="4">—</td></tr>
            </tbody>
          </table>
          <div class="rp-sec-hdr">7. &nbsp;CHAMP D'ACTION (ÉTENDUE DE L'AUDIT)</div>
          <table class="rp-tbl rp-champ-tbl">
            <tbody>
              <tr v-for="item in perimetre" :key="item.titre" class="rp-tr-data">
                <td class="rp-champ-lbl">{{ item.titre }}</td>
                <td class="rp-champ-saisie">{{ item.contenu||'(zone de saisie)' }}</td>
              </tr>
            </tbody>
          </table>
          <template v-if="printOpts.equipe">
            <div class="rp-sec-hdr">9. &nbsp;ÉQUIPE D'AUDIT ET PLANNING DE MISSION</div>
            <table class="rp-tbl">
              <thead><tr class="rp-th-orange"><th>Nom &amp; Prénom</th><th style="width:120pt">Rôle / Fonction</th><th style="width:55pt">J/H prévus</th><th>Observations</th></tr></thead>
              <tbody>
                <tr v-for="eq in equipe_audit" :key="eq.nom" class="rp-tr-data">
                  <td>{{ eq.nom }}</td><td>{{ eq.role }}</td><td class="tc">{{ eq.jours_homme }}</td><td>{{ eq.observations }}</td>
                </tr>
                <tr v-for="i in Math.max(0,4-equipe_audit.length)" :key="`e-eq-${i}`" class="rp-tr-empty rp-tr-data">
                  <td></td><td>{{ ['Chef de mission','Auditeur senior','Auditeur junior','Expert technique'][equipe_audit.length+i-1]||'' }}</td><td></td><td></td>
                </tr>
              </tbody>
            </table>
          </template>
          <template v-if="printOpts.documents">
            <div class="rp-sec-hdr">11. &nbsp;LISTE DES DOCUMENTS ET INFORMATIONS REQUIS</div>
            <table class="rp-tbl">
              <thead><tr class="rp-th-orange"><th>Document / Information demandé(e)</th><th style="width:130pt">Source / Responsable</th><th style="width:95pt">Délai de transmission</th><th style="width:65pt">Statut</th></tr></thead>
              <tbody>
                <tr v-for="d in documents_requis" :key="d.document" class="rp-tr-data">
                  <td>{{ d.document }}</td><td>{{ d.source }}</td><td>{{ d.delai }}</td><td>{{ d.statut }}</td>
                </tr>
                <tr v-for="def in missingDocsDefaults" :key="def" class="rp-tr-data rp-tr-def">
                  <td>{{ def }}</td><td></td><td></td><td></td>
                </tr>
              </tbody>
            </table>
          </template>
          <div v-if="di<printContent.length-1" class="rp-page-break"></div>
        </div>
      </div>
    </Teleport>

    <!-- Toast -->
    <Teleport to="body">
      <Transition name="toast-t">
        <div v-if="toast.show" class="toast" :class="`toast--${toast.type}`">
          <i :class="toast.type==='success'?'ti ti-circle-check':'ti ti-circle-x'"></i>{{ toast.msg }}
        </div>
      </Transition>
    </Teleport>
  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, computed, reactive, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'

const props = withDefaults(defineProps<{
  mission?: any; assignment?: any; auditorRole?: string; missionId?: number; assignmentId?: number
  form?: any; radoList?: any[]; currentAuditor?: any; phaseAuditeurs?: any[]
  donneesDB?: {
    mission?: any; entites?: any[]; ordre_mission?: any
    risques_retenus?: any[]; faiblesses?: any
    objectifs_controle?: any[]; equipe?: any[]; pdc?: any
  }
  backUrl?: string; formUrl?: string
  urlStore?: string; urlUpdate?: string; urlSoumettre?: string; urlValider?: string
  urlAiSuggest?: string; urlIndex?: string
}>(), {
  radoList: () => [],
  phaseAuditeurs: () => [],
  donneesDB: () => ({ mission: null, entites: [], ordre_mission: null, risques_retenus: [], faiblesses: {}, objectifs_controle: [], equipe: [], pdc: null }),
})

// ─── Constantes ──────────────────────────────────────────────
const DOCS_STANDARDS = [
  'Manuel de procédures', 'Plan comptable / Politiques comptables',
  "Rapports d'audits antérieurs", 'Tableaux de bord / reporting existant',
  'Contrats, accords et délégations de pouvoir', 'Organigramme et fiches de poste',
]
const PERIMETRE_DEFAULTS = [
  { titre: 'Général',        contenu: '', guide: "Décrire la portée générale des vérifications (ex. : tous les crédits octroyés sur la période)" },
  { titre: 'Fonctionnel',    contenu: '', guide: "Identifier les directions, services et fonctions concernés" },
  { titre: 'Géographique',   contenu: '', guide: "Préciser les agences, sites ou régions couverts" },
  { titre: 'Temporel',       contenu: '', guide: "Définir la période d'audit (date de début – date de fin)" },
  { titre: 'Échantillonnage', contenu: '', guide: "Indiquer la méthode d'échantillonnage et la taille de l'échantillon" },
]

const db = props.donneesDB!
let _uid = 0
const uid = () => String(++_uid)

function safeArr(v: any): any[] {
  if (Array.isArray(v)) return [...v]
  if (!v) return []
  try { const d = JSON.parse(v); return Array.isArray(d) ? d : [] } catch { return [] }
}
function csrf(): string {
  return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? ''
}

// ─── State réactif principal ──────────────────────────────────
const form = reactive<any>({
  id: null, code: '', validation_status: 'draft', validation_note: '',
  titre: '', date_rapport: '', periode_auditee: '',
  objectif_general: '', methodologie: '', limites: '',
  fait_par: '', revue_par: '', approuve_par: '', date_approbation: '', destinataires: '',
  contexte: '', reference_paa: '', origine_mission: '',
  ...(props.form ?? {}),
})

const axes_audit = reactive<any[]>(
  safeArr(props.form?.axes_audit).map((a: any) => ({
    ...a, _uid: uid(),
    objectifs: a.objectifs ?? a.objectifs_specifiques ?? [],
  }))
)
const equipe_audit     = reactive<any[]>(safeArr(props.form?.equipe_audit))
const documents_requis = reactive<any[]>(safeArr(props.form?.documents_requis))
const perimetre        = reactive<any[]>(
  safeArr(props.form?.perimetre).length
    ? safeArr(props.form?.perimetre)
    : PERIMETRE_DEFAULTS.map(p => ({ ...p }))
)

// ─── Sauvegarde automatique (2 niveaux) ──────────────────────
// Niveau 1 : localStorage  — debounce 1.5s  (toujours actif)
// Niveau 2 : serveur       — debounce 4s    (uniquement si form.id existe)
const draftKey = computed(() => `rado_draft_${props.assignmentId ?? 'new'}`)

const hasDraft         = ref(false)
const lastDraftSavedAt = ref<string>('')
const autoSaveState    = ref<'idle' | 'saving' | 'saved' | 'error'>('idle')

let draftTimer:      ReturnType<typeof setTimeout> | null = null
let serverTimer:     ReturnType<typeof setTimeout> | null = null
let autoSaveClearTimer: ReturnType<typeof setTimeout> | null = null

// Appelé à chaque @input / @change → déclenche les deux timers
function scheduleDraft(): void {
  if (isLocked.value) return

  // Niveau 1 — localStorage 1.5s
  if (draftTimer) clearTimeout(draftTimer)
  draftTimer = setTimeout(() => { saveDraft() }, 1500)

  // Niveau 2 — serveur 4s (seulement si déjà enregistré)
  if (form.id) {
    if (serverTimer) clearTimeout(serverTimer)
    serverTimer = setTimeout(() => { autoSaveServer() }, 4000)
  }
}

// Auto-save silencieux vers le serveur
async function autoSaveServer(): Promise<void> {
  if (!form.id || isLocked.value) return
  autoSaveState.value = 'saving'
  try {
    const url = props.urlUpdate || `${props.formUrl}/${form.id}`
    const res = await fetch(url, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify(buildPayload()),
    })
    const d = await res.json()
    if (d.success || res.ok) {
      autoSaveState.value = 'saved'
      if (d.form) Object.assign(form, { validation_status: d.form.validation_status })
      // Effacer le brouillon local : le serveur est à jour
      clearDraft()
    } else {
      autoSaveState.value = 'error'
    }
  } catch {
    autoSaveState.value = 'error'
  }
  if (autoSaveClearTimer) clearTimeout(autoSaveClearTimer)
  autoSaveClearTimer = setTimeout(() => { autoSaveState.value = 'idle' }, 3000)
}

function buildSnapshot() {
  return {
    form: { ...form },
    axes_audit: JSON.parse(JSON.stringify(axes_audit)),
    equipe_audit: JSON.parse(JSON.stringify(equipe_audit)),
    documents_requis: JSON.parse(JSON.stringify(documents_requis)),
    perimetre: JSON.parse(JSON.stringify(perimetre)),
    savedAt: new Date().toISOString(),
  }
}

// Sauvegarde locale (niveau 1)
function saveDraft(): void {
  if (isLocked.value) return
  // Si form.id existe, l'auto-save serveur gère tout → pas besoin de localStorage
  if (form.id) return
  autoSaveState.value = 'saving'
  try {
    const snapshot = buildSnapshot()
    localStorage.setItem(draftKey.value, JSON.stringify(snapshot))
    hasDraft.value = true
    lastDraftSavedAt.value = formatTime(snapshot.savedAt)
    autoSaveState.value = 'saved'
  } catch {
    autoSaveState.value = 'error'
  }
  if (autoSaveClearTimer) clearTimeout(autoSaveClearTimer)
  autoSaveClearTimer = setTimeout(() => { autoSaveState.value = 'idle' }, 3000)
}

function loadDraft(): void {
  const raw = localStorage.getItem(draftKey.value)
  if (!raw) return
  try {
    const snap = JSON.parse(raw)
    Object.assign(form, snap.form)
    axes_audit.splice(0, axes_audit.length, ...snap.axes_audit)
    equipe_audit.splice(0, equipe_audit.length, ...snap.equipe_audit)
    documents_requis.splice(0, documents_requis.length, ...snap.documents_requis)
    perimetre.splice(0, perimetre.length, ...snap.perimetre)
    lastDraftSavedAt.value = formatTime(snap.savedAt)
    hasDraft.value = true
    showToast('success', 'Brouillon restauré avec succès')
  } catch {
    showToast('error', 'Impossible de restaurer le brouillon')
  }
}

function clearDraft(): void {
  localStorage.removeItem(draftKey.value)
  hasDraft.value = false
  lastDraftSavedAt.value = ''
  autoSaveState.value = 'idle'
}

function formatTime(iso: string): string {
  try {
    const d = new Date(iso)
    return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
  } catch { return '' }
}

// Au montage : vérifier brouillon local si pas encore en DB
onMounted(() => {
  if (!form.id) {
    const raw = localStorage.getItem(draftKey.value)
    if (raw) {
      try {
        const snap = JSON.parse(raw)
        hasDraft.value = true
        lastDraftSavedAt.value = formatTime(snap.savedAt)
      } catch { /* ignore */ }
    }
  }
})

// ─── UI State ─────────────────────────────────────────────────
const activeTab      = ref('A')
const processing     = ref(false)
const aiLoading      = ref(false)
const aiError        = ref('')
const objSelArr      = ref<number[]>([])
const showEquipeModal = ref(false)
const showPrintModal  = ref(false)
const printEntiteId   = ref<string | number>('all')
const printOrg        = ref('')
const printContent    = ref<any[]>([])
const printOpts       = reactive({ equipe: true, documents: true, signatures: true })
const objSelected     = computed((): Set<number> => new Set(objSelArr.value))

const toast = ref<{ show: boolean; type: string; msg: string }>({ show: false, type: 'success', msg: '' })
let _tt: ReturnType<typeof setTimeout> | null = null
function showToast(t: string, m: string) {
  if (_tt) clearTimeout(_tt)
  toast.value = { show: true, type: t, msg: m }
  _tt = setTimeout(() => { toast.value.show = false }, 4000)
}

// ─── Computed ─────────────────────────────────────────────────
const canManage = computed((): boolean => ['DM', 'CM'].includes(props.auditorRole ?? ''))
const isLocked  = computed((): boolean =>
  form.validation_status === 'validated' ||
  (form.validation_status === 'in_review' && !canManage.value)
)

const faiblessesByDomaine = computed((): Record<string, any[]> => {
  const map: Record<string, any[]> = {
    analyse_risques: [], analyse_processus: [], repartition_taches: [],
    analyse_procedures: [], controle_interne: [], controle_conformite: [],
  }
  const raw = db.faiblesses
  if (!raw) return map
  if (!Array.isArray(raw) && typeof raw === 'object') {
    for (const [dom, items] of Object.entries(raw as Record<string, any>)) {
      if (Array.isArray(items)) map[dom] = items
    }
    return map
  }
  for (const f of (raw as any[])) {
    const d = f.domaine ?? 'analyse_risques'
    if (!map[d]) map[d] = []
    map[d].push(f)
  }
  return map
})

const faibProcessusCount  = computed((): number =>
  (faiblessesByDomaine.value['analyse_risques']?.length ?? 0) +
  (faiblessesByDomaine.value['analyse_processus']?.length ?? 0)
)
const faibProceduresCount = computed((): number =>
  (faiblessesByDomaine.value['analyse_procedures']?.length ?? 0) +
  (faiblessesByDomaine.value['controle_conformite']?.length ?? 0)
)
const missingDocsDefaults = computed((): string[] => {
  const existing = new Set(documents_requis.map((d: any) => d.document))
  return DOCS_STANDARDS.filter(d => !existing.has(d))
})

// Objectifs déjà placés dans un axe (par libellé normalisé)
const usedObjectifsSet = computed((): Set<string> => {
  const s = new Set<string>()
  for (const axe of axes_audit) {
    for (const obj of (axe.objectifs ?? [])) {
      if (obj.objectif) s.add(obj.objectif.trim().toLowerCase().slice(0, 60))
    }
  }
  return s
})

// Retourne true si l'objectif BD à l'index i est déjà dans un axe
function isObjUsed(i: number): boolean {
  const o = (db.objectifs_controle ?? [])[i]
  if (!o) return false
  const key = (o.libelle ?? '').trim().toLowerCase().slice(0, 60)
  return usedObjectifsSet.value.has(key)
}

// ─── Helpers ──────────────────────────────────────────────────
function vstLbl(s: string): string {
  return ({ draft: 'Brouillon', in_review: 'En attente', validated: 'Validé ✓', rejected: 'Rejeté' } as Record<string, string>)[s] ?? s
}
function vstIcon(s: string): string {
  return ({ draft: 'ti ti-pencil', in_review: 'ti ti-clock', validated: 'ti ti-circle-check', rejected: 'ti ti-circle-x' } as Record<string, string>)[s] ?? 'ti ti-circle'
}
function formatBudget(v: any): string {
  if (!v) return '—'
  return Number(v).toLocaleString('fr-FR') + ' FCFA'
}
function origineLabel(v: string): string {
  return ({ routinier: 'Routinier', demande_speciale: 'Demande spéciale', alerte: 'Alerte / Signal', autre: 'Autre' } as Record<string, string>)[v] ?? v ?? '—'
}
function addAxe(): void {
  axes_audit.push({ _uid: uid(), axe: '', priorite: 'haute', criteres_evaluation: '', objectifs: [] })
  scheduleDraft()
}
function addObj(axe: any): void {
  if (!axe.objectifs) axe.objectifs = []
  axe.objectifs.push({ objectif: '', indicateurs: '' })
  scheduleDraft()
}
function addPerimetre(): void {
  perimetre.push({ titre: '', contenu: '', guide: '' })
  scheduleDraft()
}
function selectAllObj(): void {
  objSelArr.value = (db.objectifs_controle ?? []).map((_: any, i: number) => i)
}
function clearSelObj(): void { objSelArr.value = [] }
function importEquipe(): void {
  ;(db.equipe ?? []).forEach((eq: any) => {
    if (!equipe_audit.some((e: any) => e.nom === eq.nom))
      equipe_audit.push({ ...eq, jours_homme: '', observations: '' })
  })
  scheduleDraft()
  showToast('success', `${db.equipe?.length ?? 0} membre(s) importé(s)`)
}
function addDocsStandards(): void {
  DOCS_STANDARDS.forEach(d => {
    if (!documents_requis.some((x: any) => x.document === d))
      documents_requis.push({ document: d, source: '', delai: '', statut: '' })
  })
  scheduleDraft()
  showToast('success', 'Documents standards ajoutés')
}

// ─── Impression ───────────────────────────────────────────────
function buildFaibProcessus(): any[] {
  const out: any[] = []; let n = 1
  for (const dom of ['analyse_risques', 'analyse_processus']) {
    for (const f of (faiblessesByDomaine.value[dom] ?? [])) out.push({ ...f, num: n++ })
  }
  return out
}
function buildFaibProcedures(): any[] {
  const out: any[] = []; let n = 1
  for (const dom of ['analyse_procedures', 'controle_conformite']) {
    for (const f of (faiblessesByDomaine.value[dom] ?? [])) out.push({ ...f, num: n++ })
  }
  return out
}
async function lancerImpression(): Promise<void> {
  showPrintModal.value = false
  const entites: any[] = db.entites ?? []
  let selected: any[]
  if (printEntiteId.value === 'all') {
    selected = entites.length
      ? entites
      : [{ name: form.titre || db.mission?.libelle || 'Mission', code_base: '', date_debut: '', date_fin: '' }]
  } else if (printEntiteId.value === 'generic') {
    selected = [{ name: form.titre || db.mission?.libelle || 'Mission', code_base: '', date_debut: '', date_fin: '' }]
  } else {
    selected = entites.filter((e: any) => e.id === printEntiteId.value)
    if (!selected.length) { showToast('error', 'Entité introuvable'); return }
  }
  printContent.value = selected.map((e: any) => ({
    entite: e,
    risques: db.risques_retenus ?? [],
    faibProcessus: buildFaibProcessus(),
    faibProcedures: buildFaibProcedures(),
  }))
  await nextTick()
  window.print()
  setTimeout(() => { printContent.value = [] }, 1500)
}

// ─── IA ───────────────────────────────────────────────────────
const iaSuggestion = ref<any>(null)
// Garde une copie des objectifs sélectionnés AVANT l'appel IA
// pour les réinjecter tels quels si l'IA les modifie
const selectionSnapshot = ref<any[]>([])

async function lancerIA_Objectifs(): Promise<void> {
  if (!objSelArr.value.length) { showToast('error', 'Sélectionnez au moins un objectif'); return }
  aiLoading.value = true; aiError.value = ''; iaSuggestion.value = null

  // Snapshot des objectifs cochés (libellés exacts)
  const sel = objSelArr.value
    .map((i: number) => (db.objectifs_controle ?? [])[i])
    .filter(Boolean)
  selectionSnapshot.value = sel

  try {
    const res = await fetch(props.urlAiSuggest!, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({
        type: 'objectifs_audit',
        mode: 'single_axe',
        mission_title: db.mission?.libelle ?? '',
        mission_objectif: form.objectif_general || db.mission?.objectif || '',
        entity_name: (db.entites ?? [])[0]?.name ?? '',
        objectifs_raw: sel,
        faiblesses_raw: db.faiblesses ?? [],
        risques_retenus: db.risques_retenus ?? [],
      }),
    })
    const d = await res.json()
    if (!d.success) throw new Error(d.error ?? 'Erreur IA')

    const axes: any[] = d.axes ?? []
    if (!axes.length) throw new Error('Aucun axe généré')

    // Fusionner en 1 axe, récupérer le titre et les critères de l'IA
    // mais REMPLACER les objectifs par les libellés exacts cochés
    const axeIA = axes[0]
    const iaIndicateurs: Record<string, string> = {}
    for (const a of axes) {
      for (const o of (a.objectifs ?? [])) {
        // Associer indicateurs IA à la clé normalisée du libellé
        if (o.objectif) {
          iaIndicateurs[o.objectif.trim().toLowerCase().slice(0, 60)] = o.indicateurs ?? ''
        }
      }
    }

    // Construire les objectifs avec libellés EXACTS des sélections
    const objectifsExacts = sel.map((o: any) => {
      const key = (o.libelle ?? '').trim().toLowerCase().slice(0, 60)
      // Chercher les indicateurs IA correspondants (fuzzy match sur 60 chars)
      const indicateurs = iaIndicateurs[key] ?? ''
      return { objectif: o.libelle, indicateurs }
    })

    iaSuggestion.value = {
      axe: axeIA.axe ?? '',
      priorite: axeIA.priorite ?? 'haute',
      criteres_evaluation: axeIA.criteres_evaluation ?? '',
      objectifs: objectifsExacts,
    }
  } catch (e: any) {
    aiError.value = e.message ?? 'Erreur IA'
    showToast('error', aiError.value)
  } finally { aiLoading.value = false }
}

// Confirme et insère l'axe dans le tableau
function confirmerIaAxe(): void {
  if (!iaSuggestion.value) return
  axes_audit.push({
    _uid: uid(),
    axe: iaSuggestion.value.axe,
    priorite: iaSuggestion.value.priorite ?? 'haute',
    criteres_evaluation: iaSuggestion.value.criteres_evaluation ?? '',
    objectifs: iaSuggestion.value.objectifs ?? [],
  })
  scheduleDraft()
  showToast('success', `Axe « ${iaSuggestion.value.axe} » ajouté — ${iaSuggestion.value.objectifs?.length ?? 0} objectif(s)`)
  iaSuggestion.value = null
  selectionSnapshot.value = []
  objSelArr.value = []
}

async function lancerIA_Champ(): Promise<void> {
  aiLoading.value = true; aiError.value = ''
  try {
    const res = await fetch(props.urlAiSuggest!, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({
        type: 'champ_action',
        mission_title: db.mission?.libelle ?? '',
        mission_objectif: form.objectif_general || '',
        entity_name: (db.entites ?? [])[0]?.name ?? '',
        lieux: db.mission?.lieux ?? '',
        periode_auditee: form.periode_auditee || '',
      }),
    })
    const d = await res.json()
    if (!d.success) throw new Error(d.error ?? 'Erreur IA')
    ;(d.perimetre ?? []).forEach((item: any) => {
      const ex = perimetre.find((p: any) => p.titre === item.titre)
      if (ex && !ex.contenu) ex.contenu = item.contenu
      else if (!ex) perimetre.push({ titre: item.titre, contenu: item.contenu, guide: '' })
    })
    scheduleDraft()
    showToast('success', "Champ d'action suggéré")
  } catch (e: any) {
    aiError.value = e.message ?? 'Erreur IA'; showToast('error', aiError.value)
  } finally { aiLoading.value = false }
}

// ─── Submit (enregistrement serveur) ─────────────────────────
function buildPayload() {
  return {
    mission_id: props.missionId,
    assignment_id: props.assignmentId,
    // Fallback sur libellé mission si titre vide (colonne NOT NULL)
    titre: form.titre || db.mission?.libelle || 'Rapport d\'orientation',
    mission_libelle: db.mission?.libelle ?? '',
    date_rapport: form.date_rapport,
    periode_auditee: form.periode_auditee,
    objectif_general: form.objectif_general,
    methodologie: form.methodologie,
    limites: form.limites,
    fait_par: form.fait_par,
    revue_par: form.revue_par,
    approuve_par: form.approuve_par,
    date_approbation: form.date_approbation,
    destinataires: form.destinataires,
    contexte: form.contexte,
    reference_paa: form.reference_paa,
    origine_mission: form.origine_mission,
    axes_audit: JSON.stringify(axes_audit.map((a: any) => ({
      axe: a.axe, priorite: a.priorite,
      criteres_evaluation: a.criteres_evaluation,
      objectifs: a.objectifs ?? [],
    }))),
    objectifs_specifiques: JSON.stringify([]),
    perimetre: JSON.stringify(perimetre),
    equipe_audit: JSON.stringify(equipe_audit),
    documents_requis: JSON.stringify(documents_requis),
    calendrier: JSON.stringify([]),
  }
}

async function submit(): Promise<void> {
  processing.value = true
  try {
    const method = form.id ? 'PUT' : 'POST'
    const url    = form.id
      ? (props.urlUpdate || `${props.formUrl}/${form.id}`)
      : (props.urlStore  || props.formUrl)
    const res = await fetch(url!, {
      method,
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify(buildPayload()),
    })
    const d = await res.json()
    if (d.success || res.ok) {
      showToast('success', form.id ? 'RADO mis à jour.' : 'RADO créé et enregistré.')
      if (d.form) {
        Object.assign(form, {
          id: d.form.id,
          code: d.form.code,
          validation_status: d.form.validation_status,
        })
      }
      // Effacer le brouillon local après enregistrement serveur réussi
      clearDraft()
    } else {
      showToast('error', d.message ?? 'Erreur serveur.')
    }
  } catch {
    showToast('error', 'Erreur réseau.')
  } finally {
    processing.value = false
  }
}

function annuler(): void {
  if (props.backUrl) router.visit(props.backUrl)
}

async function soumettre(): Promise<void> {
  processing.value = true
  try {
    const res = await fetch(props.urlSoumettre || '', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ mission_id: props.missionId, assignment_id: props.assignmentId }),
    })
    const d = await res.json()
    if (d.success) { form.validation_status = 'in_review'; showToast('success', 'Rapport soumis pour validation.') }
    else showToast('error', d.error ?? 'Erreur')
  } catch { showToast('error', 'Erreur réseau') }
  processing.value = false
}

async function valider(action: string, note?: string): Promise<void> {
  processing.value = true
  try {
    const res = await fetch(props.urlValider || '', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ mission_id: props.missionId, assignment_id: props.assignmentId, action, note }),
    })
    const d = await res.json()
    if (d.success) {
      form.validation_status = d.status
      showToast('success', action === 'validate' ? 'Rapport validé ✓' : 'Rapport rejeté.')
    } else showToast('error', d.error ?? 'Erreur')
  } catch { showToast('error', 'Erreur réseau') }
  processing.value = false
}

function promptReject(): void {
  const n = prompt('Motif du rejet :')
  if (!n?.trim()) return
  valider('reject', n.trim())
}

onBeforeUnmount(() => {
  if (_tt) clearTimeout(_tt)
  if (draftTimer) clearTimeout(draftTimer)
  if (serverTimer) clearTimeout(serverTimer)
  if (autoSaveClearTimer) clearTimeout(autoSaveClearTimer)
})
</script>

<style scoped>
*, *::before, *::after { box-sizing: border-box }
.rado-shell { display: flex; flex-direction: column; min-height: 100vh; font-family: 'Segoe UI', system-ui, sans-serif; background: #f0f4f8 }

/* ─── Header ─── */
.rado-header { background: #fff; border-bottom: 1px solid #e5e7eb; padding: 10px 18px 0; position: sticky; top: 0; z-index: 50; box-shadow: 0 1px 4px rgba(0,0,0,.06) }
.rado-hrow { display: flex; align-items: flex-start; gap: 10px; padding-bottom: 8px }
.rado-back { display: flex; align-items: center; justify-content: center; width: 30px; height: 30px; border: 1px solid #e5e7eb; border-radius: 6px; color: #6b7280; text-decoration: none; flex-shrink: 0; margin-top: 2px }
.rado-back:hover { background: #f3f4f6 }
.rado-hinfo { flex: 1; min-width: 0 }
.rado-chips { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; margin-bottom: 2px }
.rado-code { font-size: .64rem; font-weight: 700; background: #1e293b; color: #fff; padding: 1px 6px; border-radius: 3px; font-family: ui-monospace, monospace }
.rado-chip { display: inline-flex; align-items: center; gap: 2px; font-size: .62rem; font-weight: 600; padding: 1px 6px; border-radius: 8px; border: 1px solid transparent }
.chip-draft { background: #f3f4f6; color: #6b7280; border-color: #e5e7eb }
.chip-in_review { background: #e3f2fd; color: #1565C0 }
.chip-validated { background: #ecfdf5; color: #059669; border-color: #a7f3d0 }
.chip-type { background: #fff7ed; color: #c2410c; border-color: #fed7aa }
.chip-role-DM { background: #f5f3ff; color: #7c3aed; border-color: #ddd6fe }
.chip-role-CM { background: #f0f9ff; color: #0284c7; border-color: #bae6fd }
.chip-role-AS { background: #f0fdf4; color: #059669; border-color: #a7f3d0 }
.chip-role-AJ { background: #fffbeb; color: #d97706; border-color: #fde68a }
/* Chips sauvegarde auto */
.chip-autosave { transition: all .2s }
.chip-saving { background: #fffbeb; color: #d97706; border-color: #fde68a }
.chip-saved  { background: #ecfdf5; color: #059669; border-color: #a7f3d0 }
.chip-err    { background: #fef2f2; color: #dc2626; border-color: #fecaca }
.chip-draft-ind { background: #f0f9ff; color: #0284c7; border-color: #bae6fd }
.rado-title { font-size: .95rem; font-weight: 800; color: #111827; margin: 0 0 2px }
.rado-meta { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; font-size: .7rem; color: #6b7280 }
.rado-meta span { display: flex; align-items: center; gap: 3px }
.autosave-hint { color: #0284c7; font-style: italic }
.rado-banner { display: flex; align-items: center; gap: 6px; padding: 5px 0; font-size: .74rem; font-weight: 500; border-top: 1px solid transparent }
.banner-lock { color: #059669; border-top-color: #a7f3d0 }
.banner-review { color: #1565C0 }
.banner-reject { color: #dc2626 }
.banner-draft-restore { color: #15803d; border-top-color: #a7f3d0; background: #f0fdf4; padding: 5px 10px; border-radius: 4px; margin: 4px 0; font-size: .72rem }

/* ─── Onglets ─── */
.rado-tabs { display: flex; background: #fff; border-bottom: 2px solid #e5e7eb; padding: 0 16px; flex-wrap: wrap; flex-shrink: 0; position: sticky; top: 76px; z-index: 40 }
.rtab { display: inline-flex; align-items: center; gap: 4px; padding: 9px 11px; border: none; border-bottom: 3px solid transparent; background: none; color: #6b7280; cursor: pointer; font-size: .7rem; font-weight: 600; font-family: inherit; transition: all .12s; white-space: nowrap }
.rtab:hover { color: #c2410c; background: #fff7ed }
.rtab.active { color: #c2410c; border-bottom-color: #c2410c }
.rtab-modal { border: 1px dashed #e5e7eb; border-bottom: 3px solid transparent; border-radius: 4px 4px 0 0; margin-left: 6px; color: #1f3864 }
.rtab-modal:hover { border-color: #bae6fd; color: #0284c7; background: #f0f9ff }
.tab-badge { font-size: .56rem; font-weight: 800; background: #e2e8f0; color: #475569; padding: 1px 5px; border-radius: 8px; margin-left: 2px }
.rtab.active .tab-badge { background: #fed7aa; color: #c2410c }

/* ─── Body ─── */
.rado-body { flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 0 }
.rado-body::-webkit-scrollbar { width: 4px }
::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 2px }
.tab-content { display: flex; flex-direction: column; gap: 12px }

/* ─── Inputs ─── */
.rinp { background: #fff; border: 1px solid #e5e7eb; color: #111827; padding: 5px 8px; border-radius: 5px; font-size: .75rem; outline: none; transition: border-color .15s; font-family: inherit; width: 100% }
.rinp:focus { border-color: #1f3864; box-shadow: 0 0 0 2px rgba(31,56,100,.08) }
.rinp:disabled { background: #f9fafb; color: #6b7280 }
.rinp-ta { resize: vertical; min-height: 60px }
.rinp-ta-sm { resize: vertical; min-height: 36px; font-size: .73rem }
.rfg { display: flex; flex-direction: column; gap: 2px }
.rlbl { font-size: .58rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .05em }
.mb6 { margin-bottom: 6px } .mb10 { margin-bottom: 10px } .mb4 { margin-bottom: 4px }
.mt6 { margin-top: 6px } .mt12 { margin-top: 16px } .ml-auto { margin-left: auto }

/* ─── En-tête RADO style Excel ─── */
.ro-entete { background: #fff; border: 2px solid #1f3864; border-radius: 6px; overflow: hidden }
.ro-top-bar { background: #1f3864; color: #fff; font-size: .78rem; font-weight: 800; text-align: center; padding: 6px; letter-spacing: .04em }
.ro-logo-row { background: #1f3864; color: rgba(255,255,255,.6); font-size: .68rem; text-align: center; padding: 3px 8px; border-top: 1px solid rgba(255,255,255,.1) }
.ro-ident-bar { background: #1f3864; color: #fff; font-size: .76rem; font-weight: 700; padding: 5px 10px; border-top: 1px solid rgba(255,255,255,.15) }
.ro-ident-fields { display: flex; border-bottom: 1px solid #e5e7eb }
.ro-ifield { display: flex; flex-direction: column; padding: 5px 8px; border-right: 1px solid #e5e7eb; background: #2e75b6 }
.ro-ifield:last-child { border-right: none }
.rf-sm { flex: 0 0 80px } .rf-md { flex: 0 0 140px } .rf-xl { flex: 1 }
.ro-flbl { font-size: .6rem; font-weight: 700; color: rgba(255,255,255,.75); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 2px }
.ro-finp { background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.25); color: #fff; padding: 3px 6px; border-radius: 3px; font-size: .76rem; font-family: inherit; outline: none; width: 100% }
.ro-finp::placeholder { color: rgba(255,255,255,.4) }
.ro-finp:focus { background: rgba(255,255,255,.25) }
.ro-finp-ro { opacity: .7 }
.ro-meta-row { display: flex; flex-wrap: wrap; border-bottom: 1px solid #e5e7eb; background: #f2f2f2 }
.ro-mfield { display: flex; align-items: center; gap: 5px; padding: 5px 10px; border-right: 1px solid #e5e7eb; flex: 1 }
.ro-mfield-sm { flex: 0 0 160px } .ro-mfield-xl { flex: 2 }
.ro-mlbl { font-size: .68rem; font-weight: 700; color: #374151; white-space: nowrap; flex-shrink: 0 }
.ro-minp { flex: 1; background: transparent; border: none; border-bottom: 1px dashed #d1d5db; padding: 1px 4px; font-size: .74rem; color: #111827; font-family: inherit; outline: none; min-width: 60px }
.ro-main-title { text-align: center; font-size: 1rem; font-weight: 900; color: #fff; background: #1f3864; padding: 10px; letter-spacing: .05em; text-transform: uppercase }

/* ─── Sections style Excel ─── */
.xls-section { background: #fff; border: 1px solid #1f3864; border-radius: 4px; overflow: hidden }
.xls-hdr { background: #1f3864; color: #fff; font-size: .76rem; font-weight: 800; padding: 6px 12px; letter-spacing: .02em }
.xls-body { padding: 8px 12px }
.xls-body-pad { padding: 10px 12px }
.xls-row { display: flex; gap: 12px; align-items: flex-start; padding: 4px 12px; border-bottom: 1px solid #f0f0f0 }
.xls-row:last-child { border-bottom: none }
.xls-lbl { font-size: .7rem; font-weight: 600; color: #374151; width: 280px; flex-shrink: 0; background: #f2f2f2; margin: -4px 0; padding: 7px 8px }
.xls-val { flex: 1; padding: 4px 0 }
.xls-guide-txt { font-size: .68rem; color: #6b7280; font-style: italic }

/* ─── Tableaux style Excel ─── */
.xls-tbl { width: 100%; border-collapse: collapse; font-size: .7rem }
.xls-tbl thead th { background: #ed7d31; color: #fff; font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; padding: 6px 8px; border: 1px solid #d97706; white-space: nowrap }
.xls-tbl tbody td { padding: 5px 8px; border: 1px solid #e5e7eb; vertical-align: top }
.xls-tbl tbody tr:nth-child(even) td { background: #ebf4fb }
.xls-tbl tbody tr:nth-child(odd) td { background: #fff }
.tr-crit td { border-left: 3px solid #c00000 !important; background: #fce4d6 !important }
.tr-elev td { border-left: 3px solid #ff0000 !important; background: #fff2cc !important }
.tr-mod td  { border-left: 3px solid #ffc000 !important; background: #fffbeb !important }

/* Objectifs */
.xls-obj-tbl .axe-cell { background: #d6e4f0; border: 1px solid #aaa; vertical-align: middle }
.xls-obj-tbl .axe-cell-inner { display: flex; flex-direction: column; gap: 4px; padding: 4px }
.axe-inp { background: #fff; border: 1px solid #d0e4f0; padding: 3px 6px; border-radius: 3px; font-size: .74rem; font-weight: 700; font-family: inherit; outline: none; width: 100% }
.axe-inp:focus { border-color: #1f3864 }
.axe-lbl { font-size: .74rem; font-weight: 700; color: #1f3864 }
.axe-acts { display: flex; gap: 4px; margin-top: 4px }
.axe-criteres-cell { background: #ebf4fb; font-size: .68rem; color: #374151 }
.axe-row td { background: #d6e4f0 }
.obj-row td { background: #fff }
.crit-ro { font-size: .68rem; color: #374151; font-style: italic }
.obj-ro { font-size: .72rem; color: #111827; line-height: 1.5 }

/* Champ action */
.xls-champ-tbl .xls-champ-lbl { background: #f2f2f2; font-weight: 700; font-size: .72rem; vertical-align: middle }
.xls-champ-tbl .xls-guide { background: #fff2cc; font-size: .66rem; color: #5a4000 }
.db-hint-inline { display: flex; align-items: center; gap: 5px; font-size: .7rem; color: #1e40af }
.guide-txt { color: #6b7280; font-style: italic }
.ps-ro { font-size: .74rem; color: #111827; margin: 0; line-height: 1.6 }

/* Info cards */
.info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 10px }
.info-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 6px; overflow: hidden }
.info-card-hdr { background: #1f3864; color: #fff; font-size: .63rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; padding: 6px 10px; display: flex; align-items: center; gap: 5px }
.ir { display: flex; align-items: flex-start; gap: 8px; padding: 3px 10px; border-bottom: 1px solid #f9fafb; font-size: .7rem }
.ilbl { font-size: .6rem; font-weight: 600; color: #9ca3af; width: 65px; flex-shrink: 0 }
.ival { color: #111827; flex: 1 } .ival.fw { font-weight: 600 } .ival.mono { font-family: ui-monospace, monospace; font-size: .67rem }
.entite-row { display: flex; align-items: center; gap: 6px; padding: 4px 10px; border-bottom: 1px solid #f9fafb; font-size: .7rem }
.entite-name { font-weight: 600; flex: 1 } .entite-code { font-family: ui-monospace, monospace; font-size: .64rem; color: #6b7280 } .entite-dates { font-size: .62rem; color: #9ca3af }

/* Pool objectifs (ancien - conservé pour compatibilité) */
.pool-hdr { display: flex; align-items: center; justify-content: space-between; padding: 6px 10px; background: #f8fafc; border-bottom: 1px solid #e5e7eb; flex-wrap: wrap; gap: 6px }
.pool-info { font-size: .7rem; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 5px }
.pool-acts { display: flex; gap: 5px }

/* ── Nouveau sélecteur compact d'objectifs ── */
.obj-picker { border-top: 1px solid #e5e7eb; padding: 8px 10px; background: #f8fafc; display: flex; flex-direction: column; gap: 6px }
.obj-picker-top { display: flex; align-items: center; justify-content: space-between }
.obj-picker-lbl { font-size: .65rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; display: flex; align-items: center; gap: 4px }
.obj-picker-acts { display: flex; gap: 4px }

/* Chips de sélection */
.obj-chips-list { display: flex; flex-wrap: wrap; gap: 4px; max-height: 120px; overflow-y: auto; padding: 2px 0 }
.obj-chips-list::-webkit-scrollbar { height: 3px; width: 3px }
.obj-chips-list::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 2px }
.obj-chip { display: inline-flex; align-items: baseline; gap: 4px; padding: 2px 8px 2px 6px; border: 1px solid #e2e8f0; border-radius: 20px; background: #fff; cursor: pointer; transition: all .12s; max-width: 320px }
.obj-chip:hover { border-color: #bfdbfe; background: #eff6ff }
.obj-chip.active { border-color: #1f3864; background: #1f3864 }
.obj-chip.active .obj-chip-src { color: rgba(255,255,255,.65) }
.obj-chip.active .obj-chip-lib { color: #fff }
.obj-chip.used { border-color: #d1fae5; background: #f0fdf4; cursor: not-allowed; opacity: .7 }
.obj-chip.used .obj-chip-src { color: #6ee7b7 }
.obj-chip.used .obj-chip-lib { color: #6b7280; text-decoration: line-through }
.obj-chip-used-icon { font-size: .55rem; color: #059669; flex-shrink: 0; margin-left: 2px }
.obj-chip-lib { font-size: .66rem; color: #374151; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 260px }
.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0) }

/* Barre d'action contextuelle */
.obj-action-bar { display: flex; align-items: center; gap: 8px; padding: 6px 10px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; flex-wrap: wrap }
.obj-action-count { font-size: .7rem; font-weight: 700; color: #1f3864; flex-shrink: 0 }
.action-bar-t-enter-active, .action-bar-t-leave-active { transition: all .18s }
.action-bar-t-enter-from, .action-bar-t-leave-to { opacity: 0; transform: translateY(-4px) }

/* Preview suggestion IA inline */
.ia-preview { display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0; flex-wrap: wrap }
.ia-preview-hdr { display: flex; align-items: center; gap: 6px; flex: 1; min-width: 0; padding: 3px 8px; background: #fdf4ff; border: 1px solid #e9d5ff; border-radius: 4px }
.ia-preview-axe { font-size: .72rem; font-weight: 700; color: #7c3aed; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1 }
.ia-preview-count { font-size: .6rem; color: #a78bfa; flex-shrink: 0; white-space: nowrap }
.ia-preview-actions { display: flex; gap: 4px; flex-shrink: 0 }
.ai-err-sm { font-size: .65rem; padding: 3px 8px }

/* Misc */
.empty-block { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 20px; color: #9ca3af; text-align: center; background: #fafafa; border: 1.5px dashed #e5e7eb; border-radius: 6px }
.empty-block i { font-size: 1.2rem; opacity: .2 } .empty-block p { font-size: .7rem; max-width: 300px }
.empty-sm { font-size: .68rem; color: #9ca3af; padding: 6px; font-style: italic }
.tc { text-align: center } .fw { font-weight: 700 } .sm { font-size: .66rem } .mono { font-family: ui-monospace, monospace }
.sc { font-size: .62rem; font-weight: 800; padding: 2px 5px; border-radius: 3px }
.sc-crit { background: #c00000; color: #fff } .sc-elev { background: #ff0000; color: #fff }
.sc-mod  { background: #ffc000; color: #000 } .sc-faib { background: #d1fae5; color: #065f46 }
.db-hint { display: flex; align-items: center; gap: 6px; padding: 5px 8px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 4px; font-size: .7rem; color: #1e40af; flex-wrap: wrap }
.db-hint em { font-style: italic; color: #374151; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 400px }
.ai-bar { display: flex; align-items: center; justify-content: space-between; padding: 7px 10px; background: #fdf4ff; border: 1px solid #e9d5ff; border-radius: 5px; font-size: .72rem; color: #7c3aed; gap: 8px }
.ai-err { display: flex; align-items: center; gap: 5px; padding: 6px 10px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 5px; font-size: .7rem; color: #dc2626; margin-top: 6px }
.note-bas { display: flex; align-items: center; gap: 6px; padding: 8px 12px; background: #1f3864; color: rgba(255,255,255,.85); font-size: .7rem; font-style: italic; border-radius: 4px; margin-top: 4px }
.xls-guide-txt { font-size: .68rem; color: #6b7280; font-style: italic; margin-bottom: 6px }

/* Inline inputs (tableaux) */
.c-inp { width: 100%; border: 1px solid transparent; border-radius: 3px; padding: 2px 4px; font-size: .7rem; color: #111827; font-family: inherit; outline: none; background: transparent }
.c-inp:hover { border-color: #e5e7eb; background: #fff }
.c-inp:focus { border-color: #1f3864; background: #fff }
.c-ro { font-size: .7rem; color: #374151 }

/* ─── Boutons ─── */
.btn { display: inline-flex; align-items: center; gap: 4px; padding: 5px 10px; border-radius: 5px; font-size: .75rem; font-weight: 600; border: none; cursor: pointer; font-family: inherit; transition: all .13s; white-space: nowrap }
.btn-save  { background: #1f3864; color: #fff } .btn-save:hover:not(:disabled) { background: #162d4e }
.btn-draft { background: #f0fdf4; color: #15803d; border: 1px solid #a7f3d0 } .btn-draft:hover:not(:disabled) { background: #dcfce7 }
.btn-ghost { background: #fff; color: #374151; border: 1px solid #e5e7eb } .btn-ghost:hover:not(:disabled) { background: #f9fafb }
.btn-db  { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe } .btn-db:hover { background: #dbeafe }
.btn-ai  { background: #fdf4ff; color: #7c3aed; border: 1px solid #e9d5ff } .btn-ai:hover:not(:disabled) { background: #ede9fe }
.btn-sub { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa }
.btn-ok  { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0 }
.btn-rej { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca }
.btn-print { background: #1f3864; color: #fff; border: 1px solid #1f3864 } .btn-print:hover { background: #162d4e }
.btn-sm { padding: 4px 9px; font-size: .72rem } .btn-xs { padding: 2px 6px; font-size: .65rem }
.ibtn { width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; background: transparent; border: 1px solid transparent; border-radius: 4px; cursor: pointer; font-size: .72rem; color: #d1d5db; padding: 0; flex-shrink: 0 }
.ibtn-ok:hover  { color: #059669; border-color: #a7f3d0; background: #ecfdf5 }
.ibtn-del:hover { color: #dc2626; border-color: #fecaca; background: #fef2f2 }
.btn:disabled { opacity: .45; cursor: not-allowed }

/* ─── Footer ─── */
.rado-footer { display: flex; align-items: center; justify-content: space-between; padding: 8px 16px; background: #fff; border-top: 1px solid #e5e7eb; flex-wrap: wrap; gap: 6px; flex-shrink: 0; position: sticky; bottom: 0; z-index: 40 }
.footer-left  { display: flex; gap: 5px; flex-wrap: wrap }
.footer-right { display: flex; gap: 5px; flex-wrap: wrap }
.footer-mid   { flex: 1; display: flex; justify-content: center }

/* Niveaux sauvegarde */
.save-levels { display: flex; align-items: center; gap: 6px; padding: 4px 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 20px }
.save-level { display: flex; align-items: center; gap: 4px; font-size: .62rem; font-weight: 600; color: #9ca3af; transition: color .2s }
.save-level.active { color: #059669 }
.level-dot { width: 6px; height: 6px; border-radius: 50%; background: #e2e8f0; transition: background .2s; flex-shrink: 0 }
.save-level.active .level-dot { background: #059669 }
.save-level-sep { font-size: .6rem; color: #d1d5db }
.saved-code { font-size: .7rem; color: #059669; display: flex; align-items: center; gap: 3px; font-weight: 600 }

/* ─── Toast ─── */
.toast { position: fixed; top: 14px; right: 14px; z-index: 9999; display: flex; align-items: center; gap: 6px; padding: 8px 13px; border-radius: 7px; font-size: .75rem; font-weight: 600; box-shadow: 0 4px 16px rgba(0,0,0,.12); border: 1px solid transparent }
.toast--success { background: #ecfdf5; color: #059669; border-color: #a7f3d0 }
.toast--error   { background: #fef2f2; color: #dc2626; border-color: #fecaca }
.toast-t-enter-active, .toast-t-leave-active { transition: all .22s }
.toast-t-enter-from, .toast-t-leave-to { opacity: 0; transform: translateX(8px) }

/* ─── Modales ─── */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 1000; display: flex; align-items: center; justify-content: center }
.modal-box { background: #fff; border-radius: 10px; width: 500px; max-width: 95vw; box-shadow: 0 20px 60px rgba(0,0,0,.22); overflow: hidden }
.modal-box-lg { width: 800px; max-width: 96vw }
.modal-hdr { display: flex; align-items: center; gap: 8px; padding: 14px 18px; background: #1f3864; color: #fff; font-size: .82rem; font-weight: 700 }
.modal-close { margin-left: auto; background: transparent; border: none; color: rgba(255,255,255,.6); cursor: pointer; font-size: .9rem; display: flex; align-items: center; padding: 2px }
.modal-close:hover { color: #fff }
.modal-body { padding: 18px }
.modal-body-scroll { max-height: 62vh; overflow-y: auto; padding: 16px 18px }
.modal-body-scroll::-webkit-scrollbar { width: 4px }
.modal-note { font-size: .73rem; color: #6b7280; margin: 0 0 14px; font-style: italic; line-height: 1.5 }
.modal-footer { display: flex; justify-content: flex-end; align-items: center; gap: 8px; padding: 12px 18px; border-top: 1px solid #f3f4f6 }
.modal-footer-info { font-size: .68rem; color: #059669; display: flex; align-items: center; gap: 4px; margin-right: auto }
.modal-section-hdr { display: flex; align-items: center; gap: 8px; font-size: .78rem; font-weight: 800; color: #1f3864; padding: 8px 0 6px; border-bottom: 2px solid #1f3864; margin-bottom: 8px }
.modal-section-acts { margin-left: auto; display: flex; align-items: center; gap: 6px }
.modal-t-enter-active, .modal-t-leave-active { transition: all .2s }
.modal-t-enter-from, .modal-t-leave-to { opacity: 0; transform: scale(.96) }
.chk-row { display: flex; align-items: center; gap: 6px; font-size: .73rem; color: #374151; padding: 3px 0; cursor: pointer }

/* Spinner */
.spin-s { width: 10px; height: 10px; border-radius: 50%; border: 2px solid currentColor; border-top-color: transparent; animation: spin .6s linear infinite; display: inline-block; flex-shrink: 0 }
@keyframes spin { to { transform: rotate(360deg) } }

/* ════════════════════════════════════
   IMPRESSION — format fidèle Excel
   ════════════════════════════════════ */
#rado-print-zone { display: none }

@media print {
  #rado-print-zone { display: block !important }
  body > *:not(#rado-print-zone) { display: none !important }
  #rado-print-zone { font-family: Arial, Helvetica, sans-serif; font-size: 8.5pt; color: #000; background: #fff }
  .rp-doc { padding: 6mm 8mm }
  .rp-org-hdr { background: #1f3864; color: #fff; font-size: 9pt; font-weight: bold; text-align: center; padding: 5pt 8pt; letter-spacing: .03em }
  .rp-org-sub { background: #1f3864; color: rgba(255,255,255,.65); font-size: 7.5pt; text-align: center; padding: 2pt 8pt; border-top: 1pt solid rgba(255,255,255,.2) }
  .rp-ident-bar { background: #1f3864; color: #fff; font-size: 8pt; font-weight: bold; padding: 4pt 8pt }
  .rp-ident-tbl { width: 100%; border-collapse: collapse; border: 1pt solid #2e75b6 }
  .rp-ident-tbl td { background: #2e75b6; padding: 4pt 6pt; border-right: 1pt solid rgba(255,255,255,.3) }
  .rp-ident-tbl td:last-child { border-right: none }
  .rp-id-lbl { display: block; font-size: 6.5pt; font-weight: bold; color: rgba(255,255,255,.75); text-transform: uppercase }
  .rp-id-val { display: block; font-size: 8.5pt; color: #fff }
  .rp-id-val.fw { font-weight: bold }
  .rp-id-rado { width: 55pt; text-align: center }
  .rp-id-xl { width: auto }
  .rp-meta-tbl { width: 100%; border-collapse: collapse; background: #f2f2f2; border: 1pt solid #ccc; border-top: none }
  .rp-meta-tbl td { padding: 3pt 8pt; border-right: 1pt solid #ccc; font-size: 7.5pt }
  .rp-meta-tbl td:last-child { border-right: none }
  .rp-main-title { background: #1f3864; color: #fff; font-size: 12pt; font-weight: bold; text-align: center; padding: 8pt; text-transform: uppercase; letter-spacing: .05em }
  .rp-entite-banner { background: #2e75b6; color: #fff; font-size: 9pt; font-weight: bold; padding: 4pt 8pt; margin: 5pt 0; border-radius: 2pt }
  .rp-sec-hdr { background: #1f3864; color: #fff; font-size: 8.5pt; font-weight: bold; padding: 4pt 8pt; text-transform: uppercase; margin-top: 4pt }
  .rp-s1-tbl { width: 100%; border-collapse: collapse; font-size: 8pt }
  .rp-s1-tbl td { padding: 4pt 7pt; border: 1pt solid #ccc; vertical-align: top }
  .rp-s1-lbl { background: #f2f2f2; font-weight: bold; font-size: 7.5pt; width: 180pt }
  .rp-s2-body { padding: 7pt 10pt; font-size: 8.5pt; min-height: 25pt; line-height: 1.55; border: 1pt solid #ccc; border-top: none; background: #fff9e6 }
  .rp-tbl { width: 100%; border-collapse: collapse; font-size: 7.5pt }
  .rp-th-orange th { background: #ed7d31; color: #fff; font-size: 7pt; font-weight: bold; padding: 3.5pt 5pt; text-align: left; border: 1pt solid #d97706; white-space: nowrap }
  .rp-tr-data td { padding: 3.5pt 5pt; border: 1pt solid #ddd; vertical-align: top }
  .rp-tr-data:nth-child(even) td { background: #ebf4fb }
  .rp-tr-data:nth-child(odd) td { background: #fff }
  .rp-tr-empty td { background: #fce4d6 !important; color: #999 }
  .rp-sc { font-size: 7pt; font-weight: bold; padding: 1pt 4pt; border-radius: 2pt; display: inline-block }
  .rp-sc-crit { background: #c00000; color: #fff }
  .rp-sc-elev { background: #ff0000; color: #fff }
  .rp-sc-mod  { background: #ffc000; color: #000 }
  .rp-axe-td { background: #d6e4f0; font-weight: bold; font-size: 7.5pt; vertical-align: middle }
  .rp-champ-tbl { width: 100%; border-collapse: collapse; font-size: 8pt }
  .rp-champ-lbl { background: #f2f2f2; font-weight: bold; width: 90pt; padding: 4pt 7pt; border: 1pt solid #ccc; vertical-align: middle }
  .rp-champ-saisie { padding: 4pt 7pt; border: 1pt solid #ccc; background: #fff2cc; min-height: 20pt }
  .rp-tr-def td { background: #fff2cc !important; color: #888; font-style: italic }
  .rp-page-break { page-break-after: always; height: 0; visibility: hidden }
  * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important }
  @page { margin: 10mm 8mm; size: A4 }
}
</style>