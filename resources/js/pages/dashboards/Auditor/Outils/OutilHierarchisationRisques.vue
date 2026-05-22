<template>
  <VerticalLayoutAudit>
    <div class="vi-root">

      <!-- ══ TOPBAR ══════════════════════════════════════════════ -->
      <div class="vi-bar">
        <a :href="backUrl" class="vi-back"><i class="ti ti-arrow-left"></i></a>
        <div class="vi-bar__id">
          <span class="vi-code">{{ form.code || 'VI-AUTO' }}</span>
          <span class="vi-num">VI</span>
          <span class="vi-name">Hiérarchisation des Risques</span>
        </div>
        <div v-if="hasCtx" class="vi-bar__ctx">
          <span v-if="mc.test_ref" class="vi-ctag vi-ctag--v">{{ mc.test_ref }}</span>
          <span v-if="mc.procedure_code" class="vi-ctag vi-ctag--b">{{ mc.procedure_code }}</span>
          <span v-if="mc.mission_libelle" class="vi-ctag vi-ctag--g">{{ mc.mission_libelle }}</span>
        </div>
        <div class="vi-spacer"></div>
        <span class="vi-st" :class="'vi-st--' + form.statut">
          <i :class="statusIcon(form.statut)"></i>{{ statusLabel(form.statut) }}
        </span>
        <span class="vi-role"><i class="ti ti-shield-half"></i>{{ auditorRole }}</span>
        <template v-if="!isLocked">
          <button class="vi-btn vi-btn--ghost" :disabled="saving" @click="annuler"><i class="ti ti-x"></i></button>
          <button class="vi-btn vi-btn--save" :disabled="saving" @click="submit">
            <span v-if="saving" class="vi-spin"></span>
            <i v-else class="ti ti-device-floppy"></i>{{ form.id ? 'Sauver' : 'Créer' }}
          </button>
          <button v-if="form.id && form.statut === 'draft'" class="vi-btn vi-btn--submit" @click="soumettre" title="Soumettre pour validation">
            <i class="ti ti-send"></i>
          </button>
        </template>
        <template v-if="canManage && form.statut === 'in_review'">
          <button class="vi-btn vi-btn--ok" @click="valider('validated')" title="Valider"><i class="ti ti-check"></i></button>
          <button class="vi-btn vi-btn--ko" @click="promptReject" title="Rejeter"><i class="ti ti-x"></i></button>
        </template>
        
        <!-- ⭐ BOUTON EMAIL - visible dès que la fiche a un ID et un email interlocuteur -->
        <button
          v-if="form.id && form.interlocuteur_email"
          class="vi-btn vi-btn--mail"
          @click="sendValidationEmail"
          title="Envoyer la confirmation par email à l'interlocuteur">
          <i class="ti ti-mail"></i> Envoyer email
        </button>
        <button
          v-else-if="form.id && !form.interlocuteur_email"
          class="vi-btn vi-btn--mail-disabled"
          @click="demanderEmail"
          title="Renseignez d'abord l'email de l'interlocuteur">
          <i class="ti ti-mail"></i> Email manquant
        </button>

        <!-- Bouton test email (optionnel) -->
        <button
          v-if="form.id"
          class="vi-btn vi-btn--test"
          @click="testEmail"
          title="Tester l’envoi d’email (SMTP)">
          <i class="ti ti-mail-search"></i> Test
        </button>
      </div>

      <!-- Banner statut -->
      <div v-if="banner.show" class="vi-banner" :class="'vi-banner--' + banner.type">
        <i :class="banner.icon"></i> <span v-html="banner.msg"></span>
      </div>

      <!-- ══ ONGLETS ══════════════════════════════════════════════ -->
      <div class="vi-tabs">
        <button class="vi-tab" :class="{'vi-tab--on': tab === 'form'}" @click="tab = 'form'">
          <i class="ti ti-shield-half"></i> Risques
          <span v-if="statsRisques.critique > 0" class="vi-tab-alert">{{ statsRisques.critique }}</span>
        </button>
        <button class="vi-tab" :class="{'vi-tab--on': tab === 'docs'}" @click="tab = 'docs'">
          <i class="ti ti-files"></i> Documents &amp; IA
          <span v-if="documents.length" class="vi-tab-n">{{ documents.length }}</span>
        </button>
      </div>

      <!-- ══ CORPS ════════════════════════════════════════════════ -->
      <div class="vi-body">

        <!-- ─── ONGLET RISQUES ────────────────────────────────── -->
        <template v-if="tab === 'form'">

          <!-- Contexte procédure -->
          <div v-if="hasCtx && (mc.libelle_proc || mc.libelle_test || mc.objectif_audit)" class="vi-ctx-strip">
            <div v-if="mc.libelle_test" class="vi-ctx-item">
              <span class="vi-ctx-k">Test</span>
              <span class="vi-ctx-v">{{ mc.libelle_test }}</span>
            </div>
            <div v-if="mc.libelle_proc" class="vi-ctx-item">
              <span class="vi-ctx-k">Procédure</span>
              <span class="vi-ctx-v">{{ mc.libelle_proc }}</span>
            </div>
            <div v-if="mc.objectif_audit" class="vi-ctx-item vi-ctx-item--full">
              <span class="vi-ctx-k">Objectif d'audit</span>
              <span class="vi-ctx-v">{{ mc.objectif_audit }}</span>
            </div>
          </div>

          <!-- Infos générales -->
          <div class="vi-section">
            <div class="vi-hd"><i class="ti ti-info-circle"></i> Informations générales</div>
            <div class="vi-grid">
              <div class="vi-f vi-s2">
                <label>Intitulé <span class="vi-req">*</span></label>
                <input class="vi-inp" v-model="form.intitule" :disabled="isLocked" placeholder="Ex: Hiérarchisation risques Achats" />
              </div>
              <div class="vi-f vi-s2">
                <label>Périmètre</label>
                <input class="vi-inp" v-model="form.perimetre" :disabled="isLocked" placeholder="Ex: Direction des Achats — exercice 2024" />
              </div>
              <div class="vi-f">
                <label>Date d'analyse</label>
                <input type="date" class="vi-inp" v-model="form.date_analyse" :disabled="isLocked" />
              </div>
              <div class="vi-f">
                <label>Email interlocuteur <span class="vi-hint">(pour validation par email)</span></label>
                <input type="email" class="vi-inp" v-model="form.interlocuteur_email" :disabled="isLocked" placeholder="prenom.nom@domaine.tld" />
              </div>
            </div>
          </div>

          <!-- Import depuis mission -->
          <div v-if="risquesMission && risquesMission.length && !isLocked" class="vi-section vi-section--import">
            <div class="vi-hd">
              <i class="ti ti-database-import"></i> Risques de la mission
              <span class="vi-badge">{{ risquesMission.length }}</span>
              <button class="vi-btn-import" @click="importerTousRisques" title="Importer tous les risques de la mission">
                <i class="ti ti-download"></i> Importer tous ({{ risquesMission.length }})
              </button>
            </div>
            <div class="vi-import-list">
              <div v-for="(r, ri) in risquesMission.slice(0, showMoreImport ? 999 : 5)" :key="r.id || ri" class="vi-import-item">
                <div class="vi-import-item__info">
                  <span class="vi-import-code">{{ r.code || r.process_code || '—' }}</span>
                  <span class="vi-import-label">{{ r.label }}</span>
                  <span v-if="r.process_name && r.process_name !== '—'" class="vi-import-proc">{{ r.process_name }}</span>
                </div>
                <div class="vi-import-item__scores">
                  <span class="vi-import-score vi-import-score--f">P:{{ r.frequency_level ?? r.frequency_net ?? '?' }}</span>
                  <span class="vi-import-score vi-import-score--i">I:{{ r.impact_level ?? r.impact_net ?? '?' }}</span>
                  <span class="vi-import-score vi-import-score--c" :class="getCritClass((r.frequency_level??1)*(r.impact_level??1))">
                    {{ (r.frequency_level??1)*(r.impact_level??1) }}
                  </span>
                </div>
                <button v-if="!dejaImporte(r)" class="vi-btn-add-risk" @click="importerRisque(r)" title="Ajouter ce risque">
                  <i class="ti ti-plus"></i>
                </button>
                <span v-else class="vi-imported-badge"><i class="ti ti-check"></i></span>
              </div>
              <button v-if="!showMoreImport && risquesMission.length > 5" class="vi-show-more" @click="showMoreImport = true">
                <i class="ti ti-chevron-down"></i> Voir {{ risquesMission.length - 5 }} autres risques
              </button>
            </div>
          </div>

          <!-- Tableau des risques -->
          <div class="vi-section">
            <div class="vi-hd">
              <i class="ti ti-shield-half"></i> Inventaire &amp; Évaluation des Risques
              <span class="vi-badge">{{ lignes.length }}</span>
              <span v-if="statsRisques.critique > 0" class="vi-pill vi-pill--ko">🔴 {{ statsRisques.critique }}</span>
              <span v-if="statsRisques.eleve > 0" class="vi-pill vi-pill--warn">🟠 {{ statsRisques.eleve }}</span>
              <span v-if="statsRisques.modere > 0" class="vi-pill vi-pill--mod">🟡 {{ statsRisques.modere }}</span>
              <span v-if="statsRisques.faible > 0" class="vi-pill vi-pill--ok">🟢 {{ statsRisques.faible }}</span>
              <button v-if="!isLocked" class="vi-add" @click="addLigne"><i class="ti ti-plus"></i> Risque</button>
            </div>

            <div v-if="!lignes.length" class="vi-empty">
              <i class="ti ti-shield-off"></i>
              <span>Aucun risque — cliquez sur « Risque » ou importez depuis la mission</span>
            </div>

            <div v-else class="vi-twrap">
              <table class="vi-tbl">
                <thead>
                  <tr>
                    <th style="width:28px">#</th>
                    <th style="min-width:200px">Risque identifié</th>
                    <th style="min-width:110px">Causes</th>
                    <th style="min-width:110px">Conséquences</th>
                    <th style="width:90px">Catégorie</th>
                    <th style="width:75px" class="tc">Prob.</th>
                    <th style="width:75px" class="tc">Impact</th>
                    <th style="width:50px" class="tc">P×I</th>
                    <th style="width:70px" class="tc">Niveau</th>
                    <th style="min-width:110px">Traitement</th>
                    <th style="width:90px">Responsable</th>
                    <th style="width:90px">Échéance</th>
                    <th v-if="!isLocked" style="width:26px"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(l, li) in lignes" :key="li"
                    :style="criticite(l) >= 16 ? 'background:#fff5f5' : criticite(l) >= 8 ? 'background:#fffbeb' : ''">
                    <td class="tc vi-n">{{ li + 1 }}</td>
                    <td><textarea class="vi-ta-sm" v-model="l.libelle" rows="2" :disabled="isLocked" placeholder="Libellé du risque"></textarea></td>
                    <td><textarea class="vi-ta-sm" v-model="l.causes" rows="2" :disabled="isLocked" placeholder="Causes..."></textarea></td>
                    <td><textarea class="vi-ta-sm" v-model="l.consequences" rows="2" :disabled="isLocked" placeholder="Conséquences..."></textarea></td>
                    <td><input class="vi-inp-sm" v-model="l.categorie" :disabled="isLocked" placeholder="Opérationnel..." /></td>
                    <td class="tc">
                      <select class="vi-sel-sm" v-model.number="l.probabilite" :disabled="isLocked">
                        <option :value="1">1</option><option :value="2">2</option>
                        <option :value="3">3</option><option :value="4">4</option><option :value="5">5</option>
                      </select>
                    </td>
                    <td class="tc">
                      <select class="vi-sel-sm" v-model.number="l.impact" :disabled="isLocked">
                        <option :value="1">1</option><option :value="2">2</option>
                        <option :value="3">3</option><option :value="4">4</option><option :value="5">5</option>
                      </select>
                    </td>
                    <td class="tc" :class="getCritClass(criticite(l))" style="font-weight:800">
                      {{ criticite(l) }}
                    </td>
                    <td class="tc">
                      <span class="vi-niveau" :class="getNiveauClass(criticite(l))">
                        {{ getNiveauLabel(criticite(l)) }}
                      </span>
                    </td>
                    <td><textarea class="vi-ta-sm" v-model="l.traitement" rows="2" :disabled="isLocked" placeholder="Traitement..."></textarea></td>
                    <td><input class="vi-inp-sm" v-model="l.responsable" :disabled="isLocked" placeholder="Resp." /></td>
                    <td><input type="date" class="vi-inp-sm" v-model="l.echeance" :disabled="isLocked" /></td>
                    <td v-if="!isLocked" class="tc">
                      <button class="vi-del" @click="lignes.splice(li,1)"><i class="ti ti-trash"></i></button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Résultats de l'analyse -->
          <div v-if="lignes.length" class="vi-section vi-section--results">
            <div class="vi-hd"><i class="ti ti-chart-bar"></i> Résultats de l'analyse</div>
            <div class="vi-results-grid">
              <div class="vi-rc vi-rc--total"><div class="vi-rn">{{ lignes.length }}</div><div class="vi-rl">Total</div></div>
              <div class="vi-rc vi-rc--critical"><div class="vi-rn">{{ statsRisques.critique }}</div><div class="vi-rl">Critiques ≥16</div></div>
              <div class="vi-rc vi-rc--elevated"><div class="vi-rn">{{ statsRisques.eleve }}</div><div class="vi-rl">Élevés 8–15</div></div>
              <div class="vi-rc vi-rc--moderate"><div class="vi-rn">{{ statsRisques.modere }}</div><div class="vi-rl">Modérés 4–7</div></div>
              <div class="vi-rc vi-rc--low"><div class="vi-rn">{{ statsRisques.faible }}</div><div class="vi-rl">Faibles ≤3</div></div>
            </div>
            <div v-if="lignesCritiques.length" class="vi-critical-list">
              <div class="vi-critical-hd"><i class="ti ti-alert-triangle"></i> Risques à traiter en priorité</div>
              <div v-for="(l, i) in lignesCritiques" :key="i" class="vi-critical-item">
                <span class="vi-cn">{{ i + 1 }}</span>
                <span class="vi-ct">{{ l.libelle || 'Risque sans libellé' }}</span>
                <span class="vi-cs">Score: {{ criticite(l) }}</span>
              </div>
            </div>
          </div>

          <!-- Résumé IA -->
          <div v-if="iaResult" class="vi-section vi-section--ia">
            <div class="vi-hd">
              <i class="ti ti-sparkles"></i> Analyse IA
              <span class="vi-ia-score">{{ iaResult.score }}/10</span>
              <button class="vi-add vi-add--ghost" @click="tab = 'docs'"><i class="ti ti-files"></i> Détail</button>
            </div>
            <p class="vi-ia-synth">{{ iaResult.synthese }}</p>
          </div>

        </template>

        <!-- ─── ONGLET DOCUMENTS & IA ──────────────────────────── -->
        <template v-if="tab === 'docs'">
          <div class="vi-section">
            <div class="vi-hd">
              <i class="ti ti-files"></i> Documents
              <span class="vi-badge">{{ documents.length }}</span>
              <template v-if="!isLocked">
                <input type="file" ref="fileInput" class="vi-fhidden"
                  accept=".doc,.docx,.odt,.rtf,.pdf,.xls,.xlsx,.csv"
                  @change="handleUpload" />
                <button class="vi-add vi-add--upload" @click="triggerFileUpload" :disabled="uploading">
                  <span v-if="uploading" class="vi-spin vi-spin--d"></span>
                  <i v-else class="ti ti-upload"></i>
                  {{ uploading ? 'Upload…' : 'Importer' }}
                </button>
              </template>
            </div>
            <div v-if="!documents.length" class="vi-empty">
              <i class="ti ti-file-off"></i><span>Aucun document · Word, Excel, PDF (max 10 Mo)</span>
            </div>
            <div v-else class="vi-doclist">
              <div v-for="doc in documents" :key="doc.id" class="vi-doc" :class="'vi-doc--' + doc.status">
                <div class="vi-doc-ico" :class="icoClass(doc.file_extension)">
                  <i :class="fIcon(doc.file_extension)"></i>
                </div>
                <div class="vi-doc-info">
                  <span class="vi-doc-name">{{ doc.original_name }}</span>
                  <span class="vi-doc-meta">{{ fSize(doc.file_size) }} · {{ fDate(doc.created_at) }}</span>
                </div>
                <span class="vi-dst" :class="'vi-dst--' + doc.status">{{ dstLbl(doc.status) }}</span>
                <div class="vi-doc-acts">
                  <button class="vi-da" @click="dlDoc(doc)" title="Télécharger"><i class="ti ti-download"></i></button>
                  <template v-if="canManage">
                    <button class="vi-da vi-da--ok" @click="valDoc(doc,'validated')" title="Valider"><i class="ti ti-check"></i></button>
                    <button class="vi-da vi-da--ko" @click="valDoc(doc,'rejected')" title="Rejeter"><i class="ti ti-x"></i></button>
                  </template>
                  <button v-if="!isLocked" class="vi-da vi-da--del" @click="delDoc(doc)" title="Supprimer"><i class="ti ti-trash"></i></button>
                </div>
              </div>
            </div>
          </div>

          <!-- Panneau IA -->
          <div class="vi-section vi-section--ia-panel">
            <div class="vi-hd">
              <div class="vi-ia-ico"><i class="ti ti-sparkles"></i></div>
              <div>
                <span class="vi-ia-title">Analyse IA · Claude</span>
                <span class="vi-ia-sub">Synthèse automatique de la hiérarchisation des risques</span>
              </div>
              <div class="vi-spacer"></div>
              <button v-if="form.id" class="vi-btn-ia" :disabled="iaLoading" @click="genIa">
                <span v-if="iaLoading" class="vi-spin"></span>
                <i v-else class="ti ti-sparkles"></i>
                {{ iaLoading ? 'Analyse…' : 'Lancer' }}
              </button>
              <span v-else class="vi-ia-hint">Enregistrez d'abord</span>
            </div>

            <template v-if="iaResult">
              <div class="vi-ia-scorebar">
                <span class="vi-ia-scorenum">{{ iaResult.score }}<small>/10</small></span>
                <div class="vi-ia-bar-bg">
                  <div class="vi-ia-bar-fill" :style="{width:(iaResult.score*10)+'%',background:iaResult.score>=7?'#16a34a':iaResult.score>=5?'#d97706':'#dc2626'}"></div>
                </div>
              </div>
              <div class="vi-ia-box">
                <span class="vi-ia-boxlbl">Synthèse</span>
                <p>{{ iaResult.synthese }}</p>
              </div>
              <div class="vi-ia-cols">
                <div v-if="iaResult.risques_majeurs?.length" class="vi-ia-col vi-ia-col--ko">
                  <div class="vi-ia-col-hd"><i class="ti ti-alert-triangle"></i> Risques majeurs</div>
                  <ul><li v-for="(x,i) in iaResult.risques_majeurs" :key="i">{{ x }}</li></ul>
                </div>
                <div v-if="iaResult.points_forts?.length" class="vi-ia-col vi-ia-col--ok">
                  <div class="vi-ia-col-hd"><i class="ti ti-thumb-up"></i> Points forts</div>
                  <ul><li v-for="(x,i) in iaResult.points_forts" :key="i">{{ x }}</li></ul>
                </div>
                <div v-if="iaResult.points_faibles?.length" class="vi-ia-col vi-ia-col--warn">
                  <div class="vi-ia-col-hd"><i class="ti ti-alert-circle"></i> Points faibles</div>
                  <ul><li v-for="(x,i) in iaResult.points_faibles" :key="i">{{ x }}</li></ul>
                </div>
                <div v-if="iaResult.recommandations?.length" class="vi-ia-col vi-ia-col--blue">
                  <div class="vi-ia-col-hd"><i class="ti ti-bulb"></i> Recommandations</div>
                  <ul><li v-for="(x,i) in iaResult.recommandations" :key="i">{{ x }}</li></ul>
                </div>
              </div>
              <div class="vi-ia-foot">
                <button class="vi-add vi-add--ghost" @click="reporterSynthese">
                  <i class="ti ti-file-import"></i> Reporter la synthèse dans le formulaire
                </button>
              </div>
            </template>
            <div v-else class="vi-empty">
              <i class="ti ti-sparkles"></i>
              <span>{{ form.id ? "Cliquez sur Lancer pour analyser" : "Enregistrez d'abord la fiche" }}</span>
            </div>
          </div>

        </template>
      </div>

      <!-- Toast -->
      <Transition name="t">
        <div v-if="toast.show" class="vi-toast" :class="'vi-toast--' + toast.type">
          <i :class="toast.type === 'success' ? 'ti ti-circle-check' : 'ti ti-alert-circle'"></i>
          {{ toast.msg }}
        </div>
      </Transition>

    </div>
  </VerticalLayoutAudit>
