<template>
  <VerticalLayoutAudit>
    <div class="amq-shell">

      <!-- ═══ HEADER ═══ -->
      <header class="amq-header">
        <div class="amq-hrow">
          <a :href="props.backUrl" class="amq-back"><i class="ti ti-arrow-left"></i></a>
          <div class="amq-hinfo">
            <div class="amq-chips">
              <code class="amq-code">{{ mission?.code ?? '—' }}</code>
              <span class="amq-chip" :class="`chip-${form.validation_status||'draft'}`">
                <i :class="vstIcon(form.validation_status||'draft')"></i>{{ vstLbl(form.validation_status||'draft') }}
              </span>
              <span class="amq-chip chip-type">AMQ</span>
              <span v-if="props.auditorRole" class="amq-chip" :class="`chip-role-${props.auditorRole}`">{{ props.auditorRole }}</span>
            </div>
            <h1 class="amq-title">Analyse des Marchés — QEM</h1>
            <div class="amq-meta">
              <span v-if="mission?.title"><i class="ti ti-clipboard"></i>{{ mission.title }}</span>
              <span v-if="mission?.entity_name"><i class="ti ti-building"></i>{{ mission.entity_name }}</span>
              <span><i class="ti ti-shopping-cart"></i>{{ marches.length }} marché(s)</span>
            </div>
          </div>
        </div>
        <div v-if="form.validation_status==='validated'" class="amq-banner banner-lock">
          <i class="ti ti-lock"></i> Formulaire <strong>validé définitivement</strong> — lecture seule
        </div>
        <div v-else-if="form.validation_status==='in_review'" class="amq-banner banner-review">
          <i class="ti ti-clock"></i> Soumis pour validation<span v-if="canManage"> · Vous pouvez valider ou rejeter.</span>
        </div>
        <div v-else-if="form.validation_status==='draft'&&form.validation_note" class="amq-banner banner-reject">
          <i class="ti ti-circle-x"></i> Rejeté — <em>{{ form.validation_note }}</em>
        </div>
      </header>

      <!-- ═══ BODY ═══ -->
      <div class="amq-body">
        <div class="amq-layout">

          <!-- ─── SIDEBAR ─── -->
          <aside class="amq-sidebar">
            <section class="card">
              <div class="card-lbl"><i class="ti ti-briefcase"></i> Mission</div>
              <div class="card-body">
                <div class="fg"><span class="flbl">Code</span><input class="inp inp-ro" :value="mission?.code" readonly/></div>
                <div class="fg"><span class="flbl">Entité</span><input class="inp inp-ro" :value="mission?.entity_name||'—'" readonly/></div>
                <div class="fg"><span class="flbl">Intitulé</span><input class="inp inp-ro" :value="mission?.title" readonly/></div>
              </div>
            </section>

            <section class="card">
              <div class="card-lbl"><i class="ti ti-table"></i> En-tête QEM</div>
              <div class="card-body">
                <div class="fg"><span class="flbl">Intitulé QEM</span>
                  <input class="inp" v-model="form.intitule_qem" :disabled="isLocked" placeholder="Titre du questionnaire…"/>
                </div>
                <div class="fg"><span class="flbl">Code</span><input class="inp inp-ro" :value="form.code||'AMQ-AUTO'" readonly/></div>
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
              <div class="card-lbl"><i class="ti ti-users"></i> Auditeurs <span class="card-cnt">{{ props.phaseAuditeurs?.length??0 }}</span></div>
              <div class="card-body p6">
                <div v-for="aud in (props.phaseAuditeurs as any[])" :key="aud.id" class="aud-row">
                  <div class="aud-av" :class="`av-${aud.role_code}`">{{ aud.initials }}</div>
                  <div class="aud-inf"><span class="aud-nm">{{ aud.full_name }}</span><span class="aud-cd">{{ aud.audit_code }}</span></div>
                  <span class="amq-chip" :class="`chip-role-${aud.role_code}`">{{ aud.role_code }}</span>
                </div>
              </div>
            </section>

            <section class="card">
              <div class="card-lbl"><i class="ti ti-list"></i> AMQ enregistrés</div>
              <div class="card-body p0">
                <table class="stbl">
                  <thead><tr><th>Code</th><th>Intitulé</th><th>Statut</th></tr></thead>
                  <tbody>
                    <tr v-if="!props.amqList?.length"><td colspan="3" class="td-empty">Aucune analyse</td></tr>
                    <tr v-for="a in (props.amqList as any[])" :key="a.id" class="stbl-row" @click="loadAmq(a)">
                      <td class="td-code">{{ a.code }}</td>
                      <td class="td-ov">{{ a.intitule_qem||'—' }}</td>
                      <td><span class="amq-chip" :class="`chip-${a.validation_status||'draft'}`">{{ vstLbl(a.validation_status||'draft') }}</span></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </section>

            <!-- Légende statuts étapes -->
            <section class="card">
              <div class="card-lbl"><i class="ti ti-info-circle"></i> Légende étapes</div>
              <div class="card-body">
                <div class="lg-row"><span class="st-oui">OUI</span><div><b>Conforme / Réalisé</b></div></div>
                <div class="lg-row"><span class="st-non">NON</span><div><b>Non conforme / Non réalisé</b></div></div>
                <div class="lg-row"><span class="st-so">S/O</span><div><b>Sans objet</b></div></div>
              </div>
            </section>
          </aside>

          <!-- ─── ZONE MARCHÉS ─── -->
          <div class="amq-marches">

            <!-- Toolbar création -->
            <div class="marches-hdr">
              <div class="marches-hdr-title">
                <i class="ti ti-shopping-cart"></i> Marchés
                <span class="marche-cnt">{{ marches.length }}</span>
              </div>
              <div v-if="!isLocked" class="create-tools">
                <div class="create-row">
                  <input v-model="newMarcheRef"   class="inp inp-sm" style="width:90px" placeholder="Référence"/>
                  <input v-model="newMarcheTitle" class="inp inp-sm" style="flex:1" placeholder="Intitulé du marché…" @keydown.enter="addMarche()"/>
                  <button class="btn btn-save btn-sm" :disabled="!newMarcheTitle.trim()" @click="addMarche()">
                    <i class="ti ti-plus"></i> Ajouter
                  </button>
                </div>
                <div class="tool-row">
                  <label class="btn btn-import btn-sm">
                    <i class="ti ti-cloud-upload"></i> Importer Excel
                    <input type="file" accept=".xlsx,.xls" class="hidden" @change="onExcelImport"/>
                  </label>
                  <button class="btn btn-ai btn-sm" @click="showIaZone=!showIaZone">
                    <i class="ti ti-brain"></i> Générer par IA
                  </button>
                </div>
                <div v-if="showIaZone" class="ia-zone">
                  <div class="ia-zone-ttl"><i class="ti ti-sparkles"></i> Génération IA — Nouveau marché</div>
                  <textarea v-model="iaPrompt" class="inp ia-ta" rows="2"
                    placeholder="Ex : marché de travaux de réhabilitation du siège, marché de fourniture de carburant…"/>
                  <div class="ia-zone-acts">
                    <button class="btn btn-ai btn-sm" @click="suggestAnalyseComplete" :disabled="iaLoading||!iaPrompt.trim()">
                      <i class="ti" :class="iaLoading?'ti-loader-2 spin':'ti-sparkles'"></i>{{ iaLoading?'En cours…':'Analyser & Générer' }}
                    </button>
                    <button class="btn btn-ghost btn-sm" @click="showIaZone=false">Annuler</button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Vide -->
            <div v-if="!marches.length" class="marche-empty">
              <i class="ti ti-shopping-cart"></i>
              <p>Aucun marché — créez-en un manuellement, importez via Excel ou générez via IA.</p>
            </div>

            <!-- Liste marchés -->
            <div class="marches-list">
              <div v-for="marche in marches" :key="marche._k" class="marche-block">

                <!-- En-tête marché -->
                <div class="marche-hdr" @click="toggleMarche(marche._k)">
                  <div class="marche-hdr-l">
                    <span class="marche-chev" :class="{'marche-chev--open':expandedMarches.has(marche._k)}">
                      <i class="ti ti-chevron-right"></i>
                    </span>
                    <span class="marche-ref-badge">{{ marche.reference||'—' }}</span>
                    <div class="marche-inf">
                      <span class="marche-nm">{{ marche.intitule||'Marché sans titre' }}</span>
                      <span v-if="marche.objet" class="marche-sub">{{ marche.objet }}</span>
                    </div>
                    <!-- Score étapes -->
                    <div class="marche-scores">
                      <span v-if="countEtape(marche,'oui')" class="s-oui-bg"><i class="ti ti-check"></i>{{ countEtape(marche,'oui') }}</span>
                      <span v-if="countEtape(marche,'non')" class="s-non-bg"><i class="ti ti-x"></i>{{ countEtape(marche,'non') }}</span>
                      <span v-if="countEtape(marche,'sans_objet')" class="s-so-bg">S/O {{ countEtape(marche,'sans_objet') }}</span>
                    </div>
                  </div>
                  <div class="marche-hdr-r" @click.stop>
                    <button v-if="!isLocked" class="ibtn ibtn-del" @click.stop="removeMarche(marche._k)"><i class="ti ti-x"></i></button>
                  </div>
                </div>

                <!-- Corps marché -->
                <div v-if="expandedMarches.has(marche._k)" class="marche-body">

                  <!-- Onglets -->
                  <div class="marche-tabs">
                    <button v-for="tab in TABS" :key="tab.key"
                            :class="['mtab',{active:activeTab(marche._k)===tab.key}]"
                            @click="marcheTab[marche._k]=tab.key">
                      <i class="ti" :class="tab.icon"></i>{{ tab.label }}
                      <span v-if="tab.key==='ETAPES'" class="mtab-ct">{{ marche.etapes?.length||0 }}</span>
                      <span v-if="tab.key==='OBJECTIFS'" class="mtab-ct">{{ marche.objectifs?.length||0 }}</span>
                    </button>
                  </div>

                  <!-- ════ IDENTIFICATION ════ -->
                  <div v-show="activeTab(marche._k)==='ID'" class="tab-content">
                    <div class="id-grid">
                      <div class="fg"><span class="flbl">Référence</span>
                        <input class="inp" v-model="marche.reference" :disabled="isLocked" placeholder="REF-2024-001"/>
                      </div>
                      <div class="fg"><span class="flbl">Date marché</span>
                        <input type="date" class="inp" v-model="marche.date_marche" :disabled="isLocked"/>
                      </div>
                      <div class="fg full"><span class="flbl">Intitulé *</span>
                        <input class="inp" v-model="marche.intitule" :disabled="isLocked"/>
                      </div>
                      <div class="fg full"><span class="flbl">Objet du marché</span>
                        <textarea class="inp inp-ta" v-model="marche.objet" :disabled="isLocked" rows="2"></textarea>
                      </div>
                      <div class="fg"><span class="flbl">Montant (FCFA)</span>
                        <input class="inp" v-model="marche.montant" :disabled="isLocked" placeholder="0"/>
                      </div>
                      <div class="fg"><span class="flbl">Attributaire / Titulaire</span>
                        <input class="inp" v-model="marche.attributaire" :disabled="isLocked"/>
                      </div>
                      <div class="fg full"><span class="flbl">Commentaire général</span>
                        <textarea class="inp inp-ta" v-model="marche.commentaire" :disabled="isLocked" rows="2"></textarea>
                      </div>
                    </div>
                  </div>

                  <!-- ════ ÉTAPES PRINCIPALES ════ -->
                  <div v-show="activeTab(marche._k)==='ETAPES'" class="tab-content">
                    <div class="tb-bar">
                      <button v-if="!isLocked" class="btn btn-xs btn-ai"
                              @click="suggestEtapesIA(marche)" :disabled="iaLoading">
                        <i class="ti" :class="iaLoading?'ti-loader-2 spin':'ti-brain'"></i> Étapes IA
                      </button>
                      <button v-if="!isLocked" class="btn btn-xs btn-save" @click="addEtape(marche)">
                        <i class="ti ti-plus"></i> Étape
                      </button>
                      <div class="tb-scores ms-auto">
                        <span class="s-oui-sm">✓ {{ countEtape(marche,'oui') }} Oui</span>
                        <span class="s-non-sm">✗ {{ countEtape(marche,'non') }} Non</span>
                        <span class="s-so-sm">S/O {{ countEtape(marche,'sans_objet') }}</span>
                      </div>
                    </div>

                    <div v-if="!(marche.etapes||[]).length" class="tb-empty">
                      <i class="ti ti-list-check"></i><p>Aucune étape — ajoutez manuellement ou utilisez l'IA</p>
                    </div>

                    <div v-else class="tb-wrap">
                      <table class="btbl etapes-tbl">
                        <thead>
                          <tr>
                            <th style="width:30px;text-align:center">N°</th>
                            <th>Référence</th>
                            <th>Étapes principales</th>
                            <th style="width:120px;text-align:center">Statut</th>
                            <th style="width:180px">Observation</th>
                            <th style="width:24px"></th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(etape,ei) in (marche.etapes||[])" :key="ei"
                              class="etape-row"
                              :class="etape.statut==='oui'?'row-oui':etape.statut==='non'?'row-non':etape.statut==='sans_objet'?'row-so':''">
                            <td class="td-n">{{ ei+1 }}</td>
                            <td>
                              <input v-if="!isLocked" class="c-inp" v-model="etape.ref_etape" placeholder="Réf."/>
                              <span v-else class="ro-t">{{ etape.ref_etape||'—' }}</span>
                            </td>
                            <td>
                              <input v-if="!isLocked" class="c-inp c-x" v-model="etape.libelle" placeholder="Libellé de l'étape…"/>
                              <span v-else class="ro-t">{{ etape.libelle }}</span>
                            </td>
                            <!-- Statut : boutons OUI / NON / S/O -->
                            <td class="td-c">
                              <div v-if="!isLocked" class="st-btns">
                                <button :class="['st-btn','st-oui-btn',{active:etape.statut==='oui'}]"
                                        @click="etape.statut=etape.statut==='oui'?null:'oui'">OUI</button>
                                <button :class="['st-btn','st-non-btn',{active:etape.statut==='non'}]"
                                        @click="etape.statut=etape.statut==='non'?null:'non'">NON</button>
                                <button :class="['st-btn','st-so-btn',{active:etape.statut==='sans_objet'}]"
                                        @click="etape.statut=etape.statut==='sans_objet'?null:'sans_objet'">S/O</button>
                              </div>
                              <span v-else :class="['b-st','st-'+etape.statut]">{{ stLbl(etape.statut) }}</span>
                            </td>
                            <td>
                              <input v-if="!isLocked" class="c-inp c-x" v-model="etape.observation" placeholder="Obs…"/>
                              <span v-else class="ro-t ro-e">{{ etape.observation||'—' }}</span>
                            </td>
                            <td>
                              <button v-if="!isLocked" class="btn-del" @click="marche.etapes.splice(ei,1)">×</button>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <!-- ════ OBJECTIFS D'AUDIT ════ -->
                  <div v-show="activeTab(marche._k)==='OBJECTIFS'" class="tab-content">
                    <div class="tb-bar">
                      <button v-if="!isLocked" class="btn btn-xs btn-ai"
                              @click="suggestObjectifsIA(marche)" :disabled="iaLoading">
                        <i class="ti" :class="iaLoading?'ti-loader-2 spin':'ti-brain'"></i> IA
                      </button>
                      <button v-if="!isLocked" class="btn btn-xs btn-save" @click="addObjectif(marche)">
                        <i class="ti ti-plus"></i> Objectif
                      </button>
                      <span class="tb-ct">{{ (marche.objectifs||[]).length }} objectif(s)</span>
                    </div>

                    <div v-if="!(marche.objectifs||[]).length" class="tb-empty">
                      <i class="ti ti-target"></i><p>Aucun objectif d'audit — ajoutez ou générez via IA</p>
                    </div>

                    <div v-else class="obj-list">
                      <div v-for="(obj,oi) in (marche.objectifs||[])" :key="oi" class="obj-item">
                        <span class="obj-num">{{ oi+1 }}</span>
                        <div class="obj-body">
                          <textarea v-if="!isLocked" class="inp inp-ta obj-ta" v-model="obj.libelle"
                                    rows="2" placeholder="Objectif d'audit…"></textarea>
                          <span v-else class="obj-txt">{{ obj.libelle }}</span>
                          <div class="obj-foot">
                            <div class="obj-atteint">
                              <span class="flbl">Atteint ?</span>
                              <div v-if="!isLocked" class="st-btns">
                                <button :class="['st-btn','st-oui-btn',{active:obj.atteint==='oui'}]"   @click="obj.atteint=obj.atteint==='oui'?null:'oui'">OUI</button>
                                <button :class="['st-btn','st-non-btn',{active:obj.atteint==='non'}]"   @click="obj.atteint=obj.atteint==='non'?null:'non'">NON</button>
                                <button :class="['st-btn','st-so-btn',{active:obj.atteint==='partiel'}]" @click="obj.atteint=obj.atteint==='partiel'?null:'partiel'">PARTIEL</button>
                              </div>
                              <span v-else :class="['b-st','st-'+obj.atteint]">{{ stLbl(obj.atteint) }}</span>
                            </div>
                            <input v-if="!isLocked" class="inp" v-model="obj.commentaire" placeholder="Commentaire sur l'atteinte…" style="flex:1;font-size:.7rem"/>
                            <span v-else class="ro-t">{{ obj.commentaire||'—' }}</span>
                          </div>
                        </div>
                        <button v-if="!isLocked" class="btn-del obj-del" @click="marche.objectifs.splice(oi,1)">×</button>
                      </div>
                    </div>
                  </div>

                  <!-- ════ FORCES & FAIBLESSES ════ -->
                  <div v-show="activeTab(marche._k)==='FF'" class="tab-content">
                    <div class="tb-bar">
                      <button v-if="!isLocked" class="btn btn-sm btn-ai"
                              @click="suggestFFIA(marche)" :disabled="iaLoading">
                        <i class="ti" :class="iaLoading?'ti-loader-2 spin':'ti-sparkles'"></i>
                        {{ iaLoading?'Génération…':'Forces / Faiblesses par IA' }}
                      </button>
                      <span class="tb-ct" style="color:#6b7280;font-size:.68rem">
                        L'IA analyse les étapes du marché pour suggérer les forces et faiblesses
                      </span>
                    </div>

                    <div class="ff-grid">
                      <!-- Forces -->
                      <div class="ff-col ff-col--f">
                        <div class="ff-hdr">
                          <i class="ti ti-arrow-up-circle"></i> Forces
                          <span class="ff-cnt">{{ (marche.forces||[]).length }}</span>
                          <button v-if="!isLocked" class="btn btn-xs ff-add-btn" @click="addForce(marche)">
                            <i class="ti ti-plus"></i>
                          </button>
                        </div>
                        <div v-if="!(marche.forces||[]).length" class="ff-empty">
                          Aucune force — ajoutez manuellement ou via IA
                        </div>
                        <div v-else class="ff-list">
                          <div v-for="(f,fi) in (marche.forces||[])" :key="fi" class="ff-item ff-item--f">
                            <i class="ti ti-arrow-up ff-dot"></i>
                            <textarea v-if="!isLocked" class="ff-inp" v-model="marche.forces[fi]" rows="2" placeholder="Décrire la force…"></textarea>
                            <span v-else class="ff-txt">{{ f }}</span>
                            <button v-if="!isLocked" class="btn-del" @click="marche.forces.splice(fi,1)">×</button>
                          </div>
                        </div>
                      </div>
                      <!-- Faiblesses -->
                      <div class="ff-col ff-col--w">
                        <div class="ff-hdr">
                          <i class="ti ti-arrow-down-circle"></i> Faiblesses
                          <span class="ff-cnt">{{ (marche.faiblesses||[]).length }}</span>
                          <button v-if="!isLocked" class="btn btn-xs ff-add-btn" @click="addFaiblesse(marche)">
                            <i class="ti ti-plus"></i>
                          </button>
                        </div>
                        <div v-if="!(marche.faiblesses||[]).length" class="ff-empty">
                          Aucune faiblesse — ajoutez manuellement ou via IA
                        </div>
                        <div v-else class="ff-list">
                          <div v-for="(w,wi) in (marche.faiblesses||[])" :key="wi" class="ff-item ff-item--w">
                            <i class="ti ti-arrow-down ff-dot"></i>
                            <textarea v-if="!isLocked" class="ff-inp" v-model="marche.faiblesses[wi]" rows="2" placeholder="Décrire la faiblesse…"></textarea>
                            <span v-else class="ff-txt">{{ w }}</span>
                            <button v-if="!isLocked" class="btn-del" @click="marche.faiblesses.splice(wi,1)">×</button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- ════ DOCUMENTS ════ -->
                  <div v-show="activeTab(marche._k)==='DOCS'" class="tab-content">
                    <div class="docs-hdr">
                      <div class="docs-ttl"><i class="ti ti-paperclip"></i> Documents joints <span class="docs-cnt">{{ (marche.attached_docs||[]).length }}</span></div>
                      <label v-if="!isLocked&&form.id" class="btn btn-xs btn-import">
                        <span v-if="marche.uploading" class="spin-s"></span>
                        <i v-else class="ti ti-cloud-upload"></i>
                        {{ marche.uploading?'Upload…':'Joindre un fichier' }}
                        <input v-if="!marche.uploading" type="file" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.xlsx,.xls,.txt" class="hidden" @change="e=>uploadDoc(e,marche)"/>
                      </label>
                    </div>
                    <div v-if="!(marche.attached_docs||[]).length" class="tb-empty">
                      <i class="ti ti-file-off"></i><p>Aucun document joint</p>
                    </div>
                    <div v-else class="docs-grid">
                      <div v-for="(doc,di) in (marche.attached_docs||[])" :key="di" class="doc-card" :class="{'doc-card--active':marche.activeDocIdx===di}">
                        <div class="doc-card-hdr" @click="toggleDocViewer(marche,di)">
                          <i :class="['ti doc-ico', docIcon(doc.extension||doc.name)]"></i>
                          <div class="doc-inf">
                            <span class="doc-nm">{{ doc.original_name||doc.name }}</span>
                            <span class="doc-sz">{{ doc.size_label||'' }} · {{ (doc.extension||'').toUpperCase() }}</span>
                          </div>
                          <div class="doc-card-acts" @click.stop>
                            <a v-if="doc.url" :href="doc.url" target="_blank" class="btn btn-xs btn-ghost"><i class="ti ti-external-link"></i></a>
                            <button v-if="!isLocked" class="btn btn-xs btn-ghost" @click="removeDoc(marche,di)">
                              <i class="ti ti-trash" style="color:#dc2626"></i>
                            </button>
                          </div>
                        </div>
                        <div v-if="marche.activeDocIdx===di" class="doc-viewer-inline">
                          <iframe v-if="isPdf(doc)" :src="doc.url+'#toolbar=1'" class="doc-iframe"></iframe>
                          <div v-else-if="isImage(doc)" class="doc-img-wrap"><img :src="doc.url" class="doc-img-full"/></div>
                          <div v-else class="doc-dl-wrap">
                            <a :href="doc.url" target="_blank" class="btn btn-sm btn-save"><i class="ti ti-download"></i> Ouvrir</a>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                </div><!-- /marche-body -->
              </div><!-- /marche-block -->
            </div><!-- /marches-list -->
          </div><!-- /amq-marches -->
        </div><!-- /amq-layout -->

        <!-- ─── FOOTER ─── -->
        <footer class="amq-footer">
          <div>
            <button v-if="!isLocked" type="button" class="btn btn-ghost btn-sm" :disabled="processing" @click="annuler"><i class="ti ti-x"></i> Annuler</button>
            <button v-if="!isLocked" type="button" class="btn btn-save btn-sm" :disabled="processing" @click="submit">
              <span v-if="processing" class="spin-s"></span>
              <i v-else class="ti ti-device-floppy"></i>{{ form.id?'Mettre à jour':'Enregistrer' }}
            </button>
          </div>
          <div class="footer-mid">
            <span v-if="form.id" class="saved-code"><i class="ti ti-check"></i> {{ form.code }}</span>
          </div>
          <div>
            <button v-if="form.id&&form.validation_status==='draft'" type="button" class="btn btn-sub btn-sm" :disabled="processing" @click="soumettre">
              <i class="ti ti-send"></i> Soumettre
            </button>
            <template v-if="canManage&&form.validation_status==='in_review'">
              <button type="button" class="btn btn-ok btn-sm" :disabled="processing" @click="valider('validate')"><i class="ti ti-circle-check"></i> Valider</button>
              <button type="button" class="btn btn-rej btn-sm" :disabled="processing" @click="promptReject"><i class="ti ti-circle-x"></i> Rejeter</button>
            </template>
          </div>
        </footer>
      </div><!-- /amq-body -->
    </div>

    <!-- Toast -->
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
import { ref, computed, reactive, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'
import axios from 'axios'

// ── Props ─────────────────────────────────────────────────────
const props = withDefaults(defineProps<{
  mission?:any; assignment?:any; auditorRole?:string; missionId?:number; assignmentId?:number
  form?:any; marchesData?:any[]; amqList?:any[]; currentAuditor?:any
  phaseAuditeurs?:any[]
  backUrl?:string; formUrl?:string
  urlStore?:string; urlUpdate?:string; urlSoumettre?:string; urlValider?:string
  urlImportExcel?:string; urlAiSuggest?:string
  urlDocUpload?:string; urlDeleteDoc?:string; urlIndex?:string
}>(), {
  marchesData:()=>[], amqList:()=>[], phaseAuditeurs:()=>[],
})

// ── Constantes ────────────────────────────────────────────────
const TABS = [
  {key:'ID',        label:'Identification',    icon:'ti-id'},
  {key:'ETAPES',    label:'Étapes principales',icon:'ti-list-check'},
  {key:'OBJECTIFS', label:'Objectifs d\'audit', icon:'ti-target'},
  {key:'FF',        label:'Forces / Faibl.',   icon:'ti-list-check'},
  {key:'DOCS',      label:'Documents',         icon:'ti-paperclip'},
]

// ── State ─────────────────────────────────────────────────────
let _sk = 0; const gk = () => ++_sk

const form = reactive<any>({
  id:null, code:'', validation_status:'draft', validation_note:'',
  intitule_qem:'', fait_par:'', revue_par:'', date_fait:'', date_revue:'',
  commentaire_global:'',
  ...(props.form ?? {})
})

const marches = reactive<any[]>(
  (props.marchesData as any[]).map(m => ({
    ...m, _k: gk(),
    etapes:       (m.etapes       || []),
    objectifs:    (m.objectifs    || []),
    forces:       (m.forces       || []),
    faiblesses:   (m.faiblesses   || []),
    attached_docs:(m.attached_docs|| []),
    activeDocIdx: null as number|null,
    uploading:    false,
  }))
)

const expandedMarches = ref<Set<string|number>>(new Set(marches.map(m => m._k)))
const marcheTab       = reactive<Record<string|number,string>>({})
const processing      = ref(false)
const iaLoading       = ref(false)
const newMarcheRef    = ref('')
const newMarcheTitle  = ref('')
const showIaZone      = ref(false)
const iaPrompt        = ref('')

const toast = ref({show:false, type:'success', msg:''})
let _tt: any
function showToast(t:string, m:string){ if(_tt) clearTimeout(_tt); toast.value={show:true,type:t,msg:m}; _tt=setTimeout(()=>{ toast.value.show=false },4000) }

// ── Computed ──────────────────────────────────────────────────
const canManage = computed(() => ['DM','CM'].includes(props.auditorRole ?? ''))
const isLocked  = computed(() => form.validation_status === 'validated' || (form.validation_status === 'in_review' && !canManage.value))

// ── Helpers ────────────────────────────────────────────────────
function activeTab(key:string|number): string { return marcheTab[key] || 'ID' }
function toggleMarche(key:string|number){ expandedMarches.value.has(key) ? expandedMarches.value.delete(key) : expandedMarches.value.add(key) }
function countEtape(m:any, statut:string): number { return (m.etapes||[]).filter((e:any) => e.statut === statut).length }
function stLbl(s:string|null): string { return ({oui:'OUI', non:'NON', sans_objet:'S/O', partiel:'PARTIEL'} as any)[s??''] ?? '—' }
function csrf(){ return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '' }
function vstLbl(s:string){ return ({draft:'Brouillon',in_review:'En attente',validated:'Validé ✓',rejected:'Rejeté'} as any)[s] ?? s }
function vstIcon(s:string){ return ({draft:'ti ti-pencil',in_review:'ti ti-clock',validated:'ti ti-circle-check',rejected:'ti ti-circle-x'} as any)[s] ?? 'ti ti-circle' }
function isPdf(doc:any):boolean{ const e=(doc.extension||'').toLowerCase(); return e==='pdf'||(doc.mime_type||'').includes('pdf') }
function isImage(doc:any):boolean{ return ['png','jpg','jpeg','gif','webp'].includes((doc.extension||'').toLowerCase()) }
function docIcon(n:string){ const e=(n||'').split('.').pop()?.toLowerCase()||''; if(e==='pdf') return 'ti-file-type-pdf'; if(['doc','docx'].includes(e)) return 'ti-file-type-doc'; if(['xls','xlsx'].includes(e)) return 'ti-file-type-xls'; return 'ti-file' }

// ── CRUD Marchés ───────────────────────────────────────────────
function addMarche(data?: any){
  if (!data && !newMarcheTitle.value.trim()) return
  const k = gk()
  marches.push({
    _k: k,
    reference:    data?.reference    || newMarcheRef.value.trim(),
    intitule:     data?.intitule     || newMarcheTitle.value.trim(),
    objet:        data?.objet        || '',
    montant:      data?.montant      || null,
    attributaire: data?.attributaire || '',
    date_marche:  data?.date_marche  || null,
    commentaire:  data?.commentaire  || '',
    etapes:       data?.etapes       || [],
    objectifs:    data?.objectifs    || [],
    forces:       data?.forces       || [],
    faiblesses:   data?.faiblesses   || [],
    attached_docs: [],
    activeDocIdx: null as number|null,
    uploading: false,
  })
  expandedMarches.value.add(k)
  marcheTab[k] = data ? 'ETAPES' : 'ID'
  newMarcheRef.value   = ''
  newMarcheTitle.value = ''
}

function removeMarche(key:string|number){
  if (!confirm('Supprimer ce marché ?')) return
  const i = marches.findIndex(m => m._k === key)
  if (i >= 0) marches.splice(i, 1)
  expandedMarches.value.delete(key)
  delete marcheTab[key]
}

// ── CRUD lignes ────────────────────────────────────────────────
function addEtape(m:any){ if(!m.etapes) m.etapes=[]; m.etapes.push({ref_etape:'', libelle:'', statut:null, observation:''}) }
function addObjectif(m:any){ if(!m.objectifs) m.objectifs=[]; m.objectifs.push({libelle:'', atteint:null, commentaire:''}) }
function addForce(m:any){ if(!m.forces) m.forces=[]; m.forces.push('') }
function addFaiblesse(m:any){ if(!m.faiblesses) m.faiblesses=[]; m.faiblesses.push('') }

// ── Import Excel ───────────────────────────────────────────────
async function onExcelImport(e:Event){
  const file = (e.target as HTMLInputElement).files?.[0]
  ;(e.target as HTMLInputElement).value = ''
  if (!file) return
  try {
    const fd = new FormData(); fd.append('file', file)
    const res = await axios.post(props.urlImportExcel||'', fd, {headers:{'Content-Type':'multipart/form-data'}})
    if (!res.data.success) throw new Error(res.data.error)
    ;(res.data.marches || []).forEach((m:any) => addMarche(m))
    showToast('success', `${res.data.count} marché(s) importé(s)`)
  } catch(err:any){ showToast('error','Erreur import : '+(err.response?.data?.error||err.message)) }
}

// ── IA ────────────────────────────────────────────────────────
async function suggestAnalyseComplete(){
  if (!iaPrompt.value.trim()) return; iaLoading.value=true
  try {
    const res = await axios.post(props.urlAiSuggest||'', {
      type:'analyse_complete', prompt:iaPrompt.value,
      marche_intitule:iaPrompt.value,
      mission_id:props.missionId, mission_title:props.mission?.title, entity_name:props.mission?.entity_name,
    },{headers:{'X-CSRF-TOKEN':csrf()}})
    if (!res.data.success) throw new Error(res.data.error)
    addMarche({
      intitule:    res.data.intitule   || iaPrompt.value,
      objet:       res.data.objet      || '',
      etapes:      res.data.etapes     || [],
      objectifs:   res.data.objectifs  || [],
      forces:      res.data.forces     || [],
      faiblesses:  res.data.faiblesses || [],
    })
    iaPrompt.value=''; showIaZone.value=false
    showToast('success', `Marché "${res.data.intitule||iaPrompt.value}" généré avec succès`)
  } catch(err:any){ showToast('error','Erreur IA : '+(err.response?.data?.error||err.message)) }
  finally{ iaLoading.value=false }
}

async function suggestEtapesIA(marche:any){
  iaLoading.value=true
  try {
    const res = await axios.post(props.urlAiSuggest||'', {
      type:'etapes_marche',
      marche_intitule:marche.intitule, marche_objet:marche.objet,
      mission_id:props.missionId, mission_title:props.mission?.title, entity_name:props.mission?.entity_name,
    },{headers:{'X-CSRF-TOKEN':csrf()}})
    if (!res.data.success) throw new Error(res.data.error)
    marche.etapes = res.data.etapes || []
    showToast('success', `${marche.etapes.length} étapes générées`)
  } catch(err:any){ showToast('error','Erreur IA : '+err.message) }
  finally{ iaLoading.value=false }
}

async function suggestObjectifsIA(marche:any){
  iaLoading.value=true
  try {
    const res = await axios.post(props.urlAiSuggest||'', {
      type:'objectifs_marche',
      marche_intitule:marche.intitule, marche_objet:marche.objet,
      mission_id:props.missionId, mission_title:props.mission?.title, entity_name:props.mission?.entity_name,
    },{headers:{'X-CSRF-TOKEN':csrf()}})
    if (!res.data.success) throw new Error(res.data.error)
    marche.objectifs = res.data.objectifs || []
    showToast('success', `${marche.objectifs.length} objectifs générés`)
  } catch(err:any){ showToast('error','Erreur IA : '+err.message) }
  finally{ iaLoading.value=false }
}

async function suggestFFIA(marche:any){
  iaLoading.value=true
  try {
    const res = await axios.post(props.urlAiSuggest||'', {
      type:'forces_faiblesses',
      marche_intitule:marche.intitule, marche_objet:marche.objet,
      etapes:marche.etapes,
      mission_id:props.missionId, mission_title:props.mission?.title, entity_name:props.mission?.entity_name,
    },{headers:{'X-CSRF-TOKEN':csrf()}})
    if (!res.data.success) throw new Error(res.data.error)
    if ((res.data.forces||[]).length)     marche.forces     = res.data.forces
    if ((res.data.faiblesses||[]).length) marche.faiblesses = res.data.faiblesses
    showToast('success', `${(res.data.forces||[]).length} force(s) et ${(res.data.faiblesses||[]).length} faiblesse(s) générées`)
  } catch(err:any){ showToast('error','Erreur IA : '+err.message) }
  finally{ iaLoading.value=false }
}

// ── Documents ─────────────────────────────────────────────────
function toggleDocViewer(marche:any, idx:number){ marche.activeDocIdx = marche.activeDocIdx===idx ? null : idx }

async function uploadDoc(e:Event, marche:any){
  const file = (e.target as HTMLInputElement).files?.[0]
  ;(e.target as HTMLInputElement).value = ''
  if (!file || !form.id) return
  marche.uploading = true
  try {
    const fd = new FormData()
    fd.append('file', file); fd.append('amq_id', String(form.id))
    if (marche.id) fd.append('marche_id', String(marche.id))
    const res = await axios.post(props.urlDocUpload||'', fd, {headers:{'Content-Type':'multipart/form-data','X-CSRF-TOKEN':csrf()}})
    if (!res.data.success) throw new Error(res.data.error)
    if (!marche.attached_docs) marche.attached_docs=[]
    marche.attached_docs.push(res.data.document)
    showToast('success', `"${file.name}" joint`)
  } catch(err:any){ showToast('error','Erreur upload : '+(err.response?.data?.error||err.message)) }
  finally{ marche.uploading=false }
}

async function removeDoc(marche:any, idx:number){
  if (!confirm('Supprimer ce document ?')) return
  const doc = marche.attached_docs[idx]
  if (doc?.id && props.urlDeleteDoc){
    try {
      const res = await fetch(props.urlDeleteDoc, {
        method:'DELETE', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},
        body:JSON.stringify({doc_id:doc.id, amq_id:form.id})
      })
      const d = await res.json()
      if (!d.success){ showToast('error', d.error??'Erreur'); return }
    } catch{ showToast('error','Erreur réseau'); return }
  }
  marche.attached_docs.splice(idx,1)
  if (marche.activeDocIdx === idx) marche.activeDocIdx = null
  else if (marche.activeDocIdx > idx) marche.activeDocIdx--
  showToast('success','Document supprimé')
}

