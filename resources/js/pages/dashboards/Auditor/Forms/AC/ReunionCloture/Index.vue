<template>
  <VerticalLayoutAudit>
    <div class="rc-shell">

      <!-- ══ TOPBAR ══════════════════════════════════════════════ -->
      <header class="rc-topbar">
        <div class="rc-topbar__left">
          <a :href="props.backUrl" class="rc-ib" title="Retour">
            <i class="ti ti-arrow-left"></i>
          </a>
          <span class="rc-code">{{ reunion?.id ? 'RC-' + String(reunion.id).padStart(4,'0') : 'RC-NOUVEAU' }}</span>
          <span class="rc-sdot" :class="'sd--' + (reunion?.statut ?? 'draft')"></span>
          <span class="rc-vstatus">{{ vstLbl(reunion?.statut ?? 'draft') }}</span>
          <div class="rc-div"></div>
          <i class="ti ti-building rc-icon-muted"></i>
          <span class="rc-mission-lbl">{{ missionLibelle }}</span>
        </div>
        <div class="rc-topbar__right">
          <div class="rc-tabs">
            <button v-for="tab in tabs" :key="tab.id"
              class="rc-tab" :class="activeTab === tab.id ? 'rc-tab--active' : ''"
              @click="activeTab = tab.id">
              <i :class="'ti ' + tab.icon"></i> {{ tab.label }}
            </button>
          </div>
          <button class="rc-btn rc-btn--slides" @click="ouvrirSlides"
            :class="slidesMode ? 'rc-btn--slides-on' : ''"
            title="Mode présentation">
            <i class="ti ti-presentation"></i> Slides
          </button>
          <template v-if="!isLocked">
            <button class="rc-btn rc-btn--ghost" :disabled="saving" @click="annuler">
              <i class="ti ti-x"></i>
            </button>
            <button class="rc-btn rc-btn--save" :disabled="saving" @click="sauvegarder">
              <span v-if="saving" class="rc-spin"></span>
              <i v-else class="ti ti-device-floppy"></i>
              Enregistrer
            </button>
            <button v-if="reunion?.id && reunion.statut === 'draft'"
              class="rc-btn rc-btn--submit" @click="soumettre">
              <i class="ti ti-send"></i> Soumettre
            </button>
          </template>
          <template v-if="props.canManage && reunion?.statut === 'in_review'">
            <button class="rc-btn rc-btn--validate" @click="valider('validate')">
              <i class="ti ti-circle-check"></i> Valider
            </button>
            <button class="rc-btn rc-btn--reject" @click="promptReject">
              <i class="ti ti-circle-x"></i> Rejeter
            </button>
          </template>
        </div>
      </header>

      <!-- Banners statut -->
      <div v-if="reunion?.statut === 'validated'" class="rc-banner rc-banner--ok">
        <i class="ti ti-lock"></i> PV validé — lecture seule
      </div>
      <div v-else-if="reunion?.statut === 'in_review'" class="rc-banner rc-banner--review">
        <i class="ti ti-clock"></i> En attente de validation
      </div>
      <div v-else-if="reunion?.statut === 'draft' && reunion?.validation_note"
        class="rc-banner rc-banner--rejected">
        <i class="ti ti-circle-x"></i> Rejeté — <em>{{ reunion.validation_note }}</em>
      </div>

      <!-- PANEL LOGS (toujours visible pour le debug) -->
      <div class="rc-debug-panel" style="margin: 1rem; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0.75rem;">
        <details>
          <summary>📋 Logs techniques ({{ props.logs?.length ?? 0 }})</summary>
          <pre style="font-size: 11px; overflow: auto; max-height: 200px;">{{ JSON.stringify(props.logs, null, 2) }}</pre>
        </details>
      </div>

      <!-- ══ MODE SLIDES ════════════════════════════════════════ -->
      <transition name="slides-fade">
        <div v-if="slidesMode" class="rc-slides-overlay">
          <div class="rc-slides-shell">
            <div class="rc-slides-nav">
              <button class="rc-slides-close" @click="slidesMode = false">
                <i class="ti ti-x"></i> Quitter
              </button>
              <div class="rc-slides-progress">
                <span v-for="(s, si) in slides" :key="si"
                  class="rc-slides-dot"
                  :class="slideIndex === si ? 'rc-slides-dot--active' : ''"
                  @click="slideIndex = si"></span>
              </div>
              <div class="rc-slides-counter">{{ slideIndex + 1 }} / {{ slides.length }}</div>
              <button class="rc-slides-prev" :disabled="slideIndex === 0" @click="slideIndex--">
                <i class="ti ti-chevron-left"></i>
              </button>
              <button class="rc-slides-next" :disabled="slideIndex === slides.length - 1" @click="slideIndex++">
                <i class="ti ti-chevron-right"></i>
              </button>
            </div>
            <div class="rc-slides-stage" @keydown="onSlideKey" tabindex="0" ref="stageRef">
              <transition :name="slideDir === 'next' ? 'slide-next' : 'slide-prev'" mode="out-in">
                <div :key="slideIndex" class="rc-slide">
                  <component :is="slides[slideIndex].component"
                    v-bind="slides[slideIndex].props"
                    :reunionData="rcForm"
                    :fraps="props.fraps"
                    :fociGrouped="props.fociGrouped"
                    :planActionGrouped="props.planActionGrouped"
                    :missionLibelle="missionLibelle" />
                </div>
              </transition>
            </div>
          </div>
        </div>
      </transition>

      <!-- ══ CONTENU PRINCIPAL ═════════════════════════════════ -->
      <div class="rc-main">

        <!-- ◈ ONGLET PV DE CLOTURE ─────────────────────────── -->
        <div v-show="activeTab === 'pv'" class="rc-pv-doc">
          <div class="rc-doc-header">
            <div class="rc-doc-header__title">
              PROCÈS-VERBAL DE RÉUNION DE CLÔTURE — PHASE DE VÉRIFICATION
            </div>
            <div class="rc-doc-header__sub">AUDIT INTERNE · Norme MPA 2400 – 2410-1</div>
          </div>

          <!-- ── BLOC INFO MISSION COMPLET (auto-chargé) ── -->
          <div v-if="props.mission" class="rc-mission-banner">
            <!-- Colonne gauche : infos mission -->
            <div class="rc-mb-left">
              <div class="rc-mb-icon"><i class="ti ti-clipboard-list"></i></div>
              <div class="rc-mb-body">
                <div class="rc-mb-title">{{ props.mission.libelle }}</div>
                <div class="rc-mb-meta">
                  <span><i class="ti ti-hash"></i>{{ props.mission.code_mission }}</span>
                  <span v-if="props.mission.date_debut">
                    <i class="ti ti-calendar"></i>
                    {{ formatDate(props.mission.date_debut) }} → {{ formatDate(props.mission.date_fin) }}
                  </span>
                  <span v-if="props.mission.lieux"><i class="ti ti-map-pin"></i>{{ props.mission.lieux }}</span>
                </div>
                <div v-if="props.mission.objectif" class="rc-mb-obj">
                  <i class="ti ti-target"></i> {{ props.mission.objectif }}
                </div>
                <div v-if="props.programmeData?.found" class="rc-mb-prog">
                  <span class="rc-mb-prog-badge">{{ props.programmeData.programme_code }}</span>
                  <span>{{ props.programmeData.total_objectifs }} objectifs · {{ props.programmeData.total_tests }} tests</span>
                </div>
              </div>
            </div>

            <!-- Colonne centre : statistiques FRAP -->
            <div class="rc-mb-stats">
              <div class="rc-mb-stats__title"><i class="ti ti-chart-bar"></i> Vue d'ensemble</div>
              <div class="rc-mb-kpis">
                <div class="rc-mb-kpi"><div class="rc-mb-kpi__val">{{ props.totalFraps ?? 0 }}</div><div class="rc-mb-kpi__lbl">FRAPs</div></div>
                <div class="rc-mb-kpi"><div class="rc-mb-kpi__val">{{ props.fichesTest?.length ?? 0 }}</div><div class="rc-mb-kpi__lbl">Fiches test</div></div>
                <div class="rc-mb-kpi" v-if="props.scoreIaMoyen !== null">
                  <div class="rc-mb-kpi__val" :style="{ color: props.scoreIaMoyen >= 7 ? '#10b981' : props.scoreIaMoyen >= 5 ? '#f59e0b' : '#dc2626' }">
                    {{ props.scoreIaMoyen }}/10
                  </div>
                  <div class="rc-mb-kpi__lbl">Score IA</div>
                </div>
                <div class="rc-mb-kpi" v-else><div class="rc-mb-kpi__val rc-mb-kpi__val--muted">—</div><div class="rc-mb-kpi__lbl">Score IA</div></div>
              </div>
              <div class="rc-mb-niveaux">
                <div v-for="s in statsGlobalesDisplay" :key="s.key"
                  class="rc-mb-niv-chip" :style="{ background: s.bg, color: s.color, borderColor: s.color + '40' }">
                  {{ s.label }} <strong>{{ s.count }}</strong>
                </div>
                <div v-if="!statsGlobalesDisplay.length" class="rc-mb-niv-empty">Aucune FRAP enregistrée</div>
              </div>
              <div v-if="topIaGlobal" class="rc-mb-ia-top">
                <i class="ti ti-robot"></i>
                <span class="rc-mb-ia-concl">{{ topIaGlobal.conclusion }}</span>
              </div>
            </div>

            <!-- Colonne droite : équipe d'audit -->
            <div class="rc-mb-equipe">
              <div class="rc-mb-equipe__title"><i class="ti ti-users"></i> Équipe d'audit</div>
              <div class="rc-mb-equipe__list">
                <div v-for="m in props.equipe" :key="m.id" class="rc-mb-membre">
                  <span class="rc-mb-role" :class="'role--' + m.role_code">{{ m.role_code }}</span>
                  <div class="rc-mb-membre-info">
                    <span class="rc-mb-nom">{{ m.full_name }}</span>
                    <span class="rc-mb-role-lbl">{{ m.role_label }}</span>
                  </div>
                </div>
              </div>
              <div v-if="props.rciLignes?.length" class="rc-mb-rci">
                <i class="ti ti-shield-check"></i> {{ props.rciLignes.length }} contrôle(s) référentiel CI
              </div>
            </div>
          </div>

          <!-- Section 0 : En-tête -->
          <div class="rc-section">
            <div class="rc-section__title">Identification</div>
            <div class="rc-grid rc-grid--3">
              <div class="rc-field rc-field--full"><label>Entité / Structure auditée</label><input class="rc-inp" v-model="rcForm.entite" :disabled="isLocked" /></div>
              <div class="rc-field rc-field--full"><label>Mission d'audit</label><input class="rc-inp" v-model="rcForm.intitule_mission" :disabled="isLocked" /></div>
              <div class="rc-field"><label>Code mission</label><input class="rc-inp" v-model="rcForm.code_mission" :disabled="isLocked" /></div>
              <div class="rc-field"><label>Normes MPA</label><input class="rc-inp" v-model="rcForm.norme_mpa" :disabled="isLocked" /></div>
              <div class="rc-field"><label>Date de la réunion</label><input class="rc-inp" type="date" v-model="rcForm.date_reunion" :disabled="isLocked" /></div>
              <div class="rc-field"><label>Heure début</label><input class="rc-inp" type="time" v-model="rcForm.heure_debut" :disabled="isLocked" /></div>
              <div class="rc-field"><label>Heure fin</label><input class="rc-inp" type="time" v-model="rcForm.heure_fin" :disabled="isLocked" /></div>
              <div class="rc-field"><label>Lieu</label><input class="rc-inp" v-model="rcForm.lieu" :disabled="isLocked" /></div>
              <div class="rc-field"><label>Présidée par</label><input class="rc-inp" v-model="rcForm.preside_par" :disabled="isLocked" /></div>
              <div class="rc-field"><label>Secrétaire de séance</label><input class="rc-inp" v-model="rcForm.secretaire_seance" :disabled="isLocked" /></div>
            </div>
          </div>

          <!-- Section 1 : Ordre du jour -->
          <div class="rc-section">
            <div class="rc-section__title">
              <span>1. Ordre du jour</span>
              <button v-if="!isLocked" class="rc-add-btn" @click="rcForm.ordre_jour.push({ libelle: '' })"><i class="ti ti-plus"></i> Ajouter</button>
            </div>
            <table class="rc-tbl">
              <thead><tr><th style="width:50px">N°</th><th>Point à l'ordre du jour</th><th v-if="!isLocked" style="width:36px"></th></tr></thead>
              <tbody>
                <tr v-if="!rcForm.ordre_jour.length"><td colspan="3" class="rc-ec">Aucun point défini</td></tr>
                <tr v-for="(oj, i) in rcForm.ordre_jour" :key="i">
                  <td class="rc-tc rc-muted">{{ i+1 }}</td>
                  <td><textarea class="rc-ta-sm" v-model="oj.libelle" rows="2" :disabled="isLocked"></textarea></td>
                  <td v-if="!isLocked" class="rc-tc"><button class="rc-del" @click="rcForm.ordre_jour.splice(i,1)"><i class="ti ti-trash"></i></button></td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Section 2 : Points forts -->
          <div class="rc-section">
            <div class="rc-section__title">
              <span>2. Points forts de l'entité auditée</span>
              <button v-if="!isLocked" class="rc-add-btn" @click="rcForm.points_forts.push({ libelle: '' })"><i class="ti ti-plus"></i> Ajouter</button>
            </div>
            <table class="rc-tbl">
              <thead><tr><th style="width:50px">N°</th><th>Bonne pratique / Point fort identifié</th><th v-if="!isLocked" style="width:36px"></th></tr></thead>
              <tbody>
                <tr v-if="!rcForm.points_forts.length"><td colspan="3" class="rc-ec">Aucun point fort enregistré</td></tr>
                <tr v-for="(pf, i) in rcForm.points_forts" :key="i">
                  <td class="rc-tc rc-muted">{{ i+1 }}</td>
                  <td><input class="rc-inp-sm" v-model="pf.libelle" :disabled="isLocked" /></td>
                  <td v-if="!isLocked" class="rc-tc"><button class="rc-del" @click="rcForm.points_forts.splice(i,1)"><i class="ti ti-trash"></i></button></td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Section 3 : Participants -->
          <div class="rc-section">
            <div class="rc-section__title">
              <span>3. Liste des participants</span>
              <button v-if="!isLocked" class="rc-add-btn" @click="rcForm.participants.push({ nom:'', fonction:'', entite:'', present:true })"><i class="ti ti-plus"></i> Ajouter</button>
              <button v-if="!isLocked && props.equipe?.length" class="rc-sync-btn" @click="syncParticipantsDepuisEquipe"><i class="ti ti-refresh"></i> Sync équipe</button>
            </div>
            <div class="rc-participants-grid">
              <div v-if="!rcForm.participants.length" class="rc-ec">Aucun participant. Cliquez sur « Sync équipe ».</div>
              <div v-for="(p, pi) in rcForm.participants" :key="pi" class="rc-participant-card">
                <div class="rc-participant-card__top">
                  <label class="rc-pcard-check"><input type="checkbox" v-model="p.present" :disabled="isLocked" /><span class="rc-pcard-present" :class="p.present ? 'rpp--ok' : 'rpp--absent'">{{ p.present ? 'Présent' : 'Absent' }}</span></label>
                  <button v-if="!isLocked" class="rc-del" style="margin-left:auto" @click="rcForm.participants.splice(pi,1)"><i class="ti ti-trash"></i></button>
                </div>
                <div class="rc-pcard-fields">
                  <input class="rc-inp-sm" v-model="p.nom" :disabled="isLocked" placeholder="Nom et Prénom" />
                  <input class="rc-inp-sm" v-model="p.fonction" :disabled="isLocked" placeholder="Fonction / Titre" />
                  <input class="rc-inp-sm" v-model="p.entite" :disabled="isLocked" placeholder="Entité / Structure" />
                </div>
              </div>
            </div>
          </div>

          <!-- Section 4 : Validation FAR -->
          <div class="rc-section">
            <div class="rc-section__title">
              <span>4. Validation des observations d'audit (FAR)</span>
              <button v-if="!isLocked" class="rc-sync-btn" @click="syncFraps" :disabled="syncing"><span v-if="syncing" class="rc-spin rc-spin--xs"></span><i v-else class="ti ti-refresh"></i> Sync FRAPs</button>
              <button v-if="!isLocked" class="rc-add-btn" @click="ajouterFarManuelle"><i class="ti ti-plus"></i> FAR manuelle</button>
            </div>
            <div class="rc-far-table-wrap">
              <table class="rc-far-tbl">
                <thead>
                  <tr><th style="width:80px">N° FAR</th><th>Faits / Constats</th><th>Problèmes</th><th>Causes</th><th>Impacts</th><th>Recommandations</th><th>Acceptation</th><th>Appréciation audité</th><th>Échéance</th><th>Responsable</th><th>Livrable</th></tr>
                </thead>
                <tbody>
                  <tr v-if="!rcForm.far_validations?.length"><td colspan="11" class="rc-ec">Aucune FAR. Cliquez sur « Sync FRAPs ».</td></tr>
                  <tr v-for="(far, fi) in rcForm.far_validations" :key="far.id ?? fi" :class="farRowClass(far.acceptation)">
                    <td class="rc-tc"><span class="rc-far-num-badge">{{ far.num_far }}</span></td>
                    <td><div class="rc-cell-editable"><span class="rc-cell-txt">{{ far.faits || '—' }}</span></div></td>
                    <td><div class="rc-cell-editable"><span class="rc-cell-txt">{{ far.problemes || '—' }}</span></div></td>
                    <td><div class="rc-cell-editable"><span class="rc-cell-txt">{{ far.causes || '—' }}</span></div></td>
                    <td><div class="rc-cell-editable"><span class="rc-cell-txt">{{ far.impacts || '—' }}</span></div></td>
                    <td><div class="rc-cell-editable rc-cell-reco"><span class="rc-cell-txt">{{ far.recommandations || '—' }}</span></div></td>
                    <td>
                      <select class="rc-sel-sm" v-model="far.acceptation" :style="acceptStyle(far.acceptation)" @change="saveFarField(far, 'acceptation', far.acceptation)">
                        <option value="en_discussion">🔵 En discussion</option><option value="accepte">✅ Accepté</option><option value="non_accepte">❌ Non accepté</option>
                      </select>
                      <div class="rc-qual-checks">
                        <label class="rc-chk-lbl"><input type="checkbox" :checked="far.pertinence === 'pertinente'" @change="saveFarField(far, 'pertinence', $event.target.checked ? 'pertinente' : 'non_pertinente')" /> Pertinente</label>
                        <label class="rc-chk-lbl"><input type="checkbox" :checked="far.faisabilite === 'faisable'" @change="saveFarField(far, 'faisabilite', $event.target.checked ? 'faisable' : 'non_faisable')" /> Faisable</label>
                        <label class="rc-chk-lbl"><input type="checkbox" :checked="far.pratique === 'pratique'" @change="saveFarField(far, 'pratique', $event.target.checked ? 'pratique' : 'non_pratique')" /> Pratique</label>
                      </div>
                    </td>
                    <td><textarea class="rc-ta-cell" v-model="far.appreciation_audite" rows="2" :disabled="isLocked" @blur="saveFarField(far, 'appreciation_audite', far.appreciation_audite)"></textarea></td>
                    <td><input type="date" class="rc-inp-sm" v-model="far.date_echeance" :disabled="isLocked" @blur="saveFarField(far, 'date_echeance', far.date_echeance)" /></td>
                    <td><input class="rc-inp-sm" v-model="far.personne_responsable" :disabled="isLocked" @blur="saveFarField(far, 'personne_responsable', far.personne_responsable)" /></td>
                    <td><input class="rc-inp-sm" v-model="far.livrable" :disabled="isLocked" @blur="saveFarField(far, 'livrable', far.livrable)" /></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Section 5 : Modalités de suivi -->
          <div class="rc-section">
            <div class="rc-section__title">
              <span>5. Modalités de suivi</span>
              <button v-if="!isLocked" class="rc-add-btn" @click="rcForm.suivi_modalites.push({ date_rapport: '', delais_mise_oeuvre: '', modalites_suivi: '' })"><i class="ti ti-plus"></i> Ajouter</button>
            </div>
            <table class="rc-tbl">
              <thead>
                <tr><th style="width:40px">N°</th><th style="width:140px">Date du rapport</th><th style="width:180px">Délais de mise en œuvre</th><th>Modalités de suivi</th><th v-if="!isLocked"></th></tr>
              </thead>
              <tbody>
                <tr v-if="!rcForm.suivi_modalites?.length"><td colspan="5" class="rc-ec">Aucune modalité définie</td></tr>
                <tr v-for="(sm, i) in rcForm.suivi_modalites" :key="i">
                  <td class="rc-tc rc-muted">{{ i+1 }}</td>
                  <td><input type="date" class="rc-inp-sm" v-model="sm.date_rapport" :disabled="isLocked" /></td>
                  <td><input class="rc-inp-sm" v-model="sm.delais_mise_oeuvre" :disabled="isLocked" /></td>
                  <td><textarea class="rc-ta-sm" v-model="sm.modalites_suivi" rows="2" :disabled="isLocked"></textarea></td>
                  <td v-if="!isLocked"><button class="rc-del" @click="rcForm.suivi_modalites.splice(i,1)"><i class="ti ti-trash"></i></button></td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Section 6 : Conclusion -->
          <div class="rc-section">
            <div class="rc-section__title">Conclusion générale</div>
            <div class="rc-field"><textarea class="rc-ta" v-model="rcForm.conclusion_generale" rows="4" :disabled="isLocked"></textarea></div>
            <div class="rc-field"><label class="rc-lbl-sm">Observations finales</label><textarea class="rc-ta" v-model="rcForm.observations_finales" rows="3" :disabled="isLocked"></textarea></div>
          </div>

          <!-- Section 7 : Signatures -->
          <div class="rc-section">
            <div class="rc-section__title">7. Signatures</div>
            <div class="rc-signatures-grid">
              <div v-for="role in ['chef_mission','representant_audite','superviseur']" :key="role" class="rc-sig-card">
                <div class="rc-sig-card__title">{{ roleLabel(role) }}</div>
                <div class="rc-sig-fields">
                  <input class="rc-inp-sm" placeholder="Nom" v-model="sigForms[role].nom" @blur="saveSig(role)" :disabled="isLocked" />
                  <input class="rc-inp-sm" placeholder="Prénom" v-model="sigForms[role].prenom" @blur="saveSig(role)" :disabled="isLocked" />
                  <input class="rc-inp-sm" placeholder="Fonction" v-model="sigForms[role].fonction" @blur="saveSig(role)" :disabled="isLocked" />
                  <input type="date" class="rc-inp-sm" v-model="sigForms[role].date_signature" @change="saveSig(role)" :disabled="isLocked" />
                </div>
                <div class="rc-sig-box">
                  <canvas :ref="el => sigCanvases[role] = el" class="rc-sig-canvas" width="260" height="90"
                    v-if="!isLocked"
                    @mousedown="startDraw(role, $event)"
                    @mousemove="draw(role, $event)"
                    @mouseup="endDraw(role)"
                    @mouseleave="endDraw(role)"
                    @touchstart.prevent="startDraw(role, $event)"
                    @touchmove.prevent="draw(role, $event)"
                    @touchend="endDraw(role)"></canvas>
                  <img v-else-if="sigForms[role].signature_b64" :src="sigForms[role].signature_b64" class="rc-sig-img" alt="Signature" />
                  <div v-else class="rc-sig-placeholder">—</div>
                  <button v-if="!isLocked && sigForms[role].signature_b64" class="rc-sig-clear" @click="clearSig(role)"><i class="ti ti-eraser"></i> Effacer</button>
                </div>
              </div>
            </div>
            <div class="rc-confidential">Document confidentiel – Usage interne exclusivement</div>
          </div>
        </div><!-- /pv -->

        <!-- ◈ ONGLET FOCI ────────────────────────────────────── -->
        <div v-show="activeTab === 'foci'" class="rc-foci-view">
          <div class="rc-foci-entete">
            <div class="rc-foci-entete__left">
              <div class="rc-foci-logo"><i class="ti ti-building-bank"></i></div>
              <div class="rc-foci-meta">
                <div class="rc-foci-zone">ZONE D'EN-TÊTE — STRUCTURE AUDIT INTERNE</div>
                <div><span class="rc-mk">Code phase :</span><span class="rc-mv">{{ rcForm.code_mission || '—' }}</span><span class="rc-msep"></span><span class="rc-mk">Code mission :</span><span class="rc-mv">{{ props.mission?.code_mission || '—' }}</span></div>
                <div><span class="rc-mk">Date :</span><span class="rc-mv">{{ formatDate(new Date().toISOString()) }}</span></div>
              </div>
            </div>
            <div class="rc-foci-center">
              <div class="rc-foci-title">Feuille d'Observations Contrôle Interne</div>
              <div class="rc-foci-sub">FOCI — Synthèse des FRAP</div>
              <div class="rc-foci-note"><i class="ti ti-info-circle"></i> Édition automatique · Une seule FOCI par mission</div>
            </div>
            <div class="rc-foci-right">
              <div class="rc-mission-card">
                <div class="rc-mc-lbl">INTITULÉ DE LA MISSION</div>
                <div class="rc-mc-val">{{ rcForm.intitule_mission || missionLibelle || '—' }}</div>
                <div class="rc-mc-stats"><span class="rc-pill rc-pill--blue">{{ props.fraps?.length ?? 0 }} FRAP</span><span class="rc-pill rc-pill--green">{{ fociTotalRubriques }} Rubriques</span></div>
              </div>
            </div>
          </div>
          <div class="rc-foci-legend">
            <div class="rc-lc">N° FRAP</div><div class="rc-lc">Niveau CI</div><div class="rc-lc">Fait / Constat</div><div class="rc-lc">Problème</div>
            <div class="rc-lc">Causes</div><div class="rc-lc">Impacts</div><div class="rc-lc">Recommandation</div><div class="rc-lc">Comm. Audité</div>
            <div class="rc-lc">Points forts</div><div class="rc-lc">Date éch.</div><div class="rc-lc">Responsable</div><div class="rc-lc">Livrable</div>
          </div>
          <div v-if="!props.fociGrouped?.length" class="rc-foci-empty"><div class="rc-foci-empty__ico"><i class="ti ti-clipboard-off"></i></div><p>Aucune FRAP disponible.</p></div>
          <div v-for="(objCtrl, oci) in props.fociGrouped" :key="oci" class="rc-foci-obj-block">
            <div class="rc-foci-obj-banner"><span class="rc-foci-obj-lbl">Objectif de contrôle</span><span class="rc-foci-obj-txt">{{ objCtrl.objectif_controle }}</span></div>
            <div v-for="(rubr, ri) in objCtrl.rubriques" :key="ri" class="rc-foci-rubr-block">
              <div class="rc-foci-rubr-banner"><i class="ti ti-tag"></i> Rubrique : <strong>{{ rubr.rubrique }}</strong></div>
              <div v-for="(ssrubr, sri) in rubr.sous_rubriques" :key="sri">
                <div v-if="ssrubr.sous_rubrique" class="rc-foci-ssrubr-banner"><i class="ti ti-corner-down-right"></i> Sous-rubrique : {{ ssrubr.sous_rubrique }}</div>
                <div v-for="(frap, fi) in ssrubr.fraps" :key="frap.id" class="rc-foci-row" :class="fociNiveauRowClass(frap.niveau_controle_interne)">
                  <div class="rc-foci-cell rc-foci-cell--num"><button class="rc-foci-num-btn" @click="ouvrirFrapObs(frap)"><span class="rc-foci-num-badge">{{ frap.num_frap }}</span><i class="ti ti-external-link rc-ext-sm"></i></button></div>
                  <div class="rc-foci-cell rc-foci-cell--niv"><span class="rc-foci-niv-badge" :style="fociNiveauStyle(frap.niveau_controle_interne)">{{ fociNiveauLabel(frap.niveau_controle_interne) }}</span></div>
                  <div class="rc-foci-cell">{{ frap.fait_constats || '—' }}</div><div class="rc-foci-cell">{{ frap.probleme || '—' }}</div>
                  <div class="rc-foci-cell">{{ frap.causes || '—' }}</div><div class="rc-foci-cell">{{ frap.impacts || '—' }}</div>
                  <div class="rc-foci-cell rc-cell-reco">{{ frap.recommandation || '—' }}</div><div class="rc-foci-cell">{{ frap.commentaires_audite || '—' }}</div>
                  <div class="rc-foci-cell rc-cell-green">{{ frap.points_forts || '—' }}</div><div class="rc-foci-cell">{{ formatDate(frap.date_echeance) || '—' }}</div>
                  <div class="rc-foci-cell">{{ frap.personne_responsable || '—' }}</div><div class="rc-foci-cell">{{ frap.livrable || '—' }}</div>
                </div>
              </div>
            </div>
          </div>
          <div class="rc-foci-footnote"><i class="ti ti-info-circle"></i> Cliquez sur un N° FRAP pour ouvrir la feuille d'observation.</div>
        </div><!-- /foci -->

        <!-- ◈ ONGLET PLAN D'ACTION ───────────────────────────── -->
        <div v-show="activeTab === 'plan'" class="rc-plan-view">
          <div class="rc-plan-header">
            <div class="rc-plan-header__title">Plan d'Action</div>
            <div class="rc-plan-header__sub">Édition automatique — {{ props.fraps?.length ?? 0 }} recommandation(s)</div>
          </div>
          <div class="rc-plan-entete"><div><span class="rc-mk">Code phase :</span> {{ rcForm.code_mission || '—' }}</div><div><span class="rc-mk">Entité :</span> {{ rcForm.entite || '—' }}</div></div>
          <div class="rc-plan-legend">
            <div class="rc-plc">N° FRAP</div><div class="rc-plc">Solutions / Recommandation</div>
            <div class="rc-plc">Date d'échéance</div><div class="rc-plc">Personne responsable</div><div class="rc-plc">Livrables</div>
          </div>
          <div v-if="!props.planActionGrouped?.length" class="rc-plan-empty">Aucune recommandation disponible.</div>
          <div v-for="(objCtrl, oci) in props.planActionGrouped" :key="oci">
            <div class="rc-plan-obj-banner">{{ objCtrl.objectif_controle }}</div>
            <div v-for="(rubr, ri) in objCtrl.rubriques" :key="ri">
              <div class="rc-plan-rubr-banner">Rubrique : <strong>{{ rubr.rubrique }}</strong></div>
              <div v-for="(frap, fi) in rubr.fraps" :key="frap.id" class="rc-plan-row">
                <div class="rc-plan-cell rc-plan-cell--num"><button class="rc-foci-num-btn" @click="ouvrirFrapObs(frap)"><span class="rc-foci-num-badge">{{ frap.num_frap }}</span><i class="ti ti-external-link rc-ext-sm"></i></button></div>
                <div class="rc-plan-cell rc-plan-cell--reco">{{ frap.recommandation || '—' }}</div>
                <div class="rc-plan-cell">{{ formatDate(frap.date_echeance) || '—' }}</div>
                <div class="rc-plan-cell">{{ frap.personne_responsable || '—' }}</div>
                <div class="rc-plan-cell">{{ frap.livrable || '—' }}</div>
              </div>
            </div>
          </div>
          <div class="rc-plan-footnote">NB — Une seule FOCI pour regrouper toutes les FRAP-QCI d'une même mission.</div>
        </div><!-- /plan -->

      </div><!-- /rc-main -->

      <!-- MODAL FRAP -->
      <Teleport to="body">
        <Transition name="om-fade">
          <div v-if="frapModal.visible" class="rc-modal-overlay" @click.self="frapModal.visible = false">
            <div class="rc-frap-modal">
              <div class="rc-frap-modal__hd">
                <div class="rc-frap-modal__icon"><i class="ti ti-clipboard-text"></i></div>
                <div><h2>{{ frapModal.frap?.num_frap || 'FRAP' }}</h2><div class="rc-frap-sub">{{ frapModal.frap?.rubrique }}<span v-if="frapModal.frap?.sous_rubrique"> · {{ frapModal.frap.sous_rubrique }}</span></div></div>
                <div class="rc-frap-modal__right"><button class="rc-ib" @click="frapModal.visible = false"><i class="ti ti-x"></i></button></div>
              </div>
              <div class="rc-frap-modal__body" v-if="frapModal.frap">
                <div class="rc-fm-grid">
                  <div class="rc-fm-field rc-fm-field--full"><label class="rc-fm-lbl">Objectif de contrôle</label><div class="rc-fm-val rc-fm-val--obj">{{ frapModal.frap.objectif_controle || '—' }}</div></div>
                  <div class="rc-fm-field rc-fm-field--full"><label class="rc-fm-lbl">Fait / Constat</label><div class="rc-fm-val">{{ frapModal.frap.fait_constats || '—' }}</div></div>
                  <div class="rc-fm-field"><label class="rc-fm-lbl">Problème</label><div class="rc-fm-val">{{ frapModal.frap.probleme || '—' }}</div></div>
                  <div class="rc-fm-field"><label class="rc-fm-lbl">Causes</label><div class="rc-fm-val">{{ frapModal.frap.causes || '—' }}</div></div>
                  <div class="rc-fm-field"><label class="rc-fm-lbl">Impacts</label><div class="rc-fm-val">{{ frapModal.frap.impacts || '—' }}</div></div>
                  <div class="rc-fm-field"><label class="rc-fm-lbl">Recommandation</label><div class="rc-fm-val rc-fm-val--reco">{{ frapModal.frap.recommandation || '—' }}</div></div>
                  <div class="rc-fm-field"><label class="rc-fm-lbl">Commentaires de l'audité</label><div class="rc-fm-val">{{ frapModal.frap.commentaires_audite || '—' }}</div></div>
                  <div class="rc-fm-field" v-if="frapModal.frap.points_forts"><label class="rc-fm-lbl rc-fm-lbl--green">Points forts</label><div class="rc-fm-val rc-fm-val--green">{{ frapModal.frap.points_forts }}</div></div>
                  <div class="rc-fm-field"><label class="rc-fm-lbl">Date d'échéance</label><div class="rc-fm-val">{{ formatDate(frapModal.frap.date_echeance) || '—' }}</div></div>
                  <div class="rc-fm-field"><label class="rc-fm-lbl">Responsable</label><div class="rc-fm-val">{{ frapModal.frap.personne_responsable || '—' }}</div></div>
                </div>
              </div>
              <div class="rc-frap-modal__ft"><button class="rc-btn rc-btn--ghost" @click="frapModal.visible = false">Fermer</button></div>
            </div>
          </div>
        </Transition>
      </Teleport>

      <!-- TOAST -->
      <Teleport to="body">
        <Transition name="toast-pop">
          <div v-if="toast.show" class="rc-toast" :class="'rc-toast--' + toast.type">
            <i :class="toast.type === 'success' ? 'ti ti-circle-check' : 'ti ti-alert-circle'"></i> {{ toast.msg }}
            <button class="rc-toast__x" @click="toast.show = false"><i class="ti ti-x"></i></button>
          </div>
        </Transition>
      </Teleport>
    </div>
  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, nextTick, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'

