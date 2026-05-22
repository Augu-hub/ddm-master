<template>
  <Teleport to="body">
    <Transition name="drawer-slide">
      <div v-if="visible" class="ft-drawer-overlay">
        <div class="ft-drawer-scrim" @click="$emit('close')"></div>

        <div class="ft-drawer" :style="'--dc:' + (currentOutil?.color ?? '#0f172a')">

          <!-- Header -->
          <div class="drawer-hdr">
            <div class="drawer-hdr__left">
              <span class="drawer-code">{{ outilCode }}</span>
              <div>
                <div class="drawer-title">{{ currentOutil?.label }}</div>
                <div class="drawer-sub">
                  {{ testRef }}
                  <span v-if="procIdx !== null"> · Procédure {{ (procIdx ?? 0) + 1 }}</span>
                </div>
              </div>
            </div>
            <div class="drawer-hdr__right">
              <label v-if="!isLocked" class="btn btn-import btn-sm" title="Importer depuis CSV/TSV">
                <span v-if="importLoading" class="spin-s"></span>
                <i v-else class="ti ti-file-upload"></i>
                Import CSV
                <input type="file" accept=".csv,.tsv" class="hidden" @change="$emit('import', $event)" />
              </label>
              <button class="btn btn-drawer-save btn-sm" :disabled="saving" @click="$emit('save')">
                <span v-if="saving" class="spin-s"></span>
                <i v-else class="ti ti-device-floppy"></i>
                Enregistrer
              </button>
              <button class="btn btn-close-x btn-sm" @click="$emit('close')">
                <i class="ti ti-x"></i>
              </button>
            </div>
          </div>

          <!-- Contexte -->
          <div class="drawer-ctx">
            <span><i class="ti ti-building"></i> {{ missionLibelle }}</span>
            <span><i class="ti ti-target"></i> {{ objNum }}</span>
            <span><i class="ti ti-checklist"></i> {{ testRef }}</span>
            <span v-if="procIdx !== null" class="ctx-proc">
              <i class="ti ti-list-check"></i> Procédure {{ (procIdx ?? 0) + 1 }}
            </span>
          </div>

          <!-- Corps scrollable -->
          <div class="drawer-body">

            <!-- ══════════════════════════════════════════════════
                 OUTIL I — Grille d'Entretien
            ══════════════════════════════════════════════════ -->
            <template v-if="outilCode === 'I'">
              <div class="ot-section">
                <h3 class="ot-ttl">Informations générales</h3>
                <div class="ot-grid">
                  <div class="ot-field">
                    <label class="ot-lbl">Date de l'entretien</label>
                    <input type="date" class="ot-inp" v-model="data.I.date" />
                  </div>
                  <div class="ot-field ot-fw">
                    <label class="ot-lbl">Interlocuteur(s) — Nom, Email</label>
                    <input type="text" class="ot-inp" v-model="data.I.interlocuteurs" placeholder="Jean Dupont, j.dupont@org.fr" />
                  </div>
                  <div class="ot-field ot-fw">
                    <label class="ot-lbl">Objectif d'audit</label>
                    <textarea class="ot-ta" v-model="data.I.objectif_audit" rows="2" placeholder="S'assurer que…"></textarea>
                  </div>
                </div>
              </div>

              <div class="ot-section">
                <div class="ot-bar">
                  <h3 class="ot-ttl">Questions (QQOCPQ)</h3>
                  <button class="btn-add" @click="data.I.questions.push({type:'Ouverte',question:'',reponse:''})">
                    <i class="ti ti-plus"></i> Question
                  </button>
                </div>
                <div class="ot-tbl-wrap">
                  <table class="ot-tbl">
                    <thead>
                      <tr>
                        <th style="width:34px" class="tc">N°</th>
                        <th style="width:108px">Type</th>
                        <th>Question</th>
                        <th>Réponse / Observation</th>
                        <th style="width:30px"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="!data.I.questions.length">
                        <td colspan="5" class="td-empty">Aucune question</td>
                      </tr>
                      <tr v-for="(q, qi) in data.I.questions" :key="qi">
                        <td class="tc muted">{{ qi + 1 }}</td>
                        <td>
                          <select class="ot-sel" v-model="q.type">
                            <option>Ouverte</option><option>Fermée</option>
                            <option>Factuelle</option><option>Rebond</option>
                          </select>
                        </td>
                        <td><textarea class="ot-ta-sm" v-model="q.question" rows="2" placeholder="Question…"></textarea></td>
                        <td><textarea class="ot-ta-sm" v-model="q.reponse" rows="2" placeholder="Réponse…"></textarea></td>
                        <td class="tc"><button class="btn-del" @click="data.I.questions.splice(qi,1)">×</button></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="ot-section">
                <h3 class="ot-ttl">Synthèse &amp; Validation</h3>
                <div class="ot-field ot-fw" style="margin-bottom:.75rem">
                  <label class="ot-lbl">Points clés validés avec l'interlocuteur</label>
                  <textarea class="ot-ta" v-model="data.I.synthese" rows="3" placeholder="Résumez les points validés…"></textarea>
                </div>
                <div class="ot-grid">
                  <div class="ot-field">
                    <label class="ot-lbl">Signature Auditeur</label>
                    <input type="text" class="ot-inp" v-model="data.I.sig_auditeur" :placeholder="auditeurNom" />
                  </div>
                  <div class="ot-field">
                    <label class="ot-lbl">Signature Interlocuteur</label>
                    <input type="text" class="ot-inp" v-model="data.I.sig_interlocuteur" placeholder="Nom interlocuteur" />
                  </div>
                </div>
              </div>
            </template>

            <!-- ══════════════════════════════════════════════════
                 OUTIL II — Grille Analyse des Tâches
            ══════════════════════════════════════════════════ -->
            <template v-else-if="outilCode === 'II'">
              <div class="ot-section">
                <div class="ot-grid">
                  <div class="ot-field ot-fw">
                    <label class="ot-lbl">Processus audité</label>
                    <input type="text" class="ot-inp" v-model="data.II.processus" placeholder="Ex : Processus achats" />
                  </div>
                  <div class="ot-field">
                    <label class="ot-lbl">Date</label>
                    <input type="date" class="ot-inp" v-model="data.II.date" />
                  </div>
                </div>
              </div>

              <div class="ot-section">
                <div class="ot-bar">
                  <h3 class="ot-ttl">Acteurs du processus</h3>
                  <button v-if="data.II.acteurs.length < 8" class="btn-add" @click="data.II.acteurs.push('')">
                    <i class="ti ti-plus"></i> Acteur
                  </button>
                </div>
                <div class="acteurs-row">
                  <div v-for="(a, ai) in data.II.acteurs" :key="ai" class="acteur-item">
                    <span class="acteur-bdg" :style="'background:'+ACOLORS[ai%ACOLORS.length]">A{{ ai+1 }}</span>
                    <input type="text" class="ot-inp-sm" style="width:110px" v-model="data.II.acteurs[ai]" :placeholder="'Acteur '+(ai+1)" />
                    <button v-if="data.II.acteurs.length > 1" class="btn-del-sm" @click="retirerActeur(ai)"><i class="ti ti-x"></i></button>
                  </div>
                </div>
                <p class="legend">R = Réalise · A = Approuve · C = Consulté · I = Informé</p>
              </div>

              <div class="ot-section">
                <div class="ot-bar">
                  <h3 class="ot-ttl">Tâches &amp; Séparation des fonctions</h3>
                  <button class="btn-add" @click="data.II.taches.push({libelle:'',roles:new Array(data.II.acteurs.length).fill('')})">
                    <i class="ti ti-plus"></i> Tâche
                  </button>
                </div>
                <div class="ot-tbl-wrap">
                  <table class="ot-tbl" style="min-width:400px">
                    <thead>
                      <tr>
                        <th style="width:34px" class="tc">N°</th>
                        <th style="min-width:150px">Tâche du processus</th>
                        <th v-for="(a,ai) in data.II.acteurs" :key="ai" class="tc" style="width:44px"
                            :style="'color:#fff;background:'+ACOLORS[ai%ACOLORS.length]">A{{ ai+1 }}</th>
                        <th style="width:30px"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="!data.II.taches.length">
                        <td :colspan="2+data.II.acteurs.length+1" class="td-empty">Aucune tâche</td>
                      </tr>
                      <tr v-for="(t,ti) in data.II.taches" :key="ti">
                        <td class="tc muted">{{ ti+1 }}</td>
                        <td><input type="text" class="ot-inp-sm" v-model="t.libelle" placeholder="Libellé de la tâche…" /></td>
                        <td v-for="(a,ai) in data.II.acteurs" :key="ai" class="tc" style="padding:3px">
                          <select class="ot-sel-xs" v-model="t.roles[ai]">
                            <option value="">—</option><option>R</option><option>A</option><option>C</option><option>I</option>
                          </select>
                        </td>
                        <td class="tc"><button class="btn-del" @click="data.II.taches.splice(ti,1)">×</button></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="ot-section">
                <label class="ot-lbl">Observations — fonctions incompatibles</label>
                <textarea class="ot-ta" v-model="data.II.observations" rows="4" placeholder="Identifiez les cumuls de rôles incompatibles…"></textarea>
              </div>
            </template>

            <!-- ══════════════════════════════════════════════════
                 OUTIL III — Diagramme de Flux
            ══════════════════════════════════════════════════ -->
            <template v-else-if="outilCode === 'III'">
              <div class="ot-section">
                <div class="ot-grid">
                  <div class="ot-field ot-fw">
                    <label class="ot-lbl">Processus</label>
                    <input type="text" class="ot-inp" v-model="data.III.processus" placeholder="Nom du processus" />
                  </div>
                  <div class="ot-field">
                    <label class="ot-lbl">Version</label>
                    <input type="text" class="ot-inp" v-model="data.III.version" placeholder="V1" />
                  </div>
                  <div class="ot-field">
                    <label class="ot-lbl">Date</label>
                    <input type="date" class="ot-inp" v-model="data.III.date" />
                  </div>
                </div>
              </div>

              <div class="ot-section">
                <div class="ot-bar">
                  <h3 class="ot-ttl">Étape 1 — Inventaire des activités et acteurs</h3>
                  <button class="btn-add" @click="data.III.activites.push({libelle:'',acteur:'',entrant:'',sortant:'',documents:''})">
                    <i class="ti ti-plus"></i> Activité
                  </button>
                </div>
                <div class="ot-tbl-wrap">
                  <table class="ot-tbl" style="min-width:650px">
                    <thead>
                      <tr>
                        <th style="width:30px" class="tc">N°</th>
                        <th style="min-width:150px">Activité</th>
                        <th style="width:100px">Acteur responsable</th>
                        <th style="width:100px">Élément entrant</th>
                        <th style="width:100px">Élément sortant</th>
                        <th style="width:100px">Documents</th>
                        <th style="width:28px"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="!data.III.activites.length">
                        <td colspan="7" class="td-empty">Aucune activité</td>
                      </tr>
                      <tr v-for="(a,ai) in data.III.activites" :key="ai">
                        <td class="tc muted">{{ ai+1 }}</td>
                        <td><textarea class="ot-ta-sm" v-model="a.libelle" rows="2" placeholder="Description…"></textarea></td>
                        <td><input type="text" class="ot-inp-sm" v-model="a.acteur" /></td>
                        <td><input type="text" class="ot-inp-sm" v-model="a.entrant" /></td>
                        <td><input type="text" class="ot-inp-sm" v-model="a.sortant" /></td>
                        <td><input type="text" class="ot-inp-sm" v-model="a.documents" /></td>
                        <td class="tc"><button class="btn-del" @click="data.III.activites.splice(ai,1)">×</button></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="ot-section">
                <h3 class="ot-ttl">Étape 2 — Description narrative du processus</h3>
                <textarea class="ot-ta" v-model="data.III.description_narrative" rows="5" placeholder="Décrire le déroulement chronologique du processus…"></textarea>
              </div>

              <div class="ot-section">
                <h3 class="ot-ttl">Étape 3 — Symboles ISO 5807</h3>
                <div class="sym-grid">
                  <div v-for="s in SYMBOLES" :key="s.nom" class="sym-item">
                    <div class="sym-icon" :style="'border-color:'+s.color+';color:'+s.color">{{ s.icon }}</div>
                    <span class="sym-lbl">{{ s.nom }}</span>
                  </div>
                </div>
              </div>

              <div class="ot-section">
                <label class="ot-lbl">Synthèse et validations</label>
                <textarea class="ot-ta" v-model="data.III.synthese_validations" rows="3" placeholder="Risques identifiés, contrôles manquants…"></textarea>
              </div>
            </template>

            <!-- ══════════════════════════════════════════════════
                 OUTIL IV — Approche Processus
            ══════════════════════════════════════════════════ -->
            <template v-else-if="outilCode === 'IV'">
              <div class="ot-section">
                <div class="ot-grid">
                  <div class="ot-field ot-fw">
                    <label class="ot-lbl">Domaine audité</label>
                    <input type="text" class="ot-inp" v-model="data.IV.domaine" placeholder="Direction Financière…" />
                  </div>
                  <div class="ot-field">
                    <label class="ot-lbl">Date</label>
                    <input type="date" class="ot-inp" v-model="data.IV.date" />
                  </div>
                </div>
              </div>

              <div class="ot-tabs">
                <button v-for="tp in PTYPES" :key="tp.code" class="ot-tab"
                        :class="activeTabIV===tp.code?'ot-tab--active':''"
                        :style="activeTabIV===tp.code?'--tc:'+tp.color:''"
                        @click="activeTabIV=tp.code">{{ tp.label }}</button>
              </div>

              <div v-for="tp in PTYPES" :key="tp.code">
                <div v-if="activeTabIV===tp.code" class="ot-section" style="padding-top:0;border-top:none">
                  <div class="ot-bar" style="margin-bottom:.5rem">
                    <p style="font-size:.66rem;color:#6b7280;margin:0">{{ tp.desc }}</p>
                    <button class="btn-add" :style="'background:'+tp.color+';color:#fff;border-color:'+tp.color"
                            @click="addProc(tp.code)"><i class="ti ti-plus"></i> Processus</button>
                  </div>
                  <div class="ot-tbl-wrap">
                    <table class="ot-tbl" style="min-width:720px">
                      <thead>
                        <tr>
                          <th style="min-width:100px" :style="'color:'+tp.color">Nom</th>
                          <th style="min-width:90px"  :style="'color:'+tp.color">Finalité</th>
                          <th style="min-width:90px"  :style="'color:'+tp.color">Entrants</th>
                          <th style="min-width:90px"  :style="'color:'+tp.color">Sortants</th>
                          <th style="min-width:120px" :style="'color:'+tp.color">Activités</th>
                          <th style="min-width:80px"  :style="'color:'+tp.color">Clients</th>
                          <th style="min-width:80px"  :style="'color:'+tp.color">Fournisseurs</th>
                          <th style="width:28px"></th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-if="!data.IV.processus[tp.code]?.length">
                          <td colspan="8" class="td-empty">Aucun processus</td>
                        </tr>
                        <tr v-for="(p,pi) in (data.IV.processus[tp.code]||[])" :key="pi">
                          <td><input type="text" class="ot-inp-sm" v-model="p.nom" /></td>
                          <td><input type="text" class="ot-inp-sm" v-model="p.finalite" /></td>
                          <td><input type="text" class="ot-inp-sm" v-model="p.entrants" /></td>
                          <td><input type="text" class="ot-inp-sm" v-model="p.sortants" /></td>
                          <td><textarea class="ot-ta-sm" v-model="p.activites" rows="2"></textarea></td>
                          <td><input type="text" class="ot-inp-sm" v-model="p.clients" /></td>
                          <td><input type="text" class="ot-inp-sm" v-model="p.fournisseurs" /></td>
                          <td class="tc"><button class="btn-del" @click="data.IV.processus[tp.code].splice(pi,1)">×</button></td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </template>

            <!-- ══════════════════════════════════════════════════
                 OUTIL V — Test de Cheminement
            ══════════════════════════════════════════════════ -->
            <template v-else-if="outilCode === 'V'">
              <div class="ot-section">
                <div class="ot-grid">
                  <div class="ot-field ot-fw">
                    <label class="ot-lbl">Transaction sélectionnée <span style="color:#dc2626">*</span></label>
                    <input type="text" class="ot-inp" v-model="data.V.transaction" placeholder="Ex : Facture N°12345 du 15/01/2025" />
                  </div>
                  <div class="ot-field">
                    <label class="ot-lbl">Référence</label>
                    <input type="text" class="ot-inp" v-model="data.V.reference" />
                  </div>
                  <div class="ot-field">
                    <label class="ot-lbl">Date du test</label>
                    <input type="date" class="ot-inp" v-model="data.V.date_test" />
                  </div>
                  <div class="ot-field ot-fw">
                    <label class="ot-lbl">Processus testé</label>
                    <input type="text" class="ot-inp" v-model="data.V.processus" placeholder="Processus d'achats…" />
                  </div>
                </div>
              </div>

              <div class="ot-section">
                <div class="ot-bar">
                  <h3 class="ot-ttl">Suivi de la transaction étape par étape</h3>
                  <button class="btn-add" @click="data.V.etapes.push({description:'',acteur:'',document:'',controle:'',conforme:'',observation:'',preuve:''})">
                    <i class="ti ti-plus"></i> Étape
                  </button>
                </div>
                <div class="ot-tbl-wrap">
                  <table class="ot-tbl" style="min-width:860px">
                    <thead>
                      <tr>
                        <th style="width:34px" class="tc">N°</th>
                        <th style="min-width:150px">Description de l'activité</th>
                        <th style="width:95px">Acteur</th>
                        <th style="width:95px">Document/Système</th>
                        <th style="width:72px;text-align:center">Contrôle</th>
                        <th style="width:80px;text-align:center">Conforme</th>
                        <th style="min-width:130px">Observation/Écart</th>
                        <th style="width:88px">Preuve</th>
                        <th style="width:28px"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="!data.V.etapes.length">
                        <td colspan="9" class="td-empty">Aucune étape — démarrez le cheminement</td>
                      </tr>
                      <tr v-for="(e,ei) in data.V.etapes" :key="ei" :class="ei%2===0?'':'tr-alt'">
                        <td class="tc muted">{{ ei+1 }}</td>
                        <td><textarea class="ot-ta-sm" v-model="e.description" rows="2" placeholder="Décrire l'activité…"></textarea></td>
                        <td><input type="text" class="ot-inp-sm" v-model="e.acteur" placeholder="Poste/Nom" /></td>
                        <td><input type="text" class="ot-inp-sm" v-model="e.document" placeholder="ERP, bon…" /></td>
                        <td class="tc" style="padding:3px">
                          <select class="ot-sel-xs" v-model="e.controle"
                                  :class="e.controle==='Oui'?'sel-ok':e.controle==='Non'?'sel-ko':''">
                            <option value="">—</option><option>Oui</option><option>Non</option><option>N/A</option>
                          </select>
                        </td>
                        <td class="tc" style="padding:3px">
                          <select class="ot-sel-xs" v-model="e.conforme"
                                  :class="e.conforme==='Oui'?'sel-ok':e.conforme==='Non'?'sel-ko':e.conforme==='Ecart'?'sel-warn':''">
                            <option value="">—</option>
                            <option value="Oui">✅ Oui</option>
                            <option value="Non">❌ Non</option>
                            <option value="Ecart">⚠️ Écart</option>
                          </select>
                        </td>
                        <td><textarea class="ot-ta-sm" v-model="e.observation" rows="2"></textarea></td>
                        <td><input type="text" class="ot-inp-sm" v-model="e.preuve" placeholder="Réf. pièce…" /></td>
                        <td class="tc"><button class="btn-del" @click="data.V.etapes.splice(ei,1)">×</button></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="ot-section">
                <h3 class="ot-ttl">Questions de vérification IFACI</h3>
                <div class="qlist">
                  <div v-for="(q,qi) in QIFACI" :key="qi" class="q-item">
                    <div class="q-num">{{ qi+1 }}</div>
                    <div class="q-body">
                      <p class="q-txt">{{ q }}</p>
                      <div class="ot-grid">
                        <div class="ot-field">
                          <select class="ot-sel" v-model="data.V.reponses_verification[qi].statut">
                            <option value="">— Réponse —</option>
                            <option value="oui">✅ Oui</option>
                            <option value="non">❌ Non</option>
                            <option value="partiel">⚠️ Partiel</option>
                            <option value="na">N/A</option>
                          </select>
                        </div>
                        <div class="ot-field ot-fw">
                          <textarea class="ot-ta-sm" v-model="data.V.reponses_verification[qi].commentaire" rows="2" placeholder="Commentaire…"></textarea>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="ot-section">
                <div class="ot-grid">
                  <div class="ot-field">
                    <label class="ot-lbl">Synthèse des écarts</label>
                    <textarea class="ot-ta" v-model="data.V.synthese_ecarts" rows="4" placeholder="Principaux écarts identifiés…"></textarea>
                  </div>
                  <div class="ot-field">
                    <label class="ot-lbl">Conclusion générale</label>
                    <textarea class="ot-ta" v-model="data.V.conclusion" rows="4" placeholder="Le contrôle interne est…"></textarea>
                  </div>
                </div>
              </div>
            </template>

          </div><!-- /drawer-body -->

          <!-- Footer sticky -->
          <div class="drawer-footer">
            <span class="drawer-footer-hint"><i class="ti ti-info-circle"></i> Enregistrer sauvegarde aussi la fiche de test.</span>
            <div style="display:flex;gap:.5rem">
              <button class="btn btn-ghost btn-sm" @click="$emit('close')">Fermer</button>
              <button class="btn btn-drawer-save btn-sm" :disabled="saving" @click="$emit('save')">
                <span v-if="saving" class="spin-s"></span>
                <i v-else class="ti ti-device-floppy"></i> Enregistrer
              </button>
            </div>
          </div>

        </div><!-- /ft-drawer -->
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

