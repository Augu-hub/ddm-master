<template>
  <VerticalLayoutAudit>
    <div class="oz-shell">

      <!-- ═══════════════════════ HEADER ═══════════════════════ -->
      <header class="oz-header">
        <div class="oz-header__inner">
          <a :href="props.backUrl" class="oz-back"><i class="ti ti-arrow-left"></i></a>
          <div class="oz-header__meta">
            <div class="oz-header__badges">
              <span class="oz-code">{{ form.code || 'I-AUTO' }}</span>
              <span class="oz-status" :class="'ozs--' + form.statut">
                <i :class="statusIcon(form.statut)"></i>{{ statusLabel(form.statut) }}
              </span>
              <span class="oz-pill"><i class="ti ti-shield-half"></i>{{ props.auditorRole }}</span>
              <span class="oz-pill"><i class="ti ti-user-check"></i>{{ props.auditeurNom }}</span>
            </div>
            <h1 class="oz-header__title" style="--hc:#1e40af">
              <span class="oz-num">I</span>Grille d'Entretien
            </h1>
            <div class="oz-header__info">
              <span v-if="missionLibelle"><i class="ti ti-building"></i>{{ missionLibelle }}</span>
              <span v-if="form.procedure_code" class="oz-proc-badge"><i class="ti ti-list-check"></i>{{ form.procedure_code }}</span>
            </div>
          </div>
          <div class="oz-header__actions">
            <template v-if="!isLocked">
              <button class="oz-btn oz-btn--ghost" :disabled="processing" @click="annuler"><i class="ti ti-x"></i></button>
              <button class="oz-btn oz-btn--save" :disabled="processing" @click="submit()">
                <span v-if="processing" class="oz-spin"></span>
                <i v-else class="ti ti-device-floppy"></i>
                {{ form.id ? 'Mettre à jour' : 'Enregistrer' }}
              </button>
              <button v-if="form.id && form.statut === 'draft'" class="oz-btn oz-btn--submit" @click="soumettre">
                <i class="ti ti-send"></i> Soumettre
              </button>
            </template>
            <template v-if="canManage && form.statut === 'in_review'">
              <button class="oz-btn oz-btn--ok" @click="valider('validated')"><i class="ti ti-circle-check"></i> Valider</button>
              <button class="oz-btn oz-btn--ko" @click="promptReject"><i class="ti ti-circle-x"></i> Rejeter</button>
            </template>
            <button v-if="form.id && form.statut === 'validated' && !['email_sent','confirmed'].includes(form.validation_status)"
                    class="oz-btn oz-btn--email" @click="sendValidationEmail">
              <i class="ti ti-mail"></i> Envoyer validation
            </button>
          </div>
        </div>
        <!-- Banners -->
        <div v-if="form.statut === 'validated'" class="oz-banner oz-banner--ok"><i class="ti ti-lock"></i> Fiche validée — lecture seule</div>
        <div v-else-if="form.statut === 'in_review'" class="oz-banner oz-banner--review"><i class="ti ti-clock"></i> Soumise pour validation</div>
        <div v-else-if="form.statut === 'draft' && form.validation_note" class="oz-banner oz-banner--ko"><i class="ti ti-circle-x"></i> Rejetée — <em>{{ form.validation_note }}</em></div>
        <div v-if="form.validation_status === 'email_sent'" class="oz-banner oz-banner--email"><i class="ti ti-mail"></i> Email envoyé — en attente de confirmation</div>
        <div v-else-if="form.validation_status === 'confirmed'" class="oz-banner oz-banner--confirmed"><i class="ti ti-check"></i> Confirmée par l'audité le {{ formatDate(form.confirmed_at) }}</div>
      </header>

      <!-- ═══════════════════════ BODY ═══════════════════════ -->
      <div class="oz-body">

        <!-- ─── Infos générales ─── -->
        <div class="oz-card">
          <div class="oz-card__title-row"><i class="ti ti-info-circle oz-card__ico"></i><h3 class="oz-card__title">Informations générales</h3></div>
          <div class="oz-g2">
            <div class="oz-f oz-full">
              <label class="oz-lbl">Intitulé <span class="oz-req">*</span></label>
              <input type="text" class="oz-inp" v-model="form.intitule" :disabled="isLocked" placeholder="Ex. : Entretien avec le Directeur Financier" />
            </div>
            <div class="oz-f oz-full">
              <label class="oz-lbl">Interlocuteur(s) — Nom, Prénom, Email</label>
              <input type="text" class="oz-inp" v-model="form.interlocuteur" :disabled="isLocked" placeholder="Jean Dupont, j.dupont@org.fr" />
            </div>
            <div class="oz-f">
              <label class="oz-lbl">Fonction / Poste</label>
              <input type="text" class="oz-inp" v-model="form.fonction" :disabled="isLocked" placeholder="Directeur Financier" />
            </div>
            <div class="oz-f">
              <label class="oz-lbl">Date de l'entretien</label>
              <input type="date" class="oz-inp" v-model="form.date_entretien" :disabled="isLocked" />
            </div>
            <div class="oz-f oz-full">
              <label class="oz-lbl">Lieu</label>
              <input type="text" class="oz-inp" v-model="form.lieu" :disabled="isLocked" placeholder="Salle de réunion A, Siège social…" />
            </div>
            <div class="oz-f oz-full">
              <label class="oz-lbl">Objectif de l'entretien</label>
              <textarea class="oz-ta" v-model="form.objectif" :disabled="isLocked" rows="3" placeholder="S'assurer que le processus décrit par l'interlocuteur est conforme au référentiel…"></textarea>
            </div>
          </div>
        </div>

        <!-- ─── Questions QQOCPQ ─── -->
        <div class="oz-card">
          <div class="oz-card__hd">
            <div class="oz-card__title-row"><i class="ti ti-help-circle oz-card__ico oz-card__ico--purple"></i><h3 class="oz-card__title">Questions — Méthode QQOCPQ</h3></div>
            <span class="oz-badge-count">{{ questions.length }}</span>
            <button v-if="!isLocked" class="oz-add" @click="addQuestion"><i class="ti ti-plus"></i> Ajouter</button>
          </div>
          <div class="oz-table-wrap" v-if="questions.length">
            <table class="oz-tbl">
              <thead>
                <tr>
                  <th style="width:32px" class="tc">N°</th>
                  <th style="width:100px">Type</th>
                  <th>Question</th>
                  <th>Réponse obtenue</th>
                  <th>Note / Observation</th>
                  <th v-if="!isLocked" style="width:32px"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(q, qi) in questions" :key="qi" :class="qi % 2 === 0 ? 'oz-tr-even' : ''">
                  <td class="tc oz-n">{{ qi + 1 }}</td>
                  <td>
                    <select class="oz-sel-sm" v-model="q.type" :disabled="isLocked">
                      <option>Ouverte</option><option>Fermée</option>
                      <option>Factuelle</option><option>Rebond</option>
                    </select>
                  </td>
                  <td><textarea class="oz-ta-sm" v-model="q.libelle" rows="2" :disabled="isLocked" placeholder="Question…"></textarea></td>
                  <td><textarea class="oz-ta-sm" v-model="q.reponse" rows="2" :disabled="isLocked" placeholder="Réponse…"></textarea></td>
                  <td><textarea class="oz-ta-sm" v-model="q.note" rows="2" :disabled="isLocked" placeholder="Note…"></textarea></td>
                  <td v-if="!isLocked" class="tc">
                    <button class="oz-del" @click="questions.splice(qi,1)"><i class="ti ti-trash"></i></button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="oz-ec"><i class="ti ti-help-circle"></i> Aucune question — cliquez « Ajouter » pour commencer</div>
        </div>

        <!-- ─── Documents Word & Excel ─── -->
        <div class="oz-card">
          <div class="oz-card__hd">
            <div class="oz-card__title-row"><i class="ti ti-files oz-card__ico oz-card__ico--blue"></i><h3 class="oz-card__title">Documents attachés</h3></div>
            <span class="oz-badge-count">{{ documents.length }}</span>
            <div class="oz-doc-upload-zone" v-if="!isLocked">
              <input 
                type="file" 
                ref="fileInput" 
                class="oz-hidden-input" 
                accept=".doc,.docx,.odt,.rtf,.pdf,.xls,.xlsx,.csv" 
                @change="handleFileUpload" 
              />
              <button class="oz-add oz-add--upload" @click="fileInput?.click()" :disabled="uploadLoading">
                <span v-if="uploadLoading" class="oz-spin oz-spin--dark"></span>
                <i v-else class="ti ti-upload"></i>
                {{ uploadLoading ? 'Chargement…' : 'Importer document' }}
              </button>
            </div>
          </div>

          <!-- Liste des documents -->
          <div v-if="documents.length === 0" class="oz-doc-empty">
            <div class="oz-doc-empty__ico"><i class="ti ti-file"></i></div>
            <p class="oz-doc-empty__title">Aucun document importé</p>
            <p class="oz-doc-empty__sub">Importez un fichier Word (.doc, .docx) ou Excel (.xls, .xlsx)</p>
          </div>
          <div v-else class="oz-doc-list">
            <div v-for="doc in documents" :key="doc.id" class="oz-doc-item" :class="`oz-doc--${doc.status}`">
              <div class="oz-doc-item__left">
                <div class="oz-doc-icon" :class="{ 'oz-doc-icon--excel': isExcelFile(doc.file_extension) }">
                  <i :class="getFileIcon(doc.file_extension)"></i>
                </div>
                <div class="oz-doc-info">
                  <div class="oz-doc-name">{{ doc.original_name }}</div>
                  <div class="oz-doc-meta">
                    <span>{{ formatFileSize(doc.file_size) }}</span>
                    <span>• {{ formatDate(doc.created_at) }}</span>
                    <span class="oz-doc-status-badge" :class="`ozds--${doc.status}`">{{ getStatusLabel(doc.status) }}</span>
                  </div>
                </div>
              </div>
              <div class="oz-doc-actions">
                <!-- Éditer uniquement pour Word -->
                <button 
                  v-if="isWordFile(doc.file_extension)" 
                  class="oz-doc-btn oz-doc-btn--primary" 
                  @click="openWordEditor(doc)" 
                  title="Ouvrir et modifier dans l'éditeur"
                >
                  <i class="ti ti-edit"></i> Modifier
                </button>
                <!-- Aperçu/Afficher pour Excel et autres -->
                <button 
                  v-else 
                  class="oz-doc-btn" 
                  @click="viewDocument(doc)" 
                  title="Télécharger pour visualiser"
                >
                  <i class="ti ti-eye"></i> Afficher
                </button>
                <button class="oz-doc-btn" @click="downloadDocument(doc)" title="Télécharger">
                  <i class="ti ti-download"></i>
                </button>
                <button v-if="canManage" class="oz-doc-btn oz-doc-btn--valid" @click="validateDocument(doc, 'validated')" title="Valider">
                  <i class="ti ti-check"></i>
                </button>
                <button v-if="canManage" class="oz-doc-btn oz-doc-btn--reject" @click="validateDocument(doc, 'rejected')" title="Rejeter">
                  <i class="ti ti-x"></i>
                </button>
                <button v-if="!isLocked" class="oz-doc-btn oz-doc-btn--delete" @click="deleteDocument(doc)" title="Supprimer">
                  <i class="ti ti-trash"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ─── Synthèse & Signatures ─── -->
        <div class="oz-card">
          <div class="oz-card__title-row"><i class="ti ti-file-check oz-card__ico oz-card__ico--green"></i><h3 class="oz-card__title">Synthèse & Signatures</h3></div>
          <div class="oz-synthese-grid">
            <div class="oz-f oz-full">
              <label class="oz-lbl">Points clés validés lors de l'entretien</label>
              <textarea class="oz-ta oz-ta--synthese" v-model="form.synthese" :disabled="isLocked" rows="5"
                placeholder="Résumé des éléments importants abordés, points de convergence, informations factuelles recueillies…"></textarea>
            </div>
            <div class="oz-sig-row">
              <div class="oz-sig-box">
                <label class="oz-lbl">Signature de l'Auditeur</label>
                <input type="text" class="oz-inp oz-inp--sig" v-model="form.sig_auditeur" :disabled="isLocked" :placeholder="props.auditeurNom" />
                <div class="oz-sig-line"></div>
              </div>
              <div class="oz-sig-box">
                <label class="oz-lbl">Signature de l'Interlocuteur</label>
                <input type="text" class="oz-inp oz-inp--sig" v-model="form.sig_interlocuteur" :disabled="isLocked" :placeholder="form.interlocuteur || 'Interlocuteur'" />
                <div class="oz-sig-line"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- ─── Analyse IA ─── -->
        <div v-if="iaResult" class="oz-card oz-card--ia">
          <div class="oz-card__title-row oz-ia-header">
            <i class="ti ti-sparkles oz-card__ico oz-card__ico--purple"></i>
            <h3 class="oz-card__title">Analyse IA — Mistral</h3>
            <span class="oz-ia-score">{{ iaResult.score }}<span class="oz-ia-score__max">/10</span></span>
          </div>
          <p class="oz-ia-synth">{{ iaResult.synthese }}</p>
          <div class="oz-ia-cols">
            <div v-if="iaResult.points_forts?.length" class="oz-ia-section oz-ia-section--ok">
              <div class="oz-ia-section__title"><i class="ti ti-circle-check"></i> Points forts</div>
              <div v-for="r in iaResult.points_forts" :key="r" class="oz-ia-item">{{ r }}</div>
            </div>
            <div v-if="iaResult.points_faibles?.length" class="oz-ia-section oz-ia-section--warn">
              <div class="oz-ia-section__title"><i class="ti ti-alert-triangle"></i> Points faibles</div>
              <div v-for="r in iaResult.points_faibles" :key="r" class="oz-ia-item">{{ r }}</div>
            </div>
          </div>
          <div v-if="iaResult.risques?.length" class="oz-ia-section oz-ia-section--danger">
            <div class="oz-ia-section__title"><i class="ti ti-alert-circle"></i> Risques identifiés</div>
            <div v-for="r in iaResult.risques" :key="r" class="oz-ia-item">{{ r }}</div>
          </div>
          <div v-if="iaResult.recommandations?.length" class="oz-ia-section oz-ia-section--rec">
            <div class="oz-ia-section__title"><i class="ti ti-bulb"></i> Recommandations</div>
            <div v-for="r in iaResult.recommandations" :key="r" class="oz-ia-item">{{ r }}</div>
          </div>
        </div>

        <!-- Bouton IA -->
        <button v-if="form.id && !isLocked" class="oz-btn-ia" :disabled="iaLoading" @click="genererIa">
          <span v-if="iaLoading" class="oz-spin"></span>
          <i v-else class="ti ti-sparkles"></i>
          {{ iaLoading ? 'Analyse en cours…' : '✨ Générer l\'analyse IA (Mistral)' }}
        </button>

        <!-- Toast -->
        <Transition name="toast">
          <div v-if="toast.show" class="oz-toast" :class="'ozt--' + toast.type">
            <i :class="toast.type === 'success' ? 'ti ti-circle-check' : 'ti ti-alert-circle'"></i>
            {{ toast.msg }}
          </div>
        </Transition>
      </div>

      <!-- ═══════════════ MODAL ÉDITEUR WORD (TipTap) ═══════════════ -->
      <WordEditorModal
        v-model:show="wordEditor.show"
        :doc="wordEditor.doc"
        :url-edit-base="props.urlEditDocBase"
        :url-save-base="props.urlSaveDocBase"
        @saved="onWordDocSaved"
      />

    </div>
  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { reactive, ref, computed, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayoutAudit from '@/Layouts/VerticalLayoutAudit.vue'
import WordEditorModal from '@/Components/WordEditorModal.vue'

// ─────────────────────────────────────────────────────────────
// PROPS
// ─────────────────────────────────────────────────────────────
const props = withDefaults(defineProps<{
  record?: any
  documents?: any[]
  questions?: any[]
  missions?: any[]
  auditorRole?: string
  auditeurNom?: string
  backUrl?: string
  urlStore?: string
  urlUpdate?: string | null
  urlSoumettre?: string | null
  urlValider?: string | null
  urlIa?: string | null
  urlUploadDoc?: string
  urlDocuments?: string
  urlPreviewDocBase?: string
  urlDownloadDocBase?: string
  urlEditDocBase?: string
  urlSaveDocBase?: string
  urlValidateDocBase?: string
  urlDeleteDocBase?: string
  urlSendValidationEmail?: string
  missionContext?: any
}>(), {
  record: null, documents: () => [], questions: () => [], missions: () => [],
  auditorRole: 'AJ', auditeurNom: '', backUrl: '/', urlStore: '', urlUpdate: null,
  urlSoumettre: null, urlValider: null, urlIa: null, urlUploadDoc: '', urlDocuments: '',
  urlPreviewDocBase: '', urlDownloadDocBase: '', urlEditDocBase: '', urlSaveDocBase: '',
  urlValidateDocBase: '', urlDeleteDocBase: '', urlSendValidationEmail: '',
  missionContext: () => ({}),
})

// ─────────────────────────────────────────────────────────────
// STATE
// ─────────────────────────────────────────────────────────────
const form = reactive<any>({
  id: null, code: '', statut: 'draft', validation_note: '', validation_status: 'pending',
  intitule: '', objectif: '', interlocuteur: '', fonction: '', date_entretien: '', lieu: '',
  synthese: '', sig_auditeur: props.auditeurNom, sig_interlocuteur: '', confirmed_at: null,
  ...(props.record ?? {}),
})
const questions    = reactive<any[]>([...(props.questions ?? [])])
const documents    = reactive<any[]>([...(props.documents ?? [])])
const processing   = ref(false)
const uploadLoading = ref(false)
const fileInput    = ref<HTMLInputElement | null>(null)
const iaLoading    = ref(false)
const iaResult     = ref<any>(
  props.record?.ia_result
    ? (typeof props.record.ia_result === 'string' ? JSON.parse(props.record.ia_result) : props.record.ia_result)
    : null
)
const toast = reactive({ show: false, type: 'success', msg: '' })
let toastTimeout: ReturnType<typeof setTimeout> | null = null

// Éditeur Word
const wordEditor = reactive<{ show: boolean; doc: any }>({ show: false, doc: null })

// ─────────────────────────────────────────────────────────────
// COMPUTED
// ─────────────────────────────────────────────────────────────
const canManage      = computed(() => ['DM', 'CM'].includes(props.auditorRole ?? ''))
const isLocked       = computed(() =>
  form.statut === 'validated' ||
  (form.statut === 'in_review' && !canManage.value) ||
  form.validation_status === 'email_sent'
)
const missionLibelle = computed(() => props.missionContext?.mission_libelle ?? '')

// ─────────────────────────────────────────────────────────────
// UTILITAIRES
// ─────────────────────────────────────────────────────────────
function docUrl(base: string, docId: number) { return base.replace('__DOC__', String(docId)) }
function csrfToken() { return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '' }
function statusLabel(s: string) { return ({draft:'Brouillon',in_review:'En attente',validated:'Validé ✓',rejected:'Rejeté'} as any)[s] ?? s }
function statusIcon(s: string)  { return ({draft:'ti ti-edit',in_review:'ti ti-clock',validated:'ti ti-lock',rejected:'ti ti-circle-x'} as any)[s] ?? 'ti ti-file' }
function formatDate(d: string | null) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}
function formatFileSize(bytes: number) {
  if (!bytes) return '0 B'
  const k = 1024, s = ['B','KB','MB','GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + s[i]
}
function getStatusLabel(status: string) { return ({draft:'En attente',validated:'Validé ✓',rejected:'Rejeté ✗'} as any)[status] ?? status }
function showToast(type: string, msg: string, dur = 4000) {
  if (toastTimeout) clearTimeout(toastTimeout)
  toast.show = true; toast.type = type; toast.msg = msg
  toastTimeout = setTimeout(() => (toast.show = false), dur)
}

// Fonctions pour détecter les types de fichiers
function isWordFile(extension: string): boolean {
  const ext = extension?.toLowerCase() || '';
  return ['doc', 'docx', 'odt', 'rtf'].includes(ext);
}

function isExcelFile(extension: string): boolean {
  const ext = extension?.toLowerCase() || '';
  return ['xls', 'xlsx', 'csv'].includes(ext);
}

function getFileIcon(extension: string): string {
  if (isWordFile(extension)) return 'ti ti-file-word';
  if (isExcelFile(extension)) return 'ti ti-file-spreadsheet';
  if (extension === 'pdf') return 'ti ti-file-pdf';
  return 'ti ti-file';
}

function viewDocument(doc: any) {
  // Pour les fichiers Excel, PDF et autres non-éditables
  window.open(docUrl(props.urlDownloadDocBase, doc.id), '_blank');
}

// ─────────────────────────────────────────────────────────────
// CRUD
// ─────────────────────────────────────────────────────────────
function annuler() { if (props.backUrl) router.visit(props.backUrl) }

async function submit(silent = false) {
  processing.value = !silent
  try {
    const url    = form.id ? (props.urlUpdate ?? '') : (props.urlStore ?? '')
    const method = form.id ? 'PUT' : 'POST'
    if (!url) throw new Error('URL manquante')
    const res = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
      body: JSON.stringify({
        mission_id: props.missionContext?.mission_id,
        assignment_id: props.missionContext?.assignment_id,
        procedure_code: props.missionContext?.procedure_code,
        test_ref: props.missionContext?.test_ref,
        intitule: form.intitule, objectif: form.objectif,
        interlocuteur: form.interlocuteur, fonction: form.fonction,
        date_entretien: form.date_entretien, lieu: form.lieu,
        synthese: form.synthese, sig_auditeur: form.sig_auditeur,
        sig_interlocuteur: form.sig_interlocuteur, questions,
      }),
    })
    const data = await res.json()
    if (data.success || res.ok) {
      if (!silent) showToast('success', form.id ? 'Grille mise à jour.' : 'Grille créée.')
      if (data.record?.id) {
        form.id = data.record.id
        if (data.record.code) form.code = data.record.code
        if (data.record.statut) form.statut = data.record.statut
      }
    } else throw new Error(data.message ?? data.error ?? 'Erreur')
  } catch (err: any) { if (!silent) showToast('error', err.message) }
  finally { processing.value = false }
}

