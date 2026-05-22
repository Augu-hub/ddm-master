<template>
  <VerticalLayoutAudit>
    <div class="apt-shell">

      <!-- HEADER -->
      <header class="apt-header">
        <div class="apt-hrow">
          <a :href="props.backUrl" class="apt-back"><i class="ti ti-arrow-left"></i></a>
          <div class="apt-hinfo">
            <div class="apt-chips">
              <code class="apt-code">{{ mission?.code ?? '—' }}</code>
              <span class="apt-chip" :class="`chip-${form.validation_status||'draft'}`">
                <i :class="vstIcon(form.validation_status||'draft')"></i>{{ vstLbl(form.validation_status||'draft') }}
              </span>
              <span class="apt-chip chip-type">APT</span>
              <span v-if="props.auditorRole" class="apt-chip" :class="`chip-role-${props.auditorRole}`">{{ props.auditorRole }}</span>
            </div>
            <h1 class="apt-title">Analyse des Procédures de Test</h1>
            <div class="apt-meta">
              <span v-if="mission?.title"><i class="ti ti-clipboard"></i>{{ mission.title }}</span>
              <span v-if="mission?.entity_name"><i class="ti ti-building"></i>{{ mission.entity_name }}</span>
              <span v-if="props.phaseAuditeurs?.length"><i class="ti ti-users"></i>{{ props.phaseAuditeurs.length }} auditeurs</span>
              <span><i class="ti ti-clipboard-list"></i>{{ procedures.length }} procédure(s)</span>
            </div>
          </div>
          <div class="apt-hactions">
            <button class="btn-chat" :class="{unread:unreadCount>0}" @click="openChat">
              <i class="ti ti-message-circle"></i><span>Chat APT</span>
              <span v-if="unreadCount>0" class="chat-badge">{{ unreadCount }}</span>
            </button>
          </div>
        </div>
        <div v-if="form.validation_status==='validated'" class="apt-banner banner-lock">
          <i class="ti ti-lock"></i> Formulaire <strong>validé définitivement</strong> — lecture seule
        </div>
        <div v-else-if="form.validation_status==='in_review'" class="apt-banner banner-review">
          <i class="ti ti-clock"></i> Soumis pour validation<span v-if="canManage"> · Vous pouvez valider ou rejeter.</span>
        </div>
        <div v-else-if="form.validation_status==='draft'&&form.validation_note" class="apt-banner banner-reject">
          <i class="ti ti-circle-x"></i> Rejeté — <em>{{ form.validation_note }}</em>
        </div>
      </header>

      <!-- BODY -->
      <div class="apt-body">
        <div :class="['apt-layout', docLoaded?'with-doc':'no-doc']">

          <!-- SIDEBAR -->
          <aside class="apt-sidebar">
            <section class="card">
              <div class="card-lbl"><i class="ti ti-briefcase"></i> Mission</div>
              <div class="card-body">
                <div class="fg"><span class="flbl">Code</span><input class="inp inp-ro" :value="mission?.code" readonly/></div>
                <div class="fg"><span class="flbl">Entité</span><input class="inp inp-ro" :value="mission?.entity_name||'—'" readonly/></div>
                <div class="fg"><span class="flbl">Intitulé</span><input class="inp inp-ro" :value="mission?.title" readonly/></div>
              </div>
            </section>
            <section class="card">
              <div class="card-lbl"><i class="ti ti-users"></i> Auditeurs <span class="card-cnt">{{ props.phaseAuditeurs?.length??0 }}</span></div>
              <div class="card-body p6">
                <div v-if="!props.phaseAuditeurs?.length" class="empty-s"><i class="ti ti-user-off"></i> Aucun</div>
                <div v-for="aud in (props.phaseAuditeurs as any[])" :key="aud.id" class="aud-row">
                  <div class="aud-av" :class="`av-${aud.role_code}`">{{ aud.initials }}</div>
                  <div class="aud-inf"><span class="aud-nm">{{ aud.full_name }}</span><span class="aud-cd">{{ aud.audit_code }}</span></div>
                  <span class="apt-chip" :class="`chip-role-${aud.role_code}`">{{ aud.role_code }}</span>
                </div>
              </div>
            </section>
            <section v-if="canManage" class="card">
              <div class="card-lbl"><i class="ti ti-table"></i> Formulaire APT</div>
              <div class="card-body">
                <div class="fg"><span class="flbl">Code</span><input class="inp inp-ro" :value="form.code||'APT-AUTO'" readonly/></div>
                <div class="form-r2">
                  <div class="fg"><span class="flbl">Fait par</span><input class="inp" v-model="form.fait_par" :disabled="isLocked"/></div>
                  <div class="fg"><span class="flbl">Date</span><input type="date" class="inp" v-model="form.date_fait" :disabled="isLocked"/></div>
                </div>
                <div class="form-r2">
                  <div class="fg"><span class="flbl">Revu par</span><input class="inp" v-model="form.revue_par" :disabled="isLocked"/></div>
                  <div class="fg"><span class="flbl">Date revue</span><input type="date" class="inp" v-model="form.date_revue" :disabled="isLocked"/></div>
                </div>
                <div class="fg"><span class="flbl">Commentaire global</span>
                  <textarea class="inp inp-ta" v-model="form.commentaire_global" :disabled="isLocked" rows="2"></textarea>
                </div>
              </div>
            </section>
            <section class="card">
              <div class="card-lbl"><i class="ti ti-list"></i> APT enregistrés</div>
              <div class="card-body p0">
                <table class="stbl">
                  <thead><tr><th>Code</th><th>Fait par</th><th>Statut</th></tr></thead>
                  <tbody>
                    <tr v-if="!props.aptList?.length"><td colspan="3" class="td-empty">Aucune analyse</td></tr>
                    <tr v-for="a in (props.aptList as any[])" :key="a.id" class="stbl-row" @click="loadApt(a)">
                      <td class="td-code">{{ a.code }}</td>
                      <td>{{ a.fait_par||'—' }}</td>
                      <td><span class="apt-chip" :class="`chip-${a.validation_status||'draft'}`">{{ vstLbl(a.validation_status||'draft') }}</span></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </section>
            <section class="card">
              <div class="card-lbl"><i class="ti ti-info-circle"></i> Légende</div>
              <div class="card-body">
                <div class="lg-row"><span class="lg-b lg-c">C</span><div><b>Conforme</b><p>Contrôle effectif</p></div></div>
                <div class="lg-row"><span class="lg-b lg-nc">NC</span><div><b>Non Conforme</b><p>Défaillance</p></div></div>
                <div class="lg-row"><span class="lg-b lg-pp">PP</span><div><b>Partiel</b><p>Incomplet</p></div></div>
              </div>
            </section>
          </aside>

          <!-- VISIONNEUSE DOC (DM/CM : import doc → IA) -->
          <div v-if="docLoaded" class="apt-docview">
            <div class="dv-bar">
              <i class="ti ti-file-text" style="color:#a78bfa"></i>
              <span class="dv-name">{{ docState.name }}</span>
              <span v-if="docState.analyzing" class="dv-badge-ana"><span class="spin-s"></span> Analyse IA…</span>
              <span v-else-if="docState.analyzed" class="dv-badge-ok"><i class="ti ti-check"></i> Analysé</span>
              <button class="dv-cls" @click="closeDoc"><i class="ti ti-x"></i></button>
            </div>
            <div class="dv-body">
              <iframe v-if="docState.type==='pdf'" :src="docState.objectUrl" class="dv-iframe"></iframe>
              <div v-else-if="docState.type==='image'" class="dv-imgwrap"><img :src="docState.objectUrl" class="dv-img"/></div>
              <div v-else class="dv-ph"><i class="ti ti-file-description"></i><p>{{ docState.name }}</p></div>
            </div>
            <div v-if="docState.analyzed&&docState.aiResult" class="dv-ai">
              <div class="dv-ai-ttl"><i class="ti ti-sparkles"></i> Résultats IA</div>
              <div v-if="docState.aiResult.synthese?.titre" class="dv-ai-row">
                <span class="dv-ai-lbl">Titre</span><span class="dv-ai-val">{{ docState.aiResult.synthese.titre }}</span>
              </div>
              <div class="dv-ai-stats">
                <span><i class="ti ti-layout-rows"></i> {{ docState.aiResult.items_matrice?.length||0 }} pts</span>
                <span><i class="ti ti-files"></i> {{ docState.aiResult.plan_collecte?.length||0 }} collectes</span>
                <span><i class="ti ti-message-question"></i> {{ docState.aiResult.grille_entretien?.length||0 }} Q</span>
              </div>
              <button v-if="canManage&&!isLocked" class="btn btn-save dv-ai-cta" @click="createProcFromAI">
                <i class="ti ti-plus"></i> Créer la procédure avec ces données
              </button>
            </div>
            <div v-if="docState.error" class="dv-err"><i class="ti ti-alert-circle"></i> {{ docState.error }}</div>
          </div>

          <!-- PROCÉDURES -->
          <div class="apt-procs">
            <div class="procs-hdr">
              <!-- DM/CM : création -->
              <template v-if="canManage&&!isLocked">
                <div class="procs-hdr-title">
                  <i class="ti ti-clipboard-list"></i> Procédures <span class="proc-cnt">{{ procedures.length }}</span>
                </div>
                <div class="create-row">
                  <input v-model="newProcTitle" class="inp" style="flex:1;font-size:.8rem"
                         placeholder="Titre de la nouvelle procédure…" @keydown.enter="addProcedure()"/>
                  <button class="btn btn-save btn-sm" :disabled="!newProcTitle.trim()" @click="addProcedure()">
                    <i class="ti ti-plus"></i> Créer
                  </button>
                </div>
                <div class="create-tools">
                  <label class="btn btn-ia btn-sm" :class="{loading:docState.analyzing}">
                    <span v-if="docState.analyzing" class="spin-s"></span>
                    <i v-else class="ti ti-cloud-upload"></i>
                    {{ docState.analyzing?'Analyse…':'Importer doc → IA' }}
                    <input v-if="!docState.analyzing" type="file" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.txt" class="hidden" @change="onDocUpload"/>
                  </label>
                  <button class="btn btn-ai btn-sm" @click="showIaSuggest=!showIaSuggest">
                    <i class="ti ti-brain"></i> Générer par IA
                  </button>
                </div>
                <div v-if="showIaSuggest" class="ia-zone">
                  <div class="ia-zone-ttl"><i class="ti ti-sparkles"></i> Génération IA</div>
                  <textarea v-model="iaProcPrompt" class="inp ia-ta" rows="3"
                    placeholder="Décrivez la procédure ex : procédure de passation des marchés, gestion de la paie…"/>
                  <div class="ia-zone-acts">
                    <button class="btn btn-ai btn-sm" @click="suggestProcedureIA" :disabled="iaLoading||!iaProcPrompt.trim()">
                      <i class="ti" :class="iaLoading?'ti-loader-2 spin':'ti-sparkles'"></i>{{ iaLoading?'En cours…':'Générer' }}
                    </button>
                    <button class="btn btn-ghost btn-sm" @click="showIaSuggest=false">Annuler</button>
                  </div>
                </div>
              </template>
              <!-- AS/AJ -->
              <template v-else-if="!canManage">
                <div class="procs-hdr-title">
                  <i class="ti ti-clipboard-list"></i> Mes procédures assignées <span class="proc-cnt">{{ procedures.length }}</span>
                </div>
                <div class="role-info">
                  <i class="ti ti-info-circle"></i>
                  <span>Rôle <strong>{{ props.auditorRole }}</strong> — Vous éditez uniquement les procédures qui vous sont assignées par le DM/CM.</span>
                </div>
              </template>
              <!-- Locked -->
              <template v-else>
                <div class="procs-hdr-title"><i class="ti ti-lock"></i> Procédures (verrouillé) <span class="proc-cnt">{{ procedures.length }}</span></div>
              </template>
            </div>

            <!-- Liste scrollable -->
            <div class="procs-list">
              <div v-if="!procedures.length" class="proc-empty">
                <i class="ti ti-clipboard-list"></i>
                <p v-if="canManage&&!isLocked">Créez une procédure ci-dessus ou importez un document.</p>
                <p v-else>Aucune procédure assignée.</p>
              </div>

              <div v-for="proc in procedures" :key="proc._k" class="proc-block">
                <!-- En-tête -->
                <div class="proc-hdr" @click="toggleProc(proc._k)">
                  <div class="proc-hdr-l">
                    <span class="proc-chev" :class="{'proc-chev--open':expandedProcs.has(proc._k)}">
                      <i class="ti ti-chevron-right"></i>
                    </span>
                    <span class="proc-ico"><i class="ti ti-clipboard-list"></i></span>
                    <div class="proc-inf">
                      <span class="proc-ref">{{ proc.ref_procedure||'—' }}</span>
                      <span class="proc-nm">{{ proc.intitule||'Procédure sans titre' }}</span>
                    </div>
                    <div class="proc-bgs">
                      <span class="proc-st" :class="`pcs-${proc.statut}`">{{ {en_cours:'En cours',termine:'Terminé',suspendu:'Suspendu'}[proc.statut]||'—' }}</span>
                      <span v-if="matCount(proc)" class="mat-bg">
                        <i class="ti ti-layout-rows"></i>{{ matCount(proc) }}
                        <span class="s-c ml2">{{ countR(proc,'c') }}C</span>
                        <span class="s-nc">{{ countR(proc,'nc') }}NC</span>
                        <span class="s-pp">{{ countR(proc,'pp') }}PP</span>
                      </span>
                      <span v-if="getAssigned(proc._k)" class="assigned-bg" :class="`av-${getAssigned(proc._k).role_code}`">
                        <i class="ti ti-user-check"></i>{{ getAssigned(proc._k).initials }}
                        <span class="asgn-nm">{{ getAssigned(proc._k).full_name }}</span>
                      </span>
                      <span v-else-if="canManage" class="unassigned-bg"><i class="ti ti-user-off"></i> Non assigné</span>
                    </div>
                  </div>
                  <div class="proc-hdr-r" @click.stop>
                    <select v-if="canManage&&!isLocked" class="proc-asgn-sel"
                      :value="procAssignments[proc._k]??''"
                      @change="assignProc(proc._k,($event.target as HTMLSelectElement).value)">
                      <option value="">— Assigner —</option>
                      <option v-for="aud in (props.phaseAuditeurs as any[])" :key="aud.id" :value="aud.id">{{ aud.role_code }} · {{ aud.full_name }}</option>
                    </select>
                    <button v-if="canManage&&!isLocked" class="ibtn ibtn-del" @click.stop="removeProc(proc._k)"><i class="ti ti-x"></i></button>
                  </div>
                </div>

                <!-- Corps -->
                <div v-if="expandedProcs.has(proc._k)" class="proc-body">
                  <!-- Onglets -->
                  <div class="proc-tabs">
                    <button v-for="tab in getTabsForRole()" :key="tab.key"
                            :class="['ptab',{active:activeTab(proc._k)===tab.key}]"
                            @click="procTab[proc._k]=tab.key">
                      <i class="ti" :class="tab.icon"></i>{{ tab.label }}
                      <span v-if="tab.key==='ANALYSE'&&matCount(proc)" class="ptab-ct">{{ matCount(proc) }}</span>
                    </button>
                  </div>

                  <!-- ════ ID — DM/CM seulement ════ -->
                  <div v-if="canManage" v-show="activeTab(proc._k)==='ID'" class="tab-content">
                    <div class="fg2">
                      <div class="fg"><span class="flbl">Référence</span><input class="inp" v-model="proc.ref_procedure" :disabled="isLocked" placeholder="PROC-001"/></div>
                      <div class="fg"><span class="flbl">Version</span><input class="inp" v-model="proc.version_vigueur" :disabled="isLocked" placeholder="v1.0"/></div>
                      <div class="fg full"><span class="flbl">Intitulé *</span><input class="inp" v-model="proc.intitule" :disabled="isLocked"/></div>
                      <div class="fg"><span class="flbl">Service</span><input class="inp" v-model="proc.service_dept" :disabled="isLocked"/></div>
                      <div class="fg"><span class="flbl">Responsable</span><input class="inp" v-model="proc.responsable_proc" :disabled="isLocked"/></div>
                      <div class="fg"><span class="flbl">Date vigueur</span><input type="date" class="inp" v-model="proc.date_entree_vigueur" :disabled="isLocked"/></div>
                      <div class="fg"><span class="flbl">Dernière révision</span><input type="date" class="inp" v-model="proc.date_derniere_revision" :disabled="isLocked"/></div>
                      <div class="fg full"><span class="flbl">Description</span><textarea class="inp inp-ta" v-model="proc.description" :disabled="isLocked" rows="2"></textarea></div>
                      <div class="fg"><span class="flbl">Population totale</span><input type="number" class="inp" v-model.number="proc.population_totale" :disabled="isLocked"/></div>
                      <div class="fg"><span class="flbl">Taille échantillon</span><input type="number" class="inp" v-model.number="proc.taille_echantillon" :disabled="isLocked"/></div>
                      <div class="fg full"><span class="flbl">Méthode d'échantillonnage</span>
                        <div class="radio-grp">
                          <label v-for="m in METHODES" :key="m.key" class="rlbl"><input type="radio" :value="m.key" v-model="proc.methode_echantillonnage" :disabled="isLocked"/>{{ m.label }}</label>
                        </div>
                      </div>
                      <div class="fg"><span class="flbl">Statut</span>
                        <select class="inp" v-model="proc.statut" :disabled="isLocked">
                          <option value="en_cours">En cours</option><option value="termine">Terminé</option><option value="suspendu">Suspendu</option>
                        </select>
                      </div>
                    </div>
                  </div>

                  <!-- ════ DOCS — tous les auditeurs assignés ════ -->
                  <div v-show="activeTab(proc._k)==='DOCS'" class="tab-content">
                    <!-- Résumé ID pour AS/AJ -->
                    <div v-if="!canManage" class="proc-resume">
                      <div class="pr-row"><span class="pr-lbl">Référence</span><span class="pr-val">{{ proc.ref_procedure||'—' }}</span></div>
                      <div class="pr-row"><span class="pr-lbl">Intitulé</span><span class="pr-val fw">{{ proc.intitule||'—' }}</span></div>
                      <div class="pr-row"><span class="pr-lbl">Service</span><span class="pr-val">{{ proc.service_dept||'—' }}</span></div>
                      <div class="pr-row" v-if="proc.responsable_proc"><span class="pr-lbl">Responsable</span><span class="pr-val">{{ proc.responsable_proc }}</span></div>
                      <div class="pr-row" v-if="proc.description"><span class="pr-lbl">Description</span><span class="pr-val">{{ proc.description }}</span></div>
                    </div>
                    <!-- Docs -->
                    <div class="docs-sec">
                      <div class="docs-hdr">
                        <div class="docs-ttl"><i class="ti ti-paperclip"></i> Documents <span class="docs-cnt">{{ (proc.attached_docs||[]).length }}</span></div>
                        <!-- Upload : DM/CM et AS/AJ assigné -->
                        <label v-if="!isLocked&&canEdit(proc._k)" class="btn btn-xs btn-import">
                          <span v-if="proc.uploading" class="spin-s"></span>
                          <i v-else class="ti ti-cloud-upload"></i>
                          {{ proc.uploading?'Upload…':'Joindre un fichier' }}
                          <input v-if="!proc.uploading" type="file" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.txt,.xlsx,.xls" class="hidden" @change="e=>uploadDocForProc(e,proc)"/>
                        </label>
                      </div>
                      <div v-if="!(proc.attached_docs||[]).length" class="tb-empty" style="margin-top:6px">
                        <i class="ti ti-file-off"></i>
                        <p>{{ canEdit(proc._k)?'Aucun document — cliquez sur "Joindre un fichier".':'Aucun document attaché.' }}</p>
                      </div>
                      <div v-else class="docs-grid">
                        <div v-for="(doc,di) in (proc.attached_docs||[])" :key="di" class="doc-card" :class="{'doc-card--active':proc.activeDocIdx===di}">
                          <div class="doc-card-hdr" @click="toggleDocViewer(proc,di)">
                            <i :class="['ti doc-ico', docIcon(doc.extension||doc.name)]"></i>
                            <div class="doc-inf">
                              <span class="doc-nm">{{ doc.original_name||doc.name }}</span>
                              <span class="doc-sz">{{ doc.size_label||'' }} · {{ (doc.extension||'').toUpperCase() }}</span>
                            </div>
                            <div class="doc-card-acts" @click.stop>
                              <a v-if="doc.url" :href="doc.url" target="_blank" class="btn btn-xs btn-ghost" title="Ouvrir onglet"><i class="ti ti-external-link"></i></a>
                              <button class="btn btn-xs btn-ghost" :class="proc.activeDocIdx===di?'btn-eye-on':''" @click="toggleDocViewer(proc,di)">
                                <i class="ti" :class="proc.activeDocIdx===di?'ti-chevron-up':'ti-eye'"></i>
                              </button>
                              <!-- Supprimer : DM/CM tous, AS/AJ les siens -->
                              <button v-if="!isLocked&&canEdit(proc._k)" class="btn btn-xs btn-ghost" @click="removeDocFromProc(proc,di)">
                                <i class="ti ti-trash" style="color:#dc2626"></i>
                              </button>
                            </div>
                          </div>
                          <div v-if="proc.activeDocIdx===di" class="doc-viewer-inline">
                            <iframe v-if="isPdf(doc)" :src="doc.url+'#toolbar=1&navpanes=0&scrollbar=1'" class="doc-iframe" title="Document"></iframe>
                            <div v-else-if="isImage(doc)" class="doc-img-wrap"><img :src="doc.url" class="doc-img-full" :alt="doc.original_name"/></div>
                            <div v-else class="doc-dl-wrap">
                              <i class="ti ti-file-description"></i><p>{{ doc.original_name }}</p>
                              <a :href="doc.url" target="_blank" class="btn btn-sm btn-save"><i class="ti ti-download"></i> Ouvrir / Télécharger</a>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- ════ ENTRETIEN — sans colonne Objectif ════ -->
                  <div v-show="activeTab(proc._k)==='ENTRET'" class="tab-content">
                    <div class="tb-bar">
                      <button v-if="!isLocked&&canEdit(proc._k)" class="btn btn-xs btn-ai" @click="suggestGrilleIA(proc)" :disabled="iaLoading"><i class="ti" :class="iaLoading?'ti-loader-2 spin':'ti-brain'"></i> IA</button>
                      <button v-if="!isLocked&&canEdit(proc._k)" class="btn btn-xs btn-ghost" @click="addAxeRow(proc)"><i class="ti ti-plus"></i> Axe</button>
                      <button v-if="!isLocked&&canEdit(proc._k)" class="btn btn-xs btn-save" @click="addGrilleRow(proc)"><i class="ti ti-plus"></i> Question</button>
                      <span class="tb-ct">{{ (proc.grille_entretien_parsed||[]).filter((r:any)=>!r.is_axe).length }} question(s)</span>
                    </div>
                    <div v-if="!(proc.grille_entretien_parsed||[]).length" class="tb-empty"><i class="ti ti-message-question"></i><p>Grille vide</p></div>
                    <div v-else class="tb-wrap">
                      <table class="btbl">
                        <thead><tr><th class="th-n">N°</th><th>Question posée à l'interlocuteur</th><th style="width:230px">Réponse / Constat de l'auditeur</th><th style="width:22px"></th></tr></thead>
                        <tbody>
                          <template v-for="(row,ri) in (proc.grille_entretien_parsed||[])" :key="ri">
                            <tr v-if="row.is_axe" class="row-sec">
                              <td colspan="4"><div class="sec-hd"><i class="ti ti-layout-rows"></i>
                                <input v-if="!isLocked&&canEdit(proc._k)" class="c-inp c-sec" v-model="row.axe" placeholder="Axe thématique…"/>
                                <span v-else class="sec-txt">{{ row.axe }}</span>
                              </div></td>
                            </tr>
                            <tr v-else class="row-b" :class="row.reponse?'row-answered':''">
                              <td class="td-n">{{ row.num }}</td>
                              <td>
                                <input v-if="!isLocked&&canEdit(proc._k)" class="c-inp c-x" v-model="row.question" placeholder="Question…"/>
                                <span v-else class="ro-t">{{ row.question }}</span>
                              </td>
                              <!-- Réponse : AS/AJ ET DM/CM saisissent -->
                              <td>
                                <textarea v-if="!isLocked&&canEdit(proc._k)" class="c-ta" v-model="row.reponse" placeholder="Saisir la réponse / le constat…" rows="2"></textarea>
                                <span v-else class="ro-t reponse-txt">{{ row.reponse||'—' }}</span>
                              </td>
                              <td><button v-if="!isLocked&&canEdit(proc._k)" class="btn-del" @click="proc.grille_entretien_parsed.splice(ri,1)">×</button></td>
                            </tr>
                          </template>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <!-- ════ COLLECTE — avec Doc reçu ════ -->
                  <div v-show="activeTab(proc._k)==='COLL'" class="tab-content">
                    <div class="tb-bar">
                      <button v-if="!isLocked&&canEdit(proc._k)" class="btn btn-xs btn-ai" @click="suggestCollecteIA(proc)" :disabled="iaLoading"><i class="ti" :class="iaLoading?'ti-loader-2 spin':'ti-brain'"></i> IA</button>
                      <label v-if="!isLocked&&canEdit(proc._k)" class="btn btn-xs btn-import"><i class="ti ti-upload"></i> Excel<input type="file" accept=".xlsx,.xls" class="hidden" @change="e=>importExcel(e,proc,'C')"/></label>
                      <button v-if="!isLocked&&canEdit(proc._k)" class="btn btn-xs btn-save" @click="addCollecteRow(proc)"><i class="ti ti-plus"></i> Ligne</button>
                      <span class="tb-ct">{{ (proc.plan_collecte_parsed||[]).length }} éléments</span>
                      <span v-if="(proc.plan_collecte_parsed||[]).some((r:any)=>r.doc_recu)" class="coll-recu-ct">
                        <i class="ti ti-circle-check"></i>{{ (proc.plan_collecte_parsed||[]).filter((r:any)=>r.doc_recu).length }} reçu(s)
                      </span>
                    </div>
                    <div v-if="!(proc.plan_collecte_parsed||[]).length" class="tb-empty"><i class="ti ti-files"></i><p>Plan de collecte vide</p></div>
                    <div v-else class="tb-wrap">
                      <table class="btbl">
                        <thead>
                          <tr><th class="th-n">N°</th><th>Information à collecter</th><th style="width:90px">Source</th><th style="width:90px">Méthode</th><th style="width:46px;text-align:center">Reçu</th><th style="width:110px">Réf. doc reçu</th><th style="width:54px">Statut</th><th style="width:22px"></th></tr>
                        </thead>
                        <tbody>
                          <tr v-for="(row,ri) in (proc.plan_collecte_parsed||[])" :key="ri" class="row-b" :class="row.doc_recu?'row-recu':''">
                            <td class="td-n">{{ ri+1 }}</td>
                            <td><input v-if="!isLocked&&canEdit(proc._k)" class="c-inp c-x" v-model="row.information" placeholder="Information…"/><span v-else class="ro-t">{{ row.information }}</span></td>
                            <td><input v-if="!isLocked&&canEdit(proc._k)" class="c-inp" v-model="row.source"/><span v-else class="ro-t">{{ row.source }}</span></td>
                            <td><input v-if="!isLocked&&canEdit(proc._k)" class="c-inp" v-model="row.methode_collecte"/><span v-else class="ro-t">{{ row.methode_collecte }}</span></td>
                            <!-- Checkbox Reçu : AS/AJ et DM/CM cochent -->
                            <td class="td-c">
                              <label class="chk-wrap" :class="isLocked||!canEdit(proc._k)?'chk-dis':''">
                                <input type="checkbox" v-model="row.doc_recu" :disabled="isLocked||!canEdit(proc._k)" class="hidden"/>
                                <span class="chk-box" :class="row.doc_recu?'chk-on':''"><i v-if="row.doc_recu" class="ti ti-check"></i></span>
                              </label>
                            </td>
                            <!-- Réf doc reçu -->
                            <td>
                              <input v-if="!isLocked&&canEdit(proc._k)&&row.doc_recu" class="c-inp c-x" v-model="row.ref_doc_recu" placeholder="N° ou nom doc…"/>
                              <span v-else-if="row.ref_doc_recu" class="ro-t ref-doc">{{ row.ref_doc_recu }}</span>
                              <span v-else class="ro-t nd">—</span>
                            </td>
                            <td class="td-c">
                              <select v-if="!isLocked&&canEdit(proc._k)" class="c-sel" v-model="row.statut">
                                <option :value="null">—</option>
                                <option v-for="s in STATUTS_C" :key="s.key" :value="s.key">{{ s.label }}</option>
                              </select>
                              <span v-else :class="['b-sc','sc-'+row.statut]">{{ STATUTS_C.find(s=>s.key===row.statut)?.label||'—' }}</span>
                            </td>
                            <td><button v-if="!isLocked&&canEdit(proc._k)" class="btn-del" @click="proc.plan_collecte_parsed.splice(ri,1)">×</button></td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <!-- ════ ANALYSE — Matrice + Forces/Faiblesses + Appréciation ════ -->
                  <div v-show="activeTab(proc._k)==='ANALYSE'" class="tab-content">

                    <!-- Matrice -->
                    <div class="sec-ttl"><i class="ti ti-layout-rows"></i> Matrice de test</div>
                    <div class="tb-bar">
                      <button v-if="!isLocked&&canEdit(proc._k)" class="btn btn-xs btn-ai" @click="suggestMatriceIA(proc)" :disabled="iaLoading"><i class="ti" :class="iaLoading?'ti-loader-2 spin':'ti-brain'"></i> IA</button>
                      <label v-if="!isLocked&&canEdit(proc._k)" class="btn btn-xs btn-import"><i class="ti ti-upload"></i> Excel<input type="file" accept=".xlsx,.xls" class="hidden" @change="e=>importExcel(e,proc,'B')"/></label>
                      <button v-if="!isLocked&&canEdit(proc._k)" class="btn btn-xs btn-ghost" @click="addSectionRow(proc)"><i class="ti ti-plus"></i> Section</button>
                      <button v-if="!isLocked&&canEdit(proc._k)" class="btn btn-xs btn-save" @click="addMatriceRow(proc)"><i class="ti ti-plus"></i> Point</button>
                      <div v-if="matCount(proc)" class="mat-score ms-auto">
                        <span class="score-c"><i class="ti ti-check"></i>{{ countR(proc,'c') }} Conf.</span>
                        <span class="score-nc"><i class="ti ti-x"></i>{{ countR(proc,'nc') }} NC</span>
                        <span class="score-pp"><i class="ti ti-minus"></i>{{ countR(proc,'pp') }} PP</span>
                      </div>
                    </div>
                    <div v-if="!(proc.items_matrice_parsed||[]).length" class="tb-empty"><i class="ti ti-layout-rows"></i><p>Matrice vide — générez via IA ou importez via Excel</p></div>
                    <div v-else class="tb-wrap mb14">
                      <table class="btbl">
                        
                        <thead>
                          <tr><th class="th-n">N°</th><th>Point de contrôle</th><th style="width:54px;text-align:center">Force/<br> Faiblesse</th><th style="width:44px;text-align:center">Oui/<br> Non</th><th style="width:95px">Preuve / Réf.</th><th style="width:150px">Observation</th><th style="width:70px;text-align:center">Résultat</th><th style="width:22px"></th></tr>
                        </thead>
                        <tbody>
                          <template v-for="(row,ri) in (proc.items_matrice_parsed||[])" :key="ri">
                            <tr v-if="row.is_section" class="row-sec">
                              <td colspan="8"><div class="sec-hd"><i class="ti ti-layout-rows"></i>
                                <input v-if="!isLocked&&canEdit(proc._k)" class="c-inp c-sec" v-model="row.section" placeholder="Section…"/>
                                <span v-else class="sec-txt">{{ row.section }}</span>
                              </div></td>
                            </tr>
                            <tr v-else class="row-b" :class="rCls(row)">
                              <td class="td-n">{{ row.num }}</td>
                              <!-- Point contrôle : DM/CM édite, AS/AJ lit -->
                              <td><input v-if="!isLocked&&canEdit(proc._k)" class="c-inp c-x" v-model="row.point_controle" placeholder="Point de contrôle…"/><span v-else class="ro-t">{{ row.point_controle }}</span></td>
                              <!-- Nature : DM/CM définit, AS/AJ voit badge -->
                              <td class="td-c">
                                <select v-if="!isLocked&&canEdit(proc._k)" class="c-sel" :class="row.nature==='fort'?'s-fort':row.nature==='faible'?'s-faib':''" v-model="row.nature"><option :value="null">—</option><option value="fort">↑ Fort</option><option value="faible">↓ Faible</option></select>
                                <span v-else :class="['b-nat-lg', row.nature==='fort'?'nat-f':row.nature==='faible'?'nat-b':'nat-nd']">{{ row.nature==='fort'?'↑ Fort':row.nature==='faible'?'↓ Faible':'—' }}</span>
                              </td>
                              <!-- Ctrl : AS/AJ et DM/CM -->
                              <td class="td-c">
                                <select v-if="!isLocked&&canEdit(proc._k)" class="c-sel" :class="row.controle_present==='oui'?'s-oui':row.controle_present==='non'?'s-non':''" v-model="row.controle_present"><option :value="null">—</option><option value="oui">Oui</option><option value="non">Non</option></select>
                                <span v-else :class="['b-ctrl',row.controle_present==='oui'?'ctrl-o':'ctrl-n']">{{ row.controle_present==='oui'?'O':row.controle_present==='non'?'N':'—' }}</span>
                              </td>
                              <!-- Preuve : AS/AJ saisit -->
                              <td><input v-if="!isLocked&&canEdit(proc._k)" class="c-inp c-x" v-model="row.preuve" placeholder="Réf. doc…"/><span v-else class="ro-t ro-e">{{ row.preuve||'—' }}</span></td>
                              <!-- Observation : AS/AJ saisit -->
                              <td><input v-if="!isLocked&&canEdit(proc._k)" class="c-inp c-x" v-model="row.observation" placeholder="Obs…"/><span v-else class="ro-t ro-e">{{ row.observation||'—' }}</span></td>
                              <!-- Résultat : boutons toggle C/NC/PP -->
                              <td class="td-c">
                                <div v-if="!isLocked&&canEdit(proc._k)" class="res-btns">
                                  <button :class="['res-btn','res-btn-c',{active:row.resultat==='c'}]" @click="row.resultat=row.resultat==='c'?null:'c'" title="Conforme">C</button>
                                  <button :class="['res-btn','res-btn-nc',{active:row.resultat==='nc'}]" @click="row.resultat=row.resultat==='nc'?null:'nc'" title="Non conforme">NC</button>
                                  <button :class="['res-btn','res-btn-pp',{active:row.resultat==='pp'}]" @click="row.resultat=row.resultat==='pp'?null:'pp'" title="Partiel">PP</button>
                                </div>
                                <span v-else :class="['b-res','res-'+row.resultat]">{{ row.resultat?.toUpperCase()||'—' }}</span>
                              </td>
                              <td><button v-if="!isLocked&&canEdit(proc._k)" class="btn-del" @click="proc.items_matrice_parsed.splice(ri,1)">×</button></td>
                            </tr>
                          </template>
                        </tbody>
                      </table>
                    </div>

                    <!-- Appréciation globale — toujours visible avec radio buttons -->
                    <div class="sec-ttl mt14"><i class="ti ti-chart-bar"></i> Appréciation globale</div>
                    <div class="appre-grid">
                      <div v-for="grp in APPRE_GROUPS" :key="grp.key" class="appre-col">
                        <div class="appre-lbl">{{ grp.label }}</div>
                        <div class="radio-grp-v">
                          <label v-for="o in grp.options" :key="o.key" class="rlbl" :class="isLocked||!canEdit(proc._k)?'rlbl-ro':''">
                            <input type="radio" :value="o.key" v-model="(proc as any)[grp.field]" :disabled="isLocked||!canEdit(proc._k)"/>
                            <span :class="['b-ap', o.cls, (proc as any)[grp.field]===o.key?'b-ap--sel':'']">{{ o.label }}</span>
                          </label>
                        </div>
                      </div>
                    </div>

                    <!-- Synthèse -->
                    <div class="fg mt12">
                      <span class="flbl">Synthèse &amp; conclusions d'audit</span>
                      <textarea class="inp inp-ta" v-model="proc.commentaire" :disabled="isLocked||!canEdit(proc._k)" rows="4" placeholder="Résumé des constats, recommandations, plan d'action…"></textarea>
                    </div>
                    <div v-if="!isLocked&&canEdit(proc._k)" class="analyse-acts">
                      <button class="btn btn-xs btn-ghost" @click="clearResultats(proc)"><i class="ti ti-eraser"></i> Effacer résultats matrice</button>
                    </div>
                  </div>

                  <!-- ════ FF — Forces & Faiblesses + suggestion IA ════ -->
                  <div v-show="activeTab(proc._k)==='FF'" class="tab-content">

                    <!-- Barre outils IA -->
                    <div class="tb-bar">
                      <button v-if="!isLocked&&canEdit(proc._k)" class="btn btn-sm btn-ai"
                              @click="suggestFFIA(proc)" :disabled="iaLoading">
                        <i class="ti" :class="iaLoading?'ti-loader-2 spin':'ti-sparkles'"></i>
                        {{ iaLoading?'Génération…':'Générer Forces / Faiblesses par IA' }}
                      </button>
                      <span class="tb-ct" style="color:#6b7280;font-size:.68rem">
                        L'IA analyse la matrice de test pour suggérer les forces et faiblesses
                      </span>
                    </div>

                    <div class="ff-grid">
                      <!-- Forces -->
                      <div class="ff-col ff-col--f">
                        <div class="ff-hdr">
                          <i class="ti ti-arrow-up-circle"></i> Forces identifiées
                          <span class="ff-cnt">{{ (proc.forces||[]).length }}</span>
                          <button v-if="!isLocked&&canEdit(proc._k)" class="btn btn-xs ff-add" @click="addForce(proc)">
                            <i class="ti ti-plus"></i> Ajouter
                          </button>
                        </div>
                        <div v-if="!(proc.forces||[]).length" class="ff-empty">
                          Aucune force — ajoutez manuellement ou générez via IA
                        </div>
                        <div v-else class="ff-list">
                          <div v-for="(f,fi) in (proc.forces||[])" :key="fi" class="ff-item ff-item--f">
                            <i class="ti ti-arrow-up ff-dot"></i>
                            <textarea v-if="!isLocked&&canEdit(proc._k)"
                                      class="ff-inp" v-model="proc.forces[fi]"
                                      rows="2" placeholder="Décrire la force…"></textarea>
                            <span v-else class="ff-txt">{{ f }}</span>
                            <button v-if="!isLocked&&canEdit(proc._k)" class="btn-del"
                                    @click="proc.forces.splice(fi,1)">×</button>
                          </div>
                        </div>
                      </div>

                      <!-- Faiblesses -->
                      <div class="ff-col ff-col--w">
                        <div class="ff-hdr">
                          <i class="ti ti-arrow-down-circle"></i> Faiblesses identifiées
                          <span class="ff-cnt">{{ (proc.faiblesses||[]).length }}</span>
                          <button v-if="!isLocked&&canEdit(proc._k)" class="btn btn-xs ff-add" @click="addFaiblesse(proc)">
                            <i class="ti ti-plus"></i> Ajouter
                          </button>
                        </div>
                        <div v-if="!(proc.faiblesses||[]).length" class="ff-empty">
                          Aucune faiblesse — ajoutez manuellement ou générez via IA
                        </div>
                        <div v-else class="ff-list">
                          <div v-for="(w,wi) in (proc.faiblesses||[])" :key="wi" class="ff-item ff-item--w">
                            <i class="ti ti-arrow-down ff-dot"></i>
                            <textarea v-if="!isLocked&&canEdit(proc._k)"
                                      class="ff-inp" v-model="proc.faiblesses[wi]"
                                      rows="2" placeholder="Décrire la faiblesse…"></textarea>
                            <span v-else class="ff-txt">{{ w }}</span>
                            <button v-if="!isLocked&&canEdit(proc._k)" class="btn-del"
                                    @click="proc.faiblesses.splice(wi,1)">×</button>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div v-if="!isLocked&&canEdit(proc._k)" class="analyse-acts" style="margin-top:12px">
                      <button class="btn btn-xs btn-ghost" @click="clearFF(proc)">
                        <i class="ti ti-eraser"></i> Effacer tout
                      </button>
                    </div>
                  </div>

                  <!-- ════ DIAGRAMME BPMN — généré par IA + XML éditable ════ -->
                  <div v-show="activeTab(proc._k)==='DIAG'" class="tab-content">
                    <div class="tb-bar">
                      <button v-if="!isLocked&&canEdit(proc._k)" class="btn btn-sm btn-ai"
                              @click="suggestBpmnIA(proc)" :disabled="proc.bpmnGenerating">
                        <span v-if="proc.bpmnGenerating" class="spin-s"></span>
                        <i v-else class="ti ti-sparkles"></i>
                        {{ proc.bpmnGenerating?'Génération en cours…':'Générer par IA' }}
                      </button>
                      <button v-if="proc.bpmn_xml&&!isLocked&&canEdit(proc._k)"
                              class="btn btn-xs btn-ghost"
                              @click="proc.bpmnEditMode=!proc.bpmnEditMode">
                        <i class="ti" :class="proc.bpmnEditMode?'ti-eye':'ti-edit'"></i>
                        {{ proc.bpmnEditMode?'Vue':'Éditer XML' }}
                      </button>
                      <button v-if="proc.bpmn_xml&&!isLocked&&canEdit(proc._k)"
                              class="btn btn-xs btn-ghost"
                              @click="copyBpmn(proc.bpmn_xml)">
                        <i class="ti ti-copy"></i> Copier
                      </button>
                      <button v-if="proc.bpmn_xml&&!isLocked&&canEdit(proc._k)"
                              class="btn btn-xs btn-ghost"
                              @click="proc.bpmn_xml='';proc.bpmn_synthese=null;proc.bpmnEditMode=false">
                        <i class="ti ti-trash"></i> Effacer
                      </button>
                    </div>

                    <div v-if="proc.bpmnError" class="diag-err">
                      <i class="ti ti-alert-circle"></i> {{ proc.bpmnError }}
                    </div>

                    <!-- Vide -->
                    <div v-if="!proc.bpmn_xml&&!proc.bpmnGenerating" class="diag-empty">
                      <div class="diag-empty-ico"><i class="ti ti-git-branch"></i></div>
                      <p class="diag-empty-ttl">Aucun diagramme</p>
                      <p class="diag-empty-sub">
                        Cliquez « Générer par IA » — le diagramme est construit à partir
                        de la matrice de test et de la procédure analysée.
                        Vous pourrez ensuite modifier le XML directement.
                      </p>
                    </div>

                    <!-- Diagramme présent -->
                    <div v-if="proc.bpmn_xml" class="diag-result">

                      <!-- Synthèse IA -->
                      <div v-if="proc.bpmn_synthese?.titre" class="diag-synth">
                        <div class="ds-row"><span class="ds-l">Processus</span><span class="ds-v">{{ proc.bpmn_synthese.titre }}</span></div>
                        <div v-if="proc.bpmn_synthese.description" class="ds-row">
                          <span class="ds-l">Description</span><span class="ds-v">{{ proc.bpmn_synthese.description }}</span>
                        </div>
                        <div v-if="proc.bpmn_synthese.risques_principaux?.length" class="ds-row">
                          <span class="ds-l">Risques</span>
                          <span class="ds-v">{{ (proc.bpmn_synthese.risques_principaux as string[]).join(' · ') }}</span>
                        </div>
                      </div>

                      <!-- Mode édition XML -->
                      <div v-if="proc.bpmnEditMode" class="diag-edit-box">
                        <div class="diag-edit-bar">
                          <i class="ti ti-code" style="color:#7c3aed"></i>
                          <span>Édition XML BPMN 2.0</span>
                          <span class="diag-edit-hint">Modifiez directement le XML — sauvegardez avec le bouton Enregistrer</span>
                        </div>
                        <textarea class="diag-xml-edit" v-model="proc.bpmn_xml"
                                  rows="20"
                                  spellcheck="false"
                                  placeholder="Collez ou éditez votre XML BPMN 2.0 ici…"></textarea>
                      </div>

                      <!-- Mode lecture XML -->
                      <div v-else class="diag-xml-box">
                        <div class="diag-xml-bar">
                          <span><i class="ti ti-code"></i> BPMN 2.0 XML</span>
                        </div>
                        <pre class="diag-xml">{{ proc.bpmn_xml }}</pre>
                      </div>
                    </div>
                  </div>

                </div><!-- /proc-body -->
              </div><!-- /proc-block -->
            </div><!-- /procs-list -->
          </div><!-- /apt-procs -->
        </div><!-- /apt-layout -->

        <!-- FOOTER -->
        <footer class="apt-footer">
          <div>
            <button v-if="!isLocked" type="button" class="btn btn-ghost btn-sm" :disabled="processing" @click="annuler"><i class="ti ti-x"></i> Annuler</button>
            <button v-if="!isLocked" type="button" class="btn btn-save btn-sm" :disabled="processing" @click="submit">
              <span v-if="processing" class="spin-s"></span>
              <i v-else class="ti ti-device-floppy"></i>{{ form.id?'Mettre à jour':'Enregistrer' }}
            </button>
          </div>
          <div class="footer-mid"><span v-if="form.id" class="saved-code"><i class="ti ti-check"></i> {{ form.code }}</span></div>
          <div>
            <button v-if="form.id&&form.validation_status==='draft'" type="button" class="btn btn-sub btn-sm" :disabled="processing" @click="soumettre"><i class="ti ti-send"></i> Soumettre</button>
            <template v-if="canManage&&form.validation_status==='in_review'">
              <button type="button" class="btn btn-ok btn-sm" :disabled="processing" @click="valider('validate')"><i class="ti ti-circle-check"></i> Valider</button>
              <button type="button" class="btn btn-rej btn-sm" :disabled="processing" @click="promptReject"><i class="ti ti-circle-x"></i> Rejeter</button>
            </template>
          </div>
        </footer>
      </div><!-- /apt-body -->
    </div>

    <Teleport to="body">
      <Transition name="toast-t">
        <div v-if="toast.show" class="toast" :class="`toast--${toast.type}`">
          <i :class="toast.type==='success'?'ti ti-circle-check':'ti ti-circle-x'"></i>{{ toast.msg }}
        </div>
      </Transition>
    </Teleport>
  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, computed, reactive, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'
