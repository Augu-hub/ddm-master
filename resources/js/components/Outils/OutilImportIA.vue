<template>
  <!-- ══════════════════════════════════════════════════════════════
       MODAL IMPORT EXCEL + SUGGESTIONS IA — OUTILS IFACI I→V
       Usage: <OutilImportIA v-model:visible="showImportIA" :outil-code="'I'" :contexte="contexteTest" @apply="onApply" />
  ══════════════════════════════════════════════════════════════ -->
  <Teleport to="body">
    <Transition name="oia-fade">
      <div v-if="visible" class="oia-overlay" @click.self="close">
        <div class="oia-panel" :style="`--oia-accent:${accentColor}`">

          <!-- ── HEADER ────────────────────────────────────────────── -->
          <div class="oia-header">
            <div class="oia-header__left">
              <div class="oia-header__badge" :style="`background:${accentColor}`">{{ outilCode }}</div>
              <div>
                <div class="oia-header__title">{{ outilMeta.label }}</div>
                <div class="oia-header__sub">Import Excel IFACI · Suggestions IA</div>
              </div>
            </div>
            <button class="oia-close" @click="close"><i class="ti ti-x"></i></button>
          </div>

          <!-- ── TABS ──────────────────────────────────────────────── -->
          <div class="oia-tabs">
            <button class="oia-tab" :class="tab==='ia'?'oia-tab--active':''" @click="tab='ia'">
              <i class="ti ti-sparkles"></i> Suggestions IA
            </button>
            <button class="oia-tab" :class="tab==='import'?'oia-tab--active':''" @click="tab='import'">
              <i class="ti ti-file-spreadsheet"></i> Import Excel
            </button>
            <button class="oia-tab" :class="tab==='template'?'oia-tab--active':''" @click="tab='template'">
              <i class="ti ti-download"></i> Télécharger modèle
            </button>
          </div>

          <!-- ══ TAB : SUGGESTIONS IA ════════════════════════════════ -->
          <div v-if="tab==='ia'" class="oia-body">

            <!-- Contexte affiché -->
            <div class="oia-ctx-banner">
              <i class="ti ti-info-circle"></i>
              <span v-if="contexte.testLibelle"><strong>Test :</strong> {{ contexte.testLibelle }}</span>
              <span v-if="contexte.objectif"><strong>Objectif :</strong> {{ contexte.objectif }}</span>
              <span v-if="contexte.procedure"><strong>Procédure :</strong> {{ contexte.procedure }}</span>
              <span v-if="contexte.axeRado" class="oia-ctx-purple">{{ contexte.axeRado }}</span>
            </div>

            <!-- Bouton générer -->
            <div class="oia-ia-actions">
              <button class="oia-btn-generate" :disabled="iaLoading" @click="genererIA">
                <span v-if="iaLoading" class="oia-spinner"></span>
                <i v-else class="ti ti-brain"></i>
                {{ iaLoading ? 'Génération en cours…' : 'Générer les suggestions' }}
              </button>
              <span class="oia-ia-hint">L'IA analyse le contexte du test pour proposer un contenu pré-rempli.</span>
            </div>

            <!-- Résultat IA -->
            <div v-if="iaError" class="oia-ia-error">
              <i class="ti ti-alert-circle"></i> {{ iaError }}
            </div>

            <div v-if="iaResult && !iaLoading" class="oia-ia-result">

              <!-- ─ OUTIL I ──────────────────────────────────────── -->
              <template v-if="outilCode==='I'">
                <div class="oia-section">
                  <div class="oia-section__title"><i class="ti ti-target"></i> Objectif d'audit suggéré</div>
                  <div class="oia-ia-text">{{ iaResult.objectif_audit }}</div>
                </div>
                <div class="oia-section">
                  <div class="oia-section__title"><i class="ti ti-list-check"></i> Questions suggérées ({{ iaResult.questions?.length || 0 }})</div>
                  <div class="oia-questions-preview">
                    <div v-for="(q, qi) in iaResult.questions" :key="qi" class="oia-q-item">
                      <span class="oia-q-badge" :style="`background:${accentColor}`">{{ q.type?.slice(0,3) }}</span>
                      <div class="oia-q-content">
                        <div class="oia-q-text">{{ q.question }}</div>
                        <div v-if="q.reponse" class="oia-q-reponse">→ {{ q.reponse }}</div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="oia-section">
                  <div class="oia-section__title"><i class="ti ti-notes"></i> Synthèse suggérée</div>
                  <div class="oia-ia-text">{{ iaResult.synthese }}</div>
                </div>
              </template>

              <!-- ─ OUTIL II ─────────────────────────────────────── -->
              <template v-else-if="outilCode==='II'">
                <div class="oia-section">
                  <div class="oia-section__title"><i class="ti ti-users"></i> Acteurs suggérés</div>
                  <div class="oia-chips-row">
                    <span v-for="(a, ai) in iaResult.acteurs" :key="ai" class="oia-chip" :style="`border-color:${acteurColors[ai%acteurColors.length]};color:${acteurColors[ai%acteurColors.length]}`">
                      A{{ ai+1 }} — {{ a }}
                    </span>
                  </div>
                </div>
                <div class="oia-section">
                  <div class="oia-section__title"><i class="ti ti-list"></i> Tâches et rôles RACI ({{ iaResult.taches?.length || 0 }})</div>
                  <div class="oia-raci-preview">
                    <div v-for="(t, ti) in iaResult.taches" :key="ti" class="oia-raci-row">
                      <span class="oia-raci-num">{{ ti+1 }}</span>
                      <span class="oia-raci-label">{{ t.libelle }}</span>
                      <div class="oia-raci-roles">
                        <span v-for="(r, ri) in t.roles" :key="ri"
                              class="oia-raci-badge"
                              :class="r?`oia-raci--${r.toLowerCase()}`:'oia-raci--empty'">
                          {{ r || '·' }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="oia-section">
                  <div class="oia-section__title"><i class="ti ti-alert-triangle"></i> Observations séparation des fonctions</div>
                  <div class="oia-ia-text">{{ iaResult.observations }}</div>
                </div>
              </template>

              <!-- ─ OUTIL III ────────────────────────────────────── -->
              <template v-else-if="outilCode==='III'">
                <div class="oia-section">
                  <div class="oia-section__title"><i class="ti ti-list-numbers"></i> Activités du processus ({{ iaResult.activites?.length || 0 }})</div>
                  <div class="oia-table-preview">
                    <div class="oia-table-head">
                      <span style="flex:2">Activité</span><span style="flex:1">Acteur</span><span style="flex:1">Entrant</span><span style="flex:1">Sortant</span><span style="flex:1">Documents</span>
                    </div>
                    <div v-for="(a, ai) in iaResult.activites" :key="ai" class="oia-table-row" :class="ai%2===0?'':'oia-table-row--alt'">
                      <span style="flex:2">{{ a.libelle }}</span><span style="flex:1">{{ a.acteur }}</span><span style="flex:1">{{ a.entrant }}</span><span style="flex:1">{{ a.sortant }}</span><span style="flex:1">{{ a.documents }}</span>
                    </div>
                  </div>
                </div>
                <div class="oia-section">
                  <div class="oia-section__title"><i class="ti ti-align-left"></i> Description narrative</div>
                  <div class="oia-ia-text">{{ iaResult.description_narrative }}</div>
                </div>
              </template>

              <!-- ─ OUTIL IV ─────────────────────────────────────── -->
              <template v-else-if="outilCode==='IV'">
                <div v-for="tp in ['realisation','management','support']" :key="tp" class="oia-section">
                  <div class="oia-section__title">
                    <span :style="`color:${typeProcessusColor(tp)}`">{{ typeProcessusLabel(tp) }}</span>
                    — {{ iaResult.processus?.[tp]?.length || 0 }} processus
                  </div>
                  <div v-if="iaResult.processus?.[tp]?.length" class="oia-procs-preview">
                    <div v-for="(p, pi) in iaResult.processus[tp]" :key="pi" class="oia-proc-item">
                      <span class="oia-proc-nom" :style="`color:${typeProcessusColor(tp)}`">{{ p.nom }}</span>
                      <span class="oia-proc-fin">{{ p.finalite }}</span>
                    </div>
                  </div>
                  <div v-else class="oia-empty-hint">Aucun processus de ce type suggéré.</div>
                </div>
              </template>

              <!-- ─ OUTIL V ──────────────────────────────────────── -->
              <template v-else-if="outilCode==='V'">
                <div class="oia-section">
                  <div class="oia-section__title"><i class="ti ti-route"></i> Transaction sélectionnée</div>
                  <div class="oia-ia-text">{{ iaResult.transaction }}</div>
                </div>
                <div class="oia-section">
                  <div class="oia-section__title"><i class="ti ti-list-numbers"></i> Étapes du cheminement ({{ iaResult.etapes?.length || 0 }})</div>
                  <div class="oia-table-preview">
                    <div class="oia-table-head">
                      <span style="flex:3">Description</span><span style="flex:1">Acteur</span><span style="flex:1">Document</span><span style="flex:1">Contrôle</span><span style="flex:1">Conforme</span>
                    </div>
                    <div v-for="(e, ei) in iaResult.etapes" :key="ei" class="oia-table-row" :class="ei%2===0?'':'oia-table-row--alt'">
                      <span style="flex:3">{{ e.description }}</span><span style="flex:1">{{ e.acteur }}</span><span style="flex:1">{{ e.document }}</span>
                      <span style="flex:1">
                        <span class="oia-badge-mini" :class="e.controle==='Oui'?'oia-badge--ok':e.controle==='Non'?'oia-badge--ko':''">{{ e.controle || '—' }}</span>
                      </span>
                      <span style="flex:1">
                        <span class="oia-badge-mini" :class="e.conforme==='Oui'?'oia-badge--ok':e.conforme==='Non'?'oia-badge--ko':e.conforme==='Écart'?'oia-badge--warn':''">{{ e.conforme || '—' }}</span>
                      </span>
                    </div>
                  </div>
                </div>
                <div class="oia-section">
                  <div class="oia-section__title"><i class="ti ti-clipboard-text"></i> Conclusion suggérée</div>
                  <div class="oia-ia-text">{{ iaResult.conclusion }}</div>
                </div>
              </template>

              <!-- Bouton appliquer -->
              <div class="oia-apply-bar">
                <span class="oia-apply-hint"><i class="ti ti-info-circle"></i> Les données seront pré-remplies dans le formulaire. Vous pourrez les modifier.</span>
                <button class="oia-btn-apply" @click="appliquer">
                  <i class="ti ti-check"></i> Appliquer les suggestions
                </button>
              </div>
            </div>
          </div>

          <!-- ══ TAB : IMPORT EXCEL ═══════════════════════════════════ -->
          <div v-if="tab==='import'" class="oia-body">

            <div class="oia-import-zone"
                 :class="isDragging?'oia-import-zone--drag':''"
                 @dragover.prevent="isDragging=true"
                 @dragleave="isDragging=false"
                 @drop.prevent="onDrop">
              <i class="ti ti-file-spreadsheet oia-import-icon"></i>
              <div class="oia-import-title">Glissez votre fichier Excel ici</div>
              <div class="oia-import-sub">ou cliquez pour sélectionner un fichier .xlsx / .csv / .tsv</div>
              <label class="oia-btn-browse">
                <i class="ti ti-upload"></i> Parcourir
                <input type="file" accept=".xlsx,.csv,.tsv" class="oia-file-hidden" @change="onFileChange" />
              </label>
            </div>

            <!-- Infos colonnes attendues -->
            <div class="oia-columns-info">
              <div class="oia-columns-title">
                <i class="ti ti-table"></i> Colonnes attendues — Outil {{ outilCode }} (feuille IFACI)
              </div>
              <div class="oia-columns-grid">
                <div v-for="col in expectedColumns" :key="col.col" class="oia-col-item">
                  <span class="oia-col-letter" :style="`background:${accentColor}`">{{ col.col }}</span>
                  <span class="oia-col-label">{{ col.label }}</span>
                </div>
              </div>
            </div>

            <!-- Preview import -->
            <div v-if="importPreview.length" class="oia-preview-wrap">
              <div class="oia-preview-header">
                <span class="oia-preview-count">{{ importPreview.length }} ligne(s) détectée(s)</span>
                <button class="oia-btn-clear" @click="importPreview=[];importFile=null">
                  <i class="ti ti-x"></i> Effacer
                </button>
              </div>
              <div class="oia-preview-table-wrap">
                <table class="oia-preview-table">
                  <thead>
                    <tr>
                      <th>N°</th>
                      <th v-for="col in expectedColumns" :key="col.col">{{ col.label }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(row, ri) in importPreview.slice(0,8)" :key="ri">
                      <td>{{ ri+1 }}</td>
                      <td v-for="col in expectedColumns" :key="col.col">{{ row[col.field] || '—' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div v-if="importPreview.length > 8" class="oia-preview-more">
                + {{ importPreview.length - 8 }} ligne(s) supplémentaire(s) non affichées
              </div>

              <div class="oia-apply-bar oia-apply-bar--import">
                <span class="oia-apply-hint"><i class="ti ti-info-circle"></i> {{ importPreview.length }} enregistrement(s) seront importés dans l'outil.</span>
                <button class="oia-btn-apply" @click="appliquerImport">
                  <i class="ti ti-table-import"></i> Importer dans l'outil
                </button>
              </div>
            </div>

            <div v-if="importError" class="oia-ia-error mt-2">
              <i class="ti ti-alert-circle"></i> {{ importError }}
            </div>
          </div>

          <!-- ══ TAB : TÉLÉCHARGER MODÈLE ════════════════════════════ -->
          <div v-if="tab==='template'" class="oia-body">
            <div class="oia-template-hero">
              <i class="ti ti-file-spreadsheet oia-template-icon" :style="`color:${accentColor}`"></i>
              <div class="oia-template-title">Modèle Excel — Outil {{ outilCode }}</div>
              <div class="oia-template-sub">{{ outilMeta.label }}</div>
              <div class="oia-template-desc">
                Téléchargez le modèle Excel IFACI pré-formaté pour l'Outil {{ outilCode }}.
                Remplissez-le selon vos données, puis importez-le via l'onglet "Import Excel".
              </div>
            </div>

            <!-- Aperçu structure -->
            <div class="oia-template-preview">
              <div class="oia-template-preview__header" :style="`background:${accentColor}`">
                <span>Feuille : {{ outilMeta.sheetName }}</span>
                <span>{{ expectedColumns.length }} colonnes</span>
              </div>
              <div class="oia-template-cols">
                <div v-for="(col, ci) in expectedColumns" :key="ci" class="oia-tcol">
                  <div class="oia-tcol__letter" :style="`background:${accentColor}22;color:${accentColor}`">{{ col.col }}</div>
                  <div class="oia-tcol__label">{{ col.label }}</div>
                  <div v-if="col.example" class="oia-tcol__example">ex: {{ col.example }}</div>
                </div>
              </div>
            </div>

            <div class="oia-apply-bar">
              <button class="oia-btn-apply" @click="telechargerModele">
                <i class="ti ti-download"></i> Télécharger le modèle .xlsx
              </button>
            </div>
          </div>

        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'

// ─── Props & Emits ──────────────────────────────────────────────
const props = withDefaults(defineProps<{
  visible: boolean
  outilCode: string   // 'I' | 'II' | 'III' | 'IV' | 'V'
  contexte?: {
    testLibelle?: string
    objectif?: string
    procedure?: string
    axeRado?: string
    missionLibelle?: string
    processus?: string
    priorite?: string
    risques?: string
    auditeurNom?: string
  }
}>(), { contexte: () => ({}) })

const emit = defineEmits<{
  (e: 'update:visible', v: boolean): void
  (e: 'apply', data: any): void
}>()

// ─── State ──────────────────────────────────────────────────────
const tab         = ref<'ia'|'import'|'template'>('ia')
const iaLoading   = ref(false)
const iaError     = ref('')
const iaResult    = ref<any>(null)
const isDragging  = ref(false)
const importPreview = ref<any[]>([])
const importError   = ref('')
const importFile    = ref<File|null>(null)

// Reset quand l'outil change
watch(() => props.outilCode, () => {
  iaResult.value = null; iaError.value = ''; importPreview.value = []; importError.value = ''
})
watch(() => props.visible, (v) => { if (v) { tab.value = 'ia'; iaResult.value = null } })

// ─── Méta outils ────────────────────────────────────────────────
const OUTILS_META: Record<string, { label: string; color: string; sheetName: string }> = {
  'I':   { label: 'Grille d\'Entretien',          color: '#1e40af', sheetName: 'I-Entretien' },
  'II':  { label: 'Grille Analyse des Tâches',    color: '#065f46', sheetName: 'II-Grille Analyse Tâches' },
  'III': { label: 'Diagramme de Flux',             color: '#6d28d9', sheetName: 'III-Diagramme de Flux' },
  'IV':  { label: 'Approche Processus',            color: '#b45309', sheetName: 'IV-Approche Processus' },
  'V':   { label: 'Test de Cheminement',           color: '#be185d', sheetName: 'V-Test de Cheminement' },
}

const outilMeta   = computed(() => OUTILS_META[props.outilCode] ?? { label: 'Outil', color: '#0f172a', sheetName: '' })
const accentColor = computed(() => outilMeta.value.color)

const acteurColors = ['#1e40af','#065f46','#6d28d9','#b45309','#be185d','#0f172a','#047857','#7c3aed']

// ─── Colonnes attendues par outil ────────────────────────────────
const COLUMNS: Record<string, { col: string; field: string; label: string; example?: string }[]> = {
  'I': [
    { col: 'A', field: 'type',     label: 'Type de question',    example: 'Ouverte / Fermée / Factuelle / Rebond' },
    { col: 'B', field: 'question', label: 'Question (QQOCPQ)',   example: 'Comment les factures sont-elles traitées ?' },
    { col: 'C', field: 'reponse',  label: 'Réponse / Observation', example: 'La procédure prévoit une validation…' },
  ],
  'II': [
    { col: 'A', field: 'libelle', label: 'Tâche du processus',   example: 'Réception de la commande' },
    { col: 'B', field: 'a1',      label: 'Acteur 1 (R/A/C/I)',   example: 'R' },
    { col: 'C', field: 'a2',      label: 'Acteur 2 (R/A/C/I)',   example: 'A' },
    { col: 'D', field: 'a3',      label: 'Acteur 3 (R/A/C/I)',   example: 'C' },
    { col: 'E', field: 'a4',      label: 'Acteur 4 (R/A/C/I)',   example: 'I' },
    { col: 'F', field: 'a5',      label: 'Acteur 5 (R/A/C/I)',   example: '' },
    { col: 'G', field: 'a6',      label: 'Acteur 6 (R/A/C/I)',   example: '' },
    { col: 'H', field: 'a7',      label: 'Acteur 7 (R/A/C/I)',   example: '' },
    { col: 'I', field: 'a8',      label: 'Acteur 8 (R/A/C/I)',   example: '' },
  ],
  'III': [
    { col: 'A', field: 'libelle',   label: 'Activité',             example: 'Réception bon de commande' },
    { col: 'B', field: 'acteur',    label: 'Acteur responsable',   example: 'Responsable Achats' },
    { col: 'C', field: 'entrant',   label: 'Élément entrant',      example: 'Bon de commande signé' },
    { col: 'D', field: 'sortant',   label: 'Élément sortant',      example: 'Commande enregistrée' },
    { col: 'E', field: 'documents', label: 'Documents',            example: 'Formulaire F-ACH-01' },
  ],
  'IV': [
    { col: 'A', field: 'type',         label: 'Type (realisation/management/support)', example: 'realisation' },
    { col: 'B', field: 'nom',          label: 'Nom du processus',       example: 'Gestion des achats' },
    { col: 'C', field: 'finalite',     label: 'Finalité',               example: 'Assurer l\'approvisionnement' },
    { col: 'D', field: 'entrants',     label: 'Éléments entrants',      example: 'Demandes d\'achat' },
    { col: 'E', field: 'sortants',     label: 'Éléments sortants',      example: 'Bons de commande' },
    { col: 'F', field: 'activites',    label: 'Activités principales',  example: 'Réception, validation, envoi' },
    { col: 'G', field: 'clients',      label: 'Clients du processus',   example: 'Direction Financière' },
    { col: 'H', field: 'fournisseurs', label: 'Fournisseurs',           example: 'Direction Commerciale' },
  ],
  'V': [
    { col: 'A', field: 'description', label: 'Description de l\'activité', example: 'Réception de la facture fournisseur' },
    { col: 'B', field: 'acteur',      label: 'Acteur responsable',         example: 'Comptable Fournisseur' },
    { col: 'C', field: 'document',    label: 'Document / Système',         example: 'ERP SAP / Facture N°' },
    { col: 'D', field: 'controle',    label: 'Contrôle appliqué (Oui/Non/N/A)', example: 'Oui' },
    { col: 'E', field: 'conforme',    label: 'Conforme procédure (Oui/Non/Écart)', example: 'Écart' },
    { col: 'F', field: 'observation', label: 'Observation / Écart',        example: 'Délai de validation dépassé' },
    { col: 'G', field: 'preuve',      label: 'Preuve collectée',           example: 'Facture N°12345 scannée' },
  ],
}

const expectedColumns = computed(() => COLUMNS[props.outilCode] ?? [])

// ─── Helpers processus IV ────────────────────────────────────────
function typeProcessusColor(tp: string) {
  return tp==='realisation' ? '#065f46' : tp==='management' ? '#1e40af' : '#6d28d9'
}
function typeProcessusLabel(tp: string) {
  return tp==='realisation' ? 'Processus de Réalisation' : tp==='management' ? 'Processus de Management' : 'Processus de Support'
}

// ─── GÉNÉRATION IA ──────────────────────────────────────────────
async function genererIA() {
  iaLoading.value = true
  iaError.value   = ''
  iaResult.value  = null

  const ctx = props.contexte
  const systemPrompt = buildSystemPrompt()
  const userPrompt   = buildUserPrompt(ctx)

  try {
    const res = await fetch('/api/mistral/suggest', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({
        outil_code: props.outilCode,
        contexte:   ctx,
        system:     systemPrompt,
        prompt:     userPrompt,
      }),
    })

    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    const d = await res.json()

    // Le backend retourne { suggestion: "{json string}" } ou { suggestion: {object} }
    let suggestion = d.suggestion ?? d.data ?? d
    if (typeof suggestion === 'string') {
      // Nettoyer les backticks JSON éventuels
      suggestion = suggestion.replace(/```json\s*/gi, '').replace(/```\s*/g, '').trim()
      suggestion = JSON.parse(suggestion)
    }
    iaResult.value = suggestion
  } catch (e: any) {
    iaError.value = `Erreur lors de la génération IA : ${e?.message ?? 'inconnue'}. Vérifiez la connexion au service Mistral.`
  } finally {
    iaLoading.value = false
  }
}

function buildSystemPrompt(): string {
  const schemas: Record<string, string> = {
    'I': `Tu es un expert en audit interne IFACI. Génère un JSON pour la Grille d'Entretien (Outil I) avec les champs suivants UNIQUEMENT, sans texte avant/après :
{
  "objectif_audit": "string",
  "interlocuteurs": "string",
  "questions": [{"type":"Ouverte|Fermée|Factuelle|Rebond","question":"string","reponse":"string"}],
  "synthese": "string",
  "sig_auditeur": "string"
}
Génère 4 à 6 questions pertinentes de types variés (QQOCPQ). Réponse JSON pur uniquement.`,

    'II': `Tu es un expert en audit interne IFACI. Génère un JSON pour la Grille d'Analyse des Tâches (Outil II) avec les champs suivants UNIQUEMENT :
{
  "processus": "string",
  "acteurs": ["string","string","string"],
  "taches": [{"libelle":"string","roles":["R|A|C|I|","",...]}],
  "observations": "string"
}
Les rôles sont dans l'ordre des acteurs (R=Réalise, A=Approuve, C=Consulté, I=Informé, ou vide). Génère 5 à 8 tâches réalistes. JSON pur uniquement.`,

    'III': `Tu es un expert en audit interne IFACI. Génère un JSON pour le Diagramme de Flux (Outil III) :
{
  "processus": "string",
  "version": "V1",
  "activites": [{"libelle":"string","acteur":"string","entrant":"string","sortant":"string","documents":"string"}],
  "description_narrative": "string",
  "synthese_validations": "string"
}
Génère 5 à 7 activités séquentielles. JSON pur uniquement.`,

    'IV': `Tu es un expert en audit interne IFACI. Génère un JSON pour l'Approche Processus (Outil IV) :
{
  "domaine": "string",
  "processus": {
    "realisation": [{"nom":"string","finalite":"string","entrants":"string","sortants":"string","activites":"string","clients":"string","fournisseurs":"string"}],
    "management": [...],
    "support": [...]
  }
}
Génère 2 à 3 processus par type. JSON pur uniquement.`,

    'V': `Tu es un expert en audit interne IFACI. Génère un JSON pour le Test de Cheminement (Outil V) :
{
  "transaction": "string",
  "processus": "string",
  "etapes": [{"description":"string","acteur":"string","document":"string","controle":"Oui|Non|N/A","conforme":"Oui|Non|Écart","observation":"string","preuve":"string"}],
  "reponses_verification": [{"statut":"oui|non|partiel|na","commentaire":"string"}],
  "synthese_ecarts": "string",
  "conclusion": "string"
}
Génère 6 à 8 étapes réalistes. Les reponses_verification contiennent exactement 5 éléments. JSON pur uniquement.`,
  }
  return schemas[props.outilCode] ?? 'Réponds en JSON pur.'
}

function buildUserPrompt(ctx: any): string {
  const parts = []
  if (ctx.missionLibelle) parts.push(`Mission : ${ctx.missionLibelle}`)
  if (ctx.objectif)       parts.push(`Objectif d'audit : ${ctx.objectif}`)
  if (ctx.testLibelle)    parts.push(`Test : ${ctx.testLibelle}`)
  if (ctx.procedure)      parts.push(`Procédure : ${ctx.procedure}`)
  if (ctx.axeRado)        parts.push(`Axe RADO : ${ctx.axeRado}`)
  if (ctx.processus)      parts.push(`Processus audité : ${ctx.processus}`)
  if (ctx.risques)        parts.push(`Risques identifiés : ${ctx.risques}`)
  if (ctx.auditeurNom)    parts.push(`Auditeur : ${ctx.auditeurNom}`)
  if (ctx.priorite)       parts.push(`Priorité : ${ctx.priorite}`)
  return `Génère le contenu de l'Outil ${props.outilCode} pour ce contexte d'audit :\n\n${parts.join('\n')}\n\nJSON pur uniquement.`
}

// ─── IMPORT EXCEL / CSV / TSV ────────────────────────────────────
function onDrop(e: DragEvent) {
  isDragging.value = false
  const file = e.dataTransfer?.files?.[0]
  if (file) traiterFichier(file)
}

function onFileChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (file) traiterFichier(file)
}

async function traiterFichier(file: File) {
  importError.value = ''
  importPreview.value = []
  importFile.value = file

  const ext = file.name.split('.').pop()?.toLowerCase() ?? ''

  if (ext === 'xlsx' || ext === 'xls') {
    // Pour xlsx, on envoie au serveur qui parse et retourne du JSON
    await traiterXlsxServeur(file)
    return
  }

  // CSV / TSV — parsing natif
  try {
    const text = await file.text()
    const rows = parseCSV(text)
    if (!rows.length) { importError.value = 'Fichier vide.'; return }
    importPreview.value = buildPreview(rows)
  } catch (e) {
    importError.value = 'Erreur de lecture du fichier.'
  }
}

async function traiterXlsxServeur(file: File) {
  // Envoyer le fichier xlsx au serveur Laravel pour parsing
  const fd = new FormData()
  fd.append('file', file)
  fd.append('outil_code', props.outilCode)
  try {
    const res = await fetch('/api/outils/import-xlsx', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf() },
      body: fd,
    })
    const d = await res.json()
    if (d.success && d.rows) {
      importPreview.value = d.rows
    } else {
      importError.value = d.message ?? 'Erreur serveur lors de la lecture du fichier Excel.'
    }
  } catch {
    importError.value = 'Impossible de contacter le serveur. Exportez en CSV depuis Excel (Fichier → Enregistrer sous → CSV UTF-8).'
  }
}

