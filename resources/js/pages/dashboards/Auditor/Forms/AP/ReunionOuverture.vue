<template>
  <VerticalLayoutAudit>
    <div class="apro-shell">

      <!-- ══════════════════════════════════════════════
           HEADER sticky — identité Audit de Performance
      ══════════════════════════════════════════════ -->
      <header class="apro-header" :style="`--fc:${mc};--fcl:${mc}18;--fcm:${mc}35`">
        <div class="apro-hrow">

          <a :href="props.backUrl" class="apro-back" title="Retour aux phases">
            <i class="ti ti-arrow-left"></i>
          </a>

          <div class="apro-hinfo">
            <div class="apro-chips">
              <code class="apro-code" :style="`color:${mc};background:${mc}15;border-color:${mc}30`">
                {{ mission?.code_mission ?? '—' }}
              </code>
              <span v-if="fro?.validation_status" class="apro-vstchip" :class="`avsc-${fro.validation_status}`">
                <i :class="vstIcon(fro.validation_status)"></i>
                {{ vstLbl(fro.validation_status) }}
              </span>
              <span class="apro-typechip" :style="`color:${mc};background:${mc}12`">
                <i class="ti ti-chart-arrows-vertical"></i>
                FRO · {{ mission?.audit_type_label || 'Audit de Performance' }}
              </span>
              <span v-if="props.auditorRole" class="apro-rolechip" :class="`rc-${props.auditorRole}`">
                <i class="ti ti-shield-half"></i> {{ props.auditorRole }}
              </span>
            </div>
            <h1 class="apro-htitle">Réunion d'Ouverture</h1>
            <div class="apro-hmeta">
              <span v-if="assignment?.phase_label"><i class="ti ti-git-branch"></i>{{ assignment.phase_label }}</span>
              <span v-if="mission?.entity_name"><i class="ti ti-building"></i>{{ mission.entity_name }}</span>
              <span v-if="mission?.date_debut_fr"><i class="ti ti-calendar"></i>{{ mission.date_debut_fr }} — {{ mission.date_fin_fr }}</span>
              <span v-if="mission?.lieux"><i class="ti ti-map-pin"></i>{{ mission.lieux }}</span>
            </div>
          </div>

          <div class="apro-hbtns">
            <a v-if="form.id" :href="`${props.formUrl}/${form.id}/pdf?download=1`"
              class="apro-hbtn" title="Télécharger le PDF">
              <i class="ti ti-file-type-pdf"></i>
            </a>
            <button class="apro-hbtn apro-hbtn-chat" :class="{ unread: unreadCount > 0 }" @click="chatOpen = true">
              <i class="ti ti-message-circle"></i>
              <span v-if="unreadCount > 0" class="apro-chatbadge">{{ unreadCount }}</span>
            </button>
          </div>
        </div>

        <!-- Bandeaux d'état -->
        <div v-if="fro?.validation_status === 'validated'" class="apro-hbanner apro-hbanner-lock">
          <i class="ti ti-lock"></i>
          <span>Formulaire <strong>validé définitivement</strong> par le DM — lecture seule</span>
        </div>
        <div v-else-if="fro?.validation_status === 'in_review'" class="apro-hbanner apro-hbanner-review">
          <i class="ti ti-clock"></i>
          <span>Soumis pour validation — en attente DM. <span v-if="canManage">Vous pouvez valider ou rejeter.</span></span>
        </div>
        <div v-if="props.noMission" class="apro-hbanner apro-hbanner-warn">
          <i class="ti ti-alert-triangle"></i>
          <span>Aucune mission active. <a :href="props.backUrl" class="apro-link">Retour aux missions</a></span>
        </div>
        <div v-if="props.phaseNotStarted" class="apro-hbanner apro-hbanner-warn">
          <i class="ti ti-player-pause"></i>
          <span>Cette phase n'est pas encore démarrée. Démarrez-la depuis les phases de mission.</span>
        </div>
      </header>

      <!-- ══ BODY ══ -->
      <div v-if="!props.noMission && !props.phaseNotStarted" class="apro-body">
        <form @submit.prevent="submit" class="apro-form">

          <!-- ══ DASHBOARD REPRISE (si une fiche existe) ══ -->
          <div v-if="form.id" class="apro-reprise">
            <div class="arp-left">
              <div class="arp-ring-wrap">
                <svg viewBox="0 0 42 42" class="arp-ring-svg">
                  <circle cx="21" cy="21" r="17" fill="none" stroke="#e2e8f0" stroke-width="3.2"/>
                  <circle cx="21" cy="21" r="17" fill="none"
                    :stroke="mc" stroke-width="3.2" stroke-linecap="round"
                    :stroke-dasharray="`${progress} ${100 - progress}`"
                    stroke-dashoffset="25"
                    style="transition: stroke-dasharray .5s ease"/>
                </svg>
                <div class="arp-ring-inner">
                  <span class="arp-pct" :style="`color:${mc}`">{{ progress }}%</span>
                  <span class="arp-pctsub">rempli</span>
                </div>
              </div>
              <div class="arp-vst-block">
                <span class="arp-vst" :class="`avsc-${fro.validation_status || 'draft'}`">
                  <i :class="vstIcon(fro.validation_status || 'draft')"></i>
                  {{ vstLbl(fro.validation_status || 'draft') }}
                </span>
                <span class="arp-code" :style="`color:${mc}`">{{ form.code_fro }}</span>
              </div>
            </div>

            <div class="arp-checks">
              <div class="arp-checks-title">Avancement des sections</div>
              <div class="arp-checklist">
                <div class="arp-chk" :class="{ done: form.date_reunion && form.lieu }">
                  <span class="arp-chk-dot"></span><span class="arp-chk-lbl">Info réunion</span>
                  <span v-if="form.date_reunion" class="arp-chk-val">{{ form.date_reunion }}</span>
                </div>
                <div class="arp-chk" :class="{ done: form.ordre_du_jour.length > 0 }">
                  <span class="arp-chk-dot"></span><span class="arp-chk-lbl">Ordre du jour</span>
                  <span class="arp-chk-cnt">{{ form.ordre_du_jour.length }} pt</span>
                </div>
                <div class="arp-chk" :class="{ done: form.participants.length > 0 }">
                  <span class="arp-chk-dot"></span><span class="arp-chk-lbl">Participants</span>
                  <span class="arp-chk-cnt">{{ form.participants.length }} pers.</span>
                </div>
                <div class="arp-chk" :class="{ done: form.points_generaux.length > 0 }">
                  <span class="arp-chk-dot"></span><span class="arp-chk-lbl">Points performance</span>
                  <span class="arp-chk-cnt">{{ form.points_generaux.length }} pt</span>
                </div>
                <div class="arp-chk" :class="{ done: form.preoccupations.length > 0 }">
                  <span class="arp-chk-dot"></span><span class="arp-chk-lbl">Préoccupations</span>
                  <span class="arp-chk-cnt">{{ form.preoccupations.length }} élémt.</span>
                </div>
              </div>
            </div>

            <div class="arp-right">
              <div class="arp-next-title">À faire maintenant</div>
              <div class="arp-next-list">
                <template v-if="(fro.validation_status || 'draft') === 'draft'">
                  <div v-if="!form.date_reunion || !form.lieu" class="arp-next-item arp-next-todo">
                    <i class="ti ti-edit"></i><span>Renseigner la date et le lieu</span>
                  </div>
                  <div v-if="form.ordre_du_jour.length === 0" class="arp-next-item arp-next-todo">
                    <i class="ti ti-list-numbers"></i><span>Ajouter l'ordre du jour</span>
                  </div>
                  <div v-if="form.participants.length === 0" class="arp-next-item arp-next-todo">
                    <i class="ti ti-users"></i><span>Lister les participants</span>
                  </div>
                  <div v-if="progress >= 80" class="arp-next-item arp-next-ok">
                    <i class="ti ti-send"></i><span>Fiche prête à soumettre au DM</span>
                  </div>
                </template>
                <template v-else-if="fro.validation_status === 'in_review'">
                  <div class="arp-next-item arp-next-wait">
                    <i class="ti ti-clock"></i><span>En attente de validation DM/CM</span>
                  </div>
                  <div v-if="canManage" class="arp-next-item arp-next-ok">
                    <i class="ti ti-shield-check"></i><span>Vous pouvez valider ou rejeter</span>
                  </div>
                </template>
                <template v-else-if="fro.validation_status === 'validated'">
                  <div class="arp-next-item arp-next-done">
                    <i class="ti ti-circle-check"></i><span>Fiche validée — phase clôturée</span>
                  </div>
                </template>
              </div>
            </div>
          </div>

          <!-- ══ GRILLE 2 COLONNES ══ -->
          <div class="apro-grid">

            <!-- ── COLONNE GAUCHE ── -->
            <div class="apro-col">

              <!-- Info mission (lecture seule) -->
              <section class="apro-card">
                <div class="apro-clabel" :style="`color:${mc};border-color:${mc}25`">
                  <i class="ti ti-briefcase"></i> Info mission
                </div>
                <div class="apro-cbody apro-mission-grid">
                  <div class="apro-ro"><em>Code</em><strong>{{ mission?.code_mission ?? '—' }}</strong></div>
                  <div class="apro-ro"><em>Type</em><strong>{{ mission?.audit_type_label ?? 'Audit de Performance' }}</strong></div>
                  <div class="apro-ro apro-ro-full"><em>Libellé</em><strong>{{ mission?.libelle ?? '—' }}</strong></div>
                  <div class="apro-ro"><em>Entité</em><strong>{{ mission?.entity_name ?? '—' }}</strong></div>
                  <div class="apro-ro"><em>Période</em><strong>{{ mission?.date_debut_fr ?? '—' }} → {{ mission?.date_fin_fr ?? '—' }}</strong></div>
                  <div v-if="mission?.objectif" class="apro-ro apro-ro-full"><em>Objectif</em><strong>{{ mission.objectif }}</strong></div>
                </div>
              </section>

              <!-- Informations réunion -->
              <section class="apro-card">
                <div class="apro-clabel" :style="`color:${mc};border-color:${mc}25`">
                  <i class="ti ti-calendar-event"></i> Informations réunion
                </div>
                <div class="apro-cbody">
                  <div class="apro-frow apro-frow-2">
                    <div class="apro-field">
                      <label>Date de la réunion <b>*</b></label>
                      <input v-model="form.date_reunion" type="date" :disabled="isLocked" required />
                      <small v-if="formErrors.date_reunion" class="apro-err">{{ formErrors.date_reunion }}</small>
                    </div>
                    <div class="apro-field">
                      <label>Lieu <b>*</b></label>
                      <input v-model="form.lieu" type="text" placeholder="Salle, site, visio…" :disabled="isLocked" required />
                      <small v-if="formErrors.lieu" class="apro-err">{{ formErrors.lieu }}</small>
                    </div>
                  </div>
                  <div class="apro-frow apro-frow-2">
                    <div class="apro-field">
                      <label>Heure début</label>
                      <input v-model="form.heure_debut" type="time" :disabled="isLocked" />
                    </div>
                    <div class="apro-field">
                      <label>Heure fin</label>
                      <input v-model="form.heure_fin" type="time" :disabled="isLocked" />
                    </div>
                  </div>
                  <div class="apro-frow apro-frow-2">
                    <div class="apro-field">
                      <label>Fait par</label>
                      <input v-model="form.fait_par" type="text" list="apro-auditeurs" :disabled="isLocked" />
                    </div>
                    <div class="apro-field">
                      <label>Revue par</label>
                      <input v-model="form.revue_par" type="text" list="apro-auditeurs" placeholder="CM / DM…" :disabled="isLocked" />
                    </div>
                    <datalist id="apro-auditeurs">
                      <option v-for="a in (props.auditeurs as any[])" :key="a.id"
                        :value="`${a.nom ?? ''} ${a.prenom ?? ''}`.trim()">{{ a.grade }}</option>
                    </datalist>
                  </div>
                </div>
              </section>

              <!-- Cadrage performance — guide 3E -->
              <section class="apro-card apro-card-3e" :style="`border-color:${mc}30`">
                <div class="apro-clabel" :style="`color:${mc};border-color:${mc}25`">
                  <i class="ti ti-target-arrow"></i> Cadrage performance — les 3E
                </div>
                <div class="apro-cbody">
                  <p class="apro-3e-intro">
                    Points à présenter en réunion d'ouverture d'un audit de performance :
                  </p>
                  <div class="apro-3e-grid">
                    <div class="apro-3e" :style="`border-color:${mc}25`">
                      <span class="apro-3e-t" :style="`color:${mc}`"><i class="ti ti-coin"></i> Économie</span>
                      <span class="apro-3e-d">Minimiser le coût des ressources (acquisition au meilleur coût)</span>
                    </div>
                    <div class="apro-3e" :style="`border-color:${mc}25`">
                      <span class="apro-3e-t" :style="`color:${mc}`"><i class="ti ti-settings-bolt"></i> Efficience</span>
                      <span class="apro-3e-d">Optimiser le rapport ressources engagées / résultats obtenus</span>
                    </div>
                    <div class="apro-3e" :style="`border-color:${mc}25`">
                      <span class="apro-3e-t" :style="`color:${mc}`"><i class="ti ti-flag-check"></i> Efficacité</span>
                      <span class="apro-3e-d">Atteindre les objectifs fixés et les résultats attendus</span>
                    </div>
                  </div>
                </div>
              </section>

              <!-- Ordre du jour -->
              <section class="apro-card">
                <div class="apro-clabel" :style="`color:${mc};border-color:${mc}25`">
                  <i class="ti ti-list-numbers"></i> Ordre du jour
                  <span class="apro-cnt">{{ form.ordre_du_jour.length }}</span>
                </div>
                <div class="apro-cbody">
                  <!-- Suggestions AP (1 clic) -->
                  <div v-if="!isLocked" class="apro-sugg">
                    <button v-for="s in ODJ_SUGGESTIONS" :key="s" type="button"
                      class="apro-sugg-chip" :style="`border-color:${mc}30;color:${mc}`"
                      :disabled="form.ordre_du_jour.some((o:any) => o.point === s)"
                      @click="form.ordre_du_jour.push({ point: s })">
                      <i class="ti ti-plus"></i> {{ s }}
                    </button>
                  </div>
                  <div v-for="(o, i) in form.ordre_du_jour" :key="i" class="apro-row">
                    <span class="apro-row-n" :style="`background:${mc}12;color:${mc}`">{{ i + 1 }}</span>
                    <input v-model="o.point" type="text" placeholder="Point de l'ordre du jour…" :disabled="isLocked" />
                    <button v-if="!isLocked" type="button" class="apro-row-del" @click="form.ordre_du_jour.splice(i, 1)">
                      <i class="ti ti-trash"></i>
                    </button>
                  </div>
                  <button v-if="!isLocked" type="button" class="apro-add" :style="`color:${mc};border-color:${mc}35`"
                    @click="form.ordre_du_jour.push({ point: '' })">
                    <i class="ti ti-plus"></i> Ajouter un point
                  </button>
                </div>
              </section>
            </div>

            <!-- ── COLONNE DROITE ── -->
            <div class="apro-col">

              <!-- Participants -->
              <section class="apro-card">
                <div class="apro-clabel" :style="`color:${mc};border-color:${mc}25`">
                  <i class="ti ti-users"></i> Participants
                  <span class="apro-cnt">{{ form.participants.length }}</span>
                </div>
                <div class="apro-cbody">
                  <div v-for="(p, i) in form.participants" :key="i" class="apro-part">
                    <div class="apro-part-head">
                      <span class="apro-part-av" :style="`background:${mc}15;color:${mc}`">
                        {{ ((p.nom?.[0] ?? '') + (p.prenom?.[0] ?? '')).toUpperCase() || (i + 1) }}
                      </span>
                      <button v-if="!isLocked" type="button" class="apro-row-del" @click="form.participants.splice(i, 1)">
                        <i class="ti ti-trash"></i>
                      </button>
                    </div>
                    <div class="apro-frow apro-frow-2">
                      <div class="apro-field"><label>Nom</label>
                        <input v-model="p.nom" type="text" :disabled="isLocked" /></div>
                      <div class="apro-field"><label>Prénom</label>
                        <input v-model="p.prenom" type="text" :disabled="isLocked" /></div>
                    </div>
                    <div class="apro-frow apro-frow-2">
                      <div class="apro-field"><label>Fonction</label>
                        <input v-model="p.fonction" type="text" placeholder="DG, DAF, Resp. production…" :disabled="isLocked" /></div>
                      <div class="apro-field"><label>Observation</label>
                        <input v-model="p.observation" type="text" :disabled="isLocked" /></div>
                    </div>
                  </div>
                  <button v-if="!isLocked" type="button" class="apro-add" :style="`color:${mc};border-color:${mc}35`"
                    @click="form.participants.push({ nom: '', prenom: '', fonction: '', observation: '' })">
                    <i class="ti ti-user-plus"></i> Ajouter un participant
                  </button>
                </div>
              </section>

              <!-- Points généraux (performance) -->
              <section class="apro-card">
                <div class="apro-clabel" :style="`color:${mc};border-color:${mc}25`">
                  <i class="ti ti-chart-line"></i> Points performance abordés
                  <span class="apro-cnt">{{ form.points_generaux.length }}</span>
                </div>
                <div class="apro-cbody">
                  <div v-for="(pt, i) in form.points_generaux" :key="i" class="apro-row">
                    <span class="apro-row-n" :style="`background:${mc}12;color:${mc}`"><i class="ti ti-point"></i></span>
                    <input v-model="pt.libelle" type="text"
                      placeholder="KPI retenus, sources de données, cibles, référentiels…" :disabled="isLocked" />
                    <button v-if="!isLocked" type="button" class="apro-row-del" @click="form.points_generaux.splice(i, 1)">
                      <i class="ti ti-trash"></i>
                    </button>
                  </div>
                  <button v-if="!isLocked" type="button" class="apro-add" :style="`color:${mc};border-color:${mc}35`"
                    @click="form.points_generaux.push({ libelle: '' })">
                    <i class="ti ti-plus"></i> Ajouter un point
                  </button>
                </div>
              </section>

              <!-- Préoccupations de l'audité -->
              <section class="apro-card">
                <div class="apro-clabel" :style="`color:${mc};border-color:${mc}25`">
                  <i class="ti ti-message-question"></i> Préoccupations de l'audité
                  <span class="apro-cnt">{{ form.preoccupations.length }}</span>
                </div>
                <div class="apro-cbody">
                  <div v-for="(pr, i) in form.preoccupations" :key="i" class="apro-row">
                    <span class="apro-row-n" :style="`background:${mc}12;color:${mc}`"><i class="ti ti-help"></i></span>
                    <input v-model="pr.libelle" type="text" placeholder="Question ou préoccupation soulevée…" :disabled="isLocked" />
                    <button v-if="!isLocked" type="button" class="apro-row-del" @click="form.preoccupations.splice(i, 1)">
                      <i class="ti ti-trash"></i>
                    </button>
                  </div>
                  <button v-if="!isLocked" type="button" class="apro-add" :style="`color:${mc};border-color:${mc}35`"
                    @click="form.preoccupations.push({ libelle: '' })">
                    <i class="ti ti-plus"></i> Ajouter une préoccupation
                  </button>
                </div>
              </section>

              <!-- Historique des fiches de cet assignment -->
              <section v-if="(props.fros as any[]).length" class="apro-card">
                <div class="apro-clabel" :style="`color:${mc};border-color:${mc}25`">
                  <i class="ti ti-history"></i> Historique des fiches
                  <span class="apro-cnt">{{ (props.fros as any[]).length }}</span>
                </div>
                <div class="apro-cbody">
                  <div v-for="f in (props.fros as any[])" :key="f.id"
                    class="apro-hist" :class="{ on: f.id === form.id }">
                    <code :style="`color:${mc}`">{{ f.code_fro }}</code>
                    <span class="apro-hist-date">{{ f.date_reunion_fr ?? f.date_reunion ?? '—' }}</span>
                    <span class="apro-hist-lieu">{{ f.lieu ?? '—' }}</span>
                    <span class="apro-vstchip" :class="`avsc-${f.status ?? 'draft'}`">{{ vstLbl(f.status ?? 'draft') }}</span>
                    <span class="apro-hist-btns">
                      <button type="button" class="apro-hist-btn" title="Ouvrir" @click="loadFro(f)">
                        <i class="ti ti-external-link"></i>
                      </button>
                      <button v-if="canManage && f.status !== 'validated'" type="button"
                        class="apro-hist-btn apro-hist-del" title="Supprimer" @click="deleteFro(f)">
                        <i class="ti ti-trash"></i>
                      </button>
                    </span>
                  </div>
                </div>
              </section>
            </div>
          </div>

          <!-- ══ BARRE D'ACTIONS ══ -->
          <div class="apro-actions">
            <div class="apro-actions-left">
              <span v-if="form.code_fro" class="apro-act-code">
                <i class="ti ti-clipboard-text"></i> {{ form.code_fro }}
              </span>
            </div>
            <div class="apro-actions-right">
              <button type="button" class="apro-btn apro-btn-ghost" :disabled="processing" @click="annuler">
                <i class="ti ti-eraser"></i> Réinitialiser
              </button>
              <button v-if="!isLocked" type="submit"
                class="apro-btn apro-btn-save" :style="`background:${mc}`" :disabled="processing">
                <i :class="processing ? 'ti ti-loader-2 apro-spin' : 'ti ti-device-floppy'"></i>
                {{ form.id ? 'Enregistrer' : 'Créer la fiche' }}
              </button>
              <button v-if="form.id && (fro.validation_status || 'draft') === 'draft'"
                type="button" class="apro-btn apro-btn-submitrev" :disabled="processing" @click="soumettre">
                <i class="ti ti-send"></i> Soumettre au DM
              </button>
              <template v-if="canManage && fro.validation_status === 'in_review'">
                <button type="button" class="apro-btn apro-btn-reject" :disabled="processing" @click="promptReject">
                  <i class="ti ti-x"></i> Rejeter
                </button>
                <button type="button" class="apro-btn apro-btn-validate" :disabled="processing" @click="valider('validate')">
                  <i class="ti ti-shield-check"></i> Valider définitivement
                </button>
              </template>
            </div>
          </div>
        </form>
      </div>

      <!-- ══ PANNEAU CHAT ══ -->
      <Teleport to="body">
        <transition name="apro-slide">
          <div v-if="chatOpen" class="apro-chat">
            <div class="apro-chat-hd" :style="`border-color:${mc}30`">
              <i class="ti ti-message-circle" :style="`color:${mc}`"></i>
              <div class="apro-chat-hd-t">
                <strong>Chat — Réunion d'ouverture</strong>
                <span>{{ localMsgs.length }} message(s)</span>
              </div>
              <button class="apro-chat-x" @click="chatOpen = false"><i class="ti ti-x"></i></button>
            </div>
            <div ref="chatListEl" class="apro-chat-msgs">
              <div v-if="!localMsgs.length" class="apro-chat-empty">
                <i class="ti ti-messages"></i>
                <p>Aucun message pour ce formulaire.</p>
              </div>
              <div v-for="m in localMsgs" :key="m.id"
                class="apro-msg" :class="{ mine: m.is_mine }">
                <span class="apro-msg-av" :class="`rc-${m.author_role}`">{{ m.author_initials ?? '–' }}</span>
                <div class="apro-msg-body">
                  <div class="apro-msg-meta">
                    <strong>{{ m.author_name }}</strong>
                    <em>{{ m.author_role }}</em>
                    <span>{{ m.created_at_fr }}</span>
                  </div>
                  <p>{{ m.content }}</p>
                </div>
              </div>
            </div>
            <div class="apro-chat-compose">
              <textarea v-model="chatDraft" rows="2"
                placeholder="Écrivez… (Ctrl+Entrée pour envoyer)"
                @keydown.ctrl.enter.prevent="sendChat"></textarea>
              <button class="apro-chat-send" :style="`background:${mc}`"
                :disabled="!chatDraft.trim() || chatSending" @click="sendChat">
                <i :class="chatSending ? 'ti ti-loader-2 apro-spin' : 'ti ti-send'"></i>
              </button>
            </div>
          </div>
        </transition>
        <div v-if="chatOpen" class="apro-chat-ovl" @click="chatOpen = false"></div>
      </Teleport>

      <!-- ══ TOAST ══ -->
      <Teleport to="body">
        <transition name="apro-toast">
          <div v-if="toast.show" class="apro-toast" :class="`apro-toast-${toast.type}`">
            <i :class="toast.type === 'success' ? 'ti ti-circle-check' : 'ti ti-circle-x'"></i>
            {{ toast.msg }}
          </div>
        </transition>
      </Teleport>

    </div>
  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
