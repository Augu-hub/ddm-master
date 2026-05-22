<template>
  <VerticalLayoutAudit>
    <div class="rci-shell">

      <!-- ═══ HEADER ═══ -->
      <header class="rci-header">
        <div class="rci-hrow">
          <a :href="props.backUrl" class="rci-back"><i class="ti ti-arrow-left"></i></a>
          <div class="rci-hinfo">
            <div class="rci-chips">
              <code class="rci-code">{{ form.code || 'RCI-AUTO' }}</code>
              <span class="rci-chip" :class="`chip-${form.validation_status||'draft'}`">
                <i :class="vstIcon(form.validation_status||'draft')"></i>
                {{ vstLbl(form.validation_status||'draft') }}
              </span>
              <span class="rci-chip chip-type">RCI</span>
              <span v-if="props.auditorRole" class="rci-chip" :class="`chip-role-${props.auditorRole}`">
                {{ props.auditorRole }}
              </span>
            </div>
            <h1 class="rci-title">Référentiel de Contrôle Interne — Mission d'Audit</h1>
            <div class="rci-meta">
              <span v-if="mission?.code_mission"><i class="ti ti-clipboard"></i>{{ mission.code_mission }}</span>
              <span v-if="mission?.libelle"><i class="ti ti-file-description"></i>{{ mission.libelle }}</span>
              <span class="meta-tot"><i class="ti ti-table"></i>{{ criteres.length }} ligne(s)</span>
              <span class="meta-ret"><i class="ti ti-check-circle"></i>{{ criteres.filter(c=>c._retenu).length }} retenu(s)</span>
              <span class="meta-fn" v-if="props.fonctionsEntite?.length">
                <i class="ti ti-users"></i>{{ props.fonctionsEntite.length }} fonction(s) entité
              </span>
            </div>
          </div>
        </div>
        <div v-if="form.validation_status==='validated'" class="rci-banner banner-lock">
          <i class="ti ti-lock"></i> RCI <strong>validé définitivement</strong> — lecture seule
        </div>
        <div v-else-if="form.validation_status==='in_review'" class="rci-banner banner-review">
          <i class="ti ti-clock"></i> Soumis pour validation
          <span v-if="canManage"> · DM/CM peut valider ou rejeter.</span>
        </div>
        <div v-else-if="form.validation_status==='draft'&&form.validation_note" class="rci-banner banner-reject">
          <i class="ti ti-circle-x"></i> Rejeté — <em>{{ form.validation_note }}</em>
        </div>
      </header>

      <!-- ═══ BODY ═══ -->
      <div class="rci-body">

        <!-- ── Barre d'outils ── -->
        <div class="rci-toolbar">
          <div class="tb-left">
            <div class="tb-filters">
              <button :class="['tbf',filtreRetenu===null?'tbf-all':'']" @click="filtreRetenu=null">
                Tous <span class="tbf-cnt">{{ criteres.length }}</span>
              </button>
              <button :class="['tbf tbf-ret',filtreRetenu===true?'tbf-active':'']" @click="filtreRetenu=true">
                <i class="ti ti-check-circle"></i> Retenus <span class="tbf-cnt">{{ criteres.filter(c=>c._retenu).length }}</span>
              </button>
              <button :class="['tbf tbf-nret',filtreRetenu===false?'tbf-active':'']" @click="filtreRetenu=false">
                Non retenus <span class="tbf-cnt">{{ criteres.filter(c=>!c._retenu).length }}</span>
              </button>
            </div>
            <div class="tb-search">
              <i class="ti ti-search"></i>
              <input v-model="search" class="search-inp" placeholder="Processus, risque, contrôle…"/>
              <button v-if="search" class="search-clear" @click="search=''"><i class="ti ti-x"></i></button>
            </div>
          </div>
          <div class="tb-right">
            <div v-if="hasDonneesDB" class="db-badge">
              <i class="ti ti-database"></i>
              <span>{{ props.donneesDB?.lignes?.length }} ligne(s) AR/AP</span>
              <button v-if="!criteres.length&&!isLocked" class="btn btn-import btn-xs" @click="importerTout">
                <i class="ti ti-download"></i> Importer
              </button>
              <button v-else-if="!isLocked" class="btn btn-ghost btn-xs" @click="rafraichirDB">
                <i class="ti ti-refresh"></i>
              </button>
            </div>
            <button v-if="!isLocked" class="btn btn-add btn-sm" @click="ajouterLigne">
              <i class="ti ti-plus"></i> Ajouter
            </button>
          </div>
        </div>

        <!-- ── Alerte BD vide ── -->
        <div v-if="!hasDonneesDB" class="alert-info">
          <i class="ti ti-info-circle"></i>
          Aucune donnée trouvée dans l'Analyse des Risques (AR) pour cette phase.
          Complétez l'AR et sélectionnez des risques (choix = ✓) pour générer automatiquement le RCI.
        </div>

        <!-- ══════════════════════════════════════════════════════
             TABLEAU RCI — 13 colonnes Excel, scroll horizontal
             ══════════════════════════════════════════════════════ -->
        <div class="tbl-container">
          <div v-if="!filteredCriteres.length" class="empty-state">
            <i class="ti ti-table-off"></i>
            <p>Aucune ligne RCI.<br/>Importez depuis l'Analyse des Risques ou ajoutez manuellement.</p>
          </div>

          <div v-else class="tbl-scroll-wrap">
            <table class="rci-tbl">
              <thead>
                <!-- Ligne 1 : titre document -->
                <tr class="head-doc">
                  <th colspan="14" class="doc-titre">
                    RÉFÉRENTIEL DE CONTRÔLE INTERNE — MISSION D'AUDIT INTERNE
                  </th>
                </tr>
                <!-- Ligne 2 : groupes de colonnes -->
                <tr class="head-grp">
                  <th class="hg-num" rowspan="2">N°</th>
                  <th colspan="2" class="hg-ident">
                    IDENTIFICATION DU PROCESSUS
                    <span class="hg-note">(Issue de l'analyse des risques)</span>
                  </th>
                  <th colspan="2" class="hg-obj">OBJECTIFS</th>
                  <th colspan="2" class="hg-risq">ÉVALUATION DES RISQUES</th>
                  <th colspan="5" class="hg-ctrl">DISPOSITIF DE CONTRÔLE</th>
                  <th v-if="!isLocked" class="hg-act" rowspan="2">⚙</th>
                </tr>
                <!-- Ligne 3 : colonnes -->
                <tr class="head-col">
                  <th class="hc hc-proc">Processus<br/><span class="hs">Nom du processus</span></th>
                  <th class="hc hc-act">Activité<br/><span class="hs">Action élémentaire</span></th>
                  <th class="hc hc-os">Objectif Stratégique<br/><span class="hs">Lien stratégie</span></th>
                  <th class="hc hc-oo">Objectif Opérationnel<br/><span class="hs">Résultat attendu</span></th>
                  <th class="hc hc-risq">Risque Identifié<br/><span class="hs">Événement risqué</span></th>
                  <th class="hc hc-crit">Criticité Résid.<br/><span class="hs">Prob × Impact</span></th>
                  <th class="hc hc-desc">Description du Contrôle<br/><span class="hs">Procédure en place</span></th>
                  <th class="hc hc-type">Type<br/><span class="hs">Prév/Dét/Corr</span></th>
                  <th class="hc hc-prev">Preuve<br/><span class="hs">Articles, Actes</span></th>
                  <th class="hc hc-freq">Fréquence<br/><span class="hs">Continu/Mensuel…</span></th>
                  <th class="hc hc-resp">Responsable Contrôle<br/><span class="hs">Fonction/Service</span></th>
                  <th class="hc hc-prop">Propriétaire Processus<br/><span class="hs">Fonction entité</span></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, idx) in filteredCriteres" :key="row._uid"
                    :class="['rci-row', row._retenu?'row-ret':'row-nret', critClasse(row.criticite_residuelle)]">

                  <!-- A : N° -->
                  <td class="td-num">
                    <div class="num-wrap">
                      <span class="num-val">{{ row.num }}</span>
                      <span v-if="!isLocked" class="ret-toggle"
                            :title="row._retenu?'Retenu (cliquer pour exclure)':'Non retenu (cliquer pour inclure)'"
                            @click="row._retenu=!row._retenu">
                        <i class="ti" :class="row._retenu?'ti-circle-check ret-on':'ti-circle ret-off'"></i>
                      </span>
                      <span v-else>
                        <i class="ti" :class="row._retenu?'ti-circle-check ret-on':'ti-circle ret-off'"></i>
                      </span>
                    </div>
                  </td>

                  <!-- B : Processus -->
                  <td class="td-proc">
                    <div class="cell-v">
                      <span class="tag tag-proc">{{ row.process_code }}</span>
                      <span v-if="isLocked" class="cv-main">{{ row.process_name||'—' }}</span>
                      <input v-else class="c-inp" v-model="row.process_name"
                             placeholder="Nom du processus…" @change="row._edited=true"/>
                    </div>
                  </td>

                  <!-- C : Activité -->
                  <td class="td-act">
                    <div class="cell-v">
                      <span class="tag tag-act">{{ row.activity_code }}</span>
                      <span v-if="isLocked" class="cv-main">{{ row.activity_name||'—' }}</span>
                      <input v-else class="c-inp" v-model="row.activity_name"
                             placeholder="Activité…" @change="row._edited=true"/>
                    </div>
                  </td>

                  <!-- D : Objectif Stratégique -->
                  <td class="td-os">
                    <textarea v-if="!isLocked" class="c-ta" v-model="row.objectif_strategique"
                              rows="2" placeholder="Lien avec la stratégie…"
                              @change="row._edited=true"></textarea>
                    <span v-else class="cv-main small">{{ row.objectif_strategique||'—' }}</span>
                  </td>

                  <!-- E : Objectif Opérationnel -->
                  <td class="td-oo">
                    <textarea v-if="!isLocked" class="c-ta" v-model="row.objectif_operationnel"
                              rows="2" placeholder="Résultat attendu…"
                              @change="row._edited=true"></textarea>
                    <span v-else class="cv-main small">{{ row.objectif_operationnel||'—' }}</span>
                  </td>

                  <!-- F : Risque Identifié -->
                  <td class="td-risq">
                    <div class="cell-v">
                      <span class="tag tag-risk">{{ row.risque_code }}</span>
                      <textarea v-if="!isLocked" class="c-ta" v-model="row.risque_libelle"
                                rows="2" placeholder="Événement risqué…"
                                @change="row._edited=true"></textarea>
                      <span v-else class="cv-main">{{ row.risque_libelle||'—' }}</span>
                    </div>
                  </td>

                  <!-- G : Criticité Résiduelle -->
                  <td class="td-crit">
                    <div class="crit-wrap">
                      <span :class="['crit-badge', critClasse(row.criticite_residuelle)]">
                        {{ row.criticite_residuelle || '?' }}
                      </span>
                      <div v-if="!isLocked" class="crit-inputs">
                        <div class="crit-inp-row">
                          <label class="crit-lbl">I</label>
                          <input type="number" class="c-num" v-model.number="row.impact_net"
                                 min="0" max="5" step="0.5"
                                 @input="recalcCrit(row)" title="Impact (0–5)"/>
                        </div>
                        <div class="crit-inp-row">
                          <label class="crit-lbl">F</label>
                          <input type="number" class="c-num" v-model.number="row.frequency_net"
                                 min="0" max="5" step="0.5"
                                 @input="recalcCrit(row)" title="Fréquence (0–5)"/>
                        </div>
                      </div>
                      <div v-else class="crit-dims-ro">
                        {{ row.impact_net }}×{{ row.frequency_net }}
                      </div>
                    </div>
                  </td>

                  <!-- H : Description du Contrôle -->
                  <td class="td-desc">
                    <textarea v-if="!isLocked" class="c-ta c-ta-lg" v-model="row.description_controle"
                              rows="3" placeholder="Procédure de contrôle en place…"
                              @change="row._edited=true"></textarea>
                    <p v-else class="cv-main small pre">{{ row.description_controle||'—' }}</p>
                  </td>

                  <!-- I : Type de Contrôle -->
                  <td class="td-type">
                    <select v-if="!isLocked" class="c-sel" v-model="row.type_controle"
                            @change="row._edited=true">
                      <option value="">—</option>
                      <option v-for="t in TYPES" :key="t" :value="t">{{ t }}</option>
                    </select>
                    <span v-else>
                      <span v-if="row.type_controle" :class="['type-badge','type-'+typeKey(row.type_controle)]">
                        {{ row.type_controle }}
                      </span>
                      <span v-else class="cv-empty">—</span>
                    </span>
                  </td>

                  <!-- J : Preuve du Contrôle -->
                  <td class="td-prev">
                    <input v-if="!isLocked" class="c-inp" v-model="row.preuve_controle"
                           placeholder="Articles, actes, décisions…"
                           @change="row._edited=true"/>
                    <span v-else class="cv-main small">{{ row.preuve_controle||'—' }}</span>
                  </td>

                  <!-- K : Fréquence -->
                  <td class="td-freq">
                    <select v-if="!isLocked" class="c-sel" v-model="row.frequence_controle"
                            @change="row._edited=true">
                      <option value="">—</option>
                      <option v-for="f in FREQUENCES" :key="f" :value="f">{{ f }}</option>
                    </select>
                    <span v-else class="cv-main small">{{ row.frequence_controle||'—' }}</span>
                  </td>

                  <!-- L : Responsable du Contrôle (fonction de l'entité) -->
                  <td class="td-resp">
                    <template v-if="!isLocked">
                      <select v-if="fonctionOptions.length" class="c-sel"
                              v-model="row.responsable_controle"
                              @change="row._edited=true">
                        <option value="">— Sélectionner —</option>
                        <option v-for="fn in fonctionOptions" :key="fn.id"
                                :value="fn.name">
                          {{ fn.name }} ({{ fn.character }})
                        </option>
                      </select>
                      <input v-else class="c-inp" v-model="row.responsable_controle"
                             placeholder="Fonction/Service responsable…"
                             @change="row._edited=true"/>
                    </template>
                    <span v-else class="cv-main small">{{ row.responsable_controle||'—' }}</span>
                  </td>

                  <!-- M : Propriétaire du Processus (fonctions de l'entité) -->
                  <td class="td-prop">
                    <template v-if="!isLocked">
                      <select v-if="fonctionOptions.length" class="c-sel c-sel-prop"
                              v-model="row.proprietaire_processus"
                              @change="row._edited=true">
                        <option value="">— Propriétaire —</option>
                        <option v-for="fn in fonctionOptions" :key="fn.id"
                                :value="fn.name">
                          {{ fn.name }}
                        </option>
                      </select>
                      <input v-else class="c-inp" v-model="row.proprietaire_processus"
                             placeholder="Propriétaire processus…"
                             @change="row._edited=true"/>
                    </template>
                    <span v-else class="cv-main small">{{ row.proprietaire_processus||'—' }}</span>
                  </td>

                  <!-- Actions -->
                  <td v-if="!isLocked" class="td-act-btn">
                    <button class="ibtn ibtn-del" @click="supprimerLigne(idx)"
                            title="Supprimer cette ligne">
                      <i class="ti ti-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Légende criticité -->
          <div class="crit-legend">
            <span class="leg-item"><span class="crit-badge crit-critique">16+</span> Critique</span>
            <span class="leg-item"><span class="crit-badge crit-eleve">9–15</span> Élevé</span>
            <span class="leg-item"><span class="crit-badge crit-modere">4–8</span> Modéré</span>
            <span class="leg-item"><span class="crit-badge crit-faible">1–3</span> Faible</span>
            <span class="leg-sep">|</span>
            <span class="leg-item"><i class="ti ti-circle-check ret-on"></i> Retenu dans l'audit</span>
            <span class="leg-item"><i class="ti ti-circle ret-off"></i> Non retenu</span>
          </div>
        </div>

        <!-- ── Fonctions de l'entité (info) ── -->
        <div v-if="props.fonctionsEntite?.length" class="fn-panel">
          <div class="fn-panel-hdr">
            <i class="ti ti-users"></i>
            Fonctions affectées à l'entité auditée
            <span class="fn-count">{{ props.fonctionsEntite.length }}</span>
          </div>
          <div class="fn-list">
            <div v-for="fn in props.fonctionsEntite" :key="fn.id" class="fn-item">
              <span class="fn-char">{{ fn.character }}</span>
              <span class="fn-name">{{ fn.name }}</span>
            </div>
          </div>
        </div>

        <!-- ── Synthèse ── -->
        <div class="card-section">
          <div class="cs-hdr"><i class="ti ti-report-analytics"></i> Synthèse globale</div>
          <div class="cs-body">
            <textarea class="rinp rinp-ta" v-model="form.synthese" :disabled="isLocked"
                      rows="3"
                      placeholder="Observations générales sur le référentiel de contrôle interne…"></textarea>
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
                <input class="rinp rinp-nrm" v-model="s.norme" placeholder="Norme (ex: COSO 2013, INTOSAI…)"/>
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

        <!-- ── Fait par / Revu par ── -->
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
        <footer class="rci-footer">
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
              {{ criteres.filter(c=>c._retenu).length }}/{{ criteres.length }} lignes retenues
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
  form?:any; rciList?:any[]; currentAuditor?:any; phaseAuditeurs?:any[]
  donneesDB?:        { lignes?: any[]; total?: number }
  fonctionsEntite?:  { id:number; name:string; character:string; entity_id?:number }[]
  typesControle?:    string[]
  frequences?:       string[]
  backUrl?:string; formUrl?:string
  urlStore?:string; urlUpdate?:string; urlSoumettre?:string; urlValider?:string; urlIndex?:string
}>(), {
  rciList:()=>[], phaseAuditeurs:()=>[],
  donneesDB:()=>({ lignes:[], total:0 }),
  fonctionsEntite:()=>[],
  typesControle:()=>['Préventif','Détectif','Correctif','Directif'],
  frequences:()=>['Continu','Quotidien','Hebdomadaire','Mensuel','Trimestriel','Semestriel','Annuel'],
})

