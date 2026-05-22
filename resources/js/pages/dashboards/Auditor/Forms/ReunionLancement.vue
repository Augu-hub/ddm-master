<template>
  <VerticalLayoutAudit>
    <div class="frl-shell">

      <!-- ══ HEADER ══ -->
      <header class="frl-header">
        <div class="frl-hrow">
          <a :href="props.backUrl" class="frl-back"><i class="ti ti-arrow-left"></i></a>
          <div class="frl-hinfo">
            <div class="frl-chips">
              <code class="frl-code">{{ form.code || 'FRL-AUTO' }}</code>
              <span class="frl-vst" :class="`fvsc-${form.validation_status||'draft'}`">
                <i :class="vstIcon(form.validation_status||'draft')"></i>
                {{ vstLbl(form.validation_status||'draft') }}
              </span>
              <span class="frl-typechip">
                <i class="ti ti-clipboard-check"></i> Réunion de Lancement
              </span>
              <span v-if="props.auditorRole" class="frl-rolechip" :class="`rc-${props.auditorRole}`">
                {{ props.auditorRole }}
              </span>
              <!-- Badge du programme source -->
              <span v-if="programmeData.found" class="frl-progchip"
                    :class="`prog-${programmeData.programme_code}`">
                <i class="ti ti-file-check"></i>
                {{ programmeData.programme_code }} — {{ programmeData.programme_label }}
              </span>
              <span v-else class="frl-progchip prog-none">
                <i class="ti ti-alert-triangle"></i> Aucun programme de travail
              </span>
            </div>
            <h1 class="frl-title">Procès-Verbal de Réunion de Lancement — Phase de Réalisation</h1>
            <div class="frl-meta">
              <span v-if="mission?.code_mission"><i class="ti ti-clipboard"></i>{{ mission.code_mission }}</span>
              <span v-if="mission?.libelle"><i class="ti ti-file-description"></i>{{ mission.libelle }}</span>
              <span v-if="mission?.entity_name"><i class="ti ti-building"></i>{{ mission.entity_name }}</span>
            </div>
          </div>
        </div>

        <!-- Banners statut -->
        <div v-if="form.validation_status==='validated'" class="frl-banner frl-banner-lock">
          <i class="ti ti-lock"></i> PV <strong>validé définitivement</strong> — lecture seule
        </div>
        <div v-else-if="form.validation_status==='in_review'" class="frl-banner frl-banner-review">
          <i class="ti ti-clock"></i> Soumis pour validation
          <span v-if="canManage"> · DM/CM peut valider ou rejeter.</span>
        </div>
        <div v-else-if="form.validation_status==='draft'&&form.validation_note" class="frl-banner frl-banner-reject">
          <i class="ti ti-circle-x"></i> Rejeté — <em>{{ form.validation_note }}</em>
        </div>

        <!-- Dashboard programmes disponibles — boutons cliquables -->
        <div class="frl-prog-bar">
          <span class="frl-prog-lbl">Source objectifs :</span>
          <div class="frl-prog-badges">
            <button v-for="prog in statutProgrammes" :key="prog.code"
                  type="button"
                  class="frl-prog-badge frl-prog-btn"
                  :class="[
                    prog.exists ? `pb-${prog.code} pb-exists` : 'pb-missing',
                    activeProgrammeCode === prog.code ? 'pb-active' : '',
                    !prog.exists ? 'pb-disabled' : ''
                  ]"
                  :disabled="!prog.exists || loadingProg"
                  :title="prog.exists ? `Charger les objectifs de ${prog.code}` : `${prog.code} non disponible`"
                  @click="changerProgramme(prog)">
              <span v-if="loadingProg && activeProgrammeCode === prog.code" class="pb-spin"></span>
              <i v-else :class="prog.exists ? (activeProgrammeCode === prog.code ? 'ti ti-circle-check-filled' : 'ti ti-circle-check') : 'ti ti-circle-x'"></i>
              {{ prog.code }}
              <span v-if="prog.exists" class="pb-status">{{ vstLbl(prog.status) }}</span>
              <span v-if="activeProgrammeCode === prog.code" class="pb-active-dot"></span>
            </button>
          </div>
          <span v-if="activeProgrammeCode" class="frl-prog-src">
            <i class="ti ti-file-check"></i>
            Source active : <strong>{{ activeProgrammeCode }}</strong>
            · {{ form.objectifs.length }} objectif(s)
          </span>
          <span v-if="loadingProg" class="frl-prog-loading">
            <span class="pb-spin pb-spin-sm"></span> Chargement…
          </span>
        </div>
      </header>

      <!-- ══ BODY ══ -->
      <div class="frl-body">

        <!-- ═══════════════════════════════════════════
             ENTÊTE STYLE EXCEL (fidèle au modèle)
        ═══════════════════════════════════════════ -->
        <div class="pv-entete">
          <div class="pv-entete-left">
            <div class="pv-org-label">AUDIT INTERNE</div>
            <div class="pv-title-main">PROCÈS-VERBAL DE RÉUNION DE LANCEMENT</div>
            <div class="pv-title-sub">PHASE DE RÉALISATION</div>
          </div>
          <div class="pv-entete-right">
            <div class="pv-meta-row">
              <span class="pv-meta-lbl">Code Mission</span>
              <span class="pv-meta-val">{{ mission?.code_mission || '—' }}</span>
            </div>
            <div class="pv-meta-row">
              <span class="pv-meta-lbl">Intitulé</span>
              <span class="pv-meta-val">{{ mission?.libelle || '—' }}</span>
            </div>
            <div class="pv-meta-row">
              <span class="pv-meta-lbl">Entité auditée</span>
              <span class="pv-meta-val">{{ mission?.entity_name || '—' }}</span>
            </div>
            <div class="pv-meta-row">
              <span class="pv-meta-lbl">Réf. PV</span>
              <span class="pv-meta-val frl-code-sm">{{ form.code || 'FRL-AUTO' }}</span>
            </div>
          </div>
        </div>

        <!-- ═══════════════════════════════════════════
             INFORMATIONS RÉUNION
        ═══════════════════════════════════════════ -->
        <div class="pv-section">
          <div class="pv-sec-hdr"><span>1. INFORMATIONS DE LA RÉUNION</span></div>
          <div class="pv-sec-body">
            <div class="pv-grid-3">
              <div class="frl-field">
                <label class="frl-lbl">Date de la réunion <span class="req">*</span></label>
                <input type="date" class="frl-inp" v-model="form.date_reunion" :disabled="isLocked"
                       :class="{ err: errors.date_reunion }"/>
                <span v-if="errors.date_reunion" class="frl-err">{{ errors.date_reunion }}</span>
              </div>
              <div class="frl-field">
                <label class="frl-lbl">Heure début</label>
                <input type="time" class="frl-inp" v-model="form.heure_debut" :disabled="isLocked"/>
              </div>
              <div class="frl-field">
                <label class="frl-lbl">Heure fin</label>
                <input type="time" class="frl-inp" v-model="form.heure_fin" :disabled="isLocked"/>
              </div>
            </div>
            <div class="pv-grid-2">
              <div class="frl-field">
                <label class="frl-lbl">Lieu de la réunion <span class="req">*</span></label>
                <input type="text" class="frl-inp" v-model="form.lieu" :disabled="isLocked"
                       placeholder="Salle, adresse…" :class="{ err: errors.lieu }"/>
                <span v-if="errors.lieu" class="frl-err">{{ errors.lieu }}</span>
              </div>
              <div class="frl-field">
                <label class="frl-lbl">Présidée par</label>
                <input type="text" class="frl-inp" v-model="form.presidente_par" :disabled="isLocked"
                       placeholder="Nom du président de séance…"/>
              </div>
            </div>
          </div>
        </div>

        <!-- ═══════════════════════════════════════════
             PARTICIPANTS (enrichi : tél, email, signature)
        ═══════════════════════════════════════════ -->
        <div class="pv-section">
          <div class="pv-sec-hdr">
            <span>2. LISTE DES PARTICIPANTS</span>
            <div class="pv-sec-hdr-meta">
              <span class="pv-prog-tag">
                <i class="ti ti-users"></i>
                Équipe : {{ phaseAuditeurs.length }} · Audités : {{ form.participants.length }}
                · Total : {{ phaseAuditeurs.length + form.participants.length }}
              </span>
              <button v-if="!isLocked" type="button" class="pv-add-btn"
                      @click="addRow(form.participants, {nom:'',prenom:'',fonction:'',entite:'',telephone:'',email:'',presence:'present',signature_initiales:''})">
                <i class="ti ti-plus"></i> Ajouter participant
              </button>
            </div>
          </div>

          <!-- Stats présence -->
          <div class="part-stats">
            <div class="part-stat ps-present">
              <i class="ti ti-circle-check"></i>
              <span>{{ presentsCount }}</span> Présents
            </div>
            <div class="part-stat ps-excused">
              <i class="ti ti-clock"></i>
              <span>{{ excusesCount }}</span> Excusés
            </div>
            <div class="part-stat ps-absent">
              <i class="ti ti-circle-x"></i>
              <span>{{ absentsCount }}</span> Absents
            </div>
            <div class="part-stat ps-total">
              <i class="ti ti-users"></i>
              <span>{{ phaseAuditeurs.length + form.participants.length }}</span> Total
            </div>
          </div>

          <div class="pv-tbl-wrap">
            <table class="pv-tbl pv-tbl-part" style="min-width:900px">
              <thead>
                <tr>
                  <th style="width:34px">N°</th>
                  <th style="min-width:110px">Nom</th>
                  <th style="min-width:100px">Prénoms</th>
                  <th style="min-width:130px">Fonction / Qualité</th>
                  <th style="min-width:120px">Entité / Structure</th>
                  <th style="width:110px">Téléphone</th>
                  <th style="min-width:140px">Email</th>
                  <th style="width:88px">Présence</th>
                  <th style="width:70px">
                    Initiales
                    <div class="th-sub">signature</div>
                  </th>
                  <th v-if="!isLocked" style="width:34px"></th>
                </tr>
              </thead>
              <tbody>
                <!-- Équipe d'audit pré-remplie (readonly) -->
                <tr v-for="a in phaseAuditeurs" :key="`aud-${a.id}`" class="tr-auditeur">
                  <td class="tc">
                    <span class="part-badge-aud"><i class="ti ti-shield-half"></i></span>
                  </td>
                  <td class="fw">{{ a.full_name.split(' ')[0] }}</td>
                  <td>{{ a.full_name.split(' ').slice(1).join(' ') }}</td>
                  <td>Auditeur · <span class="rb-chip" :class="`rbc-${a.role_code}`">{{ a.role_code }}</span></td>
                  <td>Équipe d'audit</td>
                  <td class="muted">—</td>
                  <td class="muted">—</td>
                  <td><span class="pv-pres pv-pres-ok"><i class="ti ti-check"></i> Présent</span></td>
                  <td class="tc">
                    <div class="part-sig-box">{{ (a.full_name[0]||'') + (a.full_name.split(' ')[1]?.[0]||'') }}</div>
                  </td>
                  <td v-if="!isLocked"></td>
                </tr>
                <!-- Participants saisis -->
                <tr v-if="!form.participants.length && !phaseAuditeurs.length">
                  <td colspan="10" class="pv-empty">Aucun participant enregistré</td>
                </tr>
                <tr v-for="(p, i) in form.participants" :key="i" :class="{'tr-absent': p.presence==='absent','tr-excused':p.presence==='excused'}">
                  <td class="tc muted fw">{{ i + 1 }}</td>
                  <td>
                    <input v-if="!isLocked" class="pv-tdinp" v-model="p.nom" placeholder="Nom…"/>
                    <span v-else class="fw">{{ p.nom }}</span>
                  </td>
                  <td>
                    <input v-if="!isLocked" class="pv-tdinp" v-model="p.prenom" placeholder="Prénoms…"/>
                    <span v-else>{{ p.prenom }}</span>
                  </td>
                  <td>
                    <input v-if="!isLocked" class="pv-tdinp" v-model="p.fonction" placeholder="Fonction…"/>
                    <span v-else>{{ p.fonction }}</span>
                  </td>
                  <td>
                    <input v-if="!isLocked" class="pv-tdinp" v-model="p.entite" placeholder="Entité…"/>
                    <span v-else>{{ p.entite }}</span>
                  </td>
                  <td>
                    <input v-if="!isLocked" class="pv-tdinp" v-model="p.telephone" placeholder="+229…" type="tel"/>
                    <span v-else>{{ p.telephone || '—' }}</span>
                  </td>
                  <td>
                    <input v-if="!isLocked" class="pv-tdinp" v-model="p.email" placeholder="email@…" type="email"/>
                    <span v-else>{{ p.email || '—' }}</span>
                  </td>
                  <td>
                    <select v-if="!isLocked" class="pv-tdinp pv-sel" v-model="p.presence">
                      <option value="present">Présent</option>
                      <option value="excused">Excusé</option>
                      <option value="absent">Absent</option>
                    </select>
                    <span v-else class="pv-pres" :class="`pv-pres-${p.presence||'present'}`">
                      {{ presenceLabel(p.presence) }}
                    </span>
                  </td>
                  <td class="tc">
                    <!-- Case initiales / signature manuscrite simulée -->
                    <input v-if="!isLocked" class="part-sig-inp" v-model="p.signature_initiales"
                           :placeholder="(p.nom?.[0]||'?')+(p.prenom?.[0]||'?')"
                           maxlength="4" title="Initiales ou paraphe"/>
                    <div v-else class="part-sig-box">{{ p.signature_initiales || ((p.nom?.[0]||'')+(p.prenom?.[0]||'')) }}</div>
                  </td>
                  <td v-if="!isLocked">
                    <button type="button" class="pv-delbtn" @click="removeRow(form.participants, i)">
                      <i class="ti ti-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ═══════════════════════════════════════════
             MÉTHODOLOGIE GÉNÉRALE
        ═══════════════════════════════════════════ -->
        <div class="pv-section">
          <div class="pv-sec-hdr"><span>3. MÉTHODOLOGIE GÉNÉRALE</span></div>
          <div class="pv-sec-body">
            <textarea class="frl-inp frl-ta" v-model="form.methodologie" :disabled="isLocked"
                      rows="4" placeholder="Décrire la méthodologie générale d'audit présentée lors de la réunion de lancement…"></textarea>
          </div>
        </div>

        <!-- ═══════════════════════════════════════════
             SECTION 4 — DOCUMENTS DE RÉFÉRENCE
        ═══════════════════════════════════════════ -->
     

        <!-- ═══════════════════════════════════════════
             SECTION 5 — OBJECTIFS D'AUDIT & DILIGENCES
             (fidèle au format Excel)
        ═══════════════════════════════════════════ -->
        <div class="pv-section">
          <div class="pv-sec-hdr">
            <span>5. OBJECTIFS D'AUDIT ET DILIGENCES PRÉVUES</span>
            <div class="pv-sec-hdr-meta">
              <span v-if="programmeData.found" class="pv-prog-tag">
                <i class="ti ti-file-check"></i>
                Source : {{ programmeData.programme_code }} — {{ programmeData.programme_label }}
              </span>
              <span v-else class="pv-warn-tag">
                <i class="ti ti-alert-triangle"></i> Aucun programme de travail disponible
              </span>
              <button v-if="!isLocked" type="button" class="pv-add-btn"
                      @click="addRow(form.objectifs, {objectif:'',ref:'',etapes_travaux:'',auditeurs:'',periode_lieu:'',observations:'',risques_faiblesses:''})">
                <i class="ti ti-plus"></i> Ajouter ligne
              </button>
            </div>
          </div>

          <!-- Alerte si aucun programme -->
          <div v-if="!programmeData.found && !activeProgrammeCode" class="pv-nodata">
            <i class="ti ti-alert-triangle"></i>
            <p>Aucun programme de travail trouvé pour cette mission.
              Créez d'abord un Programme CI, Conformité, Marchés ou Transactions.</p>
          </div>

          <div v-else-if="form.objectifs.length === 0 && (programmeData.found || activeProgrammeCode)" class="pv-nodata pv-nodata-info">
            <i class="ti ti-info-circle"></i>
            <p>Cliquez sur un programme dans la barre en haut pour charger les objectifs, ou ajoutez une ligne manuellement.</p>
          </div>

          <div class="pv-tbl-wrap pv-tbl-scroll">
            <table class="pv-tbl pv-tbl-obj" style="min-width:1100px">
              <thead>
                <tr>
                  <th style="width:36px">Réf.</th>
                  <th style="min-width:200px">Objectif d'audit</th>
                  <th style="min-width:160px">Risques / Faiblesses apparentes</th>
                  <th style="min-width:220px">
                    Étapes / Travaux &amp; Procédures
                    <div class="th-sub">tests + procédures du programme sélectionné</div>
                  </th>
                  <th style="width:130px">Auditeurs</th>
                  <th style="width:130px">Période / Lieu</th>
                  <th style="min-width:160px">Observations</th>
                  
                  <th v-if="!isLocked" style="width:34px"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!form.objectifs.length">
                  <td :colspan="isLocked ? 7 : 8" class="pv-empty">
                    Aucun objectif. Sélectionnez un programme ou ajoutez manuellement.
                  </td>
                </tr>
                <template v-for="(obj, i) in form.objectifs" :key="i">
                  <!-- Bandeau axe RADO si présent -->
                  <tr v-if="obj._axe_rado && (i===0 || form.objectifs[i-1]?._axe_rado !== obj._axe_rado)"
                      class="tr-axe">
                    <td :colspan="isLocked ? 7 : 8">
                      <i class="ti ti-compass"></i> {{ obj._axe_rado }}
                      <span v-if="obj._priorite" class="axe-prio" :class="`prio-${obj._priorite}`">
                        {{ obj._priorite }}
                      </span>
                    </td>
                  </tr>

                  <tr :class="i % 2 === 0 ? '' : 'tr-alt'">
                    <td class="tc muted fw">{{ obj.ref || (i + 1) }}</td>
                    <td class="td-objectif">
                      <textarea v-if="!isLocked" class="pv-tdinp pv-ta" v-model="obj.objectif"
                                rows="3" placeholder="S'assurer que…"></textarea>
                      <span v-else class="pv-pre">{{ obj.objectif || '—' }}</span>
                    </td>
                    <td>
                      <textarea v-if="!isLocked" class="pv-tdinp pv-ta" v-model="obj.risques_faiblesses"
                                rows="2" placeholder="Risques / Faiblesses…"></textarea>
                      <span v-else class="pv-pre">{{ obj.risques_faiblesses || '' }}</span>
                    </td>
                    <!-- ── Étapes / Travaux + Procédures ─────────── -->
                    <td class="td-etapes">
                      <!-- Mode édition : champ texte libre -->
                      <template v-if="!isLocked">
                        <textarea class="pv-tdinp pv-ta" v-model="obj.etapes_travaux"
                                  rows="2" placeholder="Libellé du test…"></textarea>
                        <!-- Procédures issues du programme (lecture + éditables) -->
                        <div v-if="obj._procedures && obj._procedures.length" class="proc-block">
                          <div class="proc-hdr">
                            <i class="ti ti-list-check"></i>
                            Procédures ({{ obj._procedures.length }})
                            <button type="button" class="proc-toggle"
                                    @click="toggleProc(i)">
                              {{ procExpanded[i] ? '▲ Réduire' : '▼ Voir' }}
                            </button>
                          </div>
                          <ul v-if="procExpanded[i]" class="proc-list">
                            <li v-for="(pr, pi) in obj._procedures" :key="pi" class="proc-item">
                              <span class="proc-num">{{ pi + 1 }}.</span>
                              <input class="proc-inp" v-model="obj._procedures[pi]"
                                     :placeholder="`Procédure ${pi+1}…`"/>
                              <button type="button" class="proc-del"
                                      @click="obj._procedures.splice(pi, 1)">
                                <i class="ti ti-x"></i>
                              </button>
                            </li>
                            <li>
                              <button type="button" class="proc-add"
                                      @click="obj._procedures.push('')">
                                <i class="ti ti-plus"></i> Ajouter procédure
                              </button>
                            </li>
                          </ul>
                        </div>
                        <!-- Pas de procédures → bouton ajouter -->
                        <button v-else type="button" class="proc-add-inline"
                                @click="obj._procedures = ['']">
                          <i class="ti ti-plus"></i> Ajouter procédures
                        </button>
                      </template>

                      <!-- Mode lecture seule -->
                      <template v-else>
                        <span class="pv-pre fw-sm">{{ obj.etapes_travaux || '—' }}</span>
                        <!-- Afficher les procédures -->
                        <div v-if="obj._procedures && obj._procedures.filter((p:any)=>p).length"
                             class="proc-block-ro">
                          <div class="proc-hdr-ro">
                            <i class="ti ti-list-check"></i> Procédures
                          </div>
                          <ol class="proc-list-ro">
                            <li v-for="(pr, pi) in obj._procedures.filter((p:any)=>p)" :key="pi">
                              {{ pr }}
                            </li>
                          </ol>
                        </div>
                      </template>
                    </td>

                    <td>
                      <select v-if="!isLocked && phaseAuditeurs.length"
                              class="pv-tdinp pv-sel" v-model="obj.auditeurs">
                        <option value="">—</option>
                        <option v-for="a in phaseAuditeurs" :key="a.id" :value="a.full_name">
                          {{ a.full_name }} ({{ a.role_code }})
                        </option>
                      </select>
                      <input v-else-if="!isLocked" class="pv-tdinp" v-model="obj.auditeurs"
                             placeholder="Auditeur(s)…"/>
                      <span v-else>{{ obj.auditeurs || '—' }}</span>
                    </td>
                    <td>
                      <input v-if="!isLocked" class="pv-tdinp" v-model="obj.periode_lieu"
                             placeholder="Période / Lieu…"/>
                      <span v-else>{{ obj.periode_lieu || '—' }}</span>
                    </td>
                    <td>
                      <textarea v-if="!isLocked" class="pv-tdinp pv-ta" v-model="obj.observations"
                                rows="2" placeholder="Observations…"></textarea>
                      <span v-else class="pv-pre">{{ obj.observations || '' }}</span>
                    </td>
                    
                    <td v-if="!isLocked">
                      <button type="button" class="pv-delbtn" @click="removeRow(form.objectifs, i)">
                        <i class="ti ti-trash"></i>
                      </button>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ═══════════════════════════════════════════
             SECTION 6 — PRÉOCCUPATIONS DES AUDITÉS
        ═══════════════════════════════════════════ -->
        <div class="pv-section">
          <div class="pv-sec-hdr">
            <span>6. PRÉOCCUPATIONS DES AUDITÉS</span>
            <button v-if="!isLocked" type="button" class="pv-add-btn"
                    @click="addRow(form.preoccupations, {libelle:'',exprimee_par:'',niveau_importance:'moyen',reponse:'',statut:'ouvert'})">
              <i class="ti ti-plus"></i> Ajouter
            </button>
          </div>
          <div class="pv-tbl-wrap">
            <table class="pv-tbl">
              <thead>
                <tr>
                  <th style="width:36px">N°</th>
                  <th style="min-width:220px">Préoccupation / Point soulevé</th>
                  <th style="width:150px">Exprimée par</th>
                  <th style="width:110px">Niveau d'importance</th>
                  <th style="min-width:180px">Réponse / Action prévue</th>
                  <th style="width:90px">Statut</th>
                  <th v-if="!isLocked" style="width:34px"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!form.preoccupations.length">
                  <td :colspan="isLocked ? 6 : 7" class="pv-empty">Aucune préoccupation enregistrée</td>
                </tr>
                <tr v-for="(pr, i) in form.preoccupations" :key="i" :class="i % 2 === 0 ? '' : 'tr-alt'">
                  <td class="tc muted">{{ i + 1 }}</td>
                  <td>
                    <textarea v-if="!isLocked" class="pv-tdinp pv-ta" v-model="pr.libelle"
                              rows="2" placeholder="Préoccupation…"></textarea>
                    <span v-else class="pv-pre">{{ pr.libelle || '—' }}</span>
                  </td>
                  <td>
                    <input v-if="!isLocked" class="pv-tdinp" v-model="pr.exprimee_par"
                           placeholder="Nom, fonction…"/>
                    <span v-else>{{ pr.exprimee_par || '—' }}</span>
                  </td>
                  <td>
                    <select v-if="!isLocked" class="pv-tdinp pv-sel" v-model="pr.niveau_importance">
                      <option value="faible">Faible</option>
                      <option value="moyen">Moyen</option>
                      <option value="eleve">Élevé</option>
                      <option value="critique">Critique</option>
                    </select>
                    <span v-else class="pv-niveau" :class="`niv-${pr.niveau_importance||'moyen'}`">
                      {{ niveauLabel(pr.niveau_importance) }}
                    </span>
                  </td>
                  <td>
                    <textarea v-if="!isLocked" class="pv-tdinp pv-ta" v-model="pr.reponse"
                              rows="2" placeholder="Réponse / action…"></textarea>
                    <span v-else class="pv-pre">{{ pr.reponse || '—' }}</span>
                  </td>
                  <td>
                    <select v-if="!isLocked" class="pv-tdinp pv-sel" v-model="pr.statut">
                      <option value="ouvert">Ouvert</option>
                      <option value="en_cours">En cours</option>
                      <option value="clos">Clos</option>
                    </select>
                    <span v-else class="pv-statut" :class="`st-${pr.statut||'ouvert'}`">
                      {{ statutLabel(pr.statut) }}
                    </span>
                  </td>
                  <td v-if="!isLocked">
                    <button type="button" class="pv-delbtn" @click="removeRow(form.preoccupations, i)">
                      <i class="ti ti-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ═══════════════════════════════════════════
             SECTION 7 — PIÈCES JOINTES & ENREGISTREMENTS
        ═══════════════════════════════════════════ -->
        <div class="pv-section">
          <div class="pv-sec-hdr">
            <span>7. PIÈCES JOINTES &amp; ENREGISTREMENTS</span>
            <div class="pv-sec-hdr-meta">
              <span class="pv-prog-tag">
                <i class="ti ti-paperclip"></i> {{ allMediaCount }} fichier(s)
              </span>
            </div>
          </div>

          <div class="media-shell">

            <!-- ── Barre d'actions ───────────────────────────── -->
            <div v-if="!isLocked" class="media-toolbar">

              <!-- Enregistrement audio -->
              <div class="media-rec-group">
                <button type="button"
                        class="media-rec-btn"
                        :class="{ 'rec-active': recorder.active && recorder.type==='audio', 'rec-paused': recorder.paused && recorder.type==='audio' }"
                        @click="toggleRecord('audio')"
                        :title="recorder.active && recorder.type==='audio' ? 'Arrêter l\'enregistrement' : 'Enregistrer audio'">
                  <i :class="recorder.active && recorder.type==='audio' ? 'ti ti-player-stop-filled' : 'ti ti-microphone'"></i>
                  <span>{{ recorder.active && recorder.type==='audio' ? 'Arrêter' : 'Audio' }}</span>
                  <span v-if="recorder.active && recorder.type==='audio'" class="rec-dot"></span>
                </button>

                <!-- Timer pendant enregistrement -->
                <span v-if="recorder.active && recorder.type==='audio'" class="rec-timer">
                  {{ recTimerDisplay }}
                </span>
              </div>

              <!-- Enregistrement vidéo -->
              <div class="media-rec-group">
                <button type="button"
                        class="media-rec-btn media-rec-btn-vid"
                        :class="{ 'rec-active': recorder.active && recorder.type==='video', 'rec-paused': recorder.paused && recorder.type==='video' }"
                        @click="toggleRecord('video')"
                        :title="recorder.active && recorder.type==='video' ? 'Arrêter' : 'Enregistrer vidéo'">
                  <i :class="recorder.active && recorder.type==='video' ? 'ti ti-player-stop-filled' : 'ti ti-video'"></i>
                  <span>{{ recorder.active && recorder.type==='video' ? 'Arrêter' : 'Vidéo' }}</span>
                  <span v-if="recorder.active && recorder.type==='video'" class="rec-dot rec-dot-vid"></span>
                </button>
                <span v-if="recorder.active && recorder.type==='video'" class="rec-timer rec-timer-vid">
                  {{ recTimerDisplay }}
                </span>
              </div>

              <!-- Séparateur -->
              <div class="media-sep"></div>

              <!-- Joindre fichier -->
              <label class="media-attach-btn" title="Joindre un fichier (audio, vidéo, PDF, image)">
                <i class="ti ti-paperclip"></i>
                <span>Joindre fichier</span>
                <input type="file" class="media-file-input" multiple
                       accept="audio/*,video/*,image/*,.pdf,.doc,.docx,.xls,.xlsx"
                       @change="onFileAttach"/>
              </label>

              <!-- Preview vidéo en direct -->
              <video v-if="recorder.active && recorder.type==='video'"
                     ref="liveVideoEl"
                     class="rec-live-preview"
                     autoplay muted playsinline></video>
            </div>

            <!-- ── Message erreur micro/caméra ───────────────── -->
            <div v-if="recorder.error" class="media-err">
              <i class="ti ti-alert-triangle"></i> {{ recorder.error }}
            </div>

            <!-- ── Liste des médias ──────────────────────────── -->
            <div v-if="allMediaCount === 0" class="media-empty">
              <i class="ti ti-player-record"></i>
              <p>Aucune pièce jointe pour le moment.</p>
              <span>Enregistrez un audio / vidéo ou joignez un fichier.</span>
            </div>

            <div v-else class="media-list">
              <div v-for="(m, i) in form.mediaItems" :key="m.id || i" class="media-item" :class="`mi-${m.kind}`">

                <!-- Icône type -->
                <div class="mi-icon">
                  <i :class="mediaIcon(m.kind)"></i>
                </div>

                <!-- Infos -->
                <div class="mi-info">
                  <div class="mi-name">{{ m.name }}</div>
                  <div class="mi-meta">
                    <span class="mi-type-badge" :class="`mtb-${m.kind}`">{{ mediaKindLabel(m.kind) }}</span>
                    <span v-if="m.size_label">{{ m.size_label }}</span>
                    <span v-if="m.duration_label"><i class="ti ti-clock"></i> {{ m.duration_label }}</span>
                    <span class="mi-date">{{ m.created_at }}</span>
                    <span v-if="m.source==='recorded'" class="mi-recorded-badge">
                      <i class="ti ti-player-record"></i> Enregistré
                    </span>
                  </div>
                  <!-- Note optionnelle -->
                  <input v-if="!isLocked" class="mi-note-inp" v-model="m.note"
                         placeholder="Ajouter une note sur ce fichier…"/>
                  <span v-else-if="m.note" class="mi-note-ro">{{ m.note }}</span>
                </div>

                <!-- Player inline pour audio -->
                <div v-if="m.kind==='audio' && m.blob_url" class="mi-player">
                  <audio :src="m.blob_url" controls class="mi-audio"></audio>
                </div>

                <!-- Player inline pour vidéo -->
                <div v-if="m.kind==='video' && m.blob_url" class="mi-player">
                  <video :src="m.blob_url" controls class="mi-video"></video>
                </div>

                <!-- Actions -->
                <div class="mi-actions">
                  <a v-if="m.blob_url" :href="m.blob_url" :download="m.name"
                     class="mi-btn mi-btn-dl" title="Télécharger">
                    <i class="ti ti-download"></i>
                  </a>
                  <button v-if="!isLocked" type="button" class="mi-btn mi-btn-del"
                          @click="removeMedia(i)" title="Supprimer">
                    <i class="ti ti-trash"></i>
                  </button>
                </div>
              </div>
            </div>

          </div><!-- /media-shell -->
        </div>

        <!-- ═══════════════════════════════════════════
             SECTION 8 — SIGNATURES (fidèle Excel)
        ═══════════════════════════════════════════ -->
        

        <!-- ═══════════════════════════════════════════
             FOOTER ACTIONS
        ═══════════════════════════════════════════ -->
        <footer class="frl-footer">
          <div class="frl-footer-meta">
            <div class="frl-field-i">
              <label class="frl-lbl">Fait par</label>
              <input v-if="!isLocked" class="frl-inp frl-inp-sm" v-model="form.fait_par"/>
              <span v-else class="frl-ro-val">{{ form.fait_par || '—' }}</span>
            </div>
            <div class="frl-field-i">
              <label class="frl-lbl">Revu par</label>
              <input v-if="!isLocked" class="frl-inp frl-inp-sm" v-model="form.revue_par"/>
              <span v-else class="frl-ro-val">{{ form.revue_par || '—' }}</span>
            </div>
            <!-- Indicateur autosave -->
            <div v-if="form.id && !isLocked" class="autosave-indicator">
              <i class="ti ti-cloud-check"></i>
              Sauvegarde auto toutes les 90s
            </div>
          </div>

          <div class="frl-footer-acts">
            <button v-if="!isLocked" type="button" class="frl-btn frl-btn-ghost"
                    :disabled="processing" @click="annuler">
              <i class="ti ti-x"></i> Annuler
            </button>

            <button v-if="!isLocked" type="button" class="frl-btn frl-btn-save"
                    :disabled="processing" @click="submit">
              <span v-if="processing" class="frl-spin"></span>
              <i v-else class="ti ti-device-floppy"></i>
              {{ form.id ? 'Mettre à jour' : 'Enregistrer' }}
            </button>

            <button v-if="form.id && form.validation_status === 'draft'" type="button"
                    class="frl-btn frl-btn-submit" :disabled="processing" @click="soumettre">
              <i class="ti ti-send"></i> Soumettre
            </button>

            <template v-if="canManage && form.validation_status === 'in_review'">
              <button type="button" class="frl-btn frl-btn-validate" :disabled="processing" @click="valider('validate')">
                <i class="ti ti-circle-check"></i> Valider
              </button>
              <button type="button" class="frl-btn frl-btn-reject" :disabled="processing" @click="promptReject">
                <i class="ti ti-circle-x"></i> Rejeter
              </button>
            </template>
          </div>
        </footer>

      </div><!-- /frl-body -->
    </div><!-- /frl-shell -->

    <!-- Toast -->
    <Teleport to="body">
      <Transition name="toast-t">
        <div v-if="toast.show" class="frl-toast" :class="`toast-${toast.type}`">
          <i :class="toast.type==='success' ? 'ti ti-circle-check' : 'ti ti-circle-x'"></i>
          {{ toast.msg }}
        </div>
      </Transition>
    </Teleport>
  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, computed, reactive, onBeforeUnmount, onMounted, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'

