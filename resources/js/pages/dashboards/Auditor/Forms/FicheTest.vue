<template>
  <VerticalLayoutAudit>
    <div class="ft-shell">

      <!-- ══ TOPBAR ══════════════════════════════════════════════ -->
      <header class="ft-topbar">
        <div class="ft-topbar__left">
          <a :href="props.backUrl" class="ft-ib" title="Retour"><i class="ti ti-arrow-left"></i></a>
          <span class="ft-code">{{ form.code || 'FT-AUTO' }}</span>
          <span class="ft-sdot" :class="'sd--' + form.validation_status"></span>
          <span class="ft-vstatus">{{ vstLbl(form.validation_status) }}</span>
          <div class="ft-div"></div>
          <i class="ti ti-building ft-icon-muted"></i>
          <span class="ft-mission-lbl">{{ missionLibelle || '—' }}</span>
          <span v-if="programmeData.found" class="ft-prog-badge">{{ programmeData.programme_code }}</span>
          <span v-if="programmeData.found" class="ft-stat-lbl">
            {{ programmeData.total_objectifs }} obj. · {{ programmeData.total_tests }} tests
          </span>
          <transition name="autosave-fade">
            <span v-if="autoSaving" class="ft-autosave-indicator">
              <span class="ft-spin ft-spin--xs"></span> Sauvegarde…
            </span>
            <span v-else-if="lastAutoSave" class="ft-autosave-ok">
              <i class="ti ti-check"></i> Sauvé {{ lastAutoSave }}
            </span>
          </transition>
        </div>
        <div class="ft-topbar__right">
          <span class="ft-chip-role"><i class="ti ti-shield-half"></i>{{ props.auditorRole }}</span>
          <span class="ft-chip-user"><i class="ti ti-user-check"></i>{{ props.auditeurNom }}</span>

          <!-- Bouton Synthèse FOCI -->
          <button v-if="form.id" class="ft-btn ft-btn--foci"
            :class="{ 'ft-btn--foci-active': fociVueActif, 'ft-btn--foci-empty': !frapsLocal.length }"
            @click="ouvrirFociVue">
            <i class="ti ti-file-text"></i>
            Synthèse FOCI
            <span v-if="frapsLocal.length" class="ft-foci-count">{{ frapsLocal.length }}</span>
          </button>

          <template v-if="!isLocked">
            <button class="ft-btn ft-btn--ghost" :disabled="processing" @click="annuler">
              <i class="ti ti-x"></i>
            </button>
            <button class="ft-btn ft-btn--save" :disabled="processing" @click="submit()">
              <span v-if="processing" class="ft-spin"></span>
              <i v-else class="ti ti-device-floppy"></i>
              {{ form.id ? 'Enregistrer' : 'Créer' }}
            </button>
            <button v-if="form.id && form.validation_status === 'draft'"
              class="ft-btn ft-btn--submit" @click="soumettre">
              <i class="ti ti-send"></i>Soumettre
            </button>
          </template>
          <template v-if="canManage && form.validation_status === 'in_review'">
            <button class="ft-btn ft-btn--validate" @click="valider('validate')">
              <i class="ti ti-circle-check"></i>Valider
            </button>
            <button class="ft-btn ft-btn--reject" @click="promptReject">
              <i class="ti ti-circle-x"></i>Rejeter
            </button>
          </template>
        </div>
      </header>

      <!-- Banners statut -->
      <div v-if="form.validation_status === 'validated'" class="ft-banner ft-banner--ok">
        <i class="ti ti-lock"></i> Fiche <strong>validée</strong> — lecture seule
      </div>
      <div v-else-if="form.validation_status === 'in_review'" class="ft-banner ft-banner--review">
        <i class="ti ti-clock"></i> En attente de validation
        <span v-if="canManage"> · DM/CM peut valider ou rejeter</span>
      </div>
      <div v-else-if="form.validation_status === 'draft' && form.validation_note"
        class="ft-banner ft-banner--rejected">
        <i class="ti ti-circle-x"></i> Rejetée — <em>{{ form.validation_note }}</em>
      </div>

      <!-- ══ BLOC IA GLOBAL ════════════════════════════════════ -->
      <div v-if="iaGlobal" class="ft-synthese-banner">
        <div class="ft-synthese-header">
          <i class="ti ti-chart-bar"></i>
          <strong>Synthèse IA globale</strong>
          <span class="ft-ia-score" :style="{ background: iaScoreColor(iaGlobal.score_global) }">
            Score : {{ iaGlobal.score_global ?? '—' }}/10
          </span>
          <span v-if="iaGlobal.fiabilite" class="ft-fiabilite" :class="'fiab-' + iaGlobal.fiabilite">
            Fiabilité : {{ iaGlobal.fiabilite }}
          </span>
          <button class="ft-btn-refresh" @click="lancerIaGlobale" :disabled="iaProcessing">
            <i class="ti ti-refresh" :class="{ 'ft-spin': iaProcessing }"></i>
            {{ iaProcessing ? 'Analyse…' : 'Actualiser' }}
          </button>
          <button class="ft-btn-ia-close" @click="iaGlobal = null" title="Masquer">
            <i class="ti ti-x"></i>
          </button>
        </div>
        <div class="ft-synthese-grid">
          <div class="ft-synthese-col">
            <div class="ft-stitle">✅ Points forts</div>
            <ul class="ft-synthese-list">
              <li v-for="p in (iaGlobal.points_forts || [])" :key="p">{{ p }}</li>
              <li v-if="!iaGlobal.points_forts?.length" class="ft-empty-mute">—</li>
            </ul>
          </div>
          <div class="ft-synthese-col">
            <div class="ft-stitle">⚠️ Risques majeurs</div>
            <ul class="ft-synthese-list">
              <li v-for="r in (iaGlobal.risques_majeurs || [])" :key="r">{{ r }}</li>
              <li v-if="!iaGlobal.risques_majeurs?.length" class="ft-empty-mute">—</li>
            </ul>
          </div>
          <div class="ft-synthese-col ft-synthese-col--full">
            <div class="ft-stitle">💡 Recommandations</div>
            <ul class="ft-synthese-list ft-synthese-list--reco">
              <li v-for="rec in (iaGlobal.recommandations || [])" :key="rec">{{ rec }}</li>
              <li v-if="!iaGlobal.recommandations?.length" class="ft-empty-mute">—</li>
            </ul>
          </div>
          <div class="ft-synthese-col ft-synthese-col--full">
            <div class="ft-stitle">Conclusion</div>
            <p class="ft-synthese-concl">{{ iaGlobal.conclusion || '—' }}</p>
          </div>
        </div>
        <div v-if="iaGlobal.generated_at" class="ft-synthese-footer">
          <i class="ti ti-calendar"></i> Générée le {{ formatDate(iaGlobal.generated_at) }}
        </div>
      </div>

      <!-- CTA IA quand pas encore générée -->
      <div v-else class="ft-ia-cta">
        <div class="ft-ia-cta__left">
          <i class="ti ti-robot ft-ia-cta__ico"></i>
          <div>
            <div class="ft-ia-cta__title">Synthèse IA globale</div>
            <div class="ft-ia-cta__sub">
              Analyse automatique de tous les outils utilisés sur cette fiche
            </div>
          </div>
        </div>
        <button class="ft-btn ft-btn--ia" @click="lancerIaGlobale"
          :disabled="iaProcessing || !form.id">
          <span v-if="iaProcessing" class="ft-spin"></span>
          <i v-else class="ti ti-sparkles"></i>
          {{ iaProcessing ? 'Génération en cours…' : 'Générer la synthèse IA' }}
        </button>
      </div>

      <!-- ══ CONTENU PRINCIPAL ═════════════════════════════════ -->
      <div class="ft-main">

        <!-- ◈ LISTE DES TESTS ──────────────────────────────────── -->
        <div v-if="!ficheActif && !obsVueActif && !fociVueActif" class="ft-tests-view">
          <div v-if="!programmeData.objectifs?.length" class="ft-empty">
            <div class="ft-empty__ico"><i class="ti ti-clipboard-off"></i></div>
            <p class="ft-empty__title">Aucun test affecté</p>
            <p class="ft-empty__sub">
              Contactez le Chef de Mission pour vous affecter des tests dans le programme de travail.
            </p>
          </div>
          <div v-else class="ft-obj-list">
            <div v-for="(obj, oi) in programmeData.objectifs" :key="oi" class="ft-obj">
              <div class="ft-obj__hd">
                <span class="ft-obj__num">{{ obj.num }}</span>
                <span class="ft-obj__label">{{ obj.objectif }}</span>
                <span v-if="obj._axe_rado" class="ft-tag ft-tag--blue">{{ obj._axe_rado }}</span>
              </div>
              <div class="ft-tests">
                <div v-for="(test, ti) in obj.tests" :key="ti" class="ft-test"
                  :class="getResultat(obj.num, tRef(test, oi, ti)) ? 'ft-test--done' : ''">
                  <div class="ft-test__row">
                    <code class="ft-ref">{{ tRef(test, oi, ti) }}</code>
                    <div class="ft-test__info">
                      <p class="ft-test__lbl">{{ test.libelle || '—' }}</p>
                      <div class="ft-test__chips">
                        <span v-if="test.periode_testee" class="ft-chip ft-chip--blue">
                          <i class="ti ti-calendar"></i>{{ test.periode_testee }}
                        </span>
                        <span v-if="test.lieu" class="ft-chip ft-chip--green">
                          <i class="ti ti-map-pin"></i>{{ test.lieu }}
                        </span>
                        <span v-if="test.taille_echantillon" class="ft-chip ft-chip--purple">
                          n={{ test.taille_echantillon }}
                        </span>
                      </div>
                      <div v-if="getOutilsPourTest(obj.num, tRef(test, oi, ti)).length"
                        class="ft-test__outils-badges">
                        <button v-for="ot in getOutilsPourTest(obj.num, tRef(test, oi, ti))"
                          :key="ot.outil_code + ot.proc_idx"
                          class="ft-outil-badge" :style="`--oc:${ot.color}`"
                          @click="ouvrirOutil(obj, test, ot.outil_code, ot.proc_idx, oi, ti)"
                          :title="ot.label">
                          <i class="ti ti-tool"></i>{{ ot.outil_code }}
                          <span v-if="ot.ia_score !== null && ot.ia_score !== undefined"
                            class="ft-outil-badge__score">{{ ot.ia_score }}/10</span>
                        </button>
                      </div>
                    </div>
                    <select v-if="!isLocked" class="ft-sel-result"
                      :value="getResultat(obj.num, tRef(test, oi, ti))"
                      @change="onResultatChange(obj.num, tRef(test, oi, ti), ($event.target as HTMLSelectElement).value)">
                      <option value="">— résultat —</option>
                      <option value="conforme">✅ Conforme</option>
                      <option value="ecart">⚠️ Écart</option>
                      <option value="nc">❌ Non conforme</option>
                      <option value="na">N/A</option>
                    </select>
                    <span v-else class="ft-result-pill"
                      :class="`frp--${getResultat(obj.num, tRef(test, oi, ti))}`">
                      {{ resultatLabel(getResultat(obj.num, tRef(test, oi, ti))) || '—' }}
                    </span>
                    <div class="ft-test__acts">
                      <button class="ft-btn ft-btn--fiche"
                        :class="getConstat(obj.num, tRef(test, oi, ti)) ? 'ft-btn--fiche-done' : ''"
                        @click="ouvrirFiche(obj, test, oi, ti)">
                        <i class="ti ti-clipboard-text"></i> Fiche test
                      </button>
                      <!-- Bouton observation directe XIV -->
                      <button class="ft-btn-obs-mini"
                        :class="testHasOutil(obj.num, tRef(test, oi, ti), 'XIV') ? 'ft-btn-obs-mini--done' : ''"
                        @click="ouvrirObsDirecte(obj, test, oi, ti)"
                        title="Fiche d'Observation FRAP">
                        <i class="ti ti-eye"></i>
                        <span v-if="testHasOutil(obj.num, tRef(test, oi, ti), 'XIV')"
                          class="ft-btn-obs-mini__dot"></span>
                      </button>
                      <!-- Dropdown outils -->
                      <div class="dropdown">
                        <button class="ft-ib ft-ib--sm ft-ib--tool dropdown-toggle"
                          :class="testHasAnyOutil(obj.num, tRef(test, oi, ti)) ? 'ft-ib--active' : ''"
                          data-bs-toggle="dropdown">
                          <i class="ti ti-tool"></i>
                          <span v-if="testHasAnyOutil(obj.num, tRef(test, oi, ti))"
                            class="ft-dot"></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end ft-dd shadow-sm">
                          <li class="ft-dd__head">Ouvrir un outil sur ce test</li>
                          <li v-for="outil in props.outilsIfaci" :key="outil.code">
                            <button class="ft-dd__item"
                              @click="ouvrirOutil(obj, test, outil.code, 0, oi, ti)">
                              <span class="ft-dd__dot"
                                :style="`background:${outil.color}`"></span>
                              <span class="ft-dd__code"
                                :style="`color:${outil.color}`">{{ outil.code }}</span>
                              <span class="ft-dd__lbl">{{ outil.label }}</span>
                              <i v-if="testHasOutil(obj.num, tRef(test, oi, ti), outil.code)"
                                class="ti ti-check ft-dd__chk"></i>
                            </button>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                  <!-- Procédures -->
                  <div v-if="test.procedures?.length" class="ft-procs">
                    <div v-for="(proc, pi) in test.procedures" :key="pi" class="ft-proc"
                      :class="getOutilsForProc(obj.num, tRef(test, oi, ti), pi).length ? 'ft-proc--linked' : ''">
                      <span class="ft-proc__n">{{ pi + 1 }}</span>
                      <span class="ft-proc__txt">{{ proc }}</span>
                      <div class="ft-proc__acts">
                        <button v-for="code in getOutilsForProc(obj.num, tRef(test, oi, ti), pi)"
                          :key="code" class="ft-outil-tag"
                          :style="`--ot:${outilColor(code)}`"
                          @click="ouvrirOutil(obj, test, code, pi, oi, ti)">
                          <i class="ti ti-tool"></i>{{ code }}
                        </button>
                        <button v-if="!isLocked" class="ft-ib ft-ib--xs"
                          :class="getOutilsForProc(obj.num, tRef(test, oi, ti), pi).length
                            ? 'ft-ib--edit' : 'ft-ib--add'"
                          @click="ouvrirChoixOutil(obj, test, pi, oi, ti)">
                          <i :class="getOutilsForProc(obj.num, tRef(test, oi, ti), pi).length
                            ? 'ti ti-edit' : 'ti ti-plus'"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ◈ FICHE DE TEST ─────────────────────────────────── -->
        <div v-if="ficheActif && !obsVueActif && !fociVueActif" class="ft-fiche-capture">
          <div class="ft-fiche-header">
            <button class="ft-btn-back" @click="ficheActif = null">
              <i class="ti ti-arrow-left"></i> Retour
            </button>
            <div class="ft-fiche-header__center">
              <div class="ft-fiche-icon"><i class="ti ti-clipboard-text"></i></div>
              <div>
                <div class="ft-fiche-title">Fiche de Test d'Audit</div>
                <div class="ft-fiche-sub">
                  {{ ficheActif.testRef }} · Objectif {{ ficheActif.objNum }}
                </div>
              </div>
            </div>
            <div class="ft-fiche-header__right">
              <button v-if="!isLocked" class="ft-btn-save-green" :disabled="processing"
                @click="submit(false)">
                <i class="ti ti-device-floppy"></i> Enregistrer
              </button>
              <button class="ft-btn-close" @click="ficheActif = null">
                <i class="ti ti-x"></i>
              </button>
            </div>
          </div>
          <div class="ft-fiche-body">
            <div class="fi-section-title">Objectif d'audit — {{ ficheActif.objNum }}</div>
            <div class="fi-objectif">{{ getObjectifTexte() }}</div>
            <table class="fi-auditeur-table">
              <tr><th>Auditeur interne</th><th>Date</th></tr>
              <tr>
                <td>{{ props.auditeurNom || '—' }}</td>
                <td>
                  <input v-if="!isLocked" type="date" class="fi-date-inp"
                    v-model="ficheActif.date" @change="scheduleAutoSave" />
                  <span v-else>{{ formatDate(ficheActif.date) || '—' }}</span>
                </td>
              </tr>
            </table>
            <div class="fi-section-title fi-section-title--light">
              Tests d'audit — {{ ficheActif.testRef }}
            </div>
            <div class="fi-tests-list">
              <template v-if="ficheActif.test?.procedures?.length">
                <div v-for="(proc, pi) in ficheActif.test.procedures" :key="pi"
                  class="fi-proc-block">
                  <div class="fi-test-row">
                    <div class="fi-test-num">{{ pi + 1 }}</div>
                    <div class="fi-test-lbl">{{ proc }}</div>
                    <div class="fi-proc-outils">
                      <button v-for="code in getOutilsForProc(ficheActif.objNum, ficheActif.testRef, pi)"
                        :key="code" class="fi-outil-btn"
                        :style="`--oc:${outilColor(code)}`"
                        @click="ouvrirOutilDepuisFiche(code, pi)">
                        <i class="ti ti-tool"></i> Outil {{ code }}
                      </button>
                      <button v-if="!isLocked" class="fi-add-outil-btn"
                        @click="ouvrirChoixOutil(ficheActif.obj, ficheActif.test, pi,
                          ficheActif.oi, ficheActif.ti)">
                        <i class="ti ti-plus"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </template>
              <template v-else>
                <div class="fi-test-row">
                  <div class="fi-test-num">1</div>
                  <div class="fi-test-lbl">{{ ficheActif.test?.libelle || '—' }}</div>
                  <select v-if="!isLocked" class="fi-result-sel"
                    :value="getResultat(ficheActif.objNum, ficheActif.testRef)"
                    @change="onResultatChange(ficheActif.objNum, ficheActif.testRef,
                      ($event.target as HTMLSelectElement).value)">
                    <option value="">— résultat —</option>
                    <option value="conforme">✅ Conforme</option>
                    <option value="ecart">⚠️ Écart</option>
                    <option value="nc">❌ Non conforme</option>
                    <option value="na">N/A</option>
                  </select>
                  <span v-else class="fi-result-badge"
                    :class="'fi-rb--' + getResultat(ficheActif.objNum, ficheActif.testRef)">
                    {{ resultatLabel(getResultat(ficheActif.objNum, ficheActif.testRef)) || '—' }}
                  </span>
                  <div class="fi-proc-outils">
                    <button v-for="code in getOutilsForProc(ficheActif.objNum, ficheActif.testRef, 0)"
                      :key="code" class="fi-outil-btn"
                      :style="`--oc:${outilColor(code)}`"
                      @click="ouvrirOutilDepuisFiche(code, 0)">
                      <i class="ti ti-tool"></i> Outil {{ code }}
                    </button>
                    <button v-if="!isLocked" class="fi-add-outil-btn"
                      @click="ouvrirChoixOutil(ficheActif.obj, ficheActif.test, 0,
                        ficheActif.oi, ficheActif.ti)">
                      <i class="ti ti-plus"></i>
                    </button>
                  </div>
                </div>
                <div class="fi-constat-row">
                  <div class="fi-constat-cell">
                    <label class="fi-mini-lbl">Constat / Observation</label>
                    <textarea v-if="!isLocked" class="fi-ta fi-ta--sm" rows="2"
                      :value="getConstat(ficheActif.objNum, ficheActif.testRef)"
                      @input="onConstatInput(ficheActif.objNum, ficheActif.testRef,
                        ($event.target as HTMLTextAreaElement).value)"
                      placeholder="Observation, écart constaté…"></textarea>
                    <div v-else class="fi-ro">
                      {{ getConstat(ficheActif.objNum, ficheActif.testRef) || '—' }}
                    </div>
                  </div>
                  <div class="fi-preuve-cell">
                    <label class="fi-mini-lbl">Référence de preuve</label>
                    <input v-if="!isLocked" type="text" class="fi-inp fi-inp--sm"
                      :value="getPreuve(ficheActif.objNum, ficheActif.testRef)"
                      @input="onPreuveInput(ficheActif.objNum, ficheActif.testRef,
                        ($event.target as HTMLInputElement).value)"
                      placeholder="Réf. document…" />
                    <div v-else class="fi-ro">
                      {{ getPreuve(ficheActif.objNum, ficheActif.testRef) || '—' }}
                    </div>
                  </div>
                </div>
              </template>
            </div>

            <!-- Résultats outils utilisés -->
            <div v-if="getOutilsPourTest(ficheActif.objNum, ficheActif.testRef).length"
              class="fi-outils-resultats">
              <div class="fi-or-title">
                <i class="ti ti-tool"></i> Résultats des outils utilisés
              </div>
              <div class="fi-or-list">
                <div v-for="ot in getOutilsPourTest(ficheActif.objNum, ficheActif.testRef)"
                  :key="ot.outil_code + ot.proc_idx"
                  class="fi-or-card" :style="`--oc:${ot.color}`"
                  @click="ouvrirOutilDepuisFiche(ot.outil_code, ot.proc_idx)">
                  <div class="fi-or-card__head">
                    <span class="fi-or-card__code" :style="`background:${ot.color}`">
                      {{ ot.outil_code }}
                    </span>
                    <span class="fi-or-card__label">{{ ot.label }}</span>
                    <span v-if="ot.statut" class="fi-or-card__st" :class="'fist--' + ot.statut">
                      {{ stLbl(ot.statut) }}
                    </span>
                    <span v-if="ot.ia_score !== null && ot.ia_score !== undefined"
                      class="fi-or-card__score">IA: {{ ot.ia_score }}/10</span>
                    <i class="ti ti-external-link fi-or-card__open"></i>
                  </div>
                  <div v-if="ot.resume" class="fi-or-card__body">
                    <p v-if="ot.resume.titre" class="fi-or-card__titre">{{ ot.resume.titre }}</p>
                    <div v-if="ot.resume.resultats?.length" class="fi-or-card__stats">
                      <span v-for="(r, ri) in ot.resume.resultats.slice(0, 3)" :key="ri"
                        class="fi-or-stat">
                        <span class="fi-or-stat__k">{{ r.label }}</span>
                        <span class="fi-or-stat__v">{{ r.valeur }}</span>
                      </span>
                    </div>
                    <p v-if="ot.resume.conclusion" class="fi-or-card__concl">
                      {{ ot.resume.conclusion }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- CTA vers Observation FRAP -->
            <div class="fi-obs-cta-inline">
              <button class="fi-obs-btn-mini" @click="ouvrirObsDepuisFiche">
                <i class="ti ti-eye"></i>
                <span>Observation directe (Outil XIV → FRAP)</span>
                <span v-if="obsXIVLiee" class="fi-obs-badge-liee">
                  <i class="ti ti-check"></i> Liée
                </span>
                <i class="ti ti-chevron-right fi-obs-arrow"></i>
              </button>
              <!-- Compteur FRAP pour ce test -->
              <span v-if="getFrapsForTest(ficheActif.objNum, ficheActif.testRef).length"
                class="fi-frap-counter">
                <i class="ti ti-clipboard-list"></i>
                {{ getFrapsForTest(ficheActif.objNum, ficheActif.testRef).length }} FRAP(s)
              </span>
            </div>
          </div>
          <div class="ft-fiche-footer">
            <button class="ft-btn ft-btn--ghost" @click="ficheActif = null">Fermer</button>
            <button v-if="!isLocked" class="ft-btn ft-btn--save" :disabled="processing"
              @click="submit(false)">
              <i class="ti ti-device-floppy"></i> Enregistrer
            </button>
          </div>
        </div>

        <!-- ◈ VUE OBSERVATION XIV → FRAP ──────────────────── -->
        <div v-if="obsVueActif && !fociVueActif" class="ft-obs-view">
          <div class="ft-obs-header">
            <button class="ft-btn-back" @click="fermerObsVue">
              <i class="ti ti-arrow-left"></i> Retour
            </button>
            <div class="ft-obs-header__center">
              <div class="ft-obs-icon"><i class="ti ti-eye"></i></div>
              <div>
                <div class="ft-obs-title">Fiche d'Observation</div>
                <div class="ft-obs-sub" v-if="obsVueContext">
                  <span class="ft-obs-sub__ref">{{ obsVueContext.testRef }}</span>
                  <span class="ft-obs-sub__sep">·</span>
                  <span>Objectif {{ obsVueContext.objNum }}</span>
                  <span v-if="obsVueContext.test?.libelle" class="ft-obs-sub__sep">·</span>
                  <span v-if="obsVueContext.test?.libelle" class="ft-obs-sub__lbl">
                    {{ obsVueContext.test.libelle }}
                  </span>
                </div>
              </div>
            </div>
            <div class="ft-obs-header__right">
              <!-- Indicateur FRAP existantes -->
              <span v-if="obsFrapsLiees.length" class="obs-frap-counter">
                <i class="ti ti-clipboard-list"></i>
                {{ obsFrapsLiees.length }} FRAP(s) générée(s)
              </span>
              <button v-if="!isLocked && !obsLoadingBD" class="ft-obs-save-btn"
                :disabled="obsSaving" @click="sauvegarderObservation">
                <span v-if="obsSaving" class="ft-spin"></span>
                <i v-else class="ti ti-device-floppy"></i>
                {{ obsSaving ? 'Sauvegarde…' : 'Enregistrer → FRAP' }}
              </button>
            </div>
          </div>

          <!-- Bandeau info flux -->
          <div class="obs-flow-banner">
            <div class="obs-flow-step obs-flow-step--active">
              <span class="obs-flow-num">1</span>
              <span>Observation</span>
            </div>
            <i class="ti ti-chevron-right obs-flow-sep"></i>
            <div class="obs-flow-step" :class="obsSavedOnce ? 'obs-flow-step--active' : ''">
              <span class="obs-flow-num">2</span>
              <span>Sauvegarde dans FRAP</span>
            </div>
            <i class="ti ti-chevron-right obs-flow-sep"></i>
            <div class="obs-flow-step" :class="obsFrapsLiees.length ? 'obs-flow-step--active' : ''">
              <span class="obs-flow-num">3</span>
              <span>Synthèse FOCI</span>
            </div>
          </div>

          <div v-if="obsLoadingBD" class="ft-obs-body ft-obs-body--loading">
            <div class="ft-obs-loading-box">
              <span class="ft-spin ft-spin--lg"></span>
              <p>Chargement de la fiche d'observation…</p>
            </div>
          </div>

          <div v-else class="ft-obs-body">

            <!-- Section 1 : Identification -->
            <div class="ft-obs-section">
              <div class="ft-obs-section__title">
                <i class="ti ti-info-circle"></i> 1. Identification du constat
              </div>
              <div class="ft-obs-grid ft-obs-grid--3" style="padding:.85rem 1rem .5rem">
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Date d'observation</label>
                  <input type="date" class="ft-obs-inp"
                    v-model="obsData.date_observation" :disabled="isLocked" />
                </div>
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Heure début</label>
                  <input type="time" class="ft-obs-inp"
                    v-model="obsData.heure_debut" :disabled="isLocked" />
                </div>
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Heure fin</label>
                  <input type="time" class="ft-obs-inp"
                    v-model="obsData.heure_fin" :disabled="isLocked" />
                </div>
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Auditeur</label>
                  <input type="text" class="ft-obs-inp"
                    v-model="obsData.auditeur" :disabled="isLocked"
                    :placeholder="props.auditeurNom" />
                </div>
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Localisation</label>
                  <input type="text" class="ft-obs-inp"
                    v-model="obsData.localisation" :disabled="isLocked"
                    placeholder="Service, bureau, magasin…" />
                </div>
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Interlocuteurs présents</label>
                  <input type="text" class="ft-obs-inp"
                    v-model="obsData.interlocuteurs_presents" :disabled="isLocked"
                    placeholder="Noms, fonctions…" />
                </div>
                <div class="ft-obs-field ft-obs-field--full">
                  <label class="ft-obs-lbl">Objectif d'audit</label>
                  <input type="text" class="ft-obs-inp"
                    v-model="obsData.objectif_audit" :disabled="isLocked"
                    placeholder="Vérifier que…" />
                </div>
                <div class="ft-obs-field ft-obs-field--full">
                  <label class="ft-obs-lbl">Tâche / Lieu observé</label>
                  <input type="text" class="ft-obs-inp"
                    v-model="obsData.tache_local_observer" :disabled="isLocked"
                    placeholder="Processus observé…" />
                </div>
              </div>
            </div>

            <!-- Section 2 : Constat principal (→ FRAP) -->
            <div class="ft-obs-section ft-obs-section--frap">
              <div class="ft-obs-section__title ft-obs-section__title--frap">
                <i class="ti ti-clipboard-text"></i> 2. Constat principal
                <span class="obs-frap-badge">→ Alimentera la FRAP</span>
              </div>
              <div class="ft-obs-grid ft-obs-grid--1" style="padding:.85rem 1rem .5rem">
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Rubrique</label>
                  <input type="text" class="ft-obs-inp"
                    v-model="obsFormFrap.rubrique" :disabled="isLocked"
                    placeholder="Ex. : Gestion des stocks, Trésorerie…" />
                </div>
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Sous-rubrique (optionnel)</label>
                  <input type="text" class="ft-obs-inp"
                    v-model="obsFormFrap.sous_rubrique" :disabled="isLocked"
                    placeholder="Sous-domaine précis…" />
                </div>
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Objectif de contrôle</label>
                  <input type="text" class="ft-obs-inp"
                    v-model="obsFormFrap.objectif_controle" :disabled="isLocked"
                    placeholder="Ex. : Protection du patrimoine, Régularité…" />
                </div>
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Intitulé du problème</label>
                  <input type="text" class="ft-obs-inp"
                    v-model="obsFormFrap.intitule_probleme" :disabled="isLocked"
                    placeholder="Ex. : Absence de rapprochement bancaire mensuel…" />
                </div>
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Fait / Constat observé <span class="req">*</span></label>
                  <textarea class="ft-obs-ta" v-model="obsFormFrap.fait_constats"
                    rows="4" :disabled="isLocked"
                    placeholder="Décrire précisément ce qui a été observé…"></textarea>
                </div>
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Problème identifié <span class="req">*</span></label>
                  <textarea class="ft-obs-ta" v-model="obsFormFrap.probleme"
                    rows="3" :disabled="isLocked"
                    placeholder="Quel est le problème résultant du constat ?"></textarea>
                </div>
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Critère / Référentiel</label>
                  <textarea class="ft-obs-ta" v-model="obsFormFrap.critere_referentiel"
                    rows="2" :disabled="isLocked"
                    placeholder="Manuel des procédures, texte réglementaire…"></textarea>
                </div>
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Niveau de contrôle interne</label>
                  <select class="ft-obs-sel-niveau"
                    v-model="obsFormFrap.niveau_controle_interne" :disabled="isLocked"
                    :style="niveauControleStyle(obsFormFrap.niveau_controle_interne)">
                    <option value="">— Choisir —</option>
                    <option value="satisfaisant">✅ Satisfaisant</option>
                    <option value="a_ameliorer">🔶 À améliorer</option>
                    <option value="insuffisant">🔴 Insuffisant</option>
                    <option value="critique">⛔ Critique</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Section 3 : Causes identifiées (→ FRAP) -->
            <div class="ft-obs-section ft-obs-section--frap">
              <div class="ft-obs-section__title ft-obs-section__title--frap">
                <i class="ti ti-list-check"></i> 3. Causes identifiées
                <span class="obs-frap-badge">→ Alimentera la FRAP</span>
              </div>
              <div class="ft-obs-causes-grid">
                <label v-for="cause in causesList" :key="cause.value" class="ft-obs-check">
                  <input type="checkbox" :value="cause.value"
                    v-model="obsFormFrap.causes_selection" :disabled="isLocked" />
                  <span>{{ cause.label }}</span>
                </label>
              </div>
              <div class="ft-obs-grid ft-obs-grid--1" style="padding:.5rem 1rem .85rem">
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Développement des causes <span class="req">*</span></label>
                  <textarea class="ft-obs-ta" v-model="obsFormFrap.causes"
                    rows="3" :disabled="isLocked"
                    placeholder="Expliquer les causes racines identifiées…"></textarea>
                </div>
              </div>
            </div>

            <!-- Section 4 : Impacts & Conséquences (→ FRAP) -->
            <div class="ft-obs-section ft-obs-section--frap">
              <div class="ft-obs-section__title ft-obs-section__title--frap">
                <i class="ti ti-alert-triangle"></i> 4. Impacts & Conséquences
                <span class="obs-frap-badge">→ Alimentera la FRAP</span>
              </div>
              <div class="ft-obs-risks-table">
                <div v-for="risk in risksList" :key="risk.key" class="ft-obs-risk-item">
                  <label class="ft-obs-check">
                    <input type="checkbox" :value="risk.key"
                      v-model="obsFormFrap.consequences_selection" :disabled="isLocked" />
                    <span class="risk-label" :style="{ color: risk.color }">
                      {{ risk.label }}
                    </span>
                  </label>
                </div>
              </div>
              <div class="ft-obs-grid ft-obs-grid--1"
                style="padding:.5rem 1rem .85rem">
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Description des impacts <span class="req">*</span></label>
                  <textarea class="ft-obs-ta" v-model="obsFormFrap.impacts"
                    rows="3" :disabled="isLocked"
                    placeholder="Risque financier, opérationnel, réglementaire…"></textarea>
                </div>
              </div>
            </div>

            <!-- Section 5 : Constats détaillés -->
            <div class="ft-obs-section">
              <div class="ft-obs-section__title">
                <i class="ti ti-table"></i> 5. Points observés
                <span class="obs-badge-count" style="margin-left:.5rem">
                  {{ obsData.constats.length }}
                </span>
                <button v-if="!isLocked" class="obs-add" style="margin-left:.5rem"
                  @click="obsData.constats.push({
                    element_observe: '', conforme_referentiel: '',
                    ecart_constate: '', risque_associe: '', preuve: ''
                  })">
                  <i class="ti ti-plus"></i> Ajouter
                </button>
              </div>
              <div class="obs-table-wrap">
                <table class="obs-tbl">
                  <thead>
                    <tr>
                      <th class="tc" style="width:28px">N°</th>
                      <th>Élément observé</th>
                      <th class="tc" style="width:90px">Conforme?</th>
                      <th>Écart constaté</th>
                      <th>Risque associé</th>
                      <th style="width:110px">Preuve</th>
                      <th v-if="!isLocked" style="width:28px"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="!obsData.constats.length">
                      <td colspan="7" class="obs-ec">Aucun point observé</td>
                    </tr>
                    <tr v-for="(c, ci) in obsData.constats" :key="ci"
                      :style="c.conforme_referentiel === 'non' ? 'background:#fef2f2'
                        : c.conforme_referentiel === 'oui' ? 'background:#f0fdf4'
                        : c.conforme_referentiel === 'partiel' ? 'background:#fffbeb' : ''">
                      <td class="tc obs-n">{{ ci + 1 }}</td>
                      <td>
                        <textarea class="obs-ta-sm" v-model="c.element_observe"
                          rows="2" :disabled="isLocked"></textarea>
                      </td>
                      <td class="tc">
                        <select class="obs-sel-sm"
                          v-model="c.conforme_referentiel" :disabled="isLocked">
                          <option value="">—</option>
                          <option value="oui">✅ Oui</option>
                          <option value="non">❌ Non</option>
                          <option value="partiel">⚠️ Partiel</option>
                        </select>
                      </td>
                      <td>
                        <textarea class="obs-ta-sm" v-model="c.ecart_constate"
                          rows="2" :disabled="isLocked"></textarea>
                      </td>
                      <td>
                        <textarea class="obs-ta-sm" v-model="c.risque_associe"
                          rows="2" :disabled="isLocked"></textarea>
                      </td>
                      <td>
                        <input class="obs-inp-sm" type="text"
                          v-model="c.preuve" :disabled="isLocked"
                          placeholder="Réf. photo, doc…" />
                      </td>
                      <td v-if="!isLocked" class="tc">
                        <button class="obs-del" @click="obsData.constats.splice(ci, 1)">
                          <i class="ti ti-trash"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Section 6 : Recommandation (→ FRAP) -->
            <div class="ft-obs-section ft-obs-section--frap">
              <div class="ft-obs-section__title ft-obs-section__title--frap">
                <i class="ti ti-bulb"></i> 6. Recommandation & Plan d'action
                <span class="obs-frap-badge">→ Alimentera la FRAP</span>
              </div>
              <div class="ft-obs-grid ft-obs-grid--1" style="padding:.85rem 1rem">
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Recommandation <span class="req">*</span></label>
                  <textarea class="ft-obs-ta" v-model="obsFormFrap.recommandation"
                    rows="4" :disabled="isLocked"
                    placeholder="Décrire précisément la recommandation formulée…"></textarea>
                </div>
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Commentaires de l'audité</label>
                  <textarea class="ft-obs-ta" v-model="obsFormFrap.commentaires_audite"
                    rows="3" :disabled="isLocked"
                    placeholder="Réaction de l'entité auditée…"></textarea>
                </div>
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Points forts</label>
                  <textarea class="ft-obs-ta" v-model="obsFormFrap.points_forts"
                    rows="2" :disabled="isLocked"
                    placeholder="Ce qui fonctionne bien malgré les constats…"></textarea>
                </div>
              </div>
              <div class="ft-obs-grid ft-obs-grid--3" style="padding:0 1rem .85rem">
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Date d'échéance</label>
                  <input type="date" class="ft-obs-inp"
                    v-model="obsFormFrap.date_echeance" :disabled="isLocked" />
                </div>
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Personne responsable</label>
                  <input type="text" class="ft-obs-inp"
                    v-model="obsFormFrap.personne_responsable" :disabled="isLocked"
                    placeholder="Nom / Fonction…" />
                </div>
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Livrable attendu</label>
                  <input type="text" class="ft-obs-inp"
                    v-model="obsFormFrap.livrable" :disabled="isLocked"
                    placeholder="Rapport, procédure, tableau…" />
                </div>
              </div>
            </div>

            <!-- Section 7 : Synthèse & Niveau -->
            <div class="ft-obs-section">
              <div class="ft-obs-section__title">
                <i class="ti ti-chart-bar"></i> 7. Synthèse & Niveau
              </div>
              <div class="ft-obs-grid ft-obs-grid--2" style="padding:.85rem 1rem .5rem">
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Niveau du risque</label>
                  <select class="ft-obs-sel-niveau" v-model="obsData.niveau_controle"
                    :disabled="isLocked"
                    :style="niveauControleStyle(obsData.niveau_controle)">
                    <option value="">— Choisir —</option>
                    <option value="1_faible">1 – Faible</option>
                    <option value="2_moyen">2 – Moyen</option>
                    <option value="3_satisfaisant">3 – Satisfaisant</option>
                    <option value="4_bon">4 – Bon</option>
                    <option value="5_excellent">5 – Excellent</option>
                  </select>
                  <div v-if="obsData.niveau_controle" class="ft-obs-niveau-badge"
                    :style="niveauBadgeStyle(obsData.niveau_controle)">
                    {{ niveauLabel(obsData.niveau_controle) }}
                  </div>
                </div>
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Niveau de contrôle</label>
                  <select class="ft-obs-sel-niveau" v-model="obsData.niveau_synthese"
                    :disabled="isLocked"
                    :style="niveauSyntheseStyle(obsData.niveau_synthese)">
                    <option value="">— Choisir —</option>
                    <option value="conforme">✅ Conforme</option>
                    <option value="a_ameliorer">🔶 À améliorer</option>
                    <option value="insuffisant">🔴 Insuffisant</option>
                    <option value="critique">⛔ Critique</option>
                  </select>
                  <div v-if="obsData.niveau_synthese" class="ft-obs-niveau-badge"
                    :style="niveauSyntheseBadgeStyle(obsData.niveau_synthese)">
                    {{ niveauSyntheseLabel(obsData.niveau_synthese) }}
                  </div>
                </div>
                <div class="ft-obs-field ft-obs-field--full">
                  <label class="ft-obs-lbl">Conclusion générale</label>
                  <textarea class="ft-obs-ta" v-model="obsData.conclusion"
                    rows="3" :disabled="isLocked"
                    placeholder="Conclusion de la fiche d'observation…"></textarea>
                </div>
              </div>
            </div>

            <!-- Section 8 : Recommandations détaillées -->
            <div class="ft-obs-section">
              <div class="ft-obs-section__title">
                <i class="ti ti-list-check"></i> 8. Plan d'action détaillé
                <span class="obs-badge-count" style="margin-left:.5rem">
                  {{ obsData.recommandations.length }}
                </span>
                <button v-if="!isLocked" class="obs-add" style="margin-left:.5rem"
                  @click="obsData.recommandations.push({
                    recommandation: '', responsable: '', date_prevue: '',
                    livrable: '', commentaire_auditeur: '', commentaire_audite: ''
                  })">
                  <i class="ti ti-plus"></i> Ligne
                </button>
              </div>
              <div class="obs-table-wrap" style="margin:.75rem">
                <table class="obs-tbl">
                  <thead>
                    <tr>
                      <th class="tc" style="width:28px">N°</th>
                      <th>Recommandation</th>
                      <th style="width:140px">Responsable</th>
                      <th style="width:110px">Date prévue</th>
                      <th>Livrable</th>
                      <th>Comm. auditeur</th>
                      <th v-if="!isLocked" style="width:28px"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="!obsData.recommandations.length">
                      <td colspan="7" class="obs-ec">Aucune recommandation</td>
                    </tr>
                    <tr v-for="(rec, ri) in obsData.recommandations" :key="ri">
                      <td class="tc obs-n">{{ ri + 1 }}</td>
                      <td>
                        <textarea class="obs-ta-sm" v-model="rec.recommandation"
                          rows="2" :disabled="isLocked"
                          placeholder="Mettre en place…"></textarea>
                      </td>
                      <td>
                        <input class="obs-inp-sm" type="text"
                          v-model="rec.responsable" :disabled="isLocked"
                          placeholder="Nom / Fonction" />
                      </td>
                      <td>
                        <input class="obs-inp-sm" type="date"
                          v-model="rec.date_prevue" :disabled="isLocked" />
                      </td>
                      <td>
                        <textarea class="obs-ta-sm" v-model="rec.livrable"
                          rows="2" :disabled="isLocked"
                          placeholder="Livrable attendu…"></textarea>
                      </td>
                      <td>
                        <textarea class="obs-ta-sm" v-model="rec.commentaire_auditeur"
                          rows="2" :disabled="isLocked"
                          placeholder="Commentaire…"></textarea>
                      </td>
                      <td v-if="!isLocked" class="tc">
                        <button class="obs-del"
                          @click="obsData.recommandations.splice(ri, 1)">
                          <i class="ti ti-trash"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- FRAPs générées depuis cette observation -->
            <div v-if="obsFrapsLiees.length" class="obs-fraps-generated">
              <div class="obs-fraps-hd">
                <i class="ti ti-clipboard-list"></i>
                <strong>FRAPs générées depuis cette observation</strong>
                <span class="obs-badge-count">{{ obsFrapsLiees.length }}</span>
                <span class="obs-fraps-info">
                  Ces FRAPs apparaissent automatiquement dans la Synthèse FOCI
                </span>
              </div>
              <div class="obs-fraps-list">
                <div v-for="f in obsFrapsLiees" :key="f.id" class="obs-frap-card"
                  :class="fociNiveauRowClass(f.niveau_controle_interne)">
                  <span class="obs-frap-num">{{ f.num_frap }}</span>
                  <span class="obs-frap-rub">{{ f.rubrique }}</span>
                  <span class="obs-frap-pb">{{ f.probleme || '—' }}</span>
                  <span class="foci-niv-badge"
                    :style="fociNiveauStyle(f.niveau_controle_interne)">
                    {{ fociNiveauLabel(f.niveau_controle_interne) || '—' }}
                  </span>
                </div>
              </div>
            </div>

          </div><!-- /ft-obs-body -->

          <div class="ft-obs-footer">
            <button class="ft-btn ft-btn--ghost" @click="fermerObsVue">Retour</button>
            <button v-if="!isLocked && !obsLoadingBD" class="ft-obs-save-btn"
              :disabled="obsSaving" @click="sauvegarderObservation">
              <span v-if="obsSaving" class="ft-spin"></span>
              <i v-else class="ti ti-device-floppy"></i>
              {{ obsSaving ? 'Sauvegarde…' : "Enregistrer l'observation → FRAP" }}
            </button>
          </div>
        </div>

        <!-- ◈ VUE SYNTHÈSE FOCI ──────────────────────────────── -->
        <div v-if="fociVueActif" class="foci-view">
          <!-- Header FOCI -->
          <div class="foci-hd">
            <button class="ft-btn-back" @click="fociVueActif = false">
              <i class="ti ti-arrow-left"></i> Retour
            </button>
            <div class="foci-hd__center">
              <div class="foci-hd__icon"><i class="ti ti-file-text"></i></div>
              <div>
                <div class="foci-hd__title">
                  Feuille d'Observations Contrôle Interne — FOCI
                </div>
                <div class="foci-hd__sub">Synthèse des FRAP · {{ missionLibelle }}</div>
              </div>
            </div>
            <div class="foci-hd__right">
              <button class="foci-regen-btn" @click="genererSyntheseFoci"
                :disabled="fociLoading">
                <span v-if="fociLoading" class="ft-spin ft-spin--xs"></span>
                <i v-else class="ti ti-refresh"></i>
                {{ fociLoading ? 'Génération…' : 'Regénérer' }}
              </button>
            </div>
          </div>

          <!-- Barre état -->
          <div v-if="fociLoading" class="foci-status foci-status--gen">
            <span class="ft-spin ft-spin--xs"></span>
            Génération de la synthèse FOCI en cours…
          </div>
          <div v-else-if="fociLastGen" class="foci-status foci-status--ok">
            <i class="ti ti-circle-check"></i>
            Synthèse générée · {{ fociLastGen }} —
            {{ frapsLocal.length }} FRAP(s) · {{ fociTotalRubriques }} rubrique(s)
          </div>
          <div v-else-if="!frapsLocal.length" class="foci-status foci-status--warn">
            <i class="ti ti-alert-triangle"></i>
            Aucune FRAP enregistrée.
            Créez des observations (Outil XIV) et sauvegardez-les pour générer des FRAPs.
          </div>
          <div v-else class="foci-status foci-status--info">
            <i class="ti ti-info-circle"></i>
            {{ frapsLocal.length }} FRAP(s) disponible(s).
            Cliquez sur « Regénérer » pour mettre à jour la synthèse.
          </div>

          <!-- Document FOCI -->
          <div class="foci-doc">
            <!-- En-tête institutionnel -->
            <div class="foci-entete">
              <div class="foci-entete__left">
                <div class="foci-entete__logo">
                  <i class="ti ti-building-bank"></i>
                </div>
                <div class="foci-entete__meta">
                  <div class="foci-entete__zone">
                    ZONE D'EN-TÊTE — STRUCTURE AUDIT INTERNE
                  </div>
                  <div class="foci-entete__row">
                    <span class="foci-mk">Code phase :</span>
                    <span class="foci-mv">{{ form.code || '—' }}</span>
                    <span class="foci-msep"></span>
                    <span class="foci-mk">Code mission :</span>
                    <span class="foci-mv">
                      {{ props.missionContext?.code_mission || '—' }}
                    </span>
                  </div>
                  <div class="foci-entete__row">
                    <span class="foci-mk">Date :</span>
                    <span class="foci-mv">
                      {{ formatDate(new Date().toISOString()) }}
                    </span>
                  </div>
                </div>
              </div>
              <div class="foci-entete__center">
                <div class="foci-entete__title">
                  Feuille d'Observations Contrôle Interne
                </div>
                <div class="foci-entete__sub">FOCI — Synthèse automatique des FRAP</div>
                <div class="foci-entete__note">
                  <i class="ti ti-info-circle"></i>
                  Édition automatique · Une seule FOCI par mission
                </div>
              </div>
              <div class="foci-entete__right">
                <div class="foci-mission-card">
                  <div class="foci-mc-lbl">INTITULÉ DE LA MISSION</div>
                  <div class="foci-mc-val">{{ missionLibelle || '—' }}</div>
                  <div class="foci-mc-stats">
                    <span class="foci-pill foci-pill--blue">
                      {{ frapsLocal.length }} FRAP
                    </span>
                    <span class="foci-pill foci-pill--green">
                      {{ fociTotalRubriques }} Rubriques
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Légende colonnes -->
            <div class="foci-legend">
              <div class="foci-lc foci-lc--num">N° FRAP</div>
              <div class="foci-lc foci-lc--niv">Niveau CI</div>
              <div class="foci-lc foci-lc--fait">Fait / Constat</div>
              <div class="foci-lc foci-lc--pb">Problème</div>
              <div class="foci-lc foci-lc--cause">Causes</div>
              <div class="foci-lc foci-lc--impact">Impacts</div>
              <div class="foci-lc foci-lc--reco">Recommandation</div>
              <div class="foci-lc foci-lc--comm">Comm. Audité</div>
              <div class="foci-lc foci-lc--pts">Points forts</div>
              <div class="foci-lc foci-lc--date">Date éch.</div>
              <div class="foci-lc foci-lc--resp">Responsable</div>
              <div class="foci-lc foci-lc--livr">Livrable</div>
            </div>

            <!-- État vide -->
            <div v-if="!fociGrouped.length" class="foci-empty">
              <div class="foci-empty__ico"><i class="ti ti-clipboard-off"></i></div>
              <p class="foci-empty__title">Aucune FRAP disponible</p>
              <p class="foci-empty__sub">
                Créez des observations (Outil XIV) sur vos tests d'audit, renseignez les
                constats, causes et recommandations, puis sauvegardez pour alimenter la FOCI.
              </p>
              <button v-if="frapsLocal.length && !fociLastGen" class="foci-gen-cta"
                @click="genererSyntheseFoci" :disabled="fociLoading">
                <span v-if="fociLoading" class="ft-spin"></span>
                <i v-else class="ti ti-refresh"></i>
                Générer la synthèse FOCI ({{ frapsLocal.length }} FRAP)
              </button>
            </div>

            <!-- Objectifs → Rubriques → Sous-rubriques → FRAP -->
            <div v-for="(objCtrl, oci) in fociGrouped" :key="oci" class="foci-obj-block">
              <div class="foci-obj-banner">
                <span class="foci-obj-lbl">Objectif de contrôle</span>
                <span class="foci-obj-txt">{{ objCtrl.objectif_controle }}</span>
              </div>
              <div v-for="(rubr, ri) in objCtrl.rubriques" :key="ri"
                class="foci-rubr-block">
                <div class="foci-rubr-banner">
                  <i class="ti ti-tag"></i> Rubrique :
                  <strong>{{ rubr.rubrique }}</strong>
                </div>
                <div v-for="(ssrubr, sri) in rubr.sous_rubriques" :key="sri">
                  <div v-if="ssrubr.sous_rubrique" class="foci-ssrubr-banner">
                    <i class="ti ti-corner-down-right"></i>
                    Sous-rubrique : {{ ssrubr.sous_rubrique }}
                  </div>
                  <!-- Lignes FRAP -->
                  <div v-for="(frap, fi) in ssrubr.fraps" :key="frap.id"
                    class="foci-row"
                    :class="fociNiveauRowClass(frap.niveau_controle_interne)">
                    <!-- N° FRAP -->
                    <div class="foci-cell foci-cell--num">
                      <button class="foci-num-btn"
                        @click="fociModalOuvrir(frap)"
                        :title="'Voir/modifier FRAP ' + frap.num_frap">
                        <span class="foci-num-badge">
                          {{ frap.num_frap || ('FRAP-' + (fi + 1)) }}
                        </span>
                        <i class="ti ti-external-link foci-ext"></i>
                      </button>
                    </div>
                    <!-- Niveau CI -->
                    <div class="foci-cell foci-cell--niv">
                      <span class="foci-niv-badge"
                        :style="fociNiveauStyle(frap.niveau_controle_interne)">
                        {{ fociNiveauLabel(frap.niveau_controle_interne) }}
                      </span>
                    </div>
                    <!-- Fait/Constat -->
                    <div class="foci-cell foci-cell--fait">
                      <div class="foci-editable"
                        v-if="!fociEditMode(frap.id, 'fait_constats')">
                        <span class="foci-txt"
                          @dblclick="fociStartEdit(frap, 'fait_constats')">
                          {{ frap.fait_constats || '—' }}
                        </span>
                        <button v-if="!isLocked" class="foci-edit-btn"
                          @click="fociStartEdit(frap, 'fait_constats')">
                          <i class="ti ti-pencil"></i>
                        </button>
                      </div>
                      <textarea v-else class="foci-ta"
                        v-model="fociEditData[frap.id + '_fait_constats']"
                        rows="3"
                        @blur="fociSaveField(frap, 'fait_constats')"
                        @keydown.esc="fociCancelEdit(frap.id, 'fait_constats')"
                        autofocus></textarea>
                    </div>
                    <!-- Problème -->
                    <div class="foci-cell foci-cell--pb">
                      <div class="foci-editable"
                        v-if="!fociEditMode(frap.id, 'probleme')">
                        <span class="foci-txt"
                          @dblclick="fociStartEdit(frap, 'probleme')">
                          {{ frap.probleme || '—' }}
                        </span>
                        <button v-if="!isLocked" class="foci-edit-btn"
                          @click="fociStartEdit(frap, 'probleme')">
                          <i class="ti ti-pencil"></i>
                        </button>
                      </div>
                      <textarea v-else class="foci-ta"
                        v-model="fociEditData[frap.id + '_probleme']"
                        rows="3"
                        @blur="fociSaveField(frap, 'probleme')"
                        @keydown.esc="fociCancelEdit(frap.id, 'probleme')"></textarea>
                    </div>
                    <!-- Causes -->
                    <div class="foci-cell foci-cell--cause">
                      <div class="foci-editable"
                        v-if="!fociEditMode(frap.id, 'causes')">
                        <span class="foci-txt"
                          @dblclick="fociStartEdit(frap, 'causes')">
                          {{ frap.causes || '—' }}
                        </span>
                        <button v-if="!isLocked" class="foci-edit-btn"
                          @click="fociStartEdit(frap, 'causes')">
                          <i class="ti ti-pencil"></i>
                        </button>
                      </div>
                      <textarea v-else class="foci-ta"
                        v-model="fociEditData[frap.id + '_causes']"
                        rows="3"
                        @blur="fociSaveField(frap, 'causes')"
                        @keydown.esc="fociCancelEdit(frap.id, 'causes')"></textarea>
                    </div>
                    <!-- Impacts -->
                    <div class="foci-cell foci-cell--impact">
                      <div class="foci-editable"
                        v-if="!fociEditMode(frap.id, 'impacts')">
                        <span class="foci-txt"
                          @dblclick="fociStartEdit(frap, 'impacts')">
                          {{ frap.impacts || '—' }}
                        </span>
                        <button v-if="!isLocked" class="foci-edit-btn"
                          @click="fociStartEdit(frap, 'impacts')">
                          <i class="ti ti-pencil"></i>
                        </button>
                      </div>
                      <textarea v-else class="foci-ta"
                        v-model="fociEditData[frap.id + '_impacts']"
                        rows="3"
                        @blur="fociSaveField(frap, 'impacts')"
                        @keydown.esc="fociCancelEdit(frap.id, 'impacts')"></textarea>
                    </div>
                    <!-- Recommandation -->
                    <div class="foci-cell foci-cell--reco">
                      <div class="foci-editable"
                        v-if="!fociEditMode(frap.id, 'recommandation')">
                        <span class="foci-txt foci-txt--reco"
                          @dblclick="fociStartEdit(frap, 'recommandation')">
                          {{ frap.recommandation || '—' }}
                        </span>
                        <button v-if="!isLocked" class="foci-edit-btn"
                          @click="fociStartEdit(frap, 'recommandation')">
                          <i class="ti ti-pencil"></i>
                        </button>
                      </div>
                      <textarea v-else class="foci-ta"
                        v-model="fociEditData[frap.id + '_recommandation']"
                        rows="3"
                        @blur="fociSaveField(frap, 'recommandation')"
                        @keydown.esc="fociCancelEdit(frap.id, 'recommandation')"></textarea>
                    </div>
                    <!-- Commentaires audité -->
                    <div class="foci-cell foci-cell--comm">
                      <div class="foci-editable"
                        v-if="!fociEditMode(frap.id, 'commentaires_audite')">
                        <span class="foci-txt"
                          @dblclick="fociStartEdit(frap, 'commentaires_audite')">
                          {{ frap.commentaires_audite || '—' }}
                        </span>
                        <button v-if="!isLocked" class="foci-edit-btn"
                          @click="fociStartEdit(frap, 'commentaires_audite')">
                          <i class="ti ti-pencil"></i>
                        </button>
                      </div>
                      <textarea v-else class="foci-ta"
                        v-model="fociEditData[frap.id + '_commentaires_audite']"
                        rows="3"
                        @blur="fociSaveField(frap, 'commentaires_audite')"
                        @keydown.esc="fociCancelEdit(frap.id, 'commentaires_audite')"></textarea>
                    </div>
                    <!-- Points forts -->
                    <div class="foci-cell foci-cell--pts">
                      <div class="foci-editable"
                        v-if="!fociEditMode(frap.id, 'points_forts')">
                        <span class="foci-txt foci-txt--green"
                          @dblclick="fociStartEdit(frap, 'points_forts')">
                          {{ frap.points_forts || '—' }}
                        </span>
                        <button v-if="!isLocked" class="foci-edit-btn"
                          @click="fociStartEdit(frap, 'points_forts')">
                          <i class="ti ti-pencil"></i>
                        </button>
                      </div>
                      <textarea v-else class="foci-ta"
                        v-model="fociEditData[frap.id + '_points_forts']"
                        rows="3"
                        @blur="fociSaveField(frap, 'points_forts')"
                        @keydown.esc="fociCancelEdit(frap.id, 'points_forts')"></textarea>
                    </div>
                    <!-- Date échéance -->
                    <div class="foci-cell foci-cell--date">
                      <input v-if="!isLocked" type="date" class="foci-date-inp"
                        :value="frap.date_echeance"
                        @change="fociSaveFieldDirect(frap, 'date_echeance',
                          ($event.target as HTMLInputElement).value)" />
                      <span v-else class="foci-txt">
                        {{ formatDate(frap.date_echeance) || '—' }}
                      </span>
                    </div>
                    <!-- Responsable -->
                    <div class="foci-cell foci-cell--resp">
                      <div class="foci-editable"
                        v-if="!fociEditMode(frap.id, 'personne_responsable')">
                        <span class="foci-txt"
                          @dblclick="fociStartEdit(frap, 'personne_responsable')">
                          {{ frap.personne_responsable || '—' }}
                        </span>
                        <button v-if="!isLocked" class="foci-edit-btn"
                          @click="fociStartEdit(frap, 'personne_responsable')">
                          <i class="ti ti-pencil"></i>
                        </button>
                      </div>
                      <input v-else class="foci-inp"
                        v-model="fociEditData[frap.id + '_personne_responsable']"
                        @blur="fociSaveField(frap, 'personne_responsable')"
                        @keydown.esc="fociCancelEdit(frap.id, 'personne_responsable')" />
                    </div>
                    <!-- Livrable -->
                    <div class="foci-cell foci-cell--livr">
                      <div class="foci-editable"
                        v-if="!fociEditMode(frap.id, 'livrable')">
                        <span class="foci-txt"
                          @dblclick="fociStartEdit(frap, 'livrable')">
                          {{ frap.livrable || '—' }}
                        </span>
                        <button v-if="!isLocked" class="foci-edit-btn"
                          @click="fociStartEdit(frap, 'livrable')">
                          <i class="ti ti-pencil"></i>
                        </button>
                      </div>
                      <input v-else class="foci-inp"
                        v-model="fociEditData[frap.id + '_livrable']"
                        @blur="fociSaveField(frap, 'livrable')"
                        @keydown.esc="fociCancelEdit(frap.id, 'livrable')" />
                    </div>
                  </div><!-- /foci-row -->
                </div>
              </div>
            </div>

            <div class="foci-foot-note">
              <i class="ti ti-info-circle"></i>
              NB — Double-cliquez sur une cellule pour la modifier directement.
              Les modifications sont sauvegardées automatiquement dans la FRAP correspondante.
            </div>
          </div><!-- /foci-doc -->
        </div><!-- /fociVueActif -->

      </div><!-- /ft-main -->
    </div><!-- /ft-shell -->

    <!-- ══ MODAL CHOIX OUTILS ══════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="om-fade">
        <div v-if="om.visible" class="om-overlay" @click.self="om.visible = false">
          <div class="om-dialog">
            <div class="om-hd">
              <div class="om-hd__left">
                <div class="om-hd__icon"><i class="ti ti-tool"></i></div>
                <div>
                  <h2 class="om-hd__title">Outils IFACI</h2>
                  <div class="om-hd__sub">
                    <span class="om-ctx">{{ om.testRef }}</span>
                    <span v-if="om.procIdx !== null"
                      class="om-ctx om-ctx--proc">
                      Procédure {{ (om.procIdx ?? 0) + 1 }}
                    </span>
                  </div>
                </div>
              </div>
              <button class="ft-ib" @click="om.visible = false">
                <i class="ti ti-x"></i>
              </button>
            </div>
            <div class="om-selbar">
              <div class="om-tags">
                <span v-for="code in om.selected" :key="code" class="om-tag"
                  :style="`--ot:${outilColor(code)}`">
                  {{ code }}
                  <button @click="omToggle(code)">×</button>
                </span>
              </div>
              <button v-if="om.selected.length" class="om-clear"
                @click="om.selected = []">Effacer</button>
            </div>
            <div class="om-body">
              <div class="om-grid">
                <button v-for="outil in props.outilsIfaci" :key="outil.code"
                  class="om-card"
                  :class="om.selected.includes(outil.code) ? 'om-card--sel' : ''"
                  @click="omToggle(outil.code)">
                  <div class="om-card__num" :style="`background:${outil.color}`">
                    {{ outil.code }}
                  </div>
                  <div class="om-card__body">
                    <span class="om-card__lbl">{{ outil.label }}</span>
                  </div>
                </button>
              </div>
            </div>
            <div class="om-ft">
              <button class="ft-btn ft-btn--ghost" @click="om.visible = false">Annuler</button>
              <button class="om-confirm" :disabled="!om.selected.length" @click="omConfirmer">
                Ouvrir
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ══ MODAL FRAP DÉTAIL / ÉDITION ══════════════════════════ -->
    <Teleport to="body">
      <Transition name="om-fade">
        <div v-if="fociModal.visible" class="om-overlay"
          @click.self="fociModal.visible = false">
          <div class="fm-dialog">
            <div class="fm-hd">
              <div class="fm-hd__left">
                <div class="fm-icon"><i class="ti ti-clipboard-text"></i></div>
                <div>
                  <h2 class="fm-title">
                    {{ fociModal.frap?.num_frap || 'FRAP' }}
                  </h2>
                  <div class="fm-sub">
                    {{ fociModal.frap?.rubrique }}
                    <span v-if="fociModal.frap?.sous_rubrique">
                      · {{ fociModal.frap.sous_rubrique }}
                    </span>
                  </div>
                </div>
              </div>
              <div class="fm-hd__right">
                <span class="foci-niv-badge"
                  v-if="fociModal.frap?.niveau_controle_interne"
                  :style="fociNiveauStyle(fociModal.frap.niveau_controle_interne)">
                  {{ fociNiveauLabel(fociModal.frap.niveau_controle_interne) }}
                </span>
                <button class="ft-ib" @click="fociModal.visible = false">
                  <i class="ti ti-x"></i>
                </button>
              </div>
            </div>
            <div class="fm-body" v-if="fociModal.frap">
              <div class="fm-grid">
                <div class="fm-field fm-field--full">
                  <label class="fm-lbl">Objectif de contrôle</label>
                  <div class="fm-val fm-val--obj">
                    {{ fociModal.frap.objectif_controle || '—' }}
                  </div>
                </div>
                <div class="fm-field fm-field--full">
                  <label class="fm-lbl">Fait / Constat</label>
                  <div class="fm-val">{{ fociModal.frap.fait_constats || '—' }}</div>
                </div>
                <div class="fm-field">
                  <label class="fm-lbl">Problème</label>
                  <div class="fm-val">{{ fociModal.frap.probleme || '—' }}</div>
                </div>
                <div class="fm-field">
                  <label class="fm-lbl">Causes</label>
                  <div class="fm-val">{{ fociModal.frap.causes || '—' }}</div>
                </div>
                <div class="fm-field">
                  <label class="fm-lbl">Impacts</label>
                  <div class="fm-val">{{ fociModal.frap.impacts || '—' }}</div>
                </div>
                <div class="fm-field">
                  <label class="fm-lbl">Recommandation</label>
                  <div class="fm-val fm-val--reco">
                    {{ fociModal.frap.recommandation || '—' }}
                  </div>
                </div>
                <div class="fm-field">
                  <label class="fm-lbl">Commentaires de l'audité</label>
                  <div class="fm-val">
                    {{ fociModal.frap.commentaires_audite || '—' }}
                  </div>
                </div>
                <div class="fm-field fm-field--full"
                  v-if="fociModal.frap.points_forts">
                  <label class="fm-lbl fm-lbl--green">Points forts</label>
                  <div class="fm-val fm-val--green">
                    {{ fociModal.frap.points_forts }}
                  </div>
                </div>
                <div class="fm-field">
                  <label class="fm-lbl">Date d'échéance</label>
                  <div class="fm-val">
                    {{ formatDate(fociModal.frap.date_echeance) || '—' }}
                  </div>
                </div>
                <div class="fm-field">
                  <label class="fm-lbl">Responsable</label>
                  <div class="fm-val">
                    {{ fociModal.frap.personne_responsable || '—' }}
                  </div>
                </div>
                <div class="fm-field">
                  <label class="fm-lbl">Livrable</label>
                  <div class="fm-val">{{ fociModal.frap.livrable || '—' }}</div>
                </div>
                <div class="fm-field">
                  <label class="fm-lbl">Statut</label>
                  <div class="fm-val">
                    <span class="fi-or-card__st"
                      :class="'fist--' + (fociModal.frap.statut || 'draft')">
                      {{ stLbl(fociModal.frap.statut || 'draft') }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
            <div class="fm-ft">
              <button class="ft-btn ft-btn--ghost"
                @click="fociModal.visible = false">Fermer</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ══ TOAST ══════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="toast-pop">
        <div v-if="toast.show" class="ft-toast" :class="`ft-toast--${toast.type}`">
          <i :class="toast.type === 'success'
            ? 'ti ti-circle-check' : 'ti ti-alert-circle'"></i>
          {{ toast.msg }}
          <button class="ft-toast__x" @click="toast.show = false">
            <i class="ti ti-x"></i>
          </button>
        </div>
      </Transition>
    </Teleport>
  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'

// ─── PROPS ──────────────────────────────────────────────────────
const props = withDefaults(defineProps<{
  mission?: any
  auditorRole?: string
  auditeurNom?: string
  missionId?: number
  assignmentId?: number
  form?: any
  phaseAuditeurs?: any[]
  programmeData?: {
    found: boolean; programme_code?: string; programme_label?: string
    programme_status?: string; objectifs?: any[]
    total_objectifs?: number; total_tests?: number
  }
  outilsIfaci?: { code: string; label: string; icon: string; color: string }[]
  processus?: any[]
  risquesMission?: any[]
  rciLignes?: any[]
  missionContext?: {
    mission_id?: number; assignment_id?: number
    mission_libelle?: string; code_mission?: string
  }
  backUrl?: string
  urlStore?: string
  urlUpdate?: string
  urlSoumettre?: string
  urlValider?: string
  urlSaveOutil?: string
  urlLoadOutil?: string
  urlAutoSave?: string
  urlIaGlobal?: string
  urlGenererSynthese?: string
  urlFrapStore?: string
  urlFrapUpdateBase?: string
  outilsParTest?: Record<string, any[]>
  iaGlobal?: any
  fraps?: any[]
}>(), {
  phaseAuditeurs: () => [],
  outilsIfaci: () => [],
  processus: () => [],
  risquesMission: () => [],
  rciLignes: () => [],
  missionContext: () => ({}),
  programmeData: () => ({
    found: false, objectifs: [], total_objectifs: 0, total_tests: 0
  }),
  outilsParTest: () => ({}),
  iaGlobal: null,
  fraps: () => [],
})

// ─── LISTES STATIQUES ───────────────────────────────────────────
const causesList = [
  { value: 'absence_procedure',       label: 'Absence de procédure' },
  { value: 'procedure_non_appliquee', label: 'Procédure non appliquée' },
  { value: 'manque_supervision',      label: 'Manque de supervision' },
  { value: 'manque_ressources',       label: 'Manque de ressources' },
  { value: 'defaillance_systeme',     label: 'Défaillance système' },
  { value: 'erreur_humaine',          label: 'Erreur humaine' },
  { value: 'formation_insuffisante',  label: 'Formation insuffisante' },
  { value: 'fraude_intentionnel',     label: 'Fraude / intentionnel' },
]
const risksList = [
  { key: 'financier',     label: 'Financier',                 color: '#dc2626' },
  { key: 'operationnel',  label: 'Opérationnel',              color: '#d97706' },
  { key: 'juridique',     label: 'Juridique / réglementaire', color: '#7c3aed' },
  { key: 'reputationnel', label: 'Réputationnel',             color: '#0c4a6e' },
  { key: 'fraude',        label: 'Fraude',                    color: '#b91c1c' },
  { key: 'qualite_info',  label: "Qualité de l'information",  color: '#15803d' },
  { key: 'continuite',    label: "Continuité d'activité",     color: '#6b21a8' },
]

// ─── STATE PRINCIPAL ────────────────────────────────────────────
const form = reactive<any>({
  id: null, code: '', validation_status: 'draft', validation_note: '',
  ...(props.form ?? {}),
})
const dynUrls = reactive({
  update:    props.urlUpdate    ?? null,
  soumettre: props.urlSoumettre ?? null,
  valider:   props.urlValider   ?? null,
  autoSave:  props.urlAutoSave  ?? null,
  iaGlobal:  props.urlIaGlobal  ?? null,
})
const processing = ref(false)
const toast = ref({ show: false, type: 'success', msg: '' })
let _tt: ReturnType<typeof setTimeout> | null = null

// ── Auto-save ──────────────────────────────────────────────────
const autoSaving   = ref(false)
const lastAutoSave = ref<string>('')
let _autoSaveTimer: ReturnType<typeof setTimeout> | null = null
const AUTO_SAVE_DELAY = 3000

// ── IA Globale ─────────────────────────────────────────────────
const iaGlobal     = ref<any>(props.iaGlobal ?? null)
const iaProcessing = ref(false)

// ── Navigation vues ────────────────────────────────────────────
const ficheActif    = ref<any>(null)
const obsVueActif   = ref(false)
const obsVueContext = ref<any>(null)
const obsSaving     = ref(false)
const obsLoadingBD  = ref(false)
const obsSavedOnce  = ref(false)

// ── FRAPs locales (synchronisées avec le serveur) ─────────────
const frapsLocal = ref<any[]>(
  props.fraps ? JSON.parse(JSON.stringify(props.fraps)) : []
)

// ── FRAPs liées à l'observation en cours ──────────────────────
// Stocke les FRAPs créées/mises à jour lors de la sauvegarde d'une obs XIV
const obsFrapsLiees = ref<any[]>([])

// ── FOCI State ─────────────────────────────────────────────────
const fociVueActif   = ref(false)
const fociLoading    = ref(false)
const fociLastGen    = ref<string>('')
const fociEditingMap = reactive<Record<string, boolean>>({})
const fociEditData   = reactive<Record<string, string>>({})
const fociModal      = reactive<{ visible: boolean; frap: any | null }>({
  visible: false, frap: null
})

// ─── DONNÉES OBSERVATION XIV ────────────────────────────────────
/**
 * obsFormFrap : champs qui alimentent directement la FRAP
 * Ces données sont sauvegardées dans fiche_observation_frap via storeFrap/updateFrap
 */
const obsFormFrap = reactive<any>({
  // Identification FRAP
  rubrique: '',
  sous_rubrique: '',
  objectif_controle: '',
  intitule_probleme: '',
  // Colonnes FRAP principales
  fait_constats: '',
  probleme: '',
  causes: '',
  impacts: '',
  recommandation: '',
  commentaires_audite: '',
  points_forts: '',
  niveau_controle_interne: '',
  date_echeance: '',
  personne_responsable: '',
  livrable: '',
  // Checkboxes (fusionnées dans les champs texte à la sauvegarde)
  causes_selection: [] as string[],
  consequences_selection: [] as string[],
  critere_referentiel: '',
})

/**
 * obsData : champs pour l'outil XIV (saveOutil)
 * Ces données vont dans la table outil_observation via saveOutil
 */
const obsData = reactive<any>({
  date_observation: '',
  heure_debut: '',
  heure_fin: '',
  auditeur: props.auditeurNom ?? '',
  localisation: '',
  interlocuteurs_presents: '',
  objectif_audit: '',
  tache_local_observer: '',
  elements_verifier: '',
  pieces_attendues: '',
  points_forts: '',
  conclusion: '',
  constats: [] as any[],
  niveau_controle: '',
  niveau_synthese: '',
  recommandations: [] as any[],
})

// ── frap_id lié à l'obs en cours (pour update vs create) ─────
const obsFrapId = ref<number | null>(null)

// ─── RÉSULTATS & OUTILS ──────────────────────────────────────
const resultatsMap   = reactive<Record<string, {
  resultat: string; constat: string; preuve: string
}>>({})
const outilsProcsMap = reactive<Record<string, string[]>>({})

// Initialisation depuis props
if (props.form?.resultats) {
  const arr = Array.isArray(props.form.resultats)
    ? props.form.resultats
    : (() => { try { return JSON.parse(props.form.resultats) } catch { return [] } })()
  arr.forEach((r: any) => {
    resultatsMap[r.obj_num + '::' + r.test_ref] = {
      resultat: r.resultat ?? '',
      constat: r.constat ?? '',
      preuve: r.preuve ?? '',
    }
  })
}
if (props.outilsParTest) {
  Object.entries(props.outilsParTest).forEach(([testKey, outils]) => {
    ;(outils as any[]).forEach((ot: any) => {
      const parts   = testKey.split('::')
      const objNum  = parts[0]
      const testRef = parts.slice(1).join('::')
      const mapKey  = pKey(objNum, testRef, ot.proc_idx ?? 0)
      if (!outilsProcsMap[mapKey]) outilsProcsMap[mapKey] = []
      if (!outilsProcsMap[mapKey].includes(ot.outil_code))
        outilsProcsMap[mapKey].push(ot.outil_code)
    })
  })
}

// ─── COMPUTED ──────────────────────────────────────────────────
const canManage = computed(() =>
  ['DM', 'CM'].includes(props.auditorRole ?? '')
)
const isLocked = computed(() =>
  form.validation_status === 'validated'
  || (form.validation_status === 'in_review' && !canManage.value)
)
const missionLibelle = computed(() =>
  props.mission?.libelle ?? props.missionContext?.mission_libelle ?? ''
)
const obsXIVLiee = computed(() =>
  ficheActif.value
    ? getOutilsPourTest(ficheActif.value.objNum, ficheActif.value.testRef)
        .some((o: any) => o.outil_code === 'XIV')
    : false
)

const fociGrouped = computed(() => {
  const fraps = frapsLocal.value
  if (!fraps.length) return []
  const byObj: Record<string, Record<string, Record<string, any[]>>> = {}
  fraps.forEach(frap => {
    const obj  = frap.objectif_controle || 'Protection du patrimoine'
    const rub  = frap.rubrique          || 'Sans rubrique'
    const srub = frap.sous_rubrique     || ''
    if (!byObj[obj])           byObj[obj] = {}
    if (!byObj[obj][rub])      byObj[obj][rub] = {}
    if (!byObj[obj][rub][srub]) byObj[obj][rub][srub] = []
    byObj[obj][rub][srub].push(frap)
  })
  return Object.entries(byObj).map(([obj, rubriques]) => ({
    objectif_controle: obj,
    rubriques: Object.entries(rubriques).map(([rub, ssrubs]) => ({
      rubrique: rub,
      sous_rubriques: Object.entries(ssrubs).map(([srub, fps]) => ({
        sous_rubrique: srub,
        fraps: fps,
      }))
    }))
  }))
})

const fociTotalRubriques = computed(() => {
  const set = new Set(frapsLocal.value.map(f => f.rubrique || ''))
  return set.size
})

// ─── HELPERS ───────────────────────────────────────────────────
function vstLbl(s: string) {
  return ({ draft: 'Brouillon', in_review: 'En attente', validated: 'Validé ✓' } as any)[s] ?? s
}
function stLbl(s: string) {
  return ({
    draft: 'Brouillon', in_review: 'En révision',
    validated: 'Validé', rejected: 'Rejeté'
  } as any)[s] ?? s
}
function resultatLabel(v: string) {
  return ({
    conforme: '✅ Conforme', ecart: '⚠️ Écart',
    nc: '❌ Non conforme', na: 'N/A'
  } as any)[v] ?? v
}
function tRef(test: any, oi: number, ti: number) {
  return test.ref || ('T' + (oi + 1) + '.' + (ti + 1))
}
function pKey(on: string, tr: string, pi: number) { return on + '::' + tr + '::' + pi }
function rk(on: string, tr: string)               { return on + '::' + tr }
function getOutilsForProc(on: string, tr: string, pi: number): string[] {
  return outilsProcsMap[pKey(on, tr, pi)] ?? []
}
function testHasAnyOutil(on: string, tr: string): boolean {
  return Object.keys(outilsProcsMap)
    .some(k => k.startsWith(on + '::' + tr + '::')
      && (outilsProcsMap[k] ?? []).length > 0)
}
function testHasOutil(on: string, tr: string, code: string): boolean {
  return Object.entries(outilsProcsMap)
    .some(([k, v]) => k.startsWith(on + '::' + tr + '::') && v.includes(code))
}
function outilColor(code: string) {
  return props.outilsIfaci?.find(o => o.code === code)?.color ?? '#374151'
}
function getOutilsPourTest(objNum: string, testRef: string): any[] {
  return props.outilsParTest?.[objNum + '::' + testRef] ?? []
}
function getObjectifTexte(): string {
  return ficheActif.value?.obj?.objectif ?? ficheActif.value?.obj?.libelle ?? ''
}
function formatDate(d?: string | null) {
  return d ? new Date(d).toLocaleDateString('fr-FR') : ''
}
function csrf() {
  return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)
    ?.content ?? ''
}

