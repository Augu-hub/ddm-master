<template>
  <VerticalLayoutAudit>
    <div class="rcm-shell">

      <!-- ═══════════════════════════════════════════════════════════
           HEADER
      ═══════════════════════════════════════════════════════════ -->
      <header class="rcm-header">
        <div class="rcm-hrow">
          <a :href="props.backUrl" class="rcm-back"><i class="ti ti-arrow-left"></i></a>
          <div class="rcm-hinfo">
            <div class="rcm-chips">
              <code class="rcm-code">{{ form.code || 'RCM-AUTO' }}</code>
              <span class="rcm-chip" :class="`chip-${form.validation_status||'draft'}`">
                <i :class="vstIcon(form.validation_status||'draft')"></i>
                {{ vstLbl(form.validation_status||'draft') }}
              </span>
              <span class="rcm-chip chip-type">📋 RCM Marchés Publics</span>
              <span v-if="props.currentAuditor?.role" class="rcm-chip" :class="`chip-role-${props.currentAuditor.role}`">
                {{ roleLabel(props.currentAuditor.role) }}
              </span>
            </div>
            <h1 class="rcm-title">Référentiel de Contrôle — Marchés Publics</h1>
            <div class="rcm-meta">
              <span v-if="props.mission?.code_mission"><i class="ti ti-clipboard"></i>{{ props.mission.code_mission }}</span>
              <span v-if="props.mission?.libelle"><i class="ti ti-briefcase"></i>{{ props.mission.libelle }}</span>
              <span class="meta-pts"><i class="ti ti-list-check"></i>{{ totalCriteres }} point(s)</span>
              <span class="meta-ok"><i class="ti ti-circle-check"></i>{{ totalC }} conforme(s)</span>
              <span class="meta-nc"><i class="ti ti-circle-x"></i>{{ totalNC }} non conforme(s)</span>
              <span v-if="tauxGlobal>0" class="meta-tx">
                <i class="ti ti-chart-bar"></i>{{ (tauxGlobal*100).toFixed(0) }}% conformité
              </span>
            </div>
          </div>
        </div>
        <!-- Banderoles de statut -->
        <div v-if="form.validation_status==='validated'" class="rcm-banner banner-lock">
          <i class="ti ti-lock"></i> RCM <strong>validé définitivement</strong> — lecture seule
        </div>
        <div v-else-if="form.validation_status==='in_review'" class="rcm-banner banner-review">
          <i class="ti ti-clock"></i> Soumis pour validation
          <span v-if="props.canManage"> · DM/CM peut valider ou rejeter.</span>
        </div>
        <div v-else-if="form.validation_status==='draft'&&form.validation_note" class="rcm-banner banner-reject">
          <i class="ti ti-circle-x"></i> Rejeté — <em>{{ form.validation_note }}</em>
        </div>
      </header>

      <!-- ═══════════════════════════════════════════════════════════
           ONGLETS
      ═══════════════════════════════════════════════════════════ -->
      <div class="rcm-tabs">
        <button :class="['rtab', activePhase==='SYNTHESE'?'active':'']" @click="activePhase='SYNTHESE'">
          <i class="ti ti-chart-bar"></i> Synthèse
        </button>
        <button v-for="(ph, key) in PHASES" :key="key"
                :class="['rtab', activePhase===key?'active':'']"
                @click="activePhase=key">
          <span class="tab-icon">{{ ph.icon }}</span>
          <span class="tab-lbl">{{ phaseShort(key) }}</span>
          <span class="tab-score" :class="niveauMaitrise(statsPhase(key).taux).cls">
            {{ (statsPhase(key).taux*100).toFixed(0) }}%
          </span>
          <span v-if="affectations[key]" class="tab-aff">
            <i class="ti ti-user-check"></i>
          </span>
        </button>
        <!-- Panneau DM/CM : Affectation -->
        <button v-if="props.canManage"
                :class="['rtab rtab-mgmt', activePhase==='AFFECT'?'active':'']"
                @click="activePhase='AFFECT'">
          <i class="ti ti-users-group"></i> Affecter
          <span v-if="nbPhasesAffectees>0" class="tab-score niv-ex">{{ nbPhasesAffectees }}/{{ Object.keys(PHASES).length }}</span>
        </button>
      </div>

      <!-- ═══════════════════════════════════════════════════════════
           BODY
      ═══════════════════════════════════════════════════════════ -->
      <div class="rcm-body">

        <!-- ══════════════════════════════
             TABLEAU DE SYNTHÈSE
        ══════════════════════════════ -->
        <div v-show="activePhase==='SYNTHESE'" class="tab-content">
          <div class="synth-header">
            <div class="sh-titre">TABLEAU DE SYNTHÈSE — CONTRÔLE DES MARCHÉS PUBLICS</div>
            <div class="sh-sub">Décret N° 2009-277 du 25 août 2009 — Scoring automatique par phase</div>
          </div>

          <!-- Score global -->
          <div class="global-score">
            <div class="gs-ring" :class="niveauMaitrise(tauxGlobal).cls">
              <span class="gs-pct">{{ (tauxGlobal*100).toFixed(0) }}%</span>
              <span class="gs-lbl">Conformité<br/>globale</span>
            </div>
            <div class="gs-stats">
              <div class="gs-stat gs-c"><span class="gs-n">{{ totalC }}</span><span>Conforme</span></div>
              <div class="gs-stat gs-pc"><span class="gs-n">{{ totalPC }}</span><span>Part. conf.</span></div>
              <div class="gs-stat gs-nc"><span class="gs-n">{{ totalNC }}</span><span>Non conf.</span></div>
              <div class="gs-stat gs-ne"><span class="gs-n">{{ totalNE }}</span><span>Non évalué</span></div>
              <div class="gs-stat gs-na"><span class="gs-n">{{ totalNA }}</span><span>Non appl.</span></div>
            </div>
            <div class="gs-niveau" :class="niveauMaitrise(tauxGlobal).cls">
              <span>{{ niveauMaitrise(tauxGlobal).emoji }} {{ niveauMaitrise(tauxGlobal).label }}</span>
              <span class="gs-score">Score risque : {{ scoreRisqueTotal }}</span>
            </div>
          </div>

          <!-- Tableau par phase -->
          <div class="synth-tbl-wrap">
            <table class="synth-tbl">
              <thead>
                <tr>
                  <th class="st-phase">Phase du Cycle</th>
                  <th>Points</th><th>C</th><th>PC</th><th>NC</th><th>NE</th><th>NA</th>
                  <th>Taux</th><th>Niveau</th><th>Score Risque</th><th>Auditeur</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(ph, key) in PHASES" :key="key"
                    class="synth-row" @click="activePhase=key" style="cursor:pointer">
                  <td class="st-phase-val">
                    <span class="ph-icon">{{ ph.icon }}</span><span>{{ ph.label }}</span>
                  </td>
                  <td class="tc fw">{{ statsPhase(key).total }}</td>
                  <td class="tc c-c">{{ statsPhase(key).C }}</td>
                  <td class="tc c-pc">{{ statsPhase(key).PC }}</td>
                  <td class="tc c-nc">{{ statsPhase(key).NC }}</td>
                  <td class="tc c-ne">{{ statsPhase(key).NE }}</td>
                  <td class="tc c-na">{{ statsPhase(key).NA }}</td>
                  <td class="tc">
                    <div class="taux-bar">
                      <div class="taux-fill" :style="{width:(statsPhase(key).taux*100)+'%',background:niveauMaitrise(statsPhase(key).taux).color}"></div>
                      <span>{{ (statsPhase(key).taux*100).toFixed(0) }}%</span>
                    </div>
                  </td>
                  <td class="tc">
                    <span :class="['niveau-badge', niveauMaitrise(statsPhase(key).taux).cls]">
                      {{ niveauMaitrise(statsPhase(key).taux).emoji }} {{ niveauMaitrise(statsPhase(key).taux).label }}
                    </span>
                  </td>
                  <td class="tc" :class="statsPhase(key).scoreRisque>0?'c-nc':''">
                    {{ statsPhase(key).scoreRisque }}
                  </td>
                  <td class="tc sm">
                    <span v-if="affectations[key]" class="aud-badge">
                      {{ auditorInitials(affectations[key]) }}
                    </span>
                    <span v-else class="na-badge">—</span>
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="synth-total">
                  <td class="fw">TOTAL CONSOLIDÉ</td>
                  <td class="tc fw">{{ totalCriteres }}</td>
                  <td class="tc c-c fw">{{ totalC }}</td>
                  <td class="tc c-pc fw">{{ totalPC }}</td>
                  <td class="tc c-nc fw">{{ totalNC }}</td>
                  <td class="tc c-ne fw">{{ totalNE }}</td>
                  <td class="tc c-na fw">{{ totalNA }}</td>
                  <td class="tc fw">{{ (tauxGlobal*100).toFixed(0) }}%</td>
                  <td class="tc">
                    <span :class="['niveau-badge', niveauMaitrise(tauxGlobal).cls]">
                      {{ niveauMaitrise(tauxGlobal).emoji }} {{ niveauMaitrise(tauxGlobal).label }}
                    </span>
                  </td>
                  <td class="tc fw c-nc">{{ scoreRisqueTotal }}</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- Légendes -->
          <div class="legenda">
            <div class="leg-grp">
              <span class="leg-t">Qualification :</span>
              <span class="leg-item"><span class="q-c">C</span> Conforme</span>
              <span class="leg-item"><span class="q-pc">PC</span> Partiellement Conforme</span>
              <span class="leg-item"><span class="q-nc">NC</span> Non Conforme</span>
              <span class="leg-item"><span class="q-ne">NE</span> Non Évalué</span>
              <span class="leg-item"><span class="q-na">NA</span> Non Applicable</span>
            </div>
            <div class="leg-grp">
              <span class="leg-t">Niveau :</span>
              <span class="leg-item">🟢 Excellent ≥ 90%</span>
              <span class="leg-item">🟡 Bon ≥ 70%</span>
              <span class="leg-item">🟠 Insuffisant ≥ 50%</span>
              <span class="leg-item">🔴 Critique &lt; 50%</span>
            </div>
            <div class="leg-grp">
              <span class="leg-t">Score risque :</span>
              <span class="leg-item">NC × 3 + PC × 1</span>
            </div>
          </div>
        </div>

        <!-- ══════════════════════════════
             PHASES DE CONTRÔLE
        ══════════════════════════════ -->
        <template v-for="(ph, phKey) in PHASES" :key="phKey">
          <div v-show="activePhase===phKey" class="tab-content">

            <!-- En-tête phase -->
            <div class="phase-header" :style="{borderColor: ph.color}">
              <div class="ph-icon-lg">{{ ph.icon }}</div>
              <div class="ph-info">
                <div class="ph-titre">{{ ph.label }}</div>
                <div class="ph-ref">Décret N° 2009-277 · {{ getCriteresPhase(phKey).length }} points de contrôle</div>
              </div>
              <div class="ph-stats">
                <div v-for="(cnt, q) in {C:statsPhase(phKey).C, PC:statsPhase(phKey).PC, NC:statsPhase(phKey).NC}" :key="q" class="ph-stat">
                  <span :class="['ph-q', `q-${q.toLowerCase()}`]">{{ q }}</span>
                  <span class="ph-cnt">{{ cnt }}</span>
                </div>
                <div class="ph-taux" :class="niveauMaitrise(statsPhase(phKey).taux).cls">
                  {{ (statsPhase(phKey).taux*100).toFixed(0) }}%
                </div>
              </div>
            </div>

            <!-- Barre d'affectation (DM/CM) -->
            <div v-if="props.canManage && !isLocked" class="affect-bar">
              <i class="ti ti-user-check"></i>
              <span>Auditeur affecté à cette phase :</span>
              <select class="af-sel" v-model="affectations[phKey]" @change="markDirty">
                <option :value="null">— Non affecté —</option>
                <option v-for="aud in props.phaseAuditeurs" :key="aud.id" :value="aud.id">
                  {{ aud.full_name }} ({{ aud.role_code }})
                </option>
              </select>
              <span v-if="affectations[phKey]" class="af-badge">
                {{ auditorName(affectations[phKey]) }}
              </span>
            </div>
            <div v-else-if="affectations[phKey]" class="affect-bar affect-bar--ro">
              <i class="ti ti-user-check"></i>
              <span>Auditeur affecté :</span>
              <strong>{{ auditorName(affectations[phKey]) ?? '—' }}</strong>
            </div>

            <!-- Message si non affecté et auditeur non DM/CM -->
            <div v-if="!props.canManage && !affectations[phKey] && !isLocked" class="info-bar">
              <i class="ti ti-info-circle"></i>
              Cette phase n'est pas encore affectée. Vous pouvez saisir en attendant l'affectation par le DM/CM.
            </div>

            <!-- Tableau des critères -->
            <div class="crit-tbl-wrap">
              <table class="crit-tbl">
                <thead>
                  <tr class="crit-head">
                    <th class="ch ch-ref">Réf.</th>
                    <th class="ch ch-art">Art. régl.</th>
                    <th class="ch ch-proc">Intitulé Procédure</th>
                    <th class="ch ch-point">Point de Contrôle / Exigence</th>
                    <th class="ch ch-res">Résultat du Contrôle</th>
                    <th class="ch ch-preuves">Preuves obtenues</th>
                    <th class="ch ch-qual">Qualification</th>
                    <th class="ch ch-ecart">Écart / Constat</th>
                    <th class="ch ch-reco">Recommandation</th>
                    <th class="ch ch-prio">Priorité</th>
                    <th class="ch ch-resp">Responsable</th>
                    <th class="ch ch-ech">Échéance</th>
                    <th class="ch ch-statut">Statut Suivi</th>
                    <th class="ch ch-ia">🤖 IA</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="critere in getCriteresPhase(phKey)" :key="critere.ref_controle"
                      :class="['crit-row', `q-row-${(critere.qualification||'NE').toLowerCase()}`]">

                    <!-- A Réf -->
                    <td class="td-ref">
                      <span class="ref-badge">{{ critere.ref_controle }}</span>
                    </td>

                    <!-- B Article -->
                    <td class="td-art">
                      <span class="art-txt">{{ critere.ref_reglementaire }}</span>
                    </td>

                    <!-- C Intitulé -->
                    <td class="td-proc">
                      <span class="proc-txt fw">{{ critere.intitule_procedure }}</span>
                    </td>

                    <!-- D Point de contrôle -->
                    <td class="td-point">
                      <p class="point-txt">{{ critere.point_controle }}</p>
                      <details class="preuves-attendues">
                        <summary class="pa-toggle">
                          <i class="ti ti-file-check"></i> Preuves attendues
                        </summary>
                        <p class="pa-txt">{{ critere.preuves_attendues }}</p>
                      </details>
                    </td>

                    <!-- E Résultat (saisie) -->
                    <td class="td-res">
                      <textarea v-if="canEdit(critere, phKey)" class="c-ta"
                                v-model="critere.resultat_controle"
                                rows="3" placeholder="Résultat constaté…"
                                @change="markDirty"></textarea>
                      <p v-else class="c-ro sm">{{ critere.resultat_controle||'—' }}</p>
                    </td>

                    <!-- F Preuves obtenues -->
                    <td class="td-preuves">
                      <textarea v-if="canEdit(critere, phKey)" class="c-ta"
                                v-model="critere.preuves_obtenues"
                                rows="3" placeholder="Documents collectés…"
                                @change="markDirty"></textarea>
                      <p v-else class="c-ro sm">{{ critere.preuves_obtenues||'—' }}</p>
                    </td>

                    <!-- G Qualification -->
                    <td class="td-qual">
                      <select v-if="canEdit(critere, phKey)" class="q-sel"
                              v-model="critere.qualification"
                              @change="markDirty">
                        <option v-for="(lbl, key) in QUALIFICATIONS" :key="key" :value="key">{{ lbl }}</option>
                      </select>
                      <span v-else :class="['q-badge', `q-${(critere.qualification||'NE').toLowerCase()}`]">
                        {{ critere.qualification || 'NE' }}
                      </span>
                    </td>

                    <!-- H Écart -->
                    <td class="td-ecart">
                      <textarea v-if="canEdit(critere, phKey)" class="c-ta"
                                v-model="critere.ecart_constat"
                                :disabled="['C','NA'].includes(critere.qualification||'NE')"
                                rows="3" placeholder="Écart constaté…"
                                @change="markDirty"></textarea>
                      <p v-else class="c-ro sm">{{ critere.ecart_constat||'—' }}</p>
                    </td>

                    <!-- I Recommandation -->
                    <td class="td-reco">
                      <textarea v-if="canEdit(critere, phKey)" class="c-ta"
                                v-model="critere.recommandation"
                                :disabled="['C','NA'].includes(critere.qualification||'NE')"
                                rows="3" placeholder="Action corrective…"
                                @change="markDirty"></textarea>
                      <p v-else class="c-ro sm">{{ critere.recommandation||'—' }}</p>
                    </td>

                    <!-- J Priorité -->
                    <td class="td-prio">
                      <select v-if="canEdit(critere, phKey)" class="p-sel"
                              v-model="critere.priorite"
                              :disabled="['C','NA'].includes(critere.qualification||'NE')"
                              @change="markDirty">
                        <option value="">—</option>
                        <option v-for="(lbl, key) in PRIORITES" :key="key" :value="key">{{ key }}</option>
                      </select>
                      <span v-else :class="['prio-badge', `prio-${critere.priorite?.toLowerCase()||'nd'}`]">
                        {{ critere.priorite || '—' }}
                      </span>
                    </td>

                    <!-- K Responsable -->
                    <td class="td-resp">
                      <input v-if="canEdit(critere, phKey)" class="c-inp"
                             v-model="critere.responsable_action"
                             placeholder="Responsable…"
                             @change="markDirty"/>
                      <span v-else class="c-ro sm">{{ critere.responsable_action||'—' }}</span>
                    </td>

                    <!-- L Échéance -->
                    <td class="td-ech">
                      <input v-if="canEdit(critere, phKey)" type="date" class="c-inp"
                             v-model="critere.echeance"
                             @change="markDirty"/>
                      <span v-else class="c-ro sm">{{ critere.echeance||'—' }}</span>
                    </td>

                    <!-- M Statut suivi -->
                    <td class="td-statut">
                      <select v-if="canEdit(critere, phKey)" class="s-sel"
                              v-model="critere.statut_suivi"
                              @change="markDirty">
                        <option v-for="(lbl, key) in STATUTS_SUIVI" :key="key" :value="key">{{ lbl }}</option>
                      </select>
                      <span v-else :class="['statut-badge', `st-${critere.statut_suivi||'nd'}`]">
                        {{ STATUTS_SUIVI[critere.statut_suivi] || '—' }}
                      </span>
                    </td>

                    <!-- N Bouton IA -->
                    <td class="td-ia">
                      <button v-if="canEdit(critere, phKey) && ['NC','PC'].includes(critere.qualification||'NE')"
                              class="btn-ia"
                              :class="{loading: iaLoading === critere.ref_controle}"
                              @click="demanderSuggestionIA(critere)"
                              title="Obtenir une suggestion IA pour la recommandation">
                        <span v-if="iaLoading === critere.ref_controle" class="spin-s"></span>
                        <i v-else class="ti ti-sparkles"></i>
                      </button>
                      <span v-else class="ia-nd">—</span>
                    </td>

                  </tr>
                </tbody>
                <tfoot>
                  <tr class="synth-phase-row">
                    <td colspan="6" class="fw">SYNTHÈSE — {{ ph.label }}</td>
                    <td class="tc">
                      <span :class="['q-badge', niveauMaitrise(statsPhase(phKey).taux).cls]">
                        {{ (statsPhase(phKey).taux*100).toFixed(0) }}%
                      </span>
                    </td>
                    <td colspan="7" class="sm">
                      NC={{ statsPhase(phKey).NC }} · PC={{ statsPhase(phKey).PC }} · Score={{ statsPhase(phKey).scoreRisque }}
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </template>

        <!-- ══════════════════════════════
             PANNEAU AFFECTATION (DM/CM)
        ══════════════════════════════ -->
        <div v-show="activePhase==='AFFECT'" class="tab-content">
          <div class="affect-panel-header">
            <div class="ap-titre">
              <i class="ti ti-users-group"></i>
              Affectation des phases aux auditeurs
            </div>
            <div class="ap-sub">
              En tant que {{ props.canManage ? 'DM/CM' : 'responsable' }}, vous affectez chaque phase à un auditeur spécifique.
              L'auditeur affecté peut saisir les résultats des contrôles de sa phase.
            </div>
            <div class="ap-progress">
              <div class="ap-prog-bar">
                <div class="ap-prog-fill" :style="{width: (nbPhasesAffectees/Object.keys(PHASES).length*100)+'%'}"></div>
              </div>
              <span class="ap-prog-lbl">{{ nbPhasesAffectees }}/{{ Object.keys(PHASES).length }} phases affectées</span>
            </div>
          </div>

          <!-- Grille d'affectation -->
          <div class="affect-grid">
            <div v-for="(ph, key) in PHASES" :key="key" class="affect-card">
              <div class="ac-hdr" :style="{background: ph.color}">
                <span class="ac-icon">{{ ph.icon }}</span>
                <div class="ac-hdr-info">
                  <span class="ac-hdr-label">{{ ph.label }}</span>
                  <span class="ac-hdr-pts">{{ getCriteresPhase(key).length }} points · {{ (statsPhase(key).taux*100).toFixed(0) }}%</span>
                </div>
                <span :class="['ac-niv', niveauMaitrise(statsPhase(key).taux).cls]">
                  {{ niveauMaitrise(statsPhase(key).taux).emoji }}
                </span>
              </div>
              <div class="ac-body">
                <!-- Stats de la phase -->
                <div class="ac-stats-row">
                  <span class="acs-c">C: {{ statsPhase(key).C }}</span>
                  <span class="acs-pc">PC: {{ statsPhase(key).PC }}</span>
                  <span class="acs-nc">NC: {{ statsPhase(key).NC }}</span>
                  <span class="acs-ne">NE: {{ statsPhase(key).NE }}</span>
                </div>
                <!-- Sélect auditeur -->
                <div class="ac-sel-wrap">
                  <label class="ac-lbl">Auditeur affecté</label>
                  <select v-if="props.canManage && !isLocked"
                          class="af-sel-full" v-model="affectations[key]"
                          @change="markDirty">
                    <option :value="null">— Non affecté —</option>
                    <option v-for="aud in props.phaseAuditeurs" :key="aud.id" :value="aud.id">
                      {{ aud.full_name }} ({{ aud.role_code }})
                    </option>
                  </select>
                  <div v-else class="af-ro">
                    {{ affectations[key] ? auditorName(affectations[key]) : 'Non affecté' }}
                  </div>
                </div>
                <!-- Carte auditeur si affecté -->
                <div v-if="affectations[key]" class="ac-aud">
                  <span class="aud-av" :class="`av-${auditorByKey(affectations[key])?.role_code}`">
                    {{ auditorByKey(affectations[key])?.initials ?? '?' }}
                  </span>
                  <div class="aud-info">
                    <span class="aud-name">{{ auditorName(affectations[key]) }}</span>
                    <span class="aud-role">{{ auditorByKey(affectations[key])?.role_label }}</span>
                  </div>
                  <button v-if="props.canManage && !isLocked" class="btn-remove-aff"
                          @click="affectations[key]=null; markDirty()" title="Retirer l'affectation">
                    <i class="ti ti-x"></i>
                  </button>
                </div>
                <div v-else class="ac-empty">
                  <i class="ti ti-user-question"></i> Phase non affectée
                </div>
              </div>
            </div>
          </div>

          <!-- Récapitulatif des auditeurs -->
          <div class="recap-auditeurs">
            <div class="recap-titre"><i class="ti ti-users"></i> Charge de travail par auditeur</div>
            <div class="recap-grid">
              <div v-for="aud in props.phaseAuditeurs" :key="aud.id" class="recap-card">
                <span class="aud-av" :class="`av-${aud.role_code}`">{{ aud.initials }}</span>
                <div class="recap-info">
                  <span class="aud-name">{{ aud.full_name }}</span>
                  <span class="aud-role">{{ aud.role_label }}</span>
                  <span class="aud-charge">
                    {{ phasesDeAuditeur(aud.id).length }} phase(s) :
                    {{ phasesDeAuditeur(aud.id).map(k => phaseShort(k)).join(', ') || 'Aucune' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div><!-- /rcm-body -->

      <!-- ═══════════════════════════════════════════════════════════
           FOOTER
      ═══════════════════════════════════════════════════════════ -->
      <footer class="rcm-footer">
        <div class="footer-left">
          <button v-if="!isLocked" type="button" class="btn btn-ghost btn-sm"
                  :disabled="processing" @click="annuler">
            <i class="ti ti-x"></i> Annuler
          </button>
          <button v-if="!isLocked" type="button" class="btn btn-save btn-sm"
                  :disabled="processing||!dirty" @click="submit">
            <span v-if="processing" class="spin-s"></span>
            <i v-else class="ti ti-device-floppy"></i>
            {{ form.id ? 'Mettre à jour' : 'Enregistrer' }}
            <span v-if="dirty" class="dirty-dot"></span>
          </button>
        </div>
        <div class="footer-mid">
          <span v-if="form.id" class="saved-code"><i class="ti ti-check"></i> {{ form.code }}</span>
          <span class="stat-lbl">{{ totalCriteres }} pts · {{ (tauxGlobal*100).toFixed(0) }}% conformité</span>
        </div>
        <div class="footer-right">
          <button v-if="form.id && form.validation_status==='draft'" type="button"
                  class="btn btn-sub btn-sm" :disabled="processing" @click="soumettre">
            <i class="ti ti-send"></i> Soumettre
          </button>
          <template v-if="props.canManage && form.validation_status==='in_review'">
            <button type="button" class="btn btn-ok btn-sm" :disabled="processing" @click="valider('validate')">
              <i class="ti ti-circle-check"></i> Valider
            </button>
            <button type="button" class="btn btn-rej btn-sm" :disabled="processing" @click="promptReject">
              <i class="ti ti-circle-x"></i> Rejeter
            </button>
          </template>
        </div>
      </footer>

    </div><!-- /rcm-shell -->

    <!-- ═══ MODAL SUGGESTION IA ═══ -->
    <Teleport to="body">
      <Transition name="modal-t">
        <div v-if="iaModal.show" class="ia-overlay" @click.self="closeIaModal">
          <div class="ia-modal">
            <div class="ia-modal-hdr">
              <div class="ia-modal-title">
                <i class="ti ti-sparkles"></i>
                Suggestion IA — Recommandation
              </div>
              <div class="ia-modal-ref">{{ iaModal.critere?.ref_controle }}</div>
              <button class="ia-modal-close" @click="closeIaModal"><i class="ti ti-x"></i></button>
            </div>

            <div class="ia-modal-body">
              <!-- Contexte -->
              <div class="ia-ctx">
                <div class="ia-ctx-item">
                  <span class="ia-ctx-lbl">Procédure</span>
                  <span class="ia-ctx-val">{{ iaModal.critere?.intitule_procedure }}</span>
                </div>
                <div class="ia-ctx-item">
                  <span class="ia-ctx-lbl">Point de contrôle</span>
                  <span class="ia-ctx-val sm">{{ iaModal.critere?.point_controle }}</span>
                </div>
                <div class="ia-ctx-item">
                  <span class="ia-ctx-lbl">Qualification actuelle</span>
                  <span :class="['q-badge', `q-${(iaModal.critere?.qualification||'ne').toLowerCase()}`]">
                    {{ iaModal.critere?.qualification || 'NE' }}
                  </span>
                </div>
              </div>

              <!-- Chargement -->
              <div v-if="iaModal.loading" class="ia-loading">
                <div class="ia-spinner"></div>
                <span>Analyse en cours par l'IA…</span>
              </div>

              <!-- Erreur -->
              <div v-else-if="iaModal.error" class="ia-error">
                <i class="ti ti-alert-circle"></i> {{ iaModal.error }}
              </div>

              <!-- Suggestions -->
              <div v-else-if="iaModal.suggestions.length" class="ia-suggestions">
                <div class="ia-sugg-titre">
                  <i class="ti ti-list-check"></i>
                  {{ iaModal.suggestions.length }} recommandation(s) proposée(s) — cliquez pour sélectionner
                </div>
                <div v-for="(s, idx) in iaModal.suggestions" :key="idx"
                     class="ia-sugg-card"
                     :class="{selected: iaModal.selected===idx}"
                     @click="iaModal.selected=idx">
                  <div class="ia-sugg-num">{{ idx+1 }}</div>
                  <div class="ia-sugg-content">
                    <div class="ia-sugg-reco">{{ s.recommandation }}</div>
                    <div v-if="s.priorite" class="ia-sugg-meta">
                      <span :class="['prio-badge', `prio-${s.priorite.toLowerCase()}`]">{{ s.priorite }}</span>
                      <span v-if="s.echeance_suggestion" class="ia-ech">📅 {{ s.echeance_suggestion }}</span>
                    </div>
                    <div v-if="s.ecart" class="ia-sugg-ecart">
                      <span class="ia-ecart-lbl">Constat suggéré :</span> {{ s.ecart }}
                    </div>
                  </div>
                  <div v-if="iaModal.selected===idx" class="ia-sugg-check">
                    <i class="ti ti-check"></i>
                  </div>
                </div>
              </div>

              <!-- Mode fallback info -->
              <div v-if="iaModal.mode==='fallback'" class="ia-fallback-info">
                <i class="ti ti-info-circle"></i>
                Suggestions générées en mode hors-ligne (API IA non disponible).
              </div>
            </div>

            <div class="ia-modal-footer">
              <button class="btn btn-ghost btn-sm" @click="closeIaModal">
                <i class="ti ti-x"></i> Annuler
              </button>
              <button class="btn btn-ia-apply btn-sm"
                      :disabled="iaModal.selected === null || iaModal.loading"
                      @click="appliquerSuggestion">
                <i class="ti ti-check"></i> Appliquer la sélection
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Toast -->
    <Teleport to="body">
      <Transition name="toast-t">
        <div v-if="toast.show" class="toast" :class="`toast--${toast.type}`">
          <i :class="toast.type==='success'?'ti ti-circle-check':'ti ti-alert-circle'"></i>
          {{ toast.msg }}
        </div>
      </Transition>
    </Teleport>

  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, computed, reactive, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'

// ── Types ──────────────────────────────────────────────────────
interface PhaseAuditeur {
  id: number; audit_code: string; last_name: string; first_name: string
  full_name: string; initials: string; role_code: string; role_label: string
}
interface IaSuggestion {
  recommandation: string; ecart?: string; priorite?: string; echeance_suggestion?: string
}

// ── Props ──────────────────────────────────────────────────────
const props = withDefaults(defineProps<{
  mission?: any
  auditorRole?: string
  missionId?: number
  assignmentId?: number
  form?: any
  criteres?: any[]
  rcmList?: any[]
  phaseAuditeurs?: PhaseAuditeur[]
  currentAuditor?: { id: number; audit_code: string; last_name: string; first_name: string; role: string }
  phases?: Record<string, { label: string; icon: string; color: string }>
  qualifications?: Record<string, string>
  priorites?: Record<string, string>
  statutsSuivi?: Record<string, string>
  affectations?: Record<string, number | null>
  canManage?: boolean
  backUrl?: string
  formUrl?: string
  urlStore?: string; urlUpdate?: string; urlSoumettre?: string
  urlValider?: string; urlIndex?: string
  urlIaSuggestion?: string
}>(), {
  rcmList: () => [], phaseAuditeurs: () => [], criteres: () => [], canManage: false,
  phases: () => ({}), qualifications: () => ({}), priorites: () => ({}),
  statutsSuivi: () => ({}), affectations: () => ({}),
})

const PHASES         = props.phases
const QUALIFICATIONS = props.qualifications
const PRIORITES      = props.priorites
const STATUTS_SUIVI  = props.statutsSuivi

// ── State ──────────────────────────────────────────────────────
const form = reactive<any>({
  id: null, code: '', validation_status: 'draft', validation_note: '',
  fait_par: '', revue_par: '', synthese: '',
  ...(props.form ?? {}),
})

const criteres = reactive<any[]>(JSON.parse(JSON.stringify(props.criteres ?? [])))

const affectations = reactive<Record<string, number | null>>(
  Object.fromEntries(
    Object.keys(PHASES).map(k => [k, (props.affectations as any)?.[k] ?? null])
  )
)

const activePhase = ref('SYNTHESE')
const processing  = ref(false)
const dirty       = ref(false)
function markDirty() { dirty.value = true }

// ── Toast ──────────────────────────────────────────────────────
const toast = ref({ show: false, type: 'success', msg: '' })
let _tt: any
function showToast(t: string, m: string) {
  if (_tt) clearTimeout(_tt)
  toast.value = { show: true, type: t, msg: m }
  _tt = setTimeout(() => { toast.value.show = false }, 4000)
}

// ── IA Modal ───────────────────────────────────────────────────
const iaLoading = ref<string | null>(null)
const iaModal = reactive<{
  show: boolean; loading: boolean; error: string | null
  critere: any | null; suggestions: IaSuggestion[]
  selected: number | null; mode: string
}>({
  show: false, loading: false, error: null,
  critere: null, suggestions: [], selected: null, mode: 'ai',
})

async function demanderSuggestionIA(critere: any) {
  iaModal.show      = true
  iaModal.loading   = true
  iaModal.error     = null
  iaModal.critere   = critere
  iaModal.selected  = null
  iaModal.suggestions = []
  iaModal.mode      = 'ai'
  iaLoading.value   = critere.ref_controle

  try {
    // Appel API Laravel → RiskAISuggestionService (ou endpoint dédié RCM)
    const url = props.urlIaSuggestion || '/api/ia/rcm-suggestion'
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({
        ref_controle:       critere.ref_controle,
        intitule_procedure: critere.intitule_procedure,
        point_controle:     critere.point_controle,
        qualification:      critere.qualification,
        ecart_constat:      critere.ecart_constat,
        preuves_attendues:  critere.preuves_attendues,
        mission_id:         props.missionId,
      }),
    })
    const d = await res.json()
    if (d.success && d.suggestions?.length) {
      iaModal.suggestions = d.suggestions
      iaModal.mode = d.mode ?? 'ai'
    } else {
      iaModal.suggestions = fallbackSuggestions(critere)
      iaModal.mode = 'fallback'
    }
  } catch {
    iaModal.suggestions = fallbackSuggestions(critere)
    iaModal.mode = 'fallback'
  } finally {
    iaModal.loading = false
    iaLoading.value = null
  }
}