// ─── SLIDES COMPONENTS ─────────────────────────────────────
const SlideTitle = { props: ['reunionData','missionLibelle'], template: `<div class="slide-title-card"><div class="stc-icon"><i class="ti ti-presentation"></i></div><div class="stc-title">RÉUNION DE CLÔTURE</div><div class="stc-sub">Phase de Vérification — Audit Interne</div><div class="stc-mission">{{ missionLibelle || reunionData?.intitule_mission }}</div><div class="stc-meta"><span v-if="reunionData?.date_reunion">📅 {{ reunionData.date_reunion }}</span><span v-if="reunionData?.lieu">📍 {{ reunionData.lieu }}</span></div><div class="stc-norme">Norme MPA 2400 – 2410-1</div></div>` }
const SlideOrdreJour = { props: ['reunionData'], template: `<div class="slide-card"><div class="slide-card__title"><i class="ti ti-list"></i> Ordre du jour</div><ol class="slide-list"><li v-for="oj in reunionData?.ordre_jour ?? []" :key="oj.id">{{ oj.libelle }}</li></ol></div>` }
const SlidePointsForts = { props: ['reunionData'], template: `<div class="slide-card"><div class="slide-card__title"><i class="ti ti-star"></i> Points forts</div><div v-if="!reunionData?.points_forts?.length" class="slide-empty">Aucun point fort.</div><ul class="slide-list slide-list--check"><li v-for="pf in reunionData?.points_forts ?? []" :key="pf.id">✅ {{ pf.libelle }}</li></ul></div>` }
const SlideFar = { props: ['reunionData'], template: `<div class="slide-card slide-card--far"><div class="slide-card__title"><i class="ti ti-clipboard-check"></i> Validation des observations (FAR)</div><div class="slide-far-list"><div v-for="far in reunionData?.far_validations ?? []" :key="far.id" class="slide-far-item" :class="far.acceptation === 'accepte' ? 'sfi--ok' : far.acceptation === 'non_accepte' ? 'sfi--ko' : 'sfi--pending'"><div class="sfi-num">{{ far.num_far }}</div><div class="sfi-body"><p class="sfi-fait">{{ far.faits || '—' }}</p><p class="sfi-reco">→ {{ far.recommandations || '—' }}</p></div><div class="sfi-status">{{ far.acceptation === 'accepte' ? '✅ Accepté' : far.acceptation === 'non_accepte' ? '❌ Non accepté' : '🔵 En discussion' }}</div></div></div></div>` }
const SlidePlanAction = { props: ['fraps'], template: `<div class="slide-card"><div class="slide-card__title"><i class="ti ti-checklist"></i> Plan d'Action</div><table class="slide-plan-tbl"><thead><tr><th>N° FRAP</th><th>Recommandation</th><th>Échéance</th><th>Responsable</th><th>Livrable</th></tr></thead><tbody><tr v-for="frap in fraps ?? []" :key="frap.id"><td><strong>{{ frap.num_frap }}</strong></td><td>{{ frap.recommandation || '—' }}</td><td>{{ frap.date_echeance || '—' }}</td><td>{{ frap.personne_responsable || '—' }}</td><td>{{ frap.livrable || '—' }}</td></tr></tbody></table></div>` }
const SlideIAGlobale = { props: ['iaGlobales','scoreIaMoyen'], template: `<div class="slide-card"><div class="slide-card__title"><i class="ti ti-robot"></i> Synthèse IA</div><div v-if="!iaGlobales?.length" class="slide-empty">Aucune synthèse IA.</div><div v-else><div class="slide-ia-score-row" v-if="scoreIaMoyen !== null"><span class="slide-ia-score-badge" :style="{ background: scoreIaMoyen >= 7 ? '#10b981' : scoreIaMoyen >= 5 ? '#f59e0b' : '#dc2626' }">Score moyen : {{ scoreIaMoyen }}/10</span></div><div v-for="ia in iaGlobales ?? []" :key="ia.id" class="slide-ia-card"><div class="slide-ia-card__head"><span class="slide-ia-score" :style="{ background: (ia.score_global??0) >= 7 ? '#10b981' : (ia.score_global??0) >= 5 ? '#f59e0b' : '#dc2626' }">{{ ia.score_global ?? '—' }}/10</span><span class="slide-ia-fiab">Fiabilité : {{ ia.fiabilite ?? '—' }}</span></div><p class="slide-ia-concl">{{ ia.conclusion }}</p><div class="slide-ia-lists"><div v-if="ia.risques_majeurs?.length"><div class="slide-ia-sec">⚠️ Risques</div><ul><li v-for="r in ia.risques_majeurs" :key="r">{{ r }}</li></ul></div><div v-if="ia.recommandations?.length"><div class="slide-ia-sec">💡 Recommandations</div><ul><li v-for="r in ia.recommandations" :key="r">{{ r }}</li></ul></div></div></div></div></div>` }
const SlideConclusion = { props: ['reunionData'], template: `<div class="slide-card slide-card--conclusion"><div class="slide-card__title"><i class="ti ti-flag"></i> Conclusion générale</div><p class="slide-conclusion-txt">{{ reunionData?.conclusion_generale || 'Aucune conclusion rédigée.' }}</p><div v-if="reunionData?.observations_finales" class="slide-obs-fin"><div class="slide-obs-fin__lbl">Observations finales</div><p>{{ reunionData.observations_finales }}</p></div><div class="slide-merci">Merci pour votre participation</div></div>` }