</template>

<script setup>
import { reactive, ref, computed, onBeforeUnmount } from 'vue'
import VerticalLayoutAudit from '@/Layouts/VerticalLayoutAudit.vue'

// ── Props ──────────────────────────────────────────────────────
const props = defineProps({
  fiche:                { type: Object,  default: () => ({}) },
  lignes:               { type: Array,   default: () => [] },
  documents:            { type: Array,   default: () => [] },
  iaResult:             { type: Object,  default: null },
  risquesMission:       { type: Array,   default: () => [] },
  auditorRole:          { type: String,  default: 'AS' },
  auditeurNom:          { type: String,  default: '' },
  backUrl:              { type: String,  default: '/' },
  urlStore:             { type: String,  default: '' },
  urlUpdate:            { type: String,  default: null },
  urlSoumettre:         { type: String,  default: null },
  urlValider:           { type: String,  default: null },
  urlIa:                { type: String,  default: null },
  urlUploadDoc:         { type: String,  default: '' },
  urlDownloadDocBase:   { type: String,  default: '' },
  urlValidateDocBase:   { type: String,  default: '' },
  urlDeleteDocBase:     { type: String,  default: '' },
  urlSendValidationEmail: { type: String, default: null },
  missionContext:       { type: Object,  default: () => ({}) },
})

