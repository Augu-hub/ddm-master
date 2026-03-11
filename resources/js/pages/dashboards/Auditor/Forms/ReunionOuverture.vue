<template>
  <VerticalLayoutAudit>
    <div class="fro-shell">

      <!-- ══════════════════════════════════════════════
           HEADER sticky (même pattern que MissionPhases)
      ══════════════════════════════════════════════ -->
      <header class="fro-header" :style="`--fc:${mc};--fcl:${mc}18;--fcm:${mc}35`">
        <div class="fro-hrow">

          <a :href="props.backUrl" class="fro-back" title="Retour aux phases">
            <i class="ti ti-arrow-left"></i>
          </a>

          <div class="fro-hinfo">
            <div class="fro-chips">
              <code class="fro-code" :style="`color:${mc};background:${mc}15;border-color:${mc}30`">
                {{ mission?.code_mission ?? '—' }}
              </code>
              <span v-if="fro?.validation_status" class="fro-vstchip" :class="`fvsc-${fro.validation_status}`">
                <i :class="vstIcon(fro.validation_status)"></i>
                {{ vstLbl(fro.validation_status) }}
              </span>
              <span class="fro-typechip" :style="`color:${mc};background:${mc}12`">
                <i class="ti ti-clipboard-text"></i> FRO
              </span>
              <span v-if="props.auditorRole" class="fro-rolechip" :class="`rc-${props.auditorRole}`">
                <i class="ti ti-shield-half"></i> {{ props.auditorRole }}
              </span>
            </div>
            <h1 class="fro-htitle">Réunion d'Ouverture</h1>
            <div class="fro-hmeta">
              <span v-if="assignment?.phase_label"><i class="ti ti-git-branch"></i>{{ assignment.phase_label }}</span>
              <span v-if="mission?.entity_name"><i class="ti ti-building"></i>{{ mission.entity_name }}</span>
              <span v-if="mission?.date_debut_fr"><i class="ti ti-calendar"></i>{{ mission.date_debut_fr }} — {{ mission.date_fin_fr }}</span>
              <span v-if="mission?.lieux"><i class="ti ti-map-pin"></i>{{ mission.lieux }}</span>
            </div>
          </div>

          <!-- Boutons header -->
          <div class="fro-hbtns">
            <button class="fro-hbtn fro-hbtn-chat" :class="{ unread: unreadCount > 0 }" @click="openChat">
              <i class="ti ti-message-circle"></i>
              <span v-if="unreadCount > 0" class="fro-chatbadge">{{ unreadCount }}</span>
            </button>
          </div>
        </div>

        <!-- Alertes inline -->
        <div v-if="fro?.validation_status === 'validated'" class="fro-hbanner fro-hbanner-lock">
          <i class="ti ti-lock"></i>
          <span>Formulaire <strong>validé définitivement</strong> par le DM — lecture seule</span>
        </div>
        <div v-else-if="fro?.validation_status === 'in_review'" class="fro-hbanner fro-hbanner-review">
          <i class="ti ti-clock"></i>
          <span>Soumis pour validation — en attente DM. <span v-if="canManage">Vous pouvez valider ou rejeter.</span></span>
        </div>
        <div v-if="props.noMission" class="fro-hbanner fro-hbanner-warn">
          <i class="ti ti-alert-triangle"></i>
          <span>Aucune mission active. <a :href="props.backUrl" class="fro-link">Retour aux missions</a></span>
        </div>
        <div v-if="props.phaseNotStarted" class="fro-hbanner fro-hbanner-warn">
          <i class="ti ti-player-pause"></i>
          <span>Cette phase n'est pas encore démarrée. Démarrez-la depuis les phases de mission.</span>
        </div>
      </header>

      <!-- ══ BODY ══ -->
      <div v-if="!props.noMission && !props.phaseNotStarted" class="fro-body">

        <form @submit.prevent="submit" class="fro-form">
          <!-- ══ DASHBOARD REPRISE ════════════════════════════════════
               Affiché uniquement si un FRO existe déjà (fro.id),
               pour que l'auditeur retrouve immédiatement son avancement.
          ════════════════════════════════════════════════════════════ -->
          <div v-if="fro.id" class="fro-reprise">

            <!-- Colonne gauche : progression + statut -->
            <div class="frp-left">
              <!-- Anneau progression -->
              <div class="frp-ring-wrap">
                <svg viewBox="0 0 42 42" class="frp-ring-svg">
                  <circle cx="21" cy="21" r="17" fill="none" stroke="#e2e8f0" stroke-width="3.2"/>
                  <circle cx="21" cy="21" r="17" fill="none"
                    :stroke="mc" stroke-width="3.2" stroke-linecap="round"
                    :stroke-dasharray="`${froProgress} ${100 - froProgress}`"
                    stroke-dashoffset="25"
                    style="transition: stroke-dasharray .5s ease"/>
                </svg>
                <div class="frp-ring-inner">
                  <span class="frp-pct" :style="`color:${mc}`">{{ froProgress }}%</span>
                  <span class="frp-pctsub">rempli</span>
                </div>
              </div>
              <!-- Statut validation -->
              <div class="frp-vst-block">
                <span class="frp-vst" :class="`fvsc-${fro.validation_status || 'draft'}`">
                  <i :class="vstIcon(fro.validation_status || 'draft')"></i>
                  {{ vstLbl(fro.validation_status || 'draft') }}
                </span>
                <span class="frp-code" :style="`color:${mc}`">{{ fro.code_fro }}</span>
                <span v-if="fro.date_reunion" class="frp-date">
                  <i class="ti ti-calendar-event"></i>
                  {{ fro.date_reunion }}
                </span>
              </div>
            </div>

            <!-- Colonne milieu : checklist sections -->
            <div class="frp-checks">
              <div class="frp-checks-title">Avancement des sections</div>
              <div class="frp-checklist">
                <div class="frp-chk" :class="{ done: fro.date_reunion && fro.lieu }">
                  <span class="frp-chk-dot"></span>
                  <span class="frp-chk-lbl">Info réunion</span>
                  <span class="frp-chk-val" v-if="fro.date_reunion">{{ fro.date_reunion }}</span>
                </div>
                <div class="frp-chk" :class="{ done: ordreCount > 0 }">
                  <span class="frp-chk-dot"></span>
                  <span class="frp-chk-lbl">Ordre du jour</span>
                  <span class="frp-chk-cnt">{{ ordreCount }} point{{ ordreCount > 1 ? 's' : '' }}</span>
                </div>
                <div class="frp-chk" :class="{ done: participantsCount > 0 }">
                  <span class="frp-chk-dot"></span>
                  <span class="frp-chk-lbl">Participants</span>
                  <span class="frp-chk-cnt">{{ participantsCount }} pers.</span>
                </div>
                <div class="frp-chk" :class="{ done: pointsCount > 0 }">
                  <span class="frp-chk-dot"></span>
                  <span class="frp-chk-lbl">Points généraux</span>
                  <span class="frp-chk-cnt">{{ pointsCount }} point{{ pointsCount > 1 ? 's' : '' }}</span>
                </div>
                <div class="frp-chk" :class="{ done: preoccCount > 0 }">
                  <span class="frp-chk-dot"></span>
                  <span class="frp-chk-lbl">Préoccupations</span>
                  <span class="frp-chk-cnt">{{ preoccCount }} élémt.</span>
                </div>
                <div class="frp-chk" :class="{ done: !!fro.fichier_audio_path }">
                  <span class="frp-chk-dot"></span>
                  <span class="frp-chk-lbl">Fichier audio</span>
                  <span class="frp-chk-val">{{ fro.fichier_audio_path ? '✓' : '—' }}</span>
                </div>
              </div>
            </div>

            <!-- Colonne droite : prochaines actions -->
            <div class="frp-right">
              <div class="frp-next-title">À faire maintenant</div>
              <div class="frp-next-list">
                <template v-if="fro.validation_status === 'draft'">
                  <div v-if="!fro.date_reunion || !fro.lieu" class="frp-next-item frp-next-todo">
                    <i class="ti ti-edit"></i>
                    <span>Renseigner la date et le lieu</span>
                  </div>
                  <div v-if="ordreCount === 0" class="frp-next-item frp-next-todo">
                    <i class="ti ti-list-numbers"></i>
                    <span>Ajouter l'ordre du jour</span>
                  </div>
                  <div v-if="participantsCount === 0" class="frp-next-item frp-next-todo">
                    <i class="ti ti-users"></i>
                    <span>Lister les participants</span>
                  </div>
                  <div v-if="froProgress >= 80" class="frp-next-item frp-next-ok">
                    <i class="ti ti-send"></i>
                    <span>Formulaire prêt à soumettre</span>
                  </div>
                  <div v-if="froProgress === 100" class="frp-next-item frp-next-done">
                    <i class="ti ti-circle-check"></i>
                    <span>Toutes les sections remplies !</span>
                  </div>
                </template>
                <template v-else-if="fro.validation_status === 'in_review'">
                  <div class="frp-next-item frp-next-wait">
                    <i class="ti ti-clock"></i>
                    <span>En attente de validation DM/CM</span>
                  </div>
                  <div v-if="canManage" class="frp-next-item frp-next-ok">
                    <i class="ti ti-shield-check"></i>
                    <span>Vous pouvez valider ou rejeter</span>
                  </div>
                </template>
                <template v-else-if="fro.validation_status === 'validated'">
                  <div class="frp-next-item frp-next-done">
                    <i class="ti ti-circle-check"></i>
                    <span>Formulaire validé — phase clôturée</span>
                  </div>
                </template>
              </div>

              <!-- Dernière modification -->
              <div v-if="fro.updated_at" class="frp-lastmod">
                <i class="ti ti-clock-edit"></i>
                Dernière modif. : {{ fro.updated_at }}
              </div>
            </div>

          </div><!-- /fro-reprise -->

          <div class="fro-grid">

            <!-- ══ COLONNE GAUCHE ══ -->
            <div class="fro-col">

              <!-- Info Mission (readonly) -->
              <section class="fro-card">
                <div class="fro-clabel" :style="`color:${mc};border-color:${mc}25`">
                  <i class="ti ti-briefcase"></i> Info mission
                </div>
                <div class="fro-cbody">
                  <div class="fro-r2">
                    <div class="fro-field">
                      <label class="fro-lbl">Code mission</label>
                      <input class="fro-inp fro-ro" :value="mission?.code_mission" readonly />
                    </div>
                    <div class="fro-field">
                      <label class="fro-lbl">Phase</label>
                      <input class="fro-inp fro-ro" :value="assignment?.phase_label || form.phase_code" readonly />
                    </div>
                  </div>
                  <div class="fro-field">
                    <label class="fro-lbl">Intitulé</label>
                    <input class="fro-inp fro-ro" :value="mission?.libelle" readonly />
                  </div>
                  <div class="fro-r2">
                    <div class="fro-field">
                      <label class="fro-lbl">Entité auditée</label>
                      <input class="fro-inp fro-ro" :value="mission?.entity_name || '—'" readonly />
                    </div>
                    <div class="fro-field">
                      <label class="fro-lbl">Lieu</label>
                      <input class="fro-inp fro-ro" :value="mission?.lieux || '—'" readonly />
                    </div>
                  </div>
                  <div class="fro-r3">
                    <div class="fro-field">
                      <label class="fro-lbl">Début</label>
                      <input class="fro-inp fro-ro" :value="mission?.date_debut_fr" readonly />
                    </div>
                    <div class="fro-field">
                      <label class="fro-lbl">Fin</label>
                      <input class="fro-inp fro-ro" :value="mission?.date_fin_fr" readonly />
                    </div>
                    <div class="fro-field">
                      <label class="fro-lbl">Objet</label>
                      <input class="fro-inp fro-ro" :value="mission?.objectif" readonly />
                    </div>
                  </div>
                </div>
              </section>

              <!-- Info Réunion -->
              <section class="fro-card">
                <div class="fro-clabel" :style="`color:${mc};border-color:${mc}25`">
                  <i class="ti ti-calendar-event"></i> Info réunion
                </div>
                <div class="fro-cbody">
                  <div class="fro-r2">
                    <div class="fro-field">
                      <label class="fro-lbl">Code FRO</label>
                      <input class="fro-inp fro-ro" :value="form.code_fro || 'FRO-AUTO'" readonly />
                    </div>
                    <div class="fro-field">
                      <label class="fro-lbl">Phase</label>
                      <input class="fro-inp fro-ro" v-model="form.phase_code" readonly />
                    </div>
                  </div>
                  <div class="fro-field">
                    <label class="fro-lbl">Date réunion <span class="fro-req">*</span></label>
                    <input type="date" class="fro-inp" :class="{ err: formErrors.date_reunion }"
                      v-model="form.date_reunion" :disabled="isLocked" />
                    <span v-if="formErrors.date_reunion" class="fro-errmsg">{{ formErrors.date_reunion }}</span>
                  </div>
                  <div class="fro-r2">
                    <div class="fro-field">
                      <label class="fro-lbl">Heure début</label>
                      <input type="time" class="fro-inp" v-model="form.heure_debut" :disabled="isLocked" />
                    </div>
                    <div class="fro-field">
                      <label class="fro-lbl">Heure fin</label>
                      <input type="time" class="fro-inp" v-model="form.heure_fin" :disabled="isLocked" />
                    </div>
                  </div>
                  <div class="fro-field">
                    <label class="fro-lbl">Lieu de la réunion <span class="fro-req">*</span></label>
                    <input type="text" class="fro-inp" :class="{ err: formErrors.lieu }"
                      v-model="form.lieu" :disabled="isLocked" placeholder="Salle, adresse…" />
                    <span v-if="formErrors.lieu" class="fro-errmsg">{{ formErrors.lieu }}</span>
                  </div>
                </div>
              </section>

              <!-- Ordre du jour -->
              <section class="fro-card">
                <div class="fro-clabel" :style="`color:${mc};border-color:${mc}25`">
                  <i class="ti ti-list-numbers"></i> Ordre du jour
                </div>
                <div class="fro-cbody fro-cbody-table">
                  <div class="fro-tbhd" :style="`background:${mc}`">
                    <span>Points à l'ordre du jour</span>
                    <button v-if="!isLocked" type="button" class="fro-tbadd" @click="addRow(form.ordre_du_jour, { point:'' })">
                      <i class="ti ti-plus"></i>
                    </button>
                  </div>
                  <table class="fro-tbl">
                    <thead><tr>
                      <th style="width:38px">N°</th>
                      <th>Point</th>
                      <th v-if="!isLocked" style="width:34px"></th>
                    </tr></thead>
                    <tbody>
                      <tr v-if="!form.ordre_du_jour.length">
                        <td :colspan="isLocked ? 2 : 3" class="fro-td-empty">Aucun point ajouté</td>
                      </tr>
                      <tr v-for="(o, i) in form.ordre_du_jour" :key="i">
                        <td class="fro-tdn">{{ i + 1 }}</td>
                        <td>
                          <input v-if="!isLocked" class="fro-tdinp" v-model="o.point" placeholder="Libellé…" />
                          <span v-else class="fro-tdro">{{ o.point }}</span>
                        </td>
                        <td v-if="!isLocked">
                          <button type="button" class="fro-delbtn" @click="removeRow(form.ordre_du_jour, i)">
                            <i class="ti ti-trash"></i>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </section>

              <!-- Participants -->
              <section class="fro-card">
                <div class="fro-clabel" :style="`color:${mc};border-color:${mc}25`">
                  <i class="ti ti-users"></i> Participants
                </div>
                <div class="fro-cbody fro-cbody-table">
                  <div class="fro-tbhd" :style="`background:${mc}`">
                    <span>Liste des participants</span>
                    <button v-if="!isLocked" type="button" class="fro-tbadd"
                      @click="addRow(form.participants, { nom:'', prenom:'', fonction:'', observation:'' })">
                      <i class="ti ti-plus"></i>
                    </button>
                  </div>
                  <div class="fro-tblwrap">
                    <table class="fro-tbl">
                      <thead><tr>
                        <th>Nom</th>
                        <th>Prénoms</th>
                        <th>Fonction</th>
                        <th>Observation</th>
                        <th v-if="!isLocked" style="width:34px"></th>
                      </tr></thead>
                      <tbody>
                        <tr v-if="!form.participants.length">
                          <td :colspan="isLocked ? 4 : 5" class="fro-td-empty">Aucun participant</td>
                        </tr>
                        <tr v-for="(p, i) in form.participants" :key="i">
                          <td>
                            <input v-if="!isLocked" class="fro-tdinp" v-model="p.nom" />
                            <span v-else class="fro-tdro">{{ p.nom }}</span>
                          </td>
                          <td>
                            <input v-if="!isLocked" class="fro-tdinp" v-model="p.prenom" />
                            <span v-else class="fro-tdro">{{ p.prenom }}</span>
                          </td>
                          <td>
                            <input v-if="!isLocked" class="fro-tdinp" v-model="p.fonction" />
                            <span v-else class="fro-tdro">{{ p.fonction }}</span>
                          </td>
                          <td>
                            <input v-if="!isLocked" class="fro-tdinp" v-model="p.observation" />
                            <span v-else class="fro-tdro">{{ p.observation }}</span>
                          </td>
                          <td v-if="!isLocked">
                            <button type="button" class="fro-delbtn" @click="removeRow(form.participants, i)">
                              <i class="ti ti-trash"></i>
                            </button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </section>

            </div><!-- /col gauche -->

            <!-- ══ COLONNE DROITE ══ -->
            <div class="fro-col">

              <!-- Auditeurs de la mission -->
              <section class="fro-card">
                <div class="fro-clabel" :style="`color:${mc};border-color:${mc}25`">
                  <i class="ti ti-id-badge"></i> Équipe d'audit
                </div>
                <div class="fro-cbody fro-cbody-table">
                  <table class="fro-tbl">
                    <thead><tr>
                      <th style="width:76px">Code</th>
                      <th>Nom</th>
                      <th>Prénom</th>
                      <th style="width:56px">Grade</th>
                    </tr></thead>
                    <tbody>
                      <tr v-if="!auditeurs.length">
                        <td colspan="4" class="fro-td-empty">Aucun auditeur affecté</td>
                      </tr>
                      <tr v-for="a in auditeurs" :key="a.id">
                        <td class="fro-tdcode">{{ a.code }}</td>
                        <td class="fro-tdbold">{{ a.nom }}</td>
                        <td>{{ a.prenom }}</td>
                        <td><span class="fro-grade" :class="`gr-${a.grade}`">{{ a.grade }}</span></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </section>

              <!-- Points généraux -->
              <section class="fro-card">
                <div class="fro-clabel" :style="`color:${mc};border-color:${mc}25`">
                  <i class="ti ti-file-description"></i> Résumé de la réunion
                </div>
                <div class="fro-cbody fro-cbody-table">
                  <div class="fro-tbhd" :style="`background:${mc}`">
                    <span>Points généraux abordés</span>
                    <button v-if="!isLocked" type="button" class="fro-tbadd"
                      @click="addRow(form.points_generaux, { libelle:'' })">
                      <i class="ti ti-plus"></i>
                    </button>
                  </div>
                  <table class="fro-tbl">
                    <thead><tr>
                      <th style="width:38px">N°</th>
                      <th>Libellé</th>
                      <th v-if="!isLocked" style="width:34px"></th>
                    </tr></thead>
                    <tbody>
                      <tr v-if="!form.points_generaux.length">
                        <td :colspan="isLocked ? 2 : 3" class="fro-td-empty">—</td>
                      </tr>
                      <tr v-for="(pg, i) in form.points_generaux" :key="i">
                        <td class="fro-tdn">{{ i + 1 }}</td>
                        <td>
                          <input v-if="!isLocked" class="fro-tdinp" v-model="pg.libelle" />
                          <span v-else class="fro-tdro">{{ pg.libelle }}</span>
                        </td>
                        <td v-if="!isLocked">
                          <button type="button" class="fro-delbtn" @click="removeRow(form.points_generaux, i)">
                            <i class="ti ti-trash"></i>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>

                  <!-- Préoccupations -->
                  <div class="fro-tbhd fro-tbhd-2" :style="`background:${mc}`">
                    <span>Préoccupations des participants</span>
                    <button v-if="!isLocked" type="button" class="fro-tbadd"
                      @click="addRow(form.preoccupations, { libelle:'' })">
                      <i class="ti ti-plus"></i>
                    </button>
                  </div>
                  <table class="fro-tbl">
                    <thead><tr>
                      <th style="width:38px">N°</th>
                      <th>Libellé</th>
                      <th v-if="!isLocked" style="width:34px"></th>
                    </tr></thead>
                    <tbody>
                      <tr v-if="!form.preoccupations.length">
                        <td :colspan="isLocked ? 2 : 3" class="fro-td-empty">—</td>
                      </tr>
                      <tr v-for="(pr, i) in form.preoccupations" :key="i">
                        <td class="fro-tdn">{{ i + 1 }}</td>
                        <td>
                          <input v-if="!isLocked" class="fro-tdinp" v-model="pr.libelle" />
                          <span v-else class="fro-tdro">{{ pr.libelle }}</span>
                        </td>
                        <td v-if="!isLocked">
                          <button type="button" class="fro-delbtn" @click="removeRow(form.preoccupations, i)">
                            <i class="ti ti-trash"></i>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </section>

              <!-- Audio -->
              <section class="fro-card">
                <div class="fro-clabel" :style="`color:${mc};border-color:${mc}25`">
                  <i class="ti ti-microphone"></i> Fichier audio
                </div>
                <div class="fro-cbody">
                  <div class="fro-audiorow">
                    <input class="fro-inp fro-ro" style="flex:1"
                      :value="form.fichier_audio_path || 'Aucun fichier'" readonly />
                    <button v-if="!isLocked" type="button" class="fro-btn fro-btn-sm fro-btn-sec"
                      @click="($refs.audioInput as HTMLInputElement).click()">
                      <i class="ti ti-folder"></i>
                    </button>
                    <input ref="audioInput" type="file" accept="audio/*" class="fro-hidden" @change="onAudioFile" />
                  </div>
                  <div v-if="!isLocked" class="fro-recrow">
                    <button type="button" class="fro-btn fro-btn-sm"
                      :class="recording ? 'fro-btn-rec-on' : 'fro-btn-rec'"
                      :disabled="recording" @click="startRec">
                      <i class="ti ti-microphone"></i>
                      <span v-if="recording" class="fro-recdot"></span>
                      {{ recording ? 'En cours…' : 'Enregistrer' }}
                    </button>
                    <button v-if="recording" type="button" class="fro-btn fro-btn-sm fro-btn-sec" @click="stopRec">
                      <i class="ti ti-player-stop"></i> Stop
                    </button>
                    <span v-if="recording" class="fro-rectimer">
                      <i class="ti ti-clock"></i> {{ recTime }}
                    </span>
                  </div>
                  <div v-if="audioBlobUrl" class="fro-audioplayer">
                    <audio :src="audioBlobUrl" controls></audio>
                  </div>
                </div>
              </section>

              <!-- Liste FRO -->
              <section class="fro-card">
                <div class="fro-clabel" :style="`color:${mc};border-color:${mc}25`">
                  <i class="ti ti-list"></i> Réunions enregistrées
                </div>
                <div class="fro-cbody fro-cbody-table">
                  <div class="fro-listhd">
                    <input class="fro-inp fro-search" v-model="search" placeholder="Rechercher…" />
                  </div>
                  <table class="fro-tbl">
                    <thead><tr>
                      <th>Code</th>
                      <th>Date</th>
                      <th>Lieu</th>
                      <th>Statut</th>
                      <th style="width:70px"></th>
                    </tr></thead>
                    <tbody>
                      <tr v-if="!filteredFros.length">
                        <td colspan="5" class="fro-td-empty">Aucune réunion enregistrée</td>
                      </tr>
                      <tr v-for="f in filteredFros" :key="f.id" class="fro-listrow" @click="loadFro(f)">
                        <td class="fro-tdcode">{{ f.code_fro }}</td>
                        <td>{{ f.date_reunion_fr }}</td>
                        <td>{{ f.lieu }}</td>
                        <td>
                          <span class="fro-vstchip" :class="`fvsc-${f.status || 'draft'}`">
                            {{ vstLbl(f.status || 'draft') }}
                          </span>
                        </td>
                        <td class="fro-tdacts" @click.stop>
                          <button class="fro-actbtn fro-actedit" title="Éditer" @click.stop="loadFro(f)">
                            <i class="ti ti-pencil"></i>
                          </button>
                          <button v-if="(f.status || 'draft') !== 'validated'" class="fro-actbtn fro-actdel"
                            title="Supprimer" @click.stop="deleteFro(f)">
                            <i class="ti ti-trash"></i>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </section>

            </div><!-- /col droite -->
          </div><!-- /fro-grid -->

          <!-- ══ FOOTER ACTIONS ══ -->
          <footer v-if="!isLocked || canManage" class="fro-footer">
            <div v-if="!isLocked" class="fro-footer-meta">
              <div class="fro-field-i">
                <label class="fro-lbl">Fait par</label>
                <input class="fro-inp fro-inp-sm" v-model="form.fait_par" />
              </div>
              <div class="fro-field-i">
                <label class="fro-lbl">Revue par</label>
                <input class="fro-inp fro-inp-sm" v-model="form.revue_par" />
              </div>
            </div>

            <div class="fro-footer-acts">
              <!-- Annuler -->
              <button v-if="!isLocked" type="button" class="fro-btn fro-btn-ghost"
                :disabled="processing" @click="annuler">
                <i class="ti ti-x"></i> Annuler
              </button>

              <!-- Enregistrer -->
              <button v-if="!isLocked" type="submit" class="fro-btn fro-btn-save"
                :disabled="processing" :style="`background:${mc};border-color:${mc}`">
                <span v-if="processing" class="fro-spin"></span>
                <i v-else class="ti ti-device-floppy"></i>
                Enregistrer
              </button>

              <!-- Soumettre pour validation (draft uniquement) -->
              <button v-if="fro?.validation_status === 'draft' && fro?.id" type="button"
                class="fro-btn fro-btn-submit" :disabled="processing" @click="soumettre">
                <i class="ti ti-send"></i> Soumettre pour validation
              </button>

              <!-- Valider / Rejeter (DM/CM si in_review) -->
              <template v-if="canManage && fro?.validation_status === 'in_review'">
                <button type="button" class="fro-btn fro-btn-validate"
                  :disabled="processing" @click="valider('validate')">
                  <i class="ti ti-circle-check"></i> Valider
                </button>
                <button type="button" class="fro-btn fro-btn-reject"
                  :disabled="processing" @click="promptReject">
                  <i class="ti ti-circle-x"></i> Rejeter
                </button>
              </template>

              <!-- ── Télécharger PDF ── -->
              <a v-if="fro?.id && (fro?.validation_status === 'validated' || canManage)"
                 :href="`${props.formUrl}/${fro.id}/pdf?download=1`"
                 target="_blank"
                 class="fro-btn fro-btn-pdf"
                 title="Télécharger la fiche FRO en PDF">
                <i class="ti ti-file-type-pdf"></i>
                <span>Télécharger PDF</span>
              </a>

            </div>
          </footer>

        </form>
      </div><!-- /fro-body -->

    </div><!-- /fro-shell -->

    <!-- ══════════════════════════════════════
         PANEL CHAT (même pattern MissionPhases)
    ══════════════════════════════════════ -->
    <Teleport to="body">
      <transition name="slide-right">
        <div v-if="chatPanel.show" class="fro-chat-panel">
          <div class="fro-chat-hd">
            <div class="fro-chat-hdinfo">
              <div class="fro-chat-av" :style="`background:${mc}20;color:${mc}`">
                <i class="ti ti-message-circle"></i>
              </div>
              <div>
                <span class="fro-chat-title">Chat FRO</span>
                <span class="fro-chat-sub">{{ mission?.code_mission }} · Réunion d'ouverture</span>
              </div>
            </div>
            <button class="fro-chat-close" @click="chatPanel.show = false"><i class="ti ti-x"></i></button>
          </div>

          <div class="fro-chat-msgs" ref="chatMsgEl">
            <div v-if="!chatPanel.messages.length" class="fro-chat-empty">
              <i class="ti ti-messages"></i>
              <p>Aucun message pour ce formulaire.</p>
            </div>
            <div v-for="msg in chatPanel.messages" :key="msg.id"
              class="fro-cmsg" :class="[`ft-${msg.type}`, `fp-${msg.priority}`, { mine: msg.is_mine }]">
              <div class="fro-cav" :class="`cav-${msg.author_role}`">{{ msg.author_initials }}</div>
              <div class="fro-cbody2">
                <div class="fro-cmeta">
                  <span class="fro-cwho" :class="`cr-${msg.author_role}`">{{ msg.author_name }}</span>
                  <span class="fro-crole">{{ msg.author_role }}</span>
                  <span v-if="msg.type !== 'message'" class="fro-ctypetag">{{ chatTypeLbl(msg.type) }}</span>
                  <span v-if="msg.priority !== 'normal'" class="fro-cpritag" :class="`pp-${msg.priority}`">{{ msg.priority }}</span>
                  <span class="fro-cdate">{{ msg.created_at_fr }}</span>
                </div>
                <p class="fro-ctxt">{{ msg.content }}</p>
              </div>
            </div>
          </div>

          <div class="fro-chat-compose">
            <div class="fro-chat-opts">
              <select v-model="chatPanel.type" class="fro-chat-sel">
                <option value="message">Message</option>
                <option value="instruction">Instruction</option>
                <option value="info">Info</option>
              </select>
              <div class="fro-prios">
                <button v-for="p in PRIOS" :key="p.v" type="button"
                  class="fro-priobtn" :class="[{ active: chatPanel.priority === p.v }, `ppb-${p.v}`]"
                  @click="chatPanel.priority = p.v">
                  <i :class="p.icon"></i> {{ p.l }}
                </button>
              </div>
            </div>
            <div class="fro-chat-row">
              <textarea v-model="chatPanel.draft" class="fro-chat-ta" rows="2"
                placeholder="Votre message…" @keydown.ctrl.enter="sendMsg"></textarea>
              <button type="button" class="fro-chat-send"
                :disabled="!chatPanel.draft.trim() || chatPanel.sending"
                :style="`background:${mc}`"
                @click="sendMsg">
                <i class="ti ti-send"></i>
              </button>
            </div>
            <div class="fro-chat-hint">Ctrl+Entrée pour envoyer</div>
          </div>
        </div>
      </transition>
      <div v-if="chatPanel.show" class="fro-chat-overlay" @click="chatPanel.show = false"></div>
    </Teleport>

    <!-- Toast -->
    <Teleport to="body">
      <transition name="fade-up">
        <div v-if="toast.show" class="fro-toast" :class="`toast-${toast.type}`">
          <i :class="toast.type === 'success' ? 'ti ti-circle-check' : 'ti ti-alert-circle'"></i>
          {{ toast.msg }}
        </div>
      </transition>
    </Teleport>

  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onBeforeUnmount, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'

