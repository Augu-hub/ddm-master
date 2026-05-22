<template>
  <Teleport to="body">
    <Transition name="eem-fade">
      <div v-if="show" class="eem-backdrop" @click.self="handleClose">
        <div class="eem-modal">

          <div class="eem-header">
            <div class="eem-header__left">
              <div class="eem-file-icon"><i class="ti ti-file-spreadsheet"></i></div>
              <div>
                <div class="eem-title">Édition du document Excel</div>
                <div class="eem-filename">{{ doc?.original_name }}</div>
              </div>
            </div>
            <div class="eem-header__right">
              <span v-if="isDirty" class="eem-dirty-badge"><i class="ti ti-pencil"></i> Non sauvegardé</span>
              <button class="eem-icon-btn" @click="handleClose" title="Fermer"><i class="ti ti-x"></i></button>
            </div>
          </div>

          <div v-if="!loading && !error" class="eem-toolbar">
            <button class="eet-btn" @click="addRow"><i class="ti ti-row-insert-bottom"></i> Ligne</button>
            <button class="eet-btn" @click="addColumn"><i class="ti ti-column-insert-right"></i> Colonne</button>
            <div class="eet-sep"></div>
            <button class="eet-btn" @click="removeRow"><i class="ti ti-row-remove"></i> Suppr ligne</button>
            <button class="eet-btn" @click="removeColumn"><i class="ti ti-column-remove"></i> Suppr col</button>
            <div class="eet-sep"></div>
            <button class="eet-btn" @click="formatBold"><i class="ti ti-bold"></i></button>
            <button class="eet-btn" @click="formatItalic"><i class="ti ti-italic"></i></button>
            <div class="eet-sep"></div>
            <label class="eet-color-btn" title="Couleur cellule">
              <i class="ti ti-palette"></i>
              <input type="color" @input="e => changeBackgroundColor((e.target as HTMLInputElement).value)" />
            </label>
          </div>

          <div class="eem-body">
            <div v-if="loading" class="eem-loading">
              <div class="eem-spinner"></div>
              <p>Chargement du document Excel…</p>
            </div>

            <div v-else-if="error" class="eem-error">
              <i class="ti ti-alert-circle"></i>
              <p>{{ error }}</p>
              <button class="eem-btn eem-btn--ghost" @click="loadDocument">Réessayer</button>
            </div>

            <div v-else class="eem-editor-wrap">
              <div class="eem-spreadsheet" ref="spreadsheetRef">
                <div id="hot-container"></div>
              </div>
            </div>
          </div>

          <div class="eem-footer">
            <div class="eem-footer__left">
              <span v-if="lastSaved" class="eem-last-saved">
                <i class="ti ti-check"></i> Sauvegardé {{ lastSaved }}
              </span>
              <span v-if="!loading && !error && rowCount > 0" class="eem-info">
                {{ rowCount }} lignes × {{ colCount }} colonnes
              </span>
            </div>
            <div class="eem-footer__right">
              <button class="eem-btn eem-btn--ghost" @click="handleClose">Fermer</button>
              <button class="eem-btn eem-btn--save" :disabled="saving || loading || !!error" @click="saveDocument">
                <span v-if="saving" class="eem-spin"></span>
                <i v-else class="ti ti-device-floppy"></i>
                {{ saving ? 'Sauvegarde…' : 'Sauvegarder' }}
              </button>
            </div>
          </div>

        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, watch, onBeforeUnmount, nextTick } from 'vue'
import * as XLSX from 'xlsx'
import Handsontable from 'handsontable'
import 'handsontable/styles/handsontable.css'
import 'handsontable/styles/ht-theme-main.css'

// Props
const props = defineProps<{
  show: boolean
  doc: any | null
  urlLoadBase: string
  urlSaveBase: string
}>()

const emit = defineEmits<{
  (e: 'update:show', val: boolean): void
  (e: 'saved', payload: { docId: number; data: any[][] }): void
}>()

// State
const loading = ref(false)
const saving = ref(false)
const error = ref<string | null>(null)
const isDirty = ref(false)
const lastSaved = ref<string | null>(null)
const spreadsheetRef = ref<HTMLElement | null>(null)
const rowCount = ref(0)
const colCount = ref(0)

let hotInstance: any = null

// Utils
function docUrl(base: string, docId: number): string {
  return base?.replace('__DOC__', String(docId)) || ''
}

function getCsrfToken(): string {
  return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content || ''
}

