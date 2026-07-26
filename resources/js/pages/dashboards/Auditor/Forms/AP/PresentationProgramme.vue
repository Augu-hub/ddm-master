<template>
  <VerticalLayoutAudit>
    <div class="app-shell">

      <!-- ══ HEADER ══ -->
      <header class="app-header" :style="`--fc:${mc};--fcl:${mc}18;--fcm:${mc}35`">
        <div class="app-hrow">
          <a :href="props.backUrl" class="app-back" title="Retour aux phases"><i class="ti ti-arrow-left"></i></a>
          <div class="app-hinfo">
            <div class="app-chips">
              <code class="app-code" :style="`color:${mc};background:${mc}15;border-color:${mc}30`">
                {{ mission?.code_mission ?? '—' }}
              </code>
              <span v-if="form.validation_status" class="app-vst" :class="`avs-${form.validation_status}`">
                <i :class="vstIcon(form.validation_status)"></i>{{ vstLbl(form.validation_status) }}
              </span>
              <span class="app-type" :style="`color:${mc};background:${mc}12`">
                <i class="ti ti-file-analytics"></i> PC · {{ mission?.audit_type_label || 'Audit de Performance' }}
              </span>
              <span v-if="props.auditorRole" class="app-role" :class="`rc-${props.auditorRole}`">
                <i class="ti ti-shield-half"></i>{{ props.auditorRole }}
              </span>
            </div>
            <h1 class="app-htitle">Présentation générale du programme</h1>
            <div class="app-hmeta">
              <span v-if="mission?.entity_name"><i class="ti ti-building"></i>{{ mission.entity_name }}</span>
              <span v-if="mission?.date_debut_fr"><i class="ti ti-calendar"></i>{{ mission.date_debut_fr }} — {{ mission.date_fin_fr }}</span>
              <span v-if="form.code" class="app-fcode"><i class="ti ti-clipboard-text"></i>{{ form.code }}</span>
            </div>
          </div>
        </div>

        <div v-if="form.validation_status === 'validated'" class="app-banner app-banner-lock">
          <i class="ti ti-lock"></i><span>Fiche <strong>validée définitivement</strong> par le DM — lecture seule</span>
        </div>
        <div v-else-if="form.validation_status === 'in_review'" class="app-banner app-banner-review">
          <i class="ti ti-clock"></i><span>Soumise pour validation — en attente DM.<span v-if="canManage"> Vous pouvez valider ou rejeter.</span></span>
        </div>
        <div v-if="props.noMission" class="app-banner app-banner-warn">
          <i class="ti ti-alert-triangle"></i><span>Ouvrez ce formulaire depuis les phases d'une mission.</span>
        </div>
        <div v-if="props.phaseNotStarted" class="app-banner app-banner-warn">
          <i class="ti ti-player-pause"></i><span>Cette phase n'est pas encore démarrée.</span>
        </div>
      </header>

      <!-- ══ BODY ══ -->
      <div v-if="!props.noMission && !props.phaseNotStarted" class="app-body">

        <!-- OBJECTIF GENERAL -->
        <section class="app-card app-obj-gen" :style="`border-left:4px solid ${mc}`">
          <label class="app-og-label"><i class="ti ti-target-arrow" :style="`color:${mc}`"></i> OBJECTIF GÉNÉRAL de la mission</label>
          <textarea v-model="form.objectif_general" rows="2" :disabled="isLocked"
            placeholder="Ex : vérifier la bonne application des procédures et la conformité aux normes prescrites…"></textarea>
        </section>

        <!-- ★ PROGRAMME AUDITÉ (liaison processus de réalisation) -->
        <section class="app-card">
          <div class="app-clabel" :style="`color:${mc};border-color:${mc}25`">
            <i class="ti ti-route"></i> Programme audité
            <em class="app-hint">l'audit de performance porte sur un processus de réalisation (= programme)</em>
          </div>
          <div class="app-cbody app-prog-row">
            <div class="app-field app-field-prog">
              <label>Processus / Programme <b>*</b></label>
              <select v-model="form.process_id" :disabled="isLocked">
                <option :value="null">— Sélectionner le programme —</option>
                <option v-for="p in (props.programmes as any[])" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
            </div>
            <!-- Objectifs du programme depuis la BASE -->
            <div class="app-objbase">
              <span class="app-objbase-t"><i class="ti ti-database"></i> Objectifs du programme (base)
                <em>{{ objectifsDuProcessus.length }}</em></span>
              <div v-if="!objectifsDuProcessus.length" class="app-objbase-empty">
                Aucun objectif saisi dans le module Processus pour ce programme.
              </div>
              <div v-for="o in objectifsDuProcessus" :key="o.id" class="app-objbase-item">
                <span class="app-objbase-type" :class="o.type === 'strategique' ? 'obt-strat' : 'obt-oper'">
                  {{ o.type === 'strategique' ? 'STRAT' : 'OPÉR' }}
                </span>
                <span class="app-objbase-name">{{ o.name }}</span>
                <span v-if="o.kpi" class="app-objbase-kpi"><i class="ti ti-chart-line"></i>{{ o.kpi }}</span>
              </div>
            </div>
          </div>
        </section>

        <!-- RUBRIQUE 1 -->
        <section class="app-card">
          <div class="app-rub" :style="`--rc:${mc}`"><span class="app-rub-n">1.</span> Présentation générale du programme</div>
          <div class="app-cbody">
            <div class="app-srow"><label>Mandats</label>
              <textarea v-model="form.mandats" rows="2" :disabled="isLocked" placeholder="Environnement, cadre du programme…"></textarea></div>
            <div class="app-srow"><label>Missions</label>
              <textarea v-model="form.missions_prog" rows="2" :disabled="isLocked" placeholder="Mission du programme…"></textarea></div>
            <div class="app-srow"><label>Objectifs</label>
              <textarea v-model="form.objectifs_prog" rows="2" :disabled="isLocked" placeholder="Objectifs chiffrés du programme…"></textarea></div>
          </div>
        </section>

        <!-- RUBRIQUE 2 -->
        <section class="app-card">
          <div class="app-rub" :style="`--rc:${mc}`"><span class="app-rub-n">2.</span> Résultats escomptés de l'entité par rapport au programme</div>
          <div class="app-cbody">
            <div class="app-srow"><label>En matière d'économie</label>
              <textarea v-model="form.res_economie" rows="2" :disabled="isLocked"></textarea></div>
            <div class="app-srow"><label>En matière d'efficacité</label>
              <textarea v-model="form.res_efficacite" rows="2" :disabled="isLocked"></textarea></div>
            <div class="app-srow"><label>En matière d'efficience</label>
              <textarea v-model="form.res_efficience" rows="2" :disabled="isLocked"></textarea></div>
            <div class="app-srow"><label>En matière de qualité de service</label>
              <textarea v-model="form.res_qualite" rows="2" :disabled="isLocked"></textarea></div>
          </div>
        </section>

        <!-- RUBRIQUE 3 · STRUCTURATION EN ACTIONS -->
        <section class="app-card">
          <div class="app-rub" :style="`--rc:${mc}`">
            <span class="app-rub-n">3.</span> Structuration du programme en actions
            <span class="app-cnt">{{ activites.length }} activité(s)</span>
          </div>
          <div class="app-cbody">
            <p v-if="!form.id" class="app-need-save">
              <i class="ti ti-info-circle"></i> Enregistrez d'abord la fiche pour ajouter des activités.
            </p>

            <div v-for="act in activites" :key="act.id" class="app-act">
              <div class="app-act-head">
                <button class="app-act-toggle" @click="toggleAct(act.id)">
                  <i :class="openActs.has(act.id) ? 'ti ti-chevron-down' : 'ti ti-chevron-right'"></i>
                </button>
                <code class="app-act-code" :style="`color:${mc};background:${mc}12`">{{ act.code }}</code>
                <span class="app-act-title">{{ act.intitule }}</span>
                <span v-if="act.quantite" class="app-act-qte">Qté : {{ act.quantite }}</span>
                <span v-if="act.budget_global" class="app-act-budget">{{ fmtMoney(act.budget_global) }}</span>
                <span class="app-act-stats">{{ (act.objectifs ?? []).length }} obj. · {{ nbIndicateurs(act) }} ind.</span>
                <button v-if="!isLocked" class="app-mini" title="Fiche activité" @click="openActivite(act)"><i class="ti ti-id"></i></button>
                <button v-if="!isLocked" class="app-mini app-mini-del" title="Supprimer" @click="removeActivite(act)"><i class="ti ti-trash"></i></button>
              </div>

              <div v-show="openActs.has(act.id)" class="app-act-body">
                <!-- Objectifs -->
                <div v-for="obj in act.objectifs" :key="obj.id" class="app-obj">
                  <div class="app-obj-head">
                    <code class="app-obj-code">{{ obj.code }}</code>
                    <span class="app-obj-lib">{{ obj.libelle }}</span>
                    <span v-if="obj.source_name" class="app-obj-src" :title="`Lié à l'objectif base : ${obj.source_name}`">
                      <i class="ti ti-link"></i>{{ obj.source_name }}
                    </span>
                    <button v-if="!isLocked" class="app-mini app-mini-del" title="Supprimer l'objectif" @click="removeObjectif(obj)"><i class="ti ti-trash"></i></button>
                  </div>
                  <!-- Indicateurs -->
                  <div class="app-inds">
                    <span v-for="ind in obj.indicateurs" :key="ind.id" class="app-ind-chip"
                      :title="ind.intitule" @click="openIndicateur(obj, ind)">
                      <i class="ti ti-chart-line"></i>{{ ind.code }} · {{ ind.intitule }}
                      <i v-if="ind.sens_evolution" :class="ind.sens_evolution === 'hausse' ? 'ti ti-trending-up' : 'ti ti-trending-down'"></i>
                    </span>
                    <button v-if="!isLocked" class="app-ind-add" :style="`color:${mc};border-color:${mc}35`"
                      @click="openIndicateur(obj, null)">
                      <i class="ti ti-plus"></i> Indicateur
                    </button>
                  </div>
                </div>

                <!-- Ajouter un objectif -->
                <div v-if="!isLocked" class="app-obj-new">
                  <input v-model="newObj[act.id].libelle" type="text" placeholder="Libellé du nouvel objectif…" />
                  <select v-model="newObj[act.id].source_objectif_id" title="Lier à un objectif du programme (base)">
                    <option :value="null">— sans liaison —</option>
                    <option v-for="o in objectifsDuProcessus" :key="o.id" :value="o.id">🔗 {{ o.name }}</option>
                  </select>
                  <button class="app-btn-sm" :style="`background:${mc}`" :disabled="!newObj[act.id].libelle?.trim() || busy"
                    @click="addObjectif(act)">
                    <i class="ti ti-plus"></i> Objectif
                  </button>
                </div>
              </div>
            </div>

            <button v-if="form.id && !isLocked" class="app-add" :style="`color:${mc};border-color:${mc}35`" @click="openActivite(null)">
              <i class="ti ti-plus"></i> Insérer une activité
            </button>
          </div>
        </section>

        <!-- RUBRIQUE 4 -->
        <section class="app-card">
          <div class="app-rub" :style="`--rc:${mc}`"><span class="app-rub-n">4.</span> La gouvernance du programme</div>
          <div class="app-cbody">
            <div class="app-srow"><label>L'organisation de la gouvernance</label>
              <textarea v-model="form.gouv_organisation" rows="2" :disabled="isLocked"></textarea></div>
            <div class="app-srow"><label>Les relations intérieures du programme</label>
              <textarea v-model="form.gouv_rel_interieures" rows="2" :disabled="isLocked"></textarea></div>
            <div class="app-srow"><label>Les relations extérieures du programme</label>
              <textarea v-model="form.gouv_rel_exterieures" rows="2" :disabled="isLocked"></textarea></div>
          </div>
        </section>

        <!-- RUBRIQUE 5 -->
        <section class="app-card">
          <div class="app-rub" :style="`--rc:${mc}`"><span class="app-rub-n">5.</span> Les sources d'information du programme</div>
          <div class="app-cbody">
            <div class="app-srow"><label>Législation, interventions parlementaires, déclarations ministérielles et décisions gouvernementales</label>
              <textarea v-model="form.src_legislation" rows="2" :disabled="isLocked"></textarea></div>
            <div class="app-srow"><label>Rapports d'audits antérieurs</label>
              <textarea v-model="form.src_rapports_anterieurs" rows="2" :disabled="isLocked"></textarea></div>
          </div>
        </section>

        <!-- RUBRIQUE 6 -->
        <section class="app-card">
          <div class="app-rub" :style="`--rc:${mc}`"><span class="app-rub-n">6.</span> Les ressources du programme</div>
          <div class="app-cbody">
            <div class="app-srow"><label>Ressources humaines</label>
              <textarea v-model="form.ress_humaines" rows="2" :disabled="isLocked"></textarea></div>
            <div class="app-srow"><label>Ressources financières</label>
              <textarea v-model="form.ress_financieres" rows="2" :disabled="isLocked"></textarea></div>
            <div class="app-srow"><label>Ressources techniques</label>
              <textarea v-model="form.ress_techniques" rows="2" :disabled="isLocked"></textarea></div>
          </div>
        </section>

        <!-- RUBRIQUES LIBRES -->
        <section class="app-card">
          <div class="app-rub" :style="`--rc:${mc}`">
            <span class="app-rub-n">{{ 7 }}<span v-if="form.rubriques_extra.length">–{{ 6 + form.rubriques_extra.length }}</span>.</span>
            Rubriques complémentaires
          </div>
          <div class="app-cbody">
            <div v-for="(rub, ri) in form.rubriques_extra" :key="ri" class="app-xrub">
              <div class="app-xrub-head">
                <span class="app-rub-n2">{{ 7 + ri }}.</span>
                <input v-model="rub.titre" type="text" placeholder="Titre de la rubrique…" :disabled="isLocked" />
                <button v-if="!isLocked" class="app-mini app-mini-del" @click="form.rubriques_extra.splice(ri, 1)"><i class="ti ti-trash"></i></button>
              </div>
              <div v-for="(s, si) in rub.sous" :key="si" class="app-xsub">
                <input v-model="s.titre" type="text" class="app-xsub-t" placeholder="Sous-rubrique…" :disabled="isLocked" />
                <textarea v-model="s.contenu" rows="2" placeholder="Zone de saisie…" :disabled="isLocked"></textarea>
                <button v-if="!isLocked" class="app-mini app-mini-del" @click="rub.sous.splice(si, 1)"><i class="ti ti-x"></i></button>
              </div>
              <button v-if="!isLocked" class="app-add-sub" @click="rub.sous.push({ titre: '', contenu: '' })">
                <i class="ti ti-plus"></i> insérer une sous-rubrique
              </button>
            </div>
            <button v-if="!isLocked" class="app-add" :style="`color:${mc};border-color:${mc}35`"
              @click="form.rubriques_extra.push({ titre: '', sous: [{ titre: '', contenu: '' }] })">
              <i class="ti ti-plus"></i> insérer une rubrique
            </button>
          </div>
        </section>

        <!-- POINT FINANCIER & PHYSIQUE -->
        <section class="app-card">
          <div class="app-clabel" :style="`color:${mc};border-color:${mc}25`">
            <i class="ti ti-coin"></i> Point financier &amp; point physique
            <button v-if="form.id && !isLocked" class="app-btn-sm app-fin-save" :style="`background:${mc}`"
              :disabled="busy" @click="saveFinancier">
              <i class="ti ti-device-floppy"></i> Enregistrer le point
            </button>
          </div>
          <div class="app-cbody">
            <p v-if="!form.id" class="app-need-save"><i class="ti ti-info-circle"></i> Enregistrez d'abord la fiche.</p>
            <div v-else class="app-fin-wrap">
              <table class="app-fin">
                <thead>
                  <tr>
                    <th>Année</th><th>Dotation</th><th>Exécution</th>
                    <th>Taux d'exécut°</th><th>Point physique %</th><th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(r, i) in financier" :key="i">
                    <td><input v-model.number="r.annee" type="number" min="1990" max="2100" :disabled="isLocked" class="app-fin-annee" /></td>
                    <td><input v-model.number="r.dotation" type="number" step="0.01" :disabled="isLocked" /></td>
                    <td><input v-model.number="r.execution" type="number" step="0.01" :disabled="isLocked" /></td>
                    <td class="app-fin-taux" :style="`color:${mc}`">{{ tauxExec(r) }}</td>
                    <td><input v-model.number="r.point_physique" type="number" step="0.1" min="0" max="100" :disabled="isLocked" /></td>
                    <td><button v-if="!isLocked" class="app-mini app-mini-del" @click="financier.splice(i, 1)"><i class="ti ti-trash"></i></button></td>
                  </tr>
                  <tr class="app-fin-total">
                    <td><strong>Total</strong></td>
                    <td><strong>{{ fmtMoney(totalFin.dotation) }}</strong></td>
                    <td><strong>{{ fmtMoney(totalFin.execution) }}</strong></td>
                    <td :style="`color:${mc}`"><strong>{{ totalFin.taux }}</strong></td>
                    <td colspan="2"></td>
                  </tr>
                </tbody>
              </table>
              <button v-if="!isLocked" class="app-add" :style="`color:${mc};border-color:${mc}35`" @click="addYear">
                <i class="ti ti-plus"></i> Ajouter une année
              </button>
            </div>
          </div>
        </section>

        <!-- FAIT PAR / REVUE PAR -->
        <section class="app-card">
          <div class="app-cbody app-sign-row">
            <div class="app-field"><label>Fait par</label>
              <input v-model="form.fait_par" type="text" list="app-auditeurs" :disabled="isLocked" /></div>
            <div class="app-field"><label>Revue par</label>
              <input v-model="form.revue_par" type="text" list="app-auditeurs" :disabled="isLocked" /></div>
            <datalist id="app-auditeurs">
              <option v-for="a in (props.auditeurs as any[])" :key="a.id" :value="`${a.nom ?? ''} ${a.prenom ?? ''}`.trim()">{{ a.grade }}</option>
            </datalist>
          </div>
        </section>

        <!-- BARRE D'ACTIONS -->
        <div class="app-actions">
          <div class="app-actions-l"><span v-if="form.code" class="app-fcode"><i class="ti ti-clipboard-text"></i>{{ form.code }}</span></div>
          <div class="app-actions-r">
            <button v-if="!isLocked" class="app-btn app-btn-save" :style="`background:${mc}`" :disabled="busy" @click="saveFiche">
              <i :class="busy ? 'ti ti-loader-2 app-spin' : 'ti ti-device-floppy'"></i>
              {{ form.id ? 'Enregistrer la fiche' : 'Créer la fiche' }}
            </button>
            <button v-if="form.id && form.validation_status === 'draft'" class="app-btn app-btn-submit" :disabled="busy" @click="soumettre">
              <i class="ti ti-send"></i> Soumettre au DM
            </button>
            <template v-if="canManage && form.validation_status === 'in_review'">
              <button class="app-btn app-btn-reject" :disabled="busy" @click="promptReject"><i class="ti ti-x"></i> Rejeter</button>
              <button class="app-btn app-btn-validate" :disabled="busy" @click="valider('validate')"><i class="ti ti-shield-check"></i> Valider</button>
            </template>
          </div>
        </div>
      </div>

      <!-- ══ MODALE FICHE ACTIVITÉ ══ -->
      <Teleport to="body">
        <transition name="app-fade">
          <div v-if="actModal.show" class="app-ovl" @click.self="actModal.show = false">
            <div class="app-modal app-modal-lg">
              <div class="app-m-head" :style="`border-color:${mc}30`">
                <i class="ti ti-id" :style="`color:${mc}`"></i>
                <strong>Fiche activité</strong>
                <code v-if="actModal.data.code" :style="`color:${mc}`">{{ actModal.data.code }}</code>
                <button class="app-m-x" @click="actModal.show = false"><i class="ti ti-x"></i></button>
              </div>
              <div class="app-m-body">
                <div class="app-field"><label>Intitulé activité <b>*</b></label>
                  <input v-model="actModal.data.intitule" type="text" /></div>
                <div class="app-field"><label>Résultat activité</label>
                  <textarea v-model="actModal.data.resultat" rows="2" placeholder="Ex : La prévention multiple est améliorée"></textarea></div>
                <div class="app-grid2">
                  <div class="app-field"><label>Responsable de l'activité</label>
                    <input v-model="actModal.data.responsable" type="text" /></div>
                  <div class="app-field"><label>Quantité</label>
                    <input v-model="actModal.data.quantite" type="text" /></div>
                </div>
                <div class="app-field"><label>Autres membres de l'équipe</label>
                  <div class="app-chips-edit">
                    <span v-for="(m, i) in actModal.data.membres" :key="i" class="app-chip">
                      {{ m }} <i class="ti ti-x" @click="actModal.data.membres.splice(i, 1)"></i>
                    </span>
                    <input v-model="actModal.newMembre" type="text" placeholder="Nom + Entrée"
                      @keydown.enter.prevent="pushMembre" />
                  </div>
                </div>
                <div class="app-grid2">
                  <div class="app-field"><label>Budget global</label>
                    <input v-model.number="actModal.data.budget_global" type="number" step="0.01" /></div>
                  <div class="app-field"><label>Budget de l'exercice</label>
                    <input v-model.number="actModal.data.budget_exercice" type="number" step="0.01" /></div>
                </div>
                <div class="app-field"><label>Commentaires</label>
                  <textarea v-model="actModal.data.commentaires" rows="2"></textarea></div>

                <!-- Extrants EFE -->
                <div class="app-ext-label" :style="`color:${mc}`"><i class="ti ti-arrows-exchange"></i> Éléments extrants — Std/Ref EFE · Entrées · Sorties</div>
                <div class="app-ext-wrap">
                  <table class="app-ext">
                    <thead>
                      <tr>
                        <th rowspan="2">Élément</th>
                        <th colspan="2">Std/Ref EFE</th>
                        <th colspan="2">Entrées</th>
                        <th colspan="2">Sorties</th>
                        <th rowspan="2">Obs</th><th rowspan="2"></th>
                      </tr>
                      <tr><th>Taux</th><th>Valeur</th><th>Nombre</th><th>Valeur</th><th>Nombre</th><th>Valeur</th></tr>
                    </thead>
                    <tbody>
                      <tr v-for="(e, i) in actModal.data.extrants" :key="i">
                        <td><input v-model="e.element" type="text" list="app-elements" /></td>
                        <td><input v-model="e.taux_efe" type="text" /></td>
                        <td><input v-model="e.valeur_efe" type="text" /></td>
                        <td><input v-model.number="e.entrees_nombre" type="number" step="0.01" /></td>
                        <td><input v-model.number="e.entrees_valeur" type="number" step="0.01" /></td>
                        <td><input v-model.number="e.sorties_nombre" type="number" step="0.01" /></td>
                        <td><input v-model.number="e.sorties_valeur" type="number" step="0.01" /></td>
                        <td><input v-model="e.obs" type="text" /></td>
                        <td><button class="app-mini app-mini-del" @click="actModal.data.extrants.splice(i, 1)"><i class="ti ti-trash"></i></button></td>
                      </tr>
                    </tbody>
                  </table>
                  <datalist id="app-elements">
                    <option v-for="el in ELEMENTS" :key="el" :value="el" />
                  </datalist>
                  <button class="app-add-sub" @click="actModal.data.extrants.push(blankExtrant())">
                    <i class="ti ti-plus"></i> Ajouter un élément
                  </button>
                </div>
              </div>
              <div class="app-m-foot">
                <button class="app-btn app-btn-ghost" @click="actModal.show = false">Annuler</button>
                <button class="app-btn app-btn-save" :style="`background:${mc}`"
                  :disabled="!actModal.data.intitule?.trim() || busy" @click="saveActivite">
                  <i class="ti ti-device-floppy"></i> Enregistrer l'activité
                </button>
              </div>
            </div>
          </div>
        </transition>
      </Teleport>

      <!-- ══ MODALE FICHE INDICATEUR ══ -->
      <Teleport to="body">
        <transition name="app-fade">
          <div v-if="indModal.show" class="app-ovl" @click.self="indModal.show = false">
            <div class="app-modal app-modal-lg">
              <div class="app-m-head" :style="`border-color:${mc}30`">
                <i class="ti ti-chart-line" :style="`color:${mc}`"></i>
                <strong>Fiche indicateur</strong>
                <code v-if="indModal.data.code" :style="`color:${mc}`">{{ indModal.data.code }}</code>
                <span class="app-m-sub">{{ indModal.objectifLabel }}</span>
                <button class="app-m-x" @click="indModal.show = false"><i class="ti ti-x"></i></button>
              </div>
              <div class="app-m-body">
                <!-- ★ Indicateurs suggérés (base + standards 3E) -->
                <div v-if="indModal.suggestions.length" class="app-sugg-box" :style="`border-color:${mc}30`">
                  <span class="app-sugg-t" :style="`color:${mc}`"><i class="ti ti-bulb"></i> Indicateurs suggérés</span>
                  <div class="app-sugg-list">
                    <button v-for="(s, i) in indModal.suggestions" :key="i" class="app-sugg-chip2"
                      :title="s.source" @click="applySuggestion(s)">
                      <i class="ti ti-plus"></i>{{ s.intitule }}
                      <em v-if="s.unite">· {{ s.unite }}</em>
                    </button>
                  </div>
                </div>
                <div class="app-field"><label>Intitulé indicateur <b>*</b></label>
                  <input v-model="indModal.data.intitule" type="text" /></div>
                <div class="app-grid2">
                  <div class="app-field"><label>Service utilisateur</label>
                    <input v-model="indModal.data.service_utilisateur" type="text" /></div>
                  <div class="app-field"><label>Unité de mesure</label>
                    <input v-model="indModal.data.unite_mesure" type="text" placeholder="%, Montant, Nombre…" /></div>
                  <div class="app-field"><label>Périodicité de la mesure</label>
                    <input v-model="indModal.data.periodicite_mesure" type="text" list="app-periodes" /></div>
                  <div class="app-field"><label>Périodicité de l'indicateur</label>
                    <input v-model="indModal.data.periodicite_indicateur" type="text" list="app-periodes" placeholder="Fréquence de calcul et de parution" /></div>
                </div>
                <datalist id="app-periodes">
                  <option v-for="p in PERIODES" :key="p" :value="p" />
                </datalist>

                <div class="app-field">
                  <label>Dernières valeurs connues (3 dernières + années)</label>
                  <div class="app-vals">
                    <div v-for="(v, i) in indModal.data.dernieres_valeurs" :key="i" class="app-val">
                      <input v-model.number="v.annee" type="number" placeholder="Année" min="1990" max="2100" />
                      <input v-model="v.valeur" type="text" placeholder="Valeur" />
                      <button class="app-mini app-mini-del" @click="indModal.data.dernieres_valeurs.splice(i, 1)"><i class="ti ti-x"></i></button>
                    </div>
                    <button v-if="indModal.data.dernieres_valeurs.length < 5" class="app-add-sub"
                      @click="indModal.data.dernieres_valeurs.push({ annee: null, valeur: '' })">
                      <i class="ti ti-plus"></i> valeur
                    </button>
                  </div>
                </div>

                <div class="app-field"><label>Nature des données de base</label>
                  <textarea v-model="indModal.data.nature_donnees" rows="2" placeholder="Ratio : distinguer numérateur / dénominateur…"></textarea></div>
                <div class="app-grid2">
                  <div class="app-field"><label>Mode de collecte des données</label>
                    <input v-model="indModal.data.mode_collecte" type="text" list="app-collecte" /></div>
                  <div class="app-field"><label>Service resp. de la synthèse</label>
                    <input v-model="indModal.data.service_synthese" type="text" /></div>
                </div>
                <datalist id="app-collecte">
                  <option v-for="c in COLLECTES" :key="c" :value="c" />
                </datalist>
                <div class="app-field"><label>Structure de validation de l'indicateur</label>
                  <input v-model="indModal.data.structure_validation" type="text" /></div>
                <div class="app-field"><label>Mode de calcul</label>
                  <textarea v-model="indModal.data.mode_calcul" rows="2"></textarea></div>
                <div class="app-field"><label>Modalités d'interprétation</label>
                  <textarea v-model="indModal.data.interpretation" rows="2"></textarea></div>

                <div class="app-field"><label>Sens d'évolution souhaité</label>
                  <div class="app-sens">
                    <button :class="{ on: indModal.data.sens_evolution === 'hausse' }" @click="indModal.data.sens_evolution = 'hausse'">
                      <i class="ti ti-trending-up"></i> À la hausse
                    </button>
                    <button :class="{ on: indModal.data.sens_evolution === 'baisse' }" @click="indModal.data.sens_evolution = 'baisse'">
                      <i class="ti ti-trending-down"></i> À la baisse
                    </button>
                  </div>
                </div>

                <div class="app-field"><label>Limites et biais connus</label>
                  <textarea v-model="indModal.data.limites" rows="2"></textarea></div>
                <div class="app-grid2">
                  <div class="app-field"><label>Date de livraison de l'indicateur</label>
                    <input v-model="indModal.data.date_livraison" type="text" /></div>
                </div>
                <div class="app-field"><label>Plan d'amélioration / construction</label>
                  <textarea v-model="indModal.data.plan_amelioration" rows="2"></textarea></div>
                <div class="app-field"><label>Commentaires</label>
                  <textarea v-model="indModal.data.commentaires" rows="2"></textarea></div>
              </div>
              <div class="app-m-foot">
                <button v-if="indModal.data.id && !isLocked" class="app-btn app-btn-reject" :disabled="busy" @click="removeIndicateur">
                  <i class="ti ti-trash"></i> Supprimer
                </button>
                <span class="app-m-spacer"></span>
                <button class="app-btn app-btn-ghost" @click="indModal.show = false">Annuler</button>
                <button class="app-btn app-btn-save" :style="`background:${mc}`"
                  :disabled="!indModal.data.intitule?.trim() || busy" @click="saveIndicateur">
                  <i class="ti ti-device-floppy"></i> Enregistrer l'indicateur
                </button>
              </div>
            </div>
          </div>
        </transition>
      </Teleport>

      <!-- ══ TOAST ══ -->
      <Teleport to="body">
        <transition name="app-toastx">
          <div v-if="toast.show" class="app-toast" :class="`app-toast-${toast.type}`">
            <i :class="toast.type === 'success' ? 'ti ti-circle-check' : 'ti ti-circle-x'"></i>{{ toast.msg }}
          </div>
        </transition>
      </Teleport>

    </div>
  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
