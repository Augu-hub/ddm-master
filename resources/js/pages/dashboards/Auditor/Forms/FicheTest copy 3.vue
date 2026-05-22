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
          <span v-if="programmeData.found" class="ft-stat-lbl">{{ programmeData.total_objectifs }} obj. · {{ programmeData.total_tests }} tests</span>
        </div>
        <div class="ft-topbar__right">
          <span class="ft-chip-role"><i class="ti ti-shield-half"></i>{{ props.auditorRole }}</span>
          <span class="ft-chip-user"><i class="ti ti-user-check"></i>{{ props.auditeurNom }}</span>
          <template v-if="!isLocked">
            <button class="ft-btn ft-btn--ghost" :disabled="processing" @click="annuler"><i class="ti ti-x"></i></button>
            <button class="ft-btn ft-btn--save" :disabled="processing" @click="submit()">
              <span v-if="processing" class="ft-spin"></span>
              <i v-else class="ti ti-device-floppy"></i>
              {{ form.id ? 'Enregistrer' : 'Créer' }}
            </button>
            <button v-if="form.id && form.validation_status === 'draft'" class="ft-btn ft-btn--submit" @click="soumettre">
              <i class="ti ti-send"></i>Soumettre
            </button>
          </template>
          <template v-if="canManage && form.validation_status === 'in_review'">
            <button class="ft-btn ft-btn--validate" @click="valider('validate')"><i class="ti ti-circle-check"></i>Valider</button>
            <button class="ft-btn ft-btn--reject" @click="promptReject"><i class="ti ti-circle-x"></i>Rejeter</button>
          </template>
        </div>
      </header>

      <!-- Banners -->
      <div v-if="form.validation_status === 'validated'" class="ft-banner ft-banner--ok"><i class="ti ti-lock"></i> Fiche <strong>validée</strong> — lecture seule</div>
      <div v-else-if="form.validation_status === 'in_review'" class="ft-banner ft-banner--review"><i class="ti ti-clock"></i> En attente de validation<span v-if="canManage"> · DM/CM peut valider ou rejeter</span></div>
      <div v-else-if="form.validation_status === 'draft' && form.validation_note" class="ft-banner ft-banner--rejected"><i class="ti ti-circle-x"></i> Rejetée — <em>{{ form.validation_note }}</em></div>

      <!-- ══ CONTENU ══════════════════════════════════════════════ -->
      <div class="ft-main">

        <!-- ◈ LISTE DES TESTS ──────────────────────────────────── -->
        <div v-if="!ficheActif && !constatActif && !obsVueActif" class="ft-tests-view">
          <div v-if="!programmeData.objectifs?.length" class="ft-empty">
            <div class="ft-empty__ico"><i class="ti ti-clipboard-off"></i></div>
            <p class="ft-empty__title">Aucun test affecté</p>
            <p class="ft-empty__sub">Contactez le Chef de Mission pour vous affecter des tests dans le programme de travail.</p>
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
                  :class="getResultat(obj.num, tRef(test,oi,ti)) ? 'ft-test--done' : ''">
                  <div class="ft-test__row">
                    <code class="ft-ref">{{ tRef(test, oi, ti) }}</code>
                    <div class="ft-test__info">
                      <p class="ft-test__lbl">{{ test.libelle || '—' }}</p>
                      <div class="ft-test__chips">
                        <span v-if="test.periode_testee" class="ft-chip ft-chip--blue"><i class="ti ti-calendar"></i>{{ test.periode_testee }}</span>
                        <span v-if="test.lieu" class="ft-chip ft-chip--green"><i class="ti ti-map-pin"></i>{{ test.lieu }}</span>
                        <span v-if="test.taille_echantillon" class="ft-chip ft-chip--purple">n={{ test.taille_echantillon }}</span>
                      </div>
                      <!-- Outils liés (badges) -->
                      <div v-if="getOutilsPourTest(obj.num, tRef(test,oi,ti)).length" class="ft-test__outils-badges">
                        <button v-for="ot in getOutilsPourTest(obj.num, tRef(test,oi,ti))" :key="ot.outil_code + ot.proc_idx"
                          class="ft-outil-badge" :style="`--oc:${ot.color}`"
                          @click="ouvrirOutil(obj, test, ot.outil_code, ot.proc_idx, oi, ti)"
                          :title="ot.label">
                          <i class="ti ti-tool"></i>{{ ot.outil_code }}
                          <span v-if="ot.ia_score !== null" class="ft-outil-badge__score">{{ ot.ia_score }}/10</span>
                        </button>
                      </div>
                    </div>
                    <select v-if="!isLocked" class="ft-sel-result"
                      :value="getResultat(obj.num, tRef(test,oi,ti))"
                      @change="setResultat(obj.num, tRef(test,oi,ti), ($event.target as HTMLSelectElement).value)">
                      <option value="">— résultat —</option>
                      <option value="conforme">✅ Conforme</option>
                      <option value="ecart">⚠️ Écart</option>
                      <option value="nc">❌ Non conforme</option>
                      <option value="na">N/A</option>
                    </select>
                    <span v-else class="ft-result-pill" :class="`frp--${getResultat(obj.num, tRef(test,oi,ti))}`">{{ getResultat(obj.num, tRef(test,oi,ti)) || '—' }}</span>
                    <div class="ft-test__acts">
                      <button class="ft-btn ft-btn--fiche"
                        :class="getConstat(obj.num, tRef(test,oi,ti)) ? 'ft-btn--fiche-done' : ''"
                        @click="ouvrirFiche(obj, test, oi, ti)">
                        <i class="ti ti-clipboard-text"></i> Fiche test
                      </button>
                      <!-- ✦ Bouton Observation PETIT -->
                      <button class="ft-btn-obs-mini"
                        :class="testHasOutil(obj.num, tRef(test,oi,ti), 'XIV') ? 'ft-btn-obs-mini--done' : ''"
                        @click="ouvrirObsDirecte(obj, test, oi, ti)"
                        title="Fiche d'Observation Directe (XIV)">
                        <i class="ti ti-eye"></i>
                        <span v-if="testHasOutil(obj.num, tRef(test,oi,ti), 'XIV')" class="ft-btn-obs-mini__dot"></span>
                      </button>
                      <div class="dropdown">
                        <button class="ft-ib ft-ib--sm ft-ib--tool dropdown-toggle"
                          :class="testHasAnyOutil(obj.num, tRef(test,oi,ti)) ? 'ft-ib--active' : ''"
                          data-bs-toggle="dropdown" title="Ouvrir un outil IFACI">
                          <i class="ti ti-tool"></i>
                          <span v-if="testHasAnyOutil(obj.num, tRef(test,oi,ti))" class="ft-dot"></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end ft-dd shadow-sm">
                          <li class="ft-dd__head">Ouvrir un outil sur ce test</li>
                          <li v-for="outil in props.outilsIfaci" :key="outil.code">
                            <button class="ft-dd__item" @click="ouvrirOutil(obj, test, outil.code, 0, oi, ti)">
                              <span class="ft-dd__dot" :style="`background:${outil.color}`"></span>
                              <span class="ft-dd__code" :style="`color:${outil.color}`">{{ outil.code }}</span>
                              <span class="ft-dd__lbl">{{ outil.label }}</span>
                              <i v-if="testHasOutil(obj.num, tRef(test,oi,ti), outil.code)" class="ti ti-check ft-dd__chk"></i>
                            </button>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                  <div v-if="test.procedures?.length" class="ft-procs">
                    <div v-for="(proc, pi) in test.procedures" :key="pi" class="ft-proc"
                      :class="getOutilsForProc(obj.num, tRef(test,oi,ti), pi).length ? 'ft-proc--linked' : ''">
                      <span class="ft-proc__n">{{ pi + 1 }}</span>
                      <span class="ft-proc__txt">{{ proc }}</span>
                      <div class="ft-proc__acts">
                        <button v-for="code in getOutilsForProc(obj.num, tRef(test,oi,ti), pi)" :key="code"
                          class="ft-outil-tag" :style="`--ot:${outilColor(code)}`"
                          @click="ouvrirOutil(obj, test, code, pi, oi, ti)">
                          <i class="ti ti-tool"></i>{{ code }}
                        </button>
                        <button v-if="!isLocked" class="ft-ib ft-ib--xs"
                          :class="getOutilsForProc(obj.num, tRef(test,oi,ti), pi).length ? 'ft-ib--edit' : 'ft-ib--add'"
                          @click="ouvrirChoixOutil(obj, test, pi, oi, ti)">
                          <i :class="getOutilsForProc(obj.num, tRef(test,oi,ti), pi).length ? 'ti ti-edit' : 'ti ti-plus'"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ◈ FICHE DE TEST IFACI ──────────────────────────────── -->
        <div v-if="ficheActif && !obsActif && !obsVueActif" class="ft-fiche-capture">

          <div class="ft-fiche-header">
            <button class="ft-btn-back" @click="ficheActif = null"><i class="ti ti-arrow-left"></i> Retour</button>
            <div class="ft-fiche-header__center">
              <div class="ft-fiche-icon"><i class="ti ti-clipboard-text"></i></div>
              <div>
                <div class="ft-fiche-title">Fiche de Test d'Audit</div>
                <div class="ft-fiche-sub">{{ ficheActif.testRef }} · Objectif {{ ficheActif.objNum }}</div>
              </div>
            </div>
            <div class="ft-fiche-header__right">
              <button v-if="!isLocked" class="ft-btn-save" :disabled="processing" @click="saveFiche"><i class="ti ti-device-floppy"></i> Enregistrer</button>
              <button class="ft-btn-close" @click="ficheActif = null"><i class="ti ti-x"></i></button>
            </div>
          </div>

          <div class="ft-fiche-body">

            <!-- Objectif -->
            <div class="fi-section-title">Objectif d'audit — {{ ficheActif.objNum }}</div>
            <div class="fi-objectif">{{ getObjectifTexte() }}</div>

            <!-- Auditeur / Date -->
            <table class="fi-auditeur-table">
              <tr><th>Auditeur interne</th><th>Date</th></tr>
              <tr>
                <td>{{ props.auditeurNom || '—' }}</td>
                <td>
                  <input v-if="!isLocked" type="date" class="fi-date-inp" v-model="ficheActif.date" />
                  <span v-else>{{ formatDate(ficheActif.date) || '—' }}</span>
                </td>
              </tr>
            </table>

            <!-- Source d'informations -->
            <div class="fi-section-title fi-section-title--light">Source d'informations</div>
            <div class="fi-source-box">
              <div v-for="(src, i) in ficheSourcesDefaut" :key="i" class="fi-source-item">
                <span class="fi-dash">-</span>{{ src }}
              </div>
              <textarea v-if="!isLocked" class="fi-ta" v-model="ficheActif.sourceComplementaire" placeholder="Source complémentaire…" rows="2"></textarea>
              <div v-else-if="ficheActif.sourceComplementaire" class="fi-source-item"><span class="fi-dash">-</span>{{ ficheActif.sourceComplementaire }}</div>
            </div>

            <!-- Tests d'audit -->
            <div class="fi-section-title">Tests d'audit — {{ ficheActif.testRef }}</div>
            <div class="fi-tests-list">
              <template v-if="ficheActif.test?.procedures?.length">
                <div v-for="(proc, pi) in ficheActif.test.procedures" :key="pi" class="fi-proc-block">
                  <div class="fi-test-row">
                    <div class="fi-test-num">{{ pi + 1 }}</div>
                    <div class="fi-test-lbl">{{ proc }}</div>
                    <div class="fi-proc-outils">
                      <button v-for="code in getOutilsForProc(ficheActif.objNum, ficheActif.testRef, pi)" :key="code"
                        class="fi-outil-btn" :style="`--oc:${outilColor(code)}`"
                        @click="ouvrirOutilDepuisFiche(code, pi)">
                        <i class="ti ti-tool"></i> Outil {{ code }}
                      </button>
                      <button v-if="!isLocked" class="fi-add-outil-btn" @click="ouvrirChoixOutil(ficheActif.obj, ficheActif.test, pi, ficheActif.oi, ficheActif.ti)"><i class="ti ti-plus"></i></button>
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
                    @change="setResultat(ficheActif.objNum, ficheActif.testRef, ($event.target as HTMLSelectElement).value)">
                    <option value="">— résultat —</option><option value="conforme">✅ Conforme</option>
                    <option value="ecart">⚠️ Écart</option><option value="nc">❌ Non conforme</option><option value="na">N/A</option>
                  </select>
                  <span v-else class="fi-result-badge" :class="'fi-rb--' + getResultat(ficheActif.objNum, ficheActif.testRef)">{{ resultatLabel(getResultat(ficheActif.objNum, ficheActif.testRef)) || '—' }}</span>
                  <div class="fi-proc-outils">
                    <button v-for="code in getOutilsForProc(ficheActif.objNum, ficheActif.testRef, 0)" :key="code"
                      class="fi-outil-btn" :style="`--oc:${outilColor(code)}`" @click="ouvrirOutilDepuisFiche(code, 0)">
                      <i class="ti ti-tool"></i> Outil {{ code }}
                    </button>
                    <button v-if="!isLocked" class="fi-add-outil-btn" @click="ouvrirChoixOutil(ficheActif.obj, ficheActif.test, 0, ficheActif.oi, ficheActif.ti)"><i class="ti ti-plus"></i></button>
                  </div>
                </div>
                <div class="fi-constat-row">
                  <div class="fi-constat-cell">
                    <label class="fi-mini-lbl">Constat / Observation</label>
                    <textarea v-if="!isLocked" class="fi-ta fi-ta--sm" rows="2"
                      :value="getConstat(ficheActif.objNum, ficheActif.testRef)"
                      @input="setConstat(ficheActif.objNum, ficheActif.testRef, ($event.target as HTMLTextAreaElement).value)"
                      placeholder="Observation, écart constaté…"></textarea>
                    <div v-else class="fi-ro">{{ getConstat(ficheActif.objNum, ficheActif.testRef) || '—' }}</div>
                  </div>
                  <div class="fi-preuve-cell">
                    <label class="fi-mini-lbl">Référence de preuve</label>
                    <input v-if="!isLocked" type="text" class="fi-inp fi-inp--sm"
                      :value="getPreuve(ficheActif.objNum, ficheActif.testRef)"
                      @input="setPreuve(ficheActif.objNum, ficheActif.testRef, ($event.target as HTMLInputElement).value)"
                      placeholder="Réf. document…" />
                    <div v-else class="fi-ro">{{ getPreuve(ficheActif.objNum, ficheActif.testRef) || '—' }}</div>
                  </div>
                </div>
              </template>
            </div>

            <!-- ══ RÉSULTATS DU TEST D'AUDIT ══ -->
            <div class="fi-section-title fi-section-title--light">Résultats du test d'audit</div>

            <!-- Résultats des outils utilisés -->
            <div v-if="getOutilsPourTest(ficheActif.objNum, ficheActif.testRef).length" class="fi-outils-resultats">
              <div class="fi-or-title"><i class="ti ti-tool"></i> Résultats des outils utilisés</div>
              <div class="fi-or-list">
                <div v-for="ot in getOutilsPourTest(ficheActif.objNum, ficheActif.testRef)" :key="ot.outil_code + ot.proc_idx"
                  class="fi-or-card" :style="`--oc:${ot.color}`"
                  @click="ouvrirOutilDepuisFiche(ot.outil_code, ot.proc_idx)">
                  <div class="fi-or-card__head">
                    <span class="fi-or-card__code" :style="`background:${ot.color}`">{{ ot.outil_code }}</span>
                    <span class="fi-or-card__label">{{ ot.label }}</span>
                    <span v-if="ot.statut" class="fi-or-card__st" :class="'fist--' + ot.statut">{{ stLbl(ot.statut) }}</span>
                    <span v-if="ot.ia_score !== null && ot.ia_score !== undefined" class="fi-or-card__score">IA: {{ ot.ia_score }}/10</span>
                    <i class="ti ti-external-link fi-or-card__open"></i>
                  </div>
                  <div v-if="ot.resume" class="fi-or-card__body">
                    <p v-if="ot.resume.titre" class="fi-or-card__titre">{{ ot.resume.titre }}</p>
                    <div v-if="ot.resume.resultats?.length" class="fi-or-card__stats">
                      <span v-for="(r, ri) in ot.resume.resultats.slice(0,3)" :key="ri" class="fi-or-stat">
                        <span class="fi-or-stat__k">{{ r.label }}</span>
                        <span class="fi-or-stat__v">{{ r.valeur }}</span>
                      </span>
                    </div>
                    <p v-if="ot.resume.conclusion" class="fi-or-card__concl">{{ ot.resume.conclusion }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- ✦ Lien Observation XIV depuis fiche — BOUTON COMPACT -->
            <div v-if="ficheActif" class="fi-obs-cta-inline">
              <button class="fi-obs-btn-mini" @click="ouvrirObsDepuisFiche">
                <i class="ti ti-eye"></i>
                <span>Observation directe (XIV)</span>
                <span v-if="obsXIVLiee" class="fi-obs-badge-liee"><i class="ti ti-check"></i> Liée</span>
                <i class="ti ti-chevron-right fi-obs-arrow"></i>
              </button>
            </div>

          </div><!-- /ft-fiche-body -->

          <div class="ft-fiche-footer">
            <button class="ft-btn ft-btn--ghost" @click="ficheActif = null">Fermer</button>
            <button v-if="!isLocked" class="ft-btn ft-btn--save" :disabled="processing" @click="saveFiche"><i class="ti ti-device-floppy"></i> Enregistrer</button>
          </div>
        </div><!-- /ft-fiche-capture -->

        <!-- ◈ VUE OBSERVATION DIRECTE XIV — PLEINE PAGE ──────────── -->
        <div v-if="obsVueActif" class="ft-obs-view">
          <div class="ft-obs-header">
            <button class="ft-btn-back" @click="fermerObsVue">
              <i class="ti ti-arrow-left"></i> Retour
            </button>
            <div class="ft-obs-header__center">
              <div class="ft-obs-icon"><i class="ti ti-eye"></i></div>
              <div>
                <div class="ft-obs-title">Fiche d'Observation Directe</div>
                <div class="ft-obs-sub" v-if="obsVueContext">
                  <span class="ft-obs-sub__ref">{{ obsVueContext.testRef }}</span>
                  <span class="ft-obs-sub__sep">·</span>
                  <span>Objectif {{ obsVueContext.objNum }}</span>
                  <span v-if="obsVueContext.test?.libelle" class="ft-obs-sub__sep">·</span>
                  <span v-if="obsVueContext.test?.libelle" class="ft-obs-sub__lbl">{{ obsVueContext.test.libelle }}</span>
                </div>
              </div>
            </div>
            <div class="ft-obs-header__right">
              <button v-if="!isLocked && !obsLoadingBD" class="ft-obs-save-btn" :disabled="obsSaving" @click="sauvegarderObservation">
                <span v-if="obsSaving" class="ft-spin"></span>
                <i v-else class="ti ti-device-floppy"></i>
                {{ obsSaving ? 'Sauvegarde…' : 'Enregistrer' }}
              </button>
            </div>
          </div>

          <div v-if="obsLoadingBD" class="ft-obs-body ft-obs-body--loading">
            <div class="ft-obs-loading-box">
              <span class="ft-spin ft-spin--lg"></span>
              <p>Chargement de la fiche d'observation…</p>
            </div>
          </div>

          <div v-else class="ft-obs-body">

            <!-- ═══ SECTION 1 : DESCRIPTION DU CONSTAT ═══ -->
            <div class="ft-obs-section">
              <div class="ft-obs-section__title">
                <i class="ti ti-info-circle"></i> 1. Description du constat
              </div>
              <div class="ft-obs-grid ft-obs-grid--1">
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Intitulé du problème</label>
                  <input type="text" class="ft-obs-inp" v-model="obsFormXIV.intitule_probleme"
                    :disabled="isLocked"
                    placeholder="Ex. : Absence de rapprochement bancaire mensuel formalisé" />
                </div>
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Faits constatés / Observation</label>
                  <textarea class="ft-obs-ta" v-model="obsFormXIV.faits_constates" rows="4"
                    :disabled="isLocked"
                    placeholder="Sur les 6 mois examinés, aucun état de rapprochement bancaire signé n'a été produit…"></textarea>
                  <div class="ft-obs-hint">Indiquez l'échantillon, les preuves, etc.</div>
                </div>

                <!-- Tableau constats inline section 1 -->
                <div class="ft-obs-section ft-obs-section--constats" style="margin:0;border:none;background:#f8fafc;border-radius:8px;overflow:hidden">
                  <div class="ft-obs-section__title" style="border-radius:0">
                    <i class="ti ti-eye"></i> Constats
                    <span class="obs-badge-count">{{ obsData.constats.length }}</span>
                    <button v-if="!isLocked" class="obs-add" @click="obsData.constats.push({element_observe:'',conforme_referentiel:'',ecart_constate:'',risque_associe:'',preuve:''})">
                      <i class="ti ti-plus"></i> Constat
                    </button>
                  </div>
                  <div class="obs-table-wrap" style="margin:.5rem">
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
                          <td colspan="7" class="obs-ec">Aucun constat</td>
                        </tr>
                        <tr v-for="(c, ci) in obsData.constats" :key="ci"
                          :style="c.conforme_referentiel==='non'?'background:#fef2f2':c.conforme_referentiel==='oui'?'background:#f0fdf4':c.conforme_referentiel==='partiel'?'background:#fffbeb':''">
                          <td class="tc obs-n">{{ ci+1 }}</td>
                          <td><textarea class="obs-ta-sm" v-model="c.element_observe" rows="2" :disabled="isLocked"></textarea></td>
                          <td class="tc">
                            <select class="obs-sel-sm" v-model="c.conforme_referentiel" :disabled="isLocked">
                              <option value="">—</option>
                              <option value="oui">✅ Oui</option>
                              <option value="non">❌ Non</option>
                              <option value="partiel">⚠️ Partiel</option>
                            </select>
                          </td>
                          <td><textarea class="obs-ta-sm" v-model="c.ecart_constate" rows="2" :disabled="isLocked"></textarea></td>
                          <td><textarea class="obs-ta-sm" v-model="c.risque_associe" rows="2" :disabled="isLocked"></textarea></td>
                          <td><input class="obs-inp-sm" type="text" v-model="c.preuve" :disabled="isLocked" placeholder="Réf. photo, doc…"/></td>
                          <td v-if="!isLocked" class="tc">
                            <button class="obs-del" @click="obsData.constats.splice(ci,1)"><i class="ti ti-trash"></i></button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Critère / Référentiel</label>
                  <textarea class="ft-obs-ta" v-model="obsFormXIV.critere_referentiel" rows="3"
                    :disabled="isLocked"
                    placeholder="Ex. : Manuel des procédures financières (MPF-07) section 3.2.1…"></textarea>
                </div>
              </div>
            </div>

            <!-- ═══ SECTION 2 : CAUSES ═══ -->
            <div class="ft-obs-section">
              <div class="ft-obs-section__title">
                <i class="ti ti-list-check"></i> 2. Analyse du problème – Causes identifiées
              </div>
              <div class="ft-obs-causes-grid">
                <label v-for="cause in causesList" :key="cause.value" class="ft-obs-check">
                  <input type="checkbox" :value="cause.value" v-model="obsFormXIV.causes_selection" :disabled="isLocked" />
                  <span>{{ cause.label }}</span>
                </label>
                <div class="ft-obs-field ft-obs-field--full" style="margin-top: 8px;">
                  <label class="ft-obs-lbl">Autres causes (précisez)</label>
                  <input type="text" class="ft-obs-inp" v-model="obsFormXIV.causes_autres" :disabled="isLocked" placeholder="Décrivez ici…" />
                </div>
              </div>
            </div>

            <!-- ═══ SECTION 3 : CONSÉQUENCES ═══ -->
            <div class="ft-obs-section">
              <div class="ft-obs-section__title">
                <i class="ti ti-alert-triangle"></i> 3. Conséquences / Risques encourus
              </div>
              <div class="ft-obs-risks-table">
                <div v-for="risk in risksList" :key="risk.key" class="ft-obs-risk-item">
                  <label class="ft-obs-check">
                    <input type="checkbox" :value="risk.key" v-model="obsFormXIV.consequences_selection" :disabled="isLocked" />
                    <span class="risk-label" :style="{ color: risk.color }">{{ risk.label }}</span>
                  </label>
                </div>
              </div>
              <div class="ft-obs-field ft-obs-field--full" style="padding: 0 1rem 1rem;">
                <label class="ft-obs-lbl">Description des conséquences / risques</label>
                <textarea class="ft-obs-ta" v-model="obsFormXIV.consequences_description" rows="3"
                  :disabled="isLocked"
                  placeholder="Risque d'erreur dans la trésorerie, de fraude non détectée…"></textarea>
              </div>
            </div>

            <!-- ═══ SECTION 4 : SYNTHÈSE ═══ -->
            <div class="ft-obs-section">
              <div class="ft-obs-section__title">
                <i class="ti ti-chart-bar"></i> 4. Synthèse
              </div>
              <div class="ft-obs-grid ft-obs-grid--2" style="padding:.85rem 1rem .5rem;">
                <!-- Niveau de contrôle -->
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Niveau de contrôle</label>
                  <select class="ft-obs-sel-niveau" v-model="obsData.niveau_controle" :disabled="isLocked"
                    :style="niveauControleStyle(obsData.niveau_controle)">
                    <option value="">— Choisir —</option>
                    <option value="1_faible">1 – Faible</option>
                    <option value="2_moyen">2 – Moyen</option>
                    <option value="3_satisfaisant">3 – Satisfaisant</option>
                    <option value="4_bon">4 – Bon</option>
                    <option value="5_excellent">5 – Excellent</option>
                  </select>
                  <div v-if="obsData.niveau_controle" class="ft-obs-niveau-badge" :style="niveauBadgeStyle(obsData.niveau_controle)">
                    {{ niveauLabel(obsData.niveau_controle) }}
                  </div>
                </div>
                <!-- Niveau de synthèse -->
                <div class="ft-obs-field">
                  <label class="ft-obs-lbl">Niveau de synthèse</label>
                  <select class="ft-obs-sel-niveau" v-model="obsData.niveau_synthese" :disabled="isLocked"
                    :style="niveauSyntheseStyle(obsData.niveau_synthese)">
                    <option value="">— Choisir —</option>
                    <option value="conforme">✅ Conforme</option>
                    <option value="a_ameliorer">🔶 À améliorer</option>
                    <option value="insuffisant">🔴 Insuffisant</option>
                    <option value="critique">⛔ Critique</option>
                  </select>
                  <div v-if="obsData.niveau_synthese" class="ft-obs-niveau-badge" :style="niveauSyntheseBadgeStyle(obsData.niveau_synthese)">
                    {{ niveauSyntheseLabel(obsData.niveau_synthese) }}
                  </div>
                </div>
                <!-- Points forts -->
                <div class="ft-obs-field ft-obs-field--full">
                  <label class="ft-obs-lbl">Points forts</label>
                  <textarea class="ft-obs-ta" v-model="obsData.points_forts" :disabled="isLocked" rows="3" placeholder="Ce qui fonctionne bien…"></textarea>
                </div>
                <!-- Conclusion -->
                <div class="ft-obs-field ft-obs-field--full">
                  <label class="ft-obs-lbl">Conclusion</label>
                  <textarea class="ft-obs-ta" v-model="obsData.conclusion" :disabled="isLocked" rows="3" placeholder="Conclusion générale…"></textarea>
                </div>
              </div>
            </div>

            <!-- ═══ SECTION 5 : RECOMMANDATIONS ═══ -->
            <div class="ft-obs-section">
              <div class="ft-obs-section__title">
                <i class="ti ti-bulb"></i> 5. Recommandations de l'audit interne
                <span class="obs-badge-count" style="margin-left:.5rem">{{ obsData.recommandations.length }}</span>
                <button v-if="!isLocked" class="obs-add" style="margin-left:.5rem"
                  @click="obsData.recommandations.push({recommandation:'',responsable:'',date_prevue:'',commentaire_auditeur:''})">
                  <i class="ti ti-plus"></i> Recommandation
                </button>
              </div>
              <div class="obs-table-wrap" style="margin:.75rem;">
                <table class="obs-tbl">
                  <thead>
                    <tr>
                      <th class="tc" style="width:28px">N°</th>
                      <th>Recommandation</th>
                      <th style="width:140px">Responsable</th>
                      <th style="width:110px">Date prévue</th>
                      <th>Livrable</th>
                      <th>Commentaire de l'audité</th>
                      <th v-if="!isLocked" style="width:28px"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="!obsData.recommandations.length">
                      <td colspan="6" class="obs-ec">Aucune recommandation — cliquez sur "+ Recommandation" pour en ajouter</td>
                    </tr>
                    <tr v-for="(rec, ri) in obsData.recommandations" :key="ri">
                      <td class="tc obs-n">{{ ri + 1 }}</td>
                      <td><textarea class="obs-ta-sm" v-model="rec.recommandation" rows="2" :disabled="isLocked" placeholder="Mettre en place…"></textarea></td>
                      <td><input class="obs-inp-sm" type="text" v-model="rec.responsable" :disabled="isLocked" placeholder="Nom / Fonction"/></td>
                      <td><input class="obs-inp-sm" type="date" v-model="rec.date_prevue" :disabled="isLocked"/></td>
                      <td><textarea class="obs-ta-sm" v-model="rec.commentaire_auditeur" rows="2" :disabled="isLocked" placeholder="Livrables attendus…"></textarea></td>
                      <td><textarea class="obs-ta-sm" v-model="rec.commentaire_auditeur" rows="2" :disabled="isLocked" placeholder="Commentaire de l'auditeur…"></textarea></td>
                      <td v-if="!isLocked" class="tc">
                        <button class="obs-del" @click="obsData.recommandations.splice(ri,1)"><i class="ti ti-trash"></i></button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- ═══ SECTION 6 : MÉTADONNÉES ═══ -->
            

          </div><!-- /ft-obs-body -->

          <div class="ft-obs-footer">
            <button class="ft-btn ft-btn--ghost" @click="fermerObsVue">Retour</button>
            <button v-if="!isLocked && !obsLoadingBD" class="ft-obs-save-btn" :disabled="obsSaving" @click="sauvegarderObservation">
              <span v-if="obsSaving" class="ft-spin"></span>
              <i v-else class="ti ti-device-floppy"></i>
              {{ obsSaving ? 'Sauvegarde…' : "Enregistrer l'observation" }}
            </button>
          </div>
        </div><!-- /ft-obs-view -->

        <!-- ◈ VUE CONSTAT ──────────────────────────────────────── -->
        <div v-if="constatActif && !ficheActif && !obsVueActif" class="ft-constat-view">
          <div class="ft-constat-back">
            <button class="ft-btn ft-btn--ghost ft-btn--sm" @click="constatActif = null"><i class="ti ti-arrow-left"></i> Retour</button>
            <span class="ft-constat-ref">{{ constatActif.testRef }} — Constat &amp; Preuve</span>
          </div>
          <div class="ft-constat-body">
            <div class="fc-sect">
              <label class="fc-lbl">Résultat du test</label>
              <select v-if="!isLocked" class="fc-sel"
                :value="getResultat(constatActif.objNum, constatActif.testRef)"
                @change="setResultat(constatActif.objNum, constatActif.testRef, ($event.target as HTMLSelectElement).value)">
                <option value="">— Sélectionner —</option>
                <option value="conforme">✅ Conforme</option><option value="ecart">⚠️ Écart</option>
                <option value="nc">❌ Non conforme</option><option value="na">N/A</option>
              </select>
              <span v-else class="ft-result-pill" :class="`frp--${getResultat(constatActif.objNum, constatActif.testRef)}`">{{ getResultat(constatActif.objNum, constatActif.testRef) || '—' }}</span>
            </div>
            <div class="fc-foot">
              <button class="ft-btn ft-btn--ghost" @click="constatActif = null">Fermer</button>
              <button v-if="!isLocked" class="ft-btn ft-btn--save" :disabled="processing" @click="submit(true)"><i class="ti ti-device-floppy"></i> Enregistrer</button>
            </div>
          </div>
        </div>

      </div><!-- /ft-main -->
    </div>

    <!-- ══ MODAL OBSERVATION XIV ════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="om-fade">
        <div v-if="obsActif" class="om-overlay obs-overlay" @click.self="obsActif = false">
          <div class="obs-dialog">
            <div class="obs-hd">
              <div class="obs-hd__left">
                <span class="obs-num">XIV</span>
                <div>
                  <div class="obs-title">Fiche d'Observation Directe</div>
                  <div class="obs-sub" v-if="ficheActif">{{ ficheActif.testRef }} · {{ ficheActif.objNum }}</div>
                </div>
              </div>
              <div class="obs-hd__right">
                <span v-if="obsLoadingBD" class="obs-loading"><span class="ft-spin"></span> Chargement…</span>
                <button v-if="!isLocked && !obsLoadingBD" class="obs-btn-save" :disabled="obsSaving" @click="sauvegarderObservation">
                  <span v-if="obsSaving" class="ft-spin"></span><i v-else class="ti ti-device-floppy"></i> Enregistrer
                </button>
                <button class="obs-btn-close" @click="obsActif = false"><i class="ti ti-x"></i></button>
              </div>
            </div>

            <div v-if="obsLoadingBD" class="obs-body obs-body--loading">
              <div class="obs-loading-center"><span class="ft-spin ft-spin--lg"></span><p>Chargement des données de l'observation…</p></div>
            </div>

            <div v-else class="obs-body">
              <!-- En-tête -->
              <div class="obs-card">
                <h3 class="obs-card__title"><i class="ti ti-info-circle"></i> En-tête</h3>
                <div class="obs-g3">
                  <div class="obs-f"><label class="obs-lbl">Date d'observation</label><input type="date" class="obs-inp" v-model="obsData.date_observation" :disabled="isLocked"/></div>
                  <div class="obs-f"><label class="obs-lbl">Heure début</label><input type="time" class="obs-inp" v-model="obsData.heure_debut" :disabled="isLocked"/></div>
                  <div class="obs-f"><label class="obs-lbl">Heure fin</label><input type="time" class="obs-inp" v-model="obsData.heure_fin" :disabled="isLocked"/></div>
                  <div class="obs-f"><label class="obs-lbl">Auditeur</label><input type="text" class="obs-inp" v-model="obsData.auditeur" :disabled="isLocked" :placeholder="props.auditeurNom"/></div>
                  <div class="obs-f"><label class="obs-lbl">Localisation</label><input type="text" class="obs-inp" v-model="obsData.localisation" :disabled="isLocked" placeholder="Ex. : Magasin central"/></div>
                  <div class="obs-f obs-full"><label class="obs-lbl">Interlocuteur(s) présent(s)</label><input type="text" class="obs-inp" v-model="obsData.interlocuteurs_presents" :disabled="isLocked" placeholder="Noms, fonctions…"/></div>
                </div>
              </div>
              <!-- Préparation -->
              <div class="obs-card">
                <h3 class="obs-card__title"><i class="ti ti-clipboard"></i> Préparation de l'Observation</h3>
                <div class="obs-g2">
                  <div class="obs-f obs-full"><label class="obs-lbl">Objectif d'audit visé</label><textarea class="obs-ta" v-model="obsData.objectif_audit" :disabled="isLocked" rows="2" placeholder="S'assurer que…"></textarea></div>
                  <div class="obs-f obs-full"><label class="obs-lbl">Tâche / Local à observer</label><textarea class="obs-ta" v-model="obsData.tache_local_observer" :disabled="isLocked" rows="2" placeholder="Description précise…"></textarea></div>
                  <div class="obs-f obs-full"><label class="obs-lbl">Éléments à vérifier</label><textarea class="obs-ta" v-model="obsData.elements_verifier" :disabled="isLocked" rows="2" placeholder="Liste des éléments…"></textarea></div>
                  <div class="obs-f obs-full"><label class="obs-lbl">Pièces attendues</label><textarea class="obs-ta" v-model="obsData.pieces_attendues" :disabled="isLocked" rows="2" placeholder="Documents, registres…"></textarea></div>
                </div>
              </div>
              <!-- Causes -->
              <div class="obs-card">
                <h3 class="obs-card__title"><i class="ti ti-list-check"></i> Causes identifiées</h3>
                <div class="ft-obs-causes-grid" style="padding: 0.5rem;">
                  <label v-for="cause in causesList" :key="cause.value" class="ft-obs-check">
                    <input type="checkbox" :value="cause.value" v-model="obsFormXIV.causes_selection" :disabled="isLocked" />
                    <span>{{ cause.label }}</span>
                  </label>
                </div>
                <div style="padding: 0.5rem;">
                  <label class="obs-lbl">Autres causes</label>
                  <input type="text" class="obs-inp" v-model="obsFormXIV.causes_autres" :disabled="isLocked" placeholder="Décrivez ici…" />
                </div>
              </div>
              <!-- Conséquences -->
              <div class="obs-card">
                <h3 class="obs-card__title"><i class="ti ti-alert-triangle"></i> Conséquences / Risques</h3>
                <div class="ft-obs-risks-table" style="padding: 0.5rem;">
                  <div v-for="risk in risksList" :key="risk.key" class="ft-obs-risk-item">
                    <label class="ft-obs-check">
                      <input type="checkbox" :value="risk.key" v-model="obsFormXIV.consequences_selection" :disabled="isLocked" />
                      <span class="risk-label" :style="{ color: risk.color }">{{ risk.label }}</span>
                    </label>
                  </div>
                </div>
                <div style="padding: 0.5rem;">
                  <label class="obs-lbl">Description</label>
                  <textarea class="obs-ta" v-model="obsFormXIV.consequences_description" :disabled="isLocked" rows="2" placeholder="Risques…"></textarea>
                </div>
              </div>
              <!-- Synthèse avec niveaux -->
              <div class="obs-card">
                <h3 class="obs-card__title"><i class="ti ti-chart-bar"></i> Synthèse</h3>
                <div class="obs-g2" style="margin-bottom:.5rem">
                  <div class="obs-f">
                    <label class="obs-lbl">Niveau de contrôle</label>
                    <select class="obs-inp ft-obs-sel-niveau" v-model="obsData.niveau_controle" :disabled="isLocked"
                      :style="niveauControleStyle(obsData.niveau_controle)">
                      <option value="">— Choisir —</option>
                      <option value="1_faible">1 – Faible</option>
                      <option value="2_moyen">2 – Moyen</option>
                      <option value="3_satisfaisant">3 – Satisfaisant</option>
                      <option value="4_bon">4 – Bon</option>
                      <option value="5_excellent">5 – Excellent</option>
                    </select>
                    <div v-if="obsData.niveau_controle" class="ft-obs-niveau-badge" :style="niveauBadgeStyle(obsData.niveau_controle)">{{ niveauLabel(obsData.niveau_controle) }}</div>
                  </div>
                  <div class="obs-f">
                    <label class="obs-lbl">Niveau de synthèse</label>
                    <select class="obs-inp ft-obs-sel-niveau" v-model="obsData.niveau_synthese" :disabled="isLocked"
                      :style="niveauSyntheseStyle(obsData.niveau_synthese)">
                      <option value="">— Choisir —</option>
                      <option value="conforme">✅ Conforme</option>
                      <option value="a_ameliorer">🔶 À améliorer</option>
                      <option value="insuffisant">🔴 Insuffisant</option>
                      <option value="critique">⛔ Critique</option>
                    </select>
                    <div v-if="obsData.niveau_synthese" class="ft-obs-niveau-badge" :style="niveauSyntheseBadgeStyle(obsData.niveau_synthese)">{{ niveauSyntheseLabel(obsData.niveau_synthese) }}</div>
                  </div>
                  <div class="obs-f"><label class="obs-lbl">Points forts</label><textarea class="obs-ta" v-model="obsData.points_forts" :disabled="isLocked" rows="3" placeholder="Ce qui fonctionne bien…"></textarea></div>
                  <div class="obs-f obs-full"><label class="obs-lbl">Conclusion</label><textarea class="obs-ta" v-model="obsData.conclusion" :disabled="isLocked" rows="3" placeholder="Conclusion générale…"></textarea></div>
                </div>
              </div>
              <!-- Constats -->
              <div class="obs-card">
                <div class="obs-card__hd">
                  <h3 class="obs-card__title"><i class="ti ti-eye"></i> Constats d'Observation</h3>
                  <span class="obs-badge-count">{{ obsData.constats.length }}</span>
                  <button v-if="!isLocked" class="obs-add" @click="obsData.constats.push({element_observe:'',conforme_referentiel:'',ecart_constate:'',risque_associe:'',preuve:''})"><i class="ti ti-plus"></i> Constat</button>
                </div>
                <div class="obs-table-wrap">
                  <table class="obs-tbl">
                    <thead><tr><th class="tc" style="width:28px">N°</th><th>Élément observé</th><th class="tc" style="width:90px">Conforme?</th><th>Écart constaté</th><th>Risque associé</th><th style="width:110px">Preuve</th><th v-if="!isLocked" style="width:28px"></th></tr></thead>
                    <tbody>
                      <tr v-if="!obsData.constats.length"><td colspan="7" class="obs-ec">Aucun constat</td></tr>
                      <tr v-for="(c, ci) in obsData.constats" :key="ci"
                        :style="c.conforme_referentiel==='non'?'background:#fef2f2':c.conforme_referentiel==='oui'?'background:#f0fdf4':c.conforme_referentiel==='partiel'?'background:#fffbeb':''">
                        <td class="tc obs-n">{{ ci+1 }}</td>
                        <td><textarea class="obs-ta-sm" v-model="c.element_observe" rows="2" :disabled="isLocked"></textarea></td>
                        <td class="tc"><select class="obs-sel-sm" v-model="c.conforme_referentiel" :disabled="isLocked"><option value="">—</option><option value="oui">✅ Oui</option><option value="non">❌ Non</option><option value="partiel">⚠️ Partiel</option></select></td>
                        <td><textarea class="obs-ta-sm" v-model="c.ecart_constate" rows="2" :disabled="isLocked"></textarea></td>
                        <td><textarea class="obs-ta-sm" v-model="c.risque_associe" rows="2" :disabled="isLocked"></textarea></td>
                        <td><input class="obs-inp-sm" type="text" v-model="c.preuve" :disabled="isLocked" placeholder="Réf. photo, doc…"/></td>
                        <td v-if="!isLocked" class="tc"><button class="obs-del" @click="obsData.constats.splice(ci,1)"><i class="ti ti-trash"></i></button></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <!-- Recommandations tableau -->
              <div class="obs-card">
                <div class="obs-card__hd">
                  <h3 class="obs-card__title"><i class="ti ti-bulb"></i> Recommandations</h3>
                  <span class="obs-badge-count">{{ obsData.recommandations.length }}</span>
                  <button v-if="!isLocked" class="obs-add"
                    @click="obsData.recommandations.push({recommandation:'',responsable:'',date_prevue:'',commentaire_auditeur:''})">
                    <i class="ti ti-plus"></i> Recommandation
                  </button>
                </div>
                <div class="obs-table-wrap" style="margin:.25rem .5rem .5rem;">
                  <table class="obs-tbl">
                    <thead>
                      <tr>
                        <th class="tc" style="width:28px">N°</th>
                        <th>Recommandation</th>
                        <th style="width:130px">Responsable</th>
                        <th style="width:100px">Date prévue</th>
                        <th>Commentaire auditeur</th>
                        <th v-if="!isLocked" style="width:28px"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="!obsData.recommandations.length">
                        <td colspan="6" class="obs-ec">Aucune recommandation</td>
                      </tr>
                      <tr v-for="(rec, ri) in obsData.recommandations" :key="ri">
                        <td class="tc obs-n">{{ ri + 1 }}</td>
                        <td><textarea class="obs-ta-sm" v-model="rec.recommandation" rows="2" :disabled="isLocked" placeholder="Mettre en place…"></textarea></td>
                        <td><input class="obs-inp-sm" type="text" v-model="rec.responsable" :disabled="isLocked" placeholder="Nom / Fonction"/></td>
                        <td><input class="obs-inp-sm" type="date" v-model="rec.date_prevue" :disabled="isLocked"/></td>
                        <td><textarea class="obs-ta-sm" v-model="rec.commentaire_auditeur" rows="2" :disabled="isLocked" placeholder="Commentaire…"></textarea></td>
                        <td v-if="!isLocked" class="tc"><button class="obs-del" @click="obsData.recommandations.splice(ri,1)"><i class="ti ti-trash"></i></button></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <div class="obs-footer">
              <button class="ft-btn ft-btn--ghost" @click="obsActif = false">Fermer</button>
              <button v-if="!isLocked && !obsLoadingBD" class="obs-btn-save" :disabled="obsSaving" @click="sauvegarderObservation">
                <span v-if="obsSaving" class="ft-spin"></span><i v-else class="ti ti-device-floppy"></i> Enregistrer
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ══ MODAL CHOIX OUTILS ════════════════════════════════════ -->
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
                    <span v-if="om.procIdx !== null" class="om-ctx om-ctx--proc">Procédure {{ (om.procIdx ?? 0) + 1 }}</span>
                  </div>
                </div>
              </div>
              <button class="ft-ib" @click="om.visible = false"><i class="ti ti-x"></i></button>
            </div>
            <div class="om-selbar">
              <div class="om-tags">
                <span v-for="code in om.selected" :key="code" class="om-tag" :style="`--ot:${outilColor(code)}`">
                  {{ code }}<button @click="omToggle(code)">×</button>
                </span>
              </div>
              <button v-if="om.selected.length" class="om-clear" @click="om.selected = []">Effacer</button>
            </div>
            <div class="om-body">
              <div class="om-grid">
                <button v-for="outil in props.outilsIfaci" :key="outil.code"
                  class="om-card" :class="om.selected.includes(outil.code) ? 'om-card--sel' : ''"
                  @click="omToggle(outil.code)">
                  <div class="om-card__num" :style="`background:${outil.color}`">{{ outil.code }}</div>
                  <div class="om-card__body"><span class="om-card__lbl">{{ outil.label }}</span></div>
                </button>
              </div>
            </div>
            <div class="om-ft">
              <button class="ft-btn ft-btn--ghost" @click="om.visible = false">Annuler</button>
              <button class="om-confirm" :disabled="!om.selected.length" @click="omConfirmer">Ouvrir</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ══ TOAST ════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="toast-pop">
        <div v-if="toast.show" class="ft-toast" :class="`ft-toast--${toast.type}`">
          <i :class="toast.type === 'success' ? 'ti ti-circle-check' : 'ti ti-alert-circle'"></i>
          {{ toast.msg }}
          <button class="ft-toast__x" @click="toast.show = false"><i class="ti ti-x"></i></button>
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
  missionContext?: { mission_id?: number; assignment_id?: number; mission_libelle?: string; code_mission?: string }
  backUrl?: string
  urlStore?: string
  urlUpdate?: string
  urlSoumettre?: string
  urlValider?: string
  urlSaveOutil?: string
  urlLoadOutil?: string
  outilsParTest?: Record<string, any[]>
}>(), {
  phaseAuditeurs: () => [],
  outilsIfaci: () => [],
  processus: () => [],
  risquesMission: () => [],
  rciLignes: () => [],
  missionContext: () => ({}),
  programmeData: () => ({ found: false, objectifs: [], total_objectifs: 0, total_tests: 0 }),
  outilsParTest: () => ({}),
})