function fallbackSuggestions(critere: any): IaSuggestion[] {
  const q = critere.qualification
  return [
    {
      recommandation: `Mettre en place un dispositif de contrôle renforcé pour : "${critere.intitule_procedure}". Documenter les preuves et corriger les écarts constatés.`,
      priorite: q === 'NC' ? 'P1' : 'P2',
      ecart: `Non-conformité détectée sur ${critere.ref_controle} — exigence réglementaire non respectée.`,
    },
    {
      recommandation: `Former le personnel responsable aux exigences du Décret 2009-277 concernant "${critere.intitule_procedure}". Établir une procédure documentée.`,
      priorite: q === 'NC' ? 'P2' : 'P3',
    },
    {
      recommandation: `Effectuer un audit ciblé de la procédure "${critere.intitule_procedure}" et produire un plan d'action corrective dans un délai de 30 jours.`,
      priorite: 'P2',
      echeance_suggestion: '30 jours',
    },
  ]
}

function appliquerSuggestion() {
  if (iaModal.selected === null || !iaModal.critere) return
  const s = iaModal.suggestions[iaModal.selected]
  const idx = criteres.findIndex(c => c.ref_controle === iaModal.critere.ref_controle)
  if (idx >= 0) {
    if (s.recommandation) criteres[idx].recommandation = s.recommandation
    if (s.ecart && !criteres[idx].ecart_constat) criteres[idx].ecart_constat = s.ecart
    if (s.priorite && !criteres[idx].priorite)   criteres[idx].priorite = s.priorite
    markDirty()
  }
  showToast('success', 'Suggestion IA appliquée.')
  closeIaModal()
}