// ─── PROPS ──────────────────────────────────────────────────────
const props = withDefaults(defineProps<{
  reunion?: any
  mission?: any
  fraps?: any[]
  fociGrouped?: any[]
  planActionGrouped?: any[]
  canManage?: boolean
  isNew?: boolean
  backUrl?: string
  urlUpdate?: string
  urlSoumettre?: string
  urlValider?: string
  urlFarUpdate?: string
  urlFarStore?: string
  urlSyncFraps?: string
  urlSignature?: string
  equipe?: any[]
  fichesTest?: any[]
  iaGlobales?: any[]
  programmeData?: any
  rciLignes?: any[]
  statsNiveaux?: Record<string, number>
  totalFraps?: number
  scoreIaMoyen?: number | null
  logs?: any[]
}>(), {
  equipe: () => [],
  fichesTest: () => [],
  iaGlobales: () => [],
  rciLignes: () => [],
  statsNiveaux: () => ({}),
  totalFraps: 0,
  scoreIaMoyen: null,
  fraps: () => [],
  fociGrouped: () => [],
  planActionGrouped: () => [],
  canManage: false,
  logs: () => []
})

// ─── TABS ───────────────────────────────────────────────────────
const tabs = [
  { id: 'pv',   label: 'PV de Clôture',   icon: 'ti-file-text' },
  { id: 'foci', label: 'FOCI',             icon: 'ti-table' },
  { id: 'plan', label: "Plan d'Action",    icon: 'ti-checklist' },
]
const activeTab = ref('pv')