/** Récupère les FRAPs liées à un test précis */
function getFrapsForTest(objNum: string, testRef: string): any[] {
  return frapsLocal.value.filter(f =>
    f.test_ref === testRef || f.obj_num === objNum
  )
}

// ─── RÉSULTATS ─────────────────────────────────────────────────
function getResultat(on: string, tr: string) {
  return resultatsMap[rk(on, tr)]?.resultat ?? ''
}
function getConstat(on: string, tr: string) {
  return resultatsMap[rk(on, tr)]?.constat ?? ''
}
function getPreuve(on: string, tr: string) {
  return resultatsMap[rk(on, tr)]?.preuve ?? ''
}

function setResultat(on: string, tr: string, v: string) {
  if (!resultatsMap[rk(on, tr)])
    resultatsMap[rk(on, tr)] = { resultat: '', constat: '', preuve: '' }
  resultatsMap[rk(on, tr)].resultat = v
}
function setConstat(on: string, tr: string, v: string) {
  if (!resultatsMap[rk(on, tr)])
    resultatsMap[rk(on, tr)] = { resultat: '', constat: '', preuve: '' }
  resultatsMap[rk(on, tr)].constat = v
}
function setPreuve(on: string, tr: string, v: string) {
  if (!resultatsMap[rk(on, tr)])
    resultatsMap[rk(on, tr)] = { resultat: '', constat: '', preuve: '' }
  resultatsMap[rk(on, tr)].preuve = v
}