function closeIaModal() {
  iaModal.show = false
  iaModal.critere = null
  iaModal.suggestions = []
  iaModal.selected = null
}

// ── Computed ───────────────────────────────────────────────────
const isLocked = computed(() =>
  form.validation_status === 'validated' ||
  (form.validation_status === 'in_review' && !props.canManage)
)

const totalCriteres = computed(() => criteres.length)
const totalC  = computed(() => criteres.filter(c => c.qualification === 'C').length)
const totalPC = computed(() => criteres.filter(c => c.qualification === 'PC').length)
const totalNC = computed(() => criteres.filter(c => c.qualification === 'NC').length)
const totalNE = computed(() => criteres.filter(c => !c.qualification || c.qualification === 'NE').length)
const totalNA = computed(() => criteres.filter(c => c.qualification === 'NA').length)

const tauxGlobal = computed(() => {
  const evalues = criteres.filter(c => c.qualification && c.qualification !== 'NE' && c.qualification !== 'NA')
  if (!evalues.length) return 0
  return evalues.filter(c => c.qualification === 'C').length / evalues.length
})
const scoreRisqueTotal = computed(() => totalNC.value * 3 + totalPC.value * 1)

const nbPhasesAffectees = computed(() =>
  Object.values(affectations).filter(v => v !== null && v !== undefined).length
)