// ══════════════════════════════════════════════════════════
// PROPS — URLs construites côté PHP (pattern MissionPhases)
// ══════════════════════════════════════════════════════════
const props = defineProps({
  // Données mission
  mission:      { type: Object,  default: null },
  assignment:   { type: Object,  default: null },
  auditeurs:    { type: Array,   default: () => [] },
  fro:          { type: Object,  default: null },     // FRO courant (hydraté, JSON décodé)
  fros:         { type: Array,   default: () => [] }, // Liste FRO pour cet assignment
  chatMessages: { type: Array,   default: () => [] },
  errors:       { type: Object,  default: () => ({}) },

  // État
  noMission:       { type: Boolean, default: false },
  phaseNotStarted: { type: Boolean, default: false },
  auditorRole:     { type: String,  default: null },

  // IDs de contexte
  missionId:    { type: Number, default: null },
  assignmentId: { type: Number, default: null },

  // Auditeur courant (pour auto-remplir fait_par et afficher le dashboard reprise)
  currentAuditor: { type: Object, default: null },

  // URLs pré-construites côté PHP — AUCUNE reconstruction JS
  backUrl:     { type: String, default: '' },   // /m/audit.core/auditor/missions/{id}/phases
  formUrl:     { type: String, default: '' },   // /m/audit.core/ac/preparation/reunion-ouverture
  chatBaseUrl: { type: String, default: '' },   // /api/mission-phase-chat
})