// ══════════════════════════════════════════════════════════════
// PROPS
// ══════════════════════════════════════════════════════════════
const props = defineProps({
  mission:          { type: Object, default: null },
  auditorRole:      { type: String, default: null },
  missionId:        { type: Number, default: null },
  assignmentId:     { type: Number, default: null },
  form:             { type: Object, default: null },
  phaseAuditeurs:   { type: Array,  default: () => [] },
  programmeData:    { type: Object, default: () => ({ found: false, objectifs: [], total_objectifs: 0, total_tests: 0 }) },
  statutProgrammes: { type: Array,  default: () => [] },
  missionContext:   { type: Object, default: () => ({}) },
  backUrl:          { type: String, default: '' },
  urlStore:         { type: String, default: '' },
  urlUpdate:        { type: String, default: null },
  urlSoumettre:     { type: String, default: null },
  urlValider:       { type: String, default: null },
  urlIndex:         { type: String, default: '' },   // route('audit.ac.realisation.reunion-lancement')
  errors:           { type: Object, default: () => ({}) },
})

// ══════════════════════════════════════════════════════════════
// ÉTAT
// ══════════════════════════════════════════════════════════════
function safeArr(v: any): any[] {
  if (Array.isArray(v)) return [...v]
  if (!v) return []
  try { const d = JSON.parse(v); return Array.isArray(d) ? d : [] } catch { return [] }
}
function safeObj(v: any): any {
  if (v && typeof v === 'object' && !Array.isArray(v)) return { ...v }
  if (!v) return {}
  try { const d = JSON.parse(v); return (d && typeof d === 'object') ? d : {} } catch { return {} }
}