// Initialisation Handsontable - SANS FORMULAS
function initHotTable(data: any[][]) {
  const container = document.getElementById('hot-container')
  if (!container) {
    console.error('Container hot-container non trouvé')
    return false
  }
  
  if (hotInstance) {
    hotInstance.destroy()
    hotInstance = null
  }
  
  rowCount.value = data.length
  colCount.value = data[0]?.length || 0
  
  try {
    hotInstance = new Handsontable(container, {
      data: data,
      rowHeaders: true,
      colHeaders: true,
      contextMenu: true,
      filters: true,
      dropdownMenu: true,
      manualRowMove: true,
      manualColumnMove: true,
      manualRowResize: true,
      manualColumnResize: true,
      width: '100%',
      height: '100%',
      stretchH: 'all',
      autoWrapRow: true,
      autoWrapCol: true,
      allowInsertRow: true,
      allowInsertColumn: true,
      allowRemoveRow: true,
      allowRemoveColumn: true,
      // Désactiver complètement les formules pour éviter l'erreur
      formulas: false,
      // Gestion des changements
      afterChange: (changes: any[] | null, source: string) => {
        if (changes && source !== 'loadData' && source !== 'external') {
          isDirty.value = true
        }
      },
      afterCreateRow: () => {
        rowCount.value = hotInstance?.countRows() || 0
        isDirty.value = true
      },
      afterCreateCol: () => {
        colCount.value = hotInstance?.countCols() || 0
        isDirty.value = true
      },
      afterRemoveRow: () => {
        rowCount.value = hotInstance?.countRows() || 0
        isDirty.value = true
      },
      afterRemoveCol: () => {
        colCount.value = hotInstance?.countCols() || 0
        isDirty.value = true
      }
    })
    return true
  } catch (err) {
    console.error('Erreur création Handsontable:', err)
    return false
  }
}

// Actions
function addRow() {
  if (hotInstance) {
    const rowIndex = hotInstance.countRows()
    hotInstance.alter('insert_row', rowIndex, 1)
    setTimeout(() => hotInstance?.scrollViewportTo(rowIndex, 0), 50)
  }
}

function addColumn() {
  if (hotInstance) {
    const colIndex = hotInstance.countCols()
    hotInstance.alter('insert_col', colIndex, 1)
    setTimeout(() => hotInstance?.scrollViewportTo(0, colIndex), 50)
  }
}

function removeRow() {
  if (hotInstance) {
    const selected = hotInstance.getSelected()
    if (selected && selected[0]) {
      hotInstance.alter('remove_row', selected[0][0], 1)
    } else if (rowCount.value > 1) {
      hotInstance.alter('remove_row', rowCount.value - 1, 1)
    }
  }
}

function removeColumn() {
  if (hotInstance) {
    const selected = hotInstance.getSelected()
    if (selected && selected[0]) {
      hotInstance.alter('remove_col', selected[0][1], 1)
    } else if (colCount.value > 1) {
      hotInstance.alter('remove_col', colCount.value - 1, 1)
    }
  }
}

function formatBold() {
  if (hotInstance) {
    const selected = hotInstance.getSelected()
    if (selected && selected[0]) {
      const range = selected[0]
      for (let r = range[0]; r <= range[2]; r++) {
        for (let c = range[1]; c <= range[3]; c++) {
          const cellMeta = hotInstance.getCellMeta(r, c)
          const currentStyle = cellMeta.style || {}
          const isBold = currentStyle.fontWeight === 'bold'
          hotInstance.setCellMeta(r, c, 'style', { ...currentStyle, fontWeight: isBold ? 'normal' : 'bold' })
        }
      }
      hotInstance.render()
    }
  }
}

function formatItalic() {
  if (hotInstance) {
    const selected = hotInstance.getSelected()
    if (selected && selected[0]) {
      const range = selected[0]
      for (let r = range[0]; r <= range[2]; r++) {
        for (let c = range[1]; c <= range[3]; c++) {
          const cellMeta = hotInstance.getCellMeta(r, c)
          const currentStyle = cellMeta.style || {}
          const isItalic = currentStyle.fontStyle === 'italic'
          hotInstance.setCellMeta(r, c, 'style', { ...currentStyle, fontStyle: isItalic ? 'normal' : 'italic' })
        }
      }
      hotInstance.render()
    }
  }
}