import axios from 'axios'

// ── Props ─────────────────────────────────────────────────────
const props = withDefaults(defineProps<{
  mission?:any; assignment?:any; auditorRole?:string; missionId?:number; assignmentId?:number
  form?:any; proceduresData?:any[]; aptList?:any[]; currentAuditor?:any
  phaseAuditeurs?:any[]; procedureAssignments?:Record<string,number>; templates?:any[]
  backUrl?:string; formUrl?:string; chatBaseUrl?:string; chatMessages?:any[]
  urlStore?:string; urlUpdate?:string; urlSoumettre?:string; urlValider?:string
  urlImportExcel?:string; urlAnalyzeDocument?:string; urlAiSuggest?:string
  urlAssignProcedure?:string; urlIndex?:string; urlLevelDocUpload?:string
  urlDeleteDoc?:string
}>(), {
  proceduresData:()=>[], aptList:()=>[], phaseAuditeurs:()=>[], procedureAssignments:()=>({}),
  templates:()=>[], chatMessages:()=>[],
})

// ── Constantes ────────────────────────────────────────────────
// Onglets DM/CM : ID + tous
const TABS_MANAGE = [
  {key:'ID',     label:'Identification', icon:'ti-id'},
  {key:'DOCS',   label:'Documents',      icon:'ti-paperclip'},
  {key:'ENTRET', label:'Entretien',      icon:'ti-message-question'},
  {key:'COLL',   label:'Collecte',       icon:'ti-clipboard'},
  {key:'ANALYSE',label:'Analyse',        icon:'ti-layout-rows'},
  {key:'FF',     label:'Forces / Faibl.', icon:'ti-list-check'},
  {key:'DIAG',   label:'Diagramme',      icon:'ti-git-branch'},
]
// Onglets AS/AJ : pas d'onglet ID (ils voient le résumé dans DOCS)
const TABS_AUDITOR = [
  {key:'DOCS',   label:'Documents',      icon:'ti-paperclip'},
  {key:'ENTRET', label:'Entretien',      icon:'ti-message-question'},
  {key:'COLL',   label:'Collecte',       icon:'ti-clipboard'},
  {key:'ANALYSE',label:'Analyse',        icon:'ti-layout-rows'},
  {key:'FF',     label:'Forces / Faibl.', icon:'ti-list-check'},
  {key:'DIAG',   label:'Diagramme',      icon:'ti-git-branch'},
]
const METHODES   = [{key:'aleatoire',label:'Aléatoire'},{key:'systematique',label:'Systématique'},{key:'jugement',label:'Jugement'},{key:'exhaustif',label:'Exhaustif'},{key:'autre',label:'Autre'}]
const STATUTS_C  = [{key:'a_collecter',label:'À col.'},{key:'obtenu',label:'Obtenu'},{key:'na',label:'N/A'}]
const APPRE_GROUPS = [
  {key:'c1',field:'niveau_conformite',  label:'Conformité',    options:[{key:'conforme',label:'Conforme',cls:'ap-c'},{key:'partiellement',label:'Partiel',cls:'ap-p'},{key:'non_conforme',label:'Non conf.',cls:'ap-n'}]},
  {key:'c2',field:'niveau_risque',      label:'Risque résid.', options:[{key:'faible',label:'Faible',cls:'ap-c'},{key:'modere',label:'Modéré',cls:'ap-p'},{key:'eleve',label:'Élevé',cls:'ap-n'},{key:'critique',label:'Critique',cls:'ap-cr'}]},
  {key:'c3',field:'fiabilite_controle', label:'Fiabilité',     options:[{key:'bon',label:'Bon',cls:'ap-c'},{key:'acceptable',label:'Accept.',cls:'ap-b'},{key:'insuffisant',label:'Insuff.',cls:'ap-p'},{key:'defaillant',label:'Défaill.',cls:'ap-n'}]},
  {key:'c4',field:'suites',             label:'Suites',        options:[{key:'aucune',label:'Aucune',cls:''},{key:'recommandation',label:'Recomm.',cls:''},{key:'plan_action',label:"Plan d'action",cls:''},{key:'escalade',label:'Escalade',cls:''}]},
]