// ══════════════════════════════════════════════════════════
// COULEUR — même logique que MissionPhases
// ══════════════════════════════════════════════════════════
const mc = computed<string>(() => {
  const c = (props.mission as any)?.audit_color
  if (c && c !== '#000000' && c !== '#000' && c !== 'null') return c
  return '#1565C0'
})

// ══════════════════════════════════════════════════════════
// ÉTAT
// ══════════════════════════════════════════════════════════
const canManage   = computed(() => ['DM', 'CM'].includes(props.auditorRole ?? ''))
const isLocked    = computed(() =>
  fro?.validation_status === 'validated' ||
  (fro?.validation_status === 'in_review' && !canManage.value)
)

// Nom de l'auditeur connecté pour auto-remplir fait_par
const currentAuditorName = computed<string>(() => {
  const a = props.currentAuditor as any
  if (!a) return ''
  return [a.last_name, a.first_name].filter(Boolean).join(' ').trim()
    || a.audit_code || ''
})


// ══════════════════════════════════════════════════════════
// DASHBOARD REPRISE — compteurs et progression
// ══════════════════════════════════════════════════════════
const ordreCount       = computed(() => form.ordre_du_jour.length)
const participantsCount = computed(() => form.participants.length)
const pointsCount      = computed(() => form.points_generaux.length)
const preoccCount      = computed(() => form.preoccupations.length)