// ════════════════════════════════════════════════════════════════════
// AP · Présentation générale du programme (formulaire PC)
// Maquettes FranckS : rubriques 1-6 + rubriques libres, structuration en
// actions (AC_01.0n → OB_01.0n.m → IND-nnn), fiche activité (extrants
// EFE), fiche indicateur complète, point financier & physique.
// ★ Liaison : programme = processus de réalisation (processes), objectifs
// liables aux objectifs du processus déjà en base (objectifsBase).
// ════════════════════════════════════════════════════════════════════
import { computed, reactive, ref } from 'vue'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'

const props = defineProps({
  mission:      { type: Object,  default: null },
  assignment:   { type: Object,  default: null },
  auditeurs:    { type: Array,   default: () => [] },
  auditorRole:  { type: String,  default: null },
  record:       { type: Object,  default: null },
  form:         { type: Object,  default: null },
  programmes:   { type: Array,   default: () => [] },   // processes du tenant
  objectifsBase:{ type: Object,  default: () => ({}) }, // objectifs par process_id
  financier:    { type: Array,   default: () => [] },
  activites:    { type: Array,   default: () => [] },
  errors:       { type: Object,  default: () => ({}) },
  noMission:       { type: Boolean, default: false },
  phaseNotStarted: { type: Boolean, default: false },
  missionId:    { type: Number, default: null },
  assignmentId: { type: Number, default: null },
  missionMenu:  { type: Array,  default: () => [] },
  backUrl:      { type: String, default: '' },
  formUrl:      { type: String, default: '' },
  chatBaseUrl:  { type: String, default: '' },
})

