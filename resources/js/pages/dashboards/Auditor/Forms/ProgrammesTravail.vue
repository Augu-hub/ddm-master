<template>
  <VerticalLayoutAudit>
    <div class="pta-shell">

      <!-- ═══ HEADER ═══ -->
      <header class="pta-header">
        <div class="pta-hrow">
          <a :href="props.backUrl" class="pta-back"><i class="ti ti-arrow-left"></i></a>
          <div class="pta-hinfo">
            <div class="pta-chips">
              <code class="pta-code">{{ form.code || 'PTA-AUTO' }}</code>
              <span class="pta-chip" :class="`chip-${form.validation_status||'draft'}`">
                <i :class="vstIcon(form.validation_status||'draft')"></i>
                {{ vstLbl(form.validation_status||'draft') }}
              </span>
              <span class="pta-chip chip-type">Programme de Travail</span>
              <span v-if="props.auditorRole" class="pta-chip" :class="`chip-role-${props.auditorRole}`">
                {{ props.auditorRole }}
              </span>
            </div>
            <h1 class="pta-title">Programme de Travail d'Audit — Contrôle Interne</h1>
            <div class="pta-meta">
              <span v-if="mission?.code_mission"><i class="ti ti-clipboard"></i>{{ mission.code_mission }}</span>
              <span v-if="mission?.libelle"><i class="ti ti-file-description"></i>{{ mission.libelle }}</span>
              <span class="meta-tot"><i class="ti ti-table"></i>{{ lignes.length }} ligne(s)</span>
              <span class="meta-ret"><i class="ti ti-check-circle"></i>{{ lignes.filter(l=>l._retenu).length }} retenu(s)</span>
              <span class="meta-rci" v-if="hasDonneesRCI">
                <i class="ti ti-link"></i>{{ props.donneesRCI?.total }} ligne(s) RCI source
              </span>
            </div>
          </div>
        </div>
        <!-- Banners statut -->
        <div v-if="form.validation_status==='validated'" class="pta-banner banner-lock">
          <i class="ti ti-lock"></i> Programme <strong>validé définitivement</strong> — lecture seule
        </div>
        <div v-else-if="form.validation_status==='in_review'" class="pta-banner banner-review">
          <i class="ti ti-clock"></i> Soumis pour validation
          <span v-if="canManage"> · DM/CM peut valider ou rejeter.</span>
        </div>
        <div v-else-if="form.validation_status==='draft'&&form.validation_note" class="pta-banner banner-reject">
          <i class="ti ti-circle-x"></i> Rejeté — <em>{{ form.validation_note }}</em>
        </div>
      </header>

      <!-- ═══ BODY ═══ -->
      <div class="pta-body">

        <!-- Info liaison RCI -->
        <div class="rci-link-info">
          <i class="ti ti-info-circle"></i>
          <span>
            Les colonnes <strong>Test d'Audit</strong> et <strong>Procédures d'Audit</strong>
            sont pré-remplies depuis le RCI (colonnes <em>Description du Contrôle</em> et
            <em>Preuve du Contrôle</em>). Elles restent éditables.
          </span>
        </div>

        <!-- ── Barre d'outils ── -->
        <div class="pta-toolbar">
          <div class="tb-left">
            <div class="tb-filters">
              <button :class="['tbf', filtreRetenu===null?'tbf-all':'']" @click="filtreRetenu=null">
                Tous <span class="tbf-cnt">{{ lignes.length }}</span>
              </button>
              <button :class="['tbf tbf-ret', filtreRetenu===true?'tbf-active':'']" @click="filtreRetenu=true">
                <i class="ti ti-check-circle"></i> Retenus <span class="tbf-cnt">{{ lignes.filter(l=>l._retenu).length }}</span>
              </button>
              <button :class="['tbf tbf-nret', filtreRetenu===false?'tbf-active':'']" @click="filtreRetenu=false">
                Non retenus <span class="tbf-cnt">{{ lignes.filter(l=>!l._retenu).length }}</span>
              </button>
            </div>
            <div class="tb-search">
              <i class="ti ti-search"></i>
              <input v-model="search" class="search-inp" placeholder="Objectif, contrôle, auditeur…"/>
              <button v-if="search" class="search-clear" @click="search=''"><i class="ti ti-x"></i></button>
            </div>
          </div>
          <div class="tb-right">
            <div v-if="hasDonneesRCI" class="rci-badge">
              <i class="ti ti-link"></i>
              <span>{{ props.donneesRCI?.total }} ligne(s) RCI</span>
              <button v-if="!lignes.length && !isLocked" class="btn btn-import btn-xs" @click="importerTout">
                <i class="ti ti-download"></i> Importer depuis RCI
              </button>
              <button v-else-if="!isLocked" class="btn btn-ghost btn-xs" @click="rafraichirRCI">
                <i class="ti ti-refresh"></i>
              </button>
            </div>
            <button v-if="!isLocked" class="btn btn-add btn-sm" @click="ajouterLigne">
              <i class="ti ti-plus"></i> Ajouter
            </button>
          </div>
        </div>

        <!-- Alerte pas de données RCI -->
        <div v-if="!hasDonneesRCI" class="alert-info">
          <i class="ti ti-alert-triangle"></i>
          Aucun RCI disponible pour cette phase. Complétez d'abord le
          <strong>Référentiel de Contrôle Interne</strong> pour générer automatiquement
          ce programme de travail.
        </div>

        <!-- ══════════════════════════════════════════════════
             TABLEAU PTA — 16 colonnes (conforme Excel)
             ══════════════════════════════════════════════════ -->
        <div class="tbl-container">
          <div v-if="!filteredLignes.length" class="empty-state">
            <i class="ti ti-table-off"></i>
            <p>Aucune ligne de programme.<br/>Importez depuis le RCI ou ajoutez manuellement.</p>
          </div>

          <div v-else class="tbl-scroll-wrap">
            <table class="pta-tbl">
              <thead>
                <!-- Ligne titre -->
                <tr class="head-doc">
                  <th colspan="17" class="doc-titre">
                    PROGRAMME DE TRAVAIL D'AUDIT — CONTRÔLE INTERNE
                  </th>
                </tr>
                <!-- Groupes de colonnes -->
                <tr class="head-grp">
                  <th class="hg-num" rowspan="2">N°</th>
                  <th colspan="3" class="hg-ident">
                    IDENTIFICATION
                  </th>
                  <th colspan="2" class="hg-tests">
                    TESTS &amp; PROCÉDURES
                    <span class="hg-note">(⬤ = lien → RCI)</span>
                  </th>
                  <th colspan="6" class="hg-plan">PLANIFICATION / RESSOURCES</th>
                  <th colspan="4" class="hg-res">RÉSULTATS</th>
                  <th class="hg-ref" rowspan="2">Réf.<br/>PT</th>
                  <th v-if="!isLocked" class="hg-act" rowspan="2">⚙</th>
                </tr>
                <tr class="head-col">
                  <!-- Identification -->
                  <th class="hc hc-ref">Réf.<br/><span class="hs">Objectif</span></th>
                  <th class="hc hc-obj">Objectif d'Audit<br/><span class="hs">Ce qu'on vérifie</span></th>
                  <th class="hc hc-rci">Réf.<br/>Contrôle RCI<br/><span class="hs">Lien RCI</span></th>
                  <!-- Tests (liés RCI) -->
                  <th class="hc hc-test">
                    Test d'Audit
                    <span class="link-badge">→ RCI col. H</span>
                    <br/><span class="hs">Description contrôle</span>
                  </th>
                  <th class="hc hc-proc">
                    Procédures d'Audit
                    <span class="link-badge">→ RCI col. J</span>
                    <br/><span class="hs">Preuve / Articles</span>
                  </th>
                  <!-- Planification -->
                  <th class="hc hc-ech">Taille<br/>Échantillon</th>
                  <th class="hc hc-per">Période<br/>testée</th>
                  <th class="hc hc-aud">Auditeur<br/>Responsable</th>
                  <th class="hc hc-dd">Date<br/>Début</th>
                  <th class="hc hc-df">Date<br/>Fin</th>
                  <th class="hc hc-lieu">Lieu /<br/>Local</th>
                  <!-- Résultats -->
                  <th class="hc hc-res">Résultat<br/>du Test</th>
                  <th class="hc hc-anom">Nb<br/>Anomalies</th>
                  <th class="hc hc-tc">Taux<br/>Conformité</th>
                  <th class="hc hc-concl">Conclusion<br/>Auditeur</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, idx) in filteredLignes" :key="row._uid"
                    :class="['pta-row', row._retenu?'row-ret':'row-nret', resultatClasse(row.resultat_test)]">

                  <!-- N° + toggle retenu -->
                  <td class="td-num">
                    <div class="num-wrap">
                      <span class="num-val">{{ row.num }}</span>
                      <span v-if="!isLocked" class="ret-toggle"
                            :title="row._retenu?'Retenu':'Non retenu'"
                            @click="row._retenu=!row._retenu">
                        <i class="ti" :class="row._retenu?'ti-circle-check ret-on':'ti-circle ret-off'"></i>
                      </span>
                      <span v-else>
                        <i class="ti" :class="row._retenu?'ti-circle-check ret-on':'ti-circle ret-off'"></i>
                      </span>
                    </div>
                  </td>

                  <!-- A : Réf. Objectif -->
                  <td class="td-ref">
                    <div class="cell-v">
                      <span v-if="isLocked" class="ref-badge">{{ row.ref_objectif }}</span>
                      <input v-else class="c-inp c-inp-sm" v-model="row.ref_objectif"
                             placeholder="O1.1" @change="row._edited=true"/>
                    </div>
                  </td>

                  <!-- B : Objectif d'Audit -->
                  <td class="td-obj">
                    <textarea v-if="!isLocked" class="c-ta" v-model="row.objectif_audit"
                              rows="2" placeholder="S'assurer que…" @change="row._edited=true"></textarea>
                    <span v-else class="cv-main small">{{ row.objectif_audit||'—' }}</span>
                  </td>

                  <!-- C : Réf. Contrôle RCI -->
                  <td class="td-rci">
                    <div class="cell-v">
                      <span class="tag tag-rci">{{ row.ref_controle_rci || '—' }}</span>
                      <input v-if="!isLocked" class="c-inp c-inp-xs" v-model="row.ref_controle_rci"
                             placeholder="C-XXX-01" @change="row._edited=true"/>
                    </div>
                  </td>

                  <!-- D : Test d'Audit (lié RCI col. H) -->
                  <td class="td-test">
                    <div class="lien-rci-wrap">
                      <span class="lien-dot" title="Lié au RCI col. Description du Contrôle">⬤</span>
                      <textarea v-if="!isLocked" class="c-ta c-ta-lg" v-model="row.test_audit"
                                rows="3" placeholder="Procédure de contrôle…"
                                @change="row._edited=true"></textarea>
                      <p v-else class="cv-main small pre">{{ row.test_audit||'—' }}</p>
                    </div>
                  </td>

                  <!-- E : Procédures d'Audit (lié RCI col. J) -->
                  <td class="td-proc">
                    <div class="lien-rci-wrap">
                      <span class="lien-dot lien-dot-j" title="Lié au RCI col. Preuve du Contrôle">⬤</span>
                      <textarea v-if="!isLocked" class="c-ta c-ta-lg" v-model="row.procedures_audit"
                                rows="3" placeholder="Articles, actes, décisions…"
                                @change="row._edited=true"></textarea>
                      <p v-else class="cv-main small pre">{{ row.procedures_audit||'—' }}</p>
                    </div>
                  </td>

                  <!-- F : Taille Échantillon -->
                  <td class="td-ech">
                    <input v-if="!isLocked" class="c-inp c-inp-sm" v-model="row.taille_echantillon"
                           placeholder="30, Exhaustif…" @change="row._edited=true"/>
                    <span v-else class="cv-main small">{{ row.taille_echantillon||'—' }}</span>
                  </td>

                  <!-- G : Période testée -->
                  <td class="td-per">
                    <input v-if="!isLocked" class="c-inp c-inp-sm" v-model="row.periode_testee"
                           placeholder="Jan–Mar 2024" @change="row._edited=true"/>
                    <span v-else class="cv-main small">{{ row.periode_testee||'—' }}</span>
                  </td>

                  <!-- H : Auditeur Responsable -->
                  <td class="td-aud">
                    <template v-if="!isLocked">
                      <select v-if="auditeurOptions.length" class="c-sel" v-model="row.auditeur_responsable"
                              @change="row._edited=true">
                        <option value="">— Sélectionner —</option>
                        <option v-for="a in auditeurOptions" :key="a.id" :value="a.full_name">
                          {{ a.full_name }} ({{ a.role_code }})
                        </option>
                      </select>
                      <input v-else class="c-inp" v-model="row.auditeur_responsable"
                             placeholder="Nom auditeur…" @change="row._edited=true"/>
                    </template>
                    <span v-else class="cv-main small">{{ row.auditeur_responsable||'—' }}</span>
                  </td>

                  <!-- I : Date Début -->
                  <td class="td-dd">
                    <input v-if="!isLocked" class="c-inp c-inp-sm" type="date" v-model="row.date_debut"
                           @change="row._edited=true"/>
                    <span v-else class="cv-main small">{{ formatDate(row.date_debut) }}</span>
                  </td>

                  <!-- J : Date Fin -->
                  <td class="td-df">
                    <input v-if="!isLocked" class="c-inp c-inp-sm" type="date" v-model="row.date_fin"
                           @change="row._edited=true"/>
                    <span v-else class="cv-main small">{{ formatDate(row.date_fin) }}</span>
                  </td>

                  <!-- K : Lieu / Local -->
                  <td class="td-lieu">
                    <input v-if="!isLocked" class="c-inp" v-model="row.lieu_local"
                           placeholder="Bureau, entrepôt…" @change="row._edited=true"/>
                    <span v-else class="cv-main small">{{ row.lieu_local||'—' }}</span>
                  </td>

                  <!-- L : Résultat du Test -->
                  <td class="td-res">
                    <select v-if="!isLocked" class="c-sel" v-model="row.resultat_test"
                            @change="row._edited=true">
                      <option value="">—</option>
                      <option v-for="r in RESULTATS" :key="r" :value="r">{{ r }}</option>
                    </select>
                    <span v-else>
                      <span v-if="row.resultat_test" :class="['res-badge', resKey(row.resultat_test)]">
                        {{ row.resultat_test }}
                      </span>
                      <span v-else class="cv-empty">—</span>
                    </span>
                  </td>

                  <!-- M : Nb Anomalies -->
                  <td class="td-anom">
                    <input v-if="!isLocked" class="c-num" type="number" min="0"
                           v-model.number="row.nb_anomalies"
                           @input="calcTaux(row)" @change="row._edited=true"/>
                    <span v-else :class="['anom-val', row.nb_anomalies>0?'anom-pos':'']">
                      {{ row.nb_anomalies ?? '—' }}
                    </span>
                  </td>

                  <!-- N : Taux Conformité (calculé) -->
                  <td class="td-tc">
                    <div class="taux-wrap">
                      <span :class="['taux-badge', tauxClasse(row.taux_conformite)]">
                        {{ formatTaux(row.taux_conformite) }}
                      </span>
                    </div>
                  </td>

                  <!-- O : Conclusion Auditeur -->
                  <td class="td-concl">
                    <textarea v-if="!isLocked" class="c-ta" v-model="row.conclusion_auditeur"
                              rows="2" placeholder="Observations finales…"
                              @change="row._edited=true"></textarea>
                    <span v-else class="cv-main small">{{ row.conclusion_auditeur||'—' }}</span>
                  </td>

                  <!-- P : Référence Papier de Travail -->
                  <td class="td-ptref">
                    <span v-if="isLocked" class="ref-badge">{{ row.ref_papier_travail||'—' }}</span>
                    <input v-else class="c-inp c-inp-sm" v-model="row.ref_papier_travail"
                           placeholder="PT-XXX-01" @change="row._edited=true"/>
                  </td>

                  <!-- Actions -->
                  <td v-if="!isLocked" class="td-act-btn">
                    <button class="ibtn ibtn-del" @click="supprimerLigne(idx)" title="Supprimer">
                      <i class="ti ti-trash"></i>
                    </button>
                  </td>

                </tr>
              </tbody>
            </table>
          </div>

          <!-- Légende -->
          <div class="res-legend">
            <span class="leg-item"><span class="res-badge res-conf">Conforme</span></span>
            <span class="leg-item"><span class="res-badge res-nonconf">Non conforme</span></span>
            <span class="leg-item"><span class="res-badge res-part">Partiel</span></span>
            <span class="leg-item"><span class="res-badge res-na">N/A</span></span>
            <span class="leg-sep">|</span>
            <span class="leg-item"><i class="ti ti-circle-check ret-on"></i> Retenu dans l'audit</span>
            <span class="leg-item"><i class="ti ti-circle ret-off"></i> Non retenu</span>
          </div>
        </div>

        <!-- ── Statistiques rapides ── -->
        <div v-if="lignes.length" class="stats-bar">
          <div class="stat-card">
            <span class="sc-val">{{ lignes.length }}</span>
            <span class="sc-lbl">Total lignes</span>
          </div>
          <div class="stat-card">
            <span class="sc-val sc-green">{{ nbConformes }}</span>
            <span class="sc-lbl">Conformes</span>
          </div>
          <div class="stat-card">
            <span class="sc-val sc-red">{{ nbNonConformes }}</span>
            <span class="sc-lbl">Non conformes</span>
          </div>
          <div class="stat-card">
            <span class="sc-val sc-orange">{{ totalAnomalies }}</span>
            <span class="sc-lbl">Anomalies</span>
          </div>
          <div class="stat-card stat-taux">
            <span class="sc-val" :class="tauxGlobalClasse">{{ formatTaux(tauxGlobal) }}</span>
            <span class="sc-lbl">Taux global</span>
          </div>
        </div>

        <!-- ── Synthèse ── -->
        <div class="card-section">
          <div class="cs-hdr"><i class="ti ti-report-analytics"></i> Synthèse globale</div>
          <div class="cs-body">
            <textarea class="rinp rinp-ta" v-model="form.synthese" :disabled="isLocked"
                      rows="3"
                      placeholder="Observations générales sur le programme de travail d'audit…"></textarea>
          </div>
        </div>

        <!-- ── Sources normatives ── -->
        <div class="card-section" v-if="!isLocked || sources_normatives.length">
          <div class="cs-hdr">
            <i class="ti ti-book-2"></i> Sources Normatives
            <button v-if="!isLocked" class="btn btn-xs btn-ghost ml-auto"
                    @click="sources_normatives.push({norme:'',version:'',articles_applicables:''})">
              <i class="ti ti-plus"></i> Ajouter
            </button>
          </div>
          <div class="cs-body">
            <div v-if="!sources_normatives.length" class="empty-sm">Aucune source normative</div>
            <div v-for="(s, i) in sources_normatives" :key="i" class="norm-row">
              <template v-if="!isLocked">
                <input class="rinp rinp-nrm" v-model="s.norme" placeholder="Norme (ex: IIA, INTOSAI…)"/>
                <input class="rinp rinp-sm" v-model="s.version" placeholder="Version"/>
                <input class="rinp" v-model="s.articles_applicables" placeholder="Articles applicables…"/>
                <button class="ibtn ibtn-del" @click="sources_normatives.splice(i,1)">
                  <i class="ti ti-x"></i>
                </button>
              </template>
              <span v-else class="norm-ro">
                <strong>{{ s.norme }}</strong> {{ s.version }} — {{ s.articles_applicables }}
              </span>
            </div>
          </div>
        </div>

        <!-- ── Signatures ── -->
        <div class="card-section">
          <div class="cs-hdr"><i class="ti ti-pen"></i> Signatures</div>
          <div class="cs-body cs-sign">
            <div class="sfg">
              <label class="slbl">Fait par</label>
              <input class="rinp" v-model="form.fait_par" :disabled="isLocked"/>
            </div>
            <div class="sfg">
              <label class="slbl">Revu par</label>
              <input class="rinp" v-model="form.revue_par" :disabled="isLocked"/>
            </div>
          </div>
        </div>

        <!-- ── FOOTER ── -->
        <footer class="pta-footer">
          <div class="footer-left">
            <button v-if="!isLocked" type="button" class="btn btn-ghost btn-sm"
                    :disabled="processing" @click="annuler">
              <i class="ti ti-x"></i> Annuler
            </button>
            <button v-if="!isLocked" type="button" class="btn btn-save btn-sm"
                    :disabled="processing" @click="submit">
              <span v-if="processing" class="spin-s"></span>
              <i v-else class="ti ti-device-floppy"></i>
              {{ form.id ? 'Mettre à jour' : 'Enregistrer' }}
            </button>
          </div>
          <div class="footer-mid">
            <span v-if="form.id" class="saved-code"><i class="ti ti-check"></i> {{ form.code }}</span>
            <span class="stat-lbl">
              {{ lignes.filter(l=>l._retenu).length }}/{{ lignes.length }} lignes retenues
            </span>
          </div>
          <div class="footer-right">
            <button v-if="form.id&&form.validation_status==='draft'" type="button"
                    class="btn btn-sub btn-sm" :disabled="processing" @click="soumettre">
              <i class="ti ti-send"></i> Soumettre pour validation
            </button>
            <template v-if="canManage&&form.validation_status==='in_review'">
              <button type="button" class="btn btn-ok btn-sm" :disabled="processing"
                      @click="valider('validate')">
                <i class="ti ti-circle-check"></i> Valider
              </button>
              <button type="button" class="btn btn-rej btn-sm" :disabled="processing"
                      @click="promptReject">
                <i class="ti ti-circle-x"></i> Rejeter
              </button>
            </template>
          </div>
        </footer>
      </div>

    </div>

    <!-- Toast -->
    <Teleport to="body">
      <Transition name="toast-t">
        <div v-if="toast.show" class="toast" :class="`toast--${toast.type}`">
          <i :class="toast.type==='success'?'ti ti-circle-check':'ti ti-alert-circle'"></i>
          {{ toast.msg }}
        </div>
      </Transition>
    </Teleport>
  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, computed, reactive, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'

