<template>
  <VerticalLayoutAudit>
    <div class="shell">

      <!-- ═══════════════ TOPBAR -->
      <header class="topbar">
        <div class="row-g4">
          <a :href="props.backUrl" class="icon-btn" title="Retour">
            <i class="ti ti-arrow-left"></i>
          </a>
          <span class="code-badge">{{ form.code || 'FT-AUTO' }}</span>
          <span class="status-dot" :class="'sd--' + form.validation_status"></span>
          <span class="muted-sm">{{ vstLbl(form.validation_status) }}</span>
          <span class="divider"></span>
          <i class="ti ti-building muted-icon"></i>
          <span class="muted-sm">{{ missionLibelle || '—' }}</span>
          <span v-if="programmeData.found" class="prog-badge">{{ programmeData.programme_code }}</span>
          <span v-if="programmeData.found" class="muted-sm">
            {{ programmeData.total_objectifs }} obj. · {{ programmeData.total_tests }} tests
          </span>
        </div>
        <div class="row-g4">
          <span class="chip chip--role"><i class="ti ti-shield-half"></i>{{ props.auditorRole }}</span>
          <span class="chip chip--user"><i class="ti ti-user-check"></i>{{ props.auditeurNom }}</span>
          <button class="btn btn--synth" @click="ouvrirSynthese">
            <i class="ti ti-file-analytics"></i> Feuille de Synthèse
          </button>
          <template v-if="!isLocked">
            <button class="btn btn--ghost" :disabled="processing" @click="annuler"><i class="ti ti-x"></i></button>
            <button class="btn btn--save" :disabled="processing" @click="submit()">
              <span v-if="processing" class="spin"></span>
              <i v-else class="ti ti-device-floppy"></i>
              {{ form.id ? 'Enregistrer' : 'Créer' }}
            </button>
            <button v-if="form.id && form.validation_status === 'draft'" class="btn btn--submit" @click="soumettre">
              <i class="ti ti-send"></i> Soumettre
            </button>
          </template>
          <template v-if="canManage && form.validation_status === 'in_review'">
            <button class="btn btn--ok" @click="valider('validate')"><i class="ti ti-circle-check"></i> Valider</button>
            <button class="btn btn--ko" @click="promptReject"><i class="ti ti-circle-x"></i> Rejeter</button>
          </template>
        </div>
      </header>

      <!-- Banners statut -->
      <div v-if="form.validation_status === 'validated'" class="status-banner banner--ok">
        <i class="ti ti-lock"></i> Fiche <strong>validée</strong> — lecture seule
      </div>
      <div v-else-if="form.validation_status === 'in_review'" class="status-banner banner--review">
        <i class="ti ti-clock"></i> En attente de validation
        <span v-if="canManage"> · DM/CM peut valider ou rejeter</span>
      </div>
      <div v-else-if="form.validation_status === 'draft' && form.validation_note" class="status-banner banner--reject">
        <i class="ti ti-circle-x"></i> Rejetée — <em>{{ form.validation_note }}</em>
      </div>

      <!-- ═══════════════ KPI BAR -->
      <div v-if="!ficheActif && !obsVueActif" class="kpi-bar">
        <div class="kpi-strip">
          <div class="kpi-cell"><span class="kv kv--blue">{{ programmeData.total_objectifs || 0 }}</span><span class="kl">Objectifs</span></div>
          <div class="kpi-cell"><span class="kv kv--slate">{{ programmeData.total_tests || 0 }}</span><span class="kl">Tests</span></div>
          <div class="kpi-cell"><span class="kv kv--green">{{ getConformeCount() }}</span><span class="kl">Conformes</span></div>
          <div class="kpi-cell"><span class="kv kv--orange">{{ getEcartCount() }}</span><span class="kl">Écarts</span></div>
          <div class="kpi-cell"><span class="kv kv--red">{{ getNcCount() }}</span><span class="kl">Non conf.</span></div>
          <div class="kpi-cell"><span class="kv kv--purple">{{ syntheseData.frap_lignes.length }}</span><span class="kl">FRAP</span></div>
        </div>
        <div class="progress-wrap">
          <div class="progress-track">
            <div class="ps ps--green" :style="`width:${tauxConformite}%`"></div>
            <div class="ps ps--orange" :style="`width:${tauxEcart}%;left:${tauxConformite}%`"></div>
            <div class="ps ps--red" :style="`width:${tauxNc}%;left:${tauxConformite + tauxEcart}%`"></div>
          </div>
          <span class="muted-sm">{{ tauxConformite }}% conformes</span>
        </div>
      </div>

      <!-- ═══════════════ CONTENU PRINCIPAL -->
      <div class="main-content">

        <!-- ◈ LISTE DES TESTS -->
        <div v-if="!ficheActif && !obsVueActif" class="tests-section">
          <div v-if="!programmeData.objectifs?.length" class="empty-state">
            <i class="ti ti-clipboard-off empty-state__ico"></i>
            <p class="fw-600">Aucun test affecté</p>
            <p class="muted-sm">Contactez le Chef de Mission pour vous affecter des tests dans le programme de travail.</p>
          </div>

          <div v-else class="obj-list">
            <div v-for="(obj, oi) in programmeData.objectifs" :key="oi" class="obj-block">

              <div class="obj-hd">
                <span class="obj-num">{{ obj.num }}</span>
                <span class="obj-lbl">{{ obj.objectif || obj.libelle }}</span>
                <span v-if="obj._axe_rado" class="tag tag--blue">{{ obj._axe_rado }}</span>
                <div class="row-g4 ml-auto">
                  <span v-if="getObjectifConformeCount(obj,oi)>0" class="obj-mini-kpi kpi--ok">{{ getObjectifConformeCount(obj,oi) }}✅</span>
                  <span v-if="getObjectifEcartCount(obj,oi)>0" class="obj-mini-kpi kpi--wn">{{ getObjectifEcartCount(obj,oi) }}⚠️</span>
                  <span v-if="getObjectifNcCount(obj,oi)>0" class="obj-mini-kpi kpi--nc">{{ getObjectifNcCount(obj,oi) }}❌</span>
                </div>
              </div>

              <div class="tests-list">
                <div v-for="(test, ti) in obj.tests" :key="ti"
                  class="test-row"
                  :class="{
                    'tr--ok': getResultat(obj.num,tRef(test,oi,ti))==='conforme',
                    'tr--wn': getResultat(obj.num,tRef(test,oi,ti))==='ecart',
                    'tr--nc': getResultat(obj.num,tRef(test,oi,ti))==='nc',
                    'tr--na': getResultat(obj.num,tRef(test,oi,ti))==='na',
                  }"
                >
                  <code class="test-ref">{{ tRef(test,oi,ti) }}</code>

                  <div class="test-info">
                    <div class="test-lbl">{{ test.libelle || '—' }}</div>
                    <div class="row-g4 mt4">
                      <span v-if="test.periode_testee" class="tag tag--blue"><i class="ti ti-calendar"></i>{{ test.periode_testee }}</span>
                      <span v-if="test.lieu" class="tag tag--green"><i class="ti ti-map-pin"></i>{{ test.lieu }}</span>
                      <span v-if="test.taille_echantillon" class="tag tag--purple">n={{ test.taille_echantillon }}</span>
                    </div>
                    <div v-if="getOutilsPourTest(obj.num, tRef(test,oi,ti)).length" class="row-g4 mt4">
                      <button
                        v-for="ot in getOutilsPourTest(obj.num, tRef(test,oi,ti))" :key="ot.outil_code"
                        class="outil-chip" :style="`--oc:${ot.color}`"
                        :title="ot.label"
                        @click="ouvrirOutil(obj, test, ot.outil_code, ot.proc_idx, oi, ti)"
                      >
                        {{ ot.outil_code }}
                        <span v-if="ot.ia_score !== null" class="outil-chip__score">{{ ot.ia_score }}</span>
                      </button>
                    </div>
                  </div>

                  <div>
                    <select v-if="!isLocked" class="res-sel"
                      :value="getResultat(obj.num,tRef(test,oi,ti))"
                      @change="setResultat(obj.num,tRef(test,oi,ti),($event.target as HTMLSelectElement).value)"
                    >
                      <option value="">— résultat —</option>
                      <option value="conforme">✅ Conforme</option>
                      <option value="ecart">⚠️ Écart</option>
                      <option value="nc">❌ Non conforme</option>
                      <option value="na">N/A</option>
                    </select>
                    <span v-else class="res-pill" :class="'rp--'+(getResultat(obj.num,tRef(test,oi,ti))||'na')">
                      {{ resLbl(getResultat(obj.num,tRef(test,oi,ti))) || '—' }}
                    </span>
                  </div>

                  <div class="test-acts">
                    <button class="act-btn act--fiche"
                      :class="{'act--has-data': testHasAnyOutil(obj.num,tRef(test,oi,ti))}"
                      @click="ouvrirFiche(obj,test,oi,ti)"
                    >
                      <i class="ti ti-clipboard-text"></i> Fiche
                    </button>
                    <button class="act-btn act--obs"
                      :class="{'act--has-data': testHasOutil(obj.num,tRef(test,oi,ti),'XIV')}"
                      @click="ouvrirObsDirecte(obj,test,oi,ti)"
                      title="Observation directe XIV"
                    >
                      <i class="ti ti-eye"></i> Obs.
                      <span v-if="testHasOutil(obj.num,tRef(test,oi,ti),'XIV')" class="saved-dot"></span>
                    </button>
                    <div class="dd-wrap" @click.stop>
                      <button class="act-btn act--tools"
                        :class="{active: testHasAnyOutil(obj.num,tRef(test,oi,ti))}"
                        @click="toggleDD(tRef(test,oi,ti))"
                      >
                        <i class="ti ti-tool"></i>
                      </button>
                      <div v-if="showDD===tRef(test,oi,ti)" class="dd-menu">
                        <div class="dd-head">Outils IFACI</div>
                        <button v-for="outil in props.outilsIfaci" :key="outil.code"
                          class="dd-item"
                          @click="ouvrirOutil(obj,test,outil.code,0,oi,ti); showDD=''"
                        >
                          <span class="dd-dot" :style="`background:${outil.color}`"></span>
                          <span class="dd-code" :style="`color:${outil.color}`">{{ outil.code }}</span>
                          <span class="dd-lbl">{{ outil.label }}</span>
                          <i v-if="testHasOutil(obj.num,tRef(test,oi,ti),outil.code)" class="ti ti-check dd-chk"></i>
                        </button>
                      </div>
                    </div>
                  </div>

                  <div v-if="test.procedures?.length" class="proc-list">
                    <div v-for="(proc,pi) in test.procedures" :key="pi"
                      class="proc-item"
                      :class="{'proc-item--linked': getOutilsForProc(obj.num,tRef(test,oi,ti),pi).length}"
                    >
                      <span class="proc-n">{{ pi+1 }}</span>
                      <span class="proc-txt">{{ proc }}</span>
                      <div class="row-g4">
                        <button v-for="code in getOutilsForProc(obj.num,tRef(test,oi,ti),pi)" :key="code"
                          class="outil-chip" :style="`--oc:${outilColor(code)}`"
                          @click.stop="ouvrirOutil(obj,test,code,pi,oi,ti)"
                        >{{ code }}</button>
                        <button v-if="!isLocked" class="proc-add-btn"
                          @click.stop="ouvrirChoixOutil(obj,test,pi,oi,ti)"
                        >
                          <i :class="getOutilsForProc(obj.num,tRef(test,oi,ti),pi).length ? 'ti ti-edit':'ti ti-plus'"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ◈ FICHE DE TEST — vue pleine page -->
        <div v-if="ficheActif && !obsVueActif" class="page-view">
          <div class="page-header page-header--fiche">
            <button class="btn-back" @click="ficheActif=null">
              <i class="ti ti-arrow-left"></i> Retour
            </button>
            <div class="page-header__center">
              <div class="page-ico page-ico--fiche"><i class="ti ti-clipboard-text"></i></div>
              <div>
                <div class="page-title">Fiche de Test d'Audit</div>
                <div class="page-sub">{{ ficheActif.testRef }} · Objectif {{ ficheActif.objNum }}</div>
              </div>
            </div>
            <div class="row-g4">
              <span class="res-pill" :class="'rp--'+(getResultat(ficheActif.objNum,ficheActif.testRef)||'na')">
                {{ resLbl(getResultat(ficheActif.objNum,ficheActif.testRef)) || 'À évaluer' }}
              </span>
              <button v-if="!isLocked" class="btn btn--save btn--sm" :disabled="processing" @click="submit(false)">
                <i class="ti ti-device-floppy"></i>
              </button>
            </div>
          </div>

          <div class="page-body">
            <div class="sc">
              <div class="fi-meta">
                <div><span class="lbl">Mission</span><span class="val">{{ missionLibelle||'—' }}</span></div>
                <div><span class="lbl">Programme</span><span class="val">{{ programmeData.programme_code||'—' }}</span></div>
                <div><span class="lbl">Auditeur</span><span class="val">{{ props.auditeurNom||'—' }}</span></div>
                <div>
                  <span class="lbl">Date</span>
                  <input v-if="!isLocked" type="date" class="inp-sm" v-model="ficheActif.date"/>
                  <span v-else class="val">{{ fmtDate(ficheActif.date)||'—' }}</span>
                </div>
              </div>
              <div class="mt8">
                <span class="lbl">Objectif</span>
                <span class="val fw-600">{{ getObjectifTexte() }}</span>
              </div>
            </div>

            <div class="sc">
              <div class="sc-title">Résultat du test</div>
              <div class="row-g8" style="align-items:center;flex-wrap:wrap">
                <select v-if="!isLocked" class="res-sel"
                  :value="getResultat(ficheActif.objNum,ficheActif.testRef)"
                  @change="setResultat(ficheActif.objNum,ficheActif.testRef,($event.target as HTMLSelectElement).value)"
                >
                  <option value="">— résultat —</option>
                  <option value="conforme">✅ Conforme</option>
                  <option value="ecart">⚠️ Écart</option>
                  <option value="nc">❌ Non conforme</option>
                  <option value="na">N/A</option>
                </select>
                <span v-else class="res-pill" :class="'rp--'+(getResultat(ficheActif.objNum,ficheActif.testRef)||'na')">
                  {{ resLbl(getResultat(ficheActif.objNum,ficheActif.testRef))||'—' }}
                </span>
              </div>
            </div>

            <div class="sc">
              <div class="sc-title"><i class="ti ti-list-check"></i> Procédures — {{ ficheActif.testRef }}</div>
              <div class="procs-list">
                <template v-if="ficheActif.test?.procedures?.length">
                  <div v-for="(proc,pi) in ficheActif.test.procedures" :key="pi"
                    class="fi-proc" :class="{'fi-proc--linked': getOutilsForProc(ficheActif.objNum,ficheActif.testRef,pi).length}"
                  >
                    <div class="fi-proc__hd">
                      <span class="fi-proc-n">{{ pi+1 }}</span>
                      <span class="fi-proc-txt">{{ proc }}</span>
                    </div>
                    <div class="fi-proc__tools">
                      <button v-for="code in getOutilsForProc(ficheActif.objNum,ficheActif.testRef,pi)" :key="code"
                        class="outil-chip" :style="`--oc:${outilColor(code)}`"
                        @click="ouvrirOutilDepuisFiche(code,pi)"
                      >
                        <i class="ti ti-tool"></i> {{ code }}
                      </button>
                      <button v-if="!isLocked" class="proc-add-btn"
                        @click="ouvrirChoixOutil(ficheActif.obj,ficheActif.test,pi,ficheActif.oi,ficheActif.ti)"
                      >
                        <i :class="getOutilsForProc(ficheActif.objNum,ficheActif.testRef,pi).length ? 'ti ti-edit':'ti ti-plus'"></i>
                      </button>
                    </div>
                  </div>
                </template>
                <template v-else>
                  <div class="fi-proc fi-proc--single">
                    <div class="fi-proc__hd">
                      <span class="fi-proc-n">1</span>
                      <span class="fi-proc-txt">{{ ficheActif.test?.libelle||'—' }}</span>
                    </div>
                    <div class="fi-proc__tools">
                      <button v-for="code in getOutilsForProc(ficheActif.objNum,ficheActif.testRef,0)" :key="code"
                        class="outil-chip" :style="`--oc:${outilColor(code)}`"
                        @click="ouvrirOutilDepuisFiche(code,0)"
                      ><i class="ti ti-tool"></i> {{ code }}</button>
                      <button v-if="!isLocked" class="proc-add-btn"
                        @click="ouvrirChoixOutil(ficheActif.obj,ficheActif.test,0,ficheActif.oi,ficheActif.ti)"
                      ><i class="ti ti-plus"></i></button>
                    </div>
                  </div>
                </template>
              </div>
            </div>

            <div v-if="getOutilsPourTest(ficheActif.objNum,ficheActif.testRef).length" class="sc">
              <div class="sc-title"><i class="ti ti-layers-intersect"></i> Résultats </div>
              <div class="outils-grid">
                <div v-for="ot in getOutilsPourTest(ficheActif.objNum,ficheActif.testRef)" :key="ot.outil_code"
                  class="outil-card" :style="`--oc:${ot.color}`"
                  @click="ouvrirOutilDepuisFiche(ot.outil_code,ot.proc_idx)"
                >
                  <div class="outil-card__hd">
                    <span class="outil-card__code" :style="`background:${ot.color}`">{{ ot.outil_code }}</span>
                    <span class="outil-card__lbl">{{ ot.label }}</span>
                    <span v-if="ot.ia_score!==null" class="outil-card__score">{{ ot.ia_score }}/10</span>
                    <i class="ti ti-external-link muted-icon"></i>
                  </div>
                  <div v-if="ot.resume?.conclusion" class="outil-card__concl">{{ ot.resume.conclusion }}</div>
                </div>
              </div>
            </div>

            <div class="obs-cta" @click="ouvrirObsDepuisFiche">
              <div class="obs-cta__ico"><i class="ti ti-eye"></i></div>
              <div class="obs-cta__body">
                <div class="obs-cta__title">Observations  (FRAP)</div>
                <div class="obs-cta__sub">Constats, causes, risques et recommandations</div>
              </div>
              <div class="row-g4">
                <span v-if="obsXIVLiee" class="tag tag--green"><i class="ti ti-check"></i> Liée</span>
                <button class="btn btn--obs">
                  <i class="ti ti-arrow-right"></i>
                  {{ obsXIVLiee ? 'Ouvrir' : 'Créer' }}
                </button>
              </div>
            </div>
          </div>

          <div class="page-footer">
            <button class="btn btn--ghost" @click="ficheActif=null">Fermer</button>
            <button v-if="!isLocked" class="btn btn--save" :disabled="processing" @click="submit(false)">
              <i class="ti ti-device-floppy"></i> Enregistrer
            </button>
          </div>
        </div>

        <!-- ◈ OBSERVATION XIV — vue pleine page -->
        <div v-if="obsVueActif" class="page-view">
          <div class="page-header page-header--obs">
            <button class="btn-back btn-back--light" @click="fermerObs">
              <i class="ti ti-arrow-left"></i> Retour
            </button>
            <div class="page-header__center">
             
              <div>
                <div class="page-title" style="color:#fff">Observation</div>
                <div class="page-sub obs-sub-light" v-if="obsContext">
                  <span class="obs-ref-badge">{{ obsContext.testRef }}</span>
                  · Obj. {{ obsContext.objNum }}
                  <span v-if="getResultat(obsContext.objNum,obsContext.testRef)"
                    class="res-pill ml4" :class="'rp--'+getResultat(obsContext.objNum,obsContext.testRef)"
                  >{{ resLbl(getResultat(obsContext.objNum,obsContext.testRef)) }}</span>
                </div>
              </div>
            </div>
            <div class="row-g4">
              <span v-if="obsLoadingBD" class="spin spin--sm"></span>
              <button v-if="!isLocked && !obsLoadingBD" class="btn btn--obs" :disabled="obsSaving" @click="sauvegarderObservation">
                <span v-if="obsSaving" class="spin"></span>
                <i v-else class="ti ti-device-floppy"></i>
                {{ obsSaving ? 'Sauvegarde…' : 'Enregistrer' }}
              </button>
            </div>
          </div>

          <div v-if="obsLoadingBD" class="page-body loading-center">
            <span class="spin spin--lg"></span>
            <p class="muted-sm">Chargement…</p>
          </div>

          <div v-else class="page-body">
            <div v-if="obsContext && getResultat(obsContext.objNum,obsContext.testRef)"
              class="result-banner"
              :class="'rb--'+getResultat(obsContext.objNum,obsContext.testRef)"
            >
              <i class="ti ti-info-circle"></i>
              Résultat <strong>{{ obsContext.testRef }}</strong> :
              <strong>{{ resLbl(getResultat(obsContext.objNum,obsContext.testRef)) }}</strong>
            </div>

            <div class="sc">
              <div class="sc-title"><span class="sec-num">1</span><i class="ti ti-file-description"></i> Description du Constat</div>
              <div class="form-grid form-grid--2">
                <div class="fg">
                  <label class="lbl">Intitulé du problème</label>
                  <input type="text" class="inp-f" v-model="obsFormXIV.intitule_probleme" :disabled="isLocked" placeholder="Ex : Absence de rapprochement bancaire"/>
                </div>
                <div class="fg">
                  <label class="lbl">Critère / Référentiel</label>
                  <textarea class="ta-f" v-model="obsFormXIV.critere_referentiel" :disabled="isLocked" rows="2" placeholder="MPF-07, COSO…"></textarea>
                </div>
                <div class="fg fg--full">
                  <label class="lbl">
                    Faits constatés
                    <span v-if="obsFormXIV._prefilled" class="tag tag--blue ml4">Pré-rempli</span>
                  </label>
                  <textarea class="ta-f" v-model="obsFormXIV.faits_constates" :disabled="isLocked" rows="3" placeholder="Décrivez les faits, l'échantillon, les preuves…"></textarea>
                  <span class="hint">Indiquez l'échantillon, les périodes testées, les pièces consultées</span>
                </div>
              </div>

              <div class="subsec">
                <div class="subsec__hd">
                  <span><i class="ti ti-eye"></i> Points observés</span>
                  <span class="count-badge">{{ obsData.constats.length }}</span>
                  <button v-if="!isLocked" class="add-btn ml-auto"
                    @click="obsData.constats.push({element_observe:'',conforme_referentiel:'',ecart_constate:'',risque_associe:'',preuve:''})"
                  ><i class="ti ti-plus"></i> Ajouter</button>
                </div>
                <div class="tbl-scroll">
                  <table class="data-tbl">
                    <thead>
                      <tr>
                        <th style="width:28px">#</th>
                        <th>Élément observé</th>
                        <th style="width:90px">Conforme ?</th>
                        <th>Écart</th>
                        <th>Risque</th>
                        <th style="width:90px">Preuve</th>
                        <th v-if="!isLocked" style="width:28px"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="!obsData.constats.length">
                        <td colspan="7" class="tbl-empty">Aucun constat — cliquez Ajouter</td>
                      </tr>
                      <tr v-for="(c,ci) in obsData.constats" :key="ci"
                        :class="{'tr-row--nc': c.conforme_referentiel==='non','tr-row--ok': c.conforme_referentiel==='oui','tr-row--wn': c.conforme_referentiel==='partiel'}"
                      >
                        <td class="tc muted-sm">{{ ci+1 }}</td>
                        <td><textarea class="ta-sm" v-model="c.element_observe" rows="2" :disabled="isLocked"></textarea></td>
                        <td class="tc">
                          <select class="sel-sm" v-model="c.conforme_referentiel" :disabled="isLocked">
                            <option value="">—</option>
                            <option value="oui">✅ Oui</option>
                            <option value="non">❌ Non</option>
                            <option value="partiel">⚠️ Partiel</option>
                          </select>
                        </td>
                        <td><textarea class="ta-sm" v-model="c.ecart_constate" rows="2" :disabled="isLocked"></textarea></td>
                        <td><textarea class="ta-sm" v-model="c.risque_associe" rows="2" :disabled="isLocked"></textarea></td>
                        <td><input class="inp-sm" type="text" v-model="c.preuve" :disabled="isLocked" placeholder="Réf. doc…"/></td>
                        <td v-if="!isLocked" class="tc">
                          <button class="del-btn" @click="obsData.constats.splice(ci,1)"><i class="ti ti-trash"></i></button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <div class="sc">
              <div class="sc-title"><span class="sec-num">2</span><i class="ti ti-list-check"></i> Causes Identifiées</div>
              <div class="chk-grid">
                <label v-for="cause in causesList" :key="cause.value" class="chk-item">
                  <input type="checkbox" :value="cause.value" v-model="obsFormXIV.causes_selection" :disabled="isLocked"/>
                  <span>{{ cause.label }}</span>
                </label>
              </div>
              <div class="fg mt8 px">
                <label class="lbl">Autres causes</label>
                <input type="text" class="inp-f" v-model="obsFormXIV.causes_autres" :disabled="isLocked" placeholder="Précisez…"/>
              </div>
            </div>

            <div class="sc">
              <div class="sc-title"><span class="sec-num">3</span><i class="ti ti-alert-triangle"></i> Conséquences / Risques</div>
              <div class="chk-grid px">
                <label v-for="risk in risksList" :key="risk.key" class="chk-item">
                  <input type="checkbox" :value="risk.key" v-model="obsFormXIV.consequences_selection" :disabled="isLocked"/>
                  <span :style="{color:risk.color}">{{ risk.label }}</span>
                </label>
              </div>
              <div class="fg mt8 px">
                <label class="lbl">Description des conséquences</label>
                <textarea class="ta-f" v-model="obsFormXIV.consequences_description" :disabled="isLocked" rows="2" placeholder="Risques et impacts potentiels…"></textarea>
              </div>
            </div>

            <div class="sc">
              <div class="sc-title"><span class="sec-num">4</span><i class="ti ti-gauge"></i> Synthèse et Niveaux</div>
              <div class="niveaux-grid">
                <div>
                  <label class="lbl">Niveau de Maîtrise</label>
                  <select class="sel-f" v-model="obsData.niveau_controle" :disabled="isLocked" :style="niveauControleStyle(obsData.niveau_controle)">
                    <option value="">— Choisir —</option>
                    <option value="1_faible">1 – Faible</option>
                    <option value="2_moyen">2 – Moyen</option>
                    <option value="3_satisfaisant">3 – Satisfaisant</option>
                    <option value="4_bon">4 – Bon</option>
                    <option value="5_excellent">5 – Excellent</option>
                  </select>
                </div>
                <div>
                  <label class="lbl">Niveau de Synthèse</label>
                  <select class="sel-f" v-model="obsData.niveau_synthese" :disabled="isLocked" :style="niveauSyntheseStyle(obsData.niveau_synthese)">
                    <option value="">— Choisir —</option>
                    <option value="conforme">✅ Conforme</option>
                    <option value="a_ameliorer">🔶 À améliorer</option>
                    <option value="insuffisant">🔴 Insuffisant</option>
                    <option value="critique">⛔ Critique</option>
                  </select>
                </div>
                <div>
                  <label class="lbl">Points forts</label>
                  <textarea class="ta-f" v-model="obsData.points_forts" :disabled="isLocked" rows="2" placeholder="Bonnes pratiques…"></textarea>
                </div>
                <div>
                  <label class="lbl">Conclusion générale</label>
                  <textarea class="ta-f" v-model="obsData.conclusion" :disabled="isLocked" rows="2" placeholder="Conclusion…"></textarea>
                </div>
              </div>
            </div>

            <div class="sc">
              <div class="subsec__hd mb8">
                <span class="sc-title" style="margin:0"><span class="sec-num">5</span><i class="ti ti-bulb"></i> Recommandations</span>
                <span class="count-badge ml4">{{ obsData.recommandations.length }}</span>
                <button v-if="!isLocked" class="add-btn ml-auto"
                  @click="obsData.recommandations.push({recommandation:'',responsable:'',date_prevue:'',commentaire_auditeur:'',commentaire_audite:''})"
                ><i class="ti ti-plus"></i> Ajouter</button>
              </div>
              <div class="tbl-scroll">
                <table class="data-tbl">
                  <thead>
                    <tr>
                      <th style="width:28px">#</th>
                      <th>Recommandation</th>
                      <th style="width:120px">Responsable</th>
                      <th style="width:100px">Date prévue</th>
                      <th>Comm. auditeur</th>
                      <th v-if="!isLocked" style="width:28px"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="!obsData.recommandations.length">
                      <td colspan="6" class="tbl-empty">Aucune recommandation</td>
                    </tr>
                    <tr v-for="(rec,ri) in obsData.recommandations" :key="ri">
                      <td class="tc muted-sm">{{ ri+1 }}</td>
                      <td><textarea class="ta-sm" v-model="rec.recommandation" rows="2" :disabled="isLocked" placeholder="Action à mettre en place…"></textarea></td>
                      <td><input class="inp-sm" type="text" v-model="rec.responsable" :disabled="isLocked" placeholder="Nom / Fonction"/></td>
                      <td><input class="inp-sm" type="date" v-model="rec.date_prevue" :disabled="isLocked"/></td>
                      <td><textarea class="ta-sm" v-model="rec.commentaire_auditeur" rows="2" :disabled="isLocked" placeholder="…"></textarea></td>
                      <td v-if="!isLocked" class="tc">
                        <button class="del-btn" @click="obsData.recommandations.splice(ri,1)"><i class="ti ti-trash"></i></button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="sc">
              <div class="sc-title"><span class="sec-num">6</span><i class="ti ti-info-circle"></i> Métadonnées</div>
              <div class="form-grid form-grid--3 px mt8">
                <div class="fg"><label class="lbl">Date d'observation</label><input type="date" class="inp-f" v-model="obsData.date_observation" :disabled="isLocked"/></div>
                <div class="fg"><label class="lbl">Heure début</label><input type="time" class="inp-f" v-model="obsData.heure_debut" :disabled="isLocked"/></div>
                <div class="fg"><label class="lbl">Heure fin</label><input type="time" class="inp-f" v-model="obsData.heure_fin" :disabled="isLocked"/></div>
                <div class="fg"><label class="lbl">Auditeur</label><input type="text" class="inp-f" v-model="obsData.auditeur" :disabled="isLocked" :placeholder="props.auditeurNom"/></div>
                <div class="fg"><label class="lbl">Localisation</label><input type="text" class="inp-f" v-model="obsData.localisation" :disabled="isLocked" placeholder="Magasin central…"/></div>
                <div class="fg"><label class="lbl">Interlocuteurs</label><input type="text" class="inp-f" v-model="obsData.interlocuteurs_presents" :disabled="isLocked" placeholder="Noms, fonctions…"/></div>
              </div>
            </div>
          </div>

          <div class="page-footer">
            <button class="btn btn--ghost" @click="fermerObs">Fermer</button>
            <button v-if="!isLocked && !obsLoadingBD" class="btn btn--obs" :disabled="obsSaving" @click="sauvegarderObservation">
              <span v-if="obsSaving" class="spin"></span>
              <i v-else class="ti ti-device-floppy"></i>
              {{ obsSaving ? 'Sauvegarde…' : "Enregistrer l'observation" }}
            </button>
          </div>
        </div>

      </div><!-- /main-content -->
    </div>

    <!-- ═══════════════ MODAL: SYNTHÈSE FOCI -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="syntheseModalOuverte" class="modal-ov" @click.self="fermerSynthese">
          <div class="modal-synthese">
            <div class="syn-hd">
              <div class="row-g8">
                <div class="syn-ico"><i class="ti ti-file-analytics"></i></div>
                <div>
                  <div class="syn-title">Feuille de Synthèse FOCI</div>
                  <div class="row-g4 mt2">
                    <span class="muted-sm-light">{{ missionLibelle||'—' }}</span>
                    <span v-if="form.code" class="syn-code">{{ form.code }}</span>
                    <span class="muted-sm-light"><i class="ti ti-user"></i> {{ props.auditeurNom }}</span>
                  </div>
                </div>
              </div>
              <div class="row-g4">
                <span v-if="syntheseLoading" class="row-g4 muted-sm-light"><span class="spin spin--sm"></span> Chargement…</span>
                <button class="syn-btn" @click="imprimerSynthese"><i class="ti ti-printer"></i> Imprimer</button>
                <button class="syn-btn syn-btn--pdf" @click="exporterPDF"><i class="ti ti-file-type-pdf"></i> PDF</button>
                <button class="drawer-close" @click="fermerSynthese"><i class="ti ti-x"></i></button>
              </div>
            </div>

           <div class="syn-body" id="synthese-print-zone">

  <!-- ─── EN-TÊTE FOCI (calque exact du format Excel) ─── -->
  <div class="foci-doc">

    <!-- Bandeau principal -->
    <div class="foci-doc__banner">
      <div class="foci-doc__org-block">
        <div class="foci-doc__zone-lbl">ZONE D'EN-TÊTE DE LA STRUCTURE AUDIT INTERNE</div>
        <input v-if="!isLocked" type="text" class="inp-sy" v-model="syntheseData.entite_org"
          placeholder="Nom / Logo de la structure Audit Interne…"/>
        <div v-else class="foci-doc__org-val">{{ syntheseData.entite_org || '—' }}</div>
      </div>

      <div class="foci-doc__title-block">
        <div class="foci-doc__main-title">Feuille d'observations Contrôle Interne (FOCI)</div>
        <div class="foci-doc__subtitle">État de synthèse des FRAP-QCI de mission</div>
      </div>

      <div class="foci-doc__date-block">
        <div class="foci-doc__field-lbl">Date</div>
        <input v-if="!isLocked" type="date" class="inp-sy" v-model="syntheseData.date_edition"/>
        <div v-else class="foci-doc__field-val">{{ fmtDate(syntheseData.date_edition) || '—' }}</div>
      </div>
    </div>

    <!-- Ligne d'identification -->
    <div class="foci-doc__ident">
      <div class="foci-doc__ident-cell">
        <div class="foci-doc__field-lbl">Code phase</div>
        <input v-if="!isLocked" type="text" class="inp-sy" v-model="syntheseData.code_phase"
          placeholder="PH-001"/>
        <div v-else class="foci-doc__field-val">{{ syntheseData.code_phase || '—' }}</div>
      </div>
      <div class="foci-doc__ident-cell">
        <div class="foci-doc__field-lbl">Code Mission</div>
        <input v-if="!isLocked" type="text" class="inp-sy" v-model="syntheseData.code_mission"
          placeholder="M-2024-01"/>
        <div v-else class="foci-doc__field-val">{{ syntheseData.code_mission || '—' }}</div>
      </div>
      <div class="foci-doc__ident-cell foci-doc__ident-cell--wide">
        <div class="foci-doc__field-lbl">Intitulé de la mission</div>
        <div class="foci-doc__field-val fw-600">{{ missionLibelle || '—' }}</div>
      </div>
      <div class="foci-doc__ident-cell">
        <div class="foci-doc__field-lbl">Auditeur</div>
        <div class="foci-doc__field-val">{{ props.auditeurNom || '—' }}</div>
      </div>
    </div>

    <!-- Ligne Entité -->
    <div class="foci-doc__entite">
      <div class="foci-doc__entite-lbl">ENTITÉ AUDITÉE</div>
      <input v-if="!isLocked" type="text" class="inp-sy foci-doc__entite-inp"
        v-model="syntheseData.entite" placeholder="Nom de l'entité auditée…"/>
      <div v-else class="foci-doc__field-val fw-600">{{ syntheseData.entite || '—' }}</div>
    </div>
  </div>

  <!-- ─── TABLEAU FOCI / FRAP ─── -->
  <div class="foci-card">
    <div class="foci-card__hd">
      <i class="ti ti-table"></i>
      Feuille d'Observations CI (FOCI) — FRAP
      <span class="sy-badge">{{ syntheseData.frap_lignes.length }}</span>
      <button v-if="!isLocked" class="add-btn ml-auto" @click="addFrapLigne">
        <i class="ti ti-plus"></i> Ajouter FRAP
      </button>
    </div>

    <div v-if="!syntheseData.frap_lignes.length" class="sy-empty">
      <i class="ti ti-clipboard-off"></i>
      Aucune FRAP enregistrée — cliquez « Ajouter FRAP » pour commencer.
    </div>

    <div v-else class="tbl-scroll">
      <table class="foci-tbl">
        <colgroup>
          <col style="width:82px"/>   <!-- N° FRAP -->
          <col style="width:46px"/>   <!-- NCI -->
          <col/>                      <!-- Faits/Constats -->
          <col/>                      <!-- Problèmes -->
          <col/>                      <!-- Causes -->
          <col/>                      <!-- Impacts -->
          <col/>                      <!-- Solutions/Reco -->
          <col/>                      <!-- Actions convenues -->
          <col/>                      <!-- Points forts -->
          <col style="width:92px"/>   <!-- Échéance -->
          <col style="width:115px"/>  <!-- Responsable -->
          <col style="width:95px"/>   <!-- Livrables -->
          <col v-if="!isLocked" style="width:28px"/>
        </colgroup>
        <thead>
          <tr class="foci-thead-top">
            <th rowspan="2" class="foci-th-center">N° FRAP</th>
            <th rowspan="2" class="foci-th-center" title="Niveau Contrôle Interne">NCI</th>
            <th colspan="4" class="foci-th-group foci-th-constats">
              Faits · Problèmes · Causes · Impacts
            </th>
            <th colspan="2" class="foci-th-group foci-th-recommandations">
              Recommandations &amp; Actions
            </th>
            <th rowspan="2">Points forts</th>
            <th rowspan="2">Date d'échéance</th>
            <th rowspan="2">Personne responsable</th>
            <th rowspan="2">Livrables</th>
            <th v-if="!isLocked" rowspan="2"></th>
          </tr>
          <tr class="foci-thead-sub">
            <th class="foci-th-sub">
              FAIT / CONSTATS
              <span class="foci-th-hint">(Provenant des FRAP-QCI)</span>
            </th>
            <th class="foci-th-sub">Problèmes</th>
            <th class="foci-th-sub">Causes</th>
            <th class="foci-th-sub">Impacts</th>
            <th class="foci-th-sub">Solutions / Recommandation</th>
            <th class="foci-th-sub">
              Commentaires de l'audité
              <span class="foci-th-hint">/ Actions convenues</span>
            </th>
          </tr>
        </thead>

        <tbody>
          <template v-for="(grp, gi) in frapGrouped" :key="gi">

            <!-- ── Ligne Objectif de contrôle ── -->
            <tr class="foci-obj-tr">
              <td colspan="2" class="foci-sec-label foci-sec-label--obj">
                Objectif de contrôle
                <span class="foci-sec-hint">(regroupement)</span>
              </td>
              <td :colspan="isLocked ? 10 : 11" class="foci-sec-val foci-sec-val--obj">
                <input v-if="!isLocked" type="text" class="inp-sm foci-sec-input"
                  :value="grp.objectif"
                  @change="updateGrpObjectif(grp, ($event.target as HTMLInputElement).value)"
                  placeholder="Libellé de l'objectif de contrôle…"/>
                <span v-else class="foci-sec-text">{{ grp.objectif || '(non classé)' }}</span>
              </td>
            </tr>

            <template v-for="(rub, ri) in grp.rubriques" :key="ri">

              <!-- ── Ligne Rubrique ── -->
              <tr class="foci-rub-tr">
                <td colspan="2" class="foci-sec-label foci-sec-label--rub">
                  Rubrique
                </td>
                <td :colspan="isLocked ? 10 : 11" class="foci-sec-val foci-sec-val--rub">
                  <input v-if="!isLocked" type="text" class="inp-sm foci-sec-input"
                    :value="rub.rubrique"
                    @change="updateRubrique(rub, ($event.target as HTMLInputElement).value)"
                    placeholder="Libellé rubrique…"/>
                  <span v-else class="foci-sec-text">{{ rub.rubrique || '—' }}</span>
                </td>
              </tr>

              <!-- ── Ligne Sous-rubrique ── -->
              <tr class="foci-subrub-tr">
                <td colspan="2" class="foci-sec-label foci-sec-label--sub">
                  Sous-rubrique
                  <span class="foci-sec-hint">(si existant)</span>
                </td>
                <td :colspan="isLocked ? 10 : 11" class="foci-sec-val foci-sec-val--sub">
                  <input v-if="!isLocked" type="text" class="inp-sm foci-sec-input"
                    :value="rub.sousRubrique"
                    @change="updateSousRubrique(rub, ($event.target as HTMLInputElement).value)"
                    placeholder="Libellé sous-rubrique…"/>
                  <span v-else class="foci-sec-text">{{ rub.sousRubrique || '—' }}</span>
                </td>
              </tr>

              <!-- ── Lignes FRAP ── -->
              <tr v-for="l in rub.lignes" :key="l._idx"
                class="foci-row"
                :class="'nci--' + (l.niveau_ci || '')"
              >
                <!-- N° FRAP -->
                <td class="foci-num-cell">
                  <input v-if="!isLocked" type="text" class="inp-sm" style="width:72px;text-align:center"
                    v-model="syntheseData.frap_lignes[l._idx].num_frap"/>
                  <span v-else class="frap-num">{{ l.num_frap }}</span>
                </td>
                <!-- NCI -->
                <td class="tc">
                  <select v-if="!isLocked" class="sel-sm foci-nci-sel"
                    v-model="syntheseData.frap_lignes[l._idx].niveau_ci">
                    <option value="">—</option>
                    <option v-for="n in 5" :key="n" :value="String(n)">{{ n }}</option>
                  </select>
                  <span v-else class="nci-badge" :class="'nci-b--' + l.niveau_ci">
                    {{ l.niveau_ci || '—' }}
                  </span>
                </td>
                <!-- Faits / Constats -->
                <td>
                  <textarea v-if="!isLocked" class="ta-sm"
                    v-model="syntheseData.frap_lignes[l._idx].faits_constats"
                    rows="3" placeholder="Libellé fait / constat…"></textarea>
                  <span v-else class="cell-txt">{{ l.faits_constats || '—' }}</span>
                </td>
                <!-- Problèmes -->
                <td>
                  <textarea v-if="!isLocked" class="ta-sm"
                    v-model="syntheseData.frap_lignes[l._idx].problemes"
                    rows="3" placeholder="Libellé problème…"></textarea>
                  <span v-else class="cell-txt">{{ l.problemes || '—' }}</span>
                </td>
                <!-- Causes -->
                <td>
                  <textarea v-if="!isLocked" class="ta-sm"
                    v-model="syntheseData.frap_lignes[l._idx].causes"
                    rows="3" placeholder="Libellé cause…"></textarea>
                  <span v-else class="cell-txt">{{ l.causes || '—' }}</span>
                </td>
                <!-- Impacts -->
                <td>
                  <textarea v-if="!isLocked" class="ta-sm"
                    v-model="syntheseData.frap_lignes[l._idx].impacts"
                    rows="3" placeholder="Libellé impact…"></textarea>
                  <span v-else class="cell-txt">{{ l.impacts || '—' }}</span>
                </td>
                <!-- Solutions / Recommandation -->
                <td>
                  <textarea v-if="!isLocked" class="ta-sm"
                    v-model="syntheseData.frap_lignes[l._idx].recommandations"
                    rows="3" placeholder="Solution / Recommandation…"></textarea>
                  <span v-else class="cell-txt">{{ l.recommandations || '—' }}</span>
                </td>
                <!-- Commentaires audité / Actions convenues -->
                <td>
                  <textarea v-if="!isLocked" class="ta-sm"
                    v-model="syntheseData.frap_lignes[l._idx].actions_convenues"
                    rows="3" placeholder="Commentaire audité / Actions convenues…"></textarea>
                  <span v-else class="cell-txt">{{ l.actions_convenues || '—' }}</span>
                </td>
                <!-- Points forts -->
                <td>
                  <textarea v-if="!isLocked" class="ta-sm"
                    v-model="syntheseData.frap_lignes[l._idx].points_forts"
                    rows="3" placeholder="Points forts…"></textarea>
                  <span v-else class="cell-txt">{{ l.points_forts || '—' }}</span>
                </td>
                <!-- Date d'échéance -->
                <td>
                  <input v-if="!isLocked" type="date" class="inp-sm"
                    v-model="syntheseData.frap_lignes[l._idx].date_echeance"/>
                  <span v-else class="cell-txt">{{ fmtDate(l.date_echeance) || '—' }}</span>
                </td>
                <!-- Personne responsable -->
                <td>
                  <input v-if="!isLocked" type="text" class="inp-sm"
                    v-model="syntheseData.frap_lignes[l._idx].responsable"
                    placeholder="Nom / Fonction…"/>
                  <span v-else class="cell-txt">{{ l.responsable || '—' }}</span>
                </td>
                <!-- Livrables -->
                <td>
                  <input v-if="!isLocked" type="text" class="inp-sm"
                    v-model="syntheseData.frap_lignes[l._idx].livrables"
                    placeholder="Livrable…"/>
                  <span v-else class="cell-txt">{{ l.livrables || '—' }}</span>
                </td>
                <!-- Supprimer -->
                <td v-if="!isLocked" class="tc">
                  <button class="del-btn" @click="removeFrapLigne(l._idx)">
                    <i class="ti ti-trash"></i>
                  </button>
                </td>
              </tr>

            </template><!-- /rubriques -->
          </template><!-- /groupes objectifs -->
        </tbody>
      </table>
    </div><!-- /tbl-scroll -->

    <div class="foci-note">
      <i class="ti ti-info-circle"></i>
      NB — Une seule FOCI pour regrouper toutes les FRAP-QCI d'une seule et même mission.
      Les FRAP sont classées par ordre chronologique dans la sous-rubrique et rubrique.
      Si on clique sur N° FRAP, le lien ouvre la FRAP correspondante.
    </div>
  </div><!-- /foci-card -->

