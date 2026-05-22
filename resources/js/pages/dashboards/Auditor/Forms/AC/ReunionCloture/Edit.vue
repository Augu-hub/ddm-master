<template>
  <VerticalLayoutAudit>
    <div class="rc-shell">

      <!-- ══ TOPBAR ══════════════════════════════════════════════ -->
      <header class="rc-topbar">
        <div class="rc-topbar__left">
          <a :href="props.backUrl" class="rc-ib" title="Retour"><i class="ti ti-arrow-left"></i></a>
          <span class="rc-code">{{ reunion?.id ? 'RC-' + String(reunion.id).padStart(4,'0') : 'RC-NOUVEAU' }}</span>
          <span class="rc-sdot" :class="'sd--' + (rcForm.statut ?? 'draft')"></span>
          <span class="rc-vstatus">{{ vstLbl(rcForm.statut ?? 'draft') }}</span>
          <div class="rc-div"></div>
          <i class="ti ti-building rc-icon-muted"></i>
          <span class="rc-mission-lbl">{{ missionLibelle }}</span>
          <span v-if="props.currentAuditeur" class="rc-chip-role">
            <i class="ti ti-shield-half"></i>{{ props.currentAuditeur.role_code }}
          </span>
        </div>
        <div class="rc-topbar__right">
          <div class="rc-tabs">
            <button v-for="tab in tabs" :key="tab.id" class="rc-tab"
              :class="activeTab === tab.id ? 'rc-tab--active' : ''"
              @click="activeTab = tab.id">
              <i :class="'ti ' + tab.icon"></i> {{ tab.label }}
            </button>
          </div>
          <button class="rc-btn rc-btn--slides" @click="ouvrirSlides(0)" title="Mode présentation">
            <i class="ti ti-presentation"></i> Présentation
          </button>
          <template v-if="!isLocked">
            <button class="rc-btn rc-btn--ghost" :disabled="saving" @click="annuler">
              <i class="ti ti-x"></i>
            </button>
            <button class="rc-btn rc-btn--save" :disabled="saving" @click="sauvegarder">
              <span v-if="saving" class="rc-spin"></span>
              <i v-else class="ti ti-device-floppy"></i> Enregistrer
            </button>
            <button v-if="reunion?.id && rcForm.statut === 'draft'" class="rc-btn rc-btn--submit" @click="soumettre">
              <i class="ti ti-send"></i> Soumettre
            </button>
            <button v-if="!reunion?.id" class="rc-btn rc-btn--submit" :disabled="saving" @click="creer">
              <span v-if="saving" class="rc-spin"></span>
              <i v-else class="ti ti-plus"></i> Créer le PV
            </button>
          </template>
          <template v-if="props.canManage && rcForm.statut === 'in_review'">
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
      <div v-if="rcForm.statut === 'validated'" class="rc-banner rc-banner--ok">
        <i class="ti ti-lock"></i> PV validé — lecture seule
      </div>
      <div v-else-if="rcForm.statut === 'in_review'" class="rc-banner rc-banner--review">
        <i class="ti ti-clock"></i> En attente de validation — MPA 2400
      </div>
      <div v-else-if="rcForm.statut === 'draft' && reunion?.validation_note" class="rc-banner rc-banner--rejected">
        <i class="ti ti-circle-x"></i> Rejeté — <em>{{ reunion.validation_note }}</em>
      </div>

      <!-- ══ SLIDES POWERPOINT — VUE PLEIN ÉCRAN ══════════════════ -->
      <transition name="slides-fade">
        <div v-if="slidesMode" class="pres-overlay" tabindex="-1" ref="overlayRef" @keydown="onSlideKey">
          <div class="pres-shell">

            <!-- Topbar slides -->
            <div class="pres-topbar">
              <button class="pres-close-btn" @click="slidesMode = false">
                <i class="ti ti-x"></i> Quitter
              </button>
              <div class="pres-topbar__mission">
                <i class="ti ti-building" style="opacity:.4;font-size:.75rem"></i>
                <span>{{ missionLibelle }}</span>
                <span class="pres-sep">·</span>
                <span>{{ formatDate(rcForm.date_reunion) || 'Date à définir' }}</span>
              </div>
              <div class="pres-topbar__center">
                <div class="pres-slide-counter">
                  <span class="pres-slide-n">{{ curSlideIdx + 1 }}</span>
                  <span style="opacity:.4">/</span>
                  <span class="pres-slide-t">{{ slides.length }}</span>
                </div>
              </div>
              <div class="pres-topbar__right">
                <transition name="save-fade">
                  <span v-if="lastSaved" class="pres-saved">
                    <i class="ti ti-circle-check"></i> {{ lastSaved }}
                  </span>
                </transition>
                <button v-if="!isLocked" class="pres-save-btn" :disabled="saving" @click="sauvegarder">
                  <span v-if="saving" class="rc-spin"></span>
                  <i v-else class="ti ti-device-floppy"></i> Enregistrer
                </button>
              </div>
            </div>

            <!-- Corps: sidebar + stage -->
            <div class="pres-body">

              <!-- Sidebar menu vertical -->
              <nav class="pres-sidebar">
                <div class="pres-sidebar__hd">
                  <i class="ti ti-presentation"></i>
                  <span>Navigation</span>
                </div>
                <div class="pres-sidebar__list">
                  <button v-for="(g, gi) in slideGroups" :key="gi"
                    class="pres-nav-item"
                    :class="{ 'pres-nav-item--active': activeGroup === gi }"
                    @click="goGroup(gi)">
                    <div class="pres-nav-item__num">{{ gi + 1 }}</div>
                    <div class="pres-nav-item__body">
                      <i :class="'ti ' + g.icon" style="font-size:.75rem;opacity:.7"></i>
                      <span class="pres-nav-item__label">{{ g.label }}</span>
                      <span v-if="g.count" class="pres-nav-item__count">{{ g.count }}</span>
                    </div>
                  </button>
                </div>
              </nav>

              <!-- Stage des slides -->
              <main class="pres-stage">
                <div class="pres-progress">
                  <div class="pres-progress__bar"
                    :style="{ width: ((curSlideIdx + 1) / slides.length * 100) + '%' }"></div>
                </div>

                <transition name="slide-anim" mode="out-in">
                  <div :key="curSlideIdx" class="pres-slide-wrap">

                    <!-- ▸ COUVERTURE ──────────────────────────────── -->
                    <div v-if="curSlide.type === 'cover'" class="sl sl--cover">
                      <div class="sl-cover__left">
                        <div class="sl-cover__logo"><i class="ti ti-building-bank"></i></div>
                        <div class="sl-cover__norme">MPA 2400 – 2410-1</div>
                        <div class="sl-cover__eyebrow">Réunion de clôture · Phase AC</div>
                        <h1 class="sl-cover__h1">{{ missionLibelle }}</h1>
                        <div v-if="rcForm.entite" class="sl-cover__entite">{{ rcForm.entite }}</div>
                        <div class="sl-cover__meta">
                          <div v-if="rcForm.date_reunion" class="sl-cover__meta-row">
                            <i class="ti ti-calendar"></i><span>{{ formatDate(rcForm.date_reunion) }}</span>
                          </div>
                          <div v-if="rcForm.lieu" class="sl-cover__meta-row">
                            <i class="ti ti-map-pin"></i><span>{{ rcForm.lieu }}</span>
                          </div>
                          <div v-if="rcForm.heure_debut" class="sl-cover__meta-row">
                            <i class="ti ti-clock"></i>
                            <span>{{ rcForm.heure_debut }}{{ rcForm.heure_fin ? ' – ' + rcForm.heure_fin : '' }}</span>
                          </div>
                          <div v-if="rcForm.preside_par" class="sl-cover__meta-row">
                            <i class="ti ti-user"></i><span>{{ rcForm.preside_par }}</span>
                          </div>
                        </div>
                      </div>
                      <div class="sl-cover__right">
                        <div class="sl-cover__equipe-title">Équipe d'audit</div>
                        <div class="sl-cover__equipe">
                          <div v-for="m in props.equipe" :key="m.id" class="sl-cover__membre">
                            <span class="sl-cover__role" :class="'role--' + m.role_code">{{ m.role_code }}</span>
                            <div>
                              <div class="sl-cover__nom">{{ m.full_name }}</div>
                              <div class="sl-cover__role-lbl">{{ m.role_label }}</div>
                            </div>
                          </div>
                        </div>
                        <div class="sl-cover__kpis">
                          <div class="sl-cover__kpi">
                            <div class="sl-cover__kpi-val">{{ props.totalFraps ?? 0 }}</div>
                            <div class="sl-cover__kpi-lbl">FRAPs</div>
                          </div>
                          <div class="sl-cover__kpi">
                            <div class="sl-cover__kpi-val">{{ props.fichesTest?.length ?? 0 }}</div>
                            <div class="sl-cover__kpi-lbl">Fiches test</div>
                          </div>
                          <div v-if="props.scoreIaMoyen !== null" class="sl-cover__kpi">
                            <div class="sl-cover__kpi-val" :style="{ color: scoreColor(props.scoreIaMoyen ?? 0) }">
                              {{ props.scoreIaMoyen }}/10
                            </div>
                            <div class="sl-cover__kpi-lbl">Score IA</div>
                          </div>
                        </div>
                        <div class="sl-cover__niveaux">
                          <div v-for="(count, niv) in props.statsNiveaux" :key="niv"
                            class="sl-cover__niv-chip"
                            :style="{ background: nivMap[niv]?.bg, color: nivMap[niv]?.color }">
                            {{ nivMap[niv]?.label || niv }} {{ count }}
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- ▸ OBJECTIFS ODJ 1 ─────────────────────────── -->
                    <div v-else-if="curSlide.type === 'objectifs'" class="sl sl--std">
                      <div class="sl-std__hd" style="--ic:#1e40af">
                        <div class="sl-std__icon"><i class="ti ti-target"></i></div>
                        <div>
                          <div class="sl-std__eyebrow">Ordre du jour · Point 1</div>
                          <div class="sl-std__title">Objectifs de la mission d'audit</div>
                        </div>
                        <div v-if="props.programmeData?.found" class="sl-std__badge">
                          {{ props.programmeData.programme_code }} · {{ props.programmeData.total_objectifs }} obj. · {{ props.programmeData.total_tests }} tests
                        </div>
                      </div>
                      <div class="sl-std__body">
                        <div v-if="props.mission?.objectif" class="sl-obj-mission">
                          <div class="sl-obj-mission__lbl">Objectif général</div>
                          <p>{{ props.mission.objectif }}</p>
                        </div>
                        <div v-if="!props.programmeData?.found" class="sl-empty">
                          <i class="ti ti-clipboard-off"></i><span>Aucun programme trouvé</span>
                        </div>
                        <div v-else class="sl-obj-list">
                          <div v-for="(obj, oi) in props.programmeData.objectifs" :key="oi" class="sl-obj-block">
                            <div class="sl-obj-block__hd">
                              <span class="sl-obj-num">{{ obj.num || (oi + 1) }}</span>
                              <span class="sl-obj-label">{{ obj.objectif_controle ?? obj.objectif ?? obj.libelle }}</span>
                            </div>
                            <div class="sl-obj-tests">
                              <div v-for="(t, ti) in (obj.tests ?? []).slice(0, 5)" :key="ti" class="sl-obj-test">
                                <code class="sl-obj-test__ref">{{ t.ref ?? (String(oi + 1) + '.' + String(ti + 1)) }}</code>
                                <span class="sl-obj-test__lib">{{ t.libelle }}</span>
                              </div>
                              <div v-if="(obj.tests?.length ?? 0) > 5" class="sl-obj-more">
                                +{{ obj.tests.length - 5 }} tests…
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- ▸ POINTS FORTS ODJ 2 ─────────────────────── -->
                    <div v-else-if="curSlide.type === 'points_forts'" class="sl sl--std">
                      <div class="sl-std__hd" style="--ic:#065f46">
                        <div class="sl-std__icon"><i class="ti ti-star"></i></div>
                        <div>
                          <div class="sl-std__eyebrow">Ordre du jour · Point 2</div>
                          <div class="sl-std__title">Points forts de l'entité auditée</div>
                        </div>
                        <span class="sl-std__badge">{{ rcForm.points_forts.length }} point(s)</span>
                        <button v-if="!isLocked" class="sl-add-btn" @click="rcForm.points_forts.push({ libelle: '' })">
                          <i class="ti ti-plus"></i> Ajouter
                        </button>
                      </div>
                      <div class="sl-std__body">
                        <div v-if="!rcForm.points_forts.length" class="sl-empty">
                          <i class="ti ti-star-off"></i><span>Aucun point fort enregistré</span>
                        </div>
                        <div v-else class="sl-pf-grid">
                          <div v-for="(pf, pi) in rcForm.points_forts" :key="pi" class="sl-pf-card">
                            <div class="sl-pf-card__ico">✅</div>
                            <div class="sl-pf-card__body">
                              <input v-if="!isLocked" class="sl-pf-card__inp"
                                v-model="pf.libelle" @blur="sauvegarder" placeholder="Point fort…" />
                              <div v-else class="sl-pf-card__txt">{{ pf.libelle }}</div>
                            </div>
                            <button v-if="!isLocked" class="sl-pf-card__del"
                              @click="rcForm.points_forts.splice(pi, 1); sauvegarder()">
                              <i class="ti ti-x"></i>
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- ▸ SYNTHÈSE FOCI ───────────────────────────── -->
                    <div v-else-if="curSlide.type === 'foci'" class="sl sl--std sl--wide">
                      <div class="sl-std__hd" style="--ic:#5b21b6">
                        <div class="sl-std__icon"><i class="ti ti-file-text"></i></div>
                        <div>
                          <div class="sl-std__eyebrow">Synthèse FOCI</div>
                          <div class="sl-std__title">Feuille d'Observations Contrôle Interne</div>
                        </div>
                        <div class="sl-niv-chips">
                          <span v-for="(count, niv) in props.statsNiveaux" :key="niv"
                            class="sl-niv-chip" :style="{ background: nivMap[niv]?.bg, color: nivMap[niv]?.color }">
                            {{ nivMap[niv]?.label || niv }} {{ count }}
                          </span>
                        </div>
                      </div>
                      <div class="sl-std__body sl-std__body--scroll">
                        <div v-if="!props.fociGrouped?.length" class="sl-empty">
                          <i class="ti ti-clipboard-off"></i><span>Aucune FRAP disponible</span>
                        </div>
                        <div v-else>
                          <div class="sl-foci-legend">
                            <div>N° FRAP</div><div>Niveau CI</div>
                            <div>Fait / Constat</div><div>Recommandation</div>
                          </div>
                          <template v-for="(group, gi) in props.fociGrouped" :key="gi">
                            <div class="sl-foci-obj-banner">
                              <span class="sl-foci-obj__lbl">Objectif</span>
                              <span class="sl-foci-obj__txt">{{ group.objectif_controle }}</span>
                            </div>
                            <template v-for="(rub, ri) in group.rubriques" :key="ri">
                              <div class="sl-foci-rub"><i class="ti ti-tag"></i> {{ rub.rubrique }}</div>
                              <template v-for="(ss, si) in rub.sous_rubriques" :key="si">
                                <div v-if="ss.sous_rubrique" class="sl-foci-ssrub">
                                  <i class="ti ti-corner-down-right"></i> {{ ss.sous_rubrique }}
                                </div>
                                <div v-for="frap in ss.fraps" :key="frap.id"
                                  class="sl-foci-row"
                                  :class="'focirow--' + (frap.niveau_controle_interne || 'nc')"
                                  @click="ouvrirFrapModal(frap)"
                                  title="Cliquer pour voir la fiche d'observation">
                                  <div class="sl-foci-cell">
                                    <span class="sl-foci-num-badge">{{ frap.num_frap }}</span>
                                    <i class="ti ti-external-link" style="font-size:.45rem;opacity:.4;margin-left:2px"></i>
                                  </div>
                                  <div class="sl-foci-cell">
                                    <span class="sl-niv-badge" :style="nivBadgeStyle(frap.niveau_controle_interne)">
                                      {{ nivLbl(frap.niveau_controle_interne) }}
                                    </span>
                                  </div>
                                  <div class="sl-foci-cell sl-foci-cell--txt">{{ frap.fait_constats || '—' }}</div>
                                  <div class="sl-foci-cell sl-foci-cell--reco">{{ frap.recommandation || '—' }}</div>
                                </div>
                              </template>
                            </template>
                          </template>
                        </div>
                      </div>
                      <div class="sl-foci-hint">
                        <i class="ti ti-info-circle"></i>
                        Cliquez sur une ligne FRAP pour voir la fiche d'observation complète
                      </div>
                    </div>

                    <!-- ▸ FAR — une slide par FAR ────────────────── -->
                    <div v-else-if="curSlide.type === 'far'" class="sl sl--far">
                      <div class="sl-far-hd" :class="'sl-far-hd--' + (curFar?.acceptation || 'en_discussion')">
                        <div class="sl-far-hd__left">
                          <span class="sl-far-num">{{ curFar?.num_far }}</span>
                          <div>
                            <div class="sl-far-rubrique">
                              {{ curFar?.rubrique || "Observation d'audit" }}
                              <span v-if="curFar?.frap_id" class="sl-far-linked">
                                <i class="ti ti-link"></i> FRAP liée
                              </span>
                            </div>
                            <div class="sl-far-eyebrow">Ordre du jour · Point 3 — Validation des FAR</div>
                          </div>
                        </div>
                        <div class="sl-far-hd__right">
                          <div class="sl-far-accept-grp">
                            <button v-for="opt in acceptOptions" :key="opt.val"
                              class="sl-far-accept-btn"
                              :class="{ 'active': curFar?.acceptation === opt.val }"
                              :style="curFar?.acceptation === opt.val ? opt.activeStyle : {}"
                              @click="!isLocked && saveFarField(curFar, 'acceptation', opt.val)"
                              :disabled="isLocked">
                              {{ opt.icon }} {{ opt.label }}
                            </button>
                          </div>
                          <button v-if="curFar?.frap_id" class="sl-far-obs-btn"
                            @click="ouvrirFrapParId(curFar.frap_id)">
                            <i class="ti ti-eye"></i> Fiche d'observation
                          </button>
                        </div>
                      </div>

                      <div class="sl-far-body">
                        <div class="sl-far-col">
                          <div class="sl-far-section">
                            <div class="sl-far-section__title sl-far-section__title--red">
                              <i class="ti ti-clipboard-text"></i> Fait / Constat
                            </div>
                            <div class="sl-far-txt">{{ curFar?.faits || '—' }}</div>
                          </div>
                          <div class="sl-far-section">
                            <div class="sl-far-section__title">
                              <i class="ti ti-list-check"></i> Causes identifiées
                            </div>
                            <div class="sl-far-txt">{{ curFar?.causes || '—' }}</div>
                          </div>
                          <div class="sl-far-section">
                            <div class="sl-far-section__title sl-far-section__title--orange">
                              <i class="ti ti-alert-triangle"></i> Impacts / Conséquences
                            </div>
                            <div class="sl-far-txt">{{ curFar?.impacts || '—' }}</div>
                          </div>
                        </div>
                        <div class="sl-far-col">
                          <div class="sl-far-section">
                            <div class="sl-far-section__title sl-far-section__title--blue">
                              <i class="ti ti-bulb"></i> Recommandation
                            </div>
                            <div class="sl-far-txt sl-far-txt--reco">{{ curFar?.recommandations || '—' }}</div>
                          </div>
                          <div class="sl-far-plan">
                            <div class="sl-far-plan__title">Plan d'action</div>
                            <div class="sl-far-plan__row">
                              <span class="sl-far-plan__lbl">Échéance</span>
                              <input v-if="!isLocked" type="date" class="sl-far-plan__inp"
                                :value="curFar?.date_echeance"
                                @change="saveFarField(curFar, 'date_echeance', ($event.target as HTMLInputElement).value)" />
                              <span v-else>{{ formatDate(curFar?.date_echeance) || '—' }}</span>
                            </div>
                            <div class="sl-far-plan__row">
                              <span class="sl-far-plan__lbl">Responsable</span>
                              <input v-if="!isLocked" class="sl-far-plan__inp"
                                :value="curFar?.personne_responsable"
                                @blur="saveFarField(curFar, 'personne_responsable', ($event.target as HTMLInputElement).value)"
                                placeholder="Responsable…" />
                              <span v-else>{{ curFar?.personne_responsable || '—' }}</span>
                            </div>
                            <div class="sl-far-plan__row">
                              <span class="sl-far-plan__lbl">Livrable</span>
                              <input v-if="!isLocked" class="sl-far-plan__inp"
                                :value="curFar?.livrable"
                                @blur="saveFarField(curFar, 'livrable', ($event.target as HTMLInputElement).value)"
                                placeholder="Livrable…" />
                              <span v-else>{{ curFar?.livrable || '—' }}</span>
                            </div>
                            <div class="sl-far-checks">
                              <label class="sl-far-check">
                                <input type="checkbox" :checked="curFar?.pertinence === 'pertinente'"
                                  :disabled="isLocked"
                                  @change="saveFarField(curFar, 'pertinence', ($event.target as HTMLInputElement).checked ? 'pertinente' : 'non_pertinente')" />
                                <span>Pertinente</span>
                              </label>
                              <label class="sl-far-check">
                                <input type="checkbox" :checked="curFar?.faisabilite === 'faisable'"
                                  :disabled="isLocked"
                                  @change="saveFarField(curFar, 'faisabilite', ($event.target as HTMLInputElement).checked ? 'faisable' : 'non_faisable')" />
                                <span>Faisable</span>
                              </label>
                            </div>
                          </div>
                          <div class="sl-far-section">
                            <div class="sl-far-section__title">
                              <i class="ti ti-message"></i> Commentaires de l'audité
                            </div>
                            <textarea v-if="!isLocked" class="sl-far-ta" rows="3"
                              :value="curFar?.appreciation_audite"
                              @blur="saveFarField(curFar, 'appreciation_audite', ($event.target as HTMLTextAreaElement).value)"
                              placeholder="Réaction de l'entité auditée…"></textarea>
                            <div v-else class="sl-far-txt">{{ curFar?.appreciation_audite || '—' }}</div>
                          </div>
                        </div>
                      </div>

                      <div class="sl-far-nav">
                        <span class="sl-far-nav__info">FAR {{ farLocalIdx + 1 }} / {{ rcForm.far_validations.length }}</span>
                        <div class="sl-far-nav__btns">
                          <button class="sl-far-nav__btn" :disabled="farLocalIdx === 0" @click="goSlide(curSlideIdx - 1)">
                            <i class="ti ti-chevron-left"></i> FAR précédente
                          </button>
                          <button class="sl-far-nav__btn" :disabled="farLocalIdx >= rcForm.far_validations.length - 1" @click="goSlide(curSlideIdx + 1)">
                            FAR suivante <i class="ti ti-chevron-right"></i>
                          </button>
                        </div>
                        <button v-if="!isLocked" class="sl-far-refresh-btn" @click="refreshFraps">
                          <i class="ti ti-refresh"></i> Actualiser FRAPs
                        </button>
                        <button class="sl-far-detail-btn" @click="ouvrirFarDetail(curFar)">
                          <i class="ti ti-external-link"></i> Détail complet
                        </button>
                      </div>
                    </div>

                    <!-- ▸ SUIVI ODJ 4 ─────────────────────────────── -->
                    <div v-else-if="curSlide.type === 'suivi'" class="sl sl--std">
                      <div class="sl-std__hd" style="--ic:#92400e">
                        <div class="sl-std__icon"><i class="ti ti-calendar-check"></i></div>
                        <div>
                          <div class="sl-std__eyebrow">Ordre du jour · Point 4</div>
                          <div class="sl-std__title">Modalités de suivi du plan d'action</div>
                        </div>
                        <button v-if="!isLocked" class="sl-add-btn"
                          @click="rcForm.suivi_modalites.push({ date_rapport:'', delais_mise_oeuvre:'', modalites_suivi:'' })">
                          <i class="ti ti-plus"></i> Ajouter
                        </button>
                      </div>
                      <div class="sl-std__body">
                        <div v-if="!rcForm.suivi_modalites.length" class="sl-empty">
                          <i class="ti ti-calendar-off"></i><span>Aucune modalité définie</span>
                        </div>
                        <div v-else class="sl-suivi-cards">
                          <div v-for="(sm, si) in rcForm.suivi_modalites" :key="si" class="sl-suivi-card">
                            <div class="sl-suivi-card__num">{{ si + 1 }}</div>
                            <div class="sl-suivi-card__body">
                              <div v-if="!isLocked" class="sl-suivi-card__edit">
                                <input type="date" class="sl-suivi-inp" v-model="sm.date_rapport" @blur="sauvegarder" />
                                <input class="sl-suivi-inp" v-model="sm.delais_mise_oeuvre" @blur="sauvegarder" placeholder="Délais…" />
                                <textarea class="sl-suivi-ta" v-model="sm.modalites_suivi" rows="3" @blur="sauvegarder" placeholder="Modalités de suivi…"></textarea>
                                <button class="rc-del" @click="rcForm.suivi_modalites.splice(si,1);sauvegarder()">
                                  <i class="ti ti-trash"></i>
                                </button>
                              </div>
                              <div v-else>
                                <div class="sl-suivi-card__date"><i class="ti ti-calendar"></i> {{ formatDate(sm.date_rapport) || '—' }}</div>
                                <div class="sl-suivi-card__delais">Délai : {{ sm.delais_mise_oeuvre || '—' }}</div>
                                <div class="sl-suivi-card__modal">{{ sm.modalites_suivi || '—' }}</div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- ▸ PARTICIPANTS ────────────────────────────── -->
                    <div v-else-if="curSlide.type === 'participants'" class="sl sl--std">
                      <div class="sl-std__hd" style="--ic:#0f172a">
                        <div class="sl-std__icon"><i class="ti ti-users"></i></div>
                        <div>
                          <div class="sl-std__eyebrow">Participants</div>
                          <div class="sl-std__title">Présents à la réunion de clôture</div>
                        </div>
                        <span class="sl-std__badge">
                          {{ rcForm.participants.filter((p: any) => p.present).length }} présent(s) / {{ rcForm.participants.length }}
                        </span>
                        <button v-if="!isLocked" class="sl-add-btn" @click="rcForm.participants.push({ nom:'', fonction:'', entite:'', present:true })">
                          <i class="ti ti-plus"></i> Ajouter
                        </button>
                      </div>
                      <div class="sl-std__body">
                        <div v-if="!rcForm.participants.length" class="sl-empty">
                          <i class="ti ti-user-off"></i><span>Aucun participant</span>
                        </div>
                        <div v-else class="sl-part-grid">
                          <div v-for="(p, pi) in rcForm.participants" :key="pi"
                            class="sl-part-card" :class="p.present ? 'sl-part-card--ok' : 'sl-part-card--abs'">
                            <div class="sl-part-card__top">
                              <label class="sl-part-check">
                                <input type="checkbox" v-model="p.present" :disabled="isLocked" @change="sauvegarder" />
                                <span :class="p.present ? 'p-ok' : 'p-abs'">{{ p.present ? '✅ Présent' : '❌ Absent' }}</span>
                              </label>
                              <button v-if="!isLocked" class="rc-del" @click="rcForm.participants.splice(pi,1);sauvegarder()">
                                <i class="ti ti-x"></i>
                              </button>
                            </div>
                            <div v-if="!isLocked" class="sl-part-card__fields">
                              <input class="sl-part-inp" v-model="p.nom" @blur="sauvegarder" placeholder="Nom Prénom" />
                              <input class="sl-part-inp" v-model="p.fonction" @blur="sauvegarder" placeholder="Fonction" />
                              <input class="sl-part-inp" v-model="p.entite" @blur="sauvegarder" placeholder="Entité" />
                            </div>
                            <div v-else class="sl-part-card__view">
                              <div class="sl-part-card__nom">{{ p.nom || '—' }}</div>
                              <div class="sl-part-card__fn">{{ p.fonction }}</div>
                              <div class="sl-part-card__ent">{{ p.entite }}</div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- ▸ CONCLUSION ──────────────────────────────── -->
                    <div v-else-if="curSlide.type === 'conclusion'" class="sl sl--concl">
                      <div class="sl-concl__left">
                        <div class="sl-concl__icon"><i class="ti ti-flag"></i></div>
                        <div class="sl-concl__title">Conclusion</div>
                        <div class="sl-concl__norme">MPA 2400 – 2410-1</div>
                        <div class="sl-concl__mission">{{ missionLibelle }}</div>
                        <div v-if="rcForm.date_reunion" class="sl-concl__date">{{ formatDate(rcForm.date_reunion) }}</div>
                        <div class="sl-concl__stats">
                          <div class="sl-concl__stat">
                            <span class="sl-concl__stat-val">{{ props.totalFraps ?? 0 }}</span>
                            <span class="sl-concl__stat-lbl">FRAPs</span>
                          </div>
                          <div class="sl-concl__stat">
                            <span class="sl-concl__stat-val">{{ rcForm.far_validations.filter((f: any) => f.acceptation === 'accepte').length }}</span>
                            <span class="sl-concl__stat-lbl">Acceptées</span>
                          </div>
                        </div>
                        <button v-if="!isLocked" class="sl-concl__save-btn" :disabled="saving" @click="sauvegarder">
                          <span v-if="saving" class="rc-spin"></span>
                          <i v-else class="ti ti-device-floppy"></i>
                          Enregistrer le PV
                        </button>
                      </div>
                      <div class="sl-concl__right">
                        <label class="sl-concl__lbl">Conclusion générale</label>
                        <textarea v-if="!isLocked" class="sl-concl__ta" v-model="rcForm.conclusion_generale"
                          rows="6" placeholder="Synthèse de la réunion de clôture…"></textarea>
                        <div v-else class="sl-concl__ro">{{ rcForm.conclusion_generale || '—' }}</div>
                        <label class="sl-concl__lbl" style="margin-top:1rem">Observations finales</label>
                        <textarea v-if="!isLocked" class="sl-concl__ta" v-model="rcForm.observations_finales"
                          rows="4" placeholder="Observations finales…"></textarea>
                        <div v-else class="sl-concl__ro sl-concl__ro--obs">{{ rcForm.observations_finales || '—' }}</div>
                        <div class="sl-concl__merci">Merci pour votre participation</div>
                      </div>
                    </div>

                  </div>
                </transition>

                <!-- Flèches flottantes -->
                <button class="pres-arr pres-arr--prev" :disabled="curSlideIdx === 0" @click="goSlide(curSlideIdx - 1)">
                  <i class="ti ti-chevron-left"></i>
                </button>
                <button class="pres-arr pres-arr--next" :disabled="curSlideIdx >= slides.length - 1" @click="goSlide(curSlideIdx + 1)">
                  <i class="ti ti-chevron-right"></i>
                </button>
              </main>
            </div>
          </div>
        </div>
      </transition>

      <!-- ══ CONTENU PRINCIPAL ═════════════════════════════════ -->
      <div class="rc-main">

        <!-- ◈ ONGLET FICHE DE TEST ──────────────────────────── -->
        <div v-show="activeTab === 'fiche_test'" class="rc-ft-view">
          <div class="rc-mission-banner">
            <div class="rc-mb-left">
              <div class="rc-mb-icon"><i class="ti ti-clipboard-list"></i></div>
              <div class="rc-mb-body">
                <div class="rc-mb-title">{{ props.mission?.libelle }}</div>
                <div class="rc-mb-meta">
                  <span><i class="ti ti-hash"></i>{{ props.mission?.code_mission }}</span>
                  <span v-if="props.mission?.date_debut">
                    <i class="ti ti-calendar"></i>
                    {{ formatDate(props.mission.date_debut) }} → {{ formatDate(props.mission.date_fin) }}
                  </span>
                  <span v-if="props.mission?.lieux"><i class="ti ti-map-pin"></i>{{ props.mission.lieux }}</span>
                </div>
                <div class="rc-mb-kpis">
                  <div class="rc-mb-kpi"><div class="rc-mb-kpi__val">{{ props.totalFraps ?? 0 }}</div><div class="rc-mb-kpi__lbl">FRAPs</div></div>
                  <div class="rc-mb-kpi"><div class="rc-mb-kpi__val">{{ props.fichesTest?.length ?? 0 }}</div><div class="rc-mb-kpi__lbl">Fiches test</div></div>
                  <div class="rc-mb-kpi" v-if="props.scoreIaMoyen !== null">
                    <div class="rc-mb-kpi__val" :style="{ color: scoreColor(props.scoreIaMoyen ?? 0) }">{{ props.scoreIaMoyen }}/10</div>
                    <div class="rc-mb-kpi__lbl">Score IA</div>
                  </div>
                </div>
                <div class="rc-mb-niveaux">
                  <div v-for="s in statsGlobalesDisplay" :key="s.key" class="rc-mb-niv-chip"
                    :style="{ background: s.bg, color: s.color }">
                    {{ s.label }} <strong>{{ s.count }}</strong>
                  </div>
                </div>
              </div>
            </div>
            <div class="rc-mb-equipe">
              <div class="rc-mb-equipe__title"><i class="ti ti-users"></i> Équipe</div>
              <div v-for="m in props.equipe" :key="m.id" class="rc-mb-membre">
                <span class="rc-mb-role" :class="'role--' + m.role_code">{{ m.role_code }}</span>
                <div class="rc-mb-membre-info">
                  <span class="rc-mb-nom">{{ m.full_name }}</span>
                  <span class="rc-mb-role-lbl">{{ m.role_label }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="rc-section rc-section--ft">
            <div class="rc-section__title"><span><i class="ti ti-file-analytics"></i> Fiches de test</span></div>
            <div v-if="!props.fichesTest?.length" class="rc-ec rc-ec--pad">Aucune fiche de test trouvée.</div>
            <div v-else class="rc-ft-list">
              <div v-for="ft in props.fichesTest" :key="ft.id" class="rc-ft-card">
                <div class="rc-ft-card__head">
                  <span class="rc-ft-code">{{ ft.code }}</span>
                  <span class="rc-sdot" :class="'sd--' + ft.validation_status" style="width:8px;height:8px;display:inline-block;border-radius:50%;"></span>
                  <span class="rc-ft-statut">{{ vstLbl(ft.validation_status) }}</span>
                  <span class="rc-ft-auditeur">{{ ft.auditeur_nom }}</span>
                </div>
                <div v-if="iaForFt(ft.id)" class="rc-ft-ia">
                  <span class="rc-ia-score" :style="{ background: scoreColor(iaForFt(ft.id)?.score_global ?? 0) }">
                    {{ iaForFt(ft.id)?.score_global }}/10
                  </span>
                  <span class="rc-ia-concl">{{ iaForFt(ft.id)?.conclusion }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="rc-section rc-section--fraps">
            <div class="rc-section__title">
              <span><i class="ti ti-clipboard-text"></i> FRAPs — {{ props.totalFraps ?? 0 }} observations</span>
            </div>
            <div v-if="!props.fociGrouped?.length" class="rc-ec rc-ec--pad">Aucune FRAP pour cette mission.</div>
            <div v-for="(obj, oi) in props.fociGrouped" :key="oi" class="rc-foci-obj-block">
              <div class="rc-foci-obj-banner">
                <span class="rc-foci-obj-lbl">Objectif</span>
                <span class="rc-foci-obj-txt">{{ obj.objectif_controle }}</span>
              </div>
              <div v-for="(rub, ri) in obj.rubriques" :key="ri" class="rc-foci-rubr-block">
                <div class="rc-foci-rubr-banner"><i class="ti ti-tag"></i> {{ rub.rubrique }}</div>
                <div v-for="(ss, si) in rub.sous_rubriques" :key="si">
                  <div v-if="ss.sous_rubrique" class="rc-foci-ssrubr-banner">
                    <i class="ti ti-corner-down-right"></i> {{ ss.sous_rubrique }}
                  </div>
                  <div v-for="frap in ss.fraps" :key="frap.id"
                    class="rc-frap-row" :class="fociNiveauRowClass(frap.niveau_controle_interne)">
                    <div class="rc-frap-row__num">
                      <button class="rc-foci-num-btn" @click="ouvrirFrapModal(frap)">
                        <span class="rc-foci-num-badge">{{ frap.num_frap }}</span>
                      </button>
                    </div>
                    <div class="rc-frap-row__niv">
                      <span class="rc-foci-niv-badge" :style="fociNiveauStyle(frap.niveau_controle_interne)">
                        {{ fociNiveauLabel(frap.niveau_controle_interne) }}
                      </span>
                    </div>
                    <div class="rc-frap-row__fait">{{ frap.fait_constats || '—' }}</div>
                    <div class="rc-frap-row__reco">{{ frap.recommandation || '—' }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ◈ ONGLET PV DE CLÔTURE ─────────────────────────── -->
        <div v-show="activeTab === 'pv'" class="rc-pv-doc">
          <div class="rc-doc-header">
            <div class="rc-doc-header__title">PROCÈS-VERBAL DE RÉUNION DE CLÔTURE — PHASE DE VÉRIFICATION</div>
            <div class="rc-doc-header__sub">AUDIT INTERNE · Norme MPA 2400 – 2410-1</div>
          </div>

          <!-- Section 0 : Identification -->
          <div class="rc-section">
            <div class="rc-section__title">Identification de la mission</div>
            <div class="rc-grid rc-grid--3">
              <div class="rc-field rc-field--full"><label>Entité / Structure auditée</label><input class="rc-inp" v-model="rcForm.entite" :disabled="isLocked" /></div>
              <div class="rc-field rc-field--full"><label>Intitulé de la mission</label><input class="rc-inp" v-model="rcForm.intitule_mission" :disabled="isLocked" /></div>
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

          <!-- Section 1 : ODJ avec formulaires liés -->
          <div class="rc-section">
            <div class="rc-section__title">
              <span>1. Ordre du jour <span class="rc-badge-count">{{ rcForm.ordre_jour.length }}</span></span>
              <div class="rc-section__actions" v-if="!isLocked">
                <div class="rc-odj-add-wrap">
                  <select class="rc-sel-sm rc-odj-link-sel" v-model="newOdjLink">
                    <option value="">— Choisir un formulaire —</option>
                    <option value="objectifs_mission">Objectifs de la mission</option>
                    <option value="points_forts">Points forts</option>
                    <option value="far_validations">Validation FAR</option>
                    <option value="suivi_modalites">Modalités de suivi</option>
                    <option value="fiche_test">Fiche de test</option>
                    <option value="custom">Point libre</option>
                  </select>
                  <button class="rc-add-btn" @click="ajouterOdj"><i class="ti ti-plus"></i> Ajouter</button>
                </div>
                <button class="rc-btn-slides-link" @click="ouvrirSlides(1)" title="Voir dans la présentation">
                  <i class="ti ti-presentation"></i>
                </button>
              </div>
            </div>
            <div class="rc-odj-list">
              <div v-if="!rcForm.ordre_jour.length" class="rc-ec">Aucun point défini.</div>
              <div v-for="(oj, oi) in rcForm.ordre_jour" :key="oi" class="rc-odj-item">
                <div class="rc-odj-item__head" @click="oj._open = !oj._open">
                  <span class="rc-odj-num">{{ oi + 1 }}</span>
                  <div class="rc-odj-libelle-wrap" @click.stop>
                    <textarea class="rc-ta-sm rc-odj-ta" v-model="oj.libelle" rows="2" :disabled="isLocked"></textarea>
                  </div>
                  <span v-if="oj.form_link" class="rc-odj-link-badge" :class="'flb--' + oj.form_link">
                    <i :class="formLinkIcon(oj.form_link)"></i>
                    {{ formLinkLabel(oj.form_link) }}
                  </span>
                  <div class="rc-odj-item__actions" @click.stop>
                    <button v-if="oj.form_link" class="rc-ib rc-ib--expand" @click="oj._open = !oj._open">
                      <i :class="oj._open ? 'ti ti-chevron-up' : 'ti ti-chevron-down'"></i>
                    </button>
                    <button v-if="!isLocked" class="rc-del" @click="rcForm.ordre_jour.splice(oi, 1)">
                      <i class="ti ti-trash"></i>
                    </button>
                  </div>
                </div>
                <transition name="odj-expand">
                  <div v-if="oj._open && oj.form_link" class="rc-odj-form">

                    <!-- objectifs_mission -->
                    <div v-if="oj.form_link === 'objectifs_mission'" class="rc-odj-form__inner">
                      <div class="rc-odj-form__badge"><i class="ti ti-target"></i> Objectifs de contrôle</div>
                      <div v-if="!props.programmeData?.found" class="rc-ec">Aucun programme de travail trouvé.</div>
                      <div v-else>
                        <div class="rc-prog-badge">{{ props.programmeData.programme_code }} — {{ props.programmeData.total_objectifs }} objectifs, {{ props.programmeData.total_tests }} tests</div>
                        <table class="rc-tbl rc-tbl--compact">
                          <thead><tr><th>Objectif de contrôle</th><th>N° test</th><th>Libellé test</th><th>Auditeur</th></tr></thead>
                          <tbody>
                            <template v-for="(obj, oi2) in props.programmeData.objectifs" :key="oi2">
                              <tr v-for="(t, ti) in obj.tests" :key="ti">
                                <td v-if="ti === 0" :rowspan="obj.tests.length" class="rc-td-obj">{{ obj.objectif_controle ?? obj.libelle ?? '—' }}</td>
                                <td class="rc-tc rc-muted">{{ t.ref ?? (ti+1) }}</td>
                                <td>{{ t.libelle ?? '—' }}</td>
                                <td>{{ t.auditeur ?? '—' }}</td>
                              </tr>
                            </template>
                          </tbody>
                        </table>
                      </div>
                    </div>

                    <!-- points_forts -->
                    <div v-else-if="oj.form_link === 'points_forts'" class="rc-odj-form__inner">
                      <div class="rc-odj-form__badge"><i class="ti ti-star"></i> Points forts identifiés</div>
                      <div v-if="!isLocked" class="rc-odj-form__add">
                        <button class="rc-add-btn" @click="rcForm.points_forts.push({ libelle: '' })"><i class="ti ti-plus"></i> Ajouter</button>
                        <button class="rc-sync-btn" @click="syncPointsForts"><i class="ti ti-refresh"></i> Sync FRAPs</button>
                      </div>
                      <div v-if="!rcForm.points_forts.length" class="rc-ec">Aucun point fort.</div>
                      <div class="rc-pf-list">
                        <div v-for="(pf, pi) in rcForm.points_forts" :key="pi" class="rc-pf-row">
                          <span class="rc-odj-num">{{ pi + 1 }}</span>
                          <input class="rc-inp-sm" v-model="pf.libelle" :disabled="isLocked" placeholder="Point fort…" />
                          <button v-if="!isLocked" class="rc-del" @click="rcForm.points_forts.splice(pi,1)"><i class="ti ti-trash"></i></button>
                        </div>
                      </div>
                    </div>

                    <!-- far_validations -->
                    <div v-else-if="oj.form_link === 'far_validations'" class="rc-odj-form__inner rc-odj-form__inner--wide">
                      <div class="rc-odj-form__badge">
                        <i class="ti ti-clipboard-check"></i> Validation des observations d'audit (FAR)
                        <span class="rc-far-count">{{ rcForm.far_validations.length }} FAR(s)</span>
                      </div>
                      <div v-if="!isLocked" class="rc-odj-form__add">
                        <button class="rc-sync-btn" :disabled="refreshing" @click="refreshFraps">
                          <span v-if="refreshing" class="rc-spin rc-spin--xs"></span>
                          <i v-else class="ti ti-refresh"></i> Sync FRAPs
                        </button>
                        <button class="rc-add-btn" @click="ajouterFarManuelle"><i class="ti ti-plus"></i> FAR manuelle</button>
                      </div>
                      <div v-if="!rcForm.far_validations.length" class="rc-ec">Aucune FAR. Cliquez sur « Sync FRAPs ».</div>
                      <div v-else class="rc-far-table-wrap">
                        <table class="rc-far-tbl">
                          <thead><tr><th>N° FAR</th><th>Faits / Constats</th><th>Problèmes</th><th>Causes</th><th>Impacts</th><th>Recommandations</th><th>Acceptation</th><th>Appréciation</th><th>Échéance</th><th>Responsable</th><th>Livrable</th></tr></thead>
                          <tbody>
                            <tr v-for="(far, fi) in rcForm.far_validations" :key="far.id ?? fi" :class="farRowClass(far.acceptation)">
                              <td class="rc-tc">
                                <button class="rc-far-num-btn" @click="ouvrirFarDetail(far)">
                                  <span class="rc-far-num-badge">{{ far.num_far }}</span>
                                  <i class="ti ti-external-link" style="font-size:.5rem;opacity:.4"></i>
                                </button>
                              </td>
                              <td><div class="rc-cell-txt">{{ far.faits || '—' }}</div></td>
                              <td><div class="rc-cell-txt">{{ far.problemes || '—' }}</div></td>
                              <td><div class="rc-cell-txt">{{ far.causes || '—' }}</div></td>
                              <td><div class="rc-cell-txt">{{ far.impacts || '—' }}</div></td>
                              <td><div class="rc-cell-txt rc-cell-reco">{{ far.recommandations || '—' }}</div></td>
                              <td class="rc-far-accept-cell">
                                <select class="rc-sel-sm" v-model="far.acceptation" :style="acceptStyle(far.acceptation)"
                                  @change="saveFarField(far,'acceptation',far.acceptation)">
                                  <option value="en_discussion">🔵 En discussion</option>
                                  <option value="accepte">✅ Accepté</option>
                                  <option value="non_accepte">❌ Non accepté</option>
                                </select>
                                <div class="rc-qual-checks">
                                  <label class="rc-chk-lbl">
                                    <input type="checkbox" :checked="far.pertinence === 'pertinente'"
                                      @change="saveFarField(far,'pertinence',($event.target as HTMLInputElement).checked?'pertinente':'non_pertinente')" />
                                    Pertinente
                                  </label>
                                  <label class="rc-chk-lbl">
                                    <input type="checkbox" :checked="far.faisabilite === 'faisable'"
                                      @change="saveFarField(far,'faisabilite',($event.target as HTMLInputElement).checked?'faisable':'non_faisable')" />
                                    Faisable
                                  </label>
                                </div>
                              </td>
                              <td><textarea class="rc-ta-cell" v-model="far.appreciation_audite" rows="2" :disabled="isLocked"
                                @blur="saveFarField(far,'appreciation_audite',far.appreciation_audite)"></textarea></td>
                              <td><input type="date" class="rc-inp-sm" v-model="far.date_echeance" :disabled="isLocked"
                                @blur="saveFarField(far,'date_echeance',far.date_echeance)" /></td>
                              <td><input class="rc-inp-sm" v-model="far.personne_responsable" :disabled="isLocked"
                                @blur="saveFarField(far,'personne_responsable',far.personne_responsable)" /></td>
                              <td><input class="rc-inp-sm" v-model="far.livrable" :disabled="isLocked"
                                @blur="saveFarField(far,'livrable',far.livrable)" /></td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>

                    <!-- suivi_modalites -->
                    <div v-else-if="oj.form_link === 'suivi_modalites'" class="rc-odj-form__inner">
                      <div class="rc-odj-form__badge"><i class="ti ti-calendar-check"></i> Modalités de suivi</div>
                      <div v-if="!isLocked" class="rc-odj-form__add">
                        <button class="rc-add-btn" @click="rcForm.suivi_modalites.push({ date_rapport:'', delais_mise_oeuvre:'', modalites_suivi:'' })">
                          <i class="ti ti-plus"></i> Ajouter
                        </button>
                      </div>
                      <div v-if="!rcForm.suivi_modalites.length" class="rc-ec">Aucune modalité.</div>
                      <table v-else class="rc-tbl">
                        <thead><tr><th style="width:40px">N°</th><th style="width:140px">Date du rapport</th><th style="width:180px">Délais</th><th>Modalités de suivi</th><th v-if="!isLocked"></th></tr></thead>
                        <tbody>
                          <tr v-for="(sm, si) in rcForm.suivi_modalites" :key="si">
                            <td class="rc-tc rc-muted">{{ si + 1 }}</td>
                            <td><input type="date" class="rc-inp-sm" v-model="sm.date_rapport" :disabled="isLocked" /></td>
                            <td><input class="rc-inp-sm" v-model="sm.delais_mise_oeuvre" :disabled="isLocked" /></td>
                            <td><textarea class="rc-ta-sm" v-model="sm.modalites_suivi" rows="2" :disabled="isLocked"></textarea></td>
                            <td v-if="!isLocked"><button class="rc-del" @click="rcForm.suivi_modalites.splice(si,1)"><i class="ti ti-trash"></i></button></td>
                          </tr>
                        </tbody>
                      </table>
                    </div>

                    <!-- fiche_test -->
                    <div v-else-if="oj.form_link === 'fiche_test'" class="rc-odj-form__inner">
                      <div class="rc-odj-form__badge"><i class="ti ti-file-analytics"></i> Synthèse des fiches de test</div>
                      <div v-for="ft in props.fichesTest" :key="ft.id" class="rc-ft-mini-card">
                        <span class="rc-ft-code">{{ ft.code }}</span>
                        <span class="rc-sdot" :class="'sd--' + ft.validation_status" style="width:7px;height:7px;display:inline-block;border-radius:50%;"></span>
                        <span class="rc-ft-statut">{{ vstLbl(ft.validation_status) }}</span>
                        <span class="rc-ft-auditeur">{{ ft.auditeur_nom }}</span>
                        <span v-if="iaForFt(ft.id)" class="rc-ia-score rc-ia-score--sm"
                          :style="{ background: scoreColor(iaForFt(ft.id)?.score_global ?? 0) }">
                          {{ iaForFt(ft.id)?.score_global }}/10
                        </span>
                      </div>
                      <div v-if="!props.fichesTest?.length" class="rc-ec">Aucune fiche de test.</div>
                    </div>

                    <!-- custom -->
                    <div v-else class="rc-odj-form__inner">
                      <div class="rc-odj-form__badge"><i class="ti ti-notes"></i> Notes complémentaires</div>
                      <textarea class="rc-ta" rows="4" placeholder="Notes pour ce point…" :disabled="isLocked" v-model="oj.notes"></textarea>
                    </div>

                  </div>
                </transition>
              </div>
            </div>
          </div>

          <!-- Section 2 : Participants -->
          <div class="rc-section">
            <div class="rc-section__title">
              <span>2. Liste des participants <span class="rc-badge-count">{{ rcForm.participants.length }}</span></span>
              <div class="rc-section__actions" v-if="!isLocked">
                <button class="rc-add-btn" @click="rcForm.participants.push({ nom:'', fonction:'', entite:'', present:true })">
                  <i class="ti ti-plus"></i> Ajouter
                </button>
                <button v-if="props.equipe?.length" class="rc-sync-btn" @click="syncParticipants">
                  <i class="ti ti-refresh"></i> Sync équipe
                </button>
                <button class="rc-btn-slides-link" @click="ouvrirSlides(slideGroups.findIndex(g => g.type === 'participants'))" title="Voir dans la présentation">
                  <i class="ti ti-presentation"></i>
                </button>
              </div>
            </div>
            <div class="rc-participants-grid">
              <div v-if="!rcForm.participants.length" class="rc-ec">Aucun participant.</div>
              <div v-for="(p, pi) in rcForm.participants" :key="pi" class="rc-participant-card">
                <div class="rc-participant-card__top">
                  <label class="rc-pcard-check">
                    <input type="checkbox" v-model="p.present" :disabled="isLocked" />
                    <span class="rc-pcard-present" :class="p.present ? 'rpp--ok' : 'rpp--absent'">
                      {{ p.present ? 'Présent' : 'Absent' }}
                    </span>
                  </label>
                  <button v-if="!isLocked" class="rc-del" style="margin-left:auto" @click="rcForm.participants.splice(pi,1)">
                    <i class="ti ti-trash"></i>
                  </button>
                </div>
                <div class="rc-pcard-fields">
                  <input class="rc-inp-sm" v-model="p.nom" :disabled="isLocked" placeholder="Nom et Prénom" />
                  <input class="rc-inp-sm" v-model="p.fonction" :disabled="isLocked" placeholder="Fonction / Titre" />
                  <input class="rc-inp-sm" v-model="p.entite" :disabled="isLocked" placeholder="Entité / Structure" />
                </div>
              </div>
            </div>
          </div>

          <!-- Section 3 : Conclusion -->
          <div class="rc-section">
            <div class="rc-section__title">
              3. Conclusion générale
              <button v-if="!isLocked" class="rc-btn-slides-link" @click="ouvrirSlides(slides.length - 1)" title="Voir dans la présentation">
                <i class="ti ti-presentation"></i>
              </button>
            </div>
            <div class="rc-section__body">
              <div class="rc-field">
                <textarea class="rc-ta" v-model="rcForm.conclusion_generale" rows="4"
                  :disabled="isLocked" placeholder="Conclusion générale de la réunion de clôture…"></textarea>
              </div>
              <div class="rc-field" style="margin-top:.5rem">
                <label class="rc-lbl-sm">Observations finales</label>
                <textarea class="rc-ta" v-model="rcForm.observations_finales" rows="3" :disabled="isLocked"></textarea>
              </div>
            </div>
          </div>

          <!-- Section 4 : Signatures -->
          <div class="rc-section">
            <div class="rc-section__title">4. Signatures</div>
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
                  <canvas v-if="!isLocked" :ref="el => sigCanvases[role] = el as HTMLCanvasElement"
                    class="rc-sig-canvas" width="260" height="90"
                    @mousedown="startDraw(role,$event)" @mousemove="draw(role,$event)"
                    @mouseup="endDraw(role)" @mouseleave="endDraw(role)"
                    @touchstart.prevent="startDraw(role,$event)" @touchmove.prevent="draw(role,$event)"
                    @touchend="endDraw(role)"></canvas>
                  <img v-else-if="sigForms[role].signature_b64" :src="sigForms[role].signature_b64" class="rc-sig-img" alt="Signature" />
                  <div v-else class="rc-sig-placeholder">—</div>
                  <button v-if="!isLocked && sigForms[role].signature_b64" class="rc-sig-clear" @click="clearSig(role)">
                    <i class="ti ti-eraser"></i> Effacer
                  </button>
                </div>
              </div>
            </div>
            <div class="rc-confidential">Document confidentiel – Usage interne exclusivement</div>
          </div>

        </div><!-- /pv tab -->
      </div><!-- /rc-main -->

      <!-- ══ MODAL FAR ÉDITION COMPLÈTE ══════════════════════ -->
      <Teleport to="body">
        <Transition name="om-fade">
          <div v-if="farModal.visible" class="rc-modal-overlay" @click.self="farModal.visible = false">
            <div class="rc-far-modal">
              <div class="rc-far-modal__hd">
                <div class="rc-far-modal__icon"><i class="ti ti-clipboard-check"></i></div>
                <div style="flex:1">
                  <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">
                    <h2 style="margin:0;font-size:.95rem;color:#0f172a">{{ farModal.far?.num_far }}</h2>
                    <span class="rc-far-modal-acc" :style="acceptStyle(farModal.far?.acceptation ?? 'en_discussion')">
                      {{ acceptLabel(farModal.far?.acceptation ?? 'en_discussion') }}
                    </span>
                  </div>
                  <div style="font-size:.65rem;color:#94a3b8;margin-top:3px">
                    <i class="ti ti-info-circle"></i> Modifications sauvegardées automatiquement
                  </div>
                </div>
                <button class="rc-ib" @click="farModal.visible = false"><i class="ti ti-x"></i></button>
              </div>
              <div class="rc-far-modal__body" v-if="farModal.far">
                <div class="rc-fm-grid">
                  <div class="rc-fm-field rc-fm-field--full">
                    <label class="rc-fm-lbl">Faits / Constats</label>
                    <textarea v-if="!isLocked" class="rc-fm-ta" v-model="farModal.far.faits" rows="3"
                      @blur="saveFarField(farModal.far,'faits',farModal.far.faits)"></textarea>
                    <div v-else class="rc-fm-val">{{ farModal.far.faits || '—' }}</div>
                  </div>
                  <div class="rc-fm-field">
                    <label class="rc-fm-lbl">Problèmes</label>
                    <textarea v-if="!isLocked" class="rc-fm-ta" v-model="farModal.far.problemes" rows="3"
                      @blur="saveFarField(farModal.far,'problemes',farModal.far.problemes)"></textarea>
                    <div v-else class="rc-fm-val">{{ farModal.far.problemes || '—' }}</div>
                  </div>
                  <div class="rc-fm-field">
                    <label class="rc-fm-lbl">Causes</label>
                    <textarea v-if="!isLocked" class="rc-fm-ta" v-model="farModal.far.causes" rows="3"
                      @blur="saveFarField(farModal.far,'causes',farModal.far.causes)"></textarea>
                    <div v-else class="rc-fm-val">{{ farModal.far.causes || '—' }}</div>
                  </div>
                  <div class="rc-fm-field">
                    <label class="rc-fm-lbl">Impacts</label>
                    <textarea v-if="!isLocked" class="rc-fm-ta" v-model="farModal.far.impacts" rows="3"
                      @blur="saveFarField(farModal.far,'impacts',farModal.far.impacts)"></textarea>
                    <div v-else class="rc-fm-val">{{ farModal.far.impacts || '—' }}</div>
                  </div>
                  <div class="rc-fm-field rc-fm-field--full">
                    <label class="rc-fm-lbl">Recommandations</label>
                    <textarea v-if="!isLocked" class="rc-fm-ta" v-model="farModal.far.recommandations" rows="3"
                      @blur="saveFarField(farModal.far,'recommandations',farModal.far.recommandations)"></textarea>
                    <div v-else class="rc-fm-val rc-fm-val--reco">{{ farModal.far.recommandations || '—' }}</div>
                  </div>
                  <div class="rc-fm-field rc-fm-field--full">
                    <label class="rc-fm-lbl">Appréciation de l'audité</label>
                    <textarea v-if="!isLocked" class="rc-fm-ta" v-model="farModal.far.appreciation_audite" rows="3"
                      @blur="saveFarField(farModal.far,'appreciation_audite',farModal.far.appreciation_audite)"></textarea>
                    <div v-else class="rc-fm-val">{{ farModal.far.appreciation_audite || '—' }}</div>
                  </div>
                  <div class="rc-fm-field">
                    <label class="rc-fm-lbl">Acceptation</label>
                    <select v-if="!isLocked" class="rc-fm-sel" v-model="farModal.far.acceptation"
                      :style="acceptStyle(farModal.far.acceptation)"
                      @change="saveFarField(farModal.far,'acceptation',farModal.far.acceptation)">
                      <option value="en_discussion">🔵 En discussion</option>
                      <option value="accepte">✅ Accepté</option>
                      <option value="non_accepte">❌ Non accepté</option>
                    </select>
                    <div v-else class="rc-fm-val">{{ acceptLabel(farModal.far.acceptation) }}</div>
                  </div>
                  <div class="rc-fm-field">
                    <label class="rc-fm-lbl">Qualité</label>
                    <div class="rc-qual-checks" style="flex-direction:row;gap:.75rem">
                      <label class="rc-chk-lbl">
                        <input type="checkbox" :checked="farModal.far.pertinence === 'pertinente'" :disabled="isLocked"
                          @change="saveFarField(farModal.far,'pertinence',($event.target as HTMLInputElement).checked?'pertinente':'non_pertinente')" />
                        Pertinente
                      </label>
                      <label class="rc-chk-lbl">
                        <input type="checkbox" :checked="farModal.far.faisabilite === 'faisable'" :disabled="isLocked"
                          @change="saveFarField(farModal.far,'faisabilite',($event.target as HTMLInputElement).checked?'faisable':'non_faisable')" />
                        Faisable
                      </label>
                    </div>
                  </div>
                  <div class="rc-fm-field">
                    <label class="rc-fm-lbl">Date d'échéance</label>
                    <input v-if="!isLocked" type="date" class="rc-fm-inp" v-model="farModal.far.date_echeance"
                      @blur="saveFarField(farModal.far,'date_echeance',farModal.far.date_echeance)" />
                    <div v-else class="rc-fm-val">{{ formatDate(farModal.far.date_echeance) || '—' }}</div>
                  </div>
                  <div class="rc-fm-field">
                    <label class="rc-fm-lbl">Responsable</label>
                    <input v-if="!isLocked" class="rc-fm-inp" v-model="farModal.far.personne_responsable"
                      @blur="saveFarField(farModal.far,'personne_responsable',farModal.far.personne_responsable)" />
                    <div v-else class="rc-fm-val">{{ farModal.far.personne_responsable || '—' }}</div>
                  </div>
                  <div class="rc-fm-field">
                    <label class="rc-fm-lbl">Livrable</label>
                    <input v-if="!isLocked" class="rc-fm-inp" v-model="farModal.far.livrable"
                      @blur="saveFarField(farModal.far,'livrable',farModal.far.livrable)" />
                    <div v-else class="rc-fm-val">{{ farModal.far.livrable || '—' }}</div>
                  </div>
                  <!-- Lien fiche d'observation si FRAP liée -->
                  <div v-if="farModal.far.frap_id" class="rc-fm-field rc-fm-field--full">
                    <label class="rc-fm-lbl">Fiche d'observation liée</label>
                    <button class="rc-far-obs-link" @click="ouvrirFrapParId(farModal.far.frap_id); farModal.visible = false">
                      <i class="ti ti-eye"></i> Voir la fiche d'observation (FRAP liée)
                    </button>
                  </div>
                </div>
              </div>
              <div class="rc-far-modal__ft">
                <button class="rc-btn rc-btn--ghost" @click="farModal.visible = false">Fermer</button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>

      <!-- ══ MODAL FRAP DÉTAIL ══════════════════════════════════ -->
      <Teleport to="body">
        <Transition name="om-fade">
          <div v-if="frapModal.visible" class="rc-modal-overlay" @click.self="frapModal.visible = false">
            <div class="rc-frap-modal">
              <div class="rc-frap-modal__hd">
                <div class="rc-frap-modal__icon"><i class="ti ti-clipboard-text"></i></div>
                <div>
                  <h2 style="margin:0;font-size:.95rem;color:#0f172a">{{ frapModal.frap?.num_frap }}</h2>
                  <div style="font-size:.7rem;color:#64748b;margin-top:2px">{{ frapModal.frap?.rubrique }}</div>
                </div>
                <span v-if="frapModal.frap?.niveau_controle_interne"
                  class="rc-foci-niv-badge"
                  :style="fociNiveauStyle(frapModal.frap.niveau_controle_interne)"
                  style="margin-left:.5rem">
                  {{ fociNiveauLabel(frapModal.frap.niveau_controle_interne) }}
                </span>
                <button class="rc-ib" style="margin-left:auto" @click="frapModal.visible = false"><i class="ti ti-x"></i></button>
              </div>
              <div class="rc-frap-modal__body" v-if="frapModal.frap">
                <div class="rc-fm-grid">
                  <div class="rc-fm-field rc-fm-field--full">
                    <label class="rc-fm-lbl">Objectif de contrôle</label>
                    <div class="rc-fm-val rc-fm-val--obj">{{ frapModal.frap.objectif_controle || '—' }}</div>
                  </div>
                  <div class="rc-fm-field rc-fm-field--full">
                    <label class="rc-fm-lbl">Fait / Constat</label>
                    <div class="rc-fm-val">{{ frapModal.frap.fait_constats || '—' }}</div>
                  </div>
                  <div class="rc-fm-field"><label class="rc-fm-lbl">Problème</label><div class="rc-fm-val">{{ frapModal.frap.probleme || '—' }}</div></div>
                  <div class="rc-fm-field"><label class="rc-fm-lbl">Causes</label><div class="rc-fm-val">{{ frapModal.frap.causes || '—' }}</div></div>
                  <div class="rc-fm-field"><label class="rc-fm-lbl">Impacts</label><div class="rc-fm-val">{{ frapModal.frap.impacts || '—' }}</div></div>
                  <div class="rc-fm-field"><label class="rc-fm-lbl">Recommandation</label><div class="rc-fm-val rc-fm-val--reco">{{ frapModal.frap.recommandation || '—' }}</div></div>
                  <div class="rc-fm-field" v-if="frapModal.frap.points_forts">
                    <label class="rc-fm-lbl rc-fm-lbl--green">Points forts</label>
                    <div class="rc-fm-val rc-fm-val--green">{{ frapModal.frap.points_forts }}</div>
                  </div>
                  <div class="rc-fm-field">
                    <label class="rc-fm-lbl">Date d'échéance</label>
                    <div class="rc-fm-val">{{ formatDate(frapModal.frap.date_echeance) || '—' }}</div>
                  </div>
                  <div class="rc-fm-field">
                    <label class="rc-fm-lbl">Responsable</label>
                    <div class="rc-fm-val">{{ frapModal.frap.personne_responsable || '—' }}</div>
                  </div>
                  <div class="rc-fm-field" v-if="frapModal.frap.commentaires_audite">
                    <label class="rc-fm-lbl">Commentaires de l'audité</label>
                    <div class="rc-fm-val">{{ frapModal.frap.commentaires_audite }}</div>
                  </div>
                </div>
              </div>
              <div class="rc-frap-modal__ft">
                <button class="rc-btn rc-btn--ghost" @click="frapModal.visible = false">Fermer</button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>

      <!-- TOAST -->
      <Teleport to="body">
        <Transition name="toast-pop">
          <div v-if="toast.show" class="rc-toast" :class="'rc-toast--' + toast.type">
            <i :class="toast.type === 'success' ? 'ti ti-circle-check' : 'ti ti-alert-circle'"></i>
            {{ toast.msg }}
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

// ─── PROPS ──────────────────────────────────────────────────────
const props = withDefaults(defineProps<{
  reunion?: any
  mission?: any
  fraps?: any[]
  fociGrouped?: any[]
  canManage?: boolean
  isNew?: boolean
  backUrl?: string
  urlStore?: string
  urlUpdate?: string
  urlSoumettre?: string
  urlValider?: string
  urlFarUpdate?: string
  urlFarStore?: string
  urlRefreshFraps?: string
  urlSignature?: string
  equipe?: any[]
  fichesTest?: any[]
  iaGlobales?: any[]
  programmeData?: any
  statsNiveaux?: Record<string, number>
  totalFraps?: number
  scoreIaMoyen?: number | null
  currentAuditeur?: { id: number; full_name: string; role_code: string }
}>(), {
  equipe: () => [], fichesTest: () => [], iaGlobales: () => [],
  statsNiveaux: () => ({}), totalFraps: 0, scoreIaMoyen: null,
  fraps: () => [], fociGrouped: () => [], canManage: false,
})

// ─── TABS ───────────────────────────────────────────────────────
const tabs = [
  { id: 'fiche_test', label: 'Fiche de Test', icon: 'ti-file-analytics' },
  { id: 'pv',         label: 'PV de Clôture', icon: 'ti-file-text' },
]
const activeTab = ref('pv')

// ─── FORM STATE ──────────────────────────────────────────────────
const rcForm = reactive<any>({
  entite:'', intitule_mission:'', code_mission:'', norme_mpa:'MPA 2400 – 2410-1',
  date_reunion:'', heure_debut:'', heure_fin:'', lieu:'',
  preside_par:'', secretaire_seance:'',
  conclusion_generale:'', observations_finales:'',
  statut:'draft', validation_note:'',
  ordre_jour:[], points_forts:[], far_validations:[], suivi_modalites:[], participants:[],
})
const sigForms = reactive<Record<string,any>>({
  chef_mission:        { nom:'', prenom:'', fonction:'', date_signature:'', signature_b64:'' },
  representant_audite: { nom:'', prenom:'', fonction:'', date_signature:'', signature_b64:'' },
  superviseur:         { nom:'', prenom:'', fonction:'', date_signature:'', signature_b64:'' },
})
const sigCanvases = reactive<Record<string, HTMLCanvasElement | null>>({ chef_mission:null, representant_audite:null, superviseur:null })
const sigDrawing  = reactive<Record<string,boolean>>({ chef_mission:false, representant_audite:false, superviseur:false })
const sigLastPos  = reactive<Record<string,{x:number;y:number}>>({ chef_mission:{x:0,y:0}, representant_audite:{x:0,y:0}, superviseur:{x:0,y:0} })

const saving     = ref(false)
const refreshing = ref(false)
const lastSaved  = ref('')
const newOdjLink = ref('')
const toast      = ref({ show:false, type:'success', msg:'' })
const frapModal  = reactive<{visible:boolean;frap:any|null}>({ visible:false, frap:null })
const farModal   = reactive<{visible:boolean;far:any|null}>({ visible:false, far:null })
let _tt: ReturnType<typeof setTimeout> | null = null

// ─── SLIDES DYNAMIQUES ────────────────────────────────────────────
const slidesMode  = ref(false)
const curSlideIdx = ref(0)
const overlayRef  = ref<HTMLElement | null>(null)

// Niveaux CI
const nivMap: Record<string,any> = {
  critique:    { label:'⛔ Critique',     bg:'#fce7f3', color:'#9d174d' },
  insuffisant: { label:'🔴 Insuffisant',  bg:'#fee2e2', color:'#dc2626' },
  a_ameliorer: { label:'🔶 À améliorer',  bg:'#fef3c7', color:'#d97706' },
  satisfaisant:{ label:'✅ Satisfaisant', bg:'#d1fae5', color:'#065f46' },
  conforme:    { label:'✅ Conforme',     bg:'#d1fae5', color:'#065f46' },
}
function nivLbl(v?: string)        { return nivMap[v?.toLowerCase() ?? '']?.label ?? (v || '—') }
function nivBadgeStyle(v?: string) {
  const c = nivMap[v?.toLowerCase() ?? '']
  return c ? { background:c.bg, color:c.color } : { background:'#f1f5f9', color:'#475569' }
}

// Options acceptation FAR
const acceptOptions = [
  { val:'en_discussion', icon:'🔵', label:'En discussion', activeStyle:{ background:'#dbeafe', color:'#1d4ed8', borderColor:'#93c5fd' }},
  { val:'accepte',       icon:'✅', label:'Accepté',       activeStyle:{ background:'#d1fae5', color:'#065f46', borderColor:'#6ee7b7' }},
  { val:'non_accepte',   icon:'❌', label:'Non accepté',   activeStyle:{ background:'#fee2e2', color:'#dc2626', borderColor:'#fca5a5' }},
]

// Construction dynamique des slides
interface Slide { type:string; label:string; icon:string; farIdx?:number }
const slides = computed<Slide[]>(() => {
  const s: Slide[] = []
  s.push({ type:'cover',        label:'Couverture',    icon:'ti-presentation' })
  s.push({ type:'objectifs',    label:'Objectifs',     icon:'ti-target' })
  s.push({ type:'points_forts', label:'Points forts',  icon:'ti-star' })
  s.push({ type:'foci',         label:'Synthèse FOCI', icon:'ti-file-text' })
  rcForm.far_validations.forEach((_: any, i: number) => {
    s.push({ type:'far', label:`FAR ${i + 1}`, icon:'ti-clipboard-check', farIdx:i })
  })
  s.push({ type:'suivi',        label:'Suivi',         icon:'ti-calendar-check' })
  s.push({ type:'participants', label:'Participants',   icon:'ti-users' })
  s.push({ type:'conclusion',   label:'Conclusion',    icon:'ti-flag' })
  return s
})

// Groupes pour le menu sidebar vertical
const slideGroups = computed(() => {
  const farStart = 4
  return [
    { label:'Couverture',     icon:'ti-presentation',   firstSlide:0,                               count:0,   type:'cover' },
    { label:'Objectifs',      icon:'ti-target',          firstSlide:1,                               count:0,   type:'objectifs' },
    { label:'Points forts',   icon:'ti-star',            firstSlide:2,                               count:rcForm.points_forts.length,      type:'points_forts' },
    { label:'Synthèse FOCI',  icon:'ti-file-text',       firstSlide:3,                               count:props.fraps?.length || 0,        type:'foci' },
    { label:'Validation FAR', icon:'ti-clipboard-check', firstSlide:farStart,                        count:rcForm.far_validations.length,   type:'far' },
    { label:'Suivi',          icon:'ti-calendar-check',  firstSlide:farStart + rcForm.far_validations.length,     count:rcForm.suivi_modalites.length, type:'suivi' },
    { label:'Participants',   icon:'ti-users',           firstSlide:farStart + rcForm.far_validations.length + 1, count:rcForm.participants.length,    type:'participants' },
    { label:'Conclusion',     icon:'ti-flag',            firstSlide:farStart + rcForm.far_validations.length + 2, count:0, type:'conclusion' },
  ]
})

const curSlide    = computed(() => slides.value[curSlideIdx.value] ?? slides.value[0])
const curFar      = computed(() => curSlide.value.type === 'far' ? rcForm.far_validations[curSlide.value.farIdx ?? 0] : null)
const farLocalIdx = computed(() => curSlide.value.farIdx ?? 0)
const activeGroup = computed(() => {
  const idx = curSlideIdx.value
  const g = slideGroups.value
  for (let i = g.length - 1; i >= 0; i--) {
    if (idx >= g[i].firstSlide) return i
  }
  return 0
})

function ouvrirSlides(idx = 0) {
  curSlideIdx.value = Math.min(idx, slides.value.length - 1)
  slidesMode.value  = true
  nextTick(() => overlayRef.value?.focus())
}
function goSlide(idx: number) {
  if (idx < 0 || idx >= slides.value.length) return
  curSlideIdx.value = idx
}
function goGroup(gi: number) {
  goSlide(slideGroups.value[gi]?.firstSlide ?? 0)
}
function onSlideKey(e: KeyboardEvent) {
  if (e.key === 'ArrowRight' || e.key === 'ArrowDown') goSlide(curSlideIdx.value + 1)
  else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') goSlide(curSlideIdx.value - 1)
  else if (e.key === 'Escape') slidesMode.value = false
}

// ─── COMPUTED ───────────────────────────────────────────────────
const isLocked = computed(() =>
  rcForm.statut === 'validated' || (rcForm.statut === 'in_review' && !props.canManage)
)
const missionLibelle = computed(() => props.mission?.libelle ?? rcForm.intitule_mission ?? '—')
const statsGlobalesDisplay = computed(() => {
  const s = props.statsNiveaux ?? {}
  return [
    { key:'critique',    label:'⛔ Critique',     count:s['critique']     ?? 0, color:'#9d174d', bg:'#fce7f3' },
    { key:'insuffisant', label:'🔴 Insuffisant',  count:s['insuffisant']  ?? 0, color:'#dc2626', bg:'#fee2e2' },
    { key:'a_ameliorer', label:'🔶 À améliorer',  count:s['a_ameliorer']  ?? 0, color:'#d97706', bg:'#fef3c7' },
    { key:'satisfaisant',label:'✅ Satisfaisant', count:s['satisfaisant'] ?? 0, color:'#065f46', bg:'#d1fae5' },
    { key:'conforme',    label:'✅ Conforme',     count:s['conforme']     ?? 0, color:'#065f46', bg:'#d1fae5' },
  ].filter(x => x.count > 0)
})

// ─── HELPERS ────────────────────────────────────────────────────
const vstLbl     = (s: string) => ({ draft:'Brouillon', in_review:'En attente', validated:'Validé ✓' }[s] ?? s)
const roleLabel  = (r: string) => ({ chef_mission:'Chef de Mission', representant_audite:'Représentant des Audités', superviseur:'Superviseur / Resp. Audit' }[r] ?? r)
const formatDate = (d?: string | null) => d ? new Date(d).toLocaleDateString('fr-FR') : ''
const scoreColor = (n: number) => n >= 7 ? '#10b981' : n >= 5 ? '#f59e0b' : '#dc2626'
const csrf       = () => (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? ''
function showToast(t: string, m: string, dur = 4000) {
  if (_tt) clearTimeout(_tt)
  toast.value = { show:true, type:t, msg:m }
  _tt = setTimeout(() => (toast.value.show = false), dur)
}
function setLastSaved() {
  lastSaved.value = 'Sauvegardé ' + new Date().toLocaleTimeString('fr-FR', { hour:'2-digit', minute:'2-digit' })
  setTimeout(() => { lastSaved.value = '' }, 6000)
}
function iaForFt(ftId: number) { return props.iaGlobales?.find(ia => ia.fiche_test_id === ftId) ?? null }
function formLinkLabel(link: string) {
  return ({ objectifs_mission:'Objectifs de la mission', points_forts:'Points forts', far_validations:'Validation FAR', suivi_modalites:'Modalités de suivi', fiche_test:'Fiche de test', custom:'Point libre' } as any)[link] ?? link
}
function formLinkIcon(link: string) {
  return ({ objectifs_mission:'ti ti-target', points_forts:'ti ti-star', far_validations:'ti ti-clipboard-check', suivi_modalites:'ti ti-calendar-check', fiche_test:'ti ti-file-analytics', custom:'ti ti-notes' } as any)[link] ?? 'ti ti-link'
}

// FOCI helpers
const fociNiveauMap: Record<string,any> = {
  'satisfaisant':{ label:'✅ Satisfaisant', bg:'#d1fae5', color:'#065f46' },
  'a_ameliorer': { label:'🔶 À améliorer',  bg:'#fef3c7', color:'#92400e' },
  'insuffisant': { label:'🔴 Insuffisant',  bg:'#fee2e2', color:'#dc2626' },
  'critique':    { label:'⛔ Critique',     bg:'#fce7f3', color:'#9d174d' },
  'conforme':    { label:'✅ Conforme',     bg:'#d1fae5', color:'#065f46' },
}
const fociNiveauLabel    = (v?: string) => fociNiveauMap[v?.toLowerCase() ?? '']?.label ?? (v || '—')
const fociNiveauStyle    = (v?: string) => { const c = fociNiveauMap[v?.toLowerCase() ?? '']; return c ? { background:c.bg, color:c.color } : { background:'#f1f5f9', color:'#475569' } }
const fociNiveauRowClass = (v?: string) => {
  const l = v?.toLowerCase()
  if (l === 'critique')    return 'rc-foci-row--critique'
  if (l === 'insuffisant') return 'rc-foci-row--insuffisant'
  if (l === 'a_ameliorer') return 'rc-foci-row--ameliorer'
  if (l === 'satisfaisant' || l === 'conforme') return 'rc-foci-row--ok'
  return ''
}
const farRowClass   = (acc: string) => acc === 'accepte' ? 'rc-far-row--ok' : acc === 'non_accepte' ? 'rc-far-row--ko' : ''
const acceptStyle   = (v: string) => {
  if (v === 'accepte')     return { background:'#d1fae5', color:'#065f46',  borderColor:'#a7f3d0' }
  if (v === 'non_accepte') return { background:'#fee2e2', color:'#dc2626',  borderColor:'#fca5a5' }
  return                          { background:'#dbeafe', color:'#1d4ed8',  borderColor:'#bfdbfe' }
}
const acceptLabel   = (v: string) => ({ accepte:'✅ Accepté', non_accepte:'❌ Non accepté', en_discussion:'🔵 En discussion' }[v] ?? v)

// ─── MODALS ──────────────────────────────────────────────────────
function ouvrirFarDetail(far: any)   { farModal.far  = far;  farModal.visible  = true }
function ouvrirFrapModal(frap: any)  { frapModal.frap = frap; frapModal.visible = true }
function ouvrirFrapParId(frapId: number) {
  const frap = props.fraps?.find(f => f.id === frapId)
  if (frap) ouvrirFrapModal(frap)
  else showToast('error', "Fiche d'observation introuvable")
}

// ─── ODJ ─────────────────────────────────────────────────────────
function ajouterOdj() {
  const link = newOdjLink.value
  const labels: Record<string, string> = {
    objectifs_mission: "Rappeler les objectifs de la mission et les objectifs d'audit",
    points_forts:      "Présenter les points forts de l'entité auditée",
    far_validations:   "Faire valider les observations d'audit (FAR)",
    suivi_modalites:   "Présenter les modalités de suivi de la mission",
    fiche_test:        "Présentation des résultats des fiches de test",
    custom:            "Point à l'ordre du jour",
  }
  rcForm.ordre_jour.push({ libelle: labels[link] ?? "Point à l'ordre du jour", form_link: link || null, _open: !!link, notes:'' })
  newOdjLink.value = ''
}

// ─── SYNC ────────────────────────────────────────────────────────
function syncPointsForts() {
  const pfs = new Set(rcForm.points_forts.map((p: any) => (p.libelle ?? '').toLowerCase()))
  ;(props.fraps ?? []).forEach((f: any) => {
    const pf = (f.points_forts ?? '').trim()
    if (pf && !pfs.has(pf.toLowerCase())) { rcForm.points_forts.push({ libelle:pf }); pfs.add(pf.toLowerCase()) }
  })
  showToast('success', 'Points forts synchronisés depuis les FRAPs.')
}
function syncParticipants() {
  if (!props.equipe?.length) return
  const existing = new Set(rcForm.participants.map((p: any) => (p.nom ?? '').toLowerCase()))
  props.equipe.forEach(a => {
    if (!existing.has((a.full_name ?? '').toLowerCase())) {
      rcForm.participants.push({ nom:a.full_name, fonction:a.role_label, entite:'Audit Interne', present:true })
      existing.add((a.full_name ?? '').toLowerCase())
    }
  })
  showToast('success', `${props.equipe.length} membre(s) synchronisé(s).`)
}

// ─── FAR ─────────────────────────────────────────────────────────
async function saveFarField(far: any, field: string, value: any) {
  if (!props.urlFarUpdate || !far?.id) return
  far[field] = value
  try {
    await fetch(props.urlFarUpdate.replace(':farId', String(far.id)), {
      method:'PUT',
      headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN':csrf(), Accept:'application/json' },
      body: JSON.stringify({ [field]:value }),
    })
    setLastSaved()
  } catch(e: any) { showToast('error', e.message) }
}
async function ajouterFarManuelle() {
  if (!props.urlFarStore) return
  const res = await fetch(props.urlFarStore, {
    method:'POST', headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN':csrf(), Accept:'application/json' },
    body: JSON.stringify({}),
  })
  const d = await res.json()
  if (d.success) { rcForm.far_validations.push(d.far); showToast('success', 'FAR ajoutée.') }
  else showToast('error', d.error || 'Erreur')
}
async function refreshFraps() {
  if (!props.urlRefreshFraps) return
  refreshing.value = true
  try {
    const res = await fetch(props.urlRefreshFraps, {
      method:'POST', headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN':csrf(), Accept:'application/json' },
      body: JSON.stringify({}),
    })
    const d = await res.json()
    if (d.success) { rcForm.far_validations = d.far_list; showToast('success', `${d.count} FAR(s) rechargée(s).`) }
    else showToast('error', d.error || 'Erreur')
  } catch(e: any) { showToast('error', e.message) }
  finally { refreshing.value = false }
}

// ─── WORKFLOW ────────────────────────────────────────────────────
function annuler() { if (props.backUrl) router.visit(props.backUrl) }

async function creer() {
  if (!props.urlStore) return
  saving.value = true
  try { router.post(props.urlStore, { mission_id: props.mission?.id ?? 0 }) }
  catch(e: any) { showToast('error', e.message); saving.value = false }
}

async function sauvegarder() {
  if (!props.urlUpdate) { showToast('error', 'PV non encore créé.'); return }
  saving.value = true
  try {
    const res = await fetch(props.urlUpdate, {
      method:'PUT', headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN':csrf(), Accept:'application/json' },
      body: JSON.stringify({ ...rcForm }),
    })
    const d = await res.json()
    if (d.success) { if (d.reunion?.statut) rcForm.statut = d.reunion.statut; setLastSaved(); showToast('success', 'PV enregistré.') }
    else showToast('error', d.error || 'Erreur')
  } catch(e: any) { showToast('error', e.message) }
  finally { saving.value = false }
}

async function soumettre() {
  if (!props.urlSoumettre) return
  const res = await fetch(props.urlSoumettre, {
    method:'POST', headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN':csrf(), Accept:'application/json' },
    body: JSON.stringify({}),
  })
  const d = await res.json()
  if (d.success) { rcForm.statut = 'in_review'; showToast('success', 'Soumis pour validation.') }
  else showToast('error', d.error || 'Erreur')
}

async function valider(action: string, note?: string) {
  if (!props.urlValider) return
  const res = await fetch(props.urlValider, {
    method:'POST', headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN':csrf(), Accept:'application/json' },
    body: JSON.stringify({ action, note }),
  })
  const d = await res.json()
  if (d.success) { rcForm.statut = d.statut; showToast('success', action === 'validate' ? 'PV validé ✓' : 'Rejeté.') }
}
function promptReject() { const n = prompt('Motif du rejet :',''); if (n?.trim()) valider('reject', n.trim()) }

// ─── SIGNATURES ──────────────────────────────────────────────────
function _canvasPos(role: string, e: MouseEvent | TouchEvent) {
  const c = sigCanvases[role]; if (!c) return { x:0, y:0 }
  const r = c.getBoundingClientRect()
  const t = e instanceof TouchEvent ? e.touches[0] : e
  return { x: t.clientX - r.left, y: t.clientY - r.top }
}
function startDraw(role: string, e: MouseEvent | TouchEvent) { sigDrawing[role]=true; sigLastPos[role]=_canvasPos(role,e) }
function draw(role: string, e: MouseEvent | TouchEvent) {
  if (!sigDrawing[role]) return
  const ctx = sigCanvases[role]?.getContext('2d'); if (!ctx) return
  const pos = _canvasPos(role,e)
  ctx.beginPath(); ctx.moveTo(sigLastPos[role].x, sigLastPos[role].y)
  ctx.lineTo(pos.x, pos.y); ctx.strokeStyle='#0f172a'; ctx.lineWidth=2; ctx.lineCap='round'
  ctx.stroke(); sigLastPos[role]=pos
}
function endDraw(role: string) {
  if (!sigDrawing[role]) return; sigDrawing[role]=false
  const c = sigCanvases[role]
  if (c) { sigForms[role].signature_b64 = c.toDataURL('image/png'); saveSig(role) }
}
function clearSig(role: string) {
  const ctx = sigCanvases[role]?.getContext('2d'); if (!ctx) return
  ctx.clearRect(0,0,sigCanvases[role]!.width,sigCanvases[role]!.height)
  sigForms[role].signature_b64=''; saveSig(role)
}
async function saveSig(role: string) {
  if (!props.urlSignature) return
  await fetch(props.urlSignature, {
    method:'POST', headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN':csrf(), Accept:'application/json' },
    body: JSON.stringify({ role, ...sigForms[role] }),
  })
}

// ─── INIT ────────────────────────────────────────────────────────
onMounted(() => {
  if (props.reunion) {
    Object.assign(rcForm, {
      entite:               props.reunion.entite               ?? '',
      intitule_mission:     props.reunion.intitule_mission     ?? '',
      code_mission:         props.reunion.code_mission         ?? '',
      norme_mpa:            props.reunion.norme_mpa            ?? 'MPA 2400 – 2410-1',
      date_reunion:         props.reunion.date_reunion         ?? '',
      heure_debut:          props.reunion.heure_debut          ?? '',
      heure_fin:            props.reunion.heure_fin            ?? '',
      lieu:                 props.reunion.lieu                 ?? '',
      preside_par:          props.reunion.preside_par          ?? '',
      secretaire_seance:    props.reunion.secretaire_seance    ?? '',
      conclusion_generale:  props.reunion.conclusion_generale  ?? '',
      observations_finales: props.reunion.observations_finales ?? '',
      statut:               props.reunion.statut               ?? 'draft',
      validation_note:      props.reunion.validation_note      ?? '',
      ordre_jour:      (props.reunion.ordre_jour      ?? []).map((oj: any) => ({ ...oj, _open:!!oj.form_link, notes:oj.notes??'' })),
      points_forts:     props.reunion.points_forts    ?? [],
      far_validations:  props.reunion.far_validations ?? [],
      suivi_modalites:  props.reunion.suivi_modalites ?? [],
      participants:     props.reunion.participants    ?? [],
    })
    if (!rcForm.participants.length && props.equipe?.length) {
      rcForm.participants = props.equipe.map((a: any) => ({ nom:a.full_name, fonction:a.role_label, entite:'Audit Interne', present:true }))
    }
    if (props.reunion.signatures) {
      Object.entries(props.reunion.signatures).forEach(([role, sig]) => { if (sigForms[role]) Object.assign(sigForms[role], sig) })
    }
  } else if (props.mission) {
    rcForm.intitule_mission = props.mission.libelle      ?? ''
    rcForm.code_mission     = props.mission.code_mission ?? ''
    rcForm.entite           = props.mission.lieux        ?? ''
    if (props.equipe?.length) {
      rcForm.participants = props.equipe.map((a: any) => ({ nom:a.full_name, fonction:a.role_label, entite:'Audit Interne', present:true }))
    }
  }
})
onBeforeUnmount(() => { if (_tt) clearTimeout(_tt) })
</script>

<style scoped>
/* ══ VARIABLES ══════════════════════════════════════════════════ */
:root { --navy:#0f172a; --blue:#1e40af; --border:#e2e8f0; --bg:#f1f5f9; --sh:0 1px 3px rgba(15,23,42,.07); }
.rc-shell { display:flex; flex-direction:column; height:100vh; background:var(--bg); font-family:'Segoe UI',system-ui,sans-serif; }

/* ── TOPBAR ──────────────────────────────────────────────────── */
.rc-topbar { display:flex; justify-content:space-between; align-items:center; padding:.5rem 1rem; background:white; border-bottom:1px solid var(--border); flex-shrink:0; gap:.4rem; flex-wrap:wrap; }
.rc-topbar__left,.rc-topbar__right { display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; }
.rc-code { background:var(--navy); color:white; padding:.2rem .6rem; border-radius:4px; font-size:.7rem; font-weight:600; }
.rc-sdot { width:8px; height:8px; border-radius:50%; display:inline-block; }
.sd--draft{background:#94a3b8;} .sd--in_review{background:#2563eb;} .sd--validated{background:#16a34a;}
.rc-vstatus { font-size:.7rem; color:#475569; }
.rc-div { width:1px; height:20px; background:var(--border); }
.rc-icon-muted { font-size:.8rem; color:#94a3b8; }
.rc-mission-lbl { font-size:.75rem; color:#475569; max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.rc-chip-role { display:inline-flex; align-items:center; gap:.25rem; background:#f1f5f9; padding:.2rem .6rem; border-radius:20px; font-size:.7rem; }
.rc-tabs { display:flex; background:#f1f5f9; border-radius:8px; padding:2px; gap:2px; }
.rc-tab { background:none; border:none; padding:.3rem .75rem; border-radius:6px; font-size:.72rem; cursor:pointer; color:#64748b; display:inline-flex; align-items:center; gap:.25rem; transition:all .15s; }
.rc-tab--active { background:white; color:#1e40af; font-weight:600; box-shadow:var(--sh); }
.rc-btn { display:inline-flex; align-items:center; gap:.3rem; padding:.3rem .75rem; border-radius:6px; font-size:.75rem; font-weight:500; border:1px solid transparent; cursor:pointer; transition:all .15s; }
.rc-btn:disabled { opacity:.5; cursor:not-allowed; }
.rc-btn--ghost { background:transparent; border-color:#cbd5e1; } .rc-btn--ghost:hover { background:#f1f5f9; }
.rc-btn--save { background:var(--navy); color:white; }
.rc-btn--submit { background:#2563eb; color:white; }
.rc-btn--validate { background:#10b981; color:white; }
.rc-btn--reject { background:#dc2626; color:white; }
.rc-btn--slides { background:linear-gradient(135deg,#6d28d9,#7c3aed); color:white; border:none; }
.rc-btn--slides:hover { opacity:.9; }
.rc-ib { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; background:transparent; border:1px solid #e2e8f0; border-radius:6px; cursor:pointer; }
.rc-ib--expand { color:#1e40af; border-color:#bfdbfe; }
.rc-banner { display:flex; align-items:center; gap:.5rem; padding:.3rem 1rem; font-size:.75rem; flex-shrink:0; }
.rc-banner--ok{background:#d1fae5;color:#065f46;} .rc-banner--review{background:#dbeafe;color:#1d4ed8;} .rc-banner--rejected{background:#fee2e2;color:#dc2626;}

/* ══ PRESENTATION PLEIN ÉCRAN ═══════════════════════════════════ */
.slides-fade-enter-active,.slides-fade-leave-active { transition:opacity .22s; }
.slides-fade-enter-from,.slides-fade-leave-to { opacity:0; }
.slide-anim-enter-active,.slide-anim-leave-active { transition:all .25s ease; }
.slide-anim-enter-from { transform:translateX(32px); opacity:0; }
.slide-anim-leave-to   { transform:translateX(-32px); opacity:0; }

.pres-overlay { position:fixed; inset:0; background:linear-gradient(160deg,#090e1a 0%,#111827 100%); z-index:3000; display:flex; flex-direction:column; outline:none; }
.pres-shell { display:flex; flex-direction:column; height:100%; }

/* Topbar présentation */
.pres-topbar { display:flex; align-items:center; gap:.6rem; padding:.42rem 1rem; background:rgba(255,255,255,.03); border-bottom:1px solid rgba(255,255,255,.07); flex-shrink:0; flex-wrap:wrap; }
.pres-close-btn { background:#dc2626; border:none; color:white; padding:.26rem .65rem; border-radius:6px; cursor:pointer; font-size:.7rem; font-weight:600; display:inline-flex; align-items:center; gap:.25rem; flex-shrink:0; }
.pres-topbar__mission { display:flex; align-items:center; gap:.35rem; font-size:.7rem; color:rgba(255,255,255,.4); white-space:nowrap; }
.pres-sep { opacity:.35; }
.pres-topbar__center { flex:1; display:flex; justify-content:center; }
.pres-slide-counter { display:flex; align-items:baseline; gap:.15rem; }
.pres-slide-n { font-size:1rem; font-weight:700; color:white; }
.pres-slide-t { font-size:.72rem; color:rgba(255,255,255,.4); }
.pres-topbar__right { display:flex; align-items:center; gap:.5rem; flex-shrink:0; }
.pres-saved { display:flex; align-items:center; gap:.25rem; font-size:.65rem; color:#10b981; }
.save-fade-enter-active,.save-fade-leave-active { transition:opacity .4s; }
.save-fade-enter-from,.save-fade-leave-to { opacity:0; }
.pres-save-btn { display:inline-flex; align-items:center; gap:.28rem; background:#1d4ed8; border:none; color:white; padding:.26rem .65rem; border-radius:6px; cursor:pointer; font-size:.7rem; font-weight:600; }
.pres-save-btn:disabled { opacity:.4; cursor:not-allowed; }

/* Corps: sidebar + stage */
.pres-body { display:flex; flex:1; overflow:hidden; }

/* Sidebar verticale */
.pres-sidebar { width:185px; flex-shrink:0; background:linear-gradient(180deg,#0f172a 0%,#1e293b 100%); border-right:1px solid rgba(255,255,255,.06); display:flex; flex-direction:column; overflow-y:auto; }
.pres-sidebar__hd { display:flex; align-items:center; gap:.4rem; padding:.65rem .85rem; border-bottom:1px solid rgba(255,255,255,.06); font-size:.65rem; font-weight:600; color:rgba(255,255,255,.4); text-transform:uppercase; letter-spacing:.07em; flex-shrink:0; }
.pres-sidebar__list { flex:1; overflow-y:auto; padding:.25rem 0; }
.pres-nav-item { display:flex; align-items:flex-start; gap:.4rem; width:100%; padding:.55rem .8rem; background:none; border:none; cursor:pointer; text-align:left; transition:all .12s; border-left:3px solid transparent; }
.pres-nav-item:hover { background:rgba(255,255,255,.05); }
.pres-nav-item--active { background:rgba(59,130,246,.13); border-left-color:#3b82f6; }
.pres-nav-item__num { width:17px; height:17px; border-radius:50%; flex-shrink:0; background:rgba(255,255,255,.1); display:flex; align-items:center; justify-content:center; font-size:.58rem; font-weight:700; color:rgba(255,255,255,.45); margin-top:1px; }
.pres-nav-item--active .pres-nav-item__num { background:#3b82f6; color:white; }
.pres-nav-item__body { flex:1; display:flex; flex-direction:column; gap:.08rem; min-width:0; }
.pres-nav-item__label { font-size:.7rem; font-weight:500; color:rgba(255,255,255,.45); line-height:1.2; }
.pres-nav-item--active .pres-nav-item__label { color:white; font-weight:600; }
.pres-nav-item__count { font-size:.58rem; color:rgba(255,255,255,.3); }
.pres-nav-item--active .pres-nav-item__count { color:#93c5fd; }

/* Stage */
.pres-stage { flex:1; position:relative; overflow:hidden; display:flex; flex-direction:column; background:#0d1117; }
.pres-progress { height:3px; background:rgba(255,255,255,.06); flex-shrink:0; }
.pres-progress__bar { height:100%; background:linear-gradient(90deg,#3b82f6,#8b5cf6); transition:width .3s ease; }

.pres-slide-wrap { flex:1; display:flex; padding:1rem; }
.pres-arr { position:absolute; top:50%; transform:translateY(-50%); width:32px; height:32px; border-radius:50%; background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.15); color:white; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:.85rem; transition:all .15s; z-index:10; }
.pres-arr--prev { left:6px; }
.pres-arr--next { right:6px; }
.pres-arr:hover:not(:disabled) { background:rgba(59,130,246,.65); transform:translateY(-50%) scale(1.08); }
.pres-arr:disabled { opacity:.12; cursor:not-allowed; }

/* ── SLIDES ────────────────────────────────────────────────── */
.sl { flex:1; display:flex; flex-direction:column; background:white; border-radius:12px; box-shadow:0 20px 55px rgba(0,0,0,.55); overflow:hidden; animation:sl-in .2s ease; }
@keyframes sl-in { from{opacity:0;transform:translateX(18px)} to{opacity:1;transform:translateX(0)} }

/* COUVERTURE */
.sl--cover { display:grid; grid-template-columns:1fr 1fr; background:linear-gradient(155deg,#0f172a 0%,#1e3a5f 45%,#1e40af 100%); }
.sl-cover__left { padding:1.75rem 1.5rem; display:flex; flex-direction:column; gap:.75rem; border-right:1px solid rgba(255,255,255,.07); }
.sl-cover__right { padding:1.5rem; display:flex; flex-direction:column; gap:.75rem; overflow-y:auto; }
.sl-cover__logo { width:44px; height:44px; background:rgba(255,255,255,.12); border-radius:10px; display:flex; align-items:center; justify-content:center; color:white; font-size:1.3rem; }
.sl-cover__norme { font-size:.6rem; color:rgba(255,255,255,.4); border:1px solid rgba(255,255,255,.15); padding:.15rem .5rem; border-radius:20px; width:fit-content; }
.sl-cover__eyebrow { font-size:.62rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:rgba(255,255,255,.45); }
.sl-cover__h1 { font-size:clamp(1rem,2.1vw,1.6rem); font-weight:800; color:white; line-height:1.25; margin:0; }
.sl-cover__entite { font-size:.75rem; color:rgba(255,255,255,.5); font-style:italic; }
.sl-cover__meta { display:flex; flex-direction:column; gap:.35rem; margin-top:auto; }
.sl-cover__meta-row { display:flex; align-items:center; gap:.4rem; font-size:.72rem; color:rgba(255,255,255,.6); }
.sl-cover__meta-row i { opacity:.55; }
.sl-cover__equipe-title { font-size:.6rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:rgba(255,255,255,.38); }
.sl-cover__equipe { display:flex; flex-direction:column; gap:.38rem; }
.sl-cover__membre { display:flex; align-items:center; gap:.5rem; background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.08); padding:.38rem .65rem; border-radius:7px; }
.sl-cover__role { font-size:.58rem; font-weight:800; padding:.1rem .38rem; border-radius:4px; flex-shrink:0; }
.role--DM{background:#dc2626;color:white;} .role--CM{background:#2563eb;color:white;} .role--AS{background:#059669;color:white;} .role--AJ{background:#7c3aed;color:white;}
.sl-cover__nom { font-size:.72rem; font-weight:600; color:white; }
.sl-cover__role-lbl { font-size:.58rem; color:rgba(255,255,255,.42); }
.sl-cover__kpis { display:flex; gap:.85rem; padding-top:.65rem; border-top:1px solid rgba(255,255,255,.08); }
.sl-cover__kpi { text-align:center; }
.sl-cover__kpi-val { font-size:1.4rem; font-weight:800; color:white; line-height:1; }
.sl-cover__kpi-lbl { font-size:.58rem; color:rgba(255,255,255,.38); text-transform:uppercase; margin-top:2px; }
.sl-cover__niveaux { display:flex; flex-wrap:wrap; gap:.28rem; }
.sl-cover__niv-chip { display:inline-flex; align-items:center; padding:.15rem .5rem; border-radius:20px; font-size:.6rem; font-weight:600; }

/* STANDARD */
.sl--std { overflow:hidden; }
.sl--wide { }
.sl-std__hd { display:flex; align-items:flex-start; gap:.8rem; padding:.95rem 1.2rem; background:linear-gradient(135deg,#f8fafc,#eff6ff); border-bottom:1px solid #e2e8f0; flex-shrink:0; flex-wrap:wrap; }
.sl-std__icon { width:40px; height:40px; border-radius:10px; background:var(--ic,#1e40af); display:flex; align-items:center; justify-content:center; color:white; font-size:1.15rem; flex-shrink:0; }
.sl-std__eyebrow { font-size:.6rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#94a3b8; margin-bottom:2px; }
.sl-std__title { font-size:.95rem; font-weight:700; color:#1e3a5f; }
.sl-std__badge { display:inline-flex; align-items:center; background:#dbeafe; color:#1e40af; padding:.18rem .55rem; border-radius:20px; font-size:.62rem; font-weight:600; white-space:nowrap; }
.sl-std__body { flex:1; overflow-y:auto; padding:1rem 1.2rem; }
.sl-std__body--scroll { overflow-y:auto; }
.sl-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:.65rem; flex:1; color:#94a3b8; font-size:.8rem; font-style:italic; padding:2rem; }
.sl-empty i { font-size:1.75rem; }
.sl-add-btn { display:inline-flex; align-items:center; gap:.25rem; background:#eff6ff; border:1px solid #bfdbfe; color:#1d4ed8; padding:.25rem .65rem; border-radius:6px; cursor:pointer; font-size:.7rem; font-weight:600; margin-left:auto; flex-shrink:0; }
.sl-niv-chips { display:flex; gap:.35rem; flex-wrap:wrap; margin-left:auto; }
.sl-niv-chip { display:inline-flex; align-items:center; padding:.13rem .5rem; border-radius:20px; font-size:.6rem; font-weight:600; white-space:nowrap; }
.sl-niv-badge { display:inline-block; padding:.1rem .42rem; border-radius:20px; font-size:.58rem; font-weight:700; white-space:nowrap; }

/* Objectifs */
.sl-obj-mission { background:#eff6ff; border-radius:7px; padding:.6rem .8rem; margin-bottom:.75rem; }
.sl-obj-mission__lbl { font-size:.6rem; font-weight:700; color:#1e40af; text-transform:uppercase; letter-spacing:.05em; margin-bottom:.18rem; }
.sl-obj-mission p { font-size:.8rem; color:#1e293b; margin:0; line-height:1.6; }
.sl-obj-list { display:flex; flex-direction:column; gap:.55rem; }
.sl-obj-block { background:#f8fafc; border-radius:9px; overflow:hidden; border:1px solid #e2e8f0; }
.sl-obj-block__hd { display:flex; align-items:center; gap:.55rem; padding:.48rem .85rem; background:#1e3a5f; }
.sl-obj-num { background:rgba(255,255,255,.2); color:white; padding:.08rem .4rem; border-radius:4px; font-size:.65rem; font-weight:700; }
.sl-obj-label { font-size:.78rem; font-weight:600; color:white; }
.sl-obj-tests { display:flex; flex-direction:column; }
.sl-obj-test { display:flex; align-items:center; gap:.5rem; padding:.32rem .85rem; border-bottom:1px solid #f1f5f9; font-size:.7rem; }
.sl-obj-test:last-child { border-bottom:none; }
.sl-obj-test__ref { background:#ede9fe; color:#6d28d9; padding:.06rem .38rem; border-radius:4px; font-size:.6rem; font-weight:700; flex-shrink:0; }
.sl-obj-test__lib { color:#334155; flex:1; }
.sl-obj-more { padding:.25rem .85rem; font-size:.62rem; color:#94a3b8; font-style:italic; }

/* Points forts */
.sl-pf-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:.55rem; }
.sl-pf-card { display:flex; align-items:flex-start; gap:.6rem; background:linear-gradient(135deg,#f0fdf4,#dcfce7); border:1px solid #a7f3d0; border-radius:9px; padding:.75rem .9rem; }
.sl-pf-card__ico { font-size:1rem; flex-shrink:0; margin-top:1px; }
.sl-pf-card__body { flex:1; }
.sl-pf-card__txt { font-size:.8rem; color:#064e3b; font-weight:500; line-height:1.5; }
.sl-pf-card__inp { width:100%; border:1px solid #a7f3d0; border-radius:5px; padding:.3rem .45rem; font-size:.8rem; font-family:inherit; background:white; outline:none; }
.sl-pf-card__inp:focus { border-color:#059669; }
.sl-pf-card__del { background:#fee2e2; border:1px solid #fecaca; color:#dc2626; width:18px; height:18px; border-radius:4px; cursor:pointer; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:.6rem; }

/* FOCI */
.sl-foci-legend { display:grid; grid-template-columns:80px 90px 1fr 1fr; background:#1e3a5f; }
.sl-foci-legend > div { padding:.3rem .38rem; font-size:.58rem; font-weight:700; color:rgba(255,255,255,.72); text-transform:uppercase; letter-spacing:.04em; border-right:1px solid rgba(255,255,255,.1); }
.sl-foci-obj-banner { display:flex; align-items:center; gap:.45rem; padding:.3rem .75rem; background:#1e3a5f; border-bottom:2px solid #1e3a5f; }
.sl-foci-obj__lbl { background:rgba(255,255,255,.2); padding:.05rem .4rem; border-radius:3px; font-size:.56rem; font-weight:700; color:white; white-space:nowrap; }
.sl-foci-obj__txt { font-size:.68rem; font-weight:600; color:white; }
.sl-foci-rub { padding:.26rem .75rem; background:linear-gradient(90deg,#eff6ff,#f8fafc); border-bottom:1px solid #bfdbfe; font-size:.67rem; font-weight:600; color:#1e40af; display:flex; align-items:center; gap:.28rem; }
.sl-foci-ssrub { padding:.18rem 1rem; background:#f0fdf4; border-bottom:1px solid #bbf7d0; font-size:.63rem; color:#065f46; font-style:italic; display:flex; align-items:center; gap:.22rem; }
.sl-foci-row { display:grid; grid-template-columns:80px 90px 1fr 1fr; border-bottom:1px solid #f1f5f9; cursor:pointer; transition:background .1s; }
.sl-foci-row:hover { background:#f0f9ff; }
.sl-foci-cell { padding:.3rem .38rem; font-size:.65rem; color:#334155; border-right:1px solid #f1f5f9; display:flex; align-items:center; }
.sl-foci-cell--txt { line-height:1.35; align-items:flex-start; }
.sl-foci-cell--reco { color:#1e40af; font-weight:500; }
.sl-foci-num-badge { background:#1e3a5f; color:white; padding:.08rem .35rem; border-radius:4px; font-size:.56rem; font-weight:700; }
.focirow--critique   { border-left:3px solid #9d174d; }
.focirow--insuffisant{ border-left:3px solid #dc2626; }
.focirow--a_ameliorer{ border-left:3px solid #d97706; }
.focirow--satisfaisant{ border-left:3px solid #16a34a; }
.focirow--conforme   { border-left:3px solid #16a34a; }
.sl-foci-hint { padding:.38rem .85rem; background:#f0f9ff; border-top:1px solid #bfdbfe; font-size:.62rem; color:#0369a1; display:flex; align-items:center; gap:.35rem; flex-shrink:0; }

/* FAR */
.sl--far { flex:1; overflow:hidden; }
.sl-far-hd { display:flex; align-items:flex-start; gap:.85rem; padding:.85rem 1.2rem; flex-shrink:0; flex-wrap:wrap; gap:.6rem; }
.sl-far-hd--accepte       { background:linear-gradient(135deg,#065f46,#059669); color:white; }
.sl-far-hd--non_accepte   { background:linear-gradient(135deg,#991b1b,#dc2626); color:white; }
.sl-far-hd--en_discussion { background:linear-gradient(135deg,#1e40af,#2563eb); color:white; }
.sl-far-hd__left  { display:flex; align-items:flex-start; gap:.8rem; flex:1; }
.sl-far-hd__right { display:flex; align-items:flex-start; gap:.4rem; flex-wrap:wrap; flex-shrink:0; }
.sl-far-num { background:rgba(255,255,255,.2); color:white; padding:.18rem .6rem; border-radius:5px; font-size:.88rem; font-weight:800; white-space:nowrap; }
.sl-far-rubrique { font-size:.8rem; font-weight:600; color:white; }
.sl-far-linked { display:inline-flex; align-items:center; gap:.18rem; background:rgba(255,255,255,.18); padding:.06rem .38rem; border-radius:4px; font-size:.58rem; font-weight:600; margin-left:.4rem; }
.sl-far-eyebrow { font-size:.6rem; color:rgba(255,255,255,.52); margin-top:2px; }
.sl-far-accept-grp { display:flex; gap:.25rem; flex-wrap:wrap; }
.sl-far-accept-btn { display:inline-flex; align-items:center; gap:.22rem; padding:.25rem .6rem; background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.22); color:white; border-radius:6px; cursor:pointer; font-size:.68rem; font-weight:600; transition:all .12s; }
.sl-far-accept-btn:hover:not(:disabled) { background:rgba(255,255,255,.24); }
.sl-far-accept-btn:disabled { opacity:.45; cursor:not-allowed; }
.sl-far-accept-btn.active { border-width:2px; }
.sl-far-obs-btn { display:inline-flex; align-items:center; gap:.28rem; background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.28); color:white; padding:.25rem .7rem; border-radius:6px; cursor:pointer; font-size:.68rem; font-weight:600; }
.sl-far-obs-btn:hover { background:rgba(255,255,255,.28); }
.sl-far-body { display:grid; grid-template-columns:1fr 1fr; flex:1; overflow-y:auto; }
.sl-far-col { padding:.9rem 1.1rem; display:flex; flex-direction:column; gap:.75rem; }
.sl-far-col:last-child { background:#f8fafc; border-left:1px solid var(--border); }
.sl-far-section {}
.sl-far-section__title { display:flex; align-items:center; gap:.3rem; font-size:.6rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#64748b; margin-bottom:.35rem; }
.sl-far-section__title--red { color:#dc2626; }
.sl-far-section__title--orange { color:#d97706; }
.sl-far-section__title--blue { color:#1e40af; }
.sl-far-txt { font-size:.8rem; color:#334155; line-height:1.55; background:#f1f5f9; padding:.5rem .7rem; border-radius:7px; white-space:pre-wrap; word-break:break-word; }
.sl-far-txt--reco { background:#eff6ff; color:#1e40af; font-weight:500; }
.sl-far-ta { width:100%; border:1px solid #ddd6fe; border-radius:6px; padding:.45rem .6rem; font-size:.78rem; font-family:inherit; resize:vertical; outline:none; }
.sl-far-ta:focus { border-color:#7c3aed; box-shadow:0 0 0 2px rgba(124,58,237,.1); }
.sl-far-plan { background:white; border:1px solid var(--border); border-radius:9px; padding:.8rem; }
.sl-far-plan__title { font-size:.6rem; font-weight:700; text-transform:uppercase; color:#64748b; margin-bottom:.5rem; letter-spacing:.05em; }
.sl-far-plan__row { display:flex; align-items:center; gap:.45rem; margin-bottom:.35rem; font-size:.73rem; }
.sl-far-plan__lbl { color:#94a3b8; min-width:78px; font-weight:600; }
.sl-far-plan__inp { flex:1; border:1px solid var(--border); border-radius:5px; padding:.22rem .38rem; font-size:.7rem; font-family:inherit; }
.sl-far-checks { display:flex; gap:.9rem; padding-top:.5rem; border-top:1px solid var(--border); }
.sl-far-check { display:flex; align-items:center; gap:.35rem; font-size:.73rem; cursor:pointer; }
.sl-far-nav { display:flex; align-items:center; gap:.65rem; padding:.5rem 1.1rem; background:#f8fafc; border-top:1px solid var(--border); flex-shrink:0; }
.sl-far-nav__info { font-size:.68rem; color:#64748b; flex:1; }
.sl-far-nav__btns { display:flex; gap:.3rem; }
.sl-far-nav__btn { display:inline-flex; align-items:center; gap:.22rem; background:white; border:1px solid #cbd5e1; color:#475569; padding:.25rem .65rem; border-radius:6px; cursor:pointer; font-size:.68rem; }
.sl-far-nav__btn:disabled { opacity:.28; cursor:not-allowed; }
.sl-far-nav__btn:not(:disabled):hover { background:#f1f5f9; }
.sl-far-refresh-btn { display:inline-flex; align-items:center; gap:.25rem; background:#fef3c7; border:1px solid #fcd34d; color:#92400e; padding:.25rem .6rem; border-radius:6px; cursor:pointer; font-size:.65rem; font-weight:600; }
.sl-far-refresh-btn:hover { background:#fde68a; }
.sl-far-detail-btn { display:inline-flex; align-items:center; gap:.25rem; background:#eff6ff; border:1px solid #bfdbfe; color:#1d4ed8; padding:.25rem .6rem; border-radius:6px; cursor:pointer; font-size:.65rem; font-weight:600; }

/* Suivi slides */
.sl-suivi-cards { display:flex; flex-direction:column; gap:.55rem; }
.sl-suivi-card { display:flex; gap:.75rem; background:#f8fafc; border:1px solid var(--border); border-radius:9px; padding:.75rem .9rem; }
.sl-suivi-card__num { width:24px; height:24px; background:var(--navy); color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.65rem; font-weight:700; flex-shrink:0; }
.sl-suivi-card__body { flex:1; }
.sl-suivi-card__edit { display:flex; flex-direction:column; gap:.35rem; }
.sl-suivi-inp { border:1px solid var(--border); border-radius:5px; padding:.3rem .45rem; font-size:.76rem; font-family:inherit; width:100%; box-sizing:border-box; }
.sl-suivi-ta { border:1px solid var(--border); border-radius:5px; padding:.3rem .45rem; font-size:.76rem; font-family:inherit; resize:vertical; width:100%; box-sizing:border-box; }
.sl-suivi-card__date { font-size:.78rem; font-weight:600; color:#1e40af; margin-bottom:.18rem; display:flex; align-items:center; gap:.28rem; }
.sl-suivi-card__delais { font-size:.72rem; color:#64748b; margin-bottom:.28rem; }
.sl-suivi-card__modal { font-size:.78rem; color:#334155; line-height:1.55; }

/* Participants slides */
.sl-part-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(185px,1fr)); gap:.55rem; }
.sl-part-card { border-radius:9px; padding:.75rem .85rem; border:1px solid var(--border); display:flex; flex-direction:column; gap:.5rem; }
.sl-part-card--ok  { background:linear-gradient(135deg,#f0fdf4,white); border-color:#a7f3d0; }
.sl-part-card--abs { background:#fafafa; opacity:.72; }
.sl-part-card__top { display:flex; align-items:center; justify-content:space-between; }
.sl-part-check { display:flex; align-items:center; gap:.38rem; cursor:pointer; font-size:.7rem; }
.p-ok  { color:#065f46; font-weight:600; }
.p-abs { color:#dc2626; font-weight:600; }
.sl-part-card__fields { display:flex; flex-direction:column; gap:.28rem; }
.sl-part-inp { border:1px solid var(--border); border-radius:5px; padding:.28rem .45rem; font-size:.73rem; font-family:inherit; width:100%; box-sizing:border-box; }
.sl-part-card__nom { font-size:.82rem; font-weight:600; color:#1e293b; }
.sl-part-card__fn  { font-size:.7rem; color:#64748b; }
.sl-part-card__ent { font-size:.63rem; color:#94a3b8; }

/* Conclusion slide */
.sl--concl { display:grid; grid-template-columns:230px 1fr; }
.sl-concl__left { background:linear-gradient(160deg,#1e3a5f,#1e40af); padding:1.65rem 1.4rem; display:flex; flex-direction:column; gap:.75rem; color:white; }
.sl-concl__icon { font-size:2rem; opacity:.6; }
.sl-concl__title { font-size:1rem; font-weight:800; }
.sl-concl__norme { font-size:.58rem; opacity:.42; text-transform:uppercase; letter-spacing:.08em; }
.sl-concl__mission { font-size:.73rem; background:rgba(255,255,255,.1); padding:.4rem .62rem; border-radius:6px; line-height:1.4; margin-top:auto; }
.sl-concl__date { font-size:.67rem; opacity:.48; }
.sl-concl__stats { display:flex; gap:1.1rem; padding-top:.65rem; border-top:1px solid rgba(255,255,255,.1); }
.sl-concl__stat { text-align:center; }
.sl-concl__stat-val { font-size:1.4rem; font-weight:800; display:block; line-height:1; }
.sl-concl__stat-lbl { font-size:.58rem; opacity:.48; text-transform:uppercase; }
.sl-concl__save-btn { display:inline-flex; align-items:center; gap:.28rem; background:rgba(255,255,255,.17); border:1px solid rgba(255,255,255,.26); color:white; padding:.38rem .85rem; border-radius:7px; cursor:pointer; font-size:.73rem; font-weight:600; }
.sl-concl__save-btn:hover { background:rgba(255,255,255,.27); }
.sl-concl__right { padding:1.4rem; overflow-y:auto; display:flex; flex-direction:column; gap:.3rem; }
.sl-concl__lbl { font-size:.6rem; font-weight:700; color:#1e40af; text-transform:uppercase; letter-spacing:.04em; display:block; }
.sl-concl__ta { border:1px solid #ddd6fe; border-radius:7px; padding:.55rem .75rem; font-size:.82rem; font-family:inherit; resize:vertical; outline:none; width:100%; box-sizing:border-box; }
.sl-concl__ta:focus { border-color:#7c3aed; box-shadow:0 0 0 2px rgba(124,58,237,.1); }
.sl-concl__ro { font-size:.83rem; color:#334155; line-height:1.7; background:#f8fafc; padding:.55rem .75rem; border-radius:7px; }
.sl-concl__ro--obs { color:#64748b; font-style:italic; }
.sl-concl__merci { text-align:center; margin-top:auto; padding-top:.85rem; font-size:.85rem; font-weight:600; color:#1e3a5f; border-top:1px solid var(--border); }

/* ══ MAIN ════════════════════════════════════════════════════ */
.rc-main { flex:1; overflow:auto; padding:1rem; }
.rc-section { background:white; border-radius:10px; border:1px solid var(--border); overflow:visible; box-shadow:var(--sh); margin-bottom:.85rem; }
.rc-section__title { display:flex; align-items:center; justify-content:space-between; padding:.6rem 1rem; background:linear-gradient(135deg,#eff6ff,#f0fdf4); border-bottom:1px solid var(--border); font-size:.82rem; font-weight:700; color:#1e3a5f; border-radius:10px 10px 0 0; }
.rc-section__actions { display:flex; align-items:center; gap:.4rem; }
.rc-section__body { padding:.85rem 1rem; }
.rc-section--ft,.rc-section--fraps { overflow:hidden; }
.rc-badge-count { background:#1e40af; color:white; font-size:.58rem; padding:.05rem .4rem; border-radius:20px; font-weight:600; margin-left:.3rem; }
.rc-btn-slides-link { display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px; background:linear-gradient(135deg,#6d28d9,#7c3aed); color:white; border:none; border-radius:6px; cursor:pointer; font-size:.7rem; }
.rc-btn-slides-link:hover { opacity:.88; }
.rc-grid { display:grid; gap:.65rem; padding:.85rem 1rem; }
.rc-grid--3 { grid-template-columns:1fr 1fr 1fr; }
.rc-field { display:flex; flex-direction:column; gap:.25rem; }
.rc-field--full { grid-column:1/-1; }
.rc-field label,.rc-lbl-sm { font-size:.62rem; font-weight:700; color:#1e40af; text-transform:uppercase; letter-spacing:.04em; }
.rc-inp { border:1px solid #ddd6fe; border-radius:6px; padding:6px 10px; font-size:.78rem; font-family:inherit; width:100%; box-sizing:border-box; outline:none; }
.rc-inp:focus { border-color:#1e40af; box-shadow:0 0 0 2px rgba(30,64,175,.12); }
.rc-inp:disabled { background:#f8fafc; color:#64748b; }
.rc-inp-sm { width:100%; border:1px solid #e2e8f0; border-radius:4px; padding:.2rem .4rem; font-size:.7rem; font-family:inherit; box-sizing:border-box; }
.rc-ta { width:100%; border:1px solid #ddd6fe; border-radius:6px; padding:6px 10px; font-size:.78rem; font-family:inherit; resize:vertical; box-sizing:border-box; outline:none; }
.rc-ta-sm { width:100%; border:1px solid #e2e8f0; border-radius:4px; padding:.2rem .4rem; font-size:.7rem; font-family:inherit; box-sizing:border-box; }
.rc-ta-cell { width:100%; border:1px solid #e2e8f0; border-radius:4px; padding:.2rem .4rem; font-size:.66rem; font-family:inherit; resize:vertical; box-sizing:border-box; }
.rc-sel-sm { border-radius:6px; border:1px solid #e2e8f0; padding:.2rem .3rem; font-size:.65rem; font-family:inherit; cursor:pointer; }
.rc-del { display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; background:#fee2e2; border:1px solid #fecaca; color:#dc2626; border-radius:4px; cursor:pointer; }
.rc-add-btn { display:inline-flex; align-items:center; gap:.2rem; padding:.2rem .6rem; background:#eff6ff; border:1px solid #bfdbfe; color:#1d4ed8; border-radius:6px; font-size:.65rem; cursor:pointer; }
.rc-sync-btn { display:inline-flex; align-items:center; gap:.2rem; padding:.2rem .6rem; background:#fef3c7; border:1px solid #fcd34d; color:#92400e; border-radius:6px; font-size:.65rem; cursor:pointer; }
.rc-tc { text-align:center; } .rc-muted { color:#94a3b8; font-weight:700; font-size:.62rem; }
.rc-ec { text-align:center; color:#94a3b8; padding:1rem; font-style:italic; font-size:.7rem; }
.rc-ec--pad { padding:2rem; }

/* Mission banner */
.rc-mission-banner { display:flex; gap:1rem; background:linear-gradient(135deg,#1e3a5f 0%,#1e40af 100%); border-radius:10px; padding:1rem 1.25rem; box-shadow:0 4px 12px rgba(30,58,95,.25); flex-wrap:wrap; margin-bottom:.85rem; }
.rc-mb-left { display:flex; gap:.85rem; flex:1; min-width:280px; align-items:flex-start; }
.rc-mb-icon { width:42px; height:42px; background:rgba(255,255,255,.15); border-radius:10px; display:flex; align-items:center; justify-content:center; color:white; font-size:1.3rem; flex-shrink:0; }
.rc-mb-body { flex:1; }
.rc-mb-title { font-size:.88rem; font-weight:700; color:white; line-height:1.4; margin-bottom:.35rem; }
.rc-mb-meta { display:flex; flex-wrap:wrap; gap:.5rem .85rem; margin-bottom:.4rem; }
.rc-mb-meta span { display:inline-flex; align-items:center; gap:.3rem; font-size:.68rem; color:rgba(255,255,255,.75); }
.rc-mb-kpis { display:flex; gap:.75rem; margin-bottom:.5rem; }
.rc-mb-kpi { text-align:center; }
.rc-mb-kpi__val { font-size:1.35rem; font-weight:800; color:white; line-height:1; }
.rc-mb-kpi__lbl { font-size:.58rem; color:rgba(255,255,255,.6); text-transform:uppercase; margin-top:2px; }
.rc-mb-niveaux { display:flex; flex-wrap:wrap; gap:.25rem; }
.rc-mb-niv-chip { display:inline-flex; align-items:center; gap:.25rem; font-size:.58rem; padding:.1rem .4rem; border-radius:20px; font-weight:600; }
.rc-mb-equipe { background:rgba(255,255,255,.1); border-radius:8px; padding:.65rem .85rem; min-width:200px; }
.rc-mb-equipe__title { font-size:.65rem; font-weight:700; color:rgba(255,255,255,.7); text-transform:uppercase; letter-spacing:.06em; display:flex; align-items:center; gap:.3rem; margin-bottom:.5rem; }
.rc-mb-membre { display:flex; align-items:center; gap:.5rem; margin-bottom:.3rem; }
.rc-mb-role { font-size:.6rem; font-weight:700; padding:.1rem .4rem; border-radius:4px; white-space:nowrap; }
.rc-mb-nom { font-size:.72rem; color:white; font-weight:500; }
.rc-mb-role-lbl { font-size:.58rem; color:rgba(255,255,255,.55); }
.rc-mb-membre-info { display:flex; flex-direction:column; }

/* Fiches test */
.rc-ft-list { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:.65rem; padding:1rem; }
.rc-ft-card { border:1px solid var(--border); border-radius:8px; padding:.65rem .85rem; background:white; }
.rc-ft-card__head { display:flex; align-items:center; gap:.4rem; margin-bottom:.35rem; }
.rc-ft-code { background:#1e3a5f; color:white; padding:.1rem .4rem; border-radius:4px; font-size:.6rem; font-weight:700; }
.rc-ft-statut { font-size:.65rem; color:#64748b; }
.rc-ft-auditeur { font-size:.68rem; color:#334155; margin-left:auto; }
.rc-ft-ia { display:flex; align-items:center; gap:.4rem; }
.rc-ft-mini-card { display:flex; align-items:center; gap:.5rem; padding:.28rem .55rem; border:1px solid #f1f5f9; border-radius:6px; margin-bottom:.22rem; font-size:.7rem; }
.rc-ia-score { color:white; padding:.1rem .4rem; border-radius:12px; font-size:.65rem; font-weight:700; }
.rc-ia-score--sm { font-size:.6rem; padding:.05rem .3rem; }
.rc-ia-concl { font-size:.65rem; color:#475569; flex:1; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; }

/* ODJ */
.rc-odj-list { padding:.5rem 1rem 1rem; display:flex; flex-direction:column; gap:.6rem; }
.rc-odj-add-wrap { display:flex; align-items:center; gap:.4rem; }
.rc-odj-link-sel { min-width:180px; }
.rc-odj-item { border:1px solid #e2e8f0; border-radius:8px; overflow:hidden; background:white; }
.rc-odj-item__head { display:flex; align-items:flex-start; gap:.5rem; padding:.5rem .75rem; background:#fafafa; cursor:pointer; transition:background .15s; }
.rc-odj-item__head:hover { background:#f1f5f9; }
.rc-odj-num { width:22px; height:22px; background:#1e3a5f; color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.6rem; font-weight:700; flex-shrink:0; margin-top:3px; }
.rc-odj-libelle-wrap { flex:1; }
.rc-odj-ta { resize:none; background:transparent; border-color:transparent; }
.rc-odj-ta:focus { border-color:#bfdbfe; background:white; }
.rc-odj-link-badge { display:inline-flex; align-items:center; gap:.22rem; padding:.13rem .45rem; border-radius:20px; font-size:.57rem; font-weight:700; white-space:nowrap; flex-shrink:0; margin-top:4px; }
.flb--objectifs_mission{background:#dbeafe;color:#1d4ed8;} .flb--points_forts{background:#fef3c7;color:#92400e;}
.flb--far_validations{background:#fee2e2;color:#dc2626;} .flb--suivi_modalites{background:#d1fae5;color:#065f46;}
.flb--fiche_test{background:#ede9fe;color:#5b21b6;} .flb--custom{background:#f1f5f9;color:#475569;}
.rc-odj-item__actions { display:flex; align-items:center; gap:.22rem; flex-shrink:0; margin-top:2px; }
.rc-odj-form { border-top:2px solid #e0f2fe; background:#f8fafc; }
.odj-expand-enter-active,.odj-expand-leave-active { transition:max-height .25s ease,opacity .2s; overflow:hidden; max-height:1200px; }
.odj-expand-enter-from,.odj-expand-leave-to { max-height:0; opacity:0; }
.rc-odj-form__inner { padding:.72rem 1rem; }
.rc-odj-form__inner--wide { padding:.72rem .5rem; }
.rc-odj-form__badge { display:inline-flex; align-items:center; gap:.32rem; font-size:.7rem; font-weight:700; color:#1e3a5f; margin-bottom:.6rem; background:#eff6ff; padding:.22rem .7rem; border-radius:20px; border:1px solid #bfdbfe; }
.rc-odj-form__add { display:flex; gap:.38rem; margin-bottom:.6rem; }
.rc-far-count { background:#fee2e2; color:#dc2626; padding:.04rem .38rem; border-radius:20px; font-size:.57rem; font-weight:700; }
.rc-pf-list { display:flex; flex-direction:column; gap:.28rem; }
.rc-pf-row { display:flex; align-items:center; gap:.38rem; }
.rc-prog-badge { display:inline-flex; align-items:center; gap:.28rem; background:#1e3a5f; color:white; padding:.18rem .55rem; border-radius:6px; font-size:.62rem; font-weight:600; margin-bottom:.45rem; }

/* Tables */
.rc-tbl { width:100%; border-collapse:collapse; font-size:.72rem; }
.rc-tbl--compact th,.rc-tbl--compact td { padding:.22rem .38rem; }
.rc-tbl th,.rc-tbl td { padding:.32rem .55rem; border-bottom:1px solid #f1f5f9; border-right:1px solid #f1f5f9; text-align:left; vertical-align:middle; }
.rc-tbl th { background:#f8fafc; font-weight:700; color:#475569; border-bottom:1px solid var(--border); }
.rc-td-obj { background:#eff6ff; font-weight:600; color:#1e40af; font-size:.65rem; vertical-align:top; }
.rc-far-table-wrap { overflow-x:auto; }
.rc-far-tbl { width:100%; border-collapse:collapse; font-size:.63rem; }
.rc-far-tbl th,.rc-far-tbl td { padding:.26rem .3rem; border-bottom:1px solid #f1f5f9; border-right:1px solid #f1f5f9; vertical-align:top; }
.rc-far-tbl th { background:#1e3a5f; color:white; font-weight:600; font-size:.56rem; text-transform:uppercase; }
.rc-far-row--ok { border-left:3px solid #10b981; }
.rc-far-row--ko { border-left:3px solid #dc2626; }
.rc-far-num-btn { display:inline-flex; align-items:center; gap:.2rem; background:none; border:none; cursor:pointer; }
.rc-far-num-badge { background:#1e3a5f; color:white; padding:.1rem .32rem; border-radius:4px; font-size:.56rem; font-weight:700; }
.rc-cell-txt { font-size:.62rem; color:#334155; line-height:1.4; white-space:pre-wrap; word-break:break-word; }
.rc-cell-reco { color:#1e40af; font-weight:500; }
.rc-far-accept-cell { display:flex; flex-direction:column; gap:.22rem; }
.rc-qual-checks { display:flex; flex-direction:column; gap:.13rem; }
.rc-chk-lbl { display:flex; align-items:center; gap:.22rem; font-size:.58rem; color:#475569; cursor:pointer; }

/* FOCI onglet */
.rc-foci-obj-block { border-bottom:2px solid #1e3a5f; }
.rc-foci-obj-banner { display:flex; align-items:center; gap:.5rem; padding:.38rem .85rem; background:#1e3a5f; }
.rc-foci-obj-lbl { background:rgba(255,255,255,.2); padding:.1rem .45rem; border-radius:4px; font-size:.58rem; font-weight:700; color:white; white-space:nowrap; }
.rc-foci-obj-txt { font-size:.7rem; font-weight:600; color:white; }
.rc-foci-rubr-block { border-bottom:1px solid #dde5ef; }
.rc-foci-rubr-banner { padding:.3rem .85rem; background:linear-gradient(90deg,#eff6ff,#f8fafc); border-bottom:1px solid #bfdbfe; font-size:.7rem; font-weight:600; color:#1e40af; }
.rc-foci-ssrubr-banner { padding:.2rem 1.2rem; background:#f0fdf4; border-bottom:1px solid #bbf7d0; font-size:.65rem; color:#065f46; font-style:italic; }
.rc-frap-row { display:grid; grid-template-columns:68px 97px 1fr 1fr; border-bottom:1px solid #f1f5f9; }
.rc-foci-row--critique   { border-left:3px solid #9d174d; }
.rc-foci-row--insuffisant{ border-left:3px solid #dc2626; }
.rc-foci-row--ameliorer  { border-left:3px solid #d97706; }
.rc-foci-row--ok         { border-left:3px solid #16a34a; }
.rc-frap-row > * { padding:.32rem .38rem; font-size:.65rem; border-right:1px solid #f1f5f9; color:#334155; line-height:1.4; }
.rc-frap-row__num,.rc-frap-row__niv { display:flex; align-items:center; justify-content:center; }
.rc-frap-row__reco { color:#1e40af; }
.rc-foci-num-btn { background:none; border:none; cursor:pointer; }
.rc-foci-num-badge { background:#1e3a5f; color:white; padding:.1rem .38rem; border-radius:4px; font-size:.56rem; font-weight:700; }
.rc-foci-niv-badge { display:inline-block; padding:.1rem .38rem; border-radius:20px; font-size:.53rem; font-weight:700; text-align:center; white-space:nowrap; }

/* Participants PV */
.rc-participants-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); gap:.72rem; padding:1rem; }
.rc-participant-card { border:1px solid var(--border); border-radius:8px; overflow:hidden; background:white; }
.rc-participant-card__top { display:flex; align-items:center; gap:.5rem; padding:.42rem .65rem; background:linear-gradient(90deg,#eff6ff,#f0fdf4); border-bottom:1px solid var(--border); }
.rc-pcard-check { display:flex; align-items:center; gap:.32rem; cursor:pointer; }
.rc-pcard-present { font-size:.6rem; font-weight:700; padding:.08rem .42rem; border-radius:20px; }
.rpp--ok{background:#d1fae5;color:#065f46;} .rpp--absent{background:#fee2e2;color:#dc2626;}
.rc-pcard-fields { display:flex; flex-direction:column; gap:.28rem; padding:.5rem .65rem .6rem; }

/* Signatures */
.rc-signatures-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; padding:1rem; }
.rc-sig-card { border:1px solid var(--border); border-radius:8px; overflow:hidden; }
.rc-sig-card__title { background:#1e3a5f; color:white; padding:.38rem .7rem; font-size:.7rem; font-weight:600; text-align:center; }
.rc-sig-fields { display:flex; flex-direction:column; gap:.28rem; padding:.5rem .7rem; }
.rc-sig-box { padding:.28rem .7rem; border-top:1px dashed var(--border); position:relative; }
.rc-sig-canvas { border:1px solid #e2e8f0; border-radius:4px; width:100%; cursor:crosshair; background:#fafafa; }
.rc-sig-img { width:100%; border:1px solid #e2e8f0; border-radius:4px; }
.rc-sig-placeholder { text-align:center; color:#94a3b8; padding:1.5rem; font-size:.8rem; }
.rc-sig-clear { position:absolute; top:.5rem; right:.7rem; background:#fee2e2; border:none; color:#dc2626; padding:.13rem .38rem; border-radius:4px; font-size:.58rem; cursor:pointer; }
.rc-confidential { text-align:center; font-size:.65rem; color:#94a3b8; font-style:italic; padding:.5rem 1rem; border-top:1px solid var(--border); }

/* PV */
.rc-pv-doc { max-width:1200px; margin:0 auto; display:flex; flex-direction:column; gap:.85rem; }
.rc-doc-header { background:linear-gradient(135deg,#1e3a5f,#2c5282); color:white; border-radius:10px; padding:1.25rem 1.5rem; text-align:center; }
.rc-doc-header__title { font-size:1rem; font-weight:800; letter-spacing:.03em; }
.rc-doc-header__sub { font-size:.72rem; color:rgba(255,255,255,.7); margin-top:.25rem; }

/* Modals */
.rc-modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.55); display:flex; align-items:center; justify-content:center; z-index:4000; }
.rc-far-modal,.rc-frap-modal { background:white; border-radius:14px; width:90%; max-width:720px; max-height:90vh; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.28); }
.rc-frap-modal { max-width:680px; }
.rc-far-modal__hd,.rc-frap-modal__hd { display:flex; align-items:center; gap:.72rem; padding:.95rem 1.2rem; border-bottom:1px solid var(--border); background:#f8fafc; }
.rc-far-modal__icon,.rc-frap-modal__icon { width:36px; height:36px; background:#1e3a5f; border-radius:8px; display:flex; align-items:center; justify-content:center; color:white; font-size:1rem; flex-shrink:0; }
.rc-frap-modal__hd h2 { font-size:.95rem; font-weight:700; margin:0; color:#0f172a; }
.rc-far-modal__body,.rc-frap-modal__body { flex:1; overflow-y:auto; padding:1rem; }
.rc-far-modal__ft,.rc-frap-modal__ft { display:flex; justify-content:flex-end; padding:.7rem 1.2rem; border-top:1px solid var(--border); }
.rc-far-modal-acc { display:inline-block; padding:.13rem .45rem; border-radius:6px; font-size:.68rem; font-weight:600; }
.rc-fm-grid { display:grid; grid-template-columns:1fr 1fr; gap:.72rem; }
.rc-fm-field { display:flex; flex-direction:column; gap:.2rem; }
.rc-fm-field--full { grid-column:span 2; }
.rc-fm-lbl { font-size:.6rem; font-weight:700; color:#1e40af; text-transform:uppercase; letter-spacing:.04em; }
.rc-fm-lbl--green { color:#065f46; }
.rc-fm-val { font-size:.78rem; color:#334155; line-height:1.5; background:#f8fafc; padding:.38rem .55rem; border-radius:6px; border:1px solid #f1f5f9; white-space:pre-wrap; }
.rc-fm-val--obj  { background:#eff6ff; border-left:3px solid #2563eb; color:#1e40af; }
.rc-fm-val--reco { border-left:3px solid #1e40af; background:#eff6ff; }
.rc-fm-val--green{ background:#f0fdf4; border-left:3px solid #16a34a; color:#065f46; }
.rc-fm-ta { width:100%; border:1px solid #e2e8f0; border-radius:6px; padding:.32rem .48rem; font-size:.76rem; font-family:inherit; resize:vertical; outline:none; box-sizing:border-box; }
.rc-fm-ta:focus { border-color:#1e40af; box-shadow:0 0 0 2px rgba(30,64,175,.1); }
.rc-fm-inp { border:1px solid #e2e8f0; border-radius:6px; padding:.32rem .48rem; font-size:.76rem; font-family:inherit; width:100%; box-sizing:border-box; outline:none; }
.rc-fm-inp:focus { border-color:#1e40af; }
.rc-fm-sel { border:1px solid #e2e8f0; border-radius:6px; padding:.32rem .48rem; font-size:.76rem; font-family:inherit; width:100%; cursor:pointer; }
.rc-far-obs-link { display:inline-flex; align-items:center; gap:.35rem; background:#eff6ff; border:1px solid #bfdbfe; color:#1d4ed8; padding:.35rem .75rem; border-radius:7px; cursor:pointer; font-size:.75rem; font-weight:600; width:100%; justify-content:center; }
.rc-far-obs-link:hover { background:#dbeafe; }

/* Toast & Spinner */
.rc-toast { position:fixed; bottom:1rem; right:1rem; display:flex; align-items:center; gap:.5rem; padding:.5rem 1rem; border-radius:8px; font-size:.75rem; z-index:5000; box-shadow:0 4px 12px rgba(0,0,0,.15); }
.rc-toast--success{background:#065f46;color:white;} .rc-toast--error{background:#dc2626;color:white;}
.rc-toast__x { background:none; border:none; color:white; opacity:.7; cursor:pointer; margin-left:.5rem; }
.rc-spin { display:inline-block; width:.8rem; height:.8rem; border:2px solid rgba(255,255,255,.3); border-top-color:white; border-radius:50%; animation:spin .7s linear infinite; }
.rc-spin--xs { width:.55rem; height:.55rem; border-color:rgba(100,116,139,.3); border-top-color:#64748b; }
@keyframes spin { to{transform:rotate(360deg);} }

/* Transitions */
.om-fade-enter-active,.om-fade-leave-active { transition:opacity .2s; }
.om-fade-enter-from,.om-fade-leave-to { opacity:0; }
.toast-pop-enter-active,.toast-pop-leave-active { transition:all .2s; }
.toast-pop-enter-from,.toast-pop-leave-to { opacity:0; transform:translateY(10px); }

/* Responsive */
@media (max-width:900px) {
  .rc-grid--3 { grid-template-columns:1fr 1fr; }
  .rc-signatures-grid { grid-template-columns:1fr; }
  .sl--cover,.sl--concl { grid-template-columns:1fr; }
  .sl-far-body { grid-template-columns:1fr; }
  .rc-frap-row { grid-template-columns:60px 88px 1fr; }
  .rc-frap-row__reco { display:none; }
  .pres-sidebar { width:150px; }
}
@media (max-width:600px) {
  .rc-topbar { flex-direction:column; align-items:flex-start; }
  .rc-tabs { overflow-x:auto; }
  .rc-grid--3 { grid-column:1fr; }
  .rc-odj-add-wrap { flex-direction:column; align-items:flex-start; }
  .pres-sidebar { display:none; }
}
</style> 