// ── Sérialisation & Submit ─────────────────────────────────────
function serializeMarches(){
  return marches.map((m,mi) => ({
    id:         m.id || undefined,
    ordre:      mi+1,
    reference:  m.reference,
    intitule:   m.intitule,
    objet:      m.objet,
    montant:    m.montant,
    attributaire:m.attributaire,
    date_marche:m.date_marche,
    commentaire:m.commentaire,
    forces:     JSON.stringify(m.forces||[]),
    faiblesses: JSON.stringify(m.faiblesses||[]),
    etapes:     m.etapes||[],
    objectifs:  m.objectifs||[],
  }))
}

async function submit(){
  processing.value=true
  try {
    const payload = {
      mission_id:props.missionId, assignment_id:props.assignmentId,
      intitule_qem:form.intitule_qem,
      fait_par:form.fait_par, revue_par:form.revue_par,
      date_fait:form.date_fait, date_revue:form.date_revue,
      commentaire_global:form.commentaire_global,
      marches: JSON.stringify(serializeMarches()),
    }
    const method = form.id ? 'PUT' : 'POST'
    const url    = form.id ? (props.urlUpdate||`${props.formUrl}/${form.id}`) : (props.urlStore||props.formUrl)
    const res    = await fetch(url!, {method, headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()}, body:JSON.stringify(payload)})
    const d      = await res.json()
    if (d.success || res.ok){
      showToast('success', form.id ? 'Analyse mise à jour.' : 'Analyse créée.')
      if (!form.id && d.form?.id){ form.id=d.form.id; form.code=d.form.code }
      if (d.form) Object.assign(form, d.form)
    } else showToast('error', d.message ?? 'Erreur.')
  } catch{ showToast('error','Erreur réseau.') }
  finally{ processing.value=false }
}