const props = defineProps<{
  visible: boolean
  outilCode: string
  objNum: string
  testRef: string
  procIdx: number | null
  missionLibelle?: string
  auditeurNom?: string
  outilsIfaci?: { code: string; label: string; icon: string; color: string }[]
  data: any
  saving?: boolean
  importLoading?: boolean
  isLocked?: boolean
}>()

defineEmits(['close', 'save', 'import'])

const currentOutil = computed(() => props.outilsIfaci?.find(o => o.code === props.outilCode) ?? null)

// Outil II — acteurs
const ACOLORS = ['#1e40af','#065f46','#6d28d9','#b45309','#be185d','#0f172a','#047857','#7c3aed']
function retirerActeur(ai: number) {
  props.data.II.acteurs.splice(ai, 1)
  props.data.II.taches.forEach((t: any) => { if (t.roles) t.roles.splice(ai, 1) })
}

// Outil III — symboles
const SYMBOLES = [
  { nom: 'Traitement', icon: '□', color: '#6d28d9' },
  { nom: 'Décision',   icon: '◇', color: '#1e40af' },
  { nom: 'Document',   icon: '⌐', color: '#065f46' },
  { nom: 'Saisie man.',icon: '⌂', color: '#b45309' },
  { nom: 'Données',    icon: '⊞', color: '#be185d' },
  { nom: 'Renvoi',     icon: '○', color: '#0f172a' },
  { nom: 'Flux',       icon: '→', color: '#7c3aed' },
]