// ── State ─────────────────────────────────────────────────────
let _sk=0; const gk=()=>++_sk

const form = reactive<any>({
  id:null,code:'',validation_status:'draft',validation_note:'',
  fait_par:'',revue_par:'',date_fait:'',date_revue:'',commentaire_global:'',
  ...(props.form??{})
})

// Normaliser proceduresData : parser les JSON
const procedures = reactive<any[]>(
  (props.proceduresData as any[]).map(p=>({
    ...p, _k:gk(),
    bpmn_synthese: p.bpmn_synthese
      ? (typeof p.bpmn_synthese==='string' ? JSON.parse(p.bpmn_synthese) : p.bpmn_synthese)
      : null,
    items_matrice_parsed:    safeJson(p.items_matrice??p.levels?.[0]?.items_matrice),
    plan_collecte_parsed:    safeJson(p.plan_collecte??p.levels?.[0]?.plan_collecte),
    grille_entretien_parsed: safeJson(p.grille_entretien??p.levels?.[0]?.grille_entretien),
    attached_docs: p.attached_docs||[],
    activeDocIdx: null as number|null,
    uploading: false,
    niveau_conformite:p.niveau_conformite??null, niveau_risque:p.niveau_risque??null,
    fiabilite_controle:p.fiabilite_controle??null, suites:p.suites??null, commentaire:p.commentaire??'',
    forces: safeJson(p.forces), faiblesses: safeJson(p.faiblesses),
    bpmnGenerating:false, bpmnError:null, bpmnEditMode:false,
  }))
)