const froProgress = computed<number>(() => {
  // 6 sections pondérées
  let score = 0
  if (fro.date_reunion && fro.lieu) score += 25    // Info réunion : critique
  if (ordreCount.value > 0)         score += 20    // ODJ
  if (participantsCount.value > 0)  score += 25    // Participants
  if (pointsCount.value > 0)        score += 15    // Points généraux
  if (preoccCount.value > 0)        score += 10    // Préoccupations
  if (fro.fichier_audio_path)       score += 5     // Audio (bonus)
  return Math.min(score, 100)
})

// Fro réactif local (pour mise à jour après submit sans rechargement complet)
const fro = reactive<Record<string, any>>(props.fro ? { ...props.fro } : {})

// Formulaire réactif
const form = reactive({
  id:                 props.fro?.id               ?? null,
  code_fro:           props.fro?.code_fro         ?? '',
  phase_code:         props.fro?.phase_code        ?? props.assignment?.phase_code ?? 'P1',
  date_reunion:       props.fro?.date_reunion      ?? '',
  heure_debut:        props.fro?.heure_debut       ?? '',
  heure_fin:          props.fro?.heure_fin         ?? '',
  lieu:               props.fro?.lieu              ?? '',
  // Auto-rempli avec l'auditeur connecté si le champ est vide
  fait_par:           props.fro?.fait_par          || currentAuditorName.value,
  revue_par:          props.fro?.revue_par         ?? '',
  fichier_audio_path: props.fro?.fichier_audio_path ?? '',
  audio_data:         null as File | Blob | null,
  ordre_du_jour:   (Array.isArray(props.fro?.ordre_du_jour)
    ? props.fro.ordre_du_jour
    : safeJson(props.fro?.ordre_du_jour, [])) as any[],
  participants:    (Array.isArray(props.fro?.participants)
    ? props.fro.participants
    : safeJson(props.fro?.participants, [])) as any[],
  points_generaux: (Array.isArray(props.fro?.points_generaux)
    ? props.fro.points_generaux
    : safeJson(props.fro?.points_generaux, [])) as any[],
  preoccupations:  (Array.isArray(props.fro?.preoccupations)
    ? props.fro.preoccupations
    : safeJson(props.fro?.preoccupations, [])) as any[],
})