const mc = computed<string>(() => {
  const c = (props.mission as any)?.audit_color
  return c && c !== '#000000' && c !== 'null' ? c : '#059669'
})
const canManage = computed(() => ['DM', 'CM'].includes(props.auditorRole ?? ''))

const ELEMENTS  = ['Biens', 'Matériel/équipement', 'Installations', 'Informations', 'Énergie']
const PERIODES  = ['Mensuelle', 'Trimestrielle', 'Semestrielle', 'Annuelle']
const COLLECTES = ['Rapport d\'activité', 'Enquête', 'Système d\'information', 'Relevé manuel', 'Registre']

// ── Fiche ────────────────────────────────────────────────────────────────
const src = (props.record ?? props.form ?? {}) as any
function safeArr(v: any): any[] {
  if (Array.isArray(v)) return v
  if (!v) return []
  try { const p = JSON.parse(v); return Array.isArray(p) ? p : [] } catch { return [] }
}
const form = reactive<Record<string, any>>({
  id: src.id ?? null, code: src.code ?? '',
  process_id: src.process_id ?? null,
  objectif_general: src.objectif_general ?? '',
  mandats: src.mandats ?? '', missions_prog: src.missions_prog ?? '', objectifs_prog: src.objectifs_prog ?? '',
  res_economie: src.res_economie ?? '', res_efficacite: src.res_efficacite ?? '',
  res_efficience: src.res_efficience ?? '', res_qualite: src.res_qualite ?? '',
  gouv_organisation: src.gouv_organisation ?? '', gouv_rel_interieures: src.gouv_rel_interieures ?? '',
  gouv_rel_exterieures: src.gouv_rel_exterieures ?? '',
  src_legislation: src.src_legislation ?? '', src_rapports_anterieurs: src.src_rapports_anterieurs ?? '',
  ress_humaines: src.ress_humaines ?? '', ress_financieres: src.ress_financieres ?? '', ress_techniques: src.ress_techniques ?? '',
  rubriques_extra: safeArr(src.rubriques_extra),
  fait_par: src.fait_par ?? '', revue_par: src.revue_par ?? '',
  validation_status: src.validation_status ?? null,
})