// ── State ──────────────────────────────────────────────────────
const auditorRole = props.auditorRole
const backUrl     = props.backUrl
const mc          = computed(() => props.missionContext ?? {})

const tab  = ref('form')
const form = reactive({
  id: null, code: '', statut: 'draft',
  validation_note: '', validation_status: 'pending',
  interlocuteur_email: '', confirmed_at: null,
  intitule: '', perimetre: '', date_analyse: '',
  ...props.fiche,
})

const lignes   = ref([...(props.lignes ?? [])])
const documents = ref([...(props.documents ?? [])])
const iaResult  = ref(props.iaResult ?? null)

const saving     = ref(false)
const uploading  = ref(false)
const iaLoading  = ref(false)
const fileInput  = ref(null)
const showMoreImport = ref(false)

const toast = reactive({ show: false, type: 'success', msg: '' })
let toastTimer = null

// ── Computed ───────────────────────────────────────────────────
const canManage = computed(() => ['DM','CM'].includes(auditorRole))
const isLocked  = computed(() =>
  form.statut === 'validated' ||
  (form.statut === 'in_review' && !canManage.value) ||
  form.validation_status === 'email_sent'
)
const hasCtx = computed(() => !!(mc.value.test_ref || mc.value.procedure_code))

const banner = computed(() => {
  if (form.validation_status === 'confirmed')
    return { show: true, type: 'ok', icon: 'ti ti-check-circle',
      msg: `✅ <strong>Confirmée par l'interlocuteur</strong> le ${fDate(form.confirmed_at)} — La hiérarchisation des risques a été validée par l'audité.` }
  if (form.validation_status === 'email_sent')
    return { show: true, type: 'mail', icon: 'ti ti-mail',
      msg: `📧 <strong>Email envoyé</strong> à ${form.interlocuteur_email || 'l\'interlocuteur'} — En attente de confirmation.` }
  if (form.statut === 'validated')
    return { show: true, type: 'ok', icon: 'ti ti-lock',
      msg: '🔒 Fiche <strong>validée</strong> — lecture seule' }
  if (form.statut === 'in_review')
    return { show: true, type: 'rev', icon: 'ti ti-clock',
      msg: '⏳ <strong>Soumise pour validation</strong>' + (canManage.value ? ' · DM/CM peut valider ou rejeter' : '') }
  if (form.statut === 'draft' && form.validation_note)
    return { show: true, type: 'ko', icon: 'ti ti-circle-x',
      msg: `❌ <strong>Rejetée</strong> — ${form.validation_note}` }
  return { show: false, type: '', icon: '', msg: '' }
})