// ════════════════════════════════════════════════════════════════════
// AP (Audit de Performance) — Réunion d'ouverture
// Même contrat que Forms/ReunionOuverture.vue (AC) : toutes les URLs
// viennent du contrôleur (formUrl/backUrl/chatBaseUrl), même table
// mission_phase_fros, mêmes clés JSON. Identité AP : couleur du type
// (#059669 via mission.audit_color), cadrage 3E, suggestions d'ODJ
// orientées indicateurs/méthodologie.
// ════════════════════════════════════════════════════════════════════
import { computed, nextTick, reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'

const props = defineProps({
  mission:      { type: Object,  default: null },
  assignment:   { type: Object,  default: null },
  auditeurs:    { type: Array,   default: () => [] },
  fro:          { type: Object,  default: null },
  fros:         { type: Array,   default: () => [] },
  chatMessages: { type: Array,   default: () => [] },
  errors:       { type: Object,  default: () => ({}) },

  noMission:       { type: Boolean, default: false },
  phaseNotStarted: { type: Boolean, default: false },
  auditorRole:     { type: String,  default: null },

  missionId:      { type: Number, default: null },
  assignmentId:   { type: Number, default: null },
  currentAuditor: { type: Object, default: null },

  // URLs pré-construites côté PHP (ApReunionOuvertureController)
  backUrl:     { type: String, default: '' },  // /m/audit.core/auditor/missions/{id}/phases
  formUrl:     { type: String, default: '' },  // /m/audit.core/ap/preparation/reunion-ouverture
  chatBaseUrl: { type: String, default: '' },  // /m/audit.core/missions/{id}/chat/PREPARATION
})

// ── Couleur : audit_color AP (#059669) fournie par le contrôleur ──────────
const mc = computed<string>(() => {
  const c = (props.mission as any)?.audit_color
  if (c && c !== '#000000' && c !== '#000' && c !== 'null') return c
  return '#059669'
})

const canManage = computed(() => ['DM', 'CM'].includes(props.auditorRole ?? ''))

// ── Suggestions d'ordre du jour spécifiques Audit de Performance ──────────
const ODJ_SUGGESTIONS = [
  'Présentation des objectifs de la mission',
  'Indicateurs et KPIs retenus',
  'Méthodologie 3E (économie, efficience, efficacité)',
  'Sources et accès aux données',
  'Calendrier et jalons de la mission',
]

// ── État réactif ──────────────────────────────────────────────────────────
const fro = reactive<Record<string, any>>(props.fro ? { ...props.fro } : {})

function safeJson(val: any, fallback: any) {
  if (!val) return fallback
  if (Array.isArray(val)) return val
  try { return JSON.parse(val) } catch { return fallback }
}

const currentAuditorName = computed<string>(() => {
  const a = props.currentAuditor as any
  if (!a) return ''
  return [a.last_name, a.first_name].filter(Boolean).join(' ').trim() || a.audit_code || ''
})

const form = reactive({
  id:            (props.fro as any)?.id            ?? null,
  code_fro:      (props.fro as any)?.code_fro      ?? '',
  phase_code:    (props.fro as any)?.phase_code    ?? (props.assignment as any)?.phase_code ?? 'P1',
  date_reunion:  (props.fro as any)?.date_reunion  ?? '',
  heure_debut:   (props.fro as any)?.heure_debut   ?? '',
  heure_fin:     (props.fro as any)?.heure_fin     ?? '',
  lieu:          (props.fro as any)?.lieu          ?? '',
  fait_par:      (props.fro as any)?.fait_par      || currentAuditorName.value,
  revue_par:     (props.fro as any)?.revue_par     ?? '',
  ordre_du_jour:   safeJson((props.fro as any)?.ordre_du_jour,   []) as any[],
  participants:    safeJson((props.fro as any)?.participants,    []) as any[],
  points_generaux: safeJson((props.fro as any)?.points_generaux, []) as any[],
  preoccupations:  safeJson((props.fro as any)?.preoccupations,  []) as any[],
})

const formErrors = reactive<Record<string, string>>({ ...(props.errors as any) })
const processing = ref(false)

const isLocked = computed(() =>
  fro.validation_status === 'validated' ||
  (fro.validation_status === 'in_review' && !canManage.value)
)

// ── Progression (mêmes pondérations que l'AC, sans l'audio) ──────────────
const progress = computed<number>(() => {
  let score = 0
  if (form.date_reunion && form.lieu)   score += 30
  if (form.ordre_du_jour.length > 0)    score += 20
  if (form.participants.length > 0)     score += 25
  if (form.points_generaux.length > 0)  score += 15
  if (form.preoccupations.length > 0)   score += 10
  return Math.min(score, 100)
})

// ── CRUD ──────────────────────────────────────────────────────────────────
function submit() {
  if (isLocked.value) return
  processing.value = true
  Object.keys(formErrors).forEach(k => delete formErrors[k])

  const data = new FormData()
  ;(['id', 'code_fro', 'phase_code', 'date_reunion', 'heure_debut', 'heure_fin', 'lieu', 'fait_par', 'revue_par'] as const)
    .forEach(k => {
      const v = form[k]
      if (v !== null && v !== undefined) data.append(k, String(v))
    })
  data.append('mission_id',      String(props.missionId))
  data.append('assignment_id',   String(props.assignmentId))
  data.append('ordre_du_jour',   JSON.stringify(form.ordre_du_jour))
  data.append('participants',    JSON.stringify(form.participants))
  data.append('points_generaux', JSON.stringify(form.points_generaux))
  data.append('preoccupations',  JSON.stringify(form.preoccupations))

  const url = form.id ? `${props.formUrl}/${form.id}` : props.formUrl
  if (form.id) data.append('_method', 'PUT')

  router.post(url, data, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: (page: any) => {
      const newFro = page.props?.fro
      if (newFro) {
        if (!form.id) form.id = newFro.id
        if (newFro.code_fro) form.code_fro = newFro.code_fro
        Object.assign(fro, newFro)
      }
      showToast('success', 'Fiche enregistrée')
    },
    onError: (e: any) => Object.assign(formErrors, e),
    onFinish: () => { processing.value = false },
  })
}