// ── Props ──────────────────────────────────────────────────────
const props = withDefaults(defineProps<{
  mission?:any; assignment?:any; auditorRole?:string; missionId?:number; assignmentId?:number
  form?:any; ptaList?:any[]; currentAuditor?:any; phaseAuditeurs?:any[]
  donneesRCI?:  { lignes?: any[]; total?: number; source?: string }
  resultats?:   string[]
  backUrl?:string; formUrl?:string
  urlStore?:string; urlUpdate?:string; urlSoumettre?:string; urlValider?:string; urlIndex?:string
}>(), {
  ptaList:()=>[], phaseAuditeurs:()=>[],
  donneesRCI:()=>({ lignes:[], total:0, source:'none' }),
  resultats:()=>['Conforme','Non conforme','Partiellement conforme','Non applicable','En cours'],
})

const RESULTATS = props.resultats
const mission   = props.mission ?? props.form?.mission

let _uid = 0
const uid = () => String(++_uid)

// ── State ──────────────────────────────────────────────────────
const form = reactive<any>({
  id:null, code:'', validation_status:'draft', validation_note:'',
  fait_par:'', revue_par:'', synthese:'',
  ...(props.form ?? {}),
})

const lignes = reactive<any[]>(
  safeArr(props.form?.lignes).map(l => ({ ...l, _uid:uid(), _retenu:l._retenu??true }))
)