const formErrors  = reactive<Record<string, string>>({ ...props.errors })
const processing  = ref(false)
const search      = ref('')

function safeJson(val: any, fallback: any) {
  if (!val) return fallback
  try { return JSON.parse(val) } catch { return fallback }
}

// ══════════════════════════════════════════════════════════
// LISTE — FROs filtrés
// ══════════════════════════════════════════════════════════
const filteredFros = computed(() => {
  const q = search.value.toLowerCase()
  return (props.fros as any[]).filter(f =>
    !q ||
    f.code_fro?.toLowerCase().includes(q) ||
    f.code_mission?.toLowerCase().includes(q) ||
    f.lieu?.toLowerCase().includes(q)
  )
})

// ══════════════════════════════════════════════════════════
// GESTION TABLEAUX
// ══════════════════════════════════════════════════════════
function addRow(arr: any[], template: object) { arr.push({ ...template }) }
function removeRow(arr: any[], i: number) { arr.splice(i, 1) }

// ══════════════════════════════════════════════════════════
// AUDIO
// ══════════════════════════════════════════════════════════
const audioInput   = ref<HTMLInputElement | null>(null)
const recording    = ref(false)
const recTime      = ref('00:00')
const audioBlobUrl = ref<string | null>(null)
let mediaRecorder: MediaRecorder | null = null
let audioChunks: BlobPart[] = []
let timerInterval: ReturnType<typeof setInterval> | null = null
let seconds = 0

function onAudioFile(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  form.fichier_audio_path = file.name
  form.audio_data = file
}

async function startRec() {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true })
    mediaRecorder = new MediaRecorder(stream)
    audioChunks = []
    mediaRecorder.ondataavailable = e => audioChunks.push(e.data)
    mediaRecorder.onstop = () => {
      const blob = new Blob(audioChunks, { type: 'audio/webm' })
      audioBlobUrl.value = URL.createObjectURL(blob)
      form.audio_data = blob
    }
    mediaRecorder.start()
    recording.value = true
    seconds = 0
    timerInterval = setInterval(() => {
      seconds++
      recTime.value = `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`
    }, 1000)
  } catch (err: any) {
    showToast('error', 'Microphone inaccessible : ' + err.message)
  }
}

function stopRec() {
  mediaRecorder?.stop()
  recording.value = false
  if (timerInterval) clearInterval(timerInterval)
}

onBeforeUnmount(() => {
  stopRec()
  if (audioBlobUrl.value) URL.revokeObjectURL(audioBlobUrl.value)
})

// ══════════════════════════════════════════════════════════
// NAVIGATION / CRUD
// ══════════════════════════════════════════════════════════
function loadFro(f: any) {
  // URL construite côté PHP via formUrl ; on ajoute juste l'ID pour l'edit
  router.visit(`${props.formUrl}/${f.id}/edit?mission_id=${props.missionId ?? ''}&assignment_id=${props.assignmentId ?? ''}`)
}

function deleteFro(f: any) {
  if (!confirm(`Supprimer la réunion ${f.code_fro} ?`)) return
  router.delete(`${props.formUrl}/${f.id}`, {
    preserveScroll: true,
    data: { mission_id: props.missionId, assignment_id: props.assignmentId },
    onSuccess: () => showToast('success', 'Réunion supprimée'),
    onError:   () => showToast('error', 'Erreur lors de la suppression'),
  })
}

function annuler() {
  Object.assign(form, {
    id: null, code_fro: '', phase_code: props.assignment?.phase_code ?? 'P1',
    date_reunion: '', heure_debut: '', heure_fin: '', lieu: '',
    fait_par: currentAuditorName.value, revue_par: '', fichier_audio_path: '',
    audio_data: null,
    ordre_du_jour: [], participants: [], points_generaux: [], preoccupations: [],
  })
  audioBlobUrl.value = null
  Object.assign(fro, {})
}

// ══════════════════════════════════════════════════════════
// SUBMIT (store / update)
// ══════════════════════════════════════════════════════════
function submit() {
  if (isLocked.value) return
  processing.value = true
  Object.keys(formErrors).forEach(k => delete formErrors[k])

  const data = new FormData()
  ;['id','code_fro','phase_code','date_reunion','heure_debut','heure_fin','lieu','fait_par','revue_par'].forEach(k => {
    if (form[k as keyof typeof form] !== null && form[k as keyof typeof form] !== undefined)
      data.append(k, String(form[k as keyof typeof form]))
  })
  data.append('mission_id',    String(props.missionId))
  data.append('assignment_id', String(props.assignmentId))
  data.append('ordre_du_jour',   JSON.stringify(form.ordre_du_jour))
  data.append('participants',    JSON.stringify(form.participants))
  data.append('points_generaux', JSON.stringify(form.points_generaux))
  data.append('preoccupations',  JSON.stringify(form.preoccupations))
  if (form.audio_data) data.append('audio_file', form.audio_data as Blob, 'enregistrement.webm')

  const url = form.id ? `${props.formUrl}/${form.id}` : props.formUrl
  if (form.id) data.append('_method', 'PUT')

  router.post(url, data, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: (page: any) => {
      processing.value = false
      const newFro = page.props?.fro
      if (newFro) {
        if (!form.id) form.id = newFro.id
        if (newFro.code_fro) form.code_fro = newFro.code_fro
        Object.assign(fro, newFro)
      }
      showToast('success', 'Formulaire enregistré')
    },
    onError: (e: any) => {
      Object.assign(formErrors, e)
      processing.value = false
    },
    onFinish: () => { processing.value = false },
  })
}

// ══════════════════════════════════════════════════════════
// WORKFLOW VALIDATION
// ══════════════════════════════════════════════════════════
async function soumettre() {
  if (!form.id) { showToast('error', 'Enregistrez d\'abord le formulaire.'); return }
  if (!confirm('Soumettre ce formulaire pour validation par le DM ?')) return
  await apiPost(`${props.formUrl}/${form.id}/soumettre`, { mission_id: props.missionId, assignment_id: props.assignmentId }, (json: any) => {
    fro.validation_status = json.status
    showToast('success', 'Formulaire soumis — en attente validation DM')
  })
}

async function valider(action: 'validate' | 'reject', note?: string) {
  await apiPost(`${props.formUrl}/${form.id}/valider`,
    { mission_id: props.missionId, assignment_id: props.assignmentId, action, note },
    (json: any) => {
      fro.validation_status = json.status
      showToast('success', action === 'validate' ? 'Formulaire validé ✓' : 'Formulaire rejeté')
    }
  )
}

function promptReject() {
  const note = prompt('Motif du rejet (obligatoire) :')
  if (!note?.trim()) return
  valider('reject', note)
}

async function apiPost(url: string, body: object, onOk: (json: any) => void) {
  processing.value = true
  try {
    const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
      body: JSON.stringify(body),
    })
    const json = await res.json()
    if (!res.ok) throw new Error(json?.message ?? json?.error ?? 'Erreur')
    onOk(json)
  } catch (e: any) {
    showToast('error', e.message)
  } finally {
    processing.value = false
  }
}

// ══════════════════════════════════════════════════════════
// CHAT
// ══════════════════════════════════════════════════════════
const chatMsgEl    = ref<HTMLElement | null>(null)
const localMsgs    = ref<any[]>([...props.chatMessages])
const chatPanel    = ref({
  show: false, messages: localMsgs.value,
  draft: '', type: 'message', priority: 'normal', sending: false,
})
const unreadCount  = computed(() => localMsgs.value.filter(m => !m.is_read && !m.is_mine).length)