async function soumettre() {
  if (!props.urlSoumettre) return
  processing.value = true
  try {
    const res  = await fetch(props.urlSoumettre, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() }, body: '{}' })
    const data = await res.json()
    if (data.success) { form.statut = 'in_review'; showToast('success', 'Grille soumise pour validation.') }
    else throw new Error(data.error ?? 'Erreur')
  } catch (err: any) { showToast('error', err.message) }
  finally { processing.value = false }
}

async function valider(action: string, note?: string) {
  if (!props.urlValider) return
  processing.value = true
  try {
    const res  = await fetch(props.urlValider, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() }, body: JSON.stringify({ decision: action, commentaire: note }) })
    const data = await res.json()
    if (data.success) { form.statut = action; showToast('success', action === 'validated' ? 'Validée ✓' : 'Rejetée.') }
    else throw new Error(data.error ?? 'Erreur')
  } catch (err: any) { showToast('error', err.message) }
  finally { processing.value = false }
}
function promptReject() { const n = prompt('Motif du rejet :'); if (n?.trim()) valider('rejected', n.trim()) }
function addQuestion()  { questions.push({ type: 'Ouverte', libelle: '', reponse: '', note: '' }) }

// ─────────────────────────────────────────────────────────────
// DOCUMENTS
// ─────────────────────────────────────────────────────────────
async function handleFileUpload(event: Event) {
  const input = event.target as HTMLInputElement
  if (!input.files?.length) return
  const file = input.files[0]
  const formData = new FormData()
  formData.append('document', file)
  if (props.missionContext?.assignment_id) formData.append('assignment_id', props.missionContext.assignment_id)
  uploadLoading.value = true
  try {
    const res  = await fetch(props.urlUploadDoc, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken() }, body: formData })
    const data = await res.json()
    if (data.success && data.document) {
      documents.unshift(data.document)
      showToast('success', 'Document importé avec succès')
      // Si c'est un fichier Word, ouvrir l'éditeur
      if (isWordFile(data.document.file_extension)) {
        setTimeout(() => openWordEditor(data.document), 300)
      }
    } else throw new Error(data.error ?? 'Erreur upload')
  } catch (err: any) { showToast('error', err.message) }
  finally { uploadLoading.value = false; input.value = '' }
}