// Auto-import si aucune ligne sauvegardée
if (!lignes.length && (props.donneesRCI?.lignes?.length ?? 0) > 0) {
  props.donneesRCI!.lignes!.forEach(l => lignes.push({ ...l, _uid:uid() }))
}

const sources_normatives = reactive<any[]>(safeArr(props.form?.sources_normatives))

const processing   = ref(false)
const filtreRetenu = ref<boolean|null>(null)
const search       = ref('')

const toast = ref({show:false, type:'success', msg:''})
let _tt:any
function showToast(t:string, m:string){
  if(_tt) clearTimeout(_tt)
  toast.value={show:true,type:t,msg:m}
  _tt=setTimeout(()=>{ toast.value.show=false },4000)
}

// ── Computed ───────────────────────────────────────────────────
const canManage    = computed(()=> ['DM','CM'].includes(props.auditorRole??''))
const isLocked     = computed(()=> form.validation_status==='validated' ||
  (form.validation_status==='in_review' && !canManage.value))
const hasDonneesRCI = computed(()=> (props.donneesRCI?.lignes?.length??0) > 0)

const auditeurOptions = computed(()=> props.phaseAuditeurs ?? [])

const filteredLignes = computed(()=>{
  let list = lignes
  if (filtreRetenu.value !== null) list = list.filter(l => l._retenu === filtreRetenu.value)
  if (search.value.trim()) {
    const q = search.value.toLowerCase()
    list = list.filter(l =>
      (l.objectif_audit??'').toLowerCase().includes(q) ||
      (l.ref_controle_rci??'').toLowerCase().includes(q) ||
      (l.test_audit??'').toLowerCase().includes(q) ||
      (l.procedures_audit??'').toLowerCase().includes(q) ||
      (l.auditeur_responsable??'').toLowerCase().includes(q) ||
      (l.ref_papier_travail??'').toLowerCase().includes(q)
    )
  }
  return list
})