// ─── LISTES STATIQUES (XIV) ─────────────────────────────────────
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
  { key: 'financier',    label: 'Financier',                     color: '#dc2626' },
  { key: 'operationnel', label: 'Opérationnel',                  color: '#d97706' },
  { key: 'juridique',    label: 'Juridique / réglementaire',     color: '#7c3aed' },
  { key: 'reputationnel',label: 'Réputationnel',                 color: '#0c4e6e' },
  { key: 'fraude',       label: 'Fraude',                        color: '#b91c1c' },
  { key: 'qualite_info', label: "Qualité de l'information",      color: '#15803d' },
  { key: 'continuite',   label: "Continuité d'activité",         color: '#6b21a5' },
]

// ─── STATE ──────────────────────────────────────────────────────
const form = reactive<any>({
  id: null, code: '', validation_status: 'draft', validation_note: '',
  ...(props.form ?? {}),
})
const dynUrls = reactive({ update: props.urlUpdate ?? null, soumettre: props.urlSoumettre ?? null, valider: props.urlValider ?? null })
const processing   = ref(false)
const toast        = ref({ show: false, type: 'success', msg: '' })
let _tt: ReturnType<typeof setTimeout> | null = null

const ficheActif   = ref<any>(null)
const constatActif = ref<any>(null)
const obsActif     = ref(false)
const obsSaving    = ref(false)
const obsLoadingBD = ref(false)
const obsVueActif   = ref(false)
const obsVueContext = ref<any>(null)