function annuler(){ if(props.backUrl) router.visit(props.backUrl) }

async function soumettre(){
  processing.value=true
  try {
    const res = await fetch(props.urlSoumettre||`${props.formUrl}/${form.id}/soumettre`, {
      method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},
      body:JSON.stringify({mission_id:props.missionId, assignment_id:props.assignmentId})
    })
    const d = await res.json()
    if (d.success){ form.validation_status='in_review'; showToast('success','Soumis.') }
    else showToast('error', d.error ?? 'Erreur')
  } catch{ showToast('error','Erreur réseau') }
  processing.value=false
}

async function valider(action:string, note?:string){
  processing.value=true
  try {
    const res = await fetch(props.urlValider||`${props.formUrl}/${form.id}/valider`, {
      method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},
      body:JSON.stringify({mission_id:props.missionId, assignment_id:props.assignmentId, action, note})
    })
    const d = await res.json()
    if (d.success){ form.validation_status=d.status; showToast('success', action==='validate'?'Validé ✓':'Rejeté.') }
    else showToast('error', d.error ?? 'Erreur')
  } catch{ showToast('error','Erreur réseau') }
  processing.value=false
}

function promptReject(){ const n=prompt('Motif du rejet :'); if (!n?.trim()) return; valider('reject',n.trim()) }
function loadAmq(a:any){ router.visit(`${props.urlIndex?.replace(/\/[^/]*$/,'')||''}/${a.id}/edit?mission_id=${props.missionId}&assignment_id=${props.assignmentId}`) }