// ─── SLIDES ─────────────────────────────────────────────────────
const slidesMode = ref(false)
const slideIndex = ref(0)
const slideDir   = ref<'next'|'prev'>('next')
const stageRef   = ref<HTMLElement | null>(null)
const slides = computed(() => [
  { component: SlideTitle,      props: { missionLibelle: missionLibelle.value } },
  { component: SlideOrdreJour,  props: {} },
  { component: SlidePointsForts,props: {} },
  { component: SlideFar,        props: {} },
  { component: SlidePlanAction,  props: { fraps: props.fraps } },
  { component: SlideIAGlobale,   props: { iaGlobales: props.iaGlobales, scoreIaMoyen: props.scoreIaMoyen } },
  { component: SlideConclusion,  props: {} },
])
function ouvrirSlides() { slideIndex.value = 0; slidesMode.value = true; nextTick(() => stageRef.value?.focus()) }
function onSlideKey(e: KeyboardEvent) {
  if (e.key === 'ArrowRight' || e.key === 'ArrowDown') { if (slideIndex.value < slides.value.length - 1) { slideDir.value = 'next'; slideIndex.value++ } }
  else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') { if (slideIndex.value > 0) { slideDir.value = 'prev'; slideIndex.value-- } }
  else if (e.key === 'Escape') slidesMode.value = false
}

// ─── STATE ──────────────────────────────────────────────────────
const rcForm = reactive<any>({
  entite: '', intitule_mission: '', code_mission: '', norme_mpa: 'MPA 2400 – 2410-1',
  date_reunion: '', heure_debut: '', heure_fin: '', lieu: '', preside_par: '', secretaire_seance: '',
  conclusion_generale: '', observations_finales: '', date_rapport: '', delais_plan_action: '', modalites_suivi: '',
  ordre_jour: [], points_forts: [], far_validations: [], suivi_modalites: [], participants: [],
  statut: 'draft', validation_note: ''
})
const sigForms = reactive<Record<string, any>>({
  chef_mission: { nom:'', prenom:'', fonction:'', date_signature:'', signature_b64:'' },
  representant_audite: { nom:'', prenom:'', fonction:'', date_signature:'', signature_b64:'' },
  superviseur: { nom:'', prenom:'', fonction:'', date_signature:'', signature_b64:'' }
})
const sigCanvases = reactive<Record<string, HTMLCanvasElement | null>>({
  chef_mission: null, representant_audite: null, superviseur: null
})
const sigDrawing = reactive<Record<string, boolean>>({
  chef_mission: false, representant_audite: false, superviseur: false
})
const sigLastPos = reactive<Record<string, { x: number; y: number }>>({
  chef_mission: { x:0, y:0 }, representant_audite:{ x:0, y:0 }, superviseur:{ x:0, y:0 }
})
const saving = ref(false)
const syncing = ref(false)
const toast = ref({ show: false, type: 'success', msg: '' })
const frapModal = reactive<{ visible: boolean; frap: any | null }>({ visible: false, frap: null })
let _tt: ReturnType<typeof setTimeout> | null = null

// ─── COMPUTED ───────────────────────────────────────────────────
const isLocked = computed(() => rcForm.statut === 'validated' || (rcForm.statut === 'in_review' && !props.canManage))
const missionLibelle = computed(() => props.mission?.libelle ?? rcForm.intitule_mission ?? '—')
const fociTotalRubriques = computed(() => new Set((props.fraps ?? []).map(f => f.rubrique || '')).size)
const statsGlobalesDisplay = computed(() => {
  const s = props.statsNiveaux ?? {}
  return [
    { key: 'critique', label: '⛔ Critique', count: s['critique']??0, color: '#9d174d', bg: '#fce7f3' },
    { key: 'insuffisant', label: '🔴 Insuffisant', count: s['insuffisant']??0, color: '#dc2626', bg: '#fee2e2' },
    { key: 'a_ameliorer', label: '🔶 À améliorer', count: s['a_ameliorer']??0, color: '#d97706', bg: '#fef3c7' },
    { key: 'satisfaisant', label: '✅ Satisfaisant', count: s['satisfaisant']??0, color: '#065f46', bg: '#d1fae5' },
    { key: 'conforme', label: '✅ Conforme', count: s['conforme']??0, color: '#065f46', bg: '#d1fae5' }
  ].filter(x => x.count > 0)
})
const topIaGlobal = computed(() => props.iaGlobales?.length ? props.iaGlobales.reduce((best,cur) => (cur.score_global??0) > (best.score_global??0) ? cur : best, props.iaGlobales[0]) : null)

