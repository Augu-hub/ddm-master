<template>
  <!-- ═══════════════════════════════════════════════════════════════════
       WordEditorModal.vue
       Usage : <WordEditorModal v-model:show="editModal.show"
                                :doc="editModal.doc"
                                :url-edit-base="urlEditDocBase"
                                :url-save-base="urlSaveDocBase"
                                @saved="onDocSaved" />
       Dépendances npm :
         npm install @tiptap/vue-3 @tiptap/pm @tiptap/starter-kit
                     @tiptap/extension-underline @tiptap/extension-text-align
                     @tiptap/extension-color @tiptap/extension-text-style
                     @tiptap/extension-highlight @tiptap/extension-table
                     @tiptap/extension-table-row @tiptap/extension-table-header
                     @tiptap/extension-table-cell
  ═══════════════════════════════════════════════════════════════════ -->
  <Teleport to="body">
    <Transition name="wem-fade">
      <div v-if="show" class="wem-backdrop" @click.self="handleClose">
        <div class="wem-modal">

          <!-- ── Header ── -->
          <div class="wem-header">
            <div class="wem-header__left">
              <div class="wem-file-icon"><i class="ti ti-file-word"></i></div>
              <div>
                <div class="wem-title">Édition du document</div>
                <div class="wem-filename">{{ doc?.original_name }}</div>
              </div>
            </div>
            <div class="wem-header__right">
              <span v-if="state.dirty" class="wem-dirty-badge"><i class="ti ti-pencil"></i> Non sauvegardé</span>
              <button class="wem-icon-btn" @click="handleClose" title="Fermer"><i class="ti ti-x"></i></button>
            </div>
          </div>

          <!-- ── Toolbar ── -->
          <div v-if="editor && !state.loading" class="wem-toolbar">
            <!-- Historique -->
            <button class="wt-btn" :disabled="!editor.can().undo()" @click="editor.chain().focus().undo().run()" title="Annuler"><i class="ti ti-arrow-back-up"></i></button>
            <button class="wt-btn" :disabled="!editor.can().redo()" @click="editor.chain().focus().redo().run()" title="Refaire"><i class="ti ti-arrow-forward-up"></i></button>
            <div class="wt-sep"></div>

            <!-- Titres -->
            <select class="wt-select" @change="applyHeading($event)">
              <option value="0" :selected="!editor.isActive('heading')">Paragraphe</option>
              <option value="1" :selected="editor.isActive('heading',{level:1})">Titre 1</option>
              <option value="2" :selected="editor.isActive('heading',{level:2})">Titre 2</option>
              <option value="3" :selected="editor.isActive('heading',{level:3})">Titre 3</option>
            </select>
            <div class="wt-sep"></div>

            <!-- Gras / Italique / Souligné / Barré -->
            <button class="wt-btn" :class="{active:editor.isActive('bold')}" @click="editor.chain().focus().toggleBold().run()" title="Gras"><i class="ti ti-bold"></i></button>
            <button class="wt-btn" :class="{active:editor.isActive('italic')}" @click="editor.chain().focus().toggleItalic().run()" title="Italique"><i class="ti ti-italic"></i></button>
            <button class="wt-btn" :class="{active:editor.isActive('underline')}" @click="editor.chain().focus().toggleUnderline().run()" title="Souligné"><i class="ti ti-underline"></i></button>
            <button class="wt-btn" :class="{active:editor.isActive('strike')}" @click="editor.chain().focus().toggleStrike().run()" title="Barré"><i class="ti ti-strikethrough"></i></button>
            <div class="wt-sep"></div>

            <!-- Couleur de texte -->
            <label class="wt-color-btn" title="Couleur texte">
              <i class="ti ti-typography"></i>
              <input type="color" @input="e => editor.chain().focus().setColor((e.target as HTMLInputElement).value).run()" />
            </label>
            <!-- Surlignage -->
            <button class="wt-btn" :class="{active:editor.isActive('highlight')}" @click="editor.chain().focus().toggleHighlight().run()" title="Surligner"><i class="ti ti-highlight"></i></button>
            <div class="wt-sep"></div>

            <!-- Alignement -->
            <button class="wt-btn" :class="{active:editor.isActive({textAlign:'left'})}" @click="editor.chain().focus().setTextAlign('left').run()" title="Gauche"><i class="ti ti-align-left"></i></button>
            <button class="wt-btn" :class="{active:editor.isActive({textAlign:'center'})}" @click="editor.chain().focus().setTextAlign('center').run()" title="Centre"><i class="ti ti-align-center"></i></button>
            <button class="wt-btn" :class="{active:editor.isActive({textAlign:'right'})}" @click="editor.chain().focus().setTextAlign('right').run()" title="Droite"><i class="ti ti-align-right"></i></button>
            <button class="wt-btn" :class="{active:editor.isActive({textAlign:'justify'})}" @click="editor.chain().focus().setTextAlign('justify').run()" title="Justifier"><i class="ti ti-align-justified"></i></button>
            <div class="wt-sep"></div>

            <!-- Listes -->
            <button class="wt-btn" :class="{active:editor.isActive('bulletList')}" @click="editor.chain().focus().toggleBulletList().run()" title="Liste à puces"><i class="ti ti-list"></i></button>
            <button class="wt-btn" :class="{active:editor.isActive('orderedList')}" @click="editor.chain().focus().toggleOrderedList().run()" title="Liste numérotée"><i class="ti ti-list-numbers"></i></button>
            <div class="wt-sep"></div>

            <!-- Tableau -->
            <button class="wt-btn" @click="insertTable" title="Insérer tableau"><i class="ti ti-table"></i></button>
            <button class="wt-btn" v-if="editor.isActive('table')" @click="editor.chain().focus().deleteTable().run()" title="Supprimer tableau"><i class="ti ti-table-off"></i></button>
            <div class="wt-sep"></div>

            <!-- Effacer formatage -->
            <button class="wt-btn" @click="editor.chain().focus().clearNodes().unsetAllMarks().run()" title="Effacer formatage"><i class="ti ti-clear-formatting"></i></button>
          </div>

          <!-- ── Body ── -->
          <div class="wem-body">
            <!-- Loading -->
            <div v-if="state.loading" class="wem-loading">
              <div class="wem-spinner"></div>
              <p>Chargement du document Word…</p>
              <span class="wem-load-sub">Extraction et conversion en cours</span>
            </div>

            <!-- Erreur -->
            <div v-else-if="state.error" class="wem-error">
              <i class="ti ti-alert-circle"></i>
              <p>{{ state.error }}</p>
              <button class="wem-btn wem-btn--ghost" @click="loadDocument">Réessayer</button>
            </div>

            <!-- Éditeur TipTap -->
            <div v-else class="wem-editor-wrap">
              <div class="wem-page">
                <editor-content :editor="editor" class="wem-tiptap" />
              </div>
            </div>
          </div>

          <!-- ── Footer ── -->
          <div class="wem-footer">
            <div class="wem-footer__left">
              <span v-if="state.lastSaved" class="wem-last-saved">
                <i class="ti ti-check"></i> Sauvegardé {{ state.lastSaved }}
              </span>
            </div>
            <div class="wem-footer__right">
              <button class="wem-btn wem-btn--ghost" @click="handleClose">Fermer</button>
              <button class="wem-btn wem-btn--save" :disabled="state.saving || state.loading || !!state.error" @click="saveDocument">
                <span v-if="state.saving" class="wem-spin"></span>
                <i v-else class="ti ti-device-floppy"></i>
                {{ state.saving ? 'Sauvegarde…' : 'Sauvegarder (.docx)' }}
              </button>
            </div>
          </div>

        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, reactive, watch, onBeforeUnmount } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