onBeforeUnmount(()=>{ if(_tt) clearTimeout(_tt) })
</script>

<style scoped>
*,*::before,*::after{box-sizing:border-box}
.amq-shell{display:flex;flex-direction:column;min-height:100vh;font-family:'Segoe UI',system-ui,sans-serif;background:#f0f4f8}

/* ── Header ── */
.amq-header{background:#fff;border-bottom:1px solid #e5e7eb;padding:12px 20px 0;position:sticky;top:0;z-index:50;box-shadow:0 1px 4px rgba(0,0,0,.05)}
.amq-hrow{display:flex;align-items:flex-start;gap:10px;padding-bottom:10px}
.amq-back{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border:1px solid #e5e7eb;border-radius:7px;color:#6b7280;text-decoration:none;flex-shrink:0}
.amq-back:hover{background:#f3f4f6}
.amq-hinfo{flex:1;min-width:0}
.amq-chips{display:flex;align-items:center;gap:5px;flex-wrap:wrap;margin-bottom:3px}
.amq-code{font-size:.68rem;font-weight:700;background:#1e293b;color:#fff;padding:2px 7px;border-radius:4px;font-family:ui-monospace,monospace}
.amq-chip{display:inline-flex;align-items:center;gap:3px;font-size:.66rem;font-weight:600;padding:2px 7px;border-radius:9px;border:1px solid transparent}
.chip-draft{background:#f3f4f6;color:#6b7280;border-color:#e5e7eb}
.chip-in_review{background:#e3f2fd;color:#1565C0;border-color:rgba(21,101,192,.2)}
.chip-validated{background:#ecfdf5;color:#059669;border-color:#a7f3d0}
.chip-type{background:#fef3c7;color:#d97706;border-color:#fde68a}
.chip-role-DM{background:#f5f3ff;color:#7c3aed;border-color:#ddd6fe}
.chip-role-CM{background:#f0f9ff;color:#0284c7;border-color:#bae6fd}
.chip-role-AS{background:#f0fdf4;color:#059669;border-color:#a7f3d0}
.chip-role-AJ{background:#fffbeb;color:#d97706;border-color:#fde68a}
.amq-title{font-size:1rem;font-weight:800;color:#111827;margin:0 0 3px}
.amq-meta{display:flex;align-items:center;gap:12px;flex-wrap:wrap;font-size:.72rem;color:#6b7280}
.amq-meta span{display:flex;align-items:center;gap:3px}
.amq-banner{display:flex;align-items:center;gap:7px;padding:6px 0;font-size:.76rem;font-weight:500}
.banner-lock{color:#059669;border-top:1px solid #a7f3d0}.banner-review{color:#1565C0}.banner-reject{color:#dc2626}

/* ── Layout ── */
.amq-body{flex:1;overflow:hidden;display:flex;flex-direction:column}
.amq-layout{display:grid;grid-template-columns:240px 1fr;gap:0;flex:1;overflow:hidden;height:calc(100vh - 118px)}

/* ── Sidebar ── */
.amq-sidebar{overflow-y:auto;border-right:1px solid #e5e7eb;background:#f9fafb;padding:10px;display:flex;flex-direction:column;gap:8px}
.amq-sidebar::-webkit-scrollbar{width:3px}
.card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;flex-shrink:0}
.card-lbl{display:flex;align-items:center;gap:5px;font-size:.64rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;padding:7px 10px;background:#f9fafb;border-bottom:1px solid #e5e7eb}
.card-cnt{margin-left:auto;font-size:.6rem;font-weight:800;background:#e2e8f0;color:#64748b;padding:1px 5px;border-radius:6px}
.card-body{padding:8px 10px}.p6{padding:6px!important}.p0{padding:0!important}
.fg{display:flex;flex-direction:column;gap:2px;margin-bottom:7px}.fg:last-child{margin-bottom:0}
.flbl{font-size:.6rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.04em;display:block}
.form-r2{display:grid;grid-template-columns:1fr 1fr;gap:6px}
.inp{background:#fff;border:1px solid #e5e7eb;color:#111827;padding:5px 8px;border-radius:5px;font-size:.76rem;outline:none;transition:border-color .15s;font-family:inherit;width:100%}
.inp:focus{border-color:#1565C0;box-shadow:0 0 0 2px rgba(21,101,192,.1)}
.inp:disabled,.inp-ro{background:#f9fafb;color:#9ca3af;cursor:default}
.inp-ta{resize:vertical;min-height:48px}
.inp-sm{padding:4px 7px;font-size:.72rem}
.aud-row{display:flex;align-items:center;gap:6px;padding:5px 7px;border-radius:6px;border:1px solid #e9ecef;background:#fafafa;margin-bottom:3px}
.aud-av{width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.52rem;font-weight:800;flex-shrink:0;border:2px solid transparent}
.av-DM{background:#f5f3ff;color:#7c3aed;border-color:#ddd6fe}.av-CM{background:#f0f9ff;color:#0284c7;border-color:#bae6fd}
.av-AS{background:#f0fdf4;color:#059669;border-color:#a7f3d0}.av-AJ{background:#fffbeb;color:#d97706;border-color:#fde68a}
.aud-inf{flex:1;min-width:0}
.aud-nm{font-size:.7rem;font-weight:600;color:#111827;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.aud-cd{font-size:.58rem;font-family:monospace;color:#9ca3af;display:block}
.stbl{width:100%;border-collapse:collapse;font-size:.7rem}
.stbl thead tr{background:#f9fafb}
.stbl th,.stbl td{padding:6px 9px;border-bottom:1px solid #f3f4f6;text-align:left}
.stbl th{font-size:.6rem;font-weight:700;color:#9ca3af;text-transform:uppercase}
.stbl-row{cursor:pointer}.stbl-row:hover{background:#f9fafb}
.td-empty{text-align:center;color:#d1d5db;padding:12px}
.td-code{font-family:ui-monospace,monospace;font-size:.66rem;color:#6b7280}
.td-ov{max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
/* Légende */
.lg-row{display:flex;align-items:center;gap:8px;padding:4px 0;border-bottom:1px solid #f3f4f6}
.lg-row b{font-size:.72rem;color:#374151}
.st-oui{background:#d1fae5;color:#065f46;font-size:.62rem;font-weight:800;padding:2px 6px;border-radius:4px;display:inline-block;min-width:30px;text-align:center}
.st-non{background:#fef2f2;color:#991b1b;font-size:.62rem;font-weight:800;padding:2px 6px;border-radius:4px;display:inline-block;min-width:30px;text-align:center}
.st-so{background:#f1f5f9;color:#64748b;font-size:.62rem;font-weight:800;padding:2px 6px;border-radius:4px;display:inline-block;min-width:30px;text-align:center}

/* ── Zone marchés ── */
.amq-marches{display:flex;flex-direction:column;overflow:hidden;background:#f9fafb}
.marches-hdr{background:#fff;border-bottom:1px solid #e5e7eb;padding:10px 14px;flex-shrink:0;display:flex;flex-direction:column;gap:7px}
.marches-hdr-title{display:flex;align-items:center;gap:6px;font-size:.85rem;font-weight:700;color:#111827}
.marche-cnt{font-size:.66rem;font-weight:800;background:#fef3c7;color:#d97706;padding:2px 6px;border-radius:7px}
.create-row{display:flex;align-items:center;gap:6px}
.tool-row{display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.ia-zone{background:#fdf4ff;border:1.5px solid #e9d5ff;border-radius:7px;padding:10px;display:flex;flex-direction:column;gap:7px}
.ia-zone-ttl{font-size:.7rem;font-weight:700;color:#7c3aed;display:flex;align-items:center;gap:5px}
.ia-ta{font-size:.76rem;resize:vertical;min-height:52px}
.ia-zone-acts{display:flex;gap:7px}
.marche-empty{display:flex;flex-direction:column;align-items:center;gap:7px;padding:28px;color:#9ca3af;text-align:center;background:#fff;border:1.5px dashed #e5e7eb;border-radius:8px;margin:8px}
.marche-empty i{font-size:1.5rem;opacity:.2}.marche-empty p{font-size:.76rem}

/* Liste marchés */
.marches-list{flex:1;overflow-y:auto;padding:8px}
.marches-list::-webkit-scrollbar{width:3px}

/* Bloc marché */
.marche-block{background:#fff;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:8px;overflow:hidden}
.marche-hdr{display:flex;align-items:center;justify-content:space-between;padding:9px 12px;cursor:pointer;transition:background .12s;border-bottom:1px solid transparent}
.marche-hdr:hover{background:#f9fafb}
.marche-block:has(.marche-body) .marche-hdr{border-bottom-color:#e5e7eb}
.marche-hdr-l{display:flex;align-items:center;gap:8px;flex:1;min-width:0;overflow:hidden}
.marche-hdr-r{display:flex;align-items:center;gap:5px;flex-shrink:0}
.marche-chev{width:16px;height:16px;display:flex;align-items:center;justify-content:center;color:#9ca3af;flex-shrink:0}
.marche-chev i{transition:transform .2s;font-size:.8rem}
.marche-chev--open i{transform:rotate(90deg)}
.marche-ref-badge{font-size:.62rem;font-weight:800;font-family:ui-monospace,monospace;background:#fef3c7;color:#d97706;padding:2px 6px;border-radius:4px;border:1px solid #fde68a;flex-shrink:0}
.marche-inf{min-width:0;overflow:hidden;flex:1}
.marche-nm{font-size:.8rem;font-weight:600;color:#111827;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.marche-sub{font-size:.65rem;color:#9ca3af;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.marche-scores{display:flex;align-items:center;gap:4px;flex-shrink:0}
.s-oui-bg{display:inline-flex;align-items:center;gap:2px;font-size:.6rem;font-weight:700;background:#d1fae5;color:#065f46;padding:1px 5px;border-radius:4px;border:1px solid #a7f3d0}
.s-non-bg{display:inline-flex;align-items:center;gap:2px;font-size:.6rem;font-weight:700;background:#fef2f2;color:#991b1b;padding:1px 5px;border-radius:4px;border:1px solid #fecaca}
.s-so-bg{display:inline-flex;align-items:center;gap:2px;font-size:.6rem;font-weight:700;background:#f1f5f9;color:#64748b;padding:1px 5px;border-radius:4px;border:1px solid #e2e8f0}
.ibtn{width:22px;height:22px;display:flex;align-items:center;justify-content:center;background:transparent;border:1px solid transparent;border-radius:4px;cursor:pointer;font-size:.74rem;color:#d1d5db;padding:0}
.ibtn-del:hover{color:#dc2626;border-color:#fecaca;background:#fef2f2}

/* Onglets */
.marche-tabs{display:flex;align-items:center;padding:6px 12px 0;background:#f9fafb;border-bottom:1px solid #e5e7eb;flex-wrap:wrap;gap:1px}
.mtab{display:inline-flex;align-items:center;gap:3px;padding:5px 9px;border-radius:5px 5px 0 0;border:1.5px solid transparent;background:none;color:#6b7280;cursor:pointer;font-size:.68rem;font-weight:600;font-family:inherit;transition:all .12s;border-bottom:none}
.mtab:hover{color:#1565C0;background:#eff6ff}
.mtab.active{background:#fff;border-color:#e5e7eb;border-bottom-color:#fff;color:#1565C0}
.mtab-ct{font-size:.56rem;padding:0 3px;border-radius:3px;background:rgba(21,101,192,.1);color:#1565C0}
.marche-body{overflow:hidden}
.tab-content{padding:12px;overflow-y:auto;max-height:600px}
.tab-content::-webkit-scrollbar{width:3px}

/* Identification */
.id-grid{display:grid;grid-template-columns:1fr 1fr;gap:7px}
.id-grid .full{grid-column:span 2}

/* Tableau étapes */
.tb-bar{display:flex;align-items:center;gap:5px;padding-bottom:8px;flex-wrap:wrap}
.tb-ct{margin-left:auto;font-size:.64rem;color:#9ca3af}
.tb-scores{display:flex;gap:6px;font-size:.66rem;font-weight:600}
.ms-auto{margin-left:auto}
.s-oui-sm{color:#065f46;background:#d1fae5;padding:2px 6px;border-radius:4px}
.s-non-sm{color:#991b1b;background:#fef2f2;padding:2px 6px;border-radius:4px}
.s-so-sm{color:#64748b;background:#f1f5f9;padding:2px 6px;border-radius:4px}
.tb-empty{display:flex;flex-direction:column;align-items:center;gap:5px;padding:16px;color:#9ca3af;text-align:center;background:#fafafa;border:1.5px dashed #e5e7eb;border-radius:6px}
.tb-empty i{font-size:1rem;opacity:.25}.tb-empty p{font-size:.68rem}
.tb-wrap{overflow:auto;border:1px solid #e5e7eb;border-radius:7px}
.btbl{width:100%;border-collapse:collapse;font-size:.68rem;table-layout:fixed}
.btbl thead th{background:#1e293b;color:rgba(255,255,255,.88);font-size:.56rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;padding:6px 5px;border:none;white-space:nowrap;position:sticky;top:0;z-index:2}
.btbl tbody td{padding:2px 3px;border:1px solid #f3f4f6;vertical-align:middle}
.td-n{text-align:center;font-weight:700;color:#9ca3af;font-size:.6rem}.td-c{text-align:center}
.etape-row{background:#fff;transition:background .1s}.etape-row:hover td{background:#f8fbff}
.row-oui td{border-left:3px solid #059669!important;background:#f0fdf4}
.row-non td{border-left:3px solid #dc2626!important;background:#fef2f2}
.row-so  td{border-left:3px solid #94a3b8!important;background:#f8fafc}
.c-inp{width:100%;border:1px solid transparent;border-radius:3px;padding:2px 3px;font-size:.64rem;color:#111827;font-family:inherit;outline:none;background:transparent;height:22px}
.c-inp:hover{border-color:#e5e7eb;background:#fff}.c-inp:focus{border-color:#1565C0;background:#fff}
.c-x{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ro-t{font-size:.64rem;color:#374151;display:block}.ro-e{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%}
/* Boutons statut */
.st-btns{display:flex;gap:2px;justify-content:center}
.st-btn{padding:1px 4px;border-radius:3px;font-size:.58rem;font-weight:800;border:1.5px solid;cursor:pointer;transition:all .12s;background:transparent;font-family:inherit}
.st-oui-btn{border-color:#a7f3d0;color:#059669}.st-oui-btn.active{background:#059669;color:#fff;border-color:#059669}
.st-non-btn{border-color:#fecaca;color:#dc2626}.st-non-btn.active{background:#dc2626;color:#fff;border-color:#dc2626}
.st-so-btn{border-color:#e2e8f0;color:#64748b}.st-so-btn.active{background:#64748b;color:#fff;border-color:#64748b}
.b-st{font-size:.6rem;font-weight:700;padding:2px 5px;border-radius:3px;display:inline-block}
.st-oui{background:#d1fae5;color:#065f46}.st-non{background:#fef2f2;color:#991b1b}
.st-sans_objet{background:#f1f5f9;color:#64748b}.st-partiel{background:#fef3c7;color:#d97706}
.btn-del{background:none;border:none;cursor:pointer;color:#d1d5db;font-size:.72rem;padding:1px 2px;border-radius:3px}
.btn-del:hover{color:#ef4444;background:#fee2e2}

/* Objectifs */
.obj-list{display:flex;flex-direction:column;gap:6px}
.obj-item{display:flex;align-items:flex-start;gap:7px;padding:8px 10px;background:#fafafa;border:1px solid #e5e7eb;border-radius:7px}
.obj-num{width:22px;height:22px;border-radius:50%;background:#1e293b;color:#fff;font-size:.6rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px}
.obj-body{flex:1;min-width:0;display:flex;flex-direction:column;gap:5px}
.obj-ta{font-size:.74rem;min-height:44px;resize:vertical}
.obj-txt{font-size:.74rem;color:#111827;line-height:1.5}
.obj-foot{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.obj-atteint{display:flex;align-items:center;gap:6px;font-size:.66rem;font-weight:600}
.obj-del{margin-top:2px;flex-shrink:0}

/* Forces / Faiblesses */
.ff-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:4px}
.ff-col{border-radius:7px;overflow:hidden;border:1.5px solid}
.ff-col--f{border-color:#a7f3d0;background:#f0fdf4}
.ff-col--w{border-color:#fecaca;background:#fef2f2}
.ff-hdr{display:flex;align-items:center;gap:6px;padding:7px 10px;font-size:.7rem;font-weight:700;border-bottom:1px solid}
.ff-col--f .ff-hdr{color:#059669;border-bottom-color:#a7f3d0;background:#dcfce7}
.ff-col--w .ff-hdr{color:#dc2626;border-bottom-color:#fecaca;background:#fee2e2}
.ff-cnt{font-size:.6rem;font-weight:800;padding:1px 5px;border-radius:6px;background:rgba(0,0,0,.08)}
.ff-add-btn{margin-left:auto;background:transparent;border:1px solid currentColor;padding:2px 5px;border-radius:4px;cursor:pointer;font-size:.65rem;font-family:inherit;display:inline-flex;align-items:center;gap:2px;color:inherit;opacity:.75}
.ff-add-btn:hover{opacity:1}
.ff-empty{padding:10px;font-size:.68rem;color:#9ca3af;text-align:center;font-style:italic}
.ff-list{padding:7px;display:flex;flex-direction:column;gap:5px}
.ff-item{display:flex;align-items:flex-start;gap:5px;padding:5px 7px;background:rgba(255,255,255,.7);border-radius:5px;border:1px solid rgba(0,0,0,.06)}
.ff-dot{font-size:.76rem;margin-top:2px;flex-shrink:0}
.ff-col--f .ff-dot{color:#059669}.ff-col--w .ff-dot{color:#dc2626}
.ff-inp{flex:1;border:1px solid transparent;border-radius:3px;padding:2px 4px;font-size:.68rem;color:#111827;font-family:inherit;outline:none;background:transparent;resize:vertical;min-height:36px;line-height:1.4}
.ff-inp:hover{border-color:#e5e7eb;background:#fff}.ff-inp:focus{border-color:#1565C0;background:#fff}
.ff-txt{flex:1;font-size:.68rem;color:#374151;line-height:1.5;white-space:pre-wrap}

/* Documents */
.docs-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;gap:8px;flex-wrap:wrap}
.docs-ttl{display:flex;align-items:center;gap:5px;font-size:.68rem;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.04em}
.docs-cnt{font-size:.6rem;font-weight:800;background:#e2e8f0;color:#64748b;padding:1px 5px;border-radius:6px}
.docs-grid{display:flex;flex-direction:column;gap:6px}
.doc-card{border:1px solid #e5e7eb;border-radius:7px;overflow:hidden;background:#fff}
.doc-card--active{border-color:#1565C0}
.doc-card-hdr{display:flex;align-items:center;gap:8px;padding:7px 10px;cursor:pointer;transition:background .12s}
.doc-card-hdr:hover{background:#f9fafb}
.doc-ico{font-size:1.1rem;color:#6b7280;flex-shrink:0}
.doc-inf{flex:1;min-width:0}
.doc-nm{font-size:.74rem;font-weight:500;color:#111827;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.doc-sz{font-size:.6rem;color:#9ca3af;display:block}
.doc-card-acts{display:flex;align-items:center;gap:3px;flex-shrink:0}
.doc-viewer-inline{border-top:1px solid #e5e7eb}
.doc-iframe{width:100%;height:400px;border:none;display:block}
.doc-img-wrap{padding:10px;display:flex;justify-content:center}
.doc-img-full{max-width:100%;height:auto;max-height:400px;border-radius:5px}
.doc-dl-wrap{display:flex;justify-content:center;padding:16px}

/* Buttons */
.btn{display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:6px;font-size:.78rem;font-weight:600;border:none;cursor:pointer;font-family:inherit;transition:all .15s;white-space:nowrap}
.btn-save{background:#1e293b;color:#fff}.btn-save:hover:not(:disabled){background:#0f172a}
.btn-ghost{background:#fff;color:#374151;border:1px solid #e5e7eb}.btn-ghost:hover:not(:disabled){background:#f9fafb}
.btn-sub{background:#eff6ff;color:#2563EB;border:1px solid #bfdbfe}
.btn-ok{background:#ecfdf5;color:#059669;border:1px solid #a7f3d0}
.btn-rej{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
.btn-ai{background:linear-gradient(135deg,#6d28d9,#7c3aed);color:#fff}.btn-ai:hover:not(:disabled){filter:brightness(1.1)}
.btn-import{background:#f0f9ff;color:#0369a1;border:1px solid #bae6fd;cursor:pointer}
.btn-sm{padding:4px 9px;font-size:.74rem}
.btn-xs{padding:3px 7px;font-size:.68rem}
.btn:disabled{opacity:.45;cursor:not-allowed}
.hidden{display:none}

/* Footer */
.amq-footer{display:flex;align-items:center;justify-content:space-between;padding:9px 18px;background:#fff;border-top:1px solid #e5e7eb;flex-wrap:wrap;gap:6px;flex-shrink:0}
.amq-footer>div{display:flex;gap:6px;flex-wrap:wrap}
.footer-mid{flex:1;display:flex;justify-content:center}
.saved-code{font-size:.7rem;color:#059669;display:flex;align-items:center;gap:3px;font-weight:600}

/* Toast */
.toast{position:fixed;top:16px;right:16px;z-index:9999;display:flex;align-items:center;gap:7px;padding:9px 14px;border-radius:8px;font-size:.78rem;font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.12);border:1px solid transparent}
.toast--success{background:#ecfdf5;color:#059669;border-color:#a7f3d0}
.toast--error{background:#fef2f2;color:#dc2626;border-color:#fecaca}
.toast-t-enter-active,.toast-t-leave-active{transition:all .25s}
.toast-t-enter-from,.toast-t-leave-to{opacity:0;transform:translateX(10px)}
.spin-s{width:10px;height:10px;border-radius:50%;border:2px solid currentColor;border-top-color:transparent;animation:spin .6s linear infinite;display:inline-block;flex-shrink:0}
.spin{animation:spin .6s linear infinite;display:inline-block}
@keyframes spin{to{transform:rotate(360deg)}}
::-webkit-scrollbar{width:4px;height:4px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:#e2e8f0;border-radius:2px}
</style>