// Outil IV — types de processus
const activeTabIV = ref('realisation')
const PTYPES = [
  { code: 'realisation', label: '1. Réalisation', desc: 'Processus produisant des produits/services', color: '#065f46' },
  { code: 'management',  label: '2. Management',  desc: 'Processus de pilotage et direction',         color: '#1e40af' },
  { code: 'support',     label: '3. Support',      desc: 'Processus de soutien',                       color: '#6d28d9' },
]
function addProc(type: string) {
  if (!props.data.IV.processus[type]) props.data.IV.processus[type] = []
  props.data.IV.processus[type].push({ nom: '', finalite: '', entrants: '', sortants: '', activites: '', clients: '', fournisseurs: '', contrats: '' })
}

// Outil V — questions IFACI
const QIFACI = [
  'Le processus fonctionne-t-il tel que décrit dans le diagramme de flux ?',
  'De quelle façon les contrôles sont-ils supposés fonctionner ?',
  'Quels sont les objectifs de contrôle identifiés ?',
  'Les contrôles sont-ils opérationnels ?',
  'La conception du contrôle permet-elle de répondre aux objectifs de contrôle ?',
]
</script>

<style scoped>
/* ── Overlay & Panel ─────────────────────────────────────────── */
.ft-drawer-overlay { position: fixed; inset: 0; z-index: 1050; display: flex; justify-content: flex-end; }
.ft-drawer-scrim   { flex: 1; background: rgba(15,23,42,.4); }
.ft-drawer { width: min(720px,96vw); height: 100vh; display: flex; flex-direction: column; background: #fff; box-shadow: -6px 0 30px rgba(0,0,0,.18); }

/* ── Header ─────────────────────────────────────────────────── */
.drawer-hdr { display: flex; align-items: center; justify-content: space-between; padding: .7rem 1rem; background: var(--dc,#0f172a); flex-shrink: 0; gap: .75rem; }
.drawer-hdr__left { display: flex; align-items: center; gap: .6rem; flex: 1; min-width: 0; }
.drawer-code { display: inline-flex; align-items: center; justify-content: center; min-width: 32px; height: 28px; background: rgba(255,255,255,.2); color: #fff; border-radius: 5px; font-size: .8rem; font-weight: 700; font-family: monospace; flex-shrink: 0; }
.drawer-title { font-size: .84rem; font-weight: 700; color: #fff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.drawer-sub { font-size: .6rem; color: rgba(255,255,255,.72); margin-top: 1px; }
.drawer-hdr__right { display: flex; align-items: center; gap: .4rem; flex-shrink: 0; }

/* ── Contexte ────────────────────────────────────────────────── */
.drawer-ctx { display: flex; gap: .75rem; padding: .4rem 1rem; background: #f8fafc; border-bottom: 1px solid #e5e7eb; flex-wrap: wrap; font-size: .66rem; color: #64748b; flex-shrink: 0; }
.ctx-proc { color: #7c3aed; font-weight: 600; }

/* ── Body ────────────────────────────────────────────────────── */
.drawer-body { flex: 1; overflow-y: auto; padding: 1rem; }
.drawer-body::-webkit-scrollbar { width: 4px; }
.drawer-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 2px; }

/* ── Footer ─────────────────────────────────────────────────── */
.drawer-footer { display: flex; align-items: center; justify-content: space-between; padding: .55rem 1rem; background: #f8fafc; border-top: 1px solid #e5e7eb; flex-shrink: 0; }
.drawer-footer-hint { font-size: .64rem; color: #94a3b8; }

/* ── Section ─────────────────────────────────────────────────── */
.ot-section { margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid #e5e7eb; }
.ot-section:last-child { border-bottom: none; margin-bottom: 0; }
.ot-ttl { font-size: .74rem; font-weight: 700; color: #111827; margin: 0 0 .6rem; text-transform: uppercase; letter-spacing: .04em; }
.ot-bar { display: flex; align-items: center; justify-content: space-between; margin-bottom: .6rem; }

/* ── Grid ────────────────────────────────────────────────────── */
.ot-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; }
.ot-field { display: flex; flex-direction: column; gap: .25rem; }
.ot-fw { grid-column: 1 / -1; }
.ot-lbl { font-size: .66rem; font-weight: 600; color: #6b7280; }

/* ── Controls ────────────────────────────────────────────────── */
.ot-inp    { width: 100%; border: 1px solid #e5e7eb; border-radius: 5px; padding: 5px 8px; font-size: .75rem; color: #111827; background: #fff; outline: none; font-family: inherit; }
.ot-inp:focus { border-color: #93c5fd; }
.ot-ta     { width: 100%; border: 1px solid #e5e7eb; border-radius: 5px; padding: 5px 8px; font-size: .73rem; color: #111827; background: #fff; outline: none; resize: vertical; font-family: inherit; }
.ot-ta:focus { border-color: #93c5fd; }
.ot-inp-sm { width: 100%; border: 1px solid #e5e7eb; border-radius: 4px; padding: 3px 5px; font-size: .68rem; color: #111827; background: #fff; outline: none; font-family: inherit; }
.ot-ta-sm  { width: 100%; border: 1px solid #e5e7eb; border-radius: 4px; padding: 3px 5px; font-size: .68rem; color: #111827; background: #fff; outline: none; resize: vertical; font-family: inherit; }
.ot-sel    { width: 100%; border: 1px solid #e5e7eb; border-radius: 4px; padding: 3px 5px; font-size: .7rem; color: #111827; background: #fff; outline: none; cursor: pointer; font-family: inherit; }
.ot-sel-xs { width: 100%; border: 1px solid #e5e7eb; border-radius: 4px; padding: 2px 3px; font-size: .62rem; color: #111827; background: #fff; outline: none; cursor: pointer; font-family: inherit; }
.sel-ok   { color: #065f46; font-weight: 700; background: #f0fdf4; }
.sel-ko   { color: #dc2626; font-weight: 700; background: #fef2f2; }
.sel-warn { color: #d97706; font-weight: 700; background: #fffbeb; }

/* ── Table ───────────────────────────────────────────────────── */
.ot-tbl-wrap { overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 6px; }
.ot-tbl { width: 100%; border-collapse: collapse; font-size: .7rem; }
.ot-tbl thead th { padding: .35rem .5rem; background: #1e293b; color: rgba(255,255,255,.88); font-weight: 700; border-bottom: 1px solid #334155; border-right: 1px solid #334155; white-space: nowrap; font-size: .6rem; text-transform: uppercase; letter-spacing: .04em; }
.ot-tbl tbody td { padding: .3rem .4rem; border-bottom: 1px solid #f3f4f6; border-right: 1px solid #f3f4f6; vertical-align: middle; }
.ot-tbl tbody tr:last-child td { border-bottom: none; }
.td-empty { text-align: center; color: #94a3b8; padding: 1rem; font-style: italic; }
.tc { text-align: center; }
.muted { font-weight: 700; color: #9ca3af; font-size: .65rem; }
.tr-alt { background: #fafbfc; }

/* ── Acteurs (Outil II) ──────────────────────────────────────── */
.acteurs-row { display: flex; flex-wrap: wrap; gap: .45rem; }
.acteur-item { display: flex; align-items: center; gap: .3rem; }
.acteur-bdg  { display: inline-flex; align-items: center; justify-content: center; min-width: 24px; height: 20px; border-radius: 4px; color: #fff; font-size: .62rem; font-weight: 700; flex-shrink: 0; }
.legend { font-size: .62rem; color: #94a3b8; margin: .35rem 0 0; }

/* ── Symboles ISO (Outil III) ────────────────────────────────── */
.sym-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(100px,1fr)); gap: .4rem; }
.sym-item { display: flex; align-items: center; gap: .4rem; padding: .35rem .55rem; border: 1px solid #e5e7eb; border-radius: 6px; background: #faf5ff; }
.sym-icon { display: flex; align-items: center; justify-content: center; width: 26px; height: 20px; border: 2px solid currentColor; border-radius: 3px; font-size: .72rem; font-weight: 700; flex-shrink: 0; }
.sym-lbl  { font-size: .6rem; color: #475569; font-weight: 500; }

/* ── Tabs (Outil IV) ─────────────────────────────────────────── */
.ot-tabs { display: flex; gap: 2px; border-bottom: 2px solid #e5e7eb; margin-bottom: 1rem; }
.ot-tab { padding: .38rem .75rem; background: none; border: none; border-bottom: 2px solid transparent; font-size: .7rem; font-weight: 600; color: #6b7280; cursor: pointer; margin-bottom: -2px; transition: all .15s; font-family: inherit; }
.ot-tab--active { border-bottom-color: var(--tc,#1e40af); color: var(--tc,#1e40af); }

/* ── Questions IFACI (Outil V) ───────────────────────────────── */
.qlist  { display: flex; flex-direction: column; gap: .7rem; }
.q-item { display: flex; align-items: flex-start; gap: .55rem; padding: .55rem .75rem; border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; }
.q-num  { display: flex; align-items: center; justify-content: center; min-width: 22px; height: 22px; background: #fce7f3; color: #be185d; border-radius: 50%; font-size: .65rem; font-weight: 700; flex-shrink: 0; }
.q-body { flex: 1; }
.q-txt  { font-size: .71rem; font-weight: 600; color: #111827; margin: 0 0 .45rem; }

/* ── Buttons ─────────────────────────────────────────────────── */
.btn { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 6px; font-size: .78rem; font-weight: 600; border: none; cursor: pointer; font-family: inherit; transition: all .15s; }
.btn:disabled { opacity: .45; cursor: not-allowed; }
.btn-sm { padding: 4px 9px; font-size: .72rem; }
.btn-ghost { background: #fff; color: #374151; border: 1px solid #e5e7eb; }
.btn-ghost:hover:not(:disabled) { background: #f9fafb; }
.btn-drawer-save { background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.3); color: #fff; }
.btn-drawer-save:hover:not(:disabled) { background: rgba(255,255,255,.28); }
.btn-close-x  { background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2); color: #fff; padding: 4px 8px; }
.btn-import   { background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.25); color: #fff; cursor: pointer; }
.btn-add { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; border-radius: 5px; font-size: .68rem; font-weight: 600; cursor: pointer; font-family: inherit; }
.btn-add:hover { background: #dbeafe; }
.btn-del    { background: #fee2e2; border: 1px solid #fecaca; color: #dc2626; border-radius: 4px; cursor: pointer; font-size: .65rem; padding: 2px 5px; }
.btn-del:hover { background: #fecaca; }
.btn-del-sm { background: none; border: 1px solid #e5e7eb; color: #94a3b8; border-radius: 4px; cursor: pointer; font-size: .62rem; padding: 0; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; }
.btn-del-sm:hover { color: #dc2626; border-color: #fecaca; background: #fef2f2; }
.hidden { display: none; }

/* ── Spinner ─────────────────────────────────────────────────── */
.spin-s { width: 10px; height: 10px; border-radius: 50%; border: 2px solid currentColor; border-top-color: transparent; animation: spin .6s linear infinite; display: inline-block; }
@keyframes spin { to { transform: rotate(360deg) } }

/* ── Transitions ─────────────────────────────────────────────── */
.drawer-slide-enter-active,.drawer-slide-leave-active { transition: all .28s cubic-bezier(.4,0,.2,1); }
.drawer-slide-enter-from,.drawer-slide-leave-to { opacity: 0; }
.drawer-slide-enter-from .ft-drawer,.drawer-slide-leave-to .ft-drawer { transform: translateX(100%); }
</style>