// Statistiques
const nbConformes    = computed(()=> lignes.filter(l=>l.resultat_test==='Conforme').length)
const nbNonConformes = computed(()=> lignes.filter(l=>l.resultat_test==='Non conforme').length)
const totalAnomalies = computed(()=> lignes.reduce((s,l)=> s + (parseInt(l.nb_anomalies)||0), 0))
const tauxGlobal     = computed(()=>{
  const avecTaux = lignes.filter(l => l.taux_conformite !== null && l.taux_conformite !== undefined)
  if (!avecTaux.length) return null
  return avecTaux.reduce((s,l)=> s + (parseFloat(l.taux_conformite)||0), 0) / avecTaux.length
})
const tauxGlobalClasse = computed(()=> tauxClasse(tauxGlobal.value))

// ── Helpers ────────────────────────────────────────────────────
function safeArr(v:any): any[]{
  if (Array.isArray(v)) return [...v]
  if (!v) return []
  try { const d=JSON.parse(v); return Array.isArray(d)?d:[] } catch{ return [] }
}
function csrf(){ return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content??'' }
function vstLbl(s:string){ return ({draft:'Brouillon',in_review:'En attente',validated:'Validé ✓'} as any)[s]??s }
function vstIcon(s:string){ return ({draft:'ti ti-pencil',in_review:'ti ti-clock',validated:'ti ti-circle-check'} as any)[s]??'ti ti-circle' }