// ─── FORMULAIRE XIV enrichi ─────────────────────────────────────
const obsFormXIV = reactive<any>({
  intitule_probleme:        '',
  faits_constates:          '',
  critere_referentiel:      '',
  causes_selection:         [] as string[],
  causes_autres:            '',
  consequences_selection:   [] as string[],
  consequences_description: '',
  recommandation:           '',
  date_observation:         '',
  heure_debut:              '',
  heure_fin:                '',
  auditeur:                 props.auditeurNom ?? '',
  localisation:             '',
  interlocuteurs:           '',
})

// ─── RÉSULTATS ──────────────────────────────────────────────────
const resultatsMap = reactive<Record<string, { resultat: string; constat: string; preuve: string }>>({})
if (props.form?.resultats) {
  const arr = Array.isArray(props.form.resultats)
    ? props.form.resultats
    : (() => { try { return JSON.parse(props.form.resultats) } catch { return [] } })()
  arr.forEach((r: any) => {
    resultatsMap[r.obj_num + '::' + r.test_ref] = { resultat: r.resultat ?? '', constat: r.constat ?? '', preuve: r.preuve ?? '' }
  })
}
const subTestResultsMap = reactive<Record<string, any>>({})

// ─── OUTILS ─────────────────────────────────────────────────────
const outilsProcsMap = reactive<Record<string, string[]>>({})
const outilsDataMap  = reactive<Record<string, any>>({})