// StarterKit — default export (inclut Bold, Italic, Strike, History, BulletList, OrderedList, Heading…)
import StarterKit from '@tiptap/starter-kit'
// Extensions — toutes en named export pour compatibilité Vite
import { Underline } from '@tiptap/extension-underline'
import { TextAlign } from '@tiptap/extension-text-align'
import { TextStyle } from '@tiptap/extension-text-style'
import { Color } from '@tiptap/extension-color'
import { Highlight } from '@tiptap/extension-highlight'
import { Table } from '@tiptap/extension-table'
import { TableRow } from '@tiptap/extension-table-row'
import { TableHeader } from '@tiptap/extension-table-header'
import { TableCell } from '@tiptap/extension-table-cell'

// ─────────────────────────────────────────────────────────────
// Props & Emits
// ─────────────────────────────────────────────────────────────
const props = defineProps<{
  show: boolean
  doc: any | null
  urlEditBase: string   // ex: "https://app.test/auditor/ac/outil-entretien/5/documents/__DOC__/edit"
  urlSaveBase: string   // ex: "https://app.test/auditor/ac/outil-entretien/5/documents/__DOC__/save"
}>()

const emit = defineEmits<{
  (e: 'update:show', val: boolean): void
  (e: 'saved', payload: { docId: number; html: string }): void
}>()