function onResultatChange(on: string, tr: string, v: string) {
  setResultat(on, tr, v)
  scheduleAutoSave()
}
function onConstatInput(on: string, tr: string, v: string) {
  setConstat(on, tr, v)
  scheduleAutoSave()
}
function onPreuveInput(on: string, tr: string, v: string) {
  setPreuve(on, tr, v)
  scheduleAutoSave()
}

// ─── AUTO-SAVE ─────────────────────────────────────────────────
function scheduleAutoSave() {
  if (isLocked.value || !form.id) return
  if (_autoSaveTimer) clearTimeout(_autoSaveTimer)
  _autoSaveTimer = setTimeout(() => { doAutoSave() }, AUTO_SAVE_DELAY)
}
async function doAutoSave() {
  const url = dynUrls.autoSave ?? props.urlAutoSave
  if (!url || !form.id || isLocked.value) return
  autoSaving.value = true
  try {
    const payload = {
      mission_id:    props.missionId ?? props.missionContext?.mission_id,
      assignment_id: props.assignmentId ?? props.missionContext?.assignment_id,
      resultats:     JSON.stringify(serializeResultats()),
    }
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf(),
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload),
    })
    if (res.ok)
      lastAutoSave.value = new Date().toLocaleTimeString('fr-FR', {
        hour: '2-digit', minute: '2-digit'
      })
  } catch { /* silencieux */ }
  finally { autoSaving.value = false }
}