function annuler() {
  if (!confirm('Réinitialiser le formulaire (les données non enregistrées seront perdues) ?')) return
  Object.assign(form, {
    date_reunion: '', heure_debut: '', heure_fin: '', lieu: '',
    fait_par: currentAuditorName.value, revue_par: '',
    ordre_du_jour: [], participants: [], points_generaux: [], preoccupations: [],
  })
}

function loadFro(f: any) {
  router.visit(`${props.formUrl}/${f.id}/edit?mission_id=${props.missionId ?? ''}&assignment_id=${props.assignmentId ?? ''}`)
}

function deleteFro(f: any) {
  if (!confirm(`Supprimer la fiche ${f.code_fro} ?`)) return
  router.delete(`${props.formUrl}/${f.id}`, {
    preserveScroll: true,
    data: { mission_id: props.missionId, assignment_id: props.assignmentId },
    onSuccess: () => showToast('success', 'Fiche supprimée'),
    onError:   () => showToast('error', 'Erreur lors de la suppression'),
  })
}

// ── Workflow soumettre / valider / rejeter ────────────────────────────────
async function soumettre() {
  if (!form.id) { showToast('error', 'Enregistrez d\'abord la fiche.'); return }
  if (!confirm('Soumettre cette fiche pour validation par le DM ?')) return
  await apiPost(`${props.formUrl}/${form.id}/soumettre`,
    { mission_id: props.missionId, assignment_id: props.assignmentId },
    (json: any) => {
      fro.validation_status = json.status
      showToast('success', 'Fiche soumise — en attente validation DM')
    })
}