// ─── HELPERS ────────────────────────────────────────────────────
function vstLbl(s: string) { return { draft: 'Brouillon', in_review: 'En attente', validated: 'Validé ✓' }[s] ?? s }
function roleLabel(role: string) { return { chef_mission: 'Chef de Mission', representant_audite: "Représentant des Audités", superviseur: 'Superviseur / Resp. Audit' }[role] ?? role }
function formatDate(d?: string | null) { return d ? new Date(d).toLocaleDateString('fr-FR') : '' }
function csrf() { return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '' }
function showToast(t: string, m: string, dur = 4000) { if (_tt) clearTimeout(_tt); toast.value = { show:true, type:t, msg:m }; _tt = setTimeout(() => toast.value.show=false, dur) }
function annuler() { if (props.backUrl) router.visit(props.backUrl) }
function syncParticipantsDepuisEquipe() {
  if (!props.equipe?.length) return
  const existing = rcForm.participants.map(p => p.nom?.toLowerCase())
  props.equipe.forEach(a => {
    if (!existing.includes(a.full_name?.toLowerCase())) rcForm.participants.push({ nom: a.full_name, fonction: a.role_label, entite: 'Audit Interne', present: true })
  })
  showToast('success', `${props.equipe.length} membre(s) synchronisé(s).`)
}
function farRowClass(acc: string) { return acc === 'accepte' ? 'rc-far-row--ok' : acc === 'non_accepte' ? 'rc-far-row--ko' : '' }
function acceptStyle(v: string) {
  if(v==='accepte') return { background:'#d1fae5', color:'#065f46', borderColor:'#a7f3d0' }
  if(v==='non_accepte') return { background:'#fee2e2', color:'#dc2626', borderColor:'#fca5a5' }
  return { background:'#dbeafe', color:'#1d4ed8', borderColor:'#bfdbfe' }
}
const fociNiveauMap: Record<string,any> = {
  'satisfaisant':{label:'✅ Satisfaisant',bg:'#d1fae5',color:'#065f46'}, 'a_ameliorer':{label:'🔶 À améliorer',bg:'#fef3c7',color:'#92400e'},
  'insuffisant':{label:'🔴 Insuffisant',bg:'#fee2e2',color:'#dc2626'}, 'critique':{label:'⛔ Critique',bg:'#fce7f3',color:'#9d174d'},
  'conforme':{label:'✅ Conforme',bg:'#d1fae5',color:'#065f46'}
}
function fociNiveauLabel(v?: string) { return fociNiveauMap[v?.toLowerCase()]?.label || v || '—' }
function fociNiveauStyle(v?: string) { const c = fociNiveauMap[v?.toLowerCase()]; return c ? { background:c.bg, color:c.color } : { background:'#f1f5f9', color:'#475569' } }
function fociNiveauRowClass(v?: string) { const lv = v?.toLowerCase(); if(lv==='critique') return 'rc-foci-row--critique'; if(lv==='insuffisant') return 'rc-foci-row--insuffisant'; if(lv==='a_ameliorer') return 'rc-foci-row--ameliorer'; if(['satisfaisant','conforme'].includes(lv)) return 'rc-foci-row--ok'; return '' }

// ─── ACTIONS ────────────────────────────────────────────────────
async function sauvegarder() {
  if(!props.urlUpdate) return
  saving.value = true
  try {
    const res = await fetch(props.urlUpdate, { method:'PUT', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf(),Accept:'application/json'}, body:JSON.stringify({ ...rcForm, ordre_jour:rcForm.ordre_jour, points_forts:rcForm.points_forts, suivi_modalites:rcForm.suivi_modalites, participants:rcForm.participants }) })
    const d = await res.json()
    if(d.success) { if(d.reunion?.statut) rcForm.statut = d.reunion.statut; showToast('success','PV enregistré.') }
    else showToast('error', d.error || 'Erreur')
  } catch(e:any) { showToast('error', e.message) } finally { saving.value = false }
}
async function soumettre() {
  const res = await fetch(props.urlSoumettre, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf(),Accept:'application/json'}, body:JSON.stringify({}) })
  const d = await res.json()
  if(d.success) { rcForm.statut = 'in_review'; showToast('success','Soumis pour validation.') }
  else showToast('error', d.error || 'Erreur')
}
async function valider(action: string, note?: string) {
  const res = await fetch(props.urlValider, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf(),Accept:'application/json'}, body:JSON.stringify({ action, note }) })
  const d = await res.json()
  if(d.success) { rcForm.statut = d.statut; showToast('success', action === 'validate' ? 'Validé ✓' : 'Rejeté.') }
}
function promptReject() { const n = prompt('Motif du rejet :',''); if(n?.trim()) valider('reject', n.trim()) }
async function saveFarField(far: any, field: string, value: any) {
  if(!props.urlFarUpdate) return
  const url = props.urlFarUpdate.replace(':farId', far.id)
  await fetch(url, { method:'PUT', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf(),Accept:'application/json'}, body:JSON.stringify({ [field]: value }) })
}
async function ajouterFarManuelle() {
  if(!props.urlFarStore) return
  const res = await fetch(props.urlFarStore, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf(),Accept:'application/json'}, body:JSON.stringify({ faits:'', recommandations:'' }) })
  const d = await res.json()
  if(d.success) { rcForm.far_validations.push(d.far); showToast('success','FAR ajoutée.') }
}
async function syncFraps() {
  if(!props.urlSyncFraps) return
  syncing.value = true
  try {
    const res = await fetch(props.urlSyncFraps, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf(),Accept:'application/json'}, body:JSON.stringify({}) })
    const d = await res.json()
    if(d.success) { rcForm.far_validations = d.far_list; showToast('success',`Synchronisé — ${d.far_list.length} FAR(s).`) }
  } catch(e:any) { showToast('error', e.message) } finally { syncing.value = false }
}
function ouvrirFrapObs(frap: any) { frapModal.frap = frap; frapModal.visible = true }

// Signatures
function _getCanvas(role: string) { return sigCanvases[role] }
function _getCtx(role: string) { return _getCanvas(role)?.getContext('2d') }
function _canvasPos(role: string, e: MouseEvent | TouchEvent) {
  const canvas = _getCanvas(role); if(!canvas) return { x:0, y:0 }
  const rect = canvas.getBoundingClientRect()
  const touch = e instanceof TouchEvent ? e.touches[0] : e
  return { x: touch.clientX - rect.left, y: touch.clientY - rect.top }
}
function startDraw(role: string, e: MouseEvent | TouchEvent) { sigDrawing[role] = true; sigLastPos[role] = _canvasPos(role, e) }
function draw(role: string, e: MouseEvent | TouchEvent) {
  if(!sigDrawing[role]) return
  const ctx = _getCtx(role); if(!ctx) return
  const pos = _canvasPos(role, e)
  ctx.beginPath(); ctx.moveTo(sigLastPos[role].x, sigLastPos[role].y); ctx.lineTo(pos.x, pos.y)
  ctx.strokeStyle = '#0f172a'; ctx.lineWidth = 2; ctx.lineCap = 'round'
  ctx.stroke(); sigLastPos[role] = pos
}
function endDraw(role: string) {
  if(!sigDrawing[role]) return
  sigDrawing[role] = false
  const canvas = _getCanvas(role)
  if(canvas) { sigForms[role].signature_b64 = canvas.toDataURL('image/png'); saveSig(role) }
}
function clearSig(role: string) {
  const ctx = _getCtx(role); if(!ctx) return
  const canvas = _getCanvas(role)!
  ctx.clearRect(0,0, canvas.width, canvas.height)
  sigForms[role].signature_b64 = ''; saveSig(role)
}
async function saveSig(role: string) {
  if(!props.urlSignature) return
  await fetch(props.urlSignature, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf(),Accept:'application/json'}, body:JSON.stringify({ role, ...sigForms[role] }) })
}

// Initialisation
onMounted(() => {
  if(props.reunion) {
    Object.assign(rcForm, {
      entite: props.reunion.entite ?? '', intitule_mission: props.reunion.intitule_mission ?? '', code_mission: props.reunion.code_mission ?? '',
      norme_mpa: props.reunion.norme_mpa ?? 'MPA 2400 – 2410-1', date_reunion: props.reunion.date_reunion ?? '', heure_debut: props.reunion.heure_debut ?? '',
      heure_fin: props.reunion.heure_fin ?? '', lieu: props.reunion.lieu ?? '', preside_par: props.reunion.preside_par ?? '',
      secretaire_seance: props.reunion.secretaire_seance ?? '', conclusion_generale: props.reunion.conclusion_generale ?? '',
      observations_finales: props.reunion.observations_finales ?? '', date_rapport: props.reunion.date_rapport ?? '',
      delais_plan_action: props.reunion.delais_plan_action ?? '', modalites_suivi: props.reunion.modalites_suivi ?? '',
      statut: props.reunion.statut ?? 'draft', validation_note: props.reunion.validation_note ?? '',
      ordre_jour: props.reunion.ordre_jour ?? [], points_forts: props.reunion.points_forts ?? [],
      far_validations: props.reunion.far_validations ?? [], suivi_modalites: props.reunion.suivi_modalites ?? [],
      participants: props.reunion.participants ?? []
    })
    if(!rcForm.participants.length && props.equipe?.length) {
      rcForm.participants = props.equipe.map(a => ({ nom: a.full_name, fonction: a.role_label, entite: 'Audit Interne', present: true }))
    }
    if(props.reunion.signatures) {
      Object.entries(props.reunion.signatures).forEach(([role, sig]) => { if(sigForms[role]) Object.assign(sigForms[role], sig) })
    }
  }
})
onBeforeUnmount(() => { if(_tt) clearTimeout(_tt) })
</script>



<style scoped>
/* ════ VARIABLES ══════════════════════════════════════════════ */
:root {
  --navy: #0f172a; --blue: #1e40af; --green: #065f46;
  --border: #e2e8f0; --bg: #f1f5f9; --sh: 0 1px 3px rgba(15,23,42,.07);
}
.rc-shell {
  display: flex; flex-direction: column; height: 100vh;
  background: var(--bg); font-family: 'Segoe UI', system-ui, sans-serif;
}