function parseCSV(text: string): string[][] {
  const sep = text.includes('\t') ? '\t' : ','
  return text.split(/\r?\n/).filter(l => l.trim()).map(line => splitLine(line, sep))
}

function splitLine(line: string, sep: string): string[] {
  const cols: string[] = []; let cur = '', inQ = false
  for (let i = 0; i < line.length; i++) {
    const c = line[i]
    if (c === '"') { if (inQ && line[i+1]==='"') { cur+='"'; i++ } else inQ=!inQ }
    else if (c===sep && !inQ) { cols.push(cur.trim()); cur='' }
    else cur+=c
  }
  cols.push(cur.trim())
  return cols
}

function buildPreview(rows: string[][]): any[] {
  const cols = expectedColumns.value
  // Ligne 0 = header (skippée), lignes suivantes = data
  return rows.slice(1)
    .filter(r => r.some(c => c))
    .map(r => {
      const obj: any = {}
      cols.forEach((col, ci) => { obj[col.field] = r[ci] ?? '' })
      return obj
    })
}

// ─── APPLIQUER IMPORT ────────────────────────────────────────────
function appliquerImport() {
  const cols   = expectedColumns.value
  const code   = props.outilCode
  let data: any = {}

  if (code === 'I') {
    data = {
      questions: importPreview.value.map(r => ({
        type:     r.type     || 'Ouverte',
        question: r.question || '',
        reponse:  r.reponse  || '',
      })).filter(q => q.question)
    }
  } else if (code === 'II') {
    // Ligne header du CSV = acteurs (colonnes B…I du fichier)
    // (On les a mis dans importPreview comme champs a1…a8)
    const acteurs = cols.slice(1).map((c, i) => importPreview.value[0]?.[c.field] || `Acteur ${i+1}`)
    data = {
      acteurs,
      taches: importPreview.value.map(r => ({
        libelle: r.libelle || '',
        roles:   cols.slice(1).map(c => r[c.field] || ''),
      })).filter(t => t.libelle)
    }
  } else if (code === 'III') {
    data = {
      activites: importPreview.value.map(r => ({
        libelle: r.libelle || '', acteur: r.acteur || '',
        entrant: r.entrant || '', sortant: r.sortant || '', documents: r.documents || '',
      })).filter(a => a.libelle)
    }
  } else if (code === 'IV') {
    const tab: any = { realisation: [], management: [], support: [] }
    importPreview.value.filter(r => r.nom).forEach(r => {
      const t = (r.type||'').toLowerCase().includes('management') ? 'management'
              : (r.type||'').toLowerCase().includes('support')    ? 'support' : 'realisation'
      tab[t].push({
        nom: r.nom, finalite: r.finalite || '', entrants: r.entrants || '',
        sortants: r.sortants || '', activites: r.activites || '',
        clients: r.clients || '', fournisseurs: r.fournisseurs || '', contrats: '',
      })
    })
    data = { processus: tab }
  } else if (code === 'V') {
    data = {
      etapes: importPreview.value.map(r => ({
        description: r.description || '', acteur: r.acteur || '', document: r.document || '',
        controle: r.controle || '', conforme: r.conforme || '',
        observation: r.observation || '', preuve: r.preuve || '',
      })).filter(e => e.description)
    }
  }

  emit('apply', { source: 'import', code, data })
  close()
}