// ─── IA GLOBALE ─────────────────────────────────────────────────
function iaScoreColor(score: number | null): string {
  if (score === null) return '#94a3b8'
  if (score >= 8) return '#10b981'
  if (score >= 5) return '#f59e0b'
  return '#ef4444'
}

async function lancerIaGlobale() {
  const url = dynUrls.iaGlobal ?? props.urlIaGlobal
  if (!url) { showToast('error', "URL d'analyse IA non disponible"); return }
  if (!form.id) { showToast('error', "Veuillez d'abord enregistrer la fiche."); return }
  iaProcessing.value = true
  try {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf(),
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        mission_id:    props.missionId ?? props.missionContext?.mission_id,
        assignment_id: props.assignmentId ?? props.missionContext?.assignment_id,
      }),
    })
    const data = await res.json()
    if (data.success) {
      iaGlobal.value = { ...data.ia_global, generated_at: new Date().toISOString() }
      showToast('success', 'Synthèse IA générée avec succès')
    } else {
      showToast('error', data.error || 'Erreur lors de la génération')
    }
  } catch (e: any) {
    showToast('error', e.message || 'Erreur réseau')
  } finally {
    iaProcessing.value = false
  }
}

// ─── NIVEAUX ────────────────────────────────────────────────────
const niveauCtrlColors: Record<string, any> = {
  '1_faible':       { bg: '#fee2e2', color: '#dc2626', border: '#fca5a5' },
  '2_moyen':        { bg: '#fef3c7', color: '#b45309', border: '#fcd34d' },
  '3_satisfaisant': { bg: '#fefce8', color: '#854d0e', border: '#fde68a' },
  '4_bon':          { bg: '#dcfce7', color: '#166534', border: '#86efac' },
  '5_excellent':    { bg: '#d1fae5', color: '#065f46', border: '#6ee7b7' },
  // Alias niveaux synthèse
  'satisfaisant':   { bg: '#d1fae5', color: '#065f46', border: '#6ee7b7' },
  'a_ameliorer':    { bg: '#fef3c7', color: '#92400e', border: '#fcd34d' },
  'insuffisant':    { bg: '#fee2e2', color: '#dc2626', border: '#fca5a5' },
  'critique':       { bg: '#fce7f3', color: '#9d174d', border: '#f9a8d4' },
  'conforme':       { bg: '#d1fae5', color: '#065f46', border: '#6ee7b7' },
}
const niveauSynthColors: Record<string, any> = {
  'conforme':    { bg: '#d1fae5', color: '#065f46', border: '#6ee7b7' },
  'a_ameliorer': { bg: '#fef3c7', color: '#92400e', border: '#fcd34d' },
  'insuffisant': { bg: '#fee2e2', color: '#dc2626', border: '#fca5a5' },
  'critique':    { bg: '#fce7f3', color: '#9d174d', border: '#f9a8d4' },
}
const niveauLabels: Record<string, string> = {
  '1_faible': '1 – Faible', '2_moyen': '2 – Moyen',
  '3_satisfaisant': '3 – Satisfaisant', '4_bon': '4 – Bon',
  '5_excellent': '5 – Excellent',
}
const niveauSyntheseLabelsMap: Record<string, string> = {
  'conforme': '✅ Conforme', 'a_ameliorer': '🔶 À améliorer',
  'insuffisant': '🔴 Insuffisant', 'critique': '⛔ Critique',
}
function niveauLabel(v: string) { return niveauLabels[v] ?? v }
function niveauSyntheseLabel(v: string) { return niveauSyntheseLabelsMap[v] ?? v }
function niveauControleStyle(v: string) {
  const c = niveauCtrlColors[v]
  return c ? { background: c.bg, color: c.color, borderColor: c.border, fontWeight: '600' } : {}
}
function niveauBadgeStyle(v: string) {
  const c = niveauCtrlColors[v]
  return c ? { background: c.bg, color: c.color, border: `1px solid ${c.border}` } : {}
}
function niveauSyntheseStyle(v: string) {
  const c = niveauSynthColors[v]
  return c ? { background: c.bg, color: c.color, borderColor: c.border, fontWeight: '600' } : {}
}
function niveauSyntheseBadgeStyle(v: string) {
  const c = niveauSynthColors[v]
  return c ? { background: c.bg, color: c.color, border: `1px solid ${c.border}` } : {}
}