// ── Helpers ────────────────────────────────────────────────────
function getCriteresPhase(phase: string): any[] {
  return criteres.filter(c => c.phase === phase)
}

function statsPhase(phase: string) {
  const list = getCriteresPhase(phase)
  const C  = list.filter(c => c.qualification === 'C').length
  const PC = list.filter(c => c.qualification === 'PC').length
  const NC = list.filter(c => c.qualification === 'NC').length
  const NE = list.filter(c => !c.qualification || c.qualification === 'NE').length
  const NA = list.filter(c => c.qualification === 'NA').length
  const evalues = list.filter(c => c.qualification && c.qualification !== 'NE' && c.qualification !== 'NA')
  const taux = evalues.length ? C / evalues.length : 0
  return { total: list.length, C, PC, NC, NE, NA, taux, scoreRisque: NC * 3 + PC * 1 }
}

function niveauMaitrise(taux: number) {
  if (taux >= 0.9) return { label: 'Excellent', emoji: '🟢', cls: 'niv-ex', color: '#15803d' }
  if (taux >= 0.7) return { label: 'Bon',       emoji: '🟡', cls: 'niv-bon', color: '#ca8a04' }
  if (taux >= 0.5) return { label: 'Insuffisant',emoji: '🟠', cls: 'niv-ins', color: '#ea580c' }
  return { label: 'Critique', emoji: '🔴', cls: 'niv-crit', color: '#dc2626' }
}