function changeBackgroundColor(color: string) {
  if (hotInstance) {
    const selected = hotInstance.getSelected()
    if (selected && selected[0]) {
      const range = selected[0]
      for (let r = range[0]; r <= range[2]; r++) {
        for (let c = range[1]; c <= range[3]; c++) {
          const currentStyle = hotInstance.getCellMeta(r, c).style || {}
          hotInstance.setCellMeta(r, c, 'style', { ...currentStyle, backgroundColor: color })
        }
      }
      hotInstance.render()
    }
  }
}

// Chargement Excel
async function loadDocument() {
  if (!props.doc) return
  
  loading.value = true
  error.value = null
  isDirty.value = false

  try {
    const url = docUrl(props.urlLoadBase, props.doc.id)
    console.log('Chargement Excel depuis:', url)
    
    const response = await fetch(url, {
      headers: {
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel'
      }
    })
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`)
    }
    
    const arrayBuffer = await response.arrayBuffer()
    
    // Parser Excel
    const workbook = XLSX.read(arrayBuffer, { type: 'array' })
    const firstSheet = workbook.Sheets[workbook.SheetNames[0]]
    const data = XLSX.utils.sheet_to_json(firstSheet, { header: 1, defval: '' })
    
    if (!data || (data as any[]).length === 0) {
      data[0] = ['']
    }
    
    const cleanedData = (data as any[][]).map(row => 
      row.map(cell => (cell === null || cell === undefined) ? '' : cell)
    )
    
    await nextTick()
    
    // Attendre que le DOM soit prêt
    setTimeout(() => {
      const success = initHotTable(cleanedData)
      if (!success) {
        error.value = "Erreur lors de l'initialisation de l'éditeur"
      } else {
        console.log('Excel chargé avec succès:', cleanedData.length, 'lignes')
      }
    }, 100)
    
  } catch (err: any) {
    console.error('Erreur chargement Excel:', err)
    error.value = err.message || 'Erreur de chargement du fichier Excel'
    loading.value = false
  } finally {
    loading.value = false
  }
}

// Sauvegarde Excel
async function saveDocument() {
  if (!props.doc || !hotInstance) return
  
  saving.value = true

  try {
    const currentData = hotInstance.getData()
    const cleanData = currentData.map(row => 
      row.map(cell => (cell === null || cell === undefined) ? '' : cell)
    )
    
    // Créer nouveau fichier Excel
    const worksheet = XLSX.utils.aoa_to_sheet(cleanData)
    const workbook = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Sheet1')
    const excelBuffer = XLSX.write(workbook, { bookType: 'xlsx', type: 'array' })
    const excelBlob = new Blob([excelBuffer], { 
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
    })
    
    const formData = new FormData()
    formData.append('document', excelBlob, props.doc.original_name)
    
    const url = docUrl(props.urlSaveBase, props.doc.id)
    const response = await fetch(url, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': getCsrfToken() },
      body: formData
    })
    
    const result = await response.json()
    if (!response.ok) throw new Error(result.error || 'Erreur de sauvegarde')
    
    isDirty.value = false
    lastSaved.value = new Date().toLocaleTimeString('fr-FR')
    emit('saved', { docId: props.doc.id, data: cleanData })
    
  } catch (err: any) {
    console.error('Erreur sauvegarde:', err)
    alert('Erreur lors de la sauvegarde : ' + err.message)
  } finally {
    saving.value = false
  }
}

// Fermeture
function handleClose() {
  if (isDirty.value) {
    if (!confirm('Des modifications non sauvegardées seront perdues. Fermer quand même ?')) return
  }
  emit('update:show', false)
}

// Watcher
watch(() => props.show, (val) => {
  if (val && props.doc) {
    lastSaved.value = null
    isDirty.value = false
    loadDocument()
  } else if (!val && hotInstance) {
    hotInstance.destroy()
    hotInstance = null
  }
})

onBeforeUnmount(() => {
  if (hotInstance) {
    hotInstance.destroy()
    hotInstance = null
  }
})
</script>

<style scoped>
.eem-backdrop {
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

.eem-modal {
  background: #fff;
  border-radius: 14px;
  width: 100%;
  max-width: 1200px;
  height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 25px 60px rgba(15, 23, 42, 0.25);
  overflow: hidden;
}

.eem-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
  flex-shrink: 0;
}
.eem-header__left { display: flex; align-items: center; gap: 0.6rem; }
.eem-header__right { display: flex; align-items: center; gap: 0.5rem; }
.eem-file-icon {
  width: 36px; height: 36px; background: #e8f5e9; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; color: #2e7d32;
}
.eem-title { font-size: 0.78rem; font-weight: 700; color: #0f172a; }
.eem-filename { font-size: 0.65rem; color: #64748b; max-width: 400px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.eem-dirty-badge {
  display: inline-flex; align-items: center; gap: 0.2rem; background: #fef3c7;
  color: #92400e; border: 1px solid #fde68a; border-radius: 20px;
  padding: 2px 8px; font-size: 0.62rem; font-weight: 600;
}
.eem-icon-btn {
  background: none; border: 1px solid #e2e8f0; border-radius: 6px;
  width: 28px; height: 28px; display: flex; align-items: center;
  justify-content: center; cursor: pointer; color: #64748b; font-size: 0.85rem;
}
.eem-icon-btn:hover { background: #f1f5f9; }

.eem-toolbar {
  display: flex; align-items: center; gap: 4px; padding: 6px 10px;
  border-bottom: 1px solid #e2e8f0; background: #fff; flex-shrink: 0;
  flex-wrap: wrap;
}
.eet-btn {
  background: none; border: 1px solid transparent; border-radius: 5px;
  padding: 4px 8px; display: inline-flex; align-items: center; gap: 4px;
  cursor: pointer; color: #475569; font-size: 0.7rem; font-weight: 500;
  transition: all 0.1s;
}
.eet-btn:hover { background: #f1f5f9; border-color: #e2e8f0; }
.eet-sep { width: 1px; height: 20px; background: #e2e8f0; margin: 0 4px; }
.eet-color-btn {
  position: relative; display: inline-flex; align-items: center; gap: 4px;
  padding: 4px 8px; border: 1px solid #e2e8f0; border-radius: 5px;
  cursor: pointer; color: #475569; font-size: 0.7rem; overflow: hidden;
}
.eet-color-btn input[type=color] {
  position: absolute; opacity: 0; inset: 0; cursor: pointer; width: 100%; height: 100%;
}
.eet-color-btn:hover { background: #f1f5f9; }

.eem-body { flex: 1; overflow: hidden; background: #f1f5f9; display: flex; flex-direction: column; }
.eem-loading { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.6rem; color: #64748b; }
.eem-spinner {
  width: 36px; height: 36px; border: 3px solid #e2e8f0;
  border-top-color: #2e7d32; border-radius: 50%;
  animation: eem-spin 0.7s linear infinite;
}
@keyframes eem-spin { to { transform: rotate(360deg); } }
.eem-error { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.6rem; color: #dc2626; font-size: 0.8rem; }
.eem-error i { font-size: 2rem; }
.eem-editor-wrap { flex: 1; display: flex; padding: 1rem; overflow: auto; }
.eem-spreadsheet { width: 100%; height: 100%; background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; }
#hot-container { width: 100%; height: 100%; }

.eem-footer {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.75rem 1rem; border-top: 1px solid #e2e8f0;
  background: #f8fafc; flex-shrink: 0;
}
.eem-footer__right { display: flex; gap: 0.4rem; }
.eem-footer__left { display: flex; gap: 0.8rem; }
.eem-last-saved { font-size: 0.65rem; color: #15803d; display: inline-flex; align-items: center; gap: 0.2rem; }
.eem-info { font-size: 0.65rem; color: #64748b; display: inline-flex; align-items: center; gap: 0.2rem; }
.eem-btn {
  display: inline-flex; align-items: center; gap: 0.25rem; padding: 6px 14px;
  border: none; border-radius: 6px; font-size: 0.73rem; font-weight: 600;
  cursor: pointer; transition: all 0.12s;
}
.eem-btn--ghost { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
.eem-btn--ghost:hover { background: #e2e8f0; }
.eem-btn--save { background: #2e7d32; color: #fff; }
.eem-btn--save:hover { background: #1b5e20; }
.eem-btn--save:disabled { opacity: 0.55; cursor: default; }
.eem-spin {
  width: 12px; height: 12px; border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff; border-radius: 50%; animation: eem-spin 0.7s linear infinite;
  display: inline-block;
}

.eem-fade-enter-active, .eem-fade-leave-active { transition: all 0.2s ease; }
.eem-fade-enter-from, .eem-fade-leave-to { opacity: 0; transform: scale(0.97); }

@media (max-width: 640px) {
  .eem-modal { height: 95vh; border-radius: 10px 10px 0 0; margin-top: auto; }
  .eem-filename { max-width: 160px; }
  .eet-btn span { display: none; }
  .eet-btn { padding: 4px 6px; }
}
</style>