function openWordEditor(doc: any) {
  wordEditor.doc  = doc
  wordEditor.show = true
}

function onWordDocSaved({ docId, html }: { docId: number; html: string }) {
  const idx = documents.findIndex(d => d.id === docId)
  if (idx !== -1) documents[idx].word_content_html = html
  showToast('success', 'Document sauvegardé ✓')
}

async function downloadDocument(doc: any) {
  window.open(docUrl(props.urlDownloadDocBase, doc.id), '_blank')
}

async function validateDocument(doc: any, status: string) {
  let comment = ''
  if (status === 'rejected') { comment = prompt('Motif du rejet :') || ''; if (!comment) return }
  try {
    const res  = await fetch(docUrl(props.urlValidateDocBase, doc.id), {
      method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
      body: JSON.stringify({ status, comment })
    })
    const data = await res.json()
    if (data.success) {
      const idx = documents.findIndex(d => d.id === doc.id)
      if (idx !== -1) documents[idx].status = status
      showToast('success', status === 'validated' ? 'Document validé' : 'Document rejeté')
    }
  } catch (err: any) { showToast('error', err.message) }
}

async function deleteDocument(doc: any) {
  if (!confirm(`Supprimer "${doc.original_name}" ?`)) return
  try {
    const res  = await fetch(docUrl(props.urlDeleteDocBase, doc.id), { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken() } })
    const data = await res.json()
    if (data.success) {
      documents.splice(documents.findIndex(d => d.id === doc.id), 1)
      showToast('success', 'Document supprimé')
    }
  } catch (err: any) { showToast('error', err.message) }
}