const form = reactive<any>({
  id: null, code: '', validation_status: 'draft', validation_note: '',
  date_reunion: '', heure_debut: '', heure_fin: '',
  lieu: '', presidente_par: '', methodologie: '',
  fait_par: '', revue_par: '',
  participants:   safeArr(props.form?.participants),
  preoccupations: safeArr(props.form?.preoccupations),
  mediaItems:     safeArr(props.form?.media_items),
  documents_ref:  safeArr(props.form?.documents_ref),
  // Section 5 : charger depuis programme si pas encore sauvegardé
  objectifs: (() => {
    const saved = safeArr(props.form?.objectifs)
    if (saved.length) return saved
    // Pré-charger depuis le programme de travail
    return safeArr((props.programmeData as any)?.objectifs)
  })(),
  signatures: safeObj(props.form?.signatures),
  ...(props.form ? {
    id: props.form.id,
    code: props.form.code,
    validation_status: props.form.validation_status,
    validation_note: props.form.validation_note,
    date_reunion: props.form.date_reunion || '',
    heure_debut: props.form.heure_debut || '',
    heure_fin: props.form.heure_fin || '',
    lieu: props.form.lieu || '',
    presidente_par: props.form.presidente_par || '',
    methodologie: props.form.methodologie || '',
    fait_par: props.form.fait_par || '',
    revue_par: props.form.revue_par || '',
  } : {})
})