function resultatClasse(r:string): string {
  if (r==='Conforme')              return 'row-conf'
  if (r==='Non conforme')         return 'row-nonconf'
  if (r==='Partiellement conforme') return 'row-part'
  return ''
}
function resKey(r:string): string {
  if (r==='Conforme')              return 'res-conf'
  if (r==='Non conforme')         return 'res-nonconf'
  if (r==='Partiellement conforme') return 'res-part'
  if (r==='Non applicable')       return 'res-na'
  if (r==='En cours')             return 'res-encours'
  return ''
}
function tauxClasse(t:any): string {
  const n = parseFloat(t)
  if (isNaN(n)) return 'taux-nd'
  if (n >= 0.9) return 'taux-bon'
  if (n >= 0.7) return 'taux-moy'
  return 'taux-bas'
}
function formatTaux(t:any): string {
  const n = parseFloat(t)
  if (isNaN(n) || t === null || t === undefined) return '—'
  return (n * 100).toFixed(1) + ' %'
}
function formatDate(d:string): string {
  if (!d) return '—'
  try { return new Date(d).toLocaleDateString('fr-FR', {day:'2-digit', month:'short', year:'numeric'}) }
  catch { return d }
}

function calcTaux(row:any){
  const ech  = parseFloat(row.taille_echantillon)
  const anom = parseInt(row.nb_anomalies)
  if (!isNaN(ech) && ech > 0 && !isNaN(anom)) {
    row.taux_conformite = Math.max(0, (ech - anom) / ech)
  } else {
    row.taux_conformite = null
  }
}

// ── CRUD ─────────────────────────────────────────────────────
function ajouterLigne(){
  lignes.push({
    _uid:uid(), num:lignes.length+1, _retenu:true, _source:'MANUEL',
    ref_objectif:'O' + (lignes.length+1),
    objectif_audit:'', ref_controle_rci:'',
    test_audit:'', procedures_audit:'',
    taille_echantillon:'', periode_testee:'',
    auditeur_responsable:'', date_debut:'', date_fin:'', lieu_local:'',
    resultat_test:'', nb_anomalies:null, taux_conformite:null,
    conclusion_auditeur:'', ref_papier_travail:'',
  })
}
function supprimerLigne(idx:number){ lignes.splice(idx,1) }

function importerTout(){
  lignes.splice(0,lignes.length)
  ;(props.donneesRCI?.lignes??[]).forEach(l => lignes.push({ ...l, _uid:uid() }))
  showToast('success', `${props.donneesRCI?.lignes?.length||0} ligne(s) importée(s) depuis le RCI`)
}
function rafraichirRCI(){
  const map = new Map((props.donneesRCI?.lignes??[]).map((l:any) => [l._source, l]))
  lignes.forEach(l => {
    if (!l._edited && l._source && map.has(l._source)) {
      const rci = map.get(l._source)
      Object.assign(l, {
        test_audit:       rci.test_audit,
        procedures_audit: rci.procedures_audit,
        _uid: l._uid, _retenu: l._retenu,
      })
    }
  })
  showToast('success', 'Données RCI rafraîchies')
}
function renumeroter(){ lignes.forEach((l,i) => { l.num=i+1 }) }

// ── Sérialisation ─────────────────────────────────────────────
function serializeLignes(){
  return lignes.map(l => ({
    num:l.num, ref_objectif:l.ref_objectif, objectif_audit:l.objectif_audit,
    ref_controle_rci:l.ref_controle_rci,
    test_audit:l.test_audit, procedures_audit:l.procedures_audit,
    taille_echantillon:l.taille_echantillon, periode_testee:l.periode_testee,
    auditeur_responsable:l.auditeur_responsable,
    date_debut:l.date_debut, date_fin:l.date_fin, lieu_local:l.lieu_local,
    resultat_test:l.resultat_test, nb_anomalies:l.nb_anomalies,
    taux_conformite:l.taux_conformite, conclusion_auditeur:l.conclusion_auditeur,
    ref_papier_travail:l.ref_papier_travail,
    _retenu:l._retenu, _source:l._source??null, _rci_id:l._rci_id??null,
  }))
}

// ── Submit ────────────────────────────────────────────────────
async function submit(){
  renumeroter()
  processing.value=true
  try {
    const payload = {
      mission_id:props.missionId, assignment_id:props.assignmentId,
      fait_par:form.fait_par, revue_par:form.revue_par, synthese:form.synthese,
      lignes:              JSON.stringify(serializeLignes()),
      sources_normatives:  JSON.stringify(sources_normatives),
    }
    const method = form.id ? 'PUT' : 'POST'
    const url    = form.id ? (props.urlUpdate||`${props.formUrl}/${form.id}`) : (props.urlStore||props.formUrl)
    const res    = await fetch(url!, {
      method, headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},
      body:JSON.stringify(payload),
    })
    const d = await res.json()
    if (d.success||res.ok){
      showToast('success', form.id?'Programme mis à jour.':'Programme créé.')
      if (!form.id&&d.form?.id){ form.id=d.form.id; form.code=d.form.code }
      if (d.form) Object.assign(form,{id:d.form.id,code:d.form.code,validation_status:d.form.validation_status})
    } else showToast('error',d.message??'Erreur.')
  } catch { showToast('error','Erreur réseau.') }
  finally  { processing.value=false }
}

function annuler(){ if(props.backUrl) router.visit(props.backUrl) }

async function soumettre(){
  processing.value=true
  try {
    const res = await fetch(props.urlSoumettre||'',{
      method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},
      body:JSON.stringify({mission_id:props.missionId,assignment_id:props.assignmentId}),
    })
    const d = await res.json()
    if(d.success){form.validation_status='in_review';showToast('success','Soumis pour validation.')}
    else showToast('error',d.error??'Erreur')
  } catch { showToast('error','Erreur réseau') }
  processing.value=false
}