const isLocked = computed(() =>
  form.validation_status === 'validated' ||
  (form.validation_status === 'in_review' && !canManage.value)
)

// ── Liaison base : objectifs du processus sélectionné ────────────────────
const objectifsDuProcessus = computed<any[]>(() => {
  if (!form.process_id) return []
  return ((props.objectifsBase as any)[form.process_id] ?? []) as any[]
})

// ── Enfants ──────────────────────────────────────────────────────────────
const activites = ref<any[]>([...(props.activites as any[])])
const financier = ref<any[]>((props.financier as any[]).map(r => ({ ...r })))
const openActs  = reactive<Set<number>>(new Set((props.activites as any[]).map((a: any) => a.id)))
const newObj    = reactive<Record<number, any>>({})
function ensureNewObj() {
  activites.value.forEach(a => { if (!newObj[a.id]) newObj[a.id] = { libelle: '', source_objectif_id: null } })
}
ensureNewObj()

function toggleAct(id: number) { openActs.has(id) ? openActs.delete(id) : openActs.add(id) }
function nbIndicateurs(act: any): number {
  return (act.objectifs ?? []).reduce((s: number, o: any) => s + (o.indicateurs?.length ?? 0), 0)
}

// ── API ──────────────────────────────────────────────────────────────────
const busy  = ref(false)
async function api(url: string, method: string, body?: object): Promise<any> {
  busy.value = true
  try {
    const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
    const res  = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
      body: body ? JSON.stringify(body) : undefined,
    })
    const json = await res.json()
    if (!res.ok) throw new Error(json?.message ?? json?.error ?? 'Erreur')
    return json
  } finally { busy.value = false }
}