const dynUrls = reactive({
  update:    props.urlUpdate    ?? null as string | null,
  soumettre: props.urlSoumettre ?? null as string | null,
  valider:   props.urlValider   ?? null as string | null,
})

const errors     = reactive<any>({ ...(props.errors ?? {}) })
const processing = ref(false)
const toast      = ref({ show: false, type: 'success', msg: '' })
let _tt: any

// Computed
const canManage = computed(() => ['DM', 'CM'].includes(props.auditorRole ?? ''))
const isLocked  = computed(() =>
  form.validation_status === 'validated' ||
  (form.validation_status === 'in_review' && !canManage.value)
)

const phaseAuditeurs = computed(() => (props.phaseAuditeurs as any[]) ?? [])
const programmeData  = computed(() => props.programmeData as any)
const statutProgrammes = computed(() => props.statutProgrammes as any[])

// ── Programme actif (sélection dynamique) ──────────────────────────────────
const activeProgrammeCode = ref<string>((props.programmeData as any)?.programme_code ?? '')
const loadingProg         = ref(false)
const procExpanded        = reactive<Record<number, boolean>>({})

function toggleProc(i: number) {
  procExpanded[i] = !procExpanded[i]
}

/**
 * Changer de programme source : appel API → recharge les objectifs section 5
 * L'endpoint est celui du contrôleur ReunionLancement : GET urlIndex?...&load_programme=PTCI
 * On utilise l'URL de base de la mission déjà connue.
 */