if (props.outilsParTest) {
  Object.entries(props.outilsParTest).forEach(([testKey, outils]) => {
    ;(outils as any[]).forEach((ot: any) => {
      const parts  = testKey.split('::')
      const objNum = parts[0]
      const testRef = parts.slice(1).join('::')
      const mapKey  = pKey(objNum, testRef, ot.proc_idx ?? 0)
      if (!outilsProcsMap[mapKey]) outilsProcsMap[mapKey] = []
      if (!outilsProcsMap[mapKey].includes(ot.outil_code)) outilsProcsMap[mapKey].push(ot.outil_code)
    })
  })
}
if (props.form?.outils_data) {
  const arr = Array.isArray(props.form.outils_data)
    ? props.form.outils_data
    : (() => { try { return JSON.parse(props.form.outils_data) } catch { return [] } })()
  arr.forEach((item: any) => {
    if (item?._key && item?._code) {
      outilsDataMap[item._key] = item
      const pk = item._key.split('::').slice(1).join('::')
      if (!outilsProcsMap[pk]) outilsProcsMap[pk] = []
      if (!outilsProcsMap[pk].includes(item._code)) outilsProcsMap[pk].push(item._code)
    }
  })
}

// ─── OBS DATA ───────────────────────────────────────────────────
const obsData = reactive<any>({
  date_observation: '', heure_debut: '', heure_fin: '',
  auditeur: props.auditeurNom ?? '', localisation: '', interlocuteurs_presents: '',
  objectif_audit: '', tache_local_observer: '', elements_verifier: '', pieces_attendues: '',
  points_forts: '', conclusion: '', constats: [],
  niveau_controle: '', niveau_synthese: '',
  recommandations: [] as Array<{ recommandation: string; responsable: string; date_prevue: string; commentaire_auditeur: string }>,
})

// Modal outils
const om = reactive({ visible: false, selected: [] as string[], testRef: '', procIdx: null as number | null, objNum: '', obj: null as any, test: null as any, oi: 0, ti: 0 })

// ─── COMPUTED ─────────────────────────────────────────────────────
const canManage      = computed(() => ['DM', 'CM'].includes(props.auditorRole ?? ''))
const isLocked       = computed(() => form.validation_status === 'validated' || (form.validation_status === 'in_review' && !canManage.value))
const missionLibelle = computed(() => props.mission?.libelle ?? props.missionContext?.mission_libelle ?? '')
const ficheSourcesDefaut = computed(() => {
  if (!ficheActif.value) return []
  const sources: string[] = []
  if (ficheActif.value.obj?.objectif) sources.push(ficheActif.value.obj.objectif)
  return sources.length ? sources : ['Documents de référence', 'Entretiens avec les responsables']
})
const obsXIVLiee = computed(() => {
  if (!ficheActif.value) return false
  return getOutilsPourTest(ficheActif.value.objNum, ficheActif.value.testRef).some(o => o.outil_code === 'XIV')
})

// ─── HELPERS ──────────────────────────────────────────────────────
function vstLbl(s: string) { return { draft: 'Brouillon', in_review: 'En attente', validated: 'Validé ✓' }[s] ?? s }
function stLbl(s: string)  { return { draft: 'Brouillon', in_review: 'En révision', validated: 'Validé', rejected: 'Rejeté' }[s] ?? s }
function resultatLabel(v: string) { return { conforme: '✅ Conforme', ecart: '⚠️ Écart', nc: '❌ Non conforme', na: 'N/A' }[v] ?? v }
function tRef(test: any, oi: number, ti: number) { return test.ref || ('T' + (oi+1) + '.' + (ti+1)) }
function pKey(on: string, tr: string, pi: number) { return on + '::' + tr + '::' + pi }
function getOutilsForProc(on: string, tr: string, pi: number): string[] { return outilsProcsMap[pKey(on, tr, pi)] ?? [] }
function testHasAnyOutil(on: string, tr: string): boolean { return Object.keys(outilsProcsMap).some(k => k.startsWith(on + '::' + tr + '::') && (outilsProcsMap[k] ?? []).length > 0) }
function testHasOutil(on: string, tr: string, code: string): boolean { return Object.entries(outilsProcsMap).some(([k,v]) => k.startsWith(on + '::' + tr + '::') && v.includes(code)) }
function outilColor(code: string) { return props.outilsIfaci?.find(o => o.code === code)?.color ?? '#374151' }
function rk(on: string, tr: string) { return on + '::' + tr }
function formatDate(d?: string | null) { return d ? new Date(d).toLocaleDateString('fr-FR') : '' }
function csrf() { return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '' }
function getOutilsPourTest(objNum: string, testRef: string): any[] {
  return (props.outilsParTest?.[objNum + '::' + testRef] ?? [])
}
function getObjectifTexte(): string {
  if (ficheActif.value?.obj?.objectif) return ficheActif.value.obj.objectif
  if (ficheActif.value?.obj?.libelle)  return ficheActif.value.obj.libelle
  return ''
}