// ─────────────────────────────────────────────────────────────
// State
// ─────────────────────────────────────────────────────────────
const state = reactive({
  loading: false,
  saving: false,
  error: null as string | null,
  dirty: false,
  lastSaved: null as string | null,
})

// ─────────────────────────────────────────────────────────────
// Utils
// ─────────────────────────────────────────────────────────────
function docUrl(base: string, docId: number): string {
  return base.replace('__DOC__', String(docId))
}
function timeNow(): string {
  return new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

// ─────────────────────────────────────────────────────────────
// TipTap Editor
// ─────────────────────────────────────────────────────────────
const editor = useEditor({
  extensions: [
    StarterKit.configure({
      history: { depth: 100 },
    }),
    Underline,
    TextAlign.configure({ types: ['heading', 'paragraph'] }),
    TextStyle,
    Color,
    Highlight.configure({ multicolor: true }),
    Table.configure({ resizable: true }),
    TableRow,
    TableHeader,
    TableCell,
  ],
  content: '',
  editorProps: {
    attributes: {
      class: 'wem-tiptap-inner',
    },
  },
  onUpdate: () => {
    state.dirty = true
  },
})

// ─────────────────────────────────────────────────────────────
// Toolbar helpers
// ─────────────────────────────────────────────────────────────
function applyHeading(event: Event) {
  const level = parseInt((event.target as HTMLSelectElement).value)
  if (level === 0) {
    editor.value?.chain().focus().setParagraph().run()
  } else {
    editor.value?.chain().focus().toggleHeading({ level: level as 1|2|3 }).run()
  }
}

function insertTable() {
  editor.value?.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()
}

// ─────────────────────────────────────────────────────────────
// Load document from server
// ─────────────────────────────────────────────────────────────
// Fetch utilitaire — sans header X-Inertia pour éviter
// que l'intercepteur Inertia retourne { code:403 } au lieu de JSON pur
// ─────────────────────────────────────────────────────────────
async function apiFetch(url: string, options: RequestInit = {}): Promise<any> {
  const csrfToken = (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? ''
  const res = await fetch(url, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      // X-Inertia intentionnellement ABSENT → Laravel répond JSON pur
      ...(options.headers ?? {}),
    },
  })
  const json = await res.json()
  if (!res.ok) throw new Error(json?.error ?? json?.message ?? `HTTP ${res.status}`)
  return json
}

async function loadDocument() {
  if (!props.doc) return
  state.loading = true
  state.error   = null
  state.dirty   = false

  try {
    const url  = docUrl(props.urlEditBase, props.doc.id)
    const data = await apiFetch(url, { method: 'GET' })
    if (data.error) throw new Error(data.error)
    editor.value?.commands.setContent(data.html ?? '<p></p>', false)
    state.dirty = false
  } catch (err: any) {
    state.error = err.message ?? 'Erreur inconnue'
  } finally {
    state.loading = false
  }
}

// ─────────────────────────────────────────────────────────────
// Save document to server
// ─────────────────────────────────────────────────────────────
async function saveDocument() {
  if (!props.doc || !editor.value) return
  state.saving = true

  try {
    const html = editor.value.getHTML()
    const url  = docUrl(props.urlSaveBase, props.doc.id)
    const data = await apiFetch(url, { method: 'PUT', body: JSON.stringify({ html }) })
    if (data.error) throw new Error(data.error)
    state.dirty     = false
    state.lastSaved = timeNow()
    emit('saved', { docId: props.doc.id, html })
  } catch (err: any) {
    alert('Erreur sauvegarde : ' + err.message)
  } finally {
    state.saving = false
  }
}