function refreshChildren(json: any) {
  if (json.activites) { activites.value = json.activites; ensureNewObj() }
  if (json.financier) financier.value = json.financier.map((r: any) => ({ ...r }))
}

// ── Fiche : save / soumettre / valider ───────────────────────────────────
async function saveFiche() {
  try {
    const json = await api(props.formUrl, 'POST', {
      mission_id: props.missionId, assignment_id: props.assignmentId,
      ...form, rubriques_extra: form.rubriques_extra,
    })
    if (json.record) {
      form.id = json.record.id
      form.code = json.record.code
      form.validation_status = json.record.validation_status
    }
    showToast('success', 'Fiche programme enregistrée')
  } catch (e: any) { showToast('error', e.message) }
}

async function soumettre() {
  if (!confirm('Soumettre cette fiche pour validation par le DM ?')) return
  try {
    const json = await api(`${props.formUrl}/${form.id}/soumettre`, 'POST', {})
    form.validation_status = json.status
    showToast('success', 'Fiche soumise — en attente DM')
  } catch (e: any) { showToast('error', e.message) }
}

async function valider(action: 'validate' | 'reject', note?: string) {
  try {
    const json = await api(`${props.formUrl}/${form.id}/valider`, 'POST', { action, note })
    form.validation_status = json.status
    showToast('success', action === 'validate' ? 'Fiche validée ✓' : 'Fiche rejetée — repassée en brouillon')
  } catch (e: any) { showToast('error', e.message) }
}
function promptReject() {
  const note = prompt('Motif du rejet (obligatoire) :')
  if (note?.trim()) valider('reject', note)
}

// ── Point financier ──────────────────────────────────────────────────────
function addYear() {
  const last = financier.value.length
    ? Math.max(...financier.value.map(r => Number(r.annee) || 0))
    : new Date().getFullYear() - 1
  financier.value.push({ annee: last + 1, dotation: null, execution: null, point_physique: null })
}
function tauxExec(r: any): string {
  const d = Number(r.dotation), e = Number(r.execution)
  return d > 0 && isFinite(e) ? Math.round((e / d) * 100) + '%' : '—'
}
const totalFin = computed(() => {
  const dotation  = financier.value.reduce((s, r) => s + (Number(r.dotation) || 0), 0)
  const execution = financier.value.reduce((s, r) => s + (Number(r.execution) || 0), 0)
  return { dotation, execution, taux: dotation > 0 ? Math.round((execution / dotation) * 100) + '%' : '—' }
})
async function saveFinancier() {
  try {
    const json = await api(`${props.formUrl}/financier`, 'POST', {
      programme_id: form.id,
      rows: financier.value.filter(r => r.annee),
    })
    refreshChildren(json)
    showToast('success', 'Point financier enregistré')
  } catch (e: any) { showToast('error', e.message) }
}

// ── Activités ────────────────────────────────────────────────────────────
function blankExtrant() {
  return { element: '', taux_efe: '', valeur_efe: '', entrees_nombre: null, entrees_valeur: null, sorties_nombre: null, sorties_valeur: null, obs: '' }
}
const actModal = reactive<{ show: boolean; data: any; newMembre: string }>({ show: false, data: {}, newMembre: '' })