// ─── NAVIGATION ─────────────────────────────────────────────────
function ouvrirFiche(obj: any, test: any, oi: number, ti: number) {
  ficheActif.value = {
    obj, test, objNum: obj.num, testRef: tRef(test, oi, ti), oi, ti,
    date: new Date().toISOString().slice(0, 10),
  }
  obsVueActif.value  = false
  fociVueActif.value = false
}

function _resetObsData() {
  Object.assign(obsFormFrap, {
    rubrique: '', sous_rubrique: '', objectif_controle: '',
    intitule_probleme: '', fait_constats: '', probleme: '',
    causes: '', impacts: '', recommandation: '',
    commentaires_audite: '', points_forts: '',
    niveau_controle_interne: '', date_echeance: '',
    personne_responsable: '', livrable: '',
    causes_selection: [], consequences_selection: [], critere_referentiel: '',
  })
  Object.assign(obsData, {
    date_observation: '', heure_debut: '', heure_fin: '',
    auditeur: props.auditeurNom ?? '', localisation: '',
    interlocuteurs_presents: '', objectif_audit: '',
    tache_local_observer: '', elements_verifier: '', pieces_attendues: '',
    points_forts: '', conclusion: '', constats: [],
    niveau_controle: '', niveau_synthese: '', recommandations: [],
  })
  obsFrapId.value     = null
  obsFrapsLiees.value = []
  obsSavedOnce.value  = false
}

function ouvrirObsDirecte(obj: any, test: any, oi: number, ti: number) {
  const testRef = tRef(test, oi, ti)
  obsVueContext.value = { obj, test, objNum: obj.num, testRef, oi, ti, fromFiche: false }
  _resetObsData()
  obsData.objectif_audit       = obj.objectif ?? obj.libelle ?? ''
  obsData.tache_local_observer = test.libelle ?? ''
  obsFormFrap.objectif_controle = obj.objectif ?? ''
  obsFormFrap.intitule_probleme = test.libelle ?? ''
  ficheActif.value   = null
  fociVueActif.value = false
  obsVueActif.value  = true
  // Charger données existantes si XIV déjà utilisé sur ce test
  if (testHasOutil(obj.num, testRef, 'XIV') && props.urlLoadOutil && form.id) {
    _chargerObsDataComplete(obj.num, testRef)
  }
}

function ouvrirObsDepuisFiche() {
  if (!ficheActif.value) return
  const { obj, test, objNum, testRef, oi, ti } = ficheActif.value
  obsVueContext.value = { obj, test, objNum, testRef, oi, ti, fromFiche: true }
  _resetObsData()
  obsData.objectif_audit        = getObjectifTexte()
  obsFormFrap.objectif_controle = getObjectifTexte()
  obsFormFrap.intitule_probleme = test?.libelle ?? ''
  obsData.tache_local_observer  = test?.libelle ?? ''
  obsData.auditeur              = props.auditeurNom ?? ''
  if (testHasOutil(objNum, testRef, 'XIV') && props.urlLoadOutil && form.id) {
    _chargerObsDataComplete(objNum, testRef)
  }
  obsVueActif.value = true
}

function fermerObsVue() {
  const ctx       = obsVueContext.value
  const fromFiche = ctx?.fromFiche
  obsVueActif.value = false
  if (fromFiche && ctx) {
    setTimeout(() => ouvrirFiche(ctx.obj, ctx.test, ctx.oi, ctx.ti), 30)
  }
  obsVueContext.value = null
}

function ouvrirOutilDepuisFiche(code: string, procIdx: number) {
  if (!ficheActif.value) return
  if (code === 'XIV') { ouvrirObsDepuisFiche(); return }
  ouvrirOutil(
    ficheActif.value.obj, ficheActif.value.test,
    code, procIdx, ficheActif.value.oi, ficheActif.value.ti
  )
}

// ─── CHARGEMENT OBSERVATION EXISTANTE ───────────────────────────
/**
 * Charge les données d'un outil XIV ET les FRAPs liées au test
 * pour hydrater le formulaire d'observation complet
 */
async function _chargerObsDataComplete(objNum: string, testRef: string) {
  if (!props.urlLoadOutil || !form.id) return
  obsLoadingBD.value = true
  try {
    // 1. Charger l'outil XIV (outil_observation)
    const params = new URLSearchParams({
      outil_code: 'XIV', procedure_code: testRef,
      test_ref: testRef, obj_num: objNum, proc_idx: '0'
    })
    const resOutil = await fetch(
      props.urlLoadOutil + '?' + params.toString(),
      { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() } }
    )
    const dOutil = await resOutil.json()
    if (dOutil.success && dOutil.found && dOutil.record) {
      const r = dOutil.record
      const ch = dOutil.children ?? {}
      // Hydrater obsData (outil_observation)
      Object.assign(obsData, {
        date_observation:       r.date_observation        ?? '',
        heure_debut:            r.heure_debut             ?? '',
        heure_fin:              r.heure_fin               ?? '',
        auditeur:               r.auditeur                ?? props.auditeurNom ?? '',
        localisation:           r.localisation            ?? '',
        interlocuteurs_presents:r.interlocuteurs_presents ?? '',
        objectif_audit:         r.objectif_audit          ?? '',
        tache_local_observer:   r.tache_local_observer    ?? '',
        elements_verifier:      r.elements_verifier       ?? '',
        pieces_attendues:       r.pieces_attendues        ?? '',
        points_forts:           r.points_forts            ?? '',
        conclusion:             r.conclusion              ?? '',
        constats:               ch.outil_observation_constats        ?? [],
        recommandations:        ch.outil_observation_recommandations ?? [],
        niveau_controle:        r.niveau_controle  ?? r.niveau_maitrise ?? '',
        niveau_synthese:        r.niveau_synthese  ?? '',
      })
    }

    // 2. Charger les FRAPs existantes liées à ce test
    const frapsExistantes = frapsLocal.value.filter(f =>
      f.test_ref === testRef || f.obj_num === objNum
    )
    if (frapsExistantes.length) {
      obsFrapsLiees.value = frapsExistantes
      // Hydrater obsFormFrap avec la première FRAP trouvée
      const f = frapsExistantes[0]
      obsFrapId.value = f.id
      Object.assign(obsFormFrap, {
        rubrique:                f.rubrique                ?? '',
        sous_rubrique:           f.sous_rubrique           ?? '',
        objectif_controle:       f.objectif_controle       ?? '',
        intitule_probleme:       f.intitule_probleme       ?? '',
        fait_constats:           f.fait_constats           ?? '',
        probleme:                f.probleme                ?? '',
        causes:                  f.causes                  ?? '',
        impacts:                 f.impacts                 ?? '',
        recommandation:          f.recommandation          ?? '',
        commentaires_audite:     f.commentaires_audite     ?? '',
        points_forts:            f.points_forts            ?? '',
        niveau_controle_interne: f.niveau_controle_interne ?? '',
        date_echeance:           f.date_echeance           ?? '',
        personne_responsable:    f.personne_responsable    ?? '',
        livrable:                f.livrable                ?? '',
        causes_selection:        [],
        consequences_selection:  [],
        critere_referentiel:     f.critere_referentiel     ?? '',
      })
      obsSavedOnce.value = true
    }
  } catch (e) {
    console.warn('[FT] _chargerObsDataComplete:', e)
  } finally {
    obsLoadingBD.value = false
  }
}

// ─── SAUVEGARDE OBSERVATION → FRAP ──────────────────────────────
/**
 * Sauvegarde en deux étapes :
 * 1. saveOutil → outil_observation (XIV) pour l'outil IFACI
 * 2. storeFrap ou updateFrap → fiche_observation_frap (la FRAP elle-même)
 *
 * C'est le second enregistrement dans fiche_observation_frap
 * qui alimentera la FOCI.
 */
async function sauvegarderObservation() {
  const urlSaveOutil = props.urlSaveOutil
  const urlFrapStore = props.urlFrapStore
  const urlFrapUpdateBase = props.urlFrapUpdateBase

  if (!form.id) {
    showToast('error', "Veuillez d'abord créer la fiche de test (bouton Créer).")
    return
  }
  if (!obsVueContext.value) return

  obsSaving.value = true
  const ctx = obsVueContext.value

  try {
    // ── Étape 1 : Sauvegarder l'outil XIV (outil_observation) ──
    if (urlSaveOutil) {
      // Construire les causes textuelles
      const causesTexte = [
        ...obsFormFrap.causes_selection.map((v: string) => {
          const found = causesList.find(c => c.value === v)
          return found ? found.label : v
        }),
        obsFormFrap.causes || '',
      ].filter(Boolean).join(' ; ')

      const consTxt = [
        ...obsFormFrap.consequences_selection.map((v: string) => {
          const found = risksList.find(r => r.key === v)
          return found ? found.label : v
        }),
        obsFormFrap.impacts || '',
      ].filter(Boolean).join(' ; ')

      const payloadOutil = {
        outil_code:     'XIV',
        procedure_code: ctx.testRef,
        test_ref:       ctx.testRef,
        obj_num:        ctx.objNum,
        proc_idx:       0,
        data: {
          date_observation:        obsData.date_observation,
          heure_debut:             obsData.heure_debut,
          heure_fin:               obsData.heure_fin,
          auditeur:                obsData.auditeur,
          localisation:            obsData.localisation,
          interlocuteurs_presents: obsData.interlocuteurs_presents,
          objectif_audit:          obsData.objectif_audit,
          tache_local_observer:    obsData.tache_local_observer,
          elements_verifier:       obsData.elements_verifier,
          pieces_attendues:        obsData.pieces_attendues,
          points_forts:            obsData.points_forts || obsFormFrap.points_forts,
          conclusion:              obsData.conclusion,
          niveau_maitrise:         obsData.niveau_controle,
          niveau_controle:         obsData.niveau_controle,
          niveau_synthese:         obsData.niveau_synthese,
          intitule_probleme:       obsFormFrap.intitule_probleme,
          faits_constates:         obsFormFrap.fait_constats,
          critere_referentiel:     obsFormFrap.critere_referentiel,
          causes_json:             JSON.stringify(obsFormFrap.causes_selection),
          causes_autres:           obsFormFrap.causes,
          consequences_json:       JSON.stringify(obsFormFrap.consequences_selection),
          consequences_description: obsFormFrap.impacts,
        },
        children: {
          outil_observation_constats:        obsData.constats,
          outil_observation_recommandations: obsData.recommandations,
        },
      }

      const resOutil = await fetch(urlSaveOutil, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf(),
          'Accept': 'application/json'
        },
        body: JSON.stringify(payloadOutil),
      })
      const dOutil = await resOutil.json()
      if (dOutil.success) {
        // Marquer l'outil XIV comme utilisé sur ce test
        const pk = pKey(ctx.objNum, ctx.testRef, 0)
        if (!outilsProcsMap[pk]) outilsProcsMap[pk] = []
        if (!outilsProcsMap[pk].includes('XIV'))
          outilsProcsMap[pk].push('XIV')
      }
    }

    // ── Étape 2 : Sauvegarder / Mettre à jour la FRAP ──────────
    // Construction du payload FRAP
    // Les causes et conséquences des checkboxes sont fusionnées dans les champs texte
    const causesFinales = [
      ...obsFormFrap.causes_selection.map((v: string) => {
        const found = causesList.find(c => c.value === v)
        return found ? found.label : v
      }),
    ].filter(Boolean)
    if (obsFormFrap.causes?.trim()) causesFinales.push(obsFormFrap.causes.trim())

    const impactsFinaux = [
      ...obsFormFrap.consequences_selection.map((v: string) => {
        const found = risksList.find(r => r.key === v)
        return found ? found.label : v
      }),
    ].filter(Boolean)
    if (obsFormFrap.impacts?.trim()) impactsFinaux.push(obsFormFrap.impacts.trim())

    const payloadFrap = {
      rubrique:                obsFormFrap.rubrique                || (ctx.testRef),
      sous_rubrique:           obsFormFrap.sous_rubrique           || null,
      objectif_controle:       obsFormFrap.objectif_controle       || obsData.objectif_audit || 'Protection du patrimoine',
      test_ref:                ctx.testRef,
      obj_num:                 ctx.objNum,
      fait_constats:           obsFormFrap.fait_constats,
      probleme:                obsFormFrap.probleme,
      causes:                  causesFinales.join('\n• ') || null,
      impacts:                 impactsFinaux.join('\n• ') || null,
      recommandation:          obsFormFrap.recommandation,
      commentaires_audite:     obsFormFrap.commentaires_audite    || null,
      points_forts:            obsFormFrap.points_forts           || null,
      niveau_controle_interne: obsFormFrap.niveau_controle_interne || null,
      date_echeance:           obsFormFrap.date_echeance          || null,
      personne_responsable:    obsFormFrap.personne_responsable   || null,
      livrable:                obsFormFrap.livrable               || null,
    }

    let frapSauvee: any = null

    if (obsFrapId.value && urlFrapUpdateBase) {
      // UPDATE d'une FRAP existante
      const url = urlFrapUpdateBase.replace(':id', String(obsFrapId.value))
      const res = await fetch(url, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf(),
          'Accept': 'application/json'
        },
        body: JSON.stringify(payloadFrap),
      })
      const d = await res.json()
      if (d.success) {
        frapSauvee = d.frap
        // Mettre à jour dans la liste locale
        const idx = frapsLocal.value.findIndex(f => f.id === obsFrapId.value)
        if (idx !== -1) frapsLocal.value[idx] = { ...frapsLocal.value[idx], ...d.frap }
        showToast('success', 'Observation et FRAP mises à jour.')
      } else {
        showToast('error', d.error ?? 'Erreur lors de la mise à jour de la FRAP')
        return
      }
    } else if (urlFrapStore) {
      // CREATE d'une nouvelle FRAP
      const res = await fetch(urlFrapStore, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf(),
          'Accept': 'application/json'
        },
        body: JSON.stringify(payloadFrap),
      })
      const d = await res.json()
      if (d.success && d.frap) {
        frapSauvee = d.frap
        obsFrapId.value = d.frap.id
        frapsLocal.value.push(d.frap)
        showToast('success', `FRAP créée : ${d.frap.num_frap}. Visible dans la FOCI.`)
      } else {
        showToast('error', d.error ?? 'Erreur lors de la création de la FRAP')
        return
      }
    } else {
      showToast('error', 'URLs FRAP non configurées.')
      return
    }

    // Mettre à jour la liste des FRAPs liées à cette observation
    if (frapSauvee) {
      obsFrapsLiees.value = frapsLocal.value.filter(f =>
        f.test_ref === ctx.testRef || f.obj_num === ctx.objNum
      )
    }
    obsSavedOnce.value = true
    lastAutoSave.value = new Date().toLocaleTimeString('fr-FR', {
      hour: '2-digit', minute: '2-digit'
    })

  } catch (e: any) {
    showToast('error', `Erreur : ${e?.message ?? 'Réseau'}`)
  } finally {
    obsSaving.value = false
  }
}

// ─── FOCI ────────────────────────────────────────────────────────
function ouvrirFociVue() {
  ficheActif.value   = null
  obsVueActif.value  = false
  fociVueActif.value = true
  // Auto-générer si FRAP présentes et pas encore généré
  if (!fociLastGen.value && frapsLocal.value.length && props.urlGenererSynthese && form.id) {
    genererSyntheseFoci()
  }
}

async function genererSyntheseFoci() {
  if (!props.urlGenererSynthese || !form.id) return
  fociLoading.value = true
  try {
    const res = await fetch(props.urlGenererSynthese, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf(),
        'Accept': 'application/json'
      },
      body: JSON.stringify({ fiche_test_id: form.id }),
    })
    const d = await res.json()
    if (d.success) {
      if (d.fraps) frapsLocal.value = d.fraps
      fociLastGen.value = new Date().toLocaleTimeString('fr-FR', {
        hour: '2-digit', minute: '2-digit'
      })
      showToast('success', `Synthèse FOCI générée — ${frapsLocal.value.length} FRAP(s).`)
    } else {
      showToast('error', d.error || 'Erreur')
    }
  } catch (e: any) {
    showToast('error', e.message || 'Erreur réseau')
  } finally {
    fociLoading.value = false
  }
}

// FOCI édition inline
function fociEditMode(frapId: number, field: string): boolean {
  return !!fociEditingMap[`${frapId}_${field}`]
}
function fociStartEdit(frap: any, field: string) {
  if (isLocked.value) return
  const key = `${frap.id}_${field}`
  fociEditingMap[key] = true
  fociEditData[key]   = frap[field] || ''
}
function fociCancelEdit(frapId: number, field: string) {
  fociEditingMap[`${frapId}_${field}`] = false
}
async function fociSaveField(frap: any, field: string) {
  const key = `${frap.id}_${field}`
  fociEditingMap[key] = false
  const newVal = fociEditData[key] ?? ''
  if (newVal === (frap[field] || '')) return
  frap[field] = newVal
  await fociPersistFrap(frap)
}
async function fociSaveFieldDirect(frap: any, field: string, value: string) {
  frap[field] = value
  await fociPersistFrap(frap)
}

async function fociPersistFrap(frap: any) {
  const baseUrl = props.urlFrapUpdateBase
  if (!baseUrl) return
  const url = baseUrl.replace(':id', frap.id)
  try {
    const res = await fetch(url, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf(),
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        fait_constats:           frap.fait_constats,
        probleme:                frap.probleme,
        causes:                  frap.causes,
        impacts:                 frap.impacts,
        recommandation:          frap.recommandation,
        commentaires_audite:     frap.commentaires_audite,
        points_forts:            frap.points_forts,
        date_echeance:           frap.date_echeance,
        personne_responsable:    frap.personne_responsable,
        livrable:                frap.livrable,
        niveau_controle_interne: frap.niveau_controle_interne,
        rubrique:                frap.rubrique,
        sous_rubrique:           frap.sous_rubrique,
        objectif_controle:       frap.objectif_controle,
      }),
    })
    const d = await res.json()
    if (d.success) {
      showToast('success', 'FRAP enregistrée.')
    } else {
      showToast('error', d.error || 'Erreur')
    }
  } catch (e: any) {
    showToast('error', e.message || 'Erreur réseau')
  }
}