</div>

            <div class="syn-ft">
              <div class="muted-sm"><i class="ti ti-info-circle"></i> Données en temps réel</div>
              <div class="row-g4">
                <button class="btn btn--ghost" @click="fermerSynthese">Fermer</button>
                <button v-if="!isLocked" class="btn btn--save" @click="saveSynthese" :disabled="savingSynthese">
                  <span v-if="savingSynthese" class="spin"></span>
                  <i v-else class="ti ti-device-floppy"></i> Enregistrer
                </button>
                <button class="btn" style="background:#1e3a5f;color:#fff" @click="imprimerSynthese"><i class="ti ti-printer"></i> Imprimer</button>
                <button class="btn" style="background:#dc2626;color:#fff" @click="exporterPDF"><i class="ti ti-file-type-pdf"></i> PDF</button>
              </div>
            </div>
          </div><!-- /modal-synthese -->
        </div>
      </Transition>
    </Teleport>

    <!-- ═══════════════ MODAL: CHOIX OUTILS -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="om.visible" class="modal-ov" @click.self="om.visible=false">
          <div class="modal-sm modal--outils">
            <div class="modal-hd">
              <div class="row-g8">
                <div class="drawer-ico" style="background:#ede9fe;color:#5b21b6"><i class="ti ti-tool"></i></div>
                <div>
                  <div class="modal-title">Outils IFACI</div>
                  <div class="muted-sm">{{ om.testRef }} · Procédure {{ (om.procIdx??0)+1 }}</div>
                </div>
              </div>
              <button class="icon-btn" @click="om.visible=false"><i class="ti ti-x"></i></button>
            </div>
            <div class="om-selbar">
              <div class="row-g4" style="flex-wrap:wrap">
                <span v-for="code in om.selected" :key="code" class="outil-chip" :style="`--oc:${outilColor(code)}`">
                  {{ code }} <button class="chip-rm" @click="omToggle(code)">×</button>
                </span>
              </div>
              <button v-if="om.selected.length" class="add-btn" style="background:#fee2e2;border-color:#fecaca;color:#dc2626" @click="om.selected=[]">Effacer</button>
            </div>
            <div class="om-body">
              <div class="om-grid">
                <button v-for="outil in props.outilsIfaci" :key="outil.code"
                  class="om-card" :class="{'om-card--sel': om.selected.includes(outil.code)}"
                  @click="omToggle(outil.code)"
                >
                  <div class="om-card__num" :style="`background:${outil.color}`">{{ outil.code }}</div>
                  <div class="om-card__lbl">{{ outil.label }}</div>
                </button>
              </div>
            </div>
            <div class="modal-ft">
              <button class="btn btn--ghost" @click="om.visible=false">Annuler</button>
              <button class="btn btn--save" :disabled="!om.selected.length" @click="omConfirmer">Ouvrir</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- TOAST -->
    <Teleport to="body">
      <Transition name="toast-pop">
        <div v-if="toast.show" class="toast" :class="'toast--'+toast.type">
          <i :class="toast.type==='success' ? 'ti ti-circle-check':'ti ti-alert-circle'"></i>
          {{ toast.msg }}
          <button class="toast__x" @click="toast.show=false"><i class="ti ti-x"></i></button>
        </div>
      </Transition>
    </Teleport>
  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'