// ─── HELPERS NIVEAUX ─────────────────────────────────────────────
const niveauControleColors: Record<string, { bg: string; color: string; border: string }> = {
  '1_faible':       { bg: '#fee2e2', color: '#dc2626', border: '#fca5a5' },
  '2_moyen':        { bg: '#fef3c7', color: '#b45309', border: '#fcd34d' },
  '3_satisfaisant': { bg: '#fefce8', color: '#854d0e', border: '#fde68a' },
  '4_bon':          { bg: '#dcfce7', color: '#166534', border: '#86efac' },
  '5_excellent':    { bg: '#d1fae5', color: '#065f46', border: '#6ee7b7' },
}
const niveauSyntheseColors: Record<string, { bg: string; color: string; border: string }> = {
  'conforme':    { bg: '#d1fae5', color: '#065f46', border: '#6ee7b7' },
  'a_ameliorer': { bg: '#fef3c7', color: '#92400e', border: '#fcd34d' },
  'insuffisant': { bg: '#fee2e2', color: '#dc2626', border: '#fca5a5' },
  'critique':    { bg: '#fce7f3', color: '#9d174d', border: '#f9a8d4' },
}
const niveauLabels: Record<string, string> = {
  '1_faible': '1 – Faible', '2_moyen': '2 – Moyen', '3_satisfaisant': '3 – Satisfaisant',
  '4_bon': '4 – Bon', '5_excellent': '5 – Excellent',
}
const niveauSyntheseLabels: Record<string, string> = {
  'conforme': '✅ Conforme', 'a_ameliorer': '🔶 À améliorer',
  'insuffisant': '🔴 Insuffisant', 'critique': '⛔ Critique',
}
function niveauLabel(v: string)         { return niveauLabels[v] ?? v }
function niveauSyntheseLabel(v: string) { return niveauSyntheseLabels[v] ?? v }
function niveauControleStyle(v: string) {
  const c = niveauControleColors[v]; if (!c) return {}
  return { background: c.bg, color: c.color, borderColor: c.border, fontWeight: '600' }
}
function niveauBadgeStyle(v: string) {
  const c = niveauControleColors[v]; if (!c) return {}
  return { background: c.bg, color: c.color, border: `1px solid ${c.border}` }
}
function niveauSyntheseStyle(v: string) {
  const c = niveauSyntheseColors[v]; if (!c) return {}
  return { background: c.bg, color: c.color, borderColor: c.border, fontWeight: '600' }
}
function niveauSyntheseBadgeStyle(v: string) {
  const c = niveauSyntheseColors[v]; if (!c) return {}
  return { background: c.bg, color: c.color, border: `1px solid ${c.border}` }
}

// ─── RÉSULTATS ───────────────────────────────────────────────────
function getResultat(on: string, tr: string) { return resultatsMap[rk(on,tr)]?.resultat ?? '' }
function setResultat(on: string, tr: string, v: string) { if (!resultatsMap[rk(on,tr)]) resultatsMap[rk(on,tr)] = {resultat:'',constat:'',preuve:''}; resultatsMap[rk(on,tr)].resultat = v }
function getConstat(on: string, tr: string)  { return resultatsMap[rk(on,tr)]?.constat ?? '' }
function setConstat(on: string, tr: string, v: string)  { if (!resultatsMap[rk(on,tr)]) resultatsMap[rk(on,tr)] = {resultat:'',constat:'',preuve:''}; resultatsMap[rk(on,tr)].constat = v }
function getPreuve(on: string, tr: string)   { return resultatsMap[rk(on,tr)]?.preuve ?? '' }
function setPreuve(on: string, tr: string, v: string)   { if (!resultatsMap[rk(on,tr)]) resultatsMap[rk(on,tr)] = {resultat:'',constat:'',preuve:''}; resultatsMap[rk(on,tr)].preuve = v }

// ─── RÉINITIALISATION XIV ─────────────────────────────────────────
function resetObsFormXIV() {
  Object.assign(obsFormXIV, {
    intitule_probleme: '', faits_constates: '', critere_referentiel: '',
    causes_selection: [], causes_autres: '',
    consequences_selection: [], consequences_description: '',
    recommandation: '', date_observation: '', heure_debut: '', heure_fin: '',
    auditeur: props.auditeurNom ?? '', localisation: '', interlocuteurs: '',
  })
  Object.assign(obsData, {
    date_observation: '', heure_debut: '', heure_fin: '',
    auditeur: props.auditeurNom ?? '', localisation: '', interlocuteurs_presents: '',
    objectif_audit: '', tache_local_observer: '', elements_verifier: '', pieces_attendues: '',
    points_forts: '', conclusion: '', constats: [],
    niveau_controle: '', niveau_synthese: '',
    recommandations: [],
  })
}

// ─── NAVIGATION ──────────────────────────────────────────────────
function ouvrirFiche(obj: any, test: any, oi: number, ti: number) {
  const testRef = tRef(test, oi, ti)
  const ex = resultatsMap[rk(obj.num, testRef)]
  ficheActif.value = {
    obj, test, objNum: obj.num, testRef, oi, ti,
    date: new Date().toISOString().slice(0,10),
    sourceComplementaire: '', referentiel: '',
    faits: ex?.constat ?? '', causes: '', consequences: '',
    conclusions: '', recommandations: '', procConstats: {},
  }
  obsActif.value = false
}

function ouvrirObsDirecte(obj: any, test: any, oi: number, ti: number) {
  const testRef = tRef(test, oi, ti)
  obsVueContext.value = { obj, test, objNum: obj.num, testRef, oi, ti, fromFiche: false }
  resetObsFormXIV()
  obsFormXIV.auditeur      = props.auditeurNom ?? ''
  obsData.objectif_audit   = obj.objectif ?? obj.libelle ?? ''
  obsVueActif.value = true
  ficheActif.value  = null
  if (testHasOutil(obj.num, testRef, 'XIV') && props.urlLoadOutil && form.id) {
    _chargerObsVueBD(obj.num, testRef)
  }
}

function ouvrirObsDepuisFiche() {
  if (!ficheActif.value) return
  const { obj, test, objNum, testRef, oi, ti } = ficheActif.value
  obsVueContext.value = { obj, test, objNum, testRef, oi, ti, fromFiche: true }
  resetObsFormXIV()
  obsFormXIV.auditeur    = props.auditeurNom ?? ''
  obsData.objectif_audit = getObjectifTexte()
  if (testHasOutil(objNum, testRef, 'XIV') && props.urlLoadOutil && form.id) {
    _chargerObsVueBD(objNum, testRef)
  }
  obsVueActif.value = true
}

function fermerObsVue() {
  const fromFiche = obsVueContext.value?.fromFiche
  const ctx       = obsVueContext.value
  obsVueActif.value = false
  if (fromFiche && ctx) ouvrirFiche(ctx.obj, ctx.test, ctx.oi, ctx.ti)
  obsVueContext.value = null
}

function ouvrirObservation() {
  if (ficheActif.value) {
    obsData.objectif_audit = getObjectifTexte()
    if (obsXIVLiee.value && props.urlLoadOutil && form.id) chargerObservationBD()
  }
  obsActif.value = true
}

// ─── CHARGEMENT BD ───────────────────────────────────────────────
function _hydrateFromRecord(r: any, d: any) {
  Object.assign(obsData, {
    date_observation:    r.date_observation ?? '',
    heure_debut:         r.heure_debut ?? '',
    heure_fin:           r.heure_fin ?? '',
    auditeur:            r.auditeur ?? props.auditeurNom ?? '',
    localisation:        r.localisation ?? '',
    interlocuteurs_presents: r.interlocuteurs_presents ?? '',
    objectif_audit:      r.objectif_audit ?? obsData.objectif_audit,
    tache_local_observer: r.tache_local_observer ?? '',
    elements_verifier:   r.elements_verifier ?? '',
    pieces_attendues:    r.pieces_attendues ?? '',
    points_forts:        r.points_forts ?? '',
    conclusion:          r.conclusion ?? '',
    constats:            d.children?.outil_observation_constats ?? [],
    niveau_controle:     r.niveau_controle ?? '',
    niveau_synthese:     r.niveau_synthese ?? '',
    recommandations:     d.children?.outil_observation_recommandations ?? [],
  })
  Object.assign(obsFormXIV, {
    intitule_probleme:        r.intitule_probleme ?? '',
    faits_constates:          r.faits_constates ?? '',
    critere_referentiel:      r.critere_referentiel ?? '',
    causes_selection:         (() => { try { return JSON.parse(r.causes_json ?? '[]') } catch { return [] } })(),
    causes_autres:            r.causes_autres ?? '',
    consequences_selection:   (() => { try { return JSON.parse(r.consequences_json ?? '[]') } catch { return [] } })(),
    consequences_description: r.consequences_description ?? '',
    recommandation:           r.recommandation ?? '',
    date_observation:         r.date_observation ?? '',
    heure_debut:              r.heure_debut ?? '',
    heure_fin:                r.heure_fin ?? '',
    auditeur:                 r.auditeur ?? props.auditeurNom ?? '',
    localisation:             r.localisation ?? '',
    interlocuteurs:           r.interlocuteurs_presents ?? '',
  })
}

async function _chargerObsVueBD(objNum: string, testRef: string) {
  if (!props.urlLoadOutil || !form.id) return
  obsLoadingBD.value = true
  try {
    const params = new URLSearchParams({ outil_code: 'XIV', procedure_code: testRef, test_ref: testRef, obj_num: objNum, proc_idx: '0' })
    const res = await fetch(props.urlLoadOutil + '?' + params.toString(), { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() } })
    const d = await res.json()
    if (d.success && d.found && d.record) _hydrateFromRecord(d.record, d)
  } catch(e) { console.warn('Chargement obs XIV:', e) }
  finally { obsLoadingBD.value = false }
}

async function chargerObservationBD() {
  if (!props.urlLoadOutil || !form.id || !ficheActif.value) return
  obsLoadingBD.value = true
  try {
    const params = new URLSearchParams({ outil_code: 'XIV', procedure_code: ficheActif.value.testRef ?? '', test_ref: ficheActif.value.testRef ?? '', proc_idx: '0' })
    const res = await fetch(props.urlLoadOutil + '?' + params.toString(), { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() } })
    const d = await res.json()
    if (d.success && d.found && d.record) _hydrateFromRecord(d.record, d)
  } catch(e) { console.warn('Chargement obs XIV:', e) }
  finally { obsLoadingBD.value = false }
}

// ─── SAUVEGARDE XIV ──────────────────────────────────────────────
async function sauvegarderObservation() {
  const url = props.urlSaveOutil
  if (!url || !form.id) {
    const ctx = obsVueContext.value ?? ficheActif.value
    if (ctx && obsData.conclusion) setConstat(ctx.objNum, ctx.testRef, obsData.conclusion)
    showToast('success', 'Observation sauvegardée localement.')
    return
  }
  obsSaving.value = true
  try {
    const ctx = obsVueContext.value ?? ficheActif.value
    const payload = {
      outil_code: 'XIV',
      procedure_code: ctx?.testRef ?? '',
      test_ref:       ctx?.testRef ?? '',
      obj_num:        ctx?.objNum  ?? '',
      proc_idx:       0,
      data: {
        date_observation:         obsFormXIV.date_observation,
        heure_debut:              obsFormXIV.heure_debut,
        heure_fin:                obsFormXIV.heure_fin,
        auditeur:                 obsFormXIV.auditeur,
        localisation:             obsFormXIV.localisation,
        interlocuteurs_presents:  obsFormXIV.interlocuteurs,
        intitule_probleme:        obsFormXIV.intitule_probleme,
        faits_constates:          obsFormXIV.faits_constates,
        critere_referentiel:      obsFormXIV.critere_referentiel,
        causes_json:              JSON.stringify(obsFormXIV.causes_selection),
        causes_autres:            obsFormXIV.causes_autres,
        consequences_json:        JSON.stringify(obsFormXIV.consequences_selection),
        consequences_description: obsFormXIV.consequences_description,
        objectif_audit:           obsData.objectif_audit,
        tache_local_observer:     obsData.tache_local_observer,
        elements_verifier:        obsData.elements_verifier,
        pieces_attendues:         obsData.pieces_attendues,
        points_forts:             obsData.points_forts,
        conclusion:               obsData.conclusion,
        niveau_controle:          obsData.niveau_controle,
        niveau_synthese:          obsData.niveau_synthese,
      },
      children: {
        outil_observation_constats:        obsData.constats,
        outil_observation_recommandations: obsData.recommandations,
      },
    }
    const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }, body: JSON.stringify(payload) })
    const ct = res.headers.get('content-type') ?? ''
    if (!ct.includes('application/json')) { showToast('error', `Erreur serveur (${res.status})`); return }
    const d = await res.json()
    if (d.success) {
      showToast('success', "Fiche d'observation sauvegardée.")
      if (ctx) {
        const pk = pKey(ctx.objNum, ctx.testRef, 0)
        if (!outilsProcsMap[pk]) outilsProcsMap[pk] = []
        if (!outilsProcsMap[pk].includes('XIV')) outilsProcsMap[pk].push('XIV')
      }
    } else showToast('error', d.error || 'Erreur serveur')
  } catch(e: any) { showToast('error', `Erreur réseau : ${e?.message ?? ''}`) }
  finally { obsSaving.value = false }
}