const TYPES     = props.typesControle
const FREQUENCES = props.frequences
const mission   = props.mission ?? props.form?.mission

let _uid = 0
const uid = () => String(++_uid)

// ── State ──────────────────────────────────────────────────────
const form = reactive<any>({
  id:null, code:'', validation_status:'draft', validation_note:'',
  fait_par:'', revue_par:'', synthese:'',
  ...(props.form ?? {}),
})

const criteres = reactive<any[]>(
  safeArr(props.form?.criteres).map(c => ({ ...c, _uid:uid(), _retenu:c._retenu??true }))
)

// Auto-import si aucun critère sauvegardé
if (!criteres.length && (props.donneesDB?.lignes?.length ?? 0) > 0) {
  props.donneesDB!.lignes!.forEach(l => criteres.push({ ...l, _uid:uid() }))
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
const canManage  = computed(()=> ['DM','CM'].includes(props.auditorRole??''))
const isLocked   = computed(()=> form.validation_status==='validated' ||
  (form.validation_status==='in_review' && !canManage.value))
const hasDonneesDB = computed(()=> (props.donneesDB?.lignes?.length??0) > 0)

// Fonctions uniques dédupliquées pour les selects
const fonctionOptions = computed(()=>{
  const seen = new Set<number>()
  return (props.fonctionsEntite??[]).filter(fn => {
    if (seen.has(fn.id)) return false
    seen.add(fn.id); return true
  })
})

const filteredCriteres = computed(()=>{
  let list = criteres
  if (filtreRetenu.value !== null) {
    list = list.filter(c => c._retenu === filtreRetenu.value)
  }
  if (search.value.trim()) {
    const q = search.value.toLowerCase()
    list = list.filter(c =>
      (c.process_name??'').toLowerCase().includes(q) ||
      (c.activity_name??'').toLowerCase().includes(q) ||
      (c.risque_libelle??'').toLowerCase().includes(q) ||
      (c.risque_code??'').toLowerCase().includes(q) ||
      (c.description_controle??'').toLowerCase().includes(q) ||
      (c.responsable_controle??'').toLowerCase().includes(q) ||
      (c.proprietaire_processus??'').toLowerCase().includes(q)
    )
  }
  return list
})

// ── Helpers ────────────────────────────────────────────────────
function safeArr(v:any): any[]{
  if (Array.isArray(v)) return [...v]
  if (!v) return []
  try { const d=JSON.parse(v); return Array.isArray(d)?d:[] } catch{ return [] }
}
function csrf(){ return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content??'' }
function vstLbl(s:string){ return ({draft:'Brouillon',in_review:'En attente',validated:'Validé ✓',rejected:'Rejeté'} as any)[s]??s }
function vstIcon(s:string){ return ({draft:'ti ti-pencil',in_review:'ti ti-clock',validated:'ti ti-circle-check',rejected:'ti ti-circle-x'} as any)[s]??'ti ti-circle' }

function critClasse(v:any): string {
  const n = parseFloat(v)||0
  if (n>=16) return 'crit-critique'
  if (n>=9)  return 'crit-eleve'
  if (n>=4)  return 'crit-modere'
  if (n>0)   return 'crit-faible'
  return 'crit-nd'
}
function typeKey(t:string): string {
  return ({Préventif:'prev',Détectif:'det',Correctif:'corr',Directif:'dir'} as any)[t]??'nd'
}
function recalcCrit(row:any){
  row.criticite_residuelle = +(parseFloat(row.impact_net||0) * parseFloat(row.frequency_net||0)).toFixed(1)
}

// ── CRUD ─────────────────────────────────────────────────────
function ajouterLigne(){
  criteres.push({
    _uid:uid(), num:criteres.length+1, _retenu:true, _source:'MANUEL',
    process_code:'', process_name:'', activity_code:'', activity_name:'',
    objectif_strategique:'', objectif_operationnel:'',
    risque_code:'', risque_libelle:'',
    impact_net:0, frequency_net:0, criticite_residuelle:0,
    description_controle:'', type_controle:'', preuve_controle:'',
    frequence_controle:'', responsable_controle:'', proprietaire_processus:'',
  })
}
function supprimerLigne(idx:number){ criteres.splice(idx,1) }

function importerTout(){
  criteres.splice(0,criteres.length)
  ;(props.donneesDB?.lignes??[]).forEach(l => criteres.push({ ...l, _uid:uid() }))
  showToast('success', `${props.donneesDB?.lignes?.length||0} ligne(s) importée(s)`)
}
function rafraichirDB(){
  const map = new Map((props.donneesDB?.lignes??[]).map((l:any) => [l._source, l]))
  criteres.forEach(c => {
    if (!c._edited && c._source && map.has(c._source)) {
      const db = map.get(c._source)
      Object.assign(c, { ...db, _uid:c._uid, _retenu:c._retenu })
    }
  })
  showToast('success', 'Données rafraîchies')
}

function renumeroter(){ criteres.forEach((c,i) => { c.num=i+1 }) }

// ── Sérialisation ────────────────────────────────────────────
function serializeCriteres(){
  return criteres.map(c => ({
    num:c.num, process_code:c.process_code, process_name:c.process_name,
    activity_code:c.activity_code, activity_name:c.activity_name,
    objectif_strategique:c.objectif_strategique, objectif_operationnel:c.objectif_operationnel,
    risque_code:c.risque_code, risque_libelle:c.risque_libelle,
    impact_net:c.impact_net, frequency_net:c.frequency_net,
    criticite_residuelle:c.criticite_residuelle,
    description_controle:c.description_controle, type_controle:c.type_controle,
    preuve_controle:c.preuve_controle, frequence_controle:c.frequence_controle,
    responsable_controle:c.responsable_controle, proprietaire_processus:c.proprietaire_processus,
    _retenu:c._retenu, _source:c._source??null, _risk_id:c._risk_id??null,
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
      criteres:           JSON.stringify(serializeCriteres()),
      sources_normatives: JSON.stringify(sources_normatives),
    }
    const method = form.id ? 'PUT' : 'POST'
    const url    = form.id ? (props.urlUpdate||`${props.formUrl}/${form.id}`) : (props.urlStore||props.formUrl)
    const res    = await fetch(url!, {
      method, headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},
      body:JSON.stringify(payload),
    })
    const d = await res.json()
    if (d.success||res.ok){
      showToast('success', form.id?'RCI mis à jour.':'RCI créé.')
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
      method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},
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
      method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},
      body:JSON.stringify({mission_id:props.missionId,assignment_id:props.assignmentId,action,note}),
    })
    const d = await res.json()
    if(d.success){form.validation_status=d.status;showToast('success',action==='validate'?'RCI Validé ✓':'Rejeté.')}
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
.rci-shell{display:flex;flex-direction:column;min-height:100vh;font-family:'Segoe UI',system-ui,sans-serif;background:#f1f5f9}

/* ── HEADER ── */
.rci-header{background:#fff;border-bottom:1px solid #e2e8f0;padding:10px 20px 0;position:sticky;top:0;z-index:50;box-shadow:0 1px 6px rgba(0,0,0,.07)}
.rci-hrow{display:flex;align-items:flex-start;gap:12px;padding-bottom:8px}
.rci-back{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border:1px solid #e2e8f0;border-radius:7px;color:#64748b;text-decoration:none;flex-shrink:0;transition:all .12s}
.rci-back:hover{background:#f1f5f9;border-color:#cbd5e1}
.rci-hinfo{flex:1;min-width:0}
.rci-chips{display:flex;align-items:center;gap:5px;flex-wrap:wrap;margin-bottom:3px}
.rci-code{font-size:.66rem;font-weight:800;background:#0f172a;color:#fff;padding:2px 8px;border-radius:4px;font-family:ui-monospace,monospace;letter-spacing:.04em}
.rci-chip{display:inline-flex;align-items:center;gap:3px;font-size:.63rem;font-weight:700;padding:2px 8px;border-radius:10px;border:1px solid transparent}
.chip-draft{background:#f1f5f9;color:#64748b;border-color:#e2e8f0}
.chip-in_review{background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe}
.chip-validated{background:#f0fdf4;color:#15803d;border-color:#bbf7d0}
.chip-type{background:#fdf4ff;color:#7e22ce;border-color:#e9d5ff}
.chip-role-DM{background:#fdf4ff;color:#7e22ce;border-color:#e9d5ff}
.chip-role-CM{background:#eff6ff;color:#0369a1;border-color:#bae6fd}
.chip-role-AS{background:#f0fdf4;color:#15803d;border-color:#bbf7d0}
.chip-role-AJ{background:#fffbeb;color:#b45309;border-color:#fde68a}
.rci-title{font-size:.97rem;font-weight:800;color:#0f172a;margin:0 0 3px;line-height:1.2}
.rci-meta{display:flex;align-items:center;gap:12px;flex-wrap:wrap;font-size:.7rem;color:#64748b}
.rci-meta span{display:flex;align-items:center;gap:3px}
.meta-tot{color:#7e22ce!important;font-weight:700}
.meta-ret{color:#15803d!important;font-weight:700}
.meta-fn{color:#0369a1!important}
.rci-banner{display:flex;align-items:center;gap:7px;padding:5px 0;font-size:.74rem;font-weight:600;border-top:1px solid transparent}
.banner-lock{color:#15803d;border-top-color:#bbf7d0}
.banner-review{color:#1d4ed8}
.banner-reject{color:#dc2626}

/* ── BODY ── */
.rci-body{flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:12px;padding:14px 20px 80px}

/* ── Toolbar ── */
.rci-toolbar{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:9px 14px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;flex-wrap:wrap;box-shadow:0 1px 3px rgba(0,0,0,.05)}
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
.search-clear{border:none;background:none;cursor:pointer;color:#94a3b8;font-size:.75rem;padding:0;line-height:1;flex-shrink:0}
.search-clear:hover{color:#475569}
.db-badge{display:flex;align-items:center;gap:6px;font-size:.7rem;color:#7e22ce;background:#fdf4ff;border:1px solid #e9d5ff;border-radius:7px;padding:4px 10px}
.alert-info{display:flex;align-items:center;gap:8px;padding:10px 14px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:.74rem;color:#92400e}

/* ── Tableau principal ── */
.tbl-container{display:flex;flex-direction:column;gap:8px}
.tbl-scroll-wrap{overflow-x:auto;border:1px solid #e2e8f0;border-radius:10px;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.05)}
.tbl-scroll-wrap::-webkit-scrollbar{height:8px}
.tbl-scroll-wrap::-webkit-scrollbar-track{background:#f8fafc;border-radius:4px}
.tbl-scroll-wrap::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:4px}
.tbl-scroll-wrap::-webkit-scrollbar-thumb:hover{background:#94a3b8}

.rci-tbl{width:100%;border-collapse:collapse;font-size:.7rem;min-width:1700px}

/* En-tête document */
.head-doc th.doc-titre{background:#0f172a;color:#fff;font-size:.8rem;font-weight:800;text-align:center;padding:8px 16px;letter-spacing:.06em;text-transform:uppercase}

/* Groupes */
.head-grp th{font-size:.64rem;font-weight:700;text-align:center;padding:6px 10px;border:1px solid rgba(255,255,255,.15);vertical-align:middle;white-space:nowrap}
.hg-num{background:#334155;color:#fff;width:52px}
.hg-ident{background:#1e40af;color:#fff}
.hg-obj{background:#7c3aed;color:#fff}
.hg-risq{background:#991b1b;color:#fff}
.hg-ctrl{background:#065f46;color:#fff}
.hg-act{background:#374151;color:#fff;width:38px;font-size:.7rem}
.hg-note{display:block;font-size:.54rem;font-weight:400;opacity:.75;margin-top:1px}

/* Colonnes */
.head-col th{font-size:.64rem;font-weight:700;text-align:center;padding:6px 8px;border:1px solid rgba(255,255,255,.1);vertical-align:bottom;white-space:nowrap;line-height:1.3}
.hc{color:#fff}
.hc-proc{background:#2563eb;min-width:130px}
.hc-act{background:#2563eb;min-width:110px}
.hc-os{background:#8b5cf6;min-width:110px}
.hc-oo{background:#8b5cf6;min-width:110px}
.hc-risq{background:#b91c1c;min-width:130px}
.hc-crit{background:#b91c1c;width:88px}
.hc-desc{background:#047857;min-width:160px}
.hc-type{background:#047857;width:88px}
.hc-prev{background:#047857;min-width:110px}
.hc-freq{background:#047857;width:100px}
.hc-resp{background:#047857;min-width:130px}
.hc-prop{background:#047857;min-width:130px}
.hs{display:block;font-size:.54rem;font-weight:400;opacity:.7;margin-top:1px}

/* Lignes */
.rci-row td{padding:5px 7px;border:1px solid #e2e8f0;vertical-align:top;transition:background .1s}
.rci-row:hover td{background:#f8fbff!important}
.row-ret td{background:#fefffe}
.row-nret td{background:#fafafa}
.row-nret td *{color:#94a3b8!important}

/* Bord gauche criticité */
.crit-critique .td-num{border-left:4px solid #7f1d1d!important}
.crit-eleve    .td-num{border-left:4px solid #b91c1c!important}
.crit-modere   .td-num{border-left:4px solid #b45309!important}
.crit-faible   .td-num{border-left:4px solid #15803d!important}

/* Cellule N° */
.td-num{width:52px;text-align:center;vertical-align:middle;padding:6px 4px}
.num-wrap{display:flex;flex-direction:column;align-items:center;gap:4px}
.num-val{font-size:.72rem;font-weight:800;color:#475569}
.ret-toggle{cursor:pointer;font-size:.95rem;line-height:1;transition:all .12s}
.ret-on{color:#15803d}.ret-off{color:#cbd5e1}
.ret-toggle:hover .ret-on{color:#166534}
.ret-toggle:hover .ret-off{color:#94a3b8}

/* Cellules contenu */
.td-proc{min-width:130px}.td-act{min-width:110px}
.td-os,.td-oo{min-width:110px}
.td-risq{min-width:130px}
.td-crit{width:88px;vertical-align:middle;text-align:center}
.td-desc{min-width:160px}
.td-type{width:88px;text-align:center;vertical-align:middle}
.td-prev{min-width:110px}.td-freq{width:100px}
.td-resp,.td-prop{min-width:130px}
.td-act-btn{width:38px;text-align:center;vertical-align:middle}

.cell-v{display:flex;flex-direction:column;gap:3px}
.cv-main{font-size:.7rem;color:#1e293b;line-height:1.4}
.cv-main.small{font-size:.67rem;color:#334155}
.cv-main.pre{white-space:pre-wrap;word-break:break-word}
.cv-empty{color:#cbd5e1;font-size:.68rem}
.tag{display:inline-block;font-size:.56rem;font-weight:700;font-family:ui-monospace,monospace;padding:1px 5px;border-radius:4px;flex-shrink:0}
.tag-proc{background:#dbeafe;color:#1d4ed8}
.tag-act{background:#ede9fe;color:#7c3aed}
.tag-risk{background:#fee2e2;color:#b91c1c}

/* Inputs dans cellule */
.c-inp{width:100%;border:1px solid transparent;background:transparent;font-size:.7rem;font-family:inherit;outline:none;color:#1e293b;padding:2px 4px;border-radius:4px;transition:all .12s;line-height:1.4}
.c-inp:hover{border-color:#e2e8f0;background:#f8fafc}
.c-inp:focus{border-color:#3b82f6;background:#fff;box-shadow:0 0 0 2px rgba(59,130,246,.1)}
.c-ta{width:100%;border:1px solid transparent;background:transparent;font-size:.68rem;font-family:inherit;outline:none;color:#1e293b;resize:none;padding:2px 4px;border-radius:4px;transition:all .12s;line-height:1.5}
.c-ta:hover{border-color:#e2e8f0;background:#f8fafc}
.c-ta:focus{border-color:#3b82f6;background:#fff;box-shadow:0 0 0 2px rgba(59,130,246,.1)}
.c-ta-lg{min-height:60px}
.c-sel{width:100%;border:1px solid transparent;background:transparent;font-size:.68rem;font-family:inherit;outline:none;color:#1e293b;cursor:pointer;padding:2px 4px;border-radius:4px;transition:all .12s}
.c-sel:hover{border-color:#e2e8f0;background:#f8fafc}
.c-sel:focus{border-color:#3b82f6;background:#fff}
.c-sel-prop{color:#7c3aed}

/* Criticité */
.crit-wrap{display:flex;flex-direction:column;align-items:center;gap:5px}
.crit-badge{display:inline-block;font-size:.65rem;font-weight:800;padding:3px 8px;border-radius:6px;text-align:center;min-width:32px}
.crit-critique{background:#7f1d1d;color:#fff}
.crit-eleve{background:#fef2f2;color:#7f1d1d;border:1px solid #fecaca}
.crit-modere{background:#fffbeb;color:#92400e;border:1px solid #fde68a}
.crit-faible{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0}
.crit-nd{background:#f1f5f9;color:#94a3b8;border:1px solid #e2e8f0}
.crit-inputs{display:flex;flex-direction:column;gap:3px;width:100%}
.crit-inp-row{display:flex;align-items:center;gap:4px}
.crit-lbl{font-size:.58rem;font-weight:700;color:#94a3b8;width:10px;flex-shrink:0}
.c-num{width:44px;text-align:center;border:1px solid #e2e8f0;border-radius:4px;font-size:.66rem;padding:2px 4px;outline:none;background:#fff;font-family:inherit;color:#1e293b}
.c-num:focus{border-color:#3b82f6}
.crit-dims-ro{font-size:.62rem;color:#94a3b8}

/* Type badge */
.type-badge{font-size:.62rem;font-weight:700;padding:2px 7px;border-radius:5px}
.type-prev{background:#dbeafe;color:#1d4ed8}
.type-det{background:#ede9fe;color:#7c3aed}
.type-corr{background:#fef3c7;color:#92400e}
.type-dir{background:#f0fdf4;color:#15803d}

/* Actions */
.ibtn{width:28px;height:28px;display:flex;align-items:center;justify-content:center;background:transparent;border:1px solid transparent;border-radius:6px;cursor:pointer;font-size:.8rem;color:#cbd5e1;padding:0;transition:all .12s}
.ibtn-del:hover{color:#dc2626;border-color:#fecaca;background:#fef2f2}

/* Légende */
.crit-legend{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:6px 12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:0 0 10px 10px;font-size:.66rem;color:#64748b}
.leg-item{display:flex;align-items:center;gap:4px}
.leg-sep{color:#e2e8f0;font-size:1rem}
.leg-item .crit-badge{font-size:.56rem;padding:1px 5px}

/* Fonctions entité */
.fn-panel{background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden}
.fn-panel-hdr{display:flex;align-items:center;gap:7px;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#0369a1;background:#f0f9ff;padding:8px 14px;border-bottom:1px solid #e2e8f0}
.fn-count{background:#bae6fd;color:#0369a1;font-size:.6rem;font-weight:800;padding:1px 7px;border-radius:8px;margin-left:4px}
.fn-list{display:flex;flex-wrap:wrap;gap:6px;padding:10px 14px}
.fn-item{display:flex;align-items:center;gap:6px;padding:4px 10px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:6px;font-size:.69rem}
.fn-char{font-family:ui-monospace,monospace;font-size:.62rem;font-weight:800;color:#0369a1;background:#e0f2fe;padding:1px 6px;border-radius:4px}
.fn-name{color:#1e293b;font-weight:500}

/* Cards sections */
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
.btn-add{background:#7e22ce;color:#fff}.btn-add:hover:not(:disabled){background:#6b21a8}
.btn-import{background:#fdf4ff;color:#7e22ce;border:1px solid #e9d5ff}
.btn-sub{background:#1d4ed8;color:#fff}.btn-sub:hover:not(:disabled){background:#1e40af}
.btn-ok{background:#15803d;color:#fff}.btn-ok:hover:not(:disabled){background:#166534}
.btn-rej{background:#fff;color:#dc2626;border:1px solid #fecaca}.btn-rej:hover:not(:disabled){background:#fef2f2}
.btn-sm{padding:5px 11px;font-size:.73rem}
.btn-xs{padding:3px 8px;font-size:.66rem}
.ml-auto{margin-left:auto}
.btn:disabled{opacity:.45;cursor:not-allowed}

/* Empty state */
.empty-state{display:flex;flex-direction:column;align-items:center;gap:8px;padding:40px;color:#94a3b8;text-align:center;background:#fafafa}
.empty-state i{font-size:1.8rem;opacity:.2}
.empty-state p{font-size:.74rem;max-width:320px;line-height:1.6;margin:0}

/* Footer */
.rci-footer{position:sticky;bottom:0;display:flex;align-items:center;justify-content:space-between;padding:10px 20px;background:#fff;border-top:2px solid #e2e8f0;box-shadow:0 -2px 8px rgba(0,0,0,.06);flex-wrap:wrap;gap:8px;z-index:40}
.footer-left,.footer-right{display:flex;gap:6px;flex-wrap:wrap}
.footer-mid{flex:1;display:flex;justify-content:center;align-items:center;gap:12px}
.saved-code{font-size:.72rem;color:#15803d;display:flex;align-items:center;gap:4px;font-weight:700;background:#f0fdf4;padding:3px 10px;border-radius:6px;border:1px solid #bbf7d0}
.stat-lbl{font-size:.7rem;color:#7e22ce;font-weight:700;background:#fdf4ff;padding:3px 10px;border-radius:6px;border:1px solid #e9d5ff}

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