// ─── APPLIQUER SUGGESTIONS IA ────────────────────────────────────
function appliquer() {
  emit('apply', { source: 'ia', code: props.outilCode, data: iaResult.value })
  close()
}

// ─── TÉLÉCHARGER MODÈLE EXCEL ────────────────────────────────────
function telechargerModele() {
  // Générer un CSV simple avec les en-têtes IFACI
  const cols = expectedColumns.value
  const exampleRow = cols.map(c => c.example ?? '')

  const lines = [
    cols.map(c => c.label).join(','),
    exampleRow.join(','),
    // Quelques lignes vides
    cols.map(() => '').join(','),
    cols.map(() => '').join(','),
    cols.map(() => '').join(','),
  ]

  const csv     = lines.join('\r\n')
  const blob    = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' })
  const url     = URL.createObjectURL(blob)
  const a       = document.createElement('a')
  a.href        = url
  a.download    = `Outil-${props.outilCode}-IFACI-modele.csv`
  a.click()
  URL.revokeObjectURL(url)
}

function close() { emit('update:visible', false) }
function csrf() { return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '' }
</script>

<style scoped>
/* ══════════════════════════════════════════════════════════════
   OVERLAY & PANEL
══════════════════════════════════════════════════════════════ */
.oia-overlay {
  position: fixed; inset: 0; z-index: 1060;
  background: rgba(15,23,42,.55); backdrop-filter: blur(3px);
  display: flex; align-items: center; justify-content: center;
  padding: 1rem;
}
.oia-panel {
  --oia-accent: #1e40af;
  background: #fff; border-radius: 14px; width: min(780px, 96vw);
  max-height: 90vh; display: flex; flex-direction: column;
  box-shadow: 0 24px 80px rgba(0,0,0,.22);
  overflow: hidden;
}