async function changerProgramme(prog: any) {
  if (!prog.exists || loadingProg.value) return
  if (activeProgrammeCode.value === prog.code) return // déjà actif

  const confirmed = form.objectifs.length > 0
    ? confirm(`Remplacer les ${form.objectifs.length} objectif(s) actuels par ceux de ${prog.code} ?`)
    : true
  if (!confirmed) return

  loadingProg.value       = true
  activeProgrammeCode.value = prog.code

  try {
    const missionId    = props.missionId    ?? props.missionContext?.mission_id
    const assignmentId = props.assignmentId ?? props.missionContext?.assignment_id

    // Appel API : GET /reunion-lancement?mission_id=X&assignment_id=Y&load_programme=PTCI&json=1
    const base   = props.urlIndex || props.urlStore?.replace(/\?.*/, '') || ''
    const url    = `${base}?mission_id=${missionId}&assignment_id=${assignmentId}&load_programme=${prog.code}&json=1`
    const res    = await fetch(url, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    const data = await res.json()

    if (data.objectifs && Array.isArray(data.objectifs)) {
      // Mapper les objectifs du programme vers la structure du PV
      form.objectifs = data.objectifs.map((obj: any, idx: number) => {
        // Extraire les procédures depuis le premier test
        const firstTest  = (obj.tests && obj.tests[0]) || {}
        const procedures = firstTest.procedures ?? []

        return {
          ref:               obj.num      ?? String(idx + 1),
          objectif:          obj.objectif ?? '',
          etapes_travaux:    firstTest.libelle ?? '',
          auditeurs:         firstTest.auditeur ?? '',
          periode_lieu:      [firstTest.periode_testee, firstTest.lieu].filter(Boolean).join(' / '),
          observations:      '',
          risques_faiblesses: obj._risque_libelle ?? '',
          // Champs cachés pour affichage procédures
          _procedures:       procedures,
          _axe_rado:         obj._axe_rado  ?? '',
          _priorite:         obj._priorite  ?? '',
          _risque_libelle:   obj._risque_libelle ?? '',
          _source:           prog.code,
        }
      })
      showToast('success', `${form.objectifs.length} objectif(s) chargés depuis ${prog.code}`)
    } else if (data.programmeData?.objectifs) {
      // Fallback : format programmeData
      const objList = data.programmeData.objectifs as any[]
      form.objectifs = objList.map((obj: any, idx: number) => {
        const firstTest  = (obj.tests && obj.tests[0]) || {}
        const procedures = firstTest.procedures ?? []
        return {
          ref:               obj.num ?? String(idx + 1),
          objectif:          obj.objectif ?? '',
          etapes_travaux:    firstTest.libelle ?? '',
          auditeurs:         firstTest.auditeur ?? '',
          periode_lieu:      [firstTest.periode_testee, firstTest.lieu].filter(Boolean).join(' / '),
          observations:      '',
          risques_faiblesses: obj._risque_libelle ?? '',
          _procedures:       procedures,
          _axe_rado:         obj._axe_rado  ?? '',
          _priorite:         obj._priorite  ?? '',
          _risque_libelle:   obj._risque_libelle ?? '',
          _source:           prog.code,
        }
      })
      showToast('success', `${form.objectifs.length} objectif(s) chargés depuis ${prog.code}`)
    } else {
      showToast('error', `Aucun objectif trouvé dans ${prog.code}`)
      activeProgrammeCode.value = ''
    }
  } catch (e: any) {
    showToast('error', `Erreur chargement ${prog.code} : ${e.message}`)
    activeProgrammeCode.value = ''
  } finally {
    loadingProg.value = false
    // Reset expanded state
    Object.keys(procExpanded).forEach(k => delete procExpanded[Number(k)])
  }
}

const dmName = computed(() => {
  const dm = phaseAuditeurs.value.find(a => a.role_code === 'DM')
  return dm?.full_name ?? ''
})
const cmName = computed(() => {
  const cm = phaseAuditeurs.value.find(a => a.role_code === 'CM' || a.role_code === 'DM')
  return cm?.full_name ?? ''
})

// ── Computed participants ──────────────────────────────────────────────────
const presentsCount = computed(() =>
  phaseAuditeurs.value.length +
  form.participants.filter((p: any) => !p.presence || p.presence === 'present').length
)
const excusesCount  = computed(() => form.participants.filter((p: any) => p.presence === 'excused').length)
const absentsCount  = computed(() => form.participants.filter((p: any) => p.presence === 'absent').length)
const allMediaCount = computed(() => form.mediaItems.length)

// ══════════════════════════════════════════════════════════════
// ENREGISTREMENT AUDIO / VIDÉO
// ══════════════════════════════════════════════════════════════
const liveVideoEl = ref<HTMLVideoElement | null>(null)

const recorder = reactive({
  active:  false,
  paused:  false,
  type:    '' as 'audio' | 'video' | '',
  error:   '' as string,
})

let _mediaRecorder: MediaRecorder | null = null
let _recordedChunks: BlobPart[] = []
let _recStream: MediaStream | null = null
let _recTimerSec = ref(0)
let _recInterval: any = null

const recTimerDisplay = computed(() => {
  const s = _recTimerSec.value
  const m = Math.floor(s / 60)
  const sec = s % 60
  return `${String(m).padStart(2,'0')}:${String(sec).padStart(2,'0')}`
})

async function toggleRecord(type: 'audio' | 'video') {
  // Si un enregistrement du même type est en cours → arrêter
  if (recorder.active && recorder.type === type) {
    stopRecord()
    return
  }
  // Si enregistrement d'un autre type → arrêter d'abord
  if (recorder.active) stopRecord()

  recorder.error = ''
  try {
    const constraints = type === 'video'
      ? { audio: true, video: true }
      : { audio: true }
    _recStream = await navigator.mediaDevices.getUserMedia(constraints)

    // Preview vidéo
    if (type === 'video') {
      await nextTick()
      if (liveVideoEl.value) {
        liveVideoEl.value.srcObject = _recStream
      }
    }

    const mimeType = type === 'video'
      ? (MediaRecorder.isTypeSupported('video/webm;codecs=vp9,opus') ? 'video/webm;codecs=vp9,opus' : 'video/webm')
      : (MediaRecorder.isTypeSupported('audio/webm;codecs=opus') ? 'audio/webm;codecs=opus' : 'audio/webm')

    _mediaRecorder  = new MediaRecorder(_recStream, { mimeType })
    _recordedChunks = []

    _mediaRecorder.ondataavailable = (e) => {
      if (e.data.size > 0) _recordedChunks.push(e.data)
    }

    _mediaRecorder.onstop = () => {
      const ext      = type === 'video' ? 'webm' : 'webm'
      const mime     = type === 'video' ? 'video/webm' : 'audio/webm'
      const blob     = new Blob(_recordedChunks, { type: mime })
      const url      = URL.createObjectURL(blob)
      const duration = _recTimerSec.value
      const now      = new Date().toLocaleString('fr-FR', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' })
      const name     = `${type === 'video' ? 'Video' : 'Audio'}_reunion_${Date.now()}.${ext}`

      form.mediaItems.push({
        id:             Date.now(),
        kind:           type,
        name,
        blob_url:       url,
        blob,
        size_label:     formatFileSize(blob.size),
        duration_label: formatDuration(duration),
        source:         'recorded',
        created_at:     now,
        note:           '',
      })

      // Nettoyer stream
      _recStream?.getTracks().forEach(t => t.stop())
      _recStream = null
      if (liveVideoEl.value) liveVideoEl.value.srcObject = null
    }

    _mediaRecorder.start(500) // chunk toutes les 500ms
    recorder.active = true
    recorder.type   = type
    _recTimerSec.value = 0
    _recInterval = setInterval(() => { _recTimerSec.value++ }, 1000)

  } catch (e: any) {
    recorder.error = e.name === 'NotAllowedError'
      ? 'Accès microphone/caméra refusé. Vérifiez les permissions du navigateur.'
      : `Impossible de démarrer l'enregistrement : ${e.message}`
  }
}

function stopRecord() {
  if (_mediaRecorder && _mediaRecorder.state !== 'inactive') {
    _mediaRecorder.stop()
  }
  clearInterval(_recInterval)
  recorder.active = false
  recorder.paused = false
  recorder.type   = ''
}

// ── Joindre fichier ──────────────────────────────────────────
function onFileAttach(event: Event) {
  const input = event.target as HTMLInputElement
  const files = Array.from(input.files ?? [])
  const now   = new Date().toLocaleString('fr-FR', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' })

  for (const file of files) {
    const kind = file.type.startsWith('audio/') ? 'audio'
               : file.type.startsWith('video/') ? 'video'
               : file.type.startsWith('image/') ? 'image'
               : 'document'
    const url = URL.createObjectURL(file)
    form.mediaItems.push({
      id:         Date.now() + Math.random(),
      kind,
      name:       file.name,
      blob_url:   url,
      blob:       file,
      size_label: formatFileSize(file.size),
      duration_label: '',
      source:     'attached',
      created_at: now,
      note:       '',
    })
  }
  // Reset input pour permettre re-sélection du même fichier
  input.value = ''
}

function removeMedia(i: number) {
  const item = form.mediaItems[i]
  if (item?.blob_url) URL.revokeObjectURL(item.blob_url)
  form.mediaItems.splice(i, 1)
}

function mediaIcon(kind: string) {
  return { audio:'ti ti-microphone', video:'ti ti-video', image:'ti ti-photo', document:'ti ti-file-description' }[kind] ?? 'ti ti-paperclip'
}
function mediaKindLabel(kind: string) {
  return { audio:'Audio', video:'Vidéo', image:'Image', document:'Document' }[kind] ?? kind
}
function formatFileSize(bytes: number): string {
  if (bytes < 1024) return bytes + ' o'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' Ko'
  return (bytes / 1024 / 1024).toFixed(1) + ' Mo'
}
function formatDuration(sec: number): string {
  const m = Math.floor(sec / 60)
  const s = sec % 60
  return `${m}m ${String(s).padStart(2,'0')}s`
}

// ══════════════════════════════════════════════════════════════
// Gestion tableaux
// ══════════════════════════════════════════════════════════════
function addRow(arr: any[], tpl: object) { arr.push({ ...tpl }) }
function removeRow(arr: any[], i: number) { arr.splice(i, 1) }

// ══════════════════════════════════════════════════════════════
// Submit
// ══════════════════════════════════════════════════════════════
async function submit() {
  if (isLocked.value) return
  processing.value = true
  Object.keys(errors).forEach(k => delete errors[k])

  try {
    const url    = form.id ? (dynUrls.update ?? props.urlUpdate) : props.urlStore
    const method = form.id ? 'PUT' : 'POST'
    if (!url) { showToast('error', 'URL de sauvegarde indisponible.'); return }

    const res = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({
        mission_id:      props.missionId    ?? props.missionContext?.mission_id,
        assignment_id:   props.assignmentId ?? props.missionContext?.assignment_id,
        date_reunion:    form.date_reunion,
        heure_debut:     form.heure_debut,
        heure_fin:       form.heure_fin,
        lieu:            form.lieu,
        presidente_par:  form.presidente_par,
        methodologie:    form.methodologie,
        fait_par:        form.fait_par,
        revue_par:       form.revue_par,
        participants:    JSON.stringify(form.participants),
        objectifs:       JSON.stringify(form.objectifs),
        preoccupations:  JSON.stringify(form.preoccupations),
        signatures:      JSON.stringify(form.signatures),
        documents_ref:   JSON.stringify(form.documents_ref),
        // media_items : on sauvegarde les métadonnées (pas les blobs binaires)
        media_items:     JSON.stringify(form.mediaItems.map((m: any) => ({
          id: m.id, kind: m.kind, name: m.name,
          size_label: m.size_label, duration_label: m.duration_label,
          source: m.source, created_at: m.created_at, note: m.note,
          // blob_url non sérialisé — uniquement en mémoire session
        }))),
      }),
    })
    const d = await res.json()
    if (d.success || res.ok) {
      showToast('success', form.id ? 'PV mis à jour.' : 'PV créé.')
      if (d.form) {
        Object.assign(form, { id: d.form.id, code: d.form.code, validation_status: d.form.validation_status })
      }
      if (d.urlUpdate)    dynUrls.update    = d.urlUpdate
      if (d.urlSoumettre) dynUrls.soumettre = d.urlSoumettre
      if (d.urlValider)   dynUrls.valider   = d.urlValider
    } else {
      showToast('error', d.message ?? 'Erreur lors de l\'enregistrement.')
      if (d.errors) Object.assign(errors, d.errors)
    }
  } catch { showToast('error', 'Erreur réseau.') }
  finally { processing.value = false }
}

function annuler() { if (props.backUrl) router.visit(props.backUrl) }

async function soumettre() {
  processing.value = true
  try {
    const url = dynUrls.soumettre ?? props.urlSoumettre ?? ''
    const d = await (await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ mission_id: props.missionId, assignment_id: props.assignmentId }),
    })).json()
    if (d.success) { form.validation_status = 'in_review'; showToast('success', 'PV soumis pour validation.') }
    else showToast('error', d.error ?? 'Erreur')
  } catch { showToast('error', 'Erreur réseau') }
  processing.value = false
}

async function valider(action: string, note?: string) {
  processing.value = true
  try {
    const url = dynUrls.valider ?? props.urlValider ?? ''
    const d = await (await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ mission_id: props.missionId, assignment_id: props.assignmentId, action, note }),
    })).json()
    if (d.success) {
      form.validation_status = d.status
      showToast('success', action === 'validate' ? 'PV validé ✓' : 'Rejeté.')
    } else showToast('error', d.error ?? 'Erreur')
  } catch { showToast('error', 'Erreur réseau') }
  processing.value = false
}

function promptReject() {
  const n = prompt('Motif du rejet (obligatoire) :', '')
  if (!n?.trim()) return
  valider('reject', n.trim())
}