const props = withDefaults(defineProps<{
  mission?: any
  auditorRole?: string
  auditeurNom?: string
  missionId?: number
  assignmentId?: number
  form?: any
  phaseAuditeurs?: any[]
  programmeData?: { found:boolean; programme_code?:string; programme_label?:string; objectifs?:any[]; total_objectifs?:number; total_tests?:number }
  outilsIfaci?: { code:string; label:string; icon:string; color:string }[]
  outilsParTest?: Record<string,any[]>
  processus?: any[]
  risquesMission?: any[]
  rciLignes?: any[]
  missionContext?: { mission_id?:number; assignment_id?:number; mission_libelle?:string; code_mission?:string }
  backUrl?: string
  urlStore?: string
  urlUpdate?: string
  urlSoumettre?: string
  urlValider?: string
  urlSaveOutil?: string
  urlLoadOutil?: string
}>(), {
  auditorRole: 'Auditeur', auditeurNom: '',
  phaseAuditeurs: ()=>[],
  outilsIfaci: ()=>[
    { code:'I',    label:"Grille d'Entretien",          icon:'ti-message-question', color:'#1e40af' },
    { code:'II',   label:"Analyse des Tâches",          icon:'ti-layout-list',      color:'#065f46' },
    { code:'III',  label:'Diagramme de Flux',           icon:'ti-git-branch',       color:'#6d28d9' },
    { code:'IV',   label:'Approche Processus',          icon:'ti-sitemap',          color:'#b45309' },
    { code:'V',    label:'Test de Cheminement',         icon:'ti-route',            color:'#be185d' },
    { code:'VI',   label:'Hiérarchisation des Risques', icon:'ti-alert-triangle',   color:'#dc2626' },
    { code:'VII',  label:"Référentiel d'Audit",         icon:'ti-table',            color:'#0891b2' },
    { code:'VIII', label:'Cause / Effet (Ishikawa)',    icon:'ti-git-merge',        color:'#7c3aed' },
    { code:'IX',   label:'Questionnaire CI (QCI)',      icon:'ti-clipboard-check',  color:'#0f766e' },
    { code:'X',    label:'Brainstorming',               icon:'ti-bulb',             color:'#d97706' },
    { code:'XI',   label:"Piste d'Audit",               icon:'ti-route-scan',       color:'#4f46e5' },
    { code:'XII',  label:'Circularisation',             icon:'ti-mail-forward',     color:'#0369a1' },
    { code:'XIII', label:'Audit Analytique',            icon:'ti-chart-line',       color:'#15803d' },
    { code:'XIV',  label:'Observation Directe',         icon:'ti-eye',              color:'#9333ea' },
    { code:'XV',   label:'Échantillonnage Statistique', icon:'ti-calculator',       color:'#0c4a6e' },
  ],
  programmeData: ()=>({ found:false, objectifs:[], total_objectifs:0, total_tests:0 }),
  outilsParTest: ()=>({}),
  processus: ()=>[], risquesMission: ()=>[], rciLignes: ()=>[],
  missionContext: ()=>({}), backUrl: '/auditor',
})