async function valider(action:string,note?:string){
  processing.value=true
  try {
    const res = await fetch(props.urlValider||'',{
      method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},
      body:JSON.stringify({mission_id:props.missionId,assignment_id:props.assignmentId,action,note}),
    })
    const d = await res.json()
    if(d.success){form.validation_status=d.status;showToast('success',action==='validate'?'Programme validé ✓':'Rejeté.')}
    else showToast('error',d.error??'Erreur')
  } catch { showToast('error','Erreur réseau') }
  processing.value=false
}

function promptReject(){
  const n=prompt('Motif du rejet (obligatoire) :')
  if(!n?.trim()) return
  valider('reject',n.trim())
}

onBeforeUnmount(()=>{ if(_tt) clearTimeout(_tt) })
</script>

<style scoped>
*,*::before,*::after{box-sizing:border-box}
.pta-shell{display:flex;flex-direction:column;min-height:100vh;font-family:'Segoe UI',system-ui,sans-serif;background:#f1f5f9}

/* ── HEADER ── */
.pta-header{background:#fff;border-bottom:1px solid #e2e8f0;padding:10px 20px 0;position:sticky;top:0;z-index:50;box-shadow:0 1px 6px rgba(0,0,0,.07)}
.pta-hrow{display:flex;align-items:flex-start;gap:12px;padding-bottom:8px}
.pta-back{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border:1px solid #e2e8f0;border-radius:7px;color:#64748b;text-decoration:none;flex-shrink:0;transition:all .12s}
.pta-back:hover{background:#f1f5f9;border-color:#cbd5e1}
.pta-hinfo{flex:1;min-width:0}
.pta-chips{display:flex;align-items:center;gap:5px;flex-wrap:wrap;margin-bottom:3px}
.pta-code{font-size:.66rem;font-weight:800;background:#0f172a;color:#fff;padding:2px 8px;border-radius:4px;font-family:ui-monospace,monospace;letter-spacing:.04em}
.pta-chip{display:inline-flex;align-items:center;gap:3px;font-size:.63rem;font-weight:700;padding:2px 8px;border-radius:10px;border:1px solid transparent}
.chip-draft{background:#f1f5f9;color:#64748b;border-color:#e2e8f0}
.chip-in_review{background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe}
.chip-validated{background:#f0fdf4;color:#15803d;border-color:#bbf7d0}
.chip-type{background:#fff7ed;color:#c2410c;border-color:#fed7aa}
.chip-role-DM{background:#fdf4ff;color:#7e22ce;border-color:#e9d5ff}
.chip-role-CM{background:#eff6ff;color:#0369a1;border-color:#bae6fd}
.chip-role-AS{background:#f0fdf4;color:#15803d;border-color:#bbf7d0}
.chip-role-AJ{background:#fffbeb;color:#b45309;border-color:#fde68a}
.pta-title{font-size:.97rem;font-weight:800;color:#0f172a;margin:0 0 3px;line-height:1.2}
.pta-meta{display:flex;align-items:center;gap:12px;flex-wrap:wrap;font-size:.7rem;color:#64748b}
.pta-meta span{display:flex;align-items:center;gap:3px}
.meta-tot{color:#7e22ce!important;font-weight:700}
.meta-ret{color:#15803d!important;font-weight:700}
.meta-rci{color:#c2410c!important}
.pta-banner{display:flex;align-items:center;gap:7px;padding:5px 0;font-size:.74rem;font-weight:600;border-top:1px solid transparent}
.banner-lock{color:#15803d;border-top-color:#bbf7d0}
.banner-review{color:#1d4ed8}
.banner-reject{color:#dc2626}

/* ── BODY ── */
.pta-body{flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:12px;padding:14px 20px 80px}

/* Bannière info liaison RCI */
.rci-link-info{display:flex;align-items:center;gap:8px;padding:9px 14px;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;font-size:.73rem;color:#9a3412}
.rci-link-info i{flex-shrink:0;font-size:.85rem}

/* ── Toolbar ── */
.pta-toolbar{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:9px 14px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;flex-wrap:wrap;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.tb-left{display:flex;align-items:center;gap:8px;flex:1;flex-wrap:wrap}
.tb-right{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.tb-filters{display:flex;gap:2px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:7px;padding:2px}
.tbf{padding:4px 10px;border-radius:5px;font-size:.69rem;font-weight:600;border:none;background:transparent;cursor:pointer;color:#64748b;font-family:inherit;transition:all .12s;display:flex;align-items:center;gap:4px}
.tbf-all,.tbf:hover{background:#fff;color:#0f172a;box-shadow:0 1px 3px rgba(0,0,0,.08)}
.tbf-active{background:#fff!important;color:#0f172a!important;box-shadow:0 1px 3px rgba(0,0,0,.1)!important}
.tbf-ret.tbf-active{background:#f0fdf4!important;color:#15803d!important}
.tbf-nret.tbf-active{background:#f8fafc!important;color:#475569!important}
.tbf-cnt{font-size:.6rem;font-weight:800;background:#e2e8f0;color:#475569;padding:0 5px;border-radius:8px;min-width:18px;text-align:center}
.tb-search{display:flex;align-items:center;gap:5px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:5px 10px;min-width:200px}
.tb-search i{color:#94a3b8;font-size:.8rem;flex-shrink:0}
.search-inp{border:none;background:transparent;font-size:.73rem;color:#334155;outline:none;flex:1;font-family:inherit}
.search-clear{border:none;background:none;cursor:pointer;color:#94a3b8;font-size:.75rem;padding:0;line-height:1}
.rci-badge{display:flex;align-items:center;gap:6px;font-size:.7rem;color:#c2410c;background:#fff7ed;border:1px solid #fed7aa;border-radius:7px;padding:4px 10px}
.alert-info{display:flex;align-items:center;gap:8px;padding:10px 14px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:.74rem;color:#92400e}

/* ── Tableau ── */
.tbl-container{display:flex;flex-direction:column;gap:8px}
.tbl-scroll-wrap{overflow-x:auto;border:1px solid #e2e8f0;border-radius:10px;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.05)}
.tbl-scroll-wrap::-webkit-scrollbar{height:8px}
.tbl-scroll-wrap::-webkit-scrollbar-track{background:#f8fafc;border-radius:4px}
.tbl-scroll-wrap::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:4px}

.pta-tbl{width:100%;border-collapse:collapse;font-size:.7rem;min-width:2100px}

.head-doc th.doc-titre{background:#0f172a;color:#fff;font-size:.8rem;font-weight:800;text-align:center;padding:8px 16px;letter-spacing:.06em;text-transform:uppercase}

.head-grp th{font-size:.64rem;font-weight:700;text-align:center;padding:6px 10px;border:1px solid rgba(255,255,255,.15);vertical-align:middle;white-space:nowrap}
.hg-num{background:#334155;color:#fff;width:52px}
.hg-ident{background:#1e40af;color:#fff}
.hg-tests{background:#7c3aed;color:#fff}
.hg-plan{background:#065f46;color:#fff}
.hg-res{background:#991b1b;color:#fff}
.hg-ref{background:#374151;color:#fff;width:80px;font-size:.62rem}
.hg-act{background:#374151;color:#fff;width:38px;font-size:.7rem}
.hg-note{display:block;font-size:.54rem;font-weight:400;opacity:.75;margin-top:1px}

.head-col th{font-size:.64rem;font-weight:700;text-align:center;padding:6px 8px;border:1px solid rgba(255,255,255,.1);vertical-align:bottom;white-space:nowrap;line-height:1.3}
.hc{color:#fff}
.hc-ref{background:#2563eb;width:55px}
.hc-obj{background:#2563eb;min-width:150px}
.hc-rci{background:#2563eb;width:80px}
.hc-test{background:#7c3aed;min-width:170px}
.hc-proc{background:#7c3aed;min-width:150px}
.hc-ech{background:#047857;width:80px}
.hc-per{background:#047857;width:100px}
.hc-aud{background:#047857;min-width:130px}
.hc-dd{background:#047857;width:90px}
.hc-df{background:#047857;width:90px}
.hc-lieu{background:#047857;min-width:110px}
.hc-res{background:#b91c1c;width:110px}
.hc-anom{background:#b91c1c;width:65px}
.hc-tc{background:#b91c1c;width:80px}
.hc-concl{background:#b91c1c;min-width:140px}
.hs{display:block;font-size:.54rem;font-weight:400;opacity:.7;margin-top:1px}
.link-badge{display:inline-block;font-size:.52rem;background:rgba(255,255,255,.2);padding:1px 4px;border-radius:3px;margin-left:3px;font-weight:400}

/* Lignes */
.pta-row td{padding:5px 7px;border:1px solid #e2e8f0;vertical-align:top;transition:background .1s}
.pta-row:hover td{background:#f8fbff!important}
.row-ret td{background:#fefffe}
.row-nret td{background:#fafafa}
.row-nret td *{color:#94a3b8!important}
.row-conf .td-res{border-left:3px solid #15803d!important}
.row-nonconf .td-res{border-left:3px solid #dc2626!important}
.row-part .td-res{border-left:3px solid #b45309!important}

/* N° */
.td-num{width:52px;text-align:center;vertical-align:middle;padding:6px 4px}
.num-wrap{display:flex;flex-direction:column;align-items:center;gap:4px}
.num-val{font-size:.72rem;font-weight:800;color:#475569}
.ret-toggle{cursor:pointer;font-size:.95rem;line-height:1;transition:all .12s}
.ret-on{color:#15803d}.ret-off{color:#cbd5e1}

/* Colonnes */
.td-ref{width:55px;text-align:center;vertical-align:middle}
.td-obj{min-width:150px}
.td-rci{width:80px;text-align:center;vertical-align:top}
.td-test,.td-proc{min-width:150px;vertical-align:top}
.td-ech{width:80px;text-align:center;vertical-align:middle}
.td-per{width:100px;text-align:center;vertical-align:middle}
.td-aud{min-width:130px}
.td-dd,.td-df{width:90px;text-align:center;vertical-align:middle}
.td-lieu{min-width:110px}
.td-res{width:110px;text-align:center;vertical-align:middle}
.td-anom{width:65px;text-align:center;vertical-align:middle}
.td-tc{width:80px;text-align:center;vertical-align:middle}
.td-concl{min-width:140px}
.td-ptref{width:80px;text-align:center;vertical-align:middle}
.td-act-btn{width:38px;text-align:center;vertical-align:middle}

/* Lien RCI */
.lien-rci-wrap{display:flex;flex-direction:column;gap:3px;position:relative}
.lien-dot{font-size:.55rem;color:#7c3aed;flex-shrink:0;align-self:flex-end}
.lien-dot-j{color:#065f46}

/* Contenu */
.cell-v{display:flex;flex-direction:column;gap:3px}
.cv-main{font-size:.7rem;color:#1e293b;line-height:1.4}
.cv-main.small{font-size:.67rem;color:#334155}
.cv-main.pre{white-space:pre-wrap;word-break:break-word}
.cv-empty{color:#cbd5e1;font-size:.68rem}
.ref-badge{display:inline-block;font-size:.62rem;font-weight:800;font-family:ui-monospace,monospace;background:#dbeafe;color:#1d4ed8;padding:2px 7px;border-radius:5px}
.tag{display:inline-block;font-size:.58rem;font-weight:700;font-family:ui-monospace,monospace;padding:1px 5px;border-radius:4px}
.tag-rci{background:#ede9fe;color:#7c3aed}

/* Inputs */
.c-inp{width:100%;border:1px solid transparent;background:transparent;font-size:.7rem;font-family:inherit;outline:none;color:#1e293b;padding:2px 4px;border-radius:4px;transition:all .12s;line-height:1.4}
.c-inp:hover{border-color:#e2e8f0;background:#f8fafc}
.c-inp:focus{border-color:#3b82f6;background:#fff;box-shadow:0 0 0 2px rgba(59,130,246,.1)}
.c-inp-sm{font-size:.67rem}
.c-inp-xs{font-size:.62rem;margin-top:3px}
.c-ta{width:100%;border:1px solid transparent;background:transparent;font-size:.68rem;font-family:inherit;outline:none;color:#1e293b;resize:none;padding:2px 4px;border-radius:4px;transition:all .12s;line-height:1.5}
.c-ta:hover{border-color:#e2e8f0;background:#f8fafc}
.c-ta:focus{border-color:#3b82f6;background:#fff}
.c-ta-lg{min-height:56px}
.c-sel{width:100%;border:1px solid transparent;background:transparent;font-size:.68rem;font-family:inherit;outline:none;color:#1e293b;cursor:pointer;padding:2px 4px;border-radius:4px;transition:all .12s}
.c-sel:hover{border-color:#e2e8f0;background:#f8fafc}
.c-sel:focus{border-color:#3b82f6}
.c-num{width:52px;text-align:center;border:1px solid #e2e8f0;border-radius:4px;font-size:.66rem;padding:2px 4px;outline:none;background:#fff;font-family:inherit;color:#1e293b}
.c-num:focus{border-color:#3b82f6}

/* Résultat */
.res-badge{font-size:.62rem;font-weight:700;padding:2px 7px;border-radius:5px;white-space:nowrap}
.res-conf{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0}
.res-nonconf{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
.res-part{background:#fffbeb;color:#b45309;border:1px solid #fde68a}
.res-na{background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0}
.res-encours{background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe}

/* Anomalies */
.anom-val{font-size:.73rem;font-weight:700;color:#475569}
.anom-pos{color:#dc2626}

/* Taux */
.taux-wrap{display:flex;justify-content:center}
.taux-badge{font-size:.68rem;font-weight:700;padding:3px 8px;border-radius:6px}
.taux-bon{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0}
.taux-moy{background:#fffbeb;color:#b45309;border:1px solid #fde68a}
.taux-bas{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
.taux-nd{background:#f1f5f9;color:#94a3b8;border:1px solid #e2e8f0}

/* Actions */
.ibtn{width:28px;height:28px;display:flex;align-items:center;justify-content:center;background:transparent;border:1px solid transparent;border-radius:6px;cursor:pointer;font-size:.8rem;color:#cbd5e1;padding:0;transition:all .12s}
.ibtn-del:hover{color:#dc2626;border-color:#fecaca;background:#fef2f2}

/* Légende */
.res-legend{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:6px 12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:0 0 10px 10px;font-size:.66rem;color:#64748b}
.leg-item{display:flex;align-items:center;gap:4px}
.leg-sep{color:#e2e8f0;font-size:1rem}

/* Stats */
.stats-bar{display:flex;gap:10px;flex-wrap:wrap}
.stat-card{flex:1;min-width:100px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:12px 16px;display:flex;flex-direction:column;align-items:center;gap:3px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.sc-val{font-size:1.4rem;font-weight:800;color:#0f172a;line-height:1}
.sc-lbl{font-size:.64rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.04em}
.sc-green{color:#15803d}
.sc-red{color:#dc2626}
.sc-orange{color:#b45309}
.stat-taux{border-color:#e9d5ff;background:#fdf4ff}

/* Cards */
.card-section{background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.cs-hdr{display:flex;align-items:center;gap:7px;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#fff;background:#1e293b;padding:8px 14px}
.cs-body{padding:12px 14px}
.cs-sign{display:flex;gap:16px;flex-wrap:wrap}
.sfg{display:flex;flex-direction:column;gap:3px;flex:1;min-width:160px}
.slbl{font-size:.6rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em}

/* Normes */
.empty-sm{font-size:.68rem;color:#94a3b8;font-style:italic;padding:4px 0}
.norm-row{display:flex;align-items:center;gap:8px;padding:5px 0;border-top:1px solid #f1f5f9}
.norm-ro{font-size:.72rem;color:#334155}
.rinp-nrm{flex:2}
.rinp-sm{width:90px;flex:none}

/* Inputs généraux */
.rinp{background:#fff;border:1px solid #e2e8f0;color:#1e293b;padding:6px 10px;border-radius:7px;font-size:.76rem;outline:none;transition:border-color .15s;font-family:inherit;width:100%}
.rinp:focus{border-color:#3b82f6;box-shadow:0 0 0 2px rgba(59,130,246,.1)}
.rinp:disabled{background:#f8fafc;color:#94a3b8}
.rinp-ta{resize:vertical;min-height:64px;line-height:1.5}

/* Boutons */
.btn{display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:7px;font-size:.76rem;font-weight:700;border:none;cursor:pointer;font-family:inherit;transition:all .13s;white-space:nowrap}
.btn-save{background:#0f172a;color:#fff}.btn-save:hover:not(:disabled){background:#1e293b}
.btn-ghost{background:#fff;color:#475569;border:1px solid #e2e8f0}.btn-ghost:hover:not(:disabled){background:#f8fafc}
.btn-add{background:#c2410c;color:#fff}.btn-add:hover:not(:disabled){background:#9a3412}
.btn-import{background:#fff7ed;color:#c2410c;border:1px solid #fed7aa}
.btn-sub{background:#1d4ed8;color:#fff}.btn-sub:hover:not(:disabled){background:#1e40af}
.btn-ok{background:#15803d;color:#fff}.btn-ok:hover:not(:disabled){background:#166534}
.btn-rej{background:#fff;color:#dc2626;border:1px solid #fecaca}.btn-rej:hover:not(:disabled){background:#fef2f2}
.btn-sm{padding:5px 11px;font-size:.73rem}
.btn-xs{padding:3px 8px;font-size:.66rem}
.ml-auto{margin-left:auto}
.btn:disabled{opacity:.45;cursor:not-allowed}

/* Empty */
.empty-state{display:flex;flex-direction:column;align-items:center;gap:8px;padding:40px;color:#94a3b8;text-align:center;background:#fafafa}
.empty-state i{font-size:1.8rem;opacity:.2}
.empty-state p{font-size:.74rem;max-width:320px;line-height:1.6;margin:0}

/* Footer */
.pta-footer{position:sticky;bottom:0;display:flex;align-items:center;justify-content:space-between;padding:10px 20px;background:#fff;border-top:2px solid #e2e8f0;box-shadow:0 -2px 8px rgba(0,0,0,.06);flex-wrap:wrap;gap:8px;z-index:40}
.footer-left,.footer-right{display:flex;gap:6px;flex-wrap:wrap}
.footer-mid{flex:1;display:flex;justify-content:center;align-items:center;gap:12px}
.saved-code{font-size:.72rem;color:#15803d;display:flex;align-items:center;gap:4px;font-weight:700;background:#f0fdf4;padding:3px 10px;border-radius:6px;border:1px solid #bbf7d0}
.stat-lbl{font-size:.7rem;color:#c2410c;font-weight:700;background:#fff7ed;padding:3px 10px;border-radius:6px;border:1px solid #fed7aa}

/* Toast */
.toast{position:fixed;bottom:80px;right:20px;z-index:9999;display:flex;align-items:center;gap:8px;padding:10px 16px;border-radius:10px;font-size:.76rem;font-weight:700;box-shadow:0 6px 24px rgba(0,0,0,.15);border:1px solid transparent;min-width:200px}
.toast--success{background:#f0fdf4;color:#15803d;border-color:#bbf7d0}
.toast--error{background:#fef2f2;color:#dc2626;border-color:#fecaca}
.toast-t-enter-active,.toast-t-leave-active{transition:all .25s cubic-bezier(.4,0,.2,1)}
.toast-t-enter-from,.toast-t-leave-to{opacity:0;transform:translateY(10px)}

.spin-s{width:12px;height:12px;border-radius:50%;border:2px solid currentColor;border-top-color:transparent;animation:spin .6s linear infinite;display:inline-block;flex-shrink:0}
@keyframes spin{to{transform:rotate(360deg)}}

::-webkit-scrollbar{width:5px;height:8px}
::-webkit-scrollbar-track{background:#f8fafc}
::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:4px}
::-webkit-scrollbar-thumb:hover{background:#94a3b8}
</style>