/* ── TOPBAR ─────────────────────────────────────────────────── */
.rc-topbar {
  display: flex; justify-content: space-between; align-items: center;
  padding: .5rem 1rem; background: white; border-bottom: 1px solid var(--border); flex-shrink: 0;
}
.rc-topbar__left,
.rc-topbar__right { display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; }
.rc-code { background: var(--navy); color: white; padding: .2rem .6rem; border-radius: 4px; font-size: .7rem; font-weight: 600; }
.rc-sdot { width: 8px; height: 8px; border-radius: 50%; }
.sd--draft { background: #94a3b8; } .sd--in_review { background: #2563eb; } .sd--validated { background: #16a34a; }
.rc-vstatus { font-size: .7rem; color: #475569; }
.rc-div { width: 1px; height: 20px; background: var(--border); }
.rc-icon-muted { font-size: .8rem; color: #94a3b8; }
.rc-mission-lbl { font-size: .75rem; color: #475569; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* Tabs */
.rc-tabs { display: flex; background: #f1f5f9; border-radius: 8px; padding: 2px; gap: 2px; }
.rc-tab { background: none; border: none; padding: .3rem .75rem; border-radius: 6px; font-size: .72rem; cursor: pointer; color: #64748b; display: inline-flex; align-items: center; gap: .25rem; transition: all .15s; }
.rc-tab--active { background: white; color: #1e40af; font-weight: 600; box-shadow: var(--sh); }

/* Boutons */
.rc-btn { display: inline-flex; align-items: center; gap: .3rem; padding: .3rem .75rem; border-radius: 6px; font-size: .75rem; font-weight: 500; border: 1px solid transparent; cursor: pointer; transition: all .15s; }
.rc-btn:disabled { opacity: .5; cursor: not-allowed; }
.rc-btn--ghost { background: transparent; border-color: #cbd5e1; }
.rc-btn--ghost:hover { background: #f1f5f9; }
.rc-btn--save { background: var(--navy); color: white; }
.rc-btn--submit { background: #2563eb; color: white; }
.rc-btn--validate { background: #10b981; color: white; }
.rc-btn--reject { background: #dc2626; color: white; }
.rc-btn--slides { background: linear-gradient(135deg, #6d28d9, #7c3aed); color: white; border: none; }
.rc-btn--slides-on { box-shadow: 0 0 0 2px #c4b5fd; }
.rc-ib { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: transparent; border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer; }

/* Banners */
.rc-banner { display: flex; align-items: center; gap: .5rem; padding: .3rem 1rem; font-size: .75rem; flex-shrink: 0; }
.rc-banner--ok { background: #d1fae5; color: #065f46; } .rc-banner--review { background: #dbeafe; color: #1d4ed8; } .rc-banner--rejected { background: #fee2e2; color: #dc2626; }

/* ── MAIN ───────────────────────────────────────────────────── */
.rc-main { flex: 1; overflow: auto; padding: 1rem; }

/* ── PV DOC ─────────────────────────────────────────────────── */
.rc-pv-doc { max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: 1rem; }
.rc-doc-header { background: linear-gradient(135deg, #1e3a5f, #2c5282); color: white; border-radius: 10px; padding: 1.25rem 1.5rem; text-align: center; }
.rc-doc-header__title { font-size: 1rem; font-weight: 800; letter-spacing: .03em; }
.rc-doc-header__sub { font-size: .72rem; color: rgba(255,255,255,.7); margin-top: .25rem; }

.rc-section { background: white; border-radius: 10px; border: 1px solid var(--border); overflow: hidden; box-shadow: var(--sh); }
.rc-section__title { display: flex; align-items: center; justify-content: space-between; padding: .6rem 1rem; background: linear-gradient(135deg, #eff6ff, #f0fdf4); border-bottom: 1px solid var(--border); font-size: .82rem; font-weight: 700; color: #1e3a5f; }

.rc-grid { display: grid; gap: .65rem; padding: .85rem 1rem; }
.rc-grid--3 { grid-template-columns: 1fr 1fr 1fr; }
.rc-field { display: flex; flex-direction: column; gap: .25rem; }
.rc-field--full { grid-column: 1 / -1; }
.rc-field label { font-size: .62rem; font-weight: 700; color: #1e40af; text-transform: uppercase; letter-spacing: .04em; }
.rc-inp { border: 1px solid #ddd6fe; border-radius: 6px; padding: 6px 10px; font-size: .78rem; font-family: inherit; width: 100%; box-sizing: border-box; outline: none; }
.rc-inp:focus { border-color: #1e40af; box-shadow: 0 0 0 2px rgba(30,64,175,.12); }
.rc-inp:disabled { background: #f8fafc; color: #64748b; }
.rc-ta { width: 100%; border: 1px solid #ddd6fe; border-radius: 6px; padding: 6px 10px; font-size: .78rem; font-family: inherit; resize: vertical; box-sizing: border-box; outline: none; }
.rc-lbl-sm { font-size: .62rem; font-weight: 700; color: #1e40af; text-transform: uppercase; letter-spacing: .04em; }

/* Tables */
.rc-tbl { width: 100%; border-collapse: collapse; font-size: .72rem; }
.rc-tbl th,
.rc-tbl td { padding: .35rem .6rem; border-bottom: 1px solid #f1f5f9; border-right: 1px solid #f1f5f9; text-align: left; vertical-align: middle; }
.rc-tbl th { background: #f8fafc; font-weight: 700; color: #475569; border-bottom: 1px solid var(--border); }
.rc-tc { text-align: center; }
.rc-muted { color: #94a3b8; font-weight: 700; font-size: .62rem; }
.rc-ec { text-align: center; color: #94a3b8; padding: 1rem; font-style: italic; font-size: .7rem; }
.rc-ta-sm { width: 100%; border: 1px solid #e2e8f0; border-radius: 4px; padding: .2rem .4rem; font-size: .7rem; font-family: inherit; box-sizing: border-box; }
.rc-inp-sm { width: 100%; border: 1px solid #e2e8f0; border-radius: 4px; padding: .2rem .4rem; font-size: .7rem; font-family: inherit; box-sizing: border-box; }
.rc-del { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; background: #fee2e2; border: 1px solid #fecaca; color: #dc2626; border-radius: 4px; cursor: pointer; }
.rc-add-btn { display: inline-flex; align-items: center; gap: .2rem; padding: .2rem .6rem; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; border-radius: 6px; font-size: .65rem; cursor: pointer; }
.rc-sync-btn { display: inline-flex; align-items: center; gap: .2rem; padding: .2rem .6rem; background: #fef3c7; border: 1px solid #fcd34d; color: #92400e; border-radius: 6px; font-size: .65rem; cursor: pointer; }

/* FAR TABLE */
.rc-note-mpa { font-size: .7rem; color: #64748b; padding: .4rem 1rem; border-bottom: 1px solid #f1f5f9; font-style: italic; margin: 0; }
.rc-far-table-wrap { overflow-x: auto; }
.rc-far-tbl { width: 100%; border-collapse: collapse; font-size: .68rem; }
.rc-far-tbl th,
.rc-far-tbl td { padding: .3rem .4rem; border-bottom: 1px solid #f1f5f9; border-right: 1px solid #f1f5f9; vertical-align: top; }
.rc-far-tbl th { background: #1e3a5f; color: white; font-weight: 600; border-bottom: 1px solid #2c5282; font-size: .6rem; text-transform: uppercase; }
.rc-far-row--ok { border-left: 3px solid #10b981; }
.rc-far-row--ko { border-left: 3px solid #dc2626; }
.rc-far-num-btn { display: inline-flex; align-items: center; gap: .2rem; background: none; border: none; cursor: pointer; padding: .1rem 0; }
.rc-far-num-badge { background: #1e3a5f; color: white; padding: .1rem .35rem; border-radius: 4px; font-size: .58rem; font-weight: 700; }
.rc-ext { font-size: .55rem; color: #94a3b8; }
.rc-cell-editable { width: 100%; }
.rc-cell-txt { font-size: .68rem; color: #334155; line-height: 1.4; white-space: pre-wrap; word-break: break-word; }
.rc-cell-reco .rc-cell-txt { color: #1e40af; font-weight: 500; }
.rc-cell-green { color: #065f46; }
.rc-accept-cell { display: flex; flex-direction: column; gap: .25rem; }
.rc-sel-sm { border-radius: 6px; border: 1px solid #e2e8f0; padding: .2rem .3rem; font-size: .65rem; width: 100%; font-weight: 600; cursor: pointer; }
.rc-qual-checks { display: flex; flex-direction: column; gap: .15rem; margin-top: .2rem; }
.rc-chk-lbl { display: flex; align-items: center; gap: .25rem; font-size: .62rem; color: #475569; cursor: pointer; }
.rc-ta-cell { width: 100%; border: 1px solid #e2e8f0; border-radius: 4px; padding: .2rem .4rem; font-size: .66rem; font-family: inherit; resize: vertical; box-sizing: border-box; }

/* Signatures */
.rc-signatures-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; padding: 1rem; }
.rc-sig-card { border: 1px solid var(--border); border-radius: 8px; overflow: hidden; }
.rc-sig-card__title { background: #1e3a5f; color: white; padding: .4rem .75rem; font-size: .72rem; font-weight: 600; text-align: center; }
.rc-sig-fields { display: flex; flex-direction: column; gap: .3rem; padding: .5rem .75rem; }
.rc-sig-box { padding: .3rem .75rem; border-top: 1px dashed var(--border); position: relative; }
.rc-sig-canvas { border: 1px solid #e2e8f0; border-radius: 4px; width: 100%; cursor: crosshair; background: #fafafa; }
.rc-sig-img { width: 100%; border: 1px solid #e2e8f0; border-radius: 4px; }
.rc-sig-placeholder { text-align: center; color: #94a3b8; padding: 1.5rem; font-size: .8rem; }
.rc-sig-clear { position: absolute; top: .5rem; right: .75rem; background: #fee2e2; border: none; color: #dc2626; padding: .15rem .4rem; border-radius: 4px; font-size: .6rem; cursor: pointer; display: inline-flex; align-items: center; gap: .2rem; }
.rc-sig-label { text-align: center; font-size: .6rem; color: #94a3b8; padding: .25rem; border-top: 1px dashed var(--border); }
.rc-confidential { text-align: center; font-size: .65rem; color: #94a3b8; font-style: italic; padding: .5rem 1rem; border-top: 1px solid var(--border); }

/* ── FOCI VIEW ──────────────────────────────────────────────── */
.rc-foci-view { display: flex; flex-direction: column; gap: 0; background: white; border-radius: 10px; overflow: hidden; box-shadow: var(--sh); }
.rc-foci-entete { display: grid; grid-template-columns: 1fr 1.4fr 1fr; border-bottom: 2px solid #1e3a5f; }
.rc-foci-entete__left { display: flex; align-items: flex-start; gap: .75rem; padding: .85rem; background: #f8fafc; border-right: 1px solid var(--border); }
.rc-foci-logo { width: 44px; height: 44px; background: #1e3a5f; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.3rem; flex-shrink: 0; }
.rc-foci-meta { font-size: .72rem; }
.rc-foci-zone { font-weight: 700; color: #1e3a5f; font-size: .62rem; text-transform: uppercase; margin-bottom: .25rem; }
.rc-mk { color: #64748b; font-weight: 600; font-size: .68rem; }
.rc-mv { color: #0f172a; font-weight: 500; font-size: .68rem; }
.rc-msep { display: inline-block; width: 1px; height: 12px; background: var(--border); margin: 0 .35rem; vertical-align: middle; }
.rc-foci-center { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1rem; text-align: center; background: linear-gradient(135deg, #1e3a5f, #2c5282); }
.rc-foci-title { font-size: .95rem; font-weight: 800; color: white; }
.rc-foci-sub { font-size: .68rem; color: rgba(255,255,255,.75); margin-top: .2rem; text-transform: uppercase; letter-spacing: .07em; }
.rc-foci-note { display: flex; align-items: center; gap: .3rem; font-size: .6rem; color: rgba(255,255,255,.5); margin-top: .4rem; font-style: italic; }
.rc-foci-right { padding: .85rem; background: #f8fafc; border-left: 1px solid var(--border); }
.rc-mission-card { display: flex; flex-direction: column; gap: .3rem; }
.rc-mc-lbl { font-size: .6rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; }
.rc-mc-val { font-size: .78rem; font-weight: 600; color: #1e293b; flex: 1; line-height: 1.4; }
.rc-mc-stats { display: flex; gap: .3rem; }
.rc-pill { padding: .1rem .5rem; border-radius: 20px; font-size: .6rem; font-weight: 700; }
.rc-pill--blue { background: #dbeafe; color: #1d4ed8; }
.rc-pill--green { background: #d1fae5; color: #065f46; }

.rc-foci-legend { display: grid; grid-template-columns: 80px 70px 1fr 100px 100px 100px 130px 110px 95px 72px 100px 90px; background: #1e3a5f; border-bottom: 1px solid #2c5282; }
.rc-lc { padding: .35rem .4rem; font-size: .58rem; font-weight: 700; color: rgba(255,255,255,.8); text-transform: uppercase; letter-spacing: .04em; border-right: 1px solid rgba(255,255,255,.1); }
.rc-lc:last-child { border-right: none; }
.rc-foci-empty { text-align: center; padding: 3rem; color: #94a3b8; }
.rc-foci-empty__ico { font-size: 2.5rem; margin-bottom: .75rem; }
.rc-foci-obj-block { border-bottom: 2px solid #1e3a5f; }
.rc-foci-obj-banner { display: flex; align-items: center; gap: .5rem; padding: .4rem .85rem; background: #1e3a5f; }
.rc-foci-obj-lbl { background: rgba(255,255,255,.2); padding: .1rem .5rem; border-radius: 4px; font-size: .6rem; font-weight: 700; color: white; white-space: nowrap; }
.rc-foci-obj-txt { font-size: .72rem; font-weight: 600; color: white; }
.rc-foci-rubr-block { border-bottom: 1px solid #dde5ef; }
.rc-foci-rubr-banner { display: flex; align-items: center; gap: .4rem; padding: .32rem .85rem; background: linear-gradient(90deg, #eff6ff, #f8fafc); border-bottom: 1px solid #bfdbfe; font-size: .7rem; font-weight: 600; color: #1e40af; }
.rc-foci-ssrubr-banner { display: flex; align-items: center; gap: .3rem; padding: .22rem 1.25rem; background: #f0fdf4; border-bottom: 1px solid #bbf7d0; font-size: .67rem; color: #065f46; font-style: italic; }
.rc-foci-row { display: grid; grid-template-columns: 80px 70px 1fr 100px 100px 100px 130px 110px 95px 72px 100px 90px; border-bottom: 1px solid #f1f5f9; }
.rc-foci-row--critique { border-left: 3px solid #9d174d; }
.rc-foci-row--insuffisant { border-left: 3px solid #dc2626; }
.rc-foci-row--ameliorer { border-left: 3px solid #d97706; }
.rc-foci-row--ok { border-left: 3px solid #16a34a; }
.rc-foci-cell { padding: .4rem; font-size: .68rem; border-right: 1px solid #f1f5f9; min-height: 45px; color: #334155; line-height: 1.4; word-break: break-word; white-space: pre-wrap; }
.rc-foci-cell--num { display: flex; align-items: center; justify-content: center; }
.rc-foci-cell--niv { display: flex; align-items: center; justify-content: center; }
.rc-foci-num-btn { display: inline-flex; align-items: center; gap: .2rem; background: none; border: none; cursor: pointer; }
.rc-foci-num-badge { background: #1e3a5f; color: white; padding: .1rem .4rem; border-radius: 4px; font-size: .58rem; font-weight: 700; }
.rc-ext-sm { font-size: .55rem; color: #94a3b8; }
.rc-foci-niv-badge { display: inline-block; padding: .1rem .4rem; border-radius: 20px; font-size: .56rem; font-weight: 700; text-align: center; white-space: nowrap; }
.rc-foci-footnote { display: flex; align-items: center; gap: .4rem; padding: .6rem 1rem; background: #f8fafc; border-top: 1px solid var(--border); font-size: .65rem; color: #64748b; font-style: italic; }

/* ── PLAN D'ACTION ──────────────────────────────────────────── */
.rc-plan-view { display: flex; flex-direction: column; gap: 0; background: white; border-radius: 10px; overflow: hidden; box-shadow: var(--sh); }
.rc-plan-header { background: linear-gradient(135deg, #1e3a5f, #2c5282); color: white; padding: .75rem 1rem; }
.rc-plan-header__title { font-size: .9rem; font-weight: 700; }
.rc-plan-header__sub { font-size: .68rem; color: rgba(255,255,255,.7); }
.rc-plan-entete { display: flex; gap: 1.5rem; padding: .5rem 1rem; background: #f8fafc; border-bottom: 1px solid var(--border); font-size: .72rem; }
.rc-plan-legend { display: grid; grid-template-columns: 80px 1fr 120px 150px 150px; background: #1e3a5f; }
.rc-plc { padding: .35rem .5rem; font-size: .58rem; font-weight: 700; color: rgba(255,255,255,.8); text-transform: uppercase; border-right: 1px solid rgba(255,255,255,.1); }
.rc-plan-empty { text-align: center; padding: 3rem; color: #94a3b8; font-size: .8rem; }
.rc-plan-obj-banner { background: #1e3a5f; color: white; padding: .35rem .85rem; font-size: .72rem; font-weight: 600; }
.rc-plan-rubr-banner { background: linear-gradient(90deg, #eff6ff, #f8fafc); border-bottom: 1px solid #bfdbfe; border-top: 1px solid #bfdbfe; padding: .28rem .85rem; font-size: .7rem; color: #1e40af; }
.rc-plan-row { display: grid; grid-template-columns: 80px 1fr 120px 150px 150px; border-bottom: 1px solid #f1f5f9; }
.rc-plan-cell { padding: .4rem .5rem; font-size: .7rem; border-right: 1px solid #f1f5f9; line-height: 1.4; color: #334155; }
.rc-plan-cell--num { display: flex; align-items: center; justify-content: center; }
.rc-plan-cell--reco { color: #1e40af; font-weight: 500; }
.rc-plan-footnote { text-align: center; font-size: .65rem; color: #94a3b8; font-style: italic; padding: .6rem 1rem; border-top: 1px solid var(--border); }

/* ── SLIDES OVERLAY ─────────────────────────────────────────── */
.rc-slides-overlay { position: fixed; inset: 0; background: #0f172a; z-index: 3000; display: flex; flex-direction: column; }
.rc-slides-shell { display: flex; flex-direction: column; height: 100%; }
.rc-slides-nav { display: flex; align-items: center; gap: .75rem; padding: .5rem 1rem; background: rgba(255,255,255,.05); border-bottom: 1px solid rgba(255,255,255,.1); flex-shrink: 0; }
.rc-slides-close { background: #dc2626; border: none; color: white; padding: .3rem .75rem; border-radius: 6px; cursor: pointer; font-size: .75rem; font-weight: 600; display: inline-flex; align-items: center; gap: .3rem; }
.rc-slides-progress { display: flex; align-items: center; gap: .3rem; flex: 1; justify-content: center; }
.rc-slides-dot { width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,.3); cursor: pointer; transition: all .2s; }
.rc-slides-dot--active { background: white; width: 20px; border-radius: 4px; }
.rc-slides-counter { font-size: .72rem; color: rgba(255,255,255,.6); white-space: nowrap; }
.rc-slides-prev,
.rc-slides-next { background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.2); color: white; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.rc-slides-prev:disabled,
.rc-slides-next:disabled { opacity: .3; cursor: not-allowed; }
.rc-slides-stage { flex: 1; display: flex; align-items: center; justify-content: center; padding: 2rem; overflow: hidden; outline: none; }
.rc-slide { width: 100%; max-width: 900px; animation: none; }

/* Slide cards */
.slide-title-card { background: linear-gradient(135deg, #1e3a5f, #1e40af); border-radius: 16px; padding: 3rem; text-align: center; color: white; }
.stc-icon { font-size: 3rem; margin-bottom: 1rem; opacity: .7; }
.stc-title { font-size: 2rem; font-weight: 800; letter-spacing: .05em; }
.stc-sub { font-size: .9rem; opacity: .7; margin-top: .3rem; }
.stc-mission { font-size: 1.1rem; font-weight: 600; margin-top: 1.5rem; background: rgba(255,255,255,.15); padding: .5rem 1rem; border-radius: 8px; }
.stc-meta { display: flex; justify-content: center; gap: 1.5rem; margin-top: 1rem; font-size: .85rem; opacity: .8; }
.stc-norme { font-size: .7rem; opacity: .5; margin-top: 1rem; }
.slide-card { background: white; border-radius: 14px; padding: 1.75rem 2rem; max-height: calc(100vh - 120px); overflow-y: auto; }
.slide-card--far { max-height: calc(100vh - 120px); overflow-y: auto; }
.slide-card--conclusion { background: linear-gradient(135deg, #f0fdf4, #eff6ff); }
.slide-card__title { font-size: 1.2rem; font-weight: 700; color: #1e3a5f; margin-bottom: 1.25rem; display: flex; align-items: center; gap: .5rem; border-bottom: 2px solid #e2e8f0; padding-bottom: .75rem; }
.slide-list { padding-left: 1.25rem; line-height: 1.8; font-size: .88rem; color: #334155; }
.slide-list--check { list-style: none; padding-left: 0; }
.slide-list--check li { padding: .4rem 0; border-bottom: 1px solid #f1f5f9; }
.slide-empty { text-align: center; color: #94a3b8; padding: 2rem; font-style: italic; }
.slide-far-list { display: flex; flex-direction: column; gap: .5rem; }
.slide-far-item { display: flex; align-items: flex-start; gap: .75rem; padding: .6rem .75rem; border-radius: 8px; border: 1px solid var(--border); }
.sfi--ok { border-left: 4px solid #10b981; }
.sfi--ko { border-left: 4px solid #dc2626; }
.sfi--pending { border-left: 4px solid #2563eb; }
.sfi-num { background: #1e3a5f; color: white; padding: .15rem .4rem; border-radius: 4px; font-size: .62rem; font-weight: 700; white-space: nowrap; height: fit-content; }
.sfi-body { flex: 1; }
.sfi-fait { font-size: .78rem; color: #334155; margin: 0 0 .25rem; }
.sfi-reco { font-size: .72rem; color: #1e40af; margin: 0; font-style: italic; }
.sfi-status { font-size: .7rem; font-weight: 600; white-space: nowrap; align-self: center; }
.slide-plan-tbl { width: 100%; border-collapse: collapse; font-size: .78rem; }
.slide-plan-tbl th { background: #1e3a5f; color: white; padding: .4rem .6rem; text-align: left; font-size: .7rem; }
.slide-plan-tbl td { padding: .35rem .6rem; border-bottom: 1px solid #f1f5f9; color: #334155; }
.slide-conclusion-txt { font-size: .9rem; color: #334155; line-height: 1.7; }
.slide-obs-fin { background: white; border-radius: 8px; padding: .75rem 1rem; margin-top: 1rem; }
.slide-obs-fin__lbl { font-size: .65rem; font-weight: 700; color: #1e40af; text-transform: uppercase; margin-bottom: .35rem; }
.slide-merci { text-align: center; margin-top: 1.5rem; font-size: 1rem; font-weight: 600; color: #1e3a5f; }
.slide-ia-score-row { margin-bottom: 1rem; }
.slide-ia-score-badge { display: inline-block; color: white; padding: .3rem .85rem; border-radius: 20px; font-size: .85rem; font-weight: 700; }
.slide-ia-card { border: 1px solid #e2e8f0; border-radius: 10px; padding: .85rem 1rem; margin-bottom: .75rem; }
.slide-ia-card__head { display: flex; align-items: center; gap: .5rem; margin-bottom: .4rem; }
.slide-ia-score { color: white; padding: .15rem .5rem; border-radius: 12px; font-size: .75rem; font-weight: 700; }
.slide-ia-fiab { font-size: .7rem; color: #64748b; }
.slide-ia-concl { font-size: .78rem; color: #334155; margin: 0 0 .5rem; font-style: italic; }
.slide-ia-lists { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; }
.slide-ia-sec { font-size: .65rem; font-weight: 700; color: #1e3a5f; text-transform: uppercase; margin-bottom: .25rem; }
.slide-ia-lists ul { margin: 0; padding-left: 1rem; font-size: .72rem; color: #475569; }

/* Slide transitions */
.slides-fade-enter-active,
.slides-fade-leave-active { transition: opacity .25s; }
.slides-fade-enter-from,
.slides-fade-leave-to { opacity: 0; }
.slide-next-enter-active,
.slide-next-leave-active { transition: all .3s ease; }
.slide-next-enter-from { transform: translateX(60px); opacity: 0; }
.slide-next-leave-to { transform: translateX(-60px); opacity: 0; }
.slide-prev-enter-active,
.slide-prev-leave-active { transition: all .3s ease; }
.slide-prev-enter-from { transform: translateX(-60px); opacity: 0; }
.slide-prev-leave-to { transform: translateX(60px); opacity: 0; }

/* ── MODAL FRAP ─────────────────────────────────────────────── */
.rc-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.rc-frap-modal { background: white; border-radius: 14px; width: 90%; max-width: 680px; max-height: 85vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,.2); }
.rc-frap-modal__hd { display: flex; align-items: center; gap: .75rem; padding: 1rem 1.2rem; border-bottom: 1px solid var(--border); background: #f8fafc; }
.rc-frap-modal__icon { width: 38px; height: 38px; background: #1e3a5f; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.1rem; flex-shrink: 0; }
.rc-frap-modal__hd h2 { font-size: 1rem; font-weight: 700; margin: 0; color: #0f172a; }
.rc-frap-sub { font-size: .7rem; color: #64748b; margin-top: 2px; }
.rc-frap-modal__right { display: flex; align-items: center; gap: .5rem; margin-left: auto; }
.rc-frap-modal__body { flex: 1; overflow-y: auto; padding: 1rem; }
.rc-fm-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
.rc-fm-field { display: flex; flex-direction: column; gap: .2rem; }
.rc-fm-field--full { grid-column: span 2; }
.rc-fm-lbl { font-size: .62rem; font-weight: 700; color: #1e40af; text-transform: uppercase; letter-spacing: .04em; }
.rc-fm-lbl--green { color: #065f46; }
.rc-fm-val { font-size: .8rem; color: #334155; line-height: 1.5; background: #f8fafc; padding: .4rem .6rem; border-radius: 6px; border: 1px solid #f1f5f9; white-space: pre-wrap; }
.rc-fm-val--obj { background: #eff6ff; border-left: 3px solid #2563eb; color: #1e40af; }
.rc-fm-val--reco { border-left: 3px solid #1e40af; background: #eff6ff; }
.rc-fm-val--green { background: #f0fdf4; border-left: 3px solid #16a34a; color: #065f46; }
.rc-obs-source { display: flex; flex-direction: column; gap: .25rem; font-size: .72rem; color: #475569; background: #f8fafc; padding: .5rem .75rem; border-radius: 6px; }
.rc-obs-source div { display: flex; align-items: center; gap: .35rem; }
.rc-frap-modal__ft { display: flex; justify-content: flex-end; padding: .75rem 1.2rem; border-top: 1px solid var(--border); }

/* ── TOAST ──────────────────────────────────────────────────── */
.rc-toast { position: fixed; bottom: 1rem; right: 1rem; display: flex; align-items: center; gap: .5rem; padding: .5rem 1rem; border-radius: 8px; font-size: .75rem; z-index: 2000; box-shadow: 0 4px 12px rgba(0,0,0,.15); }
.rc-toast--success { background: #065f46; color: white; }
.rc-toast--error { background: #dc2626; color: white; }
.rc-toast__x { background: none; border: none; color: white; opacity: .7; cursor: pointer; margin-left: .5rem; }

/* ── SPINNER ─────────────────────────────────────────────────── */
.rc-spin { display: inline-block; width: .8rem; height: .8rem; border: 2px solid rgba(255,255,255,.3); border-top-color: white; border-radius: 50%; animation: spin .7s linear infinite; }
.rc-spin--xs { width: .55rem; height: .55rem; border-color: rgba(100,116,139,.3); border-top-color: #64748b; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ── TRANSITIONS ────────────────────────────────────────────── */
.om-fade-enter-active, .om-fade-leave-active { transition: opacity .2s; }
.om-fade-enter-from, .om-fade-leave-to { opacity: 0; }
.toast-pop-enter-active, .toast-pop-leave-active { transition: all .2s; }
.toast-pop-enter-from, .toast-pop-leave-to { opacity: 0; transform: translateY(10px); }

/* ── MISSION BANNER ─────────────────────────────────────────── */
.rc-mission-banner {
  display: flex; gap: 1rem; background: linear-gradient(135deg, #1e3a5f 0%, #1e40af 100%);
  border-radius: 10px; padding: 1rem 1.25rem; box-shadow: 0 4px 12px rgba(30,58,95,.25);
  flex-wrap: wrap;
}
.rc-mb-left { display: flex; gap: .85rem; flex: 1; min-width: 280px; align-items: flex-start; }
.rc-mb-icon {
  width: 42px; height: 42px; background: rgba(255,255,255,.15);
  border-radius: 10px; display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.3rem; flex-shrink: 0;
}
.rc-mb-body { flex: 1; }
.rc-mb-title { font-size: .88rem; font-weight: 700; color: white; line-height: 1.4; margin-bottom: .35rem; }
.rc-mb-meta {
  display: flex; flex-wrap: wrap; gap: .5rem .85rem; margin-bottom: .4rem;
}
.rc-mb-meta span {
  display: inline-flex; align-items: center; gap: .3rem;
  font-size: .68rem; color: rgba(255,255,255,.75);
}
.rc-mb-meta i { font-size: .7rem; opacity: .7; }
.rc-mb-obj {
  font-size: .7rem; color: rgba(255,255,255,.8); line-height: 1.5;
  background: rgba(255,255,255,.08); border-radius: 6px;
  padding: .4rem .6rem; display: flex; gap: .35rem; align-items: flex-start;
}
.rc-mb-obj i { flex-shrink: 0; margin-top: 2px; }

/* Équipe */
.rc-mb-equipe {
  background: rgba(255,255,255,.1); border-radius: 8px;
  padding: .65rem .85rem; min-width: 200px; flex-shrink: 0;
}
.rc-mb-equipe__title {
  font-size: .65rem; font-weight: 700; color: rgba(255,255,255,.7);
  text-transform: uppercase; letter-spacing: .06em;
  display: flex; align-items: center; gap: .3rem; margin-bottom: .5rem;
}
.rc-mb-equipe__list { display: flex; flex-direction: column; gap: .3rem; }
.rc-mb-membre { display: flex; align-items: center; gap: .5rem; }
.rc-mb-role {
  font-size: .6rem; font-weight: 700; padding: .1rem .4rem;
  border-radius: 4px; white-space: nowrap;
}
.role--DM { background: #dc2626; color: white; }
.role--CM { background: #2563eb; color: white; }
.role--AS { background: #059669; color: white; }
.role--AJ { background: #7c3aed; color: white; }
.rc-mb-nom { font-size: .72rem; color: white; font-weight: 500; }

/* ── STATS & KPIs dans le banner ─────────────────────────────── */
.rc-mb-stats {
  background: rgba(255,255,255,.1); border-radius: 8px;
  padding: .65rem .85rem; min-width: 220px; flex-shrink: 0;
}
.rc-mb-stats__title {
  font-size: .65rem; font-weight: 700; color: rgba(255,255,255,.7);
  text-transform: uppercase; letter-spacing: .06em;
  display: flex; align-items: center; gap: .3rem; margin-bottom: .5rem;
}
.rc-mb-kpis {
  display: flex; gap: .75rem; margin-bottom: .5rem;
}
.rc-mb-kpi { text-align: center; }
.rc-mb-kpi__val {
  font-size: 1.35rem; font-weight: 800; color: white; line-height: 1;
}
.rc-mb-kpi__val--muted { color: rgba(255,255,255,.4); }
.rc-mb-kpi__lbl {
  font-size: .58rem; color: rgba(255,255,255,.6); text-transform: uppercase; margin-top: 2px;
}
.rc-mb-niveaux {
  display: flex; flex-wrap: wrap; gap: .25rem; margin-bottom: .35rem;
}
.rc-mb-niv-chip {
  display: inline-flex; align-items: center; gap: .25rem;
  font-size: .58rem; padding: .1rem .4rem; border-radius: 20px;
  border: 1px solid transparent; font-weight: 600;
}
.rc-mb-niv-chip strong { font-size: .7rem; }
.rc-mb-niv-empty { font-size: .62rem; color: rgba(255,255,255,.4); font-style: italic; }
.rc-mb-ia-top {
  display: flex; align-items: flex-start; gap: .3rem;
  font-size: .62rem; color: rgba(255,255,255,.65); margin-top: .35rem; line-height: 1.4;
  background: rgba(255,255,255,.08); border-radius: 5px; padding: .3rem .45rem;
}
.rc-mb-ia-concl {
  overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

/* Prog badge dans banner */
.rc-mb-prog {
  display: flex; align-items: center; gap: .4rem; margin-top: .35rem;
  font-size: .65rem; color: rgba(255,255,255,.65);
}
.rc-mb-prog-badge {
  background: rgba(255,255,255,.2); padding: .1rem .4rem;
  border-radius: 4px; font-size: .6rem; font-weight: 700; color: white;
}

/* Membre info vertical */
.rc-mb-membre-info { display: flex; flex-direction: column; }
.rc-mb-role-lbl { font-size: .58rem; color: rgba(255,255,255,.55); }
.rc-mb-rci {
  margin-top: .5rem; font-size: .62rem; color: rgba(255,255,255,.65);
  background: rgba(255,255,255,.08); border-radius: 5px;
  padding: .25rem .45rem; display: flex; align-items: center; gap: .3rem;
}

/* ── PARTICIPANTS ────────────────────────────────────────────── */
.rc-participants-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: .75rem; padding: 1rem;
}
.rc-participant-card {
  border: 1px solid var(--border); border-radius: 8px;
  overflow: hidden; background: white;
  box-shadow: 0 1px 3px rgba(15,23,42,.05); transition: box-shadow .15s;
}
.rc-participant-card:hover { box-shadow: 0 3px 8px rgba(15,23,42,.1); }
.rc-participant-card__top {
  display: flex; align-items: center; gap: .5rem;
  padding: .45rem .7rem;
  background: linear-gradient(90deg, #eff6ff, #f0fdf4);
  border-bottom: 1px solid var(--border);
}
.rc-pcard-check { display: flex; align-items: center; gap: .35rem; cursor: pointer; }
.rc-pcard-present {
  font-size: .62rem; font-weight: 700; padding: .1rem .45rem;
  border-radius: 20px;
}
.rpp--ok     { background: #d1fae5; color: #065f46; }
.rpp--absent { background: #fee2e2; color: #dc2626; }
.rc-pcard-fields { display: flex; flex-direction: column; gap: .3rem; padding: .55rem .7rem .65rem; }

/* ── RESPONSIVE ─────────────────────────────────────────────── */
@media (max-width: 900px) {
  .rc-grid--3 { grid-template-columns: 1fr 1fr; }
  .rc-signatures-grid { grid-template-columns: 1fr; }
  .rc-foci-entete { grid-template-columns: 1fr; }
  .rc-foci-legend, .rc-foci-row { display: flex; flex-direction: column; }
  .rc-plan-legend, .rc-plan-row { display: flex; flex-direction: column; }
}
@media (max-width: 600px) {
  .rc-topbar { flex-direction: column; align-items: flex-start; }
  .rc-tabs { overflow-x: auto; }
  .rc-grid--3 { grid-template-columns: 1fr; }
}
</style>