// ── STATIC DATA ────────────────────────────────────────────────
const causesList = [
  { value:'absence_procedure',       label:'Absence de procédure' },
  { value:'procedure_non_appliquee', label:'Procédure non appliquée' },
  { value:'manque_supervision',      label:'Manque de supervision' },
  { value:'manque_ressources',       label:'Manque de ressources' },
  { value:'defaillance_systeme',     label:'Défaillance système' },
  { value:'erreur_humaine',          label:'Erreur humaine' },
  { value:'formation_insuffisante',  label:'Formation insuffisante' },
  { value:'fraude_intentionnel',     label:'Fraude / intentionnel' },
]
const risksList = [
  { key:'financier',     label:'Financier',                 color:'#dc2626' },
  { key:'operationnel',  label:'Opérationnel',              color:'#d97706' },
  { key:'juridique',     label:'Juridique / réglementaire', color:'#7c3aed' },
  { key:'reputationnel', label:'Réputationnel',             color:'#0c4e6e' },
  { key:'fraude',        label:'Fraude',                    color:'#b91c1c' },
  { key:'qualite_info',  label:"Qualité de l'information",  color:'#15803d' },
  { key:'continuite',    label:"Continuité d'activité",     color:'#6b21a5' },
]
const niveauxMaitrise = [
  { value:'1_faible',       num:'1', label:'Faible',       bg:'#fee2e2', color:'#dc2626', border:'#fca5a5' },
  { value:'2_moyen',        num:'2', label:'Moyen',        bg:'#fef3c7', color:'#b45309', border:'#fcd34d' },
  { value:'3_satisfaisant', num:'3', label:'Satisfaisant', bg:'#fefce8', color:'#854d0e', border:'#fde68a' },
  { value:'4_bon',          num:'4', label:'Bon',          bg:'#dcfce7', color:'#166534', border:'#86efac' },
  { value:'5_excellent',    num:'5', label:'Excellent',    bg:'#d1fae5', color:'#065f46', border:'#6ee7b7' },
]
const niveauxSynthese = [
  { value:'conforme',    emoji:'✅', label:'Conforme',    bg:'#d1fae5', color:'#065f46', border:'#6ee7b7' },
  { value:'a_ameliorer', emoji:'🔶', label:'À améliorer', bg:'#fef3c7', color:'#92400e', border:'#fcd34d' },
  { value:'insuffisant', emoji:'🔴', label:'Insuffisant', bg:'#fee2e2', color:'#dc2626', border:'#fca5a5' },
  { value:'critique',    emoji:'⛔', label:'Critique',    bg:'#fce7f3', color:'#9d174d', border:'#f9a8d4' },
]
const niveauSyntheseLabels: Record<string,string> = {
  'conforme':'✅ Conforme','a_ameliorer':'🔶 À améliorer','insuffisant':'🔴 Insuffisant','critique':'⛔ Critique',
}
const niveauCtrlColors: Record<string,any> = {
  '1_faible':{bg:'#fee2e2',color:'#dc2626',border:'#fca5a5'},
  '2_moyen':{bg:'#fef3c7',color:'#b45309',border:'#fcd34d'},
  '3_satisfaisant':{bg:'#fefce8',color:'#854d0e',border:'#fde68a'},
  '4_bon':{bg:'#dcfce7',color:'#166534',border:'#86efac'},
  '5_excellent':{bg:'#d1fae5',color:'#065f46',border:'#6ee7b7'},
}
const niveauSynthColors: Record<string,any> = {
  'conforme':{bg:'#d1fae5',color:'#065f46',border:'#6ee7b7'},
  'a_ameliorer':{bg:'#fef3c7',color:'#92400e',border:'#fcd34d'},
  'insuffisant':{bg:'#fee2e2',color:'#dc2626',border:'#fca5a5'},
  'critique':{bg:'#fce7f3',color:'#9d174d',border:'#f9a8d4'},
}

const syntheseMetaFields = computed(()=>[
  { key:'code_phase',   label:'Code Phase',    editable:true, type:'text', placeholder:'PH-001' },
  { key:'code_mission', label:'Code Mission',  editable:true, type:'text', placeholder:props.missionContext?.code_mission },
  { key:'entite',       label:'Entité auditée',editable:true, type:'text', placeholder:'Entité…' },
  { key:'date_edition', label:"Date d'édition",editable:true, type:'date' },
  { key:'programme',    label:'Programme',     editable:false, display:()=>`${programmeData.value.programme_code||'—'} — ${programmeData.value.programme_label||'—'}` },
])
const programmeData = computed(()=>props.programmeData ?? { found:false, objectifs:[], total_objectifs:0, total_tests:0 })

// ── STATE ───────────────────────────────────────────────────────
const form = reactive<any>({ id:null, code:'FT-AUTO', validation_status:'draft', validation_note:'', ...(props.form??{}) })
const dynUrls = reactive({ update:props.urlUpdate??null, soumettre:props.urlSoumettre??null, valider:props.urlValider??null })
const processing     = ref(false)
const savingSynthese = ref(false)
const toast          = ref({ show:false, type:'success', msg:'' })
let _tt: ReturnType<typeof setTimeout>|null = null

const ficheActif   = ref<any>(null)
const obsVueActif  = ref(false)
const obsContext   = ref<any>(null)
const obsSaving    = ref(false)
const obsLoadingBD = ref(false)
const syntheseModalOuverte = ref(false)
const syntheseLoading      = ref(false)
const showDD = ref('')

const resultatsMap   = reactive<Record<string,{resultat:string}>>({})
const outilsProcsMap = reactive<Record<string,string[]>>({})
const obsXIVCache    = reactive<Record<string,any>>({})


let frapCounter = 1

const obsFormXIV = reactive<any>({
  intitule_probleme:'', faits_constates:'', critere_referentiel:'',
  causes_selection:[] as string[], causes_autres:'',
  consequences_selection:[] as string[], consequences_description:'', _prefilled:false,
})
const obsData = reactive<any>({
  date_observation:'', heure_debut:'', heure_fin:'',
  auditeur:props.auditeurNom??'', localisation:'', interlocuteurs_presents:'',
  objectif_audit:'', tache_local_observer:'', elements_verifier:'', pieces_attendues:'',
  points_forts:'', conclusion:'', niveau_controle:'', niveau_synthese:'',
  constats:[] as any[], recommandations:[] as any[],
})
const om = reactive({ visible:false, selected:[] as string[], testRef:'', procIdx:null as number|null, objNum:'', obj:null as any, test:null as any, oi:0, ti:0 })

// ── COMPUTED ────────────────────────────────────────────────────
const canManage      = computed(()=>['DM','CM'].includes(props.auditorRole??''))
const isLocked       = computed(()=>form.validation_status==='validated'||(form.validation_status==='in_review'&&!canManage.value))
const missionLibelle = computed(()=>props.mission?.libelle??props.missionContext?.mission_libelle??'')
const obsXIVLiee     = computed(()=>ficheActif.value ? testHasOutil(ficheActif.value.objNum,ficheActif.value.testRef,'XIV') : false)
const obsXIVLoaded   = computed(()=>Object.values(obsXIVCache))

const allTests = computed(()=>{
  const all:Array<{resultat:string}>=[]
  props.programmeData?.objectifs?.forEach((o,oi)=>{
    o.tests?.forEach((t:any,ti:number)=>{ all.push({resultat:getResultat(o.num,tRef(t,oi,ti))}) })
  })
  return all
})
const total = computed(()=>allTests.value.length)
const pct   = (n:number)=>total.value?Math.round((n/total.value)*100):0
const tauxConformite = computed(()=>pct(getConformeCount()))
const tauxEcart      = computed(()=>pct(getEcartCount()))
const tauxNc         = computed(()=>pct(getNcCount()))
const tauxNa         = computed(()=>pct(getNaCount()))

// ── FRAP GROUPED — structure FOCI: objectif > rubrique+sous-rubrique > lignes
const frapGrouped = computed(()=>{
  const g:Record<string,any>={}
  syntheseData.frap_lignes.forEach((l,i)=>{
    const objKey = l.objectif_ctrl || '_sans_objectif'
    if(!g[objKey]) g[objKey] = { objectif: l.objectif_ctrl||'', rubriques:{} }
    // clé rubrique composée pour gérer sous-rubriques distinctes
    const rubKey = (l.rubrique||'_default') + '||' + (l.sous_rubrique||'')
    if(!g[objKey].rubriques[rubKey]) g[objKey].rubriques[rubKey] = { rubrique:l.rubrique||'', sousRubrique:l.sous_rubrique||'', lignes:[] }
    g[objKey].rubriques[rubKey].lignes.push({...l, _idx:i})
  })
  return Object.values(g).map((grp:any)=>({...grp, rubriques:Object.values(grp.rubriques)}))
})

const syntheseOutilsGroupes = computed(()=>{
  const map:Record<string,any>={}
  Object.entries(props.outilsParTest??{}).forEach(([testKey,outils])=>{
    const parts=testKey.split('::'); const testRef=parts.slice(1).join('::')
    ;(outils as any[]).forEach((ot:any)=>{
      const k=ot.outil_code
      if(!map[k]) map[k]={outil_code:k,label:props.outilsIfaci?.find(o=>o.code===k)?.label??`Outil ${k}`,color:ot.color,ia_score:ot.ia_score,resume:ot.resume,tests:[] as string[]}
      if(testRef&&!map[k].tests.includes(testRef)) map[k].tests.push(testRef)
    })
  })
  return Object.values(map).sort((a:any,b:any)=>a.outil_code.localeCompare(b.outil_code,undefined,{numeric:true}))
})