// ══════════════════════════════════════════════════════════════
// Helpers
// ══════════════════════════════════════════════════════════════
function showToast(t: string, m: string, dur = 4000) {
  if (_tt) clearTimeout(_tt)
  toast.value = { show: true, type: t, msg: m }
  _tt = setTimeout(() => { toast.value.show = false }, dur)
}
function csrf() {
  return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? ''
}
function vstLbl(s: string)  { return ({ draft:'Brouillon', in_review:'En attente', validated:'Validé ✓' } as any)[s] ?? s }
function vstIcon(s: string) { return ({ draft:'ti ti-pencil', in_review:'ti ti-clock', validated:'ti ti-circle-check' } as any)[s] ?? 'ti ti-circle' }
function formatDate(d: string) {
  if (!d) return ''
  try { return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) } catch { return d }
}
function presenceLabel(v: string) { return ({ present:'Présent', excused:'Excusé', absent:'Absent' } as any)[v] ?? v ?? 'Présent' }
function niveauLabel(v: string)   { return ({ faible:'Faible', moyen:'Moyen', eleve:'Élevé', critique:'Critique' } as any)[v] ?? v ?? '—' }
function statutLabel(v: string)   { return ({ ouvert:'Ouvert', en_cours:'En cours', clos:'Clos' } as any)[v] ?? v ?? '—' }

function docTypeLabel(v: string) {
  return ({ loi:'Loi/Décret', circulaire:'Circulaire', guide:'Guide', procedure:'Procédure', norme:'Norme', autre:'Autre' } as any)[v] ?? v ?? '—'
}

// ══════════════════════════════════════════════════════════════
// AUTOSAVE — toutes les 90 secondes si des modifications en cours
// ══════════════════════════════════════════════════════════════
let _autoSaveInterval: any = null
let _lastSavedHash = ''

function formHash(): string {
  return JSON.stringify({
    date_reunion: form.date_reunion, lieu: form.lieu,
    methodologie: form.methodologie,
    participants: form.participants.length,
    objectifs: form.objectifs.length,
    preoccupations: form.preoccupations.length,
    mediaItems: form.mediaItems.length,
    documents_ref: form.documents_ref.length,
  })
}

async function autoSave() {
  if (isLocked.value) return
  if (!form.id && !form.date_reunion && !form.lieu) return // rien à sauvegarder
  const h = formHash()
  if (h === _lastSavedHash) return // pas de changement
  try {
    await submit()
    _lastSavedHash = formHash()
  } catch { /* silencieux */ }
}

// Démarrer autosave après mount
onMounted(() => {
  _lastSavedHash = formHash()
  _autoSaveInterval = setInterval(autoSave, 90_000) // 90 secondes
})

onBeforeUnmount(() => {
  if (_tt) clearTimeout(_tt)
  if (_autoSaveInterval) clearInterval(_autoSaveInterval)
  // Arrêter enregistrement si actif
  if (recorder.active) stopRecord()
  // Révoquer les blob URLs pour libérer mémoire
  form.mediaItems.forEach((m: any) => { if (m.blob_url) URL.revokeObjectURL(m.blob_url) })
})
</script>

<style scoped>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