const statsRisques = computed(() => {
  let critique=0, eleve=0, modere=0, faible=0
  for (const l of lignes.value) {
    const c = criticite(l)
    if (c >= 16) critique++
    else if (c >= 8) eleve++
    else if (c >= 4) modere++
    else faible++
  }
  return { critique, eleve, modere, faible }
})

const lignesCritiques = computed(() =>
  [...lignes.value].filter(l => criticite(l) >= 16).sort((a,b) => criticite(b) - criticite(a))
)

// ── Helpers ────────────────────────────────────────────────────
function csrf() { return document.querySelector('meta[name=csrf-token]')?.content || '' }
function criticite(l) { return (l.probabilite ?? 1) * (l.impact ?? 1) }
function statusLabel(s) { return {draft:'Brouillon',in_review:'En révision',validated:'Validé',rejected:'Rejeté'}[s] || s }
function statusIcon(s) { return {draft:'ti ti-edit',in_review:'ti ti-clock',validated:'ti ti-lock',rejected:'ti ti-circle-x'}[s] || 'ti ti-file' }
function fDate(d) { return d ? new Date(d).toLocaleDateString('fr-FR') : '' }
function fSize(b) { if (!b) return '—'; const k=1024,i=Math.floor(Math.log(b)/Math.log(k)); return parseFloat((b/Math.pow(k,i)).toFixed(1))+' '+['B','KB','MB','GB'][i] }
function isWord(ext) { return ['doc','docx','odt','rtf'].includes(ext?.toLowerCase()??'') }
function isExcel(ext) { return ['xls','xlsx','csv'].includes(ext?.toLowerCase()??'') }
function fIcon(ext) { return isWord(ext)?'ti ti-file-word':isExcel(ext)?'ti ti-file-spreadsheet':'ti ti-file' }
function icoClass(ext) { return isWord(ext)?'vi-dic--w':isExcel(ext)?'vi-dic--e':'' }
function dstLbl(s) { return {draft:'Brouillon',validated:'Validé',rejected:'Rejeté'}[s]||s }
function dUrl(base, id) { return base?.replace('__DOC__', String(id)) || '' }
function annuler() { window.location.href = backUrl }
function reporterSynthese() {
  if (iaResult.value?.synthese) {
    showToast('success', 'Synthèse IA reportée')
  }
}

function getCritClass(c) {
  if (c >= 16) return 'vi-crit--ko'
  if (c >= 8)  return 'vi-crit--warn'
  if (c >= 4)  return 'vi-crit--mod'
  return 'vi-crit--ok'
}
function getNiveauClass(c) {
  if (c >= 16) return 'vi-niveau--ko'
  if (c >= 8)  return 'vi-niveau--warn'
  if (c >= 4)  return 'vi-niveau--mod'
  return 'vi-niveau--ok'
}
function getNiveauLabel(c) {
  if (c >= 16) return 'Critique'
  if (c >= 8)  return 'Élevé'
  if (c >= 4)  return 'Modéré'
  return 'Faible'
}

function showToast(type, msg, dur = 4000) {
  if (toastTimer) clearTimeout(toastTimer)
  toast.show = true; toast.type = type; toast.msg = msg
  toastTimer = setTimeout(() => { toast.show = false }, dur)
}

// ── Lignes ─────────────────────────────────────────────────────
function addLigne() {
  lignes.value.push({ libelle:'', categorie:'', causes:'', consequences:'', probabilite:1, impact:1, traitement:'', responsable:'', echeance:'', from_mission:false, risk_id:null })
}

function dejaImporte(r) {
  return lignes.value.some(l => l.risk_id && l.risk_id == r.id)
}

function importerRisque(r) {
  if (dejaImporte(r)) return
  lignes.value.push({
    libelle:      r.label || '',
    categorie:    r.risk_type_label || r.process_name || '',
    causes:       r.description || '',
    consequences: '',
    probabilite:  r.frequency_level ?? r.frequency_net ?? 1,
    impact:       r.impact_level    ?? r.impact_net    ?? 1,
    traitement:   r.control_procedure || '',
    responsable:  r.owner || '',
    echeance:     '',
    from_mission: true,
    risk_id:      r.id,
  })
  showToast('success', `"${r.label}" importé.`)
}

function importerTousRisques() {
  let n = 0
  for (const r of props.risquesMission) {
    if (!dejaImporte(r)) { importerRisque(r); n++ }
  }
  if (n > 0) showToast('success', `${n} risque(s) importé(s).`)
  else showToast('error', 'Tous les risques sont déjà dans la liste.')
}