function openActivite(act: any | null) {
  actModal.data = act
    ? { ...act, membres: [...(act.membres ?? [])], extrants: (act.extrants ?? []).map((e: any) => ({ ...e })) }
    : { id: null, code: '', intitule: '', resultat: '', responsable: '', membres: [], quantite: '',
        budget_global: null, budget_exercice: null, commentaires: '',
        extrants: ELEMENTS.slice(0, 2).map(el => ({ ...blankExtrant(), element: el })) }
  actModal.newMembre = ''
  actModal.show = true
}
function pushMembre() {
  const v = actModal.newMembre.trim()
  if (v) { actModal.data.membres.push(v); actModal.newMembre = '' }
}
async function saveActivite() {
  try {
    const json = await api(`${props.formUrl}/activites`, 'POST', { programme_id: form.id, ...actModal.data })
    refreshChildren(json)
    actModal.show = false
    showToast('success', 'Activité enregistrée')
  } catch (e: any) { showToast('error', e.message) }
}
async function removeActivite(act: any) {
  if (!confirm(`Supprimer l'activité ${act.code} (objectifs et indicateurs inclus) ?`)) return
  try {
    const json = await api(`${props.formUrl}/activites/${act.id}?programme_id=${form.id}`, 'DELETE', { programme_id: form.id })
    refreshChildren(json)
    showToast('success', 'Activité supprimée')
  } catch (e: any) { showToast('error', e.message) }
}

// ── Objectifs ────────────────────────────────────────────────────────────
async function addObjectif(act: any) {
  const d = newObj[act.id]
  try {
    const json = await api(`${props.formUrl}/objectifs`, 'POST', {
      programme_id: form.id, activite_id: act.id,
      libelle: d.libelle, source_objectif_id: d.source_objectif_id,
    })
    refreshChildren(json)
    newObj[act.id] = { libelle: '', source_objectif_id: null }
    showToast('success', 'Objectif ajouté')
  } catch (e: any) { showToast('error', e.message) }
}
async function removeObjectif(obj: any) {
  if (!confirm(`Supprimer l'objectif ${obj.code} (indicateurs inclus) ?`)) return
  try {
    const json = await api(`${props.formUrl}/objectifs/${obj.id}`, 'DELETE', { programme_id: form.id })
    refreshChildren(json)
  } catch (e: any) { showToast('error', e.message) }
}

// ── Indicateurs ──────────────────────────────────────────────────────────
const indModal = reactive<{ show: boolean; objectifId: number | null; objectifLabel: string; data: any; suggestions: any[] }>(
  { show: false, objectifId: null, objectifLabel: '', data: {}, suggestions: [] }
)
function openIndicateur(obj: any, ind: any | null) {
  indModal.objectifId = obj.id
  indModal.objectifLabel = `${obj.code} — ${obj.libelle}`
  indModal.data = ind
    ? { ...ind, dernieres_valeurs: (ind.dernieres_valeurs ?? []).map((v: any) => ({ ...v })) }
    : { id: null, code: '', intitule: '', service_utilisateur: '', unite_mesure: '',
        periodicite_mesure: '', periodicite_indicateur: '',
        dernieres_valeurs: [{ annee: null, valeur: '' }, { annee: null, valeur: '' }, { annee: null, valeur: '' }],
        nature_donnees: '', mode_collecte: '', service_synthese: '', structure_validation: '',
        mode_calcul: '', interpretation: '', sens_evolution: null, limites: '',
        date_livraison: '', plan_amelioration: '', commentaires: '' }
  indModal.suggestions = []
  indModal.show = true
  // ★ Charger les indicateurs suggérés depuis la base (objectif lié + libellé)
  if (!ind) loadSuggestions(obj)
}

async function loadSuggestions(obj: any) {
  try {
    const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
    const res = await fetch(`${props.formUrl}/suggest-indicateurs`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
      body: JSON.stringify({ source_objectif_id: obj.source_objectif_id ?? null, objectif_libelle: obj.libelle }),
    })
    const json = await res.json()
    if (res.ok && json.indicateurs) indModal.suggestions = json.indicateurs.slice(0, 8)
  } catch { /* suggestions best-effort */ }
}

function applySuggestion(s: any) {
  indModal.data.intitule = s.intitule
  if (s.unite && !indModal.data.unite_mesure) indModal.data.unite_mesure = s.unite
  if (s.sens && !indModal.data.sens_evolution) indModal.data.sens_evolution = s.sens
}
async function saveIndicateur() {
  try {
    const json = await api(`${props.formUrl}/indicateurs`, 'POST', {
      programme_id: form.id, objectif_id: indModal.objectifId, ...indModal.data,
      dernieres_valeurs: indModal.data.dernieres_valeurs.filter((v: any) => v.annee || v.valeur),
    })
    refreshChildren(json)
    indModal.show = false
    showToast('success', 'Indicateur enregistré')
  } catch (e: any) { showToast('error', e.message) }
}
async function removeIndicateur() {
  if (!confirm(`Supprimer l'indicateur ${indModal.data.code} ?`)) return
  try {
    const json = await api(`${props.formUrl}/indicateurs/${indModal.data.id}`, 'DELETE', { programme_id: form.id })
    refreshChildren(json)
    indModal.show = false
  } catch (e: any) { showToast('error', e.message) }
}

// ── Helpers ──────────────────────────────────────────────────────────────
function fmtMoney(v: any): string {
  const n = Number(v)
  return isFinite(n) && n !== 0 ? new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(n) : '—'
}
function vstLbl(s: string) {
  return ({ draft: 'Brouillon', in_review: 'Soumis — en revue', validated: 'Validé' } as any)[s] ?? s
}
function vstIcon(s: string) {
  return ({ draft: 'ti ti-pencil', in_review: 'ti ti-clock', validated: 'ti ti-lock' } as any)[s] ?? 'ti ti-pencil'
}