async function sendValidationEmail() {
  if (!props.urlSendValidationEmail) return
  processing.value = true
  try {
    const res  = await fetch(props.urlSendValidationEmail, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() }, body: '{}' })
    const data = await res.json()
    if (data.success) { form.validation_status = 'email_sent'; showToast('success', 'Email envoyé ✓') }
    else throw new Error(data.error ?? 'Erreur')
  } catch (err: any) { showToast('error', err.message) }
  finally { processing.value = false }
}

// ─────────────────────────────────────────────────────────────
// IA
// ─────────────────────────────────────────────────────────────
async function genererIa() {
  if (!props.urlIa || !form.id) return
  iaLoading.value = true
  try {
    const res  = await fetch(props.urlIa, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() }, body: '{}' })
    const data = await res.json()
    if (data.success) { iaResult.value = data.ia_result; showToast('success', 'Analyse IA générée ✓') }
    else throw new Error(data.error ?? 'Erreur IA')
  } catch (err: any) { showToast('error', err.message) }
  finally { iaLoading.value = false }
}

onBeforeUnmount(() => { if (toastTimeout) clearTimeout(toastTimeout) })
</script>

<style scoped>
/* ── Shell ── */
.oz-shell { display:flex; flex-direction:column; min-height:100vh; background:#f0f4f8; font-family:'DM Sans',system-ui,sans-serif; }

/* ── Header ── */
.oz-header { background:#fff; border-bottom:1px solid #e2e8f0; box-shadow:0 2px 8px rgba(15,23,42,.07); position:sticky; top:0; z-index:100; flex-shrink:0; }
.oz-header__inner { display:flex; align-items:center; gap:.6rem; padding:.55rem 1rem; flex-wrap:wrap; }
.oz-back { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:8px; border:1px solid #e2e8f0; color:#475569; text-decoration:none; transition:all .12s; }
.oz-back:hover { background:#f1f5f9; border-color:#cbd5e1; }
.oz-header__meta { flex:1; min-width:0; }
.oz-header__badges { display:flex; gap:.3rem; flex-wrap:wrap; margin-bottom:.25rem; }
.oz-code { background:#0f172a; color:#e2e8f0; padding:2px 8px; border-radius:5px; font-size:.62rem; font-family:monospace; font-weight:700; letter-spacing:.05em; }
.oz-status { display:inline-flex; align-items:center; gap:.2rem; padding:2px 8px; border-radius:20px; font-size:.62rem; font-weight:600; }
.ozs--draft { background:#f1f5f9; color:#64748b; }
.ozs--in_review { background:#dbeafe; color:#1d4ed8; }
.ozs--validated { background:#dcfce7; color:#15803d; }
.ozs--rejected { background:#fee2e2; color:#dc2626; }
.oz-pill { display:inline-flex; align-items:center; gap:.2rem; background:#f8fafc; color:#64748b; padding:2px 8px; border-radius:20px; font-size:.62rem; border:1px solid #e2e8f0; }
.oz-proc-badge { background:#fef3c7; color:#92400e; border:1px solid #fde68a; border-radius:10px; padding:1px 7px; font-size:.62rem; display:inline-flex; align-items:center; gap:.2rem; }
.oz-header__title { font-size:.85rem; font-weight:700; color:#0f172a; margin:0; display:flex; align-items:center; gap:.4rem; }
.oz-num { display:inline-flex; align-items:center; justify-content:center; min-width:24px; height:24px; background:var(--hc,#1e40af); color:#fff; border-radius:6px; font-size:.68rem; font-weight:700; }
.oz-header__info { display:flex; gap:.5rem; margin-top:.2rem; }
.oz-header__info span { display:inline-flex; align-items:center; gap:.2rem; font-size:.65rem; color:#64748b; }
.oz-header__actions { display:flex; gap:.3rem; flex-wrap:wrap; }
.oz-btn { display:inline-flex; align-items:center; gap:.25rem; padding:6px 12px; border:none; border-radius:7px; font-size:.73rem; font-weight:600; cursor:pointer; transition:all .12s; white-space:nowrap; }
.oz-btn--ghost { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }
.oz-btn--ghost:hover { background:#e2e8f0; }
.oz-btn--save { background:#1e40af; color:#fff; }
.oz-btn--save:hover { background:#1d3a8a; }
.oz-btn--submit { background:#7c3aed; color:#fff; }
.oz-btn--ok { background:#15803d; color:#fff; }
.oz-btn--ko { background:#dc2626; color:#fff; }
.oz-btn--email { background:#d97706; color:#fff; }
.oz-btn:disabled { opacity:.5; cursor:default; }

/* Banners */
.oz-banner { display:flex; align-items:center; gap:.4rem; padding:.3rem 1rem; font-size:.72rem; font-weight:500; }
.oz-banner--ok { background:#d1fae5; color:#065f46; border-top:1px solid #a7f3d0; }
.oz-banner--review { background:#dbeafe; color:#1d4ed8; border-top:1px solid #bfdbfe; }
.oz-banner--ko { background:#fee2e2; color:#dc2626; border-top:1px solid #fecaca; }
.oz-banner--email { background:#fef3c7; color:#92400e; border-top:1px solid #fde68a; }
.oz-banner--confirmed { background:#dcfce7; color:#15803d; border-top:1px solid #bbf7d0; }

/* Body */
.oz-body { flex:1; padding:.85rem 1rem; display:flex; flex-direction:column; gap:.85rem; }

/* Cards */
.oz-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.1rem; box-shadow:0 1px 4px rgba(15,23,42,.05); }
.oz-card__title-row { display:flex; align-items:center; gap:.45rem; margin-bottom:.75rem; }
.oz-card__ico { font-size:1.05rem; color:#64748b; }
.oz-card__ico--purple { color:#7c3aed; }
.oz-card__ico--blue { color:#1d4ed8; }
.oz-card__ico--green { color:#15803d; }
.oz-card__title { font-size:.82rem; font-weight:700; color:#0f172a; margin:0; }
.oz-card__hd { display:flex; align-items:center; gap:.5rem; margin-bottom:.75rem; }

/* Grid */
.oz-g2 { display:grid; grid-template-columns:1fr 1fr; gap:.65rem; }
.oz-f { display:flex; flex-direction:column; gap:.25rem; }
.oz-full { grid-column:1 / -1; }
.oz-lbl { font-size:.65rem; font-weight:700; color:#475569; letter-spacing:.03em; text-transform:uppercase; }
.oz-req { color:#dc2626; }
.oz-inp { width:100%; border:1px solid #e2e8f0; border-radius:7px; padding:7px 10px; font-size:.78rem; outline:none; font-family:inherit; box-sizing:border-box; transition:border-color .12s; }
.oz-inp:focus { border-color:#93c5fd; box-shadow:0 0 0 3px rgba(147,197,253,.15); }
.oz-inp:disabled { background:#f8fafc; color:#64748b; cursor:default; }
.oz-inp--sig { font-size:.82rem; font-style:italic; }
.oz-ta { width:100%; border:1px solid #e2e8f0; border-radius:7px; padding:7px 10px; font-size:.78rem; outline:none; font-family:inherit; box-sizing:border-box; resize:vertical; transition:border-color .12s; }
.oz-ta:focus { border-color:#93c5fd; box-shadow:0 0 0 3px rgba(147,197,253,.15); }
.oz-ta:disabled { background:#f8fafc; color:#64748b; cursor:default; }
.oz-ta--synthese { min-height:100px; }

/* Table questions */
.oz-badge-count { background:#f1f5f9; color:#475569; border-radius:20px; padding:2px 8px; font-size:.65rem; font-weight:600; border:1px solid #e2e8f0; }
.oz-add { display:inline-flex; align-items:center; gap:.25rem; padding:4px 10px; background:#eff6ff; border:1px solid #bfdbfe; color:#1d4ed8; border-radius:6px; font-size:.7rem; font-weight:600; cursor:pointer; margin-left:auto; transition:all .12s; }
.oz-add:hover { background:#dbeafe; }
.oz-add--upload { background:#f0fdf4; border-color:#bbf7d0; color:#15803d; }
.oz-add--upload:hover { background:#dcfce7; }
.oz-add:disabled { opacity:.5; cursor:default; }
.oz-table-wrap { overflow-x:auto; border:1px solid #e2e8f0; border-radius:8px; margin-top:.3rem; }
.oz-tbl { width:100%; border-collapse:collapse; font-size:.7rem; }
.oz-tbl thead th { padding:.4rem .5rem; background:#0f172a; color:rgba(255,255,255,.85); font-weight:700; text-transform:uppercase; font-size:.6rem; letter-spacing:.04em; }
.oz-tbl tbody td { padding:.3rem .4rem; border-bottom:1px solid #f3f4f6; vertical-align:top; }
.oz-tr-even td { background:#fafbfc; }
.oz-ta-sm { width:100%; border:1px solid #e5e7eb; border-radius:5px; padding:4px 6px; font-size:.7rem; resize:vertical; font-family:inherit; }
.oz-ta-sm:focus { outline:none; border-color:#93c5fd; }
.oz-sel-sm { width:100%; border:1px solid #e2e8f0; border-radius:5px; padding:4px 6px; font-size:.68rem; background:#fff; cursor:pointer; }
.oz-del { background:#fee2e2; border:1px solid #fecaca; color:#dc2626; border-radius:5px; cursor:pointer; padding:3px 5px; font-size:.65rem; display:flex; align-items:center; }
.oz-ec { text-align:center; color:#94a3b8; padding:1.5rem; font-size:.72rem; font-style:italic; }
.oz-n { font-size:.62rem; font-weight:700; color:#94a3b8; }
.tc { text-align:center; }

/* Documents */
.oz-hidden-input { display:none; }
.oz-doc-upload-zone { margin-left:auto; }
.oz-doc-empty { text-align:center; padding:2.5rem 1rem; }
.oz-doc-empty__ico { font-size:2.5rem; color:#bfdbfe; margin-bottom:.75rem; }
.oz-doc-empty__title { font-size:.82rem; font-weight:600; color:#475569; margin:0 0 .3rem; }
.oz-doc-empty__sub { font-size:.7rem; color:#94a3b8; margin:0; }
.oz-doc-list { display:flex; flex-direction:column; gap:.5rem; margin-top:.5rem; }
.oz-doc-item { display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.85rem 1rem; background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0; transition:all .15s; }
.oz-doc-item:hover { border-color:#bfdbfe; background:#f0f7ff; }
.oz-doc--validated { border-left:4px solid #15803d; background:#f0fdf4; }
.oz-doc--rejected { border-left:4px solid #dc2626; background:#fef2f2; }
.oz-doc-item__left { display:flex; align-items:center; gap:.75rem; min-width:0; }
.oz-doc-icon { flex-shrink:0; width:42px; height:42px; background:#dbeafe; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; color:#1d4ed8; }
.oz-doc-icon--excel { background:#e8f5e9; color:#2e7d32; }
.oz-doc-info { min-width:0; }
.oz-doc-name { font-size:.78rem; font-weight:600; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:280px; }
.oz-doc-meta { font-size:.62rem; color:#64748b; display:flex; gap:.4rem; flex-wrap:wrap; align-items:center; margin-top:.15rem; }
.oz-doc-status-badge { display:inline-flex; align-items:center; padding:1px 6px; border-radius:10px; font-size:.6rem; font-weight:600; }
.ozds--draft { background:#e2e8f0; color:#475569; }
.ozds--validated { background:#dcfce7; color:#15803d; }
.ozds--rejected { background:#fee2e2; color:#dc2626; }
.oz-doc-actions { display:flex; align-items:center; gap:.3rem; flex-shrink:0; }
.oz-doc-btn { background:none; border:1px solid #e2e8f0; border-radius:6px; padding:5px 8px; cursor:pointer; color:#64748b; transition:all .12s; display:flex; align-items:center; gap:.25rem; font-size:.68rem; font-weight:600; }
.oz-doc-btn:hover { background:#f1f5f9; }
.oz-doc-btn--primary { background:#1e40af; color:#fff; border-color:#1e40af; padding:5px 12px; }
.oz-doc-btn--primary:hover { background:#1d3a8a; }
.oz-doc-btn--valid:hover { color:#15803d; border-color:#15803d; background:#dcfce7; }
.oz-doc-btn--reject:hover { color:#dc2626; border-color:#dc2626; background:#fee2e2; }
.oz-doc-btn--delete:hover { color:#dc2626; border-color:#dc2626; }

/* Synthèse */
.oz-synthese-grid { display:flex; flex-direction:column; gap:.85rem; }
.oz-sig-row { display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; }
.oz-sig-box { display:flex; flex-direction:column; gap:.3rem; }
.oz-sig-line { height:1px; background:repeating-linear-gradient(90deg,#cbd5e1 0,#cbd5e1 6px,transparent 6px,transparent 12px); margin-top:.5rem; }

/* IA */
.oz-card--ia { background:linear-gradient(135deg,#faf5ff,#f0f9ff); border-color:#c4b5fd; }
.oz-ia-header { justify-content:flex-start; }
.oz-ia-score { margin-left:auto; font-size:1.5rem; font-weight:800; color:#7c3aed; }
.oz-ia-score__max { font-size:.75rem; color:#94a3b8; font-weight:500; }
.oz-ia-synth { font-size:.78rem; color:#374151; line-height:1.65; margin:.5rem 0 1rem; padding:.75rem; background:rgba(255,255,255,.7); border-radius:8px; border-left:3px solid #7c3aed; }
.oz-ia-cols { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; margin-bottom:.75rem; }
.oz-ia-section { border-radius:8px; padding:.75rem; }
.oz-ia-section--ok { background:#f0fdf4; border:1px solid #bbf7d0; }
.oz-ia-section--warn { background:#fffbeb; border:1px solid #fde68a; }
.oz-ia-section--danger { background:#fef2f2; border:1px solid #fecaca; margin-bottom:.75rem; }
.oz-ia-section--rec { background:#f0f7ff; border:1px solid #bfdbfe; }
.oz-ia-section__title { display:flex; align-items:center; gap:.3rem; font-size:.7rem; font-weight:700; margin-bottom:.4rem; }
.oz-ia-section--ok .oz-ia-section__title { color:#15803d; }
.oz-ia-section--warn .oz-ia-section__title { color:#b45309; }
.oz-ia-section--danger .oz-ia-section__title { color:#dc2626; }
.oz-ia-section--rec .oz-ia-section__title { color:#1d4ed8; }
.oz-ia-item { font-size:.72rem; color:#374151; padding:.25rem 0 .25rem .75rem; border-left:2px solid currentColor; margin-bottom:.2rem; line-height:1.5; }
.oz-ia-section--ok .oz-ia-item { border-color:#86efac; }
.oz-ia-section--warn .oz-ia-item { border-color:#fcd34d; }
.oz-ia-section--danger .oz-ia-item { border-color:#fca5a5; }
.oz-ia-section--rec .oz-ia-item { border-color:#93c5fd; }
.oz-btn-ia { display:inline-flex; align-items:center; gap:.35rem; padding:8px 18px; background:linear-gradient(135deg,#7c3aed,#6d28d9); color:#fff; border:none; border-radius:8px; font-size:.75rem; font-weight:600; cursor:pointer; align-self:flex-start; transition:all .15s; }
.oz-btn-ia:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(109,40,217,.3); }
.oz-btn-ia:disabled { opacity:.55; cursor:default; transform:none; }

/* Toast */
.oz-toast { position:fixed; bottom:1.2rem; right:1.2rem; display:flex; align-items:center; gap:.4rem; padding:.65rem 1.1rem; border-radius:10px; font-size:.75rem; font-weight:600; z-index:9999; pointer-events:none; box-shadow:0 4px 14px rgba(0,0,0,.12); }
.ozt--success { background:#dcfce7; color:#15803d; border:1px solid #a7f3d0; }
.ozt--error   { background:#fee2e2; color:#dc2626; border:1px solid #fecaca; }
.ozt--info    { background:#dbeafe; color:#1d4ed8; border:1px solid #bfdbfe; }
.oz-spin { width:13px; height:13px; border:2px solid rgba(255,255,255,.3); border-top-color:#fff; border-radius:50%; animation:spin .7s linear infinite; display:inline-block; }
.oz-spin--dark { border:2px solid rgba(0,0,0,.15); border-top-color:#15803d; }
@keyframes spin { to { transform:rotate(360deg); } }
.toast-enter-active, .toast-leave-active { transition:all .3s; }
.toast-enter-from, .toast-leave-to { opacity:0; transform:translateY(10px); }
@media (max-width:640px) {
  .oz-g2 { grid-template-columns:1fr; }
  .oz-full { grid-column:1; }
  .oz-sig-row { grid-template-columns:1fr; }
  .oz-ia-cols { grid-template-columns:1fr; }
  .oz-doc-item { flex-direction:column; align-items:flex-start; }
}
</style>