function fociModalOuvrir(frap: any) {
  fociModal.frap    = frap
  fociModal.visible = true
}

// Niveaux FOCI
const fociNiveauMap: Record<string, any> = {
  'satisfaisant': { label: '✅ Satisfaisant', bg: '#d1fae5', color: '#065f46', border: '#a7f3d0' },
  'a_ameliorer':  { label: '🔶 À améliorer',  bg: '#fef3c7', color: '#92400e', border: '#fcd34d' },
  'insuffisant':  { label: '🔴 Insuffisant',  bg: '#fee2e2', color: '#dc2626', border: '#fca5a5' },
  'critique':     { label: '⛔ Critique',     bg: '#fce7f3', color: '#9d174d', border: '#f9a8d4' },
  'conforme':     { label: '✅ Conforme',      bg: '#d1fae5', color: '#065f46', border: '#a7f3d0' },
}
function fociNiveauLabel(v?: string): string {
  return fociNiveauMap[v?.toLowerCase() ?? '']?.label || v || '—'
}
function fociNiveauStyle(v?: string): Record<string, string> {
  const c = fociNiveauMap[v?.toLowerCase() ?? '']
  return c
    ? { background: c.bg, color: c.color, border: `1px solid ${c.border}` }
    : { background: '#f1f5f9', color: '#475569' }
}
function fociNiveauRowClass(v?: string): string {
  const lv = v?.toLowerCase() ?? ''
  if (lv === 'critique')                       return 'foci-row--critique'
  if (lv === 'insuffisant')                    return 'foci-row--insuffisant'
  if (['a_ameliorer', 'à améliorer'].includes(lv)) return 'foci-row--ameliorer'
  if (['satisfaisant', 'conforme'].includes(lv))   return 'foci-row--ok'
  return ''
}

// ─── OUTILS ─────────────────────────────────────────────────────
function ouvrirOutil(
  obj: any, test: any, code: string, procIdx: number, oi: number, ti: number
) {
  if (code === 'XIV') { ouvrirObsDirecte(obj, test, oi, ti); return }
  const tr  = tRef(test, oi, ti)
  const pk  = pKey(obj.num, tr, procIdx)
  const segments: Record<string, string> = {
    I: 'entretien', II: 'analyse-taches', III: 'diagramme-flux',
    IV: 'approche-processus', V: 'test-cheminement',
    VI: 'hierarchisation-risques', VII: 'referentiel-audit',
    VIII: 'cause-effet', IX: 'qci', X: 'brainstorming',
    XI: 'piste-audit', XII: 'circularisation',
    XIII: 'audit-analytique', XV: 'echantillonnage',
  }
  const seg = segments[code]; if (!seg) return
  if (!outilsProcsMap[pk]) outilsProcsMap[pk] = []
  if (!outilsProcsMap[pk].includes(code)) outilsProcsMap[pk].push(code)
  const libelleProcedure = (procIdx >= 0 && (test.procedures?.length ?? 0) > procIdx)
    ? test.procedures[procIdx] : (test.libelle ?? '')
  const params = new URLSearchParams({
    fiche_test_id:  String(form.id ?? ''),
    mission_id:     String(props.missionId ?? props.missionContext?.mission_id ?? ''),
    assignment_id:  String(props.assignmentId ?? props.missionContext?.assignment_id ?? ''),
    test_ref:       tr,
    obj_num:        obj.num,
    proc_idx:       String(procIdx),
    procedure_code: props.missionContext?.code_mission ?? '',
    libelle_test:   test.libelle ?? '',
    libelle_proc:   libelleProcedure,
    objectif_audit: obj.objectif ?? obj.libelle ?? '',
    back:           window.location.href,
  })
  router.visit('/auditor/outils/' + seg + '?' + params.toString())
}

function ouvrirChoixOutil(obj: any, test: any, pi: number, oi: number, ti: number) {
  const tr = tRef(test, oi, ti)
  Object.assign(om, {
    visible: true, testRef: tr, procIdx: pi, objNum: obj.num,
    obj, test, oi, ti,
    selected: [...(getOutilsForProc(obj.num, tr, pi))],
  })
}
function omToggle(code: string) {
  const i = om.selected.indexOf(code)
  if (i === -1) om.selected.push(code)
  else om.selected.splice(i, 1)
}
function omConfirmer() {
  if (!om.selected.length) return
  const pk = pKey(om.objNum, om.testRef, om.procIdx ?? 0)
  outilsProcsMap[pk] = [...om.selected]
  om.visible = false
  ouvrirOutil(om.obj, om.test, om.selected[0], om.procIdx ?? 0, om.oi, om.ti)
}

const om = reactive({
  visible: false, selected: [] as string[],
  testRef: '', procIdx: null as number | null,
  objNum: '', obj: null as any, test: null as any, oi: 0, ti: 0,
})

// ─── SÉRIALISATION ──────────────────────────────────────────────
function serializeResultats() {
  return Object.entries(resultatsMap).map(([k, v]) => {
    const p = k.split('::')
    return { obj_num: p[0], test_ref: p.slice(1).join('::'), ...v }
  })
}

// ─── CRUD FICHE ─────────────────────────────────────────────────
async function submit(silent = false) {
  processing.value = !silent
  try {
    const url = form.id
      ? (dynUrls.update ?? props.urlUpdate)
      : props.urlStore
    if (!url) {
      if (!silent) showToast('error', 'URL indisponible.')
      return
    }
    const payload: any = {
      mission_id:    props.missionId ?? props.missionContext?.mission_id,
      assignment_id: props.assignmentId ?? props.missionContext?.assignment_id,
      resultats:     JSON.stringify(serializeResultats()),
      outils_data:   JSON.stringify([]),
    }
    if (form.id) payload._method = 'PUT'
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf(),
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload),
    })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    const d = await res.json()
    if (d.success || res.ok) {
      if (!silent) showToast('success', form.id ? 'Fiche mise à jour.' : 'Fiche créée.')
      if (d.form)
        Object.assign(form, {
          id: d.form.id, code: d.form.code,
          validation_status: d.form.validation_status
        })
      if (d.urlUpdate)    dynUrls.update    = d.urlUpdate
      if (d.urlSoumettre) dynUrls.soumettre = d.urlSoumettre
      if (d.urlValider)   dynUrls.valider   = d.urlValider
      if (d.urlAutoSave)  dynUrls.autoSave  = d.urlAutoSave
      if (d.urlIaGlobal)  dynUrls.iaGlobal  = d.urlIaGlobal
      lastAutoSave.value = new Date().toLocaleTimeString('fr-FR', {
        hour: '2-digit', minute: '2-digit'
      })
    } else {
      if (!silent) showToast('error', d.message ?? 'Erreur.')
    }
  } catch (e: any) {
    if (!silent) showToast('error', e.message ?? 'Erreur réseau.')
  } finally {
    processing.value = false
  }
}

function annuler() {
  if (props.backUrl) router.visit(props.backUrl)
}

async function soumettre() {
  processing.value = true
  try {
    const d = await (await fetch(dynUrls.soumettre ?? props.urlSoumettre ?? '', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf(),
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        mission_id:    props.missionId ?? props.missionContext?.mission_id,
        assignment_id: props.assignmentId ?? props.missionContext?.assignment_id,
      }),
    })).json()
    if (d.success) {
      form.validation_status = 'in_review'
      showToast('success', 'Soumise pour validation.')
    } else {
      showToast('error', d.error ?? 'Erreur')
    }
  } catch {
    showToast('error', 'Erreur réseau')
  }
  processing.value = false
}

async function valider(action: string, note?: string) {
  processing.value = true
  try {
    const d = await (await fetch(dynUrls.valider ?? props.urlValider ?? '', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf(),
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        mission_id:    props.missionId ?? props.missionContext?.mission_id,
        assignment_id: props.assignmentId ?? props.missionContext?.assignment_id,
        action, note,
      }),
    })).json()
    if (d.success) {
      form.validation_status = d.status
      showToast('success', action === 'validate' ? 'Fiche validée ✓' : 'Rejetée.')
    } else {
      showToast('error', d.error ?? 'Erreur')
    }
  } catch {
    showToast('error', 'Erreur réseau')
  }
  processing.value = false
}

function promptReject() {
  const n = prompt('Motif du rejet :', '')
  if (n?.trim()) valider('reject', n.trim())
}

// ─── TOAST ──────────────────────────────────────────────────────
function showToast(t: string, m: string, dur = 4500) {
  if (_tt) clearTimeout(_tt)
  toast.value = { show: true, type: t, msg: m }
  _tt = setTimeout(() => { toast.value.show = false }, dur)
}

onBeforeUnmount(() => {
  if (_tt) clearTimeout(_tt)
  if (_autoSaveTimer) clearTimeout(_autoSaveTimer)
})
</script>

<style scoped>
/* ════ VARIABLES ══════════════════════════════════════════════ */
:root {
  --navy:   #0f172a;
  --blue:   #1e40af;
  --green:  #065f46;
  --purple: #6d28d9;
  --border: #e2e8f0;
  --bg:     #f1f5f9;
  --sh:     0 1px 3px rgba(15,23,42,.07);
}
.ft-shell {
  display: flex; flex-direction: column; height: 100vh;
  background: var(--bg);
  font-family: 'Segoe UI', system-ui, sans-serif;
}