// ─── OUTILS ──────────────────────────────────────────────────────
function ouvrirOutil(obj: any, test: any, code: string, procIdx: number, oi: number, ti: number) {
  if (code === 'XIV') {
    if (!ficheActif.value) ouvrirFiche(obj, test, oi, ti)
    obsData.objectif_audit = getObjectifTexte()
    if (obsXIVLiee.value && props.urlLoadOutil && form.id) chargerObservationBD()
    obsActif.value = true
    return
  }
  const tr  = tRef(test, oi, ti)
  const pk  = pKey(obj.num, tr, procIdx)
  const segments: Record<string, string> = {
    I:'entretien', II:'analyse-taches', III:'diagramme-flux', IV:'approche-processus',
    V:'test-cheminement', VI:'hierarchisation-risques', VII:'referentiel-audit',
    VIII:'cause-effet', IX:'qci', X:'brainstorming', XI:'piste-audit',
    XII:'circularisation', XIII:'audit-analytique', XV:'echantillonnage',
  }
  const seg = segments[code]
  if (!seg) return
  if (!outilsProcsMap[pk]) outilsProcsMap[pk] = []
  if (!outilsProcsMap[pk].includes(code)) outilsProcsMap[pk].push(code)

  const libelleProcedure = (procIdx >= 0 && (test.procedures?.length ?? 0) > procIdx) ? test.procedures[procIdx] : (test.libelle ?? '')
  const objectifAudit    = obj.objectif ?? obj.libelle ?? ''
  const params = new URLSearchParams({
    fiche_test_id:  String(form.id ?? ''),
    mission_id:     String(props.missionId ?? props.missionContext?.mission_id ?? ''),
    assignment_id:  String(props.assignmentId ?? props.missionContext?.assignment_id ?? ''),
    test_ref: tr, obj_num: obj.num, proc_idx: String(procIdx),
    procedure_code: props.missionContext?.code_mission ?? '',
    libelle_test:   test.libelle ?? '',
    libelle_proc:   libelleProcedure,
    objectif_audit: objectifAudit,
    back: window.location.href,
  })
  router.visit('/auditor/outils/' + seg + '?' + params.toString())
}

function ouvrirOutilDepuisFiche(code: string, procIdx: number) {
  if (!ficheActif.value) return
  if (code === 'XIV') { ouvrirObservation(); return }
  ouvrirOutil(ficheActif.value.obj, ficheActif.value.test, code, procIdx, ficheActif.value.oi, ficheActif.value.ti)
}

// ─── MODAL OUTILS ─────────────────────────────────────────────────
function ouvrirChoixOutil(obj: any, test: any, pi: number, oi: number, ti: number) {
  const tr = tRef(test, oi, ti)
  Object.assign(om, { visible: true, testRef: tr, procIdx: pi, objNum: obj.num, obj, test, oi, ti, selected: [...(getOutilsForProc(obj.num, tr, pi))] })
}
function omToggle(code: string) { const i = om.selected.indexOf(code); if (i===-1) om.selected.push(code); else om.selected.splice(i,1) }
function omConfirmer() {
  if (!om.selected.length) return
  const pk = pKey(om.objNum, om.testRef, om.procIdx ?? 0)
  outilsProcsMap[pk] = [...om.selected]
  om.visible = false
  ouvrirOutil(om.obj, om.test, om.selected[0], om.procIdx ?? 0, om.oi, om.ti)
}

// ─── SÉRIALISATION ────────────────────────────────────────────────
function serializeResultats() {
  const main = Object.entries(resultatsMap).map(([k,v]) => { const p = k.split('::'); return { obj_num: p[0], test_ref: p.slice(1).join('::'), ...v } })
  const sub  = Object.entries(subTestResultsMap).map(([k,v]) => { const p = k.split('::'); return { obj_num: p[0], test_ref: p.slice(1).join('::'), ...v } })
  return [...main, ...sub]
}
function serializeOutilsData() { return Object.values(outilsDataMap) }

// ─── CRUD ─────────────────────────────────────────────────────────
async function submit(silent = false) {
  processing.value = !silent
  try {
    const url = form.id ? (dynUrls.update ?? props.urlUpdate) : props.urlStore
    if (!url) { if (!silent) showToast('error', 'URL indisponible.'); return }
    const payload: any = {
      mission_id:    props.missionId ?? props.missionContext?.mission_id,
      assignment_id: props.assignmentId ?? props.missionContext?.assignment_id,
      resultats:     JSON.stringify(serializeResultats()),
      outils_data:   JSON.stringify(serializeOutilsData()),
    }
    if (form.id) payload._method = 'PUT'
    const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }, body: JSON.stringify(payload) })
    if (!res.ok) { const txt = await res.text(); throw new Error(`HTTP ${res.status} — ${txt.substring(0,200)}`) }
    const d = await res.json()
    if (d.success || res.ok) {
      if (!silent) showToast('success', form.id ? 'Fiche mise à jour.' : 'Fiche créée.')
      if (d.form) Object.assign(form, { id: d.form.id, code: d.form.code, validation_status: d.form.validation_status })
      if (d.urlUpdate)    dynUrls.update    = d.urlUpdate
      if (d.urlSoumettre) dynUrls.soumettre = d.urlSoumettre
      if (d.urlValider)   dynUrls.valider   = d.urlValider
    } else { if (!silent) showToast('error', d.message ?? 'Erreur.') }
  } catch(e: any) { if (!silent) showToast('error', e.message ?? 'Erreur réseau.') }
  finally { processing.value = false }
}

async function saveFiche() { await submit(false) }
function annuler() { if (props.backUrl) router.visit(props.backUrl) }

async function soumettre() {
  processing.value = true
  try {
    const d = await (await fetch(dynUrls.soumettre ?? props.urlSoumettre ?? '', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf(),'Accept':'application/json'}, body:JSON.stringify({mission_id: props.missionId??props.missionContext?.mission_id, assignment_id: props.assignmentId??props.missionContext?.assignment_id}) })).json()
    if (d.success) { form.validation_status = 'in_review'; showToast('success', 'Soumise pour validation.') }
    else showToast('error', d.error ?? 'Erreur')
  } catch { showToast('error', 'Erreur réseau') }
  processing.value = false
}

async function valider(action: string, note?: string) {
  processing.value = true
  try {
    const d = await (await fetch(dynUrls.valider ?? props.urlValider ?? '', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf(),'Accept':'application/json'}, body:JSON.stringify({mission_id: props.missionId??props.missionContext?.mission_id, assignment_id: props.assignmentId??props.missionContext?.assignment_id, action, note}) })).json()
    if (d.success) { form.validation_status = d.status; showToast('success', action==='validate'?'Fiche validée ✓':'Rejetée.') }
    else showToast('error', d.error ?? 'Erreur')
  } catch { showToast('error', 'Erreur réseau') }
  processing.value = false
}

function promptReject() { const n = prompt('Motif du rejet :',''); if (n?.trim()) valider('reject', n.trim()) }

function showToast(t: string, m: string, dur = 4000) {
  if (_tt) clearTimeout(_tt)
  toast.value = { show: true, type: t, msg: m }
  _tt = setTimeout(() => { toast.value.show = false }, dur)
}

onBeforeUnmount(() => { if (_tt) clearTimeout(_tt) })
</script>