const PRIOS = [
  { v: 'normal',   l: 'Normal',   icon: 'ti ti-info-circle' },
  { v: 'urgent',   l: 'Urgent',   icon: 'ti ti-alert-triangle' },
  { v: 'bloquant', l: 'Bloquant', icon: 'ti ti-alert-octagon' },
]

function openChat() {
  chatPanel.value.show = true
  nextTick(() => { if (chatMsgEl.value) chatMsgEl.value.scrollTop = chatMsgEl.value.scrollHeight })
}

async function sendMsg() {
  const { draft, type, priority } = chatPanel.value
  if (!draft.trim()) return
  chatPanel.value.sending = true
  try {
    const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
    const res = await fetch(props.chatBaseUrl || '/api/mission-phase-chat', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
      body: JSON.stringify({
        assignment_id: props.assignmentId,
        mission_id:    props.missionId,
        form_code:     'reunion-ouverture',
        content: draft, type, priority,
      }),
    })
    const json = await res.json()
    if (!res.ok) throw new Error(json?.message ?? 'Erreur')
    localMsgs.value.push({ ...json.message, is_mine: true })
    chatPanel.value.messages = localMsgs.value
    chatPanel.value.draft = ''
    nextTick(() => { if (chatMsgEl.value) chatMsgEl.value.scrollTop = chatMsgEl.value.scrollHeight })
  } catch (e: any) {
    showToast('error', 'Erreur chat : ' + e.message)
  } finally {
    chatPanel.value.sending = false
  }
}

function chatTypeLbl(t: string) {
  return ({ instruction: 'Instruction', correction: 'Correction', validation: 'Validation', rejet: 'Rejet', info: 'Info' } as any)[t] ?? t
}

// ══════════════════════════════════════════════════════════
// HELPERS
// ══════════════════════════════════════════════════════════
const toast = ref({ show: false, type: 'success', msg: '' })
let toastTimer: ReturnType<typeof setTimeout> | null = null

function showToast(type: string, msg: string) {
  if (toastTimer) clearTimeout(toastTimer)
  toast.value = { show: true, type, msg }
  toastTimer = setTimeout(() => { toast.value.show = false }, 3200)
}

function vstLbl(s: string) {
  return ({ draft: 'Brouillon', in_review: 'En attente', validated: 'Validé ✓', rejected: 'Rejeté' } as any)[s] ?? s
}

function vstIcon(s: string) {
  return ({ draft: 'ti ti-pencil', in_review: 'ti ti-clock', validated: 'ti ti-circle-check', rejected: 'ti ti-circle-x' } as any)[s] ?? 'ti ti-circle'
}
</script>