// ── CRUD ───────────────────────────────────────────────────────
async function submit() {
  if (!form.intitule?.trim()) {
    showToast('error', "L'intitulé est obligatoire.");
    return;
  }
  saving.value = true
  try {
    let url = form.id ? props.urlUpdate : props.urlStore
    if (!url) {
      // fallback construction manuelle
      if (form.id) {
        const match = window.location.pathname.match(/\/outil-hierarchisation-risques\/(\d+)/);
        if (match && match[1]) url = `/auditor/ac/outil-hierarchisation-risques/${match[1]}`;
        else throw new Error('Impossible de déterminer l’URL de mise à jour');
      } else {
        url = '/auditor/ac/outil-hierarchisation-risques';
      }
    }
    const lignesValides = lignes.value.filter(l => l.libelle?.trim())
    const missionId = mc.value.real_mission_id ?? mc.value.mission_id
    const payload = {
      mission_id:     missionId,
      assignment_id:  mc.value.assignment_id,
      procedure_code: mc.value.procedure_code,
      test_ref:       mc.value.test_ref,
      obj_num:        mc.value.obj_num,
      intitule:       form.intitule,
      perimetre:      form.perimetre,
      date_analyse:   form.date_analyse,
      interlocuteur_email: form.interlocuteur_email,
      echelle: 5,
      lignes:  lignesValides,
    }
    if (form.id) payload._method = 'PUT'
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':csrf(), 'Accept':'application/json' },
      body: JSON.stringify(payload)
    })
    if (!res.ok) {
      let errorMsg = `HTTP ${res.status}`;
      try { const errData = await res.json(); errorMsg = errData.message || errData.error || errorMsg; } catch(e) {}
      throw new Error(errorMsg);
    }
    const data = await res.json()
    if (data.success) {
      showToast('success', form.id ? 'Mise à jour enregistrée.' : 'Hiérarchisation créée avec succès.')
      if (data.record) {
        form.id = data.record.id
        form.code = data.record.code
        form.statut = data.record.statut
        form.validation_status = data.record.validation_status
        form.interlocuteur_email = data.record.interlocuteur_email
      }
      // Mise à jour des URLs
      if (data.urlUpdate)              props.urlUpdate              = data.urlUpdate
      if (data.urlSoumettre)           props.urlSoumettre           = data.urlSoumettre
      if (data.urlValider)             props.urlValider             = data.urlValider
      if (data.urlIa)                  props.urlIa                  = data.urlIa
      if (data.urlUploadDoc)           props.urlUploadDoc           = data.urlUploadDoc
      if (data.urlDownloadDocBase)     props.urlDownloadDocBase     = data.urlDownloadDocBase
      if (data.urlValidateDocBase)     props.urlValidateDocBase     = data.urlValidateDocBase
      if (data.urlDeleteDocBase)       props.urlDeleteDocBase       = data.urlDeleteDocBase
      if (data.urlSendValidationEmail) props.urlSendValidationEmail = data.urlSendValidationEmail
    } else throw new Error(data.error || 'Erreur serveur')
  } catch(e) { showToast('error', e.message) }
  finally { saving.value = false }
}

async function soumettre() {
  if (!props.urlSoumettre) return
  saving.value = true
  try {
    const res  = await fetch(props.urlSoumettre, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf(),'Accept':'application/json'}, body:'{}' })
    const data = await res.json()
    if (data.success) { form.statut='in_review'; showToast('success','Soumise pour validation.') }
    else throw new Error(data.error)
  } catch(e) { showToast('error', e.message) }
  finally { saving.value = false }
}

async function valider(decision, note) {
  if (!props.urlValider) return
  saving.value = true
  try {
    const res  = await fetch(props.urlValider, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf(),'Accept':'application/json'}, body:JSON.stringify({decision, commentaire:note}) })
    const data = await res.json()
    if (data.success) { form.statut=decision; showToast('success', decision==='validated'?'Fiche validée ✓':'Rejetée.') }
    else throw new Error(data.error)
  } catch(e) { showToast('error', e.message) }
  finally { saving.value = false }
}

function promptReject() {
  const n = prompt('Motif du rejet :')
  if (n?.trim()) valider('rejected', n.trim())
}

// ⭐ Fonction d'envoi d'email (accessible même si non validé)
async function sendValidationEmail() {
  if (!props.urlSendValidationEmail) {
    showToast('error', 'URL d’envoi d’email manquante. Re-sauvegardez la fiche.');
    return;
  }
  let email = form.interlocuteur_email;
  if (!email || !email.trim()) {
    email = prompt("Email de l'interlocuteur :");
    if (!email?.trim()) { showToast('error', 'Email requis.'); return; }
    form.interlocuteur_email = email.trim();
  }
  try {
    const res = await fetch(props.urlSendValidationEmail, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf(),
        'Accept': 'application/json'
      },
      body: JSON.stringify({ email: email.trim() })
    });
    
    // 🔥 LIRE LE CORPS DE LA RÉPONSE MÊME EN CAS D'ERREUR HTTP
    const data = await res.json();
    
    if (!res.ok) {
      // Afficher le message d'erreur renvoyé par le serveur
      const errorMsg = data.error || data.message || `HTTP ${res.status}`;
      throw new Error(errorMsg);
    }
    
    if (data.success) {
      form.validation_status = 'email_sent';
      showToast('success', `Email envoyé à ${email.trim()} — En attente de confirmation.`, 6000);
    } else {
      throw new Error(data.error || 'Erreur inconnue');
    }
  } catch (e) {
    showToast('error', e.message);  // ← affiche le vrai message
  }
}

// Fonction pour demander l'email si le champ est vide
function demanderEmail() {
  const email = prompt("Veuillez renseigner l'email de l'interlocuteur :");
  if (email?.trim()) {
    form.interlocuteur_email = email.trim();
    showToast('info', 'Email ajouté. Sauvegardez la fiche puis envoyez l’email.');
  }
}

// ── TEST EMAIL ─────────────────────────────────────────────────
async function testEmail() {
  const email = form.interlocuteur_email || prompt("Email de test :");
  if (!email?.trim()) { showToast('error', 'Email requis pour le test.'); return; }
  try {
    const res = await fetch('/test-email', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
      body: JSON.stringify({ email })
    });
    const data = await res.json();
    if (data.success) showToast('success', `Email test envoyé à ${email}.`);
    else throw new Error(data.error);
  } catch(e) { showToast('error', `Échec envoi : ${e.message}`); }
}

// ── Documents ──────────────────────────────────────────────────
async function handleUpload(e) {
  const file = e.target.files?.[0];
  if (!file) return;
  const fd = new FormData();
  fd.append('document', file);
  if (mc.value.assignment_id) fd.append('assignment_id', String(mc.value.assignment_id));
  uploading.value = true;
  try {
    const res = await fetch(props.urlUploadDoc, { method:'POST', headers:{'X-CSRF-TOKEN':csrf()}, body:fd });
    const data = await res.json();
    if (data.success && data.document) { documents.value.unshift(data.document); showToast('success','Document importé.'); }
    else throw new Error(data.error||'Erreur');
  } catch(e) { showToast('error', e.message); }
  finally { uploading.value=false; if(fileInput.value) fileInput.value.value=''; }
}

function triggerFileUpload() { fileInput.value?.click(); }

function dlDoc(doc) {
  const url = dUrl(props.urlDownloadDocBase, doc.id);
  if (url) window.open(url,'_blank');
}