// Affectations _k → auditeur_id
const procAssignments = reactive<Record<string|number,number|null>>(
  Object.fromEntries(procedures.map(p=>[
    p._k,
    props.procedureAssignments?.[String(p.id)] ? Number(props.procedureAssignments![String(p.id)]) : null
  ]))
)

const expandedProcs = ref<Set<string|number>>(new Set(procedures.map(p=>p._k)))
const procTab       = reactive<Record<string|number,string>>({})
const processing    = ref(false)
const iaLoading     = ref(false)
const newProcTitle  = ref('')
const showIaSuggest = ref(false)
const iaProcPrompt  = ref('')

// Visionneuse doc
const docState = reactive<{name:string;type:'pdf'|'image'|'other'|null;objectUrl:string|null;analyzing:boolean;analyzed:boolean;error:string|null;aiResult:any|null;pendingKey:string|number|null}>({
  name:'',type:null,objectUrl:null,analyzing:false,analyzed:false,error:null,aiResult:null,pendingKey:null
})
const docLoaded = computed(()=>!!docState.name)

// Toast
const toast=ref({show:false,type:'success',msg:''})
let _tt:any
function showToast(t:string,m:string){ if(_tt) clearTimeout(_tt); toast.value={show:true,type:t,msg:m}; _tt=setTimeout(()=>{toast.value.show=false},4000) }

// ── Computed ──────────────────────────────────────────────────
const canManage = computed(()=>['DM','CM'].includes(props.auditorRole??''))
const isLocked  = computed(()=>form.validation_status==='validated'||(form.validation_status==='in_review'&&!canManage.value))
const unreadCount = ref(0) // simplifié

// ── Fonctions onglets ─────────────────────────────────────────
function getTabsForRole(){ return canManage.value ? TABS_MANAGE : TABS_AUDITOR }
function defaultTab():string { return canManage.value ? 'ID' : 'DOCS' }
function activeTab(key:string|number):string { return procTab[key] || defaultTab() }

// ── Droits ────────────────────────────────────────────────────
// RÈGLE SIMPLE :
//   DM/CM → peut tout faire sur tout
//   AS/AJ → peut tout faire sur les procédures visibles
//            (le contrôleur PHP ne leur envoie QUE leurs procédures assignées)
//            + le formulaire n'est pas verrouillé
function canEdit(key:string|number):boolean{
  if(isLocked.value) return false
  return true  // DM/CM et AS/AJ : tous peuvent éditer ce qu'ils voient
}
// Pour l'affichage des badges d'affectation dans l'en-tête
function getAssigned(key:string|number):any|null{
  const id = procAssignments[key]
  if(!id) return null
  return (props.phaseAuditeurs as any[]).find(a=>Number(a.id)===Number(id)) ?? null
}

async function assignProc(key:string|number, audStr:string){
  const audId=audStr?Number(audStr):null
  procAssignments[key]=audId
  const proc=procedures.find(p=>p._k===key)
  if(!proc?.id||!props.urlAssignProcedure) return
  try{
    const res=await fetch(props.urlAssignProcedure,{
      method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},
      body:JSON.stringify({apt_id:form.id,procedure_id:proc.id,auditeur_id:audId,assignment_id:props.assignmentId})
    })
    const d=await res.json()
    showToast(d.success?'success':'error', d.success?(audId?'Procédure affectée.':'Affectation retirée.'):(d.error??'Erreur'))
  }catch{showToast('error','Erreur réseau')}
}

// ── Toggles ────────────────────────────────────────────────────
function toggleProc(key:string|number){ expandedProcs.value.has(key)?expandedProcs.value.delete(key):expandedProcs.value.add(key) }