/* ── TOPBAR ───────────────────────────────────────────────── */
.ft-topbar {
  display: flex; justify-content: space-between; align-items: center;
  padding: .5rem 1rem; background: white;
  border-bottom: 1px solid var(--border); flex-shrink: 0;
}
.ft-topbar__left  { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
.ft-topbar__right { display: flex; align-items: center; gap: .3rem; flex-wrap: wrap; }
.ft-code {
  background: var(--navy); color: white; padding: .2rem .6rem;
  border-radius: 4px; font-size: .7rem; font-weight: 600;
}
.ft-sdot { width: 8px; height: 8px; border-radius: 50%; }
.sd--draft { background: #94a3b8; }
.sd--in_review { background: #2563eb; }
.sd--validated { background: #16a34a; }
.ft-vstatus { font-size: .7rem; color: #475569; }
.ft-div { width: 1px; height: 20px; background: var(--border); }
.ft-icon-muted { font-size: .8rem; color: #94a3b8; }
.ft-mission-lbl { font-size: .75rem; color: #475569; }
.ft-prog-badge {
  background: #ede9fe; color: var(--purple);
  padding: .1rem .5rem; border-radius: 4px; font-size: .65rem; font-weight: 600;
}
.ft-stat-lbl { font-size: .7rem; color: #64748b; }
.ft-chip-role,
.ft-chip-user {
  display: inline-flex; align-items: center; gap: .25rem;
  background: #f1f5f9; padding: .2rem .6rem;
  border-radius: 20px; font-size: .7rem;
}
.ft-autosave-indicator {
  display: inline-flex; align-items: center; gap: .25rem;
  font-size: .62rem; color: #94a3b8;
}
.ft-autosave-ok {
  display: inline-flex; align-items: center; gap: .2rem;
  font-size: .62rem; color: #16a34a;
}
.autosave-fade-enter-active,
.autosave-fade-leave-active { transition: opacity .3s; }
.autosave-fade-enter-from,
.autosave-fade-leave-to { opacity: 0; }

/* Bouton FOCI */
.ft-btn--foci {
  background: linear-gradient(135deg, #1e3a5f, #2c5282);
  color: white; border: none; font-weight: 600;
  display: inline-flex; align-items: center; gap: .3rem;
  padding: .35rem .85rem; border-radius: 6px; cursor: pointer; font-size: .75rem;
}
.ft-btn--foci:hover { opacity: .9; }
.ft-btn--foci-active { box-shadow: 0 0 0 2px #93c5fd; }
.ft-btn--foci-empty { background: #64748b; }
.ft-foci-count {
  background: rgba(255,255,255,.25); padding: 0 .35rem;
  border-radius: 10px; font-size: .62rem;
}

/* ── BANNERS ─────────────────────────────────────────────── */
.ft-banner {
  display: flex; align-items: center; gap: .5rem;
  padding: .3rem 1rem; font-size: .75rem; flex-shrink: 0;
}
.ft-banner--ok       { background: #d1fae5; color: #065f46; border-bottom: 1px solid #a7f3d0; }
.ft-banner--review   { background: #dbeafe; color: #1d4ed8; border-bottom: 1px solid #bfdbfe; }
.ft-banner--rejected { background: #fee2e2; color: #dc2626; border-bottom: 1px solid #fecaca; }

/* ── BLOC IA ──────────────────────────────────────────────── */
.ft-synthese-banner {
  background: #f0f9ff; border-left: 4px solid #0284c7;
  border-radius: 8px; margin: .75rem 1rem 0;
  padding: .75rem; font-size: .75rem; box-shadow: var(--sh);
}
.ft-synthese-header {
  display: flex; align-items: center; gap: .75rem; flex-wrap: wrap;
  margin-bottom: .5rem; padding-bottom: .5rem;
  border-bottom: 1px solid #bae6fd;
}
.ft-ia-score {
  color: white; padding: .2rem .6rem;
  border-radius: 20px; font-weight: 700; font-size: .7rem;
}
.ft-btn-refresh {
  background: transparent; border: 1px solid #7dd3fc;
  padding: .2rem .6rem; border-radius: 20px; cursor: pointer;
  font-size: .7rem; display: inline-flex; align-items: center; gap: .3rem;
}
.ft-btn-refresh:hover { background: #e0f2fe; }
.ft-btn-ia-close {
  background: transparent; border: 1px solid #bae6fd;
  width: 22px; height: 22px; border-radius: 50%; cursor: pointer;
  display: inline-flex; align-items: center; justify-content: center;
  margin-left: auto;
}
.ft-synthese-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
.ft-synthese-col--full { grid-column: span 2; }
.ft-stitle {
  font-weight: 700; color: #0c4a6e; text-transform: uppercase;
  font-size: .65rem; margin-bottom: .25rem;
}
.ft-synthese-list { margin: 0; padding-left: 1rem; }
.ft-synthese-list li { margin-bottom: .2rem; line-height: 1.4; }
.ft-synthese-list--reco li {
  border-left: 2px solid #0284c7; padding-left: .5rem; margin-bottom: .3rem;
}
.ft-empty-mute { color: #94a3b8; font-style: italic; }
.ft-synthese-concl { margin: 0; color: #1e293b; font-style: italic; }
.ft-synthese-footer {
  margin-top: .5rem; padding-top: .5rem;
  border-top: 1px solid #bae6fd; font-size: .65rem; color: #475569;
  display: flex; align-items: center; gap: .4rem;
}
.ft-fiabilite { padding: .1rem .4rem; border-radius: 12px; background: #e2e8f0; }
.fiab-haute    { background: #d1fae5; color: #065f46; }
.fiab-moyenne  { background: #fef3c7; color: #92400e; }
.fiab-faible   { background: #fee2e2; color: #dc2626; }

/* CTA IA */
.ft-ia-cta {
  background: white; border: 1px dashed #94a3b8; border-radius: 8px;
  margin: .75rem 1rem 0; padding: .85rem 1rem;
  display: flex; align-items: center; justify-content: space-between; gap: 1rem;
}
.ft-ia-cta__left { display: flex; align-items: center; gap: .75rem; }
.ft-ia-cta__ico  { font-size: 1.8rem; color: #64748b; }
.ft-ia-cta__title { font-weight: 700; color: #1e293b; font-size: .82rem; }
.ft-ia-cta__sub   { font-size: .7rem; color: #64748b; margin-top: .1rem; }
.ft-btn--ia {
  background: linear-gradient(135deg, #1e40af, #6d28d9);
  color: white; border: none; padding: .4rem 1rem;
  border-radius: 6px; cursor: pointer; font-size: .75rem; font-weight: 600;
  display: inline-flex; align-items: center; gap: .35rem; white-space: nowrap;
}
.ft-btn--ia:disabled { opacity: .5; cursor: not-allowed; }

/* ── CONTENU PRINCIPAL ────────────────────────────────────── */
.ft-main { flex: 1; overflow: auto; padding: 1rem; }

/* ── TESTS VIEW ──────────────────────────────────────────── */
.ft-tests-view { max-width: 1000px; margin: 0 auto; }
.ft-empty { text-align: center; padding: 3rem; }
.ft-empty__ico   { font-size: 2.5rem; color: #94a3b8; margin-bottom: 1rem; }
.ft-empty__title { font-weight: 600; color: #475569; margin-bottom: .25rem; }
.ft-empty__sub   { font-size: .75rem; color: #94a3b8; }
.ft-obj-list { display: flex; flex-direction: column; gap: 1rem; }
.ft-obj {
  background: white; border-radius: 10px;
  border: 1px solid var(--border); overflow: hidden; box-shadow: var(--sh);
}
.ft-obj__hd {
  display: flex; align-items: center; gap: .5rem;
  padding: .7rem 1rem;
  background: linear-gradient(135deg, #eff6ff, #f0fdf4);
  border-bottom: 1px solid var(--border); flex-wrap: wrap;
}
.ft-obj__num {
  background: #1e40af; color: white; padding: .15rem .5rem;
  border-radius: 4px; font-size: .7rem; font-weight: 600;
}
.ft-obj__label { font-weight: 600; color: #1e293b; flex: 1; }
.ft-tag { display: inline-flex; gap: .2rem; padding: .1rem .5rem; border-radius: 20px; font-size: .65rem; }
.ft-tag--blue { background: #dbeafe; color: #1d4ed8; }
.ft-tests { display: flex; flex-direction: column; }
.ft-test {
  padding: .7rem 1rem;
  border-bottom: 1px solid #f1f5f9; transition: background .1s;
}
.ft-test:hover { background: #fafbfc; }
.ft-test--done { border-left: 3px solid #10b981; }
.ft-test__row {
  display: flex; justify-content: space-between;
  align-items: center; gap: 1rem; flex-wrap: wrap;
}
.ft-test__info { flex: 1; min-width: 200px; }
.ft-test__lbl  { margin: 0 0 .25rem; font-size: .8rem; font-weight: 500; color: #334155; }
.ft-test__chips { display: flex; gap: .25rem; flex-wrap: wrap; }
.ft-test__acts  { display: flex; align-items: center; gap: .3rem; }
.ft-test__outils-badges { display: flex; gap: .25rem; flex-wrap: wrap; margin-top: .35rem; }
.ft-outil-badge {
  display: inline-flex; align-items: center; gap: .2rem;
  padding: .15rem .5rem;
  background: color-mix(in srgb, var(--oc, #374151) 12%, white);
  border: 1px solid color-mix(in srgb, var(--oc, #374151) 30%, white);
  color: var(--oc, #374151); border-radius: 20px;
  font-size: .62rem; font-weight: 700; cursor: pointer; transition: all .15s;
}
.ft-outil-badge:hover {
  background: var(--oc, #374151); color: white; transform: translateY(-1px);
}
.ft-outil-badge__score {
  background: rgba(255,255,255,.3); border-radius: 10px;
  padding: 0 4px; font-size: .58rem;
}
.ft-ref {
  background: #ede9fe; color: var(--purple);
  padding: .2rem .5rem; border-radius: 4px;
  font-size: .65rem; font-weight: 600; white-space: nowrap;
}
.ft-chip {
  display: inline-flex; align-items: center; gap: .2rem;
  padding: .1rem .4rem; border-radius: 20px; font-size: .6rem;
}
.ft-chip--blue   { background: #dbeafe; color: #1d4ed8; }
.ft-chip--green  { background: #d1fae5; color: #065f46; }
.ft-chip--purple { background: #ede9fe; color: #6d28d9; }
.ft-sel-result {
  border: 1px solid #cbd5e1; border-radius: 6px;
  padding: .2rem .4rem; font-size: .7rem;
}
.ft-result-pill {
  display: inline-block; padding: .15rem .5rem;
  border-radius: 20px; font-size: .7rem; font-weight: 600;
}
.frp--conforme { background: #d1fae5; color: #065f46; }
.frp--ecart    { background: #fef3c7; color: #92400e; }
.frp--nc       { background: #fee2e2; color: #dc2626; }
.frp--na       { background: #f1f5f9; color: #475569; }
.ft-procs {
  background: #f8fafc; border-top: 1px solid #f1f5f9;
  padding: .5rem 1rem; display: flex; flex-direction: column; gap: .3rem;
}
.ft-proc {
  display: flex; align-items: center; gap: .5rem;
  padding: .3rem .5rem; border-radius: 6px; border: 1px solid transparent;
}
.ft-proc--linked { background: #f0fdf4; border-color: #bbf7d0; }
.ft-proc__n   { font-size: .6rem; font-weight: 700; color: #94a3b8; min-width: 18px; }
.ft-proc__txt { font-size: .7rem; color: #334155; flex: 1; }
.ft-proc__acts { display: flex; align-items: center; gap: .3rem; flex-wrap: wrap; }
.ft-outil-tag {
  display: inline-flex; align-items: center; gap: .2rem;
  padding: .1rem .5rem; background: var(--ot, #374151); color: white;
  border-radius: 20px; font-size: .6rem; font-weight: 600;
  cursor: pointer; border: none;
}
.ft-btn-obs-mini {
  position: relative;
  display: inline-flex; align-items: center; justify-content: center;
  width: 28px; height: 28px;
  background: #faf5ff; border: 1px solid #ddd6fe;
  color: #7c3aed; border-radius: 6px;
  cursor: pointer; transition: all .15s; flex-shrink: 0;
}
.ft-btn-obs-mini:hover {
  background: #ede9fe; border-color: #c4b5fd; transform: translateY(-1px);
}
.ft-btn-obs-mini--done { background: #ede9fe; border-color: #a78bfa; color: #6d28d9; }
.ft-btn-obs-mini__dot {
  position: absolute; top: 3px; right: 3px;
  width: 6px; height: 6px; background: #10b981; border-radius: 50%;
}

/* ── BOUTONS GLOBAUX ─────────────────────────────────────── */
.ft-btn {
  display: inline-flex; align-items: center; gap: .3rem;
  padding: .35rem .8rem; border-radius: 6px;
  font-size: .75rem; font-weight: 500;
  border: 1px solid transparent; cursor: pointer; transition: all .2s;
}
.ft-btn:disabled { opacity: .5; cursor: not-allowed; }
.ft-btn--ghost         { background: transparent; border-color: #cbd5e1; }
.ft-btn--ghost:hover   { background: #f1f5f9; }
.ft-btn--save          { background: var(--navy); color: white; }
.ft-btn--save:hover    { background: #1e293b; }
.ft-btn--submit        { background: #2563eb; color: white; }
.ft-btn--validate      { background: #10b981; color: white; }
.ft-btn--reject        { background: #dc2626; color: white; }
.ft-btn--fiche         { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
.ft-btn--fiche-done    { background: #d1fae5; border-color: #a7f3d0; color: #065f46; }
.ft-ib {
  display: inline-flex; align-items: center; justify-content: center;
  width: 28px; height: 28px; background: transparent;
  border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer; position: relative;
}
.ft-ib--sm { width: 25px; height: 25px; }
.ft-ib--xs { width: 22px; height: 22px; font-size: .7rem; }
.ft-ib--active { background: #d1fae5; border-color: #a7f3d0; color: #065f46; }
.ft-ib--add    { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
.ft-ib--edit   { background: #fefce8; border-color: #fde68a; color: #b45309; }
.ft-dot {
  position: absolute; top: 3px; right: 3px;
  width: 6px; height: 6px; background: #10b981; border-radius: 50%;
}

/* ── DROPDOWN ────────────────────────────────────────────── */
.ft-dd { min-width: 220px; padding: .25rem 0; }
.ft-dd__head {
  padding: .3rem .75rem; font-size: .65rem; font-weight: 700;
  color: #94a3b8; text-transform: uppercase;
  border-bottom: 1px solid #f1f5f9;
}
.ft-dd__item {
  display: flex; align-items: center; gap: .4rem;
  width: 100%; padding: .4rem .75rem;
  background: none; border: none; font-size: .75rem; cursor: pointer; color: #1e293b;
}
.ft-dd__item:hover { background: #f8fafc; }
.ft-dd__dot  { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.ft-dd__code { font-weight: 700; font-size: .7rem; min-width: 28px; }
.ft-dd__lbl  { flex: 1; }
.ft-dd__chk  { color: #10b981; }

/* ── FICHE DE TEST ────────────────────────────────────────── */
.ft-fiche-capture {
  max-width: 900px; margin: 0 auto;
  background: white; border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0,0,0,.1); overflow: hidden;
}
.ft-fiche-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: .8rem 1.2rem; background: #1e3a5f;
}
.ft-fiche-header__center { display: flex; align-items: center; gap: .8rem; }
.ft-fiche-header__right  { display: flex; align-items: center; gap: .5rem; }
.ft-fiche-icon {
  width: 36px; height: 36px;
  background: rgba(255,255,255,.15); border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.2rem;
}
.ft-fiche-title { font-size: .9rem; font-weight: 700; color: white; }
.ft-fiche-sub   { font-size: .65rem; color: rgba(255,255,255,.7); margin-top: 2px; }
.ft-btn-back {
  background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.25);
  color: white; padding: .3rem .8rem; border-radius: 6px;
  cursor: pointer; font-size: .75rem;
  display: inline-flex; align-items: center; gap: .3rem;
}
.ft-btn-back:hover { background: rgba(255,255,255,.25); }
.ft-btn-close {
  background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.2);
  color: white; width: 30px; height: 30px; border-radius: 6px;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
}
.ft-btn-save-green {
  background: #10b981; border: none; color: white;
  padding: .3rem .8rem; border-radius: 6px; cursor: pointer; font-size: .75rem;
  display: inline-flex; align-items: center; gap: .3rem;
}
.ft-btn-save-green:hover    { background: #059669; }
.ft-btn-save-green:disabled { opacity: .5; cursor: not-allowed; }
.ft-fiche-body { padding: 0; }
.ft-fiche-footer {
  display: flex; justify-content: space-between;
  padding: .8rem 1.5rem; background: #f8fafc;
  border-top: 1px solid var(--border);
}
.fi-section-title {
  background: #1e3a5f; color: white; text-align: center;
  padding: 7px 14px; font-size: 12px; font-weight: 600;
}
.fi-section-title--light { background: #2c5282; }
.fi-objectif {
  padding: 10px 16px; font-size: 13px; color: #1e293b;
  line-height: 1.65; border-bottom: 1px solid #e2e8f0;
}
.fi-auditeur-table {
  width: 100%; border-collapse: collapse;
  border-bottom: 1px solid #e2e8f0;
}
.fi-auditeur-table th,
.fi-auditeur-table td {
  padding: 8px 16px; border: 1px solid #e2e8f0;
  text-align: center; font-size: 12px;
}
.fi-auditeur-table th {
  background: #1e3a5f; color: white; font-weight: 600; width: 50%;
}
.fi-date-inp {
  border: 1px solid #cbd5e1; border-radius: 4px;
  padding: 2px 6px; font-size: 12px;
}
.fi-tests-list { border-bottom: 1px solid #e2e8f0; }
.fi-proc-block { border-top: 1px solid #e8ecf0; }
.fi-test-row {
  display: grid; grid-template-columns: 26px 1fr auto;
  align-items: center; gap: 8px; padding: 9px 16px;
}
.fi-test-num {
  display: flex; align-items: center; justify-content: center;
  width: 22px; height: 22px; background: #1e3a5f; color: white;
  border-radius: 4px; font-size: 10px; font-weight: 700; flex-shrink: 0;
}
.fi-test-lbl  { font-size: 12px; color: #1e293b; line-height: 1.5; }
.fi-proc-outils { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
.fi-outil-btn {
  display: flex; align-items: center; gap: 4px; padding: 4px 8px;
  background: color-mix(in srgb, var(--oc, #1e40af) 10%, white);
  border: 1px solid color-mix(in srgb, var(--oc, #1e40af) 40%, white);
  color: var(--oc, #1e40af); border-radius: 6px;
  font-size: 11px; font-weight: 600; cursor: pointer; white-space: nowrap;
  transition: all .15s;
}
.fi-outil-btn:hover { filter: brightness(.95); transform: translateY(-1px); }
.fi-add-outil-btn {
  display: inline-flex; align-items: center; justify-content: center;
  width: 22px; height: 22px;
  background: #eff6ff; border: 1px solid #bfdbfe;
  color: #1d4ed8; border-radius: 5px; cursor: pointer;
}
.fi-result-sel {
  border: 1px solid #cbd5e1; border-radius: 5px;
  padding: 4px 6px; font-size: 11px;
}
.fi-result-badge {
  display: inline-block; padding: 2px 7px;
  border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap;
}
.fi-rb--conforme { background: #d1fae5; color: #065f46; }
.fi-rb--ecart    { background: #fef3c7; color: #92400e; }
.fi-rb--nc       { background: #fee2e2; color: #dc2626; }
.fi-rb--na       { background: #f1f5f9; color: #475569; }
.fi-constat-row {
  display: grid; grid-template-columns: 1fr 1fr;
  gap: 10px; padding: 6px 16px 10px;
  background: #fafafa; border-top: 1px dashed #e2e8f0;
}
.fi-constat-cell,
.fi-preuve-cell { display: flex; flex-direction: column; gap: 4px; }
.fi-mini-lbl {
  font-size: 10px; font-weight: 700; color: #475569; text-transform: uppercase;
}
.fi-ta {
  width: 100%; border: 1px solid #e2e8f0; border-radius: 4px;
  padding: 5px 7px; font-size: 11px; font-family: inherit; resize: vertical;
}
.fi-inp {
  width: 100%; border: 1px solid #e2e8f0; border-radius: 4px;
  padding: 5px 7px; font-size: 11px;
}
.fi-ro {
  font-size: 11px; color: #1e293b; background: #f1f5f9;
  padding: 5px 7px; border-radius: 4px;
}
.fi-outils-resultats {
  border-top: 1px solid #e2e8f0; padding: 12px 16px; background: #f8fafc;
}
.fi-or-title {
  font-size: 11px; font-weight: 700; color: #475569;
  text-transform: uppercase; letter-spacing: .05em;
  margin-bottom: 10px; display: flex; align-items: center; gap: 6px;
}
.fi-or-list { display: flex; flex-direction: column; gap: 8px; }
.fi-or-card {
  border: 1px solid color-mix(in srgb, var(--oc, #374151) 25%, #e2e8f0);
  border-radius: 8px; overflow: hidden;
  cursor: pointer; transition: all .2s;
  background: white; border-left: 4px solid var(--oc, #374151);
}
.fi-or-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.1); }
.fi-or-card__head {
  display: flex; align-items: center; gap: 8px; padding: 8px 12px;
  background: color-mix(in srgb, var(--oc, #374151) 8%, white);
}
.fi-or-card__code {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 28px; height: 22px; color: white; border-radius: 5px;
  font-size: 10px; font-weight: 800; padding: 0 5px;
}
.fi-or-card__label { font-size: 12px; font-weight: 600; color: #1e293b; flex: 1; }
.fi-or-card__st {
  font-size: 10px; padding: 1px 6px; border-radius: 10px; font-weight: 600;
}
.fist--draft     { background: #f1f5f9; color: #64748b; }
.fist--in_review { background: #dbeafe; color: #1d4ed8; }
.fist--validated { background: #dcfce7; color: #15803d; }
.fi-or-card__score {
  font-size: 11px; font-weight: 700; color: var(--oc, #374151);
  background: color-mix(in srgb, var(--oc, #374151) 12%, white);
  padding: 1px 7px; border-radius: 10px;
}
.fi-or-card__open { font-size: 12px; color: #94a3b8; margin-left: auto; }
.fi-or-card__body {
  padding: 8px 12px 10px;
  border-top: 1px solid color-mix(in srgb, var(--oc, #374151) 10%, #e2e8f0);
}
.fi-or-card__titre { font-size: 12px; font-weight: 600; color: #334155; margin: 0 0 6px; }
.fi-or-card__stats { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 6px; }
.fi-or-stat {
  display: flex; flex-direction: column;
  background: #f1f5f9; border-radius: 5px; padding: 3px 8px; min-width: 60px;
}
.fi-or-stat__k { font-size: 9px; color: #94a3b8; text-transform: uppercase; }
.fi-or-stat__v { font-size: 12px; font-weight: 700; color: #1e293b; }
.fi-or-card__concl { font-size: 11px; color: #475569; margin: 0; line-height: 1.4; font-style: italic; }
.fi-obs-cta-inline {
  padding: 8px 16px; border-top: 1px solid #e9d5ff;
  background: #faf5ff; display: flex; align-items: center; gap: .75rem;
}
.fi-obs-btn-mini {
  display: inline-flex; align-items: center; gap: .4rem;
  padding: .3rem .7rem; background: white;
  border: 1px solid #ddd6fe; color: #7c3aed;
  border-radius: 6px; cursor: pointer;
  font-size: .72rem; font-weight: 600; transition: all .18s;
}
.fi-obs-btn-mini:hover {
  background: #f5f3ff; border-color: #c4b5fd;
  box-shadow: 0 2px 6px rgba(124,58,237,.15);
}
.fi-obs-arrow { margin-left: .2rem; opacity: .4; font-size: .7rem; }
.fi-obs-badge-liee {
  display: inline-flex; align-items: center; gap: .2rem;
  background: #d1fae5; color: #065f46; padding: .1rem .4rem;
  border-radius: 10px; font-size: .64rem; font-weight: 700;
}
.fi-frap-counter {
  display: inline-flex; align-items: center; gap: .3rem;
  background: #ede9fe; color: #6d28d9; padding: .2rem .6rem;
  border-radius: 20px; font-size: .68rem; font-weight: 600;
}

/* ── OBSERVATION XIV ──────────────────────────────────────── */
.ft-obs-view {
  display: flex; flex-direction: column;
  max-width: 1000px; margin: 0 auto;
  background: white; border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0,0,0,.1); overflow: hidden;
}
.ft-obs-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: .8rem 1.2rem;
  background: linear-gradient(135deg, #6d28d9, #7c3aed);
  flex-shrink: 0;
}
.ft-obs-header__center {
  display: flex; align-items: center; gap: .8rem; flex: 1; justify-content: center;
}
.ft-obs-header__right { display: flex; align-items: center; gap: .5rem; }
.ft-obs-icon {
  width: 36px; height: 36px; background: rgba(255,255,255,.18);
  border-radius: 8px; display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.2rem;
}
.ft-obs-title { font-size: .9rem; font-weight: 700; color: white; }
.ft-obs-sub {
  display: flex; align-items: center; gap: .3rem;
  flex-wrap: wrap; margin-top: 2px;
}
.ft-obs-sub__ref {
  background: rgba(255,255,255,.2); padding: 1px 7px;
  border-radius: 4px; font-size: .68rem; color: white; font-weight: 600;
}
.ft-obs-sub__sep { font-size: .65rem; color: rgba(255,255,255,.5); }
.ft-obs-sub__lbl {
  font-size: .68rem; color: rgba(255,255,255,.75); font-style: italic;
  max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.ft-obs-save-btn {
  display: inline-flex; align-items: center; gap: .3rem;
  padding: .4rem 1rem; background: rgba(255,255,255,.18);
  border: 1px solid rgba(255,255,255,.3); color: white;
  border-radius: 7px; cursor: pointer; font-size: .75rem; font-weight: 600;
}
.ft-obs-save-btn:hover    { background: rgba(255,255,255,.28); }
.ft-obs-save-btn:disabled { opacity: .5; cursor: not-allowed; }

/* Compteur FRAP dans le header obs */
.obs-frap-counter {
  display: inline-flex; align-items: center; gap: .3rem;
  background: rgba(255,255,255,.2); color: white;
  padding: .2rem .6rem; border-radius: 20px; font-size: .68rem; font-weight: 600;
}

/* Bandeau flux */
.obs-flow-banner {
  display: flex; align-items: center; gap: .5rem;
  padding: .45rem 1.2rem; background: #faf5ff;
  border-bottom: 1px solid #e9d5ff; flex-shrink: 0;
}
.obs-flow-step {
  display: flex; align-items: center; gap: .3rem;
  font-size: .7rem; color: #94a3b8; font-weight: 500;
}
.obs-flow-step--active { color: #6d28d9; }
.obs-flow-num {
  display: inline-flex; align-items: center; justify-content: center;
  width: 18px; height: 18px; background: #e2e8f0; color: #64748b;
  border-radius: 50%; font-size: .6rem; font-weight: 700;
}
.obs-flow-step--active .obs-flow-num { background: #6d28d9; color: white; }
.obs-flow-sep { font-size: .7rem; color: #94a3b8; }

/* Badge FRAP dans titre section */
.obs-frap-badge {
  display: inline-flex; align-items: center;
  background: #ede9fe; color: #6d28d9;
  padding: .1rem .5rem; border-radius: 20px;
  font-size: .6rem; font-weight: 700; margin-left: auto;
}

.ft-obs-body {
  flex: 1; overflow-y: auto; padding: 1rem;
  background: #f5f3ff; display: flex; flex-direction: column; gap: .75rem;
}
.ft-obs-body--loading { justify-content: center; align-items: center; }
.ft-obs-loading-box {
  text-align: center; padding: 3rem;
  display: flex; flex-direction: column; align-items: center; gap: 1rem;
}
.ft-obs-loading-box p { font-size: .8rem; color: #64748b; margin: 0; }
.ft-obs-footer {
  display: flex; justify-content: space-between; align-items: center;
  padding: .8rem 1.2rem; background: #faf5ff;
  border-top: 1px solid #e9d5ff; flex-shrink: 0;
}
.ft-obs-section {
  background: white; border-radius: 10px;
  border: 1px solid #e9d5ff; overflow: hidden;
  box-shadow: 0 1px 3px rgba(109,40,217,.06);
}
/* Sections qui alimentent les FRAP → bordure gauche distincte */
.ft-obs-section--frap { border-left: 3px solid #7c3aed; }
.ft-obs-section__title {
  display: flex; align-items: center; gap: .4rem;
  padding: .6rem 1rem;
  background: linear-gradient(135deg, #ede9fe, #f5f3ff);
  border-bottom: 1px solid #e9d5ff;
  font-size: .78rem; font-weight: 700; color: #6d28d9;
}
.ft-obs-section__title--frap {
  background: linear-gradient(135deg, #ddd6fe, #ede9fe);
  color: #5b21b6;
}
.ft-obs-grid { display: grid; gap: .65rem; padding: .85rem 1rem; }
.ft-obs-grid--1 { grid-template-columns: 1fr; }
.ft-obs-grid--2 { grid-template-columns: 1fr 1fr; }
.ft-obs-grid--3 { grid-template-columns: 1fr 1fr 1fr; }
.ft-obs-field { display: flex; flex-direction: column; gap: .2rem; }
.ft-obs-field--full { grid-column: 1 / -1; }
.ft-obs-lbl {
  font-size: .62rem; font-weight: 700; color: #6d28d9;
  text-transform: uppercase; letter-spacing: .04em;
}
.req { color: #dc2626; }
.ft-obs-inp {
  border: 1px solid #ddd6fe; border-radius: 6px;
  padding: 6px 10px; font-size: .78rem; font-family: inherit;
  width: 100%; box-sizing: border-box; outline: none; transition: border-color .15s;
}
.ft-obs-inp:focus {
  border-color: #7c3aed;
  box-shadow: 0 0 0 2px rgba(124,58,237,.15);
}
.ft-obs-inp:disabled { background: #faf5ff; color: #64748b; }
.ft-obs-ta {
  border: 1px solid #ddd6fe; border-radius: 6px;
  padding: 6px 10px; font-size: .78rem; font-family: inherit;
  width: 100%; box-sizing: border-box; resize: vertical; outline: none;
}
.ft-obs-ta:focus {
  border-color: #7c3aed;
  box-shadow: 0 0 0 2px rgba(124,58,237,.15);
}
.ft-obs-causes-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: .4rem; padding: .75rem 1rem;
}
.ft-obs-check {
  display: flex; align-items: center; gap: .4rem;
  font-size: .78rem; cursor: pointer;
}
.ft-obs-risks-table {
  display: flex; flex-wrap: wrap; gap: .5rem 1rem; padding: .75rem 1rem .25rem;
}
.ft-obs-risk-item { flex: 1 1 160px; }
.risk-label { font-weight: 500; }
.ft-obs-sel-niveau {
  border: 1px solid #ddd6fe; border-radius: 6px;
  padding: 6px 10px; font-size: .78rem; font-family: inherit;
  width: 100%; box-sizing: border-box; outline: none; cursor: pointer;
}
.ft-obs-niveau-badge {
  display: inline-flex; align-items: center;
  padding: .2rem .7rem; border-radius: 20px;
  font-size: .7rem; font-weight: 700; margin-top: .35rem; width: fit-content;
}

/* FRAPs générées depuis l'observation */
.obs-fraps-generated {
  background: white; border-radius: 10px;
  border: 1px solid #a7f3d0; border-left: 3px solid #10b981;
  overflow: hidden; box-shadow: 0 1px 3px rgba(16,185,129,.1);
}
.obs-fraps-hd {
  display: flex; align-items: center; gap: .5rem;
  padding: .6rem 1rem; background: #f0fdf4;
  border-bottom: 1px solid #a7f3d0; font-size: .75rem;
  color: #065f46; flex-wrap: wrap;
}
.obs-fraps-info {
  font-size: .65rem; color: #4d7c60; font-style: italic; margin-left: .5rem;
}
.obs-fraps-list { padding: .5rem 1rem .75rem; display: flex; flex-direction: column; gap: .35rem; }
.obs-frap-card {
  display: flex; align-items: center; gap: .5rem;
  padding: .35rem .5rem; background: #f9fafb;
  border-radius: 6px; border: 1px solid #e5e7eb;
  font-size: .72rem;
}
.obs-frap-num {
  background: #1e3a5f; color: white; padding: .1rem .4rem;
  border-radius: 4px; font-size: .6rem; font-weight: 700; white-space: nowrap;
}
.obs-frap-rub { color: #6b7280; white-space: nowrap; }
.obs-frap-pb  { flex: 1; color: #374151; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* ── TABLE OBS ───────────────────────────────────────────── */
.obs-card { background: white; border-radius: 8px; border: 1px solid #e9d5ff; padding: .75rem 1rem; }
.obs-card__hd { display: flex; align-items: center; gap: .5rem; margin-bottom: .65rem; flex-wrap: wrap; }
.obs-card__title { font-size: .85rem; font-weight: 700; color: var(--navy); margin: 0; flex: 1; }
.obs-badge-count {
  background: #6d28d9; color: white; padding: .1rem .5rem;
  border-radius: 20px; font-size: .65rem; font-weight: 700;
}
.obs-add {
  display: inline-flex; align-items: center; gap: .2rem;
  padding: .2rem .6rem; background: #ede9fe;
  border: 1px solid #c4b5fd; color: #6d28d9;
  border-radius: 6px; font-size: .65rem; cursor: pointer;
}
.obs-table-wrap {
  overflow-x: auto; border-radius: 6px; border: 1px solid var(--border);
}
.obs-tbl { width: 100%; border-collapse: collapse; font-size: .7rem; }
.obs-tbl th,
.obs-tbl td {
  padding: .3rem .5rem; border-bottom: 1px solid var(--border);
  border-right: 1px solid var(--border); text-align: left; vertical-align: middle;
}
.obs-tbl th { background: #f8fafc; font-weight: 700; color: #475569; }
.obs-n { font-size: .6rem; font-weight: 700; color: #94a3b8; text-align: center; }
.obs-ec { text-align: center; color: #94a3b8; padding: 1rem; font-style: italic; font-size: .7rem; }
.obs-del {
  display: inline-flex; align-items: center; justify-content: center;
  width: 22px; height: 22px; background: #fee2e2;
  border: 1px solid #fecaca; color: #dc2626; border-radius: 4px; cursor: pointer;
}
.obs-inp-sm,
.obs-ta-sm {
  width: 100%; border: 1px solid #cbd5e1; border-radius: 4px;
  padding: .25rem .4rem; font-size: .7rem; font-family: inherit; box-sizing: border-box;
}
.obs-sel-sm {
  border: 1px solid #cbd5e1; border-radius: 4px;
  padding: .15rem .3rem; font-size: .7rem; width: 100%;
}
.tc { text-align: center; }

/* ════ VUE FOCI ═════════════════════════════════════════════ */
.foci-view { display: flex; flex-direction: column; height: 100%; min-height: 0; }

/* Header FOCI */
.foci-hd {
  display: flex; align-items: center; gap: .75rem;
  padding: .75rem 1rem; background: #1e3a5f;
  border-radius: 10px 10px 0 0; flex-shrink: 0; flex-wrap: wrap;
}
.foci-hd__center { display: flex; align-items: center; gap: .75rem; flex: 1; }
.foci-hd__right  { display: flex; align-items: center; gap: .5rem; }
.foci-hd__icon {
  width: 36px; height: 36px; background: rgba(255,255,255,.15);
  border-radius: 8px; display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.2rem; flex-shrink: 0;
}
.foci-hd__title { font-size: .88rem; font-weight: 700; color: white; }
.foci-hd__sub   { font-size: .68rem; color: rgba(255,255,255,.7); margin-top: 2px; }
.foci-regen-btn {
  display: inline-flex; align-items: center; gap: .3rem;
  padding: .35rem .85rem; background: rgba(255,255,255,.18);
  border: 1px solid rgba(255,255,255,.3); color: white;
  border-radius: 7px; cursor: pointer; font-size: .72rem; font-weight: 600;
}
.foci-regen-btn:hover    { background: rgba(255,255,255,.28); }
.foci-regen-btn:disabled { opacity: .5; cursor: not-allowed; }

/* Status bar */
.foci-status {
  display: flex; align-items: center; gap: .5rem;
  padding: .3rem 1rem; font-size: .72rem; flex-shrink: 0;
}
.foci-status--gen  { background: #dbeafe; color: #1d4ed8; }
.foci-status--ok   { background: #d1fae5; color: #065f46; border-bottom: 1px solid #a7f3d0; }
.foci-status--warn { background: #fef3c7; color: #92400e; }
.foci-status--info { background: #f0f9ff; color: #0369a1; }

/* Document */
.foci-doc { background: white; border-radius: 0 0 10px 10px; overflow: auto; flex: 1; }

/* En-tête institutionnel */
.foci-entete { display: grid; grid-template-columns: 1fr 1.4fr 1fr; border-bottom: 2px solid #1e3a5f; }
.foci-entete__left {
  display: flex; align-items: flex-start; gap: .75rem;
  padding: .85rem; background: #f8fafc; border-right: 1px solid #e2e8f0;
}
.foci-entete__logo {
  width: 44px; height: 44px; background: #1e3a5f; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.3rem; flex-shrink: 0;
}
.foci-entete__meta { font-size: .72rem; }
.foci-entete__zone {
  font-weight: 700; color: #1e3a5f; font-size: .62rem;
  text-transform: uppercase; margin-bottom: .25rem;
}
.foci-entete__row { display: flex; align-items: center; gap: .35rem; flex-wrap: wrap; margin-bottom: .15rem; }
.foci-mk { color: #64748b; font-weight: 600; font-size: .68rem; }
.foci-mv { color: #0f172a; font-weight: 500; font-size: .68rem; }
.foci-msep { width: 1px; height: 12px; background: #e2e8f0; }
.foci-entete__center {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  padding: 1rem; text-align: center;
  background: linear-gradient(135deg, #1e3a5f, #2c5282);
}
.foci-entete__title { font-size: .95rem; font-weight: 800; color: white; }
.foci-entete__sub {
  font-size: .68rem; color: rgba(255,255,255,.75); margin-top: .2rem;
  text-transform: uppercase; letter-spacing: .07em;
}
.foci-entete__note {
  display: flex; align-items: center; gap: .3rem;
  font-size: .6rem; color: rgba(255,255,255,.5); margin-top: .4rem; font-style: italic;
}
.foci-entete__right { padding: .85rem; background: #f8fafc; border-left: 1px solid #e2e8f0; }
.foci-mission-card { height: 100%; display: flex; flex-direction: column; gap: .3rem; }
.foci-mc-lbl { font-size: .6rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; }
.foci-mc-val { font-size: .78rem; font-weight: 600; color: #1e293b; flex: 1; line-height: 1.4; }
.foci-mc-stats { display: flex; gap: .3rem; flex-wrap: wrap; }
.foci-pill { padding: .1rem .5rem; border-radius: 20px; font-size: .6rem; font-weight: 700; }
.foci-pill--blue  { background: #dbeafe; color: #1d4ed8; }
.foci-pill--green { background: #d1fae5; color: #065f46; }

/* Légende */
.foci-legend {
  display: grid;
  grid-template-columns: 90px 75px 1fr 110px 110px 110px 140px 120px 105px 78px 105px 95px;
  background: #1e3a5f; border-bottom: 1px solid #2c5282;
}
.foci-lc {
  padding: .35rem .4rem; font-size: .58rem; font-weight: 700;
  color: rgba(255,255,255,.8); text-transform: uppercase; letter-spacing: .04em;
  border-right: 1px solid rgba(255,255,255,.1);
}
.foci-lc:last-child { border-right: none; }

/* Vide */
.foci-empty { text-align: center; padding: 4rem 2rem; }
.foci-empty__ico   { font-size: 2.5rem; color: #94a3b8; margin-bottom: 1rem; }
.foci-empty__title { font-weight: 600; color: #475569; margin-bottom: .25rem; }
.foci-empty__sub   { font-size: .78rem; color: #94a3b8; max-width: 480px; margin: 0 auto; }
.foci-gen-cta {
  display: inline-flex; align-items: center; gap: .4rem;
  margin-top: 1.5rem; padding: .5rem 1.25rem;
  background: #1e3a5f; color: white; border: none;
  border-radius: 8px; cursor: pointer; font-size: .8rem; font-weight: 600;
}
.foci-gen-cta:disabled { opacity: .5; cursor: not-allowed; }

/* Objectif */
.foci-obj-block { border-bottom: 2px solid #1e3a5f; }
.foci-obj-banner {
  display: flex; align-items: center; gap: .5rem;
  padding: .4rem .85rem; background: #1e3a5f;
}
.foci-obj-lbl {
  background: rgba(255,255,255,.2); padding: .1rem .5rem;
  border-radius: 4px; font-size: .6rem; font-weight: 700;
  color: white; white-space: nowrap;
}
.foci-obj-txt { font-size: .72rem; font-weight: 600; color: white; }

/* Rubrique */
.foci-rubr-block { border-bottom: 1px solid #dde5ef; }
.foci-rubr-banner {
  display: flex; align-items: center; gap: .4rem;
  padding: .32rem .85rem;
  background: linear-gradient(90deg, #eff6ff, #f8fafc);
  border-bottom: 1px solid #bfdbfe;
  font-size: .7rem; font-weight: 600; color: #1e40af;
}

/* Sous-rubrique */
.foci-ssrubr-banner {
  display: flex; align-items: center; gap: .3rem;
  padding: .22rem 1.25rem; background: #f0fdf4;
  border-bottom: 1px solid #bbf7d0;
  font-size: .67rem; color: #065f46; font-style: italic;
}

/* Ligne FRAP */
.foci-row {
  display: grid;
  grid-template-columns: 90px 75px 1fr 110px 110px 110px 140px 120px 105px 78px 105px 95px;
  border-bottom: 1px solid #f1f5f9; transition: background .1s;
}
.foci-row:hover { background: #fafbfc; }
.foci-row--critique    { border-left: 3px solid #9d174d; }
.foci-row--insuffisant { border-left: 3px solid #dc2626; }
.foci-row--ameliorer   { border-left: 3px solid #d97706; }
.foci-row--ok          { border-left: 3px solid #16a34a; }

/* Cellule */
.foci-cell {
  padding: .4rem .4rem; font-size: .7rem;
  border-right: 1px solid #f1f5f9;
  display: flex; align-items: flex-start; min-height: 50px;
}
.foci-cell:last-child { border-right: none; }
.foci-cell--num  { justify-content: center; align-items: center; }
.foci-cell--niv  { justify-content: center; align-items: center; }
.foci-cell--date { align-items: center; }
.foci-editable {
  position: relative; width: 100%;
  display: flex; align-items: flex-start; gap: .2rem; min-height: 34px;
}
.foci-editable:hover .foci-edit-btn { opacity: 1; }
.foci-txt {
  flex: 1; line-height: 1.4; color: #334155;
  cursor: text; word-break: break-word; white-space: pre-wrap;
}
.foci-txt--reco  { color: #1e40af; font-weight: 500; }
.foci-txt--green { color: #065f46; }
.foci-edit-btn {
  opacity: 0;
  display: inline-flex; align-items: center; justify-content: center;
  width: 17px; height: 17px; background: #eff6ff;
  border: 1px solid #bfdbfe; border-radius: 3px;
  cursor: pointer; color: #1d4ed8; font-size: .6rem;
  flex-shrink: 0; transition: opacity .15s;
}
.foci-ta,
.foci-inp {
  width: 100%; border: 1px solid #7c3aed; border-radius: 4px;
  padding: .25rem .4rem; font-size: .7rem; font-family: inherit;
  box-shadow: 0 0 0 2px rgba(124,58,237,.15);
  box-sizing: border-box; resize: vertical; outline: none;
}
.foci-inp { resize: none; height: 26px; }
.foci-num-btn {
  display: inline-flex; align-items: center; gap: .2rem;
  background: none; border: none; cursor: pointer; padding: .1rem 0;
}
.foci-num-badge {
  background: #1e3a5f; color: white; padding: .12rem .42rem;
  border-radius: 4px; font-size: .6rem; font-weight: 700; letter-spacing: .02em;
}
.foci-ext { font-size: .58rem; color: #94a3b8; }
.foci-niv-badge {
  display: inline-block; padding: .1rem .42rem;
  border-radius: 20px; font-size: .58rem; font-weight: 700;
  text-align: center; white-space: nowrap;
}
.foci-date-inp {
  border: 1px solid #e2e8f0; border-radius: 4px;
  padding: .18rem .32rem; font-size: .66rem; width: 100%; box-sizing: border-box;
}
.foci-foot-note {
  display: flex; align-items: center; gap: .4rem;
  padding: .6rem 1rem; background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  font-size: .67rem; color: #64748b; font-style: italic;
}

/* ── MODAL OUTILS ─────────────────────────────────────────── */
.om-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,.5);
  display: flex; align-items: center; justify-content: center; z-index: 1000;
}
.om-dialog {
  background: white; border-radius: 16px;
  width: 90%; max-width: 600px; max-height: 80vh;
  display: flex; flex-direction: column; overflow: hidden;
}
.om-hd {
  display: flex; justify-content: space-between; align-items: center;
  padding: 1rem; border-bottom: 1px solid #e2e8f0;
}
.om-hd__left { display: flex; align-items: center; gap: .8rem; }
.om-hd__icon {
  width: 40px; height: 40px;
  background: linear-gradient(135deg, #1e40af, #6d28d9);
  border-radius: 10px; display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.2rem;
}
.om-hd__title { font-size: 1rem; font-weight: 700; margin: 0; }
.om-hd__sub { display: flex; gap: .3rem; margin-top: .2rem; }
.om-ctx { font-size: .65rem; background: #f1f5f9; padding: .1rem .5rem; border-radius: 20px; }
.om-ctx--proc { background: #ede9fe; color: #6d28d9; }
.om-selbar {
  display: flex; justify-content: space-between; align-items: center;
  padding: .5rem 1rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0;
}
.om-tags { display: flex; gap: .3rem; flex-wrap: wrap; }
.om-tag {
  display: inline-flex; align-items: center; gap: .2rem;
  background: var(--ot, #374151); color: white;
  padding: .2rem .4rem .2rem .6rem; border-radius: 20px; font-size: .7rem;
}
.om-tag button {
  background: none; border: none; color: white;
  cursor: pointer; font-size: .8rem; padding: 0 .1rem;
}
.om-clear {
  background: none; border: 1px solid #fecaca; color: #dc2626;
  padding: .2rem .6rem; border-radius: 6px; font-size: .7rem; cursor: pointer;
}
.om-body  { flex: 1; overflow: auto; padding: 1rem; }
.om-grid  {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: .5rem;
}
.om-card {
  display: flex; align-items: center; gap: .5rem;
  padding: .5rem; border: 2px solid #e2e8f0;
  border-radius: 10px; cursor: pointer; background: white; width: 100%; text-align: left;
}
.om-card:hover    { background: #f8fafc; }
.om-card--sel     { border-color: #1e40af; background: #eff6ff; }
.om-card__num {
  width: 32px; height: 32px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  color: white; font-weight: 700; flex-shrink: 0;
}
.om-card__lbl { font-size: .7rem; font-weight: 600; }
.om-ft {
  display: flex; justify-content: flex-end; gap: .5rem;
  padding: 1rem; border-top: 1px solid #e2e8f0;
}
.om-confirm {
  background: linear-gradient(135deg, #1e40af, #6d28d9);
  color: white; padding: .4rem 1rem; border-radius: 8px;
  border: none; cursor: pointer; font-size: .8rem; font-weight: 600;
}
.om-confirm:disabled { opacity: .5; cursor: not-allowed; }

/* ── MODAL FRAP DÉTAIL ────────────────────────────────────── */
.fm-dialog {
  background: white; border-radius: 14px;
  width: 90%; max-width: 680px; max-height: 85vh;
  display: flex; flex-direction: column; overflow: hidden;
  box-shadow: 0 20px 60px rgba(0,0,0,.2);
}
.fm-hd {
  display: flex; justify-content: space-between; align-items: center;
  padding: 1rem 1.2rem; border-bottom: 1px solid #e2e8f0; background: #f8fafc;
}
.fm-hd__left  { display: flex; align-items: center; gap: .75rem; }
.fm-hd__right { display: flex; align-items: center; gap: .5rem; }
.fm-icon {
  width: 38px; height: 38px; background: #1e3a5f; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.1rem;
}
.fm-title { font-size: 1rem; font-weight: 700; margin: 0; color: #0f172a; }
.fm-sub   { font-size: .7rem; color: #64748b; margin-top: 2px; }
.fm-body  { flex: 1; overflow-y: auto; padding: 1rem; }
.fm-grid  { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
.fm-field { display: flex; flex-direction: column; gap: .2rem; }
.fm-field--full { grid-column: span 2; }
.fm-lbl {
  font-size: .62rem; font-weight: 700; color: #1e40af;
  text-transform: uppercase; letter-spacing: .04em;
}
.fm-lbl--green { color: #065f46; }
.fm-val {
  font-size: .8rem; color: #334155; line-height: 1.5;
  background: #f8fafc; padding: .4rem .6rem;
  border-radius: 6px; border: 1px solid #f1f5f9; white-space: pre-wrap;
}
.fm-val--obj  { background: #eff6ff; border-left: 3px solid #2563eb; color: #1e40af; }
.fm-val--reco { border-left: 3px solid #1e40af; background: #eff6ff; }
.fm-val--green { background: #f0fdf4; border-left: 3px solid #16a34a; color: #065f46; }
.fm-ft { display: flex; justify-content: flex-end; padding: .75rem 1.2rem; border-top: 1px solid #e2e8f0; }

/* ── TOAST ───────────────────────────────────────────────── */
.ft-toast {
  position: fixed; bottom: 1rem; right: 1rem;
  display: flex; align-items: center; gap: .5rem;
  padding: .5rem 1rem; border-radius: 8px; font-size: .75rem;
  z-index: 2000; box-shadow: 0 4px 12px rgba(0,0,0,.15);
}
.ft-toast--success { background: #065f46; color: white; }
.ft-toast--error   { background: #dc2626; color: white; }
.ft-toast__x { background: none; border: none; color: white; opacity: .7; cursor: pointer; margin-left: .5rem; }

/* ── SPINNER ─────────────────────────────────────────────── */
.ft-spin {
  display: inline-block; width: .8rem; height: .8rem;
  border: 2px solid rgba(255,255,255,.3); border-top-color: white;
  border-radius: 50%; animation: spin .7s linear infinite;
}
.ft-spin--xs { width: .55rem; height: .55rem; border-color: rgba(100,116,139,.3); border-top-color: #64748b; }
.ft-spin--lg { width: 2rem; height: 2rem; border-width: 3px; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ── TRANSITIONS ─────────────────────────────────────────── */
.om-fade-enter-active,
.om-fade-leave-active { transition: opacity .2s; }
.om-fade-enter-from,
.om-fade-leave-to { opacity: 0; }
.toast-pop-enter-active,
.toast-pop-leave-active { transition: all .2s; }
.toast-pop-enter-from,
.toast-pop-leave-to { opacity: 0; transform: translateY(10px); }

/* ── RESPONSIVE ──────────────────────────────────────────── */
@media (max-width: 1200px) {
  .foci-legend,
  .foci-row {
    grid-template-columns: 80px 70px 1fr 95px 95px 95px 125px 105px 95px 72px 95px 85px;
  }
}
@media (max-width: 900px) {
  .foci-entete { grid-template-columns: 1fr; }
  .foci-legend,
  .foci-row { display: flex; flex-direction: column; }
  .foci-lc,
  .foci-cell { border-right: none; border-bottom: 1px solid #f1f5f9; }
}
@media (max-width: 880px) {
  .ft-prog-badge,
  .ft-stat-lbl { display: none; }
  .fi-constat-row { grid-template-columns: 1fr; }
  .ft-obs-grid--2,
  .ft-obs-grid--3 { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
  .ft-topbar { flex-direction: column; align-items: flex-start; }
  .fi-test-row { grid-template-columns: 22px 1fr; }
}
</style>