// ── HELPERS ─────────────────────────────────────────────────────
const vstLbl    = (s:string)=>({draft:'Brouillon',in_review:'En attente',validated:'Validé ✓'}[s]??s)
const resLbl    = (r:string)=>({conforme:'✅ Conforme',ecart:'⚠️ Écart',nc:'❌ Non conforme',na:'N/A'}[r]??r)
const fmtDate   = (d:string)=>d?new Date(d).toLocaleDateString('fr-FR'):''
const csrf      = ()=>(document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content??''
const tRef      = (test:any,oi:number,ti:number)=>test.ref||('T'+(oi+1)+'.'+(ti+1))
const rk        = (on:string,tr:string)=>on+'::'+tr
const pk        = (on:string,tr:string,pi:number)=>on+'::'+tr+'::'+pi
const outilColor= (code:string)=>props.outilsIfaci?.find(o=>o.code===code)?.color??'#374151'

const getResultat = (on:string,tr:string)=>resultatsMap[rk(on,tr)]?.resultat??''
const setResultat = (on:string,tr:string,v:string)=>{
  if(!resultatsMap[rk(on,tr)]) resultatsMap[rk(on,tr)]={resultat:''}
  resultatsMap[rk(on,tr)].resultat=v
}
const getConformeCount = ()=>allTests.value.filter(t=>t.resultat==='conforme').length
const getEcartCount    = ()=>allTests.value.filter(t=>t.resultat==='ecart').length
const getNcCount       = ()=>allTests.value.filter(t=>t.resultat==='nc').length
const getNaCount       = ()=>allTests.value.filter(t=>t.resultat==='na').length

const getObjectifConformeCount = (obj:any,oi:number)=>obj.tests?.filter((_t:any,ti:number)=>getResultat(obj.num,tRef(_t,oi,ti))==='conforme').length??0
const getObjectifEcartCount    = (obj:any,oi:number)=>obj.tests?.filter((_t:any,ti:number)=>getResultat(obj.num,tRef(_t,oi,ti))==='ecart').length??0
const getObjectifNcCount       = (obj:any,oi:number)=>obj.tests?.filter((_t:any,ti:number)=>getResultat(obj.num,tRef(_t,oi,ti))==='nc').length??0

const getOutilsPourTest  = (on:string,tr:string):any[]=>props.outilsParTest?.[rk(on,tr)]??[]
const getOutilsForProc   = (on:string,tr:string,pi:number):string[]=>outilsProcsMap[pk(on,tr,pi)]??[]
const testHasOutil       = (on:string,tr:string,code:string)=>Object.entries(outilsProcsMap).some(([k,v])=>k.startsWith(rk(on,tr)+'::')&&v.includes(code))
const testHasAnyOutil    = (on:string,tr:string)=>Object.keys(outilsProcsMap).some(k=>k.startsWith(rk(on,tr)+'::'))
const toggleDD           = (tr:string)=>{ showDD.value=showDD.value===tr?'':tr }
const getObjectifTexte   = ()=>ficheActif.value?.obj?.objectif||ficheActif.value?.obj?.libelle||''
const getObsConclusion   = (on:string,tr:string)=>obsXIVCache[rk(on,tr)]?.conclusion??''
const getObsNiveau       = (on:string,tr:string)=>obsXIVCache[rk(on,tr)]?.niveau_synthese??''

const niveauControleStyle = (v:string)=>{const c=niveauCtrlColors[v];return c?{background:c.bg,color:c.color,borderColor:c.border,fontWeight:'600'}:{}}
const niveauSyntheseStyle = (v:string)=>{const c=niveauSynthColors[v];return c?{background:c.bg,color:c.color,borderColor:c.border,fontWeight:'600'}:{}}
const getNiveauSyntheseStyle=(v:string)=>{const c=niveauSynthColors[v];return c?{background:c.bg,color:c.color,border:`1px solid ${c.border}`,padding:'.1rem .35rem',borderRadius:'20px',fontWeight:'700',fontSize:'.62rem',display:'inline-block'}:{}}
const toggleNiveau        = (field:string,value:string)=>{ (syntheseData as any)[field]=(syntheseData as any)[field]===value?'':value }

// ── SYNTHÈSE ────────────────────────────────────────────────────
async function ouvrirSynthese() {
  syntheseModalOuverte.value=true
  await chargerToutesObsXIV()
}
function fermerSynthese() { syntheseModalOuverte.value=false }

async function chargerToutesObsXIV() {
  if(!form.id||!props.urlLoadOutil) return
  syntheseLoading.value=true
  try {
    const promises:Promise<void>[]=[]
    props.programmeData?.objectifs?.forEach((obj,oi)=>{
      obj.tests?.forEach((test:any,ti:number)=>{
        const testRef=tRef(test,oi,ti)
        if(!testHasOutil(obj.num,testRef,'XIV')) return
        const key=rk(obj.num,testRef)
        if(obsXIVCache[key]) return
        promises.push(chargerObsXIVCache(obj.num,testRef))
      })
    })
    await Promise.all(promises)
  } catch(e){console.warn('[chargerToutesObsXIV]',e)}
  finally{syntheseLoading.value=false}
}

async function chargerObsXIVCache(objNum:string,testRef:string) {
  if(!props.urlLoadOutil||!form.id) return
  try {
    const params=new URLSearchParams({outil_code:'XIV',procedure_code:testRef,test_ref:testRef,obj_num:objNum,proc_idx:'0'})
    const res=await fetch(`${props.urlLoadOutil}?${params}`,{headers:{'Accept':'application/json','X-CSRF-TOKEN':csrf()}})
    const d=await res.json()
    if(d.success&&d.found&&d.record) {
      obsXIVCache[rk(objNum,testRef)]={...d.record,testRef,objNum,constats:d.children?.outil_observation_constats??[],recommandations:d.children?.outil_observation_recommandations??[]}
    }
  } catch(e){console.warn('[chargerObsXIVCache]',e)}
}

function imprimerSynthese() {
  const zone=document.getElementById('synthese-print-zone')
  if(!zone) return
  const win=window.open('','_blank')
  if(!win) return
  win.document.write(`<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"/><title>Synthèse FOCI — ${form.code||'FT'}</title>
  <style>*{box-sizing:border-box;margin:0;padding:0}body{font-family:'Segoe UI',Arial,sans-serif;font-size:10px;color:#1e293b;padding:10mm}
  .sy-card{border:1px solid #e2e8f0;border-radius:6px;margin-bottom:8px;overflow:hidden;break-inside:avoid}
  .sy-card__hd{background:#ede9fe;color:#5b21b6;font-size:9px;font-weight:700;padding:4px 8px;border-bottom:1px solid #ddd6fe}
  .sy-meta{display:grid;grid-template-columns:repeat(4,1fr);gap:4px;padding:6px 8px}
  .sy-meta-item label{display:block;font-size:7px;color:#94a3b8;text-transform:uppercase;margin-bottom:1px}
  .sy-val{font-size:9px;font-weight:600}
  .sy-kpi-row{display:flex;border-bottom:1px solid #e2e8f0}
  .sy-kpi{flex:1;text-align:center;padding:6px 4px;border-right:1px solid #e2e8f0}
  .sy-kv{font-size:18px;font-weight:800;line-height:1}.sy-kl{font-size:7px;text-transform:uppercase;color:#64748b}
  .foci-tbl{width:100%;border-collapse:collapse;font-size:7.5px}
  .foci-tbl th{background:#1e1b4b;color:#fff;padding:2px 3px;font-size:6.5px;text-transform:uppercase}
  .foci-tbl td{padding:2px 3px;border-bottom:1px solid #f1f5f9;border-right:1px solid #f1f5f9;vertical-align:top}
  .foci-obj-row{background:linear-gradient(135deg,#1e1b4b,#312e81);color:#fff;padding:3px 7px;font-size:8px;font-weight:700}
  .foci-rub-cell,.foci-subrub-cell{background:#f5f3ff;color:#5b21b6;font-weight:600}
  .foci-header{padding:5px 8px;background:#faf5ff;border-bottom:1px solid #e9d5ff;display:grid;grid-template-columns:repeat(6,1fr);gap:4px}
  .foci-hl{font-size:6px;color:#7c3aed;font-weight:700;text-transform:uppercase;display:block}
  .foci-hv{font-size:8px;color:#1e293b}
  .frap-num{background:#1e1b4b;color:#fff;padding:1px 3px;border-radius:2px;font-size:6.5px;font-weight:700}
  .sy-tbl{width:100%;border-collapse:collapse;font-size:8px}
  .sy-tbl th{background:#1e1b4b;color:#fff;padding:2px 4px;font-size:7px;text-transform:uppercase}
  .sy-tbl td{padding:2px 4px;border-bottom:1px solid #f1f5f9;border-right:1px solid #f1f5f9;vertical-align:top}
  .sy-obj-row td{background:#312e81;color:#fff;font-weight:700;padding:2px 6px}
  .btn,.add-btn,.del-btn,.sel-sm,.ta-sm,.inp-sm{display:none!important}
  .cell-txt{font-size:7.5px;white-space:pre-wrap}
  @page{margin:8mm;size:A4 landscape}</style>
  </head><body>${zone.innerHTML}</body></html>`)
  win.document.close()
  setTimeout(()=>{win.print();win.close()},600)
}
async function exporterPDF() { imprimerSynthese() }

// ── FRAP ────────────────────────────────────────────────────────
function addFrapLigne() {
  syntheseData.frap_lignes.push({
    num_frap:`FRAP-${String(frapCounter++).padStart(3,'0')}`,
    niveau_ci:'',objectif_ctrl:'',rubrique:'',sous_rubrique:'',
    faits_constats:'',problemes:'',causes:'',impacts:'',
    recommandations:'',actions_convenues:'',points_forts:'',
    date_echeance:'',responsable:'',livrables:'',
  })
}
function removeFrapLigne(idx:number) { syntheseData.frap_lignes.splice(idx,1) }

// ── NAVIGATION VUES ─────────────────────────────────────────────
function ouvrirFiche(obj:any,test:any,oi:number,ti:number) {
  ficheActif.value={ obj,test,objNum:obj.num,testRef:tRef(test,oi,ti),oi,ti,date:new Date().toISOString().slice(0,10) }
  obsVueActif.value=false
}

function resetObsData() {
  Object.assign(obsFormXIV,{ intitule_probleme:'',faits_constates:'',critere_referentiel:'',causes_selection:[],causes_autres:'',consequences_selection:[],consequences_description:'',_prefilled:false })
  Object.assign(obsData,{ date_observation:'',heure_debut:'',heure_fin:'',auditeur:props.auditeurNom??'',localisation:'',interlocuteurs_presents:'',objectif_audit:'',tache_local_observer:'',elements_verifier:'',pieces_attendues:'',points_forts:'',conclusion:'',niveau_controle:'',niveau_synthese:'',constats:[],recommandations:[] })
}

function ouvrirObsDirecte(obj:any,test:any,oi:number,ti:number) {
  const testRef=tRef(test,oi,ti)
  obsContext.value={ obj,test,objNum:obj.num,testRef,oi,ti,fromFiche:false }
  resetObsData()
  obsData.objectif_audit=obj.objectif??obj.libelle??''
  obsFormXIV.intitule_probleme=test.libelle??''
  obsData.tache_local_observer=test.libelle??''
  obsData.auditeur=props.auditeurNom??''
  if(test.periode_testee) obsData.elements_verifier=`Période : ${test.periode_testee}`
  if(test.taille_echantillon) obsData.tache_local_observer+=` (n=${test.taille_echantillon})`
  ficheActif.value=null
  obsVueActif.value=true
  if(testHasOutil(obj.num,testRef,'XIV')&&props.urlLoadOutil&&form.id) chargerObsBD(obj.num,testRef)
}

function ouvrirObsDepuisFiche() {
  if(!ficheActif.value) return
  const {obj,test,objNum,testRef,oi,ti}=ficheActif.value
  obsContext.value={obj,test,objNum,testRef,oi,ti,fromFiche:true}
  resetObsData()
  obsData.objectif_audit=getObjectifTexte()
  obsFormXIV.intitule_probleme=test?.libelle??''
  obsData.tache_local_observer=test?.libelle??''
  obsData.auditeur=props.auditeurNom??''
  if(test?.periode_testee) obsData.elements_verifier=`Période : ${test.periode_testee}`
  if(test?.taille_echantillon) obsData.tache_local_observer+=` (n=${test.taille_echantillon})`
  if(testHasOutil(objNum,testRef,'XIV')&&props.urlLoadOutil&&form.id) chargerObsBD(objNum,testRef)
  obsVueActif.value=true
}

function fermerObs() {
  const fromFiche=obsContext.value?.fromFiche
  const ctx=obsContext.value
  obsVueActif.value=false
  if(fromFiche&&ctx) setTimeout(()=>ouvrirFiche(ctx.obj,ctx.test,ctx.oi,ctx.ti),30)
  obsContext.value=null
}

function ouvrirOutilDepuisFiche(code:string,procIdx:number) {
  if(!ficheActif.value) return
  if(code==='XIV') { ouvrirObsDepuisFiche(); return }
  ouvrirOutil(ficheActif.value.obj,ficheActif.value.test,code,procIdx,ficheActif.value.oi,ficheActif.value.ti)
}
function updateGrpObjectif(grp: any, val: string) {
  grp.rubriques.forEach((rub: any) => {
    rub.lignes.forEach((l: any) => {
      syntheseData.frap_lignes[l._idx].objectif_ctrl = val
    })
  })
}
function updateRubrique(rub: any, val: string) {
  rub.lignes.forEach((l: any) => {
    syntheseData.frap_lignes[l._idx].rubrique = val
  })
  rub.rubrique = val
}
function updateSousRubrique(rub: any, val: string) {
  rub.lignes.forEach((l: any) => {
    syntheseData.frap_lignes[l._idx].sous_rubrique = val
  })
  rub.sousRubrique = val
}


  
    const syntheseData = reactive({
      entite_org:'',          // ← AJOUTER
      code_phase:'', code_mission:props.missionContext?.code_mission??'', entite:'',
      date_edition:new Date().toISOString().slice(0,10),
      niveau_maitrise_global:'', niveau_synthese_global:'',
      points_forts_globaux:'', axes_amelioration:'', conclusion_generale:'',
      frap_lignes:[] as any[],
    })
// ── CHARGEMENT BD ───────────────────────────────────────────────
async function chargerObsBD(objNum:string,testRef:string) {
  if(!props.urlLoadOutil||!form.id) return
  obsLoadingBD.value=true
  try {
    const params=new URLSearchParams({outil_code:'XIV',procedure_code:testRef,test_ref:testRef,obj_num:objNum,proc_idx:'0'})
    const res=await fetch(`${props.urlLoadOutil}?${params}`,{headers:{'Accept':'application/json','X-CSRF-TOKEN':csrf()}})
    const d=await res.json()
    if(d.success&&d.found&&d.record) {
      const r=d.record
      Object.assign(obsData,{
        date_observation:r.date_observation??'',heure_debut:r.heure_debut??'',heure_fin:r.heure_fin??'',
        auditeur:r.auditeur??props.auditeurNom??'',localisation:r.localisation??'',interlocuteurs_presents:r.interlocuteurs_presents??'',
        objectif_audit:r.objectif_audit??obsData.objectif_audit,tache_local_observer:r.tache_local_observer??'',
        elements_verifier:r.elements_verifier??'',pieces_attendues:r.pieces_attendues??'',
        points_forts:r.points_forts??'',conclusion:r.conclusion??'',
        niveau_controle:r.niveau_controle??r.niveau_maitrise??'',niveau_synthese:r.niveau_synthese??'',
        constats:d.children?.outil_observation_constats??[],recommandations:d.children?.outil_observation_recommandations??[],
      })
      Object.assign(obsFormXIV,{
        intitule_probleme:r.intitule_probleme??obsFormXIV.intitule_probleme,faits_constates:r.faits_constates??'',
        critere_referentiel:r.critere_referentiel??'',
        causes_selection:(()=>{try{return JSON.parse(r.causes_json??'[]')}catch{return[]}})(),
        causes_autres:r.causes_autres??'',
        consequences_selection:(()=>{try{return JSON.parse(r.consequences_json??'[]')}catch{return[]}})(),
        consequences_description:r.consequences_description??'',_prefilled:false,
      })
      obsXIVCache[rk(objNum,testRef)]={...r,testRef,objNum,constats:d.children?.outil_observation_constats??[],recommandations:d.children?.outil_observation_recommandations??[]}
    }
  } catch(e){console.warn('[chargerObsBD]',e)}
  finally{obsLoadingBD.value=false}
}

// ── SAVE OBS XIV ────────────────────────────────────────────────
async function sauvegarderObservation() {
  const ctx=obsContext.value
  if(!props.urlSaveOutil||!form.id) { showToast('success','Observation sauvegardée localement.'); return }
  obsSaving.value=true
  try {
    const payload={
      outil_code:'XIV',procedure_code:ctx?.testRef??'',test_ref:ctx?.testRef??'',obj_num:ctx?.objNum??'',proc_idx:0,
      data:{
        date_observation:obsData.date_observation,heure_debut:obsData.heure_debut,heure_fin:obsData.heure_fin,
        auditeur:obsData.auditeur,localisation:obsData.localisation,interlocuteurs_presents:obsData.interlocuteurs_presents,
        objectif_audit:obsData.objectif_audit,tache_local_observer:obsData.tache_local_observer,
        elements_verifier:obsData.elements_verifier,pieces_attendues:obsData.pieces_attendues,
        points_forts:obsData.points_forts,conclusion:obsData.conclusion,
        niveau_maitrise:obsData.niveau_controle,niveau_controle:obsData.niveau_controle,niveau_synthese:obsData.niveau_synthese,
        intitule_probleme:obsFormXIV.intitule_probleme,faits_constates:obsFormXIV.faits_constates,
        critere_referentiel:obsFormXIV.critere_referentiel,
        causes_json:JSON.stringify(obsFormXIV.causes_selection),causes_autres:obsFormXIV.causes_autres,
        consequences_json:JSON.stringify(obsFormXIV.consequences_selection),consequences_description:obsFormXIV.consequences_description,
      },
      children:{outil_observation_constats:obsData.constats,outil_observation_recommandations:obsData.recommandations},
    }
    const res=await fetch(props.urlSaveOutil,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf(),'Accept':'application/json'},body:JSON.stringify(payload)})
    const d=await res.json()
    if(d.success) {
      showToast('success',"Fiche d'observation sauvegardée.")
      if(ctx) {
        const k=pk(ctx.objNum,ctx.testRef,0)
        if(!outilsProcsMap[k]) outilsProcsMap[k]=[]
        if(!outilsProcsMap[k].includes('XIV')) outilsProcsMap[k].push('XIV')
        obsXIVCache[rk(ctx.objNum,ctx.testRef)]={...obsData,...obsFormXIV,testRef:ctx.testRef,objNum:ctx.objNum,constats:[...obsData.constats],recommandations:[...obsData.recommandations]}
      }
    } else showToast('error',d.error||'Erreur serveur')
  } catch(e:any){showToast('error',e.message)}
  finally{obsSaving.value=false}
}

// ── OUTILS ──────────────────────────────────────────────────────
function ouvrirOutil(obj:any,test:any,code:string,procIdx:number,oi:number,ti:number) {
  const tr=tRef(test,oi,ti)
  if(code==='XIV') { ouvrirObsDirecte(obj,test,oi,ti); return }
  const k=pk(obj.num,tr,procIdx)
  if(!outilsProcsMap[k]) outilsProcsMap[k]=[]
  if(!outilsProcsMap[k].includes(code)) outilsProcsMap[k].push(code)
  const segments:Record<string,string>={
    I:'entretien',II:'analyse-taches',III:'diagramme-flux',IV:'approche-processus',
    V:'test-cheminement',VI:'hierarchisation-risques',VII:'referentiel-audit',
    VIII:'cause-effet',IX:'qci',X:'brainstorming',XI:'piste-audit',
    XII:'circularisation',XIII:'audit-analytique',XV:'echantillonnage',
  }
  const seg=segments[code]; if(!seg) return
  const libProc=(procIdx>=0&&(test.procedures?.length??0)>procIdx)?test.procedures[procIdx]:(test.libelle??'')
  const params=new URLSearchParams({
    fiche_test_id:String(form.id??''),mission_id:String(props.missionId??''),assignment_id:String(props.assignmentId??''),
    test_ref:tr,obj_num:obj.num,proc_idx:String(procIdx),procedure_code:props.missionContext?.code_mission??'',
    libelle_test:test.libelle??'',libelle_proc:libProc,objectif_audit:obj.objectif??obj.libelle??'',back:window.location.href,
  })
  router.visit(`/auditor/outils/${seg}?${params}`)
}

function ouvrirChoixOutil(obj:any,test:any,pi:number,oi:number,ti:number) {
  const tr=tRef(test,oi,ti)
  Object.assign(om,{visible:true,testRef:tr,procIdx:pi,objNum:obj.num,obj,test,oi,ti,selected:[...(getOutilsForProc(obj.num,tr,pi))]})
}
function omToggle(code:string) { const i=om.selected.indexOf(code); if(i===-1) om.selected.push(code); else om.selected.splice(i,1) }
function omConfirmer() {
  if(!om.selected.length) return
  const k=pk(om.objNum,om.testRef,om.procIdx??0)
  outilsProcsMap[k]=[...om.selected]
  om.visible=false
  ouvrirOutil(om.obj,om.test,om.selected[0],om.procIdx??0,om.oi,om.ti)
}

// ── CRUD ────────────────────────────────────────────────────────
const serializeResultats = ()=>Object.entries(resultatsMap).map(([k,v])=>{const p=k.split('::');return{obj_num:p[0],test_ref:p.slice(1).join('::'),resultat:v.resultat}})

async function submit(silent=false) {
  processing.value=!silent
  try {
    const url=form.id?(dynUrls.update??props.urlUpdate):props.urlStore
    if(!url) { if(!silent) showToast('error','URL indisponible.'); return }
    const payload:any={
      mission_id:props.missionId??props.missionContext?.mission_id,
      assignment_id:props.assignmentId??props.missionContext?.assignment_id,
      resultats:JSON.stringify(serializeResultats()),outils_data:JSON.stringify([]),
    }
    if(form.id) payload._method='PUT'
    const res=await fetch(url,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf(),'Accept':'application/json'},body:JSON.stringify(payload)})
    const d=await res.json()
    if(d.success||res.ok) {
      if(!silent) showToast('success',form.id?'Fiche mise à jour.':'Fiche créée.')
      if(d.form) Object.assign(form,{id:d.form.id,code:d.form.code,validation_status:d.form.validation_status})
      if(d.urlUpdate)    dynUrls.update=d.urlUpdate
      if(d.urlSoumettre) dynUrls.soumettre=d.urlSoumettre
      if(d.urlValider)   dynUrls.valider=d.urlValider
    } else if(!silent) showToast('error',d.message||'Erreur.')
  } catch(e:any){ if(!silent) showToast('error',e.message) }
  finally{processing.value=false}
}