// ── CRUD Procédures ────────────────────────────────────────────
function addProcedure(data?:any){
  if(!data&&!newProcTitle.value.trim()) return
  const k=gk()
  procedures.push({
    _k:k, intitule:data?.intitule||newProcTitle.value.trim(),
    ref_procedure:data?.ref_procedure||'', service_dept:data?.service_dept||'',
    responsable_proc:data?.responsable_proc||'', version_vigueur:data?.version_vigueur||'',
    description:data?.description||'', population_totale:null, taille_echantillon:null,
    methode_echantillonnage:null, statut:'en_cours',
    niveau_conformite:null, niveau_risque:null, fiabilite_controle:null, suites:null, commentaire:'',
    forces:[], faiblesses:[],
    bpmn_xml:'', bpmn_synthese:null, bpmnGenerating:false, bpmnError:null, bpmnEditMode:false,
    attached_docs:[], activeDocIdx:null as number|null, uploading:false,
    items_matrice_parsed:data?.items_matrice||[],
    plan_collecte_parsed:data?.plan_collecte||[],
    grille_entretien_parsed:data?.grille_entretien||[],
  })
  // Auto-affecter au 1er AS de la phase
  const firstAS=(props.phaseAuditeurs as any[]).find(a=>a.role_code==='AS')||(props.phaseAuditeurs as any[]).find(a=>a.role_code==='AJ')
  procAssignments[k]=firstAS?.id??null
  expandedProcs.value.add(k)
  procTab[k]='ID'
  newProcTitle.value=''
}

function removeProc(key:string|number){
  if(!confirm('Supprimer cette procédure ?')) return
  const i=procedures.findIndex(p=>p._k===key); if(i>=0) procedures.splice(i,1)
  expandedProcs.value.delete(key); delete procAssignments[key]
}

// ── Lignes tableaux ────────────────────────────────────────────
function addMatriceRow(p:any){ const n=p.items_matrice_parsed.filter((r:any)=>!r.is_section).length+1; p.items_matrice_parsed.push({num:String(n),is_section:false,point_controle:'',nature:null,controle_present:null,preuve:'',observation:'',resultat:null}) }
function addSectionRow(p:any){ p.items_matrice_parsed.push({is_section:true,section:''}) }
function addCollecteRow(p:any){ p.plan_collecte_parsed.push({information:'',source:'',methode_collecte:'',statut:null}) }
function addGrilleRow(p:any){ const n=p.grille_entretien_parsed.filter((r:any)=>!r.is_axe).length+1; p.grille_entretien_parsed.push({num:`Q${n}`,is_axe:false,question:'',obj_audit:'',reponse:''}) }
function addAxeRow(p:any){ p.grille_entretien_parsed.push({is_axe:true,axe:''}) }
function addForce(p:any){ if(!p.forces) p.forces=[]; p.forces.push('') }
function addFaiblesse(p:any){ if(!p.faiblesses) p.faiblesses=[]; p.faiblesses.push('') }
function clearFF(p:any){ p.forces=[]; p.faiblesses=[] }
function clearResultats(p:any){ (p.items_matrice_parsed||[]).forEach((r:any)=>{if(!r.is_section){r.resultat=null;r.observation=''}}); p.forces=[]; p.faiblesses=[] }

// ── Import Excel ───────────────────────────────────────────────
async function importExcel(e:Event,proc:any,section:string){
  const f=(e.target as HTMLInputElement).files?.[0]; (e.target as HTMLInputElement).value=''; if(!f) return
  const fd=new FormData(); fd.append('file',f); fd.append('section',section)
  try{
    const res=await axios.post(props.urlImportExcel||'',fd,{headers:{'Content-Type':'multipart/form-data'}})
    if(res.data.success){
      if(section==='B') proc.items_matrice_parsed=res.data.items||[]
      if(section==='C') proc.plan_collecte_parsed=res.data.items||[]
      if(section==='D') proc.grille_entretien_parsed=res.data.items||[]
      showToast('success',`${res.data.count} lignes importées`)
    }
  }catch{showToast('error','Erreur import')}
}

// ── Visionneuse document ───────────────────────────────────────
function openDoc(file:File, key:string|number|null){
  if(docState.objectUrl) URL.revokeObjectURL(docState.objectUrl)
  const ext=file.name.split('.').pop()?.toLowerCase()||''
  const isPdf=ext==='pdf', isImg=['png','jpg','jpeg','webp'].includes(ext)
  Object.assign(docState,{name:file.name,type:isPdf?'pdf':isImg?'image':'other',objectUrl:(isPdf||isImg)?URL.createObjectURL(file):null,analyzing:true,analyzed:false,error:null,aiResult:null,pendingKey:key})
}
function closeDoc(){ if(docState.objectUrl) URL.revokeObjectURL(docState.objectUrl); Object.assign(docState,{name:'',type:null,objectUrl:null,analyzing:false,analyzed:false,error:null,aiResult:null,pendingKey:null}) }

async function onDocUpload(e:Event){
  const f=(e.target as HTMLInputElement).files?.[0]; (e.target as HTMLInputElement).value=''; if(!f) return
  openDoc(f,null); await analyzeDoc(f)
}
async function onDocUploadForProc(e:Event,key:string|number){
  const f=(e.target as HTMLInputElement).files?.[0]; (e.target as HTMLInputElement).value=''; if(!f) return
  openDoc(f,key); await analyzeDoc(f)
}

async function analyzeDoc(file:File){
  try{
    const fd=new FormData(); fd.append('document',file)
    if(props.missionId) fd.append('mission_id',String(props.missionId))
    const res=await axios.post(props.urlAnalyzeDocument||'',fd,{headers:{'Content-Type':'multipart/form-data','X-CSRF-TOKEN':csrf()},timeout:120000})
    if(res.data.success===false) throw new Error(res.data.error)
    const d=res.data
    docState.analyzing=false; docState.analyzed=true
    docState.aiResult={synthese:d.synthese||{},items_matrice:d.items_matrice||d.matrice_b||[],plan_collecte:d.plan_collecte||d.collecte_c||[],grille_entretien:d.grille_entretien||d.grille_d||[]}
    // Si proc existante ciblée → injecter
    if(docState.pendingKey!==null){
      const proc=procedures.find(p=>p._k===docState.pendingKey)
      if(proc){ injectIntoProc(proc,docState.aiResult); showToast('success','Données injectées dans la procédure') }
    }
  }catch(err:any){
    docState.analyzing=false; docState.error=err.response?.data?.error||err.message||'Erreur'
    showToast('error','Erreur : '+docState.error)
  }
}

function injectIntoProc(proc:any, data:any){
  const s=data.synthese||{}
  if(!proc.intitule&&s.titre) proc.intitule=s.titre
  if(!proc.ref_procedure&&s.ref_procedure) proc.ref_procedure=s.ref_procedure
  if(!proc.service_dept&&s.domaine) proc.service_dept=s.domaine
  if(!proc.description&&s.description) proc.description=s.description
  if((data.items_matrice||[]).length) proc.items_matrice_parsed=data.items_matrice
  if((data.plan_collecte||[]).length) proc.plan_collecte_parsed=data.plan_collecte
  if((data.grille_entretien||[]).length) proc.grille_entretien_parsed=data.grille_entretien
}

function createProcFromAI(){
  if(!docState.aiResult) return
  const s=docState.aiResult.synthese||{}
  addProcedure({intitule:s.titre||docState.name,ref_procedure:s.ref_procedure||'',service_dept:s.domaine||'',description:s.description||'',version_vigueur:s.version||'',items_matrice:docState.aiResult.items_matrice||[],plan_collecte:docState.aiResult.plan_collecte||[],grille_entretien:docState.aiResult.grille_entretien||[]})
  showToast('success','Procédure créée depuis le document')
  docState.analyzed=false; docState.aiResult=null
}

// ── IA ────────────────────────────────────────────────────────
async function suggestProcedureIA(){
  if(!iaProcPrompt.value.trim()) return; iaLoading.value=true
  try{
    const res=await axios.post(props.urlAiSuggest||'',{type:'procedure_complete',prompt:iaProcPrompt.value,mission_id:props.missionId,mission_title:props.mission?.title,entity_name:props.mission?.entity_name},{headers:{'X-CSRF-TOKEN':csrf()}})
    if(!res.data.success) throw new Error(res.data.error)
    const d=res.data; const allMat:any[]=[],allCol:any[]=[],allGri:any[]=[]
    ;(d.levels||[]).forEach((l:any)=>{allMat.push(...(l.items_matrice||[]));allCol.push(...(l.plan_collecte||[]));allGri.push(...(l.grille_entretien||[]))})
    addProcedure({intitule:d.intitule||iaProcPrompt.value,ref_procedure:d.ref_procedure||'',service_dept:d.service_dept||'',responsable_proc:d.responsable_proc||'',description:d.description||'',items_matrice:allMat,plan_collecte:allCol,grille_entretien:allGri})
    iaProcPrompt.value=''; showIaSuggest.value=false; showToast('success',`Procédure "${d.intitule}" créée`)
  }catch(err:any){showToast('error','Erreur IA : '+(err.response?.data?.error||err.message))}
  finally{iaLoading.value=false}
}

async function suggestMatriceIA(proc:any){ await callAiSuggest(proc,'matrice_niveau',(d)=>{ proc.items_matrice_parsed=d.items||[]; showToast('success',`${proc.items_matrice_parsed.length} points générés`) }) }
async function suggestCollecteIA(proc:any){ await callAiSuggest(proc,'collecte_niveau',(d)=>{ proc.plan_collecte_parsed=d.items||[]; showToast('success','Plan de collecte généré') }) }
async function suggestGrilleIA(proc:any){
  await callAiSuggest(proc,'collecte_niveau',(d)=>{
    proc.grille_entretien_parsed=(d.items||[]).map((item:any,i:number)=>({num:`Q${i+1}`,is_axe:false,question:item.information||item.question||'',obj_audit:item.source||'',reponse:''}))
    showToast('success',`${proc.grille_entretien_parsed.length} questions générées`)
  }, `Grille d'entretien pour "${proc.intitule}"`)
}

async function suggestBpmnIA(proc:any){
  proc.bpmnGenerating=true; proc.bpmnError=null
  try{
    const res=await axios.post(props.urlAiSuggest||'',{
      type:'bpmn_procedure',procedure_title:proc.intitule,procedure_description:proc.description,
      items_matrice:(proc.items_matrice_parsed||[]).filter((r:any)=>!r.is_section).slice(0,12),
      mission_id:props.missionId,mission_title:props.mission?.title,entity_name:props.mission?.entity_name,
    },{headers:{'X-CSRF-TOKEN':csrf()}})
    if(!res.data.success) throw new Error(res.data.error||'Erreur IA')
    proc.bpmn_xml=res.data.bpmn_xml||res.data.xml||''
    proc.bpmn_synthese=res.data.bpmn_synthese||null
    proc.bpmnEditMode=false
    showToast('success','Diagramme BPMN généré')
  }catch(err:any){ proc.bpmnError=err.response?.data?.error||err.message||'Erreur'; showToast('error','Erreur BPMN : '+proc.bpmnError) }
  finally{ proc.bpmnGenerating=false }
}

// Générer Forces & Faiblesses par IA depuis la matrice de test
async function suggestFFIA(proc:any){
  iaLoading.value=true
  try{
    const points=(proc.items_matrice_parsed||[])
      .filter((r:any)=>!r.is_section).slice(0,15)
      .map((r:any)=>({point:r.point_controle||'',nature:r.nature||'',ctrl:r.controle_present||'',resultat:r.resultat||'',obs:r.observation||''}))

    const res=await axios.post(props.urlAiSuggest||'',{
      type:'forces_faiblesses',
      procedure_title:proc.intitule,
      procedure_description:proc.description,
      points_controle:points,
      mission_id:props.missionId,
    },{headers:{'X-CSRF-TOKEN':csrf()}})

    if(!res.data.success) throw new Error(res.data.error||'Erreur IA')
    if((res.data.forces||[]).length)     proc.forces     = res.data.forces
    if((res.data.faiblesses||[]).length) proc.faiblesses = res.data.faiblesses
    showToast('success',`${(res.data.forces||[]).length} force(s) et ${(res.data.faiblesses||[]).length} faiblesse(s) générées`)
  }catch(err:any){ showToast('error','Erreur IA : '+(err.response?.data?.error||err.message)) }
  finally{ iaLoading.value=false }
}

async function callAiSuggest(proc:any, type:string, onSuccess:(d:any)=>void, prompt?:string){
  iaLoading.value=true
  try{
    const res=await axios.post(props.urlAiSuggest||'',{type,procedure_title:proc.intitule,niveau_code:'N1',niveau_libelle:proc.intitule,items_matrice:proc.items_matrice_parsed?.slice(0,10),mission_id:props.missionId,prompt:prompt||''},{headers:{'X-CSRF-TOKEN':csrf()}})
    if(!res.data.success) throw new Error(res.data.error)
    onSuccess(res.data)
  }catch(err:any){showToast('error','Erreur IA : '+err.message)}
  finally{iaLoading.value=false}
}

// ── Submit ────────────────────────────────────────────────────
function serializeProcs(){
  return procedures.map((p,pi)=>({
    id:p.id||undefined, ordre:pi+1,
    ref_procedure:p.ref_procedure, intitule:p.intitule, version_vigueur:p.version_vigueur,
    service_dept:p.service_dept, responsable_proc:p.responsable_proc,
    date_entree_vigueur:p.date_entree_vigueur, date_derniere_revision:p.date_derniere_revision,
    description:p.description, population_totale:p.population_totale,
    taille_echantillon:p.taille_echantillon, methode_echantillonnage:p.methode_echantillonnage,
    statut:p.statut, bpmn_xml:p.bpmn_xml||'',
    bpmn_synthese:p.bpmn_synthese?JSON.stringify(p.bpmn_synthese):null,
    niveau_conformite:p.niveau_conformite, niveau_risque:p.niveau_risque,
    fiabilite_controle:p.fiabilite_controle, suites:p.suites, commentaire:p.commentaire,
    forces: JSON.stringify(p.forces||[]),
    faiblesses: JSON.stringify(p.faiblesses||[]),
    levels:[{code_niveau:'N1',libelle_niveau:p.intitule||'Test',statut_niveau:p.statut||'en_cours',
      items_matrice:JSON.stringify(p.items_matrice_parsed||[]),
      plan_collecte:JSON.stringify(p.plan_collecte_parsed||[]),
      grille_entretien:JSON.stringify(p.grille_entretien_parsed||[]),
    }],
  }))
}