async function valDoc(doc, status) {
  let comment = '';
  if (status === 'rejected') { comment = prompt('Motif du rejet :') || ''; if (!comment) return; }
  const url = dUrl(props.urlValidateDocBase, doc.id);
  if (!url) { showToast('error','URL de validation manquante'); return; }
  try {
    const res = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf(),'Accept':'application/json'}, body:JSON.stringify({status,comment}) });
    const data = await res.json();
    if (data.success) {
      const idx = documents.value.findIndex(x => x.id === doc.id);
      if (idx !== -1) documents.value[idx].status = status;
      showToast('success', status === 'validated' ? 'Document validé.' : 'Document rejeté.');
    } else throw new Error(data.error);
  } catch(e) { showToast('error', e.message); }
}

async function delDoc(doc) {
  if (!confirm('Supprimer ce document ?')) return;
  const url = dUrl(props.urlDeleteDocBase, doc.id);
  if (!url) { showToast('error','URL de suppression manquante'); return; }
  try {
    const res = await fetch(url, { method:'DELETE', headers:{'X-CSRF-TOKEN':csrf(),'Accept':'application/json'} });
    const data = await res.json();
    if (data.success) {
      const idx = documents.value.findIndex(x => x.id === doc.id);
      if (idx !== -1) documents.value.splice(idx,1);
      showToast('success','Document supprimé.');
    } else throw new Error(data.error);
  } catch(e) { showToast('error', e.message); }
}

// ── IA ─────────────────────────────────────────────────────────
async function genIa() {
  if (!props.urlIa) return;
  iaLoading.value = true;
  try {
    const res = await fetch(props.urlIa, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf(),'Accept':'application/json'}, body:'{}' });
    const data = await res.json();
    if (data.success) { iaResult.value=data.ia_result; showToast('success','Analyse IA générée.'); }
    else throw new Error(data.error);
  } catch(e) { showToast('error', e.message); }
  finally { iaLoading.value = false; }
}

onBeforeUnmount(() => { if (toastTimer) clearTimeout(toastTimer); })
</script>