async function saveSynthese() {
  savingSynthese.value=true
  try {
    const url=dynUrls.update??props.urlUpdate
    if(!url) { showToast('error','URL indisponible'); return }
    const payload:any={
      mission_id:props.missionId??props.missionContext?.mission_id,
      assignment_id:props.assignmentId??props.missionContext?.assignment_id,
      synthese_data:JSON.stringify({...syntheseData}),
      resultats:JSON.stringify(serializeResultats()),outils_data:JSON.stringify([]),
    }
    if(form.id) payload._method='PUT'
    const res=await fetch(url,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf(),'Accept':'application/json'},body:JSON.stringify(payload)})
    if(res.ok) showToast('success','Synthèse enregistrée.')
    else showToast('error','Erreur lors de la sauvegarde')
  } catch(e:any){showToast('error',e.message)}
  finally{savingSynthese.value=false}
}

async function soumettre() {
  processing.value=true
  try {
    const d=await(await fetch(dynUrls.soumettre??props.urlSoumettre??'',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf(),'Accept':'application/json'},body:JSON.stringify({mission_id:props.missionId,assignment_id:props.assignmentId})})).json()
    if(d.success){form.validation_status='in_review';showToast('success','Soumise pour validation.')}
    else showToast('error',d.error||'Erreur')
  } catch{showToast('error','Erreur réseau')}
  finally{processing.value=false}
}

async function valider(action:string,note?:string) {
  processing.value=true
  try {
    const d=await(await fetch(dynUrls.valider??props.urlValider??'',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf(),'Accept':'application/json'},body:JSON.stringify({mission_id:props.missionId,assignment_id:props.assignmentId,action,note})})).json()
    if(d.success){form.validation_status=d.status;showToast('success',action==='validate'?'Fiche validée ✓':'Rejetée.')}
    else showToast('error',d.error||'Erreur')
  } catch{showToast('error','Erreur réseau')}
  finally{processing.value=false}
}

const promptReject=()=>{const n=prompt('Motif du rejet :','');if(n?.trim()) valider('reject',n.trim())}
const annuler=()=>{if(props.backUrl) router.visit(props.backUrl)}

function showToast(type:string,msg:string,dur=4000) {
  if(_tt) clearTimeout(_tt)
  toast.value={show:true,type,msg}
  _tt=setTimeout(()=>{toast.value.show=false},dur)
}

// ── INIT ────────────────────────────────────────────────────────
onMounted(()=>{
  if(props.form?.resultats) {
    try {
      const arr=Array.isArray(props.form.resultats)?props.form.resultats:JSON.parse(props.form.resultats)
      arr.forEach((r:any)=>{resultatsMap[rk(r.obj_num,r.test_ref)]={resultat:r.resultat??''}})
    } catch{}
  }
  if(props.outilsParTest) {
    Object.entries(props.outilsParTest).forEach(([testKey,outils])=>{
      ;(outils as any[]).forEach((ot:any)=>{
        const parts=testKey.split('::')
        const k=pk(parts[0],parts.slice(1).join('::'),ot.proc_idx??0)
        if(!outilsProcsMap[k]) outilsProcsMap[k]=[]
        if(!outilsProcsMap[k].includes(ot.outil_code)) outilsProcsMap[k].push(ot.outil_code)
      })
    })
  }
  if(props.form?.synthese_data) {
    try {
      const s=typeof props.form.synthese_data==='string'?JSON.parse(props.form.synthese_data):props.form.synthese_data
      Object.assign(syntheseData,s)
      if(Array.isArray(s.frap_lignes)){syntheseData.frap_lignes=s.frap_lignes;frapCounter=s.frap_lignes.length+1}
    } catch{}
  }
  document.addEventListener('click',()=>{showDD.value=''})
})
onBeforeUnmount(()=>{
  if(_tt) clearTimeout(_tt)
  document.removeEventListener('click',()=>{showDD.value=''})
})
</script>

<style scoped>
/* ── VARIABLES ── */
:root {
  --p:#6d28d9; --pl:#ede9fe; --navy:#0f172a;
  --bd:#e2e8f0; --bg:#f8fafc; --tx:#1e293b; --muted:#64748b; --wh:#fff;
  --rad:7px; --rad-sm:5px;
}