async function submit(){
  processing.value=true
  try{
    const payload={
      mission_id:props.missionId, assignment_id:props.assignmentId,
      fait_par:form.fait_par, revue_par:form.revue_par,
      date_fait:form.date_fait, date_revue:form.date_revue,
      commentaire_global:form.commentaire_global,
      procedures:JSON.stringify(serializeProcs()),
    }
    const method=form.id?'PUT':'POST'
    const url=form.id?(props.urlUpdate||`${props.formUrl}/${form.id}`):(props.urlStore||props.formUrl)
    const res=await fetch(url!,{method,headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify(payload)})
    const d=await res.json()
    if(d.success||res.ok){
      showToast('success',form.id?'Analyse mise à jour.':'Analyse créée.')
      if(!form.id&&d.form?.id){ form.id=d.form.id; form.code=d.form.code }
      if(d.form) Object.assign(form,d.form)
    }else showToast('error',d.message??'Erreur.')
  }catch{showToast('error','Erreur réseau.')}
  finally{processing.value=false}
}

function annuler(){ if(props.backUrl) router.visit(props.backUrl) }

async function soumettre(){
  processing.value=true
  try{
    const res=await fetch(props.urlSoumettre||`${props.formUrl}/${form.id}/soumettre`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({mission_id:props.missionId,assignment_id:props.assignmentId})})
    const d=await res.json()
    if(d.success){form.validation_status='in_review';showToast('success','Soumis.')}
    else showToast('error',d.error??'Erreur')
  }catch{showToast('error','Erreur réseau')}
  processing.value=false
}

async function valider(action:string,note?:string){
  processing.value=true
  try{
    const res=await fetch(props.urlValider||`${props.formUrl}/${form.id}/valider`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({mission_id:props.missionId,assignment_id:props.assignmentId,action,note})})
    const d=await res.json()
    if(d.success){form.validation_status=d.status;showToast('success',action==='validate'?'Validé ✓':'Rejeté.')}
    else showToast('error',d.error??'Erreur')
  }catch{showToast('error','Erreur réseau')}
  processing.value=false
}
function promptReject(){ const n=prompt('Motif du rejet :'); if(!n?.trim()) return; valider('reject',n.trim()) }
function loadApt(a:any){ router.visit(`${props.urlIndex?.replace(/\/[^/]*$/,'')||''}/${a.id}/edit?mission_id=${props.missionId}&assignment_id=${props.assignmentId}`) }
function openChat(){}

// ── Helpers ────────────────────────────────────────────────────
function safeJson(v:any):any[]{ if(Array.isArray(v)) return v; if(!v) return []; try{const d=JSON.parse(v);return Array.isArray(d)?d:[]}catch{return[]} }
function matCount(p:any){ return(p.items_matrice_parsed||[]).filter((r:any)=>!r.is_section).length }
function countR(p:any,v:string){ return(p.items_matrice_parsed||[]).filter((r:any)=>!r.is_section&&r.resultat===v).length }
function rCls(row:any){ return row.resultat==='c'?'row-c':row.resultat==='nc'?'row-nc':row.resultat==='pp'?'row-pp':'' }
// ── Upload doc pour une procédure (DM/CM et AS/AJ assignés) ──────
async function uploadDocForProc(e:Event, proc:any){
  const file=(e.target as HTMLInputElement).files?.[0]
  ;(e.target as HTMLInputElement).value=''
  if(!file||!form.id) return

  proc.uploading=true
  try{
    const fd=new FormData()
    fd.append('file', file)
    fd.append('apt_id', String(form.id))
    if(proc.id) fd.append('procedure_id', String(proc.id))

    const res=await axios.post(props.urlLevelDocUpload||'', fd, {
      headers:{'Content-Type':'multipart/form-data','X-CSRF-TOKEN':csrf()}
    })
    if(!res.data.success) throw new Error(res.data.error||'Erreur upload')

    if(!proc.attached_docs) proc.attached_docs=[]
    proc.attached_docs.push(res.data.document)
    showToast('success', `"${file.name}" joint à la procédure`)
  }catch(err:any){
    showToast('error', 'Erreur upload : '+(err.response?.data?.error||err.message))
  }finally{
    proc.uploading=false
  }
}

function toggleDocViewer(proc:any, idx:number){
  if(proc.activeDocIdx===idx) proc.activeDocIdx=null
  else proc.activeDocIdx=idx
}

async function removeDocFromProc(proc:any, idx:number){
  if(!confirm('Supprimer ce document ?')) return
  const doc = proc.attached_docs[idx]
  // Si le doc a un id (déjà persisté), appeler l'API
  if(doc?.id && props.urlDeleteDoc && form.id){
    try{
      const res = await fetch(props.urlDeleteDoc, {
        method:'DELETE',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},
        body:JSON.stringify({doc_id:doc.id, apt_id:form.id})
      })
      const d = await res.json()
      if(!d.success){ showToast('error', d.error??'Erreur suppression'); return }
    }catch{ showToast('error','Erreur réseau'); return }
  }
  // Retirer localement
  proc.attached_docs.splice(idx,1)
  if(proc.activeDocIdx===idx) proc.activeDocIdx=null
  else if(proc.activeDocIdx>idx) proc.activeDocIdx--
  showToast('success','Document supprimé')
}

function isPdf(doc:any):boolean{
  const ext=(doc.extension||doc.name||'').split('.').pop()?.toLowerCase()||''
  return ext==='pdf'||(doc.mime_type||'').includes('pdf')
}
function isImage(doc:any):boolean{
  const ext=(doc.extension||doc.name||'').split('.').pop()?.toLowerCase()||''
  return ['png','jpg','jpeg','gif','webp','svg'].includes(ext)||(doc.mime_type||'').startsWith('image/')
}

function docIcon(n:string){ const e=(n||'').split('.').pop()?.toLowerCase()||''; if(e==='pdf') return 'ti-file-type-pdf'; if(['doc','docx'].includes(e)) return 'ti-file-type-doc'; if(['xls','xlsx'].includes(e)) return 'ti-file-type-xls'; if(['png','jpg','jpeg'].includes(e)) return 'ti-file-type-jpg'; return 'ti-file' }
function copyBpmn(x:string){ navigator.clipboard?.writeText(x).then(()=>showToast('success','XML copié')) }
function csrf(){ return(document.querySelector('meta[name=csrf-token]')as HTMLMetaElement)?.content??'' }
function vstLbl(s:string){ return({draft:'Brouillon',in_review:'En attente',validated:'Validé ✓',rejected:'Rejeté'}as any)[s]??s }
function vstIcon(s:string){ return({draft:'ti ti-pencil',in_review:'ti ti-clock',validated:'ti ti-circle-check',rejected:'ti ti-circle-x'}as any)[s]??'ti ti-circle' }

onMounted(()=>{})
onBeforeUnmount(()=>{ if(_tt) clearTimeout(_tt); if(docState.objectUrl) URL.revokeObjectURL(docState.objectUrl) })
</script>