<style scoped>
*{box-sizing:border-box}
.vi-root{display:flex;flex-direction:column;height:100vh;overflow:hidden;background:#f0f4f8;font-family:'Segoe UI',system-ui,sans-serif;font-size:.78rem}

/* ── TOPBAR ── */
.vi-bar{display:flex;align-items:center;gap:.35rem;padding:.28rem .7rem;background:#fff;border-bottom:1px solid #e2e8f0;flex-shrink:0;min-height:38px;overflow:hidden}
.vi-back{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border:1px solid #e2e8f0;border-radius:5px;color:#64748b;text-decoration:none;flex-shrink:0}
.vi-back:hover{background:#f1f5f9}
.vi-bar__id{display:flex;align-items:center;gap:.25rem;flex-shrink:0}
.vi-code{background:#0f172a;color:#e2e8f0;padding:1px 6px;border-radius:4px;font-size:.6rem;font-family:monospace;font-weight:600}
.vi-num{background:#dc2626;color:#fff;padding:1px 6px;border-radius:4px;font-size:.6rem;font-weight:700}
.vi-name{font-size:.75rem;font-weight:700;color:#0f172a;white-space:nowrap}
.vi-bar__ctx{display:flex;gap:.2rem;overflow:hidden;flex-wrap:nowrap}
.vi-ctag{padding:1px 6px;border-radius:20px;font-size:.58rem;font-weight:600;white-space:nowrap}
.vi-ctag--v{background:#ede9fe;color:#6d28d9}
.vi-ctag--b{background:#dbeafe;color:#1d4ed8}
.vi-ctag--g{background:#f0fdf4;color:#15803d;max-width:160px;overflow:hidden;text-overflow:ellipsis}
.vi-spacer{flex:1}
.vi-st{display:inline-flex;align-items:center;gap:.15rem;padding:1px 6px;border-radius:20px;font-size:.6rem;font-weight:600;white-space:nowrap;flex-shrink:0}
.vi-st--draft{background:#f1f5f9;color:#64748b}
.vi-st--in_review{background:#dbeafe;color:#1d4ed8}
.vi-st--validated{background:#dcfce7;color:#15803d}
.vi-st--rejected{background:#fee2e2;color:#dc2626}
.vi-role{font-size:.6rem;color:#64748b;padding:1px 6px;border:1px solid #e2e8f0;border-radius:20px;white-space:nowrap;flex-shrink:0}
.vi-btn{display:inline-flex;align-items:center;gap:.18rem;padding:3px 8px;border:none;border-radius:5px;font-size:.68rem;font-weight:600;cursor:pointer;flex-shrink:0;transition:opacity .15s}
.vi-btn:disabled{opacity:.45;cursor:not-allowed}
.vi-btn--ghost{background:#f1f5f9;color:#475569;border:1px solid #e2e8f0}
.vi-btn--save{background:#dc2626;color:#fff}
.vi-btn--save:hover{background:#b91c1c}
.vi-btn--submit{background:#7c3aed;color:#fff;padding:3px 6px}
.vi-btn--ok{background:#15803d;color:#fff;padding:3px 6px}
.vi-btn--ko{background:#dc2626;color:#fff;padding:3px 6px}
.vi-btn--mail{background:#d97706;color:#fff;padding:3px 6px}

/* ── BANNER ── */
.vi-banner{display:flex;align-items:center;gap:.4rem;padding:.3rem .7rem;font-size:.72rem;flex-shrink:0;line-height:1.4}
.vi-banner--ok{background:#d1fae5;color:#065f46;border-bottom:1px solid #a7f3d0}
.vi-banner--rev{background:#dbeafe;color:#1d4ed8;border-bottom:1px solid #bfdbfe}
.vi-banner--ko{background:#fee2e2;color:#dc2626;border-bottom:1px solid #fecaca}
.vi-banner--mail{background:#fef3c7;color:#92400e;border-bottom:1px solid #fde68a}

/* ── ONGLETS ── */
.vi-tabs{display:flex;background:#f8fafc;border-bottom:1px solid #e2e8f0;flex-shrink:0}
.vi-tab{display:inline-flex;align-items:center;gap:.2rem;padding:.3rem .8rem;font-size:.7rem;font-weight:600;color:#64748b;border:none;background:none;border-bottom:2px solid transparent;cursor:pointer;position:relative}
.vi-tab:hover{color:#dc2626;background:#fff5f5}
.vi-tab--on{color:#dc2626;border-bottom-color:#dc2626;background:#fff}
.vi-tab-n{background:#dc2626;color:#fff;padding:0 5px;border-radius:10px;font-size:.56rem}
.vi-tab-alert{background:#dc2626;color:#fff;padding:0 5px;border-radius:10px;font-size:.56rem;animation:pulse 1.5s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.6}}

/* ── BODY ── */
.vi-body{flex:1;overflow-y:auto;padding:.5rem .65rem;display:flex;flex-direction:column;gap:.45rem}

/* ── SECTIONS ── */
.vi-section{background:#fff;border:1px solid #e2e8f0;border-radius:7px;padding:.5rem .6rem}
.vi-section--results{background:linear-gradient(135deg,#fef2f2,#fff);border-left:3px solid #dc2626}
.vi-section--ia{border-left:3px solid #7c3aed;background:#faf5ff}
.vi-section--ia-panel{background:#fff}
.vi-section--import{border-left:3px solid #1d4ed8;background:#eff6ff}
.vi-hd{display:flex;align-items:center;gap:.3rem;font-size:.72rem;font-weight:700;color:#0f172a;margin-bottom:.4rem;flex-wrap:wrap}
.vi-hd i{color:#64748b;font-size:.85rem}
.vi-badge{background:#f1f5f9;border:1px solid #e2e8f0;padding:0 5px;border-radius:10px;font-size:.58rem;font-weight:600}
.vi-pill{padding:1px 7px;border-radius:10px;font-size:.6rem;font-weight:700}
.vi-pill--ko{background:#fee2e2;color:#dc2626}
.vi-pill--warn{background:#fff7ed;color:#d97706}
.vi-pill--mod{background:#fefce8;color:#ca8a04}
.vi-pill--ok{background:#f0fdf4;color:#15803d}

/* ── CONTEXTE ── */
.vi-ctx-strip{background:#faf5ff;border:1px solid #ddd6fe;border-radius:6px;padding:.35rem .55rem;display:flex;gap:.4rem .8rem;flex-wrap:wrap}
.vi-ctx-item{display:flex;flex-direction:column;gap:.03rem;min-width:100px}
.vi-ctx-item--full{flex:1 1 100%}
.vi-ctx-k{font-size:.56rem;font-weight:700;text-transform:uppercase;color:#7c3aed}
.vi-ctx-v{font-size:.68rem;color:#1e293b}

/* ── GRID FORM ── */
.vi-grid{display:grid;grid-template-columns:1fr 1fr;gap:.35rem}
.vi-f{display:flex;flex-direction:column;gap:.12rem}
.vi-f label{font-size:.58rem;font-weight:700;color:#475569;text-transform:uppercase}
.vi-hint{font-weight:400;text-transform:none;font-size:.56rem;color:#94a3b8}
.vi-req{color:#dc2626}
.vi-s2{grid-column:1/-1}
.vi-inp{border:1px solid #e2e8f0;border-radius:5px;padding:4px 6px;font-size:.73rem;width:100%;outline:none;font-family:inherit}
.vi-inp:focus{border-color:#fca5a5}
.vi-inp:disabled{background:#f8fafc;color:#64748b}

/* ── IMPORT MISSION ── */
.vi-btn-import{display:inline-flex;align-items:center;gap:.2rem;padding:2px 8px;background:#1d4ed8;color:#fff;border:none;border-radius:5px;font-size:.65rem;font-weight:600;cursor:pointer;margin-left:auto}
.vi-btn-import:hover{background:#1e3a8a}
.vi-import-list{display:flex;flex-direction:column;gap:.3rem}
.vi-import-item{display:flex;align-items:center;gap:.4rem;padding:.3rem .45rem;background:#f0f9ff;border:1px solid #bae6fd;border-radius:5px}
.vi-import-item__info{flex:1;display:flex;align-items:center;gap:.35rem;min-width:0}
.vi-import-code{background:#1d4ed8;color:#fff;padding:1px 5px;border-radius:3px;font-size:.58rem;font-weight:700;flex-shrink:0;font-family:monospace}
.vi-import-label{font-size:.7rem;font-weight:600;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.vi-import-proc{font-size:.6rem;color:#64748b;white-space:nowrap}
.vi-import-item__scores{display:flex;gap:.2rem;flex-shrink:0}
.vi-import-score{padding:1px 5px;border-radius:4px;font-size:.6rem;font-weight:700}
.vi-import-score--f{background:#dbeafe;color:#1d4ed8}
.vi-import-score--i{background:#ede9fe;color:#6d28d9}
.vi-import-score--c{padding:1px 6px;border-radius:4px;font-size:.62rem;font-weight:800}
.vi-btn-add-risk{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;border-radius:4px;cursor:pointer;font-size:.7rem;flex-shrink:0}
.vi-btn-add-risk:hover{background:#bbf7d0}
.vi-imported-badge{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;background:#f0fdf4;border:1px solid #86efac;color:#15803d;border-radius:4px;font-size:.7rem;flex-shrink:0}
.vi-show-more{background:none;border:none;color:#1d4ed8;font-size:.68rem;cursor:pointer;padding:.25rem;display:flex;align-items:center;gap:.2rem;margin:0 auto}

/* ── TABLEAU RISQUES ── */
.vi-add{display:inline-flex;align-items:center;gap:.18rem;padding:2px 7px;background:#fee2e2;border:1px solid #fecaca;color:#dc2626;border-radius:5px;font-size:.65rem;cursor:pointer;font-weight:600;margin-left:auto}
.vi-add:hover{background:#fecaca}
.vi-add--upload{background:#f0fdf4;border-color:#bbf7d0;color:#15803d}
.vi-add--ghost{background:none;border:1px solid #e2e8f0;color:#475569}
.vi-add--ghost:hover{background:#f1f5f9}
.vi-add:disabled{opacity:.45;cursor:not-allowed}
.vi-empty{display:flex;align-items:center;gap:.35rem;justify-content:center;padding:.8rem;color:#94a3b8;font-size:.7rem;font-style:italic}
.vi-twrap{overflow-x:auto;border:1px solid #e2e8f0;border-radius:5px}
.vi-tbl{width:100%;border-collapse:collapse;font-size:.65rem}
.vi-tbl th{padding:.28rem .35rem;background:#0f172a;color:#fff;font-size:.58rem;font-weight:600;text-align:left;white-space:nowrap}
.vi-tbl td{padding:.22rem .3rem;border-bottom:1px solid #f3f4f6;vertical-align:middle}
.tc{text-align:center}
.vi-n{color:#94a3b8;font-size:.6rem;font-weight:600}
.vi-ta-sm{width:100%;border:1px solid #e5e7eb;border-radius:4px;padding:3px 5px;font-size:.65rem;resize:vertical;font-family:inherit}
.vi-inp-sm{width:100%;border:1px solid #e5e7eb;border-radius:4px;padding:3px 5px;font-size:.65rem;font-family:inherit}
.vi-sel-sm{width:100%;border:1px solid #e5e7eb;border-radius:4px;padding:3px 5px;font-size:.65rem}
.vi-del{background:#fee2e2;border:1px solid #fecaca;color:#dc2626;border-radius:4px;cursor:pointer;padding:2px 4px;font-size:.65rem}
.vi-del:hover{background:#fecaca}

/* Criticité couleurs */
.vi-crit--ko{color:#dc2626}.vi-crit--warn{color:#d97706}.vi-crit--mod{color:#ca8a04}.vi-crit--ok{color:#15803d}
.vi-niveau{display:inline-block;padding:2px 6px;border-radius:8px;font-size:.6rem;font-weight:700;white-space:nowrap}
.vi-niveau--ko{background:#fee2e2;color:#dc2626}
.vi-niveau--warn{background:#fff7ed;color:#d97706}
.vi-niveau--mod{background:#fefce8;color:#ca8a04}
.vi-niveau--ok{background:#f0fdf4;color:#15803d}

/* ── RÉSULTATS ── */
.vi-results-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:.6rem;margin-bottom:.7rem}
.vi-rc{text-align:center;padding:.5rem;border-radius:7px;background:#fff;border:1px solid #e2e8f0}
.vi-rc--total{background:#fef2f2;border-color:#fecaca}
.vi-rc--critical{background:#fee2e2;border-color:#fca5a5}
.vi-rc--elevated{background:#fff7ed;border-color:#fed7aa}
.vi-rc--moderate{background:#fefce8;border-color:#fef08a}
.vi-rc--low{background:#f0fdf4;border-color:#bbf7d0}
.vi-rn{font-size:1.2rem;font-weight:800;color:#0f172a}
.vi-rl{font-size:.55rem;color:#64748b}
.vi-critical-list{margin-top:.6rem;padding:.45rem;background:#fff5f5;border-radius:6px;border-left:3px solid #dc2626}
.vi-critical-hd{font-size:.68rem;font-weight:700;color:#dc2626;margin-bottom:.4rem;display:flex;align-items:center;gap:.3rem}
.vi-critical-item{display:flex;align-items:center;gap:.45rem;padding:.22rem 0;font-size:.68rem;border-bottom:1px solid #fee2e2}
.vi-cn{background:#dc2626;color:#fff;border-radius:50%;width:17px;height:17px;display:inline-flex;align-items:center;justify-content:center;font-size:.58rem;flex-shrink:0}
.vi-ct{flex:1;color:#1e293b}
.vi-cs{font-size:.6rem;color:#dc2626;font-weight:600}

/* ── DOCUMENTS ── */
.vi-fhidden{display:none}
.vi-doclist{display:flex;flex-direction:column;gap:.3rem}
.vi-doc{display:flex;align-items:center;gap:.4rem;padding:.35rem .5rem;background:#f8fafc;border-radius:6px;border:1px solid #e2e8f0}
.vi-doc--validated{border-left:3px solid #15803d;background:#f0fdf4}
.vi-doc--rejected{border-left:3px solid #dc2626;background:#fef2f2}
.vi-doc-ico{width:28px;height:28px;border-radius:5px;display:flex;align-items:center;justify-content:center;font-size:.9rem;background:#dbeafe;color:#1d4ed8;flex-shrink:0}
.vi-dic--w{background:#dbeafe;color:#1d4ed8}.vi-dic--e{background:#e8f5e9;color:#2e7d32}
.vi-doc-info{flex:1;min-width:0;display:flex;flex-direction:column;gap:.02rem}
.vi-doc-name{font-size:.7rem;font-weight:600;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.vi-doc-meta{font-size:.58rem;color:#64748b}
.vi-dst{font-size:.56rem;font-weight:600;padding:1px 5px;border-radius:10px;white-space:nowrap;flex-shrink:0}
.vi-dst--draft{background:#f1f5f9;color:#64748b}
.vi-dst--validated{background:#dcfce7;color:#15803d}
.vi-dst--rejected{background:#fee2e2;color:#dc2626}
.vi-doc-acts{display:flex;gap:.18rem}
.vi-da{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border:1px solid #e2e8f0;border-radius:4px;background:none;cursor:pointer;font-size:.65rem}
.vi-da:hover{background:#f1f5f9}
.vi-da--ok:hover{color:#15803d;border-color:#15803d;background:#f0fdf4}
.vi-da--ko:hover{color:#dc2626;border-color:#dc2626;background:#fef2f2}
.vi-da--del:hover{color:#dc2626;border-color:#fecaca;background:#fef2f2}

/* ── IA ── */
.vi-ia-ico{width:30px;height:30px;background:linear-gradient(135deg,#dc2626,#b91c1c);border-radius:7px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.9rem;flex-shrink:0}
.vi-ia-title{font-size:.72rem;font-weight:700;color:#0f172a;display:block}
.vi-ia-sub{font-size:.6rem;color:#64748b;display:block}
.vi-ia-hint{font-size:.62rem;color:#94a3b8}
.vi-btn-ia{display:inline-flex;align-items:center;gap:.2rem;padding:4px 10px;background:linear-gradient(135deg,#dc2626,#b91c1c);color:#fff;border:none;border-radius:5px;font-size:.68rem;font-weight:600;cursor:pointer;flex-shrink:0}
.vi-btn-ia:disabled{opacity:.45;cursor:not-allowed}
.vi-ia-score{margin-left:.2rem;font-size:.95rem;font-weight:800;color:#dc2626}
.vi-ia-synth{font-size:.7rem;color:#1e293b;margin:0;line-height:1.45}
.vi-ia-scorebar{display:flex;align-items:center;gap:.5rem;padding:.3rem .45rem;background:#f8fafc;border-radius:5px;border:1px solid #e2e8f0;margin-bottom:.4rem}
.vi-ia-scorenum{font-size:1.3rem;font-weight:900;color:#0f172a;flex-shrink:0}
.vi-ia-scorenum small{font-size:.58rem;color:#64748b;font-weight:400}
.vi-ia-bar-bg{flex:1;height:5px;background:#e2e8f0;border-radius:3px;overflow:hidden}
.vi-ia-bar-fill{height:100%;border-radius:3px;transition:width .5s}
.vi-ia-box{padding:.35rem .5rem;border-radius:5px;background:#f8fafc;border-left:3px solid #dc2626;margin-bottom:.4rem}
.vi-ia-boxlbl{font-size:.58rem;font-weight:700;color:#dc2626;text-transform:uppercase;display:block;margin-bottom:.15rem}
.vi-ia-box p{font-size:.7rem;color:#1e293b;margin:0;line-height:1.45}
.vi-ia-cols{display:grid;grid-template-columns:1fr 1fr;gap:.35rem;margin-bottom:.4rem}
.vi-ia-col{border-radius:5px;padding:.35rem .5rem}
.vi-ia-col--ok{background:#f0fdf4;border:1px solid #bbf7d0}
.vi-ia-col--warn{background:#fffbeb;border:1px solid #fde68a}
.vi-ia-col--ko{background:#fef2f2;border:1px solid #fecaca}
.vi-ia-col--blue{background:#eff6ff;border:1px solid #bfdbfe}
.vi-ia-col-hd{display:flex;align-items:center;gap:.2rem;font-size:.62rem;font-weight:700;margin-bottom:.2rem}
.vi-ia-col--ok .vi-ia-col-hd{color:#15803d}
.vi-ia-col--warn .vi-ia-col-hd{color:#92400e}
.vi-ia-col--ko .vi-ia-col-hd{color:#dc2626}
.vi-ia-col--blue .vi-ia-col-hd{color:#1d4ed8}
.vi-ia-col ul{margin:0;padding-left:.8rem}
.vi-ia-col li{font-size:.65rem;color:#1e293b;margin-bottom:.12rem}
.vi-ia-foot{display:flex;padding-top:.35rem;border-top:1px solid #e2e8f0}

/* ── SPINNER / TOAST ── */
.vi-spin{display:inline-block;width:10px;height:10px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite}
.vi-spin--d{border:2px solid rgba(0,0,0,.1);border-top-color:#15803d}
@keyframes spin{to{transform:rotate(360deg)}}
.vi-toast{position:fixed;bottom:.75rem;right:.75rem;display:flex;align-items:center;gap:.3rem;padding:.45rem .9rem;border-radius:7px;font-size:.72rem;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,.15);max-width:420px}
.vi-toast--success{background:#dcfce7;color:#15803d}
.vi-toast--error{background:#fee2e2;color:#dc2626}
.t-enter-active,.t-leave-active{transition:all .25s}
.t-enter-from,.t-leave-to{opacity:0;transform:translateY(6px)}

@media(max-width:700px){
  .vi-grid{grid-template-columns:1fr}
  .vi-s2{grid-column:1}
  .vi-ia-cols{grid-template-columns:1fr}
  .vi-results-grid{grid-template-columns:repeat(3,1fr)}
  .vi-bar__ctx{display:none}
}
</style>