<style scoped>
/* ── Variables ──────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* ── Shell ──────────────────────────────────────────── */
.fro-shell { display: flex; flex-direction: column; min-height: 100vh; font-family: 'Segoe UI', system-ui, sans-serif; background: #f4f6f8; }

/* ── Header sticky ──────────────────────────────────── */
.fro-header {
  position: sticky; top: 0; z-index: 100;
  background: #fff; border-bottom: 1px solid #e2e8f0;
  box-shadow: 0 1px 4px rgba(0,0,0,.06);
  padding: 0 20px;
}
.fro-hrow { display: flex; align-items: center; gap: 12px; min-height: 60px; flex-wrap: wrap; padding: 8px 0; }
.fro-back {
  display: flex; align-items: center; justify-content: center;
  width: 34px; height: 34px; border-radius: 8px;
  background: #f1f5f9; border: 1px solid #e2e8f0;
  color: #64748b; cursor: pointer; text-decoration: none; flex-shrink: 0;
  font-size: .9rem; transition: all .15s;
}
.fro-back:hover { background: var(--fc, #1565C0); color: #fff; border-color: var(--fc, #1565C0); }

.fro-hinfo { flex: 1; min-width: 0; }
.fro-chips { display: flex; align-items: center; gap: 5px; flex-wrap: wrap; margin-bottom: 3px; }
.fro-code {
  font-family: 'JetBrains Mono', monospace; font-size: .68rem; font-weight: 700;
  padding: 2px 8px; border-radius: 5px; border: 1px solid; letter-spacing: .04em;
}
.fro-vstchip {
  display: inline-flex; align-items: center; gap: 3px;
  font-size: .62rem; font-weight: 700; padding: 2px 8px; border-radius: 10px;
  text-transform: uppercase; letter-spacing: .04em;
}
.fvsc-draft     { background: rgba(100,116,139,.1); color: #64748b; }
.fvsc-in_review { background: #e3f2fd; color: #1565C0; border: 1px solid rgba(21,101,192,.2); }
.fvsc-validated { background: #d1e7dd; color: #0f5132; border: 1px solid rgba(15,81,50,.2); }
.fvsc-rejected  { background: #f8d7da; color: #842029; border: 1px solid rgba(132,32,41,.2); }

.fro-typechip {
  font-size: .6rem; font-weight: 700; padding: 2px 8px; border-radius: 10px;
  display: inline-flex; align-items: center; gap: 3px; letter-spacing: .04em;
}
.fro-rolechip {
  font-size: .6rem; font-weight: 700; padding: 2px 8px; border-radius: 10px;
  display: inline-flex; align-items: center; gap: 3px;
}
.rc-DM { background: rgba(251,191,36,.18); color: #d97706; }
.rc-CM { background: rgba(21,101,192,.12); color: #1565C0; }
.rc-AS { background: rgba(22,163,74,.12);  color: #15803d; }
.rc-AJ { background: rgba(124,58,237,.12); color: #6d28d9; }

.fro-htitle { font-size: .92rem; font-weight: 700; color: #1a1a2e; line-height: 1.2; }
.fro-hmeta  { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 2px; }
.fro-hmeta span { display: inline-flex; align-items: center; gap: 4px; font-size: .69rem; color: #64748b; }
.fro-hmeta i { font-size: .68rem; }

.fro-hbtns { display: flex; align-items: center; gap: 6px; margin-left: auto; }
.fro-hbtn {
  width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0;
  background: #f8fafc; cursor: pointer; display: flex; align-items: center;
  justify-content: center; font-size: .85rem; color: #64748b; transition: all .15s; position: relative;
}
.fro-hbtn-chat { color: #1565C0; background: #e3f2fd; border-color: #bbdefb; }
.fro-hbtn-chat:hover, .fro-hbtn-chat.unread { background: #1565C0; color: #fff; border-color: #1565C0; }
.fro-chatbadge {
  position: absolute; top: -5px; right: -5px; width: 15px; height: 15px;
  background: #dc3545; border-radius: 50%; font-size: .48rem; font-weight: 700;
  color: #fff; display: flex; align-items: center; justify-content: center; border: 2px solid #fff;
}

/* Banners header */
.fro-hbanner {
  display: flex; align-items: center; gap: 10px;
  padding: 7px 0 10px; font-size: .77rem; flex-wrap: wrap;
  border-top: 1px solid #f1f5f9;
}
.fro-hbanner i { font-size: .9rem; flex-shrink: 0; }
.fro-hbanner-lock   { color: #0f5132; }
.fro-hbanner-review { color: #1565C0; }
.fro-hbanner-warn   { color: #856404; }
.fro-link { font-weight: 600; text-decoration: underline; }

/* ── Body ────────────────────────────────────────────── */
.fro-body { flex: 1; padding: 20px; }
.fro-form {}
.fro-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 900px) { .fro-grid { grid-template-columns: 1fr; } }
.fro-col { display: flex; flex-direction: column; gap: 14px; }

/* ── Cards ───────────────────────────────────────────── */
.fro-card {
  background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
  position: relative; overflow: visible;
}
.fro-clabel {
  position: absolute; top: -10px; left: 14px; background: #fff;
  padding: 0 8px; font-size: .66rem; font-weight: 700; letter-spacing: .06em;
  text-transform: uppercase; border: 1px solid; border-radius: 4px;
  display: inline-flex; align-items: center; gap: 5px; z-index: 1; white-space: nowrap;
}
.fro-cbody {
  padding: 18px 14px 12px; display: flex; flex-direction: column; gap: 9px;
}
.fro-cbody-table { padding: 14px 0 0; overflow: hidden; border-radius: 0 0 10px 10px; }

/* ── Champs ──────────────────────────────────────────── */
.fro-lbl { font-size: .7rem; font-weight: 600; color: #475569; display: block; margin-bottom: 3px; }
.fro-req { color: #dc3545; }
.fro-inp {
  width: 100%; border: 1px solid #d1d5db; border-radius: 6px;
  padding: 6px 10px; font-size: .81rem; color: #1a1a2e;
  background: #fff; outline: none; transition: border-color .12s; font-family: inherit;
}
.fro-inp:focus { border-color: var(--fc, #1565C0); box-shadow: 0 0 0 3px color-mix(in srgb, var(--fc, #1565C0) 15%, transparent); }
.fro-inp.err { border-color: #dc3545; }
.fro-errmsg { font-size: .67rem; color: #dc3545; margin-top: 2px; }
.fro-ro { background: #f8fafc !important; cursor: default; color: #64748b; }
.fro-inp-sm { width: 150px; }
.fro-search { width: 200px; }
.fro-hidden { display: none; }
.fro-field { display: flex; flex-direction: column; }
.fro-field-i { display: flex; align-items: center; gap: 8px; }
.fro-field-i .fro-lbl { white-space: nowrap; margin: 0; }
.fro-r2 { display: grid; grid-template-columns: 1fr 1fr; gap: 9px; }
.fro-r3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 9px; }

/* ── Table header bande ──────────────────────────────── */
.fro-tbhd {
  display: flex; align-items: center; justify-content: space-between;
  padding: 6px 12px; margin-top: 10px;
}
.fro-tbhd:first-child { margin-top: 0; }
.fro-tbhd-2 { margin-top: 14px; }
.fro-tbhd span { font-size: .67rem; font-weight: 700; color: #fff; text-transform: uppercase; letter-spacing: .05em; }
.fro-tbadd {
  width: 21px; height: 21px; border-radius: 4px; background: rgba(255,255,255,.22);
  border: 1px solid rgba(255,255,255,.3); color: #fff; cursor: pointer;
  display: flex; align-items: center; justify-content: center; font-size: .72rem;
}
.fro-tbadd:hover { background: rgba(255,255,255,.38); }
.fro-listhd { padding: 8px 12px; }

/* ── Tables ──────────────────────────────────────────── */
.fro-tbl { width: 100%; border-collapse: collapse; font-size: .79rem; }
.fro-tblwrap { overflow-x: auto; }
.fro-tbl thead th {
  background: #1e3a5f; color: rgba(255,255,255,.85);
  font-size: .64rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em;
  padding: 7px 10px; border: none; white-space: nowrap;
}
.fro-tbl tbody td { padding: 5px 10px; border: 1px solid #e9ecef; vertical-align: middle; }
.fro-tbl tbody tr:hover td { background: #f8fafc; }
.fro-td-empty { text-align: center; color: #adb5bd; font-size: .74rem; padding: 12px !important; }
.fro-tdn     { text-align: center; color: #94a3b8; font-size: .7rem; }
.fro-tdcode  { font-weight: 700; color: var(--fc, #1565C0); font-size: .75rem; font-family: monospace; }
.fro-tdbold  { font-weight: 600; }
.fro-tdinp { width: 100%; border: none; outline: none; background: transparent; font-size: .79rem; font-family: inherit; padding: 0; color: #1a1a2e; }
.fro-tdro  { font-size: .79rem; color: #374151; }
.fro-tdacts { text-align: right; white-space: nowrap; }
.fro-delbtn { background: none; border: none; cursor: pointer; color: #ef4444; font-size: .72rem; padding: 2px 4px; }
.fro-delbtn:hover { color: #b91c1c; }
.fro-actbtn { display: inline-flex; align-items: center; justify-content: center; width: 25px; height: 25px; border-radius: 5px; border: none; cursor: pointer; font-size: .7rem; margin-left: 3px; }
.fro-actedit { background: #e3f2fd; color: #1565C0; }
.fro-actedit:hover { background: #1565C0; color: #fff; }
.fro-actdel { background: #fee2e2; color: #ef4444; }
.fro-actdel:hover { background: #ef4444; color: #fff; }
.fro-listrow { cursor: pointer; }

.fro-grade { font-size: .62rem; font-weight: 700; padding: 2px 7px; border-radius: 6px; }
.gr-DM { background: rgba(251,191,36,.18); color: #d97706; }
.gr-CM { background: rgba(21,101,192,.12); color: #1565C0; }
.gr-AS { background: rgba(22,163,74,.12); color: #15803d; }
.gr-AJ { background: rgba(124,58,237,.12); color: #6d28d9; }

/* ── Audio ───────────────────────────────────────────── */
.fro-audiorow { display: flex; gap: 8px; align-items: center; }
.fro-recrow { display: flex; align-items: center; gap: 10px; margin-top: 8px; flex-wrap: wrap; }
.fro-rectimer { font-size: .7rem; color: #ef4444; font-weight: 600; display: flex; align-items: center; gap: 4px; }
.fro-recdot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: currentColor; animation: blink 1s infinite; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:.2} }
.fro-audioplayer { margin-top: 8px; }
.fro-audioplayer audio { width: 100%; height: 34px; }

/* ── Boutons ─────────────────────────────────────────── */
.fro-btn {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 7px 14px; border-radius: 7px; font-size: .75rem; font-weight: 600;
  border: 1px solid transparent; cursor: pointer; transition: all .12s;
  font-family: inherit; text-decoration: none;
}
.fro-btn-sm { padding: 5px 10px; font-size: .69rem; }
.fro-btn-sec   { background: #f1f5f9; color: #475569; border-color: #d1d5db; }
.fro-btn-sec:hover { background: #e2e8f0; }
.fro-btn-ghost { background: transparent; color: #64748b; border-color: #d1d5db; }
.fro-btn-ghost:hover { background: #f1f5f9; color: #1a1a2e; }
.fro-btn-save  { color: #fff; border-color: transparent; }
.fro-btn-save:hover { filter: brightness(1.1); }
.fro-btn-submit  { background: #0f766e; color: #fff; border-color: #0f766e; }
.fro-btn-submit:hover  { background: #0d6460; }
.fro-btn-validate { background: #15803d; color: #fff; border-color: #15803d; }
.fro-btn-validate:hover { background: #166534; }
.fro-btn-reject  { background: #dc2626; color: #fff; border-color: #dc2626; }
.fro-btn-reject:hover  { background: #b91c1c; }
.fro-btn-rec   { background: #1565C0; color: #fff; border-color: #1565C0; }
.fro-btn-rec-on { background: #dc2626; color: #fff; border-color: #dc2626; }
.fro-btn:disabled { opacity: .5; cursor: not-allowed; }
.fro-spin { width: 12px; height: 12px; border: 2px solid rgba(255,255,255,.4); border-top-color: #fff; border-radius: 50%; animation: spin .6s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Footer ──────────────────────────────────────────── */
.fro-footer {
  display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
  padding: 12px 16px; background: #fff; border: 1px solid #e2e8f0;
  border-radius: 10px; margin-top: 12px;
  box-shadow: 0 1px 4px rgba(0,0,0,.05);
}
.fro-footer-meta { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
.fro-footer-acts { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-left: auto; }

/* ══ CHAT PANEL ══ */
.fro-chat-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.28); z-index: 400; }
.fro-chat-panel {
  position: fixed; top: 0; right: 0; bottom: 0; width: 370px; max-width: 95vw;
  background: #fff; border-left: 1px solid #e2e8f0; z-index: 401;
  display: flex; flex-direction: column; box-shadow: -4px 0 20px rgba(0,0,0,.12);
}
.slide-right-enter-active, .slide-right-leave-active { transition: transform .22s ease; }
.slide-right-enter-from, .slide-right-leave-to { transform: translateX(100%); }

.fro-chat-hd {
  display: flex; align-items: center; justify-content: space-between; gap: 10px;
  padding: 12px 14px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; flex-shrink: 0;
}
.fro-chat-hdinfo { display: flex; align-items: center; gap: 10px; min-width: 0; }
.fro-chat-av { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: .95rem; flex-shrink: 0; }
.fro-chat-title { display: block; font-size: .8rem; font-weight: 700; color: #1a1a2e; }
.fro-chat-sub   { display: block; font-size: .6rem; color: #94a3b8; font-family: monospace; }
.fro-chat-close { width: 26px; height: 26px; border-radius: 6px; background: #f1f5f9; border: 1px solid #e2e8f0; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: .72rem; }

.fro-chat-msgs { flex: 1; overflow-y: auto; padding: 12px; display: flex; flex-direction: column; gap: 8px; }
.fro-chat-msgs::-webkit-scrollbar { width: 3px; }
.fro-chat-empty { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 40px 20px; color: #cbd5e1; }
.fro-chat-empty i { font-size: 1.8rem; }
.fro-chat-empty p { font-size: .76rem; }

.fro-cmsg { display: flex; gap: 8px; }
.fro-cmsg.mine { flex-direction: row-reverse; }
.fro-cav { width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .5rem; font-weight: 700; flex-shrink: 0; }
.cav-DM { background: rgba(251,191,36,.22); color: #d97706; }
.cav-CM { background: rgba(21,101,192,.15); color: #1565C0; }
.cav-AS { background: rgba(22,163,74,.15); color: #15803d; }
.cav-AJ { background: rgba(124,58,237,.15); color: #6d28d9; }
.fro-cbody2 { flex: 1; min-width: 0; }
.fro-cmsg.mine .fro-cbody2 { align-items: flex-end; display: flex; flex-direction: column; }
.fro-cmeta { display: flex; align-items: center; gap: 5px; flex-wrap: wrap; margin-bottom: 3px; }
.fro-cmsg.mine .fro-cmeta { flex-direction: row-reverse; }
.fro-cwho { font-size: .64rem; font-weight: 600; }
.cr-DM { color: #d97706; }
.cr-CM { color: #1565C0; }
.cr-AS { color: #15803d; }
.cr-AJ { color: #6d28d9; }
.fro-crole { font-size: .54rem; color: #94a3b8; }
.fro-ctypetag { font-size: .54rem; padding: 1px 5px; border-radius: 3px; background: #e3f2fd; color: #1565C0; }
.fro-cpritag { font-size: .54rem; font-weight: 700; padding: 1px 5px; border-radius: 3px; }
.pp-urgent   { background: #fef9c3; color: #854d0e; }
.pp-bloquant { background: #fee2e2; color: #991b1b; }
.fro-cdate { font-size: .52rem; color: #94a3b8; }
.fro-ctxt { font-size: .75rem; color: #1e293b; line-height: 1.5; background: #f8fafc; padding: 7px 10px; border-radius: 8px; border: 1px solid #e9ecef; }
.fro-cmsg.mine .fro-ctxt { background: #e3f2fd; border-color: #bbdefb; }
.fp-bloquant .fro-cbody2 { padding-left: 6px; border-left: 2px solid #dc2626; }
.fp-urgent   .fro-cbody2 { padding-left: 6px; border-left: 2px solid #f59e0b; }

.fro-chat-compose { border-top: 1px solid #e2e8f0; padding: 10px 12px 12px; background: #f8fafc; flex-shrink: 0; display: flex; flex-direction: column; gap: 7px; }
.fro-chat-opts { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.fro-chat-sel { border: 1px solid #d1d5db; border-radius: 6px; padding: 4px 9px; font-size: .69rem; color: #1a1a2e; background: #fff; cursor: pointer; font-family: inherit; }
.fro-prios { display: flex; gap: 4px; }
.fro-priobtn { display: inline-flex; align-items: center; gap: 3px; padding: 3px 9px; border-radius: 20px; border: 1px solid #d1d5db; background: #fff; color: #64748b; font-size: .61rem; font-weight: 600; cursor: pointer; transition: all .1s; font-family: inherit; }
.fro-priobtn.active.ppb-normal   { background: #e3f2fd; color: #1565C0; border-color: #1565C0; }
.fro-priobtn.active.ppb-urgent   { background: #fef9c3; color: #854d0e; border-color: #f59e0b; }
.fro-priobtn.active.ppb-bloquant { background: #fee2e2; color: #991b1b; border-color: #dc2626; }
.fro-chat-row { display: flex; gap: 8px; align-items: flex-end; }
.fro-chat-ta { flex: 1; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 10px; font-size: .77rem; color: #1a1a2e; font-family: inherit; resize: none; outline: none; background: #fff; transition: border-color .12s; }
.fro-chat-ta:focus { border-color: var(--fc, #1565C0); }
.fro-chat-send { width: 34px; height: 34px; border-radius: 8px; border: none; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: .82rem; transition: filter .12s; }
.fro-chat-send:disabled { opacity: .35; cursor: not-allowed; }
.fro-chat-send:not(:disabled):hover { filter: brightness(1.12); }
.fro-chat-hint { font-size: .57rem; color: #94a3b8; }

/* ── Toast ───────────────────────────────────────────── */
.fro-toast {
  position: fixed; bottom: 24px; right: 24px; z-index: 500;
  display: flex; align-items: center; gap: 10px;
  padding: 11px 18px; border-radius: 10px; font-size: .8rem; font-weight: 600;
  box-shadow: 0 4px 16px rgba(0,0,0,.18);
  max-width: 340px;
}
.toast-success { background: #15803d; color: #fff; }
.toast-error   { background: #dc2626; color: #fff; }
.fade-up-enter-active, .fade-up-leave-active { transition: all .24s ease; }
.fade-up-enter-from, .fade-up-leave-to { opacity: 0; transform: translateY(10px); }

/* ══ DASHBOARD REPRISE ══════════════════════════════════ */
.fro-reprise {
  display: grid;
  grid-template-columns: 180px 1fr 220px;
  gap: 0;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  margin-bottom: 18px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,.05);
}
@media (max-width: 900px) { .fro-reprise { grid-template-columns: 1fr; } }

/* Colonne gauche : anneau + statut */
.frp-left {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 12px; padding: 20px 16px;
  background: #f8fafc; border-right: 1px solid #e2e8f0;
}
.frp-ring-wrap { position: relative; width: 78px; height: 78px; }
.frp-ring-svg  { width: 100%; height: 100%; transform: rotate(-90deg); }
.frp-ring-inner {
  position: absolute; inset: 0; display: flex; flex-direction: column;
  align-items: center; justify-content: center;
}
.frp-pct    { font-size: .92rem; font-weight: 800; line-height: 1; }
.frp-pctsub { font-size: .54rem; color: #94a3b8; margin-top: 1px; }
.frp-vst-block { display: flex; flex-direction: column; align-items: center; gap: 5px; }
.frp-code { font-family: monospace; font-size: .68rem; font-weight: 700; letter-spacing: .04em; }
.frp-date { font-size: .65rem; color: #64748b; display: flex; align-items: center; gap: 4px; }
.frp-date i { font-size: .62rem; }

/* Colonne milieu : checklist */
.frp-checks {
  padding: 16px 18px; border-right: 1px solid #e2e8f0;
}
.frp-checks-title {
  font-size: .62rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .08em; color: #94a3b8; margin-bottom: 10px;
}
.frp-checklist { display: flex; flex-direction: column; gap: 6px; }
.frp-chk {
  display: flex; align-items: center; gap: 8px;
  font-size: .75rem; color: #94a3b8;
  transition: color .15s;
}
.frp-chk.done { color: #1e293b; }
.frp-chk-dot {
  width: 14px; height: 14px; border-radius: 50%; flex-shrink: 0;
  border: 1.5px solid #cbd5e1; background: #f1f5f9;
  position: relative; transition: all .15s;
}
.frp-chk.done .frp-chk-dot {
  background: #15803d; border-color: #15803d;
}
.frp-chk.done .frp-chk-dot::after {
  content: '';
  position: absolute; inset: 0; margin: auto;
  width: 5px; height: 3px;
  border-left: 1.5px solid #fff; border-bottom: 1.5px solid #fff;
  transform: rotate(-45deg) translate(1px, -1px);
}
.frp-chk-lbl { flex: 1; font-weight: 500; }
.frp-chk-cnt { font-size: .67rem; color: #64748b; background: #f1f5f9; padding: 1px 6px; border-radius: 8px; }
.frp-chk.done .frp-chk-cnt { background: #dcfce7; color: #15803d; }
.frp-chk-val { font-size: .67rem; color: #94a3b8; }
.frp-chk.done .frp-chk-val { color: #15803d; }

/* Colonne droite : actions + lastmod */
.frp-right { padding: 16px 16px 14px; display: flex; flex-direction: column; }
.frp-next-title {
  font-size: .62rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .08em; color: #94a3b8; margin-bottom: 10px;
}
.frp-next-list { display: flex; flex-direction: column; gap: 6px; flex: 1; }
.frp-next-item {
  display: flex; align-items: flex-start; gap: 8px;
  font-size: .73rem; padding: 6px 10px; border-radius: 7px;
  border-left: 3px solid transparent;
}
.frp-next-item i { font-size: .8rem; flex-shrink: 0; margin-top: 1px; }
.frp-next-todo {
  background: #fff7ed; color: #9a3412;
  border-left-color: #f97316;
}
.frp-next-ok {
  background: #f0fdf4; color: #15803d;
  border-left-color: #22c55e;
}
.frp-next-done {
  background: #d1fae5; color: #065f46;
  border-left-color: #10b981;
}
.frp-next-wait {
  background: #eff6ff; color: #1d4ed8;
  border-left-color: #3b82f6;
}
.frp-lastmod {
  display: flex; align-items: center; gap: 5px;
  font-size: .61rem; color: #94a3b8; margin-top: 10px;
  padding-top: 8px; border-top: 1px solid #f1f5f9;
}


/* Bouton PDF */
.fro-btn-pdf {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: .8rem; font-weight: 600; padding: 8px 16px;
  border-radius: 8px; background: #dc2626; color: #fff;
  text-decoration: none; transition: all .15s;
}
.fro-btn-pdf:hover { background: #b91c1c; }

</style>