.frl-shell { display: flex; flex-direction: column; min-height: 100vh; font-family: 'Segoe UI', system-ui, sans-serif; background: #f0f4f8; }

/* ── Header ─────────────────────────────────────────── */
.frl-header {
  position: sticky; top: 0; z-index: 100;
  background: #fff; border-bottom: 1px solid #e2e8f0;
  box-shadow: 0 1px 4px rgba(0,0,0,.06); padding: 0 18px;
}
.frl-hrow { display: flex; align-items: flex-start; gap: 10px; padding: 10px 0 6px; }
.frl-back {
  display: flex; align-items: center; justify-content: center;
  width: 32px; height: 32px; border-radius: 7px;
  background: #f1f5f9; border: 1px solid #e2e8f0;
  color: #64748b; text-decoration: none; flex-shrink: 0; margin-top: 2px; font-size: .85rem;
}
.frl-back:hover { background: #1565C0; color: #fff; border-color: #1565C0; }
.frl-hinfo { flex: 1; min-width: 0; }
.frl-chips { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; margin-bottom: 3px; }
.frl-code { font-family: monospace; font-size: .66rem; font-weight: 700; background: #1e293b; color: #fff; padding: 2px 7px; border-radius: 4px; }
.frl-code-sm { font-family: monospace; font-size: .7rem; color: #1565C0; font-weight: 700; }
.frl-vst {
  display: inline-flex; align-items: center; gap: 3px;
  font-size: .62rem; font-weight: 700; padding: 2px 7px; border-radius: 10px;
}
.fvsc-draft     { background: #f1f5f9; color: #64748b; }
.fvsc-in_review { background: #e3f2fd; color: #1565C0; }
.fvsc-validated { background: #dcfce7; color: #15803d; }
.frl-typechip { font-size: .62rem; font-weight: 700; padding: 2px 8px; border-radius: 10px; background: #fff7ed; color: #c2410c; display: inline-flex; align-items: center; gap: 3px; }
.frl-rolechip { font-size: .6rem; font-weight: 700; padding: 2px 7px; border-radius: 10px; }
.rc-DM { background: rgba(251,191,36,.18); color: #d97706; }
.rc-CM { background: rgba(21,101,192,.12); color: #1565C0; }
.rc-AS { background: rgba(22,163,74,.12); color: #15803d; }
.rc-AJ { background: rgba(124,58,237,.12); color: #6d28d9; }
.frl-progchip { font-size: .62rem; font-weight: 700; padding: 2px 9px; border-radius: 10px; display: inline-flex; align-items: center; gap: 3px; }
.prog-PTCI    { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.prog-PTCONF  { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
.prog-PTMAR   { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
.prog-PTTRANS { background: #fdf4ff; color: #7c3aed; border: 1px solid #e9d5ff; }
.prog-none    { background: #fff1f2; color: #9f1239; border: 1px solid #fecdd3; }
.frl-title { font-size: .9rem; font-weight: 800; color: #1a1a2e; }
.frl-meta  { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; font-size: .68rem; color: #64748b; margin-top: 2px; }
.frl-meta span { display: inline-flex; align-items: center; gap: 3px; }
.frl-banner {
  display: flex; align-items: center; gap: 8px;
  padding: 6px 0 8px; font-size: .75rem; font-weight: 500;
  border-top: 1px solid #f1f5f9; flex-wrap: wrap;
}
.frl-banner-lock   { color: #15803d; }
.frl-banner-review { color: #1565C0; }
.frl-banner-reject { color: #dc2626; }

/* Barre programmes */
.frl-prog-bar {
  display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
  padding: 6px 0 9px; border-top: 1px solid #f1f5f9; font-size: .68rem;
}
.frl-prog-lbl { color: #64748b; font-weight: 600; white-space: nowrap; }
.frl-prog-badges { display: flex; gap: 5px; flex-wrap: wrap; }
.frl-prog-badge {
  display: inline-flex; align-items: center; gap: 3px;
  font-size: .6rem; font-weight: 700; padding: 2px 7px; border-radius: 8px;
}
.pb-exists { background: #dcfce7; color: #15803d; }
.pb-missing { background: #f1f5f9; color: #9ca3af; }
.pb-status { font-weight: 400; font-size: .55rem; margin-left: 2px; opacity: .8; }
.frl-prog-src { font-size: .67rem; color: #1565C0; font-weight: 600; margin-left: auto; }

/* ── Body ──────────────────────────────────────────── */
.frl-body { flex: 1; padding: 18px; display: flex; flex-direction: column; gap: 14px; }

/* ── En-tête PV style Excel ─────────────────────────── */
.pv-entete {
  display: flex; justify-content: space-between; align-items: stretch;
  background: #1f3864; border-radius: 8px; overflow: hidden; gap: 0;
}
.pv-entete-left {
  flex: 1; padding: 16px 20px; display: flex; flex-direction: column; justify-content: center;
}
.pv-org-label { font-size: .65rem; font-weight: 700; color: rgba(255,255,255,.6); text-transform: uppercase; letter-spacing: .1em; margin-bottom: 6px; }
.pv-title-main { font-size: 1.1rem; font-weight: 900; color: #fff; text-transform: uppercase; letter-spacing: .04em; line-height: 1.2; }
.pv-title-sub  { font-size: .75rem; font-weight: 600; color: #93c5fd; margin-top: 4px; text-transform: uppercase; letter-spacing: .06em; }
.pv-entete-right {
  background: rgba(255,255,255,.08); padding: 14px 18px; min-width: 280px;
  display: flex; flex-direction: column; gap: 6px; justify-content: center;
  border-left: 1px solid rgba(255,255,255,.1);
}
.pv-meta-row { display: flex; align-items: center; gap: 8px; font-size: .72rem; }
.pv-meta-lbl { color: rgba(255,255,255,.6); font-weight: 600; white-space: nowrap; min-width: 85px; }
.pv-meta-val { color: #fff; font-weight: 500; }

/* ── Sections ──────────────────────────────────────── */
.pv-section { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
.pv-sec-hdr {
  display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;
  background: #1f3864; padding: 8px 14px;
  font-size: .73rem; font-weight: 800; color: #fff; text-transform: uppercase; letter-spacing: .04em;
}
.pv-sec-hdr-meta { display: flex; align-items: center; gap: 8px; }
.pv-prog-tag { font-size: .62rem; font-weight: 600; background: rgba(255,255,255,.18); padding: 2px 8px; border-radius: 8px; display: inline-flex; align-items: center; gap: 4px; color: #fff; }
.pv-warn-tag { font-size: .62rem; font-weight: 600; background: #fef9c3; color: #854d0e; padding: 2px 8px; border-radius: 8px; display: inline-flex; align-items: center; gap: 4px; }
.pv-add-btn {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: .65rem; font-weight: 700; padding: 3px 10px; border-radius: 6px;
  background: rgba(255,255,255,.18); color: #fff; border: 1px solid rgba(255,255,255,.25);
  cursor: pointer; font-family: inherit;
}
.pv-add-btn:hover { background: rgba(255,255,255,.3); }
.pv-sec-body { padding: 14px; display: flex; flex-direction: column; gap: 10px; }
.pv-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.pv-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
@media (max-width: 768px) { .pv-grid-2, .pv-grid-3 { grid-template-columns: 1fr; } }

/* ── Champs ─────────────────────────────────────────── */
.frl-field { display: flex; flex-direction: column; }
.frl-field-i { display: flex; align-items: center; gap: 8px; }
.frl-lbl { font-size: .7rem; font-weight: 600; color: #475569; margin-bottom: 3px; display: block; }
.req { color: #dc3545; }
.frl-inp {
  width: 100%; border: 1px solid #d1d5db; border-radius: 6px;
  padding: 6px 10px; font-size: .8rem; color: #1a1a2e;
  background: #fff; outline: none; font-family: inherit; transition: border-color .12s;
}
.frl-inp:focus { border-color: #1f3864; box-shadow: 0 0 0 3px rgba(31,56,100,.08); }
.frl-inp:disabled { background: #f8fafc; color: #64748b; }
.frl-inp.err { border-color: #dc3545; }
.frl-err { font-size: .67rem; color: #dc3545; margin-top: 2px; }
.frl-ta  { resize: vertical; min-height: 80px; }
.frl-inp-sm { width: 160px; }
.frl-ro-val { font-size: .78rem; color: #374151; }

/* ── Tables ─────────────────────────────────────────── */
.pv-tbl-wrap { overflow-x: auto; }
.pv-tbl-scroll { max-height: 500px; overflow-y: auto; }
.pv-tbl { width: 100%; border-collapse: collapse; font-size: .78rem; }
.pv-tbl thead th {
  background: #1e3a5f; color: rgba(255,255,255,.9);
  font-size: .63rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .04em; padding: 7px 10px; white-space: nowrap; position: sticky; top: 0; z-index: 2;
}
.th-sub { font-size: .56rem; font-weight: 400; opacity: .7; text-transform: none; margin-top: 1px; }
.pv-tbl tbody td { padding: 5px 9px; border: 1px solid #e9ecef; vertical-align: middle; }
.pv-tbl tbody tr:hover td { background: #f8fafc; }
.tr-alt td { background: #f8fafc; }
.pv-tbl tbody tr.tr-auditeur td { background: #f0fdf4; }
.tr-axe td {
  background: #eff6ff; color: #1d4ed8; font-size: .68rem; font-weight: 700;
  padding: 4px 10px; border: 1px solid #bfdbfe;
}
.pv-empty { text-align: center; color: #adb5bd; font-size: .74rem; padding: 16px !important; }
.pv-nodata {
  display: flex; align-items: flex-start; gap: 10px; padding: 14px 16px;
  background: #fffbeb; border-top: 1px solid #fde68a;
  font-size: .77rem; color: #92400e;
}
.pv-nodata i { font-size: .9rem; flex-shrink: 0; margin-top: 1px; }
.pv-nodata-info { background: #eff6ff; border-top-color: #bfdbfe; color: #1e40af; }
.tc { text-align: center; }
.muted { color: #94a3b8; }
.fw  { font-weight: 600; }
.pv-pre { white-space: pre-wrap; font-size: .75rem; color: #374151; line-height: 1.5; }
.pv-tdinp {
  width: 100%; border: none; outline: none; background: transparent;
  font-size: .78rem; font-family: inherit; color: #1a1a2e; padding: 0;
}
.pv-tdinp:focus { background: #fffbeb; }
.pv-ta { resize: vertical; min-height: 36px; }
.pv-sel { border: 1px solid #e5e7eb; border-radius: 4px; background: #fff; font-size: .75rem; padding: 2px 5px; }
.pv-delbtn { background: none; border: none; cursor: pointer; color: #ef4444; font-size: .72rem; padding: 2px 4px; }
.pv-delbtn:hover { color: #b91c1c; }

.pv-pres { font-size: .65rem; font-weight: 700; padding: 2px 7px; border-radius: 8px; display: inline-block; }
.pv-pres-ok, .pv-pres-present { background: #dcfce7; color: #15803d; }
.pv-pres-excused { background: #fef9c3; color: #854d0e; }
.pv-pres-absent  { background: #fee2e2; color: #991b1b; }

.pv-niveau { font-size: .65rem; font-weight: 700; padding: 2px 7px; border-radius: 8px; display: inline-block; }
.niv-faible   { background: #dcfce7; color: #15803d; }
.niv-moyen    { background: #fef9c3; color: #854d0e; }
.niv-eleve    { background: #ffedd5; color: #9a3412; }
.niv-critique { background: #fee2e2; color: #991b1b; }

.pv-statut { font-size: .65rem; font-weight: 700; padding: 2px 7px; border-radius: 8px; display: inline-block; }
.st-ouvert   { background: #fee2e2; color: #991b1b; }
.st-en_cours { background: #fef9c3; color: #854d0e; }
.st-clos     { background: #dcfce7; color: #15803d; }

/* Objectifs col */
.td-objectif { min-width: 180px; }

/* ── Signatures style Excel ─────────────────────────── */
.pv-signatures {
  display: grid; grid-template-columns: 1fr 1fr 1fr;
  border: 1px solid #1f3864; overflow: hidden; border-radius: 0;
}
@media (max-width: 768px) { .pv-signatures { grid-template-columns: 1fr; } }
.pv-sig-col { border-right: 1px solid #1f3864; }
.pv-sig-col:last-child { border-right: none; }
.pv-sig-hdr { background: #1f3864; color: #fff; font-size: .68rem; font-weight: 700; text-align: center; padding: 7px 10px; text-transform: uppercase; letter-spacing: .05em; }
.pv-sig-body { padding: 12px 14px; display: flex; flex-direction: column; gap: 8px; }
.pv-sig-field { display: flex; align-items: center; gap: 6px; font-size: .75rem; }
.pv-sig-lbl { font-weight: 700; color: #374151; white-space: nowrap; flex-shrink: 0; font-size: .7rem; }
.pv-sig-inp {
  flex: 1; border: none; border-bottom: 1px dashed #d1d5db;
  padding: 2px 4px; font-size: .75rem; font-family: inherit;
  outline: none; background: transparent; color: #1a1a2e;
}
.pv-sig-val { flex: 1; font-size: .75rem; color: #374151; }
.pv-sig-zone { display: flex; align-items: flex-end; gap: 8px; }
.pv-sig-lbl-sm { font-size: .65rem; font-weight: 600; color: #6b7280; white-space: nowrap; }
.pv-sig-space { flex: 1; height: 48px; border-bottom: 1px solid #d1d5db; }

.pv-confidential {
  display: flex; align-items: center; gap: 6px;
  background: #1f3864; color: rgba(255,255,255,.8);
  font-size: .68rem; font-style: italic; padding: 7px 14px;
}

/* ── Footer ─────────────────────────────────────────── */
.frl-footer {
  display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
  background: #fff; border: 1px solid #e2e8f0; border-radius: 8px;
  padding: 12px 16px; box-shadow: 0 1px 4px rgba(0,0,0,.05);
}
.frl-footer-meta { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
.frl-footer-acts { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-left: auto; }

/* ── Boutons ─────────────────────────────────────────── */
.frl-btn {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 7px 14px; border-radius: 7px; font-size: .75rem; font-weight: 600;
  border: 1px solid transparent; cursor: pointer; font-family: inherit; transition: all .12s;
}
.frl-btn-ghost    { background: transparent; color: #64748b; border-color: #d1d5db; }
.frl-btn-ghost:hover { background: #f1f5f9; }
.frl-btn-save     { background: #1f3864; color: #fff; }
.frl-btn-save:hover { background: #162d4e; }
.frl-btn-submit   { background: #0f766e; color: #fff; }
.frl-btn-submit:hover { background: #0d6460; }
.frl-btn-validate { background: #15803d; color: #fff; }
.frl-btn-validate:hover { background: #166534; }
.frl-btn-reject   { background: #dc2626; color: #fff; }
.frl-btn-reject:hover { background: #b91c1c; }
.frl-btn:disabled { opacity: .45; cursor: not-allowed; }
.frl-spin { width: 12px; height: 12px; border: 2px solid rgba(255,255,255,.4); border-top-color: #fff; border-radius: 50%; animation: spin .6s linear infinite; flex-shrink: 0; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Badges programmes cliquables ───────────────────── */
.frl-prog-btn {
  border: 1.5px solid transparent;
  cursor: pointer; font-family: inherit;
  transition: all .15s; position: relative;
}
.frl-prog-btn.pb-exists:hover {
  transform: translateY(-1px);
  box-shadow: 0 3px 10px rgba(0,0,0,.12);
  filter: brightness(1.08);
}
.frl-prog-btn.pb-active {
  outline: 2px solid #1f3864;
  outline-offset: 2px;
  font-weight: 800;
}
.frl-prog-btn.pb-disabled {
  cursor: not-allowed; opacity: .45; pointer-events: none;
}
.pb-active-dot {
  position: absolute; top: -3px; right: -3px;
  width: 8px; height: 8px; background: #1f3864;
  border-radius: 50%; border: 1.5px solid #fff;
}
.pb-spin {
  display: inline-block; width: 10px; height: 10px;
  border: 2px solid rgba(0,0,0,.2); border-top-color: #1f3864;
  border-radius: 50%; animation: spin .6s linear infinite; flex-shrink: 0;
}
.pb-spin-sm { width: 8px; height: 8px; }
.frl-prog-loading {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: .65rem; color: #1f3864; font-weight: 600;
}

/* ── Procédures dans étapes/travaux ────────────────── */
.td-etapes { vertical-align: top; }
.fw-sm { font-weight: 600; font-size: .76rem; }

.proc-block {
  margin-top: 6px; border: 1px solid #e2e8f0; border-radius: 6px;
  background: #f8fafc; overflow: hidden;
}
.proc-hdr {
  display: flex; align-items: center; gap: 6px;
  padding: 4px 8px; background: #1e3a5f; color: #fff;
  font-size: .62rem; font-weight: 700;
}
.proc-toggle {
  margin-left: auto; font-size: .58rem; font-weight: 600;
  background: rgba(255,255,255,.2); border: none; color: #fff;
  padding: 1px 6px; border-radius: 4px; cursor: pointer; font-family: inherit;
}
.proc-toggle:hover { background: rgba(255,255,255,.35); }
.proc-list { list-style: none; margin: 0; padding: 4px 6px 6px; display: flex; flex-direction: column; gap: 3px; }
.proc-item {
  display: flex; align-items: center; gap: 5px; font-size: .72rem;
}
.proc-num { font-size: .6rem; font-weight: 700; color: #1f3864; flex-shrink: 0; min-width: 14px; }
.proc-inp {
  flex: 1; border: none; border-bottom: 1px dashed #d1d5db;
  font-size: .72rem; font-family: inherit; padding: 1px 3px;
  background: transparent; outline: none; color: #1a1a2e;
}
.proc-inp:focus { border-bottom-color: #1f3864; background: #fffbeb; }
.proc-del {
  background: none; border: none; cursor: pointer; color: #94a3b8;
  font-size: .65rem; padding: 1px 3px; flex-shrink: 0;
}
.proc-del:hover { color: #dc2626; }
.proc-add {
  display: inline-flex; align-items: center; gap: 3px;
  font-size: .65rem; font-weight: 600; color: #1f3864;
  background: none; border: none; cursor: pointer; font-family: inherit;
  padding: 2px 0; margin-top: 2px;
}
.proc-add:hover { text-decoration: underline; }
.proc-add-inline {
  display: inline-flex; align-items: center; gap: 3px;
  font-size: .62rem; font-weight: 600; color: #64748b;
  background: none; border: 1px dashed #e2e8f0; border-radius: 4px;
  cursor: pointer; font-family: inherit; padding: 2px 7px; margin-top: 4px;
}
.proc-add-inline:hover { color: #1f3864; border-color: #1f3864; }

/* Lecture seule procédures */
.proc-block-ro {
  margin-top: 5px; border-left: 2px solid #1e3a5f;
  padding-left: 8px;
}
.proc-hdr-ro {
  font-size: .6rem; font-weight: 700; color: #1e3a5f;
  text-transform: uppercase; letter-spacing: .05em; margin-bottom: 3px;
  display: flex; align-items: center; gap: 4px;
}
.proc-list-ro {
  margin: 0; padding-left: 14px;
  display: flex; flex-direction: column; gap: 2px;
}
.proc-list-ro li { font-size: .71rem; color: #475569; line-height: 1.4; }

/* ── Section 4 — Documents de référence ────────────── */
.doc-type-badge { font-size: .65rem; font-weight: 700; padding: 2px 7px; border-radius: 8px; display: inline-block; }
.dt-loi        { background: #eff6ff; color: #1d4ed8; }
.dt-circulaire { background: #fef9c3; color: #854d0e; }
.dt-guide      { background: #dcfce7; color: #15803d; }
.dt-procedure  { background: #fdf4ff; color: #7c3aed; }
.dt-norme      { background: #fff7ed; color: #c2410c; }
.dt-autre      { background: #f1f5f9; color: #64748b; }

/* ── Autosave indicator ─────────────────────────────── */
.autosave-indicator {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: .65rem; color: #64748b; font-style: italic;
  padding: 3px 9px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 20px;
}
.autosave-indicator i { color: #15803d; font-size: .7rem; }

/* ── Toast ────────────────────────────────────────────── */
.frl-toast {
  position: fixed; bottom: 20px; right: 20px; z-index: 9999;
  display: flex; align-items: center; gap: 8px;
  padding: 10px 18px; border-radius: 9px; font-size: .8rem; font-weight: 600;
  box-shadow: 0 4px 16px rgba(0,0,0,.18);
}
.toast-success { background: #15803d; color: #fff; }
.toast-error   { background: #dc2626; color: #fff; }
.toast-t-enter-active, .toast-t-leave-active { transition: all .22s; }
.toast-t-enter-from, .toast-t-leave-to { opacity: 0; transform: translateY(8px); }

/* ── Participants enrichis ───────────────────────────── */
.part-stats {
  display: flex; gap: 8px; flex-wrap: wrap;
  padding: 10px 14px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;
}
.part-stat {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 4px 12px; border-radius: 20px; font-size: .7rem; font-weight: 600;
}
.part-stat span { font-size: .85rem; font-weight: 800; }
.ps-present { background: #dcfce7; color: #15803d; }
.ps-excused { background: #fef9c3; color: #854d0e; }
.ps-absent  { background: #fee2e2; color: #991b1b; }
.ps-total   { background: #eff6ff; color: #1d4ed8; }

.part-badge-aud {
  display: inline-flex; align-items: center; justify-content: center;
  width: 22px; height: 22px; border-radius: 50%;
  background: #eff6ff; color: #1d4ed8; font-size: .6rem;
}
.rb-chip { font-size: .58rem; font-weight: 700; padding: 1px 5px; border-radius: 4px; }
.rbc-DM { background: #fef9c3; color: #a16207; }
.rbc-CM { background: #eff6ff; color: #1d4ed8; }
.rbc-AS { background: #dcfce7; color: #15803d; }
.rbc-AJ { background: #faf5ff; color: #7c3aed; }

.tr-absent td  { background: #fff5f5 !important; opacity: .75; }
.tr-excused td { background: #fffbeb !important; }

.part-sig-inp {
  width: 60px; border: 1px dashed #d1d5db; border-radius: 4px;
  text-align: center; font-size: .8rem; font-weight: 700; color: #1f3864;
  padding: 3px 4px; font-family: Georgia, serif; background: #fafbfc;
  outline: none;
}
.part-sig-inp:focus { border-color: #1f3864; background: #fff; }
.part-sig-box {
  min-width: 36px; height: 28px; border: 1px dashed #d1d5db; border-radius: 4px;
  display: flex; align-items: center; justify-content: center;
  font-size: .8rem; font-weight: 800; color: #1f3864; font-family: Georgia, serif;
  background: #f8fafc;
}

/* ── Section médias ─────────────────────────────────── */
.media-shell { padding: 14px; display: flex; flex-direction: column; gap: 12px; }

.media-toolbar {
  display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
  padding: 10px 12px; background: #f8fafc;
  border: 1px solid #e2e8f0; border-radius: 8px;
}

.media-rec-group { display: flex; align-items: center; gap: 6px; }

.media-rec-btn {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 7px 14px; border-radius: 20px; font-size: .74rem; font-weight: 700;
  border: 1.5px solid #dc2626; background: #fff0f0; color: #dc2626;
  cursor: pointer; font-family: inherit; transition: all .15s; position: relative;
}
.media-rec-btn:hover { background: #dc2626; color: #fff; }
.media-rec-btn.rec-active { background: #dc2626; color: #fff; animation: rec-pulse .9s ease infinite; }

.media-rec-btn-vid { border-color: #7c3aed; background: #faf5ff; color: #7c3aed; }
.media-rec-btn-vid:hover { background: #7c3aed; color: #fff; }
.media-rec-btn-vid.rec-active { background: #7c3aed; color: #fff; }

@keyframes rec-pulse { 0%,100% { box-shadow: 0 0 0 0 rgba(220,38,38,.4); } 50% { box-shadow: 0 0 0 6px rgba(220,38,38,0); } }

.rec-dot {
  width: 8px; height: 8px; border-radius: 50%; background: #fff;
  animation: blink .8s step-end infinite; flex-shrink: 0;
}
.rec-dot-vid { background: #e9d5ff; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0} }

.rec-timer {
  font-size: .72rem; font-weight: 800; color: #dc2626;
  font-family: 'JetBrains Mono', monospace; letter-spacing: .05em;
}
.rec-timer-vid { color: #7c3aed; }

.media-sep { width: 1px; height: 28px; background: #e2e8f0; margin: 0 4px; flex-shrink: 0; }

.media-attach-btn {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 7px 14px; border-radius: 20px; font-size: .74rem; font-weight: 700;
  border: 1.5px solid #1f3864; background: #fff; color: #1f3864;
  cursor: pointer; font-family: inherit; transition: all .15s;
}
.media-attach-btn:hover { background: #1f3864; color: #fff; }
.media-file-input { display: none; }

.rec-live-preview {
  width: 180px; height: 100px; border-radius: 8px; border: 2px solid #7c3aed;
  object-fit: cover; background: #000; flex-shrink: 0;
}

.media-err {
  display: flex; align-items: center; gap: 8px;
  padding: 8px 12px; background: #fff0f0; border: 1px solid #fecaca; border-radius: 7px;
  font-size: .75rem; color: #dc2626; font-weight: 500;
}

.media-empty {
  display: flex; flex-direction: column; align-items: center; gap: 6px;
  padding: 30px 20px; color: #94a3b8; text-align: center;
}
.media-empty i   { font-size: 2rem; }
.media-empty p   { font-size: .8rem; font-weight: 600; color: #64748b; }
.media-empty span { font-size: .72rem; }

.media-list { display: flex; flex-direction: column; gap: 8px; }

.media-item {
  display: flex; align-items: flex-start; gap: 12px;
  padding: 10px 12px; border-radius: 9px;
  border: 1px solid #e2e8f0; background: #fff;
  transition: box-shadow .12s;
}
.media-item:hover { box-shadow: 0 2px 10px rgba(0,0,0,.07); }
.mi-audio    { border-left: 3px solid #dc2626; }
.mi-video    { border-left: 3px solid #7c3aed; }
.mi-image    { border-left: 3px solid #0891b2; }
.mi-document { border-left: 3px solid #1f3864; }

.mi-icon {
  width: 36px; height: 36px; border-radius: 9px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: 1rem;
}
.mi-audio    > .mi-icon { background: #fff0f0; color: #dc2626; }
.mi-video    > .mi-icon { background: #faf5ff; color: #7c3aed; }
.mi-image    > .mi-icon { background: #ecfeff; color: #0891b2; }
.mi-document > .mi-icon { background: #eff6ff; color: #1f3864; }

.mi-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 4px; }
.mi-name { font-size: .78rem; font-weight: 700; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.mi-meta {
  display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
  font-size: .65rem; color: #64748b;
}
.mi-meta i { font-size: .6rem; }
.mi-type-badge { font-size: .58rem; font-weight: 700; padding: 1px 6px; border-radius: 8px; }
.mtb-audio    { background: #fff0f0; color: #dc2626; }
.mtb-video    { background: #faf5ff; color: #7c3aed; }
.mtb-image    { background: #ecfeff; color: #0891b2; }
.mtb-document { background: #eff6ff; color: #1f3864; }
.mi-recorded-badge { display: inline-flex; align-items: center; gap: 3px; background: #fee2e2; color: #dc2626; font-size: .58rem; font-weight: 700; padding: 1px 6px; border-radius: 8px; }
.mi-date { color: #94a3b8; }

.mi-note-inp {
  width: 100%; border: none; border-bottom: 1px dashed #e2e8f0;
  font-size: .72rem; color: #475569; background: transparent; outline: none; padding: 2px 0;
  font-family: inherit;
}
.mi-note-inp:focus { border-bottom-color: #1f3864; }
.mi-note-inp::placeholder { color: #cbd5e1; }
.mi-note-ro { font-size: .72rem; color: #64748b; font-style: italic; }

.mi-player { width: 100%; max-width: 360px; }
.mi-audio { width: 100%; height: 36px; }
.mi-video { width: 100%; max-height: 180px; border-radius: 6px; background: #000; }

.mi-actions { display: flex; flex-direction: column; gap: 5px; flex-shrink: 0; }
.mi-btn {
  display: inline-flex; align-items: center; justify-content: center;
  width: 30px; height: 30px; border-radius: 7px; border: 1px solid #e2e8f0;
  background: #f8fafc; color: #64748b; cursor: pointer; font-size: .78rem;
  text-decoration: none; transition: all .12s;
}
.mi-btn-dl:hover  { background: #1f3864; color: #fff; border-color: #1f3864; }
.mi-btn-del:hover { background: #dc2626; color: #fff; border-color: #dc2626; }
</style>