/* ── SHELL ── */
.shell { display:flex; flex-direction:column; min-height:100vh; background:var(--bg); }
.row-g4  { display:flex; align-items:center; gap:.35rem; flex-wrap:wrap; }
.row-g8  { display:flex; align-items:center; gap:.6rem; }
.ml-auto { margin-left:auto; }
.ml4     { margin-left:.25rem; }
.mt4     { margin-top:.2rem; }
.mt8     { margin-top:.5rem; }
.mb4     { margin-bottom:.2rem; }
.mb6     { margin-bottom:.35rem; }
.mb8     { margin-bottom:.5rem; }
.mt2     { margin-top:.1rem; }
.px      { padding-left:.85rem; padding-right:.85rem; }
.tc      { text-align:center; }
.fw-600  { font-weight:600; }
.muted-sm       { font-size:.68rem; color:var(--muted); }
.muted-sm-light { font-size:.68rem; color:rgba(255,255,255,.65); }
.muted-icon     { font-size:.75rem; color:#94a3b8; }

/* ── TOPBAR ── */
.topbar {
  display:flex; justify-content:space-between; align-items:center;
  padding:.42rem 1rem; background:var(--wh); border-bottom:1px solid var(--bd);
  position:sticky; top:0; z-index:100; flex-wrap:wrap; gap:.3rem;
}
.icon-btn {
  display:inline-flex; align-items:center; justify-content:center;
  width:26px; height:26px; border-radius:var(--rad-sm);
  background:var(--bg); border:1px solid var(--bd);
  color:var(--muted); text-decoration:none; cursor:pointer;
}
.icon-btn:hover { background:var(--pl); color:var(--p); }
.code-badge  { background:var(--navy); color:#fff; padding:.12rem .5rem; border-radius:5px; font-size:.65rem; font-weight:600; }
.status-dot  { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
.sd--draft{background:#94a3b8} .sd--in_review{background:#2563eb} .sd--validated{background:#16a34a}
.divider     { width:1px; height:16px; background:var(--bd); }
.prog-badge  { background:var(--pl); color:var(--p); padding:.08rem .42rem; border-radius:4px; font-size:.6rem; font-weight:700; }
.chip        { display:inline-flex; align-items:center; gap:.18rem; background:var(--bg); border:1px solid var(--bd); padding:.1rem .42rem; border-radius:20px; font-size:.65rem; }
.chip--role  { background:#f5f3ff; border-color:#ddd6fe; color:#5b21b6; }
.chip--user  { background:#f0fdf4; border-color:#bbf7d0; color:#065f46; }

/* ── BANNERS ── */
.status-banner { display:flex; align-items:center; gap:.35rem; padding:.28rem 1rem; font-size:.72rem; border-bottom:1px solid transparent; }
.banner--ok     { background:#d1fae5; color:#065f46; border-color:#a7f3d0; }
.banner--review { background:#dbeafe; color:#1d4ed8; border-color:#bfdbfe; }
.banner--reject { background:#fee2e2; color:#dc2626; border-color:#fecaca; }

/* ── KPI BAR ── */
.kpi-bar { display:flex; align-items:center; gap:.85rem; padding:.48rem 1rem; background:var(--wh); border-bottom:1px solid var(--bd); flex-wrap:wrap; }
.kpi-strip { display:flex; border:1px solid var(--bd); border-radius:var(--rad); overflow:hidden; }
.kpi-cell  { display:flex; flex-direction:column; align-items:center; padding:.28rem .65rem; border-right:1px solid var(--bd); }
.kpi-cell:last-child { border-right:none; }
.kv { font-size:1.15rem; font-weight:700; line-height:1; }
.kl { font-size:.52rem; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); margin-top:.08rem; }
.kv--blue{color:#2563eb} .kv--slate{color:#475569} .kv--green{color:#16a34a} .kv--orange{color:#d97706} .kv--red{color:#dc2626} .kv--purple{color:#7c3aed}
.progress-wrap { display:flex; align-items:center; gap:.5rem; flex:1; min-width:160px; }
.progress-track { flex:1; height:7px; background:#f1f5f9; border-radius:4px; overflow:hidden; position:relative; }
.ps { position:absolute; top:0; height:100%; }
.ps--green{background:#16a34a;left:0} .ps--orange{background:#d97706} .ps--red{background:#dc2626}

/* ── MAIN CONTENT ── */
.main-content { flex:1; overflow:auto; }

/* ── TESTS SECTION ── */
.tests-section { padding:.85rem; }
.empty-state { text-align:center; padding:3rem; background:var(--wh); border-radius:12px; border:1px solid var(--bd); }
.empty-state__ico { font-size:2rem; color:var(--muted); display:block; margin-bottom:.6rem; }
.obj-list { display:flex; flex-direction:column; gap:.7rem; max-width:1180px; margin:0 auto; }
.obj-block { background:var(--wh); border-radius:10px; border:1px solid var(--bd); overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.05); }
.obj-hd { display:flex; align-items:center; gap:.4rem; padding:.55rem .9rem; background:#f8fafc; border-bottom:1px solid var(--bd); flex-wrap:wrap; }
.obj-num   { background:var(--p); color:#fff; padding:.1rem .42rem; border-radius:4px; font-size:.65rem; font-weight:700; }
.obj-lbl   { font-weight:600; color:var(--tx); flex:1; font-size:.8rem; }
.obj-mini-kpi { font-size:.65rem; font-weight:600; }

/* ── TEST ROW ── */
.tests-list .test-row {
  display:grid; grid-template-columns:58px 1fr 108px auto;
  grid-template-rows:auto auto; gap:.4rem .65rem;
  padding:.65rem .9rem; border-bottom:1px solid #f1f5f9;
  border-left:3px solid transparent; align-items:start; transition:border-color .15s;
}
.test-row:last-child { border-bottom:none; }
.tr--ok{border-left-color:#16a34a} .tr--wn{border-left-color:#d97706} .tr--nc{border-left-color:#dc2626} .tr--na{border-left-color:#94a3b8}
.test-ref  { background:var(--pl); color:var(--p); padding:.09rem .38rem; border-radius:4px; font-size:.6rem; font-weight:700; font-family:monospace; white-space:nowrap; display:inline-block; align-self:flex-start; margin-top:2px; }
.test-info { min-width:0; }
.test-lbl  { font-size:.77rem; font-weight:500; color:var(--tx); }
.test-acts { display:flex; align-items:center; gap:.28rem; }

/* ── TAGS ── */
.tag { display:inline-flex; align-items:center; gap:.12rem; padding:.05rem .3rem; border-radius:20px; font-size:.6rem; border:1px solid transparent; }
.tag--blue   { background:#dbeafe; border-color:#bfdbfe; color:#1d4ed8; }
.tag--green  { background:#d1fae5; border-color:#a7f3d0; color:#065f46; }
.tag--purple { background:var(--pl); border-color:#c4b5fd; color:var(--p); }
.tag--red    { background:#fee2e2; border-color:#fecaca; color:#dc2626; }
.tag--slate  { background:#f1f5f9; border-color:var(--bd); color:var(--muted); }

/* ── OUTIL CHIPS ── */
.outil-chip {
  display:inline-flex; align-items:center; gap:.12rem; padding:.08rem .35rem;
  background:color-mix(in srgb,var(--oc,#374151) 10%,#fff);
  border:1px solid color-mix(in srgb,var(--oc,#374151) 25%,#fff);
  color:var(--oc,#374151); border-radius:20px; font-size:.58rem; font-weight:700; cursor:pointer;
}
.outil-chip:hover { background:var(--oc,#374151); color:#fff; }
.outil-chip__score { background:rgba(0,0,0,.12); border-radius:10px; padding:0 3px; font-size:.52rem; }

/* ── RESULT ── */
.res-sel  { border:1px solid var(--bd); border-radius:var(--rad-sm); padding:.18rem .35rem; font-size:.68rem; background:var(--wh); cursor:pointer; width:100%; }
.res-pill { display:inline-block; padding:.09rem .38rem; border-radius:20px; font-size:.65rem; font-weight:600; }
.rp--conforme{background:#d1fae5;color:#065f46} .rp--ecart{background:#fef3c7;color:#92400e}
.rp--nc{background:#fee2e2;color:#dc2626} .rp--na{background:#f1f5f9;color:var(--muted)}
.ssp--draft{background:#f1f5f9;color:var(--muted)} .ssp--in_review{background:#dbeafe;color:#1d4ed8} .ssp--validated{background:#d1fae5;color:#065f46}

/* ── ACTION BTNS ── */
.act-btn { display:inline-flex; align-items:center; gap:.2rem; padding:.24rem .5rem; border-radius:var(--rad-sm); font-size:.65rem; font-weight:500; border:1px solid var(--bd); background:var(--wh); color:var(--muted); cursor:pointer; position:relative; }
.act--fiche   { background:#eff6ff; border-color:#bfdbfe; color:#1d4ed8; }
.act--obs     { background:#faf5ff; border-color:#ddd6fe; color:#7c3aed; }
.act--tools   { background:var(--bg); }
.act--tools.active { background:var(--pl); border-color:#a78bfa; color:var(--p); }
.act--has-data { background:#f0fdf4; border-color:#bbf7d0; color:#065f46; }
.saved-dot { position:absolute; top:3px; right:3px; width:5px; height:5px; background:#10b981; border-radius:50%; }

/* ── DROPDOWN ── */
.dd-wrap { position:relative; }
.dd-menu { position:absolute; top:calc(100% + 4px); right:0; z-index:200; min-width:210px; background:var(--wh); border:1px solid var(--bd); border-radius:9px; box-shadow:0 6px 20px rgba(0,0,0,.12); overflow:hidden; }
.dd-head { padding:.26rem .7rem; font-size:.58rem; font-weight:700; color:var(--muted); text-transform:uppercase; border-bottom:1px solid var(--bd); background:var(--bg); }
.dd-item { display:flex; align-items:center; gap:.28rem; width:100%; padding:.28rem .7rem; background:none; border:none; font-size:.68rem; cursor:pointer; color:var(--tx); }
.dd-item:hover { background:var(--bg); }
.dd-dot  { width:6px; height:6px; border-radius:50%; flex-shrink:0; }
.dd-code { font-weight:700; font-size:.65rem; min-width:26px; }
.dd-lbl  { flex:1; }
.dd-chk  { color:#10b981; font-size:.65rem; }

/* ── PROCÉDURES ── */
.proc-list { grid-column:1/-1; background:#f8fafc; border-top:1px solid #f1f5f9; padding:.35rem .9rem; display:flex; flex-direction:column; gap:.2rem; }
.proc-item { display:flex; align-items:center; gap:.4rem; padding:.2rem .4rem; border-radius:4px; border:1px solid transparent; }
.proc-item--linked { background:#f0fdf4; border-color:#bbf7d0; }
.proc-n   { font-size:.58rem; font-weight:700; color:#94a3b8; min-width:14px; }
.proc-txt { font-size:.68rem; color:var(--tx); flex:1; }
.proc-add-btn { display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; background:#eff6ff; border:1px solid #bfdbfe; color:#1d4ed8; border-radius:4px; cursor:pointer; font-size:.65rem; }

/* ── BOUTONS GLOBAUX ── */
.btn { display:inline-flex; align-items:center; gap:.25rem; padding:.28rem .65rem; border-radius:var(--rad); font-size:.7rem; font-weight:500; border:1px solid var(--bd); background:var(--wh); color:var(--tx); cursor:pointer; transition:all .15s; }
.btn:disabled { opacity:.5; cursor:not-allowed; }
.btn--sm      { padding:.2rem .45rem; font-size:.65rem; }
.btn--ghost   { color:var(--p); border-color:var(--p); } .btn--ghost:hover { background:var(--pl); }
.btn--save    { background:var(--navy); color:#fff; border-color:var(--navy); } .btn--save:hover:not(:disabled) { background:#1e293b; }
.btn--submit  { background:#2563eb; color:#fff; border-color:#2563eb; }
.btn--ok      { background:#16a34a; color:#fff; border-color:#16a34a; }
.btn--ko      { background:#dc2626; color:#fff; border-color:#dc2626; }
.btn--obs     { background:#7c3aed; color:#fff; border-color:#7c3aed; }
.btn--synth   { background:#4c1d95; color:#fff; border-color:#4c1d95; } .btn--synth:hover { background:#3b0764; }

/* ═══════════════ PAGE VIEW ═══════════════ */
.page-view {
  display:flex; flex-direction:column;
  max-width:900px; margin:.85rem auto;
  background:var(--wh); border-radius:12px;
  box-shadow:0 4px 20px rgba(0,0,0,.1); overflow:hidden;
}
.page-header {
  display:flex; justify-content:space-between; align-items:center;
  padding:.8rem 1.2rem; flex-shrink:0; flex-wrap:wrap; gap:.4rem;
}
.page-header--fiche { background:#1e3a5f; }
.page-header--obs   { background:linear-gradient(135deg,#6d28d9,#9333ea); }
.page-header__center { display:flex; align-items:center; gap:.8rem; flex:1; justify-content:center; }
.page-ico { width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.page-ico--fiche { background:rgba(255,255,255,.15); color:#fff; font-size:1.1rem; }
.page-ico--obs   { background:rgba(255,255,255,.2); color:#fff; font-size:.85rem; font-weight:700; }
.page-title { font-size:.9rem; font-weight:700; color:#fff; }
.page-sub   { font-size:.65rem; color:rgba(255,255,255,.7); margin-top:2px; display:flex; align-items:center; gap:.3rem; flex-wrap:wrap; }
.obs-sub-light { color:rgba(255,255,255,.7); }
.obs-ref-badge { background:rgba(255,255,255,.18); padding:.02rem .35rem; border-radius:3px; font-size:.65rem; color:#fff; font-weight:600; }
.btn-back {
  display:inline-flex; align-items:center; gap:.3rem;
  padding:.3rem .7rem; border-radius:6px; font-size:.75rem; cursor:pointer;
  background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.25); color:#fff;
}
.btn-back:hover { background:rgba(255,255,255,.25); }
.page-body { flex:1; overflow-y:auto; padding:.85rem; display:flex; flex-direction:column; gap:.65rem; background:var(--bg); }
.page-footer { display:flex; justify-content:space-between; gap:.45rem; padding:.65rem 1rem; background:var(--wh); border-top:1px solid var(--bd); flex-shrink:0; }
.loading-center { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:3rem; gap:.75rem; }
.result-banner { display:flex; align-items:center; gap:.35rem; padding:.4rem .65rem; border-radius:7px; font-size:.7rem; border:1px solid; }
.rb--conforme{background:#d1fae5;color:#065f46;border-color:#a7f3d0}
.rb--ecart{background:#fef3c7;color:#92400e;border-color:#fcd34d}
.rb--nc{background:#fee2e2;color:#dc2626;border-color:#fecaca}
.rb--na{background:#f1f5f9;color:var(--muted);border-color:var(--bd)}

/* ── SECTIONS ── */
.sc { background:var(--wh); border-radius:8px; border:1px solid var(--bd); padding:.6rem .8rem; }
.sc-title { font-size:.65rem; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:.45rem; display:flex; align-items:center; gap:.28rem; flex-wrap:wrap; }
.sec-num  { display:inline-flex; align-items:center; justify-content:center; width:16px; height:16px; background:var(--p); color:#fff; border-radius:50%; font-size:.55rem; font-weight:700; flex-shrink:0; }
.subsec   { margin-top:.5rem; }
.subsec__hd { display:flex; align-items:center; gap:.28rem; padding:.3rem 0; font-size:.68rem; font-weight:600; color:var(--p); border-top:1px solid var(--bd); }

/* ── FORMULAIRE ── */
.form-grid    { display:grid; gap:.48rem; }
.form-grid--2 { grid-template-columns:1fr 1fr; padding:.6rem .85rem; }
.form-grid--3 { grid-template-columns:1fr 1fr 1fr; }
.fg       { display:flex; flex-direction:column; gap:.14rem; }
.fg--full { grid-column:1/-1; }
.fi-meta  { display:grid; grid-template-columns:1fr 1fr; gap:.4rem; }
.lbl  { font-size:.6rem; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; display:block; }
.val  { font-size:.72rem; color:var(--tx); }
.hint { font-size:.58rem; color:var(--muted); margin-top:.12rem; display:block; }

/* ── INPUTS ── */
.inp-f,.ta-f { border:1px solid var(--bd); border-radius:var(--rad-sm); padding:.25rem .42rem; font-size:.73rem; width:100%; background:var(--wh); color:var(--tx); font-family:inherit; }
.inp-f:focus,.ta-f:focus { outline:none; border-color:var(--p); box-shadow:0 0 0 2px rgba(109,40,217,.1); }
.ta-f { resize:vertical; }
.sel-f { border:1px solid var(--bd); border-radius:var(--rad-sm); padding:.3rem .42rem; font-size:.73rem; width:100%; background:var(--wh); color:var(--tx); font-family:inherit; cursor:pointer; outline:none; }
.inp-sm,.sel-sm { border:1px solid var(--bd); border-radius:4px; padding:.18rem .3rem; font-size:.65rem; width:100%; background:var(--wh); font-family:inherit; }
.ta-sm { width:100%; border:1px solid var(--bd); border-radius:4px; padding:.16rem .3rem; font-size:.65rem; font-family:inherit; resize:vertical; }
.inp-sy { border:1px solid var(--bd); border-radius:5px; padding:.2rem .38rem; font-size:.7rem; width:100%; }
.ro-field { font-size:.7rem; color:var(--tx); background:#f8fafc; border:1px solid var(--bd); border-radius:5px; padding:.35rem .45rem; min-height:40px; white-space:pre-wrap; }

/* ── CHECKBOXES ── */
.chk-grid { display:grid; grid-template-columns:1fr 1fr; gap:.25rem; padding:.4rem .85rem; }
.chk-item { display:flex; align-items:center; gap:.3rem; font-size:.7rem; cursor:pointer; }
.chk-item input { accent-color:var(--p); width:13px; height:13px; }

/* ── NIVEAUX ── */
.niveaux-grid { display:grid; grid-template-columns:1fr 1fr; gap:.55rem; padding:.6rem .85rem; }
.lvl-picker,.synth-picker { display:flex; gap:.22rem; flex-wrap:wrap; }
.lvl-btn { display:flex; align-items:center; gap:.18rem; padding:.22rem .55rem; border:2px solid var(--bd); border-radius:7px; background:var(--wh); cursor:pointer; font-size:.65rem; transition:all .15s; flex:1; justify-content:center; }
.lvl-btn.selected { font-weight:700; box-shadow:0 1px 6px rgba(0,0,0,.1); }
.lvl-n { display:inline-flex; align-items:center; justify-content:center; width:15px; height:15px; background:rgba(0,0,0,.1); border-radius:50%; font-size:.55rem; font-weight:800; }
.synth-btn { padding:.22rem .55rem; border:2px solid var(--bd); border-radius:20px; background:var(--wh); cursor:pointer; font-size:.66rem; transition:all .15s; }
.synth-btn.selected { font-weight:700; }

/* ── TABLES ── */
.tbl-scroll { overflow-x:auto; border-radius:6px; border:1px solid var(--bd); margin-top:.35rem; }
.data-tbl { width:100%; border-collapse:collapse; font-size:.66rem; }
.data-tbl th { background:#f8fafc; padding:.26rem .38rem; text-align:left; font-size:.57rem; font-weight:700; color:var(--muted); text-transform:uppercase; border-bottom:1px solid var(--bd); border-right:1px solid var(--bd); }
.data-tbl td { padding:.22rem .38rem; border-bottom:1px solid #f1f5f9; border-right:1px solid #f1f5f9; vertical-align:middle; }
.data-tbl tr:last-child td { border-bottom:none; }
.tbl-empty { text-align:center; color:var(--muted); padding:1rem; font-style:italic; font-size:.68rem; }
.tr-row--nc{background:#fef2f2} .tr-row--ok{background:#f0fdf4} .tr-row--wn{background:#fffbeb}
.count-badge { background:var(--p); color:#fff; padding:.05rem .35rem; border-radius:20px; font-size:.58rem; font-weight:700; }
.add-btn { display:inline-flex; align-items:center; gap:.18rem; padding:.15rem .45rem; background:var(--pl); border:1px solid #c4b5fd; color:var(--p); border-radius:5px; font-size:.63rem; cursor:pointer; }
.del-btn { display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; background:#fee2e2; border:1px solid #fecaca; color:#dc2626; border-radius:4px; cursor:pointer; font-size:.65rem; }

/* ── FICHE PROCÉDURES ── */
.procs-list { display:flex; flex-direction:column; gap:.4rem; }
.fi-proc { border:1px solid var(--bd); border-radius:7px; overflow:hidden; }
.fi-proc--linked { border-color:#bbf7d0; }
.fi-proc__hd { display:flex; align-items:flex-start; gap:.45rem; padding:.4rem .6rem; background:#f8fafc; }
.fi-proc-n  { display:flex; align-items:center; justify-content:center; width:20px; height:20px; background:#1e3a5f; color:#fff; border-radius:3px; font-size:.58rem; font-weight:700; flex-shrink:0; margin-top:1px; }
.fi-proc-txt { font-size:.72rem; color:var(--tx); flex:1; line-height:1.5; }
.fi-proc__tools { display:flex; align-items:center; gap:.3rem; padding:.3rem .6rem; border-top:1px dashed var(--bd); flex-wrap:wrap; }
.outils-grid { display:grid; grid-template-columns:1fr 1fr; gap:.4rem; }
.outil-card { border:1px solid color-mix(in srgb,var(--oc,#374151) 20%,var(--bd)); border-radius:7px; border-left:3px solid var(--oc,#374151); cursor:pointer; background:var(--wh); transition:transform .15s; }
.outil-card:hover { transform:translateY(-1px); box-shadow:0 3px 10px rgba(0,0,0,.08); }
.outil-card__hd   { display:flex; align-items:center; gap:.35rem; padding:.32rem .48rem; }
.outil-card__code { min-width:22px; height:16px; display:inline-flex; align-items:center; justify-content:center; color:#fff; border-radius:3px; font-size:.55rem; font-weight:800; padding:0 3px; }
.outil-card__lbl  { font-size:.65rem; font-weight:600; color:var(--tx); flex:1; }
.outil-card__score { font-size:.58rem; background:#f1f5f9; padding:1px 4px; border-radius:8px; }
.outil-card__concl { padding:.28rem .48rem; font-size:.62rem; color:var(--muted); font-style:italic; border-top:1px solid #f1f5f9; }

/* CTA Obs XIV */
.obs-cta { display:flex; align-items:center; gap:.85rem; padding:.65rem .8rem; background:#faf5ff; border:1px solid #ddd6fe; border-radius:8px; flex-wrap:wrap; cursor:pointer; transition:background .15s; }
.obs-cta:hover { background:#f5f3ff; }
.obs-cta__ico   { width:34px; height:34px; background:#7c3aed; color:#fff; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.9rem; flex-shrink:0; }
.obs-cta__body  { flex:1; }
.obs-cta__title { font-size:.75rem; font-weight:600; color:#4c1d95; }
.obs-cta__sub   { font-size:.63rem; color:#6d28d9; margin-top:1px; }

/* ═══════════════ MODAL SYNTHÈSE ═══════════════ */
.modal-ov { position:fixed; inset:0; background:rgba(0,0,0,.6); display:flex; align-items:center; justify-content:center; z-index:600; padding:.75rem; }
.modal-synthese { background:var(--wh); border-radius:14px; width:98%; max-width:1260px; max-height:96vh; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 24px 72px rgba(0,0,0,.3); }
.syn-hd  { display:flex; justify-content:space-between; align-items:center; padding:.75rem 1.1rem; background:linear-gradient(135deg,#0f172a,#1e1b4b,#4c1d95); flex-shrink:0; flex-wrap:wrap; gap:.4rem; }
.syn-ico   { width:36px; height:36px; border-radius:8px; background:rgba(255,255,255,.15); color:#fff; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
.syn-title { font-size:.95rem; font-weight:700; color:#fff; }
.syn-code  { background:rgba(255,255,255,.15); padding:.04rem .38rem; border-radius:4px; font-size:.6rem; font-weight:700; color:#fff; }
.syn-btn   { display:inline-flex; align-items:center; gap:.25rem; padding:.25rem .6rem; background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.25); color:#fff; border-radius:5px; font-size:.65rem; cursor:pointer; }
.syn-btn--pdf { background:rgba(220,38,38,.4); border-color:rgba(220,38,38,.6); }
.syn-body  { flex:1; overflow-y:auto; background:#f5f3ff; padding:.65rem; display:flex; flex-direction:column; gap:.55rem; }
.syn-ft    { display:flex; justify-content:space-between; align-items:center; padding:.65rem 1.1rem; background:var(--bg); border-top:1px solid var(--bd); flex-shrink:0; flex-wrap:wrap; gap:.4rem; }

/* Cartes synthèse */
.sy-card    { background:var(--wh); border-radius:9px; border:1px solid #e9d5ff; overflow:hidden; }
.sy-card__hd { display:flex; align-items:center; gap:.35rem; padding:.48rem .9rem; background:linear-gradient(135deg,#ede9fe,#f5f3ff); border-bottom:1px solid #ddd6fe; color:#5b21b6; font-size:.72rem; font-weight:700; flex-wrap:wrap; }
.sy-badge   { background:#1e1b4b; color:#fff; padding:.08rem .38rem; border-radius:20px; font-size:.6rem; font-weight:700; margin-left:.25rem; }
.sy-meta    { display:grid; grid-template-columns:repeat(4,1fr); gap:.45rem; padding:.65rem .9rem; }
.sy-meta-item label { display:block; font-size:.57rem; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em; margin-bottom:.14rem; }
.sy-val     { font-size:.7rem; font-weight:500; }
.sy-kpi-row { display:flex; border-bottom:1px solid #e9d5ff; flex-wrap:wrap; }
.sy-kpi     { flex:1; min-width:80px; text-align:center; padding:.6rem .4rem; border-right:1px solid #e9d5ff; }
.sy-kpi:last-child { border-right:none; }
.sy-kv      { font-size:1.5rem; font-weight:800; line-height:1; }
.sy-kl      { font-size:.55rem; text-transform:uppercase; color:var(--muted); margin-top:.15rem; }
.sy-kpi--blue .sy-kv{color:#2563eb} .sy-kpi--slate .sy-kv{color:#475569} .sy-kpi--green .sy-kv{color:#16a34a}
.sy-kpi--orange .sy-kv{color:#d97706} .sy-kpi--red .sy-kv{color:#dc2626} .sy-kpi--purple .sy-kv{color:#7c3aed}
.sy-progress { padding:.5rem .9rem .7rem; border-top:1px solid #e9d5ff; }
.sy-prog-labels { display:flex; justify-content:space-between; font-size:.65rem; color:var(--muted); margin-bottom:.25rem; }
.sy-prog-bar { position:relative; height:10px; background:#f1f5f9; border-radius:5px; overflow:hidden; }
.sy-ps { position:absolute; top:0; height:100%; }
.sy-ps--green{background:#16a34a;left:0} .sy-ps--orange{background:#d97706} .sy-ps--red{background:#dc2626}
.legend-dot { font-size:.6rem; display:flex; align-items:center; gap:.18rem; }
.legend-dot::before { content:'●'; font-size:.65rem; }
.legend--green::before{color:#16a34a} .legend--orange::before{color:#d97706} .legend--red::before{color:#dc2626} .legend--slate::before{color:#94a3b8}
.sy-niveaux { display:grid; grid-template-columns:1fr 1fr; gap:.85rem; padding:.65rem .9rem; }
.sy-empty   { display:flex; align-items:center; gap:.45rem; padding:1.2rem .9rem; color:var(--muted); font-size:.72rem; }

/* Tables synthèse résultats tests */
.sy-tbl { width:100%; border-collapse:collapse; font-size:.65rem; }
.sy-tbl th { background:#1e1b4b; color:#fff; padding:.26rem .38rem; text-align:left; font-size:.55rem; text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
.sy-tbl td { padding:.24rem .38rem; border-bottom:1px solid #f1f5f9; border-right:1px solid #f1f5f9; vertical-align:top; }
.sy-obj-row td { background:linear-gradient(135deg,#1e1b4b,#312e81); color:#fff; padding:.28rem .6rem; font-size:.65rem; font-weight:700; }
.sy-obj-num  { background:rgba(255,255,255,.18); padding:.04rem .35rem; border-radius:3px; font-size:.58rem; font-weight:800; margin-right:.35rem; }
.sy-test-lbl { font-size:.65rem; color:var(--tx); }
.sy-constat  { font-size:.63rem; color:var(--tx); line-height:1.4; }
.sy-ref  { background:var(--pl); color:var(--p); padding:.06rem .32rem; border-radius:3px; font-size:.58rem; font-weight:800; font-family:monospace; }
.sy-obj-tag  { background:var(--pl); color:var(--p); padding:.04rem .32rem; border-radius:3px; font-size:.58rem; font-weight:700; }
.sy-tr--conforme td{border-left:2px solid #16a34a} .sy-tr--ecart td{border-left:2px solid #d97706} .sy-tr--nc td{border-left:2px solid #dc2626}
.sy-outil-dots { display:flex; flex-wrap:wrap; gap:.1rem; justify-content:center; }
.sy-outil-dot  { display:inline-flex; align-items:center; justify-content:center; padding:.03rem .25rem; color:#fff; border-radius:3px; font-size:.53rem; font-weight:800; }
.niveau-badge  { display:inline-block; }

/* Observations synthèse */
.sy-obs-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:.55rem; padding:.65rem .9rem; }
.sy-obs-card { border:1px solid var(--bd); border-radius:7px; padding:.55rem .7rem; border-left:3px solid #7c3aed; }
.sy-obs-hd   { display:flex; align-items:center; gap:.3rem; margin-bottom:.3rem; flex-wrap:wrap; }
.sy-obs-titre { font-size:.7rem; font-weight:600; color:var(--tx); margin-bottom:.2rem; }

/* Outils synthèse */
.sy-outils-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(195px,1fr)); gap:.48rem; padding:.65rem .9rem; }
.sy-outil-card  { border:1px solid var(--bd); border-left:4px solid; border-radius:7px; padding:.42rem .58rem; background:var(--wh); }
.sy-outil-code  { min-width:22px; height:16px; display:inline-flex; align-items:center; justify-content:center; color:#fff; border-radius:3px; font-size:.55rem; font-weight:800; padding:0 3px; }

/* ═══════════════ FOCI — TABLEAU STRUCTURÉ ═══════════════ */

/* En-tête identification */
.foci-header {
  padding:.6rem .9rem;
  background:#faf5ff;
  border-bottom:1px solid #e9d5ff;
}
.foci-header__grid {
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(155px,1fr));
  gap:.42rem;
}
.foci-hf   { display:flex; flex-direction:column; gap:.1rem; }
.foci-hl   { font-size:.54rem; font-weight:700; color:#7c3aed; text-transform:uppercase; letter-spacing:.05em; }
.foci-hv   { font-size:.7rem; color:var(--tx); }

/* Groupe objectif */
.foci-obj-group { border-top:2px solid #1e1b4b; }
.foci-obj-row {
  display:flex; align-items:center; gap:.55rem;
  padding:.3rem .9rem;
  background:linear-gradient(135deg,#1e1b4b 0%,#312e81 100%);
  color:#fff;
}
.foci-obj-label {
  font-size:.55rem; font-weight:700; text-transform:uppercase;
  letter-spacing:.07em; opacity:.7; white-space:nowrap;
  background:rgba(255,255,255,.12); padding:.06rem .35rem; border-radius:3px;
}
.foci-obj-text  { font-size:.75rem; font-weight:700; flex:1; }
.foci-obj-count {
  font-size:.58rem; background:rgba(255,255,255,.18);
  padding:.06rem .42rem; border-radius:20px; font-weight:600; white-space:nowrap;
}

/* Table FOCI principale */
.foci-tbl {
  width:100%; border-collapse:collapse;
  font-size:.63rem; min-width:1100px;
}

/* En-têtes double niveau */
.foci-thead-top th,
.foci-thead-sub th {
  padding:.22rem .32rem;
  text-align:left;
  border-right:1px solid rgba(255,255,255,.12);
  border-bottom:1px solid rgba(255,255,255,.2);
  vertical-align:middle;
  white-space:nowrap;
}
.foci-thead-top th {
  background:#1e1b4b; color:#fff;
  font-size:.56rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em;
}
.foci-th-center { text-align:center; }
.foci-th-group {
  text-align:center; font-style:italic; font-size:.58rem; font-weight:600;
}
.foci-th-constats      { background:#312e81; border-right:2px solid rgba(255,255,255,.3) !important; }
.foci-th-recommandations { background:#3730a3; border-right:2px solid rgba(255,255,255,.3) !important; }
.foci-thead-sub th {
  background:#3730a3; color:rgba(255,255,255,.82);
  font-size:.52rem; font-style:italic;
}

/* Cellules de données */
.foci-tbl td {
  padding:.22rem .32rem;
  border-bottom:1px solid #f1f5f9;
  border-right:1px solid #e9d5ff;
  vertical-align:top;
  background:#fff;
}
.foci-tbl td:last-child { border-right:none; }
.foci-row:hover td { background:#faf5ff; }

/* Cellule N° FRAP */
.foci-num-cell { text-align:center; white-space:nowrap; vertical-align:middle; }
.frap-num {
  display:inline-block; background:#1e1b4b; color:#fff;
  padding:.06rem .38rem; border-radius:3px; font-size:.58rem; font-weight:800;
  font-family:monospace; white-space:nowrap;
}

/* NCI select compact */
.foci-nci-sel { width:46px; text-align:center; }

/* Cellules Rubrique / Sous-rubrique fusionnées */
.foci-rub-cell,
.foci-subrub-cell {
  background:#f5f3ff !important;
  border-right:2px solid #ddd6fe !important;
  vertical-align:top;
}
.foci-rub-lbl {
  font-size:.65rem; font-weight:600; color:#5b21b6; line-height:1.5;
}

/* NCI couleurs */
.nci-badge { display:inline-block; padding:.04rem .3rem; border-radius:3px; font-size:.6rem; font-weight:700; }
.nci-b--1 { background:#fee2e2; color:#dc2626; }
.nci-b--2 { background:#fef3c7; color:#b45309; }
.nci-b--3 { background:#fefce8; color:#854d0e; }
.nci-b--4 { background:#dcfce7; color:#166534; }
.nci-b--5 { background:#d1fae5; color:#065f46; }

/* Couleurs lignes selon NCI */
.foci-row.nci--1 td { border-left:2px solid #fca5a5; }
.foci-row.nci--2 td { border-left:2px solid #fcd34d; }
.foci-row.nci--3 td { border-left:2px solid #fde68a; }
.foci-row.nci--4 td { border-left:2px solid #86efac; }
.foci-row.nci--5 td { border-left:2px solid #6ee7b7; }

/* Texte cellule lecture seule */
.cell-txt { font-size:.63rem; color:var(--tx); line-height:1.45; white-space:pre-wrap; }

/* Note de bas */
.foci-note {
  display:flex; align-items:flex-start; gap:.38rem;
  padding:.45rem .9rem;
  background:#fef3c7; border-top:1px solid #fde68a;
  font-size:.62rem; color:#92400e; font-style:italic;
  line-height:1.5;
}

/* ═══════════════ MODAL OUTILS ═══════════════ */
.modal-sm  { background:var(--wh); border-radius:12px; width:92%; max-width:520px; max-height:82vh; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 16px 50px rgba(0,0,0,.22); }
.modal-hd  { display:flex; justify-content:space-between; align-items:center; padding:.72rem 1rem; border-bottom:1px solid var(--bd); }
.modal-title { font-size:.88rem; font-weight:600; }
.modal-ft  { display:flex; justify-content:flex-end; gap:.45rem; padding:.65rem 1rem; background:var(--bg); border-top:1px solid var(--bd); flex-shrink:0; }
.drawer-ico  { width:34px; height:34px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.drawer-close { display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px; border-radius:5px; border:1px solid var(--bd); background:var(--bg); cursor:pointer; font-size:.75rem; color:var(--muted); }
.om-selbar { display:flex; justify-content:space-between; align-items:center; padding:.4rem .9rem; background:var(--bg); border-bottom:1px solid var(--bd); min-height:36px; }
.chip-rm   { background:none; border:none; color:#fff; cursor:pointer; font-size:.75rem; padding:0 .1rem; line-height:1; }
.om-body   { flex:1; overflow-y:auto; padding:.75rem; }
.om-grid   { display:grid; grid-template-columns:repeat(auto-fill,minmax(130px,1fr)); gap:.35rem; }
.om-card   { display:flex; align-items:center; gap:.38rem; padding:.4rem; border:2px solid var(--bd); border-radius:8px; cursor:pointer; background:var(--wh); width:100%; text-align:left; transition:all .15s; }
.om-card:hover { border-color:var(--p); background:var(--bg); }
.om-card--sel  { border-color:var(--p); background:var(--pl); }
.om-card__num  { width:28px; height:28px; border-radius:6px; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:.62rem; flex-shrink:0; }
.om-card__lbl  { font-size:.63rem; font-weight:500; color:var(--tx); }

/* ── TOAST ── */
.toast { position:fixed; bottom:1rem; right:1rem; display:flex; align-items:center; gap:.38rem; padding:.42rem .85rem; border-radius:8px; font-size:.7rem; z-index:2000; box-shadow:0 4px 14px rgba(0,0,0,.14); }
.toast--success{background:#065f46;color:#fff} .toast--error{background:#dc2626;color:#fff}
.toast__x { background:none; border:none; color:inherit; opacity:.7; cursor:pointer; margin-left:.35rem; }

/* ── SPINNER ── */
.spin { display:inline-block; width:.72rem; height:.72rem; border:2px solid rgba(255,255,255,.3); border-top-color:#fff; border-radius:50%; animation:sp .7s linear infinite; }
.spin--sm { width:.55rem; height:.55rem; }
.spin--lg { width:1.8rem; height:1.8rem; border-width:3px; border-color:rgba(109,40,217,.2); border-top-color:var(--p); }
@keyframes sp { to { transform:rotate(360deg); } }

/* ── TRANSITIONS ── */
.modal-fade-enter-active,.modal-fade-leave-active { transition:all .2s ease; }
.modal-fade-enter-from,.modal-fade-leave-to       { opacity:0; }
.modal-fade-enter-from .modal-synthese,.modal-fade-leave-to .modal-synthese,
.modal-fade-enter-from .modal-sm,.modal-fade-leave-to .modal-sm { transform:scale(.97) translateY(8px); }
.toast-pop-enter-active,.toast-pop-leave-active { transition:all .2s; }
.toast-pop-enter-from,.toast-pop-leave-to       { opacity:0; transform:translateY(8px); }

/* ── RESPONSIVE ── */
@media (max-width:900px) {
  .sy-meta,.sy-niveaux,.form-grid--2,.niveaux-grid,.outils-grid,.fi-meta { grid-template-columns:1fr; }
  .sy-meta { grid-template-columns:1fr 1fr; }
  .form-grid--3 { grid-template-columns:1fr 1fr; }
  .tests-list .test-row { grid-template-columns:50px 1fr 95px; }
  .test-acts { grid-column:1/-1; }
  .sy-kpi { min-width:calc(33% - 1px); }
  .foci-header__grid { grid-template-columns:1fr 1fr 1fr; }
}
@media (max-width:600px) {
  .topbar { flex-direction:column; align-items:flex-start; }
  .tests-list .test-row { grid-template-columns:1fr; }
  .form-grid--3 { grid-template-columns:1fr; }
  .sy-obs-grid,.sy-outils-grid { grid-template-columns:1fr; }
  .sy-kpi { min-width:calc(50% - 1px); }
  .obs-cta { flex-direction:column; align-items:flex-start; }
  .foci-header__grid { grid-template-columns:1fr 1fr; }
}
</style>

<style>
/* ═══════════════ EN-TÊTE FOCI ═══════════════ */

.foci-doc {
  background: var(--wh);
  border: 2px solid #1e1b4b;
  border-radius: 9px;
  overflow: hidden;
  margin-bottom: .55rem;
}

/* Bandeau supérieur */
.foci-doc__banner {
  display: grid;
  grid-template-columns: 1fr 2fr 140px;
  border-bottom: 1px solid #c7d2fe;
  background: #eef2ff;
}

.foci-doc__org-block {
  padding: .5rem .7rem;
  border-right: 1px solid #c7d2fe;
  display: flex;
  flex-direction: column;
  gap: .18rem;
}
.foci-doc__zone-lbl {
  font-size: .52rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .06em;
  color: #4338ca;
  margin-bottom: .1rem;
}
.foci-doc__org-val {
  font-size: .75rem;
  font-weight: 600;
  color: #1e293b;
  min-height: 20px;
}

.foci-doc__title-block {
  padding: .5rem .7rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border-right: 1px solid #c7d2fe;
  gap: .15rem;
}
.foci-doc__main-title {
  font-size: 1rem;
  font-weight: 800;
  color: #1e1b4b;
  text-align: center;
  letter-spacing: .02em;
}
.foci-doc__subtitle {
  font-size: .6rem;
  color: #6366f1;
  font-style: italic;
  text-align: center;
}

.foci-doc__date-block {
  padding: .5rem .7rem;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: .15rem;
}

/* Ligne identification */
.foci-doc__ident {
  display: grid;
  grid-template-columns: 90px 120px 1fr 140px;
  border-bottom: 1px solid #c7d2fe;
  background: #f5f3ff;
}
.foci-doc__ident-cell {
  padding: .35rem .6rem;
  border-right: 1px solid #c7d2fe;
  display: flex;
  flex-direction: column;
  gap: .1rem;
}
.foci-doc__ident-cell:last-child { border-right: none; }
.foci-doc__ident-cell--wide { flex: 1; }

.foci-doc__field-lbl {
  font-size: .54rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .05em;
  color: #7c3aed;
}
.foci-doc__field-val {
  font-size: .72rem;
  color: #1e293b;
  min-height: 18px;
}

/* Ligne Entité */
.foci-doc__entite {
  display: flex;
  align-items: center;
  gap: .6rem;
  padding: .35rem .7rem;
  background: #ede9fe;
  border-top: 1px solid #c4b5fd;
}
.foci-doc__entite-lbl {
  font-size: .6rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .07em;
  color: #5b21b6;
  white-space: nowrap;
}
.foci-doc__entite-inp { flex: 1; }

/* ═══════════════ CARTE FOCI ═══════════════ */

.foci-card {
  background: var(--wh);
  border: 1px solid #e9d5ff;
  border-radius: 9px;
  overflow: hidden;
}
.foci-card__hd {
  display: flex;
  align-items: center;
  gap: .35rem;
  padding: .5rem .9rem;
  background: linear-gradient(135deg, #ede9fe, #f5f3ff);
  border-bottom: 1px solid #ddd6fe;
  color: #5b21b6;
  font-size: .72rem;
  font-weight: 700;
  flex-wrap: wrap;
}

/* ═══════════════ LIGNES STRUCTURELLES (Objectif / Rubrique / Sous-rubrique) ═══════════════ */

.foci-sec-label {
  white-space: nowrap;
  font-size: .56rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .05em;
  vertical-align: middle;
  padding: .22rem .45rem;
}
.foci-sec-hint {
  display: block;
  font-size: .5rem;
  font-weight: 400;
  font-style: italic;
  text-transform: none;
  letter-spacing: 0;
  opacity: .75;
  margin-top: 2px;
}
.foci-sec-val { vertical-align: middle; padding: .22rem .45rem; }
.foci-sec-text { font-size: .72rem; font-weight: 600; }
.foci-sec-input { width: 100%; min-width: 200px; font-weight: 600; }

/* Objectif de contrôle */
.foci-obj-tr .foci-sec-label--obj {
  background: #1e1b4b;
  color: rgba(255,255,255,.7);
}
.foci-obj-tr .foci-sec-val--obj {
  background: linear-gradient(90deg, #1e1b4b, #312e81);
}
.foci-obj-tr .foci-sec-text { color: #fff; font-size: .76rem; }
.foci-obj-tr .foci-sec-input {
  background: rgba(255,255,255,.12);
  border-color: rgba(255,255,255,.25);
  color: #fff;
}
.foci-obj-tr .foci-sec-input::placeholder { color: rgba(255,255,255,.5); }

/* Rubrique */
.foci-rub-tr .foci-sec-label--rub {
  background: #312e81;
  color: rgba(255,255,255,.7);
}
.foci-rub-tr .foci-sec-val--rub {
  background: #eef2ff;
  border-bottom: 1px solid #c7d2fe;
}
.foci-rub-tr .foci-sec-text { color: #3730a3; font-size: .7rem; }

/* Sous-rubrique */
.foci-subrub-tr .foci-sec-label--sub {
  background: #4338ca;
  color: rgba(255,255,255,.65);
}
.foci-subrub-tr .foci-sec-val--sub {
  background: #f5f3ff;
  border-bottom: 1px solid #ddd6fe;
}
.foci-subrub-tr .foci-sec-text { color: #5b21b6; font-style: italic; font-size: .68rem; }

/* Hint dans les en-têtes de colonnes */
.foci-th-hint {
  display: block;
  font-size: .5rem;
  font-style: italic;
  font-weight: 400;
  opacity: .75;
  margin-top: 2px;
}

/* Impression */
@media print {
  .foci-doc__banner { grid-template-columns: 1fr 2fr 130px !important; }
  .foci-doc__ident  { grid-template-columns: 80px 110px 1fr 130px !important; }
  .add-btn, .del-btn, .inp-sm, .sel-sm, .ta-sm, .inp-sy, .btn { display: none !important; }
  .foci-sec-input { display: none !important; }
  .foci-sec-text  { display: inline !important; }
  .modal-synthese { max-height: none !important; box-shadow: none !important; }
}

@media (max-width: 900px) {
  .foci-doc__banner { grid-template-columns: 1fr 1fr; }
  .foci-doc__date-block { grid-column: 1 / -1; }
  .foci-doc__ident { grid-template-columns: 1fr 1fr; }
  .foci-doc__ident-cell--wide { grid-column: 1 / -1; }
}
</style>