function phaseShort(key: string): string {
  const m: Record<string, string> = {
    PLA: 'Plan.', DAO: 'DAO', ROO: 'Récp.', EVA: 'Éval.',
    SAN: 'Sign.', EXE: 'Exéc.', REP: 'Paiem.', CAT: 'Arch.',
  }
  return m[key] ?? key
}

function canEdit(_critere: any, phase: string): boolean {
  if (isLocked.value) return false
  if (props.canManage) return true
  const affectedId = affectations[phase]
  if (!affectedId) return true
  return props.currentAuditor?.id === affectedId
}

function auditorByKey(id: number | null): PhaseAuditeur | undefined {
  if (!id) return undefined
  return props.phaseAuditeurs?.find(a => a.id === id)
}
function auditorName(id: number | null): string {
  return auditorByKey(id)?.full_name ?? '—'
}
function auditorInitials(id: number | null): string {
  return auditorByKey(id)?.initials ?? '?'
}
function phasesDeAuditeur(audId: number): string[] {
  return Object.entries(affectations)
    .filter(([, v]) => v === audId)
    .map(([k]) => k)
}
function roleLabel(role: string): string {
  return ({ DM: 'Directeur Mission', CM: 'Chef Mission', AS: 'Auditeur Senior', AJ: 'Auditeur Junior' } as any)[role] ?? role
}
function vstLbl(s: string): string {
  return ({ draft: 'Brouillon', in_review: 'En attente', validated: 'Validé ✓', rejected: 'Rejeté' } as any)[s] ?? s
}
function vstIcon(s: string): string {
  return ({ draft: 'ti ti-pencil', in_review: 'ti ti-clock', validated: 'ti ti-circle-check', rejected: 'ti ti-circle-x' } as any)[s] ?? 'ti ti-circle'
}
function csrf(): string {
  return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? ''
}

// ── Submit ─────────────────────────────────────────────────────
async function submit() {
  processing.value = true
  try {
    const payload = {
      mission_id: props.missionId, assignment_id: props.assignmentId,
      fait_par: form.fait_par, revue_par: form.revue_par, synthese: form.synthese,
      criteres_marches: JSON.stringify(criteres.map(c => ({
        phase: c.phase, ref_controle: c.ref_controle,
        resultat_controle: c.resultat_controle, preuves_obtenues: c.preuves_obtenues,
        qualification: c.qualification, ecart_constat: c.ecart_constat,
        recommandation: c.recommandation, priorite: c.priorite,
        responsable_action: c.responsable_action, echeance: c.echeance,
        statut_suivi: c.statut_suivi, auditeur_id: c.auditeur_id ?? null,
      }))),
      phase_affectations: JSON.stringify(affectations),
    }
    const method = form.id ? 'PUT' : 'POST'
    const url    = form.id
      ? (props.urlUpdate || `${props.formUrl}/${form.id}`)
      : (props.urlStore  || props.formUrl)
    const res    = await fetch(url!, {
      method,
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify(payload),
    })
    const d = await res.json()
    if (d.success || res.ok) {
      showToast('success', form.id ? 'RCM mis à jour.' : 'RCM créé.')
      dirty.value = false
      if (!form.id && d.form?.id) { form.id = d.form.id; form.code = d.form.code }
      if (d.form) Object.assign(form, { id: d.form.id, code: d.form.code, validation_status: d.form.validation_status })
    } else {
      showToast('error', d.message ?? 'Erreur.')
    }
  } catch { showToast('error', 'Erreur réseau.') }
  finally { processing.value = false }
}

function annuler() { if (props.backUrl) router.visit(props.backUrl) }

async function soumettre() {
  processing.value = true
  try {
    const res = await fetch(props.urlSoumettre || '', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ mission_id: props.missionId, assignment_id: props.assignmentId }),
    })
    const d = await res.json()
    if (d.success) { form.validation_status = 'in_review'; showToast('success', 'Soumis pour validation.') }
    else showToast('error', d.error ?? 'Erreur')
  } catch { showToast('error', 'Erreur réseau') }
  processing.value = false
}

async function valider(action: string, note?: string) {
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
      showToast('success', action === 'validate' ? 'RCM Validé ✓' : 'Rejeté.')
    } else showToast('error', d.error ?? 'Erreur')
  } catch { showToast('error', 'Erreur réseau') }
  processing.value = false
}

function promptReject() {
  const n = prompt('Motif du rejet (obligatoire) :')
  if (!n?.trim()) return
  valider('reject', n.trim())
}

onBeforeUnmount(() => { if (_tt) clearTimeout(_tt) })
</script>