/* ── Header ───────────────────────────────────────────────────── */
.oia-header { display: flex; align-items: center; justify-content: space-between; padding: .9rem 1.25rem; border-bottom: 1px solid #e2e8f0; }
.oia-header__left { display: flex; align-items: center; gap: .75rem; }
.oia-header__badge { display: flex; align-items: center; justify-content: center; width: 36px; height: 32px; border-radius: 6px; color: #fff; font-size: .85rem; font-weight: 700; font-family: monospace; flex-shrink: 0; }
.oia-header__title { font-size: .88rem; font-weight: 700; color: #0f172a; }
.oia-header__sub { font-size: .65rem; color: #64748b; margin-top: 2px; }
.oia-close { background: none; border: none; color: #64748b; font-size: 1.1rem; cursor: pointer; padding: 4px; display: flex; align-items: center; border-radius: 4px; transition: background .12s; }
.oia-close:hover { background: #f1f5f9; }

/* ── Tabs ─────────────────────────────────────────────────────── */
.oia-tabs { display: flex; border-bottom: 2px solid #e2e8f0; background: #f8fafc; }
.oia-tab { display: flex; align-items: center; gap: .35rem; padding: .55rem 1.1rem; background: none; border: none; border-bottom: 2px solid transparent; font-size: .75rem; font-weight: 600; color: #64748b; cursor: pointer; margin-bottom: -2px; transition: all .15s; }
.oia-tab:hover { color: #0f172a; }
.oia-tab--active { color: var(--oia-accent); border-bottom-color: var(--oia-accent); background: #fff; }

/* ── Body ─────────────────────────────────────────────────────── */
.oia-body { flex: 1; overflow-y: auto; padding: 1.1rem 1.25rem; display: flex; flex-direction: column; gap: 1rem; }

/* ── Contexte ─────────────────────────────────────────────────── */
.oia-ctx-banner { display: flex; align-items: flex-start; flex-wrap: wrap; gap: .5rem .75rem; padding: .55rem .75rem; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; font-size: .7rem; color: #1e40af; }
.oia-ctx-purple { color: #6d28d9; font-weight: 600; }

/* ── IA Actions ───────────────────────────────────────────────── */
.oia-ia-actions { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
.oia-btn-generate { display: inline-flex; align-items: center; gap: .5rem; padding: .55rem 1.25rem; background: var(--oia-accent); color: #fff; border: none; border-radius: 8px; font-size: .78rem; font-weight: 700; cursor: pointer; transition: opacity .15s; }
.oia-btn-generate:disabled { opacity: .5; cursor: not-allowed; }
.oia-btn-generate:hover:not(:disabled) { opacity: .88; }
.oia-ia-hint { font-size: .68rem; color: #64748b; }
.oia-ia-error { padding: .55rem .9rem; background: #fee2e2; border: 1px solid #fecaca; border-radius: 8px; color: #dc2626; font-size: .73rem; display: flex; align-items: center; gap: .5rem; }

/* ── IA Result ────────────────────────────────────────────────── */
.oia-ia-result { display: flex; flex-direction: column; gap: .85rem; }
.oia-section { border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
.oia-section__title { padding: .45rem .8rem; background: color-mix(in srgb, var(--oia-accent) 8%, #fff); color: var(--oia-accent); font-size: .72rem; font-weight: 700; display: flex; align-items: center; gap: .35rem; border-bottom: 1px solid #e2e8f0; }
.oia-ia-text { padding: .6rem .8rem; font-size: .75rem; color: #334155; line-height: 1.55; white-space: pre-wrap; }

/* Questions preview */
.oia-questions-preview { padding: .5rem .8rem; display: flex; flex-direction: column; gap: .4rem; }
.oia-q-item { display: flex; gap: .5rem; align-items: flex-start; }
.oia-q-badge { display: inline-flex; align-items: center; justify-content: center; min-width: 30px; padding: 2px 5px; color: #fff; border-radius: 4px; font-size: .6rem; font-weight: 700; flex-shrink: 0; margin-top: 2px; }
.oia-q-content { flex: 1; }
.oia-q-text { font-size: .73rem; color: #1e293b; font-weight: 500; }
.oia-q-reponse { font-size: .68rem; color: #64748b; font-style: italic; margin-top: 1px; }

/* Chips acteurs */
.oia-chips-row { display: flex; flex-wrap: wrap; gap: .35rem; padding: .6rem .8rem; }
.oia-chip { padding: 2px 10px; border: 1.5px solid; border-radius: 20px; font-size: .68rem; font-weight: 600; }

/* RACI preview */
.oia-raci-preview { padding: .5rem .8rem; display: flex; flex-direction: column; gap: .35rem; }
.oia-raci-row { display: flex; align-items: center; gap: .5rem; padding: .25rem; border-radius: 4px; font-size: .72rem; }
.oia-raci-row:nth-child(even) { background: #f8fafc; }
.oia-raci-num { min-width: 20px; font-size: .65rem; color: #94a3b8; font-weight: 700; }
.oia-raci-label { flex: 1; color: #334155; }
.oia-raci-roles { display: flex; gap: 3px; }
.oia-raci-badge { display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; border-radius: 4px; font-size: .62rem; font-weight: 700; }
.oia-raci--r { background: #dbeafe; color: #1d4ed8; }
.oia-raci--a { background: #fee2e2; color: #dc2626; }
.oia-raci--c { background: #fef3c7; color: #d97706; }
.oia-raci--i { background: #d1fae5; color: #065f46; }
.oia-raci--empty { background: #f1f5f9; color: #94a3b8; }

/* Table preview générique */
.oia-table-preview { overflow-x: auto; }
.oia-table-head { display: flex; padding: .3rem .8rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: .62rem; font-weight: 700; color: #64748b; text-transform: uppercase; gap: .5rem; }
.oia-table-row { display: flex; padding: .3rem .8rem; font-size: .68rem; color: #334155; gap: .5rem; }
.oia-table-row--alt { background: #fafbfc; }

/* Badges mini */
.oia-badge-mini { padding: 1px 6px; border-radius: 20px; font-size: .62rem; font-weight: 600; background: #f1f5f9; color: #64748b; }
.oia-badge--ok   { background: #d1fae5; color: #065f46; }
.oia-badge--ko   { background: #fee2e2; color: #dc2626; }
.oia-badge--warn { background: #fef3c7; color: #d97706; }

/* Processus preview */
.oia-procs-preview { padding: .5rem .8rem; display: flex; flex-direction: column; gap: .3rem; }
.oia-proc-item { display: flex; align-items: baseline; gap: .5rem; font-size: .73rem; }
.oia-proc-nom { font-weight: 700; min-width: 140px; flex-shrink: 0; }
.oia-proc-fin { color: #64748b; font-style: italic; }
.oia-empty-hint { padding: .6rem .8rem; font-size: .7rem; color: #94a3b8; font-style: italic; }

/* Apply bar */
.oia-apply-bar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .75rem 1rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; flex-wrap: wrap; }
.oia-apply-bar--import { margin-top: .5rem; }
.oia-apply-hint { font-size: .68rem; color: #64748b; display: flex; align-items: center; gap: .35rem; }
.oia-btn-apply { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem 1.2rem; background: var(--oia-accent); color: #fff; border: none; border-radius: 7px; font-size: .75rem; font-weight: 700; cursor: pointer; transition: opacity .15s; white-space: nowrap; }
.oia-btn-apply:hover { opacity: .88; }

/* ── Import zone ──────────────────────────────────────────────── */
.oia-import-zone { border: 2px dashed #cbd5e1; border-radius: 12px; padding: 2rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: .6rem; transition: all .2s; }
.oia-import-zone--drag { border-color: var(--oia-accent); background: color-mix(in srgb, var(--oia-accent) 5%, #fff); }
.oia-import-icon { font-size: 2.5rem; color: #94a3b8; }
.oia-import-title { font-size: .85rem; font-weight: 700; color: #1e293b; }
.oia-import-sub { font-size: .7rem; color: #64748b; }
.oia-btn-browse { display: inline-flex; align-items: center; gap: .4rem; padding: .45rem 1rem; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 7px; font-size: .73rem; font-weight: 600; color: #475569; cursor: pointer; transition: background .12s; }
.oia-btn-browse:hover { background: #e2e8f0; }
.oia-file-hidden { display: none; }

/* Colonnes info */
.oia-columns-info { border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
.oia-columns-title { padding: .4rem .8rem; background: #f8fafc; font-size: .7rem; font-weight: 700; color: #475569; display: flex; align-items: center; gap: .35rem; border-bottom: 1px solid #e2e8f0; }
.oia-columns-grid { display: flex; flex-wrap: wrap; gap: .4rem; padding: .6rem .8rem; }
.oia-col-item { display: flex; align-items: center; gap: .35rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; padding: .25rem .5rem; }
.oia-col-letter { display: inline-flex; align-items: center; justify-content: center; min-width: 22px; height: 20px; color: #fff; border-radius: 4px; font-size: .6rem; font-weight: 700; font-family: monospace; }
.oia-col-label { font-size: .65rem; color: #334155; }

/* Preview table */
.oia-preview-wrap { display: flex; flex-direction: column; gap: .5rem; }
.oia-preview-header { display: flex; align-items: center; justify-content: space-between; }
.oia-preview-count { font-size: .72rem; font-weight: 700; color: var(--oia-accent); }
.oia-btn-clear { display: inline-flex; align-items: center; gap: .3rem; background: none; border: 1px solid #e2e8f0; border-radius: 5px; padding: 3px 8px; font-size: .65rem; color: #64748b; cursor: pointer; }
.oia-preview-table-wrap { overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 8px; }
.oia-preview-table { width: 100%; border-collapse: collapse; font-size: .68rem; }
.oia-preview-table thead th { padding: .3rem .5rem; background: #f8fafc; color: var(--oia-accent); font-weight: 700; border-bottom: 1px solid #e2e8f0; white-space: nowrap; font-size: .62rem; text-transform: uppercase; }
.oia-preview-table tbody td { padding: .3rem .5rem; border-bottom: 1px solid #e2e8f0; color: #334155; max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.oia-preview-table tbody tr:nth-child(even) { background: #fafbfc; }
.oia-preview-more { font-size: .65rem; color: #94a3b8; text-align: right; padding: .25rem .5rem; }

/* ── Template tab ─────────────────────────────────────────────── */
.oia-template-hero { text-align: center; padding: 1rem .5rem; display: flex; flex-direction: column; align-items: center; gap: .4rem; }
.oia-template-icon { font-size: 2.8rem; }
.oia-template-title { font-size: 1rem; font-weight: 800; color: #0f172a; }
.oia-template-sub { font-size: .75rem; color: #475569; }
.oia-template-desc { font-size: .7rem; color: #64748b; max-width: 480px; line-height: 1.5; }
.oia-template-preview { border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
.oia-template-preview__header { display: flex; justify-content: space-between; padding: .4rem .9rem; color: #fff; font-size: .7rem; font-weight: 700; }
.oia-template-cols { display: flex; flex-wrap: wrap; gap: .5rem; padding: .75rem .9rem; }
.oia-tcol { display: flex; align-items: center; gap: .4rem; border: 1px solid #e2e8f0; border-radius: 8px; padding: .3rem .6rem; flex: 1; min-width: 130px; }
.oia-tcol__letter { display: flex; align-items: center; justify-content: center; min-width: 26px; height: 22px; border-radius: 4px; font-size: .7rem; font-weight: 700; font-family: monospace; flex-shrink: 0; }
.oia-tcol__label { font-size: .68rem; font-weight: 600; color: #1e293b; flex: 1; }
.oia-tcol__example { font-size: .58rem; color: #94a3b8; font-style: italic; margin-top: 1px; }

/* ── Spinner ──────────────────────────────────────────────────── */
.oia-spinner { display: inline-block; width: .8rem; height: .8rem; border: 2px solid rgba(255,255,255,.35); border-top-color: #fff; border-radius: 50%; animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg) } }

/* ── Transitions ──────────────────────────────────────────────── */
.oia-fade-enter-active, .oia-fade-leave-active { transition: all .22s ease; }
.oia-fade-enter-from, .oia-fade-leave-to { opacity: 0; }
.oia-fade-enter-from .oia-panel, .oia-fade-leave-to .oia-panel { transform: scale(.96) translateY(8px); }

/* ── Scrollbar ────────────────────────────────────────────────── */
.oia-body::-webkit-scrollbar { width: 4px; }
.oia-body::-webkit-scrollbar-track { background: #f1f5f9; }
.oia-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 2px; }

.mt-2 { margin-top: .5rem; }
</style>