// ─────────────────────────────────────────────────────────────
// Close handler — avertit si non sauvegardé
// ─────────────────────────────────────────────────────────────
function handleClose() {
  if (state.dirty) {
    if (!confirm('Des modifications non sauvegardées seront perdues. Fermer quand même ?')) return
  }
  emit('update:show', false)
}

// ─────────────────────────────────────────────────────────────
// Watch show → charger quand la modale s'ouvre
// ─────────────────────────────────────────────────────────────
watch(() => props.show, (val) => {
  if (val && props.doc) {
    state.lastSaved = null
    state.dirty     = false
    loadDocument()
  } else if (!val) {
    // Reset éditeur quand on ferme
    editor.value?.commands.clearContent()
    state.error   = null
    state.loading = false
    state.dirty   = false
  }
})

onBeforeUnmount(() => {
  editor.value?.destroy()
})
</script>

<style scoped>
/* ══════════════════════════════════════════════════════════
   BACKDROP & MODAL
══════════════════════════════════════════════════════════ */
.wem-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  padding: 1rem;
}

.wem-modal {
  background: #fff;
  border-radius: 14px;
  width: 100%;
  max-width: 960px;
  height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 25px 60px rgba(15, 23, 42, 0.25);
  overflow: hidden;
}

/* ══════════════════════════════════════════════════════════
   HEADER
══════════════════════════════════════════════════════════ */
.wem-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
  flex-shrink: 0;
}
.wem-header__left {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}
.wem-header__right {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.wem-file-icon {
  width: 36px;
  height: 36px;
  background: #dbeafe;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  color: #1d4ed8;
}
.wem-title {
  font-size: 0.78rem;
  font-weight: 700;
  color: #0f172a;
}
.wem-filename {
  font-size: 0.65rem;
  color: #64748b;
  max-width: 400px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.wem-dirty-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.2rem;
  background: #fef3c7;
  color: #92400e;
  border: 1px solid #fde68a;
  border-radius: 20px;
  padding: 2px 8px;
  font-size: 0.62rem;
  font-weight: 600;
}
.wem-icon-btn {
  background: none;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #64748b;
  font-size: 0.85rem;
}
.wem-icon-btn:hover { background: #f1f5f9; }

/* ══════════════════════════════════════════════════════════
   TOOLBAR
══════════════════════════════════════════════════════════ */
.wem-toolbar {
  display: flex;
  align-items: center;
  gap: 2px;
  padding: 6px 10px;
  border-bottom: 1px solid #e2e8f0;
  background: #fff;
  flex-shrink: 0;
  flex-wrap: wrap;
  overflow-x: auto;
}
.wt-btn {
  background: none;
  border: 1px solid transparent;
  border-radius: 5px;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #475569;
  font-size: 0.82rem;
  transition: all 0.1s;
  flex-shrink: 0;
}
.wt-btn:hover { background: #f1f5f9; border-color: #e2e8f0; }
.wt-btn.active { background: #dbeafe; color: #1d4ed8; border-color: #bfdbfe; }
.wt-btn:disabled { opacity: 0.4; cursor: default; }

.wt-select {
  height: 28px;
  border: 1px solid #e2e8f0;
  border-radius: 5px;
  padding: 0 6px;
  font-size: 0.67rem;
  color: #374151;
  background: #fff;
  cursor: pointer;
  flex-shrink: 0;
}
.wt-sep {
  width: 1px;
  height: 18px;
  background: #e2e8f0;
  margin: 0 3px;
  flex-shrink: 0;
}
.wt-color-btn {
  position: relative;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #e2e8f0;
  border-radius: 5px;
  cursor: pointer;
  color: #475569;
  font-size: 0.82rem;
  overflow: hidden;
  flex-shrink: 0;
}
.wt-color-btn input[type=color] {
  position: absolute;
  opacity: 0;
  inset: 0;
  cursor: pointer;
  width: 100%;
  height: 100%;
}
.wt-color-btn:hover { background: #f1f5f9; }

/* ══════════════════════════════════════════════════════════
   BODY
══════════════════════════════════════════════════════════ */
.wem-body {
  flex: 1;
  overflow: auto;
  background: #f1f5f9;
  display: flex;
  flex-direction: column;
}

/* Loading */
.wem-loading {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  color: #64748b;
}
.wem-spinner {
  width: 36px;
  height: 36px;
  border: 3px solid #e2e8f0;
  border-top-color: #1e40af;
  border-radius: 50%;
  animation: wem-spin 0.7s linear infinite;
}
@keyframes wem-spin { to { transform: rotate(360deg); } }
.wem-load-sub { font-size: 0.65rem; color: #94a3b8; }

/* Error */
.wem-error {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  color: #dc2626;
  font-size: 0.8rem;
}
.wem-error i { font-size: 2rem; }

/* Editor page */
.wem-editor-wrap {
  flex: 1;
  display: flex;
  justify-content: center;
  padding: 1.5rem 1rem;
}
.wem-page {
  background: #fff;
  width: 100%;
  max-width: 760px;
  min-height: 100%;
  border-radius: 8px;
  box-shadow: 0 1px 8px rgba(15,23,42,0.08);
  padding: 2rem 2.5rem;
  border: 1px solid #e2e8f0;
}

/* TipTap inner */
:deep(.wem-tiptap-inner) {
  outline: none;
  font-family: 'Calibri', 'Georgia', serif;
  font-size: 11pt;
  line-height: 1.6;
  color: #1e293b;
  min-height: 400px;
}
:deep(.wem-tiptap-inner p)       { margin: 4px 0; }
:deep(.wem-tiptap-inner h1)      { font-size: 1.6em; font-weight: 700; margin: 12px 0 6px; }
:deep(.wem-tiptap-inner h2)      { font-size: 1.3em; font-weight: 700; margin: 10px 0 5px; }
:deep(.wem-tiptap-inner h3)      { font-size: 1.1em; font-weight: 700; margin: 8px 0 4px; }
:deep(.wem-tiptap-inner ul)      { padding-left: 1.5rem; list-style: disc; }
:deep(.wem-tiptap-inner ol)      { padding-left: 1.5rem; list-style: decimal; }
:deep(.wem-tiptap-inner table)   { border-collapse: collapse; width: 100%; margin: 8px 0; }
:deep(.wem-tiptap-inner td),
:deep(.wem-tiptap-inner th)      { border: 1px solid #cbd5e1; padding: 6px 8px; vertical-align: top; }
:deep(.wem-tiptap-inner th)      { background: #f8fafc; font-weight: 600; }
:deep(.wem-tiptap-inner mark)    { background: #fef08a; color: inherit; }
:deep(.wem-tiptap-inner .is-editor-empty:first-child::before) {
  content: attr(data-placeholder);
  float: left;
  color: #94a3b8;
  pointer-events: none;
  height: 0;
}

/* ══════════════════════════════════════════════════════════
   FOOTER
══════════════════════════════════════════════════════════ */
.wem-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
  flex-shrink: 0;
}
.wem-footer__right {
  display: flex;
  gap: 0.4rem;
}
.wem-last-saved {
  font-size: 0.65rem;
  color: #15803d;
  display: inline-flex;
  align-items: center;
  gap: 0.2rem;
}
.wem-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 6px 14px;
  border: none;
  border-radius: 6px;
  font-size: 0.73rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.12s;
}
.wem-btn--ghost { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
.wem-btn--ghost:hover { background: #e2e8f0; }
.wem-btn--save { background: #1e40af; color: #fff; }
.wem-btn--save:hover { background: #1d3a8a; }
.wem-btn--save:disabled { opacity: 0.55; cursor: default; }
.wem-spin {
  width: 12px;
  height: 12px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: wem-spin 0.7s linear infinite;
  display: inline-block;
}

/* ══════════════════════════════════════════════════════════
   TRANSITION
══════════════════════════════════════════════════════════ */
.wem-fade-enter-active, .wem-fade-leave-active { transition: all 0.2s ease; }
.wem-fade-enter-from, .wem-fade-leave-to { opacity: 0; transform: scale(0.97); }

/* ══════════════════════════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════════════════════════ */
@media (max-width: 640px) {
  .wem-page { padding: 1rem; }
  .wem-modal { height: 95vh; border-radius: 10px 10px 0 0; margin-top: auto; }
  .wem-filename { max-width: 160px; }
}
</style>