<style scoped>
*,*::before,*::after { box-sizing: border-box }
.rcm-shell { display: flex; flex-direction: column; min-height: 100vh; font-family: 'Segoe UI', system-ui, sans-serif; background: #f1f5f9 }

/* ── HEADER ── */
.rcm-header { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 10px 20px 0; position: sticky; top: 0; z-index: 50; box-shadow: 0 1px 6px rgba(0,0,0,.07) }
.rcm-hrow { display: flex; align-items: flex-start; gap: 12px; padding-bottom: 8px }
.rcm-back { display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border: 1px solid #e2e8f0; border-radius: 7px; color: #64748b; text-decoration: none; flex-shrink: 0; transition: all .12s }
.rcm-back:hover { background: #f1f5f9 }
.rcm-hinfo { flex: 1; min-width: 0 }
.rcm-chips { display: flex; align-items: center; gap: 5px; flex-wrap: wrap; margin-bottom: 3px }
.rcm-code { font-size: .66rem; font-weight: 800; background: #0f172a; color: #fff; padding: 2px 8px; border-radius: 4px; font-family: ui-monospace, monospace }
.rcm-chip { display: inline-flex; align-items: center; gap: 3px; font-size: .63rem; font-weight: 700; padding: 2px 8px; border-radius: 10px; border: 1px solid transparent }
.chip-draft { background: #f1f5f9; color: #64748b; border-color: #e2e8f0 }
.chip-in_review { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe }
.chip-validated { background: #f0fdf4; color: #15803d; border-color: #bbf7d0 }
.chip-type { background: #fff7ed; color: #c2410c; border-color: #fed7aa }
.chip-role-DM { background: #fdf4ff; color: #7e22ce; border-color: #e9d5ff }
.chip-role-CM { background: #eff6ff; color: #0369a1; border-color: #bae6fd }
.chip-role-AS { background: #f0fdf4; color: #15803d; border-color: #bbf7d0 }
.chip-role-AJ { background: #fffbeb; color: #b45309; border-color: #fde68a }
.rcm-title { font-size: .97rem; font-weight: 800; color: #0f172a; margin: 0 0 3px }
.rcm-meta { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; font-size: .7rem; color: #64748b }
.rcm-meta span { display: flex; align-items: center; gap: 3px }
.meta-pts { color: #0369a1 !important; font-weight: 700 }
.meta-ok { color: #15803d !important; font-weight: 700 }
.meta-nc { color: #dc2626 !important; font-weight: 700 }
.meta-tx { color: #7e22ce !important; font-weight: 700 }
.rcm-banner { display: flex; align-items: center; gap: 7px; padding: 5px 0; font-size: .74rem; font-weight: 600; border-top: 1px solid transparent }
.banner-lock { color: #15803d; border-top-color: #bbf7d0 }
.banner-review { color: #1d4ed8 }
.banner-reject { color: #dc2626 }

/* ── ONGLETS ── */
.rcm-tabs { display: flex; align-items: center; background: #fff; border-bottom: 2px solid #e2e8f0; padding: 0 16px; overflow-x: auto; flex-shrink: 0; gap: 2px; scrollbar-width: thin }
.rcm-tabs::-webkit-scrollbar { height: 3px }
.rtab { display: inline-flex; align-items: center; gap: 5px; padding: 9px 12px; border: none; border-bottom: 3px solid transparent; background: none; color: #64748b; cursor: pointer; font-size: .7rem; font-weight: 600; font-family: inherit; transition: all .12s; white-space: nowrap; flex-shrink: 0 }
.rtab:hover { color: #0f172a; background: #f8fafc }
.rtab.active { color: #0f172a; border-bottom-color: #0f172a }
.rtab-mgmt { color: #7e22ce !important }
.rtab-mgmt.active { border-bottom-color: #7e22ce !important }
.tab-icon { font-size: .85rem }
.tab-lbl { font-size: .68rem }
.tab-score { font-size: .58rem; font-weight: 800; padding: 1px 5px; border-radius: 6px; margin-left: 2px }
.tab-aff { color: #15803d; font-size: .65rem }

/* ── BODY ── */
.rcm-body { flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; padding: 14px 20px 80px }

/* ── Synthèse ── */
.synth-header { background: linear-gradient(135deg, #0f172a, #1e293b); color: #fff; padding: 14px 18px; border-radius: 10px }
.sh-titre { font-size: .85rem; font-weight: 800; letter-spacing: .04em }
.sh-sub { font-size: .7rem; color: rgba(255,255,255,.6); margin-top: 3px }
.global-score { display: flex; align-items: center; gap: 20px; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; flex-wrap: wrap; box-shadow: 0 1px 4px rgba(0,0,0,.05) }
.gs-ring { width: 80px; height: 80px; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; border: 5px solid; flex-shrink: 0 }
.gs-ring.niv-ex { border-color: #15803d; background: #f0fdf4 }
.gs-ring.niv-bon { border-color: #ca8a04; background: #fffbeb }
.gs-ring.niv-ins { border-color: #ea580c; background: #fff7ed }
.gs-ring.niv-crit { border-color: #dc2626; background: #fef2f2 }
.gs-pct { font-size: 1.1rem; font-weight: 900; color: #0f172a; line-height: 1 }
.gs-lbl { font-size: .5rem; color: #64748b; text-align: center; line-height: 1.2; margin-top: 2px }
.gs-stats { display: flex; gap: 10px; flex-wrap: wrap }
.gs-stat { display: flex; flex-direction: column; align-items: center; padding: 6px 12px; border-radius: 8px; min-width: 50px }
.gs-stat.gs-c { background: #f0fdf4; border: 1px solid #bbf7d0 }
.gs-stat.gs-pc { background: #fffbeb; border: 1px solid #fde68a }
.gs-stat.gs-nc { background: #fef2f2; border: 1px solid #fecaca }
.gs-stat.gs-ne { background: #f8fafc; border: 1px solid #e2e8f0 }
.gs-stat.gs-na { background: #f5f3ff; border: 1px solid #ddd6fe }
.gs-n { font-size: 1.2rem; font-weight: 900; color: #0f172a }
.gs-stat span:last-child { font-size: .6rem; color: #64748b; font-weight: 600 }
.gs-niveau { display: flex; flex-direction: column; gap: 6px; padding: 10px 16px; border-radius: 8px; border: 1px solid; font-size: .74rem; font-weight: 700 }
.gs-niveau.niv-ex { background: #f0fdf4; border-color: #bbf7d0; color: #15803d }
.gs-niveau.niv-bon { background: #fffbeb; border-color: #fde68a; color: #92400e }
.gs-niveau.niv-ins { background: #fff7ed; border-color: #fed7aa; color: #c2410c }
.gs-niveau.niv-crit { background: #fef2f2; border-color: #fecaca; color: #dc2626 }
.gs-score { font-size: .68rem; font-weight: 600; opacity: .8 }
.aud-badge { font-size: .6rem; font-weight: 800; background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px }
.na-badge { color: #cbd5e1; font-size: .7rem }

/* Tableau synthèse */
.synth-tbl-wrap { overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,.04) }
.synth-tbl { width: 100%; border-collapse: collapse; font-size: .72rem }
.synth-tbl thead th { background: #1e293b; color: rgba(255,255,255,.85); font-size: .6rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; padding: 7px 10px; border: none; white-space: nowrap; text-align: center }
.st-phase { text-align: left; min-width: 200px }
.synth-tbl tbody td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: middle }
.synth-row:hover td { background: #f8fbff !important }
.st-phase-val { display: flex; align-items: center; gap: 8px; font-size: .74rem; font-weight: 600 }
.ph-icon { font-size: .85rem }
.taux-bar { position: relative; background: #f1f5f9; border-radius: 4px; height: 18px; overflow: hidden; min-width: 60px; display: flex; align-items: center; justify-content: center }
.taux-fill { position: absolute; left: 0; top: 0; height: 100%; opacity: .25; transition: width .3s }
.taux-bar span { position: relative; font-size: .65rem; font-weight: 700; color: #0f172a; z-index: 1 }
.niveau-badge { font-size: .62rem; font-weight: 700; padding: 2px 7px; border-radius: 5px }
.niv-ex { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0 }
.niv-bon { background: #fffbeb; color: #92400e; border: 1px solid #fde68a }
.niv-ins { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa }
.niv-crit { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca }
.synth-total td { background: #f8fafc; font-weight: 700; padding: 8px 10px; border-top: 2px solid #e2e8f0 }

/* Légendes */
.legenda { display: flex; flex-wrap: wrap; gap: 16px; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: .66rem; color: #475569 }
.leg-grp { display: flex; align-items: center; gap: 8px; flex-wrap: wrap }
.leg-t { font-weight: 700; color: #0f172a }
.leg-item { display: flex; align-items: center; gap: 4px }

/* ── Phase header ── */
.tab-content { display: flex; flex-direction: column; gap: 10px }
.phase-header { display: flex; align-items: center; gap: 14px; padding: 12px 16px; background: #fff; border-left: 5px solid; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.05) }
.ph-icon-lg { font-size: 1.4rem }
.ph-info { flex: 1 }
.ph-titre { font-size: .85rem; font-weight: 800; color: #0f172a }
.ph-ref { font-size: .68rem; color: #64748b; margin-top: 2px }
.ph-stats { display: flex; align-items: center; gap: 8px }
.ph-stat { display: flex; align-items: center; gap: 4px; font-size: .72rem }
.ph-q { font-size: .6rem; font-weight: 800; padding: 2px 5px; border-radius: 4px }
.ph-cnt { font-weight: 800; color: #0f172a }
.ph-taux { font-size: .8rem; font-weight: 900; padding: 4px 10px; border-radius: 7px }

/* Barre affectation */
.affect-bar { display: flex; align-items: center; gap: 8px; padding: 8px 14px; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; font-size: .72rem; color: #0369a1; flex-wrap: wrap }
.affect-bar--ro { background: #f8fafc; border-color: #e2e8f0; color: #475569 }
.af-sel { padding: 4px 8px; border: 1px solid #bae6fd; border-radius: 6px; font-size: .7rem; color: #0f172a; background: #fff; outline: none; cursor: pointer; font-family: inherit; min-width: 200px }
.af-badge { font-size: .7rem; font-weight: 700; background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 5px }
.info-bar { display: flex; align-items: center; gap: 7px; padding: 7px 14px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; font-size: .7rem; color: #92400e }

/* Tableau critères */
.crit-tbl-wrap { overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.05) }
.crit-tbl-wrap::-webkit-scrollbar { height: 8px }
.crit-tbl-wrap::-webkit-scrollbar-track { background: #f8fafc }
.crit-tbl-wrap::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px }
.crit-tbl { width: 100%; border-collapse: collapse; font-size: .7rem; min-width: 1600px }
.crit-head th { background: #0f172a; color: rgba(255,255,255,.85); font-size: .58rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; padding: 7px 8px; border: none; white-space: nowrap; vertical-align: bottom; position: sticky; top: 0; z-index: 2 }
.ch-ref { width: 70px } .ch-art { width: 90px } .ch-proc { width: 130px }
.ch-point { min-width: 190px } .ch-res { width: 115px } .ch-preuves { width: 120px }
.ch-qual { width: 80px } .ch-ecart { width: 120px } .ch-reco { width: 140px }
.ch-prio { width: 65px } .ch-resp { width: 95px } .ch-ech { width: 85px }
.ch-statut { width: 85px } .ch-ia { width: 45px; text-align: center }
.crit-row td { padding: 6px 8px; border: 1px solid #f1f5f9; vertical-align: top }
.crit-row:hover td { background: #fafbff !important }
.q-row-c td { background: #fefffe }
.q-row-pc td { background: #fffdf0 }
.q-row-nc td { background: #fffafa }
.q-row-ne td { background: #fafafa }
.q-row-na td { background: #faf9ff }
.q-row-nc .td-ref { border-left: 4px solid #dc2626 !important }
.q-row-pc .td-ref { border-left: 4px solid #ca8a04 !important }
.q-row-c  .td-ref { border-left: 4px solid #15803d !important }
.td-ref { text-align: center; vertical-align: middle }
.ref-badge { display: inline-block; font-size: .6rem; font-weight: 800; font-family: ui-monospace, monospace; background: #0f172a; color: #fff; padding: 2px 6px; border-radius: 5px }
.art-txt { font-size: .62rem; font-family: ui-monospace, monospace; color: #0369a1 }
.proc-txt { font-size: .72rem; color: #0f172a; line-height: 1.4 }
.fw { font-weight: 700 }
.point-txt { font-size: .68rem; color: #334155; line-height: 1.5; margin: 0 }
.preuves-attendues { margin-top: 5px }
.pa-toggle { font-size: .6rem; color: #7e22ce; cursor: pointer; list-style: none; display: flex; align-items: center; gap: 3px; font-weight: 600 }
.pa-txt { font-size: .62rem; color: #64748b; font-style: italic; margin-top: 3px; line-height: 1.4; padding-left: 8px; border-left: 2px solid #e9d5ff }
.c-ta { width: 100%; border: 1px solid transparent; background: transparent; font-size: .68rem; font-family: inherit; outline: none; color: #1e293b; resize: none; padding: 2px 4px; border-radius: 4px; transition: all .12s; line-height: 1.5 }
.c-ta:not(:disabled):hover { border-color: #e2e8f0; background: #f8fafc }
.c-ta:not(:disabled):focus { border-color: #3b82f6; background: #fff }
.c-ta:disabled { color: #94a3b8; cursor: not-allowed }
.c-inp { width: 100%; border: 1px solid transparent; background: transparent; font-size: .7rem; font-family: inherit; outline: none; color: #1e293b; padding: 2px 4px; border-radius: 4px; transition: all .12s }
.c-inp:hover { border-color: #e2e8f0; background: #f8fafc }
.c-inp:focus { border-color: #3b82f6; background: #fff }
.c-ro { font-size: .68rem; color: #334155; line-height: 1.4; margin: 0; white-space: pre-wrap; word-break: break-word }
.c-ro.sm { font-size: .66rem }
.q-sel,.p-sel,.s-sel { width: 100%; border: 1px solid transparent; background: transparent; font-size: .68rem; font-family: inherit; outline: none; color: #1e293b; cursor: pointer; padding: 2px 4px; border-radius: 4px }
.q-sel:hover,.p-sel:hover,.s-sel:hover { border-color: #e2e8f0; background: #f8fafc }
.q-sel:disabled { color: #94a3b8; cursor: not-allowed }
.q-badge { display: inline-block; font-size: .62rem; font-weight: 800; padding: 2px 7px; border-radius: 5px }
.q-c { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0 }
.q-pc { background: #fffbeb; color: #92400e; border: 1px solid #fde68a }
.q-nc { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca }
.q-ne { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0 }
.q-na { background: #f5f3ff; color: #7e22ce; border: 1px solid #ddd6fe }
.prio-badge { font-size: .62rem; font-weight: 800; padding: 2px 7px; border-radius: 5px }
.prio-p1 { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca }
.prio-p2 { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa }
.prio-p3 { background: #fffbeb; color: #b45309; border: 1px solid #fde68a }
.prio-p4 { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0 }
.prio-nd { background: #f8fafc; color: #94a3b8 }
.statut-badge { font-size: .6rem; font-weight: 700; padding: 2px 6px; border-radius: 5px }
.st-non_demarre { background: #f8fafc; color: #94a3b8 }
.st-en_cours { background: #eff6ff; color: #1d4ed8 }
.st-realisee { background: #f0fdf4; color: #15803d }
.st-cloturee { background: #d1fae5; color: #065f46; font-weight: 800 }
/* Bouton IA */
.td-ia { text-align: center; vertical-align: middle }
.btn-ia { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: linear-gradient(135deg, #7e22ce, #4f46e5); color: #fff; border: none; border-radius: 8px; cursor: pointer; font-size: .75rem; transition: all .15s; box-shadow: 0 2px 8px rgba(126,34,206,.3) }
.btn-ia:hover { transform: scale(1.1); box-shadow: 0 4px 12px rgba(126,34,206,.4) }
.btn-ia.loading { opacity: .7; cursor: wait }
.ia-nd { color: #e2e8f0; font-size: .65rem }

.synth-phase-row td { background: #f8fafc; font-size: .68rem; padding: 5px 10px; border-top: 1px solid #e2e8f0 }
.c-c { color: #15803d; font-weight: 700 } .c-pc { color: #92400e; font-weight: 700 }
.c-nc { color: #dc2626; font-weight: 700 } .c-ne { color: #475569 } .c-na { color: #7e22ce }
.tc { text-align: center } .sm { font-size: .65rem }

/* ── PANNEAU AFFECTATION ── */
.affect-panel-header { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px 20px; display: flex; flex-direction: column; gap: 8px }
.ap-titre { display: flex; align-items: center; gap: 7px; font-size: .82rem; font-weight: 800; color: #0f172a }
.ap-sub { font-size: .72rem; color: #64748b }
.ap-progress { display: flex; align-items: center; gap: 10px; margin-top: 4px }
.ap-prog-bar { flex: 1; height: 6px; background: #f1f5f9; border-radius: 4px; overflow: hidden }
.ap-prog-fill { height: 100%; background: linear-gradient(90deg, #15803d, #22c55e); border-radius: 4px; transition: width .4s }
.ap-prog-lbl { font-size: .68rem; font-weight: 700; color: #15803d; white-space: nowrap }
.affect-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px }
.affect-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.05); transition: transform .12s }
.affect-card:hover { transform: translateY(-1px) }
.ac-hdr { display: flex; align-items: center; gap: 10px; padding: 12px 14px; color: #fff }
.ac-icon { font-size: 1.2rem; flex-shrink: 0 }
.ac-hdr-info { flex: 1; min-width: 0 }
.ac-hdr-label { display: block; font-size: .74rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis }
.ac-hdr-pts { display: block; font-size: .62rem; opacity: .7 }
.ac-niv { font-size: 1rem }
.ac-body { padding: 12px 14px; display: flex; flex-direction: column; gap: 10px }
.ac-stats-row { display: flex; gap: 8px; font-size: .65rem; font-weight: 700 }
.acs-c { color: #15803d } .acs-pc { color: #92400e } .acs-nc { color: #dc2626 } .acs-ne { color: #94a3b8 }
.ac-lbl { display: block; font-size: .65rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px }
.af-sel-full { width: 100%; padding: 7px 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: .72rem; color: #0f172a; background: #f8fafc; outline: none; cursor: pointer; font-family: inherit; transition: border-color .12s }
.af-sel-full:hover { border-color: #bae6fd }
.af-ro { font-size: .72rem; color: #475569; font-style: italic; padding: 4px 0 }
.ac-aud { display: flex; align-items: center; gap: 8px; padding: 8px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0 }
.aud-av { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .65rem; font-weight: 800; border: 2px solid transparent; flex-shrink: 0 }
.av-DM { background: #f5f3ff; color: #7e22ce; border-color: #e9d5ff }
.av-CM { background: #eff6ff; color: #0369a1; border-color: #bae6fd }
.av-AS { background: #f0fdf4; color: #15803d; border-color: #bbf7d0 }
.av-AJ { background: #fffbeb; color: #b45309; border-color: #fde68a }
.aud-info { flex: 1; min-width: 0 }
.aud-name { display: block; font-size: .72rem; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis }
.aud-role { display: block; font-size: .62rem; color: #94a3b8 }
.aud-charge { display: block; font-size: .62rem; color: #7e22ce; font-weight: 600; margin-top: 2px }
.btn-remove-aff { width: 22px; height: 22px; border-radius: 5px; border: 1px solid #fecaca; background: #fef2f2; color: #dc2626; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: .7rem; flex-shrink: 0 }
.btn-remove-aff:hover { background: #dc2626; color: #fff }
.ac-empty { display: flex; align-items: center; gap: 6px; font-size: .7rem; color: #94a3b8; font-style: italic; padding: 4px 0 }

/* Recap auditeurs */
.recap-auditeurs { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px }
.recap-titre { display: flex; align-items: center; gap: 6px; font-size: .76rem; font-weight: 800; color: #0f172a; margin-bottom: 10px }
.recap-grid { display: flex; flex-wrap: wrap; gap: 10px }
.recap-card { display: flex; align-items: flex-start; gap: 10px; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; min-width: 220px; flex: 1 }
.recap-info { display: flex; flex-direction: column; gap: 2px }

/* ── FOOTER ── */
.rcm-footer { position: sticky; bottom: 0; display: flex; align-items: center; justify-content: space-between; padding: 10px 20px; background: #fff; border-top: 2px solid #e2e8f0; box-shadow: 0 -2px 8px rgba(0,0,0,.06); flex-wrap: wrap; gap: 8px; z-index: 40 }
.footer-left,.footer-right { display: flex; gap: 6px; flex-wrap: wrap }
.footer-mid { flex: 1; display: flex; justify-content: center; align-items: center; gap: 12px }
.saved-code { font-size: .72rem; color: #15803d; display: flex; align-items: center; gap: 4px; font-weight: 700; background: #f0fdf4; padding: 3px 10px; border-radius: 6px; border: 1px solid #bbf7d0 }
.stat-lbl { font-size: .7rem; color: #7e22ce; font-weight: 700; background: #fdf4ff; padding: 3px 10px; border-radius: 6px; border: 1px solid #e9d5ff }
.btn { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 7px; font-size: .76rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; transition: all .13s; white-space: nowrap }
.btn-save { background: #0f172a; color: #fff; position: relative } .btn-save:hover:not(:disabled) { background: #1e293b }
.btn-ghost { background: #fff; color: #475569; border: 1px solid #e2e8f0 } .btn-ghost:hover:not(:disabled) { background: #f8fafc }
.btn-sub { background: #1d4ed8; color: #fff } .btn-sub:hover:not(:disabled) { background: #1e40af }
.btn-ok { background: #15803d; color: #fff } .btn-ok:hover:not(:disabled) { background: #166534 }
.btn-rej { background: #fff; color: #dc2626; border: 1px solid #fecaca } .btn-rej:hover:not(:disabled) { background: #fef2f2 }
.btn-ia-apply { background: linear-gradient(135deg, #7e22ce, #4f46e5); color: #fff } .btn-ia-apply:hover:not(:disabled) { opacity: .9 }
.btn-sm { padding: 5px 11px; font-size: .73rem }
.btn:disabled { opacity: .45; cursor: not-allowed }
.dirty-dot { width: 7px; height: 7px; background: #f59e0b; border-radius: 50%; position: absolute; top: 4px; right: 4px }

/* ── MODAL IA ── */
.ia-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.5); backdrop-filter: blur(4px); z-index: 9000; display: flex; align-items: center; justify-content: center; padding: 20px }
.ia-modal { background: #fff; border-radius: 16px; width: 100%; max-width: 680px; max-height: 85vh; display: flex; flex-direction: column; box-shadow: 0 24px 80px rgba(0,0,0,.25); overflow: hidden }
.ia-modal-hdr { display: flex; align-items: center; gap: 10px; padding: 16px 20px; background: linear-gradient(135deg, #7e22ce, #4f46e5); color: #fff; flex-shrink: 0 }
.ia-modal-title { display: flex; align-items: center; gap: 7px; font-size: .85rem; font-weight: 800; flex: 1 }
.ia-modal-ref { font-size: .7rem; background: rgba(255,255,255,.2); padding: 2px 8px; border-radius: 6px; font-family: ui-monospace, monospace }
.ia-modal-close { background: rgba(255,255,255,.15); border: none; color: #fff; cursor: pointer; width: 28px; height: 28px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: .8rem }
.ia-modal-close:hover { background: rgba(255,255,255,.25) }
.ia-modal-body { flex: 1; overflow-y: auto; padding: 16px 20px; display: flex; flex-direction: column; gap: 14px }
.ia-modal-footer { padding: 12px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 8px; flex-shrink: 0 }

/* Contexte IA */
.ia-ctx { display: flex; flex-direction: column; gap: 8px; padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px }
.ia-ctx-item { display: flex; flex-direction: column; gap: 3px }
.ia-ctx-lbl { font-size: .6rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8 }
.ia-ctx-val { font-size: .72rem; color: #1e293b; line-height: 1.4 }
.ia-ctx-val.sm { font-size: .68rem; color: #64748b }

/* Chargement */
.ia-loading { display: flex; flex-direction: column; align-items: center; gap: 12px; padding: 30px; color: #7e22ce; font-size: .78rem; font-weight: 600 }
.ia-spinner { width: 36px; height: 36px; border-radius: 50%; border: 3px solid #e9d5ff; border-top-color: #7e22ce; animation: spin .7s linear infinite }

/* Erreur */
.ia-error { display: flex; align-items: center; gap: 8px; padding: 12px 14px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; font-size: .74rem; color: #dc2626 }

/* Suggestions */
.ia-suggestions { display: flex; flex-direction: column; gap: 8px }
.ia-sugg-titre { display: flex; align-items: center; gap: 6px; font-size: .72rem; font-weight: 700; color: #7e22ce; margin-bottom: 2px }
.ia-sugg-card { display: flex; align-items: flex-start; gap: 12px; padding: 12px 14px; border: 2px solid #e2e8f0; border-radius: 10px; cursor: pointer; transition: all .12s; background: #fff }
.ia-sugg-card:hover { border-color: #7e22ce; background: #fdf4ff }
.ia-sugg-card.selected { border-color: #7e22ce; background: #fdf4ff; box-shadow: 0 0 0 3px rgba(126,34,206,.1) }
.ia-sugg-num { width: 22px; height: 22px; border-radius: 50%; background: #7e22ce; color: #fff; display: flex; align-items: center; justify-content: center; font-size: .62rem; font-weight: 800; flex-shrink: 0; margin-top: 1px }
.ia-sugg-content { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 6px }
.ia-sugg-reco { font-size: .74rem; color: #1e293b; line-height: 1.5; font-weight: 600 }
.ia-sugg-meta { display: flex; align-items: center; gap: 8px }
.ia-ech { font-size: .65rem; color: #64748b }
.ia-sugg-ecart { font-size: .68rem; color: #64748b; font-style: italic; padding: 5px 8px; background: #f8fafc; border-radius: 5px; border-left: 3px solid #e2e8f0 }
.ia-ecart-lbl { font-weight: 600; color: #475569; font-style: normal }
.ia-sugg-check { width: 22px; height: 22px; border-radius: 50%; background: #15803d; color: #fff; display: flex; align-items: center; justify-content: center; font-size: .7rem; flex-shrink: 0 }
.ia-fallback-info { display: flex; align-items: center; gap: 7px; padding: 8px 12px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 7px; font-size: .68rem; color: #92400e }

/* Toast */
.toast { position: fixed; bottom: 80px; right: 20px; z-index: 9999; display: flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 10px; font-size: .76rem; font-weight: 700; box-shadow: 0 6px 24px rgba(0,0,0,.15); border: 1px solid transparent }
.toast--success { background: #f0fdf4; color: #15803d; border-color: #bbf7d0 }
.toast--error { background: #fef2f2; color: #dc2626; border-color: #fecaca }
.toast-t-enter-active,.toast-t-leave-active { transition: all .25s cubic-bezier(.4,0,.2,1) }
.toast-t-enter-from,.toast-t-leave-to { opacity: 0; transform: translateY(10px) }
.modal-t-enter-active,.modal-t-leave-active { transition: all .2s ease }
.modal-t-enter-from,.modal-t-leave-to { opacity: 0 }
.modal-t-enter-from .ia-modal,.modal-t-leave-to .ia-modal { transform: scale(.95) }
.spin-s { width: 12px; height: 12px; border-radius: 50%; border: 2px solid currentColor; border-top-color: transparent; animation: spin .6s linear infinite; display: inline-block; flex-shrink: 0 }
@keyframes spin { to { transform: rotate(360deg) } }
::-webkit-scrollbar { width: 5px; height: 8px }
::-webkit-scrollbar-track { background: #f8fafc }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px }
::-webkit-scrollbar-thumb:hover { background: #94a3b8 }
</style>