const toast = ref({ show: false, type: 'success', msg: '' })
let tt: ReturnType<typeof setTimeout>
function showToast(type: string, msg: string) {
  toast.value = { show: true, type, msg }
  clearTimeout(tt)
  tt = setTimeout(() => { toast.value.show = false }, 4000)
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap');
* { box-sizing: border-box; }

.app-shell { font-family: 'Plus Jakarta Sans', sans-serif; min-height: calc(100vh - 68px); background: #f4f7f6; color: #1e293b; }

/* HEADER */
.app-header { position: sticky; top: 0; z-index: 30; background: #fff; border-bottom: 1px solid #e2e8f0; box-shadow: 0 1px 8px rgba(15,23,42,.05); }
.app-hrow { display: flex; align-items: center; gap: 14px; padding: 13px 22px 10px; }
.app-back { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
  background: var(--fcl); color: var(--fc); border: 1px solid var(--fcm); text-decoration: none; flex-shrink: 0; transition: all .14s; }
.app-back:hover { background: var(--fc); color: #fff; }
.app-hinfo { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 4px; }
.app-chips { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.app-code { font-family: 'JetBrains Mono', monospace; font-size: .68rem; font-weight: 700; padding: 2px 8px; border-radius: 5px; border: 1px solid; }
.app-type { display: inline-flex; align-items: center; gap: 4px; font-size: .62rem; font-weight: 800; padding: 2px 8px; border-radius: 12px; }
.app-role { display: inline-flex; align-items: center; gap: 3px; font-size: .6rem; font-weight: 800; padding: 2px 7px; border-radius: 10px; }
.rc-DM { background: #fef3c7; color: #b45309; } .rc-CM { background: #dbeafe; color: #1d4ed8; }
.rc-AS { background: #d1fae5; color: #047857; } .rc-AJ { background: #ede9fe; color: #6d28d9; }
.app-vst { display: inline-flex; align-items: center; gap: 4px; font-size: .6rem; font-weight: 800; padding: 2px 8px; border-radius: 12px; text-transform: uppercase; }
.avs-draft { background: #f1f5f9; color: #64748b; } .avs-in_review { background: #fef3c7; color: #b45309; } .avs-validated { background: #d1fae5; color: #047857; }
.app-htitle { margin: 0; font-size: 1.2rem; font-weight: 800; color: #0f172a; letter-spacing: -.02em; }
.app-hmeta { display: flex; flex-wrap: wrap; gap: 12px; }
.app-hmeta span { display: inline-flex; align-items: center; gap: 4px; font-size: .7rem; color: #64748b; }
.app-fcode { font-family: 'JetBrains Mono', monospace; }
.app-banner { display: flex; align-items: center; gap: 8px; padding: 8px 22px; font-size: .74rem; font-weight: 600; border-top: 1px solid; }
.app-banner-lock { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
.app-banner-review { background: #fffbeb; color: #b45309; border-color: #fde68a; }
.app-banner-warn { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }

/* BODY */
.app-body { padding: 16px 22px 90px; max-width: 1150px; margin: 0 auto; display: flex; flex-direction: column; gap: 14px; }
.app-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 1px 6px rgba(15,23,42,.04); }
.app-clabel { display: flex; align-items: center; gap: 7px; padding: 11px 16px; font-size: .74rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid; }
.app-hint { font-style: normal; font-size: .62rem; font-weight: 600; color: #94a3b8; text-transform: none; letter-spacing: 0; }
.app-cnt { margin-left: auto; font-size: .62rem; font-weight: 800; background: #f1f5f9; color: #64748b; padding: 1px 8px; border-radius: 10px; }
.app-cbody { padding: 14px 16px; display: flex; flex-direction: column; gap: 11px; }
.app-need-save { margin: 0; display: flex; align-items: center; gap: 6px; font-size: .72rem; color: #b45309; background: #fffbeb; border: 1px dashed #fde68a; border-radius: 9px; padding: 8px 12px; }

/* OBJECTIF GENERAL */
.app-obj-gen { padding: 13px 16px; display: flex; flex-direction: column; gap: 7px; }
.app-og-label { display: flex; align-items: center; gap: 7px; font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: #334155; }
.app-obj-gen textarea { width: 100%; }

/* Rubriques */
.app-rub { display: flex; align-items: center; gap: 9px; padding: 11px 16px; font-size: .8rem; font-weight: 800; color: #0f172a;
  background: color-mix(in srgb, var(--rc) 7%, #fffbeb); border-bottom: 1px solid #f1e8c8; }
.app-rub-n { font-family: 'JetBrains Mono', monospace; color: var(--rc); }
.app-srow { display: grid; grid-template-columns: 260px 1fr; gap: 12px; align-items: start; }
@media (max-width: 760px) { .app-srow { grid-template-columns: 1fr; gap: 4px; } }
.app-srow > label { font-size: .72rem; font-weight: 700; color: #475569; padding-top: 8px; }
textarea, input[type="text"], input[type="number"], select {
  width: 100%; padding: 8px 10px; border-radius: 9px; border: 1px solid #e2e8f0;
  font-size: .76rem; color: #0f172a; outline: none; font-family: inherit; background: #fff;
  transition: border-color .12s;
}
textarea:focus, input:focus, select:focus { border-color: var(--fc, #059669); }
textarea:disabled, input:disabled, select:disabled { background: #f8fafc; color: #94a3b8; }
textarea { resize: vertical; line-height: 1.45; }

/* Programme audité */
.app-prog-row { flex-direction: row; flex-wrap: wrap; gap: 16px; }
.app-field { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
.app-field label { font-size: .66rem; font-weight: 700; color: #475569; }
.app-field label b { color: #dc2626; }
.app-field-prog { flex: 1; min-width: 260px; }
.app-objbase { flex: 1.4; min-width: 300px; background: #f8fafc; border: 1px solid #eef2f5; border-radius: 11px; padding: 10px 12px; display: flex; flex-direction: column; gap: 6px; }
.app-objbase-t { display: flex; align-items: center; gap: 6px; font-size: .64rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: #64748b; }
.app-objbase-t em { font-style: normal; background: #e2e8f0; border-radius: 8px; padding: 0 6px; }
.app-objbase-empty { font-size: .68rem; color: #94a3b8; font-style: italic; }
.app-objbase-item { display: flex; align-items: center; gap: 7px; font-size: .7rem; }
.app-objbase-type { font-size: .52rem; font-weight: 800; padding: 1px 5px; border-radius: 5px; flex-shrink: 0; }
.obt-strat { background: #ede9fe; color: #6d28d9; } .obt-oper { background: #dbeafe; color: #1d4ed8; }
.app-objbase-name { color: #334155; flex: 1; }
.app-objbase-kpi { font-size: .62rem; color: #059669; display: inline-flex; gap: 3px; align-items: center; }

/* Activités */
.app-act { border: 1px solid #eef2f5; border-radius: 11px; overflow: hidden; }
.app-act-head { display: flex; align-items: center; gap: 8px; padding: 8px 11px; background: #fbfdfc; flex-wrap: wrap; }
.app-act-toggle { border: none; background: none; cursor: pointer; color: #64748b; padding: 2px; }
.app-act-code { font-family: 'JetBrains Mono', monospace; font-size: .64rem; font-weight: 700; padding: 2px 7px; border-radius: 4px; }
.app-act-title { flex: 1; min-width: 160px; font-size: .78rem; font-weight: 700; color: #0f172a; }
.app-act-qte, .app-act-stats { font-size: .62rem; color: #64748b; }
.app-act-budget { font-size: .66rem; font-weight: 700; color: #047857; }
.app-act-body { padding: 9px 12px 11px 34px; display: flex; flex-direction: column; gap: 8px; border-top: 1px dashed #eef2f5; }

.app-obj { border-left: 2px solid #e2e8f0; padding-left: 10px; display: flex; flex-direction: column; gap: 5px; }
.app-obj-head { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; }
.app-obj-code { font-family: 'JetBrains Mono', monospace; font-size: .6rem; font-weight: 700; color: #6d28d9; background: #ede9fe; padding: 1px 6px; border-radius: 4px; }
.app-obj-lib { flex: 1; min-width: 140px; font-size: .73rem; color: #334155; }
.app-obj-src { display: inline-flex; align-items: center; gap: 3px; font-size: .6rem; color: #1d4ed8; background: #eff6ff; padding: 1px 7px; border-radius: 8px; max-width: 240px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.app-inds { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.app-ind-chip { display: inline-flex; align-items: center; gap: 4px; font-size: .62rem; font-weight: 600; color: #334155;
  background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 12px; padding: 2px 9px; cursor: pointer; max-width: 320px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
.app-ind-chip:hover { border-color: #94a3b8; }
.app-ind-add { display: inline-flex; align-items: center; gap: 3px; font-size: .62rem; font-weight: 700; border: 1px dashed; border-radius: 12px; padding: 2px 9px; background: #fff; cursor: pointer; font-family: inherit; }
.app-obj-new { display: flex; gap: 7px; align-items: center; flex-wrap: wrap; }
.app-obj-new input { flex: 2; min-width: 180px; }
.app-obj-new select { flex: 1.4; min-width: 170px; }

/* Rubriques libres */
.app-xrub { border: 1px solid #eef2f5; border-radius: 11px; padding: 10px 12px; display: flex; flex-direction: column; gap: 8px; }
.app-xrub-head { display: flex; align-items: center; gap: 8px; }
.app-rub-n2 { font-family: 'JetBrains Mono', monospace; font-weight: 700; color: #64748b; font-size: .74rem; }
.app-xrub-head input { font-weight: 700; }
.app-xsub { display: grid; grid-template-columns: 220px 1fr 30px; gap: 8px; align-items: start; }
@media (max-width: 700px) { .app-xsub { grid-template-columns: 1fr; } }
.app-add-sub { align-self: flex-start; display: inline-flex; align-items: center; gap: 4px; font-size: .64rem; font-weight: 700;
  color: #2563eb; background: none; border: none; cursor: pointer; font-family: inherit; font-style: italic; }
.app-add { align-self: flex-start; display: inline-flex; align-items: center; gap: 5px; font-size: .7rem; font-weight: 700;
  padding: 6px 12px; border: 1px dashed; border-radius: 9px; background: transparent; cursor: pointer; font-family: inherit; }

/* Financier */
.app-fin-save { margin-left: auto; }
.app-fin-wrap { display: flex; flex-direction: column; gap: 9px; overflow-x: auto; }
.app-fin { width: 100%; border-collapse: collapse; font-size: .72rem; min-width: 620px; }
.app-fin th { text-align: center; padding: 6px 8px; font-size: .58rem; font-weight: 800; text-transform: uppercase; color: #475569; border-bottom: 1px solid #e2e8f0; background: #f8fafc; }
.app-fin td { padding: 5px 6px; border-bottom: 1px solid #f1f5f9; }
.app-fin td input { text-align: right; }
.app-fin-annee { text-align: center !important; max-width: 90px; }
.app-fin-taux { text-align: center; font-weight: 800; }
.app-fin-total td { background: #f8fafc; text-align: right; }
.app-fin-total td:first-child { text-align: left; }

/* Signatures */
.app-sign-row { flex-direction: row; gap: 14px; flex-wrap: wrap; }
.app-sign-row .app-field { flex: 1; min-width: 220px; }

/* Boutons */
.app-mini { width: 26px; height: 26px; border-radius: 7px; border: 1px solid #e2e8f0; background: #fff; color: #64748b;
  display: inline-flex; align-items: center; justify-content: center; cursor: pointer; font-size: .72rem; flex-shrink: 0; }
.app-mini:hover { color: #0f172a; }
.app-mini-del { border-color: #fee2e2; color: #dc2626; }
.app-mini-del:hover { background: #dc2626; color: #fff; }
.app-btn { display: inline-flex; align-items: center; gap: 6px; font-size: .74rem; font-weight: 700; padding: 8px 16px;
  border-radius: 9px; border: 1px solid transparent; cursor: pointer; font-family: inherit; transition: all .13s; }
.app-btn:disabled { opacity: .55; cursor: not-allowed; }
.app-btn-save { color: #fff; } .app-btn-save:hover:not(:disabled) { filter: brightness(1.08); }
.app-btn-ghost { background: #fff; border-color: #e2e8f0; color: #64748b; }
.app-btn-submit { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
.app-btn-submit:hover:not(:disabled) { background: #1d4ed8; color: #fff; }
.app-btn-validate { background: #ecfdf5; border-color: #a7f3d0; color: #047857; }
.app-btn-validate:hover:not(:disabled) { background: #047857; color: #fff; }
.app-btn-reject { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
.app-btn-reject:hover:not(:disabled) { background: #b91c1c; color: #fff; }
.app-btn-sm { display: inline-flex; align-items: center; gap: 4px; font-size: .66rem; font-weight: 700; padding: 6px 11px;
  border-radius: 8px; border: none; color: #fff; cursor: pointer; font-family: inherit; }
.app-btn-sm:disabled { opacity: .55; cursor: not-allowed; }

/* Barre actions */
.app-actions { position: fixed; bottom: 0; left: 0; right: 0; z-index: 25; display: flex; align-items: center; justify-content: space-between;
  gap: 10px; padding: 10px 22px; background: rgba(255,255,255,.94); backdrop-filter: blur(8px); border-top: 1px solid #e2e8f0; }
.app-actions-l { font-size: .7rem; color: #475569; }
.app-actions-r { display: flex; gap: 8px; flex-wrap: wrap; }

/* Modales */
.app-ovl { position: fixed; inset: 0; z-index: 1000; background: rgba(15,23,42,.5); backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center; padding: 18px; }
.app-modal { width: min(680px, 96vw); max-height: 92vh; overflow-y: auto; background: #fff; border-radius: 16px;
  box-shadow: 0 24px 80px rgba(15,23,42,.3); display: flex; flex-direction: column; }
.app-modal-lg { width: min(860px, 96vw); }
.app-m-head { display: flex; align-items: center; gap: 9px; padding: 14px 18px; border-bottom: 2px solid; position: sticky; top: 0; background: #fff; z-index: 2; }
.app-m-head strong { font-size: .88rem; color: #0f172a; }
.app-m-head code { font-family: 'JetBrains Mono', monospace; font-size: .66rem; font-weight: 700; }
.app-m-sub { flex: 1; font-size: .64rem; color: #64748b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.app-m-x { margin-left: auto; width: 30px; height: 30px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer; flex-shrink: 0; }
.app-m-body { padding: 14px 18px; display: flex; flex-direction: column; gap: 11px; }
.app-m-foot { display: flex; gap: 8px; padding: 12px 18px; border-top: 1px solid #e2e8f0; position: sticky; bottom: 0; background: #fff; }
.app-m-spacer { flex: 1; }
.app-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
@media (max-width: 620px) { .app-grid2 { grid-template-columns: 1fr; } }

.app-chips-edit { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; border: 1px solid #e2e8f0; border-radius: 9px; padding: 5px 8px; }
.app-chips-edit input { border: none; flex: 1; min-width: 120px; padding: 4px; }
.app-chip { display: inline-flex; align-items: center; gap: 5px; font-size: .66rem; font-weight: 600; background: #f1f5f9; border-radius: 12px; padding: 2px 9px; }
.app-chip i { cursor: pointer; color: #94a3b8; } .app-chip i:hover { color: #dc2626; }

.app-ext-label { display: flex; align-items: center; gap: 6px; font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; margin-top: 4px; }
.app-ext-wrap { overflow-x: auto; display: flex; flex-direction: column; gap: 7px; }
.app-ext { width: 100%; border-collapse: collapse; font-size: .68rem; min-width: 760px; }
.app-ext th { padding: 5px 6px; font-size: .56rem; font-weight: 800; text-transform: uppercase; color: #475569; background: #f8fafc; border: 1px solid #eef2f5; text-align: center; }
.app-ext td { padding: 3px 4px; border: 1px solid #f1f5f9; }
.app-ext td input { padding: 5px 7px; font-size: .68rem; }

.app-vals { display: flex; flex-direction: column; gap: 6px; }
.app-val { display: grid; grid-template-columns: 110px 1fr 30px; gap: 7px; }
.app-sugg-box { border: 1px dashed; border-radius: 11px; padding: 9px 12px; display: flex; flex-direction: column; gap: 6px; background: #fafcfb; }
.app-sugg-t { display: inline-flex; align-items: center; gap: 5px; font-size: .66rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
.app-sugg-list { display: flex; flex-wrap: wrap; gap: 6px; }
.app-sugg-chip2 { display: inline-flex; align-items: center; gap: 4px; font-size: .64rem; font-weight: 600; color: #334155; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 3px 10px; cursor: pointer; font-family: inherit; }
.app-sugg-chip2:hover { border-color: #94a3b8; }
.app-sugg-chip2 em { font-style: normal; color: #94a3b8; }
.app-sens { display: flex; gap: 8px; }
.app-sens button { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 8px;
  border-radius: 9px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; font-size: .72rem; font-weight: 700; cursor: pointer; font-family: inherit; }
.app-sens button.on { border-color: #059669; background: #ecfdf5; color: #047857; }

/* Transitions / toast */
.app-fade-enter-active, .app-fade-leave-active { transition: opacity .16s ease; }
.app-fade-enter-from, .app-fade-leave-to { opacity: 0; }
.app-toast { position: fixed; bottom: 76px; right: 22px; z-index: 2000; display: flex; align-items: center; gap: 8px;
  padding: 10px 16px; border-radius: 11px; font-size: .76rem; font-weight: 700; box-shadow: 0 8px 30px rgba(15,23,42,.18); font-family: 'Plus Jakarta Sans', sans-serif; }
.app-toast-success { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
.app-toast-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
.app-toastx-enter-active, .app-toastx-leave-active { transition: all .2s ease; }
.app-toastx-enter-from, .app-toastx-leave-to { opacity: 0; transform: translateY(8px); }
.app-spin { animation: app-rot .7s linear infinite; }
@keyframes app-rot { to { transform: rotate(360deg); } }
</style>