<style scoped>
:root{--navy:#0f172a;--blue:#1e40af;--green:#065f46;--purple:#6d28d9;--border:#e2e8f0;--bg:#f1f5f9;--sh:0 1px 3px rgba(15,23,42,.07)}
.ft-shell{display:flex;flex-direction:column;height:100vh;background:var(--bg);font-family:'Segoe UI',system-ui,sans-serif}

/* ── TOPBAR ── */
.ft-topbar{display:flex;justify-content:space-between;align-items:center;padding:.5rem 1rem;background:white;border-bottom:1px solid var(--border);flex-shrink:0}
.ft-topbar__left{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}
.ft-topbar__right{display:flex;align-items:center;gap:.3rem}
.ft-code{background:var(--navy);color:white;padding:.2rem .6rem;border-radius:4px;font-size:.7rem;font-weight:600}
.ft-sdot{width:8px;height:8px;border-radius:50%}
.sd--draft{background:#94a3b8}.sd--in_review{background:#2563eb}.sd--validated{background:#16a34a}
.ft-vstatus{font-size:.7rem;color:#475569}
.ft-div{width:1px;height:20px;background:var(--border)}
.ft-icon-muted{font-size:.8rem;color:#94a3b8}
.ft-mission-lbl{font-size:.75rem;color:#475569}
.ft-prog-badge{background:#ede9fe;color:var(--purple);padding:.1rem .5rem;border-radius:4px;font-size:.65rem;font-weight:600}
.ft-stat-lbl{font-size:.7rem;color:#64748b}
.ft-chip-role,.ft-chip-user{display:inline-flex;align-items:center;gap:.25rem;background:#f1f5f9;padding:.2rem .6rem;border-radius:20px;font-size:.7rem}

/* ── BANNERS ── */
.ft-banner{display:flex;align-items:center;gap:.5rem;padding:.3rem 1rem;font-size:.75rem;flex-shrink:0}
.ft-banner--ok{background:#d1fae5;color:#065f46;border-bottom:1px solid #a7f3d0}
.ft-banner--review{background:#dbeafe;color:#1d4ed8;border-bottom:1px solid #bfdbfe}
.ft-banner--rejected{background:#fee2e2;color:#dc2626;border-bottom:1px solid #fecaca}

/* ── MAIN ── */
.ft-main{flex:1;overflow:auto;padding:1rem}

/* ── TESTS VIEW ── */
.ft-tests-view{max-width:1000px;margin:0 auto}
.ft-empty{text-align:center;padding:3rem}
.ft-empty__ico{font-size:2.5rem;color:#94a3b8;margin-bottom:1rem}
.ft-empty__title{font-weight:600;color:#475569;margin-bottom:.25rem}
.ft-empty__sub{font-size:.75rem;color:#94a3b8}
.ft-obj-list{display:flex;flex-direction:column;gap:1rem}
.ft-obj{background:white;border-radius:10px;border:1px solid var(--border);overflow:hidden;box-shadow:var(--sh)}
.ft-obj__hd{display:flex;align-items:center;gap:.5rem;padding:.7rem 1rem;background:linear-gradient(135deg,#eff6ff,#f0fdf4);border-bottom:1px solid var(--border);flex-wrap:wrap}
.ft-obj__num{background:#1e40af;color:white;padding:.15rem .5rem;border-radius:4px;font-size:.7rem;font-weight:600}
.ft-obj__label{font-weight:600;color:#1e293b;flex:1}
.ft-tag{display:inline-flex;gap:.2rem;padding:.1rem .5rem;border-radius:20px;font-size:.65rem}
.ft-tag--blue{background:#dbeafe;color:#1d4ed8}
.ft-tests{display:flex;flex-direction:column}
.ft-test{padding:.7rem 1rem;border-bottom:1px solid #f1f5f9}
.ft-test:hover{background:#fafbfc}
.ft-test--done{border-left:3px solid #10b981}
.ft-test__row{display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap}
.ft-test__info{flex:1;min-width:200px}
.ft-test__lbl{margin:0 0 .25rem;font-size:.8rem;font-weight:500;color:#334155}
.ft-test__chips{display:flex;gap:.25rem;flex-wrap:wrap}
.ft-test__acts{display:flex;align-items:center;gap:.3rem}
.ft-test__outils-badges{display:flex;gap:.25rem;flex-wrap:wrap;margin-top:.35rem}
.ft-outil-badge{display:inline-flex;align-items:center;gap:.2rem;padding:.15rem .5rem;background:color-mix(in srgb,var(--oc,#374151) 12%,white);border:1px solid color-mix(in srgb,var(--oc,#374151) 30%,white);color:var(--oc,#374151);border-radius:20px;font-size:.62rem;font-weight:700;cursor:pointer;transition:all .15s}
.ft-outil-badge:hover{background:var(--oc,#374151);color:white;transform:translateY(-1px)}
.ft-outil-badge__score{background:rgba(255,255,255,.3);border-radius:10px;padding:0 4px;font-size:.58rem}
.ft-ref{background:#ede9fe;color:var(--purple);padding:.2rem .5rem;border-radius:4px;font-size:.65rem;font-weight:600;white-space:nowrap}
.ft-chip{display:inline-flex;align-items:center;gap:.2rem;padding:.1rem .4rem;border-radius:20px;font-size:.6rem}
.ft-chip--blue{background:#dbeafe;color:#1d4ed8}.ft-chip--green{background:#d1fae5;color:#065f46}.ft-chip--purple{background:#ede9fe;color:#6d28d9}
.ft-sel-result{border:1px solid #cbd5e1;border-radius:6px;padding:.2rem .4rem;font-size:.7rem}
.ft-result-pill{display:inline-block;padding:.15rem .5rem;border-radius:20px;font-size:.7rem;font-weight:600}
.frp--conforme{background:#d1fae5;color:#065f46}.frp--ecart{background:#fef3c7;color:#92400e}.frp--nc{background:#fee2e2;color:#dc2626}.frp--na{background:#f1f5f9;color:#475569}
.ft-procs{background:#f8fafc;border-top:1px solid #f1f5f9;padding:.5rem 1rem;display:flex;flex-direction:column;gap:.3rem}
.ft-proc{display:flex;align-items:center;gap:.5rem;padding:.3rem .5rem;border-radius:6px;border:1px solid transparent}
.ft-proc--linked{background:#f0fdf4;border-color:#bbf7d0}
.ft-proc__n{font-size:.6rem;font-weight:700;color:#94a3b8;min-width:18px}
.ft-proc__txt{font-size:.7rem;color:#334155;flex:1}
.ft-proc__acts{display:flex;align-items:center;gap:.3rem;flex-wrap:wrap}
.ft-outil-tag{display:inline-flex;align-items:center;gap:.2rem;padding:.1rem .5rem;background:var(--ot,#374151);color:white;border-radius:20px;font-size:.6rem;font-weight:600;cursor:pointer;border:none}

/* ── BOUTON OBSERVATION MINI (liste tests) ── */
.ft-btn-obs-mini{position:relative;display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;background:#faf5ff;border:1px solid #ddd6fe;color:#7c3aed;border-radius:6px;cursor:pointer;transition:all .15s;flex-shrink:0}
.ft-btn-obs-mini:hover{background:#ede9fe;border-color:#c4b5fd;transform:translateY(-1px)}
.ft-btn-obs-mini--done{background:#ede9fe;border-color:#a78bfa;color:#6d28d9}
.ft-btn-obs-mini--done:hover{background:#ddd6fe}
.ft-btn-obs-mini__dot{position:absolute;top:3px;right:3px;width:6px;height:6px;background:#10b981;border-radius:50%}
.ft-btn-obs-mini i{font-size:.8rem}

/* ── BOUTONS ── */
.ft-btn{display:inline-flex;align-items:center;gap:.3rem;padding:.35rem .8rem;border-radius:6px;font-size:.75rem;font-weight:500;border:1px solid transparent;cursor:pointer;transition:all .2s}
.ft-btn:disabled{opacity:.5;cursor:not-allowed}
.ft-btn--sm{padding:.25rem .6rem;font-size:.7rem}
.ft-btn--ghost{background:transparent;border-color:#cbd5e1}.ft-btn--ghost:hover{background:#f1f5f9}
.ft-btn--save{background:var(--navy);color:white}.ft-btn--save:hover{background:#1e293b}
.ft-btn--submit{background:#2563eb;color:white}
.ft-btn--validate{background:#10b981;color:white}.ft-btn--reject{background:#dc2626;color:white}
.ft-btn--fiche{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}
.ft-btn--fiche-done{background:#d1fae5;border-color:#a7f3d0;color:#065f46}
.ft-ib{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;background:transparent;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;position:relative}
.ft-ib--sm{width:25px;height:25px}.ft-ib--xs{width:22px;height:22px;font-size:.7rem}
.ft-ib--active{background:#d1fae5;border-color:#a7f3d0;color:#065f46}
.ft-ib--add{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}
.ft-ib--edit{background:#fefce8;border-color:#fde68a;color:#b45309}
.ft-dot{position:absolute;top:3px;right:3px;width:6px;height:6px;background:#10b981;border-radius:50%}

/* ── FICHE DE TEST ── */
.ft-fiche-capture{max-width:900px;margin:0 auto;background:white;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.1);overflow:hidden}
.ft-fiche-header{display:flex;justify-content:space-between;align-items:center;padding:.8rem 1.2rem;background:#1e3a5f}
.ft-fiche-header__center{display:flex;align-items:center;gap:.8rem}
.ft-fiche-header__right{display:flex;align-items:center;gap:.5rem}
.ft-fiche-icon{width:36px;height:36px;background:rgba(255,255,255,.15);border-radius:8px;display:flex;align-items:center;justify-content:center;color:white;font-size:1.2rem}
.ft-fiche-title{font-size:.9rem;font-weight:700;color:white}
.ft-fiche-sub{font-size:.65rem;color:rgba(255,255,255,.7);margin-top:2px}
.ft-btn-back,.ft-btn-close{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);color:white;padding:.3rem .8rem;border-radius:6px;cursor:pointer;font-size:.75rem;display:inline-flex;align-items:center;gap:.3rem}
.ft-btn-back:hover,.ft-btn-close:hover{background:rgba(255,255,255,.25)}
.ft-btn-save{background:#10b981;border:none;color:white;padding:.3rem .8rem;border-radius:6px;cursor:pointer;font-size:.75rem;display:inline-flex;align-items:center;gap:.3rem}
.ft-btn-save:hover{background:#059669}.ft-btn-save:disabled{opacity:.5;cursor:not-allowed}
.ft-fiche-body{padding:0}
.ft-fiche-footer{display:flex;justify-content:space-between;padding:.8rem 1.5rem;background:#f8fafc;border-top:1px solid var(--border)}

/* ── FICHE IFACI ── */
.fi-section-title{background:#1e3a5f;color:white;text-align:center;padding:7px 14px;font-size:12px;font-weight:600;letter-spacing:.02em}
.fi-section-title--light{background:#2c5282}
.fi-objectif{padding:10px 16px;font-size:13px;color:#1e293b;line-height:1.65;border-bottom:1px solid #e2e8f0}
.fi-auditeur-table{width:100%;border-collapse:collapse;border-bottom:1px solid #e2e8f0}
.fi-auditeur-table th,.fi-auditeur-table td{padding:8px 16px;border:1px solid #e2e8f0;text-align:center;font-size:12px}
.fi-auditeur-table th{background:#1e3a5f;color:white;font-weight:600;width:50%}
.fi-date-inp{border:1px solid #cbd5e1;border-radius:4px;padding:2px 6px;font-size:12px}
.fi-source-box{padding:10px 16px;border-bottom:1px solid #e2e8f0;display:flex;flex-direction:column;gap:4px}
.fi-source-item{display:flex;gap:8px;font-size:12px;color:#1e293b;line-height:1.5}
.fi-dash{color:#1e3a5f;font-weight:700;flex-shrink:0}
.fi-tests-list{border-bottom:1px solid #e2e8f0}
.fi-proc-block{border-top:1px solid #e8ecf0}
.fi-test-row{display:grid;grid-template-columns:26px 1fr auto;align-items:center;gap:8px;padding:9px 16px}
.fi-test-num{display:flex;align-items:center;justify-content:center;width:22px;height:22px;background:#1e3a5f;color:white;border-radius:4px;font-size:10px;font-weight:700;flex-shrink:0}
.fi-test-lbl{font-size:12px;color:#1e293b;line-height:1.5}
.fi-proc-outils{display:flex;align-items:center;gap:4px;flex-wrap:wrap}
.fi-outil-btn{display:flex;align-items:center;gap:4px;padding:4px 8px;background:color-mix(in srgb,var(--oc,#1e40af) 10%,white);border:1px solid color-mix(in srgb,var(--oc,#1e40af) 40%,white);color:var(--oc,#1e40af);border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;white-space:nowrap;transition:all .15s}
.fi-outil-btn:hover{filter:brightness(.95);transform:translateY(-1px)}
.fi-add-outil-btn{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;border-radius:5px;cursor:pointer}
.fi-result-sel{border:1px solid #cbd5e1;border-radius:5px;padding:4px 6px;font-size:11px}
.fi-result-badge{display:inline-block;padding:2px 7px;border-radius:20px;font-size:11px;font-weight:600;white-space:nowrap}
.fi-rb--conforme{background:#d1fae5;color:#065f46}.fi-rb--ecart{background:#fef3c7;color:#92400e}.fi-rb--nc{background:#fee2e2;color:#dc2626}.fi-rb--na{background:#f1f5f9;color:#475569}
.fi-constat-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:6px 16px 10px;background:#fafafa;border-top:1px dashed #e2e8f0}
.fi-mini-lbl{font-size:10px;font-weight:700;color:#475569;text-transform:uppercase;display:block;margin-bottom:3px}
.fi-ta{width:100%;border:1px solid #e2e8f0;border-radius:4px;padding:5px 7px;font-size:11px;font-family:inherit;resize:vertical}
.fi-ta--sm{font-size:11px}
.fi-inp{width:100%;border:1px solid #e2e8f0;border-radius:4px;padding:5px 7px;font-size:11px}
.fi-inp--sm{font-size:11px}
.fi-ro{font-size:11px;color:#1e293b;background:#f1f5f9;padding:5px 7px;border-radius:4px}
.fi-outils-resultats{border-top:1px solid #e2e8f0;padding:12px 16px;background:#f8fafc}
.fi-or-title{font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;display:flex;align-items:center;gap:6px}
.fi-or-list{display:flex;flex-direction:column;gap:8px}
.fi-or-card{border:1px solid color-mix(in srgb,var(--oc,#374151) 25%,#e2e8f0);border-radius:8px;overflow:hidden;cursor:pointer;transition:all .2s;background:white;border-left:4px solid var(--oc,#374151)}
.fi-or-card:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.1)}
.fi-or-card__head{display:flex;align-items:center;gap:8px;padding:8px 12px;background:color-mix(in srgb,var(--oc,#374151) 8%,white)}
.fi-or-card__code{display:inline-flex;align-items:center;justify-content:center;min-width:28px;height:22px;color:white;border-radius:5px;font-size:10px;font-weight:800;padding:0 5px}
.fi-or-card__label{font-size:12px;font-weight:600;color:#1e293b;flex:1}
.fi-or-card__st{font-size:10px;padding:1px 6px;border-radius:10px;font-weight:600}
.fist--draft{background:#f1f5f9;color:#64748b}.fist--in_review{background:#dbeafe;color:#1d4ed8}.fist--validated{background:#dcfce7;color:#15803d}.fist--rejected{background:#fee2e2;color:#dc2626}
.fi-or-card__score{font-size:11px;font-weight:700;color:var(--oc,#374151);background:color-mix(in srgb,var(--oc,#374151) 12%,white);padding:1px 7px;border-radius:10px}
.fi-or-card__open{font-size:12px;color:#94a3b8;margin-left:auto}
.fi-or-card__body{padding:8px 12px 10px;border-top:1px solid color-mix(in srgb,var(--oc,#374151) 10%,#e2e8f0)}
.fi-or-card__titre{font-size:12px;font-weight:600;color:#334155;margin:0 0 6px}
.fi-or-card__stats{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:6px}
.fi-or-stat{display:flex;flex-direction:column;background:#f1f5f9;border-radius:5px;padding:3px 8px;min-width:60px}
.fi-or-stat__k{font-size:9px;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em}
.fi-or-stat__v{font-size:12px;font-weight:700;color:#1e293b}
.fi-or-card__concl{font-size:11px;color:#475569;margin:0;line-height:1.4;font-style:italic}

/* ── BOUTON OBSERVATION MINI (depuis fiche) ── */
.fi-obs-cta-inline{padding:8px 16px;border-top:1px solid #e9d5ff;background:#faf5ff}
.fi-obs-btn-mini{display:inline-flex;align-items:center;gap:.4rem;padding:.3rem .7rem;background:white;border:1px solid #ddd6fe;color:#7c3aed;border-radius:6px;cursor:pointer;font-size:.72rem;font-weight:600;transition:all .18s}
.fi-obs-btn-mini:hover{background:#f5f3ff;border-color:#c4b5fd;box-shadow:0 2px 6px rgba(124,58,237,.15)}
.fi-obs-btn-mini i:first-child{font-size:.8rem}
.fi-obs-arrow{margin-left:.2rem;opacity:.4;font-size:.7rem}
.fi-obs-badge-liee{display:inline-flex;align-items:center;gap:.2rem;background:#d1fae5;color:#065f46;padding:.1rem .4rem;border-radius:10px;font-size:.64rem;font-weight:700}

/* ── CONSTAT VIEW ── */
.ft-constat-view{max-width:700px;margin:0 auto;background:white;border-radius:12px;padding:1.5rem;box-shadow:var(--sh)}
.ft-constat-back{display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem}
.ft-constat-ref{font-size:.8rem;font-weight:600;color:var(--navy)}
.ft-constat-body{display:flex;flex-direction:column;gap:1rem}
.fc-sect{display:flex;flex-direction:column;gap:.4rem}
.fc-lbl{font-size:.7rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em}
.fc-sel{border:1px solid #cbd5e1;border-radius:6px;padding:.35rem .6rem;font-size:.8rem}
.fc-foot{display:flex;justify-content:flex-end;gap:.5rem;padding-top:.5rem}

/* ── DROPDOWN ── */
.ft-dd{min-width:220px;padding:.25rem 0}
.ft-dd__head{padding:.3rem .75rem;font-size:.65rem;font-weight:700;color:#94a3b8;text-transform:uppercase;border-bottom:1px solid #f1f5f9}
.ft-dd__item{display:flex;align-items:center;gap:.4rem;width:100%;padding:.4rem .75rem;background:none;border:none;font-size:.75rem;cursor:pointer;color:#1e293b}
.ft-dd__item:hover{background:#f8fafc}
.ft-dd__dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.ft-dd__code{font-weight:700;font-size:.7rem;min-width:28px}
.ft-dd__lbl{flex:1}
.ft-dd__chk{color:#10b981}

/* ── MODAL OUTILS ── */
.om-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:1000}
.om-dialog{background:white;border-radius:16px;width:90%;max-width:600px;max-height:80vh;display:flex;flex-direction:column;overflow:hidden}
.om-hd{display:flex;justify-content:space-between;align-items:center;padding:1rem;border-bottom:1px solid #e2e8f0}
.om-hd__left{display:flex;align-items:center;gap:.8rem}
.om-hd__icon{width:40px;height:40px;background:linear-gradient(135deg,#1e40af,#6d28d9);border-radius:10px;display:flex;align-items:center;justify-content:center;color:white;font-size:1.2rem}
.om-hd__title{font-size:1rem;font-weight:700;margin:0}
.om-hd__sub{display:flex;gap:.3rem;margin-top:.2rem}
.om-ctx{font-size:.65rem;background:#f1f5f9;padding:.1rem .5rem;border-radius:20px}
.om-ctx--proc{background:#ede9fe;color:#6d28d9}
.om-selbar{display:flex;justify-content:space-between;align-items:center;padding:.5rem 1rem;background:#f8fafc;border-bottom:1px solid #e2e8f0}
.om-tags{display:flex;gap:.3rem;flex-wrap:wrap}
.om-tag{display:inline-flex;align-items:center;gap:.2rem;background:var(--ot,#374151);color:white;padding:.2rem .4rem .2rem .6rem;border-radius:20px;font-size:.7rem}
.om-tag button{background:none;border:none;color:white;cursor:pointer;font-size:.8rem;padding:0 .1rem}
.om-clear{background:none;border:1px solid #fecaca;color:#dc2626;padding:.2rem .6rem;border-radius:6px;font-size:.7rem;cursor:pointer}
.om-body{flex:1;overflow:auto;padding:1rem}
.om-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:.5rem}
.om-card{display:flex;align-items:center;gap:.5rem;padding:.5rem;border:2px solid #e2e8f0;border-radius:10px;cursor:pointer;background:white;width:100%;text-align:left}
.om-card:hover{background:#f8fafc}.om-card--sel{border-color:#1e40af;background:#eff6ff}
.om-card__num{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;flex-shrink:0}
.om-card__body{flex:1}.om-card__lbl{font-size:.7rem;font-weight:600;display:block}
.om-ft{display:flex;justify-content:flex-end;gap:.5rem;padding:1rem;border-top:1px solid #e2e8f0}
.om-confirm{background:linear-gradient(135deg,#1e40af,#6d28d9);color:white;padding:.4rem 1rem;border-radius:8px;border:none;cursor:pointer;font-size:.8rem;font-weight:600}
.om-confirm:disabled{opacity:.5;cursor:not-allowed}

/* ── MODAL OBSERVATION XIV ── */
.obs-overlay{position:fixed;inset:0;background:rgba(0,0,0,.6);display:flex;align-items:center;justify-content:center;z-index:1050;padding:1rem}
.obs-dialog{background:white;border-radius:16px;width:95%;max-width:900px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.3)}
.obs-hd{display:flex;justify-content:space-between;align-items:center;padding:1rem 1.25rem;background:linear-gradient(135deg,#6d28d9,#9333ea);flex-shrink:0}
.obs-hd__left{display:flex;align-items:center;gap:.8rem}
.obs-hd__right{display:flex;align-items:center;gap:.5rem}
.obs-num{display:inline-flex;align-items:center;justify-content:center;min-width:40px;height:36px;background:rgba(255,255,255,.2);color:white;border-radius:8px;font-size:1rem;font-weight:700}
.obs-title{font-size:.95rem;font-weight:700;color:white}
.obs-sub{font-size:.65rem;color:rgba(255,255,255,.7);margin-top:2px}
.obs-loading{display:flex;align-items:center;gap:.4rem;font-size:.7rem;color:rgba(255,255,255,.8)}
.obs-btn-save{background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);color:white;padding:.35rem .9rem;border-radius:6px;cursor:pointer;font-size:.75rem;display:inline-flex;align-items:center;gap:.3rem}
.obs-btn-save:hover{background:rgba(255,255,255,.3)}.obs-btn-save:disabled{opacity:.5;cursor:not-allowed}
.obs-btn-close{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);color:white;width:32px;height:32px;border-radius:6px;cursor:pointer;display:flex;align-items:center;justify-content:center}
.obs-body{flex:1;overflow-y:auto;padding:1rem;background:#f1f5f9;display:flex;flex-direction:column;gap:.75rem}
.obs-body--loading{justify-content:center;align-items:center}
.obs-loading-center{text-align:center;padding:3rem;display:flex;flex-direction:column;align-items:center;gap:1rem}
.obs-loading-center p{font-size:.8rem;color:#64748b;margin:0}
.obs-footer{display:flex;justify-content:space-between;padding:.75rem 1.25rem;background:white;border-top:1px solid var(--border);flex-shrink:0}
.obs-card{background:white;border-radius:10px;border:1px solid var(--border);padding:.9rem 1rem;box-shadow:var(--sh)}
.obs-card__hd{display:flex;align-items:center;gap:.5rem;margin-bottom:.65rem;flex-wrap:wrap}
.obs-card__title{font-size:.85rem;font-weight:700;color:var(--navy);margin:0;flex:1}
.obs-badge-count{background:#6d28d9;color:white;padding:.1rem .5rem;border-radius:20px;font-size:.65rem;font-weight:700}
.obs-add{display:inline-flex;align-items:center;gap:.2rem;padding:.2rem .6rem;background:#ede9fe;border:1px solid #c4b5fd;color:#6d28d9;border-radius:6px;font-size:.65rem;cursor:pointer}
.obs-g2{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
.obs-g3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:.75rem}
.obs-f{display:flex;flex-direction:column;gap:.25rem}
.obs-full{grid-column:1/-1}
.obs-lbl{font-size:.65rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em}
.obs-inp{width:100%;border:1px solid #cbd5e1;border-radius:6px;padding:.4rem .6rem;font-size:.75rem;font-family:inherit;box-sizing:border-box}
.obs-ta{width:100%;border:1px solid #cbd5e1;border-radius:6px;padding:.4rem .6rem;font-size:.75rem;font-family:inherit;resize:vertical;box-sizing:border-box}
.obs-inp-sm,.obs-ta-sm{width:100%;border:1px solid #cbd5e1;border-radius:4px;padding:.25rem .4rem;font-size:.7rem;font-family:inherit;box-sizing:border-box}
.obs-sel-sm{border:1px solid #cbd5e1;border-radius:4px;padding:.15rem .3rem;font-size:.7rem;width:100%}
.obs-table-wrap{overflow-x:auto;border-radius:6px;border:1px solid var(--border)}
.obs-tbl{width:100%;border-collapse:collapse;font-size:.7rem}
.obs-tbl th,.obs-tbl td{padding:.3rem .5rem;border-bottom:1px solid var(--border);border-right:1px solid var(--border);text-align:left;vertical-align:middle}
.obs-tbl th{background:#f8fafc;font-weight:700;color:#475569}
.obs-n{font-size:.6rem;font-weight:700;color:#94a3b8;text-align:center}
.obs-ec{text-align:center;color:#94a3b8;padding:1rem;font-style:italic;font-size:.7rem}
.obs-del{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;background:#fee2e2;border:1px solid #fecaca;color:#dc2626;border-radius:4px;cursor:pointer}
.tc{text-align:center}

/* ── VUE OBSERVATION XIV (pleine page) ── */
.ft-obs-view{display:flex;flex-direction:column;max-width:1000px;margin:0 auto;background:white;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.1);overflow:hidden}
.ft-obs-header{display:flex;justify-content:space-between;align-items:center;padding:.8rem 1.2rem;background:linear-gradient(135deg,#6d28d9,#7c3aed);flex-shrink:0}
.ft-obs-header__center{display:flex;align-items:center;gap:.8rem;flex:1;justify-content:center}
.ft-obs-header__right{display:flex;align-items:center;gap:.5rem}
.ft-obs-icon{width:36px;height:36px;background:rgba(255,255,255,.18);border-radius:8px;display:flex;align-items:center;justify-content:center;color:white;font-size:1.2rem}
.ft-obs-title{font-size:.9rem;font-weight:700;color:white}
.ft-obs-sub{display:flex;align-items:center;gap:.3rem;flex-wrap:wrap;margin-top:2px}
.ft-obs-sub__ref{background:rgba(255,255,255,.2);padding:1px 7px;border-radius:4px;font-size:.68rem;color:white;font-weight:600}
.ft-obs-sub__sep{font-size:.65rem;color:rgba(255,255,255,.5)}
.ft-obs-sub__lbl{font-size:.68rem;color:rgba(255,255,255,.75);font-style:italic;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ft-obs-save-btn{display:inline-flex;align-items:center;gap:.3rem;padding:.4rem 1rem;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);color:white;border-radius:7px;cursor:pointer;font-size:.75rem;font-weight:600;transition:all .2s}
.ft-obs-save-btn:hover{background:rgba(255,255,255,.28)}
.ft-obs-save-btn:disabled{opacity:.5;cursor:not-allowed}
.ft-obs-body{flex:1;overflow-y:auto;padding:1rem;background:#f5f3ff;display:flex;flex-direction:column;gap:.75rem}
.ft-obs-body--loading{justify-content:center;align-items:center}
.ft-obs-loading-box{text-align:center;padding:3rem;display:flex;flex-direction:column;align-items:center;gap:1rem}
.ft-obs-loading-box p{font-size:.8rem;color:#64748b;margin:0}
.ft-obs-footer{display:flex;justify-content:space-between;align-items:center;padding:.8rem 1.2rem;background:#faf5ff;border-top:1px solid #e9d5ff;flex-shrink:0}
.ft-obs-section{background:white;border-radius:10px;border:1px solid #e9d5ff;overflow:hidden;box-shadow:0 1px 3px rgba(109,40,217,.06)}
.ft-obs-section--compact .ft-obs-grid{padding:.75rem 1rem}
.ft-obs-section__title{display:flex;align-items:center;gap:.4rem;padding:.6rem 1rem;background:linear-gradient(135deg,#ede9fe,#f5f3ff);border-bottom:1px solid #e9d5ff;font-size:.78rem;font-weight:700;color:#6d28d9}
.ft-obs-grid{display:grid;gap:.65rem;padding:.85rem 1rem}
.ft-obs-grid--1{grid-template-columns:1fr}
.ft-obs-grid--2{grid-template-columns:1fr 1fr}
.ft-obs-grid--4{grid-template-columns:1fr 1fr 1fr 1fr}
.ft-obs-field{display:flex;flex-direction:column;gap:.2rem}
.ft-obs-field--full{grid-column:1/-1}
.ft-obs-lbl{font-size:.62rem;font-weight:700;color:#6d28d9;text-transform:uppercase;letter-spacing:.04em}
.ft-obs-hint{font-size:.62rem;color:#94a3b8;margin-top:2px}
.ft-obs-inp{border:1px solid #ddd6fe;border-radius:6px;padding:6px 10px;font-size:.78rem;font-family:inherit;width:100%;box-sizing:border-box;outline:none;transition:border-color .15s}
.ft-obs-inp:focus{border-color:#7c3aed;box-shadow:0 0 0 2px rgba(124,58,237,.15)}
.ft-obs-inp:disabled{background:#faf5ff;color:#64748b}
.ft-obs-ta{border:1px solid #ddd6fe;border-radius:6px;padding:6px 10px;font-size:.78rem;font-family:inherit;width:100%;box-sizing:border-box;resize:vertical;outline:none;transition:border-color .15s}
.ft-obs-ta:focus{border-color:#7c3aed;box-shadow:0 0 0 2px rgba(124,58,237,.15)}
.ft-obs-ta:disabled{background:#faf5ff;color:#64748b}
.ft-obs-causes-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.4rem;padding:.75rem 1rem}
.ft-obs-check{display:flex;align-items:center;gap:.4rem;font-size:.78rem;cursor:pointer}
.ft-obs-risks-table{display:flex;flex-wrap:wrap;gap:.5rem 1rem;padding:.75rem 1rem .25rem}
.ft-obs-risk-item{flex:1 1 160px}
.risk-label{font-weight:500}

/* ── NIVEAUX COLORÉS ── */
.ft-obs-sel-niveau{border:1px solid #ddd6fe;border-radius:6px;padding:6px 10px;font-size:.78rem;font-family:inherit;width:100%;box-sizing:border-box;outline:none;transition:all .2s;cursor:pointer}
.ft-obs-sel-niveau:focus{box-shadow:0 0 0 2px rgba(124,58,237,.15)}
.ft-obs-sel-niveau:disabled{cursor:default;opacity:.9}
.ft-obs-niveau-badge{display:inline-flex;align-items:center;padding:.2rem .7rem;border-radius:20px;font-size:.7rem;font-weight:700;margin-top:.35rem;width:fit-content}

/* ── TOAST ── */
.ft-toast{position:fixed;bottom:1rem;right:1rem;display:flex;align-items:center;gap:.5rem;padding:.5rem 1rem;border-radius:8px;font-size:.75rem;z-index:2000;box-shadow:0 4px 12px rgba(0,0,0,.15)}
.ft-toast--success{background:#065f46;color:white}.ft-toast--error{background:#dc2626;color:white}
.ft-toast__x{background:none;border:none;color:white;opacity:.7;cursor:pointer;margin-left:.5rem}

/* ── SPINNER ── */
.ft-spin{display:inline-block;width:.8rem;height:.8rem;border:2px solid rgba(255,255,255,.3);border-top-color:white;border-radius:50%;animation:spin .7s linear infinite}
.ft-spin--lg{width:2rem;height:2rem;border-width:3px}
@keyframes spin{to{transform:rotate(360deg)}}

/* ── TRANSITIONS ── */
.om-fade-enter-active,.om-fade-leave-active{transition:opacity .2s}
.om-fade-enter-from,.om-fade-leave-to{opacity:0}
.toast-pop-enter-active,.toast-pop-leave-active{transition:all .2s}
.toast-pop-enter-from,.toast-pop-leave-to{opacity:0;transform:translateY(10px)}

/* ── RESPONSIVE ── */
@media(max-width:880px){
  .ft-prog-badge,.ft-stat-lbl{display:none}
  .om-grid{grid-template-columns:1fr 1fr}
  .fi-constat-row{grid-template-columns:1fr}
  .obs-g2,.obs-g3{grid-template-columns:1fr}
  .ft-obs-grid--4{grid-template-columns:1fr 1fr}
  .ft-obs-grid--2{grid-template-columns:1fr}
}
@media(max-width:600px){
  .fi-test-row{grid-template-columns:22px 1fr}
  .ft-obs-grid--4{grid-template-columns:1fr}
  .ft-obs-grid--2{grid-template-columns:1fr}
}
</style>