<style scoped>
*,*::before,*::after{box-sizing:border-box}
.apt-shell{display:flex;flex-direction:column;min-height:100vh;font-family:'Segoe UI',system-ui,sans-serif;background:#f0f4f8}
/* ── Header ── */
.apt-header{background:#fff;border-bottom:1px solid #e5e7eb;padding:12px 20px 0;position:sticky;top:0;z-index:50;box-shadow:0 1px 4px rgba(0,0,0,.05)}
.apt-hrow{display:flex;align-items:flex-start;gap:10px;padding-bottom:10px}
.apt-back{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border:1px solid #e5e7eb;border-radius:7px;color:#6b7280;text-decoration:none;flex-shrink:0}
.apt-back:hover{background:#f3f4f6}
.apt-hinfo{flex:1;min-width:0}
.apt-chips{display:flex;align-items:center;gap:5px;flex-wrap:wrap;margin-bottom:3px}
.apt-code{font-size:.68rem;font-weight:700;background:#1e293b;color:#fff;padding:2px 7px;border-radius:4px;font-family:ui-monospace,monospace}
.apt-chip{display:inline-flex;align-items:center;gap:3px;font-size:.66rem;font-weight:600;padding:2px 7px;border-radius:9px;border:1px solid transparent}
.chip-draft{background:#f3f4f6;color:#6b7280;border-color:#e5e7eb}
.chip-in_review{background:#e3f2fd;color:#1565C0;border-color:rgba(21,101,192,.2)}
.chip-validated{background:#ecfdf5;color:#059669;border-color:#a7f3d0}
.chip-rejected{background:#fef2f2;color:#dc2626;border-color:#fecaca}
.chip-type{background:#ede9fe;color:#7c3aed;border-color:#c4b5fd}
.chip-role-DM{background:#f5f3ff;color:#7c3aed;border-color:#ddd6fe}
.chip-role-CM{background:#f0f9ff;color:#0284c7;border-color:#bae6fd}
.chip-role-AS{background:#f0fdf4;color:#059669;border-color:#a7f3d0}
.chip-role-AJ{background:#fffbeb;color:#d97706;border-color:#fde68a}
.apt-title{font-size:1rem;font-weight:800;color:#111827;margin:0 0 3px}
.apt-meta{display:flex;align-items:center;gap:12px;flex-wrap:wrap;font-size:.72rem;color:#6b7280}
.apt-meta span{display:flex;align-items:center;gap:3px}
.apt-hactions{display:flex;align-items:center;gap:7px;flex-shrink:0}
.apt-banner{display:flex;align-items:center;gap:7px;padding:6px 0;font-size:.76rem;font-weight:500}
.banner-lock{color:#059669;border-top:1px solid #a7f3d0}.banner-review{color:#1565C0}.banner-reject{color:#dc2626}
/* ── Body layout ── */
.apt-body{flex:1;overflow:hidden;display:flex;flex-direction:column}
/* sans doc : 240px sidebar + 1fr procs */
.apt-layout{display:grid;grid-template-columns:240px 1fr;gap:0;flex:1;overflow:hidden;height:calc(100vh - 118px)}
/* avec doc : sidebar + viewer + procs */
.apt-layout.with-doc{grid-template-columns:220px minmax(320px,1fr) 420px}
/* ── Sidebar ── */
.apt-sidebar{overflow-y:auto;border-right:1px solid #e5e7eb;background:#f9fafb;padding:10px;display:flex;flex-direction:column;gap:8px}
.apt-sidebar::-webkit-scrollbar{width:3px}
.card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;flex-shrink:0}
.card-label{display:flex;align-items:center;gap:5px;font-size:.64rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;padding:7px 10px;background:#f9fafb;border-bottom:1px solid #e5e7eb}
.card-cnt{margin-left:auto;font-size:.6rem;font-weight:800;background:#e2e8f0;color:#64748b;padding:1px 5px;border-radius:6px}
.card-body{padding:8px 10px}
.fg{display:flex;flex-direction:column;gap:2px;margin-bottom:7px}
.fg:last-child{margin-bottom:0}
.flbl{font-size:.6rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.04em;display:block}
.form-r2{display:grid;grid-template-columns:1fr 1fr;gap:6px}
.inp{background:#fff;border:1px solid #e5e7eb;color:#111827;padding:5px 8px;border-radius:5px;font-size:.76rem;outline:none;transition:border-color .15s;font-family:inherit;width:100%}
.inp:focus{border-color:#2563EB;box-shadow:0 0 0 2px rgba(37,99,235,.1)}
.inp:disabled,.inp-ro{background:#f9fafb;color:#9ca3af;cursor:default}
.inp-ta{resize:vertical;min-height:52px}
.empty-s{display:flex;align-items:center;gap:5px;font-size:.7rem;color:#9ca3af;padding:4px}
.aud-row{display:flex;align-items:center;gap:6px;padding:5px 7px;border-radius:6px;border:1px solid #e9ecef;background:#fafafa;margin-bottom:3px}
.aud-av{width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.52rem;font-weight:800;flex-shrink:0;border:2px solid transparent}
.av-DM{background:#f5f3ff;color:#7c3aed;border-color:#ddd6fe}.av-CM{background:#f0f9ff;color:#0284c7;border-color:#bae6fd}
.av-AS{background:#f0fdf4;color:#059669;border-color:#a7f3d0}.av-AJ{background:#fffbeb;color:#d97706;border-color:#fde68a}
.aud-inf{flex:1;min-width:0}
.aud-nm{font-size:.7rem;font-weight:600;color:#111827;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.aud-cd{font-size:.58rem;font-family:monospace;color:#9ca3af;display:block}
.aud-rl{font-size:.58rem;font-weight:700;padding:2px 5px;border-radius:5px;border:1px solid transparent;flex-shrink:0}
.stbl{width:100%;border-collapse:collapse;font-size:.72rem}
.stbl thead tr{background:#f9fafb}
.stbl th,.stbl td{padding:6px 9px;border-bottom:1px solid #f3f4f6;text-align:left}
.stbl th{font-size:.62rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.04em}
.stbl-row{cursor:pointer;transition:background .1s}.stbl-row:hover{background:#f9fafb}
.td-empty{text-align:center;color:#d1d5db;padding:12px}
.td-code{font-family:ui-monospace,monospace;font-size:.68rem;color:#6b7280}
.lg-row{display:flex;align-items:flex-start;gap:8px;padding:5px 0;border-bottom:1px solid #f3f4f6}
.lg-b{width:24px;height:16px;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:800;flex-shrink:0}
.lg-c{background:#d1fae5;color:#065f46}.lg-nc{background:#fef2f2;color:#991b1b}.lg-pp{background:#fef3c7;color:#92400e}
.lg-row b{font-size:.74rem;color:#374151;display:block}.lg-row p{font-size:.66rem;color:#9ca3af;margin:0}
/* ── Doc viewer ── */
.apt-docviewer{display:flex;flex-direction:column;border-right:1px solid #e5e7eb;background:#1a1a2e;overflow:hidden}
.dv-bar{display:flex;align-items:center;gap:7px;padding:7px 11px;background:#2d2d4e;border-bottom:1px solid #3d3d5e;flex-shrink:0}
.dv-name{flex:1;font-size:.7rem;font-weight:600;color:#e2e8f0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.dv-badge-ana{display:inline-flex;align-items:center;gap:5px;font-size:.62rem;color:#a78bfa;background:rgba(167,139,250,.15);padding:2px 7px;border-radius:8px;flex-shrink:0}
.dv-badge-ok{display:inline-flex;align-items:center;gap:4px;font-size:.62rem;color:#34d399;background:rgba(52,211,153,.15);padding:2px 7px;border-radius:8px;flex-shrink:0}
.dv-cls{width:22px;height:22px;border-radius:5px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);color:#94a3b8;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.68rem;flex-shrink:0}
.dv-cls:hover{background:rgba(239,68,68,.25);color:#fca5a5}
.dv-body{flex:1;overflow:auto;min-height:0}
.dv-iframe{width:100%;height:100%;border:none;background:#fff}
.dv-imgwrap{padding:10px;display:flex;justify-content:center}
.dv-img{max-width:100%;height:auto;border-radius:5px}
.dv-ph{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;padding:32px;color:#64748b}
.dv-ph i{font-size:2rem;color:#4b5563}
.dv-ph p{font-size:.76rem;color:#9ca3af}
.dv-ai{background:#2d2d4e;border-top:1px solid #3d3d5e;padding:10px;flex-shrink:0}
.dv-ai-ttl{font-size:.7rem;font-weight:700;color:#a78bfa;display:flex;align-items:center;gap:5px;margin-bottom:8px}
.dv-ai-row{display:flex;align-items:flex-start;gap:8px;padding:4px 0;border-bottom:1px solid #3d3d5e}
.dv-ai-lbl{font-size:.62rem;font-weight:700;color:#6b7280;text-transform:uppercase;width:65px;flex-shrink:0}
.dv-ai-val{font-size:.72rem;color:#e2e8f0;flex:1}
.dv-ai-stats{display:flex;gap:10px;padding:7px 0;font-size:.64rem;color:#94a3b8}
.dv-ai-stats span{display:flex;align-items:center;gap:3px}
.dv-ai-cta{width:100%;justify-content:center;margin-top:5px;background:#7c3aed;font-size:.76rem;padding:7px}
.dv-ai-cta:hover{filter:brightness(1.1)}
.dv-err{background:#3b1414;border-top:1px solid #7f1d1d;padding:9px 11px;font-size:.7rem;color:#fca5a5;display:flex;align-items:center;gap:5px;flex-shrink:0}
/* ── Colonne procédures ── */
.apt-procs{display:flex;flex-direction:column;overflow:hidden;background:#f9fafb}
.procs-hdr{background:#fff;border-bottom:1px solid #e5e7eb;padding:10px 14px;flex-shrink:0;display:flex;flex-direction:column;gap:7px}
.procs-hdr-title{display:flex;align-items:center;gap:6px;font-size:.85rem;font-weight:700;color:#111827}
.proc-cnt{font-size:.66rem;font-weight:800;background:#e2e8f0;color:#64748b;padding:2px 6px;border-radius:7px}
.create-row{display:flex;align-items:center;gap:7px}
.create-tools{display:flex;align-items:center;gap:7px;flex-wrap:wrap}
.btn-ia{background:#7c3aed;color:#fff;border:none}
.btn-ia:hover:not(.loading){filter:brightness(1.1)}
.btn-ia.loading{opacity:.7;cursor:wait}
.ia-zone{background:#fdf4ff;border:1.5px solid #e9d5ff;border-radius:7px;padding:10px;display:flex;flex-direction:column;gap:7px}
.ia-zone-ttl{font-size:.7rem;font-weight:700;color:#7c3aed;display:flex;align-items:center;gap:5px}
.ia-ta{font-size:.76rem;resize:vertical;min-height:58px}
.ia-zone-acts{display:flex;gap:7px;flex-wrap:wrap}
.role-info{display:flex;align-items:flex-start;gap:7px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:8px 10px;font-size:.74rem;color:#1e40af;line-height:1.5}
/* Liste procs */
.procs-list{flex:1;overflow-y:auto;padding:8px}
.procs-list::-webkit-scrollbar{width:3px}
.proc-empty{display:flex;flex-direction:column;align-items:center;gap:7px;padding:28px 16px;color:#9ca3af;text-align:center;background:#fff;border:1.5px dashed #e5e7eb;border-radius:8px}
.proc-empty i{font-size:1.5rem;opacity:.2}.proc-empty p{font-size:.76rem}
/* Bloc procédure */
.proc-block{background:#fff;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:8px;overflow:hidden}
.proc-hdr{display:flex;align-items:center;justify-content:space-between;padding:9px 12px;cursor:pointer;transition:background .12s;border-bottom:1px solid transparent}
.proc-hdr:hover{background:#f9fafb}
.proc-block:has(.proc-body) .proc-hdr{border-bottom-color:#e5e7eb}
.proc-hdr-l{display:flex;align-items:center;gap:7px;flex:1;min-width:0;overflow:hidden}
.proc-hdr-r{display:flex;align-items:center;gap:5px;flex-shrink:0}
.proc-chev{width:16px;height:16px;display:flex;align-items:center;justify-content:center;color:#9ca3af;flex-shrink:0}
.proc-chev i{transition:transform .2s;font-size:.8rem}
.proc-chev--open i{transform:rotate(90deg)}
.proc-ico{font-size:.9rem;color:#1565C0;flex-shrink:0}
.proc-inf{min-width:0;overflow:hidden;flex:1}
.proc-ref{font-size:.62rem;font-weight:800;font-family:ui-monospace,monospace;color:#6b7280;display:block}
.proc-nm{font-size:.8rem;font-weight:600;color:#111827;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.proc-bgs{display:flex;align-items:center;gap:4px;flex-shrink:0;flex-wrap:wrap}
.proc-st{font-size:.58rem;font-weight:700;padding:2px 5px;border-radius:5px;white-space:nowrap}
.pcs-en_cours{background:#e3f2fd;color:#1565C0}.pcs-termine{background:#d1fae5;color:#065f46}.pcs-suspendu{background:#fef3c7;color:#d97706}
.mat-bg{font-size:.6rem;font-weight:600;background:#ede9fe;color:#7c3aed;padding:2px 5px;border-radius:5px;display:flex;align-items:center;gap:3px;border:1px solid #ddd6fe}
.assigned-bg{display:inline-flex;align-items:center;gap:3px;font-size:.6rem;font-weight:700;padding:2px 6px;border-radius:7px;border:1.5px solid transparent;cursor:default}
.asgn-nm{font-weight:500;max-width:80px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.unassigned-bg{font-size:.6rem;color:#9ca3af;display:flex;align-items:center;gap:3px;background:#f3f4f6;padding:2px 6px;border-radius:5px}
.proc-asgn-sel{font-size:.68rem;padding:3px 7px;border:1px solid #d1d5db;border-radius:5px;background:#f9fafb;color:#374151;cursor:pointer;max-width:160px}
.proc-asgn-sel:focus{border-color:#6366f1;outline:none}
.ibtn{width:22px;height:22px;display:flex;align-items:center;justify-content:center;background:transparent;border:1px solid transparent;border-radius:4px;cursor:pointer;font-size:.74rem;color:#d1d5db;transition:all .12s;padding:0}
.ibtn-del:hover{color:#dc2626;border-color:#fecaca;background:#fef2f2}
/* Onglets */
.proc-tabs{display:flex;align-items:center;padding:6px 12px 0;background:#f9fafb;border-bottom:1px solid #e5e7eb;flex-wrap:wrap;gap:1px}
.ptab{display:inline-flex;align-items:center;gap:3px;padding:5px 9px;border-radius:5px 5px 0 0;border:1.5px solid transparent;background:none;color:#6b7280;cursor:pointer;font-size:.68rem;font-weight:600;font-family:inherit;transition:all .12s;border-bottom:none}
.ptab:hover{color:#1565C0;background:#eff6ff}
.ptab.active{background:#fff;border-color:#e5e7eb;border-bottom-color:#fff;color:#1565C0}
.ptab-ct{font-size:.56rem;padding:0 3px;border-radius:3px;background:rgba(21,101,192,.1);color:#1565C0}
.ms-auto{margin-left:auto}
.proc-body{overflow:hidden}
.tab-content{padding:12px;overflow-y:auto;max-height:650px}
.tab-content::-webkit-scrollbar{width:3px}
/* Grids */
.fg2{display:grid;grid-template-columns:1fr 1fr;gap:7px}
.fg2 .full{grid-column:span 2}
.radio-grp{display:flex;flex-wrap:wrap;gap:7px}.radio-grp-v{display:flex;flex-direction:column;gap:5px}
.rlbl{display:inline-flex;align-items:center;gap:4px;font-size:.74rem;color:#374151;cursor:pointer}
/* Résumé proc (AS/AJ) */
.proc-resume{background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:9px 11px;margin-bottom:10px}
.pr-row{display:flex;align-items:flex-start;gap:8px;padding:3px 0;border-bottom:1px solid #dbeafe}
.pr-row:last-child{border-bottom:none}
.pr-lbl{font-size:.6rem;font-weight:700;color:#1d4ed8;text-transform:uppercase;width:75px;flex-shrink:0}
.pr-val{font-size:.72rem;color:#1e293b;flex:1}.pr-val.fw{font-weight:600}
/* Docs */
.docs-sec{margin-top:4px}
.docs-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;gap:8px;flex-wrap:wrap}
.docs-ttl{display:flex;align-items:center;gap:5px;font-size:.68rem;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.04em}
.docs-cnt{font-size:.6rem;font-weight:800;background:#e2e8f0;color:#64748b;padding:1px 5px;border-radius:6px}
.docs-upload-btn{cursor:pointer;display:inline-flex;align-items:center;gap:4px}
.docs-grid{display:flex;flex-direction:column;gap:6px;margin-top:4px}
/* Card document */
.doc-card{border:1px solid #e5e7eb;border-radius:7px;overflow:hidden;background:#fff;transition:border-color .15s}
.doc-card--active{border-color:#2563EB;box-shadow:0 0 0 2px rgba(37,99,235,.08)}
.doc-card-hdr{display:flex;align-items:center;gap:8px;padding:7px 10px;cursor:pointer;transition:background .12s;user-select:none}
.doc-card-hdr:hover{background:#f9fafb}
.doc-card--active .doc-card-hdr{background:#eff6ff}
.doc-ico{font-size:1.1rem;color:#6b7280;flex-shrink:0}
.doc-inf{flex:1;min-width:0}
.doc-nm{font-size:.74rem;font-weight:500;color:#111827;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.doc-sz{font-size:.6rem;color:#9ca3af;display:block}
.doc-card-acts{display:flex;align-items:center;gap:3px;flex-shrink:0}
.btn-active{background:#eff6ff!important;color:#2563EB!important;border-color:#bfdbfe!important}
/* Viewer intégré */
.doc-inline-viewer{border-top:1px solid #e5e7eb;background:#f8fafc}
.doc-iframe{width:100%;height:480px;border:none;display:block;background:#fff}
.doc-img-viewer{padding:12px;display:flex;justify-content:center;background:#1a1a2e}
.doc-img-full{max-width:100%;max-height:480px;height:auto;border-radius:5px;box-shadow:0 4px 20px rgba(0,0,0,.4)}
.doc-dl-viewer{display:flex;flex-direction:column;align-items:center;gap:10px;padding:28px 16px;color:#6b7280}
.doc-dl-viewer i{font-size:2rem;color:#9ca3af}
.doc-dl-viewer p{font-size:.78rem;color:#374151;font-weight:500}
/* Tableaux */
.tb-bar{display:flex;align-items:center;gap:5px;padding-bottom:8px;flex-wrap:wrap}
.tb-ct{margin-left:auto;font-size:.64rem;color:#9ca3af}
.tb-empty{display:flex;flex-direction:column;align-items:center;gap:5px;padding:16px;color:#9ca3af;text-align:center;background:#fafafa;border:1.5px dashed #e5e7eb;border-radius:6px}
.tb-empty i{font-size:1rem;opacity:.25}.tb-empty p{font-size:.68rem}
.tb-wrap{overflow:auto;border:1px solid #e5e7eb;border-radius:7px}
.btbl{width:100%;border-collapse:collapse;font-size:.68rem;table-layout:fixed}
.btbl thead th{background:#1e293b;color:rgba(255,255,255,.88);font-size:.56rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;padding:5px 4px;border:none;white-space:nowrap;position:sticky;top:0;z-index:2}
.btbl tbody td{padding:2px 3px;border:1px solid #f3f4f6;vertical-align:middle;overflow:hidden}
.th-n{width:26px;text-align:center}.td-n{text-align:center;font-weight:700;color:#9ca3af;font-size:.6rem}.td-c{text-align:center}
.row-sec td{padding:0!important}
.sec-hd{display:flex;align-items:center;gap:4px;padding:3px 7px;background:#1e293b;color:#fff;font-size:.62rem;font-weight:700}
.c-sec{background:transparent;color:#fff;border:none;flex:1;font-weight:700;font-size:.62rem;outline:none;font-family:inherit}
.c-sec::placeholder{color:rgba(255,255,255,.4)}
.row-b{background:#fff;transition:background .1s}.row-b:hover td{background:#f8fbff}
.row-c td{border-left:3px solid #059669!important;background:#f0fdf4}
.row-nc td{border-left:3px solid #dc2626!important;background:#fef2f2}
.row-pp td{border-left:3px solid #d97706!important;background:#fffbeb}
.c-inp{width:100%;border:1px solid transparent;border-radius:3px;padding:2px 3px;font-size:.64rem;color:#111827;font-family:inherit;outline:none;background:transparent;height:21px}
.c-inp:hover{border-color:#e5e7eb;background:#fff}.c-inp:focus{border-color:#2563EB;background:#fff}
.c-x{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.c-sel{width:100%;border:1px solid #e5e7eb;border-radius:3px;padding:1px 2px;font-size:.6rem;font-family:inherit;outline:none;background:#fff;cursor:pointer;height:21px}
.c-sel:focus{border-color:#2563EB}
.s-fort{background:#d1fae5!important;color:#065f46!important;font-weight:700}
.s-faib{background:#fef2f2!important;color:#991b1b!important;font-weight:700}
.s-oui{background:#d1fae5!important;color:#065f46!important;font-weight:700}
.s-non{background:#fef2f2!important;color:#991b1b!important;font-weight:700}
.s-c{background:#d1fae5!important;color:#065f46!important;font-weight:800}
.s-nc{background:#fef2f2!important;color:#991b1b!important;font-weight:800}
.s-pp{background:#fef3c7!important;color:#92400e!important;font-weight:800}
.b-nat,.b-ctrl,.b-res,.b-sc{font-size:.6rem;font-weight:700;padding:1px 3px;border-radius:3px;display:inline-block}
.nat-f{background:#d1fae5;color:#065f46}.nat-b{background:#fef2f2;color:#991b1b}
.ctrl-o{background:#d1fae5;color:#065f46}.ctrl-n{background:#fef2f2;color:#991b1b}
.res-c{background:#d1fae5;color:#065f46}.res-nc{background:#fef2f2;color:#991b1b}.res-pp{background:#fef3c7;color:#92400e}
.sc-a_collecter{background:#e3f2fd;color:#1565C0}.sc-obtenu{background:#d1fae5;color:#065f46}.sc-na{background:#f1f5f9;color:#6b7280}
.ro-t{font-size:.64rem;color:#374151;display:block}
.ro-e{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%}
.btn-del{background:none;border:none;cursor:pointer;color:#d1d5db;font-size:.72rem;padding:1px 2px;border-radius:3px;line-height:1}
.btn-del:hover{color:#ef4444;background:#fee2e2}
/* Sec titre */
.sec-ttl{display:flex;align-items:center;gap:5px;font-size:.7rem;font-weight:700;color:#1e293b;text-transform:uppercase;letter-spacing:.05em;padding:5px 0;border-bottom:2px solid #e5e7eb;margin-bottom:7px}
/* Appréciation */
.appre-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:7px}
@media(min-width:900px){.appre-grid{grid-template-columns:repeat(4,1fr)}}
.appre-col{display:flex;flex-direction:column;gap:4px;padding:7px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px}
.appre-lbl{font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#1565C0;padding-bottom:3px;border-bottom:1px solid #e5e7eb}
.b-ap{font-size:.64rem;font-weight:700;padding:2px 6px;border-radius:5px;display:inline-block}
.ap-c{background:#d1fae5;color:#065f46}.ap-p{background:#fef3c7;color:#92400e}.ap-n{background:#fef2f2;color:#991b1b}.ap-cr{background:#991b1b;color:#fff}.ap-b{background:#e3f2fd;color:#1565C0}
/* Diagramme */
.diag-err{background:#fef2f2;border:1px solid #fecaca;border-radius:6px;padding:7px 10px;font-size:.72rem;color:#dc2626;display:flex;align-items:center;gap:5px;margin-bottom:8px}
.diag-empty{display:flex;flex-direction:column;align-items:center;gap:7px;padding:28px 16px;text-align:center;background:#fafafa;border:2px dashed #e5e7eb;border-radius:8px}
.diag-empty-ico{width:48px;height:48px;border-radius:50%;background:#ede9fe;display:flex;align-items:center;justify-content:center}
.diag-empty-ico i{font-size:1.3rem;color:#7c3aed}
.diag-empty-ttl{font-size:.82rem;font-weight:700;color:#374151}
.diag-empty-sub{font-size:.72rem;color:#6b7280;max-width:300px;line-height:1.5}
.diag-result{display:flex;flex-direction:column;gap:9px}
.diag-synth{background:#f0fdf4;border:1px solid #a7f3d0;border-radius:7px;padding:9px 11px}
.ds-row{display:flex;align-items:flex-start;gap:8px;padding:3px 0;border-bottom:1px solid #d1fae5}
.ds-row:last-child{border-bottom:none}
.ds-l{font-size:.6rem;font-weight:700;color:#059669;text-transform:uppercase;width:72px;flex-shrink:0}
.ds-v{font-size:.72rem;color:#1e293b;flex:1}
.diag-xml-box{border:1px solid #e5e7eb;border-radius:7px;overflow:hidden}
.diag-xml-bar{display:flex;align-items:center;justify-content:space-between;padding:5px 9px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-size:.66rem;color:#6b7280;font-weight:600}
.diag-xml-bar span{display:flex;align-items:center;gap:4px}
.diag-xml{font-family:ui-monospace,monospace;font-size:.6rem;color:#1e293b;line-height:1.6;overflow:auto;max-height:260px;white-space:pre;padding:8px;margin:0;background:#f8fafc}
/* Éditeur XML BPMN */
.diag-edit-box{border:1.5px solid #7c3aed;border-radius:7px;overflow:hidden}
.diag-edit-bar{display:flex;align-items:center;gap:7px;padding:6px 10px;background:#fdf4ff;border-bottom:1px solid #e9d5ff;font-size:.7rem;color:#7c3aed;font-weight:600;flex-wrap:wrap}
.diag-edit-hint{font-size:.62rem;color:#9ca3af;font-weight:400;margin-left:auto}
.diag-xml-edit{width:100%;font-family:ui-monospace,monospace;font-size:.65rem;color:#1e293b;background:#1e1e2e;color:#a5f3fc;padding:10px 12px;border:none;outline:none;resize:vertical;min-height:320px;line-height:1.6;display:block}
/* Buttons */
.btn{display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:6px;font-size:.78rem;font-weight:600;border:none;cursor:pointer;font-family:inherit;transition:all .15s;white-space:nowrap}
.btn-save{background:#1e293b;color:#fff}.btn-save:hover:not(:disabled){background:#0f172a}
.btn-ghost{background:#fff;color:#374151;border:1px solid #e5e7eb}.btn-ghost:hover:not(:disabled){background:#f9fafb}
.btn-sub{background:#eff6ff;color:#2563EB;border:1px solid #bfdbfe}.btn-sub:hover:not(:disabled){background:#dbeafe}
.btn-ok{background:#ecfdf5;color:#059669;border:1px solid #a7f3d0}.btn-ok:hover:not(:disabled){background:#d1fae5}
.btn-rej{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}.btn-rej:hover:not(:disabled){background:#fee2e2}
.btn-ai{background:linear-gradient(135deg,#6d28d9,#7c3aed);color:#fff}.btn-ai:hover:not(:disabled){filter:brightness(1.1)}
.btn-import{background:#f0f9ff;color:#0369a1;border:1px solid #bae6fd;cursor:pointer}.btn-import:hover{background:#e0f2fe}
.btn-sm{padding:4px 9px;font-size:.74rem}
.btn-xs{padding:3px 7px;font-size:.68rem}
.btn:disabled{opacity:.45;cursor:not-allowed}
.hidden{display:none}
/* Footer */
.apt-footer{display:flex;align-items:center;justify-content:space-between;padding:9px 18px;background:#fff;border-top:1px solid #e5e7eb;flex-wrap:wrap;gap:6px;flex-shrink:0}
.apt-footer>div{display:flex;gap:6px;flex-wrap:wrap}
.footer-mid{flex:1;display:flex;justify-content:center}
.saved-code{font-size:.7rem;color:#059669;display:flex;align-items:center;gap:3px;font-weight:600}
/* Chat btn */
.btn-chat{display:inline-flex;align-items:center;gap:5px;padding:5px 10px;background:#fff;border:1px solid #e5e7eb;border-radius:7px;color:#6b7280;font-size:.74rem;font-weight:500;cursor:pointer;transition:all .15s;position:relative}
.btn-chat:hover,.btn-chat.unread{border-color:#2563EB;color:#2563EB;background:#eff6ff}
.chat-badge{position:absolute;top:-5px;right:-5px;background:#ef4444;color:#fff;font-size:.56rem;font-weight:700;min-width:14px;height:14px;border-radius:7px;display:flex;align-items:center;justify-content:center;padding:0 2px;border:2px solid #fff}
/* Toast */
.toast{position:fixed;top:16px;right:16px;z-index:9999;display:flex;align-items:center;gap:7px;padding:9px 14px;border-radius:8px;font-size:.78rem;font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.12);border:1px solid transparent}
.toast--success{background:#ecfdf5;color:#059669;border-color:#a7f3d0}
.toast--error{background:#fef2f2;color:#dc2626;border-color:#fecaca}
.toast-t-enter-active,.toast-t-leave-active{transition:all .25s}
.toast-t-enter-from,.toast-t-leave-to{opacity:0;transform:translateX(10px)}
/* ── Entretien ── */
.c-ta{width:100%;border:1px solid transparent;border-radius:3px;padding:3px 4px;font-size:.66rem;color:#111827;font-family:inherit;outline:none;background:transparent;resize:vertical;min-height:40px;line-height:1.4}
.c-ta:hover{border-color:#e5e7eb;background:#fff}.c-ta:focus{border-color:#2563EB;background:#fff}
.row-answered td{background:#f0fdf4!important}
.reponse-txt{font-size:.66rem;color:#1e293b;white-space:pre-wrap;line-height:1.5}

/* ── Collecte doc reçu ── */
.row-recu td{background:#f0fdf4!important}
.chk-wrap{display:flex;align-items:center;justify-content:center;cursor:pointer;user-select:none}
.chk-disabled{cursor:default;opacity:.7}
.chk{display:none}
.chk-box{width:18px;height:18px;border:2px solid #d1d5db;border-radius:4px;display:flex;align-items:center;justify-content:center;background:#fff;transition:all .15s;font-size:.65rem}
.chk-box--on{background:#059669;border-color:#059669;color:#fff}
.ref-doc{font-size:.64rem;color:#059669;font-weight:600}
.coll-recu-ct{display:inline-flex;align-items:center;gap:3px;font-size:.66rem;font-weight:600;color:#059669;background:#ecfdf5;padding:2px 7px;border-radius:6px;border:1px solid #a7f3d0}

/* ── Matrice résultat boutons (AS/AJ) ── */
.res-btns{display:flex;gap:2px;justify-content:center}
.res-btn{width:22px;height:18px;border-radius:3px;font-size:.6rem;font-weight:800;border:1.5px solid;cursor:pointer;transition:all .12s;background:transparent;font-family:inherit;padding:0}
.res-btn-c{border-color:#a7f3d0;color:#059669}.res-btn-c.active{background:#059669;color:#fff;border-color:#059669}
.res-btn-nc{border-color:#fecaca;color:#dc2626}.res-btn-nc.active{background:#dc2626;color:#fff;border-color:#dc2626}
.res-btn-pp{border-color:#fde68a;color:#d97706}.res-btn-pp.active{background:#d97706;color:#fff;border-color:#d97706}

/* ── Nature badge large (AS/AJ) ── */
.b-nat-lg{font-size:.62rem;font-weight:700;padding:2px 5px;border-radius:4px;white-space:nowrap;display:inline-block}
.nat-f{background:#d1fae5;color:#065f46}.nat-b{background:#fef2f2;color:#991b1b}.nat-nd{background:#f3f4f6;color:#9ca3af}

/* ── Forces / Faiblesses ── */
.ff-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:4px}
.ff-col{border-radius:7px;overflow:hidden;border:1.5px solid}
.ff-col--f{border-color:#a7f3d0;background:#f0fdf4}
.ff-col--w{border-color:#fecaca;background:#fef2f2}
.ff-hdr{display:flex;align-items:center;gap:6px;padding:7px 10px;font-size:.7rem;font-weight:700;border-bottom:1px solid}
.ff-col--f .ff-hdr{color:#059669;border-bottom-color:#a7f3d0;background:#dcfce7}
.ff-col--w .ff-hdr{color:#dc2626;border-bottom-color:#fecaca;background:#fee2e2}
.ff-cnt{font-size:.6rem;font-weight:800;padding:1px 5px;border-radius:6px;background:rgba(0,0,0,.08)}
.ff-add-btn{margin-left:auto;background:transparent;border:1px solid currentColor;padding:2px 6px;opacity:.7}
.ff-add-btn:hover{opacity:1}
.ff-empty{padding:10px;font-size:.68rem;color:#9ca3af;text-align:center;font-style:italic}
.ff-list{padding:7px;display:flex;flex-direction:column;gap:5px}
.ff-item{display:flex;align-items:flex-start;gap:5px;padding:5px 7px;background:rgba(255,255,255,.7);border-radius:5px;border:1px solid rgba(0,0,0,.06)}
.ff-dot{font-size:.76rem;margin-top:2px;flex-shrink:0}
.ff-col--f .ff-dot{color:#059669}.ff-col--w .ff-dot{color:#dc2626}
.ff-inp{flex:1;border:1px solid transparent;border-radius:3px;padding:2px 4px;font-size:.68rem;color:#111827;font-family:inherit;outline:none;background:transparent;resize:vertical;min-height:36px;line-height:1.4}
.ff-inp:hover{border-color:#e5e7eb;background:#fff}.ff-inp:focus{border-color:#2563EB;background:#fff}
.ff-txt{flex:1;font-size:.68rem;color:#374151;line-height:1.5;white-space:pre-wrap}

/* ── Appréciation avec sélection visible ── */
.rlbl-ro{cursor:default;opacity:.85}
.b-ap--sel{outline:2px solid currentColor;outline-offset:1px;font-weight:800}

/* ── Matrice score ── */
.mat-score{display:flex;align-items:center;gap:6px;font-size:.68rem}
.score-c{display:flex;align-items:center;gap:3px;color:#059669;font-weight:700;background:#ecfdf5;padding:2px 6px;border-radius:5px}
.score-nc{display:flex;align-items:center;gap:3px;color:#dc2626;font-weight:700;background:#fef2f2;padding:2px 6px;border-radius:5px}
.score-pp{display:flex;align-items:center;gap:3px;color:#d97706;font-weight:700;background:#fffbeb;padding:2px 6px;border-radius:5px}

/* ── Analyse actions ── */
.analyse-actions{display:flex;gap:6px;padding-top:8px;border-top:1px solid #f3f4f6;margin-top:8px;flex-wrap:wrap}
/* Spin */
.spin-s{width:10px;height:10px;border-radius:50%;border:2px solid currentColor;border-top-color:transparent;animation:spin .6s linear infinite;display:inline-block;flex-shrink:0}
::-webkit-scrollbar{width:4px;height:4px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:#e2e8f0;border-radius:2px}
@keyframes spin{to{transform:rotate(360deg)}}
.spin{animation:spin .6s linear infinite;display:inline-block}
</style>