async function valider(action: 'validate' | 'reject', note?: string) {
  await apiPost(`${props.formUrl}/${form.id}/valider`,
    { mission_id: props.missionId, assignment_id: props.assignmentId, action, note },
    (json: any) => {
      fro.validation_status = json.status
      showToast('success', action === 'validate' ? 'Fiche validée ✓' : 'Fiche rejetée — repassée en brouillon')
    })
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

// ── Chat (POST /m/audit.core/missions/{id}/chat/PREPARATION) ─────────────
const chatOpen    = ref(false)
const chatDraft   = ref('')
const chatSending = ref(false)
const chatListEl  = ref<HTMLElement | null>(null)
const localMsgs   = ref<any[]>([...(props.chatMessages as any[])])
const unreadCount = computed(() => localMsgs.value.filter(m => !m.is_mine && m.is_read === false).length)

async function sendChat() {
  if (!chatDraft.value.trim() || chatSending.value) return
  chatSending.value = true
  try {
    const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
    const res = await fetch(props.chatBaseUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
      body: JSON.stringify({
        assignment_id: props.assignmentId,
        mission_id:    props.missionId,
        form_code:     'reunion-ouverture',
        content: chatDraft.value, type: 'message', priority: 'normal',
      }),
    })
    const json = await res.json()
    if (!res.ok) throw new Error(json?.message ?? 'Erreur')
    localMsgs.value.push({ ...json.message, is_mine: true, is_read: true })
    chatDraft.value = ''
    nextTick(() => { if (chatListEl.value) chatListEl.value.scrollTop = chatListEl.value.scrollHeight })
  } catch (e: any) {
    showToast('error', e.message)
  } finally {
    chatSending.value = false
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────
function vstLbl(s: string) {
  return ({ draft: 'Brouillon', in_review: 'Soumis — en revue', validated: 'Validé', rejected: 'Rejeté' } as any)[s] ?? s
}
function vstIcon(s: string) {
  return ({ draft: 'ti ti-pencil', in_review: 'ti ti-clock', validated: 'ti ti-lock', rejected: 'ti ti-x' } as any)[s] ?? 'ti ti-pencil'
}

const toast = ref({ show: false, type: 'success', msg: '' })
let toastTimer: ReturnType<typeof setTimeout>
function showToast(type: string, msg: string) {
  toast.value = { show: true, type, msg }
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { toast.value.show = false }, 4000)
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap');

* { box-sizing: border-box; }

.apro-shell {
  font-family: 'Plus Jakarta Sans', sans-serif;
  min-height: calc(100vh - 68px);
  background: #f4f7f6;
  color: #1e293b;
}

/* ══ HEADER ══ */
.apro-header {
  position: sticky;
  top: 0;
  z-index: 30;
  background: #fff;
  border-bottom: 1px solid #e2e8f0;
  box-shadow: 0 1px 8px rgba(15, 23, 42, .05);
}
.apro-hrow { display: flex; align-items: center; gap: 14px; padding: 14px 22px 10px; }
.apro-back {
  width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: var(--fcl); color: var(--fc);
  border: 1px solid var(--fcm); text-decoration: none;
  transition: all .15s;
}
.apro-back:hover { background: var(--fc); color: #fff; }

.apro-hinfo { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 4px; }
.apro-chips { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.apro-code {
  font-family: 'JetBrains Mono', monospace; font-size: .68rem; font-weight: 700;
  padding: 2px 8px; border-radius: 5px; border: 1px solid;
}
.apro-typechip {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: .62rem; font-weight: 800; padding: 2px 8px; border-radius: 12px;
}
.apro-rolechip {
  display: inline-flex; align-items: center; gap: 3px;
  font-size: .6rem; font-weight: 800; padding: 2px 7px; border-radius: 10px;
}
.rc-DM { background: #fef3c7; color: #b45309; }
.rc-CM { background: #dbeafe; color: #1d4ed8; }
.rc-AS { background: #d1fae5; color: #047857; }
.rc-AJ { background: #ede9fe; color: #6d28d9; }

.apro-vstchip {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: .6rem; font-weight: 800; padding: 2px 8px; border-radius: 12px;
  text-transform: uppercase; letter-spacing: .04em;
}
.avsc-draft     { background: #f1f5f9; color: #64748b; }
.avsc-in_review { background: #fef3c7; color: #b45309; }
.avsc-validated { background: #d1fae5; color: #047857; }
.avsc-rejected  { background: #fee2e2; color: #b91c1c; }

.apro-htitle { margin: 0; font-size: 1.25rem; font-weight: 800; letter-spacing: -.02em; color: #0f172a; }
.apro-hmeta { display: flex; flex-wrap: wrap; gap: 12px; }
.apro-hmeta span { display: inline-flex; align-items: center; gap: 4px; font-size: .7rem; color: #64748b; }

.apro-hbtns { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
.apro-hbtn {
  position: relative;
  width: 36px; height: 36px; border-radius: 10px;
  border: 1px solid #e2e8f0; background: #fff; color: #475569;
  display: inline-flex; align-items: center; justify-content: center;
  cursor: pointer; text-decoration: none; font-size: .95rem;
  transition: all .12s;
}
.apro-hbtn:hover { border-color: var(--fcm); color: var(--fc); }
.apro-chatbadge {
  position: absolute; top: -5px; right: -5px;
  min-width: 16px; height: 16px; padding: 0 4px; border-radius: 9px;
  background: #dc2626; color: #fff; font-size: .58rem; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
}

.apro-hbanner {
  display: flex; align-items: center; gap: 8px;
  padding: 8px 22px; font-size: .74rem; font-weight: 600;
  border-top: 1px solid;
}
.apro-hbanner-lock   { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
.apro-hbanner-review { background: #fffbeb; color: #b45309; border-color: #fde68a; }
.apro-hbanner-warn   { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }
.apro-link { color: inherit; text-decoration: underline; }

/* ══ BODY / FORM ══ */
.apro-body { padding: 18px 22px 90px; }
.apro-form { max-width: 1240px; margin: 0 auto; }

/* Reprise */
.apro-reprise {
  display: grid; grid-template-columns: auto 1fr auto; gap: 22px;
  background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
  padding: 16px 20px; margin-bottom: 16px;
  box-shadow: 0 1px 6px rgba(15,23,42,.04);
}
.arp-left { display: flex; align-items: center; gap: 14px; }
.arp-ring-wrap { position: relative; width: 76px; height: 76px; flex-shrink: 0; }
.arp-ring-svg { width: 100%; height: 100%; transform: rotate(-90deg); }
.arp-ring-inner {
  position: absolute; inset: 0;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
}
.arp-pct { font-size: 1rem; font-weight: 800; line-height: 1; }
.arp-pctsub { font-size: .56rem; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; }
.arp-vst-block { display: flex; flex-direction: column; gap: 5px; }
.arp-vst { align-self: flex-start; }
.arp-code { font-family: 'JetBrains Mono', monospace; font-size: .72rem; font-weight: 700; }

.arp-checks-title, .arp-next-title {
  font-size: .62rem; font-weight: 800; text-transform: uppercase;
  letter-spacing: .08em; color: #94a3b8; margin-bottom: 8px;
}
.arp-checklist { display: flex; flex-direction: column; gap: 5px; }
.arp-chk { display: flex; align-items: center; gap: 8px; font-size: .72rem; color: #64748b; }
.arp-chk-dot {
  width: 8px; height: 8px; border-radius: 50%;
  background: #e2e8f0; flex-shrink: 0; transition: background .2s;
}
.arp-chk.done .arp-chk-dot { background: #10b981; }
.arp-chk.done .arp-chk-lbl { color: #0f172a; font-weight: 600; }
.arp-chk-cnt, .arp-chk-val { margin-left: auto; font-size: .64rem; color: #94a3b8; }

.arp-next-list { display: flex; flex-direction: column; gap: 6px; min-width: 220px; }
.arp-next-item {
  display: flex; align-items: center; gap: 7px;
  font-size: .72rem; font-weight: 600; padding: 5px 10px; border-radius: 8px;
}
.arp-next-todo { background: #f8fafc; color: #475569; }
.arp-next-ok   { background: #eff6ff; color: #1d4ed8; }
.arp-next-wait { background: #fffbeb; color: #b45309; }
.arp-next-done { background: #ecfdf5; color: #047857; }

/* Grille */
.apro-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: start; }
@media (max-width: 1000px) { .apro-grid { grid-template-columns: 1fr; } }
.apro-col { display: flex; flex-direction: column; gap: 16px; min-width: 0; }

/* Cartes */
.apro-card {
  background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
  box-shadow: 0 1px 6px rgba(15,23,42,.04); overflow: hidden;
}
.apro-clabel {
  display: flex; align-items: center; gap: 7px;
  padding: 11px 16px; font-size: .74rem; font-weight: 800;
  text-transform: uppercase; letter-spacing: .05em;
  border-bottom: 1px solid;
}
.apro-cnt {
  margin-left: auto; font-size: .62rem; font-weight: 800;
  background: #f1f5f9; color: #64748b; padding: 1px 8px; border-radius: 10px;
}
.apro-cbody { padding: 14px 16px; display: flex; flex-direction: column; gap: 10px; }

/* Info mission */
.apro-mission-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 14px; }
.apro-ro { display: flex; flex-direction: column; gap: 1px; }
.apro-ro-full { grid-column: 1 / -1; }
.apro-ro em { font-style: normal; font-size: .6rem; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; font-weight: 700; }
.apro-ro strong { font-size: .78rem; color: #0f172a; font-weight: 600; }

/* Champs */
.apro-frow { display: grid; gap: 10px; }
.apro-frow-2 { grid-template-columns: 1fr 1fr; }
@media (max-width: 560px) { .apro-frow-2 { grid-template-columns: 1fr; } }
.apro-field { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
.apro-field label { font-size: .66rem; font-weight: 700; color: #475569; }
.apro-field label b { color: #dc2626; }
.apro-field input {
  width: 100%; padding: 8px 10px; border-radius: 9px;
  border: 1px solid #e2e8f0; background: #fff;
  font-size: .78rem; color: #0f172a; outline: none;
  transition: border-color .12s, box-shadow .12s;
  font-family: inherit;
}
.apro-field input:focus { border-color: var(--fc, #059669); box-shadow: 0 0 0 3px var(--fcl, #05966918); }
.apro-field input:disabled { background: #f8fafc; color: #94a3b8; cursor: not-allowed; }
.apro-err { font-size: .64rem; color: #dc2626; font-weight: 600; }

/* Cadrage 3E */
.apro-card-3e { border-width: 1px; }
.apro-3e-intro { margin: 0; font-size: .72rem; color: #64748b; }
.apro-3e-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
@media (max-width: 700px) { .apro-3e-grid { grid-template-columns: 1fr; } }
.apro-3e {
  display: flex; flex-direction: column; gap: 3px;
  padding: 9px 11px; border: 1px dashed; border-radius: 10px; background: #fafcfb;
}
.apro-3e-t { display: inline-flex; align-items: center; gap: 5px; font-size: .7rem; font-weight: 800; }
.apro-3e-d { font-size: .64rem; color: #64748b; line-height: 1.35; }

/* Suggestions ODJ */
.apro-sugg { display: flex; flex-wrap: wrap; gap: 6px; }
.apro-sugg-chip {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: .64rem; font-weight: 700; padding: 4px 10px;
  border: 1px dashed; border-radius: 14px; background: #fff;
  cursor: pointer; transition: all .12s; font-family: inherit;
}
.apro-sugg-chip:hover:not(:disabled) { background: var(--fcl, #05966918); }
.apro-sugg-chip:disabled { opacity: .35; cursor: default; }

/* Lignes dynamiques */
.apro-row { display: flex; align-items: center; gap: 8px; }
.apro-row-n {
  width: 26px; height: 26px; border-radius: 8px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: .66rem; font-weight: 800;
}
.apro-row input {
  flex: 1; padding: 7px 10px; border-radius: 9px;
  border: 1px solid #e2e8f0; font-size: .76rem; color: #0f172a; outline: none;
  font-family: inherit; min-width: 0;
}
.apro-row input:focus { border-color: var(--fc, #059669); }
.apro-row input:disabled { background: #f8fafc; color: #94a3b8; }
.apro-row-del {
  width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0;
  border: 1px solid #fee2e2; background: #fff; color: #dc2626;
  display: inline-flex; align-items: center; justify-content: center;
  cursor: pointer; font-size: .78rem; transition: all .12s;
}
.apro-row-del:hover { background: #dc2626; color: #fff; }

.apro-add {
  align-self: flex-start;
  display: inline-flex; align-items: center; gap: 5px;
  font-size: .7rem; font-weight: 700; padding: 6px 12px;
  border: 1px dashed; border-radius: 9px; background: transparent;
  cursor: pointer; transition: all .12s; font-family: inherit;
}
.apro-add:hover { background: var(--fcl, #05966918); }

/* Participants */
.apro-part {
  border: 1px solid #eef2f5; border-radius: 11px; padding: 10px 12px;
  display: flex; flex-direction: column; gap: 8px; background: #fbfdfc;
}
.apro-part-head { display: flex; align-items: center; justify-content: space-between; }
.apro-part-av {
  width: 30px; height: 30px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: .68rem; font-weight: 800;
}

/* Historique */
.apro-hist {
  display: flex; align-items: center; gap: 10px;
  padding: 7px 10px; border-radius: 9px; border: 1px solid transparent;
  font-size: .72rem;
}
.apro-hist.on { border-color: #e2e8f0; background: #f8fafc; }
.apro-hist code { font-family: 'JetBrains Mono', monospace; font-size: .66rem; font-weight: 700; }
.apro-hist-date { color: #64748b; }
.apro-hist-lieu { flex: 1; color: #94a3b8; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.apro-hist-btns { display: flex; gap: 4px; }
.apro-hist-btn {
  width: 26px; height: 26px; border-radius: 7px;
  border: 1px solid #e2e8f0; background: #fff; color: #64748b;
  display: inline-flex; align-items: center; justify-content: center;
  cursor: pointer; font-size: .72rem;
}
.apro-hist-btn:hover { color: #0f172a; }
.apro-hist-del:hover { background: #dc2626; border-color: #dc2626; color: #fff; }

/* Barre d'actions */
.apro-actions {
  position: fixed; bottom: 0; left: 0; right: 0; z-index: 25;
  display: flex; align-items: center; justify-content: space-between; gap: 10px;
  padding: 10px 22px;
  background: rgba(255,255,255,.94); backdrop-filter: blur(8px);
  border-top: 1px solid #e2e8f0;
}
.apro-act-code {
  display: inline-flex; align-items: center; gap: 5px;
  font-family: 'JetBrains Mono', monospace; font-size: .7rem; font-weight: 700; color: #475569;
}
.apro-actions-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.apro-btn {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: .74rem; font-weight: 700; padding: 8px 16px;
  border-radius: 9px; border: 1px solid transparent;
  cursor: pointer; transition: all .13s; font-family: inherit;
}
.apro-btn:disabled { opacity: .55; cursor: not-allowed; }
.apro-btn-ghost { background: #fff; border-color: #e2e8f0; color: #64748b; }
.apro-btn-ghost:hover:not(:disabled) { color: #0f172a; }
.apro-btn-save { color: #fff; }
.apro-btn-save:hover:not(:disabled) { filter: brightness(1.08); }
.apro-btn-submitrev { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
.apro-btn-submitrev:hover:not(:disabled) { background: #1d4ed8; color: #fff; }
.apro-btn-validate { background: #ecfdf5; border-color: #a7f3d0; color: #047857; }
.apro-btn-validate:hover:not(:disabled) { background: #047857; color: #fff; }
.apro-btn-reject { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
.apro-btn-reject:hover:not(:disabled) { background: #b91c1c; color: #fff; }

/* ══ CHAT ══ */
.apro-chat-ovl { position: fixed; inset: 0; background: rgba(15,23,42,.35); z-index: 90; }
.apro-chat {
  position: fixed; top: 0; right: 0; bottom: 0; z-index: 95;
  width: min(420px, 94vw);
  background: #fff; border-left: 1px solid #e2e8f0;
  display: flex; flex-direction: column;
  box-shadow: -12px 0 40px rgba(15,23,42,.15);
  font-family: 'Plus Jakarta Sans', sans-serif;
}
.apro-slide-enter-active, .apro-slide-leave-active { transition: transform .22s ease, opacity .22s ease; }
.apro-slide-enter-from, .apro-slide-leave-to { transform: translateX(40px); opacity: 0; }

.apro-chat-hd {
  display: flex; align-items: center; gap: 10px;
  padding: 14px 16px; border-bottom: 2px solid;
}
.apro-chat-hd-t { flex: 1; display: flex; flex-direction: column; }
.apro-chat-hd-t strong { font-size: .82rem; color: #0f172a; }
.apro-chat-hd-t span { font-size: .64rem; color: #94a3b8; }
.apro-chat-x {
  width: 30px; height: 30px; border-radius: 8px;
  border: 1px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer;
}
.apro-chat-msgs { flex: 1; overflow-y: auto; padding: 14px 16px; display: flex; flex-direction: column; gap: 10px; }
.apro-chat-empty {
  flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 6px; color: #cbd5e1;
}
.apro-chat-empty i { font-size: 1.6rem; }
.apro-chat-empty p { margin: 0; font-size: .74rem; color: #94a3b8; }

.apro-msg { display: flex; gap: 8px; }
.apro-msg.mine { flex-direction: row-reverse; }
.apro-msg-av {
  width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: .58rem; font-weight: 800; background: #f1f5f9; color: #475569;
}
.apro-msg-body {
  max-width: 82%;
  background: #f8fafc; border: 1px solid #eef2f5; border-radius: 11px;
  padding: 7px 10px;
}
.apro-msg.mine .apro-msg-body { background: #ecfdf5; border-color: #d1fae5; }
.apro-msg-meta { display: flex; align-items: center; gap: 6px; margin-bottom: 2px; }
.apro-msg-meta strong { font-size: .66rem; color: #0f172a; }
.apro-msg-meta em { font-style: normal; font-size: .58rem; font-weight: 800; color: #64748b; }
.apro-msg-meta span { font-size: .58rem; color: #94a3b8; margin-left: auto; }
.apro-msg-body p { margin: 0; font-size: .74rem; color: #334155; white-space: pre-wrap; word-break: break-word; }

.apro-chat-compose {
  display: flex; gap: 8px; align-items: flex-end;
  padding: 12px 16px; border-top: 1px solid #e2e8f0;
}
.apro-chat-compose textarea {
  flex: 1; resize: none; padding: 8px 10px; border-radius: 10px;
  border: 1px solid #e2e8f0; font-size: .76rem; font-family: inherit;
  color: #0f172a; outline: none;
}
.apro-chat-compose textarea:focus { border-color: #059669; }
.apro-chat-send {
  width: 38px; height: 38px; border-radius: 10px; border: none;
  color: #fff; cursor: pointer; font-size: .9rem;
  display: inline-flex; align-items: center; justify-content: center;
}
.apro-chat-send:disabled { opacity: .5; cursor: not-allowed; }

/* ══ TOAST ══ */
.apro-toast {
  position: fixed; bottom: 76px; right: 22px; z-index: 200;
  display: flex; align-items: center; gap: 8px;
  padding: 10px 16px; border-radius: 11px;
  font-size: .76rem; font-weight: 700;
  box-shadow: 0 8px 30px rgba(15,23,42,.18);
  font-family: 'Plus Jakarta Sans', sans-serif;
}
.apro-toast-success { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
.apro-toast-error   { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
.apro-toast-enter-active, .apro-toast-leave-active { transition: all .2s ease; }
.apro-toast-enter-from, .apro-toast-leave-to { opacity: 0; transform: translateY(8px); }

.apro-spin { animation: apro-rot .7s linear infinite; }
@keyframes apro-rot { to { transform: rotate(360deg); } }
</style>
